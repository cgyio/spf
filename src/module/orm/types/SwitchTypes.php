<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 Orm 模块支持的 特殊字段类型  switch
 */

namespace Spf\module\orm\types;

use Spf\module\orm\Types;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

//!! 无法使用 Switch 作为类名，添加 -Type后缀，在 collect 时，后缀会被去除
class SwitchTypes extends Types 
{
    /**
     * !! 必须指定的，覆盖父类
     */
    //此 字段类型的 名称 foo_bar
    protected static $type = "switch";
    //在 creation-sql 中 此字段类型的 语法正则
    protected static $pattern = [
        //!! 可以有多个 pattern 表示在 creation-sql 中可以使用多个别名，都指向此字段类型
        "/^switch\s+/",
        "/^boolean\s+/"
    ];
    //定义 此字段类型 在 不同数据库中的 类型映射
    protected static $map = [
        //定义 Orm 默认支持的 数据库类型 对应 映射类型
        "mysql" => "TINYINT",
        "sqlite" => "INTEGER",
    ];
    
    /**
     * 特殊字段类型参数，在数据模型配置参数 column 项下的 键名
     * 不指定，则使用 static::$type 作为键名
     * !! 可以有多个键名，依次从数据模型配置参数 column 项下查找
     */
    protected static $optProps = ["switch"];
    //如果是特殊字段类型，在此指定 在数据库配置文件中，此类型字段的 默认参数形式
    protected static $optDefine = [
        "true" => 1,
        "false" => 0
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
            "js"    => "Boolean",
            "php"   => "Boolean",
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
     *      "value" => php类型下的 默认值,
     *      "insql" => "可以写入 creation-sql 的默认值字符串",
     *  ]
     */
    protected static function parseDefaultValue($defv=null, $defc=null)
    {
        if (is_null($defv)) return null;
        if (!Is::nemarr($defc)) $defc = Arr::copy(static::$optDefine);

        //传入 String 类型的默认值定义
        if (Is::nemstr($defv)) {
            //去除可能存在的 '' ""
            $dv = Str::trimQuote($defv);
            $lowdv = strtolower($dv);
            if (
                !Is::nemstr($dv) || 
                (
                    !in_array($lowdv, ["true", "false"]) &&
                    !in_array($lowdv, [$defc["true"]."", $defc["false"].""])
                )
            ) {
                //默认值 默认是 false
                return [
                    "value" => false,
                    "insql" => $defc["false"]."",
                ];
            } else {
                $v = $lowdv==="true" || $lowdv===$defc["true"]."";
                return [
                    "value" => $v,
                    "insql" => $defc[$v ? "true" : "false"]."",
                ];
            }
        }

        //传入 Boolean 类型的 默认值定义
        if (is_bool($defv)) {
            return [
                "value" => $defv,
                "insql" => $defc[$defv ? "true" : "false"]."",
            ];
        }

        //传入 Int 类型的默认值定义
        if (is_int($defv)) {
            if (in_array($defv, [$defc["true"], $defc["false"]])) {
                return [
                    "value" => $defv===$defc["true"],
                    "insql" => $defv."",
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
        //字段参数
        $swc = $this->conf();
        //字段默认值
        $dft = $this->conf("default/value");
        //字段默认值
        if (!is_numeric($val)) return $dft;
        $val = $val*1;
        return $val === $swc["true"];
    }

    /**
     * 将 在写入数据库之前，将 php 数据 转为 对应的 数据库字段保存类型的数据
     * @param Mixed $val php 数据
     * @return Mixed 对应的 数据库字段保存类型的数据
     */
    public function to($val=null)
    {
        //字段参数
        $swc = $this->conf();
        //字段默认值
        $dft = $this->conf("default/value");
        //字段默认值
        if (!is_bool($val)) return $swc[$dft ? "true" : "false"];
        return $swc[$val ? "true" : "false"];
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
        //直接设置布尔值
        if (is_bool($val)) return $val;

        //!! 还可以设置 类型参数中定义的 opt["true | false"] 对应的数字 默认 true=1 false=0
        $opt = $this->opt();
        if (in_array($val, [$opt["true"], $opt["false"]])) return $val === $opt["true"];
        
        return $old;
    }
}