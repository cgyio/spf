<?php
/**
 * 框架核心类
 * 框架 路由管理类
 * 
 * 框架初始化阶段，执行以下操作：
 *      收集 框架全局 所有应用的可用操作|全局启用模块的可用操作 汇总为操作列表，并生成路由表数据，缓存这些数据
 *      查找本次会话请求的 App 应用类
 * 
 * 在 App 应用实例创建后：
 *      收集 当前应用中 所有启用模块的可用操作 更新操作列表，路由表，缓存这些数据
 *      根据更新后的 路由数据 匹配 当前会话请求的 响应方法
 * 
 * !! 此类禁止被继承
 */

namespace Spf;

use Spf\Core;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Cache;
use Spf\util\Url;

final class Router extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    /**
     * 收集汇总 生成的 操作列表 
     * 此列表可能在 框架运行过程中被修改，
     * !! 如果 运行时缓存 开启，此列表会被缓存
     * !! 静态属性
     */
    protected static $context = [
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

    /**
     * 收集到的 匿名函数 操作 method
     * 通常是在自定义 路由操作时，会指定 匿名函数 作为 操作的 method
     * 以 操作标识 为键名
     * !! 如果 运行时缓存 开启，此列表会被缓存
     * !! 静态属性
     */
    protected static $closure = [
        /*
        "操作标识" => function(...) { ... },
        ...
        */
    ];



    /**
     * 从传入的 $opt 数组中选取部分作为 Router 类的 启动参数
     * !! 覆盖父类
     * @param Array $opt 传入的框架启动参数，通过 核心类的 current 方法传入的
     * @return Array 选取后的 此核心类的 启动参数
     */
    protected function fixOpt($opt=[])
    {
        $rut = $opt["route"] ?? [];
        $mod = $opt["module"] ?? [];

        //收集 框架启动参数中 全局启用模块名 列表，以便在 Router 是实例化后，收集这些 模块的 操作列表
        $rut["modules"] = [];
        foreach ($mod as $modk => $modc) {
            if (!isset($modc["enable"]) || $modc["enable"]!==true) continue;
            $rut["modules"][] = $modk;
        }

        return $rut;
    }

    /**
     * 此 Router 类自有的 init 方法，执行以下操作：
     *  0   尝试读取缓存，取得缓存的 操作列表 数据，如果没有缓存数据，则开始收集 下列位置中 可能存在的 操作信息
     *          框架启动参数中定义的路由 | 框架全局路由表文件 
     *          执行全局所有 App 应用的 操作列表获取方法，收集的到的 操作列表
     *          执行模块自定义的 操作列表获取方法，收集的到的 操作列表
     *  1   根据收集到的 路由表数据，匹配并查找 得到 当前请求的 App 应用类，找到则缓存到 static::$app
     * !! Core 子类必须实现的
     * @return $this
     */
    final public function initialize()
    {
        // 0 首先尝试缓存文件
        if (true === $this->readCache()) {
            //缓存读取成功，且已写入 $context|$closure
            return true;
        }
        /**
         * 未获取到缓存，可能 缓存已过期 或 未开启 WEB_CACHE
         * 开始 收集 操作列表
         */
        //收集 全局路由 操作
        $this->initRoutes();
        //收集 所有应用的 可用操作
        $this->initAppOprs();
        //收集 所有全局启用模块的 可用操作
        $this->initModuleOprs();
        //将得到的 操作列表 写入 缓存
        $this->saveCache();

        // 1 匹配当前请求的 App 应用类
        if (App::$isInsed !== true) {
            $app = static::matchApp();
            if (is_subclass_of($app, App::class)) {
                static::$app = $app;
            }
        }
        
        return $this;
    }



    /**
     * 缓存相关 方法
     */

    /**
     * 读取缓存文件，写入 $context|$closure
     * @return Bool 是否读取并写入成功
     */
    public function readCache()
    {
        //获取缓存文件路径
        $cof = $this->config->ctx("cache/operation");
        $ccf = $this->config->ctx("cache/closure");
        //读取预设的 缓存文件
        $ctx = Cache::read($cof);
        $closure = Cache::read($ccf);
        //写入 静态属性
        if (Is::nemarr($ctx)) {
            static::$context = $ctx;
            if (Is::nemarr($closure)) self::$closure = $closure;
            return true;
        }
        return false;
    }

    /**
     * 将解析得到的 $context|$closure 数据写入缓存
     * @return Bool 
     */
    public function saveCache()
    {
        //获取缓存文件路径
        $cof = $this->config->ctx("cache/operation");
        $ccf = $this->config->ctx("cache/closure");
        //写入缓存
        Cache::save($cof, static::$context);
        if (Is::nemarr(self::$closure)) Cache::saveClosure($ccf, self::$closure);
        return true;
    }

    /**
     * 判断当前的 操作列表 是否来自 缓存
     * @return Bool
     */
    public function isCached()
    {
        return Cache::isCached(static::$context);
    }



    /**
     * init 方法
     */

    /**
     * 收集 手动定义的 全局路由 操作
     * 通常定义在：
     *      启动参数的 route 项
     *      全局路由表文件中
     * @param Array $opr 启动参数中的 route 项
     * @return Bool
     */
    protected function initRoutes()
    {
        //准备 路由表
        $routes = [];
        //首先尝试读取 全局路由表
        $ruf = $this->config->ctx("file");
        $ruf = Path::find($ruf, Path::FIND_FILE);
        if (!empty($ruf)) {
            //读取 全局路由表
            $routes = require($ruf);
        } 
        //与 启动参数中的 route 项 合并，参数中的 覆盖 route.php 中的
        $opt = $this->config->ctx("routes");
        $routes = Arr::extend($routes, $opt);

        //标准操作列表数据格式
        $dftOprs = $this->config->ctx("oprs");

        /**
         * 遍历路由表，将 路由操作 添加到对应的 应用的 操作列表中
         * !! 全局路由必须完整定义 name|[title|desc]|class|method
         * !! class 指向某个 应用类全称
         * !! method 指向 class 类方法  或者  自定义的函数
         */
        //所有找到的 操作列表
        $oprs = [];
        foreach ($routes as $pattern => $oprc) {
            //处理这个 路由定义
            $oprc = self::parseRoute($pattern, $oprc);
            if (!Is::nemarr($oprc)) continue;
            $name = $oprc["name"];
            $oprn = $oprc["oprn"];
            $clsn = $oprc["class"];
            $expt = $oprc["export"];
            $clsk = $clsn::clsk();

            if (!isset($oprs[$clsk])) $oprs[$clsk] = Arr::copy($dftOprs);
            $oprs[$clsk][$expt."s"][] = Str::snake($name, "_");
            $oprs[$clsk][$oprn] = $oprc;
        }

        //将找到的操作列表写入 $context
        return static::setContext($oprs);
    }

    /**
     * 收集 所有 应用的 可用操作，通过调用 App::getOprs
     * @return Bool
     */
    protected function initAppOprs()
    {
        $path = defined("APP_PATH") ? APP_PATH : null;
        if (!Is::nemstr($path) || !is_dir($path)) {
            //应用路径还未定义
            return false;
        }

        //找到的 操作列表
        $oprs = [];

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
            if (!Is::nemstr($appcls) || !class_exists($appcls) || is_subclass_of($appcls, App::class)!==true) {
                continue;
            }
            //应用名 FooBar
            $appn = Str::camel($appn, true);
            //应用名称 全小写下划线_ 形式
            $appk = Str::snake($appn,"_");

            //调用 App 类的 getOprs 方法
            $aoprs = $appcls::getOprs();

            //写入 oprs
            if (!isset($oprs[$appk])) $oprs[$appk] = static::dftOprs();
            $oprs[$appk] = Arr::extend($oprs[$appk], $aoprs);
        }
        closedir($ph);
        
        //写入 context
        return static::setContext($oprs);
    }

    /**
     * 收集 所有 全局启用模块的 可用操作，通过调用 Module::getOprs
     * @return Bool
     */
    protected function initModuleOprs()
    {
        //全局启用的 模块名
        $mods = $this->config->ctx("modules");
        if (!Is::nemarr($mods)) return false;

        //找到的 操作列表
        $oprs = [];
        foreach ($mods as $modk) {
            $modcls = Module::has($modk);
            if ($modcls===false) continue;
            //调用 模块的 getOprs 方法
            $moprs = $modcls::getOprs();
            
            //合并到 oprs
            $oprs = Arr::extend($oprs, $moprs);
        }

        //先处理 匿名函数
        $oprs = static::setClosure($oprs);
        
        if (App::$isInsed !== true) {
            //如果 App 应用实例还未创建，则把这些操作写入 所有 应用的下
            $apps = array_keys(static::$context);
            $noprs = [];
            foreach ($apps as $appk) {
                $noprs[$appk] = $oprs;
            }
        } else {
            //App 应用实例已创建，将这些操作写入 当前 app 名下
            $appk = App::$current::clsk();
            $noprs = [
                $appk => $oprs
            ];
        }

        //写入 context
        return static::setContext($noprs, false);
    }
    



    /**
     * 路由类 静态方法
     */

    /**
     * 将 操作列表数据 写入 Router::$context
     * 如果存在 匿名函数，则缓存到 Router::$closure
     * @param Array $oprs 要写入的 操作列表 格式为：
     *      [
     *          "app_name" => [
     *              # 标准的操作列表数据格式
     *              "apis" => [],
     *              "views" => [],
     *              "oprn" => [ 操作信息 ... ],
     *              ...
     *          ],
     *          ...
     *      ]
     * @param Bool $handleClosure 是否处理 匿名函数，默认 true
     * @return Bool
     */
    public static function setContext($oprs=[], $handleClosure=true)
    {
        if (!Is::nemarr($oprs)) return false;
        $ctx = static::$context;
        foreach ($oprs as $appk => $aoprs) {
            //处理 匿名函数
            if ($handleClosure === true) $aoprs = static::setClosure($aoprs);

            //合并
            if (!isset($ctx[$appk])) $ctx[$appk] = static::dftOprs();
            $ctx[$appk] = Arr::extend($ctx[$appk], $aoprs);
        }

        //写入
        static::$context = $ctx;

        return true;
    }

    /**
     * 处理 标准操作列表中 可能存在的 匿名函数
     * @param Array $oprs 标准操作列表
     * @return Array 处理后的 操作列表，匿名函数已被缓存，并替换为 __closure__ 字符
     */
    public static function setClosure($oprs=[])
    {
        if (!Is::nemarr($oprs)) return [];
        //处理后的 oprs
        $noprs = [];
        foreach ($oprs as $oprn => $oprc) {
            if (!Is::nemarr($oprc)) continue;
            if (!Is::associate($oprc)) {
                $noprs[$oprn] = $oprc;
                continue;
            }

            //处理 method 是 匿名函数的 情况
            $m = $oprc["method"] ?? null;
            if (is_null($m)) continue;
            if ($m instanceof \Closure) {
                //缓存
                static::$closure[$oprn] = $m;
                $oprc["method"] = "__closure__";
            }

            $noprs[$oprn] = $oprc;
        }
        //返回处理后的 oprs
        return $noprs;
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
     *      "method" => 类方法名 | Closure 匿名函数,
     * 
     *      ...
     *  ]
     */
    public static function parseRoute($pattern, $oprc=[])
    {
        //参数排错
        if (!Is::nemstr($pattern) || substr($pattern, 0,1)!=="/" || substr($pattern, -1)!=="/") return null;

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
        //!! 自定义的 路由 必须指向 某个 应用类
        if (empty($clsn) || !is_subclass_of($clsn, App::class)) {
            //路由中指定的类 必须是某个 应用类
            return null;
        }
        if (Is::nemstr($mthd) && !method_exists($clsn, $mthd)) {
            //指定的类方法 不存在于 指定的类中
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

        //返回解析后的 操作信息
        return $oprc;
    }

    
    /**
     * 输出所有可用的 操作列表
     * @param String $app 指定查找某个 应用的 操作列表 不指定则查找全部，默认 null;
     * @return Array
     *  [ 
     *      "操作标识" => [ 操作信息 ... ], 
     *      ... 
     *  ]
     */
    public static function defines($app=null)
    {
        if (!Is::nemarr(static::$context)) return [];
        $ctx = static::$context;
        $defs = [];

        //查找某个 应用的 全部操作
        if (Is::nemstr($app)) {
            if (App::has($app)===false) return [];
            $appk = Str::snake($app, "_");
            if (!isset($ctx[$appk]) || !Is::nemarr($ctx[$appk])) return [];

            foreach ($ctx[$appk] as $k => $c) {
                if (!Is::nemarr($c) || !isset($c["oprn"])) continue;
                $defs[$k] = $c;
            }

            return $defs;
        }
        
        //查找全部操作
        foreach ($ctx as $appk => $opsc) {
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
     * @param String $app 指定查找某个 应用的 操作列表 不指定则查找全部，默认 null;
     * @return Array
     *  [
     *      "/路由正则/" => [ 操作信息 ... ],
     *      ...
     *  ]
     */
    public static function routes($app=null)
    {
        if (!Is::nemarr(static::$context)) return [];

        //获取全部 可用的 操作列表
        $defs = self::defines($app);
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
     * @param String $app 指定查找某个 应用的 操作列表 不指定则查找全部，默认 null;
     * @return Array
     *  [ 
     *      "操作标识" => Closure, 
     *      ... 
     *  ]
     */
    public static function closures($app=null)
    {
        if (!Is::nemarr(static::$context) || !Is::nemarr(static::$closure)) return [];
        $ctx = static::$context;
        $clo = static::$closure;

        //查找某个应用下的 匿名函数
        if (Is::nemstr($app)) {
            if (App::has($app) === false) return [];
            $appk = Str::snake($app,"_");
            if (!isset($cts[$appk]) || !Is::nemarr($ctx[$appk])) return [];
            $oprcs = array_values($ctx[$appk]);
            $oprcs = array_filter($oprcs, function($oprc) {
                return Is::nemarr($oprc) && Is::associate($oprc) && isset($oprc["oprn"]);
            });
            $clos = array_map(function($oprc) use ($clo) {
                $oprn = $oprc["oprn"];
                return $clo[$oprn] ?? null;
            }, $oprcs);
            $clos = array_filter($clos, function($cli) {
                return $cli instanceof \Closure;
            });
            return $clos;
        }

        //遍历
        $defs = [];
        foreach ($clo as $oprn => $fn) {
            if (!$fn instanceof \Closure) continue;
            $defs[$oprn] = $fn;
        }
        return $defs;
    }

    /**
     * 使用 路由表匹配请求的 url，得到请求的 App 应用类全称
     * @return String 得到 当前请求的 App 应用类全称
     */
    public static function matchApp()
    {
        //解析请求 URL
        $url = Url::current();
        //URI 数组
        $path = $url->path;

        //提前判断是否存在 app/Index
        $index = App::has("index");

        /**
         * 空 URI 
         */
        if (empty($path)) {
            if ($index !== false) return $index;
            //TODO: 抛出错误

        }

        /**
         * https://host/app_name/...        默认情况
         */
        if (false !== ($appcls = App::has($path[0]))) return $appcls;

        /**
         * https://host/method_name/...     省略 index 的 情况
         */
        //if ($index !== false && )


        //待匹配的 URI 字符串，不带开头的 /
        $uri = implode("/", $path);
        //空 URI 
        if (!Is::nemstr($uri)) return null;
    }

    /**
     * 使用 路由表匹配请求的 url，得到请求的 App 应用类全称 | 响应方法操作信息 | 响应方法参数
     * @return Array|null
     */
    public static function match()
    {
        //路由表
        $routes = self::ctx();
        if (!Is::nemarr($routes)) $routes = Operation::routes();
        if (!Is::nemarr($routes)) return null;

        //解析请求 URL
        $url = Url::current();
        //URI 数组
        $path = $url->path;
        //待匹配的 URI 字符串，不带开头的 /
        $uri = implode("/", $path);
        //空 URI 
        if (!Is::nemstr($uri)) return null;

        //依次匹配路由
        foreach ($routes as $pattern => $oprc) {
            //使用 正则 匹配 $uri 字符串
            try {
                $mt = preg_match($pattern, $uri, $matches);
                //未匹配成功，继续下一个
                if ($mt !== 1) continue;

                //匹配成功，将 匹配结果 作为 响应方法参数 返回
                $mcs = array_slice($matches, 1);
                $msc = array_map(function($mci) {
                    return trim($mci, "/");
                }, $mcs);
                $mcstr = implode("/", $mcs);
                $mcs = explode("/", $mcstr);

                //检查是否还有剩余的 uri 路径
                $uarr = [];
                if (strpos($uri, $mcstr)!==false) {
                    $uriarr = explode($mcstr, $uri);
                    if (count($uriarr)>1 && $uriarr[1]!="") {
                        $uarr = explode("/", trim($uriarr[1], "/"));
                    }
                }

                //返回匹配结果
                return [
                    "app" => $oprc["class"],
                    "operation" => $oprc,
                    "args" => $mcs,
                    "uri" => $uarr,
                ];
            } catch (\Exception $e) {
                //正则匹配出错 跳过
                continue;
            }
        }

        //没有匹配到结果
        return null;
    }
}