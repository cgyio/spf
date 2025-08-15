<?php
/**
 * 框架核心配置类
 * 相应处理 配置类
 */

namespace Spf\config;

use Spf\util\Is;
use Spf\util\Str;

class ResponseConfig extends Configer 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [
        //定义默认的 view 视图页面路径
        "view" => [
            //输出异常 视图页面
            "exception" => "spf/view/exception.php",
            //响应状态码 视图页面
            "code" => "spf/view/http_code.php",
            //WEB_PAUSE 输出视图页面
            "pause" => "spf/view/pause.php",
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