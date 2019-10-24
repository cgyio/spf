<?php
/*
 *  数据模型  \dp\app\pms\model\Stocker
 * 
 */

namespace dp\app\pms\model;
use dp\App as App;
use dp\Model as Model;

class Stocker extends Model {

    public $dbtype = "jdb";
    public $dbn = "pms/stocker";

    

    public function export_cdt(){
        return $this->db->cdt();
    }
}