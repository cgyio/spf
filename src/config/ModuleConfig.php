<?php
/**
 * 框架核心配置类
 * 模块配置类 基类
 */

namespace Spf\config;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Cls;

class ModuleConfig extends Configer 
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

        //是否启用此模块，默认 false
        "enable" => false,

        //此模块必须的 中间件
        "middleware" => [
            //入站
            "in" => [],
            //出站
            "out" => [],
            //中间件配置参数
            //...
        ],
        
        //其他模块参数，应在对应的模块配置类 $init 属性中定义
        //...
    ];



    /**
     * 根据当前 配置类全称，获取对应的 模块类名
     *      Spf\module\foo_bar\ModuleFooBarConfig           -->  FooBar
     *      NS\module\app_name\foo_bar\ModuleFooBarConfig   -->  FooBar
     * @return String 模块类名 FooBar 形式
     */
    public static function moduleClsn()
    {
        $cls = static::class;
        $clsn = Cls::name($cls);
        //截取 模块类名
        if (substr($clsn, 0, 6)==="Module") $clsn = substr($clsn, 6);
        if (substr($clsn, -6)==="Config") $clsn = substr($clsn, 0, -6);
        return $clsn;
    }
}