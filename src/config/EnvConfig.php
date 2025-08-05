<?php
/**
 * 框架核心配置类
 * 环境参数 核心类 的 配置类
 */

namespace Spf\config;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\SpecialUtil;
use Monolog\Logger;

class EnvConfig extends CoreConfig 
{
    /**
     * 预设的设置参数
     * !! 覆盖父类
     * 
     * 预定义的 环境参数
     * 这些参数的 数据类型 必须可以被定义为常量，数据类型包括：
     *      标量类型：Int|Float|String|Bool
     *      复合类型：Array
     *      其他类型：NULL
     *      !! 这些类型不能被定义为常量：Object | resource(文件句柄，数据库连接等)
     * 
     * 此处定义的是 默认参数
     * !! 可在框架启动时，根据需要修改
     */
    protected $init = [

        //php 参数
        "php" => [
            "error_reporting" => -1,
            "timezone" => "Asia/Shanghai",
            "ini_set" => [
                //是否在浏览器中显示 错误信息，1 true   0 false
                "display_errors" => "0",
                // 其他 ini_set ...
            ],
        ],

        //定义 各类文件 后缀名
        "ext" => [
            //类文件
            "class" => ".php",
            //配置文件
            "conf" => ".json",
            //缓存文件
            "cache" => ".json",
            //日志文件
            "log" => ".log",
        ],

        //路径分隔符，linux 下为 /   windows 下为 \
        "ds" => DIRECTORY_SEPARATOR,

        /**
         * 可以自定义 命名空间 前缀
         * 仅在当前 webroot 路径下生效，不会影响 框架自有类
         * 默认 与 框架自有类的 命名空间前缀 一致
         */
        "ns" => "Spf\\",

        /**
         * 特殊 路径|文件夹
         * 可自定义这些特殊 路径|文件夹 的名称
         * 在 patchAutoload 以及 查找文件 等方法中，将使用到 这些特殊 路径|文件夹
         */
        "dir" => [
            //App 应用的保存文件夹
            "app"       => "app",
            //class 文件路径，命名空间 NS\\*** 或 NS\\app\\app_name\\***
            "lib"       => "library",
            //module 模块类文件路径，命名空间 NS\\module\\*** 或 NS\\module\\app_name\\***
            "module"    => "module",
            //数据表(模型) 类文件路径，命名空间 NS\\model\\db_name\\*** 或 NS\\model\\app_name\\db_name\\***
            "model"     => "model",
            //中间件类文件路径，命名空间 NS\\middleware\\*** 或 NS\\middleware\\app_name\\***
            "middleware"    => "middleware",
            //数据库文件路径，数据库配置文件应在 db/config 路径下
            "db"        => "library/db",
            //view 视图文件路径，类命名空间 NS\\view\\*** 或 NS\\view\\app_name\\***
            "view"      => "view",
            //assets 路径，默认的 静态资源路径
            "src"       => "assets",
            //文件上传路径
            "upload"    => "assets/uploads",
            //cache 缓存文件路径
            "cache"     => "library/cache",
            //exception 异常处理类文件路径，命名空间 NS\\exception\\*** 或 NS\\exception\\app_name\\***
            "exception" => "library/exception",
            //runtime 整站的运行时 缓存路径
            "runtime"   => "runtime",
            //log 整站日志文件路径
            "log" => "log",
        ],

        //web 参数
        "web" => [
            //定义网站目录中 vendor 的上一级路径 !! 绝对路径 !!，例如：vendor 路径为 /data/vendor 则此处为：/data
            "pre" => "",
            //当前网站根目录，从 PRE_PATH 起始，例如：www 表示网站根路径为：PRE_PATH/www   不指定则以 PRE_PATH 作为网站根目录
            "root" => "",
            //当前网站 key, 应该是网站根目录 文件夹名
            "key" => "",

            //网站参数
            "protocol"      => "https",
            "domain"        => "",  //"systech.work",
            "ip"            => "",  //"124.223.97.67",
            "ajaxallowed"   => "",  //",systech.work",
            //语言
            "lang"          => "zh-CN",
            	
            //暂停网站
            "pause" => false,	

            //是否显示debug信息
            "debug" => false,

            //其他 web 参数

        ],

        /**
         * 框架 特殊工具
         * 开关|初始化参数
         */
        "util" => [

            //Event 事件 订阅|触发|销毁
            "event" => [
                //开关
                "enable" => true,
            ],

            //Cache 运行时缓存工具
            "cache" => [
                //开关
                "enable" => true,
                //支持的 缓存文件格式
                "exts" => ".json,.php", //TODO: .xml,.yml,...
                //缓存文件内容中 时间戳的 键名
                "time_key" => "__CACHE_TIME__",
                //缓存数据应用到目标数据中时的 缓存标记 键名
                "sign_key" => "__CACHE_SIGN__",
                //缓存过期时间
                "expire" => 60*60,
            ],

            //Log 日志工具
            "log" => [
                //开关
                "enable" => true,
                //支持的 Monolog 方法
                "ms" => "debug,info,notice,warning,error,critical,alert,emergency",
                //日志记录的 文本格式
                "format" => "%datetime% | %channel% | %level_name% | %message% | %context% | %level%\n",
                //日志记录的 其实等级，超过此等级的日志才会被记录，默认 Logger::DEBUG 为最低等级，表示记录所有日志
                "lvl" => Logger::DEBUG,
            ],

        ],

        
    ];

