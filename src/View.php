<?php
/**
 * 框架核心类
 * View 视图类，基类
 */

namespace Spf;

use Spf\Request;
use Spf\exception\BaseException;
use Spf\exception\CoreException;
use Spf\config\ViewConfig;
use Spf\module\src\Resource;
use Spf\module\src\ResourceSeeker;
use Spf\module\src\resource\Vcom;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Conv;
use Spf\util\Path;
use Spf\util\Url;

class View extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;



    /**
     * 视图参数
     */
    //视图页面 文件路径，已确认存在的 *.php 本地文件路径
    //!! 视图内容 必须包裹在 $view->renderStart() 和 $view->renderEnd() 方法中间
    public $page = "";
    //本次视图输出的 custom 参数，这些参数将被注入 视图页面
    public $custom = [];

    //当前视图的 要输出的 html 内容行数组
    protected $content = [];

    /**
     * 视图使用的 Vcom|Vcom3|Theme|Icon 等复合资源的实例缓存
     */
    //SPA 环境 基础组件库
    public $spaBase = null;
    //SPA 环境 业务组件库，[]
    public $spaApp = [];
    //SPF-Theme 主题资源
    public $theme = null;
    //SPF-Icon 图标库资源，[]
    public $iconset = [];

    /**
     * 要通过 link|script 插入 head 段 的资源路径
     */
    public $links = [
        /*
        [
            "href" => "",
            "rel" => "stylesheet",
            ...
        ]
        */
    ];
    public $scripts = [
        /*
        "/src/foo/bar.js",
        "module@/src/foo/bar/jaz.js",
        ...
        */
    ];



    /**
     * 此 View 视图类自有的 init 方法，执行以下操作：
     *  0   缓存 page 视图页面
     *  1   生成 视图使用的 Vcom|Vcom3|Theme|Icon 等复合资源的实例
     * !! Core 子类必须实现的，View 子类不要覆盖
     * @return $this
     */
    final public function initialize()
    {
        // 0 缓存 page 视图页面
        $page = $this->config->page;
        if (Is::nemstr($page)) {
            $pfp = Path::find($page, Path::FIND_FILE);
            if (Is::nemstr($pfp)) {
                //外部指定了 有效的视图页面
                $this->page = $page;
            }
        }
        //如果未指定有效的 视图页面，报错
        if (!Is::nemstr($this->page)) {
            throw new CoreException("无法初始化视图，未指定视图页面", "initialize/init");
        }
        //将指定的视图页面，转为真实路径
        $pfp = Path::find($this->page, Path::FIND_FILE);
        if (!Is::nemstr($pfp)) {
            throw new CoreException("无法初始化视图，未指定有效的视图页面", "initialize/init");
        }
        $this->page = $pfp;

        // 1 生成 视图使用的 Vcom|Vcom3|Theme|Icon 等复合资源的实例
        $resCreated = $this->initDependedResource();
        if ($resCreated !== true) {
            //有 依赖的资源实例化失败，报错
            throw new CoreException("无法初始化视图，有依赖的资源无法实例化", "initialize/init");
        }

        return $this;
    }

    /**
     * 生成 视图使用的 Vcom|Vcom3|Theme|Icon 等复合资源的实例
     * !! View 子类不要覆盖此方法
     * @return Bool
     */
    final protected function initDependedResource()
    {
        //先重置
        $this->resetResource();

        $rst = true;

        //是否启用的 SPA 环境
        $spaEnabled = $this->spaEnabled();

        if ($spaEnabled) {
            //实例化 SPA 组件库资源
            $rst = $this->createSpaResource();
        } else {
            //依次实例化 SPF-Theme|SPF-Icon 
            $rst = $rst && $this->createThemeResource();
            $rst = $rst && $this->createIconsetResource();
        }

        return $rst;
    }

    /**
     * 视图依赖的资源实例化方法
     * !! View 子类可根据需要覆盖这些方法
     * @param Array $conf 资源参数，不指定则使用 $view->config->compound[...]
     * @return Bool
     */
    //实例化 SPA 组件库资源
    protected function createSpaResource($conf=[])
    {
        if (!Is::nemarr($conf)) $conf = $this->config->compound["spa"] ?? [];
        if ($conf["enable"] !== true) return false;

        //SPA 基础组件库
        $base = $conf["base"] ?? [];
        $bf = $base["file"] ?? null;
        if (!Is::nemstr($bf) || !Is::nemstr(Path::find($bf, Path::FIND_FILE))) return false;

        //SPA 基础组件的 实例化参数
        $bp = $base["params"] ?? [];
        //创建基础组件临时资源
        $bres = Resource::create($bf, $bp);
        //判断基础组件库是否定义了必须的 依赖项
        if (!$bres instanceof Resource || $bres->desc["theme"]["enable"] !== true) return false;
        //所有 业务组件库 都使用 基础组件的 组件名 prefix
        $vcprefix = $bres->desc["prefix"];
        //基础组件的 combine 参数准备，需要将 各业务组件库的 default.scss 文件合并进来
        $bmgs = [];
        //SPA 组件库指定的 SPF-Icon 图标库资源，需要将各 业务组件库的 iconset 合并进来
        $iconset = $bres->desc["iconset"] ?? [];
        if (Is::nemarr($iconset)) $iconset = array_merge([], $iconset);
        //释放临时资源
        unset($bres);

        //SPA 业务组件
        $app = $conf["app"] ?? [];
        if (Is::nemarr($app)) {
            //SPA 环境依赖的 vcom 版本
            $vver = $this->spaVcomVer();
            foreach ($app as $appk => $appc) {
                $appf = $appc["file"] ?? null;
                if (
                    !Is::nemstr($appf) || 
                    //!! 指定的业务组件库，必须复合对应的 vcom 版本
                    strpos($appf, ".$vver.") === false || 
                    !Is::nemstr(Path::find($appf, Path::FIND_FILE))
                ) { continue; }
                $appp = $appc["params"] ?? [];
                //!!业务组件库使用 统一的 组件名前缀
                $appp["prefix"] = $vcprefix;
                $appp = $this->fixResParams($appp);
                $appres = Resource::create($appf, $appp);
                if (!$appres instanceof Resource) continue;

                //将业务组件库的 default.scss 合并到 SPA 基础组件库的 combine 参数中
                $bmgs[] = $appres->viewUrl("default.scss");
                //业务组件库中的 SPF-Icon 图标库，需要提取出来，统一加载
                $appicon = $appres->desc["iconset"] ?? [];
                if (Is::nemarr($appicon)) $iconset = array_merge($iconset, $appicon);

                //缓存
                $this->spaApp[$appk] = $appres;
            }
        }

        //处理 config->merge 参数中定义的 scss 资源，这些资源可能使用了 主题 scss 参数，需要合并到 SPA 基础组件库资源实例中
        $merges = $this->config->merge;
        if (Is::nemarr($merges)) {
            //提取其中的 scss 资源
            $mscsses = array_filter($merges, function($mscssi) {
                return Is::nemstr($mscssi) && substr($mscssi, -5) === ".scss";
            });
            $mscsses = array_merge([], $mscsses);
            if (Is::nemarr($mscsses)) {
                $bmgs = array_merge($bmgs, $mscsses);
            }
        }

        //实例化 SPA 基础组件库
        if (Is::nemarr($bmgs)) $bp["combine"] = $bmgs;
        $bp = $this->fixResParams($bp);
        //缓存
        $this->spaBase = Resource::create($bf, $bp);

        //加载 SPF-Icon 资源
        if (Is::nemarr($iconset) && Is::indexed($iconset)) {
            $iconres = $this->createIconsetResource($iconset);
        }

        return true;
    }
    //实例化视图依赖的 SPF-Theme 主题资源
    protected function createThemeResource($conf=[])
    {
        if (!Is::nemarr($conf)) $conf = $this->config->compound["theme"] ?? [];
        if ($conf["enable"] !== true) return false;

        $thfp = $conf["file"] ?? null;
        if (!Is::nemstr($thfp)) return false;
        if (substr($thfp, -11) !== ".theme.json") $thfp .= ".theme.json";
        $thf = Path::find($thfp, Path::FIND_FILE);
        if (!Is::nemstr($thf)) return false;

        //主题的实例化参数
        $thp = $conf["params"] ?? [];
        //需要添加到 主题资源 params["merge"] 参数中的 资源路径
        $thmgs = [];
        //处理 config->merge 参数中定义的 scss 资源，这些资源可能使用了 主题 scss 参数，需要合并到 主题资源实例中
        $merges = $this->config->merge;
        if (Is::nemarr($merges)) {
            //提取其中的 scss 资源
            $mscsses = array_filter($merges, function($mscssi) {
                return Is::nemstr($mscssi) && substr($mscssi, -5) === ".scss";
            });
            $mscsses = array_merge([], $mscsses);
            if (Is::nemarr($mscsses)) {
                $thmgs = array_merge($thmgs, $mscsses);
            }
        }
        if (Is::nemarr($thmgs)) $thp["combine"] = $thmgs;
        $thp = $this->fixResParams($thp);
        //实例化
        $thres = Resource::create($thf, $thp);
        if (!$thres instanceof Resource) return false;
        //缓存
        $this->theme = $thres;
        return true;
    }
    //实例化视图依赖的 SPF-Icon 图标库资源
    protected function createIconsetResource($conf=[])
    {
        if (!Is::nemarr($conf) || !Is::indexed($conf)) $conf = $this->config->compound["iconset"] ?? [];
        if (is_array($conf) && empty($conf)) return true;

        //依次实例化
        $loaded = [];
        foreach ($conf as $isfp) {
            if (!Is::nemstr($isfp)) continue;
            if (substr($isfp, -10) !== ".icon.json") $isfp .= ".icon.json";
            //!! 处理重复加载
            if (in_array($isfp, $loaded)) continue;
            $isf = Path::find($isfp, Path::FIND_FILE);
            if (!Is::nemstr($isf)) continue;
            //实例化
            $isres = Resource::create($isf, $this->fixResParams([
                //!! iconset 图标库不统一刷新，如果有修改，需要手动刷新
                "create" => false,
            ]));
            if (!$isres instanceof Resource) continue;
            //缓存
            $this->iconset[] = $isres;
            $loaded[] = $isfp;
        }
        //如果指定了 iconset 参数，但是没有创建任何实例，表示出错了
        if (!Is::nemarr($this->iconset)) return false;
        return true;
    }

    /**
     * 重置视图依赖的 资源缓存
     * @return $this
     */
    protected function resetResource()
    {
        $this->spaBase = null;
        $this->spaApp = [];
        $this->theme = null;
        $this->iconset = [];
        return $this;
    }

    /**
     * 运行时 修改 视图的默认参数，通常用于在 视图 php 页面内部指定 此视图的 默认参数，例如：页面标题|默认CSS 等
     * @param Array $init 新的 视图默认参数
     * @return $this
     */
    public function runtimeSetInit($init=[])
    {
        //调用 config 配置类的 runtimeSetInit 方法
        $this->config->runtimeSetInit($init);
        //再执行一次 initialize 方法
        return $this->initialize();
    }



    /**
     * render 方法
     * 入口方法：render 
     * 开始输出：renderStart 输出 html 头部
     * 结束输出：renderEnd 输出 html 结束标签
     * !! 视图页面的 任何 html 输出内容 必须包裹在 renderStart 和 renderEnd 这两个方法中间
     */

    /**
     * 生成视图的 核心内容
     * 视图页面 php 代码的运行环境 在此方法内部
     * @return String 最终输出的 html 
     */
    public function render()
    {
        //视图页面
        $page = $this->page;
        //本次 输出视图的 自定义参数 保存在 $this->custom
        $params = $this->custom;
        
        //准备 在 page 视图页面中可以直接访问的 变量
        foreach ($params as $k => $v) {
            $$k = $v;
        }
        //当前 View 实例
        $view = $this;
        
        //require 视图页面
        require($page);

        //从 输出缓冲区 中获取内容
        $html = ob_get_contents();
        //清空缓冲区
        ob_clean();

        //输出前执行模板替换
        if ($this->spaEnabled() === true) {
            $html = $this->replaceSpaVcomTpls($html);
        }

        //返回 html
        return $html;
    }

    /**
     * 开始 生成视图页面 html 内容，输出 html 头部
     * @param String $body 特别的 body 标签，不指定则使用默认的 <body>
     * @return void
     */
    public function renderStart($body=null)
    {
        //准备 视图 内容行数组
        $this->content = [];

        //必须严格按顺序执行
        $this->parseMeta();
        if ($this->spaEnabled() === true) {
            $this->parseSpa();
        } else if ($this->themeEnabled() === true) {
            $this->parseTheme();
        }
        $this->parseIconset();
        $this->parseLinks();
        $this->parseScripts();

        /**
         * !! 不自动应用 SPA 环境的 基础组件库|业务组件库 插件（同时注册全局组件）
         * 应由 各视图页面 在各自内部合适位置，自行执行 $view->useSpaPlugin() 方法
         *      $view->useSpaPlugin() 方法      将在调用位置插入 ...import 语句 以及 Vue.use() 语句...
         */
        /*if ($this->spaEnabled() === true) {
            $this->useSpaPlugin([....], true);
        }*/
        //结束 head
        $this->html("</head>");
        //开始 body
        if (!Is::nemstr($body)) $body = "<body>";
        $this->html($body);

        //输出 内容行数组
        $cnt = implode("\n", $this->content);
        echo $cnt;
    }
    
    /**
     * 结束 视图的 html 输出 echo 结束标签
     * @return void
     */
    public function renderEnd()
    {
        echo "\n</body>\n</html>";
    }

    /**
     * 解析视图参数，生成 对应的 html 语句，存入 content 内容行数组
     * !! 必须严格按顺序执行
     * @return Bool
     */
    // 0 解析 lang|charset|favicon|title|viewport|pwa 以及其他 meta 参数
    protected function parseMeta()
    {
        //核心配置类
        $cfger = $this->config;

        //解析 lang
        $lang = $cfger->lang;
        $this->html(
            "<!DOCTYPE html>",
            "<html lang=\"$lang\">",
            "<head>"
        );

        //解析 charset
        $charset = $cfger->charset;
        $this->html("<meta charset=\"$charset\">");

        //解析 favicon
        $fav = $cfger->favicon;
        if (isset($fav["href"]) && Is::nemstr($fav["href"])) {
            $fav = Arr::extend($fav, [
                //补齐完整路径，否则作为应用安装到本地时，无法获取应用图标
                "href" => Url::current()->domain.$fav["href"],
                //"id" => "favicon",
                "rel" => "icon",
            ]);
            $this->link([ $fav ]);
        }

        //解析 title
        $tit = $cfger->title;
        if (Is::nemstr($tit)) $this->html("<title>$tit</title>");
        
        //解析 viewport
        $vp = $cfger->viewport;
        //可选的 viewport 设置
        $vps = $cfger->viewports;
        if (!isset($vps[$vp])) $vp = "default";
        $vpc = $vps[$vp];
        if (Is::nemstr($vpc)) {
            //生成 html
            $this->meta([
                "viewport" => $vpc,
            ]);
        }

        //解析 pwa 参数
        $pwa = $cfger->pwa;
        $pwaon = $pwa["enable"] ?? false;
        if ($pwaon) {
            $pmeta = [];
            $plink = [];
            foreach ($pwa as $k => $v) {
                if ($k === "enable") continue;
                if (Is::nemstr($v)) {
                    $pmeta[$k] = $v;
                } else if (Is::nemarr($v)) {
                    //TODO：需要细化
                    //创建 link
                    $plink[] = Arr::extend($v, [
                        "rel" => $k
                    ]);
                }
            }
            //生成 html
            $this->meta($pmeta);
            $this->link($plink);
        }

        //解析其他 meta 参数
        $mns = ["description", "keywords", "author", "copyright", "robots"];
        $metas = [];
        foreach ($mns as $mn) {
            $mi = $cfger->$mn;
            if (Is::nemstr($mi)) {
                $metas[$mn] = $mi;
            }
        }
        $this->meta($metas);

        return true;
    }
    // 1 解析 SPA 环境
    protected function parseSpa()
    {
        if ($this->spaEnabled() !== true) return false;
        //SPA 基础组件库资源实例
        $spa = $this->spaBase;

        //输出 SPA 环境样式
        //使用 SPF-Theme 主题资源实例
        $theme = $spa->theme;
        //判断此主题是否支持 light|dark 暗黑模式
        $hasDarkMode = $theme->supportDarkMode();
        //如果支持暗黑模式，分别生成 light|dark 模式对应 字符串
        if ($hasDarkMode) {
            $modeShift = $theme->colorModeShift();
            $lightMode = $modeShift["light"];
            $darkMode = $modeShift["dark"];
        }
        //如果基础组件库使用了 第三方 UI 库，需要先输出 UI css
        if (Is::nemarr($spa->ui)) {
            if ($hasDarkMode) {
                //支持暗黑模式，分别将 light|dark 样式 url 插入 $this->links []
                $this->links = array_merge($this->links, $this->autoDarkCssLink(
                    $spa->viewUrl("ui.min.css", [
                        "theme" => $lightMode,
                        //!! 不统一刷新
                        "create" => false,
                    ]),
                    $spa->viewUrl("ui.min.css", [
                        "theme" => $darkMode,
                        //!! 不统一刷新
                        "create" => false,
                    ])
                ));
            } else {
                $this->links[] = [
                    "href" => $spa->viewUrl("ui.min.css", ["create" => false]),
                    "rel" => "stylesheet",
                ];
            }
        }
        //如果业务组件库使用了第三方 UI 库，也需要输出 css
        if (Is::nemarr($this->spaApp)) {
            foreach ($this->spaApp as $appk => $appres) {
                if (!Is::nemarr($appres->ui)) continue;
                if ($hasDarkMode) {
                    //支持暗黑模式，分别将 light|dark 样式 url 插入 $this->links []
                    $this->links = array_merge($this->links, $this->autoDarkCssLink(
                        $appres->viewUrl("ui.min.css", [
                            "theme" => $lightMode,
                            //!! 不统一刷新
                            "create" => false,
                        ]),
                        $appres->viewUrl("ui.min.css", [
                            "theme" => $darkMode,
                            //!! 不统一刷新
                            "create" => false,
                        ])
                    ));
                } else {
                    $this->links[] = [
                        "href" => $appres->viewUrl("ui.min.css", ["create" => false]),
                        "rel" => "stylesheet",
                    ];
                }
            }
        }
        //输出主题样式
        if ($hasDarkMode) {
            //支持暗黑模式，分别将 light|dark 样式 url 插入 $this->links []
            $this->links = array_merge($this->links, $this->autoDarkCssLink(
                $spa->viewUrl("browser.min.css", [
                    "combine" => true,
                    "theme" => $lightMode,
                ]),
                $spa->viewUrl("browser.min.css", [
                    "combine" => true,
                    "theme" => $darkMode,
                ])
            ));
        } else {
            //不支持 暗黑模式，仅添加默认 样式
            $this->links[] = [
                "href" => $spa->viewUrl("browser.min.css", [
                    "combine" => true,
                ]),
                "rel" => "stylesheet",
            ];
        }

        //输出 SPA 环境 js
        /**
         * !! Vue 库 以及 第三方 UI 库 不会统一刷新
         * 第三方库基本不会变化，因此如果发生改变，需要手动刷新
         */
        $ps = ["create" => false];
        //基础组件库实例中包含的 Vue 库实例
        $this->scripts[] = $spa->viewUrl("vue.min.js", $ps);
        //输出第三方 UI js
        if (Is::nemarr($spa->ui)) {
            $this->scripts[] = $spa->viewUrl("ui.min.js", $ps);
        }
        //业务组件库使用的 第三方 UI js
        if (Is::nemarr($this->spaApp)) {
            foreach ($this->spaApp as $appk => $appres) {
                if (!Is::nemarr($appres->ui)) continue;
                $this->scripts[] = $appres->viewUrl("ui.min.js", $ps);
            }
        }

        return true;
    }
    // 2 解析 theme|css 参数
    protected function parseTheme()
    {
        if ($this->themeEnabled() !== true) return false;

        //SPF-Theme 主题资源实例
        $theme = $this->theme;
        //判断此主题是否支持 light|dark 暗黑模式
        if ($theme->supportDarkMode() === true) {
            //分别将 light|dark 样式 url 插入 $this->links []
            $modeShift = $theme->colorModeShift();
            $this->links = array_merge($this->links, $this->autoDarkCssLink(
                $theme->viewUrl("default.min.css", [
                    "combine" => true,
                    "mode" => $modeShift["light"],
                ]),
                $theme->viewUrl("default.min.css", [
                    "combine" => true,
                    "mode" => $modeShift["dark"],
                ])
            ));
        } else {
            //不支持暗黑模式，仅添加默认样式
            $this->links[] = [
                "href" => $theme->viewUrl("default.min.css", [
                    "combine" => true,
                ]),
                "rel" => "stylesheet",
            ];
        }

        return true;
    }
    // 3 解析 iconset 图标库参数
    protected function parseIconset()
    {
        //已实例化的 iconset 资源实例
        $isets = $this->iconset;
        if (!Is::nemarr($isets)) return true;

        //依次插入各 iconset 图标库的 js|css
        foreach ($isets as $iset) {
            $pather = $iset->PathProcessor;
            //js
            $this->scripts[] = $pather->innerUrl("default.min.js");
            //css
            $this->links[] = [
                "href" => $iset->resUrlSelf("default.css", [
                    "export" => "css",
                    "min" => true,
                ]),
                "rel" => "stylesheet",
            ];
        }
        return true;
    }
    // 4 解析 $this->links 以及 config->static[] 中定义的 css url
    protected function parseLinks()
    {

        //var_dump($this->links);exit;

        //先插入 $this->links
        if (Is::nemarr($this->links)) $this->link($this->links);

        //再处理 config->static 中定义的 静态 css url
        $statics = $this->config->static;
        $links = [];
        if (Is::nemarr($statics)) {
            foreach ($statics as $href) {
                if (!Is::nemstr($href) || substr($href, -4) !== ".css") continue;
                //写入 $links
                $links[] = [
                    "href" => $href,
                    "rel" => "stylesheet",
                ];
            }
        }
        if (Is::nemarr($links)) $this->link($links);
        return true;
    }
    // 5 解析 $this->scripts 以及 config->static[] 中定义的 js url
    protected function parseScripts()
    {
        //先插入 $this->scripts
        if (Is::nemarr($this->scripts)) $this->script(...$this->scripts);

        //再处理 config->static 中定义的 静态 js url
        $statics = $this->config->static;
        $scripts = [];
        if (Is::nemarr($statics)) {
            foreach ($statics as $src) {
                if (!Is::nemstr($src) || substr($src, -3) !== ".js") continue;
                //写入 $scripts
                $scripts[] = $src;
            }
        }
        if (Is::nemarr($scripts)) $this->script(...$scripts);
        return true;
    }



    /**
     * content 内容行数组 操作方法
     */

    /**
     * 向 内容行数组 插入 html
     * @param Array $htmls 一行 或 多行数据
     * @return $this
     */
    public function html(...$htmls)
    {
        $this->content = array_merge($this->content, $htmls);
        return $this;
    }

    /**
     * 向 内容行数组 插入 <meta name="..." content="...">
     * @param Array $meta 要插入的 meta 数据 [ "name..." => "content...", ... ]
     * @return $this
     */
    public function meta($meta=[])
    {
        if (!Is::nemarr($meta) || !Is::associate($meta)) return $this;
        //要插入的 meta 语句
        $mts = [];
        foreach ($meta as $n => $c) {
            if (!Is::nemstr($n) || !Is::nemstr($c)) continue;
            $mts[] = "<meta name=\"$n\" content=\"$c\">";
        }
        //插入 content
        return $this->html(...$mts);
    }

    /**
     * 向 内容行数组 插入 <link rel="..." href="..." ...>
     * @param Array $links indexed 数组[ [ rel=>..., href=..., type=>..., media=>..., ... ], ... ]
     * @return $this
     */
    public function link($links=[])
    {
        if (!Is::nemarr($links) || !Is::indexed($links)) return $this;
        //要插入的 html
        $ls = [];
        foreach ($links as $link) {
            if (!Is::nemarr($link) || !Is::associate($link)) continue;
            $lsi = [];
            foreach ($link as $n => $c) {
                $lsi[] = "$n=\"$c\"";
            }
            $ls[] = "<link ".implode(" ", $lsi).">";
        }
        //插入
        return $this->html(...$ls);
    }

    /**
     * 向 内容行数组 插入 <style>...</style>
     * !! 自动编译 scss
     * @param String $css 样式代码，支持传入 scss 代码，将自动编译后 插入
     * @return $this
     */
    public function style($css)
    {
        if (!Is::nemstr($css)) return $this;

        //创建临时 scss 资源，对 css|scss 代码执行编译，minify
        $scss = Resource::manual(
            $css,
            "temp.scss",
            [
                "ext" => "scss",
                "export" => "css",
                "ignoreGet" => true,
                //默认 minify
                "min" => true,
                //import 保持原样
                "import" => "keep",
            ]
        );
        //通过资源实例输出 编译|minify 后的 css 代码
        $css = $scss->export([
            "return" => true
        ]);
        //释放临时资源
        unset($scss);

        //插入内容行数组
        return $this->html("<style>$css</style>");
    }

    /**
     * 向 内容行数组 插入 <script src="..."></script>
     * @param Array $srcs js src 以 module@ 开头的 则插入 <script type="module" src="..."></script>
     * @return $this
     */
    public function script(...$srcs)
    {
        $srcs = array_filter($srcs, function($src) {
            return Is::nemstr($src);
        });
        $s = [];
        foreach ($srcs as $src) {
            if (substr($src, 0, 7) === "module@") {
                $s[] = "<script type=\"module\" src=\"$src\"></script>";
            } else {
                $s[] = "<script src=\"$src\"></script>";
            }
        }
        return $this->html(...$s);
    }



    /**
     * SPA 环境的 工具方法
     */

    /**
     * 判断当前视图是否启用了 统一 SPA 环境
     * @return Bool
     */
    public function spaEnabled()
    {
        $cfger = $this->config;
        //spa 参数
        $spa = $cfger->compound["spa"];
        return $spa["enable"] === true;
    }

    /**
     * 判断 SPA 环境依赖的 vcom 版本
     * @return String vcom|vcom3
     */
    public function spaVcomVer()
    {
        $spa = $this->config->compound["spa"] ?? [];
        $base = $spa["base"] ?? [];
        $bf = $base["file"] ?? null;
        if (!Is::nemstr($bf) || substr($bf, -5) !== ".json") return "vcom";    //默认 vcom Vue2.x
        $bf = substr($bf, 0, -5);
        return array_slice(explode(".", $bf), -1)[0];
    }

    /**
     * 生成用于 视图页面 html 内容 字符串模板替换的 数据源
     * @return Array 
     *  [
     *      "vcinfo" => [ ... SPA 环境基础组件库的相关数据集合 ... ],
     *      "tpls" => [
     *          "模板字符串" => "在 vcinfo 数组中的 键名 xpath 例如: urls/component",
     *          ...
     *      ],
     *  ]
     */
    public function spaVcomTpls()
    {
        if ($this->spaEnabled() !== true) return [];

        //结果
        $rtn = [];

        //数据源，由 基础组件库 $this->spaBase->resVcomInfo() 方法生成
        $vcinfo = $this->spaBase->resVcomInfo();
        $tpls = [
            //组件库定义的 组件名称前缀，通常用于 style 样式代码中的 样式类名称
            "__PRE__" => "pre",
            //用于组件模板代码块中，代替 组件名称前缀，以便可以方便的 在不同的使用场景下，切换组件名称前缀
            //例如：<PRE@-button>...</PRE@-button> 替换为 <pre-button>...</pre-button>
            "PRE@" => "pre",
    
            //针对组件库的 特殊路径字符串模板
            //component 文件夹名
            "__COMPONENT__" => "dirnames/component",
            //mixin 文件夹名
            "__MIXIN__" => "dirnames/mixin",
            //plugin 文件夹名
            "__PLUGIN__" => "dirnames/plugin",
            //common 文件夹名
            "__COMMON__" => "dirnames/common",
    
            //component 文件夹 url 前缀
            "__URL_COMPONENT__" => "urls/component",
            //mixin 文件夹 url 前缀
            "__URL_MIXIN__" => "urls/mixin",
            //plugin 文件夹 url 前缀
            "__URL_PLUGIN__" => "urls/plugin",
            //common 文件夹 url 前缀
            "__URL_COMMON__" => "urls/common",
        ];
        $rtn["base"] = [
            "vcinfo" => $vcinfo,
            "tpls" => $tpls
        ];

        //各业务组件库的 模板替换数据源
        if (Is::nemarr($this->spaApp)) {
            $apptpls = [];
            foreach ($this->spaApp as $appk => $appres) {
                $appvcinfo = $appres->resVcomInfo();
                /**
                 * 字符串模板 增加 APPK_ 前缀
                 * 例如：
                 *      __PRE__             --> __PMS_PRE__
                 *      PRE@                --> PMS_PRE@
                 *      __URL_COMPONENT__   --> __PMS_URL_COMPONENT__
                 */
                $appp = strtoupper($appk);
                $atpls = [];
                foreach ($tpls as $tplk => $tplv) {
                    if (substr($tplk, 0,2) === "__") {
                        $tplk = "__".$appp."_".substr($tplk, 2);
                    } else {
                        $tplk = $appp."_".$tplk;
                    }
                    $atpls[$tplk] = $tplv;
                }
                $apptpls[$appk] = [
                    "vcinfo" => $appvcinfo,
                    "tpls" => $atpls
                ];
            }
            $rtn["app"] = $apptpls;
        }

        return $rtn;
    }

    /**
     * 在生成 html 后自动替换 SPA 环境基础组件库的 设定的 组件名|样式类名 前缀字符串模板
     * @param String $html 已生成的 html
     * @return String 字符串模板替换后的 html
     */
    public function replaceSpaVcomTpls($html)
    {
        if ($this->spaEnabled() !== true || !Is::nemstr($html)) return $html;

        //生成 用于模板替换的 数据源
        $rtpl = $this->spaVcomTpls();

        //先替换 业务组件库的 字符串模板
        $apps = $rtpl["app"] ?? [];
        if (Is::nemarr($apps)) {
            foreach ($apps as $appk => $atpl) {
                $vci = $atpl["vcinfo"] ?? [];
                $tpls = $atpl["tpls"] ?? [];
                if (!Is::nemarr($tpls)) continue;
                foreach ($tpls as $tpl => $prop) {
                    $vd = Arr::find($vci, $prop);
                    if (!is_string($vd)) continue;
                    
                    //替换
                    $html = str_replace($tpl, $vd, $html);
                }
            }
        }

        //再替换 基础组件库的 字符串模板
        $base = $rtpl["base"] ?? [];
        $vcinfo = $base["vcinfo"] ?? [];
        $tpls = $base["tpls"] ?? [];
        foreach ($tpls as $tpl => $prop) {
            $vd = Arr::find($vcinfo, $prop);
            if (!is_string($vd)) continue;
            
            //替换
            $html = str_replace($tpl, $vd, $html);
        }

        return $html;
    }

    /**
     * 自动生成 SPA 环境 基础组件库插件|业务组件库插件 应用的 js 语句
     * !! 框架不会自动执行此方法，应由 各视图页面在各自内部的合适位置，调用此方法 生成并在调用位置 echo 
     *      js 代码块： import ... ; Vue.use(...); ...
     * @param Array $spaOptions 向 基础组件库 Vue.use() 的 js 方法语句 插入 第二参数 options {}
     * @param Bool $eko 是否执行 echo 默认 true
     * @return String html 代码
     */
    public function useSpaPlugin($spaOptions=[], $eko=true)
    {
        if ($this->spaEnabled() !== true) return "";

        //html rows
        $html = [];

        //手动插入 插入 module script
        //$html[] = "<script type=\"module\">";

        //SPA 基础组件库插件
        $spa = $this->spaBase;
        //变量
        $spav = $spa->desc["var"];
        $html[] = "import $spav from '".$spa->viewUrl("esm-browser.min.js", [], true)."';";

        //业务组件库 插件
        if (Is::nemarr($this->spaApp)) {
            foreach ($this->spaApp as $appk => $appres) {
                $appv = $appres->desc["var"];
                //!! 业务组件库 中所有组件都被定义为 异步组件形式
                $html[] = "import $appv from '".$appres->viewUrl("esm-browser-async.min.js", [], true)."';";
            }
        }

        //显示 import 基础组件库的 根组件 mixin
        $html[] = "import baseRootMixin from '".$spa->viewUrl($spa->desc["dirs"]["mixin"]."/base-root.js")."';";

        //准备基础组件库插件的 use options
        if (!Is::nemarr($spaOptions)) $spaOptions = [];
        //合并 $view->config->compound["spa"]["base"]["options"]
        $confOpts = $this->config->compound["spa"]["base"]["options"] ?? [];
        if (!Is::nemarr($confOpts)) $confOpts = [];
        //合并默认 Vue2.* 插件的 options
        $spaOptions = Arr::extend([
            //TODO: SPF-View 视图系统 使用 Vue2.* 插件时 默认的 插件启动参数
            //服务
            "service" => [
                //service.ui
                "ui" => [
                    //开启控制台输出，与 Env::$current->dev 模式关联
                    "log" => true,  //Env::$current->dev
                ],
            ],

        ], $confOpts, $spaOptions);
        //json
        $spaJson = Conv::a2j($spaOptions);
        $spaJson = str_replace("'", "\\'", $spaJson);
        //应用基础组件库插件
        $html[] = "const vcomOptions = {};";
        $html[] = "vcomOptions.base = JSON.parse('$spaJson');";
        //$html[] = "console.log(vcomOptions);";
        $html[] = "Vue.use($spav, vcomOptions.base);";

        //应用所有业务组件库插件
        if (Is::nemarr($this->spaApp)) {
            foreach ($this->spaApp as $appk => $appres) {
                $appv = $appres->desc["var"];
                $appOpts = $this->config->compound["spa"]["app"][$appk]["options"] ?? [];
                if (!Is::nemarr($appOpts)) $appOpts = [];
                if (Is::nemarr($appOpts)) {
                    $appJson = Conv::a2j($appOpts);
                    $appJson = str_replace("'","\\'",$appJson);
                } else {
                    $appJson = "{}";
                }
                $html[] = "vcomOptions.$appk = JSON.parse('$appJson');";
                //$html[] = "console.log(vcomOptions);";
                $html[] = "Vue.use($appv, vcomOptions.$appk);";
            }
        }
        //所有插件的 install options 保存到 window
        $html[] = "window.vcomOptions = vcomOptions;";
        //空行
        $html[] = "";

        //合并 html 并 echo
        $html = implode("\r", $html);
        if ($eko === true) echo $html;

        return $html;
    }



    /**
     * 工具方法
     */

    /**
     * 创建 暗黑模式自动切换的 links[] 参数
     * @param String $light css href
     * @param String $dark css href
     * @return Array links[] 参数
     */
    public function autoDarkCssLink($light, $dark)
    {
        $links = [];
        $llink = [
            "href" => $light,
            "media" => "(prefers-color-scheme: light), (prefers-color-scheme: no-preference)",
            "rel" => "stylesheet",
            "name" => "color-scheme-light",
            "light-href" => $light,
            "dark-href" => $dark,
        ];
        $dlink = [
            "href" => $dark,
            "media" => "(prefers-color-scheme: dark)",
            "rel" => "stylesheet",
            "name" => "color-scheme-dark",
            "light-href" => $light,
            "dark-href" => $dark,
        ];
        $links[] = Arr::extend($extra, $llink);
        $links[] = Arr::extend($extra, $dlink);
        return $links;
    }

    /**
     * 根据当前的 App 应用实例化情况，为 传入的路径 增加 appk 前缀
     * @param String $path 要处理的路径 如 /src/theme/spf
     * @return String 处理后的 url
     */
    public function apppre($path)
    {
        return App::path($path);
    }

    /**
     * 补全 依赖的资源实例化参数
     * @param Array $params 依赖资源的实例化参数
     * @return Array 补全后的 参数
     */
    public function fixResParams($params=[])
    {
        if (!Is::nemarr($params)) $params = [];

        //自动参数
        $ps = [
            "belongTo" => null,
            //创建视图依赖的资源实例时，默认不受 url 参数影响
            "ignoreGet" => true,
        ];
        //处理 忽略缓存的 config|url 参数
        if (Request::$current->gets->has("create")) {
            //如果在 url 中定义了 create
            $create = Request::$current->gets->create;
            if (!is_bool($create)) $create = false;
        } else {
            //检查 view->config->compound["create] 参数
            $create = $this->config->compound["create"] ?? false;
            if (!is_bool($create)) $create = false;
        }
        if ($create) $ps["create"] = true;

        //其他默认的实例化参数
        return Arr::extend($ps, $params);
    }

    /**
     * 判断当前视图是否启用了 SPF-Theme 主题
     * @return Bool
     */
    public function themeEnabled()
    {
        return $this->theme instanceof Resource;
    }



    /**
     * 静态方法
     */

    /**
     * 输出 page 页面
     * @param String $page 输出页面路径
     * @param Array $params 要传入页面内的 数据
     * @return String|null html
     */
    public static function page($page, $params=[])
    {
        //要使用的 视图文件 路径
        if (!Is::nemstr($page)) return null;
        $page = Path::find($page, Path::FIND_FILE);
        if (!file_exists($page)) return null;

        //视图的 实例化参数
        if (!Is::nemarr($params)) $params = [];
        //page 路径写入 params
        $params["page"] = $page;

        //提取出本次视图输出的 custom 参数，不作为 视图参数
        $cfger = new ViewConfig();
        $custom = [];
        foreach  ($params as $k => $v) {
            //如果定义了 默认值，表示此参数 不是 custom 参数
            if ($cfger->defInDft($k)) continue;
            $custom[$k] = $v;
            unset($params[$k]);
        }
        unset($cfger);

        //实例化视图类
        $view = View::current($params);

        //写入 custom 参数
        $view->custom = $custom;

        //调用 视图实例的 render 方法生成最终的 html
        $html = $view->render();
        
        //返回 html
        return $html;
    }

    /**
     * 输出 Spa 单页应用视图，使用 Vue2.x 组件框架
     * @param Array $params 要传入页面内的 参数数据，在 ViewConfig::$dftInit 参数基础上 extend
     *      !! 需要额外定义 $params["app"] 参数，指定此视图页面将要使用的 业务组件库 *.vcom.json 文件路径，可选形式：
     *          $params["app"] = "foo/bar/spa.vcom.json"
     *          $params["app"] = ["foo/bar/spa_1", "foo/bar/spa_2.vcom.json"]
     *          $params["app"] = [
     *              "spa_1" => "foo/bar/spa_1",
     *              "spa_2" => [
     *                  "file" => "foo/bar/spa_2",
     *                  "params" => [
     *                      ...
     *                  ]
     *              ],
     *          ]
     * @param String $page Vue2.x 视图页面，默认 spf/assets/view/spa_vue2x.php
     * @return String|null html
     */
     public static function SpaVue2x($params=[/*"app"=>"",*/], $page="spf/assets/view/spa_vue2x.php")
    {
        //视图页面
        if (!Is::nemstr($page)) $page = "spf/assets/view/spa_vue2x.php";

        //使用业务组件库 剔除无效的组件库
        $apps = $params["app"] ?? [];
        if (Is::nemarr($apps) && Is::indexed($apps)) $apps = array_merge([], $apps);
        if (isset($params["app"])) unset($params["app"]);
        $napps = [];
        if (Is::nemstr($apps)) {
            $napps = Vcom::fixVcomList($apps);
        } else if (Is::nemarr($apps)) {
            if (Is::indexed($apps)) {
                $napps = Vcom::fixVcomList(...$apps);
            } else if (Is::associate($apps)) {
                $napps = Vcom::fixVcomList($apps);
            }
        }
        //将筛选后的 业务组件库 添加到 $params["compound"]["spa"]["app"]
        if (Is::nemarr($napps)) {
            $params = Arr::extend($params, [
                "compound" => [
                    "spa" => [
                        "app" => $napps
                    ]
                ]
            ]);
        }

        //实例化 View 返回 html
        return static::page($page, $params);
    }


}