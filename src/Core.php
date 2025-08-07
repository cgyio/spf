<?php
/**
 * 框架核心类 基类 抽象类
 * 所有核心类，共有这些特性：
 * 
 *      单例模式：
 *          实例化：Class::current()
 *          单例缓存：Class::$current
 * 
 *      核心类都关联了各自的 configer 参数配置类，应在核心类实例化的同时 实例化
 *          核心类的 参数配置类 全称 Spf\config\ClassNameConfig
 *          !! 如果 App 应用已经实例化，则对应的 核心配置类全称应为 NS\app\app_name\config\ClassNameConfig
 *          !! 各 App 应用中定义的 核心配置类 应继承自相应的 ClassNameConfig
 *          实例化后的 参数配置类实例，缓存在 Class::$current->config
 *          
 */

namespace Spf;

use Spf\App;
use Spf\config\Configer;
use Spf\exception\CoreException;
use Spf\util\Event;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\traits\Operation as OperationTrait;

abstract class Core 
{
    //引用 trait
    use OperationTrait {
        OperationTrait::__callStatic as operationTraitCallStatic;
    }

    /**
     * 单例模式
     * !! 子类必须覆盖这些 静态属性|方法
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    //核心类 对应的参数配置类 实例
    public $config = null;
    //外部传入的启动参数 即 核心配置类的构造参数，在核心配置类实例化后，此属性将被释放
    public $opt = [];



    /**
     * 单例实例化方法
     * !! 子类不要覆盖
     * @param Array $args 核心类构造参数
     * @return Core 核心类实例
     */
    final public static function current(...$args)
    {
        //检查是否已经 实例化
        if (static::$isInsed === true) return static::$current;
        //核心类 实例化 流程
        try {
            //创建实例
            $cls = static::class;
            $ins = new $cls(...$args);
            if (!$ins instanceof $cls) {
                //实例化失败
                throw new CoreException("核心类实例化失败", "singleton/instantiate");
            }
            //标记 已实例化
            static::$isInsed = true;
            //缓存 核心实例
            static::$current = $ins;
            
            //核心类实例化之后，立即执行：
            //实例化 核心类对应的 config 参数配置类
            $ins->initConfig();

            //核心类自定义的 init 方法
            $ins->initialize();
            //订阅事件
            Event::regist($ins);
            
        } catch (CoreException $e) {
            //核心类实例化失败，终止响应
            $e->handleException(true);
        }

        //返回 核心实例
        return static::$current;
    }

    /**
     * 核心类构造方法，protected 不能通过 new 方式创建 核心类 实例
     * !! 子类可覆盖此方法，必须在内部调用父类构造函数
     * @param Array $opt 框架启动参数中关于此核心类的 参数
     * @param Array $args 实例化参数
     * @return void
     */
    protected function __construct($opt=[], ...$args)
    {
        //从 外部传入的框架启动参数中，选取 核心类启动参数
        $opt = $this->fixOpt($opt);
        //缓存 核心类启动参数
        $this->opt = Is::nemarr($opt) ? $opt : [];
        
    }

    /**
     * 从传入的 $opt 数组中选取部分作为 核心类的 启动参数
     * !! 子类可覆盖此方法
     * @param Array $opt 传入的框架启动参数，通过 核心类的 current 方法传入的
     * @return Array 选取后的 此核心类的 启动参数
     */
    protected function fixOpt($opt=[])
    {
        if (!Is::nemarr($opt)) return [];
        //核心类 类名 路径形式 foo_bar
        $clsk = static::clsk();
        //核心类 类型
        $type = Str::snake(static::is(), "_");
        //依次查找
        $conf = Arr::find($opt, "$type/$clsk");
        if (!Is::nemarr($conf)) $conf = Arr::find($opt, $clsk);
        if (!Is::nemarr($conf)) $conf = $opt;

        return $conf;
    }

