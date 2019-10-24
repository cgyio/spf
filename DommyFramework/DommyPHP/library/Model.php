<?php
/*
 *  DommyFramework 数据模型类
 * 
 */

namespace dp;

class Model {

    //所有已创建模型实例
    public static $_LIST_ = [];

    //链接的数据库对象
    public $db = NULL;
    public $dbtype = "";
    public $dbn = "";
    //关联的Appname
    public $appname = "";

    public function __construct($mdtype=NULL){
        if(is_string($mdtype) && $mdtype!="") $this->dbtype = strtolower($mdtype);
        $dbcls = $this->dbcls();
        if(is_null($dbcls)) return FALSE;
        if($this->dbn=="") return FALSE;
        $this->db = $dbcls::load($this->dbn);
    }

    //获取当前Model链接的数据库类
    public function dbcls(){
        $cls = "\\dp\\".ucfirst($this->dbtype);
        if(!class_exists($cls) || !method_exists($cls,"load")) return NULL;
        return $cls;
    }
    //获取关联数据库实例
    public function dbo(){
        return $this->db;
    }
    //获取关联app
    public function app(){return $this->appname=="" ? NULL : _app_($this->appname);}
    //直接获取数据
    public function cdt($key=""){return $this->db->cdt($key);}

    /*
     *  curd
     */
    public function select($tbn="",$where=NULL,$order=NULL,$limit=NULL){
        if(FALSE===$this->db->has($tbn)) return [];
        $this->db->table($tbn);
        if(!is_null($where)) $this->db->where($where);
        if(!is_null($order)) $this->db->order($order);
        if(!is_null($limit)) $this->db->limit($limit);
        return $this->db->select();
    }
    public function insert($tbn="",$data=[]){
        if(FALSE===$this->db->has($tbn)) return FALSE;
        return $this->db->table($tbn)->insert($data);
    }
    public function update($tbn="",$data=[],$where=NULL){
        if(FALSE===$this->db->has($tbn)) return FALSE;
        $this->db->table($tbn);
        if(!is_null($where)) $this->db->where($where);
        return $this->db->update($data);
    }
    public function delete($tbn="",$where=NULL){
        if(FALSE===$this->db->has($tbn)) return FALSE;
        $this->db->table($tbn);
        if(!is_null($where)) $this->db->where($where);
        return $this->db->delete();
    }
    public function count($tbn="",$where=NULL){
        if(FALSE===$this->db->has($tbn)) return FALSE;
        $this->db->table($tbn);
        if(!is_null($where)) $this->db->where($where);
        return $this->db->count();
    }
    public function search(){
        $args = func_get_args();
        if(count($args)<2) return [];
        $tbn = array_shift($args);
        if(FALSE===$this->db->has($tbn)) return [];
        $this->db->table($tbn);
        return call_user_func_array([$this->db,"search"],$args);
    }

    /*
     *  custom curd
     */
    public function select_by_id($tbn="",$id=0,$option=NULL){
        if(FALSE===$this->db->has($tbn) || !is_numeric($id) || $id<=0) return NULL;
        $this->db->table($tbn);
        $where = "`id` = ".$id;
        if(is_string($option) && $option!="") $where .= " AND ".$option;
        $rs = $this->db->where($where)->limit(1)->select();
        return empty($rs) ? NULL : $rs[0];
    }
    public function delete_by_id($tbn="",$id=0,$option=NULL){
        if(FALSE===$this->db->has($tbn) || !is_numeric($id) || $id<=0) return FALSE;
        $this->db->table($tbn);
        $where = "`id` = ".$id;
        if(is_string($option) && $option!="") $where .= " AND ".$option;
        return $this->db->where($where)->limit(1)->delete();
    }
    public function update_by_id($tbn="",$id=0,$data=[],$option=NULL){
        if(FALSE===$this->db->has($tbn) || !is_numeric($id) || $id<=0) return FALSE;
        $this->db->table($tbn);
        $where = "`id` = ".$id;
        if(is_string($option) && $option!="") $where .= " AND ".$option;
        return $this->db->where($where)->limit(1)->update($data);
    }



    //数据库管理界面
    public function manager(){
        return $this->cdt("name")."管理界面";
    }






    /*
     *  static
     */
    //创建Model实例，参数形式  [dbtype(jdb)]/[modelname(app/model/aaa)]
    public static function load($md=""){
        if(!is_string($md) || $md=="") return NULL;
        $md = strtolower($md);
        $md = trim($md,"/");
        $mdkey = str_replace("/","_",$md);
        if(isset(self::$_LIST_[$mdkey])) return self::$_LIST_[$mdkey];
        $mda = explode("/",$md);
        $mdn = array_pop($mda);
        if(empty($mda)){
            //通用Model类
            $cls = "\\dp\\model\\".ucfirst($mdn);
        }else{
            //属于某个App的Model类
            $cls = "\\dp\\app\\".implode("\\",$mda)."\\model\\".ucfirst($mdn);
        }
        if(!class_exists($cls)) return NULL;
        self::$_LIST_[$mdkey] = new $cls();
        return self::$_LIST_[$mdkey];
    }

    //router
    public static function _router($uri=[]){
        $uris = $uri["uri"];
        array_shift($uris);     //model
        $mts = ["export","edit"];
        $mtype = NULL;
        $idx = -1;
        for($i=0;$i<count($mts);$i++){
            if(in_array($mts[$i],$uris)){
                $mtype = $mts[$i];
                $idx = array_search($mts[$i],$uris);
                break;
            }
        }
        if(is_null($mtype) || $idx<0){
            $mdn = implode("/",$uris);
            $md = self::load($mdn);
            if(empty($md)) _export_404_();
            //输出管理界面
            return call_user_func_array([$md,"manager"],[]);
        }
        $action = array_splice($uris,$idx);
        $mdn = implode("/",$uris);
        $md = self::load($mdn);
        if(empty($md)) _export_404_();
        array_shift($action);   //export or edit
        $method = $mtype."_".array_shift($action);
        if(method_exists($md,$method)){
            return call_user_func_array([$md,$method],$action);
        }else{
            return _export_404_();
        }
    }

}