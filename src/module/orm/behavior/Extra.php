<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 数据模型通用的 Behavior 行为
 * 
 * 框架默认的 所有 SPF-Orm 数据模型 都必须包含的 行为 Behavior/Extra
 * 
 * !! 为 所有数据模型 增加 info(备注), extra(更多信息), enable(生效状态), disat(失效时间戳)
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

class Extra extends Behavior 
{
    /**
     * !! 子类必须定义
     */
    //Behavior 名称
    protected static $behavior = "extra";
    //此 behavior 定义的 字段组合
    protected static $columnSet = [
        //必须在结尾
        "append" => true,
        "columns" => [
            "info" => [
                "columns" => ["备注","此记录的备注",3],
                "creation" => "varchar",
                "column" => [
                    "includes" => ["info"],
                    "search" => ["info"],
                ]
            ],
            "extra" => [
                "columns" => ["更多","此记录的更多数据",5],
                "creation" => "json NOT NULL DEFAULT '{}'",
                "column" => [
                    "includes" => ["extra"],
                    "search" => ["extra"],
                    "json" => [
                        "extra" => "associate"
                    ]
                ]
            ],
            "enable" => [
                "columns" => ["生效","此记录是否生效(逻辑删除标记)",3],
                "creation" => "switch NOT NULL DEFAULT 1",
                "column" => [
                    //索引
                    //"indexs" => [
                    //    "idx_enable" => "(`enable`)",
                    //],
                    "includes" => ["enable"],
                    "filter" => ["enable"],
                    "switch" => ["enable"],
                ]
            ],
            "disat" => [
                "columns" => ["失效时间","此记录的失效时间",5],
                "creation" => "datetime",
                "column" => [
                    "sort" => ["disat"],
                    "filter" => ["disat"],
                    "datetime" => [
                        "disat" => [
                            "type" => "datetime",
                            "default" => [
                                "value" => "now",
                                "params" => [
                                    "when" => ["logic_delete"]
                                ],
                            ]
                        ]
                    ]
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
     * !! 必须是 实例方法 public
     * !! 调用时，必须提供 Record 实例作为上下文
     */

    /**
     * getter
     * @name is_empty_extra
     * @title 额外信息为空
     * @type Boolean
     * @jstype Boolean
     * @return Bool|null
     */
    public function isEmptyExtraGetter()
    {
        //!! json 类型字段会通过 Types 类型系统自动转换为 Array 类型
        $extra = $this->context->extra;
        //如果本次查询不含 extra 字段，则返回 null
        if (is_null($extra)) return null;
        return Is::nemarr($extra) !== true;
    }



    /**
     * api 接口方法
     * !! 可以是 实例方法 或 静态方法，必须是 public
     * !! 实例方法接口，调用时 需要传入 Record|RecordSet 作为上下文
     * !! 静态方法接口，调用时 需要传入 模型类全称 作为上下文
     */

    /**
     * api
     * @name test
     * @title ExtraBehavior 测试接口
     * @auth false
     * @pause false
     * @param Array $args 更多 url 参数
     * @return Array
     */
    public function testApi(...$args)
    {
        var_dump(static::$behavior);
        var_dump("testApi");
        var_dump($this->context);
    }

    /**
     * api
     * @name test_stc
     * @title ExtraBehavior 静态测试接口
     * @auth false
     * @pause false
     * @param String $model 必须传入 模型类全称
     * @param Array $args 更多 url 参数
     * @return Array
     */
    public static function testStcApi($model, ...$args)
    {
        var_dump(static::$behavior);
        var_dump("testStcApi");
        var_dump($model);
    }



    /**
     * curd 系列方法
     * 在 curd 执行前，修改查询参数
     * !! 必须是 静态方法
     * @param Db $db 第一个参数一定是 数据库实例引用，可以通过 $db->curd 获取当前 curd 操作实例
     * @param Mixed[] $args 更多参数
     * @return Db 被修改后的 数据库实例，可以继续 链式执行 后续 curd 操作
     */
    //curdEnable(true|false)
    public static function curdEnable($db, ...$args)
    {
        $enable = (isset($args[0]) && is_bool($args[0])) ? $args[0] : true;
        $val = $enable ? 1 : 0;
        return $db->where(["enable" => $val]);
    }
    //curdExtra(["key"=>"val", ... ])
    public static function curdExtra($db, ...$args)
    {
        return $db->whereJson("extra", ...$args);
    }



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