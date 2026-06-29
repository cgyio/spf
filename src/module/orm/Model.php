<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型(表) 类 基类
 * 定义 数据模型(表)方法，记录实例方法从 Record 类继承
 * 
 * 数据模型(表)级操作，使用 静态方法：
 *      Model::find()
 *      Model::get()
 *      $db->Usr->where()->select()
 * 
 * 数据模型(表)记录级操作，使用 实例方法：
 *      $usr->uid 
 *      $usr->save()
 * 
 */

namespace Spf\module\orm;

use Spf\module\orm\Record;
use Spf\module\Orm;
use Spf\module\orm\Db;
use Spf\module\orm\Types;
use Spf\module\orm\OrmException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Model extends Record 
{
    /**
     * 数据模型(表) 的额外配置参数
     * 这些参数将在 数据库配置解析阶段，在 调用 Preparer 预处理之前 合并到 此数据模型的待解析配置数据中
     * !! 具体的 数据模型(表) 子类必须覆盖
     * !! 否则这些静态参数 会在多个 数据模型(表) 类中相互影响
     */
    public static $customConf = [
        //!! 参数结构 与 module/orm/config/DbConfig::$stdModel[] 一致
        //!! 可以使用 %{...}% 模板字符，可用的模板字符 在 module\orm\ModuleOrmConfig::processConf() 方法中查看
    ];

    /**
     * 此数据模型(表) 的 配置参数
     * !! 具体的 数据模型(表) 子类必须覆盖这些 静态参数
     * !! 否则这些静态参数 会在多个 数据模型(表) 类中相互影响
     */
    //数据模型(表) 所属 数据库实例  访问：NS\model\Foo::$db
    public static $db = null;
    //数据模型(表) 名称 foo_bar  访问：NS\model\Foo::$modk
    public static $modk = "";
    //数据模型(表) 类名 FooBar  访问：NS\model\Foo::$modn
    public static $modn = "";
    //数据模型(表) 配置参数 (object)[]  访问：NS\model\Foo::$config
    public static $config = null;
    //标记 此数据模型(表) 配置参数已经被解析并初始化
    public static $isConfed = false;

    /**
     * 在 所有 module\orm\config\model\Parser 解析器类处理完成后，最后执行一次 模型参数的 手动处理
     * !! 此方法返回的 模型参数 就是最终的 模型参数，会被缓存
     * !! 如果有 Parser 解析器未能处理的逻辑，可直接写到每个模型类的此方法里
     * !! 此方法中还可以处理一些 其他 数据模型的 初始化动作
     * !! 此方法在 DbConfig::parseModelConf() 方法中被最后调用
     * @param Array $conf 所有 Parser 解析器处理完后得到的 模型参数，
     * @return Array 此处执行手动处理后，返回最终的模型参数，会被写入 $db->config->context["model"][$modk][] 中，然后被缓存
     */
    public static function finalParse($conf=[])
    {
        //已经初始化过，直接返回
        if (static::$isConfed!==false) return $conf;

        //!! 模型子类实现具体逻辑
        //!! 如果不需要，就不要修改

        return $conf;
    }

    /**
     * 判断此数据模型是否已经过初始化，关联到某个 Db 数据库实例
     * @return Bool
     */
    final public static function inited()
    {
        return static::$db instanceof Db && !empty(static::$config);
    }



    /**
     * 数据模型 CURD 操作
     */

    /**
     * 在数据模型类 内部初始化 curd 准备链式调用
     * static::model()->where()...  相当于 static::$db->[static::$modn]->where()...
     * !! 子类不要覆盖
     * @param String $otherModk 可以指向另一个 兄弟模型
     * @return Db 已经过 curd 初始化的 Db 实例
     */
    final public static function model($modk=null)
    {
        $db = static::$db;
        $mod = static::$modk;
        if (Is::nemstr($modk) && ($nmodcls = $db->hasModel($modk))!==false) {
            $mod = $nmodcls::$modk;
        }
        return $db->$mod;
    }

    /**
     * 手动创建 数据模型实例
     * !! 通常用于 新建记录，生成默认值，并将其包裹为数据模型实例
     * !! 从数据库查询后，将数据记录包裹为模型实例的操作 会自动执行，不需要调用此方法
     * !! 如果使用 从数据库查询得到的原始记录 创建 Record 记录实例，应指定额外参数 $manual $curd
     * @param Array $data 覆盖默认值的 数据
     * @param Bool $manual 默认 true   如果是使用 从数据库查询得到的原始记录 创建 Record 记录实例，此参数应设为 false
     * @param Curd $curd 如果是使用 从数据库查询得到的原始记录 创建 Record 记录实例，可以传入此次 curd 操作的实例
     * @return Record|null 当前数据模型的 实例
     */
    final public static function record($data=[], $manual=true, $curd=null)
    {
        if ($manual===true) {
            //手动创建 新建记录
            //填充默认值
            $manv = static::dftv("insert", $data);
            if (is_null($manv)) return null;
            //创建 模型实例 第二参数标记 这是手动创建
            return new static($manv, $manual);
        } else {
            //使用 从数据库查询得到的原始记录 创建 Record 记录实例
            return new static($data, $manual, $curd);
        }
    }

    /**
     * 根据模型的 unique 字段(字段有唯一索引) 的值，获取单条记录
     * !! 如果此模型有多个 unique 字段，且未指定用哪一个，默认使用第一个
     * !! 如果此模型没有 unique 字段，则默认使用 id 字段(SPF-Orm 模块支持的模型都必须包含id字段)
     * !! 如果有需要，模型子类可以覆盖此方法，实现自定义获取方法，必须返回一条记录实例
     * @param Mixed $val 字段值
     * @param String $colk 可以手动指定字段名，必须是 unique 字段 或 id，默认 null 不指定
     * @return Record|null 获取到记录则返回 记录实例，否则返回 null
     */
    public static function find($val, $colk=null)
    {
        //模型配置参数
        $conf = static::$db->config->ctx(static::cpath());
        //unique 字段列表
        $unis = $conf["special"]["unique"];
        if (!Is::nemidx($unis)) $unis = [];
        if (Is::nemstr($colk)) {
            $colk = Str::snake($colk,"_");
            //指定了要查询的 unique 字段，但无效 也不是 id，直接返回 null
            if ($colk!=="id" && !in_array($colk, $unis)) return null;
        } else {
            //未指定要查询的 unique 字段
            if (!Is::nemidx($unis)) {
                //模型没有 unique 字段，则使用 id 字段
                $colk = "id";
            } else {
                //默认使用第一个 unique 字段
                $colk = $unis[0];
            }
        }
        //调用字段的类型实例，处理 $val 转换为 db 类型
        $tpins = static::columnTypesIns($colk);
        if (!$tpins instanceof Types) return null;
        //调用类型实例的 to 方法
        $val = $tpins->to($val);
        unset($tpins);
        //字段值类型转换失败
        if (is_null($val)) return null;

        //开始执行 CURD
        return static::model()
            ->nojoin()
            ->column($colk)         //将要查询的字段添加到查询字段列表中
            ->where($colk, $val)
            ->get();
    }

    /**
     * 快速搜索记录，例如：
     *      $db->Modn->search("foo,bar%")       --> 直接搜索，不排序，不关联，无其它参数
     * 也可以作为 链式调用的最终方法，这样就可以增加 order | join | column 等其他参数了，例如：
     *      $db->Modn->join(...)->column(...)->enabled()->order(...)->...->search("foo,bar%")
     *          --> 在其他 curd 参数基础上，执行搜索
     * @param String $sk 搜索关键字，多个可以使用 ，隔开， 可以使用 % 
     * @return RecordSet|null
     */
    public static function search($sk)
    {
        if (!Is::nemstr($sk)) return null;
        return static::model()
            ->sk($sk)       //使用 $curd->whereParser->sk() 方法，生成 where ... like ... 条件
            ->select();
    }

    /**
     * 包裹 curd 操作得到的 结果
     * 根据不同的 $rst 返回不同的数据：
     *      PDOStatement                    根据 $method 返回 Record 实例  or  RecordSet 记录集
     *      null,false,true,string,number   直接返回
     *      indexed array                   包裹成为 RecordSet 记录集
     *      associate array                 包裹成为 Record 实例
     * !! 子类不要覆盖
     * @param Mixed $rst 由 medoo 查询操作得到的结果
     * @param String $method 由 medoo 执行的查询方法，select / insert / ...
     * @param Curd $curd curd 操作实例
     * @return Mixed 
     */
    final public static function wrap($rst, $method, &$curd)
    {
        if (!static::inited()) return $rst;

        $db = static::$db;      //数据库实例
        $mcls = static::class;  //数据表(模型) 类全称，== static::class
        if ($rst instanceof \PDOStatement) {
            //通常 insert/update/delete 方法返回 PDOStatement
            if ($method=="insert") {
                //返回 刚添加的 Model 实例
                //使用 medoo 实例的 id() 方法，返回最后 insert 的 id
                $id = $db->medoo("id");
                //再次 curd 查询，查询完不销毁 curd 实例
                $rst = $curd->column("*")->where([
                    //!! SPF-Orm 所有表都必须包含 id 字段
                    "id" => $id
                ])->get(false);
                $curd->where = [];
                return $rst;
            } else if ($method=="update") {
                //返回 刚修改的 RecordSet 记录集
                //再次 curd 查询，使用当前的 curd->where 参数
                $rst = $curd->select(false);
                return $rst;
            } else if ($method=="delete") {
                //返回 删除的行数
                $rcs = $rst->rowCount();
                return $rcs;
            } else {
                return $rst;
            }
        } else if (is_array($rst)) {
            //返回的是 记录 / 记录集
            if (empty($rst)) {
                //如果返回空记录，判断 $method
                if (in_array($method, ["select", "rand"])) {
                    //包裹为 RecordSet 空记录集对象 isEmpty()==true
                    return new RecordSet($mcls, $rst, false, $curd);
                } else {
                    //查询单条记录的 直接返回 null
                    return null;
                }
            } else if(Is::indexed($rst)) {
                //记录集 通常 select/rand 方法 返回记录集
                //包裹为 RecordSet 记录集对象
                return new RecordSet($mcls, $rst, false, $curd);
            } else if (Is::associate($rst)) {
                //单条记录 通常 get 方法 返回单条记录
                //包裹为 Model 实例
                //return new static($rst, false, $curd);
                return static::record($rst, false, $curd);
            }
        } else {
            return $rst;
        }
    }



    /**
     * 数据模型 类工具
     */

    /**
     * 数据模型单条记录数据的 批量格式转换
     *      from    curd 查询结果包裹           数据库中读取到的数据  转为  php 类型
     *      to      模型实例提交数据到数据库    数据模型实例的 context  转为  db 类型
     * !! 通过调用各字段 对应字段类型的 实例方法 $type->from|to()
     * @param String $type 转换类型，from 或 to  默认 to
     * @param Array $data 要转换的 记录条目的数据
     * @return Array|null 转换后的 数据
     */
    final public static function conv($type="to", $data=[])
    {
        if (
            !static::inited() || 
            !(Is::nemstr($type) || !in_array(strtolower($type), ["from", "to"])) || 
            !Is::nemaso($data)
        ) {
            return null;
        }
        $type = strtolower($type);

        //去除 $data 中可能存在的 非原始字段值
        $data = static::realInDb($data);

        //转换后
        $rtn = [];

        //依次转换
        static::eachColumn(function($colk, $colc) use ($type, $data, &$rtn) {
            //data 中不含此字段
            if (!isset($data[$colk])) return true;

            //创建各字段对应 类型的 Types 实例
            $tpo = static::columnTypesIns($colk);
            //调用 类型实例的 from|to 方法
            $rtn[$colk] = $tpo->$type($data[$colk]);
            //释放
            unset($tpo);

            return true;
        });

        return $rtn;
    }

    /**
     * 数据模型单条记录的 某个字段数据的 格式转换
     *      from    curd 查询结果包裹           数据库中读取到的数据  转为  php 类型
     *      to      模型实例提交数据到数据库    数据模型实例的 context  转为  db 类型
     * !! 通过调用各字段 对应字段类型的 实例方法 $type->from|to()
     * @param String $type 转换类型，from 或 to  默认 to
     * @param String $colk 字段名
     * @param Mixed $val 要转换的 字段值数据
     * @return Mixed|null 转换后的 数据
     */
    final public static function columnConv($type="to", $colk, $val)
    {
        if (
            !(Is::nemstr($type) && in_array(strtolower($type), ["from", "to"])) ||
            !static::hasColumn($colk)
        ) {
            return null;
        }

        $tpo = static::columnTypesIns($colk);
        //调用 类型实例的 from|to 方法
        $rtn = $tpo->$type($val);
        //释放
        unset($tpo);

        return $rtn;
    }

    /**
     * 只保留 传入模型记录数据中 原始字段的值，剔除 计算字段|不存在的字段
     * @param Array $data 传入的模型记录数据
     * @return Array 只保留原始字段值，去除 计算字段|不存在的字段
     */
    final public static function realInDb($data=[])
    {
        if (!static::inited()) return $data;
        $rtn = [];
        static::eachColumn(function($colk, $colc) use ($data, &$rtn) {
            if (!isset($data[$colk])) return true;
            //此字段不是原始字段，可能是 getter 计算字段
            if (!static::isRealInDbColumn($colk)) return true;
            $rtn[$colk] = $data[$colk];
        });
        return $rtn;
    }

    /**
     * 创建 数据模型 默认值[]
     * !! 自动生成的字段默认值，一定是此字段的 type["php"] 参数中指定的 数据类型
     * @param String $when 指定此默认值用于 哪个 CURD 方法，默认 insert，可选值 Spf\module\orm\Types::avaliableDefaultWhens()
     * @param Array $override 可覆盖部分字段的 默认值
     * @return Array|null 默认值数组，如果此数据模型还没有关联到 某个 Db 实例，则返回 null
     */
    final public static function dftv($when="insert", $override=[])
    {
        if (!static::inited()) return null;
        
        //准备默认值数组
        $dftv = [];
        static::eachColumn(function($colk, $colc) use ($when, &$dftv) {
            //获取此字段默认值
            $cdftv = static::columnDftv($colk, $when);
            if (is_null($cdftv)) return true;
            $dftv[$colk] = $cdftv;
            return true;
        });

        //override
        $override = static::realInDb($override);
        if (!Is::nemaso($override)) return $dftv;
        $dftv = Arr::extend(
            $dftv,
            $override,
            //!! indexed 数组 使用覆盖 而不是 merge 方式 合并
            true
        );

        return $dftv;
    }

    /**
     * 获取某个字段的 默认值
     * @param String $colk
     * @param String $when 可能在不同的 curd 方法下有不同默认值，需要传入 when 参数，默认 insert
     * @return Mixed|null 返回字段默认值，如果此字段没有定义 default 参数，返回 null
     */
    final public static function columnDftv($colk, $when="insert")
    {
        if (!static::hasColumn($colk) || static::columnConf($colk."/hasDefault")!==true) return null;
        //默认值参数
        $dftc = static::columnConf($colk."/default");
        if (!Is::nemaso($dftc) || !isset($dftc["value"])) return null;
        $dftp = $dftc["params"] ?? [];

        //支持的 when 参数类型
        $whens = Types::avaliableDefaultWhens();
        if (!Is::nemstr($when) || !in_array($when, $whens)) $when = $whens[0];

        //检查 when 参数
        $cwhen = $dftp["when"] ?? null;
        if (!Is::nemidx($cwhen)) $cwhen = [$whens[0]];
        //when 参数不匹配
        if (!in_array($when, $cwhen)) return null;

        //普通形式 默认值，直接返回
        if ($dftc["value"]!==Types::definedDefaultGetterSign()) return $dftc["value"];

        //需要通过 getter 获取默认值
        $getter = $dftp["getter"] ?? null;
        //getter 参数无效
        if (!is_callable($getter)) return null;
        //调用 getter 生成默认值
        return call_user_func_array($getter, [$colk, static::columnConf($colk)]);
    }

    /**
     * 自动拼接 dbn/modk/colk 路径
     * @param String $colk 要拼接的字段名，默认 null 不拼接
     * @param String $glup 路径连接符，默认 /  可选 .
     * @return String|null 传入了错误的 colk 则返回 null
     */
    final public static function xpath($colk=null, $glup="/")
    {
        if (Is::nemstr($colk) && !static::hasColumn($colk)) return null;
        $xpath = [static::$db->name, static::$modk];
        if (!Is::nemstr($colk)) return implode($glup, $xpath);
        $xpath[] = Str::snake($colk, "_");
        return implode($glup, $xpath);
    }

    /**
     * 自动拼接 config 参数键名路径  model/modk/column/colk
     * 用于在 $db->config->ctx(...) 中查询此模型或某个字段的 参数
     * @param String $colk 要拼接的字段名，默认 null 不拼接，用于查询此模型的参数
     * @return String|null 传入了错误的 colk 则返回 null
     */
    final public static function cpath($colk=null)
    {
        if (Is::nemstr($colk) && !static::hasColumn($colk)) return null;
        $xpath = ["model", static::$modk];
        if (!Is::nemstr($colk)) return implode("/", $xpath);
        $xpath[] = "column";
        $xpath[] = Str::snake($colk, "_");
        return implode("/", $xpath);
    }

    /**
     * 判断是否存在字段
     * @param String $colk
     * @return Bool
     */
    final public static function hasColumn($colk)
    {
        if (!Is::nemstr($colk)) return false;
        $colk = Str::snake($colk, "_");
        $cols = static::$config->columns;
        return in_array($colk, $cols);
    }

    /**
     * 判断一个字段是否 计算字段，而不是 原始字段
     * @param String $colk 字段名
     * @return Bool
     */
    final public static function isGetterColumn($colk)
    {
        if (static::hasColumn($colk)!==true) return false;
        //获取字段参数
        $colc = static::columnConf($colk);
        return isset($colc["isGetter"]) && $colc["isGetter"]===true;
    }

    /**
     * 获取模型中所有 getter 计算字段
     * @return Array 字段名数组
     */
    final public static function getterColumns()
    {
        $cols = [];
        static::eachColumn(function($colk, $colc) use (&$cols) {
            if (static::isGetterColumn($colk)) {
                $cols[] = $colk;
                return true;
            }
        });
        return $cols;
    }

    /**
     * 判断一个字段 是否 原始字段，真实在数据库中存在的 字段，而不是 计算字段
     * @param String $colk
     * @return Bool
     */
    final public static function isRealInDbColumn($colk)
    {
        if (static::hasColumn($colk)!==true) return false;
        //!! isGetterColumn 逆向判断
        return !static::isGetterColumn($colk);
    }

    /**
     * 获取模型中所有 真实存在的字段
     * @return Array 字段名数组
     */
    final public static function realInDbColumns()
    {
        $cols = [];
        static::eachColumn(function($colk, $colc) use (&$cols) {
            if (static::isRealInDbColumn($colk)) {
                $cols[] = $colk;
                return true;
            }
        });
        return $cols;
    }

    /**
     * 如果一个字段是 计算字段，判断其类型：
     *      0   在模型类中定义的 getter
     *      1   在特殊字段类型 Types 中定义的 getter
     * @param String $colk
     * @return Int|null 如果字段不是计算字段，返回 null
     */
    final public static function columnGetterIs($colk)
    {
        if (!static::isGetterColumn($colk)) return null;
        $gc = static::columnConf($colk."/getter");
        $gcls = $gc["class"] ?? null;
        $gm = $gc["method"] ?? null;
        //getter 参数无效
        if (!class_exists($gcls) || !Is::nemstr($gm) || !method_exists($gcls, $gm)) return null;
        //在模型类中定义的 getter
        if (static::class===$gcls || is_subclass_of($gcls, static::class)) return 0;
        //在特殊字段类型 Types 中定义的 getter
        if (is_subclass_of($gcls, Types::class)) return 1;
        return null;
    }
    public static function columnGetterIsInModel($colk) { return static::columnGetterIs($colk) === 0; }
    public static function columnGetterIsInTypes($colk) { return static::columnGetterIs($colk) === 1; }

    /**
     * 获取模型的特殊字段
     * @param String $spec 特殊字段类型  id|pk|unique|required|includes|getter|default|...
     * @return Array|null 这些特殊类型的 字段名[]
     */
    final public static function specialColumns($spec)
    {
        if (!static::inited() || !Is::nemstr($spec)) return null;
        $spec = Str::snake($spec, "_");
        return static::$db->config->ctx("model/".static::$modk."/special/".$spec);
    }

    /**
     * 获取某个字段的 参数
     * @param String $colk 字段名，或 字段名/参数路径/参数路径...
     * @return Array|null
     */
    final public static function columnConf($colk)
    {
        if (!Is::nemstr($colk)) return null;
        $ca = explode("/", trim($colk, "/"));
        $colk = array_shift($ca);
        if (!static::hasColumn($colk)) return null;
        $colc = static::$db->config->ctx(static::cpath($colk));
        if (!Is::nemidx($ca)) return $colc;
        return Arr::find($colc, implode("/", $ca));
    }

    /**
     * 如果一个字段是 计算字段，返回此计算字段的 getter 参数
     * @param String $colk
     * @return Array|null
     */
    final public static function getterConf($colk)
    {
        if (!static::isGetterColumn($colk)) return null;
        return static::columnConf($colk."/getter");
    }

    /**
     * 创建某个 原始字段对应的 字段类型类实例 
     * !! 计算字段没有 type["def"] 参数，无法获取 Types 类型类
     * @param String $colk
     * @return Types|null 字段类型实例， 字段不存在则返回  null
     */
    final public static function columnTypesIns($colk)
    {
        if (!static::hasColumn($colk) || static::isGetterColumn($colk)) return null;
        $tp = static::columnConf($colk."/type/def");
        if (!Is::nemstr($tp)) return null;
        $tpcls = Types::support($tp);
        if ($tpcls===false) return null;
        return new $tpcls(static::xpath($colk));
    }

    /**
     * 执行某个 特殊字段类型对应的计算字段的 getter 方法
     * 例如：模型中有 uuid 类型的字段 foo  会自动创建计算字段 foo_uuid_ts 用于输出 字段 foo 的值中包含的 时间戳，执行：
     *      Modcls::callTypesGetter( "foo_uuid_ts", $record ) 将返回 $record->foo 字段值中包含的 时间戳
     * !! 通常在 $record 模型记录实例内部通过 __get 方法 调用此方法：
     * !!   $record->foo_uuid_ts    --> $record::callTypesGetter( "foo_uuid_ts", $this )
     * @param String $colk 计算字段名
     * @param Record $record 当前所在记录实例
     * @return Mixed|null 计算字段的值，如果不是 特殊字段类型计算字段，则返回 null
     */
    final public static function callTypesGetter($colk, $record)
    {
        if (!static::columnGetterIsInTypes($colk) || !$record instanceof static) return null;
        //getter 参数
        $gc = static::columnConf($colk."/getter");
        $gm = $gc["method"];
        //必须传入 此计算字段 依赖的 实际字段名
        $ga = $gc["args"] ?? null;
        if (!Is::nemidx($ga) || !static::hasColumn($ga[0])) return null;
        //从 $record 中读取 依赖的 实际字段的值
        $dp = $ga[0];
        //从 $record 中读取 依赖的 实际字段的值
        $gr = array_map(function($col) use ($record) { return $record->$col; }, $ga);
        //$record 中没有 依赖的 实际字段的值，可能是本次查询的字段不包含 依赖的字段
        if (is_null($gr[0])) return null;

        //调用 getter
        return static::columnTypesIns($dp)->$gm(...$gr);
        //return call_user_func_array([$gcls, $gm], $ga);
    }

    /**
     * 模型内 调用 $db->eachColumn 方法
     * @param \Closure $callback 与 $db->eachColumn 参数一致
     * @return Mixed
     */
    final public static function eachColumn($callback=null)
    {
        return static::model()->eachColumn($callback);
    }

    /**
     * 准备 OrmException 中 curd 系列异常的 参数
     * @param String[] $args 参数列表，最后一个一定是 原因描述，不能含 ,
     * @return String 用 , 拼接 这些参数 成为单一的字符串
     */
    final public static function errInfo(...$args)
    {
        //这些错误信息的 第一个参数，一定是 当前模型的 xpath  数据库名/表名
        $errs = [static::xpath()];
        $args = array_map(function($ai) {
            //英文逗号，转为中文逗号
            //return str_replace(",", "，", (string)$ai);
            return OrmException::rawEncode((string)$ai);
        }, $args);
        $errs = array_merge($errs, $args);
        return implode(",", $errs);
    }



    /**
     * __callStatic
     */
    public static function __callStatic($key, $args)
    {
        /**
         * $db->Modn->findColName(val)      --> $db->Modn->find(val, col_name)  
         */
        if (substr($key, 0, 4)==="find" && strlen($key)>4) {
            if (empty($args)) return null;
            $colk = substr($key, 4);
            return static::find($args[0], $colk);
        }

        return null;
    }

    /**
     * 判断字符串是否 __callStatic 魔术方法
     * @param String $method
     * @return Bool
     */
    public static function isMagicCall($method)
    {
        //$db->Modn->findFooBar
        if (substr($method, 0, 4)==="find" && strlen($method)>4) return true;
        return false;
    }



    /**
     * 数据模型通用接口
     * 模型类接口，静态方法
     * !! 如果不是必须的，模型子类不要覆盖
     */

    /**
     * api
     * @auth true
     * @role all
     * @title 新建记录
     * @desc 新建记录
     */
    public static function createApi(...$args)
    {

    }

    /**
     * api
     * @auth true
     * @role all
     * @title 编辑记录
     */
    public static function updateApi(...$args)
    {
        
    }

    /**
     * api
     * @auth true
     * @role all
     * @title 查询记录
     */
    public static function retrieveApi(...$args)
    {
        
    }

    /**
     * api
     * @auth true
     * @role all
     * @title 删除记录
     */
    public static function deleteApi(...$args)
    {
        
    }

    /**
     * api
     * @auth true
     * @role all
     * @title 软删除删除记录
     */
    public static function disableApi(...$args)
    {
        
    }

}