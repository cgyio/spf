<?php
/**
 * 框架核心配置类
 * 视图配置类 基类
 */

namespace Spf\config;

use Spf\Middleware;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class ViewConfig extends Configer 
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
        /**
         * 定义所有 视图类的 支持的传入参数结构
         */

        //favicon
        "favicon" => "/src/icon/spf/logo-light.svg",

        //theme 视图使用的 SPF-Theme 主题，指定 *.theme 的路径
        "theme" => "spf/assets/theme/spf.theme",
        
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
         * 模块的 配置参数，在 App 实例化时已处理过，直接传入即可
         */
        return $opt;
    }

    /**
     * 定义 配置参数 合并方法 默认使用 Arr::extend 覆盖方向： $opt --> $init --> $dftInit
     * !! 覆盖父类
     * @return $this
     */
    /*public function extendConf()
    {
        

        return $this;
    }*/

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