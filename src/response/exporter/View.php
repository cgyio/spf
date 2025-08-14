<?php
/**
 * Response 响应输出类
 * 响应类型 view
 * 输出 html
 */

namespace Spf\response\exporter;

use Spf\response\Exporter;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class View extends Exporter 
{
    /**
     * 当前响应类型的 Content-Type 
     * !! 覆盖父类
     * View 类型的响应数据 为 html 页面
     */
    public $contentType = "text/html; charset=utf-8";

    /**
     * 当前响应类型的 $response->data 的 数据结构
     * view 类型的 数据结构
     * !! 覆盖父类
     */
    public $stdData = [
        //view 视图类 类全称 或 Cls::find(...)
        "view" => "",
        //需要传入 view 视图类实例的 源数据
        "params" => [],
    ];

    //最终输出的内容 与 stdData 格式一致
    protected $content = [];



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
    protected function setContent($data=[])
    {
        //如果 输出异常
        if ($this->exportException === true || $this->statusError === true) {
            $data = [
                "view" => $this->exportException === true ? "view/exception" : "view/error_code",
                "params" => $data
            ];
            if ($this->statusError !== true) {
                //将状态码设为 500
                $this->response->setCode(500);
                $this->statusError = true;
            }
        }
        //写入 content
         $this->content = Arr::extend($this->stdData, $data);
        return $this;
    }
}