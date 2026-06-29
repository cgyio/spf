<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型(表) 配置参数解析类
 * 解析模型中各字段的 creation-sql 得到字段的 类型、默认值 等参数
 * 
 * 
 * SPF-Orm 模块支持的 creation-sql 语法：
 *      `字段名` [字段类型(精度约束)] [静态约束...] [DEFAULT *] [其他约束...]
 * 
 * 字段类型 由 module/orm/Types::$collection[] 指定(将会收集所有 原生、特殊以及自定义的字段类型)，例如：
 *      varchar, integer, json, time, ...
 * 
 * 支持的静态约束(true|false) ：unsigned, autoincrement, primary, required, unique
 * 静态约束对应的 字符串 由 数据库驱动子类 在 AnyDriver::$staticBinds[] 数组中定义
 * 在 AnyDriver::whenCollect() 方法中，此数据库对应的 $staticBinds[] 会被收集到 CreationSqlParser::$staticBinds[] 中
 * 
 * 默认值 统一使用 DEFAULT * 写法
 * 
 * 其他约束 由 数据库驱动子类 在 AnyDriver::$extraBinds[] 数组中定义
 * 在 AnyDriver::whenCollect() 方法中，此数据库对应的 $extraBinds[] 会被收集到 CreationSqlParser::$extraBinds[] 中
 * 
 * !! 所有 约束字符串 必须 全大写
 */

namespace Spf\module\orm\config\model\parser;

use Spf\module\orm\OrmException;
use Spf\module\orm\config\model\Parser;
use Spf\module\orm\Driver;
use Spf\module\orm\Types;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

class CreationSql extends Parser 
{
    /**
     * creation-sql 中可能存在的 固定约束字符
     * !! 支持的静态约束(true|false) ：unsigned, autoincrement, primary, required, unique
     * !! 在 各数据库驱动子类的 AnyDriver::$staticBinds[] 中必须完整定义 全部固定约束 以及 其对应的字符串 
     * !! 在 Orm 初始化阶段，将会收集所有支持的 数据库驱动的 固定约束字符串写法
     */
    protected static $staticBinds = [
        "unsigned"      => [],
        "autoincrement" => [],
        "primary"       => [],
        "required"      => [],
        "unique"        => [],
    ];
    //各静态约束在 解析得到的 字段参数中的 参数项名称
    protected static $staticBindProps = [
        "unsigned"      => "isUnsigned",
        "autoincrement" => "isId",
        "primary"       => "isPk",
        "required"      => "isRequired",
        "unique"        => "isUnique",
    ];
    //收集 各数据库驱动类型 自定义的 staticBinds[]
    protected static $driverStaticBinds = [
        //"mysql" => [ "unsigned" => "", ... ],
        //"sqlite" => [ ... ],
    ];

    /**
     * creation-sql 中可能存在的 其他约束字符
     * !! 在 各数据库驱动子类的 AnyDriver::$extraBinds[] 中可以定义 支持的其他约束字符 
     * !! 在 Orm 初始化阶段，将会收集所有支持的 数据库驱动的 其他约束字符串写法
     */
    protected static $extraBinds = [
        //"mysql" => [ ... ],
    ];

    /**
     * 收集 各 数据库驱动类型的 静态约束字符串
     * !! 由 数据库驱动子类  在 whenCollect() 方法中调用
     * @param String $driver 数据库驱动名 mysql|sqlite|...  foo_bar
     * @param Array $binds 约束字符串 数组 [ "autoincrement" => "AUTO_INCREMENT", ... ]
     * @return Bool
     */
    public static function collectStaticBinds($driver, $binds=[])
    {
        if (!Is::nemstr($driver) || !Is::nemarr($binds) || !Is::associate($binds)) return true;
        if (Driver::support($driver)===false) return true;
        $driver = Str::snake($driver, "_");
        
        //收集
        foreach ($binds as $bn => $bi) {
            if (!Is::nemstr($bi)) continue;
            //约束字符 必须 全大写
            $bi = strtoupper($bi);

            //收集到 static::$staticBinds[]
            if (!isset(static::$staticBinds[$bn])) static::$staticBinds[$bn] = [];
            if (!in_array($bi, static::$staticBinds[$bn])) static::$staticBinds[$bn][] = $bi;

            //收集到 static::$driverStaticBinds[]
            if (!isset(static::$driverStaticBinds[$driver])) static::$driverStaticBinds[$driver] = [];
            static::$driverStaticBinds[$driver][$bn] = $bi;
        }

        return true;
    }

