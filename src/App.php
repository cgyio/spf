<?php
/**
 * 框架核心类
 * 应用类基类，抽象类
 * 所有业务功能都应通过此类 来实现
 */

namespace Spf;

use Spf\exception\AppException;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Url;

abstract class App extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    /**
     * 应用的元数据
     * !! 实际应用类必须覆盖
     */
    //应用的说明信息
    public $intr = "";
    //应用的名称 类名 FooBar 形式
    public $name = "";



    /**
     * 获取 App 应用类 对应的 config 配置类 类全称
     * !! 覆盖父类
     * @return String|null 类全称
     */
    protected function getConfigCls()
    {
        //当前应用的 类名 FooBar 形式
        $clsn = static::clsn();
        //当前应用的 路径名 foo_bar
        $clsk = static::clsk();
        //应用配置类 类名 AppFooBarConfig
        $cfgn = "App".$clsn."Config";

        //查找 参数配置类的 类全称
        $cfgcls = null;
        //在 框架默认的 应用路径下查找
        $cfgcls = Cls::find("app/$clsk/$cfgn");
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //默认路径下，没有此应用的 配置类，则使用 AppConfig 类，此类一定存在
            $cfgcls = Cls::find("config/AppConfig", "Spf\\");
        }
        return (!empty($cfgcls) && class_exists($cfgcls)) ? $cfgcls : null;
    }

    /**
     * 此 App 应用类自有的 init 方法，执行以下操作：
     *  0   实例化参数中的所有 启用的模块
     *  1   初始化参数中的所有 中间件，处理他们的 配置参数，覆盖到 中间件类的 静态属性中
     * !! Core 子类必须实现的，App 子类不要覆盖
     * @return $this
     */
    final public function initialize()
    {
        //当前应用的 类名 路径形式 foo_bar
        $appk = $this::clsk();

        // 0 实例化参数中的所有 启用的模块
        $mods = $this->module;
        var_dump("---- app initialize ----");
        var_dump($mods);
        foreach ($mods as $modk => $modc) {
            //确认启用此模块
            if (!isset($modc["enable"]) || $modc["enable"]!==true) continue;
            //获取模块类全称，未获取到则 跳过
            $modcls = Module::has($modk);
            if ($modcls === false) continue;
            //实例化 模块
            $modcls::current($modc);
        }

        return $this;
    }



    /**
     * !! 在框架启动阶段，App 应用实例还未创建之前，执行 整站应用的初始化
     * 需要传入 框架启动参数 route|operation|app|module|middleware 项参数
     */
    //定义 框架启动参数中的 针对 整站所有可用 App 应用的配置参数 默认值
    public static $init = [
        //整站 静态路由表
        "route" => [
            /*
            "/路由正则/" => [
                "name" => "",
                "class" => "",
                "method" => function() {  },
                更多参数项参考 app/Operation 类中的定义
                ...
            ],
            */
        ],

        //整站 所有可用操作管理类 相关参数
        "operation" => [
            //整站所有操作列表的 缓存文件路径
            "cache" => [
                //操作列表 缓存文件路径
                "operation" => "runtime/operation.php",
                //操作列表(路由表)中 定义的 匿名函数 缓存文件路径
                "closure" => "runtime/closure.php"
            ],
        ],

        //每个 App 应用的参数
        "app" => [
            /*
            "app_name" => [
                # 应用参数格式见 config/AppConfig 中的 $dftInit 属性
                !! 此处定义的 参数 将覆盖 app/foo_bar/AppFooBarConfig 类中的定义
                ...
            ],
            ...
            */
        ],

        //整站 全局启用的 Module 模块
        "module" => [
            /*
            "module_name" => [
                # 模块参数格式见 config/ModuleConfig 中的 $dftInit 属性
                !! 此处定义的 参数 将覆盖 module/[app_name/]foo_bar/ModuleFooBarConfig 类中的定义
                ...
            ],
            */
        ],

        //整站 全局启用的 Middleware 中间件
        "middleware" => [
            //框架启动阶段，Request|App 实例都未创建时 执行的
            "init" => [
                //中间件类全称 数组
            ],
            //入站中间件，Request|App 实例已创建，Response 响应实例还未创建
            "in" => [],
            //出站中间件，响应方法已经执行完，Response 响应实例已创建
            "out" => [],
        ],
    ];

    //当前请求的 路由匹配结果
    public static $runtime = [
        //当前请求的 App 应用类全称
        "app" => "",
        //当前请求的 响应方法 操作信息数组，在 Operation::$context[app_name][] 中
        "operation" => [],
        //当前请求的 响应方法的 参数数组
        "args" => [],
        //解析后剩余的 URI 路径
        "uri" => [],
    ];

    /**
     * 整站 App 应用初始化 入口
     *  0   初始化所有可用应用的 Operation 操作列表
     *  1   初始化所有可用的 Route 静态路由表
     * @param Array $opt 框架启动参数，格式见 App::$opt
     * @return Bool
     */
    final public static function prepare($opt=[])
    {
        //获取 框架启动参数中的 app 参数项
        $opt = Arr::choose($opt, "route", "operation", "app", "module", "middleware");
        //使用默认值填充
        self::$init = Arr::extend(self::$init, $opt);

        //初始化 Operation 可用操作列表 以及 路由表数据
        $opt = Arr::choose(self::$init, "route", "operation","module");
        if (Operation::$isInited !== true) Operation::initOprs($opt);

        //将生成的 路由表数据，写入 Route::$context
        Operation::routes();

        return true;
    }

    /**
     * 使用 路由表匹配请求的 url，得到请求的 App 应用类全称
     * 如果路由没有结果，则解析 URI 
     * 得到的结果 保存在 App::$runtime 中
     * @return String 匹配得到的 App 应用类全称
     */
    final public static function find()
    {
        $appcls = null;

        try {

            //调用 Route::match 方法
            $res = Route::match();
            if (Is::nemarr($res)) {
                //匹配到结果，保存到 App::$runtime
                self::$runtime = Arr::extend(self::$runtime, $res);
                return self::$runtime["app"];
            }

            //路由没有匹配的结果，开始解析 URI 路径
            $url = Url::current();
            $path = $url->path;

            /**
             * URI = /app_name/...
             * 指向 app/AppName 应用
             */
            if (Is::nemarr($path)) {
                $appcls = self::has($path[0]);
                if ($appcls!==false) {
                    //得到匹配的 App 应用
                    self::$runtime["app"] = $appcls;
                    //截取 URI
                    array_shift($path);
                    self::$runtime["uri"] = $path;
                    return $appcls;
                }
            }

            /**
             * URI = ""  或  /method_name/...
             * 指向 app/Index 应用
             */
            //确认存在 app/Index
            $appcls = self::has("index");
            if ($appcls!==false) {
                //得到匹配的 App 应用
                self::$runtime["app"] = $appcls;
                self::$runtime["uri"] = $path;
                return $appcls;
            }

            /**
             * 没有匹配到任何 App 报错
             */
            throw new AppException("URL 未匹配到应用", "route/missapp");

        } catch (AppException $e) {
            //终止响应
            $e->handleException(true);
        }
    }



    /**
     * 静态方法
     */

    /**
     * 获取当前 App 类的 所有可用操作列表，包括 关联的 应用路由表文件
     * !! 覆盖 traits/Operation 中的方法
     * @return Array 标准的 操作列表数据格式
     */
    public static function getOprs()
    {
        //调用 父类的 方法，获取 类中定义的 特殊操作方法，得到 标准的 操作列表
        $oprs = parent::getOprs();

        //应用的 属性
        $cls = static::class;
        //$clsn = static::clsn();
        $clsk = static::clsk();
        //操作标识前缀
        //$pre = $clsk;
        //操作说明前缀
        //$intr = Cls::ref($cls)->getDefaultProperties()["intr"] ?? $clsn;

        //处理 应用内的 路由表文件
        $ruf = Path::find("app/$clsk/route.php", Path::FIND_FILE);
        if (!empty($ruf)) {
            $roprs = [];
            $routes = require($ruf);
            foreach ($routes as $pattern => $oprc) {
                //写入固定的 操作信息
                $oprc["class"] = $cls;

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
                            "desc" => $intr."：".($oprc["desc"] ?? ($oprc["title"] ?? Str::camel($oprname, false)."路由")),
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
                
                //当定义的 method 是 匿名函数时 使用通用的 路由信息解析方法
                $oprc = Router::parseRoute($pattern, $oprc);
                if (!Is::nemarr($oprc)) continue;
                $oprn = $oprc["oprn"];
                $expt = $oprc["export"];
                $name = $oprc["name"];
                $oprs[$expt."s"][] = $name;
                $oprs[$oprn] = $oprc;
            }
        }

        //返回找到的 操作列表
        return $oprs;
    }

    /**
     * 判断 $app 应用是否存在
     * @param String $app 应用名称 FooBar 或 foo_bar 形式
     * @return String|false 类全称，未找到 则返回 false
     */
    final public static function has($app)
    {
        if (!Is::nemstr($app)) return false;

        //先判断一次
        if (class_exists($app) && is_subclass_of($app, App::class)) return $app;

        //路径名形式 foo_bar
        $appk = Str::snake($app, "_");
        //类名形式 FooBar
        $appn = Str::camel($appk, true);
        //应用类文件必须存在
        $appcls = Cls::find("app/$appn");
        if (Is::nemstr($appcls) && class_exists($appcls)) return $appcls;

        return false;
    }
}