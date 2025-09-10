<?php
/**
 * 框架 Src 资源处理模块 
 * Resource 资源类，要管理任意类型的文件资源，需要建立此类的实例
 * 应根据 资源后缀名，或 资源后缀名在 Mime::$processable 数组中的 键名(类型) 创建子类
 * 例如：src\resource\Js | src\resource\Plain | src\resource\Image | src\resource\Video ...
 * 如果要处理的资源 不匹配任何 Resource 子类，则 直接使用 Resource 类处理
 */

namespace Spf\module\src;

use Spf\Request;
use Spf\Response;
use Spf\App;
use Spf\module\Src;
use Spf\exception\BaseException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Num;
use Spf\util\Curl;

class Resource 
{
    /**
     * 预定义的 资源来源类型
     * 不同的来源，将有不同的 content 内容读取方法
     */
    public static $sourceTypes = [
        "local",        //真实存在的本地文件
        "remote",       //远程资源
        "require",      //通过 require 本地 php 文件动态生成内容
        //"export",       //通过调用 当前应用的 某个操作方法，生成的文件

        //针对 plain 纯文本文件，还可能有以下来源
        "build",        //通过 build 生成的纯文本文件，如合并多个 js 文件
        //"compile",      //通过编译本地文件，生成的文件
        "create",       //通过直接输入文本内容，创建纯文本文件 
    ];

    /**
     * 当前的资源类型的本地文件，是否应保存在 特定路径下
     * 指定的 特定路径 必须在 Src::$current->config->resource["access"] 中定义的 允许访问的文件夹下
     *  null        表示不需要保存在 特定路径下，本地资源应在 [允许访问的文件夹]/... 路径下
     *  "ext"       表示应保存在   [允许访问的文件夹]/[资源后缀名]/... 路径下
     *  "foo/bar"   指定了 特定路径 foo/bar 表示此类型本地资源文件应保存在   [允许访问的文件夹]/foo/bar/... 路径下
     * 默认 null 不指定 特定路径
     * !! 如果必须，子类可以覆盖此属性
     */
    public static $filePath = null;     //可选 null | ext | 其他任意路径形式字符串 foo/bar 首尾不应有 /

    /**
     * 根据 URI 请求参数，查找 并创建 Resource 资源实例
     * 资源管理类的 核心入口方法
     * @param Array|String $uri 字符串 或 数组
     * @param Array $params 创建资源时的 额外参数
     * @return Resource|null 资源实例，或 null
     */
    final public static function create($uri, $params=[])
    {
        try {

            //创建 URI 路径字符
            if (Is::indexed($uri)) $uri = implode("/", $uri);

            if (isset($params["content"]) && Is::nemstr($params["content"])) {
                //通过直接输入内容，创建纯文本文件
                $content = $params["content"];
                unset($params["content"]);
                $params = Is::nemarr($params) ? $params : [];
                //收集 $_GET 中的 参数
                $params = self::getParamsFromGets($params);
                if (strpos($uri, ".")===false) $uri .= ".txt";
                $p = [
                    "uri"   => $uri,
                    "upi" => pathinfo($uri),
                    "ext" => self::getExtFromPath($uri),
                    "real" => "",
                    "type" => "create",
                    "params"    => $params,

                    "content"   => $content
                ];
            } else {
                //传入了 一个远程地址
                if (strpos($uri, "://")!==false && (substr($uri, 0,4)==="http" || substr($uri, 0,5)==="https")) {
                    $uri = str_replace("://","/",$uri);
                }
                //通过输入 调用路径 rawPath 创建 resource 实例
                $p = self::parse($uri);
                if (is_null($p)) {
                    //没有匹配到任何资源，返回 null
                    //throw new SrcException("无法匹配到任何类型的资源", "resource/parse");
                    return null;
                }
                $params = Is::nemarr($params) ? $params : [];
                //合并 params
                $p["params"] = Arr::extend($p["params"], $params);
            }

            //根据 资源 ext 获取存在的 Resource 子类
            $cls = self::resCls($p["ext"] ?? "");
            //var_dump($cls);
            if (empty($cls) || !class_exists($cls)) {
                //Resource 资源子类不存在
                throw new SrcException("不支持或不存在的资源类型", "resource/parse");
            }
            $res = new $cls($p);
            if (!$res instanceof Resource) {
                //Resource 资源类实例化失败
                $clsn = Cls::name($cls);
                throw new SrcException("$clsn,不支持此资源类型", "resource/instance");
            }
            return $res;

        } catch (BaseException $e) {
            $e->handleException();
        }
    }

