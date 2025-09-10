<?php
/**
 * 工具类
 * Path 路径处理
 */

namespace Spf\util;

use Spf\Env;

class Path extends Util 
{
    /**
     * 用于 Path::find 方法的 查询方式
     */
    public const FIND_FILE  = 0;
    public const FIND_DIR   = 1;
    public const FIND_BOTH  = -1;

    /**
     * 返回默认的起始路径
     * 默认以 
     *      ROOT_PATH / PRE_PATH
     * 常量为起始路径
     * 如果常量未定义，则返回 空字符
     * @return String
     */
    public static function root()
    {
        if (defined("ROOT_PATH")) return ROOT_PATH;
        if (defined("PRE_PATH")) return PRE_PATH;
        return "";
    }

    /**
     * 将输入的预定义路径名称，转换为路径，路径名称已定义为常量
     * foo  -->  FOO_PATH
     * @param String $cnst 常量名，不带 _PATH
     * @return String 路径
     */
    public static function cnst($cnst = "")
    {
        $cp = null;
        if (empty($cnst)) {
            $cp = self::root();
        } else {
            $cnst = strtoupper($cnst)."_PATH";
            if (defined($cnst)) {
                $cp = constant($cnst);
            }
        }
        if (empty($cp)) return null;
        return self::fix($cp);
    }

    /**
     * 获取 定义在 Env::$current->config->context["dir"] 项下的 特殊路径
     * !! 必须在 环境参数初始化后 执行，否则无法获取，因为 这些常量 还未定义
     * @return Array [ "app" => "", "lib" => "", ... ]
     */
    public static function dirs()
    {
        //检查 Env 是否已经实例化
        if (Env::$isInsed !== true) return [];
        //定义在 Env::$current->config->context["dir"] 项下的 特殊路径
        $dirs = Env::$current->dir;
        if (!Is::nemarr($dirs)) return [];
        return $dirs;
    }

    //根据输入，返回物理路径（文件可能不存在）
    /**
     * 根据输入，返回物理路径（文件/路径可能不存在）
     * foo/bar  -->  ROOT_PATH/foo/bar
     * app/foo/bar  -->  APP_PATH/foo/bar
     * @param String $path
     * @return String
     */
    public static function mk($path = "")
    {
        if (empty($path)) return self::root();
        $path = trim(str_replace("/", DS, $path), DS);
        if (!is_null(self::relative($path))) {
            //$path 已经包含了 起始路径，表示这是一个 物理路径，直接返回
            return self::fix($path);
        }
        $parr = explode(DS, $path);
        $p = [];
        $root = array_shift($parr);
        $rootpath = self::cnst($root);
        if (!is_null($rootpath)) {
            $p[] = $rootpath;
        } else {
            $p[] = self::root();
            array_unshift($parr, $root);
        }
        $p = array_merge($p, $parr);
        return self::fix(implode(DS, $p));
    }

    /**
     * 将输入的绝对路径，转换为相对于 $root 的相对路径
     * /root-path/foo/bar  -->  foo/bar
     * @param String $path
     * @param String $root 相对路径的起始，默认为网站根目录 ROOT_PATH;
     * @return String|null
     * 当输入的路径 不是 起始于 $root 的路径时，返回 null
     */
    public static function relative($path = "", $root = null)
    {
        if (!Is::nemstr($path)) return null;
        if (empty($root)) $root = self::root();
        $path = self::fix($path);
        $root = self::fix($root);
        $rlen = strlen($root);
        if (substr($path, 0, $rlen) === $root) {
            return substr($path, $rlen);
        }
        return null;
    }

