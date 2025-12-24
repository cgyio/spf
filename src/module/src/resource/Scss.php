<?php
/**
 * Codex 类型资源类
 * 处理 SCSS 文件资源
 */

namespace Spf\module\src\resource;

use Spf\module\src\SrcException;
use Spf\Request;
use Spf\Response;
use Spf\exception\BaseException;
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

        /**
         * 当 export == scss 时，控制是否输出 ScssPhp 补丁文件内容，用于补丁开发期间调试 补丁文件
         */
        "patch" => false,
        
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

            //!! 20251202 合并 ScssPhp 库补丁文件，生成最终 scss 内容
            "CombineScssPatch ?export=scss&patch=true" => [
                "break" => true
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
    //!! 20251202 CombineScssPatch 合并 ScssPhp 库补丁文件，生成最终 scss 内容
    public function stageCombineScssPatch($params=[]) {
        //scss 文件内容 增加补丁文件内容
        $this->content = static::patchDartScss($this->content);
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
            //$this->content = static::parseScss($cnt, false);
            $this->content = static::parseByDartSass($cnt, false);
        }
        
        return true;
    }



    /**
     * 静态工具
     */

    /**
     * 调用 ScssPhp 库 解析 scss 内容
     * @param String $scss 内容字符串
     * @param Bool $compressed 是否压缩字符串，默认 true
     * @return String 解析得到的 css 字符串
     */
    public static function parseScss($scss="", $compressed=true)
    {
        if (!Is::nemstr($scss)) return "";
        
        //为 ScssPhp 库打补丁
        $scss = static::patchDartScss($scss);
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
            $errmsg = $e->getMessage();
            //合并为单行 msg
            $errmsg = str_replace(["\r\n", "\r", "\n"]," ", $errmsg);
            throw new SrcException("ScssPhp 编译器报错：$errmsg", "resource/export");
        }
        return $cnt;
    }

    /**
     * 调用 dart-sass v1.94.2 命令行工具解析 scss
     * !! 命令行工具位置：spf/module/src/resource/util/dart-sass/sass 文件和路径必须拥有执行权限
     * !! scss|css 临时文件路径 spf/assets/temp/dart-sass 路径必须拥有写权限
     * @param String $scss 内容字符串
     * @param Bool $compressed 是否压缩字符串，默认 true
     * @return String 解析得到的 css 字符串
     */
    public static function parseByDartSass($scss="", $compressed=false)
    {
        if (!Is::nemstr($scss)) return "";
        //为要解析的 scss 代码添加全局 patch
        $scss = static::patchDartScss($scss);

        $dartSass = Path::find("spf/module/src/resource/util/dart-sass/sass", Path::FIND_FILE);
        $tempDir = Path::find("spf/assets/temp/dart-sass", Path::FIND_DIR);
        if (!file_exists($dartSass) || !is_dir($tempDir)) {
            throw new SrcException("Dart-Sass 编译器未找到或无权限", "resource/export");
        }

        //return function_exists("proc_open") ? "ok" : "fail";

        //临时文件名
        $tempName = date("YmdHis", time())."-".Str::nonce(8, false);
        $tempScss = $tempDir. DS . "$tempName.scss";
        $tempCss = $tempDir. DS . "$tempName.css";
        $tempCssMap = $tempDir. DS . "$tempName.css.map";

        //创建临时 scss 文件
        Path::mkfile($tempScss, $scss);
        if (file_exists($tempScss)) {
            //0777
            chmod($tempScss, 0777);
        } else {
            throw new SrcException("Dart-Sass 编译器编译失败，无法创建 scss 临时文件", "resource/export");
        }

        //调用 dart-sass 命令行
        try {
            //处理参数
            //$p1 = escapeshellarg($tempScss);
            
            //拼接命令
            $cmd = [$dartSass, $tempScss, $tempCss, "--no-source-map"];
            //return $cmd;

            //固定管道描述数组
            $descriptorspec = [
                0 => ["pipe", "r"],     //stdin 写管道，php向命令输入数据，无需交互则后续关闭
                1 => ["pipe", "w"],     //stdout 读管道，接受命令的正常输出
                2 => ["pipe", "w"],     //stderr 读管道，接受命令的错误信息
            ];

            //创建进程
            $process = proc_open($cmd, $descriptorspec, $pipes, null, null, [
                "timeout" => 10,        //超时时间 10s
                "bypass_shell" => true, //跳过 shell 解析，更安全
            ]);

            if (!is_resource($process)) {
                throw new SrcException("Dart-Sass 编译器编译失败，创建编译进程失败！", "resource/export");
            }

            //!! 关闭 stdin 无需向命令输入数据时必须关闭，否则进程会阻塞
            fclose($pipes[0]);

            //读取输出和错误信息
            $stdout = stream_get_contents($pipes[1]);   //命令输出
            $stderr = stream_get_contents($pipes[2]);   //错误信息

            //关闭管道和进程，获取退出码 0=成功 1=失败
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnVar = proc_close($process);

            //if ($returnVar === 0) {
                //读取输出的 css 文件
                if (file_exists($tempCss)) {
                    $css = file_get_contents($tempCss);
                    //删除 临时文件
                    unlink($tempScss);
                    unlink($tempCss);
                    if (file_exists($tempCssMap)) unlink($tempCssMap);
                    //返回 css
                    return $css;
                } else {
                    throw new SrcException("Dart-Sass 编译器编译失败，输出：$stdout   错误：$stderr", "resource/export");
                }
            //} else {
            //    throw new SrcException("Dart-Sass 编译器编译失败，输出：$stdout   错误：$stderr", "resource/export");
            //}

            

        } catch (BaseException $e) {
            $e->handleException();
        }
    }

    /**
     * !! 处理要编译的 scss 内容，增加一些全局功能
     * patch 文件位于：vendor/cgyio/spf/src/module/src/resource/util/dart-scss-patch.scss
     * 需要将此文件内容，附加到 scss @import 语句之后
     * @param String $scss 代码内容
     * @return String 添加 patch 文件内容后的 代码
     */
    public static function patchDartScss($scss="")
    {
        //为 scss 代码增加 @use 规则 这些语句必须在最顶部
        $userows = [
            "@use 'sass:string';",
            "@use 'sass:list';",
            "@use 'sass:map';",
            "@use 'sass:color';",
            "@use 'sass:math';",
            "@use 'sass:meta';",
            "@use 'sass:selector';",
            "",
            "",
        ];

        //拆分当前 scss 代码为 rows
        $rows = explode("\n", $scss);
        $improws = [];
        $cntrows = [];
        foreach ($rows as $i => $row) {
            $ri = trim($row);
            if (
                substr($ri, 0, 8) == "@charset" || 
                substr($ri, 0, 7) == "@import"
            ) {
                $improws[] = $row;
            } else {
                $cntrows[] = $row;
            }
        }
        //读取补丁文件
        $ptf = Path::find("spf/module/src/resource/util/dart-scss-patch.scss", Path::FIND_FILE);
        if (file_exists($ptf)) {
            $ptcnt = file_get_contents($ptf);
            $improws[] = "\n\n";
            $improws[] = $ptcnt;
            $improws[] = "\n\n";
        }

        //合并为 scss cnt 等待 编译器解析
        return implode("\n", $userows) . implode("\n", $improws) . implode("\n", $cntrows);
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