<?php
/**
 * Vue2.x 单文件组件 资源类
 * 支持输出：
 *      0   vue 原始文件内容
 *      1   Vue.component({ ... }) 形式的 组件定义 js 语句
 * 
 * 单文件组件 必须遵循下列语法：
 * 
 * foo.vue
 * 
 * <profile>
 * # 建议自定义 profile 代码块，内部使用 json 定义语句，定义一些 此组件相关的参数 例如：权限，标题，排序 等
 * {
 *      "name": "...",
 *      "sort": "...",
 *      "auth": [],
 *      ...
 * }
 * </profile>
 * 
 * <template> ... </template>
 * 
 * <script>
 * 
 * import mixinFooBar from 'mixins/foo/bar.js';             # 组件库内部资源 例如：mixin|其他组件 必须使用相对路径
 * import PreCgFooBar from 'components/cg/foo-bar.vue';     
 * import externalLib from '/src/lib/external.js';          # 外部资源 必须使用 完整的 或 /src 开头的 url
 * 
 * export default {     # 组件定义必须使用 此语句作为开始，并单独为一行
 * 
 * 
 * }
 * 
 * </script>
 * 
 * <style>
 * 组件样式建议通过 关联 scss 定义，不要在组件文件内部定义
 * </style>
 * 
 * 
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

class Vue extends ParsablePlain 
{
    /**
     * 当前资源类型是否定义了 factory 工厂方法，如果是，则在实例化时，通过 工厂方法 创建资源实例，而不是 new 
     * !! 针对存在 资源自定义子类 的情况，如果设为 true，则必须同时定义 factory 工厂方法
     * !! 覆盖父类
     * 
     * 复合资源默认开启 工厂方法，允许在当前 *.theme|*.icon|*.lib|... 文件中定义 class 项目，指向自定义的 ParsablePlain 子类
     */
    public static $hasFactory = false;

    
    /**
     * 此类型纯文本资源的 注释符 [ 开始符号, 每行注释的开头符号, 结尾符号 ]
     * !! 覆盖父类
     */
    public $cm = ["/**", " * ", " */"];

    /**
     * 此类型的纯文本资源，如果可用 import 语句，则 指定 import 语法
     * 默认 为 null，表示不处理 或 不适用 import 语法
     * js 文件的 import 语句不在此处处理，直接输出
     * !! 各子类可以定义各自的 import 语句语法 正则
     */
    public $importPattern = "/import\s+([a-zA-Z0-9_\-]+)\s+from\s+['\"](.+)['\"];?/";

    /**
     * 定义 可用的 params 参数规则
     * 参数项 => 默认值
     */
    protected static $stdParams = [
        //当前组件的 完整名称 pre-foo-bar 形式
        "name" => "",
        //"create" => false,
        //输出文件的 类型 css|scss
        "export" => "js",
        //可指定要合并输出的 其他 scss 文件
        //"use" => "",
        //是否忽略 import 默认 false
        "noimport" => false,
        //是否以 esm 形式输出
        "esm" => true,
        
        /**
         * 可在 vue 组件资源实例化时，传入此组件所属组件库的 相关信息
         * 这样就不需要执行消耗资源的 getVueCompLibInfo 方法
         * !! 这个参数是 array 无法通过 url 传递，因此通常是 通过组件库访问某个组件，由组件库实例化组件资源时 额外传入此参数
         */
        "lib" => [
            /*
            # 组件库 *.lib 文件路径
            "file" => "",
            # 组件库 *.lib 文件内容 []
            "ctx" => [],
            # 组件库根路径，本地文件夹路径
            "dir" => "",
            # 组件库名称
            "lib" =>"",
            # 组件库版本
            "ver" =>"",
            # 组件库定义的 组件名称前缀
            "pre" =>"",
            # 组件库外部访问 url 前缀
            "urlpre" => "",

            # 传入 此组件在组件库中的 组件名 pre-foo-bar 形式
            "vcn" => "",
            */
        ],
    ];

    /**
     * 定义支持的 export 类型，必须定义相关的 createFooContent() 方法
     * 必须是 Mime 支持的 文件后缀名
     * !! 覆盖父类
     */
    protected static $exps = [
        "js", "vue",
    ];

    /**
     * 定义 在 *.vue 文件中可使用的 字符串模板，以及其对应的 meta 数组中的数据
     */
    protected $tpls = [
        //此组件所在组件库 外部访问的 url 前缀，通常用于 import url
        "__URLPRE__" => "lib/urlpre",
        //组件库定义的 组件名称前缀，通常用于 style 样式代码中的 样式类名称
        "__PRE__" => "lib/pre",
        //用于组件模板代码块中，代替 组件名称前缀，以便可以方便的 在不同的使用场景下，切换组件名称前缀
        //例如：<PRE@-button>...</PRE@-button> 替换为 <pre-button>...</pre-button>
        "PRE@" => "lib/pre",
        //"<@-" => ["lib/pre", "<%-"],
        //"</@-" => ["lib/pre", "</%-"],
        //此组件的 名称 pre-foo-bar 形式，用于 js 代码中
        "__VCN__" => "name",
        //此组件的 变量名 PreFooBar 形式，用于 js 代码中
        "__VCV__" => "var",
    ];

    /**
     * 当前 组件的 meta 元数据
     */
    public $meta = [
        //profile 通过 <profile>{ json 数据 }</profile> 自定义的代码块数据
        "profile" => [],

        //当前组件所在 VueCompLib 组件库的相关信息，如果是手动输入 content 创建的组件，则没有此信息
        "lib" => null,

        //组件名称 pre-foo-bar 形式
        "name" => "",
        //组件名称 PreFooBar 形式
        "var" => "",
    ];

    /**
     * 当前组件的 各代码块解析结果
     */
    //完整的 *.vue 代码
    public $vueContent = "";
    //如果定义了 profile 
    public $profile = [];
    //模板代码块 包含在 <template>...</template> 中的代码块
    public $template = [
        //模板内容，不包含标签
        "content" => "",
        //模板内容，行数组
        "rows" => [],
    ];
    //js 代码块
    public $script = [
        //js 代码内容，不包含标签
        "content" => "",
        //import 语句数组
        "imports" => [],
        //其他 js 语句数组
        "rows" => [],
    ];
    //样式代码块
    public $style = [
        //样式代码内容，不包含标签
        "content" => "",
        //样式内容，行数组
        "rows" => [],

    ];
    //自定义语言块 可通过 <foo>...</foo> 自定义代码块
    public $custom = [];    



    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类，如果需要，Plain 子类可以覆盖此方法
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //标准化 params
        $this->formatParams();

        //解析此组件的 所在 组件库的信息，存入 meta
        $libi = $this->params["lib"] ?? [];
        if (Is::nemarr($libi)) {
            //外部传入了 组件库信息
            $this->meta["lib"] = $libi;
            $this->meta["name"] = $libi["vcn"];
        } else {
            //未传入，则开始自行查找
            $this->meta["lib"] = $this->getVueCompLibInfo();
            $vcn = $this->getVueCompName();
            $this->meta["lib"]["vcn"] = $vcn;
            $this->meta["name"] = $vcn;
        }
        //组件名称 pre-foo-bar 转为 PreFooBar 形式
        $vcn = $this->meta["lib"]["vcn"] ?? null;
        if (Is::nemstr($vcn)) {
            $this->meta["var"] = Str::camel($vcn, true);
        }

        //解析之前 先替换字符串模板，替换后的 content 保存到 vueContent 中
        $this->replaceTplsInVueContent();

        //解析 vueContent
        $this->parseProfile();
        $this->parseTemplate();
        $this->parseScript();
        $this->parseStyle();

        //var_dump($this->getFormattedJsCode());exit;

        //var_dump($this->meta);
        //var_dump($this->profile);
        //var_dump($this->template);
        //var_dump($this->script);
        //var_dump($this->style);
        //exit;

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
        //调用 Plain 父类的默认方法
        return $this->plainBeforeExport($params);
    }



    /**
     * 不同 export 类型，生成不同的 content
     * !! 子类可以根据 exps 中定义的可选值，实现对应的 createFooContent() 方法
     * @return $this
     */
    //生成 JS content
    protected function createJsContent()
    {
        //准备
        $this->rows = [];

        //js 语句
        $js = $this->script;
        $jsImports = $js["imports"] ?? [];
        $jsRows = $js["rows"] ?? [];


        //插入 import 语句
        $this->rowAdd($jsImports);
        $this->rowEmpty(1);
        //js 语句写入 rows
        $this->rowAdd($jsRows);
        //定义模板
        $defTemp = $this->defineTemplateJs();
        if (Is::nemstr($defTemp)) $this->rowAdd($defTemp, "");
        //定义 profile
        $defProf = $this->defineProfileJs();
        if (Is::nemstr($defProf)) $this->rowAdd($defProf, "");
        //调用 Vue.component() 语句
        $callVc = $this->callVueComponentJs();
        if (Is::nemstr($callVc)) $this->rowAdd($callVc, "");
        //插入 <style>...</style>
        $defSty = $this->defineStyleTagJs();
        if (Is::nemstr($defSty)) $this->rowAdd($defSty, "");
        //export
        $defExp = $this->defineExportJs();
        if (Is::nemstr($defExp)) $this->rowAdd($defExp, "");
        //空行
        $this->rowEmpty(1);

        //创建 content
        $this->content = $this->rowCnt(false);

        return $this;
    }



    /**
     * 统一替换 vue 组件文件中的 字符串模板，替换为对应的 meta 中的数据
     * 字符串模板定义在 $this->tpls 数组中
     * @return String 返回替换后的 vue 文件内容，保存到 $this->vueContent 中
     */
    protected function replaceTplsInVueContent()
    {
        //当前 *.vue 文件实际代码内容
        $cnt = $this->content;
        //tpls 定义的字符串模板
        $tpls = $this->tpls;
        //meta 元数据
        $meta = $this->meta;

        //调用 Plain::replaceTplsInCode 方法 替换字符串 模板
        $cnt = static::replaceTplsInCode($cnt, $tpls, $meta);

        //依次执行模板替换
        /*foreach ($tpls as $tpl => $tpv) {
            if (Is::nemstr($tpv)) {
                $data = Arr::find($meta, $tpv);
                $stp = null;
            } else if (Is::nemarr($tpv) && Is::indexed($tpv) && count($tpv)>=2) {
                $data = Arr::find($meta, $tpv[0]);
                //需要二次替换，替换其中的 %
                $stp = $tpv[1];
            }
            //替换数据必须是非空字符串
            if (!Is::nemstr($data)) continue;
            //如果需要二次替换，先将 data 替换进入 二次替换字符串中
            if (Is::nemstr($stp) && strpos($stp, "%")!==false) {
                $data = str_replace("%", $data, $stp);
            }
            //替换字符串
            $cnt = str_replace($tpl, $data, $cnt);
        }*/

        //将替换后的 content 存入 vueContent
        $this->vueContent = $cnt;

        return $cnt;
    }



    /**
     * vue 文件解析 得到各代码块，保存到对应属性
     * @return $this
     */
    //解析 第一个 template 模板代码块
    protected function parseTemplate()
    {
        //解析
        $temp = $this->parseStaticNode("template");
        //拆分行
        $rows = explode($this->rn, $temp);
        //合并为单行
        $rows = array_filter($rows, function($row) {
            return Is::nemstr($row);
        });
        $cnt = implode("",$rows);
        //去除 多个空格
        $cnt = preg_replace("/\>\s+\</", "><", $cnt);
        $cnt = preg_replace("/\>\s+/", ">", $cnt);
        $cnt = preg_replace("/\s+\</", "<", $cnt);
        $cnt = preg_replace("/\<\s+/", "<", $cnt);
        $cnt = preg_replace("/\s+\>/", ">", $cnt);
        $cnt = preg_replace("/\s+/", " ", $cnt);
        //保存解析结果
        $this->template = [
            "content" => $cnt,
            "rows" => $rows,
        ];
        return $this;
    }
    //解析 第一个 script 代码块
    protected function parseScript()
    {
        //组件名称
        $cname = $this->cname();
        $vcn = $cname["vcn"];
        $vcv = $cname["vcv"];

        //解析
        $temp = $this->parseStaticNode("script");
        //拆分行
        $rows = explode($this->rn, $temp);
        //拆分出 import 行
        $imports = [];
        foreach ($rows as $i => $row) {
            $row = preg_replace("/\s+/", " ", trim($row));
            //拆分 import
            if (substr($row, 0, 7) === "import ") {
                //替换|补全 import 语句
                $imports[] = $this->fixImportSentence($row);
                $rows[$i] = "//__import__";
                continue;
            }
            //export default { 行转换为 定义语句
            if (substr($row, 0, 16) === "export default {") {
                $rows[$i] = "let ".$vcv." = {".substr($row, 16);
                continue;
            }
        }
        //去除标记为 //__import__ 的行
        $rows = array_filter($rows, function($row) {
            return $row !== "//__import__";
        });
        //保存解析结果
        $this->script = [
            "content" => $temp,
            "imports" => $imports,
            "rows" => $rows
        ];
        return $this;
    }
    //解析 第一个 style 代码块
    protected function parseStyle()
    {
        //解析
        $temp = $this->parseStaticNode("style");
        //分行
        $rows = explode($this->rn, $temp);
        //压缩
        $temp = $this->minifyCnt($temp, "css");
        //保存解析结果
        $this->style = [
            "content" => $temp,
            "rows" => $rows,
        ];
        return $this;

    }
    //处理 profile 数据
    protected function parseProfile()
    {
        $rtn = $this->parseCustomNode("profile");
        if (!Is::nemarr($rtn)) return $this;
        //定义了 profile
        $parse = $rtn["parse"] ?? "json";
        $temp = $rtn["temp"];
        //解析得到 profile
        $profile = Conv::j2a($temp);
        //保存结果
        $this->meta["profile"] = $profile;
        $this->profile = $profile;
        return $this;
    }

    /**
     * 解析 必须的 标签 代码块 template|script|style
     * 不包含 attr 参数
     * @param String $node 可以是 template|script|style
     * @return String|null 包含在标签内的字符串，不包含标签本身
     */
    protected function parseStaticNode($node="template")
    {
        if (!in_array($node, ["template","script","style"])) return null;
        //完整的 vueContent
        $cnt = $this->vueContent;
        //匹配
        $mt = preg_match("/\<".$node."\>([\s\S]*)\<\/".$node."\>/", $cnt, $matches);
        //未匹配到
        if ($mt !== 1) return null;
        $mts = array_slice($matches, 1);
        //匹配到内容
        if (Is::nemarr($mts)) return $mts[0];
        return null;
    }

    /**
     * 解析自定义的 标签 代码块，例如：profile 等
     * 可通过 attr 定义 parse="json" 定义 内部代码块的解析方法，默认为 json
     * @param String $node 任意自定义 标签，使用 foo-bar 形式
     * @return Array|null
     *  [
     *      "attr" => [],           自定义标签的 参数
     *      "parse" => "json",      自定义的解析方法
     *      "temp" => "",           标签内的 代码块字符串
     *  ]
     */
    protected function parseCustomNode($node="profile")
    {
        //完整的 vue content
        $cnt = $this->vueContent;
        $regx = "/\<".$node."([^\>]*)\>([\s\S]*)\<\/".$node."\>/";
        $mt = preg_match($regx, $cnt, $matches);
        if ($mt !== 1) return null;
        $mts = array_slice($matches, 1);
        //attr
        $attr = $mts[0] ?? "";
        if (Is::nemstr($attr)) {
            $attr = Conv::p2a($attr);
        }
        if (!Is::nemarr($attr)) $attr = [];
        //parse
        $parse = "json";
        if (isset($attr["parse"])) $parse = $attr["parse"];
        //temp
        $temp = $mts[1] ?? "";
        return [
            "attr" => $attr,
            "parse" => $parse,
            "temp" => $temp
        ];
    }

    /**
     * 将 import 语句中的 url 补全|替换 为完整 url
     * 可能的 import url 形式：
     *  0   https://host/foo/bar/jaz[.js]       完整 url
     *  1   /src/lib/vcomp/mixins/base[.js]     也可看作 完整 url
     *  2   mixins/base[.js]                    需要补全的，相对路径 url
     *  3   __URLPRE__/foo/bar/jaz[.js]         需要替换字符串模板的，完整 url
     * @param String $import import 语句，符合 $this->importPattern 正则
     * @return String|null 补全|替换后的 import 语句
     */
    public function fixImportSentence($import)
    {
        if (!Is::nemstr($import)) return $import;
        //使用 importPattern 正则匹配
        $pattern = $this->importPattern;
        $mt = preg_match($pattern, $import, $mts);
        //未匹配成功
        if ($mt !== 1) return null;
        $mts = array_slice($mts, 1);
        //未能匹配到对应的关键参数
        if (count($mts)<2) return null;
        //import 的变量名
        $iv = $mts[0];
        //import url
        $iu = $mts[1];
        if (!Is::nemstr($iv) || !Is::nemstr($iu)) return null;

        //处理 import url
        //组件库 info
        $libi = $this->meta["lib"];
        if (!Is::nemarr($libi)) $libi = [];
        //url 前缀
        $urlpre = $libi["urlpre"] ?? null;

        //调用 Js::fixImportUrl 方法
        $iu = Js::fixImportUrl($iu, $urlpre);

        //替换 __URLPRE__
        //if (Is::nemstr($urlpre)) $iu = str_replace("__URLPRE__", trim($urlpre, "/"), $iu);
        //import 了一个相对路径，使用 urlpre 补齐
        /*if (substr($iu,0,4)!=="http" && substr($iu,0,2)!=="//" && substr($iu,0,1)!=="/") {
            $iu = trim($urlpre, "/")."/".$iu;
            //处理 ../
            $iu = Path::fix($iu);
        }
        //补全 js 后缀名
        if (substr($iu, -3)!==".js") $iu .= ".js";*/

        //合并为完整 import 语句
        return "import $iv from '$iu';";
    }

    /**
     * 将 template 代码转换为 js 定义 语句
     * @return String|null 
     */
    public function defineTemplateJs()
    {
        $temp = $this->template;
        $tempCnt = $temp["content"] ?? "";
        if (!Is::nemstr($tempCnt)) return null;
        //组件变量名
        $vcv = $this->meta["var"] ?? "";
        if (!Is::nemstr($vcv)) return null;
        //定义语句
        return "$vcv.template = `$tempCnt`;";
    }

    /**
     * 将 可能存在的 自定义 profile 转换为 js 定义语句
     * @return String|null
     */
    public function defineProfileJs()
    {
        $prof = $this->profile;
        if (!Is::nemarr($prof)) return null;
        //组件变量名
        $vcv = $this->meta["var"];
        if (!Is::nemstr($vcv)) return null;
        //转为 json
        $json = Conv::a2j($prof);
        //!! 将 profile 放到 computed 中
        $rows = [];
        $rows[] = "if ($vcv.computed === undefined) $vcv.computed = {};";
        $rows[] = "$vcv.computed.profile = function() {return JSON.parse('$json');}";
        return implode("", $rows);
    }

    /**
     * 生成 组件定义语句 Vue.component('pre-foo-bar', {...})
     * @param String|null
     */
    public function callVueComponentJs()
    {
        //组件名称 pre-foo-bar 形式
        $vcn = $this->meta["name"];
        //组件名称 PreFooBar 形式
        $vcv = $this->meta["var"];
        if (!Is::nemstr($vcn) || !Is::nemstr($vcv)) return null;
        //变量名
        $var = $vcv."Comp";
        //Vue.component('pre-foo-bar', {...})
        return "let $var = Vue.component('$vcn', $vcv);";
    }

    /**
     * 将 style 代码转换为定义 <style>...</style> 的 js 语句
     * @return String|null
     */
    public function defineStyleTagJs()
    {
        $css = $this->style;
        $cssCnt = $css["content"] ?? "";
        if (!Is::nemstr($cssCnt)) return null;
        //组件变量名
        $vcv = $this->meta["var"] ?? "";
        if (!Is::nemstr($vcv)) return null;
        $stv = $vcv."Sty";
        //rows
        $rows = [];
        $rows[] = "let $stv = document.createElement('div');";
        $rows[] = "$stv.innerHtml = '<style>$cssCnt</style>';";
        $rows[] = "document.querySelector('head').appendChild($stv.childNodes[0]);";

        return implode("", $rows);
    }

    /**
     * 生成 export default PreFooBarComp 语句
     * @return String|null
     */
    public function defineExportJs()
    {
        //如果不启用 esm 则不输出
        $esm = $this->params["esm"] ?? true;
        if ($esm !== true) return null;

        //组件名 变量名
        $vcv = $this->meta["var"];
        if (!Is::nemstr($vcv)) return null;
        //comp 变量名
        $var = $vcv."Comp";
        //语句
        return "export default $var;";
    }

    /**
     * 当通过组件库 生成此组件实例，用于 合并编译为单个 js 文件时，输出 格式化的 js 语句
     * @return Array|null
     *  [
     *      "imports" => [
     *          # import 语句数组
     *          "变量名" => "url",
     * 
     *          # import fooBar from '/src/lib/foo/bar.js';
     *          "fooBar" => "/src/lib/foo/bar.js",
     *          ...
     *      ],
     *      
     *      # js 定义语句 行数组，含 css 定义语句， 不含 Vue.component() 语句
     *      "jsrows" => [
     * 
     *      ],
     * 
     *      # style 样式定义语句
     *      "css" => "",
     * 
     *      # 组件 名称|变量名
     *      "vcn" => "",
     *      "vcv" => "",
     *  ]
     */
    public function getFormattedJsCode()
    {
        //组件名称
        $vcn = $this->meta["name"];
        $vcv = $this->meta["var"];
        if (!Is::nemstr($vcn) || !Is::nemstr($vcv)) return null;

        //js 语句
        $js = $this->script;
        $jsImports = $js["imports"] ?? [];
        $jsRows = $js["rows"] ?? [];

        //imports 部分
        $inps = [];
        if (Is::nemarr($jsImports)) {
            foreach ($jsImports as $inp) {
                //import 语句拆分
                $inp = preg_replace("/\s+/", " ", $inp);
                $inp = str_replace(["import ","'","\"",";"], "", $inp);
                $ina = explode(" from ", trim($inp));
                if (count($ina)!==2) continue;
                $inps[$ina[0]] = $ina[1];
            }
        }

        //js 语句
        $rows = array_merge([], $jsRows);
        //定义模板
        $defTemp = $this->defineTemplateJs();
        if (Is::nemstr($defTemp)) $rows[] = $defTemp;
        //定义 profile
        $defProf = $this->defineProfileJs();
        if (Is::nemstr($defProf)) $rows[] = $defProf;
        //插入 <style>...</style>
        //$defSty = $this->defineStyleTagJs();
        //if (Is::nemstr($defSty)) $rows[] = $defSty;

        //style 样式语句
        $css = $this->style;
        $cssCnt = $css["content"] ?? "";
        
        //输出数据
        return [
            "imports" => $inps,
            "jsrows" => $rows,
            "css" => $cssCnt,
            "vcn" => $vcn,
            "vcv" => $vcv
        ];
    }



    /**
     * 工具方法
     */

    /**
     * 获取组件名称 foo-bar 以及 FooBar 形式
     * 从 meta 中读取
     * @return Array [ "vcn" => "foo-bar", "vcv" => "FooBar" ]
     */
    public function cname()
    {
        $name = $this->meta["name"];
        if (!Is::nemstr($name)) {
            return [
                "vcn" => "comp-name",
                "vcv" => "CompName"
            ];
        }

        return [
            "vcn" => $name,
            "vcv" => $this->meta["var"],    //Str::camel($name, true)
        ];
    }

    /**
     * 获取当前组件 所在 VueCompLib 组件库的 相关信息
     * @return Array|null
     */
    public function getVueCompLibInfo()
    {
        //首先检查 params 中是否包含 lib 参数

        //当前组件的 本地文件路径
        $real = $this->real;
        //手动创建的 vue 组件，没有组件库
        if (!Is::nemstr($real) || !file_exists($real)) return null;

        //开始依次向上级路径查找
        $real = Path::fix($real);
        $real = str_replace(["/","\\"], DS, $real);
        $dir = dirname($real);
        //路径数组
        $parr = explode(DS, $dir);
        //找到的 *.lib 文件路径
        $libf = null;
        for ($i=count($parr);$i>=1;$i--) {
            $sparr = array_slice($parr, 0, $i);
            $lfp = implode(DS, $sparr).".lib";
            if (file_exists($lfp)) {
                //找到 *.lib 文件 
                $libf = $lfp;
                break;
            }
        }

        //未找到文件，返回 null
        if (!Is::nemstr($libf)) return null;
        
        //读取组件库 *.lib 文件，json
        $libc = file_get_contents($libf);
        $libc = Conv::j2a($libc);
        //组件库名
        $lib = $libc["lib"];
        //组件库的 名称前缀
        $pre = $libc["prefix"] ?? $lib;
        //组件库根路径
        $libp = dirname($libf).DS.$lib;
        //此组件 在组件库中的 相对路径 通常为：/1.0.0/components/foo/bar.vue
        $rela = substr($real, strlen($libp));
        //使用 components 分割
        $rarr = explode(DS."components".DS, $rela);
        //当前组件库版本
        $ver = trim($rarr[0], DS);
        //当前组件 在组件库 components 文件夹下的 相对路径
        $rela = trim($rarr[1], DS);

        //创建 VueCompLib 组件库实例
        $res = Resource::create($libf, [
            "ver" => $ver
        ]);
        if (!$res instanceof Resource) return null;
        //获取组件库 外部请求 url 的前缀
        $upre = $res->getLibUrlPrefix();
        //释放实例
        unset($res);

        //返回获取到的 信息
        return [
            //组件库 *.lib 文件路径
            "file" => $libf,
            //组件库 *.lib 文件内容 []
            "conf" => $libc,
            //组件库根路径，本地文件夹路径
            "dir" => $libp,
            //组件库名称
            "lib" => $lib,
            //组件库版本
            "ver" => $ver,
            //组件库定义的 组件名称前缀
            "pre" => $pre,
            //组件库外部访问 url 前缀
            "urlpre" => $upre,
            
            //当前组件 在 组件库 components 文件夹下的相对路径
            "rela" => $rela,
        ];
    }

    /**
     * 获取完整的 组件名称 foo-bar 形式
     * 首先尝试通过 组件库信息 生成，如果不存在组件库，则直接读取 uri|real 等路径，生成
     * @param String $glup 连接字符 默认 -
     * @return String
     */
    public function getVueCompName($glup="-")
    {
        //首先尝试读取所在 组件库 信息，然后生成组件名
        $vcn = $this->getVueCompNameFromLib($glup);
        if (Is::nemstr($vcn)) return $vcn;

        //通过当前资源的 uri|real 等信息生成
        $vcp = $this->uri;
        if (!Is::nemstr($uri)) {
            $vcp = $this->real;
            if (!Is::nemstr($vcp)) return null;
        }
        //如果是 url
        if (strpos($vcp, "://")!==false) $vcp = explode("://", $vcp)[1];
        if (strpos($vcp, "//")!==false) $vcp = explode("//", $vcp)[1];
        if (strpos($vcp, "?")!==false) $vcp = explode("?", $vcp)[0];
        //统一分隔符
        $vcp = str_replace(["/","\\",DS], "/", $vcp);
        $vcp = trim($vcp, "/");
        //pi
        $pi = pathinfo($vcp);
        //文件名作为 组件名
        $vcn = $pi["filename"] ?? "";

        if (!Is::nemstr($vcn)) return null;
        return $vcn;
    }

    /**
     * 通过解析所在 VueCompLib 组件库路径，取得相对路径，读取组件库的 组件名称前缀，最终生成 组件名称 pre-foo-bar 形式
     * @param String $glup 连接字符 默认 -
     * @return String|null
     */
    protected function getVueCompNameFromLib($glup="-")
    {
        //获取当前组件 所在组件库 的信息
        $libinfo = $this->meta["lib"];
        if (!Is::nemarr($libinfo)) $libinfo = $this->getVueCompLibInfo();
        if (!Is::nemarr($libinfo)) return null;

        //组件库定义的 组件名称前缀
        $pre = $libinfo["pre"];
        //当前组件 在组件库 components 文件夹下的相对路径
        $rela = $libinfo["rela"];
        //pi
        $pi = pathinfo($rela);
        //组件文件名
        $fn = $pi["filename"];

        //生成组件名
        $vcn = [];
        //前缀
        $vcn[] = $pre;
        //组件库路径下的 文件夹路径转为 foo-bar
        $vcn[] = str_replace(DS, $glup, $pi["dirname"]);
        //组件文件名
        $vcn[] = $fn;
        //拼接
        $vcn = implode($glup, $vcn);
        //处理 components/button/button.vue --> pre-button-button 的情况，转为 pre-button
        if (strpos($vcn, "-$fn-$fn")!==false) {
            $vcn = str_replace("-$fn-$fn", "-$fn", $vcn);
        }

        //返回
        return $vcn;
    }

}