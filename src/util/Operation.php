<?php
/**
 * 框架 特殊工具类
 * 可响应请求的 操作方法 管理
 * 扫描类文件，生成 操作信息列表
 * 
 * 
 * 操作方法、操作标识、请求 URI 存在下述对应关系：
 *      操作方法                                 操作标识                             URI
 *      NS\app\FooBar::jazTomApi()              api/foo_bar:jaz_tom                 /foo_bar/api/jaz_tom/arg1/arg2/...
 *      NS\module\ModFoo::barView()             view/module/mod_foo:bar             /current_app/[mod_foo/]view/bar/arg1/arg2/...
 *      NS\module\app_foo\ModBar::jazSrc()      src/module/app_foo/mod_bar:jaz      /app_foo/[mod_foo/]src/jaz/arg1/arg2/...
 *      
 *      NS\model\db_foo\MdlBar::jazTomApi       api/model/db_foo/mdl_bar:jaz_tom    /current_app/db_foo/mdl_bar/api/jaz_tom/arg1/...
 */

namespace Spf\util;

use Spf\App;
use Spf\Module;
use Spf\exception\CoreException;
use Spf\config\Configer;

class Operation extends SpecialUtil 
{
    /**
     * 此工具 在启动参数中的 参数定义
     *  [
     *      "util" => [
     *          "util_name" => [
     *              # 如需开启某个 特殊工具，设为 true
     *              "enable" => true|false, 是否启用
     *              ... 其他参数
     *          ],
     *      ]
     *  ]
     * !! 覆盖父类静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = true;
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];
    
    /**
     * 可用的 操作类型
     * 不同的操作类型，将 返回不同类型的 Response 响应实例
     */
    public static $types = [
        "api", "view", "src",
    ];

    /**
     * 标准的 App 应用类 操作列表 数据格式
     * 与 $types 对应
     */
    protected static $stdOprs = [
        /*
        "apis" => [ 操作标识1, 操作标识2, ... ],
        "views" => [ ... ],
        ...
        "操作标识1" => [ 标准的 操作信息数组 ... ],
        "操作标识2" => [ ... ],
        ...
        */
    ];

    /**
     * 标准的 操作信息数据格式
     */
    protected static $stdOprc = [
        //操作标识，键名，全小写，下划线_ 形式：api/foo_bar:jaz_tom
        "oprn"      => "",
        //此路由操作的 App 应用类全称：NS\app\FooBar
        "class"     => "",
        //实际执行的方法名，驼峰，首字母小写，带 -Api|-View 等类型的后缀：jazTomApi
        "method"    => "",
        //在对应的 App 应用类参数类实例 context[apis|views|...] 数组中的键名，全小写下划线_形式：jaz_tom
        "name"      => "",
        //定义在注释中的此操作的标题：操作jazTom
        "title"     => "",
        //经过处理的用作 操作标识对应的操作说明文字：  应用说明：方法说明
        "desc"      => "",
        //输出方式，影响创建的 Response 响应实例类型，可选 api|view ：api
        "export"    => "api",
        //此路由操作是否启用 Uac 权限控制，默认 true 通常在方法注释中指定
        "auth"      => true,
        //可额外指定此操作的权限角色，在注释中指定，默认 all 多个角色用 , 隔开：all
        "role"      => "all",
        //可在方法注释中定义 此方法的 路由正则，将被收集到 路由表中，默认不指定：null
        "route"     => null,
        //此方法是否受 WEB_PAUSE 开关影响，false 表示 无论 WEB_PAUSE 是否开启，此方法依然作出响应，默认 true
        "pause"     => true,
        
        //其他 在方法注释中定义的 信息
        //...
    ];

