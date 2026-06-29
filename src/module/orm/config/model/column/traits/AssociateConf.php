<?php
/**
 * SPF-Orm 数据库操作模块
 * 特殊类型字段参数解析类  通用功能 trait
 * !! 由 SpecialColumn 子类引用的 trait
 * 
 * 引用此 trait 的 SpecialColumn 子类，其对应的 特殊字段参数必须是 associate 关联数组，键名必须是有效的 字段名
 */

namespace Spf\module\orm\config\model\column\traits;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Cls;

trait AssociateConf 
{
    /**
     * 定义此类型字段参数的 默认参数格式
     * !! SpecialColumn 子类必须实现的
     * !! 此特殊类型的参数 必须是 associate 关联数组，键名必须是有效的 字段名
     * !! 此处定义 每个此类型字段的 默认 参数形式，由具体的 特殊类型字段类个自定义
     */
    protected $dftOption = [
        /*
        # time 类型字段的 默认参数
        "type" => "datetime",   # 可选 date|datetime|date-range|datetime-range
        "default" => "",        # 可指定默认值字符串 
        */
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

        //!! 此特殊类型的参数 必须是 associate 关联数组，键名必须是有效的 字段名
        if (!Is::nemarr($conf) || !Is::associate($conf)) return null;
        //去除不存在的 字段名
        $conf = $this->filterAssociateConf($conf);
        //再检查一次 是否为空
        if (!Is::nemarr($conf)) return null;

        return $conf;
    }



    /**
     * 此 trait 的专用方法
     * 由引用的 SpecialColumn 子类，在 parse 方法中引用
     */

    /**
     * 过滤传入的 特殊类型字段参数 中 不存在的 字段名，并使用默认参数填充 各字段的参数
     * @param Array $conf 传入的 特殊类型字段参数  一定是 associate 数组 且 键名为 字段名
     * @return Array 过滤后的 associate 数组
     */
    protected function filterAssociateConf($conf=[])
    {
        if (!Is::nemarr($conf)) return [];

        //当前已经解析得到的 字段名数组
        $colks = $this->temp["columns"];
        //去除不存在的 字段名
        $nconf = [];
        foreach ($conf as $colk => $colc) {
            //如果 键名是 数字，则检查 键值
            if (is_numeric($colk)) {
                if (Is::nemstr($colc) && in_array($colc, $colks)) {
                    //使用 默认参数 填充
                    $nconf[$colc] = Arr::copy($this->dftOption);
                }
                continue;
            }

            //如果键名是字符串，则检查
            if (in_array($colk, $colks)) {
                if (!Is::nemarr($colc) || !Is::associate($colc)) {
                    //空参数，使用默认参数填充
                    $nconf[$colk] = Arr::copy($this->dftOption);
                } else {
                    //有指定参数，则 覆盖到默认参数
                    $nconf[$colk] = Arr::extend($this->dftOption, $colc);
                }
            }
        }
        //返回
        return $nconf;
    }

    /**
     * 向 数据模型(表)参数的 special 项添加 $this->origin 中的所有字段名   
     * 向 对应的 字段参数中 添加 
     *      isXxxx 特殊类型标记
     *      此特殊字段类型的 参数
     * @return Array 返回处理后的参数，可由外部的 ColumnParser 类实例添加到 最终的 数据模型参数中
     */
    protected function parseAssociateConf()
    {
        $rtn = [
            "column" => [],
        ];

        $sk = $this->special;
        $ik = "is".Str::camel($sk, true);
        $colks = [];
        foreach ($this->origin as $colk => $colc) {
            $rtn["column"][$colk] = [
                //增加 特殊字段类型标记  到 每个字段的 参数中
                $ik => true,
                //将对应的 特殊类型参数 添加到 对应的 字段参数中
                $sk => $colc
            ];
            $colks[] = $colk;
        }

        //增加 special 参数 到 当前数据模型参数中
        $rtn["special"] = [  
            $sk => $colks
        ];
        
        //返回解析结果
        return $rtn;
    }
}