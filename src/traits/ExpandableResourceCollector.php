<?php
/**
 * SPF 框架 可复用类特征
 * 由 App 和 Module 引用，增加自动收集 ExpandableResource 通用可扩展资源 的 功能
 */

namespace Spf\traits;

use Spf\App;
use Spf\Module;
use Spf\exception\CoreException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Cache;

trait ExpandableResourceCollector
{
    /**
     * 在 App 以及 Module 初始化阶段  收集资源类的 入口方法
     * !! 引用的类，不要覆盖
     * !! 此方法 由 App 和 Module 在 initialize 方法中调用
     * @param App|Module $collector 资源收集者，可以是 App 或 Module 实例
     * @return Bool
     */
    final protected static function collectExres($collector=null)
    {
        //!! 必须是有效的 资源收集者
        if (!$collector instanceof App && !$collector instanceof Module) return false;
        //从 收集者 配置参数中 获取 要收集的 资源类基类
        $excls = $collector->config->ctx("expandableResource/dependency");
        if (!Is::nemidx($excls)) return true;

        //过滤无效的 资源类
        $exs = [];
        foreach ($excls as $exclsi) {
            if (!Is::nemstr($exclsi)) continue;
            //查找真实存在的 资源类基类
            $clsp = Cls::find($exclsi);
            if (!class_exists($clsp)) continue;
            //必须是 Exres 类
            if (!isset($clsp::$isExres) || $clsp::$isExres!==true) continue;
            //排除已经被收集过的类，ExpandableResource 资源类只需要收集一次
            if (!isset($clsp::$isCollected) || $clsp::$isCollected===true) continue;
            $exs[] = $clsp;
        }
        if (!Is::nemidx($exs)) return true;

        //收集者名称
        $cn = $collector->name;
        $ctp = ($collector instanceof App) ? "应用" : "模块";

        //errmsg
        $errmsg = [];

        //执行收集
        $collect = true;
        foreach ($exs as $exi) {
            //执行每个资源类的 collect 方法
            $collect = $collect && $exi::collect();
        }
        if (!$collect) $errmsg[] = "collect 失败";

        //执行收集后的 afterCollect 方法
        $afterCollect = true;
        foreach ($exs as $exi) {
            //执行每个资源类的 collect 方法
            $afterCollect = $afterCollect && $exi::afterCollect();
        }
        if (!$afterCollect) $errmsg[] = "afterCollect 失败";

        if (true!==($collect && $afterCollect)) {
            //报异常
            if (Is::nemidx($errmsg)) {
                $errmsg = "，原因：".implode(" 且 ", $errmsg);
            } else {
                $errmsg = "";
            }
            throw new CoreException("未能正确收集".$ctp." ".$cn." 依赖的通用可扩展资源".$errmsg, "initialize/init");
            return false;
        }

        return true;
    }
} 