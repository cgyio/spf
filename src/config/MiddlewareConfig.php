<?php
/**
 * 框架核心配置类
 * 中间件 配置类
 */

namespace Spf\config;

use Spf\util\Is;
use Spf\util\Str;

class MiddlewareConfig extends Configer 
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
        //此中间件是否 仅 开发环境下 可用
        "dev" => false,
    ];



    /**
     * 在初始化时，处理外部传入的 用户设置，例如：提取需要的部分，过滤 等
     * !! 覆盖父类
     * @param Array $opt 外部传入的 用户设置内容
     * @return Array 处理后的 用户设置内容
     */
    protected function fixOpt($opt=[])
    {
        /**
         * 中间件 配置参数，在 App 实例化时已处理过，直接传入即可
         */
        return $opt;
    }

    /**
     * 在 应用用户设置后 执行 自定义的处理方法
     * !! 覆盖父类
     * @return $this
     */
    public function processConf()
    {
        

        return $this;
    }
}