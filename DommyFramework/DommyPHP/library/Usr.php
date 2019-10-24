<?php
/*
 *  DommyFramework Usr 用户类
 * 
 */

namespace dp;
use dp\Router as Router;
use dp\App as App;

class Usr {

    //当前用户实例
    public static $current = NULL;
    //Usr数据库实例
    //public static $db = NULL;
    //Usr数据模型
    public static $model = NULL;
    //所有操作树
    protected static $_OPRS_ = [];
    //默认的全局权限要求
    protected static $_UACS_ = ["_NEED_LOGIN_"];

    //保存用户数据
    public $_data = [];
    public $_login = [];
    public $_uid = 0;
    public $_logid = 0;
    public $_uidx = -1;
    public $_logidx = -1;
    public $_uac = [];

    public function __construct(){
        $ud = $this->_usesession();
        if(is_null($ud)){
            $ud = $this->_useguest();
        }
        $this->_resetud($ud);
    }
    
    //获取、设置用户数据
    public function data($key="",$val=NULL){
        if(!is_string($key)) return NULL;
        $key = trim($key,"/");
        if(strpos($key,"/")===FALSE){
            if($key==""){
                return [
                    "usr" => $this->_data,
                    "login" => $this->_login,
                    "uid" => $this->_uid,
                    "logid" => $this->_logid,
                    "uidx" => $this->_uidx,
                    "logidx" => $this->_logidx,
                    "uac" => $this->_uac
                ];
            }
            $prop = "_".$key;
            if(!property_exists($this,$prop)) return NULL;
            return $this->$prop;
        }else{
            $karr = explode("/",$key);
            $prop = "_".array_shift($karr);
            $k = implode("/",$karr);
            if(!property_exists($this,$prop)) return NULL;
            $dt = $this->$prop;
            if(!is_array($dt)) return NULL;
            if(is_null($val)){
                return _array_xpath_($dt,$k);
            }else{
                if(!$this->islogin()) return _array_xpath_($dt,$k);
                //$this->$prop = _array_xpath_($dt,$k,$val);
                switch($prop){
                    case "_data" :
                        $this->edit("usr",[$k=>$val]);
                        $rst = $this->_data;
                        break;
                    case "_login" :
                        $this->edit("usrlogin",[$k=>$val]);
                        $rst = $this->_login;
                        break;
                    case "_uac" :
                        $rst = "";
                        break;
                }
                return $rst;
            }
        }
    }
    //获取uid
    public function uid(){return $this->_data["id"];}
    //获取当前用户的状态
    public function islogin(){return !empty($this->_login) && $this->_login["id"]!=0;}
    //编辑
    public function edit($tbn="usr",$data=[],$where=NULL){
        if(!is_array($data) || empty($data)) return FALSE;
        $ws = [
            "usr" => "`id` = '".$this->_uid."'",
            "usrlogin" => "`id` = '".$this->_logid."'",
            "usrgroup" => "`id` = '".$this->_data["group"]."'"
        ];
        if(!array_key_exists($tbn,$ws)) return FALSE;
        $where = $ws[$tbn].(!is_string($where) || $where=="" ? "" : " AND ".$where);
        self::$model->update($tbn,$data,$where);
        $this->reload($tbn);
    }
    //reload usrdata
    public function reload($tbn="usr"){
        switch($tbn){
            case "usr" :
                $this->_data = self::$model->select_by_id("usr",$this->_uid);
                break;
            case "usrlogin" :
                $this->_login = self::$model->select_by_id("usrlogin",$this->_logid);
                break;
        }
    }

    

    //重置\设置usrdata
    protected function _resetud($ud=[]){
        $this->_data = [];
        $this->_login = [];
        $this->_uid = 0;
        $this->_logid = 0;
        $this->_uidx = -1;
        $this->_logidx = -1;
        $this->_uac = [];
        if(is_array($ud) && !empty($ud)){
            foreach($ud as $k => $v){
                $this->$k = $v;
            }
        }
        $this->_createuac();
    }
    //根据session建立用户对象，返回usrdata
    protected function _usesession(){
        $uid = _session_get_("usr_id",NULL);
        if(is_null($uid)) return NULL;
        $urs = self::$model->select_by_id("usr",$uid,"`enabled` = TRUE");
        if(is_null($urs)) return NULL;
        $ud = [
            "_uid" => $uid,
            "_uidx" => $urs["_rsidx_"],
            "_data" => $urs
        ];

        $logid = _session_get_("usr_logid",NULL);
        if(!is_null($logid)){
            $logrs = self::$model->select_by_id("usrlogin",$logid,"`expire` = FALSE AND `logout` = FALSE AND `enabled` = true");
            if(is_null($logrs)) return $ud;
            $ts = (int)$logrs["timestamp"];
            $ct = time();
            if($ts+USR_EXPIRE<$ct){  //登录已过期
                self::$model->update_by_id("usrlogin",$logid,["expire"=>TRUE]);
                return $ud;
            }else{
                self::$model->update_by_id("usrlogin",$logid,["timestamp"=>$ct]);
                $ud = _array_extend_($ud,[
                    "_logid" => $logid,
                    "_logidx" => $logrs["_rsidx_"],
                    "_login" => $logrs
                ]);
            }
        }
        return $ud;
    }
    //未登录情况下，建立一个默认的游客用户对象，返回usrdata
    protected function _useguest(){
        _session_del_("usr_id");
        _session_del_("usr_logid");
        return [
            "_data" => self::$model->cdt("tables/usr/default"),
            "_login" => self::$model->cdt("tables/usrlogin/default")
        ];
    }
    //建立UAC树
    protected function _createuac(){
        $this->_uac = [];
    }

