<?php
/**
 * 框架 Orm 模块
 * 数据库配置器
 */

namespace Spf\module\orm\config;

use Spf\config\Configer;
use Spf\Env;
use Spf\App;
use Spf\module\Orm;
use Spf\module\orm\OrmException;
use Spf\module\orm\Db;
use Spf\module\orm\Driver;
use Spf\module\orm\Model;
use Spf\module\orm\config\model\Preparer;
use Spf\module\orm\config\model\Parser;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Cache;
use Spf\util\Operation;

class DbConfig extends Configer 
{
    /**
     * 预设的设置参数
     * !! 子类自定义，将覆盖 $dftInit
     * 
     * 用于自定义某些数据库的特殊配置项目
     */
    protected $init = [];

    /**
     * 框架通用的 Db 数据库配置项目
     * !! 数据库配置文件中的项目 应基于此数据结构
     * !! 可以使用 %{...}% 模板字符，可用的模板字符 在 module\orm\ModuleOrmConfig::processConf() 方法中查看
     */
    protected $dftInit = [
        /**
         * 此数据库的类型，可选：mysql|sqlite|external|自定义类型
         * !! 必须在 spf/module/orm/driver 路径下定义的所有驱动类型： Mysql|Sqlite|External|自定义类型
         * !! 可以扩展数据库类型，驱动类必须定义在 下列路径下：
         * !!       webroot/app/[app_name]/library/db/driver/..         --> 仅针对当前 App 应用
         * !!       webroot/library/db/driver/..                        --> 可针对当前 host 下的多个 App 应用
         * !!       vendor/cgyio/spf/src/module/orm/driver/..           --> 针对此服务器中所有使用 Spf 框架的应用
         */
        "driver" => "mysql",
        /**
         * 根据 driver 数据库类型，定义对应的 数据库连接参数
         * !! 不同类型的数据库连接参数格式 在 对应的 驱动类 中定义 Driver::$dftConnect[] 
         */
        "connect" => [
            /**
             * mysql 数据库连接参数：
             * host
             * port: 3306
             * database
             * username
             * password
             * charset: utf8mb4
             * collation: utf8mb4_general_ci
             * 
             * sqlite 数据库连接参数：
             * database 数据库文件路径
             * 
             * external 外部数据库连接参数：
             * host
             * database
             * username
             * password
             * interface[]
             * 
             * 其他数据库类型参数
             * ...
             */
        ],

        /**
         * 数据库元数据
         */
        "name"  => "",  //通常使用 %{DBN}% 字符串模板，可以在使用时动态指定
        "title" => "",  //数据库中文名称，***库
        "desc"  => "",  //数据库说明，用途、注意、等

        /**
         * 除了 数据库连接参数、元数据 之外的其他配置参数项目，可以通过 mixin 方式混入 其他数据库的配置
         * !! 不允许 mixin 混入的配置项目 在 $this->mixinConfExcepts[] 中定义
         * !! 如果被 mixin 的数据库配置中 也定义了 mixin 参数，则递归 mixin
         * !! 被 mixin 的数据库配置参数中的 %{***}% 模板将被替换为当前数据库的 tpls 数据源
         * !! 可以同时 mixin 多个数据库配置，按顺序，后指定的覆盖先指定的
         * 此处指定 要 mixin 的 数据库配置文件路径，Path::find() 识别
         * !! 如果被 mixin 的其他数据库配置文件的 后缀名 与 当前数据库配置文件的后缀名一致，则可以省略
         */
        "mixin" => [],

        /**
         * 此数据库初始化时，必须立即创建实例的 数据模型(表)
         * !! 如果此处未定义，则只有在 $db->model(model_name) 时，才会实例化数据模型
         */
        "required" => [
            //model_name_1, model_name_2, ...
        ],

        /**
         * 数据库中包含的 数据模型(表)
         */
        "model" => [
            /**
             * 所有数据模型(表) 通用的 参数，作为默认值，将被具体的数据模型参数覆盖
             */
            "_default_" => [
                //!! 与 $stdModel 数据结构一致
                //!! 可使用 %{DBN}% 代替数据库名，%{MDN}% 代替数据模型(表)名
                "name" => "%{MDK}%",    //foo_bar 形式
                "class" => "%{MODPRE}%/%{MDN}%",
            ],

            /**
             * 分别定义各数据模型(表)的 配置参数
             */
            //"model_name" => [
                //!! 与 $stdModel 数据结构一致
                //!! 可以使用 %{...}% 模板字符，可用的模板字符 在 module\orm\ModuleOrmConfig::processConf() 方法中查看
            //],
            //...
        ],

        /**
         * 可以自定义 数据模型(表) 配置参数解析器  以及 解析顺序
         * !! Parser::exec() 将使用此处定义的 解析器[] 列表中 定义的 解析器和解析顺序
         * !! 自定义 数据模型参数解析器 的方法，见 module/orm/config/model/Parser 基类中的注释
         * 默认不指定，表示 使用 Parser 默认的解析器 和 解析顺序
         */
        "parser" => [
            //"column", "自定义解析器名 foo_bar", ...
        ],
    ];

    /**
     * 如果 数据库配置参数定义了 mixin 混入参数 要混入其他数据库配置参数内容
     * !! 此处定义不允许混入的 数据库配置项目
     */
    protected $mixinDbConfExcepts = [
        //不允许混入 数据库连接参数
        "driver", "connect",
        //不允许混入 数据库元数据
        "name", "title", "desc",
    ];

