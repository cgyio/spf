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

use Spf\config\Configer;
use Spf\exception\BaseException;
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
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;

    //核心类 对应的参数配置类 实例
    public $config = null;
    //外部传入的启动参数 即 核心配置类的构造参数，在核心配置类实例化后，此属性将被释放
    //public $opt = [];



    /**
     * 单例实例化方法
     * CoreClass::current( [] )                 实例化核心类 NS\CoreClass
     * App::current( [], "app/foo_app", ... )   实例化应用类 NS\app\FooApp
     * Module::current( [], "module/orm", ... ) 实例化模块类 NS\module\Orm
     * !! 子类不要覆盖
     * @param Array $opt 框架启动参数，通过 Runtime::start([...]) 传入
     * @param String $cls 实际 实例化的 核心类全称，不指定则实例化当前类，默认 null
     * @param Array $args 核心类构造参数
     * @return Core 核心类实例
     */
    final public static function current($opt=[], $cls=null, ...$args)
    {
        //核心类 实例化 流程
        try {

            //标记 实例化的类 是否是此类的子类
            $issub = false; 

            //确认 要实例化的 核心类类全称，必须是此类 或 此类的子类
            if (!Is::nemstr($cls)) {
                //未指定 实际要实例化的核心类，则实例化此类
                $cls = static::class;
            } else {
                //指定了 实际要实例化的 核心类，必须是 此类的子类
                $ocls = $cls;
                $cls = Cls::find($cls);
                if (!class_exists($cls) || !is_subclass_of($cls, static::class)) {
                    //实例化失败
                    throw new CoreException("指定的核心类 $ocls 不存在", "initialize/core");
                }
                $issub = true;
            }

            //检查是否已经 实例化
            if ($cls::$isInsed === true) return $cls::$current;
            //类名 FooBar
            $clsn = $cls::clsn();

            //创建实例
            $ins = new $cls(...$args);
            if (!$ins instanceof $cls) {
                //实例化失败
                throw new CoreException("核心类 $clsn 实例化失败", "initialize/core");
            }

            //标记 已实例化
            $cls::$isInsed = true;
            //缓存 核心实例
            $cls::$current = $ins;

            /**
             * 如果实例化的 是 此核心类的子类，例如：某个具体的 应用类|模块类 ，还需要将 此核心父类 设置为 isInsed
             * 例如：App::current([], "app/foo") 执行后：
             *      NS\app\Foo::$isInsed === true
             *      NS\App::$isInsed === true
             *      NS\app\Foo::$current === NS\App::$current === $fooInstance
             * 
             * !! 如果此核心父类定义了 $multiSubInsed === true 则不需要设置 isInsed
             */
            if ($issub && static::$multiSubInsed !== true) {
                //标记 已实例化
                static::$isInsed = true;
                //缓存 核心实例
                static::$current = $ins;
            }

            //核心类实例化之后，立即执行：
            // 0 实例化 核心类对应的 config 参数配置类
            $ins->initConfig($opt);
            if (empty($ins->config) || !$ins->config instanceof Configer) {
                //实例化失败
                throw new CoreException($clsn."Config 类实例化失败", "iniaialize/config");
            }

            // 1 核心类自定义的 init 方法
            $ins->initialize();

            // 2 订阅事件
            Event::regist($ins);
            
        } catch (BaseException $e) {
            //核心类实例化失败，终止响应
            $e->handleException(true);
        }

        //返回 核心实例
        return $issub ? $cls::$current : static::$current;
    }

    /**
     * 核心类构造方法，protected 不能通过 new 方式创建 核心类 实例
     * !! 子类可覆盖此方法，必须在内部调用父类构造函数
     * @param Array $args 实例化参数
     * @return void
     */
    protected function __construct(...$args)
    {
        //子类覆盖 ...
    }

    /**
     * 实例化 参数配置类
     * !! 子类可覆盖此方法
     * @param Array $opt 框架启动参数
     * @return Configer 实例
     */
    public function initConfig($opt=[])
    {
        //查找 参数配置类的 类全称
        $cfgcls = $this->getConfigCls();

        //实例化 配置类
        $cfger = new $cfgcls($opt, $this);
        //缓存 配置类实例
        $this->config = $cfger;

        //返回 实例化的 配置类
        return $cfger;
    }

    /**
     * 获取此核心类 对应的 config 配置类 类全称
     * !! 子类可覆盖此方法
     * @return String 类全称
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
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //未找到配置类，报错
            throw new CoreException("未找到 $cfgn 配置类", "initialize/config");
        }
        return $cfgcls;
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
        /**
         * static::foo()            -->  static::$current->foo
         * static::foo(...args)     -->  static::$current->foo(...args)
         * 以 静态方法 形式 调用 单例的 属性|方法
         * !! 核心类单例必须已经创建
         */
        if (static::$isInsed === true) {
            //核心类 单例
            $ins = static::$current;
            if (!Is::nemarr($args)) {
                //访问 单例的 属性 或 __get($key)
                $rtn = $ins->$key;
                if (!is_null($rtn)) return $rtn;
            }
            //尝试访问 单例的 方法
            if (method_exists($ins, $key)) return call_user_func_array([$ins, $key], $args);
        }

        //调用 BaseTrait::__callStatic
        return static::operationTraitCallStatic($key, $args);
    }




    /**
     * 静态工具
     * !! 子类不要覆盖
     */
    
}