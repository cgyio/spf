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



}