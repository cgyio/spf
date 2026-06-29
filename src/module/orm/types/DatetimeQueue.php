<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 Orm 模块支持的 特殊字段类型  datetime_queue
 */

namespace Spf\module\orm\types;

use Spf\module\orm\Types;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Dater;

class DatetimeQueue extends Types 
{
    /**
     * !! 必须指定的，覆盖父类
     */
    //此 字段类型的 名称 foo_bar
    protected static $type = "datetime_queue";
    //在 creation-sql 中 此字段类型的 语法正则
    protected static $pattern = [
        //!! 可以有多个 pattern 表示在 creation-sql 中可以使用多个别名，都指向此字段类型
        "/^datetime_queue\s+/",
        "/^time_queue\s+/"
    ];
    //定义 此字段类型 在 不同数据库中的 类型映射
    protected static $map = [
        //定义 Orm 默认支持的 数据库类型 对应 映射类型
        "mysql" => "VARCHAR(255)",
        "sqlite" => "TEXT",
    ];
    
    /**
     * 特殊字段类型参数，在数据模型配置参数 column 项下的 键名
     * 不指定，则使用 static::$type 作为键名
     * !! 可以有多个键名，依次从数据模型配置参数 column 项下查找
     */
    protected static $optProps = ["datetime", "datetime_queue"];
    //如果是特殊字段类型，在此指定 在数据库配置文件中，此类型字段的 默认参数形式
    protected static $optDefine = [
        "type" => "datetime",   // datetime|date
    ];
    //当前特殊字段类型下，可选的 子类型，例如：json 类型的字段，可选的子类型包括 associate | indexed
    protected static $optDefineTypes = [
        //!! 特殊类型子类必须定义，默认子类型排在首位
        "datetime", "date",
    ];



    /**
     * 字段参数解析 相关方法
     * !! 仅在 数据库参数解析阶段，由 数据模型参数解析器 Parser 调用
     */

    /**
     * 解析各种 字段类型参数的 parseColumnXxxx 系列方法
     * !! 子类必须实现
     * @param Object $p 由 beforeParse 方法生成的统一入参
     * @return Array|null 各解析方法返回各自的结果，如果解析失败，返回 null
     */
    /**
     * 解析字段的 完整类型数组
     * @return Array|null 不支持的 字段类型 返回 null 
     *  [
     *      "def" => "",
     *      "db" => "",
     *      "js" => "",
     *      "php" => "",
     *  ]
     */
    public static function parseColumnTypesArr($p=null)
    {
        if (is_null($p)) return null;

        //返回的结果  默认值
        return [
            "def"   => $p->type,
            "db"    => $p->maptype,
            "js"    => "Array",
            "php"   => "Array",
        ];
    }



    /**
     * 字段默认值相关
     */

    /**
     * 根据传入的 默认值定义，解析得到实际的默认值结果
     * !! 子类必须实现
     * @param Mixed $defv 传入的默认值定义，可以是 String|Number|Array|Bool
     * @param String|null $defc 此字段可能存在的 特殊类型定义参数，已被 fixColumnDefine() 方法处理过的
     * @return Array|null 如果无法解析，返回 null  否则返回：
     *  [
     *      "value" => php类型的 默认值,
     *      "insql" => "可以写入 creation-sql 的默认值字符串",
     *  ]
     */
    protected static function parseDefaultValue($defv=null, $defc=null)
    {
        if (is_null($defv)) return null;
        if (!Is::nemarr($defc)) $defc = [];
        $deftp = $defc["type"] ?? static::$optDefineTypes[0];

        //传入的默认值 是 json 字符串 '[integer, "Y-m-d h:i:s", ... ]'
        if (Is::json($defv)) {
            $dva = Conv::j2a($defv);
            if (!Is::nemarr($dva) || !Is::indexed($dva)) {
                return [
                    "value" => [],
                    "insql" => "'[]'",
                ];
            }

            //依次处理 默认值中的 每个元素，只能是 整数 或 整数字符串 或 日期字符串
            $ndva = [];
            foreach ($dva as $dvi) {
                //日期字符串
                if (Dater::isStr($dvi)) {
                    if ($deftp==="datetime") {
                        //datetime 类型 默认使用 09 点的时间戳
                        if (strpos($dvi, " ")===false) $dvi .= " 09:00:00";
                    }
                    $ndva[] = strtotime($dvi);
                    continue;
                }

                //整数 或 整数字符串
                if (is_numeric($dvi) && is_int($dvi*1)) {
                    $ndva[] = $dvi*1;
                    continue;
                }
            }

            return [
                "value" => $ndva,
                "insql" => "'[".implode(",",$ndva)."]'",
            ];
        }

        //传入其他类型默认值
        return null;
    }

