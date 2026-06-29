<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 Orm 模块支持的 字段类型  基类  抽象类
 * 所有 框架默认的  以及  自定义扩展的  字段类型 都必须继承自此类
 * 
 * 
 * Orm 模块 默认支持下列字段类型：（在不同类型的数据库中会被转换为对应的 字段类型）
 * 在数据库配置文件中，定义字段的 creation-sql 时，应使用这些类型定义写法（默认采用 Mysql语法）
 * 
 *      类型定义(支持的写法)       Mysql                       Sqlite
 * 
 * 通常的 字段类型
 * 
 * !!   字符串类型：() 内为 字符长度
 *      varchar                     VARCHAR(255)                TEXT
 *      varchar(50)                 VARCHAR(50)                 TEXT
 *      char                        CHAR(255)                   TEXT
 *      char(10)                    CHAR(10)                    TEXT
 *      text                        TEXT                        TEXT
 * 
 * !!   整数类型：
 *      integer                     INT                         INTEGER             -2147483648 ~ 2147483647
 *      bigint                      BIGINT                      INTEGER             -9223372036854775808 ~ 9223372036854775807
 *      tinyint                     TINYINT                     INTEGER             -128~127
 * 
 * !!   浮点类型：
 *      float                       FLOAT                       REAL
 * 
 * 
 * 针对一些通用的 特殊类型
 * 
 * !!   uuid 类型
 *      uuid                        VARCHAR(36)                 TEXT
 * 
 * !!   json 类型数据：[] | {}
 * !!       系统默认以 字符串格式存储（兼容低版本数据库），由 Orm 系统执行 数据转换，内容检索 等后续操作
 *      json                        VARCHAR(2000)               TEXT
 * 
 * !!   switch 类型数据： 0 = false   1 = true
 *      switch                      TINYINT                     INTEGER
 * 
 * !!   datetime 类型数据：
 * !!       系统默认以 timestamp 秒级时间戳  存储 ，由 Orm 系统执行 数据转换
 *      datetime                    BIGINT                      INTEGER
 * 
 * !!   datetime_queue 类型数据
 * !!       系统自动转为 json[] 形式存储，由 Orm 系统执行 数据转换 等后续操作
 *      datetime_queue              VARCHAR(255)                TEXT
 *      
 * !!   money 类型数据：
 * !!       系统默认以 分 形式，保存 整数值
 *      money                       BIGINT                      INTEGER
 *      
 * 
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * 
 * !! 需要在 自定义类型类的 whenCollect() 方法中，适配所有支持的 数据库类型
 * 
 * 
 * Types 字段类型的使用：
 *  0   数据库参数解析阶段：
 *          Types::getDefineType(creation-sql)          --> [type=>"Orm 支持的字段类型", precision=>"(精度字符)"]
 *          Types::get(字段类型)::getTypesArr()         --> [def=>"",db=>"",js=>"",php=>""]
 *  1   数据记录实例处理阶段：
 *          $record->col_name   读取字段值，将自动调用 $type->from() 执行数据类型转换
 *          $record->save()     写入数据库之前，将自动调用 $type->to() 执行数据类型转换
 * 
 */

namespace Spf\module\orm;

use Spf\module\Orm;
use Spf\module\orm\Driver;
use Spf\module\orm\Db;
use Spf\module\orm\Model;
use Spf\module\orm\Record;
use Spf\module\orm\config\DbConfig;
use Spf\module\orm\types\Native;
use Spf\module\orm\config\model\Parser;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

use Spf\traits\ExpandableResource;

abstract class Types 
{
    //引用  可扩展底层资源类  特征
    use ExpandableResource {
        ExpandableResource::support as exSupport;
    }
    //!! trait 中要求的，子类不要覆盖
    protected static $exresName = "types";
    protected static $exresClassPath = [
        "module/orm",
        "db"
    ];
    public static $isCollected = false;
    
    /**
     * 当某个 字段类型子类被 collect 收集时，创建 所有支持类型的数据库的 类型映射
     * !! trait 中要求的，子类根据需要覆盖
     * @return Bool
     */
    protected static function whenCollect()
    {
        //将此字段类型的 语法正则，收集到 Types::$patterns 中
        $type = static::$type;
        Types::$patterns[$type] = static::$pattern;

        //更新 Types::$types[]
        Types::$types = array_merge([], array_keys(Types::$patterns));
        
        //依次将 $map 中定义的 类型映射，写入 Types::$maps 中
        $rtn = true;
        foreach (static::$map as $driver => $conv) {
            $rtn = $rtn && static::extendMapper($driver, $conv);
        }
        return $rtn;
    }

    /**
     * forDev：外部查看 $collection[]
     * !! 如果需要，引用的资源类可以覆盖，资源子类不要覆盖
     * @return Array
     */
    public static function all()
    {
        $collection = self::$collection;
        //将 原生类型 添加到 $collection 中
        $ncls = Cls::find("module/orm/types/Native", "Spf");
        foreach (Native::$nativeTypes as $type) {
            if (isset($collection[$type])) continue;
            $collection[$type] = $ncls;
        }
        return $collection;
    }
    
