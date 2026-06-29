<?php
/**
 * SPF-Orm 数据库操作模块
 * Curd 操作 join 条件解析器
 * 
 * Medoo(2.x) 库 join 参数用法：   join 参数是 [] 数组
 *      
 *      [>]     LEFT JOIN
 *      [<]     RIGHT JOIN
 *      [<>]    FULL JOIN
 *      [><]    INNER JOIN
 * 
 *      用法：
 *      [
 *          !! 通常情况
 *          "[>]table" => [                                 --> LEFT JOIN `table` ON `main`.`主表字段` = `table`.`关联表字段`
 *              "主表字段" => "关联表字段"
 *          ],
 * 
 *          !! 主表和关联表 使用相同字段名
 *          "[>]table" => "column",                         --> LEFT JOIN `table` USING (`column`)
 * 
 *          !! 主表和关联表 同时使用多个相同字段名
 *          "[>]table" => ["column_a", "column_b],          --> LEFT JOIN `table` USING (`column_a`, `column_b`)
 * 
 *          !! 同一张关联表，需要多次 join 可以加 别名
 *          "[>]table" => "column_a",
 *          "[>]table (o_table)" => "column_b",
 * 
 *          !! 使用其他关联表的字段
 *          "[>]table_a" => [ "main.column_a" => "column_b" ],
 *          "[>]table_b" => [ "table_a.column_b" => "column_b" ],
 * 
 *          !! 同时关联多个字段
 *          "[>]table" => [
 *              "main_col_a" => "column_a",
 *              "main_col_b" => "column_b",
 * 
 *              !! 还可以加筛选条件
 *              "AND" => [
 *                  "column_c[!]" => "foo",
 *              ],
 *          ],
 *      ]
 */

namespace Spf\module\orm\curd\parser;

use Spf\module\Orm;
use Spf\module\orm\Db;
use Spf\module\orm\Model;
use Spf\module\orm\Curd;
use Spf\module\orm\curd\CurdParser;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Medoo\Medoo;

class JoinParser extends CurdParser 
{
    /**
     * 初始参数
     */
    //配置参数中的 join 原始参数
    public $origin = [];
    //join 参数是否可用
    public $available = false;
    //此数据模型定义了哪些 关联表  表名
    public $tables = [];
    //此数据模型中的 哪些字段关联到了其他表  字段名
    public $columns = [];
    /**
     * 标准的 关联表参数
     * !! 由 module\orm\config\model\parser\JoinParser 解析器解析得到
     */
    public $param = [
        /*
        "表名 或 别名" => [
            !! 与 module\orm\config\model\parser\JoinParser::$dftJoinTable[] 结构一致

            # 关联表名 真实表名
            "table" => "",

            # 如果关联表存在 别名，记录别名，没别名则为空
            "alias" => "",

            # 使用到的关联字段
            !! 必须是 表名.字段名  有别名时则 别名.字段名
            "columns" => [
                "表名.字段名", "别名.字段名",
            ],

            # join 方式  
            "type" => "",

            !! 依赖的其他 关联表
            !! 如果此 join 参数中使用了其他关联表的字段，表示依赖这个 其他关联表
            !! 其他关联表如果有别名，则记录别名，否则记录表名
            "dep" => [
                "表名", "别名",
            ],

            !! 配置参数中的 原始 join 参数
            "param" => [],
        ],
        ...
        */
    ];

    //本次 CURD 是否要 JOIN 其他表  默认 false
    public $use = false;
    //如果 use == true 则此处保存要 join 的表的 表名 或 别名
    public $current = [
        //"表名",
        //"如果有别名则使用 别名",
        //...
    ];
    //如果 current 不为空，则将其中的 别名 替换为 真实的 表名，保存在这个数组中
    public $currentTbns = [
        //其中一定是 真实表名
    ];
    
    /**
     * !! 如果本次 CURD 需要 JOIN 的表不在配置参数的定义之中
     * !! 传入原生 Medoo join 参数形式，将保存在这里
     */
    public $raw = [
        /*
        "[>]other_table" => "same_col",
        ...
        */
    ];
    

    /**
     * 初始化 curd 参数
     * !! 子类必须实现 !!
     * @return CurdParser $this
     */
    public function initParam()
    {
        //写入初始参数
        foreach ($this->conf("join") as $k => $v) {
            $this->$k = $v;
        }
        return $this;
    }

