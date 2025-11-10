<?php
/**
 * 工具类 
 * DSL 专用于 Spf 框架内部的 特定领域语言 基类
 * 
 * 基于 Spf 框架内部的 核心类实例的 数据，将遵循特定语法的 DSL 语句，转译为 可执行的 php 语句，最终执行并返回结果
 * 
 * 例如：
 *      ?env.config.dir[app]!=app&nemarr(env.dir)&!nemstr(env.dir[app]) 将被解析为：
 *          ?           表示返回值为 布尔值
 *          语句解析为：  return Env::$current->config->dir["app"] !== "app" && Is::nemarr(Env::$current->dir) && Is::nemstr(Env::$current->dir["app"]) === false
 * 
 * 如果指定了 默认数据源为 Env::$current 则可以简写为：?dir[app]!=app&nemarr(dir)&!nemstr(dir[app])
 * 
 * !! 创建 DSL 实例必须在 所有核心类都已完成实例化之后
 */

namespace Spf\util;

use Spf\Env;
use Spf\Runtime;
use Spf\Request;
use Spf\Response;
use Spf\Middleware;
use Spf\Module;
use Spf\App;

class Dsl 
{
    /**
     * 定义 DSL 关键字
     */
    protected static $keyword = [
        //数据源
        "env","runtime","request","response","middleware","module","app",
        //指代 默认数据源
        "SCOPE",
    ];

    /**
     * 定义 DSL 语句的 语法结构
     * 以 返回值类型 作为大类区分
     */
    protected static $syntax = [
        //返回 布尔类型
        "bool" => [
            //以 ? 开头的 语句
            "match" => "/^\?(.*)$/",
            //字句语法
            "clause" => [
                //直接比较 形式的子句 foo.bar=true | foo.bar[jaz.tom]!=string | foo.bar[jaz].tom>=123
                //开始|中间
                "eq" => "/((__VAR__)(__LOGIC__)(__VAL__))__END__/U",
                //结尾
                "eq_end" => "/((__VAR__)(__LOGIC__)(__VAL__))$/U",

                //调用方法 形式的子句 util.is::fooBar('string', foo.bar) | app.fooBar(123, foo.bar[jaz]) | fooBar(...)
                //开始|中间
                "fn" => "/((__FCN__)\((__ARG__)\))__END__/U",
                //结尾的 function(...args) 形式
                "fn_end" => "/((__FCN__)\((__ARG__)\))$/U",

                //调用方法，然后比较结果的 util.is::fooBar('string', foo.bar)>=123
                //开始|中间
                "fneq" => "/((__FCN__)\((__ARG__)\)(__LOGIC__)(__VAL__))__END__/U",
                //结尾的 function(...args) 形式
                "fneq_end" => "/((__FCN__)\((__ARG__)\)(__LOGIC__)(__VAL__))$/U",

            ],
        ],

        //TODO: 待支持其他类型返回值
    ];

    /**
     * 定义 DSL 语句子句的 语法结构
     */
    protected static $clause = [
        //变量名 正则  foo.bar | foo.bar[jaz.tom] | foo.bar[jaz].tom
        "var" => "[a-zA-Z0-9_.[\]]+",
        //方法名 正则  util.is::fooBar(...) | !app.fooBar(...) | fooBar(...)
        "fcn" => "[a-zA-Z0-9_:.!]+",
        //值 正则
        "val" => "[a-zA-Z0-9_.' ]+",
        //方法参数 正则
        "arg" => "[a-zA-Z0-9_.,' [\]]+",
        //比较逻辑符号
        "logic" => "[=><!]+",
        //布尔类型 语句结尾
        "end" => "[&|)]+",

        //额外解析 变量名 正则  foo.bar | foo.bar[jaz.tom] | foo.bar[jaz].tom
        "varpt" => "([a-zA-Z0-9_.]+)(\[[a-zA-Z0-9_.]+\]([a-zA-Z0-9_.]+)?)?",
    ];

    /**
     * 指定默认数据源 用于简写 变量
     * 默认数据源必须是 某个类的实例
     */
    public $source = null;
    //可以指定 默认数据源内部 用作数据源的 属性名，属性值必须是 关联数组
    public $sourceProperties = [
        //属性名 数组，默认会在这些数组中 查找数据
    ];