    /**
     * 定义 数据模型(表) 参数标准数据结构
     * !! 数据库配置文件中的 数据模型(表)参数项目 应基于此数据结构
     */
    protected $stdModel = [
        /**
         * 数据模型(表) 元数据
         */
        "name" => "",   //应与外部的 键名一致，foo_bar 形式
        "title" => "",  //数据模型(表)中文名称，***表
        "desc" => "",   //数据模型(表)说明，用途等

        /**
         * 数据模型类路径，可被 Cls::find() 识别
         * 例如：model/foo_app/%{DBN}%/%{MDN}%  --> NS\model\foo_app\dbn\ModelName
         * !! 不指定则根据数据库配置文件路径，自动查找 数据模型类
         * 例如：数据库配置文件路径 app/foo_app/library/db/db_foo.json 则：
         *      默认的 数据模型类路径：    model/foo_app/db_foo/...
         */
        "class" => "",

        /**
         * 数据模型(表) 参数预处理
         * 参数结构与 module/orm/config/Preparer::$dftOption[] 一致
         */
        "prepare" => [
            "class" => "",
            "extends" => "",
            "columns" => [],
            "idx" => [
                "unique" => [],
                "normal" => [],
                "multiple" => [],
            ],
        ],

        /**
         * 数据模型(表) 字段元数据
         */
        "columns" => [
            //"字段名 foo_bar" => [ "中文名", "字段介绍", 显示宽度(1-10) ],
        ],

        /**
         * 数据模型(表) 字段 creation SQL
         * !! 采用 sqlite 语法
         * !! 由 数据库驱动 自动兼容
         */
        "creation" => [
            //"字段名 foo_bar" => "varchar AUTOINCREMENT PRIMARY KEY NOT NULL DEFAULT 1",
        ],

        /**
         * 关联表(数据模型) 参数
         */
        "join" => [
            /**
             * 不同形式的 join 关联
             *      [>|<|<>|><]表名 => "字段名"  或  [ "此表中字段名" => "关联表中字段名" ]
             * 
             * "[>]关联表名" => "主表关联表使用的相同字段名",
             * "[>]关联表名" => [
             *     "主表字段" => "关联表字段",
             *     "AND" => [
             *          可以带筛选条件
             *     ],
             * ]
             */
        ],

        /**
         * 定义 特殊类型的字段参数
         */
        "column" => [

            /**
             * 此数据模型(表) 中定义的 索引字段
             * !! 这些参数 将由 Preparer 预处理类 根据 prepare["idx"] 参数 自动生成
             */
            // normal|multiple 普通索引|复合索引  将通过 CREATE INDEX 语句显式定义
            "indexs" => [
                //"idx_***" => "(`字段名`)",            # 普通索引
                //"idx_***" => "(`字段a`, `字段b`)",    # 复合索引
            ],
            // unique 唯一索引  在 字段的 creation 语句中 增加 UNIQUE 后缀 隐式定义
            "unique" => [
                //"字段名", "...", ...
            ],

            

            /**
             * 特殊类型字段
             * !! 特殊类型字段 必须在指定位置定义处理类，例如框架默认提供的 特殊字段类型处理类：
             *      module/orm/config/model/column/..
             *          IncludesColumn 
             *          SortColumn 
             *          SearchColumn
             *          ...
             * !! 可以在 App 应用路径下，扩展 特殊字段类型，解析类保存在：
             *      app/app_name/db/config/model/column/.. 路径下，类名为：FooBarColumn
             * !! 也可以为 整站多个 App 应用 扩展特殊字段类型，解析类保存在：
             *      webroot/db/config/model/column/.. 路径下
             * !! 扩展的 特殊字段类型，必须继承自 module/orm/config/model/SpecialColumn 基类
             */

            //必须字段，每次查询都必须包含的 字段名
            "includes" => [],

            //可用于 搜索|筛选|排序 的字段
            "sort" => [],
            "search" => [],
            "filter" => [],

            //需要数据模型自行创建的 特殊自增字段，例如：员工号|产品编码|库存编码
            //!! 需要在模型类中自定义 colNameGenerator() 生成方法
            "generator" => [
                //字段名, 字段名, ...
            ],
            //时间日期字段
            "time" => [
                /**
                 * !! 可以是 纯字段名数组
                 * 默认 时间参数：
                 *      type = datetime
                 *      default = ''
                 */

                /*
                # 也可以完整定义 时间参数
                "col_name" => [
                    # 时间日期类型
                    "type" => " date|datetime|date-range|datetime-range ",
                    # 初始值
                    "default" => " now|today|week|month|season|year "  或  timestamp  或  [timestamp, timestamp]
                ],
                */
            ],
            //数值字段
            "number" => [
                /**
                 * !! 可以是 纯字段名数组
                 * 默认 数值参数：
                 *      如果字段类型是 float 则：
                 *          precision = 4
                 *          step = 0.0001
                 * 
                 *      如果字段类型是 integer 则：
                 *          precision = 0
                 *          step = 1
                 */

                /*
                # 也可以完整定义 数值参数
                "col_name" => [
                    # 数值精度 几位小数
                    "precision" => 4,
                    # 自动增减时的 步长，需要与 精度 匹配
                    "step" => 0.0001,
                ],
                */
            ],
            //金额字段  默认 float 类型，精度 4
            "money" => [
                /**
                 * !! 可以是 纯字段名数组
                 * 默认 金额参数：
                 *      如果字段类型是 float 则：
                 *          precision = 4
                 *          step = 0.0001
                 * 
                 *      如果字段类型是 integer 则：
                 *          precision = 0
                 *          step = 1
                 *      
                 *      货币符号
                 *      sign = "￥"
                 */
                 
                /*
                # 也可以完整定义 金额参数
                "col_name" => [
                    # 数值精度 几位小数
                    "precision" => 4,
                    # 自动增减时的 步长，需要与 精度 匹配
                    "step" => 0.0001,
                    # 货币符号
                    "sign" => "￥",
                ],
                */

                //!! 金额字段会自动添加到 number 数值字段列表中，因为金额字段一定是数值字段
            ],
            //开关字段 integer 类型，0 = false   1 = true
            "switch" => [
                //字段名, 字段名, ...
            ],
            //json 字段
            "json" => [
                //简易定义，此时 默认值为 []|{}
                //"col_name" => "indexed | associate",  # []|{}  

                //完整定义
                /*
                "col_name" => [
                    "type" => "indexed",
                    "default" => [ 1,2,3,... ],
                ],
                "col_name" => [
                    "type" => "associate",
                    "default" => [
                        "foo" => 1,
                        "bar" => "jaz"
                    ],
                ],
                */
            ],
            //select 下拉选择字段
            "select" => [
                /*
                "col_name" => [
                    # 选择列表是否动态生成
                    "dynamic" => true,
                    # 是否支持多选，如果是多选，则此字段将被添加到 json 字段列表，indexed 类型
                    "multiple" => false,
                    # 是否允许手动输入 新数据
                    "allow-create" => false,
                    # 是否级联选择，如果是，则此字段将被添加到 json 字段列表，indexed 类型
                    "cascade" => false,

                    #数据源参数
                    "source" => [
                        # dynamic === false 提供静态备选数据
                        [
                            label: '',
                            value: '',
                            items: [
                                [
                                    label: '',
                                    value: '',
                                    items: [ ... ],
                                ],
                                ...
                            ],
                        ],
                        ... 更多静态备选数据

                        # 来自其他数据表
                        "db" => "",         # 支持跨库查询
                        "table" => "",      # 关联表
                        "label" => "",      # 用于显示的 关联表字段名(计算字段名)，不指定则使用 value 字段名
                        "value" => "",      # 关联的目标表字段，不指定则使用当前字段名
                        # 支持添加关联表的筛选条件，只引用部分关联表数据
                        "where" => [
                            # 有效的 Medoo 查询 where 参数形式
                        ],

                        # 或者 来自某个 api 接口，此接口必须返回 数组 [ [label:'', value:''], [], ... ]
                        !! 如果是级联选择，必须使用接口，级联选项接口可以接收一个 current[] 参数，指代当前已经选中的选项
                        !! 级联接口根据传入的 current[] 当前已选择内容，返回 选项数组：
                        !!  [
                        !!      [
                        !!          label: '',
                        !!          value: '',
                        !!          items: [
                        !!              label: '',
                        !!              value: '',
                        !!              items: [...],
                        !!          ],
                        !!      ],
                        !!      ...
                        !!  ]
                        "api" => "",
                    ],
                ],
                */
            ],
        ],

        /**
         * 定义前端显示参数
         */
        "front" => [
            //定义前端 table 组件在显示 此表(数据模型) 时的特殊参数
            "table" => [
                //在 table 中隐藏的字段
                "hide" => [],
                /**
                 * 定义前端显示时的 table-mode 显示模式
                 */
                "modes" => [
                    //默认显示模式
                    "default" => [
                        //显示模式名称
                        "title" => "默认模式",
                        //显示所有 未被 hide 的字段，可指定需要显示的字段 []
                        "columns" => "*",
                        //是否经典表格模式
                        "isClassic" => true,
                        //isClassic !== true 非经典表格模式下，记录行可指定特定的 前端 vue 组件名，组件 props 必须包含 record{}
                        "vue" => "",    //组件名必须在前端已存在
                    ],
                    //
                    /*
                    # 可以增加其他显示模式
                    "mini" => [
                        "title" => "极简模式",
                        "columns" => [ "col_1", "col_2", "getter_1", ... ],
                        "isClassic" => true,
                    ],
                    ...
                    */
                ],
            ],
            
            //定义前端 form 组件在显示 此数据模型(表) 时的特殊参数
            "form" => [
                //在 form 中隐藏的字段
                "hide" => [],
                /**
                 * 定义字段验证器
                 * 前端 form 组件必须定义对应的 validator 方法
                 */
                "validator" => [
                    /*
                    # form 组件将在提交前自动调用 form.validator[validatorFunctionName](input-value)
                    "col_name" => "validatorFunctionName",
                    */
                ],

                //!! 可以额外定义各字段 在 form 中的 inputer 组件的其他参数
                "inputer" => [
                    /*
                    "col_name" => [
                        # 此处定义的 inputer 组件参数，将会作为 props 传入 前端组件中
                    ],
                    */
                ],
            ],
        ],
    ];

