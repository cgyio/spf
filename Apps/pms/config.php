<?php
/*
 *  Dommyframewor框架
 *  \dp\app\Pms类 预设
 * 
 */

return [
    "version" => "0.1",
    //App模块名
    //"name" => "",
    //App模块标题
    "title" => "生产管理系统",
    //此App模块的子模块，类文件位于Approot/app/module/mod1.appmodule.php...
    "module" => array(),
    //此App模块包含的operation操作预设，用于通过router访问operation方法，以及用于前端页面hash路由访问后端方法（对应于前端dj.nav.tree）
    "oprsetting" => array(),
    //默认operation结构
    "oprdefault" => array(
        "appname" => NULL,
        "text" => NULL,
        "hash" => NULL,
        "hashpath" => NULL,
        "icon" => NULL,
        "notice" => NULL,
        "table" => array(),
        "btns" => array(),
        "tabs" => array()
    ),
    //此App模块拥有的系统通知模块
    "ntcsetting" => array(),
    //默认系统通知notice结构
    "ntcdefault" => array(
        "appname" => NULL,
        "code" => NULL,
        "text" => NULL,
        "desc" => NULL,
        "fromclass" => NULL,
        "frommethod" => NULL
    ),
    //是否采用default路由方式访问此App（host/app/{appname}/...），设置为FALSE时，采用自定义路由（host/{appname}/...）
    "dftrouter" => FALSE,
    //模块调用的数据库
    "db" => array(),
    //App自定参数，各个App不同
    "params" => array(),
    //App作为单页应用时，输出HTML框架的预设参数
    "page" => array()

    //"router" => array()     //此App模块的路由规则
];