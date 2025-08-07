<?php
/**
 * 框架核心类
 * 运行时，所有 核心类实例、环境变量、配置参数 等全局资源的 的挂载主体，
 * 框架响应流程的实施者
 */

namespace Spf;

use Spf\exception\BaseException;
use Spf\util\Event;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Runtime extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    /**
     * 核心类 单例的挂载点
     */
    //环境参数
    public static $env = null;
    //路由管理
    public static $router = null;
    //应用实例
    public static $app = null;
    //请求实例
    public static $request = null;
    //响应实例
    public static $response = null;
    //本次会话 启动的 模块实例 []
    //public static $modules = [
        /*
        "FooBar" => 模块实例,
        */
    //];

    /**
     * cgyio/spf 框架启动入口
     * 在 index.php 中调用，并输入 框架启动参数 Runtime::start([ ... ])
     * @param Array $opt 框架启动参数
     * @return void
     */
    public static function start($opt=[])
    {
        //此方法只能执行一次
        if (static::$isInsed === true) return;

        //确认输入的 启动参数
        if (!Is::nemarr($opt)) $opt = [];

        /**
         * 框架启动 流程
         */
        @ob_start();
        @session_start();

        /**
         * step 0   全局错误处理
         */
        BaseException::regist();

        /**
         * step 1   实例化 环境参数管理类
         * 定义 框架环境参数常量
         * 处理框架启动参数中的 env 参数项
         */
        Runtime::$env = Env::current($opt);
        
        /**
         * step 2   Router 路由类实例化，路由类 将在实例化后 执行下列操作：
         *  0   生成(并缓存) 整站  全局路由|所有应用|全局启用模块  的 可用操作列表，同时生成 路由表
         *  1   根据 生成的 路由表 匹配得到 当前请求的 App 应用类
         * 处理框架启动参数中的 route|module 参数项
         */
        Runtime::$router = Router::current($opt);
        var_dump(Router::defines());
        var_dump(Router::routes());
        var_dump(Router::closures());

        /**
         * step 3   初始化整站所有 App 应用参数
         *  0   生成(并缓存) 整站所有 App 应用的 所有可用操作列表
         *  1   生成 整站 路由表数据
         * 处理框架启动参数中的 route|operation|app|module|middleware 参数项
         */
        //App::prepare($opt);

        /**
         * step 4   路由匹配，查找本次请求对应的 App 应用类
         * 匹配得到的 结果，保存在 App::$runtime 数组中
         */
        //$appcls = App::find();
        //var_dump(Runtime::is());

        /**
         * step 5   实例化 本次请求对应的 App 应用类
         */
        //Runtime::$app = $appcls::current($opt);
        

        





        /**
         * step 1   Runtime 实例化
         */
        //Runtime::current();


        //Event test
        //Event::trigger("test_evt", Runtime::$current, "foo","bar","jaz");

    }



    /**
     * 运行时实例方法
     */

    /**
     * 从传入的 $opt 数组中选取部分作为 核心类的 启动参数
     * 运行时类 启动参数：route|operation|app|module|middleware
     * !! 子类可覆盖此方法
     * @param Array $opt 传入的框架启动参数，通过 核心类的 current 方法传入的
     * @return Array 选取后的 此核心类的 启动参数
     */
    protected function fixOpt($opt=[])
    {
        //获取 框架启动参数中的 参数项
        $opt = Arr::choose($opt, "route", "operation", "app", "module", "middleware");
        return $opt;
    }

    /**
     * Runtime 运行时类自有的 init 方法，执行以下操作：
     *  0   生成(并缓存) 整站所有  应用|全局启用模块  的 可用操作列表，同时生成 路由表
     *  1   初始化所有 全局启用中间件 的参数，并覆盖中间件的 静态属性
     * !! 子类必须实现
     * @return $this
     */
    final public function initialize()
    {
        // 0 初始化 Operation 可用操作列表 以及 路由表数据
        $opt = Arr::choose($this->ctx, "route", "operation","module");
        if (Operation::$isInited !== true) Operation::initOprs($opt);
        //将生成的 路由表数据，写入 Route::$context
        Operation::routes();

        // 1 将所有全局启用的 模块，添加到 Module::$modules 数组，准备在 App 应用实例化后，实例化这些模块
        $mods = $this->module;
        foreach ($mods as $modk => $modc) {
            //在此阶段，模块还未实例化，仅保存模块参数
            Module::enable($modk, $modc);
        }


        // 1 初始化所有 全局启用中间件 的参数，并覆盖中间件的 静态属性

        return $this;
    }
    
    /**
     * 快捷访问 __get
     * !! 覆盖子类，请在此基础上增加，即 必须在子类 __get 方法中调用 parent::__get()
     * @param String $key 要访问的 不存在的 属性
     * @return Mixed
     */
    public function __get($key)
    {
        /**
         * 
         */
        /**
         * Runtime::$current->ModuleName  --> Runtime::$modules[ModuleName]
         * Runtime::$current->Orm  -->  Runtime::$modules["Orm"]  -->  Orm::$current
         */
        $modn = Str::camel($key, true);
        if (isset(self::$modules[$modn])) {
            return self::$modules[$modn];
        }

        /**
         * 最后
         * 调用父类 __get 方法
         */
        $rtn = parent::__get($key);
        if (!is_null($rtn)) return $rtn;

        return null;
    }









}