<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Lib 子类
 * 继承自 ParsablePlain 纯文本类型基类，处理 *.lib 类型本地文件
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

class Lib extends ParsablePlain 
{
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
        /**
         * 资源来源 cdn 路径前缀 版本号之前的内容，如：https://cdnjs.cloudflare.com/ajax/libs/vue/
         * !! 可以指定为本地文件路径，如 src/app_name/lib/lib_name/
         * !! 如果指定为空字符串，则使用当前 *.lib 文件所在路径下的 lib_name 同名文件夹
         */
        "cdn" => "",

        //可以为当前的 前端库资源 单独指定一个 Lib 子类，用于执行一些自定义的 库资源 操作方法，必须是 类全称
        "class" => "",

        /*
        # 依次定义各版本的 库文件
        "2.7.16" => [
            # 对应的 文件后缀名
            "js" => [
                # 对应开关状态的 库文件
                "dev-esm-browser" => [
                    # 文件名
                    !! 文件名可以是本地路径，如：spf/assets/lib/lib_name/foo.js
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
     * !! 如果包含 * 字符，表示支持任意类型
     */
    protected static $exps = [];

    //当前前端库的 元数据
    public $meta = [
        //包含 lib | variable | version | ext | cdn | ...
    ];

    //当前请求的 库文件信息
    public $libfile = [
        //库版本号 具体的版本号，必须在 *.lib 文件中定义了
        "ver" => "",
        //请求文件的 后缀名，必须在 exps 数组中
        "ext" => "",
        //开关信息组成的 key 例如：dev-esm-browser 必须在 *.lib 文件中定义了
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

        //处理 stdFoobar 参数
        $this->fixStdProperties($ctx);

        //获取元数据
        $this->getLibMeta($ctx);
        $meta = $this->meta;

        //格式化 params 并根据 export 修改 ext|mime
        $this->formatParams();
        $ps = $this->params;

        //获取当前请求的 文件信息
        $this->getLibFile($ctx);

        //判断是否需要加载缓存
        if ($this->useCache() === true) {
            //尝试读取缓存
            $cnt = $this->getCacheContent();
            if (Is::nemstr($cnt)) {
                //存在缓存文件 则 使用缓存的 content
                $this->content = $cnt;
                return $this;
            }
        }
        
        //开始从 cdn 读取文件
        $this->getLibFromCdn($ctx);
        
        //对 cdn 返回的文件内容，按 info["fix"] 中指定的 方法序列，依次执行处理，返回处理后的 文件内容
        $this->fixContentInQueue();
        
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
     * 对从 cdn 获取的文件内容，按顺序执行 fix 处理 将处理后的结果存入 content
     * !! 自定义的库资源类，可以根据实际需要，覆盖此方法
     * @return $this
     */
    protected function fixContentInQueue()
    {
        $cnt = $this->content;

        $lf = $this->libfile;
        $info = $lf["info"];
        $fixs = $info["fix"];
        if (!Is::nemarr($fixs)) $fixs = [];

        foreach ($fixs as $fixn) {
            if (!method_exists($this, $fixn)) {
                //处理函数必须在 此类|自定义的库资源子类 中定义，访问未定义的处理函数 将报错
                throw new SrcException("调用未定义的处理方法 $fixn", "resource/getcontent");
            }
            $cnti = $this->$fixn($cnt);
            if (!Is::nemstr($cnti)) {
                //处理函数未能正确返回处理后的 content 报错
                throw new SrcException("$fixn 方法未能返回正确结果", "resource/getcontent");
            }
            $cnt = $cnti;
        }

        //保存到 content
        $this->content = $cnt;

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
        //!! 通常 库文件 css 中指向的 font 文件，应保存在本地 [lib_path]/lib_name/fonts 路径下
        $meta = $this->meta;
        $lib = $meta["lib"];
        //当前 库文件路径
        $dir = dirname($this->real);
        //转为 url
        $u = Url::src($dir);
        if (!Is::nemstr($u)) {
            //库文件路径不是可以通过 url 直接访问的，无法转为 url，通常不可能
            $pre = $lib;
        } else {
            //库文件路径 url 附加库名称
            $pre = $u."/".$lib;
        }

        $cnt = str_replace("url(fonts/", "url($pre/fonts/", $cnt);
        $cnt = str_replace("url('fonts/", "url('$pre/fonts/", $cnt);
        $cnt = str_replace("url(\"fonts/", "url(\"$pre/fonts/", $cnt);
        return $cnt;
    }



    /**
     * 缓存处理方法
     */

    /**
     * 根据传入的参数，获取缓存文件的 路径，不论文件是否存在
     * 缓存文件 默认保存在 当前资源文件路径下的 资源同名文件夹下
     * !! 子类必须实现此方法
     * @return String 缓存文件的 路径
     */
    protected function getCachePath()
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
     * 工具方法
     */

    /**
     * 根据 *.lib 文件内容，处理 stdFoobar 标准参数结构
     * @param Array $ctx *.lib 文件内容
     * @return $this
     */
    protected function fixStdProperties($ctx=[])
    {
        //修改 stdParams 和 exps 属性
        static::$stdParams = Arr::extend(static::$stdParams, $ctx["switch"]);
        static::$exps = array_merge($ctx["ext"]);
        static::$stdParams["export"] = $ctx["ext"][0];
        return $this;
    }

    /**
     * 从 *.lib 文件内容中获取 meta 元数据 保存到 meta 属性
     * @param Array $ctx *.lib 文件内容
     * @return Array $this
     */
    protected function getLibMeta($ctx=[])
    {
        //获取元数据
        $meta = [];
        $mks = ["lib","variable","version","ext","switch","cdn","class"];
        foreach ($ctx as $k => $v) {
            if (!in_array($k, $mks)) continue;
            $meta[$k] = $v;
        }
        $this->meta = $meta;
        return $this;
    }

    /**
     * 根据 params 参数，获取当前请求的 版本号
     * @return String 
     */
    protected function getLibVer()
    {
        $ps = $this->params;
        $ver = $ps["ver"] ?? static::$stdParams["ver"];
        $vers = $this->meta["version"] ?? [];
        if (isset($vers[$ver])) $ver = $vers[$ver];
        return $ver;
    }

    /**
     * 获取当前请求的 库文件信息 保存到 libfile 属性
     * !! 自定义的库资源类，可以根据实际需要，覆盖此方法
     * @param Array $ctx
     * @return $this
     */
    protected function getLibFile($ctx=[])
    {
        //$ps = $this->params;

        //获取当前请求的 文件信息
        $ver = $this->getLibVer();
        //if (isset($ctx["version"][$ver])) $ver = $ctx["version"][$ver];
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

        return $this;
    }

    /**
     * 从 cdn 获取库文件内容 保存到 content
     * !! 自定义的库资源类，可以根据实际需要，覆盖此方法
     * @param Array $ctx
     * @return $this
     */
    protected function getLibFromCdn($ctx=[])
    {
        //当前请求的 文件信息
        $lf = $this->libfile;
        $ver = $lf["ver"];
        $info = $lf["info"];

        //处理 cdn 未指定的情况
        $cdn = $ctx["cdn"] ?? null;
        if (!Is::nemstr($cdn)) {
            //未指定 cdn，使用当前 *.lib 文件的路径
            $cdn = dirname($this->real).DS.$ctx["lib"].DS;
            //转为 可通过 Path::find 获取的 相对路径
            $cdn = Path::rela($cdn);
        }

        //从 cdn 读取文件
        $cnt = static::getContentFromCdn($cdn, $ctx["lib"], $ver, $info["file"]);
        if (!Is::nemstr($cnt)) {
            //未读取到文件内容
            throw new SrcException("无法从 CDN 获取文件内容", "resource/getcontent");
        }

        //保存到 content
        $this->content = $cnt;

        return $this;
    }

    /**
     * 根据 params 中的 开关状态，生成请求的文件 keys
     * 如：dev=yes&esm=no&browser=yes       --> dev-esm!-browser  或者  dev-browser
     * !! 子类可覆盖此方法
     * @return Array 多个可能 keys 数组
     */
    public function getLibFileKeys()
    {
        $meta = $this->meta;
        $switch = $meta["switch"] ?? [];
        $ps = $this->params;
        $ns = [];
        foreach ($switch as $k => $v) {
            //if (!isset($ps[$k])) continue;
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
            return implode("-", $nsi);
        },$ns);

        return $names;
    }