    //用户设置 需要覆盖 init 参数
    protected $opt = [];

    //经过处理后的 运行时参数
    protected $context = [];

    //全局静态常量
    protected $special = [
        "ext", "ds", "ns",
    ];

    /**
     * 在 应用用户设置后 执行 自定义的处理方法
     * !! 覆盖父类
     * @return $this
     */
    public function processConf()
    {
        /**
         * 框架启动阶段 
         * 处理 环境参数，定义环境 变量 | 常量 等
         */

        //php 环境参数
        $this->initPhpConf();

        //定义全局静态常量 ext|ds|ns
        $this->initStaticCnst();

        //初始化 WEB_*** | DIR_*** | ***_PATH 常量
        $this->initPathConf();

        //初始化 其他环境参数
        $this->initConf();

        return $this;
    }

    /**
     * 在初始化时，处理外部传入的 用户设置，例如：提取需要的部分，过滤 等
     * !! 覆盖父类
     * @param Array $opt 外部传入的 用户设置内容
     * @return Array 处理后的 用户设置内容
     */
    protected function fixOpt($opt=[])
    {
        /**
         * EnvConfig 只需要参数中的 $opt["env"] 部分 
         */
        return $opt["env"] ?? [];
    }



    /**
     * 各子项参数 处理
     * 统一返回 $this
     */

    //处理定义在 php 项下的预设参数
    protected function initPhpConf()
    {
        $phpc = $this->ctx("php");
        if (isset($phpc["error_reporting"])) {
            //0/-1 = 关闭/开启
            @error_reporting($phpc["error_reporting"]);
        }
        if (isset($phpc["timezone"])) {
            //时区
            @date_default_timezone_set($phpc["timezone"]);
        }
        if (isset($phpc["ini_set"])) {
            //ini_set
            $ist = $phpc["ini_set"];
            if (Is::nemarr($ist)) {
                foreach ($ist as $k => $v) {
                    ini_set($k, $v);
                }
            }
        }
        return $this;
    }

    //定义全局静态常量 ext|ds|ns
    protected function initStaticCnst()
    {
        //特别的常量
        $spe = $this->special;
        foreach ($spe as $sk) {
            if (!isset($this->context[$sk])) continue;
            $sv = $this->context[$sk];
            $ck = strtoupper($sk);
            
            //定义为常量
            if (Is::nemarr($sv) && Is::associate($sv)) {
                self::def($sv, $ck);
            } else {
                if (!defined($ck)) {
                    define($ck, $sv);
                }
            }
        }
        
        //兼容老版本
        define("EXT", EXT_CLASS);

        return $this;
    }

