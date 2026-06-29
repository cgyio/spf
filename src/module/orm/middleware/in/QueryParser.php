<?php
/**
 * SPF-Orm 数据库操作模块
 * 入站中间件 
 * QueryParser 解析请求的数据中，与 Orm 相关的数据
 * 
 */

namespace Spf\module\orm\middleware\in;

use Spf\Middleware;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Conv;

class PostParser extends Middleware 
{
    /**
     * 单例模式
     * !! 覆盖父类，具体中间件子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;

    /**
     * 定义 Orm 相关的 post 数据默认结构
     * 此数据应在 Request::$current->inputs->json["orm"] 中保存
     */
    protected $dftOrmPosts = [
        //query 参数，用于创建 Queryer 查询器
        "query" => [
            //结构与 module/orm/curd/Queryer::$dftQuery[] 一致
        ],

        //需要 update 数据的，需要传入 data
        "data" => [
            //某条记录的 待写入数据
            //!! 所有字段的数据使用对应的 php 类型，写入数据库前 会自动转换为 db 类型
        ],

        //可以有其他 额外参数 ...

    ];

    

    /**
     * 中间件的 核心方法，执行 入站|出站 过滤操作
     * 执行中间件逻辑，处理 Request|Response 实例，返回 是否过滤通过 的标记
     * !! 子类必须实现
     * @return Bool 当 此方法返回 false 时，将触发 中间件的 exit 终止响应 动作
     */
    public function handle()
    {
        //Request 实例 必须存在
        $req = $this->Req();
        if (empty($req)) return true;

        //读取由 Request 处理过的 传入的 json 数据
        $op = $req->inputs->json;
        $ormp = $op["orm"] ?? [];

        if (!Is::nemaso($ormp)) {
            //未传入 Orm 相关的 post 数据
            $ormp = [];
        }

        //合并默认值
        $ormp = Arr::extend([], $this->dftOrmPosts, $ormp);
        //挂载到 Request 实例
        $req->inputs->orm = (object)$ormp;

        return true;
    }

    /**
     * 中间件过滤方法 返回了 false 需要终止响应，将执行此方法
     * !! 此中间件不需要 退出方法，不要覆盖父类
     * @return void
     */
    //protected function exit()
}