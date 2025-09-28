<?php
/**
 * Resource 资源处理中间件
 * GetJsonContent 读取 json 类型资源的 content 
 * 并将获取的数据转为 array 类型数据 保存到 当前资源的 jsonData 参数中
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Conv;

class GetJsonContent extends GetContent 
{
    /**
     * 此中间件的核心处理方法
     * 处理并修改 资源实例数据
     * !! 覆盖父类
     * @return Bool 
     */
    public function handle()
    {
        //调用父类 GetContent::handle 方法，读取 content
        parent::handle();

        //转换 获取到的 json 为 array
        $jd = Conv::j2a($this->resource->content);
        if (!Is::nemarr($jd)) {
            //json 数据转换失败
            throw new SrcException("无法转换获取到的 JSON 数据", "resource/getcontent");
        }
        $this->resource->jsonData = $jd;

        return true;
    }
}