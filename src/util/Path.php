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
     * 将绝对路径，转换为可以通过 Path::find 查询到的 相对路径
     * 如：
     * /data/ms/app/foo_app/assets/bar/jaz.js           -->  src/foo_app/bar/jaz.js
     * /data/ms/library/foo/bar.php                     -->  lib/foo/bar.php
     * /data/vendor/cgyio/spf/src/assets/lib/vue.lib    -->  spf/assets/lib/vue.lib
     * 
     * !! 需要在 Env::$current->config->path 中定义各 特殊路径
     * @param String $path 绝对路径
     * @return String|null 转换后的相对路径
     */
    public static function rela($path)
    {
        if (!Is::nemstr($path)) return null;

        //确保路径分隔符为 DS
        $path = str_replace(["\\","/"], DS, $path);
        //去除 path 可能存在的 ../.. 
        $path = self::fix($path);
        //路径数组
        $parr = explode(DS, $path);
        //路径数组长度
        $plen = count($parr);

        //确保 环境实例已创建
        if (Env::$isInsed!==true) return null;
        //获取预定义的 各种特殊路径
        $dirs = Env::$current->config->dir;
        $pathes = Env::$current->config->path;
        //var_dump($dirs);

        //针对 $path 在某个 app 路径下 的情况
        $apre = $pathes["app"];
        $alen = strlen($apre);
        if (substr($path, 0, $alen)===$apre) {
            $appp = substr($path, $alen);
            $appk = explode(DS, ltrim($appp, DS))[0];
            //将 dirs 中定义的 特殊路径附加 appk 前缀，形成完整的 特殊路径前缀
            foreach ($dirs as $dk => $dp) {
                if ($dk==="app") continue;
                $pdk = $dk."/".$appk;
                $pathes[$pdk] = $apre.DS.$appk.DS.str_replace(["\\","/"],DS, $dp);
            }
        }
        //var_dump($pathes);
        //exit;

        //将 特殊路径 分别拆分为数组，并按数组长度，大到小排序
        $pts = [];
        foreach ($pathes as $pk => $pi) {
            $pip = str_replace(["\\","/"], DS, $pi);
            $pia = explode(DS, $pip);
            $pts[] = [
                "key" => $pk,
                "path" => $pip,
                "parr" => $pia,
                "plen" => count($pia),
                "slen" => strlen($pip),
            ];
        }
        //按路径长度 大到小 排序
        usort($pts, function($a,$b) {
            if ($a["plen"]===$b["plen"]) return 0;
            return $a["plen"]>$b["plen"] ? -1 : 1;
        });
        //var_dump($pts);
        //exit;

        //检查是否有 符合的特殊路径 是 $path 的根路径
        for ($i=0;$i<count($pts);$i++) {
            $pti = $pts[$i];
            $pilen = $pti["plen"];
            //特殊路径 一定不能比 $path 长
            if ($pilen>$plen) continue;
            //特殊路径 字符串长度
            $slen = $pti["slen"];
            if (substr($path, 0, $slen) === $pti["path"]) {
                //找到符合条件的前缀
                $pr = $pti["key"].substr($path, $slen);
                //路径分割符 转为 /
                $pr = str_replace(DS, "/", $pr);
                return $pr;
            }
        }

        //var_dump($dirs);
        //var_dump($pts);
        //exit;

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
        $path = str_replace(["\\","/"],DS,$path);
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

    /**
     * 将某个路径下的 文件|子文件夹文件 递归输出为 一维数组
     * @param String $path 路径，必须是存在的本地文件夹路径
     * @param String $prefix 在一维文件数组中的 key 键名 前缀，默认 ""
     * @param String $glup key 的连接符，默认 -
     * @param String $ext 可以指定要查找的文件后缀名，默认 ""
     * @return Array 粗路径下所有找到的文件 一维数组
     *  [
     *      "pre-fn" => "物理路径",
     *      "pre-subdir-fn" => "",
     *      ...
     *  ]
     */
    public static function flat($path, $prefix="", $glup="-", $ext="")
    {
        //确保查找路径真实存在
        if (!Is::nemstr($path)) return [];
        $path = self::find($path, Path::FIND_DIR);
        if (!Is::nemstr($path)) return [];

        //处理 文件 key 的连接符
        if (!Is::nemstr($glup)) $glup = "-";
        if (strlen($glup)>1) $glup = substr($glup, -1);

        //处理前缀 必须为 foo-bar- 形式  或者  为空
        if (!Is::nemstr($prefix)) {
            $prefix = "";
        } else {
            if (substr($prefix, -1)!==$glup) {
                $prefix .= $glup;
            }
        }
        
        //要查询的文件 后缀名
        if (!Is::nemstr($ext)) $ext = "";
        if (Is::nemstr($ext)) {
            $oext = $ext;
            if (substr($ext, 0, 1)!==".") $ext = ".".$ext;
            $extlen = strlen($ext);
        } else {
            $oext = "";
            $extlen = 0;
        }

        //准备 文件 列表数组
        $fs = [];

        //遍历
        $dh = opendir($path);
        while ( false !== ($fn = readdir($dh)) ) {
            if (in_array($fn,[".",".."])) continue;
            $fp = $path.DS.$fn;
            //针对文件
            if (is_file($fp)) {
                if ($extlen>0) {
                    //指定了要查找的文件后缀名
                    if (strlen($fn)<=$extlen || strtolower(substr($fn, $extlen*-1))!==$ext) continue;
                    //解析得到 组件名 或 组件组 component-group 名称
                    $vn = $prefix.substr($fn, 0, $extlen*-1);
                    //写入组件数组
                    $fs[$vn] = $fp;
                } else {
                    //未指定要查找的文件后缀名，查找所有类型文件，将文件后缀名作为 key 的最后一节
                    $fpi = pathinfo($fp);
                    $fn = $fpi["filename"];
                    $fext = $fpi["extension"];
                    $vn = $prefix.$fn.$glup.$fext;
                    //写入组件数组
                    $fs[$vn] = $fp;
                }
                continue;
            }
            //针对文件夹
            if (is_dir($fp)) {
                $subfs = self::flat($fp, $prefix.$fn, $glup, $oext);
                if (Is::nemarr($subfs)) {
                    $fs = array_merge($fs, $subfs);
                }
            }
        }

        //返回
        return $fs;
    }

}