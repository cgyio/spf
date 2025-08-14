<?php
/**
 * 核心类
 * Response 响应类
 */

namespace Spf;

use Spf\response\Status;
use Spf\response\Exporter;
use Spf\exception\BaseException;
use Spf\exception\AppException;
use Spf\util\ResponseHeader;
use Spf\util\Operation;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Response extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;

    //响应头实例
    public $header = null;

    //响应参数
    //WEB_PAUSE
    public $paused = false;
    //响应状态码 实例 默认 200
    public $status = null;
    //响应类型 在 Operation::$types 中定义，默认 view
    public $type = "view";
    //响应类型 对应的 Exporter 类实例
    public $exporter = null;

    //TODO: 支持标准的 psr7 响应
    //public $psr7 = true;

    //准备输出的内容
    public $data = null;

    //获取到的 异常信息数组
    public $exceptions = [
        //按先后顺序 push 进来的 多个 BaseException 类实例
    ];



    /**
     * 此 Response 响应类自有的 init 方法，执行以下操作：
     *  0   创建响应头 ResponseHeader 实例
     *  1   创建响应码管理实例 创建时 默认状态码 200
     *  2   收集必须的 响应参数
     *  3   创建 Exporter 类实例
     * !! Core 子类必须实现的
     * @return $this
     */
    final public function initialize()
    {
        //!! 响应实例 可能在 Request|App 实例未创建前 生成，比如 异常退出时
        //响应头 初始数据
        $ohds = [];
        //响应类型 来自请求的 操作方法信息
        $expt = null;
        if (Request::$isInsed === true) {
            //请求实例已创建
            $request = Request::$current;
            $ohds = $request->responseHeaders;
            if ($request->oprcMatched === true) {
                //请求的 操作方法已匹配到
                $oprc = $request->getOprc();
                $expt = $oprc["export"];
            }
        }

        // 0 创建响应头 ResponseHeader 实例，合并 $request->responseHeaders 数组
        $this->header = new ResponseHeader($ohds);

        // 1 创建响应码管理实例 创建时 默认状态码 200
        $this->status = new Status(200);

        // 2 收集必须的 响应参数
        if (defined("WEB_PAUSE")) $this->pause = WEB_PAUSE;
        if (Is::nemstr($expt) && in_array($expt, Operation::$types)) {
            //操作方法 类型可用
            $this->type = $expt;
        }

        // 3 创建 Exporter 类实例
        $expcls = $this->getExporter();
        //创建 Exporter 实例
        $this->exporter = new $expcls($this);

        return $this;
    }

    /**
     * 响应实例 执行输出 完成响应流程
     * @return void
     */
    final public function export()
    {
        try {
            //获取 Exporter 类
            $expcls = $this->getExporter();
            //创建 Exporter 实例
            $exper = new $expcls($this);

            //异常输出的情况


            //输出数据，完成本次响应
            $exper->export();
            //完成
            exit;
        } catch (BaseException $e) {
            //处理异常
            $e->handleException();
        }
    }

    /**
     * 执行异常输出，将 exceptions 中的异常实例信息，输出
     */



    /**
     * 工具方法
     */

    /**
     * 设置状态码
     * @param Int $code 响应状态码
     * @return Bool
     */
    public function setCode($code=200)
    {
        return $this->status->setCode($code);
    }

    /**
     * 动态设置 实际响应类型
     * @param String $type 实际响应类型
     * @return Bool
     */
    public function setType($type)
    {
        //确认有变化
        if ($type === $this->type) return true;

        try {
            if (!Is::nemstr($type) || !in_array($type, Operation::$types)) {
                //新类型 不被支持
                throw new AppException($type, "response/unsupport");
            }
            $this->type = $type;
            //获取 Exporter 类
            $expcls = $this->getExporter();
            //创建 Exporter 实例
            $this->exporter = new $expcls($this);

            return true;
        } catch (BaseException $e) {
            //处理异常
            $e->handleException();
        }
    }

    /**
     * 根据不同的 响应类型，执行不同 写入 响应数据的 操作
     * 通过各 类型的 Exporter 类 执行
     * @param Mixed $data 要写入的数据
     * @return Bool
     */
    public function setData($data)
    {
        try {
            //通过 Exporter 类实例 执行 setData 操作
            return $this->exporter->setResponseData($data);
        } catch (BaseException $e) {
            //处理异常
            $e->handleException();
        }
    }

    /**
     * catch 到 异常后，在 handleException 方法中，将异常实例 添加到 Response 实例中
     * @param BaseException $exception 异常实例
     * @return Bool
     */
    public function setException($exception)
    {
        if ($exception instanceof BaseException) {
            $this->exceptions[] = $exception;
        }
        return true;
    }

    /**
     * 根据当前响应实例的 type 属性，获取用于 输出响应数据的 Exporter 类
     * @return String Exporter 类全称
     */
    protected function getExporter()
    {
        //当前的 响应类型
        $type = $this->type;
        //要查找的 Exporter 类路径 []
        $cls = [];

        //优先在 应用目录下 查找类
        if (App::$isInsed === true) {
            $appk = App::$current::clsk();
            $cls[] = "app/$appk/response/exporter/$type";
        }

        //在框架默认路径下查找
        $cls[] = "response/exporter/$type";

        //查找
        $ecls = Cls::find($cls);
        if (!Is::nemstr($ecls) || !class_exists($ecls)) {
            //未找到对应的 Exporter 类
            throw new AppException($type, "response/unsupport");
        }

        return $ecls;
    }



    /**
     * 静态方法
     */

    /**
     * 非常规状态下 创建响应实例
     */
}