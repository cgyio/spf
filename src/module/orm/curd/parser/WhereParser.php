<?php
/**
 * SPF-Orm 数据库操作模块
 * Curd 操作 where 条件解析器
 * 
 * Medoo(2.x) 库 where 参数用法：   where 参数是 [] 数组
 * 
 *      比较运算符：
 *          [>] [<] [>=] [<=] [!] [~] [!~]      常用的
 *          [<>] [><]                           BETWEEN | NOT BETWEEN   范围内 | 范围外
 *          [in] [not in]                       IN | NOT IN 
 *          [REGEXP]                            REGEXP
 * 
 *          用法：
 *          [
 *              !! 比较的值是 [] 则展开为 IN 
 *              "name" => ["tom", "jerry"],             --> WHERE `name` IN ('tom', 'jerry')
 *              "name[!]" => ["tom", "jerry"],          --> WHERE `name` NOT IN ('tom', 'jerry')
 * 
 *              !! 模糊查询下 比较值是 [] 则展开，默认 OR 
 *              "name[~]" => ["foo", "bar"],            --> WHERE (`name` LIKE '%foo%' OR `name` LIKE '%bar%')
 *              !! 可以手动指定 展开为 AND|OR
 *              "name[~]" => [                          --> WHERE (`name` LIKE '%foo%' AND `name` LIKE '%bar%')
 *                  "AND" => ["foo", "bar"]
 *              ]
 *              !! 模糊查询支持特殊写法
 *              "name[~]" => "%foo",        --> xxxfoo
 *              "name[~]" => "foo_",        --> foox,fooy,foob,...
 *              "name[~]" => "[BCR]at",     --> Bat,Cat,Rat
 *              "name[~]" => "[!BCR]at",    --> Eat,Fat,Hat,...
 * 
 *              !! 范围运算，也适用于 时间字符串
 *              "uid[<>]" => ["2025-01-01", 2025-12-31],    --> WHERE `uid` BETWEEN '2025-01-01' AND '2025-12-31'
 * 
 *              !! 正则
 *              "name[REGEXP]" => "[a-z0-9_]{4,}",          --> WHERE `name` REGEXP '[a-z0-9_]{4,}'
 *          ]
 * 
 *      条件间 逻辑运算 AND OR
 *          !! 默认使用 AND 逻辑
 *              [
 *                  "name[~]" => "foo",
 *                  "uid[<>]" => [100, 200]
 *              ]   --> WHERE `name` LIKE '%foo%' AND `uid` BETWEEN 100 AND 200
 *          !! 可以显式指定 AND OR
 *              "AND" => [
 *                  "name[~]" => "foo",
 *                  "uid[<>]" => [100, 200]
 *              ]   --> WHERE `name` LIKE '%foo%' AND `uid` BETWEEN 100 AND 200
 * 
 *              "OR" => [
 *                  "name[~]" => "foo",
 *                  "uid[<>]" => [100, 200]
 *              ]   --> WHERE `name` LIKE '%foo%' OR `uid` BETWEEN 100 AND 200
 *          !! 显式指定时，可在 AND|OR 键名后 加 #标记 以区分多个条件组
 *              [
 *                  "OR #任意标记" => [
 *                      "name[~]" => "foo",
 *                      "uid[<>]" => [100, 200]
 *                  ],
 *                  "OR #另一个条件组" => [
 *                      "age[>=]" => 18,
 *                      "idcard" => 1
 *                  ]
 *              ]   --> WHERE (`name` LIKE '%foo%' OR `uid` BETWEEN 100 AND 200) AND (`age` >= 18 OR `idcard` = 1)
 *          !! 可任意嵌套
 *              [
 *                  "OR #1" => [
 *                      "AND #11" => [
 *                          ...
 *                      ],
 *                      "cdtk" => "cdtv",
 *                  ],
 *                  ...
 *              ]
 * 
 *      GROUP HAVING 用法
 *          [
 *              !! 单列分组
 *              "GROUP" => "col_name",
 * 
 *              !! 多列分组
 *              "GROUP" => [ "col_a", "col_b" ],
 * 
 *              !! HAVING 筛选
 *              "HAVING" => [
 *                  "col[>]" => 100
 *              ],
 *          ]
 */

