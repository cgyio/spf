<?php
/**
 * 框架模块类
 * Src 资源处理模块
 */

namespace Spf\module;

use Spf\App;
use Spf\Response;
use Spf\Module;
use Spf\module\src\Resource;
use Spf\module\src\Fs;
use Spf\module\src\resource\Lib;
use Spf\module\src\resource\Theme;
use Spf\module\src\resource\Icon;
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;
use Spf\util\Color;

class Src extends Module 
{
    /**
     * 单例模式
     * !! 覆盖父类，具体模块子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    /**
     * 模块的元数据
     * !! 实际模块类必须覆盖
     */
    //模块的说明信息
    public $intr = "资源处理模块";
    //模块的名称 类名 FooBar 形式
    public $name = "Src";



    /**
     * 资源处理模块启用后，将在实例化后，立即执行此 初始化操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function initModule()
    {

        return true;
    }



    /**
     * default
     * @desc 资源输出
     * @export src
     * @auth false
     * @pause false 资源输出不受WEB_PAUSE影响
     * @param Array $args url 参数
     * @return Src 输出
     */
    public function default(...$args)
    {
        //根据 URI 参数，解析并获取 资源
        $resource = Resource::create($args);
        //var_dump(get_class($resource));exit;
        //返回得到的资源，将在 Response::export() 方法中自动调用资源输出
        return $resource;
    }

    /**
     * api
     * @desc 文件系统操作
     * @auth true
     * @role all
     * 
     * @param Array $args url 参数
     * @return Mixed
     */
    public function fsApi(...$args)
    {
        //调用 Fs::response 方法 代理此请求
        return Fs::response(...$args);
    }



    /**
     * 针对 特定类型资源的 响应方法
     */

    /**
     * view
     * @desc 输出前端库文件JS|CSS
     * @auth false
     * @pause false
     * 
     * 请求方法：
     * https://host/[app_name/]src/lib/[foo/bar/][lib_name][.js|css]
     * https://host/[app_name/]src/lib/[foo/bar/][lib_name]/[dev!-esm|esm|...].[js|css]
     * https://host/[app_name/]src/lib/[foo/bar/][lib_name]/[@|latest|1.2.3][.js|css]
     * https://host/[app_name/]src/lib/[foo/bar/][lib_name]/[@|latest|1.2.3]/[esm|...].[js|css]
     * 
     * @param Array $args url 参数
     * @return Mixed
     */
    public function libView(...$args)
    {
        //调用 Lib::response() 方法 代理此请求
        return Lib::response(...$args);
    }

    /**
     * view
     * @desc 输出主题资源JS|CSS|SCSS
     * @auth false
     * @pause false 资源输出不受WEB_PAUSE影响
     * 
     * 请求方法：
     * https://host/[app_name/]src/theme/[theme_name]                       访问 主题编辑器
     * https://host/[app_name/]src/theme/[theme_name][.min].[js|css|scss]   访问图标库 JS|CSS|SCSS 文件
     * 
     * @param Array $args url 参数
     * @return Mixed
     */
    public function themeView(...$args)
    {
        //调用 Theme::response() 方法 代理此请求
        return Theme::response(...$args);
    }

    /**
     * view
     * @desc 输出图标库资源JS|CSS|svg
     * @auth false
     * @pause false 资源输出不受WEB_PAUSE影响
     * 
     * 请求方法：
     * https://host/[app_name/]src/icon/[iconset]                       访问图标库 列表|查找|选用 页面
     * https://host/[app_name/]src/icon/[iconset][.min].[js|css]        访问图标库 JS|CSS 文件
     * https://host/[app_name/]src/icon/[iconset]/[icon-name].svg       访问图表库中的 单个图标 svg
     * 
     * @param Array $args url 参数
     * @return Mixed
     */
    public function iconView(...$args)
    {
        //调用 Icon::response() 方法 代理此请求
        return Icon::response(...$args);
    }



    /**
     * 工具方法
     */

    /**
     * 检查某个路径 是否真实存在的文件路径，
     * 如果是，则直接实例化为文件资源实例，并输出
     * 否则，返回 false
     * @param String $path 资源路径
     * @return Resource|false
     */
    protected function localFileExistsed($path)
    {
        $lf = Resource::findLocal($path);
        if (!file_exists($lf)) return false;

        //如果此文件真实存在，直接创建资源实例 并返回
        $res = Resource::create($lf, [
            //min
            "min" => strpos($path, ".min.") !== false,
        ]);
        if (!$res instanceof Resource) return false;
        //将 Response 输出类型 改为 src
        Response::insSetType("src");
        //输出资源
        return $res;
    }









    

    /**
     * forDev
     * api
     * @desc 资源模块测试方法
     */
    public function srcTestApi()
    {
        //$lcf = Resource::findLocal("lib/element-ui/element-ui-dark.css");
        //var_dump($lcf);exit;


        $path = [
            "/data/vendor/cgyio/spf/src/assets/lib/vue.lib",
            "/data/vendor/cgyio/resper/src/../../spf/src/assets/.foo.css",
            "/data/ms/assets/theme/spf_ms.theme",
            "/data/ms/library/foo.php",
            "/data/ms/library/db/config/dbn.json",
            "/data/ms/app/goods/assets/foo/bar.js",
            "/data/ms/app/goods/assets/uploads/foo/bar.jpg",
            "/data/ms/app/goods/library/db/config/dbn.json",
            "/data/ms/app/goods/library/db/dbn.db",
            "/data/ms/app/goods/library/foo.php",
        ];
        /*$rela = [];
        foreach ($path as $pi) {
            $rela[] = Path::rela($pi);
        }*/
        $urls = [];
        foreach ($path as $pi) {
            $urls[] = Url::src($pi);
        }

        var_dump($path);
        //var_dump($rela);exit;
        var_dump($urls);exit;


        $clrs = [
            "#fa0",
            "#fa07",
            "#ffaa00",
            "#ffaa0077",

            "rgb(255,128,0)",
            "rgb(100%,50%,0)",
            "rgba(255,128,0,.5)",
            "rgb(255 128 0 / .5)",
            "rgb(100% 50% 0 / 50%)",

            "hsl(120,75,65)",
            "hsl(120deg,75%,65%)",
            "hsla(120,75,65,.5)",
            "hsl(120deg 75% 65% / 50%)"
        ];

        $rtn = [];
        foreach ($clrs as $clr) {
            //var_dump($clr." ======> ");
            $color = new Color($clr);
            //var_dump($color);
            $rtn[$clr] = $color->hex()."; ".$color->rgb()."; ".$color->hsl();
        }

        var_dump($rtn);
        exit;
    }



    /**
     * api forDev
     * @desc 颜色参数工具
     * @param String $hex 颜色值 hex 不带 #
     * @return void
     */
    public function colorApi($hex)
    {
        $hex = "#".$hex;
        $color = Color::parse($hex);
        if (!$color instanceof Color) return ["color" => "输入的颜色值不存在"];
        return $color->value();
    }

    
}