<?php
/**
 * 工具类
 * UUIDv7  创建 解析
 */

namespace Spf\util;

class Uuid extends Util 
{
    /**
     * 根据传入的 时间戳 创建一个 UUIDv7
     * 标准 RFC 9562，时间有序，MySQL主键最优
     * @param Int $timestamp 可以传入 时间戳 或 时间字符串，默认 使用当前时间戳
     * @return String
     */
    public static function v7($timestamp=null)
    {
        // 1. 处理时间戳，统一转为 毫秒级时间戳
        if ($timestamp === null) {
            // 使用当前毫秒时间
            $ms = (int)(microtime(true) * 1000);
        } else {
            if (is_numeric($timestamp)) {
                $num = (float)$timestamp;
                if ($num > 9999999999) {
                    // 毫秒时间戳
                    $ms = (int)$num;
                } else {
                    // 秒时间戳
                    $ms = (int)($num * 1000);
                }
            } else {
                // 日期字符串 转时间戳
                $ts = strtotime((string)$timestamp);
                if ($ts === false) {
                    $ts = time();
                }
                $ms = $ts * 1000;
            }
        }

        // 2. 生成 48 位时间戳（固定12位十六进制）
        $timeHex = substr(str_pad(dechex($ms), 12, '0', STR_PAD_LEFT), 0, 12);
    
        // 3. 生成安全随机数（固定 10 字节 = 20 位十六进制）
        $rand = bin2hex(random_bytes(10));
    
        // 4. 严格按 UUIDv7 格式拼接（确保总长度 36）
        return vsprintf('%08s-%04s-7%03s-%04x-%012s', [
            substr($timeHex, 0, 8),
            substr($timeHex, 8, 4),
            substr($rand, 0, 3),
            0x8000 | (hexdec(substr($rand, 3, 4)) & 0x3FFF),
            substr($rand, 7, 12)
        ]);
    }

    /**
     * 判断一个字符串，是否是标准的 UUIDv7
     * @param String $uuid
     * @return Bool
     */
    public static function isV7($uuid=null)
    {
        if (!Is::nemstr($uuid)) return false;
        if (strlen($uuid)!==36) return false;

        // UUIDv7 正则：第15位必须是 7
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * UUIDv7 --> 32 位纯字符串 （即 十六进制数）
     * @param String $uuid
     * @return String|null
     */
    public static function v7Hex($uuid=null)
    {
        if (!self::isV7($uuid)) return null;
        
        // 去掉横杠，返回32位纯十六进制
        return str_replace('-', '', $uuid);
    }

    /**
     * UUIDv7 --> 128位二进制数
     * @param String $uuid
     * @return Int|null
     */
    public static function v7Bin($uuid=null)
    {
        $hex = self::v7Hex($uuid);
        if (!$hex) return null;

        return hex2bin($hex);
    }

    /**
     * UUIDv7 --> 十进制数
     * @param String $uuid
     * @return String|null 十进制大数（PHP int装不下，返回字符串
     */
    public static function v7Dec($uuid=null)
    {
        $hex = self::v7Hex($uuid);
        if (!$hex) return null;

        return bcmul(hexdec(substr($hex, 0, 16)), '18446744073709551616') + hexdec(substr($hex, 16));
    }

    /**
     * UUIDv7 --> unix 时间戳，秒级
     * @param String $uuid
     * @return Int|null
     */
    public static function v7Ts($uuid)
    {
        $ms = self::v7Ms($uuid);
        return $ms ? (int)($ms / 1000) : null;
    }

    /**
     * UUIDv7 --> unix 时间戳，毫秒级
     * @param String $uuid
     * @return Int|null
     */
    public static function v7Ms($uuid)
    {
        if (!self::isV7($uuid)) return null;

        // 提取前 12 位十六进制时间戳
        $hex = self::v7Hex($uuid);
        $timeHex = substr($hex, 0, 12);
        
        return hexdec($timeHex);
    }

    /**
     * 将传入的 多个 UUIDv7 按最后一个参数(asc|desc) 进行排序
     * @param Array $uuids 传入的多个 UUIDv7
     * @param String $order 升序或降序，asc 或 desc，如果最后一个参数不是 asc|desc  则 默认 asc
     * @return Array 排序后的 $uuids
     */
    public static function v7Sort($uuids=[], $order="asc")
    {
        if (!is_array($uuids) || empty($uuids)) return [];

        // 过滤无效 UUIDv7
        $valid = array_filter($uuids, [__CLASS__, 'isV7']);

        // 排序
        if (strtolower($order) === 'desc') {
            rsort($valid);
        } else {
            sort($valid);
        }

        return array_values($valid);
    }
}