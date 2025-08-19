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
use Spf\Env;
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
            //将 响应状态码设为 404
            $code = 404;
        } else {
            //写入
            $this->response->data = $data;
            //将 响应状态码设为 200
            $code = 200;
        }
        //修改 响应状态码
        $this->response->setCode($code);
        //不论资源是否找到，都返回 true 确保上层不出错
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
        //直接调用 View Exporter 类的 exportPause 方法
        $exper = new View($this->response);
        $exper->exportPause();
        exit;


        //输出资源时，如果中断输出，响应状态码 改为 404
        $this->response->setCode(404);
        //http_response_code(404);

        //直接调用 View Exporter 类的 exportCode 方法
        $exper = new View($this->response);
        $exper->exportCode();
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

        //直接调用 View Exporter 类的 exportCode 方法
        $exper = new View($this->response);
        $exper->exportCode();
        //http_response_code($this->response->status->code);
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

        //直接调用 View Exporter 类的 exportException 方法
        $exper = new View($this->response);
        $exper->exportException($ecp);
        exit;
    }

    /**
     * 核心方法 输出响应数据，不同的响应类型，使用不同的输出方法
     * !! 覆盖父类
     * @return exit
     */
    public function export()
    {
        //eko
        $this->eko($this->response->data);

        exit;
    }

    /**
     * echo 步骤，将要输出的内容 echo 到响应体
     * !! 覆盖父类
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

        //输出 ?dump=yes
        if ($forDev && $sw->dump) {
            var_dump($oData);
            exit;
        }

        //正常输出
        //如果 不包含 Resource 资源实例
        if (!$eData instanceof Resource) {
            //404
            http_response_code(404);
            exit;
        }
        //调用资源实例的 输出方法
        $eData->export();
        exit;
    }
}