    /**
     * 判断 当前的资源类 是否包含传入的 子类
     * !! 覆盖 trait，子类不要覆盖，仅通过 Types 基类调用
     * @param String $clsk 要检查是否存在的 类型名 foo_bar
     * @return false|String 存在则返回 类全称，不存在 返回 false
     * !!       原生字段类型 将返回 Types 基类
     */
    public static function support($clsk)
    {
        //调用 trait 中的方法
        $clsp = static::exSupport($clsk);
        if (class_exists($clsp)) return $clsp;

        //Types 类新增逻辑
        $clsk = Str::snake($clsk, "_");
        //如果是 原生类型，返回 Native 类
        if (Native::includes($clsk)===true) return Cls::find("module/orm/types/Native", "Spf");
        
        return false;
    }



    /**
     * !! 子类不要覆盖
     */
    /**
     * 收集了所有 字段类型
     * !! 只能通过 Types 基类访问
     */
    protected static $types = [
        //"json", ...
    ];
    /**
     * 收集了所有 字段类型的 creation-sql 语法正则
     * !! 只能通过 Types 基类访问
     */
    protected static $patterns = [
        //"json" => "/^json(\([0-9]+\))?\s+",
        //...
    ];
    /**
     * 收集了所有 数据库类型的 类型映射表
     * !! 只能通过 Types 基类访问
     * 外部使用：
     *      Types::map("json", "mysql")     --> LONGTEXT
     *      
     */
    protected static $maps = [
        //框架默认支持的 数据库类型
        "mysql" => [
            //"json"          => "LONGTEXT",
            //...
        ],

        "sqlite" => [
            //"json"          => "TEXT",
            //...
        ],
        
        //扩展的 数据库类型  自动填充
        //...
    ];
        
    /**
     * 不同类型数据库的 类型映射操作
     * !! 子类不要覆盖，仅通过 Types 基类调用
     * !! 由 Types 子类 或 Driver 子类 在 whenCollect() 方法中调用：
     *      Types 子类中调用：      types\Json::extendMapper("mysql", "LONGTEXT")
     *      Driver 子类中调用：     Types::extendMapper("mysql", [ "json" => "LONGTEXT", ... ])
     * @param String $driver 数据库驱动名 mysql | sqlite | ...
     * @param String|Array $conv 
     *      String      --> 此字段类型 在 $driver 数据库类型下的 映射字段类型
     *      Array       --> 同时指定多个类型 在 $driver 数据库类型下的 映射
     * @return Bool
     */
    final protected static function extendMapper($driver, $conv) 
    {
        $extend = [
            $driver => []
        ];
        if (Is::nemstr($conv)) {
            $type = static::$type;
            $extend[$driver][$type] = $conv;
        } else if (Is::nemarr($conv)) {
            $extend[$driver] = $conv;
        }
        
        if (!Is::nemarr($extend[$driver])) return true;

        //填充到 $maps
        Types::$maps = Arr::extend(Types::$maps, $extend);
        return true;
    }

    /**
     * 获取指定 $driver 数据库类型下的 $type 字段类型 的 映射类型
     * !! 子类不要覆盖，仅通过 Types 基类调用
     * @param String $driver 传入使用的 数据库类型
     * @param String $type 传入的 字段类型，默认 null 则使用当前字段类型
     * @return String|null
     */
    final public static function getMappingType($driver, $type=null)
    {
        //必须是支持的 数据库类型
        if (!Is::nemstr($driver) || Driver::support($driver)===false) return null;
        //默认使用 当前字段类型
        if (!Is::nemstr($type)) $type = static::$type;
        if (!Is::nemstr($type)) return null;
        if (Types::support($type)===false) return null;
        //foo_bar
        $driver = Str::snake($driver, "_");
        $type = Str::snake($type, "_");
        
        //此 $driver 对应的 类型映射
        $map = Types::$maps[$driver] ?? null;
        if (!Is::nemarr($map) || !Is::associate($map)) return null;
        $maptp = $map[$type] ?? null;
        if (!Is::nemstr($maptp)) return null;
        return $maptp;
    }

