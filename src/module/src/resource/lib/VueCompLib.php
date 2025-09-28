<?php
/**
 * Lib 前端库资源类的子类 VueCompLib
 * 专门处理 Vue2.x 组件库资源
 * 
 * SPF-VueComponentLib 组件库，以 *.vue 文件以及对应的 *.scss 文件作为最基本的 组件资源，同时每套组件库还搭配一个 Vue2.x plugin 插件
 * 一个标准的组件库文件夹结构：
 *      -- vcomp.lib 
 *      -- vcomp
 *         |-- 1.0.0
 *         |   |-- plugin
 *         |   |   |-- vcomp.js
 *         |   |   |-- directive.js
 *         |   |   |-- global.js
 *         |   |   |-- instance.js
 *         |   |   |-- mixin.js
 *         |   |-- components
 *         |   |   |-- button
 *         |   |   |   |-- button.vue           //组件名：pre-button
 *         |   |   |   |-- button.scss 
 *         |   |   |-- icon.vue                 //组件名：pre-icon
 *         |   |   |-- table
 *         |   |   |   |-- table.vue            //组件名：pre-table
 *         |   |   |   |-- row.vue              //组件名：pre-table-row
 *         |   |   |   |-- col.vue              //组件名：pre-table-col
 *         |   |   |   |-- form_auto.vue        //组件名：pre-table-form-auto
 *         |   |   |   |-- ...
 *         |   |   |   |-- table.scss
 *         |   |   |-- ...
 *         |   |-- mixins
 *         |   |   |-- base.js 
 *         |   |   |-- table-base.js
 *         |   |   |-- ...
 *         |   |-- common
 *         |   |   |-- common.scss
 *         |   |   |-- ...
 * !! components 文件夹下的 *.vue | *.scss 文件名不应带有 - 连接符，应使用文件夹层级表示 组件组 关系
 * !! 如果必须使用连接符，必须使用 _
 * 
 * 组件库自动编译输出完整的 js|css 文件，并缓存。生成的 js 代码包含 Vue.use() 等环境准备语句，将自动向 head 插入 css link
 * 只需要 <script src="/src/lib/vcomp.min.js"></script> 即可完成环境准备
 * 各组件 *.vue 文件 以及 *.scss 文件 修改后，通过指定 create=true 即可实时刷新缓存，组件的修改将立即生效
 * 
 * 资源输出形式：
 *  0   以 script 形式引用
 *      https://host/src/lib/vcomp[/version][.min].js        输出所有组件，注册为全局组件
 *      https://host/src/lib/vcomp[/version][.min].css       自动编译所有组件的 内部 css | 外部 scss ，合并 common.scss 最终输出完整 css
 *  1   以 esm 形式引用
 *      import vcomp from 'https://host/src/lib/vcomp[/version]/esm[.min].js'
 *      Vue.use(vcomp, {...})
 *  2   仅加载部分组件
 *      /src/lib/vcomp[/version]/[esm-]load-button-icon-...[.min].js|css        加载指定的组件
 *      /src/lib/vcomp[/version]/[esm-]button!-icon!-...[.min].js|css           加载全部，除了指定的组件
 *  3   输出内部的 js|scss 文件
 *      /src/lib/vcomp[/version]/plugin/mixin[.min].js
 *      /src/lib/vcomp[/version]/components/button[.min].vue|scss
 *      /src/lib/vcomp[/version]/components/compgroup[.min].scss
 *      /src/lib/vcomp[/version]/components/compgroup/foo[.min].vue|scss
 *      /src/lib/vcomp[/version]/mixins/base[.min].js
 *      /src/lib/vcomp[/version]/common/common.scss
 */

namespace Spf\module\src\resource\lib;

use Spf\Response;
use Spf\module\src\Resource;
use Spf\module\src\resource\Lib;
use Spf\module\src\resource\Theme;
use Spf\module\src\resource\Scss;
use Spf\module\src\resource\Js;
use Spf\module\src\Mime;
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;
use Spf\util\Curl;

class VueCompLib extends Lib 
{
    /**
     * 定义标准的 前端库参数格式
     * !! 覆盖父类
     */
    protected static $stdLib = [
        //前端库名称 如：vue
        "lib" => "",
        //此组件库的 组件名 前缀，不指定则使用 lib 名称
        "prefix" => "",
        //此前端库 代码中的 变量名 如：Vue
        "variable" => "",
        //指定可以通过 @|latest 访问的 版本号
        "version" => [
            //当前使用的 默认版本 可省略版本号，如：/src/vue.lib  相当于  /src/vue.lib?ver=@
            "@" => "",
            //最新版本号，如：/src/vue.lib?ver=latest
            "latest" => "",
        ],
        //此 前端库 可对外提供的 文件后缀名类型，可以有多个
        "ext" => [],
        //可通过这些开关，切换需要输出的 库文件 版本
        "switch" => [
            //是否输出 环境准备 js 将引入 plugin 插件，注册所有 可用组件，并使用 Vue.use 方法
            "env" => false,
            //是否以 esm 形式导入
            "esm" => true,
            //是否用于 浏览器端
            //"browser" => true,

            //仅加载指定的，组件
            "load" => false,

            //可以自定义其他 开关
            //...
        ],
        /**
         * 资源来源 cdn 路径前缀 版本号之前的内容，如：https://cdnjs.cloudflare.com/ajax/libs/vue/
         * !! 可以指定为本地文件路径，如 src/app_name/lib/lib_name/
         * !! 如果指定为空字符串，则使用当前 *.lib 文件所在路径下的 lib_name 同名文件夹
         */
        "cdn" => "",

        //可以为当前的 前端库资源 单独指定一个 Lib 子类，用于执行一些自定义的 库资源 操作方法，必须是 类全称
        "class" => "",

        /**
         * 此组件库依赖的 其他 Vue2.x 组件库
         * 例如 此组件库可以在 element-ui 基础上 二次封装
         * 指定 一个|多个 本地 *.lib 文件路径
         * !! 严格按顺序加载
         */
        "dependency" => [
            //"spf/assets/lib/element-ui.lib",
        ],

        /**
         * 可为 组件库指定 SPF-Theme 主题资源库
         * 指定 一个 本地 *.theme 文件路径
         */
        "theme" => "",  // spf/assets/theme/spf.theme

        /**
         * 定义此组件库的通用文件
         * 需要在输出对应文件内容时，按顺序合并这些文件
         * 这些文件必须是 组件内部文件，使用相对于 version 版本号文件夹的 相对路径
         * 支持使用 ../ 访问上级路径
         * 例如：
         *      common/foo.js           -->  lib_path/version/common/foo.js 
         *      ../extra/foo.css        -->  lib_path/extra/foo.css
         */
        "common" => [
            //通用 js 文件，在创建 env 环境初始化 js 时，需要合并这些文件
            "js" => [],

            //通用 css|scss 文件，在创建 组件库 css 文件内容时，需要合并这些文件
            "css" => [
                //默认 需要加载 common.scss
                "common/common.scss",
            ]
        ],
    ];

