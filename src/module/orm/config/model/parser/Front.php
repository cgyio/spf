<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型配置解析类  继承自 Parser 基类
 * 
 * 处理 数据模型(表)配置参数中的 front 参数项、
 * 
 * !! 可以在 应用级-网站级 继承此类并扩展 自有的 其他项目的解析方法
 * 
 * !! 也可以 直接自定义解析类，将会自动注册到 Parser::$collection 
 * !!   在 whenCollect 方法中 可以设置自定义解析器的 解析顺序
 * 
 * 
 * !! SPF-Orm 数据库配置参数中 front 前端参数结构：
 *  "front" => [
 *      # 表格组件参数
 *      "table" => [
 *          # 指定表格组件中 默认隐藏的 不显示的 字段列表，可以是 物理字段 和 计算字段 字段名 foo_bar
 *          "hide" => [ ... ],
 *          
 *          # 定义表格组件的 显示模式
 *          "mode" => [
 *              # 默认模式，一定存在，可不指定
 *              "default" => [
 *                  # 模式名称
 *                  "title" => "默认模式",
 * 
 *                  # 此模式下要显示的 字段名，可以是 物理字段 和 计算字段
 *                  !! [] 或 ["*"] 表示显示所有物理字段
 *                  !! ["*", "getter_a", "getter_b", ...] 表示在所有 物理字段基础上 增加显示 计算字段
 *                  !! ["*", "$"] 表示显示所有 物理字段 和 计算字段
 * 
 *                  !! 关联表字段名格式 table_name.col_name 
 *                  !! 表名默认是真实表名，以下情况使用 alias.col_name
 *                  !!      1   关联自身
 *                  !!      2   关联同一张表多次
 *                  !! 如果模型关联表参数中此关联表启用了别名，会在解析时自动处理
 *                  "columns" => [],
 * 
 *                  # 是否经典表格模式
 *                  !! 如果是 false 则 同时需要指定 component 前端组件名称，以及 props 组件参数
 *                  "isClassic" => true,
 *                  # 如果不是经典表格模式，指定前端 显示单条记录的 组件名称 和 参数
 *                  "component" => "",
 *                  # 前端 单条记录组件的 参数
 *                  "props" => [],
 *              ],
 * 
 *              # 可定义其他显示模式
 *              "mini" => [
 *                  # 必须指定的 参数
 *                  "title" => "极简模式",
 *                  "isClassic" => true,
 * 
 *                  # 此参数不指定 则相当于 []
 *                  "columns" => [ ... 仅指定要显示的 字段名 ... ],
 * 
 *                  # isClassic == true 可不指定下面的参数
 *                  "component" => "",
 *                  "props" => [],
 *              ],
 * 
 *              # 非经典表格模式
 *              "card" => [
 *                  "title" => "记录卡片模式",
 *                  "isClassic" => false,
 *                  # 可选参数，指定在 记录卡片组件中需要使用的 字段名，可以是 物理字段 和 计算字段
 *                  !! 不指定 默认为 []
 *                  "columns" => ["*", "$"],
 *                  # 必须参数，指定 记录卡片的 前端组件名称，必须是全局组件
 *                  "component" => "DvRecordCard",
 *                  # 指定 单条记录显示组件的 参数
 *                  !! 不同的前端库需要转译为对应的组件参数
 *                  "props" => [
 *                      # 标识当前数据模型的 xpath 路径：数据库名/数据表名
 *                      !! 可以使用 %{DBN}% %{MDK}% 字符串模板
 *                      "dbXpath" => "%{DBN}%/%{MDK}%",
 *                      ... 其他可选参数
 *                  ],
 *              ],
 *          ],
 * 
 *          # 在表格组件中 高亮显示记录
 *          !! 从 序号0 开始执行条件判断，满足就返回，因此 先定义的 优先级更高
 *          !! 条件判断使用 JS 函数体字符串，此函数必须接受一个入参 rs 表示当前记录{}，必须返回 boolean
 *          !! 条件判断函数键名是 函数中使用到的所有字段名，用 , 分隔，用于快速判断当前条件是否需要执行
 *          !! 条件判断中使用到的所有字段（可以是 物理字段 和 计算字段）必须在本次查询的 字段列表中
 *          !! 如果本次查询的字段列表，不包含条件判断用到的字段，则当前条件判断直接跳过
 *          "highlight" => [
 *              # 满足条件的记录，改变显示的 颜色 或 背景色
 * 
 *              !! 默认第一个高亮条件是 软删除
 *              [
 *                  "where" => [
 *                      "enable" => "return rs.enable !== true;",
 *                  ],
 *                  "style" => [
 *                      "color" => "var(--color-red) !important",
 *                  ],
 *                  "level" => "disabled",
 *              ],
 * 
 *              [
 *                  # 要满足的条件
 *                  "where" => [
 *                      !! 多字段同时判断，默认使用 && 逻辑，可以手动指定 "OR" "AND"
 *                      "OR" => [
 *                          # 单个字段判断条件
 *                          "foo_bar" => "return rs.foo_bar > 100 && rs.foo_bar < 200;",
 *                          !! OR|AND 可以嵌套
 *                          "AND" => [
 *                              "jaz_a" => "return rs.jaz_a.includes('包含字符');",
 *                              "tom_b" => "return rs.tom_b <= Date.now() / 1000;",
 *                              !! 多个字段判断，键名是多个字段名以 , 分隔
 *                              "foo,bar" => "return rs.foo > 300 || rs.bar.includes('oo');",
 *                          ],
 *                      ],
 * 
 *                      !! 建议使用单个函数体，上面的条件可以写为：
 *                      "foo_bar,jaz_a,tom_b,foo,bar" => "return (rs.foo_bar > 100 && rs.foo_bar < 200) || (rs.jaz_a.includes('包含字符') && rs.tom_b <= Date.now() / 1000 && (rs.foo > 300 || rs.bar.includes('oo')));"
 *                  ],
 *                  # 满足条件后的 高亮样式
 *                  !! 这个 style 样式属于兜底样式，实际应由前端库 根据下面的 level 参数，来决定最终显示样式
 *                  "style" => [
 *                      # 颜色定义必须在前端库中已经预定义了，否则直接使用 hex
 *                      !! 可以使用 !important 标记
 *                      "color" => "var(--color-red) !important",
 *                      "backgroundColor" => "#ffdddd !important",
 *                      # 还可以使用其他 css 样式
 *                      "fontSize" => "1.5em !important",
 *                      "fontWeight" => "bold !important",
 *                  ],
 *                  # 当前记录的 高亮等级，应由对应的前端库来决定实际的 高亮样式
 *                  "level" => "warning",
 *              ],
 *              # 更多条件，排在后面，优先级更低
 *              [
 *                  "where" => ...,
 *                  "style" => ...,
 *                  "level" => ...,
 *              ],
 *          ],
 * 
 *      ],
 * 
 *      # form 表单组件参数
 *      "form" => [
 *          # 表单组件中 默认隐藏的 不显示的 字段名，只能是 物理字段
 *          !! 需要在表单中隐藏的字段，自增id，generator生成编码 这些字段是默认隐藏的，不需要手动指定
 *          "hide" => [ ... ],
 * 
 *          # 字段验证器
 *          "validator" => [
 *              # 物理字段名 --> 验证器方法名
 *              !! 前端库必须已定义对应的 验证器方法
 *              "foo_bar" => "requiredValidator",
 *              !! 极端情况下，可以直接指定函数体，前端使用 new Function() 调用
 *              !! 必须接受一个参数 val 表示当前字段值，必须返回 boolean
 *              "jaz_a" => "return val < 100 && val > 50;",
 *          ],
 * 
 *          # 各字段 inputer 输入组件的 默认 props
 *          !! 会根据各字段的 types 类型，生成对应的 inputer 组件，然后会合并这里定义的 props
 *          !! 合并后的 inputer 参数会保存到 各字段的 inputer 参数中，统一汇总到 $db->config->context[]
 *          "inputer" => [
 *              "foo_bar" => [
 *                  ... inputer 组件 默认参数 ...
 *              ],
 *              ...
 *          ],
 *      ],
 *  ]
 * !! 各 Frontend 前端兼容层子类，应实现此参数的解析方法，生成各前端库适配的 front 参数 以及 各字段的 inputer 参数
 * !! 汇总到 $db->config->context[] 并缓存
 * 
 * 
 * 
 */

