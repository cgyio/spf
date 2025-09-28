<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 ParsablePlain 子类 基类
 * 继承自 Plain 类，专门处理 需要 解析|生成 并可以输出多种 格式资源的 本地复合资源类
 * 如：*.theme | *.icon | *.lib | *.scss 等 类型的 资源
 */

namespace Spf\module\src\resource;

use Spf\Env;
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

class ParsablePlain extends Plain
{
    /**
     * 当前的资源类型的本地文件，是否应保存在 特定路径下
     * 指定的 特定路径 必须在 Src::$current->config->resource["access"] 中定义的 允许访问的文件夹下
     *  null        表示不需要保存在 特定路径下，本地资源应在 [允许访问的文件夹]/... 路径下
     *  "ext"       表示应保存在   [允许访问的文件夹]/[资源后缀名]/... 路径下
     *  "foo/bar"   指定了 特定路径 foo/bar 表示此类型本地资源文件应保存在   [允许访问的文件夹]/foo/bar/... 路径下
     * 默认 null 不指定 特定路径
     * !! 如果需要，子类可以覆盖
     */
    public static $filePath = "ext";    //默认必须保存在 当前资源后缀名（子类名）文件夹下

    /**
     * 当前资源类型是否定义了 factory 工厂方法，如果是，则在实例化时，通过 工厂方法 创建资源实例，而不是 new 
     * !! 针对存在 资源自定义子类 的情况，如果设为 true，则必须同时定义 factory 工厂方法
     * !! 覆盖父类
     * 
     * 复合资源默认开启 工厂方法，允许在当前 *.theme|*.icon|*.lib|... 文件中定义 class 项目，指向自定义的 ParsablePlain 子类
     */
    public static $hasFactory = true;

    /**
     * 可根据当前的 资源类的 需要，定义一系列 stdFoo 标准数据格式
     */
    //protected static $stdFoo = [];
    //...

    /**
     * 定义 纯文本文件 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    protected static $stdParams = [
        //是否 强制不使用 缓存的 数据
        "create" => false,
        //输出文件的 类型
        "export" => "",
        //可指定要合并输出的 其他 文件
        "use" => [],
        //是否忽略 @import 默认 false
        //"noimport" => false,
        
        //其他可选参数
        //...
    ];

    /**
     * 定义支持的 export 类型，必须定义相关的 createFooContent() 方法
     * 必须是 Mime 支持的 文件后缀名
     * !! 子类可覆盖此属性
     * !! 如果包含 * 字符，表示支持任意类型
     */
    protected static $exps = [
        "js", "css",
    ];

    //当前资源的 元数据
    public $meta = [];



    /**
     * ParsablePlain 复合资源类 自定义 工厂方法
     * @param Array $opt 资源类的实例化参数
     * @return Lib|null 库资源类实例
     */
    final protected static function factory($opt=[])
    {
        //复合资源一定是本地资源，一定存在 opt["real"] 本地文件路径
        $real = $opt["real"] ?? null;
        if (!Is::nemstr($real) || !file_exists($real)) return null;

        /**
         * 复合资源类 如果要使用自定的 ParsablePlain 子类，必须在当前 *.theme|*.icon|*.lib|... 文件中定义 class 项
         */
        $ctx = file_get_contents($real);
        $ctx = Conv::j2a($ctx);
        //检查是否指定了一个 ParsablePlain 子类
        $cls = $ctx["class"] ?? "";
        if (Is::nemstr($cls)) {
            $clsp = Cls::find($cls);
            if (class_exists($clsp) && is_subclass_of($clsp, static::class)) {
                //指定了一个 存在的 ParsablePlain 子类，实例化
                $res = new $clsp($opt);
                if (!$res instanceof static) return null;
                return $res;
            }
        }

        //未指定 ParsablePlain 子类，则实例化 当前 ParsablePlain 类
        $res = new static($opt);
        if (!$res instanceof static) return null;
        return $res;
    }

