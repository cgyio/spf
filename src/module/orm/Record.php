<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型(表) 记录实例 基类
 * Model 数据模型(表) 类的实例方法，继承自此类
 */

namespace Spf\module\orm;

use Spf\module\orm\Model;
use Spf\module\orm\RecordSet;
use Spf\module\orm\Curd;
use Spf\module\orm\Types;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Event;

use Spf\traits\CoreInsGetter;

class Record 
{
    //快速获取核心类
    use CoreInsGetter;

    /**
     * 此模型实例的 原始数据
     * !! 来自 数据库，各字段值类型 == type["db"]
     * !! 如果此模型实例是手动创建的，则此项为空
     */
    protected $fromDb = [];

    /**
     * 模型实例数据，写入数据库之前，所有字段转为 db 类型后的数据
     * !! 将写入 数据库，各字段值类型 == type["db"]
     * !! 只有在执行 insert|update|logic_delete 等方法之前，由 $this->diff() 方法生成，其他情况下，此项为空
     * !! 最终写入数据库的数据
     */
    protected $toDb = [];

    /**
     * 模型实例的 转换后的 原始数据
     * !! 对 $this->fromDb[] 执行格式转换，各字段值类型 == type["php"]
     * !! 如果此模型实例是手动创建的，则此处是 传入的手动创建的 模型实例数据
     */
    protected $origin = [];

    /**
     * 模型实例的 实际操作数据
     * !! 用于数据模型的实际业务操作，可被编辑修改，各字段值类型 == type["php"]
     * !! 在执行 insert|update|logic_delete 等方法之前，此项数据 会被格式转换为 db 类型，保存到 $this->toDb[] 中
     */
    protected $context = [];

    /**
     * 标记 此数据模型实例 是否为 新建的 记录
     * !! 通过传入 手动创建的数据 而创建的 模型实例，即 新建的记录
     * !! 标记为新建的 记录，在写入数据库之前，会自动去除 id 自增序号 字段
     */
    protected $isNew = false;

    /**
     * 如果不是新建的记录，而是通过 curd 查询得到的 记录实例
     * 缓存 curd 实例
     */
    protected $curd = null;

    /**
     * 如果存在 curd 实例，且 存在 join 关联表
     * 创建这些关联表的 记录实例
     */
    protected $joins = [
        /*
        "关联表名或别名" => Record 关联表记录实例,
        ...
        */
    ];

    /**
     * 如果当前记录实例 包含了 一个或多个 子表记录集
     * 缓存在这里
     */
    protected $subs = [
        /*
        "子表名或别名" => RecordSet 子表记录集实例
        */
    ];





    /**
     * 构造
     * 将 从数据库读取的 单条记录，创建为 Record 数据模型实例
     * !! 如果传入第二参数 $manual == true 则将 手动创建的记录数据 包裹为 Record 数据模型实例
     * @param Array $data 传入数据模型实例的 原始数据，类型 == type["db"]
     *                    !! 如果 第二参数 $manual == true 则传入的是手动创建的数据，类型 == type["php"]
     * @param Bool $manual 是否手动创建，默认 false，决定了 传入的 $data 中个字段值的 类型
     * @param Curd $curd 如果是通过 curd 查询得到的 记录实例，传入并缓存 curd 实例
     * @return void
     */
    final public function __construct($data, $manual=false, $curd=null)
    {
        if (!static::inited() || !Is::nemaso($data)) return null;

        if ($manual===true) {
            //手动创建
            $this->origin = $data;
            $this->context = Arr::copy($data);
            $this->isNew = true;
        } else {
            //从数据库读取数据，并创建实例
            $this->fromDb = $data;
            //conv
            $this->origin = static::conv("from", $data);
            $this->context = Arr::copy($this->origin);
            //!! 缓存传入的 curd
            if ($curd instanceof Curd) {
                $this->curd = $curd;
            }
            //构造 关联表记录实例
            $this->initJoinRecords();
        }
    }

