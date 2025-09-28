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
use Spf\module\src\resource\Builder;
use Spf\module\src\resource\util\Paramer;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Path;
use Spf\util\Num;
use Spf\util\Curl;
use Spf\util\Conv;

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
     * 当前资源类型是否定义了 factory 工厂方法，如果是，则在实例化时，通过 工厂方法 创建资源实例，而不是 new 
     * !! 针对存在 资源自定义子类 的情况，如果设为 true，则必须同时定义 factory 工厂方法
     * !! 如果必须，子类可以覆盖此属性
     */
    //public static $hasFactory = false;

    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 子类应覆盖此属性，定义自己的 params 参数规则
     */
    public static $stdParams = [];

    /**
     * 根据 URI 请求参数，查找 并创建 Resource 资源实例
     * 资源管理类的 核心入口方法
     * @param Array|String $uri 字符串 或 数组
     * @param Array $params 创建资源时的 额外参数
     * @return Resource|null 资源实例，或 null
     */
    final public static function create($uri, $params=[])
    {
        //创建 URI 路径字符
        if (Is::indexed($uri)) $uri = implode("/", $uri);
        if (!Is::nemarr($params)) $params = [];

        //可能传入 content 资源内容 手动创建 临时资源
        $manual = false;
        if (isset($params["content"])) {
            $manual = true;
            $content = $params["content"];
            unset($params["content"]);
        }

        //开始创建资源实例
        try {

            //首先处理手动创建临时资源的情况
            if ($manual) return static::manual($content, $uri, $params);
            
            //开始创建资源实例
            //根据 URI 查询对应的资源信息
            $opts = ResourceSeeker::seek($uri);
            //没有匹配到任何资源，返回 null 不是错误，不报错
            if (!Is::nemarr($opts)) return null;
            //合并 传入的 params
            if (Is::nemarr($params)) $opts["params"] = Arr::extend($opts["params"], $params);

            //根据 opts 实例化参数，创建资源实例
            return static::instantiate($opts);

        } catch (BaseException $e) {
            $e->handleException();
        }
    }

    /**
     * 使用 conent 资源内容 创建临时资源实例
     * !! 必须指定此临时资源的 上下文参数，可以是 某个文件路径 或 某个资源实例
     * 用来为此临时资源提供一些上下文参数 例如：目录路径前缀|url 前缀 等
     * 如果 params 参数中不包含 ext 信息，还需要使用 上下文参数中的 ext 数据
     * @param Mixed $content 资源内容，通常为字符串数据
     * @param Resource|String $context 可传入 资源实例 或 资源路径
     * @param Array $params 此临时资源的 其他实例化参数
     * @return Resource|null 返回 资源实例
     */
    final public static function manual($content, $context=null, $params=[])
    {
        //必须传入 context 内容
        if (!Is::nemstr($context) && !$context instanceof Resource) {
            //没有上下文，报错
            throw new SrcException("创建临时资源缺少必要的上下文信息", "resource/parse");
        }

        //准备此临时资源的 实例化参数 opts
        $opts = array_merge([], ResourceSeeker::$stdResourceOpts);

        //准备上下文信息
        if (Is::nemstr($context)) {
            //传入了某个 资源路径，尝试查找此路径对应的 资源参数
            $opt = ResourceSeeker::seek($context);
            if (Is::nemarr($opt)) {
                //找到资源参数，去除某些不需要的 上下文参数
                $opt = array_filter($opt, function($v,$k) {
                    return !in_array($k, ["type", "params"]);
                },ARRAY_FILTER_USE_BOTH);
                $opts = Arr::extend($opts, $opt);
            } else {
                //未找到资源参数，尝试从 context 路径中解析必须的 参数
                $pi = pathinfo($context);
                $opts = Arr::extend($opts, [
                    "uri" => $context,
                    "upi" => $pi,
                    "ext" => $pi["extension"],
                    "real" => "",
                ]);
            }
        } else {
            //传入了某个资源实例
            $opts = Arr::extend($opts, [
                "uri" => $context->uri,
                "upi" => $context->upi,
                "ext" => $context->ext,
                "real" => $context->real,
            ]);
        }

        //params 
        if (!Is::nemarr($params)) $params = [];
        if (isset($params["ext"])) {
            //定义了 ext
            $ext = $params["ext"];
            unset($params["ext"]);
            if (Mime::support($ext)) $opts["ext"] = $ext;
        }
        //合并 $_GET
        $gets = Request::$current->gets->ctx();
        $params = Arr::extend($params, $gets);

        //合并到 opts
        $opts = Arr::extend($opts, [
            "type" => "create",
            "params" => $params,
            "content" => $content,
        ]);

        //根据 opts 实例化参数，创建资源实例
        return static::instantiate($opts);
    }

    /**
     * 根据 opts 参数，实例化资源类，并返回
     * @param Array $opts 资源类的实例化参数
     * @return Resource|null
     */
    final public static function instantiate($opts=[])
    {
        if (!Is::nemarr($opts)) {
            //缺少实例化参数，报错
            throw new SrcException("null,缺少资源实例化参数", "resource/instance");
        }

        //根据 资源 ext 获取存在的 Resource 子类
        $ext = $opts["ext"] ?? "";
        $cls = self::resCls($ext);
        if (empty($cls) || !class_exists($cls)) {
            //Resource 资源子类不存在
            throw new SrcException("$ext,不支持或不存在的资源类型", "resource/instance");
        }
        $clsn = Cls::name($cls);

        /**
         * 检查 是否存在对应的 工厂方法
         * \NS\resource\Plain 资源类对应的工厂方法为：PlainFactory
         */
        $factory = $clsn."Factory";
        if (method_exists($cls, $factory)) {
            //使用 工厂方法 创建资源实例
            $res = $cls::$factory($opts);
        } else {
            //使用 new
            $res = new $cls($opts);
        }
        if (!$res instanceof Resource) {
            //Resource 资源类实例化失败
            throw new SrcException("$clsn,不支持此资源类型", "resource/instance");
        }
        return $res;
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
        return ResourceSeeker::seekLocal($path, $findDir);
    }

    /**
     * 根据 资源后缀名 获取 预定义的 Resource 子类 类全称
     * @param String $ext 资源后缀名
     * @return String 找到的 类全称，没有对应的子类 直接返回 Resource 类
     */
    public static function resCls($ext)
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
     * 根据资源类全称 获取对应类型的 后缀名 NS\resource\FooBar  --> foo_bar
     * @param String $cls 资源类全称
     * @return String 资源后缀名 foo_bar 形式
     */
    public static function clsk($cls)
    {
        $clsn = Cls::name($cls);
        if (!Is::nemstr($clsn)) return null;
        return Str::snake($clsn, "_");
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

    //资源元数据 meta 主要针对本地文件，创建时间|修改时间|文件大小 等 信息
    public $meta = [];

    //针对某些资源需要 builder 资源构建类 
    public $builder = null;

    /**
     * 针对此资源实例的 处理中间件
     * 需要严格按照先后顺序 定义处理中间件
     * !! 子类必须自定义各自类型资源的 处理中间件顺序
     */
    public $middleware = [
        //资源实例 构建阶段 执行的中间件
        "create" => [
            //使用资源类的 stdParams 标准参数数组 填充 params
            "UpdateParams" => [],
            //获取 资源实例的 meta 数据
            "GetMeta" => [],
            //获取 资源的 content
            "GetContent" => [],
            /*
            "MiddlewareClassName" => [
                # 中间件的实例化参数
                ...
            ],

            # 可以以这样的方式 在同一资源处理阶段，多次调用同一个 middleware
            "MiddlewareClassName #不同的标记" => [
                不同的 实例化参数 ...
            ],

            # 可以增加 ? 标记的 条件判断 dsl 语句，作为此中间件的执行条件，只有满足条件，才会执行此中间件
            "MiddlewareClassName ?foo=bar... #可以叠加标记" => [], 

            # 还可以调用 当前资源内部定义的 stage 方法，像中间件一样处理资源数据，返回 Bool 值
            "CustomStage" => [
                指向当前资源内部的 stageCustomStage 方法，传入此数组作为参数
            ]
            ...
            */
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],
        ],
    ];



    /**
     * 构造 Resource 资源实例
     * !! 不能直接调用，必须通过 Resource::create 工厂方法
     * @param Array $opts 通过 Resource::parse 解析请求 URI 得到的 资源参数
     * @return void
     */
    protected function __construct($opts = [])
    {
        if (!Is::nemarr($opts)) return null;
        $uri = $opts["uri"] ?? null;
        $ext = $opts["ext"] ?? null;
        $type = $opts["type"] ?? null;
        $params = $opts["params"] ?? [];
        if (
            !Is::nemstr($uri) || !Is::nemstr($ext) || 
            !Is::nemstr($type) || !in_array($type, self::$sourceTypes)
        ) {
            return null;
        }

        //写入 资源参数
        $upi = $opts["upi"] ?? [];
        if (!Is::nemarr($upi)) $upi = pathinfo($uri);
        $this->uri = $uri;
        $this->upi = $upi;
        $this->ext = $ext;
        $this->mime = Mime::getMime($ext);
        $this->name = $upi["basename"];
        $this->real = $opts["real"] ?? "";
        $this->sourceType = $type;
        $this->params = $params;

        //手动调用 通用的 资源处理中间件（都是 Processor 类型的中间件，会在资源实例内部缓存）
        $pcs = [
            //处理资源路径相关操作
            "PathProcessor",
        ];
        foreach ($pcs as $pc) {
            ResourceMiddleware::manual($pc, $this);
        }

        //启动 create 阶段的资源中间件 处理序列  将 opts 参数 作为中间件处理参数 注入
        ResourceMiddleware::process($this, "create", $opts);

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

        //是否返回资源内容，而不是直接输出
        $return = $params["return"] ?? false;
        if (!is_bool($return) && !Is::nemstr($return)) $return = false;
        if (Is::nemstr($return) && $return!=="instance" && !property_exists($this, $return)) $return = false;
        unset($params["return"]);

        try {

            //执行 export 阶段的 资源中间件处理序列
            ResourceMiddleware::process($this, "export", $params);

            //子类实现的 自定义处理方法
            $this->beforeExport();

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
     * @return Resource $this
     */
    protected function beforeExport()
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
     * 工具方法
     * !! 如果不是必须的，子类不要覆盖
     */

    /**
     * 复制当前资源实例
     * @param Array $params 可以调整 params 参数
     * @return Resource 新的资源实例
     */
    public function clone($params=[])
    {
        //当前资源实例的 params
        if (!Is::nemarr($params)) $params = [];
        $params = Arr::extend($this->params, $params);

        $isCreated = $this->sourceType === "create";
        if ($isCreated === true) {
            //针对手动创建的 资源实例
            $params["content"] = $this->content;
        } else if (isset($params["content"])) {
            //其他类型资源
            unset($params["content"]);
        }

        //原始调用 uri
        $uri = $this->uri;
        if (!Is::nemstr($uri) && $isCreated !== true) return null;

        //创建新资源实例
        $res = Resource::create($uri, $params);
        if (!$res instanceof Resource) {
            //资源 clone 出错，报错，阻止继续操作
            throw new SrcException($this->name." 的克隆,未知", "resource/instance");
        }
        return $res;
    }

    /**
     * 设置当前资源的 输出 ext
     * @param String $ext
     * @return $this
     */
    public function setExt($ext)
    {
        if ($this->ext === $ext) return $this;
        if (!Is::nemstr($ext) || Mime::support($ext)!==true) return $this;
        //先备份 原始 ext
        if (!isset($this->originExt) || !Is::nemstr($this->originExt)) {
            $this->originExt = $this->ext;
        }
        //设置输出 ext
        $this->ext = $ext;
        $this->mime = Mime::getMime($ext);
        return $this;
    }

    /**
     * 恢复当前资源的 原始 ext
     * @return $this
     */
    public function restoreExt()
    {
        if (!isset($this->originExt) || !Is::nemstr($this->originExt)) return $this;
        if ($this->originExt === $this->ext) return $this;
        //恢复
        return $this->setExt($this->originExt);
    }

    /**
     * 获取当前资源的 名称 foo_bar 形式，通常是 不带后缀的文件名
     * !! 特殊类型的资源，可以覆盖此方法，实现自有的 资源名称获取方法
     * @return String|null
     */
    public function resName()
    {
        $stp = $this->sourceType;
        //资源的基础路径
        $base = $this->pather->basePath();
        if (!Is::nemstr($base)) return null;
        $pi = pathinfo($base);

        //build 类型资源 返回 文件夹名
        if ($stp === "build") {
            $rarr = explode(DS, trim(str_replace(["/","\\"], DS, $base), DS));
            $dirn = array_slice($rarr, -1)[0];
            return Str::snake($dirn, "_");
        }

        //其他资源统一返回文件名
        if (Is::nemstr($pi["filename"])) {
            $fn = $pi["filename"];
            //处理 foo.theme.json 或 foo.js.php 这种情况
            if (strpos($fn, ".") !== false) {
                $fn = explode(".", $fn)[0];
            }
            //返回
            return Str::snake($fn, "_");
        }

        return null;
    }



    /**
     * 针对本地资源 路径处理 相关方法
     */

    /**
     * 获取本地资源的 名称，不含后缀名
     * !! 子类可覆盖此方法，例如 ParsablePlain 类型资源的 名称，不一定是 文件名
     * @return String|null
     */
    public function getLocalResName()
    {
        if ($this->sourceType !== "local") return null;
        if (!Is::nemstr($this->real)) return null;
        //默认返回 资源文件的 文件名
        $pi = pathinfo($real);
        return $pi["filename"] ?? null;
    }

    /**
     * 针对本地资源，获取资源的 所在文件路径 或 路径下的 子文件|子文件夹 路径
     * !! 子类可覆盖此方法
     * @param String $subpath 可拼接指定的 子路径
     * @param Bool $exists 是否检查 路径是否存在，如果 true 且 路径不存在，则返回 null
     * @return String|null
     */
    public function getLocalResInnerPath($subpath="", $exists=false)
    {
        if ($this->sourceType !== "local") return null;
        if (!Is::nemstr($this->real)) return null;
        //当前资源所在 文件夹 路径
        $dir = dirname($this->real);
        //拼接 path
        if (Is::nemstr($subpath)) {
            $path = $dir.DS.str_replace(["/","\\"], DS, $subpath);
        } else {
            $path = $dir;
        }

        //如果不检查是否存在
        if ($exists !== true) return $path;

        return file_exists($path) ? $path : null;
    }

    /**
     * 针对本地资源，或此资源外部访问 url 的 前缀 urlpre
     * 即 通过 urlpre/[$this->name] 可以直接访问到此资源
     * 例如：
     * 有本地资源：             /data/ms/app/foo_app/assets/bar/jaz.js
     * 相对地址为：             src/foo_app/bar/jaz.js 
     * 最终生成的 url 前缀为：   https://domain/foo_app/src/bar
     * !! 子类可覆盖此方法，例如 ParsablePlain 类型资源 有不同的 url 访问规则
     * @return String|null
     */
    public function getLocalResUrlPrefix()
    {
        if ($this->sourceType !== "local") return null;
        if (!Is::nemstr($this->real)) return null;
        //文件 basename
        $basename = basename($this->real);
        //本地资源路径 转为 相对路径
        $rela = Path::rela($this->real);
        if (!Is::nemstr($rela)) return null;
        //相对路径 数组
        $relarr = explode("/", trim($rela,"/"));

        /**
         * !! 只有 Src::$current->config->resource["access"] 中定义的 路径 可以被 url 访问到
         */
        $access = [];
        if (Src::$isInsed === true) {
            $access = Src::$current->config->resource["access"] ?? [];
        }
        if (!Is::nemarr($access)) return null;
        $accessable = false;
        foreach ($access as $aci) {
            $acilen = strlen($aci);
            if (substr($rela, 0, $acilen) === $aci) {
                //去除 路径 前缀
                $rela = substr($rela, $acilen);
                $accessable = true;
                break;
            }
        }
        if ($accessable !== true) return null;

        //当前 url
        $uo = Url::current();

        //生成 urlpre
        $ua = [];
        $ua[] = $uo->domain;
        //app
        if (App::$isInsed === true) {
            $appk = App::$current::clsk();
            if ($appk !== "base_app") {
                $ua[] = $appk;
                //去除 rela 相对路径中的 appk
                if (strpos($rela, $appk."/") !== false) {
                    $rela = str_replace($appk."/", "", $rela);
                }
            }
        }
        //src
        $ua[] = "src";
        //去除 rela 相对路径中的 文件名 basename
        $rela = str_replace($basename, "", $rela);
        $ua[] = trim($rela, "/");

        return implode("/", $ua);
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