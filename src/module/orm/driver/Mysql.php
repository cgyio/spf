<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据库驱动类  继承自 Driver 抽象基类
 * 操作 Mysql 数据库
 * !! 兼容低版本，不支持 新版特性   例如：  json 数据类型，CURRENT_* 系列方法
 */

namespace Spf\module\orm\driver;

use Spf\Runtime;
use Spf\module\orm\Driver;
use Spf\module\orm\OrmException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Cls;
use Medoo\Medoo;

class Mysql extends Driver 
{
    /**
     * 定义 Orm 默认支持的字段类型 针对 此数据库驱动类型的 映射
     * !! 必须覆盖 Orm 默认支持的 所有字段类型
     * !! 扩展的 数据库驱动子类必须指定，默认支持的 Mysql|Sqlite 不需要指定
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
        //"json"          => "LONGTEXT",
        //"switch"        => "TINYINT",
        //"time"          => "BIGINT",
        //"time_range"    => "LONGTEXT",
        //"money"         => "BIGINT",
    ];



    /**
     * !! 子类必须指定
     */
    //此数据库类型名 foo_bar
    protected static $driver = "mysql";
    /**
     * 用于 creation-sql 语句中的 约束字符串
     */
    //静态约束，支持的静态约束 在 module/orm/util/CreationSqlParser::$staticBinds[] 中定义
    protected static $staticBinds = [
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
        //使用 Medoo 库执行底层数据库操作，连接参数使用 Medoo 库参数形式
        "type"      => "mysql",
        "host"      => "",
        "port"      => 3306,
        "database"  => "",
        "username"  => "",
        "password"  => "",

        "charset"   => "utf8mb4",
        "collation" => "utf8mb4_general_ci",
        
        //可扩展...
    ];

