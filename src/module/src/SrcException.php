<?php
/**
 * cgyio/spf 框架 异常处理类
 * 
 * 框架 Src 模块 异常处理
 * 归属于 框架内部模块异常
 * 具体的应用类，如果要自定义异常处理，应继承此类
 */

namespace Spf\module\src;

use Spf\exception\BaseException;

class SrcException extends BaseException 
{
    /**
     * 当前类型的 异常处理类 异常代码 code 前缀
     * spf 框架内部异常 code 前缀区间 000~099
     * 应用层自定义异常 code 前缀区间 100~999
     * !! 覆盖父类的静态属性
     */
    protected static $codePrefix = 20;   //相当于 020
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

            //Resource 资源类异常
            "resource"  => [
                //解析 URI 异常
                "parse"             => ["无法解析资源请求", "解析资源请求错误，可能的原因：%{1}%", 1],  //code = 0200001
                //资源类实例化失败
                "instance"          => ["资源实例创建失败", "无法创建 %{1}% 资源，可能的原因：%{2}%", 2],  //code = 0200002
                //读取(生成)资源内容
                "getcontent"        => ["无法读取资源", "无法读取(生成)资源的内容或信息，可能的原因：%{1}%",  3],     //code = 0200003
                //输出资源内容
                "export"            => ["无法输出资源", "无法输出资源内容，可能的原因：%{1}%",  4],     //code = 0200004
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
        //!! 所有 Src 模块异常 都必须终止响应
        return true;
    }
}