<?php
/**
 * 框架模块类
 * Test 框架测试模块
 * 提供 框架功能测试 的外部调用入口
 * 
 * !! 仅在 开发模式下可用：$app->config->ctx["module"]["test"]["dev"] === true
 */

namespace Spf\module;

use Spf\Module;
use Spf\module\test\TestException;
use Spf\module\orm\Db;
use Spf\module\orm\Record;
use Spf\module\orm\RecordSet;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;
use Medoo\Medoo;

class Test extends Module 
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
    public $intr = "Spf 框架测试模块";
    //模块的名称 类名 FooBar 形式
    public $name = "Test";



    /**
     * 资源处理模块启用后，将在实例化后，立即执行此 初始化操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function initModule()
    {

        return true;
    }



    /**
     * 工具方法
     */

    /**
     * 带标记的 var_dump
     * @param Mixed $val 要 var_dump 的数据
     * @param String $sign 标记
     * @return $this
     */
    protected function dump($val, $sign=null)
    {
        if (Is::nemstr($sign)) var_dump("----- $sign Start -----");
        var_dump($val);
        if (Is::nemstr($sign)) var_dump("----- $sign End -----");
        return $this;
    }

    /**
     * 输出数据表 html
     * @param Array|RecordSet $rs
     * @param Array $modc 对应数据表的 config 参数
     * @param Bool $nostyle 不输出 style
     * @return String html
     */
    protected function recordTableHtml($rs, $modc=null, $nostyle=false)
    {
        if (!(Is::nemidx($rs) || $rs instanceof RecordSet)) return "缺少必要参数";

        //标记是否传入了 $modc 
        $hasmodc = Is::nemaso($modc);
        //表名
        $tbn = $modc["name"];

        //准备要输出的 html
        $html = [];
        if ($nostyle===false) {
            $html[] = '<style>';
            $html[] = '.table {box-sizing:border-box;display:flex;flex-direction:column;width:100%;border:#e0e0e0 solid 1px;font-size:12px;font-family:monospace;}';
            $html[] = '.tr {box-sizing:border-box;display:flex;align-items:stretch;min-height:24px;border-bottom:#e0e0e0 solid 1px;}';
            $html[] = '.td {box-sizing:border-box;display:flex;align-items:center;padding:0 8px;}';
            $html[] = '</style>';
        }

        //如果传入 RecordSet 实例
        if ($rs instanceof RecordSet) $rs = $rs->_old;

        if (Is::nemidx($rs)) {
            //计算列宽
            if ($hasmodc) {
                $wsum = 0;
                foreach ($rs[0] as $colk => $colv) {
                    $colc = $modc["column"][$colk];
                    $wsum += isset($colc["width"]) ? $colc["width"]*1 : 3;
                }
                $wi = 100/$wsum;
            } else {
                $wi = 100/count($rs[0]);
            }

            //标题
            $html[] = '<div class="tr" style="min-height:32px;border-bottom:none;margin-top:24px;font-size:14px;">';
            if ($hasmodc) {
                $html[] = '<span class="td" style="font-weight:bold;font-size:18px;">'.$modc["title"].'('.$tbn.')</span>';
                $html[] = '<span style="flex:1;"></span>';
            }
            $html[] = '<span class="td">'.count($rs).' 条记录</span>';
            $html[] = '</div>';
            $html[] = '<div class="table" style="border-bottom:none;">';
            //表头
            $html[] = '<div class="tr">';
            foreach ($rs[0] as $colk => $colv) {
                if ($hasmodc) {
                    $colc = $modc["column"][$colk];
                    $html[] = '<span class="td" style="width:'.($colc["width"]*$wi).'%;">'.$colk.'('.$colc["title"].')</span>';
                } else {
                    $html[] = '<span class="td" style="width:'.$wi.'%;">'.$colk.'</span>';
                }
            }
            $html[] = "</div>";
            //输出记录
            foreach ($rs as $rsi) {
                $html[] = '<div class="tr">';
                foreach ($rsi as $colk => $colv) {
                    //pswd
                    if ($colk==="pswd") {
                        $colv = substr($colv, 0,4)."****".substr($colv, -4);
                    }
                    if ($hasmodc) {
                        $colc = $modc["column"][$colk];
                        $html[] = '<span class="td" style="width:'.($colc["width"]*$wi).'%;">'.$colv.'</span>';
                    } else {
                        $html[] = '<span class="td" style="width:'.$wi.'%;">'.$colv.'</span>';
                    }
                }
                $html[] = "</div>";
            }
            $html[] = "</div>";
        }

        //输出 html
        return implode("",$html);
    }



    /**
     * default
     * @title Spf框架测试接口列表
     * @export view
     * @auth false
     * 
     * @param Array $args url 参数
     * @return Array 输出
     */
    public function default(...$args)
    {
        $app = $this->App();
        $appk = $app::clsk();
        $req = $this->Req();
        $domain = $req->url->domain;

        //当前可用的 所有操作
        $defs = $app->operation->defines();
        $html = [
            '<!DOCTYPE html><html lang="zh-cn"><body style="margin:0; padding:0;">',
            '<div style="display:flex; align-items:center; width:100%; height:48px; padding:0 16px; font-size:18px; font-weight:bold; color:#191919; font-family: monospace; box-sizing:border-box; border-bottom:#f0f0f0 solid 1px;">'.$app->intr.' ▶ 测试接口</div>',
        ];

        foreach ($defs as $oprn => $oprc) {
            $cls = $oprc["class"];
            $tmn = $oprc["method"];

            //当前是 Test 基类
            $isBase = method_exists($cls, "clsk") && $cls::clsk()===Test::clsk();
            //当前是 Test 子类
            $isSubc = is_subclass_of($cls, Test::class);

            //操作指向的类必须是 Test 或 Test子类
            if (!$isBase && !$isSubc) continue;

            //Test 父类接口，如果子类未重写，则只出现一次
            if ($isSubc && method_exists(Test::class, $tmn) && Cls::isMethodOverride($cls, $tmn, Test::class)!==true) continue;

            $rut = $oprc["route"] ?? null;
            if (Is::nemstr($rut)) {
                $rut = substr($rut, 1, -1);
                $rut = str_replace("(\\.*)", "", $rut);
                $rut = str_replace("\\/","/",$rut);
                $tu = "/".$appk."/".trim($rut, "/");
            }
            //行
            $row = [
                '<div style="display:flex; width:100%; height:32px; align-items:center; font-size:14px; 
                font-family:monospace; color:#303030; margin:0; padding:0 16px; box-sizing:border-box; border-bottom:#f0f0f0 solid 1px;">'
            ];
            $row[] = '<span style="width:512px; margin-right:32px;"><span style="font-size:12px;">'.$cls::$current->intr.'</span> ▶ <span style="font-weight:bold;">'.$oprc["title"].'</span></span>';
            $row[] = '<span style="width:256px; text-align:right; margin-right:16px; font-size:12px; color:#a0a0a0;">'.$cls::clsn().'::'.$tmn.'</span>';
            if (!Is::nemstr($rut)) {
                $row[] = '<span style="flex:1; color:#ff3333; font-weight:bold;">无接口地址</span>';
            } else {
                $row[] = '<a href="'.$domain.$tu.'" target="_blank" style="text-decoration:none; color:#1020ff; margin:0; padding:0; flex:1;">'.$tu.'</a>';
            }
            $row[] = '</div>';

            $html[] = implode("", $row);
        }

        $html[] = '</body></html>';

        return implode("", $html);
    }

    /**
     * api
     * @title Util工具类：Uuid测试
     * @auth false
     */
    public function utilUuidTestApi(...$args)
    {
        var_dump("-------- Util工具类：Uuid测试 --------");

        //工具类
        $ucls = \Spf\util\Uuid::class;

        //创建 UUIDv7
        $uuid = $ucls::v7();
        var_dump($uuid);
        var_dump("UUIDv7 字符长度 = ".strlen($uuid));

        //isV7
        var_dump($uuid." isV7 = ".($ucls::isV7($uuid) ? "true" : "false"));
        $not_uuid = str_replace("-",":",$uuid);
        var_dump($not_uuid." isV7 = ".($ucls::isV7($not_uuid) ? "true" : "false"));

        //解析
        var_dump($uuid." --> hex = ".$ucls::v7Hex($uuid));
        var_dump($uuid." --> bin = ".$ucls::v7Bin($uuid));
        var_dump($uuid." --> dec = ".$ucls::v7Dec($uuid));

        //排序
        $times = [
            "2026-04-19",
            "2025-12-10 10:21:49",
            "2026-04-19 20:18:00",
            "2025-12-10 10:21:48"
        ];
        $uuids = array_map(function($ti) use ($ucls) {
            return $ucls::v7($ti);
        }, $times);
        var_dump("----排序前----");
        var_dump(array_map(function($ui) use ($ucls) {
            return $ui." (".date("Y-m-d H:i:s",$ucls::v7Ts($ui)).")";
        }, $uuids));
        var_dump("----升序排列----");
        var_dump(array_map(function($ui) use ($ucls) {
            return $ui." (".date("Y-m-d H:i:s",$ucls::v7Ts($ui)).")";
        }, $ucls::v7Sort($uuids)));
        var_dump("----降序排列----");
        var_dump(array_map(function($ui) use ($ucls) {
            return $ui." (".date("Y-m-d H:i:s",$ucls::v7Ts($ui)).")";
        }, $ucls::v7Sort($uuids, "desc")));

        return [
            "test" => __METHOD__
        ];
    }

    /**
     * api
     * @title Util工具类：Operation测试
     * @auth false
     */
    public function utilOperationTestApi(...$args)
    {
        $operation = $this->App()->operation;
        $defs = $operation->defines();

        $dump = [];
        foreach ($defs as $oprn => $oprc) {
            $dump[$oprn] = $oprc["title"];
        }
        $this->dump($dump, "定义的操作");

        return [
            "test" => __METHOD__
        ];
    }

    /**
     * api
     * @title CoreInsGetter测试
     * @auth false
     */
    public function coreInsGetterApi(...$args)
    {
        var_export($this->EnvOk());
        var_export($this->Env()->config->ctx);
        
        var_export($this->Req()->config->ctx);
        var_export($this->Req()->gets);
        var_export($this->Req()->posts);
        var_export($this->Req()->inputs);

        var_export($this->App()->config->ctx);
        var_export($this->App()::clsk());
        var_export($this->App()->intr);

        var_export($this->ModOk("test"));
        var_export($this->Mod("test")->config->ctx);

        return [
            "test" => __METHOD__
        ];
    }



    /**
     * Orm 模块测试
     */

    /**
     * api
     * @title Orm模块：find方法测试
     * @auth false
     */
    public function moduleOrmFindTestApi(...$args)
    {
        $acdb = $this->Mod("uac")->db;

        //account
        $acrs = $acdb->Account->find("019dd7b2-865d-7b46-986d-d260426730c5");
        $this->dump($acrs->entire(), "find account");
        $acrs = $acdb->Account->find(4, "id");
        $this->dump($acrs->entire(), "find account");
        $acrs = $acdb->Account->findId(4);
        $this->dump($acrs->entire(), "find account");

        //account_iso
        $isors = $acdb->AccountIso->find(2);
        $this->dump($isors->entire(), "find account_iso");

        return [
            "test" => __METHOD__
        ];
    }

    /**
     * api
     * @title Orm模块：search方法测试
     * @auth false
     */
    public function moduleOrmSearchTestApi(...$args)
    {
        $acdb = $this->Mod("uac")->db;

        //带条件
        $sql = $acdb->Account
            //->debug()
            ->enabled()
            ->join([
                "[<]account_iso" => "uuid"
            ])
            ->column("account_iso.iso")
            ->search("张%，陈%");
        $this->dump($sql->_ctx);

        //直接搜索
        $acrs = $acdb->Account->search("张");
        $this->dump($acrs->_ctx, "find account");

        return [
            "test" => __METHOD__
        ];
    }

    /**
     * api
     * @title Orm模块：关联表记录实例测试
     * @auth false
     */
    public function moduleOrmJoinRecTestApi(...$args)
    {
        $orm = $this->Mod("orm");
        $db = $orm->Account;

        $rs = $db->Organize
            ->column("desc","organize(superior).desc")
            ->join("superior")
            ->whereId(">", 50)
            ->select();

        //RecordSet->arrange() 将 一对多的 关联表数据，整理为 记录实例的 subs 子表 RecordSet 结构
        $arranged = $rs->arrange("superior.orgid");
        /*$this->dump($arranged->mapper([
            "id",
            "orgid",
            "name",
            "inferior" => "organizeRsEntire",
        ]));*/
        $this->dump($arranged->entire());

        //$this->dump($rs->ctx,"Organize 表 ID>50 记录实例");
        //$this->dump($rs[1]->entire(),"Organize 表 ID=51 记录实例");
        /*$this->dump($rs[1]->mapper([
            "id",
            "superior.orgid",
            "foo" => [
                "iso",
                "sup_iso" => "superior.iso"
            ]
        ]),"Organize 表 ID=51 记录实例");*/
        //$this->dump($rs->superior->ctx, "关联表 superior 记录实例");
        //$this->dump($rs->fromCurd->final, "关联 curd 实例的最终参数");

        return [
            "test" => __METHOD__
        ];
    }

    /**
     * api
     * @title Orm模块：RIGHT JOIN 查询整理为多维数组
     * @auth false
     */
    public function moduleOrmRightJoinTestApi(...$args)
    {
        //数据来自 Organize 表，保存了多个层级的 组织结构
        $orm = $this->Mod("orm");
        $db = $orm->db("account");

        //$_GET
        $gets = $this->Req()->gets;
        //debug = true|false
        $debug = $gets->debug(false);
        //nowrap = true|false
        $nowrap = $gets->nowrap(false);

        //!! 使用 right join 关联自身（别名 inferior）
        $curd = $db->Organize
            ->join("superior")
            ->column("*","organize(superior).*")
            /*->column([
                "inferior_count" => Medoo::raw("COUNT(inferior.orgid)")
            ])
            ->group("orgid")
            ->having([
                "orgid[!]" => ""
            ])*/
            ->curd;

        //sql debug
        if ($debug) {
            $sql = $curd->debug()->select();
            $this->dump($sql);
        }

        //nowrap
        if ($nowrap) {
            $rs = $curd->debug(false)->nowrap()->select();
            if (Is::nemidx($rs)) {
                $html = $this->recordTableHtml($rs);
                $this->Rep()->setType("view");
                return $html;
            }
            //$this->dump($rs);
        }
        

        return [
            "test" => __METHOD__
        ];
    }



    /**
     * Uac 模块测试
     */

    /**
     * api
     * @title UAC模块：JWT测试
     * @auth false
     */
    public function moduleUacJwtApi(...$args)
    {
        $uac = $this->Mod("uac");
        $islogin = $uac->isLogin();

        return [
            "test" => __METHOD__,
            "validate_token" => $uac->validate,
            "iso_list" => $islogin ? $uac->usr->iso_list : "not login",
            "role_list" => $islogin ? $uac->usr->role_list : "not login",
            "auth_list" => $islogin ? $uac->usr->auth_list : "not login",
        ];
    }

    /**
     * api
     * @title UAC模块：ApifoxToken快速生成
     * @auth false
     */
    public function moduleUacApifoxTokenApi(...$args)
    {
        //通过 uri 参数挑选 account 账号
        if (empty($args)) {
            $acid = 4;
        } else {
            $acid = (int)$args[0];
        }

        //获取账号信息
        $uac = $this->Mod("uac");
        $usr = $uac->Account->findId($acid);
        if (!$usr instanceof Record) {
            throw new TestException("无法获取用户信息", "common");
        }
        $uuid = $usr->uuid;

        //生成 jwt-token 
        $aud = "public";    //for apifox
        $token = $uac->jwt->generate([
            "uuid" => $uuid
        ], $aud);

        return [
            "test" => __METHOD__,

            "account" => [
                "uuid" => $uuid,
                "token" => $token
            ],
        ];
    }

    /**
     * api
     * @title UAC模块：Login接口
     * @auth false
     */
    public function moduleUacLoginApi(...$args)
    {
        $uac = $this->Mod("uac");

        //!! 如果已登录，先退出
        if ($uac->isLogin()===true) {
            //!! 访问 logout 接口将直接退出，然后重新访问此测试接口
            $uac->logoutApi();
            return null;
        }

        //!! login/pswd 接口需要通过表单提交数据，需要在 postman 中配置 post 数据
        return [
            "test" => __METHOD__,
            "loginRtn" => $uac->loginApi("pswd")
        ];
    }

    /**
     * api
     * @title UAC模块：Logout接口
     * @auth false
     */
    public function moduleUacLogoutApi(...$args)
    {
        $uac = $this->Mod("uac");
        if ($uac->isLogin()===true) {
            //!! 调用 logout 接口将直接退出
            $uac->logoutApi();
            return null;
        }

        return [
            "test" => __METHOD__,
            "alreadyLogout" => true
        ];
    }

    /**
     * api
     * @title UAC模块：清空pswdTryTimes
     * @auth false
     */
    public function moduleUacClearTryTimesApi(...$args)
    {
        return [
            "test" => __METHOD__,
            "clearTryTimes" => \Spf\module\uac\Loginner::support("pswd")::clearTryTimes()
        ];
    }

    /**
     * api
     * @title UAC模块：AC测试
     * @auth false
     */
    public function moduleUacAcApi(...$args)
    {
        $uac = $this->Mod("uac");
        $oidx = empty($args) ? 0 : (int)$args[0];
        $oprns = [
            "api/module/uac:account",
            "api/db/account:backup_db",
            "api/db/account:recreate_db",
            "api/model/account/organize:retrieve",
            "api/model/account/organize:update"
        ];
        $oprn = $oprns[$oidx];

        return [
            "test" => __METHOD__,
            "usr" => $uac->usr->mapper(["auth_list"]),
            "ac" => $uac->authCheck($oprn, false)
        ];
    }

    /**
     * api
     * @title UAC模块：重建AC库、表和测试记录
     * @auth false
     */
    public function moduleUacRecreateApi(...$args)
    {
        //$_GET
        $gets = $this->Req()->gets;
        //是否重建表 ?recreate_table=true 或 表名
        $rctb = $gets->recreateTable(false);
        //重建表时，是否 dump detail  ?dump_detail=true
        $ddtl = $gets->dumpDetail(false);

        if ($rctb===true) $this->dump("recreate table");
        if ($ddtl===true) $this->dump("dump detail when recreate table");

        $orm = $this->Mod("orm");
        //Uac 对应的数据库名 account 自行修改
        $dbn = "account";
        $acdb = $orm->db($dbn);

        /*if ($acdb instanceof Db) {
            $this->dump($acdb, "AC库已经存在，重建将删除所有数据！！在 Test 模块方法代码中删除此段后，才能继续");
            return [
                "test" => __METHOD__
            ];
        }*/

        //!! 不重建整个 AC库，而是依次重建每个表
        //$this->dump($acdb->driver->recreate(), "重建AC库和账号角色表");

        //准备要输出的 html
        $html = [];

        //读取初始数据
        $initd = file_get_contents(Path::find("spf/module/uac/orm/model/record/init.json"));
        $initd = Conv::j2a($initd);
        //$this->dump($initd, "初始数据");

        //依次 重建表，写入原始数据
        foreach ($initd as $tbn => $tbc) {
            if (!Is::nemaso($tbc)) continue;
            if (false===($mod = $acdb->hasModel($tbn))) continue;

            //?recreate_table=true  或  ?recreate_table=$tbn
            if ($rctb===true || $rctb===$tbn) {
                
                //!!重建表，不重建数据
                $acdb->driver->recreateTable($tbn, false);

                //是否依赖其他数据表
                $dep = $tbc["dependency"] ?? false;
                if ($dep!==false) {
                    $deptbn = $dep["table"];
                    $depwhr = $dep["where"];
                    $depcol = $dep["quote"];

                    $nrs = [];
                    foreach ($tbc["rs"] as $j => $rsi) {
                        //执行where
                        $whrrs = $acdb->$deptbn->nojoin();
                        foreach ($depwhr as $whrcol => $colvidx) {
                            $whrrs->column($whrcol)->where([ $whrcol=>$rsi[0][$colvidx] ]);
                        }
                        $whrrs = $whrrs->get();
                        //准备要插入此表的 此条数据
                        $rsi = array_slice($rsi, 1);
                        $rsdi = [];
                        foreach ($depcol as $qcol => $ccol) {
                            $rsdi[$ccol] = $whrrs->$qcol;
                        }
                        foreach ($tbc["columns"] as $k => $ccoli) {
                            $rsdi[$ccoli] = $rsi[$k];
                        }
    
                        //准备 dump 数据
                        $dump = [
                            "要写入表的原始数据" => $rsdi,
                        ];
        
                        //创建 临时 记录实例
                        $rsoi = $acdb->$tbn->record($rsdi);
                        $dump["原始数据经过默认值填充后"] = $rsoi->ctx;
                        $dump["要写入的 toDb 数据"] = $rsoi->_diff;
        
                        //debug SQL
                        if ($ddtl===true) {
                            $dbg = $rsoi->save(true);
                            $dump["debug SQL"] = $dbg;
                        }
        
                        //实际写入
                        $saved = $rsoi->save();
                        $dump["实际写入后返回的 fromDb 数据"] = $saved->_old;
                        $dump["实际写入后返回的记录实例数据"] = $saved->ctx;
        
                        if ($ddtl===true) $this->dump($dump, "重建 $tbn 表记录 ID = ".$saved->id);
                        //break;
                    }
                    continue;
                }
    
                //字段列表
                $cols = $tbc["columns"] ?? [];
                $rs = $tbc["rs"] ?? [];
                if (!Is::nemidx($cols) || !Is::nemidx($rs)) continue;
                //字段数量
                $colen = count($cols);
                //依次插入记录数据
                foreach ($rs as $j => $rsi) {
                    if (!Is::nemidx($rsi) || count($rsi)!==$colen) continue;
                    //依次准备此条记录各字段的值
                    $rsdi = [];
                    for ($i=0;$i<$colen;$i++) {
                        $rsdi[$cols[$i]] = $cols[$i]==="pswd" ? md5($rsi[$i]) : $rsi[$i];
                    }
    
                    //准备 dump 数据
                    $dump = [
                        "要写入表的原始数据" => $rsdi,
                    ];
    
                    //创建 临时 记录实例
                    $rsoi = $acdb->$tbn->record($rsdi);
                    $dump["原始数据经过默认值填充后"] = $rsoi->ctx;
                    $dump["要写入的 toDb 数据"] = $rsoi->_diff;
    
                    //debug SQL
                    if ($ddtl===true) {
                        $dbg = $rsoi->save(true);
                        $dump["debug SQL"] = $dbg;
                    }
    
                    //实际写入
                    $saved = $rsoi->save();
                    $dump["实际写入后返回的 fromDb 数据"] = $saved->_old;
                    $dump["实际写入后返回的记录实例数据"] = $saved->ctx;
    
                    if ($ddtl===true) $this->dump($dump, "重建 $tbn 表记录 ID = ".$saved->id);
                    //break;
                }
                //break;
            }

            //查询数据
            $rs = $acdb->$tbn->nojoin()->column("*")->nowrap()->select();
            if (Is::nemidx($rs)) {
                $html[] = $this->recordTableHtml(
                    $rs, 
                    $acdb->config->ctx("model/$tbn"),
                    //如果已有表输出过，不重复输出 style
                    !empty($html)
                );
            }
        }

        //修改为 view 输出
        $this->Rep()->setType("view");
        //输出 html
        return implode("",$html);

        return [
            "test" => __METHOD__
        ];
    }

    /**
     * api
     * @title UAC模块：ISO权限隔离测试
     * @auth false
     */
    public function moduleUacIsoApi(...$args)
    {
        $uac = $this->Mod("uac");
        //$orm = $this->Mod("orm");
        //$acdb = $uac->db;

        //当前项目隔离 iso
        //$iso = $this->Mod("uac")->config->ctx["iso"];

        //获取 account 测试账号
        //$uuid = "019dd7b2-865d-7b46-986d-d260426730c5";     //super
        $uuid = "019dd7b2-8664-7b8d-a020-a635ba423fc8";     //normal|db|admin
        $usr = $uac->currentUsr($uuid);
        //$usr = $uac->Account->findUuid($uuid);
        $this->dump($usr->mapper([
            "name",
            "uuid",
            "iso_list",
            "role_list",
            "auth_list"
        ]), "用户数据");

        //展开后的 auth_list
        $al = $uac->expandWildcardInAuthList();
        $this->dump($al, "展开后的完整 auth_list");


        /*$curd = $acdb->Account
            ->join([
                "[<]account_iso" => [
                    "account.uuid" => "uuid",
                    "AND" => $uac->getUacModelPrefilter("account_iso", true)
                ],
                "[<]account_role" => [
                    "account.uuid" => "uuid",
                    "AND" => $uac->getUacModelPrefilter("account_role", true)
                ],
                "[<]role_auth" => [
                    "account_role.roleid" => "roleid",
                    "AND" => $uac->getUacModelPrefilter("role_auth", true)
                ],
                "[>]account_auth" => [
                    "account.uuid" => "uuid",
                    "AND" => $uac->getUacModelPrefilter("account_auth", true)
                ]
            ])
            ->column("name", "account_iso.iso", "account_role.roleid", "role_auth.auth", "account_auth.auth")
            ->whereUuid($uuid)
            ->enabled()
            ->nowrap()
            ->curd;
        

        //debug
        if ($this->Req()->gets->debug(false)===true) {
            $sql = $curd->debug()->select();
            $this->dump($sql, "获取测试账号 SQL Debug");
        }

        //执行 sql
        $usr = $curd->debug(false)->select();
        $usr = RecordSet::to2d($usr, "uuid", 
            "account_iso.iso", 
            "account_role.roleid", 
            "role_auth.auth",
            "account_auth.auth"
        );
        $this->dump($usr, "测试账号记录");
            */

        return [
            "test" => __METHOD__
        ];
    }

    
}