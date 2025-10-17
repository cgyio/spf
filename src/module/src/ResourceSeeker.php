<?php
/**
 * Resource 资源处理类 核心工具类 ResourceSeeker
 * 根据传入参数，定位资源位置，创建资源参数，然后创建资源实例
 */

namespace Spf\module\src;

use Spf\Request;
use Spf\App;
use Spf\module\Src;
use Spf\module\src\resource\Json;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Path;
use Spf\util\Num;
use Spf\util\Curl;
use Spf\util\Conv;

final class ResourceSeeker 
{
    /**
     * 标准的资源实例化参数格式
     */
    protected static $stdResourceOpts = [
        //请求资源时，传入的 原始 uri
        //"ouri" => $ouri,
        //URI 原始调用路径，通常是传入 Src 模块 default 方法的 参数字符串 foo/bar/jaz.ext
        "uri" => "",
        //URI 的 pathinfo
        "upi" => [],
        //要输出的 文件后缀名，通常通过 URI 或 real 路径解析得到
        "ext" => "",
        //解析 URI 得到的 实际资源路径，可以是：远程 url，本地 php 文件，本地文件夹，本地文件
        "real" => "",
        //sourceType 在 Resource::$sourceTypes 数组中定义的 资源类型
        "type" => "",
        //资源的输出参数，需要合并 $_GET
        "params" => [],
    ];

    /**
     * 定义某些后缀名 可以 export 的 其他后缀名
     * 例如：
     *      访问 foo/bar.css    可以指向：foo/bar.scss?export=css
     * 在 查找本地资源时，需要根据此处的定义，查找不同后缀名的文件是否存在
     */
    protected static $exportExts = [
        "vue" => ["js","css","vue"],
        "scss" => ["css", "scss"],
    ];



    /**
     * 核心工具方法 入口
     */

    /**
     * 解析 URI 入口，返回解析得到的 资源相关实例化参数
     * @param Array $args URI 或 手动传入的 参数数组
     * @return Array|null 解析得到的 资源实例化参数
     */
    public static function seek(...$args)
    {
        //根据传入的 参数 获取 合法的路径字符串
        $uri = self::seekPath(...$args);
        if (!Is::nemstr($uri)) return null;

        //解析 URI 获取资源的 sourceType 以及相关其他参数
        $opt = self::seekSourceType($uri);
        if (!Is::nemarr($opt) || !isset($opt["type"]) || !in_array($opt["type"], Resource::$sourceTypes)) return null;

        //准备要返回的 资源参数
        $opts = Arr::extend(self::$stdResourceOpts, [
            "ouri" => $uri,
            "uri" => $uri,
            "ext" => Mime::getEXt($uri),
        ], $opt);

        //处理 params
        //min
        if (strpos($uri, ".min.")!==false) {
            $opts["params"]["min"] = true;
        }

        return $opts;
    }



    /**
     * 静态工具
     */

    /**
     * 外部使用 $stdResourceOpts 填充 资源实例化参数
     * @param Array $params 外部传入的 参数
     * @return Array 填充后的 实例化参数
     */
    public static function fixOpts($params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        $params = Arr::extend(static::$stdResourceOpts, $params);
        return $params;
    }

    /**
     * 根据传入的 参数，解析得到 可用的 路径字符串 
     * 参数可以是：
     *  0   请求的 URI 数组
     *  1   直接手动传入 路径字符串 或 路径数组
     *  2   直接手动传入 完整的 url https://....
     * @param Array $args 传入的参数 数组
     * @return String|null 合法的路径字符串
     */
    public static function seekPath(...$args)
    {
        if (!Is::nemarr($args)) return null;

        //单独传入了 路径字符串 手动传入
        if (count($args)===1) {
            //先检查是否传入了 真实存在的 路径
            if (file_exists($args[0])) return $args[0];
            //再检查是否传入了 url
            if (Url::isUrl($args[0])) return $args[0];
            if (Url::isShortUrl($args[0])) return Url::fixShortUrl($args[0]);
        }

        //拼接路径
        $path = implode("/", $args);
        //统一路径分隔符 /
        $path = str_replace(["\\", DS], "/", $path);

        return $path;
    }

    /**
     * 根据传入的 资源路径 获取资源的 sourceType 以及其他相关资源参数
     * @param String $path
     * @return Array 包含资源参数的 数组
     */
    public static function seekSourceType($path)
    {
        if (!Is::nemstr($path)) return null;

        //先检查一次 是否传入了 实际存在的本地文件路径
        if (file_exists($path)) {
            return [
                "type" => "local",
                "ext" => Mime::getExt($path),
                "real" => $path,
            ];
        }

        //开始 判断 资源的来源类型 以及 其他资源参数
        $types = Resource::$sourceTypes;
        foreach ($types as $type) {
            //调用 self::isFooBarSource 方法，依次解析 URI
            $m = "is".Str::camel($type, true)."Source";
            if (!method_exists(self::class, $m)) continue;
            $info = self::$m($path);
            if ($info === false) continue;
            return $info;
        }

        //未能匹配 任何资源类型
        return null;
    }

    /**
     * 在框架允许直接访问的 资源路径下查找对应的 文件|文件夹
     * 如果请求的路径 包含 .min. 将自动去除
     * @param String $path 要查找的 文件|文件夹 路径
     * @param Bool $findDir 是否查找文件夹，默认 false 查找文件
     * @param Bool $all 是否返回所有存在的路径，默认 false
     * @return String|null 找到 文件|文件夹 则返回真实路径 DS，未找到则返回 null
     */
    public static function seekLocal($path, $findDir=false, $all=false)
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
        $path = trim(str_replace([DS, "\\"], "/", $path), "/");
        if ($hasmin) $nomin = str_replace(".min.",".",$path);