    /**
     * 输出 标准的 操作列表数据格式 默认值
     * @param Array $oprs 要格式化的 操作列表数据( 通过 Cls::specific() 方法生成的数组 )，将与默认值合并
     *  [
     *      "method_name" => [ self::$stdOprc 形式的 操作信息数组... ],
     *      ...
     *  ]
     * @return Array
     */
    public static function getStdOprs($oprs=null)
    {
        if (!Is::nemarr($oprs)) {
            //未指定 要格式化的 操作列表数据，则获取并返回 标准的 操作列表数据
            if (Is::nemarr(self::$stdOprs)) return self::$stdOprs;

            $types = self::$types;
            if (!Is::nemarr($types)) return [];
            foreach ($types as $type) {
                self::$stdOprs[$type."s"] = [];
            }
            return self::$stdOprs;
        }

        //标准 操作列表数组 空值
        $std = Arr::copy(self::getStdOprs());

        /**
         * 指定了 要格式化的 操作列表，则与标准格式数据 合并
         * 
         * 将 通过 Cls::specific() 方法生成的 操作列表信息数组，合并到 标准操作列表中
         *  [
         *      "method_name" => [ self::$stdOprc 形式的 操作信息数组... ],
         *      ...
         *  ]
         * 合并为
         *  [
         *      "apis" => [ 操作标识1, 操作标识2, ... ],
         *      "views" => [ ... ],
         *      ...
         *      "操作标识1" => [ 标准的 操作信息数组 ... ],
         *      "操作标识2" => [ ... ],
         *      ...
         *  ]
         */
        foreach ($oprs as $mn => $oprc) {
            if (true !== self::isStdOprc($oprc)) continue;
            $oprn = $oprc["oprn"];
            $expt = $oprc["export"];
            //写入
            if (!Is::nemarr($std[$expt."s"])) $std[$expt."s"] = [];
            $std[$expt."s"][] = $oprn;
            $std[$oprn] = $oprc;
        }
        return $std;
    }

    /**
     * 输出默认的 操作信息 数据格式
     * @param Array $oprc 要格式化的 操作信息数组，将与 标准的 操作信息数组合并
     * @return Array
     */
    public static function getStdOprc($oprc=null)
    {
        if (!Is::nemarr($oprc)) {
            return self::$stdOprc;
        }

        return Arr::extend(self::$stdOprc, $oprc);
    }

    /**
     * 操作标识解析 得到
     *  [
     *      "class" => 此操作所属 类全称,
     *      "app" => 此操作所属 App 应用，或者 当前已实例化的应用 类全称,
     *      "method" => 方法名 全称带后缀 fooBarApi,
     *      "export" => 此操作 输出的 响应类型 api|view|src...
     *      "uri" => 此操作的 请求 URI
     *  ]
     * @param String $oprn 操作标识
     * @return Array
     */
    public static function parseOprn($oprn)
    {
        if (!Is::nemstr($oprn)) return [];

        $opra = explode(":", $oprn);
        $oprp = empty($opra) ? [] : explode("/", $opra[0]);
        $mthk = $opra[1] ?? null;
        if (empty($oprp) || !Is::nemstr($mthk)) return [];

        //方法类型 必须在 self::$types 中定义了
        $export = $oprp[0] ?? null;
        if (!in_array($export, self::$types)) return [];

        //构造实际 方法名 fooBarApi 形式
        $method = Str::camel($mthk, false).Str::camel($export, true);
        //查找此方法所属的 类全称  view/module/foo  -->  module/foo  |  api/foo  -->  app/foo
        $clsp = implode("/", array_slice($oprp, 1));
        if (count($oprp)<=2) $clsp = "app/".$clsp;
        $class = Cls::find($clsp);
        //方法所属类 必须存在
        if (empty($class)) return [];

        //查找方法所属 App 应用类，未找到 设为 null
        if (Cls::is($class)==="app") {
            //此操作所属类 即 App 应用类
            $app = $class;
        } else if (App::$isInsed === true) {
            //使用当前的应用类
            $app = get_class(App::$current);
        } else {
            //当前没有已实例化的 应用类
            $app = null;
        }

        /**
         * 构造此方法的 请求 URI
         * api/foo_bar:jaz_tom                  --> /foo_bar/jaz_tom/arg1/arg2/...
         * view/module/foo_bar:jaz_tom          --> /current_app/[foo_bar/]jaz_tom/arg1/arg2/...
         * src/module/app_foo/mod_bar:jaz_tom   --> /app_foo/[mod_bar/]jaz_tom/arg1/arg2/...
         */
        if (!Is::nemstr($app)) {
            //要 构造 URI 必须有 $app
            $uri = null;
        } else {
            $uri = [];
            $appn = Cls::name($app);
            $appk = Str::snake($appn, "_");
            $uri[] = $appk;
            if (Cls::is($class)!=="app") {
                //此方法 不是 应用类中的 方法
                $clsn = Cls::name($class);
                $clsk = Str::snake($clsn, "_");
                $uri[] = $clsk;
            }
            $uri[] = $mthk;
            //构造
            $uri = "/".implode("/", $uri);
        }

        //返回
        return [
            "class" => $class,
            "method" => $method,
            "app" => $app,
            "export" => $export,
            "uri" => $uri
        ];

    }

