<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型(表) 配置参数解析类  基类
 * 负责解析 某个数据模型(表) 配置参数中的 某些参数项目
 * 
 * 所有 Parser 解析类子类 都继承自此基类
 * 
 * 框架预定义的 数据模型(表) 配置参数解析器：
 *      CreationSql       解析模型中各字段的 creation-sql 得到字段的 类型、默认值 等参数
 *      Column            解析模型中各字段的特殊字段类型参数 json|time|number|select...
 *      Join              解析模型的 关联查询参数
 *      Getter            解析模型类中定义的 fooBarGetter 计算字段，添加到字段列表中
 *      Api               解析模型类中定义的，可用于响应请求的 api 接口方法
 *      Other             解析其他的模型参数，例如 元数据
 * 
 * 
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * !! 需要在 自定义 Parser 类的 whenCollect() 方法中，将自身添加到 Parser::$parsers[] 解析序列中
 * 
 * !! 此解析类支持扩展的意义：
 * 可在扩展的解析类中，自定义某些 数据模型配置参数的 解析方法
 * 即：可以在数据库配置文件中，自定义一些数据模型配置项目，不在 DbConfig::$stdModel[] 定义范围内的项目
 * 
 */

namespace Spf\module\orm\config\model;

use Spf\App;
use Spf\module\orm\OrmException;
use Spf\module\orm\config\DbConfig;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

use Spf\traits\ExpandableResource;

abstract class Parser 
{
    //引用  可扩展底层资源类  特征
    use ExpandableResource;
    //!! trait 中要求的，子类不要覆盖
    protected static $exresName = "parser";
    protected static $exresClassPath = [
        "module/orm/config/model",
        "db/config/model"
    ];
    public static $isCollected = false;
    
    /**
     * 当某个 数据模型参数解析子类被 collect 收集时，将此解析类附加到 Parser::$parsers[] 解析顺序的 末尾
     * !! trait 中要求的，子类根据需要覆盖
     * !! 可以在特定的解析类中，自行处理 解析顺序，例如插入某个其他解析类之前或之后
     * @return Bool
     */
    protected static function whenCollect()
    {
        $rtn = true;

        //当前解析类名称
        $pk = static::$parser;
        if (!in_array($pk, Parser::$parsers)) {
            //扩展的 解析类，默认在最后执行解析
            Parser::$parsers[] = $pk;
        }

        //!! 各扩展解析类，如果需要特殊解析顺序，在此方法中处理

        return $rtn;
    }



    /**
     * 框架预定义的 Parser 解析器类型
     * 执行解析时，将严格按照此顺序依次执行解析
     * !! 子类不要覆盖，将在 collect 阶段自动处理解析顺序
     * !! 在 DbConfig 配置类中调用 Parser::exec() 方法时，将按照此处的解析顺序  执行解析
     */
    protected static $parsers = [
        //!! 执行解析时，将严格按照此顺序依次 调用解析器子类 执行解析
        "creation_sql", //调用 解析类 config\model\parser\CreationSql
        "column",       //调用 解析类 config\model\parser\Column
        "join",         //调用 解析类 config\model\parser\Join
        "getter",       //调用 解析类 config\model\parser\Getter
        "api",          //调用 解析类 config\model\parser\Api
        "other",        //调用 解析类 config\model\parser\Other
        
        //其他扩展的解析类，默认在最后执行
        //...
    ];



    /**
     * !! 子类必须定义
     */
    //此 数据模型(表) 参数解析类的 名称 foo_bar
    protected static $parser = "";

    /**
     * 数据缓存
     */
    //缓存 数据库配置类 DbConfig 实例
    protected $config = null;
    //缓存 当前解析的 数据库名 foo_bar
    protected $dbn = "";
    //缓存 当前解析的 数据模型(表) 名称 foo_bar
    protected $modk = "";
    //缓存 待解析的 数据模型(表) $modk 的配置参数
    protected $origin = [];
    //处理 过程中|之后 的 数据模型(表) 的 完整配置参数，作为返回值对外提供
    //!! 与 DbConfig::$exportModelConf[] 结构一致
    protected $context = [];
    
    //数据库驱动类全称
    protected $driver = "";
    //当前 数据模型(表) 类全称
    protected $model = "";

    /**
     * 要解析的 $origin 中 对应配置参数项目 的 默认数据结构
     * !! 子类如果需要，必须覆盖
     */
    protected $dftOption = [
        
    ];