    /**
     * 获取 当前库内部的 文件夹路径
     * @param String $inner 文件夹名称
     * @param Bool $exists 是否检查是否存在，默认 false，如果要检查，则不存在则返回 null
     * @return String|null
     */
    public function getInnerDir($inner, $exists=false)
    {
        //当前组件库的 本地路径
        $dir = dirname($this->real);
        //组件库名称
        $lib = $this->meta["lib"];
        //组件库版本
        $ver = $this->getLibVer();
        //路径
        $path = $dir.DS.$lib.DS.$ver.(Is::nemstr($inner) ? DS.trim(str_replace("/",DS,$inner), DS) : "");
        //处理 ../
        $path = Path::fix($path);

        if ($exists === false) return $path;

        return (file_exists($path) && is_dir($path)) ? $path : null;
    }

    /**
     * 获取 当前库内部的 文件路径
     * @param String $inner 内部文件路径 相对路径，不含 版本号
     * @param Bool $exists 是否检查是否存在，默认 false，如果要检查，则不存在则返回 null
     * @return String|null 需要检查是否存在，且文件不存在时，返回 null，否则返回文件路径
     */
    public function getInnerFilePath($inner, $exists=false)
    {
        //当前库的根目录
        $libp = $this->getInnerDir("", $exists);
        if (!Is::nemstr($libp)) return null;
        //拼接文件路径
        $fp = $libp.(Is::nemstr($inner) ? DS.trim(str_replace(["/","\\"], DS, $inner), DS) : "");
        //处理 ../
        $fp = Path::fix($fp);
        if ($exists === false) return $fp;
        return (file_exists($fp) && is_file($fp)) ? $fp : null;
    }