    /**
     * 收集 各 数据库驱动类型的 其他约束字符串
     * !! 由 数据库驱动子类  在 whenCollect() 方法中调用
     * @param String $driver 数据库驱动名 mysql|sqlite|...  foo_bar
     * @param Array $binds 约束字符串 数组 [ "支持的约束字符", ... ]
     * @return Bool
     */
    public static function collectExtraBinds($driver, $binds=[])
    {
        if (!Is::nemstr($driver) || !Is::nemarr($binds) || !Is::indexed($binds)) return true;
        if (Driver::support($driver)===false) return true;
        $driver = Str::snake($driver, "_");

        //约束字符 必须 全大写
        $binds = array_map(function($bi) {
            return strtoupper($bi);
        }, $binds);

        //合并到 static::$extraBinds[]   Arr::extend() 合并 indexed 数组 默认使用 去重合并 
        static::$extraBinds = Arr::extend(static::$extraBinds, [
            $driver => $binds
        ]);

        return true;
    }



    /**
     * CreationSql 解析器运行时参数
     * !! 在依次解析每个字段 creation-sql 时，会在每次解析完成后 重置这些参数
     */
    //当前解析的 字段名 foo_bar
    protected $colk = "";
    //当前解析的字段的 原始 creation-sql
    protected $csql = "";
    //将原始 creation-sql 拆分为固定的 段
    protected $sqla = [
        //字段类型定义段
        "type" => "",
        //约束字符 段
        "binds" => "",
        //默认值段
        "default" => "",
    ];
    //当前字段 creation-sql 解析得到的临时参数结构
    protected $sqlTempDft = [
        //当前 driver 驱动名 foo_bar
        "driver" => "",

        //构建符合 当前 driver 的 creation-sql 的 字符序列
        "nsql" => [],
        "sqlp" => [
            "type" => "",
            "binds" => [],
            "default" => "",
            "extra" => [],
        ],

        //字段类型
        //配置的字段类型，Types::support()!==false
        "defType" => "",
        //字段类型 类全称
        "typeCls" => "",
        //字段类型类 beforeParse 预解析结果
        "typePs" => null,
        //Types::parseColumnConfig() 返回的此字段的特殊类型参数 [ isFoo=>true, foo=>[...] ]
        "typeConf" => [],
        //Types::getTypesArr() 返回的完整字段类型数组
        "type" => [
            //定义的字段类型 不含()  $columnTypeDef[] 中的键名
            "def" => "",
            //符合当前数据库类型的 字段类型，不含() 大写
            "db" => "",
            //前端 js 中的字段数据类型
            "js" => "",   //Array|Object|Number|Boolean
            //后端 php 中的字段数据类型
            "php" => "",  //Array|Integer|Float|Boolean
        ],
        
        //符合 php 类型的 默认值
        "default" => [
            "value" => null,
            "params" => [],
        ],
        
        //特殊字段属性 在 static::$staticBindProps[] 中定义的 静态约束对应的 字段参数项
        "special" => [
            //unsigned
            "isUnsigned"    => false,
            //autoincrement
            "isId"          => false,
            //primary
            "isPk"          => false,
            //required
            "isRequired"    => false,
            //unique
            "isUnique"      => false,
        ],
    ];