namespace Spf\module\orm\curd\parser;

use Spf\module\Orm;
use Spf\module\orm\Db;
use Spf\module\orm\Model;
use Spf\module\orm\Curd;
use Spf\module\orm\curd\CurdParser;
use Spf\module\orm\curd\parser\JoinParser;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Medoo\Medoo;

class WhereParser extends CurdParser 
{
    //解析得到的 where 参数
    public $where = [];

    //直接传入的 Medoo 格式的 where 参数，直接缓存
    public $raw = [];

    //比较运算符
    protected $logics = [
        ">", "<", ">=", "<=", "!", "~", "!~",
        "<>", "><",
        "in", "not in",
        "REGEXP"
    ];

    //可选的 where 参数中的 其他相关参数
    protected $extras = [
        "order", "limit", "match", "group", "having",
    ];


    /**
     * 初始化 curd 参数
     * !! 子类必须实现 !!
     * @return CurdParser $this
     */
    public function initParam()
    {
        //将此模型可能存在的 prefilter 前置查询条件 写入 raw 参数中
        return $this->setPrefilter();
    }

    /**
     * 设置 curd 参数
     * !! 子类必须实现 !!
     * 构造 medoo 查询 where 参数
     * 
     * 参数形式：
     *      # 单条件  针对字段
     *      $wp->setParam("col_a", "!~", [...])                 --> $this->where["main.col_a[!~]" = [...]
     *      $wp->setParam("table(alias).col_b", null, 123)      --> $this->where["alias.col_b"] = 123
     *      $wp->setParam("table(alias).col_b", 123)            --> $this->where["alias.col_b"] = 123
     * 
     *      # 单条件  extra 参数：limit,order,match,group,having
     *      $wp->setParam("order", "table(alias).col_c", "desc")
     *      $wp->setParam("order", [
     *          "table(alias).col_c" => "desc"
     *      ])
     *          --> $this->where = Arr::extend( $this->where, [ "ORDER" => [ "alias.col_c" => "DESC" ] ] )
     * 
     *      $wp->setParam("limit", [100,200])                   --> $this->where["LIMIT"] = [100,200]
     *      $wp->setParam("limit", 100)                         --> $this->where["LIMIT"] = 100
     * 
     *      $wp->setParam("match", [
     *          "columns" => ["col_a", "table(alias).col_b"],
     *          "keyword" => "foobar"
     *      ])
     *          --> $this->where = Arr::extend( $this->where, [ "MATCH" => [ "columns" => [...], "keyword" => "foobar" ] ] )
     * 
     *      $wp->setParam("group", ["col_a", "table.col_b"])    --> $this->where["GROUP"] = ["col_a", "table.col_b"]
     *      $wp->setParam("group", "col_a")                     --> $this->where["GROUP"] = "col_a"
     * 
     *      $wp->setParam("having", "table(alias).col_c[>=]", 100)
     *      $wp->setParam("having", [
     *          "table(alias).col_c[>=]" => 100
     *      ])
     *          --> $this->where = Arr::extend( $this->where, [ "HAVING" => [ "alias.col_c[>=]" => 100 ] ] )
     * 
     *      # 多条件 批量设置
     *      $wp->setParam([
     *          "table(alias).col_a[>=]" => 123,
     *          "table.col_b" => [1,2,3],
     *          "OR #123" => [
     *              ...
     *          ],
     *          "order" => [ ... ],
     *          "limit" => [ ... ],
     *          "group" => [ ... ],
     *          "having" => [ ... ],
     *          ...
     *      ])
     * 
     *      !! 传入 raw 形式的 Medoo where 参数，第一个参数 必须是 true
     *      !! raw 形式参数，不会自动处理 表名前缀 以及 表名别名，不会自动添加相关字段到 column 参数中
     *      !! 将直接传递给 Medoo 方法
     *      $wp->setParam(
     *          !! 第一个参数必须是 true
     *          true,
     *          [
     *              "alias.col_a" => ...,
     *              "table.col_b[!]" => ...,
     *              "main.col_c" => ...,
     *              "group" => [ ... ],
     *              ...
     *          ]
     *      )
     * 
     * @param Mixed $param 要设置的 curd 参数
     * @return CurdParser $this
     */
    public function setParam($param=null)
    {
        $args = func_get_args();
        if (empty($args) || (count($args)===1 && is_null($args[0]))) return $this;

        //标记 传入了 raw
        $isRaw = count($args)===2 && $args[0]===true && Is::nemaso($args[1]);
        //标记 批量设置 where 参数
        $isBatch = count($args)===1 && Is::nemaso($args[0]);
        //标记 设置 单条 where 参数
        $isSingle = count($args)>=2 && Is::nemstr($args[0]);

        //无效参数
        if (!$isRaw && !$isBatch && !$isSingle) return $this;

        //设置 raw 参数
        if ($isRaw) {
            $this->raw = Arr::extend($this->raw, $args[1]);
            return $this;
        }

        //准备最终要写入 where 参数的 数据
        $wps = [];
        if ($isSingle) {
            //单条 参数，也转为 批量写入
            if (in_array(strtolower($args[0]), $this->extras)) {
                //extra 参数
                $ek = strtoupper($args[0]);
                if (!isset($wps[$ek])) $wps[$ek] = [];
                if (count($args)===2) {
                    if (Is::nemaso($args[1])) {
                        // setParam("order", [ "col_a"=>"desc", "col_b"=>"asc", ... ])
                        $wps[$ek] = Arr::extend($wps[$ek], $args[1]);
                    } else {
                        // setParam("limit", [100,200])
                        // setParam("limit", 100)
                        $wps[$ek] = $args[1];
                    }
                } else if (count($args)>2) {
                    if (Is::nemstr($args[1])) {
                        // setParam("order", "table.col_a", "desc)
                        // setParam("having", "col_a[>]", 100)
                        $wps[$ek][$args[1]] = $args[2];
                    }
                }
                if (empty($wps[$ek])) unset($wps[$ek]);
            } else {
                //普通字段参数
                $colk = $args[0];
                $logc = count($args)===2 ? "=" : ((Is::nemstr($args[1]) && in_array($args[1], $this->logics)) ? $args[1] : "=");
                $cval = count($args)===2 ? $args[1] : $args[2];
                $wpk = $colk.($logc==="=" ? "" : "[".$logc."]");
                $wps = Arr::extend($wps, [
                    $wpk => $cval
                ]);
            }
        } else if ($isBatch) {
            //批量写入的 参数，直接使用
            $wps = $args[0];
        }

        if (!Is::nemaso($wps)) return $this;

        //自动处理 表名前缀，表名别名，将关联的字段添加到 column 参数中
        $wps = $this->autoParseParams($wps);

        //合并到 $this->where[] 中
        $this->where = Arr::extend($this->where, $wps);

        return $this;
    }

