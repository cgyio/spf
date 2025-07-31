<?php
/**
 * 框架参数配置工具类 基类
 */

namespace Spf;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Conv;

class Configer 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [];

    //用户设置 需要覆盖 init 参数
    protected $opt = [];

    //经过处理后的 运行时参数
    protected $context = [];

    //已定义的常量
    //public static $cnsts = [];

    /**
     * runtimeCache trait 要求的属性
     * 已在 trait 中定义
     */
    //public $runtimeCache = "";
    //protected $rcTimeKey = "__CACHE_TIME__";
    //protected $rcSignKey = "__USE_CACHE__";
    //protected $rcExpired = 60*60;    //缓存更新的时间间隔，1h

    /**
     * 默认配置文件后缀名，默认 .json
     * !! 子类可覆盖
     * 可通过定义 XX_CONFEXT 形式的常量 来覆盖此参数
     */
    public static $confExt = ".json";
    /**
     * 支持的 配置文件后缀名
     * !! 子类不要覆盖
     */
    public static $confExts = [
        ".json", ".xml", ".yaml", ".yml",
    ];

    /**
     * 构造
     * @param Array $opt 输入的设置参数
     * @return void
     */
    public function __construct($opt = [])
    {
        //保存用户设置原始值
        $this->opt = Arr::extend($this->opt, $opt);

        //合并 用户设置 与 默认参数，保存到 context
        $ctx = $this->context;
        if (empty($ctx)) $ctx = Arr::copy($this->init);
        $ctx = Arr::extend($ctx, $opt);

        //处理设置值，支持格式：String, IndexedArray, Numeric, Bool, null,
        $this->context = $this->fixConfVal($ctx);

        //处理这些参数
        return $this->processConf();
    }

    /**
     * 在 应用用户设置后 执行 自定义的处理方法
     * !! 子类可覆盖
     * @return $this
     */
    public function processConf()
    {
        //子类可自定义方法
        //...

        return $this;
    }

    /**
     * getContext 获取 context 数据
     * 指定 $data 的值，则变为 setContext 运行时修改 context 数据
     * @param String $key context 字段 或 字段 path： 
     *      foo | foo/bar  -->  context["foo"] | context["foo"]["bar"]
     * @param Mixed $data 可以指定新值，覆盖旧的设置值，默认 __empty__ 标识未指定
     * @return Mixed 
     *      不指定 $data 则返回找到的 数据，未找到则返回 null
     *      指定了 $data 则尝试修改 context 返回是否修改成功的 Bool 值
     */
    public function ctx($key = "", $data="__empty__")
    {
        //确认是否指定了需要覆盖的新设置值
        $nconf = $data!=="__empty__";
        //原设置数组
        $conf = $this->context;

        if (!Is::nemstr($key)) {
            //指定的设置项路径不是非空字符串
            if ($key=="") {
                //设置项路径为 空字符串
                if ($nconf) {
                    //覆盖原设置值
                    $conf = Arr::find($conf, $key, $data);
                    //修改失败
                    if (is_null($conf)) return false;
                    //修改成功，写回 context
                    $this->context = $conf;
                    return true;
                }
                //返回完整的设置值
                return $this->context;
            }
            return null;
        }

        if (isset($conf[$key])) {
            //直接指定了某个设置项 键名
            if ($nconf) {
                //覆盖原设置值
                $kconf = Arr::extend($conf[$key], $data);
                //修改失败
                if (is_null($kconf)) return false;
                //修改成功，写回 context
                $this->context[$key] = $kconf;
                return true;
            }
            return $conf[$key];
        }

        if ($nconf) {
            //覆盖原设置值
            $conf = Arr::find($conf, $key, $data);
            //修改失败
            if (is_null($conf)) return false;
            //修改成功，写回 context
            $this->context = $conf;
            //返回完整设置值
            return true;
        }

        //使用 Arr::find 方法，查找目标设置值，未找到返回 null
        return Arr::find($conf, $key);
    }

    /**
     * __get 访问 context
     * @param String $key
     * @return Mixed
     */
    public function __get($key)
    {
        /**
         * $this->ctx  --> $this->context
         */
        if ($key=="ctx") return $this->context;

        /**
         * $this->field  -->  $this->context[field]
         */
        if (isset($this->context[$key])) {
            $ctx = $this->context[$key];
            //if (Is::associate($ctx)) return (object)$ctx;
            return $ctx;
        }

        /**
         * $this->_field  -->  $this->init[field]
         */
        if (substr($key, 0, 1)==="_" && isset($this->init[substr($key, 1)])) {
            $k = substr($key, 1);
            return $this->init[$k];
        }

        /**
         * $this->origin  -->  $this->opt
         * 访问传入的用户自定义参数
         * 即 此类的构造函数的参数
         * 保存在 $this->opt 中
         */
        if ($key == "origin") {
            if (!is_null($this->opt)) return $this->opt;
        }

        return null;
    }

    /**
     * 处理设置值
     * 设置值支持格式：String, IndexedArray, Numeric, Bool, null
     * @param Mixed $val 要处理的设置值
     * @return Mixed 处理后的设置值，不支持的格式 返回 null
     */
    public function fixConfVal($val = null)
    {
        if (Is::associate($val)) {
            $vn = [];
            foreach ($val as $k => $v) {
                $vn[$k] = $this->fixConfVal($v);
            }
            return $vn;
        }

        if (Is::ntf($val)) {
            //"null true false"
            eval("\$val = ".$val.";");
        } else if (is_numeric($val)) {
            $val = $val*1;
        } else if (Is::nemstr($val)) {
            if ("," == substr($val, 0,1) || false !== strpos($val, ",")) {
                //首字符为 , 或 包含字符 , 表示是一个 array
                $val = trim(trim($val), ",");
                $val = preg_replace("/\s+,/", ",", $val);
                $val = preg_replace("/,\s+/", ",", $val);
                $val = explode(",", $val);
                $val = array_map(function($i) {
                    return trim($i);
                }, $val);
            }
        } else if ($val=="" || is_bool($val) || Is::indexed($val)) {
            //$val = $val;
        } else {
            $val = null;
        }

        return $val;
    }



    /**
     * static tools
     */

    /**
     * 定义常量 递归某个数组，按层级 定义为 FOO_BAR_... 形式 常量
     * @param Array $defs
     * @param String $pre 常量前缀
     * @return Array
     */
    public static function def($defs = [], $pre="")
    {
        $pre = ($pre=="" || !Is::nemstr($pre)) ? "" : strtoupper($pre)."_";
        //所有定义的 常量，一维数组
        $cnsts = [];
        foreach ($defs as $k => $v) {
            $k = $pre.strtoupper($k);
            //$ln = count(explode("_",$k));
            if (Is::nemarr($v) && Is::associate($v)) {
                $cnsts = array_merge($cnsts, self::def($v, $k));
            } else {
                if (!defined($k)) {
                    $cnsts[$k] = $v;
                    define($k, $v);
                }
            }
        }
        return $cnsts;
    }

    /**
     * 自动补全配置文件后缀名
     * @param String $path 配置文件路径
     * @param String $for 此配置文件的用途，db|app|cache... 相应的需要定义 CONFEXT_[DB|APP|CACHE...] 形式的常量，来覆盖默认后缀名
     * @return String 补全后的文件路径
     */
    protected static function autoSuffix($path, $for="db")
    {
        if (!Is::nemstr($path)) return $path;
        //获取默认后缀名
        $cnst = "CONFEXT_".strtoupper($for);
        $ext = defined($cnst) ? constant($cnst) : static::$confExt;
        //支持的配置文件后缀名
        $exts = defined("CONFEXT_SUPPORT") ? Arr::mk(CONFEXT_SUPPORT) : static::$confExts;
        //自动补全
        if (strpos($path, ".")===false) return $path.$ext;
        //获取当前路径的后缀名
        $pi = pathinfo($path);
        $cext = ".".$pi["extension"];
        //如果已有 被支持的后缀名，不补全，直接返回
        if (in_array(strtolower($cext), $exts)) return $path;
        //否则 补全并返回
        return $path.$ext;
    }
    
}