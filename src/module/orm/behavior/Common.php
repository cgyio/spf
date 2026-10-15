<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 数据模型通用的 Behavior 行为
 * 
 * 框架默认的 所有 SPF-Orm 数据模型 都必须包含的 行为 Behavior/Common 
 * 
 * !! 为 所有数据模型 增加 id 自增编号
 * 
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * !! 不同的应用可以在自身的 module/orm/behavior/ 路径下扩展不同的 Behavior 模型行为子类
 */

namespace Spf\module\orm\behavior;

use Spf\Env;
use Spf\App;
use Spf\exception\AppException;
use Spf\module\Orm;
use Spf\module\orm\Behavior;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Common extends Behavior 
{
    /**
     * !! 子类必须定义
     */
    //Behavior 名称
    protected static $behavior = "common";
    //此 behavior 定义的 字段组合
    protected static $columnSet = [
        //标示这些特殊字段将被 prepend or append 默认 append
        "append" => false,
        //要添加的 特殊字段
        "columns" => [
            //所有表都必须在开头有 id 字段，定义的参数将被合并到 数据模型参数中，然后交由 Parser 解析器统一解析
            "id" => [
                //字段元数据  [ 中文字段名, 字段说明, 前端表格组件中字段的显示宽度% ]
                "columns" => ["ID","自增序号",3],
                //字段的 creation-sql 使用 Types 定义的字段类型，约束字符使用任意支持的 数据库 Driver 形式都可以
                "creation" => "integer PRIMARY KEY AUTOINCREMENT",
                //字段的 特殊类型参数
                "column" => [
                    "includes" => ["id"],
                    "sort" => ["id"],
                    "filter" => ["id"]
                ]
            ],
        ],
    ];
    /**
     * 当前 Behavior 类扩展的 curd|filter|业务 方法列表
     * !! 收集并缓存，当此 Behavior 被多次引用时，不会重复收集
     * !! 子类必须定义这些属性，否则会在多个 Behavior 子类中相互影响
     */
    protected static $extended = [
        "curd" => [],   //方法名数组
        "filter" => [],
        "biz" => [],
    ];
    //标记 已经收集过
    protected static $extendIsCollected = false;
    //Behavior 子类实例缓存
    protected static $ins = null;
    /**
     * 当前 Behavior 类扩展的 Getter|Api 方法收集
     * !! 收集并缓存，当此 Behavior 被多次引用时，不会重复收集
     * !! 子类必须定义这些属性，否则会在多个 Behavior 子类中相互影响
     */
    //getters
    protected static $getters = [];
    //标记 getters 已经收集过
    protected static $getterIsCollected = false;
    //apis
    protected static $apis = [];
    //标记 apis 已经收集过
    protected static $apiIsCollected = false;



    //!! Behavior 扩展行为
    
    /**
     * getters 计算字段
     */



    /**
     * api 接口方法
     */



    /**
     * curd 系列方法
     * 在 curd 执行前，修改查询参数
     * !! 必须是 静态方法
     * @param Db $db 第一个参数一定是 数据库实例，可以通过 $db->curd 获取当前 curd 操作实例
     * @param Mixed[] $args 更多参数
     * @return Db 被修改后的 数据库实例，可以继续 链式执行 后续 curd 操作
     */
    //curdSortId(''asc|'desc')
    public static function curdSortId($db, $sort = "desc")
    {
        $sort = strtolower($sort)==="asc" ? "asc" : "desc";
        return $db->order("id", $sort);
    }
    public static function curdAsc($db) { return static::curdSortId($db, "asc"); }
    public static function curdDesc($db) { return static::curdSortId($db, "desc"); }



    /**
     * filter 系列方法
     * 在 curd 执行完成，得到 结果记录集后，在 RecordSet 实例上执行 二次筛选
     * !! 必须是 实例方法
     * !! $this->context 一定指向 当前的 RecordSet
     * @param Mixed[] $args 更多参数
     * @return RecordSet 返回值一定是 新的 RecordSet 记录集实例
     */



    /**
     * biz 系列方法
     * 在 Record 单记录实例 或 模型类 上执行业务操作
     * !! 必须是 实例方法
     * !! $this->context 可能指向 Record 实例  或  模型类全称，需要在方法内自行判断
     * @param Mixed[] $args 更多参数
     * @return Mixed 业务方法返回值不确定
     */



}