    /**
     * 根据输入，查找真实存在的 文件路径|文件夹路径
     * foo/bar          --> ROOT_PATH/foo/bar
     * xxxx/foo         --> XXXX_PATH/foo
     * 
     * app/foo/bar      --> APP_PATH/foo/bar
     * xxxx/foo/bar     --> APP_PATH/foo/[Env->dir[xxx]]/bar 
     *                  --> 或 XXXX_PATH/foo/bar
     * @param String $path 要查找的路径 文件|文件夹
     * @param Int $type 查询方法，FIND_[FILE|DIR|BOTH] 默认 FIND_FILE
     * @return String|null 找到 文件|文件夹 则返回其真实路径，否则 返回 null
     */
    public static function find($path, $type = Path::FIND_FILE)
    {
        //查询路径必须合法
        if (!Is::nemstr($path)) return null;

        //先判断一次
        if (file_exists($path)) {
            //如果直接输入了 存在的实际 文件|文件夹 路径
            $isdir = is_dir($path);
            $path = self::fix($path);
            if ($type==Path::FIND_BOTH) return $path;
            if ($type==Path::FIND_FILE && !$isdir) return $path;
            if ($type==Path::FIND_DIR && $isdir) return $path;
            return null;
        }

        //规范路径分隔符为 DS 并去除 头尾的 DS
        $path = trim(str_replace(["/","\\"], DS, $path), DS);
        //路径数组
        $parr = explode(DS, $path);
        //查询路径的 根节点
        $proot = array_shift($parr);
        //剩余的 查询路径 转为 str 以 DS 开头
        $parrstr = empty($parr) ? "" : DS.implode(DS,$parr);
        //所有已定义的 特殊路径
        $dirs = self::dirs();
        //lib 路径会在 类名路径中省略，因此查询文件时，也要尝试省略 lib 路径名的情况
        $libdir = trim(str_replace("/",DS,$dirs["lib"]), DS);

        //定义需要判断 file_exists | is_dir 的 路径列表
        $chks = [];
        
        //检查根节点是否特殊路径
        if (isset($dirs[$proot])) {
            //根节点是 特殊路径
            $dir = trim(str_replace("/",DS,$dirs[$proot]), DS);
            if (empty($parr)) {
                //直接检查对应的 ***_PATH
                $chks[] = self::cnst($proot);
            } else {
                //剩余的 parr 转为 路径str 以 DS 开头
                $subparr = count($parr)<=1 ? "" : DS.implode(DS, array_slice($parr, 1));
                if ($proot=="app") {
                    //根节点是 app
                    $chks[] = APP_PATH.DS.implode(DS, $parr);
                    //省略 lib 的情况
                    $chks[] = APP_PATH.DS.$parr[0].DS.$libdir.$subparr;
                } else {
                    //根节点不是 app 需要检查是否可能在 app 路径下
                    $chks[] = APP_PATH.DS.$parr[0].DS.$dir.$subparr;
                    //在检查 ***_PATH 路径下
                    $chks[] = self::cnst($proot).DS.implode(DS, $parr);
                }
            }
        } else if (!is_null(self::cnst($proot))) {
            //根节点 是 pre|vendor|cgy|spf 等框架固定路径
            $chks[] = self::cnst($proot).$parrstr;
        } else {
            //个节点不是任何 特殊路径，使用 webroot
            $chks[] = self::root().DS.$proot.$parrstr;
            //省略 lib 的情况
            $chks[] = self::root().DS.$libdir.DS.$proot.$parrstr;
        }

        //按顺序 检查 chks 数组中的 路径
        for ($i=0;$i<count($chks);$i++) {
            $chki = self::fix($chks[$i]);
            if (!Is::nemstr($chki)) continue;
            switch ($type) {
                case self::FIND_FILE :
                    if (file_exists($chki) && !is_dir($chki)) return $chki;
                    break;
                case self::FIND_DIR :
                    if (is_dir($chki)) return $chki;
                    break;
                case self::FIND_BOTH :
                    if (file_exists($chki) || is_dir($chki)) return $chki;
                    break;
            }
        }

        //未找到
        return null;
    }

    /**
     * 在给定的多个 path 中，挑选 真实存在的 文件|文件夹
     * @param Array $pathes 要检查的 文件|文件夹 路径 数组
     * @param Bool $all 是否返回所有存在的 文件|文件夹 ，false 则进返回第一个存在的 默认 false
     * @param Int $type 查询方法，FIND_[FILE|DIR|BOTH] 默认 FIND_FILE
     * @return String|Array|null
     */
    public static function exists($pathes=[], $all=false, $type=Path::FIND_FILE) {
        if (!Is::nemarr($pathes)) return null;
        $exists = [];
        foreach ($pathes as $i => $v) {
            $rv = self::find($v, $type);
            if (Is::nemstr($rv)) $exists[] = $rv;
        }
        if (empty($exists)) return null;
        return $all ? $exists : $exists[0];
    }

