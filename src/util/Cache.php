<?php
/**
 * 框架 特殊工具类
 * 缓存 读取/转换/写入
 * 支持不同 格式的 缓存文件
 */

namespace Spf\util;

class Cache extends SpecialUtil
{
    /**
     * 此工具 在启动参数中的 参数定义
     *  [
     *      "util" => [
     *          "util_name" => [
     *              # 如需开启某个 特殊工具，设为 true
     *              "enable" => true|false, 是否启用
     *              ... 其他参数
     *          ],
     *      ]
     *  ]
     * !! 覆盖父类静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = false;
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];
    
    /**
     * 缓存文件类型 默认 .json
     */
    protected static $ext = ".json";
    //支持的 缓存文件类型 
    protected static $exts = [
        ".json", ".php",
        //TODO: .xml | .yml | ...
    ];

    /**
     * 缓存内容中的 特殊数据项
     */
    //时间戳
    protected static $timeKey = "__CACHE_TIME__";
    //缓存数据读取到 指定对象中后，标记 这是缓存数据
    protected static $signKey = "__CACHE_SIGN__";

    //默认的缓存过期时间
    protected static $expire = 60*60;  // 1h

    /**
     * 读取 缓存内容
     * @param String $path 要读取的缓存文件路径 Path::find 路径，不存在则会尝试创建
     * @return Array|null
     */
    public static function read($path)
    {
        //框架必须开启了 运行时缓存
        if (self::$enable !== true) return null;

        //必须指定缓存文件路径
        if (!Is::nemstr($path)) return null;
        //自动补全缓存文件后缀名
        $path = self::suffix($path);
        //缓存文件类型
        $ext = Path::ext($path);
        //根据缓存文件类型 获取 readFoobar 方法
        $m = "read".ucfirst(substr($ext, 1));
        //read 方法不存在，表示不支持 此类型的缓存文件
        if (!method_exists(self::class, $m)) return null;
        //缓存文件路径
        $cf = Path::find($path, Path::FIND_FILE);
        //缓存文件不存在，返回空数据
        if (!file_exists($cf)) return [];

        //根据 文件类型 读取缓存文件数据
        $cache = self::$m($cf);
        if (empty($cache)) return [];
        
        //检查是否过期
        $tk = self::$timeKey;
        $sk = self::$signKey;
        $exp = self::$expire;
        $ct = $cache[$tk] ?? null;
        $ct = is_null($ct) ? 0 : strtotime($ct);
        //缓存过期，不读取
        if ($ct<=0 || time()-$ct>$exp) return [];

        //清除缓存中的 时间戳
        unset($cache[$tk]);
        //增加标记
        $cache[$sk] = true;
        //返回数据
        return $cache;
    }

    /**
     * 写入 缓存内容
     * @param String $path 要读取的缓存文件路径 Path::find 路径，不存在则会尝试创建
     * @param Array $data 要写入缓存的 数据
     * @return Bool
     */
    public static function save($path, $data=[])
    {
        //!! 不论是否 开启了运行时缓存 都执行 缓存写入
        //if (self::$enable !== true) return false;

        //必须指定缓存文件路径
        if (!Is::nemstr($path)) return false;
        //自动补全缓存文件后缀名
        $path = self::suffix($path);
        //缓存文件类型
        $ext = Path::ext($path);
        //根据 缓存文件类型 取得 saveFoobar 方法名
        $m = "save".ucfirst(substr($ext, 1));
        //save 方法不存在，表示不支持 此类型缓存文件
        if (!method_exists(self::class, $m)) return false;
        //缓存文件路径
        $cf = Path::find($path, Path::FIND_FILE);
        //缓存文件不存在，则创建
        if (!file_exists($cf)) {
            if (Path::mkfile($path)!==true) {
                //缓存文件创建失败
                return false;
            }
            //缓存文件创建成功，获取 新建的文件
            $cf = Path::find($path, Path::FIND_FILE);
        }

        //准备写入数据
        $tk = self::$timeKey;
        $sk = self::$signKey;
        //去除 缓存标记
        unset($data[$sk]);
        //添加 缓存时间
        $data[$tk] = date("Y-m-d H:i:s", time());

        //根据 缓存文件类型 转换数据 并写入文件
        return self::$m($cf, $data);
    }