    /**
     * 解析过程中的 数据，这些数据最终将被 写入 $this->context 
     * 通常指定了 此解析器将要修改 $this->context 中的 哪些数据
     * !! 与 DbConfig::$exportModelConf[] 结构一致
     * !! 子类根据需要，指定此数据
     */
    protected $temp = [];
    


    /**
     * 构造
     * !! 子类不要覆盖
     * !! 使用 Parser::exec() 方法，依次调用解析器，不要手动实例化
     * @param String $modk 数据模型(表)名称 foo_bar
     * @param Array $origin 初始的 数据模型(表) 待解析参数
     * @param Array $conf 已经由其他 解析器处理并生成的 (部分)数据模型配置参数
     * @param DbConfig $config 数据库配置类实例
     * @return void
     */
    final protected function __construct($modk, $origin=[], $conf=[], $config=null)
    {
        if (!Is::nemstr($modk) || !Is::nemarr($origin) || !$config instanceof DbConfig) {
            //参数异常
            throw new OrmException("unknown,解析数据模型配置参数时缺少必要参数", "orm/config");
            return null;
        }
        
        //如果当前的 解析类定义了 $dftOption 默认参数结构，则对传入的 $origin 进行填充处理，然后缓存到 $origin
        $this->origin = $this->fixOption($origin);

        //缓存
        $this->config = $config;
        $this->dbn = $config->dbn;
        $this->modk = $modk;
        //读取 数据库 驱动类全称  已经过检查，一定存在此类
        $this->driver = $config->ctx["driver"];
        //获取 当前数据模型(表) 类全称，已经检查过，一定存在
        $this->model = $this->origin["class"];

        if (Is::nemarr($conf)) {
            //如果传入的 $conf 已经存在内容，表示已有其他 Parser 解析器运行过，直接缓存 处理结果
            $this->context = $conf;
        } else {
            //传入的 $conf 为空，表示这是首次调用的 Parser  准备 标准输出结构  与 $config->exportModelConf[] 一致
            $this->context = $config->stdExport(
                "exportModelConf",
                //使用 $origin 作为源数据，模型元数据可以直接被继承
                $origin,
            );
            //清空 部分参数项目内容，这些项目将由 各自对应的 Parser 自动生成并填充
            $this->context["creation"] = [];
            $this->context["default"] = [];
            $this->context["join"] = [];
            $this->context["columns"] = [];
            $this->context["column"] = [];
        }

        //!! forDev
        //var_dump($this->modk);
        //var_dump($this->origin);
        //var_dump($this->driver);
        //var_dump($this->model);
        //var_dump($this->context);
    }



    /**
     * 解析入口
     * 解析 $this->origin 参数，将生成的最终参数 写入 $this->context 并返回
     * !! 子类必须实现
     * @return Array $this->context 解析得到的 此数据模型(表)参数 []
     */
    abstract public function parse();

    /**
     * 在所有 通用的|自定义的 Parser 解析器执行完之后，最后执行的操作
     * !! 子类不要覆盖
     * @param Array $ctx Parser 序列执行完后，得到的 数据模型参数 context
     * @return Array 处理后的 数据模型参数 context 将作为最终参数
     */
    final protected static function afterParse($ctx=[])
    {
        if (!Is::nemaso($ctx)) return $ctx;

        // 0    处理各字段的参数
        $colcs = $ctx["column"] ?? [];
        //收集 有 default 默认值参数字段的 字段名
        $dfts = [];
        foreach ($colcs as $colk => $colc) {
            // 0.1  根据各字段的 isXxxx|hasXxxx 参数，决定是否保留 对应的特殊字段类型参数
            foreach ($colc as $k => $v) {
                if (!Is::nemstr($k)) continue;

                // isFooBar == false --> 去除 foo_bar 参数
                if (substr($k, 0,2)==="is" && is_bool($v) && $v===false) {
                    //对应的 特殊字段参数 键名
                    $ck = Str::snake(substr($k, 2), "_");
                    unset($ctx["column"][$colk][$ck]);
                    continue;
                }

                // hasFooBar == false --> 去除 foo_bar 参数
                if (substr($k, 0,3)==="has" && is_bool($v) && $v===false) {
                    //对应的 特殊字段参数 键名
                    $ck = Str::snake(substr($k, 3), "_");
                    unset($ctx["column"][$colk][$ck]);
                    continue;
                }
            }

            // 0.2  根据 各字段的 default 默认值参数，生成 hasDefault 标记
            $dft = $colc["default"] ?? null;
            $hasDft = Is::nemaso($dft) && isset($dft["value"]) && (
                !is_null($dft["value"]) || (
                    isset($dft["params"]) && isset($dft["params"]["getter"]) && !is_null($dft["params"]["getter"])
                )
            );
            //创建 hasDefault 标记
            $ctx["column"][$colk]["hasDefault"] = $hasDft;
            //处理 when
            if ($hasDft && isset($dft["params"])) {
                $dftp = $dft["params"];
                if (isset($dftp["getter"]) && !is_null($dftp["getter"])) {
                    if (!isset($dftp["when"]) || !Is::nemidx($dftp["when"])) {
                        //!! when 参数不存在 则使用默认值填充
                        $dft["params"]["when"] = ["insert"];
                        //写回 ctx
                        $ctx["column"][$colk]["default"] = $dft;
                    }
                }
            }
            if ($hasDft) {
                //如果存在默认值，则收集 字段名
                $dfts[] = $colk;
            } else {
                //没有默认值 则 unset 字段参数中的 default
                unset($ctx["column"][$colk]["default"]);
            }
        }

        //将收集到的 有 default 默认值参数字段的字段名，写入 ctx["special"]
        $ctx["special"]["default"] = $dfts;

        //返回处理后的 $ctx 将作为最终的 此数据模型的 参数
        return $ctx;
    }



