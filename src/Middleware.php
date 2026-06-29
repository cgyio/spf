<?php
/**
 * 框架核心类
 * 中间件类 基类，抽象类
 * 
 * 可以同时实例化多个 中间件子类（每个子类都是单例模式）
 * 所有启用的 中间件，在实例化后，将立即自动执行 handle 核心方法，处理 Request|Response 实例，
 * 根据执行结果，决定是否触发 终止响应 的操作
 */

namespace Spf;

use Spf\exception\CoreException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

use Spf\traits\CoreInsGetter;

abstract class Middleware extends Core 
{
    //快速获取核心类
    use CoreInsGetter;

    /**
     * 单例模式
     * !! 覆盖父类，具体模块子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = true;

    /**
     * !! 手否手动执行中间件的 标记
     * 如果是手动执行，则不会自动调用 exit 方法，而是返回 中间件 handle 的结果 true|false
     */
    public static $manual = false;
    //手动执行中间件的 handle 结果 true|false
    public static $manualHandleResult = null;

    /**
     * 标准的 中间件列表 数组格式
     */
    public static $stdMids = [
        /*
        "in" => [],
        "out" => [],

        "中间件类名路径 middleware/in/foo_bar" => [ 中间件的配置参数 ... ],
        ...
        */
    ];
    //中间件 类型
    public static $types = [
        "in",   //入站中间件
        "out",  //出站中间件
    ];



