<?php
/**
 * 框架 App 应用类 可用操作管理类
 * 
 * 类 属性|方法 用于：
 *      查找当前 webroot 路径下的所有可用的 App 应用类，收集它们的可用操作方法，汇总生成操作列表
 * 实例的 属性|方法 用于：
 *      管理 关联的 App 应用实例的 所有可用 操作，维护这些操作的信息，如：操作标识，权限信息 等
 * 
 * !! 此类禁止被继承
 */

namespace Spf\app;

use Spf\App;
use Spf\exception\CoreException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Cache;

final class Operation 
{
    /**
     * 收集汇总 生成的 操作列表
     * 此列表可能在 框架运行过程中被修改，
     * !! 如果 运行时缓存 开启，此列表会被缓存
     */
    protected static $all = [
        /*
        "应用名称(应用文件夹名)，全小写，下划线_ 格式：foo_bar" => [

            "apis" => [
                # 所有可用的 api 操作的 名称数组
                "jaz_tom", "", ...
            ],

            "views" => [
                # 所有可用的 view 操作的 名称数组
                "", "", ...
            ],
            
            "操作标识，全小写，下划线_ 格式：api/foo_bar:jaz_tom" => [

                # 操作信息 通过解析相关方法的 注释信息得到

                "oprn"      => "操作标识，键名，全小写，下划线_ 形式：api/foo_bar:jaz_tom",
                "class"     => "此路由操作的 App 应用类全称：NS\app\FooBar",
                "method"    => "实际执行的方法名，驼峰，首字母小写，带 -Api|-View 等类型的后缀：jazTomApi",
                "name"      => "在对应的 App 应用类参数类实例 context[apis|views|...] 数组中的键名，全小写下划线_形式：jaz_tom",
                "title"     => "定义在注释中的此操作的标题：操作jazTom",
                "desc"      => "经过处理的用作 操作标识对应的操作说明文字：  应用说明：方法说明",
                "export"    => "输出方式，影响创建的 Response 响应实例类型，可选 api|view ：api",
                "auth"      => 此路由操作是否启用 Uac 权限控制，默认 true 通常在方法注释中指定：true,
                "role"      => "可额外指定此操作的权限角色，在注释中指定，默认 all 多个角色用 , 隔开：all",
                "route"     => "可在方法注释中定义 此方法的 路由 正则，将被收集到 路由表中，默认不指定：null"

                # 其他 方法注释中指定的 操作信息
                ...
            ],
            ...
        ],
        ...
        */
    ];
    //缓存文件路径
    protected static $cacheFile = "runtime/operation.php";

    /**
     * 收集到的 匿名函数 操作 method
     * 通常是在自定义 路由操作时，会指定 匿名函数 作为 操作的 method
     * 以 操作标识 为键名
     * !! 如果 运行时缓存 开启，此列表会被缓存
     */
    protected static $closure = [
        /*
        "操作标识" => function(...) { ... },
        ...
        */
    ];
    //缓存文件路径
    protected static $cacheClosureFile = "runtime/closure.php";

    //标记 initOprs 已执行
    public static $isInited = false;

    /**
     * 可用操作的收集与汇总
     * !! 应在 环境变量初始化后，路由解析之前 执行此方法
     * @param Array $opt 框架启动参数中的 route 路由参数，通常是手动定义的 全局路由操作
     * @return Bool
     */
    public static function initOprs($opt=[])
    {
        //首先尝试缓存文件
        if (true === self::readCache()) {
            //缓存读取成功，且已写入 $all|$closure
            //inited 标记
            self::$isInited = true;
            return true;
        }

        /**
         * 未获取到缓存，可能 缓存已过期 或 未开启 WEB_CACHE
         * 开始 收集 操作列表
         */
        //收集 全局路由 操作
        $initrut = self::initRoutes($opt);
        //收集 所有应用的 可用操作
        $initapp = self::initAppOprs();
        //收集 所有模块的 可用操作
        $initmod = self::initModuleOprs();

        //错误收集
        if ($initrut && $initapp && $initmod) {
            //init 操作正常，标记 inited
            self::$isInited = true;
            //将得到的 操作列表 写入 缓存
            return self::saveCache();
        }
        
        //发生错误
        return false;
    }

