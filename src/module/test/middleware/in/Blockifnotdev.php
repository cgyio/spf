<?php
/**
 * Spf 框架 入站中间件
 * Test 模块专用
 * Blockifnotdev 检查当前环境，如果不是开发环境，直接退出
 * 
 * !! 通常情况下，Test 模块类或子类，其 参数中 dev 一定是 true
 * !! 表示测试模块只能在 开发环境下使用
 * !! 此中间件作为 二次保险，防止 测试模块中的 危险接口被使用
 * !! 如果任意应用 启用了 Test 模块，则此中间件会自动执行
 */

namespace Spf\module\test\middleware\in;

use Spf\Middleware;
use Spf\module\Test;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;

class Blockifnotdev extends Middleware 
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
     * 中间件的 核心方法，执行 入站|出站 过滤操作
     * 执行中间件逻辑，处理 Request|Response 实例，返回 是否过滤通过 的标记
     * !! 子类必须实现
     * @return Bool 当 此方法返回 false 时，将触发 中间件的 exit 终止响应 动作
     */
    public function handle()
    {
        //!! Test 测试模块只能在 开发模式下运行
        if ($this->Env()->dev===true) return true;

        $oprc = $this->Req()->getOprc();
        //当前请求的 操作指向的 类全称
        $cls = $oprc["class"];

        //当前是 Test 基类
        $isBase = method_exists($cls, "clsk") && $cls::clsk()===Test::clsk();
        //当前是 Test 子类
        $isSubc = is_subclass_of($cls, Test::class);

        return !($isBase || $isSubc);
    }

    /**
     * 中间件过滤方法 返回了 false 需要终止响应，将执行此方法
     * !! 覆盖父类
     * @return void
     */
    protected function exit()
    {
        //TODO：日志，权限信息 返回前端

        exit;
    }

}