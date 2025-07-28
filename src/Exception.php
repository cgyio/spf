<?php
/**
 * cgyio/spf 框架 异常处理类 基类
 * 
 * 通过 set_error_handler 和 regist_shutdown_function 将 php(fatal) error 通过 spf\Exception 处理
 * 框架中 其他类型的异常处理类 都 继承自此类
 */

namespace Spf;

class Exception extends \Exception 
{
    /**
     * 当前类型的 异常处理类 异常代码 code 前缀
     * spf 框架内部异常 code 前缀区间 000~099
     * 应用层自定义异常 code 前缀区间 100~999
     * !! spf 框架所有异常处理类，都继承自此类，必须在子类中 覆盖此静态属性
     */
    protected static $codePrefix = 0;   //相当于 000

    /**
     * 当前类型的 异常处理类 中定义的 异常信息，定义格式为：
     *  [
     *      "key" => [
     *          "错误标题", 
     *          "错误信息模板 %{n}%", 
     *          (int)错误代码 
     *      ],
     * 
     *      # 可以有更多层级，可通过 key-path 访问：foo/bar/jaz
     *      "foo" => [
     *          "bar" => [
     *              "jaz" => ["标题", "%{1}%", 1024],
     *              ...
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     * 主要定义可被捕获的 php 错误，和 php fatal 错误 等
     * 
     * !! 子类必须覆盖此静态属性
     * !! 应定义多语言
     */
    protected static $exceptions = [
        //zh-CN
        "zh-CN" => [
            
            //php error
            "php" => [
                "warning"           => ["PHP Warning",          "%{1}%",    E_WARNING],
                "notice"            => ["PHP Notice",           "%{1}%",    E_NOTICE],
                "user" => [
                    "error"         => ["User Error",           "%{1}%",    E_USER_ERROR],
                    "warning"       => ["User Warning",         "%{1}%",    E_USER_WARNING],
                    "notice"        => ["User Notice",          "%{1}%",    E_USER_NOTICE],
                    "deprecated"    => ["User Deprecated",      "%{1}%",    E_USER_DEPRECATED],
                ],
                "strict"            => ["PHP Strict Notice",    "%{1}%",    E_STRICT],
                "deprecated"        => ["PHP Deprecated",       "%{1}%",    E_DEPRECATED],
                "recoverable" => [
                    "error"         => ["Recoverable Error",    "%{1}%",    E_RECOVERABLE_ERROR]
                ],
            ],

            //php fatal
            "fatal" => [
                "error"             => ["Fatal Error",          "%{1}%",    E_ERROR],
                "parse"             => ["Parse Error",          "%{1}%",    E_PARSE],
                "core" => [
                    "error"         => ["Core Error",           "%{1}%",    E_CORE_ERROR],
                    "warning"       => ["Core Warning",         "%{1}%",    E_CORE_WARNING],
                ],
                "compile" => [
                    "error"         => ["Compile Error",        "%{1}%",    E_COMPILE_ERROR],
                    "warning"       => ["Compile Warning",      "%{1}%",    E_COMPILE_WARNING],
                ],
            ],

        ],
    ];

    /**
     * 默认 异常输出语言，SPF_EXPORT_LANG 常量优先级更高
     * !! 子类不要覆盖
     */
    public static $lang = "zh-CN";

    //异常类实例化时，缓存 当前的 异常预设信息
    public $conf = [
        /*
        "title" => "标题",
        "message" => "经过字符串模板替换的 最终显示的 message",
        "xpath" => "当前异常 在 static::$exceptions 数组中的 键名路径：foo/bar",
        "code" => "带有 异常代码前缀的 最终异常码，例如：0001024"
        */
    ];

    /**
     * 覆盖构造函数
     * @param String $msg 错误信息，可指定多条，英文逗号隔开 用于替换信息模板中的 %{1}% %{2}% ...
     * @param Int|String $code 错误代码 4 --> '0000004'  或者  key-path：fatal/parse
     * @param Array $args \Exception 类构造函数的 其他可选参数
     * @return void
     */
    public function __construct($msg, $code, ...$args)
    {
        //根据 传入的 code( 异常码 或 key-path ) 查找预定义的 异常信息
        $exception = static::getException($code);

        if (!is_array($exception) || count($exception)<3) {
            //获取预定义的 异常信息 失败，直接使用 \Exception 的构造函数，创建实例
            parent::__construct($msg, $code, ...$args);
            //返回
            return;
        }

        //存在 预定义的 异常信息，处理 code 和 message
        $real = $exception[2];
        $code = static::getCodeWithPre($real, true);
        //处理 预定义的 异常信息 字符串模板
        $msg = static::fixMsgTemplate($exception[1], $msg);
        //增加标题
        $msg = $exception[0]."：".$msg;
        
        //调用父类构造函数，创建异常实例
        parent::__construct($msg, $code, ...$args);

        //缓存 当前异常的预设参数
        $this->conf = [
            //输出的异常码 带前缀
            "code" => $code,
            "title" => $exception[0],
            //处理后的 已经替换模板字符的 msg
            "message" => $msg,
            //缓存 key-path
            "xpath" => static::getKeyPath($code),
        ];
    }

