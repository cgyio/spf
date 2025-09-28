<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Json 子类，继承自 Plain 资源类
 * 处理 *.json 类型的 资源
 * 
 * 一些复合类型的资源 例如 Lib|Theme|Icon 等，其主文件是 json 格式文件
 * 应通过 Json 资源类的 JsonFactory 工厂方法 转发到对应的 资源类，以创建正确类型的资源实例
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

class Json extends Plain
{
    /**
     * 定义 某个 json 文件作为其他类型资源的 主文件 时，json 数组中应包含的 键名
     * 同时定义 其他类型资源描述的 标准数据格式
     */
    protected static $resJsonKey = "__RESOURCE__";
    protected static $stdResJsonVal = [
        //其他资源类型的 类名|类全称 或 可用 Cls::find 解析的类路径
        "class" => "",
        //要合并到 资源实例启动参数 opts 中的 必要参数
        "opts" => [],
    ];

    /**
     * 定义 json 类型的工厂方法
     * 针对 某些复合类型的 资源，使用 json 作为其主文件的，应在此方法内部，解析并转发到对应的 资源类
     * 并创建对应资源类型的 资源实例
     * 例如：访问 foo.theme.json 文件，应创建 Theme 类型的 资源实例
     * !! 复合类型资源的主文件 *.json 必须是本地文件
     * @param Array $opts 资源的实例化参数
     * @return Resource 对应类型的 资源实例
     */
    public static function JsonFactory($opts=[])
    {
        //传入的 真实路径
        $real = $opts["real"] ?? null;
        if (!Is::nemstr($real) || !file_exists($real)) return new Json($opts);

        //读取 json 内容
        $json = file_get_contents($real);
        $json = Conv::j2a($json);
        //如果 json 数据中包含 static::$resJsonKey 定义的项目，则表示这是一个 其他类型的资源
        $key = Json::$resJsonKey;
        if (isset($json[$key]) && Is::nemarr($json[$key])) {
            //获取指向的其他资源类型的 描述数据
            $resc = Arr::extend(Json::$stdResJsonVal, $json[$key]);
            //指向的 其他资源类
            $clsp = $resc["class"] ?? null;
            if (!Is::nemstr($clsp)) return new Json($opts);
            //获取对应的 资源类全称
            if (Str::hasAny($clsp, "/", "\\") === false) $clsp = "module/src/resource/$clsp";
            $cls = Cls::find($clsp);
            if (!class_exists($cls)) return new Json($opts);
            //需要合并到 opts 中的数据
            $nopts = $resc["opts"] ?? [];
            if (Is::nemarr($nopts)) $opts = Arr::extend($opts, $nopts);
            //创建资源实例
            return new $cls($opts);
        }
        
        //未指向其他资源类
        return new Json($opts);
    }
}