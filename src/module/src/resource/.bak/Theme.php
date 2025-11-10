<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Theme 子类
 * 继承自 ParsablePlain 复合资源类型基类，处理 *.theme 类型本地文件
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

class Theme extends ParsablePlain
{
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
        "css", "scss", "js",
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
    public $meta = [];
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

        //检查是否需要 读取缓存
        if ($this->useCache() === true) {
            //读取缓存
            $cnt = $this->getCacheContent();
            if (Is::nemstr($cnt)) {
                //缓存内容存在，直接使用缓存的内容
                $this->content = $cnt;
                return $this;
            }
        }

        //忽略缓存 或 缓存不存在 则 解析主题文件 生成资源内容
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

        //根据 export 类型 生成对应的 content
        $this->createExportContent();

        //写入缓存文件
        $this->saveCacheContent();

        return $this;
    }



    /**
     * 缓存处理方法
     */

    /**
     * 根据传入的参数，获取缓存文件的 路径，不论文件是否存在
     * 缓存文件 默认保存在 当前资源文件路径下的 资源同名文件夹下
     * !! 覆盖父类
     * @return String 缓存文件的 路径
     */
    protected function getCachePath()
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
     * 获取本地资源名称，即 主题名称
     * !! 覆盖 Resource 父类方法
     * @return String|null
     */
    public function getLocalResName()
    {
        return $this->meta["name"];
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
            $this->useFile([
                //合并 common 文件时，启用 import 即执行 common 文件中定义的 import 语句
                "noimport" => false,
            ], $cmf);
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



    /**
     * 静态工具
     */

    /**
     * 当前类型的 复合资源，可以在 Src 模块中定义 特有的 响应方法
     * 例如：*.theme 资源 在 Src 模块中的 可以定义 特有的响应方法 themeView
     * 响应方法将调用 此处定义的 方法逻辑
     * !! 覆盖父类
     * !! 此方法将直接操作 Response 响应实例，返回响应结果
     * @param Array $args URI 路径数组
     * @return Mixed
     */
    public static function response(...$args)
    {
        /**
         * 实现 Src 模块中的 Theme 特有响应方法
         * 
         * 请求方法：
         * https://host/[app_name/]src/theme/[theme_name]                       访问 主题编辑器
         * https://host/[app_name/]src/theme/[theme_name][.min].[js|css|scss]   访问主题 JS|CSS|SCSS 文件
         */

        //传入空参数，报 404
        if (!Is::nemarr($args)) {
            Response::insSetCode(404);
            return null;
        }
        
        //拼接请求的 路径字符串
        $req = implode("/", $args);

        //先检查一次请求的路径是否真实存在的 文件
        $lfres = static::getLocalResource("theme/$req");
        if ($lfres !== false) return $lfres;
        
        //解析对应的 后缀名|文件夹|文件名
        $pi = pathinfo($req);
        $ext = $pi["extension"] ?? "";
        $dir = $pi["dirname"] ?? "";
        $fn = $pi["filename"] ?? "";

        //处理请求的资源上级路径
        if (!Is::nemstr($dir) || in_array($dir, ["."])) $dir = "";

        //处理请求资源的 后缀名
        if (!Is::nemstr($ext)) $ext = "theme";
        $ext = strtolower($ext);

        //支持输出的 ext 格式
        $exts = array_merge(static::$exps, ["theme"]);
        //请求的 后缀名不支持，报 404
        if (!in_array(strtolower($ext), $exts)) {
            Response::insSetCode(404);
            return null;
        }

        //请求的文件名为空，报 404
        if (!Is::nemstr($fn)) {
            Response::insSetCode(404);
            return null;
        }

        //是否存在 .min.
        $ismin = false;
        if (substr(strtolower($fn), -4)===".min") {
            $ismin = true;
            $fn = substr($fn, 0, -4);
        }

        //首先定位 请求的主题 *.theme 文件，创建 Theme 主题资源实例
        if ($ext === "theme") {
            $thfp = substr($req, -6)===".theme" ? $req : $req.".theme";
        } else {
            $thfp = ($dir=="" ? "" : $dir."/").$fn.".theme";
        }
        //调用 Resource::findLocal() 方法 查找对应的 *.theme 文件
        $thf = Resource::findLocal($thfp);
        //未找到 *.theme 文件
        if (!file_exists($thf)) {
            Response::insSetCode(404);
            return null;
        }

        //主题资源实例 的 params 参数
        $ps = [];
        if ($ismin) $ps["min"] = true;
        if ($ext !== "theme") {
            $ps["export"] = $ext;
        }
        
        //创建 Theme 资源实例
        $theme = Resource::create($thf, $ps);
        if (!$theme instanceof Resource) {
            //报错
            throw new SrcException("$thfp 文件资源无法实例化", "resource/instance");
        }

        //根据请求的后缀名，决定输出的资源内容
        if ($ext === "theme") {
            //输出 主题编辑器 视图
            Response::insSetType("view");
            
            //TODO: 创建 ThemeView 实例
            return [
                //使用 ThemeView 视图类
                "view" => "view/ThemeView",
                //传入 Theme 资源实例作为 视图类的 实例化参数
                "params" => [
                    "theme" => $theme
                ]
            ];

        }
        
        //根据 ext 输出对应的 JS|CSS|svg 资源
        //将 Response 输出类型 改为 src
        Response::insSetType("src");
        //输出资源
        return $theme;
    }

}