    /**
     * 获取当前组件的 url 前缀，用于获取库内部文件的 外部访问 url
     * @return String
     */
    public function getLibUrlPrefix()
    {
        //当前 url
        $url = Url::current();
        $u = $url->full;
        //当前 库 名称，保存在 meta["lib"]
        $meta = $this->meta;
        $lib = $meta["lib"];
        //当前版本
        $ver = $this->getLibVer();
        //pattern
        $pattern = "/(\/src\/lib\/.*".$lib.")/";
        //匹配当前 url
        $mt = preg_match($pattern, $u, $matches);

        if ($mt === 1) {
            //匹配的完整字符，通常为：/src/lib/[path/to/lib/]lib_name
            $key = $matches[0];
            //分割 url
            $ua = explode($key, $u);
            //返回前缀
            return $ua[0].$key."/".$ver;
        }

        //未匹配到 url 中的关键字符，尝试构建
        $ua = [];
        //domain
        $ua[] = rtrim($url->domain, "/");
        //当前请求指向的 App
        if (App::$isInsed === true) {
            $appk = App::$current::clsk();
            if ($appk !== "base_app") {
                $ua[] = $appk;
            }
        }
        // src
        $ua[] = "src";
        //当前库的 filePath 特定路径
        $sfp = $this->getSpecFilePath();
        if (!Is::nemstr($sfp) || strpos($sfp, "/")===false) $sfp = "lib";
        $ua[] = trim($sfp);
        //当前的 库名称
        $ua[] = $lib;
        //当前版本
        $ua[] = $ver;

        //拼接，返回
        return implode("/", $ua);
    }