    /**
     * URI 解析得到资源参数
     * @param Array|String $args 通常是资源请求的 URI 字符|数组
     * @return Array|null 返回完整的 资源参数，未匹配到对应的资源 返回 null
     */
    final public static function parse(...$args)
    {
        if (!Is::nemarr($args) || !Is::indexed($args)) return null;
        //将传入的 参数 拼接为 URI 字符串
        $ouri = implode("/", $args);   //trim(implode("/", $args), "/");
        $uarr = explode("/", $ouri);
        $uri = implode("/", $uarr);
        //var_dump($uri);

        //最终 要输出的 资源参数
        $pi = pathinfo($uri);
        $res = [
            "ouri" => $ouri,
            "uri" => $uri,
            "upi" => $pi,
            "ext" => "",
            "real" => "",
            "type" => "",
            "params" => [
                "min" => strpos($uri, ".min.") !== false
            ]
        ];

        //收集 params
        $res["params"] = self::getParamsFromGets($res["params"]);

        //先判断一次是否传入了真实存在的 本地文件路径
        $lp = str_replace("/",DS,$uri);
        if (file_exists($lp) && !is_dir($lp)) {
            $res = Arr::extend($res, [
                "type" => "local",
                "ext" => self::getExtFromPath($lp),
                "real" => $lp,
            ]);
            return $res;
        }

        //开始 判断 资源的来源类型 以及 其他资源参数
        $types = self::$sourceTypes;
        foreach ($types as $type) {
            //调用 self::isFooBarSource 方法，依次解析 URI
            $m = "is".Str::camel($type, true)."Source";
            if (!method_exists(self::class, $m)) continue;
            $info = self::$m($uri, $res);
            if ($info === false) continue;
            $res = $info;
            break;
        }

        //如果未匹配到对应的 来源类型
        if (!Is::nemstr($res["type"])) return null;

        //返回匹配到的 资源参数
        return $res;
    }

    /**
     * 解析 URI 判断是否为某个来源类型资源的 系列解析方法 isFooBarSource
     * @param String $uri 字符串 foo/bar/jaz.ext
     * @param Array $res 完整的 资源参数格式
     * @return Array|false 如果是此类型的资源，返回完整参数数据，否则 返回 false
     */
    //判断是否 本地存在的文件资源
    protected static function isLocalSource($uri, $res=[])
    {
        //在允许访问的 路径下 查找此文件
        $fp = self::findLocal($uri);
        if (!Is::nemstr($fp)) return false;
        
        //找到文件，表示这是本地资源，准备参数
        return Arr::extend($res, [
            "type" => "local",
            "ext" => self::getExtFromPath($fp),
            "real" => $fp,
        ]);
    }
    //判断是否 远程资源
    protected static function isRemoteSource($uri, $res=[])
    {
        //需要调用原始 URI
        $ouri = $res["ouri"] ?? null;
        if (!Is::nemstr($ouri)) return false;
        $ouri = trim($ouri, "/");
        //以 http|https 开头
        $uarr = explode("/", $ouri);
        if (!in_array(strtolower($uarr[0]), ["http","https"])) return false;
        //构建远程资源 url
        $uarr[0] = $uarr[0].":/";
        $real = implode("/", $uarr);
        //检查远程文件是否存在
        if (self::exists($real)!==true) return false;
        //获取远程文件信息
        //$fi = self::getRemoteFileInfo($real);

        //这是 远程资源
        return Arr::extend($res, [
            "type" => "remote",
            "ext" => self::getExtFromPath($real),    //$fi["ext"],
            "real" => $real
        ]);
    }
    //判断是否 通过 require 本地 php 文件生成资源，例如：请求 foo/bar.js 真实存在文件 foo/bar.js.php 
    protected static function isRequireSource($uri, $res=[])
    {
        //php 文件后缀，在 EXT_CLASS 常量中定义
        $ext = defined("EXT_CLASS") ? EXT_CLASS : ".php";
        $rfp = $uri.$ext;
        //查找是否存在 php 文件
        $rfp = self::findLocal($rfp);
        if (!Is::nemstr($rfp)) return false;

        //找到此 php 文件，表示 这是 require 类型资源
        return Arr::extend($res, [
            "type" => "require",
            "ext" => self::getExtFromPath($uri),
            "real" => $rfp
        ]);
    }
    //判断是否 通过 build 构建的方式，生成文本资源，例如：请求 foo/bar.js 真实存在文件夹 foo/bar.js
    protected static function isBuildSource($uri, $res=[])
    {
        //检查是否存在 文件夹
        $dir = self::findLocal($uri, true);
        if (!Is::nemstr($dir)) return false;

        //找到对应文件夹，表示这是 build 类型资源
        return Arr::extend($res, [
            "type" => "build",
            "ext" => self::getExtFromPath($uri),
            "real" => $dir
        ]);
    }