    /**
     * 针对不同的 异常 code 执行可能定义的 handler
     * key-path 为 foo/bar 对应的 handler 方法为 fooBarHandler 方法，如果有，则调用
     * 任意异常类实例的 handler 方法 必须 public 且 返回 $this
     * !! 子类可覆盖此方法
     * @param Bool $exit 是否立即终止响应，输出错误信息
     * @return $this
     */
    public function handleException($exit=false)
    {
        //log
        $this->logError();

        //读取缓存的 异常信息
        $conf = $this->conf;
        //key-path
        $kp = $conf["xpath"] ?? "";
        
        if (is_string($kp) && $kp!="") {
            //查找 可能存在的 handler 方法
            $kp = str_replace(["/","_","-"], " ", $kp);
            $kp = ucwords($kp);
            $ka = explode(" ", $kp);
            $ka[0] = lcfirst($ka[0]);
            $m = implode("", $ka)."Handler";
            if (method_exists($this, $m)) {
                //如果存在 handler 方法，则尝试调用
                $this->$m($exit);
            }
        }

        //如果需要终止响应
        if ($exit) {
            //TODO: 调用 Response 类，创建输出内容，然后终止响应

            exit;
        }

        //不需要终止响应
        return $this;
    }

    /**
     * TODO: log 错误日志
     * 
     */
    protected function logError($msg, $extra=[])
    {
        //todo:

    }



    /**
     * php 错误处理
     * @return void
     */
    final public static function handlePhpError($code, $msg, $file, $line)
    {
        //处理 code
        $code = self::getCodeWithPre($code, true);
        //抛出异常
        throw new Exception($msg, $code, null, $file, $line);
    }

    /**
     * php fatal 错误处理
     * @return void
     */
    final public static function handlePhpFatalError()
    {
        $lastError = error_get_last();
    
        // 检查是否有未处理的致命错误
        if ($lastError !== null) {
            $fatalErrors = [
                //这些错误类型为 fatal error
                E_ERROR, E_PARSE, 
                E_CORE_ERROR, E_CORE_WARNING, 
                E_COMPILE_ERROR, E_COMPILE_WARNING
            ];
            if (in_array($lastError['type'], $fatalErrors)) {
                try {
                    //获取错误信息
                    $code = self::getCodeWithPre($lastError["type"], true);
                    $msg = $lastError['message'];
                    $file = $lastError['file'];
                    $line = $lastError['line'];
                    //抛出异常
                    throw new Exception($msg, $code, null, $file, $line);
                } catch (Exception $e) {
                    //处理异常，终止响应
                    $e->handleException(true);
                }
            }
        }
    }

    /**
     * 框架初始化阶段 注册 php 错误处理函数
     * @return void
     */
    final public static function regist()
    {
        set_error_handler([self::class, "handlePhpError"]);
		//注册一个 在 exit() 后执行的方法，此方法中获取最后一个错误，如果是 fatal error 则抛出
		register_shutdown_function([self::class, "handlePhpFatalError"]);
    }



    /**
     * 静态工具
     * !! 子类不要覆盖
     */

    /**
     * 获取 异常信息的 输出语言，使用 SPF_EXPORT_LANG | self::$lang
     * @return String 输出语言 zh-CN | en | ...
     */
    final protected static function getLang()
    {
        return defined("SPF_EXPORT_LANG") ? SPF_EXPORT_LANG : static::$lang;
    }

    /**
     * 根据输出语言，获取预设的 异常参数
     * @param String $lang 要获取参数的 语言，默认不指定，使用 SPF_EXPORT_LANG | self::$lang
     * @return Array 预设的 异常参数
     */
    final protected static function getExceptions($lang=null)
    {
        if (!is_string($lang)) $lang = static::getLang();
        return static::$exceptions[$lang] ?? null;
    }

    /**
     * 根据输入的 异常码 或 key-path 获取预定义的异常信息数组
     * @param String|Int $code 异常码 或 key-path
     * @return Array|null
     */
    final protected static function getException($code)
    {
        //获取 key-path
        if (is_int($code) || is_numeric($code)) {
            $kp = static::getKeyPath($code);
        } else if (is_string($code) && strpos($code, "/")!==false) {
            $kp = $code;
        } else {
            $kp = null;
        }
        if (!is_string($kp) || $kp=="") return null;

        //所有已定义的 异常信息 一维数组，key-path 为键名
        $defs = static::definedExceptions();
        if (!is_array($defs) || empty($defs) || !isset($defs[$kp])) return null;

        return $defs[$kp];
    }