    /**
     * 读取缓存文件，写入 $all|$closure
     * @return Bool 是否读取并写入成功
     */
    protected static function readCache()
    {
        //读取预设的 缓存文件
        $cf = self::$cacheFile;
        $ccf = self::$cacheClosureFile;
        $all = Cache::read($cf);
        $closure = Cache::read($ccf);
        //写入 静态属性
        if (Is::nemarr($all)) {
            self::$all = $all;
            if (Is::nemarr($closure)) self::$closure = $closure;
            return true;
        }
        return false;
    }

    /**
     * 将解析得到的 $all|$closure 数据写入缓存
     * @return Bool 
     */
    protected static function saveCache()
    {
        //写入缓存
        Cache::save(self::$cacheFile, self::$all);
        if (Is::nemarr(self::$closure)) Cache::saveClosure(self::$cacheClosureFile, self::$closure);
        return true;
    }

    /**
     * init 方法
     * 收集 手动定义的 全局路由 操作
     * 通常定义在：
     *      启动参数的 route 项
     *      lib/route.php
     * @param Array $opr 启动参数中的 route 项
     * @return Bool
     */
    protected static function initRoutes($opt=[])
    {
        //准备 路由表
        $routes = [];
        //首先尝试读取 lib/route.php 全局路由表
        $rf = Path::find("lib/route.php", Path::FIND_FILE);
        if (!empty($rf)) {
            //读取 全局路由表
            $routes = require($rf);
        } 
        //与 启动参数中的 route 项 合并，参数中的 覆盖 route.php 中的
        $routes = array_merge($routes, $opt);

        /**
         * 遍历路由表，将 路由操作 添加到对应的 应用的 操作列表中
         * !! 全局路由必须完整定义 name|[title|desc]|class|method
         * !! class 指向某个 应用的 类全称
         * !! method 指向某各 应用类方法  或者  自定义的函数
         */
        foreach ($routes as $pattern => $oprc) {
            //处理这个 路由定义
            $oprc = self::parseRoute($pattern, $oprc);
            if (!Is::nemarr($oprc)) continue;
            $name = $oprc["name"];
            $oprn = $oprc["oprn"];
            $clsn = $oprc["class"];
            $expt = $oprc["export"];
            $route = $oprc["route"];
            $appk = $clsn::clsk();

            if (isset(self::$all[$appk]) && isset(self::$all[$appk][$oprn])) {
                //此路由操作 已在 应用的 操作列表中，仅修订 操作信息
                self::$all[$appk][$oprn]["route"] = $route;
                continue;
            }

            //操作写入对应的 应用操作列表
            $oprs = [
                "apis" => [],
                "views" => [],
            ];
            $oprs[$expt."s"] = [ Str::snake($name, "_") ];
            $oprs[$oprn] = $oprc;
            //写入对应 app 的操作列表中
            self::setAppOprs($appk, $oprs);
        }

        return true;
    }