    /**
     * 在框架允许直接访问的 资源路径下查找对应的 文件|文件夹
     * 如果请求的路径 包含 .min. 将自动去除
     * @param String $path 要查找的 文件|文件夹 路径
     * @param Bool $findDir 是否查找文件夹，默认 false 查找文件
     * @return String|null 找到 文件|文件夹 则返回真实路径 DS，未找到则返回 null
     */
    final public static function findLocal($path, $findDir=false)
    {
        if (!Is::nemstr($path)) return null;

        //是否包含 .min.
        $hasmin = strpos($path, ".min.")!==false;
        if ($hasmin) $nomin = str_replace(".min.",".",$path);

        //构建查询 路径数组
        $pathes = [];

        //默认先直接检查 传入的是否是已经存在的 路径
        $pathes[] = $path;
        //去除 .min. 再次直接检查 传入的是否是已经存在的 路径
        if ($hasmin) $pathes[] = $nomin;

        //将 传入的路径 处理为可拼接的 路径字符串 去除首尾的 /
        $path = trim(str_replace(DS, "/", $path), "/");
        if ($hasmin) $nomin = str_replace(".min.",".",$path);

        /**
         * 默认 可访问的 本地资源 路径在 SRC_PATH | VIEW_PATH | UPLOAD_PATH 下
         * 在 Src::$current->config->resource["access"] 参数中定义
         */
        $dirs = Src::$current->config->resource["access"] ?? ["src", "view", "upload", "spf/assets", /*"spf/view"*/];
        //在允许访问的 dirs 文件夹下，可能还有指定的 特定路径
        $subdir = null;
        //如果查询的是 文件而不是文件夹 则需要查询此文件对应的 Resource 资源类是否定义了 本地文件保存的 特定路径
        if ($findDir!==true) {
            //获取 path 中包含的 后缀名
            $ext = static::getExtFromPath($path);
            if (Is::nemstr($ext)) {
                $ext = strtolower($ext);
                //获取当前路径 path 指向的 Resource 资源类全称
                $rescls = static::resCls($ext);
                //获取当前 Resource 资源类 指定的 本地文件必须保存在的 特定路径
                $subdir = $rescls::$filePath;
                //如果指定的 特定路径是 "ext" 则使用当前的 后缀名替换
                if ($subdir === "ext") $subdir = $ext;
            }
        }
        //将 可能存在的 subdir 拼接到 path 路径之前
        if (Is::nemstr($subdir)) {
            $path = trim($subdir, "/")."/".$path;
            if ($hasmin) $nomin = trim($subdir, "/")."/".$nomin;
        }

        /**
         * 按 优先级 在 允许访问的路径下 查找 文件|文件夹
         */
        //优先在 当前应用路径下查找
        if (App::$isInsed === true){
            $appk = App::$current::clsk();
            //跳过 BaseApp 默认应用
            if ($appk !== "base_app") {
                foreach ($dirs as $dir) {
                    //忽略 框架内部路径
                    if (substr($dir, 0, 4)==="spf/") continue;
                    $pathes[] = "$dir/$appk/$path";
                    if ($hasmin) $pathes[] = "$dir/$appk/$nomin";
                }
            }
        }

        //然后再 webroot 路径下查找
        foreach ($dirs as $dir) {
            //忽略 框架内部路径
            if (substr($dir, 0, 4)==="spf/") continue;
            $pathes[] = "$dir/$path";
            if ($hasmin) $pathes[] = "$dir/$nomin";
        }

        //最后再尝试 spf 框架内部路径
        foreach ($dirs as $dir) {
            //只能访问 框架内部路径
            if (substr($dir, 0, 4)!=="spf/") continue;
            $pathes[] = "$dir/$path";
            if ($hasmin) $pathes[] = "$dir/$nomin";
        }
        /*$pathes[] = "spf/view/$path";
        $pathes[] = "spf/module/src/resource/theme/$path";
        $pathes[] = "spf/module/src/resource/icon/$path";
        if ($hasmin) {
            $pathes[] = "spf/view/$nomin";
            $pathes[] = "spf/module/src/resource/theme/$nomin";
            $pathes[] = "spf/module/src/resource/icon/$nomin";
        }*/

        //var_dump($pathes);exit;

        //调用 Path::exists 方法，查找第一个存在的 路径
        $ftp = $findDir===true ? Path::FIND_DIR : Path::FIND_FILE;
        return Path::exists($pathes, false, $ftp);
    }

