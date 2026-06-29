<?php
/**
 * 框架特殊工具类
 * 专门处理 $_POST
 */

namespace Spf\util;

class Posts extends Gets 
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
     * 构造
     * @param Array $gets 
     * @return void
     */
    public function __construct($gets = [])
    {
        if (!Is::nemaso($gets)) {
            $gets = $_POST;
        }
        //缓存到 origin
        $this->origin = Arr::copy($gets);

        //对 $gets 依次执行处理
        $ctx = [];
        foreach ($gets as $k => $v) {

            //只处理 字符串
            if (!is_string($v)) {
                $ctx[$k] = $v;
                continue;
            };
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
     * 安全输出，过滤 Secure 工具类 中定义的 illegal 字符
     * @param String $key 键名
     * @param Array $chars 可以指定额外要过滤的字符
     * @return String 过滤后的字符串
     */
    public function secure($key, $chars=[])
    {
        $v = $this->ctx($key);
        //!! 不能是特殊类型 null|bool|number|url
        if (is_null($v) || is_bool($v) || Is::realnum($v) || (Is::nemstr($v) && Url::isUrl($v)===true)) return $v;

        //!! $_POST 中可能包含提交的 文章数据，需要过滤 风控字符
        $fn = function($s) {
            return Secure::trimIllegal($s, "risks");
        };

        //[] 批量过滤
        if (Is::nemarr($v)) return Secure::batchTrimIllegal($v, "posts", $chars, $fn);

        //调用 Secure 工具类
        return Secure::trimIllegal($v, "posts", $chars, $fn);
    }

}