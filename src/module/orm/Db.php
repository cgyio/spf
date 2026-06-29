<?php
/**
 * SPF-Orm 数据库操作模块
 * Db 数据库类 
 * 此类可直接操作数据库
 * 一次会话，一个数据库只实例化一次
 * 
 * 创建 Db 实例：
 *      !! 应通过 Orm 实例创建 Db 实例
 *      $db = Orm::$current->db(dbn)
 *      也可以直接使用魔术方法：
 *          Db::FooBar()
 *          $orm->FooBar
 *          $orm->foo_bar
 *      
 * 
 * 初始化一个 curd 操作，指向模型 Modn  准备链式调用：
 *      $db->Modn
 *              ->join(false)->field("*")->where([...])->limit([0,20])->order(["foo"=>"DESC"])->select();
 * 
 * 获取数据模型类 属性：
 *      $db->Modn
 *              ->conf
 *                  ->ctx[...]
 *                  ->column[...]
 *              ->modk
 *              ->modn
 */

namespace Spf\module\orm;

use Spf\Runtime;
use Spf\module\Orm;
use Spf\module\orm\OrmException;
use Spf\module\orm\Driver;
use Spf\module\orm\Curd;
use Spf\module\orm\config\DbConfig;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Operation;
use Medoo\Medoo;

use Spf\traits\CoreInsGetter;

class Db 
{
    //核心类快捷获取
    use CoreInsGetter;

    /**
     * 此数据库的原始信息
     */
    //当前会话中，此数据库的名称 foo_bar
    public $name = "";
    //全局唯一 key
    public $key = "";
    //此数据库的 title 中文名
    public $title = "";
    //此数据库的 desc 说明
    public $desc = "";

    //此数据库实例关联的 DbConfig 配置实例 继承自 Configer 类
    public $config = null;

    //此数据库所属的 driver 实例
    public $driver = null;
    //关联的 实际操作数据库的 Medoo 底层实例
    protected $_medoo = null;
    //关联的 Curd 实例，执行单次 curd 操作
    public $curd = null;

    //数据库已连接 标记
    public $isConnected = false;

    //内部 数据模型指针，指向当前操作的 数据模型，可通过 $db->Modn 修改指向
    protected $currentModel = "";   //数据模型类全称
    protected $currentModk = "";    //数据模型名 foo_bar



    /**
     * 构造
     * @param DbConfig $cfger 数据库配置类实例
     * @return void
     */
    public function __construct($cfger)
    {
        //数据库实例化时，Orm 模块必须已经实例化
        if (Orm::$isInsed!==true) return null;
        if (!$cfger instanceof DbConfig) return null;

        $orm = Orm::$current;

        //缓存原始信息
        $dbn = $cfger->ctx["name"];
        $this->name = $dbn;
        $this->key = $cfger->ctx["key"];
        $this->title = $cfger->ctx["title"];
        $this->desc = $cfger->ctx["desc"];

        //缓存数据库实例配置器
        $this->config = $cfger;

        //将当前数据库实例，挂到每个 模型的 类属性中
        $this->eachModel(function($modk, $modc) {
            $modcls = $modc["class"] ?? null;
            if (!class_exists($modcls)) return true;
            //挂载
            $modcls::$db = $this;
            $modcls::$modk = $modk;
            $modcls::$modn = Str::camel($modk, true);
            $modcls::$config = (object)$modc;
            return true;
        });

        //通过 driver 连接数据库，返回 driver 实例
        $driver = $cfger->ctx["driver"];
        $dro = new $driver($this);
        if (!$dro instanceof Driver) {
            //未能实例化 数据库驱动
            throw new OrmException($this->name.",无法创建数据库驱动实例", "db/connect");
            return null;
        }
        $this->driver = $dro;

        //立即连接
        if ($this->connect()!==true) {
            //无法连接数据库
            throw new OrmException($this->name.",未知原因，检查连接参数", "db/connect");
            return null;
        }
    }

