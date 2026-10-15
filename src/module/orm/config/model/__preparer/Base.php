<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型配置参数 预处理类 
 * 
 * 框架自带的 BasePreparer 基础预处理类
 */

namespace Spf\module\orm\config\model\preparer;

use Spf\module\orm\config\model\Preparer;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

class Base extends Preparer 
{
    /**
     * 当前 Preparer 子类定义的 特殊字段
     * !! 子类必须指定
     * 结构与 Preparer::$specialColumnsCollection[] 一致
     */
    protected static $specialColumns = [
        //记录创建者字段
        "creator" => [
            "append" => true,
            "columns" => [
                "creator" => [
                    "columns" => ["创建人","此记录的创建人",3],
                    "creation" => "char(10) NOT NULL",
                    "join" => [
                        "[>]usr" => [
                            "creator" => "uid"
                        ]
                    ],
                    "column" => [
                        "includes" => ["creator"],
                        "filter" => ["creator"]
                    ]
                ],
                "addtime" => [
                    "columns" => ["创建时间","此记录的创建时间",5],
                    "creation" => "datetime NOT NULL DEFAULT 'now'",
                    "column" => [
                        "time" => [
                            "addtime" => [
                                "type" => "datetime",
                                //"default" => "now"
                            ]
                        ]
                    ]
                ],
                "modlog" => [
                    "columns" => ["修订记录","此记录的修订日志",4],
                    "creation" => "json NOT NULL DEFAULT '{}'",
                    "column" => [
                        "json" => [
                            "modlog" => "associate"
                        ]
                    ]
                ],
                "modtime" => [
                    "columns" => ["最新修订","此记录的最新修订时间",5],
                    "creation" => "datetime",
                    "column" => [
                        "time" => [
                            "modtime" => [
                                "type" => "datetime",
                                "default" => [
                                    "value" => "now",
                                    "params" => [
                                        "when" => ["update"]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ],

        //记录状态字段
        "status" => [
            "append" => true,
            "columns" => [
                "status" => [
                    "columns" => ["状态","此记录的当前状态",3],
                    "creation" => "varchar",
                    "column" => [
                        //索引
                        //"indexs" => [
                        //    "idx_status" => "(`status`)",
                        //],
                        "includes" => ["status"],
                    ]
                ],
                "stnum" => [
                    "columns" => ["状态码","此记录的当前状态编码，可通过比较大小来确定状态的先后关系",3],
                    "creation" => "integer NOT NULL DEFAULT 0",
                    "column" => [
                        //索引
                        //"indexs" => [
                        //    "idx_stnum" => "(`stnum`)",
                        //],
                        "includes" => ["stnum"],
                        /*"highlight" => [
                            "stnum" => [
    
                            ]
                        ]*/
                    ]
                ],
                "stlog" => [
                    "columns" => ["状态记录","此记录的状态变更日志，记录变更的时间节点以及相关人员",5],
                    "creation" => "json NOT NULL DEFAULT '{}'",
                    "column" => [
                        "includes" => ["stlog"],
                        "json" => [
                            "stlog" => "associate"
                        ]
                    ]
                ],
            ]
        ],

        //UAC字段
        "uac" => [
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
        ],
        
    ];
}