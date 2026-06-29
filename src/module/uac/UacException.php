<?php
/**
 * cgyio/spf 框架 异常处理类
 * 
 * 框架 Uac 模块 异常处理
 * 归属于 框架内部模块异常
 * 具体的应用类，如果要自定义异常处理，应继承此类
 */

namespace Spf\module\uac;

use Spf\exception\BaseException;

class UacException extends BaseException 
{
    /**
     * 当前类型的 异常处理类 异常代码 code 前缀
     * spf 框架内部异常 code 前缀区间 000~099
     * 应用层自定义异常 code 前缀区间 100~999
     * !! 覆盖父类的静态属性
     */
    protected static $codePrefix = 31;   //相当于 031
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

            //init 初始化异常
            "initialize" => [
                //未知异常 0310000
                "unknown"       => ["Uac 模块无法初始化", "可能的原因：%{1}%", 0],
                //注入 iso prefilter 失败 0310001
                "injectiso"     => ["向 Orm 数据模型注入 iso 相关 prefilter 失败", "可能的原因：%{1}%", 1],
            ],

            //jwt 相关异常
            "jwt"  => [
                //初始化失败 0310010
                "unknown"       => ["Jwt 无法初始化", "可能的原因：%{1}%", 10],
                //创建 secret 文件失败 0310011
                "nosecretdir"   => ["Jwt 无法初始化", "无法获取 jwt-secret 文件路径，可能的原因：%{1}%", 11],

                //已登出 0310015
                "islogout"      => ["用户已登出", "用户已手动登出", 15],
                //未登录 0310016
                "empty_token"   => ["用户还未登录", "Token 为空", 16],
                //登录已过期 0310017
                "expired"       => ["用户登录状态已过期", "Token 已过期", 17],
                //被篡改 0310018
                "error_token"   => ["登录信息可能被篡改", "Token 未通过验证", 18],
                //错误来源 0310019
                "different_audience"   => ["登录信息可能被盗用", "Token 与请求来源不一致", 19],
            ],

            //login|logout 异常
            "login" => [
                //unsupport 不支持的登录方式 0310020
                "unsupport"     => ["不支持的登录方式", "不支持以 %{1}% 方式登录", 20],
                //登录失败 0310021
                "failed"        => ["登录失败", "以 %{1}% 方式登录失败，可能的原因：%{2}%", 21],
            ],
            
            //authCheck 异常
            "auth" => [
                //denied 0310030
                "denied"        => ["请求已被拒绝", "%{1}%", 30],
            ],
            
            
            //...

        ],
    ];
    


    /**
     * 判断当前异常 是 框架内部异常 还是 
     * 如果是 框架内部异常，在输出异常信息时，需要同时输出 500 状态码
     * 应用层异常，输出 200 状态码
     * !! 覆盖父类
     * @return Bool true 表示 框架内部异常 false 表示 应用层异常
     */
    public function isInnerException()
    {
        $xpath = $this->ctx("xpath");
        //这些异常是应用级别
        $excs = [
            "jwt/islogout", "jwt/empty_token", "jwt/expired", "jwt/error_token", "jwt/different_audience",
            "login/failed",
            "auth/denied",
        ];

        return !in_array($xpath, $excs);
    }

    /**
     * 判断当前异常是否需要终止响应
     * !! 子类必须覆盖此方法，实现不同类型异常的 退出 判断
     * @return Bool
     */
    public function needExit()
    {
        //!! 所有 Orm 模块异常 都必须终止响应
        return true;
    }
}