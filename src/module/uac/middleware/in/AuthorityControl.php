<?php
/**
 * Spf 框架 入站中间件
 * Uac 模块专用
 * AuthorityControl 执行用户权鉴过滤
 */

namespace Spf\module\uac\middleware\in;

use Spf\Middleware;
use Spf\Response;
use Spf\module\uac\UacException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;

class AuthorityControl extends Middleware 
{
    /**
     * 单例模式
     * !! 覆盖父类，具体中间件子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;

    /**
     * 缓存 权鉴不通过的 额外信息
     * 用于 exit 方法中，判断以何种方式退出
     */
    protected $deniedData = null;

    

    /**
     * 中间件的 核心方法，执行 入站|出站 过滤操作
     * 执行中间件逻辑，处理 Request|Response 实例，返回 是否过滤通过 的标记
     * !! 子类必须实现
     * @return Bool 当 此方法返回 false 时，将触发 中间件的 exit 终止响应 动作
     */
    public function handle()
    {
        $req = $this->Req();
        //当前 请求的 操作标识 
        $oprc = $req->getOprc();
        $oprn = $oprc["oprn"];

        //!! 如果请求的 操作 auth == false 表示不启用 权限限制，直接跳过此中间件
        if (is_bool($oprc["auth"]) && $oprc["auth"]===false) {
            return true;
        }

        //Uac 模块在启动时已经 初始解析了可能存在的 jwt-token
        $uac = $this->Mod("uac");
        //!! 检查 $uac->isLogin()，如果未能正确登录，统一报异常
        if ($uac->isLogin()!==true) {
            $this->deniedData = [
                //退出方式标记为 not_login
                "type" => "not_login",
                //明细数据为 jwt-token 解析结果
                "detail" => $uac->validate,
                //标记当前请求的 输出方式 api|view|src
                "export" => $oprc["export"],
            ];
            return false;
        }

        //如果用户正确登录了，开始权鉴操作，得到标准 AcResult 数组
        $ac = $uac->authCheck($oprn, false);
        if ($ac["grant"]!==true) {
            $this->deniedData = [
                //退出方式标记为 auth_denied
                "type" => "auth_denied",
                //明细数据为 AcResult 结果
                "detail" => $ac,
                //标记当前请求的 输出方式 api|view|src
                "export" => $oprc["export"],
            ];
            return false;
        }

        //权鉴通过
        return true;
    }

    /**
     * 中间件过滤方法 返回了 false 需要终止响应，将执行此方法
     * !! 覆盖父类
     * @return void
     */
    protected function exit()
    {
        //!! 根据 deniedData 数据，决定怎样退出此次会话
        $dd = $this->deniedData;
        if (!Is::nemaso($dd)) {
            exit;
        }

        //TODO：日志...

        //根据 deniedData 创建 UacException 这将终止本次响应
        $dtp = $dd["type"];
        if ($dtp==="not_login") {
            //调用 Uac 模块 强制抛出未登陆错误
            $this->Mod("uac")->throwException(true);
        } else {
            //权鉴拒绝
            $ddt = $dd["detail"];
            throw new UacException($ddt["msg"]." [ OPRN = ".$ddt["oprn"]." ]", "auth/denied");
        }

        exit;
    }

}