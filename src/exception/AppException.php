<?php
/**
 * cgyio/spf 框架 异常处理类
 * 
 * 框架 App 应用类 异常处理
 * 具体的应用类，如果要自定义异常处理，应继承此类
 */

namespace Spf\exception;

class AppException extends BaseException 
{
    /**
     * 当前类型的 异常处理类 异常代码 code 前缀
     * spf 框架内部异常 code 前缀区间 000~099
     * 应用层自定义异常 code 前缀区间 100~999
     * !! 覆盖父类的静态属性
     */
    protected static $codePrefix = 2;   //相当于 002
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

            //Route 路由相关
            "route"  => [
                //初始化错误
                "init"              => ["应用路由表初始化失败", "无法创建应用路由表，可能的原因：%{1}%",  1],   //code = 0020001

                //请求为指向任何 App 应用
                "missapp"           => ["当前请求未能匹配到应用", "无法创建应用实例，可能的原因：%{1}%",  5],   //code = 0020005
            ],
            
            
            
            //...

        ],
    ];
}