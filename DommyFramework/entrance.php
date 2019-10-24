<?php
/*
 *  DommyFramework入口
 * 
 */ 



/*
 *  服务器设置
 */
@error_reporting(-1);	//0/-1 = 关闭/开启
@date_default_timezone_set("Asia/Shanghai");
@session_start();



/*
 *  定义全局常量
 */
define('DF_VERSION', '0.6');
define('DF_DEBUG', TRUE);
//define('DF_START_TIME', microtime(true));
//define('DF_START_MEM', memory_get_usage());
define('PROTOCOL', 'https');
define('DOMAIN', '820529.com');
define('IP', '47.89.249.16');
define('DOMAIN_AJAXALLOWED', '820529.com');

define('EXT', '.php');
define('DS', DIRECTORY_SEPARATOR);

define('ROOT_PATH', __DIR__ . DS . '..');
define('DF_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . DS . 'Apps');
define('DP_PATH', DF_PATH . DS . 'DommyPHP');
define('LIB_PATH', DP_PATH . DS . 'library');
define('DJ_PATH', DF_PATH . DS . 'DommyJS');
define('DU_PATH', DF_PATH . DS . 'DommyUI');

define('CACHE_PATH', DF_PATH . DS . 'cache');
define('LOG_PATH', DF_PATH . DS . 'log');
define('PAGE_PATH', DF_PATH . DS . 'page');
//define('TRAIT_PATH', LIB_PATH . 'traits' . DS);
//defined('APP_PATH') or define('APP_PATH', ROOT_PATH . 'Apps' . DS);
//defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);
//defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'cache' . DS);
//defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'temp' . DS);
//defined('CONF_PATH') or define('CONF_PATH', APP_PATH); // 配置文件目录
//defined('CONF_EXT') or define('CONF_EXT', EXT); // 配置文件后缀
//defined('ENV_PREFIX') or define('ENV_PREFIX', 'PHP_'); // 环境变量的配置前缀
// 环境常量
define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);
//util常量
define("NUM_DIGIT",2);
define("EXPORT_FORMATS","html,page,json,xml,str,dump");
define("EXPORT_FORMAT","html");
define('EXPORT_LANG', 'cn');    //输出语言
//autoload
define("AUTOLOAD_PATH","library,ctrl,model,view,db,page,plugin");	//自动加载的查找目录
//usr
define("USR_EXPIRE",60*60);	//登录失效



//工具函数库  _xxx_()
require LIB_PATH.DS."Util".EXT;
//自动加载
require LIB_PATH.DS."Loader".EXT;
//错误处理
require LIB_PATH.DS."Error".EXT;
//设置错误代码
\dp\Error::set();
//接管错误处理方法
function _err_handler_($error_level, $error_message, $file, $line){
	return call_user_func_array(array("\\dp\\Error","handler"), func_get_args());
}
set_error_handler("_err_handler_");
//Usr
\dp\Usr::create();
//App
\dp\App::load_default_config();
\dp\App::load_all();

//设置系统路由
_router_add_("usr",["_default_" => ["\\dp\\Usr","_router"]]);
_router_add_("app",["_default_" => ["\\dp\\App","_router"]]);
_router_add_("model",["_default_" => ["\\dp\\Model","_router"]]);
_router_add_("view",["_default_" => ["\\dp\\View","_router"]]);
_router_add_("res",["_default_" => "_res_"]);
_router_add_("js",["_default_" => "_dj_js_"]);
_router_add_("css",["_default_" => "_dj_css_"]);
_router_add_("plugin",["_default_" => ["\\dp\\Plugin","_router"]]);
_router_add_("wx",["_default_" => "_wx_router_"]);
_router_add_("iosapp",["_default_" => "_iosapp_"]);
_router_add_("see",["_default_" => "_see_"]);

