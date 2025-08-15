<?php
/**
 * Response 响应输出类
 * 响应类型 view
 * 输出 html
 */

namespace Spf\response\exporter;

use Spf\response\Exporter;
use Spf\exception\BaseException;
use Spf\View as cView;
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
        //view 视图类 类全称 或 Cls::find(...) 或 输出页面路径
        "view" => null,
        //需要传入 view 视图类实例 或 输出页面 的 源数据
        "params" => [],
        //也可以直接传入 html
        "html" => null,
    ];



    /**
     * 为 Response 响应实例 提供各响应类型的 setData 方法
     * !! 覆盖父类
     * @param Mixed $data 要写入的 响应数据 可以是： view 类路径 | html | params 数组 | stdData 格式数组
     * @return Bool
     */
    public function setResponseData($data)
    {
        //view 响应类型 输出数据 必须包含 view 类全称 或 输出页面文件路径
        if (!Is::nemstr($data) && !Is::nemarr($data)) return false;
        if (!Is::nemarr($this->response->data)) {
            //标准输出数据格式
            $this->response->data = Arr::copy($this->stdData);
        }
        $rep = $this->response;

        //根据 要写入的数据 的类型 决定写入方法
        if (Is::nemstr($data)) {
            //写入字符串 类型数据，必须是 view 视图类名 | 输出页面文件路径 | html
            if (substr($data, 0, 5)==="view/") {
                //以 view/ 开头的 字符串
                $viewcls = Cls::find($data);
                if (class_exists($viewcls)) {
                    //找到 view 视图类全称
                    $rep->data["view"] = $viewcls;
                    $rep->data["html"] = null;
                    return true;
                }
            } else if (substr($data, strlen(EXT_CLASS)*-1) === EXT_CLASS) {
                //以 EXT_CLASS .php 结尾的 字符串
                $page = Path::find($data, Path::FIND_FILE);
                if (file_exists($page)) {
                    //找到 输出页面文件
                    $rep->data["view"] = $page;
                    $rep->data["html"] = null;
                    return true;
                }
            } else if (Is::html($data)) {
                //直接传入 html
                //!! 只能一次性传入所有 html，无法追加
                $rep->data["view"] = null;
                $rep->data["html"] = $data;
                return true;
            }
            return false;
        }

        if (Is::nemarr($data)) {
            //传入的 数组中包含 view 信息
            if (isset($data["view"]) && Is::nemstr($data["view"])) {
                $this->setResponseData($data["view"]);
                unset($data["view"]);
            }
            //传入的 数组中包含 html
            if (isset($data["html"]) && Is::nemstr($data["html"])) {
                $this->setResponseData($data["html"]);
                unset($data["html"]);
            }
            //传入的数组中包含 params
            if (isset($data["params"]) && Is::nemarr($data["params"])) {
                $params = $data["params"];
                unset($data["params"]);
                $data = Arr::extend($data, $params);
            }

            //传入数组，将作为 view 视图实例  或  输出页面  的源数据
            if (!Is::nemarr($rep->data["params"])) $rep->data["params"] = [];
            $rep->data["params"] = Arr::extend($rep->data["params"], $data);
            return true;
        }

        return false;
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
        //从 Response 响应配置类中 获取对应的 默认 视图文件
        $view = $this->response->config->ctx("view/pause");
        $html = cView::page(
            $view,
            [
                "pause_msg" => "Web Paused!"
            ]
        );
        $this->setContentType();
        $this->response->header->sent();
        echo $html;
        exit;
    }

    /**
     * 响应状态码 !== 200 输出数据
     * !! 覆盖父类
     * @return exit
     */
    public function exportCode()
    {
        //从 Response 响应配置类中 获取对应的 默认 视图文件
        $view = $this->response->config->ctx("view/code");
        $stu = $this->response->status;
        $html = cView::page(
            $view,
            [
                "code" => $stu->code,
                "info" => $stu->info
            ]
        );
        $this->setContentType();
        //输出 响应状态码
        http_response_code($stu->code);
        $this->response->header->sent();
        echo $html;
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
        //从 Response 响应配置类中 获取对应的 默认 视图文件
        $view = $this->response->config->ctx("view/exception");
        if (!$ecp instanceof BaseException) exit;
        $html = cView::page(
            $view,
            $ecp->getInfo()
        );
        $this->setContentType();
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
        $view = $rd["view"] ?? null;
        $params = $rd["params"] ?? [];
        $html = $rd["html"] ?? null;

        //如果需要调用 View 视图类
        if (class_exists($view)) {
            //TODO: 创建视图实例，传入 params ，调用 视图实例的 export 方法

            exit;
        }

        //如果传入了 输出页面的文件路径
        if (file_exists($view)) {
            $pd = cView::page(
                $view,
                $params
            );
            //设置 Content-Type
            $this->setContentType();
            //输出 响应头
            $this->response->header->sent();
            //echo 
            echo $pd;
            exit;
        }

        //如果传入了 html
        if (Is::nemstr($html)) {
            //设置 Content-Type
            $this->setContentType();
            //输出 响应头
            $this->response->header->sent();
            //echo 
            echo $html;
            exit;
        }

        exit;
    }
}