    /**
     * 重置 curd 参数 到初始状态
     * !! 子类必须实现 !!
     * @return CurdParser $this
     */
    public function resetParam()
    {
        $this->where = [];
        $this->raw = [];
        return $this->initParam();
    }

    /**
     * 执行 curd 操作前 返回处理后的 curd 参数
     * !! 子类必须实现 !!
     * @return Mixed curd 操作 medoo 参数，应符合 medoo 参数要求
     */
    public function getParam()
    {
        $where = $this->where;
        $raw = $this->raw;
        if (empty($where) && empty($raw)) return [];
        //$where = $this->withTbnPre($where);
        $where = Arr::extend($where, $raw);
        return $where;
    }

    /**
     * 将此模型可能存在的 prefilter 前置查询条件 写入 raw 参数中
     * !! prefilter 条件必须是标准的 Medoo where 参数形式
     * !! 在 initParam 中执行
     * @return CurdParser $this
     */
    protected function setPrefilter()
    {
        //!! 向 where 参数中增加 此数据模型的 prefilter 参数
        $pref = $this->conf("prefilter");
        if (Is::nemaso($pref)) {
            //!! 对 prefilter 执行自动处理 表名前缀，表明别名，自动添加到 column 字段
            //$this->raw = Arr::extend($this->raw, $pref);
            return $this->setParam($pref);
        }
        return $this;
    }