    /**
     * 定义 数据库配置文件 解析后产出的 最终数据库参数结构
     * !! 此数据最终将被写入 $db->config->context 供外部使用
     * !! 此处产出的数据库配置数据  将被传到前端，并被前端缓存，作为前端渲染的规则数据源
     */
    protected $exportDbConf = [
        //基础数据
        "class" => "",      //数据库类全称
        "driver" => "",
        "connect" => [],    //解析后得到的 正确的有效的连接参数
        "name" => "",
        "title" => "",
        "desc" => "",

        //数据模型类中 定义的 apis 接口
        "oprs" => [
            //标准的 操作数组，与 util\Operation::$stdOprs[] 结构一致
            //!! 这些操作，会在 Orm 初始化时，被添加到当前应用的 操作列表中，参与路由匹配
            //"apis" => [ 操作标识1, ... ],
            //"操作标识1" => [ ... ],
            //...
        ],

        //!! 数据库全局唯一 key 将在 将在 Orm 启动时，为所有数据库自动生成
        //"key" => "",

        //必须的 数据模型 列表，这些数据模型，将会在 数据库实例化之后立即初始化
        "required" => [
            //model_name_1, model_name_2, ...
        ],

        //解析生成 所有可用的 数据模型(表) 列表
        "models" => [
            //model_name_1, model_name_2, ...
        ],

        //解析生成 各 数据模型(表) 的最终产出参数
        "model" => [
            /*
            "model_name" => [
                !! 与 $exportModelConf 数据结构一致
            ],
            ...
            */
        ],
    ];

    /**
     * 定义 数据库配置文件 解析后产出的 最终数据模型(表)参数结构
     * !! 此数据填充到 $db->config->context["model"][model_name] 中
     */
    protected $exportModelConf = [
        //基础数据
        "name" => "",
        "title" => "",
        "desc" => "",
        //真实有效的 数据模型类 全称
        "class" => "",

        //解析生成 此数据模型(表) 完整的 creation SQL
        //!! 包含所有自动补全的 通用字段
        "creation" => [
            /*
            "col_name" => "varchar NOT NULL DEFAULT ''",
            ...
            */
        ],
        //索引
        // normal|multiple 普通索引|复合索引  将通过 CREATE INDEX 语句显式定义
        "indexs" => [
            //"idx_***" => "(`字段名`)",            # 普通索引
            //"idx_***" => "(`字段a`, `字段b`)",    # 复合索引
        ],
        // unique 唯一索引  在 字段的 creation 语句中 增加 UNIQUE 后缀 隐式定义
        //!! 转移到 special["unique"] 中保存
        //"unique" => [
            //"字段名", "...", ...
        //],

        //解析生成 此数据模型(表) 的 字段默认值
        "default" => [],

        //解析生成 此数据模型(表) 关联查询参数
        "join" => [
            //配置文件中的 原始参数
            "param" => [],
            //join 参数是否有效
            "availabel" => false,
            //是否每次查询都默认启用 join 关联表查询
            "use" => false,
            //关联表 表名 数组，全小写
            "tables" => [],
            //有关联表的 字段参数
            "column" => [
                /*
                "col_name" => [
                    "table_name" => [
                        "linkto" => "关联表中字段名"
                        "relate" => ">|<|<>|>< == left|right|full|inner join"
                    ],
                ]
                */
            ],
        ],

        //解析生成 此数据模型(表) 中包含的所有 字段，
        //!! 包含各类型 自动生成|手动定义 的 计算字段 getters
        "columns" => [
            //col_name_1, col_name_2, ...
        ],

        //各种特殊类型字段的 字段名 数组
        "special" => [
            //"includes" => [ 字段名, ... ],
            //"sort" => [ ... ],
            //...
        ],

        //解析生成 各 数据模型(表)字段 的最终产出参数
        "column" => [
            /*
            "col_name" => [
                !! 与 $exportColumnConf 数据结构一致
            ],
            ...
            */
        ],

        //此数据模型对外提供的 api 操作接口列表
        "oprs" => [
            //与 util\Operation::$stdOprs[] 结构一致
            //!! 这些操作，会在 Orm 初始化时，被添加到当前应用的 操作列表中，参与路由匹配
            //"apis" => [ 操作标识1, ... ],
            //"操作标识1" => [ ... ],
            //...
        ],

        //!! 如果 Orm 配置参数 dbs 中此数据库定义了 prefilter，则将其拆分到各 model 的参数中
        "prefilter" => [
            /*
            !! 与 Medoo 的 where 参数格式一致，使用 AND 合并到最终的查新条件中
            "colk[~]" => "keywords",
            ...
            */
        ], 

        //解析生成 此数据模型(表) 的前端参数
        "front" => [
            "table" => [
                //在表中隐藏的 字段
                "hide" => [],
                //表模式
                "mode" => [
                    //默认模式
                    "default" => [
                        "title" => "默认模式",
                    "columns" => [/* 默认显示的 字段列表 */],
                        "isClassic" => true,
                        "vue" => "",
                    ],
                ],
            ],
            //表单
            "form" => [
                //在表单中 隐藏的 字段
                "hide" => [],
            ],
        ],

    ];

