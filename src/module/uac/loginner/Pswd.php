<?php
/**
 * SPF-Uac 权限控制模块
 * 用户登录方式处理类 Loginner
 * 专门处理 账号密码的 登录方式
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * 
 * 此类型登录方式，必须由前端表单 填写并提交 账号和密码
 *      账号密码的字段名 默认为 name 和 pswd
 *      !! 可以在应用的 app/app_name/module/uac/loginner 路径下 创建扩展的 Pswd 类，并自定义 Pswd::$fields[] 手动指定对应的字段名
 * 
 *      用户账号 支持输入： $usr->name 用户名称  或  $usr->uuid 用户的 uuid
 * 
 */

namespace Spf\module\uac\loginner;

use Spf\module\Uac;
use Spf\module\uac\Loginner;
use Spf\module\uac\UacException;
use Spf\module\orm\Record;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Session;
use Spf\util\Vcode;

class Pswd extends Loginner 
{
    /**
     * !! 子类必须定义
     */
    //此 数据模型(表) 参数解析类的 名称 foo_bar
    protected static $loginner = "pswd";
    //指定此登录方式的 中文名，用于返回错误信息
    protected static $loginTitle = "账号密码";

    //允许的尝试次数
    //!! 可在应用路径下扩展此类，并修改此参数
    protected static $maxTryTimes = 5;
    //尝试次数在 session 中的 key
    protected static $tryTimesKeyInSession = "spf_uac_pswd_login_try_times";
    


    /**
     * !! Loginner 子类必须实现的 登录方法
     * 执行用户登录
     * @return Array 返回标准的 用户登录结果数组： 与 $loginner->stdLoginResult[] 结构一致
     *      登录成功，则返回：
     *          [
     *              "success" => true,
     *              "msg" => "",
     *              "uuid" => "....",
     *              "loginner" => "登录器名称 pswd|scan|...",
     *              "extra" => [ ... 额外参数 ... ],
     *          ]
     *      登录失败，则返回：
     *          [
     *              "success" => false,
     *              "msg" => "返回给前端的 错误信息",
     *              "uuid" => null,
     *              "loginner" => "登录器名称 pswd|scan|...",
     *              "extra" => [ ... 额外参数 ... ],
     *          ]
     */
    public function doLogin()
    {
        //准备返回值
        $rtn = Arr::copy($this->stdLoginResult);

        //设置并返回错误信息
        $errRtn = function($msg) use ($rtn) {
            return Arr::extend($rtn, [
                "success" => false,
                "msg" => $msg,
                "uuid" => null,
                "loginner" => static::$loginner,
            ]);
        };

        //!! 首先检查尝试次数
        if (static::checkTryTimes()!==true) {
            $msg = "[too-much-times] 已尝试了很多次，过会再试";
            throw new UacException(static::$loginTitle.",".$msg, "login/failed");
            return $errRtn($msg);
        }
        //错误信息包含 try-times
        $tryTimes = "[已尝试：".Session::get(self::$tryTimesKeyInSession)." 次]";

        //获取前端传回的 账号密码
        $ap = $this->getAccountPswd();
        $name = $ap["name"] ?? null;
        $pswd = $ap["pswd"] ?? null;
        $vcode = $ap["vcode"] ?? null;

        //!! 如果存在验证码，则先检查验证码
        if (!is_null($vcode) && $vcode!=="") {
            if (Vcode::check($vcode)!==true) {
                if (!Is::nemstr($vcode)) {
                    $msg = "[empty-vcode] 验证码不能为空";  // $tryTimes";
                } else {
                    $msg = "[error-vcode] 错误的验证码";    // $tryTimes";
                }
                throw new UacException(static::$loginTitle.",".$msg, "login/failed");
                return $errRtn($msg);
            }
        }

        //如果传入的 账号密码 为空
        if (!Is::nemstr($name) || !Is::nemstr($pswd)) {
            $msg = "[empty-name-or-pswd] 登录名称或密码为空";   // $tryTimes";
            throw new UacException(static::$loginTitle.",".$msg, "login/failed");
            return $errRtn($msg);
        }

        //查询可能存在的 账号
        $usr = $this->uac->account
            ->nojoin()
            //$pswd 已经过 MD5
            ->wherePswd($pswd)
            //$name 支持查询 name 或 uuid 字段
            ->where([
                "OR #pswd login" => [
                    "name" => $name,
                    "uuid" => $name
                ]
            ])
            //必须是生效状态
            ->enabled()
            //只查询一条记录
            ->get();
        
        //未获取到有效用户记录
        if (!$usr instanceof Record) {
            $msg = "[error-name-or-pswd] 错误的登录名称或密码"; // $tryTimes";
            throw new UacException(static::$loginTitle.",".$msg, "login/failed");
            return $errRtn($msg);
        }

        //登录正常
        $uuid = $usr->uuid;
        unset($usr);

        //清空 tryTimes session
        static::clearTryTimes();

        //返回登录成功数据
        return Arr::extend($rtn, [
            "success" => true,
            "msg" => "登录成功",
            "uuid" => $uuid,
            "loginner" => static::$loginner,
        ]);
    }



