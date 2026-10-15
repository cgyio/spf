<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 Orm 要适配的 前端框架 Frontend 兼容层，主要功能：
 * 
 *      0   翻译数据模型中 front 参数
 *          解析生成 与字段 Type 对应的 inputer 输入组件参数，使其可以被前端框架识别，以及使用
 * 
 *      1   代理响应前端框架内置的 自定义接口
 *          某些前端框架可能会定义内置的通用接口（login，config，model-list，等）
 *          在此兼容层代理响应这些接口
 * 
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * !! 不同的应用可以在自身的 module/orm/frontend/ 路径下扩展不同的 前端框架兼容层
 * 
 * 
 * !! SPF-Orm 数据库配置参数中 front 前端参数结构： 见 Parser/Front 解析类定义
 * 
 * 
 * !! 前端库 自定义接口 兼容层 规则：
 *      0   所有前端库自定义的接口，最终都会被转译为 SPF-Orm 内置的接口，包含完整的 oprc 操作信息数组参数
 *          !! 所有收集到的 当前前端库的 接口定义，会在 SPF-Orm 初始化阶段，被收集到 $app->operation 操作列表中
 *          !! 所有 Frontend 前端接口都会统一接入 Spf 框架的 oprs 缓存、权限控制，匿名函数 路由也会自动处理并缓存
 * 
 *          经过处理展开后，Frontend 前端接口的完整结构 与 标准操作信息数组 oprc 一致：
 * 
 *          "操作标识 oprn" => [
 *              "oprn" => "操作标识，其结构为：api/frontend/frontend_name(/...):api_name",
 * 
 *              !! 标记 frontend 子类名，可以通过 Frontend::support(..) 获取类全称，通过 $orm->frontend[..] 获取实例
 *              "frontend" => "front_name",
 * 
 *              !! Spf 框架标准操作 opr 只能指向核心类 App|Module 因此此处固定为 Orm 模块类
 *              "class" => "固定为 Spf\\module\\Orm",
 * 
 *              !! 响应方法默认是 frontendProxyer，如果 frontend 子类定义的接口方法是 匿名函数，则此处为函数定义
 *              !! 在接入 Spf 框架 oprs 操作列表缓存时，会自动处理匿名函数类型的响应方法，自动持久化到 closure 文件
 *              "method" => "固定为 frontendProxyer",
 * 
 *              !! 这些参数是固定的
 *              "isStatic" => 固定为 false,
 *              "export" => "固定为 api",
 * 
 *              !! 这些参数是动态的
 *              "route" => "路由正则根据 前端接口的 路由正则，自动转换(或展开 %{DBN}% %{MDK}%)",
 *              "dbn" => "根据 正则中是否包含 %{DBN}% 决定，展开为具体数据库名 或 null",
 *              "modk" => "根据 正则中是否包含 %{MDK}% 决定，展开为具体数据模型名 或 null",
 * 
 *              !! 接口实际参数
 *              "proxy" => [
 *                  !! 指定实际执行响应的类，默认 当前的 Frontend 子类，也可能是 App|Module|数据库|数据模型 类
 *                  !! 不指定，默认 当前的 Frontend 子类
 *                  "class" => "当前的 Frontend 子类",
 * 
 *                  !! 实际响应方法，会自动转为 fooBarApi 方法名
 *                  !! 如果使用 匿名函数作为响应方法，此处不指定 method
 *                  "method" => "实际响应方法，会自动转为 fooBarApi 方法名",
 * 
 *                  !! 可以指定 input json 参数转译方法，将前端库自定义参数结构，转译为适配 SPF-Orm 框架的参数结构
 *                  !! 指向当前 Frontend 兼容层子类的实例方法 fooBarTranslator
 *                  "translator" => "foo_bar | fooBar",
 *              ],
 *              
 *              !! 下面的参数 由各 Frontend 子类，在 FrontendCls::$apiMap[] 中手动定义
 *              "name" => "foo_bar",
 *              "title" => "...",
 *              "desc" => "...",
 *              "pause" => true|false,
 *              "auth" => true|false,
 *              "role" => "all | foo,bar...",
 *          ]
 *      
 *      1   各 Frontend 子类，在 FrontendCls::$apiMap[] 中手动定义接口映射的 定义方法：
 *          !! 一定以 路由正则 作为键名
 *          SomeFrontend::$apiMap = [
 *              1.1 可以直接映射到 SPF-Orm 内置接口
 *              "/foo/bar(.*)/" => "内置接口的 oprn 操作标识：api/module/foo:bar",
 * 
 *              1.2 可以同时指定 入参转换方法，以及其他更多参数
 *              "/foo/bar(.*)/" => [
 *                  "oprn" => "内置接口的 oprn 操作标识：api/module/foo:bar",
 *                  "proxy" => [
 *                      "translator" => "foo_bar",
 *                      ...
 *                  ],
 *                  "name" => "...",
 *                  "title" => "...",
 *                  "auth" => true,
 *                  ...
 *              ],
 * 
 *              1.3 可以手动定义接口参数，与 route 路由表中接口定义方式 一致
 *              "/foo/bar(.*)/" => [
 *                  "name" => "foo_bar",
 *                  !! 不指定 class 默认指向当前 Frontend 子类
 *                  "class" => "NS\\module\\app_name\\ModuleFoo",
 *                  "method" => "bar", 自动转为 barApi 方法,
 *                  !! 可以直接使用 匿名函数 function，此时不需要指定 class
 *                  "method" => function(...$args) { ... },
 *                  "proxy" => [
 *                      "translator" => "foo_bar",
 *                      ...
 *                  ],
 *                  ...
 *              ],
 * 
 *              !! 1.4  路由正则可以使用 %{DBN}% %{MDK}% 字符串模板，表示这个前端接口，需要使用 数据库|数据模型 作为入参
 *              !!      此类接口会在 SPF-Orm 初始化阶段，被展开后合并到 $app->operation 操作列表中
 *              "/foo/%{DBN}%/%{MDK}%(.*)/" => [
 *                  ... 具体定义与上述方法一致 ...
 *              ],
 * 
 *              ...
 *          ]
 * 
 * 
 * !! 各前端库 拉取数据库完整参数的 方法：
 *      0   直接使用 SPF-Orm 内置接口：api/module/orm:front_db_config
 *          url: https://host/app_name/orm/api/front_db_config/[frontend_name]/[db_name]
 *      1   定义自有接口，指向内置接口：api/module/orm:front_db_config
 *          !! 路由正则必须保证可以传入 当前前端库名 和 数据库名 作为 url 参数
 *          "/dv/db_config(.*)/" => "api/module/orm:front_db_config"
 *          !! 实际访问 url：https://host/app_name/dv/db_config/[frontend_name]/[db_name] 需要传入 前端库名 和 数据库名
 *      2   定义自有接口，使用 Frontend 基类自带的 frontDbConfigApi 方法，作为响应方法
 *          !! 路由正则必须保证可以传入 数据库名 作为 url 参数:
 *          "/dv/db_config(.*)/" => [
 *              "method" => "front_db_config",
 *              "name" => "front_db_config",
 *              "title" => "数据库参数拉取",
 *              "desc" => "拉取数据库的完整参数",
 *              "auth" => false,
 *              "pause" => false
 *          ]
 *          !! 实际访问 url：https://host/app_name/dv/db_config/[db_name] 不需要传入 前端库名
 * 
 * !! 前端库拉取得到的 数据库参数中，会将已解析的 前端库自有参数，合并到最终输出的 数据库参数结构中
 * !! 意味着：不同前端库拉取的数据库参数，其内容是有区别的
 * 
 */

