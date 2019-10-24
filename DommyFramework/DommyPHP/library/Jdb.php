<?php
/*
 *  DommyFramework json数据库操作类
 * 
 */

namespace dp;
use dp\App as App;

class Jdb {

    //实例集合
    private static $_LIST_ = [];

    //默认数据库文件结构
    private static $_STRC_ = [];

    //数据库文件，json
    private $_dbf = "";
    //缓存
    private $_cache = [];

    //curd操作缓存，执行某个操作时，生成缓存，操作结束则清空
    private $_curd = NULL;


    //构造
    public function __construct($dbf=""){
        if(!is_string($dbf) || $dbf=="" || !file_exists($dbf)) return FALSE;
        self::strc();
        $this->_dbf = $dbf;
        $this->cache();
    }

    //读取后台用户数据文件
    private function cache($recache=FALSE){
        if(empty($this->_cache) || $recache==TRUE) $this->_cache = _j2a_(file_get_contents($this->_dbf));
        return $this->_cache;
    }
    //写入后台数据
    private function save($backup=TRUE){
        $dbf = $this->_dbf;
        $dbfb = str_replace(".json",".bak.json",$dbf);
        if(!empty($this->_cache)){
            if(TRUE==$backup){
                $ori = file_get_contents($dbf);
                file_put_contents($dbfb,$ori);
            }
            $this->_cache["time_lastmod"] = time();
            file_put_contents($dbf,_a2j_($this->_cache));
        }
        return $this;
    }
    //写入log
    public function log($opr="未知操作",$uid=0,$save=FALSE){
        $log = $this->cdt("log");
        $log[] = [
            "operation" => $opr,
            "uid" => $uid,
            "timestamp" => time(),
            "ip" => _clientip_()
        ];
        $this->_cache["log"] = $log;
        if($save==TRUE) $this->save();
    }

    //获取
    //cache
    public function cdt($key=""){return _array_xpath_($this->cache(),$key);}
    //table列表
    public function tables(){return array_keys($this->cache()["tables"]);}
    //table
    public function t($tbn=""){
        if(FALSE===$this->has($tbn)) return NULL;
        return $this->cdt("tables/".$tbn);
    }

    //判断
    //是否存在表，或表/字段
    public function has($tbn=""){
        if(!is_string($tbn) || $tbn=="") return FALSE;
        if(strpos($tbn,"/")===FALSE) return in_array($tbn,$this->tables());
        $tarr = explode("/",$tbn);
        if(FALSE===$this->has($tarr[0])) return FALSE;
        $t = $this->cdt("tables/".$tarr[0]);
        return array_key_exists($tarr[1],$t["default"]);
    }
    //某表是否无数据
    public function emptytb($tbn=""){
        $t = $this->t($tbn);
        if(is_null($t) || !isset($t["data"]) || !is_array($t["data"]) || !array_key_exists(0,$t["data"])) return FALSE;
        $tbd = $t["data"];
        if(empty($tbd)) return TRUE;
        $flag = TRUE;
        for($i=0;$i<count($tbd);$i++){
            if(!empty($tbd[$i]) || !is_null($tbd[$i])){
                $flag = FALSE;
                break;
            }
        }
        return $flag;
    }

