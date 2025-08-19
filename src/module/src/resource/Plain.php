<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Plain 子类
 * 处理 Plain 纯文本类型 资源 基类
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;

use MatthiasMullie\Minify;  //JS/CSS文件压缩

class Plain extends Resource 
{

    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function afterCreated()
    {
        
        
        return $this;
    }

    /**
     * 在输出资源内容之前，对资源内容执行处理
     * !! 覆盖父类
     * @param Array $params 可传入额外的 资源处理参数
     * @return Resource $this
     */
    protected function beforeExport($params=[])
    {
        //合并额外参数
        if (!Is::nemarr($params)) $params = [];
        if (!Is::nemarr($params)) $this->params = Arr::extend($this->params, $params);

        //minify
        if ($this->isMin() === true) {
            //压缩 JS/CSS 文本
            $this->content = $this->minify();
        }

        return $this;
    }



    /**
     * 判断是否 压缩输出
     * @return Bool
     */
    protected function isMin()
    {
        $min = $this->params["min"] ?? false;
        return is_bool($min) ? $min : false;
    }

    /**
     * 压缩 JS/CSS
     * @return String 压缩后的 内容
     */
    protected function minify()
    {
        $ext = $this->ext;
        $mcls = "MatthiasMullie\\Minify\\".strtoupper($ext);
        if (class_exists($mcls)) {
            $minifier = new $mcls();
            $minifier->add($this->content);
            $cnt = $minifier->minify();
            return $cnt;
        }
        return $this->content;
    }
}