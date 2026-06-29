<?php
/**
 * 框架模块类
 * SPF-Orm 数据库操作模块 全局单例
 * 
 * 与当前会话的 App 应用实例绑定，可通过下列方式访问：
 *      App::ModuleOrm()
 *      $app->ModuleOrm
 *      $app->mod_orm
 *      Orm::$current
 * 
 * 在 ORM 配置参数的 dbs 项目中指定要使用的数据库，可以同时使用多个数据库
 * 数据库配置参数中必须包含 config 项目，此参数指定此数据库的配置文件路径 path/to/foo_bar.json 
 * 
 * 
 * 访问数据库 foo_bar 的实例：
 *      $orm->FooBar
 *      $orm->foo_bar
 *      $orm->db(foo_bar)
 *      Db::FooBar()
 * 如果未定义此数据库，将返回 null
 * 
 * 在 ORM 配置参数中被指定为 required 的数据库，将在初始化时立即连接并创建数据库实例
 * 非必须的数据库，则会在需要时再进行连接并创建实例
 * 
 */

namespace Spf\module;

use Spf\Module;
use Spf\App;
use Spf\module\orm\OrmException;
use Spf\module\orm\Db;
use Spf\module\orm\Driver;
use Spf\module\orm\Types;
use Spf\module\orm\config\model\Preparer;
use Spf\module\orm\config\model\Parser;
use Spf\module\orm\config\DbConfig;
use Spf\util\Event;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Operation;

class Orm extends Module 
{
    /**
     * 单例模式
     * !! 覆盖父类，具体模块子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = false;

    /**
     * 模块的元数据
     * !! 实际模块类必须覆盖
     */
    //模块的说明信息
    public $intr = "ORM数据库支持模块";
    //模块的名称 类名 FooBar 形式
    public $name = "Orm";

    //数据库实例集合
    protected static $dbs = [
        //"数据库唯一 key：DB_****" => Db 实例  或  DbConfig 数据库配置类实例,
    ];



    /**
     * ORM 模块启用后，将在实例化后，立即执行此 初始化操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function initModule()
    {
        //执行数据库初始化
        $ctx = $this->config->ctx;
        $dbs = $ctx["dbs"] ?? [];
        $req = $ctx["required"] ?? [];
        
        if (!Is::nemarr($dbs)) {
            //未指定有效的数据库
            throw new OrmException("", "orm/nodb");
            return false;
        }

        //依次处理所有定义的 数据库参数
        foreach ($dbs as $dbn => $dbc) {
            //创建 数据库配置类实例 并缓存到 static::$dbs[]
            $cfger = $this->dbCfger($dbn);
            if (!$cfger instanceof DbConfig) {
                //数据库配置类无法实例化
                throw new OrmException("$dbn,无法创建数据库配置类实例", "orm/instance");
                return false;
            }

            //在 Orm 初始化阶段，注册所有 数据库类中定义的 事件订阅
            Event::regist($cfger->ctx["class"]);

            //在 Orm 初始化阶段，注册所有数据模型的事件订阅
            $models = $cfger->ctx["model"] ?? [];
            foreach ($models as $modk => $modc) {
                //注册数据模型中的 事件订阅
                Event::regist($modc["class"]);
            }

            //如果当前数据库 $dbn 在 required 数组中
            if (in_array($dbn, $req)) {
                //创建数据库实例
                $this->db($dbn);
            }
        }

        return true;
    }
    
    /**
     * ORM 数据库操作类 获取操作列表 的自定义方法
     * 这些操作将被注入 当前应用的 操作列表中，并被缓存
     * !! 如果当前应用的 操作列表 已经被缓存，则需要清空缓存，否则此方法不会执行
     * !! 引用的类可覆盖
     * @param \Closure $func 可以指定额外的操作，参数为每个解析得到的 操作信息参数数组
     * @return Array 标准的 操作列表数据格式
     */
    public static function getOprs($func=null)
    {
        //从此模块类中 获取 util/Operation::$types 中定义的 特殊类型的方法，得到 操作列表 标准数据格式
        $oprs = parent::getOprs($func);

        //准备合并 数据库 以及 数据模型 中定义的 apis
        if (!Is::nemaso($oprs)) $oprs = [];
        if (!isset($oprs["apis"])) $oprs["apis"] = [];

        //如果 Orm 模块还未启动，直接返回
        if (Orm::$isInsed!==true) return $oprs;

        /**
         * !! ORM 数据库操作类 额外的 操作类型
         * !! 为当前启用的所有数据库以及各数据库中的所有数据模型，增加操作接口 api
         * !! 这些操作 统一通过 $orm->config->ctx["dbRoutePrefix"] 定义的 路由前缀访问，默认 db/...
         *      https://host/app_name/db
         *                              /dbn/api_name/arg1/arg2/...
         *                              /dbn/modk/api_name/arg1/arg2/...
         * !! 实际响应方法 统一指向方法 Orm::responseProxyer() 
         */
        $orm = Orm::$current;
        //收集所有 数据库以及所有数据模型中的 apis
        $orm->eachDb(function($dbn, $db) use (&$oprs) {
            //获取数据库配置类实例
            $cfger = $db instanceof DbConfig ? $db : $db->config;
            if (!$cfger instanceof DbConfig) return true;

            //数据库类中 定义的 api 方法，已在数据库参数解析阶段，被收集到 $dbconfig->ctx["oprs"] 中
            $dboprs = $cfger->ctx["oprs"] ?? null;
            if (Is::nemaso($dboprs)) {
                //合并到 $oprs
                $oprs = Arr::extend($oprs, $dboprs);
            }

            //收集此数据库中 所有数据模型的 apis
            foreach ($cfger->ctx["model"] as $modk => $modc) {
                //数据模型参数中的 apis 已在数据库参数解析阶段，被收集到 $dbconfig->ctx("model/$modk/oprs") 中
                $mdoprs = $modc["oprs"] ?? null;
                if (!Is::nemaso($mdoprs)) continue;
                //合并到 $oprs
                $oprs = Arr::extend($oprs, $mdoprs);
            }
            return true;
        });

        //返回找到的 操作列表 标准数据格式
        return $oprs;
    }