    /**
     * 工具方法
     */

    /**
     * 使用 $dftOption 默认参数结构 填充传入的 此数据模型的 待解析参数
     * !! 子类如果需要，必须覆盖此方法
     * @param Array $conf 传入的 数据模型(表) 的 待解析的配置参数
     * @return Array 处理后的 模型待解析参数，将被缓存到 $this->origin
     */
    protected function fixOption($conf=[])
    {
        //!! 有需要的 解析器子类，自行实现处理逻辑
        //...

        return $conf;
    }

    /**
     * 写入 context
     * !! 子类不要覆盖
     * @param Array $conf 要写入 context 中的参数
     * @param Bool $replaceIndexedArray 传入 Arr::extend() 方法的最后一个参数，决定 indexed 数组的合并方式
     *              false 表示去重合并，true 表示直接替换
     * @return Bool
     */
    final protected function setCtx($conf=[], $replaceIndexedArray=false)
    {
        if (Is::nemarr($conf)) {
            $this->context = Arr::extend($this->context, $conf, $replaceIndexedArray);
            return true;
        }
        return false;
    }

    /**
     * 解析过程中 写入 临时数据 $this->temp
     * !! 子类根据需要，可以覆盖
     * @param Array $conf 要写入 $temp 中的参数
     * @param Bool $replaceIndexedArray 传入 Arr::extend() 方法的最后一个参数，决定 indexed 数组的合并方式
     *              false 表示去重合并，true 表示直接替换
     * @return Bool
     */
    protected function setTemp($conf=[], $replaceIndexedArray=false)
    {
        if (Is::nemarr($conf)) {
            $this->temp = Arr::extend($this->temp, $conf, $replaceIndexedArray);
            return true;
        }
        return false;
    }

    /**
     * 输出此 Parser 的受保护参数内容，用于向 下级解析类传递必要参数
     * @return Object 包含所有必要参数的 对象
     */
    public function params()
    {
        return (object)[
            "dbn"       => $this->dbn,
            "modk"      => $this->modk,
            "model"     => $this->model,
            "driver"    => $this->driver,
            "origin"    => $this->origin,
            "temp"      => $this->temp,
        ];
    }
    
    /**
     * 对 数据模型中所有字段 执行 each 操作，调用自定义方法，如果返回有效 [] 将被自动合并到 $this->temp[]
     * @param \Closure $closure 回调方法
     *      @param String $colk 字段名 foo_bar
     *      @param Mixed $colv 用来循环的 origin[] 中某个包含所有字段名的 关联数组的 当前字段键名对应的键值
     *      @return Array|Bool 
     *           返回 非空关联数组，则自动调用 $this->setTemp 修改 $temp 数据
     *           返回 true 则 continue， false 则 break
     * @param Array $columns 用于循环的 包含所有字段名的 associate 数组，默认是 origin["columns"] 字段元数据数组
     * @return $this
     */
    protected function eachColumn($closure, $columns="columns")
    {
        if (!$closure instanceof \Closure) return $this;
        //将当前 $this 绑定到 $closure，传入 __CLASS__ 确保可以放当前类的 受保护属性
        $closure = $closure->bindTo($this, __CLASS__);

        //所有字段名数组 来自 $this->origin["columns"] 的键名数组，因为所有字段必须定义元数据
        $colks = $this->origin[$columns] ?? null;
        if (!Is::nemarr($colks) || !Is::associate($colks)) return $this;

        foreach ($colks as $colk => $colv) {
            /**
             * 执行 回调
             */
            $rtn = $closure($colk, $colv);
            if ($rtn===true) continue;
            if ($rtn===false) break;
            if (Is::nemarr($rtn) && Is::associate($rtn)) {
                //自动调用 $this->setTemp
                $this->setTemp($rtn);
            }
        }
        return $this;
    }