namespace Spf\module\orm\config\model\parser;

use Spf\module\Orm;
use Spf\module\orm\OrmException;
use Spf\module\orm\Frontend;
//use Spf\module\orm\config\DbConfig;
use Spf\module\orm\config\model\Parser;
//use Spf\module\orm\config\model\parser\CreationSql;
use Spf\module\orm\Types;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Front extends Parser 
{
    /**
     * 要解析的 $origin 中 对应配置参数项目 的 默认数据结构
     * !! 子类如果需要，必须覆盖
     * 默认的 数据模型中的 front 参数
     * !! 使用的 front 前端 ui 库，会在 Orm::$current->config->ctx["frontComponentLib"] 中指定，
     * !! 将调用对应的 ui 前端库解析类，对 模型的 front 参数进行解析
     */
    protected $dftOption = [
        //
    ];

    /**
     * 解析过程中的 数据，这些数据最终将被 写入 $this->context 
     * 通常指定了 此解析器将要修改 $this->context 中的 哪些数据
     * !! 与 DbConfig::$exportModelConf[] 结构一致
     * !! 子类根据需要，指定此数据
     */
    protected $temp = [
        "front" => [
            //表格通用参数
            "table" => [
                //全局隐藏字段
                "hide" => [],
                //表格组件可选显示模式
                "mode" => [
                    //"mode_name" => $stdMode 结构
                ],
                //表格组件 行高亮
                "highlight" => [
                    // [ $stdHighlight 结构 ],
                    //...
                ]
            ],
            //表单通用参数
            "form" => [
                //表单中全局隐藏字段，物理字段，id generator 等字段默认包含
                "hide" => [],
                //全局 validator 字段验证器，物理字段
                "validator" => [
                    //"col_name" => "前端定义的 function 名称",
                    //"col_name" => "return val > 100;"
                ],
            ],

            //!! 针对各 Frontend 前端库，自有 front 参数，将与 上方 通用参数 合并输出
            "frontend" => [
                //"front_name" => []
            ],
        ],
    ];

    /**
     * 标准显示模式参数
     */
    protected $stdMode = [
        "title" => "",
        "columns" => [],
        "isClassic" => true,
        "component" => "",
        "props" => []
    ];

    /**
     * 标准高亮模式参数
     */
    protected $stdHighlight = [
        //此高亮条件判断使用到的 字段名列表，包含 计算字段
        "columns" => [],
        //此高亮条件判断的 JS 函数体，接受参数 rs，返回 boolean
        "function" => "",
        //可选兜底高亮样式
        "style" => [
            //"backgroundColor" => "var(...) !important"
        ],
        //前端库根据 level 自生成高亮样式
        "level" => "",
    ];
    


    /**
     * 解析入口
     * 解析 $this->origin 参数，将生成的最终参数 写入 $this->context 并返回
     * !! 必须实现，覆盖父类
     * @return Array 解析得到的 此数据模型(表)参数 []
     */
    public function parse()
    {
        //origin 参数
        $origin = $this->origin["front"] ?? [];

        //处理 table 通用参数
        $otable = $origin["table"] ?? [];
        $ttable = $this->temp["front"]["table"];
        //table.hide
        $ttable["hide"] = $this->parseHide($otable["hide"] ?? []);
        //table.mode
        $omode = $otable["mode"] ?? [];
        //!! 一定包含 default 默认模式
        if (!isset($omode["default"])) {
            $omode["default"] = Arr::extend($this->stdMode, [
                "title" => "默认模式",
            ]);
        }
        foreach ($omode as $mode => $mdc) {
            $tc = $this->parseMode($mode, $mdc);
            if (!Is::nemaso($tc)) continue;
            $ttable["mode"][$mode] = $tc;
        }
        //table.highlight
        $ohigh = $otable["highlight"] ?? [];
        if (!is_array($ohigh)) $ohigh = [];
        //!! 一定包含 软删除高亮模式
        array_unshift($ohigh, [
            "where" => [
                "enable" => "return rs.enable!==true;",
            ],
            "style" => [
                "color" => "var(--color-red) !important"
            ],
            "level" => "disabled",
        ]);
        foreach ($ohigh as $ohi) {
            $ohc = $this->parseHighlight($ohi);
            if (!Is::nemaso($ohc)) continue;
            $ttable["highlight"][] = $ohc;
        }
        $this->temp["front"]["table"] = $ttable;

        //处理 form 通用参数
        $oform = $origin["form"] ?? [];
        $tform = $this->temp["front"]["form"];
        //form.hide
        $tform["hide"] = $this->parseHide($oform["hide"] ?? [], true);
        //form.validator
        //!! 根据 context["special"]["required"] 中保存的字段列表，自动生成 validator 验证器
        $dftVali = $this->getDftValidator();
        //手动定义的 覆盖自动生成的
        $tform["validator"] = Arr::extend($dftVali, $oform["validator"] ?? []);
        $this->temp["front"]["form"] = $tform;

        /**
         * !! 调用 $orm->frontend 中所有保存的 前端兼容层实例，对 $temp 前端参数，进行个性化处理
         * 生成的针对 某个特定前端库的 参数，保存到 context["front"]["frontend"][front_name][...]
         */
        //已实例化的 Frontend 前端兼容层子类实例
        $frts = $this->orm->frontend;
        //准备处理后的 frontend 参数
        $frontend = [];
        //要传入 parse 方法的参数，包含了当前已解析的 context,temp,origin,...
        $params = $this->params();
        foreach ($frts as $frtn => $frt) {
            if (!($frt instanceof Frontend)) continue;
            //!! 调用 parseFront 方法
            $frtc = $frt->parseFront($params);
            if (Is::nemaso($frtc)) {
                $frontend[$frtn] = $frtc;
            }
        }
        //写回 temp
        $this->temp["front"]["frontend"] = $frontend;

        //var_dump($this->dbn);
        //var_dump($this->modk);
        //var_dump($this->temp);
        //exit;

        //写入 context
        $this->setCtx($this->temp);

        return $this->context;
    }



    /**
     * 工具方法
     */

    /**
     * 获取已经解析得到的 columns 字段列表
     * @return Array
     *  [
     *      "columns" => [ ... 物理字段名列表 ... ],
     *      "getters" => [ ... 计算字段名列表 ... ],
     *  ]
     */
    protected function getParsedColumns()
    {
        $cols = $this->context["columns"];
        $colp = $this->context["column"];
        $rtn = [
            "columns" => [],
            "getters" => [],
        ];
        foreach ($cols as $col) {
            $colc = $colp[$col] ?? null;
            if (!Is::nemaso($colc)) continue;
            if ($colc["isGetter"]===true) {
                if (!in_array($col, $rtn["getters"])) $rtn["getters"][] = $col;
            } else {
                if (!in_array($col, $rtn["columns"])) $rtn["columns"][] = $col;
            }
        }
        return $rtn;
    }

    /**
     * 根据 字段类型，以及 special 参数，生成默认的 validator 前端表单验证器 JS 函数体
     * @return Array 
     *  [
     *      "col_name" => "JS 函数体，接受 val 参数，返回 boolean",
     *      ...
     *  ]
     */
    protected function getDftValidator()
    {
        //validator 只针对物理字段
        $cols = $this->getParsedColumns()["columns"];
        if (!Is::nemidx($cols)) return [];

        //ctx
        $ctx = $this->context;
        $spec = $ctx["special"];
        $reqs = $spec["required"];

        //rtn
        $rtn = [];

        foreach ($cols as $col) {
            //required 验证器
            if (in_array($col, $reqs)) {
                //!! 前端库必须根据字段类型，实现空值判断方法
                //!! 然后在 空值判断函数基础上，事项 必填字段验证器
                $rtn[$col] = "requiredValidator";
            }
        }

        return $rtn;
    }

    /**
     * 解析 table|form 参数中的 hide 项目
     * @param Array $hide 配置参数
     * @param Bool $inForm 是否是表单隐藏字段，表单中隐藏字段默认要包含 id|generator 类型的字段
     * @return Array 处理后的 hide 字段列表
     */
    protected function parseHide($hide, $inForm = false)
    {
        if (!Is::nemidx($hide)) $hide = [];

        //全部 物理字段，hide 只能是物理字段
        $acols = $this->getParsedColumns();
        $cols = $acols["columns"];
        $spec = $this->context["special"];

        //表单隐藏字段 特殊处理，自动包含 id|generator
        if ($inForm===true) {
            $ids = $spec["id"] ?? [];
            $gens = $spec["generator"] ?? [];
            if (Is::nemidx($ids)) $hide = array_merge($hide, $ids);
            if (Is::nemidx($gens)) $hide = array_merge($hide, $gens);
            //去重
            if (!empty($hide)) $hides = array_merge(array_flip(array_flip($hide)));
        }

        if (!Is::nemidx($hide)) return [];
        $hide = array_filter([...$hide], function($col) use ($cols) {
            return in_array($col, $cols);
        });
        return array_merge([], $hide);
    }

    /**
     * 解析单个显示模式参数，生成 $stdMode 数据格式
     * @param String $mode 显示模式名
     * @param Array $mdc 显示模式的配置参数
     * @return Array|null 返回 $stdMode 结构的 参数
     */
    protected function parseMode($mode, $mdc = [])
    {
        if (!Is::nemstr($mode) || !Is::nemaso($mdc)) return null;
        //所有字段名
        $acols = $this->getParsedColumns();
        
        //准备返回数据结构
        $rtn = Arr::copy($this->stdMode);

        //title
        $rtn["title"] = Is::nemstr($mdc["title"]) ? $mdc["title"] : "$mode 模式";

        //解析 columns 参数
        $cols = $mdc["columns"] ?? null;
        if (!Is::nemidx($cols)) $cols = [];
        if (!Is::nemidx($cols)) {
            //未指定 columns 或 columns = []
            $rtn["columns"] = $acols["columns"];
        } else {
            //展开 *
            if (false !== ($idx = array_search("*", $cols))) {
                array_splice($cols, $idx, 1);
                $rtn["columns"] = array_merge($rtn["columns"], $acols["columns"]);
            }
            //展开 $
            if (false !== ($idx = array_search("$", $cols))) {
                array_splice($cols, $idx, 1);
                $rtn["columns"] = array_merge($rtn["columns"], $acols["getters"]);
            }

            //其他字段

            //收集关联表
            $rtn["join"] = [];
            //模型参数中的 join 关联表集合
            $jc = $this->context["join"];
            $jtbs = $jc["tables"] ?? [];
            $jps = $jc["param"] ?? [];
            //依次处理剩余的 定义字段
            foreach ($cols as $col) {
                //指定字段是主表字段，在 columns 集合中
                if (isset($this->context["column"][$col])) {
                    $rtn["columns"][] = $col;
                    continue;
                }

                //指定字段是 关联表字段，一定包含 . 字符
                if (strpos($col, ".")!==false) {
                    $cola = explode(".", $col);
                    if (count($cola)<2) continue;
                    //收集关联表 表名 或 别名
                    $jtbn = $cola[0];
                    $jcol = $cola[1];
                    if (!Is::nemstr($jtbn) || !Is::nemstr($jcol)) continue;
                    //!! mode.columns[] 参数中的 关联表名 默认是真实表名
                    //!! 如果关联表是自身，或者同一张表多次关联，则使用 alias.col_name
                    if (!in_array($jtbn, $jtbs)) continue;
                    //检查 关联表参数中 是否定义了 alias 别名
                    $jp = $jps[$jtbn] ?? [];
                    if (!Is::nemaso($jp)) continue;
                    if (isset($jp["alias"]) && $jp["alias"]!=="") {
                        //关联表存在别名，则收集别名
                        $jtbn = $jp["alias"];
                    }
                    //收集 表名 别名
                    if (!in_array($jtbn, $rtn["join"])) $rtn["join"][] = $jtbn;
                    //收集字段名
                    $rtn["columns"][] = "$jtbn.$jcol";
                }
            }
            //$rtn["columns"] = array_merge($rtn["columns"], $cols);
        }
        //去重
        $rtn["columns"] = array_merge(array_flip(array_flip($rtn["columns"])));

        //isClassic
        $rtn["isClassic"] = is_bool($mdc["isClassic"]) ? $mdc["isClassic"] : true;

        //仅当 isClassic == false 时，需要处理 component|props 参数
        if ($rtn["isClassic"] !== true) {
            $rtn["component"] = $mdc["component"] ?? "";
            $rtn["props"] = Arr::extend([
                //前端组件参数 默认包含
                "dbXpath" => $this->dbn."/".$this->modk,
            ], $mdc["props"] ?? []);
        }

        return $rtn;
    }

    /**
     * 解析单个 highlight 高亮条件
     * @param Array $high 高亮参数
     * @return Array|null 返回 $stdHighlight 结构的 参数
     */
    protected function parseHighlight($high = [])
    {
        if (!Is::nemaso($high)) return null;
        //where
        $where = $high["where"] ?? [];
        if (!Is::nemaso($where)) return null;

        //准备返回结果
        $rtn = Arr::copy($this->stdHighlight);
        
        if (Is::nemaso($where["OR"])) {
            //TODO：递归解析 where 参数中可能存在的 OR | AND 嵌套结构
            //...
            return null;
        } else {
            //没有指定 OR|AND 时，默认 以 && 方式合并所有字段判断
            $wks = array_keys($where);

            //单条件，直接输出
            if (count($wks)===1) {
                $rtn["columns"] = explode(",", $wks[0]);
                $rtn["function"] = $where[$wks[0]];
                $rtn["level"] = $high["level"] ?? str_replace(",","_",$wks[0]);
                $rtn["style"] = $high["style"] ?? [];
                return $rtn;
            }

            //多字段条件，以 && 方式合并函数体
            $js = [];
            foreach ($where as $ck => $fn) {
                $rtn["columns"] = array_merge($rtn["columns"], explode(",", $ck));
                //函数体字符串处理
                //去除 ;
                $fn = trim(trim(trim($fn), ";"));
                $fn = preg_replace("/\s+/", " ", $fn);
                $fn = substr($fn, strlen("return "));
                //如果函数体字符串包含 || 则加 () 包裹
                $fn = strpos($fn, "||")!==false ? "($fn)" : $fn;
                //放入 js 函数字符串列表
                $js[] = $fn;
            }
            //多个 JS 函数体 以 && 方式合并
            $rtn["function"] = "return ".implode(" && ", $js).";";
            //使用到的 columns 去重
            $rtn["columns"] = array_merge(array_flip(array_flip($rtn["columns"])));
            $rtn["level"] = $high["level"] ?? implode("_", $rtn["columns"]);
            $rtn["style"] = $high["style"] ?? [];

            return $rtn;
        }
    }

    /**
     * 递归解析 highlight where 参数中 OR|AND
     */
}