    /**
     * init 方法
     * 收集 所有 应用的 可用操作
     * @return Bool
     */
    protected static function initAppOprs()
    {
        $path = defined("APP_PATH") ? APP_PATH : null;
        if (!Is::nemstr($path) || !is_dir($path)) {
            //应用路径还未定义
            return false;
        }

        //遍历 应用路径 创建 可用操作数组
        $ph = opendir($path);
        while(false!==($app = readdir($ph))) {
            if (in_array($app, [".",".."])) continue;
            if (is_dir($path.DS.$app)) {
                //如果存在 应用文件夹
                $appn = Str::camel($app, true);
                $appcls = Cls::find("app/$appn");
            } else if (substr(strtolower($app), strlen(EXT_CLASS)*-1) == strtolower(EXT_CLASS)) {
                //如果存在应用类文件
                $appn = substr($app, 0, strlen(EXT_CLASS)*-1);
                $appcls = Cls::find("app/$appn");
            } else {
                $appcls = null;
            }
            //检查应用类是否存在
            if (Is::nemstr($appcls) && class_exists($appcls)) {
                //应用名称 全小写下划线_ 形式
                $appk = Str::snake($appn,"_");
                //此应用的 所有可用操作
                $aoprs = [
                    "apis" => [],
                    "views" => [],
                ];

                //收集 应用类中定义的 可用操作
                $aoprs = self::getAppOprs($appcls, $aoprs);
                //收集 应用路径下的 route.php 路由表 中定义的 自定义路由操作
                $aoprs = self::getAppRouteOprs($appcls, $aoprs);
                if (empty($aoprs["apis"]) && empty($aoprs["views"])) continue;

                //合并到 self::$all[$appk]
                self::setAppOprs($appk, $aoprs);
                //self::$all[$appk] = Arr::extend(self::$all[$appk],$aoprs);
            }
        }
        closedir($ph);
        
        return true;
    }

    /**
     * TODO:
     * init 方法
     * 收集 所有模块的 可用操作
     * @return Bool
     */
    protected static function initModuleOprs()
    {
        //TODO:

        return true;
    }

    /**
     * 收集 某个 App 应用类的 所有可用操作
     * @param String $appcls 应用类全称
     * @param Array $oprs 已经收集到的 操作列表
     * @return Array 合并后的 此应用的 可用操作列表
     *  [
     *      "apis" => [ "api_name", "api_name", ... ],
     *      "views" => [ "view_name", "view_name", ... ],
     * 
     *      "操作标识" => [ 操作信息 ... ],
     *      ...
     *  ]
     */
    public static function getAppOprs($appcls, $oprs=null)
    {
        //规范输出的数据
        if (!Is::nemarr($oprs)) {
            $oprs = [
                "apis" => [],
                "views" => [],
            ];
        }
        
        //应用类 必须存在
        if (!class_exists($appcls)) return $oprs;

        //应用名称 类名 FooBar
        $appn = $appcls::clsn();
        //应用名称 文件夹名称 foo_bar
        $appk = $appcls::clsk();
        //应用的说明，在应用类中 定义的 intr 属性
        $appintr = Cls::ref($appcls)->getDefaultProperties()["intr"] ?? Str::camel($appn, true);

        //遍历 类中定义的 特定方法
        //api 方法
        $apis = Cls::specific(
            $appcls,
            "public,&!static",
            "api",
            null,
            function($mi, $conf) use ($appn,$appk,$appintr) {
                //附加 uac 信息到 操作信息数组
                //!! 将生成操作标识
                $conf = Cls::parseMethodInfoWithUac($mi, $conf, "api/$appk", $appintr);
                //附加 export 参数
                $conf["export"] = "api";
                return $conf;
            }
        );
        //view 方法
        $views = Cls::specific(
            $appcls,
            "public,&!static",
            "view",
            null,
            function($mi, $conf) use ($appn,$appk,$appintr) {
                //附加 uac 信息到 操作信息数组
                //!! 将生成操作标识
                $conf = Cls::parseMethodInfoWithUac($mi, $conf, "view/$appk", $appintr);
                //附加 export 参数
                $conf["export"] = "view";
                return $conf;
            }
        );

        //返回 收集到的 操作列表
        foreach ($apis as $fn => $fc) {
            if (!isset($fc["oprn"]) || !Is::nemstr($fc["oprn"])) continue;
            $oprs["apis"][] = $fn;
            $oprs[$fc["oprn"]] = $fc;
        }
        foreach ($views as $fn => $fc) {
            if (!isset($fc["oprn"]) || !Is::nemstr($fc["oprn"])) continue;
            $oprs["views"][] = $fn;
            $oprs[$fc["oprn"]] = $fc;
        }
        return $oprs;
    }