    /**
     * 构造 where 参数 方法
     */

    /**
     * 构造 where 参数
     * !! 通过 curd 实例的 __call 方法调用： $curd->whereFooBar(...) --> $curd->whereParser->whereCol("foo_bar", ...)
     * !! 传入的字段名可能包含 表名别名前缀，需要处理
     * !! 如果传入的 字段名字符串不能被正确解析，返回 null
     * whereCol("table_col_a", "~", ["foo","bar"])  -->  setParam( "table.col_a", "~", ["foo","bar"] ) 
     * whereCol("alias_col_a", "foo")               -->  setParam( "table(alias).col_a", null, "foo" )
     * @param String $key 列名称
     * @param Array $args 列参数
     * @return CurdParser $this
     */
    public function whereCol($key, ...$args)
    {
        //解析传入的 字段名字符串，生成完整的 表名(别名).字段名 字符串
        $colk = $this->parseKababColumnDef($key);
        //!! 如果传入的 字段名字符串不能被正确解析，返回 null
        if (!Is::nemstr($colk)) return null;

        if (count($args)<2) array_unshift($args, null);
        return $this->setParam($colk, ...$args);
    }

    /**
     * 构造 where 参数
     * 同时设置多个字段的 查询值
     * 参数形式：
     *      !! 默认是 AND 方式
     *      whereCols( [col1,col2], "~", val)           --> setParam([ "col1[~]" => val, "col2[~]" => val ])
     *      !! 可以手动指定 OR
     *      whereCols("OR #123", [col1,col2], val)      --> setParam([ "OR #123" => [ "col1" => val, "col2" => val ]])
     * @param Mixed $args
     * @return CurdParser $this
     */
    public function whereCols(...$args)
    {
        if (empty($args)) return $this;
        $where = [];

        //手动标记 AND|OR
        $aor = $this->isAndOr($args[0]);
        if ($aor!==false) $aork = array_shift($args);
        if (empty($args) || !Is::nemidx($args[0]) || count($args)<2) return $this;

        //拆分 比较运算符 和 参数值
        if (Is::nemstr($args[1]) && in_array($args[1], $this->logics)) {
            $logic = "[".$args[1]."]";
            if (!isset($args[2])) return $this;
            $val = $args[2];
        } else {
            $logic = "";
            $val = $args[1];
        }

        //生成 参数数组
        foreach ($args[0] as $colk) {
            $where[$colk.$logic] = $val;
        }

        //如果是 OR 则包裹
        if ($aor==="OR") {
            $where = [
                $aork => $where
            ];
        }
        
        return $this->setParam($where);
    }

