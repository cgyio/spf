<?php
/*
 *  DommyFramework template 模板引擎
 * 
 */

namespace dp;
use dp\App as App;

class Template {

    //已加载的tpl实例
    public static $_LIST_ = [];

    //模板文件后缀
    public static $ext_tpl = ".tpl";
    public static $ext_php = ".php";

    //自定义标签正则
    /*
     *  %{aaa/bbb/ccc}%
     *  %{if::condition,truetpl,falsetpl}%
     *  %{if::condition,truetpl,falsetpl}%
     *  %{for::aaa/bbb,tpl}%
     *  %{func::param1,param2,...}%
     * 
     */
    protected static $_reg = "/\%\{[^\}\%]+\}\%/";

    //自定义标签预留关键字
    protected static $_keywords = ["for","foreach","if"];

    //是否为php格式的文件，是则通过require执行，并输出
    public $isphp = FALSE;

    //template原始字符串
    public $original = "";
    //模板文件
    public $tplf = "";
    //数据
    public $data = [];
    //解析后的html
    public $html = "";

    public function __construct($tplf=""){
        if(!file_exists($tplf)){
            $this->_setoriginal($tplf);
        }else{
            $this->tplf = $tplf;
            if(strpos($tplf,self::$ext_php)!==FALSE){
                $this->isphp = TRUE;
            }else{
                $this->original = file_get_contents($tplf);
            }
        }
    }

    //设置tpl内容，用于直接使用字符串创建tpl实例
    protected function _setoriginal($str=""){
        $this->original = $str;
    }

    //获取data数据
    public function d($key="",$val=NULL){
        return _array_xpath_($this->data, $key, $val);
    }

    //设置要输出的数据
    public function assign($data=[]){
        $this->data = $data;
        return $this;
    }

    //解析模板，将解析结果保存到$this->html
    public function parse($data=[]){
        if(!empty($data)) $this->assign($data);
        if($this->isphp==TRUE){
            require($this->tplf);
            //var_dump($html);
        }else{
            $html = $this->original;
            if($html==""){
                $this->html = "";
                return $this;
            };
            preg_match_all(self::$_reg,$html,$matches);
            $ms = $matches[0];
            if(!empty($ms)){
                for($i=0;$i<count($ms);$i++){
                    $tag = $ms[$i];
                    $tag = str_replace("%{","",$tag);
                    $tag = str_replace("}%","",$tag);
                    $tag = trim($tag,"::");
                    if(strpos($tag,"::")!==FALSE){
                        $tarr = explode("::",$tag);
                        $tm = strtolower(array_shift($tarr));
                        $params = implode("::",$tarr);
                        if(strpos($params,",")!==FALSE){
                            $params = explode(",",$params);
                        }else{
                            $params = [$params];
                        }
                        if(method_exists("\\dp\\Template","_parse_".$tm)){
                            array_splice($params,0,0,[$this->data]);
                            $td = call_user_func_array(["\\dp\\Template","_parse_".$tm],$params);
                        }else if(function_exists($tm)){
                            $params = _array_xpathes_($this->data,$params);
                            //var_dump($params);
                            $td = call_user_func_array($tm,$params);
                        }else{
                            $td = NULL;
                        }
                    }else{
                        $td = _array_xpath_($this->data,$tag);
                    }
                    if(!is_null($td)){
                        $html = str_replace($ms[$i],$td,$html);
                    }else{
                        $html = str_replace($ms[$i],"",$html);
                    }
                }
            }
            $this->html = $html;
        }
        return $this;
    }

    //输出
    public function export($format=NULL){
        $format = _format_($format);

        return $this->html;
    }

    //直接输出
    public function rtn(){return $this->html;}



    /*
     *  解析方法 _parse_xxxx
     */
    //%{tpl::tplc,dataxpath}%
    public static function _parse_tpl($data=[],$tplc="",$dataxpath=NULL){
        if(!is_string($dataxpath) || $dataxpath==""){
            $tpldata = $data;
        }else{
            $tpldata = _array_xpath_($data,$dataxpath);
            if(is_null($tpldata)) $tpldata = [];
        }
        return self::load($tplc)->assign($tpldata)->parse()->rtn();
    }
    //%{if::condition,truestr,falsestr}%
    public static function _parse_if($data=[],$condition,$truetpl,$falsetpl){
        $condition = self::_parse_condition($condition,$data);
        $tpls = $condition==TRUE ? $truetpl : $falsetpl;
        $tplf = self::tplf($tpls);
        if($tplf===FALSE){
            $tpl = self::load_str($tpls);
        }else{
            $tpl = self::load($tpls);
        }
        return $tpl->assign($data)->parse()->rtn();
    }

