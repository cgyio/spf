<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型配置参数 预处理类 
 * 
 * 框架自带的 BizPreparer 基础业务预处理类
 */

namespace Spf\module\orm\config\model\preparer;

use Spf\module\orm\config\model\Preparer;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

class Biz extends Preparer 
{
    /**
     * 当前 Preparer 子类定义的 特殊字段
     * !! 子类必须指定
     * 结构与 Preparer::$specialColumnsCollection[] 一致
     */
    protected static $specialColumns = [
        //资质相关字段
        "lisence" => [
            "append" => true,
            "columns" => [
                "bzname" => [
                    "columns" => ["全称","此主体在资质文件中的全称",5],
                    "creation" => "varchar(50)",
                    "column" => [
                        "includes" => ["bzname"],
                        "search" => ["bzname"]
                    ]
                ],
                "bztel" => [
                    "columns" => ["联系电话","此主体的联系电话",4],
                    "creation" => "varchar(20)",
                    "column" => [
                        "search" => ["bztel"]
                    ]
                ],
                "lisence" => [
                    "columns" => ["许可证","此主体的资质许可证号",4],
                    "creation" => "varchar(20)",
                    "column" => [
                        "includes" => ["lisence"],
                        "search" => ["lisence"]
                    ]
                ],
                "bzinfo" => [
                    "columns" => ["资质详情","此主体的资质信息详情记录",3],
                    "creation" => "json NOT NULL DEFAULT '{}'",
                    "column" => [
                        "search" => ["bzinfo"],
                        "json" => [
                            "bzinfo" => [
                                "type" => "associate",
                                "default" => [
                                    "公司全称" => "",
                                    "公司电话" => "",
                                    "公司地址" => "",
                                    "法人姓名" => "",
                                    "法人证件" => "",
                                    "法人电话" => "",
                                    "营业执照" => "",
                                    "经营范围" => "",
                                    "许可证号" => "",
                                    "许可范围" => ""
                                ]
                            ]
                        ],
                    ]
                ],
                "bzlogo" => [
                    "columns" => ["Logo","此主体的Logo文件",4],
                    "creation" => "json NOT NULL DEFAULT '[]'",
                    "column" => [
                        "file" => [
                            "bzlogo" => [
                                "uploadTo" => "__assets_files__/logos",
                                "accept" => "image/*",
                            ]
                        ],
                        "json" => [
                            "bzlogo" => "indexed"
                        ],
                    ]
                ],
                "bzbrand" => [
                    "columns" => ["拥有品牌","此主体注册的品牌商标",4],
                    "creation" => "json NOT NULL DEFAULT '[]'",
                    "column" => [
                        "filter" => ["bzbrand"],
                        "search" => ["bzbrand"],
                        "json" => [
                            "bzbrand" => "indexed"
                        ],
                    ]
                ],
                "bzverify" => [
                    "columns" => ["核验状态","此主体资质文件的核验状态",3],
                    "creation" => "switch NOT NULL DEFAULT 0",
                    "column" => [
                        "filter" => ["bzverify"],
                        "switch" => ["bzverify"]
                    ]
                ],
                "bzvrlog" => [
                    "columns" => ["核验记录","此主体资质文件的核验记录",4],
                    "creation" => "json NOT NULL DEFAULT '{}'",
                    "column" => [
                        "search" => ["bzvrlog"],
                        "json" => [
                            "bzvrlog" => "associate"
                        ],
                    ]
                ],
            ],
        ],

        //规格字段
        "package" => [
            "append" => true,
            "columns" => [
                "unit" => [
                    "columns" => ["单位","此品种的计量单位，散装货品选「克」",3],
                    "creation" => "varchar(10) NOT NULL DEFAULT '克'",
                    "column" => [
                        "includes" => ["unit"],
                        /*"package" => [
                            "columns" => ["unit","netwt","maxunit","minnum","midunit"],
                            "intunit" => true,
                            "dig" => 2
                        ]*/
                    ]
                ],
                "netwt" => [
                    "columns" => ["净含量","此品种的计量单位重量「克」，散装货品填 1",4],
                    "creation" => "float NOT NULL DEFAULT 1",
                    "column" => [
                        "includes" => ["netwt"],
                        "number" => [
                            "netwt" => [
                                "precision" => 2,
                                "step" => 0.01
                            ]
                        ]
                    ]
                ],
                "maxunit" => [
                    "columns" => ["外包装单位","此品种的外包装单位，散装货品选「无」",3],
                    "creation" => "varchar(10) NOT NULL DEFAULT '无'",
                    "column" => [
                        "includes" => ["maxunit"],
                    ]
                ],
                "minnum" => [
                    "columns" => ["小包装数","此品种的每个外包装包含小包装个数，散装货品填 1",3],
                    "creation" => "integer NOT NULL DEFAULT 1",
                    "column" => [
                        "includes" => ["minnum"],
                        "number" => [
                            "minnum" => [
                                "precision" => 0,
                                "step" => 1
                            ]
                        ]
                    ]
                ],
                "midunit" => [
                    "columns" => ["中间包装","此品种的中间包装规格，可选，可以有多个，从小到大排列",3],
                    "creation" => "json NOT NULL DEFAULT '[]'",
                    "column" => [
                        "includes" => ["midunit"],
                        "json" => [
                            "midunit" => "indexed"
                        ]
                    ]
                ],
            ]
        ],
        
    ];
}