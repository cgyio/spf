<?php
/**
 * Resource 资源处理中间件 Processor 处理器子类
 * RowProcessor 专门处理 Codex 类型代码资源 内容行数组 相关操作
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\resource\Util;
use Spf\module\src\resource\Plain;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

class RowProcessor extends Processor 
{
    //设置 当前中间件实例 在资源实例中的 属性名，不指定则自动生成 NS\middleware\FooBar  -->  foo_bar
    public static $cacheProperty = "rower";

    /**
     * 定义不同类型文件的 行处理参数
     */
    protected static $opts = [
        //默认 js 类型
        "default" => [
            //换行符
            "rn" => "\n",
            //注释符 [ 开始符号, 每行注释的开头符号, 结尾符号 ]
            "cm" => ["/**", " * ", " */"],
        ],

        //js|css|scss 与 default 相同

        //html
        "html" => [
            "cm" => ["<!-- ", "    ", " -->"],
        ],

        "xml" => "html",
        "svg" => "html",
    ];

    /**
     * 当前类型文件的 行处理参数
     */
    public $rn = "";
    public $cm = [];

    //原始 内容行数据
    protected $originRows = [];

    /**
     * 历史 rows 数组记录
     * 每次执行 clear 时，会将当前 $resource->rows 写入此数组
     * 用于操作失败时，恢复 $resource->rows
     */
    public $historyRows = [];



    /**
     * 处理器初始化动作
     * !! Processor 子类 必须实现
     * @return $this
     */
    protected function initialize()
    {
        $res = $this->resource;

        //$res 的 ext
        $ext = $res->ext;
        //查找 ext 对应的 处理参数
        $opts = static::$opts;
        $dft = $opts["default"];
        $eopt = $opts[$ext] ?? [];
        if (Is::nemstr($eopt)) $eopt = $opts[$eopt] ?? [];
        $eopt = Arr::extend($dft, $eopt, true);
        //写入处理参数
        $ks = array_keys($dft);
        foreach ($ks as $k) {
            if (isset($eopt[$k])) $this->$k = $eopt[$k];
        }

        //记录原始 rows
        $rows = isset($res->rows) ? $res->rows : [];
        if (Is::nemarr($rows)) {
            $this->originRows = array_merge([], $rows);
        }

        return $this;
    }

    /**
     * create 阶段执行的操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function stageCreate()
    {
        //创建 资源的内容行数组
        $this->createRows();

        return true;
    }

    /**
     * export 阶段执行的操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function stageExport()
    {
        //合并 资源实例的 rows 生成 content
        $this->resource->content = $this->rowCombine();
        
        return true;
    }



    /**
     * 工具方法
     */

    /**
     * 将 resource 资源实例的 content 内容 拆分为 rows 内容行
     * @return $this
     */
    protected function createRows()
    {
        $res = $this->resource;

        //content
        $cnt = $res->content;
        if (!Is::nemstr($cnt)) {
            $this->resource->rows = [];
            return $this;
        }

        $rows = explode($this->rn, $cnt);
        //原始 行数组
        if (!Is::nemarr($this->originRows)) $this->originRows = array_merge([],$rows);
        //行数组
        $this->resource->rows = array_merge([], $rows);

        return $this;
    }
    
    /**
     * 清空 $resource->rows 为生成内容做准备
     * @return $this
     */
    public function clearRows()
    {
        $this->saveHistory();
        $this->resource->rows = [];
        return $this;
    }

    /**
     * 将当前 $resource->rows 存入 history
     * @return $this
     */
    public function saveHistory()
    {
        $this->historyRows[] = array_merge([], $this->resource->rows);
        return $this;
    }

    /**
     * 从 history 中恢复最新的 rows 数组记录
     * @return $this
     */
    public function restoreRows()
    {
        $his = $this->historyRows;
        if (!Is::nemarr($his)) return $this;
        $rows = array_slice($his, -1)[0];
        $this->historyRows = array_slice($his, 0, -1);
        if (!is_array($rows)) return $this;
        $this->resource->rows = array_merge([], $rows);
        return $this;
    }

    /**
     * 输出当前 rows
     * @return Array
     */
    public function exportRows()
    {
        $rows = array_merge([], $this->resource->rows);
        return $rows;
    }



    /**
     * 内容行数组 处理
     */

    /**
     * 向 内容行数组中增加 一行 或 多行
     *      rowAdd("内容", "行尾符号", 10)
     *      rowAdd([ "一行","二行",... ], 10)
     * @param String|Array $cnt 内容行数据，可以是 一行 或 多行 数据
     * @param String|Int $rn 结尾字符，默认 ; 当 $cnt 指定的是 数组时，此参数相当于 $idx
     * @param Int $idx 在 rows 中插入的 位置，默认 -1 表示 append，指定了则使用 splice 方法
     * @return $this
     */
    public function rowAdd($cnt, $rn=";", $idx=-1) 
    {
        if ($cnt==="" || Is::nemstr($cnt)) {
            //插入 一行
            if ($idx<0) {
                $this->resource->rows[] = $cnt.$rn;
            } else {
                array_splice($this->resource->rows, $idx, 0, $cnt.$rn);
            }
        } else if (Is::nemarr($cnt) && Is::indexed($cnt)) {
            //插入 多行
            $idx = -1;
            if (is_int($rn)) $idx = $rn;
            if ($idx<0) {
                $this->resource->rows = array_merge($this->resource->rows, $cnt);
            } else {
                array_splice($this->resource->rows, $idx, 0, $cnt);
            }
        }
        return $this;
    }

    /**
     * 向 内容行数组中 增加 $n 空行
     * @param Int $n 空行的行数 默认 1
     * @param Int $idx 在 rows 中插入的 位置，默认 -1 表示 append，指定了则使用 splice 方法
     * @return $this
     */
    public function rowEmpty($n=1, $idx=-1)
    {
        if ($n>0) {
            $rows = array_fill(0, $n, "");
            $this->rowAdd($rows, $idx);
        }
        return $this;
    }

    /**
     * 向 内容行数组中 增加 注释行，可以有多行
     * @param Array $comments 一行 或 多行注释，最后一个参数如果是 int 则作为 插入位置
     * @return $this
     */
    public function rowComment(...$comments)
    {
        if (!Is::nemarr($comments)) return $this;
        //获取插入位置
        $cl = array_slice($comments, -1)[0];
        if (is_int($cl)) {
            $idx = $cl;
            $comments = array_slice($comments, 0, -1);
        } else {
            $idx = -1;
        }
        if (!Is::nemarr($comments)) return $this;

        //获取当前资源的 注释符 [ 开始符号, 每行注释的开头符号, 结尾符号 ]
        $cm = $this->cm;
        if (!Is::nemarr($cm) || !Is::indexed($cm) || count($cm)<3) return $this;

        if (count($comments)===1) {
            //单行注释
            $this->rowAdd($cm[0]." ".$comments[0]." ".$cm[2], "", $idx);
        } else {
            //多行注释
            $rows = [];
            $rows[] = $cm[0];
            foreach ($comments as $comment) {
                $rows[] = $cm[1].$comment;
            }
            $rows[] = $cm[2];
            //插入
            $this->rowAdd($rows, $idx);
        }

        return $this;
    }

    /**
     * 向 内容行数组 增加 定义变量语句
     * @param String $name 变量名
     * @param String|Int|Float $val 变量值
     * @param Array $opt 变量定义语句参数，格式见下方定义
     * @param Int $idx 在 rows 中插入的 位置，默认 -1 表示 append，指定了则使用 splice 方法
     * @return $this
     */
    public function rowDef($name, $val, $opt=[], $idx=-1)
    {
        $opt = Arr::extend([
            "prev" => "\$",     //键名 前缀，默认 \$
            "sufx" => "",       //键名 后缀，默认 无
            "gap" => ":",       //键名与值 之间的间隔符，默认 :
            "quote" => false,   //键值是否使用引号包裹，false 表示不用引号，传入 '或" 表示使用 '或" 包裹键值
            "rn" => ";",        //行尾字符，默认 ;
        ], $opt);

        $k = $opt["prev"].$name.$opt["sufx"].$opt["gap"];
        $row = [];
        $row[] = $this->rowTab($k);
        $quote = $opt["quote"];
        if (is_numeric($val)) {
            $row[] = $val;
        } else if (is_string($val)) {
            if ($quote!==false && in_array($quote, ["'","\""])) {
                $row[] = $quote.$val.$quote;
            } else {
                $row[] = $val;
            }
        } else if (is_array($val) && Is::indexed($val)) {
            $nval = array_map(function($vi) use ($quote) {
                if (is_numeric($vi) || $quote===false) return $vi;
                return $quote.$vi.$quote;
            }, $val);
            if ($quote!==false) {
                $row[] = "[".implode(",",$nval)."]";
            } else {
                $row[] = implode(",", $nval);
            }
        } else {
            $row[] = $quote===false ? "\"\"" : $quote.$quote;
        }
        $row = implode("", $row);

        return $this->rowAdd($row, $opt["rn"], $idx);
    }

    /**
     * 根据当前字符串，计算下一个 tab位 需要增加几个空格，并附加到字符串后
     * @param String $s 要处理的字符串
     * @param Int $ti 用空格模拟 tab 每 $ti 个空格表示一个 tab 位 默认 4 个空格
     * @return String 增加一定数量空格后的 字符串
     */
    public function rowTab($s="", $ti=4)
    {
        if (!is_int($ti) || $ti<=0) $ti = 2;
        if (!is_string($s)) return "";
        if (!Is::nemstr($s)) {
            //如果输入空字符串，直接输出 $ti 个空格
            return array_fill(0, $ti, " ");
        }

        $ln = strlen($s);
        $sn = ceil($ln/$ti) * $ti - $ln;
        if ($sn<=0) $sn = $ti;
        $ss = array_fill(0, $sn, " ");
        return $s.implode("",$ss);
    }

    /**
     * 将 内容行数组 合并为 字符串
     * @param Bool $clear 是否清空 rows 默认 false
     * @param String $glue 换行符 默认 \r\n
     * @return String 合并后的 字符串
     */
    public function rowCombine($clear=false, $glue=null)
    {
        //换行符
        if (!Is::nemstr($glue)) $glue = $this->rn;
        //文件行数组
        $rows = $this->resource->rows;
        //合并
        $cnt = implode($glue, $rows);
        //清空 content
        if ($clear === true) {
            //$this->resource->rows = [];
            $this->clearRows();
        }
        
        return $cnt;
    }
}