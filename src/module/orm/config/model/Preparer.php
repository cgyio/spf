<?php
/**
 * SPF-Orm 数据库操作模块  数据库配置参数处理类
 * 处理 model 数据模型(表)参数中定义的 prepare 预处理操作
 * 
 * 预处理操作将 影响 数据模型(表)参数项目：  columns[]  creation[]  column[]  front[]  等
 * 
 * 数据模型(表)配置参数中的 prepare 预处理参数形式：见 Preparer::$dftOption[]
 * 
 * 
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * !! 需要在 自定义 Preparer 类的 whenCollect() 方法中，将自定义的 $specialColumns[] 通用特殊字段 
 * !! 合并到 Preparer::$specialColumnsCollection[] 中
 * 
 */

namespace Spf\module\orm\config\model;

use Spf\module\orm\OrmException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

use Spf\traits\ExpandableResource;

abstract class Preparer 
{
    //引用  可扩展底层资源类  特征
    use ExpandableResource;
    //!! trait 中要求的，子类不要覆盖
    protected static $exresName = "preparer";
    protected static $exresClassPath = [
        "module/orm/config/model",
        "db/config/model"
    ];
    public static $isCollected = false;
    
    /**
     * 当某个 Preparer 子类被 collect 收集时，将此子类的 $specialColumns[] 合并到 Preparer::$specialColumnsCollection[]
     * !! trait 中要求的，子类根据需要覆盖
     * !! 可以在特定的解析类中，自行处理 解析顺序，例如插入某个其他解析类之前或之后
     * @return Bool
     */
    protected static function whenCollect()
    {
        $scols = static::$specialColumns;
        if (!Is::nemaso($scols)) return true;

        //合并到 基类 Preparer::$specialColumnsCollection[] 中
        //!! 子类定义的 将覆盖 基类定义的
        Preparer::$specialColumnsCollection = Arr::extend(
            Preparer::$specialColumnsCollection, 
            $scols,
            //!! indexed 数组使用 覆盖的方式合并
            true
        );

        return true;
    }



    /**
     * Orm 支持的 通用特殊字段
     * !! 子类不要覆盖
     * 可通过 数据模型参数 prepare["columns"] 中指定 要使用的特殊字段
     * 将会自动添加 字段参数 到 数据模型参数的 creation|columns|column|... 这些项目中
     * !! Exres 子类可以扩展 特殊字段，将被自动收集到这里
     */
    protected static $specialColumnsCollection = [
        //这些是 默认的 特殊字段

        //common 所有 SPF-Orm 数据库模块的数据模型都必须在 开头包含的 字段
        "common" => [
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
        ],

        //final 所有 SPF-Orm 数据库模块的数据模型都必须在 结尾包含的 字段
        "final" => [
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
        ],

    ];

    /**
     * 当前 Preparer 子类定义的 特殊字段
     * !! 子类必须指定
     * 结构与 Preparer::$specialColumnsCollection[] 一致
     */
    protected static $specialColumns = [
        //子类定义...
    ];

    /**
     * 默认的 prepare 数据模型(表) 与处理参数结构
     * !! 子类不要覆盖
     */
    protected static $dftOption = [

        //!! 用于预处理的 Preparer 子类，默认 preparer/Base ，可以指定使用扩展的 预处理类
        "class" => "base",  //foo_bar

        //数据模型(表) 参数可以继承自某个通用数据模型，指定此通用模型的 配置文件路径，Path::find() 可识别
        "extends" => "",    //!! 如果后缀名 与 当前数据库的 $db->config->ctx["dbConfigExt"] 一致，则可省略文件后缀名
        
        //可通过 columns 指定需要自动补全的 通用特殊字段字段
        //!! 特殊字段会被收集到 Preparer::$specialColumnsCollection[] 中
        //!! common 和 final 特殊字段不需要手动指定，会自动添加
        "columns" => [
            //"package", ...
        ],

        //指定 索引参数
        //!! 必须在 columns 参数之后定义
        "idx" => [
            //唯一索引，索引名自动增加 idx_ 前缀
            "unique" => [
                //字段名, 字段名, ...
            ],
            //普通索引，索引名自动增加 idx_ 前缀
            "normal" => [
                //字段名, 字段名, ...
            ],
            //复合索引，索引名自动增加 idx_ 前缀
            "multiple" => [
                //"索引名 foo_bar" => [ 字段1, 字段2, ... ],
            ],
        ],

    ];

    //缓存 初始的 数据模型(表) 参数
    protected $origin = [];

    //缓存 数据模型(表)参数中的 prepare 参数
    protected $option = [];

