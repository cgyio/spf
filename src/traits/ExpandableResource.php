<?php
/**
 * SPF 框架 可复用类特征
 * 通用  可扩展底层资源类  特征 trait
 * 
 * 引用此 trait 的类，都具有下列特征：
 *      0   是 App 或 Module 底层的、必须依赖的 资源类，这些资源的基类都是抽象类，必须实现对应功能的 子类
 *      1   可以在 3个层级(应用级、网站级、框架级) 扩展这些资源，覆盖顺序：应用级 > 网站级 > 框架级
 *      2   在 App 或 Module 初始化阶段，需要收集这些 资源的各级子类，建立 子类名称 <--> 类全称 的映射数组
 *      3   收集到的 映射数组，将被保存到 此资源基类的 $collection[] 中，同时建立对应的 缓存文件
 * 
 * !! 引用了此 trait 的资源类，会在 App 以及 Module 的 initialize 方法中自动收集并缓存
 * !! App 和 Module 必须在其对应的配置参数中，定义下列参数：
 *      [
 *          !! # 指定需要使用的资源基类  类全称 或 可被 Cls::find 识别的 类路径
 *          "expandableResource" => [
 *              "module/orm/Driver",
 *              ...
 *          ],
 *      ]
 * !! App 还必须在其对应的配置参数中，定义下列参数：
 *      [
 *          !! # 是否启用 资源缓存
 *          "enableCache" => true,
 * 
 *          !! # 定义资源缓存文件路径  默认缓存在 runtime 路径
 *          "cache" => "runtime/app/%{APPK}%/expandable_resource.json",
 *      ]
 * 
 * !! 已收集到的 资源子类的  资源名 --> 资源子类类全称 映射数据，将被缓存到 当前 App 应用的 缓存路径下
 * 
 * 通常，这些资源类包括：
 *      Driver          Orm 模块支持的 数据库驱动
 *      Types           Orm 模块支持的 数据库字段类型
 *      Preparer        数据模型配置参数预处理类
 *      Parser          数据模型配置参数解析类
 *      ...
 */

namespace Spf\traits;

use Spf\Env;
use Spf\App;
use Spf\Module;
use Spf\exception\CoreException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Cache;

trait ExpandableResource 
{

    /**
     * !! 引用类不要覆盖
     * 在 App 或 Module 初始化阶段，收集此资源的 所有层级的 可用子类，保存到此 []
     */
    protected static $collection = [
        //"sub_class_name" => "资源子类的 类全称",
        //...
    ];

    /**
     * !! 引用类不要覆盖
     * 标记这是一个 ExpandableResource 资源类
     */
    public static $isExres = true;

    /**
     * !! 引用的类，必须定义
     * !! 引用类的子类，不要覆盖
     * 当前引用此 trait 的资源类 名称 foo_bar 形式
     * 例如：资源类 Types 可设置资源名称 types
     */
    //protected static $exresName = "";

    /**
     * !! 引用的类，必须定义
     * !! 引用类的子类，不要覆盖
     * 当前资源的 子资源类 class 文件 在各层级的保存路径
     * 例如：
     *      当前引用此 trait 的资源是 Orm 数据库模块的 字段类型 Types 资源基类，定义下列路径参数：
     *          $exresClassPath = [
     *              "module/orm",   # 框架级子类路径
     *              "db",           # 网站级子类路径
     *              "db",           # 应用级子类路径，如果不指定，与 网站级子类路径 一致
     *          ]
     *      则 依次在这些路径下，查找 Types 资源子类：
     *          应用级(请求应用 != base_app)        app/[app_name]/db/[expandableResourceName]/... 
     *          网站级(请求应用 == base_app)        db/[expandableResourceName]/... 
     *          框架级                              spf/module/orm/[expandableResourceName]/...
     * 
     *      如果仅定义 $exresClassPath = "foo/bar" 则 依次在这些路径下，查找 Types 资源子类：
     *          应用级(请求应用 != base_app)        app/[app_name]/foo/bar/[expandableResourceName]/... 
     *          网站级(请求应用 == base_app)        foo/bar/[expandableResourceName]/... 
     *          框架级                              spf/foo/bar/[expandableResourceName]/...
     */
    //protected static $exresClassPath = [ "框架级子类路径", "网站级子类路径", "应用级子类路径" ];

