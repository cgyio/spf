<?php
/*
 *  app/Pms   production management system 生产管理系统
 * 
 */

namespace dp\app;
use dp\App as App;

class Pms extends App {


    public function router_db($dbn="stocker"){
        $mdn = $this->pn()."/".$dbn;
        $md = _model_($mdn);
        if(is_null($md)) _export_404_();
        return $md->manager();
    }

    static function tst(){
        return "\dp\app\Index::tst();";
    }

    //空规则，显示pms界面
    public function router_empty(){
        $data = [];
        
        $tpl = _template_("pms/main/main");
        return $tpl->assign($data)->parse()->export();
    }

    public function router_foo(){
        return _template_("pms/foo")->assign([])->parse()->export();

    }
}