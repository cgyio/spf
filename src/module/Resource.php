<?php
/**
 * 框架模块类
 * Resource 资源处理模块
 */

namespace Spf\module;

use Spf\Module;

class Resource extends Module 
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
    public $name = "Resource";

    
}