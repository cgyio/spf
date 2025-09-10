<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Theme 子类
 * 继承自 Plain 纯文本类型基类，处理 *.theme 类型本地文件
 * 
 * 定义 Spf 框架视图 主题的 文件格式|解析方式|输出方式 的规则：
 *   0  ***.theme 文件内容是 包含主题相关参数的 json 数据，只能是 !! 本地文件
 *   1  传入参数 ***.theme?mode=[light|mobile,dark] 输出对应的 css 文件 可以指定输出多个 mode 英文逗号隔开，后面的覆盖前面的
 *   2  传入参数 ***.theme?editor=yes 可进入 主题编辑器页面
 */

namespace Spf\module\src\resource;

use Spf\Response;
use Spf\module\src\Resource;
use Spf\module\src\resource\theme\ThemeModule;
use Spf\module\src\Mime;
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Conv;
use Spf\util\Path;
use Spf\util\Color;

class Theme extends Plain
{
    /**
     * 当前的资源类型的本地文件，是否应保存在 特定路径下
     * 指定的 特定路径 必须在 Src::$current->config->resource["access"] 中定义的 允许访问的文件夹下
     *  null        表示不需要保存在 特定路径下，本地资源应在 [允许访问的文件夹]/... 路径下
     *  "ext"       表示应保存在   [允许访问的文件夹]/[资源后缀名]/... 路径下
     *  "foo/bar"   指定了 特定路径 foo/bar 表示此类型本地资源文件应保存在   [允许访问的文件夹]/foo/bar/... 路径下
     * 默认 null 不指定 特定路径
     * !! 覆盖父类
     * !! *.theme 主题文件 必须保存在 [允许访问的文件夹]/theme/... 路径下
     */
    public static $filePath = "ext";    //可选 null | ext | 其他任意路径形式字符串 foo/bar 首尾不应有 /



    /**
     * 主题参数 元数据 标准数据结构
     */
    //标准 主题参数 数据格式
    protected static $stdMeta = [
        //主题名称 foo.theme --> foo
        "name" => "",
        //主题版本
        "version" => "",
        //主题说明
        "desc" => "SPF-Theme 主题",

        //主题使用的 图标包 
        "iconset" => [],

        //主题模式 定义 通过 foo.theme?mode=light|dark|mobile... 指定输出 css 内容
        "mode" => [
            "light", "dark", "mobile", //"wxmp", ...
        ],

        //定义此主题 use 的通用 SCSS 文件，common 为必须使用的
        "use" => [
            //必须引用的 SCSS 通用模块 默认使用定义在框架内部的 spf/assets/theme/common.scss
            "common" => "spf/assets/theme/common.scss",
            //可 额外定义 需要 use 的 SCSS 文件路径
            //...
        ],
    ];

    /**
     * 定义 主题中包含的 可用的主题模块
     */
    protected static $themeModules = [
        //这些模块 必须定义了对应的 类 ThemeFoobarModule
        "color", "size", "vars",
    ];

    /**
     * 定义 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    protected static $stdParams = [
        //是否 强制不使用 缓存的 css 文件
        "create" => false,
        //输出文件的 类型 css|scss|js 输出的同时，会生成 缓存文件
        "export" => "css",
        //输出主题的 mode 模式，可有多个，英文逗号隔开，后面的覆盖前面的
        "mode" => "light",  //mobile,dark
        //合并主题文件路径下的 其他 scss 文件，默认 all 表示使用所有定义在 meta["use"] 中的 SCSS 文件
        "use" => "all",        //foo,bar  -->  需要合并 meta["use"]["foo"] meta["use"]["bar"]
    ];

    /**
     * 定义支持的 export 类型，必须定义相关的 createFooContent() 方法
     * 必须是 Mime 支持的 文件后缀名
     * !! 覆盖父类
     */
    protected static $exps = [
        "js", "css", "scss",
    ];
    
    /**
     * 此类型纯文本资源的 注释符 [ 开始符号, 每行注释的开头符号, 结尾符号 ]
     * !! 覆盖父类
     */
    public $cm = ["/**", " * ", " */"];

    /**
     * 解析 theme 主题文件 得到的 主题数据
     */
    //主题元数据
    protected $meta = [];
    //各主题模块的 实例
    protected $modules = [
        /*
        "color" => ThemeColorModule 实例,
        ...
        */
    ];

    //当前输出的 mode 模式的 主题参数 context，依据此生成对应的 js|css|scss 文件内容
    public $context = [
        /*
        # 某个 主题模块 的数据
        "module_name" => [
            # 从模块下的 某个 参数 item
            "item_name" => [
                ... 参数值 ...
            ],
            ...
        ],

        "color" => [
            "red" => [
                "m" => "#ff0000",
                "d2" => "",
                "d1" => "",
                ...
            ],
        ],
        ...
        */
    ];

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

