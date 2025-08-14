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
     *  2   调用 Request::$current->getOprc 方法，查找请求的 响应方法
     *  3   执行 此应用类 自定义的 初始化方法
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
        var_dump($this->operation->defines());

        // 2 查找此次请求的 实际 操作信息
        $oprc = Request::$current->getOprc();
        var_dump(Request::request());
        var_dump(Request::gets());
        throw new AppException(Request::request()->oprc["oprn"], "route/missoprc");

        // 3 执行 此应用类 自定义的 初始化方法
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
}