namespace Spf\module\orm;

use Spf\Env;
use Spf\App;
use Spf\exception\AppException;
use Spf\module\Orm;
use Spf\module\orm\Types;
use Spf\module\orm\config\DbConfig;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

use Spf\traits\ExpandableResource;
use Spf\traits\CoreInsGetter;

abstract class Frontend
{
    //核心类快捷获取
    use CoreInsGetter;

    //引用  可扩展底层资源类  特征
    use ExpandableResource;
    //!! trait 中要求的，子类不要覆盖
    protected static $exresName = "frontend";
    protected static $exresClassPath = "module/orm";
    public static $isCollected = false;
    
    /**
     * 当某个 Frontend 子类被 collect 收集时，可以执行自定义操作
     * !! trait 中要求的，子类根据需要覆盖
     * @return Bool
     */
    protected static function whenCollect()
    {
        //Frontend 子类可以指定 collect 阶段的操作

        return true;
    }



    /**
     * !! Frontend 子类必须定义
     */
    //当前 frontend 子类名称
    protected static $frontend = "";

    /**
     * 当前 前端库自定义接口 与 SPF-Orm 模块实际接口的映射
     * 将会在 Orm 初始化阶段，被处理并收集到 全局 oprs 操作列表中，并接入统一的 缓存、权鉴体系中
     */
    protected static $apiMap = [
        
    ];
    
    /**
     * !! 如果有需要，子类覆盖
     * 可定义此前端库 统一的 接口返回数据的 结构处理方法
     * !! 可以覆盖 Spf 框架默认的 api 返回数据结构
     */
    public function responseData($spfReturn = [])
    {
        //子类可实现自定义 逻辑，将 Spf 默认的 接口返回结构转换为当前 前端库自有的返回结构

        return $spfReturn;
    }
    


    /**
     * 构造
     * 一次会话中，对应的 Frontend 前端兼容层是单例的，缓存在 Orm::$current->frontend 属性中
     * 引入 CoreInsGetter trait 获得完整的 核心类访问能力
     * @return void
     */
    public function __construct()
    {
        //前端兼容层实例化时，Orm 模块必须已经实例化
        if (Orm::$isInsed!==true) return null;
        //$orm = Orm::$current;
    }

