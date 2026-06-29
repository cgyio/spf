<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据库驱动 基类（抽象类）
 * 处理不同类型数据的 连接、curd 等操作，作为不同类型数据库的 操作兼容层
 * 
 * SPF-Orm 数据库操作模块，默认支持的数据库类型包括：
 *      mysql, sqlite
 * 
 * 
 * 
 * !! ExpandableResource 通用可扩展资源，可在 应用级>网站级>框架级 扩展此资源类
 * !! 需要在 自定义驱动类的 whenCollect() 方法中，适配所有支持的 Types 特殊字段类型
 */

namespace Spf\module\orm;

use Spf\Env;
use Spf\App;
use Spf\module\orm\Db;
use Spf\module\orm\Types;
use Spf\module\orm\config\model\parser\CreationSql;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Medoo\Medoo;

use Spf\traits\ExpandableResource;

abstract class Driver 
{
    //引用  可扩展底层资源类  特征
    use ExpandableResource;
    //!! trait 中要求的，子类不要覆盖
    protected static $exresName = "driver";
    protected static $exresClassPath = [
        "module/orm",
        "db"
    ];
    public static $isCollected = false;
    
    /**
     * 当某个 数据库驱动子类被 collect 收集时，创建 所有支持的字段类型 针对此数据库驱动的 类型映射
     * !! trait 中要求的，子类根据需要覆盖
     * @return Bool
     */
    protected static function whenCollect()
    {
        $rtn = true;

        //依次将 $columnTypeMap 中定义的 类型映射，写入 Types::$maps 中
        $map = static::$columnTypeMap;
        if (Is::nemarr($map)) $rtn = $rtn && Types::extendMapper(static::$driver, $map);

        //依次收集 creation-sql 中的 静态约束、其他约束
        $rtn = $rtn && CreationSql::collectStaticBinds(static::$driver, static::$staticBinds);
        $rtn = $rtn && CreationSql::collectExtraBinds(static::$driver, static::$extraBinds);

        return $rtn;
    }



    /**
     * 定义 Orm 默认支持的字段类型 针对 此数据库驱动类型的 映射
     * !! 必须覆盖 Orm 默认支持的 所有字段类型
     * !! 扩展的 数据库驱动子类必须指定，默认支持的 Mysql|Sqlite 不需要指定，Types 已默认支持
     */
    protected static $columnTypeMap = [
        //原生字段类型
        //"varchar"       => "VARCHAR(255)",
        //"char"          => "CHAR(255)",
        //"text"          => "TEXT",
        //"integer"       => "INT",
        //"bigint"        => "BIGINT",
        //"tinyint"       => "TINYINT",
        //"float"         => "FLOAT",

        //特殊类型
        //"json"          => "VARCHAR(2000)",
        //"switch"        => "TINYINT",
        //"time"          => "BIGINT",
        //"time_range"    => "VARCHAR(255)",
        //"money"         => "BIGINT",
    ];



    /**
     * !! 子类必须指定
     */
    //此数据库类型名 foo_bar
    protected static $driver = "";
    /**
     * 用于 creation-sql 语句中的 约束字符串
     */
    //静态约束，支持的静态约束 在 module/orm/util/CreationSql::$staticBinds[] 中定义
    protected static $staticBinds = [
        //如果 驱动子类不指定，则使用此默认写法(Mysql)
        "unsigned"          => "UNSIGNED",
        "autoincrement"     => "AUTO_INCREMENT",
        "primary"           => "PRIMARY KEY",
        "required"          => "NOT NULL",
        "unique"            => "UNIQUE",
    ];
    //此数据库类型 支持的 其他约束字符
    protected static $extraBinds = [];



    /**
     * 默认的数据库连接参数
     */
    protected static $dftConnect = [
        /*
        "database" => "",
        ...
        */
    ];

    //归一化为 $dftConnect 形式的 当前数据库连接参数
    protected $opt = [];

    //关联的 数据库实例
    protected $db = null;

    //检查数据库是否存在，缓存结果
    protected $dbExists = false;



    /**
     * 构造
     * @param Db $db 数据库实例
     * @return void
     */
    public function __construct($db=null)
    {
        if (!$db instanceof Db) return null;
        $this->db = $db;
        //合并默认连接参数
        $this->opt = Arr::extend([], static::$dftConnect, $db->config->ctx["connect"]);
    }