    /**
     * !! 引用的类，必须定义
     * !! 引用类的子类，不要覆盖
     * 标记此资源类已被收集过，不要重复收集
     */
    //public static $isCollected = false;



    /**
     * 当某个资源子类被 collect 收集时，执行一些初始化操作，例如：
     *      某个自定义的 字段类型，可以在此方法中，建立 针对所有 数据库类型的  字段类型映射
     * !! 引用的资源类或子类，根据需要，实现各自的操作
     * @return Bool
     */
    protected static function whenCollect()
    {
        //...
        return true;
    }



    /**
     * 在 App 以及 Module 初始化阶段，调用此方法，执行 collect 收集操作，创建映射，缓存
     * !! 引用的类，不要覆盖
     * !! 此方法 由 App 和 Module 在 initialize 方法中调用
     * @return Bool
     */
    final public static function collect()
    {
        //!! 首先尝试读取缓存
        $cd = self::readExresCache();
        if (Is::nemaso($cd)) {
            //获取到缓存，直接使用
            self::$collection = $cd;
            return true;
        }

        //!! 未获取到缓存内容，开始查找并收集 此资源的子类
        //当前资源类名
        $resn = Str::snake(static::$exresName, "_");
        //异常信息前缀
        $errpre = "在收集 ExpandableResource 资源类 $resn 时，";

        //按优先级 在这些路径下查找 资源子类
        $clsp = static::$exresClassPath;
        if (!Is::nemstr($clsp) && !Is::nemidx($clsp)) {
            //参数无效，报异常
            throw new CoreException($errpre."未指定有效的资源类保存路径", "initialize/init");
            return false;
        }
        $clsps = [];    //必须是：框架级，网站级，应用级 顺序指定
        if (Is::nemstr($clsp)) {
            $clsps = [ $clsp, $clsp, $clsp ];
        } else {
            for ($i=0;$i<3;$i++) {
                if (isset($clsp[$i])) {
                    $clsps[] = trim($clsp[$i], "/");
                } else {
                    $clsps[] = trim($clsp[$i-1], "/");
                }
            }
        }

        //!! 依次查找资源子类  覆盖顺序：应用级 > 网站级 > 框架级
        $collection = [];
        //  0   框架级 资源子类
        $clspi = $clsps[0];
        static::getExresSubClass(
            $collection,
            "spf/$clspi/$resn",
            "$clspi/$resn",
        );

        //  1   网站级 资源子类
        $clspi = $clsps[1];
        static::getExresSubClass($collection, "$clspi/$resn");

        //  2   应用级 资源子类
        //!! 此方法被调用时，App 应用一定已经实例化
        if (true!==($appk = App::isBaseApp())) {
            $clspi = $clsps[2];
            static::getExresSubClass($collection, "app/$appk/$clspi/$resn");
        }

        //!! 未收集到任何 资源子类，报异常，因为这是 App 或 Module 的底层依赖，必须存在
        if (!Is::nemarr($collection)) {
            throw new CoreException($errpre."未找到任何支持的 $resn 资源类", "initialize/init");
            return false;
        }

        //保存到 $collection
        self::$collection = $collection;

        //写入缓存
        return static::saveExresCache();

    }

    /**
     * 在 所有资源类 collect 完成后，对所有收集到的 资源子类，执行一次 whenCollect() 方法
     * !! 引用的类，不要覆盖
     * !! 此方法 由 App 和 Module 在 initialize 方法中调用
     * @return Bool
     */
    final public static function afterCollect()
    {
        $rtn = true;
        foreach (self::$collection as $clsk => $clsp) {
            //!! 对每个收集到的 资源子类 执行 whenCollect() 方法
            $rtni = $clsp::whenCollect();
            //标记 isCollected
            if ($rtni===true) static::$isCollected = true;
            //合并结果
            $rtn = $rtn && $rtni;
        }
        return $rtn;
    }



