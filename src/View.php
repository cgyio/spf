<?php
/**
 * 框架核心类
 * View 视图类，基类
 */

namespace Spf;

use Spf\exception\BaseException;
use Spf\exception\CoreException;
use Spf\config\ViewConfig;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Conv;
use Spf\util\Path;

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
     * 此 View 视图类自有的 init 方法，执行以下操作：
     *  0   缓存 page 视图页面
     *  //1   生成 html head 页面头部
     * !! Core 子类必须实现的，View 子类不要覆盖
     * @return $this
     */
    final public function initialize()
    {
        // 0 缓存 page 视图页面
        $this->page = Path::find($this->config->page);

        // 1 生成 html head 页面头部
        //!! 此步骤转移到 render 方法中，在输出之前才生成 html，而不是实例化后立即渲染
        /*$this->content = [];
        //必须严格按顺序执行
        $meta = $this->parseMeta();
        $theme = $this->parseTheme();
        $iconset = $this->parseIconset();
        if (!($meta && $theme && $iconset)) {
            //视图 html head 生成错误
            throw new CoreException("视图页面头部生成错误", "initialize/init");
        }
        //结束 head
        $this->html("</head>");*/

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
        $this->parseTheme();
        $this->parseIconset();
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
                "id" => "favicon",
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
    // 1 解析 theme|css 参数
    protected function parseTheme()
    {
        $cfger = $this->config;

        //要生成 html 的 css link 列表
        $clnk = [];

        //主题参数
        $theme = $cfger->theme;
        $enable = $theme["enable"] ?? true;
        $thn = $theme["name"] ?? null;
        $darkmd = $theme["darkmode"] ?? true;
        $tmod = $theme["mode"] ?? [];
        $uses = $theme["use"] ?? "all";
        //如果启用了 SPF-Theme 主题
        if ($enable === true && Is::nemstr($thn)) {
            //主题 css 文件路径 默认加载 min 形式
            $turl = $this->apppre("/src/theme/$thn.min.css?use=$uses");
            if ($darkmd === true) {
                //如果启用的 暗黑模式
                $lmd = $tmod["light"] ?? "light";
                $dmd = $tmod["dark"] ?? "dark";
                //分别引入 light|dark css
                $clnk[] = [
                    "href" => "$turl&mode=$lmd",
                    "media" => "(prefers-color-scheme: light), (prefers-color-scheme: no-preference)",
                ];
                $clnk[] = [
                    "href" => "$turl&mode=$dmd",
                    "media" => "(prefers-color-scheme: dark)",
                ];
            } else {
                //未启用 暗黑模式，直接引入 light 模式的 css
                $clnk[] = [
                    "href" => $turl,
                ];
            }
        }

        //css 列表
        $css = $cfger->css;
        if (Is::nemarr($css) && Is::indexed($css)) {
            foreach ($css as $csi) {
                if (!Is::nemstr($csi)) continue;
                $clnk[] = [
                    "href" => $this->apppre($csi)
                ];
            }
        }

        //生成 css link html
        $clnk = array_map(function($lnk) {
            $lnk["rel"] = "stylesheet";
            return $lnk;
        }, $clnk);
        $this->link($clnk);

        return true;
    }
    // 2 解析 iconset 图标库参数
    protected function parseIconset()
    {
        $ics = $this->config->iconset;
        //未指定要使用的 图标库，则使用默认 spf 图标库
        if (!Is::nemarr($ics) || !Is::indexed($ics)) $ics = ["spf"];

        //要使用 css link
        $css = [];
        //要使用 js 雪碧图生成代码
        $js = [];
        foreach ($ics as $icn) {
            $css[] = [
                "rel" => "stylesheet",
                "href" => "/src/icon/$icn.min.css",
            ];
            $js[] = "/src/icon/$icn.min.js";
        }

        //生成 html
        $this->link($css);
        $this->script(...$js);

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
     * 向 内容行数组 插入 <script src="..."></script>
     * @param Array $srcs js src
     * @return $this
     */
    public function script(...$srcs)
    {
        $srcs = array_filter($srcs, function($src) {
            return Is::nemstr($src);
        });
        $s = [];
        foreach ($srcs as $src) {
            $s[] = "<script src=\"$src\"></script>";
        }
        return $this->html(...$s);
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


}