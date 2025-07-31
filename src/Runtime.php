<?php
/**
 * 框架核心类
 * 运行时，所有 核心类实例、环境变量、配置参数 等全局资源的 的挂载主体，
 * 框架响应流程的实施者
 */

namespace Spf;

use Spf\Core;
use Spf\Exception;
use Spf\Env;
use Spf\App;
use Spf\app\Operation;
use Spf\util\Autoloader;
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
    //请求实例
    public static $request = null;
    //应用实例
    public static $app = null;
    //响应实例
    public static $response = null;
    //本次会话 启动的 模块实例 []
    protected static $modules = [
        /*
        "FooBar" => 模块实例,
        */
    ];

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
        Exception::regist();

        /**
         * step 1   实例化 环境参数管理类
         * 定义 框架环境参数常量
         */
        Runtime::$env = Env::current($opt["env"] ?? []);

        /**
         * step 2   为 composer 的类自动加载方法 打补丁
         * 将 webroot 路径下的 app|model|module|error 等目录 添加到 自动加载类的路径数组中
         * 需要 环境参数已经完成初始化
         */
        Autoloader::patch();

        Operation::initOprs();
        var_dump(Operation::defines());

        //Path::mkfile("module/foo/bar.json", "{\"bar\":123}");

        /*var_dump("Path::canChmod(module) ==");
        var_dump(Path::canChmod("module"));
        var_dump("Path::canChmod(module/foo) ==");
        var_dump(Path::canChmod("module/foo"));

        var_dump("Path::isWritable(module) ==");
        var_dump(Path::isWritable("module"));
        var_dump("Path::isWritable(module/foo) ==");
        var_dump(Path::isWritable("module/foo"));

        var_dump(Cls::find("app/goods"));
        var_dump(Cls::find("app/goods/lib_test"));
        var_dump(Cls::find("module/goods/TestModule"));
        var_dump(Cls::find("model/goods/db_foo/Bar"));
        var_dump(Cls::find("middleware/goods/in/TestInMiddleware"));
        var_dump(Cls::find("view/goods/TestView"));
        var_dump(Cls::find("exception/goods/GoodsException"));

        var_dump(Cls::find("LibTest"));
        var_dump(Cls::find("LibTestRoot"));
        var_dump(Cls::find("module/TestRootModule"));
        var_dump(Cls::find("model/goods/Goods"));
        var_dump(Cls::find("middleware/in/test_root_in_middleware"));
        var_dump(Cls::find("view/TestRootView"));
        var_dump(Cls::find("exception/MsException"));


        var_dump(Path::find("app/goods"));
        var_dump(Path::find("lib/goods/LibTest.php"));
        var_dump(Path::find("app/goods/LibTest.php"));
        var_dump(Path::find("app/goods/cache", Path::FIND_DIR));
        var_dump(Path::find("module/goods/TestModule.php"));
        var_dump(Path::find("model/goods/db_foo/Bar.php"));
        var_dump(Path::find("db/goods", Path::FIND_DIR));
        var_dump(Path::find("middleware/goods/in/TestInMiddleware.php"));
        var_dump(Path::find("view/goods/TestView.php"));
        var_dump(Path::find("exception/goods/GoodsException.php"));

        var_dump(Path::find("LibTest.php"));
        var_dump(Path::find("LibTestRoot.php"));
        var_dump(Path::find("module/TestRootModule.php"));
        var_dump(Path::find("model/goods/Goods.php"));
        var_dump(Path::find("middleware/in/TestRootInMiddleware.json"));
        var_dump(Path::find("view/TestRootView.php"));
        var_dump(Path::find("exception/MsException.php"));

        $exs = [
            "middleware/in/TestRootInMiddleware.json",
            "db/goods",
            "app/goods/LibTest.php"
        ];
        var_dump(Path::exists($exs));
        var_dump(Path::exists($exs, true, Path::FIND_DIR));*/

        /**
         * step 3   初始化路由表
         * 通过 App::initRoute() 方法，初始化路由表数据
         * 如果 WEB_CACHE = true 则使用缓存的 路由表
         */
        //App::initRoute($opt["route"] ?? []);
        //var_dump(EXT);





        /**
         * step 1   Runtime 实例化
         */
        Runtime::current();

    }








    /**
     * runtime 运行时缓存相关
     */
    
    //缓存数据中的 时间项
    protected $rcTimeKey = "__CACHE_TIME__";
    //缓存数据被读取到 context 中后，添加的 缓存使用标记
    protected $rcSignKey = "__USE_CACHE__";
    //缓存数据的 过期时间 1h
    protected $rcExpired = 60*60;
    //默认的 缓存文件后缀名
    protected $rcExt = ".json";

}