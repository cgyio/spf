<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Icon 子类
 * 继承自 Plain 纯文本类型基类，处理 *.scss 类型本地文件
 */

namespace Spf\module\src\resource;

use Spf\App;
use Spf\module\src\Resource;
use Spf\module\src\Mime;
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Conv;
use Spf\util\Path;

use ScssPhp\ScssPhp\Compiler as scssCompiler;
use ScssPhp\ScssPhp\OutputStyle as scssOutputStyle;

class Scss extends Plain 
{
    /**
     * 此类型纯文本资源的 注释符 [ 开始符号, 每行注释的开头符号, 结尾符号 ]
     * !! 覆盖父类
     */
    public $cm = ["/**", " * ", " */"];

    /**
     * 此类型的纯文本资源，如果可用 import 语句，则 指定 import 语法
     * 默认 为 null，表示不处理 或 不适用 import 语法
     * js 文件的 import 语句不在此处处理，直接输出
     * !! 各子类可以定义各自的 import 语句语法 正则
     */
    public $importPattern = "/@import\s+['\"](.+)['\"];?/";

    /**
     * 定义 可用的 params 参数规则
     * 参数项 => 默认值
     */
    protected static $stdParams = [
        //是否 强制不使用 缓存的 glyphs
        "create" => false,
        //输出文件的 类型 css|scss
        "export" => "css",
        //可指定要合并输出的 其他 scss 文件
        "use" => "",
        //是否忽略 @import 默认 false
        "noimport" => false,
        //...
    ];

    /**
     * 定义支持的 export 类型，必须定义相关的 createFooContent() 方法
     * 必须是 Mime 支持的 文件后缀名
     * !! 覆盖父类
     */
    protected static $exps = [
        "css", "scss",
    ];



    /**
     * 不同 export 类型，生成不同的 content
     * !! 子类可以根据 exps 中定义的可选值，实现对应的 createFooContent() 方法
     * @return $this
     */
    //生成 CSS content
    protected function createCssContent()
    {
        //解析 scss 为 css，默认不压缩
        $this->content = static::parseScss($this->content, false);
        return $this;
    }
    //生成 SCSS content
    protected function createScssContent()
    {
        return $this;
    }



    /**
     * 静态工具
     */

    /**
     * 调用工具 解析 scss 内容
     * @param String $scss 内容字符串
     * @param Bool $compressed 是否压缩字符串，默认 true
     * @return String 解析得到的 css 字符串
     */
    public static function parseScss($scss="", $compressed=true)
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