    //组件库内部文件夹列表，标准结构
    protected static $stdDirs = [
        "plugin", "components", "mixins", "common",
    ];

    /**
     * 定义 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    protected static $stdParams = [
        //是否 强制不使用 缓存的 库文件内容
        "create" => false,
        //使用的 库版本号 默认 @  可选 @|latest|具体版本号 如 2.3.4
        "ver" => "@",
        //输出文件的 类型 默认类型是 stdLib["ext"][0]
        "export" => "",
        //是否输出某个 组件库内部文件
        "inner" => false,
        //通过解析 URI 得到的 要访问的 组件库内部文件实际路径
        "file" => "",

        //输出 css 时，可以指定 暗黑模式
        "dark" => false,

        //开关参数，在 stdLib["switch"] 中定义的内容
        //...
        
        //其他可选参数，主要用于处理 要输出的 库文件 如 js|css 文件 的参数
        //...
    ];
    
    /**
     * 定义支持的 export 类型，必须定义相关的 createFooContent() 方法
     * 必须是 Mime 支持的 文件后缀名
     * !! 覆盖父类
     * !! 如果包含 * 字符，表示支持任意类型
     */
    protected static $exps = [
        "js", "css", "scss", "*",
    ];
    
    /**
     * 定义 在 输出的 内容字符串中可使用的 字符串模板，以及其对应的 getCompLibInfo 数组中的数据 key
     */
   protected $tpls = [
       //此组件所在组件库 外部访问的 url 前缀，通常用于 import url
       "__URLPRE__" => "urlpre",
       //组件库定义的 组件名称前缀，通常用于 style 样式代码中的 样式类名称
       "__PRE__" => "pre",
       //用于组件模板代码块中，代替 组件名称前缀，以便可以方便的 在不同的使用场景下，切换组件名称前缀
       //例如：<PRE@-button>...</PRE@-button> 替换为 <pre-button>...</pre-button>
       "PRE@" => "pre",
       //"<@-" => ["lib/pre", "<%-"],
       //"</@-" => ["lib/pre", "</%-"],
   ];

    //当前请求的 库文件信息
    public $libfile = [
        //库版本号 具体的版本号，必须在 *.lib 文件中定义了
        "ver" => "",
        //需要加载的 组件列表
        "comps" => [
            /*
            "prefix-button" => "button.vue 本地路径",
            "prefix-vg-foo" => "vg/foo.vue",
            ...
            */
        ],

        //当需要输出组件库内部某个文件时 
        "inner" => false,
        //要输出的文件 路径
        "file" => "",

        //请求文件的 后缀名，必须在 exps 数组中
        //"ext" => "",
        //开关信息组成的 key 例如：dev-esm-browser 必须在 *.lib 文件中定义了
        //"key" => "",
        //当前请求的 文件信息 与 stdLibfile 结构一致
        //"info" => [],

    ];

    /**
     * 依赖的 其他 Resource 资源实例
     * 例如：其他组件库实例
     */
    public $dependency = [
        //Lib instance,
        //...
    ];

    //指定的 SPF-Theme 主题资源实例
    public $theme = null;

    

    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //读取并处理 库定义数据
        $ctx = Conv::j2a($this->content);
        //合并 格式化为 标准图标库参数形式
        $ctx = Arr::extend(static::$stdLib, $ctx);

        //处理 stdFoobar 参数
        $this->fixStdProperties($ctx);

        //获取元数据
        $this->getLibMeta($ctx);
        $meta = $this->meta;

        //建立依赖的 资源实例
        $deps = $ctx["dependency"] ?? [];
        if (!Is::nemarr($deps)) $deps = [];
        foreach ($deps as $depi) {
            //实例化 依赖的其他 库
            $depo = Resource::create($depi);
            if (!$depo instanceof Resource) {
                //依赖项必须加载
                throw new SrcException("无法加载依赖的资源 $depi", "resource/getcontent");
            }
            //写入属性
            $this->dependency[] = $depo;
        }

        /**
         * !! 读取缓存的 全部 components 组件列表
         * 写入 meta，如果不存在缓存，将创建缓存
         */
        $this->getCompList(false);

        /**
         * 将当前版本下的 components 组件名合并到 switch 开关数组中
         * 可通过 开关 控制是否需要合并这些 组件
         */
        $this->mergeCompSwitch();

        //格式化 params 并根据 export 修改 ext|mime
        $this->formatParams();
        $ps = $this->params;

        //!! content 资源内容生成 转移到 beforeExport 方法中，此处直接返回
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
        $this->formatParams();

        /**
         * 开始 读取|生成 资源内容
         */

        //params 
        $ps = $this->params;

        //请求的后缀名
        $ext = $this->ext;

        //获取当前请求的 文件信息
        $this->getLibFile();
        $lbf = $this->libfile;

        //var_dump($this->getLibFileKeys());exit;

        /**
         * 如果请求的是组件库内部文件，则结束生成，准备输出
         * 否则 根据 switch 开关状态，生成缓存文件名，然后查找缓存的文件
         */
        if ($lbf["inner"] === true) {
            //将输出内部文件，不需要读取缓存
            return $this->exportInnerFileContent();
        }

        //判断是否需要加载缓存
        if ($this->useCache() === true) {
            //尝试读取缓存
            $cnt = $this->getCacheContent();
            if (Is::nemstr($cnt)) {
                //存在缓存文件 则 使用缓存的 content
                $this->content = $cnt;
                return $this;
            }
        }

        /**
         * switch["env"] 输出环境准备 代码
         * 引用 plugin 以及 所有启用的 组件，并向 head 插入 Theme 以及 所有组件的 css 代码路径
         * 开始编译生成
         */
        $env = $ps["env"] ?? false;
        if ($env === true) {
            //切换 ext 为 js
            if ($ext!=="js") {
                $this->ext = "js";
                $this->mime = Mime::getMime("js");
            }
            //编译生成 环境准备 js 代码
            $this->buildCompEnvJs();
            //写入缓存文件
            $this->saveCacheContent();
            return $this;
        }
        
        /**
         * 开始 编译生成对应的 输出文件内容 支持将 Vue2.x 组件库编译为 js|css 文件
         */
        $lib = $ctx["lib"];
        $ext = $this->ext;
        $bm = "buildComp".Str::camel($this->ext, true)."File";
        if (!method_exists($this, $bm)) {
            //编译方法不存在
            throw new SrcException("无法编译输出组件库 $lib.$ext", "resource/getcontent");
        }
        //调用对应的编译方法，生成文件内容，并保存到 content
        $this->$bm();
        
        //写入缓存文件
        $this->saveCacheContent();

