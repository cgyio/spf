<?php
/**
 * 框架模块类
 * Uac 权限控制模块
 */

namespace Spf\module;

use Spf\Module;
use Spf\module\uac\Jwt;
use Spf\module\uac\Loginner;
use Spf\module\uac\UacException;
use Spf\module\orm\Db;
use Spf\module\orm\Model;
use Spf\module\orm\Record;
use Spf\module\orm\RecordSet;
use Spf\module\orm\types\Iso;
use Spf\module\orm\config\DbConfig;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Uuid;

class Uac extends Module 
{
    /**
     * 单例模式
     * !! 覆盖父类，具体模块子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    /**
     * 模块的元数据
     * !! 实际模块类必须覆盖
     */
    //模块的说明信息
    public $intr = "Uac权限控制模块";
    //模块的名称 类名 FooBar 形式
    public $name = "Uac";

    //关联的 权限数据库实例
    public $db = null;

    //jwt 处理类
    public $jwt = null;
    //jwt-token 解析结果缓存
    protected $jwtValidate = [];

    //当前登录的 用户实例
    public $usr = null;

    //Uac 模块支持的 登录方式
    protected $supportLoginTypes = [
        "pswd",         //普通账号密码登录
        "scan",         //扫码登录
    ];



    /**
     * Uac 模块启用后，将在实例化后，立即执行此 初始化操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function initModule()
    {
        //获取 权限数据库实例
        $this->db = $this->Mod("orm")->db($this->config->ctx("orm/dbn"));

        //!! 将 iso 参数注入 当前 Orm 模块中所有可用数据库中的所有模型 的 prefilter 前置查询条件中
        $injectUacReady = $this->injectIsoPrefilterToUacDb();
        $injectOrmReady = $this->injectIsoPrefilterToOrmDb();

        //jwt 处理类
        $this->jwt = new Jwt($this);
        $jwtReady = $this->jwt instanceof Jwt;

        if (!($injectUacReady && $injectOrmReady && $jwtReady)) {
            //初始化失败
            throw new UacException("iso-prefilter 注入失败 或 Jwt 无法初始化", "initialize/unknown");
            return false;
        }

        //初始解析 jwt-token 生成登录用户实例
        $this->initLogin();

        return true;
    }



    /**
     * !! 权鉴入口方法
     * !! 调用 Account Record 实例方法 authCheck
     * @param String $oprn 标准操作标识，如果不指定，则使用当前请求的 操作信息
     * @param Bool $silence 是否静默失败，如果为 true 则只会返回 true|false ，默认 false 返回标准 AcResult[] 数组，包含详细信息
     * @return Array|Bool
     */
    final public function authCheck($oprn=null, $silence=false)
    {
        if ($this->isLogin()!==true) {
            return $silence ? false : $this->rtnAcResult(null, false, "用户还未登录");
        }
        
        //调用 $this->usr->authCheck()
        return $this->usr->authCheck($oprn, $silence);
    }



    /**
     * login|logout 方法
     */

    /**
     * Uac 模块启动时，首次解析 jwt-token 尝试登陆
     * @return Bool
     */
    protected function initLogin()
    {
        //解析 可能存在的 jwt-token
        $vali = $this->jwt->validate();
        //缓存结果
        $this->jwtValidate = $vali;
        $success = $vali["success"] ?? false;
        //如果解析不成功，则抛出必须抛出的异常，未登录 或 登录过期的情况不要抛异常
        if (!$success) {
            $this->throwException(false);
            return false;
        }
        
        //如果 jwt-token 存在且有效 则 创建当前登录账号的 实例
        $usr = $this->loginAccount();
        if (!$usr instanceof Record) return false;

        //一切正常
        return true;
    }

