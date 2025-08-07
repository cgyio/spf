<?php
/**
 * 框架 可复用类特征
 * 为 引用的类 增加 操作列表 相关功能：
 *      Class::oprs(api, ...)   获取类中定义的 特殊操作方法，解析注释，获取操作信息
 */

namespace Spf\traits;

use Spf\Router;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\traits\Base as BaseTrait;

trait Operation 
{
    //需要使用 BaseTrait
    use BaseTrait;

    //增加一个标记，表示 引用的类 使用了 Operation 相关功能
    public static $hasOperationTrait = true;

    /**
     * 获取标准的 操作列表数组，定义在实例化后的 Route::$current->config->ctx("oprs")
     * @return Array
     */
    final public static function dftOprs()
    {
        if (Router::$isInsed === true) {
            return Arr::copy(Router::$current->config->ctx("oprs"));
        }
        //默认的
        return [
            "apis" => [],
            "views" => [],
        ];
    }

    /**
     * 筛选类中定义的 操作列表 api|view
     * 解析这些方法的注释，得到 标准的操作信息 列表
     * @param String $type 筛选方法类型 api|view 默认 api
     * @param String $filter 筛选类中方法时，指定筛选方法 默认 public,&!static 
     * @param String $pre 操作标识 前缀，默认不指定，从 $cls 解析得到
     * @param String $intr 操作说明字符串 前缀，默认不指定，从 $cls 解析得到
     * @return Array 标准的 操作列表数据格式，与 self::$dftOprs 格式一致
     */
    final public static function oprs($type="api", $filter="public,&!static", $pre=null, $intr=null)
    {
        //准备标准的 操作列表输出数据
        $oprs = [
            "apis" => [],
            "views" => [],
        ];

        //当前类全称
        $cls = static::class;

        //参数默认值
        if (!Is::nemstr($type)) $type = "api";
        if (!Is::nemstr($filter)) $filter = "public,&!static";
        //解析操作标识前缀
        if (!Is::nemstr($pre)) {
            //类全称去除 NS 头 foo/bar_jaz/... 形式
            $pre = Cls::rela($cls);
            //App 应用类，还要去除 app/ 前缀
            if (static::isAppCls() === true) $pre = substr($pre, 4);
        }
        $pre = "$type/$pre";
        if (!Is::nemstr($intr)) {
            //从 $cls 类解析 操作说明前缀
            $clsps = Cls::ref($cls)->getDefaultProperties();
            $intr = $clsps["intr"] ?? Cls::name($cls);
        }

        //遍历 类中定义的 特定方法
        $ms = Cls::specific(
            $cls,
            $filter,
            $type,
            null,
            function($mi, $conf) use ($type, $pre, $intr) {
                //附加 uac 信息到 操作信息数组
                //!! 将生成操作标识
                $conf = Cls::parseMethodInfoWithUac($mi, $conf, $pre, $intr);
                //附加 export 参数
                $conf["export"] = $type;
                return $conf;
            }
        );

        //收集到的 操作列表 格式化
        foreach ($ms as $fn => $fc) {
            if (!isset($fc["oprn"]) || !Is::nemstr($fc["oprn"])) continue;
            $oprs[$type."s"][] = $fn;
            $oprs[$fc["oprn"]] = $fc;
        }

        //返回 操作列表
        return $oprs;
    }

    /**
     * 增加 获取操作列表 的自定义方法
     * !! 引用的类可覆盖
     * @return Array 标准的 操作列表数据格式
     */
    public static function getOprs()
    {
        //应用的 属性
        $cls = static::class;
        $clsn = static::clsn();
        $clsk = static::clsk();

        //操作标识前缀
        //类全称去除 NS 头 foo/bar_jaz/... 形式
        $pre = Cls::rela($cls);
        //App 应用类，还要去除 app/ 前缀
        if (static::isAppCls() === true) $pre = substr($pre, 4);

        //操作说明前缀
        $intr = Cls::ref($cls)->getDefaultProperties()["intr"] ?? $clsn;

        //获取 标准操作列表格式
        $oprs = static::dftOprs();

        //apis
        $apis = static::oprs("api", "public,&!static", $pre, $intr);
        //views
        $views = static::oprs("view", "public,&!static", $pre, $intr);

        //合并
        $ms = array_merge($apis, $views);
        foreach ($ms as $k => $mc) {
            if (!Is::nemarr($mc) || !isset($mc["name"]) || !isset($mc["oprn"]) || !isset($mc["export"])) continue;
            $oprn = $mc["oprn"];
            $expt = $mc["export"];
            $oprs[$expt."s"][] = $mc["name"];
            $oprs[$oprn] = $mc;
        }

        //返回找到的 操作列表 标准数据格式
        return $oprs;
    }
}