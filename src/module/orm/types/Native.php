<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 Orm 模块支持的 原生字段类型，包括：
 *      varchar, char, text,
 *      integer, bigint, tinyint,
 *      float,
 * 
 * 与 其他特殊类型类 不同
 * 
 */

namespace Spf\module\orm\types;

use Spf\module\orm\Types;
use Spf\module\orm\config\model\Parser;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Path;
use Spf\util\Cls;
use Spf\util\Conv;

class Native extends Types 
{
    /**
     * 当某个 字段类型子类被 collect 收集时，创建 所有支持类型的数据库的 类型映射
     * !! trait 中要求的，Native 原生类型类覆盖 Types 基类
     * @return Bool
     */
    protected static function whenCollect()
    {
        //所有原生字段类型 参数 添加到 Types 基类参数中
        foreach (self::$nativeTypes as $ntp) {
            if (in_array($ntp, Types::$types) || !isset(self::$nativePatterns[$ntp])) continue;
            //插入 Types::$types
            Types::$types[] = $ntp;
            //插入 Types::$patterns
            Types::$patterns[$ntp] = self::$nativePatterns[$ntp];
        }
        
        //依次将 $nativeMaps 中定义的 类型映射，写入 Types::$maps 中
        $rtn = true;
        foreach (self::$nativeMaps as $driver => $maps) {
            $rtn = $rtn && static::extendMapper($driver, $maps);
        }
        return $rtn;
    }



    /**
     * !! 必须指定的，覆盖父类
     */
    //此 字段类型的 名称 foo_bar
    protected static $type = "";
    //在 creation-sql 中 此字段类型的 语法正则
    protected static $pattern = "";
    //定义 此字段类型 在 不同数据库中的 类型映射
    protected static $map = [];
    //是否原生字段类型 
    protected static $isNative = true;
    //如果是特殊字段类型，在此指定 在数据库配置文件中，此类型字段的 默认参数形式
    protected static $dftOption = [];

    /**
     * !! Native 原生类型自有参数
     * 定义 SPF-Orm 模块支持的原生字段类型的 相关参数
     */
    //原生字段类型 列表
    protected static $nativeTypes = [
        //原生类型  直接定义
        "varchar", "char", "text", "integer", "bigint", "tinyint", "float",
    ];
    //原生字段类型的 patterns
    protected static $nativePatterns = [
        //原生类型  直接定义
        "varchar"       => "/^varchar(\([0-9]+\))?\s+/",
        "char"          => "/^char(\([0-9]+\))?\s+/",
        "text"          => "/^text\s+/",
        "integer"       => "/^integer\s+/",
        "bigint"        => "/^bigint\s+/",
        "tinyint"       => "/^tinyint\s+/",
        "float"         => "/^float\s+/",
    ];
    //各默认支持的 数据库驱动类型中 原生字段类型的 映射类型
    protected static $nativeMaps = [
        //框架默认支持的 数据库类型
        "mysql" => [
            //原生类型的映射  直接定义
            "varchar"       => "VARCHAR(255)",
            "char"          => "CHAR(255)",
            "text"          => "TEXT",
            "integer"       => "INT",
            "bigint"        => "BIGINT",
            "tinyint"       => "TINYINT",
            "float"         => "FLOAT",
        ],

        "sqlite" => [
            //原生类型的映射  直接定义
            "varchar"       => "TEXT",
            "char"          => "TEXT",
            "text"          => "TEXT",
            "integer"       => "INTEGER",
            "bigint"        => "INTEGER",
            "tinyint"       => "INTEGER",
            "float"         => "REAL",
        ],
    ];

    /**
     * 判断传入的 字段类型 foo_bar 是否原生类型
     * @param String $type 
     * @return Bool
     */
    public static function includes($type)
    {
        if (!Is::nemstr($type)) return false;
        $type = Str::snake($type, "_");
        return in_array($type, self::$nativeTypes) && isset(self::$nativePatterns[$type]);
    }



    /**
     * 字段参数解析 相关方法
     * !! 仅在 数据库参数解析阶段，由 数据模型参数解析器 Parser 调用
     */

    /**
     * 如果是 特殊字段类型，且存在 $optDefine
     * 必须 定义一个合并 字段配置参数 与 $optDefine 默认参数的 方法
     * !! 可 通过 Types 基类链式调用  或  类型子类直接调用
     * !! 非原生字段类型，必须定义
     * !! 覆盖父类，Native 原生字段类型不需要处理 特殊类型参数，直接返回 []
     * @return Array 返回的参数结构与 static::$optDefine[] 一致
     */
    protected static function fixColumnDefine($colk, $parser=null, $extra=[])
    {
        //!! Native 原生字段类型不需要处理 特殊类型参数，直接返回 []
        return [];
    }

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
        $rtn = [
            "def"   => $p->type,
            "db"    => $p->maptype,
            "js"    => "String",
            "php"   => "String",
        ];

        //原生类型 与 js|php 类型映射
        switch ($p->type) {
            case "varchar":
            case "char":
            case "text":
                return $rtn;
                break;
            case "integer":
            case "bigint":
            case "tinyint":
                return Arr::extend($rtn, [
                    "js" => "Number",
                    "php" => "Integer"
                ]);
                break;
            case "float":
                return Arr::extend($rtn, [
                    "js" => "Number",
                    "php" => "Float"
                ]);
                break;
            //不支持的 原生类型
            default:
                return null;
                break;
        }