    //遍历路径path，callback($file)，$recursive = true时，递归遍历所有子目录
    public static function traverse($path = "", $callback = null, $recursive = false)
    {
        if (!is_dir($path) || !is_callable($callback)) return false;
        $dh = @opendir($path);
        $rst = [];
        while (false !== ($file = readdir($dh))) {
            if ($file == "." || $file == "..") continue;
            $_rst = $callback($path, $file);
            $fp = $path.DS.$file;
            if ($recursive && is_dir($fp)) {
                $_rst = self::traverse($fp, $callback, true);
            }
            if ($_rst==="_continue_") continue;
            if ($_rst==="_break_") break;
            $rst[] = [$file, $_rst];
        }
        closedir($dh);
        return $rst;
    }

    /**
     * 创建 多级 文件夹，$path 从 root 根目录开始
     * !! 要写入的文件夹路径必须有写权限，否则报错！！
     * @param String $path 要创建的 文件夹路径
     * @param Int $mod 八进制权限模式 默认 0777
     * @return Bool
     */
    public static function mkdir($path = "", $mod=0777)
    {
        if (!Is::nemstr($path)) return false;

        //先检查一次
        if (!empty(self::find($path, Path::FIND_DIR))) {
            //path 路径已存在
            return true;
        }

        //路径数组
        $path = str_replace(["\\","/"],DS,$path);   //trim(str_replace(["\\","/"],DS,$path), DS);
        $parr = explode(DS, $path);

        //从右向左 依次检查 路径是否存在
        $dir = null;
        for ($i=count($parr)-1;$i>=0;$i--) {
            $chkarr = array_slice($parr, 0, $i);
            $dir = self::find(implode("/", $chkarr), Path::FIND_DIR);
            if (!empty($dir)) {
                $parr = array_slice($parr, $i);
                break;
            }
        }

        if (empty($dir)) {
            //给定的 path 路径都不存在，直接向 ROOT_PATH 创建路径
            $mkdir = ROOT_PATH.DS.$path;
        } else {
            //给定的 path 有部分路径已存在
            if (empty($parr)) {
                //给定的 path 整个路径 都已存在
                return true;
            }

            //从存在的 文件夹开始 创建新路径
            $mkdir = $dir.DS.implode(DS,$parr);
        }

        //创建路径
        //var_dump($mkdir);exit;
        mkdir($mkdir, $mod, true);
        if (!is_dir($mkdir)) return false;
        return true;
    }

    /**
     * 创建文件，如果路径不存在，自动创建路径(多级) $path 从 root 根目录开始
     * !! 要写入的文件夹路径必须有写权限，否则报错！！
     * @param String $path 要创建的文件路径
     * @param String $content 文件内容，指定 则写入新创建的文件
     * @return Bool 是否创建成功
     */
    public static function mkfile($path, $content=null)
    {
        if (!Is::nemstr($path)) return false;

        //先判断文件是否已经存在
        $fp = self::find($path, Path::FIND_FILE);
        if (!empty($fp)) {
            //文件已存在
            //如果指定了 content 则写入
            if (Is::nemstr($content)) return file_put_contents($fp, $content)!==false;
            //未指定直接返回 true
            return true;
        }

        //获取文件名
        $fn = basename($path);
        //获取文件所在文件夹路径
        $dir = dirname($path);
        //检查路径是否存在
        $dp = self::find($dir, Path::FIND_DIR);
        if (empty($dp)) {
            //文件所在文件夹路径不存在，创建
            $mkdir = self::mkdir($dir);
            if ($mkdir!==true) {
                //创建文件夹失败
                return false;
            }
            //获取新创建的 文件夹路径
            $dp = self::find($dir, Path::FIND_DIR);
        }
        //确保 文件夹 存在|已被创建
        if (empty($dp)) return false;

        //创建文件
        $fp = $dp.DS.$fn;
        $fh = fopen($fp, "w");
        //写入内容
        if (!Is::nemstr($content)) $content ="";
        fwrite($fh, $content);
        fclose($fh);
        
        return true;
        
    }

    /**
     * 删除 文件|文件夹
     * 删除文件 使用 unlink
     * 删除文件夹 rmdir() 只能删除空文件夹，因此需要先 递归删除内部内容
     * @param String $path 要删除的 文件|文件夹 路径
     * @return Bool
     */
    public static function del($path)
    {
        if (!Is::nemstr($path)) return false;
        $path = self::find($path, self::FIND_BOTH);
        //要删除的 路径必须存在
        if (empty($path)) return false;

        //删除文件
        if (!is_dir($path)) return unlink($path);

        //删除文件夹，需要先递归删除 文件夹中的内容
        $files = array_diff(scandir($path), [".", ".."]);
        foreach ($files as $file) {
            self::del($path.DS.$file);
            //(is_dir("$dir/$file")) ? self::del("$dir/$file") : unlink("$dir/$file");
        }
        //删除空文件夹
        return rmdir($path);
    }

