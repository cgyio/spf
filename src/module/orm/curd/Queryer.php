<?php
/**
 * SPF-Orm 数据库操作模块
 * 处理复合查询条件，生成 medoo 查询参数
 * 
 * 通常通过 php://input 传入符合查询条件，其格式为：
 * 
 *  [
 *      # 可以在 数组顶层，或在 query 键下
 *      "query" => [
 *          # join 关联表参数
 *          !! 其结构与 $curd->joinParser->setParam(...) 方法参数结构一致
 *          "join" => [ ... 关联表名|别名, ... ],
 *          "join" => [
 *              !! 也可以是 Medoo 标准 join 参数格式
 *              "[>]table" => "col_name",
 *              "[>]table" => [
 *                  "table.col" => "col"
 *              ]
 *          ],
 * 
 *          # column 查询字段参数
 *          !! 其结构与 $curd->columnParser->setParam(...) 方法参数结构一致
 *          "column" => [ "*", "$", "table.col", "alias.col", ... ],
 * 
 *          # where 系列参数
 *          "sk" => "foo,bar，jaz",
 *          "filter" => [
 *              "column" => [
 *                  "logic" => ">=",
 *                  "value" => 123
 *              ],
 *              ...
 *          ],
 *          !! json 类型字段的查询参数，结构与 $curd->whereParser->parseJsonWhere() 参数结构一致
 *          "json" => [
 *              "col_a" => [
 *                  "key_a%" => ["val_a", "val_b"],
 *                  ...
 *              ],
 *              ...
 *          ],
 *          "match" => [...],
 *          "group" => [...],
 *          "having" => [...],
 *          # 手动指定 额外的 查询参数
 *          "where" => [
 *              "column[!~]" => "foobar",
 *              ...
 *          ],
 * 
 *          # sort 排序参数
 *          "sort" => [
 *              "column" => "DESC",
 *              "column2" => "",
 *              ...
 *          ],
 * 
 *          # limit 分页系列参数
 *          "page" => [
 *              "size" => 100,
 *              "ipage" => 3
 *          ],
 *          "limit" => 10 | [100,200],
 * 
 *          !! 前端传入这些参数，不会自动处理，仅存入 $curd->queryer->context[]
 *          !! 由外层的 具体业务方法，自行读取 $curd->queryer->context[...] 进行判断和处理
 *          !! 因为 通过 query([...]) 方法执行自动查询，需要返回查询结果，
 *          !! 这些传入的 特殊参数（例如：debug, method）会影响实际返回的查询结果，可能不是预期的数据类型
 *          !! 因此，由具体的业务方法自行判断执行，此处仅缓存本次 curd 中的 query 参数 
 *          "debug" => true,
 *          "method" => "select",  # 可选不同的 medoo 方法
 *      ]
 *  ]
 * 
 */

namespace Spf\module\orm\curd;

use Spf\module\Orm;
use Spf\module\orm\Curd;
use Spf\Request;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Medoo\Medoo;

class Queryer 
{
    //关联的 Curd 实例
    public $curd = null;

    //缓存 input 参数
    protected $input = [];

    //curd\parser\****Parser 各类型参数处理工具类实例
    protected $wp = null;
    protected $cp = null;
    protected $jp = null;

    //指定解析方法，将依次执行这些解析方法
    protected $keys = [
        //join 参数解析方法
        "join",

        //column 参数解析方法
        "column",

        //filter|sk|json|match|group|having|where 参数解析方法
        "where",

        //sort 参数解析方法
        "sort",

        //page|limit 参数解析方法
        "page",

        //!! 额外参数，需要外层业务方法自行手动处理，此处不自动处理的
        //debug|method|... 参数
        "manual",
    ];

    //解析后得到的参数
    public $context = [];

    /**
     * 构造
     * @param Curd $curd 当前 curd 实例
     * @param Array $extra 在 input 传入的参数基础上，手动增加 extra 额外查询参数
     * @return void
     */
    public function __construct($curd, $extra = [])
    {
        if (!$curd instanceof Curd) return null;
        $this->curd = $curd;
        $this->wp = $curd->whereParser;
        $this->cp = $curd->columnParser;
        $this->jp = $curd->joinParser;
    }

    /**
     * 读取当前已解析完成的 curd 查询参数
     * @return Array
     */
    protected function args()
    {
        return $this->curd->parseArguments();
    }

