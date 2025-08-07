<?php
/**
 * 框架核心配置类
 * 操作列表管理 配置类
 */

namespace Spf\config;

use Spf\util\Is;
use Spf\util\Str;

class RouterConfig extends Configer 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [
        //定义 操作列表的 运行时缓存 路径
        "cache" => [
            //全局操作列表 缓存位置
            "operation" => "runtime/operation/cache.php",
            //匿名函数 缓存位置
            "closure" => "runtime/operation/closure.php",
        ],

        //框架 全局路由表文件 位置
        "file" => "lib/route.php",

        //定义 标准的 操作列表 数据格式
        "oprs" => [
            "apis" => [],
            "views" => []
        ],

        //全局启用的 模块名 列表 foo_bar 形式，用于收集这些模块的 可用操作列表
        "modules" => [],

        //其他 在框架启动参数中 手动定义的 路由数据
        /*
        "/路由正则/" => [
            "oprn" => "",
            "class" => "必须的",
            "method" => "必须的",
            "name" => "",
            "title" => "",
            "desc" => "",
            "export" => "",
            "auth" => "",
            "role" => "",
            ...
        ],
        ...
        */
    ];

    

    /**
     * 在 应用用户设置后 执行 自定义的处理方法
     * !! 覆盖父类
     * @return $this
     */
    public function processConf()
    {
        /**
         * 将参数中的 手动定义的 路由信息，提取到 $context["routes"]
         */
        $ctx = $this->context;
        $ruts = [];
        foreach ($ctx as $k => $v) {
            if (!Is::nemstr($k) || !Is::nemarr($v) || !isset($v["class"]) || !isset($v["method"])) continue;
            if (substr($k, 0,1)!=="/" || substr($k, -1)!=="/") continue;
            $ruts[$k] = $v;
        }
        $this->context["routes"] = $ruts;

        return $this;
    }
}