    /**
     * Db 数据库相关
     */

    /**
     * 获取数据库实例
     * 如果已经创建数据库实例，直接返回，否则调用 Db::current() 方法创建数据库实例，并连接数据库
     * @param String $dbn 数据库名 foo_bar 或 唯一key
     * @return Db 数据库实例
     */
    final public function db($dbn=null)
    {
        if (!$this->dbDefed($dbn)) return null;
        if ($this->dbInsed($dbn)) return static::$dbs[$this->dbKey($dbn)];
        
        //实例化 DbConfig 数据库参数配置类
        $dbcfger = $this->dbCfger($dbn);
        if (!$dbcfger instanceof DbConfig) {
            //数据库配置类无法实例化
            throw new OrmException("$dbn,无法创建数据库配置类实例", "orm/instance");
            return null;
        }

        //var_dump($dbcfger->ctx["class"]);
        //var_dump($dbcfger->ctx["key"]);
        //var_dump($dbcfger->ctx("model/usr/indexs"));

        //实例化数据库
        $dbcls = $dbcfger->ctx["class"];
        $dbo = new $dbcls($dbcfger);
        if (!$dbo instanceof Db) {
            //数据库实例化失败，报异常
            unset($dbo);
            throw new OrmException("$dbn,未知原因", "orm/instance");
            return null;
        }

        //实例缓存到 static::$dbs
        $dbk = $this->dbKey($dbn);
        static::$dbs[$dbk] = $dbo;

        //触发 db_insed 事件，传递当前数据库实例作为参数
        Event::trigger("db_insed", $this, $dbo);

        return $dbo;
    }

    /**
     * 根据数据库名，从 Orm 参数中 获取数据库配置参数
     * @param String $dbn 数据库名 foo_bar 或 唯一key
     * @return Array 数据库配置参数 []
     */
    public function dbConf($dbn=null)
    {
        if (!$this->dbDefed($dbn)) return null;
        $dbs = $this->config->ctx("dbs");

        //直接传入了 key
        if ($this->isDbKey($dbn)) {
            foreach ($dbs as $dbk => $dbc) {
                if (isset($dbc["key"]) && $dbc["key"]===$dbn) return $dbc;
            }
            return null;
        }

        //传入了数据库名
        $dbn = Str::snake($dbn, "_");
        return $dbs[$dbn] ?? [];
    }

    /**
     * 根据数据库名，从数据库实例中读取 DbConfig 实例，或者创建然后返回 DbConfig 实例
     * @param String $dbn 数据库名 foo_bar 或 唯一key
     * @return DbConfig 数据库配置类实例
     */
    public function dbCfger($dbn=null)
    {
        //已经实例化的数据库
        if ($this->dbInsed($dbn)) {
            return static::$dbs[$this->dbKey($dbn)]->config;
        }
        //未定义的数据库
        if ($this->dbDefed($dbn)!==true) return null;
        //已定义，还未实例化的数据库
        $dbkey = $this->dbKey($dbn);
        $indbs = static::$dbs[$dbkey] ?? null;
        //如果此数据库配置类已经实例化
        if ($indbs instanceof DbConfig) return $indbs;
        //实例化 DbConfig 数据库参数配置类
        $dbc = $this->dbConf($dbn);
        $cfger = new DbConfig($dbc, $dbn);
        //将 dbkey 写入 数据库 config 配置类的 context 中
        $cfger->ctx("", [
            "key" => $dbkey
        ]);
        //缓存
        static::$dbs[$dbkey] = $cfger;
        return $cfger;
    }