    /**
     * 如果 jwt-token 解析正常，则从 payload 中解析并创建 登录用户的 实例
     * @return Record|null 如果未能获取到有效的用户记录实例，返回 null
     */
    public function loginAccount()
    {
        //如果 $this->usr 已存在
        if ($this->usr instanceof Record) return $this->usr;

        //从 jwt-token 中获取
        $vali = $this->jwtValidate;
        $success = $vali["success"] ?? false;
        if (!$success) return null;
        $payload = $vali["payload"] ?? [];
        $uuid = $payload["uuid"] ?? null;
        $usr = $this->getAccountByUuid($uuid);
        //未获取到有效的用户记录，报账号异常
        if (!$usr instanceof Record) {
            throw new UacException("Token,未能获取账号信息，此账号可能存在问题，请联系管理员", "login/failed");
            return null;
        }

        //!! 检查 手动登出标记 isLogout，如果存在则返回 已登出异常
        if (isset($usr->extra["isLogout"])) {
            throw new UacException("用户已登出", "jwt/islogout");
            return null;
        }

        //缓存 登录的帐号实例
        $this->usr = $usr;
        return $usr;
    }

    /**
     * 判断当前是否已有登录用户
     * @return Bool
     */
    public function isLogin()
    {
        $vali = $this->jwtValidate;
        $success = $vali["success"] ?? false;
        return $success && $this->usr instanceof Record;
    }

    /**
     * api
     * @name login
     * @title 用户登录
     * @desc 用户提交账号密码，或扫码登录
     * @auth false
     */
    public function loginApi(...$args)
    {
        /**
         * 此接口需要 uri 参数
         * 指定的 登录类型，不提供则默认 pswd
         */
        $loginner = empty($args) ? "pswd" : $args[0];

        //!! 调用 Loginner 登录器基类的 login 方法，执行不同登录方式定义的 登录操作
        $uuid = Loginner::login($loginner);

        /**
         * 如果未能返回 用户的 uuid 表示登录失败
         * !! 具体失败原因，已由各类型 Loginner 登录器自行处理，此处仅返回 null
         */
        if (!Is::nemstr($uuid)) return null;

        //登录成功，开始获取 uuid 对应的 用户
        $usr = $this->account->nojoin()->column("extra")->whereUuid($uuid)->enabled()->get();
        if (!$usr instanceof Record) {
            //登录成功，但是 uuid 无效
            throw new UacException($loginner.",未能获取账号信息，此账号可能存在问题，请联系管理员", "login/failed");
            return null;
        }

        /**
         * 获取到用户记录实例，处理 extra 记录：
         *  0   记录 lastLoginTime 最后登录时间
         *  1   删除可能存在的 手动登出标记 isLogout
         */
        $extra = $usr->extra;
        //记录 lastLogintime 最后登录时间戳
        $extra = Arr::extend($extra, [
            "lastLoginTime" => time()
        ]);
        //删除 可能存在的手动登出标记 isLogout
        if (isset($extra["isLogout"])) unset($extra["isLogout"]);
        //保存 extra 字段值
        $usr->extra = $extra;
        $usr->save();

        //创建 jwt-token
        $token = $this->jwt->generate([
            "uuid" => $usr->uuid
        ]);
        if (!Is::nemaso($token) || !isset($token["token"]) || !Is::nemstr($token["token"])) {
            //!! token 创建失败
            throw new UacException($loginner.",创建用户 Token 失败，请联系管理员", "login/failed");
            return null;
        }

        //返回 token 给前端，由前端缓存，并在后续请求中自动附带
        return [
            "token" => $token["token"],
            //uuid
            "uuid" => $usr->uuid,
            //用户信息一并返回前端
            //"usrdata" => $usr->ctx
        ];

    }

    /**
     * api
     * @name logout
     * @title 用户登出
     * @desc 用户手动登出，Token 将失效
     * @auth true
     * @role all
     * 
     * !! 用户手动点击登出后，将在 account 表中 extra 字段 增加 isLogout 项，值为 1
     */
    public function logoutApi(...$args)
    {
        $this->usr->extendExtra = [
            "isLogout" => 1
        ];
        $this->usr->save();

        //返回 用户已登出 异常
        throw new UacException("用户已登出", "jwt/islogout");
        return null;
    }

    /**
     * api
     * @name account
     * @title 账号信息
     * @desc 用户登录后获取此用户的账号信息
     * @auth true
     * @role all
     */
    public function accountApi(...$args)
    {
        if ($this->isLogin()!==true) {
            //输出未登陆错误
            $this->throwException(true);
            return null;
        }

        //登录用户
        $usr = $this->loginAccount();
        return $usr->mapper([
            "id",
            "uuid",
            "openid",
            "name",
            "role_list",
            "auth_list",
            "iso_list"
        ]);
    }