    /**
     * 定义 数据库配置文件 解析后产出的 最终 单字段参数结构
     * !! 此数据填充到 $db->config->context["model"][model_name]["column"][column_name] 中
     */
    protected $exportColumnConf = [
        //基础数据
        "name" => "",
        "title" => "",
        "desc" => "",

        //字段类型
        "type" => [
            "db" => "varchar",  //!! 使用 sqlite 支持的 数据类型 varchar|integer|float|...  
            "js" => "String",   //用于前端显示的 字段数据类型  String|Boolean|Int|Float|Array|Object
            "php" => "String",  //用于后端处理时的 字段数据类型，String|Boolean|Number|Array...
        ],

        //creation-sql
        "creation" => "",
        "creationParams" => [
            "type" => "",       //经过处理的 符合 driver 的 字段类型定义，含精度()
            "binds" => [],      //经过处理的 静态约束 数组
            "default" => "",    //默认值定义字符串，默认为空，不定义默认值
            "extra" => [],      //经过处理的 额外约束 数组
        ],

        //字段默认值
        "default" => [
            "value" => null,
            "params" => [
                "getter" => null,
                "when" => null
            ],
        ],

        //!! 是否 计算字段 
        "isGetter" => false,

        //特殊类型判断 isXxxx
        "isPk"          => false,   //是否主键
        "isId"          => false,   //是否自增主键
        "isRequired"    => false,   //是否必填字段
        "isIncludes"    => false,   //是否必查字段
        "isGenerator"   => false,   //是否自定义编号
        //"isTime"        => false,   //是否时间日期字段
        //"isNumber"      => false,   //是否数值字段
        //"isMoney"       => false,   //是否金额字段
        "isSwitch"      => false,   //是否开关字段
        "isJson"        => false,   //是否 json 字段
        "isSelect"      => false,   //是否下拉选择字段
        "isCascade"     => false,   //是否级联选择下拉菜单
        "isSort"        => false,   //是否可排序字段
        "isSearch"      => false,   //是否可搜索字段
        "isFilter"      => false,   //是否可筛选字段

        //特殊类型参数，及其默认值
        //!! 当 此字段不是此特殊类型时，对应的参数项会被移除
        /*"time" => [
            "type" => "datetime",
            "default" => "",
        ],
        "number" => [
            "precision" => 4,
            "step" => 0.0001,
        ],
        "money" => [
            "precision" => 4,
            "step" => 0.0001,
            "sign" => "￥",
        ],
        "json" => [
            "type" => "indexed",
            "default" => [],
        ],
        "select" => [
            "dynamic" => true,
            "multiple" => false,
            "allow-create" => false,
            "cascade" => false,
            "source" => [
                "db" => "",
                "table" => "",
                "label" => "",
                "value" => "",
                "where" => [],
                "api" => "",
            ],
        ],*/

        //前端参数
        "width"         => 3,       //在 table 组件中显示时的 默认列宽度
        "hideInTable"   => false,   //是否在 table 中隐藏
        "hideInForm"    => false,   //是否在 form 中隐藏
        "hasValidator"  => false,   //是否需要前端验证输入
        "validator"     => null,    //前端验证函数名
        
        /**
         * 前端 form 组件中此字段的 inputer 组件的 参数
         * !! 需要在 $orm->config->ctx["frontComponentLib"] 中指定前端渲染的 SPF-Vcom 基础组件库
         * !! Orm 模块 在 module/orm/ui/ 路径下必须定义对应的 解析类   例如：
         * !!   $orm->config->ctx["frontComponentLib"] === "spf" 则必须定义解析类：Spf\module\orm\ui\Spf
         */
        "inputer" => [
            "type" => "",       //!! 组件名称，根据字段特殊类型，以及字段数据类型自动选择 inputer 组件名，
            "props" => [        //组件 props
                //将会根据字段的特殊类型，自动合并对应 props
                //!! 例如 isTime === true 将会自动合并 time 参数 到 inputer 组件 props
            ],
        ],

    ];

    //构造时传入的 当前数据库名 foo_bar
    public $dbn = "";

    /**
     * 数据库参数解析中间体
     * 经过 extendConf 方法处理的 完整的 数据库初始参数
     * !! 由有需要的 Parser 解析器调用
     * 结构与 $dftInit 一致
     */
    //public $_temp = [];

    /**
     * 当前数据库在本次请求中的 前置查询条件
     * !! 前置查询条件与 数据库配置参数本身无关联，仅针对本次请求，因此不需要被写入 context 并被缓存
     * !! 因此每次请求时，应单独保存针对此数据库的 前置查询条件
     * 外部通过 $orm->db_name->config->prefilter 直接访问即可
     */
    public $prefilter = [];


    /**
     * 构造
     * !! 覆盖父类
     * @param Array $opt 输入的参数 来自 $orm->config->ctx["dbs"][dbn] 
     *              数据结构在 ModuleOrmConfig::$stdDbConf 中定义
     * @param Core $ins 传入当前数据库的 名称 foo_bar
     * @return void
     */
    public function __construct($opt=[], $ins=null)
    {
        //缓存数据库名
        $this->dbn = $ins;

        //数据库配置参数初始化时，App 以及 Orm 核心类必须已经实例化
        if (App::$isInsed!==true || Orm::$isInsed!==true) {
            //必要的核心类未准备好，报异常
            throw new OrmException("$ins,依赖的核心类 (App 或 Orm) 还未初始化", "orm/config");
            return null;
        }

        //处理外部传入的 Orm 配置参数中的 当前数据库参数，合并 $dftInit 默认值后，作为下一步解析的 原始配置数据
        $opt = $this->fixOpt($opt);

        //保存 原始的 数据库配置文件 内容
        $this->opt = Arr::extend($this->opt, $opt);

        //读取配置缓存  或  解析数据库配置文件
        if ($this->opt["cache"]!==false) {
            $conf = $this->readConfCache();
            //缓存读取正常
            if (Is::nemarr($conf)) {
                //写入 context
                $this->context = $conf;

                //处理可能存在的 prefilter 定义，拆分到各 model 参数中
                $this->setPrefilterToModel();
                
                return $this;
            }
        }

        /**
         * 未能正确获取缓存，或未启用缓存，则：
         * 读取数据库配置文件，替换模板，合并默认值，得到 待解析的数据库初始配置数据
         * 调用 Parser 解析器 解析 所有定义的 数据模型(表)参数
         * 将最终得到的 完整的数据库参数 写入 $this->context
         */
        $this->extendConf();

        //某些数据库可能需要 进一步处理配置参数，此处调用钩子方法
        $this->processConf();

        //!! 不论是否启用缓存，一律更新缓存
        $this->saveConfCache();

        //处理可能存在的 prefilter 定义，拆分到各 model 参数中
        $this->setPrefilterToModel();

        return $this;
    }