    /**
     * 工具方法
     */

    /**
     * 检查尝试次数，判断是否超出，未超出则递增并保存 尝试次数
     * @return Bool 已超出返回 false   否则返回 true
     */
    protected static function checkTryTimes()
    {
        $maxTry = static::$maxTryTimes;
        $tryk = static::$tryTimesKeyInSession;
        $trys = Session::get($tryk, 0);

        //已超出最大尝试次数
        if ($trys>=$maxTry) return false;

        //tryTimes 自增并保存
        $trys += 1;
        Session::set($tryk, $trys);
        return true;
    }

    /**
     * 清空 tryTimes session
     * @return Bool
     */
    public static function clearTryTimes()
    {
        Session::del(static::$tryTimesKeyInSession);
        return true;
    }

    /**
     * 从前端传回的数据中获取 账号密码
     * !! 可能返回 null 需要在 doLogin 中判断
     * !! pswd 会自动 MD5
     * !! 如果 post 中包含验证码字段，需要一起获取
     * @return Array|null 返回 [ "name"=>"登陆账号", "pswd"=>"登陆密码", "vcode"=>"此键可能不存在" ]
     */
    public function getAccountPswd()
    {
        //Request 实例
        $req = $this->uac->Req();
        //表单对应字段名
        $nameField = static::$fields["name"];
        $pswdField = static::$fields["pswd"];
        $vcodeField = static::$fields["vcode"];
        
        $posts = $req->posts;
        $inps = $req->inputs;

        //首先尝试 从 $req->posts $_POST 中获取
        $name = $posts->$nameField;
        $pswd = $posts->$pswdField;
        if (!Is::nemstr($name)) $name = null;
        if (!Is::nemstr($pswd)) $pswd = null;
        if (is_null($name) && is_null($pswd)) {
            //$_POST 中未能获取，尝试 php://inputs
            $name = $inps->$nameField;
            $pswd = $inps->$pswdField;
            if (!Is::nemstr($name)) $name = null;
            if (!Is::nemstr($pswd)) $pswd = null;
        }
        //如果前端未传回经过 MD5 的 pswd
        if (Is::nemstr($pswd) && strlen($pswd)!==32) $pswd = md5($pswd);

        $rtn = [
            "name" => $name,
            "pswd" => $pswd
        ];

        //!! 可能存在的 验证码字段
        if ($posts->has($vcodeField)===true) {
            $rtn["vcode"] = $posts->$vcodeField;
        } else if (!is_null($inps->ctx($vcodeField, null))) {
            $rtn["vcode"] = $inps->$vcodeField;
        }

        return $rtn;
    }
}