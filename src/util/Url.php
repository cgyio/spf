<?php
/**
 * 框架 特殊工具类
 * 处理 url
 */

namespace Spf\util;

class Url extends SpecialUitl
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
     * 判断一个字符串是 有效的 url
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
}