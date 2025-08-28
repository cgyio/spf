<?php
/**
 * 框架核心类
 * 模块类基类，抽象类
 * 
 * 模块独立于应用，每个应用都可以选择启用某些模块，为应用添加相应的功能
 * 可以全局启用一些模块，这样每个应用都会同时拥有这些模块的功能
 * 
 * 模块类可以同时实例化多个 模块子类（每个子类都是单例模式）
 */

namespace Spf;

use Spf\exception\CoreException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

abstract class Module extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类，具体模块子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = true;

    /**
     * 当前会话 启用的 所有模块
     */
    public static $modules = [
        /*
        "模块类名 foo_bar" => 模块类单例 modcls::$current  未实例化时为 null,
        ...
        */
    ];

    /**
     * 模块的元数据
     * !! 实际模块类必须覆盖
     */
    //模块的说明信息
    public $intr = "";
    //模块的名称 类名 FooBar 形式
    public $name = "";

    

    /**
     * 获取模块类 对应的 config 配置类 类全称
     * !! 覆盖父类
     * @return String|null 类全称
     */
    protected function getConfigCls()
    {
        //当前模块的 类名 FooBar 形式
        $clsn = static::clsn();
        //当前模块的 路径名 foo_bar
        $clsk = static::clsk();
        //模块配置类 类名 ModuleFooBarConfig
        $cfgn = "Module".$clsn."Config";

        //查找 参数配置类的 类全称
        $cfgcls = null;
        if (App::$isInsed === true) {
            //应用已经创建，则应在 应用路径下 查找对应的 模块配置类
            //应用名 转为 foo_bar 形式
            $appk = App::$current::clsk();
            //在 应用路径下查找 当前模块的 配置类
            $cfgcls = Cls::find("module/$appk/$clsk/$cfgn");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //在 框架默认的路径下查找
            $cfgcls = Cls::find("module/$clsk/$cfgn", "Spf\\");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //默认路径下，也没有此模块的 配置类，则使用 ModuleConfig 类，此类一定存在
            $cfgcls = Cls::find("config/ModuleConfig", "Spf\\");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //未找到配置类，报错
            throw new CoreException("未找到 $cfgn 配置类", "initialize/config");
        }
        return $cfgcls;
    }
    
    /**
     * 此模块类自有的 init 方法，执行以下操作：
     *  0   为应用实例增加 新的响应方法，将这些新的响应方法 添加到 App::$current->operation->context[] 并更新 路由表
     *  1   为应用实例增加 新的中间件，这些新的中间件 
     *  2   执行模块自定义的 init 方法
     *  3   将当前的 模块实例，缓存到 Module::$modules[module_name]
     * !! Core 子类必须实现的，Module 子类不要覆盖
     * @return $this
     */
    final public function initialize()
    {
        //模块初始化时，App 应用必须已经实例化
        if (App::$isInsed !== true) {
            throw new CoreException("模块初始化时，应用实例还未创建", "initialize/init");
        }

        //模块类名 FooBar
        $modn = $this::clsn();
        //模块类名 路径形式 foo_bar
        $modk = $this::clsk();

        //确认 只能在开发环境下启用的模块，不会在生产环境中被启用
        if (Env::$current->dev !== true && $this->config->dev === true) {
            throw new CoreException("模块 $modn 不能在当前环境下启用", "initialize/init");
        }

        //确认 此模块所有依赖的 其他模块都已经实例化
        $dps = $this->config->dependency;
        if (Is::nemarr($dps) && Is::indexed($dps)) {
            //存在 依赖的模块，依次检查 是否存在于 Module::$modules 数组中
            $mods = Module::$modules;
            foreach ($dps as $dpmodk) {
                if (!isset($mods[$dpmodk]) || !$mods[$dpmodk] instanceof Module) {
                    //!! 模块初始化时，依赖的模块还未实例化，表示可能存在 模块循环依赖的 问题
                    $dpmodn = Str::camel($dpmodk, true);
                    throw new CoreException("初始化模块 $modn 时，依赖的模块 $dpmodn 还未实例化，检查这两个模块是否出现了循环依赖", "initialize/init");
                }
            }
        }

        // 0 为应用实例增加 新的响应方法，修改 操作列表 | 路由表
        $oprsInjected = $this->injectOprs();

        // 1 为应用实例增加 新的中间件
        $midsInjected = $this->injectMiddleware();

        // 2 执行模块自定义的 init 方法
        $modInited = $this->initModule();

        if (true !== ($oprsInjected && $midsInjected && $modInited)) {
            throw new CoreException("未能正确初始化模块 $modn", "initialize/init");
        }

        // 3 将当前的 模块实例，缓存到 Module::$modules[module_name]
        Module::$modules[$modk] = $this;

        return $this;
    }

    /**
     * 模块启用后，为当前应用实例增加 新的响应方法，将这些新的响应方法 添加到 Operation::$context[app_name] 并更新 路由表
     * !! 子类可以覆盖
     * @return Bool
     */
    protected function injectOprs()
    {
        //当前应用必须已经实例化
        if (App::$isInsed !== true) return false;
        $app = App::$current;
        //操作列表管理类实例
        $operation = $app->operation;

        //!! 如果 操作列表是从 缓存中获取的 则不执行此操作，因为所有操作都已被缓存过
        if ($operation->isCached() === true) return true;

        //调用 Module::getOprs() 方法，定义在 traits/Operation 中，某些模块会覆盖这个方法
        $oprs = static::getOprs();
        //模块的 操作列表 写入 当前应用的 操作列表
        $operation->ctx($oprs);

        return true;
    }

    /**
     * 模块启用后，为当前应用实例增加 新的中间件
     * !! 子类可以覆盖
     * @return Bool
     */
    protected function injectMiddleware()
    {
        //当前应用的 中间件
        $appmids = App::$current->config->middleware;
        if (!Is::nemarr($appmids)) $appmids = [];
        //此模块必须的 中间件
        $mids = $this->config->middleware;
        
        //将 模块中定义的 中间件，添加到 应用中
        if (Is::nemarr($mids)) {
            $appmids = Middleware::extend($appmids, $mids);
            //写入 应用 config
            App::$current->config->ctx("middleware", $appmids);
        }

        return true;
    }

    /**
     * 模块启用后，将在实例化后，立即执行此 初始化操作
     * !! 需要自定义初始化动作的 模块 必须覆盖这个方法
     * @return Bool
     */
    protected function initModule()
    {

        return true;
    }



    /**
     * 在任意模块的 响应方法中 可用的 通用快捷操作
     * !! 要求 Response 实例必须已创建
     */

    //快速设置 非 200 状态，并返回 null 数据
    protected function responseCode($code=404)
    {
        Response::insSetCode($code);
        return null;
    }



    /**
     * 静态方法
     */

    /**
     * 核心类 __callStatic
     */
    public static function __callStatic($key, $args)
    {
        /**
         * Module::all()            -->  Module::$modules
         * Module::all("FooBar")    --> Modules::$modules["foo_bar"] ?? null
         */
        if ($key === "all") {
            $mods = self::$modules;
            $emods = [];
            foreach ($mods as $modk => $modins) {
                if (!$modins instanceof Module) continue;
                $emods[$modk] = $modins;
            }
            if (!empty($args)) {
                $modk = $args[0];
                if (!Is::nemstr($modk)) return null;
                $modk = Str::snake($modk, "_");
                return $emods[$modk] ?? null;
            }
            return $emods;
        }

        /**
         * Module::FooBar()     -->  Module::$modules["foo_bar"]
         * 以 静态方法 形式 调用 某个模块实例
         */
        $modins = self::all($key);
        if ($modins instanceof Module) return $modins;

        //调用 父类的魔术方法 parent::__callStatic
        return parent::__callStatic($key, $args);
    }

    /**
     * 判断模块 $mod 是否存在
     * @param String $mod 模块名称 Foobar 或 foo_bar 形式
     * @return String|false 类全称，未找到则返回 false
     */
    public static function has($mod)
    {
        if (!Is::nemstr($mod)) return false;

        //先判断一次
        if (class_exists($mod) && is_subclass_of($mod, Module::class)) return $mod;

        //路径名形式 foo_bar
        $modk = Str::snake($mod, "_");
        //类名形式 FooBar
        $modn = Str::camel($modk, true);
        //模块类必须存在
        $modcls = Cls::find("module/$modn", "Spf\\");
        if (Is::nemstr($modcls) && class_exists($modcls)) return $modcls;

        //如果 App 应用实例已创建，则在应用路径下查找
        if (App::$isInsed === true) {
            //应用名路径形式 foo_bar
            $appk = App::$current::clsk();
            //在 应用路径下查找 模块类
            $modcls = Cls::find("module/$appk/$modn");
            if (Is::nemstr($modcls) && class_exists($modcls)) return $modcls;
        }

        return false;
    }

    /**
     * 合并 启用模块的列表，$new 覆盖 $old 严格按顺序 覆盖，并处理 模块依赖链
     * 处理后的 模块列表，第一个模块必然是 最早被依赖的模块，并且 自身没有依赖（模块参数的 dependency 项为空），
     * !! 否则在 当前应用的 initialize 方法中按顺序实例化模块时，将报 模块循环依赖 错误
     * @param Array $old 原模块列表
     * @param Array $new 新模块列表
     * @return Array 合并后的，严格按顺序执行实例化的 模块列表，自动 按顺序 包含依赖的其他模块
     */
    public static function extend($old=[], $new=[])
    {
        if (!Is::nemarr($old) && !Is::nemarr($new)) return [];
        if (!Is::nemarr($old) || !Is::associate($old)) return self::findDependency($new);
        if (!Is::nemarr($new) || !Is::associate($new)) return self::findDependency($old);

        //合并
        foreach ($new as $modk => $modc) {
            if (!isset($modc["enable"]) || $modc["enable"]!==true) {
                //新模块列表中 模块不启用的，则从原模块列表中 删除此模块
                if (isset($old[$modk])) unset($old[$modk]);
                continue;
            }
            //执行 覆盖
            if (!isset($old[$modk])) {
                //后定义的 push 到原数组中
                $old[$modk] = $modc;
            } else {
                //后定义的 extend 覆盖原参数
                $old[$modc] = Arr::extend($old[$modk], $modc);
            }
        }

        //提取并合并 依赖的 其他模块
        $old = self::findDependency($old);

        //返回
        return $old;
    }

    /**
     * 将启用模块列表中 依赖的 其他模块，提取并插入模块列表
     * 例如：模块 A 依赖模块 B，则提取出模块 B，并插入模块 A 之前，因为模块是按列表顺序实例化的
     * 还要处理依赖链：模块 A 依赖模块 B，模块 B 依赖模块 C，则提取之后的 模块列表顺序为 C,B,A
     * @param Array $mods 启用的模块列表，通常是 App 应用的参数中的 module 项
     * @return Array 处理后的 启用模块列表
     */
    public static function findDependency($mods=[])
    {
        if (!Is::nemarr($mods)) return $mods;
        //筛选 模块列表，去除 未启用|环境不匹配 的模块
        $forDev = Env::$current->dev === true;
        $mods = array_filter($mods, function($modc) use ($forDev) {
            $enable = $modc["enable"] ?? true;
            if ($enable!==true) return false;
            if (!$forDev) {
                $dev = $modc["dev"] ?? false;
                return $dev!==true;
            }
            return true;
        });
        //modks 列表，原始顺序
        $modks = array_keys($mods);
        //提取到的 被依赖的 模块参数列表
        $dpmods = [];
        foreach ($mods as $modk => $modc) {
            $dp = $modc["dependency"] ?? null;
            if (!Is::nemarr($dp) || !Is::associate($dp)) continue;
            //递归
            $ndp = self::findDependency($dp);
            $nmodks = array_keys($ndp);
            //新模块插入到 dpmods
            $dpmods = Arr::extend($dpmods, $ndp);
            //插入原 modks 数组
            $i = array_search($modk, $modks);
            if ($i<=0) {
                $modks = array_merge($nmodks, $modks);
            } else {
                array_splice($modks, $i-1, 0, $nmodks);
            }
        }

        //modks 列表去重
        $nmodks = [];
        foreach ($modks as $modk) {
            if (!in_array($modk, $nmodks)) $nmodks[] = $modk;
        }

        //提取到的 所有 被依赖的 模块，必须启用，并忽略 环境要求|pause 参数
        foreach ($dpmods as $modk => $modc) {
            unset($modc["dev"]);
            unset($modc["pause"]);
            $modc["enable"] = true;
            $dpmods[$modk] = $modc;
        }

        //根据 modks 列表顺序，构建新 mods 列表
        $nmods = Arr::extend($mods, $dpmods);
        $rmods = [];
        foreach ($nmodks as $modk) {
            if (!isset($nmods[$modk])) continue;

            //将 dependency 参数 转换为 modk 数组 indexed
            if (isset($nmods[$modk]["dependency"]) && Is::associate($nmods[$modk]["dependency"])) {
                $dpks = array_keys($nmods[$modk]["dependency"]);
                $nmods[$modk]["dependency"] = $dpks;
            }

            //写入 新模块列表
            $rmods[$modk] = $nmods[$modk];
        }

        //返回
        return $rmods;
    }

}