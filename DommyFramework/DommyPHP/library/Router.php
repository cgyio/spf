<?php
/*
 *  DommyFramework 路由
 * 
 */

namespace dp;
//use dp\Usr as Usr;
use dp\App as App;

/**
 * Router 路由类
 * 
 * 路由规则形式：多维数组
 * Router::$rules = array(
 *     "_default_" => array({class},"method"),    //http://host
 *     "rule_a" => array({class},"method"),    //http://host/rule_a
 *     "rule_b" => array(
 *         "_default_" => array({class},"method"),    //http://host/rule_b
 *         "rule_b_a" => array({class},"method"),    //http://host/rule_b/rule_b_a
 *         "rule_b_b" => array(
 *             "_default_" => array({class},"method"),    //http://host/rule_b/rule_b_b
 *             "rule_b_b_a" => array({class},"method"),    //http://host/rule_b/rule_b_b/rule_b_b_a
 *             "rule_b_b_b" => array({class},"method")     //http://host/rule_b/rule_b_b/rule_b_b_b
 *         )
 *     )
 * )
 */

class Router {
    //路由规则
    public static $rules = array(
        "_default_" => array("\\dp\\Router","rule_default"),
        "api" => array("\\dp\\Router","rule_api")
        //"demo" => array("Router","rule_demo")
    );

    public function __construct(){ }

    /**
     * 添加路由规则
     * @param mixed $rule 规则名字符串，或则包含多个路由规则的数组
     * @param array $funcarr 包含类和类方法名的一维数组，长度2  array({class},"method")，指定此规则的处理方法
     */
    public static function add_rule($rule="", $funcarr=array()){
        if(is_array($rule) && !empty($rule)){
            if(!array_key_exists(0,$rule)){
                self::$rules = _array_overlay_(self::$rules,$rule);
            }
        }else if(is_string($rule) && !empty($rule) && is_array($funcarr) && !empty($funcarr)){
            if(array_key_exists(0,$funcarr)){
                self::$rules[$rule] = $funcarr;
            }else{
                if(isset(self::$rules[$rule])){
                    self::$rules[$rule] = _array_overlay_(self::$rules[$rule],$funcarr);
                }else{
                    self::$rules[$rule] = $funcarr;
                }
            }
        }
	}
	
	//设置默认路由规则
	public static function set_default_rule($rule=array()){
		self::add_rule("_default_",$rule);
	}

	//根据xpath获取对应的rule，未指定则返回NULL
	public static function get_rule($xpath=""){
		if($xpath==""){
			$xarr = array();
		}else{
			$xarr = explode("/",$xpath);
		}
        $rule_xpath = "";
        if(count($xarr)<=0){
            $rule_xpath = "_default_";
        }else{
            for($i=count($xarr)-1;$i>=0;$i--){
                $xs = implode("/",$xarr);
                $tr = _array_xpath_(self::$rules,$xs."/_default_");
                if(is_null($tr)){
                    $tr = _array_xpath_(self::$rules,$xs);
                    if(is_null($tr)){
                        array_pop($xarr);
                        continue;
                    }else{
                        $rule_xpath = $xs;
                        break;
                    }
                }else{
                    $rule_xpath = $xs."/_default_";
                    break;
                }
            }
		}
		//var_dump($rule_xpath);die();
        if($rule_xpath==""){
            return NULL;
        }else{
			/** 权限判定 **/
			if(self::check_rule_auth($rule_xpath)!==TRUE){
				trigger_error("dp/router/noauth::".$rule_xpath, E_USER_ERROR);
			}


            return _array_xpath_(self::$rules,$rule_xpath);
		}
	}

    //执行路由功能，当指定url时，则访问指定的url，post为提交到php://input的数据
    public static function run($url=NULL,$post=[]){
        //阿里云https验证
        self::aliyun_sslcheck();
        //子域名 301重定向，xxx.host/ -> host/xxx
        self::subdomain_redirect();

        $uri = _uri_($url);
        //模拟提交数据到 php://input
        if(is_array($post) && !empty($post)){
            _session_set_("_php_input_",_a2j_($post));
        }
		$rule = self::get_rule(implode("/",$uri["uri"]));
		//var_dump($rule);die();
        if(is_null($rule)){		//如果没有显式指定路由规则
            $rm = $uri["uri"][0];
            if(method_exists("\\dp\\Router","rule_".$rm)){	//首先在Router类中查找rule_xxx方法
                $data = call_user_func_array(array("\\dp\\Router","rule_".$rm),array($uri));
                _export_($data);
            }else if(function_exists("_router_".$rm."_")){	//然后在全局查找_router_xxx_函数
				$data = call_user_func_array("_router_".$rm."_",array($uri));
				_export_($data);
			}else{
                //未指定路由规则
                //trigger_error("dp/router/undefined::".implode("/",$uri["uri"]),E_USER_ERROR);
                //调用默认路由规则
                $data = call_user_func_array(["\\dp\\Router","rule_default"],[$uri]);
				_export_($data);
            }
        }else{
            //调用路由规则指定的处理方法
            $data = call_user_func_array($rule,array($uri));
            _export_($data);
		}
	}
	
