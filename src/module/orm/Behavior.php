<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义数据模型拥有的特定行为 Behavior
 * 
 * 数据模型 Behavior 行为通常包含：
 *  0   一组特定的 字段组合
 *      !! 如果模型引用了某个 Behavior 则这些字段会自动合并到模型中
 *      !! 不需要在配置参数中手动指定字段，通过 Preparer 参数预处理器 引用对应的 Behavior 子类即可：
 *          [
 *              # 通过预处理器为模型增加特定 Behavior
 *              "preparer" => [
 *                  # 指定 某些已收集到的 Behavior 子类名
 *                  "behavior" => [
 *                      "status", ...
 *                  ],
 *                  ...
 *              ],
 *          ]
 *      !! Orm 模块会默认为所有模型增加两个 Behavior
 *          common      包含字段 id 自增序号
 *          extra       包含字段 info, extra, enable, disat
 *      !! Behavior 引用的解析方法，以及调用实现：
 *          0   Preparer 参数预处理器会在 数据模型参数解析阶段 执行：
 *              0.1 得到所有引用的 Behavior 子类的类全称，缓存到 对应模型类的 $behaviors[] 静态属性中
 *              0.2 将这些 Behavior 包含的字段，插入模型参数的 字段列表中
 *              0.3 预处理生成 字段索引等后续操作
 *          1   Parser/Getter 解析阶段
 *              会收集所有 Behavior 中定义的 fooBarGetter 方法，收集到模型 getters 列表中作为计算字段
 *          2   Parser/Api 解析阶段
 *              会收集所有 Behavior 中定义的 fooBarApi 方法，收集到 模型的 oprs 操作列表中，接入框架 Operation 体系
 * 
 *          3   调用实现：
 *              3.1 getters 计算字段 以及 oprs 操作接口，统一通过框架自动调用
 *              3.2 Curd 扩展系列方法 调用实现：
 *                  $db->model('model_name')    创建 curd 实例
 *                      ->where(...)            正常 Curd 操作，通过 Db::__call() 实现
 *                      ->curdExtra(...)        在 Db::__call() 内部，循环查找当前模型的 $behaviors[] 数组，找到则 静态调用
 *                      ->select()
 *              3.3 Filter 扩展系列方法 调用实现：
 *                  通过 RecordSet::__call 实现，循环查找 对应模型类的 $behaviors[] 数组，找到 filter 方法后：
 *                      3.3.1   实例化对应的 Behavior 子类，传入 当前 RecordSet 作为上下文
 *                      3.3.2   实例调用 filter 方法，返回 新的 RecordSet
 *              3.4 业务扩展方法 调用实现：
 *                  3.4.1   通过 Record::__call 实现，循环查找 对应模型类的 $behaviors[] 数组，找到 业务扩展方法后：
 *                      3.4.1.1 实例化对应的 Behavior 子类，传入 当前 Record 作为上下文
 *                      3.4.1.2 实例调用 业务方法，返回结果
 *                  3.4.2   通过 Db::__call 实现 模型类静态调用 业务方法，
 *                          循环查找 对应模型类的 $behaviors[] 数组，找到 业务扩展方法后：
 *                          3.4.2.1 实例化对应的 Behavior 子类，传入 当前模型类全称 作为上下文
 *                          3.4.2.2 实例调用 业务方法，返回结果
 *                                  !! 要求此 业务方法 不能依赖任何 Record 实例，一般是 工具方法（纯计算）
 *                                  !! 但是可以在业务方法内 访问对应模型的 配置参数
 *      
 *  1   根据特定的字段组合 衍生出 一系列 计算字段，也会自动合并到 模型的 getters 中
 *  2   为 模型扩展一系列 Api 接口，会自动收集到 模型的 oprs 操作列表中，最终接入框架 Operation 体系
 *      !! 接口方法 可以是 静态|实例 方法，必须符合框架标准的 api 接口方法定义形式
 *  3   为 模型类 扩展一系列 CURD 方法，用于快速 生成查询条件
 *      例如：
 *          3.1 ExtraBehavior 包含 extra(额外信息，json 类型)字段 可以为模型类提供：
 *              curdExtra([ "键" => "关键字", ... ]) 方法，构造 where 条件
 *                  调用方法：
 *                      $db->SomeModel->where(...)->curdExtra([...])->select()
 *                      将自动构造针对 extra 字段的查询条件
 *              curdExtraHasKey("键名")
 *          3.2 StatusBehavior 包含 status(varchar) stnum(tinyint) stlog(json) 这些字段：
 *              curdStatusIs('some_status_key')
 *              curdStatusAfter|Before|Inside|Outside(123, ...)
 *              curdStatusHasLog("2026-01-01")
 *              curdStatusLog([ "2026-01-01" => "关键字" ])     json 查询方法
 *      !! 这些方法命名格式：curdFooBar  
 *      !! 这些方法一定是 静态方法
 *  4   为 模型查询结果记录集 RecordSet 实例 扩展 filter 二次筛选方法，用于无法通过 SQL 处理的特殊查询条件
 *      例如：
 *          4.1 StatusBehavior 包含 status(varchar) stnum(tinyint) stlog(json) 这些字段：   
 *              filterStatusIsFinished()
 *                  调用方法：
 *                      $rs = $db->Model->where(...)->select();
 *                      $newRs = $rs->filterStatusIsFinished();
 *              filterStatus(function($record) { ... })
 *      !! 这些方法命名格式：filterFooBar
 *      !! 这些方法一定是 实例方法
 *  5   为 单条记录 Record 实例 扩展一系列 具体业务方法，可用于一些通用的 记录实例内部数据操作
 *      例如：
 *          5.1 PackageBehavior 包含 unit(varchar) netwt(float) maxunit(varchar) minnum(float) midunit(json) 这些字段：
 *              packageStr()     生成前端可以直接展示的 货品规格字符串
 *                  调用方法：
 *                      $rs = $db->Model->where(...)->get();
 *                      $pkg = $rs->packageStr();
 *                  !! 实际可以通过 衍生的计算字段 $rs->pkg_str 直接获取
 *              packageCalc( 传入克重 )  自动计算 箱数袋数零头数 等
 *                  例如：某条库存记录，其 qty 实时库存保存的 克重，则可以：
 *                      $stock = $db->Stocker->where(...)->get();
 *                      $units = $stock->packageCalc($stock->qty);  获取 最小包装数|最大包装数|零头克重|中间包装数
 *                      $mins = $units["min"];
 *                      $maxs = $units["max"];
 *                      $odds = $units["odd"];
 *                      $mids = $units["mid"];
 *                      $ustr = $units["str"];  直接生成 "10箱+1袋+231克" 形式的 规格字符串
 *      !! 如果这些方法不依赖单条 Record 记录数据本身，则 也可以通过 模型类静态调用
 *      例如：packageCalc 也可以这样调用：两个调用形式是等价的
 *          Model::packageCalc(123)
 *          $db->Model->packageCalc(123)
 * 
 *      !! 这些方法的命名格式：{behaviorName}FooBar
 *      !! 这些方法一定是 实例方法
 * 
 * 
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * !! 不同的应用可以在自身的 module/orm/behavior/ 路径下扩展不同的 Behavior 模型行为子类
 */

