<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Lib 子类
 * 继承自 Plain 纯文本类型基类，处理 *.lib 类型本地文件
 * 
 * 前端库 加载|处理|输出
 * 
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
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;
use Spf\util\Curl;

class Lib extends Plain 
{
    /**
     * 当前的资源类型的本地文件，是否应保存在 特定路径下
     * 指定的 特定路径 必须在 Src::$current->config->resource["access"] 中定义的 允许访问的文件夹下
     *  null        表示不需要保存在 特定路径下，本地资源应在 [允许访问的文件夹]/... 路径下
     *  "ext"       表示应保存在   [允许访问的文件夹]/[资源后缀名]/... 路径下
     *  "foo/bar"   指定了 特定路径 foo/bar 表示此类型本地资源文件应保存在   [允许访问的文件夹]/foo/bar/... 路径下
     * 默认 null 不指定 特定路径
     * !! 覆盖父类
     * !! 前端库 *.lib 文件 必须保存在 [允许访问的文件夹]/lib/... 路径下
     */
    public static $filePath = "ext";    //可选 null | ext | 其他任意路径形式字符串 foo/bar 首尾不应有 /

    

    /**
     * 定义标准的 前端库参数格式
     * !! 如果需要，子类可以覆盖此属性
     */
    protected static $stdLib = [
        //前端库名称 如：vue
        "lib" => "",
        //此前端库 代码中的 变量名 如：Vue
        "variable" => "",
        //指定可以通过 @|latest 访问的 版本号
        "version" => [
            //当前使用的 默认版本 可省略版本号，如：/src/vue.lib  相当于  /src/vue.lib?ver=@
            "@" => "",
            //最新版本号，如：/src/vue.lib?ver=latest
            "latest" => "",
        ],
        //此 前端库 可对外提供的 文件后缀名类型，可以有多个
        "ext" => [],
        //可通过这些开关，切换需要输出的 库文件 版本
        "switch" => [
            //是否用于 开发环境
            "dev" => false,
            //是否以 esm 形式导入
            "esm" => true,
            //是否用于 浏览器端
            "browser" => true,
            //可以自定义其他 开关
            //...
        ],
        //资源来源 cdn 路径前缀 版本号之前的内容，如：https://cdnjs.cloudflare.com/ajax/libs/vue/
        "cdn" => "",

        /*
        # 依次定义各版本的 库文件
        "2.7.16" => [
            # 对应的 文件后缀名
            "js" => [
                # 对应开关状态的 库文件
                "-dev-esm-browser-" => [
                    # 文件名
                    "file" => "vue.common.dev.min.js",
                    # 文件内容处理函数序列
                    "fix" => [
                        "appendExportForEsm",   为本来不带 esm 导出的 js 库，增加导出语句
                    ]
                ],
                ...
            ],
            ...
        ],
        ...
        */
    ];
    //标准的 某个 开关状态的库文件 的参数格式
    protected static $stdLibfile = [
        //文件名，可从 cdn 直接访问到的 文件名
        "file" => "",
        //对 从 cdn 获取到的文件内容 进行处理的 方法序列，这些方法必须在 Lib 类中定义
        "fix" => [],
    ];

    /**
     * 定义 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    protected static $stdParams = [
        //是否 强制不使用 缓存的 库文件内容
        "create" => false,
        //使用的 库版本号 默认 @  可选 @|latest|具体版本号 如 2.3.4
        "ver" => "@",
        //输出文件的 类型 默认类型是 stdLib["ext"][0]
        "export" => "",

        //开关参数，在 stdLib["switch"] 中定义的内容
        //...
        
        //其他可选参数，主要用于处理 要输出的 库文件 如 js|css 文件 的参数
        //...
    ];

    /**
     * 定义支持的 export 类型，必须定义相关的 createFooContent() 方法
     * 必须是 Mime 支持的 文件后缀名
     * !! 覆盖父类
     * !! 与 stdLib["ext"] 一致，在运行时生成
     */
    protected static $exps = [];

    //当前前端库的 元数据
    protected $meta = [
        //包含 lib | variable | version | ext | cdn | ...
    ];

