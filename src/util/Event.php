<?php
/**
 * 框架 特殊工具类
 * 框架事件处理类 订阅/触发/处理
 * 
 * 事件订阅者 可以是：类(String类全称) | 类实例(Object) | NULL(使用匿名函数作为handle)
 * 事件处理函数 可以是： 静态(类)|实例 方法(String方法名) | 匿名函数
 * !! 当使用匿名函数作为 事件处理方法时，订阅者为 NULL 但与其他 NULL 订阅者不相同，因此建议：使用匿名函数处理事件时，尽量使用一次性订阅
 * 
 * !! 事件处理方法 第一个参数 必须是 triggerBy 事件触发者
 * 
 * 订阅事件：
 *      Event::listen($event, $handler, $once=false)
 * 触发事件：
 *      Event::trigger($event, $triggerBy, ...$args)
 * 取消订阅：
 *      Event::remove($event, $handler)
 * 
 * 将 类|类实例 内部的 handle***Event() 方法批量创建 事件订阅：
 *      Event::regist(类全称|类实例)
 */

namespace Spf\util;

use Spf\Core;

class Event extends SpecialUtil 
{
    /**
     * 此工具 在启动参数中的 参数定义
     *  [
     *      "util" => [
     *          "util_name" => [
     *              # 如需开启某个 特殊工具，设为 true
     *              "enable" => true|false, 是否启用
     *              ... 其他参数
     *          ],
     *      ]
     *  ]
     * !! 覆盖父类静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = false;
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];
    
    /**
     * 缓存已有订阅的 Event 事件实例
     * 键名为 foo_bar 形式
     */
    public static $events = [
        /*
        "app_created" => Event 事件实例,
        ...
        */
    ];

    /**
     * 订阅事件
     * !! 此方法 会创建对应的 Event 事件实例，并缓存到 Event::$events 数组中
     * 将根据传入的 Callable 事件处理方法，自动解析 事件订阅者
     * @param String $event 事件名称 foo_bar 形式
     * @param Callable $handler 事件处理方法，可以是：[ 类全称|类实例, 方法名 ] 或 匿名函数
     * @param Bool $once 是否一次性订阅 默认不指定，从 handler 方法信息中获取，未获取到 则为 false
     * @return Bool 订阅是否成功
     */
    public static function listen($event, $handler, $once=null)
    {
        //框架必须开启了 事件处理
        if (self::$enable !== true) return false;

        //事件名称 必须 foo_bar 形式
        if (!Is::nemstr($event)) return false;
        $event = Str::snake($event,"_");

        //事件处理方法 必须是 callable
        if (!is_callable($handler)) return false;

        //解析 handler
        $evi = self::parseHandler($handler);
        if ($evi === false) return false;
        //取得 事件订阅信息，作为 事件的实例化参数
        $listener = $evi["listener"];
        $handler = $evi["handler"];
        $once = is_bool($once) ? $once : $evi["once"];

        /**
         * 开始创建 Event 事件实例
         */
        if (isset(self::$events[$event])) {
            //实例已被创建
            $evo = self::$events[$event];
        } else {
            //创建实例
            $evo = new Event($event);
            self::$events[$event] = $evo;
        }

        //修改 $event->listeners
        return $evo->addListener($listener, $handler, $once);
    }

    /**
     * 触发事件
     * @param String $event 事件名称 foo_bar 形式
     * @param String|Object|null $triggerBy 事件的触发者，可以是：类(String类全称) | 类实例(Object) | NULL
     * @param Array $args 事件处理函数的 额外参数
     * @return void
     */
    public static function trigger($event, $triggerBy=null, ...$args)
    {
        //框架必须开启了 事件处理
        if (self::$enable !== true) return;

        //事件名称 必须 foo_bar 形式
        if (!Is::nemstr($event)) return;
        $event = Str::snake($event,"_");

        //事件实例 还未创建 直接返回
        if (!isset(self::$events[$event])) return;

        //检查参数合法性
        if (!Is::nemstr($triggerBy) && !is_object($triggerBy) && !is_null($triggerBy)) {
            return;
        }

        //调用 事件实例的 handle 方法
        $evo = self::$events[$event];
        return $evo->handle($triggerBy, ...$args);
    }

    /**
     * 取消订阅事件
     * @param String $event 事件名称 foo_bar 形式
     * @param Callable $handler 事件处理方法，可以是：[ 类全称|类实例, 方法名 ] 或 匿名函数
     * @return Bool 取消订阅是否成功
     */
    public static function remove($event, $handler)
    {
        //框架必须开启了 事件处理
        if (self::$enable !== true) return false;
        
        //事件名称 必须 foo_bar 形式
        if (!Is::nemstr($event)) return false;
        $event = Str::snake($event,"_");

        //事件实例 还未创建 直接返回 true
        if (!isset(self::$events[$event])) return true;

        //根据 handler 解析 listener 和 handler
        $evi = self::parseHandler($handler);
        if ($evi === false) return false;
        $listener = $evi["listener"];

        //使用 事件实例的 delListener 方法
        $evo = self::$events[$event];
        return $evo->delListener($listener);
    }

