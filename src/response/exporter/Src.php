<?php
/**
 * Response 响应输出类
 * 响应类型 src
 * 输出 任意 Resource 资源实例
 */

namespace Spf\response\exporter;

use Spf\response\Exporter;
use Spf\exception\BaseException;
use Spf\module\src\Resource;
use Spf\View as View;
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



    /**
     * 为 Response 响应实例 提供各响应类型的 setData 方法
     * !! 覆盖父类
     * @param Mixed $data 要写入的 响应数据
     * @return Bool
     */
    public function setResponseData($data)
    {
        //src 响应类型 输出的数据 必须是 有效的 Resource 资源类实例
        if (!$data instanceof Resource) {
            $this->response->data = null;
            return false;
        }
        //写入
        $this->response->data = $data;
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
        //输出资源时，如果中断输出，直接返回 404
        http_response_code(404);
        exit;
    }

    /**
     * 响应状态码 !== 200 输出数据
     * !! 覆盖父类
     * @return exit
     */
    public function exportCode()
    {
        //输出 响应状态码
        http_response_code($this->response->status->code);
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
        //输出资源时，发生异常，则转为输出 view 视图
        if (!$ecp instanceof BaseException) exit;
        //从 Response 响应配置类中 获取对应的 默认 视图文件
        $view = $this->response->config->ctx("view/exception");
        $html = View::page(
            $view,
            $ecp->getInfo()
        );
        $this->setContentType("text/html; charset=utf-8");
        //输出 响应状态码
        if ($ecp->isInnerException()===true) http_response_code(500);
        $this->response->header->sent();
        echo $html;
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
        //如果 不包含 Resource 资源实例
        if (!$rd instanceof Resource) {
            //404
            http_response_code(404);
            exit;
        }
        //调用资源实例的 输出方法
        $rd->export();

        exit;
    }
}