    /**
     * 在初始化时，处理外部传入的 Orm 配置参数中的 此数据库的配置参数
     * !! 覆盖父类
     * @param Array $opt 外部传入的 Orm 配置参数中的 此数据库的配置参数
     * @return Array 处理后的 数据库配置参数，数据结构与 $ModuleOrmConfig::$stdDbConf[] 中的定义一致：
     *  [
     *      "config" => "数据库配置文件真实路径",
     *      "key" => "数据库全局唯一 key",      # 仅与数据库配置文件物理路径相关，因此同一个配置文件表示同一个数据库
     *      "tpls" => [ ... 替换配置文件中 %{***}% 模板的数据源 ... ],
     * 
     *      # 数据库路径相关参数，如果这些参数未指定，则默认根据数据库配置文件解析得到
     *      "dbroot" => "数据库相关文件的文件夹",
     *      "modpre" => "数据模型类路径前缀 model 或 app/appk/model",
     *      "cache" => "数据库配置参数的缓存文件路径",                  
     * 
     *      # 其他参数将作为通用值，覆盖到此数据库的配置文件内容中 在 $this->dftInit 中定义了参数项目
     *      "common" => [
     *          "config" => "数据库配置文件路径添加到通用参数中",
     *          "driver" => "mysql",
     *          "connect" => [],
     *          "name" => "",
     *          ...
     *      ],
     *  ]
     */
    protected function fixOpt($opt=[])
    {
        //!! 首先处理本次请求时，针对此数据库的 前置查询条件
        if (Is::nemarr($opt["prefilter"])) {
            //直接缓存到 此配置类实例
            $this->prefilter = $opt["prefilter"];
            unset($opt["prefilter"]);
        }

        //准备处理后的 数据库配置参数内容
        $dbc = [
            "common" => [],
        ];

        //特殊 配置参数键
        $ks = explode(",", "config,key,tpls,dbroot,modpre,cache");
        foreach ($ks as $ki) {
            if (!isset($opt[$ki])) continue;
            $dbc[$ki] = $opt[$ki];
            //特殊配置参数，也作为 common 通用值
            $dbc["common"][$ki] = $opt[$ki];
            unset($opt[$ki]);
        }

        //剩余的配置参数作为 common 通用参数
        if (Is::nemarr($opt)) {
            $dbc["common"] = array_merge($dbc["common"], $opt);
        }

        //返回合并后的 数据库配置参数原始值
        return $dbc;
    }

    /**
     * 读取数据库配置文件，替换模板，合并默认值
     * 调用 Parser 解析器 解析 所有定义的 数据模型(表)参数
     * 将最终得到的 完整的数据库参数 写入 $this->context
     * !! 覆盖父类
     * @return $this
     */
    public function extendConf()
    {
        //处理后的 Orm 配置参数中的 此数据库的参数
        $opt = $this->opt;
        //通用参数
        $common = $opt["common"] ?? [];
        //获取 数据库配置文件内容
        $conf = $this->readConfFile();
        //排除无效配置文件
        if (!Is::nemarr($conf) || !Is::associate($conf)) {
            //配置文件无效，报异常
            throw new OrmException($this->dbn.",数据库配置文件无效", "orm/config");
            return $this;
        }

        //!! 严格按顺序合并各层级的 数据库配置参数 
        $temp = Arr::extend(
            //要合并到的 目标[]
            [],
            //默认参数
            $this->dftInit,
            //配置文件中的参数
            $conf,
            //Orm 配置参数中此数据库的 通用参数
            $common,
        );

        //!! 检查数据库配置参数的有效性
        $temp = $this->checkDbConf($temp);

        //!! 在调用 Parser 解析器之前，先执行一些预处理
        //合并 默认模型参数 以及 模型类中定义的 $customConf[]  到每一个模型配置参数中
        $temp = $this->combineModelConf($temp);
        //处理 所有数据模型的 prepare 预处理参数，将预处理后的参数作为最终被 Parser 解析的 每个数据模型的参数
        $temp = $this->prepareModelConf($temp);

        //!! 调用 Parser 解析器 解析 所有数据模型(表) 配置参数
        $ctx = $this->parseModelConf($temp);
        if (!Is::nemarr($ctx) || !Is::associate($ctx)) {
            //配置参数未能被正确解析，报异常
            throw new OrmException($this->dbn.",数据库配置参数未能正确解析", "orm/config");
            return $this;
        }

        //将 最终得到的 完整 数据库参数 写入 $this->context 结构与 $this->exportDbConf[] 一致
        $this->context = $ctx;

        return $this;
    }

    /**
     * getContext 获取 context 数据
     * 指定 $data 的值，则变为 setContext 运行时修改 context 数据
     * !! 覆盖父类，当运行时修改 context 时，自动 saveConfCache
     * @param String $key context 字段 或 字段 path： 
     *      foo | foo/bar  -->  context["foo"] | context["foo"]["bar"]
     * @param Mixed $data 可以指定新值，覆盖旧的设置值，默认 __empty__ 标识未指定
     * @return Mixed 
     *      不指定 $data 则返回找到的 数据，未找到则返回 null
     *      指定了 $data 则尝试修改 context 返回是否修改成功的 Bool 值
     */
    public function ctx($key = "", $data="__empty__")
    {
        //先调用父类方法
        $rtn = parent::ctx($key, $data);

        //如果是修改，则自动缓存
        if ($data!=="__empty__" && is_bool($rtn)) {
            //!! 自动缓存
            $this->saveConfCache();
        }

        //返回
        return $rtn;
    }



    /**
     * 扩展的 DbConfig 专用配置方法
     */

    /**
     * 检查 数据配置参数，过滤处理 无效项目
     *  0   检查 class 是否指向了有效的 Db 类
     *  1   检查 driver 以及 connect 参数，无效则报异常
     *  2   收集 数据库类中定义的 apis 生成 标准操作信息数组
     * @param Array $conf 处理前的 数据库配置参数
     * @return Array 返回处理后 一定有效的 数据库配置参数
     */
    protected function checkDbConf($conf=[])
    {
        if (Orm::$isInsed!==true) {
            //!! 解析数据库配置时，Orm 模块必须已实例化
            throw new OrmException($this->dbn.",Orm 模块实例还未创建", "orm/config");
            return $conf;
        }

        // 0    检查 class 是否指向了有效的 Db 类
        //!! 不需要判断，因为已经过 ModuleOrmConfig 处理，一定会存在 数据库类
        
        // 1   检查 driver 以及 connect 参数，无效则报异常
        $driver = $conf["driver"] ?? null;
        $connect = $conf["connect"] ?? [];
        //未指定 driver 或 connect 参数
        if (!Is::nemstr($driver) || !Is::nemarr($connect)) {
            //报异常
            throw new OrmException($this->dbn.",未指定数据库类型或连接参数", "orm/config");
            return $conf;
        }
        //查找 数据库驱动类
        $cls = Driver::support($driver);
        if ($cls===false) {
            //数据库驱动未定义，报异常
            throw new OrmException($this->dbn.",不支持的数据库类型 ".$driver, "orm/config");
            return $conf;
        }
        //检查 connect 参数 与 driver 是否匹配
        if ($cls::ensureConnectOption($connect)!==true) {
            //connect 参数不匹配，报异常
            throw new OrmException($this->dbn.",无效的 $driver 数据库连接参数", "orm/config");
            return $conf;
        }
        //一切正常，替换 driver 参数为 数据库驱动类全称
        $conf["driver"] = $cls;

        // 2    收集 数据库类中定义的 apis 生成 标准操作信息数组
        $orm = Orm::$current;
        //Orm 模块，访问数据库操作接口的统一的 路由前缀，在 $orm->config->ctx["dbRoutePrefix"] 中定义，默认 db
        $pre = $orm->config->ctx["dbRoutePrefix"];
        //开始收集 数据库操作列表，保存到 $conf["oprs"] 中
        $conf["oprs"] = Operation::oprs(
            $conf["class"],
            //数据库中指定的 操作接口 一定是 api 类型
            "api",
            //必须是 public 且 非静态方法
            "public,&!static",
            //手动指定 操作名称 oprn 前缀
            null,
            //手动指定 操作说明的 前缀
            null,
            //额外处理
            function($oprc) use ($pre, $conf) {
                //api 原名 foo_bar
                $an = $oprc["name"];
                //增加 dbn_ 前缀
                $nn = $this->dbn."_".$an;
                
                //手动修改这些操作的 参数信息
                $oprc = Arr::extend($oprc, [
                    //这些操作 统一指向 $orm->responseProxyer 方法
                    "class"     => Orm::class,
                    "method"    => "responseProxyer",
                    //$orm->responseProxyer 一定是 实例方法
                    "isStatic"  => false,
                    //!! 修改操作标识
                    "oprn"      => "api/db/".$this->dbn.":".$an,
                    //!! 修改自动创建的 route 路由正则
                    "route"     => "/".$pre."\/".$this->dbn."\/".$an."(\.*)/",
                    //修改参数
                    "name"      => $nn,
                    "title"     => $conf["title"]."：".$oprc["title"],
                    "desc"      => $conf["title"]."：".array_slice(explode("：", $oprc["desc"]), -1)[0],
                    //额外参数
                    "dbn"       => $this->dbn,
                    "modk"      => null,
                    //在 $orm->responseProxyer 方法中，需要调用的实际方法，需要标记 isStatic 
                    "proxy"     => [
                        "class"     => $oprc["class"],
                        "method"    => $oprc["method"],
                        //数据库操作 必须是 非静态 方法
                        "isStatic"  => false,
                    ]
                ]);

                return $oprc;
            }
        );

        
        return $conf;
    }

