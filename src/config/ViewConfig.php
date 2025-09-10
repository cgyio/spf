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

        //视图文件实际路径
        "page" => "",

        //语言
        "lang" => "zh-CN",

        //title 网页标题 前缀
        "title" => "",

        //favicon 图片地址
        "favicon" => [
            //图标 url 地址
            "href" => "/src/icon/spf/logo-light.svg",
            //图表类型，默认 svg
            "type" => "image/svg+xml",
        ],

        //theme 视图使用的 SPF-Theme 主题
        "theme" => [
            //是否启用 SPF-Theme 主题
            "enable" => true,
            //主题名称
            "name" => "spf",
            //是否启用 暗黑模式
            "darkmode" => true,
            
            /**
             * 可以分别指定 明|暗 模式下的 theme 输出的 mode
             * 例如指定了 mode["dark"] = "foo,bar" 则：
             *      在 暗黑模式下，输出的 css 地址为 /[app_name/]src/theme/[theme_name].min.css?mode=foo,bar
             * 
             * 如果未启用 暗黑模式，则默认使用 light 模式中指定的 mode
             */
            "mode" => [
                "light" => "light",
                "dark" => "dark",
            ],

            /**
             * 可以指定 使用的 主题相关的 scss 文件
             * 这些文件，必须在主题中已定义
             * 默认 all
             */
            "use" => "all",
        ],

        //icon 视图使用的 SPF-Theme icon 图标库，可以同时使用多个图标库
        "iconset" => [
            //默认使用 spf 图标库
            "spf",
        ],

        //css 样式文件
        "css" => [
            //"css 文件的 url 通常为 /src/foo/bar.css 形式",
            //...
        ],

        //meta 相关
        //编码
        "charset" => "utf-8",

        //viewport 视口相关
        //当前使用的 视口设置 名称，可自定义
        "viewport" => "default",
        "viewports" => [
            /**
             * 预定义的一些 视口设置参数
             */
            //默认
            "default" => "width=device-width, initial-scale=1.0",
            //移动端
            "mobile" => "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no",
            //iOS
            "ios" => "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover",

            //可自定义其他的 视口设置参数
            //...
        ],

        //PWA 相关 （网页作为应用 安装到本地）在 SPA 类型的 View 视图中，可以启用 PWA 参数
        "pwa" => [
            //是否启用 PWA 相关设置，不启用，则下列的参数不会出现在 html 代码中
            "enable" => false,

            //应用名称
            "application-name" => "",

            //应用图标，需要使用 link 标签
            //普通设备的 图标，使用 favicon 定义的
            //"icon" => [
            //    "size" => "192x192",
            //    "href" => "",
            //],
            //苹果设备的 图标
            "apple-touch-icon" => [
                "href" => "/src/icon/spf/logo-app.svg",
                "type" => "image/svg+xml",
                "sizes" => "180x180",
            ],

            //浏览器 工具栏|地址栏 背景色，仅在部分移动端设备中生效
            "theme-color" => "",

            //仅针对 苹果设备
            //允许 PWA 全屏
            "apple-mobile-web-app-capable" => "yes",
            //状态栏样式
            "apple-mobile-web-app-status-bar-style" => "black",
        ],

        //seo 相关
        //网页描述
        "description" => "",
        //关键字
        "keywords" => "",
        //作者
        "author" => "",
        //版权
        "copyright" => "",
            
        //robots 爬虫相关
        "robots" => "noindex, nofollow",

        
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
         * 视图类的 实例化参数，由 输出时 手动传入，直接返回
         */
        return $opt;
    }
    
    /**
     * 处理设置值
     * 设置值支持格式：String, IndexedArray, Numeric, Bool, null
     * !! 覆盖父类，直接返回，不做处理
     * @param Mixed $val 要处理的设置值
     * @param Closure $callback 对设置值进行自定义处理的方法，参数为 原始设置值，返回处理后的设置值
     * @return Mixed 处理后的设置值，不支持的格式 返回 null
     */
    public function fixConfVal($val = null, $callback = null)
    {
        return $val;
    }

}