        //文件内容是 主题参数 json 数据，转为 Array
        $ctx = Conv::j2a($this->content);

        //提取主题元数据
        $stdMeta = static::$stdMeta;
        $meta = [];
        foreach ($stdMeta as $key => $val) {
            if (!isset($ctx[$key])) continue;
            $meta[$key] = $ctx[$key];
        }
        //使用标准数据结构 填充
        $meta = Arr::extend(static::$stdMeta, $meta);
        //保存到 meta
        $this->meta = $meta;

        //依次提取主题中包含的 主题模块，并实例化
        $mods = static::$themeModules;
        foreach ($mods as $modk) {
            $modc = $ctx[$modk] ?? [];
            $modclsn = "Theme".Str::camel($modk, true)."Module";
            $modcls = Cls::find("module/src/resource/theme/$modclsn", "Spf\\");
            if (!class_exists($modcls)) {
                //不存在 此主题模块解析类
                throw new SrcException("未找到主题模块 $modclsn 的解析类", "resource/getcontent");
            }
            $modins = new $modcls($modc, $this);
            $this->modules[$modk] = $modins;
        }
        
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

        //读取缓存文件
        $cfp = $this->cacheFile();
        if ($cfp !== false) {
            //存在缓存文件 且 允许读取缓存 则 读取缓存
            $this->content = file_get_contents($cfp);
        } else {
            //缓存文件不存在，或 开启了 强制忽略缓存，开始解析主题文件，获取目标内容

            //请求的 mode 模式
            $modes = $this->exportModes();

            //依次调用 主题模块的 parse 方法
            $mods = $this->modules;
            //解析得到的 context
            $ctx = [];
            foreach ($mods as $modk => $modins) {
                //调用 各主题模块的 解析方法
                $modins->parse();
                $ctx[$modk] = $modins->getItemByMode(...$modes);
            }
            //缓存 context
            $this->context = $ctx;

            //根据 export 类型 调用对应的 createExtContent 方法
            $ext = $this->params["export"] ?? $this->ext;
            $m = "create".Str::camel($ext, true)."Content";
            if (!method_exists($this, $m)) {
                //对应 方法不存在，则 不做处理，表示直接使用 当前 content
                //通常针对于 输出此文件实例的 实际文件类型
    
            } else {
                //调用方法
                $this->$m();
            }

            //调用 对应 ext 的 ThemeExporter
            /*$ext = $this->exportExt();
            $extclsn = "Theme".Str::camel($ext, true)."Exporter";
            $extcls = Cls::find("module/src/resource/theme/$extclsn", "Spf\\");
            if (!class_exists($extcls)) {
                //主题资源输出类 不存在
                throw new SrcException("$ext 类型主题资源输出类不存在", "resource/export");
            }
            $exporter = new $extcls($this, $ctx);
            //生成 content
            $this->content = $exporter->createContent();*/

            //写入缓存文件
            $this->saveToCacheFile();

        }