    /**
     * !! 子类禁止覆盖
     * 实例化多个 Frontend 子类，缓存到 $orm->frontend[]
     * @param Orm $orm 当前 orm 实例
     * @return Bool
     */
    final public static function getFrontendIns($orm)
    {
        if (!($orm instanceof Orm)) return false;
        $frts = $orm->config->ctx["frontend"];
        if (Is::nemstr($frts)) $frts = [$frts];
        if (!Is::nemidx($frts)) {
            $orm->frontend = [];
            return true;
        }

        //依次实例化
        foreach ($frts as $frtn) {
            $frtcls = static::support($frtn);
            //参数配置错误
            if (!class_exists($frtcls)) {
                return false;
            }
            //缓存到 $orm
            $orm->frontend[$frtn] = new $frtcls();
        }

        return true;
    }

    /**
     * !! 子类禁止覆盖
     * 在 SPF-Orm 初始化阶段执行，批量 处理|展开 前端库自定义接口 转换为 标准 oprs 接口
     * !! 批量执行 Orm 参数中指定的所有 frontend 子类的 getOprs 方法
     * @param Orm $orm 当前 orm 实例
     * @param Array $oprs 当前已经解析得到的 所有 数据库|模型 的完整 操作列表 oprs
     * @return Array 标准操作列表 oprs[] 结构
     */
    final public static function getFrontendOprs($orm, $oprs = [])
    {
        //准备 $oprs 结果数组
        if (!Is::nemarr($oprs)) {
            $oprs = [
                "apis" => [],
            ];
        } else if (!isset($oprs["apis"])) {
            $oprs["apis"] = [];
        }

        //已经实例化的 frontend 
        if (!isset($orm) || !($orm instanceof Orm)) return $oprs;
        $frts = $orm->frontend;
        if (!Is::nemaso($frts)) return $oprs;

        //依次执行 各 frontend 子类的 getOprs 方法
        foreach ($frts as $frtn => $frto) {
            if (!($frto instanceof Frontend)) continue;
            $oprs = $frto->getOprs($orm, $oprs);
        }

        return $oprs;
    }

    /**
     * !! 子类禁止覆盖
     * 在 SPF-Orm 初始化阶段执行 处理|展开 前端库自定义接口 转换为 标准 oprs 接口
     * 将由 Orm 模块调用，并将生成的 oprs 汇总到 $app->operation 操作列表
     * !! 进入 $app->operation 操作列表后，此前端框架的所有 oprs 将自动接入 Spf 框架的 操作缓存、权鉴体系
     * @param Orm $orm 当前 orm 实例
     * @param Array $oprs 当前已经解析得到的 所有 数据库|模型 的完整 操作列表 oprs
     * @return Array 标准操作列表 oprs[] 结构
     */
    final public function getOprs($orm, $oprs = [])
    {
        //oprs 数组
        if (!Is::nemarr($oprs)) {
            $oprs = [
                "apis" => [],
            ];
        } else if (!isset($oprs["apis"])) {
            $oprs["apis"] = [];
        }
        if (!isset($orm) || !($orm instanceof Orm)) return $oprs;
        if (!Is::nemarr(static::$apiMap)) return $oprs;

        //依次解析 此前端库定义的 接口
        foreach (static::$apiMap as $pattern => $def) {
            //解析结果
            $oprc = static::parseApiDefine($pattern, $def, $oprs, $orm);
            if (!Is::nemaso($oprc)) continue;
            //解析结果是 oprs 操作列表
            if (isset($oprc["apis"]) && Is::nemidx($oprc["apis"])) {
                //合并
                $oprs = Arr::extend($oprs, $oprc);
                continue;
            } 
            //解析结果是 操作信息 oprc
            if (isset($oprc["oprn"]) && Is::nemstr($oprc["oprn"])) {
                $oprn = $oprc["oprn"];
                //写入 oprs
                if (!in_array($oprn, $oprs["apis"])) {
                    $oprs["apis"][] = $oprn;
                    $oprs[$oprn] = $oprc;
                }
            }
        }

        //返回合并后的 oprs
        return $oprs;
    }