    /**
     * Orm 操作
     */

    /**
     * 获取对应的 数据模型类，准备 curd
     * @param String $modk
     * @return Db
     */
    public function model($modk)
    {
        $modk = $this->modk($modk);
        if (!Is::nemstr($modk)) return null;

        //返回 内部指针指向 当前 modk 的 权限数据库，准备 链式 CURD
        return $this->db->$modk;
    }
    
    /**
     * 根据 Uac 模块配置参数定义的 各必须模型的实际名称
     * 依据传入的 account 账号的 uuid 创建账号记录实例
     * !! 账号相关的 iso | role | auth 数据，将被自动汇总到 $usrRecord->subs[] 子表中(缓存为 RecordSet)
     * @param String $uuid 传入的 账号 uuid
     * @return Record|null
     */
    final public function getAccountByUuid($uuid)
    {
        if (!Is::nemstr($uuid) || Uuid::isV7($uuid)!==true) return null;

        //account 表实际名称
        $acmodk = $this->modk("account");
        
        //必须的 账号库模型
        $jtbs = ["account_iso", "account_role", "role_auth", "account_auth"];

        //join 参数
        $join = [];
        //column 参数
        $cols = ["*"];          // [ *, modk.*, modk.*, ... ]
        //to2d 方法参数
        $to2d = ["uuid"];       // [ uuid, modk.pk, modk.pk, ... ]
        //依次生成参数
        foreach ($jtbs as $jtb) {
            //实际模型名
            $jmodk = $this->modk($jtb);
            //left or right
            $jtp = $jtb==="account_auth" ? "[>]" : "[<]";
            //关联字段
            $jcol = ["$acmodk.uuid", "uuid"];
            if ($jtb==="role_auth") $jcol = [$this->modk("account_role").".roleid", "roleid"];

            //创建 join 参数
            $joini = [];
            $joini[$jcol[0]] = $jcol[1];
            $joini["AND"] = $this->getUacModelPrefilter($jtb, true);
            $join[$jtp.$jmodk] = $joini;

            //创建 column 参数
            $cols[] = "$jmodk.*";

            //创建 to2d 参数
            $pk = "";
            if (substr($jtb, -4)==="_iso") {
                $pk = "iso";
            } else if (substr($jtb, -5)==="_auth") {
                $pk = "auth";
            } else if (substr($jtb, -5)==="_role") {
                $pk = "roleid";
            }
            $to2d[] = "$jmodk.$pk";
        }

        //连表查询用户信息
        $usr = $this->model("account")
            ->join($join)
            ->column(...$cols)
            ->whereUuid($uuid)
            ->enabled()     //用户账号必须是生效状态
            ->nowrap()      //此时不包裹为 RecordSet
            ->select();
        
        //!! 未查询到有效记录
        if (!Is::nemidx($usr)) return null;

        //将用户的 iso|role|auth 合并
        $usr = RecordSet::to2d($usr, ...$to2d);
        if (!Is::nemidx($usr)) return null;
        //只取第一条
        $usr = $usr[0];

        //拆分各子表为 记录集数据，并创建 RecordSet 实例
        $subs = [];
        foreach ($jtbs as $jtbn) {
            $modk = $this->modk($jtbn);
            $jtbcls = $this->db->config->ctx("model/$modk/class");
            $jrs = $usr[$modk] ?? [];
            $subs[$jtbn] = new RecordSet($jtbcls, $jrs, false, null);
            unset($usr[$modk]);
        }
        
        //创建用户实例
        $accls = $this->db->config->ctx("model/$acmodk/class");
        $usr = new $accls($usr, false, null);
        //写入子表
        foreach ($jtbs as $jtbn) {
            $usr->setSubs($jtbn, $subs[$jtbn]);
        }

        //释放临时数据
        unset($subs);

        return $usr;
    }



