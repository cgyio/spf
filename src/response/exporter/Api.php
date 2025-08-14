<?php
/**
 * Response 响应输出类
 * 响应类型 api
 * 输出 json 数据
 */

namespace Spf\response\exporter;

use Spf\response\Exporter;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;

class Api extends Exporter 
{
    /**
     * 当前响应类型的 Content-Type 
     * !! 覆盖父类
     * Api 类型的响应数据 为 json 格式数据
     */
    public $contentType = "application/json; charset=utf-8";

    /**
     * 当前响应类型的 $response->data 的 数据结构
     * api 类型的 数据结构
     * !! 子类必须覆盖
     */
    public $stdData = [
        //如果是 异常输出，此处标记
        "error" => false,
        //输出的 api 返回数据内容，如果是异常输出，此处保存 错误信息 code|msg|file|line 
        "data" => [],
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
        //准备数据
        $this->prepare();

        //content 内容转为 json
        $cnt = Conv::a2j($this->content);

        //如果 响应状态码 不是 200
        if ($this->statusError === true) {
            http_response_code($this->response->status->code);
        }

        //响应头
        $this->response->header->sent();

        //echo
        echo $cnt;

        exit;
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
                "error" => true,
                "data" => $data
            ];
            if ($this->statusError !== true) {
                //将状态码设为 500
                $this->response->setCode(500);
                $this->statusError = true;
            }
        } else {
            $data = [
                "data" => $data
            ];
        }
        //写入 content
         $this->content = Arr::extend($this->stdData, $data);
        return $this;
    }

    

    public function exportException()
    {
        $exception = $this->response->exceptions[0];
        $excepinfo = $exception->getInfo();
    }
}