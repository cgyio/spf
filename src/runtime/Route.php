<?php
/**
 * 框架运行时工具类 路由管理类
 * 
 * Operation 操作管理类 初始化生成 操作列表，并将包含 route 路由正则信息的操作，汇总为 路由表数据
 * Route 类 根据 路由表数据 匹配 用户请求，并返回 匹配到的 应用类|响应方法|方法参数
 * 这些匹配到的 响应参数 将被缓存到 Runtime 实例
 * 
 * cgyio/spf 框架的 路由规则：app_name == index 时可以省略
 *      https://host/route_pattern...
 *      https://host/app_name/route_pattern...
 *      https://host/app_name/method_name/arg1/arg2/...
 * 
 * 路由匹配步骤：
 *      0   初始化阶段，收集 所有应用|全局启用模块 的操作列表，生成路由表
 *      1   路由匹配 或 URI 解析得到 当前请求的 App 应用类
 *      2   应用实例化，实例化 全局启用模块|应用内启用模块，修订 当前应用的 操作列表，修订路由表
 *      
 * 
 * !! 此类禁止被继承
 */

namespace Spf\runtime;

use Spf\util\Is;
use Spf\util\Url;

final class Route 
{
    /**
     * 整站 路由表，由 Operation::routes() 方法生成
     * 此数组 在响应过程中 可能被修改
     */
    protected static $context = [
        //数据形式 参考 Operation::routes 方法的注释
    ];

    /**
     * 读取|写入 context
     * @param Array $ctx 要写入的 路由表 数据
     * @return Array $context
     */
    public static function ctx($ctx=[])
    {
        if (Is::nemarr($ctx)) {
            self::$context = array_merge(self::$context, $ctx);
        }
        return self::$context;
    }



    /**
     * 使用 路由表匹配请求的 url，得到请求的 App 应用类全称 | 响应方法操作信息 | 响应方法参数
     * @return Array|null
     */
    public static function match()
    {
        //路由表
        $routes = self::ctx();
        if (!Is::nemarr($routes)) $routes = Operation::routes();
        if (!Is::nemarr($routes)) return null;

        //解析请求 URL
        $url = Url::current();
        //URI 数组
        $path = $url->path;
        //待匹配的 URI 字符串，不带开头的 /
        $uri = implode("/", $path);
        //空 URI 
        if (!Is::nemstr($uri)) return null;

        //依次匹配路由
        foreach ($routes as $pattern => $oprc) {
            //使用 正则 匹配 $uri 字符串
            try {
                $mt = preg_match($pattern, $uri, $matches);
                //未匹配成功，继续下一个
                if ($mt !== 1) continue;

                //匹配成功，将 匹配结果 作为 响应方法参数 返回
                $mcs = array_slice($matches, 1);
                $msc = array_map(function($mci) {
                    return trim($mci, "/");
                }, $mcs);
                $mcstr = implode("/", $mcs);
                $mcs = explode("/", $mcstr);

                //检查是否还有剩余的 uri 路径
                $uarr = [];
                if (strpos($uri, $mcstr)!==false) {
                    $uriarr = explode($mcstr, $uri);
                    if (count($uriarr)>1 && $uriarr[1]!="") {
                        $uarr = explode("/", trim($uriarr[1], "/"));
                    }
                }

                //返回匹配结果
                return [
                    "app" => $oprc["class"],
                    "operation" => $oprc,
                    "args" => $mcs,
                    "uri" => $uarr,
                ];
            } catch (\Exception $e) {
                //正则匹配出错 跳过
                continue;
            }
        }

        //没有匹配到结果
        return null;
    }
}