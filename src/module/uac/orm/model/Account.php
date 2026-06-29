<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型类
 * 
 * 框架默认的 Account 账号模型
 * 用于 通用账号系统
 * 各应用可以继承并扩展 此数据模型
 */

namespace Spf\module\uac\orm\model;

use Spf\module\Orm;
use Spf\module\orm\Db;
use Spf\module\orm\Model;
use Spf\module\orm\RecordSet;
use Spf\module\orm\config\DbConfig;
use Spf\module\Uac;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Uuid;

class Account extends Model 
{
    /**
     * 数据模型(表) 的额外配置参数
     * 这些参数将在 数据库配置解析阶段，在 调用 Preparer 预处理之前 合并到 此数据模型的待解析配置数据中
     * !! 具体的 数据模型(表) 子类必须覆盖
     * !! 否则这些静态参数 会在多个 数据模型(表) 类中相互影响
     */
    public static $customConf = [
        //!! 参数结构 与 module/orm/config/DbConfig::$stdModel[] 一致
        //!! 可以使用 %{...}% 模板字符，可用的模板字符 在 module\orm\ModuleOrmConfig::processConf() 方法中查看
    ];

    /**
     * 此数据模型(表) 的 配置参数
     * !! 具体的 数据模型(表) 子类必须覆盖这些 静态参数
     * !! 否则这些静态参数 会在多个 数据模型(表) 类中相互影响
     */
    //数据模型(表) 所属 数据库实例  访问：NS\model\Foo::$db
    public static $db = null;
    //数据模型(表) 名称 foo_bar  访问：NS\model\Foo::$modk
    public static $modk = "account";
    //数据模型(表) 类名 FooBar  访问：NS\model\Foo::$modn
    public static $modn = "Account";
    //数据模型(表) 配置参数 (object)[]  访问：NS\model\Foo::$config
    public static $config = null;
    //标记 此数据模型(表) 配置参数已经被解析并初始化
    public static $isConfed = false;

    /**
     * 用户的 auth_list 权限列表中，可能包含 %{DBN|MODK|MODN}% 形式的 通配符
     * 在执行权限验证时，需要展开这些通配符
     * !! 此处缓存了 展开后的 auth_list ，单次会话内都有效
     * !! 展开操作需要遍历 数据库以及模型列表，比较耗资源，因此需要缓存
     */
    public $authListCache = [];



    /**
     * 调用 Uac 模块的 getAccountByUuid() 方法
     * 依据传入的 account 账号的 uuid 创建账号记录实例
     * !! 账号相关的 iso | role | auth 数据，将被自动汇总到 $record->subs[] 子表中(缓存为 RecordSet)
     * !! 此方法必须依赖 Uac 模块
     * !! 覆盖 Model 基类的 findUuid() 魔术方法
     * !! 子类不要修改
     * @param String $uuid 传入的 账号 uuid
     * @return Record|null
     */
    public static function findUuid($uuid)
    {
        if (Uac::$isInsed!==true) return null;
        //创建 Account Record 
        return Uac::$current->getAccountByUuid($uuid);
    }



    /**
     * 账号实例方法
     */

    /**
     * 判断当前用户 是否拥有 给定的 oprn 标准操作标识对应操作的 权限
     * !! 通常由 Uac 模块调用： $uac->usr->authCheck("api/foo/bar:jaz")
     * @param String $oprn 标准操作标识，如果不指定，则使用当前请求的 操作信息
     * @param Bool $silence 是否静默失败，如果为 true 则只会返回 true|false ，默认 false 返回标准 AcResult[] 数组，包含详细信息
     * @return Array|Bool
     */
    public function authCheck($oprn=null, $silence=false)
    {
        if (!is_bool($silence)) $silence = false;

        //当前应用实例
        $app = $this->App();
        //Uac 模块实例
        $uac = $this->Mod("uac");

        //包装权鉴结果
        $wrap = function($res, $oprc=null, $msg=null) use ($silence, $uac) {
            if (!is_bool($res)) $res = false;
            if ($silence) return $res;
            return $uac->rtnAcResult($oprc, $res, $msg);
        };

        //获取 oprn 指向的 操作信息
        $oprc = $app->operation->getOprc($oprn);

        //无效的操作信息数组
        if ($app->operation::isStdOprc($oprc)!==true) {
            return $wrap(false, null, "无效的操作标识");
        }

        //!! super 用户，直接通过
        if ($this->super_role===true) {
            return $wrap(true, $oprc);
        }

        //当前用户的 auth_list 已经过 expand 的完整 auth_list
        $al = $this->auth_list;
        //用户的权限列表为空  或  oprn 不在 al 中，拒绝
        if (!Is::nemidx($al) || !in_array($oprn, $al)) {
            return $wrap(false, $oprc, "用户无操作权限");
        }
        //如果 oprc 操作信息中还定义了 role 角色，且 role!=all，还要检查，当前用户的 role_list 
        if (isset($oprc["role"]) && $oprc["role"]!=="all") {
            if ($this->roleCheck($oprc["role"])!==true) {
                return $wrap(false, $oprc, "用户角色无操作权限");
            }
        }

        //权鉴通过
        return $wrap(true, $oprc);
    }

