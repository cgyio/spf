<?php
/**
 * SPF-Theme 主题输出类 基类
 * css|scss|js 等类型的 主题数据输出类 都 继承自此
 */

namespace Spf\module\src\resource\theme;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;

class ThemeExporter 
{
    //依赖的 theme 主题资源实例
    protected $theme = null;

    /**
     * 主题数据
     */
    protected $context = [
        /*
        # 某个 主题模块 的数据
        "module_name" => [
            # 从模块下的 某个 参数 item
            "item_name" => [
                ... 参数值 ...
            ],
            ...
        ],

        "color" => [
            "red" => [
                "m" => "#ff0000",
                "d2" => "",
                "d1" => "",
                ...
            ],
        ],
        ...
        */
    ];

    /**
     * 要输出的资源内容的 缓存
     * 按 行 存储的 文本内容
     */
    protected $content = [];

    /**
     * 构造
     * @param Array $theme 主题资源类实例 
     * @param Array $ctx 主题解析得到的 数据
     * @return void
     */
    public function __construct($theme, $ctx=[])
    {
        //依赖注入
        $this->theme = $theme;
        //缓存 主题设置数据
        $this->context = $ctx;
    }

    /**
     * 主题资源输出 核心入口方法 创建输出资源的 内容
     * !! 子类必须实现
     * @return String 要输出的 主题资源内容
     */
    public function createContent()
    {
        //子类实现
        //...

        return "";
    }

    /**
     * 获取 开启了 auto shift 的主题模块的 shift 级数，用于生成 SCSS 中的 ModnShiftQueue 参数
     * !! 子类不要覆盖此方法
     * @return Int shift 级数
     */
    final public function getShiftSteps()
    {
        $ctx = $this->context;

    }



    /**
     * 处理各 主题模块的 数据 一维化
     * @param String $modk 主题模块 名称 foo_bar
     * @param Array $ctx 某个主题模块的 数据
     * @return Array 经过一维化的 主题模块数据
     */
    protected function flatModuleData($modk, $ctx=[])
    {
        return Arr::flat([
            $modk => $ctx
        ],"-");
    }



    /**
     * 主题输出资源 content 缓存 内容行数组 操作方法
     */
    
    /**
     * 向 内容行数组中增加一行
     * @param String $cnt 内容行数据
     * @param String $rn 结尾字符，默认 ;
     * @return $this
     */
    public function rowAdd($cnt, $rn=";") 
    {
        $this->content[] = $cnt.$rn;
        return $this;
    }

    /**
     * 向 内容行数组中 增加 $n 空行
     * @param Int $n 空行的行数 默认 1
     * @return $this
     */
    public function rowAddEmpty($n=1)
    {
        if ($n>0) {
            for ($i=0;$i<$n;$i++) {
                $this->rowAdd("", "");
            }
        }
        return $this;
    }

    //向文件行数组中 增加 定义变量语句
    /**
     * 向 内容行数组 增加 定义变量语句
     * @param String $name 变量名
     * @param String|Int|Float $val 变量值
     * @param Array $opt 变量定义语句参数，格式见下方定义
     * @return $this
     */
    public function rowDef($name, $val, $opt=[])
    {
        $opt = Arr::extend([
            "prev" => "\$",     //键名 前缀，默认 \$
            "sufx" => "",       //键名 后缀，默认 无
            "gap" => ":",       //键名与值 之间的间隔符，默认 :
            "quote" => false,   //键值是否使用引号包裹，false 表示不用引号，传入 '或" 表示使用 '或" 包裹键值
            "rn" => ";",        //行尾字符，默认 ;
        ], $opt);

        $k = $opt["prev"].$name.$opt["sufx"].$opt["gap"];
        $row = [];
        $row[] = $this->rowTab($k);
        $quote = $opt["quote"];
        if (is_numeric($val)) {
            $row[] = $val;
        } else if (is_string($val)) {
            if ($quote!==false && in_array($quote, ["'","\""])) {
                $row[] = $quote.$val.$quote;
            } else {
                $row[] = $val;
            }
        } else if (is_array($val) && Is::indexed($val)) {
            $nval = array_map(function($vi) use ($quote) {
                if (is_numeric($vi) || $quote===false) return $vi;
                return $quote.$vi.$quote;
            }, $val);
            if ($quote!==false) {
                $row[] = "[".implode(",",$nval)."]";
            } else {
                $row[] = implode(",", $nval);
            }
        } else {
            $row[] = $quote===false ? "\"\"" : $quote.$quote;
        }
        $row = implode("", $row);

        return $this->rowAdd($row, $opt["rn"]);
    }

    /**
     * 根据当前字符串，计算下一个 tab位 需要增加几个空格，并附加到字符串后
     * @param String $s 要处理的字符串
     * @param Int $ti 用空格模拟 tab 每 $ti 个空格表示一个 tab 位 默认 4 个空格
     * @return String 增加一定数量空格后的 字符串
     */
    public function rowTab($s="", $ti=4)
    {
        if (!is_int($ti) || $ti<=0) $ti = 2;
        if (!is_string($s)) return "";
        if (!Is::nemstr($s)) {
            //如果输入空字符串，直接输出 $ti 个空格
            return array_fill(0, $ti, " ");
        }

        $ln = strlen($s);
        $sn = ceil($ln/$ti) * $ti - $ln;
        if ($sn<=0) $sn = $ti;
        $ss = array_fill(0, $sn, " ");
        return $s.implode("",$ss);
    }

    /**
     * 将 内容行数组 合并为 字符串
     * @param String $glup 换行符 默认 \r\n
     * @param Bool $clear 是否清空 content 默认 true
     * @return String 合并后的 字符串
     */
    public function rowCnt($glup="\r\n", $clear=true)
    {
        //文件行数组
        $rows = $this->content;
        //合并
        $cnt = implode($glup, $rows);
        //清空 content
        if ($clear === true) {
            $this->content = [];
        }
        
        return $cnt;
    }



    /**
     * 静态方法
     */

    /**
     * 根据主题模块名，获取主题模块类全称
     * @param String $modk foo_bar 形式
     * @return String 主题模块类全称
     */
    public static function modCls($modk)
    {
        $modn = "Theme".Str::camel($modk, true)."Module";
        return Cls::find("module/src/resource/theme/$modn", "Spf\\");
    }

    /**
     * 根据某个主题模块的参数数据，获取此模块的 auto-shift 级数，用于生成 ModnShiftQueue SCSS 参数
     * @param String $modk 主题模块的 名称 foo_bar
     * @param Array $modc 此主题模块的 参数数组
     * @return Int auto-shift 级数，如果此主题模块未开启 shift 参数，返回 0
     */
    public static function modAutoShiftSteps($modk, $modc)
    {
        $mods = static::$modules;
        $modcls = $mods[$modk] ?? null;
        if (!Is::nemstr($modcls)) return 0;
        $stdItem = $modcls::$stdItem;
        //是否开启 auto shift
        $hasShift = isset($stdItem["shift"]);
        if (!$hasShift) return 0;
        //检查 modc 中每个 item 的 值
        $steps = 0;
        foreach ($modc as $item => $itemv) {
            if (!Is::nemarr($itemv)) continue;
            $isteps = count($itemv);
            $isteps = ($isteps-1)/2;
            if ($isteps<0) {
                $isteps = 0;
            } else {
                $isteps = (int)$isteps;
            }
            if ($isteps>$steps) {
                $steps = $isteps;
            }
        }
        return $steps;
    }

    
}