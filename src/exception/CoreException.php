<?php
/**
 * cgyio/spf 框架 异常处理类
 * 
 * 框架内部核心类 异常处理
 */

namespace Spf\exception;

class CoreException extends BaseException 
{
    /**
     * 当前类型的 异常处理类 异常代码 code 前缀
     * spf 框架内部异常 code 前缀区间 000~099
     * 应用层自定义异常 code 前缀区间 100~999
     * !! 覆盖父类的静态属性
     */
    protected static $codePrefix = 1;   //相当于 001
    //异常码(不带前缀) 的 位数，0 为 不指定位数
    protected static $codeDigit = 4;

    /**
     * 当前类型的 异常处理类 中定义的 异常信息
     * 主要针对 框架内部核心类的 异常信息
     * 
     * !! 覆盖父类的静态属性
     * !! 应定义多语言
     */
    protected static $exceptions = [
        //zh-CN
        "zh-CN" => [

            //初始化错误
            "initialize" => [
                //核心类实例化失败
                "core"              => ["核心类实例化失败", "%{1}%",  1],     //code = 0010001
                //核心配置类实例化失败
                "config"            => ["核心配置类实例化失败", "%{1}%",  2],     //code = 0010002
                //核心类初始化
                "init"              => ["核心类初始化失败", "%{1}%",  3],     //code = 0010003
            ],
            
            
            
            //...

        ],
    ];
    


    /**
     * 判断当前异常是否需要终止响应
     * !! 子类必须覆盖此方法，实现不同类型异常的 退出 判断
     * @return Bool
     */
    public function needExit()
    {
        //!! 所有 核心类异常 都必须终止响应
        return true;
    }
}