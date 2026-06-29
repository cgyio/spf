<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据库驱动类  继承自 Driver 抽象基类
 * 操作 sqlite3 数据库
 */

namespace Spf\module\orm\driver;

use Spf\module\orm\Driver;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Cls;
use Medoo\Medoo;

class Sqlite extends Driver 
{
    /**
     * 定义 Orm 默认支持的字段类型 针对 此数据库驱动类型的 映射
     * !! 必须覆盖 Orm 默认支持的 所有字段类型
     * !! 扩展的 数据库驱动子类必须指定，默认支持的 Mysql|Sqlite 不需要指定
     */
    protected static $columnTypeMap = [
        //原生字段类型
        //"varchar"       => "TEXT",
        //"char"          => "TEXT",
        //"text"          => "TEXT",
        //"integer"       => "INTEGER",
        //"bigint"        => "INTEGER",
        //"tinyint"       => "INTEGER",
        //"float"         => "REAL",

        //特殊类型
        //"json"          => "TEXT",
        //"switch"        => "INTEGER",
        //"time"          => "INTEGER",
        //"time_range"    => "TEXT",
        //"money"         => "INTEGER",
    ];



    /**
     * !! 子类必须指定
     */
    //此数据库类型名 foo_bar
    protected static $driver = "sqlite";
    /**
     * 用于 creation-sql 语句中的 约束字符串
     */
    //静态约束，支持的静态约束 在 module/orm/util/CreationSqlParser::$staticBinds[] 中定义
    protected static $staticBinds = [
        "unsigned"          => "UNSIGNED",
        "autoincrement"     => "AUTOINCREMENT",
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
        //必须指定数据库文件 *.db 路径，Path::find() 识别，且文件必须存在
        "type"      => "sqlite",
        "database"  => ""
    ];

    //默认的数据库文件后缀名
    protected static $dbext = ".db";



    /**
     * !! 必须实现这些抽象方法
     */

    /**
     * 判断当前数据库是否存在，用于 connect 前检查
     * @return Bool
     */
    public function exists()
    {

    }

    /**
     * 建库建表，在 exists = false 时执行
     * 将创建完整的 建库建表 SQL 并执行
     * @param \Closure $rollback 创建失败的情况下，自定义回滚方法
     *      @param \PDO $pdo 可能不存在
     *      @param \Exception $e 异常实例
     *      @return void
     * @return Bool
     */
    public function create($rollback=null)
    {

    }

    /**
     * 备份数据库
     * 可以单独指定备份文件路径，不指定则默认在 数据库配置文件路径下 同名文件夹的 backup/dbn_yyyymmddhhiiss.sql|db|...
     * 例如：数据库配置文件：www/app/app_foo/db/db_bar.json 则默认备份文件路径：
     *          www/app/app_foo/db/db_bar/backup/db_bar_20260101001122.sql
     * @param String $path 可以单独指定 备份文件路径，foo/bar --> foo/bar/dbn_yyyymmddhhiiss.sql|db|...
     * @param Bool $withRs 是否备份数据记录，默认 true，false 则只备份数据库结构
     * @return Bool|String 备份成功会返回备份文件路径，否则返回 false
     */
    public function backup($path=null, $withRs=true)
    {
        
    }

    /**
     * 用备份的数据库 恢复数据
     * @param String $file 备份文件，默认不指定，自动使用 数据库 backup 路径下 最新的备份数据
     * @return Bool|String 备份恢复成功会返回备份文件路径，否则返回 false
     */
    public function restore($file=null)
    {
        
    }

    /**
     * 重建数据库
     * @param Bool $withrs 是否重建数据记录，默认 true
     * @param \Closure $rollback 自定义错误回滚
     * @return Bool
     */
    public function recreate($withrs=true, $rollback=null)
    {
        
    }

    /**
     * 创建 数据表
     * @param String $modk 表名 foo_bar 或 fooBar
     * @param Array|RecordSet $rs 如果传入数据记录，则插入数据
     * @param \Closure $rollback 自定义错误回滚
     * @return Bool
     */
    public function createTable($modk, $rs=null, $rollback=null)
    {
        
    }

    /**
     * 重建数据表
     * @param String $modk 表名 foo_bar 或 fooBar
     * @param Bool $withrs 是否重建数据记录，默认 true
     * @param \Closure $rollback 自定义错误回滚
     * @return Bool
     */
    public function recreateTable($modk, $withrs=true, $rollback=null)
    {
        
    }

    /**
     * 获取某张表的 creation-sql
     * !! 不同的数据库 creation-sql 可能不相同，需要各自实现
     * @param String $modk 表名 foo_bar 或 fooBar
     * @return Array|null 包含一条或多条 SQL 语句的数组
     */
    public function getTableCreationSql($modk)
    {
        
    }

    /**
     * 判断传入的 connect 参数是否有效
     * @param Array $connect 要检查的 connect 连接参数
     * @return Bool
     */
    public static function ensureConnectOption($connect=[])
    {
        if (!Is::nemarr($connect)) return false;
        $dtp = $connect["type"] ?? "sqlite";
        $dbf = $connect["database"] ?? null;
        if ($dtp!=="sqlite" || !Is::nemstr($dbf)) return false;
        //查找文件
        if (substr($dbf, -3)!==static::$dbext) $dbf .= static::$dbext;
        $dbp = Path::find($dbf, Path::FIND_FILE);
        if (!file_exists($dbp)) return false;
        return true;
    }

}