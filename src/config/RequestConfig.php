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
        //可通过 $_GET 传入的 开关标记  ?foo=yes|no
        "switches" => [
            //是否通过 dump 方式输出数据 默认 no
            "dump" => "no",
        ],
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