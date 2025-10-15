<?php
/**
 * Codex 类型资源类
 * 处理 Vue2.x 单文件组件 资源
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\SrcException;
use Spf\Request;
use Spf\Response;
use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;

class Vue extends Codex
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    public static $stdParams = [

        /**
         * !! 无法通过 url 传递 的参数，只能在此资源实例化时 手动传入
         */
        //可在资源实例化时，指定当前的 Codex 资源是否属于某个 Compound 复合资源的 内部资源
        //!! *.vue 可以属于某个组件库，此处传入组件库资源实例
        /*"belongTo" => null,
        //是否忽略 $_GET 参数
        "ignoreGet" => false,
        //可额外定义此资源的 中间件，这些额外的中间件 将在资源实例化时，附加到预先定义的中间件数组后面
        "middleware" => [
            //create 阶段
            "create" => [],
            //export 阶段
            "export" => [],
        ],*/

        /**
         * 其他参数
         */
        //可指定实际输出的 资源后缀名 可与实际资源的后缀名不一致，例如：scss 文件可指定输出为 css
        "export" => "vue",
        
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
            //获取 资源的 content
            "GetContent" => [],
            //获取当前 *.vue 组件的 名称参数
            "GetVueCompName" => [],
            //解析前处理 vue 代码，例如：字符串模板替换 等
            "FixVueCodeBeforeParse" => [],
            //解析 *.vue 代码，得到参数保存到资源实例
            "ParseVueCode" => [],
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],
            
            //条件执行 合并生成 content
            "ExportVueJs ?export=js" => [
                "break" => true,
            ],
            "ExportVueCss ?export=css" => [
                "break" => true,
            ],
            "ExportVueContent ?export=vue" => [
                "break" => true,
            ],
        ],
    ];

    /**
     * 当前组件的 名称参数
     * 这些参数 可能在 此组件属于某个组件库的情况下 有不同的获取方法
     */
    public $vueCompName = [
        //组件名前缀 foo-bar- 形式
        "pre" => "",
        //组件名 foo-bar-jaz 形式
        "vcn" => "",
        //组件变量名 FooBarJaz 形式
        "vcv" => "",
        //组件定义阶段，js 变量名，通常为 defineFooBarJaz
        "def" => "",
    ];

    /**
     * 定义 在 *.vue 文件中可使用的 字符串模板，以及其对应的 vueCompName 数组中的数据
     */
    protected $tpls = [
        //组件库定义的 组件名称前缀，通常用于 style 样式代码中的 样式类名称
        "__PRE__" => "pre",
        //用于组件模板代码块中，代替 组件名称前缀，以便可以方便的 在不同的使用场景下，切换组件名称前缀
        //例如：<PRE@button>...</PRE@button> 替换为 <pre-button>...</pre-button>
        "PRE@" => "pre",
        //此组件的 名称 pre-foo-bar 形式，用于 js 代码中
        "__VCN__" => "vcn",
        //此组件的 变量名 PreFooBar 形式，用于 js 代码中
        "__VCV__" => "vcv",
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
        //换行符
        "rn" => "\n",
        //模板内容，行数组
        "rows" => [],
    ];
    //js 代码块 
    public $script = [
        //创建为临时资源实例
        "resource" => null,
        //import 语句数组
        "importRows" => [],
        //除了 import 之外的 语句数组
        "rows" => [],
        //组件变量名
        "var" => [
            /*
            "var" => "FooBar",
            "vcn" => "foo-bar",
            "def" => "defineFooBar",
            */
        ],
    ];
    //样式代码块 创建为临时资源实例
    public $style = null;
    //自定义语言块 可通过 <foo>...</foo> 自定义代码块
    public $custom = []; 

    /**
     * 如果当前组件 是某个组件库中的成员，缓存组件库资源实例
     */
    public $lib = null;


    
    /**
     * 资源实例内部定义的 stage 处理方法
     * @param Array $params 方法额外参数
     * @return Bool 返回 false 则终止 当前阶段 后续的其他中间件执行
     */
    //GetVueCompName 获取当前 *.vue 组件的 名称参数，保存到 vueCompName
    public function stageGetVueCompName($params=[])
    {
        $vn = $this->getVueCompName();
        $this->vueCompName = Arr::extend($this->vueCompName, $vn);
        return true;
    }
    //FixVueCodeBeforeParse 在解析 vue 文件之前，处理代码，例如：字符串模板替换 等
    public function stageFixVueCodeBeforeParse($params=[])
    {
        //替换 *.vue 代码中的 $this->tpls 数组中定义的 字符串模板
        $tpls = $this->tpls;
        $vns = $this->vueCompName;
        //处理前的 *.vue 文件中的代码
        $cnt = $this->content;

        //替换模板
        foreach ($tpls as $tpl => $prop) {
            $vd = Arr::find($vns, $prop);
            if (!is_string($vd)) continue;
            
            //替换
            $cnt = str_replace($tpl, $vd, $cnt);
        }

        //保存处理后的 代码
        $this->vueContent = $cnt;
        return true;
    }
    //ParseVueCode 解析 *.vue 代码，得到参数保存到资源实例
    public function stageParseVueCode($params=[])
    {
        //解析 vueContent
        $this->parseProfile();
        $this->parseTemplate();
        $this->parseScript();
        $this->parseStyle();

        return true;
    }
    //ExportVueJs 以 js 形式输出 *.vue 组件代码，包含 组件定义|样式注入 js 代码段
    public function stageExportVueJs($params=[])
    {
        if (!isset($this->script)) {
            $this->content = "";
            return false;
        }
        //script 临时资源
        $opts = $this->script;
        $script = $opts["resource"] ?? null;
        if (!$script instanceof Codex) {
            $this->content = "";
            return false;
        }

        //开始修改 script js 代码，增加必须的 组件定义|样式注入 代码段
        //先执行一次 export 确保 js 资源内容正确生成
        //$script->export([
        //    "return" => true
        //]);
        //import 语句
        $importer = $script->ImportProcessor;
        $importRows = $importer->getImportRows();
        $rows = $importer->getNoImportRows();
        //组件变量名
        $vns = $this->vueCompName;
        $vcv = $vns["vcv"];
        $vcn = $vns["vcn"];
        $def = $vns["def"];

        //添加 template 定义
        $template = $this->template;
        $tempcnt = $template["content"];
        if (Is::nemstr($tempcnt)) {
            $rows[] = "";
            $rows[] = "$def.template = `$tempcnt`;";
        }

        //添加 profile 定义
        $profile = $this->profile;
        $rows[] = "";
        $rows[] = "if ($def.computed === undefined) $def.computed = {};";
        if (Is::nemarr($profile)) {
            $rows[] = "$def.computed.profile = function() {return JSON.parse(`".Conv::a2j($profile)."`);}";
        } else {
            $rows[] = "$def.computed.profile = function() {return {};}";
        }

        //组件定义语句 Vue.component('foo-bar', {...} ) 
        $rows[] = "";
        $rows[] = "let $vcv = Vue.component('$vcn', $def);";

        //注入 style 到 head
        if (isset($this->style) && $this->style instanceof Codex) {
            $style = $this->style->export([
                "return" => true
            ]);
            //minify
            $style = static::minifyCnt($style, "css");
            //注入 head
            $stv = $vcv."Sty";
            $rows[] = "";
            $rows[] = "let $stv = document.createElement('div');";
            $rows[] = "$stv.innerHtml = '<style>$style</style>';";
            $rows[] = "document.querySelector('head').appendChild($stv.childNodes[0]);";
        }

        //保存修改
        $this->script["importRows"] = $importRows;
        $this->script["rows"] = $rows;

        //修改 js 临时资源实例
        $rower = $script->RowProcessor;
        $rower->clearRows();
        $rower->rowAdd($importRows);
        $rower->rowEmpty(1);
        $rower->rowAdd($rows);
        $rower->rowEmpty(1);
        //增加 esm 导出语句
        $rower->rowAdd("export default $vcv;","");

        //生成 content
        $jscnt = $rower->rowCombine();

        //写入当前资源的 content
        $this->content = $jscnt;
        
        return true;
    }
    //ExportVueCss 输出 *.vue 组件代码内部的 style 样式代码
    public function stageExportVueCss($params=[])
    {
        if (isset($this->style) && $this->style instanceof Codex) {
            $style = $this->style;
            $this->content = $style->export([
                "return" => true
            ]);
        } else {
            $this->content = "";
        }
        return true;
    }
    //ExportVueContent 输出 *.vue 原始代码
    public function stageExportVueContent($params=[])
    {
        //输出处理后的 vueContent
        $this->content = $this->vueContent;

        return true;
    }



    /**
     * vue 文件解析 得到各代码块，保存到对应属性
     * @return $this
     */
    //解析 第一个 template 模板代码块
    protected function parseTemplate()
    {
        //解析
        $temp = static::parseStaticNode($this->vueContent, "template");
        if (!Is::nemstr($temp)) return $this;
        //参数保存到的 属性
        $template = $this->template;
        //拆分行
        $rn = $template["rn"] ?? "\n";
        $rows = explode($rn, $temp);
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
        $this->template = Arr::extend($template, [
            "content" => $cnt,
            "rows" => $rows,
        ]);
        return $this;
    }
    //解析 第一个 script 代码块
    protected function parseScript()
    {
        //解析
        $temp = static::parseStaticNode($this->vueContent, "script");
        if (!Is::nemstr($temp)) return $this;

        //组件变量名
        $vns = $this->vueCompName;

        //将解析得到的 js 代码，创建为 临时资源，保存到 $this->script 中
        $script = Resource::manual($temp, $this, [
            //定义临时资源类型 ext
            "ext" => "js",
            //忽略 $_GET
            "ignoreGet" => true,
            //需要处理 import 语句
            "import" => true,   //"keep",
            //不合并其他文件
            "merge" => "",
            //启用 esm 导出，如果此组件属于某个组件库，则不启用 esm 导出，将由组件库资源内部自行处理 esm 导出语句
            "esm" => $this->ParentProcessor->hasParent()!==true,    //true,
            //esm 导出时的 变量名
            "var" => $vns["def"],
        ]);
        if (!$script instanceof Codex) {
            //资源实例创建失败，报错
            throw new SrcException($this->name." 对应的 JS,解析代码阶段出错", "resource/instance");
        }
        
        //保存解析结果
        $this->script = [
            "resource" => $script,
            "importRows" => [],
            "rows" => [],
        ];

        return $this;
    }
    //解析 第一个 style 代码块
    protected function parseStyle()
    {
        //解析
        $temp = static::parseStaticNode($this->vueContent, "style");
        if (!Is::nemstr($temp)) return $this;

        //将解析得到的 css 代码，创建为 临时资源，保存到 $this->style 中
        $style = Resource::manual($temp, $this, [
            //定义临时资源类型 ext
            "ext" => "css",
            //忽略 $_GET
            "ignoreGet" => true,
            //组件代码内部的 style 样式不启用 import 语句
            "import" => false,
            //不合并其他文件
            "merge" => "",
        ]);
        if (!$style instanceof Codex) {
            //资源实例创建失败，报错
            throw new SrcException($this->name." 对应的 CSS,解析代码阶段出错", "resource/instance");
        }
        
        //保存解析结果
        $this->style = $style;

        return $this;

    }
    //处理 profile 数据
    protected function parseProfile()
    {
        $rtn = static::parseCustomNode($this->vueContent, "profile");
        if (!Is::nemarr($rtn)) return $this;
        //定义了 profile
        $parse = $rtn["parse"] ?? "json";
        $temp = $rtn["temp"];

        //解析得到 profile
        switch ($parse) {
            //json
            case "json":
                $profile = Conv::j2a($temp);
                break;

            case "xml":
                $profile = Conv::x2a($temp);
                break;
        }

        //保存结果
        //$this->meta["profile"] = $profile;
        $this->profile = $profile;
        return $this;
    }



    /**
     * 工具方法
     */

    /**
     * 获取当前 *.vue 组件的 组件名 foo-bar-jaz 形式，需要判断是否属于某个组件库
     * @return Array 组件名
     *  [
     *      "pre" => "foo-",            # 组件名前缀，通常由 组件库生成
     *      "vcv" => "FooBar",          # 组件变量名
     *      "vcn" => "foo-bar",         # 组件名 标签名
     *      "def" => "defineFooBar",    # 组件定义时，参数的变量名
     *  ]
     */
    public function getVueCompName()
    {
        //当前 *.vue 文件的绝对路径
        $pather = $this->PathProcessor;
        $base = $pather->basePath();

        //ParentProcessor
        $parenter = $this->ParentProcessor;
        //当前组件 属于某个组件库，调用组件库的 getVueCompName 方法，需要传入当前 *.vue 文件的绝对路径
        if ($parenter->hasParent()===true) {
            $pres = $this->parentResource;
            $pre = $pres->getVueCompNamePre();
            $vcn = $pres->getVueCompName($base);
            $vcv = Str::camel($vcn, true);
            return [
                "pre" => $pre,
                "vcv" => $vcv,
                "vcn" => $vcn,
                "def" => "define".$vcv
            ];
        }

        //连接符
        $glue = "-";
        
        //解析当前 *.vue 文件路径，查找 components 文件夹
        $fn = pathinfo($base)["filename"];
        $fn = Str::snake($fn, $glue);
        $fn = trim($fn, $glue);
        $parr = explode("components", $pather->updir());
        $pre = "";
        if (count($parr)>1) {
            $path = array_slice($parr, -1)[0];
            $pre = str_replace([DS,"/","\\"], $glue, $path);
            $pre = trim($pre, $glue);
        }
        if (Is::nemstr($pre)) $pre .= $glue;

        //拼接
        $vcn = $pre.$fn;

        //去重 处理 components/button/button.vue 的情况，返回 button
        $varr = explode($glue, $vcn);
        $nvcn = [];
        foreach ($varr as $vni) {
            if (in_array($vni, $nvcn)) continue;
            $nvcn[] = $vni;
        }

        //拼接
        $vcn = implode($glue, $nvcn);
        $vcv = Str::camel($vcn, true);
        return [
            "pre" => "",
            "vcv" => $vcv,
            "vcn" => $vcn,
            "def" => "define".$vcv
        ];

    }



    /**
     * 静态工具
     */

    /**
     * 解析 必须的 标签 代码块 template|script|style
     * 不包含 attr 参数
     * @param String $vue *.vue 原始代码
     * @param String $node 可以是 template|script|style
     * @return String|null 包含在标签内的字符串，不包含标签本身
     */
    public static function parseStaticNode($vue, $node="template")
    {
        if (!Is::nemstr($vue) || !in_array($node, ["template","script","style"])) return null;
        //匹配
        $mt = preg_match("/\<".$node."\>([\s\S]*)\<\/".$node."\>/", $vue, $matches);
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
     * @param String $vue *.vue 原始代码
     * @param String $node 任意自定义 标签，使用 foo-bar 形式
     * @return Array|null
     *  [
     *      "attr" => [],           自定义标签的 参数
     *      "parse" => "json",      自定义的解析方法
     *      "temp" => "",           标签内的 代码块字符串
     *  ]
     */
    public static function parseCustomNode($vue, $node="profile")
    {
        if (!Is::nemstr($vue) || !Is::nemstr($node)) return null;
        //匹配
        $regx = "/\<".$node."([^\>]*)\>([\s\S]*)\<\/".$node."\>/";
        $mt = preg_match($regx, $vue, $matches);
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
            "content" => $temp
        ];
    }

}