        return $this;
    }



    /**
     * 缓存处理
     * !! 使用自定义的缓存处理方法
     */

    /**
     * 根据传入的参数，获取缓存文件的 路径，不论文件是否存在
     * 缓存文件 默认保存在 当前资源文件路径下的 资源同名文件夹下
     * !! 子类必须实现此方法
     * @return String 缓存文件的 路径
     */
    protected function getCachePath()
    {
        /**
         * 读取必要的 params 参数，生成 当前请求的 缓存文件名
         * 拼接 对应的 路径
         * 得到最终 需要的 缓存文件路径
         */

        //缓存文件路径
        $cache = $this->getInnerDir("cache", true);
        if (!file_exists($cache)) {
            //缓存路径不存在，创建之
            $cache = $this->getInnerDir("", false).DS."cache";
            Path::mkdir($cache);
            $cache = $this->getInnerDir("cache", true);
            if (!file_exists($cache)) return null;
        }

        //缓存文件名
        $lbks = $this->getLibFileKeys();
        if (!Is::nemarr($lbks)) return null;
        $cfn = $lbks[0].".".$this->ext;

        //返回
        return $cache.DS.$cfn;
    }

    /**
     * 读取|写入 组件列表缓存 数据
     * 缓存文件路径： [lib_path]/[version]/cache/components.json
     * @param Array|String|null $action 传入 "path" 返回缓存路径，传入 associate array 则作为新的 组件列表数据写入缓存，传入 null 读取缓存
     * @return Array|String|Bool
     */
    protected function cacheComps($action=null)
    {
        //组件列表缓存文件
        $cp = $this->getInnerDir("cache", true);
        if (!Is::nemstr($cp)) return false;
        //缓存文件路径，不检查是否存在
        $cf = $cp.DS."components.json";

        //传入 path 返回缓存文件路径，不检查是否存在
        if (Is::nemstr($action)) {
            if ($action === "path") return $cf;
            return null;
        }

        //传入 components 组件列表，创建|更新 缓存文件
        if (Is::nemarr($action) && Is::associate($action)) {
            $json = [
                "components" => $action
            ];
            $json = Conv::a2j($json);
            //判断缓存文件是否存在
            if (!file_exists($cf)) {
                return Path::mkfile($cf, $json);
            } else {
                return file_put_contents($cf, $json);
            }
        }

        //读取缓存
        if (!file_exists($cf)) return null;
        $json = file_get_contents($cf);
        $json = Conv::j2a($json);
        $comps = $json["components"] ?? [];
        return $comps;
    }



    /**
     * 文件编译方法
     */

    /**
     * 编译生成 组件库环境准备 js 代码
     *  0   引用 plugin
     *  1   引用所有启动的 组件 js
     *  2   向 head 插入 theme 主题 以及 所有组件的 css 资源路径
     *  3   如果 esm===false 则调用 Vue.use() 方法
     * @return $this
     */
    protected function buildCompEnvJs()
    {
        //meta
        $meta = $this->meta;
        //组件库名称
        $lib = $meta["lib"];
        //组件库信息
        $libi = $this->getCompLibInfo();

        //收集 imports
        $imports = [];
        //收集 js 语句数组
        $jsrows = [];

        //查找 plugin 插件 js 文件
        $pgf = $this->getInnerFilePath("plugin/$lib.js", true);
        if (Is::nemstr($pgf) && file_exists($pgf)) {
            //创建 plugin 主文件的 资源实例
            $plugin = Resource::create($pgf, [
                "export" => "js",
                "lib" => $libi,
                "inlib" => true,
            ]);
            if (!$plugin instanceof Resource) {
                //实例化失败，报错
                throw new SrcException("无法创建 $lib 插件资源实例", "resource/export");
            }
            //获取 imports 和 rows 内容行数组
            $fjd = $plugin->getFormattedJsCode();
            //释放资源
            unset($plugin);

            //imports
            $inps = $fjd["imports"] ?? [];
            //jsrows
            $rows = $fjd["jsrows"] ?? [];

            //合并
            $this->combineJsRowsAndImports($inps, $rows, $imports, $jsrows, [
                "合并插件 ".basename($pgf)." 文件",
                "!! 不要手动修改 !!"
            ]);
        }



        //rows
        $this->rows = [];

        //生成 imports 语句
        if (Is::nemarr($imports)) {
            foreach ($imports as $var => $url) {
                $this->rowAdd("import $var from '$url';","");
            }
            $this->rowEmpty(1);
        }

        //合并 jsrows
        $this->rowAdd($jsrows);

        //esm

        //生成 content
        $this->content = $this->rowCnt();

        return $this;
    }

    /**
     * 将组件库 中多个 *.vue 单文件组件 编译为单个 js 文件
     *  0   依次生成 启用组件的资源实例，调用其 getFormattedJsCode 方式，生成 格式化的 js 代码
     *  1   统一处理 import
     *  2   依次合并 组件的 js 定义代码
     *  3   依次生成调用 Vue.component 方法的代码
     *  4   根据 esm 开关状态，生成 export 代码
     * @return $this
     */
    protected function buildCompJsFile()
    {
        //当前要处理的 组件库文件资源信息
        $lbf = $this->libfile;

        //如果要输出某个组件库内部文件
        $inner = $lbf["inner"] ?? false;
        if ($inner === true) return $this;

        //所有启用的组件
        $comps = $lbf["comps"] ?? [];
        if (!Is::nemarr($comps)) return $this;

        //switch
        $meta = $this->meta;
        $sw = $meta["switch"];
        $esm = $sw["esm"] ?? true;

        //收集各组件的 imports
        $imports = [
            //"mixinBase" => "/src/lib/vcomp/mixins/base.js",
            //...
            //如果有 变量名相同 但是 指向 url 不同的情况，使用 新变量名
        ];
        //收集各组件的 js 定义语句
        $jsrows = [
            /*
            包含所有组件的 定义 js 语句 数组
            包含必要的 注释
             */
        ];
        //收集 组件名 与 组件变量名 的 映射关系，用于 合并执行 Vue.component() 以及 export
        $vcns = [
            /*
            "pre-foo-bar" => "PreFooBar",
            ...
            */
        ];


        //准备组件资源实例化参数
        $vcps = [
            "export" => "js",
            "esm" => $esm,
            //当前组件库信息
            "lib" => $this->getCompLibInfo(),
        ];
        //依次实例化 启用的组件，合并其 格式化的 js code
        foreach ($comps as $compk => $compc) {
            //组件 *.vue 文件
            $vfp = $compc["vue"] ?? null;
            if (!Is::nemstr($vfp) || !file_exists($vfp)) continue;
            //实例化参数
            $vcpi = Arr::extend($vcps, [
                "lib" => [
                    "vcn" => $compk
                ]
            ]);
            //创建 vue 组件资源实例
            $vc = Resource::create($vfp, $vcpi);
            if (!$vc instanceof Resource) {
                //实例化失败 报错
                throw new SrcException("无法创建组件资源实例 $compk", "resource/export");
            }
            //调用组件实例的 getFormattedJsCode 方法，获取格式化的 组件 js 代码
            $fjd = $vc->getFormattedJsCode();
            //释放 资源实例
            unset($vc);

            //此组件变量名
            $vcv = $fjd["vcv"];

            //合并到 imports 和 jsrows
            $inps = $fjd["imports"] ?? [];
            $rows = $fjd["jsrows"] ?? [];
            $this->combineJsRowsAndImports($inps, $rows, $imports, $jsrows, [
                "定义组件 $vcv",
                "!! 不要手动修改 !!",
            ]);

            //保存到 vcns
            $vcns[$compk] = $vcv;
        }

        //准备生成 rows
        $this->rows = [];

        //imports
        if (Is::nemarr($imports)) {
            foreach ($imports as $inpv => $inpu) {
                $this->rowAdd("import $inpv from '$inpu';", "");
            }
            $this->rowEmpty(3);
        }

        //依次处理 jsrows
        if (Is::nemarr($jsrows)) {
            $this->rowAdd($jsrows);
        }

        //合并调用 Vue.component 方法
        $this->rowComment(
            "定义为全局组件",
            "!! 不要手动修改 !!",
        );
        $this->rowEmpty(1);
        if (Is::nemarr($vcns)) {
            foreach ($vcns as $vcn => $vcv) {
                $this->rowAdd("let ".$vcv."Comp = Vue.component('$vcn', $vcv);", "");
            }
            $this->rowEmpty(3);
        }

        //esm 输出
        if ($esm === true) {
            $this->rowComment(
                "ESM 输出",
                "!! 不要手动修改 !!"
            );
            $this->rowEmpty(1);
            if (Is::nemarr($vcns)) {
                $this->rowAdd("export default {", "");
                foreach ($vcns as $vcn => $vcv) {
                    $this->rowAdd($vcv.": ".$vcv."Comp,", "");
                }
                $this->rowAdd("}", "");
                $this->rowEmpty(3);
            }
        }

        //合并 生成 content
        $this->content = $this->rowCnt();

        //!! 已在 vue 资源实例中替换过，此处不需要模板替换
        //$this->content = $this->replaceTplsInCnt();

        return $this;
    }

    /**
     * 将多个 vue 单文件组件相关的 css|scss 内容，合并编译为 css 内容
     *  0   调用 buildCompScssFile 方法生成 scss 内容
     *  1   调用 Scss::parseScss 编译 scss 生成 css 内容
     * @return $this
     */
    protected function buildCompCssFile()
    {
        //生成 scss
        $this->buildCompScssFile();
        $scssCnt = $this->content;

        //编译
        $cssCnt = Scss::parseScss($scssCnt, false);
        $this->content = $cssCnt;

        return $this;
    }

    /**
     * 将多个 vue 单文件组件内部 css 以及对应的 scss 文件 编译为单个 scss 文件
     *  0   如果指定了 theme 主题信息，则需要先调用 theme 主题资源实例，生成 通用 scss
     *  1   如果指定了 common["css"] 通用样式文件，则依次合并这些 css|scss 文件
     *  2   依次合并启用的 组件对应的 scss 文件
     *  3   依次合并启用的 组件中 <style>...</style> 中的内容
     * @return $this
     */
    protected function buildCompScssFile()
    {
        //当前要处理的 组件库文件资源信息
        $lbf = $this->libfile;

        //如果要输出某个组件库内部文件
        $inner = $lbf["inner"] ?? false;
        if ($inner === true) return $this;

        //所有启用的组件
        $comps = $lbf["comps"] ?? [];
        if (!Is::nemarr($comps)) return $this;

        //准备 rows
        $this->rows = [];

        //meta
        $meta = $this->meta;
        //定义的 SPF-Theme 主题 *.theme 文件路径
        $thp = $meta["theme"] ?? "";
        if (Is::nemstr($thp)) {
            //从 params 中 获取可能存在的 暗黑模式开启状态
            $dark = $this->params["dark"] ?? false;
            //如果启用了 create 忽略缓存，则 theme 资源实例也会忽略缓存，重新创建并更新缓存
            $create = $this->params["create"] ?? false;
            //创建关联的 主题资源实例
            $tho = Resource::create($thp, [
                //从 params 中 获取可能存在的 暗黑模式开启状态
                "mode" => $dark===true ? "dark" : "light",
                //是否强制刷新主题缓存
                "create" => $create,
            ]);
            if (!$tho instanceof Theme) {
                //关联的 主题实例化失败
                throw new SrcException("无法实例化组件库关联的主题 $thp", "resource/export");
            }
            //保存到 $this->theme
            $this->theme = $tho;
        }

        //当前是否指定了 SPF-Theme 主题资源
        $theme = $this->theme;
        $themeScss = "";
        if ($theme instanceof Theme) {
            //调用 theme 主题资源实例的 export 方法生成 scss
            $themeScss = $theme->export([
                "export" => "scss",
                "return" => true
            ]);
        }
        if (Is::nemstr($themeScss)) {
            $this->rowComment(
                "应用组件库定义的 SPF-Theme 主题样式",
                "!! 不要手动修改 !!"
            );
            $themeScssRows = explode($this->rn, $themeScss);
            $this->rowAdd($themeScssRows);
            $this->rowEmpty(3);
        }
        
        //合并组件库定义的 common 通用 css|scss 文件，定义在 meta["common"]["css"] 数组中
        $ccss = $meta["common"]["css"] ?? [];
        if (Is::nemarr($ccss)) {
            //按顺序依次合并
            foreach ($ccss as $ci) {
                //文件路径
                $cif = $this->getInnerFilePath($ci, true);
                //文件不存在
                if (!Is::nemstr($cif)) continue;
                //ext 可能是 css|scss
                $cexti = pathinfo($cif)["extension"];
                //创建 资源实例
                $cresi = Resource::create($cif, [
                    "export" => $cexti
                ]);
                if (!$cresi instanceof Resource) {
                    //资源实例创建失败，报错
                    throw new SrcException("无法创建引用的 $cexti 资源实例 $ci", "resource/export");
                }
                //rows
                $crows = $cresi->export([
                    "return" => "rows"
                ]);
                //释放资源
                unset($cresi);
                if (!Is::nemarr($crows)) continue;
                //插入 rows
                $this->rowComment(
                    "引用 $ci 文件",
                    "!! 不要手动修改 !!"
                );
                $this->rowEmpty(1);
                $this->rowAdd($crows);
                $this->rowEmpty(3);
            }
        }

        //合并 comps 组件列表中的 css|scss
        //收集组件 *.vue 文件中定义的 style 语句
        $compCss = [];
        //收集组件对应的 scss 文件，可能存在多个组件使用一个 scss
        $compScss = [];
        //准备组件资源实例化参数
        $libi = $this->getCompLibInfo();
        $vcps = [
            "export" => "js",
            "esm" => $esm,
            //当前组件库信息
            "lib" => $libi,
        ];
        //依次处理 comps 组件列表
        foreach ($comps as $compk => $compc) {
            //收集对应的 scss
            $vscssi = $compc["scss"] ?? null;
            if (Is::nemstr($vscssi) && !in_array($vscssi, $compScss) && file_exists($vscssi)) {
                $compScss[] = $vscssi;
            }

            //组件 *.vue 文件
            $vfp = $compc["vue"] ?? null;
            if (!Is::nemstr($vfp) || !file_exists($vfp)) continue;
            //实例化参数
            $vcpi = Arr::extend($vcps, [
                "lib" => [
                    "vcn" => $compk
                ]
            ]);
            //创建 vue 组件资源实例
            $vc = Resource::create($vfp, $vcpi);
            if (!$vc instanceof Resource) {
                //实例化失败 报错
                throw new SrcException("无法创建组件资源实例 $compk", "resource/export");
            }
            //调用组件实例的 getFormattedJsCode 方法，获取格式化的 组件 js 代码
            $fjd = $vc->getFormattedJsCode();
            //释放 资源实例
            unset($vc);

            //收集 组件内部 css
            $vcss = $fjd["css"] ?? "";
            if (Is::nemstr($vcss)) $compCss[] = $vcss;
        }

        //合并 scss
        if (Is::nemarr($compScss)) {
            foreach ($compScss as $scssi) {
                //创建资源实例
                $sresi = Resource::create($scssi, [
                    "export" => "scss"
                ]);
                if (!$sresi instanceof Resource) {
                    //资源实例创建失败，报错
                    throw new SrcException("无法创建组件使用的 scss 资源实例 $scssi", "resource/export");
                }
                $srows = $sresi->export([
                    "return" => "rows"
                ]);
                //释放资源
                unset($sresi);
                if (!Is::nemarr($srows)) continue;
                //插入rows
                $this->rowComment(
                    "合并组件 scss 文件 ".Path::rela($scssi),
                    "!! 不要手动修改 !!"
                );
                $this->rowEmpty(1);
                $this->rowAdd($srows);
                $this->rowEmpty(3);
            }
        }

        //合并组件内部定义的 css
        if (Is::nemarr($compCss)) {
            $this->rowComment(
                "合并组件内部定义的 style",
                "!! 不要手动修改 !!"
            );
            $this->rowEmpty(1);
            $this->rowAdd($compCss);
            $this->rowEmpty(3);
        }

        //合并 rows
        $this->content = $this->rowCnt();

        //模板替换
        $this->content = static::replaceTplsInCode($this->content, $this->tpls, $libi);   //$this->replaceTplsInCnt();

        return $this;
    }

    /**
     * 输出组件库内部文件资源 生成 content
     * @return $this
     */
    protected function exportInnerFileContent()
    {
        //当前要处理的 组件库文件资源信息
        $lbf = $this->libfile;

        //如果要输出某个组件库内部文件
        $inner = $lbf["inner"] ?? false;
        if ($inner !== true) return $this;

        $ext = $this->ext;
        $ps = $this->params;

        //将输出内部文件
        $file = $lbf["file"];
        //资源实例化参数
        $fps = [
            "export" => $ext,
            "esm" => $ps["esm"] ?? false,
            //所有组件内部文件资源实例化参数中，都附加 组件库信息
            "lib" => $this->getCompLibInfo(),
            //增加一个 组件库内部文件的 标记
            "inlib" => true,
        ];

        /**
         * 如果要输出的是 *.vue 组件文件资源
         * 需要额外传入 组件库信息
         */
        $fpi = pathinfo($file);
        if ($fpi["extension"]==="vue") {
            //根据 *.vue 路径 查找 组件名称 pre-foo-bar
            $vcn = $this->getCompNameByFilePath($file);
            if (!Is::nemstr($vcn)) {
                //未找到对应的 组件名，报错
                throw new SrcException("未找到组件 ".basename($file), "resource/export");
            }
            $fps["lib"]["vcn"] = $vcn;
        }

        //创建资源实例
        $res = Resource::create($file, $fps);
        if (!$res instanceof Resource) {
            //内部文件 资源实力创建失败
            $fp = Path::rela($file);
            if (!Is::nemstr($fp)) $fp = basename($file);
            throw new SrcException("无法创建内部资源实例 $fp", "resource/export");
        }
        //获取 content
        $this->content = $res->export([
            "return" => true
        ]);
        return $this;
    }

    /**
     * 合并某个格式化的 js 文件内容，已拆分出 curImports 和 curJsrows 数组
     * 将其合并到 现有的 imports 和 jsrows 数组
     * !! 处理 import 冲突的情况
     * @param Array $curImports 当前 js 内容中的 imports 数组
     * @param Array $curJsrows 当前 js 内容中的 jsrows 数组
     * @param Array $imports 已有的 imports 数组 引用
     * @param Array $jsrows 已有的 jsrows 内容行数组 引用
     * @param Array $comment js 行数组中插入的 注释 行数组
     * @return Array 返回合并后的 imports 和 jsrows 数组
     *  [
     *      "imports" => [],
     *      "jsrows" => []
     *  ]
     */
    protected function combineJsRowsAndImports($curImports, $curJsrows, &$imports, &$jsrows, $comment=[])
    {
        //传入的参数检查
        if (!Is::nemarr($curImports)) $curImports = [];
        if (!Is::nemarr($curJsrows)) $curJsrows = [];

        //合并 imports
        //可能存在的 需要 替换的 import 变量名
        $ivars = [
            /*
            "原变量名" => "新变量名",
            ...
            */
        ];
        if (Is::nemarr($curImports)) {
            foreach ($curImports as $var => $url) {
                //如果 不存在同名 import 直接添加
                if (!isset($imports[$var])) {
                    $imports[$var] = $url;
                    continue;
                }

                //存在同名 import 则检查 url 是否一致
                if ($imports[$var] === $url) {
                    //url 也相同，表示这是同一个 import 资源，跳过
                    continue;
                }

                //同变量名，但是不同 url 指向，这是一个 import 冲突

                //首先反查 url 是否已存在于 imports 
                if (in_array($url, array_values($imports))) {
                    //url 已存在，表示已有别的 js 代码 import 了此 url，查找其 变量名
                    $nvar = array_search($url, $imports);
                    if (!isset($curImports[$nvar])) {
                        //如果其他 js 代码的 import 变量名，不在此 js 代码的 imports 变量名列表中，则可以使用其他 js 代码的 import 变量名
                        $ivars[$var] = $nvar;
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
                //写入 imports
                $imports[$nvar] = $url;
                //写入 ivars 记录变更
                $ivars[$var] = $nvar;
            }
        }

        //替换 curJsrows 中的 已变更的 变量名
        if (Is::nemarr($ivars)) {
            $glup = "__RN__";
            $curjs = implode($glup, $curJsrows);
            foreach ($ivars as $ovar => $nvar) {
                $curjs = str_replace($ovar, $nvar, $curjs);
            }
            $curJsrows = explode($glup, $curjs);
        }

        //插入 注释
        if (Is::nemarr($comment)) {
            if (count($comment)===1) {
                $comment[0] = "\/** ".$comment[0]." **\/";
            } else {
                $comment = array_map(function($crow) {
                    return " * $crow";
                }, $comment);
                array_unshift($comment, "/**");
                $comment[] = " */";
            }
            $comment[] = "";
            //插入
            $curJsrows = array_merge($comment, $curJsrows, ["","",""]);
        }

        //插入 jsrows
        $jsrows = array_merge($jsrows, $curJsrows);

        //返回
        return [
            "imports" => $imports,
            "jsrows" => $jsrows
        ];
    }



    /**
     * 工具方法
     */

    /**
     * 从 *.lib 文件内容中获取 meta 元数据 保存到 meta 属性
     * @param Array $ctx *.lib 文件内容
     * @return Array $this
     */
    protected function getLibMeta($ctx=[])
    {
        //组件库 *.lib 文件内容都作为 元数据
        $this->meta = $ctx;
        return $this;
    }

    /**
     * 获取当前请求的 库文件信息 保存到 libfile 属性
     * !! 覆盖父类
     * @param Array $ctx
     * @return $this
     */
    protected function getLibFile($ctx=[])
    {
        $ps = $this->params;

        //获取当前请求的 文件信息
        $ver = $this->getLibVer();
        //meta
        $meta = $this->meta;
        $lib = $meta["lib"];

        //判断是否要输出 某个组件库内部文件
        if (isset($ps["inner"]) && $ps["inner"]===true) {
            if (isset($ps["file"]) && Is::nemstr($ps["file"])) {
                //查找对应 内部文件路径
                $file = trim($ps["file"], "/");
                if (substr($file, 0, 11)==="components/") {
                    //请求的是 components 文件夹下文件
                    $fp = $this->queryCompInnerFile($file);
                    if (!file_exists($fp)) {
                        //未找到要访问的 内部文件
                        throw new SrcException("组件库 $lib 未找到内部文件 ".$file, "resource/getcontent");
                    }
                } else {
                    //请求的是其他路径下 文件
                    $libp = $this->getInnerDir("", true);
                    if (!Is::nemstr($libp)) {
                        //跟路径不存在，表示当前版本的 组件库文件夹不存在，报错
                        throw new SrcException("组件库 $lib 未找到版本 $ver", "resource/getcontent");
                    }
                    $file = str_replace("/",DS,$file);
                    //尝试路径
                    $fp = $libp.DS.$file;
                    if (!file_exists($fp)) {
                        //未找到要访问的 内部文件
                        throw new SrcException("组件库 $lib 未找到内部文件 ".str_replace(DS,"/",$file), "resource/getcontent");
                    }
                }

                //找到内部文件
                $this->libfile = [
                    "ver" => $ver,
                    "comps" => [],
                    "inner" => true,
                    "file" => $fp
                ];
                return $this;
            }
            //缺少参数
            throw new SrcException("访问组件库 $lib 内部文件缺少必要参数", "resource/getcontent");
        }

        //正常输出
        //获取 根据 开关参数 筛选后的 组件列表
        $comps = $this->getCompList(true);
        $this->libfile = [
            "ver" => $ver,
            "comps" => $comps,
            "inner" => false,
            "file" => ""
        ];
        
        return $this;
    }

    /**
     * 从 cdn 获取库文件内容 保存到 content
     * !! 覆盖父类
     * @param Array $ctx
     * @return $this
     */
    protected function getLibFromCdn($ctx=[])
    {
        //TODO:
    }

    /**
     * 根据 params 中的 开关状态，生成请求的文件 keys
     * 如：dev=yes&esm=no&browser=yes       --> dev-esm!-browser  或者  dev-browser
     * !! 覆盖父类
     * @return Array 多个可能 keys 数组
     */
    public function getLibFileKeys()
    {
        $meta = $this->meta;
        $ps = $this->params;
        $switch = $meta["switch"] ?? [];
        $sw = [];
        foreach ($switch as $k => $v) {
            $sw[$k] = (isset($ps[$k]) && is_bool($ps[$k])) ? $ps[$k] : $v;
        }
        $ns = [];
        if ($sw["env"]===true) $ns[] = "env";
        if ($sw["esm"]===true) $ns[] = "esm";
        $load = $sw["load"] ?? false;
        if ($load === true) $ns[] = "load";
        foreach ($sw as $k => $v) {
            if (in_array($k, ["esm","load"])) continue;
            if ($v === true && $load === true) {
                $ns[] = $k;
            } else if ($v === false && $load === false) {
                $ns[] = $k."!";
            }
        }
        
        //生成 key
        $key = implode("-", $ns);

        //返回 [ key ]
        return [ $key ];
    }

    /**
     * 将 组件名 去除 prefix 前缀后，合并到 stdLib["switch"] 以及 stdParams 开关数组中，
     * 可以通过开关单独控制某个 组件的 加载状态
     * 例如：有这些组件：
     * pre-button       --> 开关名 button
     * pre-vg-foo       --> 开关名 vg_foo
     * @return $this
     */
    protected function mergeCompSwitch()
    {
        //获取所有单文件组件，不筛选
        $comps = $this->getCompList(false);
        if (!Is::nemarr($comps)) return $this;

        //组件是否默认加载
        $dl = $this->getDftLoad();

        //合并开关
        foreach ($comps as $compn => $compc) {
            //组件名 pre-vg-foo 转换为 开关名 vg_foo
            $swn = $compc["switch"];
            //合并
            if (!isset(static::$stdLib["switch"][$swn])) {
                static::$stdLib["switch"][$swn] = $dl;
            }
            if (!isset(static::$stdParams[$swn])) {
                static::$stdParams[$swn] = $dl;
            }
        }

        //switch 开关项目写入 meta
        $this->meta["switch"] = static::$stdLib["switch"];

        return $this;
    }

    /**
     * 获取 组件库 单文件组件 列表
     * @param Bool $filter 是否根据 params 筛选要加载的组件 列表，默认 false 还可以指定为 true 或 closure
     * @return Array
     *  [
     *      "pre-button" => [
     *          "file" => "button *.vue 文件名，不带后缀",
     *          "switch" => "此组件的开关名，foo_bar_jaz 形式",
     *          "vue" => "*.vue 文件路径",
     *          "scss" => "*.scss 文件路径",
     *      ],
     *      
     *  ]
     */
    protected function getCompList($filter=false)
    {
        $comps = [];

        //先检查内存中是否存在 缓存
        $meta = $this->meta;
        $comps = $meta["components"] ?? [];
        if (!Is::nemarr($comps) || !Is::associate($comps)) $comps = [];

        //再检查 全部组件列表缓存
        if (!Is::nemarr($comps) && $this->useCache() === true) {
            //读取缓存的 组件列表数据
            $comps = $this->cacheComps();
            if (!Is::nemarr($comps) || !Is::associate($comps)) {
                $comps = [];
            } else {
                //写入 meta
                $this->meta["components"] = $comps;
            }
        }

        //如果未获取到缓存，则开始生成
        if (!Is::nemarr($comps)) {
            //components 文件夹
            $compp = $this->getInnerDir("components", true);
            //不存在 则返回 []
            if (!Is::nemstr($compp)) return [];

            //组件名前缀
            $meta = $this->meta;
            $prefix = $meta["prefix"] ?? ($meta["lib"] ?? "");
            $prelen = strlen($prefix)+1;

            //递归收集 components 文件夹下所有 *.vue 单文件组件
            $comps = Path::flat($compp, $prefix, "-", "vue");
            if (!Is::nemarr($comps)) return [];

            //统一处理 组件 key 生成对应的 文件名|文件路径
            $ncomps = [];
            foreach ($comps as $compn => $compp) {
                $compk = str_replace("_","-",$compn);
                $vf = $compp;
                $pi = pathinfo($vf);
                $fn = $pi["filename"];
                //去除 compk 中可能存在的 vg-foo-foo --> vg-foo
                $compk = str_replace("-$fn-$fn", "-".$fn, $compk);
                //组件的 switch 开关名 foo_bar_jaz 形式
                $swn = str_replace("-","_", substr($compk, $prelen));
                //对应的 scss 文件应保存在 同路径下，同名 scss 文件
                $scf = $pi["dirname"].DS.$fn.".scss";
                //如果不存在 scss，则向上查找一级，例如：comps/table/row.scss 不存在 则查找 comps/table/table.scss
                if (!file_exists($scf)) {
                    $spi = pathinfo($pi["dirname"]);
                    $scf = $spi["dirname"].DS.$spi["filename"].DS.$spi["filename"].".scss";
                }
                //作为异步组件时，外部访问的 url
                $compi = [
                    "compk" => $compk,
                    "file" => $fn,
                    "switch" => $swn,
                    "vue" => $vf,
                    "scss" => file_exists($scf) ? $scf : null,
                ];
                $ncomps[$compk] = $compi; 
            }
            $comps = $ncomps;

            //写入缓存
            $this->cacheComps($comps);

            //写入内存
            $this->meta["components"] = $comps;
        }

        //不筛选
        if (!$filter) return $comps;

        //按 switch 筛选
        $ps = $this->params;
        $ncomps = [];
        foreach ($comps as $compk => $compc) {
            $swn = $compc["switch"];
            //判断 params 中是否指定了要加载此组件
            if (isset($ps[$swn]) && $ps[$swn]===true) {
                $ncomps[$compk] = $compc;
            }
        }

        return $ncomps;
    }

    /**
     * 获取 组件库 所有组件的 scss 样式文件列表
     * @param Bool $filter 是否根据 params 筛选要加载的组件 列表，默认 false 还可以指定为 true 或 closure
     * @return Array
     */
    protected function getCompScssList($filter=false)
    {
        //components 文件夹
        $compp = $this->getInnerDir("components", true);
        //不存在 则返回 []
        if (!Is::nemstr($compp)) return [];

        //组件名前缀
        $meta = $this->meta;
        $prefix = $meta["prefix"] ?? ($meta["lib"] ?? "");

        //递归收集 components 文件夹下所有 *.scss
        $comps = Path::flat($compp, $prefix, "-", "scss");
        if (!Is::nemarr($comps)) return [];

        //不筛选
        if (!$filter) return $comps;

        //按 switch 筛选
        $ps = $this->params;
        $comps = array_filter($comps, function($comp) use ($ps) {
            //组件名 pre-vg-foo 转换为 开关名 vg_foo
            $swn = $this->compnToSwn($compn);
            //针对 某个具体组件的 scss 文件
            if (isset($ps[$swn]) && $ps[$swn]===true) return true;
            //针对 某个 组件组的 共用 scss 文件，在 params 中查找是否带 swn_ 前缀的 组件项
            $swnlen = strlen($swn)+1;
            foreach ($ps as $pk => $pv) {
                if (!is_bool($pv) || $pv!==true) continue;
                if (substr($pk, 0, $swnlen) === $swn."_") return true;
            }
            return false;
        });

        return $comps;
    }

    /**
     * 根据 某个组件的 实际文件地址，获取其在 meta["components"] 组件列表中的 key，也就是 组件名称 pre-foo-bar 形式
     * @param String $path 组件的实际文件路径
     * @return String|null
     */
    protected function getCompNameByFilePath($path)
    {
        if (!Is::nemstr($path)) return null;
        //组件列表
        $comps = $this->meta["components"] ?? [];
        if (!Is::nemarr($comps)) $comps = [];
        //查找
        foreach ($comps as $compk => $compc) {
            // vue 文件
            $vf = $compc["vue"] ?? null;
            if (!Is::nemstr($vf)) continue;
            //判断
            if (Path::fix($vf) === Path::fix($path)) {
                //找到，返回 compk
                return $compk;
            }
        }

        return null;
    }

    /**
     * 根据 params 中的 load 开关，决定所有组件是 默认加载，还是 默认不加载
     * @return Bool true 默认加载，false 默认不加载
     */
    protected function getDftLoad()
    {
        $ps = $this->params;
        $load = isset($ps["load"]) && $ps["load"] === true;
        // load === true 默认全部不加载
        return $load !== true;
    }

    /**
     * 根据请求的 components 文件夹下的 路径，获取正式文件路径
     * 例如：
     * components/pre-button.[vue|scss|css|js]          --> /components/button/button.[vue|scss]
     * components/pre-table-row.[vue|scss|css|js]       --> /components/table/row.[vue|scss]
     * components/table/table.[vue|scss]                --> /components/table/table.[vue|scss]
     * @param String $path 请求的路径，一定以 components/ 开始
     * @return String 解析得到的 实际指向的 本地文件 *.vue | *.scss 
     */
    protected function queryCompInnerFile($path)
    {
        if (!Is::nemstr($path)) return null;
        $path = str_replace(DS,"/", $path);
        if (substr($path, 0, 11)!=="components/") return null;
        $p = substr($path, 11);
        //请求的文件后缀名
        $ext = $this->ext;
        $pi = pathinfo($path);
        //请求的文件名
        $fn = $pi["filename"];
        //请求的路径数组
        $pr = explode("/", $path);
        //当前组件库 components 目录
        $compp = $this->getInnerDir("components", true);
        if (!Is::nemstr($compp)) return null;

        $meta = $this->meta;
        //组件库名称
        $lib = $meta["lib"];
        //当前组件库的 组件名前缀
        $pre = $meta["prefix"] ?? $lib;
        //组件名连字符 -
        $glup = "-";
        if (Is::nemstr($pre)) $pre .= $glup;
        $prelen = strlen($pre);

        //访问 components/foo.vue 或 components/pre-foo.vue 形式
        if (count($pr)===2) {
            if (strpos($fn, $glup)===false) {
                $fp = $compp.DS.$fn.".".$ext;
                if (file_exists($fp)) return $fp;
                return null;
            } else {
                //以 pre-foo.* 形式请求时，当请求的是 js|css 文件时，实际查找 vue|scss 文件
                if ($ext==="js") $ext = "vue";
                if ($ext==="css") $ext = "scss";

                //从 缓存的 组件列表数组 中查找 pre-foo-bar
                $comps = $this->getCompList(false);
                if (isset($comps[$fn])) {
                    $comp = $comps[$fn];
                    $fp = $comp[$ext] ?? null;
                    return file_exists($fp) ? $fp : null;
                }
                return null;
            }
        }

        //访问 components/foo/bar/jaz.scss|css|js|... 实际路径形式
        $fp = $compp.DS.implode(DS, array_slice($pr, 1));
        if (file_exists($fp)) return $fp;
        return null;
    }

    /**
     * 生成此组件库的相关信息，用于在实例化 库中某个组件 *.vue 文件资源时 传入此信息作为 额外的实例化参数 lib
     * @return Array
     */
    public function getCompLibInfo()
    {
        //读取 *.lib 文件内容
        $ctx = Conv::j2a(file_get_contents($this->real));
        //meta
        $meta = $this->meta;
        //组件库名称
        $lib = $meta["lib"];

        return [
            //组件库 *.lib 文件路径
            "file" => $this->real,
            //组件库 *.lib 文件内容 []
            "ctx" => $ctx,
            //组件库根路径，本地文件夹路径
            "dir" => dirname($this->real).DS.$lib,
            //组件库名称
            "lib" => $lib,
            //组件库版本
            "ver" => $this->getLibVer(),
            //组件库定义的 组件名称前缀
            "pre" => $ctx["prefix"] ?? $lib,
            //组件库外部访问 url 前缀
            "urlpre" => $this->getLibUrlPrefix(),
        ];

    }



    /**
     * 静态方法
     */

    /**
     * 默认的 Lib 类响应处理方法，
     * !! 覆盖父类，定义了 VueCompLib 子类的 response 响应逻辑
     * @param Array $args URI 参数数组
     * @param Object $opt 经过 Lib::response 方法处理 $args 得到的参数，子类不需要再次重复处理，只需要实现自有的处理方法
     * @return Mixed
     */
    public static function customResponse($args, $opt)
    {

        /**
         * 输出 Vue2.x 组件库资源的 方法
         * 
         * 请求方法：
         * /[app_name/]src/lib/[foo/bar/][vcomp_name][.js|css]
         * /[app_name/]src/lib/[foo/bar/][vcomp_name]/[load-button-icon-vg_foo|icon!-vg_foo!].[js|css]
         * /[app_name/]src/lib/[foo/bar/][vcomp_name]/[@|latest|1.2.3][.js|css]
         * /[app_name/]src/lib/[foo/bar/][vcomp_name]/[@|latest|1.2.3]/[load-button-icon-vg_foo|icon!-vg_foo!].[js|css]
         * /[app_name/]src/lib/[foo/bar/][vcomp_name]/[plugin|components|...]/[path/to/file].[js|scss|vue|...]
         * /[app_name/]src/lib/[foo/bar/][vcomp_name]/[@|latest|1.2.3]/[plugin|components|...]/[path/to/file].[js|scss|vue|...]
         * /[app_name/]src/lib/[foo/bar/][vcomp_name]/pre-foo-bar.vue
         * /[app_name/]src/lib/[foo/bar/][vcomp_name]/[@|latest|1.2.3]/pre-foo-bar.vue
         */

        //拼接请求的 路径字符串
        $req = $opt->req;

        //解析路径
        $pi = $opt->pi;
        $ext = $opt->ext;
        $fn = $opt->fn;
        $lp = $opt->lp;
        //lib 资源实例化参数
        $ps = $opt->ps;
        if (Is::nemstr($ext)) $ps["export"] = $ext;
        //已找到的 *.lib 文件路径
        $libf = $opt->libf;
        //找到 *.lib 文件后，剩余的 右侧路径数组，可能包含 ver|switch|内部文件 数据
        $lpr = $opt->lpr;
        //version 版本数据
        $ver = null;
        //switch 开关状态
        $sw = [];

        //处理剩余的右侧路径 可能包含 ver|switch|内部文件 数据
        if (!empty($lpr)) {
            //第一位参数，可能是 ver|switch|标准组件库文件夹
            if (in_array($lpr[0], ["@","latest"]) || strpos($lpr[0],".")!==false || is_numeric($lpr[0])) {
                //第一位是 ver 参数
                $ver = $lpr[0];
                //去除第一位参数
                array_shift($lpr);
            }
            if (!empty($lpr)) {
                //剩余的 路径数组 不为空
                if (in_array($lpr[0], static::$stdDirs)) {
                    //第一位是 标准组件库文件夹，表示访问某个 内部文件
                    $ps["inner"] = true;
                    $ps["file"] = implode("/", $lpr).".".$ext;
                } else if (count($lpr)===1 && $ext === "vue") {
                    //第一位是 pre-foo-bar 形式的 vue 组件访问方式
                    $ps["inner"] = true;
                    $ps["file"] = "components/".$lpr[0].".".$ext;
                } else {
                    //第一位是 switch 参数
                    if (count($lpr)>1) {
                        //此时，剩余路径数组长度不应大于 1
                        Response::insSetCode(404);
                        return null;
                    }
                    $sw = explode("-", $lpr[0]);
                }
            }
        }
        
        //准备 Lib 资源类实例化参数
        if (Is::nemstr($ver)) $ps["ver"] = $ver;
        if (!empty($sw)) {
            foreach ($sw as $swi) {
                $swv = substr($swi, -1)!=="!";  //strpos($swi,"!")===false;
                $swk = $swv ? $swi : substr($swi, 0, -1);  //str_replace("!","",$swi);
                $ps[$swk] = $swv;
            }
        }

        //创建 Lib 资源实例
        $libo = Resource::create($libf, $ps);
        if (!$libo instanceof Resource) {
            //报错
            throw new SrcException("$libf 文件资源无法实例化", "resource/instance");
        }
        
        //将 Response 输出类型 改为 src
        Response::insSetType("src");
        //输出资源
        return $libo;
    }
}