    /**
     * !! 子类禁止覆盖
     * 处理单个 api 接口映射定义
     * @param String $pattern 接口 路由正则 字符串
     * @param Array|String $def 接口定义，可能是当前已存在的 oprn 操作标识
     * @param Array $oprs 已解析得到的 oprs 操作列表
     * @param Orm $orm 实例
     * @return Array|null 标准操作信息数组 oprc 或 oprs 操作列表（展开 %{DBN}% %{MDK}% 得到的操作列表）
     */
    final protected static function parseApiDefine($pattern, $def, $oprs, $orm = null)
    {
        if (!$orm || !($orm instanceof Orm)) return null;
        if (!Is::nemstr($pattern) || !(Is::nemstr($def) || Is::nemarr($def))) return null;
        //App 应用实例
        $app = App::$current;
        if (!$app instanceof App) return null;
        //已有的 oprs 操作列表
        $alloprs = $app->operation->ctx();

        //标记当前接口是否需要 数据库|模型 名称作为参数
        $needDbn = strpos($pattern, "%{DBN}%") !== false;
        $needModk = strpos($pattern, "%{MDK}%") !== false;
        //$needDbnModk = $needDbn && $needModk;
        //标记是否直接映射到 某个内部接口
        $toInnerApi = Is::nemstr($def) || (Is::nemaso($def) && isset($def["oprn"]));

        //查找内部接口，改写为前端库接口信息 oprc
        $modInnerOprc = function($pt, $ooprn, $doprc) use ($alloprs, $oprs) {
            //查找内部接口
            $oprc = $alloprs[$ooprn] ?? $oprs[$ooprn] ?? null;
            if (!Is::nemaso($oprc)) return null;
            //指向的内部接口 必须是 api 类型
            if ($oprc["export"]!=="api") return null;
            //前端库名
            $frtn = static::$frontend;

            //改写接口参数 oprc
            return Arr::extend($oprc, $doprc, [
                //生成新的 oprn
                "oprn" => "api/frontend/$frtn/".substr($ooprn, 4/*strlen("api/")*/),
                //标记所属 frontend
                "frontend" => $frtn,
                //生成新接口名称
                "name" => "frontend_".$frtn."_".$oprc["name"],
                //固定参数
                "class" => Orm::class,
                "method" => "frontendProxyer",
                "isStatic" => false,
                "export" => "api",
                //路由正则 替换为 前端库接口路由
                "route" => $pt,
                "proxy" => [
                    //指向内部接口操作标识
                    "innerOprn" => $ooprn
                ],
            ]);
        };

        //处理手动定义的 oprc，添加必要项目
        $fixDefOprc = function($pt, $doprc) {
            //接口名，优先使用手动定义的
            $apin = Is::nemstr($doprc["name"]) ? $doprc["name"] : static::getApinFromPattern($pt);
            //生成 oprn
            $oprn = static::getOprnFromPattern($pt);
            if (!Is::nemstr($apin) || !Is::nemstr($oprn)) return null;
            //proxy 实际响应参数
            $proxy = [
                //实际响应类，默认 当前 Frontend 子类
                "class" => class_exists($doprc["class"]) ? $doprc["class"] : static::class,
            ];
            //method
            $om = $doprc["method"] ?? null;
            if (is_null($om) || !(Is::nemstr($om) || is_callable($om))) return null;
            if (is_callable($om)) {
                $method = $om;
            } else {
                $method = "frontendProxyer";
                $proxy["method"] = substr($om, strlen($om)-3) !== "Api" ? $om."Api" : $om;
            }
            
            //生成 操作信息数组 oprc
            return Arr::extend($doprc, [
                "oprn" => $oprn,
                //标记所属 frontend
                "frontend" => static::$frontend,
                //接口名自动加前缀
                "name" => "frontend_".static::$frontend."_".$apin,
                //路由正则
                "route" => $pt,
                //固定参数
                "class" => Orm::class,
                "method" => $method,
                "isStatic" => false,
                "export" => "api",
                //接口实际参数
                "proxy" => $proxy,
            ]);
        };

        // 0    直接映射框架内部的 接口
        if ($toInnerApi === true) {
            //解析定义的 pattern，内部 oprn，手动定义的 oprc
            $innerOprn = Is::nemstr($def) ? $def : $def["oprn"];
            $defOprc = Is::nemstr($def) ? [] : Arr::extend($def, ["oprn" => "__delete__"]);

            // 0.1  不含 %{...}% 字符串模板
            if (!$needDbn && !$needModk) {
                //查找内部接口
                return $modInnerOprc($pattern, $innerOprn, $defOprc);
            }
            //准备输出
            $ops = [
                "apis" => []
            ];
            // 0.2  含有 %{...}% 字符串模板
            if ($needDbn || $needModk) {
                $orm->eachDb(function($dbn, $db) use (&$ops, $pattern, $innerOprn, $defOprc, $needDbn, $needModk) {
                    //获取数据库配置类实例
                    $cfger = $db instanceof DbConfig ? $db : $db->config;
                    if (!$cfger instanceof DbConfig) return true;

                    //准备
                    $ptn = $pattern;
                    $ina = $innerOprn;

                    //替换 %{DBN}%
                    if ($needDbn) {
                        $ptn = str_replace("%{DBN}%", $dbn, $ptn);
                        //指向的内部接口也要替换
                        $ina = str_replace("%{DBN}%", $dbn, $ina);
                        //将 dbn 写入 defOprc
                        $defOprc["dbn"] = $dbn;
                        //如果仅包含 %{DBN}% 则直接输出
                        if (!$needModk) {
                            //查找内部接口
                            $oprc = $modInnerOprc($ptn, $ina, $defOprc);
                            if (!Is::nemaso($oprc) || !isset($oprc["oprn"])) return true;
                            //写入
                            $oprn = $oprc["oprn"];
                            if (!in_array($oprn, $ops["apis"])) {
                                $ops["apis"][] = $oprn;
                                $ops[$oprn] = $oprc;
                            }
                            return true;
                        }
                    }

                    //替换 %{MDK}%
                    if ($needModk) {
                        foreach ($cfger->ctx["model"] as $modk => $modc) {
                            $ptn = str_replace("%{MDK}%", $modk, $ptn);
                            //指向的内部接口也要替换
                            $ina = str_replace("%{MDK}%", $modk, $ina);
                            //将 modk 写入 defOprc
                            $defOprc["modk"] = $modk;
                            //查找内部接口
                            $oprc = $modInnerOprc($ptn, $ina, $defOprc);
                            if (!Is::nemaso($oprc) || !isset($oprc["oprn"])) return true;
                            //写入
                            $oprn = $oprc["oprn"];
                            if (!in_array($oprn, $ops["apis"])) {
                                $ops["apis"][] = $oprn;
                                $ops[$oprn] = $oprc;
                            }
                        }
                        return true;
                    }

                });
                return $ops;
            }
            
            return null;
        }

        // 1    手动定义前端库接口
        // 1.1  不含 %{...}% 字符串模板
        if (!$needDbn && !$needModk) {
            //直接处理手动定义的 oprc
            return $fixDefOprc($pattern, $def);
        }
        
        //准备输出
        $ops = [
            "apis" => []
        ];
        // 1.2  含有 %{...}% 字符串模板
        if ($needDbn || $needModk) {
            $orm->eachDb(function($dbn, $db) use (&$ops, $pattern, $def, $needDbn, $needModk) {
                //获取数据库配置类实例
                $cfger = $db instanceof DbConfig ? $db : $db->config;
                if (!$cfger instanceof DbConfig) return true;

                //准备
                $ptn = $pattern;

                //替换 %{DBN}%
                if ($needDbn) {
                    $ptn = str_replace("%{DBN}%", $dbn, $ptn);
                    //dbn 写入 def
                    $def["dbn"] = $dbn;
                    //如果没有 %{MDK}% 直接返回
                    if (!$needModk) {
                        //处理手动定义的 oprc
                        $oprc = $fixDefOprc($ptn, $def);
                        if (!Is::nemaso($oprc) || !isset($oprc["oprn"])) return true;
                        //写入
                        $oprn = $oprc["oprn"];
                        if (!in_array($oprn, $ops["apis"])) {
                            $ops["apis"][] = $oprn;
                            $ops[$oprn] = $oprc;
                        }
                        return true;
                    }
                }

                //替换 %{MDK}%
                if ($needModk) {
                    foreach ($cfger->ctx["model"] as $modk => $modc) {
                        $ptn = str_replace("%{MDK}%", $modk, $ptn);
                        //将 modk 写入 def
                        $def["modk"] = $modk;
                        //处理手动定义的 oprc
                        $oprc = $fixDefOprc($ptn, $def);
                        if (!Is::nemaso($oprc) || !isset($oprc["oprn"])) return true;
                        //写入
                        $oprn = $oprc["oprn"];
                        if (!in_array($oprn, $ops["apis"])) {
                            $ops["apis"][] = $oprn;
                            $ops[$oprn] = $oprc;
                        }
                    }
                    return true;
                }
            });

            return $ops;
        }

        return null;
    }