    /**
     * 当前资源创建完成后 执行
     * !! 子类必须实现此方法
     * @return Resource $this
     */
    protected function afterCreated()
    {
        /**
         * 实现对资源文件 *.theme|*.icon|... 的解析
         * 处理 params 
         * 根据 export 参数调整实际输出的 ext|mime 参数
         * 根据 create 参数 读取缓存 或 解析生成 content
         */

        $this->formatParams();
        $ps = $this->params;

        $ctx = Conv::j2a($this->content);


        return $this;
    }

    /**
     * 在输出资源内容之前，对资源内容执行处理
     * !! 子类必须实现此方法
     * @param Array $params 可传入额外的 资源处理参数
     * @return Resource $this
     */
    protected function beforeExport($params=[])
    {
        /**
         * 合并额外 params
         */
        $this->extendParams($params);
        $this->formatParams();

        return $this;
    }



    /**
     * 缓存处理方法
     */

    /**
     * 根据 create 参数，判断是否需要读取缓存
     * !! 如果需要，子类可以覆盖此方法
     * @return Bool true 表示直接使用缓存内容，false 表示忽略缓存，生成内容
     */
    protected function useCache()
    {
        $ps = $this->params;
        $create = $ps["create"] ?? false;

        //!! 不在根据 WEB_DEV 是否启用 来 决定是否使用缓存，仅通过 params["create"] 参数控制
        //首先检查当前是否处于 开发模式，如果处于开发模式，则忽略缓存，生成资源内容
        //if (Env::$isInsed === true) {
        //    if (Env::$current->dev === true) return false;
        //}

        return $create === false;
    }

    /**
     * 根据传入的参数，获取缓存文件的 路径，不论文件是否存在
     * 缓存文件 默认保存在 当前资源文件路径下的 资源同名文件夹下
     * !! 子类必须实现此方法
     * @return String 缓存文件的 路径
     */
    protected function getCachePath()
    {
        /**
         * 读取必要的 params 参数，生成 当前请求的 缓存文件名
         * 拼接 对应的 路径
         * 得到最终 需要的 缓存文件路径
         */

        $dir = dirname($this->real);
        $fn = $this->upi["filename"];   // foo.theme --> foo
        $cfn = "";  //!! 子类需要实现的 获取缓存文件名的 方法
        $ext = $this->ext;

        return $dir.DS.$fn.DS.$cfn.".".$ext;
    }

    /**
     * 读取缓存内容，如果缓存不存在 则返回 null
     * !! 如果需要，子类可以覆盖此方法
     * @return String|null
     */
    protected function getCacheContent()
    {
        //当前请求的文件信息
        $cf = $this->getCachePath();
        if (!file_exists($cf)) return null;

        //读取并返回 缓存文件内容
        $cnt = file_get_contents($cf);
        return $cnt;
    }

    /**
     * 将生成的 content 写入缓存文件
     * !! 如果需要，子类可以覆盖此方法
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



    /**
     * 生成 export 对应的 content 入口方法
     * 调用对应的 createExtContent 方法，并保存到 $this->content
     * !! 如果需要，子类可以覆盖此方法
     * @return $this
     */
    protected function createExportContent()
    {
        if (!isset(static::$stdParams["export"])) return $this;

        //要输出的 ext
        $ext = $this->exportExt();  //$this->params["export"] ?? $this->ext;
        $m = "create".Str::camel($ext, true)."Content";
        if (!method_exists($this, $m)) {
            //对应 方法不存在，则 不做处理，表示直接使用 当前 content
            //通常针对于 输出此文件实例的 实际文件类型

        } else {
            //调用方法
            $this->$m();
        }

        return $this;
    }

    /**
     * 根据 export 参数，生成对应 ext 资源的内容 content
     * !! static::$exps 数组中的所有指定支持输出的类型，都应定义对应的 createExtContent 方法
     * @return $this
     */
    //例如：protected function createCssContent() {}



    /**
     * 工具方法
     */

