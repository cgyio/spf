<?php
/*
 *  DommyFramework view 视图
 * 
 */

namespace dp;

class View {

    //已加载的视图实例
    public static $_LIST_ = [];

    //option
    public $name = "";
    //正则
    public $tag = "/\%\{[^\}\%]+\}\%/";
    //关联的App
    public $appname = "";

    //要显示的数据
    public $data = [];
    //模板内容
    public $tpl = [];

    public function __construct($vn=""){
        $this->name = $vn;
        //$this->_load_tpl();
    }

    //加载模板文件
    protected function load_tpl($tpl=""){
        $tpl = strtolower($tpl);
        if(isset($this->tpl[$tpl])) return $this->tpl[$tpl];
        if($this->appname==""){
            $tpldir = _tpl_path_($this->vn());
        }else{
            $app = $this->app();
            $tpldir = $app->path("tpl/".$this->vn());
        }
        $tplf = $tpldir.DS.$tpl.".tpl";
        if(file_exists($tplf)){
            $this->tpl[$tpl] = file_get_contents($tplf);
            return $this->tpl[$tpl];
        }
        return "";
    }

    //定义要输出的data
    public function assign($data=[]){
        $this->data = $data;
        return $this;
    }

    public function fetch($tpl=""){
        $tpl = $this->_load_tpl($tpl);
        return _str_tpl_($tpl,$this->data);
    }

    //获取关联App实例
    public function app(){
        if($this->appname=="") return NULL;
        return _app_($this->appname);
    }
    //获取小写Viewname
    public function vn(){return strtolower($this->name);}



    public function export(){
        return "view ".$this->name." here!";
    }


    //路由方法
    public function router($uri=[]){
        return _router_common_($this,array_splice($uri["uri"],0,2));
    }



    /*
     *  static
     */
    //加载视图
    public static function load($vn=""){
        if(!is_string($vn) || $vn=="") trigger_error("dp/view/needparam",E_USER_ERROR);
        $vn = strtolower($vn);
        $vo = _array_xpath_(self::$_LIST_,$vn);
        if(!is_null($vo)) return $vo;
        if(FALSE===strpos($vn,"/")){
            $vnn = ucfirst($vn);
            $cls = "\\dp\\view\\".$vnn;
            $app = NULL;
        }else{
            $varr = explode("/",$vn);
            $vnn = ucfirst($varr[1]);
            $cls = "\\dp\\app\\".$varr[0]."\\view\\".$vnn;
            $app = $varr[0];
        }
        if(class_exists($cls)){
            if(!is_null($app)){
                if(!isset(self::$_LIST_[$app])) self::$_LIST_[$app] = [];
                self::$_LIST_[$app][strtolower($vnn)] = new $cls($vnn);
                self::$_LIST_[$app][strtolower($vnn)]->appname = ucfirst($app);
                return self::$_LIST_[$app][strtolower($vnn)];
            }else{
                self::$_LIST_[strtolower($vnn)] = new $cls($vnn);
                return self::$_LIST_[strtolower($vnn)];
            }
        }else{
            trigger_error("dp/view/undefined::".strtolower($vnn),E_USER_ERROR);
        }
    }

    //静态路由
    public static function _router($uri=[]){
        $uris = $uri["uri"];
        $lvl = $uri["lvl"];
        if($lvl<=1) trigger_error("dp/view/needparam",E_USER_ERROR);
        array_shift();  //view
        $vn = array_shift();
        $view = self::load($vn);
        return call_user_func_array([$view,"router"],[$uri]);
    }



    /*
     *  
     */
}