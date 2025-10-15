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

        //尝试从 json 路径中 获取对应复合资源的 类全称
        $comCls = static::getCompoundClsFromJsonPath($real);
        if (!Is::nemstr($comCls)) return new Json($opts);

        //读取 json 内容
        $json = file_get_contents($real);
        $json = Conv::j2a($json);
        //如果 json 数据中包含 static::$resJsonKey 定义的项目，则表示这个复合资源类型 定义了额外的实例化参数
        $key = Json::$resJsonKey;
        if (!isset($json[$key]) || !Is::nemarr($json[$key])) return new $comCls($opts);
        
        //获取复合资源的 描述数据
        $resc = Arr::extend(Json::$stdResJsonVal, $json[$key], true);
        //需要合并到 opts 中的数据
        $nopts = $resc["opts"] ?? [];
        if (Is::nemarr($nopts)) $opts = Arr::extend($opts, $nopts);

        //此复合资源 可以通过 class 参数 指向其他复合资源类 必须是 $comCls 的子类
        $clsp = $resc["class"] ?? null;
        if (!Is::nemstr($clsp)) return new $comCls($opts);
        //获取复合资源子类 类全称
        if (Str::hasAny($clsp, "/", "\\") === false) $clsp = "module/src/resource/$clsp";
        $cls = Cls::find($clsp);
        if (class_exists($cls) && is_subclass_of($cls, $comCls)) return new $cls($opts);
        
        //class 参数指定的类 不是 $comCls 的子类，或 不存在 则使用 $comCls
        return new $comCls($opts);
    }

    /**
     * 针对 *.foo.json 符合资源类型，从 json 文件路径中 获取此复合资源的后缀名 foo
     * @param String $path *.foo.json 文件的路径
     * @return String|null 复合资源类型后缀名，即小写的 类名
     */
    public static function getCompoundExtFromJsonPath($path)
    {
        if (!Is::nemstr($path)) return null;
        $pb = strtolower(basename($path));
        if (substr($pb, -5)!==".json") return null;
        $pc = substr($pb, 0, -5);
        if (strpos($pc, ".")===false) return null;
        $pa = explode(".", $pc);
        return array_slice($pa, -1)[0];
    }

    /**
     * 根据 json 文件名 判断此文件是否是某个复合资源的 主文件
     * @param String $path *.foo.json 文件的路径
     * @return String|null 如果是复合资源，则返回符合资源类全称，否则返回 null
     */
    public static function getCompoundClsFromJsonPath($path)
    {
        if (!Is::nemstr($path)) return null;
        //从 路径 解析得到 复合资源 ext
        $comExt = static::getCompoundExtFromJsonPath($path);
        if (!Is::nemstr($comExt)) return null;
        //查找此 复合资源的类全称
        $cls = Resource::resCls($comExt);
        //此资源类必须是 Compound 子类
        if (!class_exists($cls) || !is_subclass_of($cls, Compound::class)) return null;

        return $cls;
    }
}