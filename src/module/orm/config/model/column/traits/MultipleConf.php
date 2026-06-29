<?php
/**
 * SPF-Orm 数据库操作模块
 * 特殊类型字段参数解析类  通用功能 trait
 * !! 由 SpecialColumn 子类引用的 trait
 * 
 * 引用此 trait 的 SpecialColumn 子类，其对应的 特殊字段参数  可以是 indexed 或 associate 数组
 */

namespace Spf\module\orm\config\model\column\traits;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Cls;

use Spf\module\orm\config\model\column\traits\IndexedConf;
use Spf\module\orm\config\model\column\traits\AssociateConf;

trait MultipleConf 
{
    /**
     * 定义此类型字段参数的 默认参数格式
     * !! SpecialColumn 子类必须实现的
     * !! 此特殊类型的参数 可以是 indexed 或 associate 数组
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

        //!! 此特殊类型的参数 可以是 indexed 或 associate 数组
        if (!Is::nemarr($conf)) return null;

        //过滤不存在的 字段名，并填充 默认参数
        $conf = $this->filterMultipleConf($conf);
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
     * @param Array $conf 传入的 特殊类型字段参数  可以是 indexed 或 associate 数组
     * @return Array 过滤后的 一填充默认参数的 associate 数组
     */
    protected function filterMultipleConf($conf=[])
    {
        if (!Is::nemarr($conf)) return [];

        //是否 indexed 标记
        $isIndexed = Is::indexed($conf);

        //过滤不存在的 字段名
        if ($isIndexed) {
            //传入了 indexed 形式的 参数
            $conf = $this->filterIndexedConf($conf);
        } else {
            //传入了 associate 形式的 参数
            $conf = $this->filterAssociateConf($conf);
        }
        
        //为空 直接返回
        if (!Is::nemarr($conf)) return [];

        if ($isIndexed) {
            //如果是 indexed 数组，则使用 默认参数填充
            $nconf = [];
            foreach ($conf as $colk) {
                $nconf[$colk] = Arr::copy($this->dftOption);
            }
            return $nconf;
        } else {
            //如果是 associate 形式，则已经在 filterAssociateConf 方法中使用 默认参数填充过，直接返回
            return $conf;
        }
    }

    /**
     * 向 数据模型(表)参数的 special 项添加 $this->origin 中的所有字段名   
     * 向 对应的 字段参数中 添加 
     *      isXxxx 特殊类型标记
     *      此特殊字段类型的 参数
     * @return Array 返回处理后的参数，可由外部的 ColumnParser 类实例添加到 最终的 数据模型参数中
     */
    protected function parseMultipleConf()
    {
        //直接使用 parseAssociateConf() 方法
        return $this->parseAssociateConf();
    }
}