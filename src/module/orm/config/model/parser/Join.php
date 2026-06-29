<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型配置解析类  继承自 Parser 基类
 * 
 * 处理 数据模型(表)配置参数中的 join 参数项，解析关联表配置
 * !! 如果 存在 select 类型字段，将一并处理 select source 指向其他表的情况
 * 
 * join 配置使用 Medoo 的标准语法
 * !! 所有关联字段名，必须完整写明：
 * !!       表名.字段名  或  别名.字段名
 * !! 如果关联表 和 主表 使用相同字段名，则 简写的情况下只需要写 字段名  ！！不推荐简写
 * 
 */

namespace Spf\module\orm\config\model\parser;

use Spf\module\orm\OrmException;
use Spf\module\orm\config\DbConfig;
use Spf\module\orm\config\model\Parser;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Join extends Parser 
{
    /**
     * 解析过程中的 数据，这些数据最终将被 写入 $this->context 
     * 通常指定了 此解析器将要修改 $this->context 中的 哪些数据
     * !! 与 DbConfig::$exportModelConf[] 结构一致
     * !! 覆盖父类
     */
    protected $temp = [
        //join 参数
        "join" => [],
        //特殊字段 列表
        "special" => [],
        //每个字段的 参数数组
        "column" => [],
    ];

    //!! 定义 默认的 join 参数结构
    protected $dftJoin = [
        //保存配置中的 原始 join 参数
        "origin" => [],
        //!! 是否可以 join 其他表，表示存在有效的 join 参数
        "available" => false,

        //提取出的 所有被 join 的关联表 表名
        "tables" => [],                 //关联表 表名 数组，foo_bar

        //提取出的 主表中所有有关联关系的 字段名
        "columns" => [],

        /**
         * 解析后的 join 参数
         * 配置中的写法：Medoo 语法
         *  "join" => [
         *      "[>]foo_bar (alias)" => [
         *          "main.col_a" => "alias.col_a",
         *          "other.col_a" => "alias.col_a",
         *          !! 可带筛选条件
         *          "AND" => [
         *              "main.col_b[!=]" => 1
         *          ]
         *      ],
         * 
         *      !! 简写形式（不推荐）
         *      "[>]bar_jaz (other)" => "col_b",
         * 
         *      "[<]tom_jry" => [
         *          "main.col_c" => "tom_jry.col_c"
         *      ],
         *  ]
         * 解析得到：
         *  [
         *      # 键名为 关联表名
         *      !! 如果关联表存在 别名，则使用 别名 作为键
         *      "alias" => [
         *          !! 结构与 $dftJoinTable[] 中定义的 一致
         *          "table" => "foo_bar",
         *          "alias" => "alias",
         *          "columns" => [
         *              "main.col_a", "alias.col_a", "other.col_a",
         *          ],
         *          "type" => "[>]",
         *          "dep" => [
         *              "other"
         *          ],
         *          "param" => [
         *              "main.col_a" => "alias.col_a",
         *              "other.col_a" => "alias.col_a",
         *              "AND" => [
         *                  "main.col_b[!=]" => 1
         *              ]
         *          ],
         *      ],
         * 
         *      "other" => [
         *          "table" => "bar_jaz",
         *          "alias" => "other",
         *          "columns" => [
         *              "main.col_b", "other.col_b",
         *          ],
         *          "type" => "[>]",
         *          "dep" => [],
         *          "param" => [
         *              "[>]bar_jaz (other)" => "col_b",
         *          ],
         *      ],
         * 
         *      "tom_jry" => [
         *          "table" => "tom_jry",
         *          "alias" => "",
         *          "columns" => [
         *              "main.col_c", "tom_jry.col_c",
         *          ],
         *          "type" => "[<]",
         *          "dep" => [],
         *          "param" => [
         *              "[>]bar_jaz (other)" => "col_b",
         *          ],
         *      ],
         *  ]
         */
        "param" => [
            
        ],
    ];

    /**
     * !! 针对某个关联表，定义标准 join 参数结构
     */
    protected $dftJoinTable = [
        //关联表名 真实表名
        "table" => "",

        //如果关联表存在 别名，记录别名，没别名则为空
        "alias" => "",

        //使用到的关联字段
        //!! 必须是 表名.字段名  有别名时则 别名.字段名
        "columns" => [
            //"表名.字段名", "别名.字段名",
        ],

        //join 方式  
        "type" => "",

        /**
         * 依赖的其他 关联表
         * !! 如果此 join 参数中使用了其他关联表的字段，表示依赖这个 其他关联表
         * !! 其他关联表如果有别名，则记录别名，否则记录表名
         */
        "dep" => [
            //"表名", "别名",
        ],

        //!! 配置参数中的 原始 join 参数
        "param" => [],
    ];
    


    /**
     * 解析入口
     * 解析 $this->origin 参数，将生成的最终参数 写入 $this->context 并返回
     * !! 必须实现，覆盖父类
     * @return Array 解析得到的 此数据模型(表)参数 []
     */
    public function parse()
    {
        //当前模型名 modk 即 主表名
        $modk = $this->modk;

        //定义完整的 join 参数结构
        $ctx = Arr::copy($this->dftJoin);

        // 0    记录原始配置
        if (Is::nemaso($this->origin["join"])) $ctx["origin"] =  Arr::copy($this->origin["join"]);
        
        // 1    解析 $this->origin["join"] 中可能存在的 join 参数
        $this->parseJoin($ctx);

        // 1    依次解析每个 select 字段，数据源来自另一个表
        $this->eachColumn(function($colk, $colv) use ($modk, &$ctx) {
            //已解析的 字段参数
            $colc = $this->context["column"][$colk] ?? [];
            //跳过这些字段
            if (
                //不是 select 字段
                $colc["isSelect"]!==true || 
                //select 参数不符合要求
                !(
                    isset($colc["select"]) && Is::nemaso($colc["select"]) &&
                    $colc["select"]["multiple"]!==true && 
                    Is::nemstr($colc["select"]["source"]["table"])
                ) || 
                //已存在于 join 参数中的字段
                in_array($colk, $ctx["columns"])
            ) {
                return true;
            }

            //!! 自动将 select 字段(source 为其他表数据) 转换为 LEFT JOIN
            $selc = $colc["select"]["source"];
            $stbn = $selc["table"];
            $scol = $selc["value"];
            $spk = "[>]".$stbn;
            $jtbc = Arr::extend([], $this->dftJoinTable, [
                "table" => $stbn,
                //!! select 参数中的 source table 一定是 LEFT JOIN
                "type" => "[>]",
                "columns" => [
                    "$modk.$colk", "$stbn.$scol"
                ],
                "param" => [
                    $spk => [
                        "$modk.$colk" => "$stbn.$scol"
                    ]
                ],
            ]);

            //写入 $ctx["origin"] $ctx["tables"] $ctx["columns"] $ctx["param"]
            //if (!isset($ctx["origin"][$spk])) $ctx["origin"] = Arr::extend($ctx["origin"], $jtbc["param"]);
            if (!in_array($stbn, $ctx["tables"])) $ctx["tables"][] = $stbn;
            if (!in_array($scol, $ctx["columns"])) $ctx["columns"][] = $colk;
            if (!isset($ctx["param"][$stbn])) $ctx["param"][$stbn] = $jtbc;
            
            return true;
        });


        //写入 $temp
        $this->setTemp([
            "join" => $ctx
        ]);
        
        //解析完成，将 $this->temp 写入 $this->context 
        $this->setCtx($this->temp);

        //!! forDev
        //var_dump($this->context["column"]["role"]);
        //exit;

        return $this->context;
    }



    /**
     * 内部工具
     */

    /**
     * 解析 $this->origin["join"] 中定义的 join 参数
     * @param Array $ctx 已经解析得到的 join 参数，结构与 $dftJoin 一致
     * @return Array 解析 join 参数后得到的 $ctx
     */
    protected function parseJoin(&$ctx)
    {
        //当前模型名 modk 即 主表名
        $modk = $this->modk;

        //原始 join 参数
        $join = $this->origin["join"] ?? [];
        //!! 不存在 join 参数 直接返回
        if (!Is::nemaso($join)) return $ctx;

        //开始解析 join 参数
        $ctx["available"] = true;
        
        //依次解析每个参数
        foreach ($join as $k => $v) {
            if (
                !Is::nemstr($k) ||
                !(Is::nemaso($v) || (Is::nemstr($v) && in_array($v, $this->context["columns"])))
            ) {
                continue;
            }
            $k = trim($k);

            /**
             * join 配置参数 键名格式：
             *      [>]table_name (alias)
             */
            $mt = preg_match(
                "/^(\[[<>]{1,2}\])([a-zA-Z0-9_]+)(\s?\(\s?[a-zA-Z0-9_]+\s?\))?$/",
                $k,
                $matches
            );
            if ($mt!==1) continue;
            $ka = array_slice($matches, 1);
            if (Count($ka)<2) continue;
            //join type
            $jtp = $ka[0];
            $jtbn = $ka[1];
            $jal = count($ka)>2 ? trim(substr(trim($ka[2]), 1, -1)) : "";
            $jpre = $jal!=="" ? $jal : $jtbn;

            //写入 $ctx["tables"]
            if ($jtbn!==$modk && !in_array($jtbn, $ctx["tables"])) $ctx["tables"][] = $jtbn;

            //准备 关联表标准参数
            $jtbc = Arr::extend([], $this->dftJoinTable, [
                "table" => $jtbn,
                "alias" => $jal,
                "type" => $jtp,
            ]);

            if (Is::nemstr($v)) {
                //!! 简写的 join 参数扩展为完整参数
                $jcol = $jpre.".".$v;
                $jtbc["param"] = [ 
                    $k => [
                        $modk.".".$v => $jcol 
                    ]
                ];
            } else {
                //完整配置，直接使用
                $jtbc["param"] = [ $k => $v ];
            }

            //查找 关联字段 | 依赖其他表
            $jps = $jtbc["param"][$k];
            $jfds = [];
            $jdep = [];
            foreach ($jps as $jk => $jv) {
                if (
                    !Is::nemstr($jk) || !Is::nemstr($jv) ||
                    in_array(strtoupper($jk), ["AND","OR"]) ||
                    preg_match(
                        "/^(AND|OR)\s+#.+/",
                        strtoupper($jk),
                        $matches
                    )===1
                ) {
                    continue;
                }

                $jk = strpos($jk,".")===false ? $modk.".".$jk : $jk;
                if (!in_array($jk, $jfds)) $jfds[] = $jk;
                $jka = explode(".",$jk);
                if (!in_array($jka[0], [$modk, $jpre])) $jdep[] = $jka[0];
                if ($jka[0]===$modk && !in_array($jka[1], $ctx["columns"])) $ctx["columns"][] = $jka[1];
                
                $jv = strpos($jv, ".")===false ? $jpre.".".$jv : $jv;
                if (!in_array($jv, $jfds)) $jfds[] = $jv;
                $jva = explode(".",$jv);
                if (!in_array($jva[0], [$modk, $jpre])) $jdep[] = $jva[0];
                if ($jva[0]===$modk && !in_array($jva[1], $ctx["columns"])) $ctx["columns"][] = $jva[1];

                //!! 如果 当前关联表存在 别名，$jv 关联表字段 转为简写，因为 $jv 一定是关联表字段
                if ($jal!=="" && $jva[0]===$jpre) {
                    $jtbc["param"][$k][$jk] = $jva[1];
                }
            }
            $jtbc["columns"] = $jfds;
            $jtbc["dep"] = $jdep;

            //写如 $ctx["param"]
            $ctx["param"][$jpre] = $jtbc;
        }

        return $ctx;
    }

    /**
     * 处理 join 参数中包含的 但 未被定义为 select 类型的 字段
     * 自动将 join 字段，转为 select 类型
     * @param Array $ctx 已经解析得到的 join 参数，结构与 $dftJoin 一致
     * @param String $colk 字段名 foo_bar
     * @return Array 解析 join 参数后得到的 $ctx
     */
    protected function __autoSelectJoinColumns(&$ctx, $colk)
    {
        //跳过 不在 join 参数中的字段
        if (!isset($ctx["column"][$colk])) return $ctx;
        
        //已解析的 字段参数
        $colc = $this->context["column"][$colk] ?? [];
        //跳过 已经是 select 类型的 字段
        if ($colc["isSelect"]===true) return $ctx;

        $lc = $ctx["column"][$colk];
        //设置 isJoin
        $this->setTemp([
            "column" => [
                $colk => [
                    "isJoin" => true,
                    "join" => $lc
                ],
            ],
            "special" => [
                "join" => [$colk]
            ],
        ]);

        //找到第一个 LEFT JOIN 关联表
        $lj = [/* tbn, col */];
        foreach ($lc as $tbn => $lci) {
            if (isset($lci["relate"]) && $lci["relate"]===">") {
                $lj = [$tbn, $lci["linkto"]];
                break;
            }
        }
        //找到这个关联表，则设置 select
        if (Is::nemidx($lj)) {
            $this->setTemp([
                "column" => [
                    $colk => [
                        "isSelect" => true,
                        "select" => [
                            "dynamic" => true,
                            "source" => [
                                "table" => $lj[0],
                                "label" => $lj[1],
                                "value" => $lj[1],
                            ],
                        ]
                    ],
                ],
                "special" => [
                    "select" => [$colk],
                ],
            ]);
        }
        
        return $ctx;
    }

    /**
     * 解析某个字段的 可能存在的 select 参数
     * 自动将 符合条件的 select 类型的 字段，转换为 join 关联字段，并创建 join 参数
     * !! 如果字段同时有 select 和 join 参数，则 select 参数 覆盖 join 参数
     * !! select 参数有更高优先级
     * @param Array $ctx 已经解析得到的 join 参数，结构与 $dftJoin 一致
     * @param String $colk 字段名 foo_bar
     * @return Array 解析 join 参数后得到的 $ctx
     */
    protected function __parseColumnSelect(&$ctx, $colk)
    {
        //已解析的 字段参数
        $colc = $this->context["column"][$colk] ?? [];
        if (!isset($colc["isSelect"]) || $colc["isSelect"]!==true) return $ctx;

        //select 参数
        $selc = $colc["select"] ?? [];
        if ($selc["dynamic"]!==true || !isset($selc["source"])) return $ctx;
        $src = $selc["source"];
        $sdbn = $src["db"] ?? null;
        $stbn = $src["table"] ?? null;
        $scol = $src["value"] ?? null;
        if (!Is::nemstr($stbn) || !Is::nemstr($scol)) return $ctx;
        $stbn = Str::snake($stbn, "_");
        $scol = Str::snake($scol, "_");

        //!! 暂不支持 跨库 join
        //TODO:
        if (Is::nemstr($sdbn)) return $ctx;

        //$stbn 写入 $ctx["tables"]
        if (!in_array($stbn, $ctx["tables"])) $ctx["tables"][] = $stbn;
        //$colk 覆盖进入 $ctx["column"] ，select 参数 将覆盖 join 参数
        $ctx["column"] = Arr::extend($ctx["column"], [
            $colk => [
                $stbn => [
                    //"alias" => null,
                    "linkto" => $scol,
                    //!! select 下拉列表字段，默认使用 LEFT JOIN 方式与对应表 关联
                    "relate" => ">",
                ]
            ]
        ]);
        //覆盖 $ctx["param"] 
        $pk = "[>]".$stbn;
        $ctx["param"] = Arr::extend($ctx["param"], [
            $pk => [
                $colk => $scol
            ]
        ]);
        //设置 isJoin
        $this->setTemp([
            "column" => [
                $colk => [
                    "isJoin" => true,
                    "join" => $ctx["column"][$colk]
                ],
            ],
            "special" => [
                "join" => [$colk]
            ],
        ]);

        return $ctx;
    }
}