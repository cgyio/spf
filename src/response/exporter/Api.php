<?php
/**
 * Response 响应输出类
 * 响应类型 api
 * 输出 json 数据
 */

namespace Spf\response\exporter;

use Spf\response\Exporter;
use Spf\exception\BaseException;
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



    /**
     * 为 Response 响应实例 提供各响应类型的 setData 方法
     * !! 覆盖父类
     * @param Mixed $data 要写入的 响应数据
     * @return Bool
     */
    public function setResponseData($data)
    {
        //api 响应类型 输出的数据 必须是 Array
        if (!Is::nemarr($data)) $data = [];
        if (!Is::nemarr($this->response->data)) {
            $this->response->data = [];
        }
        //合并
        $this->response->data = Arr::extend($this->response->data, $data);
        return true;
    }



    /**
     * export 输出方法
     */

    /**
     * WEB_PAUSE == true 中断响应，输出数据
     * !! 覆盖父类
     * @return exit
     */
    public function exportPause()
    {
        $pd = Conv::a2j([
            "error" => false,
            "data" => [
                "pause" => true
            ]
        ]);
        $this->setContentType();
        $this->response->header->sent();
        echo $pd;
        exit;
    }

    /**
     * 响应状态码 !== 200 输出数据
     * !! 覆盖父类
     * @return exit
     */
    public function exportCode()
    {
        $stu = $this->response->status;
        $pd = Conv::a2j([
            "error" => false,
            "data" => [
                "code" => $stu->code,
                "info" => $stu->info
            ]
        ]);
        $this->setContentType();
        //输出 响应状态码
        http_response_code($stu->code);
        $this->response->header->sent();
        echo $pd;
        exit;
    }

    /**
     * 当前响应包含 必须输出的 异常信息
     * !! 覆盖父类
     * @param BaseException $ecp 异常实例
     * @return exit
     */
    public function exportException($ecp)
    {
        if (!$ecp instanceof BaseException) exit;
        
        $pd = Conv::a2j([
            "error" => true,
            "data" => $ecp->getInfo()
        ]);
        $this->setContentType();
        //输出 响应状态码
        //if ($ecp->isInnerException()===true) http_response_code(500);
        $this->response->header->sent();
        echo $pd;
        exit;
    }

    /**
     * 核心方法 输出响应数据，不同的响应类型，使用不同的输出方法
     * !! 覆盖父类
     * @return exit
     */
    public function export()
    {
        //responseData
        $rd = $this->response->data;
        //格式化 输出的 数据
        $pd = Arr::extend($this->stdData, [
            "data" => $rd
        ]);
        //转为 json
        $pd = Conv::a2j($pd);
        //写入 Content-Type
        $this->setContentType();
        //响应头
        $this->response->header->sent();
        //echo
        echo $pd;

        exit;
    }
}