<?php
/**
 * 框架核心类
 * 框架运行时环境参数 管理类
 */

namespace Spf;

use Spf\exception\CoreException;
use Spf\util\Autoloader;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Env extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;



    /**
     * Env 环境参数类自有的 init 方法，执行以下操作：
     *  0   patch Autoloader 将 webroot 路径下的 app|model|module|error 等目录 添加到 自动加载类的路径数组中
     * !! 子类必须实现
     * @return $this
     */
    public function initialize()
    {
        // 0 为 composer 的类自动加载方法 打补丁，将 webroot 路径下的 app|model|module|error 等目录 添加到 自动加载类的路径数组中
        Autoloader::patch();

        return $this;
    }

    /**
     * 快捷访问 __get
     * !! 覆盖父类 必须在子类调用 parent::__get()
     * @param String $key 要访问的 不存在的 属性
     * @return Mixed
     */
    public function __get($key)
    {
        /**
         * $this->path  -->  (object)$this->config->context["path"]
         * $this->path->root
         * $this->path->app
         */
        if ($key === "path") return (object)$this->config->path;

        /**
         * $this->dev  --> $this->config->ctx("web/dev")
         * 判断 开发环境|生产环境
         */
        if ($key === "dev") {
            $dev = $this->config->ctx("web/dev");
            return is_bool($dev) && $dev === true;
        }

        /**
         * $this->fooBarEnabled  -->  FOO_BAR === true
         */
        if (substr($key, -7) === "Enabled") {
            $ck = substr($key, 0, -7);
            $cv = $this->config->$ck;
            if (is_bool($cv)) return $cv;
        }


        /**
         * 最后
         * 调用父类 __get 方法
         */
        $rtn = parent::__get($key);
        if (!is_null($rtn)) return $rtn;

        return null;
    }

    /**
     * 快捷判断 WEB_*** 开关的 开启状态
     */

    /**
     * forDev
     * event-handler
     * @event test_evt
     * @once true
     * 
     */
    public function handleTestEvtEvent($triggerBy, ...$args)
    {
        var_dump("event test_evt handled by Env");
        var_dump(get_class($triggerBy));
        var_dump($args);
    }
}