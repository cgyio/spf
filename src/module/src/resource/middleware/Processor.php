<?php
/**
 * Resource 资源处理中间件 Processor 处理器基类
 * 
 * Processor 处理器是一种特殊的中间件，他将 同一个类型的处理操作 集中在一个中间件中
 * 通过 传入不同的 params["stage"] 参数，可以在 不同的资源处理阶段，执行不同的操作
 * 例如：针对 Codex 可处理代码类型的资源，需要执行 内容行数组相关操作：
 *      create 阶段：需要生成 内容行数组，修改资源实例的 rows 参数
 *      export 阶段：需要将 rows 合并为最终输出的 content 字符串
 *      还可以通过 在资源实例的 middleware 参数中增加 特殊形式 定义，来调用自定义 stage 的 handle 方法：
 *          middleware = [
 *              "create" => [
 *                  "FooProcessor" => [
 *                      "stage" => "create",            将调用默认的 create 阶段的 handle 方法 stageCreate
 *                  ],
 * 
 *                  "FooProcessor #custom_stage" => [
 *                      "stage" => "custom_stage",      将调用 中间件自定义的 handle 方法 stageCustomStage 
 *                  ]
 *              ]
 *          ]
 * 
 * 另外，Processor 通常还会提供 此类型操作的一系列工具方法，供资源实例内部调用
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\SrcException;
use Spf\module\src\ResourceMiddleware;
use Spf\util\Is;
use Spf\util\Str;

class Processor extends ResourceMiddleware 
{
    /**
     * 定义此中间件的 标准参数 控制具体的处理方法
     * 中间件运行时，传入的参数 将与此合并
     * !! 覆盖父类
     */
    protected static $stdParams = [
        /**
         * !! processor 处理器类型的 中间件，需要在参数中确定其处于 资源处理的哪个阶段 create|export 
         * 因为 processor 处理器可以执行同一类型的 处理操作，这些操作 在 create|export 阶段都会执行
         */
        "stage" => "create",
        
        //forDev 断点标记，标记后将在此中间件执行完成后 退出脚本
        "exit" => false,
    ];

    //此中间件 是否在 目标资源实例中 缓存其实例 默认 true
    public static $cache = true;
    //设置 当前中间件实例 在资源实例中的 属性名，不指定则自动生成 NS\middleware\FooBar  -->  foo_bar
    public static $cacheProperty = "";

    /**
     * 可以指定 此处理器依赖的 其他处理器
     * 会在执行操作前检查 依赖的处理器 是否存在
     * !! 如果需要，子类应覆盖此参数
     */
    public static $dependency = [
        //"处理器的 类名|类全称|类路径",
        //...
    ];



    /**
     * !! 覆盖父类的构造方法，增加一个 初始化动作
     * @param Resource $res
     * @param Array $params 本次 中间件处理的 参数
     * @return void
     */
    final protected function __construct($res, $params=[])
    {
        //调用父类的 构造方法
        parent::__construct($res, $params);

        //执行 处理器内部的初始化方法，处理器子类必须实现这个方法，执行一些初始化动作
        $this->initialize();
    }

    /**
     * 此中间件的核心处理方法
     * 处理并修改 资源实例数据
     * !! 覆盖父类
     * @return Bool 
     */
    final public function handle()
    {
        //根据 params["stage"] 参数，分别执行对应 process 方法
        $stage = $this->params["stage"];

        //检查依赖项
        if (Is::nemarr(static::$dependency)) {
            $miss = $this->checkDependency(...static::$dependency);
            if ($miss !== true) {
                //缺少依赖项，报错
                throw new SrcException("缺少依赖项 $miss", "resource/".($stage==="export" ? "export" : "getcontent"));
            }
        }

        //foo_bar 形式的 stage 值 转为 FooBar 形式的 方法名
        $mn = Str::camel($stage, true);
        $m = "stage".$mn;

        if (!method_exists($this, $m)) {
            //要调用的 handle stage 不存在，报错
            throw new SrcException("调用了一个不存在的资源处理器方法", "resource/getcontent");
            return false;
        }

        //执行 对应的 process 方法
        $rtn = $this->$m();

        /**
         * !! 如果传入了 断点标记 exit 将在执行完此中间件的 stage 方法后 退出脚本
         */
        if (isset($this->params["exit"]) && $this->params["exit"] === true) {
            exit;
        }

        return $rtn;
    }



    /**
     * 处理器初始化动作
     * !! Processor 子类 必须实现
     * @return $this
     */
    protected function initialize()
    {

        return $this;
    }

    /**
     * create 阶段执行的操作
     * !! Processor 子类 必须实现
     * @return Bool
     */
    protected function stageCreate()
    {
        return true;
    }

    /**
     * export 阶段执行的操作
     * !! Processor 子类 必须实现
     * @return Bool
     */
    protected function stageExport()
    {
        return true;
    }

    /**
     * 可以自定义 其他 stage 方法
     * 例如： stageCustom ...
     */

    

    /**
     * __get 方法
     */
    public function __get($key)
    {
        /**
         * $this->FooProcessor  -->  $this->getProcessor("FooProcessor")
         */
        $pins = $this->getProcessor($key);
        if (!empty($pins)) return $pins;

        return null;
    }



    /**
     * 工具方法
     */

    /**
     * 在 某个处理器实例内部，访问此处理器目标资源实例中 缓存的 其他处理器实例
     * @param String $clsp 要访问的其他处理器 类名|类路径|类全称
     * @return Processor|null 已缓存的 处理器实例
     */
    public function getProcessor($clsp)
    {
        $cls = $this::cls($clsp);
        if (!class_exists($cls)) return null;
        //获取 处理器定义的 属性名
        $cp = $cls::$cacheProperty;
        if (!Is::nemstr($cp) || !isset($this->resource->$cp)) return null;
        return $this->resource->$cp;
    }

    /**
     * 检查 当前处理器依赖的 其他处理器实例是否已被缓存到 目标资源实例中
     * @param Array $processors 依赖的其他处理器名称
     * @return Bool|String 依赖的处理器实例是否存在 都存在返回 true 否则返回缺少的 处理器名称
     */
    public function checkDependency(...$processors)
    {
        if (empty($processors)) return true;
        foreach ($processors as $processor) {
            $po = $this->getProcessor($processor);
            if (!$po instanceof Processor) return $processor;
        }
        return true;
    }


}