<?php
/**
 * Lib 前端库资源类的子类 JsModLib
 * 专门用于处理 保存在本地的 JS 库，此库有以下特征：
 *      0   存在一个主文件 core.js
 *      1   在下级 modules 文件夹中，存在多个 扩展包，可通过 foo.use(mod) 方式合并这些扩展包
 *      2   通常通过下列方式使用：
 *          import foo from '/lib_path/foo/mod1-mod2-mod3.js'   加载指定模块
 *          import foo from '/lib_path/foo/all.js'              加载所有模块
 *          import foo from '/lib_path/foo.js'                  加载默认模块
 *      3   可单独引用某个 模块：
 *          import foo from '/lib_path/foo.js'
 *          import mod1 from '/lib_path/foo/mod-mod1.js'
 *          import mod2 from '/lib_path/foo/mod-mod2.js'
 *          foo.use(mod1)
 *          foo.use(mod2)
 */

namespace Spf\module\src\resource\lib;

use Spf\module\src\Resource;
use Spf\module\src\resource\Lib;
use Spf\module\src\Mime;
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;
use Spf\util\Curl;

class JsModLib extends Lib 
{
    //当前请求的 库文件信息
    public $libfile = [
        //库版本号 具体的版本号，必须在 *.lib 文件中定义了
        "ver" => "",
        //需要加载的 JS 库内部 modules 名称数组
        "mods" => [],
        //是否仅输出 S 库内部 module 文件内容
        "onlymod" => false,

        //请求文件的 后缀名，必须在 exps 数组中
        //"ext" => "",
        //开关信息组成的 key 例如：dev-esm-browser 必须在 *.lib 文件中定义了
        //"key" => "",
        //当前请求的 文件信息 与 stdLibfile 结构一致
        //"info" => [],

    ];

    

    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //读取并处理 库定义数据
        $ctx = Conv::j2a($this->content);
        //合并 格式化为 标准图标库参数形式
        $ctx = Arr::extend(static::$stdLib, $ctx);

        //处理 stdFoobar 参数
        $this->fixStdProperties($ctx);

        //获取元数据
        $this->getLibMeta($ctx);
        $meta = $this->meta;

        /**
         * 将当前版本下的 modules 合并到 switch 开关数组中
         * 可通过 开关 控制是否需要合并这些 modules
         */
        $mods = $this->getSubModList();
        if (Is::nemarr($mods)) {
            foreach ($mods as $modn) {
                if (!isset(static::$stdParams[$modn])) {
                    static::$stdParams[$modn] = false;
                }
            }
        }

        //格式化 params 并根据 export 修改 ext|mime
        $this->formatParams();
        $ps = $this->params;

        //获取当前请求的 文件信息
        $this->getLibFile($ctx);

        //!! 不使用缓存，因为此 JS 库是本地文件，仅进行合并操作
        //判断是否需要加载缓存
        /*if ($this->useCache() === true) {
            //尝试读取缓存
            $cnt = $this->getCacheContent();
            if (Is::nemstr($cnt)) {
                //存在缓存文件 则 使用缓存的 content
                $this->content = $cnt;
                return $this;
            }
        }*/
        
        //开始从 cdn 读取文件
        $this->getLibFromCdn($ctx);
        
        //!! 不使用 fix 方法
        //对 cdn 返回的文件内容，按 info["fix"] 中指定的 方法序列，依次执行处理，返回处理后的 文件内容
        //$this->fixContentInQueue();
        
        //!! 不使用缓存
        //写入缓存文件
        //$this->saveCacheContent();

