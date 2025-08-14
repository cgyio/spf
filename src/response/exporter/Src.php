<?php
/**
 * Response 响应输出类
 * 响应类型 src
 * 输出 任意 Resource 资源实例
 */

namespace Spf\response\exporter;

use Spf\response\Exporter;
use Spf\module\src\Resource;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Src extends Exporter 
{
    /**
     * 当前响应类型的 Content-Type 
     * !! 覆盖父类
     * Src 类型的响应数据 为 Resource 资源类实例
     * 应根据 Resource 资源类型，动态修改 Content-Type
     */
    public $contentType = "application/octet-stream";

    /**
     * 当前响应类型的 $response->data 的 数据结构
     * src 类型的 数据结构 必须是有效的 Resource 资源类实例
     * !! 覆盖父类
     */
    public $stdData = null;

    //最终输出的内容 与 stdData 格式一致
    protected $content = null;



    /**
     * 核心方法 输出响应数据，不同的响应类型，使用不同的输出方法
     * !! 覆盖父类
     * @return void
     */
    public function export()
    {

    }

    /**
     * 为 Response 响应实例 提供各响应类型的 setData 方法
     * !! 覆盖父类
     * @param Mixed $data 要写入的 响应数据
     * @return Bool
     */
    public function setResponseData($data)
    {
        //子类必须覆盖

        $this->response->data = $data;
        return true;
    }

    /**
     * 将要输出的 数据写入 content
     * !! 覆盖父类
     * @param Mixed $data
     * @return $this
     */
    protected function setContent($data)
    {
        //如果 输出异常 或者 
        if ($this->exportException === true || $data) {
            //设置 响应状态码为 404
            $this->response->setCode(500);
            $this->content = $data;
            $this->statusError = true;
        } else if ($this->statusError === true){
            //响应状态码 错误
            $this->content = $data;
        } else if (!$data instanceof Resource) {
            //要输出的数据 不是 Resource 资源实例
            $this->response->setCode(404);
            $this->content = null;
            $this->statusError = true;
        } else {
            //保存要输出的 资源实例
            $this->content = $data;
        }
        return $this;
    }
}