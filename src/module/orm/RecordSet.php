<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型(表) 记录集 类
 * curd 操作得到的 recordset 被包裹为 此类型
 */

namespace Spf\module\orm;

use Spf\module\orm\Db;
use Spf\module\orm\Model;
use Spf\module\orm\Record;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Cls;
use Spf\util\Path;

use Spf\traits\arrayIterator;

class RecordSet implements \ArrayAccess, \IteratorAggregate, \Countable
{
    //引入trait
    //可 for 循环此类，可用 $rs[idx] 访问 $context 数组，增加 each 方法
    use arrayIterator;


    //关联的数据库实例 Db
    public $db = null;

    //关联的 模型(数据表) 类全称
    public $model = "";

    //缓存 curd 实例
    public $curd = null;

    //是否手动创建的 记录集
    public $isNew = false;

    /**
     * 数据模型(表) 实例 数组
     */
    public $context = [
        //model instance, ...
    ];

    /**
     * 构造
     * @param String $model 数据表(模型) 类全称
     * @param Array $rs 手动传入的 或 由 $curd->select() 查询得到的结果 array
     *                  !! 也可以通过手动传入  [ Record instance, ... ] 创建记录集
     * @param Bool $manual 是否手动创建记录集，默认 false 
     *                  !! 如果是手动传入 [ Record instance, ... ] 则根据 $record->isNewRecord 决定是否 manual
     * @param Curd $curd 如果 $manual == false 则 传入本次 curd 的实例，就是由这个 curd 实例执行查询并得到 $rs 数据的
     *                  !! 如果是手动传入 [ Record instance, ... ] 则 = $record[0]->curd 
     * @return void
     */
    public function __construct($model, $rs=[],  $manual=false, $curd=null)
    {
        if (!class_exists($model)) return null;
        $this->model = $model;
        $this->db = $model::$db;
        if ($manual) $this->isNew = true;
        if ($curd instanceof Curd) $this->curd = $curd;

        if (!Is::nemarr($rs)) {
            //未传入有效数据
            $this->context = [];
        } else if (Is::nemidx($rs)) {
            //传入了 indexed 数组
            if ($rs[0] instanceof $this->model) {
                //!! 手动传入 [ Record instance, ... ]
                $this->isNew = $rs[0]->isNewRecord===true;
                if ($rs[0]->curd instanceof Curd) $this->curd = $rs[0]->curd;
                $this->context = $rs;
            } else {
                //手动传入 或 $curd->select() 得到 记录及数据
                $this->context = array_map(function($rsi) {
                    return new $this->model($rsi, $this->isNew, $this->curd);
                }, $rs);
            }
        } else if (Is::nemaso($rs)) {
            //!! 还可能传入 单条记录数据
            $this->context = [];
            $this->context[] = new $this->model($rs, $this->isNew, $this->curd);
        } else if ($rs instanceof $this->model) {
            //!! 还可能传入 单条记录实例
            $this->isNew = $rs->isNewRecord===true;
            if ($rs->curd instanceof Curd) $this->curd = $rs->curd;
            $this->context = [$rs];
        }
    }

