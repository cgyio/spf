<?php
/**
 * 框架工具类
 * Color 颜色工具类
 * 
 * 此类的实例 描述一个具体的颜色对象，颜色值以 r,g,b,h,s,l,a 属性保存，修改其中某个属性，其他属性将自动计算并同步变化
 * 可通过 setHex | setRgb | setHsl | setAlpha 方法 修改 颜色对象的属性
 * 可通过 H色相\S饱和度\L亮度 进行 颜色调整 
 * 可通过 hex | rgb | hsl 方法 输出对应的 颜色字符串
 * 
 * 此类的静态方法，可作为通用的 颜色计算函数
 */

namespace Spf\util;

class Color 
{
    /**
     * 定义支持的 颜色定义 字符串形式
     * !! 目前仅支持 hex | rgb | hsl 类型字符串
     */
    public static $regs = [
        //hex:  #fa0 | #fa07 | #ffaa00 | #ffaa0077
        "hex" => "/^#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/",
        //rgb:  rgb(255,128,0) | rgb(100%,50%,0) | rgba(255,128,0,.5) | 新语法 rgb(255 128 0 / .5) | rgb(100% 50% 0 / 50%)
        "rgb" => "/^rgba?\(\s*(((\d|\d{2}|1\d{2}|2[0-5]{2})|(\d+(\.\d+)?%)|none)\s*(,|\s)\s*){2}((\d|\d{2}|1\d{2}|2[0-5]{2})|(\d+(\.\d+)?%)|none)((\s*,\s*|\s+\/\s+)((\.|0\.)\d+|\d+(\.\d+)?%|none))?\s*\)$/",
        //hsl:  hsl(120,75,65) | hsl(120deg,75%,65%) | hsla(120,75,65,.5) | 新语法 hsl(120deg 75% 65% / 50%)
        "hsl" => "/^hsla?\(\s*(\d+(\.\d+)?(deg|grad|rad|turn)?|\d+(\.\d+)?%|(\.|0\.)\d+|none)(\s*,\s*|\s+)(\d+(\.\d+)?%?|(\.|0\.)\d+|none)(\s*,\s*|\s+)(\d+(\.\d+)?%?|(\.|0\.)\d+|none)((\s*,\s*|\s+\/\s+)(\d+(\.\d+)?%|(\.|0\.)\d+|none))?\s*\)$/",
    ];

    //定义 小数位数
    public static $dig = 2;

    /**
     * 当前 颜色的 属性
     * 默认颜色 白色 #ffffff | rgb(255,255,255) | hsl(0,0,100)
     */
    //rgb 0-255
    protected $r = 255; 
    protected $g = 255;
    protected $b = 255;
    //hsl
    protected $h = 0;       //色相，角度数值，0-360
    protected $s = 0;       //饱和度，%，0-100
    protected $l = 100;     //亮度，%，0-100
    //alpha % 0-100
    protected $a = 100;

    //记录颜色的 原始值
    protected $origin = [
        /*
        "r" => 255,
        ...
        */
    ];

    /**
     * 构造
     * @param Array|String $opt 可以是颜色字符串 或 颜色数据  [ r,g,b,a  或  h,s,l,a ]
     * @return void
     */
    public function __construct($opt=null)
    {
        if (!Is::nemstr($opt) && !Is::nemarr($opt)) return;

        //传入 颜色字符串
        if (Is::nemstr($opt)) {
            //解析颜色字符串，得到 颜色参数
            $cd = self::parse($opt, true);
            if (!Is::nemarr($cd)) return;
            $opt = $cd;
        }

        //确保 颜色参数 数组符合要求 
        $ks = array_keys($opt);
        $isrgb = empty(array_diff($ks, ["r","g","b","a"]));
        $ishsl = empty(array_diff($ks, ["h","s","l","a"]));
        if (
            count($ks)<3 || count($ks)>4 ||
            !($isrgb || $ishsl)
        ) {
            //颜色参数不合法
            return;
        }

        //写入 颜色参数
        foreach ($opt as $k => $v) {
            $this->$k = $v;
        }

        //根据传入的参数 是 rgb 或 hsl，调用对应的计算方法，传入 rgb 则计算 hsl  或  传入 hsl 则计算 rgb
        if ($isrgb) {
            $this->rgbToHsl();
        } else {
            $this->hslToRgb();
        }

        //记录原始值
        $this->origin = $this->value();
    }

