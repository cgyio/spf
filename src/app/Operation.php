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

final class Operation 
{
    /**
     * 收集汇总 生成的 操作列表
     * 此列表可能在 框架运行过程中被修改，
     * 如果 WEB_CACHE 开启，此列表会被缓存
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

                "oprn" => "操作标识，键名，全小写，下划线_ 形式：api/foo_bar:jaz_tom",
                "class" => "此路由操作的 App 应用类全称：NS\app\FooBar",
                "method" => "实际执行的方法名，驼峰，首字母小写，带 -Api|-View 等类型的后缀：jazTomApi",
                "name" => "在对应的 App 应用类参数类实例 context[apis|views|...] 数组中的键名，全小写下划线_形式：jaz_tom",
                "title" => "定义在注释中的此操作的标题：操作jazTom",
                "desc" => "经过处理的用作 操作标识对应的操作说明文字：  应用说明：方法说明",
                "export" => "输出方式，影响创建的 Response 响应实例类型，可选 api|view ：api",
                "auth" => 此路由操作是否启用 Uac 权限控制，默认 true 通常在方法注释中指定：true,
                "role" => "可额外指定此操作的权限角色，在注释中指定，默认 all 多个角色用 , 隔开：all",
                "route" => "可在方法注释中定义 此方法的 路由 正则，将被收集到 路由表中，默认不指定：null"

                # 其他 方法注释中指定的 操作信息
                ...
            ],
            ...
        ],
        ...
        */
    ];

    /**
     * 可用操作的收集与汇总
     * !! 应在 环境变量初始化后，路由解析之前 执行此方法
     * @return Bool
     */
    public static function initOprs()
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

                //写入 self::$all
                self::$all[$appk] = $aoprs;
            }
        }
        closedir($ph);
        
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
            //此路由操作的 默认 操作信息
            $dftoprc = [
                "class" => $appcls,
                "desc" => $appintr."：路由操作",
                "export" => "api",  //自定义路由操作 默认作为 api
                "auth" => true,
                "role" => "all",
                "route" => $pattern
            ];

            if (isset($oprc["oprn"])) {
                //路由操作 指向 某个操作标识
                $oprn = $oprc["oprn"];
                if (isset($oprs[$oprn])) {
                    //在已有操作中 找到 与此路由 关联的 操作信息，更新操作信息
                    $oprs[$oprn]["route"] = $pattern;
                } else {
                    //未找到关联的 操作，表示这个路由操作 是新的操作，添加到 已有操作列表中
                    $oprname = explode(":", $oprn)[1];
                    $oprs[$oprn] = Arr::extend($dftoprc, [
                        "name" => Str::snake($oprname, "_"),
                        "desc" => $appintr."：".Str::camel($oprname, false)."路由",
                    ], $oprc);
                    //export 类型 api|view
                    $exp = $oprs[$oprn]["export"];
                    $oprs[$exp."s"][] = $oprname;
                }
                continue;
            }

            if (isset($oprc["method"])) {
                //路由操作 指向 某个应用类方法 或 自定义方法函数
                $opm = $oprc["method"];
                if (Is::nemstr($opm)) {
                    //路由操作 指向 某个应用类方法
                    //在 已有操作中查找
                    //$found = false;
                    foreach ($oprs as $k => $c) {
                        if (isset($c["method"]) && $c["method"]===$opm) {
                            //在已有操作中 找到 与此路由 关联的 操作信息，更新操作信息
                            $oprs[$k]["route"] = $pattern;
                            //$found = true;
                            break;
                        }
                    }
                    continue;
                } else if (is_callable($opm)) {
                    //路由操作 定义了 一个函数，表示这个路由操作 是新的操作，添加到 已有操作列表中
                    $oprnc = Arr::extend($dftoprc, $oprc);
                    //export 类型 api|view
                    $exp = $oprnc["export"];
                    //操作名称
                    $oprname = md5($pattern);
                    //创建 操作标识 
                    $oprn = "$exp/$appk:".$oprname;
                    //操作说明
                    $oprdesc = $appintr."：".$oprname."路由";
                    //添加到 操作列表
                    $oprs[$oprn] = Arr::extend([
                        "oprn" => $oprn,
                        "name" => $oprname,
                        "desc" => $oprdesc
                    ], $oprnc);
                    $oprs[$exp."s"][] = $oprname;
                    continue;
                }
            }
        }

        return $oprs;
    }

    /**
     * 输出所有可用的 操作列表
     * @return Array [ "操作标识"=>[操作信息 ...], ... ]
     */
    public static function defines()
    {
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