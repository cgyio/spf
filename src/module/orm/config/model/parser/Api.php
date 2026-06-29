<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型配置解析类  继承自 Parser 基类
 * 
 * 收集 数据模型(表) 类中 定义的 fooBarApi (静态|实例)方法，生成 api 标准结构
 * 
 * 在 model 类中定义了 public [static] fooBarApi() 方法，
 * 必须有注释：
 *      /**
 *       * api
 *       * @name api_name       # 接口名 foo_bar
 *       * @title 接口标题      # 用于为用户赋予操作权限时，下拉列表显示
 *       * @desc Api说明        # 接口说明
 *       * @auth true           # 通常必须开启权限控制
 *       * @role all            # 有权限的 角色，默认 all 此处指定的 role 将合并到 Uac 模块的 AuthorityControl 中间件内，合并判断
 *       * @pause true          # 是否受 WEB_PAUSE 控制
 *       * ...
 * 
 * 数据模型 类|实例 定义的 api 接口，可通过 /db/[dbn]/[modk]/foo_bar/arg1/arg2... 形式调用
 * 也可以在内部调用：
 *      模型类接口：    Orm::$current->db(dbn)->model(modk)->fooBar(arg1,arg2,...) 
 *      模型实例接口：  $record->fooBar(arg1,arg2,...)
 */

namespace Spf\module\orm\config\model\parser;

use Spf\module\Orm;
use Spf\module\orm\config\model\Parser;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Operation;

class Api extends Parser 
{
    /**
     * 解析过程中的 数据，这些数据最终将被 写入 $this->context 
     * 通常指定了 此解析器将要修改 $this->context 中的 哪些数据
     * !! 与 DbConfig::$exportModelConf[] 结构一致
     * !! 覆盖父类
     */
    protected $temp = [
        //数据模型类中 定义的 apis 接口
        "oprs" => [
            //标准的 操作数组，与 util\Operation::$stdOprs[] 结构一致
            //!! 这些操作，会在 Orm 初始化时，被添加到当前应用的 操作列表中，参与路由匹配
            //"apis" => [ 操作标识1, ... ],
            //"操作标识1" => [ ... ],
            //...
        ],
    ];
    


    /**
     * 解析入口
     * 解析 $this->origin 参数，将生成的最终参数 写入 $this->context 并返回
     * !! 必须实现，覆盖父类
     * @return Array 解析得到的 此数据模型(表)参数 []
     */
    public function parse()
    {
        //!! 此时 Orm 实例必须已创建
        if (Orm::$isInsed!==true) return $this->context;
        $orm = Orm::$current;
        //Orm 模块，访问数据库操作接口的统一的 路由前缀，在 $orm->config->ctx["dbRoutePrefix"] 中定义，默认 db
        $pre = $orm->config->ctx["dbRoutePrefix"];
        
        //参数
        $dbn = $this->dbn;
        $dbt = $this->config->ctx["title"];
        $modk = $this->modk;
        $modt = $this->origin["title"];

        //收集 api 方法
        $collector = function($oprc) use ($pre, $dbn, $dbt, $modk, $modt) {
            //api 原名 foo_bar
            $an = $oprc["name"];
            //增加 dbn_ 前缀
            $nn = $dbn."_".$modk."_".$an;
            
            //手动修改这些操作的 参数信息
            $oprc = Arr::extend($oprc, [
                //这些操作 统一指向 $orm->responseProxyer 方法
                "class"     => Orm::class,
                "method"    => "responseProxyer",
                //!! $orm->responseProxyer 一定是 实例方法
                "isStatic"  => false,
                //!! 修改操作标识
                "oprn"      => "api/model/$dbn/$modk".":".$an,
                //!! 修改自动创建的 route 路由正则
                "route"     => "/".$pre."\/".$dbn."\/".$modk."\/".$an."(\.*)/",
                //修改参数
                "name"      => $nn,
                "title"     => $dbt.">".$modt."：".$oprc["title"],
                "desc"      => $dbt.">".$modt."：".array_slice(explode("：", $oprc["desc"]), -1)[0],
                //额外参数
                "dbn"       => $dbn,
                "modk"      => $modk,
                //在 $orm->responseProxyer 方法中，需要调用的实际方法，需要标记 isStatic 
                "proxy"     => [
                    "class"     => $oprc["class"],
                    "method"    => $oprc["method"],
                    //数据模型操作 可能是 静态 或 非静态 方法
                    "isStatic"  => $oprc["isStatic"],
                ]
            ]);

            return $oprc;
        };

        // 0    收集 模型类接口
        $moprs = Operation::oprs(
            $this->model,
            "api",
            //模型类接口必须是 public 且 静态方法
            "public,&static",
            //手动指定 操作名称 oprn 前缀
            null,
            //手动指定 操作说明的 前缀
            null,
            //收集方法
            $collector
        );

        // 1    收集 模型实例接口
        $roprs = Operation::oprs(
            $this->model,
            "api",
            //模型实例接口必须是 public 且 非静态方法
            "public,&!static",
            //手动指定 操作名称 oprn 前缀
            null,
            //手动指定 操作说明的 前缀
            null,
            //收集方法
            $collector
        );

        //合并
        if (!Is::nemaso($moprs)) $moprs = [];
        if (!Is::nemaso($roprs)) $roprs = [];
        $oprs = Arr::extend([], $moprs, $roprs);
        
        //写入 $temp
        $this->setTemp([
            "oprs" => $oprs
        ]);

        //写入 context
        $this->setCtx($this->temp);

        return $this->context;
    }
}