    /**
     * 传入 creation-sql (字段类型声明 一定在 语句开头) 使用收集到的 语法正则 进行匹配
     * 返回 匹配到的 字段类型，以及精度参数
     * !! 子类不要覆盖，仅通过 Types 基类调用
     * @param String $sql 有效的 creation-sql
     * @return Array|null 未匹配到 返回 null
     * 例如：char(10) NOT NULL DEFAULT 'abcdefghij' 将解析得到：
     *  [
     *      "type" => "char",
     *      "precision" => "(10)",
     *  ]
     */
    final public static function getDefineType($sql)
    {
        //如果 sql 中不含任何 空格，则在末尾增加一个空格，因为 所有类型 pattern 都包含一个 \s+ 结尾
        if (strpos($sql, " ")===false) $sql .= " ";

        //匹配字段类型
        foreach (Types::$patterns as $dtp => $pattern) {
            if (Is::indexed($pattern)) {
                foreach ($pattern as $pti) {
                    $mt = preg_match($pti, $sql, $matches);
                    if ($mt!==1) continue;
        
                    //匹配到 字段类型
                    //精度
                    $precision = (isset($matches[1]) && Is::nemstr($matches[1])) ? $matches[1] : "";
                    return [
                        "type" => $dtp,
                        "precision" => $precision,
                    ];
                    break;
                }
            } else if (Is::nemstr($pattern)) {
                $mt = preg_match($pattern, $sql, $matches);
                if ($mt!==1) continue;
    
                //匹配到 字段类型
                //精度
                $precision = (isset($matches[1]) && Is::nemstr($matches[1])) ? $matches[1] : "";
                return [
                    "type" => $dtp,
                    "precision" => $precision,
                ];
                break;
            } 
        }

        //未匹配到字段类型
        return null;
    }

    /**
     * 判断传入的 字段类型，是否特殊原生类型
     * !! 子类不要覆盖，仅通过 Types 基类调用
     * @param String $type 要检查的 字段类型 foo_bar
     * @return Bool
     */
    final public static function isNative($type)
    {
        return Native::includes($type);
    }

    /**
     * 获取 字段类型子类 类全称，用于链式执行 类型方法
     * 如果传入 原生类型，则返回 Native 类，并将类内部 Native::$type|$pattern|$map 指向此原生类型
     * !! 子类不要覆盖，仅通过 Types 基类调用
     * @param String $type 类型名 foo_bar
     * @return String|null 类型子类全称，或 Types 基类全称
     */
    final public static function get($type)
    {
        $tcls = Types::support($type);
        if ($tcls===false) return null;

        if ($tcls::$isNative===true) {
            //传入的是 原生类型 $tcls === Spf\module\orm\types\Native
            $type = Str::snake($type, "_");
            //将 Native::$type|$pattern|$map 指向 传入的类型
            $tcls::$type = $type;
            $tcls::$pattern = $tcls::$nativePatterns[$type];
            $map = [];
            foreach ($tcls::$nativeMaps as $driver => $maps) {
                if (!Is::nemarr($maps) || !isset($maps[$type])) continue;
                $map[$driver] = $maps[$type];
            }
            $tcls::$map = $map;
        } else {
            //恢复 Native::$type|$pattern|$map
            Native::$type = "";
            Native::$pattern = "";
            Native::$map = [];
        }

        //返回 类全称
        return $tcls;
    }

    /**
     * __callStatic 
     */
    public static function __callStatic($key, $args)
    {
        //$key 必须是 FooBar 形式，转为 foo_bar 形式的 类型名
        $type = Str::snake($key, "_");
        //获取 类型子类
        $cls = Types::get($type);
        if (Is::nemstr($cls) && class_exists($cls)) {
            //获取到有效的 类型子类名，返回，等待后续调用
            return $cls;
        }

        return null;
    }



    /**
     * !! 子类必须指定
     */
    //此 字段类型的 名称 foo_bar
    protected static $type = "";
    //在 creation-sql 中 此字段类型的 语法正则
    protected static $pattern = "";
    //定义 此字段类型 在 不同数据库中的 类型映射
    protected static $map = [
        /*
        "mysql" => "VARCHAR(255)",
        "sqlite" => "TEXT",
        ...
        */
    ];
    /**
     * 是否原生字段类型
     * 原生字段类型只有：varchar|char|text|integer|bigint|tinyint|float
     * !! 仅在 types/Native 子类中，此参数为 true
     */
    protected static $isNative = false;
    /**
     * 特殊字段类型参数，在数据模型配置参数 column 项下的 键名
     * 不指定，则使用 static::$type 作为键名
     * !! 可以有多个键名，依次从数据模型配置参数 column 项下查找
     */
    protected static $optProps = [];
    /**
     * 如果是特殊字段类型，在此指定 在数据库配置文件中，此类型字段的 默认参数形式
     * 例如：json 类型，默认的 字段参数形式为：
     *  [
     *      "type" => "indexed|associate",
     *  ]
     */
    protected static $optDefine = [];
    //当前特殊字段类型下，可选的 子类型，例如：json 类型的字段，可选的子类型包括 associate | indexed
    protected static $optDefineTypes = [
        //!! 特殊类型子类必须定义，默认子类型排在首位
    ];
    
