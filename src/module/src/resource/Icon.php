<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Icon 子类
 * 继承自 Plain 纯文本类型基类，处理 *.icon 类型本地文件
 * 
 * Spf 框架视图图标库 *.icon 文件基于 iconfont.cn 阿里图标库的 Symbol 图标引用方式
 * icon 文件为包含图标库信息的 json 格式 !! 本地文件 !!，必须包含下列项目：
 *      iconfont        必须关联到阿里图标库的某个 Symbol 引用的 js 文件，例如：//at.alicdn.com/t/c/font_4910918_t1l430kzeb.js
 *      iconset         此图标库的名称 foo-bar 形式，此名称将影响内部所有图标的 引用类名|单独访问的图标名，例如：内部图标名为 foo-bar-success
 *      multicolor      此图标库是否多色图标，默认 false
 *      created         此图标库所有数据已经缓存到本地的 标记，缓存后，将在 icon 文件中生成 glyphs 项目（关联数组）
 *      glyphs          保存图标库中所有图标 svg 代码的 关联数组，每个图标数据结构为：
 *          name        此图标的名称，不含 iconset 前缀，例如：success
 *          class       此图标的外部访问全名，也是以 css-class 调用时的 css 类名，例如：foo-bar-success
 *          svg         此图标的 svg 代码
 * 
 *      其他可选项目
 *      iconsize        此图标库中的图标的 默认尺寸，例如：24
 *      viewbox         此图标库 svg 文件的通用 viewbox 参数，与 iconsize 参数对应，例如：0 0 24 24
 * 
 * Spf 框架视图 图标库的 使用方法：
 *   0  在 iconfont.cn 阿里图标库创建项目，收集需要的图标，生成 Symbol 方式的 js 文件
 *   1  在项目的 SRC_PATH/icon 路径下，创建 foo-bar.icon 文件，设置关联 阿里图标库 js 文件地址，以及其他必须的 参数项目，如：iconset = foo-bar
 *   2  在需要使用图标库的 视图文件中，引用 //host/[path-to-icon]/foo-bar.icon ，将默认输出图标库为 js 文件，此 js 的功能为：
 *      2.1     将在引用的页面中 插入 svg 雪碧图
 *      2.2     然后通过 <svg class="spf-icon" aria-hidden="true"><use xlink:href="#foo-bar-success"></use></svg> 方式使用图标
 *   3  视图文件还需要引用 css 文件：//host/[path-to-icon]/foo-bar.icon?export=css 此 css 文件将定义 默认图标样式类 spf-icon
 *   4  可通过 //host/[path-to-icon]/foo-bar.icon?icon=success 直接访问库中的某个图标，输出为 svg 文件
 *   5  可通过 //host/[path-to-icon]/foo-bar.icon?create=yes 强制刷新图标缓存，可在修改 阿里图标库 js 地址后刷新
 * 
 * Spf 框架视图 图标库 icon 文件的 解析|输出 方式：
 *   0  首次访问时，将远程获取 阿里图标库 js 文件，解析并得到库中所有图标的 name 以及 svg 代码，
 *   1  更新 icon 文件内容，创建 glyphs 项目数组，并 标记为 created = true ，再次访问时，将直接使用已解析的 图标列表 glyphs
 *   2  根据 解析得到的（或缓存的）glyphs 数组，以及传入的 export|icon 参数，决定输出 js|css|svg 文件
 * 
 * 图标库中的 图标 依赖 阿里图标库--我的项目 来进行管理，也可以通过指定不同的 阿里 js 文件地址，让同一个图标库名称 指向 不同的 阿里图标库
 */

namespace Spf\module\src\resource;

use Spf\App;
use Spf\Response;
use Spf\module\src\Resource;
use Spf\module\src\Mime;
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Conv;
use Spf\util\Path;
use Spf\util\Curl;

class Icon extends Plain 
{
    /**
     * 当前的资源类型的本地文件，是否应保存在 特定路径下
     * 指定的 特定路径 必须在 Src::$current->config->resource["access"] 中定义的 允许访问的文件夹下
     *  null        表示不需要保存在 特定路径下，本地资源应在 [允许访问的文件夹]/... 路径下
     *  "ext"       表示应保存在   [允许访问的文件夹]/[资源后缀名]/... 路径下
     *  "foo/bar"   指定了 特定路径 foo/bar 表示此类型本地资源文件应保存在   [允许访问的文件夹]/foo/bar/... 路径下
     * 默认 null 不指定 特定路径
     * !! 覆盖父类
     * !! 图标库 *.icon 文件 必须保存在 [允许访问的文件夹]/icon/... 路径下
     */
    public static $filePath = "ext";    //可选 null | ext | 其他任意路径形式字符串 foo/bar 首尾不应有 /



