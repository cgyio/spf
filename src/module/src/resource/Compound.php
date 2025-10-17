<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Compound 子类
 * 处理 复合类型的 资源
 * 例如：Theme | Icon | Lib 等
 * 
 * 此类型资源都有一个主文件 *.json 文件(例如：foo.theme.json)，其中储存了对于此资源的 相关描述，描述数据的格式 在 stdDesc 参数中定义
 * 资源的描述数据 会保存到 资源实例的 desc 属性中
 * 
 * 复合资源的输出规则：
 *      https://host/src/foo.cdn?export=js&ver=@&file=bar 相当于：
 *          访问 [允许访问的资源路径]/foo.cdn.json 读取并解析 Cdn 类型资源，生成 $res->desc 属性
 *          查找版本号 ver=@ 指向的 实际版本号，在 $res->desc["version"]["@"] 中定义
 *          查找 实际版本号中 export=js 类型的，文件名 file=bar 的文件参数，在 $res->desc["content"][version]["js"]["bar"] 中定义
 *          根据子资源类型，读取|动态创建 子资源内容，生成子资源实例，然后调用子资源，生成最终输出的 资源 content
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\SrcException;
use Spf\module\src\Mime;
use Spf\Request;
use Spf\Response;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;

class Compound extends Resource 
{
    /**
     * 当前的资源类型的本地文件，是否应保存在 特定路径下
     * 指定的 特定路径 必须在 Src::$current->config->resource["access"] 中定义的 允许访问的文件夹下
     *  null        表示不需要保存在 特定路径下，本地资源应在 [允许访问的文件夹]/... 路径下
     *  "ext"       表示应保存在   [允许访问的文件夹]/[资源后缀名]/... 路径下
     *  "foo/bar"   指定了 特定路径 foo/bar 表示此类型本地资源文件应保存在   [允许访问的文件夹]/foo/bar/... 路径下
     * 默认 null 不指定 特定路径
     * !! 如果必须，子类可以覆盖此属性
     */
    public static $filePath = "ext";     //可选 null | ext | 其他任意路径形式字符串 foo/bar 首尾不应有 /

    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    public static $stdParams = [
        /**
         * 其他参数
         */
        //是否 强制不使用 缓存的 文件内容
        "create" => false,
        //使用的 版本号 默认 @  可选 @|latest|具体版本号 如 2.3.4
        "ver" => "@",
        //输出文件的 类型 默认类型是 stdDesc["ext"][0]
        "export" => "",
        //要输出的 复合资源内部文件的 文件名，在资源 desc["content"][version][ext] 数组中定义的 文件名，不一定是真实存在的文件
        "file" => "default",

        //直接输出本地库中的 内部文件，通过指定 inner 参数
        "inner" => "",
        
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
            //compound 资源的主文件是 json 读取其内容
            "GetJsonContent" => [],
            //compound 资源初始化，读取 desc 资源描述数据，生成对应参数，并缓存到资源实例
            "InitCompoundDesc" => [],
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],

            //根据 export 参数，创建|读取 子资源内容，创建子资源实例
            "CreateSubResource" => [],

