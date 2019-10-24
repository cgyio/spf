<?php
/*
 *  DommyFramework工具函数库
 * 
 */



/*
 *  Path
 */
function _pathfix_($dir=""){
    if(!is_string($dir) || $dir=="") return "";
    $dir = trim($dir);
    $dir = str_replace("/",DS,$dir);
    $dir = str_replace("\\",DS,$dir);
    $dir = trim($dir, DS);
    return $dir;
}
function _pathget_($dir="root"){
    $cnst = strtoupper($dir)."_PATH";
    return defined($cnst) ? constant($cnst) : NULL;
}
function _path_($dir=""){
    $dir = _pathfix_($dir);
    if($dir=="") return NULL;
    $darr = explode(DS,$dir);
    if(count($darr)<1) return NULL;
    $pn = array_shift($darr);
    $p = _pathget_($pn);
    if(is_null($p)) return NULL;
    if(count($darr)<=0) return $p;
    return $p.DS.implode(DS, $darr);
}
function _root_($dir=""){return _path_("root/".$dir);}
function _app_path_($dir=""){return _path_("app/".$dir);}
function _df_path_($dir=""){return _path_("df/".$dir);}
function _dp_path_($dir=""){return _path_("dp/".$dir);}
function _lib_path_($dir=""){return _path_("dp/library/".$dir);}
function _conf_path_($dir=""){return _path_("dp/library/config/".$dir);}
function _tpl_path_($dir=""){return _path_("dp/tpl/".$dir);}
function _view_path_($dir=""){return _path_("dp/view/".$dir);}
function _plugin_path_($dir=""){return _path_("dp/plugin/".$dir);}
function _dj_path_($dir=""){return _path_("dj/".$dir);}
function _du_path_($dir=""){return _path_("du/".$dir);}



/*
 *  request
 */
