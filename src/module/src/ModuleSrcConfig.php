<?php
/**
 * 框架模块配置类
 * Src 资源处理模块
 */

namespace Spf\module\src;

use Spf\config\ModuleConfig;

class ModuleSrcConfig extends ModuleConfig 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [
        
        //此模块是否受 WEB_PAUSE 影响，默认 true，此模块下的操作方法可自行在 注释中覆盖此参数
        //"pause" => true,

        //此模块是否 仅 开发环境下 可用
        //"dev" => false,

        //依赖的 其他模块
        //"dependency" => [
            /*
            "mod_name" => [
                # 模块参数 与 此数组 结构一致
                "middleware" => [],
                ...
            ],
            */
        //],

        //Resource 资源管理类的 参数
        "resource" => [
            //定义允许直接访问资源的 路径列表，这些路径名必须在 Env::$current->config->dir 数组中定义
            "access" => [
                "src", "view", "upload",
                //框架内部文件
                "spf/assets",
                //"spf/view",
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