    /**
     * 解析某个字段时 重置运行时参数
     * @param String $colk 字段名 foo_bar
     * @return $this
     */
    protected function resetRuntime($colk)
    {
        $csql = $this->origin["creation"][$colk] ?? null;
        if (!Is::nemstr($csql)) return $this->parseException(0);
        //重置
        $this->colk = $colk;
        $this->csql = static::csp($csql);
        $this->sqla = static::cut($csql);

        return $this;
    }

    /**
     * 按顺序调用 creation-sql 解析系列方法，生成最终要写入 $this->temp 的解析结果
     * @param String $colk 字段名 foo_bar
     * @return Array 解析得到的 临时数据，结构与 $this->sqlTempDft[] 一致
     */
    protected function parseColumn($colk)
    {
        //重置运行时
        $this->resetRuntime($colk);

        //准备过程中数据 $temp，会被依次传入每个 解析方法
        $temp = Arr::copy($this->sqlTempDft);
        $temp["driver"] = $this->driver::driver();

        //开始依次执行解析
        $this->parseColumnType($temp)           // 0  解析字段类型
             ->parseColumnStaticBinds($temp)    // 1  解析静态约束
             ->parseColumnTypesArr($temp)       // 2  解析完整字段类型数组
             ->parseColumnDefault($temp)        // 3  解析默认值
             ->parseColumnExtraBinds($temp);    // 4  解析额外约束

        return $temp;
    }

