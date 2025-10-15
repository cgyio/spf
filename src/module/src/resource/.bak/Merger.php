<?php
/**
 * Resource 资源管理类 工具类
 * Plain 类型资源的 合并工具，用于合并多个 相同后缀名的 Plain 类型资源
 * 支持 合并 js|css|scss 资源 rows 行数据
 * 
 */

namespace Spf\module\src\resource\util;

use Spf\module\src\Resource;
use Spf\module\src\resource\Util;
use Spf\module\src\resource\Plain;
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

class Merger extends Util 
{
    /**
     * 定义支持合并操作的 Plain 类型
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
     * 构造
     * @param String $ext 指定合并资源类型 默认 js
     * @return void
     */
    public function __construct($ext="js")
    {
        //只能合并 支持的 ext 类型资源
        if (!Is::nemstr($ext) || !in_array($ext, static::$exts)) return null;
        $this->ext =$ext;
        $this->allow = static::$mergable[$ext];
    }

    /**
     * 输出合并后的 最终 完整的 rows 内容行数组
     * @return Array
     */
    public function export()
    {
        //import 语句行数组
        $importRows = $this->importRows;
        //去除了 import 语句的 行数组
        $rows = $this->rows;

        return array_merge($importRows, ["","",""], $rows);
    }

    /**
     * 在执行 合并操作前，指定要合并的资源的 实例化参数，可以这样操作：（返回完整的内容行数组）
     *      $this->setMergeParams([...])->add($foo,$bar,...)->export() 
     *      或者：$this->setMergeParams([...])->addResource($foo)->addResource($bar)...->export()
     * @param Array $params 要合并资源的 实例化参数
     * @return $this
     */
    public function setMergeParams($params=[])
    {
        $this->mergeParams = $params;
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
        if (isset($mps["merge"])) $mps["merge"] = [];
        //如果 默认参数中包含了 export 参数，则确保其 = $ext
        if (isset($dft["export"])) $mps["export"] = $ext;

        return $mps;
    }

    /**
     * 添加要合并的 resource
     * 此资源必须已创建 Rower Importer 工具实例
     * @param Array $resources 资源实例 或 Plain 类型的 本地文件路径
     * @return $this
     */
    public function add(...$resources)
    {
        if (!Is::nemarr($resources)) return $this;
        //依次 合并
        foreach ($resources as $i => $res) {
            //执行 合并 将 修改 $this->imports 和 $this->rows
            $this->addResource($res);
        }

        return $this;
    }

    /**
     * 执行具体的 添加 resource 到 resources 数组的操作
     * !! 此操作会修改 $this->imports 和 $this->rows $this->importRows
     * @param Plain $res 资源实例 或 本地资源路径
     * @return $this
     */
    public function addResource($res)
    {
        //根据参数 获取资源实例
        $res = $this->getResourceIns($res);

        //如果 此资源不支持 import 语句 则 直接合并资源的 内容行数组
        if (empty($res->importer)) {
            //更新 $this->rows
            $this->rows = array_merge($this->rows, $res->rows);
            return $this;
        }

        //要合并成的 文件 ext
        $ext = $this->ext;

        //合并 $res 资源的 rows 到当前 $this->rows $this->importRows

        //生成合并方法名
        $m = "add".Str::camel($ext, true)."Resource";
        //如果此方法不存在，报错
        if (!method_exists($this, $m)) {
            //不支持合并
            throw new SrcException("不支持合并 $ext 格式的文件资源", "resource/getcontent");
        }
        //执行合并方法，将修改 $this->imports 以及待合并资源的 $res->imports $res->rows
        $this->$m($res);

        //将待合并资源的 import 语句行数组 合并到 $this->importRows
        $importRows = $res->importer->getImportRows();
        if (Is::nemarr($importRows)) $this->importRows = array_merge($this->importRows, $importRows);

        //生成待合并资源的 rows 不包含 import 语句
        $rows = $res->importer->export(false);

        //开始重新生成 待合并的 $res 资源的 去除了 import 语句的 rows

        //先缓存 $res->rows 然后清空
        $res->rower->clearRows();
        //头部 comment
        $res->rower->rowComment(
            "合并资源 ".$res->name,
            "!! 不要手动修改 !!"
        );
        $res->rower->rowEmpty(1);
        //合并 res 的去除了 import 的内容行
        $res->rower->rowAdd($rows);
        $res->rower->rowEmpty(3);
        
        //更新 $this->rows
        $this->rows = array_merge($this->rows, $res->rows);
        //恢复 $res->rows 为最近一次的 history 历史记录
        $res->rower->restoreRows();
        
        return $this;
    }