    /**
     * 根据数据库名 从 配置参数中查找数据库 唯一 key
     * @param String $dbn 数据库名 foo_bar 或 唯一key
     * @return String DB_**** 形式 数据库全局唯一 key
     */
    public function dbKey($dbn=null)
    {
        if (!$this->dbDefed($dbn)) return null;
        $dbc = $this->dbConf($dbn);
        if (!Is::nemarr($dbc)) return null;
        return $dbc["key"] ?? null;
    }

    /**
     * 检查数据库是否被定义
     * @param String $dbn 数据库名 foo_bar 或 唯一key
     * @return Bool
     */
    public function dbDefed($dbn=null)
    {
        if (!Is::nemstr($dbn)) return false;
        $dbs = $this->config->ctx("dbs");
        
        //直接传入了 key
        if ($this->isDbKey($dbn)) {
            foreach ($dbs as $dbk => $dbc) {
                if (isset($dbc["key"]) && $dbc["key"]===$dbn) return true;
            }
            return false;
        }

        //传入了 数据库名
        $dbn = Str::snake($dbn, "_");
        return isset($dbs[$dbn]) && Is::nemarr($dbs[$dbn]);
    }

    /**
     * 判断数据库是否已经实例化
     * @param String $dbn 数据库名 foo_bar 或 唯一key
     * @return Bool
     */
    public function dbInsed($dbn=null)
    {
        if (!$this->dbDefed($dbn)) return false;
        $dbk = $this->dbKey($dbn);
        if (!Is::nemstr($dbk)) return false;
        $dbs = static::$dbs;
        return isset($dbs[$dbk]) && $dbs[$dbk] instanceof Db;
    }

    /**
     * 判断一个字符串是否合法的 数据库唯一 key
     * 以 $this->config->ctx["dbKeyPrefix"] 为前缀，拼接 md5() 方法返回字符串 strlen()===32
     * @param String $dbk 要判断的字符串
     * @return Bool
     */
    public function isDbKey($dbk=null)
    {
        if (!Is::nemstr($dbk)) return false;
        $pre = $this->config->ctx["dbKeyPrefix"] ?? "DB_";
        $plen = strlen($pre);
        if (substr($dbk, 0, $plen)!==$pre) return false;
        return strlen($dbk) - $plen === 32;
    }

    /**
     * each db
     * 对所有启用的数据库 执行 回调方法
     * !! Orm::$dbs[] 中缓存的可能是 Db 或 DbConfig 实例，需要在 回调中自行判断
     * @param \Closure $closure 对每个 数据库 执行的 回调
     *      @param String $dbn 数据库名 foo_bar
     *      @param Db|DbConfig $db 数据库 或 数据库配置类  实例
     *      @return Mixed 返回：
     *              true    --> continue
     *              false   --> break
     *              any     --> 合并到结果数组中 [ dbn=>any, ... ]
     * @return Array|null 结果数组 associate 键名为 dbn
     */
    public function eachDb($closure=null)
    {
        if (!$closure instanceof \Closure) return null;
        //将当前 $this 绑定到 $closure，传入 __CLASS__ 确保可以放当前类的 受保护属性
        //$closure = $closure->bindTo($this, __CLASS__);
        //结果数组
        $rtn = [];

        //!! 使用 static::$dbs[] 作为循环依据
        //!! 因为存在 db 懒加载模式，缓存的 可能是 Db 或 DbConfig 实例
        foreach (static::$dbs as $dbkey => $db) {
            $dbn = null;
            if ($db instanceof Db) {
                //此数据库已经实例化
                $dbn = $db->name;
            } else if ($db instanceof DbConfig) {
                //数据库还未实例化，缓存的是 数据库配置类实例
                $dbn = $db->ctx["name"];
            } else {
                //其他类型数据 都是 无效数据
                continue;
            }
            //执行回调
            $rtni = $closure($dbn, $db);
            //true|false
            if ($rtni===true) continue;
            if ($rtni===false) break;
            //合并结果
            $rtn[$dbn] = $rtni;
        }

        return Is::nemaso($rtn) ? $rtn : null;
    }


    
    /**
     * 快捷访问 __get
     * !! 覆盖父类  在父类基础上增加
     * @param String $key 要访问的 不存在的 属性
     * @return Mixed
     */
    public function __get($key)
    {
        /**
         * 优先获取 数据库实例
         * $orm->Dbn            --> $orm->db(Dbn)
         * $orm->DB_*****       --> $orm->db(DB_*****)
         */
        if ($this->isDbKey($key)) return $this->db($key);
        if ($this->dbDefed($key)) return $this->db($key);

        /**
         * $this->dbs       -->  array_keys($this->config->ctx["dbs"])
         * 访问 当前 Orm 实例中所有已定义的 数据库
         */
        if ($key === "dbs") {
            $dbs = $this->config->ctx["dbs"] ?? [];
            return array_keys($dbs);
        }

        /**
         * $this->DbFooBar          --> $this->db(foo_bar)
         * $this->db_foo_bar        --> $this->db(foo_bar)
         */
        if (substr($key, 0, 2) === "Db" || substr($key, 0, 3)==="db_") {
            //!! 先直接检查一次是否存在定义的数据库，针对数据库名称 本身以 db_ 开头
            $dbk = Str::snake($key, "_");
            if ($this->dbDefed($dbk)) return $this->db($dbk);
            //去除 db_ 前缀
            $dbk = substr($dbk, 3);
            $kk = Str::snake($key, "_");
            if ($this->dbDefed($dbk)) return $this->db($dbk);
            return null;
        }

        //调用 父类的魔术方法 parent::__get($key)
        return parent::__get($key);
    }