    /**
     * rgb 颜色值 转换为 hsl 颜色值
     * @return $this
     */
    protected function rgbToHsl()
    {
        $hsl = self::rgb2hsl($this->r, $this->g, $this->b);
        if (Is::nemarr($hsl)) {
            foreach ($hsl as $k => $v) {
                $this->$k = $v;
            }
        }
        return $this;
    }

    /**
     * hsl 颜色值 转换为 rgb 颜色值
     * @return $this
     */
    protected function hslToRgb()
    {
        $rgb = self::hsl2rgb($this->h, $this->s, $this->l);
        if (Is::nemarr($rgb)) {
            foreach ($rgb as $k => $v) {
                $this->$k = $v;
            }
        }
        return $this;
    }

    /**
     * 手动设置 rgb
     * @param Array $rgb indexed 数组 [ r,g,b ]，部分设置可以是 [null,128,null] 必须保证 3 个元素 0-255
     * @return $this
     */
    public function setRgb($r=null, $g=null, $b=null)
    {
        $rgb = func_get_args();
        $ks = ["r","g","b"];
        for ($i=0;$i<3;$i++) {
            $v = $rgb[$i];
            if (!is_numeric($v)) continue;
            $k = $ks[$i];
            $this->$k = $v * 1;
        }

        //计算
        $this->rgbToHsl();

        return this;
    }

    /**
     * 手动设置 hsl
     * @param Array $hsl indexed 数组 [ h,s,l ]，部分设置可以是 [null,75,null] 必须保证 3 个元素 h:0-360, s,l:0-100
     * @return $this
     */
    public function setHsl($h=null, $s=null, $l=null)
    {
        $hsl = func_get_args();
        $ks = ["h","s","l"];
        for ($i=0;$i<3;$i++) {
            $v = $hsl[$i];
            if (!is_numeric($v)) continue;
            $k = $ks[$i];
            $this->$k = $v * 1;
        }

        //计算
        $this->hslToRgb();

        return $this;
    }

    /**
     * 手动设置 alpha
     * @param Int $alpha 0-100 
     * @return $this
     */
    public function setAlpha($alpha=null)
    {
        if (!is_numeric($alpha)) return $this;
        $this->a = $alpha;
        return $this;
    }

    /**
     * 恢复此颜色实例的 原始值
     * @return $this
     */
    public function restore()
    {
        $origin = $this->origin;
        if (Is::nemarr($origin)) {
            foreach ($origin as $k => $v) {
                $this->$k = $v;
            }
        }
        return $this;
    }

    /**
     * 输出颜色值 数组 包含全部 rgbhsla
     * @return Array
     */
    public function value()
    {
        $ks = explode(",", "r,g,b,h,s,l,a");
        $rtn = [];
        foreach ($ks as $k) {
            $rtn[$k] = $this->$k;
        }
        return $rtn;
    }

    /**
     * 输出当前颜色 hex 如果 alpha<100 则包含 alpha
     * @param Bool $alpha 是否输出 alpha 默认 true，false 时，不论是否 alpha<100 都不输出
     * @return String
     */
    public function hex($alpha=true)
    {
        $hex = ["#"];
        $ks = explode(",","r,g,b");
        foreach ($ks as $k) {
            $v = $this->$k;
            $hex[] = ($v<16 ? "0" : "").dechex($v);
        }
        //alpha
        if ($alpha!==false) {
            $a = $this->a;
            if ($a<100) {
                $a = self::pec2hexdec($a."%");
                $hex[] = ($a<16 ? "0" : "").dechex($a);
            }
        }
        return implode("", $hex);
    }

    /**
     * 输出当前颜色 rgb 如果 alpha<100 则包含 alpha
     * @param Bool $alpha 是否输出 alpha 默认 true，false 时，不论是否 alpha<100 都不输出
     * @return String
     */
    public function rgb($alpha=true)
    {
        $rgb = [];
        $ks = explode(",","r,g,b");
        foreach ($ks as $k) {
            $v = $this->$k;
            $rgb[] = $v;
        }
        $rgb = implode(",",$rgb);

        //alpha
        if ($alpha) {
            $a = $this->a;
            if ($a<100) {
                return "rgba($rgb,".self::pec2float($a."%").")";
            }
        }
        
        return "rgb($rgb)";

    }

