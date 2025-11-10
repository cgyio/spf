<?php
/**
 * Resource 资源处理中间件 Processor 处理器子类
 * MergeProcessor 专门处理 Codex 类型代码资源 资源合并 相关操作
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\Resource;
use Spf\module\src\Mime;
use Spf\module\src\SrcException;
use Spf\module\src\resource\Codex;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

class MergeProcessor extends Processor 
{
    //设置 当前中间件实例 在资源实例中的 属性名，不指定则自动生成 NS\middleware\FooBar  -->  foo_bar
    public static $cacheProperty = "merger";

    /**
     * 可以指定 此处理器依赖的 其他处理器
     * 会在执行操作前检查 依赖的处理器 是否存在
     * !! 覆盖父类
     */
    public static $dependency = [
        "RowProcessor", //"ImportProcessor",
    ];

    /**
     * 定义支持合并操作的 Codex 类型
     */
    protected static $exts = ["js", "scss", "css"];

    /**
     * 指定 哪些 ext 类型的资源 可以合并
     */
    protected static $mergable = [
        "js"    => ["js"],
        "scss"  => ["scss", "css"],
        "css"   => ["css"],
    ];

    //当前要合并输出的 ext
    public $ext = "";
    //允许合并的 资源 ext 类型
    public $allow = [];

    //在合并时，要合并资源的实例化参数，可在合并前，通过 setMergeParams 传入
    public $mergeParams = [];

    //要合并的 resource 资源实例数组
    public $resources = [];

    //已合并的 rows 不含 import 语句，已处理过 import 冲突问题
    public $rows = [];
    //已合并的 imports
    public $imports = [];
    //已处理后的 import 语句行数组
    public $importRows = [];



    /**
     * 处理器初始化动作
     * !! Processor 子类 必须实现
     * @return $this
     */
    protected function initialize()
    {
        $res = $this->resource;

        //var_dump($res);
        
        //资源实例的 原始 ext
        $ext = $res->getOriginExt();    //$res->ext;
        //只能合并 支持的 ext 类型资源
        if (!Is::nemstr($ext) || !in_array($ext, static::$exts)) return null;
        $this->ext = $ext;
        $this->allow = static::$mergable[$ext];

        //处理 $res->params["merge"]
        $mgs = $res->params["merge"] ?? [];
        if (Is::nemstr($mgs) || $mgs==="") $mgs = $mgs==="" ? [] : explode(",", $mgs);  //Arr::mk($mgs);
        $res->params["merge"] = $mgs;

        //执行一次 setMergeParams 将资源类中定义的 $dftMergeParams 写入 $this->mergeParams
        $this->setMergeParams();

        return $this;
    }

    /**
     * create 阶段执行的操作
     * !! 覆盖父类
     * !! 需要 RowProcessor 已执行 stageCreate 方法
     * @return Bool
     */
    protected function stageCreate()
    {
        //未指定 要合并的资源列表，跳过此阶段处理
        $mgs = $this->status();
        if ($mgs === false || !Is::nemarr($mgs)) return true;

        //先 合并当前资源实例 自身
        $this->mergeResource();
            
        //依次合并 $mgs 中定义的 待合并资源，生成 不含 import 语句的 rows，以及 importRows 
        foreach ($mgs as $fi) {
            //依次执行 合并操作
            if (!Is::nemstr($fi) && !$fi instanceof Codex) continue;
            $this->mergeResource($fi);
        }

        return true;
    }

    /**
     * export 阶段执行的操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function stageExport()
    {
        //未指定 要合并的资源列表，跳过此阶段处理
        if (false === $this->status()) return true;

        //合并后的 完整 rows 带 import 语句
        $rows = $this->exportRows();

        //调用当前资源的 RowProcessor 
        $rower = $this->RowProcessor;
        $rower->clearRows();
        $rower->rowAdd($rows);

        //!! 调用 RowProcessor 的 stageExport 方法
        return $rower->callStageExport();

    }



    /**
     * stage 工具方法
     */

    /**
     * 执行具体的 资源合并 操作
     * !! 此操作会修改 $this->imports 和 $this->rows $this->importRows
     * @param Codex $res 资源实例 或 本地资源路径 默认 null 合并当前资源 $this->resource
     * @return $this
     */
    public function mergeResource($res=null)
    {
        if (is_null($res)) {
            //默认 合并自身
            $res = $this->resource;
            if ($res->hasProcessor("import") !== true || empty($res->imports)) {
                //如果当前资源不包含 import 语句  或  未启用 import 处理器
                $this->rows = array_merge($this->rows, $res->RowProcessor->exportRows());
            } else {
                $importer = $res->ImportProcessor;
                $importer->callStageExport();
                $this->rows = array_merge([], $importer->getNoImportRows());
                $this->imports = array_merge([], $importer->getImportSettings());
                $this->importRows = array_merge([], $importer->getImportRows());
            }

            return $this;
        }

        //根据参数 获取资源实例
        $res = $this->getMergeResource($res);

        //如果 此资源不支持 import 语句 或者 不包含 import 语句 则 直接合并资源的 内容行数组
        if ($res->hasProcessor("import") !== true || empty($res->imports)) {
            //在 此待合并 资源 rows 行数组之前，增加 comment
            $rower = $res->RowProcessor;
            $rows = $rower->exportRows();
            $rower->clearRows();
            //头部 comment
            $rower->rowComment(
                "合并资源 ".$res->name,
                "!! 不要手动修改 !!"
            );
            $rower->rowEmpty(1);
            //合并 res 的去除了 import 的内容行
            $rower->rowAdd($rows);
            $rower->rowEmpty(3);
            //更新 $this->rows
            $this->rows = array_merge($this->rows, $rower->exportRows());
            //恢复
            $rower->restoreRows();
            return $this;
        }

        //要合并成的 文件 ext
        $ext = $this->ext;

        //合并 $res 资源的 rows 到当前 $this->rows $this->importRows

        //生成合并方法名
        $m = "merge".Str::camel($ext, true)."Resource";
        //如果此方法不存在，报错
        if (!method_exists($this, $m)) {
            //不支持合并
            throw new SrcException("不支持合并 $ext 格式的文件资源", "resource/getcontent");
        }
        //执行合并方法，将修改 $this->imports 以及待合并资源的 $res->imports $res->rows
        $this->$m($res);

        //调用 ImportProcessor
        $importer = $res->ImportProcessor;

        //将待合并资源的 import 语句行数组 合并到 $this->importRows
        $importRows = $importer->getImportRows();
        if (Is::nemarr($importRows)) $this->importRows = array_merge($this->importRows, $importRows);

        //生成待合并资源的 rows 不包含 import 语句
        $rows = $importer->exportRows(false);

        //开始重新生成 待合并的 $res 资源的 去除了 import 语句的 rows

        //调用 待合并资源实例 的 RowProcessor
        $rower = $res->RowProcessor;

        //先缓存 $res->rows 然后清空
        $rower->clearRows();
        //头部 comment
        $rower->rowComment(
            "合并资源 ".$res->name,
            "!! 不要手动修改 !!"
        );
        $rower->rowEmpty(1);
        //合并 res 的去除了 import 的内容行
        $rower->rowAdd($rows);
        $rower->rowEmpty(3);
        
        //更新 $this->rows
        $this->rows = array_merge($this->rows, $rower->exportRows());
        //恢复 $res->rows 为最近一次的 history 历史记录
        $rower->restoreRows();
        
        return $this;
    }

    /**
     * 输出处理后的 包含 import 语句 以及 所有合并资源 rows 的 完整的 rows
     * @return Array
     */
    public function exportRows()
    {
        //import 语句行数组
        $importRows = $this->importRows;
        //去除了 import 语句的 行数组
        $rows = $this->rows;

        return array_merge($importRows, ["","",""], $rows);
    }

    

    /**
     * 针对不同类型的 资源 执行不同的 合并规则
     * !! 这些操作会修改 $this->imports 以及待添加资源的 $res->imports $res->rows 参数
     * !! 这些操作 依赖 $res->ImportProcessor  $res->RowProcessor
     * @param Codex $res 资源实例
     * @return $this
     */
    //合并 JS 文件资源
    protected function mergeJsResource(&$res)
    {
        //当前已有的 imports 数组
        $curImports = $this->imports;
        
        //待合并资源的 imports
        $imports = $res->imports;
        //待合并资源是否需要处理的 import 变量名冲突
        $ivars = [
            /*
            "原变量名" => "新变量名",
            ...
            */
        ];

        //合并 imports
        foreach ($imports as $i => $ipc) {
            //import 变量名
            $var = $i;
            //import 资源的 url 或 本地文件路径
            $url = $ipc;
            
            //检查是否存在 import 冲突
            if (!isset($curImports[$var])) {
                //不存在同变量名 import 正常合并
                $curImports[$var] = $url;
                continue;
            }

            //存在 同变量名的 import
            if ($curImports[$var] === $url) {
                //import 的资源路径也相同，表示重复 import 直接跳过
                //!! 同时将 $res->imports[$var] 标记为 __delete__
                $imports[$var] = "__delete__";
                continue;
            }
            
            //存在同变量名 import 且指向不同的资源路径

            //反查 url 是否已被其他资源 import 过
            if (in_array($url, array_values($curImports))) {
                //url 已被其他资源 import 过，查找其变量名
                $ovar = array_search($url, $curImports);
                if (!isset($imports[$ovar])) {
                    //此资源 被其他资源 import 时的变量名 当前资源中并未使用，直接替换为 oval
                    $ivars[$var] = $ovar;
                    continue;
                }
            }

            //url 未被其他 js 代码 import 过，或者 其他 js 代码的 import 变量名，已被此 js 代码指向了 其他 url
            //需要 变更 变量名
            $nvar = $var."__".Str::nonce(8, false);
            while (in_array($nvar, array_values($ivars)) || isset($curImports[$nvar]) || isset($imports[$nvar])) {
                //生成的 新变量名 必须唯一
                $nvar = $var."__".Str::nonce(8, false);
            }
            //记录需要变更的 变量名信息
            $ivars[$var] = $nvar;
            //添加到 curImports 数组
            $curImports[$navr] = $url;
        }
        //确认写回 $this->imports
        $this->imports = $curImports;
        //var_dump($curImports);var_dump(1111);

        //更新 $res->imports $res->rows 依赖 $res->ImportProcessor
        $res->ImportProcessor->updateImportsWhenMerge([
            "imports" => $imports,
            "ivars" => $ivars,
        ]);
        
        return $this;
    }
    //合并 SCSS 文件资源
    protected function mergeScssResource(&$res)
    {
        //当前已有的 imports 数组
        $curImports = $this->imports;

        //待合并资源的 imports
        $imports = $res->imports;
        //scss 类型文件使用 无 变量名的 import 形式，因此不需要处理 变量名冲突
        $ivars = [];

        //合并 imports
        foreach ($imports as $i => $ipc) {
            //scss import 没有变量名
            //$var = $i;
            //import 资源的 url 或 本地文件路径
            $url = $ipc;

            //检查 url 是否已被其他资源 import 过
            if (in_array($url, array_values($curImports))) {
                //url 已被其他资源 import 过，直接跳过，不需要重复 import
                //将 imports 中此项标记为 __delete__
                $imports[$i] = "__delete__";
                continue;
            }

            //url 未被其他资源 import 过，正常添加
            //添加到 curImports 数组
            $curImports[] = $url;
        }
        //确认写回 $this->imports
        $this->imports = $curImports;

        //更新 $res->imports $res->rows 依赖 $res->ImportProcessor
        $res->ImportProcessor->updateImportsWhenMerge([
            "imports" => $imports,
            "ivars" => $ivars,
        ]);
        
        return $this;
    }
    //合并 CSS 文件资源
    protected function mergeCssResource(&$res)
    {
        //直接使用 mergeScssResource 方法
        return $this->mergeScssResource($res);
    }



    /**
     * 处理 $resource->params 以及 mergeParams 参数相关操作
     */

    /**
     * 获取 $resource->params["merge"] 资源合并参数，默认 false
     * @return Bool|Array 返回 false 或者 要合并文件的 数组
     */
    public function status()
    {
        $mgs = $this->resource->params["merge"] ?? [];
        //为指定要合并的 文件列表，返回 false
        if (!Is::nemarr($mgs)) return false;
        return $mgs;
    }

    /**
     * 在执行 合并操作前，指定要合并的资源的 实例化参数
     * @param Array $params 要合并资源的 实例化参数
     * @return $this
     */
    public function setMergeParams($params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        //可能存在的 $res->params["mergeParams"]
        $mergeParams = $this->resource->params["mergeParams"] ?? [];
        //合并后 保存到 $this->mergeParams
        $this->mergeParams = Arr::extend($mergeParams, $params);
        return $this;
    }

    /**
     * 获取 合并资源时的 实例化参数
     * @param String $ext 可指定要合并资源的 ext，因为 $this->ext 可能对应了多种 可合并的 资源类型，默认 $this->ext
     * @return Array 合并处理后的 待合并资源的实例化参数
     */
    public function getMergeParams($ext=null)
    {
        if (!Is::nemstr($ext)) $ext = $this->ext;
        //获取此 ext 对应的资源类的 dftMergeParams 默认实例化参数
        $dft = static::getDftMergeParams($ext);
        //当前合并操作 通过 setMergeParams 方法指定的 实例化参数
        $mps = $this->mergeParams;
        //合并
        $mps = Arr::extend($dft, $mps);
        //被合并的资源，不再合并其他资源
        $mps["merge"] = "";
        //被合并的资源，需要处理其内部的 import 语句
        $mps["import"] = true;
        //如果 默认参数中包含了 export 参数，则确保其 = $ext
        if (isset($dft["export"])) $mps["export"] = $ext;

        return $mps;
    }



    /**
     * 工具方法
     */
    
    /**
     * 根据 传入的参数 创建 资源实例
     * @param String|Codex $res 可以是 文件名 | 本地文件路径 | Path::find 可解析的路径 | 资源实例本身
     * @return Codex|null
     */
    protected function getMergeResource($res)
    {
        //$this->resource
        //$cres = $this->resource;
        //当前资源的 原始 ext
        //$cext = $this->ext;
        //当前资源类型 允许合并的 资源类型数组
        $allow = $this->allow;
        //var_dump($cext);
        //var_dump($allow);

        if (Is::nemstr($res)) {
            //传入了 文件名 | 文件路径，使用 PathProcessor 处理路径
            $pather = $this->PathProcessor;
            //var_dump($res);
            if (strpos($res,".")===false) {
                foreach ($allow as $aext) {
                    $fp = $pather->fixMergeFilePath($res, $aext);
                    if (file_exists($fp)) break;
                }
            } else {
                $fp = $pather->fixMergeFilePath($res);
            }
            if (!file_exists($fp)) {
                //传入的 文件路径不存在 报错
                throw new SrcException("文件 $res 不存在，无法合并", "resource/getcontent");
            }
            //传入资源的 ext
            $fext = pathinfo($fp)["extension"];
            //生成 待合并资源的 实例化参数
            $mps = $this->getMergeParams($fext);
            //var_dump($mps);
            $res = Resource::create($fp, $mps);
        } else if ($res instanceof Codex) {
            //传入了 某个 Codex 资源实例，则使用当前的 实例化参数 重新 clone 一次
            $mps = $this->getMergeParams($res->ext);
            $res = $res->clone($mps);
        } else {
            //传入其他形式参数
            throw new SrcException("无法创建待合并文件".(isset($fp) ? " ".$fp." " : "")."的资源实例", "resource/getcontent");
        }
        
        //允许合并的 ext 类型
        //var_dump($res->real);
        //var_dump($res->ext);
        //var_dump(in_array($res->ext, $allow));
        if (!in_array($res->ext, $allow)) {
            //只能合并指定类型的 资源
            throw new SrcException("只允许合并 ".implode("|", $allow)." 类型的纯文本文件", "resource/getcontent");
        }

        //判断此资源是否已经被合并过
        if ($this->existsInResources($res)) {
            //重复合并资源，报错
            throw new SrcException($res->real." 资源文件已被合并过，不要重复合并", "resource/getcontent");
        } else {
            //缓存这个 待合并资源实例
            $this->resources[] = $res;
        }

        //返回资源实例
        return $res;
    }

    /**
     * 判断传入的 待合并资源实例 是否已存在于 $this->resources 数组中
     * @param Codex $res 待合并的 资源实例 可能是 本地文件  或  手动创建的 Codex 资源实例
     * @return Bool
     */
    public function existsInResources($res)
    {
        if (!$res instanceof Codex) return false;
        $reses = $this->resources;
        
        //针对实际存在的 本地文件资源
        if ($res->sourceType !== "create") {
            foreach ($reses as $resi) {
                //如果 本地文件的 实际路径相同，表示这是同一个资源实例
                if ($resi->real === $res->real) return true;
            }
            return false;
        }

        //针对 手动创建的 资源实例
        foreach ($reses as $resi) {
            //手动创建的 资源，比较它们的 content
            if ($resi->content === $res->content) return true;
        }
        return false;
    }



    /**
     * 静态工具
     */

    /**
     * 获取指定 ext 资源类的 dftMergeParams 合并资源类时的 默认实例化参数
     * @param String $ext
     * @return Array 实例化参数
     */
    public static function getDftMergeParams($ext)
    {
        if (!Is::nemstr($ext)) return [];
        //ext 类型资源类全称
        $rcls = Resource::resCls($ext);
        //找到的资源类 必须是 Resource 的子类
        if (!Is::nemstr($rcls) || !is_subclass_of($rcls, Resource::class)) return [];
        $dft = $rcls::$dftMergeParams;
        if (!Is::nemarr($dft)) return [];
        return $dft;
    }

}