    /**
     * __get
     */
    public function __get($key)
    {
        /**
         * $uac->validate           --> $uac->jwtValidate
         */
        if ($key==="validate") return $this->jwtValidate;

        /**
         * $uac->account            --> $uac->model("account")
         * $uac->role               --> $uac->model("role")
         * $uac->AccountAuth        --> $uac->model("AccountAuth")
         */
        $acdb = $this->model($key);
        if ($acdb instanceof Db) return $acdb;

        return null;
    }



    /**
     * 工具方法
     */

    /**
     * 传入 Uac 库中某个模型的固定名称，返回实际模型名称，在 ModuleUacConfig::$dftInit["orm"]["model"] 中可以自定义实际模型名
     * @param String $modk Uac 模块固定的 模型名 如：account|role|account_role ...
     * @return String|null 返回实际的模型名，未找到返回 null
     */
    public function modk($modk)
    {
        if (!Is::nemstr($modk)) return null;
        $modk = Str::snake($modk, "_");
        $mods = $this->config->ctx("orm/model");
        if (!isset($mods[$modk])) return null;
        return $mods[$modk];
    }

    /**
     * 根据 jwtValidate 结果，抛出异常
     * @param Bool $always 不论何种结果，只要不是 success 一律抛出异常，
     *                     默认 false  仅对 errorToken 和 differentAudience 两种结果抛出异常（可能存在 token 被盗用或篡改）
     * @return Bool 抛出异常则返回 false  未抛异常则返回 true
     */
    public function throwException($always=false)
    {
        $vali = $this->jwtValidate;
        if ($always && !Is::nemaso($vali)) {
            //!! 还未初始化 jwt-token 解析，通常不可能
            throw new UacException("未执行初始解析", "jwt/unknown");
            return false;
        }

        $success = $vali["success"] ?? false;
        //如果 jwt-token 解析成功，则退出，不抛异常
        if ($success) return true;

        //异常类型
        $status = Str::snake($vali["status"], "_");
        if ($always || in_array($status, ["error_token", "different_audience"])) {
            throw new UacException($vali["msg"], "jwt/$status");
            return false;
        }

        return true;
    }

    /**
     * 将 权鉴结果 包装为 标准 AcResult[] 数组
     * @param Array $oprc 标准操作信息数组
     * @param Bool $grant 是否通过权鉴
     * @param String $msg 额外的信息
     * @return Array 返回 AcResult[] 数组
     */
    public function rtnAcResult($oprc, $grant=false, $msg=null)
    {
        if (!is_bool($grant)) $grant = false;
        $msgs = [
            "权鉴已".($grant ? "通过" : "拒绝")."！",
        ];
        if (!$grant) {
            $msgs[] = "可能的原因：";
            if (Is::nemstr($msg)) {
                $msgs[] = $msg;
            } else {
                $msgs[] = "未知原因";
            }
        }
        $rtn = [
            "grant" => $grant,
            "msg" => implode("", $msgs),
            "oprn" => "",
            "oprc" => null,
            "oprt" => ""
        ];

        //$oprc 必须是有效的 操作信息数组
        if ($this->App()->operation::isStdOprc($oprc)!==true) return $rtn;
        $rtn["oprn"] = $oprc["oprn"];
        $rtn["oprt"] = $oprc["title"] ?? ($oprc["desc"] ?? $oprc["name"]);
        $rtn["oprc"] = $oprc;
        //补充 msg
        $msgs[0] = "执行".$rtn["oprt"]."操作，".$msgs[0];
        $rtn["msg"] = implode("", $msgs);

        return $rtn;
    }

