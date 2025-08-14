<?php
/**
 * 框架核心类
 * 框架 请求处理类
 * 处理传入的 Url 请求
 * 
 * 框架请求 Url 形式： 
 *      0   直接访问 method 形式    
 *          https://host/ 应用名 foo_app / 模块名 bar_mod / 方法类型 api / 方法名 jaz_mth / 参数...
 *              /foo_app/bar_mod/api/jaz/1/2                访问：  NS\module\foo_app\BarMod::jazApi(1,2)   应用模块
 *                                                                 或者 Spf\module\BarMod::jazApi(1,2)      全局模块
 *          直接访问应用类中的方法，则不需要模块名
 *              /foo_app/api/bar/1/2/3                      访问：  NS\app\FooApp::barApi(1,2,3)
 *          应用名为 index 时，可省略
 *              /view/foo/1/2/3                             访问：  NS\app\Index::fooView(1,2,3)
 *          当访问 应用|模块 的默认方法时，可省略 方法类型 和 方法名
 *              /foo_app/1/2/3                              访问：  NS\app\FooApp::default(1,2,3)
 *              /foo_app/bar_mod/1/2                        访问：  NS\module\foo_app\BarMod::default(1,2)  应用模块
 *                                                                 或者 Spf\module\BarMod::default(1,2)     全局模块
 *          当访问 index 应用的 default 方法时，应用名 方法名都可以省略
 *              /1/2/3                                      访问：  NS\app\Index::default(1,2,3)
 *              /bar_mod/1/2                                访问：  NS\module\index\BarMod::default(1,2)    应用模块
 *                                                                 或者 Spf\module\BarMod::default(1,2)     全局模块
 * 
 *      1   路由正则 形式
 *          https://host/ 应用名 foo_app / 匹配某个 路由正则 ...
 *          应用名为 index 时，可省略
 * 
 * !! 如果省略了应用名，且当前整站中不存在 index 应用，则请求的应用指向 BaseApp ，通常用于访问：全局路由 | 通用响应方法 等
 *          
 */

namespace Spf;

use Spf\exception\CoreException;
use Spf\exception\AppException;
use Spf\util\Url;
use Spf\util\RequestHeader;
use Spf\util\Ajax;
use Spf\util\Gets;
use Spf\util\Posts;
use Spf\util\Inputs;
use Spf\util\Files;
use Spf\util\Server;
use Spf\util\Operation;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Cls;
use Spf\util\Path;

