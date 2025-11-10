<?php
/**
 * 框架特殊工具类
 * 处理 $_GET | $_POST
 */

namespace Spf\util;

class Gets extends SpecialUtil
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
        $this->origin = Arr::copy($gets);

        //使用 Secure 工具处理
        foreach ($gets as $k => $v) {
            $sec = Secure::str($v);
            $this->context[$k] = $sec->context;
        }

        //fix
        $this->context = self::fix($this->context);
    }

    /**
     * 返回 context
     * @param String $key
     * @return String|null
     */
    public function ctx($key=null)
    {
        if (!Is::nemstr($key)) return $this->context;
        return isset($this->context[$key]) ? $this->context[$key] : null;
    }

    /**
     * __get
     * @param String $key 访问 context[$key]
     * @return Mixed
     */
    public function __get($key)
    {
        if ($this->has($key)) {
            return $this->context[$key];
        }

        return null;
    }

    /**
     * __call
     * @param String $key 访问 context[$key]
     * @param Array $dft 不存在则 返回 默认值 $dft[0]
     * @return Mixed
     */
    public function __call($key, $dft)
    {
        if ($this->has($key)) return $this->context[$key];
        if (empty($dft)) return null;
        return $dft[0];
    }

    /**
     * 判断 键 是否存在
     * @param String $key
     * @return Bool
     */
    public function has($key)
    {
        return isset($this->context[$key]);
    }



    /**
     * 静态工具
     */

    /**
     * 处理 $_GET 参数
     * 将 true|false|null|yes|no 转为 bool
     * 将 foo,bar 转为数组
     * @param Array $gets
     * @return Array 处理后的
     */
    public static function fix($gets=[])
    {
        if (!Is::nemarr($gets)) $gets = [];
        $rtn = [];
        foreach ($gets as $k => $v) {
            if (Is::nemstr($v) && (Is::ntf($v) || in_array(strtolower($v), ["yes","no"]))) {
                //将 true|false|null|yes|no 转为 bool
                if (Is::ntf($v)) {
                    eval("\$v = ".$v.";");
                    if ($v!==true) $v = false;
                } else {
                    $v = strtolower($v) === "yes";
                }
            } else if (strpos($v, ",") !== false) {
                $v = explode(",", $v);
            } /*else if (Is::explodable($v) !== false) {
                // foo,bar  foo|bar  foo;bar ... 转为数组
                $v = Arr::mk($v);
            }*/
            $rtn[$k] = $v;
        }
        return $rtn;
    }



}