<?php
/**
 * 框架特殊工具类
 * 处理 php://input 主要是 ajax 提交的 json 数据
 */

namespace Spf\util;

class Inputs extends SpecialUtil 
{
    /**
     * 此工具 在启动参数中的 参数定义
     *  [
     *      "util" => [
     *          "util_name" => [
     *              # 如需开启某个 特殊工具，设为 true
     *              "enable" => true|false, 是否启用
     *              ... 其他参数
     *          ],
     *      ]
     *  ]
     * !! 子类必须覆盖这些静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = true;   //默认启用
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];



    //原始数据
    protected $origin = [];

    //处理后的数据
    protected $context = [];

    /**
     * 构造
     * @param Array $data 待处理数据 php://input
     * @return void
     */
    public function __construct()
    {
        $input = file_get_contents("php://input");
        if (empty($input)) {
            $input = Session::get("_php_input_", null);
            //if (is_null($input)) return null;
            Session::del("_php_input_");
        }
        $this->origin = $input;

        //Secure 处理
        //...
        //缓存处理后的 input
        $this->origin = $input;
        //写入 context
        $this->context = $input;
    }

    /**
     * 按指定类型 转换 数据
     * @param String $type 默认 json
     * @return Array
     */
    protected function export($type = "json")
    {
        $input = $this->context;
        $output = [];
        switch($type){
            case "json" :
                $output = Conv::j2a($input);
                break;
            case "xml" :
                $output = Conv::x2a($input);
                break;
            case "url" :
                $output = Conv::u2a($input);
                break;
            case "arr" : 
                $output = Arr::mk($input);
            default :
                $output = $input;
                break;
        }
        return $output;
    }

    /**
     * __get 调用 export 方法
     * $this->foo  -->  $this->export("foo")
     * @param String $key
     * @return Array
     */
    public function __get($key)
    {
        //if ($key=="raw") $key = "";
        $out = $this->export($key);
        return $out;
    }



    /**
     * 运行时修改 post 来的数据
     * 通常在 劫持某个响应者执行某个响应方法时，可能需要 append 数据到 $requesr->inputs->context
     * !! 应在执行完操作后 reset 此数据，恢复原来的 input 数据
     */

    /**
     * 可以在运行时，插入 input 数据，模拟前端 post 数据
     * @param String $type input 数据类型，默认为 json
     * @param Array $input 要模拟 input 的数据 []，与原 input 数据采用 extend 方式合并
     * @return $this
     */
    public function append($type="json", $input=[])
    {
        $oinp = $this->context;
        $ninp = [];
        switch($type){
            case "json" :
                $oinp = Conv::j2a($oinp);
                $ninp = Arr::extend($oinp, $input);
                $ninp = Conv::a2j($ninp);
                break;
            case "xml" :
                $oinp = Conv::x2a($oinp);
                $ninp = Arr::extend($oinp, $input);
                $ninp = Conv::a2x($ninp);
                break;
            case "url" :
                $oinp = Conv::u2a($oinp);
                $ninp = Arr::extend($oinp, $input);
                $ninp = Conv::a2u($ninp);
                break;
            case "arr" : 
                $oinp = Arr::mk($oinp);
                $ninp = Arr::extend($oinp, $input);
                $ninp = Str::mk($ninp);
            default :
                $ninp = $oinp."\r\n".$input;
                break;
        }
        //写入 context
        $this->context = $ninp;
        //返回
        return $this;
    }

    /**
     * 取消 运行时插入的 input 数据
     * 
     * @return $this
     */
    public function reset()
    {
        $this->context = $this->origin;
        return $this;
    }



}