    /**
     * 输出当前颜色 hsl 如果 alpha<100 则包含 alpha
     * @param Bool $alpha 是否输出 alpha 默认 true，false 时，不论是否 alpha<100 都不输出
     * @return String
     */
    public function hsl($alpha=true)
    {
        $hsl = [];
        $ks = explode(",","h,s,l");
        foreach ($ks as $k) {
            $v = $this->$k;
            $hsl[] = $v.($k=="h" ? "deg" : "%");
        }
        $hsl = implode(",",$hsl);

        //alpha
        if ($alpha) {
            $a = $this->a;
            if ($a<100) {
                return "hsla($hsl,".self::pec2float($a."%").")";
            }
        }
        
        return "hsl($hsl)";

    }



    /**
     * 静态方法
     * 通用 颜色处理函数
     */

    /**
     * 判断一个字符串是否是 有效的 颜色字符串 #fff | rgba(...) ...
     * !! 目前仅支持 hex | rgb | hsl 类型的颜色字符串
     * @param String $cstr 输入的字符串
     * @return String|false 不是合法颜色字符串 返回 false  是颜色字符串则返回 hex|rgb|hsl 颜色字符串类型
     */
    public static function isColorString($cstr)
    {
        if (!Is::nemstr($cstr)) return false;
        
        //正则匹配
        $regs = self::$regs;
        foreach ($regs as $regt => $reg) {
            if (preg_match($reg, $cstr)) return $regt;
        }
        return false;
    }

    /**
     * 解析颜色字符串，得到 Color 实例
     * @param String $cstr 输入的颜色字符串
     * @param Bool $rtnOpt 是否返回 颜色实例的构造参数，而不是返回 颜色实例，默认 false 返回 颜色实例
     * @return Color|Array|null 合法颜色字符串 则返回生成的 颜色实例(或颜色参数)，否则返回 null
     */
    public static function parse($cstr, $rtnOpt=false)
    {
        if (!Is::nemstr($cstr)) return null;
        //判断字符串是否合法，得到颜色字符串类型
        if (false === ($cstp = self::isColorString($cstr))) return null;
        //调用颜色字符串类型 对应的 解析方法
        $m = "parse".Str::camel($cstp, true);
        if (method_exists(self::class, $m)) {
            return self::$m($cstr, $rtnOpt);
        }
        return null;
    }
    //解析 hex 颜色
    protected static function parseHex($cstr, $rtnOpt=false)
    {
        $cstr = substr($cstr, 1);
        $rgb = str_split($cstr);
        if (!in_array(count($rgb), [3,4,6,8])) return null;
        if (in_array(count($rgb), [3,4])) {
            $rgb = array_map(function($i) {
                return $i.$i;
            }, $rgb);
        } else {
            $nrgb = [];
            for ($i=0;$i<count($rgb);$i+=2) {
                $nrgb[] = $rgb[$i].$rgb[$i+1];
            }
            $rgb = $nrgb;
        }
        if (count($rgb)==4) {
            $alpha = $rgb[3];
            $rgb = array_slice($rgb, 0, 3);
        } else {
            $alpha = "ff";
        }
        //var_dump($rgb);var_dump($alpha);
        //颜色实例化参数
        $opt = [
            "r" => hexdec($rgb[0]),
            "g" => hexdec($rgb[1]),
            "b" => hexdec($rgb[2]),
            "a" => self::hexdec2pec(hexdec($alpha)),
        ];
        //创建并返回 颜色实例
        return $rtnOpt ? $opt : new Color($opt);
    }
    //解析 rgb 颜色
    protected static function parseRgb($cstr, $rtnOpt=false)
    {
        $cstr = explode("(", explode(")", $cstr)[0])[1];
        $cstr = trim($cstr);
        $cstr = preg_replace("/\s*,\s*/", ",", $cstr);
        $cstr = preg_replace("/\s*\/\s*/", ",", $cstr);
        $cstr = preg_replace("/\s+/", ",", $cstr);
        $cstr = str_replace(",.", ",0.", $cstr);
        $rgb = explode(",", $cstr);
        if (count($rgb)<3 || count($rgb)>4) return null;
        if (count($rgb)==4) {
            $alpha = $rgb[3];
            $rgb = array_slice($rgb, 0, 3);
        } else {
            $alpha = "1";
        }
        //var_dump($rgb);var_dump($alpha);
        //颜色实例化参数
        $opt = [
            "r" => substr($rgb[0], -1)==="%" ? self::pec2hexdec($rgb[0]) : (int)$rgb[0],
            "g" => substr($rgb[0], -1)==="%" ? self::pec2hexdec($rgb[1]) : (int)$rgb[1],
            "b" => substr($rgb[0], -1)==="%" ? self::pec2hexdec($rgb[2]) : (int)$rgb[2],
            "a" => self::alpha2int($alpha),
        ];
        //创建并返回 颜色实例
        return $rtnOpt ? $opt : new Color($opt);

    }
    //解析 hsl 颜色
    protected static function parseHsl($cstr, $rtnOpt=false)
    {
        $cstr = explode("(", explode(")", $cstr)[0])[1];
        $cstr = trim($cstr);
        $cstr = preg_replace("/\s*,\s*/", ",", $cstr);
        $cstr = preg_replace("/\s*\/\s*/", ",", $cstr);
        $cstr = preg_replace("/\s+/", ",", $cstr);
        $cstr = str_replace(",.", ",0.", $cstr);
        $hsl = explode(",", $cstr);
        if (count($hsl)<3 || count($hsl)>4) return null;
        if (count($hsl)==4) {
            $alpha = $hsl[3];
            $hsl = array_slice($hsl, 0, 3);
        } else {
            $alpha = "1";
        }
        //var_dump($hsl);var_dump($alpha);
        //颜色实例化参数
        $opt = [
            "h" => (int)$hsl[0],    //角度，0-360[deg]
            "s" => substr($hsl[1], -1)==="%" ? self::pec2int($hsl[1]) : round($hsl[1] * 1),
            "l" => substr($hsl[2], -1)==="%" ? self::pec2int($hsl[2]) : round($hsl[2] * 1),
            "a" => self::alpha2int($alpha),
        ];
        //创建并返回 颜色实例
        return $rtnOpt ? $opt : new Color($opt);
    }

