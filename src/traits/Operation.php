<?php
/**
 * 框架 可复用类特征
 * 为 引用的类 增加 操作列表 相关功能：
 *      Class::getOprs()    获取类中定义的 特殊操作方法 包括 default 方法，解析注释，获取操作信息
 */

namespace Spf\traits;

use Spf\util\Operation as utilOpr;
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
        //操作标识前缀，不带操作类型
        $pre = utilOpr::getOprnPrefix($cls);
        //操作说明前缀
        $intr = utilOpr::getOprnTitle($cls);

        //要检查的 操作类型，在 utilOpr::$types 中定义
        $types = utilOpr::$types;

        //找到的 标准的 操作列表数组
        $oprs = [];
        foreach ($types as $type) {
            //调用 utilOpr::oprs() 方法获取 指定类型的 操作列表，得到标准的 操作列表数组
            $oprsi = utilOpr::oprs($cls, $type, "public,&!static", $type."/".$pre, $intr);
            //合并
            $oprs = Arr::extend($oprs, $oprsi);
        }

        //默认操作 default
        $dftopr = utilOpr::dftOprc($cls, $pre, $intr);
        if (Is::nemarr($dftopr)) {
            //生成的 操作标识
            $oprn = $dftopr["oprn"] ?? null;
            //export
            $expt = $dftopr["export"] ?? null;

            if (Is::nemstr($oprn) && Is::nemstr($expt)) {
                //写入 oprs
                $oprs[$expt."s"][] = $oprn;
                $oprs[$oprn] = $dftopr;
            }
        }
                    

        //返回找到的 操作列表 标准数据格式
        return $oprs;
    }
}