    /**
     * 获取库内部文件的 外部访问 url
     * 例如：
     * 当前库 url：      /src/lib/foo/bar/lib_name.lib
     * 要访问库内部文件： 1.0.1/cache/jaz.json
     * 则生成文件 url：  /src/lib/foo/bar/lib_name/cache/jaz.json
     * @param String $path 内部问相对于 库根路径的
     */



    /**
     * 静态工具
     */

    /**
     * 从 cdn 获取指定的 库文件内容 可以是 remote|local 文件路径
     * @param String $cdn *.lib 文件中指定的 cdn 路径
     * @param String $lib 库名称 如：vue
     * @param String $ver 要请求的文件版本号
     * @param String $file 要获取的 cdn 路径下的 文件名，!! 也可以是本地路径
     * @return String|null
     */
    public static function getContentFromCdn($cdn, $lib, $ver, $file)
    {
        if (!Is::nemstr($cdn) || !Is::nemstr($lib) || !Is::nemstr($ver) || !Is::nemstr($file)) return null;

        //判断 cdn 是否远程地址
        $isRemote = strpos($cdn, "//")!==false || substr($cdn, 0,1)==="/";
        if ($isRemote) {
            if (strpos($cdn, "://")!==false) {
                //完整的 url
                $isHttps = substr($cdn, 0, 5)==="https";
            } else {
                //以 // 或 / 开头的 url
                $url = Url::current();
                $isHttps = $url->protocol === "https";
                if (strpos($cdn, "//")!==false) {
                    $cdn = $url->protocol.":".$cdn;
                } else {
                    $cdn = $url->domain.$cdn;
                }
            }
        }

        //拼接文件路径
        $fp = trim($cdn,"/")."/".$ver."/".$file;

        //判断 file 是否本地路径
        $isLocalFile = false;
        //准备要检查的 文件路径列表
        $lcfs = [$file];
        //拼接 lib/lib_name/lib_ver 前缀
        $lcfs[] = "lib/$lib/$ver/".ltrim($file,"/");
        //$isRemote!==true 时，拼接 cdn 路径
        if (!$isRemote) $lcfs[] = $fp;
        //依次检查文件是否存在
        foreach ($lcfs as $lcfp) {
            $lcf = Resource::findLocal($lcfp);
            if (!file_exists($lcf)) continue;
            $isLocalFile = true;
            break;
        }

        //读取本地文件内容
        if ($isLocalFile) {
            return file_get_contents($lcf);
        }

        //读取远程文件
        if ($isRemote) {
            $cnt = $isHttps===true ? Curl::get($fp, "ssl") : Curl::get($fp);
            if (!Is::nemstr($cnt)) return null;
            return $cnt;
        }
        
        //通常不会存在的 其他情况
        return null;
    }

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
        //传入空参数，报 404
       if (!Is::nemarr($args)) {
           Response::insSetCode(404);
           return null;
       }

       //拼接请求的 路径字符串
       $req = implode("/", $args);

       //先检查一次请求的路径是否真实存在的 文件
       $lfres = static::getLocalResource("lib/$req");
       if ($lfres !== false) return $lfres;

       //解析路径
       $pi = pathinfo($req);
       $ext = $pi["extension"] ?? "";
       $fn = $pi["filename"] ?? "";
       $lp = array_slice($args, 0, -1);
       //lib 资源实例化参数
       $ps = [];
       //处理 min
       if (substr($fn, -4)===".min") {
           $fn = substr($fn, 0, -4);
           $ps["min"] = true;
       }
       //version 版本数据
       //$ver = null;
       //switch 开关状态
       //$sw = [];
       //lib 文件路径，真实存在的
       $libf = null;
       //找到 *.lib 文件后，剩余的 右侧路径数组，可能包含 ver|switch 数据
       $lpr = [];

