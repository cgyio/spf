<?php
/**
 * 框架 Src 资源处理模块
 * 不支持直接输出的资源格式 使用下载方式输出
 * mime = application/octet-stream
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\Stream;

class Download extends Resource
{
    /**
     * 资源输出的最后一步，echo
     * !! 覆盖父类
     * @param String $content 可单独指定最终输出的内容，不指定则使用 $this->content
     * @return Resource $this
     */
    protected function echoContent($content=null)
    {
        $stream = Stream::create($this->real);
        $stream->startDownload();
        
        return $this;
    }
}