    /**
     * 构造 where 参数
     * 筛选 某些字段
     * [
     *      "column" => [
     *          "logic" => "=/>/</>=/<=/<>/></! 等于/大于/小于/不小于/不大于/之内/之外/不等于",
     *          "value" => ""/[]
     *      ]
     * ]
     * @param Array $filter 筛选参数
     * @return CurdParser $this
     */
    public function filter($filter)
    {
        $fs = [];
        foreach ($filter as $coln => $fc) {
            $flgc = $fc["logic"] ?? "=";
            $fv = $fc["value"] ?? null;
            if (is_null($fv) || $fv=="" || (Is::indexed($fv) && count($fv)<=0)) continue;
            if (in_array($flgc, ["=","=="])) {
                $ck = $coln;
            } else {
                $ck = $coln."[".$flgc."]";
            }
            $fs[$ck] = $fv;
        }
        if (empty($fs)) return $this;
        return $this->setParam($fs);
    }

    /**
     * 构造 where 参数
     * 关键字搜索
     * sk("sk,sk,...")
     * @param String $sk 关键字，可有多个，逗号隔开
     * @return CurdParser $this
     */
    public function sk($sk)
    {
        if (!Is::nemstr($sk)) return $this;
        $ska = explode(",", trim(str_replace("，",",",$sk), ","));

        //主表可以搜索的 字段数组
        $scols = $this->conf("special/search");
        if (!Is::nemidx($scols)) $scols = [];

        //需要调用 JoinParser
        $jp = $this->curd->joinParser;
        if ($jp instanceof JoinParser) {
            //当前是否启用 join
            $joining = $jp->needJoin();
            //如果启用了 join 则将 $jp->current 中所有的关联表中的 可搜索字段，也加入 $scols
            if ($joining===true && Is::nemidx($jp->current)) {
                foreach ($jp->current as $tbk) {
                    $tbc = $jp->param[$tbk];
                    $tbn = $tbc["table"];
                    $tbscols = $this->db->config->ctx("model/$tbn/special/search");
                    if (!Is::nemidx($tbscols)) continue;
                    foreach ($tbscols as $tbscol) {
                        $scols[] = $tbn.($tbc["alias"]==="" ? "" : "(".$tbc["alias"].")").".".$tbscol;
                    }
                }
            }
        }

        //没有可搜索字段
        if (!Is::nemidx($scols)) return $this;

        //调用 whereCols
        return $this->whereCols("OR #search keywords", $scols, "~", count($ska)===1 ? $ska[0] : $ska);
    }

    /**
     * 构造 where 参数
     * limit 参数 
     * @param Array $limit 与 medoo limit 参数格式一致
     * @return CurdParser $this
     */
    public function limit($limit=[])
    {
        if ((is_numeric($limit) && $limit>0) || Is::nemidx($limit)) {
            $this->setParam("limit", $limit);
        }
        return $this;
    }

    /**
     * 构造 where 参数
     * 分页加载
     * @param Int $ipage 要加载的页码，>=1
     * @param Int $pagesize 每页记录数，默认 100
     */
    public function page($ipage=1, $pagesize=100)
    {
        $ipage = $ipage<1 ? 1 : $ipage;
        if ($ipage==1) {
            return $this->limit($pagesize);
        }
        $ps = ($ipage-1)*$pagesize;
        return $this->limit([$ps, $pagesize]);
    }

    /**
     * 构造 where 参数
     * order 参数 
     *      order("col_a")                  --> setParam("order", "col_a", "ASC")
     *      order("col_a", "asc|desc")      --> setParam("order", "col_a", "ASC|DESC" )
     *      order([ "table.col_a", "alias.col_b" => "desc" ])
     *          --> setParam("order", [ ... ])
     * @param Array $order 与 medoo order 参数格式一致
     * @return CurdParser $this
     */
    public function order(...$args)
    {
        if (empty($args)) return $this;
        $arglen = count($args);

        if ($arglen===1) {
            if (Is::nemstr($args[0])) return $this->setParam("order", $args[0], "ASC");
            if (Is::nemaso($args[0])) {
                //asc|desc --> ASC|DESC
                return $this->setParam("order", $args[0]);
            }
            return $this;
        }

        if ($arglen>=2  && Is::nemstr($args[0]) && in_array(strtolower($args[1]), ["asc","desc"])) {
            return $this->setParam("order", $args[0], strtoupper($args[1]));
        }
        
        return $this;
    }