    /**
     * exec 记录
     */
    public $history = [
        /*
        "dsl" => [
            "syntax" => [],
            "parsed" => [],
            "evals" => [],
            "eval" => "",
            "result" => mixed
        ],
        ...
        */
    ];

    /**
     * 构造
     * @param Mixed $source 默认数据源 不指定则使用 App::$current
     * @param Array $properties 默认数据源中的 默认查找数据的 属性值 数组
     * @return void
     */
    public function __construct($source=null, ...$properties)
    {
        if (is_null($source)) $source = Env::$current;
        $this->source = $source;
        $this->sourceProperties = $properties;
    }

    /**
     * 入口方法
     * 传入 DSL 语句，解析，执行，返回结果
     * @param String $dsl 语句
     * @return Mixed
     */
    public function exec($dsl)
    {
        if (!Is::nemstr($dsl)) return null;
        //获取 语法参数
        $syntax = $this->getSyntax($dsl);
        if (!Is::nemarr($syntax)) return null;
        //开始解析
        $parsed = $this->parse($dsl, $syntax);
        if (!Is::nemarr($parsed)) return null;
        //执行 eval
        $type = $syntax["type"];
        $em = "eval".Str::camel($type, true);
        if (!method_exists($this, $em)) return null;
        //创建 历史记录
        $this->history[$dsl] = [
            "syntax" => $syntax,
            "parsed" => $parsed,
        ];
        $rtn = $this->$em($dsl, $parsed);
        //var_dump($this->history[$dsl]);

        return $rtn;
    }

    /**
     * 静态入口方法
     * @param String $dsl 语句
     * @param Mixed $source 默认数据源 不指定则使用 App::$current
     * @param Array $properties 默认数据源中的 默认查找数据的 属性值 数组
     * @return Mixed
     */
    public static function invoke($dsl, $source=null, ...$properties)
    {
        $dsler = new Dsl($source, ...$properties);
        $rtn = $dsler->exec($dsl);
        //var_dump($dsler->lastExecResult());
        unset($dsler);
        return $rtn;
    }



    /**
     * 工具方法
     */

    /**
     * 获取最新一个 history
     * @return Array
     */
    public function lastExecResult()
    {
        if (!Is::nemarr($this->history)) return [];
        $dsls = array_keys($this->history);
        $ldsl = array_slice($dsls, -1)[0];
        return $this->history[$ldsl];
    }

    /**
     * 根据传入的 dsl 语句，获取 返回值类型，以及对应的 语法子句正则数组
     * @param String $dsl
     * @return Array|null
     *  [
     *      # 返回值类型
     *      "type" => "bool",
     * 
     *      # 子句语法正则数组
     *      "clause" => [
     *          "子句类型" => "正则",
     *          ...
     *      ],
     * 
     *      # 匹配到的 dsl 有效语句，例如：Bool 类型语句，将返回 去除 ? 的 dsl 语句
     *      "dsl" => ""
     *  ]
     */
    public function getSyntax($dsl)
    {
        if (!Is::nemstr($dsl)) return null;
        //所有定义的 syntax
        $syntaxes = static::$syntax;
        //所有定义的 子句正则
        $clauses = static::$clause;
        $rtn = [];
        foreach ($syntaxes as $type => $syc) {
            //判断正则
            $mpt = $syc["match"] ?? null;
            if (!Is::nemstr($mpt)) continue;
            //匹配
            $mt = preg_match_all($mpt, $dsl, $mts);
            if (empty($mts[0])) continue;
            //子句正则列表
            $regs = $syc["clause"] ?? [];
            if (!Is::nemarr($regs)) continue;
            //匹配成功
            $rtn["type"] = $type;
            $rtn["dsl"] = $mts[1][0];
            //补全列表中正则语句
            $rtn["clause"] = [];
            foreach ($regs as $rk => $reg) {
                foreach ($clauses as $clk => $clpt) {
                    $reg = str_replace("__".strtoupper($clk)."__", $clpt, $reg);
                }
                $rtn["clause"][$rk] = $reg;
            }
            //中断
            break;
        }
        if (!Is::nemarr($rtn)) return null;
        return $rtn;
    }

