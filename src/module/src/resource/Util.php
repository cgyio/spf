<?php
/**
 * Resource 资源处理类 工具基类
 * 针对某些类型的资源 执行一些特定的操作
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;

class Util 
{
    /**
     * 必须依赖某个 资源实例
     */
    public $resource = null;

    /**
     * 构造
     * !! 子类必须覆盖
     * @param Resource $res 资源实例
     * @return void
     */
    public function __construct($res) 
    {
        if (!$res instanceof Resource) return null;
        $this->resource = $res;
    }

    /**
     * 工具实例创建后 对关联资源实例 执行特定处理，并将处理结果 写回 资源实例
     * !! 子类必须实现此方法
     * @return $this
     */
    public function process()
    {
        //子类实现
        //...

        return $this;
    }



    /**
     * 静态方法
     */

    /**
     * 创建工具类实例，应在 资源实例内部执行
     * @param Resource $res
     * @return Util 工具类实例
     */
    public static function create($res)
    {
        if (!$res instanceof Resource) return null;

        //获取工具类实例 在资源实例 $res 中的属性名
        $k = static::clsk();

        //创建工具实例
        if (empty($res->$k)) $res->$k = new static($res);

        return $res->$k;
    }

    /**
     * 获取 当前工具类的 类名 FooBar 形式
     * @return String
     */
    public static function clsn()
    {
        $cls = static::class;
        $cla = explode("\\", $cls);
        return array_slice($cla, -1)[0];
    }

    /**
     * 获取 当前工具类的 类名 foo_bar 形式
     * @return String
     */
    public static function clsk()
    {
        $clsn = static::clsn();
        return Str::snake($clsn, "_");
    }
}