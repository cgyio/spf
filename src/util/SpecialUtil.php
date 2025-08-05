<?php
/**
 * 框架 特殊工具类 基类
 */

namespace Spf\util;

class SpecialUtil
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
    public Static $enable = false;
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];

    /**
     * 将 框架启动参数中 针对此工具的参数 写入(覆盖) 此类的 静态属性中
     * !! 此方法应在 Env::$current->config 实例化后执行
     * @param Array $conf 启动参数中 针对此工具的参数
     * @return void
     */
    public static function setInitConf($conf=[])
    {
        //检查输入的 启动参数
        if (!Is::nemarr($conf) || !isset($conf["enable"]) || !is_bool($conf["enable"])) {
            return;
        }

        //var_dump($conf);
        //缓存 框架启动参数中 针对此工具的参数
        static::$initConf = $conf;
        
        //将获取到的 启动参数 写入(覆盖) 此工具类的静态属性中
        $enable = $conf["enable"];
        //如果启动参数中 关闭了此工具，直接退出
        if ($enable !== true) {
            static::$enable = false;
            return;
        }
        //写入(覆盖) 静态属性
        foreach ($conf as $uk => $uv) {
            //静态属性名称转换为 fooBar 形式
            $uk = Str::camel($uk, false);
            static::$$uk = $uv;
        }
    }

    /**
     * forDev
     * 输出此工具类 在当前会话下的 启动参数
     * @return Array
     */
    public static function staticProperties()
    {
        //获取此工具类的 启动参数
        $uc = static::$initConf;
        if (!Is::nemarr($uc)) return;

        $ic = [];
        foreach ($uc as $uk => $uv) {
            //静态属性名称转换为 fooBar 形式
            $uk = Str::camel($uk, false);
            $ic[$uk] = static::$$uk;
        }
        return $ic;
    }
}