    /**
     * 解析 dsl 子句
     * @param String $dsl
     * @param Array $syntax 语法正则相关参数，getSyntax 方法的返回值
     * @return Array|null
     *  [
     *      "子句类型" => [
     *          "子句中片段，可用于替换" => [
     *              "参数" => "值",
     *              ...
     *          ],
     *      ],
     *      ...
     *  ]
     */
    public function parse($dsl, $syntax=[])
    {
        if (!Is::nemstr($dsl) || !Is::nemarr($syntax)) return null;
        //解析参数
        //返回值类型
        $type = $syntax["type"];
        //正则数组
        $regs = $syntax["clause"];
        if (!Is::nemstr($type) || !Is::nemarr($regs)) return null;
        //开始解析
        $rtn = [];
        foreach ($regs as $rtype => $reg) {
            preg_match_all($reg, $dsl, $mts);
            if (empty($mts[0])) continue;
            //调用对应的 解析方法
            $pm = "parse".Str::camel($type, true).Str::camel($rtype, true);
            if (method_exists($this, $pm)) {
                $pmrtn = $this->$pm($mts);
                if (!Is::nemarr($pmrtn)) continue;
                $rtn[$rtype] = $pmrtn;
            }
        }
        return $rtn;
    }



    /**
     * 定义 对应 syntax 的 解析方法
     * !! 在 Dsl::$syntax[type]["clause"] 中定义的每一个 正则，都应有对应的 解析方法
     * @param Array $mts 匹配到的 参数，根据 正则中的 () 生成的 匹配数据
     * @return Array 
     *  [
     *      "子句中片段，可用于替换" => [
     *          "参数" => "值",
     *          ...
     *      ],
     *      ...
     *  ]
     */
    //解析 bool->clause->eq 子句
    protected function parseBoolEq($mts=[])
    {
        $rtn = [];
        foreach ($mts[0] as $i => $mti) {
            //字句片段
            $sk = $mts[1][$i];
            //返回值
            $rtn[$sk] = [
                //变量名
                "var" => $this->parseVar($mts[2][$i]),
                //值
                "val" => $this->parseVal($mts[4][$i]),
                //比较逻辑符号
                "logic" => $this->parseLogic($mts[3][$i]),
            ];
        }
        return $rtn;
    }
    //解析 bool->clause->eq_end 子句
    protected function parseBoolEqEnd($mts=[])
    {
        return $this->parseBoolEq($mts);
    }
    //解析 bool->clause->fn 子句
    protected function parseBoolFn($mts=[])
    {
        $rtn = [];
        foreach ($mts[0] as $i => $mti) {
            //字句片段
            $sk = $mts[1][$i];
            //返回值
            $rtn[$sk] = [
                //方法名
                "func" => $this->parseFunc($mts[2][$i]),
                //参数
                "args" => $this->parseArgs($mts[3][$i]),
            ];
        }
        return $rtn;
    }
    //解析 bool->clause->fn_end 子句
    protected function parseBoolFnEnd($mts=[])
    {
        return $this->parseBoolFn($mts);
    }
    //解析 bool->clause->fneq 子句
    protected function parseBoolFneq($mts=[])
    {
        $rtn = [];
        foreach ($mts[0] as $i => $mti) {
            //字句片段
            $sk = $mts[1][$i];
            //返回值
            $rtn[$sk] = [
                //方法名
                "func" => $this->parseFunc($mts[2][$i]),
                //参数
                "args" => $this->parseArgs($mts[3][$i]),
                //值
                "val" => $this->parseVal($mts[5][$i]),
                //比较逻辑符号
                "logic" => $this->parseLogic($mts[4][$i]),
            ];
        }
        return $rtn;
    }
    //解析 bool->clause->fneq_end 子句
    protected function parseBoolFneqEnd($mts=[])
    {
        return $this->parseBoolFneq($mts);
    }



