<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 数据模型通用的 Behavior 行为 Behavior/Uac
 * 
 * !! 为 数据模型 增加 权限控制相关能力
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

class Uac extends Behavior 
{
    /**
     * !! 子类必须定义
     */
    //Behavior 名称
    protected static $behavior = "uac";
    //此 behavior 定义的 字段组合
    protected static $columnSet = [
        "append" => true,
        "columns" => [
            "name" => [
                "columns" => ["账号","此账号登录系统的账号名称",4],
                "creation" => "varchar(50) NOT NULL",
                "column" => [
                    //索引
                    //"indexs" => [
                    //    "idx_name" => "(`name`)",
                    //],
                    "includes" => ["name"],
                    "search" => ["name"]
                ]
            ],
            "pwd" => [
                "columns" => ["密码","此账号登录系统的密码",3],
                "creation" => "varchar(32)",
                "front" => [
                    "table" => [
                        "hide" => ["pwd"]
                    ],
                ]
            ],
            "role" => [
                "columns" => ["角色","账号角色，赋予的操作权限",3],
                "creation" => "json NOT NULL DEFAULT '[]'",
                "column" => [
                    "filter" => ["role"],
                    "select" => [
                        "role" => [
                            "dynamic" => true,
                            "multiple" => true,
                            "source" => [
                                "table" => "role",
                                "value" => "key",
                                "label" => "name"
                            ]
                        ]
                    ],
                    "json" => [
                        "role" => "indexed"
                    ],
                ]
            ],
            "auth" => [
                "columns" => ["权限","除账号角色权限外，此账号还拥有的操作权限",3],
                "creation" => "json NOT NULL DEFAULT '[]'",
                "column" => [
                    "filter" => ["auth"],
                    "select" => [
                        "auth" => [
                            "dynamic" => true,
                            "multiple" => true,
                            "source" => [
                                "api" => "uac/authvalues",
                            ]
                        ]
                    ],
                    "json" => [
                        "auth" => "indexed"
                    ],
                ]
            ],
        ]
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