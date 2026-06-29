<?php
/**
 * 工具类
 * 对输入数据安全处理 
 */

namespace Spf\util;

class Secure extends SpecialUtil 
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
     * !! 子类必须覆盖这些静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = true;   //默认启用
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];

    /**
     * 特殊字符，可以通过 Env::$current->config->ctx("util/secure/chars") 覆盖定义
     */
    protected static $chars = [
        //黑名单
        "illegal" => [
            //$_GET 中的非法字符
            //!! Spf 框架不允许任何形式的 $_GET 参数操作数据库，因此 禁止特殊字符
            "gets" => "<>&=?'\"",

            //$_POST 中的非法字符
            "posts" => "",

            //!! 合规控制，风控字符
            "risks" => [
                //!! 私有框架，仅内部小范围访问，只需要人工控制特定字符即可
                //!! 可通过环境变量，自定义扩展
            ],
        ],

        //白名单
        //!! 框架默认使用 黑名单 机制，白名单仅保留，不生效
        "allowed" => [],
    ];



    /**
     * 对传入的 String 字符串，按指定的 illegal 字符串[] 执行过滤
     * @param String $str
     * @param String $key 在 self::$chars["illegal"] 中对应的 键名路径下 定义的 特殊字符[]
     * @param Array $extras 可以额外指定 更多需要过滤的 字符
     * @param Closure $fn 还可以额外指定 自定义的 过滤方法
     * @return String 过滤后的字符串，不是字符串的 返回 空字符串
     */
    public static function trimIllegal($str, $key="gets", $extras=[], $fn=null)
    {
        if (!Is::nemstr($str)) return "";
        $nstr = $str;

        //字符列表
        if (!Is::nemstr($key)) $key = "gets";
        $chars = Arr::find(self::$chars, "illegal/".$key);
        if (is_null($chars)) $chars = [];
        if (Is::nemstr($chars)) $chars = explode("", $chars);
        if (Is::nemstr($extras)) $extras = explode("", $extras);
        if (Is::nemidx($extras)) {
            foreach ($extras as $exi) {
                if (!in_array($exi, $chars)) {
                    $chars[] = $exi;
                }
            }
        }

        //按 字符列表 执行过滤
        if (Is::nemidx($chars)) {
            $nstr = str_replace($chars, "", $nstr);
        }

        //最后执行自定义过滤方法
        if ($fn instanceof \Closure) {
            $nstr = $fn($nstr);
        }

        if (!Is::nemstr($nstr)) return "";
        return $nstr;
    }

    /**
     * 批量过滤，对传入的 [] 类型数据，依次递归过滤
     * @param String $data [] 类型数据
     * @param String $key 在 self::$chars["illegal"] 中对应的 键名路径下 定义的 特殊字符[]
     * @param Array $extras 可以额外指定 更多需要过滤的 字符
     * @param Closure $fn 还可以额外指定 自定义的 过滤方法
     * @return Array 
     */
    public static function batchTrimIllegal($data=[], $key="gets", $extras=[], $fn=null)
    {
        if (!Is::nemarr($data)) return $data;
        $ndata = [];
        foreach ($data as $k => $v) {
            //[] 类型则递归
            if (Is::nemarr($v)) {
                $ndata[$k] = self::batchTrimIllegal($v, $key, $extras, $fn);
                continue;
            }

            //排除 null|bool|number|url 等特殊类型
            if (is_null($v) || is_bool($v) || Is::realnum($v) || !Is::nemstr($v) || Url::isUrl($v)===true) {
                $ndata[$k] = $v;
                continue;
            }

            //执行过滤
            $ndata[$k] = self::trimIllegal($v, $key, $extras, $fn);
        }
        return $ndata;
    }

    /**
     * 对传入的 String 字符串，按指定的 白名单，执行过滤放行
     * @param String $str
     * @param String $key 在 self::$chars["allowed"] 中对应的 键名路径下 定义的 特殊字符[]
     * @param Array $extras 可以额外指定 更多需要放行的 字符
     * @param Closure $fn 还可以额外指定 自定义的 过滤方法
     * @return String 过滤后的字符串，不是字符串的 返回 空字符串
     */
    public static function passAllowed($str, $key="", $extras=[], $fn=null)
    {
        //TODO:...

        return $str;
    }

    //TODO:
    public static function batchPassAllowed($data, $key="", $extras=[], $fn=null)
    {

        return $data;
    }

}