    /**
     * 工具方法
     */
    
    /**
     * 根据 传入的参数 创建 资源实例
     * @param String|Plain $res 可以是 本地文件路径  或  Path::find 可解析的路径  或  资源实例本身
     * @return Plain|null
     */
    protected function getResourceIns($res)
    {
        //当前要合并的 ext
        //$ext = $this->ext;
        //允许合并的 ext 类型
        $allow = $this->allow;
        //待合并资源的 实例化参数
        $mps = $this->mergeParams;

        if (Is::nemstr($res)) {
            //传入了 资源路径
            if (file_exists($res)) {
                $fp = $res;
            } else {
                $fp = Path::find($res, Path::FIND_FILE);
            }
            if (!file_exists($fp)) {
                //传入的 文件路径不存在 报错
                throw new SrcException("文件 $res 不存在，无法合并", "resource/getcontent");
            }
            //传入资源的 ext
            $fext = pathinfo($fp)["extension"];
            //生成 待合并资源的 实例化参数
            $mps = $this->getMergeParams($fext);
            $res = Resource::create($fp, $mps);
        } else if ($res instanceof Plain) {
            //传入了 某个 Plain 资源实例，则使用当前的 实例化参数 重新 clone 一次
            $mps = $this->getMergeParams($res->ext);
            $res = $res->clone($mps);
        } else {
            //传入其他形式参数
            throw new SrcException("无法创建待合并文件".(isset($fp) ? " ".$fp." " : "")."的资源实例", "resource/getcontent");
        }
        
        if (!in_array($res->ext, $allow)) {
            //只能合并指定类型的 资源
            throw new SrcException("只允许合并 ".implode("|", $allow)." 类型的纯文本文件", "resource/getcontent");
        }

        //判断此资源是否已经被合并过
        foreach ($this->resources as $resi) {
            if ($resi->real === $res->real) {
                //如果 资源的 real 路径已存在
                throw new SrcException($res->real." 资源文件已被合并过，不要重复合并", "resource/getcontent");
            }
        }
        
        //被合并的资源，不再合并其他资源
        if (isset($res->params["merge"])) $res->params["merge"] = [];

        //合并之前 $res 资源实例需要执行一次 export 方法，以生成 合适的 rows
        $res->export([
            "return" => true
        ]);

        //返回资源实例
        return $res;
    }

    /**
     * 针对不同类型的 资源 执行不同的 合并规则
     * !! 这些操作会修改 $this->imports 以及待添加资源的 $res->imports $res->rows 参数
     * @param Plain $res 资源实例
     * @return $this
     */
    //合并 JS 文件资源
    protected function addJsResource(&$res)
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
                $curImports[$val] = $url;
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

        //更新 $res->imports $res->rows
        $res->importer->updateImportsWhenMerge([
            "imports" => $imports,
            "ivars" => $ivars,
        ]);
        
        return $this;
    }
    //合并 SCSS 文件资源
    protected function addScssResource(&$res)
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

        //更新 $res->imports $res->rows
        $res->importer->updateImportsWhenMerge([
            "imports" => $imports,
            "ivars" => $ivars,
        ]);
        
        return $this;
    }
    //合并 CSS 文件资源
    protected function addCssResource(&$res)
    {
        //直接使用 addScssResource 方法
        return $this->addScssResource($res);
    }



    /**
     * 静态方法
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