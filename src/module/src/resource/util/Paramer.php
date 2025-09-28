<?php
/**
 * Resource 资源处理类 工具类
 * 处理 任意资源的 输出参数
 */

namespace Spf\module\src\resource\util;

use Spf\Request;
use Spf\module\src\Resource;
use Spf\module\src\resource\Util;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Url;
use Spf\util\Path;

class Paramer extends Util 
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 任意资源类型的 stdParams 参数应在此基础上 扩展
     */
    protected static $stdParams = [
        
        //其他可选参数
        //...
    ];

    /**
     * 依赖的 当前资源实例
     */
    public $resource = null;

    /**
     * 构造
     * @param Resource $res
     * @return void
     */
    public function __construct($res)
    {
        if (!$res instanceof Resource) return null;
        $this->resource = $res;
    }

    /**
     * 格式化 params 需要 各类型资源类 定义自身的 stdParams
     * 将格式化后的 params 写回 $resource->params
     * @return $this
     */
    public function format()
    {
        $res = $this->resource;
        //标准 params 格式
        $stps = static::$stdParams;
        //当前类型资源的 标准 params 格式
        $rstps = $res::$stdParams;
        //当前 params
        $ps = $res->params;
        //格式化
        $ps = Arr::extend($stps, $rstps, $ps);
        //写回
        $this->resource->params = static::fixParams($ps);
        return $this;
    }

    /**
     * 编辑 合并 params 到当前资源实例
     * @param Array $params
     * @return $this
     */
    public function extend($params=[])
    {
        if (!Is::nemarr($params)) return $this;
        
        //首先处理一下 params 将 ntf|yes|no|foo,bar 形式的 字符串 转换为对应的 值
        $params = static::fixParams($params);
        //合并
        $this->resource->params = Arr::extend($this->resource->params, $params);

        return $this;
    }



    /**
     * 静态工具
     */

    /**
     * 从 $_GET 收集资源输出参数
     * @param Array $params 当前已有的 参数
     * @return Array 合并后的 资源输出参数
     */
    public static function getParamsFromGets($params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        $params = static::fixParams($params);

        //$_GET
        $gets = Request::$isInsed===true ? Request::$current->gets->ctx() : $_GET;
        $gets = static::fixParams($gets);

        //合并
        return Arr::extend($params, $gets);
    }

    /**
     * 处理 params 传入参数
     * 将 true|false|null|yes|no 转为 bool
     * 将 foo,bar 转为数组
     * @param Array $params
     * @return Array 处理后的
     */
    public static function fixParams($params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        $rtn = [];
        foreach ($params as $k => $v) {
            if (Is::nemstr($v) && (Is::ntf($v) || in_array(strtolower($v), ["yes","no"]))) {
                //将 true|false|null|yes|no 转为 bool
                if (Is::ntf($v)) {
                    eval("\$v = ".$v.";");
                    if ($v!==true) $v = false;
                } else {
                    $v = strtolower($v) === "yes";
                }
            } else if (Is::explodable($v) !== false) {
                // foo,bar  foo|bar  foo;bar ... 转为数组
                $v = Arr::mk($v);
            }
            $rtn[$k] = $v;
        }
        return $rtn;
    }
}