    //当前请求的 库文件信息
    protected $libfile = [
        //库版本号 具体的版本号，必须在 *.lib 文件中定义了
        "ver" => "",
        //请求文件的 后缀名，必须在 exps 数组中
        "ext" => "",
        //开关信息组成的 key 例如：-dev-esm-browser- 必须在 *.lib 文件中定义了
        "key" => "",
        //当前请求的 文件信息 与 stdLibfile 结构一致
        "info" => [],

    ];

    

    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //读取并处理 库定义数据
        $ctx = Conv::j2a($this->content);
        //合并 格式化为 标准图标库参数形式
        $ctx = Arr::extend(static::$stdLib, $ctx);
        //修改 stdParams 和 exps 属性
        static::$stdParams = Arr::extend(static::$stdParams, $ctx["switch"]);
        static::$exps = array_merge($ctx["ext"]);
        static::$stdParams["export"] = $ctx["ext"][0];

        //获取元数据
        $meta = [];
        $mks = ["lib","variable","version","ext","switch","cdn"];
        foreach ($ctx as $k => $v) {
            if (!in_array($k, $mks)) continue;
            $meta[$k] = $v;
        }
        $this->meta = $meta;

        //格式化 params 并根据 export 修改 ext|mime
        $this->formatParams();
        $ps = $this->params;

        //获取当前请求的 文件信息
        $ver = $ps["ver"];
        if (isset($ctx["version"][$ver])) $ver = $ctx["version"][$ver];
        if (!isset($ctx[$ver])) {
            //具体版本号 必须在 *.lib 文件中定义
            throw new SrcException("不存在版本号 $ver", "resource/getcontent");
        }
        $extfs = $ctx[$ver][$this->ext];
        //根据开关信息，生成 库文件 key
        $keys = $this->getLibFileKeys();
        $key = null;
        $info = null;
        if (Is::nemarr($keys)) {
            foreach ($keys as $keyi) {
                if (isset($extfs[$keyi])) {
                    $key = $keyi;
                    $info = $extfs[$keyi];
                    break;
                }
            }
        }
        if (!Is::nemstr($key) || !Is::nemarr($info)) {
            //无法生成 key 数组
            throw new SrcException("无法生成请求文件 KEY", "resource/getcontent");
        }
        //保存到 libfile
        $this->libfile = [
            "ver" => $ver,
            "ext" => $this->ext,
            "key" => $key,
            "info" => Arr::extend(static::$stdLibfile, $info)
        ];

        //尝试读取缓存
        $cnt = $this->getCacheContent();
        if (Is::nemstr($cnt)) {
            //存在缓存文件 则 使用缓存的 content
            $this->content = $cnt;
            return $this;
        }
        
        //开始从 cdn 读取文件
        $cdn = $ctx["cdn"] ?? null;
        if (!Is::nemstr($cdn)) {
            //未指定 cdn
            throw new SrcException("未指定库的 CDN 路径", "resource/getcontent");
        }
        $https = strpos($cdn, "http://")===false;
        $url = trim($cdn,"/")."/".$ver."/".$info["file"];
        $cnt = $https===true ? Curl::get($url, "ssl") : Curl::get($url);
        if (!Is::nemstr($cnt)) {
            //未读取到文件内容
            throw new SrcException("无法从 CDN 获取文件内容", "resource/getcontent");
        }
        //对 cdn 返回的文件内容，按 info["fix"] 中指定的 方法序列，依次执行处理，返回处理后的 文件内容
        $fixs = $info["fix"];
        if (!Is::nemarr($fixs)) $fixs = [];
        foreach ($fixs as $fixn) {
            if (!method_exists($this, $fixn)) {
                //处理函数必须在此类中定义，访问未定义的处理函数 将报错
                throw new SrcException("调用未定义的处理方法 $fixn", "resource/getcontent");
            }
            $cnti = $this->$fixn($cnt);
            if (!Is::nemstr($cnti)) {
                //处理函数未能正确返回处理后的 content 报错
                throw new SrcException("$fixn 方法未能返回正确结果", "resource/getcontent");
            }
            $cnt = $cnti;
        }
        //保存文件内容到 content
        $this->content = $cnt;
        //写入缓存文件
        $this->saveCacheContent();

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

        //TODO：调用 缓存文件路径，生成 文件后缀名对应的 Resource 实例
        //暂时不做处理，直接输出 content