    /**
     * 用户的 auth_list 权限数组，可能包含 %{DBN|MODK|MODN}% 通配符
     * 将这些通配符展开为实际的 dbn|modk|modn 生成完整的 auth_list
     * 用于 后续的权限匹配
     * !! 将会被缓存到 $uac->usr->authListCache 中，单次会话内都有效
     * !! 必须在 $uac->currentUsr() 之后执行
     * @param String|Array $al 用户权限数组，或单条 auth 权限记录，默认不指定 直接读取缓存 或 $usr->auth_list
     * @param Array $dbs 传入当前 Orm 模块下所有库和表
     * @return Array 展开后的 完整 auth_list indexed 数组
     */
    public function __expandWildcardInAuthList($al=null, $dbs=null)
    {
        //$uac->usr 实例还未创建
        if (!$this->usr instanceof Record) return [];

        //尝试读取缓存
        if (is_null($al)) {
            $alc = $this->usr->authListCache;
            if (Is::nemidx($alc)) return $alc;
            //缓存不存在 则准备生成
            $al = $this->usr->auth_list;
        }

        //传入了 用户 auth_list
        if (Is::nemidx($al)) {

            //调用 Orm 模块，生成 数据库以及模型列表
            if (!Is::nemarr($dbs)) {
                $dbs = [
                    //"dbn" => [ modk, modk, ... ]
                ];
                $this->Mod("orm")->eachDb(function($dbn, $db) use (&$dbs) {
                    if ($db instanceof Db) {
                        $cfger = $db->config;
                    } else if ($db instanceof DbConfig) {
                        $cfger = $db;
                    } else {
                        return true;
                    }
                    //获取此数据库下所有模型列表，写入 dbs
                    $dbs[$dbn] = $cfger->ctx["models"];
                    return true;
                });
            }

            $rtn = [];
            foreach ($al as $ali) {
                //依次展开 单条 auth 记录，一定返回 indexed[] 数组
                $exp = $this->expandWildcardInAuthList($ali, $dbs);
                if (Is::nemidx($exp)) {
                    //合并到 $rtn
                    $rtn = array_merge($rtn, $exp);
                }
            }
            //写入缓存
            $this->usr->authListCache = $rtn;
            //返回结果
            return $rtn;
        }

        //无效参数
        if (!Is::nemstr($al)) return $this->usr->auth_list;

        //处理单条 auth 记录
        $auth = $al;
        
        //如果不含通配符，直接比较
        if (strpos($auth, "%{")===false || strpos($auth, "}%")===false) return [$auth];

        //包含 通配符 类型
        $has = function($wc) use ($auth) {
            return strpos($auth, $wc)!==false;
        };
        $hdbn = $has("%{DBN}%");
        $hmod = $has("%{MODK}%") || $has("%{MODN}%");
        $hasWcDbn = $hdbn && !$hmod;
        $hasWcMod = !$hdbn && $hmod;
        $hasWcAll = $hdbn && $hmod;

        //按照 通配符类型 分别展开
        $rtn = [];
        
        //%{DBN}%
        if ($hasWcDbn) {
            foreach ($dbs as $dbn => $mods) {
                $rtn[] = str_replace("%{DBN}%", $dbn, $auth);
            }
            return $rtn;
        }

        //%{MODK|MODN}%
        if ($hasWcMod) {
            $aa = explode(":", $auth);
            $ab = explode("/", $aa[0]);
            $idx = array_search("%{MODK}%", $ab);
            if ($idx===false) {
                $idx = array_search("%{MODN}%", $ab);
                $wc = "modn";
            } else {
                $wc = "modk";
            }
            if ($idx===false || $idx<1) return [];
            //当前 已存在的 dbn
            $cdbn = $ab[$idx-1];
            if (!Is::nemstr($cdbn) || !isset($dbs[$cdbn])) return [];
            foreach ($dbs[$cdbn] as $modk) {
                if ($wc==="modk") {
                    $as = str_replace("%{MODK}%", $modk, $auth);
                } else {
                    $as = str_replace("%{MODN}%", Str::camel($modk, true), $auth);
                }
                $rtn[] = $as;
            }
            return $rtn;
        }

        //%{DBN}%/%{MODK|MODN}%
        if ($hasWcAll) {
            $wc = $has("%{MODK}%") ? "modk" : "modn";
            foreach ($dbs as $dbn => $mods) {
                $as = str_replace("%{DBN}%", $dbn, $auth);
                foreach ($mods as $modk) {
                    if ($wc==="modk") {
                        $rtn[] = str_replace("%{MODK}%", $modk, $as);
                    } else {
                        $rtn[] = str_replace("%{MODN}%", Str::camel($modk, true), $as);
                    }
                }
            }
            return $rtn;
        }

        return [];
    }

