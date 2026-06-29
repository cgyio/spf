<?php
/**
 * 框架 特殊工具类
 * 生成验证码，并写入 session 
 * 在需要验证码的时候，读取并检查
 * 
 * !! session 中的验证码是一次性的，生成后，只要执行 check() 将自动销毁
 */

namespace Spf\util;

class Vcode extends SpecialUtil
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
     * !! 覆盖父类静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = true;
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];

    //验证码长度
    protected static $vcodeLength = 6;
    //验证码是否包含特殊字符
    protected static $withSpecialChar = false;
    //验证码在 session 中的 key
    protected static $sessionKey = "spf_vcode";

    /**
     * 创建验证码
     * !! 通常由 验证码图片生成方法 调用
     * @param Int $len 可是手动指定长度，默认 null 使用 static::$vcodeLength
     * @return String 返回生成的 验证码
     */
    public static function generate($len=null)
    {
        if (!is_int($len)) $len = static::$vcodeLength;
        $vc = Str::nonce($len, static::$withSpecialChar);
        //保存到 session
        Session::set(static::$sessionKey, $vc);
        return $vc;
    }

    /**
     * 验证输入的验证码是否一致
     * !! 此方法一旦执行，无论是否匹配，一律清除 session 中储存的验证码
     * @param String $input 输入的验证码
     * @return Bool
     */
    public static function check($input=null)
    {
        $sk = static::$sessionKey;

        $rtn = function($res=false) use ($sk) {
            Session::del($sk);
            return $res;
        };

        if (!Is::nemstr($input)) return $rtn();

        $svc = Session::get($sk, null);
        if ($svc!==$input) return $rtn();

        return $rtn(true);
    }


}