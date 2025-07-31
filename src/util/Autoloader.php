<?php
/**
 * 框架 特殊工具类
 * 处理 Composer autoloader 类自动加载
 * 将 当前 webroot 路径下的一些 特殊目录，额外添加到 类自动加载路径数组
 * !! 必须在 Env 实例化完成后 执行 Autoloader::patch() 
 */

namespace Spf\util;

use Spf\Env;
use Spf\util\Is;
use Spf\util\Str;

class Autoloader 
{
    /**
     * composer autoload 环境
     */
    //composer autoloader 类实例
    protected static $composerAutoloader = null;

    //定义 需要添加到 类自动加载的 特殊目录
    protected static $specialDirs = [
        "module", "model", "middleware", "view", "exception"
    ];

    /**
     * 执行 patch
     * @return Bool
     */
    public static function patch() 
    {
        //Env 环境参数 必须完成实例化
        if (Env::$isInsed !== true) return false;

        //composer autoloader 实例
        $alo = self::getComposerAutoloader();
        if (empty($alo)) return false;

        //将 webroot 目录下的 所有特殊路径 添加到对应的 命名空间 下
        self::patchDir(ROOT_PATH);

        //将所有 可用的 应用目录下 的 特殊路径 添加到对应的 命名空间 下
        if (is_dir(APP_PATH)) {
            //将 APP_PATH 作为 NS\\app\\*** 命名空间的 类文件路径
            self::addPsr4(
                ["app","App"],
                APP_PATH
            );
            //遍历 应用
            $dh = opendir(APP_PATH);
            while ( false !== ($app = readdir($dh)) ) {
                if (in_array($app, [".",".."])) continue;
                if (!is_dir(APP_PATH.DS.$app)) continue;
                //将每个 应用目录 也添加到 NS\\app\\*** 命名空间下
                self::addPsr4(
                    ["app","App"],
                    APP_PATH.DS.$app
                );
                //添加 应用目录下的 特殊路径
                self::patchDir(APP_PATH.DS.$app, $app);
            }
            closedir($dh);
        }

        return true;
    }

    /**
     * 将指定路径下的 所有特殊路径 添加到 对应的 类命名空间下
     * 这样 autoloader 将会在这些路径下 自动加载对应 命名空间下的 类文件了
     * @param String $root 指定的根路径，默认 ROOT_PATH 网站根目录
     * @param String $app 如果指定的根路径 是 某个 App 应用路径，则指定 应用名称，foo_bar 形式，默认 null
     * @return void
     */
    protected static function patchDir($root=null, $app=null)
    {
        //指定的 根目录 必须存在
        if (!Is::nemstr($root)) $root = defined("ROOT_PATH") ? ROOT_PATH : null;
        if (!is_dir($root)) return;

        //处理 应用名称
        if (Is::nemstr($app)) {
            //应用名称 统一转为 foo_bar 形式
            $app = Str::snake($app, "_");
            $libpre = ["app\\$app\\", "App\\$app\\"];
            $pre = "$app\\";
        } else {
            $libpre = "";
            $pre = "";
        }

        //特殊路径，定义在 Env::$current->config->context["dir"] 项下
        $dirs = Env::$current->dir;

        //lib 路径 --> 命名空间：NS\\***  或  NS\\app\\app_name\\***
        self::addPsr4(
            $libpre,
            [
                $root,
                $root.DS.str_replace("/",DS,$dirs["lib"])
            ]
        );

        //module | model | middleware | view | exception 等 特殊路径
        $sd = self::$specialDirs;
        foreach ($sd as $sk) {
            self::addPsr4(
                "$sk\\$pre",
                $root.DS.str_replace("/",DS,$dirs[$sk])
            );
        }
    }

    /**
     * 获取当前的 composer autoloader 类实例
     * @return \Composer\Autoload\ClassLoader 实例  或  null
     */
    protected static function getComposerAutoloader()
    {
        //先检查缓存
        $alo = self::$composerAutoloader;
        if (!empty($alo)) return $alo;

        //保存在 VENDOR_PATH 路径下的 autoload.php 文件
        $af = VENDOR_PATH . DS . "autoload.php";
        if (!file_exists($af)) return null;
        //从 autoloader.php 中提取出 当前的 ClassLoader 类名
        $al = file_get_contents($af);
        $alc = "ComposerAutoloader" . explode("::getLoader", explode("return ComposerAutoloader", $al)[1])[0];
        //调用 ClassLoader::getLoader 方法 获取 类实例
        $alo = $alc::getLoader();
        //缓存
        self::$composerAutoloader = $alo;
        return $alo;
    }

    /**
     * 批量调用 autoloader->addPsr4() 方法
     * @param String|Array $nspre 命名空间前缀，不含 NS，以 \ 结尾，可以是 多个前缀 组成的 indexed 数组
     * @param String|Array $path 在此路径下查询类文件，可以是 indexed 数组
     * @return void
     */
    protected static function addPsr4($nspre, $path=[])
    {
        //autoloader 实例
        $alo = self::getComposerAutoloader();
        if (empty($alo)) return;

        //查询路径必须存在
        if (!Is::nemstr($path) && !Is::nemarr($path)) return;
        if (Is::nemstr($path)) $path = [ $path ];

        if (Is::nemstr($nspre) || $nspre=="") {
            $pres = [ $nspre ];
        } else if (Is::nemarr($nspre) && Is::indexed($nspre)) {
            $pres = $nspre;
        } else {
            $pres = [];
        }
        if (!Is::nemarr($pres)) return;
        
        //!! 自动为 命名空间前缀 附加 NS 常量的开头
        $ns = defined("NS") ? NS : "Spf\\";
        $ns = trim($ns, "\\");
        $pres = array_map(function($pri) use ($ns) {
            if (!is_string($pri)) return null;
            //自动添加 \ 结尾
            if ($pri!=="") $pri = trim($pri, "\\")."\\";
            //自动添加 NS 开头
            $pri = $ns."\\".$pri;
            return $pri;
        }, $pres);
        //去除 空前缀
        $pres = array_filter($pres, function($pri) {
            return Is::nemstr($pri);
        });
        if (!Is::nemarr($pres)) return;

        //批量调用 autoloader->addPsr4()
        foreach ($pres as $pri) {
            $alo->addPsr4($pri, $path);
        }
    }