    /**
     * 连接数据库
     * !! 子类可根据需要，覆盖此方法
     * @param Array $opt 可以额外指定连接参数，这些参数将会覆盖到 $driver->opt[] 中，作为连接参数 
     * @return Medoo 底层用于实际操作数据库的 Medoo 实例
     */
    public function connect($opt=[])
    {
        $dbn = $this->db->name;

        //检查数据库是否存在
        if ($this->dbExists!==true) $this->dbExists = $this->exists();

        //如果数据库不存在，建库建表
        if ($this->dbExists!==true) {
            //建库建表
            $created = $this->create(function($pdo, $e) use ($dbn) {
                //开发环境，建库失败，直接删除
                if ($pdo && Env::$current->dev===true) {
                    $pdo->exec("DROP DATABASE IF EXISTS `$dbn`");
                    throw new OrmException($dbn.",创建数据库失败，".str_replace(",","，",$e->getMessage()), "db/connect");
                }
            });
            if ($created===false) {
                //建库建表失败
                throw new OrmException($dbn.",创建数据库失败，检查数据库配置参数", "db/connect");
                return null;
            }
        }

        //var_dump("db $dbn already exists");
        
        //开始 Medoo 连接，处理连接参数
        if (Is::nemaso($opt)) {
            $opt = Arr::extend([], $this->opt, $opt);
        } else {
            $opt = $this->opt;
        }

        //创建 Medoo 实例
        $medoo = new Medoo($opt);
        if (!$medoo instanceof Medoo) {
            //未能创建 Medoo 实例
            throw new OrmException($dbn.",未能创建 Medoo 实例，检查连接参数", "db/connect");
            return null;
        }

        return $medoo;
    }



    /**
     * !! 子类必须实现这些抽象方法
     */

    /**
     * 判断当前数据库是否存在，用于 connect 前检查
     * @return Bool
     */
    abstract public function exists();

    /**
     * 建库建表，在 exists = false 时执行
     * 将创建完整的 建库建表 SQL 并执行
     * @param \Closure $rollback 创建失败的情况下，自定义回滚方法
     *      @param \PDO $pdo 可能不存在
     *      @param \Exception $e 异常实例
     *      @return void
     * @return Bool
     */
    abstract public function create($rollback=null);

    /**
     * 备份数据库
     * 可以单独指定备份文件路径，不指定则默认在 数据库配置文件路径下 同名文件夹的 backup/dbn_yyyymmddhhiiss.sql|db|...
     * 例如：数据库配置文件：www/app/app_foo/db/db_bar.json 则默认备份文件路径：
     *          www/app/app_foo/db/db_bar/backup/db_bar_20260101001122.sql
     * @param String $path 可以单独指定 备份文件路径，foo/bar --> foo/bar/dbn_yyyymmddhhiiss.sql|db|...
     * @param Bool $withRs 是否备份数据记录，默认 true，false 则只备份数据库结构
     * @return Bool|String 备份成功会返回备份文件路径，否则返回 false
     */
    abstract public function backup($path=null, $withRs=true);

    /**
     * 用备份的数据库 恢复数据
     * @param String $file 备份文件，默认不指定，自动使用 数据库 backup 路径下 最新的备份数据
     * @return Bool|String 备份恢复成功会返回备份文件路径，否则返回 false
     */
    abstract public function restore($file=null);

    /**
     * 重建数据库
     * @param Bool $withrs 是否重建数据记录，默认 true
     * @param \Closure $rollback 自定义错误回滚
     * @return Bool
     */
    abstract public function recreate($withrs=true, $rollback=null);

    /**
     * 创建 数据表
     * @param String $modk 表名 foo_bar 或 fooBar
     * @param Array|RecordSet $rs 如果传入数据记录，则插入数据
     * @param \Closure $rollback 自定义错误回滚
     * @return Bool
     */
    abstract public function createTable($modk, $rs=null, $rollback=null);

    /**
     * 重建数据表
     * @param String $modk 表名 foo_bar 或 fooBar
     * @param Bool $withrs 是否重建数据记录，默认 true
     * @param \Closure $rollback 自定义错误回滚
     * @return Bool
     */
    abstract public function recreateTable($modk, $withrs=true, $rollback=null);

    /**
     * 获取某张表的 creation-sql
     * !! 不同的数据库 creation-sql 可能不相同，需要各自实现
     * @param String $modk 表名 foo_bar 或 fooBar
     * @return Array|null 包含一条或多条 SQL 语句的数组
     */
    abstract public function getTableCreationSql($modk);

    /**
     * 判断传入的 connect 参数是否有效
     * @param Array $connect 要检查的 connect 连接参数
     * @return Bool
     */
    abstract public static function ensureConnectOption($connect=[]);



    /**
     * 工具方法
     */

    /**
     * 创建备份文件的 默认路径
     *      默认位置：www/app/app_foo/db/db_bar/backup/db_bar_20260102090000.sql|db|...
     * @param String $ext 文件后缀名
     * @return String 当前时间的 默认备份文件路径  生成错误则返回 null
     */
    public function getDefaultBackupFilePath($ext=null)
    {
        if (!Is::nemstr($ext)) return null;
        if (substr($ext, 0,1)!==".") $ext = ".".$ext;
        
        //默认备份到 数据库配置文件 对应目录
        $dir = rtrim($this->db->config->ctx["dbroot"], DS).DS."backup";
        //如果不存在此路径，则自动依次向上级 检查并创建
        if (!is_dir($dir)) {
            //文件路径创建失败
            if (Path::mkdir($dir, 0755)!==true) return null;
        }

        //备份文件名，不检查是否存在
        $fn = $this->opt["database"]."_".date("YmdHis").$ext;
        return $dir.DS.$fn;
    }



    /**
     * 静态工具方法
     */

    /**
     * 返回当前数据库驱动的 static::$driver 驱动名 foo_bar 例如：mysql | sqlite | ...
     * @return String
     */
    final public static function driver()
    {
        return static::$driver;
    }
}