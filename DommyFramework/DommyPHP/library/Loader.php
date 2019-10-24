<?php
/*
 *  DommyFramework自动加载
 * 
 */

//类自动加载时，默认查找的路径
//define("AUTOLOAD_PATH","library,model,view,page,plugin");

function _autoload_($class){
    $path = explode(",",AUTOLOAD_PATH);
    if($class=="" || empty($class)) trigger_error("dp/autoload/needparam",E_USER_ERROR);
    $carr = explode("\\",$class);
    if(count($carr)<=1){
        $clf = _autoload_find_("dp",$carr[0]);
    }else{
        $ns = array_shift($carr);   //namespace
        $fn = strtolower(array_pop($carr));     //class filename lowercase
        if(empty($carr)){
            $clf = _autoload_find_($ns,$fn);
        }else if(count($carr)<=1){
            if($carr[0]=="app"){
                $clf = _autoload_file_("app/".$fn,$fn);
            }else{
                if(!in_array($carr[0],$path)){
                    $clf = _autoload_find_($ns."/*/".$carr[0], $fn);
                }else{
                    $clf = _autoload_file_($ns."/".$carr[0], $fn);
                }
            }
        }else{
            if($carr[0]=="app"){
                array_shift($carr); //app
                $appname = strtolower(array_shift($carr));
                if(empty($carr)){
                    $clf = _autoload_find_("app/".$appname,$fn);
                }else{
                    if(in_array($carr[0],$path)){
                        $clf = _autoload_file_("app/".$appname."/".implode("/",$carr),$fn);
                    }else{
                        $clf = _autoload_find_("app/".$appname."/*/".implode("/",$carr),$fn);
                    }
                }
                //$dir = "app/".$appname."/*".(!empty($carr) ? "/".implode("/",$carr) : "");
                //$clf = _autoload_find_($dir,$fn);
            }else if(in_array($carr[0],$path)){
                $clf = _autoload_file_($ns."/".implode("/",$carr),$fn);
            }else{
                $dir = $ns."/*".(!empty($carr) ? "/".implode("/",$carr) : "");
                $clf = _autoload_find_($dir,$fn);
            }
        }
    }
    if(is_null($clf)) return FALSE; //trigger_error("dp/autoload/undefined::".$class,E_USER_ERROR);
    require_once($clf);
    if(class_exists($class)) return TRUE;
    return FALSE;   //trigger_error("dp/autoload/undefined::".$class,E_USER_ERROR);
}

function _autoload_find_($dir="", $fn=""){
    $f = _autoload_file_($dir, $fn);
    if(!is_null($f)) return $f;
    $path = explode(",",AUTOLOAD_PATH);
    $f = NULL;
    $di = "";
    for($i=0;$i<count($path);$i++){
        if(strpos($dir,"*")===FALSE){
            $di = $dir."/".$path[$i];
        }else{
            $di = str_replace("*",$path[$i],$dir);
        }
        $f = _autoload_file_($di,$fn);
        if(!is_null($f)) break;
    }
    return $f;
}

function _autoload_file_($dir="", $fn=""){
    if(strpos($dir,"*")!==FALSE){
        $darr = explode("/",$dir);
        array_splice($darr,array_search("*",$darr),1);
        $dir = implode("/",$darr);
    }
    if(!is_dir(_path_($dir))) return NULL;
    $fnd = strtolower($fn);
    $fnf = ucfirst($fnd);
    $f = _path_($dir."/".$fnf.EXT);
    if(file_exists($f)) return $f;
    $f = _path_($dir."/".$fnd."/".$fnf.EXT);
    if(file_exists($f)) return $f;
    return NULL;
}

// 注册系统自动加载
spl_autoload_register('_autoload_', true, true);