    /**
     * 解析参数
     * @param Array $extra 传入的复合查询参数
     * @param Bool $mixin 是否 合并 php://input 数据，默认 true
     * @return Queryer $this
     */
    public function apply($extra = [], $mixin = true)
    {
        $hasExtra = Is::nemarr($extra) && Is::associate($extra);

        //未指定查询参数
        if (!$hasExtra && !$mixin) {
            $this->context = [];
            return $this;
        }

        //合并 复合查询条件数据
        if (!$hasExtra) $extra = [];
        $input = [];
        if ($mixin) {
            //读取 当前 Request 实例的 inputs 数据
            $input = Request::$current->inputs->json;
            if (Is::nemarr($input)) {
                if (isset($input["query"])) {
                    $input = $input["query"];
                }/* else {
                    $inp = [];
                    foreach ($this->keys as $key) {
                        if (!isset($input[$key])) continue;
                        $inp[$key] = $input[$key];
                    }
                    $input = array_merge([], $inp);
                }*/
            } else {
                $input = [];
            }
            if (!empty($input)) $this->input = $input;
        }
        $this->context = Arr::extend($input, $extra);

        //开始 按 参数可用的 键名 顺序解析
        for ($i=0;$i<count($this->keys);$i++) {
            $key = $this->keys[$i];
            $m = "parse".ucfirst($key);
            if (method_exists($this, $m)) {
                $this->$m();
            }
        }

        return $this;
    }



    /**
     * 各参数 解析方法
     */

    /**
     * parseJoin 解析 join 参数
     * @return Queryer $this
     */
    protected function parseJoin()
    {
        $join = $this->context["join"] ?? [];
        if (!Is::nemarr($join)) return $this;
        
        //[ 表名， 别名， ... ]
        if (Is::nemidx($join)) {
            $this->jp->setParam(...$join);
            return $this;
        }

        //[ Medoo 标准 join 参数形式 ]
        $this->jp->setParam($join);
        return $this;
    }

    /**
     * parseColumn 解析 column 参数
     * @return Queryer $this
     */
    protected function parseColumn()
    {
        $column = $this->context["column"] ?? [];
        if (!Is::nemstr($column) && !Is::nemidx($column)) return $this;
        //var_dump($column);
        if (Is::nemstr($column)) {
            $this->cp->setParam($column);
        } else if (Is::nemidx($column)) {
            $this->cp->setParam(...$column);
        }
        return $this;
    }

    /**
     * parseWhere 同时解析 sk/filter/json/match/group/having/where 参数
     * @return Queryer $this
     */
    protected function parseWhere()
    {
        $filter = $this->context["filter"] ?? [];
        $sk = $this->context["sk"] ?? "";
        $json = $this->context["json"] ?? [];
        $match = $this->context["match"] ?? [];
        $group = $this->context["group"] ?? [];
        $having = $this->context["having"] ?? [];
        $where = $this->context["where"] ?? [];

        $this->wp->filter($filter);
        $this->wp->sk($sk);
        if (Is::nemaso($json)) {
            foreach ($json as $colk => $jc) {
                $this->wp->whereJson($colk, $jc);
            }
        }
        $this->wp->match($match);
        $this->wp->group($group);
        $this->wp->having($having);
        
        $this->wp->setParam($where);

        return $this;
    }

    /**
     * parseSort 排序参数
     * @return Queryer $this
     */
    protected function parseSort()
    {
        $sort = $this->context["sort"] ?? [];
        if (!Is::nemarr($sort)) return $this;

        $sort = array_filter($sort, function($i) {
            return Is::nemstr($i) && in_array(strtoupper($i), ["ASC","DESC"]);
        });

        //调用 WhereParser
        $this->wp->order($sort);

        return $this;
    }

    /**
     * parsePage 同时解析 page/limit 参数
     * @return Queryer $this
     */
    protected function parsePage()
    {
        $page = $this->context["page"] ?? [];
        $limit = $this->context["limit"] ?? [];

        $pagesize = $page["size"] ?? 100;
        $ipage = $page["ipage"] ?? 1;
        $this->wp->page($ipage, $pagesize);

        if (!empty($limit)) {
            $this->wp->limit($limit);
        }

        return $this;
    }

    /**
     * parseManual 处理传入的 额外参数
     * debug|method|... 等参数
     * !! 通常此处不需要处理，由外层具体业务方法读取 $curd->queryer->context[...] 自行判断，执行操作
     * @return Queryer $this
     */
    protected function parseManual()
    {

        return $this;
    }

}