<?php
/**
 * 框架参数配置工具类 基类
 */

namespace Spf\config;

use Spf\Core;
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

    /**
     * 可在多个配置类中通用的 设置参数默认值
     * 如果设定了此值，则 $init 属性需要合并(覆盖)到此数组
     * !! 如果需要，可以在某个配置类基类中定义此数组，然后在配置类子类中部分定义 $init 数组，即可实现 设置参数的继承和子类覆盖
     */
    protected $dftInit = [];

    //用户设置 需要覆盖 init 参数
    protected $opt = [];

    //经过处理后的 运行时参数
    protected $context = [];

    //关联的 类实例，通常为 核心类实例
    public $coreIns = null;

    /**
     * 构造
     * @param Array $opt 输入的设置参数
     * @param Core $ins 关联到此配置类的 实例，即 $ins->config === $this
     * @return void
     */
    public function __construct($opt=[], $ins=null)
    {
        //缓存 类实例
        if (!is_null($ins) && is_object($ins)) {
            $this->coreIns = $ins;
        }

        //处理外部传入的 用户设置
        $opt = $this->fixOpt($opt);

        //保存用户设置原始值
        $this->opt = Arr::extend($this->opt, $opt);

        /**
         * 合并参数，覆盖方向： $opt --> $init --> $dftInit
         * !! 子类可覆盖这个 合并方法
         */
        $this->extendConf();

        //处理设置值，支持格式：String, IndexedArray, Numeric, Bool, null,
        $this->context = $this->fixConfVal($this->context);

        //处理这些参数
        return $this->processConf();
    }

    /**
     * 在初始化时，处理外部传入的 用户设置，例如：提取需要的部分，过滤 等
     * !! 子类可覆盖此方法
     * @param Array $opt 外部传入的 用户设置内容
     * @return Array 处理后的 用户设置内容
     */
    protected function fixOpt($opt=[])
    {
        if (!Is::nemarr($opt)) return [];

        //根据关联的 核心类实例的 类型，从 $opt 中选取对应的 配置参数
        $ins = $this->coreIns;
        if ($ins instanceof Core) {
            //获取 核心类的 类型  app|module|env|request|response ...
            $type = $ins::is();
            //核心类 类名 路径形式 foo_bar
            $clsk = $ins::clsk();
            if (Is::nemstr($type)) {
                if ($type===$clsk) {
                    //env|request|response ... 类型 
                    return $opt[$type] ?? [];
                } else {
                    //app|module|middleware ... 拥有子类的 核心类型
                    //依次查找
                    $conf = Arr::find($opt, "$type/$clsk");
                    if (!Is::nemarr($conf)) $conf = Arr::find($opt, $clsk);
                    if (!Is::nemarr($conf)) $conf = $opt;
                    return $conf;
                }
            }
        }

        return $opt;
    }

    /**
     * 定义 配置参数 合并方法 默认使用 Arr::extend 覆盖方向： $opt --> $init --> $dftInit
     * !! 子类可覆盖
     * @return $this
     */
    public function extendConf()
    {

        //预定义设置参数，合并默认设置
        if (Is::nemarr($this->dftInit)) {
            $this->init = Arr::extend($this->dftInit, $this->init);
        }

        //合并 用户设置 与 预定义参数，保存到 context
        $this->context = Arr::extend($this->init, $this->opt);

        return $this;
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
     * 判断给定的参数 是否在 init 或 dftInit 中定义了 默认值
     * @param String $key
     * @return Bool
     */
    public function defInDft($key)
    {
        return isset($this->init[$key]);
    }

    /**
     * 运行时修改 init 默认值，相当于 在 config 配置类实例化以后，修改 init 参数，然后再次执行 extendConf
     * @param Array $ctx
     * @return $this
     */
    public function runtimeSetInit($ctx=[])
    {
        if (!Is::nemarr($ctx)) return $this;
        //传入的 opt 用户设置
        $opt = $this->opt;
        if (!Is::nemarr($opt)) $opt = [];
        //默认参数
        $init = $this->init;
        if (!Is::nemarr($init)) $init = [];
        //重新 extend 参数
        $ctx = Arr::extend($init, $ctx, $opt);
        //更新 context
        $this->context = $ctx;

        return $this;
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
         * $this->origin  -->  $this->opt
         * 访问传入的用户自定义参数
         * 即 此类的构造函数的参数
         * 保存在 $this->opt 中
         */
        if ($key == "origin") {
            if (!is_null($this->opt)) return $this->opt;
        }

        /**
         * $this->_field  -->  $this->init[field]
         */
        if (substr($key, 0, 1)==="_" && isset($this->init[substr($key, 1)])) {
            $k = substr($key, 1);
            return $this->init[$k];
        }

        /**
         * $this->field  -->  $this->context[field]
         */
        if (isset($this->context[$key])) {
            $ctx = $this->context[$key];
            //if (Is::associate($ctx)) return (object)$ctx;
            return $ctx;
        }

        /**
         * $this->fooBarJaz  -->  $this->ctx("foo/bar/jaz")
         */
        $snake = Str::snake($key, "_");
        if (strpos($snake, "_")!==false) {
            $snake = str_replace("_","/",$snake);
            return $this->ctx($snake);
        }

        return null;
    }

    /**
     * 处理设置值
     * 设置值支持格式：String, IndexedArray, Numeric, Bool, null
     * @param Mixed $val 要处理的设置值
     * @param Closure $callback 对设置值进行自定义处理的方法，参数为 原始设置值，返回处理后的设置值
     * @return Mixed 处理后的设置值，不支持的格式 返回 null
     */
    public function fixConfVal($val = null, $callback = null)
    {
        if (Is::associate($val)) {
            $vn = [];
            foreach ($val as $k => $v) {
                $vn[$k] = $this->fixConfVal($v, $callback);
            }
            return $vn;
        }

        //如果 指定了 处理函数，直接调用处理函数
        if ($callback instanceof \Closure) {
            return $callback($val);
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