namespace Spf\module\orm;

use Spf\Env;
use Spf\App;
use Spf\exception\AppException;
use Spf\module\Orm;
use Spf\module\orm\Db;
use Spf\module\orm\Model;
use Spf\module\orm\Record;
use Spf\module\orm\RecordSet;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

use Spf\traits\ExpandableResource;
use Spf\traits\CoreInsGetter;

abstract class Behavior
{
    //核心类快捷获取
    use CoreInsGetter;

    //引用  可扩展底层资源类  特征
    use ExpandableResource;
    //!! trait 中要求的，子类不要覆盖
    protected static $exresName = "behavior";
    protected static $exresClassPath = "module/orm";
    public static $isCollected = false;
    
    /**
     * 当某个 Behavior 子类被 collect 收集时，可以执行自定义操作
     * !! trait 中要求的，子类根据需要覆盖
     * @return Bool
     */
    protected static function whenCollect()
    {
        $scols = static::$columnSet;
        if (!Is::nemaso($scols)) return true;

        //behavior_name
        $bhvn = static::$behavior;
        if (!Is::nemstr($bhvn)) return true;

        //合并到 基类 Behavior::$columnSetCollection[] 中
        Behavior::$columnSetCollection[$bhvn] = $scols;

        return true;
    }

    /**
     * !! 子类禁止覆盖 
     * 收集所有可用 Behavior 子类定义的 特定字段组合
     * 定义在 Behavior 子类的 $columnSet[] 中
     */
    public static $columnSetCollection = [
        /*
        "behavior_name" => [
            # 标示这些特定字段将被 prepend or append 默认 append
            "append" => false,
            # 要添加的 特定字段
            "columns" => [
                # 定义的字段参数将 在 Prepare 预处理阶段 被合并到 数据模型参数中，然后交由 Parser 解析器统一解析
                "col_name" => [
                    # 字段元数据  [ 中文字段名, 字段说明, 前端表格组件中字段的显示宽度% ]
                    "columns" => ["ID","自增序号",3],
                    # 字段的 creation-sql 使用 Types 定义的字段类型，约束字符使用任意支持的 数据库 Driver 形式都可以
                    "creation" => "integer PRIMARY KEY AUTOINCREMENT",
                    # 字段的 特殊类型参数
                    "column" => [
                        "includes" => ["id"],
                        "sort" => ["id"],
                        "filter" => ["id"],
                        ...
                    ]
                ],
            ],
        ],
        ...
        */
    ];



