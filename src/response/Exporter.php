<?php
/**
 * Response 响应管理类
 * 输出相应数据 工具类
 * 
 * 所有最终输出的 响应数据，都应通过此类执行
 * 根据 不同的 响应类型，建立对应的 Exporter 类实例
 * 响应类型 在 util/Operation::$types 中定义
 */

namespace Spf\response;

use Spf\Response;
use Spf\exception\BaseException;
use Spf\exception\AppException;
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

    //最终输出的内容 与 stdData 格式一致
    protected $content = [];

    //是否输出异常 标记
    protected $exportException = false;

    //响应状态码 是否异常
    protected $statusError = false;



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
     * 输出前 准备要输出的数据
     * !! 子类可覆盖这个方法
     * @return $this
     */
    protected function prepare()
    {
        //从 response 实例中读取 data 合并到 content 中
        $data = $this->response->data;
        //查找 exception 异常列表，获取必须输出的 异常实例
        if (false !== ($expt = $this->needExportException())) {
            //用 异常数据 替换 响应数据
            $data = $expt->getInfo();
            $this->exportException = true;
        } else if ($this->response->status->code !== 200) {
            //响应状态码 不是 200
            $data = [
                "msg" => $this->response->status->info,
            ];
            $this->statusError = true;
        }

        //将数据 写入 content
        return $this->setContent($data);
    }

    /**
     * 核心方法 输出响应数据，不同的响应类型，使用不同的输出方法
     * !! 子类应根据需要，覆盖此方法
     * @return void
     */
    public function export()
    {

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
     * 将要输出的 数据写入 content
     * !! 子类必须覆盖此方法
     * @param Mixed $data
     * @return $this
     */
    protected function setContent($data=[])
    {

        return $this;
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
            $contentType = $this->ContentType;
        } else {
            //更新当前 Exporter 实例的 contentType 属性
            $this->contentType = $contentType;
        }

        //调用 ResponseHeader 实例的 ctx 方法
        return $this->response->header->ctx("Content-Type", $contentType);
    }

    /**
     * 查找当前响应实例中的 exceptions 异常列表
     * 判断是否需要 输出异常信息，还是继续执行响应输出
     * @return BaseException|false 返回找到的 必须输出的 异常实例，未找到任何需要输出的异常时，返回 false
     */
    protected function needExportException()
    {
        $exceps = $this->response->exceptions;
        if (!Is::nemarr($exceps)) return false;
        //依次检查 异常列表
        foreach ($exceps as $excep) {
            if (!$excep instanceof BaseException) continue;
            if (true === $excep->needExit()) {
                //此异常需要输出
                return $excep;
            }
        }
        return false;
    }
}