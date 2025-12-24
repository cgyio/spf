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
use Spf\View;
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

        //可手动指定 组件库组件名称前缀，覆盖 desc["prefix]
        "prefix" => "",

        //指定主题样式，将覆盖 desc["thememode"]，将影响输出的 组件库 css 样式文件内容
        "theme" => "light",

        //是否加载 common/ 问价夹下的所有 scss|js 文件，默认 all 可选 none 或 指定要加载的 文件名 foo,bar.js,jaz.scss
        "extra" => "all",

        //合并外部指定的 scss|js 资源，可指定 资源实例 或通过 url 指定资源路径，可以是 本地|远程 资源路径
        "combine" => [],

        //显示 scss 文件时 是否包含 dart-sass-patch 内容
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
            //compound 资源的主文件是 json 读取其内容
            "GetJsonContent" => [],
            //compound 资源初始化，读取 desc 资源描述数据，生成对应参数，并缓存到资源实例
            "InitCompoundDesc" => [],
            //获取组件库依赖的 其他 资源实例 如：Vue 库资源|Theme|Icon 等
            "GetDependResource" => [],
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
        //如果此组件库是 业务组件库，保存所属基础组件库的 prefix，如果是基础组件库 则与 prefix 相同
        "basePrefix" => "",
        //样式 scss|css|js 中代替 prefix|basePrefix 的字符串模板
        "pretemp" => [
            //"__TPL__" => "在 desc 中的 key 或 keypath"
            //prefix
            "__BPRE__"  => "basePrefix",
            "__PRE__"   => "prefix",
            "BPRE@"     => "basePrefix",
            "PRE@"      => "prefix",
        ],

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
         * 组件库使用的样式文件
         */
        "styles" => [
            //基础样式文件，通常是 *.scss 文件，应保存在 common 文件夹下，如果不指定默认使用 common/base.scss
            "base" => "base.scss",
            //要额外合并的 其他本地样式文件 scss|css 文件，按顺序合并，后面的覆盖前面的，这些文件应保存在 common 文件夹下
            "extra" => [
                
            ],

        ],



        /**
         * 组件库依赖的其他资源
         */

        /**
         * !! 必须指定组件库依赖的 Vue 库
         */
        "vue" => [
            //!! 必须指定一个本地的 *.cdn.json(远程库) 或 *.lib.json(本地库) 文件路径，文件后缀名不可省略
            "file" => "spf/assets/cdn/vue.cdn.json",
            //资源的实例化参数
            "params" => [
                //指定 vue 版本，当前组件库资源是 Vue2.x 组件库，因此使用的是 默认 2.7.16 版本
                "ver" => "@",
            ],
            //指定 Vue 库的输出文件，这些 file 在 *.cdn.json 中定义了
            "export" => [
                //普通 js
                "vue.js" => [
                    //输出 js
                    "export" => "js",
                    //当前为 dev 版本，可后期修改为 生产环境版本
                    "file" => "dev-browser",
                ],
                //esm js
                "esm-vue.js" => [
                    //输出 js
                    "export" => "js",
                    //当前为 dev 版本，可后期修改为 生产环境版本
                    "file" => "dev-esm-browser",
                ],
            ],

            /**
             * 此组件库依赖的 第三方 UI 库，例如：element-ui
             */
            "ui" => [
                //基础组件库 默认依赖 element-ui
                /*
                "element-ui" => [
                    #!! 必须指定 一个或多个 本地的 *.cdn.json(远程库) 或 *.lib.json(本地库) 文件路径，文件后缀名不可省略
                    "file" => "spf/assets/cdn/element-ui.cdn.json",
                    # 资源实例化参数
                    "params" => [
                        "ver" => "@",
                    ],
                    # 指定 UI 库的输出文件，这些 file 在 *.cdn.json 中定义了
                    "export" => [
                        # 普通 js
                        "ui.js" => [
                            # 输出 js
                            "export" => "js",
                            # 默认输出普通 js
                            "file" => "default",
                        ],
                        # esm js
                        "esm-ui.js" => [
                            # 输出 js
                            "export" => "js",
                            # 默认输出 esm js
                            "file" => "esm-browser",
                        ],
                        # css 随着 $theme->themeMode["color"] 变化
                        "ui.css" => [
                            # $theme->themeMode["color"] == light
                            "light" => [
                                "export" => "css",
                                "file" => "light",
                            ],
                            # $theme->themeMode["color"] == dark
                            "dark" => [
                                "export" => "css",
                                "file" => "dark",
                            ],
                        ],
                    ],
                ],
                */
            ],
        ],

        /**
         * 组件库依赖的 SPF-Theme 主题资源
         * !! 只有指定了依赖的 SPF-Theme 主题参数 的 vcom 才能作为 前端 SPA 环境的 基础组件库
         */
        "theme" => [
            //启用标记，默认启用
            "enable" => true,
            //!! 必须指定一个本地的 *.theme.json 文件真实路径，文件后缀名可以省略
            "file" => "spf/assets/theme/spf",
            //资源的实例化参数
            "params" => [
                //主题模式，可通过 params["theme"] 手动调整
                "mode" => "light",  //默认值 与 params["theme"] 默认值一致
            ],
        ],

        /**
         * 组件库使用的 icon 图标库，可以有多个
         * !! 可以指定一个或多个 本地 *.icon.json 文件真实路径，文件后缀名可以省略
         */
        "iconset" => [
            //"spf/assets/icon/md-round",
            //"spf/assets/icon/md-sharp",
            //"spf/assets/icon/md-fill",
            //"spf/assets/icon/spinner",
        ],
        


        //是否启用版本控制
        "enableVersion" => true,
        //指定可以通过 @|latest 访问的 版本号
        "version" => [
            //组件库 默认版本号
            "@" => "1.0.0",
            "latest" => "1.0.0",
        ],

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

        /**
         * 组件库 根据默认版本，指定子资源
         * 可在 *.vcom.json 文件中手动覆盖
         */
        "content" => [
            "1.0.0" => [
                "js" => [
                    /**
                     * 所有要加载组件的 js 定义代码，以 ESM 形式导出 { 'sv-button': defineSvButton, ... } 
                     * 外部需要使用 Vue.component() 注册组件
                     */
                    "default" => [],
                    /**
                     * 将所有要加载组件定义为异步组件形式 esm 形式导出 { 'sv-button': ()=>{import(/src/foo/bar.vue?export=js)} }
                     * 外部需要使用 Vue.component() 注册组件
                     */
                    "async-default" => [],
                    //使用 ESM 导出的插件定义代码，所有要加载组件都被定义为全局组件，外部只需要 use 此插件即可完成环境准备
                    "esm-browser" => ["fix" => ["VcomPrefix"],],
                    //与 esm-browser 区别在于：所有组件都被定义为 异步组件形式
                    "esm-browser-async" => ["fix" => ["VcomPrefix"],],
                    //组件库使用的 一个或多个 图标库资源对应的 创建雪碧图的 JS 代码文件
                    "iconset" => [],
                    //输出 Vue 库的 js
                    "vue" => [],
                    "esm-vue" => [],
                    //输出依赖的 SPF-Theme 主题资源实例的 主题样式参数 js 代码 esm 导出
                    "cssvar" => [],
                    //输出依赖的 第三方 UI 库的 js
                    "ui" => [],
                    "esm-ui" => [],
                ],
                "css" => [
                    //将 default.scss 编译为 css 输出
                    "default" => [
                        "fix" => ["VcomPrefix"],
                    ],
                    //将 browser.scss 编译为 css 输出
                    "browser" => [
                        "fix" => ["VcomPrefix"],
                    ],
                    //组件库使用的 一个或多个 图标库资源对应的 css 样式文件
                    "iconset" => [],
                    //输出依赖的 第三方 UI 库的 css 样式
                    "ui" => [],
                ],
                "scss" => [
                    //组件库基础样式，合并了 所有要加载的各 *.vue 组件的外部 scss 以及指定的 extra scss 文件 得到的 scss 内容
                    "default" => [
                        "fix" => ["VcomPrefix"],
                    ],
                    /**
                     * 组件库对外输出的 完整的 scss 内容，包括：
                     *      主题样式，组件库基础样式，要加载的 各 *.vue 组件的外部 scss，
                     *      extra 参数指定的 common/ 文件夹下的 scss，
                     *      combine 参数指定的 外部 scss 资源
                     * 返回 合并后的 scss
                     */
                    "browser" => [
                        "fix" => ["VcomPrefix"],
                    ],
                ],
                /*"vue" => [
                    //输出组件库中某个组件的原始 vue 代码
                    "default" => [],
                ],*/
            ]
        ],

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
     * 此组件库 关联的|依赖的 资源实例
     */
    //依赖的 Vue 远程|本地 库资源实例
    public $vue = null;
    //依赖的 第三方 UI 库 可以有多个
    public $ui = [];
    //使用的 主题资源实例
    public $theme = null;
    //使用的 图标库资源实例
    public $icon = [];

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
    //!! 覆盖父类 InitCompoundDesc 初始化此复合资源，生成 desc 资源描述参数，保存到 desc 属性 
    public function stageInitCompoundDesc($params=[])
    {
        //调用父类方法
        parent::stageInitCompoundDesc($params);

        //使用 params["prefix"] 覆盖 desc["prefix"]
        $pre = $this->params["prefix"] ?? null;
        if (Is::nemstr($pre) && $this->desc["prefix"] !== $pre) {
            //基础组件库 和 业务组件库 有不同的 prefix 替换逻辑
            if ($this->resIsBaseVcom() === true) {
                //在一个 SPA 应用中 只会使用一个基础组件库，因此直接替换
                $this->desc["prefix"] = $pre;
                $this->desc["basePrefix"] = $pre;
            } else {
                /**
                 * !! 业务组件库 将传入的 prefix 附加到原有 prefix 前面，以防出现 组件同名问题
                 * 例如：某个 SPA 使用了 2 个业务组件库 pms,oms 其中都包含 page-index 组件：
                 *      使用原始的 prefix 后 组件名分别为：pms-page-index,oms-page-index
                 *      如果传入了统一的 prefix 组件库前缀：sv 则组件名都会变为：sv-page-index
                 *      因此需要将后传入的 prefix 与原有的 prefix 合并，之后即可变为：sv-pms-page-index, sv-oms-page-index
                 */
                $this->desc["prefix"] = $pre."-".$this->desc["prefix"];
                //保存基础组件库 prefix
                $this->desc["basePrefix"] = $pre;
            }
        }

        //使用 params["theme"] 覆盖 desc["theme"]["params"]["mode"]
        if ($this->desc["theme"]["enable"] === true) {
            $thmode = $this->params["theme"] ?? null;
            $thc = $this->desc["theme"]["params"]["mode"];
            if (Is::nemstr($thmode) && $thmode !== $thc) {
                $this->desc["theme"]["params"]["mode"] = $thmode;
            }
        }

        return true;
    }
    //GetDependResource 获取此组件库依赖的 其他 库资源实例
    public function stageGetDependResource($params=[])
    {
        //创建 vue 资源实例
        $vueres = $this->resVueResource();
        if (is_null($vueres) || !$vueres instanceof Compound) {
            //依赖的 Vue 库资源未找到
            throw new SrcException("组件库 ".$this->resBaseName()." 无法获取依赖的 Vue 库资源实例", "resource/getcontent");
        }
        /**
         * !! 当前类型的 组件库 必须使用 2.x 版本的 vue 库
         */
        $vver = $vueres->resVersion();
        if (!strpos($vver, ".") || substr($vver, 0, 2)!=="2.") {
            //依赖的 Vue 库的版本不正确
            throw new SrcException("组件库 ".$this->resBaseName()." 必须使用 Vue2.x 版本", "resource/getcontent");
        }
        //保存
        $this->vue = $vueres;

        //创建依赖的 第三方 UI 库资源实例
        $uis = $this->resUiResource();
        if (Is::nemarr($uis)) $this->ui = $uis;

        //创建 Theme 主题资源实例
        if ($this->desc["theme"]["enable"] === true) {
            $thres = $this->resThemeResource();
            if (is_null($thres) || !$thres instanceof Compound) {
                //依赖的 Theme 主题资源未找到
                throw new SrcException("组件库 ".$this->resBaseName()." 无法获取依赖的主题资源实例", "resource/getcontent");
            }
            //保存
            $this->theme = $thres;
        }

        //创建 Icon 图标库资源实例数组
        $isets = $this->resIconResource();
        if (Is::nemarr($isets)) $this->icon = $isets;

        return true;
    }
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
        //当前是否基础组件库
        $isBase = $this->resIsBaseVcom();
        //获取 component 文件夹下的所有定义的组件
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
        //var_dump($compns);
        //var_dump($unloads);

        //排除组件
        $compns = array_diff($compns, $unloads);
        if (!Is::nemarr($compns)) {
            //最终输出组件不能为空
            throw new SrcException("组件库 ".$this->resBaseName()." 的输出组件列表不能为空", "resource/getcontent");
        }

        //var_dump($compns);exit;
        //保存
        $this->compns = array_merge([],$compns);

        return true;
    }



    /**
     * 工具方法 解析复合资源内部 子资源参数，查找|创建 子资源内容
     * 根据  子资源类型|子资源ext|子资源文件名  分别制定对应的解析方法
     * !! 覆盖父类
     * @return $this
     */
    //生成 所有要加载组件的 js 定义代码，以 ESM 形式导出 { 'sv-button': defineSvButton, ... } 外部需要使用 Vue.component() 注册组件
    protected function createDynamicJsDefaultSubResource()
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
            $vres = Resource::create($compc["file"], $this->fixSubResParams([
                "belongTo" => $this,
                "ignoreGet" => true,
                "export" => "js",
                //!! 不包含单个组件内部样式的 注入代码，组件内部样式通过 browser.scss|css 统一引入
                "inject" => false,
            ]));
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
            $this->resTempResName("js"),
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
    //生成 所有要加载组件定义为异步组件形式 esm 导出 { 'sv-button': ()=>{import(/src/foo/bar.vue?export=js)}, ... } 外部需要使用 Vue.component() 注册组件
    protected function createDynamicJsAsyncDefaultSubResource()
    {
        //所有组件
        $comps = $this->comps;
        //筛选后需要最终加载的组件名数组
        $compns = $this->compns;

        //desc
        $desc = $this->desc;
        
        //esm 导出语句数组，用于生成最终的 esm 导出语句
        $esmrows = ["export default {"];
        foreach ($compns as $compn) {
            if (!isset($comps[$compn]) || !Is::nemarr($comps[$compn])) continue;
            $compc = $comps[$compn];
            //组件 *.vue 文件真实路径
            $cvf = $compc["file"];
            //转为 url 完整形式
            $cvu = Url::src($cvf, true);
            //附加 请求参数
            $qs = [
                "export" => "js",
                "prefix" => $desc["prefix"],
                //如果此组件库是 业务组件库，需要提供不同的 basePrefix
                "basepre" => $desc["basePrefix"],
                //自动注册
                "regist" => false,
                //不注入样式
                "inject" => false,
                //esm 导出
                "esm" => true,
            ];
            //将组件库特殊文件夹名也传递进来
            $dirs = $desc["dirs"];
            foreach ($dirs as $dk => $dv) {
                //只传递与默认值不同的
                if ($dk === $dv) continue;
                $qs["dir_".$dk] = $dv;
            }
            //转为 queryString
            $qs = Conv::a2u($qs);
            //完整的 异步组件形式的 url
            $cvu = $cvu."?".$qs;

            //js 代码
            $esmrows[] = "'".$compc["vcn"]."': () => import('".$cvu."'),";
        }
        $esmrows[] = "}";

        //创建 subResource
        $this->subResource = Resource::manual(
            implode("\r", $esmrows),
            $this->resExportBaseName(),
            [
                "ext" => "js",
                "belongTo" => $this,
                "ignoreGet" => false,
                //esm 导出语句保持原样
                "esm" => "keep",
            ]
        );

        return $this;
    }
    //生成 使用 ESM 导出的插件定义代码，所有要加载组件都被定义为全局组件，外部只需要 use 此插件即可完成环境准备
    protected function createDynamicJsEsmBrowserSubResource()
    {
        //创建
        $this->subResource = $this->commonCreateDynamicJsEsmBrowserMethod(false);
        return $this;
    }
    //与 esm-browser 区别在于：所有组件都被定义为 异步组件形式
    protected function createDynamicJsEsmBrowserAsyncSubResource()
    {
        //创建
        $this->subResource = $this->commonCreateDynamicJsEsmBrowserMethod(true);
        return $this;
    }
    //生成 组件库使用的 一个或多个 图标库资源对应的 创建雪碧图的 JS 代码文件
    protected function createDynamicJsIconsetSubResource()
    {
        $isets = $this->icon ?? [];
        if (!Is::nemarr($isets)) $isets = [];
        //创建各 图标库资源实例
        $isets = array_map(function($iset) {
            $isetres = $iset->clone([
                //输出 js
                "export" => "js",
                //输出文件
                "file" => "default",
            ]);
            if (!$isetres instanceof Icon) return null;
            //创建此 图标库的 js 临时资源
            $isetjs = Resource::manual(
                $isetres->export(["return"=>true]),
                $this->resTempResName("js"),    //$isetres->resBaseName().".js",
                [
                    "ext" => "js",
                    "export" => "js",
                    "ignoreGet" => true,
                    "esm" => "keep"
                ]
            );
            if (!$isetjs instanceof Codex) return null;
            //释放临时 图标库资源
            unset($isetres);
            return $isetjs;
        }, $isets);
        //剔除不能成功实例化的 图标库 js 资源
        $isets = array_filter($isets, function($isetres) {
            return $isetres instanceof Codex;
        });

        //手动创建要输出的 js 子资源实例
        $js = Resource::manual(
            "",
            $this->resExportBaseName(),
            [
                "ext" => "js",
                //"belongTo" => $this,
                "ignoreGet" => false,
                //merge 合并参数
                "merge" => $isets
            ]
        );
        if (!$js instanceof Codex) {
            //创建 css 资源实例失败
            throw new SrcException("组件库 ".$this->resBaseName()." 无法创建要输出的图标库 JS 资源实例", "resource/getcontent");
        }
        
        //保存为 subResource
        $this->subResource = $js;

        //释放临时资源
        unset($isets);

        return $this;
    }
    //生成 组件库依赖的 Vue 库的 js 非 esm 形式
    protected function createDynamicJsVueSubResource()
    {
        //创建
        $this->subResource = $this->commonCreateDynamicJsVueMethod(false);
        return $this;
    }
    //生成 组件库依赖的 Vue 库的 js esm 形式
    protected function createDynamicJsEsmVueSubResource()
    {
        //创建
        $this->subResource = $this->commonCreateDynamicJsVueMethod(true);
        return $this;
    }
    //生成 组件库依赖的 SPF-Theme 主题资源实例的 样式变量的 js 定义代码
    protected function createDynamicJsCssvarSubResource()
    {
        //依赖的 theme 资源实例
        $theme = $this->theme;
        if (!$theme instanceof Theme) {
            //当前组件库未定义 依赖的 SPF-Theme 库参数，输出空内容
            $this->subResource = $this->tempRes("js");
            return $this;
        }

        //clone theme 资源实例
        $tho = $theme->clone([
            //输出 js
            "export" => "js",
            "import" => false,
            "merge" => "",
        ]);

        //保存
        $this->subResource = $tho;
        return $this;
    }
    //生成 依赖的 第三方 UI 库的 js 非 esm
    protected function createDynamicJsUiSubResource()
    {
        //创建
        $this->subResource = $this->commonCreateDynamicJsUiMethod(false);
        return $this;
    }
    //生成 依赖的 第三方 UI 库的 esm-js
    protected function createDynamicJsEsmUiSubResource()
    {
        //创建
        $this->subResource = $this->commonCreateDynamicJsUiMethod(true);
        return $this;
    }
    //default.scss 编译为 css
    protected function createDynamicCssDefaultSubResource()
    {
        //先生成 scss 资源实例 保存在 subResource
        $this->createDynamicScssDefaultSubResource();
        //clone 并 更新输出参数
        $cssres = $this->subResource->clone([
            //使用 scss 资源自动输出 css 的功能
            "export" => "css"
        ]);
        //更新 subResource
        $this->subResource = $cssres;
        return $this;
    }
    //browser.scss 编译为 css
    protected function createDynamicCssBrowserSubResource()
    {
        //先生成 scss 资源实例 保存在 subResource
        $this->createDynamicScssBrowserSubResource();
        //clone 并 更新输出参数
        $cssres = $this->subResource->clone([
            //使用 scss 资源自动输出 css 的功能
            "export" => "css"
        ]);
        //更新 subResource
        $this->subResource = $cssres;
        return $this;
    }
    //生成 组件库使用的 一个或多个 图标库资源对应的 css 样式文件
    protected function createDynamicCssIconsetSubResource()
    {
        $isets = $this->desc["iconset"] ?? [];
        if (!Is::nemarr($isets)) $isets = [];
        //创建各 图标库资源实例
        $isets = array_map(function($isetf) {
            if (!Is::nemstr($isetf)) return null;
            if (substr($isetdf, -10)!==".icon.json") $isetf .= ".icon.json";
            $isetres = Resource::create($isetf, $this->fixSubResParams([
                "ignoreGet" => true,
                //输出 css
                "export" => "css",
                //输出文件
                "file" => "default",
            ]));
            if (!$isetres instanceof Icon) return null;
            //创建此 图标库的 css 临时资源
            $isetcss = Resource::manual(
                $isetres->export(["return"=>true]),
                $this->resTempResName("css"),    //$isetres->resBaseName().".css",
                [
                    "ext" => "js",
                    "export" => "css",
                    "ignoreGet" => true
                ]
            );
            if (!$isetcss instanceof Codex) return null;
            //释放临时 图标库资源
            unset($isetres);
            return $isetcss;
        }, $isets);
        //剔除不能成功实例化的 图标库 css 资源
        $isets = array_filter($isets, function($isetres) {
            return $isetres instanceof Codex;
        });

        //手动创建要输出的 css 子资源实例
        $css = Resource::manual(
            "",
            $this->resExportBaseName(), //$this->resBaseName().".css",
            [
                "ext" => "css",
                //"belongTo" => $this,
                "ignoreGet" => false,
                //merge 合并参数
                "merge" => $isets
            ]
        );
        if (!$css instanceof Codex) {
            //创建 css 资源实例失败
            throw new SrcException("组件库 ".$this->resBaseName()." 无法创建要输出的图标库 CSS 资源实例", "resource/getcontent");
        }
        
        //保存为 subResource
        $this->subResource = $css;

        //释放临时资源
        unset($isets);

        return $this;
    }
    //生成 第三方 UI 的 样式文件，依赖 $theme->themeMode["color"]
    protected function createDynamicCssUiSubResource()
    {
        //第三方 UI 库
        $uis = $this->ui;
        if (!Is::nemarr($uis)) {
            //未指定第三方 UI 库，输出空内容
            $this->subResource = $this->tempRes("css");
            return $this;
        }

        //当前请求的 theme color mode
        if ($this->theme instanceof Theme) {
            $pmode = $this->theme->themeMode["color"];
            if (Is::nemarr($pmode)) $pmode = $pmode[0];
        } else {
            //默认 light
            $pmode = "light";
        }
        //临时 css 资源
        $css = $this->tempRes("css", [
            "import" => "keep",
        ]);
        $rower = $css->RowProcessor;
        //依次合并各 UI 库的输出内容
        foreach ($uis as $uik => $uires) {
            $exp = $this->desc["vue"]["ui"][$uik]["export"]["ui.css"][$pmode] ?? null;
            if (!Is::nemarr($exp)) continue;
            //clone
            $uio = $uires->clone($exp);
            //生成 js 代码
            $uicss = $uio->export([
                "return" => true,
                "min" => true
            ]);
            if (!Is::nemstr($uicss)) continue;
            //释放资源
            unset($uio);
            //comment
            $rower->rowComment(
                "合并 $uik 库",
                "!! 不要手动修改 !!"
            );
            //插入
            $rower->rowAdd($uicss, "");
            $rower->rowEmpty(3);
        }
        //生成 js content
        $css->content = $rower->rowCombine();
        //作为 subResource
        $this->subResource = $css;
        return $this;
        

    }
    //生成 组件库基础样式、合并了 所有要加载的 各 *.vue 组件的外部 scss、文件内 <style> 内容，以及 extra 中定义的 common/ 文件夹下的 scss
    protected function createDynamicScssDefaultSubResource()
    {
        //通过 组件库基础样式文件，合并所有要加载 组件的 外部 scss 资源内容，生成最终输出的 scss 资源内容
        $desc = $this->desc;
        $stys = $desc["styles"] ?? [];

        //要 merge 到 组件库基础样式资源实例中的 其他 样式文件
        $merge = [];

        // 0    base.scss 组件库基础样式
        $base = $stys["base"] ?? null;
        if (!Is::nemstr($base)) {
            $bfp = $this->resSpecDir("common/base.scss", true);
        } else {
            //$bfp = Path::find($base, Path::FIND_FILE);
            $bfp = $this->resSpecDir("common/$base", true);;
        }
        if (Is::nemstr($bfp) && file_exists($bfp)) {
            $baseres = Resource::create($bfp, [
                "ignoreGet" => true,
                "export" => Resource::getExtFromPath($bfp),
            ]);
            if ($baseres instanceof Codex) {
                //添加到 merge 列表
                $merge[] = $baseres;
            }
        }

        // 1    所有组件的 scss 外部定义样式，以及 所有组件 *.vue 文件内部 <style> 代码块中的内容，作为 scss 合并
        $loadedScsses = [];
        $comps = $this->comps;
        $compns = $this->compns;
        $inners = [];
        foreach ($compns as $compk) {
            $compc = $comps[$compk] ?? null;
            if (!Is::nemarr($compc)) continue;
            //组件的 scss 外部样式
            $cscssi = $compc["scss"] ?? null;
            if (!Is::nemstr($cscssi)) continue;
            if (!in_array($cscssi, $loadedScsses)) {
                //当多个 vue 组件使用相同的外部 scss 时，只会加载一次
                $loadedScsses[] = $cscssi;
                $cscssres = Resource::create($cscssi, [
                    "ignoreGet" => true,
                    "export" => Resource::getExtFromPath($cscssi),
                ]);
                if ($cscssres instanceof Codex) {
                    //添加到 merge 列表
                    $merge[] = $cscssres;
                }
            }

            //组件 *.vue 资源实例
            $vueres = Resource::create($compc["file"], $this->fixSubResParams([
                "belongTo" => $this,
                "ignoreGet" => true,
                "export" => "css",
                "inject" => false,
            ]));
            //将 *.vue 资源实例中的 style 内容，创建为 临时资源
            $inscss = $vueres->style["content"];
            //释放
            unset($vueres);
            if (Is::nemstr($inscss)) {
                $inners[] = Resource::manual(
                    $inscss,
                    $this->resTempResName("scss"),  //$compk."_temp_inner_scss.scss",
                    [
                        "ext" => "scss",
                        "export" => "scss",
                        "ignoreGet" => true,
                        //组件文件内部样式 不启用 import
                        "import" => false,
                        "merge" => "",
                    ]
                );
            }
        }
        //将所有组件 *.vue 文件内部样式临时资源 添加到 merge 数组
        if (Is::nemarr($inners)) $merge = array_merge($merge, $inners);

        // 2    params["extra"] 中定义的 额外样式文件
        $extra = $this->params["extra"] ?? [];
        if (Is::nemstr($extra)) {
            if ($extra === "all") {
                $exs = null;
            } else if ($extra === "none") {
                $exs = [];
            } else {
                $exs = explode(",", $extra);   //Arr::mk($extra);
            }
        }
        if (is_null($exs) || Is::nemarr($exs)) {
            //递归获取 common/ 文件夹下的 scss 文件
            $extras = Path::flat($this->resSpecDir("common"), "", "-", "scss");
            if (Is::nemarr($extras)) {
                foreach ($extras as $ek => $efp) {
                    //需要排除
                    if (Is::nemarr($exs) || Is::nemstr($base)) {
                        $efbase = basename($efp);
                        $efn = pathinfo($efp)["filename"];
                        //如果此文件不在 params["extra"] 中指定了，则排除
                        if (Is::nemarr($exs) && !in_array($efbase, $exs) && !in_array($efn, $exs)) continue;
                        //如果此文件名 == desc["styles"]["base"] 则排除
                        if (Is::nemstr($base) && $efbase === $base) continue;
                    }
                    //创建资源实例
                    $exres = Resource::create($efp, [
                        "export" => "scss",
                        "ignoreGet" => true,
                        "import" => true,
                    ]);
                    if (!$exres instanceof Codex) continue;
                    //添加到 merge 列表
                    $merge[] = $exres;
                }
            }
        }

        //创建临时 scss 资源
        $scss = Resource::manual(
            "",
            //$this->resExportBaseName(), //"temp_default.scss",
            //可能在 css 文件中创建 scss 因此需要手动指定 scss 后缀
            $this->resBaseName().".scss",
            [
                "ext" => "scss",
                "export" => "scss",
                "ignoreGet" => true,
                //合并资源
                "merge" => $merge,
            ]
        );
        //保存到 subResource
        $this->subResource = $scss;

        return $this;
    }
    //生成完整的 组件库 样式 scss 文件，包含依赖的 SPF-Theme 主题，要加载的组件，combine 指定的 内外部 scss 资源
    protected function createDynamicScssBrowserSubResource()
    {
        //如果未指定 theme 或者 未启用，则返回空内容
        if (!$this->theme instanceof Theme) {
            $this->subResource = $this->tempRes("scss");
            return $this;
        }
        
        //通过 依赖的 SPF-Theme 主题资源实例 merge 组件库自有的 样式文件，生成最终输出的 scss 资源内容
        $desc = $this->desc;

        //要 merge 到主题资源实例中的 其他 样式文件
        $merge = [];

        // 0    default.scss
        //首先调用 default.scss 生成方法，生成 组件库基础样式以及 要加载组件的外部 scss 以及 extra 指定的 common/ 中的 scss 合并后资源
        $this->createDynamicScssDefaultSubResource();
        //生成 content
        $dftcnt = $this->subResource->export([
            "return" => true,

            //修改 params
            "ignoreGet" => true,
        ]);
        //使用生成的 scss 内容创建临时资源
        $dftres = Resource::manual(
            $dftcnt,
            $this->resTempResName("scss"),  //"temp_default.scss",
            [
                "ext" => "scss",
                "export" => "scss",
                "ignoreGet" => true,
            ]
        );
        //var_dump($dftcnt);
        $merge[] = $dftres;

        // 1    params["combine"] 中指定的 外部 scss
        $pmg = $this->params["combine"] ?? [];
        if (Is::nemstr($pmg)) $pmg = explode(",", $pmg); //Arr::mk($pmg);
        if (Is::nemarr($pmg)) {
            //合并到 merge 数组中
            $merge = array_merge($merge, $pmg);
        }

        /**
         * 调用 $this->theme 主题资源实例
         */
        $thres = $this->theme;
        //clone
        $tho = $thres->clone([
            /*//!! 强制 create 因为 combine 参数不会体现到 cache 文件名上
            //!! 如果不强制 create 则当 combine 改变时也不会触发 create
            //!! 主题中的 vcom.scss 默认优先读取 default.css 缓存以跳过主体自身文件的编译
            //!! 因此如果主题本身代码发生改变，需要先单独 create default.css?mode=...
            "create" => true,
            //!! 调用 SPF-Theme 主题中的 vcom.scss 子资源
            "file" => "vcom",*/

            //输出格式为 scss
            "export" => "scss",
            //传入合并资源数组
            "combine" => $merge,
        ]);
        //生成 scss
        $scss = $tho->export([
            "return" =>  true,
        ]);
        //释放
        unset($tho);

        /**
         * 使用生成的 scss 文件内容，创建一个临时 scss 资源，作为 subResource
         */
        $temp = Resource::manual(
            $scss,
            //可能在 css 文件中创建 scss 因此需要手动指定 scss 后缀
            $this->resBaseName().".scss",
            [
                "ext" => "scss",
                "export" => "scss",
                "ignoreGet" => true,
                "patch" => $this->params["patch"] ?? false,
            ]
        );
        $this->subResource = $temp;

        return $this;
    }

    /**
     * 某些 createSubResource 方法可能共用方法逻辑
     * @return Resource 要保存到 $this->subResource 中的子资源实例
     */
    //esm-browser.js 和 esm-browser-async.js 共用的方法逻辑
    private function commonCreateDynamicJsEsmBrowserMethod($async=false)
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

        // 0    创建插件资源实例
        $plgres = Resource::create($plf, $this->fixSubResParams([
            "belongTo" => $this,
            "ignoreGet" => false,
            //处理 import 语句
            "import" => true,
            //esm 导出
            "esm" => "keep",
        ]));

        // 1    创建 组件定义 js 资源
        $var = $desc["var"];
        $vcv = $var."Comps";
        $compjs = [];
        $compjs[] = "import $vcv from '".$this->viewUrl(($async ? "async-" : "")."default.js")."';";
        $compjs[] = "";
        $compjs[] = "cgy.each($vcv, (v, k) => {";
        if ($async) {
            $compjs[] = "Vue.vcoms.async[k] = v;";
        } else {
            $compjs[] = "Vue.vcoms.global[k] = v;";
        }
        $compjs[] = "});";
        $compjs[] = "";
        $compres = Resource::manual(
            implode("\n", $compjs),
            $this->resTempResName("js"),    //$this->resBaseName().".js",
            [
                "ext" => "js",
                "export" => "js",
                "ignoreGet" => true,
                "import" => "keep",
            ]
        );

        // 2    将当前组件库依赖的 资源信息，注入 Vue 属性中
        //当前组件库是否基础组件库
        $isBase = $this->resIsBaseVcom();
        $thjs = Resource::manual(
            "",
            $this->resTempResName("js"),    //"temp_theme_info_inject.js",
            [
                "ext" => "js",
                "export" => "js",
                "ignoreGet" => true,
                "import" => false,
                "merge" => "",
            ]
        );
        $thrower = $thjs->RowProcessor;
        $radd = $thrower->rowAdd;

        //当前组件库的 基础信息 注入 Vue.vcom 属性中
        $vcname = $desc["name"];
        $thrower->rowEmpty(3);
        $thrower->rowComment(
            "写入组件库 $vcname 信息",
            "!! 不要手动修改 !!"
        );
        $thrower->rowEmpty(1);
        $thrower->rowAdd("if (Vue.vcom.list.includes('".($isBase ? "base" : $vcname)."')!==true) {","");
        $thrower->rowAdd("    Vue.vcom.list.push('".($isBase ? "base" : $vcname)."');","");
        $thrower->rowAdd("    Vue.vcom.".($isBase ? "base" : $vcname)." = {","");
        $thrower->rowAdd("        name: '$vcname',","");
        $thrower->rowAdd("        isBase: ".($isBase ? "true" : "false").",","");
        //添加组件库名称前缀
        $thrower->rowAdd("        prefix: '".$desc["prefix"]."',","");
        $thrower->rowAdd("    }","");
        $thrower->rowAdd("}","");
        
        //ui 资源信息
        if ($isBase) {
            //只有 基础组件库才能写入这些参数
            $thrower->rowEmpty(3);
            $thrower->rowComment(
                "注入此插件使用的 SPF-Theme 主题参数",
                "!! 不要手动修改 !!"
            );
            $thrower->rowEmpty(1);
            $thrower->rowAdd("Vue.service.options = cgy.extend(Vue.service.options, {ui: {}});","");
            //主题资源信息
            if ($this->theme instanceof Theme) {
                //当前组件库启用的 SPF-Theme 主题
                $theme = $this->theme;
                $hasDarkMode = $theme->supportDarkMode();
                $thrower->rowAdd("Vue.service.options.ui.theme = {","");
                $thrower->rowAdd("    enable: true,","");
                $thrower->rowAdd("    supportDarkMode: ".($hasDarkMode ? "true" : "false").",","");
                $thrower->rowAdd("    cssvar: {","");
                if ($hasDarkMode) {
                    //分别生成 light|dark 模式下的 cssvar
                    $cmodes = $theme->colorModeShift();
                    foreach ($cmodes as $cmode => $cmodestr) {
                        //clone
                        $clone = $theme->clone(["mode" => $cmodestr]);
                        //读取此主题的 cssvar
                        $csv = $clone->themeCtx;
                        //额外数据
                        $csv["extra"] = $clone->themeExtraCtx;
                        //json 必须转义 ' 字符
                        $thjson = Conv::a2j($csv);
                        $thjson = str_replace("'", "\\'", $thjson);
                        //插入定义 js 代码
                        $thrower->rowAdd("        $cmode: JSON.parse('$thjson'),","");
                        //释放
                        unset($clone);
                    }
                } else {
                    $thjson = Conv::a2j($theme->themeCtx);
                    $thjson = str_replace("'", "\\'", $thjson);
                    //插入定义 js 代码 默认是 light 模式
                    $thrower->rowAdd("        light: JSON.parse('$thjson'),","");
                }
                $thrower->rowAdd("    },","");
                $thrower->rowAdd("}","");
                if ($hasDarkMode) {
                    //读取 loacalStorage 确定最终的 主题模式
                    $thrower->rowAdd("let autoDarkMode = cgy.localStorage.\$getJson('service.ui', 'theme.autoDarkMode', true);","");
                    $thrower->rowAdd("let inDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;","");
                    $thrower->rowAdd("if (!autoDarkMode) inDarkMode = cgy.localStorage.\$getJson('service.ui', 'theme.inDarkMode', false);","");
                    $thrower->rowAdd("Vue.service.options.ui.theme.autoDarkMode = autoDarkMode;","");
                    $thrower->rowAdd("Vue.service.options.ui.theme.inDarkMode = inDarkMode;","");

                    //!! 不需要，Vue.service.ui.cssvar 通过 computed 计算属性提供
                    //将对应模式的 cssvar 写入 ui.cssvar
                    //$thrower->rowAdd("Vue.service.options.ui.cssvar = Vue.service.options.ui.theme.cssvar[inDarkMode ? 'dark' : 'light'];","");
                }
            }
        }

        //筛选所有 page-* 作为 SPA 页面的组件
        //!! 基础组件库不可能包含 SPA 页面组件，因此只检查 业务组件库
        if ($isBase) {
            //基础组件库只创建 Vue.service.options.nav.pages|page 参数
            $thrower->rowAdd("Vue.service.options = cgy.extend(Vue.service.options, {nav:{pages:[], page:{}}});","");
        } else {
            //业务组件库 则需要筛选出可作为 SPA 页面的 page-* 组件列表
            $thrower->rowEmpty(3);
            $thrower->rowComment(
                "注入当前 SPA 使用的所有业务组件库中的 页面组件参数",
                "!! 不要手动修改 !!"
            );
            $thrower->rowEmpty(1);
            $compns = $this->compns;
            //开始插入 js
            $thrower->rowAdd("let spaPages = {","");
            foreach ($compns as $compk) {
                //组件参数
                $compc = $this->comps[$compk] ?? null;
                if (!Is::nemarr($compc)) continue;
                //创建组件临时资源实例
                $vcres = Resource::create($compc["file"], $this->fixSubResParams([
                    "belongTo" => $this,
                    "export" => "js"
                ]));
                //SPA 页面组件必须定义了 profile["isSpaPage"] === true
                $profile = $vcres->profile;
                if (!Is::nemarr($profile) || !isset($profile["isSpaPage"]) || $profile["isSpaPage"] !== true) {
                    unset($vcres);
                    continue;
                }
                //复合 SPA 页面组件的 条件，保存组件 profile 参数
                $pjson = Conv::a2j($profile);
                //转义 '
                $pjson = str_replace("'","\\'", $pjson);
                $vcn = $compc["vcn"];
                //插入 js
                $thrower->rowAdd("    '$vcn': JSON.parse('$pjson'),","");
                //释放
                unset($vcres);
            }
            $thrower->rowAdd("}","");
            $thrower->rowAdd("cgy.each(spaPages, (v,k) => {","");
            $thrower->rowAdd("    if (!Vue.service.options.nav.pages.includes(k)) {","");
            $thrower->rowAdd("        Vue.service.options.nav.pages.push(k);","");
            $thrower->rowAdd("        Vue.service.options.nav.page[k] = v;","");
            $thrower->rowAdd("        Vue.service.options.nav.page[k].spaPageName = k;","");
            $thrower->rowAdd("    }","");
            $thrower->rowAdd("});","");

        }

        //图标库资源信息
        if ($isBase) {
            //只有基础组件库，才能初始定义
            $thrower->rowAdd("Vue.service.options.ui.iconset = {","");
            if (Is::nemarr($this->icon)) {
                //启用了 SPF-Icon 图标库
                $thrower->rowAdd("    enable: true,","");
                foreach ($this->icon as $isetres) {
                    if (!$isetres instanceof Icon) continue;
                    //图标库名称
                    $iset = $isetres->desc["iconset"];
                    //图表库中包含的所有图标名 数组，不含前缀
                    $icns = array_keys($isetres->glyphs);
                    //插入 js 代码
                    $thrower->rowAdd("    '$iset': ['".implode("', '", $icns)."'],","");
                }
            } else {
                //未启用图标库
                $thrower->rowAdd("    enable: false,","");
            }
            $thrower->rowAdd("}","");
        } else {
            //业务组件库只能修改 service.options.ui.iconset 参数
            if (Is::nemarr($this->icon)) {
                //业务组件库 启用了 SPF-Icon 图标库
                $thrower->rowAdd("Vue.service.options.ui.iconset.enable = true;","");
                foreach ($this->icon as $isetres) {
                    if (!$isetres instanceof Icon) continue;
                    //图标库名称
                    $iset = $isetres->desc["iconset"];
                    //图表库中包含的所有图标名 数组，不含前缀
                    $icns = array_keys($isetres->glyphs);
                    //插入 js 代码
                    $thrower->rowAdd("Vue.service.options.ui.iconset['$iset'] = ['".implode("', '", $icns)."'];","");
                }
            }
        }

        //临时 js 生成 content
        $thjs->content = $thrower->rowCombine();


        //创建一个临时 js 资源实例，合并上面的所有资源实例，作为最终 subResource 实例
        return Resource::manual(
            "",
            $this->resExportBaseName(), //$this->resBaseName.".js",
            [
                "ext" => "js",
                "export" => "js",
                "ignoreGet" => true,
                "import" => "keep",
                //合并资源
                "merge" => [
                    $plgres, $compres, $thjs
                ],
                //esm 保持
                "esm" => "keep",
            ]
        );
    }
    //vue.js 和 esm-vue.js 共用的方法逻辑
    private function commonCreateDynamicJsVueMethod($esm=false)
    {
        //Vue 库资源实例
        $vue = $this->vue;
        if (!$vue instanceof Compound) {
            //当前组件库未定义 依赖的 Vue 库参数，输出空内容
            return $this->tempRes("js");
        }

        //输出文件
        $exp = $this->desc["vue"]["export"][($esm ? "esm-" : "")."vue.js"] ?? [];
        //clone Vue 资源库作为 subResource
        return $vue->clone($exp);
    }
    //ui.js 和 esm-ui.js 共用的方法逻辑
    private function commonCreateDynamicJsUiMethod($esm=false)
    {
        //第三方 UI 库
        $uis = $this->ui;
        if (!Is::nemarr($uis)) {
            //未指定第三方 UI 库，输出空内容
            return $this->tempRes("js");
        }

        //临时 js 资源
        $js = $this->tempRes("js", [
            "import" => "keep",
        ]);
        $rower = $js->RowProcessor;
        //依次合并各 UI 库的输出内容
        foreach ($uis as $uik => $uires) {
            $exp = $this->desc["vue"]["ui"][$uik]["export"][($esm ? "esm-" : "")."ui.js"] ?? null;
            if (!Is::nemarr($exp)) continue;
            //clone
            $uio = $uires->clone($exp);
            //生成 js 代码
            $uijs = $uio->export([
                "return" => true,
                "min" => true
            ]);
            if (!Is::nemstr($uijs)) continue;
            //释放资源
            unset($uio);
            //comment
            $rower->rowComment(
                "合并 $uik 库",
                "!! 不要手动修改 !!"
            );
            //插入
            $rower->rowAdd($uijs, "");
            $rower->rowEmpty(3);
        }
        //生成 js content
        $js->content = $rower->rowCombine();

        return $js;
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
     * 根据传入的参数，生成缓存文件名
     * !! 覆盖父类
     * @return String|null 当前请求的子资源对应缓存文件的 文件名
     */
    public function cacheFileName()
    {
        if ($this->cacheEnabled() !== true) return null;

        //文件路径中的 连字符
        $glue = "-";
        //export-ext
        $sext = $this->ext;
        //params
        $params = $this->params;
        //请求的子资源 名称
        $srfn = $this->subResourceName;
        //请求的子资源 描述参数
        //$opts = $this->subResourceOpts;
        //子资源类型 static|dynamic
        //$stp = $opts["type"];

        //缓存文件 路径数组
        $fparr = [
            //路径：esm-browser-js
            $srfn.$glue.$sext,
            //路径：sv-full-light
            $this->desc["prefix"].$glue.$params["mode"].$glue.$params["theme"], 
            //路径：最终要加载的 组件名数组 implode 并 MD5 后作为文件路径
            "comps".$glue.md5(implode($glue, $this->compns)),
        ];

        //需要体现在缓存文件名中的 params 参数
        $ps = $this->resCustomParams();
        //去除 不需要体现在文件名中的 参数
        $usks = ["prefix", "mode", "load", "unload", "theme", "create"];
        foreach ($usks as $usk) {unset($ps[$usk]);}
        //需要转换 combine 参数中的 资源实例 为 资源路径
        $combine = $ps["combine"] ?? [];
        if (Is::nemstr($combine)) $combine = explode(",", $combine);
        if (!Is::nemarr($combine)) $combine = [];
        $cfp = [];
        foreach ($combine as $cbi) {
            if (Is::nemstr($cbi)) {
                //!! 传入了 combine 待合并资源的路径，如果存在 ?queryString 则去除
                //!! 否则create 模式下生成的 缓存文件名 与 !create 模式下的缓存文件名不同
                if (strpos($cbi, "?") !== false) {
                    $cfp[] = explode("?", $cbi)[0];
                } else {
                    $cfp[] = $cbi;
                }
                continue;
            }
            if ($cbi instanceof Resource) {
                $cfp[] = $cbi->real;
                continue;
            }
        }
        $ps["combine"] = $cfp;

        //参数 转 json 并 md5() 后，作为缓存文件名
        $pjson = Conv::a2j($ps);
        $fparr[] = md5($pjson).".".$sext;

        //最终返回完整的 缓存文件名称路径
        return implode("/", $fparr);
    }

    /**
     * 单独 读取缓存 components/[prefix].json 组件库中所有已定义的 *.vue 组件数据
     * @return Array|null
     */
    public function cacheGetComponents()
    {
        if ($this->cacheEnabled() !== true) return null;

        //components.json 缓存文件
        $cfp = $this->cachePath("components/".$this->desc["prefix"].".json", true);
        if (!Is::nemstr($cfp)) return null;

        //读取
        $json = file_get_contents($cfp);
        $comps = Conv::j2a($comps);
        if (!Is::nemarr($comps)) return null;
        return $comps;
    }

    /**
     * 单独 写入 components/[prefix].json 组件列表缓存
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
        $cfp = $this->cachePath("components/".$this->desc["prefix"].".json", false);
        //写入缓存
        return Path::mkfile($cfp, $json);
    }



    /**
     * 工具方法 getters 资源信息获取
     */

    /**
     * 判断当前组件库是否 基础组件库
     * @return Bool
     */
    public function resIsBaseVcom()
    {
        $desc = $this->desc;

        //必须启用主题
        $thc = $desc["theme"] ?? [];
        if (isset($thc["enable"]) && $thc["enable"]!==true) return false;

        //必须启用至少一个 图标库
        $iset = $desc["iconset"] ?? [];
        if (!Is::nemarr($iset) || !Is::indexed($iset) || empty($iset)) return false;

        return true;
    }

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
            //如果是 业务组件库，还需要提供 所在基础组件库的 prefix
            "basePre" => $this->desc["basePrefix"],
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
     * 根据 desc 获取依赖的 Vue 资源实例
     * @return Cdn|Lib|null 未找到则返回 null
     */
    public function resVueResource()
    {
        //如果 $this->vue 已存在，直接返回
        if ($this->vue instanceof Compound) return $this->vue;

        //desc
        $desc = $this->desc;
        $vuec = $desc["vue"] ?? [];
        if (!Is::nemarr($vuec)) return null;
        //指定发 vue 库文件
        $vfc = $vuec["file"];
        $vfp = Path::find($vfc, Path::FIND_FILE);
        if (!Is::nemstr($vfp)) return null;
        //实例化参数
        $ps = $vuec["params"] ?? [];
        //附加其他实例化参数
        //!! Vue 库资源不跟随 Vcom 自动刷新，如有修改需要手动 create
        $ps = $this->fixSubResParams($ps, false);
        //创建资源实例
        $vueres = Resource::create($vfp, $ps);
        if (!$vueres instanceof Compound) return null;
        return $vueres;
    }

    /**
     * 根据 desc 获取依赖的 第三方 UI 库资源实例，可以有多个
     * @return Array 资源实例组成的数组
     */
    public function resUiResource()
    {
        if (Is::nemarr($this->ui)) return $this->ui;

        //desc
        $desc = $this->desc;
        $uics = $desc["vue"]["ui"] ?? [];
        if (!Is::nemarr($uics)) return [];
        //依次实例化
        $uis = [];
        foreach ($uics as $uik => $uic) {
            //ui 库文件
            $uif = $uic["file"] ?? null;
            if (!Is::nemstr($uif)) continue;
            $uifp = Path::find($uif, Path::FIND_FILE);
            if (!Is::nemstr($uifp)) continue;
            $uip = $uic["params"] ?? [];
            //!! 第三方 UI 库资源不跟随 Vcom 自动刷新，如有修改需要手动 create
            $uip = $this->fixSubResParams($uip, false);
            $uires = Resource::create($uifp, $uip);
            if (!$uires instanceof Compound) continue;
            //缓存
            $uis[$uik] = $uires;
        }
        return $uis;
    }

    /**
     * 根据 desc 获取依赖的 Theme 资源实例
     * @return Theme|null 
     */
    public function resThemeResource()
    {
        if ($this->theme instanceof Theme) return $this->theme;

        //desc
        $desc = $this->desc;
        $thmc = $desc["theme"] ?? [];
        if (!Is::nemarr($thmc) || $thmc["enable"] !== true) return null;
        //主题文件
        $thfp = $thmc["file"] ?? null;
        if (!Is::nemstr($thfp)) return null;
        if (substr($thfp, -11) !== ".theme.json") $thfp .= ".theme.json";
        //查找
        $thf = Path::find($thfp, Path::FIND_FILE);
        if (!Is::nemstr($thf)) return null;
        //实例化参数
        $ps = $thmc["params"] ?? [];
        //!! params["theme"] 覆盖 $ps["mode"]
        if (
            isset($this->params["theme"]) && (
                Is::nemstr($this->params["theme"]) || 
                Is::nemarr($this->params["theme"])
            )
        ) {
            $ps["mode"] = $this->params["theme"];
        }
        //!! 需要将组件库的组件名前缀，作为样式类前缀 注入 主题资源实例
        $ps["prefix"] = $desc["prefix"];
        //附加其他实例化参数
        $ps = $this->fixSubResParams($ps);
        //创建资源实例
        $thres = Resource::create($thf, $ps);
        if (!$thres instanceof Theme) return null;
        return $thres;
    }

    /**
     * 根据 desc 获取依赖的 Icon 资源实例
     * @return Array|null 可以有多个依赖的图标库资源，因此返回 []
     */
    public function resIconResource()
    {
        if (Is::nemarr($this->icon) && Is::indexed($this->icon)) return $this->icon;

        //desc
        $desc = $this->desc;
        $iset = $desc["iconset"] ?? [];
        if (!Is::nemarr($iset) || !Is::indexed($iset)) return null;
        //创建各 图标库资源实例
        $isets = array_map(function($isetf) {
            if (!Is::nemstr($isetf)) return null;
            if (substr($isetdf, -10)!==".icon.json") $isetf .= ".icon.json";
            //!! Icon 图标库资源不跟随 Vcom 自动刷新，如有修改需要手动 create
            $isetres = Resource::create($isetf, $this->fixSubResParams([], false));
            if (!$isetres instanceof Icon) return null;
            return $isetres;
        }, $iset);
        //剔除不能成功实例化的 图标库 css 资源
        $isets = array_filter($isets, function($isetres) {
            return $isetres instanceof Icon;
        });
        if (!Is::nemarr($isets)) return null;
        return $isets;
    }

    /**
     * 获取当前组件库依赖的 vue 库文件 外部访问 url，用于在视图中引用 script
     * @return String|null url
     */
    public function resVueResourceUrl()
    {
        //依赖的 Vue 库资源实例
        $vres = $this->vue;

        $desc = $this->desc;
        $vuec = $desc["vue"];

        //vue 库资源 json 文件路径
        $vfc = $vuec["file"];
        $vfp = Path::find($vfc, Path::FIND_FILE);
        if (!Is::nemstr($vfp)) return null;
        //版本
        $vver = $vuec["version"];
        //实例化参数
        $ps = $vuec["params"] ?? [];
        //版本号添加到 params
        $ps["ver"] = $vver;
        //创建 vue 库资源 url
        $vu = Url::src($vfp, true);
        //去除 url 中的 .json 后缀
        $vu = substr($vu, 0, -5);
        //增加 queryString
        $qs = Conv::a2u($ps);
        //返回 url
        $vu = $vu.(Is::nemstr($qs) ? "?".$qs : "");
        return $vu;


    }

    /**
     * 生成临时资源的文件名
     * @param String $ext 资源类型
     * @return String 临时文件名
     */
    public function resTempResName($ext="js")
    {
        $rn = $this->resName();
        $nc = Str::nonce(6, false);
        return "temp_".$rn."_".$nc.".".$ext;
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

    /**
     * 替换 scss|css 文件内容中的 字符串模板 
     * @param String $cnt 要替换的文件内容
     * @return String 替换后的内容
     */
    public function replacePreTemp($cnt)
    {
        if (!Is::nemstr($cnt)) return $cnt;

        $desc = $this->desc;
        //desc 中定义的 字符串模板
        $tpl = $desc["pretemp"];

        foreach ($tpl as $ts => $dk) {
            $dv = Arr::find($desc, $dk);
            if (!Is::nemstr($dv)) continue;
            $cnt = str_replace($ts, $dv, $cnt);
        }

        return $cnt;
    }



    /**
     * fix 方法
     */

    /**
     * 在输出 css 之前，替换可能存在的 主题类名前缀
     * @return $this
     */
    protected function fixVcomPrefixBeforeExport()
    {
        $cnt = $this->content;

        /**
         * 主题样式类名前缀 字符串模板替换
         */
        $cnt = $this->replacePreTemp($cnt);

        //写回
        $this->content = $cnt;
        return $this;
    }



    /**
     * 工具方法 用于 View 视图中创建资源引用 url
     */

    /**
     * 在 View 视图中 输出 资源 url
     * @param String $file 在 desc["content"] 中定义的 资源名称
     * @param Array $params 可调整 url 的 queryString 在资源当前的 resCustomParams 基础上修改
     * @param Bool $fullUrl 是否输出完整 url 默认 false 输出 /src 开头的 shortUrl
     * @return String url
     */
    public function viewUrl($file, $params=[], $fullUrl=false)
    {
        if (!Is::nemarr($params)) $params = [];

        //去除 params 中的 export
        $params["export"] = "__delete__";
        //去除 params["file"]
        $params["file"] = "__delete__";
        //如果传入了 combine 参数，则去除，否则 combine 设为 __delete__
        if (isset($params["combine"]) && $params["combine"] === true) {
            unset($params["combine"]);
        } else {
            $params["combine"] = "__delete__";
        }

        //生成完整 url
        $url = $this->resUrlSelf($file, $params);
        if ($fullUrl) return $url;
        //转为 shortUrl
        $cu = Url::$current;
        $domain = $cu->domain;
        //判断是否与当前 url 使用相同的 domain
        $dlen = strlen($domain);
        if (substr($url, 0, $dlen) === $domain) return substr($url, $dlen);
        //domain 不一致不能简化 url
        return $url;
    }



    /**
     * 静态工具
     */

    /**
     * 解析 *.vcom.json 文件路径（.vcom.json 后缀名可以省略）得到完整路径，以及 vcom 组件名 $vcom->desc["name"]
     * @param String $vcom *.vcom.json 路径
     * @return Array|null 传入的 *.vcom.json 文件不存在 则返回 null 
     *  [
     *      "file" => "带 .vcom.json 后缀名的 完整路径",
     *      "name" => $vcom->desc["name"]
     *  ]
     */
    public static function getVcomNameFromPath($vcom)
    {
        if (!Is::nemstr($vcom)) return null;
        //补全后缀
        if (substr($vcom, -10) !== ".vcom.json") $vcom .= ".vcom.json";
        //检查文件是否存在
        $vcfp = Path::find($vcom, Path::FIND_FILE);
        if (!Is::nemstr($vcfp)) return null;
        //获取 *.vcom.json 文件中指定的 vcom name
        $vc = Conv::j2a(file_get_contents($vcfp));
        $vcn = $vc["name"] ?? null;
        //未指定 vcom name 则使用文件名
        if (!Is::nemstr($vcn)) {
            $vcbn = basename($vcfp);
            $vcn = substr($vcbn, 0, -10);
        }
        return [
            "file" => $vcfp,
            "name" => $vcn
        ];
    }

    /**
     * 解析传入的 一个或多个 组件库文件路径、参数，用于 生成最终可用于 View 视图输出的 业务组件库列表结构
     * 调用方法：
     *      Vcom::fixVcomList("foo/bar/vc.vcom.json")
     *      Vcom::fixVcomList("foo/bar/vc_1", "foo/bar/vc_2", ...)
     *      Vcom::fixVcomList([
     *          "vc_1" => "foo/bar/vc_1",
     *          "vc_2" => [
     *              "file" => "foo/bar/vc_2",
     *              "params" => [...]
     *          ],
     *          ...
     *      ])
     * @param Array $vcoms 一个或多个，文件路径 或 路径+参数 associate 数组
     * @return Array 处理后的 业务组件库参数数组：
     *  [
     *      "appk" => [
     *          "file" => "带 .vcom.json 后缀名的 完整路径",
     *          "params" => [ ... 传入的业务组件库实例化参数 ... ]
     *      ],
     *  ]
     */
    public static function fixVcomList(...$vcoms)
    {
        if (!Is::nemarr($vcoms)) return [];
        if (count($vcoms) === 1) {
            $apps = $vcoms[0];
        } else {
            $apps = array_merge([], $vcoms);
        }

        //使用业务组件库 剔除无效的组件库
        if (Is::nemstr($apps)) {
            //调用方式：Vcom::fixVcomList("foo/bar/vc.vcom.json")
            $vcn = Vcom::getVcomNameFromPath($apps);
            //指定的 业务组件库 无效
            if (!Is::nemarr($vcn)) return null;
            $vcf = $vcn["file"];
            $vcn = $vcn["name"];
            $apps = [
                $vcn => [
                    "file" => $vcf,
                    "params" => []
                ]
            ];
        } else if (Is::nemarr($apps)) {
            $napps = [];
            if (Is::associate($apps)) {
                /**
                 * 调用方式：
                 *      Vcom::fixVcomList([
                 *          "spa_1" => "foo/bar/spa_1",
                 *          "spa_2" => [
                 *              "file" => "foo/bar/spa_2",
                 *              "params" => [
                 *                  ...
                 *              ]
                 *          ],
                 *      ])
                 */
                foreach ($apps as $appk => $appc) {
                    if (Is::nemstr($appc)) {
                        $vcn = Vcom::getVcomNameFromPath($appc);
                        if (!Is::nemarr($vcn)) continue;
                        $napps[$vcn["name"]] = [
                            "file" => $vcn["file"],
                            "params" => []
                        ];
                        continue;
                    } else if (Is::nemarr($appc) && isset($appc["file"])) {
                        $vcn = Vcom::getVcomNameFromPath($appc["file"]);
                        if (!Is::nemarr($vcn)) continue;
                        $napps[$vcn["name"]] = Arr::extend($appc, [
                            "file" => $vcn["file"]
                        ]);
                        continue;
                    }
                }
            } else if (Is::indexed($apps)) {
                //调用方式：Vcom::fixVcomList("foo/bar/vc_1", "foo/bar/vc_2", ...)
                foreach ($apps as $appi) {
                    if (!Is::nemstr($appi)) continue;
                    $vcn = Vcom::getVcomNameFromPath($apps);
                    if (!Is::nemarr($vcn)) continue;
                    $napps[$vcn["name"]] = [
                        "file" => $vcn["file"],
                        "params" => []
                    ];
                }
            }
            $apps = Is::nemarr($napps) ? $napps : [];
        }
        return $apps;
    }

    /**
     * 定义处理 复合资源请求的 代理响应方法，在 Src 模块中，可使用此方法，响应前端请求
     * !! 覆盖父类，实现 组件库 特有的 响应代理方法
     * @param Array $args 前端请求 URI 数组
     * @return Mixed 
     */
    public static function responseProxyer(...$args)
    {
        if (!empty($args) && array_slice($args, -1)[0]==="spa.html") {
            //访问 使用此组件库的 Spa 视图

            //首先调用父类 proxyer 获取对应的 组件库实例
            $args = array_slice($args, 0, -1);
            $args[] = "default.js";
            //args 最后一个参数 true 表示忽略 Compound 子类覆盖定义的 responseProxyer 方法，直接使用父类的 proxyer
            $args[] = true;
            //args 数组头部增加 vcom
            array_unshift($args, "vcom");
            $vcom = Compound::responseProxyer(...$args);
            if (!$vcom instanceof Vcom) {
                Response::insSetCode(404);
                return null;
            }

            //输出视图
            Response::insSetType("view");
            /*return [
                "view" => "spf/assets/view/spa_vue2x.php",
                "params" => [
                    "vcom" => $vcom
                ],
            ];*/
            return View::SpaVue2x($vcom);
        }

        //其他情况，直接使用父类 proxyer
        
        //args 最后一个参数 true 表示忽略 Compound 子类覆盖定义的 responseProxyer 方法，直接使用父类的 proxyer
        $args[] = true;
        //args 数组头部增加 vcom
        array_unshift($args, "vcom");
        return Compound::responseProxyer(...$args);
    }
}