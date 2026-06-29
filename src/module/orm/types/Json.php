<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 Orm 模块支持的 特殊字段类型  json
 */

namespace Spf\module\orm\types;

use Spf\module\orm\Types;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

class Json extends Types 
{
    /**
     * !! 必须指定的，覆盖父类
     */
    //此 字段类型的 名称 foo_bar
    protected static $type = "json";
    //在 creation-sql 中 此字段类型的 语法正则
    protected static $pattern = "/^json\s+/";
    //定义 此字段类型 在 不同数据库中的 类型映射
    protected static $map = [
        //定义 Orm 默认支持的 数据库类型 对应 映射类型
        "mysql" => "VARCHAR(2000)",
        "sqlite" => "TEXT",
    ];
    
    /**
     * 特殊字段类型参数，在数据模型配置参数 column 项下的 键名
     * 不指定，则使用 static::$type 作为键名
     * !! 可以有多个键名，依次从数据模型配置参数 column 项下查找
     */
    protected static $optProps = ["json"];
    //如果是特殊字段类型，在此指定 在数据库配置文件中，此类型字段的 默认参数形式
    protected static $optDefine = [
        //区分 indexed|associate 格式的数组，对应 js 中的 array|object
        "type" => "associate",
    ];
    //当前特殊字段类型下，可选的 子类型，例如：json 类型的字段，可选的子类型包括 associate | indexed
    protected static $optDefineTypes = [
        //!! 特殊类型子类必须定义，默认子类型排在首位
        "associate", "indexed",
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
        $rtn = [
            "def"   => $p->type,
            "db"    => $p->maptype,
            "js"    => "Object",
            "php"   => "Array",
        ];

        $dv = $p->parsed["default"]["value"] ?? null;
        if (!is_null($dv) && Is::nemarr($dv)) {
            //如果有 默认值 根据 默认值类型 决定 js 类型
            $rtn["js"] = Is::indexed($dv) ? "Array" : "Object";
        } else {
            //如果没有 默认值 则根据 type 决定 js 类型
            $rtn["js"] = $p->parsed["type"]==="indexed" ? "Array" : "Object";
        }
        
        return $rtn;
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
        if (is_null($defv)) return null;

        if (!Is::nemarr($defc)) $defc = [];
        $deftp = $defc["type"] ?? static::$optDefineTypes[0];

        //传入 String 类型的默认值定义
        if (Is::nemstr($defv)) {
            //去除可能存在的 '' ""
            $dv = Str::trimQuote($defv);
            if (!Is::nemstr($dv) || !Is::json($dv)) {
                return [
                    "value" => [],
                    "insql" => $deftp==="associate" ? "'{}'" : "'[]'",
                ];
            } else {
                return [
                    "value" => Conv::j2a($dv),
                    "insql" => "'".$dv."'",
                ];
            }
        }

        //传入 Array 类型的默认值定义
        if (is_array($defv)) {
            if (
                !Is::nemarr($defv) || 
                ($deftp==="associate" && !Is::associate($defv)) ||
                ($deftp==="indexed" && !Is::indexed($defv))
            ) {
                return [
                    "value" => [],
                    "insql" => $deftp==="associate" ? "'{}'" : "'[]'",
                ];
            } else {
                return [
                    "value" => $defv,
                    "insql" => "'".Conv::a2j($defv)."'",
                ];
            }
        }

        //其他类型
        return null;
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
        if (!Is::json($val)) return [];

        //json 类型 indexed | associate
        $tp = $this->opt("type");
        $v = Conv::j2a($val);
        if (!Is::$tp($v)) return [];
        return $v;
    }

    /**
     * 将 在写入数据库之前，将 php 数据 转为 对应的 数据库字段保存类型的数据
     * @param Mixed $val php 数据
     * @return Mixed 对应的 数据库字段保存类型的数据
     */
    public function to($val=null)
    {
        //json 类型 indexed | associate
        $tp = $this->opt("type");
        if (empty($val) || !Is::$tp($val)) return $tp==="indexed" ? "[]" : "{}";

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
        //json 类型 indexed | associate
        $tp = $this->opt("type");

        if (Is::json($val)) return Conv::j2a($val);
        if (!is_array($val) || empty($val)) return [];
        if (!Is::$tp($val)) return [];
        return $val;
    }
}