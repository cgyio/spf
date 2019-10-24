<?php
/*
 *  数据模型  \dp\app\index\model\Usr
 */

namespace dp\app\index\model;
use dp\Model as Model;

class Usr extends Model {

    public $dbtype = "jdb";
    public $dbn = "index/usr";
    public $appname = "index";
    
    public function export_tst(){
        return $this->db->cdt();
    }

    public function edit_foo($a,$b){
        return $a+$b;
    }
}