    /**
     * 数据模型参数 front 项 解析处理 系列方法
     * !! 执行这些方法时，frontend 已经实例化，因此都是实例方法
     * !! 在 DbConfig 参数处理阶段，由 Parser 子类 Front 调用这些方法
     * !! 数据库参数解析结果会被缓存，因此如果解析逻辑发生变化，或新增 Frontend 子类，需要清空缓存
     */

    /**
     * !! 子类禁止覆盖
     * 处理 数据模型参数中的 front 参数，生成适配当前前端库的 最终参数，将会被合并到 $db->config->context 中
     * 此方法会被数据库配置解析类 Parser 子类 Front 调用
     * @param Object $params 由 Front 解析类 params() 方法生成的前置条件
     *                       结构为：[
     *                           "context" => [],   已经解析得到的 数据模型参数
     *                           "temp" => [],      Front Parser 解析器已经解析得到的 front 参数临时数据
     *                           "origin" => [],    数据库参数配置 原始数据
     *                           "dbn" => "库名", "modk" => "表名",
     *                           "driver" => "数据库驱动 类全称",
     *                           "model" => "数据模型类全称",
     *                           "orm" => Orm 模块实例,
     *                       ]
     * @return Array 最终要合并到 $db->config->context[] 中的 front 前端参数，结构与 context["front"] 一致：
     *  [
     *      "table" => [
     *          "hide" => [],
     *          "mode" => [
     *              "default" => [
     *                  "title" => "",
     *                  "columns" => [],
     *                  "isClassic" => true,
     *                  "component" => "",
     *                  "props" => [],
     *              ],
     *              ...
     *          ],
     *          "highlight" => [
     *              [
     *                  "columns" => ["enable"],
     *                  "function" => "return rs.enable !== true;",
     *                  "level" => "disabled",
     *                  "style" => [],
     *              ],
     *              ...
     *          ],
     *      ],
     * 
     *      "form" => [
     *          "hide" => ["id", "gen_a", "gen_b", ...],
     *          "validator" => [
     *              "col_a" => "requiredValidator",
     *              "col_b" => "return val > 100;",
     *              ...
     *          ],
     *          "inputer" => [
     *              "col_a" => [
     *                  "component" => "ElInput",
     *                  "props" => [...],
     *              ],
     *              ...
     *          ],
     *      ],
     *  ]
     */
    final public function parseFront($params)
    {
        //当前模型的所有字段
        $cols = $params->context["columns"] ?? [];
        $colp = $params->context["column"] ?? [];
        if (!Is::nemidx($cols) || !Is::nemaso($colp)) return [];

        //准备返回值，结构与 context["front"] 参数结构一致
        $rtn = [
            "table" => [],
            "form" => [
                "inputer" => [],
            ],
        ];
        
        //在数据库参数中的 inputer 配置项
        $oinp = $params->origin["front"]["form"]["inputer"] ?? [];

        //依次生成 各物理字段的 inputer 参数
        foreach ($cols as $col) {
            $colc = $colp[$col] ?? null;
            //只针对 物理字段
            if (!Is::nemaso($colc) || $colc["isGetter"]===true) continue;
            
            //获取当前字段类型
            $type = $colc["type"]["def"];
            //var_dump($params->dbn."/".$params->modk."/".$col.": ".$type);
            if (!Is::nemstr($type)) continue;
            $tcls = Types::support($type);
            if (!class_exists($tcls)) continue;
            $type = Types::isNative($type) === true ? "native" : $type;
            //getFooBarInputer 方法名
            $gm = "get".Str::camel($type, true)."Inputer";
            //var_dump($gm);
            if (!method_exists($this, $gm)) continue;

            //调用 getFooBarInputer
            $inp = $this->$gm($col, $colc, $params);
            if (!Is::nemaso($inp) || !isset($inp["component"])) continue;

            //如果存在手动定义的 此字段的 inputer 参数，则覆盖自动生成的
            if (Is::nemaso($oinp[$col])) {
                $inp = Arr::extend($inp, $oinp[$col]);
            }

            //写入返回值
            $rtn["form"]["inputer"][$col] = $inp;
        }

        //调用 parseFrontTemp 方法，解析其他已生成的 temp 参数，生成自有 front 参数，结构与 context["front"] 一致
        $ctemp = $this->parseFrontTemp($params);
        if (Is::nemaso($ctemp)) {
            //!! 指定 indexed 数组合并方式为 覆盖，而不是 合并去重
            $rtn = Arr::extend($rtn, $ctemp, true);
        }

        return $rtn;
    }