    /**
     * 实例化 参数配置类
     * !! 子类可覆盖此方法
     * @param Array $args 核心类参数配置类的 实例化参数，通常是 框架启动参数中 关于此核心类的 参数
     * @return Configer 实例
     */
    public function initConfig()
    {
        //查找 参数配置类的 类全称
        $cfgcls = $this->getConfigCls();

        //外部传入的 核心类启动参数
        $opt = Is::nemarr($this->opt) ? $this->opt : [];
        //实例化 配置类
        $cfger = new $cfgcls($opt);
        //释放 $this->opt
        $this->opt = null;
        //缓存 配置类实例
        $this->config = $cfger;
        //返回 实例化的 配置类
        return $cfger;
    }

    /**
     * 获取此核心类 对应的 config 配置类 类全称
     * !! 子类可覆盖此方法
     * @return String|null 类全称
     */
    protected function getConfigCls()
    {
        //当前核心类的 类名 FooBar 形式
        $clsn = static::clsn();
        //配置类 类名 FooBarConfig
        $cfgn = $clsn."Config";

        //查找 参数配置类的 类全称
        $cfgcls = null;
        if (App::$isInsed === true) {
            //应用已经创建，则应在 应用路径下 查找对应的 核心配置类
            //应用名 转为 foo_bar 形式
            $appk = App::$current::clsk();
            //在 应用路径下查找 当前核心类的 配置类
            $cfgcls = Cls::find("app/$appk/$cfgn");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //在 框架默认的路径下查找
            $cfgcls = Cls::find("config/$cfgn", "Spf\\");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //默认路径下，也没有此核心类的 配置类，则使用 CoreConfig 类，此类一定存在
            $cfgcls = Cls::find("config/CoreConfig", "Spf\\");
        }
        return (!empty($cfgcls) && class_exists($cfgcls)) ? $cfgcls : null;
    }

    /**
     * 此核心类自有的 init 方法
     * !! 子类必须实现
     * @return $this
     */
    abstract public function initialize();



    /**
     * 快捷访问 __get
     * !! 子类如果要覆盖，请在此基础上增加，即 必须在子类 __get 方法中调用 parent::__get()
     * @param String $key 要访问的 不存在的 属性
     * @return Mixed
     */
    public function __get($key)
    {
        /**
         * $this->foo  -->  $this->config->foo 
         * 访问核心配置类 的 context 内容
         */
        if ($this->config instanceof Configer) {
            $ctx = $this->config->$key;
            if (!is_null($ctx)) return $ctx;
        }

        return null;
    }

    /**
     * 核心类 __callStatic
     */
    public static function __callStatic($key, $args)
    {


        //调用 BaseTrait::__callStatic
        return static::operationTraitCallStatic($key, $args);
    }




    /**
     * 静态工具
     * !! 子类不要覆盖
     */

    /**
     * 判断核心类 是什么类型 app|module|... 不是任何类型 == core
     * @param String $type 要判断的 类型 app|module|...|core 默认 null 返回所属类型 字符
     * @return Bool|String 传入 $type 则返回 bool 不传入则返回 类型字符
     */
    final public static function isA($type=null)
    {
        $cls = static::class;
        //去除可能存在的 NS 头
        if (defined("NS")) $cls = str_replace(NS,"", $cls);
        //去除可能存在的 默认 NS 头
        $cls = str_replace("Spf\\","", $cls);
        //类名数组
        $clsarr = explode("\\", trim($cls, "\\"));
        //可能的 type
        $tpcls = Cls::find($clsarr[0], "Spf\\");
        if (!empty($tpcls)) {
            $tp = $clsarr[0];
        } else {
            $tp = "core";
        }
        //类型转为 FooBar 形式
        $tp = Str::camel($tp, true);

        if (Is::nemstr($type)) {
            return Str::camel($type, true) === $tp;
        }
        return $tp;
    }

    /**
     * 筛选 核心类的 特殊方法 api|view|...
     */
    
}