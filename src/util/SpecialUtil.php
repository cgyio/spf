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
     * 在运行时，动态改变工具参数，然后执行 callback 完成后再回复 工具参数到原始值
     * @param Array $conf 运行时要指定的 工具参数
     * @param Closure $callback 回调函数
     * @return Mixed $callback 回调函数的返回值
     */
    public static function runtimeExec($conf=[], $callback=null)
    {
        //!! enable 参数也可以运行时修改
        //if (static::$enable!==true) return null;
        //必须指定回调函数
        if (!is_callable($callback)) return null;
        //是否指定了要修改 工具的参数
        $mod = Is::nemarr($conf);

        //执行回调前 修改工具参数
        if ($mod) {
            foreach ($conf as $uk => $uv) {
                //静态属性名称转换为 fooBar 形式
                $uk = Str::camel($uk, false);
                static::$$uk = $uv;
            }
        }

        //执行回调
        $rtn = $callback();

        //回调执行完成后，恢复工具参数到初始值
        if ($mod) {
            $init = static::$initConf;
            foreach ($init as $uk => $uv) {
                //静态属性名称转换为 fooBar 形式
                $uk = Str::camel($uk, false);
                static::$$uk = $uv;
            }
        }

        //返回执行结果
        return $rtn;
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