    /**
     * 从给出的 异常码，解析出此异常 在 当前异常类的 $exceptions 数组中的 key-path
     * 例如：在 Exception 类中 错误代码：4 对应的 key-path 为：fatal/parse
     * @param Numeric|Int $code 异常码
     * @return String|null 找到的 异常信息的 key-path
     */
    final protected static function getKeyPath($code=null)
    {
        //异常码 转为 int
        if (is_string($code) && is_numeric($code)) $code = (int)$code;
        if (!is_int($code)) return null;

        //所有已定义的 异常信息 一维数组，key-path 为键名
        $defs = static::definedExceptions();
        if (!is_array($defs) || empty($defs)) return null;
        
        //当前异常类的 异常码前缀 int
        $cpre = static::$codePrefix;
        if ($cpre<=0) {
            //当前缀为 0 时，实际定义的异常码 就是 输入的 $code
            $real = $code;
        } else {
            //前缀 和 输入的 code 都转为 string
            $cstr = (string)$code;
            $pstr = (string)$cpre;
            $rstr = substr($cstr, strlen($pstr));
            //得到实际定义的 异常码
            $real = (int)$rstr;
        }

        //在所有已定义的 异常信息中 查找 实际定义的 异常码
        foreach ($defs as $kp => $ec) {
            if (!isset($ec[2]) || !is_int($ec[2])) continue;
            if ($ec[2] === $real) return $kp;
        }
        
        return null;
    }

    /**
     * 根据 给出的 key-path 或 实际异常码 int 获得输出的 异常码(带前缀) string
     * @param String $kp key-path
     * @param Bool $int 是否将输出的 异常码 转为 int，默认 false
     * @return String|null 用于输出的 异常码(带前缀)
     */
    final protected static function getCodeWithPre($kp=null, $int=false)
    {
        //前缀
        $pre = static::$codePrefix;

        //如果给出的是 实际异常码 int 直接添加 前缀 并 输出
        if (is_int($kp) || is_numeric($kp)) {
            $kp = $kp<1000 ? substr("0000".$kp, -4) : $kp;
            $code = substr("000".$pre, -3).$kp;
            if ($int) return (int)$code;
        }

        if (!is_string($kp) || strpos($kp, "/")===false) return null;

        //所有已定义的 异常信息 一维数组，key-path 为键名
        $defs = static::definedExceptions();
        if (!is_array($defs) || empty($defs) || !isset($defs[$kp])) return null;
        $exception = $defs[$kp];
        if (!isset($exception[2]) || !is_int($exception[2])) return null;

        //实际异常码
        $real = $exception[2];
        $real = $real<1000 ? substr("0000".$real, -4) : $real;
        //输出字符串形式的 异常码(带 前缀)
        $code = substr("000".$pre, -3).$real;
        if ($int) return (int)$code;
        return $code;
    }

    /**
     * 将 当前异常类中定义的 异常信息 static::$exceptions [] 转为 一维数组形式，例如：
     *  $exceptions = [
     *      "foo" => [
     *          "bar" => [
     *              "jaz" => ["标题", "%{1}%", 1024],
     *              ...
     *          ],
     *          "tom" => ["标题", "%{1}%", 2048],
     *      ],
     *  ]  
     * 转为一维数组：
     *  [
     *      "foo/bar/jaz" => ["标题", "%{1}%", 1024],
     *      "foo/tom" => ["标题", "%{1}%", 2048],
     *      ...
     *  ]
     * @param Array $exceptions 要递归的 异常信息数组，不指定则为 static::getExceptions()
     * @param String $key 当前要递归的 异常信息数组的 key
     * @return Array 一维数组
     */
    final protected static function definedExceptions($exceptions=null, $key=null)
    {
        $defs = [];
        if (!is_array($exceptions)) $exceptions = static::getExceptions();
        $key = (is_string($key) && $key!="") ? $key."/" : "";
        foreach ($exceptions as $k => $sub) {
            if (!is_array($sub) || empty($sub)) continue;
            if (empty(array_diff(array_keys($sub), [0,1,2]))) {
                //这是一个 异常信息 定义数组
                $defs[$key.$k] = $sub;
            } else {
                $defs = array_merge($defs, static::definedExceptions($sub, $key.$k));
            }
        }
        return $defs;
    }

    /**
     * 替换异常信息中的 %{n}%
     * @param String $cmsg 预设参数中的 异常信息，带有 %{n}% 模板字符
     * @param String $msg 抛出异常时 提供的 msg 参数，多条使用英文逗号隔开
     * @return String 模板替换后的异常信息
     */
    final protected static function fixMsgTemplate($cmsg, $msg)
    {
        if (!is_string($cmsg) || $cmsg=="" || !is_string($msg) || $msg=="") {
            //参数不正确，直接返回 抛出时提供的 msg
            return $msg;
        } 
        //开始替换 模板字符
        $msa = explode(",",$msg);
        $msa = array_map(function($mi){
            return trim($mi);
        });
        foreach ($msa as $i => $mi) {
            $cmsg = str_replace("%{".($i+1)."}%", $mi, $cmsg);
        }
        return $cmsg;
    }





}