            //调用 子资源实例 或 直接使用缓存内容，生成最终输出的 content
            "CreateContent" => [
                "break" => true,
            ],
        ],
    ];

    /**
     * 定义 复合资源在其 json 主文件中的 资源描述数据的 标准格式
     * !! 子类应在此基础上扩展
     */
    public static $stdDesc = [
        //!! 必须的 参数项
        //此复合资源的名称 foo_bar 形式
        "name" => "",

        //是否启用版本控制
        "enableVersion" => true,
        //指定可以通过 @|latest 访问的 版本号
        "version" => [
            //当前使用的 默认版本 可省略版本号，如：/src/vue.lib  相当于  /src/vue.lib?ver=@
            "@" => "",
            //最新版本号，如：/src/vue.lib?ver=latest
            "latest" => "",
        ],

        //是否启用缓存
        "enableCache" => true,
        //如果启用缓存，指定缓存文件保存路径，一定在 foo/bar/[version]/ 路径下，默认保存在 cache 文件夹下
        "cachePath" => "cache",

        //允许输出的 ext 类型数组，必须是 Mime 类中支持的后缀名类型
        "ext" => [],

        /**
         * 复合资源的根路径
         *  0   针对本地的复合资源 Theme | Icon | Vcom | Lib 等类型：
         *      此参数表示 此复合资源的本地保存路径，不指定则使用当前 *.ext.json 文件的 同级同名文件夹
         *  1   针对远程复合资源 Cdn 类型：
         *      此参数表示 cdn 资源的 url 前缀，不带版本号
         */
        "root" => "",

        //复合资源中包含的资源定义
        "content" => [
            /*
            # 定义不同的版本号 包含资源
            "2.7.1" => [
                # 输出的资源 ext
                "js" => [
                    # 定义可访问的 复合资源内部子文件的 文件名，通过 params["file"] 传入
                    "file-name" => [
                        # 子资源描述参数，与 $stdSubResource 数据结构一致
                    ],
                ],
            ],
            */
        ],


    ];

    //保存在资源实例中的 资源描述参数
    public $desc = [];

    /**
     * 定义复合资源 内部子资源的 描述参数
     * !! 子类应在此基础上扩展
     */
    public static $stdSubResource = [
        //子资源类型，可以是 static | dynamic   表示   静态的实际存在的 | 动态生成的   子资源内容
        "type" => "static",

        /**
         * type=static 静态子资源 需要下列参数
         */
        //实际文件路径，通过 [root]/[version]/[此处定义的 file] 拼接即可得到实际子文件路径
        "file" => "",

        /**
         * type=dynamic 动态子资源 需要下列参数
         */
        //定义动态生成子资源内容的 方法名，不指定则使用默认的 createFileNameExt 方法名，必须在资源类中定义此方法
        "creator" => "",
        //可单独定义 动态生成此子资源时，是否不启用缓存
        "disableCache" => false,

        //子资源的 实例化参数
        "params" => [
            //可手动指定此 子资源的 实例化参数
        ],

        //针对 输出内容 content 创建之后，输出之前，执行一些特殊操作，fixFooBarBeforeExport 可在子资源描述参数中定义要执行的操作
        "fix" => [],

    ];

    //解析得到的 子资源文件名，可通过 file 参数传入，在 desc["content"][version][ext] 数组中定义
    public $subResourceName = "default";    //默认 default
    //解析得到的 当前请求的子资源 描述参数
    public $subResourceOpts = [];

    //查找并解析要返回的子资源，创建|读取 子资源内容，生成子资源实例，缓存到此属性
    public $subResource = null;


    
    /**
     * 资源实例内部定义的 stage 处理方法
     * @param Array $params 方法额外参数
     * @return Bool 返回 false 则终止 当前阶段 后续的其他中间件执行
     */
    //InitCompoundDesc 初始化此复合资源，生成 desc 资源描述参数，保存到 desc 属性
    public function stageInitCompoundDesc($params=[])
    {
        //json 文件内容已读取到 jsonData 数组中
        $jd = $this->jsonData;
        //stdDesc 标准描述数据格式，需要合并 Compound 基类和子类的 stdDesc
        $std = Arr::extend(Compound::$stdDesc, static::$stdDesc, true);
        //返回标准描述数据中定义的 json 中的数据，这些数据将作为 desc
        $desc = Arr::intersect($jd, $std);
        //默认值填充
        $desc = Arr::extend($std, $desc, true);
        //保存到 desc
        $this->desc = $desc;

        //确保 export 输出类型，在 desc["ext"] 数组中
        $this->initExport();

        //如果定义了 本地复合资源的 root
        $this->initRoot();

        //解析并查找当前请求的子资源 描述参数数据，保存到 subResourceOpts 属性中
        $this->initSubResource();

        return true;
    }
    //CreateSubResource 根据传入的参数，查找|读取|创建 子资源内容，生成子资源实例，保存到 subResource 属性
    public function stageCreateSubResource($params=[])
    {
        //如果指定了 要输出 本地库内部文件
        $ps = $this->params;
        $inner = $ps["inner"] ?? "";
        if (Is::nemstr($inner) || Is::nemarr($inner)) {
            //获取要输出的 内部资源实例
            $ires = $this->resInnerResource();
            if (!$ires instanceof Resource) {
                //报错
                throw new SrcException("当前复合资源 ".$this->resBaseName()." 未找到要输出的内部资源 $inner", "resource/getcontent");
            }
            //将内部资源实例 作为 subResource
            $this->subResource = $ires;
            return true;
        }

        //子资源名称
        $fn = $this->subResourceName;
        //子资源 描述参数
        $opts = $this->subResourceOpts;
        //子资源 ext
        $ext = $this->ext;

        //是否启用缓存
        $cacheEnabled = $this->cacheEnabled();
        //是否传入 create 参数强制忽略缓存
        $cacheIgnored = $this->cacheIgnored();

        //解析子资源参数，查找|创建 资源内容
        $type = $opts["type"] ?? "static";

        //针对 dynamic 动态子资源
        if ($type === "dynamic") {

            //此 子资源单独定义的 disableCache 不启用缓存 标记
            $cacheDisabled = $opts["disableCache"] ?? false;
            if (!is_bool($cacheDisabled)) $cacheDisabled = false;
            //首先尝试读取缓存
            if ($cacheEnabled === true && $cacheIgnored !== true && $cacheDisabled !== true) {
                $cache = $this->cacheGetContent();
                if (!is_null($cache)) {
                    //读取到缓存内容，直接使用
                    $this->content = $cache;
                    return true;
                }
            }

            //然后尝试获取 动态子资源可能定义的 creator 自定义创建方法
            $creator = $opts["creator"] ?? "";
            if (Is::nemstr($creator) && method_exists($this, $creator)) {
                //调用 creator 方法，将生成 subResource 子资源实例
                $this->$creator();
            }

        }

        //针对 static 静态子资源  或  未定义 creator 方法的 dynamic 动态子资源
        if (empty($this->subResource) || !$this->subResource instanceof Resource) {
            //依次在当前资源实例中，查找 create 方法，找到则执行，将生成的子资源实例 缓存到 subResource 属性
            $this->createSubResource();
        }

        return true;
    }
    //CreateContent 调用 subResource 子资源实例，生成最终输出的 content
    public function stageCreateContent($params=[])
    {
        //是否启用缓存
        $cacheEnabled = $this->cacheEnabled();
        //是否传入 create 参数强制忽略缓存
        $cacheIgnored = $this->cacheIgnored();

        //调用子资源实例，生成要输出的 content
        $subres = $this->subResource;
        if (!$subres instanceof Resource && !($cacheEnabled === true && $cacheIgnored !== true) ) {
            //不启用缓存  或  强制忽略缓存时  子资源实例必须创建
            throw new SrcException("当前复合资源 ".$this->resBaseName()." 中的子文件 ".$this->resExportBaseName()." 无法创建资源实例", "resource/getcontent");
        }

        if ($subres instanceof Resource) {
            //调用子资源实例 创建当前资源的输出 content
            $cnt = $subres->export([
                "return" => true
            ]);
            //保存生成的 content
            if (Is::nemstr($cnt)) {
                $this->content = $cnt;
            } else {
                $this->content = "";
            }
    
            //针对 dynamic 动态子资源，需要写入缓存
            $type = $this->subResourceOpts["type"] ?? "static";
            //此 子资源单独定义的 不启用缓存 标记
            $cacheDisabled = $this->subResourceOpts["disableCache"] ?? false;
            if (!is_bool($cacheDisabled)) $cacheDisabled = false;
            if ($type === "dynamic" && $cacheEnabled === true && $cacheDisabled !== true) {
                //写入缓存
                $this->cacheSaveContent();
            }
        }

        return true;
    }



    /**
     * 在输出资源内容之前，对资源内容执行处理
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function beforeExport()
    {
        //子资源描述参数
        $opts = $this->subResourceOpts;
        //子资源参数中定义的 fixFooBarBeforeExport 方法数组
        $fixes = $opts["fix"] ?? [];
        if (Is::nemarr($fixes) && Is::indexed($fixes)) {
            //依次执行 fix 方法，这些方法必须在 此资源类中被定义
            foreach ($fixes as $fixm) {
                $m = "fix".$fixm."BeforeExport";
                if (method_exists($this, $m)) {
                    //执行这些 fix 方法，可能会修改最终输出内容 content
                    $this->$m();
                }
            }
        }

        //如果输出的是 Codex 代码文件，根据 min 参数 minify 压缩
        $min = $this->params["min"] ?? false;
        if (!is_bool($min)) $min = false;
        $ext = $this->ext;
        if ($min === true && Mime::getProcessableType($ext) === "codex") {
            $this->content = Codex::minifyCnt($this->content, $ext);
        }

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
        /**
         * !! 调用 子资源实例的 echoContent 方法
         * 主要针对某些 特殊类型的子资源，例如：字体|图片|Stream类型  调用资源自有的 export 输出方法
         */
        $subres = $this->subResource;
        if ($subres instanceof Resource) {
            if (
                $subres instanceof Download ||
                $subres instanceof Image ||
                $subres instanceof Svg ||
                $subres instanceof Audio ||
                $subres instanceof Video
            ) {
                //当子资源是这些类型时，调用子资源自有的 export 输出方法
                return $subres->export();
            }
        }

        //子资源实例不存在，则使用 默认输出方法
        return parent::echoContent($content);
    }



    /**
     * 工具方法 解析复合资源内部 子资源参数，查找|创建 子资源内容
     * 根据  子资源类型|子资源ext|子资源文件名  分别制定对应的解析方法
     * !! Compound 子类可覆盖此方法
     * @return $this
     */
    //static 静态的本地存在的 子资源
    protected function createStaticSubResource()
    {
        //子资源名称
        $fn = $this->subResourceName;
        //子资源 描述参数
        $opts = $this->subResourceOpts;
        //子资源 ext
        $ext = $this->ext;

        if (isset($opts["type"]) && $opts["type"]!=="static") return $this;

        //复合资源名称
        $rn = $this->resName();
        
        //static 类型子资源参数
        $file = $opts["file"] ?? null;
        if (!Is::nemstr($file)) return $this;

        //查找子资源文件，一定真实存在
        $pather = $this->PathProcessor;
        $fp = $pather->inner(trim($file, "/"), true);
        if (!file_exists($fp)) {
            //未找到子资源文件，报错
            throw new SrcException("当前复合资源 $rn 中未找到子文件 $file", "resource/getcontent");
        }

        //子资源实例化参数
        $ps = $opts["params"] ?? [];
        $ps = Arr::extend([
            //belongTo
            "belongTo" => $this,
            //不忽略 $_GET
            "ignoreGet" => false,
        ], $ps);

        //创建子资源实例
        //var_dump($fp);var_dump($ps);
        $sres = Resource::create($fp, $ps);
        if (!$sres instanceof Resource) {
            //子资源实例创建失败
            throw new SrcException("当前复合资源 $rn 中的子文件 $file 无法创建资源实例", "resource/getcontent");
        }

        //保存到 $this->subResource 
        $this->subResource = $sres;
        return $this;
    }
    //dynamic 动态创建 子资源内容
    protected function createDynamicSubResource()
    {
        /**
         * 子类应实现各自的 动态创建子资源内容的 方法逻辑
         * 根据需要，可定义不同层级的 动态创建方法，例如：
         *      createDynamicJsFilenameSubResource()
         *      createDynamicJsSubResource()
         *      createDynamicSubResource()
         */

        //!! 示例代码

        //子资源名称
        $fn = $this->subResourceName;
        //子资源 描述参数
        $opts = $this->subResourceOpts;
        //子资源 ext
        $ext = $this->ext;

        //for Dev
        //手动创建 js 
        $jsres = Resource::manual(
            "",
            "".$this->resExportBaseName()."",
            [
                //belongTo
                "belongTo" => $this,
                //不忽略 $_GET
                "ignoreGet" => false,
                //min
                "min" => $this->params["min"] ?? false,
            ]
        );
        //通过 RowProcessor 写入内容行
        $rower = $jsres->RowProcessor;
        $rower->rowComment(
            "手动创建 JS",
            "forDev",
            "将自动处理 import 语句中的 url"
        );
        $rower->rowEmpty(1);
        $rower->rowAdd("import jaz from 'mixin/base';","");
        $rower->rowEmpty(1);
        $rower->rowAdd("export default {","");
        $rower->rowAdd("    foo: 123,","");
        $rower->rowAdd("    bar: 456,","");
        $rower->rowAdd("}","");
        $rower->rowEmpty(1);
        //ImportProcessor 执行 stageCreate 处理 import 语句
        $jsres->ImportProcessor->callStageCreate();
        //EsmProcessor 执行 stageCreate
        $jsres->EsmProcessor->callStageCreate();
        //var_dump($jsres->imports);

        $this->subResource = $jsres;
        

        return $this;
    }



    /**
     * 工具方法 初始化复合资源 desc 工具
     */

    /**
     * 处理 export 参数，确保 export 输出类型，在 desc["ext"] 数组中
     * @return $this
     */
    protected function initExport()
    {
        $desc = $this->desc;
        $ps = $this->params;

        //支持的 ext 输出类型
        $exts = $desc["ext"] ?? [];
        if (!isset($ps["export"]) || !Is::nemstr($ps["export"])) {
            //未指定 export 则使用第一个支持的 ext 类型作为输出类型
            $this->params["export"] = $exts[0];
            //设置 export
            $this->setExt($exts[0]);
        }
        if (!in_array(strtolower($this->ext), $exts)) {
            //要输出的类型，不在支持的 ext 列表中，报错
            throw new SrcException("当前复合资源 ".$this->resBaseName()." 不支持输出 ".$this->ext." 类型","resource/getcontent");
        }

        return $this;
    }

    /**
     * 将 desc 中指定的 root 参数，更新到资源实例中
     * @return $this
     */
    protected function initRoot()
    {
        $desc = $this->desc;
        $root = $desc["root"] ?? null;
        if (!Is::nemstr($root)) return $this;
        //复合资源 json 主文件名
        $jn = $this->name;
        
        //使用 Path::find 查找本地路径
        if ($this->PathProcessor->isRemote($root) !== true) {
            $root = Path::find($root, Path::FIND_DIR);
            if (Is::nemstr($root)) $this->real = $root.DS.$jn;
        } else {
            //!! root 如果是远程 url 则不覆盖 real
            //$this->real = "$root/$jn";
        }

        return $this;
    }

    /**
     * 根据请求参数 获取目标子资源的 名称|参数 保存到 subResourceName | subResourceOpts 属性
     * @return $this
     */
    protected function initSubResource()
    {
        $desc = $this->desc;
        $exts = $desc["ext"] ?? [];
        $ps = $this->params;

        //此复合资源中 定义的所有可用 子资源参数
        $defs = $this->resContents();
        
        //export
        $exp = $ps["export"] ?? $exts[0];
        if (!isset($defs[$exp])) {
            //指定输出的 ext 未在 desc["content"][version] 中定义，报错
            throw new SrcException("当前复合资源 ".$this->resBaseName()." 中未定义输出类型为 $exp 的资源参数", "resource/getcontent");
        }
        $defs = $defs[$exp];
        //要访问的 子资源文件名，在 desc["content"][version][ext] 数组中定义
        $fn = $ps["file"] ?? "default";
        if (!isset($defs[$fn]) || !Is::nemarr($defs[$fn])) {
            //要访问的文件名 未在 desc["content"][version][ext] 中定义
            throw new SrcException("当前复合资源 ".$this->resBaseName()." 中未定义名称为 ".$this->resExportBaseName()." 的资源参数", "resource/getcontent");
        }

        //缓存解析结果
        $this->subResourceName = $fn;
        $this->subResourceOpts = Arr::extend(Compound::$stdSubResource, static::$stdSubResource, $defs[$fn], true);

        return $this;
    }



    /**
     * 工具方法 缓存处理工具
     */

    /**
     * 判断当前请求的资源是否启用了缓存
     * @return Bool
     */
    final public function cacheEnabled()
    {
        //desc
        $desc = $this->desc;
        //cache 参数
        $enable = $desc["enableCache"] ?? true;
        if (!is_bool($enable)) $enable = true;
        return $enable;
    }

    /**
     * 判断当前 params 参数中 是否指定强制忽略缓存
     * @return Bool
     */
    final public function cacheIgnored()
    {
        $cacheIgnored = $this->params["create"] ?? false;
        if (!is_bool($cacheIgnored)) $cacheIgnored = false;
        return $cacheIgnored;
    }

    /**
     * 获取当前资源的 缓存路径前缀，真实存在的本地文件夹路径
     * !! 子类可覆盖此方法
     * @param String $file 可指定缓存文件夹下的某个缓存文件，将返回完整的 缓存文件路径
     * @param Bool $exists 在返回文件路径前是否检查其存在性，默认 false 不检查存在性
     * @return String|null 未启用缓存时 返回 null
     */
    public function cachePath($file=null, $exists=false)
    {
        if ($this->cacheEnabled() !== true) return null;
        
        //cache dir
        $cp = $desc["cachePath"] ?? "cache";
        //pather
        $pather = $this->PathProcessor;
        //要查询的 子文件|子路径
        $sp = $cp.(Is::nemstr($file) ? "/".trim($file,"/") : "");

        //查询路径，返回结果
        return $pather->inner($sp, $exists);
    }

    /**
     * 根据传入的参数，生成缓存文件名
     * !! 子类可覆盖此方法
     * @return String|null 当前请求的子资源对应缓存文件的 文件名
     */
    public function cacheFileName()
    {
        if ($this->cacheEnabled() !== true) return null;

        //请求的 file 参数
        //$fn = $this->subResourceName;
        //请求的子资源 描述参数
        $opts = $this->subResourceOpts;
        //子资源类型 static|dynamic
        $stp = $opts["type"];
        //要输出的子资源类型 ext
        //$ext = $this->ext;
        //子资源完整的 文件名 dynamic-default.js
        $base = "$stp-".$this->resExportBaseName();

        return $base;
    }

    /**
     * 读取缓存文件的内容
     * @return String|null 
     */
    final public function cacheGetContent()
    {
        if ($this->cacheEnabled() !== true) return null;
        
        //缓存文件名
        $cfn = $this->cacheFileName();
        if (!Is::nemstr($cfn)) return null;
        //缓存文件路径，需要检查存在性
        $cfp = $this->cachePath($cfn, true);
        if (!Is::nemstr($cfp)) return null;
        //读取缓存文件内容
        $cache = file_get_contents($cfp);
        return $cache;
    }

    /**
     * 将当前生成的 资源输出内容 写入缓存文件
     * @return Bool
     */
    final public function cacheSaveContent()
    {
        if ($this->cacheEnabled() !== true) return false;
        
        //缓存文件名
        $cfn = $this->cacheFileName();
        if (!Is::nemstr($cfn)) return null;
        //缓存文件路径，不需要检查存在性
        $cfp = $this->cachePath($cfn, false);
        //写入文件内容
        return Path::mkfile($cfp, $this->content);
    }



    /**
     * 按层级尝试调用 子资源的 create 方法
     * 例如：请求 export=js&file=default  对应的 default 子资源类型为 dynamic  则依次尝试调用下列方法：
     *      createDynamicJsDefaultSubResource
     *      createDynamicJsSubResource
     *      createDynamicSubResource
     * 如果方法存在，则调用，将 生成 子资源实例 缓存到 $this->subResource 
     * @return $this
     */
    protected function createSubResource()
    {
        //子资源名称
        $fn = $this->subResourceName;
        //子资源 描述参数
        $opts = $this->subResourceOpts;
        $type = $opts["type"] ?? "static";
        //子资源 ext
        $ext = $this->ext;

        //依次在当前资源实例中，查找下列方法，找到则执行，将生成的子资源实例 缓存到 subResource 属性
        $mtp = Str::camel($type, true);     // Static|Dynamic
        $mex = Str::camel($ext, true);      // Js|Css|Scss... 
        $mfn = Str::camel($fn, true);       // Defaulr|FooBar... 
        $ms = [];
        $ms[] = $mtp.$mex.$mfn;
        $ms[] = $mtp.$mex;
        $ms[] = $mtp;
        //依次查找方法
        foreach ($ms as $mi) {
            $m = "create".$mi."SubResource";
            if (method_exists($this, $m)) {
                $this->$m();
                break;
            }
        }

        //检查 subResource 子资源实例是否被 正确的 创建
        $subres = $this->subResource;
        if (!$subres instanceof Resource) {
            //子资源实例创建失败
            throw new SrcException("当前复合资源 ".$this->resBaseName()." 中的子文件 ".$this->resExportBaseName()." 无法创建资源实例", "resource/getcontent");
        }

        return $this;
    }



    /**
     * 工具方法 getters 资源信息获取
     */

    /**
     * 获取此复合资源的 ext 后缀名，即此类的 小写类名 foo_bar 形式
     * @return String 
     */
    public function resComExt()
    {
        $clsn = Cls::name(static::class);
        return Str::snake($clsn);
    }

    /**
     * 复合资源必须覆盖 Resource 父类的 resName 方法，获取资源名称
     * !! 如果需要，Compound 子类可覆盖这个方法
     * @return String 当前资源的 名称 foo_bar 形式
     */
    public function resName()
    {
        $n = $this->desc["name"] ?? null;
        if (Is::nemstr($n)) return $n;
        //调用父类方法
        return parent::resName();
    }

    /**
     * 此复合资源的 basename 即 资源名称.资源ext
     * @return String
     */
    public function resBaseName()
    {
        return $this->resName().".".$this->resComExt();
    }

    /**
     * 此复合资源当前请求输出的 子资源 basename 即 subResourceName.exportExt
     * @return String
     */
    public function resExportBaseName()
    {
        return $this->subResourceName.".".$this->ext;
    }

    /**
     * 如果此复合资源 启用了版本控制，需要定义 获取当前版本的 方法
     * !! 子类可覆盖此方法
     * @return String|null 版本号字符串 1.0.0  2.17.53 等形式的 字符串，对应着实际存在的 文件夹
     */
    public function resVersion()
    {
        //desc
        $desc = $this->desc;
        //version 定义
        $enable = $desc["enableVersion"] ?? true;
        if (!is_bool($enable)) $enable = true;

        //如果未启用 版本控制，返回 null
        if ($enable !== true) return null;

        //version 定义
        $vers = $desc["version"] ?? [];
        //传入的 params 中的版本参数
        $ver = $this->params["ver"] ?? "@";
        if (!Is::nemstr($ver) || !isset($vers[$ver])) $ver = "@";
        //指向的实际版本号 
        $cver = $vers[$ver];

        //在 desc["content"] 中定义的 版本号
        $dvers = $desc["content"] ?? [];
        if (Is::nemarr($dvers) && !isset($dvers[$cver])) return null;
        return $cver;
    }

    /**
     * 获取此复合资源中，定义的所有版本号，以及各版本号下的所有定义的 可访问资源名称和参数，即 desc["content"][version] 数组
     * @return Array|null
     */
    public function resContents()
    {
        $desc = $this->desc;
        $verEnabled = $desc["enableVersion"] ?? true;
        if (!is_bool($verEnabled)) $verEnabled = true;

        //ver
        $ver = $this->resVersion();
        if ($verEnabled === true && !Is::nemstr($ver)) return null;
        //content
        $cnts = $this->desc["content"] ?? [];
        if ($verEnabled !== true) return $cnts;
        return $cnts[$ver];
    }

    /**
     * 查找并获取 本地库 内部实际存在的 文件，返回资源实例
     * @return Resource|null
     */
    public function resInnerResource()
    {
        $ps = $this->params;
        $inner = $ps["inner"] ?? "";
        if (Is::nemarr($inner)) $inner = implode("/", $inner);
        if (!Is::nemstr($inner)) return null;
        //ext
        $pi = pathinfo($inner);
        $iext = $pi["extension"] ?? null;
        if (!Is::nemstr($iext)) {
            //未指定 内部文件 ext 则使用当前的 export ext
            $iext = $this->ext;
            $inner .= ".$iext";
        } else {
            //指定了 内部文件 ext
            if ($iext !== $this->ext) {
                $this->setExt($iext);
                $this->params["export"] = $iext;
            }
        }
        //查找内部文件
        $innerp = $this->PathProcessor->inner($inner, true);
        if (!Is::nemstr($innerp)) return null;

        //创建内部资源实例
        $ires = Resource::create($innerp,[
            //!! 外部直接访问此内部资源，不需要作为 复合资源的子资源来 实例化，因此不需要传入 belongTo
            //"belongTo" => $this,
            //"ignoreGet" => false,
        ]);
        if (!$ires instanceof Resource) return null;
        return $ires;
    }

    /**
     * 在当前请求的 复合资源 url 基础上创建新 url 实例
     * 例如：
     *      当前请求的复合资源 url：https://host/src/foo/bar/jaz.vcom?export=css&mode=mini
     *          调用 resUrlMk("default.js?mode=full") 将得到新 url ：
     *              https://host/src/foo/bar/jaz.vcom?export=js&file=default&mode=full
     *      当前请求的复合资源 url：https://host/src/vcom/foo/bar/jaz/default.css?mode=mini
     *          调用 resUrlMk("default.js?mode=full") 将得到新 url ：
     *              https://host/src/vcom/foo/bar/jaz/default.js?mode=full
     * @param String $uri 新的 uri 将与当前 url 合并，参考 Url::mk 方法参数
     * @return Url 生成新的 url 实例
     */
    public function resUrlMk($uri)
    {
        //当前 url 实例
        $uo = Url::current();
        //指向的 basename
        $basename = $uo->basename;
        //当前复合资源的 comExt 
        $cext = $this->resComExt();
        $cextlen = strlen($cext)+1;

        //检查当前请求的 方式，直接通过 json 文件路径访问  或  通过调用 responseProxyer 代理响应方式访问
        if (substr($basename, $cextlen*-1) === ".$cext") {
            //直接通过 json 路径访问此复合资源的 标记
            $direct = true;
        } else {
            //使用 Compound::responseProxyer 代理响应方式 请求此复合资源
            $direct = false;
        }

        //处理传入的 uri
        if (substr($uri, 0,1)==="?") {
            //传入 ? 开头的 queryString 补全此复合资源 basename
            $uri = "../$basename".$uri;
        } else {
            //uri 字符串中 path 部分
            $upath = strpos($uri, "?")===false ? $uri : explode("?", $uri)[0];
            //uri 字符串中的 basename
            $ubase = basename($upath);
            //uri 字符串中的 dirname
            $udir = dirname($upath);
            //uri 中的 queryString
            if (strpos($uri, "?")===false) {
                $uq = [];
            } else {
                $uq = Conv::u2a(explode("?", $uri)[1]);
            }

            if (substr($ubase, $cextlen*-1) === ".$cext") {
                //uri 中是直接访问 复合资源的 json 文件名
                if ($direct === false) {
                    //当前是使用 proxyer 代理响应，需要从 uri queryString 中查找 file.export
                    $file = $uq["file"] ?? static::$stdParams["file"];
                    $export = $uq["export"] ?? null;
                    if (!Is::nemstr($export)) $export = $this->desc["ext"][0];
                    //重新拼接 uri
                    $uarr = [];
                    if (Is::nemstr($udir)) $uarr[] = $udir;
                    $uarr[] = "$file.$export";
                    if (Is::nemarr($uq)) $uarr[] = "?".http_build_query($uq);
                    $uri = implode("", $uarr);
                }
            } else {
                //uri 中传入的是 通过 proxyer 代理响应的 访问方式
                if ($direct === true) {
                    //当前请求是直接通过 json 路径访问，需要将 uri 的 ubase 拆分为 file 和 export 参数
                    $ubarr = explode(".", $ubase);
                    $export = array_slice($ubarr, -1)[0];
                    $file = implode(".", array_slice($ubarr, 0, -1));
                    $uq = Arr::extend($uq, [
                        "file" => $file,
                        "export" => $export
                    ]);
                    //重新拼接 uri
                    $uarr = [];
                    if (Is::nemstr($udir)) $uarr[] = $udir;
                    $uarr[] = $basename;
                    if (Is::nemarr($uq)) $uarr[] = "?".http_build_query($uq);
                    $uri = implode("", $uarr);
                }
            }

            //添加 ../ 
            $uri = "../".$uri;
        }

        //在此基础上创建新 url 实例
        return Url::mk($uri);
    }



    /**
     * 静态工具
     */

    /**
     * 判断给定的 ext 是否是 符合资源类型
     * @param String $ext 复合资源的后缀名 cdn|icon|theme ...
     * @return String|Bool 如果是复合资源，返回类全称  否则返回 false
     */
    public static function hasExt($ext)
    {
        if (!Is::nemstr($ext)) return false;
        $clsp = "module/src/resource/$ext";
        $cls = Cls::find($clsp);
        if (!class_exists($cls) || !is_subclass_of($cls, Compound::class)) return false;
        return $cls;
    }

    /**
     * 定义处理 复合资源请求的 代理响应方法，在 Src 模块中，可使用此方法，响应前端请求
     * !! Compound 子类可以覆盖此方法，实现各自特有的 响应代理方法
     * @param Array $args 前端请求 URI 数组
     * @return Mixed 
     */
    public static function responseProxyer(...$args)
    {
        if (!Is::nemarr($args) || count($args)<=1) {
            Response::insSetCode(404);
            return null;
        }

        //指向的 复合资源类 ext
        $ext = array_shift($args);
        if (($comCls = static::hasExt($ext)) === false) {
            //不存在的 复合资源 ext
            Response::insSetCode(404);
            return null;
        }

        //判断子类是否 重写了这个方法
        if (Cls::isMethodOverride($comCls, "responseProxyer", Compound::class) === true) {
            //子类重写了此方法，调用子类方法
            return $comCls::responseProxyer(...$args);
        }

        /**
         * 此处定义 通用的 复合资源请求的 代理响应方法
         * 按以下规则，对请求进行解析：
         *      https://host/src/[com-ext]/foo/bar/[res-name]/[@|latest]/[file].[export]?params... 
         * 例如：
         *      https://host/src/cdn/foo/bar/vue/[@|latest|1.0.0/]default.js?create=true 将被解析为：
         *          访问 Cdn 资源 [accessable-assets-path]/cdn/foo/bar/vue.cdn.json
         *          访问参数 ver=@&export=js&file=default&create=true
         * 
         * 针对有静态内部资源的 复合资源，可通过 inner 标记直接访问并输出 内部真实资源，例如：
         *      https://host/src/lib/foo/bar/[@|latest|1.0.0/]inner/inner-dir/inner-file.js
         *          访问 Lib 资源 [accessable-assets-path]/cdn/foo/bar.lib.json
         *          访问参数 ver=@&export=js&file=default&create=true&inner=inner-dir/inner-file
         */
        //拼接 URI
        $uri = implode("/", $args);
        if (strpos($uri, "/")===false) $uri = "/".$uri;

        //匹配到的 params 参数
        $params = [];

        if (strpos($uri, "/inner"."/")!==false) {
            //如果 uri 中包含 /inner/ 字符

            $uarr = explode("/inner"."/", $uri);
            //请求的 json 文件路径
            $cfp = $uarr[0];
            //请求的 inner 文件路径
            $params["inner"] = $uarr[1];

        } else {
            //匹配 URI 中包含的 json 文件路径，以及 file.export 参数

            preg_match_all("/(.*)\/([a-zA-Z0-9-_.]+\.[a-zA-Z0-9]+)/", $uri, $mts);
            if (!isset($mts[2]) || empty($mts[2]) || !Is::nemstr($mts[2][0])) {
                //未匹配到有效的 请求参数
                Response::insSetCode(404);
                return null;
            }
            //匹配到的 复合资源 json 文件路径
            $cfp = $mts[1][0];
            //匹配到的 请求的 file.export
            $efp = $mts[2][0];
        }

        //处理 json 文件路径中 可能包含的 version 信息
        preg_match_all("/\/(@|latest|((\.?[0-9]+\.?)+)){1}/", $cfp, $cmts);
        if (!isset($cmts[1]) || empty($cmts[1]) || !Is::nemstr($cmts[1][0])) {
            //未匹配到 version 使用默认
            $ver = "@";
        } else {
            //匹配到 version
            $ver = $cmts[1][0];
            //从 json 路径中 去除 version 信息
            $cfp = str_replace($cmts[0][0], "", $cfp);
        }
        $params["ver"] = $ver;

        if (isset($params["inner"]) && Is::nemstr($params["inner"])) {
            //如果请求的是某个 内部真是资源
            $params["file"] = static::$stdParams["file"];
            $params["export"] = static::$stdParams["export"];
        } else {
            //处理 请求的 file.export 得到 params["file"] ["export"]
            preg_match_all("/([a-zA-Z0-9-_.]+)\.([a-zA-Z0-9]+)/", $efp, $emts);
            if (!isset($emts[1]) || empty($emts[1]) || !Is::nemstr($emts[1][0])) {
                //未匹配到有效的 file 和 export 参数，使用默认
                $std = static::$stdParams;
                $file = $std["file"];
                $export = $std["export"];
            } else {
                //匹配到有效的 file 和 export 参数
                $file = $emts[1][0];
                $export = $emts[2][0];
                //如果 file 中包含 .min
                if (strpos($file, ".min")!==false) {
                    $file = str_replace(".min","", $file);
                    $params["min"] = true;
                }
            }
            
            $params["file"] = $file;
            $params["export"] = $export;
        }
        //var_dump($params);

        //查找 复合资源的 json 文件路径
        $cfp = rtrim($cfp, "/").".$ext.json";
        //var_dump($cfp);
        //尝试创建 复合资源实例
        $res = Resource::create($cfp, $params);
        if (!$res instanceof Compound) {
            //无法创建资源实例
            Response::insSetCode(404);
            return null;
        }
        //var_dump($res);

        //返回创建好的 资源实例
        return $res;

    }

}