    /**
     * !! 除原生 Deskvue 前端库外，其他兼容层子类必须覆盖
     * 前端兼容层 处理 Parser/Front 解析得到的 临时 front 通用参数，得到前端库自有参数
     * @param Object $params 前置条件
     * @return Array 返回 与 context["front"] 结构一致的 前端库自有参数
     */
    protected function parseFrontTemp($params)
    {
        //!! 除 原生 Deskvue 库外，其他前端库兼容层自行处理

        //默认 Deskvue 库不需要额外逻辑，直接返回空值
        return [];
    }

    /**
     * getTypeNameInputer($col_name) 
     * 
     * !! 默认的 字段类型 --> 前端 inputer 组件参数的转换系列方法
     * !! Spf 框架原生的前端库 Deskvue 不需要覆盖，其他前端库需要覆盖这些方法
     * !! 如果使用 Element-Plus 作为前端表单库的，也不需要覆盖
     * !! 必须根据 Spf 框架支持的 Types 字段类型，分别定义 getFooBarInputer() 方法
     * 
     * !! 如果当前应用定义了自有的 特殊字段类型：例如：Package
     *      0   在应用路径下扩展 Frontend 子类，
     *          例如：如果使用 Deskvue 前端库，则需要在应用目录下新建 Deskvue 前端兼容层类，并 extends 默认的 Deskvue 类
     *      1   在扩展的 Frontend 子类中，针对新增的特殊字段类型 Package，定义对应的 getPackageInputer() 方法
     * 
     * !! 第三方前端库，推荐在前端库内部自行定义 字段类型 --> 输入组件 的映射
     * !! 不推荐在后端的 Frontend 兼容层生成 inputer 参数
     * 
     * @param String $col 字段名，一定是 物理字段
     * @param Array $colc 字段参数
     * @param Object $params 由 Front 解析类 params() 方法生成的前置条件
     * @return Array|null 
     * 统一的返回格式：
     *  [
     *      # 前端库使用的，此类型字段的 input 输入组件名称
     *      !! 默认使用 Element-Plus 组件名
     *      "component" => "ElInput",
     * 
     *      # 前端 input 输入组件的 props
     *      !! 默认使用 Element-Plus 组件参数结构
     *      "props" => [
     *          !! 会根据传入的 字段名，读取字段参数，然后自动生成 props
     *      ],
     * 
     *      !! dura 值区间的输入组件参数
     *      "dura" => [
     *          "component" => "组件名，不指定使用默认的",
     *          "props" => [
     *              !! 值区间输入组件的参数
     *          ]
     *      ]
     *  ]
     */
    //Native 原生字段类型
    protected function getNativeInputer($col, $colc, $params)
    {
        //原生字段类型，支持："varchar", "char", "text", "integer", "bigint", "tinyint", "float",
        $type = $colc["type"]["def"];

        //字段参数
        $colt = $colc["title"];
        $desc = $colc["desc"];

        //字符串
        $str = ["varchar", "char"];
        if (in_array($type, $str)) {
            return [
                "component" => "ElInput",
                "props" => [
                    "type" => in_array($col, explode(",","pswd,password")) ? "password" : "text",
                    "placeholder" => $colt,
                    "clearable" => true,
                    "title" => $desc,
                ],
            ];
        }

        //全文
        $txt = ["text"];
        if (in_array($type, $txt)) {
            return [
                "component" => "ElInput",
                "props" => [
                    "type" => "textarea",
                    "placeholder" => $colt,
                    "clearable" => true,
                    "title" => $desc,
                ],
            ];
        }
        
        //int
        $int = ["integer", "bigint", "tinyint"];
        if (in_array($type, $int)) {
            return [
                "component" => "ElInputNumber",
                "props" => [
                    "step" => 1,
                    "placeholder" => $colt,
                    "title" => $desc,
                ],
            ];
        }
        
        //float
        $flo = ["float"];
        if (in_array($type, $flo)) {
            return [
                "component" => "ElInputNumber",
                "props" => [
                    //默认精度 4
                    "precision" => 4,
                    "step" => 0.0001,
                    "placeholder" => $colt,
                    "title" => $desc,
                ],
            ];
        }

        return null;
    }
    //TODO: 更多字段类型...




