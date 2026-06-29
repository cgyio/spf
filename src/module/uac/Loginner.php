<?php
/**
 * SPF-Uac 权限控制模块
 * 可扩展的 用户登录登录器 资源类 Loginner
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * 
 * Spf 框架默认的 登录方式 包括：
 *      module\uac\loginner\Pswd            普通账号密码登录
 *      module\uac\loginner\Scan            普通扫码登陆(一般通过 微信公众号 扫码登陆)
 * 
 * 各 应用 可以扩展自有的 登录方式
 * !! Loginner 子类必须保存在：
 * !!   应用级扩展：        app/app_name/module/uac/loginner 路径下
 * !!   项目级扩展：        webroot/module/uac/loginner 路径下
 */

namespace Spf\module\uac;

use Spf\module\Uac;
use Spf\module\uac\UacException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

use Spf\traits\ExpandableResource;

abstract class Loginner
{
    //引用  可扩展底层资源类  特征
    use ExpandableResource;
    //!! trait 中要求的，子类不要覆盖
    protected static $exresName = "loginner";
    protected static $exresClassPath = "module/uac";
    public static $isCollected = false;
    
    /**
     * 当某个 数据模型参数解析子类被 collect 收集时，将此解析类附加到 Parser::$parsers[] 解析顺序的 末尾
     * !! trait 中要求的，子类根据需要覆盖
     * !! 可以在特定的解析类中，自行处理 解析顺序，例如插入某个其他解析类之前或之后
     * @return Bool
     */
    protected static function whenCollect()
    {

        return true;
    }



    /**
     * !! 子类必须定义
     */
    //此 数据模型(表) 参数解析类的 名称 foo_bar
    protected static $loginner = "";
    //指定此登录方式的 中文名，用于返回错误信息
    protected static $loginTitle = "";
    //!! 如果需要，可以设置 账号密码 在前端表单中的 字段名
    public static $fields = [
        "name" => "name",
        "pswd" => "pswd",
        //!! 可能存在验证码字段
        "vcode" => "vcode",
    ];

    //必须依赖 Uac 模块实例
    public $uac = null;

    //标准的 用户登录结果数组
    protected $stdLoginResult = [
        "success" => false,
        "msg" => "",
        "uuid" => null,
        "loginner" => "",
        "extra" => [],
    ];

    /**
     * 构造
     * @param Uac $uac 当前的 Uac 模块实例
     * @return void
     */
    protected function __construct($uac)
    {
        if (!$uac instanceof Uac) return null;
        $this->uac = $uac;
    }

    /**
     * 登录器执行用户登录的 入口方法
     * !! 由 Uac 模块在 login 接口中调用
     * !! 必须通过 基类调用： $uuid = Loginner::login(登录器子类名称foo_bar)
     * @param String $loginner 要调用的 登录器子类名 foo_bar
     * @return String|Bool 登录成功则返回有效的 用户 uuid，否则返回 false
     * !! 异常信息由 各 Loginner 子类在其对应的 doLogin 方法中 throw，前端将会接收到异常信息
     */
    final public static function login($loginner)
    {
        //获取登录器类全称
        $log = self::support($loginner);
        if ($log===false || !class_exists($log)) {
            //!! 不支持的 登录方式
            throw new UacException($loginner, "login/unsupport");
            return false;
        }

        //Uac 实例
        $uac = Uac::$current;

        //实例化 登录器
        $logins = new $log($uac);

        /**
         * 执行各 登录器子类的 doLogin 方法
         * !! 如果登录失败，由各子类自行 throw exception
         */
        $logres = $logins->doLogin();

        //不成功 返回 false
        if (!Is::nemaso($logres) || !isset($logres["success"]) || $logres["success"]!==true) return false;
        //登录成功 返回 用户的 uuid
        return $logres["uuid"];
    }
    


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
    abstract public function doLogin();

}