    /**
     * 处理数据库配置参数中的 model 数据模型(表)参数
     *  0   合并 默认数据模型(表) 参数到每一个定义的数据模型(表)中
     *  1   合并 每个数据模型(表) 类中自定义的 配置参数 Model::$customConf[] 
     *  2   处理 required 必须初始加载的 数据模型(表) 名称数组
     * 返回处理后的数据库配置参数
     * @param Array $conf 合并之前的数据库配置参数 []
     * @return Array 返回合并之后的 数据库配置参数 []
     */
    protected function combineModelConf($conf=[])
    {
        if (
            !Is::nemarr($conf) || !Is::associate($conf) || 
            !isset($conf["model"]) || !Is::nemarr($conf["model"])
        ) {
            return $conf;
        }
        
        //默认 数据模型(表) 名称
        $dftn = "_default_";
        $modc = $conf["model"];
        //获取 默认数据模型(表) 参数
        $dft = $modc[$dftn] ?? [];
        if (isset($modc[$dftn])) unset($modc[$dftn]);

        //如果没有定义任何 数据模型(表)，报异常
        if (!Is::nemarr($modc)) {
            throw new OrmException($this->dbn.",数据库没有定义任何数据模型", "orm/config");
            return null;
        }

        //生成 models 数据模型(表) 列表
        $conf["models"] = [];   //array_merge([], array_keys($modc));

        //处理每个 数据模型(表)的 配置参数
        $std = $this->stdModel;
        //准备处理后的 数据模型(表)的 配置参数
        $nmodc = [];
        //依次处理定义的 每个 数据模型(表)
        foreach ($modc as $modn => $modi) {
            //模型名称 转为 foo_bar 形式
            $modk = Str::snake($modn, "_");
            //模型类名 FooBar 形式
            $modn = Str::camel($modk, true);
            //必须定义 数据模型(表) 参数 []
            if (!Is::nemarr($modi) || !Is::associate($modi)) {
                //报异常
                throw new OrmException($this->dbn.",数据表 ".$modn." 没有定义任何有效参数", "orm/config");
                continue;
            }

            //扩展 %{...}% 模板数据源
            $tpls = [
                "mdk" => $modk,
                "mdn" => $modn,
            ];

            //!! 严格按顺序合并 $std $dft 到 $modi
            $nmodci = Arr::extend([], $std, $dft, $modi);
            //!! 替换 此模型参数中的 %{MDK}% %{MDN}% 等 模板字符
            $nmodci = $this->fixConfTpls($nmodci, $tpls);

            //class 项目必须存在，不存在则自动生成
            if (!Is::nemstr($nmodci["class"])) $nmodci["class"] = $this->opt["modpre"]."/".$modn;
            //模型类必须存在
            $modclsi = Cls::find($nmodci["class"]);
            if (!Is::nemstr($modclsi) || !class_exists($modclsi)) {
                //如果 指定的数据模型(表)类不存在，则使用默认的 Spf\module\orm\Model 类 此类一定存在
                $modclsi = Cls::find("module/orm/Model", "Spf");
            }
            //类全称写入 class 参数
            $nmodci["class"] = $modclsi;

            //!! 合并 此模型类中 自定义的 $customConf[] 到此模型的 配置参数中
            $custom = $modclsi::$customConf ?? [];
            if (Is::nemarr($custom) && Is::associate($custom)) {
                //替换 %{...}%
                $custom = $this->fixConfTpls($custom, $tpls);
                //合并到 $nmodci[]
                $nmodci = Arr::extend($nmodci, $custom);
            }

            //字段元数据 中的字段名列表 []
            $colmetas = array_merge([], array_keys($nmodci["columns"]));
            //字段 creation 数据 中的字段名列表 []
            $colsqls = array_merge([], array_keys($nmodci["creation"]));
            //!! 元数据 和 creation sql 必须一一对应
            if (count($colmetas)!==count($colsqls) || !empty(array_diff($colmetas, $colsqls))) {
                //报异常
                throw new OrmException($this->dbn.",数据表 ".$modn." 字段元数据与 creation-sql 不能完整对应", "orm/config");
                continue;
            }

            //配置参数有效，写入处理后的 []
            $nmodc[$modk] = $nmodci;
            //数据模型(表) 名 foo_bar 写入 models[]
            $conf["models"][] = $modk;
        }
        //写回 $conf[]
        $conf["model"] = $nmodc;
        

        //处理 required 数据模型列表，去除无效的数据模型
        if (Is::nemarr($conf["required"])) {
            //去除不在 models[] 中的 模型名
            $conf["required"] = array_merge(
                [],
                array_diff(
                    $conf["required"],
                    array_diff($conf["required"], $conf["models"])
                )
            );
        }

        return $conf;
    }

    /**
     * 处理数据库配置参数中的 model 数据模型(表)参数，执行每个数据模型(表)参数中定义的 prepare 预处理操作
     * 返回预处理后的数据库配置参数
     * @param Array $conf 预处理之前的数据库配置参数 []
     * @return Array 返回预处理之后的 数据库配置参数 []
     */
    protected function prepareModelConf($conf=[])
    {
        //定义的 model 数据模型 []
        $models = $conf["models"] ?? [];
        //每个数据模型的 定义参数
        $modc = $conf["model"] ?? [];
        if (!Is::nemarr($models) || !Is::nemarr($modc)) return $conf;
        //依次处理每个 数据模型(表)的 prepare 参数
        foreach ($modc as $modk => $modi) {
            //prepare 参数不存在时，自动补全
            if (!isset($modi["prepare"]) || !Is::associate($modi["prepare"])) $modi["prepare"] = []; 
            //!! 自动处理 extends 继承的 其他数据模型的 配置文件后缀名
            if (isset($modi["prepare"]["extends"]) && Is::nemstr($modi["prepare"]["extends"])) {
                //调用 $orm->config->autoSuffixDbConf() 方法
                $modi["prepare"]["extends"] = Orm::$current->config->autoSuffixDbConf($modi["prepare"]["extends"]);
            }
            //调用 Preparer 预处理类处理 prepare 参数，生成新的 数据模型(表)参数
            $conf["model"][$modk] = Preparer::prepare($modk, $modi);
        }
        return $conf;
    }