        //解析结束
        return $this;
    }



    /**
     * 工具方法
     */

    /**
     * 获取当前请求的 库文件信息 保存到 libfile 属性
     * !! 覆盖父类
     * @param Array $ctx
     * @return $this
     */
    protected function getLibFile($ctx=[])
    {
        $ps = $this->params;

        //获取当前请求的 文件信息
        $ver = $this->getLibVer();
        //所有 modules 列表
        $mods = $this->getSubModList();
        
        //要加载全部 modules
        if ($ps["all"]===true) {
            //加载所有 modules
            $this->libfile = [
                "ver" => $ver,
                "mods" => $mods,
                "onlymod" => false
            ];
            return $this;
        }

        //是否仅输出 某个 module 文件本身
        if ($ps["mod"] === true) {
            $mds = [];
            foreach ($mods as $modn) {
                if ($ps[$modn] === true) {
                    $mds[] = $modn;
                    break;
                }
            }
            $this->libfile = [
                "ver" => $ver,
                "mods" => $mds,
                "onlymod" => true
            ];
            return $this;
        }

        //筛选需要合并的 module
        $mods = array_filter($mods, function($modn) use ($ps) {
            return $ps[$modn] === true;
        });
        
        $this->libfile = [
            "ver" => $ver,
            "mods" => $mods,
            "onlymod" => false
        ];
        return $this;
    }

    /**
     * 从 cdn 获取库文件内容 保存到 content
     * !! 覆盖父类
     * @param Array $ctx
     * @return $this
     */
    protected function getLibFromCdn($ctx=[])
    {
        //当前请求的 文件信息
        $lf = $this->libfile;
        $ver = $lf["ver"];
        $mods = $lf["mods"];
        $only = $lf["onlymod"];
        $ext = $this->ext; //js
        $lib = $this->meta["lib"];
        $var = $this->meta["variable"];

        //处理 cdn 未指定的情况
        $cdn = $ctx["cdn"] ?? null;
        if (!Is::nemstr($cdn)) {
            //未指定 cdn，使用当前 *.lib 文件的路径
            $cdn = dirname($this->real).DS.$lib.DS;
            //转为 可通过 Path::find 获取的 相对路径
            $cdn = Path::rela($cdn);
        } else {
            //指定的 cdn 必须是某个本地路径
            $rela = Path::rela($cdn);
            if (Is::nemstr($rela)) $cdn = $rela;
        }
        $cdnp = Path::find($cdn, Path::FIND_DIR);
        if (!Is::nemstr($cdnp)) {
            //指定的 本地路径不存在，报错
            throw new SrcException("无法在指定位置找到 $lib.$ext 库", "resource/getcontent");
        }
        $cdnp = rtrim($cdnp, DS).DS.$ver.DS;

        //准备 rows
        $this->rows = [];

        //如果仅输出某个 module
        if ($only) {
            if (!Is::nemarr($mods)) {
                //为指定要输出的 module
                throw new SrcException("未指定要输出的 $lib.$ext 库模块文件", "resource/getcontent");
            }
            $modp = $cdnp."modules".DS.$mods[0].".".$ext;
            if (!file_exists($modp)) {
                //不存在 module 文件
                throw new SrcException("无法在指定位置找到 $lib.$ext 库的模块文件", "resource/getcontent");
            }
            //直接读取
            $this->content = file_get_contents($modp);
            return $this;
        }

        //core 文件
        $corep = $cdnp."core.js";
        if (!file_exists($corep)) {
            //不存在 core 核心文件
            throw new SrcException("无法在指定位置找到 $lib.$ext 库的核心文件", "resource/getcontent");
        }
        //core 资源实例
        $core = Resource::create($corep);
        if (!$core instanceof Resource) {
            //核心文件无法实例化
            throw new SrcException("无法读取 $lib.$ext 库的核心文件", "resource/getcontent");
        }
        $corerows = $core->export([
            "return" => "rows"
        ]);
        $this->rowComment(
            "$lib 库核心文件",
            "不要手动修改",
        );
        $this->rowEmpty(1);
        if (Is::nemarr($corerows)) {
            //去除 core 中的 export 语句
            $corerows = $this->stripExport($corerows, "");
            $this->rowAdd($corerows);
            $this->rowEmpty(3);
        }
        //是否资源实例
        unset($core);

        //依次合并 module 文件
        foreach ($mods as $modn) {
            $modp = $cdnp."modules".DS.$modn.".".$ext;
            if (!file_exists($modp)) continue;
            $modrows = $this->getFileRows($modp);
            if (Is::nemarr($modrows)) {
                //将 各模块中的 export 语句替换为 use 语句
                $modrows = $this->stripExport($modrows, "$var.use(%{var}%);");
                $this->rowAdd($modrows);
                $this->rowEmpty(3);
            }
        }

        //最后添加 export 语句
        $this->rowEmpty(3);
        $this->rowAdd("export default $var",";");
        
        //合并 rows 保存到 content
        $this->content = $this->rowCnt();

        return $this;
    }

    /**
     * 去除 rows 行数组中的 export default *** 语句
     * @param Array $rows
     * @param String $replace 替换为语句，默认 空字符，可指定为 foo bar %{var}%;
     * @return Array
     */
    protected function stripExport($rows=[], $replace="")
    {
        if (!Is::nemarr($rows)) return $rows;
        foreach ($rows as $i => $row) {
            if (substr(trim($row), 0, 6)!=="export") continue;
            $r = preg_replace("/\s+/"," ",trim($row));
            if (substr($r, 0, 14) !== "export default") continue;

            if ($replace === "") {
                //不替换，直接去除
                $rows[$i] = "";
            } else {
                //替换为指定语句，其中 %{var}% 指带 export default foo; 中的 foo
                $var = trim(str_replace(["export default", ";"], "", $r));
                $rps = str_replace("%{var}%", $var, $replace);
                $rows[$i] = $rps;
            }
        }
        return $rows;
    }

    /**
     * 获取此 JS 库内部 modules 文件夹路径，不检查是否存在
     * @return String
     */
    protected function getSubModDir()
    {
        $real = $this->real;
        $dir = dirname($real);
        $lib = $this->meta["lib"];
        $ver = $this->getLibVer();
        return $dir.DS.$lib.DS.$ver.DS."modules";
    }

    /**
     * 获取此 JS 库内部 modules 文件列表
     * @return Array
     */
    protected function getSubModList()
    {
        $modp = $this->getSubModDir();
        $modp = Path::find($modp, Path::FIND_DIR);
        if (empty($modp) || !is_dir($modp)) return [];
        //查找 module 文件
        $dh = opendir($modp);
        $mods = [];
        while (false !== ($fn = readdir($dh))) {
            //只查找当前文件夹下
            if (in_array($fn, [".",".."]) || is_dir($modp.DS.$fn)) continue;
            //只查找 js 文件
            if (strtolower(substr($fn, -3)) !== ".js") continue;
            $mods[] = substr($fn, 0, -3);
        }
        closedir($dh);
        return $mods;
    }
}