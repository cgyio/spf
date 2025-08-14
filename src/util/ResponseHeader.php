<?php
/**
 * 框架特殊工具类
 * 响应头处理类
 */

namespace Spf\util;

class ResponseHeader extends Header 
{
    //headers 参数数组 一维关联数组
    protected $context = [
        //默认值
        "Content-Type" => "text/html; charset=utf-8",
        "Access-Control-Allow-Origin" => "*",
        "Access-Control-Allow-Headers" => "*",
        "Access-Control-Allow-Methods" => "POST,GET,OPTIONS",
        //"Access-Control-Allow-Credentials" => "true",
        "User-Agent" => "Spf/Response",
        "X-Framework" => "cgyio/spf",
    ];

    /**
     * 构造
     * 创建 Response Header 实例
     * @param Array $hds 初始值，需要写入 context
     * @return void
     */
    public function __construct($hds=[])
    {
        if (Is::nemarr($hds)) {
            $this->ctx($hds);
        }
    }

    /**
     * 发送 headers 开始输出
     * @param Mixed $key 关联数组 或 键名
     * @param Mixed $val 
     * @return $this
     */
    public function sent($key = [], $val = null)
    {
        //如果 headers 已发送，返回
        if (headers_sent() === true) return $this;
        if (!empty($key)) {
            if (Is::associate($key)) {
                foreach ($key as $k => $v) {
                    header("$k: $v");
                }
            } else if (is_string($key) && is_string($val)) {
                header("$key: $val");
            }
        } else {
            foreach ($this->context as $k => $v) {
                header("$k: $v");
            }
        }
        return $this;
    }
}