        //不处理 min 因为 加载的 cdn 内容通常都是 压缩版
        return $this;
    }



    /**
     * 针对从 cdn 获取的库文件内容，执行 *.lib 中定义的处理函数
     * @param String $cnt 文件内容，已经过上一个处理函数处理过
     * @return String 返回处理后的 cnt
     */
    //处理 js： 为普通库文件 增加 esm 支持的 export 导出语句
    protected function appendExportForEsm($cnt)
    {
        $meta = $this->meta;
        $var = $meta["variable"];
        $js = ";window.$var = $var;export default $var;";
        return $cnt.$js;
    }
    //处理 js： 某些版本的 vue 会出现 module 未定义错误
    protected function handleModuleIsNotDefinedErr($cnt)
    {
        $cnt = $this->stripUseStrict($cnt);
        $cnt = "const module={};".$cnt;
        $cnt = "\"use strict\";".$cnt;
        return $cnt;
    }
    //处理 js： 去除 use strict 语句
    protected function stripUseStrict($cnt)
    {
        return str_replace("\"use strict\";","",$cnt);
    }
    //处理 css： 替换 css 中的 fonts 文件路径
    protected function fixIconFonts($cnt)
    {
        //$url = Url::current();
        //$domain = $url->domain;
        $meta = $this->meta;
        $lib = $meta["lib"];
        $cnt = str_replace("url(fonts/", "url($lib/fonts/", $cnt);
        return $cnt;
    }




    /**
     * 工具方法
     */

    /**
     * 根据 params 中的 开关状态，生成请求的文件 keys
     * 如：dev=yes&esm=no&browser=yes       --> -dev-esm!-browser-  或者  -dev-browser-
     * @return Array 多个可能 keys 数组
     */
    public function getLibFileKeys()
    {
        $meta = $this->meta;
        $switch = $meta["switch"] ?? [];
        $ps = $this->params;
        $ns = [];
        foreach ($switch as $k => $v) {
            $pv = $ps[$k];
            if ($pv === true) {
                if (empty($ns)) {
                    $ns[] = [$k];
                } else {
                    foreach ($ns as $i => $nsi) {
                        $ns[$i] = array_merge($nsi, [$k]);
                    }
                }
            } else {
                if (empty($ns)) {
                    $ns[] = [$k."!"];
                    $ns[] = [];
                } else {
                    $nns = [];
                    foreach ($ns as $i => $nsi) {
                        $nns[$i] = array_merge($nsi, [$k."!"]);
                    }
                    $ns = array_merge($ns, $nns);
                }
            }
        }
        $names = array_map(function($nsi){
            if (!is_array($nsi)) return "--";
            return "-".implode("-", $nsi)."-";
        },$ns);

        return $names;
    }

    /**
     * 根据 当前请求的文件信息，获取 缓存文件路径  不检查是否存在
     * @return String 
     */
    public function getCachePath()
    {
        //当前请求的文件信息
        $lf = $this->libfile;
        $key = $lf["key"];
        $ver = $lf["ver"];
        $meta = $this->meta;
        $lib = $meta["lib"];
        $dir = dirname($this->real);
        return $dir.DS.$lib.DS.$ver.DS.$key.".".$this->ext;
    }

    /**
     * 根据 当前请求的文件信息 查找对应路径下是否存在缓存文件，存在则读取内容，不存在则返回 null
     * @return String|null
     */
    protected function getCacheContent()
    {
        //在 create==true 情况下，不读取缓存
        $create = $this->params["create"] ?? false;
        if ($create===true) return null;

        //当前请求的文件信息
        $cf = $this->getCachePath();
        if (!file_exists($cf)) return null;

        //读取并返回 缓存文件内容
        $cnt = file_get_contents($cf);
        return $cnt;
    }

    /**
     * 将解析后的 文件内容 写入 缓存文件
     * @return Bool
     */
    protected function saveCacheContent()
    {
        //当前请求的文件信息
        $cf = $this->getCachePath();
        if (!file_exists($cf)) {
            //文件不存在 则创建
            return Path::mkfile($cf, $this->content);
        }

        //文件存在 则写入
        file_put_contents($cf, $this->content);
        return true;
    }



}