<?php
/**
 * 框架特殊工具类
 * 处理 $_GET|$_POST
 * 
 * Spf 框架允许的 $_GET 参数形式包括：
 *      URL 参数值                  转为类型        转为值
 *      'yes|no|true|false'         bool            true|false|true|false
 *      'foo,bar,jaz'               array           ['foo', 'bar', 'jaz']
 *      '20|15.36'                  int|float       20|15.36
 *      'foo_bar-jaz~tom.jry'       任意 string     'foo_bar-jaz~tom.jry'
 * 
 *      !! url 作为 $_GET 参数，需要先对 url 执行 urlencode
 *      'https%3A%2F%2Ffoo.com%2Fbar'               --> https://foo.com/bar
 *      !! 如果 url 不以 http|https:// 开头的，需要额外拼接一个 U_ 开头标记
 *      'U_%2Ffoo%2Fbar.js'                         --> https://[当前host]/foo/bar.js
 *      'U_foo%2Fbar.js'                            --> https://[当前页面路径]/foo/bar.js
 *      'U_..%2F..%2Ffoo%2Fbar.js'                  --> https://[当前页面路径]/../../foo/bar.js
 */

namespace Spf\util;

use Spf\traits\ContextProvider;

class Gets extends SpecialUtil
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

    //原始数据
    protected $origin = [];

    //处理后数据
    protected $context = [];

    /**
     * 构造
     * @param Array $gets
     * @return void
     */
    public function __construct($gets = [])
    {
        //如果传入 null 直接返回空 gets
        if (is_null($gets)) return;
        
        if (!Is::nemaso($gets)) {
            $gets = $_GET;
        }
        //缓存到 origin
        $this->origin = Arr::copy($gets);

        //对 $gets 依次执行处理
        $ctx = [];
        foreach ($gets as $k => $v) {
            //一定是字符串
            if (!is_string($v)) continue;
            $v = trim($v);

            // 0    null
            if (strtolower($v)==="null") {
                $ctx[$k] = null;
                continue;
            }

            // 1    检查是否有效的 布尔值
            $fv = self::fixBoolInData($v);
            if (is_bool($fv)) {
                $ctx[$k] = $fv;
                continue;
            }

            // 2    检查是否 Number 数字
            $fv = self::fixNumInData($v);
            if (!is_null($fv)) {
                $ctx[$k] = $fv;
                continue;
            }

            // 3    检查是否有效的 url
            $fv = self::fixUrlInData($v);
            if (!is_null($$fv)) {
                $ctx[$k] = $fv;
                continue;
            }

            // 4    带 , 的转为 []
            if (strpos($v, ",")!==false) {
                $ctx[$k] = explode(",", $v);
                continue;
            }

            // 5    普通字符串
            $ctx[$k] = $v;
        }

        //缓存到 context
        $this->context = $ctx;
    }

    /**
     * 返回 context
     * @param String $key
     * @return String|null
     */
    public function ____ctx($key=null)
    {
        if (!Is::nemstr($key)) return $this->context;
        return isset($this->context[$key]) ? $this->context[$key] : null;
    }

    /**
     * 运行时 向 context 中插入新数据
     * @param String|Array $key
     * @param Mixed $val
     * @return Gets $this
     */
    public function ____set($key, $val)
    {
        if (Is::nemstr($key)) {
            $this->context[$key] = $val;
            return $this;
        }

        if (Is::nemaso($key)) {
            $this->context = Arr::extend($this->context, $key);
            return $this;
        }

        return $this;
    }

    /**
     * 安全输出，过滤 Secure 工具类 中定义的 illegal 字符
     * @param String $key 键名
     * @param Array $chars 可以指定额外要过滤的字符
     * @return String 过滤后的字符串
     */
    public function secure($key, $chars=[])
    {
        $v = $this->ctx($key);
        //!! 不能是特殊类型 null|bool|number
        if (is_null($v) || is_bool($v) || Is::realnum($v) || !Is::nemstr($v)) return $v;

        //!! 排除 url
        if (Url::isUrl($v)===true) return $v;

        //调用 Secure 工具类
        return Secure::trimIllegal($v, "gets", $chars);
    }

    /**
     * __get
     * @param String $key 访问 context[$key]
     * @return Mixed
     */
    public function __get($key)
    {
        

        //最后调用 ContextProvider trait 中的 __get
        return $this->ctxGet($key);
    }

    /**
     * __call
     * @param String $key 访问 context[$key]
     * @param Array $dft 不存在则 返回 默认值 $dft[0]
     * @return Mixed
     */
    public function __call($key, $dft)
    {
        

        //最后调用 ContextProvider trait 中的 __call
        return $this->ctxCall($key, $dft);
    }

    /**
     * 判断 键 是否存在
     * @param String $key
     * @return Bool
     */
    public function has($key)
    {
        //$keys = array_merge([], array_keys($this->context));
        //$key = Str::snake($key,"_");
        //return in_array($key, $keys);
        return $this->ctxHas($key);
    }



    /**
     * 静态工具
     */

    /**
     * 处理 bool 类型的 $_GET | $_POST 参数
     * 只能是：yes|no|true|false
     * @param String $bool
     * @return Bool|null 不是有效的 布尔类型参数，返回 null
     */
    public static function fixBoolInData($bool=null)
    {
        if (!Is::nemstr($bool)) return null;
        //小写
        $bool = strtolower($bool);
        if (!in_array($bool, ["true","false","yes","no"])) return null;
        return in_array($bool, ["true","yes"]);
    }

    /**
     * 处理 Number 形式的 $_GET | $_POST 参数
     * 只能是：Is::realnum($num)===true 的 字符串
     * @param String $num
     * @return Int|Float|null 不是有效的 Number 形式的值，返回 null
     */
    public static function fixNumInData($num=null)
    {
        if (!Is::realnum($num)) return null;
        return $num * 1;
    }

    /**
     * 处理 url 形式的 $_GET | $_POST 参数
     * 可能以 http:// | https:// | U_ 开头 的字符串
     * 不过滤 self::$illegalChars
     * @param String $url
     * @return String|null 如果不是有效的 url 字符串，返回 null
     */
    public static function fixUrlInData($url=null)
    {
        if (!Is::nemstr($url)) return null;
        
        //标准格式 url 直接返回
        $urllow = strtolower($url);
        if (substr($urllow, 0, 7)==="http://" || substr($urllow, 0, 8)==="https://") {
            return $url;
        }

        //U_ 开头的，需要根据当前 url 拼接最终的 url
        if (substr($urllow, 0, 2)==="u_") {
            $url = substr($url, 2);
            //构建 Url 实例 自动处理 ../ ，合并 queryString
            $uo = Url::mk($url);
            return $uo->full;
        }

        return null;
    }



}