    /**
     * 获取 中间件类 对应的 config 配置类 类全称
     * !! 覆盖父类
     * @return String 类全称
     */
    protected function getConfigCls()
    {
        //中间件实例化时 App 应用实例必须已创建
        if (App::$isInsed !== true) return null;
        $appk = App::$current::clsk();

        //当前中间件的 类名 FooBar 形式
        $clsn = static::clsn();
        //当前中间件的 路径名 foo_bar
        $clsk = static::clsk();
        //当前中间件的 类型 in|out
        $mitp = static::typeIs();
        if (!Is::nemstr($mitp)) {
            //此中间件无法判断 in|out
            throw new CoreException("无法判断中间件 $clsn 的类型", "initialize/config");
        }
        //中间件配置类 类名 MiddlewareFooBarConfig
        $cfgn = "Middleware".Str::camel($mitp, true).$clsn."Config";

        //查找 参数配置类的 类全称
        $cfgcls = null;
        //优先在 当前应用路径下 查找对应的 中间件配置类
        $cfgcls = Cls::find("app/$appk/middleware/config/$cfgn");
        //如果当前应用下不存在 此中间件的配置参数
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //根据 此中间件所属的 App | Module 查找配置文件
            $bto = static::belongTo();
            if (!Is::nemaso($bto)) {
                //此中间件不属于任何 App | Module 表示这是框架默认中间件
                //在 框架默认的路径下查找
                $cfgcls = Cls::find("middleware/config/$cfgn", "Spf\\");
            } else {
                $mappk = $bto["app"];
                $mmodk = $bto["module"];
                if (Is::nemstr($mappk) && Is::nemstr($mmodk)) {
                    //此中间件被定义在 某个应用内部的模块中
                    $cfgcls = Cls::find("app/$mappk/module/$mmodk/middleware/config/$cfgn");
                } else if (Is::nemstr($mappk)) {
                    //此中间件定义在其他 App 下
                    $cfgcls = Cls::find("app/$mappk/middleware/config/$cfgn");
                } else {
                    //此中间件被定义在 某个框架模块下 或 某个网站模块下
                    $cfgcls = Cls::find("module/$mmodk/middleware/config/$cfgn");
                }
            }
        }
        //如果  仍未找到 中间件的配置参数
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //在 框架默认的路径下查找
            $cfgcls = Cls::find("middleware/config/$cfgn", "Spf\\");
        }
        //使用 默认配置
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //默认路径下，也没有此中间件的 配置类，则使用 MiddlewareConfig 类，此类一定存在
            $cfgcls = Cls::find("config/MiddlewareConfig", "Spf\\");
        }
        if (empty($cfgcls) || !class_exists($cfgcls)) {
            //未找到配置类，报错
            throw new CoreException("未找到 $cfgn 配置类", "initialize/config");
        }
        return $cfgcls;
    }
    
    /**
     * 此中间件类自有的 init 方法，执行以下操作：
     *  0   执行 中间件的 核心方法 handle
     *  1   根据返回的 Bool 决定是否 终止响应
     * !! Core 子类必须实现的，Middleware 子类不要覆盖
     * @return $this
     */
    final public function initialize()
    {
        //中间件执行时，App 应用必须已经实例化
        if (App::$isInsed !== true) {
            throw new CoreException("中间件初始化时，应用实例还未创建", "initialize/init");
        }

        //中间件类名
        $midn = $this::clsn();

        //确认 只能在开发环境中启用的 中间件，不会在生产环境中被启用
        if (Env::$current->dev !== true && $this->config->dev === true) {
            throw new CoreException("中间件 $midn 不能在当前环境下启用", "initialize/init");
        }
        
        // 0 执行 handle
        $res = $this->handle();
        if (static::$manual===true) {
            //如果是手动执行此中间件，直接返回 handle 结果
            static::$manualHandleResult = $res;
            return $this;
        }

        // 1 触发 终止响应
        if ($res===false) $this->exit();

        return $this;
    }

    /**
     * 中间件的 核心方法，执行 入站|出站 过滤操作
     * 执行中间件逻辑，处理 Request|Response 实例，返回 是否过滤通过 的标记
     * !! 子类必须实现
     * @return Bool 当 此方法返回 false 时，将触发 中间件的 exit 终止响应 动作
     */
    abstract public function handle();

    /**
     * 中间件过滤方法 返回了 false 需要终止响应，将执行此方法
     * !! 子类可覆盖此方法
     * @return void
     */
    protected function exit()
    {

        exit;
    }



    /**
     * !! 外部入口方法
     * 依次执行 入站|出站 中间件，实例化，执行 handle 方法
     * !! 中间件执行时 App 应用必须已经实例化
     * @param String $type 入站|出站 类型 默认 in
     * @param Bool $return 是否返回过滤结果，而不是 终止响应，默认 false
     * @return void
     */
    public static function process($type="in", $return=false)
    {
        if (App::$isInsed !== true) return;
        //获取 当前应用 关联的 中间件列表
        $midcs = App::$current->config->middleware;
        //var_dump($midcs);
        $mids = $midcs[$type] ?? [];
        if (!Is::nemarr($mids)) return;

        //if ($type==="out") var_dump($mids);
        //return;

        //按顺序 执行 中间件实例化，执行 handle 方法
        foreach ($mids as $midk) {
            //中间件 配置参数
            $midc = $midcs[$midk] ?? [];

            //获取 中间件类全称
            $midcls = static::has($midk);
            //如果不存在此类
            if (!class_exists($midcls)) continue;

            //如果是 返回过滤结果
            if ($return) {
                //手动标记
                $midcls::$manual = true;
                $midcls::$manualHandleResult = null;
            }

            //实例化 此中间件，将自动执行 handle
            $mido = Middleware::current($midc, $midcls);

            //如果没有终止响应，则 释放此中间件实例
            unset($mido);

            //如果是 返回过滤结果
            if ($return) {
                //记录过滤结果
                $res = $midcls::$manualHandleResult;

                //取消手动标记
                $midcls::$manual = true;
                $midcls::$manualHandleResult = null;

                //如果是 false 立即返回，不继续执行后续 中间件
                if ($res!==true) return false;
            }
        }

        //如果是 返回过滤结果
        if ($return) return true;

    }

    /**
     * !! 外部入口
     * 手动执行 中间件过滤，实例化， 执行 handle 方法，返回 handle 结果
     * @param String $midcls 中间件类全称  或 Cls::find 可识别的 类路径
     * @return Bool 
     */
    public static function manualProcess($midcls)
    {
        //获取 中间件类全称
        $midcls = static::has($midk);
        //如果不存在此类  返回 true 表示不做控制
        if (!class_exists($midcls)) return true;

        //手动标记
        $midcls::$manual = true;
        $midcls::$manualHandleResult = null;
        
        //实例化 此中间件，将自动执行 handle
        $mido = Middleware::current([], $midcls);
        unset($mido);

        //读取 handle 结果
        $res = $midcls::$manualHandleResult;

        //清除 标记和结果
        $midcls::$manual = false;
        $midcls::$manualHandleResult = null;

        //返回 结果
        return $res;
    }



    /**
     * 静态工具
     */

    /**
     * 判断传入的 中间件类 是否存在  可以传入 类全称 或 类路径，可以省略 middleware/ 前缀
     * @param String $midcls 中间件类全称  或 Cls::find 可识别的 类路径
     * @return String|false 存在则返回 类全称 否则返回 false
     */
    public static function has($midk) 
    {
        if (!Is::nemstr($midk)) return false;

        //中间件类前缀
        $pre = "middleware/";
        $prelen = strlen($pre);

        //先检查一次
        $midcls = Cls::find($midk);
        if (!class_exists($midcls) && substr($midk, 0, $prelen)!==$pre) {
            //如果没找到中间件类，自动补全 类名路径的 middleware/ 前缀
            $midcls = Cls::find($pre.$midk);
        }

        if (!class_exists($midcls)) return false;
        return $midcls;
    }

    /**
     * 输出 标准的 中间件列表 数组格式
     * @param Array $mids 要格式化的 中间件数组
     * @return Array 标准的 中间件数组格式
     */
    public static function getStdMids($mids=[])
    {
        //返回 空值
        if (!Is::nemarr($mids) || !Is::associate($mids)) {
            if (Is::nemarr(self::$stdMids)) return Arr::copy(self::$stdMids);
            //生成
            $types = self::$types;
            $std = [];
            foreach ($types as $type) {
                $std[$type] = [];
            }
            //缓存
            self::$stdMids = $std;
            return $std;
        }

        //格式化输入的 中间件数组
        $std = self::getStdMids();
        foreach ($mids as $k => $v) {
            if (in_array($k, self::$types)) {
                if (Is::indexed($v)) array_push($std[$k], ...$v);
                continue;
            }
            if (Is::nemarr($v) && Is::associate($v)) {
                if (isset($std[$k])) {
                    $std[$k] = Arr::extend($std[$k], $v);
                } else {
                    $std[$k] = $v;
                }
            }
        }
        return $std;
    }

    /**
     * 中间件 列表合并，$new 覆盖 $old 严格按顺序 覆盖
     * @param Array $old 原中间件列表
     * @param Array $new 新中间件列表
     * @return Array 合并后的，严格按顺序执行的 中间件列表，标准 中间件数组格式
     */
    public static function extend($old=[], $new=[])
    {
        if (!Is::nemarr($old) && !Is::nemarr($new)) return self::getStdMids();
        if (!Is::nemarr($old) || !Is::associate($old)) return self::getStdMids($new);
        if (!Is::nemarr($new) || !Is::associate($new)) return self::getStdMids($old);

        //格式化
        $old = self::getStdMids($old);
        $new = self::getStdMids($new);

        //覆盖
        foreach ($new as $k => $v) {
            if (in_array($k, self::$types)) {
                //针对 in | out 项
                if (Is::indexed($v)) {
                    for ($i=0;$i<count($v);$i++) {
                        $vi = $v[$i];
                        if (!Is::nemstr($vi)) continue;
                        if (substr($vi, 0, 10)==="__delete__") {
                            $vicls = substr($vi, 10);
                            if (in_array($vicls, $old[$k])) {
                                //遇到 __delete__ 标记 则删除
                                array_splice($old[$k], array_search($vicls, $old[$k]), 1);
                                continue;
                            }
                        }
                        //push
                        if (!in_array($vi, $old[$k])) {
                            $old[$k][] = $vi;
                        }
                    }
                }
                //从 new 中删除
                unset($new[$k]);
                continue;
            }
        }
        //使用 Arr::extend 合并 除了 in|out 的剩余项
        $old = Arr::extend($old, $new);
        //返回
        return $old;
    }

    /**
     * 判断当前 中间件类的 类型 in|out
     * @return String|null
     */
    public static function typeIs()
    {
        $clsp = strtolower(static::class);
        if (strpos($clsp, "\\in\\")!==false) return "in";
        if (strpos($clsp, "\\out\\")!==false) return "out";
        return null;
    }

    /**
     * 判断当前中间件 是否在某个 App 或 Module 下被定义
     * 返回 所属 App 或 Module 的 名称 foo_bar
     * @return Array|null
     *  [
     *      "app" => null,
     *      "module" => "module_name"
     *  ]
     * !! 同时属于 App 和 Module 表示这个中间件被定义在 某个应用内部的 模块内
     */
    public static function belongTo()
    {
        $rtn = [
            "app" => null,
            "module" => null
        ];

        $clsp = strtolower(static::class);
        $ks = array_merge([], array_keys($rtn));
        foreach ($ks as $k) {
            if (strpos($clsp, "\\".$k."\\")===false) continue;
            $clspa = explode("\\".$k."\\", $clsp);
            $clspa = explode("\\", $clspa[1]);
            if (!Is::nemstr($clspa[0])) continue;
            $rtn[$k] = $clspa[0];
        }
        
        $emp = true;
        foreach ($ks as $k) {
            $emp = $emp && is_null($rtn[$k]);
        }
        if ($emp===true) return null;

        return $rtn;
    }
}