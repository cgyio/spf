<?php
/**
 * SPF-Orm 数据库操作模块
 * 特殊类型字段参数解析类  通用功能 trait
 * !! 由 SpecialColumn 子类引用的 trait
 * 
 * 引用此 trait 的 SpecialColumn 子类，其对应的 特殊字段参数必须是 indexed 字段名数组
 */

namespace Spf\module\orm\config\model\column\traits;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Cls;

trait IndexedConf 
{
    /**
     * 定义此类型字段参数的 默认参数格式
     * !! SpecialColumn 子类必须实现的
     * !! 此特殊类型的参数 必须是 [] 字段名数组
     */
    protected $dftOption = [
        //"字段a", "字段b", ...
    ];


    
    /**
     * 对传入的 此类型特殊字段的 配置参数，进行有效检查，合并默认值，返回值将被缓存到 $this->origin
     * !! SpecialColumn 子类必须实现的
     * @param String|Array $conf 要解析的 数据库配置参数中的 此类型字段的 配置参数
     * @return String|Array|null 如果传入的参数无效，返回 null
     */
    protected function fixOption($conf=null)
    {
        //!! forDev
        //var_dump($this->params->modk."---------");
        //var_dump($conf);
        //var_dump($this->temp["columns"]);
        //var_dump("---------");

        //!! 此特殊类型的参数 必须是 [] 字段名数组
        if (!Is::nemarr($conf) || !Is::indexed($conf)) return null;
        //去除不存在的 字段名
        $conf = $this->filterIndexedConf($conf);
        //再检查一次 是否为空
        if (!Is::nemarr($conf)) return null;

        return $conf;
    }



    /**
     * 此 trait 的专用方法
     * 由引用的 SpecialColumn 子类，在 parse 方法中引用
     */

    /**
     * 过滤传入的 特殊类型字段参数 中 不存在的 字段名
     * @param Array $conf 传入的 特殊类型字段参数  一定是 indexed 数组
     * @return Array 过滤后的 字段名数组
     */
    protected function filterIndexedConf($conf=[])
    {
        if (!Is::nemarr($conf)) return [];

        //当前已经解析得到的 字段名数组
        $colks = $this->temp["columns"];
        //去除不存在的 字段名
        $conf = array_merge([], array_diff(
            $conf, 
            array_diff($conf, $colks)
        ));
        //返回
        return $conf;
    }

    /**
     * 向 数据模型(表)参数的 special 项添加 $this->origin 中的所有字段名  
     * 向 对应的 字段参数中 添加 isXxxx 特殊类型标记
     * @return Array 返回处理后的参数，可由外部的 ColumnParser 类实例添加到 最终的 数据模型参数中
     */
    protected function parseIndexedConf()
    {
        $rtn = [
            "column" => [],
        ];

        //增加 特殊字段类型标记  到 每个字段的 参数中
        $ik = "is".Str::camel($this->special, true);
        foreach ($this->origin as $colk) {
            $rtn["column"][$colk] = [
                $ik => true
            ];
        }

        //增加 special 参数 到 当前数据模型参数中
        $spec = $this->special;
        $rtn["special"] = [  
            $spec => $this->origin
        ];

        //返回解析结果
        return $rtn;
    }
}