    /**
     * __call
     * @param String $key 方法
     * @param Array $args 参数
     * @return Array [ 调用结果, ... ]
     */
    public function __call($key, $args)
    {
        if (!empty($this->context) && $this->context[0] instanceof $this->model) {
            $msi = $this->context[0];

            /**
             * $recordset->modelInstanceMethod()
             * $recordset->getter()
             * 调用 Record 实例方法 / __call 方法，返回 结果数组
             */
            $tst = $msi->$key(...$args);
            if (method_exists($msi, $key) || !empty($tst)) {
                $rst = $this->map(function($i) use ($key, $args) {
                    return $i->$key(...$args);
                });
                if (empty($rst)) return [];
                if ($rst[0] instanceof $this->model) {
                    //如果每个 Record 实例 执行结果返回 Record 实例 本身
                    array_splice($this->context, 0);
                    $this->context = $rst;
                    //返回 RecordSet 实例本身
                    return $this;
                } else {
                    return $rst;
                }
            }

            /**
             * $recordset->slice(0,2)
             * 调用 array_*** 方法，对 recordset->context 执行数组操作
             * 返回处理后的 recordset 实例
             */
            $arr_funcs = [
                "shift","unshift",
                "pop","push",
                "splice","slice"
            ];
            if (in_array($key, $arr_funcs)) {
                $af = "array_".$key;
                if (!function_exists($af)) return $this;
                if (in_array($key, ["shift", "pop"])) {
                    //shift, pop 操作 context 返回移出的 Record 实例
                    return $af($this->context);
                } else if (in_array($key, ["unshift","push"])) {
                    //unshift, push 向 context 增加 Record 实例
                    if (!empty($args)) {
                        $ags = [];
                        for ($i=0;$i<count($args);$i++) {
                            if ($args[$i] instanceof $this->model) {
                                //增加到 $recordset->context 数组的 必须是 Record 实例
                                $ags[] = $args[$i];
                            }
                        }
                        $af($this->context, ...$ags);
                    }
                    return $this;
                } else if ($key=="slice") {
                    //slice 从 context 中复制部分 Record 实例，返回新 recordset 实例
                    $ctx = array_slice($this->context, ...$args);
                    return new RecordSet($this->model, $ctx, $this->isNew, $this->curd);
                } else if ($key=="splice") {
                    //slice 从 context 中分割部分 Record 实例，插入新 Record 实例
                    $ctx = array_splice($this->context, ...$args);
                    //返回移除的 Record 实例 组成的 recordset
                    return new RecordSet($this->model, $ctx, $this->isNew, $this->curd);
                    //return $this;
                }
            }

        }

        return $this;
    }

    /**
     * __get
     * @param String $key
     * @return Mixed
     */
    public function __get($key) 
    {
        //如果是 空集合，直接返回 []
        if ($this->isEmpty()===true) return [];

        if (!empty($this->context) && $this->context[0] instanceof $this->model) {

            /**
             * $recordSet->colk         --> [colv, colv, ...] 
             * $recordSet->_colk        --> [col_dbv, col_dbv, ...]
             * #recordSet->ctx          --> [ [ctx], [ctx], ... ]
             */
            $rtn = [];
            $this->each(function($record, $i) use ($key, &$rtn) {
                //直接尝试 $record->$key
                $rst = $record->$key;
                if (is_null($rst)) return true;
                $rtn[] = $rst;
                return true;
            });
            
            return $rtn;
        }
        
        return [];
    }

    /**
     * __set 设置全部 context record 对象的属性
     * $recordSet->colk = sth   --> each(){ $record->colk = sth }
     */
    public function __set($key, $value)
    {
        //直接调用 Record 实例的 __set
        if (!empty($this->context)) {
            $this->each(function($record, $i) use ($key, $value) {
                $record->$key = $value;
                return true;
            });
        }
    }

    /**
     * 是否空记录集
     */
    public function isEmpty()
    {
        return empty($this->context);
    }

    /**
     * 在 recordset 中筛选
     * @param \Closure $callback 筛选方法，返回 true or false
     * @return RecordSet 返回新 recordset 实例
     */
    public function filter($callback)
    {
        if (!$callback instanceof \Closure) return $this;

        $rs = [];
        $this->each(function($record, $i) use ($callback, &$rs) {
            //bindTo $record
            $cb = $callback->bindTo($record, $this->model);
            //执行 filter
            if ($cb()===true) {
                $rs[] = $record;
            }
            return true;
        });
        
        //使用筛选结果 创建新 RecordSet
        return new RecordSet($this->model, $rs, $this->isNew, $this->curd);
    }