        //minify
        if ($this->isMin() === true) {
            //压缩 JS/CSS 文本
            $this->content = $this->minify();
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
        //meta
        $meta = $this->meta;
        $thn = $meta["name"];

        //context
        $ctx = $this->context;
        if (!Is::nemarr($ctx)) return $this;

        //先清空 rows 内容行数据缓存
        $this->rows = [];

        /**
         * 创建此主题的 CSS 文件内容
         * 
         *  0   生成 CSS 文件头部
         *  1   生成此主题的 CSS 变量语句
         *  2   调用 createScssContent 生成此主题的 SCSS 语句
         *  3   调用 Scss::parseScss 方法 编译得到的 SCSS 内容 生成 CSS 语句
         *  4   合并 CSS 变量语句 和 SCSS 解析得到的 CSS 语句，生成新最终的 content
         */

        // 0  生成 CSS 文件头部
        //charset
        $charset = "@charset \"UTF-8\";";
        $this->rowAdd($charset, "");
        $this->rowEmpty(1);
        $this->rowComment(
            "SPF-Theme 主题 $thn",
            "$thn.css 主要样式",
            "!! 不要手动修改 !!"
        );
        
        // 1 生成此主题的 CSS 变量语句
        $this->rowCssVars();

        //缓存 生成的 rows
        $rows = array_merge($this->rows);

        // 2 生成此主题的 SCSS 语句
        $this->createScssContent();
        $cnt = $this->content;

        // 3 调用 Scss::parseScss 方法 编译得到的 SCSS 内容 生成最终的 CSS 文件内容
        //编译，默认不压缩，通过 theme 自身的 min 参数决定是否输出压缩文件
        $cnt = Scss::parseScss($cnt, false);
        //去掉自动生成的 charset
        $cnt = str_replace($charset, "", $cnt);

        // 4 合并 rows 和 cnt
        $rn = $this->rn;
        $cnt = implode($rn, $rows) .$rn. $cnt;

        //创建 CSS 文件资源实例，通过 export 获取最终的 content
        $res = $this->createSubRes($cnt, "css");
        if (empty($res)) return $this;
        //生成 content
        $this->content = $res->export([
            "return" => true
        ]);

        return $this;
    }
    //生成 SCSS content
    protected function createScssContent()
    {
        //meta
        $meta = $this->meta;
        $thn = $meta["name"];

        //先清空 rows 内容行数据缓存
        $this->rows = [];
        
        /**
         * 创建此主题的 SCSS 文件内容
         * 
         *  0   生成 SCSS 文件头部
         *  1   解析 $this->context 生成 各种 SCSS 变量
         *  2   合并 主题必须的 common.scss 文件 和 通过 use=foo,bar 指定的 其他同路径下的 scss 文件
         */

        // 0  生成 SCSS 文件头部
        $this->rowComment(
            "SPF-Theme 主题 $thn",
            "$thn.scss 变量以及样式定义",
            "!! 不要手动修改 !!"
        );

        // 1 解析 $this->context 生成 各种 SCSS 变量
        $this->rowScssVars();

        // 2 合并 主题必须的 common.scss 文件 和 通过 use=foo,bar 指定的 其他同路径下的 scss 文件
        $this->rowUseFiles();

        //合并 rows 得到 scss 文件内容 清空 rows 数组
        $cnt = $this->rowCnt(true);

        //创建 SCSS 文件资源实例，通过 export 获取最终的 content
        $res = $this->createSubRes($cnt, "scss");
        if (empty($res)) return $this;
        //生成 content
        $this->content = $res->export([
            "return" => true
        ]);

        return $this;
    }
    //生成 JS content
    protected function createJsContent()
    {
        //meta
        $meta = $this->meta;
        $thn = $meta["name"];

        //context
        $ctx = $this->context;
        if (!Is::nemarr($ctx)) return $this;
        $ctxjson = Conv::a2j($ctx);

        //先清空 rows 内容行数据缓存
        $this->rows = [];
        
        /**
         * 创建此主题的 CSS 文件内容
         * 
         *  0   生成 JS 文件头部
         *  1   将 context 内容 直接输出为 Js 语句
         *  2   为 JS 语句 增加 ES6 export 支持
         */

        // 0  生成 JS 文件头部
        $this->rowComment(
            "SPF-Theme 主题 $thn",
            "$thn.js 定义 cssvar",
            "!! 不要手动修改 !!"
        );

        // 1 将 context 内容 直接输出为 Js 语句
        //const cssvar
        $this->rowAdd("const cssvar = JSON.parse(\"".$ctxjson."\")");

        // 2 为 JS 语句 增加 ES6 export 支持
        $this->rowAdd("export default cssvar");
        $this->rowEmpty(1);

        //合并 rows 得到 JS 文件内容 清空 rows
        $cnt = $this->rowCnt(true);

        //创建 SCSS 文件资源实例，通过 export 获取最终的 content
        $res = $this->createSubRes($cnt, "js");
        if (empty($res)) return $this;
        //生成 content
        $this->content = $res->export([
            "return" => true
        ]);

        return $this;
    }



    /**
     * 工具方法
     */

    /**
     * 判断是否存在 要输出的 css|scss|js 缓存文件
     * @return String|false 存在则返回文件路径，不存在 返回 false
     */
    protected function cacheFile()
    {
        //params
        $ps = $this->params;

        //是否 强制不使用 缓存
        $create = $ps["create"] ?? false;
        if (!is_bool($create)) $create = false;
        //强制不使用缓存 则返回 false
        if ($create===true) return false;

        //缓存文件路径
        $cfp = $this->cacheFilePath();

        //检查文件是否存在
        if (file_exists($cfp)) return $cfp;
        return false;
    }

    /**
     * 把本次请求文件的 解析结果 缓存到 对应的 缓存文件
     * @return Bool
     */
    protected function saveToCacheFile()
    {
        //根据 params 获取 缓存文件路径
        $cfp = $this->cacheFilePath();
        //保存解析结果
        $cnt = $this->content;
        //写入文件
        return Path::mkfile($cfp, $cnt);
    }