       //依次截取 请求路径，查找可能存在的 *.lib 文件
       if (empty($lp)) {
            // /src/lib/lib_name[.js|css] 形式
            $libf = Resource::findLocal($fn.".lib");
            if (!file_exists($libf)) {
                Response::insSetCode(404);
                return null;
            }
        } else {
            $lps = array_merge($lp, [$fn]);
            //依次查找可能存在的 *.lib 本地文件
            for ($i=count($lps);$i>=1;$i--) {
                $dlp = array_slice($lps, 0, $i);
                $lbf = implode("/", $dlp).".lib";
                //var_dump($lbf);
                $lbfp = Resource::findLocal($lbf);
                if (file_exists($lbfp)) {
                    $libf = $lbfp;
                    $j = count($lps)-$i;
                    if ($j>0) $lpr = array_slice($lps, $j*-1);
                    break;
                }
            }
        }
        
        //如果未找到 *.lib 本地文件
        if (!Is::nemstr($libf)) {
            Response::insSetCode(404);
            return null;
        }

        //将已处理的 得到的 参数合并为 object 参数
        $opt = [
            //已处理过，得到的 参数
            "req" => $req,
            "pi" => $pi,
            "ext" => $ext,
            "fn" => $fn,
            "lp" => $lp,
            "ps" => $ps,
            "libf" => $libf,
            "lpr" => $lpr,
        ];

        //读取 *.lib 文件，提取可能存在的 class 子类
        $libcnt = file_get_contents($libf);
        $libctx = Conv::j2a($libcnt);
        $libcls = $libctx["class"] ?? null;
        if (Is::nemstr($libcls)) {
            $clsn = Cls::find($libcls);
            if (class_exists($clsn) && is_subclass_of($clsn, static::class)) {
                //存在自定义的 Lib 子类，则使用 子类的 customResponse 方法
                $opt["clsn"] = $clsn;
                return $clsn::customResponse($args, (object)$opt);
            }
        }