    /**
     * 设置 curd 参数
     * !! 子类必须实现 !!
     * 
     * 参数形式：
     *      $jp->setParam(true)     --> 将 $jp->param[] 中所有 表名或别名 添加到 $jp->current 设置 $jp->use = true
     *      $jp->setParam(false)    --> 清空 $jp->current 设置 $jp->use = false
     *      $jp->setParam("表名", "别名", ...)
     *          --> 将传入的 表名或别名 写入 $jp->current 设置 $jp->use = true
     *      $jp->setParam([ Medoo 标准的 join 参数 ])
     *          --> 将传入的 Medoo join 参数 写入 $jp->raw 设置 $jp->use = true
     * 
     * @param Mixed $param 要设置的 curd 参数
     * @return CurdParser $this
     */
    public function setParam($param=null)
    {
        $args = func_get_args();
        $clearJoin = false;
        $fullJoin = false;
        $raw = false;
        $normal = false;
        if (empty($args)) {
            $fullJoin = true;
        } else if (count($args)===1) {
            if ($args[0]===false) {
                $clearJoin = true;
            } else if ($args[0]===true || is_null($args[0])) {
                $fullJoin = true;
            } else if (Is::nemaso($args[0])) {
                $raw = true;
            } else {
                $normal = true;
            }
        } else {
            $normal = true;
        }

        //传入 Medoo join 参数
        if ($raw) {
            $this->raw = Arr::extend($this->raw, $args[0]);
            return $this;
        }

        //准备要 join 的表
        if ($clearJoin || !$this->available) {
            //需要清空
            $jtbs = [];
        } else if ($fullJoin) {
            //join 全部定义的 关联表
            $jtbs = array_merge([],array_keys($this->param));
        } else {
            //join 部分表
            $jtbs = array_merge([], array_filter($args, function($arg) {
                return isset($this->param[$arg]);
            }));
        }

        //清空 join 参数
        if (!Is::nemidx($jtbs)) {
            //reset
            return $this->resetParam();
        }

        //!! 标记 use = true
        $this->use = true;

        //依次检查这些 要 join 的关联表，如果有关联的 其他表，也一并添加
        $deps = [];
        foreach ($jtbs as $jtb) {
            $jtbc = $this->param[$jtb];

            //!! 此关联表 可能依赖其他关联表
            if (Is::nemidx($jtbc["dep"])) {
                $deps = array_merge($deps, $jtbc["dep"]);
            }
        }
        //依赖的其他表 合并到 $jtbs
        if (Is::nemidx($deps)) $jtbs = array_unique(array_merge($jtbs, $deps), SORT_REGULAR);

        //将 所有关联表 插入 current
        $this->current = array_unique(array_merge($this->current, $jtbs), SORT_REGULAR);

        //最后 调用 addJoinTableColumns 将所有关联表的 关联字段 和 必查字段 写入 column 参数中
        return $this->addJoinTableColumns();
    }
    
    /**
     * 重置 curd 参数 到初始状态
     * !! 子类必须实现 !!
     * @return CurdParser $this
     */
    public function resetParam()
    {
        $this->use = false;
        $this->current = [];
        $this->currentTbns = [];
        $this->raw = [];

        //同时清空 column 参数
        return $this->clearJoinTableColumns();
    }

    /**
     * 执行 curd 操作前 返回处理后的 curd 参数
     * !! 子类必须实现 !!
     * @return Mixed curd 操作 medoo 参数，应符合 medoo 参数要求
     */
    public function getParam()
    {
        if (!$this->needJoin()) return [];

        //待输出
        $jps = [];

        //current 中的表
        $param = $this->param;
        if (Is::nemidx($this->current)) {
            foreach ($this->current as $tbk) {
                if (!isset($param[$tbk]) || !Is::nemaso($param[$tbk])) continue;
                $jps = Arr::extend($jps, $param[$tbk]["param"]);
            }
        }
        
        //!! 可能存在 raw 原生参数
        if (Is::nemaso($this->raw)) {
            $jps = Arr::extend($jps, $this->raw);
        }

        return $jps;
    }



    /**
     * 工具方法
     */

    /**
     * 快速判断当前 CURD 是否需要 join 参数
     * @return Bool
     */
    public function needJoin()
    {
        return ($this->available && $this->use) || Is::nemaso($this->raw);
    }

