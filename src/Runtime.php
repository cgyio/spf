<?php
/**
 * 框架核心类
 * 运行时，所有 核心类实例、环境变量、配置参数 等全局资源的 的挂载主体，
 * 框架响应流程的实施者
 */

namespace Spf;

use Spf\exception\BaseException;
use Spf\exception\CoreException;
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
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;

    /**
     * 核心类 单例的挂载点
     */
    //环境参数
    public static $env = null;
    //请求实例
    public static $request = null;
    //应用实例
    public static $app = null;
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
         * step 2   实例化 Request 请求，请求类将在实例化后执行下列操作：
         *  0   创建当前请求的 Url 实例，获取相应的 请求参数
         *  1   创建请求头 RequestHeader 实例，获取相应的 请求参数
         *  2   创建 Ajax 请求处理实例，获取相应的 参数
         *  3   创建所有传入的 数据对象实例 $_GET | $_POST | $_FILES | php://input
         *  4   解析当前请求的 Url 得到 目标 App 应用类
         * 处理框架启动参数中的 request 参数项
         */
        Runtime::$request = Request::current($opt);

        /**
         * step 3   实例化 App 应用类，当前请求的应用类 实例化后，将执行下列操作：
         *  0   生成(并缓存)此应用的 全部操作列表，同时生成 路由表
         *  1   实例化参数中的所有 启用的模块
         *  2   调用 Request::$current->getOprc 方法，查找请求的 响应方法
         *  3   执行 此应用类 自定义的 初始化方法
         * 处理框架启动参数中的 app|route|module|middleware 参数项
         */
        $appcls = Runtime::$request->getApp();
        Runtime::$app = App::current($opt, $app);

        /**
         * 框架初始化完成，开始执行 标准响应流程
         */
        
        /**
         * step 4   依次 实例化并执行 入站中间件 过滤
         * 如果有 中间件过滤不通过，将终止响应
         */
        Middleware::process("in");

        /**
         * step 5   创建 Response 响应类实例，当前 响应类 实例化后，将执行下列操作：
         *  0   创建响应头 ResponseHeader 实例
         *  1   创建响应码管理实例 创建时 默认状态码 200
         *  2   收集必须的 响应参数
         *  3   创建 Exporter 类实例
         *  4   如果 WEB_PAUSE==true 尝试中断响应
         * 处理框架启动参数中的 response 参数项
         */
        Runtime::$response = Response::current($opt);
        var_dump(Response::$current);

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
     * Runtime 运行时类自有的 init 方法，执行以下操作：
     *  0   生成(并缓存) 整站所有  应用|全局启用模块  的 可用操作列表，同时生成 路由表
     *  1   初始化所有 全局启用中间件 的参数，并覆盖中间件的 静态属性
     * !! 子类必须实现
     * @return $this
     */
    final public function initialize()
    {
        

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