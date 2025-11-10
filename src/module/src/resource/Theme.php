<?php
/**
 * Compound 复合资源类 子类
 * Theme 资源类，定义 SPF-Theme 主题系统
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\SrcException;
use Spf\module\src\Mime;
use Spf\module\src\resource\theme\Module;
use Spf\Request;
use Spf\Response;
use Spf\View;
use Spf\App;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;
use Spf\util\Cls;

class Theme extends Compound 
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 在父类基础上扩展
     */
    public static $stdParams = [
        
        //定义输出主题的 模式，默认 light 可选 light|dark|... 在 $desc["content"][version][ext] 中定义的项目
        "mode" => "light",
        //可指定 要合并的 desc["styles"]["use"] 中定义的额外样式，默认 all 全部合并，指定 none 则全部不合并
        "use" => "all",     //foo_bar,jaz

        //可外部指定 主题样式类前缀，覆盖 desc["prefix"]
        "prefix" => "",

        /**
         * 手动传入要合并的 scss|css 文件路径|资源实例
         * !! 无法通过 uri 传递，只能在实例化时传入的 参数
         */
        "combine" => [],
        
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
            //创建并初始化主题子模块
            "InitThemeModules" => [],
            //根据请求参数 mode 设置所有子模块的输出模式 mode
            "SetModuleMode" => [],
            //根据请求的各子模块的 输出模式 mode 获取子模块最终输出的数据
            "GetModuleContext" => [],
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
        //SPF-Theme 主题名称 foo_bar 形式
        "name" => "",
        
        //主题的 JS 变量名，可通过 Var.color.danger.[m|l1|d2] 在 JS 中访问主题变量
        "var" => "",

        //此主题的 css 样式类名称前缀
        "prefix" => "",

        //主题内部 scss|css 中样式类前缀的字符串模板
        "pretemp" => "__PRE__",

        /**
         * 此主题支持的 子模块
         */
        //定义主题中 各子模块的 参数
        "modules" => [
            //!! 必选 颜色系统参数，在 resource\theme\module\Color::$stdDef 中定义其结构
            "color" => [],
            
            //!! 必选 尺寸系统参数，在 resource\theme\module\Size::$stdDef 中定义其结构
            "size" => [],
            
            //!! 必选 样式变量系统参数，在 resource\theme\module\Vars::$stdDef 中定义其结构
            "vars" => [],

            //其他子模块，必须定义子模块类 resource\theme\module\Foo
            //...
        ],

        /**
         * 要使用的主题样式文件
         */
        "styles" => [
            //基础的主题样式 scss 文件真实路径，不指定则使用 [theme-path]/base.scss 或 spf/assets/theme/base.scss
            "base" => "spf/assets/theme/base.scss",
            //依赖的外部样式文件，指定 url，使用 import 方式引入 通常用于引入第三方样式库
            "import" => [
                //默认引入 normalize
                "/src/cdn/normalize/default.css",
                //默认引入 animate
                "/src/cdn/animate/default.css",
                //可引入其他 cdn 资源
                //...
            ],
            //要额外合并的 其他本地样式文件 scss|css
            "use" => [
                //必须指定本地真实存在的 scss|css 文件 真实路径
                /*
                "foo_bar" => "src/css/foo_bar.scss"
                */
            ],

        ],



        //是否启用版本控制
        "enableVersion" => true,
        //指定可以通过 @|latest 访问的 版本号
        "version" => [
            //主题系统默认版本号
            "@" => "1.0.0",
            "latest" => "1.0.0",
        ],

        //允许输出的 ext 类型数组，必须是 Mime 类中支持的后缀名类型
        "ext" => ["css","scss","js"],

        /**
         * 复合资源的根路径
         *  0   针对本地的复合资源 Theme | Icon | Vcom | Lib 等类型：
         *      此参数表示 此复合资源的本地保存路径，不指定则使用当前 *.ext.json 文件的 同级同名文件夹
         *  1   针对远程复合资源 Cdn 类型：
         *      此参数表示 cdn 资源的 url 前缀，不带版本号
         * !! 指定一个 本地库文件 *.theme.json 路径（完整路径），如果不指定，则使用当前 json 文件的路径
         */
        "root" => "",

        /**
         * 主题系统 根据默认版本，指定子资源
         * 可手动覆盖
         */
        "content" => [
            "1.0.0" => [
                "css" => [
                    "default" => [
                        //fix 输出前方法
                        "fix" => [
                            //输出前 替换 主题样式类名前缀
                            "ThemePrefix",
                        ]
                    ]
                ],
                "scss" => [
                    "default" => [
                        //fix 输出前方法
                        "fix" => [
                            //输出前 替换 主题样式类名前缀
                            "ThemePrefix",
                        ]
                    ]
                ],
                "js" => [
                    "default" => []
                ]
            ]
        ],

    ];

    /**
     * 定义复合资源 内部子资源的 描述参数
     * !! 在父类基础上扩展
     */
    public static $stdSubResource = [
        //子资源类型，可以是 static | dynamic   表示   静态的实际存在的 | 动态生成的   子资源内容
        //!! SPF-Theme 子资源默认为 dynamic 类型
        "type" => "dynamic",
    ];

    //主题子模块实例
    public $modules = [
        //必选的子模块
        "color" => null,
        "size" => null,
        "vars" => null,

        //可选的 自定义主题子模块
        //...
    ];

    //根据请求参数，获取每个子模块对应的输出模式 mode 可以有多个
    public $themeMode = [
        //必选的子模块
        "color" => [],
        "size" => [],
        "vars" => [],
        
        //可选的 自定义主题子模块
        //...
    ];

    //要输出的主题子模块最终数据 来自 $modules[module]->getItemsByMode() 方法
    public $themeCtx = [
        //必选子模块
        "color" => [],
        "size" => [],
        "vars" => [],
        
        //可选的 自定义主题子模块
        //...
    ];


    
    /**
     * 资源实例内部定义的 stage 处理方法
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
            $this->desc["prefix"] = $pre;
        }

        return true;
    }
    //InitThemeModules 创建并初始化主题中使用的子模块，实例保存到 $theme->module []
    public function stageInitThemeModules($params=[])
    {
        //主题 desc 中定义的 子模块
        $modules = $this->desc["modules"] ?? [];
        //color,size,vars 子模块必须定义
        if (
            !isset($modules["color"]) ||
            !isset($modules["size"]) ||
            !isset($modules["vars"])
        ) {
            //报错
            throw new SrcException("此主题资源 ".$this->resBaseName()." 缺少必要的主题子模块", "resource/getcontent");
        }

        //是否启用缓存
        $cacheEnabled = $this->cacheEnabled();
        //是否忽略缓存
        $cacheIgnored = $this->cacheIgnored();

        //依次实例化子模块
        foreach ($modules as $modk => $modc) {
            $modclsn = Str::camel($modk, true);
            $modcls = Cls::find("module/src/resource/theme/module/$modclsn");
            if (!class_exists($modcls)) {
                //不存在 此主题模块解析类
                throw new SrcException("未找到主题模块 $modclsn 的解析类", "resource/getcontent");
            }
            //实例保存到 $this->modules []
            $this->modules[$modk] = new $modcls($modc, $this);

            //子模块缓存
            if ($cacheEnabled && !$cacheIgnored) {
                //读取 子模块解析结果 缓存
                $ctx = $this->cacheGetModuleContext($modk);
                if (Is::nemarr($ctx)) {
                    //缓存读取成功，将缓存的解析结果直接写入子模块实例的 context
                    $this->modules[$modk]->ctx($ctx);
                    continue;
                }
            }

            //子模块初始化
            $this->modules[$modk]->parse();
            //缓存解析结果
            if ($cacheEnabled) {
                $this->cacheSaveModuleContext($modk);
            }

        }

        return true;
    }
    //SetModuleMode 根据请求参数，指定每个子模块的输出模式 mode
    public function stageSetModuleMode($params=[])
    {
        //请求的 主题模式 $params["mode"]
        $pmode = $this->params["mode"];
        //可以传入多个主题模式
        if (Is::nemstr($pmode)) $pmode = Arr::mk($pmode);
        //子模块
        $mods = $this->modules;
        foreach ($this->modules as $modk => $modi) {
            //从请求的输出模式中，找出当前子模块支持的输出模式
            $smodes = array_filter($pmode, function($pmodei) use (&$modi) {
                return $modi->supportMode($pmodei);
            });
            //如果传入的 输出模式都不被当前子模块支持，则使用子模块的默认模式
            if (!Is::nemarr($smodes)) {
                //写入 $this->themeMode []
                $this->themeMode[$modk] = [];
                $this->themeMode[$modk][] = $modi->getDftMode();
                continue;
            }

            //将请求的输出模式 写入 $this->themeMode []
            $this->themeMode[$modk] = $smodes;
        }

        return true;
    }
    //GetModuleContext 根据请求的各子模块的 输出模式 mode 获取子模块最终输出的数据
    public function stageGetModuleContext($params=[])
    {
        //已处理过的 子模块输出模式
        $modes = $this->themeMode;
        //依次调用各子模块的 输出方法
        foreach ($this->modules as $modk => $modi) {
            $modesi = $modes[$modk] ?? [];
            //未指定输出模式的 子模块 不参与输出
            if (!Is::nemarr($modesi)) continue;
            //子模块还未解析，则先解析
            if ($modi->parsed !== true) $modi->parse();
            //获取子模块输出的 主题数据 保存到 $this->themeCtx 
            $this->themeCtx[$modk] = $modi->getItemByMode(...$modesi);
        }
        return true;
    }



    /**
     * 工具方法 解析复合资源内部 子资源参数，查找|创建 子资源内容
     * 根据  子资源类型|子资源ext|子资源文件名  分别制定对应的解析方法
     * !! 覆盖父类
     * @return $this
     */
    //生成默认的 scss 文件
    protected function createDynamicScssDefaultSubResource()
    {
        //从 desc 中或样式参数
        $sty = $this->desc["styles"] ?? [];

        //临时 scss 内容行
        $rows = [];

        //引用的外部样式
        $import = $sty["import"] ?? [];
        foreach ($import as $url) {
            $rows[] = "@import \"".$url."\";";
        }
        if (Is::nemarr($rows)) {
            $rows = array_merge($rows, ["", "", ""]);
        }

        /**
         * 依次输出 各主题子模块 内容行
         */
        //子模块实例
        $modules = $this->modules;
        //已处理过的 子模块输出数据
        $ctx = $this->themeCtx;
        //依次执行子模块输出
        foreach ($ctx as $modk => $mctx) {
            //注释
            $rows = array_merge($rows, [
                "/**",
                " * 主题模块 $modk 生成语句",
                " * 不要手动修改",
                " */",
                "",
            ]);
            $mrows = $modules[$modk]->createContentRows($mctx, "scss");
            if (Is::nemarr($mrows) && Is::indexed($mrows)) {
                $rows = array_merge($rows, $mrows);
            } else {
                $rows = array_merge($rows, [
                    "/** !! 主题模块 $modk 生成语句错误 !! "."**/"
                ]);
            }
            $rows[] = "";
        }

        //临时 scss 内容 content
        $scsscnt = implode("\n", $rows);
        //var_dump($scsscnt);exit;

        /**
         * 要合并的 scss|css 本地资源
         */
        //merge 参数
        $merge = [];
        //主题基础样式
        $base = $sty["base"] ?? null;
        if (!Is::nemstr($base)) $base = "spf/assets/theme/base.scss";
        $basef = Path::find($base, Path::FIND_FILE);
        if (!file_exists($basef)) $basef = Path::find("spf/assets/theme/base.scss", Path::FIND_FILE);
        //创建基础样式的资源实例
        $base = Resource::create($basef, [
            "belongTo" => null,
            "ignoreGet" => true,
            //base 样式不处理 import
            "import" => false,
            "export" => "scss",
        ]);
        //合并基础样式
        if ($base instanceof Codex) $merge[] = $base;

        //use 合并额外的 本地样式资源
        $uses = $sty["use"] ?? [];
        $ups = $this->params["use"] ?? null;
        if (Is::nemstr($ups)) {
            if ($ups !== "all" && $ups !== "none") {
                $ups = Arr::mk($ups);
            }
        }
        if ($ups === "all") {
            $us = array_values($uses);
        } else if ($ups === "none") {
            $us = [];
        } else if (Is::nemarr($ups) && Is::indexed($ups)) {
            $us = [];
            foreach ($ups as $upi) {
                if (isset($uses[$upi]) && Is::nemstr($uses[$upi])) {
                    $us[] = $uses[$upi];
                }
            }
        } else {
            $us = [];
        }
        if (Is::nemarr($us)) {
            foreach ($us as $ufp) {
                $uf = Path::find($ufp, Path::FIND_FILE);
                if (!file_exists($uf)) continue;
                //合并本地额外的 scss|css 资源
                $uext = Resource::getExtFromPath($uf);
                $ures = Resource::create($uf, [
                    "belongTo" => null,
                    "ignoreGet" => true,
                    //额外样式不处理 import
                    "import" => false,
                    //原样输出
                    "export" => $uext,
                ]);
                if (!$ures instanceof Codex) continue;
                $merge[] = $ures;
            }
        }

        /**
         * 手动合并 params["combine"] 传入的 scss|css 资源实例|路径
         */
        $mgs = $this->params["combine"] ?? [];
        if (Is::nemstr($mgs)) $mgs = explode(",", $mgs);    //Arr::mk($mgs);
        if (Is::nemarr($mgs) && Is::indexed($mgs)) {
            foreach ($mgs as $mgi) {
                //直接传入了 资源实例
                if ($mgi instanceof Codex) {
                    $merge[] = $mgi;
                    continue;
                }
                
                /**
                 * 传入了资源路径
                 * !! 可以是 本地文件|remote 远程资源
                 */
                if (Is::nemstr($mgi)) {
                    //如果是远程资源
                    if (Url::isShortUrl($mgi) === true || Url::isUrl($mgi) === true) {
                        //将传入的 url 补全
                        if (Url::isShortUrl($mgi) === true) $mgi = Url::fixShortUrl($mgi);
                        //附加 App 应用信息 得到最终资源路径
                        $mgi = App::url($mgi);
                        //创建临时资源实例
                        $mgext = Resource::getExtFromPath($mgi);
                        $mgres = Resource::create($mgi, $this->fixSubResParams([
                            "belongTo" => null,
                            "ignoreGet" => true,
                            //额外样式不处理 import
                            "import" => false,
                            //原样输出
                            "export" => $mgext,
                        ]));
                        if (!$mgres instanceof Codex) continue;
                        //获取资源的 content
                        $mgcnt = $mgres->export([
                            "return" => true,
                        ]);
                        //释放
                        unset($mgres);
                        //使用资源 content 创建临时资源
                        $mgres = Resource::manual(
                            $mgcnt,
                            "temp.".$mgext,
                            [
                                "ext" => $mgext,
                                "belongTo" => null,
                                "ignoreGet" => true,
                                //额外样式不处理 import
                                "import" => false,
                                //原样输出
                                "export" => $mgext,
                            ]
                        );
                        if (!$mgres instanceof Codex) continue;
                        //附加到 merge 数组
                        $merge[] = $mgres;
                        continue;
                    }
                    
                    //传入了本地文件资源路径
                    $mgf = Path::find($mgi, Path::FIND_FILE);
                    if (!Is::nemstr($mgf)) continue;
                    //创建临时资源实例
                    $mgext = Resource::getExtFromPath($mgf);
                    $mgres = Resource::create($mgf, [
                        "belongTo" => null,
                        "ignoreGet" => true,
                        //额外样式不处理 import
                        "import" => false,
                        //原样输出
                        "export" => $mgext,
                    ]);
                    if (!$mgres instanceof Codex) continue;
                    //附加到 merge 数组
                    $merge[] = $mgres;
                }
            }
        }

        //创建临时资源
        $scss = Resource::manual(
            $scsscnt,
            "SPF_Theme_".$this->resName()."_temp.scss",
            [
                "ext" => "scss",
                "export" => "scss",
                "belongTo" => $this,
                "ignoreGet" => true,
                //处理 import
                "import" => true,
                //合并本地资源
                "merge" => $merge,
            ]
        );

        //临时资源保存到 subResource
        $this->subResource = $scss;
        return $this;
    }
    //生成默认的 css 文件
    protected function createDynamicCssDefaultSubResource()
    {
        //调用 scss 创建方法
        $this->createDynamicScssDefaultSubResource();
        //scss 资源实例
        $scss = $this->subResource;
        //调用其 css 输出方方法 得到 css 文件内容
        $csscnt = $scss->export([
            "return" => true,
            "export" => "css"
        ]);
        //使用此 css 内容，创建临时资源
        $css = Resource::manual(
            $csscnt,
            "SPF_Theme_".$this->resName()."_temp.css",
            [
                "ext" => "css",
                "export" => "css",
                "belongTo" => $this,
                "ignoreGet" => true,
                //import 保持原样
                "import" => "keep",
            ]
        );
        //更新 subResource
        $this->subResource = $css;
        return $this;
    }
    //生成默认的 js 文件 将主题样式参数输出到前端 js
    protected function createDynamicJsDefaultSubResource()
    {
        //主题参数
        $ctx = $this->themeCtx;
        //json
        $json = Conv::a2j($ctx);
        //创建临时 js
        $js = $this->tempCodex("js", [
            "import" => "keep",
        ]);
        //开始写入 js 行数组
        $rower = $js->RowProcessor;
        $rower->rowAdd("const cssvar = JSON.parse('".$json."');","");
        $rower->rowAdd("export default cssvar;","");
        //生成内容行
        $js->content = $rower->rowCombine();

        //保存为 subResource
        $this->subResource = $js;
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

        //文件名
        $cfn = [];

        //请求的子资源 描述参数
        $opts = $this->subResourceOpts;
        //子资源类型 static|dynamic
        $stp = $opts["type"];
        $cfn[] = $stp;
        
        /**
         * 缓存文件名 需要拼接所有子模块的 mode 输出模式名
         * 例如：
         *  $this->themeMode = [
         *      "color" => ["light"],
         *      "size" => ["mobile"],
         *      "vars" => ["default"]
         *  ]
         * 则 缓存文件名为：dynamic-light-mobile-default-default.css
         */
        foreach ($this->themeMode as $modk => $modes) {
            if (Is::nemarr($modes)) {
                $cfn = array_merge($cfn, $modes);
            }
        }

        /**
         * 将 params["use"] 参数也写入缓存文件名
         * 因为附加不同的 额外资源，导致输出内容不一致
         */
        $ups = $this->params["use"] ?? [];
        if (Is::nemarr($ups)) $ups = implode("-", $ups);
        if (Is::nemstr($ups)) $cfn[] = $ups;

        //拼接请求的 子资源文件名，不含 export-ext
        $cfn[] = $this->subResourceName;

        //如果名称数组为空
        if (!Is::nemarr($cfn)) return null;

        //拼接文件名，MD5
        //$cfn = md5(implode("-", $cfn));
        $cfn = implode("-", $cfn);

        //输出完整的 缓存文件名
        return $cfn.".".$this->ext;
    }

    /**
     * 读取主题子模块解析结果缓存
     * @param String $modk 子模块 key，不指定则读取 $this->modules 数组中的所有子模块的 缓存
     * @return Array|null 缓存的子模块解析结果
     */
    public function cacheGetModuleContext($modk=null)
    {
        //不指定具体的 模块 key 则读取全部
        if (!Is::nemstr($modk)) {
            $cache = [];
            foreach ($this->modules as $mod => $modins) {
                $cache[$mod] = $this->cacheGetModuleContext($mod);
            }
            return $cache;
        }

        //必须启用 cache
        if ($this->cacheEnabled() !== true) return null;
        //指定的子模块 必须存在
        if (!isset($this->modules[$modk])) return null;
        //缓存文件
        $cp = $this->cachePath("module/$modk.json", true);
        if (!Is::nemstr($cp)) return null;
        //读取
        $ctx = Conv::j2a(file_get_contents($cp));
        if (Is::nemarr($ctx)) return $ctx;
        return null;
    }

    /**
     * 写入主题子模块解析结果缓存
     * @param String $modk 子模块 key，不指定则将 $this->modules 数组中的所有子模块的解析结果 写入缓存
     * @return Bool
     */
    public function cacheSaveModuleContext($modk=null)
    {
        //不指定具体模块，则写入全部
        if (!Is::nemstr($modk)) {
            $rst = true;
            foreach ($this->modules as $mod => $modins) {
                $rst = $rst && $this->cacheSaveModuleContext($mod);
            }
            return $rst;
        }

        //必须启用 cache
        if ($this->cacheEnabled() !== true) return false;
        //指定的子模块 必须存在
        if (!isset($this->modules[$modk])) return false;
        //子模块实例
        $modi = $this->modules[$modk];
        if (!$modi instanceof Module || $modi->parsed !== true) return false;
        //准备要写入缓存的 数据
        $ctx = $modi->ctx();
        if (!Is::nemarr($ctx)) $ctx = [];
        //缓存文件，不检查是否存在
        $cp = $this->cachePath("module/$modk.json", false);
        //写入缓存文件
        return Path::mkfile($cp, Conv::a2j($ctx));
    }



    /**
     * 工具方法
     */

    /**
     * 判断此主题是否支持 dark mode
     * @return Bool
     */
    public function supportDarkMode()
    {
        //color 主题模块
        $color = $this->modules["color"];
        //color 模块支持的 mode
        $cmode = $color->supportedModes();
        //模式是否包含 light|dark
        $diff = array_diff(["light","dark"], $cmode);
        return empty($diff);
    }

    /**
     * 如果此主题支持 dark 模式，则分别生成当前请求参数下 light|dark 的 params["mode"] 字符串
     * 即：将 light,mobile,default  切换为  dark,mobile,default
     * @return Array [ "light" => "light,mobile,default", "dark" => "dark,mobile,default" ]
     */
    public function colorModeShift()
    {
        //当前请求的 mode
        $pmode = $this->params["mode"] ?? "light";
        if (Is::nemstr($pmode)) $pmode = explode(",", $pmode);
        if (!Is::nemarr($pmode)) $pmode = ["light"];
        //去除
        $diff = array_diff($pmode, ["light","dark"]);
        //合并
        $larr = array_merge(["light"], $diff);
        $darr = array_merge(["dark"], $diff);
        return [
            "light" => implode(",", $larr),
            "dark" => implode(",", $darr),
        ];
    }
    
    /**
     * 创建内部 临时 Codex 资源实例，通常用于生成内容行数组
     * @param String $ext 资源后缀名
     * @param Array $params 需要额外指定的 临时资源实例化参数
     * @return Codex 资源实例
     */
    public function tempCodex($ext, $params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        $params = Arr::extend([
            "ext" => $ext,
            "belongTo" => $this,
            "ignoreGet" => false,
            "import" => true,
            "export" => $ext,
        ], $params);

        return Resource::manual(
            "",
            "SPF_Theme_".$this->resName()."_temp.".$ext,
            $params
        );
    }

    /**
     * 替换主题 scss|css 文件内容中的 字符串模板 
     * @param String $cnt 要替换的文件内容
     * @return String 替换后的内容
     */
    public function replacePreTemp($cnt)
    {
        if (!Is::nemstr($cnt)) return $cnt;

        $desc = $this->desc;
        //desc 中定义的 字符串模板
        $tpl = $desc["pretemp"];
        if (substr($tpl, -1) === "-") $tpl = substr($tpl, 0, -1);
        //desc 中定义的 主题样式类前缀
        $pre = $desc["prefix"];
        if (substr($pre, -1) === "-") $pre = substr($pre, 0, -1);

        //依次替换
        $cnt = str_replace(".$tpl-", ".$pre-", $cnt);
        $cnt = str_replace("$tpl-", "$pre-", $cnt);
        $cnt = str_replace(".$tpl", ".$pre", $cnt);
        $cnt = str_replace($tpl, $pre, $cnt);

        return $cnt;
    }



    /**
     * fix 方法
     */

    /**
     * 在输出 css 之前，替换可能存在的 主题类名前缀
     * @return $this
     */
    protected function fixThemePrefixBeforeExport()
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



}