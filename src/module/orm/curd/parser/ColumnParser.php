<?php
/**
 * SPF-Orm 数据库操作模块
 * Curd 操作 column 条件解析器
 * 
 * Medoo(2.x) 库 column 参数用法：  column 参数是 indexed 或 associate 数组  还可以是 * 或 单一字段名 字符
 * 
 *      字符串形式：
 *          "*"               全部字段，不推荐
 *          "col_name"
 *          "table.col"
 * 
 *      indexed 数组形式：
 *      [
 *          "col_name",
 *          "main.col_a",
 *          "join_table.col_b",
 *          ...
 *      ]
 * 
 *      associate 数组形式：
 *      !! Medoo 支持在 column 参数中指定最终输出的 记录结构 map
 *      [
 *          "col_name",
 *          "some alias" => "main.col_a",
 * 
 *          !! 自定义输出结构
 *          "任意键名" => [
 *              "col_b",
 *              "字段别名" => "join_table.col_c",
 *              ...
 *          ],
 * 
 *          ...
 *      ]
 *      
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

class ColumnParser extends CurdParser 
{

    //解析得到的 column 参数
    public $column = [];

    //如果要查询的字段列表中包含 计算字段，则缓存到这里
    public $getter = [
        //"tbn" => [ getters ... ],
    ];

    /**
     * 缓存 deployColumns 方法的结果
     * !! 在每次 setParam 方法后自动清空此缓存
     */
    protected $deployed = [];

    /**
     * !! 如果指定了 自定义输出结构 map 会保存到这里
     * 多次定义，将使用 extend 合并，后定义的 将覆盖 先定义的
     */
    public $raw = [];

    /**
     * 初始化 curd 参数
     * !! 子类必须实现 !!
     * @return CurdParser $this
     */
    public function initParam()
    {
        //默认只查询 当前表的必查字段
        return $this->setParam("$");
    }

    /**
     * 设置 curd 参数
     * !! 子类必须实现 !!
     * 构造 medoo 查询参数
     * 指定要返回值的 字段名 or 字段名数组 
     * 
     * 参数形式：
     *      !! "*" 表示全部字段
     *      $cp->setParam("*")                      --> $cp->column[] = "主表名.*"
     *      $cp->setParam("关联表.*")               --> $cp->column[] = "关联表.*"
     * 
     *      !! 如果关联表有别名，则最终实际的 column 参数中，将使用 别名.字段名
     *      $cp->setParam("关联表(别名).*")         --> $cp->column[] = "关联表(别名).*"
     * 
     *      !! "$" 表示对应数据表中 被标记为 isIncludes 的 必查字段
     *      $cp->setParam("$")                      --> $cp->column[] = "主表名.$"
     *      $cp->setParam("关联表.$")               --> $cp->column[] = "关联表.$"
     *      $cp->setParam("关联表(别名).$")         --> $cp->column[] = "关联表(别名).$"
     * 
     *      !! 手动指定要查询的 字段，实际查询时 仍然会带上 必查字段
     *      $cp->setParam("col_a","col_b", ...)     --> $cp->column[] = "主表名.col_a","主表名.col_b", ... 合并
     *      $cp->setParam("关联表.col_a", ...)     
     *          --> $cp->column[] = "关联表.col_a", ... 合并
     *      $cp->setParam("关联表(别名).col_a", ...)     
     *          --> $cp->column[] = "关联表(别名).col_a", ... 合并
     * 
     *      !! 传入自定义的 输出结构
     *      !! 字段名必须是 表名.字段名 或 别名.字段名 形式
     *      !! 将直接作为最终 查询的 column 参数，ColumnParser 不做处理，需要自行注意 主表|关联表以及别名的写法
     *      $cp->setParam([ ... 自定义 map ... ])
     *          --> 保存到 $cp->raw[] 多次定义，将会 extend
     * 
     * @param Mixed $param 与 medoo column 参数格式一致
     * @return CurdParser $this
     */
    public function setParam($param=null)
    {
        $args = func_get_args();
        if (empty($args) || (count($args)===1 && is_null($args[0]))) return $this;

        //主表名
        $modk = $this->model::$modk;

        //依次处理每个 arg
        foreach ($args as $arg) {
            if (!Is::nemstr($arg) && !Is::nemaso($arg)) continue;

            //!! 直接传入 自定义输出结构 map
            if (Is::nemaso($arg)) {
                //写入 raw
                $this->raw = Arr::extend($this->raw, $arg);
                continue;
            }

            $arg = trim($arg);

            //!! 自动附加 主表名
            if (strpos($arg, ".")===false) $arg = $modk.".".$arg;
            if (in_array($arg, $this->column)) continue;
            //!! 已有 tbn.* 再次传入 tbn.xxx 则去除 tbn.*
            $tbn = explode(".",$arg)[0];
            $arc = explode(".",$arg)[1];
            if ($arc!=="*" && in_array($tbn.".*", $this->column)) {
                array_splice($this->column, array_search($tbn.".*", $this->column), 1);
            }

            //插入
            $this->column[] = $arg;
        }

        //清空 deployed 缓存
        $this->clearDeployed();

        //清空 
        
        return $this;
    }

    /**
     * 重置 curd 参数 到初始状态
     * !! 子类必须实现 !!
     * @return CurdParser $this
     */
    public function resetParam()
    {
        $this->column = [];
        $this->clearDeployed();
        $this->raw = [];
        return $this->initParam();
    }

    /**
     * 执行 curd 操作前 返回处理后的 curd column 参数
     * !! 展开 * 和 $   所有字段名增加 表名|别名 前缀
     * 
     * !! 子类必须实现 !!
     * @return Mixed curd 操作 medoo 参数，应符合 medoo 参数要求
     */
    public function getParam()
    {
        //调用 deployColumn 方法
        $cols = $this->deployColumns();

        //!! 合并可能存在的 raw 参数
        if (Is::nemaso($this->raw)) {
            $cols = array_merge($cols, $this->raw);
        }

        return $cols;
    }



    /**
     * 工具方法
     */

    /**
     * 解析  表名(别名).字段名|*|$  形式的 字段定义
     * 返回各区段的值
     * @param String $col
     * @return Array
     *  [
     *      "origin" => $col,
     *      "table" => 表名,
     *      "alias" => 别名,
     *      "column" => 字段名,
     *  ]
     */
    public function parseColumnDef($col)
    {
        if (!Is::nemstr($col)) return null;
        $col = trim(trim($col), ".");
        //!! 不解析 不含 表名. 前缀的 字段名
        if (strpos($col, ".")===false) return null;

        //拆分
        $mt = preg_match("/^([a-zA-Z0-9_]+)\s?(\(\s?[a-zA-Z0-9_]+\s?\))?\.([a-zA-Z0-9_*$]+)$/", $col, $matches);
        if ($mt!==1) return null;
        $ma = array_slice($matches, 1);
        //是否含有 别名
        $hasAlias = count($ma)===3 && Is::nemstr($ma[1]);
        //var_dump($col);
        //var_dump($ma);
        return [
            "origin" => $col,
            "table" => $ma[0],
            "alias" => $hasAlias ? trim(substr($ma[1],1,-1)) : "",
            "column" => $ma[2], //$hasAlias ? $ma[2] : $ma[1]
        ];
    }

    /**
     * 在 getParam 阶段，展开 $this->column 中的 tbn.* 或 tbn.$
     * 返回 展开后的 column[]
     * @return Array indexed 数组
     */
    public function deployColumns()
    {
        //!! 优先读取缓存
        if (Is::nemarr($this->deployed)) return $this->deployed;

        $cols = $this->column;
        if (!Is::nemidx($cols)) return [];

        //当前表名
        $modk = $this->model::$modk;

        //最终的 完整字段列表
        $dp = [];

        //使用到 tbn.* 的表名数组
        $alls = [];
        //使用到 tbn.$ 的表名数组
        $incs = [];

        //依次解析
        foreach ($cols as $col) {
            //解析
            $p = $this->parseColumnDef($col);
            if (!Is::nemaso($p)) continue;
            $col = $p["origin"];
            $tbn = $p["table"];
            $ali = $p["alias"];
            $v = $p["column"];
            //字段名前缀，有别名使用别名，没有则使用表名 做前缀
            $vp = $ali!=="" ? $ali : $tbn;
            //此字段所属表的 标记
            $vs = $tbn.($ali!=="" ? "(".$ali.")" : "");

            //!! 如果这个表已经查询了全部字段，则跳过
            if (in_array($vs, $alls)) continue;

            //!! 如果解析得到的 tbn 不存在
            $allmod = $this->curd->db->hasModel($tbn);
            if ($allmod===false) continue;

            //这个表中的全部字段
            $all = $this->curd->db->config->ctx("model/$tbn/columns");

            //普通字段
            if (!in_array($v, ["*","$"])) {
                //!! 剔除无效字段名
                if (!in_array($v, $all)) continue;
                //!! 剔除非原生字段，缓存到 $curd->columnParser->getter 中
                if ($allmod::isRealInDbColumn($v)!==true) {
                    $this->setGetterColumn($tbn, $v);
                    continue;
                }
                $_col = $vp.".".$v;
                //!! 如果不是 当前表的字段，且没有别名，自动增加 字段别名
                if ($ali!=="" || $tbn!==$modk) $_col .= " (".$vp."_".$v.")";
                //var_dump("-----------".$_col);
                //写入 最终字段名列表
                $dp[] = $_col;
                continue;
            }

            //全部字段 *
            if ($v==="*") {
                //记录这个表
                $alls[] = $vs;
                foreach ($all as $ac) {
                    if ($allmod::isRealInDbColumn($ac)!==true) {
                        $this->setGetterColumn($tbn, $ac);
                        continue;
                    }
                    //$acc = $allc[$ac];
                    //收集字段
                    $_col = $vp.".".$ac;
                    //!! 如果不是 当前表的字段，且没有别名，自动增加 字段别名
                    if ($ali!=="" || $tbn!==$modk) $_col .= " (".$vp."_".$ac.")";
                    //写入 最终字段名列表
                    $dp[] = $_col;
                }
                continue;
            }

            //必查字段 $
            if ($v==="$") {
                //记录到 incs[] 最后插入
                if (!in_array($vs, $incs)) $incs[] = $vs;
                continue;
            }

        }

        //最后统一添加 必查字段
        if (Is::nemidx($incs)) {
            foreach ($incs as $inc) {
                //!! 排除已经查询全部字段的情况
                if (in_array($inc, $alls)) continue;

                //拆出 表名 和可能存在的 别名
                $tbn = trim($inc);
                $ali = "";
                if (strpos($inc, "(")!==false) {
                    $ia = explode("(", trim($inc));
                    $tbn = $ia[0];
                    $ali = substr($ia[1], 0, -1);
                }
                $vp = $ali!=="" ? $ali : $tbn;
                
                //这个表中的 必查字段
                $all = $this->curd->db->config->ctx("model/$tbn/special/includes");
                foreach ($all as $ac) {
                    //收集字段
                    $_col = $vp.".".$ac;
                    //!! 如果不是 当前表的字段，且没有别名，自动增加 字段别名
                    if ($ali!=="" || $tbn!==$modk) $_col .= " (".$vp."_".$ac.")";
                    //写入 最终字段名列表
                    $dp[] = $_col;
                }
            }
        }

        if (!Is::nemidx($dp)) return [];

        //统一去重后 返回
        $this->deployed = array_unique(array_merge([], $dp), SORT_REGULAR);
        return $this->deployed;
    }

    /**
     * 将计算字段添加到 getter 缓存
     * $curd->column("*") 会自动添加所有计算字段
     * @param String $tbn 当前主表，或关联表，表名 foo_bar
     * @param String $col 字段名，必须是有效的 计算字段名
     * @return $this
     */
    protected function setGetterColumn($tbn, $col)
    {
        $mod = $this->curd->db->hasModel($tbn);
        if ($mod===false) return $this;
        if ($mod::isGetterColumn($col)!==true) return $this;
        if (!isset($this->getter[$tbn])) $this->getter[$tbn] = [];
        if (!in_array($col, $this->getter[$tbn])) $this->getter[$tbn][] = $col;
        return $this;
    }

    /**
     * 清空 deployed 缓存，每次 setParam 调用后执行
     * @return $this
     */
    protected function clearDeployed()
    {
        $this->deployed = [];
        //清除 getter 缓存
        $this->getter = [];
        return $this;
    }

    /**
     * 根据解析得到的 column 参数，生成要全量输出到前端的 Record 记录 mapper
     * 通常由 Record|RecordSet 实例调用 export 方法时，使用此方法
     */
    public function getRecordMapper()
    {
        $mapper = [];

        //当前数据表
        $modk = $this->model::$modk;

        //普通 columns 带查询字段，一定是 table.col 格式的字符串列表
        $cols = $this->getParam();
        if (Is::nemidx($cols)) {
            foreach ($cols as $coli) {
                $ca = explode(".", $coli);
                $tbn = $ca[0];
                $coli = $ca[1];
                if ($tbn === $modk) {
                    //主表字段
                    $mapper[] = $coli;
                } else {
                    //关联表字段
                    $cb = explode("(", $coli);
                    $coli = trim($cb[0]);
                    $mapper[] = "$tbn.$coli";
                }
            }
        }

        //getter 计算字段
        $gets = $this->getter;
        if (Is::nemaso($gets)) {
            foreach ($gets as $tbn => $gs) {
                if ($tbn === $modk) {
                    //主表计算字段
                    $mapper = array_merge($mapper, $gs);
                } else {
                    //关联表 计算字段
                    foreach ($gs as $gi) {
                        $mapper[] = "$tbn.$gi";
                    }
                }
            }
        }

        //raw 如果传入了 原始的 输出 mapper
        $raw = $this->raw;
        if (!empty($raw)) {
            //merge
            $mapper = array_merge($mapper, $raw);
        }

        //返回获取到的 Record 记录输出 mapper
        return $mapper;
    }

}