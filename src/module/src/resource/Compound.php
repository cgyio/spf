<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Compound 子类
 * 处理 复合类型的 资源
 * 例如：Theme | Icon | Lib 等
 * 
 * 此类型资源都有一个主文件 *.json 文件，其中储存了对于此资源的 相关描述，描述数据的格式 在 stdDesc 参数中定义
 * 资源的描述数据 会保存到 资源实例的 desc 属性中
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\SrcException;
use Spf\module\src\Mime;
use Spf\Request;
use Spf\Response;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;

class Compound extends Resource 
{
    /**
     * 定义 复合资源在其 json 主文件中的 资源描述数据的 标准格式
     * !! 子类应在此基础上扩展
     */
    public static $stdDesc = [
        //!! 必须的 参数项
        //此复合资源的名称 foo_bar 形式
        "name" => "",
        //是否启用版本控制
        "enableVersion" => true,
        //是否启用缓存
        "enableCache" => true,

    ];

    //保存在资源实例中的 资源描述参数
    public $desc = [];





    /**
     * 工具方法
     */

    /**
     * 复合资源必须覆盖 Resource 父类的 resName 方法，获取资源名称
     * !! 如果需要，Compound 子类可覆盖这个方法
     * @return String 当前资源的 名称 foo_bar 形式
     */
    public function resName()
    {
        $n = $this->desc["name"] ?? null;
        if (Is::nemstr($n)) return $n;
        //调用父类方法
        return parent::resName();
    }

    /**
     * 如果此复合资源 启用了版本控制，需要定义 获取当前版本的 方法
     * !! 子类实现
     * @return String 版本号字符串 1.0.0  2.17.53 等形式的 字符串，对应着实际存在的 文件夹
     */
    public function getVersion()
    {
        return "";
    }

}