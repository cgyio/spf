<?php
/**
 * Compound 复合资源类 子类
 * Vcom 资源类 Vue2.x 组件库资源类
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
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;

class Vcom extends Compound 
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 在父类基础上扩展
     */
    public static $stdParams = [
        
        //组件加载模式，调用 desc["loadmode"] 中定义的加载模式，默认 加载全部组件
        "mode" => "full",
        //可手动指定，要 加载|排除 的组件名，不含前缀
        "load" => [],
        "unload" => [],

        //指定主题样式，将覆盖 desc["thememode"]，将影响输出的 组件库 css 样式文件内容
        "theme" => "light",
        
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
            //获取组件库中所有 已定义组件的相关数据，保存到相应的属性中
            "GetVueComponents" => [],
            //根据参数，筛选组件列表，仅输出指定的 组件
            "FilterVueComponents" => [],
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
     * !! 在父类基础上扩展
     */
    public static $stdDesc = [

        //组件库名称 foo_bar 形式
        "name" => "",
        
        //此组件库的 JS 变量名 FooBar 形式，以插件形式使用此组件库时，此变量名 === 插件变量名
        "var" => "",

        //此组件库的 组件名称 前缀
        "prefix" => "",

        //组件库内部的 特殊文件夹名称
        "dirs" => [
            //保存 *.vue 的文件夹
            "component" => "component",
            //保存 mixin js 的文件夹
            "mixin" => "mixin",
            //保存此组件库配套插件的 文件夹
            "plugin" => "plugin",
            //保存通用文件的 文件夹
            "common" => "common",
        ],

        /**
         * 组件库中关于 组件的 参数
         */
        //必须加载的 组件名列表，不带组件名称前缀
        "required" => [],
        //定义组件加载模式
        "loadmode" => [
            //全部加载模式，必须的模式
            "full" => "full",
            //只加载必须的组件模式，必须的模式
            "mini" => "mini",
            //可自定义加载模式，提供此模式下需要加载的(除了required中组件之外的) 组件名称数组，不带组件名称前缀
            /*
            "custom" => [
                # 可以使用 foo-* 批量设置组件名
                "foo-*",
            ],
            */
        ],

        /**
         * 组件库对应的 插件 *.js 文件真实路径
         * 默认 "" 表示使用 当前组件库 [root-path]/[plugin] 文件夹下的 plugin.js 作为插件主文件
         * 其他插件 js 文件使用默认命名，例如：directive.js | global.js | instance.js | mixin.js
         * !! 可以指定其他路径下的 *.js 文件作为插件主文件，可以实现同一套组件库，使用不同的插件引入
         */
        "plugin" => "",

        /**
         * 组件库样式参数
         */
        //定义此组件库使用的 SPF-Theme 主题，指定一个有效的 *.theme.json 本地文件路径，可以不带 .theme.json 后缀名
        "theme" => "spf/assets/theme/spf",
        //默认的主题样式 light|dark 等
        "thememode" => "light",
        //指定一个组件库基础样式文件，通常是 *.scss 文件，应保存在 common 文件夹下
        "basestyle" => "base.scss",
        //指定额外的样式文件，可以有多个，按顺序合并，后面的覆盖前面的，这些文件应保存在 common 文件夹下
        "styles" => [],

        //组件库使用的 icon 图标库，指定一个有效的 *.icon.json 本地文件路径，可以不带 .icon.json 后缀名
        "iconset" => "spf/assets/icon/spf",

        //允许输出的 ext 类型数组，必须是 Mime 类中支持的后缀名类型
        "ext" => ["js","css","vue","scss"],

        /**
         * 复合资源的根路径
         *  0   针对本地的复合资源 Theme | Icon | Vcom | Lib 等类型：
         *      此参数表示 此复合资源的本地保存路径，不指定则使用当前 *.ext.json 文件的 同级同名文件夹
         *  1   针对远程复合资源 Cdn 类型：
         *      此参数表示 cdn 资源的 url 前缀，不带版本号
         * !! 指定一个 本地库文件 *.vcom.json 路径（完整路径），如果不指定，则使用当前 json 文件的路径
         */
        "root" => "",

    ];

    /**
     * 定义复合资源 内部子资源的 描述参数
     * !! 在父类基础上扩展
     */
    public static $stdSubResource = [
        //子资源类型，可以是 static | dynamic   表示   静态的实际存在的 | 动态生成的   子资源内容
        //!! Vcom 子资源默认为 dynamic 类型
        "type" => "dynamic",
    ];

    /**
     * 定义组件库中 单个组件的 描述参数
     */
    public static $stdComponent = [
        //组件文件名 不带 vue 后缀，foo_bar 形式
        "fn" => "",
        //组件名，不带前缀的 foo-bar 形式
        "name" => "",
        //组件名，完整的 带有组件库 组件名称前缀的 pre-foo-bar 形式
        "vcn" => "",
        //组件变量名，带前缀 PreFooBar 形式
        "vcv" => "",

        //保存 *.vue 文件的真实文件路径
        "file" => "",
        //此组件对应的 *.scss 样式文件，真实文件路径，可以有多个，最终输出时，将依次合并
        "scss" => "",
    ];

    /**
     * 此组件库关联的 资源实例
     */
    //使用的 主题资源实例
    public $theme = null;
    //使用的 图标库资源实例
    public $icon = null;

    //组件库中所有组件的 数据缓存
    public $comps = [
        /*
        "不带前缀的组件名" => [
            # 单个组件的描述参数，数据格式与 stdComponent 一致
        ],
        */
    ];

    //当前请求 经过筛选后的 需要最终加载的组件名数组，不带前缀的组件名
    public $compns = [];


    
    /**
     * 资源实例内部定义的 stage 处理方法
     * !! 覆盖父类
     * @param Array $params 方法额外参数
     * @return Bool 返回 false 则终止 当前阶段 后续的其他中间件执行
     */
    //GetVueComponents 获取此组件库中所有已定义的 *.vue 组件，保存到 comps 属性，并缓存
    public function stageGetVueComponents($params=[])
    {
        //首先尝试读取缓存
        $cacheEnabled = $this->cacheEnabled();
        $cacheIgnored = $this->cacheIgnored();
        if ($cacheEnabled && !$cacheIgnored) {
            //读取缓存
            $comps = $this->cacheGetComponents();
            //读取到缓存内容，保存到 comps 属性
            if (Is::nemarr($comps)) {
                $this->comps = $comps;
                return true;
            }
        }
        
        //未启用缓存，或缓存文件还未创建，开始生成 components 列表
        $comps = $this->resAllComps();
        //未获取到则报错
        if (!Is::nemarr($comps)) {
            //不存在组件文件夹，报错
            throw new SrcException("组件库 ".$this->resBaseName()." 无法获取已定义的组件列表", "resource/getcontent");
        }

        //依次处理获取到的 *.vue 组件，生成 comps 参数
        $pre = $this->desc["prefix"];
        $std = static::$stdComponent;
        $ncomps = [];
        foreach ($comps as $compn => $compf) {
            //文件名 foo_bar.vue  -->  foo-bar
            $compk = str_replace("_","-",$compn);
            //$vf = $compp;
            $pi = pathinfo($compf);
            $fn = $pi["filename"];
            //去除 compk 中可能存在的 vg-foo-foo --> vg-foo
            $compk = str_replace("$fn-$fn", $fn, $compk);
            //附加 组件名前缀
            $vcn = "$pre-$compk";
            //转为 变量名形式
            $vcv = Str::camel($vcn, true);
            
            //生成组件参数，标准格式
            $compc = Arr::extend($std, [
                "fn" => $fn,
                "name" => $compk,
                "vcn" => $vcn,
                "vcv" => $vcv,
                "file" => $compf,
            ]);

            //对应的 scss 文件应保存在 同路径下，同名 scss 文件
            $scf = $pi["dirname"].DS.$fn.".scss";
            //如果不存在 scss，则向上查找一级，例如：comps/table/row.scss 不存在 则查找 comps/table/table.scss
            if (!file_exists($scf)) {
                $spi = pathinfo($pi["dirname"]);
                $scf = $spi["dirname"].DS.$spi["filename"].DS.$spi["filename"].".scss";
                if (!file_exists($scf)) $scf = "";
            }
            if (Is::nemstr($scf)) $compc["scss"] = $scf;
            
            //保存
            $ncomps[$compk] = $compc;
        }

        //保存到 comps 属性
        $this->comps = $ncomps;

        //写入缓存
        if ($cacheEnabled) {
            $this->cacheSaveComponents();
        }

        return true;
    }
    //FilterVueComponents 根据请求参数 mode|load|unload 筛选要最终输出的组件列表，保存到 compns 属性
    public function stageFilterVueComponents($params=[])
    {
        $desc = $this->desc;
        $ps = $this->params;
        $comps = $this->comps;

        //必须加载的组件
        $required = $desc["required"] ?? [];
        if (!Is::nemarr($required)) $required = [];
        if (Is::nemarr($required)) $required = $this->fixCompns($required);

        //加载模式
        $mode = $ps["mode"] ?? "full";
        if (!Is::nemstr($mode)) $mode = "full";
        $modes = $desc["loadmode"] ?? [];
        if (!isset($modes[$mode])) $mode = "full";
        //按模式 生成需要加载的组件列表
        switch ($mode) {
            //完整加载
            case "full":
                $modeloads = array_diff(array_keys($comps), $required);
                break;

            //最小加载
            case "mini":
                $modeloads = [];
                break;

            //自定义加载模式
            default:
                $modeloads = $modes[$mode];
                if (!Is::nemarr($modeloads)) $modeloads = [];
                if (Is::nemarr($modeloads)) $modeloads = $this->fixCompns($modeloads);
                break;
        }

        //手动加载的组件
        $loads = $ps["load"] ?? [];
        if (Is::nemstr($loads)) $loads = Arr::mk($loads);
        if (!Is::nemarr($loads)) $loads = [];
        if (Is::nemarr($loads)) $loads = $this->fixCompns($loads);

        //手动排除的组件
        $unloads = $ps["unload"] ?? [];
        if (Is::nemstr($unloads)) $unloads = Arr::mk($unloads);
        if (!Is::nemarr($unloads)) $unloads = [];
        if (Is::nemarr($unloads)) $unloads = $this->fixCompns($unloads);

        //合并要加载的组件，并去重
        $compns = array_merge($required, $modeloads, $loads);
        $compns = array_merge(array_flip(array_flip($compns)));

        //排除组件
        $compns = array_diff($compns, $unloads);
        if (!Is::nemarr($compns)) {
            //最终输出组件不能为空
            throw new SrcException("组件库 ".$this->resBaseName()." 的输出组件列表不能为空", "resource/getcontent");
        }

        //var_dump($compns);exit;
        //保存
        $this->compns = $compns;

        return true;
    }



    /**
     * 工具方法 解析复合资源内部 子资源参数，查找|创建 子资源内容
     * 根据  子资源类型|子资源ext|子资源文件名  分别制定对应的解析方法
     * !! Compound 子类可覆盖此方法
     * @return $this
     */
    //生成 所有要加载组件的 js 定义代码，包含插入样式的代码，以 ESM 形式导出 { 'sv-button': defineSvButton, ... } 外部需要使用 Vue.component() 注册组件
    protected function createDynamicJsSubResource()
    {
        //所有组件
        $comps = $this->comps;
        //筛选后需要最终加载的组件名数组
        $compns = $this->compns;

        //用于 js 子资源的实例化参数
        $params = [
            "ext" => "js",
            "belongTo" => $this,
            "ignoreGet" => false,
            //合并参数，用于合并所有组件的 js 代码
            "merge" => [
                //直接传入 临时 js 资源实例，其 content 来自 *.vue 资源实例 export=js 的输出
            ],
            //esm 导出语句手动处理
            "esm" => "keep",
        ];
        //esm 导出语句数组，用于生成最终的 esm 导出语句
        $esmrows = ["export default {"];
        foreach ($compns as $compn) {
            if (!isset($comps[$compn]) || !Is::nemarr($comps[$compn])) continue;
            $compc = $comps[$compn];
            //创建关联 *.vue 资源实例，设置其 export 输出 ext 为 js
            $vres = Resource::create($compc["file"], [
                "belongTo" => $this,
                "ignoreGet" => true,
                "export" => "js",
                //需要生成 样式注入 代码
                "inject" => true,
            ]);
            if (!$vres instanceof Vue) continue;
            $vjs = $vres->export(["return" => true]);

            //创建临时 js 资源实例
            $jsres = Resource::manual(
                $vjs,
                "$compn.js",
                [
                    "ext" => "js",
                    "belongTo" => $this,
                    "ignoreGet" => true,
                    "export" => "js",
                    //import 语句已在 vue 资源中处理过，此处保持不变
                    "import" => "keep",
                    //esm 导出语句统一处理，此处不处理
                    "esm" => false,
                ]
            );
            if (!$jsres instanceof Js) continue;
            //添加到 merge 参数
            $params["merge"][] = $jsres;
            //添加到 esm 导出语句
            $esmrows[] = "'".$compc["vcn"]."': ".$vres->vueCompName["def"].",";

            //释放 临时 vue 资源实例
            unset($vres);
        }

        //创建临时 js 实例，用于最终输出，附加到当前组件库 subResource 属性
        $js = Resource::manual(
            "",
            $this->resExportBaseName(),
            $params
        );
        if (!$js instanceof Js) {
            //创建 js 临时实例失败
            throw new SrcException("组件库 ".$this->resBaseName()." 无法创建要输出的 JS 资源实例", "resource/getcontent");
        }

        //手动处理 esm 导出语句
        $esmrows[] = "}";
        $rower = $js->RowProcessor;
        $rower->rowEmpty(3);
        $rower->rowComment(
            "导出组件定义参数", 
            "外部需要使用 Vue.component(key, val) 注册语句来依次注册组件", 
            "不要手动修改"
        );
        $rower->rowEmpty(1);
        $rower->rowAdd($esmrows);
        $rower->rowEmpty(1);

        //保存到 subResource 属性
        $this->subResource = $js;

        return $this;

    }
    //生成 使用 ESM 导出的插件定义代码，所有要加载组件都被定义为全局组件，外部只需要 use 此插件即可完成环境准备
    protected function createDynamicJsEsmBrowserSubResource()
    {
        $desc = $this->desc;
        //获取插件 js 路径
        $plugin = $desc["plugin"];
        if (!Is::nemstr($plugin)) {
            //未指定插件文件，使用默认位置的 js 文件
            $plf = $this->resSpecDir("plugin/plugin.js", true);
        } else {
            //指定了其他插件 js
            $plf = Path::find($plugin, Path::FIND_FILE);
        }
        if (!Is::nemstr($plf) || !file_exists($plf)) {
            //插件文件不存在，报错
            throw new SrcException("组件库 ".$this->resBaseName()." 无法获取有效的插件 JS 文件", "resource/getcontent");
        }

        //插件真实路径转为 url
        $plurl = Url::src($plf, true);

        //开始创建临时 js 资源实例
        $js = Resource::manual(
            "",
            $this->resExportBaseName(),
            [
                "ext" => "js",
                "belongTo" => $this,
                "ignoreGet" => false,
                //import 语句保持原样
                "import" => "keep",
                //esm 语句保持原样
                "esm" => "keep",
            ]
        );
        if (!$js instanceof Js) {
            //创建 js 临时实例失败
            throw new SrcException("组件库 ".$this->resBaseName()." 无法创建要输出的 JS 资源实例", "resource/getcontent");
        }

        /**
         * 开始调用 RowProcessor 创建 js 代码
         */
        $rower = $js->RowProcessor;
        //当前组件库的 var
        $var = $desc["var"];

        //import 语句
        //import 组件库导出 js
        $dftJsUrl = $this->resUrlMk("../foo/default.js");
        $vcv = $var."Comps";
        var_dump($dftJsUrl);exit;
        $rower->rowAdd("import $vcv from '".$dftJsUrl->full."';","");
        //import 插件 js 文件
        $rower->rowAdd("import $var from '$plurl';","");
        //注册组件
        $rower->rowAdd("$vcv.forEach((def, compn) => {Vue.component(compn, def)});","");
        //应用插件
        //$rower->rowAdd("Vue.use($var)","");
        //esm 导出插件
        $rower->rowAdd("export default $var;","");

        //保存到 subResource
        $this->subResource = $js;

        return $this;
    }



    /**
     * 工具方法 初始化复合资源 desc 工具
     * !! 覆盖父类
     */

    /**
     * 将 desc 中指定的 root 参数，更新到资源实例中
     * @return $this
     */
    protected function initRoot()
    {
        /**
         * 本地库 root 必须是本地 *.vcom.json 文件路径（完整的 包含 json 文件名的 路径）
         * 如果不指定，只是用当前 json 的文件路径
         */
        $desc = $this->desc;
        $root = $desc["root"] ?? null;

        if (!Is::nemstr($root)) {
            //未指定，则使用当前的 json 文件路径
            $root = $this->real;
            $this->desc["root"] = $root;
        } else {
            //指定了 root，确保其有效
            if ($this->PathProcessor->isRemote($root) === true) {
                //指定了一个 url
                throw new SrcException("当前组件库 ".$this->resBaseName()." 未设置有效的描述文件路径", "resource/getcontent");
            }
            //Path::find 处理
            $rootp = Path::find($root, Path::FIND_FILE);
            if (!Is::nemstr($rootp)) {
                //指定了一个不存在 文件路径
                throw new SrcException("当前组件库 ".$this->resBaseName()." 未设置有效的描述文件路径", "resource/getcontent");
            }
            
            //使用 root 替换当前的 real
            $this->real = $rootp;
            $this->desc["root"] = $rootp;
        }

        return $this;
    }



    /**
     * 工具方法 缓存处理工具
     */

    /**
     * 单独 读取缓存 components.json 组件库中所有已定义的 *.vue 组件数据
     * @return Array|null
     */
    public function cacheGetComponents()
    {
        if ($this->cacheEnabled() !== true) return null;

        //components.json 缓存文件
        $cfp = $this->cachePath("components.json", true);
        if (!Is::nemstr($cfp)) return null;

        //读取
        $json = file_get_contents($cfp);
        $comps = Conv::j2a($comps);
        if (!Is::nemarr($comps)) return null;
        return $comps;
    }

    /**
     * 单独 写入 components.json 组件列表缓存
     * @return Bool
     */
    public function cacheSaveComponents()
    {
        if ($this->cacheEnabled() !== true) return false;
        //要缓存的 实例属性
        $comps = $this->comps;
        if (!Is::nemarr($comps)) return false;
        $json = Conv::a2j($comps);
        //缓存文件
        $cfp = $this->cachePath("components.json", false);
        //写入缓存
        return Path::mkfile($cfp, $json);
    }



    /**
     * 工具方法 getters 资源信息获取
     */

    /**
     * 获取当前组件库的 相关信息 通常用于组件库内部某个 组件 *.vue 资源实例内部获取组件库信息
     * @return Array
     */
    public function resVcomInfo()
    {
        $info = [
            "desc" => $this->desc,
            //组件名前缀
            "pre" => $this->desc["prefix"],
            //所有已定义的组件名数组，不带前缀
            "compns" => array_keys($this->comps),
            //特殊路径的 文件夹名
            "dirnames" => $this->desc["dirs"],
            //特殊路径的 真实文件夹路径
            "dirs" => [],
            //特殊路径的 外部访问 url 前缀
            "urls" => [],
        ];

        //特殊路径
        $dirs = $this->desc["dirs"];
        foreach ($dirs as $dir => $rdir) {
            //真是文件夹路径
            $rdp = $this->resSpecDir($dir, false);
            //转为 url
            $rurl = Url::src($rdp, true);

            $info["dirs"][$dir] = $rdp;
            $info["urls"][$dir] = $rurl;
        }
        
        return $info;
    }

    /**
     * 获取组件库内部特殊文件夹 下的文件路径
     * @param String $path 要查询的文件路径，一定以 desc["dirs"] 中定义的特殊文件夹名 开始，如：component/foo/bar.vue 或 mixin/base.js
     * @param Bool $exists 是否检查路径存在性，默认 false
     * @return String|null
     */
    public function resSpecDir($path, $exists=false)
    {
        if (!Is::nemstr($path)) return null;
        $parr = explode("/", trim($path, "/"));
        //特殊文件夹名
        $dirn = array_shift($parr);
        //实际文件夹名
        $dirs = $this->desc["dirs"] ?? [];
        $rdirn = $dirs[$dirn] ?? null;
        if (!Is::nemstr($rdirn)) return null;
        //拼接路径
        array_unshift($parr, $rdirn);
        $p = implode("/", $parr);
        //查找 并返回
        return $this->PathProcessor->inner($p, $exists);
    }

    /**
     * 递归收集组件库 component 文件夹下的所有 *.vue 文件，汇总为 一维数组
     * @return Array
     */
    public function resAllComps()
    {
        //component 文件夹
        $compp = $this->resSpecDir("component", true);
        if (!Is::nemstr($compp)) return [];
        
        //递归收集 文件夹下所有 *.vue 单文件组件
        $glue = "-";
        $comps = Path::flat($compp, "", $glue, "vue");
        if (!Is::nemarr($comps)) return [];
        return $comps;
    }



    /**
     * 工具方法
     */

    /**
     * 处理 配置或参数中 加载|排除 的组件名列表中的 foo-* 形式的 组件名，将其转换为 foo,foo-bar,foo-bar-jaz,... 列表形式
     * 例如：在 params 中指定要手动排除的组件  params["unload"] = ["logo", "el-*"]  将被转换为：
     *      [ "logo", "el-input", "el-select", "el-tag", ... ]
     * !! 所有已定义的组件列表 $this->comps 必须已创建
     * @param Array $compns 指定的组件名数组
     * @return Array 转换后的组件名列表
     */
    protected function fixCompns($compns=[])
    {
        if (!Is::nemarr($compns)) return $compns;
        //所有组件
        $comps = $this->comps;
        //所有组件名
        $compnall = array_keys($comps);

        //处理 foo-*
        $ncompns = [];
        foreach ($compns as $compn) {
            if (substr($compn, -2)!=="-*") {
                $ncompns[] = $compn;
                continue;
            }
            //组件名前缀
            $cpre = substr($compn, 0, -2);
            //前缀长度 +1
            $cprelen = strlen($cpre)+1;
            //从所有组件名中 匹配复合的 组件名
            foreach ($compnall as $compni) {
                if ($compni === $cpre) {
                    //组件名 === 前缀
                    $ncompns[] = $compni;
                } else if (substr($compni, 0, $cprelen) === "$cpre-") {
                    //组件名 === 前缀-foo-bar-...
                    $ncompns[] = $compni;
                }
            }
        }

        return $ncompns;
    }

    /**
     * 
     */

    /**
     * 根据传入的 *.vue 文件路径，获取对应的 $this->comps 中保存的组件参数
     * 此方法通常由 组件库中某个组件的 *.vue 文件资源实例内部调用
     * !! 所有已定义的组件列表 $this->comps 必须已创建
     * @param String $vueFilePath 真实存在的 *.vue 文件路径
     * @return Array|null 
     */
    public function getCompInfoByVuePath($vueFilePath)
    {
        if (!Is::nemstr($vueFilePath) || !file_exists($vueFilePath)) return null;
        
        //在所有组件中查找
        foreach ($this->comps as $compn => $compc) {
            if ($compc["file"] === $vueFilePath) {
                return $compc;
            }
        }
        return null;
    }
}