    /**
     * 构造 where 参数
     * !! 由 curd 实例 __call 方法调用： $curd->orderFooBar("desc")  -->  $curd->whereParser->orderCol("foo_bar", "desc")
     * !! 传入的字段名可能包含 表名别名前缀，需要处理
     *      $curd->orderTableColA(...)
     *          --> orderCol("table_col_a", ...)    --> order("table.col_a", ...)
     *      $curd->orderAliasColA(...)
     *          --> orderCol("alias_col_a", ...)    --> order("table(alias).col_a", ...)
     * !! 如果传入的 字段名字符串不能被正确解析，返回 null
     * @param String $key 列名称
     * @param Array $args 列参数
     * @return CurdParser $this
     */
    public function orderCol($key, ...$args)
    {
        //解析传入的 字段名字符串，生成完整的 表名(别名).字段名 字符串
        $colk = $this->parseKababColumnDef($key);
        //!! 如果传入的 字段名字符串不能被正确解析，返回 null
        if (!Is::nemstr($colk)) return null;

        return $this->order($colk, ...$args);
    }

    /**
     * 构造 where 参数
     * match 参数 全文搜索
     *      match(["columns"=>[...], "keyword"=>"..."])
     * @param Array $match 与 medoo match 参数格式一致
     * @return CurdParser $this
     */
    public function match($match=[])
    {
        if (!Is::nemaso($match)) {
            $this->setParam("match", $match);
        }
        return $this;
    }

    /**
     * 构造 where 参数
     * group 参数
     * @param Array $group 与 medoo group 参数格式一致
     * @return CurdParser $this
     */
    public function group($group=[])
    {
        if (Is::nemstr($group) || Is::nemidx($group)) {
            $this->setParam("group", $group);
        }
        return $this;
    }

    /**
     * 构造 where 参数
     * having 参数
     * @param Array $having 与 medoo having 参数格式一致
     * @return CurdParser $this
     */
    public function having($having=[])
    {
        if (Is::nemaso($having)) {
            $this->setParam("having", $having);
        }
        return $this;
    }



    /**
     * 工具方法
     */

