<?php
/**
 * Compound 复合资源处理类 子类
 * 定义 Icon 类型的 图标库 *.icon.json 本地图标库文件
 * 
 * Spf 框架视图图标库 *.icon 文件基于 iconfont.cn 阿里图标库的 Symbol 图标引用方式
 * 图标库 *.icon.json 文件必须为本地文件 !!，必须包含下列项目：
 *      root            必须关联到阿里图标库的某个 Symbol 引用的 js 文件，例如：//at.alicdn.com/t/c/font_4910918_t1l430kzeb.js
 *      iconset         此图标库的名称 foo-bar 形式，此名称将影响内部所有图标的 引用类名|单独访问的图标名，例如：内部图标名为 foo-bar-success
 *      iconcss         此图标库使用的样式文件，默认使用 [框架 assets]/icon/base.css，可手动指定自定义的 css 文件
 *      其他项目 与 通用 复合资源描述数据一致
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
 *   0  首次访问时，将远程获取 阿里图标库 js 文件，解析并得到库中所有图标的 name 以及 svg 代码
 *   1  首次访问将创建缓存文件 glyphs.json 和 [iconset].js ，再次访问时，将直接使用已缓存的 图标列表 glyphs
 *   2  根据 解析得到的（或缓存的）glyphs 数组，以及传入的 export|icon 参数，决定输出 js|css|svg 文件
 * 
 * 图标库中的 图标 依赖 阿里图标库--我的项目 来进行管理，也可以通过指定不同的 阿里 js 文件地址，让同一个图标库名称 指向 不同的 阿里图标库
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\ResourceSeeker;
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
use Spf\util\Curl;

class Icon extends Compound 
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 在父类基础上扩展
     */
    public static $stdParams = [
        //输出某个具体的 图标 svg，此参数如果指定，则忽略 export 参数
        "icon" => "",
        
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
            //从 缓存文件 或 阿里图标库远程 读取图标库内容，创建|更新 缓存文件，并将数据缓存到图标库实例
            "GetIconContent" => [],
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
        //此图标库的名称 foo-bar 形式，此名称将影响内部所有图标的 引用类名|单独访问的图标名，例如：内部图标名为 foo-bar-success
        "iconset" => "",

        //此图标库是否多色图标，默认 false
        "multicolor" => false,

        //指定此图标库使用的 css 文件 默认使用 spf/assets/icon/base.css
        "iconcss" => "spf/assets/icon/base.css",

        //允许输出的 ext 类型数组，必须是 Mime 类中支持的后缀名类型
        "ext" => ["js","css","svg"],

        /**
         * 复合资源的根路径
         *  0   针对本地的复合资源 Theme | Icon | Vcom | Lib 等类型：
         *      此参数表示 此复合资源的本地保存路径，不指定则使用当前 *.ext.json 文件的 同级同名文件夹
         *  1   针对远程复合资源 Cdn 类型：
         *      此参数表示 cdn 资源的 url 前缀，不带版本号
         * !! 此处的 根路径指定 阿里图标库的某个 Symbol 引用的 js 文件地址 通常以 //at.alicdn.com/t/c/... 开头
         */
        "root" => "",


    ];

    /**
     * 定义复合资源 内部子资源的 描述参数
     * !! 在父类基础上扩展
     */
    public static $stdSubResource = [
        //子资源类型，可以是 static | dynamic   表示   静态的实际存在的 | 动态生成的   子资源内容
        //!! Icon 子资源默认为 dynamic 类型
        "type" => "dynamic",
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

    //当前图标库的 glyphs 图标列表 缓存
    public $glyphs = [];
    //当前图标库的 js 代码缓存
    public $jscode = "";


    
    /**
     * 资源实例内部定义的 stage 处理方法
     * @param Array $params 方法额外参数
     * @return Bool 返回 false 则终止 当前阶段 后续的其他中间件执行
     */
    //GetIconContent 从 阿里图标库远程  或  缓存文件  中获取图标库内容数据
    public function stageGetIconContent($params=[])
    {
        //启用缓存
        $cacheEnabled = $this->cacheEnabled();
        //强制忽略缓存
        $cacheIgnored = $this->cacheIgnored();

        //启用缓存 且 未指定忽略缓存 的情况下，在资源内部 缓存路径下，查找 icon-content.json 文件
        if ($cacheEnabled===true && $cacheIgnored!==true) {
            //读取缓存的 icon-content
            $icnt = $this->cacheGetIconContent();
            if (Is::nemarr($icnt)) {
                //缓存读取成功，已将缓存数据 添加到当前图标库实例
                return true;
            }
        }

        //从 阿里图标库 获取全部定义的 glyphs 以及 js 文件内容，将保存到实例，并 创建|更新 缓存
        $get = $this->getAliIconContent();
        if ($get !== true) {
            //远程获取 阿里图标库数据 失败
            throw new SrcException("远程获取图标库 ".$this->desc["iconset"]." 未得到正确的结果，或者未能正确写入缓存文件", "resource/getcontent");
        }

        return true;
    }



    /**
     * 工具方法 解析复合资源内部 子资源参数，查找|创建 子资源内容
     * 根据  子资源类型|子资源ext|子资源文件名  分别制定对应的解析方法
     * !! Compound 子类可覆盖此方法
     * @return $this
     */
    //创建 svg 子资源实例
    protected function createDynamicSvgSubResource()
    {
        //iset
        $iset = $this->desc["iconset"];
        //要输出的 图标
        $icon = $this->params["icon"] ?? null;
        if (!Is::nemstr($icon)) {
            //未指定要输出的 图标
            throw new SrcException("未指定要输出的图标名称", "resource/export");
        }
        //指定要输出某个具体图标的 svg
        $glyph = $this->glyphs[$icon] ?? [];
        $gsvg = $glyph["svg"] ?? null;
        if (!Is::nemstr($gsvg)) {
            //要输出的 icon 不在此图标库中
            throw new SrcException("未找到图标 ".$icon, "resource/export");
        }
        
        //glyph 转为标准的 svg 代码格式
        $gsvg = Svg::glyphToSvg($gsvg);
        if (!Is::nemstr($gsvg)) {
            //生成标准 svg 代码错误
            throw new SrcException("图标 ".$icon." 无法生成 SVG 图标", "resource/export");
        }

        //创建 svg 资源
        $svg = Resource::manual(
            $gsvg,
            "$iset-$icon.svg",
            [
                //ext
                "ext" => "svg",
                //belongTo
                "belongTo" => $this,
                //不忽略 $_GET
                "ignoreGet" => false,

            ]
        );
        if (!$svg instanceof Svg) {
            throw new SrcException("图标 ".$icon." 无法生成 SVG 资源实例", "resource/export");
        }

        //保存到 subResource
        $this->subResource = $svg;

        return $this;
    }
    //创建 js 子资源实例
    protected function createDynamicJsSubResource()
    {
        //已在 stageGetIconContent 阶段 从 阿里图标库 获取过 js 文件内容，直接使用
        $this->content = $this->jscode;

        return $this;
    }
    //创建 css 子资源实例
    protected function createDynamicCssSubResource()
    {
        $desc = $this->desc;
        $icss = $desc["iconcss"] ?? "spf/assets/icon/base.css";
        //查找 css 文件
        $csf = Path::find($icss, Path::FIND_FILE);
        
        if (file_exists($csf)) {
            //创建 css|scss 资源实例
            $this->subResource = Resource::create($csf, [
                //scss 文件自动输出为 css
                "export" => "css"
            ]);
        } else {
            //未找到文件，返回简易 spf-icon 样式
            $this->content = ".spf-icon {width: 1em; height: 1em; vertical-align: -0.15em; fill: currentColor; overflow: hidden;}";
        }
        
        return $this;
    }



    /**
     * 工具方法 初始化复合资源 desc 工具
     */

    /**
     * 处理 export 参数，确保 export 输出类型，在 desc["ext"] 数组中
     * !! 覆盖父类
     * @return $this
     */
    protected function initExport()
    {
        $desc = $this->desc;
        $ps = $this->params;

        //支持的 ext 输出类型
        $exts = $desc["ext"] ?? [];

        if (isset($ps["icon"]) && Is::nemstr($ps["icon"])) {
            //如果指定了 params["icon"] 表示输出某个具体的 svg 图片，调整 export 为 svg
            $this->params["export"] = "svg";
            $this->setExt("svg");
        } else if (!isset($ps["export"]) || !Is::nemstr($ps["export"])) {
            //未指定 export 则使用第一个支持的 ext 类型作为输出类型
            $this->params["export"] = $exts[0];
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
     * !! 覆盖父类
     * @return $this
     */
    protected function initRoot()
    {
        $desc = $this->desc;
        $root = $desc["root"] ?? null;

        //指定的 root 必须是有效的 阿里图标库 symbol 引用的 js 文件地址
        if (!Is::nemstr($root) || Mime::getExt($root)!=="js" || Resource::exists($root)!==true) {
            //指定的 js 地址无效
            throw new SrcException("当前图标库 ".$this->resBaseName()." 指定的阿里图标库 JS 文件路径无效", "resource/getcontent");
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

        //desc
        $desc = $this->desc;
        $iset = $desc["iconset"] ?? null;
        if (!Is::nemstr($iset)) return null;
        //export ext
        $ext = $this->ext;
        
        return "$iset.$ext";
    }

    /**
     * 获取 glyphs.json 和 [iconset].js 缓存文件内容
     * @return Array 从 阿里图标库 获取的 内容数据
     *  [
     *      "glyphs" => [],
     *      "js" => ""
     *  ]
     */
    public function cacheGetIconContent()
    {
        $iset = $this->desc["iconset"] ?? null;
        if (!Is::nemstr($iset)) return null;

        //缓存文件名
        $glyfn = "glyphs.json";
        $jsfn = "$iset.js";
        $glyf = $this->cachePath($glyfn, true);
        $jsf = $this->cachePath($jsfn, true);
        if (!file_exists($glyf) || !file_exists($jsf)) return null;

        //读取缓存
        $glyphs = file_get_contents($glyf);
        $jscode = file_get_contents($jsf);
        if (!Is::nemstr($glyphs) || !Is::nemstr($jscode)) return null;

        //解析
        $glyphs = Conv::j2a($glyphs);
        if (!Is::nemarr($glyphs)) return null;

        //缓存到资源实例
        $this->glyphs = $glyphs;
        $this->jscode = $jscode;

        return [
            "glyphs" => $glyphs,
            "jscode" => $jscode
        ];
    }

    /**
     * 保存 glyphs.json 和 [iconset].js 缓存文件
     * @return Bool
     */
    public function cacheSaveIconContent()
    {
        $iset = $this->desc["iconset"] ?? null;
        if (!Is::nemstr($iset)) return null;

        //缓存文件名
        $glyfn = "glyphs.json";
        $jsfn = "$iset.js";
        $glyf = $this->cachePath($glyfn);
        $jsf = $this->cachePath($jsfn);

        //要缓存的数据
        $glyphs = $this->glyphs;
        $jscode = $this->jscode;
        if (!Is::nemarr($glyphs) || !Is::nemstr($jscode)) return false;
        
        //转换
        $glyphs = Conv::a2j($glyphs);
        
        //写入缓存文件
        return Path::mkfile($glyf, $glyphs) && Path::mkfile($jsf, $jscode);
    }




    /**
     * 工具方法 getters 资源信息获取
     */

    /**
     * 远程 获取此图标库中的 所有定义的 glyphs 数组  以及 js 文件内容
     * 获取到后 创建|更新 缓存文件
     * @return Bool 返回 获取成功 或 失败 状态
     */
    public function getAliIconContent()
    {
        //从 阿里图标库 获取全部定义的 glyphs
        $desc = $this->desc;
        $iset = $desc["iconset"] ?? null;
        $root = $desc["root"] ?? null;
        $pctx = static::parseAliIconfont($iset, $root);
        if (!Is::nemarr($pctx)) {
            //解析 阿里图标库文件 失败
            throw new SrcException("解析图标库 $iset 发生错误，缺少必要参数", "resource/getcontent");
        }
        $glyphs = $pctx["glyphs"] ?? null;
        $js = $pctx["jscode"] ?? null;
        if (!Is::nemarr($glyphs) || !Is::nemstr($js)) {
            //解析结果错误
            throw new SrcException("解析图标库 $iset 未得到正确的结果", "resource/getcontent");
        }
        
        //缓存到资源实例
        $this->glyphs = $glyphs;
        $this->jscode = $js;

        //创建|更新 缓存
        if ($this->cacheEnabled() === true) {
            //缓存 icon-content.json
            return $this->cacheSaveIconContent();
        }

        //返回结果
        return true;
    }



    /**
     * 静态工具
     */

    /**
     * 外部直接读取图标库 json 文件，并返回传入的 key 值
     * @param String $json *.icon.json 文件真实路径
     * @param String $key 要查询的图标库参数 key 可用 Arr::find 方法查找
     * @return Mixed
     */
    public static function getIconDesc($json, $key="")
    {
        if (!Is::nemstr($json) || !file_exists($json)) return null;
        $desc = file_get_contents($json);
        $desc = Conv::j2a($desc);
        if (!is_array($desc)) return null;
        //通过 Arr::find 查找
        return Arr::find($desc, $key);
    }

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
    public static function parseAliIconfont($iset, $url)
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
            "jscode" => $js
        ];
    }

    /**
     * 传入 foo/bar/pre-iconname 形式字符串，从中解析出对应的 图标库 json 路径，例如：
     * 存在图标库  icon/foo/bar.icon.json ，其图标库前缀为 ico-bar 则：
     *      传入 foo/ico-bar-success            -->  [ json=>"图标库文件真实路径", icon=>"success" ]
     *      传入 foo/bar/danger                 -->  [ json=>"图标库文件真实路径", icon=>"danger" ]
     *      传入 foo/bar/warning-fill           -->  [ json=>"图标库文件真实路径", icon=>"warning-fill" ]
     *      传入 foo/bar/ico-bar-eye-close      -->  [ json=>"图标库文件真实路径", icon=>"eye-close" ]
     * @param String $path
     * @return Array|false 未找到图标库 返回 false，否则返回：
     *  [
     *      "json" => "真实存在的 图标库文件路径",
     *      "icon" => "对应的 图标库内部图标名称"
     *  ]
     */
    public static function findByPath($path)
    {
        if (!Is::nemstr($path)) return false;

        //拆分路径
        $path = str_replace(["\\", DS], "/", $path);
        $parr = explode("/", $path);
        //pre-iconname 图标名字符串
        $icfn = array_slice($parr, -1)[0];
        $parr = array_slice($parr, 0, -1);

        //首先查找一次 parr 路径是否指向真实存在的 图标库文件
        if (Is::nemarr($parr)) {
            $jsonp = implode("/", $parr).".icon.json";
            $opts = ResourceSeeker::seek($jsonp);
            if (Is::nemarr($opts)) {
                //返回找到的 图标库信息
                $icfp = $opts["real"];
                //如果图标名不含 - 直接返回
                if (strpos($icfn, "-")===false) {
                    return [
                        "json" => $icfp,
                        "icon" => $icfn
                    ];
                }
    
                //如果图标名包含 - 则尝试去除图标名中的前缀部分
                //图标库前缀
                $ipre = static::getIconDesc($icfp, "iconset");
                if (!Is::nemstr($ipre)) $ipre = "";
                $iprelen = strlen($ipre)+1;
                //从图标名中 去除可能存在的 前缀，得到实际图标名
                if (substr($icfn, 0, $iprelen) === "$ipre-") {
                    $icfn = substr($icfn, $iprelen);
                }
                
                //返回找到的数据
                return [
                    "json" => $icfp,
                    "icon" => $icfn
                ];
            }
        }

        //如果 图标路径不指向某个真实存在的 图标库，且 图标名不含 - 连接符，则无法获取图标库
        if (strpos($icfn, "-")===false) return false;

        //图标库类型复合资源的 默认保存路径 
        $dfp = static::$filePath;
        if ($dfp === "ext") $dfp = Str::snake(Cls::name(static::class),"_");
        if (Is::nemstr($dfp)) {
            array_unshift($parr, $dfp);
        }

        //在本地查找 传入的 图标库所在路径 查找文件夹 返回所有存在的文件夹
        $icdirs = ResourceSeeker::seekLocal(implode("/", $parr), true, true);
        if (!Is::nemarr($icdirs)) return false;

        //在这些文件夹下 查找所有可用的 图标库文件
        $rtn = [
            "json" => "",
            "icon" => ""
        ];
        foreach ($icdirs as $icdir) {
            $cdh = opendir($icdir);
            while (false !== ($fn = readdir($cdh))) {
                if (in_array($fn, [".",".."]) || is_dir($icdir.DS.$fn)) continue;
                if (substr($fn, -10)!==".icon.json") continue;
                //获取此图标库的 前缀
                $icfp = $icdir.DS.$fn;
                //图标库前缀
                $ipre = static::getIconDesc($icfp, "iconset");
                if (!Is::nemstr($ipre)) continue;
                $iprelen = strlen($ipre)+1;
                if (substr($icfn, 0, $iprelen) === "$ipre-") {
                    $rtn["json"] = $icfp;
                    $rtn["icon"] = substr($icfn, $iprelen);
                    unset($ires);
                    break;
                }
            }
            closedir($cdh);

            //只返回找到的 第一个 资源
            if (Is::nemstr($rtn["json"])) break;
        }

        //找到图标库 则返回
        if (Is::nemstr($rtn["json"])) return $rtn;
        return false;
    }

    /**
     * 定义处理 复合资源请求的 代理响应方法，在 Src 模块中，可使用此方法，响应前端请求
     * !! 覆盖父类，实现 图标库 自有的 响应代理方法
     * @param Array $args 前端请求 URI 数组
     * @return Mixed 
     */
    public static function responseProxyer(...$args)
    {
        if (!Is::nemarr($args)) {
            Response::insSetCode(404);
            return null;
        }

        /**
         * 此处定义 图标库 自有的 代理响应方法
         * 按以下规则，对请求进行解析：  针对 图标库  [accessable-path]/foo/bar.icon.json
         *      https://host/src/icon/foo/bar/@/default.js          -->  /src/foo/bar.icon?ver=@&file=default@export=js
         *      https://host/src/icon/foo/bar/default.js            -->  /src/foo/bar.icon?ver=@&file=default@export=js
         *      https://host/src/icon/foo/bar/latest/default.css    -->  /src/foo/bar.icon?ver=latest&file=default&export=css
         *      https://host/src/icon/foo/bar/1.2.3/success.svg     -->  /src/foo/bar.icon?ver=1.2.3&file=default&icon=success
         *      https://host/src/icon/foo/bar/success.svg           -->  /src/foo/bar.icon?ver=@&file=default&icon=success
         *      图标库 图标前缀为 icopre 则可以这样访问 内部 svg 图标
         *      https://host/src/icon/foo/[bar/]icopre-success.svg  -->  /src/foo/bar.icon?ver=@&file=default&icon=success
         * 
         *      可进入图标库预览视图
         *      https://host/src/icon/foo/bar/[@|latest|1.0.0/]preview.html
         */
        //拼接 URI
        $uri = implode("/", $args);
        if (strpos($uri, "/")===false) $uri = "/".$uri;

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
        
        //整理 匹配到的 资源请求参数
        $params = [
            "ver" => $ver
        ];
        
        if (substr($efp, -4) === ".svg") {
            //!! 针对 直接访问 *.svg 图标库内部某个 svg 图标的情况

            //icon 参数
            $icon = substr($efp, 0, -4);
            //根据传入的 文件路径 和 图标名，查找 图标库路径和 图标真实名
            $icop = (!Is::nemstr($cfp) ? "" : $cfp."/").$icon;
            //var_dump($icop);
            $icoi = static::findByPath($icop);
            //var_dump($icoi);exit;
            if ($icoi === false) {
                //未找到图标库
                Response::insSetCode(404);
                return null;
            }
            //更新图标库路径
            $cfp = $icoi["json"];
            $params["icon"] = $icoi["icon"];

        } else {

            if ($efp === "preview.html") {
                //进入 图标库 预览视图
                $preview = true;
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
                }
                //如果 file 中包含 .min
                if (strpos($file, ".min")!==false) {
                    $file = str_replace(".min","", $file);
                    $params["min"] = true;
                }
                $params["file"] = $file;
                $params["export"] = $export;
            }

            //查找 复合资源的 json 文件路径
            $cfp = rtrim($cfp, "/").".icon.json";
        }

        //var_dump($params);
        //var_dump($cfp);
        //exit;
        
        //尝试创建 复合资源实例
        $res = Resource::create($cfp, $params);
        if (!$res instanceof Compound) {
            //无法创建资源实例
            Response::insSetCode(404);
            return null;
        }
        //var_dump($res);

        //进入预览试图
        if ($preview === true) {
            
            //修改 当前响应方法的 输出类为 view 视图
            Response::insSetType("view");
            
            return [
                //使用 视图页面 spf/view/iconset.php
                "view" => "spf/assets/view/iconset.php",
                //传入 Icon 资源实例作为视图页面参数
                "params" => [
                    "icon" => $res
                ]
            ];
        }

        //返回创建好的 资源实例
        return $res;


    }
}