    /**
     * 解析通用的 子句
     * @param String $clause 子句字符串
     * @return String 处理后的，关联到数据源的，可拼接后 eval 执行的 语句字符串
     */
    //通用解析 关键字
    protected function parseKeyword($clause)
    {
        if (!Is::nemstr($clause)) return $clause;
        //替换 关键字
        $kws = static::$keyword;
        //是否存在 关键字
        $haskw = false;
        foreach ($kws as $kw) {
            $klen = strlen($kw);
            if (substr($clause, 0, $klen) === $kw) {
                $haskw = true;
                if ($kw==="SCOPE") {
                    $kins = "\$this->source";
                } else {
                    $kcls = Cls::find($kw);
                    if (class_exists($kcls)) {
                        $kins = $kcls."::\$current";
                    } else {
                        break;
                    }
                }
                if (substr($clause, $klen, 1) === ".") {
                    $clause = $kins."->".substr($clause, $klen+1);
                } else {
                    $clause = $kins.substr($clause, $klen);
                }
                break;
            }
        }
        //未找到关键字 返回 false
        if ($haskw !== true) return false;
        //找到了 则返回替换后的 $clause
        return $clause;
    }
    //解析 变量名
    protected function parseVar($clause)
    {
        if (!Is::nemstr($clause)) return $clause;
        //替换 关键字
        $haskw = $this->parseKeyword($clause);
        if ($haskw !== false) {
            $clause = $haskw;
            $haskw = true;
        }
        
        //如果不存在 关键字，在默认数据源中查找 foo.bar[jaz] --> 查找 foo  foo[bar] --> 查找 foo
        if ($haskw !== true) {
            $hasprop = false;
            $prop = explode(".", explode("[", $clause)[0])[0];
            if (Is::nemarr($this->sourceProperties)) {
                foreach ($this->sourceProperties as $sprop) {
                    //if (isset($this->source->$sprop[$prop])) { //存在 =null 问题
                    if (in_array($prop, array_keys($this->source->$sprop))) {
                        $clause = "\$this->source->".$sprop."['".$prop."']".substr($clause, strlen($prop));
                        $hasprop = true;
                        break;
                    }
                }
            }
            if ($hasprop !== true) {
                $clause = "\$this->source->".$clause;
            }
        }

        //匹配 [foo.bar] 转换为 ['foo']['bar']
        preg_match_all("/\[([a-zA-Z0-9_.]+)\]/", $clause, $mts);
        if (Is::nemarr($mts[0])) {
            //替换字符
            foreach ($mts[0] as $i => $mti) {
                //替换 [] 内部字符
                $csn = "['".implode("']['", explode(".",$mts[1][$i]))."']";
                $clause = str_replace($mti, $csn, $clause);
            }
        }

        //匹配 [...] 外部的 . 转为 ->
        $clause = str_replace(".", "->", $clause);

        return $clause;
    }
    //解析 值
    protected function parseVal($clause)
    {
        if (!Is::nemstr($clause)) return $clause;
        if (substr($clause, 0,1)==="'" || is_numeric($clause) || Is::ntf($clause)) return $clause;
        if (in_array($clause, ["yes","no"])) return $clause==="yes" ? "true" : "false";
        //可能是某个 变量名
        if (Str::hasAny($clause, ".","[","]")) return $this->parseVar($clause);

        //在 sourceProperties 中查找是否存在 键名为 $clause
        $hasprop = false;
        if (Is::nemarr($this->sourceProperties)) {
            foreach ($this->sourceProperties as $sprop) {
                if (isset($this->source->$sprop[$clause])) {
                    $clause = "\$this->source->".$sprop."['".$clause."']";
                    $hasprop = true;
                    break;
                }
            }
        }
        if ($hasprop !== true) {
            if (isset($this->source->$clause)) {
                $clause = "\$this->source->".$clause;
                $hasprop = true;
            }
        }
        if ($hasprop === true) return $clause;

        return "'$clause'";
    }
    //解析 方法名
    protected function parseFunc($clause)
    {
        if (!Is::nemstr($clause)) return $clause;
        //如果以 ! 开头
        $reverse = substr($clause, 0,1)==="!" ? true : false;
        if ($reverse) $clause = substr($clause, 1);

        //首先直接检查是否存在 函数名
        if (function_exists($clause)) return ($reverse ? "!" : "").$clause;
        /**
         * 针对 empty,isset 不是函数，而是 语言结构的 情况，手动排除
         */
        $specs = ["empty","isset"];
        if (in_array($clause, $specs)) return ($reverse ? "!" : "").$clause;

        //替换 关键字
        $haskw = $this->parseKeyword($clause);
        if ($haskw !== false) {
            $clause = $haskw;
            $haskw = true;
        }

        //如果没有关键字
        if ($haskw !== true) {
            if (strpos($clause, "::")!==false) {
                $clarr = explode("::", $clause);
                $clsp = str_replace(".","/",$clarr[0]);
                if (strpos($clsp, "/")===false) $clsp = "util/".$clarr[0];
                $cls = Cls::find($clsp);
                if (class_exists($cls)) {
                    $clause = $cls."::".$clarr[1];
                }
            } else if (method_exists(Is::class, $clause)){
                $clause = Is::class."::".$clause;
            } else {
                $clause = "\$this->source->".$clause;
            }
        }

        //. --> ->
        $clause = str_replace(".","->", $clause);
        return ($reverse ? "!" : "").$clause;
    }
    //解析 方法参数
    protected function parseArgs($clause)
    {
        if (!Is::nemstr($clause)) return $clause;
        //去除 空格
        $clause = preg_replace("/\s+/", " ", $clause);
        //去除 , 前后的 空格
        $clause = preg_replace("/\s*,\s*/", ",", $clause);
        //拆分
        $clarr = explode(",", $clause);
        //处理后
        $args = [];
        foreach ($clarr as $arg) {
            $args[] = $this->parseVal($arg);
        }
        return implode(",", $args);
    }
    //解析 比较逻辑字符
    protected function parseLogic($clause)
    {
        if (!Is::nemstr($clause)) return $clause;
        if ($clause === "=") return "===";
        if ($clause === "!=") return "!==";
        return $clause;
    }