    /**
     * 将内存中的 Closure 持久化到 指定的 php 文件中
     * !! 这些 Closure 匿名函数 必须显式的定义在某个文件中，不能是动态生成的(例如通过 eval 生成的)
     * @param String $path 要写入的 php 文件路径，不存在则尝试创建
     * @param String|Array $key Closure 在 Array 代码中的 键名  或者  一个保存了多个 Closure 的关联数组
     * @param Closure $closure 要保存的 Closure 匿名函数
     * @return Bool
     */
    public static function saveClosure($path, $key, $closure=null)
    {
        //!! 不论是否 开启了运行时缓存 都执行 缓存写入
        //if (self::$enable !== true) return false;

        //必须指定缓存文件路径
        if (!Is::nemstr($path)) return false;
        
        //缓存文件路径
        $cf = Path::find($path, Path::FIND_FILE);
        //缓存文件不存在，则创建
        if (!file_exists($cf)) {
            if (Path::mkfile($path)!==true) {
                //缓存文件创建失败
                return false;
            }
            //缓存文件创建成功，获取 新建的文件
            $cf = Path::find($path, Path::FIND_FILE);
        }
        
        //写入 单个 Closure 使用 追加模式
        if (Is::nemstr($key) && $closure instanceof \Closure) {
            $cnt = file_get_contents($cf);
            $isnew = !Is::nemstr(trim($cnt));
            if ($isnew) {
                $cnt = "<?php\n\nreturn [\n\n";
            } else {
                $cnt = "\n\n";
            }
            //写入
            $cnt .= "\"$key\" => ";
            $source = self::getClosureSource($closure);
            if (!Is::nemstr($source)) return false;
            $cnt .= $source;
            $cnt .= ",\n\n";
            if ($isnew) $cnt .= "];\n\n";
        }

        //一次性写入多个 Closure 使用 覆盖模式
        if (Is::nemarr($key) && Is::associate($key)) {
            $cnt = "<?php\n\nreturn [\n\n";
            foreach ($key as $fn => $closure) {
                if (!$closure instanceof \Closure) continue;
                $source = self::getClosureSource($closure);
                if (!Is::nemstr($source)) continue;
                $cnt .= "\"$fn\" => ".$source.",\n\n";
            }

            //增加 缓存时间戳
            $tk = self::$timeKey;
            $cnt .= "\"$tk\" => \"".date("Y-m-d H:i:s", time())."\",\n\n";

            //结尾
            $cnt .= "];\n\n";
        }

        //写入文件
        if (isset($cnt)) return file_put_contents($cf, $cnt) !== false;

        return false;
    }

    /**
     * 提取匿名函数的源代码
     * @param Closure $closure 匿名函数
     * @return string 函数源代码字符串
     */
    protected static function getClosureSource(\Closure $closure) {
        $reflection = new \ReflectionFunction($closure);
        $fn = $reflection->getFileName();
        if ($fn === false) return null;
        $file = new \SplFileObject($fn);
        
        // 定位到函数定义的起始行
        $file->seek($reflection->getStartLine() - 1);
        
        $source = '';
        // 读取从起始行到结束行的代码
        for ($i = $reflection->getStartLine(); $i <= $reflection->getEndLine(); $i++) {
            //去除第一行可能存在的 "" => 
            if ($i == $reflection->getStartLine()) {
                $src = $file->current();
                if (strpos($src,"=>")!==false) {
                    $src = implode("=>", array_slice(explode("=>",$src), 1));
                }
                $source .= $src;
                $file->next();
                continue;
            }

            //去除最后一行 可能存在的 ; , 
            if ($i == $reflection->getEndLine()) {
                $src = $file->current();
                $src = str_replace(["},", "};"],"}",$src);
                $source .= $src;
                $file->next();
                continue;
            }
            
            $source .= $file->current();
            $file->next();
        }
        
        // 清理代码（去除前后空白和多余换行）
        return trim($source);
    }



    /**
     * 自动补全 缓存文件后缀名
     * @param String $path 缓存文件路径
     * @return String 补全后的文件路径
     */
    protected static function suffix($path)
    {
        if (!Is::nemstr($path)) return $path;
        //当前采用的后缀名
        $ext = self::currentSuffix();
        //路径中自带的 后缀名 .xxx 形式
        $pext = Path::ext($path);
        if (Is::nemstr($pext) && in_array($pext, self::$exts)) {
            //路径已带有 支持的后缀名
            return $path;
        }
        //自动补全
        return $path.$ext;
    }

    /**
     * 根据 常量 以及 预设 确定当前采用什么 类型的 缓存文件
     * @return String 默认 .json
     */
    protected static function currentSuffix()
    {
        $cnst = "EXT_CACHE";
        return defined($cnst) ? constant($cnst) : self::$ext;
    }



    /**
     * 不同类型 缓存文件 读取/转换/写入 数据
     * readFoobar()
     * saveFoobar()
     */

    /**
     * .json
     * 读取并转换为 array
     * @param String $file 文件路径
     * @return Array
     */
    protected static function readJson($file)
    {
        if (!file_exists($file)) return [];
        //读取
        $cnt = file_get_contents($file);
        //转换返回
        return Conv::j2a($cnt);
    }

    /**
     * .json
     * array 转为文件内容 写入
     * @param String $file 文件路径
     * @param Array $data 要写入的数据
     * @return Bool
     */
    protected static function saveJson($file, $data=[])
    {
        if (!file_exists($file) || !Is::nemarr($data)) return false;
        //转换数据
        $cnt = Conv::a2j($data);
        //写入
        return file_put_contents($file, $cnt) !== false;
    }

    /**
     * .php
     * 读取并转换为 array
     * !! php 文件内 应 return [ ... ]
     * @param String $file 文件路径
     * @return Array
     */
    protected static function readPhp($file)
    {
        if (!file_exists($file)) return [];
        //读取
        $cnt = require($file);
        //直接返回
        return $cnt;
    }

    /**
     * .php
     * array 转为文件内容 写入
     * @param String $file 文件路径
     * @param Array $data 要写入的数据
     * @return Bool
     */
    protected static function savePhp($file, $data=[])
    {
        if (!file_exists($file) || !Is::nemarr($data)) return false;
        //转换数据
        $cnt = var_export($data, true);
        //增加 php 标记
        $cnt = "<?php\n\nreturn $cnt;\n";
        //写入
        return file_put_contents($file, $cnt) !== false;
    }

    /**
     * TODO: 支持 .xml|.yml| ...
     */
}