    /**
     * 字段默认值参数，默认结构
     * !! 子类不要覆盖，所有字段类型，共用相同的 默认值参数结构
     * !! 字段默认值参数，通常在字段配置参数中的 default 项下
     */
    protected static $optDefault = [
        //配置的 默认值
        "value" => null,
        //默认值参数
        "params" => [
            //creation-sql 解析阶段，需要生成 符合当前 driver 的 默认值定义字符串
            "insql" => "",
            //如果默认值是 getter 方法，此处指定 [class, method]
            "getter" => null,
            //指定默认值生成的 时机，默认 ["insert"]
            //!! 在 static::$optDefaultWhens[] 中定义了所有支持的 when 参数
            "when" => null,
        ],
    ];
    //Orm 支持的 字段默认值生成时机
    protected static $optDefaultWhens = [
        "insert", "update", "select", "logic_delete", "event",
    ];
    //当字段默认值由 getter 函数生成时，value 中填充的 标记
    protected static $optDefaultGetterSign = "__getter__";

    /**
     * 缓存当前字段类型中 colk 字段的 配置参数解析结果
     * !! 在 数据模型配置参数解析阶段，可能需要多次执行 某个字段的参数解析，只需要解析一次即可
     * !! 此缓存数据在 static::beforeParse() 方法中生成
     * !! 子类不要覆盖
     */
    protected static $optParsedCache = [
        /*
        !! 必须按数据库区分缓存
        # 针对某个数据库
        "dbn" => [
            # 此数据库的 driver 类型
            "driver" => "mysql | sqlite | ...",

            !! 需要按 model 数据模型继续区分
            # 针对某个数据模型
            "modk" => [
                # 数据库参数解析阶段，此数据模型的参数解析器 $parser->params() 返回的 依赖项
                "params" => (object)[...],

                # 缓存此数据模型下 各字段的 参数解析结果
                "colk" => [
                    "type"      => "字段类型 == static::$type",
                    "maptype"   => "当前 driver 下的 映射数据类型",
                    "define"    => [] | null,   # 来自 $parser->getColumnTypeConf(colk)
                    "parsed"    => [
                        # 解析后的 colk 字段的 配置参数
                        !! 与 static::$optDefine[] 结构一致
                        ...

                        # 默认值参数解析结果
                        "default" => [
                            !! 与 Types::$optDefault[] 结构一致
                        ],
                    ],
                ],
                ... 更多 字段
            ],
            ... 更多 数据模型
        ],
        ... 更多 数据库
        */
    ];



    /**
     * 字段参数解析 相关方法
     * !! 仅在 数据库参数解析阶段，由 数据模型参数解析器 Parser 调用
     */
    /**
     * 处理传入的 $colk, $parser 参数，得到通用的 参数形式，作为 parseColumnXxxx 系列方法的统一入参
     * !! 字段参数的解析结果 将被缓存到 Types::$optParsedCache[dbn][modk][colk] 中
     * !! 子类不要覆盖
     * @param String $colk 当前字段名 foo_bar
     * @param Parser $parser 此方法的调用者 数据模型参数解析器 Parser 子类实例，
     *                       可通过 $parser->params() 获取必要的依赖参数
     *                       可通过 $parser->getColumnTypeConf($colk) 获取模型配置参数中，针对此字段的所有特殊类型参数
     * @param Array $extra 还可以传入 针对此字段的额外参数，通常包含：
     *                     extra["default"]     在 creation-sql 中可能存在的 default 默认值定义字符串
     * @return Object|null 返回数据：
     *  (object)[
     *      "type" => 当前类型名 foo_bar,
     *      "maptype" => 当前类型 针对 当前 driver 的 映射类型
     *      "driver" => 驱动名 foo_bar,
     *      "params" => $parser->params() 返回的对象，包含了所有必须的依赖
     *      "define" => $parser->getColumnTypeConf() 返回的 []
     *      "parsed" => [
     *          # 与 static::$optDefine[] 定义结构一致的 参数数据
     *          ...
     *          "default" => [
     *              # 与 static::$optDefault[] 定义结构一致的 默认值参数数据
     *          ],
     *      ],
     *  ]
     */
    final public static function beforeParse($colk, $parser=null, $extra=[])
    {
        //当前类型
        $type = static::$type;
        if (!Is::nemstr($type)) return null;
        //foo_bar
        $type = Str::snake($type, "_");

        //colk 必须
        if (!Is::nemstr($colk)) return null;

        //parser 必须
        if (empty($parser) || !$parser instanceof Parser) return null;
        $params = $parser->params();
        $driver = $params->driver::driver();
        $dbn = $params->dbn;
        $modk = $params->modk;

        //!! 首先检查缓存
        $cache = Types::$optParsedCache;
        if (isset($cache[$dbn])) {
            $cache = $cache[$dbn];
            if (isset($cache[$modk])) {
                $cache = $cache[$modk];
                if (isset($cache[$colk])) {
                    //找到缓存，直接返回 缓存数据
                    $rtn = Arr::extend([], $cache[$colk], [
                        "driver" => $driver,
                        "params" => $params
                    ]);
                    return (object)$rtn;
                }
            }
        }

        //开始解析，并缓存 此字段的 参数数据
        $rtn = [
            $dbn => [
                "driver" => $driver,
                $modk => [
                    "params" => $params,
                    $colk => [
                        "type" => $type,
                        "maptype" => "",
                        "define" => [],
                        "parsed" => []
                    ],
                ],
            ],
        ];
        
        //获取 $driver 对应的 映射类型
        $maptp = static::getMappingType($driver, $type);
        if (!Is::nemstr($maptp)) return null;
        //去除 映射类型中 可能存在的 () 精度字符
        $maptp = trim(explode("(", $maptp)[0]);
        $rtn[$dbn][$modk][$colk]["maptype"] = $maptp;

        //处理 数据模型中 此字段的配置参数
        //使用 static::$optDefine[] 默认字段参数结构 填充，处理生成字段最终参数
        $fixc = static::fixColumnDefine($colk, $parser, $extra);

        //将已经解析得到的 特殊字段类型参数 添加到 extra
        $extra["define"] = $fixc;
        //!! extra 中还可能包含 extra["default"] 即 creation-sql 中可能包含的默认值定义字符串
        //解析默认值参数
        $dftc = static::fixColumnDefault($colk, $parser, $extra);
        //如果解析得到了有效的默认值参数，存入 $fixc["default"] 中
        $fixc["default"] = Is::nemarr($dftc) ? $dftc : Arr::copy(Types::$optDefault);
        //生成字段最终参数
        $rtn[$dbn][$modk][$colk]["parsed"] = $fixc;
        //写入缓存
        Types::$optParsedCache = Arr::extend(Types::$optParsedCache, $rtn);

        //返回
        return (object)[
            "type" => $type,
            "maptype" => $maptp,
            "driver" => $driver,
            "params" => $params,
            "define" => $parser->getColumnTypeConf($colk),
            "parsed" => $fixc,
        ];
    }

