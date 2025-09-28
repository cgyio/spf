<?php
/**
 * js 文件资源处理
 * 继承自 ParsablePlain 类，对 *.js 文件进行一系列 解析|操作
 * 特别针对 Vue2.x 组件内部的 js 做有针对性的处理
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

class Js extends ParsablePlain 
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
         * 针对 Vue2.x 组件库 内部 js 文件
         * 可在资源实例化时，传入此组件库的 相关信息
         * !! 这个参数是 array 无法通过 url 传递，因此通常是 通过组件库访问某个内部 js 时，由组件库实例化组件资源时 额外传入此参数
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
            */
        ],
        //是组件库 内部 js 的标记
        "inlib" => false,
    ];

    /**
     * 定义支持的 export 类型，必须定义相关的 createFooContent() 方法
     * 必须是 Mime 支持的 文件后缀名
     * !! 覆盖父类
     */
    protected static $exps = [
        "js",
    ];
    
    /**
     * 在 组件库内部 js 中，可以使用这些 字符串模板
     * 定义 字符串模板 与 meta["lib"] 中某个 key 的 映射
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

    /**
     * meta 元数据
     */
    public $meta = [
        //组件库 内部 js 标记
        "inlib" => false,
        //组件库 信息参数
        "lib" => [],
    ];

    /**
     * 收集 import 信息
     */
    public $imports = [
        /*
        "fooBar" => "url",
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
        //标准化 params
        $this->formatParams();
        $ps = $this->params;

        /**
         * 收集 meta 元数据
         */
        $inlib = $ps["inlib"] ?? false;
        $lib = $ps["lib"] ?? [];
        if ($inlib === true) {
            $this->meta = [
                "inlib" => true,
                "lib" => $lib
            ];
        }

        //如果是 组件库内部 js 文件，在拆分 rows 之前先执行一次 字符串模板替换
        if ($inlib === true) $this->content = static::replaceTplsInCode($this->content, $this->tpls, $lib);
        
        //将 content 按行拆分为 rows 行数组
        $this->contentToRows();

        if ($inlib !== true) {
            //普通 js 直接处理 import 合并到此文件中
            $this->fixImport();
        } else {
            //针对 组件库内部 js 执行额外操作
            $this->inLibJsAfterCreated();
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
        //调用 Plain 父类的默认方法
        return $this->plainBeforeExport($params);
    }



    /**
     * 根据 export 参数，生成对应 ext 资源的内容 content
     * !! static::$exps 数组中的所有指定支持输出的类型，都应定义对应的 createExtContent 方法
     * @return $this
     */
    //生成 js content
    /*protected function createJsContent()
    {
        //普通 js 不做处理，直接使用 content
        if ($this->meta["inlib"] !== true) return $this;
            
        //针对 组件库内部 js 文件
        
    }*/



    /**
     * 专门针对 组件库内部 js 文件的 工具方法
     */

    /**
     * 额外的 afterCreated 方法
     * @return $this
     */
    protected function inLibJsAfterCreated()
    {
        $ps = $this->params;
        $meta = $this->meta;
        $inlib = $meta["inlib"] ?? false;
        if ($inlib !== true) return $this;

        //组件库信息参数
        $libi = $meta["lib"] ?? [];
        $urlpre = $libi["urlpre"] ?? "";

        //针对 组件库内部 js 收集 import 并处理 url
        foreach ($this->rows as $i => $row) {
            //匹配 import 语句
            $mt = preg_match($this->importPattern, $row, $mts);
            //未匹配 跳过
            if ($mt !== 1) continue;
            $mts = array_slice($mts, 1);
            if (count($mts)!==2) continue;

            //变量名
            $var = $mts[0];
            //指向 url 
            $url = $mts[1];
            if (!is::nemstr($var) || !Is::nemstr($url)) continue;
            //处理 url
            $url = static::fixImportUrl($url, $urlpre);

            //存入 imports
            $this->imports[$var] = $url;
            
            //原 行数据 替换为 新 url
            $this->rows[$i] = "import $var from '$url';";
        }

        //重新合并
        $this->content = $this->rowCnt(false);

        return $this;
    }

    /**
     * 将 import 和 其他语句分开输出
     * @return Array 
     *  [
     *      "imports" => $this->imports,
     *      "jsrows" => $this->rows 去除 import 语句
     *  ]
     */
    public function getFormattedJsCode()
    {
        $rows = array_filter($this->rows, function($row) {
            return substr(trim($row), 0, 7) !== "import ";
        });
        return [
            "imports" => $this->imports,
            "jsrows" => $rows
        ];
    }



    /**
     * 工具方法
     */

    /**
     * 根据 esm 状态，处理 rows 生成 content
     * @return $this
     */
    /*protected function fixEsmExport()
    {
        
    }*/






    /**
     * 静态工具
     */

    /**
     * 处理 import 中的 url 使其可以指向正确 完整的 目标 url
     * @param String $url 
     * @param String $urlpre 可以指定 url 前缀，不指定 则使用 Url::current 
     * @return String 处理后的 完整的 url
     */
    public static function fixImportUrl($url, $urlpre=null)
    {
        if (!Is::nemstr($url)) return $url;
        $url = trim($url);
        //补齐 .js 后缀名
        if (substr($url, -3)!==".js") $url .= ".js";

        //当前 url
        $uo = Url::current();

        if (substr($url, 0, 4) === "http" && strpos($url, "://") !== false) {
            //传入完整的 url 直接返回
            return $url;
        }

        if (substr($url, 0, 2) === "//" || substr($url, 0, 1) === "/" || substr($url, 0, 2) === "./") {
            //传入 以 // 或 / 或 ./ 开头的 url
            if (substr($url, 0, 2) === "//") {
                $url = $uo->protocol.":".$url;
            } else {
                $url = $uo->domain.(substr($url, 0, 2) === "./" ? substr($url, 1) : $url);
            }
            return $url;
        }

        //针对传入 相对路径 url

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

}