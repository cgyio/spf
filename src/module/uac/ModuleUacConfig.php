<?php
/**
 * 框架模块配置类
 * Uac 权限控制模块
 */

namespace Spf\module\uac;

use Spf\config\ModuleConfig;

class ModuleUacConfig extends ModuleConfig 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [
        
        //此模块是否受 WEB_PAUSE 影响，默认 true，此模块下的操作方法可自行在 注释中覆盖此参数
        //"pause" => true,

        //此模块是否 仅 开发环境下 可用
        //"dev" => true,

        //依赖的 其他模块
        "dependency" => [
            //此模块依赖 Orm 模块
            "orm" => [
                //必须启用
                "enable" => true,
                //其他 orm 模块参数
                /**
                 * 默认不提供参数，当前应用的 参数中 必须开启 Orm 模块，并传入参数
                 * 如果此处 传入了 参数，则可以不在 应用参数中启用 Orm 模块
                 */
            ],
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