    /**
     * !! 子类必须定义
     */
    //Behavior 名称 foo_bar 形式
    protected static $behavior = "";
    //此 behavior 定义的 字段组合
    protected static $columnSet = [];
    /**
     * 当前 Behavior 类扩展的 curd|filter|业务 方法列表
     * !! 收集并缓存，当此 Behavior 被多次引用时，不会重复收集
     * !! 子类必须定义这些属性，否则会在多个 Behavior 子类中相互影响
     */
    protected static $extended = [
        "curd" => [],   //方法名数组
        "filter" => [],
        "biz" => [],
    ];
    //标记 已经收集过
    protected static $extendIsCollected = false;
    //Behavior 子类实例缓存
    protected static $ins = null;
    /**
     * 当前 Behavior 类扩展的 Getter|Api 方法收集
     * !! 收集并缓存，当此 Behavior 被多次引用时，不会重复收集
     * !! 子类必须定义这些属性，否则会在多个 Behavior 子类中相互影响
     */
    //getters
    protected static $getters = [
        /*
        "getter_column_name" => [
            # Parser/Getter 解析器中定义的 dftGetter 标准结构
        ],
        ...
        */
    ];
    //标记 getters 已经收集过
    protected static $getterIsCollected = false;
    //apis
    protected static $apis = [
        /*
        "oprn" => [
            标准操作信息数组 oprc 结构
            !! 使用时，需要在 Parser/Api 解析器内部，修改为对应的 数据库名|数据模型名 
        ],
        ...
        */
    ];
    //标记 apis 已经收集过
    protected static $apiIsCollected = false;

    /**
     * 在 Prepare 模型参数预处理阶段，执行当前 Behavior 的预处理操作：
     *  0   收集当前类的 curdFooBar|filterBar|{behaviorName}FooBar 方法，缓存到对应 静态属性
     *      !! 如果已被收集，则跳过
     *  1   向传入的 数据模型类的 $behaviors[] 静态属性 填充当前类全称，以及收集到的 方法名
     *  2   子类实现的 其他 prepare 处理逻辑
     * !! 如果有需要，子类可以覆盖此方法，必须先调用 parent::prepare()
     * @param String $model 要引用此 Behavior 的模型类全称
     * @return Array 返回要合并到 模型配置参数中的 数据，一般是
     *  [
     *      "behaviors" => [
     *          "behavior_name" => [
     *              "class" => "Behavior 类全称",
     *              "extended" => [
     *                  "curd" => [],
     *                  "filter" => [],
     *                  "biz" => [],
     *              ],
     *          ],
     *      ],
     *  ]
     */
    public static function prepare($model)
    {
        //首次收集
        if (static::$extendIsCollected !== true) {
            static::collectExtends();
        }

        //将收集到的 扩展方法，写入 数据模型类的 $behaviors[] 静态属性
        $bhvn = static::$behavior;
        return [
            "behaviors" => [
                $bhvn => [
                    "class" => static::class,
                    "extended" => static::$extended,
                ]
            ],
        ];
    }

