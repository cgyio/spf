<?php
/**
 * 框架 App 应用类 路由管理类
 * 
 * 类 属性|方法 用于：
 *      查找当前 webroot 路径下的所有可用的 App 应用类，收集路由表数据
 *      根据输入的 URI 查找匹配 目标应用 以及 目标响应方法
 * 实例的 属性|方法 用于：
 *      管理 关联的 App 应用实例的 所有可用 路由信息
 * 
 * !! 此类禁止被继承
 */

namespace Spf\app;

use Spf\app\Operation;
use Spf\util\Is;

final class Route 
{
    /**
     * 收集到的 所有 App 应用类的 路由表数据
     * 此列表可能在 框架运行过程中被修改，
     * 如果 WEB_CACHE 开启，此列表会被缓存
     */
    protected static $all = [
        /*
        "应用名称(应用文件夹名)，全小写，下划线_ 格式：foo_bar" => [

            # 定义一个路由
            "路由匹配正则表达式：foo/(\d+)" => [
                "oprn" => "指定此路由匹配的 操作标识：api/foo_bar:jaz_tom",
                # 或者可以手动指定 App 类中的 某个 public,&!static 方法，此方法必须是 可用的 操作(拥有操作标识)
                "method" => "方法名，带后缀，驼峰，首字母小写：jazTomApi"
            ],
        ],
        ...

        # 还可以手动定义 全局路由
        "正则表达式：src/(.+/?)" => [
            "app" => "手动指定 应用类全称，或可以 Cls::find() 的类名：app/foo_bar",
            "method" => "jazTomApi",
        ],
        ...
        */
    ];

    /**
     * 收集所有 App 应用类的 可用 路由表信息
     * !! 必须在 Operation::initOprs() 方法执行后，路由解析之前 执行
     * @param Array $opt 框架启动参数中 定义的 route项 全局路由表
     * @return Bool
     */
    public static function initRoutes($opt=[])
    {
        if (Is::nemarr($opt) && Is::associate($opt)) {
            //启动参数中的 route 项定义了 全局路由表，附加到 all
            self::$all = array_merge(self::$all, $opt);
        }

        //经过初始化的 Operation::$all
        $defs = Operations::defines();
        foreach ($defs as $oprn => $opc) {
            if (!Is::nemarr($oprc) || !isset($opc["route"])) continue;
            //在操作信息中定义了 route 信息
            $ri = $opc["route"];
            if (!Is::nemstr($ri)) continue;
            //添加到 路由表中
            $appcls = $opc["class"];
            $appk = $appcls::clsk();
            if (!isset(self::$all[$appk])) self::$all[$appk] = [];
            self::$all[$appk][$ri] = $opc;
        }

        return true;

    }
}