    /**
     * 工具方法
     * !! 引用的类，不要覆盖
     * !! 这些方法 只能通过 引用的 资源基类 调用
     */

    /**
     * 获取 由当前 App 定义的 资源缓存文件路径
     * @return String|null 未开启资源缓存，返回 null 
     */
    final protected static function getExresCachePath()
    {
        //!! 执行资源收集时，App 应用一定已经实例化
        $cp = App::$current->config->ctx("expandableResource/cache");
        if (!Is::nemstr($cp)) return null;
        return $cp;
    }

    /**
     * 读取 当前资源的 缓存数据
     * @param String $resn 读取指定资源类型的 缓存，  默认 "" 表示读取当前资源缓存，如果传入 null 返回所有资源缓存
     * @param Bool $force 不论 是否开发环境，是否开启缓存，都尝试读取  
     * @return Array|null
     */
    final protected static function readExresCache($resn="", $force=false)
    {
        if ($force===false) {
            //!! 如果是 开发环境，不使用 资源缓存
            if (Env::$current->dev===true) return null;
    
            //!! 执行资源收集时，App 应用一定已经实例化
            //如果未开启资源缓存
            if (App::$current->config->ctx("expandableResource/enableCache")!==true) return null;
        }

        //缓存文件路径
        $cp = static::getExresCachePath();
        if (!Is::nemstr($cp)) return null;
        
        //读取缓存
        $cacheRtPs = [
            //!! 资源子类集合 缓存永不过期，如需更新缓存，需要手动删除已有的缓存文件
            "expire" => 0,
        ];
        //如果开启强制获取缓存，则强行忽略 全局 Cache 工具的 enable 状态
        if ($force===true) $cacheRtPs["enable"] = true;
        $cd = Cache::runtimeExec(
            $cacheRtPs,
            //读取 配置缓存
            function() use ($cp) {
                //!! 读取的缓存数据，不含 缓存标记
                return Cache::read($cp, false);
            }
        );
        if (!Is::nemaso($cd)) return null;

        //返回所有资源的 缓存
        if (is_null($resn)) return $cd;

        //当前资源名
        $resn = $resn==="" ? static::$exresName : $resn;
        if (!Is::nemstr($resn)) return null;
        $resn = Str::snake($resn, "_"); // --> foo_bar
        if (!isset($cd[$resn])) return null;

        return !Is::nemaso($cd[$resn]) ? [] : $cd[$resn];
    }

    /**
     * 写入 当前资源的 缓存
     * @return Bool
     */
    final protected static function saveExresCache()
    {
        //!! 不论是否开启缓存，都会写入
        $collection = self::$collection;
        if (!Is::nemaso($collection)) return false;

        //缓存文件路径
        $cp = static::getExresCachePath();
        if (!Is::nemstr($cp)) return false;

        //当前资源名
        $resn = static::$exresName;
        if (!Is::nemstr($resn)) return false;
        $resn = Str::snake($resn, "_"); // --> foo_bar

        //强制读取 所有原有缓存内容
        $cd = static::readExresCache(null, true);
        if (!Is::nemaso($cd)) $cd = [];

        //改写
        $cd[$resn] = $collection;

        //写入
        return Cache::save($cp, $cd);
    }