    /**
     * 定义标准的 图标库参数格式
     * !! 如果需要，子类可以覆盖此属性
     */
    protected static $stdIcon = [
        //关联到阿里图标库的某个 Symbol 引用的 js 文件地址 通常以 //at.alicdn.com/t/c/... 开头
        "iconfont" => "",
        //此图标库的名称 foo-bar 形式，此名称将影响内部所有图标的 引用类名|单独访问的图标名，例如：内部图标名为 foo-bar-success
        "iconset" => "",
        //此图标库是否多色图标，默认 false
        "multicolor" => false,
        //此图标库所有数据已经缓存到本地的 标记，缓存后，将在 icon 文件中生成 glyphs 项目（关联数组）
        "created" => false,
        //保存图标库中所有图标 svg 代码的 关联数组
        "glyphs" => [],

        /*
        # 其他可选项目

        # 此图标库中的图标的 默认尺寸
        "iconsize" => 24,

        # 此图标库 svg 文件的通用 viewbox 参数
        "viewbox" => "0 0 24 24",

        ...
        */
    ];
    //标准的 某个 图标的参数格式
    protected static $stdGlyph = [
        //此图标的名称，不含 iconset 前缀
        "name" => "",
        //此图标的外部访问全名，也是以 css-class 调用时的 css 类名，包含 iconset 前缀
        "class" => "",
        //此图标的 svg 代码
        "svg" => "",
    ];

    /**
     * 定义 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    protected static $stdParams = [
        //是否 强制不使用 缓存的 glyphs
        "create" => false,
        //输出文件的 类型 css|js|svg
        "export" => "js",
        //输出某个具体的 图标 svg，此参数如果指定，则忽略 export 参数
        "icon" => "", 
        
        //其他可选参数，在指定要输出某个图标 svg 时，其他参数参考 svg 资源类
        //...
    ];

    /**
     * 定义支持的 export 类型，必须定义相关的 createFooContent() 方法
     * 必须是 Mime 支持的 文件后缀名
     * !! 覆盖父类
     */
    protected static $exps = [
        "js", "css", "svg",
    ];

    //当前图标库的 元数据
    protected $meta = [
        //包含 iconfont | iconset | multicolor | ...
    ];

    //当前图标库的 glyphs 图标列表 缓存
    protected $glyphs = [];

    

    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //格式化 params 并根据 export 修改 ext|mime
        $this->formatParams();
        $ps = $this->params;
        //指定要输出某个具体的图标 svg 的情况
        if (isset($ps["icon"]) && Is::nemstr($ps["icon"])) {
            $this->params["export"] = "svg";
        }

        //文件内容是 图标库的参数 json 数据，转为 Array
        $ctx = Conv::j2a($this->content);
        //合并 格式化为 标准图标库参数形式
        $ctx = Arr::extend(static::$stdIcon, $ctx);
        //是否已缓存
        $created = $ctx["created"] ?? false;
        if (!is_bool($created)) $created = false;

        //获取元数据
        $meta = [];
        foreach ($ctx as $k => $v) {
            if (in_array($k, ["created", "glyphs"])) continue;
            $meta[$k] = $v;
        }
        $this->meta = $meta;

        //根据传入的参数 决定是否需要解析 阿里 js
        if ($created === true && $this->params["create"] !== true) {
            //直接使用 已缓存的 glyphs
            $this->glyphs = $ctx["glyphs"] ?? [];
            return $this;
        }

        //开始解析 阿里 js
        $iset = $ctx["iconset"] ?? null;
        $alijsf = $ctx["iconfont"] ?? null;
        $pctx = static::parseIconfont($iset, $alijsf);
        if (!Is::nemarr($pctx)) {
            //解析 阿里图标库文件 失败
            throw new SrcException("解析图标库 ".$this->name." 发生错误，缺少必要参数", "resource/getcontent");
        }
        $glyphs = $pctx["glyphs"] ?? null;
        $js = $pctx["js"] ?? null;
        if (!Is::nemarr($glyphs) || !Is::nemstr($js)) {
            //解析结果错误
            throw new SrcException("解析图标库 ".$this->name." 未得到正确的结果", "resource/getcontent");
        }
        $this->glyphs = $glyphs;