    /**
     * 判断 文件|文件夹 可以写入
     * @param String $path 文件|文件夹 路径 find 方法的第一参数
     * @return Bool
     */
    public static function isWritable($path)
    {
        $path = self::find($path, Path::FIND_BOTH);
        //路径必须存在
        if (empty($path)) return false;

        //针对 文件夹
        if (is_dir($path)) return is_writable($path);

        //针对 文件
        $dir = dirname($path);
        return is_writable($path) && is_writable($dir);
    }

    /**
     * 判断 文件|文件夹 是否可以通过 chmod 修改权限
     * @param String $path 文件|文件夹 路径 find 方法的第一参数
     * @return Bool
     */
    public static function canChmod($path)
    {
        $path = self::find($path, Path::FIND_BOTH);
        //路径不存在
        if (empty($path)) return false;

        //获取路径的所有者 ID 和进程的用户 ID
        $fileOwnerUid = fileowner($path);   //文件/目录的所有者 UID
        $processUid = posix_geteuid();      //当前 PHP 进程的 UID

        //进程是 root（UID=0），或进程是路径的所有者 可修改权限
        return $processUid === 0 || $processUid === $fileOwnerUid;
    }



    /**
     * 批量 对 path 进行处理，返回正确的路径字符串
     * @param String $path
     * @return String
     */
    public static function fix($path = "")
    {
        //去除 ..
        $path = self::up($path, DS);
        //其他操作 
        //...

        return $path;
    }

    /**
     * 计算path数组中的..标记，返回计算后的path字符串
     * foo/bar/jaz/../../tom  -->  foo/tom
     * @param String $path
     * @param String $dv 路径分隔符，默认 DS
     * @return String 路径 不含 ..
     */
    public static function up($path = "", $dv = DS)
    {
        if (empty($path) || !is_string($path)) return "";
        if ($dv!=DS) $path = str_replace(DS, $dv, $path);
        $path = str_replace("/", $dv, $path);
        $path = explode($dv, $path);
        if ($path[0] == "..") return "";
        for ($i=0; $i<count($path); $i++) {
            if ($path[$i] == "") $path[$i] = "__empty__";
            if ($path[$i] == "..") {
                for ($j=$i-1; $j>=-1; $j--) {
                    if ($j < 0) {   //越界
                        return "";
                    }
                    if ($path[$j] != ".." && !is_null($path[$j])) {
                        $path[$j] = null;
                        break;
                    }
                }
            }
        }
        $path = array_merge(array_diff($path, [null,".."]), []);
        return str_replace("__empty__", "", implode($dv, $path));
    }



    /**
     * 从文件路径 获取 后缀名，不检查文件是否存在
     * 统一返回  .json|.php|... 形式 后缀名
     * @param String $file 文件路径
     * @return String|null 后缀名 全小写，带 .    .php|.json|... 形式
     */
    public static function ext($file)
    {
        if (!Is::nemstr($file)) return null;
        $pi = pathinfo($file);
        $ext = $pi["extension"];
        if (!Is::nemstr($ext)) return null;
        return ".".strtolower($ext);
    }

    /**
     * 判断给定的 路径 是否是 url 形式 以 https:// | // | / 开头
     * @param String $path
     * @return Bool
     */
    public static function isUrl($path)
    {
        if (!Is::nemstr($path)) return false;
        return strpos($path, "://")!==false || substr($path, 0,2)==="//" || substr($path, 0,1)==="/";
    }



    

