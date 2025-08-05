<?php
/**
 * 框架 App 应用类 路由管理类
 * 
 * Operation 操作管理类 初始化生成 操作列表，并将包含 route 路由正则信息的操作，汇总为 路由表数据
 * Route 类 根据 路由表数据 匹配 用户请求，并返回 匹配到的 应用类|响应方法|方法参数
 * 这些匹配到的 响应参数 将被缓存到 Runtime 实例
 * 
 * !! 此类禁止被继承
 */

namespace Spf\app;

use Spf\app\Operation;
use Spf\util\Is;
use Spf\util\Url;

final class Route 
{
    /**
     * 收集到的 所有 App 应用类的 路由表数据
     */
    protected static $all = [
        /*

        # 定义一个路由
        !! 手动定义路由 应在 应用路径下 的 route.php 文件中  可省略 class 等参数
        "路由匹配正则表达式：foo/(\d+)" => [
            "oprn" => "指定此路由匹配的 操作标识：api/foo_bar:jaz_tom",
            # 或者可以手动指定 App 类中的 某个 public,&!static 方法，此方法必须是 可用的 操作(拥有操作标识)
            "method" => "方法名，带后缀，驼峰，首字母小写：jazTomApi"
        ],
        ...

        # 还可以手动定义 全局路由
        !! 手动定义全局路由，必须完整指定 class|method|name|title|desc 等参数，class 必须指向某个应用的类全称，method 可以是一个函数
        !! 全局路由可以定义在 应用路径下的 route.php 中，或 在框架启动参数 [ "route" => [ ... ] ] 或 webroot/[lib]/route.php
        "正则表达式：src/(.+/?)" => [
            "app" => "手动指定 应用类全称，或可以 Cls::find() 的类名：app/foo_bar",
            "method" => "jazTomApi",
        ],
        ...
        */
    ];

    /**
     * 收集所有 App 应用类的 可用 路由表信息
     * !! 此操作将通过 Operation::initOprs 方法，执行全局 可用操作初始化，最终生成可用的 路由表
     * @param Array $opt 框架启动参数中 定义的 route项 全局路由表
     * @return Bool
     */
    public static function initRoutes($opt=[])
    {
        //确保 Operation 已经初始化
        if (Operation::$isInited !== true) Operation::initOprs($opt);
        //调用 Operation::routes 获取所有 应用的 可用的 路由操作
        $routes = Operation::routes();
        
        //写入 $all
        self::$all = $routes;
        return true;
    }

    /**
     * 输出 路由表
     * @return Array
     *  [ 
     *      "路由正则" => [ 操作信息 ... ], 
     *      ... 
     *  ]
     */
    public static function defines()
    {
        return self::$all;
    }

    /**
     * 解析用户输入的 url 匹配操作
     * 
     */
    public static function match()
    {
        //$url = "https://ms.systech.work/?format=json#hash";
        //$ud = parse_url($url);
        //var_dump($ud);

        $url = Url::current();
        var_dump($url->full);

        $url1 = Url::mk("../../view/login?format=html&foo=bar");
        var_dump($url1->full);

        $url2 = Url::mk("/src/foo_bar.js");
        var_dump($url2->full);

        $url3 = Url::mk("/src/foo_bar.js?format=__delete__");
        var_dump($url3->full);
    }
}