    /**
     * 将 rgb 色值 转换为 hsl 色值
     * @param Int $r  0-255
     * @param Int $g  0-255
     * @param Int $b  0-255
     * @return Array|null [ h, s, l ]
     */
    public static function rgb2hsl($r, $g, $b)
    {
        $r = $r/255;
        $g = $g/255;
        $b = $b/255;
        $max = max($r,$g,$b);
        $min = min($r,$g,$b);
        $l = ($max+$min)/2;
        if ($max === $min) {
            $h = 0;
            $s = 0;
        } else {
            $d = $max - $min;
            $s = $l>0.5 ? $d/(2-$max-$min) : $d/($max+$min);
            switch ($max) {
                case $r: $h = ($g-$b)/$d + ($g<$b ? 6 : 0); break;
                case $g: $h = ($b-$r)/$d + 2; break;
                case $b: $h = ($r-$g)/$d + 4; break;
            }
            $h = $h/6;
        }

        return [
            //h 0-0.5-1  -->  0-360
            "h" => self::float2max($h, 360),
            //s,l  0-0.5-1  -->  0-100
            "s" => self::float2max($s, 100),
            "l" => self::float2max($l, 100),
        ];
    }

    /**
     * 将 hsl 色值 转换为 rgb 色值
     * @param Int $h  0-360
     * @param Int $s  0-100
     * @param Int $l  0-100
     * @return Array|null [ r, g, b ] 
     */
    public static function hsl2rgb($h, $s, $l)
    {
        $h = $h/360;
        $s = $s/100;
        $l = $l/100;

        if ($s === 0) {
            $r = $l;
            $g = $l;
            $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = self::hue2rgb($p, $q, $h + 1 / 3);
            $g = self::hue2rgb($p, $q, $h);
            $b = self::hue2rgb($p, $q, $h - 1 / 3);
        }

        //r,g,b  0-0.5-1  -->  0-255
        return [
            "r" => self::float2max($r, 255),
            "g" => self::float2max($g, 255),
            "b" => self::float2max($b, 255),
        ];
    }

