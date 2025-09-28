<?php
/**
 * Resource 资源处理类 资源处理中间件 基类
 * 对 目标资源实例 进行 管道式的 中间件处理，直至最终输出资源内容
 */

namespace Spf\module\src;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Dsl;
use Spf\util\Path;
use Spf\util\Conv;

class ResourceMiddleware 
{
    /**
     * 定义此中间件的 标准参数 控制具体的处理方法
     * 中间件运行时，传入的参数 将与此合并
     * !! 子类必须覆盖此参数
     */
    protected static $stdParams = [
        //...
    ];

    //此中间件 是否在 目标资源实例中 缓存其实例
    public static $cache = false;
    //设置 当前中间件实例 在资源实例中的 属性名，不指定则自动生成 NS\middleware\FooBar  -->  foo_bar
    public static $cacheProperty = "";

    /**
     * 要处理的 目标资源实例
     */
    public $resource = null;

    //记录本次 中间件处理的 处理参数
    public $params = [];



    /**
     * 构造
     * @param Resource $res
     * @param Array $params 本次 中间件处理的 参数
     * @return void
     */
    protected function __construct($res, $params=[])
    {
        if (!$res instanceof Resource) return null;
        $this->resource = $res;

        if (!Is::nemarr($params)) $params = [];
        $this->params = Arr::extend(static::$stdParams, $params);
    }

    /**
     * 此中间件的核心处理方法
     * 处理并修改 资源实例数据
     * !! 子类必须实现此方法
     * @return Bool 
     */
    public function handle()
    {

        return true;
    }



    /**
     * 工具方法
     */

    /**
     * 缓存此中间件实例 到 资源实例中
     * @return $this
     */
    public function createCache()
    {
        if (static::$cache !== true) return $this;
        //获取|创建 中间件实例缓存 在 资源实例中的 属性名
        $cp = static::$cacheProperty;
        if (!Is::nemstr($cp)) {
            $cp = static::getCacheProperty($this->resource);
            static::$cacheProperty = $cp;
        }
        //缓存
        $this->resource->$cp = $this;
        return $this;
    }



    /**
     * 静态方法
     */

    /**
     * 根据是否 缓存 执行 实例化中间件  或  从目标资源实例中读取已缓存的中间件实例  然后执行 handle
     * @param Resource $res
     * @return Bool handle 方法的执行结果
     */
    protected static function execHandle($res, $params=[])
    {
        //中间件实例 
        $mido = null;

        if (static::$cache === true) {
            //尝试读取缓存的 中间件实例
            $cp = static::$cacheProperty;
            if (Is::nemstr($cp) && isset($res->$cp)) {
                $mido = $res->$cp;
            }
        }

        //未设置缓存 或 未读取到缓存的实例 则创建 中间件实例
        $mido = new static($res, $params);

        //执行 handle 方法
        $rtn = $mido->handle();

        if (static::$cache === true) {
            //缓存实例
            $mido->createCache();
            return $rtn;
        } else {
            //不需要缓存 释放实例
            unset($mido);
            return $rtn;
        }
    }

    /**
     * 启动中间件处理  入口方法
     * @param Resource $res 要处理的 目标资源实例
     * @param String $midType 资源中间件类型 create|export 指定启动 哪个阶段的 中间件处理序列
     * @param Array $params 执行此阶段中间件处理序列时 可额外传入 处理参数，将注入每个中间件的 实例化参数中
     * @return Resource $res
     */
    final public static function process($res, $midType="create", $params=[])
    {
        if (!$res instanceof Resource) return $res;
        if (!Is::nemarr($params)) $params = [];

        //获取当前 资源实例的 中间件参数
        $mids = $res->middleware[$midType] ?? null;
        if (!Is::nemarr($mids)) return $res;

        //严格按顺序执行中间件处理
        foreach ($mids as $cls => $mps) {
            //处理 "FooBar [#|?]foo_bar" 形式的 中间件名称调用
            if (strpos($cls, " ")!==false) {
                $cls = preg_replace("/\s+/", " ", $cls);
                $clsarr = explode(" ", $cls);
                $cls = $clsarr[0];
                $cmd = implode(" ", array_slice($clsarr, 1));
            }

            //注入 params
            $ps = Arr::extend($mps, $params);

            //处理 条件中间件 FooBar ?... 形式
            if (isset($cmd) && Is::nemstr($cmd) && substr($cmd, 0,1) === "?") {
                //判断是否满足条件，满足条件 才能 执行中间件处理
                $need = static::optional($cmd, $res, $ps);
                if ($need !== true) continue;
            }

            //首先尝试查找资源中间件
            $clsp = static::cls($cls);
            if (Is::nemstr($clsp)) {
                //中到对应中间件的 类全称 开始调用中间件 处理资源实例
                //执行中间件的 handle 方法 可能是实例化一个新的中间件实例  或  使用已缓存的实例
                $rtn = $clsp::execHandle($res, $ps);
                //如果处理方法 返回 false 则终止后续处理
                if ($rtn === false) break;
                continue;
            }

            //未找到目标中间件 则尝试查找 目标资源实例中是否定义了对应的 stageFooBar 方法
            $stm = "stage".$cls;
            if (method_exists($res, $stm)) {
                //目标资源实例中 定义了对应的 stage 方法，调用此方法
                $rtn = $res->$stm($ps);
                //如果处理方法 返回 false 则终止后续处理
                if ($rtn === false) break;
                continue;
            }

            //不存在中间件 也不存在 stage 方法 报错
            throw new SrcException("未找到名称是 $cls 的资源中间件，或资源内部 stage 方法", "resource/getcontent");
        }

        return $res;
    }