    /**
     * 调用 driver 连接数据库，或 传入新参数重新连接数据库
     * @param Array $opt 可以传入额外的连接参数
     * @return Bool
     */
    public function connect($opt=[])
    {
        //没有驱动实例
        if (!$this->driver instanceof Driver) return false;
        //已经连接过，清空连接
        if ($this->isConnected) {
            $this->_medoo = null;
            $this->isConnected = false;
        }
        //调用驱动 连接数据库
        $medoo = $this->driver->connect($opt);
        $this->_medoo = $medoo;
        $this->isConnected = true;
        return true;
    }

    /**
     * 调用 底层 Medoo 实例，执行实际数据库操作
     * @param String $method Medoo 库的方法
     * @param Array $params 要传入的 参数
     * @return Mixed
     */
    public function medoo($method = null, ...$params)
    {
        //如果还未连接
        if (!$this->_medoo instanceof Medoo) {
            if ($this->connect()!==true) return null;
        }
        if (!Is::nemstr($method)) return $this->_medoo;
        if (method_exists($this->_medoo, $method)) return $this->_medoo->$method(...$params);
        return null;
    }

    /**
     * 获取数据模型的 类全称
     * @param String $modk 数据模型名  foo_bar 或 FooBar
     * @param Bool $curd 如果指定 true 则 内部指向此数据模型，创建 curd 实例，准备链式执行 curd 操作
     * @return String|Db|null 如果 $curd==true 则返回 $this 准备链式操作，否则返回 模型类全称 或 null
     */
    public function model($modk, $curd=false)
    {
        if (!Is::nemstr($modk)) return $curd ? $this : null;
        //必须传入有效的 模型名
        if (false===($modcls = $this->hasModel($modk))) return $curd ? $this : null;
        
        //不执行 curd 准备
        if ($curd!==true) return $modcls;

        //如果当前已经指向 $modk
        if ($this->curdReady($modk)===true) return $this;

        //修改 内部指向
        $this->currentModel = $modcls;
        $this->currentModk = Str::snake($modk, "_");
        //创建 curd 实例
        return $this->initCurd();
    }

    /**
     * $this->model($modk, true) 的 逆操作，取消指向某个模型
     * @return $this
     */
    public function db()
    {
        $this->currentModel = "";
        $this->currentModk = "";
        return $this->unsetCurd();
    }

    /**
     * 获取 当前数据库 或 指向的数据模型 中定义的所有 apis 操作列表
     * @param String $apin 传入 api 名 foo_bar 或 fooBar ，可查找对应的 apic 默认不穿 apin 返回全部 apis
     * @return Array|null
     *  如果未传入要查找的 apin 接口方法名 则返回所有定义的 apis 
     *      [
     *          "api_name" => [ ... stdOprc 标准操作信息数组 ... ],
     *          ...
     *      ]
     *  如果传入了 apin 则返回找到的 操作信息数组 stdOprc  未找到则返回 null
     */
    public function apis($apin=null)
    {
        //判断当前是否指向了 某个数据模型
        if ($this->curdReady()===true) {
            $pre = "api/model/".$this->name."/".$this->currentModk.":";
        } else {
            $pre = "api/db/".$this->name.":";
        }
        $prelen = strlen($pre);
        //如果传入了 apin
        if (Is::nemstr($apin)) $apin = Str::snake($apin, "_");
        //当前应用中的 全部操作
        $defs = $this->App()->operation->defines();
        $apis = [];
        foreach ($defs as $oprn => $oprc) {
            //只选择符合要求的 opr 操作
            if (substr($oprn, 0, $prelen)!==$pre) continue;
            //得到 api 名称 foo_bar
            $apini = substr($oprn, $prelen);
            $apini = Str::snake($apin, "_");
            //收集 或 返回
            if (Is::nemstr($apin)) {
                if ($apin===$apini) return $oprc;
            } else {
                $apis[$apin] = $oprc;
            }
        }
        return Is::nemstr($apin) ? null : $apis;
    }