    /**
     * 构造阶段，处理关联表记录
     * @return Record $this
     */
    protected function initJoinRecords()
    {
        if ($this->hasJoins()!==true) return $this;
        //关联表[]
        $jp = $this->curd->joinParser;
        //关联表表名或别名
        $jtbks = $jp->current;
        //一一对应的实际表名
        $jtbns = $jp->getRealTbns()->currentTbns;
        //从 fromDb 数据中分离 关联表数据，创建关联表记录实例
        foreach ($jtbks as $i => $jtbk) {
            $jtbn = $jtbns[$i];
            $jtbcls = static::$db->hasModel($jtbn);
            if ($jtbcls===false) continue;
            //关联表字段别名前缀
            $jcolpre = $jtbk."_";
            $jprelen = strlen($jcolpre);
            //关联表数据
            $jtbd = [];
            foreach ($this->fromDb as $jcolk => $colv) {
                //!! 关联表字段 一定有别名 table_colname 或 alias_colname
                if (substr($jcolk, 0, $jprelen)!==$jcolpre) continue;
                $colk = substr($jcolk, $jprelen);
                $jtbd[$colk] = $colv;
            }
            if (!Is::nemaso($jtbd)) continue;
            //创建 关联表记录实例，缓存到 $this->joins[] 中，键名是 关联表表名或别名
            $this->joins[$jtbk] = new $jtbcls($jtbd);
        }
        return $this;
    }



    /**
     * 写入 数据库
     * @param Bool $debug 标记为 true 则不会执行提交，仅返回 curd 参数 和 sql
     * @return Mixed 
     */
    final public function save($debug=false)
    {
        //!! 先检查一次 是否存在需要提交的数据
        if (!$this->needCommit()) return null;

        //curd 方法，也是默认值的 when 参数
        $when = $this->isNew ? "insert" : "update";
        //默认值填充 context
        $this->context = static::dftv($when, $this->context);

        //非 debug 情况下 执行 beforeXxxx 钩子
        if ($debug!==true) {
            $bm = "before".Str::camel($when, true);
            if (method_exists($this, $bm)) $this->$bm();
        }

        //diff 生成最终要写入数据库的 数据，自动转换格式
        $this->diff();
        $data = $this->toDb;

        //准备提交
        $curd = static::model()->curd;
        //debug 标记
        $curd = $curd->debug($debug);
        //提交
        if ($when==="insert") {
            $result = $curd->insert($data);
        } else {
            //update 需要 当前记录实例的 id 
            //!! Orm 模块中所有数据模型都会通过 prepare 预处理器自动添加 id 字段，且默认每次查询都会带上 id 字段
            $result = $curd->whereId($this->id)->update($data);
        }
        //debug 状态下，直接返回，不继续后续操作
        if ($debug===true) return $result;

        //非 debug 情况下 执行 afterXxxx 钩子
        $am = "after".Str::camel($when, true);
        if (method_exists($this, $am)) $this->$am();

        //非 debug 情况下 触发 model_insert|update 事件
        Event::trigger(
            //事件名称
            "model_".$when, 
            //triggerBy 触发者
            $this, 
            //额外参数
            //数据库名
            static::$db->name, 
            //表名
            static::$modk
        );

        //返回 结果
        return $result;
    }



    /**
     * 数据输出方法
     */