    /**
     * 从 $_GET 收集资源输出参数
     * @param Array $params 当前已有的 参数
     * @return Array 合并后的 资源输出参数
     */
    protected static function getParamsFromGets($params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        $params = static::fixParams($params);

        //$_GET
        $gets = Request::$isInsed===true ? Request::$current->gets->ctx() : $_GET;
        $gets = static::fixParams($gets);

        //合并
        return Arr::extend($params, $gets);
    }

    /**
     * 处理 params 传入参数
     * 将 true|false|null|yes|no 转为 bool
     * 将 foo,bar 转为数组
     * @param Array $params
     * @return Array 处理后的
     */
    protected static function fixParams($params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        $rtn = [];
        foreach ($params as $k => $v) {
            if (Is::nemstr($v) && (Is::ntf($v) || in_array(strtolower($v), ["yes","no"]))) {
                //将 true|false|null|yes|no 转为 bool
                if (Is::ntf($v)) {
                    eval("\$v = ".$v.";");
                    if ($v!==true) $v = false;
                } else {
                    $v = strtolower($v) === "yes";
                }
            } else if (Is::explodable($v) !== false) {
                // foo,bar  foo|bar  foo;bar ... 转为数组
                $v = Arr::mk($v);
            }
            $rtn[$k] = $v;
        }
        return $rtn;
    }

    /**
     * 根据 资源后缀名 获取 预定义的 Resource 子类 类全称
     * @param String $ext 资源后缀名
     * @return String 找到的 类全称，没有对应的子类 直接返回 Resource 类
     */
    protected static function resCls($ext)
    {
        //类名前缀
        $cpre = "module/src";
        //传入空参数
        if (!Is::nemstr($ext)) return Cls::find("$cpre/Resource");
        $ext = strtolower($ext);
        $clss = [];
        $clss[] = "$cpre/resource/$ext";
        $processableType = Mime::getProcessableType($ext);
        if (!is_null($processableType)) {
            $clss[] = "$cpre/resource/$processableType";
        } else {
            //$clss[] = "resource/Stream";
            $clss[] = "$cpre/resource/Download";
        }
        $clss[] = "$cpre/Resource";
        //var_dump($clss);
        return Cls::find($clss);
    }



    /**
     * 资源参数
     * 通过 self::parse 方法 解析 URI 后得到的资源参数
     */
    //URI 原始调用路径，通常是传入 Src 模块 default 方法的 参数字符串 foo/bar/jaz.ext
    public $uri = "";
    //根据 URI 得到的 pathinfo
    public $upi = [
        /*
        "basename" => "",
        "filename" => "",
        "dirname" => "",
        "extension" => "",
        */
    ];