    /**
     * !! 已废弃
     * patch composer autoload
     * 必须在 环境参数常量定义之后 执行
     * @return void
     */
    protected static function patchAutoload()
    {
        $ns = trim(NS, "\\");
        $af = VENDOR_PATH . DS . "autoload.php";
        if (file_exists($af)) {
            $al = file_get_contents($af);
            $alc = "ComposerAutoloader" . explode("::getLoader", explode("return ComposerAutoloader", $al)[1])[0];
            $alo = $alc::getLoader();
            if (!empty($alo)) {

                /**
                 * 将 App 应用路径下的 lib|module|model|view
                 */
                if (is_dir(APP_PATH)) {
                    $apps_dh = opendir(APP_PATH);
                    $psr_app = [APP_PATH];
                    while (($app = readdir($apps_dh)) !== false) {
                        if ($app == "." || $app == "..") continue;
                        //路径名形式 foo_bar
                        $app_dir = APP_PATH . DS . $app;
                        //路径名形式 foo_bar 转为 类名形式 FooBar
                        $uap = Str::camel($app, true);      //ucfirst(strtolower($app));
                        if (is_dir($app_dir)) {

                            // app class dir
                            $psr_app[] = $app_dir;

                            //lib class dir
                            $psr_ds = array_map(function($i) use ($app_dir) {
                                return $app_dir.DS.str_replace("/",DS,trim($i));
                            }, DIR_LIB);
                            $psr_ds = array_merge($psr_ds, [$app_dir]);
                            $alo->addPsr4($ns.'\\App\\'.$app.'\\', $psr_ds);
                            $alo->addPsr4($ns.'\\App\\'.$uap.'\\', $psr_ds);
                            $alo->addPsr4($ns.'\\app\\'.$app.'\\', $psr_ds);
                            $alo->addPsr4($ns.'\\app\\'.$uap.'\\', $psr_ds);

                            //model class dir
                            $psr_ds = array_map(function($i) use ($app_dir) {
                                return $app_dir.DS.str_replace("/",DS,trim($i));
                            }, DIR_MODEL);
                            $psr_ds = array_merge($psr_ds, [$app_dir]);
                            $alo->addPsr4($ns.'\\App\\'.$app.'\\model\\', $psr_ds);
                            $alo->addPsr4($ns.'\\App\\'.$uap.'\\model\\', $psr_ds);
                            $alo->addPsr4($ns.'\\app\\'.$app.'\\model\\', $psr_ds);
                            $alo->addPsr4($ns.'\\app\\'.$uap.'\\model\\', $psr_ds);

                            //module class dir
                            $psr_ds = [ 
                                $app_dir.DS."module"
                            ];
                            $alo->addPsr4($ns.'\\module\\'.$app.'\\', $psr_ds);
                            $alo->addPsr4($ns.'\\module\\'.$uap.'\\', $psr_ds);
                            
                            //error class
                            $psr_ds = [
                                $app_dir.DS.'error'
                            ];
                            $alo->addPsr4($ns.'\\error\\'.$app.'\\', $psr_ds);
                            $alo->addPsr4($ns.'\\error\\'.$uap.'\\', $psr_ds);
                    
                        }
                    }
                    $alo->addPsr4($ns.'\\App\\', $psr_app);
                    $alo->addPsr4($ns.'\\app\\', $psr_app);
                    closedir($apps_dh);
                }

                /**
                 * patch module classes autoload
                 */
                $mdp = MODULE_PATH;
                if (is_dir($mdp)) {
                    $dh = opendir($mdp);
                    while (($md = readdir($dh)) !== false) {
                        if ($md == "." || $md == "..") continue;
                        $md_dir = $mdp . DS . $md;
                        if (is_dir($md_dir)) {
                            //route class
                            //$alo->addPsr4($ns.'\\route\\', $md_dir.DS."route");
                            //error class
                            $alo->addPsr4($ns.'\\error\\', $md_dir.DS."error");
                        }
                    }
                    closedir($dh);
                }

                /**
                 * patch web class autoload
                 */
                //$alo->addPsr4($ns.'\\Web\\route\\', ROOT_PATH.DS."route");
                $alo->addPsr4($ns.'\\error\\', ROOT_PATH.DS."error");

                /**
                 * patch web lib/model/module class
                 */
                $lib_ds = array_map(function($i) {
                    return ROOT_PATH.DS.str_replace("/",DS,trim($i));
                }, DIR_LIB);
                $model_ds = array_map(function($i) {
                    return ROOT_PATH.DS.str_replace("/",DS,trim($i));
                }, DIR_MODEL);
                $alo->addPsr4($ns.'\\', $lib_ds);
                $alo->addPsr4($ns.'\\model\\', $model_ds);
                $alo->addPsr4($ns.'\\module\\', [ ROOT_PATH.DS."module" ]);

            }
        }
    }
}