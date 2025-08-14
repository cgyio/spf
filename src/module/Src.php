<?php
/**
 * 框架模块类
 * Src 资源处理模块
 */

namespace Spf\module;

use Spf\Module;
use Spf\module\src\Resource;

class Src extends Module 
{
    /**
     * 单例模式
     * !! 覆盖父类，具体模块子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    /**
     * 模块的元数据
     * !! 实际模块类必须覆盖
     */
    //模块的说明信息
    public $intr = "资源处理模块";
    //模块的名称 类名 FooBar 形式
    public $name = "Src";



    /**
     * 资源处理模块启用后，将在实例化后，立即执行此 初始化操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function initModule()
    {

        return true;
    }



    /**
     * default
     * @desc 资源输出
     * @export src
     * @auth false
     * @pause false 资源输出不受WEB_PAUSE影响
     * @param Array $args url 参数
     * @return Src 输出
     */
    public function default(...$args)
    {
        var_dump($args);
    }

    
}