    //CURD操作
    //读取curd操作缓存
    public function curd($key=""){
        if(is_null($this->_curd)) return NULL;
        if(!is_string($key) || $key=="") return $this->_curd;
        return isset($this->_curd[$key]) ? $this->_curd[$key] : NULL;
    }
    //重置curd
    private function reset_curd(){
        $this->_curd = NULL;
    }
    //设定要操作的table，指定rs则在结果集中操作
    public function table($tbn="",$rs=NULL){
        if(!is_null($this->_curd)) trigger_error("dp/db/jdb/incurd::".$this->cdt("dbn"),E_USER_ERROR);
        if(!is_string($tbn) || $tbn=="") trigger_error("dp/db/needparam",E_USER_ERROR);
        if(FALSE===$this->has($tbn)) trigger_error("dp/db/notexists",E_USER_ERROR);
        $this->_curd = [
            "tbn" => $tbn,
            "table" => $this->cdt("tables/".$tbn),
            "rs" => is_null($rs) ? $this->cdt("tables/".$tbn."/data") : $rs,
            "where" => NULL,
            "order" => [],
            "limit" => []
        ];
        if(is_array($rs) && !empty($rs) && array_key_exists(0,$rs)){
            $this->_curd["rs"] = $rs;
        }
        return $this;
    }
    //where
    public function where($where=NULL){
        if(is_null($this->_curd)) trigger_error("dp/db/jdb/needtable",E_USER_ERROR);
        $this->_curd["where"] = self::parse_where($where);
        return $this;
    }
    //order
    public function order($order=NULL){
        if(is_null($this->_curd)) trigger_error("dp/db/jdb/needtable",E_USER_ERROR);
        $this->_curd["order"] = self::parse_order($order);
        return $this;
    }
    //limit
    public function limit($limit=NULL){
        if(is_null($this->_curd)) trigger_error("dp/db/jdb/needtable",E_USER_ERROR);
        $this->_curd["limit"] = self::parse_limit($limit);
        return $this;
    }
    //执行select
    public function select($reset=TRUE){
        if(is_null($this->_curd)) trigger_error("dp/db/jdb/needtable",E_USER_ERROR);
        $curd = $this->_curd;
        $rs = $curd["rs"];
        if(!is_array($rs) || empty($rs) || !array_key_exists(0,$rs)){
            if($reset==TRUE){
                $this->reset_curd();
            }else{
                $this->_curd["rs"] = [];
                return $this;
            }
            return [];
        }
        $rsos = [];
        $where = $curd["where"];
        $order = $curd["order"];
        $limit = $curd["limit"];
        for($i=0;$i<count($rs);$i++){
            $rsi = $rs[$i];
            if(TRUE===self::where_test($rsi,$where)){
                if(!isset($rsi["_rsidx_"])) $rsi["_rsidx_"] = $i;
                $rsos[] = $rsi;
            }
        }
        if(empty($rsos)){
            if($reset==TRUE){
                $this->reset_curd();
            }else{
                $this->_curd["rs"] = [];
                return $this;
            }
            return [];
        }
        if(!empty($order)){
            for($i=0;$i<count($order);$i++){
                array_multisort(array_column($rsos,$order[$i][0]),constant("SORT_".$order[$i][1]),$rsos);
            }
        }
        if(!empty($limit)){
            $rsos = array_splice($rsos,$limit[0],$limit[1]);
        }
        if($reset==TRUE){
            $this->reset_curd();
        }else{
            $this->_curd["rs"] = $rsos;
            return $this;
        }
        return $rsos;
    }
    //执行update
    public function update($data=[],$reset=TRUE){
        if(is_null($this->_curd)) trigger_error("dp/db/jdb/needtable",E_USER_ERROR);
        if(is_array($data) && empty($data)){
            $this->reset_curd();
            return FALSE;
        }
        if(!is_array($data)) $data = _to_array_($data);
        if(empty($data)){
            $this->reset_curd();
            return FALSE;
        }
        $this->select(FALSE);
        $curd = $this->_curd;
        $rs = $curd["rs"];
        $tbn = $curd["tbn"];
        if(empty($rs)){
            $this->reset_curd();
            return FALSE;
        }
        for($i=0;$i<count($rs);$i++){
            $rsidx = $rs[$i]["_rsidx_"];
            $di = _array_extend_($rs[$i], $data);
            $rs[$i] = $di;
            unset($di["_rsidx_"]);
            $this->_cache["tables"][$tbn]["data"][$rsidx] = $di;
        }
        $this->log("Update table [ ".$tbn." ] where ".$curd["where"]);
        $this->save();
        if($reset==TRUE){
            $this->reset_curd();
        }else{
            $this->_curd["rs"] = $rs;
            return $this;
        }
        return $rs;
    }
    //执行delete
    public function delete($reset=TRUE){
        if(is_null($this->_curd)) trigger_error("dp/db/jdb/needtable",E_USER_ERROR);
        $this->select(FALSE);
        $curd = $this->_curd;
        $rs = $curd["rs"];
        $tbn = $curd["tbn"];
        for($i=0;$i<count($rs);$i++){
            $rsidx = $rs[$i]["_rsidx_"];
            $this->_cache["tables"][$tbn]["data"][$rsidx] = NULL;
        }
        if(TRUE===$this->emptytb($tbn)){
            $this->_cache["tables"][$tbn]["data"] = [];
            $this->_cache["tables"][$tbn]["idx"] = 0;
        }
        $this->log("Delete from table [ ".$tbn." ] where ".$curd["where"]);
        $this->save();
        if($reset==TRUE){
            $this->reset_curd();
        }else{
            $this->_curd["rs"] = $rs;
            return $this;
        }
        return $rs;
    }
    //执行insert
    public function insert($data=[],$reset=TRUE){
        if(is_null($this->_curd)) trigger_error("dp/db/jdb/needtable",E_USER_ERROR);
        if(is_array($data) && empty($data)){
            $this->reset_curd();
            return FALSE;
        }
        if(!is_array($data)) $data = _to_array_($data);
        if(empty($data)){
            $this->reset_curd();
            return FALSE;
        }
        if(array_key_exists(0,$data)){
            $dts = [];
            for($i=0;$i<count($data);$i++){
                $dti = $this->insert($data[$i],FALSE);
                if(FALSE==$dti) break;
                $dts[] = $dti;
            }
            if($reset==TRUE) $this->reset_curd();
            return $dts;
        }else{
            $curd = $this->_curd;
            //$rs = $curd["rs"];
            $tbn = $curd["tbn"];
            $tbd = $this->t($tbn);
            $needed = $tbd["field"]["needed"];
            $flag = TRUE;
            for($i=0;$i<count($needed);$i++){
                if(!isset($data[$needed[$i]])){
                    $flag = FALSE;
                    break;
                }
            }
            if($flag==FALSE){
                $this->reset_curd();
                return FALSE;
            }
            $data = _array_extend_($tbd["default"],$data);
            $tbd["idx"] += 1;
            $data["id"] = $tbd["idx"];
            $tbd["data"][] = $data;
            $this->_cache["tables"][$tbn] = $tbd;
            $this->log("Insert into table [ ".$tbn." ]");
            $this->save();
            if($reset==TRUE) $this->reset_curd();
            return $data;
        }
    }
    //执行count
    public function count($reset=TRUE){
        if(is_null($this->_curd)) trigger_error("dp/db/jdb/needtable",E_USER_ERROR);
        $this->select(FALSE);
        $curd = $this->_curd;
        $rs = $curd["rs"];
        if($reset==TRUE){
            $this->reset_curd();
            return count($rs);
        }else{
            return $this;
        }
    }
    //搜索
    public function search(){
        $args = func_get_args();
        $argn = func_num_args();
        if($argn<=0){
            $this->reset_curd();
            return [];
        }
        $reset = is_bool($args[$argn-1]) ? array_pop($args) : TRUE;
        if(is_null($this->_curd)) trigger_error("dp/db/jdb/needtable",E_USER_ERROR);
        $curd = $this->_curd;
        $tbn = $curd["tbn"];
        $tbd = $this->t($tbn);
        $sfs = $tbd["field"]["search"];
        $where = "";
        $wor = [];
        $wand = [];
        for($i=0;$i<count($args);$i++){
            $sk = $args[$i];
            switch(substr($sk,0,1)){
                case "-" :
                    $sk = substr($sk,1);
                    $bool = " AND ";
                    $logic = " UNLIKE ";
                    break;

                case "&" :
                    $sk = substr($sk,1);
                    $bool = " AND ";
                    $logic = " LIKE ";
                    break;

                default :
                    $bool = " OR";
                    $logic = " LIKE ";
                    break;
            }
            for($j=0;$j<count($sfs);$j++){
                if($bool==" AND "){
                    $wand[] = "`".$sfs[$j]."`".$logic."'%".$sk."%'";
                }else{
                    $wor[] = "`".$sfs[$j]."`".$logic."'%".$sk."%'";
                }
            }
        }
        $wand = !empty($wand) ? (count($wand)>1 ? "(".implode(" AND ",$wand).")" : $wand[0]) : "";
        $wor = !empty($wor) ? (count($wor)>1 ? "(".implode(" OR ",$wor).")" : $wor[0]) : "";
        $where = $wor;
        if($where==""){
            if($wand==""){
                $where = NULL;
            }else{
                $where = $wand;
            }
        }else{
            if($wand!=""){
                $where .= " AND ".$wand;
            }
        }
        //return $where;
        return $this->where($where)->select($reset);
    }