    /**
     * 如果关联了某表，自动向 ColumnParser 中增加此关联表的 必查字段
     * !! 在 每次 setParam 后调用
     * @return CurdParser $this
     */
    public function addJoinTableColumns()
    {
        //调用 ColumnParser
        $cp = $this->curd->columnParser;
        if (!$cp instanceof ColumnParser) return $this;

        //首先将当前主表的 关联字段加入 column 参数
        if (Is::nemidx($this->columns)) {
            $cp->setParam(...$this->columns);
        }

        //当前要 join 的表  表名 或 别名
        $tbks = $this->current;
        //当前要 join 的表  一定是表名，与 current 一一对应
        $tbns = $this->getRealTbns(true)->currentTbns;
        
        if (!Is::nemidx($tbks)) return $this;

        //依次添加 各关联表的 关联字段 和 必查字段
        //!! 关联字段 可能不在 必查字段中
        foreach ($tbks as $i => $tbk) {
            $tbn = $tbns[$i];
            $tbc = $this->param[$tbk] ?? null;
            //此关联表中对应的 关联字段
            $tcol = $tbc["columns"] ?? [];

            //要添加的字段名列表
            $cols = ["$"];      // $ 必查字段一定添加
            if (Is::nemidx($tcol)) {
                foreach ($tcol as $tc) {
                    $ta = explode(".", $tc);
                    if (count($ta)<2) {
                        $cols[] = $ta[0];
                    } else if ($ta[0]===$tbk || $ta[0]===$tbn) {
                        //对应的关联字段 可能是其他表的字段，需要跳过
                        $cols[] = $ta[1];
                    }
                }
            }

            //准备字段名前缀，可能是 表名.字段名  或  表名(别名).字段名
            $pre = $tbk===$tbn ? $tbn : $tbn."(".$tbk.")";
            //加前缀
            $cols = array_map(function($tc) use ($pre) {
                return $pre.".".$tc;
            }, $cols);

            //加入 column 参数
            $cp->setParam(...$cols);
        }

        return $this;
    }

    /**
     * 清空 ColumnParser 中所有关联表的 相关字段
     * !! setParam(false) 后调用
     * @return CurdParser $this
     */
    public function clearJoinTableColumns()
    {
        //调用 ColumnParser
        $cp = $this->curd->columnParser;
        if (!$cp instanceof ColumnParser) return $this;

        $cp->resetParam();

        return $this;
    }

    /**
     * 将 $this->current 中的 别名转换为真实表名，生成完整的 真实表名数组 存到 currentTbns 中
     * !! 可能重复，因为 current[] 中可能保存了同一个表的 多个别名
     * !! currentTbns[] 与 current[] 是 一一对应的
     * @param Bool $refresh 是否强制刷新 默认 false 直接使用已有的，仅当 已有的 currentTbns[] 为空时才创建
     * @return CurdParser $this
     */
    public function getRealTbns($refresh=false)
    {
        //!! 不强制刷新时，如果 currentTbns[] 不为空，直接返回
        if ($refresh!==true && Is::nemidx($this->currentTbns) && count($this->current)===count($this->currentTbns)) {
            return $this;
        }

        //刷新 currentTbns[]
        if (!Is::nemidx($this->current)) {
            $this->currentTbns = [];
            return $this;
        }

        $tbns = [];
        foreach ($this->current as $tbk) {
            if (!isset($this->param[$tbk]) || !Is::nemaso($this->param[$tbk])) continue;

            if ($this->curd->db->hasModel($tbk)!==false) {
                $tbns[] = $tbk;
                continue;
            }

            //从 对应的 param 中查找真实表名
            $tbns[] = $this->param[$tbk]["table"];
        }
        $this->currentTbns = $tbns;

        return $this;
    }

    /**
     * 将 currentTbns[] 数组去重后输出
     * @return Array [ "table name", ... ]
     */
    public function getJoinTables()
    {
        if (!Is::nemidx($this->currentTbns)) return [];
        return array_unique(array_merge([], $this->currentTbns), SORT_REGULAR);
    }

    /**
     * 从 $this->param 中查找 可能存在的 表名别名
     * @param String $tbk 可以传入 表名 或 别名
     * @return Array|null 返回找到的参数 $this->param[tbk][...]，未找到返回 null
     */
    public function findTableInParam($tbk=null)
    {
        if (!Is::nemstr($tbk)) return null;
        $tbk = Str::snake($tbk, "_");
        if (isset($this->param[$tbk])) return $this->param[$tbk];
        foreach ($this->param as $k => $c) {
            if ($tbk===$c["table"] || $tbk===$c["alias"]) {
                return $c;
            }
        }
        return null;
    }
}