        return null;
    }
    /**
     * 解析数据模型参数中 针对此字段的 所有特殊类型参数，返回结果将被合并到 字段最终参数中
     * @return Array|null 返回解析结果，将被合并到 字段最终参数中，结构与 DbConfig::exportColumnConf[] 一致
     */
    public static function parseColumnConfig($p=null)
    {
        //!! Native 原生类型没有特殊类型参数
        return null;
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
     *      "value" => php类型下的 默认值,
     *      "insql" => "可以写入 creation-sql 的默认值字符串",
     *  ]
     */
    protected static function parseDefaultValue($defv=null, $defc=null)
    {
        //!! Native 原生类型的 默认值定义 一定是在 creation-sql 中的 字符串
        if (!Is::nemstr($defv)) return null;
        //当前的原生类型
        $type = static::$type;

        //返回值
        $rtn = [
            "value" => null,
            "insql" => "",
        ];

        //原生类型 的 默认值
        switch ($type) {
            case "varchar":
            case "char":
            case "text":
                //去除可能存在的 '' ""
                $dv = Str::trimQuote($defv);
                $rtn["value"] = $dv;
                $rtn["insql"] = "'".$dv."'";
                break;
            case "integer":
            case "bigint":
            case "tinyint":
                if (is_numeric($defv)) {
                    $rtn["value"] = round($defv * 1);
                    $rtn["insql"] = "".$rtn["value"];
                }
                break;
            case "float":
                if (is_numeric($defv)) {
                    $rtn["value"] = $defv * 1;
                    $rtn["insql"] = "".$rtn["value"];
                }
                break;
            //不支持的 原生类型
            default:
                return $rtn;
                break;
        }

        return $rtn;
    }



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
        if (is_null($val)) return $val;

        //读取当前字段 对应的 php 类型
        $conf = $this->conf("type");
        if (!Is::nemarr($conf) || !isset($conf["php"])) return $val;
        $ptp = $conf["php"];

        //类型转换  原生字段类型 对应的 php 类型包括：String | Integer | Float
        switch ($ptp) {
            case "String":
                if (is_array($val)) return Conv::a2j($val);
                if (is_numeric($val)) return (string)$val;
                if (is_bool($val)) return $val ? "是" : "否";
                if (is_string($val)) return $val;
                return "";
                break;
            
            case "Float":
                if (is_numeric($val)) return $val * 1;
                if (is_bool($val)) return $val ? 1 : 0;
                return 0;
                break;

            case "Integer":
                if (is_numeric($val)) return round($val * 1);
                if (is_bool($val)) return $val ? 1 : 0;
                return 0;
                break;
            
            default:
                return $val;
                break;
        }

        return $val;
    }

    /**
     * 将 在写入数据库之前，将 php 数据 转为 对应的 数据库字段保存类型的数据
     * @param Mixed $val php 数据
     * @return Mixed 对应的 数据库字段保存类型的数据
     */
    public function to($val=null)
    {
        if (is_null($val)) return $val;

        //读取当前指向的 Native 原生类型
        $ntp = static::$type;

        //类型转换
        switch ($ntp) {
            case "varchar":
            case "char":
            case "text":
                if (is_array($val)) return Conv::a2j($val);
                if (is_numeric($val)) return (string)$val;
                if (is_bool($val)) return $val ? "是" : "否";
                if (is_string($val)) return $val;
                return "";
                break;

            case "integer":
            case "bigint":
            case "tinyint":
                if (is_numeric($val)) return round($val * 1);
                if (is_bool($val)) return $val ? 1 : 0;
                return 0;
                break;

            case "float":
                if (is_numeric($val)) return $val * 1;
                if (is_bool($val)) return $val ? 1 : 0;
                return 0;
                break;

            //不支持的 原生类型
            default:
                return $val;
                break;
        }

        return $val;
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
        //直接调用 from 方法
        return $this->from($val);
    }



    /**
     * 原生字段类型 数据转换工具方法
     * !! 对外提供
     */

    /**
     * 入口方法
     * @param Mixed $val 要转换的数据
     * @param String $type 要转换为的 数据类型，可以是：
     *               php 可选类型：String | Integer | Float
     *               当前数据库支持的字段类型：VARCHAR
     * @return Mixed 转换后的符合目标类型的 数据
     */
    public static function conv($val, $type="String")
    {
        if (is_null($val)) return null;
        
        //大写字母开头的 作为 php 类型
        if (Str::beginUp($type)) {
            $tps = ["String", "Integer", "Float"];
            $m = "convPhp".$type;
            //不支持的 php 类型
            if (!in_array($type, $tps) || !method_exists(static::class, $m)) return $val;
            return static::$m($val);
        }

        //foo_bar 全小写类型，作为 Native 原生字段类型

    }

    //转换为 php String 类型
    public static function convToPhpString($val)
    {
        if (is_array($val)) return Conv::a2j($val);
        if (is_numeric($val)) return (string)$val;
        if (is_bool($val)) return $val ? "是" : "否";
        if (is_string($val)) return $val;
        return "";
    }
    //转换为 php Float 类型
    public static function convToPhpFloat($val)
    {
        if (is_numeric($val)) return $val * 1;
        if (is_bool($val)) return $val ? 1 : 0;
        return 0;
    }
    //转换为 php Integer 类型
    public static function convToPhpInteger($val)
    {
        if (is_numeric($val)) return round($val * 1);
        if (is_bool($val)) return $val ? 1 : 0;
        return 0;
    }

}