    /**
     * 根据传入的 参数，获取 输出文件类型 css|scss|js
     * @return String ext
     */
    protected function exportExt()
    {
        $ps = $this->params;
        $ext = $ps["export"] ?? "css";
        if (!Is::nemstr($ext) || !in_array(strtolower($ext), ["css", "scss", "js"])) {
            $ext = "css";
        }
        return strtolower($ext);
    }

    /**
     * 从 传入的 $_GET 参数，获取要输出的 mode []
     * @return Array mode 数组
     */
    protected function exportModes()
    {
        $ps = $this->params;
        $mode = $ps["mode"] ?? "light";
        $mode = str_replace(["，","；",";","|"], ",", $mode);
        $marr = explode(",", $mode);
        if (!Is::nemarr($marr)) $marr = ["light"];
        return $marr;
    }

    /**
     * 根据传入的 参数，获取对应缓存文件的 文件路径，不论是否存在缓存文件
     * @return String 对应缓存文件 路径
     */
    protected function cacheFilePath()
    {
        //当前 theme 文件路径
        $real = $this->real;
        //缓存的 css|scss|js 文件在 theme 文件目录下的 [theme_name]/... theme 同名文件夹下
        $pi = pathinfo($real);
        //theme 所在文件夹
        $dir = $pi["dirname"];
        //theme 文件名 不带 后缀 foo_bar 形式
        $thn = Str::snake($pi["filename"], "_");

        //获取要读取的 文件类型|mode 生成 缓存文件名 theme_name_mode_list.ext
        $ext = $this->exportExt();
        $modn = implode("_", $this->exportModes());
        $cfn = $thn."_".$modn.".".$ext;
        //缓存文件路径
        return $dir.DS.$thn.DS.$cfn;
    }

    /**
     * 获取 可能存在的 此主题下的 common.css|scss 通用样式文件
     * 查找顺序：当前主题路径 --> 当前应用的 theme 路径下 --> 网站 theme 路径下 --> 框架 theme 路径下
     * @return String 找到的 对应的 文件真实路径
     */
    protected function commonFilePath()
    {
        //meta
        $meta = $this->meta;
        //meta 中定义的 common 文件
        $mcf = $meta["use"]["common"] ?? null;
        if (Is::nemstr($mcf)) {
            $cf = Path::find($mcf, Path::FIND_FILE);
            //定义的 common 文件存在，直接返回
            if (file_exists($cf)) return $cf;
        }

        //开始查找

        //主题名称
        $thn = $meta["name"];
        //主题所在路径
        $real = $this->real;
        $dir = dirname($real);
        //构建查找 路径数组
        $cfs = [];

        // 0 查找 当前主题的文件夹
        $cfs[] = $dir.DS.$thn.DS.$thn."_common.css";
        $cfs[] = $dir.DS.$thn.DS.$thn."_common.scss";

        // 1 查找 当前应用的 theme 路径
        if (App::$isInsed === true) {
            $appk = App::$current::clsk();
            if ($appk !== "base_app") {
                $cfs[] = "src/$appk/theme/$thn/$thn"."_common.css";
                $cfs[] = "src/$appk/theme/$thn/$thn"."_common.scss";
            }
        }

        // 2 查找 网站 theme 路径
        $cfs[] = "src/theme/$thn/$thn"."_common.css";
        $cfs[] = "src/theme/$thn/$thn"."_common.scss";

        // 3 查找 框架 theme 路径
        $cfs[] = "spf/assets/theme/$thn/$thn"."_common.css";
        $cfs[] = "spf/assets/theme/$thn/$thn"."_common.scss";

        // 4 兜底的 通用 SPF-Theme 主题样式文件，此文件一定存在
        $cfs[] = "spf/assets/theme/common.scss";

        //查找
        $cf = Path::exists($cfs);

        return $cf;
    }

    /**
     * 根据 传入的 content 创建对应的 CSS|SCSS|JS 资源实例 是当前 theme 主题资源的 引用资源
     * @param String $cnt 传入的 CSS|SCSS|JS 字符串内容
     * @param String $ext 要创建的资源类型 后缀名 例如：css|scss|js，默认 scss
     * @return Resource|null
     */
    protected function createSubRes($cnt, $ext="scss")
    {
        if (!Is::nemstr($cnt) || !Is::nemstr($ext)) return null;
        //根据当前资源的 params 生成对应 资源的 实例化参数
        $ps = Arr::extend($this->params, [
            "content" => $cnt,
            //取消 params 中的部分项目
            "create" => "__delete__",
            "mode" => "__delete__",
            "use" => "__delete__",
        ]);
        //当前主题名称
        $thn = $this->meta["name"];
        //创建资源的 文件名
        $fn = $thn.".".$ext;
        //创建资源实例
        $res = Resource::create($fn, $ps);
        if (!$res instanceof Resource) return null;
        return $res;
    }