    /**
     * 使用 stdParams 标准参数 格式化 params 参数
     * !! 子类可以覆盖此方法
     * @return $this
     */
    protected function formatParams()
    {
        $ps = $this->params;
        if (!Is::nemarr($ps)) $ps = [];
        $ps = Arr::extend(static::$stdParams, $ps);

        //export
        if (isset(static::$stdParams["export"])) {
            if (!isset($ps["export"]) || !Is::nemstr($ps["export"])) {
                $ps["export"] = $this->ext;
            } else {
                $ext = $ps["export"];
                //!! 如果 $exps 包含 * 字符，表示支持任意类型
                if (!(Mime::support($ext) && (in_array($ext, static::$exps) || in_array("*", static::$exps)))) {
                    //指定要输出的 export 类型 不正确，报错
                    throw new SrcException("要输出的 $ext 类型不支持，或者不是有效的文件类型", "resource/getcontent");
                }
            }
        }

        //根据要输出的 文件类型，修改 ext|mime 
        if (isset(static::$stdParams["export"])) {
            $ext = $ps["export"];
            if ($ext !== $this->ext) {
                $this->ext = $ext;
                $this->mime = Mime::getMime($ext);
            }
        }

        //写回
        $this->params = $ps;
        return $this;
    }
    
    /**
     * 根据传入的 参数，获取 输出文件类型 export
     * @return String ext
     */
    public function exportExt()
    {
        $ps = $this->params;
        $ext = $ps["export"] ?? null;
        $exps = static::$exps;

        if (!Is::nemstr($ext)) $ext = $exps[0];
        if (!in_array(strtolower($ext), $exps)) $ext = $exps[0];

        return strtolower($ext);
    }

    /**
     * 针对本地资源，或此资源外部访问 url 的 前缀 urlpre
     * 即 通过 urlpre/[$this->name] 可以直接访问到此资源
     * !! 覆盖 Resource 父类方法，实现 ParsablePlain 类型资源的 外部访问 url 前缀的生成方法
     * !! 此类型资源在 Src 模块中 拥有独立的 响应方法 theme|icon|lib 等
     * @return String|null
     */
    public function getLocalResUrlPrefix()
    {
        /**
         * 首先调用父类方法，将生成不包含 当前资源名的 路径
         * 例如 有本地资源：    /data/ms/app/foo_app/assets/theme/spf.theme
         * 生成的 url 前缀为：  https://domain/foo_app/src/theme
         */
        $urlpre = parent::getLocalResUrlPrefix();
        if (!Is::nemstr($urlpre)) return null;

        //获取当前资源的 名称
        $resn = $this->getLocalResName();

        /**
         * 此类型资源的 外部访问 url 前缀，应包含 资源名称，即：
         * https://domain/foo_app/src/theme/spf
         */
        return $urlpre."/".trim($resn, "/");

    }

    /**
     * 获取真实的 filePath 当前库指定要 保存到的 特殊路径
     * 根据 static::$filePath 设置值 来决定
     * @return String|null
     */
    public function getSpecFilePath()
    {
        $fp = static::$filePath;
        //未指定
        if (!Is::nemstr($fp)) return null;
        // == ext
        if ($fp === "ext") {
            $real = $this->real;
            if (!Is::nemstr($real)) $real = $this->uri;
            return Resource::getExtFromPath($real);
        }
        // == foo/bar
        return $fp;
    }



    /**
     * 静态工具
     */

    /**
     * 当前类型的 复合资源，可以在 Src 模块中定义 特有的 响应方法
     * 例如：*.theme 资源 在 Src 模块中的 可以定义 特有的响应方法 themeView
     * 响应方法将调用 此处定义的 方法逻辑
     * !! 如果需要，子类应定义这个响应方法
     * !! 此方法将直接操作 Response 响应实例，返回响应结果
     * @param Array $args URI 路径数组
     * @return Mixed
     */
    public static function response(...$args)
    {
        //子类实现
        //...

        return $this;
    }

    /**
     * 检查某个路径 是否真实存在的文件路径，
     * 如果是，则直接实例化为文件资源实例，并输出
     * 否则，返回 false
     * @param String $path 资源路径
     * @return Resource|false
     */
    final protected static function getLocalResource($path)
    {
        $lf = Resource::findLocal($path);
        if (!file_exists($lf)) return false;

        //如果此文件真实存在，直接创建资源实例 并返回
        $res = Resource::create($lf, [
            //min
            "min" => strpos($path, ".min.") !== false,
        ]);
        if (!$res instanceof Resource) return false;
        //将 Response 输出类型 改为 src
        Response::insSetType("src");
        //输出资源
        return $res;
    }
}