    /**
     * 中间方法
     */
    protected static function hue2rgb($p, $q, $t)
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    }



    /**
     * 用于 SPF-Theme 主题 颜色系统的 auto-shift 功能
     * 自动根据 lmax\lmin 亮度范围 以及 变化级数，对 颜色 进行 自动 加深|减淡 返回 变化后的 颜色 hex 值
     * @param String $cstr 原始颜色值，颜色字符串
     * @param Int $lmax 最大亮度值
     * @param Int $lmin 最小亮度值
     * @param Int $steps 变化级数  3 -->  d3,d2,d1,m,l1,l2,l3
     * @param Bool $isDark 是否 dark 暗黑模式，在暗黑模式下，颜色 加深|减淡 方向相反，默认 false
     * @return Array 变化后的 颜色数组
     *  [
     *      "d3" => "#123456",
     *      "d2" => "",
     *      ...
     *      "m" => "原始颜色值",
     *      ...
     *      "l3" => "",
     *  ]
     */
    public static function autoShift($cstr, $lmax=96, $lmin=24, $steps=3, $isDark=false)
    {
        //创建原始 颜色实例
        $color = static::parse($cstr);
        //原始颜色 亮度值
        $ol = $color->value()["l"];
        if ($ol<$lmin || $ol>$lmax) {
            //原始亮度 不在 亮度范围内，无法调整
            return null;
        }
        //加深方向，每步的 delta 变化值
        $dkd = ($ol-$lmin)/$steps;
        //减淡方向，每步的 delta 变化值
        $lgd = ($lmax-$ol)/$steps;
        //根据级数 依次处理
        $rtn = [];
        for ($i=$steps*-1; $i<=$steps; $i++) {
            //颜色实例 先恢复原始值
            $color->restore();
            //$i = 0 是原始颜色
            if ($i==0) {
                $rtn["m"] = $color->hex();
                continue;
            }
            //计算 亮度变化值
            if ($i<0) {
                //加深方向
                $d = $i * $dkd;
                //新颜色 键名，如果是 dark 暗黑模式，则 加深|减淡 方向相反
                $k = ($isDark ? "l" : "d").abs($i);
            } else {
                //加深方向
                $d = $i * $lgd;
                //新颜色 键名，如果是 dark 暗黑模式，则 加深|减淡 方向相反
                $k = ($isDark ? "d" : "l").abs($i);
            }
            //新亮度值
            $nl = round($ol + $d);
            //设置新亮度值
            $color->setHsl(null,null,(int)$nl);
            //保存 新颜色
            $rtn[$k] = $color->hex();
        }
        //销毁颜色实例
        unset($color);
        //返回
        return $rtn;
    }



    /**
     * 数值计算
     */

    //浮点数 保留 self::$dig 位小数
    public static function round($float, $dig=null)
    {
        if (is_numeric($dig)) {
            $dig = (int)$dig;
        } else {
            $dig = self::$dig;
        }
        return round($float, $dig);
    }
    //0-0.5-1 | 0%-100%  -->  0-100 通常用于转换 alpha
    public static function alpha2int($alpha)
    {
        $alpha = "".$alpha;
        if ($alpha==="0") return 0;
        if ($alpha==="1") return 100;
        if (strpos($alpha, ".")!==false) return self::float2pec($alpha * 1);
        if (substr($alpha, -1)==="%") return self::pec2int($alpha);
        $alpha = round($alpha * 1);
        return (int)$alpha;
    }
    //0%-100%  -->  0-100
    public static function pec2int($pec)
    {
        if (!Is::nemstr($pec) || substr($pec, -1)!=="%") return 0;
        $pec = substr($pec, 0, -1);
        return (int)$pec;
    }
    //0%-100%  -->  0-1
    public static function pec2float($pec)
    {
        if (!Is::nemstr($pec) || substr(trim($pec), -1)!=="%") return 0;
        $int = substr(trim($pec), 0, -1);
        $int = $int * 1;
        return self::round($int/100);
    }
    //0-1  -->  0%-100% 不带 % 的 int 数字
    public static function float2pec($float)
    {
        $float = round($float * 100);
        return (int)$float;
    }
    //0%-100%  -->  0-255
    public static function pec2hexdec($pec)
    {
        $float = self::pec2float($pec);
        $hd = round($float * 255);
        return (int)$hd;
    }
    //0-255  --> 0%-100% 不带 % 的 int 数字
    public static function hexdec2pec($hexdec) 
    {
        $pec = round(($hexdec/255)*100);
        return (int)$pec;
    }
    //0-0.5-1  -->  0-max 任意 0-1 的小数，转为 0-max 的 int 数字
    public static function float2max($float, $max=100)
    {
        $int = round($float * $max);
        return (int)$int;
    }

}