    /**
     * 自动处理 where 参数中的 字段的表名前缀，表名别名，将关联的字段添加到 column 参数中
     * @param Array $wps where 参数
     *  [
     *      "col_a" => "",
     *      "table(alias).col_a[>=]" => 123,
     *      "table.col_b" => [1,2,3],
     *      "OR #123" => [
     *          ...
     *      ],
     *      "order" => [ ... ],
     *      "limit" => [ ... ],
     *      "group" => [ ... ],
     *      "having" => [ ... ],
     *      ...
     *  ]
     * @return Array 处理后的 自动增加表名前缀的 where 参数
     */
    public function autoParseParams($wps=[])
    {
        if (!Is::nemaso($wps)) return [];
        //主表名
        $modk = $this->model::$modk;
        //joinParser
        $jp = $this->curd->joinParser;
        //columnParser
        $cp = $this->curd->columnParser;

        //!! 如果当前 curd 未开启 join 关联表查询，直接返回，不用处理
        //if ($jp->needJoin()!==true) return $wps;

        //收集所有关联的 字段
        $cols = [];

        //处理后的 参数
        $rtn = [];

        foreach ($wps as $wk => $wv) {

            //extra 参数
            if (in_array(strtolower($wk), $this->extras)) {
                $wk = strtoupper($wk);
                //LIMIT
                if ($wk==="LIMIT") {
                    $rtn["LIMIT"] = $wv;
                    continue;
                }
    
                //MATCH
                if ($wk==="MATCH") {
                    $mcols = [];
                    if (isset($wv["columns"]) && Is::indexed($wv["columns"])) {
                        foreach ($wv["columns"] as $mcol) {
                            $mcolc = $this->parseColumnDef($mcol);
                            if (!Is::nemaso($mcolc)) continue;
                            $mcols[] = $mcolc["column"];
                            //收集关联字段
                            $cols[] = $mcolc["addcol"];
                        }
                        unset($wv["columns"]);
                    }
                    $wv["columns"] = $mcols;
                    $rtn = Arr::extend($rtn, [
                        "MATCH" => $wv
                    ]);
                    continue;
                }
    
                //ORDER
                if ($wk==="ORDER") {
                    if (!Is::nemarr($wv)) continue;
                    $odrs = [];
                    foreach ($wv as $k => $v) {
                        if (is_numeric($k) && Is::nemstr($v)) {
                            $colc = $this->parseColumnDef($v);
                            $isidx = true;
                        } else if (Is::nemstr($k)) {
                            $colc = $this->parseColumnDef($k);
                            $isidx = false;
                        }
                        if (!Is::nemaso($colc)) continue;
                        if ($isidx) {
                            $odrs[] = $colc["column"];
                        } else {
                            $odrs[$colc["column"]] = $v;
                        }
                        //收集字段
                        $cols[] = $colc["addcol"];
                    }
                    $rtn = Arr::extend($rtn, [
                        "ORDER" => $odrs
                    ]);
                    continue;
                }
    
                //GROUP
                if ($wk==="GROUP") {
                    if (!Is::nemidx($wv) && !Is::nemstr($wv)) continue;
                    $gcols = Is::nemstr($wv) ? [$wv] : $wv;
                    $gps = [];
                    foreach ($gcols as $gcol) {
                        $colc = $this->parseColumnDef($gcol);
                        if (!Is::nemaso($colc)) continue;
                        $gps[] = $colc["column"];
                        //收集字段
                        $cols[] = $colc["addcol"];
                    }
                    $rtn = Arr::extend($rtn, [
                        "GROUP" => $gps
                    ]);
                    continue;
                }
    
                //HAVING
                if ($wk==="HAVING") {
                    if (!Is::nemaso($wv)) continue;
                    //递归调用，相关字段自动添加到 column 参数中
                    $hvs = $this->autoParseParams($wv);
                    $rtn = Arr::extend($rtn, [
                        "HAVING" => $hvs
                    ]);
                    continue;
                }

                continue;
            }

            //AND|OR
            //"AND" | "OR" | "AND #..." | "OR #..."
            if ($this->isAndOr($wk)!==false) {
                if (!Is::nemaso($wv)) continue;
                //递归调用，相关字段自动添加到 column 参数中
                $wvs = $this->autoParseParams($wv);
                $rtn = Arr::extend($rtn, [
                    $wk => $wvs
                ]);
                continue;
            }

            //普通字段的 where 参数
            if (Is::nemstr($wk)) {
                $colc = $this->parseColumnDef($wk);
                if (!Is::nemaso($colc)) continue;
                $ck = $colc["column"].($colc["logic"]==="=" ? "" : "[".$colc["logic"]."]");
                $rtn[$ck] = $wv;
                //收集字段
                $cols[] = $colc["addcol"];
                continue;
            }
        }

        //!! 自动将 where 参数中的 相关字段 添加到 column 参数中
        if (Is::nemidx($cols)) {
            $cp->setParam(...$cols);
        }

        //返回处理后的 where 参数
        return $rtn;
    }

