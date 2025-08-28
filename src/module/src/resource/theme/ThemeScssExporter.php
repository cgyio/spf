<?php
/**
 * SPF-Theme 主题输出类
 * 输出 SCSS 文件
 */

namespace Spf\module\src\resource\theme;

use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use ScssPhp\ScssPhp\Compiler as scssCompiler;
use ScssPhp\ScssPhp\OutputStyle as scssOutputStyle;

class ThemeScssExporter extends ThemeExporter 
{
    /**
     * 主题资源输出 核心入口方法 创建输出资源的 内容
     * !! 覆盖父类
     * @return String 要输出的 主题资源内容
     */
    public function createContent()
    {
        /**
         * 创建此主题的 SCSS 文件内容
         * 
         *  0   解析 $context 生成 各种 SCSS 变量
         *  1   合并 可能存在的 theme_name_common.scss 文件
         *  2   合并 通过 use=foo,bar 指定的 其他同路径下的 scss 文件
         *  3   返回 合并后的 SCSS 文件内容
         */

        // 0    解析 $context 生成 各种 SCSS 变量的 定义语句
        $this->createScssVars();

        // 1    合并 可能存在的 theme->meta["common"] 定义的 scss 文件
        $this->mergeScss();

        // 2    合并 通过 use=foo,bar 指定的 其他同路径下的 scss 文件
        $uses = $this->theme->params["use"] ?? null;
        if ($uses === "all") {
            $uses = $this->theme->meta("use");
            if (!Is::nemarr($uses)) $uses = [];
            $uses = array_keys($uses);
            $uses = array_filter($uses, function($ui) {
                return $ui !== "common";
            });
        } else {
            if (Is::nemstr($uses)) {
                $uses = Arr::mk($uses);
            }
        }
        //依次引用 use 指定的 SCSS
        if (Is::nemarr($uses)) {
            foreach ($uses as $usei) {
                //查找并合并 其他 scss 文件
                $this->mergeScss($usei);
            }
        }
        
        // 3    返回 合并后的 SCSS 文件内容
        return $this->rowCnt();
    }



    /**
     * 文件内容生成方法
     */

    /**
     * 解析 context 得到 各种 SCSS 变量，生成 变量定义语句 存入 content 缓存
     * @return $this
     */
    protected function createScssVars()
    {
        $ctx = $this->context;

        //SCSS 头部 语句
        $this->rowAdd("/** SPF-Theme 主题 SCSS 变量 **/", "");
        $this->rowAdd("/** !! 不要手动修改 !! **/", "");
        //空行
        $this->rowAddEmpty(2);

        //按主题模块 分别执行
        foreach ($ctx as $modk => $modc) {
            //获取对应的 主题模块实例
            $modins = $this->theme->module($modk);
            if (empty($modins)) continue;
            //模块输出 头部语句
            $this->rowAdd("/** $modk 模块变量定义 **/", "");
            //调用 各 主题模块的 SCSS 变量定义语句 生成方法
            $modins->createScssVarsDefineRows($this, $modc);
            //空行
            $this->rowAddEmpty(1);
        }

        return $this;
    }

    /**
     * 合并 可能存在的 theme_name_common.scss 文件
     * 按 行 拆分 SCSS 文件，缓存到 content
     * @param String $scssn 要合并的 scss 文件名，默认为 theme_name_common.scss
     * @return $this
     */
    protected function mergeScss($scssn=null)
    {
        if (!Is::nemstr($scssn)) $scssn = "common";
        //主题元数据
        $meta = $this->theme->meta();
        //use 列表
        $uses = $meta->use ?? [];
        //根据 scssn 决定要引用的 SCSS 文件
        $scf = $uses[$scssn] ?? null;
        if (!Is::nemstr($scf)) {
            //主题名称
            $thn = $meta->name;
            //可能存在的 theme_name_common.scss 文件路径
            $real = $this->theme->real;
            $dir = dirname($real);
            $scf = $dir.DS.$thn."_common.scss";
        }
        if (!Is::nemstr($scf)) return $this;
        $scf = Path::find($scf, Path::FIND_FILE);
        if (!file_exists($scf)) return $this;
        //读取 common.scss 文件
        $scrows = file_get_contents($scf);
        $scrows = explode("\n", $scrows);

        //合并 scss 文件头
        $this->rowAdd("/** 合并 ".$scssn.".scss 文件 **/");
        //空行
        $this->rowAddEmpty(1);

        //去除 scss 文件中 @import 行
        $scrows = array_filter($scrows, function($ri) {
            return substr($ri, 0, 7) !== "@import";
        });

        //合并到 content
        $this->content = array_merge($this->content, $scrows);
        //空行
        $this->rowAddEmpty(1);

        return $this;
    }
}