    /**
     * 依次对每个 数据模型(表) 调用 Parser 配置解析器，解析配置参数
     * 生成最终的 数据模型(表) 的参数结构，与 $this->exportModelConf[] 一致
     * 最终生成的 完整的 数据库参数，结构与 $exportDbConf[] 一致，将被写入 $this->context
     * @param Array $conf 待解析的 完整数据库配置参数
     * @return Array 返回 最终的数据库参数，结构与 $exportDbConf[] 一致，将被写入 $this->context
     */
    protected function parseModelConf($conf=[])
    {
        if (!Is::nemarr($conf)) {
            //没有传入有效的 数据库配置，报异常
            throw new OrmException($this->dbn.",数据库配置参数为空，无法调用数据模型解析器", "orm/config");
            return [];
        }

        //缓存 待解析的 数据库参数，由 有需要的 Parser 调用
        //$this->_temp = $conf;

        //使用当前的 数据库配置参数 作为 待解析数据
        $temp = $conf;
        //自定义的 Parser 模型参数解析器和解析顺序
        $parsers = $temp["parser"] ?? [];
        //准备 最终产出 context 的数据结构 与 $exportDbConf[] 一致
        $ctx = $this->stdExport(
            //默认结构
            "exportDbConf",
            //合并后的 数据配置文件中的参数
            $temp,
            //去除一些不需要的 参数项
            [
                "parser" => "__delete__",
            ]
        );
        //清除 model[] 由 Parser 解析填充
        $ctx["model"] = [];

        //!! 临时写入 context 用于在 各 Parser 内部访问已解析的 数据库参数
        $this->context = $ctx;

        //准备 Parser 解析器解析结果 []
        $modcs = [];

        //所有 数据模型(表) 名称数组
        $modks = $temp["models"] ?? [];
        if (!Is::nemarr($modks) || !Is::indexed($modks)) {
            //模型列表为空，报异常
            throw new OrmException($this->dbn.",数据库没有定义任何数据模型", "orm/config");
            return [];
        }
        //待 解析的 数据模型参数数组
        $tmodcs = $temp["model"];

        //依次对每个 数据模型(表) 调用 Parser 配置解析器
        foreach ($modks as $modk) {
            //跳过错误的  或  已经解析过的
            if (isset($modcs[$modk]) || !isset($tmodcs[$modk])) continue;
            //等待解析的 数据模型参数
            $tmodc = $tmodcs[$modk];
            
            //!! 调用 Parser 解析器 解析模型配置参数
            $modc = Parser::exec($modk, $tmodc, $this, $parsers);
            //如果 未能解析得到有效的 数据模型(表) 参数，报异常
            if (!Is::nemarr($modc) || !Is::associate($modc)) {
                //模型列表为空，报异常
                throw new OrmException($this->dbn.",解析数据表 ".$this->dbn."/".$modk." 的参数时发生错误", "orm/config");
                //!! 所有 数据模型 都必须被正确解析，不能跳过
                return [];
            }

            //!! 调用每个模型类中 可能定义的 ModelClass::finalParse() 方法，最后处理模型参数
            $modcls = $modc["class"];
            if (
                //如果当前模型使用的模型类是 默认基类，则不执行
                is_subclass_of($modcls, Model::class) &&
                $modcls::$isConfed!==true && 
                method_exists($modcls, "finalParse")
            ) {
                $modc = $modcls::finalParse($modc);
                //!! 标记 $isConfed 
                $modcls::$isConfed = true;
            }
            
            //写入 解析得到的 最终的 数据模型(表) 参数
            $modcs[$modk] = $modc;
        }

        //解析得到的 数据模型(表) 最终参数，写入 context
        $ctx["model"] = $modcs;

        return $ctx;
    }

    /**
     * 如果 Orm 参数的 dbs 项下，此数据库存在 prefilter 参数，在此将其筛分到各 model 模型的参数中
     * !! 此操作在 缓存写入后执行，因为 prefilter 只针对当前请求，不是数据库固有参数，不应缓存
     * !! 因此，即使是从缓存中读取数据库配置，也应该在读取后执行此方法
     * @return $this
     */
    protected function setPrefilterToModel()
    {
        $pref = $this->prefilter;
        if (!Is::nemaso($pref)) return $this;
        //依次处理每个定义了 prefilter 的 model
        foreach ($pref as $tbn => $prefc) {
            if (!Is::nemaso($prefc)) continue;
            //处理 modk fooBar --> foo_bar
            $modk = Str::snake($tbn, "_");
            if (!isset($this->context["model"][$modk])) continue;
            //写入 model 模型参数中
            $this->context["model"][$modk]["prefilter"] = $prefc;
        }
        return $this;
    }



    /**
     * 工具方法
     */

    /**
     * 读取 传入的$cf 或 $this->opt["config"] 中定义的 数据库配置文件 的内容
     * 根据 配置文件的后缀名，解析得到 配置内容
     * !! 如果配置文件中定义了 mixins 混入参数，则依次读取并合并要混入的 其他数据库配置文件内容
     * !! 如果要混入的 其他数据库配置文件中 也定义了 mixins 参数，则递归混入
     * 统一替换 %{...}% 模板字符串，返回最终处理后的 数据库配置参数内容 []
     * @param String $cf 不指定则读取 $this->opt["config"] 中的文件路径
     * @return Array 读取并解析得到的 数据库配置文件的 内容 []
     */
    protected function readConfFile($cf=null)
    {
        if (!Is::nemstr($cf)) {
            //处理后的 Orm 配置参数中的 此数据库的参数
            $opt = $this->opt;
            //配置文件路径，已经过参数检查，此处一定存在
            $cf = $opt["config"];
        } else {
            //为传入的 配置文件路径自动添加后缀名
            $cf = Orm::$current->config->autoSuffixDbConf($cf);
            //查找文件
            $cp = Path::find($cf, Path::FIND_FILE);
            //如果指定发配置文件路径不存在，直接返回 [] 空内容
            if (!file_exists($cp)) return [];
            $cf = $cp;
        }

        //数据库配置文件 后缀名
        $cext = Path::ext($cf);

        //读取文件类内容
        $cfstr = file_get_contents($cf);

        //根据 配置文件类型，解析配置内容
        $conf = [];
        switch ($cext) {
            //.json
            case ".json" :
                $conf = Conv::j2a($cfstr);
                break;
            //TODO：兼容其他类型配置文件：xml|yaml|...
        }

        //判断是否获取到了 有效的配置参数内容
        if (!Is::nemarr($conf) || !Is::associate($conf)) return [];

        //处理 mixins 混入其他数据库配置文件
        if (isset($conf["mixin"])) {
            if (Is::nemidx($conf["mixin"])) {
                //不允许混入的 配置参数项目
                $eks = $this->mixinDbConfExcepts;
                //为这些项目增加 __delete__ 标记，这些标记在使用 Arr::extend() 合并时会从原数组中删除
                $exo = [];
                foreach ($eks as $eki) {$exo[$eki] = "__delete__";}
                //依次 递归混入，后指定的 覆盖 先指定的 其他数据库配置内容
                $mixins = [];
                foreach ($conf["mixin"] as $mixinCf) {
                    //递归读取 配置文件内容
                    $mixinConf = $this->readConfFile($mixinCf);
                    //跳过未能正确读取的 待混入配置文件
                    if (!Is::nemarr($mixinConf) || !Is::associate($mixinConf)) continue;
                    //去除不允许混入的 配置项
                    $mixinConf = Arr::extend($mixinConf, $exo);
                    //后指定的 覆盖 先指定的
                    $mixins = Arr::extend($mixins, $mixinConf);
                }
                //执行混入
                if (Is::nemarr($mixins) && Is::associate($mixins)) {
                    $conf = Arr::extend($mixins, $conf);
                }
            }
            //去除 mixin 参数
            unset($conf["mixin"]);
        }
        
        //替换配置文件中的 %{***}% 字符串模板
        $conf = $this->fixConfTpls($conf);

        //返回解析得到的 []
        return $conf;
    }

