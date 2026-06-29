<?php
/**
 * 框架模块配置类
 * ORM 数据库模块
 */

namespace Spf\module\orm;

use Spf\config\ModuleConfig;
use Spf\App;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class ModuleOrmConfig extends ModuleConfig 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [

        //此模块是否受 WEB_PAUSE 影响，默认 true，此模块下的操作方法可自行在 注释中覆盖此参数
        //"pause" => true,

        //此模块是否 仅 开发环境下 可用
        //"dev" => false,

        //依赖的 其他模块
        //"dependency" => [
            /*
            "mod_name" => [
                # 模块参数 与 此数组 结构一致
                "middleware" => [],
                ...
            ],
            */
        //],

        //此模块必须依赖的 通用可扩展资源类
        "expandableResource" => [
            "dependency" => [
                //数据库驱动 资源
                "module/orm/Driver",
                //数据模型字段类型 资源
                "module/orm/Types",
                //数据模型配置预处理类
                "module/orm/config/model/Preparer",
                //数据模型配置解析器 资源
                "module/orm/config/model/Parser",
            ],
        ],

        //定义数据库通用操作接口的 路由前缀，默认 db/...
        "dbRoutePrefix" => "db",
        //!! 是否在 dev 开发环境下，取消 数据库通用操作接口的 uac 控制
        "disableDevDbRouteUac" => true,

        //数据库全局唯一 key 的 前缀字符串
        "dbKeyPrefix" => "DB_",
        //指定数据库配置文件类型  默认 .json
        "dbConfigExt" => ".json",

        //指定数据库前端渲染组件库，通常是有效的 SPF-Vcom 基础组件库(包含必要的 inputer 组件)
        "frontComponentLib" => "spf",
        
        /**
         * 指定要使用的数据库配置
         * 可以指定多个 不同位置/不同类型 的数据库
         * !! 数据库配置文件的结构，参考 module/orm/temp/db_foo.json
         */
        "dbs" => [
            /* 
            !! 键名作为此数据库名称，foo_bar 格式，将替换数据库配置文件中的 %{DBN}% 模板字符
            "db_foo" => [
                !! 数据库配置参数格式见 $stdDbConf 中的定义
            ],

            !! 如果定义的数据库没有其他额外参数，可使用简写
            "db_foo" => "path/to/config/db_foo",

            ... 可定义多个数据库
            */
        ],

        /**
         * 可定义哪些数据库是必须的
         * 在 Orm 实例创建之后，这些数据库也必须立即初始化
         */
        "required" => [
            //"db_foo",
        ],

        //此模块必须的 中间件
        "middleware" => [
            //入站
            "in" => [
                //"middleware/orm_foo",
            ],
            //出站
            "out" => [],

            //中间件配置参数
            //"middleware/orm_foo" => [
            //    "orm_foo_fooo" => 123,
            //    "orm_foo_barr" => 456
            //],
            //...
        ],
        
    ];

    /**
     * 定义标准的 数据库配置参数格式
     */
    protected static $stdDbConf = [
        
        /**
         * !! 必须指定数据库配置文件路径，可被 Path::find() 识别的形式
         * 可以不要后缀（配置文件后缀，在 $init["dbConfigExt"] 中定义）
         */
        "config" => "",

        /**
         * !! 可以自定义 数据库类全称 或 可被 Cls::find() 识别的类路径
         * 如果 不指定 或 "" 默认指向 app/%{APPK}%/db/%{DBN}%/Db 类
         * 如果 当前应用下不存在对应的 类文件，默认使用 module/orm/Db
         */
        "class" => "app/%{APPK}%/db/%{DBN}%/Db",

        /**
         * 数据库配置文件中可以使用字符串模板 %{AAA..}% 
         * 可在此处定义各模板字符对应的值
         * %{DBN}% 可不指定，自动使用 此数据库的配置名称(键名)
         */
        "tpls" => [
            //"dbn" => "db_foo",                替换 %{DBN}%
            //"path" => "path/foo/bar",         替换 %{PATH}%
            //... 根据实际配置文件中使用的模板来指定
        ],
        
        /**
         * 可以为此数据库增加 前置查询条件
         * 例如：   在多用户共用一个数据库时，表中有用户标记字段的
         *          可在此指定用户标记，这样就不需要在每次查询时都要传入用户标记
         * !! 必须是有效的 Medoo 查询的 where 条件，其他查询条件使用 AND 关系合并
         * !! 需要分别为每个需要的 表 指定前置查询条件
         */
        "prefilter" => [
            //"表名" => [ "字段名" => "标记值" ],

            //!! 支持 Medoo 查询语法
            //"表名" => [ "字段名[<]" => 100 ],

            //!! 可以有多条 前置查询条件
            //"tb_name" => [
            //    "col_a" => "val_a",
            //    "col_b[>]" => 100
            //],
        ],


        /**
         * !! 自动补全的参数项目
         * 默认 "" 将由 配置类自动补全
         */
        /**
         * 此数据库的全局唯一 key
         * DB_ . md5(配置文件真实路径)
         */
        "key" => "",

        //数据库相关文件的根目录，默认 "" 根据配置文件路径解析得到
        "dbroot" => "",
        //数据模型类路径前缀 由 Cls::find() 识别，默认 "" 根据配置文件路径解析得到
        "modpre" => "",

        /**
         * !! 指定数据库配置参数 缓存文件路径
         * !! 如果指定为 false 则表示不启用配置缓存，每次会话将重新执行数据库配置解析
         * 
         * !! 可使用 %{APPK}%(当前请求的应用名) %{IN_APPK}%(数据库定义在的应用名) 模板字符
         * 
         * !! 默认为 "" 表示根据 配置文件路径自动生成
         * 例如：配置文件路径  app/foo_app/db/bar_db.php  则自动的缓存文件路径：
         *          app/foo_app/db/bar_db/cache/config.php
         * 
         * 手动指定示例：app/%{APPK}%/db/bar_db/cache/config.php
         */
        "cache" => "",


        /**
         * 其他配置参数项目，将覆盖到 数据库配置文件中的同名项目
         * !! 数据结构在 DbConfig::$dftInit 中定义
         */
        //"driver" => "mysql",
        //"connect" => [ ... ],
        //"model" => [
        //    "_default_" => [ ... 默认 数据模型(表) 参数 ... ],
        //    ...
        //],
        //...
    ];

    

    /**
     * 在 应用用户设置后 执行 自定义的处理方法
     * !! 覆盖父类
     * @return $this
     */
    public function processConf()
    {
        //在父类基础上执行
        //parent::processConf();

        //当前请求的 App 应用名 foo_bar
        if (App::$isInsed!==true) return $this;
        $appk = App::$current::clsk();  //foo_bar
        $appn = App::$current::clsn();  //fooBar

        //过滤 dbs 中数据库定义
        $dbs = $this->context["dbs"] ?? [];
        if (!Is::nemarr($dbs) || !Is::associate($dbs)) {
            $this->context["dbs"] = [];
            return $this;
        }

        //当前数据库配置文件后缀
        $cfext = strtolower($this->context["dbConfigExt"] ?? ".json");

        //过滤无效定义
        $ndbs = [];
        foreach ($dbs as $dbn => $dbc) {
            //确保数据库名为 foo_bar 格式
            $dbk = Str::snake($dbn, "_");
            //配置文件真实路径
            $cf = null;
            
            if (Is::nemstr($dbc)) {
                //针对简写定义形式
                $cf = Path::find($this->autoSuffixDbConf($dbc), Path::FIND_FILE);
            } else if (Is::nemarr($dbc) && Is::associate($dbc)) {
                //完整定义形式
                if (!isset($dbc["config"]) || !Is::nemstr($dbc["config"])) continue;
                $cf = Path::find($this->autoSuffixDbConf($dbc["config"]), Path::FIND_FILE);
            } else {
                //其他形式
                continue;
            }

            //配置文件必须存在
            if (!file_exists($cf)) continue;

            //根据 $cf 配置文件路径，解析得到默认路径参数
            //数据库相关文件的 根目录
            $dbroot = dirname($cf).DS.$dbk;
            //此数据库是否定义在某个 App 应用之下
            $inapp = Path::inapp($cf);
            //默认的数据模型类 路径前缀
            //$modpre = Is::nemstr($inapp) ? "app/$inapp/model/$dbk" : "model/$dbk";
            $modpre = Is::nemstr($inapp) ? "model/$inapp/$dbk" : "model/$dbk";

            //合并默认值
            $ndbs[$dbk] = Arr::extend([], self::$stdDbConf, $dbc, [
                "config" => $cf,

                //!! 根据数据库配置文件路径以及数据库名，创建唯一的数据库 key
                "key" => $this->context["dbKeyPrefix"] . md5("$dbk:$cf"),

                //!! 收集 相关的框架参数，作为 %{***}% 模板替换的数据源
                "tpls" => [
                    //%{DBN}% 当前数据库名 可能与实际数据库名不一致 foo_bar
                    "dbn" => $dbk,
                    //%{CONF_EXT}% 配置文件后缀
                    "conf_ext" => $cfext,
                    //%{IN_APPK}% 数据库定义在哪个 App 应用下 foo_bar
                    "in_appk" => Is::nemstr($inapp) ? $inapp : "",
                    //%{IN_APPN}% 数据库定义在哪个 App 应用下 fooBar
                    "in_appn" => Is::nemstr($inapp) ? Str::camel($inapp, false) : "",
                    //%{APPK}% 当前请求的 App 应用名 foo_bar
                    "appk" => $appk!=="base_app" ? $appk : "",
                    //%{APPN}% 当前请求的 App 应用名 fooBar
                    "appn" => $appn!=="baseApp" ? $appn : "",
                ],
            ]);
        
            //!! 自动补全 每个数据库的参数
            $ndbc = $ndbs[$dbk];
            //dbroot    默认的数据库根目录，为配置文件所在目录下的 数据库名称文件夹
            if (!isset($ndbc["dbroot"]) || !Is::nemstr($ndbc["dbroot"])) $ndbs[$dbk]["dbroot"] = $dbroot;
            //modpre    默认的数据模型类 路径前缀
            if (!isset($ndbc["modpre"]) || !Is::nemstr($ndbc["modpre"])) $ndbs[$dbk]["modpre"] = $modpre;
            //cache     默认的数据库配置文件缓存位置
            if (!isset($ndbc["cache"]) || $ndbc["cache"]==="" || $ndbc["cache"]===true) {
                $ndbs[$dbk]["cache"] = $dbroot.DS."cache".DS."config".$cfext;
            }
            //tpls 中补齐特定的 %{***}% 模板数据
            $ndbs[$dbk]["tpls"] = Arr::extend($ndbs[$dbk]["tpls"], [
                //%{DBROOT}% 此数据库实际定义的 根路径（配置文件实际所在路径）
                "dbroot" => $ndbs[$dbk]["dbroot"],
                //%{MODPRE}% 默认的数据模型类 路径前缀
                "modpre" => $ndbs[$dbk]["modpre"],
            ]);

            //数据库类全称
            $dbclsps = [];
            if (Is::nemstr($dbc["class"])) {
                //!! 数据库类为空时，使用当前应用路径下的 Db 类
                $dbclsps[] = "app/$appk/db/$dbk/Db";
                //!! 同时检查 当前应用下的 通用 Db 类
                $dbclsps[] = "app/$appk/db/Db";
            } else {
                $dbclsps[] = $dbc["class"];
            }
            $dbcls = Cls::find($dbclsps);
            //使用 默认 Db 类兜底
            if (!class_exists($dbcls) || !is_subclass_of($dbcls, Db::class)) {
                $dbcls = Db::class;
            }
            //数据库类全程 写入参数
            $ndbs[$dbk]["class"] = $dbcls;
        }

        //过滤后的 dbs 数据库列表 写回 context
        $this->context["dbs"] = $ndbs;

        //过滤 required 中定义的必须加载的 数据库
        $req = $this->context["required"] ?? [];
        if (!Is::nemarr($req) || !Is::indexed($req) || !Is::nemarr($ndbs)) {
            $this->context["required"] = [];
            return $this;
        }
        $req = array_filter($req, function($dbi) use ($ndbs) {
            //指定的必须加载的 数据库 必须在 dbs 中存在
            return isset($ndbs[$dbi]) && Is::nemarr($ndbs[$dbi]);
        });
        //过滤后的 required 数组写回 context
        $this->context["required"] = $req;

        return $this;
    }


    /**
     * 自动补全数据库配置文件路径的后缀名
     * DbConfig 类中也使用了此方法
     */
    public function autoSuffixDbConf($p)
    {
        if (!Is::nemstr($p)) return "";
        //有后缀名的，直接返回
        if (strpos($p, ".")!==false) return $p;
        //补全后缀名
        return $p . ($this->context["dbConfigExt"] ?? ".json");
    }

}