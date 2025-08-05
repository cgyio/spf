<?php
/**
 * 框架 特殊工具类
 * 日志管理
 * 使用 Monolog 库
 */

namespace Spf\util;

use Spf\Runtime;
use Spf\App;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class Log extends SpecialUtil
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
     * 缓存已创建的 Logger 实例
     */
    protected static $loggers = [
        /*
        "logger channel name" => Logger 实例,
        ...
        */
    ];

    //可用的 Monolog 记录日志的实例方法
    protected static $ms = [
        //使用这些方法
        "debug", "info", "notice", "warning", "error",
        "critical", "alert", "emergency"
    ];

    //预定义的 log 格式，可被框架启动参数 ""
    protected static $format = "%datetime% | %channel% | %level_name% | %message% | %context% | %level%\n";

    //log 文件后缀
    protected static $ext = ".log";

    //记录 log 的级别阈值，即 只有超过此阈值级别 才会记录 log
    protected static $lvl = Logger::DEBUG;
    


    /**
     * 根据当前的 响应状态，创建对应的 Logger 实例
     * @return Logger 实例
     */
    protected static function getLogger()
    {
        //log 开关 WEB_LOG 必须开启 默认开启
        if (self::$enable !== true) return null;

        //日志文件路径 存在且可写
        $logp = defined("LOG_PATH") ? LOG_PATH : null;
        if (!is_dir($logp) || !Path::isWritable($logp)) return null;

        //判断当前响应状态
        if (!empty(Runtime::$app) && Runtime::$app instanceof App) {
            //响应当前请求的 App 应用实例已经创建，则 log 应记录到当前 App 对应的 log 中
            $logcls = get_class(Runtime::$app);
            $logmn = "response";    //TODO：获取当前阶段的 响应方法名 fooBar
        } else {
            //还未进行到 App 应用实例化阶段，通常是在 框架初始化阶段记录 log
            $logcls = Cls::find("runtime", "Spf\\");
            $logmn = "start";
        }
        //logger channel name  NS/foo/Bar:methodName()
        $logn = str_replace("\\","/", trim($logcls, "\\"));
        $logn = $logn.":".Str::camel($logmn, false)."()";

        //判断 Logger 实例是否已经存在，存在则返回此实例
        if (isset(self::$loggers[$logn]) && self::$loggers[$logn] instanceof Logger) return self::$loggers[$logn];

        //不存在实例，则创建
        //log 文件名
        $logarr = explode(":", $logn);
        $logf = str_replace("/","_",$logarr[0]).self::currentSuffix();
        //log 文件路径
        $logfp = $logp.DS.$logf;
        
        //创建日志记录器
        $logger = new Logger($logn);
        //创建日志处理器，使用默认的 DEBUG 级别阈值
        $handler = new StreamHandler($logfp, self::$lvl);
        //设置日志样式，LineFormatter
        $formatter = new LineFormatter(
            self::$format,
            "Y-m-d H:i:s",  //日期格式
            false,          //是否允许消息内包含换行符
            true            //是否忽略空的 上下文/额外数据
        );
        //应用样式
        $handler->setFormatter($formatter);
        //应用处理器
        $logger->pushHandler($handler);

        //缓存
        self::$loggers[$logn] = $logger;

        //返回新创建的 logger
        return $logger;
    }

    /**
     * 每次记录日志时，根据当前的响应状态，获取默认的上下文数据，例如：Request::audience | Uac::currentUsr() 等
     * @param Array $ctx 手动传入的需要记录的 上下文数据
     * @return Array
     */
    protected static function getContext($ctx=[])
    {
        //TODO:

        return $ctx;
    }

    /**
     * __callStatic
     * 通过 Log::error(...) | Log::critical(...) | ... 调用 支持的 Monolog 日志方法，创建日志
     * @param String $m 支持的 Monolog 库 Logger 的实例方法，debug | info | error | ...
     * @param Array $args 这些日志记录方法的 参数：
     *      0   $msg        String  日志信息
     *      1   $context    Array   可选的 额外数据
     * @return Bool
     */
    public static function __callStatic($m, $args)
    {
        //获取 或 创建 Logger 实例
        $logger = self::getLogger();
        //未获取到有效的 Logger 实例，返回 false
        if (empty($logger) || !$logger instanceof Logger) return false;

        /**
         * Log::error(...) | Log::alert(...)
         * 支持的 Monolog 方法保存在 self::$ms 数组中
         */
        if (in_array($m, self::$ms)) {
            //日志信息内容
            $msg = !empty($args) ? $args[0] : "未指定消息";
            if (!Is::nemstr($msg)) $msg = "未指定消息";
            //额外的上下文
            $ctx = count($args)>1 ? $args[1] : [];
            if (!Is::nemarr($ctx)) $ctx = [];
            //合并默认上下文
            $ctx = self::getContext($ctx);

            //调用 Logger->$m()
            $logger->$m($msg, $ctx);

            return true;
        }

        return false;

    }

    /**
     * 获取当前框架定义的 日志文件后缀类型 默认 self::$ext = ".log"
     * @return String 
     */
    protected static function currentSuffix()
    {
        $cext = defined("EXT_LOG") ? EXT_LOG : self::$ext;
        return (Is::nemstr($cext) && substr($cext, 0, 1)==".") ? $cext : ".log";
    }
}