    /**
     * !! 子类禁止覆盖
     * 收集当前类中定义的 curd|filter|业务 方法的方法名，写入缓存
     * @return Bool
     */
    final protected static function collectExtends()
    {
        if (static::$extendIsCollected === true) return true;
        //当前 Behavior 名称 fooBar 形式
        $bhvn = Str::camel(static::$behavior, false);
        $bhvnlen = strlen($bhvn);
        //反射获取所有 public 方法名
        $mns = Cls::methodNames(static::class, "public");
        //从 方法名数组中筛选相应的方法
        foreach ($mns as $mn) {
            //排除 Getter|Api
            if (substr($mn, -6)==="Getter" || substr($mn, -3)==="Api") continue;
            //方法类型 curd|filter|biz
            $mthis = static::methodIs($mn);
            if ($mthis===false) continue;
            if (!Is::indexed(static::$extended[$mthis])) static::$extended[$mthis] = [];
            if (!in_array($mn, static::$extended[$mthis])) static::$extended[$mthis][] = $mn;
        }
        //标记 已收集过
        //var_dump(static::$behavior);
        //var_dump(static::$extended);
        //if (static::$behavior === "extra") exit;
        static::$extendIsCollected = true;
        return true;
    }



    /**
     * Getter|Api 方法收集
     * !! 子类禁止覆盖
     * !! 必须 Behavior 子类调用
     */

    /**
     * 读取已收集到的 Getter|Api 数据
     * 由 Parser/Getter 解析器调用
     * @return Array|false 返回收集到的数据，如果还未收集过，返回 false
     */
    final public static function getGettersCache()
    {
        if (static::$behavior === "" || static::$getterIsCollected!==true) return false;
        return static::$getters;
    }
    final public static function getApisCache()
    {
        if (static::$behavior === "" || static::$apiIsCollected!==true) return false;
        return static::$apis;
    }

    /**
     * 由 Parser/Getter 解析器调用，将 Parser/Getter 解析获取的 getters 计算字段列表 缓存到 $getters
     * 并标记为已收集过
     * @param Array $getters 
     * @return Bool
     */
    final public static function cacheGetters($getters = [])
    {
        if (static::$behavior === "") return false;
        if (!Is::nemaso($getters)) return false;
        //写入缓存
        static::$getters = Arr::copy($getters);
        //标记
        static::$getterIsCollected = true;
        return true;
    }

    /**
     * 由 Parser/Api 解析器调用，将 Parser/Api 解析获取的 oprs 操作列表 缓存到 $apis
     * 并标记为已收集过
     * @param Array $apis 标准的 oprs 操作信息列表
     * @return Bool
     */
    final public static function cacheApis($apis = [])
    {
        if (static::$behavior === "") return false;
        if (!Is::nemaso($apis)) return false;
        //去除其中可能包含的 apis|srcs|views|... 操作标识列表
        foreach ($apis as $ak => $ac) {
            if (!Is::nemaso($ac)) {
                unset($apis[$ak]);
            }
        }
        //缓存
        static::$apis = Arr::copy($apis);
        //标记
        static::$apiIsCollected = true;
        return true;
    }



    /**
     * !! 子类禁止覆盖
     * !! 必须 Behavior 子类调用
     * 通过 $record->getter_name 调用 getter 方法，读取计算字段值
     * @param String $getter 方法名
     * @param Record $record 必须提供当前 Record 实例作为上下文
     * @param Array $args 可以传入额外参数
     * @return Mixed
     */
    final public static function callGetter($getter, $record, ...$args)
    {
        if (static::$behavior === "") return null;
        //实例化
        $ins = static::getIns($record);
        if (!($ins instanceof static)) return null;
        //$getter 方法必须存在
        if (
            !Is::nemstr($getter) || 
            !method_exists($ins, $getter)
        ) { return null; }
        //调用
        return $ins->$getter(...$args);
    }