	//路由权限检查
	public static function check_rule_auth($rule_xpath=""){
        //return _uac_($rule_xpath);

		return TRUE;
    }

    //子域名转向，xxx.host/ -> host/xxx
    public static function subdomain_redirect(){
        $host = $_SERVER["HTTP_HOST"];
        $h = str_replace(DOMAIN,"",$host);
        if($h!=""){
            $harr = explode(".",$h);
            array_pop($harr);
            $nuri = implode("/",$harr);
            $uri = _uri_();
            $u = $nuri.$uri["uristring"];
            $u = _protocol_()."://".DOMAIN."/".$u;
            header("location: ".$u);
        }
    }

    //通用路由规则
    public static function common($obj,$param=[]){
        if(!is_object($obj)) _export_500_();
        if(!is_array($param) || empty($param)) return self::emptyrule($obj);
        $m = "router_".$param[0];
        if(method_exists($obj,$m)){
            array_shift($param);
            return call_user_func_array([$obj,$m],$param);
        }else{
            return self::emptyrule($obj,$param);
        }

    }
    //通用空规则
    public static function emptyrule($obj,$param=[]){
        if(!is_object($obj)) _export_500_();
        if(method_exists($obj,"router_empty")){
            return call_user_func_array([$obj,"router_empty"],$param);
        }else{
            _export_404_();
        }
    }

    //获取当前路由所属的Appname，不是App的返回NULL
    public static function appname(){
        $uris = _uri_()["uri"];
        if(empty($uris)) return NULL;
        if($uris[0]=="app") array_shift($uris);
        if(empty($uris)) return NULL;
        $app = array_shift($uris);
        return App::has($app) ? $app : NULL;
    }
    


    /*
     *  预设规则
     */
    //空规则处理方法，默认
    public static function rule_default($uri=array()){
        //_export_("Hellow DommyFramework");
        //exit;
        return self::$rules;
    }

    //api functions 路由方法
    public static function rule_api($uri=array()){
        if($uri["lvl"]<=1){
            trigger_error("dp/router/needparam",E_USER_ERROR);
        }else{
            $uris = $uri["uri"];
            array_shift($uris);
            $api = array_shift($uris);
            if(function_exists("_".$api."_")){
                //$data = call_user_func_array("_".$api."_",$uris);
                //_export_($data);
                return call_user_func_array("_".$api."_",$uris);
            }else{
                trigger_error("dp/router/undefined::".$api,E_USER_ERROR);
            }
        }
    }

    //demo 路由方法
    public static function rule_demo($uri=array()){
        $p = _dp_path_("page/demo");
        $uris = $uri["uri"];
        array_shift($uris);
        if($uri["lvl"]<=1){
            require($p.".php");
            exit;
        }else{
            $f = $p."/".implode("/",$uris);
            if(file_exists($f)){
                echo file_get_contents($f);
                exit;
            }else{
				_export_404_();
				
            }
        }
	}
	
	//phpinfo
	public static function rule_phpinfo(){
		phpinfo();
		exit;
	}

	//md5
	public static function rule_md5($uri=array()){
		$str = isset($uri["uri"][1]) ? $uri["uri"][1] : '';
		return md5($str);
	}

	//date to num
	public static function rule_timestamp($uri=array()){
		$str = isset($uri["uri"][1]) ? $uri["uri"][1] : date("Y-m-d H:i:s",time());
		return strtotime($str);
	}

	//404模拟
	public static function rule_responsecode($uri=array()){
		$code = isset($uri["uri"][1]) ? (int)$uri["uri"][1] : 404;
		http_response_code($code);
    }



    /*
     *  特殊方法
     */
    //阿里云https开通验证，要求可以访问 host/.well-known/pki-validation/fileauth.txt
    public static function aliyun_sslcheck(){
        $uri = _uri_();
        if(in_array(".well-known",$uri["uri"])){
            $f = _root_(".well-known/pki-validation/fileauth.txt");
            $txt = file_get_contents($f);
            _export_($txt,"str");
            die();
        }
    }
}