    /**
     * 调用 数据库 或 指向的数据模型类(静态的) api 方法
     * !! 通过 $app->invoke 方式，调用 api 操作，会检查权限
     * !! 只能通过 __call 魔术方法调用
     * @param String $apin 方法名 foo_bar 或 fooBar  不带 -Api 后缀
     * @param Array $args 应在 url 提供的 路由参数  indexed 数组  或  foo/bar/jaz?a=b&c=d 形式字符串
     * @param Array $post 模拟 以 php://input 方式提交的 json 数据
     * @param \Closure $callback 额外的操作方法，参数为 api 方法返回的结果，必须在最后 返回修改后的 api 方法结果
     * @return Mixed|null api 方法的结果
     */
    protected function callApi($apin, $args=[], $post=[], $callback=null)
    {
        if (!Is::nemstr($apin)) return null;
        //apic 操作信息数组
        $apic = $this->apis($apin);
        if (!Operation::isStdOprc($apic) || !isset($apic["proxy"])) return null;
        //!! 数据库 api 必须是 实例方法，数据模型 api 必须是 静态方法
        if ($apic["proxy"]["isStatic"]!==$this->curdReady()) return null;

        //!! invoke
        return $this->App()->invoke($apic, $args, $post, $callback);
    }



    /**
     * CURD
     */

    /**
     * 判断数据库当前是否已经准备好执行 curd 操作
     * 当前是否已有 指向的 内部的数据模型，curd 实例已创建
     * @param String $modk 如果传入 模型名，则检查是否是当前指向的 模型
     * @return Bool
     */
    public function curdReady($modk=null)
    {
        $ready = Is::nemstr($this->currentModel) && 
            Is::nemstr($this->currentModk) && 
            $this->curd instanceof Curd;
        if (!Is::nemstr($modk)) return $ready;
        return $ready && $this->currentModel === $this->hasModel($modk);
    }

    /**
     * 初始化一个 curd 操作
     * @param String $modk 数据模型名 如果不指定则使用 $this->currentModel
     * @return Db $this
     */
    public function initCurd($modk=null)
    {
        $modcls = Is::nemstr($modk) ? $this->hasModel($modk) : $this->currentModel;
        //!! 如果未能传入有效模型名 且 当前也没有 currentModel 则清除当前的 curd 实例
        if (!Is::nemstr($modcls) || !class_exists($modcls)) return $this->unsetCurd();
        
        //如果当前 curd 已有实例
        if ($this->curd instanceof Curd) {
            //当前 curd 实例已经指向 currentModel
            if ($this->curd->model===$modcls) return $this;
            //否则 unsetCurd
            $this->curd = null;
        }
        
        //创建 curd 实例
        $this->curd = new Curd($this, $modcls);

        return $this;
    }

    /**
     * 销毁当前 curd 操作实例
     * @return Db $this
     */
    public function unsetCurd()
    {
        $this->curd = null;
        return $this;
    }



