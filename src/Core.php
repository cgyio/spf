<?php
/**
 * 框架核心类 基类
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
use Spf\Configer;
use Spf\Exception;
use Spf\exception\CoreException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;

class Core 
{
    /**
     * 单例模式
     * !! 子类必须覆盖这些 静态属性|方法
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

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
        } catch (CoreException $e) {
            //核心类实例化失败，终止响应
            $e->handleException(true);
        }

        //返回 核心实例
        return static::$current;
    }

    /**
     * 核心类构造方法，protected 不能通过 new 方式创建 核心类 实例
     * !! 子类可覆盖此方法
     * @param Array $opt 框架启动参数中关于此核心类的 参数
     * @param Array $args 实例化参数
     * @return void
     */
    protected function __construct($opt=[], ...$args)
    {
        //核心类实例化时 同步实例化 核心类对应的 config 参数配置类
        $this->initConfig($opt);
        
    }



    /**
     * 参数配置类 实例
     */
    public $config = null;
    /**
     * 实例化 参数配置类
     * !! 子类可覆盖此方法
     * @param Array $args 核心类参数配置类的 实例化参数，通常是 框架启动参数中 关于此核心类的 参数
     * @return Configer 实例
     */
    public function initConfig(...$args)
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
            $cfgcls = Cls::find("app/$appk/config/$cfgn");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //在 框架默认的路径下查找
            $cfgcls = Cls::find("config/$cfgn", "Spf\\");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //默认路径下，也没有此核心类的 配置类，则使用 CoreConfig 类，此类一定存在
            $cfgcls = Cls::find("config/CoreConfig", "Spf\\");
        }

        //实例化 配置类
        $cfger = new $cfgcls(...$args);
        //缓存实例
        $this->config = $cfger;
        //返回 实例化的 配置类
        return $cfger;
    }



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
     * 静态工具
     * !! 子类不要覆盖
     */

    /**
     * 返回当前核心类的 类名 FooBar 格式
     * @return String
     */
    final public static function clsn()
    {
        return Cls::name(static::class);
    }

    /**
     * 返回当前核心类的 类名 foo_bar 格式
     * @return String
     */
    final public static function clsk()
    {
        $clsn = static::clsn();
        return Str::snake($clsn, "_");
    }
    
}