    //最终 要输出的 文件后缀名|mime
    public $ext = "";
    public $mime = "";
    public $name = "";  //当资源输出为 下载时，可能需要的 与 $upi["basename"] 一致

    /**
     * 通过 Resource::parse 解析 URI 得到的 实际资源路径
     * 可以是：远程 url，本地 php 文件，本地文件夹，本地文件
     */
    public $real = "";

    //通过 Resource::parse 解析 URI 得到的 资源来源类型，在 Resource::$sourceTypes 中定义的
    public $sourceType = "";

    //资源输出 额外参数，通常通过 $_GET 传入
    public $params = [];

    //资源内容
    public $content = null;

    /**
     * 针对一些特殊的资源，例如 音频流|视频流 开启自定义的 customGetContent | customExport 方法
     * 是否开启自定义 io 方法
     * !! 有需要 自定义 io 方法的资源子类，覆盖这个属性
     */
    protected $customIO = false;



    /**
     * 构造 Resource 资源实例
     * !! 不能直接调用，必须通过 Resource::create 工厂方法
     * @param Array $opt 通过 Resource::parse 解析请求 URI 得到的 资源参数
     * @return void
     */
    protected function __construct($opt = [])
    {
        if (!Is::nemarr($opt)) return null;
        $uri = $opt["uri"] ?? null;
        $ext = $opt["ext"] ?? null;
        $type = $opt["type"] ?? null;
        $params = $opt["params"] ?? [];
        if (
            !Is::nemstr($uri) || !Is::nemstr($ext) || 
            !Is::nemstr($type) || !in_array($type, self::$sourceTypes)
        ) {
            return null;
        }

        //写入 资源参数
        $this->ext = $ext;
        $this->mime = Mime::getMime($ext);
        $this->sourceType = $type;
        $this->params = $params;

        if ($type!=="create") {
            //不是 create 手动创建的 资源来源
            if ($type==="remote") {
                if (!isset($opt["ouri"]) || !Is::nemstr($opt["ouri"])) return null;
                $uri = $opt["ouri"];
            }
            $real = $opt["real"] ?? null;
            if (!Is::nemstr($real) || self::exists($real)!==true) return null;
            $this->uri = $uri;
            $this->upi = $opt["upi"] ?? pathinfo($uri);
            $this->name = $this->upi["basename"];
            $this->real = $real;

            //读取资源内容
            $this->getContent();

        } else {
            //单独处理 手动创建类型的 资源
            $cnt = $opt["content"] ?? null;
            if (!Is::nemstr($cnt)) return null;
            $this->uri = $uri;
            $this->upi = $opt["upi"] ?? pathinfo($uri);
            $this->name = $this->upi["basename"];

            //直接写入 content
            $this->content = $cnt;
        }

        //资源创建完成后的 钩子函数
        $this->afterCreated();
    }

    /**
     * 当前资源创建完成后 执行
     * !! 子类可覆盖此方法
     * @return Resource $this
     */
    protected function afterCreated()
    {

        return $this;
    }

    /**
     * 不同资源来源类型的 资源内容读取方法 (读取|生成)
     * !! 子类不要覆盖
     * @return String $this->content
     */
    final protected function getContent()
    {
        //!! 针对 开启了 自定义 io 方法的 资源类型，直接调用 customExport 方法
        if ($this->customIO === true) return $this->customGetContent();

        $rtp = $this->sourceType;
        $m = "get".Str::camel($rtp, true)."Content";
        if (method_exists($this, $m)) {
            $this->$m();
        }
        return $this->content;
    }

