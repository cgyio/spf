<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 Orm 模块支持的 特殊字段类型  uuid
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
use Spf\util\Uuid as utilUuid;

class Uuid extends Types 
{
    /**
     * !! 必须指定的，覆盖父类
     */
    //此 字段类型的 名称 foo_bar
    protected static $type = "uuid";
    //在 creation-sql 中 此字段类型的 语法正则
    protected static $pattern = "/^uuid\s+/";
    //定义 此字段类型 在 不同数据库中的 类型映射
    protected static $map = [
        //定义 Orm 默认支持的 数据库类型 对应 映射类型
        "mysql" => "VARCHAR(36)",
        "sqlite" => "TEXT",
    ];
    
    /**
     * 特殊字段类型参数，在数据模型配置参数 column 项下的 键名
     * 不指定，则使用 static::$type 作为键名
     * !! 可以有多个键名，依次从数据模型配置参数 column 项下查找
     */
    protected static $optProps = ["uuid"];
    //如果是特殊字段类型，在此指定 在数据库配置文件中，此类型字段的 默认参数形式
    protected static $optDefine = [
        "default" => [
            "value" => null,
            "params" => [
                "when" => ["insert"]
            ]
        ]
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
            "js"    => "String",
            "php"   => "String",
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
        //!! uuid 只能传入 getter 方法形式的 默认值，其他类型一律不支持
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
    //获取 UUIDv7
    public static function defaultV7Getter($colk, $conf=[])
    {
        return utilUuid::v7();
    }
    //获取 UUIDv4
    public static function defaultV4Getter($colk, $conf=[])
    {
        return utilUuid::v4();
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
        return $val;
    }

    /**
     * 将 在写入数据库之前，将 php 数据 转为 对应的 数据库字段保存类型的数据
     * @param Mixed $val php 数据
     * @return Mixed 对应的 数据库字段保存类型的数据
     */
    public function to($val=null)
    {
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
        //重新创建 uuid
        if ($val==="__v7__") return utilUuid::v7();
        if ($val==="__v4__") return utilUuid::v4();

        //传入 时间字符串
        if (Dater::isStr($val)) return utilUuid::v7(strtotime($val));
        
        //!! 其他情况 uuid 无法手动修改，返回 old 值
        return $old;
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
     * @name ts
     * @title UUID时间戳
     * @type Integer
     * @jstype Number
     * @param String $val 记录中的 UUID
     * @return Int|null 转换为对应时间戳 秒级
     */
    public function tsGetter($val)
    {
        if (utilUuid::isV7($val)!==true) return null;
        return utilUuid::v7Ts($val);
    }

    /**
     * getter
     * @name tstr
     * @title UUID时间戳
     * @type String
     * @jstype String
     * @param String $val 记录中的 UUID
     * @return String|null 转换为对应时间字符串 Y-m-d H:i:s
     */
    public function tstrGetter($val)
    {
        if (utilUuid::isV7($val)!==true) return null;
        $ts = utilUuid::v7Ts($val);
        return date("Y-m-d H:i:s", $ts);
    }
}