    /**
     * 在某个 dir 路径下，查找 类文件  后缀名为 EXT_CLASS  返回  foo_bar => 类全称  形式的 映射数组
     * !! 仅查找 1层 目录
     * @param Array &$collection 已经收集到的 资源类 --> 类全称 映射数组
     * @param String $dir 路径，可被 Path::find() 识别
     * @param String $pre 类路径的 前缀，默认 "" 与 dir 一致
     * @return Array  类名称 <--> 类全称 映射数组
     */
    final protected static function getExresSubClass(&$collection, $dir, $pre="")
    {
        //当前资源类名
        $resn = static::$exresName;
        //转为 FooBar
        $resnup = Str::camel($resn, true);
        //长度
        $resnlen = strlen($resnup) * -1;
        //类文件后缀名 长度
        $extlen = strlen(EXT_CLASS) * -1;

        //查找结果
        $finds = [];

        //类路径前缀 默认使用 dir 路径
        if (!Is::nemstr($pre)) $pre = $dir;

        //查找路径下的 类文件
        $dir = Path::find($dir, Path::FIND_DIR);
        if (is_dir($dir)) {
            $dh = opendir($dir);
            while (false!==($fn = readdir($dh))) {
                if ($fn==="." || $fn===".." || is_dir($dir.DS.$fn)) continue;
                if (strtolower(substr($fn, $extlen))!==EXT_CLASS) continue;
                
                $clsn = substr($fn, 0, $extlen);    // FooBar
                //var_dump($clsn);
                //查找可能存在的 资源子类
                $clsp = Cls::find("$pre/$clsn");
                //var_dump($clsp);
                if (class_exists($clsp)) {
                    //获取子资源类的 名称
                    $subn = $clsp::subExresName();
                    //默认使用 资源子类名称 去除可能存在的后缀后  转为 foo_bar 的形式
                    if (!Is::nemstr($subn)) $subn = static::trimSubExresClassNameSuffix($clsn);
                    //var_dump($subn);

                    //保存到 $finds
                    $finds[$subn] = $clsp;
                }
            }
            closedir($dh);
        }

        //找到的子类映射，合并到 $collection
        if (Is::nemaso($finds)) $collection = Arr::extend($collection, $finds);

        return $collection;
    }

    /**
     * forDev：外部查看 $collection[]
     * !! 如果需要，引用的资源类可以覆盖，资源子类不要覆盖
     * @return Array
     */
    public static function all()
    {
        return self::$collection;
    }

    /**
     * 判断 当前的资源类 是否包含传入的 子类
     * !! 如果需要，引用的资源类可以覆盖
     * @param String $clsk 要检查是否存在的 子类名 foo_bar
     * @return false|String 存在则返回 类全称，不存在 返回 false
     */
    public static function support($clsk)
    {
        if (!Is::nemstr($clsk)) return false;
        $clsk = Str::snake($clsk, "_");
        $clsp = self::$collection[$clsk] ?? null;
        if (class_exists($clsp)) return $clsp;
        return false;
    }

    /**
     * 去除 资源子类的 类名称中 可能存在的 资源名后缀
     * 例如：
     *      Types 资源类的子类 Switch 是系统关键字，无法直接作为类名，因此改为 SwitchTypes
     *      在收集资源子类时，需要去除其 -Types 后缀，得到实际的 资源子类名称 Switch
     * !! 返回的子类名称 一定是 foo_bar 形式
     * !! 引用的资源类、资源子类，不要覆盖
     */
    final public static function trimSubExresClassNameSuffix($clsn)
    {
        if (!Is::nemstr($clsn)) return null;
        //资源名称
        $resn = static::$exresName;
        //FooBar
        $resnup = Str::camel($resn, true);
        //len
        $reslen = strlen($resnup) * -1;

        if (substr($clsn, $reslen)===$resnup) {
            $clsn = substr($clsn, 0, $reslen);
        }

        //foo_bar
        return Str::snake($clsn, "_");
    }

    /**
     * 获取 资源子类的 类名 foo_bar 形式
     * 所有资源类，都必须定义一个 同名(单数形式)静态属性值  用来标示 资源子类的名称
     * 例如：
     *      Types 资源类的 子类 Json  必须定义 静态属性：
     *          Json::$type = "json"
     *      Driver 资源类的 子类 Mysql  必须定义 静态属性：
     *          Mysql::$driver = "mysql"
     * !! 属性值必须是 资源子类名 foo_bar 形式
     * !! 引用的资源类、资源子类，不要覆盖
     */
    final public static function subExresName()
    {
        //资源类名
        $resn = Str::snake(static::$exresName, "_");
        //同名单数形式
        $ressn = substr($resn, -1)==="s" ? substr($resn, 0, -1) : $resn;
        //查询静态属性值
        $subresn = static::$$ressn ?? null;
        if (!Is::nemstr($subresn)) return null;
        return Str::snake($subresn, "_");
    }

}