    /*
     *  static
     */
    //获取默认数据库文件结构
    public static function strc(){
        if(empty(self::$_STRC_)){
            self::$_STRC_ = _j2a_(file_get_contents(_conf_path_("jdb.strc.json")));
        }
        return self::$_STRC_;
    }
    //创建Jdb数据库实例
    public static function load($dbn=""){
        if(!is_string($dbn) || $dbn=="") trigger_error("dp/db/needparam",E_USER_ERROR);
        $dbn = trim($dbn,"/");
        if(strpos($dbn,"/")===FALSE){
            $dbnn = strtolower($dbn);
            if(isset(self::$_LIST_[$dbnn])) return self::$_LIST_[$dbnn];
            $appn = NULL;
            $dbf = _res_find_(ucfirst($dbn).".json",AUTOLOAD_PATH,_dp_path_());
        }else{
            $darr = explode("/",$dbn);
            $app = array_shift($darr);
            $dbnn = array_pop($darr);
            if(isset(self::$_LIST_[strtolower($app)]) && isset(self::$_LIST_[strtolower($app)][strtolower($dbnn)])) return self::$_LIST_[strtolower($app)][strtolower($dbnn)];
            if(FALSE===App::has($app)) trigger_error("dp/db/notexists::".$dbn,E_USER_ERROR);
            $appn = strtolower($app);
            array_unshift($darr,$appn);
            $dbf = _res_find_(ucfirst($dbnn).".json",",".AUTOLOAD_PATH,_app_path_(implode("/",$darr)));
        }
        if(is_null($dbf)) trigger_error("dp/db/notexists::".$dbn,E_USER_ERROR);
        $cls = "\\dp\\Jdb";
        if(!is_null($appn)){
            if(!isset(self::$_LIST_[$appn])) self::$_LIST_[$appn] = [];
            self::$_LIST_[$appn][strtolower($dbnn)] = new $cls($dbf);
            return self::$_LIST_[$appn][strtolower($dbnn)];
        }else{
            self::$_LIST_[$dbnn] = new $cls($dbf);
            //var_dump(self::$_LIST_[$dbnn]);die();
            return self::$_LIST_[$dbnn];
        }
    }