        //调用 Lib 类的 默认 customResponse 方法
        return static::customResponse($args, (object)$opt);
    }

    /**
     * 默认的 Lib 类响应处理方法，
     * !! 如果定义了 Lib 子类，应在子类中实现自定义的 响应逻辑
     * @param Array $args URI 参数数组
     * @param Object $opt 经过 Lib::response 方法处理 $args 得到的参数，子类不需要再次重复处理，只需要实现自有的处理方法
     * @return Mixed
     */
    public static function customResponse($args, $opt)
    {

        /**
         * 实现 Src 模块中的 Lib 特有响应方法
         * !! 这是默认方法，如果有自定义的 Lib 子类，应实现自有的处理方法
         * 
         * 请求方法：
         * https://host/[app_name/]src/lib/[foo/bar/][lib_name][.js|css]
         * https://host/[app_name/]src/lib/[foo/bar/][lib_name]/[dev!-esm|esm|...].[js|css]
         * https://host/[app_name/]src/lib/[foo/bar/][lib_name]/[@|latest|1.2.3][.js|css]
         * https://host/[app_name/]src/lib/[foo/bar/][lib_name]/[@|latest|1.2.3]/[esm|...].[js|css]
         */

        //拼接请求的 路径字符串
        $req = $opt->req;

        //解析路径
        $pi = $opt->pi;
        $ext = $opt->ext;
        $fn = $opt->fn;
        $lp = $opt->lp;
        //lib 资源实例化参数
        $ps = $opt->ps;
        //已找到的 *.lib 文件路径
        $libf = $opt->libf;
        //找到 *.lib 文件后，剩余的 右侧路径数组，可能包含 ver|switch 数据
        $lpr = $opt->lpr;
        //version 版本数据
        $ver = null;
        //switch 开关状态
        $sw = [];

        //处理剩余的右侧路径 可能包含 ver|switch 数据
        if (!empty($lpr)) {
            if (count($lpr)>2) {
                //剩余路径只可能包含 ver|switch 数据，数组长度不会超过 2
                Response::insSetCode(404);
                return null;
            }
            if (count($lpr)===2) {
                //处理 switch 数据
                $sw = explode("-", $lpr[1]);
                //处理 ver 数据
                $ver = $lpr[0];
            } else {
                //判断 包含的是 ver 还是 switch 数据
                if (in_array($lpr[0], ["@","latest"]) || strpos($lpr[0],".")!==false || is_numeric($lpr[0])) {
                    $ver = $lpr[0];
                } else {
                    $sw = explode("-", $lpr[0]);
                }
            }
        }
        
        //准备 Lib 资源类实例化参数
        if (Is::nemstr($ext)) $ps["export"] = $ext;
        if (Is::nemstr($ver)) $ps["ver"] = $ver;
        if (!empty($sw)) {
            foreach ($sw as $swi) {
                $swv = substr($swi, -1)!=="!";  //strpos($swi,"!")===false;
                $swk = $swv ? $swi : substr($swi, 0, -1);  //str_replace("!","",$swi);
                $ps[$swk] = $swv;
            }
        }

        //创建 Lib 资源实例
        $libo = Resource::create($libf, $ps);
        if (!$libo instanceof Resource) {
            //报错
            throw new SrcException("$libf 文件资源无法实例化", "resource/instance");
        }
        
        //将 Response 输出类型 改为 src
        Response::insSetType("src");
        //输出资源
        return $libo;
    }




    /**
     * response method bak
     */
    public static function __responseBak(...$args)
    {
        
        //处理 min
        if (substr($fn, -4)===".min") {
            $fn = substr($fn, 0, -4);
            $ps["min"] = true;
        }
        //version 版本数据
        $ver = null;
        //switch 开关状态
        $sw = [];
        //lib 文件路径，真实存在的
        $libf = null;

        if (empty($lp)) {
            // /src/lib/lib_name[.js|css] 形式
            $libf = Resource::findLocal($fn.".lib");
            if (!file_exists($libf)) {
                Response::insSetCode(404);
                return null;
            }
        } else {
            $lps = array_merge($lp, [$fn]);
            //依次查找可能存在的 *.lib 本地文件
            for ($i=count($lps);$i>=1;$i--) {
                $dlp = array_slice($lps, 0, $i);
                $lbf = implode("/", $dlp).".lib";
                //var_dump($lbf);
                $lbfp = Resource::findLocal($lbf);
                if (file_exists($lbfp)) {
                    $libf = $lbfp;
                    $j = count($lps)-$i;
                    if ($j>0) $lpr = array_slice($lps, $j*-1);
                    break;
                }
            }
            //处理剩余的右侧路径 可能包含 ver|switch 数据
            if (!empty($lpr)) {
                if (count($lpr)>2) {
                    //剩余路径只可能包含 ver|switch 数据，数组长度不会超过 2
                    Response::insSetCode(404);
                    return null;
                }
                if (count($lpr)===2) {
                    //处理 switch 数据
                    $sw = explode("-", $lpr[1]);
                    //处理 ver 数据
                    $ver = $lpr[0];
                } else {
                    //判断 包含的是 ver 还是 switch 数据
                    if (in_array($lpr[0], ["@","latest"]) || strpos($lpr[0],".")!==false || is_numeric($lpr[0])) {
                        $ver = $lpr[0];
                    } else {
                        $sw = explode("-", $lpr[0]);
                    }
                }
            }
        }

        //如果未找到 *.lib 本地文件
        if (!Is::nemstr($libf)) {
            Response::insSetCode(404);
            return null;
        }
        //准备 Lib 资源类实例化参数
        if (Is::nemstr($ext)) $ps["export"] = $ext;
        if (Is::nemstr($ver)) $ps["ver"] = $ver;
        if (!empty($sw)) {
            foreach ($sw as $swi) {
                $swv = substr($swi, -1)!=="!";  //strpos($swi,"!")===false;
                $swk = $swv ? $swi : substr($swi, 0, -1);  //str_replace("!","",$swi);
                $ps[$swk] = $swv;
            }
        }

        //创建 Lib 资源实例
        $libo = Resource::create($libf, $ps);
        if (!$libo instanceof Resource) {
            //报错
            throw new SrcException("$libfp 文件资源无法实例化", "resource/instance");
        }
        
        //将 Response 输出类型 改为 src
        Response::insSetType("src");
        //输出资源
        return $libo;
    }



}