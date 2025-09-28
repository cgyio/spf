<?php
/**
 * Resource 资源处理中间件
 * GetContent 所有类型资源的 content 资源内容获取方法 默认方法
 * 不同类型的 Resource 子类可定义不同 content 获取中间件，例如：GetImageContent
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\ResourceMiddleware;
use Spf\module\src\Builder;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Curl;

class GetContent extends ResourceMiddleware 
{
    /**
     * 定义此中间件的 标准参数 控制具体的处理方法
     * 中间件运行时，传入的参数 将与此合并
     * !! 覆盖父类
     */
    protected static $stdParams = [
        //当目标资源实例是 手动创建的 临时资源时，需要提供 content 资源内容
        "content" => "",
    ];

    /**
     * 此中间件的核心处理方法
     * 处理并修改 资源实例数据
     * !! 覆盖父类
     * @return Bool 
     */
    public function handle()
    {
        //resource
        $res = $this->resource;
        //sourceType
        $type = $res->sourceType;

        //手动创建的 临时资源
        if ($type === "create") {
            $this->resource->content = $this->params["content"] ?? "";
            return true;
        }

        $m = "get".Str::camel($type, true)."Content";
        if (method_exists($this, $m)) {
            $this->$m();
        }

        return true;
    }

    /**
     * 不同资源来源类型的 资源内容读取方法 具体实现
     * @return void
     */
    //读取本地资源内容
    protected function getLocalContent() {
        $real = $this->resource->real;
        if (!file_exists($real)) $this->resource->content = null;
        //纯文本|二进制 类型文件 都可以读取
        $this->resource->content = file_get_contents($real);
    }
    //读取 远程资源内容 Curl
    protected function getRemoteContent() {
        $real = $this->resource->real;
        $isHttps = strpos($real, "https")!==false;
        if ($isHttps) {
            $this->resource->content = Curl::get($real, "ssl");
        } else {
            $this->resource->content = Curl::get($real);
        }
    }
    //读取 require 来源类型的资源内容，通过 require php 文件，生成文件内容
    protected function getRequireContent() {
        $params = $this->resource->params;
        //将资源额外参数作为 变量定义 注入 php 文件中
        foreach ($params as $k => $v) {
            $$k = $v;
        }
        //传递 资源实例
        $resource = $this->resource;
        
        //开始通过 require php 文件生成资源内容
        //@ob_start();  //框架自动开启
        require($this->resource->real); 
        $this->resource->content = ob_get_contents(); 
        ob_clean();
    }
    //通过 build 构建的方式 生成资源内容
    protected function getBuildContent() {
        $ext = $this->resource->ext;
        //查找是否存在 ExtBuilder 资源构建类
        $clsn = Str::camel($ext, true)."Builder";
        $cls = Cls::find("module/src/resource/builder/$clsn");
        if (Is::nemstr($cls)) {
            //存在则实例化之
            $this->resource->builder = new $cls($this->resource);
        } else {
            //不存在，则使用 Builder 基类
            $this->resource->builder = new Builder($this->resource);
        }
        //构建生成 content
        $this->resource->content = $this->resource->builder->build();
    }
}