    /**
     * 各字段的 creation-sql 解析系列方法
     * !! 必须在 resetRuntime() 执行后，运行时指向要解析的字段 后 执行这些方法
     * @param Array &$temp 过程中数据，结构与 $this->sqlTempDft 一致
     * @return $this
     */
    //解析字段完整类型，并进一步解析 字段默认值，特殊字段类型参数
    protected function parseColumnType(&$temp)
    {
        $tps = $this->sqla["type"];
        $tpo = Types::getDefineType($tps." ");
        if (!Is::nemarr($tpo)) return $this->parseException(1, $tps);
        $type = $tpo["type"];
        $prec = $tpo["precision"];

        //调用 Types 字段类型类
        $typeCls = Types::get($type);
        //执行预解析
        $typePs = $typeCls::beforeParse($this->colk, $this, [
            //传入 creation-sql 中的默认值定义字符串
            "default" => !Is::nemstr($this->sqla["default"]) ? null : $this->sqla["default"]
        ]);
        if (is_null($typePs)) return $this->parseException(2, $type);
        //解析得到此字段的 特殊字段类型参数
        $typeConf = $typeCls::parseColumnConfig($typePs);
        //字段的定义类型写入 $temp 数据
        $temp["defType"] = $type;
        $temp["typeCls"] = $typeCls;
        $temp["typePs"] = $typePs;
        $temp["typeConf"] = Is::nemarr($typeConf) ? $typeConf : [];

        //获取字段定义类型 在当前 driver 下的 映射类型
        $mptp = Types::getMappingType($temp["driver"], $type);
        //处理 符合当前 driver 的 字段类型定义 sql 语句
        $tpsql = $mptp;
        if (Is::nemstr($prec)) {
            if (strpos($tpsql,"(")!==false) {
                $tpsql = explode("(", $tpsql)[0].$prec;
            } else {
                $tpsql .= $prec;
            }
        }
        //字段类型定义 写入 新 creation-sql 序列中
        $temp["nsql"][] = $tpsql;
        $temp["sqlp"]["type"] = $tpsql;

        return $this;
    }
    //解析 固定的 约束条件判断
    protected function parseColumnStaticBinds(&$temp)
    {
        $bind = $this->sqla["binds"];
        if (!Is::nemstr($bind)) return $this;

        $props = static::$staticBindProps;
        $dbind = static::$driverStaticBinds[$temp["driver"]] ?? [];

        //依次查找 固定的 约束字符
        foreach (static::$staticBinds as $prop => $binds) {
            //剩余 bind 字符为空
            if (!Is::nemstr($bind)) break;
            //约束字符 $prop 必须被定义，并被 driver 支持
            if (!isset($props[$prop]) || !isset($dbind[$prop])) continue;

            //判断 约束字符串中是否包含 此约束
            $bs = static::parseStaticBind($bind, ...$binds);
            if (!Is::nemarr($bs)) continue;

            //如果包含此 约束，将当前 driver 对应的 约束字符，添加到 新的 creation-sql 序列中
            if ($bs["result"]===true) {
                $temp["nsql"][] = $dbind[$prop];
                $temp["sqlp"]["binds"][] = $dbind[$prop];
            }

            //合并到 $temp 结果
            $temp["special"][$props[$prop]] = $bs["result"];

            //保存 处理后的 约束字符串 等待继续处理
            $bind = $bs["sql"];
        }
        //剩余的 bind 约束字符保存到 $this->sqla["binds"]
        $this->sqla["binds"] = $bind;

        return $this;
    }
    //解析 完整的 字段类型数组
    protected function parseColumnTypesArr(&$temp)
    {
        $tparr = $temp["typeCls"]::parseColumnTypesArr($temp["typePs"]);
        if (!Is::nemarr($tparr)) {
            //解析错误，终止后续解析
            return $this->parseException(3, $temp["defType"]);
        } else {
            //合并到 $rtn 结果
            $temp["type"] = $tparr;
            return $this;
        }
    }
    //解析 默认值
    protected function parseColumnDefault(&$temp)
    {
        //调用 Types::parseColumnDefaultParams() 解析字段默认值
        $dft = $temp["typeCls"]::parseColumnDefault($temp["typePs"]);
        if (!Is::nemarr($dft)) return $this->parseException(4);

        //insql
        $insql = $dft["params"]["insql"] ?? "";
        unset($dft["params"]["insql"]);

        //写入 $temp 默认值
        $temp["default"] = $dft;
        //如果存在默认值
        if (!is_null($dft["value"]) && Is::nemstr($insql)) {
            //写入 $nsql[]
            $temp["nsql"][] = "DEFAULT";
            $temp["nsql"][] = $insql;
            $temp["sqlp"]["default"] = $insql;
        }

        return $this;
    }
    //处理其他的 约束字符
    protected function parseColumnExtraBinds(&$temp)
    {
        $bind = $this->sqla["binds"] ?? "";
        if (!Is::nemstr($bind)) return $this;

        $bindup = strtoupper($bind);
        $exbs = static::$extraBinds[$temp["driver"]] ?? [];
        if (Is::nemarr($exbs)) {
            foreach ($exbs as $exb) {
                if (strpos($bindup, $exb)!==false) {
                    //存在的 额外约束字符 收集到 $nsql
                    $temp["nsql"][] = $exb;
                    $temp["sqlp"]["extra"][] = $exb;
                }
            }
        }

        return $this;
    }

    /**
     * 在解析各字段的 creation-sql 时，快速抛出异常
     * @param Int $errcode 异常代码
     * @param Array $args 额外参数
     * @return $this
     */
    protected function parseException($errcode=0, ...$args)
    {
        $defs = [
            0 => "没有指定字段的 creation-sql 语句",
            1 => "定义类型 %{1}% 未被支持",
            2 => "定义类型 %{1}% 无法生成有效的预解析结果",
            3 => "无法解析类型 %{1}% 的完整类型数组",
            4 => "无法解析默认值",
        ];

        $errmsg = $defs[$errcode] ?? "发生未知错误";
        if (Is::nemarr($args)) {
            foreach ($args as $i => $k) {
                $errmsg = str_replace("%{".($i+1)."}%", $k, $errmsg);
            }
        }

        throw new OrmException($this->dbn."/".$this->modk.",解析字段 ".$this->colk." 的 creation-sql 时，".$errmsg, "orm/parse");
        return $this;
    }


    
    /**
     * !! Parser 子类必须定义
     */
    //此 数据模型(表) 参数解析类的 名称 foo_bar
    protected static $parser = "creation_sql";

