<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Plain 子类
 * 处理 Plain 纯文本类型 资源 基类
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
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

use MatthiasMullie\Minify;  //JS/CSS文件压缩

class Plain extends Resource 
{
    /**
     * 定义 纯文本文件 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 子类应覆盖此属性，定义自己的 params 参数规则
     */
    protected static $stdParams = [
        //是否 强制不使用 缓存的 数据
        "create" => false,
        //输出文件的 类型
        "export" => "",
        //可指定要合并输出的 其他 文件
        "use" => [],
        //是否忽略 @import 默认 false
        "noimport" => false,
        
        //其他可选参数
        //...
    ];

    /**
     * 定义支持的 export 类型，必须定义相关的 createFooContent() 方法
     * 必须是 Mime 支持的 文件后缀名
     * !! Plain 子类应覆盖此属性
     */
    protected static $exps = [
        "js", "css",
    ];

    //纯文本文件 将 content 按行拆分为 indexed []
    public $rows = [];
    //换行符 php 默认为 \n
    public $rn = "\n";
    
    /**
     * 此类型纯文本资源的 注释符 [ 开始符号, 每行注释的开头符号, 结尾符号 ]
     * !! 子类必须覆盖此属性
     */
    public $cm = ["/**", " * ", " */"];

    /**
     * 此类型的纯文本资源，如果可用 import 语句，则 指定 import 语法
     * 默认 为 null，表示不处理 或 不适用 import 语法
     * !! js 文件的 import 语句不处理，直接输出
     * !! 各子类可以定义各自的 import 语句语法 正则
     */
    public $importPattern = null;  // js "/import\s+(.+)\s+from\s+['\"](.+)['\"];?/";



    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类，如果需要，Plain 子类可以覆盖此方法
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //标准化 params
        $this->formatParams();

        //将 content 按行拆分为 rows 行数组
        $this->contentToRows();

        //处理 import 语句
        $this->fixImport();
        
