<?php
/*
 *  DommyFramework 插件
 * 
 */

namespace dp;

class Plugin {

	//已经加载的Plugin
	public static $loaded = array();

	public function __construct(){

    }
    
    //默认路由方法，子类覆盖
    public function router($uri=array()){
        http_response_code(403);
        die();
    }

	//加载插件    $p 插件名(插件文件夹名称)
	public static function load($p){
		if(isset(self::$loaded[$p]) && !empty(self::$loaded)){
			return self::$loaded[$p];
		}else{
			$ppf = _plugin_path_($p."/".ucfirst($p).".php");
			if(!file_exists($ppf)){
				trigger_error("dp/plugin/undefined::".ucfirst($p),E_USER_ERROR);
			}else{
                require_once($ppf);
                $cls = "\\dp\\plugin\\".ucfirst($p);
                if(class_exists($cls)){
                    self::$loaded[$p] = new $cls();
                    return self::$loaded[$p];
                }else{
                    trigger_error("dp/plugin/noclass::".ucfirst($p),E_USER_ERROR);
                    return NULL;
                }
			}
		}
    }
    
    //路由方法
    public static function _router($uri=array()){
        if($uri["lvl"]<=1){
            return "";
        }else{
            $uris = $uri["uri"];
            array_shift($uris);
            $p = array_shift($uris);
            $p = self::load($p);
            if(is_null($p)) _export_500_();
            $m = "router";
            if($uri["lvl"]>2){
                if(method_exists($p,$uris[0])){
                    $m = array_shift($uris);
                }
            }
            if(method_exists($p,$m)){
                if($m=="router"){
                    return call_user_func_array(array($p,$m),[$uri]);
                }else{
                    return call_user_func_array(array($p,$m),$uris);
                }
            }else{
                return "";
            }
        }
    }
}