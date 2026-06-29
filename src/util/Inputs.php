<?php
/**
 * 框架特殊工具类
 * 处理 php://input 中的 来自前端的请求体 body 中的数据
 * !! Spf 框架只支持 json、xml、以及纯文本的 自动解析
 * 
 * TODO：尝试实现 spf://... 形式的 框架自有格式数据
 */

namespace Spf\util;

use Spf\traits\ContextProvider;

class Inputs extends SpecialUtil 
{
    //通用 context 处理
    use ContextProvider {
        __get as ctxGet;
        __call as ctxCall;
    }

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
     * !! 子类必须覆盖这些静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = true;   //默认启用
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];

    //支持 自动转换格式的 input 类型  
    //!! 排在前面的格式 将被优先 识别并解析
    protected static $inputTypes = [
        //!! TODO:  spf://... 框架自有格式
        "spf",

        //常用 json | xml
        "json", 
        "xml",

        //url 参数 queryString 形式 foo=bar&jaz=tom...
        "qs",

        //逗号分隔的 数组形式 foo,bar,jaz,...
        "arr",

        //普通字符串 格式
        "str",
    ];



    //原始数据
    protected $origin = "";

    //本次会话的 php://input 数据格式
    protected $type = "";

    //处理后的数据
    protected $context = [];

    //在运行时修改 context 之前，先备份
    protected $ctxBackup = [];

    /**
     * 构造
     * @param String|Array $data 通过传入自定义数据，模拟前端 post 数据
     * @return void
     */
    public function __construct($data=null)
    {
        //使用 空 json 作为 默认空值 "{}" 
        $emv = "{}";

        if (is_string($data)) {
            //传入任意字符串，直接使用
        } else if (is_array($data)) {
            //传入 数组
            if (empty($data)) {
                $data = $emv;
            } else {
                $data = Conv::a2j($data);
            }
        } else if (!is_null($data)) {
            //传入其他 非空 形式，一律转为 默认空值
            $data = $emv;
        } else {
            //传入 null 则从 php://input 获取
            $data = file_get_contents("php://input");
            if (!is_string($data)) {
                //!! 前端只有传入 String 数据，才会被自动解析，不是 String 则转为 默认空值
                $data = $emv;
            } else if ($data==="") {
                //php://input 中为空字符串
                $data = Session::get("_php_input_", $emv);
            }
        }
        //兜底
        if (!is_string($data) || $data==="") $data = $emv;

        //缓存处理后的 data
        $this->origin = $data;

        //识别格式
        $this->type = $this->identify();

        //自动 识别并转换数据 写入 context
        $this->context = $this->decode();
    }

    /**
     * 读取 context 数据
     * @param String $key context[] 数据中的 键名|键名路径，默认 null 返回完整的 context
     * @param Mixed $dft 如果指定的 $key 未找到值，则使用此默认值
     * @return Mixed 找到的 context 数据
     */
    public function ____ctx($key=null, $dft=null)
    {
        if ($this->type==="str") return $this->context;
        $fm = "find".Str::camel($this->type, true);
        return static::$fm($this->context, $key, $dft);
    }

    /**
     * __get 调用 ctx 方法
     * @param String $key
     * @return Array
     */
    public function __get($key)
    {
        /**
         * $inputs->raw     --> $this->origin
         * $inputs->ctx     --> $this->context 
         */
        if ($key==="raw") return $this->origin;

        //最后调用 ContextProvider trait 中的 __get
        return $this->ctxGet($key);
    }

    /**
     * __call 调用 ctx 方法
     */
    public function __call($key, $args)
    {
       

        //最后调用 ContextProvider trait 中的 __call
        return $this->ctxCall($key, $args);
    }

    /**
     * 判断 键 是否存在
     * @param String $key
     * @return Bool
     */
    public function has($key)
    {
        return $this->ctxHas($key);
    }



    /**
     * 自动识别 static::$inputTypes[] 中定义的 格式
     * @param String $data 默认 null 将使用 $this->origin 作为数据源
     * @return String 返回 识别到的 传入数据类型，所有类型都不匹配 会返回 str
     */
    public function identify($data=null)
    {
        if (!is_string($data)) $data = $this->origin;
        //空字符串 快速判断
        if ($data==="") return "str";
        //依次识别
        foreach (static::$inputTypes as $type) {
            //识别方法
            $ism = "is".Str::camel($type, true);
            if (!method_exists(static::class, $ism)) continue;
            if (static::$ism($data)!==true) continue;
            return $type;
        }
        return "str";
    }

    /**
     * 转换 传入的 String 数据
     * @param String $data 默认 null 将使用 $this->origin
     * @return Array 转换后的数据
     */
    public function decode($data=null)
    {
        //默认 转换
        if (!is_string($data)) {
            if ($this->type==="str") return $this->origin;
            $cvm = "decode".Str::camel($this->type, true);
            return static::$cvm($this->origin);
        }
        
        //传入了 $data 
        $type = $this->identify($data);
        if ($type==="str") return $data;
        $cvm = "decode".Str::camel($type, true);
        return static::$cvm($data);
    }

    /**
     * 将当前 传入的 数据 转为 String
     * @param Array $data 默认 null 将使用 $this->context 
     * @return String 转换后的 字符串
     */
    public function encode($data=null)
    {
        if (is_null($data)) $data = $this->context;
        if ($this->type==="str") return $data;
        $cvm = "encode".Str::camel($this->type, true);
        return static::$cvm($data);
    }

    /**
     * 针对 Inputs::$inputTypes 中定义的 各种数据格式 的 
     * 格式判断方法，自动转换方法
     */
    //json
    public static function isJson($data) { return Is::json($data); }
    public static function decodeJson($data) { return Conv::j2a($data); }
    public static function encodeJson($data) { return Conv::a2j($data); }
    public static function findJson($data, $key=null, $dft=null)
    {
        if (!Is::nemarr($data)) return null;
        if (!Is::nemstr($key)) return $data;
        $val = Arr::find($data, $key);
        if (is_null($val)) return $dft;
        return $val;
    }
    //xml
    public static function isXml($data) { return Is::xml($data); }
    public static function decodeXml($data) { return Conv::x2a($data); }
    public static function encodeXml($data) { return Conv::a2x($data); }
    public static function findXml($data, $key=null, $dft=null) { return static::findJson($data,$key,$dft); }
    //qs
    public static function isQs($data) { return Is::query($data); }
    public static function decodeQs($data) { return Conv::u2a($data); }
    public static function encodeQs($data) { return Conv::a2u($data); }
    public static function findQs($data, $key=null, $dft=null) { return static::findJson($data,$key,$dft); }
    //arr 分隔符只能是 ,
    public static function isArr($data) { return Is::explodable($data, ","); }
    public static function decodeArr($data) { return explode(",", $data); }
    public static function encodeArr($data) { return implode(",", $data); }
    public static function findArr($data, $key=null, $dft=null)
    {
        if (!Is::nemidx($data)) return null;
        if (!Is::nemstr($key)) return $data;
        $val = Arr::find($data, $key);
        if (is_null($val)) return $dft;
        return $val;
    }
    //spf 框架自有的数据格式 spf://...
    public static function isSpf($data) { return Is::nemstr($data) && substr($data, 0, 6)==="spf://"; }
    public static function decodeSpf($data) { 
        //TODO: ...
        return [];
    }
    public static function encodeSpf($data) { 
        //TODO: ...
        return "spf://_to_do_";
    }
    public static function findSpf($data, $key=null, $dft=null)
    {
        //TODO: ...
        return null;
    }
    //扩展其他 支持的 input 类型 ...



    /**
     * 运行时修改 post 来的 json | xml | qs 数据
     * 通常在 劫持某个响应者执行某个响应方法时，可能需要 append 数据到 $request->inputs->context
     * !! 应在执行完操作后 reset 此数据，恢复原来的 input 数据
     */

    /**
     * 可以在运行时，插入 input 数据，模拟前端 post 数据
     * !! 在原 input 数据 基础上 append 扩展
     * @param Array $data 要模拟 input 的数据 []，与原 input 数据采用 extend 方式合并
     * @return $this
     */
    public function append($data=[])
    {
        //!! 只能在 input 为 json | xml | qs 时，使用此方法
        if (!in_array($this->type, ["json","xml","qs"])) return $this;
        if (!Is::nemaso($data)) return $this;

        //先备份
        //$this->ctxBackup = Arr::copy($this->context);
        //append
        //$this->context = Arr::extend($this->context, $data);

        return $this->extendCtx($data, true);
    }

    /**
     * 可以在运行时，替换 input 数据，模拟前端 post 数据
     * !! 覆盖原 input 数据
     * @param Array $data 要模拟 input 的数据 []，与原 input 数据采用 extend 方式合并
     * @return $this
     */
    public function replace($data=[])
    {
        //!! 只能在 input 为 json | xml | qs 时，使用此方法
        if (!in_array($this->type, ["json","xml","qs"])) return $this;
        if (!Is::nemaso($data)) $data = [];

        //先备份
        //$this->ctxBackup = Arr::copy($this->context);
        //replace
        //$this->context = $data;
        
        return $this->replaceCtx($data, true);
    }

    /**
     * 取消 运行时插入的 input 数据
     * @return $this
     */
    public function reset()
    {
        //!! 只能在 input 为 json | xml | qs 时，使用此方法
        if (!in_array($this->type, ["json","xml","qs"])) return $this;

        //恢复
        //$this->context = Arr::copy($this->ctxBackup);
        //$this->ctxBackup = [];

        return $this->restoreCtx();
    }



}