    /**
     * __get | __call
     * curd 链式操作
     */
    public function __get($key)
    {
        /**
         * $db->ModelName                   --> $this->model(ModelName, true)
         * $db->ModelA->....->ModelB->...   --> $this->model(ModelB, true) 切换指向其他模型
         */
        if ($this->hasModel($key)!==false) {
            //指向此模型，准备 curd
            return $this->model($key, true);
        } 

        /**
         * 在 $this->curdReady()===true 的情况下，优先读取 模型静态属性
         */
        if ($this->curdReady()===true) {
            $cmod = $this->currentModel;
            $modk = $this->currentModk;
            $modc = $this->config->ctx("model/$modk");
            $curd = $this->curd;

            /**
             * 读取当前 数据模型的配置参数
             * $db->Modn->conf
             * $db->Modn->conf->ctx[...]
             * $db->Modn->conf->column[...]
             */
            if ($key==="conf") {
                $rtn = Arr::extend([], $modc, [
                    "ctx" => $modc
                ]);
                return (object)$rtn;
            }

            /**
             * 数据模型内部 访问其他属性
             * $db->Modn->modk|modn
             */
            if (isset($cmod::$$key)) return $cmod::$$key;

            /**
             * 返回当前指向的 模型类全称
             * $db->Modn->class
             */
            if ($key==="class") return $cmod;

            /**
             * 获取 模型的 特殊字段
             * $db->Modn->fooBarColumns     --> $db->Modn->specialColumns(foo_bar)
             */
            if (substr($key, -7)==="Columns") return $cmod::specialColumns(Str::snake(substr($key,0,-7),"_"));
        }

        return null;
    }
    public function __call($key, $args)
    {
        /**
         * 在 $this->curdReady()===true 的情况下，优先执行 curd 相关操作
         */
        if ($this->curdReady()===true) {
            $cmod = $this->currentModel;
            $modk = $this->currentModk;
            $modc = $this->config->ctx("model/$modk");
            $curd = $this->curd;

            /**
             * 优先执行 curd 操作
             * 检查此方法是否是 curd 实例方法，或 curd 通过 __get 执行的方法
             * $db->Modn->join(false)->where(...)->whereId()->order()->orderId()->select()
             */
            if (
                //curd 实例中定义的 方法 join()|where()|order()|trans()|commit()...
                method_exists($curd, $key) ||
                //curd 实例 通过 __get 执行的方法  whereFoobar()|orderFoobar()
                $curd->hasWhereMethod($key) ||
                //curd 支持的 Medoo 底层方法  select()|update()|delete()...
                $curd->hasMedooMethod($key) 
            ) {
                $rtn = $curd->$key(...$args);
                if ($rtn instanceof Curd) return $this;
                return $rtn;
            }

            /**
             * 执行 模型定义的 __callStatic 魔术方法
             * $db->Modn->findId(...)
             */
            if ($cmod::isMagicCall($key)===true) {
                return $cmod::$key(...$args);
            }

            /**
             * 执行 模型 其他静态方法
             */
            if (method_exists($cmod, $key)) {
                $rtn = call_user_func_array([$cmod, $key], $args);
                //模型类方法 如果返回 类全称，则继续链式调用
                if ($rtn===$cmod) return $this;
                return $rtn;
            }

        }

        /**
         * 执行 数据库 或 当前指向的数据模型类 中定义的 api 方法
         * !! 通过 $app->invoke 方式执行，会检查权限
         * $db->Modn->create([], [data], function() {})
         * $db->db()->backupDb(...)
         * 完整参数：
         * @param Array 应在 url 中提供的 路由参数，indexed[] 或  foo/bar/jaz?a=b&c=d 形式字符串
         * @param Array 模拟通过 php://input 传入的 json 数据
         * @param \Closure 可以指定额外操作方法，参数为 api 方法返回的结果，返回处理后的 结果
         * !! 可以任意传入 1~3 个参数，顺序不可变，例如：
         *      (args),         (post),                 (callback),
         *      (args, post),   (post, callback),       (args, callback)
         *      (args, post, callback)
         */
        if (!empty($this->apis($key))) {
            $ps = [[],[],null];
            if (!Is::nemarr($args)) {
                //未传入任何参数
                //直接使用 $ps
            } else if (count($args)===1) {
                //传入一个参数
                if (Is::nemaso($args[0])) {
                    $ps[1] = $args[0];
                } else if (Is::nemidx($args[0]) || Is::nemstr($args[0])) {
                    $ps[0] = $args[0];
                } else if ($args[0] instanceof \Closure) {
                    $ps[2] = $args[0];
                }
            } else if (count($args)===2) {
                //传入了2个参数，只能是 [args, post] 或 [post, callback] 或 [args, callback]
                if (Is::nemidx($args[0]) || Is::nemstr($args[0])) $ps[0] = $args[0];
                if ($args[1] instanceof \Closure) $ps[2] = $args[1];
                if (Is::nemaso($args[0])) {
                    $ps[1] = $args[0];
                } else if (Is::nemaso($args[1])) {
                    $ps[1] = $args[1];
                }
            } else {
                //传入了 3 个或以上 参数
                $ps = array_slice($args, 0, 3);
            }
            //!! 数据库操作时，post 的数据必须包裹在 "orm" => [...]
            if (Is::nemaso($ps[1])) $ps[1] = ["orm"=>$ps[1]];
            //调用 callApi 方法
            return $this->callApi($key, ...$ps);
        }

        return null;
    }



    /**
     * 工具方法
     */

