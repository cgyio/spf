<?php
/**
 * 框架模块配置类
 * Test 测试模块
 */

namespace Spf\module\test;

use Spf\config\ModuleConfig;

class ModuleTestConfig extends ModuleConfig 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [
        
        //此模块是否受 WEB_PAUSE 影响，默认 true，此模块下的操作方法可自行在 注释中覆盖此参数
        //!! 测试模块，不应受 WEB_PAUSE 控制
        "pause" => false,

        //此模块是否 仅 开发环境下 可用
        //!! 测试模块 只能在开发环境下使用
        "dev" => true,

        //依赖的 其他模块
        "dependency" => [
            //...
        ],

        //此模块必须的 中间件
        "middleware" => [
            //入站
            "in" => [
                "module/test/middleware/in/blockifnotdev",
            ],
            //出站
            "out" => [],
        ],


    ];

    

    /**
     * 在 应用用户设置后 执行 自定义的处理方法
     * !! 覆盖父类
     * @return $this
     */
    public function processConf()
    {
        
    }

}