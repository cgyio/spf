<?php
/**
 * 框架核心配置类 
 * 应用配置类 基类，所有实际应用配置类 都必须 继承此类
 */

namespace Spf\config;

class AppConfig extends Configer 
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
        //在此应用中需要启用的 模块
        "module" => [
            /*
            "module_name" => [
                "enable" => true,
                # 其他参数参考对应的 模块配置类中的 init 属性值
                ...
            ],
            */
        ],

        //在此应用中需要启用的 中间件，以及其配置参数
        "middleware" => [

            //入站中间件
            "in" => [

            ],

            //出站中间件
            "out" => [

            ],

            //中间件的配置参数
            /*
            "中间件类全称" => [
                # 配置参数内容
                ...
            ],
            ...
            */
        ],
    ];

    

    /**
     * 在 应用用户设置后 执行 自定义的处理方法
     * !! 覆盖父类
     * @return $this
     */
    public function processConf()
    {
        var_dump("---- appConfig processConf ----");
        var_dump($this->context);
    }
}