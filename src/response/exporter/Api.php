<?php
/**
 * Response 响应输出类
 * 响应类型 api
 * 输出 json 数据
 */

namespace Spf\response\exporter;

use Spf\response\Exporter;
use Spf\exception\BaseException;
use Spf\Env;
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
     * !! 覆盖父类
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
        $pd = [
            "error" => false,
            "data" => [
                "pause" => true
            ]
        ];
        $ed = Conv::a2j($pd);

        //eko
        $this->eko($ed, 200, $pd);
        
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
        $pd = [
            "error" => false,
            "data" => [
                "code" => $stu->code,
                "info" => $stu->info
            ]
        ];
        $ed = Conv::a2j($pd);

        //eko
        $this->eko($ed, $stu->code, $pd);
        
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
        $einfo = $ecp->getInfo();
        
        $pd = [
            "error" => true,
            "data" => $einfo
        ];
        $ed = Conv::a2j($pd);
        $code = $ecp->isInnerException()===true ? 500 : 200;

        $ec = $ecp->ctx("code_no_pre");

        //eko
        $this->eko($ed, $code, $pd);
        
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
        //包裹结构
        if ($this->response->wrap) {
            $ed = Arr::extend($this->stdData, [
                "data" => $rd
            ]);
        } else {
            $ed = $rd;
        }

        //eko
        $this->eko(Conv::a2j($ed));

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
        /**
         * 针对 输出 json 格式内容
         * !! 从缓冲区收集 var_dump 内容，并添加到输出数据中
         */
        if (!Is::json($eData) || ob_get_level()<=0) return parent::eko($eData, $code, $oData);

        $ed = Conv::j2a($eData);
        if (!(is_array($ed) && (empty($ed) || Is::associate($ed)))) return parent::eko($eData, $code, $oData);

        //!! 从缓冲区收集 var_dump 内容
        $dump = ob_get_contents();
        //var_dump 存入输出结构
        if (!empty($dump)) $ed["dump"] = $dump;
        //清空缓冲区
        ob_clean();

        //恢复 json 数据
        $eData = Conv::a2j($ed);
        
        return parent::eko($eData, $code, $oData);
    }
}