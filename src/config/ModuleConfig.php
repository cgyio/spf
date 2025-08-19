<?php
/**
 * 框架核心配置类
 * 模块配置类 基类
 */

namespace Spf\config;

use Spf\Middleware;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class ModuleConfig extends Configer 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [];

    /**
     * 可在多个配置类中通用的 设置参数默认值
     * 如果设定了此值，则 $init 属性需要合并(覆盖)到此数组
     * !! 如果需要，可以在某个配置类基类中定义此数组，然后在配置类子类中部分定义 $init 数组，即可实现 设置参数的继承和子类覆盖
     */
    protected $dftInit = [

        //是否启用此模块，默认 false
        "enable" => false,

        //此模块是否受 WEB_PAUSE 影响，默认 true，此模块下的操作方法可自行在 注释中覆盖此参数
        "pause" => true,

        //此模块是否 仅 开发环境下 可用
        "dev" => false,

        //依赖的 其他模块
        "dependency" => [
            /*
            "mod_name" => [
                # 模块参数 与 此数组 结构一致
                "middleware" => [],
                ...
            ],
            ...
            !! 经过 Module::findDependency 方法处理后，此参数将变更为 indexed 数组，包含所有依赖的模块的 modk
            */
        ],

        //此模块必须的 中间件，要删除 全局|应用中 定义的某个中间件，可在 类名路径前增加 __delete__ 标记
        "middleware" => [
            //入站
            "in" => [],
            //出站
            "out" => [],
            //中间件配置参数
            //...
        ],
        
        //其他模块参数，应在对应的模块配置类 $init 属性中定义
        //...
    ];



    /**
     * 在初始化时，处理外部传入的 用户设置，例如：提取需要的部分，过滤 等
     * !! 覆盖父类
     * @param Array $opt 外部传入的 用户设置内容
     * @return Array 处理后的 用户设置内容
     */
    protected function fixOpt($opt=[])
    {
        /**
         * 模块的 配置参数，在 App 实例化时已处理过，直接传入即可
         */
        return $opt;
    }

    /**
     * 定义 配置参数 合并方法 默认使用 Arr::extend 覆盖方向： $opt --> $init --> $dftInit
     * !! 覆盖父类
     * @return $this
     */
    public function extendConf()
    {
        /**
         * 主要处理 middleware 序列
         * 需要严格按照  $opt --> $init --> $dftInit 顺序 push 到数组中
         * 如果 覆盖定义的 module|middleware 为不启用，则需要从已有的序列中 删除
         */
        //按顺序读取
        $queue = [
            $this->dftInit,
            $this->init,
            $this->opt
        ];

        //合并 middleware
        $mids = Middleware::getStdMids();
        for ($i=0;$i<count($queue);$i++) {
            //合并 middleware
            $cmids = $queue[$i]["middleware"] ?? [];
            $mids = Middleware::extend($mids, $cmids);
            unset($queue[$i]["middleware"]);
        }

        //合并其他参数

        //预定义设置参数，合并默认设置
        if (Is::nemarr($this->dftInit)) {
            $this->init = Arr::extend($this->dftInit, $this->init);
        }

        //合并 用户设置 与 预定义参数，保存到 context
        $this->context = Arr::extend($this->init, $this->opt);

        //将 middleware 参数写入 context
        $this->context["middleware"] = $mids;

        return $this;
    }

    /**
     * 根据当前 配置类全称，获取对应的 模块类名
     *      Spf\module\foo_bar\ModuleFooBarConfig           -->  FooBar
     *      NS\module\app_name\foo_bar\ModuleFooBarConfig   -->  FooBar
     * @return String 模块类名 FooBar 形式
     */
    public static function moduleClsn()
    {
        $cls = static::class;
        $clsn = Cls::name($cls);
        //截取 模块类名
        if (substr($clsn, 0, 6)==="Module") $clsn = substr($clsn, 6);
        if (substr($clsn, -6)==="Config") $clsn = substr($clsn, 0, -6);
        return $clsn;
    }
}