    /**
     * 如果是 特殊字段类型，且存在 $optDefine
     * 必须 定义一个合并 字段配置参数 与 $optDefine 默认参数的 方法
     * !! 可 通过 Types 基类链式调用  或  类型子类直接调用
     * !! 非原生字段类型，必须定义
     * !! 如果不是必须的，类型子类不要覆盖
     * @return Array 返回的参数结构与 static::$optDefine[] 一致
     */
    protected static function fixColumnDefine($colk, $parser=null, $extra=[])
    {
        //从此字段的配置参数中，查找针对此字段类型的参数
        $props = static::$optProps;
        if (Is::nemstr($props)) $props = [$props];
        //默认使用 static::$type 作为此字段类型参数 在 模型参数 column[] 中的键名
        if (!Is::nemidx($props)) $props = [static::$type];

        //使用默认的字段类型配置参数作为基础
        $fixc = Arr::copy(static::$optDefine);

        //如果定义了 static::$optDefineTypes 此字段类型的 子类型数组，则挑选首位作为 字段参数中的 type 子类型参数值
        $dftps = static::$optDefineTypes;
        if (Is::nemidx($dftps)) {
            if (!Is::nemstr($fixc["type"]) || !in_array($fixc["type"], $dftps)) {
                $fixc["type"] = $dftps[0];
            }
        }

        foreach ($props as $prop) {
            //调用解析器 $parser->getColumnTypeConf() 方法 从模型参数 column[] 中查找 $prop 项目
            $defc = $parser->getColumnTypeConf($colk, $prop);

            //返回 null  未获取到此字段的 针对 $prop 键名的 配置参数
            if (is_null($defc)) continue;

            //返回 __default__  直接使用 static::$optDefine[] 默认参数
            if ($defc==="__default__") {
                break;
            }

            //返回 String  且 参数字符串 在 子类型数组中，则作为 字段参数中 type 子类型 参数项的值
            if (Is::nemstr($defc) && in_array($defc, $dftps)) {
                $fixc = Arr::extend($fixc, [
                    "type" => $defc
                ]);
                break;
            }

            //返回 Array 且至少包含一个 static::$optDefine 中定义的键名
            if (Is::nemaso($defc)) {
                $cks = array_merge([], array_keys($defc));
                $dks = array_merge([], array_keys(static::$optDefine));
                $dif = array_diff($cks, $dks);
                if (count($cks) > count($dif)) {
                    //表示这是 需要覆盖到默认参数中的 完整的 字段特殊类型参数
                    $fixc = Arr::extend($fixc, $defc);
                    break;
                }
            }

            //其他类型的 定义参数，一律作为默认值
            $fixc = Arr::extend($fixc, [
                "default" => [
                    "value" => $defc
                ]
            ]);
        }

        return $fixc;
    }

