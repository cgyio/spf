<?php
/*
 *  DommyFramework 插件
 *  Vcode  验证码
 * 
 */

namespace dp\plugin;
use dp\Plugin as Plugin;

class Vcode extends Plugin {

    public function csv($s="foobar"){
        $s = "1,2,3,4\r\n5,6,7,8\r\n9,10,11,12".$s;
        return $s;
    }

    public static function tst(){
        return "\dp\plugin\Vcode::tst()";
    }
}