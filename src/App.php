<?php
/**
 * 框架核心类
 * 应用类基类，抽象类
 * 
 * 所有业务功能都应通过此类 来实现
 * 
 * 应用类在一次会话过程中，只能创建 1 个应用子类的实例
 */

namespace Spf;

use Spf\exception\BaseException;
use Spf\exception\CoreException;
use Spf\exception\AppException;
use Spf\util\Operation;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Url;
use Spf\util\Curl;
use Spf\util\Cache;

use Spf\traits\CoreInsGetter;
use Spf\traits\ExpandableResourceCollector;

abstract class App extends Core 
{
    //快速获取核心类
    use CoreInsGetter;
    //!! 自动收集 ExpandableResource 通用可扩展资源
    use ExpandableResourceCollector;

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
     * 应用的元数据
     * !! 实际应用类必须覆盖
     */
    //应用的说明信息
    public $intr = "";
    //应用的名称 类名 FooBar 形式
    public $name = "";

    //当前应用的 操作列表管理类实例
    public $operation = null;



    /**
     * 获取 App 应用类 对应的 config 配置类 类全称
     * !! 覆盖父类
     * @return String 类全称
     */
    protected function getConfigCls()
    {
        //当前应用的 类名 FooBar 形式
        $clsn = static::clsn();
        //当前应用的 路径名 foo_bar
        $clsk = static::clsk();
        //应用配置类 类名 AppFooBarConfig
        $cfgn = "App".$clsn."Config";

        //查找 参数配置类的 类全称
        $cfgcls = null;
        //在 框架默认的 应用路径下查找
        $cfgcls = Cls::find("app/$clsk/$cfgn");
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //默认路径下，没有此应用的 配置类，则使用 AppConfig 类，此类一定存在
            $cfgcls = Cls::find("config/AppConfig", "Spf\\");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //未找到配置类，报错
            throw new CoreException("未找到 $cfgn 配置类", "initialize/config");
        }
        return $cfgcls;
    }

    /**
     * 此 App 应用类自有的 init 方法，执行以下操作：
     *  0   自动收集此应用依赖的 ExpandableResource 通用可扩展资源
     *  1   生成(并缓存)此应用的 全部操作列表，同时生成 路由表
     *  2   实例化参数中的所有 启用的模块
     *  3   执行 此应用类 自定义的 初始化方法
     * !! Core 子类必须实现的，App 子类不要覆盖
     * @return $this
     */
    final public function initialize()
    {
        //!! 应用实例化时，Request 必须已创建
        if (Request::$isInsed!==true) {
            //报异常
            throw new CoreException("应用实例化时，Request 请求实例还未创建", "initialize/init");
        }

        //当前应用的 类名 路径形式 FooBar
        $appn = $this::clsn();

        //err msg
        $errmsg = [];

        // 0 自动收集此应用依赖的 ExpandableResource 通用可扩展资源
        $exresCollected = static::collectExres($this);
        if (!$exresCollected) $errmsg[] = "ExpandableResource 通用可扩展资源收集失败";

        // 1 生成(并缓存)此应用的 全部操作列表，同时生成 路由表
        $this->operation = new Operation();

        // 2 实例化参数中的所有 启用的模块
        $mods = $this->config->ctx["module"] ?? [];
        foreach ($mods as $modk => $modc) {
            //确认启用此模块
            if (!isset($modc["enable"]) || $modc["enable"]!==true) continue;
            //获取模块类全称，未获取到则 跳过
            $modcls = Module::has($modk);
            if ($modcls === false) continue;
            //实例化 模块
            Module::current($modc, $modcls);
        }

        // 3 执行 此应用类 自定义的 初始化方法
        $appInited = $this->initApp();
        if (!$appInited) $errmsg[] = "应用未能正确执行 initApp 方法";

        if (true !== ($exresCollected && $appInited)) {
            if (Is::nemidx($errmsg)) {
                $errmsg = "，原因：".implode(" 且 ", $errmsg);
            } else {
                $errmsg = "";
            }
            throw new CoreException("$appn 应用未能正确初始化$errmsg", "initialize/init");
        }

        // 4 当前应用正确启动后，执行路由匹配
        Request::$current->getOprc();

        return $this;
    }

    /**
     * 各 App 应用类 应实现自有的 初始化方法
     * !! 需要自定义初始化动作的 应用 必须覆盖这个方法
     * @return Bool
     */
    protected function initApp()
    {

        return true;
    }

    /**
     * 当前的 App 应用实例，执行匹配到的 响应操作，操作的主体类可能是 当前应用实例|某个模块的实例
     * !! 当前应用 响应 Request 请求的 核心入口方法，子类不要覆盖
     * !! 如果当前应用启用了 Uac 权限控制，会在 module\uac\middleware\in\AuthorityControl 中间件中执行 权限检查
     * !! 如果未通过 权限检查，则不会执行此方法，因此 此方法中不再执行权限检查
     * @param Bool $return 返回操作结果，而不是 setData 到 $response 实例，默认 false
     * @return Bool
     */
    final public function response($return=false)
    {
        try {

            //!! Request|Response 实例 都必须存在，响应方法 oprc 必须已匹配到
            if (
                Request::$isInsed !== true || Response::$isInsed !== true ||
                Request::$current->oprcMatched !== true
            ) {
                //报错
                throw new AppException("请求实例或响应实例还未创建，或者未能匹配到有效的响应方法", "app/response");
            }

            //匹配到的响应方法
            $oprc = Request::$current->getOprc();
            //方法指向的 类全称
            $oprcls = $oprc["class"] ?? null;
            //获取 响应方法的调用者 通常 应为 $oprcls::$current
            $caller = $this->operation->getCaller();
            if (!is_object($caller) && !(Is::nemstr($caller) && class_exists($caller))) {
                //获取 调用者失败
                throw new AppException("响应方法指向了一个不存在的位置", "app/response");
            }
            //标记这个 caller 是否 类 而不是 实例
            $callerIsClass = Is::nemstr($caller) && class_exists($caller);

            //调用响应方法

            //方法参数
            $args = Request::$current->getUris();
            if (!Is::nemarr($args)) $args = [];

            //方法名
            $m = $oprc["method"] ?? null;
            if ($m === "__closure__") {
                //针对 匿名函数 形式的 响应方法

                //操作标识
                $oprn = $oprc["oprn"];
                //所有匿名函数
                $clos = $this->operation->closures();
                //找到当前操作的 匿名函数
                $fc = $clos[$oprn] ?? null;
                if (!$fc instanceof \Closure) {
                    //不是有效的 匿名函数
                    throw new AppException("当前响应方法不是有效的 Closure", "app/response");
                }

                //绑定匿名函数中的 $this === $caller 允许在函数体没 访问 $caller 的所有属性和方法 private|protected|public
                if ($callerIsClass) {
                    //调用者是 类
                    $fc = \Closure::bind($fc, null, $caller);
                } else {
                    $fc = \Closure::bind($fc, $caller, get_class($caller));
                }

                //执行这个匿名函数
                $result = $fc(...$args);
            } else {
                //普通方法
                if (!Is::nemstr($m) || !method_exists($caller, $m)) {
                    //响应方法 不存在于 调用者实例中
                    throw new AppException("响应方法 $m 不在调用者 $oprcls 中", "app/response");
                }
                if ($callerIsClass) {
                    //调用者是 类
                    $result = $caller::$m(...$args);
                } else {
                    //执行方法
                    $result = $caller->$m(...$args);
                }
            }

            //!! 直接返回 操作的执行结果，通常用于 路由劫持
            if ($return===true) return $result;

            //将响应方法 返回的结果 存入 Response::$current->data
            $setres = Response::$current->setData($result);
            if ($setres !== true && Response::$current->status->isError()!==true) {
                //保存结果出错 且 响应状态码是 200 时，报错，如果响应状态码不是 200 则 输出时不会使用 responseData 不需要报错
                throw new AppException("响应结果保存到响应实例出错", "app/response");
            }

            //完成
            return true;

        } catch (BaseException $e) {
            //响应方法执行错误，终止响应
            $e->handleException(true);
        }

    }

    /**
     * 在应用层，封装 $app->operation->invoke 方法
     * !! 在任何 App|Module|Db|Model|Record|... 实例内部，都应通过 App::$current->invoke 执行其他操作调用
     * !! 相当于 应用层的 路由劫持器
     * !! 应用子类可以覆盖，添加自定义的 逻辑，但是最终都应通过 $this->operation->invoke 执行实际的 opr 操作
     * @param Array $args 参数原样传入 operation->invoke 要求与该方法一致
     * @return Mixed|null
     */
    public function invoke(...$args)
    {
        //应用子类可以添加 自定义的 逻辑
        //...

        //最后一定通过 operation->invoke 执行实际操作
        return $this->operation->invoke(...$args);
    }

    /**
     * ServiceApp 微服务应用 接口调用
     * !! 在任何 App|Module|Db|Model|Record|... 实例内部，都应通过 App::$current->invokeService 调用 ServiceApp 接口
     * @param String $service 微服务调用路径，一定是这种形式：  服务名/服务接口名/参数/参数...?query=str 
     * @param Array $post 要 post 到 ServiceApp 接口的 数据 
     * @param \Closure $callback 对 ServiceApp 接口返回的结果，做额外处理的 方法
     *      @param Mixed $result ServiceApp 接口返回的原始数据
     *      @return Mixed|null 额外处理后的 $result
     * @return Mixed|null ServiceApp 接口返回的数据，可能经过额外处理
     */
    public function invokeService($service, $post=[], $callback=null)
    {
        if (!Is::nemstr($service) || strpos($service,"/")===false) return null;
        $sva = explode("/", $service);
        //服务名，定义在 $app->config->context["service"][] 中的键名
        $svn = array_shift($sva);
        $svn = Str::snake($svn, "_");
        //service 参数
        $services = $this->config->ctx["service"];
        if (!isset($services[$svn]) || !Is::nemaso($services[$svn])) return null;
        //当前调用的 ServiceApp 参数
        $svc = $services[$svn];
        //service_svn
        $svnp = "service_".Str::snake($svc["name"], "_");
        //oprn 前缀
        $oprnpre = "[$svnp]";
        $prelen = strlen($oprnpre);
        //接口名
        $apin = array_shift($sva);
        $apin = Str::snake($apin, "_");
        $apin = $svnp."_".$apin;

        //到 $app->operation->defines() 中查找操作实际信息
        $defs = $this->operation->defines();
        $find = null;
        foreach ($defs as $oprn => $oprc) {
            if (substr($oprn, 0, $prelen)!==$oprnpre) continue;
            if ($oprc["name"]===$apin) {
                $find = Arr::copy($oprc);
                break;
            }
        }
        //未找到
        if (!Is::nemaso($find)) return null;

        //剩余的 url
        $url = !empty($sva) ? implode("/", $sva) : "";
        //补齐 找到的 操作信息中的 url
        if (Is::nemstr($url)) {
            $find["proxy"]["url"] = rtrim($find["proxy"]["url"])."/".$url; 
        }

        //开始调用
        $caller = $this->operation->getCaller($find);
        $method = $find["method"] ?? "serviceProxyer";
        $isStatic = $find["isStatic"] ?? false;
        if (!method_exists($caller, $method)) return null;
        //调用
        if ($isStatic) {
            $result = $caller::$method($find, $post);
        } else {
            $result = $caller->$method($find, $post);
        }

        //对结果执行 额外处理
        if ($callback instanceof \Closure) {
            $result = $callback($result);
        }

        return $result;
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
         * $this->module|mod        -->  Module::$modules
         * 访问 当前应用中 所有启用的 模块实例
         */
        if ($key === "module" || $key === "mod") {
            $mods = Module::all();
            if (!empty($mods)) return (object)$mods;
        }

        /**
         * $this->ModuleName        --> Module::$modules["module_name"]
         * 访问已经实例化的 模块实例，未实例化 则返回 null
         */
        if (Module::has($key)!==false) {
            return Module::all($key);
        }

        /**
         * $this->ModuleFooBar      --> Module::$modules["foo_bar"]
         * $this->mod_foo_bar       --> Module::$modules["foo_bar"]
         */
        if (substr($key, 0, 6) === "Module" || substr($key, 0, 4)==="mod_") {
            $kk = Str::snake($key, "_");
            $karr = explode("_", $kk);
            $modk = implode("_", array_slice($karr, 1));
            return Module::all($modk);
        }

        //调用 父类的魔术方法 parent::__get($key)
        return parent::__get($key);
    }



    /**
     * 静态方法
     */

    /**
     * 判断 $app 应用是否存在
     * @param String $app 应用名称 FooBar 或 foo_bar 形式
     * @return String|false 类全称，未找到 则返回 false
     */
    final public static function has($app)
    {
        if (!Is::nemstr($app)) return false;

        //先判断一次
        if (class_exists($app) && is_subclass_of($app, App::class)) return $app;

        //路径名形式 foo_bar
        $appk = Str::snake($app, "_");
        //类名形式 FooBar
        $appn = Str::camel($appk, true);
        //应用类文件必须存在
        $appcls = Cls::find("app/$appn");
        if (Is::nemstr($appcls) && class_exists($appcls)) return $appcls;

        return false;
    }

    /**
     * 根据当前的 App 应用实例化情况，为 传入的 本地路径|url 增加 appk 前缀
     * 例如：当前已实例化的应用 foo_app，则：
     *      本地路径
     *      bar.json                转换为：src/foo_app/bar.json
     *      src/lib/vue/@.js        转换为：src/foo_app/lib/vue/@.js
     *      view/pms/bar.css        转换为：view/foo_app/view/bar.css
     * !! 传入的 路径开始文件夹 必须在 Env::$current->config->dir 数组中定义的
     * 
     *      url
     *      https://host/src/icon/spf.js        转换为：https://host/foo_app/src/icon/spf.js
     **     //host/method/arg1/arg2             转换为：//host/foo_app/method/arg1/arg2
     *      /src/lib/vue/@/product.js           转换为：/foo_app/src/lib/vue/@/product.js
     * 
     * @param String $path 要处理的路径 如 src/theme/spf
     * @return String 处理后的 路径
     */
    public static function path($path)
    {
        //传入的 path
        if (!Is::nemstr($path)) return $path;

        //当前应用必须已经实例化，且 不能是 BaseApp
        if (App::$isInsed !== true) return $path;
        $appk = App::$current::clsk();
        if ($appk === "base_app") return $path;

        //传入 url 形式(以 https:// | // | / 开头的)，直接 使用 App::url(...) 方法
        if (Path::isUrl($path) === true) return static::url($path);

        //DS --> /
        $p = str_replace(DS, "/", $path);

        //路径数组
        $parr = explode("/", $p);
        //默认在 src 路径下，例如：传入 foo.js 相当于传入 src/foo.js
        if (count($parr)<=1) array_unshift($parr, "src");
        //支持的 路径开始文件夹
        $dirs = Env::$current->config->dir;
        //传入了不支持的 开始文件夹，直接返回
        if (!isset($dirs[$parr[0]])) return $path;

        //向路径中插入 appk
        if (count($parr)<=2) {
            array_splice($parr, 1, 0, $appk);
        } else {
            $oappk = $parr[1];
            if ($oappk === $appk || App::has($oappk)!==false) {
                //路径已经包含 应用信息，直接返回
                return implode("/", $parr);
            }
            array_splice($parr, 1, 0, $appk);
        }
        //返回处理后的 路径
        return implode("/", $parr);
    }

    /**
     * 根据当前的 App 应用实例化情况，为 传入的 url 增加 appk 前缀
     * 例如：当前已实例化的应用 foo_app，则：
     *      https://host/src/icon/spf.js        转换为：https://host/foo_app/src/icon/spf.js
     **     //host/method/arg1/arg2             转换为：//host/foo_app/method/arg1/arg2
     *      /src/lib/vue/@/product.js           转换为：/foo_app/src/lib/vue/@/product.js
     * 
     * @param String $url 要处理的路径 如 /src/theme/spf
     * @return String 处理后的 url
     */
    public static function url($url)
    {
        //传入的 path
        if (!Is::nemstr($url)) return $url;

        //当前应用必须已经实例化，且 不能是 BaseApp
        if (App::$isInsed !== true) return $url;
        $appk = App::$current::clsk();
        if ($appk === "base_app") return $url;

        //传入 url 形式 必须 以 https:// | // | / 开头的
        if (Path::isUrl($url) !== true) return $url;

        if (strpos($url, "://")!==false || substr($url, 0,2)==="//") {
            //以 https:// | // 开头的
            $uarr = explode("//", $url);
            $parr = explode("/", $uarr[1]);
            if ($uarr[1]==="" || count($parr)<2) return $url;
            $uarr[0] = $uarr[0]."//".$parr[0];
            $parr = array_slice($parr, 1);
        } else {
            //以 / 开头的
            $uarr = ["", substr($url,1)];
            $parr = explode("/", $uarr[1]);
            if ($uarr[1]==="" || count($parr)<1) return $url;
        }

        //判断传入的 url 是否已包含 appk
        $oappk = $parr[0];
        if ($oappk === $appk || App::has($oappk)) {
            //已包含 appk 信息，直接返回
            return $uarr[0]."/".implode("/", $parr);
        }

        //插入 appk
        array_splice($parr, 0, 0, $appk);
        //返回
        return $uarr[0]."/".implode("/", $parr);
    }

    /**
     * 返回当前 App 是否 base_app
     * @return String|true|null 还未实例化 返回 null   是 base_app 返回 true  否则返回 appk
     */
    public static function isBaseApp()
    {
        //如果 App 应用还未实例化，返回 null
        if (App::$isInsed !== true) return null;

        $appk = App::$current::clsk();
        if ($appk === "base_app") return true;

        return $appk;
    }



    /**
     * Spf 框架 App 应用的 通用接口方法
     */

    /**
     * ServiceApp 微服务的 接口代理方法
     * !! 系统中所有 对 ServiceApp 的调用，都应通过此方法
     * !! 如果不是必须的，子应用不要覆盖
     * @param Array $oprc 标准操作信息数组
     * @param Array $post 要提交到 ServiceApp 接口的数据
     * @return Mixed 接口返回的数据
     */
    public function serviceProxyer($oprc=[], $post=[])
    {
        if (!Operation::isStdOprc($oprc) || !isset($oprc["proxy"])) return null;
        $proxy = $oprc["proxy"];
        $url = $proxy["url"];
        $ssl = $proxy["ssl"];
        $login = $proxy["login"] ?? null;
        if (!Is::nemaso($post)) $post = [];

        //如果启用 uac
        $jwt = null;
        if ($oprc["auth"]===true) {
            //从 runtime 缓存中获取 此 url 对应的 jwt-token
            $uo = new Url($url);
            $domain = $uo->domain;
            $jwts = Cache::read("root/runtime/app/".$this::$clsk."/cache/token.php");
            if (!Is::nemarr($jwts) || !isset($jwts[$domain])) {
                //没有缓存，调用 login 接口
                //TODO：...

            } else {
                //使用缓存
                $jwt = $jwts[$domain];
            }
        }

        //Curl
        if ($oprc["auth"]===true) {
            $res = $ssl ? Curl::jwt($url, $jwt, $post, "ssl") : Curl::jwt($url, $jwt, $post);
        } else {
            $res = $ssl ? Curl::post($url, $post, "ssl") : Curl::post($url, $post);
        }
        if (empty($res) || !Is::json($res)) return null;
        $res = Conv::j2a($res);
        //接口返回数据根据 Spf 框架的 response\Exporter 的标准输出结构，一定包含在 data 中
        if (!Is::nemaso($res) || !isset($res["data"])) return null;

        //如果 jwt 错误
        if (isset($res["error"]) && $res["error"]===true && $res["data"]["errmsg"]==="not login") {
            //重新调用 login 接口，使用 当前用户的 账号密码 或 统一的账号密码
            //TODO:

        }

        //返回结果
        return $res["data"];

    }

    /**
     * api
     * @name service_apis
     * @title 获取服务接口列表
     * @auth false
     * 
     * @param Array $args url 参数
     * @return Array 此应用作为 ServiceApp 时，对外提供所有可用的 操作接口 列表
     *  [
     *      "name" => "appk 应用名 foo_bar 形式",
     *      "title" => "应用(微服务)标题，来自 $app->intr",
     *      "oprs" => [
     *          "标准操作标识 oprn" => [
     *              "oprn"  => "",
     *              "name"  => "接口名称 foo_bar 形式",
     *              "title" => "接口标题，中文",
     *              "url"   => "外部访问地址"
     *          ],
     *          ...
     *      ]
     *  ]
     */
    public function serviceApisApi(...$args)
    {
        //读取所有 定义的 operation 操作
        $defs = $this->operation->defines();
        $rtn = [
            "name" => $this::clsk(),
            "title" => $this->intr."服务",
            "oprs" => []
        ];

        /**
         * 此应用作为 ServiceApp 对外提供服务时，
         * !! 仅对外提供 两种操作接口：
         * !!       应用提供的接口          以 api/app_name: 为操作标识前缀
         * !!       数据模型提供的接口      以 api/model/ 为操作标识前缀
         */
        $appk = $this::clsk();
        $appkPre = "api/$appk:";
        $appkPrelen = strlen($appkPre);
        $bzPre = "api/model/";
        $bzPrelen = strlen($bzPre);
        foreach ($defs as $oprn => $oprc) {
            //跳过无效操作
            if (substr($oprn, 0, 4)!=="api/") continue;
            if (substr($oprn, 0, $appkPrelen)!==$appkPre && substr($oprn, 0, $bzPrelen)!==$bzPre) continue;

            //route 路由正则
            $rut = $oprc["route"] ?? null;
            //路由正则 必须包在 /.../ 之间
            if (!Is::nemstr($rut) || substr($rut, 0,1)!=="/" || substr($rut, -1)!=="/") continue;
            //根据路由正则，得到此接口的访问地址
            $rut = substr($rut, 1, -1);
            //去除最后的 (\\.*)
            $rut = str_replace("(\\.*)", "", $rut);
            //去除其他字符，最为最终的 访问 url
            $rut = str_replace(["\\","(",")","*","."],"", $rut);

            //收集
            $rtn["oprs"][$oprn] = [
                "oprn"  => $oprn,
                "name"  => $oprc["name"],
                "title" => $this->intr."服务>".$oprc["title"],
                "url"   => $rut,
            ];
        }

        return $rtn;
    }
}