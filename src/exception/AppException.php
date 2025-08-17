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
                //请求为指向任何 App 应用
                "missapp"           => ["请求的应用不存在", "当前请求未能匹配到应用，URI = %{1}%",  1],     //code = 0020001
                //未能匹配到 任何请求的操作
                "missoprc"          => ["请求的操作不存在", "当前请求未能匹配到操作，URI = %{1}%",  2],     //code = 0020002
            ],

            //Request 请求相关
            "request" => [

            ],

            //Response 响应相关
            "response" => [
                //响应状态码 错误
                "code"              => ["响应状态码错误", "设置了不正确的响应状态码：%{1}%", 11],
                //响应类型 不受支持
                "unsupport"         => ["不支持的响应类型", "响应方法返回了一个不支持的响应类型：%{1}%",    12],    //code = 0020012
                //exporter 类创建失败
                "exporter"          => ["响应输出类创建失败", "当前的响应输出类：%{1}%",    13],    //code = 0020013

                //响应失败
                "fail"              => ["响应失败", "响应流程发生错误，可能的原因：%{1}%",  15],    //code = 0020015
            ],

            //App 应用相关
            "app" => [

                //执行响应方法错误
                "response"          => ["执行响应方法失败", "执行响应方法发生错误，可能的原因：%{1}%",  21],    //code = 0020021
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
        //!! 所有 App 应用类异常 都必须终止响应
        return true;
    }
}