<?php
/**
 * 框架核心配置类 
 * 应用配置类 基类，所有实际应用配置类 都必须 继承此类
 */

namespace Spf\config;

use Spf\App;
use Spf\Module;
use Spf\Middleware;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class AppConfig extends Configer 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [];

    /**
     * 可在多个配置类中通用的 设置参数默认值
     * 如果设定了此值，则 $init 属性需要合并(覆盖)到此数组
     * !! 如果需要，可以在某个配置类基类中定义此数组，然后在配置类子类中部分定义 $init 数组，即可实现 设置参数的继承和子类覆盖
     */
    protected $dftInit = [
        //此应用是否受 WEB_PAUSE 影响，默认 true，此应用下的操作方法可自行在 注释中覆盖此参数
        "pause" => true,

        //此应用的 Operation 操作管理类参数
        "operation" => [
            //操作列表缓存路径，%{APPK}% 表示当前应用的 app_name
            "cache" => [
                //操作列表缓存文件
                "operation" => "runtime/app/%{APPK}%/operation.php",
                //作为响应方法的 匿名函数 缓存文件
                "closure" => "runtime/app/%{APPK}%/closure.php",
            ],
            //路由表文件
            "route" => [
                //全局路由表
                "global" => "lib/route.php",
                //此应用的 路由表
                "app" => "app/%{APPK}%/route.php",
            ],
        ],

        //此应用必须依赖的 通用可扩展资源类
        "expandableResource" => [
            "dependency" => [
                //资源类基类的 类全称 或 可被 Cls::find 识别的类路径 ...
            ],
            //是否开启 资源缓存
            "enableCache" => true,
            //资源缓存的 文件路径
            "cache" => "runtime/app/%{APPK}%/expandable_resource.json",
        ],

        /**
         * 在此应用配置类构造时传入的 框架启动参数 $opt[] 
         * 从其中筛选出此处定义的项目，作为 app 全局参数
         * !! 在 fixOpt 方法中自动生成 此处不需要手动定义
         * 这些全局参数将会被当前 app 的自定义参数覆盖
         * 全局参数可选： route|module|middleware|service
         */
        /*"global" => [
            "route" => [],
            "module" => [],
            "middleware" => [
                "in" => [],
                "out" => [],
            ],
            "service" => [],
        ],*/

        //定义在此应用中的 路由，全局路由将会合并到此数组
        "route" => [

        ],

        //在此应用中需要启用的 模块，全局模块将会合并到此数组
        "module" => [
            //默认启用的 模块

            //资源管理 查找|输出
            "src" => [
                "enable" => true,
                //资源输出 不受 WEB_PAUSE 影响
                "pause" => false,
            ],

            //测试模块 框架功能测试
            "test" => [
                "enable" => true,
                //!! 仅在 开发模式下 可用
                "dev" => true,
                //不受 WEB_PAUSE 影响
                "pause" => false,
            ],

            /*
            "module_name" => [
                "enable" => true,
                # 其他参数参考对应的 模块配置类中的 init 属性值
                ...
            ],
            */
        ],

        //在此应用中需要启用的 中间件，以及其配置参数，全局中间件将会合并到此数组
        //要删除 全局 定义的某个中间件，可在 类名路径前增加 __delete__ 标记
        "middleware" => [

            //入站中间件
            "in" => [

            ],

            //出站中间件
            "out" => [

            ],

            //中间件的配置参数
            /*
            "中间件类全称" => [
                # 配置参数内容
                ...
            ],
            ...
            */
        ],

        /**
         * Spf 框架的 ServiceApp 机制，分布式应用系统
         * 
         * 此 应用中 将要使用的 ServiceApp
         * !! 任意 App 应用，都可以被外部的 其他应用 作为 ServiceApp 来使用
         * !! ServiceApp 可以部署在 本项目|本服务器|其他服务器 上，通过接口调用
         * !! 所有可被作为 ServiceApp 被其他应用使用的 应用，都必须基于 Spf 框架（需注意 框架版本）
         * 
         * 在当前请求的 App 应用初始化阶段，会扫描 此参数中定义的所有 ServiceApp，访问通用的 operations 接口，获取各
         * ServiceApp 对外提供的 可用操作列表（以 Operation::$stdOprs 数组形式对外提供），
         * 当前应用，会将这些操作，合并到 $app->operation 实例中，
         * 这样 所有 ServiceApp 的所有操作接口，都会被纳入当前应用的 operation 操作与匹配体系
         * 当前应用内部直接通过 $app->invokeService() 即可调用这些 ServiceApp 对外提供的 功能
         *      $app->invokeService(
         *          "服务名称/服务接口/参数1/参数2...?a=1&b=2",
         *          [
         *              模拟 post 给接口的 json 数据 ...
         *          ], 
         *          function($result) {
         *              对 接口返回的数据，做额外加工 ...
         *              return $result;
         *          }
         *      )
         * 
         * 使用举例：
         *      当前项目 prj_aaa 根目录为 www/project/aaa
         *          其中包含应用 aaa_foo 应用目录为 www/project/aaa/app/aaa_foo
         *              此应用的访问地址：https://aaa.domain.com/aaa_foo/... 
         *          同项目中还有另一个应用 aaa_bar 应用目录为 www/project/aaa/app/aaa_bar
         *              此应用的访问地址：https://aaa.domain.com/aaa_bar/... 
         *      同服务器中还有另一个项目 prj_bbb 根目录为 www/project/bbb
         *          其中包含应用 bbb_foo 应用目录为 www/project/bbb/app/bbb_foo
         *              此应用的访问地址：https://bbb.domain.com/bbb_foo/... 
         *      外部其他服务器中有项目 prj_ccc 根目录为 otherwww/project/ccc 
         *          其中包含应用 ccc_foo 应用目录为 otherwww/project/ccc/app/ccc_foo
         *              此应用的访问地址：https://ccc.otherdomain.com/ccc_foo/... 
         * 
         * 则 在当前应用 aaa_foo 中，这样调用应用 aaa_bar|bbb_foo|ccc_foo 作为 ServiceApp
         * 在 应用 aaa_foo 的配置参数中指定 service 参数：
         *      "service" => [
         *          !! 服务名称可与 服务应用的实际的应用名不同，推荐统一加 service_project_ 前缀
         *          "service_aaa_bar" => [
         *              # 真实的 ServiceApp 名称 kabab-case
         *              "name"  => "aaa_bar",
         * 
         *              # 服务应用的实际访问地址
         *              !! 如果是同项目下的，domain 相同，则可以省略
         *              "url"   => "/aaa_bar",
         * 
         *              # 如果 服务应用开启了 Uac 控制，还需要指定 账号|密码，否则 uac 设为 false
         *              !! 如果 服务应用 与 当前应用 共享一套 Uac 体系(例如：指向相同的 Mysql库，以及相同的 用户表|角色表)，
         *              !! 则 uac 参数可设为 true
         *              !! 同项目|同服务器 中的 应用，通常采用此种模式，因此 同项目|同服务器 中的 服务应用 uac 一般是 true
         *              "uac"   => true,
         * 
         *              # 可以额外对 服务应用的 接口 做 本地映射
         *              !! 通常不推荐
         *              "api"   => [
         *                  "实际接口名" => "本地映射的接口名",
         *                  ...
         *              ],
         * 
         *              # 可以额外指定，标准操作信息数组 的覆盖参数
         *              !! 将覆盖 此 ServiceApp 的所有操作 被合并到 $app->operation 时生成的 标准操作信息数组
         *              "extra" => [
         *                  # 可以设置 此 ServiceApp 不受 WEB_PAUSE 维护标记影响
         *                  "pause" => false
         *              ],
         *          ],
         * 
         *          "service_bbb_foo" => [
         *              "name"  => "bbb_foo",
         *              !! 如果 服务应用的 访问地址 有 不同的 domain 则不能省略
         *              "url:   => "https://bbb.domain.com/bbb_foo",
         *              !! 通常情况下 同服务器中的 应用，都会 共享一套 Uac 体系，因此 uac = true
         *              "uac"   => true,
         *              "api"   => [],
         *          ],
         * 
         *          "service_ccc_foo" => [
         *              "name"  => "ccc_foo",
         *              "url"   => "https://ccc.otherdomain.com/ccc_foo",
         * 
         *              !! 针对外部服务器中的 服务应用，也可以通过 Orm 模块指向相同的 Mysql用户库用户表角色表，来共享一套 Uac
         *              !! 如果 未共享 Uac 
         *              !! 则需要在 当前应用的 用户库用户表中，为每个用户指定 对应 service 服务的 账号和密码
         *              !! 此处设为 uac = "custom"
         *              "uac"   => "custom",
         * 
         *              !! 如果 当前应用的 Uac 体系中，未能为每个用户指定 对应 service 服务的 账号和密码
         *              !! 则可以在此处指定 所有 当前应用用户 通用的 服务应用的 账号密码
         *              "uac"   => [
         *                  "usr" => "通用账号",
         *                  "pwd" => "通用密码"
         *              ],
         * 
         *              !! 如果 服务应用 未开启 Uac 模块，直接设为 false
         *              "uac"   => false,
         * 
         *              "api"   => [],
         *          ],
         *      ],
         *          
         *      
         */
        "service" => [
            /*
            "service_name" => [
                "name" => "",
                "url" => "",
                "uac" => true,
                "api" => [],
                "extra" => [],
            ],
            ...
            */
        ],
        
    ];
    


    /**
     * 在初始化时，处理外部传入的 用户设置，例如：提取需要的部分，过滤 等
     * !! 覆盖父类
     * @param Array $opt 外部传入的 用户设置内容
     * @return Array 处理后的 用户设置内容
     */
    protected function fixOpt($opt=[])
    {
        if (!Is::nemarr($opt)) return [];
        //关联的 应用实例
        $app = $this->coreIns;
        if (!$app instanceof App) return [];

        /**
         * 选择 app[app_name] 项下参数
         * 将启动参数中 定义为全局启用的 route|module|middleware 合并到 app[app_name][global] 项下
         */
        //App 应用类名 路径形式 foo_bar
        $appk = $app::clsk();
        //在 框架启动参数中 查找 此应用的 配置参数
        $conf = Arr::find($opt, "app/$appk");
        if (!Is::nemarr($conf)) $conf = [];
        //框架启动参数中 定义的 全局 route|module|middleware 参数 保存到 global 项下
        $conf["global"] = Arr::choose($opt, "route", "module", "middleware", [
            "route" => [],
            "module" => [],
            "middleware" => [
                "in" => [],
                "out" => []
            ],
        ]);

        return $conf;
    }

    /**
     * 定义 配置参数 合并方法 默认使用 Arr::extend 覆盖方向： $opt --> $init --> $dftInit
     * !! 覆盖父类
     * @return $this
     */
    public function extendConf()
    {
        /**
         * 主要处理 module|middleware 序列
         * 需要严格按照  $opt --> $init --> $dftInit --> global 顺序 push 到数组中
         * 如果 覆盖定义的 module|middleware 为不启用，则需要从已有的序列中 删除
         */
        //按顺序读取
        $queue = [
            $this->opt["global"] ?? [],
            $this->dftInit,
            $this->init,
            $this->opt
        ];

        //合并 module|middleware
        $mods = [];
        $mids = Middleware::getStdMids();
        for ($i=0;$i<count($queue);$i++) {
            //合并 module 同时 提取并合并依赖的其他模块
            $cmods = $queue[$i]["module"] ?? [];
            $mods = Module::extend($mods, $cmods);
            //从 原有的 参数数组中 删除 module 参数
            unset($queue[$i]["module"]);

            //合并 middleware
            $cmids = $queue[$i]["middleware"] ?? [];
            $mids = Middleware::extend($mids, $cmids);
            unset($queue[$i]["middleware"]);
        }

        //合并其他参数

        //合并 global 中的其他参数，去除 opt 中的 global
        $opt = $this->opt;
        if (isset($opt["global"])) {
            //global->route  合并到 当前应用的 route 
            if (isset($opt["global"]["route"])) {
                $opt["route"] = Arr::copy($opt["global"]["route"]);
            }
            //global->service  合并到 当前应用的 service 
            if (isset($opt["global"]["service"])) {
                $opt["service"] = Arr::copy($opt["global"]["service"]);
            }

            //去除 opt 中的 global 参数
            unset($opt["global"]);
        }

        //预定义设置参数，合并默认设置
        if (Is::nemarr($this->dftInit)) {
            $this->init = Arr::extend($this->dftInit, $this->init);
        }

        //合并 用户设置 与 预定义参数，保存到 context
        $this->context = Arr::extend($this->init, $opt);

        //将 module|middleware 参数写入 context
        $this->context["module"] = $mods;
        $this->context["middleware"] = $mids;

        return $this;
    }

    /**
     * 在 应用用户设置后 执行 自定义的处理方法
     * !! 覆盖父类
     * @return $this
     */
    public function processConf()
    {
        //将当前应用的 类全称|类名|类路径名 写入 context
        //当前应用的 实例
        $app = $this->coreIns;
        if (!$app instanceof App) return $this;
        //类全称
        $appcls = get_class($app);
        //类名 FooBar
        $appn = $appcls::clsn();
        //类名 foo_bar
        $appk = $appcls::clsk();
        //写入 context
        $this->context["appcls"] = $appcls;
        $this->context["appn"] = $appn;
        $this->context["appk"] = $appk;
        
        //处理参数中的 %{APPK}% %{APPN}% 字符模板
        $this->context = $this->fixConfVal($this->context, function($v) use ($appk, $appn) {
            if (!Is::nemstr($v)) return $v;
            if (strpos($v, "%{APPK}%")!==false) $v = str_replace("%{APPK}%", $appk, $v);
            if (strpos($v, "%{APPN}%")!==false) $v = str_replace("%{APPN}%", $appn, $v);
            return $v;
        });

        //去除 enable 为 false 的 模块参数
        $mods = $this->context["module"];
        foreach ($mods as $modk => $modc) {
            if (!isset($modc["enable"]) || $modc["enable"]!==true) {
                unset($mods[$modk]);
            }
        }
        $this->context["module"] = $mods;

        return $this;
    }
}