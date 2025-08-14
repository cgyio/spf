<?php
/**
 * 框架模块类
 * ORM 数据库模块
 */

namespace Spf\module;

use Spf\Module;

class Orm extends Module 
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
    public $intr = "ORM数据库支持模块";
    //模块的名称 类名 FooBar 形式
    public $name = "Orm";



    /**
     * ORM 数据库操作类 获取操作列表 的自定义方法
     * !! 引用的类可覆盖
     * @return Array 标准的 操作列表数据格式
     */
    public static function getOprs()
    {
        //从此模块类中 获取 util/Operation::$types 中定义的 特殊类型的方法，得到 操作列表 标准数据格式
        $oprs = parent::getOprs();

        //ORM 数据库操作类 额外的 操作类型
        //...

        //返回找到的 操作列表 标准数据格式
        return $oprs;
    }

    /**
     * api
     * @desc ORM模块的测试Api
     */
    public function ormTestApi()
    {
        
    }

    
}