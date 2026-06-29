<?php
/**
 * SPF-Orm 数据库处理模块
 * 数据库配置解析工具类
 * 专门解析 switch 特殊类型的 工具类
 * 
 * 处理数据库配置参数中的  $conf["model"]["model_name"]["column"]["switch"] 项目
 */

namespace Spf\module\orm\config\model\column;

use Spf\module\orm\config\model\SpecialColumn;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Cls;

use Spf\module\orm\config\model\column\traits\IndexedConf;

class SwitchColumn extends SpecialColumn 
{
    //引用 trait
    //特殊类型参数 是 indexed 字段名数组
    use IndexedConf;

    /**
     * 此特殊字段类型的 名称 foo_bar
     * 在配置参数中存在 $conf["model"]["model_name"]["column"][...] 的配置项
     * !! 覆盖父类
     */
    protected $special = "switch";

    

    /**
     * 对传入的 此类型特殊字段的 配置参数，进行有效检查，合并默认值，返回值将被缓存到 $this->origin
     * !! 已在 traits/IndexedConf 中实现
     * @param String|Array $conf 要解析的 数据库配置参数中的 此类型字段的 配置参数
     * @return String|Array|null 如果传入的参数无效，返回 null
     */
    //protected function fixOption($conf=null) {}

    /**
     * 解析入口
     * !! 必须实现
     * @param Array $conf 传入待解析的 此类型特殊字段的 配置参数
     * @return Array 返回解析后得到的 将被写入 ColumnParser 数据模型(表)字段解析器实例的 $temp 中的数据
     */
    public function parse()
    {
        //直接调用 trait 方法
        return $this->parseIndexedConf();
    }

}