    //处理 过程中|之后 的 数据模型(表) 参数
    protected $context = [];

    //缓存 数据模型(表) 名称  foo_bar 
    protected $modk = "";

    /**
     * 构造
     * @param String $modk 数据模型(表)名称 foo_bar
     * @param Array $conf 初始的 数据模型(表) 参数
     * @return void
     */
    public function __construct($modk, $conf=[])
    {
        if (!Is::nemstr($modk) || !Is::nemarr($conf)) return null;
        $conf = static::fixOption($conf);

        //缓存
        $this->modk = $modk;
        $this->origin = $conf;
        $this->option = $conf["prepare"];

        //准备处理
        $this->context = Arr::copy($conf);
        unset($this->context["prepare"]);
    }

    

    /**
     * !! 外部调用入口，在 DbConfig 数据库配置类中调用
     * 根据传入的 数据模型(表) 参数，创建 prepare 预处理类的实例
     * !! 子类不要覆盖
     * @param String $modk 数据模型(表)名称 foo_bar
     * @param Array $conf 初始的 数据模型(表) 参数
     * @return Array 返回预处理后的 配置数据
     */
    final public static function prepare($modk, $conf=[])
    {
        //默认值填充
        $conf = static::fixOption($conf);

        //调用指定的 预处理类，默认 Base
        $clsk = $conf["prepare"]["class"] ?? "base";
        $cls = Preparer::support($clsk);
        //!! 如果指定 预处理类不存在，使用 base
        if (!class_exists($cls)) {
            $cls = Preparer::support("base");
        }

        //实例化
        $po = new $cls($modk, $conf);
        //执行 parse 方法，返回处理结果
        $rtn = $po->parse();
        //释放资源
        unset($po);

        return $rtn;
    }



    /**
     * 执行处理
     * !! 子类不要覆盖
     * @return Array 返回处理后的 数据模型(表)配置参数 $this->context[]
     */
    final public function parse()
    {
        //按顺序执行 预处理操作
        // 0    处理 extends 继承其他数据模型参数
        $this->prepareExtends();
        // 1    处理 columns 通用字段
        $this->prepareColumns();
        // 2    处理 idx 索引定义
        $this->prepareIdx();

        //返回处理后的 $this->context
        return $this->context;
    }

    /**
     * 写入 context
     * @param Array $conf 要写入 context 中的参数
     * @return Bool
     */
    protected function setCtx($conf=[])
    {
        if (Is::nemarr($conf)) {
            $this->context = Arr::extend($this->context, $conf);
            return true;
        }
        return false;
    }



    /**
     * 预处理方法
     * !! 如果不是必须的，子类不要覆盖
     */

    /**
     * 处理 extends 参数
     * 使用预定义的 模型结构参数 覆盖当前数据模型
     * 适用于一些拥有相同结构的 数据模型 共用相同的模型参数
     * extends 参数指定了 要继承的 数据模型配置文件路径，读取文件，将数据覆盖到 $this->context[]
     * @return Bool
     */
    protected function prepareExtends()
    {
        //extends
        $extends = $this->option["extends"] ?? "";
        if (!Is::nemstr($extends)) return false;

        //配置文件后缀名，通常为 .json
        $ext = Path::ext($extends);
        //要继承的 数据模型配置文件
        $exf = Path::find($extends, Path::FIND_FILE);
        if (!empty($exf) && file_exists($exf)) {
            //读取配置文件内容
            $mc = file_get_contents($exf);
            //转为 []
            switch ($ext) {
                case ".json" :
                    $mc = Conv::j2a($mc);
                    break;
                //TODO: 兼容其他类型文件
                //...
            }
            //单独合并 prepare 参数
            if (isset($mc["prepare"])) {
                if (Is::nemarr($mc["prepare"]) && Is::associate($mc["prepare"])) {
                    $mcp = $mc["prepare"];
                    //如果继承的 数据模型参数中也指定了 extends 则忽略
                    if (isset($mcp["extends"])) unset($mcp["extends"]);
                    //继承的 prepare 参数写入 $this->option
                    $this->option = Arr::extend($this->option, $mcp);
                }
                unset($mc["prepare"]);
            }
            //其他继承的配置参数 写入 $this->context
            $this->setCtx($mc);
            return true;
        }
        return false;
    }

