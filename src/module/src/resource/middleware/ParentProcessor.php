<?php
/**
 * Resource 资源处理中间件 Processor 处理器子类
 * ParentProcessor 专门处理 任意类型资源 的 父级资源实例
 * 
 * !! 默认所有资源在初始化阶段 会自动创建此处理器实例，不需要再在资源类的 middleware 参数中显式指定
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\Resource;
use Spf\module\src\resource\Compound;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

class ParentProcessor extends Processor 
{
    //设置 当前中间件实例 在资源实例中的 属性名，不指定则自动生成 NS\middleware\FooBar  -->  foo_bar
    public static $cacheProperty = "parenter";



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
        //如果 指定了 belongTo 参数
        $bto = $this->resource->params["belongTo"] ?? null;
        if (empty($bto) || !$bto instanceof Compound) return true;
        //引用到 当前资源的 parentResource 属性
        $this->resource->parentResource = $bto;

        return true;
    }



    /**
     * 工具方法
     */

    /**
     * 返回当前资源是否 有父级资源
     * @return Bool
     */
    public function hasParent()
    {
        $res = $this->resource;
        //parentResource
        $pres = isset($res->parentResource) ? $res->parentResource : null;
        if (!empty($pres) && $pres instanceof Compound) return true;
        return false;
    }

}