    /**
     * 获取当前记录实例的 字段值 
     * !! 所有获取字段值的 入口方法
     * !! 可以是 主表 真实字段|计算字段，也可以是 关联表 真实字段|计算字段
     * 
     * 参数用法:
     *      !! 字段名表名别名  都 同时支持 foo_bar 和 fooBar 形式
     *      读取主表字段，自动判断 真实字段 或 计算字段，获取其值
     *          $record->colv("col_name")           --> value
     *      读取关联表字段，自动判断 真实字段 或 计算字段，获取其值
     *          !! 如果此关联表 在本次查询中 有别名，则应使用别名作为前缀
     *          $record->colv("alias.col_name")     --> value
     *          !! 如果使用表名作为前缀，且此表有多个别名，则会得到：
     *          $record->colv("table.col_name")     --> [
     *              "alias1" => value,
     *              "alias2" => value,
     *              ...
     *          ]
     *      !! 在传入的 字段名 字符串之前增加 _ 前缀，可获取 fromDb[] 中的 来自数据库的 原始值（使用数据库对应类型）
     *          $record->colv("_colName")           --> $record->fromDb["col_name"]
     *          $record->colv("table._col_name")    --> $record->joins["table"]->fromDb["col_name"]
     *          !! 必须是 真实字段，计算字段没有原始值 返回 null
     *      
     * @param String|Array $key 可以是字段名，或 要输出的数据的格式 map[]
     * @return Mixed|null
     */
    final public function colv($key)
    {
        if (!Is::nemstr($key)) return null;
        $key = trim($key, ".");
        
        //针对主表字段
        if (strpos($key,".")===false) {
            //读取 计算字段值
            if (static::isGetterColumn($key)) return $this->getterColv($key);
            //读取 真实字段值
            if (substr($key, 0,1)==="_") {
                //!! 读取 fromDb 原始值
                $colk = Str::snake(substr($key, 1), "_");
                //数据源
                $srcs = $this->fromDb;
            } else {
                //正常 从 context 中读取
                $colk = Str::snake($key,"_");
                //数据源
                $srcs = $this->context;
            }
            //必须是真实字段
            if (static::isRealInDbColumn($colk)!==true) return null;
            //从 数据源 中读取
            return $srcs[$colk] ?? null;
        }

        //针对关联表字段
        $ka = explode(".", $key);
        if (count($ka)!==2) return null;
        //获取关联表 表名 或 别名 (如果传入的表名有多个别名，则会得到 别名数组)
        if (false===($jtbk = $this->isJoined(trim($ka[0])))) return null;

        //获取到单个 表名或别名
        if (Is::nemstr($jtbk)) {
            //关联记录实例
            $jrec = $this->joins[$jtbk];
            if (!$jrec instanceof Record) return null;
            //调用 关联记录的 colv 方法
            return $jrec->colv(trim($ka[1]));
        }

        //传入的表名前缀，有多个别名的
        if (Is::nemidx($jtbk)) {
            $rtn = [];
            foreach ($jtbk as $jtbki) {
                //关联记录实例
                $jrec = $this->joins[$jtbki];
                if (!$jrec instanceof Record) continue;
                //调用 关联记录的 colv 方法
                $rtn[$jtbki] = $jrec->colv(trim($ka[1]));
            }
            return empty($rtn) ? null : $rtn;
        }
        
        return null;
    }

    /**
     * 获取 当前记录实例的 所有字段值  包含 主表|关联表 所有字段
     * !! $tree == true 则 返回 多维数组结构：
     *  [
     *      !! 主表字段
     *      "col_a" => value,
     *      "col_b" => value,
     *      !! 包含 getter 计算字段
     *      "getter_a" => value,
     *      ...,
     *      
     *      !! 关联表记录实例数据
     *      "joins" => [
     *          !! 键名为 表名或别名，如果有别名一定是 别名
     *          "table" => [
     *              "col_c" => value,
     *              ...
     *          ],
     *          "alias" => [
     *              "col_d" => value,
     *              ...
     *          ],
     *          ...
     *      ],
     * 
     *      !! 如果存在 subs 子表记录集 (由 RecordSet->arrange() 方法生成的)
     *      "subs" => [
     *          !! 键名为 表名或别名，如果有别名一定是 别名
     *          "table" => [
     *              !! 一定是记录集 []
     *              [
     *                  "col_e" => value,
     *                  ...
     *              ],
     *              [ ... ],
     *              ...
     *          ],
     *          "alias" => [
     *              [
     *                  "col_f" => value,
     *                  ...
     *              ],
     *              [ ... ],
     *              ...
     *          ],
     *      ],
     *  ]
     * !! $tree == false 则 返回 一维结构：
     *  [
     *      "col_a" => value,
     *      "col_b" => value,
     *      "getter_a" => value,
     *      ...,
     * 
     *      !! 关联表字段，使用 table_col 形式作为 字段名
     *      "table_col_c" => value,
     *      "alias_col_d" => value,
     *      ...,
     * 
     *      !! 如果存在 subs
     *      "table_rs" => [
     *          [ ... ],
     *          ...
     *      ],
     *      "alias_rs" => [
     *          [ ... ],
     *          ...
     *      ],
     *  ]
     * @param Bool $tree 是否输出 多维数组 默认 false
     * @return Array 此记录实例的 完整数据
     */
    final public function entire($tree=false)
    {
        $rtn = [];
        //多维数组结构
        if ($tree) {
            $rtn["joins"] = [];
            //!! 如果存在 subs 子表
            if (Is::nemaso($this->subs)) $rtn["subs"] = [];
        }

        //主表的 所有字段数据 包含 计算字段
        static::eachColumn(function($colk, $colc) use (&$rtn) {
            $rtn[$colk] = $this->colv($colk);
        });

        //关联表记录实例的 entire 数据
        if ($this->hasJoins() && Is::nemaso($this->joins)) {
            foreach ($this->joins as $jtbk => $jrec) {
                //关联表的 entire 数据
                $jent = $jrec->entire($tree);
                if ($tree) {
                    $rtn["joins"][$jtbk] = $jent;
                } else {
                    foreach ($jent as $jk => $jv) {
                        //自动添加 表名别名前缀 到 关联表字段名
                        $rtn[$jtbk."_".$jk] = $jv;
                    }
                }
            }
        }

        //subs 子表
        if (Is::nemaso($this->subs)) {
            foreach ($this->subs as $stbk => $srs) {
                if (!$srs instanceof RecordSet) continue;
                //子表记录集 entire
                $sent = $srs->entire($tree);
                if ($tree) {
                    $rtn["subs"][$stbk] = $sent;
                } else {
                    $rtn[$stbk."_rs"] = $sent;
                }
            }
        }

        return $rtn;
    }

