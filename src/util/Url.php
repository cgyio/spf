<?php
/**
 * 框架 特殊工具类
 * 处理 url
 */

namespace Spf\util;

use Spf\App;
use Spf\Env;
use Spf\module\Src;

class Url extends SpecialUtil
{
    /**
     * 此工具 在启动参数中的 参数定义
     *  [
     *      "util" => [
     *          "util_name" => [
     *              # 如需开启某个 特殊工具，设为 true
     *              "enable" => true|false, 是否启用
     *              ... 其他参数
     *          ],
     *      ]
     *  ]
     * !! 覆盖父类静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = false;
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];
    
    //当前会话请求的 url 实例
    public static $current = null;

    //支持的 Url 请求形式
    public static $requestTypes = [
        "method", "oprn", "pattern",
    ];

    /**
     * URL参数
     */
    public $context = [
        "scheme" => "",
        "host" => "",
        "path" => "",
        "query" => "",
        "fragment" => "",
    ];
    public $protocol = "";
    public $host = "";
    public $domain = "";
    public $dir = "";
    public $basename = "";
    public $full = "";
    public $uri = "";
    public $path = [];
    public $query = [];

    /**
     * 构造
     * @param String $url 可以传入 url 构建实例，默认不指定，使用当前会话的 url
     * @return void
     */
    public function __construct($url=null)
    {
        if (!self::isUrl($url)) $url = self::getCurrentUrl();
        //解析
        $ud = parse_url($url);
        $this->context = array_merge($this->context, $ud);
        
        //path 路径数组
        $path = $ud["path"] ?? "/";
        $this->path = $path=="/" ? [] : explode("/", substr($path, 1));
        //path 路径中 所有字符串都必须是 foo_bar 形式
        /*$this->path = array_map(function($pi) {
            if (is_numeric($pi) || !Is::nemstr($pi)) return $pi;
            return Str::snake($pi, "_");
        }, $this->path);*/

        //query 参数数组
        $query = $ud["query"] ?? "";
        if ($query=="") {
            $this->query = [];
        } else {
            parse_str($query, $qs);
            $this->query = $qs;
        }

        //uri 字符串
        $uri = $path;
        if ($query!="") $uri = $uri."?".$query;
        $this->uri = $uri;

        //protocol
        $this->protocol = $ud["scheme"];
        //host
        $this->host = $ud["host"];
        //domain
        $this->domain = $ud["scheme"]."://".$ud["host"];
        //dir 当前指向的文件的 上一级路径
        $dir = implode("/", array_slice($this->path, 0, -1));
        $this->dir = $this->domain.(Is::nemstr($dir) ? "/$dir" : "");
        //当前 url 指向的 basename 文件名，含后缀
        $this->basename = array_slice($this->path, -1)[0];

        //full
        $this->full = $url;
    }



    /**
     * 静态方法
     */

    /**
     * 获取当前会话请求的 url
     * @return String 
     */
    public static function getCurrentUrl()
    {
        //当前 url 协议
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            //反向代理
            $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } else {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        }