    /*
     *  通用方法
     */
    //将普通SQL转为{{SQL}}，用于Jdb::where_test($rsi,$where)的参数
    public static function parse_where($sql=""){
        if(!is_string($sql) || $sql=="") return NULL;
        if(strpos($sql,"{{")!==FALSE && strpos($sql,"}}")!==FALSE) return $sql;
        $sql = trim($sql);
        $sql = preg_replace("/\s\s+/"," ",$sql);    //将多个空格转为单个空格
        $sql = preg_replace("/\(\s+/","(",$sql);
        $sql = preg_replace("/\s+\)/",")",$sql);
        $sql = _str_replace_($sql,[
            [") AND (",")A("],
            [" AND (","A("],
            [") AND ",")A"],
            [") OR (",")O("],
            [" OR (","O("],
            [") OR ",")O"],
            [" OR ","}} OR {{"],
            [" AND ","}} AND {{"],
            [")A(",") AND ("],
            ["A(","}} AND ("],
            [")A",") AND {{"],
            [")O(",") OR ("],
            ["O(","}} OR ("],
            [")O",") OR {{"],
            ["((((((","[6"],
            ["(((((","[5"],
            ["((((","[4"],
            ["(((","[3"],
            ["((","[2"],
            ["))))))","]6"],
            [")))))","]5"],
            ["))))","]4"],
            [")))","]3"],
            ["))","]2"],
            ["(","({{"],
            [")","}})"],
            ["[6","(((((({{"],
            ["[5","((((({{"],
            ["[4","(((({{"],
            ["[3","((({{"],
            ["[2","(({{"],
            ["]6","}}))))))"],
            ["]5","}})))))"],
            ["]4","}}))))"],
            ["]3","}})))"],
            ["]2","}}))"],
        ]);
        if(substr($sql,0,1)!="(") $sql = "{{".$sql;
        if(substr($sql,-1)!=")") $sql = $sql."}}";
        return $sql;
    }
    //检查给定的某条记录，是否符合where条件，返回boolean
    public static function where_test($rsi=[], $where=NULL){
        if(!is_array($rsi) || empty($rsi)) return FALSE;
        if(!is_string($where) || $where=="") return TRUE;
        $wstr = $where;
        //查找条件语句   WHERE {{field logic val}}
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
                }else if($carr[1]=="UNLIKE"){
                    $carr[2] = str_replace("%","",$carr[2]);
                    $rst = strpos($rsi[$carr[0]],$carr[2])===FALSE;
                }else if($carr[1]=="BETWEEN"){
                    $carr2_arr = explode(",",$carr[2]);
                    $rst = $rsi[$carr[0]]>=$carr2_arr[0] && $rsi[$carr[0]]<=$carr2_arr[1];
                }else if($carr[1]=="OUTOF"){
                    $carr2_arr = explode(",",$carr[2]);
                    $rst = $rsi[$carr[0]]<$carr2_arr[0] && $rsi[$carr[0]]>$carr2_arr[1];
                }else{
                    $carr[1] = $carr[1]=="=" ? "==" : $carr[1];
                    $evalstr = "\$rst = \$rsi[\"".$carr[0]."\"] ".$carr[1]." ".(is_numeric($carr[2]) || in_array(strtolower($carr[2]),["true","false"]) ? $carr[2] : "\"".$carr[2]."\"").";";
                    eval($evalstr);
                }
                $wstr = str_replace($ms[$i],($rst==TRUE ? 'TRUE' : 'FALSE'),$wstr);
                $wstr = str_replace("AND","&&",$wstr);
                $wstr = str_replace("OR","||",$wstr);
            }
            //var_dump($wstr);//die();
            eval("\$frst = ".$wstr.";");
            return $frst;
        }
        return TRUE;
    }
    //解析order
    public static function parse_order($order=NULL){
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
    //解析limit
    public static function parse_limit($limit=NULL){
        if((!is_string($limit) && !is_numeric($limit)) || $limit=="") return [];
        if(!is_string($limit)) $limit = "".$limit;
        if(strpos($limit,",")===FALSE) return is_numeric($limit) ? [0,(int)$limit] : [];
        $larr = explode(",",$limit);
        $larr[0] = trim($larr[0]);
        $larr[1] = trim($larr[1]);
        if(!is_numeric($larr[0]) || !is_numeric($larr[1])) return [];
        return [(int)$larr[0], (int)$larr[1]];
    }
}