<?php
/**
 * SPF-Theme 主题输出类
 * 输出 JS 文件
 */

namespace Spf\module\src\resource\theme;

use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Conv;
use ScssPhp\ScssPhp\Compiler as scssCompiler;
use ScssPhp\ScssPhp\OutputStyle as scssOutputStyle;

class ThemeJsExporter extends ThemeExporter 
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
         *  0   将 context 内容 直接输出为 Js 语句
         *  1   为 JS 语句 增加 ES6 export 支持
         */

        $ctx = $this->context;
        
        //JS 头部 语句
        $this->rowAdd("/** SPF-Theme 主题 JS 变量 **/", "");
        $this->rowAdd("/** !! 不要手动修改 !! **/", "");
        //空行
        $this->rowAddEmpty(2);

        //const cssvar
        $ctxjson = Conv::a2j($ctx);
        $this->rowAdd("const cssvar = JSON.parse(\"".$ctxjson."\")", ";");
        $this->rowAddEmpty(1);
        $this->rowAdd("export default cssvar");
        $this->rowAddEmpty(1);

        //返回 JS 代码
        return $this->rowCnt();
    }
}