    /**
     * 对此数据库中的 所有数据模型 依次执行 回调方法
     * 使用 $this->config->ctx["model"] 作为循环依据
     * @param \Closure $closure 对每个 数据模型 执行的 回调
     *      @param String $modk 数据模型名 foo_bar
     *      @param Array $modc 数据模型配置参数
     *      @return Mixed 返回：
     *              true    --> continue
     *              false   --> break
     *              any     --> 合并到结果数组中 [ modk=>any, ... ]
     * @return Array|null 结果数组 associate 键名为 modk
     */
    public function eachModel($closure=null)
    {
        if (!$closure instanceof \Closure) return null;
        //将当前 $this 绑定到 $closure，传入 __CLASS__ 确保可以放当前类的 受保护属性
        //$closure = $closure->bindTo($this, __CLASS__);
        //结果数组
        $rtn = [];

        //!! 使用 $this->config->ctx["model"][] 作为循环依据
        foreach ($this->config->ctx["model"] as $modk => $modc) {
            //跳过 无效的 数据模型参数
            if (!Is::nemaso($modc)) continue;
            //执行回调
            $rtni = $closure($modk, $modc);
            //true|false
            if ($rtni===true) continue;
            if ($rtni===false) break;
            //合并结果
            $rtn[$dbn] = $rtni;
        }

        return Is::nemaso($rtn) ? $rtn : null;
    }

    /**
     * 对此数据库中的 指定的数据模型中的 所有 字段 依次执行 回调方法
     * 使用 $this->config->ctx["model"][$modk]["column"] 作为循环依据
     * @param \Closure $closure 对每个 字段 执行的 回调
     *      @param String $colk 字段名 foo_bar
     *      @param Array $colc 字段配置参数
     *      @return Mixed 返回：
     *              true    --> continue
     *              false   --> break
     *              any     --> 合并到结果数组中 [ colk=>any, ... ]
     * @return Array|null 结果数组 associate 键名为 colk
     */
    public function eachColumn($closure=null)
    {
        //!! 必须是 curdReady
        if ($this->curdReady()!==true) return null;
        if (!$closure instanceof \Closure) return null;
        //将当前 $this 绑定到 $closure，传入 __CLASS__ 确保可以放当前类的 受保护属性
        //$closure = $closure->bindTo($this, __CLASS__);
        //结果数组
        $rtn = [];

        //!! 使用 $this->config->ctx["model"][$currentModk]["column"] 作为循环依据
        $modc = $this->config->ctx["model"][$this->currentModk] ?? [];
        if (!Is::nemaso($modc) || !Is::nemaso($modc["column"])) return null;
        foreach ($modc["column"] as $colk => $colc) {
            //跳过 无效的 数据模型参数
            if (!Is::nemaso($colc)) continue;
            //执行回调
            $rtni = $closure($colk, $colc);
            //true|false
            if ($rtni===true) continue;
            if ($rtni===false) break;
            //合并结果
            $rtn[$dbn] = $rtni;
        }

        return Is::nemaso($rtn) ? $rtn : null;
    }

    /**
     * 判断是否存在 传入的 数据模型
     * @param String $modk 数据模型名 foo_bar FooBar
     * @return String|false 存在则返回模型类全称，否则返回 false
     */
    public function hasModel($modk)
    {
        if (!Is::nemstr($modk)) return false;

        //先检查一次
        //$modcls = Cls::find($modk);
        //if (class_exists($modcls) && is_subclass_of($modcls, static::class)) return $modcls;

        //--> foo_bar
        $modk = Str::snake($modk, "_");
        $conf = $this->config->ctx;
        return in_array($modk, $conf["models"]) ? $conf["model"][$modk]["class"] : false;
    }



    /**
     * 静态方法
     */

    /**
     * __callStatic
     */
    public static function __callStatic($key, $args)
    {
        //!! Orm 必须已经启动
        if (Orm::$isInsed!==true) return null;
        $orm = Orm::$current;

        /**
         * Db::Dbn()        --> $orm->db(Dbn)
         */
        if ($orm->dbDefed($key)) return $orm->db($key);

        return null;
    }



    /**
     * Db 接口方法
     * !! 子类可自行扩展
     */

