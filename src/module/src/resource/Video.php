<?php
/**
 * 框架 Src 资源处理模块
 * 视频资源 流式输出
 * 支持的 视频格式 在 Stream::$support 中定义：mp4|m4v|mov
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\Stream;

class Video extends Resource
{
    /**
     * 资源输出的最后一步，echo
     * !! 覆盖父类
     * @param String $content 可单独指定最终输出的内容，不指定则使用 $this->content
     * @return Resource $this
     */
    protected function echoContent($content=null)
    {
        Stream::play($this->real);
        exit;
    }
}