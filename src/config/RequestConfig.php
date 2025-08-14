<?php
/**
 * 框架核心配置类
 * 请求处理 配置类
 */

namespace Spf\config;

use Spf\util\Is;
use Spf\util\Str;

class RequestConfig extends Configer 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [
        
    ];

    

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