<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Plain 子类
 * 处理 Plain 纯文本类型 资源 基类
 */

namespace Spf\module\src\resource;

use Spf\App;
use Spf\Response;
use Spf\module\src\Resource;
use Spf\module\src\Mime;
use Spf\module\src\SrcException;
use Spf\module\src\resource\util\Rower;
use Spf\module\src\resource\util\Importer;
use Spf\module\src\resource\util\Merger;
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
    public static $stdParams = [
        //可指定要合并输出的 其他 文件
        "merge" => [],
        //合并资源时的 资源实例化参数，将与 dftMergeParams 合并
        "mergeps" => [
            //无法通过 url 传递，只能在资源实例化时 传入
        ],
        //是否忽略 import 默认 false
        "noimport" => false,
        
        //其他可选参数
        //...
    ];

    /**
     * 定义 合并资源时 被合并资源的默认实例化参数
     * !! 子类应覆盖此属性
     */
    public static $dftMergeParams = [];

    /**
     * 定义 import 此类型本地资源时，本地资源的默认实例化参数
     * !! 子类应覆盖此属性
     */
    public static $dftImportParams = [
        //默认不再执行 被 import 的本地资源内部的 import 语句
        "noimport" => true,
    ];

    /**
     * 针对 Plain 类型资源的 行数组 处理工具
     */
    public $rower = null;
    //纯文本文件 将 content 按行拆分为 indexed []
    public $rows = [];

    /**
     * 针对 Plain 类型资源的 import 语句处理工具
     */
    public $importer = null;
    //此资源中包含的 import 信息
    public $imports = [
        /*
        # css|scss 文件的 import 语句 仅包含 文件路径|url
        "文件路径 或 url",

        # js 文件的 import 语句包含 变量名 和 文件路径|url
        "fooBar" => "本地文件 或 url",
        ...
        */
    ];



    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类，如果需要，Plain 子类可以覆盖此方法
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //创建内容行工具 Rower 实例
        Rower::create($this);
        //执行 内容行数组 处理
        $this->rower->process();

        //创建 import 语句处理工具 Importer 实例
        if (Importer::support($this->ext)) {
            //只有 ext 被支持的情况下，才能创建 import 工具
            Importer::create($this);
        }
        
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

        //处理 stdParams 中定义的 某些参数
        //import
        $this->importBeforeExport();
        
        //!! merge 合并操作必须最后执行
        $this->mergeBeforeExport();

        return $this;
    }

    /**
     * 资源输出的最后一步，echo
     * !! 覆盖父类
     * @param String $content 可单独指定最终输出的内容，不指定则使用 $this->content
     * @return Resource $this
     */
    protected function echoContent($content=null)
    {
        //!! 增加 min 判断，对 JS|CSS 执行压缩
        if ($this->isMin() === true) {
            //压缩 JS/CSS 文本
            $this->content = $this->minify();
        }

        //调用父类 echoContent 方法
        return parent::echoContent($content);
    }



    /**
     * 在 beforeExport 中执行的一些特殊操作
     */

    /**
     * 处理 merge 合并操作
     * @param Array $params 输出时的 额外参数
     * @return $this
     */
    protected function mergeBeforeExport($params=[])
    {
        //根据 merge 参数，合并指定的 文件
        if (isset(static::$stdParams["merge"])) {
            $merges = $this->params["merge"] ?? [];
            if ($merges === "") $merges = [];
            if (Is::nemstr($merges)) $merges = Arr::mk($merges);
            if (Is::nemarr($merges)) {
                //合并资源 生成 content
                $this->content = $this->merge("__merge__", ...$merges);
            }
        }
        return $this;
    }

    /**
     * 处理 import 资源导入
     * @param Array $params 输出时的 额外参数
     * @return $this
     */
    protected function importBeforeExport($params=[])
    {
        if ($this->importer instanceof Importer) {
            //process
            $this->importer->process();
            //更新 rows
            $this->rows = $this->importer->export(true, $params);
            //更新 content
            $this->content = $this->rower->rowCombine();
        }

        return $this;
    }



    /**
     * 工具方法
     */

    /**
     * 合并 merge 指定的 纯文本文件 生成 包含所有文件 内容行数组的 content 字符串
     * $this->merge(null)                       清空当前 params["merge"]
     * $this->merge(null, fp, fp, ...)          清空当前 params["merge"] 然后 写入新的 merges 数组
     * $this->merge(fp, fp, ...)                向 params["merge"] 插入新的 待合并资源路径
     * $this->merge([esm=>true], fp, fp ...)    写入 params["mergeps"] 然后 插入新的 待合并资源路径
     * $this->merge("__merge__", fp, fp, ...)   开始执行合并
     * !! 此方法不会修改当前资源实例的 自身内容
     * !! 此方法不支持多次调用返回合并结果，只能一次调用返回一次结果
     * !! 子类不要覆盖此方法
     * @param Array $merges 要合并的 merge 文件名|文件路径
     * @return String 生成 完整资源内容 content
     */
    final public function merge(...$merges)
    {
        if (!Is::nemarr($merges)) return $this;

        //开始合并标记
        $do = false;

        //如果传入 null 或 以 null 开始
        if (is_null($merges[0])) {
            //清空当前 params["merge"]
            $this->params["merge"] = [];
            $merges = array_slice($merges, 1);
        }
        if (empty($merges)) return $this;
        
        //传入 [关联数组] 或 以 [关联数组] 开始
        if (Is::nemarr($merges[0]) && Is::associate($merges[0])) {
            //设置 params["mergeps"]
            $this->params["mergeps"] = $merges[0];
            $merges = array_slice($merges, 1);
        }
        if (empty($merges)) return $this;

        //出入 __merge__ 开始的 参数数组
        if ($merges[0] === "__merge__") {
            //开始执行 合并操作
            $do = true;
            $merges = array_slice($merges, 1);
        }
        if (empty($merges)) return $this;

        if ($do !== true) {
            //添加 新的待合并资源到 params["merge"]
            foreach ($merges as $mf) {
                if (!in_array($mf, $this->params["merge"])) {
                    $this->params["merge"][] = $mf;
                }
            }
            return $this;
        }

        //开始合并
        //资源合并时的 实例化参数
        $ps = $this->params["mergeps"] ?? [];
        //依次查询 merge 文件的实际 本地路径
        $fps = [];
        foreach ($merges as $fn) {
            //获取 merge 文件的 真实路径 本地文件路径
            $fp = $this->getmergeFilePath($fn);
            if (Is::nemstr($fp)) $fps[] = $fp;
        }
        if (!Is::nemarr($fps)) return $this;

        //clone 当前资源实例，这样就不会修改 当前实例自身的 数据
        $self = $this->clone();

        //使用 Merger 工具 合并资源，自动处理 import 相关数据
        $merger = new Merger($this->ext);
        //设置 待合并资源的 实例化参数
        $merger->setMergeParams($ps);
        //依次添加 待合并的资源路径
        $merger->add($self, ...$fps);
        //生成完整 内容行数组 覆盖现有的 rows
        $rows = $merger->export();
        //释放 合并工具实例
        unset($merger);

        //生成 完整的 content
        $rower = $self->rower;
        $rower->clearRows();
        $rower->rowAdd($rows);
        $content = $rower->rowCombine();

        //释放 clone 的当前资源实例
        unset($self);

        return $content;
    }

    /**
     * 根据传入的 merge 文件名|文件路径 获取真实存在的 本地文件路径
     * !! 子类可以覆盖此方法
     * @param String $ufn 文件名|文件路径
     * @return String|null 真实本地文件路径
     */
    protected function getMergeFilePath($ufn)
    {
        if (!Is::nemstr($ufn)) return null;
        //先检查一次 是否传入了 真实路径
        if (file_exists($ufn)) return $ufn;

        //当前 ext
        $ext = $this->ext;
        //当前文件名
        $fn = $this->name;

        if (Str::hasAny($ufn, "/","\\",".",DS) !== true) {
            /**
             * 传入了 文件名
             * 通过 getLocalResInnerPath 方法查找 当前资源路径下 文件
             */
            $fp = $this->getLocalResInnerPath("$ufn.$ext", true);
            if (!file_exists($fp)) $fp = $this->getLocalResInnerPath("$fn/$ufn.$ext", true);
            if (file_exists($fp)) return $fp;
            return null;
        }

        //传入了 文件路径
        
        //先使用 getLocalResInnerPath 检查
        $fp = $this->getLocalResInnerPath($ufn, true);
        if (file_exists($fp)) return $fp;

        //再使用 Path::find 查找
        $fp = Path::find($ufn, Path::FIND_FILE);
        if (file_exists($fp)) return $fp;

        return null;
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

    /**
     * 压缩指定的 字符串
     * @param String $cnt 要执行压缩的代码
     * @param String $ext 这段代码的 类型 js|css
     * @return String 压缩后的代码
     */
    public function minifyCnt($cnt, $ext="js")
    {
        if (!Is::nemstr($cnt) || !Is::nemstr($ext)) return $cnt;
        $mcls = "MatthiasMullie\\Minify\\".strtoupper($ext);
        if (class_exists($mcls)) {
            $minifier = new $mcls();
            $minifier->add($cnt);
            $mcnt = $minifier->minify();
            return $mcnt;
        }
        return $cnt;
    }



    /**
     * 静态方法
     */

    /**
     * 替换 代码中的 字符串模板
     * 
     * 字符串模板的定义形式：
     *  [
     *      "__TPL__" => "key",
     *      "TPL@" => "foo/bar",
     *      # 支持 二次替换
     *      "<@-" => ["foo/bar", "<%-"],
     *  ]
     * 
     * @param String $code 代码字符串
     * @param Array $tpls 预定义的 字符串模板 与 $data 数据 key 的映射关系
     * @param Array $data 字符串模板替换的 数据源
     * @return String 替换后的 代码字符串
     */
    public static function replaceTplsInCode($code, $tpls=[], $data=[])
    {
        if (!Is::nemstr($code)) return $code;
        if (!Is::nemarr($tpls) || !Is::nemarr($data)) return $code;

        //依次执行模板替换
        foreach ($tpls as $tpl => $tpv) {
            if (Is::nemstr($tpv)) {
                $d = Arr::find($data, $tpv);
                $stp = null;
            } else if (Is::nemarr($tpv) && Is::indexed($tpv) && count($tpv)>=2) {
                $d = Arr::find($data, $tpv[0]);
                //需要二次替换，替换其中的 %
                $stp = $tpv[1];
            }
            //替换数据必须是非空字符串
            if (!Is::nemstr($d)) continue;
            //如果需要二次替换，先将 data 替换进入 二次替换字符串中
            if (Is::nemstr($stp) && strpos($stp, "%")!==false) {
                $d = str_replace("%", $d, $stp);
            }
            //替换字符串
            $code = str_replace($tpl, $d, $code);
        }

        return $code;
    }
}