        //更新 *.icon 文件
        $iconf = file_get_contents($this->real);
        $iconarr = Conv::j2a($iconf);
        $iconarr["created"] = true;
        $iconarr["glyphs"] = $glyphs;
        $iconjson = Conv::a2j($iconarr);
        file_put_contents($this->real, $iconjson);

        //创建 js 文件缓存
        $jsf = $this->getJsPath();
        if (!file_exists($jsf)) {
            //创建
            Path::mkfile($jsf, $js);
        } else {
            //更新缓存的 js
            $jsfh = fopen($jsf, "w");
            fwrite($jsfh, $js);
            fclose($jsfh);
        }

        //解析结束
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

        //传入的 export | icon 参数
        $exp = $this->params["export"] ?? "js";
        $icon = $this->params["icon"] ?? "";

        if (!Is::nemstr($icon)) {
            //未指定要输出某个图标 svg，根据 export 生成输出内容
            $m = "create".Str::camel($exp, true)."Content";
            if (method_exists($this, $m)) {
                //生成对应 输出类型的 content
                $this->$m();
            }

            //minify
            if ($this->isMin() === true) {
                //压缩 JS/CSS 文本
                $this->content = $this->minify();
            }
        } else {
            //指定要输出某个具体图标的 svg
            $glyph = $this->glyphs[$icon] ?? [];
            $gsvg = $glyph["svg"] ?? null;
            if (!Is::nemstr($gsvg)) {
                //要输出的 icon 不在此图标库中
                throw new SrcException("未找到图标 ".$icon, "resource/export");
            }

            //创建 Svg 资源类实例，需要注入 params 可能包含对 svg 进行处理的参数
            $ps = $this->params;
            //glyph 转为标准的 svg 代码格式
            $gsvg = Svg::glyphToSvg($gsvg);
            if (!Is::nemstr($gsvg)) {
                //生成标准 svg 代码错误
                throw new SrcException("图标 ".$icon." 无法生成 SVG 图标", "resource/export");
            }
            $ps["content"] = $gsvg;
            $svg = Svg::create("$icon.svg", $ps);
            //调用 Svg 实例的 export 方法获取 content
            $this->content = $svg->export([
                "return" => true
            ]);
        }