    /**
     * 所有字段类型通用的 默认值参数处理
     * 使用 Types::$optDefault 默认结构填充，处理 value|getter|when 生成 insql 
     * !! 可 通过 Types 基类链式调用  或  类型子类直接调用
     * !! 如果不是必须的，类型子类不要覆盖
     * @param Array $extra 额外参数：
     *              $extra["define"]    已处理的 字段特殊类型参数
     * @return Array 返回的参数结构与 static::$optDefault[] 一致
     */
    protected static function fixColumnDefault($colk, $parser=null, $extra=[])
    {
        //已处理的 模型配置参数中 此字段的定义参数
        $defc = $extra["define"] ?? [];
        //可能存在的 字段定义参数中的 默认值定义
        $defd = $defc["default"] ?? null;
        //可能传入的 creation-sql 中的 默认值定义字符串
        $sqld = $extra["default"] ?? null;
        //var_dump("---- $colk ----");
        //var_dump($defd);
        //var_dump($sqld);
        //配置参数中 默认值定义 一定 优先于 creation-sql 中的默认值定义
        if ((is_null($defd) || is_null($defd["value"])) && !is_null($sqld)) $defd = $sqld;
        //var_dump($defd);

        //返回值
        $rtn = Arr::copy(Types::$optDefault);
        
        if (Is::nemarr($defd) && Is::associate($defd) && isset($defd["value"])) {
            //默认值定义 为 associate Array 形式，且包含 value 键，则 合并
            $rtn = Arr::extend($rtn, $defd);
        } else {
            //其他形式的 默认值定义，一律作为 value
            $rtn["value"] = $defd;
        }

        //var_dump("---- $colk default ----");
        //var_dump($rtn);

        //解析 默认值 value
        $value = $rtn["value"];
        $gsign = static::$optDefaultGetterSign;

        //未定义默认值，直接返回 空默认值
        if (is_null($value)) return Arr::copy(Types::$optDefault);

        //使用 getter 默认值生成方法的 默认值定义
        if (Is::nemstr($value)) {
            $getter = static::isDefinedDefaultGetter($value);
            if ($getter!==false) {
                //写入 $rtn
                $rtn["value"] = $gsign;
                //!! 使用 getter 时，creation-sql 中不再定义默认值
                $rtn["params"]["insql"] = "";
                $rtn["params"]["getter"] = $getter;
            }
        }
        
        //如果未使用 getter 则 调用此字段类型的 parseDefaultValue() 方法，解析得到实际的 value 默认值
        if ($rtn["value"]!==$gsign) {
            //需要传入 此字段的特殊类型参数
            $dv = static::parseDefaultValue($value, $defc);
            if (is_null($dv)) {
                //无法解析，直接返回 空默认值
                return Arr::copy(Types::$optDefault);
            } else {
                //解析得到的默认值，写入 $rtn
                $rtn["value"] = $dv["value"];
                $rtn["params"]["insql"] = $dv["insql"];
            }
        }
        
        //!! 确保 默认值 参数有效
        if (!is_null($rtn["value"])) {
            //处理默认值生成时机
            $whn = $rtn["params"]["when"];
            if (!Is::nemarr($whn) || !Is::indexed($whn)) {
                //默认的生成时机：insert
                $rtn["params"]["when"] = ["insert"];
            } else {
                //支持的 when
                $whs = static::$optDefaultWhens;
                //无效的 when
                $dif = array_diff($whn, $whs);
                //必须是有效的 when 
                if (Is::nemarr($dif)) $whn = array_diff($whn, $dif);
                $rtn["params"]["when"] = !Is::nemarr($whn) ? ["insert"] : $whn;
            }
        } else {
            //无默认值
            $rtn["params"]["when"] = null;
        }

        return $rtn;
    }

    /**
     * 解析各种 字段类型参数的 parseColumnXxxx 系列方法
     * !! 子类必须实现
     * @param Object $p 由 beforeParse 方法生成的统一入参
     * @return Array|null 各解析方法返回各自的结果，如果解析失败，返回 null
     */
    /**
     * 解析字段的 完整类型数组
     * @return Array|null 不支持的 字段类型 返回 null 
     *  [
     *      "def" => "",
     *      "db" => "",
     *      "js" => "",
     *      "php" => "",
     *  ]
     */
    abstract public static function parseColumnTypesArr($p=null);
    /**
     * 解析此类型字段的 默认值
     * !! 子类如果需要，可以覆盖
     * @return Array|null 默认返回 $p->parsed["default"]
     *  [
     *      "value" => php 类型的 默认值,   # 如果默认值需要使用 getter 方法生成，此处为 "__getter__"
     *      "params" => [
     *          "insql" => 在 creation-sql 中的 默认值写法
     *          # 某些特殊类型(例如 datetime) 还需要额外的 默认值参数
     *          "getter" => [class, method],        # 生成默认值的方法
     *          "when" => [insert, update, ...],    # 什么时候生成默认值并填充
     *          # 其他参数...
     *      ],
     *  ]
     */
    public static function parseColumnDefault($p=null)
    {
        if (is_null($p)) return null;
        return $p->parsed["default"];
    }
    /**
     * 解析数据模型参数中 针对此字段的 所有特殊类型参数，返回结果将被合并到 字段最终参数中
     * !! 子类如果需要，可以覆盖
     * @return Array|null 返回解析结果，将被合并到 字段最终参数中，结构与 DbConfig::exportColumnConf[] 一致
     */
    public static function parseColumnConfig($p=null)
    {
        if (is_null($p)) return null;
        $type = static::$type;
        
        //isXxxx，例如 json 类型会生成 (bool)isJson 和 (array)json 两个参数
        $prop = "is".Str::camel($type, true);
        //类型参数
        $parsed = Arr::copy($p->parsed);
        //!! 去除其中的 default 参数
        unset($parsed["default"]);

        //写入最终参数中的 isFooBar 和 foo_bar 参数项
        return [
            $prop => true,
            $type => $parsed,
        ];
    }