    /**
     * !! 将 主表 和 关联表 数据，整理为 多维结构
     * 例如：main 表 LEFT JOIN tbn 表(别名 ali) main.pk_a = ali.pk_b  查询得到记录集：
     *      $recordSet->context = [
     *          $record[0]->context = [ pk_a => 1 ]     $record[0]->joins["ali"] = Record([ pk_b => 11 ]),
     *          $record[1]->context = [ pk_a => 2 ]     $record[1]->joins["ali"] = Record([ pk_b => 11 ]),
     *          $record[2]->context = [ pk_a => 3 ]     $record[2]->joins["ali"] = Record([ pk_b => 11 ]),
     *          $record[3]->context = [ pk_a => 4 ]     $record[3]->joins["ali"] = Record([ pk_b => 22 ]),
     *          $record[4]->context = [ pk_a => 5 ]     $record[4]->joins["ali"] = Record([ pk_b => 22 ]),
     *          $record[5]->context = [ pk_a => 6 ]     $record[5]->joins["ali"] = Record([ pk_b => 22 ])
     *      ]
     * 执行 $recordSet->arrange("ali.pk_b") 整理后得到：
     *      $newRecordSet->context = [
     *          $record[0]->context = [ pk_b => 11 ]    $record[0]->subs["main"] = RecordSet( [
     *                                                      [ pk_a => 1 ],
     *                                                      [ pk_a => 2 ],
     *                                                      [ pk_a => 3 ],
     *                                                  ] ),
     *          $record[1]->context = [ pk_b => 22 ]    $record[1]->subs["main"] = RecordSet( [
     *                                                      [ pk_a => 4 ],
     *                                                      [ pk_a => 5 ],
     *                                                      [ pk_a => 6 ],
     *                                                  ] ),
     *      ]
     * @param String $colk 在当前记录集中，存在重复值的 字段名，可以是主表字段，也可以是 关联表字段
     *                     !! 传入 col_a 主表字段，则 整理后得到的新记录集的主表 就是 当前主表
     *                     !! 传入 table.col_a 或 alias.col_a 关联表字段，则 整理后得到的新记录集的主表 就是 此关联表
     *                     !! 关联表如果有别名，必须使用别名
     * @param String $stbk 在 整理后得到的新记录集中，作为 subs 子表的 表名 或 别名
     *                     !! 如果 $colk 是主表字段，
     *                     !!     则 $stbk 应该是 主表记录实例的 joins[] 中的某个关联表，如果不指定使用第一个
     *                     !! 如果 $colk 是关联表字段，
     *                     !!     则 $stbk 默认是当前记录集的主表，也可以手动指定 当前主表记录实例的 joins[] 中的某个关联表
     *                     !! 如果要作为子表的 表有别名，必须使用别名
     * @return RecordSet|null 返回整理后的 新 记录集实例，条件错误返回 null
     */
    public function arrange($colk, $stbk=null)
    {
        //!! 在这些条件下，此方法不会执行
        if (
            //当前 记录集是手动创建的 还未写入数据库的
            $this->isNew===true || 
            //当前 未关联任何 curd 实例
            !$this->curd instanceof Curd || 
            //当前关联的 curd 操作，未启用 join 关联表查询
            $this->curd->joinParser->needJoin()!==true
        ) {
            return null;
        }

        //!! 必须有记录，必须传入 存在重复值的 字段名
        if (!Is::nemarr($this->context) || !Is::nemstr($colk)) return null;

        //处理传入的 字段名
        $colk = trim($colk, ".");
        //获取 新的 主表名  和  有重复值的字段名
        if (strpos($colk, ".")===false) {
            $main = "self";
            $pk = Str::snake($colk, "_");
        } else {
            $cola = explode(".", $colk);
            $main = Str::snake($cola[0], "_");
            $pk = Str::snake($cola[1], "_");
        }

        //子表名
        if (Is::nemstr($stbk)) $stbk = Str::snake($stbk, "_");
        //当前主表名
        $modk = $this->model::$modk;

        //整理后主表 类全称
        $rsi = $this->context[0];
        if ($main==="self") {
            $maincls = $this->model;
            if (!Is::nemstr($stbk)) {
                //如果未指定 子表名，则从 joins[] 中获取第一个 关联表
                $stbk = array_merge([], array_keys($rsi->joinRecords))[0];
            } else {
                //如果指定了 子表名，则必须在 joins[] 中
                if (!isset($rsi[0]->joinRecords[$stbk])) return null;
            }
        } else {
            $jtb = $rsi->$main;
            if (!$jtb instanceof Record) return null;
            $maincls = $jtb->conf("class");
            if (!Is::nemstr($stbk)) {
                //如果未指定 子表名，则使用当前主表 作为 子表
                $stbk = $modk;
            } else {
                //如果指定了 子表名，则必须在 joins[] 中
                if (!isset($rsi[0]->joinRecords[$stbk])) return null;
            }
        }
        
        //pk 字段
        if ($maincls::hasColumn($pk)!==true) return null;
        //根据对应的 pk 字段值，筛选得到 Record 实例[]，将会去重
        //!! 新 RecordSet 将会在此 [] 基础上创建
        $rsos = [];
        $pkvs = [];     //已经存在的 pk 字段值
        $this->each(function($record) use ($main, $pk, &$rsos, &$pkvs) {
            if ($main==="self") {
                $pkv = $record->$pk;
                if (!in_array($pkv, $pkvs)) {
                    $pkvs[] = $pkv;
                    $rsos[] = $record->pureRecord(false);
                    return true;
                }
            } else {
                $pkv = $record->$main->$pk;
                if (!in_array($pkv, $pkvs)) {
                    $pkvs[] = $pkv;
                    $rsos[] = $record->$main->pureRecord(false);
                    return true;
                }
            }
            return true;
        });
        //!! 如果 筛选得到的 Record 实例[] 为空，直接返回 空内容的 记录集
        if (!Is::nemidx($rsos)) return new RecordSet($maincls, []);
        
        //根据 pk 字段值，从 当前记录集中筛选 关联表，并返回新记录集
        foreach ($rsos as $rso) {
            $pkv = $rso->$pk;
            //关联表 记录实例 []
            $jrsos = [];
            if ($main==="self") {
                //新记录集主表是当前主表，则收集 当前主表记录实例的 joins 中的 关联表记录实例
                $this->each(function($record) use ($pk, $pkv, $stbk, &$jrsos) {
                    if ($record->$pk!==$pkv) return true;
                    //收集 当前主表 joins[$stbk] 的记录实例
                    if (!isset($jrsos[$stbk])) $jrsos[$stbk] = [];
                    $jrsos[$stbk][] = $record->$stbk;
                    return true;
                });
            } else {
                //新记录集主表是当前记录实例的 关联表，则收集 当前主表的记录实例 或 指定的 关联表记录实例
                $this->each(function($record) use ($main, $pk, $pkv, $stbk, $modk, &$jrsos) {
                    if ($record->$main->$pk!==$pkv) return true;
                    //收集 当前主表的记录实例  或  指定的 关联表记录实例
                    if (!isset($jrsos[$stbk])) $jrsos[$stbk] = [];
                    if ($stbk===$modk) {
                        //指定的 子表 是 当前主表
                        $jrsos[$stbk][] = $record;
                    } else {
                        //指定的 子表 是 某个 关联表
                        $jrsos[$stbk][] = $record->$stbk;
                    }
                    return true;
                });
            }

            //根据收集到的 子表记录实例[] 创建子表记录集 保存到 $rso->subs[]
            foreach ($jrsos as $jtbk => $jrs) {
                if (!Is::nemidx($jrs)) continue;
                $subcls = $jrs[0]->conf("class");
                $rso->setSubs($jtbk, new RecordSet($subcls, $jrs));
            }
        }

        //根据 $rsos 创建记录集
        return new RecordSet($maincls, $rsos);

    }



