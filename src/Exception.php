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
    //异常码(不带前缀) 的 位数，0 为 不指定位数
    protected static $codeDigit = 0;

    /**
     * 当前类型的 异常处理类 中定义的 异常信息，定义格式为：
     *  [
     *      "key" => [
     *          "异常标题", 
     *          "异常信息模板 %{n}%", 
     *          (int)异常代码 不带前缀，在 throw 时 提供此 异常代码
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
                "warning"           => ["PHP Warning",          "%{1}%",    E_WARNING], //2
                "notice"            => ["PHP Notice",           "%{1}%",    E_NOTICE],  //8
                "user" => [
                    "error"         => ["User Error",           "%{1}%",    E_USER_ERROR],          //256
                    "warning"       => ["User Warning",         "%{1}%",    E_USER_WARNING],        //512
                    "notice"        => ["User Notice",          "%{1}%",    E_USER_NOTICE],         //1024
                    "deprecated"    => ["User Deprecated",      "%{1}%",    E_USER_DEPRECATED],     //16384
                ],
                "strict"            => ["PHP Strict Notice",    "%{1}%",    E_STRICT],      //2048
                "deprecated"        => ["PHP Deprecated",       "%{1}%",    E_DEPRECATED],  //8192
                "recoverable" => [
                    "error"         => ["Recoverable Error",    "%{1}%",    E_RECOVERABLE_ERROR],   //4096
                ],
            ],

            //php fatal
            "fatal" => [
                "error"             => ["Fatal Error",          "%{1}%",    E_ERROR],   //1
                "parse"             => ["Parse Error",          "%{1}%",    E_PARSE],   //4
                "core" => [
                    "error"         => ["Core Error",           "%{1}%",    E_CORE_ERROR],      //16
                    "warning"       => ["Core Warning",         "%{1}%",    E_CORE_WARNING],    //32
                ],
                "compile" => [
                    "error"         => ["Compile Error",        "%{1}%",    E_COMPILE_ERROR],   //64
                    "warning"       => ["Compile Warning",      "%{1}%",    E_COMPILE_WARNING], //128
                ],
            ],

        ],
    ];

    /**
     * 默认 异常输出语言，SPF_EXPORT_LANG 常量优先级更高
     * !! 子类不要覆盖
     */
    public static $lang = "zh-CN";

    //异常类实例化时，缓存 当前的 异常信息
    protected $context = [
        /*
        "title" => "标题",
        "message" => "经过字符串模板替换的 最终显示的 message",
        "xpath" => "当前异常 在 static::$exceptions 数组中的 键名路径：foo/bar",
        "code" => "带有 异常代码前缀的 最终异常码，例如：0001024"
        */
    ];

    /**
     * 覆盖构造函数
     * @param String $msg 异常信息，可指定多条，英文逗号隔开 用于替换信息模板中的 %{1}% %{2}% ...
     * @param Int|String $code 异常代码(不带前缀) 4  或者  key-path：fatal/parse
     * @param Array $extra 需要额外传入的 自定义异常信息，可以传入 file|line 等信息，替换掉异常类实例内部的属性
     * @param Throwable|null $previous 异常链
     * @return void
     */
    public function __construct($msg, $code, $extra=[], $previous=null)
    {
        //根据 传入的 code( 异常码(不带前缀) 或 key-path ) 查找预定义的 异常信息
        $exception = static::getException($code);

        if (!is_array($exception) || count($exception)<3) {
            //获取预定义的 异常信息 失败，直接使用 \Exception 的构造函数，创建实例
            parent::__construct($msg, $code, $previous);
            //返回
            return;
        }

        //存在 预定义的 异常信息
        //将 异常码(不带前缀) 转换为 完整的异常码(带前缀) string
        $fullcode = static::addCodePre($code);
        //处理 预定义的 异常信息 字符串模板
        $fullmsg = static::fixMsgTemplate($exception[1], $msg);
        //var_dump($fullcode);
        //var_dump($fullmsg);

        //缓存 当前的异常信息
        $this->context = array_merge([

            //输出的异常码 带前缀
            "code" => $fullcode,
            //不带前缀的 异常码
            "code_no_pre" => (int)$code,

            //预设异常参数
            //异常标题
            "title" => $exception[0],
            //处理后的 已经替换模板字符的 msg
            "message" => $fullmsg,
            //缓存 key-path 当前异常 在预设的 exceptions 数组中的 xpath
            "xpath" => static::getKeyPath($code),

        ], is_array($extra) ? $extra : []);
        
        //调用父类构造函数，创建异常实例
        parent::__construct($fullmsg, $fullcode, $previous);

        //检查传入的 额外异常信息中，是否包含 file|line 信息，如果有，则覆盖 
        $ks = ["file","line"];
        foreach ($ks as $ki) {
            if (isset($this->context[$ki])) {
                $this->$ki = $this->context[$ki];
            }
        }
    }

    /**
     * 读取 $this->context 
     * @param String $key 在 context 数组中的键名
     * @return Mixed
     */
    public function ctx($key="")
    {
        if (!is_string($key) || $key=="") return $this->context;
        if (!isset($this->context[$key])) return null;
        return $this->context[$key];
    }

    /**
     * 一次性完整输出 当前异常信息
     * @return Array
     */
    public function getInfo()
    {
        return [
            "code" => $this->getCode(),
            "message" => $this->getMessage(),
            "file" => $this->getFile(),
            "line" => $this->getLine(),
            "trace" => $this->getTrace(),
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
        $this->logError(
            $this->getMessage(),
            [
                "file" => $this->getFile(),
                "line" => $this->getLine()
            ]
        );
        
        //key-path
        $kp = $this->ctx("xpath");
        
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
        if ($exit || $this->needExit()===true) return $this->exit();

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
     * 异常处理方法：终止响应，输出异常信息
     * @return void
     */
    protected function exit()
    {
        $s = [];
        $s[] = "";
        $s[] = "----------PHP Error catched----------";
        $s[] = "";
        $s[] = "code: ".$this->ctx("code");
        $s[] = $this->ctx("title");
        $s[] = $this->ctx("message");
        $s[] = "file: ".$this->getFile();
        $s[] = "line: ".$this->getLine();
        if (isset($this->context["trace"])) {
            $s[] = "Stack trace";
            $s = array_merge($s, $this->ctx("trace"));
        }
        $s[] = "";
        $s[] = "----------PHP Error end----------";
        $s[] = "";
        echo implode("\n", $s);
        //var_export(implode("\n", $s));
        //var_export($this->getInfo());

        //TODO: 调用 Response 响应类，创建异常响应，并输出
        //...

        exit;
    }
    
    /**
     * 判断当前异常是否需要终止响应
     * !! 子类必须覆盖此方法，实现不同类型异常的 退出 判断
     * @return Bool
     */
    public function needExit()
    {
        /**
         * 此处判断 仅针对 php error/fatal error 
         * 要判断其他类型的 异常是否需要退出，应在对应类型的 exception 子类中 覆盖此方法
         */
        $code = $this->ctx("code_no_pre");
        $code = (int)$code;
        $okErrors = [
            //这些 php error 不需要终止
            E_NOTICE, E_USER_NOTICE,
        ];
        return !in_array($code, $okErrors);
    }



    /**
     * 全局处理 php 错误
     * 相关 属性|方法
     */

    /**
     * php 错误处理
     * @return void
     */
    final public static function handlePhpError($code, $msg, $file, $line)
    {
        //创建异常
        $e = new Exception(
            $msg, 
            $code, 
            [
                //file、line 作为额外的 异常信息
                "file" => $file,
                "line" => $line 
            ],
            null
        );

        if ($e->needExit()) {
            //如果 此 php error 需要终止响应
            try {
                //抛出异常
                throw $e;
            } catch (Exception $e) {
                //处理异常
                $e->handleException(true);
            }
        } else {
            //如果不需要终止，则 只抛出异常，由调用者决定如何处理
            throw $e;
        }

    }

    /**
     * php fatal 错误处理
     * @return void
     */
    final public static function handlePhpFatalError()
    {
        $lastError = error_get_last();
    
        // 检查是否有未处理的致命错误
        if (!empty($lastError)) {
            $fatalErrors = [
                //这些错误类型为 fatal error
                E_ERROR, E_PARSE, 
                E_CORE_ERROR, E_CORE_WARNING, 
                E_COMPILE_ERROR, E_COMPILE_WARNING
            ];
            if (in_array($lastError['type'], $fatalErrors)) {

                /**
                 * 解析 fatal error 的 message 得到 trace 序列
                 */
                $code = $lastError["type"] ?? 1;
                $msg = $lastError['message'] ?? "";
                $file = $lastError["file"] ?? "";
                $line = $lastError['line'] ?? 0;
                //var_dump($msg);
                $msga = explode("\n", $msg);
                $msgstr = array_shift($msga);
                $msgstr = explode(" in ", $msgstr)[0];
                //Stack trace
                $trace = [];
                foreach ($msga as $i => $msgi) {
                    $msgi = trim($msgi);
                    if (substr($msgi, 0, 1)!=="#") continue;
                    //$msgi = preg_replace("/#\d+\s+/","",$msgi);
                    $trace[] = $msgi;
                }
                
                try {
                    //抛出异常
                    throw new Exception(
                        $msgstr,
                        $code,
                        [
                            //将 file、line、trace 作为额外异常信息
                            "file" => $file,
                            "line" => $line,
                            "trace" => $trace
                        ],
                        null
                    );
                } catch (Exception $e) {
                    //处理异常，fatal error 必须终止响应
                    $e->handleException(true);
                }
            }
        }
    }

    /**
     * 框架初始化阶段 注册 php 错误处理函数
     * !! 此方法必须在框架启动流程之前执行
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
     * 根据输入的 异常码(不带前缀) 或 key-path 获取预定义的异常信息数组
     * @param String|Int $code 异常码(不带前缀) 或 key-path
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
     * 从给出的 异常码(不带前缀)，解析出此异常 在 当前异常类的 $exceptions 数组中的 key-path
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

        //在所有已定义的 异常信息中 查找 异常码(不带前缀)
        foreach ($defs as $kp => $ec) {
            if (!isset($ec[2]) || !is_int($ec[2])) continue;
            if ($ec[2] === $code) return $kp;
        }
        
        return null;
    }

    /**
     * 将 异常码(不带前缀) int 添加 codePrefix 后输出为 异常码(带前缀) string
     * 16  -->  0010016
     * @param String|Int $code 异常码(不带前缀) 
     * @param Bool $int 是否将输出的 异常码(带前缀) 转为 int，默认 false
     * @return String|null 异常码(带前缀)
     */
    final protected static function addCodePre($code, $int=false)
    {
        //前缀
        $pre = static::$codePrefix;
        //参数错误
        if (!is_int($code) && !is_numeric($code)) return null;

        //将 异常码(不带前缀) 补齐到 codeDigit 指定位数
        $code = static::padCode($code);
        //添加前缀，前缀自动补齐到 3 位
        $code = substr("000".$pre, -3).$code;
        if ($int) return (int)$code;
        return $code;
    }

    /**
     * 将 异常码(带前缀) string 去除 codePrefix 后输出为 异常码(不带前缀) int
     * 0010016  -->  $int=true: 16   $int=false: 0016
     * @param String|Int $code 异常码(带前缀) 数字 或 numeric 形式
     * @param Bool $int 是否输出 int 类型 异常码(不带前缀)，默认 true，false 时将输出经过 补齐位数的 异常码(不带前缀)
     * @return String|null 异常码(不带前缀)
     */
    final protected static function stripCodePre($code, $int=true)
    {
        //前缀
        $pre = static::$codePrefix;
        //参数错误
        if (!is_int($code) && !is_numeric($code)) return null;
        //数字化
        $code = (int)$code;
        //字符化
        $codestr = "".$code;
        $prestr = "".$pre;
        if (substr($codestr, 0, strlen($prestr)) !== $prestr) {
            //异常码(带前缀) 左侧 没有找到 前缀，说明提供的 code 异常码不是 当前异常类型，不做处理
            return null;
        }
        //左侧开始查找并去除 pre
        $codestr = substr($codestr, strlen($prestr));
        //得到 异常码(不带前缀) int
        $codeint = (int)$codestr;
        if ($int) return $codeint;
        //补齐到 预设位数
        return static::padCode($codeint);
    }

    /**
     * 将不带前缀的 异常码 补齐到 static::$codeDigit 位数
     * 16  -->  0016 (如果预设位数为 4)
     * @param Int|String $code 异常码(不带前缀) 
     * @return String 补齐到 $codeDigit 位数的 异常码(不带前缀)
     */
    final protected static function padCode($code=null)
    {
        if (!is_int($code) && !is_string($code)) return $code;
        if (is_numeric($code)) $code = (int)$code;
        if (!is_int($code)) return $code;
        //异常码(不带前缀) 预设的位数
        $dig = static::$codeDigit;
        if ($dig<=0) {
            //未预设位数，直接返回
            return "".$code;
        }
        //左补零
        return str_pad("".$code, $dig, "0", STR_PAD_LEFT);
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
        }, $msa);
        foreach ($msa as $i => $mi) {
            $cmsg = str_replace("%{".($i+1)."}%", $mi, $cmsg);
        }
        return $cmsg;
    }





}