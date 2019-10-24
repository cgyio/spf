<?php
/*
 *  数据模型  \dp\model\Usr
 * 
 */

namespace dp\model;
use dp\App as App;
use dp\Model as Model;

class Usr extends Model {

    public $dbtype = "jdb";
    public $dbn = "usr";

    /*
     *  curd
     */
    public function find_by_id($uid=0,$enabled=FALSE){
        if(!is_numeric($uid) || $uid<=0) return NULL;
        $where = "`id` = ".$uid;
        if(is_bool($enabled) && $enabled==TRUE) $where .= " AND `enabled` = TRUE";
        $rs = $this->select("usr",$where,NULL,1);
        return empty($rs) ? NULL : $rs[0];
    }
    


    //建立所有可用App的用户组
    public function _create_appusrgroup(){
        $apps = App::get_defined_apps();
        for($i=0;$i<count($apps);$i++){
            $appi = $apps[$i];
            $ug = $this->db->table("usrgroup")->where("`code` = '_APP_USR_".strtoupper($appi)."_'")->select();
            
            if(empty($ug)){
                $this->db->table("usrgroup")->insert([
                    [
                        "code" => "_APP_USR_".strtoupper($appi)."_",
                        "fid" => 3,
                        "name" => ucfirst($appi)."用户组",
                        "info" => ucfirst($appi)."用户组",
                        "app" => $appi,
                        "authority" => ""
                    ],
                    [
                        "code" => "_APP_ADMIN_".strtoupper($appi)."_",
                        "fid" => 4,
                        "name" => ucfirst($appi)."管理员组",
                        "info" => ucfirst($appi)."管理员组",
                        "app" => $appi,
                        "authority" => ""
                    ]
                ]);
            }
        }
    }

    public function export_cdt(){
        return $this->db->cdt();
    }
}