    //根据输入，查找真实存在的文件，返回文件路径，未找到则返回null
    //!! 已废弃
    public static function __find(
        $path = "",     //要查找的文件或文件夹
        $options = []   //可选的参数
    ) {
        $options = Arr::extend([
            "inDir" => DIR_ASSET,
            "subDir" => "",
            "checkDir" => false
        ], $options);
        if (file_exists($path) && $options["checkDir"]==false) return $path;
        if (is_dir($path) && $options["checkDir"]==true && substr($path, 0, 4)!="app/") return $path;
        $path = trim(str_replace("/", DS, $path), DS);
        $local = [];

        $subDir = $options["subDir"];
        if (empty($subDir)) $subDir = [];
        $subDir = Is::indexed($subDir) ? $subDir : (Is::nemstr($subDir) ? explode(",", $subDir) : []);
        $checkDir = $options["checkDir"];
        $inDir = $options["inDir"];
        if (empty($inDir)) $inDir = [];
        $inDir = Is::indexed($inDir) ? $inDir : (Is::nemstr($inDir) ? explode(",", $inDir) : []);
        $appDir = [];
        if (!empty($inDir)) {
            $parr = explode(DS, $path);
            //if (strtolower($parr[0]) == "app" || !is_null(Resper::cls("App/".ucfirst(strtolower($parr[0]))))) {
            if (strtolower($parr[0]) == "app" || is_dir(APP_PATH.DS.strtolower($parr[0]))) {
                if (strtolower($parr[0]) == "app") array_shift($parr);
                if (is_dir(APP_PATH.DS.$parr[0])) {
                    $app = array_shift($parr);
                    $appDir[] = "app/$app";
                    foreach ($inDir as $i => $dir) {
                        $appDir[] = "app/$app/$dir";
                        //$appDir[] = APP_PATH.DS.$app.DS.$dir;
                    }
                    $npath = implode(DS, $parr);
                    $gl = self::_findarr($npath, [
                        "inDir" => $appDir,
                        "subDir" => $subDir,
                        "checkDir" => $checkDir
                    ], $local);
                }
            /*} else if (!is_null(self::cnst($parr[0]))) {
                $cnst = array_shift($parr);
                foreach ($inDir as $i => $dir) {
                    $cnstDir[] = "$cnst/$dir";
                }
                $npath = implode(DS, $parr);
                $gl = self::_findarr($npath, [
                    "inDir" => $cnstDir,
                    "subDir" => $subDir,
                    "checkDir" => $checkDir
                ], $local);*/
            } else {
                if (!is_null(self::cnst($parr[0]))) {
                    $cnst = array_shift($parr);
                } else {
                    $cnst = "root";
                }
                //var_dump($cnst);
                foreach ($inDir as $i => $dir) {
                    $cnstDir[] = "$cnst/$dir";
                }
                $npath = implode(DS, $parr);
                $gl = self::_findarr($npath, [
                    "inDir" => $cnstDir,
                    "subDir" => $subDir,
                    "checkDir" => $checkDir
                ], $local);
            }
        }
        $gl = self::_findarr($path, [
            "inDir" => $inDir,
            "subDir" => $subDir,
            "checkDir" => $checkDir
        ], $local);
        
        //var_dump($local);
        //exit;
        if (isset($_GET["break_dump"]) && $_GET["break_dump"]=="yes") {
            var_dump($local);
            //exit;
        }
        //break_dump("break_pathfind", $local);
        
        foreach ($local as $i => $v) {
            if (file_exists($v)) return $v;
            if ($checkDir && is_dir($v)) return $v;
        }
        return null;
    }

    //建立查找路径数组
    //!! 已废弃
    protected static function _findarr(
        $path = "",
        $options = [],  //已经由 find 方法处理过，可直接使用，不做排错
        &$olocal = []    //将建立的路径数组 merge 到此数组中
    ) {
        $inDir = $options["inDir"];
        $subDir = $options["subDir"];
        $checkDir = $options["checkDir"];
        $inApp = isset($inDir[0]) && Str::has($inDir[0],"app/");
        $local = [];
        $info = pathinfo(self::mk($path));
        if (!$inApp) {
            $local[] = self::mk($path);
            if ($checkDir) $local[] = $info["dirname"].DS.$info["filename"];
        }
        foreach ($subDir as $k => $sdi) {
            $sdi = str_replace("/",  DS, $sdi);
            $local[] = $info["dirname"].DS.$sdi.DS.$info["basename"];
            if ($checkDir) $local[] = $info["dirname"].DS.$sdi.DS.$info["filename"];
        }
        foreach ($inDir as $i => $idi) {
            $idi = str_replace("/", DS, $idi);
            $pi = self::mk($idi.DS.$path);
            $local[] = $pi;
            $info = pathinfo($pi);
            if ($checkDir) $local[] = $info["dirname"].DS.$info["filename"];
            foreach ($subDir as $k => $sdi) {
                $sdi = str_replace("/",  DS, $sdi);
                $local[] = $info["dirname"].DS.$sdi.DS.$info["basename"];
                if ($checkDir) $local[] = $info["dirname"].DS.$sdi.DS.$info["filename"];
            }
        }
        //$local = array_unique($local);
        $olocal = array_merge($olocal, $local);
        $olocal = array_unique($olocal);
    }
}