    /**
     * 收集 定义在某个 App 应用路径下的 route 路由表中的 自定义路由操作
     * 路由表文件：APP_PATH/app_name/library/route.php 
     * @param String $appcls 应用类全称
     * @param Array $oprs 已经收集到的 操作列表
     * @return Array 合并路由操作之后的 此应用的 可用操作列表
     *  [
     *      "apis" => [ "api_name", "api_name", ... ],
     *      "views" => [ "view_name", "view_name", ... ],
     *      "操作标识" => [ 操作信息 ... ],
     *      ...
     *  ]
     */
    public static function getAppRouteOprs($appcls, $oprs=null)
    {
        //规范输出的数据
        if (!Is::nemarr($oprs)) {
            $oprs = [
                "apis" => [],
                "views" => [],
            ];
        }

        //应用类 必须存在
        if (!class_exists($appcls)) return $oprs;

        //应用名称 类名 FooBar
        $appn = $appcls::clsn();
        //应用名称 文件夹名称 foo_bar
        $appk = $appcls::clsk();
        //应用的说明，在应用类中 定义的 intr 属性
        $appintr = Cls::ref($appcls)->getDefaultProperties()["intr"] ?? Str::camel($appn, true);

        //获取 可能存在的 自定义 路由表
        $rf = Path::find("app/$appk/route.php");
        //没有自定义路由表，直接返回
        if (empty($rf)) return $oprs;

        //读取 路由表
        $routes = require($rf);
        if (!Is::nemarr($routes)) return $oprs;

        //合并到 oprs
        foreach ($routes as $pattern => $oprc) {
            //写入固定的 操作信息
            $oprc["class"] = $appcls;

            //处理边界情况
            //路由信息中定义了 oprn 操作标识
            if (isset($oprc["oprn"])) {
                //路由操作 指向 某个操作标识
                $oprn = $oprc["oprn"];
                if (isset($oprs[$oprn])) {
                    //在已有操作中 找到 与此路由 关联的 操作信息，更新操作信息
                    $oprs[$oprn]["route"] = $pattern;
                } else {
                    //未找到关联的 操作，表示这个路由操作 是新的操作，添加到 已有操作列表中
                    $oprname = explode(":", $oprn)[1];
                    $oprs[$oprn] = Arr::extend([
                        "export" => "api",  //自定义路由操作 默认作为 api
                        "auth" => true,
                        "role" => "all",
                        "route" => $pattern
                    ], $oprc, [
                        "name" => Str::snake($oprname, "_"),
                        "desc" => $appintr."：".($oprc["desc"] ?? ($oprc["title"] ?? Str::camel($oprname, false)."路由")),
                    ]);
                    //export 类型 api|view
                    $exp = $oprs[$oprn]["export"];
                    $oprs[$exp."s"][] = $oprname;
                }
                continue;
            }
            //路由操作 指向 某个应用类方法
            if (isset($oprc["method"]) && Is::nemstr($oprc["method"])) {
                //路由操作 指向 某个应用类方法
                //在 已有操作中查找
                foreach ($oprs as $k => $c) {
                    if (isset($c["method"]) && $c["method"]===$oprc["method"]) {
                        //在已有操作中 找到 与此路由 关联的 操作信息，更新操作信息
                        $oprs[$k]["route"] = $pattern;
                        break;
                    }
                }
                continue;
            }

            //使用通用的 路由信息解析方法
            $oprc = self::parseRoute($pattern, $oprc/*, $appcls*/);
            if (!Is::nemarr($oprc)) continue;
            $oprn = $oprc["oprn"];
            $expt = $oprc["export"];
            $name = $oprc["name"];
            $oprs[$expt."s"][] = $name;
            $oprs[$oprn] = $oprc;
        }

        return $oprs;
    }

