<?php
/**
 * Response 响应管理类
 * 输出相应数据 工具类
 * 
 * 所有最终输出的 响应数据，都应通过此类执行
 * 根据 不同的 响应类型，建立对应的 Exporter 类实例
 */

namespace Spf\response;

use Spf\Response;
use Spf\exception\BaseException;
use Spf\exception\AppException;
use Spf\Env;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Exporter 
{
    /**
     * 关联的 响应实例
     */
    public $response = null;

    /**
     * 当前响应类型的 Content-Type 
     * !! 子类必须覆盖
     */
    public $contentType = "text/html; charset=utf-8";

    /**
     * 当前响应类型的 $response->data 的 数据结构
     * !! 子类必须覆盖
     */
    public $stdData = [];



    /**
     * 构造
     * @param Response $response 响应实例
     * @return void
     */
    public function __construct($response)
    {
        if (!$response instanceof Response) {
            throw new AppException("响应输出实例缺少参数", "response/fail");
        }

        $this->response = $response;
    }

    /**
     * 为 Response 响应实例 提供各响应类型的 setData 方法
     * !! 子类必须覆盖此方法
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
     * export 输出方法
     */

    /**
     * WEB_PAUSE == true 中断响应，输出数据
     * !! 子类可覆盖遮盖此方法
     * @return exit
     */
    public function exportPause()
    {

        exit;
    }

    /**
     * 响应状态码 !== 200 输出数据
     * !! 子类可覆盖遮盖此方法
     * @return exit
     */
    public function exportCode()
    {

        exit;
    }

    /**
     * 当前响应包含 必须输出的 异常信息
     * !! 子类可覆盖遮盖此方法
     * @param BaseException $ecp 异常实例
     * @return exit
     */
    public function exportException($ecp)
    {

        exit;
    }

    /**
     * 核心方法 输出响应数据，不同的响应类型，使用不同的输出方法
     * !! 子类应根据需要，覆盖此方法
     * @return exit
     */
    public function export()
    {

        exit;
    }

    /**
     * echo 步骤，将要输出的内容 echo 到响应体
     * !! 子类应根据需要，覆盖此方法
     * @param Mixed $eData 可以是 json | html | Resource实例
     * @param Int $code 响应状态码 默认 200
     * @param Mixed $oData 转换前的数据 默认为 $this->response->data
     * @return exit
     */
    protected function eko($eData, $code=200, $oData=null)
    {
        //原始数据
        if (is_null($oData)) $oData = $this->response->data;

        //根据 影响最终输出的 开关数据，决定怎样输出
        $sw = $this->response->switch;

        //开发环境确认
        $forDev = Env::$current->dev === true;

        //输出 ?dump=yes  需要 开发环境
        if ($forDev && $sw->dump) {
            var_dump($oData);
            exit;
        }

        //TODO: 响应其他 开关的 输出方式
        //...

        //正常输出
        $this->setContentType();
        //输出 响应状态码
        if ($code!==200) http_response_code($code);
        $this->response->header->sent();
        echo $eData;
        exit;
    }



    /**
     * 工具方法
     */

    /**
     * 动态修改 响应的 Content-Type
     * @param String $contentType 指定新的 Content-Type 默认 null 使用 $this->contentType
     * @return Bool
     */
    public function setContentType($contentType=null)
    {
        if (!Is::nemstr($contentType)) {
            $contentType = $this->contentType;
        } else {
            //更新当前 Exporter 实例的 contentType 属性
            $this->contentType = $contentType;
        }

        //调用 ResponseHeader 实例的 ctx 方法
        return $this->response->header->ctx("Content-Type", $contentType);
    }
}