        /**
         * 默认 可访问的 本地资源 路径在 Src::$current->config->resource["access"] 参数中定义
         */
        $dirs = Src::$current->config->resource["access"] ?? ["src", "view", "spf/assets",];
        //在允许访问的 dirs 文件夹下，可能还有指定的 特定路径
        $subdir = null;
        //如果查询的是 文件而不是文件夹 则需要查询此文件对应的 Resource 资源类是否定义了 本地文件保存的 特定路径
        if ($findDir!==true) {
            //获取 path 中包含的 后缀名
            $ext = Mime::getExt($path);
            if (Is::nemstr($ext)) {
                $ext = strtolower($ext);
                if ($ext === "json") {
                    //json 文件可能是某个复合资源，需要单独处理，获取复合资源对应的 ext
                    $comExt = Json::getCompoundExtFromJsonPath($path);
                    if (Is::nemstr($comExt)) $ext = $comExt;
                }
                //获取当前路径 path 指向的 Resource 资源类全称
                $rescls = Resource::resCls($ext);
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

        //调用 Path::exists 方法，查找第一个存在的 路径
        $ftp = $findDir===true ? Path::FIND_DIR : Path::FIND_FILE;
        return Path::exists($pathes, $all, $ftp);
    }



    /**
     * 解析 传入的资源路径 path 判断是否为某个来源类型资源的 系列解析方法 isFooBarSource
     * @param String $path 字符串 foo/bar/jaz.ext
     * @param Array $res 完整的 资源参数格式
     * @return Array|false 如果是此类型的资源，相关参数数组，否则 返回 false
     */
    //判断是否 本地存在的文件资源 foo/bar.jpg  或  foo/bar.custom --> foo/bar.custom.json
    protected static function isLocalSource($path)
    {
        //在允许访问的 路径下 查找此文件
        $fp = self::seekLocal($path);

        //找到本地文件，直接返回
        if (Is::nemstr($fp)) {
            return [
                "type" => "local",
                "ext" => Mime::getExt($fp),
                "real" => $fp,
            ];
        }

        /**
         * 处理 exportExts 中的定义
         * 例如：请求资源 foo/bar.css  则需要查找 foo/bar.scss?export=css
         */
        $pext = pathinfo($path)["extension"];
        $parr = explode(".$pext", $path);
        if (Is::nemstr($pext)) {
            $pext = strtolower($pext);
            foreach (static::$exportExts as $eext => $eexts) {
                if (!in_array($pext, $eexts)) continue;
                $npath = implode(".$eext", $parr);
                $fp = self::seekLocal($npath);

                //找到 对应的本地文件，要修改 params 参数
                if (Is::nemstr($fp)) {
                    return [
                        "uri" => $npath,
                        "type" => "local",
                        "ext" => Mime::getExt($fp),
                        "real" => $fp,
                        "params" => [
                            "export" => $pext
                        ],
                    ];
                }
            }
        }

        /**
         * 处理 *.*.json 形式的 本地资源，有些资源可能其主文件是 json 格式
         * 例如：请求资源 foo/bar.custom  则需要查找 foo/bar.custom.json 文件
         */
        //尝试增加 .json 后缀
        $jsf = $path.".json";
        $fp = self::seekLocal($jsf);
        //找到 json 文件，直接返回，将通过 JsonFactory 工厂方法 转发到 对应的 资源类
        if (Is::nemstr($fp)) {
            return [
                "uri" => $jsf,
                "type" => "local",
                "ext" => Mime::getExt($fp),
                "real" => $fp,
            ];
        }

        //未找到本地资源
        return false;
    }
    //判断是否 远程资源
    protected static function isRemoteSource($path)
    {
        $ouri = trim($path, "/");

        //直接传入了完整 url
        if (strpos($ouri, "://")!==false) {
            //检查远程文件是否存在
            if (Resource::exists($ouri)!==true) return false;
            return [
                "type" => "remote",
                "ext" => Mime::getExt($ouri),    //$fi["ext"],
                "real" => $ouri
            ];
        }

        //传入了 http(s)/host.com/foo/bar 形式 以 http|https 开头
        $uarr = explode("/", $ouri);
        if (!in_array(strtolower($uarr[0]), ["http","https"])) return false;
        //需要 构建远程资源 url
        $uarr[0] = $uarr[0].":/";
        $real = implode("/", $uarr);
        //检查远程文件是否存在
        if (Resource::exists($real)!==true) return false;

        //这是 远程资源
        return [
            "type" => "remote",
            "ext" => Mime::getExt($real),    //$fi["ext"],
            "real" => $real
        ];
    }
    //判断是否 通过 require 本地 php 文件生成资源，例如：请求 foo/bar.js 真实存在文件 foo/bar.js.php 
    protected static function isRequireSource($path)
    {
        //php 文件后缀，在 EXT_CLASS 常量中定义
        $ext = defined("EXT_CLASS") ? EXT_CLASS : ".php";
        $rfp = $path.$ext;
        //查找是否存在 php 文件
        $rfp = self::seekLocal($rfp);
        if (!Is::nemstr($rfp)) return false;

        //找到此 php 文件，表示 这是 require 类型资源
        return [
            "type" => "require",
            "ext" => Mime::getExt($path),
            "real" => $rfp
        ];
    }
    //判断是否 通过 build 构建的方式，生成文本资源，例如：请求 foo/bar.js 真实存在文件夹 foo/bar.js
    protected static function isBuildSource($path)
    {
        //检查是否存在 文件夹
        $dir = self::seekLocal($path, true);
        if (!Is::nemstr($dir)) return false;

        //找到对应文件夹，表示这是 build 类型资源
        return [
            "type" => "build",
            "ext" => Mime::getExt($path),
            "real" => $dir
        ];
    }
}