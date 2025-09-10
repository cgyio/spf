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

abstract class App extends Core 
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
     *  0   生成(并缓存)此应用的 全部操作列表，同时生成 路由表
     *  1   实例化参数中的所有 启用的模块
     *  2   执行 此应用类 自定义的 初始化方法
     * !! Core 子类必须实现的，App 子类不要覆盖
     * @return $this
     */
    final public function initialize()
    {
        //当前应用的 类名 路径形式 FooBar
        $appn = $this::clsn();

        // 0 生成(并缓存)此应用的 全部操作列表，同时生成 路由表
        $this->operation = new Operation();

        // 1 实例化参数中的所有 启用的模块
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

        // 2 执行 此应用类 自定义的 初始化方法
        $appInited = $this->initApp();
        if (true !== $appInited) {
            throw new CoreException("未能正确初始化应用 $appn", "initialize/init");
        }

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
     * @return Bool
     */
    final public function response()
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
            $oprcls = Cls::find($oprcls);
            if (!Is::nemstr($oprcls) || !class_exists($oprcls) || !is_subclass_of($oprcls, Core::class)) {
                //操作信息指向的 类 有误
                throw new AppException("响应方法指向了一个不存在的类 $oprcls", "app/response");
            }
            //操作指向的 类名
            $oprclsn = $oprcls::clsn();

            //获取 响应方法的调用者 应为 $oprcls::$current
            if (!isset($oprcls::$current) || !($oprcls::$current instanceof $oprcls)) {
                //如果操作指向的 核心类 还未实例化
                throw new AppException("响应方法指向了一个不存在的模块或应用 $oprclsn", "app/response");
            }
            $caller = $oprcls::$current;

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
                $fc = \Closure::bind($fc, $caller, get_class($caller));

                //执行这个匿名函数
                $result = $fc(...$args);
            } else {
                //普通方法
                if (!Is::nemstr($m) || !method_exists($caller, $m)) {
                    //响应方法 不存在于 调用者实例中
                    throw new AppException("响应方法 $m 不在调用者 $oprclsn 中", "app/response");
                }
    
                //执行方法
                $result = $caller->$m(...$args);
            }

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
}