    //%{js::src1,src2,...}%
    public static function _parse_js($data=[]){
        $args = func_get_args();
        $data = array_shift($args);
        $h = "";
        for($i=0;$i<count($args);$i++){
            $h .= "<script src=\"".$args[$i]."\"></script>\r\n";
        }
        return $h;
    }
    //%{css::src1,src2,...}%
    public static function _parse_css($data=[]){
        $args = func_get_args();
        $data = array_shift($args);
        $h = "";
        for($i=0;$i<count($args);$i++){
            $h .= "<link rel=\"stylesheet\" href=\"".$args[$i]."\">\r\n";
        }
        return $h;
    }


    //解析含有xpath的布尔运算式（或返回boolean的函数），条件式使用空格分隔，返回bool
    public static function _parse_condition($str="",$data=[]){
        if($str=="") return FALSE;
        $str = trim($str);
        $str = _str_replace_($str,[
            ["(","( "],
            [")"," )"],
            [","," , "],
        ]);
        $str = preg_replace("/\s\s+/"," ",$str);
        $arr = explode(" ",$str);
        for($i=0;$i<count($arr);$i++){
            $ai = $arr[$i];
            if(is_numeric($ai) || is_bool($ai) || in_array(strtolower($ai),["true","false",">","<",">=","<=","==","!=","||","&&","(",")"]) || TRUE==_str_has_($ai,["(",")"])) continue;
            $d = _array_xpath_($data,$ai);
            if(is_null($d)){
                $arr[$i] = "'".$ai."'";
            }else{
                $arr[$i] = "_array_xpath_(\$data,'".$ai."')";
            }
        }
        $rst = FALSE;
        $evs = implode(" ",$arr).";";
        //var_dump($evs);
        eval("\$rst = ".$evs);
        return $rst;
    }



    /*
     *  static
     */
    //load
    public static function load($tplc=""){
        if(!is_string($tplc) || $tplc=="") trigger_error("dp/template/needparam",E_USER_ERROR);
        $tplc = strtolower($tplc);
        $tplc = trim($tplc,"/");
        if(strpos($tplc,"/")===FALSE){
            //全局tpl
            if(isset(self::$_LIST_[$tplc])) return self::$_LIST_[$tplc];
            $tplp = _tpl_path_($tplc);
            if(file_exists($tplp.self::$ext_tpl)){
                $tplf = $tplp.self::$ext_tpl;
            }else if(file_exists($tplp.self::$ext_php)){
                $tplf = $tplp.self::$ext_php;
            }else{
                trigger_error("dp/template/undefined",E_USER_ERROR);
            }
            self::$_LIST_[$tplc] = new Template($tplf);
            return self::$_LIST_[$tplc];
        }else{
            $tplkey = str_replace("/","_",$tplc);
            if(isset(self::$_LIST_[$tplkey])) return self::$_LIST_[$tplkey];
            $tplarr = explode("/",$tplc);
            $tpln = array_pop($tplarr);
            if(TRUE===App::has($tplarr[0])){    //属于App的tpl
                array_splice($tplarr,1,0,"tpl");
                $tplp = _app_path_(implode("/",$tplarr)."/".$tpln);
            }else{
                $tplp = _tpl_path_($tplc);
            }
            if(file_exists($tplp.self::$ext_tpl)){
                $tplf = $tplp.self::$ext_tpl;
            }else if(file_exists($tplp.self::$ext_php)){
                $tplf = $tplp.self::$ext_php;
            }else{
                trigger_error("dp/template/undefined",E_USER_ERROR);
            }
            self::$_LIST_[$tplkey] = new Template($tplf);
            return self::$_LIST_[$tplkey];
        }
    }

    //直接使用字符串创建tpl实例，此实例不缓存
    public static function load_str($str=""){
        if(!is_string($str)) $str = "";
        $str = trim($str);
        $reps = [
            ["#[","%{"],
            ["]#","}%"]
        ];
        for($i=0;$i<count($reps);$i++){
            $str = str_replace($reps[$i][0],$reps[$i][1],$str);
        }
        return new Template($str);
    }

    //检查给定字符串是否是合法的tpl文件路径，是则返回文件路径，否则返回FALSE
    public static function tplf($str=""){
        if(!is_string($str) || $str=="") return FALSE;
        $tplc = strtolower($str);
        $tplc = trim($tplc,"/");
        if(strpos($tplc,"/")===FALSE){
            //全局tpl
            $tplf = _tpl_path_($tplc.self::$tplext);
        }else{
            $tplarr = explode("/",$tplc);
            $tpln = array_pop($tplarr);
            if(TRUE===App::has($tplarr[0])){    //属于App的tpl
                array_splice($tplarr,1,0,"tpl");
                $tplf = _app_path_(implode("/",$tplarr)."/".$tpln.self::$tplext);
            }else{
                $tplf = _tpl_path_($tplc.self::$tplext);
            }
        }
        if(file_exists($tplf)){
            return $tplf;
        }else{
            return FALSE;
        }
    }


}