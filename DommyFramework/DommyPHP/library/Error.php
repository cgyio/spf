<?php
/*
 *  DommyFramework 错误处理
 * 
 */

namespace dp;
use dp\App as App;

class Error {
	//错误代码预设
	public static $codes = [];
    //存在的错误
    public static $errs = [];

    public function __construct($el,$ec="dp/sys",$f="",$l=0,$em=array()){
		$eco = self::get($ec);
		if(is_null($eco)){
			trigger_error("dp/error/undefined::".$ec,E_USER_ERROR);
		}else{
			$fl = @explode("htdocs",_res_fixpath_($f))[1];
            //$fl = $f;
			$this->level = $el;
			$this->title = $eco[0];
			$this->errmsg = self::parse($eco[1],$em).(DF_DEBUG == TRUE ? " in ".$fl." at line ".$l : "");
			$this->errpath = $ec;
			$this->errcode = self::get_code($ec);
			$this->data = $em;
			$tplf = _tpl_path_("error/".$ec.".tpl");
			if(file_exists($tplf)){
				$this->tpl = file_get_contents($tplf);
			}else{
				$this->tpl = file_get_contents(_tpl_path_("error/default.tpl"));
			}
		}
	}

	public function set_idx($idx=0){
		$this->idx = $idx;
	}

	public function throwerr(){
		$format = _format_();
		$data = (array)$this;
		switch($format){
			case "xml" :
			case "json" :
			case "dump" :
				_export_($data);
				break;
			case "html" :
			case "page" :
				_export_($data,"page");
				break;
			case "str" :
				_export_("Error ".$data["errcode"]." : ".$data["title"]." ( ".$data["errmsg"]." )");
				break;
		}
		die();
	}

    //创建错误，保存到Error::$errs
    public static function create($el,$ec="dp/sys",$f="",$l=0,$em=array()){
		$err = new Error($el,$ec,$f,$l,$em);
		$err->set_idx(count(self::$errs));
		self::$errs[] = $err;
		//如果生成的error为致命错误，必须抛出
		if(self::must_throw($el)){
			$err->throwerr();
		}
    }

    //生成错误代码数组，或添加新的错误代码
	public static function set($ecs=array()){
		if(empty(self::$codes)){
            //读取错误代码预设json，在 lib/error 路径下
			$dir = _lib_path_("error");
			//var_dump($dir);die();
            if(is_dir($dir)){
                $dh = opendir($dir);
                while(($f=readdir($dh)) !== FALSE){
                    if(!is_dir($dir.DS.$f) && strpos(strtolower($f),'.json')!==FALSE){
                        $fn = str_replace(".json","",strtolower($f));
						$earr = _j2a_(file_get_contents($dir.DS.$f));
						$lang = _lang_();
                        if(isset($earr[$lang])){
                            self::$codes[$fn] = $earr[$lang];
                        }else{
							if(isset($earr[EXPORT_LANG])){
								self::$codes[$fn] = $earr[EXPORT_LANG];
							}else{
								self::$codes[$fn] = $earr;
							}
                        }
                    }
				}
				closedir($dh);
            }
		}
        $ecs = _to_array_($ecs);
        if(!empty($ecs)){
            self::$codes = _array_extend_(self::$codes, $ecs);
        }
	}

	//读取App错误代码预设
	public static function set_apperror($app=""){
		$app = strtolower($app);
		$appo = _app_($app);
		if(!isset(self::$codes["app"])) self::$codes["app"] = [];
		$ecf = $appo->path("errcode.json");
		if(!file_exists($ecf)) $ecf = $appo->path("library/errcode.json");
		if(!file_exists($ecf)){
			return FALSE;
		}else{
			$codes = [];
			$earr = _j2a_(file_get_contents($ecf));
			$lang = _lang_();
			if(isset($earr[$lang])){
				$codes[$app] = $earr[$lang];
			}else{
				if(isset($earr[EXPORT_LANG])){
					$codes[$app] = $earr[EXPORT_LANG];
				}else{
					$codes[$app] = $earr;
				}
			}
			self::$codes["app"] = _array_extend_(self::$codes["app"],$codes);
			return TRUE;
		}
	}

	//查找错误信息
	public static function get($ec="dp/sys"){
        $err = _array_xpath_(self::$codes, $ec);
        return _is_numarr_($err) ? $err : NULL;
	}

	//获取错误代码
	public static function get_code($ec="dp/sys"){
		$earr = explode("/",$ec);
		$eo = self::$codes;
		$code = "";
		for($i=0;$i<count($earr);$i++){
			if(!isset($eo[$earr[$i]]) || !is_array($eo[$earr[$i]])) break;
			$eo = $eo[$earr[$i]];
			if(isset($eo["idx"])){
				$code .= $eo["idx"];
			}else if(_is_numarr_($eo)){
				if(array_key_exists(2,$eo)){
					$code .= $eo[2];
				}
			}else{
				$code .= "0";
			}
		}
		if($code=="" || !is_numeric($code)){
			return "0";
		}else{
			return $code;
		}
	}

	//替换输出错误信息
	public static function parse($info,$keys=array()){
		if(!is_string($info) || !is_array($keys)){return "";}
		for($i=0;$i<count($keys);$i++){
			$info = str_replace("%{".($i+1)."}%",$keys[$i],$info);
		}
		return $info;
	}

	//根据错误等级判断是否需要立即输出，并终止程序
	public static function must_throw($el){
		return $el==1 || $el==2 || $el==256 || $el==512;
	}

	//全局错误处理
	public static function handler($el,$em,$f,$l){
		if($el>1024 || $el<256){	//系统错误
			$ec = "dp/sys";
			$em = array($em);
			$ls = $el==1 ? "Error" : ($el==2 ? "Warning" : ($el==8 ? "Notice" : "Error"));
			array_unshift($em,$ls);
		}else{	//自定义错误
			//if(strpos($em,"::")===FALSE){
			if(!is_string($em) || $em==""){
				$ec = "unknown";
				$em = array("");
			}else{
				$ems = explode("::",$em);
				$ec = $ems[0];
				$em = _to_array_($ems[1]);
			}
		}
		self::create($el, $ec, $f, $l, $em);
	}

}