    /**
     * 解析过程中的 数据，这些数据最终将被 写入 $this->context 
     * 通常指定了 此解析器将要修改 $this->context 中的 哪些数据
     * !! 与 DbConfig::$exportModelConf[] 结构一致
     * !! 覆盖父类，指定当前解析其将要修改此数据模型(表)的 哪些参数项目
     */
    protected $temp = [
        //生成符合 当前 driver 的 creation-sql
        "creation" => [],
        //indexs 索引数组，用于生成 sql
        "indexs" => [],
        //解析得到的 各字段的 类型、默认值 等参数，保存到此
        "column" => [],
        //所有 字段名 数组
        "columns" => [],
        //特殊类型 字段名 数组
        "special" => [],
    ];

    /**
     * 解析入口
     * 解析 $this->origin 参数，将生成的最终参数 写入 $this->context 并返回
     * !! 必须实现，覆盖父类
     * !! 解析每个字段的 creation-sql 得到字段的 类型、默认值 等参数
     * @return Array 解析得到的 此数据模型(表)参数 []
     */
    public function parse()
    {
        //依次解析每个字段，使用 origin["columns"] 字段元数据作为循环来源
        $this->eachColumn(function ($colk, $colv) {
            $csql = $this->origin["creation"][$colk] ?? null;
            if (!Is::nemstr($csql)) return false;

            //调用解析方法
            $temp = $this->parseColumn($colk);

            //生成 符合当前 driver 的 creation-sql
            $nsql = implode(" ", $temp["nsql"]);

            //生成当前字段的 解析结果，合并完整的 字段默认参数结构
            $colc = $this->config->stdExport(
                "exportColumnConf", 
                [
                    //合并 $colv 字段元数据
                    "name"      => $colk,
                    "title"     => Is::nemstr($colv[0]) ? $colv[0] : $colk,
                    "desc"      => Is::nemstr($colv[1]) ? $colv[1] : $colk,
                    "width"     => Is::realnum($colv[2]) ? (int)$colv[2] : 3,
                    
                    //合并 $temp 中相关数据
                    "creation"  => $nsql,
                    "creationParams" => $temp["sqlp"],
                    "type"      => $temp["type"],
                    "default"   => $temp["default"],
                ],
                //合并 special
                Is::nemaso($temp["special"]) ? $temp["special"] : [],
                //合并 typeConf 特殊字段类型参数
                Is::nemaso($temp["typeConf"]) ? $temp["typeConf"] : [],
            );
            
            //生成 要写入 $this->temp 中的数据
            $rtn = [
                "creation" => [
                    $colk => $nsql,
                ],
                "column" => [
                    $colk => $colc,
                ],
                "columns" => [$colk],
            ];

            //special 字段列表
            if (Is::nemaso($temp["special"])) {
                $spec = [];
                foreach ($temp["special"] as $k => $b) {
                    if ($b!==true) continue;
                    $kp = Str::snake(substr($k, 2), "_");
                    $spec[$kp] = [$colk];
                }
                if (Is::nemarr($spec)) $rtn["special"] = $spec;
            }

            return $rtn;
        });

        //origin["column"]["indexs"] 写入 $temp["indexs"]
        $this->temp["indexs"] = $this->origin["column"]["indexs"] ?? [];

        //解析完成，将 $this->temp 写入 $this->context 
        $this->setCtx($this->temp);

        //!! forDev
        //var_dump($this->context);
        //exit;

        return $this->context;
    }



    /**
     * 静态方法
     */

    //合并 sql 中 多空格 --> 单空格  并去除头尾空格
    public static function csp($sql)
    {
        return preg_replace("/\s+/", " ", trim($sql));
    }

