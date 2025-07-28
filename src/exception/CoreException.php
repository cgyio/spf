<?php
/**
 * cgyio/spf 框架 异常处理类
 * 
 * 框架内部核心类 异常处理
 */

namespace Spf\exception;

use Spf\Exception;

class CoreException extends Exception 
{
    /**
     * 当前类型的 异常处理类 异常代码 code 前缀
     * spf 框架内部异常 code 前缀区间 000~099
     * 应用层自定义异常 code 前缀区间 100~999
     * !! spf 框架所有异常处理类，都继承自此类，必须在子类中 覆盖此静态属性
     */
    protected static $codePrefix = 1;   //相当于 001

    /**
     * 当前类型的 异常处理类 中定义的 异常信息，定义格式为：
     *  [
     *      "key" => [
     *          "错误标题", 
     *          "错误信息模板 %{n}%", 
     *          (int)错误代码 
     *      ],
     * 
     *      # 可以有更多层级，可通过 key-path 访问：foo/bar/jaz
     *      "foo" => [
     *          "bar" => [
     *              "jaz" => ["标题", "%{1}%", 1024],
     *              ...
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     * 主要定义可被捕获的 php 错误，和 php fatal 错误 等
     * 
     * !! 子类必须覆盖此静态属性
     * !! 应定义多语言
     */
    protected static $exceptions = [
        //zh-CN
        "zh-CN" => [

            //单例模式
            "singleton"             => ["核心单例错误", "核心单例无法创建，可能的原因：%{1}%",  1],     //code = 0010001
            
            //...

        ],
    ];
}