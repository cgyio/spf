<?php
/**
 * SPF 框架 可复用类特征
 * 为所有 拥有 context[] 运行时上下文属性的 类，增加 上下文 输出|修改|查询 功能
 * context[] 上下文必须是 associate 关联数组
 */

namespace Spf\traits;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;

trait ContextProvider 
{
    /**
     * 统一增加一个 backup 备份列表
     */
    protected $ctxBackup = [
        //[ 备份的 context ... ],
        //[ ... ],
        //...
    ];

    /**
     * 通用的 查询 context 数据 的方法 可以指定 默认值
     * @param String $key context 字段 或 字段 path： 
     *                    foo | foo/bar  -->  context["foo"] | context["foo"]["bar"]
     * @param Mixed $dft如果未查询到则返回此默认值
     * @return Mixed 
     */
    public function ctx($key = "", $dft=null)
    {
        if (!Is::nemstr($key)) return $this->context;
        $rtn = Arr::find($this->context, $key);
        if (is_null($rtn)) return $dft;
    }

    /**
     * 以 key val 键值形式修改 context
     * key 可以是键名路径 foo/bar/... 
     * @param String $key context 字段 或 字段 path： foo/bar/...
     * @param Mixed $data 要修改的新值
     * @param Bool $backup 是否修改前备份 默认 true
     * @return Mixed $this
     */
    public function setCtx($key, $data, $backup=true)
    {
        if (!Is::nemstr($key)) return $this;
        if ($backup) $this->backupCtx();
        $ctx = Arr::find($this->context, $key, $data);
        if (is_null($ctx)) return $this;
        $this->context = $ctx;
        return $this;
    }

    /**
     * 以 extend 方式修改 context
     * @param Array $data
     * @param Bool $backup 是否修改前备份 默认 true
     * @return Mixed $this
     */
    public function extendCtx($data=[], $backup=true)
    {
        if (!Is::nemarr($data)) return $this;
        if ($backup) $this->backupCtx();
        $this->context = Arr::extend($this->context, $data);
        return $this;
    }

    /**
     * 以 覆盖方式 修改 context
     * @param Array $new 新的 context 值
     * @param Bool $backup 是否修改前备份 默认 true
     * @return Mixed $this
     */
    public function replaceCtx($data=[], $backup=true)
    {
        if (!is_array($data)) return $this;
        if ($backup) $this->backupCtx();
        $this->context = Arr::copy($data);
        return $this;
    }

    /**
     * 备份当前 context
     * @return Mixed $this
     */
    public function backupCtx()
    {
        $this->ctxBackup[] = Arr::copy($this->context);
        return $this;
    }

    /**
     * 恢复备份的 context 可以指定备份版本
     * @param Int $ver 可以指定要恢复的备份版本，默认 1 恢复最新的备份，2 则恢复第二新的备份，类推...
     * @return Mixed $this
     */
    public function restoreCtx($ver=1)
    {
        if (!is_numeric($ver)) $ver = 1;
        $ver = abs($ver*1);
        
        $backup = $this->ctxBackup;
        if (!Is::nemidx($backup) || count($backup)<$ver) return $this;
        
        $ctx = array_slice($backup, $ver*-1)[0];
        if (!is_array($ctx)) return $this;

        $this->context = Arr::copy($ctx);
        return $this;
    }

    /**
     * 判断 context 中是否存在某个 键
     * @param String $key 键名 foo_bar 或 fooBar
     * @return String|false 自动转换 camel|snake 返回真实存在的 键名，不存在则返回 false
     */
    public function ctxHas($key)
    {
        if (!Is::nemstr($key)) return false;
        $k_sn = Str::snake($key, "_");  //foo_bar
        $k_cm = Str::camel($k_sn);      //fooBar
        $keys = array_merge([], array_keys($this->context));
        if (in_array($k_sn, $keys)) return $k_sn;
        if (in_array($k_cm, $keys)) return $k_cm;
        return false;
    }

    /**
     * 通用的 __get
     */
    public function __get($key)
    {
        /**
         * $this->ctx       --> $this->context
         */
        if ($key==="ctx") return $this->context;

        /**
         * 输入的键名必须存在
         * $this->foo       --> $this->context["foo"]
         * $this->fooBar    --> $this->context["fooBar"]
         * $this->foo_bar   --> $this->context["foo_bar"]
         */
        if (false!==($rk = $this->ctxHas($key))) return $this->context[$rk];

        return null;
    }

    /**
     * 通用的 __call
     */
    public function __call($key, $args)
    {
        /**
         * $this->foo("defaultVal")     --> $this->context["foo"] ?? defaultVal
         */
        $dft = empty($args) ? null : $args[0];
        if (false!==($rk = $this->ctxHas($key))) return $this->context[$rk] ?? $dft;

        return $dft;
    }

}