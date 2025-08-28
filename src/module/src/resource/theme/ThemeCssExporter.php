<?php
/**
 * SPF-Theme 主题输出类
 * 输出 CSS 文件
 */

namespace Spf\module\src\resource\theme;

use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use ScssPhp\ScssPhp\Compiler as scssCompiler;
use ScssPhp\ScssPhp\OutputStyle as scssOutputStyle;

class ThemeCssExporter extends ThemeExporter 
{
    /**
     * 主题资源输出 核心入口方法 创建输出资源的 内容
     * !! 覆盖父类
     * @return String 要输出的 主题资源内容
     */
    public function createContent()
    {
        /**
         * 创建此主题的 CSS 文件内容
         * 
         *  0   生成此主题的 CSS 变量语句
         *  1   调用 SCSS Exporter 生成 此主题的 SCSS 内容
         *  2   调用 ScssPhp Complier 编译得到的 SCSS 内容 生成最终的 CSS 文件内容
         */

        // 0    生成此主题的 CSS 变量语句
        $this->createCssVars();

        // 1    调用 SCSS Exporter 生成 SCSS 变量定义语句
        $scsser = new ThemeScssExporter($this->theme, $this->context);
        $scsscnt = $scsser->createContent();
        //缓存到 content
        $this->content[] = $scsscnt;

        // 2    调用 ScssPhp Complier 编译得到的 SCSS 内容 生成最终的 CSS 文件内容
        $scss = $this->rowCnt();
        //echo $scss;
        //exit;
        //默认不使用 compress 压缩，因为在 theme->beforeExport 方法中会根据 params["min"] 决定是否压缩输出
        $css = $this->parseScss($scss, false);
        //生成 输出内容
        return $css;
    }



    /**
     * 文件内容生成方法
     */

    /**
     * 解析 context 得到 各种 CSS 变量，生成 变量定义语句 存入 content 缓存
     * @return $this
     */
    protected function createCssVars()
    {
        $ctx = $this->context;

        //CSS 头部 语句
        $this->rowAdd("/** SPF-Theme 主题 CSS 变量 **/", "");
        $this->rowAdd("/** !! 不要手动修改 !! **/", "");
        //空行
        $this->rowAddEmpty(2);
        //:root {
        $this->rowAdd(":root {", "");
        //空行
        $this->rowAddEmpty(1);

        //按主题模块 分别执行
        foreach ($ctx as $modk => $modc) {
            //获取对应的 主题模块实例
            $modins = $this->theme->module($modk);
            if (empty($modins)) continue;
            //模块输出 头部语句
            $this->rowAdd("/** $modk 模块变量定义 **/", "");
            //调用 各 主题模块的 CSS 变量定义语句 生成方法
            $modins->createCssVarsDefineRows($this, $modc);
            //空行
            $this->rowAddEmpty(1);
        }

        //结尾
        //空行
        $this->rowAddEmpty(1);
        $this->rowAdd("}", "");
        //空行
        $this->rowAddEmpty(1);

        return $this;
    }



    /**
     * 调用工具 解析 scss 内容
     * @param String $scss 内容字符串
     * @param Bool $compressed 是否压缩字符串，默认 true
     * @return String 解析得到的 css 字符串
     */
    protected function parseScss($scss="", $compressed=true)
    {
        if (!Is::nemstr($scss)) return "";
        //var_dump($scss);
        $compiler = new scssCompiler();
        $outputStyle = $compressed ? scssOutputStyle::COMPRESSED : scssOutputStyle::EXPANDED;
        $compiler->setOutputStyle($outputStyle);
        //$compiler->setImportPaths($this->basePath);
        $cnt = "";
        try {
            $cnt = $compiler->compileString($scss)->getCss();
        } catch (\Exception $e) {
            //trigger_error("custom::Complie SCSS to CSS Error", E_USER_ERROR);
            throw new SrcException("SCSS 文件编译为 CSS 发生错误", "resource/export");
        }
        return $cnt;
    }
}