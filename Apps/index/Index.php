<?php

namespace dp\app;
use dp\Usr as Usr;
use dp\Router as Router;
use dp\App as App;
use dp\Model as Model;

class Index extends App {
    static function tst(){
        return "\dp\app\Index::tst();";
    }

    //空规则 host/index
    public function router_empty(){
        //_export_(Usr::_cache());
        //return _get_();
        return _input_();
    }

    public function router_foo(){
        //$jdb = _jdb_("usr");
        //$rs = $jdb->table("usr")->where("{{`id` = 1}}")->update("nick=老灰aaa&info_qq=4753");
        //return $rs;
        //return 123;
        //return _router_("/plugin/vcode/csv/dommy");
        //return _router_("/index",["foo"=>"bar","key"=>"val"]);
        //return Usr::$db->cdt("tables/usrgroup/data");
        //$usr->testlogin();
        //$usr->data("data/nick","laohui666");
        //$usr->dologout();
        //return $usr;
        //$lrs = $udb->table("usrlogin")->where("{{`session` LIKE '%pcwx=529%'}}")->select();
        
        //_uac_("_NEED_LOGIN_");
        //$usr = _usr_();
        //$usr->testlogin();
        //$udb = _usr_db_(); 
        //$uls = Usr::$db->table("usr")->search("foo","-bar","&foobar");
        //$usr->edit("usrlogin",["session"=>"foo=bar&pcwx=1234"]);
        //$usr->logsession("pcwx","5678");
        //return $usr::find_by_logsession("pcwx=5678");

        //$md = _model_("index/usr");
        //return Model::$_LIST_;

        /*$tpl = _template_("test");
        return $tpl->assign([
            "str" => [
                "a" => "Dommy"
            ],
            "num" => [
                "a" => 5.3
            ]
        ])->parse()->export();*/

        $arr = [
            "a" => 100,
            "b" => ["aa","bb","cc"],
            "c" => TRUE,
            "d" => "foobar",
            "e" => [
                "a" => "okkk",
                "b" => FALSE
            ]
        ];
        $tpls = "<h1>#[if::c == TRUE,test,否]#</h1>";
        $tpl = _template_("test");
        //return is_object(_array_xpath_($arr,"e"));
        return $tpl->parse($arr)->export();

    }
}