    /**
     * 字段默认值相关
     */

    /**
     * 向外部输出 Types::$optDefaultWhens 可选的 when 参数
     * @return Array
     */
    final public static function avaliableDefaultWhens()
    {
        return Types::$optDefaultWhens;
    }

    /**
     * 向外部输出 Types::$optDefaultGetterSign
     * @return String
     */
    final public static function definedDefaultGetterSign()
    {
        return Types::$optDefaultGetterSign;
    }
    
    /**
     * 检查传入的 字符串 是否是定义的 时间默认值生成方法
     * !! 子类不要覆盖
     * @param String $m 方法名 foo_bar
     * @return Array|false 是生成方法 则返回 [ class, method ]  否则返回 false
     */
    final public static function isDefinedDefaultGetter($m)
    {
        if (!Is::nemstr($m)) return false;
        $m = Str::trimQuote($m);
        if (!Is::nemstr($m)) return false;
        //如果传入了数字字符串，直接返回 false
        if ((is_numeric($m) && ((int)$m==$m || (float)$m==$m))) return false;

        //检查是否存在 defaultFooBarGetter 方法
        $mn = "default".Str::camel($m, true)."Getter";
        //var_dump("--- in types ---- $mn ----");
        if (method_exists(static::class, $mn)) return [static::class, $mn];
        return false;
    }

    /**
     * 根据传入的 默认值定义，解析得到实际的默认值结果
     * !! 子类必须实现
     * @param Mixed $defv 传入的默认值定义，可以是 String|Number|Array|Bool
     * @param String|null $defc 此字段可能存在的 特殊类型定义参数，已被 fixColumnDefine() 方法处理过的
     * @return Array|null 如果无法解析，返回 null  否则返回：
     *  [
     *      "value" => php类型的 默认值,
     *      "insql" => "可以写入 creation-sql 的默认值字符串",
     *  ]
     */
    abstract protected static function parseDefaultValue($defv=null, $defc=null);

    /**
     * !! 特殊类型子类，可根据需要定义 默认值生成方法 defaultXxxxGetter 
     * 这些 默认值生成方法，将在 when 参数中定义的 生成时机下(例如：insert 创建新记录时)，由 数据模型类(或实例) 调用
     * 传入 此字段的参数，生成对应的 默认值数据，最终写入数据库
     * @param String $colk 字段名 foo_bar
     * @param Array $conf 字段的配置参数，与 DbConfig::exportColumnConf[] 结构一致
     * @return Mixed 根据字段的 $conf["type"]["php"] 类型，生成对应的 默认值
     */
    //public static function defaultFooBarGetter($colk, $conf=[])



    /**
     * 字段类型的 实例 属性 | 方法
     * !! 仅在 数据库参数解析完成后，在操作数据记录过程中 实例化并调用
     */
    //关联的 数据库的 实例
    protected $db = null;
    //关联的 数据模型(表) 名 foo_bar
    protected $modk = "";
    //此字段的 字段名  foo_bar
    protected $colk = "";

    /**
     * 字段类型实例构造
     * @param String $xpath 传入必须的 dbn/modk/colk 库名/表名/字段名 
     * @return void
     */
    public function __construct($xpath)
    {
        //!! Orm 模块必须已经实例化
        if (Orm::$isInsed!==true) return null;

        //!! 必须传入有效的  库名/表名/字段名
        if (!Is::nemstr($xpath) || strpos($xpath, "/")===false) return null;
        $xa = explode("/", $xpath);
        if (count($xa)<3) return null;
        $db = Orm::$current->db($xa[0]);
        if (!$db instanceof Db || $db->hasModel($xa[1])===false) return null;
        $modcls = $db->hasModel($xa[1]);
        if ($modcls::hasColumn($xa[2])===false) return null;
        
        //缓存依赖项
        $this->db = $db;
        $this->modk = Str::snake($xa[1], "_");
        $this->colk = Str::snake($xa[2], "_");
    }