    /**
     * 解析当前请求项目的 iso 项目隔离编码， $ | foo | foo.bar... 形式
     * 返回解析后的 对应数据表的 iso 字段的 可选值 []
     * !! 如果未能正确解析，返回 null，上层将会报错
     * @return Array|null 如果指定的 项目 iso 无效，则返回 null   解析成功则返回：
     *  [
     *      "iso" => "项目隔离 iso 原始字符串",
     * 
     *      # 此项目可访问数据记录的 iso 字段的可选值 []
     *      "record" => [
     *          "ms.qypms", "ms.qypms.%"
     *      ],
     * 
     *      # 可以操作此项目数据记录的 账号或角色 表记录的 iso 字段的可选值 []
     *      "account" => [
     *          "$", "ms", "ms.qypms"
     *      ],
     * 
     *      # 当前项目 iso 是否是 $ 最高权限
     *      "highest" => false, 
     * 
     *      # 解析得到 record 或 account 后，转为 prefilter []
     *      !! 必须是标准 Medoo where 参数格式
     *      "prefilter" => [
     *          "record" => [
     *              "OR #iso prefilter" => [
     *                  "iso" => "ms.qypms",
     *                  "iso[~]" => "ms.qypms.%"
     *              ],
     *          ],
     *          "account" => [
     *              "iso" => [ "$", "ms", "ms.qypms" ],
     *          ],
     *      ],
     *  ]
     */
    protected function parseCurrentIso()
    {
        $iso = $this->config->ctx["iso"];
        if (!Iso::isLegalIso($iso)) return null;

        //将传入的 当前项目的 iso 转换为此项目可访问数据记录的 iso 字段的可选值 []
        $recIsoVals = Iso::isoToRecordIsoValues($iso);
        if (!is_array($recIsoVals) || Is::associate($recIsoVals)) return null;
        //!! 如果 iso 解析返回空数组 [] 表示传入的是 $ 拥有所有访问权限，不需要 prefilter
        $highest = empty($recIsoVals);

        //将传入的 当前项目的 iso 转换为可以操作此项目数据记录的 account 账号的 iso 字段的可选值 []
        $acIsoVals = Iso::isoToAccountIsoValues($iso);
        if (!is_array($acIsoVals) || Is::associate($acIsoVals)) return null;

        //$rtn
        $rtn = [
            "iso" => $iso,
            "record" => $recIsoVals,
            "account" => $acIsoVals,
            "highest" => $highest,
            "prefilter" => [
                "record" => [],
                "account" => [],
            ],
        ];

        //此项目可访问数据记录的 iso 字段的可选值 [] 转为 prefilter
        if (!$highest) {
            $pre = [];
            foreach ($recIsoVals as $isoi) {
                $isok = strpos($isoi, "%")===false ? "iso" : "iso[~]";
                $pre[$isok] = $isoi;
            }
            if (count($recIsoVals)>1) $pre = [ "OR #iso prefilter"=>$pre ];
            $rtn["prefilter"]["record"] = $pre;
        }

        //可以操作此项目数据记录的 账号|角色 表记录的 iso 字段的可选值 [] 转为 prefilter
        $rtn["prefilter"]["account"]["iso"] = $acIsoVals;

        return $rtn;
    }

    /**
     * 根据当前访问项目的 iso 向 Uac 库中的所有模型 注入 iso 相关的 prefilter 
     * @return Bool
     */
    protected function injectIsoPrefilterToUacDb()
    {
        $iso = $this->parseCurrentIso();
        if (!Is::nemaso($iso)) return false;

        //Uac 库的 DbConfig 实例
        $dbcfg = $this->db->config;
        //Uac 模块的 配置类实例
        $accfg = $this->config;
        //Uac 模块定义的 各数据模型表名
        $actbs = $accfg->ctx("orm/model");

        //依次向 Uac 库中的所有表 注入 prefilter
        foreach ($dbcfg->ctx["model"] as $modk => $modc) {
            //处理特殊表
            if (in_array($modk, [ $actbs["role"], $actbs["account_iso"] ])) {
                //role | account_iso
                $dbcfg->ctx("model/$modk/prefilter", $iso["prefilter"]["account"]);
            } else if (in_array($modk, [ $actbs["account_role"] ])) {
                //account_role
                $pre = array_map(function($isoi) {
                    return $isoi.":%";
                }, $iso["account"]);
                $dbcfg->ctx("model/$modk/prefilter", [
                    "roleid[~]" => $pre
                ]);
            } else {
                //其他表
                if ($iso["highest"]===true) continue;
                //!! 跳过不含 iso 字段的 数据模型
                if (!in_array("iso", $dbcfg->ctx("model/$modk/columns"))) continue;
                $dbcfg->ctx("model/$modk/prefilter", $iso["prefilter"]["record"]);
            }

            //var_dump("----- $modk prefilter -----");
            //var_dump($dbcfg->ctx("model/$modk/prefilter"));
        }

        return true;
    }

