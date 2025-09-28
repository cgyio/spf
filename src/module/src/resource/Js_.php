<?php
/**
 * Plain 类型资源类
 * 处理 JS 文件资源
 */

namespace Spf\module\src\resource;

use Spf\App;
use Spf\module\src\Resource;
use Spf\module\src\resource\util\Rower;
use Spf\module\src\resource\util\Esmer;
use Spf\module\src\resource\util\Importer;
use Spf\module\src\resource\util\Merger;
use Spf\module\src\Mime;
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

class Js extends Plain 
{
    /**
     * 定义 纯文本文件 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类，定义 JS 文件的 params 参数规则
     */
    public static $stdParams = [
        //可指定要合并输出的 其他 文件
        "merge" => [],
        //合并资源时的 资源实例化参数，将与 dftMergeParams 合并
        "mergeps" => [
            //无法通过 url 传递，只能在资源实例化时 传入
        ],
        //是否忽略 import 默认 false
        "noimport" => false,

        //是否以 ESM 形式输出 js 资源
        "esm" => true,
        /**
         * 当 esm===true 时，如果原始 js 代码中不包含 export 语句，将无法 自动生成导出语句，因为不知道要导出哪些内容
         * 因此，必须指定一个 默认导出变量名，此变量名必须在原始 js 代码中定义了
         * 如果不指定，则无法生成 esm 导出语句
         */
        "var" => "",
        
        //其他可选参数
        //...
    ];

    /**
     * 定义 合并资源时 被合并资源的默认实例化参数
     * !! 覆盖父类
     */
    public static $dftMergeParams = [
        //合并 js 文件时，要求必须输出 非 esm 形式
        "esm" => false,
    ];

    /**
     * 定义 import 此类型本地资源时，本地资源的默认实例化参数
     * !! 覆盖父类
     */
    public static $dftImportParams = [
        //!! JS 类型资源不支持直接 import 本地文件，可能出现 变量名冲突 等未知问题
        //默认不再执行 被 import 的本地资源内部的 import 语句
        //"noimport" => true,
    ];

    /**
     * esm 输出相关参数
     */
    //esm 工具实例
    public $esmer = null;



    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //创建内容行工具 Rower 实例
        Rower::create($this);
        $this->rower->process();

        //创建 esm 工具 Esmer 实例
        Esmer::create($this);

        //创建 import 语句处理工具 Importer 实例
        Importer::create($this);
        
        return $this;
    }

    /**
     * 在输出资源内容之前，对资源内容执行处理
     * !! 覆盖父类
     * @param Array $params 可传入额外的 资源处理参数
     * @return Resource $this
     */
    protected function beforeExport($params=[])
    {
        //合并额外参数
        $this->extendParams($params);

        //处理 stdParams 中定义的 某些参数
        //esm
        $this->exportEsmBeforeExport();
        //import
        $this->importBeforeExport();
        
        //!! merge 合并操作必须最后执行
        $this->mergeBeforeExport();

        return $this;
    }



    /**
     * 在 beforeExport 中执行的一些特殊操作
     */

    /**
     * 处理 esm 状态
     * @param Array $params 输出时的 额外参数
     * @return $this
     */
    protected function exportEsmBeforeExport($params=[])
    {
        //根据 esm 参数，处理 js content
        if (isset(static::$stdParams["esm"])) {
            if ($this->esmer instanceof Esmer) {
                //process
                $this->esmer->process();
                //生成 content
                $this->esmer->export();
            }
        }
        return $this;
    }



    /**
     * 处理 esm 相关方法
     */

    /**
     * 从 rows 内容行数组中 匹配 export 相关语句
     * @return $this
     */
    protected function getExportRows()
    {
        $rows = $this->rows;

    }

}