    /**
     * 工具方法
     */

    /**
     * 从 pattern 中解析得到 接口名
     * /foo/bar(.*)/ --> foo_bar
     * @param String $pattern
     * @return String|null
     */
    protected static function getApinFromPattern($pattern)
    {
        if (!Is::nemstr($pattern)) return null;
        $ptn = trim(trim($pattern), "/");
        $pta = explode("(", $ptn);
        $ptn = $pta[0] ?? null;
        if (is_null($ptn) || !Is::nemstr($ptn)) return null;
        return str_replace("\/", "_", $ptn);
    }

    /**
     * 根据传入的 pattern 解析得到 前端库接口的 操作标识
     * @param String $pattern
     * @return String|null
     */
    protected static function getOprnFromPattern($pattern)
    {
        //获取 接口名
        $apin = static::getApinFromPattern($pattern);
        if (!Is::nemstr($apin)) return null;
        $apia = explode("_", $apin);
        $apin = array_pop($apia);
        $apip = empty($apia) ? "" : "/".implode("/", $apia);
        return "api/frontend/".static::$frontend.$apip.":".$apin;
    }

    /**
     * 判断一个 oprn 是 前端兼容层 操作标识
     * !! 以 api/frontend/ 开头
     * @param String $oprn 操作标识
     * @return Bool
     */
    public static function isFrontendOprn($oprn)
    {
        if (!Is::nemstr($oprn)) return false;
        $len = strlen("api/frontend/");
        if (substr($oprn, 0, $len) !== "api/frontend/") return false;
        //!! 如果是基类调用，直接返回
        if (static::$frontend === "") return true;
        //!! 如果是子类调用，还需要比较 子类名 是否匹配
        $frn = substr($oprn, $len);
        $fra = explode("/", $frn);
        return $fra[0] === static::$frontend;
    }

