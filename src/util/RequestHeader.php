<?php
/**
 * 框架特殊工具类
 * 请求头处理类
 */

namespace Spf\util;

class RequestHeader extends Header 
{
    //$_SERVER 原始值
    public $origin = [];

    /**
     * 构造
     * 创建 Request Header 实例
     * @return void
     */
    public function __construct()
    {
        $this->origin = $_SERVER;
        $this->context = $this->getHeaders();
    }

    /**
     * getHeaders 获取请求头
     * @return Array
     */
    public function getHeaders()
    {
        $hds = [];
        if (function_exists("apache_request_headers")) {
            //Apache环境下
            $hds = apache_request_headers();
        } else {
            $hds = Server::pre("http");
        }
        return $hds;
    }
}