    //将 sql 拆分为 固定的 段
    public static function cut($sql)
    {
        $sql = static::csp($sql);

        //结果
        $rtn = [
            "type" => "",
            "binds" => "",
            "default" => "",
        ];

        //空格拆分
        $sqla = explode(" ", $sql);

        //字段类型定义 段
        $rtn["type"] = array_shift($sqla);
        if (empty($sqla)) return $rtn;

        //DEFAULT 拆分
        $sql = static::csp(implode(" ", $sqla));
        if (strpos(strtoupper($sql), "DEFAULT ")!==false) {
            $sqla = [];
            $dftks = ["DEFAULT", "default", "Default"];
            foreach ($dftks as $dftk) {
                if (strpos($sql, $dftk." ")!==false) {
                    $sqla = explode($dftk." ", $sql);
                    break;
                }
            }
            if (!empty($sqla)) {
                $sql = $sqla[0];
                $dftstr = trim($sqla[1]);
                $quo = ["'","\""];
                $hasQuo = false;
                foreach ($quo as $q) {
                    if (substr($dftstr, 0,1)===$q) {
                        $hasQuo = true;
                        $sqlb = explode($q, $dftstr);
                        $rtn["default"] = $q.$sqlb[1].$q;
                        $sql .= implode($q, array_slice($sqlb, 2));
                        break;
                    }
                }
                if (!$hasQuo) {
                    $sqlb = explode(" ", $dftstr);
                    $rtn["default"] = array_shift($sqlb);
                    $sql .= implode(" ", $sqlb);
                }
                $sql = static::csp($sql);
            }
        }
        //剩余 sql 为空
        if (!Is::nemstr($sql) || $sql===" ") return $rtn;

        //约束字符
        $rtn["binds"] = trim($sql);

        return $rtn;
    }

    /**
     * 从传入的 $sql 中查找 固定的约束条件，返回 约束标记 true|false，以及去除 约束条件字符的 $sql
     * @param String $sql
     * @param Array $binds 要查找的 约束条件字符串，可以有多个
     * @return Array 
     *  [
     *      "result" => true|false,
     *      "sql" => "处理后，去除了约束条件字符串的 $sql，可继续执行其他操作"
     *  ]
     */
    protected static function parseStaticBind($sql, ...$binds)
    {
        if (!Is::nemarr($binds)) return null;

        //约束条件字符串 默认全大写，转为 全小写
        $bindslow = array_map(function($bi) {
            return strtolower($bi);
        }, $binds);
        //合并 大小写，用于 字符串替换
        $bindsall = array_merge([], $binds, $bindslow);

        //准备返回结果
        $rtn = [
            "result" => false,
            "sql" => $sql,
        ];

        //准备 $sql
        $sql = static::csp($sql);
        $sqlup = strtoupper($sql);
        //查找 约束条件
        if (Str::hasAny($sqlup, ...$binds)) {
            $rtn["result"] = true;
            $rtn["sql"] = str_replace($bindsall, "", $sql);
        }
        //多空格
        $rtn["sql"] = static::csp($rtn["sql"]);

        return $rtn;
    }

    /**
     * 根据 字段的 creationParams 参数，重新生成 creation-sql
     * creationParams 参数结构与 $this->sqlTempDft["sqlp"] 一致
     * !! 外部调用，用于生成新的 creation-sql
     * @param Array $sqlp
     * @return String|null 生成 creation-sql
     */
    public static function createCreationSql($sqlp=[])
    {
        if (!Is::nemaso($sqlp)) return null;

        //重新生成 creation-sql
        $nsql = [];
        //字段类型
        if (!Is::nemstr($sqlp["type"])) return null;
        $nsql[] = $sqlp["type"];
        //字段 静态约束
        if (Is::nemidx($sqlp["binds"])) $nsql = array_merge($nsql, $sqlp["binds"]);
        //字段默认值
        if (Is::nemstr($sqlp["default"])) {
            $nsql[] = "DEFAULT";
            $nsql[] = $sqlp["default"];
        }
        //字段其他约束
        if (Is::nemidx($sqlp["extra"])) $nsql = array_merge($nsql, $sqlp["extra"]);
        //拼接
        $nsql = implode(" ", $nsql);

        return $nsql;
    }
}