    /**
     * 销毁事件实例
     * @param String $event 事件名称
     * @return Bool
     */
    public static function destroy($event)
    {
        //框架必须开启了 事件处理
        if (self::$enable !== true) return false;
        
        //事件名称 必须 foo_bar 形式
        if (!Is::nemstr($event)) return false;
        $event = Str::snake($event,"_");
        unset(self::$events[$event]);
        return true;
    }

    /**
     * 将 类|类实例 内部的 handle***Event() 方法批量创建 事件订阅
     * @param String|Object $cls 类|类实例
     * @return Bool
     */
    public static function regist($cls)
    {
        //框架必须开启了 事件处理
        if (self::$enable !== true) return false;
        
        if (!Is::nemstr($cls) && !is_object($cls)) return false;
        if (Is::nemstr($cls) && !class_exists($cls)) return false;
        $iscls = Is::nemstr($cls);
        $clsn = $iscls ? $cls : get_class($cls);
        $filter = $iscls ? "public,&static" : "public,&!static";

        //在类定义中 查找 handleFooBarEvent 类型的 方法
        $ms = Cls::methods($clsn, $filter, function($mi) use ($cls) {
            $mn = $mi->getName();
            //方法名必须是 handleFooBarEvent 形式
            if (substr($mn, 0, 6)!=="handle" && substr($mn, -5)!=="Event") return false;
            $doc = $mi->getDocComment();
            //定义 事件处理方法，必须在 注释中 含有 * eventHandler 字符
            if (strpos($doc, "* event-handler")===false && strpos($doc, "* eventHandler")===false) return false;

            //解析 方法信息
            $conf = Cls::parseComment($doc);
            //方法信息中 可能定义了 once 参数
            $once = $conf["once"] ?? false;

            //事件名 handleFooBarEvent --> foo_bar
            $evn = substr($mn, 6, -5);
            $evn = Str::snake($evn, "_");

            //订阅事件
            return Event::listen($evn, [$cls, $mn], $once);
        });

        return !empty($ms);
    }

    /**
     * 根据给定的 handler，解析 listener 和 handler 的方法信息 如：once
     * @param Callable $handler 事件处理方法
     * @return Array|false 解析得到的 事件订阅者 和 事件处理函数 的相关信息
     *  [
     *      "listener"  => 类(String) | 类实例(Object) | NULL
     *      "handler" => [ 类全称|类实例, 方法名 ] | 匿名函数
     *      "once" => 是否一次性订阅，从类文件解析方法注释 得到，默认 false
     *  ]
     */
    protected static function parseHandler($handler)
    {
        //框架必须开启了 事件处理
        if (self::$enable !== true) return false;
        
        //事件处理方法 必须是 callable
        if (!is_callable($handler)) return false;

        //返回的 事件订阅信息数据
        $evi = [
            "listener" => null,
            "handler" => $handler,
            "once" => false
        ];

        if ($handler instanceof \Closure) {
            //事件处理方法 是 匿名函数，直接返回默认信息
            return $evi;
        }

        //事件处理方法 被定义为 [ 类名|类实例, 方法名 ] 形式
        if (is_array($handler) && count($handler)>=2) {
            //订阅者 类|类实例
            $listener = $handler[0];
            //订阅者 是类
            $iscls = Is::nemstr($listener);
            //事件处理方法名
            $method = $handler[1];
            //订阅者 类全称
            $lclsn = $iscls ? $listener : get_class($listener);
            //筛选 类方法时的 filter
            $filter = $iscls ? "public,&static" : "public,&!static";
            
            //在 订阅者类定义中查找 事件处理方法，解析注释，获取方法信息，主要是 once 信息
            $minfo = [];
            $has = Cls::methods($lclsn, $filter, function($mi) use ($method, &$minfo) {
                if ($mi->getName() !== $method) return false;
                $doc = $mi->getDocComment();
                //定义 事件处理方法，必须在 注释中 含有 * eventHandler 字符
                if (strpos($doc, "* event-handler")===false && strpos($doc, "* eventHandler")===false) return false;
                //解析 方法信息
                $conf = Cls::parseComment($doc);
                //方法信息中 可能定义了 once 参数
                $once = $conf["once"] ?? false;
                //储存到 $minfo
                $minfo["once"] = $once;
                return true;
            });
            //未找到 handler 方法
            if (empty($has) || empty($minfo)) return false;
            //返回找到的 事件订阅信息
            $evi["listener"] = $listener;
            //$evi["handler"] = $handler;
            $evi["once"] = $minfo["once"] ?? false;

            return $evi;
        }
        
        return false;
    }



