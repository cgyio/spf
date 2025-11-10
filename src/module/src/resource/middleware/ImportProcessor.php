<?php
/**
 * Resource 资源处理中间件 Processor 处理器子类
 * RowProcessor 专门处理 Codex 类型代码资源 内容行数组 相关操作
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\Resource;
use Spf\module\src\Mime;
use Spf\module\src\resource\Codex;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

class ImportProcessor extends Processor 
{
    //设置 当前中间件实例 在资源实例中的 属性名，不指定则自动生成 NS\middleware\FooBar  -->  foo_bar
    public static $cacheProperty = "importer";

    /**
     * 可以指定 此处理器依赖的 其他处理器
     * 会在执行操作前检查 依赖的处理器 是否存在
     * !! 覆盖父类
     */
    public static $dependency = [
        "RowProcessor",
    ];

    /**
     * 定义支持的 Codex 类型
     */
    protected static $exts = ["js","scss","css"];

    /**
     * 定义不同文件类型的 import 处理参数
     */
    protected static $opts = [
        //默认使用 js 类型
        "default" => [
            //import 语句开头标记
            "prefix" => "import ",
            //import 语法
            "pattern" => "/import\s+([a-zA-Z0-9_\- {},]+)\s+from\s+['\"]{1}(.+)['\"]{1};?/",
            //import 语句中包含的 参数
            "ptparams" => ["var", "url"],
            //当前类型的代码，允许 import 的文件类型
            "allow" => ["js"],
        ],

        //js 类型 与 default 一致
        "js" => "default",

        //css|scss
        "css" => [
            "prefix" => "@import ",
            "pattern" => "/@import\s+['\"]{1}(.+)['\"]{1};?/",
            "ptparams" => ["url"],
            "allow" => ["css", "scss"],
        ],
        "scss" => "css",
    ];

    //import 本地资源时，本地资源的实例化参数，可在 import 前，通过 setImportParams 传入
    public $importParams = [];

    /**
     * 当前资源类型 import 处理参数
     */
    //import 语句 开头
    public $prefix = "";
    //此类型资源的 import 语法 默认 js
    public $pattern = "";
    //import 语句中包含的 参数信息
    public $ptparams = [];
    //当前类型的代码，允许 import 的文件类型
    public $allow = [];

    //当前资源 import 了本地资源，储存这些本地资源路径
    public $local = [];



    /**
     * 处理器初始化动作
     * !! Processor 子类 必须实现
     * @return $this
     */
    protected function initialize()
    {
        $res = $this->resource;
        
        //资源实例的 ext
        $ext = $res->ext;
        if (static::support($ext) !== true) return null;

        //查找 ext 对应的 处理参数
        $opts = static::$opts;
        $dft = $opts["default"];
        $eopt = $opts[$ext] ?? [];
        if (Is::nemstr($eopt)) $eopt = $opts[$eopt] ?? [];
        $eopt = Arr::extend($dft, $eopt, true);
        //写入处理参数
        $ks = array_keys($dft);
        foreach ($ks as $k) {
            if (isset($eopt[$k])) $this->$k = $eopt[$k];
        }

        //执行一次 setImportParams 将资源类中定义的 $dftImportParams 写入 $this->importParams
        $this->setImportParams();

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
        //创建资源 imports 数组
        $this->getImportsFromRows();
        //处理 import url 生成完整 url 或 真实本地文件路径
        $this->fixImportUrls();
        //获取并缓存不含 import 语句的 内容行数组
        $this->getNoImportRows();

        return true;
    }

    /**
     * export 阶段执行的操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function stageExport()
    {
        //var_dump("stage export");var_dump($this->resource->name);
        //当前资源的 import 参数状态 true|false|keep
        $st = $this->status();
        //var_dump($st);var_dump($this->resource->name);

        //调用 RowProcessor 中间件 生成 content
        $rower = $this->RowProcessor;
        
        //保持 原始 import 语句形式
        if ($st === "keep") {
            //!! 直接调用 RowProcessor 中间件的 stageExport
            return $rower->callStageExport();
        }

        //准备要输出的 rows 行数组
        $rows = [];
        
        if ($st === false) {
            //import = false 删除导入语句 获取不含 import 语句的 rows
            $rows = $this->getNoImportRows();
        } else {
            //import = true 处理导入语句，合并本地文件，合并所有行
            $rows = $this->exportRows(true);
        }

        //var_dump($this->resource->imports);
        //var_dump($rows);var_dump($this->resource->name);
        
        //记录原始 rows
        $rower->clearRows();
        //新的 rows 行数组 不为空时，添加到当前资源的 rows
        if (Is::nemarr($rows)) {
            //添加新的 rows 行数组
            $rower->rowAdd($rows);
        }

        //!! 调用 RowProcessor 中间件的 stageExport
        return $rower->callStageExport();
    }



    /**
     * stage 工具方法
     */

    /**
     * 从内容行数组中 收集 import 语句，写入 $resource->imports 数组
     * @return $this
     */
    protected function getImportsFromRows()
    {
        //import 处理参数
        $prefix = $this->prefix;
        $pattern = $this->pattern;
        $params = $this->ptparams;
        $prelen = strlen($prefix);
        $pslen = count($params);
        //rows
        $rows = $this->RowProcessor->exportRows();
        //收集到的 imports 数组，不处理 url
        $imports = [];
        foreach ($rows as $row) {
            $row = trim($row);
            //简易排除 非 import 语句
            if (substr($row, 0, $prelen) !== $prefix) continue;
            //匹配 pattern
            $mt = preg_match($pattern, $row, $mts);
            if ($mt !== 1) continue;

            //提取匹配到的 import 参数
            $mts = array_slice($mts, 1);
            if (count($mts) < $pslen) continue;

            if ($pslen===1) {
                $imports[] = $mts[0];
            } else if ($pslen===2) {
                $imports[$mts[0]] = $mts[1];
            } else {
                //import 语句包含 参数数量 > 2 时
                $ips = [];
                foreach ($params as $i => $pk) {
                    $ips[$pk] = $mts[$i];
                }
                $imports[] = $ips;
            }

        }

        //写入 resource->imports
        $this->resource->imports = $imports;

        return $this;
    }

    /**
     * 处理 imports 数组中的 url
     * @return $this
     */
    protected function fixImportUrls()
    {
        //如果 当前资源的导入参数 为 keep 表示 保持原始导入语句形式，此处不做处理
        if ($this->status === "keep") return $this;

        //res
        $res = $this->resource;
        //当前类型的代码，允许 import 的文件类型
        $allow = $this->allow;
        //需要调用 PathProcessor
        $pather = $this->PathProcessor;

        //循环处理 imports 数组
        return $this->eachImport(function($i, $ipc, $url, &$res) use ($allow, &$pather) {
            //调用 通用的 import url 处理方法 处理 url
            //url 中包含的 ext
            $ext = pathinfo($url)["extension"];
            if (Is::nemstr($ext) && !in_array($ext, $allow)) {
                //要 import 的文件后缀名 不被支持，跳过
                return true;
            } 
            if (!Is::nemstr($ext)) $ext = $res->ext;
            //调用 PathProcessor 工具方法
            $url = $pather->fixImportUrl($url, $ext);
            //$url = static::fixUrl($url, $ext, $innerdir, $urlpre);

            if (!Is::nemstr($url)) return true;
            return $url;
        });
    }

    /**
     * 在 与其他资源合并过程中 根据合并处理的结果，更新当前 $resource->imports 数组
     * 还可能会修改 $resource->rows 例如：替换 import 变量名
     * 资源合并处理的结果，一般是：
     *  [
     *      # 处理后的 新的 $resource->imports 数组
     *      "imports" => [
     *          # 带 import 变量名 形式
     *          "变量名" => "资源路径 url",
     *          # 如果 在与其他资源合并过程中，此 import 已有其他资源引用过，需要删除这个 import
     *          "变量名" => "__delete__",
     * 
     *          # 不带变量名
     *          "资源路径 url",
     *          "__delete__",
     *          
     *          ...
     *      ],
     * 
     *      # 需要 更新的 变量名 数组
     *      "ivars" => [
     *          "原变量名" => "新变量名",
     *          ...
     *      ],
     * 
     *      # 待扩展 其他功能
     *      ...
     *  ]
     * @param Array $params 更新参数
     * @return $this
     */
    public function updateImportsWhenMerge($params=[])
    {
        //var_dump($this->resource->name);
        //var_dump($params);
        if (!Is::nemarr($params)) return $this;
        //更新参数
        $imports = $params["imports"] ?? [];
        $ivars = $params["ivars"] ?? [];
        $lfs = $params["lfs"] ?? [];

        //先更新 $resource->imports
        $imps = [];
        foreach ($imports as $i => $ipc) {
            //去除 __delete__
            if ($ipc === "__delete__") continue;
            //更新 变量名
            if (isset($ivars[$i])) {
                $imps[$ivars[$i]] = $ipc;
            } else {
                $imps[$i] = $ipc;
            }
        }
        $this->resource->imports = $imps;

        //再更新 $resource->rows 行数组中的 变量名
        $rower = $this->RowProcessor;
        if (Is::nemarr($ivars)) {
            $rn = "__RN__";
            $cnt = implode($rn, $rower->exportRows());
            foreach ($ivars as $ovar => $nvar) {
                $cnt = str_replace($ovar, $nvar, $cnt);
            }
            $nrows = explode($rn, $cnt);
            //使用 RowProcessor 更新 $resource->rows
            $rower->clearRows();
            $rower->rowAdd($nrows);
        }
        
        return $this;
    }



    /**
     * 当前资源 内容行 rows 相关操作
     * 依赖 RowProcessor 中间件
     */

    /**
     * 输出处理后的 包含 import 语句 以及 本地 import 资源的内容行 的 完整的 rows
     * @param Bool $withImportRows 是否输出 import 语句行数组，默认 true
     * @param Array $params 可以传入额外的 输出参数，用于 import 本地资源时的实例化参数
     * @return Array 内容行数组
     */
    public function exportRows($withImportRows=true, $params=[])
    {
        //合并多个 行数组

        //import 语句行数组
        $importRows = $this->getImportRows();
        //var_dump($importRows);var_dump($this->resource->name);var_dump("imp_1111");
        //本地 import 资源的 内容行数组
        $localRows = $this->getLocalImportRows($params);
        //var_dump($localRows);var_dump($this->resource->name);var_dump("imp_2222");
        //除了 import 语句之外的 内容行数组
        $rows = $this->getNoImportRows();
        //var_dump($rows);var_dump($this->resource->name);var_dump("imp_3333");

        //开始合并 调用 RowProcessor 中间件
        $rower = $this->RowProcessor;
        $rower->clearRows();
        if ($withImportRows===true && Is::nemarr($importRows)) {
            $rower->rowAdd($importRows);
        }
        if (Is::nemarr($localRows)) {
            $rower->rowAdd($localRows);
        }
        if (Is::nemarr($rows)) {
            //如果存在外部 import 进来的其他资源内容，则添加当前资源内容的 comment 标记
            if (Is::nemarr($localRows)) {
                $rower->rowComment(
                    $this->resource->name." 原始内容",
                    "!! 不要手动修改 !!"
                );
                $rower->rowEmpty(1);
            }
            $rower->rowAdd($rows);
            $rower->rowEmpty(3);
        }

        //生成的 完整 rows
        $rows = $rower->exportRows();

        //恢复 原 $resource->rows
        $rower->restoreRows();

        return $rows;
    }

    /**
     * 获取 $resource->rows 中除了 import 之外的 行数组
     * @return Array 行数组
     */
    public function getNoImportRows()
    {
        //rows
        $rows = $this->RowProcessor->exportRows();
        
        //import 参数
        $prefix = $this->prefix;
        $prelen = strlen($prefix);

        //依次删除 import 语句
        foreach ($rows as $i => $row) {
            if (substr(trim($row), 0, $prelen) === $prefix) {
                $rows[$i] = "//__import__;";
            }
        }
        $rows = array_filter($rows, function($row) {
            return $row !== "//__import__;";
        });

        return $rows;
    }

    /**
     * 根据 $resource->imports 数组，生成 import 语句行数组
     * @return Array import 语句行数组
     */
    public function getImportRows()
    {
        $rows = [];
        //先处理 import 了远程文件的 情况
        $this->eachImport(function($i, $ipc, $url, &$res) use (&$rows) {
            //如果 url 是本地文件，跳过
            if (file_exists($url)) return true;
            //直接写入 import 语句
            $impst = $this->createImportSentance($i, $ipc);
            if (!Is::nemstr($impst)) return true;
            $rows[] = $impst;
        });
        return $rows;
    }

    /**
     * 从 resource->imports 数组中筛选出 远程 import url 的 参数项目
     * 与 getImportRows 方法相似，此方法返回的是 import 参数，而不是 语句
     * @return Array 部分 resource->imports 数组内容
     */
    public function getImportSettings()
    {
        $imports = [];
        $this->eachImport(function($i, $ipc, $url, &$res) use (&$imports) {
            //如果 url 是本地文件，跳过
            if (file_exists($url)) return true;
            //直接写入 import 参数
            if (is_numeric($i)) {
                $imports[] = $res->imports[$i];
            } else {
                $imports[$i] = $res->imports[$i];
            }
        });
        return $imports;
    }

    /**
     * 根据 $resource->imports 数组，获取其中的本地资源的 内容行数组，合并为单个 行数组
     * @param Array $params 可传入 本地 import 资源的实例化参数
     * @return Array 本地 import 资源的内容行数组
     */
    public function getLocalImportRows($params=[])
    {
        //本地 import 资源的实例化参数
        if (!Is::nemarr($params)) $params = [];
        //写入 importParams
        $this->setImportParams($params);

        //调用 RowProcessor
        $rower = $this->RowProcessor;
        //先缓存当前 $resource->rows 然后清空
        $rower->clearRows();

        //依次读取 $resource->imports 数组中的本地资源路径，创建资源实例
        $this->eachImport(function($i, $ipc, $url, &$res) use ($params, &$rower) {
            //var_dump($i);var_dump($ipc);var_dump($url);
            //如果 url 不是本地文件，跳过
            if (!file_exists($url)) return true;

            $fpi = pathinfo($url);
            $fext = $fpi["extension"];
            $fbn = $fpi["basename"];
            //准备实例化参数
            $fps = $this->getImportParams($fext);
            //创建资源实例
            $ires = Resource::create($url, $fps);
            if (!$ires instanceof Codex) {
                //实例化失败，记录 comment
                $rower->rowComment(
                    "资源 $fbn 无法实例化，未能成功 import",
                    "完整文件路径：$url"
                );
                $rower->rowEmpty(3);
                return true;
            }

            //头部 comment
            $rower->rowComment(
                "import 本地资源 $fbn",
                "!! 不要手动修改 !!"
            );
            $rower->rowEmpty(1);

            //获取本地源的 内容行数组
            $im = "importLocal".Str::camel($fext, true)."File";
            //var_dump($im);
            if (method_exists($this, $im)) {
                //如果存在自定义的 import 方法
                $this->$im($res, $ires);
            } else {
                //不存在自定义 获取方法。直接使用 ImportProcessor 中间件的 stageExport 方法
                $ires->ImportProcessor->callStageExport();
                $irows = $ires->RowProcessor->exportRows();
                //var_dump($irows);var_dump("irows_0000");
                //将 import 的本地资源的 rows 行数组添加到 当前资源 rows 数组中
                $rower->rowAdd($irows);
                $rower->rowEmpty(3);
            }
            //释放资源实例
            unset($ires);
        });

        //获取到的 所有可用的 本地 import 资源的 内容行数组
        $rows = $rower->exportRows();

        //恢复原 $resource->rows
        $rower->restoreRows();

        return $rows;
    }



    /**
     * 处理 $resource->params 以及 importParams 参数相关操作
     */

    /**
     * 获取 $resource->params["import"] 资源导入参数，默认 true
     * @return Bool|String 返回 true|false 或者 'keep'
     */
    public function status()
    {
        $pi = $this->resource->params["import"] ?? true;
        if (is_bool($pi)) return $pi;
        if (!Is::nemstr($pi)) return true;
        return "keep";
    }

    /**
     * 在 import 本地资源时 本地资源的实例化参数
     * @param Array $params 要合并资源的 实例化参数
     * @return $this
     */
    public function setImportParams($params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        //可能存在的 $res->params["importParams"]
        $importParams = $this->resource->params["importParams"] ?? [];
        //合并后 保存到 $this->importParams
        $this->importParams = Arr::extend($importParams, $params);
        return $this;
    }

    /**
     * 获取 import 本地资源时 本地资源的实例化参数
     * @param String $ext 可指定要 import 的本地资源的 ext 默认为 $this->resource->ext
     * @return Array 合并处理后的 待合并资源的实例化参数
     */
    public function getImportParams($ext=null)
    {
        if (!Is::nemstr($ext)) $ext = $this->resource->ext;
        //获取此 ext 对应的资源类的 dftImportParams 默认实例化参数
        $dft = static::getDftImportParams($ext);
        //当前合并操作 通过 setImportParams 方法指定的 实例化参数
        $ips = $this->importParams;
        //合并
        $ips = Arr::extend($dft, $ips);
        //默认 import = false 表示不继续执行 被 import 的本地资源内部的 import 语句
        $ips["import"] = false;
        //默认 merge = "" 表示被 import 的本地资源不合并其他资源
        $ips["merge"] = "";
        //如果默认参数中 包含了 export 则确保其 = $ext
        if (isset($dft["export"])) $ips["export"] = $ext;

        return $ips;
    }



    /**
     * 循环 imports 数组，执行自定义方法
     * @param Closure $func 自定义方法，参数 4 个，分别为：
     *  0   imports 数组中的 key，可能是 int 序号，或 import 变量名
     *  1   imports 数组中的 val，可能是 url | 当前 import 的参数数组
     *  2   根据 imports 数组 val 解析得到的 url
     *  3   对当前资源类实例的 引用
     * 返回值 为 任意值
     *      返回 false 时 break 当前循环
     *      返回 true 时 continue 当前循环
     *      返回 字符串时，将其视为 处理后的 import url 写回 imports 数组
     * @return $this
     */
    public function eachImport($func=null)
    {
        if (!$func instanceof \Closure) return $this;

        //已获取的 imports 数组
        $imports = $this->resource->imports;
        
        //参数
        $params = $this->ptparams;
        $pslen = count($params);

        //依次处理
        foreach ($imports as $i => $ipc) {
            if (!Is::nemstr($ipc) && !Is::nemarr($ipc)) {
                //import 参数错误
                continue;
            }

            //从 ipc 中获取 url
            if ($pslen <= 2) {
                $url = $ipc;
            } else {
                $url = $ipc["url"] ?? null;
            }
            if (!Is::nemstr($url)) continue;

            //执行自定义方法
            $rtn = $func($i, $ipc, $url, $this->resource);

            //处理返回值
            if ($rtn === true) continue;
            if ($rtn === false) break;
            if (Is::nemstr($rtn)) {
                //返回不为空字符串，视为 处理后的 url 将其写回 imports 数组
                if ($pslen <= 2) {
                    $this->resource->imports[$i] = $rtn;
                } else {
                    $ipc["url"] = $rtn;
                    $this->resource->imports[$i] = $ipc;
                }
            }
        }

        return $this;
    }

    /**
     * 根据 import 参数 生成 import 语句
     * @param String|Int $i imports 数组的 某个 key
     * @param String|Array $ipc imports 数组的 某个 val
     * @return String|null
     */
    public function createImportSentance($i, $ipc)
    {
        //params
        $ps = $this->ptparams;
        $pslen = count($ps);
        //传入的参数不正确
        if (
            ($pslen===1 && !Is::nemstr($ipc)) ||
            ($pslen===2 && (!Is::nemstr($i) || !Is::nemstr($ipc))) ||
            ($pslen>2 && !Is::nemarr($ipc))
        ) {
            return null;
        }
        //import 语句 前缀
        $prefix = trim($this->prefix);

        if ($pslen === 1) {
            $url = $ipc;
            return $prefix." '$url';";
        }

        if ($pslen === 2) {
            $var = $i;
            $url = $ipc;
            return $prefix." $var from '$url';";
        }

        $var = $ipc["var"] ?? null;
        $url = $ipc["url"] ?? null;
        if (!Is::nemstr($var) || !Is::nemstr($url)) return null;
        return $prefix." $var from '$url';";
    }



    /**
     * 静态工具
     */

    /**
     * 处理 import 中的 url 使其可以指向正确 完整的目标 url 或  本地文件路径
     * @param String $url 
     * @param String $ext import 文件的 后缀名，如果不指定，则不做 后缀名补齐操作
     * @param String $innerdir 此资源的内部文件 路径前缀，不指定则不检查内部文件
     * @param String $urlpre 可以指定 url 前缀，不指定 则使用 Url::current 
     * @return String 处理后的 完整的 url
     */
    public static function fixUrl($url, $ext=null, $innerdir=null, $urlpre=null)
    {
        if (!Is::nemstr($url)) return $url;
        $url = trim($url);
        
        //当前 url
        $uo = Url::current();

        //补齐 后缀名
        if (Is::nemstr($ext)) {
            //后缀名长度，包含前面的 .
            $extlen = strlen($ext)+1;
            //补齐后缀名
            if (substr($url, $extlen*-1) !== ".$ext") {
                if (strpos($url, ".$ext") === false) {
                    if (strpos($url, "?") !== false) {
                        $ua = explode("?", $url);
                        $url = $ua[0].".$ext?".$ua[1];
                    } else {
                        $url .= ".$ext";
                    }
                }
            }
        }

        //先检查一次 url 是否真是文件路径
        if (file_exists($url)) return $url;

        //如果 url 符合相对路径的形式，则 先检查 是否 import 了本地文件
        if (
            substr($url, 0,4) !== "http" &&
            !in_array(substr($url, 0,2), ["//", "./"]) &&
            substr($url, 0,1) !== "/"
        ) {
            //首先尝试使用 Path::find() 方法
            $fp = Path::find($url, Path::FIND_FILE);
            $url = str_replace(["/","\\"], DS, $url);
            if (strtolower($ext) === "js") {
                //!! 针对 JS 类型文件，不直接 import 本地资源，需要将 本地资源路径，转换为 外部访问 url
                if (!file_exists($fp)) {
                    if (Is::nemstr($innerdir)) {
                        $fp = $innerdir.DS.ltrim($url, DS);
                    }
                }
                if (file_exists($fp)) {
                    //将 本地 js 文件路径，转为可外部访问的 url
                    $lres = Resource::create($fp);
                    $upre = $lres->getLocalResUrlPrefix();
                    if (Is::nemstr($upre)) {
                        $fu = $upre."/".$lres->name;
                        return $fu;
                    }
                }
            } else {
                //针对 CSS|SCSS 可以直接 import 本地资源，将会读取资源内容行，并合并到当前文件中
                if (file_exists($fp)) return $fp;

                //然后尝试在 当前资源内部 查找本地文件
                if (Is::nemstr($innerdir)) {
                    $fp = $innerdir.DS.ltrim($url, DS);
                    if (file_exists($fp)) return $fp;
                }
            }

            //然后尝试在 当前资源内部 查找本地文件
            if (Is::nemstr($innerdir)) {
                $fp = $innerdir.DS.ltrim($url, DS);
                if (file_exists($fp)) return $fp;
            }

            //最后 拼接 urlpre 构建 完整 url
            //prefix
            if (!Is::nemstr($urlpre)) {
                $urlpre = rtrim($uo->dir, "/");
            } else {
                $urlpre = rtrim($urlpre, "/");
            }
            //合并 相对路径 需要处理 ../ 
            $url = Path::fix($urlpre."/".$url);
            return $url;
        }

        //如果 url 符合网址形式
        if (substr($url, 0, 4) === "http" && strpos($url, "://") !== false) {
            //传入完整的 url 直接返回
            return $url;
        } else {
            //传入 以 // 或 / 或 ./ 开头的 url
            if (substr($url, 0, 2) === "//") {
                $url = $uo->protocol.":".$url;
            } else if (substr($url, 0, 2) === "./") {
                $url = $uo->domain.substr($url, 1);
            } else {
                $url = $uo->domain.$url;
            }
            return $url;
        }
        
    }

    /**
     * 是否支持 $ext 类型的资源的 import 语句
     * @param String $ext 资源类型 后缀名
     * @return Bool
     */
    public static function support($ext)
    {
        if (!Is::nemstr($ext)) return false;
        return in_array($ext, static::$exts);
    }

    /**
     * 获取指定 ext 资源类的 dftImportParams import 本地资源类时的 默认实例化参数
     * @param String $ext
     * @return Array 实例化参数
     */
    public static function getDftImportParams($ext)
    {
        if (!Is::nemstr($ext)) return [];
        //ext 类型资源类全称
        $rcls = Resource::resCls($ext);
        //找到的资源类 必须是 Resource 的子类
        if (!Is::nemstr($rcls) || !is_subclass_of($rcls, Resource::class)) return [];
        $dft = $rcls::$dftImportParams;
        if (!Is::nemarr($dft)) return [];
        return $dft;
    }
}