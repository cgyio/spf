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
     * 针对一些特殊的资源，例如 音频流|视频流 开启自定义的 getContent | export 方法
     * 是否开启自定义 io 方法
     * !! 有需要 自定义 io 方法的资源子类，覆盖这个属性
     */
    protected $customIO = true;

    /**
     * !! customIO == true 的特殊资源类型，可自定义 customExport 方法
     * @param Array $params 可以在输出资源时，额外指定参数，与 export 方法参数一致
     * @return void
     */
    protected function customExport($params=[])
    {
        $stream = Stream::create($this->real);
        $stream->startDownload();
        exit;
    }
}