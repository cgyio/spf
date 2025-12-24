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
            //"type" => "image/svg+xml",
        ],

        /**
         * 视图使用的 复合前端资源
         * 需要单独解析的
         */
        "compound" => [
            //视图使用的 符合前端资源 强制刷新开关 ==true 表示所有的依赖的复合资源全部忽略缓存，适用于更新依赖资源
            "create" => false,

            /**
             * 视图使用的 统一 Vcom|Vcom3 Vue 组件库 SPA 环境
             * !! 如果启用了统一 SPA 环境，则不再解析下方的 theme|iconset 直接通过 Vcom|Vcom3 引入主题和图标库
             */
            "spa" => [
                //默认不启动，需要时开启
                "enable" => false,
                /**
                 * SPA 环境的基础组件库
                 * !! 此组件库中必须包含 theme|iconset 
                 */
                "base" => [
                    //基础 Vcom|Vcom3 组件库 *.[vcom|vcom3].json 文件真实路径，!! 不可省略后缀名
                    "file" => "spf/assets/vcom/spf.vcom.json",
                    //组件库资源实例化参数，与 Vcom|Vcom3::$stdParams 格式一致
                    "params" => [],
                    //组件库插件在 Vue.use(plugin, options={...}) 时传入的 options 参数
                    "options" => [],
                ],
                /**
                 * 在 SPA 环境基础上，可使用 业务组件库
                 * !! 必须与 基础环境的 Vcom 版本一致
                 * !! 可以使用多个 业务组件库
                 */
                "app" => [
                    /*
                    "foo" => [
                        "file" => "src/app_foo/vcom/foo.vcom.json",
                        "params" => [],
                        "options" => [],
                    ],
                    ...
                    */
                ],
            ],

            /**
             * 视图使用的 SPF-Theme 主题
             */
            "theme" => [
                //是否启用主题
                "enable" => true,
                //主题的 *.theme.json 文件真实路径，可以省略 .theme.json 后缀名
                "file" => "spf/assets/theme/spf",
                //主题资源实例化参数，与 Theme::$stdParams 格式一致
                "params" => [
                    //主题模式，可选 light|dark|light,mobile|dark,mobile|...
                    "mode" => "light",
                ],
            ],

            /**
             * 视图使用的 SPF-Icon 图标库
             */
            "iconset" => [
                //图标库的 *.icon.json 文件真实路径，可以省略 .icon.json 后缀名
                "spf/assets/icon/spf",
                //可以指定多个
                //...
            ],
        ],

        /**
         * 视图使用的 静态 css|js 外部文件
         * 在 head 段通过 link|script 引入
         * 按顺序依次引入，可以有多个，
         * 不同类型文件都在此数组中指定，自动识别
         * !! 引用的 js 不能使用 esm 形式 
         */
        "static" => [
            //指定完整的 css|js url
        ],

        /**
         * 视图使用的 动态 scss|js 本地资源
         * scss 资源中可能使用了 主题参数，需要通过 SPF-Theme 主题资源实例 merge 合并引入
         * !! 按顺序 merge 进 SPF-Theme 资源实例中
         */
        "merge" => [
            //指定真实存在的 scss|js 本地文件路径
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