<?php
/*
 *  DommyFramework controller控制器
 * 
 */

namespace dp;

class Ctrl {

    //已实例化的控制器集合
    public static $_LIST_ = [];

    //code
    protected $name = "";
    public $id = "";
    public $appname = "";

    public function __construct($option=[]){
        foreach($option as $k => $v){
            $this->$k = $v;
        }
        if(FALSE===$this->UAC()) trigger_error("dp/ctrl/noauth::".$this->code(),E_USER_ERROR);
        $this->id = self::_id();
        self::$_LIST_["ctrl_".$this->id] = $this;
    }
    
    //权限检查
    protected function UAC(){
        $ctrl_code = $this->code();
        //...

        return TRUE;
    }


    public function me(){return self::getctrl($this->id);}
    public function code(){return $this->appname=="" ? $this->name : $this->appname."/".$this->name;}
    public function app(){return $this->appname=="" ? NULL : _app_($this->appname);}

    //执行控制器功能，子类覆盖
    public function exec(){

        _export_("foobar");
    }




    /*
     *  static
     */
    protected static function _id(){
        $idx = count(self::$_LIST_);
        return _left_pad_($idx,4);
    }
    public static function getctrl($id="0000"){
        if(!is_numeric($id)) return NULL;
        $id = _left_pad_((int)$id,4);
        if(isset(self::$_LIST_["ctrl_".$id])){
            return self::$_LIST_["ctrl_".$id];
        }else{
            return NULL;
        }
    }
    public static function load($cn="",$option=[]){
        if(is_numeric($cn)){
            $ctrl = self::getctrl($cn);
            if(is_null($ctrl)) trigger_error("dp/ctrl/undefined::ctrl_".$cn,E_USER_ERROR);
            return $ctrl;
        }else{
            if(!is_string($cn) || $cn=="") trigger_error("dp/ctrl/needparam",E_USER_ERROR);
            if(!is_array($option)) $option = _to_array_($option);
            $cn = strtolower($cn);
            if(FALSE===strpos($cn,"/")){
                $cnn = ucfirst($cn);
                $cls = "\\dp\\ctrl\\".$cnn;
                $app = NULL;
            }else{
                $varr = explode("/",$cn);
                $cnn = ucfirst($varr[1]);
                $cls = "\\dp\\app\\".$varr[0]."\\ctrl\\".$cnn;
                $app = $varr[0];
            }
            if(class_exists($cls)){
                $option["name"] = $cnn;
                if(!is_null($app)){
                    $option["appname"] = ucfirst($app);
                }
                return new $cls($option);
            }else{
                trigger_error("dp/ctrl/undefined::".strtolower($cnn),E_USER_ERROR);
            }
        }
    }
}