    /**
     * 判断是否满足 中间件的执行条件，只有满足条件，才会执行 中间件处理
     * @param String $cmd 条件语句
     * @param Resource $res 目标资源实例
     * @param Array $params 额外传入的 处理参数
     * @return Bool
     */
    final public static function optional($cmd, $res, $params=[])
    {
        if (!$res instanceof Resource) return false;
        if (!Is::nemstr($cmd)) return false;

        //针对 FooBar ?func::FooBar #1 形式
        if (strpos($cmd, " #")!==false) $cmd = explode(" #", $cmd)[0];

        /**
         * 调用 Dsl 工具类，解析并执行 中间件执行条件语句
         * 条件语句形式 ?foo=bar&(nemstr(foo)|nemarr(SCOPE.bar))&in_array(SCOPE.jaz,tom) 解析为：
         *      $res->params["foo"] == "bar" && (
         *          Is::nemstr($res->params["foo"]) || Is::nemarr($res->bar)
         *      ) && in_array($res->jaz, $res->params["tom"])
         */
        return Dsl::invoke($cmd, $res, "params");
    }

    /**
     * 手动执行某个中间件的功能，处理指定的 资源实例
     * @param String $cls 中间件类名 或 类全称
     * @param Resource $res 要处理的资源实例
     * @param Array $params 可额外传入 处理参数
     * @return Resource $res
     */
    final public static function manual($cls, $res, $params=[])
    {
        //查找 资源中间件 类全称
        $clsp = static::cls($cls);
        if (!Is::nemstr($clsp)) {
            //未找到目标中间件，报错
            throw new SrcException("无法创建资源中间件 $cls 的实例", "resource/getcontent");
        }
        //注入 params
        if (!Is::nemarr($params)) $params = [];

        //执行中间件的 handle 方法 可能是实例化一个新的中间件实例  或  使用已缓存的实例
        $rtn = $clsp::execHandle($res, $params);

        return $res;
    }

    /**
     * 根据传入的 中间件 类名|类路径 查找对应的 资源中间件 类全称
     * @param String $cls
     * @return String 资源中间件 类全称
     */
    final public static function cls($cls)
    {
        if (!Is::nemstr($cls)) return null;
        //直接查找
        if (class_exists($cls)) return $cls;
        //通过 Cls::find
        $clsp = Cls::find($cls);
        if (class_exists($clsp)) return $clsp;
        //补齐类前缀
        $clsp = "module/src/resource/middleware/".trim($cls, "/");
        $clsp = Cls::find($clsp);
        if (class_exists($clsp)) return $clsp;
        return null;
    }

    /**
     * 根据中间件的 类全称，生成 资源实例中缓存的中间件实例的 属性名
     * NS\foo\bar\Mid  -->  foo_bar_mid
     * @param Resource $res 目标资源实例
     * @return String 
     */
    public static function getCacheProperty($res)
    {
        $cls = static::class;
        //rela
        $rela = Cls::rela($cls);
        $relarr = explode("/", $rela);
        $cpn = array_slice($relarr, -1)[0];
        //处理存在重复 属性名 的情况
        $cp = $cpn;
        while(isset($res->$cp)) {
            $cp = $cpn."_".Str::nonce(8, false);
        }
        return $cp;
    }
}