    /**
     * 获取当前数据模型 origin 参数中，针对 $colk 字段的 特殊字段类型参数
     * @param String $colk 字段名 foo_bar
     * @param String $type 特殊字段类型 默认 null 返回所有针对此字段的 特殊字段类型参数
     * @return Mixed|null
     *      $type === null 则返回：
     *      [
     *          # switch 参数是 indexed 数组，且包含了 $colk，表示字段 $colk 的 switch 参数使用默认参数
     *          "switch"        => "__default__",
     * 
     *          # associate 形式的 参数
     *          "json"          => [
     *              "type"      => "indexed",
     *              "default"   => [1,2,3]
     *          ],
     *         
     *          # 字符串 形式的 参数
     *          "json"          => "associate",
     * 
     *          # 任意形式的 参数
     *          "类型名"        => "__default__" | String | Array | Boolean | Number | ... ,
     *          
     *          ...
     *      ]
     * 
     *      $type === 类型名 则返回：任意形式的 参数："__default__" | String | Array | Boolean | Number | ... 
     * 
     *      如果 没有 针对 $colk 字段的 $type 类型参数，则返回 null
     */
    public function getColumnTypeConf($colk, $type=null)
    {
        $origin = $this->origin["column"] ?? [];

        //指定了要查找参数的 特殊字段类型
        if (Is::nemstr($type)) {
            $origin = $origin[$type] ?? [];
            //模型配置参数中 不包含 $type 特殊类型参数，返回 null
            if (!Is::nemarr($origin)) return null;
            //$type 特殊类型参数是 indexed 数组，且否包含 $colk 字段名，返回 "__default__"
            if (Is::indexed($origin) && in_array($colk, $origin)) return "__default__";
            //$type 特殊类型参数是 associate 数组，且包含 $colk 字段，则返回参数定义
            if (isset($origin[$colk])) return $origin[$colk];
            //$type 特殊类型参数 不包含 $colk 字段，返回 null
            return null;
        }

        //未指定特殊类型，则返回全部
        $rtn = [];
        foreach ($origin as $tp => $conf) {
            $c = $this->getColumnTypeConf($colk, $tp);
            if (!is_null($c)) continue;
            $rtn[$tp] = $c;
        }
        if (!Is::nemarr($rtn)) return null;
        return $rtn;
    }



    /**
     * 外部调用入口
     * 按 Parser::$parsers[] 中定义的顺序，依次实例化 解析器子类，并解析
     * 如果 传入了 自定义解析器类[]，则根据传入的 解析器类[] 顺序，依次实例化，并解析
     * 返回解析后的 当前数据模型(表) 的最终配置参数
     * @param String $modk 数据模型(表)名称 foo_bar
     * @param Array $origin 初始的 数据模型(表) 待解析参数
     * @param DbConfig $config 数据库配置类实例
     * @param Array $parsers 自定义解析器[]  不指定则使用 Parser::$parsers[] 
     * @return Array 解析后的 当前数据模型(表) 的最终配置参数
     */
    final public static function exec($modk, $origin=[], $config=null, $parsers=[])
    {
        if (!Is::nemarr($parsers)) $parsers = Parser::$parsers;
        if (!Is::nemarr($parsers) || !Is::indexed($parsers)) return [];

        //准备 解析结果
        $ctx = [];

        //按顺序 依次调用 $parsers[] 中 指定的 解析器类
        foreach ($parsers as $parser) {
            //查询 类
            $pcls = Parser::support($parser);

            //跳过不存在的 Parser 类
            if ($pcls===false || !class_exists($pcls)) continue;

            //!! 调用 Parser 解析类
            $p = new $pcls($modk, $origin, $ctx, $config);
            //执行 parse 方法，并将解析结果保存到 $ctx
            $ctx = $p->parse();
            //释放
            unset($p);
        }

        //调用基类 Parser::afterParse();
        $ctx = Parser::afterParse($ctx);

        //返回最终的 配置参数
        return $ctx;
    }

}