    /**
     * 将收集到的 应用操作列表 合并到 self::$all[$appk]
     * @param String $app 应用名称 foo_bar 或 FooBar 形式
     * @param Array $oprs 当前收集到的 操作列表 [ "apis"=>[], "views"=>[], "操作标识"=>[操作信息], ... ] 
     * @return Array 合并后的 self::$all[$appk] 数组
     */
    public static function setAppOprs($app, $oprs=[])
    {
        if (!Is::nemstr($app)) return null;
        //确保 app 为 foo_bar 形式
        $appk = Str::snake($app,"_");
        //当前已保存的 应用操作列表
        $aoprs = self::$all[$appk] ?? [];

        //如果未指定要合并的 数据，直接返回已保存的 操作列表
        if (!Is::nemarr($oprs)) return $aoprs;
        if (!Is::nemarr($aoprs)) {
            //如果当前没有已保存的 操作列表，直接写入
            self::$all[$appk] = $oprs;
            return $oprs;
        }

        //合并 
        //标准 操作列表格式
        $doprs = [
            "apis" => [],
            "views" => [],
        ];
        $oprs = Arr::extend($doprs, $oprs);
        self::$all[$appk] = Arr::extend($aoprs, $oprs);

        return self::$all[$appk];
    }

    /**
     * 输出所有可用的 操作列表
     * @return Array
     *  [ 
     *      "操作标识" => [ 操作信息 ... ], 
     *      ... 
     *  ]
     */
    public static function defines()
    {
        //Operation 类必须已初始化
        if (self::$isInited!==true || !Is::nemarr(self::$all)) return [];

        $all = self::$all;
        $defs = [];
        foreach ($all as $appk => $opsc) {
            if (!Is::nemarr($opsc)) continue;
            foreach ($opsc as $k => $c) {
                if (!Is::nemarr($c) || !isset($c["oprn"])) continue;
                $defs[$k] = $c;
            }
        }
        return $defs;
    }

    /**
     * 从 所有可用操作中，获取 带有 路由正则的 操作
     * 可输出为 静态路由表
     * @return Array
     *  [
     *      "路由正则" => [ 操作信息 ... ],
     *      ...
     *  ]
     */
    public static function routes()
    {
        //Operation 类必须已初始化
        if (self::$isInited!==true || !Is::nemarr(self::$all)) return [];

        //获取全部 可用的 操作列表
        $defs = self::defines();
        if (!Is::nemarr($defs)) return [];

        //准备 routes
        $routes = [];
        //遍历 可用操作列表，找出 带有 route 属性的操作
        foreach ($defs as $oprn => $oprc) {
            if (!Is::nemarr($oprc)) continue;
            if (isset($oprc["route"]) && Is::nemstr($oprc["route"])) {
                $pattern = $oprc["route"];
                $routes[$pattern] = $oprc;
            }
        }

        return $routes;
    }

    /**
     * 输出所有可用的 Closure 匿名方法
     */
    public static function closures()
    {
        //Operation 类必须已初始化
        if (self::$isInited!==true || !Is::nemarr(self::$closure)) return [];

        //遍历
        $closure = self::$closure;
        $defs = [];
        foreach ($closure as $oprn => $fn) {
            if (!$fn instanceof \Closure) continue;
            $defs[$oprn] = $fn;
        }
        return $defs;
    }

