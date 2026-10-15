<?php
/**
 * 框架特殊工具类
 * 处理 AJAX 请求的相关工具类
 */

namespace Spf\util;

class Ajax extends SpecialUtil 
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
    public Static $enable = true;   //默认启用
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];



    //本次会话是否 ajax
    public $true = false;

    //是否预检请求
    public $isOptions = false;

    //缓存本次 ajax 会话的 origin
    public $origin = null;
    //本次 ajax 会话的 origin 是否在允许的 domain 列表中
    public $allowed = true;

    //来源 前一页
    public $referer = "";

    //处理 跨域请求 时生成的 响应头
    public $responseHeaders = [];

    /**
     * 构造
     * @return void
     */
    public function __construct()
    {
        $this->true = $this->isAjaxRequest();
        $this->referer = Server::referer();
        $this->isOptions = $this->isOptionsRequest();
        
        if ($this->true || $this->isOptions) {
            //如果是 ajax 请求，处理跨域
            $this->handleCors();
        }
    }

    /**
     * 判断是否 ajax request
     * @return Bool
     */
    protected function isAjaxRequest()
    {
        $accept = Server::get("Http-Accept", null);
        $xRequestedWith = Server::get("Http-X-Requested-With", null);    //$this->headers["x-Requested-With"] ?? null;
        if (!empty($xRequestedWith) && strpos(strtolower($xRequestedWith), "xmlhttprequest")!==false) return true;
        if (!empty($accept) && strpos($accept, "application/json")!==false) return true;
        return false;
    }

    /**
     * 判断当前请求是否 OPTIONS 预检
     * @return Bool
     */
    protected function isOptionsRequest()
    {
        return Server::get("request-method", "GET") === "OPTIONS";
    }

    /**
     * 处理 AJAX 跨域
     * 入口
     */
    protected function handleCors()
    {
        //获取 origin ，并 检查是否允许
        $this->checkRequestOrigin();
        //不允许直接 exit
        if ($this->allowed !== true) exit;

        //增加 CORS 头 到 responseHeaders[]
        $this->addCorsHeaders();
        //如果 request.method==OPTIONS 预检请求，直接响应
        $this->responseOptionsRequest();
    }

    /**
     * 处理 AJAX 跨域
     * 检查 request.origin 与 WEB_AJAXALLOWED 比较
     * @return Bool
     */
    protected function checkRequestOrigin()
    {
        $origin = Server::get("Http-Origin", null);
        //var_dump($origin);
        $this->origin = $origin;
        //框架启动参数中必须包含 [ "web" => [ "ajaxallowed" => ",foo.com,bar.com,localhost,..." ] ]
        $domains = WEB_AJAXALLOWED;
        $allowed = false;
        for ($i=0;$i<count($domains);$i++) {
            $dmi = $domains[$i];
            if ($origin==$dmi || strpos($origin, $dmi)!==false) {
                $allowed = true;
                break;
            }
        }
        //var_dump($origin);
        //var_dump($allowed);
        $this->allowed = $allowed;
        return $allowed;
    }

    /**
     * 如果当前请求是 Ajax 且 来源域名在 允许的 域名列表中
     * 生成 CORS 头，并添加到 responseHeaders 中
     * @return void
     */
    protected function addCorsHeaders()
    {
        if ($this->allowed === true) {
            $this->responseHeaders["Access-Control-Allow-Origin"] = $this->origin;
            $this->responseHeaders["Access-Control-Allow-Credentials"] = "true";
            $this->responseHeaders["Access-Control-Allow-Methods"] = "GET,POST,OPTIONS";
            $this->responseHeaders["Access-Control-Allow-Headers"] = "Content-Type,Authorization,Accept,X-Requested-With";
        }
    }

    /**
     * 处理 AJAX 跨域
     * !! 响应预检 request.method == OPTIONS
     * !! 直接返回 响应头 结束会话
     * @return void
     */
    protected function responseOptionsRequest()
    {
        if ($this->allowed === true) {
            if ($this->isOptions === true) {
                /* !! 已在 addCorsHeaders 方法中处理过 responseHeaders
                //$this->headers["Access-Control-Request-Method"] ?? "*";
                $method = Server::get("Http-Access-Control-Request-Method", "*");
                //$this->headers["Access-Control-Request-Headers"] ?? "GET,POST";
                $hds = Server::get("Http-Access-Control-Request-Headers", "GET,POST");
                $this->responseHeaders["Access-Control-Allow-Methods"] = $method;
                $this->responseHeaders["Access-Control-Allow-Headers"] = $hds;
                */

                //直接响应 OPTIONS 请求
                $hds = $this->responseHeaders;
                foreach ($hds as $k => $v) {
                    header("$k: $v");
                }
                exit;
            }
        }
    }
}