    /**
     * 对 通过解析注释内容 得到的 方法信息，进一步处理 uac 权限控制相关信息
     * 生成标准的 操作信息数据格式 self::$stdOprc 定义的 数据格式
     * !! 此方法将生成 操作标识
     * @param Array $oprc 通过解析注释内容得到的 方法信息数组
     * @param String $oprpre 操作标识前缀
     * @param String $oprtit 操作标识的说明内容前缀
     * @return Array|null 最终生成的 操作信息数组，发生错误 则返回 null
     */
    public static function oprc($oprc=[], $oprpre=null, $oprtit=null)
    {
        if (!Is::nemarr($oprc)) return null;

        //uac 相关
        $auth = $oprc["auth"] ?? true;
        $oprc["auth"] = $auth;
        if ($auth===true) {
            //启用 uac 控制
            $role = $oprc["role"] ?? "all";
            //指定了允许访问的 用户角色，多个角色使用 , 隔开
            if ($role!="all") $role = Arr::mk($role);
            $oprc["role"] = $role;
        }

        //此操作 所在 类 全称，此项目必须存在
        $cls = $oprc["class"] ?? null;
        if (!class_exists($cls)) return null;

        //方法类型，必须存在
        $expt = $oprc["export"] ?? null;
        if (!Is::nemstr($expt)) return null;
        
        //生成 操作标识|操作说明
        if (!Is::nemstr($oprpre)) $oprpre = self::getOprnPrefix($cls, $expt);
        if (!Is::nemstr($oprtit)) $oprtit = self::getOprnTitle($cls);
        $oprc["oprn"] = $oprpre.":".$oprc["name"];
        //修改 方法说明
        $desc = $oprc["desc"] ?? ($oprc["title"] ?? null);
        if (!Is::nemstr($desc)) $desc = Str::camel($oprc["name"],false)."方法";
        $oprc["desc"] = $oprtit."：".$desc;

        //生成 路由正则
        if (!isset($oprc["route"]) || !Is::nemstr($oprc["route"])) {
            $oprc["route"] = self::getOprcPattern($oprc);
        }

        //处理 pause 参数
        if (!isset($oprc["pause"]) || !is_bool($oprc["pause"])) {
            $oprc["pause"] = self::getOprcPause($cls);
        }

        //返回 标准的 操作信息数组
        return self::getStdOprc($oprc);
    }

    /**
     * 解析摸个类中 default 默认操作信息，返回标准的 操作信息数组
     * @param String $cls 要查找 操作方法的 类名，可被 Cls::find() 识别
     * @param String $pre 操作标识 前缀，默认不指定，从 $cls 解析得到
     * @param String $intr 操作说明字符串 前缀，默认不指定，从 $cls 解析得到
     * @return Array|null 最终生成的 操作信息数组，发生错误 则返回 null
     */
    public static function dftOprc($cls, $pre=null, $intr=null)
    {
        //获取类全称
        $cls = Cls::find($cls);
        if (!class_exists($cls)) return null;
        //$clsn = Cls::name($cls);
        //$clsk = Str::snake($clsn, "_");
        //操作标识前缀，不带操作类型
        if (!Is::nemstr($pre)) $pre = self::getOprnPrefix($cls);
        //操作说明前缀
        if (!Is::nemstr($intr)) $intr = self::getOprnTitle($cls);

        //默认操作 default 必须存在
        if (!method_exists($cls, "default")) return null;
        $refm = Cls::ref($cls)->getMethod("default");
        //default 方法必须是 public,&!static
        if (
            !$refm instanceof \ReflectionMethod ||
            !($refm->isPublic() === true && $refm->isStatic() !== true)
        ) {
            return null;
        }

        //解析 default 方法
        $doc = $refm->getDocComment();
        $conf = Cls::parseComment($doc);
        //操作 name 
        $conf["name"] = "default";
        //保存完整的方法名
        $conf["method"] = $refm->name;
        //保存完整的 类全称
        $conf["class"] = $cls;
        //保存此方法所属的方法类型 在方法注释中指定
        if (!isset($conf["export"])) {
            //如果注释中未指定 方法类型 默认为 api
            $conf["export"] = "api";
        }
        $expt = $conf["export"];
        //生成 标准操作信息数组
        $conf = self::oprc($conf, $expt."/".$pre, $intr);
        if (!isset($conf["oprn"]) || !Is::nemstr($conf["oprn"])) return null;

        //返回 生成的 标准 操作信息数组
        return $conf;
    }