function _server_($key){
    $key = strtoupper($key);
    return isset($_SERVER[$key]) ? $_SERVER[$key] : NULL;
}
//protocol
function _protocol_(){
    if($_SERVER["SERVER_PROTOCOL"]=="HTTP/1.1"){
        if(isset($_SERVER["HTTPS"])){
            if($_SERVER["HTTPS"]=="off" || empty($_SERVER["HTTPS"])){
                return "http";
            }else{
                return "https";
            }
        }else{
            return "http";
        }
    }else{
        return "https";
    }
}
//host
function _host_(){return _protocol_()."://".$_SERVER["HTTP_HOST"];}
//uri
function _uri_($url=NULL){
    $uri = is_null($url) ? $_SERVER["REQUEST_URI"] : $url;
    $uri = urldecode($uri);
    //parse uri
    if(strpos($uri,"?")!==FALSE){
        $uria = explode("?",$uri);
        $uri = array_shift($uria);
        $querystring = implode("?",$uria);
    }else{
        $querystring = "";
    }
    //解析自定义url
    if(!is_null($url) && !empty(_u2a_($querystring))){
        $_GET = _array_extend_($_GET,_u2a_($querystring));
    }
    if(strpos($uri,"/")!==FALSE){
        $uris = explode("/",$uri);
        array_shift($uris);
    }else{
        $uris = array();
        $uris[] = $uri;
    }
    if(empty($uris[count($uris)-1])){
        array_pop($uris);
    }
    
    return array(
        "uristring" => urldecode($_SERVER["REQUEST_URI"]),
        "uri" => $uris,
        "lvl" => count($uris),
        "querystring" => $querystring,
        "query" => _u2a_($querystring)
    );
}
//生成url
function _url_($u=""){
    if(strpos($u,"//")!==FALSE){
        return $u;
    }
    $h = _host_();
    return empty($u) ? $h : $h."/"._str_del_($u,"/","left");
}
//method
function _method_(){return $_SERVER["REQUEST_METHOD"];}
//lang 语言
function _lang_(){return _get_("lang",EXPORT_LANG);}
//url
function _selfurl_($full=FALSE){
    $uri = $_SERVER["REQUEST_URI"];
    if(empty($uri)){
        return _host_();
    }else{
        if(strpos($uri,"?")!==FALSE){
            $uris = explode("?",$uri);
            return $full==FALSE ? _host_().$uris[0] : _host_().$uri;
        }else{
            return _host_().$uri;
        }
    }
}
//客户端IP
function _clientip_(){
    $arr_ip_header = array(
        'HTTP_CDN_SRC_IP',
        'HTTP_PROXY_CLIENT_IP',
        'HTTP_WL_PROXY_CLIENT_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR'
    );
    $client_ip = 'unknown';
    foreach ($arr_ip_header as $key){
        if (!empty($_SERVER[$key]) && strtolower($_SERVER[$key]) != 'unknown'){
            $client_ip = $_SERVER[$key];
            break;
        }
    }
    return $client_ip;
}
//$_GET
function _get_($key=array(),$val=NULL){
    if(is_array($key)){
        if(empty($key)){return $_GET;}
        $p = array();
        foreach($key as $k => $v){
            $p[$k] = _get_($k,$v);
        }
        return $p;
    }else{
        return isset($_GET[$key]) ? $_GET[$key] : $val;
    }
}
//$_POST
function _post_($key=array(),$val=NULL){
    if(is_array($key)){
        if(empty($key)){return $_POST;}
        $p = array();
        foreach($key as $k => $v){
            $p[$k] = _post_($k,$v);
        }
        return $p;
    }else{
        return isset($_POST[$key]) ? $_POST[$key] : $val;
    }
}
//php://input，输入全部转为json，返回array
function _input_($in="json"){
    $input = file_get_contents("php://input");
    if(empty($input)){
        $input = _session_get_("_php_input_",NULL);
        if(is_null($input)) return NULL;
        _session_del_("_php_input_");
    }
    $output = NULL;
    switch($in){
        case "json" :
            $output = _j2a_($input);
            break;
        case "xml" :
            $output = _x2a_($input);
            break;
        case "url" :
            $output = _u2a_($input);
            break;
        default :
            $output = _to_array_($input);
            break;
    }
    return $output;
}
//获取请求来源域名，****不安全，易伪造****
function _referer_(){
    if(isset($_SERVER["HTTP_REFERER"])){
        $url = $_SERVER["HTTP_REFERER"];		//获取完整的来路URL，不是任何时候都有效
        $urls = explode("://",$url);
        $protocol = $urls[0];
        $domain = explode("/",$urls[1]);
        $domain = $domain[0];
        //return $protocol."://".$domain;
        return $domain;
    }else{
        return DOMAIN;
    }
}
//判断请求来源是否是AJAX
function _is_ajax_() {return !(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest');}
//当使用AJAX访问时，检查请求域名是否在白名单中  DOMAIN_AJAXALLOWED
function _ajax_allowed_(){
    if(_is_ajax_()){
        $list = DOMAIN_AJAXALLOWED;
        $list = explode("|",$list);
        $referer = _referer_();
        if(in_array($referer,$list)){
            //白名单域名，添加header
            header('Access-Control-Allow-Origin:http://'.$referer);
            header('Access-Control-Allow-Methods:POST');
            header('Access-Control-Allow-Headers:x-requested-with,content-type');
        }
    }
}
//微信接口的curl方法
function _curl_($url, $data = null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}



/*
 *  response
 */
//检查是否已调用过header方法了，没有则调用
function _header_(){
    if(!headers_sent()){
        return call_user_func_array("header",func_get_args());
    }else{
        trigger_error("dp/response/headersent",E_USER_ERROR);
    }
}
//以JS方式跳转页面
function _js_location_($loc=""){
    $u = _url_($loc);
    echo '<script type="text/javascript">window.location.href = "'.$u.'";</script>';
    die();
}
//输出格式
function _format_($format=NULL){
    if(empty($format) || !in_array(strtolower($format),explode(",",EXPORT_FORMATS))){
        return _get_("format",EXPORT_FORMAT);
    }else{
        return strtolower($format);
    }
}
//默认返回数据
function _default_response_(){
    return [
        "errcode" => 0,
        "errmsg" => "",
        "errors" => [],
        "data" => NULL
    ];
}
//数据处理
function _parse_data_($data=array(),$default=NULL){
    if(!is_array($data)){
        $data = ["data"=>$data];
    }else{
        if(array_key_exists(0,$data)){
            $data = ["data"=>$data];
        }else if(!isset($data["data"])){
            if(!isset($data["errcode"]) || $data["errcode"]==0){
                $data = ["data"=>$data];
            }
        }
    }
    /*if(!isset($data["data"]) && !is_null($data["data"])){
        if(!isset($data["errcode"]) || $data["errcode"]==0){
            $data = ["data"=>$data];
        }
    }*/
    if(is_null($default)){
        $default = _default_response_();
    }else{
        $default = _array_extend_(_default_response_(), _to_array_($default));
    }
    $data = _array_extend_($default, $data);
    //如果存在错误，则把错误信息添加到要输出的数据里
    if($data["errcode"]==0){
        if(!empty(\dp\Error::$errs)){
            $data["errors"] = array();
            foreach(\dp\Error::$errs as $k => $err){
                $data["errors"][$k] = (array)$err;
            }
        }else{
            if(isset($data["errors"])) unset($data["errors"]);
        }
    }else{
        if(isset($data["errors"])) unset($data["errors"]);
    }
    //其他操作
    //..

    //输出前处理字符串
    //$data = DommyPHP::safe_output($data);
    return $data;
}
//输出
function _export_($data=array(), $format=NULL, $default_data=NULL){
    //对数据进行统一处理
    $data = _parse_data_($data, $default_data);
    $format = _format_($format);
    
    switch($format){
        case "xml" :
            //输出XML
            _header_("content-type:text/xml; charset=utf-8");
            echo _a2x_($data);
            break; 
        case "json" :
            //输出JSON
            if(_is_ajax_()) _ajax_allowed_();
            _header_("content-type:application/json; charset=utf-8");
            echo _a2j_($data);
            break;
        case "html" :
            //输出HTML
            _header_("content-type:text/html; charset=utf-8");
            echo isset($data["data"]) ? _to_string_($data["data"]) : "";
            break;
        case "page" :
            //输出HTML
            $html = "";
            if(is_string($data["tpl"])){
                $tplf = _tpl_path_($data["tpl"].".tpl");
                if(file_exists($tplf)){
                    $tpl = file_get_contents($tplf);
                }else{
                    $tpl = $data["tpl"];
                }
                $html = _str_tpl_($tpl,$data);
            }else if(isset($data["data"])){
                $html = _to_string_($data["data"]);
            }
            _header_("content-type:text/html; charset=utf-8");
            echo $html;
            break;
        case "str" :
            //echo isset($data["data"]) ? (is_string($data["data"]) ? $data["data"] : _to_string_($data["data"])) : "";
            echo isset($data["data"]) ? (is_string($data["data"]) ? $data["data"] : "") : "";
            break;
        case "dump" :
            var_dump($data);
            break;
    }
    //return $data;
    die();
}
//快捷输出
function _export_json_($data){
    $data = _to_array_($data);
    _export_($data,"json");
}
function _export_str_($str=""){
    echo $str;
    die();
}
//输出404
function _export_404_($format=NULL, $tpl=NULL){
    $format = _format_($format);
    if($format=="page"){
        $data = ["tpl"=>is_string($tpl)?"404/".$tpl:"404"];
        _export_($data, $format);
    }else{
        //header("HTTP/1.1 404 Not Found");
        //header("status: 404 Not Found");
        http_response_code(404);
    }
    die();
}
//输出500
function _export_500_($format=NULL, $tpl=NULL){
    $format = _format_($format);
    if($format=="page"){
        $data = ["tpl"=>is_string($tpl)?"500/".$tpl:"500"];
        _export_($data, $format);
    }else{
        //header("HTTP/1.1 500 Internal Server Error'");
        //header("status: 500 Internal Server Error'");
        http_response_code(500);
    }
    die();
}



/*
 *  router
 */
function _router_($url=NULL,$post=[]){return \dp\Router::run($url,$post);}
function _router_add_(){return call_user_func_array(array("\\dp\\Router","add_rule"), func_get_args());}
function _router_default_($rule=array()){return \dp\Router::set_default_rule($rule);}
function _router_rules_($key=NULL){return is_null($key) ? \dp\Router::$rules : (isset(\dp\Router::$rules[$key]) ? \dp\Router::$rules[$key] : NULL);}
function _router_app_(){return \dp\Router::appname();}
function _router_common_($obj,$param=[]){return \dp\Router::common($obj,$param);}
function _router_empty_($obj,$param=[]){return \dp\Router::emptyrule($obj,$param);}



/*
 *  usr
 */
function _usr_(){return \dp\Usr::$current;}
function _uac_($opr=""){return _usr_()->uac($opr);}



/*
 *  Model
 */
function _model_($md=""){return \dp\Model::load($md);}



/*
 *  ctrl
 */
function _ctrl_($cn="",$option=[]){return \dp\Ctrl::load($cn,$option);}



/*
 *  view
 */
function _view_($vn=""){return \dp\View::load($vn);}



/*
 *  template
 */
function _template_($tplc=""){return \dp\Template::load($tplc);}
/*function _template_($tplc=""){
    $tplf = \dp\Template::tplf($tplc);
    if($tplf===FALSE){
        return \dp\Template::load_str($tplc);
    }else{
        return \dp\Template::load($tplc);
    }
}*/



/*
 *  DommyJS/DommyUI export
 */
function _dj_export_($ftype="js"){
    $uri = _uri_();
    $lvl = $uri["lvl"];
    $uris = $uri["uri"];
    if($lvl<=1) trigger_error("dp/router/needparam",E_USER_ERROR);
    array_shift($uris); //js
    $fullpath = implode("/",$uris);
    $ffn = array_pop($uris);
    $farr = explode(".",$ffn);
    $fext = strtolower(array_pop($farr));
    if(strtolower($ftype)!=$fext) _export_404_();
    $fn = implode(".",$farr);
    $uris[] = $ffn;
    $fpfn = implode(DS,$uris);
    if(\dp\App::has($uris[0])){ //app中资源
        $appname = array_shift($uris);
        $fpfn = implode(DS,$uris);
        $f = _res_find_($fpfn,",res,".AUTOLOAD_PATH,_app_path_($appname));
    }else{
        $f = _res_find_($fpfn,",".AUTOLOAD_PATH,_dj_path_());
        if(is_null($f)) $f = _res_find_($fpfn,",".AUTOLOAD_PATH,_du_path_());
    }
    if(is_null($f) || !file_exists($f)) _export_404_();
    return _res_export_($f);
}
function _dj_js_(){return _dj_export_("js");}
function _dj_css_(){return _dj_export_("css");}



/*
 *  resource
 */
//路由方法，根据路由获取文件内容，根据文件类型，输出文件
function _res_(){
    $uri = _uri_();
    $lvl = $uri["lvl"];
    $uris = $uri["uri"];
    if($lvl<=2) trigger_error("dp/router/needparam",E_USER_ERROR);
    array_shift($uris); //res
    $path = array_shift($uris);
    if(in_array($path,["http","https"])){   //外部资源
        $fpath = $path."://".implode("/",$uris);
    }else if($path=="export"){
        $fn = array_pop($uris);
        if(strpos($fn,".")===FALSE) trigger_error("dp/resource/illegalname",E_USER_ERROR);
        $farr = explode(".",$fn);
        $fext = strtolower(array_pop($farr));
        $ffn = implode(".",$farr);
        $fpath = _url_(implode("/",$uris)."/".$ffn."?format=str");
        $content = file_get_contents($fpath);
        $mo = _plugin_("mime");
        $mo->export_header($fext,$fn);
        echo $content;
        die();
    }else{
        $pathfunc = function_exists("_".$path."_") ? "_".$path."_" : (function_exists("_".$path."_path_") ? "_".$path."_path_" : NULL);
        if(is_null($pathfunc)) trigger_error("dp/resource/notexists",E_USER_ERROR);
        if($path=="app"){
            if($lvl<=3) trigger_error("dp/resource/notexists",E_USER_ERROR);
            $appname = array_shift($uris);
            if(!is_dir(_app_path_($appname))) trigger_error("dp/resource/notexists",E_USER_ERROR);
            if(file_exists(_app_path_($appname."/".implode("/",$uris)))){
                $fpath = _app_path_($appname."/".implode("/",$uris));
            }else{
                $fpath = _app_path_($appname."/res/".implode("/",$uris));
            }
        }else{
            $fpath = $pathfunc(implode("/",$uris));
        }
    }
    //return $fpath; 
    if(!_res_exists_($fpath)) _export_404_();
    return _res_export_($fpath);
}
//输出指定路径的文件
function _res_export_($path){
    //return $path."foobar";
    return _plugin_("mime")->export($path);
}
//判断文件是否存在，支持本服务器文件和远程文件
function _res_exists_($path){
    if(_res_islocal_($path)){
        return file_exists($path);
    }else{
        $fh = @fopen($path,"r");
        if($fh===FALSE){
            $rst = FALSE;
        }else{
            $rst = TRUE;
        }
        fclose($fh);
        return $rst;
    }
}
//获取文件的content-type（mime）
function _res_mime_($path){
    if(!is_dir($path) && _res_exists_($path)){
        $mo = _plugin_("mime");
        $mime = $mo->get(_res_ext_($path));
        if(_res_islocal_($path) && $mime==$mo->default){
            $mime = mime_content_type($path);
        }
        return $mime;
    }else{
        return NULL;
    }
}
//获取文件后缀名
function _res_ext_($path=""){
    if(!empty($path) && strpos($path,".")!==FALSE){
        $fa = explode(".",$path);
        $fext = array_pop($fa);
        return strtolower($fext);
    }else{
        return NULL;
    }
}
//解析文件类型
//function _res_type_($path){
//    $fext = _res_ext_($path);
    /*if($fext=="js"){return "js";}
    if($fext=="css"){return "css";}
    if($fext=="svg"){return "svg";}*/
/*    $ct = _plugin_("content_type")->get($fext);
    if(!empty($ct) && $ct!="application/octet-stream"){
        $cta = explode("/",$ct);
        return $cta[0];
    }else{
        return "unknown";
    }
}*/
//获取文件名称/路径
function _res_path_($path){
    if(!empty($path) && strpos($path,".")!==FALSE){
        $fpath = _res_fixpath_($path);
        if(strpos($fpath,DS)!==FALSE){
            $fna = explode(DS,$fpath);
            $fn = array_pop($fna);
            $fp = implode(DS,$fna);
        }else{
            $fn = $fpath;
            $fp = "";
        }
        $fa = explode(".",$fn);
        $fext = array_pop($fa);
        return array(
            "name" => implode(".",$fa),
            "fullname" => $fn,
            "ext" => strtolower($fext),
            "path" => $fp,
            "fullpath" => $path
        );
    }else{
        return NULL;
    }
}
//获取文件相关信息
function _res_info_($path){
    if(!is_dir($path) && _res_exists_($path)){
        clearstatcache();	//清除文件状态缓存
        $fi = _res_path_($path);
        $fi["size"] = filesize($path);	//字节数
        $fi["mtime"] = filemtime($path);	//文件最后修改时间戳
        //$fi["mime"] = _plugin_("content_type")->get($fi["ext"]);
        $fi["type"] = _res_type_($path);
        if($fi["type"]=="image" && !in_array($fi["ext"],array("dwg","psd"))){
            $imgsize = @getimagesize($path);
            if($imgsize!==FALSE){
                $fi["imgsize"] = array($imgsize[0],$imgsize[1]);
            }else{
                $fi["imgsize"] = array(NULL,NULL);
            }
        }else{
            $fi["imgsize"] = array(NULL,NULL);
        }
        $fi["islocal"] = _res_islocal_($path);
        return $fi;
    }else{
        return NULL;
    }
}
//判断文件是否为远程文件
function _res_islocal_($path){
    if(strpos($path,"://")!==FALSE){
        return FALSE;
    }
    return TRUE;
}
//处理文件路径，\ => /
function _res_fixpath_($path=""){
    $fp = str_replace("\\",DS,$path);
    $fp = str_replace("/",DS,$path);
    return $fp;
}
//find，在多个路径中查找文件，返回第一个找到的文件路径
function _res_find_($fn="",$path=array(),$indir=NULL){
    $path = !is_array($path) ? _to_array_($path) : $path;
    if(!_is_numarr_($path)) return NULL;
    $indir = !is_string($indir) ? _root_() : $indir;
    $fpath = NULL;
    for($i=0;$i<count($path);$i++){
        $fp = $indir.($path[$i]=="" ? "" : DS.$path[$i]).DS.$fn;
        if(file_exists($fp)){
            $fpath = $fp;
            break;
        }
    }
    return $fpath;
}



/*
 *  plugin
 */
function _plugin_(){return call_user_func_array(array("\\dp\\Plugin","load"), func_get_args());}



/*
 *  wechat
 */
function _wx_(){
    
}
function _wx_router_($uri=[]){
    $uris = $uri["uri"];
    array_shift($uris); //wx
    return $uris;
}



/*
 *  session
 */
//保存
function _session_set_($key=array(),$val=""){
    if(is_array($key)){
        foreach($key as $k => $v){
            $_SESSION[$k] = $v;
        }
    }else{
        $_SESSION[$key] = $val;
    }
}
//读取
function _session_get_($key, $dft=NULL){
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $dft;
}
//删除
function _session_del_($key){
    if(isset($_SESSION[$key])){
        $_SESSION[$key] = NULL;
        unset($_SESSION[$key]);
    }
}
//判断
function _session_has_($key){
    return isset($_SESSION[$key]) && $_SESSION[$key]!=NULL;
}



/*
 *  cookie
 */
//set
function _cookie_set_($key, $val=NULL, $expire=NULL){
    if(is_array($key)){
        foreach($key as $k => $v){
            _cookie_set_($k,$v,$expire);
        }
    }else{
        if(is_null($val)){
            setCookie($key,NULL);
        }else{
            if(is_null($expire) || !is_numeric($expire)){
                setCookie($key, $val);
            }else{
                setCookie($key,$val,time()+(int)$expire);
            }
        }
    }
}
//get
function _cookie_get_($key,$dft=NULL){
    if(!isset($_COOKIE[$key]) || empty($_COOKIE[$key])){
        return $dft;
    }else{
        return $_COOKIE[$key];
    }
}
//del
function _cookie_del_($key){
    if(is_array($key)){
        $kv = array();
        for($i=0;$i<count($key);$i++){
            $kv[$key[$i]] = NULL;
        }
        _cookie_set_($kv);
    }else{
        _cookie_set_($key,NULL);
    }
}
//has
function _cookie_has_($key){
    return !isset($_COOKIE[$key]) || empty($_COOKIE[$key]);
}



/*
 *  is
 */
//数字下标的array
function _is_numarr_($arr=[]){
    return is_array($arr) && array_key_exists(0,$arr);
}
//is_json
function _is_json_($str){
    if(!is_string($str)){return FALSE;}
    if(empty($str)){
        return FALSE;
    }else{
        $jd = json_decode($str);
        if(is_null($jd)){
            return FALSE;
        }
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
//is_xml
function _is_xml_($str){
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NOCDATA);
    if($xml===FALSE){
        return FALSE;
    }else{
        return TRUE;
    }
}
//is_url URL参数形式
function _is_url_($str){
    if(!is_string($str) || empty($str)){return FALSE;}
    if(strpos($str,"&")===FALSE){
        if(strpos($str,"=")===FALSE){
            return FALSE;
        }else{
            $sarr = explode("=",$str);
            if(count($sarr)!=2){return FALSE;}
            return TRUE;
        }
    }else{
        $sarr = explode("&",$str);
        $rst = TRUE;
        for($i=0;$i<count($sarr);$i++){
            if($sarr[$i]==""){continue;}
            if(_is_url_($sarr[$i])==FALSE){
                $rst = FALSE;
                break;
            }
        }
        return $rst;
    }
}



/*
 *  to
 */
function _to_array_($param){
    if(empty($param)){
        return array();
    }else if(is_array($param)){
        return $param;
    }else if(is_string($param)){
        if(_is_json_($param)==TRUE){
            return json_decode($param,TRUE);
        }else if(_is_url_($param)==TRUE){
            return _u2a_($param);
        }else if(_is_xml_($param)==TRUE){
            return _x2a_($param);
        }else{
            if(strpos($param,",")!==FALSE){
                return explode(",",$param);
            }else{
                return array($param);
            }
        }
    }else if(is_numeric($param)){
        return _to_array_("[\"".$param."\"]");
    }else if(is_object($param)){
        return (array)$param;
    }else{
        $d = array();
        $d[] = $param;
        return $d;
    }
}
function _to_string_($param){
    if(empty($param)){
        return "";
    }else if(is_array($param)){
        return _to_json_($param);
    }else if(is_string($param)){
        return $param;
    }else if(is_object($param)){
        return _to_json_((array)$param);
    }else{
        return (string)$param;
    }
}
function _to_json_($param){
    $arr = _to_array_($param);
    return json_encode($arr,JSON_UNESCAPED_UNICODE);
}
function _to_url_($param){
    $arr = _to_array_($param);
    if(empty($arr)){
        return "";
    }else{
        if(array_key_exists(0,$arr)){return "";}
        $arrs = array();
        foreach($arr as $k => $v){
            $arrs[] = $k."=".$v;
        }
        return empty($arrs) ? "" : implode("&",$arrs);
    }
}
//to html property like :   [pre-]key="value"
function _to_html_property_($param,$pre=""){
    $arr = _to_array_($param);
    if(!empty($arr)){
        if(array_key_exists(0,$arr)){return "";}
        $rtn = array();
        foreach($arr as $k => $v){
            $rtn[] = $pre.$k.'="'.$v.'"'; 
        }
        return implode(" ",$rtn);
    }else{
        return "";
    }
}
//将数组转化为XML
function _a2x_($arr=array(),$dom=0,$item=0){
    if(!$dom){
        $dom = new DOMDocument("1.0");
    }
    if(!$item){
        $item = $dom->createElement("root"); 
        $dom->appendChild($item);
    }
    foreach ($arr as $key=>$val){
        $itemx = $dom->createElement(is_string($key) ? $key : "item");
        $item->appendChild($itemx);
        if (!is_array($val)){
            $text = $dom->createTextNode($val);
            $itemx->appendChild($text);
        }else {
            _a2x_($val,$dom,$itemx);
        }
    }
    return $dom->saveXML();
}
//将XML转化为数组
function _xta_($xml){
    return (array) simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
}
function _x2a_($xmlString=""){
    $targetArray = array();
    $xmlObject = simplexml_load_string($xmlString);
    $mixArray = (array)$xmlObject;
    foreach($mixArray as $key => $value){
        if(is_string($value)){
            $targetArray[$key] = $value;
        }else if(is_object($value) && !empty($value)){
            $targetArray[$key] = _x2a_($value->asXML());
        }else if(is_array($value) && !empty($value)){
            foreach($value as $zkey => $zvalue){
                if(is_numeric($zkey)){
                    $targetArray[$key][] = _x2a_($zvalue->asXML());
                }
                if(is_string($zkey)){
                    $targetArray[$key][$zkey] = _x2a_($zvalue->asXML());
                }
            }
        }else{
            if(empty($value)){
                $targetArray[$key] = "";
            }
        }
    }
    return $targetArray;
}
//将数组转化为JSON
function _a2j_($arr=array()){
    return json_encode($arr,JSON_UNESCAPED_UNICODE);
}
//将JSON转换为数组
function _j2a_($json=""){
    return json_decode($json,TRUE);
}
//array to url
function _a2u_($arr=array()){
    if(empty($arr)){
        return "";
    }else{
        $arrs = array();
        foreach($arr as $k => $v){
            $arrs[] = $k."=".$v;
        }
        return empty($arrs) ? "" : implode("&",$arrs);
    }
}
//url to array
function _u2a_($str=""){
    if(!is_string($str) || $str=="" || _is_url_($str)!=TRUE){return array();}
    $rst = array();
    if(strpos($str,"&")===FALSE){
        $sarr = explode("=",$str);
        $rst[$sarr[0]] = $sarr[1];
    }else{
        $sarr = explode("&",$str);
        for($i=0;$i<count($sarr);$i++){
            $rst = _array_add_($rst,_u2a_($sarr[$i]));
        }
    }
    return $rst;
}



/*
 *  array
 */
//按数组$new来修改数组$old，遇到__delete__标记则删除原数组内容，支持多维数组
function _array_overlay_($old=array(),$new=array()){	//v0.2
    if(empty($old) || !is_array($old)){return (empty($new) || !is_array($new)) ? array() : $new;}
    if(empty($new) || !is_array($new)){return (empty($old) || !is_array($old)) ? array() : $old;}
    foreach($old as $k => $v){
        if(!array_key_exists($k,$new)){continue;}
        if($new[$k]==="__delete__"){
            unset($old[$k]);
            continue;
        }
        if(is_array($v) && is_array($new[$k])){
            $old[$k] = _array_overlay_($v,$new[$k]);
        }else{
            $old[$k] = $new[$k];
        }
    }
    foreach($new as $k => $v){
        if(array_key_exists($k,$old)){continue;}
        if($v==="__delete__"){continue;}
        $old[$k] = $v;
    }
    return $old;
}
function _array_overlay_v2_($old=array(),$new=array()){	//v1.0
    if(empty($old) || !is_array($old)){return (empty($new) || !is_array($new)) ? array() : $new;}
    if(empty($new) || !is_array($new)){return (empty($old) || !is_array($old)) ? array() : $old;}
    foreach($old as $k => $v){
        if(!array_key_exists($k,$new)){continue;}
        if($new[$k]==="__delete__"){
            unset($old[$k]);
            continue;
        }
        if(is_array($v) && is_array($new[$k])){
            if(array_key_exists(0,$v) && array_key_exists(0,$new[$k])){		//新旧值均为数字下标数组
                //合并数组，并去重
                $old[$k] = _array_uni_(array_merge($v,$new[$k]));
            }else{
                $old[$k] = _array_overlay_v2_($v,$new[$k]);
            }
        }else{
            $old[$k] = $new[$k];
        }
    }
    foreach($new as $k => $v){
        if(array_key_exists($k,$old)){continue;}
        if($v==="__delete__"){continue;}
        $old[$k] = $v;
    }
    return $old;
}
function _array_extend_($old=array(),$new=array()){
    return _array_overlay_v2_($old,$new);
}
//按数组$new来修改数组$old，遇到__delete__标记则删除原数组内容，一维数组
function _array_add_($old=array(),$new=array()){
    //if(empty($old) || !is_array($old)){return array();}
    if(empty($new) || !is_array($new)){return (empty($old) || !is_array($old)) ? array() : $old;}
    foreach($new as $k => $v){
        if(array_key_exists($k,$old)){continue;}
        if($v==="__delete__"){continue;}
        $old[$k] = $v;
    }
    return $old;
}
//数组去重，适用于数字键名
function _array_uni_($arr=array()){
    return array_merge(array_flip(array_flip($arr)),array());
}
//多维数组search，搜索指定键名的键值，找到则返回第一个第一维的键名
function _array_mulsearch_($arr=array(), $key=NULL, $s=NULL, $like=FALSE){
    if(empty($arr) || is_null($key)){return NULL;}
    $rtn = NULL;
    foreach($arr as $k => $v){
        if(!is_array($v) || !isset($v[$key])){
            continue;
        }else{
            if(
                ($like==FALSE && $v[$key]==$s) || 
                ($like==TRUE && is_string($v[$key]) && $v[$key]!="" && is_string($s) && $s!="" && FALSE!==strpos($v[$key],$s))
            ){
                $rtn = $k;
                break;
            }
        }
    }
    return is_numeric($rtn) ? (int)$rtn : $rtn;
}
//多维数组search，搜索指定键名的键值，找到则返回所有第一维的键名
function _array_mulsearch_all_($arr=array(), $key=NULL, $s=NULL, $like=FALSE){
    if(empty($arr) || is_null($key)){return [];}
    $rtn = [];
    foreach($arr as $k => $v){
        if(!is_array($v) || !isset($v[$key])){
            continue;
        }else{
            //if($v[$key]==$s){
            if(
                ($like==FALSE && $v[$key]==$s) || 
                ($like==TRUE && is_string($v[$key]) && $v[$key]!="" && is_string($s) && $s!="" && FALSE!==strpos($v[$key],$s))
            ){
                $rtn[] = is_numeric($k) ? (int)$k : $k;
                //break;
            }
        }
    }
    return $rtn;
}
//使用xpath方式获取array内容
function _array_xpath_($arr=array(), $xpath="", $val=NULL){
    if(!is_array($arr)){return NULL;}
    if(!is_string($xpath) || $xpath==""){return $arr;}
    if(strpos($xpath,"/")===FALSE){
        if(is_null($val)){
            return array_key_exists($xpath,$arr) ? $arr[$xpath] : NULL;
        }else{
            $arr[$xpath] = $val;
            return $arr;
        }
    }else{
        $xarr = explode("/",$xpath);
        $ta = $arr;
        if(is_null($val)){
            for($i=0;$i<count($xarr);$i++){
                //if(is_null($ta)) {var_dump($xarr);}
                if(array_key_exists($xarr[$i],$ta)){
                    $ta = $ta[$xarr[$i]];
                }else{
                    $ta = NULL;
                    break;
                }
            }
            return $ta;
        }else{
            $l = count($xarr);
            $x = array();
            $x[$xarr[$l-1]] = $val;
            for($i=$l-2;$i>=0;$i--){
                $xx = $x;
                $x = array();
                $x[$xarr[$i]] = $xx;
            }
            //var_dump($x);die();
            $arr = _array_extend_($arr,$x);
            return $arr;
        }
    }
}
//将数组中的xpath都转换为值
function _array_xpathes_($arr=[],$xpathes=[]){
    if(!is_array($arr) || !is_array($xpathes) || empty($arr) || empty($xpathes)) return $xpathes;
    $rst = [];
    foreach($xpathes as $k => $v){
        $val = _array_xpath_($arr,$v);
        if(is_null($val)){
            $rst[$k] = $v;
        }else{
            $rst[$k] = $val;
        }
    }
    return $rst;
}
//数据库记录集类型的数组中某键字段值求和
function _array_sum_(){
    $args = func_get_args();
    if(count($args)<2){return 0;}
    $arr = array_shift($args);
    if(!is_array($arr) || empty($arr)){$arr = array();}
    $rst = array();
    for($i=0;$i<count($args);$i++){
        $sum = 0;
        for($j=0;$j<count($arr);$j++){
            if(!isset($arr[$j][$args[$i]])){continue;}
            $sum += $arr[$j][$args[$i]];
        }
        $rst[$args[$i]] = $sum;
    }
    return $rst;
}



/*
 *  number
 */
//处理小数点位数
function _digit_($digit=NULL){
    if($digit==NULL || !is_numeric($digit) || $digit<=0){
        $digit = NUM_DIGIT;
    }else{
        $digit = (int)$digit;
    }
    return $digit;
}
//保留小数点位数
function _num_fix_($n=0, $digit=NULL){
    $digit = _digit_($digit);
    return round($n,$digit);
}
//保留小数点位数，并补齐小数位数
function _num_fix_format_($n=0, $digit=NULL){
    $n = _num_fix_($n, $digit);
    return _num_format_($n, $digit);
}
//浮点数比较，$a>$b => 1    $a<$b => -1   $a=$b => 0
function _num_comp_($a=0, $b=0, $digit=NULL){
    $digit = _digit_($digit);
    return bccomp($a,$b,$digit);
}
//  $a>$b
function _num_big_($a,$b,$digit=NULL){
    $digit = _digit_($digit);
    $cp = _num_comp_($a,$b,$digit);
    return $cp>=1;
}
//  $a<$b
function _num_small_($a,$b,$digit=NULL){
    $digit = _digit_($digit);
    $cp = _num_comp_($a,$b,$digit);
    return $cp<=-1;
}
//  $a=$b
function _num_eq_($a,$b,$digit=NULL){
    $digit = _digit_($digit);
    $cp = _num_comp_($a,$b,$digit);
    return $cp==0;
}
//补齐小数位数，输出string
function _num_format_($n=0, $digit=NULL, $point=".", $sep=""){
    $digit = _digit_($digit);
    $n = (float)$n;
    return number_format($n,$digit,$point,$sep);
}
//在数字左侧补齐位数
function _num_pad_($n, $num, $str="0", $type="left"){
    $s = "";
    switch($type){
        case "left" :
            $s = str_pad($n,$num,$str,STR_PAD_LEFT);
            break;
        case "right" :
            $s = str_pad($n,$num,$str,STR_PAD_RIGHT);
            break;
        case "both" :
            $s = str_pad($n,$num,$str,STR_PAD_BOTH);
            break;
    }
    return $s;
}
//输出金额数字
function _num_price_($n=0, $s="￥", $digit=NULL){
    $ns = _num_format_($n, $digit);
    return $s." ".$ns;
}



/*
 *  string
 */
//左侧补零
function _left_pad_($str, $dig=3, $sign="0"){
    $str = (string)$str;
    if(strlen($str)>=$dig){return $str;}
    $signs = array_fill(0,$dig,$sign);
    $signs = implode("",$signs);
    $str = $signs.$str;
    return substr($str,$dig*-1);
}
//将字符串首或尾的给定字符删除
function _str_del_($str="", $delstr="/", $pos="left"){
    if(strpos($str,$delstr)!==FALSE){
        $strs = explode($delstr,$str);
        switch($pos){
            case "left" :
                if(empty($strs[0])){
                    array_shift($strs);
                }
                break;
            case "right" :
                if(empty($strs[count($strs)-1])){
                    array_pop($strs);
                }
                break;
        }
        return implode($delstr,$strs);
    }
    return $str;
}
//array 替换str中的%{1}%,%{2}%...
function _str_tpl_num_($str="", $arr=array()){
    if($str=="" || strpos($str,"%")===FALSE || strpos($str,"{")===FALSE || strpos($str,"}")===FALSE){return $str;}
    if(!is_array($arr) || empty($arr) || !array_key_exists(0,$arr)){return $str;}
    for($i=0;$i<count($arr);$i++){
        $str = str_replace("%{".($i+1)."}%",$arr[$i],$str);
    }
    return $str;
}
//替换%{XXX}%
function _str_tpl_($str, $val=array()){
    if(!empty($val)){
        $val = _to_array_($val);
        $reg = "/\%\{[^\}\%]+\}\%/";
        preg_match_all($reg, $str, $matches);
        $ms = $matches[0];
        if(!empty($ms)){
            for($i=0;$i<count($ms);$i++){
                $cn = $ms[$i];
                $cns = str_replace("%{","",$cn);
                $cns = str_replace("}%","",$cns);
                $tval = _array_xpath_($val,$cns);
                if(!is_null($tval)){
                    $str = str_replace($ms[$i],$tval,$str);
                }else{
                    $str = str_replace($ms[$i],"null",$str);
                }
            }
        }
    }
    return $str;
}
//生成nonce随机字符串
function _str_nonce_($length=16){
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i=0;$i<$length;$i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}
//检查字符串中是否包含数组中的任意字符
function _str_has_($str="",$k=[],$allhas=FALSE){
    if(!is_string($str) || $str=="") return FALSE;
    if(!is_array($k)) $k = _to_array_($k);
    if(!is_array($k) || empty($k)) return FALSE;
    $flag = $allhas===TRUE ? TRUE : FALSE;
    foreach($k as $i => $v){
        if($allhas===TRUE){
            if(strpos($str,$v)===FALSE ){
                $flag = FALSE;
                break;
            }
        }else{
            if(strpos($str,$v)!==FALSE ){
                $flag = TRUE;
                break;
            }
        }
    }
    return $flag;
}
//批量replace
function _str_replace_($str,$reps=[]){
    for($i=0;$i<count($reps);$i++){
        $str = str_replace($reps[$i][0],$reps[$i][1],$str);
    }
    return $str;
}
function _str_pregreplace_($str,$reps=[]){
    for($i=0;$i<count($reps);$i++){
        $str = preg_replace($reps[$i][0],$reps[$i][1],$str);
    }
    return $str;
}



/*
 *  DB,RS,SQL
 */
//加载json数据库
function _jdb_($dbn=""){return \dp\Jdb::load($dbn);}
//记录集搜索，用于模拟数据库select，返回符合条件的记录集，记录条目在原记录集中idx保存到每条记录的字段_rsidx_
/*function _rs_select_($rs=[], $where=NULL, $order=NULL, $limit=NULL){
    if(!is_array($rs) || empty($rs) || !array_key_exists(0,$rs)) return [];
    //$rss = [];
    $rsos = [];
    $order = _sql2a_order_($order);
    $limit = _sql2a_limit_($limit);
    for($i=0;$i<count($rs);$i++){
        $rsi = $rs[$i];
        if(TRUE===_rsi_where_($rsi,$where)){
            //$rss[] = $i;
            $rsi["_rsidx_"] = $i;
            $rsos[] = $rsi;
        }
    }
    if(empty($rsos)) return [];
    if(!empty($order)){
        for($i=0;$i<count($order);$i++){
            array_multisort(array_column($rsos,$order[$i][0]),constant("SORT_".$order[$i][1]),$rsos);
        }
    }
    if(!empty($limit)){
        //var_dump($limit);die();
        $rsos = array_splice($rsos,$limit[0],$limit[1]);
    }
    return $rsos;
}
//检查某一条记录是否符合where条件
function _rsi_where_($rsi=[], $where=NULL){
    if(!is_array($rsi) || empty($rsi)) return FALSE;
    if(!is_string($where) || $where=="") return TRUE;
    $wstr = $where;
    //查找条件语句   WHERE {field logic val}
    preg_match_all("/\{\{[^\}\}]+\}\}/", $wstr, $matches);
    $ms = $matches[0];
    if(!empty($ms)){
        for($i=0;$i<count($ms);$i++){
            $cn = $ms[$i];
            $cns = str_replace("{{","",$cn);
            $cns = str_replace("}}","",$cns);
            $carr = explode(" ",$cns);
            $carr[0] = trim(str_replace("`","",$carr[0]));
            $carr[1] = strtoupper(trim($carr[1]));
            $carr[2] = trim(str_replace("'","",$carr[2]));
            if($carr[1]=="LIKE"){
                $carr[2] = str_replace("%","",$carr[2]);
                $rst = strpos($rsi[$carr[0]],$carr[2])!==FALSE;
            }else if($carr[1]=="BETWEEN"){
                $carr2_arr = explode(",",$carr[2]);
                $rst = $rsi[$carr[0]]>=$carr2_arr[0] && $rsi[$carr[0]]<=$carr2_arr[1];
            }else{
                $carr[1] = $carr[1]=="=" ? "==" : $carr[1];
                $evalstr = "\$rst = \$rsi[\"".$carr[0]."\"] ".$carr[1]." ".(is_numeric($carr[2]) || in_array(strtolower($carr[2]),["true","false"]) ? $carr[2] : "\"".$carr[2]."\"").";";
                eval($evalstr);
            }
            $wstr = str_replace($ms[$i],($rst==TRUE ? 'TRUE' : 'FALSE'),$wstr);
            $wstr = str_replace("AND","&&",$wstr);
            $wstr = str_replace("OR","||",$wstr);
        }
        //var_dump($wstr);die();
        eval("\$frst = ".$wstr.";");
        return $frst;
    }
    return TRUE;
}
//sql语句解析为array
function _sql2a_order_($order=NULL){
    if(!is_string($order) || $order=="") return [];
    $oarr = explode(",",$order);
    $rst = [];
    for($i=0;$i<count($oarr);$i++){
        $oai = trim($oarr[$i]);
        $oai = str_replace("`","",$oai);
        $oaiarr = explode(" ",$oai);
        $oaiarr[0] = trim($oaiarr[0]);
        $oaiarr[1] = strtoupper(trim($oaiarr[1]));
        $rst[] = $oaiarr;
    }
    return $rst;
}
function _sql2a_limit_($limit=NULL){
    if((!is_string($limit) && !is_numeric($limit)) || $limit=="") return [];
    if(!is_string($limit)) $limit = "".$limit;
    if(strpos($limit,",")===FALSE) return is_numeric($limit) ? [0,(int)$limit] : [];
    $larr = explode(",",$limit);
    $larr[0] = trim($larr[0]);
    $larr[1] = trim($larr[1]);
    if(!is_numeric($larr[0]) || !is_numeric($larr[1])) return [];
    return [(int)$larr[0], (int)$larr[1]];
}*/





function _iosapp_($uri=[]){
    return "hellow ios webview app!";
}

//fan qiang
function _see_($uri=[]){
    $dm = [
        "baidu" => "https://www.baidu.com",
        "zhidao" => "https://zhidao.baidu.com",
        "quora" => "https://www.quora.com"
    ];
    $uris = $uri["uri"];
    array_shift($uris);     //see
    $dmn = strtolower(array_shift($uris));
    if(!isset($dm[$dmn])) return _export_404_();
    $host = $dm[$dmn];
    $u = implode("/",$uris);
    $url = $host.($u=="" ? "" : "/".$u);
    $url = $url.($uri["querystring"]=="" ? "" : "?".$uri["querystring"]);
    $h = file_get_contents($url);
    $h = str_replace($host,_url_("see/".$dmn),$h);
    $h = str_replace("href\=\"\/","href\=\""._url_("see/".$dmn)."/",$h);
    $h = str_replace("src\=\"https://","src\=\""._url_("res/https")."/",$h);
    //var_dump(_url_("see/".$dmn));
    _export_($h);
}