    /**
     * !! 子类禁止覆盖
     * !! 必须 Behavior 子类调用
     * 外部调用 Behavior 定义的接口方法
     * !! 实例接口，必须传入 Record|RecordSet 实例作为上下文
     * !! 静态接口，必须传入 模型类全称 作为上下文
     * @param String $api 接口方法名
     * @param Bool $isStatic 是否静态接口
     * @param String|Record|RecordSet $ctx 上下文参数
     * @param Array $args 额外的 url 参数
     * @return Mixed
     */
    final public static function callApi($api, $isStatic, $ctx, ...$args)
    {
        if (static::$behavior === "") return null;
        //上下文类型
        $ctxIs = static::contextIs($ctx);
        if ($ctxIs === false) return null;
        
        //静态接口
        if ($isStatic) {
            if (!method_exists(static::class, $api)) return null;
            //静态接口必须传入 模型类作为上下文
            if ($ctxIs !== "model") return null;
            //静态调用
            return static::$api($ctx, ...$args);
        }

        //实例接口
        //必须传入 Record|RecordSet 实例作为上下文
        if (!in_array($ctxIs, ["record","recordset"])) return null;
        //实例化 Behavior
        $ins = static::getIns($ctx);
        if (!($ins instanceof static)) return null;
        //$getter 方法必须存在
        if (
            !Is::nemstr($api) || 
            !method_exists($ins, $api)
        ) { return null; }
        //实例调用
        return $ins->$api(...$args);
    }



    /**
     * 调用 扩展的 curd|filter|biz 方法
     */

    /**
     * !! 子类禁止覆盖
     * !! 必须 Behavior 基类调用
     * 在 Db|Model|Record|RecordSet::__call 中调用可能存在的 Behavior 方法，通常用法：
     *      public function __call($method, $args) {
     *          ...
     *          
     *          # 开始尝试调用 Behavior 方法
     *          if (false !== Behavior::hasMethod($method, $this)) {
     *              return Behavior::callMethod($method, $args, $this);
     *          }
     * 
     *          ...
     *          return null;
     *      }
     * @param String $method 要调用的 Behavior 方法名 fooBar 形式
     * @param Array $args 本次调用的 外部参数
     * @param String|Db|Record|RecordSet $ctx 本次调用的 上下文参数
     * @return Mixed
     */
    final public static function callMethod($method, $args, $ctx)
    {
        //!! 必须 Behavior 基类调用
        if (static::$behavior !== "") return null;
        //方法名必须存在
        if (!Is::nemstr($method)) return null;
        //外部参数
        if (!Is::nemidx($args)) $args = [];
        //上下文类型必须被支持
        if (false === ($ctxIs = static::contextIs($ctx))) return null;

        //判断是否存在 $method 方法，存在会返回实际的 Behavior 子类全称
        if (false === ($bhvcls = static::hasMethod($method, $ctx))) return null;

        //!! 找到实际的 Behavior 子类后，通过 子类调用
        //方法类型
        if (false === ($mthIs = $bhvcls::methodIs($method))) return null;
        //通过 call[Curd|Filter|Biz|...]Method 二次分发
        $cm = "call".Str::camel($mthIs, true)."Method";
        if (!method_exists($bhvcls, $cm)) return null;

        return $bhvcls::$cm($method, $args, $ctx);
    }

    /**
     * !! 如果不是必须的，子类不要覆盖
     * !! 必须通过 Behavior 具体子类调用
     * call[ Curd | Filter | Biz | ... ]Method 系列方法
     * 调用实际方法，返回结果
     * @param String $method 要调用的 Behavior 方法名 fooBar 形式
     * @param Array $args 本次调用的 外部参数
     * @param String|Db|Record|RecordSet $ctx 本次调用的 上下文参数
     * @return Mixed
     */
    //callCurdMethod
    protected static function callCurdMethod($method, $args, $ctx)
    {
        //!! 必须是 Behavior 子类调用
        if (static::$behavior === "") return null;
        //curd 方法只能由 Db 实例调用
        if (static::contextIs($ctx) !== "db") return null;
        //$method 方法必须存在
        if (
            !Is::nemstr($method) || 
            static::methodIs($method)!=="curd" || 
            !method_exists(static::class, $method)
        ) { return null; }

        //curd 方法必须静态调用
        if (!Is::indexed($args)) $args = [];
        return static::$method($ctx, ...$args);
    }
    //callFilterMethod
    protected static function callFilterMethod($method, $args, $ctx)
    {
        //!! 必须是 Behavior 子类调用
        if (static::$behavior === "") return null;
        //filter 方法只能由 RecordSet 实例调用
        if (static::contextIs($ctx) !== "recordset") return null;

        //实例化 Behavior 子类，优先使用缓存的实例
        $ins = static::getIns($ctx);
        if (!($ins instanceof static)) return null;
        //$method 方法必须存在
        if (
            !Is::nemstr($method) || 
            static::methodIs($method) !== "filter" ||
            !method_exists($ins, $method)
        ) { return null; }

        //filter 方法必须实例调用
        if (!Is::indexed($args)) $args = [];
        $rs = $ins->$method(...$args);
        //filter 方法的结果 必须是 新的 RecordSet
        if (!($rs instanceof RecordSet)) return null;
        return $rs;
    }
    //callBizMethod
    protected static function callBizMethod($method, $args, $ctx)
    {
        //!! 必须是 Behavior 子类调用
        if (static::$behavior === "") return null;
        //biz 方法只能由 Db|Record 实例 或 模型类 调用
        $ctxIs = static::contextIs($ctx);
        if (!in_array($ctxIs, ["db","model","record"])) return null;

        //如果 $ctx 不是 Record 实例，则需要获取 模型类全称作为上下文
        if ($ctxIs === "db") {
            $ctx = $ctx->curd->model;
        } else if ($ctxIs === "model") {
            if (!class_exists($ctx)) return null;
        }

        //实例化 Behavior 子类，优先使用缓存的实例
        $ins = static::getIns($ctx);
        if (!($ins instanceof static)) return null;
        //$method 方法必须存在
        if (
            !Is::nemstr($method) || 
            static::methodIs($method) !== "biz" ||
            !method_exists($ins, $method)
        ) { return null; }

        //biz 方法必须实例调用
        if (!Is::indexed($args)) $args = [];
        return $ins->$method(...$args);
    }