    /**
     * 将 路由表中定义的 操作信息，格式化为 标准的操作信息数组
     * !! 必须在当前应用已经实例化后，执行
     * @param Array $oprc 启动参数 route 中 | 路由表中 定义的 路由操作信息，路由正则 在 $oprc["route"] 中定义
     * @param String $oprpre 操作标识前缀
     * @param String $oprtit 操作标识的说明内容前缀
     * @return Array|null 最终生成的 操作信息数组，发生错误 则返回 null
     */
    public static function routeOprc($oprc, $oprpre=null, $oprtit=null)
    {
        if (!Is::nemarr($oprc) || !Is::associate($oprc)) return null;
        //如果当前应用还未实例化
        if (App::$isInsed !== true) return null;
        $appcls = App::$current->config->appcls;
        //路由正则
        $pattern = $oprc["route"] ?? null;
        if (!Is::nemstr($pattern) || substr($pattern, 0,1)!=="/" || substr($pattern, -1)!=="/") return null;

        //路由操作的 默认 操作信息
        $dftoprc = [
            "export" => "api",      //自定义路由操作 默认作为 api
            "auth" => true,
            "role" => "all",
        ];
        //填充 oprc
        $oprc = array_merge($dftoprc, $oprc);

        //路由指向的类 可以是 当前应用类 或 模块类
        $ocls = $oprc["class"] ?? null;
        if (Is::nemstr($ocls)) $ocls = Cls::find($ocls);
        if (!Is::nemstr($ocls) || !class_exists($ocls)) {
            $ocls = $appcls;
        }
        $oprc["class"] = $ocls;

        //处理 method 方法
        $mthd = $oprc["method"] ?? null;
        //操作标识
        $oprn = $oprc["oprn"] ?? null;
        if (!Is::nemstr($oprn) && !(Is::nemstr($mthd) || $mthd instanceof \Closure)) {
            //未定义有效的 method 同时 也未定义 oprn 则返回 null
            return null;
        }
        if (Is::nemstr($mthd)) {
            //定义了 类方法
            //定义的 method 不在 指向的类中 
            if (!method_exists($ocls, $mthd)) return null;
            //此方法必须是 指定的 api|view|src ... 方法
            $mthk = Str::snake($mthd, "_");
            $mthp = explode("_", $mthk);
            $expt = array_slice($mthp, -1)[0];
            if (!in_array($expt, self::$types)) return null;
            //处理 export
            $oprc["export"] = $expt;
            //自动获取 操作名 foo_bar
            if (!isset($oprc["name"]) || !Is::nemstr($oprc["name"])) {
                $name = implode("_", array_slice($mthp, 0, -1));
                $oprc["name"] = $name;
            }
        } else if ($mthd instanceof \Closure) {
            //定义了 匿名函数作为 操作方法
            //不做处理，在写入 Operation 实例的 context 时，会自动处理匿名函数 
        }

        //操作名称 foo_bar 形式，未指定 则 = md5($pattern)
        $name = $oprc["name"] ?? null;
        if (Is::nemstr($name)) {
            $name = Str::snake($name,"_");
        } else {
            $name = md5($pattern);
        }
        $oprc["name"] = $name;

        //操作说明
        $desc = $oprc["desc"] ?? ($oprc["title"] ?? null);
        if (!Is::nemstr($desc)) $desc = $name."路由";
        if (!Is::nemstr($oprtit)) $oprtit = self::getOprnTitle($ocls);
        $oprc["desc"] = $oprtit."：".$desc;

        //创建 操作标识
        $expt = $oprc["export"] ?? "api";
        if (!Is::nemstr($oprn)) {
            //创建 操作标识
            if (!Is::nemstr($oprpre)) $oprpre = self::getOprnPrefix($ocls, $expt);
            $oprn = $oprpre.":".$name;
            $oprc["oprn"] = $oprn;
        }

        //返回解析后的 标准 操作信息数组
        return self::getStdOprc($oprc);
    }

    /**
     * 筛选类中定义的 特殊操作列表 操作类型在 self::$types 中定义
     * 解析这些方法的注释，得到 标准的操作信息 列表
     * @param String $cls 要查找 操作方法的 类名，可被 Cls::find() 识别
     * @param String $type 筛选方法类型 默认 self::$types[0]
     * @param String $filter 筛选类中方法时，指定筛选方法 默认 public,&!static 
     * @param String $pre 操作标识 前缀，默认不指定，从 $cls 解析得到
     * @param String $intr 操作说明字符串 前缀，默认不指定，从 $cls 解析得到
     * @return Array 标准的 操作列表数据格式，与 self::$dftOprs 格式一致
     */
    public static function oprs($cls, $type=null, $filter="public,&!static", $pre=null, $intr=null)
    {
        //准备标准的 操作列表输出数据
        $oprs = self::getStdOprs();

        //检查传入的 类名 必须存在
        if (!Is::nemstr($cls)) return $oprs;
        $cls = Cls::find($cls);
        if (empty($cls) || !class_exists($cls)) return $oprs;

        //传入的 操作类型
        $types = self::$types;
        if (empty($types)) return $oprs;
        if (!Is::nemstr($type) || !in_array($type, $types)) {
            $type = $types[0];
        }

        //传入的 方法筛选条件
        if (!Is::nemstr($filter)) $filter = "public,&!static";

        //传入的 操作标识前缀
        if (!Is::nemstr($pre)) $pre = self::getOprnPrefix($cls, $type);

        //传入的 操作说明前缀
        if (!Is::nemstr($intr)) $intr = self::getOprnTitle($cls);

        //遍历 类中定义的 特定方法
        $ms = Cls::specific(
            $cls,
            $filter,
            $type,
            null,
            function($mi, $conf) use ($pre, $intr) {
                return self::oprc($conf, $pre, $intr);
            }
        );
        if (!Is::nemarr($ms)) return $oprs;

        //返回 标准的 操作列表数据
        return self::getStdOprs($ms);
    }