    /**
     * 解析预定义的 路由操作信息
     * @param String $pattern 路由正则
     * @param Array $oprc 操作信息
     * @return Array|null 返回解析后的 操作信息数组
     *  [
     *      "oprn" => ...,
     *      "name" => ...,
     *      "class" => ...,
     * 
     *      !! 如果此路由操作的 method 是 匿名函数，则在 Operation::closures[] 数组中保存这个匿名函数，键名为 操作标识
     *      "method" => "__closure__",
     * 
     *      ...
     *  ]
     */
    protected static function parseRoute($pattern, $oprc=[])
    {
        //参数排错
        if (!Is::nemstr($pattern) || substr($pattern, 0,1)!=="/") return null;

        //此路由操作的 默认 操作信息
        $dftoprc = [
            "export" => "api",  //自定义路由操作 默认作为 api
            "auth" => true,
            "role" => "all",
            "route" => $pattern
        ];
        //填充 oprc
        $oprc = array_merge($dftoprc, $oprc);

        //针对定义在全局的路由，或者 定义在 App 下且定义了 匿名函数 method 的路由
        //必须的 操作参数
        $name = $oprc["name"] ?? md5($pattern);
        $desc = $oprc["desc"] ?? ($oprc["title"] ?? null);
        $clsn = $oprc["class"] ?? null;
        $mthd = $oprc["method"] ?? null;
        $expt = $oprc["export"] ?? "api";
        if (
            !Is::nemstr($name) || /*!Is::nemstr($desc) ||*/
            !Is::nemstr($clsn) || empty(Cls::find($clsn)) ||
            !(Is::nemstr($mthd) || is_callable($mthd))
        ) {
            //路由操作参数 不合规
            return null;
        }
        $clsn = Cls::find($clsn);
        if (
            !is_subclass_of($clsn, App::class) ||
            (Is::nemstr($mthd) && !method_exists($clsn, $mthd))
        ) {
            //指定的类不是应用类  或  指定的类方法 不存在于 应用类中
            return null;
        }

        //获取应用类信息
        $appn = $clsn::clsn();
        $appk = $clsn::clsk();
        $appintr = Cls::ref($clsn)->getDefaultProperties()["intr"] ?? Str::camel($appn, true);
        //操作信息
        $namek = Str::snake($name,"_");     //foo_bar
        $name = Str::camel($namek, false);  //fooBar
        //操作标识
        $oprn = "$expt/$appk:$namek";
        $desc = $appintr."：".(Is::nemstr($desc) ? $desc : $name."路由");

        //修订 操作信息
        $oprc = Arr::extend($oprc, [
            "oprn" => $oprn,
            "name" => $namek,
            "class" => $clsn,
            "desc" => $desc,
        ]);

        //如果 $mthd 是匿名函数，则在 Operation::closures 数组中缓存这个 函数
        //if (is_callable($mthd)) {
        if ($mthd instanceof \Closure) {
            self::$closure[$oprn] = $mthd;
            //将 操作信息中的 method 替换为 __closure__
            $oprc["method"] = "__closure__";
        }

        //返回解析后的 操作信息
        return $oprc;
    }



    /**
     * 每个 App 应用类实例 都对应一个 操作类实例
     * 定义 App 应用操作类实例的 属性 | 方法
     */
    //关联的 App 实例
    public $app = null;

    //此 App 的所有可用操作
    protected $context = [
        /*
        "操作标识" => [操作信息 ...],
        ...
        */
    ];
    //此 App 的 api 类型操作名称数组
    protected $apis = [
        /*
        "foo_bar", ...
        */
    ];
    //此 App 的 view 类型操作名称数组
    protected $views = [
        /*
        "foo_bar", ...
        */
    ];

    /**
     * 构造
     * 在 App 应用类实例化时，同时也要实例化对应的 操作类
     * @param App $app 关联的 App 应用实例
     * @return void
     */
    public function __construct($app)
    {
        if (!$app instanceof App) {
            throw new CoreExcetpion("无法创建应用操作实例", "singleton/instantiate");
        }
        $this->app = $app;
        //从 Operation::$all 中读取对应 app 的相关操作
        $appk = $app::clsk();
        $all = self::$all;
        if (isset($all[$appk])) {
            //存在 此 App 相关的 操作列表，填充到 操作类实例中
            $oprs = $all[$appk];
            $this->apis = $oprs["apis"] ?? [];
            $this->views = $oprs["views"] ?? [];
            foreach ($oprs as $k => $v) {
                if (in_array($k, ["apis","views"])) continue;
                $this->context[$v["name"]] = $v;
            }
        }

    }

}