    /**
     * Behavior 实例 属性|方法
     */
    //实例上下文 只能是 ModelClass | Db | Record | RecordSet
    public $context = null;
    //实例上下文类型 "model" | "db" | "record" | "recordset"
    public $ctxIs = null;

    /**
     * 构造
     * @param String|Db|Record|RecordSet $ctx 实例上下文
     * @return void
     */
    protected function __construct($ctx)
    {
        //写入 上下文
        $this->changeCtx($ctx);
        if (is_null($this->context)) return null;
    }

    /**
     * 切换上下文
     * @param String|Db|Record|RecordSet $ctx
     * @return $this
     */
    public function changeCtx($ctx)
    {
        if (false !== ($cis = static::contextIs($ctx))) {
            $this->context = $ctx;
            $this->ctxIs = $cis;
        } else {
            $this->context = null;
            $this->ctxIs = null;
        }
        return $this;
    }



    /**
     * 工具方法
     */

    /**
     * 实例化 Behavior 子类
     * @param String|Db|Record|RecordSet $ctx
     * @return Behavior|null 子类实例，或 null
     */
    public static function getIns($ctx)
    {
        //!! 必须 Behavior 子类调用
        if (static::$behavior === "") return null;
        if (static::contextIs($ctx)===false) return null;

        //优先使用已缓存的 实例
        $ins = static::$ins;
        if (!is_null($ins) && $ins instanceof static) {
            //切换上下文
            return $ins->changeCtx($ctx);
        }

        //创建实例
        $ins = new static($ctx);
        //缓存实例
        static::$ins = $ins;
        return $ins;

    }

    /**
     * 判断传入的 Behavior 方法的 上下文 是否复合要求
     * @param String|Db|Record|RecordSet $ctx
     * @return String|false 不符合要求返回 false，符合要求则返回 ctx 的 类型：
     *      "model" | "db" | "record" | "recordset"
     */
    public static function contextIs($ctx)
    {
        if (is_string($ctx)) {
            if ($ctx==="" || !(class_exists($ctx) && is_subclass_of($ctx, Model::class))) return false;
            return "model";
        }

        if ($ctx instanceof Db) return "db";
        if ($ctx instanceof Record) return "record";
        if ($ctx instanceof RecordSet) return "recordset";

        return false;
    }

    /**
     * 从传入的 Behavior 方法上下文中，获取对应的 数据模型类全称
     * @param String|Db|Record|RecordSet $ctx
     * @return String|false 找到则返回 类全称，否则 返回 false
     */
    public static function getModelFromCtx($ctx)
    {
        if (false === ($ctxIs = static::contextIs($ctx))) return false;

        //获取 $ctx 指向的 模型类
        $model = $ctxIs==="model" ? $ctx : (
            $ctxIs==="db" ? $ctx->curd->model : (
                $ctxIs==="recordset" ? $ctx->model : get_class($ctx)
            )
        );
        if (is_subclass_of($model, Model::class)!==true) return false;
        return $model;
    }

