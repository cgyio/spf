<?php
/**
 * Resource 资源处理中间件
 * UpdateParams 在资源输出阶段 更新资源的输出参数 params
 */

namespace Spf\module\src\resource\middleware;

use Spf\Request;
use Spf\module\src\ResourceMiddleware;
use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class UpdateParams extends ResourceMiddleware 
{
    /**
     * 此中间件的核心处理方法
     * 处理并修改 资源实例数据
     * !! 覆盖父类
     * @return Bool 
     */
    public function handle()
    {
        $res = $this->resource;
        //新的 params 
        $nps = $this->params;
        if (!Is::nemarr($nps)) return true;
        
        //资源实例 当前的 params
        $cps = $res->params;
        //资源实例的 标准 params 结构，需要合并继承链上的所有父类的 stdParams
        $std = static::mergeParentClassStdParams($res);     //$res::$stdParams;
        //合并
        $nnps = Arr::intersect($nps, $std);
        $this->resource->params = Arr::extend($std, $cps, $nnps);

        //针对 可能存在的 export 参数
        if (isset($std["export"])) {
            $eext = $this->resource->params["export"] ?? null;
            if (Is::nemstr($eext) && Mime::support($eext) && $eext !== $this->resource->ext) {
                //修改 资源实例的 输出 ext
                $this->resource->setExt($eext);
                //$this->resource->ext = $eext;
                //$this->resource->mime = Mime::getMime($eext);
            }
        }

        return true;
    }



    /**
     * 静态工具
     */

    /**
     * 合并继承链上所有父类的 stdParams 标准参数数组
     * @param String|Object $res 资源类全称 或 资源实例
     * @return Array 合并后的 stdParams
     */
    public static function mergeParentClassStdParams($res)
    {
        //传入的 资源类的 继承链 [ 当前类, 父类, 祖父类, 曾祖父类, ... ]
        $chain = Cls::inheritanceChain($res);
        if (!Is::nemarr($chain)) return [];
        //翻转 [ ..., 曾祖父类, 祖父类, 父类, 当前类]
        $chain = array_reverse($chain);
        //构造各类的 stdParams 数组 为 二维数组 [ [祖父类 stdParams], [父类 stdParams], [当前类 stdParams] ]
        $stdChain = array_map(function($clsi) {
            $stdi = $clsi::$stdParams;
            if (!isset($stdi) || !Is::nemarr($stdi)) $stdi = [];
            return $stdi;
        }, $chain);

        //使用 Arr::extend([],[],..., true) 依次合并各 stdParams 
        //最后的 true 表示针对子项为 indexed 数组时，使用新的覆盖旧的，而不是 合并去重
        $stdChain[] = true;
        $std = Arr::extend(...$stdChain);
        if (!Is::nemarr($std)) return [];
        //将最终合并后的 stdParams 写入 当前类的 stdParams
        $res::$stdParams = $std;
        return $std;
    }

    /**
     * 合并 $_GET 参数到资源 params
     * @param Array $params 当前资源参数
     * @return Array 合并后的资源参数
     */
    public static function mergeGetParams($params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        //是否忽略 $_GET
        $ignore = $params["ignoreGet"] ?? false;
        if (!is_bool($ignore)) $ignore = false;
        $params["ignoreGet"] = $ignore;

        if ($ignore === true) return $params;

        //合并 $_GET
        $gets = Request::$current->gets->ctx();
        $params = Arr::extend($params, $gets);

        return $params;
    }
}