        return $this;
    }

    /**
     * 在输出资源内容之前，对资源内容执行处理
     * !! 覆盖父类，如果需要，Plain 子类可以覆盖此方法
     * @param Array $params 可传入额外的 资源处理参数
     * @return Resource $this
     */
    protected function beforeExport($params=[])
    {
        //合并额外参数
        $this->extendParams($params);
        $this->formatParams();

        //根据 use 参数，合并指定的 文件
        if (isset(static::$stdParams["use"])) {
            $uses = $this->params["use"] ?? [];
            if ($uses === "") $uses = [];
            if (Is::nemstr($uses)) $uses = Arr::mk($uses);
            $this->useFile(...$uses);
        }

        //根据 export 类型 调用对应的 createExtContent 方法
        if (isset(static::$stdParams["export"])) {
            $ext = $this->params["export"] ?? $this->ext;
            $m = "create".Str::camel($ext, true)."Content";
            if (!method_exists($this, $m)) {
                //对应 方法不存在，则 不做处理，表示直接使用 当前 content
                //通常针对于 输出此文件实例的 实际文件类型
    
            } else {
                //调用方法
                $this->$m();
            }
        }

        //minify
        if ($this->isMin() === true) {
            //压缩 JS/CSS 文本
            $this->content = $this->minify();
        }

        return $this;
    }



    /**
     * 使用 stdParams 标准参数 格式化 params 参数
     * !! 子类可以覆盖此方法
     * @return $this
     */
    protected function formatParams()
    {
        $ps = $this->params;
        if (!Is::nemarr($ps)) $ps = [];
        $ps = Arr::extend(static::$stdParams, $ps);

        //export
        if (isset(static::$stdParams["export"])) {
            if (!isset($ps["export"]) || !Is::nemstr($ps["export"])) {
                $ps["export"] = $this->ext;
            } else {
                $ext = $ps["export"];
                if (!(Mime::support($ext) && in_array($ext, static::$exps))) {
                    //指定要输出的 export 类型 不正确，报错
                    throw new SrcException("要输出的 $ext 类型不是有效的文件类型", "resource/getcontent");
                }
            }
        }

        //根据要输出的 文件类型，修改 ext|mime 
        if (isset(static::$stdParams["export"])) {
            $ext = $ps["export"];
            if ($ext !== $this->ext) {
                $this->ext = $ext;
                $this->mime = Mime::getMime($ext);
            }
        }

        //写回
        $this->params = $ps;
        return $this;
    }

    /**
     * 如果此类型文本资源 支持 import 语句，则在生成 rows 后，需要处理 import 语句
     * 将 import 语句指向的资源行数据，读取到 语句所在 rows 中的位置
     * @param Array $params 在引用资源实例时，需要的 额外参数，例如：可以指定 继续 import 引用资源中指定的的其他 需要 import 文件
     * @return $this
     */
    protected function fixImport($params=[])
    {
        //如果未指定 import 语法，则直接返回，不做处理
        if (!Is::nemstr($this->importPattern)) return $this;
        $pattern = $this->importPattern;

        if (!Is::nemarr($params)) $params = [];

        //注释 标记
        $cm = $this->cm;

        //指定 忽略 import 
        $noimport = $this->params["noimport"] === true;
        //缓存 要 import 的 rows 
        $iprows = [
            //"__import_idx__" => [ ... rows ... ], 
            //...
        ];
        //解析 rows 中的 import 语句
        foreach ($this->rows as $i => $row) {
            if (!Is::nemstr($row)) continue;
            //开始匹配 语句
            $mts = [];
            $mt = preg_match($pattern, $row, $mts);
            //未匹配 跳过
            if ($mt !== 1) continue;

            /**
             * 匹配后 处理
             */
            //指定忽略 import 语句，则 从 rows 中删除此语句
            if ($noimport) {
                $rows[$i] = "";
                continue;
            }

            //缓存此资源的 rows
            $k = "__import_".$i."__";

            //这是 import 语句，返回匹配到的 项目，通常是 指向的资源 本地路径 | url
            $ipf = array_slice($mts, 1)[0];
            if (!Is::nemstr($ipf)) {
                //如果指向资源 不是有效的资源路径
                $this->rows[$i] = $k;
                $iprows[$k] = [
                    $cm[0]." $ipf 不是一个有效的资源路径".$cm[2],
                ];
                continue;
            }
            
            //读取此资源的 内容行数据
            $rows = $this->getFileRows($ipf, $params);
            if (!Is::nemarr($rows)) {
                //未能读取到 要引用的资源的 内容行数据
                $this->rows[$i] = $k;
                $iprows[$k] = [
                    $cm[0]." 无法读取 $ipf 资源内容".$cm[2],
                ];
                continue;
            }

            $iprows[$k] = $rows;
            //将 import 语句替换为 $k
            $this->rows[$i] = $k;
        }

        //循环替换
        foreach ($this->rows as $i => $row) {
            if (!Is::nemstr($row)) continue;
            if (substr($row, 0, 9) !== "__import_" || !isset($iprows[$row])) continue;
            $rows = $iprows[$row];
            if (!Is::nemarr($rows)) continue;
            //插入 rows
            $idx = array_search($row, $this->rows);
            array_splice($this->rows, $idx, 1, $rows);
        }

        //同步 rows 与 content，不清空 rows
        $this->content = $this->rowCnt(false);

        return $this;
    }

    /**
     * 合并 use 指定的 纯文本文件
     * 这些文件应在 当前文件的同路径下的 当前文件同名文件夹下，例如：
     * 当前文件：       foo/bar/jaz.js 
     * 请求 use=a,b    则需要查找：foo/bar/jaz/a.js|b.js
     * 将所有获取到的文件内容，转为 rows 行数据，合并到当前资源的 rows 中
     * !! 子类可以覆盖此方法
     * @param Array $uses 要查询的 use 文件名|文件路径
     * @return $this
     */
    protected function useFile(...$uses)
    {
        if (!Is::nemarr($uses)) return $this;

        //当前 ext
        $ext = $this->ext;
        //当前文件名
        $fn = $this->name;
        //当前文件所在路径 
        $dir = dirname($this->real);

        /**
         * 第一个参数 可以是 所有待合并资源实例的 params 实例化参数
         */
        $ps = [];
        if (Is::nemarr($uses[0])) {
            $ps = Arr::extend($ps, $uses[0]);
            $uses = array_slice($uses, 1);
        }
        if (!Is::nemarr($uses)) return $this;

        //查找
        foreach ($uses as $ufn) {
            //读取资源的 内容行数组
            $rows = $this->getFileRows($ufn, $ps);
            if (!Is::nemarr($rows)) continue;

            //合并到当前文件的 rows 中
            $this->rowAdd($rows);
        }

        //将 rows 数据与 content 同步，不清空 rows
        $this->content = $this->rowCnt(false);

        return $this;
    }



    /**
     * 不同 export 类型，生成不同的 content
     * !! 子类可以根据 exps 中定义的可选值，实现对应的 createFooContent() 方法
     * @return $this
     */
    //例如：protected function createCssContent() {}



    /**
     * 手动操作 内容行数组
     */

    /**
     * 将 string 类型的 content 按行拆分为 rows 数组
     * @return $this
     */
    protected function contentToRows()
    {
        if (Is::nemstr($this->content)) {
            $this->rows = explode($this->rn, $this->content);
        }
        return $this;
    }
    
    /**
     * 向 内容行数组中增加 一行 或 多行
     *      rowAdd("内容", "行尾符号", 10)
     *      rowAdd([ "一行","二行",... ], 10)
     * @param String|Array $cnt 内容行数据，可以是 一行 或 多行 数据
     * @param String|Int $rn 结尾字符，默认 ; 当 $cnt 指定的是 数组时，此参数相当于 $idx
     * @param Int $idx 在 rows 中插入的 位置，默认 -1 表示 append，指定了则使用 splice 方法
     * @return $this
     */
    public function rowAdd($cnt, $rn=";", $idx=-1) 
    {
        if ($cnt==="" || Is::nemstr($cnt)) {
            //插入 一行
            if ($idx<0) {
                $this->rows[] = $cnt.$rn;
            } else {
                array_splice($this->rows, $idx, 0, $cnt.$rn);
            }
        } else if (Is::nemarr($cnt) && Is::indexed($cnt)) {
            //插入 多行
            $idx = -1;
            if (is_int($rn)) $idx = $rn;
            if ($idx<0) {
                $this->rows = array_merge($this->rows, $cnt);
            } else {
                array_splice($this->rows, $idx, 0, $cnt);
            }
        }
        return $this;
    }

    /**
     * 向 内容行数组中 增加 $n 空行
     * @param Int $n 空行的行数 默认 1
     * @param Int $idx 在 rows 中插入的 位置，默认 -1 表示 append，指定了则使用 splice 方法
     * @return $this
     */
    public function rowEmpty($n=1, $idx=-1)
    {
        if ($n>0) {
            $rows = array_fill(0, $n, "");
            $this->rowAdd($rows, $idx);
        }
        return $this;
    }

    /**
     * 向 内容行数组中 增加 注释行，可以有多行
     * @param Array $comments 一行 或 多行注释，最后一个参数如果是 int 则作为 插入位置
     * @return $this
     */
    public function rowComment(...$comments)
    {
        if (!Is::nemarr($comments)) return $this;
        //获取插入位置
        $cl = array_slice($comments, -1)[0];
        if (is_int($cl)) {
            $idx = $cl;
            $comments = array_slice($comments, 0, -1);
        } else {
            $idx = -1;
        }
        if (!Is::nemarr($comments)) return $this;

        //获取当前资源的 注释符 [ 开始符号, 每行注释的开头符号, 结尾符号 ]
        $cm = $this->cm;
        if (!Is::nemarr($cm) || !Is::indexed($cm) || count($cm)<3) return $this;

        if (count($comments)===1) {
            //单行注释
            $this->rowAdd($cm[0]." ".$comments[0]." ".$cm[2], "", $idx);
        } else {
            //多行注释
            $rows = [];
            $rows[] = $cm[0];
            foreach ($comments as $comment) {
                $rows[] = $cm[1].$comment;
            }
            $rows[] = $cm[2];
            //插入
            $this->rowAdd($rows, $idx);
        }

        return $this;
    }

    /**
     * 向 内容行数组 增加 定义变量语句
     * @param String $name 变量名
     * @param String|Int|Float $val 变量值
     * @param Array $opt 变量定义语句参数，格式见下方定义
     * @param Int $idx 在 rows 中插入的 位置，默认 -1 表示 append，指定了则使用 splice 方法
     * @return $this
     */
    public function rowDef($name, $val, $opt=[], $idx=-1)
    {
        $opt = Arr::extend([
            "prev" => "\$",     //键名 前缀，默认 \$
            "sufx" => "",       //键名 后缀，默认 无
            "gap" => ":",       //键名与值 之间的间隔符，默认 :
            "quote" => false,   //键值是否使用引号包裹，false 表示不用引号，传入 '或" 表示使用 '或" 包裹键值
            "rn" => ";",        //行尾字符，默认 ;
        ], $opt);

        $k = $opt["prev"].$name.$opt["sufx"].$opt["gap"];
        $row = [];
        $row[] = $this->rowTab($k);
        $quote = $opt["quote"];
        if (is_numeric($val)) {
            $row[] = $val;
        } else if (is_string($val)) {
            if ($quote!==false && in_array($quote, ["'","\""])) {
                $row[] = $quote.$val.$quote;
            } else {
                $row[] = $val;
            }
        } else if (is_array($val) && Is::indexed($val)) {
            $nval = array_map(function($vi) use ($quote) {
                if (is_numeric($vi) || $quote===false) return $vi;
                return $quote.$vi.$quote;
            }, $val);
            if ($quote!==false) {
                $row[] = "[".implode(",",$nval)."]";
            } else {
                $row[] = implode(",", $nval);
            }
        } else {
            $row[] = $quote===false ? "\"\"" : $quote.$quote;
        }
        $row = implode("", $row);

        return $this->rowAdd($row, $opt["rn"], $idx);
    }

    /**
     * 根据当前字符串，计算下一个 tab位 需要增加几个空格，并附加到字符串后
     * @param String $s 要处理的字符串
     * @param Int $ti 用空格模拟 tab 每 $ti 个空格表示一个 tab 位 默认 4 个空格
     * @return String 增加一定数量空格后的 字符串
     */
    public function rowTab($s="", $ti=4)
    {
        if (!is_int($ti) || $ti<=0) $ti = 2;
        if (!is_string($s)) return "";
        if (!Is::nemstr($s)) {
            //如果输入空字符串，直接输出 $ti 个空格
            return array_fill(0, $ti, " ");
        }

        $ln = strlen($s);
        $sn = ceil($ln/$ti) * $ti - $ln;
        if ($sn<=0) $sn = $ti;
        $ss = array_fill(0, $sn, " ");
        return $s.implode("",$ss);
    }

    /**
     * 将 内容行数组 合并为 字符串
     * @param Bool $clear 是否清空 rows 默认 false
     * @param String $glup 换行符 默认 \r\n
     * @return String 合并后的 字符串
     */
    public function rowCnt($clear=false, $glup=null)
    {
        //换行符
        if (!Is::nemstr($glup)) $glup = $this->rn;
        //文件行数组
        $rows = $this->rows;
        //合并
        $cnt = implode($glup, $rows);
        //清空 content
        if ($clear === true) {
            $this->rows = [];
        }
        
        return $cnt;
    }

    /**
     * 获取指定文件的 内容行数组，用于 use 或 import 其他文件资源到此资源实例
     * @param String $path 指定的文件路径 或 url
     * @param Array $params 此文件资源 实例化参数
     * @return Array 内容行数组
     */
    public function getFileRows($path, $params=[])
    {
        //判断此文件路径是否存在
        if (!Is::nemstr($path)) return [];
        //根据传入的 path 生成真实存在 资源路径
        $fp = $this->getFilePath($path);
        if (!Is::nemstr($fp)) return [];

        /**
         * 处理要引用的资源的实例化参数
         * !! 在 use|import 资源时，被引用的资源默认 不继续 use|import 其他文件 除非手动指定
         */
        //取得被引用资源的 后缀名
        $ext = Resource::getExtFromPath($fp);
        //默认使用 当前资源的 后缀名
        if (!Is::nemstr($ext)) $ext = $this->ext;
        //默认的 实例化参数
        $ps = [
            //强制不压缩
            "min" => false,
            //强制输出默认数据，如 SCSS 文件强制输出 原始数据，而不是解析后的 CSS 内容
            "export" => $ext,
            //强制不执行 use 参数
            "use" => [],
            //强制忽略 import 语句
            "noimport" => true,
        ];
        if (!Is::nemarr($params)) $params = [];
        $ps = Arr::Extend($ps, $params);
        
        //创建文件资源实例
        $res = Resource::create($fp, $ps);
        if (!$res instanceof Plain) return [];
        //插入 头部 comment
        $res->rowEmpty(1,0);
        $res->rowComment(
            "合并 ".basename($fp)." 文件",
            "!! 请不要手动修改 !!",
            0
        );
        //插入 尾部 空行
        $res->rowEmpty(3);
        //调用 export 方法 获取此文件资源的 内容行数据
        $rows = $res->export([
            //通过指定 return 的值，来获取资源 内容行数组
            "return" => "rows",
        ]);
        if (!Is::nemarr($rows)) return [];
        return $rows;
    }

    /**
     * 根据传入的 文件名|文件路径|文件url 获取最终可用于创建 资源实例的 本地文件路径|远程文件url
     * 通常用于 use|import 文件到当前资源中
     * @param String $path 传入的  文件名|文件路径|文件url
     * @return String|null 真实物理路径|url 未找到则返回 null
     */
    public function getFilePath($path)
    {
        if (!Is::nemstr($path)) return null;

        //当前 ext
        $ext = $this->ext;
        //当前文件名
        $fn = $this->name;
        //当前文件所在路径 
        $dir = dirname($this->real);

        //检查传入的 是否是 文件名 不含 DS|/|. 等路径形式字符
        if (Str::hasAny($path, DS, "/", ".") !== true) {
            //传入文件名，在当前路径的 当前文件资源名同名文件夹下 查找文件
            $fp = $dir.DS.$fn.DS.$path.".".$ext;
            if (!file_exists($fp) || is_dir($fp)) return null;
        } else {
            //传入文件路径
            //检查传入的 文件路径 是否带有 后缀名
            if (strpos($path, ".")===false) $path = "$path.$ext";
            if (strpos($path, "://")!==false) {
                //传入了 资源完整 url
                $fp = $path;
            } else if (substr($path, 0, 2)==="//" || substr($path, 0,1)==="/") {
                //传入了 以 //|/ 开头的 url
                $url = Url::current();
                if (substr($path, 0,2)==="//") {
                    $fp = $url->protocol.":".$path;
                } else {
                    //传入 以 / 开头的路径，需要先检查一次 是否真实的物理路径
                    if (file_exists($path)) {
                        $fp = $path;
                    } else {
                        $fp = $url->domain.$path;
                    }
                }
            } else {
                //传入了 本地文件路径 通过 Path::find 方法查找
                $fp = Path::find($path, PATH::FIND_FILE);
                if (!file_exists($fp)) return null;
            }
        }
        //通过 Resource::exists 确认资源一定存在
        if (Resource::exists($fp)!==true) return null;

        return $fp;
    }



    /**
     * 判断是否 压缩输出
     * @return Bool
     */
    protected function isMin()
    {
        $min = $this->params["min"] ?? false;
        return is_bool($min) ? $min : false;
    }

    /**
     * 压缩 JS/CSS
     * @return String 压缩后的 内容
     */
    protected function minify()
    {
        $ext = $this->ext;
        $mcls = "MatthiasMullie\\Minify\\".strtoupper($ext);
        if (class_exists($mcls)) {
            $minifier = new $mcls();
            $minifier->add($this->content);
            $cnt = $minifier->minify();
            return $cnt;
        }
        return $this->content;
    }
}