class Request extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;



    /**
     * Request 请求的 参数
     */
    //Url 实例
    public $url = null;
    //请求头
    public $header = null;
    //解析 request 后得到的 response.headers 初始值
    public $responseHeaders = [];
    //Ajax 和 跨域请求处理 对象
    public $ajax = null;
    
    //请求参数
    public $method = "";
    public $time = 0;
    public $https = false;

    //传入参数 对象
    public $gets = null;
    public $posts = null;
    public $files = null;
    public $inputs = null;

    /**
     * Url 解析得到的 响应 应用类|方法|操作信息 参数
     */
    //请求的 App 应用类全称
    protected $app = "";
    //请求的 实际操作信息数组，
    protected $oprc = [];
    //可作为参数 传递给 实际操作方法的 URI 数组
    protected $uris = [];

    //标记
    //请求的 应用 已匹配到
    public $appMatched = false;
    //请求的实际操作方法 已匹配到
    public $oprcMatched = false;



    /**
     * 此 Request 请求类自有的 init 方法，执行以下操作：
     *  0   创建当前请求的 Url 实例，获取相应的 请求参数
     *  1   创建请求头 RequestHeader 实例，获取相应的 请求参数
     *  2   创建 Ajax 请求处理实例，获取相应的 参数
     *  3   创建所有传入的 数据对象实例 $_GET | $_POST | $_FILES | php://input
     *  4   解析当前请求的 Url 得到 目标 App 应用类
     * !! Core 子类必须实现的
     * @return $this
     */
    final public function initialize()
    {
        // 0 创建当前请求的 Url 实例
        $this->url = Url::current();
        $this->https = $this->url->protocol === "https";

        // 1 创建请求头 RequestHeader 实例
        $this->header = new RequestHeader();
        $this->method = Server::get("Request-Method");
        $this->time = Server::get("Request-Time");

        // 2 创建 Ajax 请求处理实例，获取相应的 参数
        $this->ajax = new Ajax();
        if (!empty($this->ajax->responseHeaders)) {
            $this->responseHeaders = Arr::extend($this->responseHeaders, $this->ajax->responseHeaders);
        }

        // 3 创建所有传入的 数据对象实例 $_GET | $_POST | $_FILE | php://input
        $this->gets = new Gets($_GET);
        $this->posts = new Posts($_POST);
        $this->inputs = new Inputs();
        $this->files = new Files();

        // 4 解析当前请求的 Url 得到 目标 App 应用类
        $this->getApp();
        
        return $this;
    }

    /**
     * 解析当前请求的 Url，取得请求的 App 应用类
     * @return String 当前请求的 应用类全称
     */
    public function getApp()
    {
        //已缓存的 应用类全称
        if (Is::nemstr($this->app) && class_exists($this->app)) return $this->app;
        
        //解析 Url
        $url = Url::current();
        $path = $url->path;

        //判断 $path[0] 是否 App 应用名
        if (Is::nemarr($path)) {
            $appk = $path[0];
            if (false !== ($appcls = Cls::find("app/$appk"))) {
                //$path[0] 是某个存在的 App 应用名
                $this->app = $appcls;
                //缓存剩余的 URI 数组，用于继续分析并查找 响应方法
                $this->uris = array_slice($path, 1);
                //返回
                return $this->app;
            }
        }
        
        //其他情况，都指向 index 应用 或 base_app 默认应用
        $this->uris = $path;
        //检查是否存在 index 应用
        if (false !== ($appcls = Cls::find("app/index"))) {
            //存在 index 应用
            $app = $appcls;
        } else {
            //不存在 index 则指向 base_app 一定存在此类
            $app = Cls::find("app/base_app", "Spf\\");
        }

        if (!Is::nemstr($app) || !class_exists($app)) {
            throw new AppException($url->uri, "route/missapp");
        }

        $this->app = $app;

        //添加标记
        $this->appMatched = true;

        return $app;
    }

    /**
     * 当解析得到的 App 应用类实例化后，继续解析剩余的 URI 序列，获取最终的 响应方法参数
     * !! 此操作必须在 应用实例化完成后 执行
     * @return Array 标准 操作信息数组
     */
    public function getOprc()
    {
        //如果已有 缓存的 响应方法 信息数据
        if (Is::nemarr($this->oprc)) return $this->oprc;

        //应用必须已经实例化
        if (App::$isInsed !== true) {
            throw new CoreException("解析请求的操作时，应用实例还未创建", "initialize/init");
        }
        $app = App::$current;
        //剩余的 URI 路径 []
        $uris = $this->uris;

        //匹配路由
        $match = $app->operation->match($uris);
        //未匹配到任何 操作
        if (!Is::nemarr($match)) {
            throw new AppException(Url::current()->uri, "route/missoprc");
        }

        //缓存匹配到的 操作数据 以及 操作参数
        $this->oprc = $match["oprc"];
        $this->uris = $match["uris"];
        
        //添加标记
        $this->oprcMatched = true;

        //返回 匹配到的的 操作信息
        return $this->oprc;
    }

    /**
     * 获取 $this->uris 
     * @return Array
     */
    public function getUris()
    {
        return $this->uris;
    }

    /**
     * 快捷访问 __get
     * !! 子类如果要覆盖，请在此基础上增加，即 必须在子类 __get 方法中调用 parent::__get()
     * @param String $key 要访问的 不存在的 属性
     * @return Mixed
     */
    public function __get($key)
    {
        /**
         * $this->request
         * 请求的 App 应用实例化，且 请求的 操作 已经匹配到
         * 返回这些信息
         */
        if ($key === "request") {
            $req = [
                "app" => $this->getApp(),
                "oprc" => $this->getOprc(),
                "args" => $this->getUris()
            ];
            return (object)$req;
        }

        /**
         * 最后 调用父类的 __get() 方法
         */
        return parent::__get($key);
    }



    

    /**
     * 获取当前请求的 来源信息
     * 为一些需要区分请求来源的场景，提供数据
     * !! Request::$current 必须已创建
     * @return Array | null
     */
    public static function audience()
    {
        if (Request::$isInsed !== true) return null;
        
        $aud = [
            "referer" => Server::referer(),
            "origin" => $_SERVER["HTTP_ORIGIN"] ?? "",
            "ip" => Server::ip(),
            "audience" => "",
            "protocol" => "",   //https or http
        ];
        $audience = "public";   //默认的 来源
        if (Is::nemstr($aud["referer"])) {
            $audience = $aud["referer"];
        } else {
            if (Is::nemstr($aud["origin"])) {
                $audience = $aud["origin"];
            }
        }
        if ($audience==="public") {
            $aud["protocol"] = "http";
        } else {
            $audurl = Url::mk($audience);
            $aud["protocol"] = $audurl->protocol;
            $audience = $audurl->host;
            //$aud["protocol"] = strpos(strtolower($audience), "https://")!==false ? "https" : "http";
            //$audience = explode("/", explode("://", $audience)[1])[0];
        }
        $aud["audience"] = $audience;

        return $aud;
    }



    /**
     * !! 已废弃
     * 使用 路由表 匹配当前请求的 URI 获取请求的 操作信息
     */
    public function __getOprc()
    {
        //如果已有 缓存的 响应方法 信息数据
        if (Is::nemarr($this->oprc)) return $this->oprc;

        //应用必须已经实例化
        if (App::$isInsed !== true) return null;
        $app = App::$current;
        //剩余的 URI 路径 []
        $uris = $this->uris;

        //首先尝试 匹配路由表
        $routes = $app->operation->routes();
        $uri = implode("/", $uris);
        //依次匹配路由
        foreach ($routes as $pattern => $oprc) {
            //使用 正则 匹配 $uri 字符串
            try {
                $mt = preg_match($pattern, $uri, $matches);
                //未匹配成功，继续下一个
                if ($mt !== 1) continue;

                //匹配成功，将 匹配结果 作为 响应方法参数 返回
                $mcs = array_slice($matches, 1);
                $msc = array_map(function($mci) {
                    return trim($mci, "/");
                }, $mcs);
                $mcstr = implode("/", $mcs);
                $mcs = explode("/", $mcstr);

                //检查是否还有剩余的 uri 路径
                $uarr = [];
                if (strpos($uri, $mcstr)!==false) {
                    $uriarr = explode($mcstr, $uri);
                    if (count($uriarr)>1 && $uriarr[1]!="") {
                        $uarr = explode("/", trim($uriarr[1], "/"));
                    }
                }
                //缓存 匹配结果数组 和 剩余的 URI 路径，合并后得到 作为响应方法参数 的数组
                $this->uris = array_merge($mcs, $uarr);

                //缓存匹配的 结果
                $this->oprc = $oprc;

                //返回匹配结果
                return $oprc;

            } catch (\Exception $e) {
                //正则匹配出错 跳过
                continue;
            }
        }

        //未匹配到

        //然后尝试在 操作列表中查找
        if (empty($uris)) {
            //空 URI 指向 当前应用的 default 方法
            //TODO：实现 应用类中的 通用的 default 方法

        }

        //获取 检查项
        $mthk = $uris[0];
        if (in_array($mthk, Operation::$types)) {
            //如果是 预定义的 特殊操作类型 api|view|src ... 
            $mthk = $uris[1] ?? null;
            if (!Is::nemstr($mthk)) {
                //未指定请求方法，使用 default 方法
                //TODO：

            }
            //生成 method 完整方法名 fooBarApi
            $mthn = Str::camel($mthk, false).Str::camel($uris[0], true);
            //查找 方法名
            $find = $app->operation->search(function($oprc) use ($mthn){
                return isset($oprc["method"]) && $oprc["method"]===$mthn;
            });
            if ($find !== false) {
                //找到方法
                //缓存剩余的 URI
                $this->uris = array_slice($uris, 2);
                //缓存 oprc
                $this->oprc = $find;
                return $find;
            }
        } else if (false !== ($mod = Module::has($mthk))) {
            //指向某个模块
            //$modn = $mod::clsn();
            //继续查找
            $mthk = $uris[1] ?? null;
            if (!Is::nemstr($mthk)) {
                //未指定 模块的 方法，指向 模块的 default 方法
                //TODO:

            } else if (in_array($mthk, Operation::$types)) {
                //如果是 预定义的 特殊操作类型 api|view|src ... 
                $mthk = $uris[2] ?? null;
                if (!Is::nemstr($mthk)) {
                    //未指定请求方法，使用 模块的 default 方法
                    //TODO：

                }
                //生成 模块内的 method 方法名
                $mthn = Str::camel($mthk, false).Str::camel($uris[1], true);
                //查找 方法名
                $find = $app->operation->search(function($oprc) use ($mod, $mthn){
                    return (
                        isset($oprc["class"]) && $oprc["class"]===$mod &&
                        isset($oprc["method"]) && $oprc["method"]===$mthn
                    );
                });
                if ($find !== false) {
                    //找到方法
                    //缓存剩余的 URI
                    $this->uris = array_slice($uris, 3);
                    //缓存 oprc
                    $this->oprc = $find;
                    return $find;
                }
            }
        }

        //未找到任何对应的 操作方法 直接使用当前应用的 default 方法
        //TODO:

    }

}