    /**
     * 获取 当前主题的 meta 元数据
     * @param String $key 要获取的某个 meta 数据的 key
     * @return Array $this->meta
     */
    public function meta($key=null)
    {
        if (!Is::nemstr($key)) return (object)$this->meta;
        $mv = Arr::find($this->meta, $key);
        return $mv;
    }

    /**
     * 获取当前主题的 某个主题模块实例
     * @param String $modk 主题模块名 foo_bar 形式
     * @return ThemeModule 主题模块实例
     */
    public function module($modk)
    {
        $modins = $this->modules[$modk] ?? null;
        if ($modins instanceof ThemeModule) return $modins;
        return null;
    }



    /**
     * 手动操作 内容行数组
     * !! Theme 资源类扩展方法，操作 rows 数组
     */

    /**
     * 生成 CSS 变量语句
     * @return $this
     */
    protected function rowCssVars()
    {
        //context
        $ctx = $this->context;
        if (!Is::nemarr($ctx)) return $this;

        //:root {
        $this->rowAdd(":root {", "");

        //依次生成 各主题模块的 CSS 变量定义语句
        foreach ($ctx as $modk => $modc) {
            //模块输出 头部语句
            $this->rowComment("$modk 模块变量定义");
            //CSS 变量前缀
            $cvpre = "--$modk-";
            if ($modk === "vars") $cvpre = "--";
            //将此主题模块参数数组 一维化，然后依次定义为 CSS 变量
            $flat = Arr::flat($modc, "-");
            foreach ($flat as $vk => $vv) {
                $this->rowDef($vk, $vv, [
                    "prev" => $cvpre,
                ]);
            }
        }

        //}
        $this->rowEmpty(1);
        $this->rowAdd("}", "");
        $this->rowEmpty(1);

        return $this;
    }

    /**
     * 生成 SCSS 变量语句
     * @return $this
     */
    protected function rowScssVars()
    {
        //context
        $ctx = $this->context;
        if (!Is::nemarr($ctx)) return $this;

        //modules
        $modules = $this->modules;
        if (!Is::nemarr($modules)) return $this;

        //依次调用 各主题模块的 createScssVarsDefineRows 方法
        foreach ($ctx as $modk => $modc) {
            //获取对应的 主题模块实例
            $modins = $modules[$modk];
            if (empty($modins) || !$modins instanceof ThemeModule) continue;
            //模块输出 头部语句
            $this->rowComment("$modk 模块变量定义", "");
            //调用 各 主题模块的 SCSS 变量定义语句 生成方法
            $modins->createScssVarsDefineRows($modc);
        }

        //尾部空行
        $this->rowEmpty(3);

        return $this;
    }

    /**
     * 合并 主题指定的 use 其他文件，读取含数据，合并到当前 rows
     * 通过 调用 useFile 方法
     * @return $this
     */
    protected function rowUseFiles()
    {
        // 0 合并 common 文件，此文件必须合并
        $cmf = $this->commonFilePath();
        if (file_exists($cmf)) {
            $this->useFile($cmf, [
                //合并 common 文件时，启用 import 即执行 common 文件中定义的 import 语句
                "noimport" => false,
            ]);
        }

        // 1 合并 use=foo,bar 中定义的要合并的 css|scss 文件
        //params 中的 use 参数
        $uses = $this->params["use"] ?? [];
        if ($uses === "") $uses = [];
        if (Is::nemstr($uses) && $uses !== "all") $uses = Arr::mk($uses);
        //meta 中定义的 use 参数
        $usedef = $this->meta["use"] ?? [];
        if (!Is::nemarr($usedef) || !(Is::nemarr($uses) || $uses==="all")) return $this;

        //uses = all
        if ($uses === "all") {
            $uses = array_filter(array_keys($usedef), function($ui) {
                return $ui !== "common";
            });
        }

        //合并 uses 中指定的 其他 css|scss 文件
        if (Is::nemarr($uses)) {
            $ufs = [];
            foreach ($uses as $usei) {
                if (!isset($usedef[$usei]) || !Is::nemstr($usedef[$usei])) continue;
                $ufs[] = $usedef[$usei];
            }
            //使用 useFile 方法
            $this->useFile(...$ufs);
        }

        return $this;
    }

}