<?php
/**
 * 框架核心配置类
 * 环境参数 核心类 的 配置类
 */

namespace Spf\config;

use Spf\config\CoreConfig;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;

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
            "cache" => ".json"
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

            //开关
            //是否显示debug信息
            "debug" => false,	
            //暂停网站
            "pause" => false,	
            //日志开关
            "log" => false,
            //缓存开关，开启后 所有参数处理类 将优先使用缓存的数据
            "cache" => true,

            //其他 web 参数

            //阿里云参数
            "ali" => [
                //安装ssl证书，首次需要开启此验证，通过后即可关闭
                "sslcheck" => false
            ],

        ],

        
    ];

    //用户设置 需要覆盖 init 参数
    protected $opt = [];

    //经过处理后的 运行时参数
    protected $context = [];

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

        //定义环境常量
        $this->initCnstConf();

        //初始化 路径常量
        $this->initPathConf();

        //php error test
        //array_shift($foo);
        //trigger_error("这是一个用户触发的错误信息", E_USER_ERROR);

        return $this;
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

    //处理环境常量 constant 项下参数
    protected function initCnstConf()
    {
        $ctx = $this->context;

        foreach ($this->context as $key => $conf) {
            //跳过 已处理过的参数项 php|
            if (in_array($key, ["php"])) continue;

            //处理 web 项
            if ($key=="web") {
                //从预定义的 webroot 路径获取当前 web 应用的 key foo_bar 形式
                $conf["key"] = Str::snake(array_slice(explode("/",$conf["root"]), -1)[0], "_");
            }

            //定义为常量
            if (Is::nemarr($conf) && Is::associate($conf)) {
                self::def($conf, $key);
            } else {
                if (!defined(strtoupper($key))) {
                    define(strtoupper($key), $conf);
                }
            }
        }
        
        //兼容老版本
        define("EXT", EXT_CLASS);

        return $this;
    }

    //处理框架 路径常量
    protected function initPathConf()
    {
        /**
         * 定义 框架路径常量
         */
        //检查是否定义了 pre 路径
        $pre = $this->ctx("web/pre");
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
        $root = $this->ctx("web/root");
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
        $dirs = $this->ctx("dir");
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
}