    /**
     * 调用 前端入参转译函数
     * @param String $oprn 操作标识
     *               !! 也可以直接传入 转译函数名称 支持 foo_bar 和 fooBar 形式，统一转为 fooBarTranslator 方法名
     * @param Array $input 可以手动指定 input 数据，不指定则自动从 $request->inputs->json 中读取
     * @return Bool 转译入参，修改 Request::$current->inputs->json 返回 bool
     *              !! 经过此处修改的 Request::$current->inputs 可以通过 inputs->restore() 恢复
     */
    public function callTranslator($oprn, $input = [])
    {
        //request 请求实例
        $req = $this->Req();
        if (!$req) return false;
        //入参
        $inp = Is::nemaso($input) ? $input : $req->inputs->json;
        if (!is_array($inp)) $inp = [];

        if (!Is::nemstr($oprn)) return false;

        //默认传入 函数名
        $trans = $oprn;

        //传入了 oprn 操作标识
        if (static::isFrontendOprn($oprn)) {
            //调用入参转译函数时，App 应用类一定已经实例化
            $app = $this->App();
            if (!$app || !($app instanceof App)) return false;
            //操作列表中查找此操作信息
            $oprc = $app->operation->ctx($oprn);
            if (!Is::nemarr($oprc)) return false;
            //translator 定义
            $trans = $oprc["proxy"]["translator"] ?? null;
            if (!Is::nemstr($trans)) return false;
        }

        //foo_bar 或 fooBar 转为 fooBarTranslator 完整方法名
        $trans = Str::camel($trans, false);
        if (substr($trans, strlen("Translator")*-1) !== "Translator") {
            $trans .= "Translator";
        }

        //在当前 兼容层类中查找实例方法
        if (method_exists($this, $trans)) {
            //执行转译
            $ninp = $this->$trans($inp);
            //替换当前 Request::$current->inputs
            if (is_array($ninp)) {
                $req->inputs->replace($ninp);
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * !! 如果有需要，应逐个定义接口 oprc 中指定的 translator 方法
     */
    //protected function fooBarTranslator($frontInput = []) { return 适配后的前端 input json 结构 }



    /**
     * !! Frontend 前端兼容层 通用的 数据库参数拉取接口
     * !! 如果某个 前端兼容层有特殊逻辑，可以覆盖这个方法，一般不需要覆盖
     * 各 前端库 可以定义自有接口，调用这个 api 方法，url 参数只需要传入 数据库名
     * 会读取 解析好（已缓存）的数据库参数，合并当前前端库的自有参数，输出到前端
     * !! 此方法执行时，Orm 实例一定已创建，通过 Orm 实例可以创建 DbConfig 实例，或者读取已缓存的 DbConfig
     * @param String $dbn 数据库名
     * @return Array 完整的 数据库参数结构，如果未获取到参数，报错并返回错误信息到前端
     */
    public function frontDbConfigApi($dbn)
    {
        $orm = Orm::$current;
        if (!Is::nemstr($dbn) || !($orm instanceof Orm) || $orm->dbDefed($dbn)!==true) {
            //环境状态 或 传入参数 错误，报错
            throw new AppException("Orm 模块状态错误 或 未传入有效的数据库名", "app/response");
            return null;
        }

        //读取当前数据库参数
        $cfger = $orm->dbCfger($dbn);
        if (!($cfger instanceof DbConfig)) {
            throw new AppException("数据库 $dbn 未能正确解析配置", "app/response");
            return null;
        }
        //完整数据库参数，包含多个前端库的自有参数
        $ctx = $cfger->ctx;

        //当前 前端兼容层的 名称
        $frtn = static::$frontend;

        //准备输出结构
        $rtn = [
            "meta" => [
                "name" => $ctx["name"],
                "title" => $ctx["title"],
                "desc" => $ctx["desc"],
            ],
            //标记使用的 frontend 前端库
            "frontend" => $frtn,
            //所有 数据模型 名称列表
            "models" => $ctx["models"],
            "model" => [],
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
                //xpath
                "xpath" => $ctx["name"]."/".$modc["name"],
                //物理字段
                "columns" => $mcls::realInDbColumns(),
                //计算字段
                "getters" => $mcls::getterColumns(),
                //全部字段
                "allColumns" => $modc["columns"],
                //特殊字段
                "special" => $modc["special"],
                //join 参数
                "join" => $modc["join"] ?? null,
                //front 前端参数
                "front" => [],
                //数据记录的 TS interface 仅包含 真实字段
                "tsInterface" => [],
                //所有字段(包含计算字段)的 结构化参数
                "column" => []
            ];

            //读取 front 参数，合并当前 frontend 自有参数
            $front = Arr::copy($modc["front"]);
            $frtc = Arr::copy($front["frontend"][$frtn] ?? []);
            unset($front["frontend"]);
            $front = Arr::extend($front, $frtc);
            //$front["frontend"] = $frtc;
            //所有字段的 inputer 参数
            $inps = Arr::copy($front["form"]["inputer"] ?? []);
            //if (isset($front["form"]["inputer"])) unset($front["form"]["inputer"]);
            //front 参数 写回 $modi
            $modi["front"] = $front;

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
                    //xpath
                    "xpath" => $ctx["name"]."/".$modc["name"]."/".$colc["name"],
                    //字段类型 js 类型
                    "type" => $colc["type"]["js"],
                    "types" => $colc["type"],
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
                    "getter" => "__delete__",
                ], $coli);

                //各字段的 前端参数
                $coli["hideInTable"] = in_array($colk, $front["table"]["hide"]);
                $coli["hideInForm"] = in_array($colk, $front["form"]["hide"]);
                $coli["hasValidator"] = isset($front["form"]["validator"][$colk]);
                $coli["validator"] = $coli["hasValidator"] === true ? $front["form"]["validator"][$colk] : "";
                $coli["inputer"] = $inps[$colk] ?? null;

                //写入 column 参数列表
                $modi["column"][$colk] = $coli;

                //interface
                if (!$real) continue;
                $itfk = $inc ? $colk : "$colk?";
                $modi["tsInterface"][$itfk] = $coli["type"];
            }
            $rtn["model"][$modk] = $modi;
        }

        //返回
        return $rtn;
    }

}