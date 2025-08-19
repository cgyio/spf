<?php
/**
 * 框架应用类
 * 通用 App 应用类实例的 响应方法
 * 响应一些 通用的 请求
 */

namespace Spf\app;

use Spf\App;
use Spf\exception\AppException;

class BaseApp extends App 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    /**
     * 应用的元数据
     * !! 实际应用类必须覆盖
     */
    //应用的说明信息
    public $intr = "默认应用";
    //应用的名称 类名 FooBar 形式
    public $name = "BaseApp";

    /**
     * default
     * @export api
     * @param Array $args url 参数
     * @return Array
     */
    public function default(...$args)
    {
        //throw new AppException("响应方法错误测试", "app/response");
        //return $this->config->module;
        //return $this->module->orm;
        var_dump(App::ModuleSrc());
        var_dump($this->mod_uac);
        var_dump($this->ModuleOrm);
    }
}