    /**
     * 处理 表名(别名).字段名[logic] 形式的 字段名定义形式
     * @param String $cold 字段名定义字符串
     * @return Array|null
     *  [   
     *      # 原始字符串
     *      "origin" => $cold,
     * 
     *      # 处理后的 字段名 以 别名或表名 作为前缀
     *      "column" => "table.colk",
     * 
     *      # 比较运算符 默认 =  在 $this->logics[] 中定义
     *      "logic" => "=",
     * 
     *      # 额外的参数
     *      "table" => "真实表名",
     *      "alias" => "可能存在的别名，默认 空字符串",
     *      "addcol" => "向 column 参数中添加字段的写法 table(alias).column"
     *  ]
     */
    public function parseColumnDef($cold)
    {
        if (!Is::nemstr($cold)) return null;
        $cold = trim(trim($cold), ".");

        //不含 表名前缀的，直接作为主表字段
        $modk = $this->model::$modk;
        if (strpos($cold, ".")===false) $cold = $modk.".".$cold;

        //正则匹配
        $mt = preg_match(
            "/^([a-zA-Z0-9_]+)\s?(\(\s?[a-zA-Z0-9_]+\s?\))?\.([a-zA-Z0-9_*$]+)\s?(\[\s?[a-zA-Z <>=!~]+\s?\])?$/",
            $cold,
            $matches
        );
        if ($mt!==1) return null;
        $ma = array_slice($matches, 1);
        $tbn = $ma[0];
        $ali = Is::nemstr($ma[1]) ? trim(substr($ma[1], 1,-1)) : "";
        $pre = $ali==="" ? $tbn : $ali;
        $col = $ma[2];
        $log = Is::nemstr($ma[3]) ? trim(substr($ma[3], 1,-1)) : "=";
        if (!in_array($log, $this->logics)) $log = "=";
        return [
            "origin" => $cold,
            "column" => $pre.".".$col,
            "logic" => $log,
            "table" => $tbn,
            "alias" => $ali,
            "addcol" => $tbn.($ali===""?"":"(".$ali.")").".".$col,
        ];
    }

    /**
     * 处理 curd 实例通过 __call 方法传入的 字段名，解析可能存在的 表名别名前缀
     * 生成完整的 表名(别名).字段名 字符串
     * @param String $cold
     * @return String|null 完整的 表名(别名).字段名 字符串
     */
    public function parseKababColumnDef($cold=null)
    {
        if (!Is::nemstr($cold)) return null;
        //确保 foo_bar 格式
        $cold = Str::snake($cold, "_");

        //先直接判断一次
        if ($this->model::hasColumn($cold)!==false) return $cold;

        //没有 _ 连接符的 直接返回 null
        if (strpos($cold,"_")===false) return null;

        //字段名拆分
        $ca = explode("_", $cold);

        //需要调用 JoinParser
        $jp = $this->curd->joinParser;
        if (!$jp instanceof JoinParser) return null;

        //从字段名数组中查找可能存在的 表名别名
        $tbc = null;
        $cola = [];
        for ($i=count($ca)-1;$i>=1;$i--) {
            $ch = implode("_", array_slice($ca, 0, $i));
            $tbc = $jp->findTableInParam($ch);
            if (!Is::nemaso($tbc)) {
                $tbc = null;
                continue;
            }
            $cola = array_slice($ca, $i);
            break;
        }

        if (is_null($tbc) || !Is::nemidx($cola)) return null;

        //如果未开启 join 且 找到的 不是主表  直接返回
        if ($jp->needJoin()!==true && $tbc["table"]!==$this->model::$modk) return null;

        //拼接完整的 表名(别名).字段名 字符串
        return $tbc["table"].($tbc["alias"]==="" ? "" : "(".$tbc["alias"].")").".".implode("_", $cola);

    }

    /**
     * 匹配 字符串 "AND", "OR", "AND #...", "OR #..."
     * @param String $key
     * @return String|false 如果是 AND|OR 标记，则返回 "AND" 或 "OR"  否则返回 false
     */
    public function isAndOr($key)
    {
        if (!Is::nemstr($key)) return false;

        $key = strtoupper($key);
        if (in_array($key, ["AND","OR"]) || preg_match("/^(AND|OR)\s+#.+/", $key, $matches)===1) {
            return in_array($key, ["AND","OR"]) ? $key : $matches[1];
        }
        return false;
    }



}