        return $this;
    }



    /**
     * 不同 export 类型，生成不同的 content
     * !! 子类可以根据 exps 中定义的可选值，实现对应的 createFooContent() 方法
     * @return $this
     */
    //生成 CSS content
    protected function createCssContent()
    {
        //当前图标库 css 文件
        $csf = $this->getCssPath();
        if (file_exists($csf)) {
            //创建 css|scss 资源实例，传入 params 作为参数
            //因为当前的 params 中的 export == css，因此 如果创建的是 scss 实例，其输出的 content 也会被解析为 css
            $cssres = Resource::create($csf, $this->params);
            //调用 CSS|SCSS 资源实例的 export 方法获取 content
            $this->content = $cssres->export([
                "return" => true
            ]);
        } else {
            //未找到文件，返回简易 spf-icon 样式
            $this->content = ".spf-icon {width: 1em; height: 1em; vertical-align: -0.15em; fill: currentColor; overflow: hidden;}";
        }
        return $this;
    }
    //生成 JS content
    protected function createJsContent()
    {
        //获取 js 缓存文件
        $jsf = $this->getJsPath();
        if (!file_exists($jsf)) {
            //输出资源错误，未找到对应的 js 文件
            throw new SrcException("未找到图标库 ".$this->name." 对应的 JS 文件", "resource/export");
        }
        //创建 JS 资源实例，将当前的 params 作为参数传入
        $jsres = Resource::create($jsf, $this->params);
        //调用 JS 资源实例的 export 方法获取 js content
        $this->content = $jsres->export([
            "return" => true
        ]);
        return $this;
    }

    /**
     * 获取当前图标库的 js 缓存文件路径，不检查是否存在
     * @return String 
     */
    protected function getJsPath()
    {
        //此图标库的 js 缓存文件应保存在 *.icon 文件目录下，同名文件夹下的 同名 js 文件中
        $real = $this->real;
        $dir = dirname($real);
        $iset = $this->meta["iconset"] ?? null;
        if (!Is::nemstr($iset)) return null;
        $jsf = $dir.DS.$iset.DS.$iset.".js";
        return $jsf;
    }

    /**
     * 查找当前图标库的 关联 css 文件路径
     * 关联的 css 文件应保存在 *.icon 文件所在路径下，同名文件中的 同名 css|scss 文件
     * 如果当前路径不存在，则依次在 应用icon目录 --> 网站icon目录 --> 框架icon目录 中查找 同名 css|scss 文件
     * @return String|null
     */
    protected function getCssPath()
    {
        //iconset
        $iset = $this->meta["iconset"] ?? null;
        if (!Is::nemstr($iset)) return null;
        //icon 文件实际路径
        $real = $this->real;
        $dir = dirname($real);

        //要查找的 路径列表
        $csfs = [];
        //当前路径下的 css|scss 文件路径
        $csfs[] = $dir.DS.$iset.DS.$iset.".css";
        $csfs[] = $dir.DS.$iset.DS.$iset.".scss";
        //当前应用的 icon 目录
        if (App::$isInsed === true) {
            $appk = App::$current::clsk();
            if ($appk !== "base_app") {
                $csfs[] = "src/$appk/icon/$iset/$iset.css";
                $csfs[] = "src/$appk/icon/$iset/$iset.scss";
            }
        }
        //网站 icon 目录
        $csfs[] = "src/icon/$iset/$iset.css";
        $csfs[] = "src/icon/$iset/$iset.scss";
        //框架 icon 目录
        $csfs[] = "spf/assets/icon/$iset/$iset.css";
        $csfs[] = "spf/assets/icon/$iset/$iset.scss";
        //!! 最终提供兜底的 css 文件，此文件一定存在
        $csfs[] = "spf/assets/icon/base.css";

        //查找文件
        $csf = Path::exists($csfs, false, Path::FIND_FILE);
        if (!file_exists($csf)) return null;
        return $csf;
    }

    /**
     * 外部访问 meta
     * @return Array $this->meta
     */
    public function meta()
    {
        return $this->meta;
    }

    /**
     * 外部访问 glyphs
     * @return Array $this->glyphs
     */
    public function glyphs()
    {
        return $this->glyphs;
    }




    /**
     * 静态方法
     */

    /**
     * 远程读取 阿里图标库 Symbol js 文件，并解析得到 glyphs 数组
     * @param String $iset 此图标库的 本地名称
     * @param String $url js 文件的 远程地址
     * @return Array 解析得到的 glyphs 数组 以及 使用 本地名称处理后的 js 字符内容
     *  [
     *      "glyphs" => [ ... ],
     *      "js" => 使用 本地名称处理后的 js 字符内容
     *  ]
     */
    public static function parseIconfont($iset, $url)
    {
        if (!Is::nemstr($iset) || !Is::nemstr($url)) return null;

        //判断远程文件是否存在
        $exi = static::exists($url);
        if ($exi !== true) return null;

        //js 文件地址 转为 json 文件地址，可以直接通过 阿里图标库 js 地址得到对应的 json 文件地址
        if (substr($url, -3) === ".js") {
            $jsonurl = $url."on";
        } else {
            $jsonurl = $url.".json";
        }

        //读取文件内容
        $js = Curl::get($url, "ssl");
        $json = Curl::get($jsonurl, "ssl");
        $json = Conv::j2a($json);

        //修改js文件中的 css_prefix_text
        $js = str_replace("id=\"icon-", "id=\"$iset-", $js);

        //读取 svg path 信息，写入 svg 数组
        $svg = [];
        $svgs = explode("<svg>", explode("</svg>", $js)[0])[1];
        $svgarr = explode("</symbol>", $svgs);
        $svgcnt = "";
        $sublen = strlen("$iset-");
        for ($i=0;$i<count($svgarr);$i++) {
            $si = $svgarr[$i];
            $ki = substr(explode("\"", explode("id=\"", $si)[1])[0], $sublen);
            $svg[$ki] = $svgh.str_replace("<symbol", "<svg", $si)."</svg>";
        }

        //创建 glyphs
        $glyphs = [];
        $ogs = $json["glyphs"] ?? [];
        foreach ($ogs as $i => $ogi) {
            $ki = $ogi["font_class"];
            if (!isset($svg[$ki])) continue;
            $glyphs[$ki] = Arr::extend(static::$stdGlyph, [
                "name" => $ki,
                "class" => $iset."-".$ki,
                "svg" => $svg[$ki],
                //来自 阿里图标库 json 的数据
                "id" => $ogi["icon_id"] ?? "",
                "desc" => $ogi["name"] ?? ""
            ]);
        }

        //输出解析结果
        return [
            "glyphs" => $glyphs,
            "js" => $js
        ];
    }
}