    /**
     * 不同资源来源类型的 资源内容读取方法 具体实现
     * !! 如果需要，子类可以覆盖这些方法
     * @return void
     */
    //读取本地资源内容
    protected function getLocalContent() {
        $real = $this->real;
        if (!file_exists($real)) $this->content = null;
        //纯文本|二进制 类型文件 都可以读取
        $this->content = file_get_contents($real);
    }
    //读取 远程资源内容 Curl
    protected function getRemoteContent() {
        $isHttps = strpos($this->real, "https")!==false;
        if ($isHttps) {
            $this->content = Curl::get($this->real, "ssl");
        } else {
            $this->content = Curl::get($this->real);
        }
    }
    //读取 require 来源类型的资源内容，通过 require php 文件，生成文件内容
    protected function getRequireContent() {
        $params = $this->params;
        //将资源额外参数作为 变量定义 注入 php 文件中
        foreach ($params as $k => $v) {
            $$k = $v;
        }
        
        //开始通过 require php 文件生成资源内容
        //@ob_start();  //框架自动开启
        require($this->real); 
        $this->content = ob_get_contents(); 
        ob_clean();
    }
    //通过 build 构建的方式 生成资源内容
    protected function getBuildContent() {
        //TODO:
        //$builder = new Builder($this);
        //$builder->build();
    }

    /**
     * !! customIO == true 的特殊资源类型，可自定义 customGetContent 方法
     * @return Mixed $this->content
     */
    protected function customGetContent()
    {

        return $this->content;
    }

    /**
     * 资源输出流程
     * !! 子类不要覆盖这个流程，可以覆盖内部的钩子方法
     * @param Array $params 可以在输出资源时，额外指定参数，可通过定义 $params["return"] 来获取资源内容，而不是直接输出
     *  return === false        直接输出资源
     *  return === true         获取 资源的 content 内容
     *  return === "instance"   直接返回资源实例本身
     *  return === "任意属性名"   直接返回 $resource->任意属性名
     * @return void
     */
    final public function export($params = [])
    {
        if (!Is::nemarr($params)) $params = [];

        try {

            //!! 针对 开启了 自定义 io 方法的 资源类型，直接调用 customExport 方法
            if ($this->customIO === true) {
                return $this->customExport($params);
            }

            //是否返回资源内容，而不是直接输出
            $return = $params["return"] ?? false;
            if (!is_bool($return) && !Is::nemstr($return)) $return = false;
            if (Is::nemstr($return) && $return!=="instance" && !property_exists($this, $return)) $return = false;
            unset($params["return"]);

            //子类实现的 自定义处理方法
            $this->beforeExport($params);

            //TODO: 输出之前，如果需要，保存文件到本地
            //$this->saveRemoteToLocal();

            /**
             * 根据 return 参数 输出资源|返回资源内容
             */
            //return === true 返回资源内容，而不是直接输出
            if ($return === true) return $this->content;
            //return === "instance" 返回资源实例本身
            if ($return === "instance") return $this;
            //return === "资源实例的任意属性名" 返回资源实例此属性的 值
            if (Is::nemstr($return)) return $this->$return;

            //return === false 输出资源 方法
            $this->echoContent();
            
            exit;

        } catch (BaseException $e) {
            $e->handleException();
        }
    }

    /**
     * 在输出资源内容之前，对资源内容执行处理
     * !! 子类可覆盖此方法
     * @param Array $params 可传入额外的 资源处理参数
     * @return Resource $this
     */
    protected function beforeExport($params=[])
    {

        return $this;
    }

    /**
     * 资源输出的最后一步，echo
     * !! 子类可覆盖此方法
     * @param String $content 可单独指定最终输出的内容，不指定则使用 $this->content
     * @return Resource $this
     */
    protected function echoContent($content=null)
    {
        //输出资源
        if (Response::$isInsed !== true) {
            //响应实例还未创建
            throw new SrcException("响应实例还未创建", "resource/export");
        }

        //输出内容
        if (!is_string($content) || !Is::nemstr($content)) {
            $content = $this->content;
        }
    
        //输出响应头，根据 资源后缀名设置 Content-Type
        Mime::setHeaders($this->ext, $this->name);
        Response::$current->header->sent();
        
        //echo
        echo $content;

        return $this;
    }

    /**
     * !! customIO == true 的特殊资源类型，可自定义 customExport 方法
     * @param Array $params 可以在输出资源时，额外指定参数，与 export 方法参数一致
     * @return void
     */
    protected function customExport($params=[])
    {

        exit;
    }



    /**
     * 工具方法
     */