    /**
     * 获取数据库缓存文件路径，不检查文件是否存在
     * !! 开发环境下，不启用缓存
     * !! 如果传入 $force 参数 true，则：
     * !!   如果当前数据库配置开启了缓存，返回 指定的文件路径
     * !!   如果未开启缓存，则在当前应用路径下，生成默认缓存路径 app/%{APPK}%/db/%{DBN}%/cache/config.php
     * @param Bool $force 是否忽略 缓存启用 以及 开发环境 状态，强制生成缓存文件路径
     * @return String|null
     */
    protected function getConfCachePath($force=false)
    {
        $opt = $this->opt;
        //配置参数中 缓存文件路径
        $cf = $opt["cache"] ?? false;
        //默认的 缓存文件路径
        $df = $this->fixConfTpls("app/%{APPK}%/db/%{DBN}%/cache/config.php");

        //不强制
        if ($force===false) {
            //受控
            if (Env::$current->dev===true || $cf===false) return null;
            //$cf==true 则使用默认路径
            return $cf===true ? $df : (Is::nemstr($cf) ? $cf : $df);
        }

        //强制
        return Is::nemstr($cf) ? $cf : $df;
    }

    /**
     * 读取数据库配置缓存
     * @return Array 缓存的数据库配置内容，不存在则返回 null
     */
    protected function readConfCache()
    {
        //读取缓存文件路径
        $cf = $this->getConfCachePath();
        if (!Is::nemstr($cf)) return null;
        //尝试读取缓存
        $cp = Path::find($cf, Path::FIND_FILE);
        //缓存文件不存在
        if (!file_exists($cp)) return null;

        /**
         * 读取并处理缓存的配置参数
         * !! Cache 工具类必须支持当前的 缓存文件后缀名，默认缓存为 json 文件，已被支持
         */
        //调用 Cache 缓存工具类的 运行时调整参数、执行回调、恢复参数 的 方法，读取配置缓存
        $cd = Cache::runtimeExec(
            [
                //!! 数据库配置缓存永不过期，如需更新缓存，需要手动删除已有的缓存文件
                "expire" => 0,
            ],
            //读取 配置缓存
            function() use ($cp) {
                //!! 读取的缓存数据，不含 缓存标记
                return Cache::read($cp, false);
            }
        );
        //未能获取到有效的 配置缓存
        if (!Is::nemarr($cd)) return null;

        //!! 缓存的数据库配置 可以被 Orm 参数中此数据的参数额外覆盖，以实现个性化
        $common = $this->opt["common"] ?? [];
        if (Is::nemarr($common)) {
            $cd = Arr::extend($cd, $common);
        }

        return $cd;
    }

    /**
     * 写入数据库配置缓存
     * @return Bool
     */
    protected function saveConfCache()
    {
        //!! 不论是否开缓存，是否开发模式，都写入缓存，因此 强制获取缓存文件路径
        $cf = $this->getConfCachePath(true);

        //未启用配置缓存
        if (!Is::nemstr($cf)) return false;
        //准备要写入的内容 当前配置类的 context
        $cnt = $this->context;
        
        /**
         * 写入缓存文件，不存在会自动创建
         * !! Cache 工具类必须支持当前的 缓存文件后缀名，默认缓存为 json 文件，已被支持
         * !! 确保缓存文件所在路径支持写入操作
         */
        return Cache::save($cf, $cnt);
    }

    /**
     * 替换配置参数中的 %{***}% 模板字符
     * @param String|Array $conf 配置参数内容，可以是 json 字符串或 []
     * @param Array $tpls 用于替换配置文件中的 %{***}% 模板字符串的数据源，将合并到 $this->opt["tpls"][] 中
     * @return String|Array 返回替换后的配置参数数据，数据类型 与传入的 $conf 一致
     */
    protected function fixConfTpls($conf=[], $tpls=[])
    {
        //类型不匹配，原样返回
        if (!Is::nemstr($conf) && !Is::nemarr($conf)) return $conf;
        //[] 类型配置参数转为 json
        $isarr = false;
        if (Is::nemarr($conf)) {
            $isarr = true;
            $conf = Conv::a2j($conf);
        }

        //准备模板替换数据源
        $otpls = $this->opt["tpls"] ?? [];
        //合并 %{***}% 模板数据源
        if (Is::nemarr($tpls)) {
            $tpls = Arr::extend($otpls, $tpls);
        } else {
            $tpls = $otpls;
        }
        if (!Is::nemarr($tpls) || !Is::associate($tpls)) $tpls = [];

        //!! 如果需要的话，此处可以添加一些默认的 全局参数 作为 %{***}% 模板替换数据源
        //...

        //依次替换 %{***}% 模板
        if (Is::nemarr($tpls) && Is::associate($tpls)) {
            foreach ($tpls as $tk => $tv) {
                $conf = str_replace("%{".strtoupper($tk)."}%", $tv, $conf);
            }
        }

        //返回对应类型的 配置参数
        if ($isarr) return Conv::j2a($conf);
        return $conf;
    }

    /**
     * 合并到 标准输出结构
     * !! 允许外部调用
     * @param String $prop 标准输出结构 键名，可选 exportDbConf | exportModelConf | exportColumnConf
     * @param Array[] $confs 需要依次合并的 数据[]，后面的覆盖前面的
     * @return Array 返回合并后的 数据[]
     */
    public function stdExport($prop="exportDbConf", ...$confs)
    {
        if (!Is::nemstr($prop) || !isset($this->$prop) || !Is::nemarr($this->$prop)) return [];
        $export = $this->$prop;
        if (!Is::nemarr($confs)) return Arr::copy($export);

        //去除 $confs 中 不是有效 [] 的内容
        $confs = array_merge([], array_filter($confs, function($conf) {
            return Is::nemarr($conf) && Is::associate($conf);
        }));
        if (!Is::nemarr($confs)) return Arr::copy($export);

        //执行合并
        return Arr::extend(
            [],
            $export,
            ...$confs
        );
    }
    
}