    /**
     * 处理 columns 参数
     * 为数据模型统一增加一些通用字段
     * @return Bool
     */
    protected function prepareColumns()
    {
        //向 option["columns"] 参数数组的 开头处/结尾处 分别添加 common/final 特殊通用字段
        //用于增加 id 以及 info/extra/enable/disat 通用字段
        $cols = $this->option["columns"] ?? [];
        if (!Is::nemarr($cols) || !Is::indexed($cols)) $cols = [];
        if (!in_array("common", $cols)) array_unshift($cols, "common");
        if (!in_array("final", $cols)) array_push($cols, "final");
        $this->option["columns"] = $cols;

        //依次添加 columns 中指定的 特殊通用字段
        $scols = Preparer::$specialColumnsCollection;
        foreach ($cols as $col) {
            //跳过不存在的 特殊字段
            if (!isset($scols[$col]) || !Is::nemaso($scols[$col])) continue;
            //特殊字段配置
            $colc = $scols[$col];
            //要插入的 字段
            $colcs = $colc["columns"] ?? [];
            if (!Is::nemaso($colcs)) continue;
            //append 默认 true
            $append = $colc["append"] ?? true;
            if (!is_bool($append)) $append = true;
            //调用 addColumns 方法，插入特殊字段
            $this->addColumns($colcs, $append);
        }

        return true;
    }
    
    /**
     * 处理 idx 参数
     * 为数据模型表增加索引列
     * !!! 必须在 columns 方法之后执行，在设置文件中，idx 必须在 columns 之后定义
     * @return Bool
     */
    protected function prepareIdx()
    {
        $conf = $this->option["idx"] ?? [];
        //var_dump("---- ".$this->context["name"]." ----");
        //var_dump($conf);
        $mdc = $this->context;
        //var_dump($mdc["creation"]);
        //var_dump($mdc["column"]["unique"]);
        //增加 unique 索引，隐式定义
        $uni = $conf["unique"] ?? [];
        if (Is::nemarr($uni) && Is::indexed($uni)) {
            $crt = $mdc["creation"] ?? [];
            $ucols = $mdc["column"]["unique"] ?? [];
            foreach ($uni as $i => $coln) {
                if (!isset($crt[$coln]) || !Is::nemstr($crt[$coln])) continue;
                if (strpos($crt[$coln], ' UNIQUE')===false) {
                    $crt[$coln] .= " UNIQUE";
                }
                //unique 属性保存到字段参数中
                $ucols[] = $coln;
            }
            $mdc["creation"] = $crt;
            if (!empty($ucols)) {
                $mdc["column"]["unique"] = $ucols;
            }
            //var_dump($mdc["creation"]);
            //var_dump($mdc["column"]["unique"]);
        }

        //其他索引使用 显式定义，参数保存在 $this->context["column"]["indexs"]
        $colc = $mdc["column"];
        $idxs = $colc["indexs"] ?? [];
        //索引前缀 idx_
        $pre = "idx_";
        //增加复合索引
        $multi = $conf["multiple"] ?? [];
        if (Is::nemarr($multi) && Is::associate($multi)) {
            foreach ($multi as $idxkey => $cols) {
                if (!Is::nemarr($cols) || !Is::indexed($cols)) continue;
                $idxs[$pre.$idxkey] = "(`".implode("`,`", $cols)."`)";
            }
        }
        //增加普通索引
        $nor = $conf["normal"] ?? [];
        if (Is::nemarr($nor) && Is::indexed($nor)) {
            foreach ($nor as $i => $coln) {
                $idxs[$pre.$coln] = "(`".$coln."`)";
            }
        }
        //var_dump("---- ".$this->context["name"]." ----");
        //var_dump($this->option);
        //var_dump($mdc["column"]["indexs"]);
        //var_dump($idxs);
        
        //更新模型参数
        if (!empty($idxs)) $mdc["column"]["indexs"] = $idxs;
        $this->context = $mdc;

        return true;
    }



    /**
     * 自动补全 特殊字段
     * !! 子类不要覆盖
     */

