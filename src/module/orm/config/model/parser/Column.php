<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型配置解析类  继承自 Parser 基类
 * 
 * 处理 数据模型(表)配置参数中的 column 参数项，解析其中的这些项目：
 *      includes,sort,search,filter，generator，select
 * 
 * !! 可以在 应用级-网站级 继承此类并扩展 自有的 其他项目的解析方法
 * 
 * !! 也可以 直接自定义解析类，将会自动注册到 Parser::$collection 
 * !!   在 whenCollect 方法中 可以设置自定义解析器的 解析顺序，可插入 Column 解析器之后执行
 */

namespace Spf\module\orm\config\model\parser;

use Spf\module\orm\OrmException;
use Spf\module\orm\config\DbConfig;
use Spf\module\orm\config\model\Parser;
use Spf\module\orm\config\model\parser\CreationSql;
use Spf\module\orm\Types;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Column extends Parser 
{
    /**
     * 解析过程中的 数据，这些数据最终将被 写入 $this->context 
     * 通常指定了 此解析器将要修改 $this->context 中的 哪些数据
     * !! 与 DbConfig::$exportModelConf[] 结构一致
     * !! 覆盖父类
     */
    protected $temp = [
        //特殊字段 列表
        "special" => [],
        //每个字段的 参数数组
        "column" => [],
    ];
    


    /**
     * 解析入口
     * 解析 $this->origin 参数，将生成的最终参数 写入 $this->context 并返回
     * !! 必须实现，覆盖父类
     * @return Array 解析得到的 此数据模型(表)参数 []
     */
    public function parse()
    {
        //使用相同方法解析的 参数
        $cks = ["includes", "sort", "filter", "search"];

        //依次解析每个字段
        $this->eachColumn(function($colk, $colv) use ($cks) {

            //准备结果
            $rtn = [
                "special" => [
                    "includes"  => [],
                    "sort"      => [],
                    "filter"    => [],
                    "search"    => [],
                    "generator" => [],
                ],
                "column" => [
                    $colk => []
                ]
            ];

            // 0    依次解析 includes,sort,search,filter
            foreach ($cks as $cki) {
                //这些参数都是 indexed 数组，如果包含 $colk 则会返回 "__default__"
                $colc = $this->getColumnTypeConf($colk, $cki);
                if ($colc!=="__default__") continue;
                
                //写入 $rtn
                $rtn["column"][$colk]["is".Str::camel($cki, true)] = true;
                $rtn["special"][Str::snake($cki, "_")][] = $colk;
            }

            // 1    解析 generator 参数
            $rtn = $this->parseColumnGeneratorConf($colk, $rtn);

            // 2    解析 select 参数
            $rtn = $this->parseColumnSelectConf($colk, $rtn);

            // 3    解析其他 origin["column"] 中的 参数项目
            $rtn = $this->parseColumnExtraConf($colk, $rtn);

            //返回
            return $rtn;
        });
        
        //解析完成，将 $this->temp 写入 $this->context 
        $this->setCtx($this->temp);

        //!! forDev
        //var_dump($this->context);
        //exit;

        return $this->context;
    }



    /**
     * 解析 当前字段的 generator 参数
     * !! 子类根据需要覆盖
     * @param String $colk 字段名
     * @param Array $rtn 已经解析得到的字段参数
     * @return Array 当前字段的 参数解析结果  与 DbConfig::$exportModelConf[] 结构一致
     */
    protected function parseColumnGeneratorConf($colk, &$rtn)
    {
        //generator 参数是 indexed 数组，如果包含 $colk 则会返回 "__default__"
        $colc = $this->getColumnTypeConf($colk, "generator");
        if ($colc!=="__default__") return $rtn;

        /**
         * generator 自动生成字段，例如 用户编码：uid  产品编码：gid
         * !! 通过 default 默认值自动生成的方式，在 insert 时自动生成 generator 字段值
         * 需要生成 default 默认值的 getter 方法，且 仅在 insert 时执行
         */
        //模型类
        $modcls = $this->model;
        //!! 必须在 模型类中定义 对应的 generator 方法  fooBarGenerator
        $getter = Str::camel($colk)."Generator";
        if (!method_exists($modcls, $getter)) return $rtn;

        $nrtn = [
            "column" => [
                $colk => [
                    //标记 generator 字段
                    "isGenerator" => true,
                    //!! generator 字段需要 作为 includes 必查字段
                    "isIncludes" => true,
                    //修改字段默认值
                    "default" => [
                        "value" => "__getter__",
                        "params" => [
                            "getter" => [$modcls, $getter],
                            "when" => ["insert"]
                        ],
                    ],
                ],
            ],
            "special" => [
                "generator" => [$colk],
                "includes" => [$colk],
            ],
        ];

        //现有 creationParams
        $sqlp = $this->context["column"][$colk]["creationParams"];
        //如果当前字段 creation-sql 中包含 默认值，则取消
        if (isset($sqlp["default"]) && Is::nemstr($sqlp["default"])) {
            //取消 creation-sql 中的默认值
            $sqlp["default"] = "";
            //重新生成 creation-sql
            $nsql = CreationSql::createCreationSql($sqlp);
            $nrtn = Arr::extend($nrtn, [
                "creation" => [
                    $colk => $nsql,
                ],
                "column" => [
                    $colk => [
                        "creationParams" => $sqlp
                    ]
                ],
            ]);
        }

        $rtn = Arr::extend($rtn, $nrtn);
        return $rtn;
    }

    /**
     * 解析 当前字段的 select 参数
     * !! 子类根据需要覆盖
     * @param String $colk 字段名
     * @param Array $rtn 已经解析得到的字段参数
     * @return Array 当前字段的 参数解析结果  与 DbConfig::$exportModelConf[] 结构一致
     */
    protected function parseColumnSelectConf($colk, &$rtn)
    {
        //读取已经解析得到的 字段参数
        $ctx = $this->context["column"][$colk] ?? [];

        //获取当前字段的 select 参数
        $selc = $this->getColumnTypeConf($colk, "select");
        if (!Is::nemaso($selc)) return $rtn;

        //字段参数
        $colsel = [];
        //字段 $colk 是 select 下拉列表
        $colsel = Arr::extend($colsel, [
            "isSelect" => true,
            "select" => $selc
        ]);
        
        //如果是 cascade 或 多选
        if ($selc["cascade"]===true || $selc["multiple"]===true) {
            $colsel = Arr::extend($colsel, [
                "isCascade" => $selc["cascade"]===true,
            ]);

            //可能需要 修改已经生成的 creation-sql 以及 字段类型、默认值
            $sqlp = $ctx["creationParams"] ?? [];
            if (
                //字段原类型不是 json
                $ctx["type"]["def"]!=="json" || 
                //字段原默认值不是有效的 indexed 数组
                !(Is::nemarr($ctx["default"]["value"]) || Is::indexed($ctx["default"]["value"]))
            ) {
                //定义 默认值
                $dft = $ctx["default"] ?? [];
                $dft["value"] = [];
                $dft["params"] = Arr::extend([
                    "getter" => null,
                    "when" => ["insert"]
                ], $dft["params"] ?? [], true);

                //修改字段类型
                $maptp = Types::get("json")::getMappingType($this->driver::driver());
                $colsel = Arr::extend($colsel, [
                    "type" => [
                        "def" => "json",
                        "db" => strpos($maptp, "(")!==false ? explode("(", $maptp)[0] : $maptp,
                        "js" => "Array",
                        "php" => "Array"
                    ],
                    "isJson" => true,
                    "json" => [
                        "type" => "indexed",
                        "default" => $dft,
                    ],
                    "default" => $dft
                ]);

                //修改 creation-sql 以及 默认值
                $sqlp = Arr::extend($sqlp, [
                    "default" => "'[]'",
                ]);
                //重新生成 creation-sql
                $nsql = CreationSql::createCreationSql($sqlp);
                /*$nsql = [];
                $nsql[] = $maptp;
                if (Is::nemidx($sqlp["binds"])) $nsql = array_merge($nsql, $sqlp["binds"]);
                $nsql[] = "DEFAULT";
                $nsql[] = $sqlp["default"];
                if (Is::nemidx($sqlp["extra"])) $nsql = array_merge($nsql, $sqlp["extra"]);
                $nsql = implode(" ", $nsql);*/
                $colsel = Arr::extend($colsel, [
                    "creation" => $nsql,
                    "creationParams" => $sqlp,
                ]);

                //修改 $ctx 中的 creation
                $rtn = Arr::extend($rtn, [
                    "creation" => [
                        $colk => $nsql,
                    ],
                ]);
            }
        }

        //写入 $rtn
        $rtn = Arr::extend($rtn, [
            "column" => [
                $colk => $colsel
            ],
            "special" => [
                "select" => [$colk]
            ],
        ]);
        
        return $rtn;
    }

    /**
     * !! 扩展点
     * 可在 应用级-网站级 继承并扩展 Column 解析类，覆盖这个方法，实现对其他 字段参数项目的 自定义解析
     * !! 子类根据需要覆盖
     * @param String $colk 字段名
     * @param Array $rtn 已经解析得到的字段参数
     * @return Array 当前字段的 参数解析结果  与 DbConfig::$exportModelConf[] 结构一致
     */
    protected function parseColumnExtraConf($colk, &$rtn)
    {
        //子类扩展...

        return $rtn;
    }



    /**
     * 内部工具
     */
}