    /**
     * 用户角色组判断
     * 要求 传入的 $opr_role[] 中的任意一个，在 当前用户的 $usr->role_list 中  即可返回 true
     * !! $opr_role[] 需要统一增加 当前应用的 iso 前缀
     * @param Array|String $opr_role 指定的操作信息中包含的 role_list[]  可能是 单一字符串，indexed数组，或 "all"
     * @return Bool 满足条件则返回 true
     */
    public function roleCheck($opr_role)
    {
        //super 用户
        if ($this->super_role===true) return true;

        //操作信息未定义 role
        if ($opr_role==="all") return true;

        //当前用户的 role_list
        $usr_role = $this->role_list;
        if (!Is::nemidx($usr_role)) return false;

        //操作信息定义的 role_list
        if (Is::nemstr($opr_role)) $opr_role = [$opr_role];
        if (!Is::nemidx($opr_role)) return true;

        //统一增加 iso 前缀
        $iso = $this->Mod("uac")->config->iso;
        $opr_role = array_map(function($roi) use ($iso) {
            return $iso.":".$roi;
        }, $opr_role);

        //diff
        $diff = array_diff($opr_role, $usr_role);
        return count($diff) < count($opr_role);
    }

    /**
     * 用户的 auth_list 权限数组，可能包含 %{DBN|MODK|MODN}% 通配符
     * 将这些通配符展开为实际的 dbn|modk|modn 生成完整的 auth_list
     * 用于 后续的权限匹配
     * !! 将会被缓存到 $usr->authListCache 中，单次会话内都有效
     * !! 自动通过 $usr->auth_list 计算字段调用此方法，只会执行一次，之后都会读缓存
     * @param String|Array $al 用户权限数组，或单条 auth 权限记录
     *                         默认 null  直接读取缓存 或 执行生成方法
     * @param Array $dbs 传入当前 Orm 模块下所有库和表
     * @return Array 展开后的 完整 auth_list indexed 数组
     */
    public function expandAuthList($al=null, $dbs=null)
    {
        //优先 尝试读取缓存
        if (is_null($al)) {
            $alc = $this->authListCache;
            if (Is::nemidx($alc)) return $alc;

            //缓存不存在 则准备生成
            $al = $this->combineAuthList();
            if (!Is::nemidx($al)) return [];
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
                $exp = $this->expandAuthList($ali, $dbs);
                if (Is::nemidx($exp)) {
                    //合并到 $rtn
                    $rtn = array_merge($rtn, $exp);
                }
            }
            //!! 写入缓存
            $this->authListCache = $rtn;
            //返回结果
            return $rtn;
        }

        //无效参数
        if (!Is::nemstr($al)) return $this->combineAuthList();

        //处理单条 auth 记录
        $auth = $al;
        
        //如果不含通配符，直接比较
        if (strpos($auth, "%{")===false || strpos($auth, "}%")===false) {
            return [$auth];
        }

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
     * 合并当前用户的 role_auth 和 account_auth 数据
     * !! 其中的 auth 权限项目，可能包含 %{...}% 通配符
     * @return Array 完整的 auth_list 原始数组，可能需要 expandAuthList 展开
     */
    protected function combineAuthList()
    {
        //需要读取 role_auth 和 account_auth 子表数据
        $roleAuthRs = $this->role_auth_rs;
        $usrAuthRs = $this->account_auth_rs;
        if (!$roleAuthRs instanceof RecordSet || !$usrAuthRs instanceof RecordSet) return [];
        $al = $roleAuthRs->auth;
        foreach ($usrAuthRs->auth as $aui) {
            if (!in_array($aui, $al)) $al[] = $aui;
        }
        if (!Is::nemidx($al)) return [];
        return $al;
    }




    /**
     * getter
     * @name iso_list
     * @title 属于ISO
     * @desc 此用户所属的 ISO 列表
     * @width 6
     * @type Array
     * @jstype Array
     */
    protected function isoListGetter()
    {
        //需要读取 account_iso 子表数据
        $isoRs = $this->account_iso_rs;
        if (!$isoRs instanceof RecordSet) return [];
        return $isoRs->iso;
    }

    /**
     * getter
     * @name role_list
     * @title 角色列表
     * @desc 此用户所属的角色 roleid 列表
     * @width 6
     * @type Array
     * @jstype Array
     */
    protected function roleListGetter()
    {
        //需要读取 account_role 子表数据
        $roleRs = $this->account_role_rs;
        if (!$roleRs instanceof RecordSet) return [];
        return $roleRs->roleid;
    }

    /**
     * getter
     * @name auth_list
     * @title 权限列表
     * @desc 此用户拥有的所有 auth 权限列表
     * @width 6
     * @type Array
     * @jstype Array
     */
    protected function authListGetter()
    {
        //直接调用 expandAuthList 方法，优先读取 $this->authListCache 缓存
        return $this->expandAuthList();
    }

    /**
     * getter
     * @name super_role
     * @title 超级用户
     * @desc 此用户是否属于 super 用户组
     * @width 3
     * @type Boolean
     * @jstype Boolean
     */
    protected function superRoleGetter()
    {
        $rl = $this->role_list;
        if (!Is::nemidx($rl)) return false;
        foreach ($rl as $ri) {
            if (strpos($ri, ":super")!==false) return true;
        }
        return false;
    }



    /**
     * 模型接口
     */

    
}