    /**
     * 根据方法名，判断这是什么类型的 方法
     * @param String $method 方法名
     * @return String|false 返回 curd|filter|biz 不是任何类型 返回 false
     */
    public static function methodIs($method)
    {
        if (!Is::nemstr($method)) return false;
        //curdFooBar --> curd_foo_bar
        $kab = Str::snake($method, "_");
        if (substr($kab, 0, 5)==="curd_") return "curd";
        if (substr($kab, 0, 7)==="filter_") return "filter";
        //如果不是基类调用，还要判断是否 biz 
        $bhvn = static::$behavior;
        if ($bhvn!=="") {
            //确保 behavior 名称是 foo_bar 形式
            $bhvn = Str::snake($bhvn, "_");
            if (substr($kab, 0, strlen($bhvn)+1)===$bhvn."_") return "biz";
        }
        return false;
    }

    /**
     * 根据传入的 behavior 方法执行上下文 判断 对应的数据模型类中 是否包含指定的 curd|filter|biz 方法
     * @param String $method 方法名
     * @param String|Db|Record|RecordSet $ctx 
     * @return String|Bool
     *      Behavior 基类调用时，存在 $method 则返回 Behavior 子类全称，不存在则返回 false
     *      Behavior 子类调用时，存在 $method 则返回 true，否则返回 false
     */
    public static function hasMethod($method, $ctx)
    {
        if (!Is::nemstr($method)) return false;
        //上下文对应的 数据模型类全称
        if (false === ($model = static::getModelFromCtx($ctx))) return false;

        //$method 方法类型
        $mthIs = static::methodIs($method);

        //此模型包含的所有 behaviors
        $bhvs = $model::$behaviors;
        if (!Is::nemarr($bhvs)) return false;

        //如果是 Behavior 子类调用
        if (static::$behavior !== "") {
            $bhvi = $bhvs[static::$behavior] ?? null;
            if (!Is::nemarr($bhvi)) return false;
            //Behavior 子类调用时，方法类型不能是 false
            if ($mthIs===false) return false;
            $bhvarr = $bhvi[$mthIs] ?? [];
            return in_array($method, $bhvarr);
        }

        //Behavior 基类调用
        foreach ($bhvs as $bhvn => $bhvi) {
            $bhvarr = $mthIs===false ? $bhvi["biz"] : $bhvi[$mthIs];
            if (!isset($bhvarr) || !Is::nemarr($bhvarr)) continue;
            if (in_array($method, $bhvarr)) {
                $bhvcls = static::support($bhvn);
                if (!class_exists($bhvcls)) return false;
                return $bhvcls;
            }
        }

        return false;
    }

    /**
     * curd 工具方法，查询 json 类型的字段 键值
     */



    //!! Behavior 扩展行为
    
    /**
     * getter 计算字段
     * !! 必须是 实例方法 public
     * !! 调用时，必须提供 Record 实例作为上下文
     */



    /**
     * api 接口方法
     * !! 可以是 实例方法 或 静态方法，必须是 public
     * !! 实例方法接口，调用时 需要传入 Record|RecordSet 作为上下文
     * !! 静态方法接口，调用时 需要传入 模型类全称 作为上下文
     */



    /**
     * curd 系列方法
     * 在 curd 执行前，修改查询参数
     * !! 必须是 静态方法
     * @param Db $db 第一个参数一定是 数据库实例，可以通过 $db->curd 获取当前 curd 操作实例
     * @param Mixed[] $args 更多参数
     * @return Db 被修改后的 数据库实例，可以继续 链式执行 后续 curd 操作
     */



    /**
     * filter 系列方法
     * 在 curd 执行完成，得到 结果记录集后，在 RecordSet 实例上执行 二次筛选
     * !! 必须是 实例方法
     * !! $this->context 一定指向 当前的 RecordSet
     * @param Mixed[] $args 更多参数
     * @return RecordSet 返回值一定是 新的 RecordSet 记录集实例
     */



    /**
     * biz 系列方法
     * 在 Record 单记录实例 或 模型类 上执行业务操作
     * !! 必须是 实例方法
     * !! $this->context 可能指向 Record 实例  或  模型类全称，需要在方法内自行判断
     * @param Mixed[] $args 更多参数
     * @return Mixed 业务方法返回值不确定
     */



}