<?php
/**
 * SPF-Orm 数据库操作模块
 * 定义 Orm 要适配的 前端框架 Frontend 兼容层
 * 
 * 适配前端框架 Deskvue
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * !! 不同的应用可以在自身的 module/orm/frontend/ 路径下扩展不同的 前端框架兼容层
 */

namespace Spf\module\orm\frontend;

use Spf\Env;
use Spf\App;
use Spf\module\orm\Frontend;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Deskvue extends Frontend 
{
    /**
     * !! Frontend 子类必须定义
     */
    //当前 frontend 子类名称
    protected static $frontend = "deskvue";

    /**
     * 当前 前端库自定义接口 与 SPF-Orm 模块实际接口的映射
     * 将会在 Orm 初始化阶段，被处理并收集到 全局 oprs 操作列表中，并接入统一的 缓存、权鉴体系中
     */
    protected static $apiMap = [
        //!! forDev: Deskvue 框架初始化接口
        "/dv\/init(.*)/" => [
            "name" => "init",
            "title" => "Deskvue 初始化接口",
            "desc" => "这是一个测试接口",
            //"class" => "不指定 class 默认指向当前 Deskvue 类",
            "method" => "init",     //响应方法指向 Deskvue::initApi()
            "proxy" => [
                "translator" => "init", //入参转译方法指向 Deskvue::initTranslator()
            ],
            "auth" => false,
            "pause" => false
        ],
    ];

    /**
     * 接口方法
     */
    //initApi
    public function initApi(...$args)
    {
        $req = $this->Req();
        $oprc = $req->request->oprc;

        return [
            "args" => $args,
            "oprc" => $oprc,
            "responser" => static::class."::initApi",
        ];
    }

    /**
     * 入参转译方法
     */
    //initTranslator
    protected function initTranslator($input)
    {
        if (!Is::nemaso($input)) return [];
        $ni = [];
        foreach ($input as $k => $v) {
            $ni[$k] = Is::nemstr($v) ? $v."_mod" : $v;
        }
        return $ni;
    }
}