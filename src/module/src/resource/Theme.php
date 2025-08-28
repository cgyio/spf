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

    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //文件内容是 主题参数 json 数据，转为 Array
        $ctx = Conv::j2a($this->content);

        //提取主题元数据
        $stdMeta = self::$stdMeta;
        $meta = [];
        foreach ($stdMeta as $key => $val) {
            if (!isset($ctx[$key])) continue;
            $meta[$key] = $ctx[$key];
        }
        //使用标准数据结构 填充
        $meta = Arr::extend(self::$stdMeta, $meta);
        //保存到 meta
        $this->meta = $meta;

        //依次提取主题中包含的 主题模块，并实例化
        $mods = self::$themeModules;
        foreach ($mods as $modk) {
            $modc = $ctx[$modk] ?? [];
            $modclsn = "Theme".Str::camel($modk, true)."Module";
            $modcls = Cls::find("module/src/resource/theme/$modclsn", "Spf\\");
            if (!class_exists($modcls)) {
                //不存在 此主题模块解析类
                throw new SrcException("未找到主题模块 $modclsn 的解析类", "resource/getcontent");
            }
            $modins = new $modcls($modc);
            $this->modules[$modk] = $modins;
        }

        //标准化 params
        $ps = $this->params;
        if (!Is::nemarr($ps)) $ps = [];
        $ps = Arr::extend(self::$stdParams, $ps);
        $this->params = $ps;

        //根据要输出的 文件类型，修改 ext|mime 
        $ext = $this->exportExt();
        if ($ext !== $this->ext) {
            $this->ext = $ext;
            $this->mime = Mime::getMime($ext);
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
        if (!Is::nemarr($params)) $params = [];
        if (!Is::nemarr($params)) $this->params = Arr::extend($this->params, $params);

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

            //调用 对应 ext 的 ThemeExporter
            $ext = $this->exportExt();
            $extclsn = "Theme".Str::camel($ext, true)."Exporter";
            $extcls = Cls::find("module/src/resource/theme/$extclsn", "Spf\\");
            if (!class_exists($extcls)) {
                //主题资源输出类 不存在
                throw new SrcException("$ext 类型主题资源输出类不存在", "resource/export");
            }
            $exporter = new $extcls($this, $ctx);
            //生成 content
            $this->content = $exporter->createContent();

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
     * 资源输出的最后一步，echo
     * !! 覆盖父类
     * @param String $content 可单独指定最终输出的内容，不指定则使用 $this->content
     * @return Resource $this
     */
    protected function echoContent($content=null)
    {
        //输出资源
        if (Response::$isInsed !== true) {
            //响应实例还未创建
            throw new SrcException("响应实例还未创建", "resource/export");
        }
    
        //输出响应头，根据 资源后缀名设置 Content-Type
        Mime::setHeaders($this->ext, $this->name);
        Response::$current->header->sent();
        
        //echo
        echo $this->content;

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

}