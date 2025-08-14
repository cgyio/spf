<?php
/**
 * 框架特殊工具类
 * 处理 Request|Response 请求|响应 头
 */

namespace Spf\util;

class Header extends SpecialUtil 
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

    //headers 参数数组 一维关联数组
    protected $context = [];

    /**
     * __get
     * 访问 context
     */
    public function __get($key)
    {
        /**
         * $header->ctx  -->  $header->context
         */
        if ($key === "ctx") return $this->context;

        /**
         * Header->AcceptLanguage  -->  context["Accept-Language"]
         */
        $snk = $this->fixKey($key);
        if (isset($this->context[$snk])) {
            return $this->context[$snk];
        }

        return null;
    }

    /**
     * 设置 context 项目
     * @param String|Array $key 要设置的项目  或  关联数组包含多个项目和值
     * @param Mixed $value 要设置的 值  或不指定
     * @return Bool
     */
    public function ctx($key, $value=null)
    {
        if (Is::nemarr($key) && Is::associate($key)) {
            $flag = true;
            foreach ($key as $k => $v) {
                $flag = $flag && $this->ctx($k, $v);
            }
            return $flag;
        }

        if (!Is::nemstr($key)) return false;
        $snk = $this->fixKey($key);
        $this->context[$snk] = $value;
        return true;
    }



    /**
     * 任意类型的 键名 转换为 标准 header 参数 键名形式 Foo-Bar
     *      foo_bar | fooBar | FooBar | foo-bar | foo/bar ...    -->  Foo-Bar
     * @param String $key 
     * @return String
     */
    protected function fixKey($key)
    {
        //转为 foo-bar 形式
        $snakeKey = Str::snake($key, "-");
        //转为 Foo-Bar
        return str_replace(" ", "-", ucwords(str_replace("-", " ", $snakeKey)));
    }
}