    /**
     * 工具方法
     */

    /**
     * 获取当前 Orm 模块中的 所有可用数据库 库名 foo_bar
     * 
     */



    /**
     * 响应方法
     */
    /**
     * default
     * @desc ORM模块测试
     * @export api
     * @auth false
     * @pause false 资源输出不受WEB_PAUSE影响
     * @param Array $args url 参数
     * @return Array 输出
     */
    protected function default(...$args)
    {

        return Orm::info();
    }

    /**
     * 通用 /db/... 接口
     * 接管所有 Orm 操作数据库的直接请求，分发到 指定数据库->数据表->操作方法
     * !! 必须进行 UAC 控制
     * @param Array $args URI 参数，通常是  db_name/model_name/[api/]method_name/arg1/arg2/...
     * @return Array api 操作类型 一定返回 [] 数据
     */
    public function responseProxyer(...$args)
    {
        //Env
        $env = $this->Env();
        //Request 实例
        $req = $this->Req();
        //Response 实例
        $rep = $this->Rep();

        /**
         * !! 必须启用 Uac 模块
         * !! 入站请求必须通过 module/uac/middleware/in/AuthorityControl 中间件
         * !! 否则无法使用此接口
         * !! 某些情况下，应用可能只启用了 Orm 但是未启用 Uac ，此时，无法使用 db 接口操作数据库
         * !! 只能在具体的 响应方法内部 进行数据库操作
         * 
         * !! $env->dev === true 开发环境下：
         * !! 如果 $orm->config->ctx["disableDevDbRouteUac"] === true 则不检查 Uac 模块
         * !! 开发环境下，如果 启用 Uac 模块，则 AuthorityControl 中间件仍旧会执行，只不过不会拒绝无权限操作，
         * !! 而是将 denied 信息，写入 $orm->denied[] 中
         */
        $modUacOk = $this->ModOk('Uac');
        if ($env->dev!==true && $modUacOk!==true) return $rep::returnCode(404);
        if ($env->dev===true) {
            if ($this->config->ctx["disableDevDbRouteUac"]!==true && $modUacOk!==true) {
                return $rep::returnCode(404);
            } else {
                //!! 开发环境下，将 denied 信息，添加到返回数据中
                //var_dump($this->denied);
                $rep->setData([
                    "uac" => $modUacOk ? "enabled" : "disabled",
                    "denied" => Is::nemaso($this->denied) ? $this->denied : null
                ]);
            }
        }

        //获取匹配的 oprc 操作实例
        $oprc = $req->request->oprc;
        //url 参数
        if ($args[0]==="api") array_shift($args);
        //dbn
        $dbn = $oprc["dbn"] ?? null;
        //数据模型
        $modk = $oprc["modk"] ?? null;
        if (!Is::nemstr($dbn)) return $rep::returnCode(404);

        //要实际调用的 数据库|模型 类|实例 方法
        $proxy = $oprc["proxy"] ?? null;
        if (!Is::nemaso($proxy)) return $rep::returnCode(404);
        //是否静态方法
        $isStatic = isset($proxy["isStatic"]) && $proxy["isStatic"]===true;
        //实际调用的类
        $rc = $proxy["class"] ?? null;
        //是即调用的方法名
        $rm = $proxy["method"] ?? null;
        //类方法必须存在
        if (!class_exists($rc) || !method_exists($rc, $rm)) return $rep::returnCode(404);

        //开始调用

        //调用 数据库类 api
        if (!Is::nemstr($modk)) {
            return $this->db($dbn)->$rm(...$args);
        }

        //调用 模型 api
        if ($isStatic) {
            return $rc::$rm(...$args);
        }

        //调用 数据记录 api
        $rs = new $rc([]);
        return $rs->$rm(...$args);

        return $rep::returnCode(404);
    }
    
}