    /**
     * 静态方法
     */

    /**
     * 将查询得到的原始格式数据（未包装为 RecordSet 实例）整理为 二维数组结构
     * 例如：通过 right join 查询得到：
     *      [
     *          [
     *              "id" => 1,
     *              "name" => "...",
     *              "关联表A_id" => 10,
     *              "关联表B_id" => 20,
     *              ...
     *          ],
     *          [
     *              "id" => 1,
     *              "name" => "...",
     *              "关联表A_id" => 11,
     *              "关联表B_id" => 21,
     *              ...
     *          ],
     *          [
     *              "id" => 1,
     *              "name" => "...",
     *              "关联表A_id" => 11,
     *              "关联表B_id" => 22,
     *              ...
     *          ],[
     *              "id" => 1,
     *              "name" => "...",
     *              "关联表A_id" => 12,
     *              "关联表B_id" => 23,
     *              ...
     *          ],
     *          [
     *              "id" => 1,
     *              "name" => "...",
     *              "关联表A_id" => 12,
     *              "关联表B_id" => 24,
     *              ...
     *          ],
     *          ...
     *      ]
     * 通过此方法整理为：
     *      [
     *          [
     *              "id" => 1,
     *              "name" => "...",
     *              "关联表A" => [
     *                  [ "id"=>11 ],
     *                  [ "id"=>12 ],
     *              ],
     *              "关联表B" => [
     *                  [ "id"=>20 ],
     *                  [ "id"=>21 ],
     *                  [ "id"=>22 ],
     *                  [ "id"=>23 ],
     *                  [ "id"=>24 ],
     *              ]
     *          ]
     *      ]
     * @param Array $rs 查询得到的原始数据格式
     * @param String $pk 主表的唯一字段
     * @param String[] $jtbs 要整理的 关联表名.关联表pk   可以有多个
     * @return Array 整理后得到的 二维数组
     */
    public static function to2d($rs, $pk, ...$jtbs)
    {
        if (!Is::nemidx($rs)) return [];

        //要收集的 关联表 以及 关联表pk
        $jtb = [];
        foreach ($jtbs as $jtbi) {
            if (!Is::nemstr($jtbi) || strpos($jtbi,".")===false) continue;
            $ja = explode(".", $jtbi);
            $jtb[$ja[0]] = $ja[1];
        }
        //关联表名数组
        $jtbns = array_keys($jtb);

        //处理 $rs[] 记录，将其中的 关联表数据，归入关联表表名下
        $rs = array_map(function($rsi) use ($jtbns) {
            foreach ($jtbns as $jtbn) {
                $rsi = Arr::collectPrefix($rsi, $jtbn);
            }
            return $rsi;
        }, $rs);
        //将各关联表数据，统一收集到 $jrs[$jtbn] 下，顺序与 $rs 一致
        $jrs = [];
        foreach ($jtbns as $jtbn) {
            $jrs[$jtbn] = [];
            foreach ($rs as $i => $rsi) {
                if (isset($rsi[$jtbn])) {
                    $jrs[$jtbn][] = $rsi[$jtbn];
                    unset($rs[$i][$jtbn]);
                }
            }
        }

        //主表记录集数据
        $mainrs = [];
        //已出现过的主表 pk 值
        $mainpk = [];
        foreach ($rs as $i => $rsi) {
            if (!Is::nemaso($rsi) || !isset($rsi[$pk])) continue;
            //当前记录 $rsi 中的 pk 值 在 $mainpk 中的序号
            $idx = array_search($rsi[$pk], $mainpk);
            //还未收集过此 pk 值
            if ($idx===false) {
                $mainpk[] = $rsi[$pk];
                $mainrs[] = $rsi;
                $idx = count($mainpk)-1;
            }
            //收集关联表数据
            foreach ($jtbns as $jtbn) {
                if (!isset($mainrs[$idx][$jtbn])) $mainrs[$idx][$jtbn] = [];
                $mainrs[$idx][$jtbn][] = $jrs[$jtbn][$i];
            }
        }

        //依次将收集到的 各 $mainrs[] 中的 关联表记录，按关联表 pk 值，进行去重
        foreach ($mainrs as $i => $rsi) {
            foreach ($jtbns as $jtbn) {
                $mainrs[$i][$jtbn] = Arr::uniqueByKey($rsi[$jtbn], $jtb[$jtbn]);
            }
        }

        return $mainrs;
    }
}