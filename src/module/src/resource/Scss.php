<?php
/**
 * Codex 类型资源类
 * 处理 SCSS 文件资源
 */

namespace Spf\module\src\resource;

use Spf\module\src\SrcException;
use Spf\Request;
use Spf\Response;
use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;

use ScssPhp\ScssPhp\Compiler as scssCompiler;
use ScssPhp\ScssPhp\OutputStyle as scssOutputStyle;

class Scss extends Codex
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    public static $stdParams = [

        /**
         * !! 无法通过 url 传递 的参数，只能在此资源实例化时 手动传入
         */
        //可在资源实例化时，指定当前的 Codex 资源是否属于某个 Compound 复合资源的 内部资源
        /*"belongTo" => null,
        //是否忽略 $_GET 参数
        "ignoreGet" => false,
        //可额外定义此资源的 中间件，这些额外的中间件 将在资源实例化时，附加到预先定义的中间件数组后面
        "middleware" => [
            //create 阶段
            "create" => [],
            //export 阶段
            "export" => [],
        ],
        //import 资源时 被导入资源的实例化参数，将与 dftImportParams 合并
        "importParams" => [],
        //合并资源时的 被合并资源的实例化参数，将与 dftMergeParams 合并
        "mergeParams" => [],*/

        /**
         * 其他参数
         */
        //可指定实际输出的 资源后缀名 可与实际资源的后缀名不一致，例如：scss 文件可指定输出为 css
        "export" => "scss",
        //代码导入参数，true:处理导入(合并本地资源|补齐远程资源地址)，false:删除所有导入语句，'keep':导入语句保持原始形式
        //"import" => true,
        //代码合并参数，可指定要合并输出的 其他本地资源文件，文件名|文件路径，如果不带 ext 则使用 $this->ext
        //"merge" => [],
        
    ];
    
    /**
     * 针对此资源实例的 处理中间件
     * 需要严格按照先后顺序 定义处理中间件
     * !! 覆盖父类
     */
    public $middleware = [
        //资源实例 构建阶段 执行的中间件
        "create" => [
            //使用资源类的 stdParams 标准参数数组 填充 params
            "UpdateParams" => [],
            //获取 资源实例的 meta 数据
            "GetMeta" => [],
            //获取 资源的 content
            "GetContent" => [],
            //内容行处理器
            "RowProcessor" => [
                "stage" => "create"
            ],
            //import 处理 有条件执行
            "ImportProcessor" => [
                "stage" => "create"
            ],
            //条件执行 资源合并
            "MergeProcessor ?!empty(merge)" => [
                "stage" => "create"
            ],
            //去除 scss 代码中的 charset 语句
            "StripScssCharset" => [
                "break" => true
            ],
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],
            
            //条件执行 合并生成 content
            "ImportProcessor ?import!=keep&empty(merge)" => [
                "stage" => "export"
            ],
            "RowProcessor ?import=keep&empty(merge)" => [
                "stage" => "export"
            ],
            "MergeProcessor ?!empty(merge)" => [
                "stage" => "export"
            ],
            //调用 scss 解析工具
            "ParseScssContent ?export=css" => [
                "break" => true
            ],
        ],
    ];


    
    /**
     * 资源实例内部定义的 stage 处理方法
     * @param Array $params 方法额外参数
     * @return Bool 返回 false 则终止 当前阶段 后续的其他中间件执行
     */
    //StripScssCharset 去除 scss 代码中的 charset 语句
    public function stageStripScssCharset($params=[])
    {
        $this->content = static::stripCharset($this->content);
        return true;
    }
    //ParseScss 将 scss content 解析为 css
    public function stageParseScssContent($params=[])
    {
        //scss 文件内容
        $cnt = $this->content;
        $exp = $this->params["export"] ?? "scss";
        //如果指定输出 css
        if ($exp === "css") {
            //解析 scss 默认不压缩，由 Codex 类统一处理代码压缩
            $this->content = static::parseScss($cnt, false);
        }
        
        return true;
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
            throw new SrcException("SCSS 文件编译为 CSS 发生错误：".$e->getMessage(), "resource/export");
        }
        return $cnt;
    }

    /**
     * 去除 @charset "UTF-8";
     * @param String $cnt 文件内容
     * @return String
     */
    public static function stripCharset($cnt)
    {
        if (!Is::nemstr($cnt)) return $cnt;
        $cnt = str_replace(
            [
                "@charset \"UTF-8\";",
                "@charset \"utf-8\";",
            ],
            "",
            $cnt
        );
        return $cnt;
    }
}