    /**
     * 以 自定义 map 的形式，输出记录实例数据
     * !! 如果未传入 有效的 map[] 则返回 $record->entire(true) 多维数组
     * map[] 的结构：
     *  [
     *      !! 定义要获取的值，必须写  可被 colv() 方法识别的 字符串   或   可以通过 __get 取值的 字符串
     *      !! 写的字符串，就是 返回值[] 中的 键名
     *      "alias.col_a",
     *      "joinRecords",
     *      "_ctx",
     * 
     *      !! 为 要获取的值 建立 别名
     *      "alias_col_a"   => "alias.col_a",
     *      "joins"         => "joinRecords",
     *      "ctx"           => "_ctx",
     * 
     *      !! 可以在任意深度的 [] 结构中 重复上述规则
     *      "foo" => [
     *          "bar" => [
     *              "alias_col_a"   => "alias.col_a",
     *              "joinRecords",
     *              "ctx"           => "_ctx",
     *          ],
     *      ],
     *  ]
     * 将返回数据：
     *  [
     *      "alias.col_a"   => $record->colv("alias.col_a"),
     *      "joinRecords"   => $record->joinRecords,
     *      "_ctx"          => $record->_ctx,
     * 
     *      "alias_col_a"   => $record->colv("alias.col_a"),
     *      "joins"         => $record->joinRecords,
     *      "ctx"           => $record->_ctx,
     *      
     *      "foo" => [
     *          "bar" => [
     *              "alias_col_a"   => $record->colv("alias.col_a"),
     *              "joinRecords"   => $record->joinRecords,
     *              "ctx"           => $record->_ctx,
     *          ]
     *      ]
     *  ]
     * @param Array $map 要输出的 数据结构
     * @return Array
     */
    final public function mapper($map=[])
    {
        if (!Is::nemarr($map)) return $this->entire(true);

        //准备输出
        $rtn = [];
        foreach ($map as $k => $v) {
            //如果是 下一级 map 结构，则递归
            if (Is::nemarr($v)) {
                $rtn[$k] = $this->mapper($v);
                continue;
            }

            //定义要获取的值的 规则 一定是 字符串
            if (!Is::nemstr($v)) continue;

            //根据规则 获取相应的值
            $colv = $this->colv($v);
            if (is_null($colv) && strpos($v,".")===false) {
                //colv() 未获取到值，则 尝试 __get 取值
                $colv = $this->$v;
            }

            //决定 返回值的 键名
            if (is_numeric($k)) {
                $rtn[$v] = $colv;
            } else {
                $rtn[$k] = $colv;
            }
        }

        return $rtn;
    }

    /**
     * 如果通过 RecordSet->arrange() 方法，将记录集中重复的记录 整理为 RecordSet 存入 subs[] ，则此处输出 子表完整记录数组
     * !! 通过调用子表的 entire 方法
     * @param String $stbk 子表名或别名，有别名一定使用别名，默认不指定，输出全部子表
     * @param Bool $tree 与 entire 方法的 $tree 参数一致，默认 false
     * @return Array|null 指定的 子表不存在 则返回 null
     */
    final public function subsEntire($stbk=null, $tree=false)
    {
        //默认不指定要输出的 子表，输出全部子表
        if (!Is::nemstr($stbk)) {
            $rtn = [];
            foreach ($this->subs as $tbk => $trs) {
                if (!$trs instanceof RecordSet) continue;
                $rtn[$tbk] = $this->subsEntire($tbk, $tree);
            }
            return $rtn;
        }

        //输出 某个子表
        $stbk = Str::snake($stbk, "_");
        if (!isset($this->subs[$stbk]) || !$this->subs[$stbk] instanceof RecordSet) return null;
        return $this->subs[$stbk]->entire($tree);
    }

