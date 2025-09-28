<?php
/**
 * Resource 资源处理中间件
 * UpdateParams 在资源输出阶段 更新资源的输出参数 params
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\ResourceMiddleware;
use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
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
        //资源实例的 标准 params 结构
        $std = $res::$stdParams;
        //合并
        $nnps = Arr::intersect($nps, $std);
        $this->resource->params = Arr::extend($std, $cps, $nnps);

        //针对 可能存在的 export 参数
        if (isset($std["export"])) {
            $eext = $this->resource->params["export"] ?? null;
            if (Is::nemstr($eext) && Mime::support($eext) && $eext !== $this->resource->ext) {
                //修改 资源实例的 输出 ext
                $this->resource->ext = $eext;
                $this->resource->mime = Mime::getMime($eext);
            }
        }

        return true;
    }
}