    /**
     * 根据 类全称，生成操作表示前缀
     * @param String $cls 类全称
     * @param String $type 操作类型 api|view|src ... 不指定则生成的 操作标识前缀中不带操作类型
     * @return String 操作标识前缀
     */
    public static function getOprnPrefix($cls, $type=null)
    {
        //去除 类全称的 NS 头，统一转为 路径形式名称  NS\foo\BarApp  -->  foo/bar_app
        $clsp = Cls::rela($cls);
        //如果是 App 类 则去除开头的 app/
        if (substr($clsp, 0,4)==="app/") $clsp = substr($clsp, 4);
        if (Is::nemstr($type)) return $type."/".$clsp;
        return $clsp;
    }

    /**
     * 根据 类全称，生成操作说明的前缀，通常是 定义在 App|Module 类中的 $intr 说明文字
     * @param String $cls 类全称
     * @return String 操作说明前缀
     */
    public static function getOprnTitle($cls)
    {
        //类名 FooBar 形式
        $clsn = Cls::name($cls);
        //如果类不存在，直接返回类名
        if (!class_exists($cls)) return $clsn;
        //从 $cls 类解析 操作说明前缀
        $clsps = Cls::ref($cls)->getDefaultProperties();
        $tit = $clsps["intr"] ?? $clsn;
        return $tit;
    }

    /**
     * 根据 操作信息数组 oprc 获取 此操作的 路由正则
     * @param Array $oprc 标准操作信息数组
     * @return String 路由正则 pattern
     */
    public static function getOprcPattern($oprc=[])
    {
        //格式化 oprc
        $oprc = self::getStdOprc($oprc);
        //如果已经指定了 路由正则
        if (Is::nemstr($oprc["route"])) return $oprc["route"];
        //必要信息
        $cls = $oprc["class"] ?? null;
        $oprnm = $oprc["name"] ?? null;
        $expt = $oprc["export"] ?? null;
        if (!class_exists($cls) || !Is::nemstr($oprnm) || !Is::nemstr($expt)) return null;

        /**
         * 根据 类名路径 方法名 方法类型 生成 路由正则 pattern
         */
        $pattern = [];
        //类路径
        $clsp = Cls::rela($cls);
        //类名 FooBar
        $clsn = Cls::name($cls);
        //类名 foo_bar
        $clsk = Str::snake($clsn, "_");
        //如果指向的类 不是 应用类，需要 在 正则中显示
        if (App::has($clsn) === false) $pattern[] = $clsk;
        //如果指向的 方法 不是 default，需要在 正则中显示
        if ($oprnm !== "default") {
            if (!empty($pattern)) $pattern[] = "\/";
            if ($expt === "view") {
                //view 类型的 操作，正则 可省略 view 字符
                $pattern[] = "(view\/)?";
            } else {
                //其他操作类型 必须在 正则中显示
                $pattern[] = $expt."\/";
            }
            //方法名 必须在 正则中显示
            $pattern[] = $oprnm;
        }
        //可能存在的 参数正则
        //if (!empty($pattern)) {
        //    $pattern[] = "(\/\.*)?";
        //} else {
            $pattern[] = "(\.*)";
        //}
        //合并得到 正则
        $pattern = "/".implode("", $pattern)."/";
        //返回
        return $pattern;
    }

    /**
     * 根据 类全称，获取此类的 pause 定义，即 此类是否受 WEB_PAUSE 开关的影响
     * 默认为 true 表示 当 WEB_PAUSE === true 时，此类中的所有 操作方法 不返回结果
     * !! 此类必须已经实例化，才能获取到 pause 参数，通常是 App|Module 类
     * @param String $cls 类全称
     * @return Bool
     */
    public static function getOprcPause($cls)
    {
        //查找 此类的实例，通常为 App|Module 类
        if (!isset($cls::$current)) return true;
        $ins = $cls::$current;
        if (!$ins instanceof $cls || !isset($ins->config)) return true;
        //此类关联的 配置类
        $cfger = $ins->config;
        if (!$cfger instanceof Configer || !isset($cfger->ctx["pause"])) return true;
        //pause 参数
        $pause = $cfger->ctx["pause"];

        return is_bool($pause) ? $pause : true;
    }