    /**
     * 读取 getter 字段的值
     * !! 如果此计算字段依赖的真实字段值 在本次查询时未包含，则返回 null
     * @param String $colk 字段名 foo_bar 或 fooBar
     * @return Mixed|null 
     */
    protected function getterColv($colk)
    {
        if (!Is::nemstr($colk) || static::hasColumn($colk)===false || static::isGetterColumn($colk)===false) return null;
        $colk = Str::snake($colk,"_");

        //计算字段，判断 getter 类型，根据类型，调用对应的 getter 方法
        $gtp = static::columnGetterIs($colk);
        if (is_null($gtp)) return null;

        //当前模型类中定义的 getter
        if ($gtp===0) {
            $gc = static::columnConf($colk."/getter");
            $gcls = $gc["class"];
            $gm = $gc["method"];
            return $gc["isStatic"]!==true ? $this->$gm() : $gcls::$gm();
        }

        //特殊字段类型 Types 类中定义的 getter
        if ($gtp===1) {
            return static::callTypesGetter($colk, $this);
        }

        return null;
    }

    /**
     * 处理传入的 字段名 字符串，生成有效的 colv() 方法的 参数字符串
     * !! 用于 __get 方法中处理 $key 得到有效参数后 调用 colv() 获取字段值
     * 有这些写法：
     *      主表字段名
     *      $record->col_name               --> col_name
     *      $record->_col_name              --> _col_name
     *      $record->_colName               --> _colName
     * 
     *      !! 关联表字段名写法
     *      $record->table_col_name         --> table.col_name
     *      !! 自动判断 关联表名
     *      $record->alias_name_col_name    --> alias_name.col_name
     *      !! _ 前缀
     *      $record->_table_name_col_name   --> table_name._col_name
     *      !! fooBar 形式
     *      $record->tableNameColName       --> table_name.col_name
     *      $record->_tableNameColName      --> table_name._col_name
     *      
     * @param String $colk 字段名字符串
     * @return String|null 如果传入的不是有效的 主表 或 关联表 字段名，返回 null
     */
    protected function fixColk($colk)
    {
        if (!Is::nemstr($colk)) return null;

        //判断是否 带 _ 前缀
        $isdbv = false;
        if (substr($colk, 0,1)==="_") {
            $isdbv = true;
            $colk = trim($colk, "_");   //substr($colk, 1);
        }
        $pre = $isdbv ? "_" : "";

        //针对 主表字段
        if (static::hasColumn($colk)===true) {
            return $pre.$colk;
        }

        //针对 关联表字段
        //fooBar --> foo_bar
        $snk = Str::snake($colk, "_");
        //拆分
        $cola = explode("_", $snk);
        //查找 可能存在的 表名或别名
        $jtbn = null;
        $jcol = null;
        for ($i=1;$i<count($cola);$i++) {
            //从前向后 依次切片
            $ch = array_slice($cola, 0, $i);
            //判断是否 表名或别名
            $jtb = $this->isJoined(implode("_", $ch));
            if ($jtb===false) continue;
            //找到关联表表名或别名
            $jtbn = implode("_", $ch);
            $jcol = implode("_", array_slice($cola, $i));
            break;
        }
        //未找到有效的 表名或别名
        if (is_null($jtbn) || is_null($jcol)) return null;
        //返回
        return $jtbn.".".$pre.$jcol;
    }