    /**
     * 根据当前访问项目的 iso 向所有 Orm 模块中的数据模型（不含 Uac 库） 注入 iso 相关的 prefilter 
     * @return Bool
     */
    protected function injectIsoPrefilterToOrmDb()
    {
        $iso = $this->parseCurrentIso();
        if (!Is::nemaso($iso)) return false;
        //如果当前项目 iso 已是 $ 最高权限，直接返回 true
        if ($iso["highest"]===true) return true;

        //Uac 库名
        $acdbn = $this->db->name;

        //依次向 当前 Orm 模块实例中的 所有可用数据库的所有数据模型，注入 prefilter
        $this->Mod("orm")->eachDb(function($dbn, $db) use ($iso, $acdbn) {
            //!! 跳过 Uac 库
            if ($dbn===$acdbn) return true;
            //因为 数据库懒加载的原因，$orm::$dbs[] 可能缓存的是 数据库实例 或 数据库 DbConfig 实例
            if ($db instanceof Db) {
                $cfger = $db->config;
            } else {
                $cfger = $db;
            }
            //跳过 还未配置初始化的 数据库，通常不可能
            if (!$cfger instanceof DbConfig) return true;

            //依次处理此数据库中的所有数据模型
            foreach ($cfger->ctx["model"] as $modk => $modc) {
                //!! 跳过不含 iso 字段的 数据模型
                if (!in_array("iso", $cfger->ctx("model/$modk/columns"))) continue;
                //写入 prefilter
                $cfger->ctx("model/$modk/prefilter", $iso["prefilter"]["record"]);

                //var_dump("----- $modk prefilter -----");
                //var_dump($cfger->ctx("model/$modk/prefilter"));
            }
            return true;
        });

        return true;
    }

    /**
     * 获取 Uac 库中各模型的 prefilter 参数
     * @param String $modk 模型名 foo_bar
     * @param Bool|String $withPrefix 指定 prefilter 参数中的各字段名是否增加 表名前缀，默认 false 
     *                                false     --> 不增加前缀
     *                                true      --> 自动增加表名前缀
     *                                "prestr"  --> 手动指定前缀字符串
     * @return Array|null 模型的 prefilter 参数  如果指定的模型不存在，则返回 null
     */
    public function getUacModelPrefilter($modk, $withPrefix=false)
    {
        $modk = $this->modk($modk);
        if (!Is::nemstr($modk)) return null;

        //是否增加 prefix
        $wp = is_bool($withPrefix) ? $withPrefix : Is::nemstr($withPrefix);
        $pre = Is::nemstr($withPrefix) ? $withPrefix : $modk;

        //prefilter
        $prefilter = $this->db->config->ctx("model/$modk/prefilter");
        if (!is_array($prefilter)) return null;
        if (empty($prefilter)) return [];

        //不需要前缀
        if ($wp===false) return $prefilter;

        //为 prefilter 中的 字段名增加 前缀
        $curd = $this->model($modk)->curd;
        $autoPrefix = function($pf, $ps) use ($curd, &$autoPrefix) {
            $rtn = [];
            foreach ($pf as $k => $v) {
                if ($curd->whereParser->isAndOr($k)!==false) {
                    $rtn[$k] = $autoPrefix($v, $ps);
                    continue;
                }
                
                //跳过已有前缀的 字段名
                if (strpos($k,".")!==false) {
                    $rtn[$k] = $v;
                    continue;
                }

                $rtn[$ps.".".$k] = $v;
            }
            return $rtn;
        };
        
        return $autoPrefix($prefilter, $pre);
    }
    
}