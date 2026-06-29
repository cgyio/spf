<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 Orm 模块支持的 特殊字段类型  iso 项目隔离ID
 * 
 * iso = isolate 
 * 当多个 项目|应用 共用一个数据库时，需要为每个 项目|应用 增加一个 iso 标记
 * 相应的，在所使用到的数据表中，每条记录都应包含 iso 字段，记录着各自不同的 项目隔离ID
 * 用来 标记某条数据记录 应属于哪一个 项目|应用
 * 
 * !! 标准的 iso 字符串格式： 项目名.应用名.子项目.更多层级...   例如：
 *      ms.qypms.stock              --> MS项目(总管理系统) > QYPMS应用(QY生产管理) > stock子项目(库存)
 * 
 * !! 项目隔离ID 是分层级的，上层(更笼统)的ID 将覆盖 下层(更细分)的ID，例如：
 *      某应用的 iso = ms.qypms     --> 此应用的记录条目将包含所有 iso = ms.qypms | ms.qypms.stock 的记录
 *      某应用的 iso = ms           --> 此应用的记录条目将包含所有 iso = ms | ms.qypms | ms.qypms.stock 的记录
 * !! 全局最上层(最高层级)的 iso 标记为 $ ，拥有此 iso 的 项目|应用 将包含(可以访问)所有数据记录
 * !! iso = $ 的意义通常是：这个应用可以管理系统中的所有数据，通常这是通用数据库系统底层的 直接管理数据的 应用
 *      
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

class Iso extends Types 
{
    /**
     * !! 必须指定的，覆盖父类
     */
    //此 字段类型的 名称 foo_bar
    protected static $type = "iso";
    //在 creation-sql 中 此字段类型的 语法正则
    protected static $pattern = "/^iso\s+/";
    //定义 此字段类型 在 不同数据库中的 类型映射
    protected static $map = [
        //定义 Orm 默认支持的 数据库类型 对应 映射类型
        "mysql" => "VARCHAR(32)",
        "sqlite" => "TEXT",
    ];
    
    /**
     * 特殊字段类型参数，在数据模型配置参数 column 项下的 键名
     * 不指定，则使用 static::$type 作为键名
     * !! 可以有多个键名，依次从数据模型配置参数 column 项下查找
     */
    protected static $optProps = ["iso"];
    //如果是特殊字段类型，在此指定 在数据库配置文件中，此类型字段的 默认参数形式
    protected static $optDefine = [
        
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
        //!! iso 类型字段一律不支持 默认值
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
    //public static function defaultV7Getter($colk, $conf=[])



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
        $val = (string)$val;
        if (!static::isLegalIso($val)) return null;
        return $val;
    }

    /**
     * 将 在写入数据库之前，将 php 数据 转为 对应的 数据库字段保存类型的数据
     * @param Mixed $val php 数据
     * @return Mixed 对应的 数据库字段保存类型的数据
     */
    public function to($val=null)
    {
        return $this->from($val);
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
        if (Is::nemstr($val)) {
            //直接指定 iso 字符串 不能以 . 开头或结尾
            $val = trim($val, ".");
        } else if (is_array($val)) {
            //也可以指定 iso 字符串序列，指定 [] 空数组则转为 $
            $val = static::queToIso($val);
        } else {
            //!! 其他情况 iso 无法手动修改，返回 old 值
            return $old;
        }

        //!! 如果无法转换为有效的 iso 字符串，返回 old 值
        if (!static::isLegalIso($val)) return $old;

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
     * @name que
     * @title ISO序列
     * @type Array
     * @jstype Array
     * @param String $val 记录中的 iso
     * @return Array|null 转换为 iso 层级序列  
     */
    public function queGetter($val)
    {
        return static::isoToQue($val);
    }

    
    
    /**
     * !! 可外部调用的 iso 工具
     */

    /**
     * 判断是否有效的 iso 字符串
     * @param Mixed $iso
     * @return Bool
     */
    public static function isLegalIso($iso)
    {
        if (!Is::nemstr($iso)) return false;
        return preg_match("/^[a-zA-Z_$][a-zA-Z0-9_]{0,}(\.[a-zA-Z_][a-zA-Z0-9_]{0,}){0,}$/", $iso)===1;
    }

    /**
     * iso 字符串 foo.bar.jaz --> [foo, bar, jaz]
     * @param String $iso
     * @return Array|null 返回 iso 序列数组，不是有效的 iso 字符串则返回 null
     */
    public static function isoToQue($iso)
    {
        if (!static::isLegalIso($iso)) return null;
        if ($iso==="$") return [];
        return explode(".", $iso);
    }

    /**
     * iso 序列转为 字符串
     * @param Array $isoa 必须是 indexed 数组，可以是 [] 空数组
     * @return String|null 自动排除数组中的 非字符串 元素，用 . 拼接，并检查是否有效 iso 字符串
     */
    public static function queToIso($isoa=[])
    {
        if (!is_array($isoa) || Is::nemaso($isoa)) return null;
        //空数组 转为 $
        if (empty($isoa)) return "$";
        //排除非 字符串 元素
        $isoa = array_filter($isoa, function($isoi) {
            return Is::nemstr($isoi);
        });
        $isoa = array_merge([], $isoa);
        $iso = implode(".", $isoa);
        //检查 iso 字符串是否有效
        if (!static::isLegalIso($iso)) return null;
        return $iso;
    }

    /**
     * 根据传入的 某项目的 iso  生成此项目可以访问的 数据记录的 iso 字段的可选值，例如：
     * 针对项目 iso = ms.qypms 则此项目可以访问的 数据记录的 iso 字段可选值为：
     *      ["ms.qypms", "ms.qypms.%"]
     * !! 如果项目 iso = $ 则返回 [] 空数组，表示可以访问所有数据
     * @param String $iso
     * @return Array|null 如果传入的 iso 无效，则返回 null
     */
    public static function isoToRecordIsoValues($iso)
    {
        if (!static::isLegalIso($iso)) return null;
        if ($iso==="$") return [];
        return [$iso, $iso.".%"];
    }

    /**
     * 根据传入的 某项目的 iso  生成可以操作此项目数据的所有 account 账号的 iso 字段的可选值，例如：
     * 针对项目 iso = ms.qypms 则 可以操作此项目数据记录的 account 账号的 iso 字段可选值为：
     *      ["$", "ms", "ms.qypms"]
     * !! 如果项目 iso = $ 则返回 ["$"] ，表示 只有 iso = $ 的账号才可以操作此项目的数据
     * @param String $iso
     * @return Array|null 如果传入的 iso 无效，则返回 null
     */
    public static function isoToAccountIsoValues($iso)
    {
        if (!static::isLegalIso($iso)) return null;
        if ($iso==="$") return ["$"];
        //拆分 iso 序列
        $isoa = static::isoToQue($iso);
        $vals = ["$"];
        for ($i=1;$i<=count($isoa);$i++) {
            $ch = array_slice($isoa, 0, $i);
            $vals[] = static::queToIso($ch);
        }
        return $vals;
    }
}