    /**
     * 合并额外的 params 到当前 params
     * 通常用于 beforeExport 方法中
     * @param Array $params 
     * @return $this
     */
    protected function extendParams($params=[])
    {
        if (!Is::nemarr($params)) return $this;
        //首先处理一下 params 将 ntf|yes|no|foo,bar 形式的 字符串 转换为对应的 值
        $params = static::fixParams($params);
        //合并
        $this->params = Arr::extend($this->params, $params);

        return $this;
    }



    /**
     * 静态工具方法
     */

    /**
     * 根据请求的 URI 获取文件 ext
     * foo/bar.js?version=3.0  => js
     * @return String | null
     */
    public static function getExtFromPath($path = "")
    {
        if (!Is::nemstr($path)) return null;
        if (Str::has($path, "?")) {
            $pstr = explode("?", $path)[0];
        } else {
            $pstr = $path;
        }
        $pathinfo = pathinfo($pstr);
        $ext = $pathinfo["extension"] ?? null;
        if (Is::nemstr($ext)) return strtolower($ext);
        return null;
    }

    /**
     * file_exists，支持远程文件
     * @return Boolean
     */
    public static function exists($file = "")
    {
        if (Is::remote($file)) {
            $hds = get_headers($file);
            return in_array("HTTP/1.1 200 OK", $hds);
        } else {
            return file_exists($file);
        }
    }

    /**
     * 解析远程文件 文件头 获取文件信息，ext/size/ctime/thumb/...
     * 通过 get_headers 获取的
     * @param String $url 文件 url
     * @return Array
     */
    public static function getRemoteFileInfo($url="")
    {
        if (!Is::nemstr($url)) return [];
        $fh = get_headers($url);
        //var_dump($fh);
        $hd = [];
        for ($i=0;$i<count($fh);$i++) {
            $fhi = trim($fh[$i]);
            if (strpos($fhi, ": ")!==false) {
                $fa = explode(": ", $fhi);
                if (count($fa)<2) {
                    $hd["Http"] = $fa[0];
                } else {
                    $k = array_shift($fa);
                    $v = implode(": ", $fa);
                    $hd[$k] = $v;
                }
            }
        }
        $fi = [];
        $fi["headers"] = $hd;
        $mime = $hd["Content-Type"];
        $fi["mime"] = $mime;
        $ext = Mime::getExt($mime);
        $fi["ext"] = $ext;
        $size = $hd["Content-Length"]*1;
        $fi["size"] = $size;
        $fi["sizestr"] = Num::fileSize($size);
        $fi["ctime"] = strtotime($hd["Last-Modified"]);
        $pcs = Mime::$processable;
        $ks = array_keys($pcs);
        foreach ($ks as $ki) {
            $fi["is".ucfirst($ki)] = in_array($ext, $pcs[$ki]);
        }
        if ($fi["isImage"]===true) {
            $fi["thumb"] = /*"/src"."/".*/str_replace("https://", "https/", str_replace("http://", "http/", $url))."?thumb=auto";
        }
        $name = array_pop(explode("/", $url));
        if (strpos($name, ".$ext")!==false) {
            $fi["basename"] = $name;
            $fi["name"] = str_replace(".$ext", "", $name);
        } else {
            $fi["name"] = $name;
            $fi["basename"] = "$name.$ext";
        }
        return $fi;
    }

    /**
     * 计算调用路径，  realPath 转换为 relative
     * @return String
     */
    /*public static function toUri($realPath = "")
    {
        $realPath = str_replace("/", DS, $realPath);
        $relative = Path::relative($realPath);
        if (is_null($relative)) return "";
        return $relative;
    }*/

    /**
     * 计算调用路径，realPath 转换为 /src/foo/bar...
     */
    /*public static function toSrcUrl($realpath = "")
    {
        $relative = self::toUri($realpath);
        if ($relative=="") return "";
        $rarr = explode(DS, $relative);
        $spc = explode("/", str_replace(",","/",ASSET_DIRS));
        //$spc = array_merge($spc,["app","cphp"]);
        $spc = array_merge($spc,["app","atto"]);
        $nrarr = array_diff($rarr, $spc);
        $src = implode("/", $nrarr);
        $src = "/src".($src=="" ? "" : "/".$src);
        return $src;
    }*/
}