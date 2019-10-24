<?php
/*
 *  DommyFramework app
 * 
 */

namespace dp;
use dp\Error as Error;
use dp\View as View;

class App {
    
    //已加载的App模块集合
    public static $_LIST_ = [];
    //App模块默认预设
    public static $_CONF_ = NULL;

    //options
    public $name = "";

    //包含的view集合
    //public $_VIEW_ = [];

    public function __construct($appname=""){
        $this->name = $appname;
        $this->_load_config();
        if(FALSE===$this->UAC()) trigger_error("app/noauth::".$this->name,E_USER_ERROR);
        $this->_init();
    }
    
    //权限检查
    protected function UAC(){
        //...

        return TRUE;
    }

    //初始化
    protected function _init(){

        $this->init();
    }
    //子类覆盖的init方法
    protected function init(){ }
    //App实例化完成后的init
    protected function _after_init(){
        $this->_load_router();
        $this->_load_errcode();

        $this->after_init();
    }
    //子类覆盖的after_init
    protected function after_init(){ }

    //加载App配置文件
    protected function _load_config(){
        $dft = self::$_CONF_;
        $cf = $this->_find("config.php");
        if(is_null($cf)){
            $conf = $dft;
        }else{
            $conf = require($cf);
            $conf = _array_extend_($dft,$conf);
        }
        foreach($conf as $k => $v){
            $this->$k = $v;
        }
    }
    //加载路由配置
    protected function _load_router(){
        if(FALSE===$this->dftrouter){
            _router_add_(
                $this->pn(),
                [
                    "_default_" => [
                        self::$_LIST_[$this->pn()],
                        "router"
                    ]
                ]
            );
        }
    }
    //加载App错误代码
    protected function _load_errcode(){
        $dir = $this->path("library/error");
        if(is_dir($dir)){
            $ecs = [];
            $ecs[$this->pn()] = [];
            $dh = opendir($dir);
            while(($f=readdir($dh)) !== FALSE){
                if(!is_dir($dir.DS.$f) && strpos(strtolower($f),'.json')!==FALSE){
                    $fn = str_replace(".json","",strtolower($f));
                    $earr = _j2a_(file_get_contents($dir.DS.$f));
                    $lang = _lang_();
                    if(isset($earr[$lang])){
                        $ec = $earr[$lang];
                    }else{
                        if(isset($earr[EXPORT_LANG])){
                            $ec = $earr[EXPORT_LANG];
                        }else{
                            $ec = $earr;
                        }
                    }
                    if($fn==$this->pn()){
                        $ecs[$this->pn()] = _array_extend_($ecs[$this->pn()],$ec);
                    }else{
                        $ecs[$this->pn()][$fn] = $ec;
                    }
                }
            }
            closedir($dh);
            if(!isset(Error::$codes["app"])) Error::$codes["app"] = [];
            Error::$codes["app"] = _array_extend_(Error::$codes["app"],$ecs);
        }
    }

    public function path($p=""){
        if(!is_string($p) || $p==""){
            $parr = [];
        }else{
            $p = _pathfix_($p);
            $parr = explode(DS,$p);
        }
        array_unshift($parr,$this->pn());
        return _app_path_(implode("/",$parr));
    }
    public function me(){return self::$_LIST_[$this->pn()];}
    public function pn($str=""){
        $arr = [];
        $arr[] = strtolower($this->name);
        if(is_string($str) && $str!="") $arr[] = $str;
        return implode("/",$arr);
    }

    //创建视图
    public function view($vn=""){
        return View::load($this->pn()."/".$vn);
    }

    //查找在特殊目录中查找文件
    protected function _find($fn="", $path=",".AUTOLOAD_PATH){
        return _res_find_($fn, $path, $this->path());
    }

    //App路由方法，子类覆盖
    public function router($uri=[]){
        $lvl = $uri["lvl"];
        $uris = $uri["uri"];
        if($uris[0]=="app") array_shift($uris);
        if($uris[0]==$this->pn()) array_shift($uris);
        return _router_common_($this,$uris);
    }
    public function router_empty(){
        return 1233;
    }



    /*
     *  static
     */
    public static function load_default_config(){
        if(is_null(self::$_CONF_)){
            $cf = _conf_path_("app.php");
            if(file_exists($cf)){
                self::$_CONF_ = require($cf);
            }else{
                self::$_CONF_ = [];
            }
        }
    }

    //默认App路由
    public static function _router($uri=[]){
        $lvl = $uri["lvl"];
        $uris = $uri["uri"];
        if($lvl<=1) trigger_error("dp/router/needparam",E_USER_ERROR);
        array_shift($uris); //app
        $app = array_shift($uris);
        $m = "_router_".$app;
        if(method_exists("\\dp\\App",$m)){
            return call_user_func_array(["\\dp\\App",$m],$uris);
        }else{
            $app = self::load($app);
            if(FALSE===$app->dftrouter){
                _export_404_();
            }else{
                return call_user_func_array([$app,"router"],[$uri]);
            }
        }
    }
    
    //加载App
    public static function load($app=""){
        if(_is_numarr_($app)){
            for($i=0;$i<count($app);$i++){
                self::load($app[$i]);
            }
        }else{
            if(!is_string($app) || $app=="") trigger_error("app/needparam",E_USER_ERROR);
            $app = strtolower($app);
            if(isset(self::$_LIST_[$app])) return self::$_LIST_[$app];
            $appname = ucfirst($app);
            $cls = "\\dp\\app\\".$appname;
            if(!class_exists($cls)) trigger_error("app/undefined::".$appname,E_USER_ERROR);
            self::$_LIST_[$app] = new $cls($appname);
            self::$_LIST_[$app]->_after_init();
            return self::$_LIST_[$app];
        }
    }

    //加载所有可用App
    public static function load_all(){
        $apps = self::_get_defined_apps();
        self::load($apps);
    }

    //判断给定的App是否存在
    public static function has($app=""){
        $apps = self::_get_defined_apps();
        return in_array(strtolower($app),$apps);
    }

    //获取可用App列表
    protected static function _get_defined_apps(){
        $apps = [];
        $dir = _app_path_();
        $dh = opendir($dir);
        while(($app=readdir($dh))!==FALSE){
            if(is_dir($dir.DS.$app) && $app!="." && $app!=".."){
                $appf = _res_find_(ucfirst($app).EXT,",".AUTOLOAD_PATH,$dir.DS.$app);
                if(!is_null($appf)){
                    $apps[] = $app;
                }
            }
        }
        closedir($dh);
        return $apps;
    }
    public static function get_defined_apps(){return self::_get_defined_apps();}



    /*
     *  静态路由方法
     */
    public static function _router_getapps(){
        return self::_get_defined_apps();
    }
}