    /**
     * api
     * @title 数据库结构化参数
     * @auth false
     * @param Array $args
     * @return Array
     */
    public function structureApi(...$args)
    {
        $cfger = $this->config;
        $ctx = $cfger->ctx;
        //返回的数据库结构化参数结构
        $rtn = [
            "meta" => [
                "name" => $ctx["name"],
                "title" => $ctx["title"],
                "desc" => $ctx["desc"],
            ],
            //模型列表
            "models" => $ctx["models"],
            //模型结构化参数
            "model" => []
        ];
        //依次输出各数据模型的结构化参数
        foreach ($ctx["models"] as $modk) {
            $modc = $ctx["model"][$modk];
            $mcls = $modc["class"];
            $modi = [
                "meta" => [
                    "name" => $modc["name"],
                    "title" => $modc["title"],
                    "desc" => $modc["desc"],
                ],
                //真实字段
                "columns" => $mcls::realInDbColumns(),
                //计算字段
                "getters" => $mcls::getterColumns(),
                //数据记录的 TS interface 仅包含 真实字段
                "tsInterface" => [],
                //所有字段(包含计算字段)的 结构化参数
                "column" => []
            ];
            //依次输出各字段的结构化参数
            foreach ($modc["columns"] as $colk) {
                $colc = $modc["column"][$colk];
                $real = ($colc["isGetter"] ?? false) === false;
                //必查字段，表示前端 interface 中此字段必须存在
                $inc = ($colc["isIncludes"] ?? false) === true;
                $coli = [
                    "meta" => [
                        "name" => $colc["name"],
                        "title" => $colc["title"],
                        "desc" => $colc["desc"],
                    ],
                    //字段类型 js 类型
                    "type" => $colc["type"]["js"],
                ];
                //合并其他参数
                $coli = Arr::extend([], $colc, [
                    //删除这些参数
                    "name" => "__delete__",
                    "title" => "__delete__",
                    "desc" => "__delete__",
                    "type" => "__delete__",
                    "creation" => "__delete__",
                    "creationParams" => "__delete__",
                    "default" => "__delete__",
                ], $coli);
                $modi["column"][$colk] = $coli;

                //interface
                if (!$real) continue;
                $itfk = $inc ? $colk : "$colk?";
                $modi["tsInterface"][$itfk] = $coli["type"];
            }
            $rtn["model"][$modk] = $modi;
        }

        return $rtn;
    }

    /**
     * api
     * @title 创建数据库
     * @auth true
     * @role db
     * @pause false
     * 
     * @param Array $args
     * @return Array
     */
    public function createDbApi(...$args)
    {
        //invoke 测试
        return [
            "create" => "invoked",
            "args" => $args
        ];

        /*$res = $this->driver->create();
        return [
            "create" => $res
        ];*/
    }

    /**
     * api
     * @title 重建数据库
     * @auth true
     * @role db
     * @pause false
     * 
     * @param Array $args
     * @return Array
     */
    public function recreateDbApi(...$args)
    {
        //invoke 测试
        $hjres = $this->db()->create("foooooo/barrrrr?a=1&b=2", [
            "input" => "input data here"
        ], function($result) {
            var_dump("----- isolate start -----");
            var_dump($this->Req()->gets);
            var_dump($this->Req()->inputs);
            var_dump("----- isolate end -----");
            return $result;
        });
        
        var_dump($this->Req()->gets);
        var_dump($this->Req()->inputs);

        return Arr::extend($hjres, [
            "recreate" => "invoked",
            "args" => $args
        ]);

        /*$res = $this->driver->recreate();
        return [
            "recreate" => $res
        ];*/
    }

    /**
     * api
     * @title 备份数据库
     * @auth true
     * @role db
     * @pause false
     * 
     * @param Array $args
     * @return Array
     */
    public function backupDbApi(...$args)
    {
        $bf = $this->driver->backup();
        return [
            "backup" => $bf
        ];
    }

    /**
     * api
     * @title 恢复数据库
     * @auth true
     * @role db
     * @pause false
     * 
     * @param Array $args
     * @return Array
     */
    public function restoreDbApi(...$args)
    {
        $bf = $this->driver->restore();
        return [
            "restore" => $bf
        ];
    }

}