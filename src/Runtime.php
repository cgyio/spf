<?php
/**
 * 框架核心类
 * 运行时，所有 核心类实例、环境变量、配置参数 等全局资源的 的挂载主体，
 * 框架响应流程的实施者
 */

namespace Spf;

use Spf\exception\BaseException;
use Spf\exception\CoreException;
use Spf\util\Event;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Gets;
use Spf\util\Conv;
use Spf\util\Operation;

final class Runtime extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;

    /**
     * 核心类 单例的挂载点
     */
    //环境参数
    public static $env = null;
    //请求实例
    public static $request = null;
    //应用实例
    public static $app = null;
    //响应实例
    public static $response = null;

    /**
     * cgyio/spf 框架启动入口
     * 在 index.php 中调用，并输入 框架启动参数 Runtime::start([ ... ])
     * @param Array $opt 框架启动参数
     * @return void
     */
    public static function start($opt=[])
    {
        //此方法只能执行一次
        if (static::$isInsed === true) return;

        //确认输入的 启动参数
        if (!Is::nemarr($opt)) $opt = [];

        /**
         * 框架启动 流程
         */
        @ob_start();
        @session_start();

        /**
         * step 0   全局错误处理
         */
        BaseException::regist();

        /**
         * step 1   实例化 环境参数管理类
         * 定义 框架环境参数常量
         * 处理框架启动参数中的 env 参数项
         */
        Runtime::$env = Env::current($opt);

        /**
         * step 2   Runtime 实例化
         */
        Runtime::current();

        /**
         * 框架环境准备完成，开始执行 标准响应流程
         */

        /**
         * step 3   实例化 Request 请求，请求类将在实例化后执行下列操作：
         *  0   创建当前请求的 Url 实例，获取相应的 请求参数
         *  1   创建请求头 RequestHeader 实例，获取相应的 请求参数
         *  2   创建 Ajax 请求处理实例，获取相应的 参数
         *  3   创建所有传入的 数据对象实例 $_GET | $_POST | $_FILES | php://input
         *  4   解析当前请求的 Url 得到 目标 App 应用类
         * 处理框架启动参数中的 request 参数项
         */
        Runtime::$request = Request::current($opt);

        /**
         * step 4   实例化 App 应用类，当前请求的应用类 实例化后，将执行下列操作：
         *  0   生成(并缓存)此应用的 全部操作列表，同时生成 路由表
         *  1   实例化参数中的所有 启用的模块
         *  2   执行 此应用类 自定义的 初始化方法
         * 处理框架启动参数中的 app|route|module|middleware 参数项
         */
        $appcls = Runtime::$request->getApp();
        Runtime::$app = App::current($opt, $appcls);
        
        /**
         * step 5   依次 实例化并执行 入站中间件 过滤
         * 如果有 中间件过滤不通过，将终止响应
         */
        Middleware::process("in");

        /**
         * step 6   创建 Response 响应类实例，当前 响应类 实例化后，将执行下列操作：
         *  0   创建响应头 ResponseHeader 实例
         *  1   创建响应码管理实例 创建时 默认状态码 200
         *  2   收集必须的 响应参数
         *  3   创建 Exporter 类实例
         *  4   如果 WEB_PAUSE==true 尝试中断响应
         * 处理框架启动参数中的 response 参数项
         */
        Runtime::$response = Response::current($opt);

        /**
         * step 7   执行响应方法，将方法返回的数据结果，存入 Response 响应实例的 data 属性
         */
        Runtime::$app->response();

        /**
         * step 8   依次 实例化并执行 出站中间件 对 Response 实例进行操作和修改
         */
        Middleware::process("out");

        /**
         * step 9   输出最终的响应结果，完成本次会话
         */
        Runtime::$response->export();

    }

    /**
     * 快速判断 核心类 ready 状态
     * @param Array $cores 要检查的核心类名 foo_bar
     * @return Bool
     */
    public static function coreReady(...$cores)
    {
        $ready = true;
        foreach ($cores as $ck) {
            $ready = $ready && isset(Runtime::$$ck) && isset(Runtime::$$ck::$isInsed) && Runtime::$$ck::$isInsed===true;
        }
        return $ready;
    }

    /**
     * 当前运行时 快照
     * !! 只有在 Env|Request|App 核心类就绪后，在能执行
     * @return Object (object)[
     *      # 当前核心单例的 深拷贝
     *      "env"       => Arr::copy(Runtime::$env, true),
     *      "app"       => ...,
     *      "request"   => ...,
     *      "response"  => ...,
     *      "modules"   => [
     *          "orm"   => ...,
     *          ...
     *      ]
     * ]
     */
    public static function snapshot()
    {
        //只有在 核心类就绪后 才能执行
        if (Runtime::coreReady("env", "request", "app")!==true) return null;

        //深拷贝 方法
        $cp = function(&$to, $key, $obj) {
            if (!Is::nemstr($key)) return $to;
            if (!is_object($obj) || empty($obj)) return $to;
            $cpo = Arr::copy($obj, true);
            if (!is_object($obj) || empty($obj)) return $to;
            $to[$key] = $cpo;
            return $to;
        };

        //快照
        $snap = [];

        //核心类 快照
        $cores = ["env", "request", "app", "response"];
        foreach ($cores as $ck) {
            if (Runtime::coreReady($ck)) {
                $cp($snap, $ck, Runtime::$$ck);
            }
        }

        //模块 快照
        $snap["modules"] = [];
        foreach (Module::$modules as $modk => $modo) {
            if ($modo instanceof Module) {
                $cp($snap["modules"], $modk, $modo);
            }
        }

        return (object)$snap;
    }

    /**
     * 使用 快照 覆盖当前环境
     * !! 危险操作
     * @param Object $snap 由 Runtime::snapshot 生成的 运行时快照
     * @return Bool
     */
    public static function restoreSnapshot($snap=null)
    {
        if (!is_object($snap) || !isset($snap->env) || !isset($snap->request) || !isset($snap->app)) return false;

        //恢复 核心类
        $cores = ["env", "request", "app", "response"];
        foreach ($cores as $ck) {
            if (!isset($snap->$ck)) continue;
            Runtime::$$ck = $snap->$ck;
            Runtime::$$ck::$current = $snap->$ck;
        }

        //恢复 模块
        $mods = $snap->modules ?? [];
        foreach ($mods as $modk => $modo) {
            Module::$modules[$modk] = $modo;
        }

        return true;
    }

    /**
     * 在 隔离当前运行时的 环境下，执行自定义操作，返回结果
     * 操作执行完毕后，将回到当前运行时
     * !! 自定义操作中 所有对运行时的修改 都不会影响当前运行时
     * @param \Closure $fn 自定义操作方法，方法中可以使用所有 核心类的方法，但是不会影响当前运行时
     * @return Mixed|null 返回 $fn 方法的执行结果
     */
    public static function isolate($fn=null)
    {
        //只有在 核心类就绪后 才能 在隔离状态下 执行自定义操作
        if (Runtime::coreReady("env", "request", "app")!==true) return null;
        if (!$fn instanceof \Closure) return null;

        //隔离当前运行时，创建当前运行时快照
        $snap = Runtime::snapshot();
        if (!is_object($snap) || empty($snap)) return null;

        //执行自定义操作
        $result = $fn();

        //恢复运行时
        Runtime::restoreSnapshot($snap);

        //返回操作结果
        return $result;
    }

    /**
     * 在一次会话中，执行另一个 opr 操作
     * !! 执行操作时，将创建一个 临时的 运行时环境，操作完成后将会恢复为原环境
     * !! 不能 跨应用，只能在 $app->response() 阶段调用
     * !! 此时 入站中间件已经执行过处理，因此需要从新执行一次 入站中间件
     * @param Array $oprc 要劫持的 opr 操作
     * @param Array $args 应在 url 中提供的 路由参数  indexed[]  可能包含 ?queryString 字符 
     * @param Array $post 模拟 以 php://input 方式传入的 json 参数
     * @param \Closure $callback 要执行的 额外 操作
     * @return Mixed|null 操作结果
     */
    public static function __hijack($oprc, $args=[], $post=[], $callback=null)
    {
        //只有在 核心类就绪后 才能执行
        if (Runtime::coreReady("env", "request", "app")!==true) return null;
        //传入的 参数必须有效
        if (!Operation::isStdOprc($oprc)) return null;

        //创建快照
        $snap = Runtime::snapshot();
        if (!is_object($snap) || empty($snap)) return null;

        //执行 opr 操作 期间可以任意修改 运行时环境，操作结束后，将会恢复

        //修改 request 实例
        //处理 args 中可能包含的 queryString
        $uris = implode("/", $args);
        if (!Is::nemstr($uris)) {
            $args = [];
            //更换 $request->gets
            Runtime::$request->gets = new Gets(null);
        } else if (strpos($uris,"?")!==false) {
            $qs = explode("?", $uris);
            $args = explode("/", $qs[0]);
            $qs = Conv::u2a($qs[1]);
            //更换 $request->gets
            Runtime::$request->gets = new Gets($qs);
        } else {
            $args = explode("/", $uris);
        }
        Runtime::$request->runtimeSetOprc($oprc, $args);
        //模拟 post 数据
        if (!Is::nemaso($post)) $post = [];
        Runtime::$request->inputs->replace("json", $post);

        //再次执行 入站中间件过滤，以 劫持方式，如果 过滤不通过，将终止并返回 null，不 exit
        $filter = Middleware::process("in", true);
        //未能通过 入站中间件过滤，不执行后续，直接返回 null
        if ($filter!==true) return null;

        //执行 被劫持的 opr 操作，返回结果，而不是 setData 到 $response
        $result = Runtime::$app->response(true);
        //如果指定了 额外操作
        if ($callback instanceof \Closure) {
            $result = $callback($result);
        }
        
        //恢复 post 数据
        Runtime::$request->inputs->reset();

        //恢复快照
        Runtime::restoreSnapshot($snap);

        //返回操作结果
        return $result;
    }



    /**
     * 静态工具
     */

    /**
     * 通过 proc_open 方式运行 命令行
     * @param String|Array $cmd 可以是 字符串 或 indexed 数组
     * @return array [status, stdout, stderr]
     */
    public static function procExec($cmd=[])
    {
        //固定管道描述数组
        $descriptorspec = [
            0 => ["pipe", "r"],     //stdin 写管道，php向命令输入数据，无需交互则后续关闭
            1 => ["pipe", "w"],     //stdout 读管道，接受命令的正常输出
            2 => ["pipe", "w"],     //stderr 读管道，接受命令的错误信息
        ];

        //创建进程
        $process = proc_open($cmd, $descriptorspec, $pipes, null, null, [
            "timeout" => 10,        //超时时间 10s
            "bypass_shell" => true, //跳过 shell 解析，更安全
        ]);

        $stdout = '';
        $stderr = '';
    
        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
    
            $status = proc_close($process);
        } else {
            $status = 1;
            $stderr = 'proc_open failed';
        }
    
        return [
            'status' => $status,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
        ];
    }



    /**
     * 运行时实例方法
     */

    /**
     * Runtime 运行时类自有的 init 方法，执行以下操作：
     * 
     * !! 子类必须实现
     * @return $this
     */
    final public function initialize()
    {
        

        return $this;
    }
    
    /**
     * 快捷访问 __get
     * !! 覆盖子类，请在此基础上增加，即 必须在子类 __get 方法中调用 parent::__get()
     * @param String $key 要访问的 不存在的 属性
     * @return Mixed
     */
    public function __get($key)
    {

        /**
         * 最后 调用父类 __get 方法
         */
        return parent::__get($key);
    }



    /**
     * 实例工具方法
     */





}