    /**
     * Event 事件实例 属性|方法
     */
    //事件名称 foo_bar
    public $name = "";
    /**
     * 此事件的 订阅者列表
     * !! 此列表 必须通过 $event->[add|del]Listener() 方法进行 增减
     * 一旦 此列表为空，此事件的实例 将被销毁
     */
    protected $listeners = [
        /*
        "类全称 | 类全称:spl_object_hash(类实例) | NULL:Str::nonce(16)" => [
            "listener" => 事件订阅者，可以是：类(String类全称) | 类实例(Object) | NULL(使用匿名函数作为handle)
            "handler" => [ 类全称|类实例, 方法名 ] | 匿名函数
            "once" => 指定此订阅是否一次性订阅，默认 false
        ],
        ...
        */
    ];

    /**
     * 构造
     * @param String $event 事件名称 foo_bar
     * @return void
     */
    public function __construct($event) 
    {
        if (!Is::nemstr($event)) return null;
        $this->name = Str::snake($event,"_");
    }

    /**
     * 修改此事件实例的 订阅者列表
     * 新增 订阅者
     * !! 此方法参数 已在 Event::listen() 方法中处理过，此处不再检查合法性
     * @param Object $listener 事件订阅者 类(String) | 类实例(Object) | NULL
     * @param Callable $handler 事件处理方法 [ 类全称|类实例, 方法名 ] | 匿名函数
     * @param Bool $once 是否一次性订阅 默认 false
     * @return Bool
     */
    public function addListener($listener, $handler, $once=false)
    {
        //创建 在 listeners 数组中的 键名
        $lk = $this->getListenerKey($listener);
        if (is_null($lk)) return false;

        if (!isset($this->listeners[$lk])) {
            $this->listeners[$lk] = [
                "listener" => $listener,
                "handler" => $handler,
                "once" => $once
            ];
            return true;
        }

        return false;
    }

    /**
     * 修改此事件实例的 订阅者列表
     * 删除 订阅者
     * !! 无法删除 NULL 订阅者，因为 NULL 订阅者都互不相等
     * !! 当 订阅者列表为空时，销毁此 事件实例
     * @param Object $listener 事件订阅者 类(String) | 类实例(Object) | NULL
     * @return Bool
     */
    public function delListener($listener)
    {
        //先检查是否存在
        if (false === $this->hasListener($listener)) return false;
        //取得 key
        $lk = $this->getListenerKey($listener);
        //删除
        unset($this->listeners[$lk]);

        if (empty($this->listeners)) {
            //订阅者列表为空，销毁此事件实例
            Event::destroy($this->name);
        }

        return true;
    }

    /**
     * 当 此事件被触发时，通知所有订阅者
     * !! 执行完成后，删除 一次性订阅者
     * !! 当 订阅者列表为空时，销毁此 事件实例
     * @param String|Object|null $triggerBy 事件触发者，可以是：：类(String类全称) | 类实例(Object) | NULL
     * @param Array $args 事件处理方法的 额外参数
     * @return void
     */
    public function handle($triggerBy, ...$args)
    {
        //订阅者
        $ls = $this->listeners;
        //准备 事件处理方法参数
        $hargs = array_merge([$triggerBy], $args);
        //通知订阅者，执行 handler
        foreach ($ls as $lk => $evi) {
            $hdl = $evi["handler"];
            if (!is_callable($hdl)) continue;
            //执行 事件处理方法
            call_user_func_array($hdl, $hargs);
        }

        //事件处理方法执行完毕后，删除一次性订阅者
        $ls = array_filter($ls, function($li) {
            return !(isset($li["once"]) && $li["once"] === true);
        });

        //订阅者列表为空，销毁此事件实例
        if (empty($ls)) Event::destroy($this->name);
        
        //写入新的 listeners
        $this->listeners = $ls;
    }



    /**
     * 事件实例 工具方法
     */

    /**
     * 根据 传入的 listener 和 handler 创建 对应的 $event->listeners 数组中的 键名
     * 类       --> 类全称
     * 类实例    --> 类全称:spl_object_hash(Object)
     * NULL     --> "NULL:".Str::nonce(16)  所有 NULL 订阅者都被当成 不同的订阅者
     * @param String|Object|null $listener 事件订阅者
     * @return String|null 此订阅者在 listeners 数组中的 键名
     */
    protected function getListenerKey($listener)
    {
        //类全称
        if (Is::nemstr($listener) && class_exists($listener)) return $listener;

        //类实例
        if (is_object($listener)) {
            $clsn = get_class($listener);
            $insc = spl_object_hash($listener);
            return "$clsn:$insc";
        }

        //NULL
        if (is_null($listener)) {
            $ls = $this->listeners;
            $lk = "NULL:".Str::nonce(16);
            while ( false !== isset($ls[$lk]) ) {
                $lk = "NULL:".Str::nonce(16);
            }
            return $lk;
        }

        return null;
    }

    /**
     * 判断 订阅者是否已存在
     * @param Object $listener 事件订阅者 类(String) | 类实例(Object) | NULL
     * @param Bool
     */
    protected function hasListener($listener)
    {
        //取得 key
        $lk = $this->getListenerKey($listener);
        if (is_null($lk)) return false;
        return isset($this->listeners[$lk]);
    }
}