    /**
     * !! 特殊类型子类，可根据需要定义 默认值生成方法 defaultXxxxGetter 
     * 这些 默认值生成方法，将在 when 参数中定义的 生成时机下(例如：insert 创建新记录时)，由 数据模型类(或实例) 调用
     * 传入 此字段的参数，生成对应的 默认值数据，最终写入数据库
     * @param String $colk 字段名 foo_bar
     * @param Array $conf 字段的配置参数，与 DbConfig::exportColumnConf[] 结构一致
     * @return Mixed 根据字段的 $conf["type"]["php"] 类型，生成对应的 默认值
     */
    //获取今天时间区间
    public static function defaultTodayGetter()
    {
        $t = time();
        $ts = date("Y-m-d", $t)." 00:00:00";
        $te = date("Y-m-d", $t)." 23:59:59";
        return [strtotime($ts), strtotime($te)];
    }
    //TODO...



    /**
     * !! 字段类型子类 必须实现的 实例工具方法
     * !! 仅在 数据库参数解析完成后，在操作数据记录过程中 调用
     * 从 select() 结果中创建符合各字段 php 类型的 记录集
     * 在 update() 之前，将 php 类型数据，转为可写入数据库的 数据
     */

    /**
     * 将 数据库中读取的数据转为 对应 php 类型的数据
     * @param Mixed $val 数据库中保存的数据
     * @return Mixed 转为对应的 php 类型的数据
     */
    public function from($val=null)
    {
        //json 字符串 转为 []
        if (!Is::json($val)) return [];
        $v = Conv::j2a($val);
        //必须是 indexed
        if (!Is::nemidx($v)) return [];
        //每个元素都必须是 时间戳
        $v = array_merge([], array_filter($v, function($vi) {
            if (is_numeric($vi)) $vi = $vi * 1;
            return Dater::isTs($vi);
        }));
        return $v;
    }

    /**
     * 将 在写入数据库之前，将 php 数据 转为 对应的 数据库字段保存类型的数据
     * @param Mixed $val php 数据
     * @return Mixed 对应的 数据库字段保存类型的数据
     */
    public function to($val=null)
    {
        //必须是 indexed
        if (!Is::nemidx($val)) return "[]";
        //每个元素都必须是 时间戳
        $val = array_merge([], array_filter($val, function($vi) {
            if (is_numeric($vi)) $vi = $vi * 1;
            return Dater::isTs($vi);
        }));
        if (!Is::nemidx($val)) return "[]";
        return Conv::a2j($val);
    }

    /**
     * 定义此类型字段的 数据 setter 
     * 在 模型实例内部 通过 __set 魔术方法，调用此方法
     * !! 覆盖父类
     * @param Mixed $val 要设置的 此字段的 新值
     * @param Mixed $old 此字段的 原始值
     * @return Mixed 生成最终的 字段新值，将被写入 模型实例 context
     */
    public function setter($val, $old=null)
    {
        //必须传入 indexed 数组
        if (!Is::nemidx($val)) return [];

        //依次处理数组中的 每个元素
        $val = array_map(function($vi) {
            //时间字符串
            if (Dater::isStr($vi)) return strtotime($vi);
            //时间戳
            if (is_numeric($vi)) $vi = $vi * 1;
            if (Dater::isTs($vi)) return $vi;
            return null;
        }, $val);
        $val = array_filter($val, function($vi) {
            return !is_null($vi);
        });
        $val = array_merge([], $val);
        if (!Is::nemidx($val)) return [];

        return $val;
    }



    /**
     * 某些 特殊字段类型 将自动为 模型原始字段附加 对应计算字段，例如：
     *      uuid 类型的字段 foo 将自动拥有对应的计算字段 foo_uuid_ts 从原始字段 foo 的字段值中，提取时间戳
     * 
     * 特殊类型计算字段是通过在 对应的 Types 子类中，定义 fooBarGetter 方法（必须包含规定的 注释内容）
     * 
     * 通常在 模型记录实例内部，通过 __get 方法读取 类型计算字段的值，例如：
     *      $record 记录实例包含 uuid 类型的字段 foo 则：
     *          $record->foo            --> 返回 uuid 字符串
     *          $record->foo_uuid_ts    --> 返回从 uuid 中提取得到的 时间戳 int
     * 
     * 按下列形式 定义 此字段类型的 对应的 计算字段 getter
     * !! 可以针对特殊字段类型，定义此类型字段的 关联计算字段，将在 记录输出时自动执行数据转换
     * !! 有需要的 字段类型，可自行定义
     */

    /**
     * getter
     * @name str
     * @title 时间字符
     * @type Array
     * @jstype Array
     * @param Array $val 实际字段的值 时间戳
     * @return Array|null 转换为对应时间字符串
     */
    public function strGetter($val)
    {
        if (!Is::nemidx($val)) return [];

        //datetime_queue 类型 datetime | date
        $tp = $this->opt("type");
        $ptn = $tp==="datetime" ? "Y-m-d H:i:s" : "Y-m-d";
        return array_map(function($di) use ($ptn) {
            return date($ptn, $di);
        }, $val);
    }



    /**
     * 内部工具方法
     */


}