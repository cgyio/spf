<?php
/**
 * 框架核心类
 * 框架运行时环境参数 管理类
 */

namespace Spf;

use Spf\Core;
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
         * 最后
         * 调用父类 __get 方法
         */
        $rtn = parent::__get($key);
        if (!is_null($rtn)) return $rtn;

        return null;
    }
}