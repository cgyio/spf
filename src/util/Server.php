<?php
/**
 * 工具类
 * $_SERVER 工具
 */

namespace Spf\util;

class Server extends Util 
{

    /**
     * 读取 $_SERVER
     * @param String $key
     * @param Mixed $dft
     * @return String  or  null
     */
    public static function get($key, $dft = null)
    {
        if (self::has($key)!==true) return $dft;
        $key = self::key($key);
        return $_SERVER[$key];
    }

    /**
     * 判断 $_SERVER 是否包含
     * @param String $key
     * @return Bool
     */
    public static function has($key)
    {
        $key = self::key($key);
        return isset($_SERVER[$key]);
    }

    /**
     * 按前缀获取 $_SERVER 数据
     * Server::pre("http") = [
     *      "Host" => "...",
     *      "Accept-Language" => "...",
     *      ...
     * ]
     * @param String $pre 前缀名 foo  -->  返回所有 $_SERVER["FOO_***"]
     * @return Array
     */
    public static function pre($pre = "")
    {
        if (!Is::nemstr($pre)) return [];
        $pre = strtoupper($pre);
        if ("_" !== substr($pre, -1)) $pre .= "_";
        $serv = $_SERVER;
        $arr = [];
        foreach ($serv as $k => $v) {
            if ($pre !== substr($k, 0, strlen($pre))) continue;
            $kk = strtolower(substr($k, strlen($pre)));
            $kk = ucwords(str_replace("_", " ", $kk));
            $kk = str_replace(" ", "-", $kk);
            $arr[$kk] = $v;
        }
        return $arr;
    }

    /**
     * $_SERVER 键名处理
     * foo-bar  -->  FOO_BAR
     * fooBar  -->  FOO_BAR
     * @param String $key
     * @return String 
     */
    public static function key($key)
    {
        $key = Str::snake($key, "_");
        $key = Str::replace("-", "_", $key);
        $key = strtoupper($key);
        return $key;
    }

    /**
     * 获取请求的 来源 网址
     * @return String 未找到 返回 ""
     */
    public static function referer()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? "";
    
        // 简单验证：检查是否为合法URL格式（减少伪造的明显错误值）
        if ($referer && filter_var($referer, FILTER_VALIDATE_URL)) {
            return $referer;
        }
        return "";
    }

    /**
     * 获取请求的 来源 IP
     * @return String 请求来源 IP (客户端IP) 默认返回服务器IP（通常为127.0.0.1）
     */
    public static function ip() {
        // 可能存储真实IP的字段（按优先级排序）
        $ipHeaders = [
            'HTTP_X_FORWARDED_FOR',  // 代理链中的所有IP（第一个为真实IP）
            'HTTP_CLIENT_IP',        // 代理客户端的IP
            'HTTP_X_REAL_IP',        // Nginx等服务器常用的真实IP字段
            'REMOTE_ADDR'            // 最后一个代理服务器的IP（最可靠但可能非真实）
        ];
        
        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                // 处理多个IP的情况（如X-Forwarded-For可能包含多个IP，用逗号分隔）
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                // 验证IP格式
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        // 所有字段都获取失败时，返回默认IP
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
}