    //登录
    public function dologin($uid=0,$session=[]){

    }
    //登出
    public function dologout(){
        $this->edit("usrlogin",[
            "logout" => TRUE,
            "logouttime" => time()
        ]);
        $this->_resetud($this->_useguest());
    }
    //检查当前登录信息中包含的session信息
    public function logsession($key="",$val=NULL){
        if(!$this->islogin()) return NULL;
        if(!is_string($key) && !is_array($key)) return NULL;
        $ss = $this->data("login/session");
        if(is_null($ss)) return NULL;
        if($ss==""){
            $ss = [];
        }else{
            $ss = _to_array_($ss);
        }
        if(is_array($key)){
            $ss = _array_extend_($ss,$key);
            $this->edit("usrlogin",["session"=>_a2u_($ss)]);
            return $this->data("login/session");
        }else if(is_string($key)){
            if(is_null($val)){
                return $key=="" ? $ss : (isset($ss[$key]) ? $ss[$key] : NULL);
            }else{
                $ss[$key] = $val;
                $this->edit("usrlogin",["session"=>_a2u_($ss)]);
                return $this->data("login/session");
            }
        }
        return NULL;
    }

    //uac检查
    public function uac($xpath=""){
        if(in_array($xpath,self::$_UACS_)){
            $m = "_uac".strtolower($xpath);
            if(method_exists($this,$m)){
                return call_user_func_array([$this,$m],[$xpath]);
            }
        }
        return TRUE;
    }
    protected function _uac_need_login_($xpath=""){
        return TRUE;


        if($this->islogin()) return TRUE;
        $v = _view_("login");
        $h = $v->assign([])->fetch("default");
        _export_($h);
        die();
    }

    
    //测试，登录用户uid=1,logid=1
    public function testlogin($uid=1,$logid=1){
        _session_set_("usr_id",$uid);
        _session_set_("usr_logid",$logid);
        //self::$db->table("usrlogin")->where("{{`id` = ".$logid."}}")->update([
        self::$model->update_by_id("usrlogin",$logid,[
            "timestamp" => time(),
            "expire" => FALSE,
            "logout" => FALSE,
            "logouttime" => 0
        ]);
        $ud = $this->_usesession();
        $this->_resetud($ud);
    }
    



    /*
     *  static
     */
    //按logsession检索用户
    public static function find_by_logsession(){
        $args = func_get_args();
        array_unshift($args,"usrlogin");
        return call_user_func_array([self::$model,"search"],$args);
    }
    
    //检查uid是否存在
    public static function has_id($uid=0){
        $us = self::$model->select_by_id("usr",$uid);
        return !empty($us);
    }

    //生成全局Usr对象
    public static function create(){
        //建立Usr数据模型
        self::_create_model();
        //建立所有可用App的用户组
        self::$model->_create_appusrgroup();
        //根据路由建立用户对象
        $app = Router::appname();
        $cls = "\\dp\\Usr";
        /*if(!is_null($app)){
            $cls = "\\dp\\app\\".strtolower($app)."\\".ucfirst($app)."usr";
        }
        if(!class_exists($cls)) $cls = "\\dp\\Usr";*/
        self::$current = new $cls();
    }

    //建立用户数据库模型
    protected static function _create_model(){
        if(is_null(self::$model)) self::$model = _model_("usr");
        return self::$model;
    }

    //路由方法
    public static function _router($uri=[]){
        $uris = $uri["uri"];
        array_shift($uris); //usr
        $usr = self::$current;
        if(empty($uris)) return $usr->data();
        $m = array_shift($uris);
        switch($m){
            case "logout" :
                $usr->dologout();
                return $usr->data();
                break;
            case "testlogin" :
                call_user_func_array([$usr,"testlogin"],$uris);
                return $usr->data();
                break;
            case "test" :

                break;
            

            default :
                $rm = "router_".$m;
                if(method_exists($usr,$rm)){
                    return call_user_func_array([$usr,$rm],$uris);
                }else{
                    return NULL;
                }
                break;
        }
        
    }
}