        //域名|IP
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            //反向代理
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } else {
            $host = $_SERVER['HTTP_HOST'];
        }

        //URI
        $uri = $_SERVER["REQUEST_URI"];

        return $scheme."://".$host.$uri;
    }

    /**
     * 判断一个字符串是 有效的 完整的 url
     * @param String $url
     * @return Bool
     */
    public static function isUrl($url)
    {
        if (!Is::nemstr($url)) return false;
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * 创建当前请求的 url 实例
     * @return Url 实例
     */
    public static function current()
    {
        if (self::$current instanceof Url) return self::$current;
        self::$current = new self();
        return self::$current;
    }

    /**
     * 判断一个字符串是否 url 简写形式
     * 以 / | // | ./ | ../ 开头的 路径字符串 视为 简写的 url
     * @param String $url
     * @return Bool
     */
    public static function isShortUrl($url)
    {
        if (!Is::nemstr($url)) return false;
        $url = trim($url);
        if (
            substr($url, 0, 3) === "../" ||
            in_array(substr($url, 0, 2), ["//","./"]) ||
            substr($url, 0, 1) === "/"
        ) {
            return true;
        }
        return false;
    }

    

    /**
     * 根据输入构造新 url 返回新的 Url实例
     *      http://xxxx      返回自身
     *      /xxxx/xxxx?qs    返回 domain + /xxxx/xxxx
     *      ../../xxxx/xxxx?qs     返回 domain + path + /../../xxxx/xxxx
     *      query 通过 Arr::extend 方式合并
     * 
     * @param String $url 要构建新 url 实例的 url 片段
     * @param Url $cu url 实例，在此基础上构建，默认 null 表示在当前请求的 url 基础上构建
     * @return Url 新实例
     */
    public static function mk($url = "", $cu = null)
    {
        if (self::isUrl($url)) {
            //输入的 url 片段是完整的 url
            return new Url($url);
        }
        
        //在原有 url 基础上构建
        if (empty($cu) || !$cu instanceof Url) $cu = self::current();
        //输入的不是字符串
        if (!Is::nemstr($url)) return $cu;

        //$url 按 ? 分割
        $uarr = explode("?", $url);

        if (substr($url, 0,1)==="/") {
            //$url 是 /foo/bar... 形式
            $nurl = [];
            $nurl[] = $cu->domain;
            $nurl[] = substr($uarr[0], 1);
            $nurl = implode("/", $nurl);
        } else {
            //$url 是 foo/bar... 或 ../foo/bar...
            $nurl = [];
            $nurl[] = $cu->domain;
            $nurl = array_merge($nurl, $cu->path);
            $nurl[] = $uarr[0];
            $nurl = implode("/", $nurl);
            $nurl = Path::up($nurl, "/");
            if (!Is::nemstr($nurl)) return $cu;
        }

        if (count($uarr)>1) {
            //$url 带参数，与原参数合并
            parse_str($uarr[1], $uq);
            $oq = array_merge([], $cu->query);
            $uq = Arr::extend($oq, $uq);
        } else {
            //$url 不带参数，使用原参数
            $uq = $cu->query;
        }

        $nurl = $nurl.(Is::nemarr($uq) ? "?".http_build_query($uq) : "");

        return new Url($nurl);
    }

    /**
     * 将 可直接通过 url 直接访问的 本地 文件|文件夹 路径 转为 实际的 Url 地址
     * !! 需要启用 Src 模块，且 Src 模块已经实例化
     * !! 允许直接通过 url 访问的 本地资源的路径 在 Src::$current->config->resource["access"] 中定义
     * 
     * 例如：
     * /data/ms/assets/foo.js                   -->  Url::mk('/src/foo.js')
     * /data/ms/app/foo_app/assets/bar.css      -->  Url::mk('/foo_app/src/bar.css')
     * /data/vendor/cgyio/spf/src/assets/lib    -->  Url::mk('/src/lib')
     * 
     * @param String $path 本地 文件|文件夹 路径
     * @param Bool $withDomain 是否添加当前 url 的 domain，默认 false
     * @return String|null 返回 资源 url，如果 withDomain===true 则返回完整 url，否则返回 / 开头的 url
     */
    public static function src($path, $withDomain=false)
    {
        if (!Is::nemstr($path)) return null;
        //确保路径分隔符为 DS
        $path = str_replace(["\\","/"], DS, $path);
        //去除 path 可能存在的 ../.. 
        $path = Path::fix($path);

        //依赖 已经实例化的 Src 模块
        if (Src::$isInsed!==true) return null;
        //获取允许的访问的 路径数组
        $accs = Src::$current->config->resource["access"] ?? [];
        if (!Is::nemarr($accs)) return null;

        //当前本地路径是在 某个 app 路径下
        $appdir = "app";
        if (Env::$isInsed === true) {
            $dirs = Env::$current->dir;
            if (isset($dirs["app"])) $appdir = $dirs["app"];
            $appdir = str_replace(["\\","/"], DS, $appdir);
            $appdir = ltrim($appdir, DS);
        }
        $inapp = false;
        if (strpos($path, DS.$appdir)!==false) {
            $inapp = true;
            $parr = explode(DS.$appdir, $path);
            if (!isset($parr[1]) || !Is::nemstr($parr[1])) return null;
            $parr = explode(DS, $parr[1]);
            if (!isset($parr[1]) || !Is::nemstr($parr[1])) return null;
            $appk = $parr[1];
        }

        //先执行 Path::rela 将物理路径 转为 可通过 Path::find 解析的相对路径
        $path = Path::rela($path);

        //判断路径是否可以被直接访问
        $accessable = false;
        foreach ($accs as $apre) {
            $alen = strlen($apre);
            if (substr($path, 0, $alen)===$apre) {
                $accessable = true;
                $path = substr($path, $alen);
                break;
            }
        }

        //不可访问
        if (!$accessable) return null;

        //生成 url
        $u = [];
        if ($inapp) $u[] = $appk;
        $u[] = "src";
        if ($inapp) {
            $path = str_replace($appk, "", $path);
            $path = preg_replace("/\/+/", "/", $path);
            $u[] = trim($path, "/");
        } else {
            $u[] = trim($path, "/");
        }
        $u = "/".implode("/", $u);

        if (!$withDomain) return $u;

        //调用当前 url 实例
        $url = Url::current();
        return $url->domain.$u;

    }

    /**
     * 将传入的 shortUrl 补全为完整的 url
     * @param String $url 必须是 以 / | // | ./ | ../ 开头的字符串
     * @return String 完整的 url https://...
     */
    public static function fixShortUrl($url)
    {
        if (!Is::nemstr($url)) return $url;
        if (!self::isShortUrl($url)) return $url;
        $url = trim($url);

        //当前 url
        $uo = self::current();
        $protocol = $uo->protocol;
        $domain = $uo->domain;
        $dir = $uo->dir;

        if (substr($url, 0, 3) === "../") {
            return Path::fix($dir."/".$url);
        }

        if (substr($url, 0, 2) === "//") return $protocol.":".$url;

        if (substr($url, 0, 2) === "./") $url = substr($url, 1);
        return $domain.$url;
    }
}