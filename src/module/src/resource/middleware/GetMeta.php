<?php
/**
 * Resource 资源处理中间件
 * GetMeta 所有类型资源的 meta 元数据获取方法 默认方法
 * 不同类型的 Resource 子类可定义不同 meta 元数据 获取中间件，例如：GetVueMeta
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\ResourceMiddleware;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Curl;
use Spf\util\Conv;

class GetMeta extends ResourceMiddleware 
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
        $real = $res->real;

        if ($res->sourceType === "local") {
            if (file_exists($real)) {
                $fi = stat($real);
                $fi["sizeStr"] = Conv::sizeToStr($fi["size"]);
                //写入 meta
                $this->resource->meta = array_merge($res->meta, $fi);
            }
        }

        return true;
    }
}