    /**
     * 判断某个关联数组，是标准的 操作信息数组
     * @param Array $oprc
     * @return Bool
     */
    public static function isStdOprc($oprc=[])
    {
        if (!Is::nemarr($oprc) || !Is::associate($oprc)) return false;
        return isset($oprc["oprn"]) && isset($oprc["class"]) && isset($oprc["method"]) && isset($oprc["export"]);
    }



    /**
     * 关联到 当前请求的 App 应用类的 Operation 操作列表管理类实例
     */

    /**
     * 当前 App 应用的 全部操作列表数据
     * !! WEB_CACHE 开启后，此列表将被缓存
     */
    protected $context = [
        //标准的 操作列表数据格式
        //...
    ];
    /**
     * 当前 App 应用的 全部操作列表中的 匿名函数 响应方法
     * !! WEB_CACHE 开启后，此列表将被缓存
     */
    protected $closure = [
        /*
        "操作标识" => function(...) { 函数定义 ... },
        ...
        */
    ];
    


    /**
     * 构造
     * @return void
     */
    public function __construct()
    {
        //当前请求的 App 应用实例 必须已创建
        if (App::$isInsed !== true) {
            throw new CoreException("操作管理类实例化时，应用实例还未创建", "initialize/init");
        }

        //首先尝试读取缓存，如果读取成功，则不再执行后续操作
        if (true === $this->readCache()) return;

        //未读取到缓存，或者未启用缓存，开始初始化当前应用的 操作列表
        // 0 查找并读取 全局|应用 路由表文件
        $rutsInited = $this->initRoutes();
        // 1 查找当前应用类中定义的 所有可用操作列表
        $aoprsInited = $this->initAppOprs();

        if (true !== ($rutsInited && $aoprsInited)) {
            throw new CoreException("操作管理类未能正确实例化", "initialize/init");
        }
    }

    /**
     * 读取|写入 context 方法，写入后 将自动更新缓存文件
     * @param String|Array $key 要读取操作信息的 操作标识，如果是 Array 则表示 标准的 操作列表数据格式，将要写入 context
     * @return Array|null
     */
    public function ctx($key=null)
    {
        if (!Is::nemstr($key) && !Is::nemarr($key)) {
            //未传入参数，直接返回 context
            return $this->context;
        }

        //读取 context
        if (Is::nemstr($key)) {
            return isset($this->context[$key]) ? $this->context[$key] : null;
        }

        //写入 context
        //将要写入的 操作列表格式化为 标准操作列表数据格式
        $oprs = self::getStdOprs($key);
        //处理 操作列表中 method 为 匿名函数的 情况
        foreach ($oprs as $k => $oprc) {
            if (true !== self::isStdOprc($oprc)) continue;
            $oprn = $oprc["oprn"];
            $mthd = $oprc["method"];
            //method 为 匿名函数
            if ($mthd instanceof \Closure) {
                $this->closure[$oprn] = $mthd;
                $oprs[$k]["method"] = "__closure__";
            }
        }
        //合并
        $this->context = Arr::extend($this->context, $oprs);
        //更新缓存数据
        $this->saveCache();
        //返回 合并后 context
        return $this->context;
    }



    /**
     * 缓存相关 方法
     */

    /**
     * 读取缓存文件，写入 $context|$closure
     * @return Bool 是否读取并写入成功
     */
    protected function readCache()
    {
        //获取缓存文件路径
        $cof = App::$current->config->ctx("operation/cache/operation");
        $ccf = App::$current->config->ctx("operation/cache/closure");
        //读取预设的 缓存文件
        $ctx = Cache::read($cof);
        $closure = Cache::read($ccf);
        //写入 context
        if (Is::nemarr($ctx)) {
            $this->context = $ctx;
            if (Is::nemarr($closure)) $this->closure = $closure;
            return true;
        }
        return false;
    }

    /**
     * 将解析得到的 $context|$closure 数据写入缓存
     * @return Bool 
     */
    protected function saveCache()
    {
        //获取缓存文件路径
        $cof = App::$current->config->ctx("operation/cache/operation");
        $ccf = App::$current->config->ctx("operation/cache/closure");
        //写入缓存
        Cache::save($cof, $this->context);
        if (Is::nemarr($this->closure)) Cache::saveClosure($ccf, $this->closure);
        return true;
    }