    /**
     * 执行 eval
     * !! 在 Dsl::$syntax 中定义的每个 返回值类型，都需要定义一个 evalFooBar 方法
     * @param String $dsl 原始的 dsl 语句
     * @param Array $parsed 解析 dsl 得到的 eval 参数，parse 方法返回的值
     * @return Mixed 根据预定义的 返回值类型 返回对应的值
     */
    //执行 Bool 语句 返回 Bool 值
    protected function evalBool($dsl, $parsed=[])
    {
        if (!Is::nemstr($dsl) || !Is::nemarr($parsed)) return false;
        
        //从 parsed 数组中拆分对应语句 生成 eval 语句
        $evals = [];
        foreach ($parsed as $ctp => $clauses) {
            foreach ($clauses as $sk => $clc) {
                if (substr($ctp, 0, 2)==="eq") {
                    $var = $clc["var"];
                    $lgc = $clc["logic"];
                    $val = $clc["val"];
                    $evals[$sk] = "$var $lgc $val";
                } else if (substr($ctp, 0, 4)==="fneq") {
                    $func = $clc["func"];
                    $args = $clc["args"];
                    $lgc = $clc["logic"];
                    $val = $clc["val"];
                    $evals[$sk] = "$func($args) $lgc $val";
                } else if (substr($ctp, 0, 2)==="fn") {
                    $func = $clc["func"];
                    $args = $clc["args"];
                    //处理 ! 开头的 方法名
                    if (substr($func, 0,1)==="!") {
                        $evals[$sk] = substr($func, 1)."($args) === false";
                    } else {
                        $evals[$sk] = "$func($args)";
                    }
                }
            }
        }
        //写入 history
        $this->history[$dsl]["evals"] = $evals;
        //var_dump($evals);

        //依次执行 eval 语句，将得到的结果 替换到 dsl 语句中
        $evaldsl = $this->history[$dsl]["syntax"]["dsl"];
        foreach ($evals as $sk => $evi) {
            eval("\$evr = $evi;");
            $evr = $evr===true ? "true" : "false";
            //替换 dsl 中的语句
            $evaldsl = str_replace($sk, $evr, $evaldsl);
        }

        //& --> &&   | --> ||
        $evaldsl = str_replace("&", " && ", $evaldsl);
        $evaldsl = str_replace("|", " || ", $evaldsl);
        //写入 history
        $this->history[$dsl]["eval"] = $evaldsl;

        //将 替换后的 dsl 作为 eval 语句执行
        eval("\$rtn = $evaldsl;");
        //写入 history
        $this->history[$dsl]["result"] = $rtn;

        return $rtn;

    }


}