    //处理框架 WEB_*** | DIR_*** | ***_PATH 常量
    protected function initPathConf()
    {
        //现有的 web 参数定义
        $root = $this->ctx("web/root");
        $wkey = $this->ctx("web/key");
        $pre = $this->ctx("web/pre");
        $dirs = $this->ctx("dir");

        //检查是否定义了 webkey
        if (!Is::nemstr($wkey)) {
            $this->context["web"]["key"] = Str::snake(array_slice(explode("/",$root), -1)[0], "_");
        }

        /**
         * 定义 WEB_*** 常量
         */
        $webc = $this->ctx("web");
        self::def($webc, "web");

        /**
         * 定义 DIR_*** 常量
         */
        self::def($dirs, "dir");

        /**
         * 定义 ***_PATH 路径常量
         */
        //检查是否定义了 pre 路径
        if (!Is::nemstr($pre) || !is_dir($pre)) {
            //PRE_PATH 指代 vendor 目录的上一级
            $pre = __DIR__.DS."..".DS."..".DS."..".DS."..".DS."..";
        } else {
            //定义了 pre 路径，这是绝对路径
            $pre = str_replace("/", DS, $pre);
        }
        $pre = Path::fix($pre);
        //VENDOR_PATH 指代 vendor 目录
        $vdp = $pre.DS."vendor";
        //CGY_PATH 指代 vendor/cgyio 目录
        $cgp = $vdp.DS."cgyio";
        //SPF_PATH 指代 vendor/cgyio/spf/src 目录
        $spp = $cgp.DS."spf".DS."src";
        //$mdp = $rep.DS."module";
        $path = [
            "pre_path" => $pre,
            "vendor_path" => $vdp,
            "cgy_path" => $cgp,
            "spf_path" => $spp,
        ];
        //定义为常量
        self::def($path);

        /**
         * 根据参数 web/root 定义 当前 webroot 路径参数
         * web/root 在配置参数中定义为 相对于 PRE_PATH 目录的 相对路径
         */
        if (Is::nemstr($root)) {
            $root = str_replace("/", DS, trim($root, "/"));
        }
        if (!Is::nemstr($root) || !is_dir(PRE_PATH.DS.$root)) {
            //未定义有效的 webroot 则 以 PRE_PATH 为 网站根路径
            $root = PRE_PATH;
        } else {
            //定义了 根路径
            $root = PRE_PATH.DS.$root;
        }
        //定义 webroot 下的 路径常量
        $webp = [
            "root_path"     => $root,
        ];
        //将 dir 项下所有 特殊路径 定义 ***_path
        if (Is::nemarr($dirs) && Is::associate($dirs)) {
            foreach ($dirs as $dk => $dv) {
                if (!Is::nemstr($dv)) continue;
                $webp[$dk."_path"] = $root . DS . str_replace("/", DS, $dv);
            }
        }
        //定义为常量
        self::def($webp);

        //将定好的 path 路径参数，写入 context
        $defs = array_merge([], $path, $webp);
        $ps = [];
        foreach ($defs as $k => $p) {
            $k = substr($k, 0, -5);
            $ps[$k] = $p;
        }
        $this->context["path"] = $ps;

        return $this;
    }

    //处理环境常量 constant 项下参数
    protected function initConf()
    {
        //不需要处理的 参数
        $spe = array_merge(["php", "dir", "web"], $this->special);

        foreach ($this->context as $key => $conf) {
            //跳过 已处理过的参数项 
            if (in_array($key, $spe)) continue;

            //如果存在专门的 initFooConf 方法
            $m = "init".Str::camel($key, true)."Conf";
            if (method_exists($this, $m)) {
                $this->$m($conf);
                continue;
            }

            //不存在专门的 init 方法，则定义为常量
            if (Is::nemarr($conf) && Is::associate($conf)) {
                self::def($conf, $key);
            } else {
                if (!defined(strtoupper($key))) {
                    define(strtoupper($key), $conf);
                }
            }
        }

        return $this;
    }

    //处理 util 框架特殊工具类 参数 开关 | 初始化参数
    protected function initUtilConf($conf=[])
    {
        //依次处理 各特殊工具类的 参数
        foreach ($conf as $util => $uc) {
            //查找对应的 工具类
            $ucls = Cls::find("util/$util", "Spf\\");
            if (empty($ucls) || !class_exists($ucls) || !is_subclass_of($ucls, SpecialUtil::class)) continue;
            //调用 工具类的 setInitConf 方法
            $ucls::setInitConf($uc);
        }

        return $this;
    }
}