    /**
     * 判断当前的 操作列表 是否来自 缓存
     * @return Bool
     */
    public function isCached()
    {
        return Cache::isCached($this->context);
    }



    /**
     * init 方法
     */

    /**
     * 收集 手动定义的 全局路由|应用路由 操作
     * 通常定义在：
     *      启动参数的 route 项
     *      应用路由表文件中
     *      全局路由表文件中
     * 覆盖规则：参数中路由定义  覆盖  应用路由表  覆盖  全局路由表
     * @return Bool
     */
    protected function initRoutes()
    {
        //准备 路由表
        $routes = [];

        //首先尝试读取 全局路由表 | 应用路由表
        $rufs = [
            //全局路由表
            App::$current->config->ctx("operation/route/global"),
            //应用路由表
            App::$current->config->ctx("operation/route/app")
        ];
        //确认 路由表文件存在
        $rufs = array_map(function($ruf) {
            $ruf = Path::find($ruf, Path::FIND_FILE);
            if (!empty($ruf)) return $ruf;
            return null;
        }, $rufs);
        $rufs = array_filter($rufs, function($ruf) {
            return Is::nemstr($ruf);
        });
        //依次合并 路由表中定义的 路由操作列表
        for ($i=0;$i<count($rufs);$i++) {
            $ruf = $rufs[$i];
            $ruts = require($ruf);
            //合并
            $routes = Arr::extend($routes, $ruts);
        }
        
        //最后与 启动参数中的 route 项 合并，参数中的 覆盖 路由表 中的
        $opt = App::$current->config->ctx("route");
        if (Is::nemarr($opt)) $routes = Arr::extend($routes, $opt);

        //标准操作列表数据格式
        //$dftOprs = $this->config->ctx("oprs");

        /**
         * 遍历路由表，将 路由操作 添加 操作列表中
         * !! 全局路由必须完整定义 name|[title|desc]|class|method
         * !! class 指向某个 应用类全称
         * !! method 指向 class 类方法  或者  自定义的函数
         */
        //所有找到的 操作列表
        $oprs = [];
        foreach ($routes as $pattern => $oprc) {
            //将 路由正则 附加到 oprc
            $oprc["route"] = $pattern;
            //处理这个 路由定义
            $oprc = self::routeOprc($oprc);
            if (!Is::nemarr($oprc)) continue;
            $mn = $oprc["name"] ?? null;
            if (!Is::nemstr($mn)) continue;
            //合并
            $oprs[$mn] = $oprc;
        }

        //将找到的操作列表写入 $context
        $this->ctx($oprs);
        
        return true;
    }

    /**
     * 收集 当前应用的 可用操作，通过调用 App::getOprs
     * @return Bool
     */
    protected function initAppOprs()
    {
        //当前应用类
        $appcls = App::$current->config->appcls;

        //调用 App::getOprs() 方法 在 traits/Operation 中定义
        $oprs = $appcls::getOprs();
        
        //写入 context
        $this->ctx($oprs);

        return true;
    }



    /**
     * 操作列表 获取
     */

    /**
     * 输出 当前应用的 所有可用的 操作列表
     * @param Bool $auth 是否筛选 启用权限控制的 操作，即 返回所有 auth == true 的 操作列表，默认 false 返回全部操作
     * @return Array
     *  [ 
     *      "操作标识" => [ 操作信息 ... ], 
     *      ... 
     *  ]
     */
    public function defines($auth=false)
    {
        if (!Is::nemarr($this->context)) return [];
        $ctx = $this->context;

        //筛选
        $defs = [];
        foreach ($this->context as $oprn => $oprc) {
            $ok = substr($oprn, 0, -1);
            if (in_array($ok, self::$types)) continue;
            if (!$auth || $oprc["auth"] === true) {
                $defs[$oprn] = $oprc;
            }
        }
        
        return $defs;
    }