    /**
     * __get
     */
    public function __get($key)
    {
        /**
         * 读取 主表字段值
         * !! 调用 colv 方法
         * $record->colk                    --> $record->colv("colk")
         * $record->colName                 --> $record->colv("colName")
         * $record->_colName                --> $record->colv("_colName")
         * !! 计算字段也包含在内
         * 
         * !! 关联表字段 也可以获取值
         * $record->_table_name_col_name    --> $record->colv("table_name._col_name")
         */
        $colk = $this->fixColk($key);
        if (!is_null($colk)) return $this->colv($colk);

        // $key --> foo_bar
        $snk = Str::snake($key,"_");

        /**
         * 返回关联表 记录实例
         * !! 关联表如果有别名，joins 中一定保存的是别名
         * $record->alias       --> $record->joins[alias] Record 实例
         * $record->tbn         --> $record->joins[tbn]  Record 实例
         */
        if (isset($this->joins[$snk]) && $this->joins[$snk] instanceof Record) {
            return $this->joins[$snk];
        }

        /**
         * 返回 子表记录集实例  必须 以  -Rs|-_rs 结尾
         * !! 子表如果有别名，subs 中一定保存的是别名
         * $record->aliasRs     --> $record->subs[alias] RecordSet 实例
         * $record->tbn_rs      --> $record->subs[tbn]  RecordSet 实例
         */
        if (substr($snk, -3)==="_rs") {
            $stbn = substr($snk, 0, -3);
            if (isset($this->subs[$stbn]) && $this->subs[$stbn] instanceof RecordSet) {
                return $this->subs[$stbn];
            }
        }

        /**
         * 返回 子表记录集 entire 数据  必须 以  -RsEntire|-_rs_entire 结尾
         * !! 子表如果有别名，subs 中一定保存的是别名
         * $record->aliasRsEntire   --> $record->subs[alias]->entire(false) 数组[]
         * $record->tbn_rs_entire   --> $record->subs[tbn]->entire(false)  数组[]
         */
        if (substr($snk, -10)==="_rs_entire") {
            $stbn = substr($snk, 0, -10);
            if (isset($this->subs[$stbn]) && $this->subs[$stbn] instanceof RecordSet) {
                return $this->subsEntire($stbn, false);
            }
        }

        //固定 $key
        switch ($key) {

            /**
             * 以数组方式 返回当前 记录实例中的数据
             * $record->ctx         --> $record->context
             * $record->old         --> $record->origin
             * $record->diff        --> $record->diff(false)    直接返回变化的 部分数据
             */
            case "ctx":             return $this->context;
            case "old":             return $this->origin;
            case "diff":            return $this->diff(false);

            /**
             * 以数组方式，返回当前记录实例数据的 db 类型数据
             * $record->_ctx        --> static::conv("to", $this->ctx)
             * $record->_old        --> $record->fromDb
             * $record->_diff       --> $record->diff()->toDb
             */
            case "_ctx":            return static::conv("to", $this->context);
            case "_old":            return $this->fromDb;
            case "_diff":           return $this->diff()->toDb;

            //返回所有 joins 中的 关联表记录实例
            case "joinRecords":     return $this->joins;

            //返回所有 subs 中的 子表记录集实例
            case "subRecordSets":   return $this->subs;

            /**
             * $record->isNewRecord --> $this->isNew
             * $record->fromCurd        --> 返回缓存的 curd 实例，如果是新建的记录，则是 null
             */
            case "isNewRecord":     return $this->isNew;
            case "fromCurd":        return $this->curd;

            default:                return null;
        }
    }



    /**
     * 数据修改方法
     */

    /**
     * __set
     */
    public function __set($key, $val)
    {
        /**
         * 修改字段值
         * $record->colk = $val 
         * !! 只能修改 原始字段，非 计算字段
         */
        if (static::hasColumn($key) && static::isRealInDbColumn($key)) {
            $colk = Str::snake($key, "_");
            //!! 若果指定新值为 null 则从 context[] 中 unset 此字段
            if (is_null($val)) {
                unset($this->context[$colk]);
                return;
            }
            //原始值  可能为 null
            $ov = $this->$colk;
            //调用此字段的 类型 Types 实例的 setter 方法
            $nv = static::columnTypesIns($colk)->setter($val, $ov); 
            //!! 使用 覆盖方式 修改 context 
            $this->context[$colk] = $nv;
            return;
        }

        /**
         * 以 extend 方式，修改 array 类型( json|datetime_queue 等类型 ) 字段数据
         * $record->extendFooBar = [...] 
         * $record->extend_foo_bar = [...]
         */
        $k = Str::snake($key, "_");
        if (substr($k, 0, 7)==="extend_" && is_array($val)) {
            $colk = trim(substr($k, 7), "_");
            if (
                static::hasColumn($colk) && static::isRealInDbColumn($colk) &&
                static::columnConf($colk."/type/php")==="Array"
            ) {
                //原始值
                $ov = $this->$colk;
                if (is_array($ov)) {
                    //!! 使用 extend 深度合并的方式 修改 context
                    //!! indexed 数组，将合并后去重
                    $this->context[$colk] = Arr::extend($ov, $val);
                }
            }
        }

        //return null;
    }



