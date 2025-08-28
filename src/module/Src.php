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
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
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
     * 针对 特定类型资源的 响应方法
     */

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
        //传入空参数，报 404
        if (!Is::nemarr($args)) return $this->responseCode(404);
        
        //拼接请求的 路径字符串
        $req = implode("/", $args);
        //解析对应的 后缀名|文件夹|文件名
        $pi = pathinfo($req);
        $ext = $pi["extension"] ?? "";
        $dir = $pi["dirname"] ?? "";
        $fn = $pi["filename"] ?? "";

        //处理请求的资源上级路径
        if (!Is::nemstr($dir) || in_array($dir, ["."])) $dir = "";

        //处理请求资源的 后缀名
        if (!Is::nemstr($ext)) $ext = "theme";
        $ext = strtolower($ext);

        //支持输出的 ext 格式
        $exts = ["theme", "js", "css", "scss"];
        //请求的 后缀名不支持，报 404
        if (!in_array(strtolower($ext), $exts)) return $this->responseCode(404);

        //请求的文件名为空，报 404
        if (!Is::nemstr($fn)) return $this->responseCode(404);

        //是否存在 .min.
        $ismin = false;
        if (substr(strtolower($fn), -4)===".min") {
            $ismin = true;
            $fn = substr($fn, 0, -4);
        }

        //首先定位 请求的主题 *.theme 文件，创建 Theme 主题资源实例
        if ($ext === "theme") {
            $thfp = substr($req, -6)===".theme" ? $req : $req.".theme";
        } else {
            $thfp = ($dir=="" ? "" : $dir."/").$fn.".theme";
        }
        //调用 Resource::findLocal() 方法 查找对应的 *.theme 文件
        $thf = Resource::findLocal($thfp);
        //未找到 *.theme 文件
        if (!file_exists($thf)) return $this->responseCode(404);

        //主题资源实例 的 params 参数
        $ps = [];
        if ($ismin) $ps["min"] = true;
        if ($ext !== "theme") {
            $ps["export"] = $ext;
        }
        
        //创建 Theme 资源实例
        $theme = Resource::create($thf, $ps);
        if (!$theme instanceof Resource) {
            //报错
            throw new SrcException("$thfp 文件资源无法实例化", "resource/instance");
        }

        //根据请求的后缀名，决定输出的资源内容
        if ($ext === "theme") {
            //输出 主题编辑器 视图
            //当前响应方法的 输出类默认为 view 无需修改
            //Response::insSetType("view");
            
            //TODO: 创建 ThemeView 实例
            return [
                //使用 ThemeView 视图类
                "view" => "view/ThemeView",
                //传入 Theme 资源实例作为 视图类的 实例化参数
                "params" => [
                    "theme" => $theme
                ]
            ];

        }
        
        //根据 ext 输出对应的 JS|CSS|svg 资源
        //将 Response 输出类型 改为 src
        Response::insSetType("src");
        //输出资源
        return $theme;
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
        //传入空参数，报 404
        if (!Is::nemarr($args)) return $this->responseCode(404);
        
        //拼接请求的 路径字符串
        $req = implode("/", $args);
        //解析对应的 后缀名|文件夹|文件名
        $pi = pathinfo($req);
        $ext = $pi["extension"] ?? "";
        $dir = $pi["dirname"] ?? "";
        $fn = $pi["filename"] ?? "";

        //处理请求的资源上级路径
        if (!Is::nemstr($dir) || in_array($dir, ["."])) $dir = "";

        //处理请求资源的 后缀名
        if (!Is::nemstr($ext)) $ext = "icon";
        $ext = strtolower($ext);

        //支持输出的 ext 格式
        $exts = ["icon", "js", "css", "svg"];
        //请求的 后缀名不支持，报 404
        if (!in_array(strtolower($ext), $exts)) return $this->responseCode(404);

        //如果请求的是 单个 svg 且 上级路径为空，报 404
        if ($ext === "svg" && !Is::nemstr($dir)) return $this->responseCode(404);

        //请求的文件名为空，报 404
        if (!Is::nemstr($fn)) return $this->responseCode(404);

        //是否存在 .min.
        $ismin = false;
        if (substr(strtolower($fn), -4)===".min") {
            $ismin = true;
            $fn = substr($fn, 0, -4);
        }

        //首先定位 请求的图标库 *.icon 文件，创建 Icon 图标库资源实例
        if ($ext === "icon") {
            $iconf = substr($req, -5)===".icon" ? $req : $req.".icon";
        } else if ($ext === "svg") {
            $iconf = $dir.".icon";
        } else {
            $iconf = ($dir=="" ? "" : $dir."/").$fn.".icon";
        }
        //调用 Resource::findLocal() 方法 查找对应的 *.icon 文件
        $icf = Resource::findLocal($iconf);
        //未找到 *.icon 文件
        if (!file_exists($icf)) return $this->responseCode(404);

        //icon 资源实例 的 params 参数
        $ps = [];
        if ($ismin) $ps["min"] = true;
        if ($ext === "svg") {
            $ps["icon"] = $fn;
        } else {
            $ps["export"] = $ext==="icon" ? "css" : $ext;
        }
        
        //创建 icon 资源实例
        $icon = Resource::create($icf, $ps);
        if (!$icon instanceof Resource) {
            //报错
            throw new SrcException("$iconf 文件资源无法实例化", "resource/instance");
        }

        //根据请求的后缀名，决定输出的资源内容
        if ($ext === "icon") {
            //输出 图标库 列表视图
            //当前响应方法的 输出类默认为 view 无需修改
            //Response::insSetType("view");
            
            return [
                //使用 视图页面 spf/view/iconset.php
                "view" => "spf/view/iconset.php",
                //传入 Icon 资源实例作为视图页面参数
                "params" => [
                    "icon" => $icon
                ]
            ];

        }
        
        //根据 ext 输出对应的 JS|CSS|svg 资源
        //将 Response 输出类型 改为 src
        Response::insSetType("src");
        //输出资源
        return $icon;
    }

    /**
     * forDev
     * api
     * @desc 资源模块测试方法
     */
    public function srcTestApi()
    {
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