<?php
/**
 * SPF-Orm 数据库处理模块
 * 数据库配置解析工具类
 * 专门解析 time 特殊类型的 工具类
 * 
 * 处理数据库配置参数中的  $conf["model"]["model_name"]["column"]["time"] 项目
 */

namespace Spf\module\orm\config\model\column;

use Spf\module\orm\config\model\SpecialColumn;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Cls;

use Spf\module\orm\config\model\column\traits\MultipleConf;

class TimeColumn extends SpecialColumn 
{
    //引用 trait
    //特殊类型参数 可以是 indexed 或 associate 数组
    use MultipleConf;

    /**
     * 定义此类型字段参数的 默认参数格式
     * !! SpecialColumn 子类必须实现的
     * !! 此特殊类型的参数 可以是 indexed 或 associate 数组
     * !! 必须定义 每个此类型字段的 默认 参数形式，由具体的 特殊类型字段类个自定义
     */
    protected $dftOption = [
        // time 类型字段的 默认参数

        //time 日期时间具体的类型，可选 date|datetime|date-range|datetime-range
        "type" => "datetime",
        
        /**
         * 可以指定默认值
         */
        "default" => [
            /**
             * 获取具体 timestamp 的 方法名  
             * 在 工具类 module/orm/util/ColumnUtil 中定义的 静态方法，自动增加 time 前缀：
             *      getter == "now"     --> 将调用 ColumnUtil::timeNow() 方法
             * 
             * !! 可以在当前 App 应用，或 网站根路径下 自定义此工具类，必须继承自此类
             *      app/app_name/db/util/ColumnUtil 
             *      db/util/ColumnUtil
             * 
             * !! 默认空值  表示 不使用 默认值
             * 
             * !! 如果 指定了 ***-range 时间区间类型，则 getter 方法在调用时，会自动增加 Range 后缀：
             *      getter == "now"     --> 将调用 ColumnUtil::timeNowRange() 方法
             */
            "getter" => "",     // false|null| 其他非字符串 类型，都表示 不启用 默认值

            /**
             * 什么时候获取并更新 timestamp 可选：insert|update|delete|select
             * 可以有 多个
             */
            "when" => [
                //默认空值表示 不启用 默认值
            ],
        ],
        /**
         * !! 支持 字符串简写：
         *      ""                      --> [ "getter" => "", "when" => [] ]
         *      "now"                   --> [ "getter" => "now", "when" => ["insert"] ] 
         *      "now@insert,update"     --> [ "getter" => "now", "when" => ["insert","update"] ] 
         * 
         * !! 指定 其他 非字符串类型值，表示不使用 默认值
         */
    ];

    /**
     * 此特殊字段类型的 名称 foo_bar
     * 在配置参数中存在 $conf["model"]["model_name"]["column"][...] 的配置项
     * !! 覆盖父类
     */
    protected $special = "time";

    

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
        return $this->parseMultipleConf();
    }



    /**
     * 外部 DefaultParser 解析类 调用入口
     * 根据传入的 特殊字段类型，实例化 特殊字段解析类，处理并生成 default 默认值参数
     * !! 覆盖父类
     * !! 例如 time 类型的字段，需要处理 default 默认值参数
     * @param String $special 传入的 特殊字段类型 foo_bar
     * @param Parser $parser 此 特殊字段解析类 依赖的 外部 ColumnParser 实例
     * @return Array|null 返回解析结果，这些数据将被 外部的 数据模型(表) 配置解析类 ColumnParser 实例，写入 $parser->temp
     *      如果发生错误，将返回 null
     */
    public static function getDefault($special, $parser=null)
    {
        //TODO：

        return null;
    }

}