    /**
     * curd 方法
     */

    /**
     * 比较 $this->context 和 $this->origin 查找实际发生变化的字段
     * 针对 diff 字段，执行 conv 操作，转为 db 类型，保存到 $this->toDb[] 等待最终写入数据库
     * @param Bool $conv 是否自动转换为 db 类型，并保存到 toDb[]，默认 true
     * @return Record|Array $conv==true 自动转换，返回自身准备链式，否则，直接返回变化的部分数据
     */
    final protected function diff($conv=true)
    {
        //isNew 新建的记录
        $isNew = $this->isNew;
        //默认值 when
        $when = $isNew ? "insert" : "update";
        //创建默认值
        $dftv = static::dftv($when);

        //diff 字段，实际发生变化的字段
        $diff = [];
        if ($isNew) {
            //新建记录
            $diff = Arr::copy($this->context);
        } else {
            //更新记录
            $ctx = $this->context;
            $old = $this->origin;
            foreach ($ctx as $colk => $colv) {
                //原始记录不存在的
                if (!isset($old[$colk])) {
                    $diff[$colk] = $colv;
                    continue;
                }
                //与 原始记录 进行比较
                if (is_array($colv) ^ is_array($old[$colk])) {
                    //新旧值 一个是数组而另一个不是，则一定不同
                    $diff[$colk] = $colv;
                } else if (is_array($colv)) {
                    //新旧值都是数组，使用 equal 方法比较
                    if (Arr::equal($colv, $old[$colk])!==true) {
                        $diff[$colk] = $colv;
                    }
                } else {
                    //新旧值都不是数组，直接比较
                    if ($colv !== $old[$colk]) {
                        $diff[$colk] = $colv;
                        continue;
                    }
                }
            }
            foreach ($old as $colk => $colov) {
                //原始记录存在，但是修改后不存在的字段，使用 默认值填充
                if (!isset($ctx[$colk])) {
                    //!! 如果默认值也不含此字段，使用 null 
                    $diff[$colk] = $dftv[$colk] ?? null;
                }
            }
        }

        //去除可能存在的 不是原始字段的 字段值
        $diff = static::realInDb($diff);

        if ($conv===false) return $diff;

        //先清空 toDb
        $this->toDb = [];

        //只有 有字段发生变化的 才执行 conv
        if (Is::nemaso($diff)) {
            //isNew 新建记录时，去除 id 字段
            //!! Orm 支持的所有数据模型都必须含有 id 字段
            $id = $this->conf("special/id")[0];
            if (isset($diff[$id])) unset($diff["id"]);
            //转换数据类型
            $this->toDb = static::conv("to", $diff);
        }

        //返回自身，准备链式
        return $this;
    }


    /**
     * before|after 钩子方法
     * !! 如果需要，模型子类自行覆盖
     */
    protected function beforeInsert() { }
    protected function afterInsert() { }
    protected function beforeUpdate() { }
    protected function afterUpdate() { }
    protected function beforeLogicDelete() { }
    protected function afterLogicDelete() { }



    /**
     * 工具方法
     */

    /**
     * 获取此数据模型的 参数
     * @param String $key 在参数中检索某些项目 xpath  foo/bar
     * @return Array
     */
    final public function conf($key=null)
    {
        $key = Is::nemstr($key) ? "/".$key : "";
        return static::$db->config->ctx("model/".static::$modk.$key);
    }

    /**
     * 判断当前是否需要 提交数据，即 diff() 后存在需要提交的数据
     * !! 模型子类可以覆盖这个方法
     * @return Bool
     */
    final public function needCommit()
    {
        return Is::nemaso($this->diff(false));
    }

    /**
     * 判断当前 记录实例包含的 curd 操作是否开启了 join 关联查询
     * @return Bool
     */
    final public function hasJoins()
    {
        return $this->curd instanceof Curd && $this->curd->joinParser->needJoin()===true;
    }

