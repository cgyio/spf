<?php
/**
 * 框架核心配置类 
 * 应用配置类 基类，所有实际应用配置类 都必须 继承此类
 */

namespace Spf\config;

use Spf\App;
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

        //定义为全局启用的 route|module|middleware
        /*"global" => [
            "route" => [],
            "module" => [],
            "middleware" => [
                "in" => [],
                "out" => [],
            ],
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
            //合并 module
            $cmods = $queue[$i]["module"] ?? [];
            if (!Is::nemarr($cmods)) continue;
            foreach ($cmods as $modk => $modc) {
                if (!isset($modc["enable"]) || $modc["enable"]!==true) {
                    //覆盖的设置中 模块不启用，则从已有模块中 删除此模块
                    if (isset($mods[$modk])) unset($mods[$modk]);
                    continue;
                }
                //执行 覆盖
                if (!isset($mods[$modk])) {
                    //后定义的 push 到原数组中
                    $mods[$modk] = $modc;
                } else {
                    //后定义的 extend 覆盖原参数
                    $mods[$modc] = Arr::extend($mods[$modk], $modc);
                }
            }
            //从 原有的 参数数组中 删除 module 参数
            unset($queue[$i]["module"]);

            //合并 middleware
            $cmids = $queue[$i]["middleware"] ?? [];
            $mids = Middleware::extend($mids, $cmids);
            unset($queue[$i]["middleware"]);
        }

        //合并其他参数

        //去除 opt 中的 global
        $opt = $this->opt;
        if (isset($opt["global"])) {
            if (isset($opt["global"]["route"])) {
                $opt["route"] = Arr::copy($opt["global"]["route"]);
            }
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