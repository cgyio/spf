<?php
/**
 * cgyio/spf 框架 异常处理类
 * 
 * 框架 Test 模块 异常处理
 * 归属于 框架内部模块异常
 */

namespace Spf\module\test;

use Spf\exception\BaseException;

class TestException extends BaseException 
{
    /**
     * 当前类型的 异常处理类 异常代码 code 前缀
     * spf 框架内部异常 code 前缀区间 000~099
     * 应用层自定义异常 code 前缀区间 100~999
     * !! 覆盖父类的静态属性
     */
    protected static $codePrefix = 99;   //相当于 099
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

            //通用异常 0990000
            "common"            => ["Test 模块异常", "可能的原因：%{1}%", 0],
        ]
    ];


}