    /**
     * 判断传入的 关联表名 是否在本次查询中 被 join
     * @param String $jtbk 可能是 表名 或 别名
     * @return String|Array|false 如果被 join 则返回 表名或别名，有别名的一定返回别名，未被 join 则返回 false
     *                            !! 有过传入的表名有多个别名，则返回 别名数组
     */
    final public function isJoined($jtbk)
    {
        if (!Is::nemstr($jtbk)) return false;
        //!! 当前记录实例 必须包含 curd 实例，且 $curd->joinParser->needJoin() 必须是 true
        if ($this->hasJoins()!==true) return false;
        $jtbk = Str::snake($jtbk,"_");

        //curd->joinParser
        $jp = $this->curd->joinParser;
        //关联表表名或别名
        $jtbks = $jp->current;
        //一一对应的实际表名
        $jtbns = $jp->getRealTbns()->currentTbns;

        if (in_array($jtbk, $jtbks)) return $jtbk;
        
        //!! 此情况是：关联表有别名，但传入的 $jtbk 是 表名，返回 别名 (如果有多个别名，则返回 别名数组)
        if (in_array($jtbk, $jtbns)) {
            $alis = [];
            foreach ($jtbns as $i => $jtbn) {
                if ($jtbn===$jtbk) {
                    $alis[] = $jtbks[$i];
                }
            }
            return count($alis)===1 ? $alis[0] : $alis;
        }

        return false;
    }

    /**
     * 根据传入的 表名 或 表名(别名)  以及  记录数据（db 格式 或 php 格式，根据 $manual 参数决定） 或  记录实例
     * 向当前记录实例的 joins 中插入 关联记录实例
     * !! 仅在 $this->hasJoins()===true 的条件下才会执行
     * !! 如果 已存在 表名或别名，则会覆盖
     * @param String $jtbk 可以是 表名 或 表名(别名) 如果有别名 一定要写别名
     * @param Array $data 要插入的 关联记录的数据  如果 $manual == true 则传入的是 php 格式，否则是 db 格式
     *                    !! 还可以直接传入 记录实例
     * @param Bool $manual 默认 false
     * @return Record $this
     */
    final public function setJoins($jtbk, $data=[], $manual=false)
    {
        if (!Is::nemstr($jtbk) || !$this->hasJoin()) return $this;
        $jtbk = trim($jtbk);

        //解析 表名(别名)
        $mt = preg_match("/^([a-zA-Z0-9_]+)\s?(\(\s?[a-zA-Z0-9_]+\s?\))?$/", $jtbk, $matches);
        if ($mt!==1) return $this;
        $ma = array_slice($matches, 1);
        $jtbn = $ma[0];
        $jtbk = $ma[1]==="" ? "" : trim(substr($ma[1],1,-1));
        $jtbk = $jtbk==="" ? $jtbn : $jtbk;
        //对应的 模型类全称
        if (false===($jtbcls = $this->db->hasModel($jtbn))) return $this;

        //!! 如果传入的 $data 是 记录实例
        if ($data instanceof Record) {
            //保存到 joins
            $this->joins[$jtbk] = $data->pureRecord(false);
            return $this;
        }

        //创建 关联记录实例
        if (Is::nemaso($data)) $this->joins[$jtbk] = new $jtbcls($data, $manual);
        return $this;
    }

    /**
     * 根据传入的  表名 或 别名  以及  记录集实例 RecordSet
     * 向当前记录实例的 subs 中插入 子表记录集实例
     * !! 如果 已存在 表名或别名，则会覆盖
     * @param String $stbk 可以是 表名 或 别名  如果有别名 一定要写别名
     * @param Array $rs 要插入的 关联记录集实例 RecordSet
     * @return Record $this
     */
    final public function setSubs($stbk, $rs=null)
    {
        if (!Is::nemstr($stbk) || !$rs instanceof RecordSet) return $this;
        //将 子表记录集中的所有记录实例，全部转为 pureRecord ，然后 存入 subs[] 
        $this->subs[$stbk] = $rs->pureRecord(false);
        return $this;
    }

    /**
     * 将当前记录实例 中的 curd|joins|subs 等数据清空，返回新的 纯粹的 记录实例
     * @param Bool $withCurd 可以选择是否保留 curd 实例上下文，默认 true
     * @return Record 新的 记录实例，不含任何 关联表数据
     */
    final public function pureRecord($withCurd=true)
    {
        //将当前 context 转为 db 数据类型
        $toDb = $this->_ctx;    //static::conv("to", $this->context);
        //新建 Record 实例
        return new static($toDb, false, $withCurd ? $this->curd : null);
    }
    
}