    /**
     * addColumn 基础方法
     * 为数据模型参数 增加字段
     * 所有 addFooBarColumns 方法都调用此方法
     * !! 子类不要覆盖
     * @param String $coln 字段名称
     * @param Array $conf 字段参数，将覆盖到现有的 context[] 中
     *  [
     *      "columns" => ["title","desc",width],
     *      "creation" => "varchar NOT NULL DEFAULT ''",
     *      "join" => [
     *          "use" => true,
     *          "[>]table" => $coln
     *      ],
     *      "column" => [
     *          "includes" => [$clon],
     *          "sort" => [$coln],
     *          "filter" => [$coln],
     *          "time" => [
     *              $coln => [
     *                  时间参数
     *              ]
     *          ]
     *      ],
     *      "front" => [
     *          "table" => [
     *              "hide" => [$coln],
     *          ],
     *          "form" => [...],
     *      ],
     *  ]
     * @param Bool $append 是否添加到现有字段列表的末尾，默认 true，false 则添加到现有字段列表的开头
     * @return Bool
     */
    final protected function addColumn($coln, $conf=[], $append=true)
    {
        if (!Is::nemstr($coln) || !Is::nemarr($conf)) return false;
        //如果要添加的字段名 已经存在，直接返回
        if ($this->hasColn($coln)===true) return false;

        //需要按 $append 指定方式插入 indexed 数组的项目
        $ks = explode(",", "columns,creation");
        foreach ($ks as $i => $ki) {
            if (!isset($conf[$ki])) continue;
            $new = $conf[$ki];
            $old = $this->context[$ki] ?? [];
            if (!empty($new)) {
                if (!Is::nemarr($old)) {
                    //原关联数组为空，直接覆盖
                    $this->context[$ki] = [
                        $coln => $new
                    ];
                } else {
                    //根据 $append 指定的方式 插入原关联数组中
                    if ($append) {
                        //直接插入原关联数组的 末尾
                        $old[$coln] = $new;
                    } else {
                        //插入原关联数组的 开头（重新定义关联数组）
                        $ocols = [];
                        $ocols[$coln] = $new;
                        foreach ($old as $n => $c) {
                            if ($n===$coln) continue;
                            $ocols[$n] = $c;
                        }
                        $old = $ocols;
                    }
                }
                //写回 $this->context 
                $this->context[$ki] = $old;
            }
            //删除已处理的 项目
            unset($conf[$ki]);
        }
        //处理 column 参数
        if (isset($conf["column"])) {
            //新 column 参数
            $new = $conf["column"];
            //原 column 参数
            $old = $this->context["column"];
            //新 column 参数必须是有效的 关联数组
            if (Is::nemarr($new) && Is::associate($new)) {
                foreach ($new as $k => $newk) {
                    //原 column 参数中不存在项目 $k
                    if (!isset($old[$k])) {
                        $old[$k] = $newk;
                        continue;
                    } 
                    //原 column 参数中的项目 $k
                    $oldk = $old[$k];
                    //根据 $oldk $newk 数据格式，以及 $append 方式 执行合并
                    if (Is::indexed($oldk) && Is::indexed($newk)) {
                        if ($append) {
                            $old[$k] = array_unique(array_merge($oldk, $newk), SORT_REGULAR);
                        } else {
                            $old[$k] = array_unique(array_merge($newk, $oldk), SORT_REGULAR);
                        }
                    } else if (Is::associate($oldk) && Is::associate($newk)) {
                        $old[$k] = Arr::extend($oldk, $newk);
                    } else {
                        $old[$k] = $newk;
                    }
                }
                //写回 $this->context
                $this->context["column"] = $old;
            }
            //删除已处理的项目
            unset($conf["column"]);
        }
        
        //其他项目 通过 Arr::extend 方法合并参数
        if (Is::nemarr($conf)) {
            $this->setCtx($conf);
        }

        return true;
    }
    //批量调用 addColumn 方法
    final protected function addColumns($cols=[], $append=true)
    {
        if (!Is::nemarr($cols) || !Is::associate($cols)) return false;
        $rtn = true;
        foreach ($cols as $coln => $conf) {
            $rtn = $rtn && $this->addColumn($coln, $conf, $append);
        }
        return $rtn;
    }



    /**
     * 工具方法
     */
    //判断给定的 col 字段名是否已经存在
    protected function hasColn($coln)
    {
        if (!Is::nemstr($coln)) return false;
        $cols = $this->context["columns"] ?? [];
        $colns = array_merge([], array_keys($cols));
        return in_array($coln, $colns);
    }



    /**
     * 静态工具
     */

    //使用 默认参数 填充
    public static function fixOption($conf=[])
    {
        $dft = static::$dftOption;
        if (!isset($conf["prepare"]) || !Is::nemarr($conf["prepare"]) || !Is::associate($conf["prepare"])) {
            $conf["prepare"] = [];
        }
        //填充
        $conf["prepare"] = Arr::extend([], $dft, $conf["prepare"]);
        return $conf;
    }

    /**
     * 检查传入的 特殊字段 是否被支持，支持则返回 字段参数
     * @param String $scol 要检查的特殊字段，默认 null 返回所有支持的 特殊字段参数
     * @return Array|false
     */
    public static function supportSpecialColumn($scol=null)
    {
        $scols = static::$specialColumnsCollection;
        if (!Is::nemstr($scol)) return $scols;
        if (!isset($scols[$scol])) return false;
        return $scols[$scol];
    }
}