    /**
     * 在 不指定 database 的情况下连接 数据库
     * @param Bool $silence 在连接不成功时，是否报异常，默认 true 不报异常
     * @return \PDO|null 连接不成功 返回 null
     */
    private function connectNoDb($silence=true)
    {
        //连接参数 opt 已处理过
        $opt = $this->opt;
        $dbn = $opt["database"];
        $dsn = "mysql:host=".$opt["host"].";port=".$opt["port"].";charset=".$opt["charset"];
        //准备 pdo
        $pdo = null;

        try {
            //pdo
            $pdo = new \PDO($dsn, $opt["username"], $opt["password"]);
            //pdo 异常
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            //关闭模拟预处理（安全）
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false); 

            return $pdo;
        } catch (\Exception $e) {
            //连接失败
            if (!$silence) {
                throw new OrmException("$dbn,创建 PDO 连接时发生错误：".OrmException::rawEncode($e->getMessage()), "db/connect");
            }
            return null;
        }
    }




    /**
     * !! 数据库驱动子类，必须实现这些抽象方法
     */

    /**
     * 判断当前数据库是否存在，用于 connect 前检查
     * @return Bool
     */
    public function exists()
    {
        //连接 数据库 不指定 database 连接不成功 报异常
        $pdo = $this->connectNoDb(false);
        if (empty($pdo)) return false;
        
        //连接参数 opt 已处理过
        $opt = $this->opt;
        $dbn = $opt["database"];

        try {

            //检查数据库是否存在
            $check = $pdo->prepare("
                SELECT SCHEMA_NAME 
                FROM INFORMATION_SCHEMA.SCHEMATA 
                WHERE SCHEMA_NAME = ?
            ");
            $check->execute([$dbn]);
            //是否存在
            $exists = $check->rowCount() > 0;

            //手动关闭连接，因为本次会话不一定立即结束
            $check = null;
            $pdo = null;
            
            return $exists;
        } catch (\Exception $e) {
            //查询错误
            $pdo = null;
            throw new OrmException("$dbn,无法检查数据库是否存在，".$e->getMessage(), "db/connect");
        }
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
        //opt
        $opt = $this->opt;
        //dbn
        $dbn = $opt["database"];
        //数据库配置参数
        $cfger = $this->db->config;
        $ctx = $cfger->ctx;
        //检查是否存在 建库建表 SQL 缓存
        $sqlCache = $ctx["CreationSql"] ?? null;
        
        if (Runtime::$env->dev!==true && Is::nemidx($sqlCache)) {
            //如果存在缓存，直接使用
            $execs = $sqlCache;
        } else {
            //不存在缓存，则创建
            $sql = [];
            $execs = [];

            //建库
            $sql[] = "CREATE DATABASE IF NOT EXISTS `$dbn`";
            //charset
            $sql[] = "DEFAULT CHARACTER SET ".$opt["charset"];
            //collation
            $sql[] = "DEFAULT COLLATE ".$opt["collation"];
            $execs[] = implode(" ", $sql);
            $sql = [];

            //使用此库
            $execs[] = "USE `$dbn`";

            //建表
            $mods = $ctx["model"];
            foreach ($ctx["model"] as $modk => $modc) {
                //表的 creation-sql 含 索引创建语句
                $csqls = $this->getTableCreationSql($modk);
                if (!Is::nemidx($csqls)) continue;
                //合并到 execs[]
                $execs = array_merge($execs, $csqls);
            }

            //写入 $db->config 并缓存
            if (Is::nemidx($execs)) {
                $this->db->config->ctx("CreationSql", $execs);
            } else {
                //生成 SQL 出错
                return false;
            }
        }

        //var_dump($execs);

        //准备 pdo 连接失败报异常
        $pdo = $this->connectNoDb(false);
        if (empty($pdo)) return false;

        try {
            //开始依次执行语句
            for ($i=0;$i<count($execs);$i++) {
                $rtn = $pdo->exec($execs[$i]);
            }

            //手动关闭连接，因为本次会话不一定立即结束
            $pdo = null;

            //!! 立即 backup 一次
            $this->backup(null, false);
            
            return true;

        } catch (\Exception $e) {
            //建库建表错误

            //如果存在 自定义回滚方法
            if ($rollback instanceof \Closure) {
                $rollback($pdo, $e);
                return false;
            }

            //不存在自定义回滚，只报异常
            $pdo = null;
            throw new OrmException("$dbn,".OrmException::rawEncode($e->getMessage()), "db/create");
            
            return false;
        }
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
        //opt
        $opt = $this->opt;
        $dbn = $opt["database"];

        //备份文件 路径
        $bfp = null;
        
        //如果指定了备份路径
        if (Is::nemstr($path)) {
            $p = Path::find($path, Path::FIND_DIR);
            if (is_dir($p)) {
                $dir = $p;
            } else {
                //路径不存在，则 自动向上级 依次创建
                if (Path::mkdir($path, 0755)!==true) {
                    //路径创建失败
                    return false;
                }
                //获取正确路径
                $dir = Path::find($path, Path::FIND_DIR);
            }
            //文件路径
            $bfp = rtrim($dir, DS).DS.$dbn."_".date("YmdHis").".sql";
        }
        
        //未指定备份路径，使用默认路径
        if (!Is::nemstr($bfp)) $bfp = $this->getDefaultBackupFilePath(".sql");

        //pdo 不报错
        $pdo = $this->connectNoDb();
        if (!$pdo) return false;

        try {
            
            // 切换到目标库
            $pdo->exec("USE `$dbn`");
    
            // 获取所有表
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    
            foreach ($tables as $table) {
                // 1. 导出表结构
                $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
                $create = $stmt->fetch(\PDO::FETCH_ASSOC);
                $sql .= "-- 表结构：$table\n";
                $sql .= $create['Create Table'] . ";\n\n";
    
                if (!$withRs) continue;
    
                // 2. 导出数据
                $stmt = $pdo->query("SELECT * FROM `$table`");
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
                if (count($rows) === 0) continue;
    
                $sql .= "-- 数据：$table\n";
                foreach ($rows as $row) {
                    $cols = array_map(function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, array_values($row));
                    $sql .= "INSERT INTO `$table` VALUES (" . implode(',', $cols) . ");\n";
                }
                $sql .= "\n";
            }
    
            // 写入文件
            Path::mkfile($bfp, $sql);

            $pdo = null;

            return $bfp;
        } catch (\Exception $e) {
            $pdo = null;
            throw new OrmException("$dbn,".OrmException::rawEncode($e->getMessage()), "db/backup");
            return false;
        }

        return false;

        //!! 使用 mysqldump 命令
        if ($withRs) {
            $cmdl = "mysqldump -h%s -P%s -u%s -p%s %s";
        } else {
            $cmdl = "mysqldump -d -h%s -P%s -u%s -p%s %s";
        }
        //创建命令
        $cmd = sprintf(
            $cmdl,
            $opt["host"],
            $opt["port"],
            $opt["username"],
            $opt["password"],
            $dbn
        );
        //通过 Runtime::procExec 执行命令
        $result = Runtime::procExec($cmd);
        var_dump($result);
        //备份成功
        if ($result["status"] === 0) {
            //获取命令返回数据
            $stdout = $result["stdout"];
            //写入文件，自动创建
            Path::mkfile($bfp, $stdout);
            return $bfp;
        }
        
        return false;
    }

    /**
     * 用备份的数据库 恢复数据
     * @param String $file 备份文件，默认不指定，自动使用 数据库 backup 路径下 最新的备份数据
     * @return Bool|String 备份恢复成功会返回备份文件路径，否则返回 false
     */
    public function restore($file=null)
    {
        //opt
        $opt = $this->opt;
        $dbn = $opt["database"];
        //数据库配置参数
        $cfger = $this->db->config;
        $ctx = $cfger->ctx;
        //默认备份到 数据库配置文件 对应目录
        $dir = rtrim($ctx["dbroot"], DS).DS."backup";

        //备份文件
        $bfp = null;
        
        //如果指定了备份文件
        if (Is::nemstr($file)) {
            //自动后缀
            if (substr($file, -4)!==".sql") $file .= ".sql";
            //首先检查文件是否存在
            $p = Path::find($file, Path::FIND_FILE);
            //如果不存在，则 在 默认备份路径下 查找
            if (!file_exists($p)) $p = Path::find($dir.DS.ltrim($file), Path::FIND_FILE);
            if (file_exists($p)) {
                //使用
                $bfp = $p;
            } else {
                //未找到备份文件，返回 false
                return false;
            }
        }

        //在 默认备份路径下 查找最新备份
        if (!Is::nemstr($bfp)) {
            //如果 默认备份路径不存在 则 自动向上级 依次创建
            if (!is_dir($dir)) {
                if (Path::mkdir($dir, 0755)!==true) {
                    //创建失败
                    return false;
                }
            }
            //在 默认备份路径中 查找最新的备份文件
            $dh = opendir($dir);
            $cur = "";
            while(false!==($fn = readdir($dh))) {
                if ($fn==="." || $fn===".." || is_dir($dir.DS.$fn)) continue;
                if (substr($fn, -4)!==".sql") continue;
                //提取 备份日期字符串
                $bds = array_slice(explode("_", substr($fn, 0, -4)),-1)[0];
                if (!is_numeric($bds)) continue;
                if ($cur==="") {
                    $cur = $bds;
                } else if ((int)$cur<=(int)$bds) {
                    $cur = $bds;
                }
            }
            closedir($dh);
            //出错
            if (!Is::nemstr($cur)) return false;
            //找到 最新备份
            $bfp = $dir.DS.$dbn."_".$cur.".sql";
        }

        var_dump($bfp);

        //读取 sql
        $sql = file_exists($bfp) ? file_get_contents($bfp) : null;
        if (!Is::nemstr($sql)) return false;
        var_dump($sql);
        //pdo 连接 失败不报异常
        $pdo = $this->connectNoDb();
        if (empty($pdo)) return false;
        var_dump($pdo);

        try {

            $pdo->exec("USE `$dbn`");
            //先删除表
            foreach ($cfger->ctx["model"] as $modk => $modc) {
                $pdo->exec("DROP TABLE IF EXISTS `$modk`");
            }
            //执行 备份文件中的 sql
            $pdo->exec($sql);

            //手动关闭连接，因为本次会话不一定立即结束
            $pdo = null;
            
            return $bfp;
            
        } catch (\Exception $e) {
            //查询错误
            $pdo = null;
            throw new OrmException("$dbn,".OrmException::rawEncode($e->getMessage()), "db/restore");
            return false;
        }
    }

    /**
     * 重建数据库
     * @param Bool $withrs 是否重建数据记录，默认 true
     * @param \Closure $rollback 自定义错误回滚
     * @return Bool
     */
    public function recreate($withrs=true, $rollback=null)
    {
        $dbn = $this->opt["database"];
        //!! 带记录备份数据库
        $this->backup();

        if (empty($pdo = $this->connectNoDb(false))) return false;
        try {
            $pdo->exec("DROP DATABASE IF EXISTS `$dbn`");
            $pdo = null;

        } catch (\Exception $e) {
            //如果存在 自定义回滚方法
            if ($rollback instanceof \Closure) {
                $rollback($pdo, $e);
                return false;
            }
            //未定义 回滚方法
            $pdo = null;
            throw new OrmException("$dbn,删除数据库时发生错误：".OrmException::rawEncode($e->getMessage()), "db/create");
            return false;
        }

        //建库建表
        return $this->create($rollback);
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
        //creation-sql
        $sql = $this->getTableCreationSql($modk);
        if (!Is::nemidx($sql)) return false;

        //当前库名
        $dbn = $this->opt["database"];

        //准备 pdo 连接失败报异常
        $pdo = $this->connectNoDb(false);
        if (empty($pdo)) return false;

        try {
            //使用当前库
            $pdo->exec("USE `$dbn`");

            //开始依次执行语句
            for ($i=0;$i<count($sql);$i++) {
                $rtn = $pdo->exec($sql[$i]);
            }

            //手动关闭连接，因为本次会话不一定立即结束
            $pdo = null;

            //!! 立即 backup 一次
            $this->backup(null, false);
            
            return true;

        } catch (\Exception $e) {
            //如果存在 自定义回滚方法
            if ($rollback instanceof \Closure) {
                $rollback($pdo, $e);
                return false;
            }

            //不存在自定义回滚，只报异常
            $pdo = null;
            throw new OrmException("$dbn,$modk,".OrmException::rawEncode($e->getMessage()), "db/create_table");
            
            return false;
        }

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
        if (!Is::nemstr($modk) || $this->db->hasModel($modk)===false) return false;
        //!! 带记录备份数据库
        $this->backup();

        //当前库名
        $dbn = $this->opt["database"];
        //表名
        $modk = Str::snake($modk,"_");
        //当前表的 全部记录
        $rs = null;

        if (empty($pdo = $this->connectNoDb(false))) return false;
        try {
            //使用当前库
            $pdo->exec("USE `$dbn`");
            //如果要重建数据记录，则先获取全部记录
            $rs = $pdo->query("SELECT * FROM `$modk`")->fetchAll(\PDO::FETCH_ASSOC);
            //删除表
            $pdo->exec("DROP TABLE IF EXISTS `$modk`");
            $pdo = null;

        } catch (\Exception $e) {
            //如果存在 自定义回滚方法
            if ($rollback instanceof \Closure) {
                $rollback($pdo, $e);
                return false;
            }
            //未定义 回滚方法
            $pdo = null;
            throw new OrmException("$dbn,$modk,删除数据表时发生错误：".OrmException::rawEncode($e->getMessage()), "db/create_table");
            return false;
        }

        //建库建表
        return $this->createTable($modk, $rs, $rollback);
    }

    /**
     * 获取某张表的 creation-sql
     * !! 不同的数据库 creation-sql 不相同，需要各自实现
     * @param String $modk 表名 foo_bar 或 fooBar
     * @return Array|null 包含一条或多条 SQL 语句的数组
     */
    public function getTableCreationSql($modk)
    {
        if (!Is::nemstr($modk) || $this->db->hasModel($modk)===false) return null;
        $modk = Str::snake($modk,"_");
        $modc = $this->db->config->ctx("model/$modk");
        $creations = $modc["creation"] ?? [];
        if (!Is::nemaso($creations)) return null;

        $sql = [];

        //creation-sql
        $csql_h = "CREATE TABLE IF NOT EXISTS `$modk` (";
        $csql_t = ") ENGINE=InnoDB DEFAULT CHARSET=".$this->opt["charset"]." COMMENT='".addslashes($modc["desc"])."'";
        $csqls = [];
        foreach ($creations as $colk => $csql) {
            $csqls[] = "`$colk` ".$csql;
        }
        $sql[] = $csql_h.implode(", ", $csqls).$csql_t;

        //索引
        $idxs = $modc["indexs"];
        if (Is::nemarr($idxs)) {
            foreach ($idxs as $idxk => $idxc) {
                //!! mysql 5.x 不支持 CREATE INDEX IF NOT EXISTS
                //$sql[] = "DROP INDEX IF EXISTS `".$idxk."` ON `".$modk."`";
                $sql[] = "CREATE INDEX `".$idxk."` ON `".$modk."`".$idxc;
            }
        }

        return $sql;
    }

    /**
     * 判断传入的 connect 参数是否有效
     * @param Array $connect 要检查的 connect 连接参数
     * @return Bool
     */
    public static function ensureConnectOption($connect=[])
    {
        if (!Is::nemarr($connect)) return false;
        $dtp = $connect["type"] ?? "mysql";
        if ($dtp!=="mysql") return false;

        //必须的 连接参数项
        $needs = explode(",", "host,database,username,password");
        $defs = array_merge([], array_keys($connect));
        if (Is::nemarr(array_diff($needs, $defs))===true) return false;

        //定义的参数 必须是 非空字符
        foreach ($needs as $k) {
            if (!Is::nemstr($connect[$k])) return false;
        }
        
        return true;
    }

}