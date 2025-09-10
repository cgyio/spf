<?php
/**
 * 框架核心配置类
 * 相应处理 配置类
 */

namespace Spf\config;

use Spf\App;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;

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
            "exception" => "spf/assets/view/exception.php",
            //响应状态码 视图页面
            "code" => "spf/assets/view/http_code.php",
            //WEB_PAUSE 输出视图页面
            "pause" => "spf/assets/view/pause.php",
        ],
    ];



    /**
     * 在初始化时，处理外部传入的 用户设置，例如：提取需要的部分，过滤 等
     * !! 子类可覆盖此方法
     * @param Array $opt 外部传入的 用户设置内容
     * @return Array 处理后的 用户设置内容
     */
    protected function fixOpt($opt=[])
    {
        //先 从框架启动参数中获取 response 参数
        $opt = $opt["response"] ?? [];

        //然后从 当前应用的 config 参数中 获取可能存在的 response 参数
        if (App::$isInsed === true) {
            $app = App::$current;
            $appResponseOpt = $app->config->response;
            if (Is::nemarr($appResponseOpt)) {
                //应用中的参数 覆盖 框架启动参数
                $opt = Arr::extend($opt, $appResponseOpt);
            }
        }

        return $opt;
    }

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