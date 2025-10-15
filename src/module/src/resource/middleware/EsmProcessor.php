<?php
/**
 * Resource 资源处理中间件 Processor 处理器子类
 * EsmProcessor 专门处理 js 代码资源 esm 相关操作
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\resource\Codex;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

class EsmProcessor extends Processor 
{
    //设置 当前中间件实例 在资源实例中的 属性名，不指定则自动生成 NS\middleware\FooBar  -->  foo_bar
    public static $cacheProperty = "esmer";

    /**
     * 可以指定 此处理器依赖的 其他处理器
     * 会在执行操作前检查 依赖的处理器 是否存在
     * !! 覆盖父类
     */
    public static $dependency = [
        "RowProcessor",
    ];

    /**
     * 通用静态参数
     */
    //合法的 JS 变量名正则
    public static $jsVarPattern = "[a-zA-Z_\$][a-zA-Z0-9_\$]*";
    //针对 匿名导出的情况，需要生成 不重复的 变量名，在此记录已使用过的 变量名
    public static $jsVariables = [];

    /**
     * export 相关的信息
     */
    //esm 状态
    public $esm = true;
    //使用 export *** 导出语句时 变量名收集
    public $vars = [
        /*
        # export default *** 导出形式
        "default" => [
            # export default [const|let|class|function] 变量名***
            "变量名" => "变量名",

            # export default [function() | class {} | {}] 匿名导出，自动生成变量名
            "VAR_foobar" => "BAR_foobar",
        
            # export default {变量名,变量名}
            "变量名,变量名" => ["变量名","变量名"],
        
            # export default {变量名 as 别名,变量名 as 别名}
            "变量名 as 别名,变量名 as 别名" => [
                "别名" => "变量名",
                "别名" => "变量名",
            ]
        ],
    
        # export *** 导出形式
        "normal" => [
            ...
        ],
        */
    ];

    //缓存经过 minify 压缩的 原始 js 代码
    protected $originCode = "";



    /**
     * 处理器初始化动作
     * !! Processor 子类 必须实现
     * @return $this
     */
    protected function initialize()
    {
        $res = $this->resource;
        //esm 状态，默认 keep 保持原样
        $this->esm = $res->params["esm"] ?? "keep"; //true;
        //缓存 原始 js 代码
        $this->originCode = $this->resource->content;

        return $this;
    }

    /**
     * create 阶段执行的操作
     * !! 覆盖父类
     * !! 需要 RowProcessor 已执行 stageCreate 方法
     * @return Bool
     */
    protected function stageCreate()
    {
        //创建阶段，判断当前 js 代码文件是否需要执行 import | merge 处理，执行这些处理将修改 $resource->rows 内容行数组
        $res = $this->resource;
        //执行操作标记
        $proc = false;
        if ($res->hasProcessor("merge") && Is::nemarr($res->params["merge"])) {
            //需要执行 merge 操作，直接调用 MergeProcessor->stageExport
            $res->MergeProcessor->callStageExport();
            //标记
            $proc = true;
        } else if ($res->hasProcessor("import") && Is::nemarr($res->imports)) {
            //需要执行 import 操作，直接调用 ImportProcessor->stageExport
            $res->ImportProcessor->callStageExport();
            //标记
            $proc = true;
        }

        //如果执行了额外的 import | merge 操作，需要更新 originCode 原始 js 代码
        if ($proc === true) $this->originCode = $this->resource->content;
        
        //创建 esm 参数 仅当 esm!==keep 时，处理 esm 导出语句
        if ($this->esm !== "keep") $this->parseEsm();

        return true;
    }

    /**
     * export 阶段执行的操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function stageExport()
    {
        //esm 状态
        $esm = $this->esm;

        //esm===keep 保持原样输出
        if ($esm === "keep") return $this->RowProcessor->callStageExport();

        //变量名数组
        $vars = $this->vars;
        if (!Is::nemarr($vars)) {
            //没获取到任何 export 信息，不做处理
            return $this->resource->content;
        }
        //默认导出
        $dftvs = $vars["default"] ?? [];
        //普通导出
        $norvs = $vars["normal"] ?? [];
        
        //rower
        $rower = $this->RowProcessor;
        //缓存当前 rows
        $rower->saveHistory();

        if ($esm === false) {
            //未开启 esm 的情况
            //comment
            $rower->rowComment(
                "未启用 ESM 导出，定义为全局变量",
                "!! 不要手动修改 !!"
            );
            $rower->rowEmpty(1);
            foreach ($vars as $vk => $vc) {
                if (!Is::nemarr($vc)) continue;
                foreach ($vc as $k => $v) {
                    if (strpos($k, " ")===false) {
                        $rower->rowAdd("window.$v = $v;","");
                    } else if (Is::nemarr($v)) {
                        foreach ($v as $i => $vn) {
                            if (is_int($i)) {
                                $rower->rowAdd("window.$vn = $vn;","");
                            } else {
                                $rower->rowAdd("window.$i = $vn;","");
                            }
                        }
                    }
                }
            }
        } else {
            //开启 esm
            //comment
            $rower->rowComment(
                "ESM 导出",
                "!! 不要手动修改 !!"
            );
            $rower->rowEmpty(1);
            foreach ($vars as $vk => $vc) {
                if (!Is::nemarr($vc)) continue;
                //export 语句前缀
                $pre = $vk==="default" ? "export default" : "export";
                foreach ($vc as $k => $v) {
                    if (strpos($k, " ")===false) {
                        $rower->rowAdd("$pre $v;","");
                    } else {
                        $ks = substr($k, -1) === ";" ? $k : $k.";";
                        $rower->rowAdd($ks,"");
                    }
                }
            }
        }

        //!! 调用 RowProcessor 的 stageExport 方法
        return $rower->callStageExport();
    }



    /**
     * stage 工具方法
     */

    /**
     * 处理 原始 js 代码中的 export 语句，将生成的 esm 参数保存到当前 Processor
     * !! 覆盖父类
     * @return $this
     */
    public function parseEsm()
    {
        //原始 js 代码
        $cnt = $this->originCode;
        //外部是否传入了 默认导出变量名
        $var = $this->resource->params["var"] ?? null;
        
        //如果原始代码 不含 export 导出语句
        if (static::hasExport($cnt) !== true) {
            if (Is::nemstr($var)) {
                //如果传入的 变量名
                $this->vars = Arr::extend([],[
                    "default" => [
                        $var => $var
                    ]
                ]);
            }
            return $this;
        } 

        //开始解析 导出语句
        $res = static::parseExportVars($cnt, $var);
        //修改后的 code 替换 $resource->content
        $code = $res["code"] ?? null;
        if (!Is::nemstr($code)) {
            //解析错误
            throw new SrcException("无法解析 JS 资源 ".$this->resource->name." 的 export 导出语句", "resource/getcontent");
        }
        $this->resource->content = $code;
        //重新执行 RowProcessor->stageCreate
        $this->RowProcessor->callStageCreate();

        //将解析得到的 到处变量名信息 写入 $this->vars
        unset($res["code"]);
        $this->vars = Arr::extend([], $res);

        return $this;
    }


    
    /**
     * 静态工具
     */

    /**
     * 判断代码中是否含有 export 导出语句
     * @param String $code 字符串
     * @return Bool 
     */
    public static function hasExport($code)
    {
        $mt = preg_match("/export\s+(default\s+)*((const|let|var|function|class|\{)|(".static::$jsVarPattern.")){1,}/", $code, $mts);
        return $mt === 1;
    }

    /**
     * 生成 不重复的 js 变量名
     * @param Int $len 字符串长度 默认 8
     * @return String
     */
    public static function uniqueJsVariable($len=8)
    {
        $vars = static::$jsVariables;
        $var = Str::nonce($len, false);
        while (in_array($var, $vars)) {
            $var = Str::nonce($len, false);
        }
        //记录此变量名
        static::$jsVariables[] = $var;
        //变量名不能以 数字开头，因此增加一个前缀
        return "VAR_".$var;
    }



    /**
     * export 导出语句 静态解析方法
     */

    /**
     * 从代码块中 解析 export 语句，生成 变量名称数组
     * @param String $code 代码语句，应为 minify 后的字符串
     * @param String|null $var 资源请求 URI 中定义的 默认导出变量名，默认 null
     * @return Array|null 返回解析得到的 所有 export 变量名数组，如果没有 export 相关语句，则返回 null
     *  [
     *      # export default *** 导出形式
     *      "default" => [
     *          # export default [const|let|class|function] 变量名***
     *          "变量名" => "变量名",
     * 
     *          # export default {变量名,变量名}
     *          "变量名,变量名" => ["变量名","变量名"],
     * 
     *          # export default {变量名 as 别名,变量名 as 别名}
     *          "变量名 as 别名,变量名 as 别名" => [
     *              "别名" => "变量名",
     *              "别名" => "变量名",
     *          ]
     *      ],
     * 
     *      # export *** 导出形式
     *      "normal" => [
     *          ...
     *      ],
     * 
     *      # 处理后的 代码字符串
     *      "code" => "...",
     *  ]
     */
    public static function parseExportVars($code, $var=null)
    {
        //先确保代码中存在 export 导出语句
        if (!Is::nemstr($code) || static::hasExport($code)!==true) return null;

        //处理结果
        $rtn = [
            "default" => [],
            "normal" => [],
        ];
        
        //依次执行下述 匹配方法
        $mts = [
            "matchExportVarsDefined",
            "matchExportVarsBatch",
            "matchExportVarsAnonymous",

            //此匹配方法 一定在最后执行
            "matchExportVarsDirect",
        ];
        //匹配
        foreach ($mts as $m) {
            for ($i=0;$i<=1;$i++) {
                //分别执行匹配 普通导出 和 默认导出 语句
                $dft = $i===0;
                //执行
                $res = static::$m($code, $dft, $var);
                if (!Is::nemarr($res)) continue;
                //更新 code
                $code = $res["code"];
                unset($res["code"]);
                //合并结果
                $rtn = Arr::extend($rtn, $res);
            }
        }

        //输出结果
        $rtn["code"] = $code;
        return $rtn;
    }

    /**
     * 解析 不同形式的导出语句
     * @param String $code 代码字符串
     * @param Bool $default 是否解析 default 默认导出语句，默认 true
     * @param String|null $var 传入 资源请求时传入的 默认导出变量名，默认 null
     * @return Array|null
     *  [
     *      "default" => [],
     *      "normal" => [],
     *      "code" => "...",
     *  ]
     */
    /**
     * 解析 直接定义 形式的导出语句：
     *      export[ default][ const|let|var] foo = ...
     *      export[ default][ function|class] foo[(|{)] ...
     */
    protected static function matchExportVarsDefined($code, $default=true, $var=null)
    {
        //导出语句正则
        $pts = [
            static::getExportPattern("/__PRE__(const|let|var)*\s*(__JSV__)\s*=/U", $default),
            static::getExportPattern("/__PRE__(function|class){1}\s+(__JSV__)\s*(\(|\{){1}/U", $default)
        ];
        
        //匹配到的 vars
        $vars = [];

        //匹配
        foreach ($pts as $pattern) {
            preg_match_all($pattern, $code, $mts);
            //未匹配到
            if (!isset($mts[0]) || empty($mts[0])) continue;
    
            //依次处理 匹配到的数据
            foreach ($mts[0] as $i => $codei) {
                //变量名
                $var = trim($mts[2][$i]);
                $vars[$var] = $var;
    
                //去除 关键字 export[ default] 
                $code = static::deleteExportKeyword($code, $codei, $default);
            }
        }

        //返回结果
        if (!Is::nemarr($vars)) return null;
        $rtn = [];
        $rtn[$default ? "default" : "normal"] = $vars;
        $rtn["code"] = $code;
        return $rtn;
    }
    /**
     * 解析 批量导出 形式的导出语句：
     *      export[ default] { foo, bar, jaz, ... }
     *      export[ default] { foo as aFoo, bar as aBar, ... }
     */
    protected static function matchExportVarsBatch($code, $default=true, $var=null)
    {
        //导出语句正则
        $pts = [
            static::getExportPattern("/__PRE__\{(\s*__JSV__\s*,?(,\s*__JSV__\s*)*)\};?/U", $default),
            static::getExportPattern("/__PRE__\{(\s*__JSV__\s+as\s+__JSV__\s*,?(,\s*__JSV__\s+as\s+__JSV__\s*)*)\};?/U", $default)
        ];
        
        //匹配到的 vars
        $vars = [];

        //匹配
        foreach ($pts as $pattern) {
            preg_match_all($pattern, $code, $mts);
            //未匹配到
            if (!isset($mts[0]) || empty($mts[0])) continue;
    
            //依次处理 匹配到的数据
            foreach ($mts[0] as $i => $codei) {
                $vars[$codei] = [];
                //变量名 可能是  foo,bar,jaz  或  foo as aFoo, bar as aBar  形式
                $var = trim($mts[1][$i]);
                //合并多个连续空格
                $var = preg_replace("/\s+/", " ", $var);
                //去除 , 前后的空格
                $var = preg_replace("/\s*,\s*/", ",", $var);
                //按 , 拆分
                $varr = explode(",", $var);
                //解析
                foreach ($varr as $vai) {
                    $vai = trim($vai);
                    if (strpos($vai, " as ") !== false) {
                        //foo as aFoo, bar as aBar 形式
                        $vair = explode(" as ", $vai);
                        $vars[$codei][trim($vair[1])] = trim($vair[0]);
                    } else {
                        //foo,bar,jaz 形式
                        $vars[$codei][] = trim($vai);
                    }
                }
    
                //去除 整个 export 导出语句
                $code = static::deleteExportSentence($code, $codei, $default);
            }
        }

        //返回结果
        if (!Is::nemarr($vars)) return null;
        $rtn = [];
        $rtn[$default ? "default" : "normal"] = $vars;
        $rtn["code"] = $code;
        return $rtn;
    }
    /**
     * 解析 匿名导出语句：
     *      export default function() ...
     *      export default class {...}
     *      export default { foo: 123, bar: 456, ... }
     */
    protected static function matchExportVarsAnonymous($code, $default=true, $var=null)
    {
        //导出语句正则
        $pts = [
            static::getExportPattern("/__PRE__(function|class){1}\s*(\(|\{)/U", $default),
            static::getExportPattern("/__PRE__\{([\s\S]+)\};?/U", $default)
        ];
        
        //匹配到的 vars
        $vars = [];

        //匹配
        foreach ($pts as $pattern) {
            preg_match_all($pattern, $code, $mts);
            //未匹配到
            if (!isset($mts[0]) || empty($mts[0])) continue;
    
            //依次处理 匹配到的数据
            foreach ($mts[0] as $i => $codei) {
                //当前是 匿名导出的形式
                if ($default && Is::nemstr($var)) {
                    //如果当前解析的是 default 默认导出，且外部传入了 变量名，则使用此变量名
                    //$var = $var;
                } else {
                    //需要手动创建一个 不重复的 变量名
                    $var = static::uniqueJsVariable();
                }
                $vars[$var] = $var;
    
                //去除 关键字 export[ default] 然后生成 定义语句
                $def = $mts[1][$i];
                if (in_array(strtolower($def), ["function","class"])) {
                    $def = strtolower($def);
                } else {
                    $def = null;
                }
                $code = static::fixExportDefineSentence($code, $codei, $var, $def, $default);
            }
        }

        //返回结果
        if (!Is::nemarr($vars)) return null;
        $rtn = [];
        $rtn[$default ? "default" : "normal"] = $vars;
        $rtn["code"] = $code;
        return $rtn;
    }
    /**
     * 解析 直接导出变量 形式的导出语句：
     *      export[ default] foo; ...
     */
    protected static function matchExportVarsDirect($code, $default=true, $var=null)
    {
        $pattern = static::getExportPattern("/__PRE__(__JSV__)\s*[^a-zA-Z_\$]+/U", $default);
        
        //匹配到的 vars
        $vars = [];
        
        //匹配
        preg_match_all($pattern, $code, $mts);
        //未匹配到
        if (!isset($mts[0]) || empty($mts[0])) return null;
        //依次处理 匹配到的数据
        foreach ($mts[0] as $i => $codei) {
            //变量名
            $var = $mts[1][$i];
            $var = trim($var);
            $vars[$var] = $var;

            //去除 整个 export 导出语句
            $code = static::deleteExportSentence($code, $codei, $default);
        }

        //返回结果
        if (!Is::nemarr($vars)) return null;
        $rtn = [];
        $rtn[$default ? "default" : "normal"] = $vars;
        $rtn["code"] = $code;
        return $rtn;
    }

    /**
     * 解析工具方法
     */

    /**
     * 根据 default 状态，生成对应的 导出语句正则
     * @param String $pt 导出语句正则，带字符串模板 __JSV__  __PRE__
     * @param Bool $default 是否匹配默认到处语句 默认 true
     * @return String 生成的完整 导出语句正则表达式
     */
    protected static function getExportPattern($pt, $default=true)
    {
        //合法的 JS 变量名正则
        $varPt = static::$jsVarPattern;
        //导出语句的 正则前缀
        $prePt = "export\s+".($default ? "default\s+" : "");
        //替换字符串模板
        $pt = str_replace("__JSV__", $varPt, $pt);
        $pt = str_replace("__PRE__", $prePt, $pt);
        return $pt;
    }

    /**
     * 将 code 代码字符串中 匹配到的完整 export 语句中的 关键字 export[ default] 去除掉
     * @param String $code 代码字符串
     * @param String $codei 匹配到的完整 export 语句字符串
     * @param Bool $default 是否匹配的是默认导出语句 默认 true
     * @return String 修改后的 code 代码字符串
     */
    protected static function deleteExportKeyword($code, $codei, $default=true)
    {
        //根据 default 生成 codei 中需要被去除的 部分
        $find = "export\s+".($default ? "default\s+" : "");
        //先修改 codei 
        $ncodei = preg_replace("/".$find."/", "", $codei);
        //在修改 code
        $code = str_replace($codei, $ncodei, $code);
        return $code;
    }

    /**
     * 将 code 代码字符串中 匹配到的完整 export 语句 整体删除
     * @param String $code 代码字符串
     * @param String $codei 匹配到的完整 export 语句字符串
     * @param Bool $default 是否匹配的是默认导出语句 默认 true
     * @return String 修改后的 code 代码字符串
     */
    protected static function deleteExportSentence($code, $codei, $default=true)
    {
        $code = str_replace($codei, "", $code);
        return $code;
    }

    /**
     * 将 code 代码字符串中 匹配到的完整 匿名导出 语句中的 关键字 替换为 变量定义语句
     *      export default function() {...}  -->  function VAR_foobar() {...}
     *      export default class {...}       -->  class VAR_foobar {...}
     *      export default {...}             -->  const VAR_foobar = {...}
     * @param String $code 代码字符串
     * @param String $codei 匹配到的完整 export 语句字符串
     * @param String $var 已生成的 全局唯一的 变量名
     * @param String $def 定义类型，可选 function|class 或者 null
     * @param Bool $default 是否匹配的是默认导出语句 默认 true
     * @return String 修改后的 code 代码字符串
     */
    protected static function fixExportDefineSentence($code, $codei, $var, $def=null, $default=true)
    {
        //根据 default 生成 codei 中需要被去除的 部分
        $find = "export\s+".($default ? "default\s+" : "");
        //根据 def 类型 function|class|null 决定怎样替换
        if (!Is::nemstr($def)) {
            //export default {...}
            $ncodei = preg_replace("/".$find."/", "const $var = ", $codei);
        } else {
            //先去除关键字
            $ncodei = preg_replace("/".$find."/", "", $codei);
            switch ($def) {
                //export default function() ...
                case "function":
                    $ncodei = str_replace("function", "function $var", $ncodei);
                    break;

                //export default class {...}
                case "class":
                    $ncodei = str_replace("class", "class $var", $ncodei);
                    break;

            }
        }
        //修改 code
        $code = str_replace($codei, $ncodei, $code);
        return $code;
    }

}