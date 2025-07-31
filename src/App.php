<?php
/**
 * 框架核心类
 * 应用类基类，所有业务功能都应通过此类 来实现
 */

namespace Spf;

use Spf\Core;

class App extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    /**
     * 应用的元数据
     */
    //应用的说明信息
    public $intr = "";
    //应用的名称 类名 FooBar 形式
    public $name = "";

    /**
     * 全局路由表数据
     */
    protected static $route = [
        /*
        "操作标识 api/foo:bar" => [

            # 路由信息 == 操作信息 通过解析相关方法的 注释信息得到

            "oprn" => "操作标识，键名，全小写，下划线_ 形式：api/foo:bar",
            "class" => "此路由操作的 App 应用类全称：NS\app\Foo",
            "method" => "实际执行的方法名，驼峰，首字母小写，带 -Api|-View 等类型的后缀：barApi",
            "name" => "在对应的 App 应用类参数类实例 context[apis|views|...] 数组中的键名，全小写下划线_形式：bar",
            "export" => "输出方式，影响创建的 Response 响应实例类型，可选 api|view ：api",
            "auth" => 此路由操作是否启用 Uac 权限控制，默认 true 通常在方法注释中指定：true,
            "role" => "可额外指定此操作的权限角色，在注释中指定，默认 all 多个角色用 , 隔开：all",

            # 其他 方法注释中指定的 操作信息
            ...
        ],

        # 还可以在框架启动参数中，手动注册路由操作
        ...
        */
    ];

    



    /**
     * 全局路由表数据初始化
     * 如果 WEB_CACHE 启用，则首先尝试读取缓存的 路由表
     */
}