    /**
     * 从 所有可用操作中，获取 带有 路由正则的 操作
     * 可输出为 静态路由表
     * !! 按正则语句长度，从大到小 排列
     * @param Bool $auth 是否筛选 启用权限控制的 操作，即 返回所有 auth == true 的 操作列表，默认 false 返回全部操作
     * @return Array
     *  [
     *      "/路由正则/" => [ 操作信息 ... ],
     *      ...
     *  ]
     */
    public function routes($auth=false)
    {
        if (!Is::nemarr($this->context)) return [];

        //获取全部 可用的 操作列表
        $defs = $this->defines($auth);
        if (!Is::nemarr($defs)) return [];

        //准备 routes
        $routes = [];
        //遍历 可用操作列表，找出 带有 route 属性的操作
        foreach ($defs as $oprn => $oprc) {
            if (isset($oprc["route"]) && Is::nemstr($oprc["route"])) {
                $pattern = $oprc["route"];
                $routes[] = [
                    "pattern" => $pattern,
                    "len" => strlen($pattern),
                    "oprc" => $oprc
                ];
            }
        }

        //!! 按正则语句长度 从大到小 排序
        usort($routes, function($a, $b) {
            $alen = $a["len"];
            $blen = $b["len"];
            if ($alen == $blen) return 0;
            return ($alen < $blen) ? 1 : -1;
        });
        //整理
        $rut = [];
        foreach ($routes as $ruti) {
            $pattern = $ruti["pattern"];
            $rut[$pattern] = $ruti["oprc"];
        }

        return $rut;
    }

    /**
     * 输出所有可用的 Closure 匿名方法
     * @param Bool $auth 是否筛选 启用权限控制的 操作，即 返回所有 auth == true 的 操作列表，默认 false 返回全部操作
     * @return Array
     *  [ 
     *      "操作标识" => Closure, 
     *      ... 
     *  ]
     */
    public function closures($auth=false)
    {
        if (!Is::nemarr($this->context) || !Is::nemarr($this->closure)) return [];
        $ctx = $this->context;
        $clo = $this->closure;

        //筛选
        $defs = [];
        foreach ($clo as $oprn => $fc) {
            if (!isset($ctx[$oprn])) return false;
            $oprc = $ctx[$oprn];
            if (!$auth || $oprc["auth"] === true) {
                $defs[$oprn] = $fc;
            }
        }
        
        return $defs;
    }

    /**
     * 根据传入的 条件判断方法 查找 操作信息
     * @param Closure $search 匿名函数，参数是 某个 oprc 返回 true 则选中
     * @param Bool $auth 是否筛选 启用权限控制的 操作
     * @return Array|false
     */
    public function search($search=null, $auth=false)
    {
        if (!$search instanceof \Closure) return false;
        //获取全部 操作列表
        $oprs = $this->defines($auth);
        //查找
        foreach ($oprs as $oprn => $oprc) {
            $srt = $search($oprc);
            if ($srt===true) return $oprc;
        }
        return false;
    }

    /**
     * 遍历 $this->routes() 生成的路由表，匹配 传入的 URI 路径数组
     * @param Array $uris 解析得到的 URI 请求路径数组
     * @return Array|null 没有匹配到 返回 null，匹配到 返回：
     *  [
     *      "oprc" => [ 操作信息数组 ... ],
     *      "uris" => [ 匹配到的 合并 解析剩余的 uris，可用作操作方法 参数的 数组 ]
     *  ]
     */
    public function match($uris=[])
    {
        if (!Is::nemarr($uris)) $uris = [];

        //生成 路由表
        $routes = $this->routes();
        //拼接为 URI 字符串
        $uri = implode("/", $uris);
        //依次匹配路由
        foreach ($routes as $pattern => $oprc) {
            //使用 正则 匹配 $uri 字符串
            try {
                $mt = preg_match($pattern, $uri, $matches);
            } catch (\Exception $e) {
                //正则匹配出错 跳过
                continue;
            }
            //未匹配成功，继续下一个
            if ($mt !== 1) continue;

            //匹配到的 完整字符串 
            $mtstr = $matches[0] ?? "";
            //每一个匹配的 字符串项目
            $mtarr = array_slice($matches, 1);
            if (!empty($mtarr)) {
                //去除其中的 空字符串
                $mtarr = array_filter($mtarr, function($mti) {
                    return Is::nemstr($mti);
                });
                if (!empty($mtarr)) {
                    //去除 每个匹配到的字符串中的 /
                    $mtarr = array_map(function($mti) {
                        return trim($mti, "/");
                    }, $mtarr);
                    //合并这些字符串，重新按 / 分割，因为可能匹配到 foo/bar 形式的字符串
                    $mtarr = explode("/", implode("/", $mtarr));
                }
            }

            //剩余的 uri 字符
            $luri = trim(str_replace($mtstr, "", $uri), "/");
            //剩余的 uri 路径数组
            $luriarr = $luri=="" ? [] : explode("/", $luri);

            //匹配结果数组 和 剩余的 URI 路径，合并后得到 作为响应方法参数 的数组
            $luris = array_merge($mtarr, $luriarr);

            //返回匹配结果
            return [
                "oprc" => $oprc,
                "uris" => $luris
            ];
        }

        //未匹配到任何内容 返回 null
        return null;
    }



}