    /**
     * 获取 当前字段的 完整配置参数
     * @param String $key 查找字段参数中的某个项目  或 xpath 默认 __type__ 返回此字段参数中 关于此特殊类型的 参数
     *                    如果传入 null 则返回此字段的 完整参数
     * @return Array $this->db->config->ctx("model/$modk/column/$colk/...")
     */
    protected function conf($key="__type__")
    {
        $kp = ["model", $this->modk, "column", $this->colk];
        if (!Is::nemstr($key)) return $this->db->config->ctx(implode("/", $kp));
        if ($key!=="__type__") {
            $kp[] = trim($key, "/");
            return $this->db->config->ctx(implode("/", $kp));
        }

        //!! 传入 __type__ 返回此字段参数中 关于此特殊类型的 完整参数
        //字段完整参数
        $colc = $this->db->config->ctx(implode("/", $kp));
        //在这些定义的 props 中查找参数项
        $props = static::$optProps;
        $tpc = null;
        foreach ($props as $prop) {
            if (isset($colc[$prop]) && Is::nemaso($colc[$prop])) {
                if (Is::nemaso(static::$optDefine)) {
                    return Arr::extend([], static::$optDefine, $colc[$prop]);
                }
                return $colc[$prop];
            }
        }
        
        return null;
    }

    /**
     * 获取当前字段的 特殊类型参数中的 某些项目
     * @param String $key 默认 null  返回完整的 类型参数
     * @return Mixed|null
     */
    protected function opt($key=null)
    {
        $tpc = $this->conf();
        if (!Is::nemstr($key)) return $tpc;
        return Arr::find($tpc, $key);
    }

    /**
     * 获取当前字段所在 driver 数据库驱动类型下的 全部 maps 类型映射
     * @return Array 完整的 字段类型映射[]
     */
    protected function getDriverMaps()
    {
        //当前阶段，数据驱动一定存在
        $driver = $this->db->config->ctx["driver"];
        $dk = Str::snake(Cls::name($driver), "_");
        return Types::$maps[$dk];
    }



    /**
     * !! 字段类型子类 必须实现的 实例工具方法
     * !! 仅在 数据库参数解析完成后，在操作数据记录过程中 调用
     * 从 select() 结果中创建符合各字段 php 类型的 记录集
     * 在 update() 之前，将 php 类型数据，转为可写入数据库的 数据
     */

    /**
     * 将 数据库中读取的数据转为 对应 php 类型的数据
     * @param Mixed $val 数据库中保存的数据
     * @return Mixed 转为对应的 php 类型的数据
     */
    abstract public function from($val=null);

    /**
     * 将 在写入数据库之前，将 php 数据 转为 对应的 数据库字段保存类型的数据
     * @param Mixed $val php 数据
     * @return Mixed 对应的 数据库字段保存类型的数据
     */
    abstract public function to($val=null);

    /**
     * 定义此类型字段的 数据 setter 
     * 在 模型实例内部 通过 __set 魔术方法，调用此方法
     * !! 如果此特殊字段类型，有特殊的 set 操作（例如对传入的 值 进行预处理），需要覆盖此方法
     * @param Mixed $val 要设置的 此字段的 新值
     * @param Mixed $old 此字段的 原始值
     * @return Mixed 生成最终的 字段新值，将被写入 模型实例 context
     */
    public function setter($val, $old=null)
    {
        //!! 有需要 自定义逻辑的 特殊字段类型，覆盖此处
        //基类 直接返回 $val
        return $val;
    }



    /**
     * 某些 特殊字段类型 将自动为 模型原始字段附加 对应计算字段，例如：
     *      uuid 类型的字段 foo 将自动拥有对应的计算字段 foo_uuid_ts 从原始字段 foo 的字段值中，提取时间戳
     * 
     * 特殊类型计算字段是通过在 对应的 Types 子类中，定义 fooBarGetter 方法（必须包含规定的 注释内容）
     * 
     * 通常在 模型记录实例内部，通过 __get 方法读取 类型计算字段的值，例如：
     *      $record 记录实例包含 uuid 类型的字段 foo 则：
     *          $record->foo            --> 返回 uuid 字符串
     *          $record->foo_uuid_ts    --> 返回从 uuid 中提取得到的 时间戳 int
     * 
     * 按下列形式 定义 此字段类型的 对应的 计算字段 getter
     * !! 可以针对特殊字段类型，定义此类型字段的 关联计算字段，将在 记录输出时自动执行数据转换
     * !! 有需要的 字段类型，可自行定义
     */
    /**
     * getter
     * @name foo_bar
     * @title 计算字段名
     * @type String
     * @jstype String
     * 
     * @param Mixed $val 此计算字段 依赖的 实际字段的值
     *                   将在执行时，由调用的 模型实例自动传入
     *                   !! 可能为 null  因为模型实例在调用此 getter 时，可能并没有查询取得 依赖字段的值
     * @return Mixed|null
     */
    //public function fooBarGetter($val)


}