<?php
/**
 * 框架核心类
 * 模块类基类，抽象类
 * 
 * 模块独立于应用，每个应用都可以选择启用某些模块，为应用添加相应的功能
 * 可以全局启用一些模块，这样每个应用都会同时拥有这些模块的功能
 * 
 * 模块类可以同时实例化多个 模块子类（每个子类都是单例模式）
 */

namespace Spf;

use Spf\exception\CoreException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

abstract class Module extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类，具体模块子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = true;

    /**
     * 当前会话 启用的 所有模块
     */
    public static $modules = [
        /*
        "模块类名 foo_bar" => 模块类单例 modcls::$current  未实例化时为 null,
        ...
        */
    ];

    /**
     * 模块的元数据
     * !! 实际模块类必须覆盖
     */
    //模块的说明信息
    public $intr = "";
    //模块的名称 类名 FooBar 形式
    public $name = "";

    

    /**
     * 获取模块类 对应的 config 配置类 类全称
     * !! 覆盖父类
     * @return String|null 类全称
     */
    protected function getConfigCls()
    {
        //当前模块的 类名 FooBar 形式
        $clsn = static::clsn();
        //当前模块的 路径名 foo_bar
        $clsk = static::clsk();
        //模块配置类 类名 ModuleFooBarConfig
        $cfgn = "Module".$clsn."Config";

        //查找 参数配置类的 类全称
        $cfgcls = null;
        if (App::$isInsed === true) {
            //应用已经创建，则应在 应用路径下 查找对应的 模块配置类
            //应用名 转为 foo_bar 形式
            $appk = App::$current::clsk();
            //在 应用路径下查找 当前模块的 配置类
            $cfgcls = Cls::find("module/$appk/$clsk/$cfgn");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //在 框架默认的路径下查找
            $cfgcls = Cls::find("module/$clsk/$cfgn", "Spf\\");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //默认路径下，也没有此模块的 配置类，则使用 ModuleConfig 类，此类一定存在
            $cfgcls = Cls::find("config/ModuleConfig", "Spf\\");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //未找到配置类，报错
            throw new CoreException("未找到 $cfgn 配置类", "initialize/config");
        }
        return $cfgcls;
    }
    
    /**
     * 此模块类自有的 init 方法，执行以下操作：
     *  0   为应用实例增加 新的响应方法，将这些新的响应方法 添加到 App::$current->operation->context[] 并更新 路由表
     *  1   为应用实例增加 新的中间件，这些新的中间件 
     *  2   执行模块自定义的 init 方法
     *  3   将当前的 模块实例，缓存到 Module::$modules[module_name]
     * !! Core 子类必须实现的，Module 子类不要覆盖
     * @return $this
     */
    final public function initialize()
    {
        //模块初始化时，App 应用必须已经实例化
        if (App::$isInsed !== true) {
            throw new CoreException("模块初始化时，应用实例还未创建", "initialize/init");
        }

        //模块类名 FooBar
        $modn = $this::clsn();
        //模块类名 路径形式 foo_bar
        $modk = $this::clsk();

        //确认 只能在开发环境下启用的模块，不会在生产环境中被启用
        if (Env::$current->dev !== true && $this->config->dev === true) {
            throw new CoreException("模块 $modn 不能在当前环境下启用", "initialize/init");
        }

        // 0 为应用实例增加 新的响应方法，修改 操作列表 | 路由表
        $oprsInjected = $this->injectOprs();

        // 1 为应用实例增加 新的中间件
        $midsInjected = $this->injectMiddleware();

        // 2 执行模块自定义的 init 方法
        $modInited = $this->initModule();

        if (true !== ($oprsInjected && $midsInjected && $modInited)) {
            throw new CoreException("未能正确初始化模块 $modn", "initialize/init");
        }

        // 3 将当前的 模块实例，缓存到 Module::$modules[module_name]
        Module::$modules[$modk] = $this;

        return $this;
    }

    /**
     * 模块启用后，为当前应用实例增加 新的响应方法，将这些新的响应方法 添加到 Operation::$context[app_name] 并更新 路由表
     * !! 子类可以覆盖
     * @return Bool
     */
    protected function injectOprs()
    {
        //当前应用必须已经实例化
        if (App::$isInsed !== true) return false;
        $app = App::$current;
        //操作列表管理类实例
        $operation = $app->operation;

        //!! 如果 操作列表是从 缓存中获取的 则不执行此操作，因为所有操作都已被缓存过
        if ($operation->isCached() === true) return true;

        //调用 Module::getOprs() 方法，定义在 traits/Operation 中，某些模块会覆盖这个方法
        $oprs = static::getOprs();
        //模块的 操作列表 写入 当前应用的 操作列表
        $operation->ctx($oprs);

        return true;
    }

    /**
     * 模块启用后，为当前应用实例增加 新的中间件
     * !! 子类可以覆盖
     * @return Bool
     */
    protected function injectMiddleware()
    {
        //当前应用的 中间件
        $appmids = App::$current->config->middleware;
        if (!Is::nemarr($appmids)) $appmids = [];
        //此模块必须的 中间件
        $mids = $this->config->middleware;
        
        //将 模块中定义的 中间件，添加到 应用中
        if (Is::nemarr($mids)) {
            $appmids = Middleware::extend($appmids, $mids);
            //写入 应用 config
            App::$current->config->ctx("middleware", $appmids);
        }

        return true;
    }

    /**
     * 模块启用后，将在实例化后，立即执行此 初始化操作
     * !! 需要自定义初始化动作的 模块 必须覆盖这个方法
     * @return Bool
     */
    protected function initModule()
    {

        return true;
    }



    /**
     * 静态方法
     */

    /**
     * 判断模块 $mod 是否存在
     * @param String $mod 模块名称 Foobar 或 foo_bar 形式
     * @return String|false 类全称，未找到则返回 false
     */
    public static function has($mod)
    {
        if (!Is::nemstr($mod)) return false;

        //先判断一次
        if (class_exists($mod) && is_subclass_of($mod, Module::class)) return $mod;

        //路径名形式 foo_bar
        $modk = Str::snake($mod, "_");
        //类名形式 FooBar
        $modn = Str::camel($modk, true);
        //模块类必须存在
        $modcls = Cls::find("module/$modn", "Spf\\");
        if (Is::nemstr($modcls) && class_exists($modcls)) return $modcls;

        //如果 App 应用实例已创建，则在应用路径下查找
        if (App::$isInsed === true) {
            //应用名路径形式 foo_bar
            $appk = App::$current::clsk();
            //在 应用路径下查找 模块类
            $modcls = Cls::find("module/$appk/$modn");
            if (Is::nemstr($modcls) && class_exists($modcls)) return $modcls;
        }

        return false;
    }

}