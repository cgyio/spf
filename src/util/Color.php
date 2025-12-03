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

        return $this;
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
     * @param String $key 可单独获取某个值，null 则返回全部
     * @return Array|Int
     */
    public function value($key=null)
    {
        if (is_null($key) || !Is::nemstr($key) || !isset($this->$key)) {
            $ks = explode(",", "r,g,b,h,s,l,a");
            $rtn = [];
            foreach ($ks as $k) {
                $rtn[$k] = $this->$k;
            }
            return $rtn;
        }
        return $this->$key;
    }

    /**
     * 计算当前颜色的 luma 视觉亮度，与 hsl 的 l 不同
     * @return Int 0-255
     */
    public function luma()
    {
        return static::rgb2luma($this->r, $this->g, $this->b);
    }

    /**
     * 判断当前颜色是否 灰度色 r==g==b
     * @return Bool
     */
    public function isGray()
    {
        return $this->r === $this->g && $this->g === $this->b;
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
     * 计算 Luma 看起来的颜色亮度，与 hsl 里的 l 不同，Luma 有人眼视觉加权
     * @param Int $r 0-255
     * @param Int $g 0-255
     * @param Int $b 0-255
     * @return Int 0-255
     */
    public static function rgb2luma($r, $g, $b)
    {
        $luma = round(0.299*$r + 0.587*$g + 0.114*$b);
        return $luma>255 ? 255 : ($luma<0 ? 0 : $luma);
    }

    /**
     * 根据 rgb 以及 luma 亮度的变化 % 计算变化后的 新 rgb
     * @param Int $r 0-255
     * @param Int $g 0-255
     * @param Int $b 0-255
     * @param Int|Float $d luma 变化 % 正负数
     * @return Array [r=>, g=>, b=>]
     */
    public static function rgbShiftByLuma($r,$g,$b, $d=0)
    {
        //原 luma 0-255
        $ol = static::rgb2luma($r,$g,$b);
        //新 luma 0-255
        $nl = $ol * (1+ $d/100);
        $nl = $nl>255 ? 255 : ($nl<0 ? 0 : $nl);
        //rgb通道的 变化量 -1~0~1
        $nd = ($nl - $ol)/$ol;
        //rgb通道分别 乘以 1+变化量
        $nr = round($r * (1+$nd));
        $ng = round($g * (1+$nd));
        $nb = round($b * (1+$nd));
        //只取 0-255 
        return [
            "r" => $nr>255 ? 255 : ($nr<0 ? 0 : $nr),
            "g" => $ng>255 ? 255 : ($ng<0 ? 0 : $ng),
            "b" => $nb>255 ? 255 : ($nb<0 ? 0 : $nb),
        ];
    }



    /**
     * 根据传入的 某个颜色字符串，计算并取得 以此颜色作为背景色时的 前景色
     * 颜色 luma 高于 128 则前景色为 黑色，否则为白色
     * @param String $cstr 原始颜色值，合法颜色字符串
     * @param Int $opacity 前景色透明度 0-100 默认 90
     * @return String #ffffff 或 #000000
     */
    public static function autoFrontColor($cstr, $opacity=90) 
    {
        //创建原始 颜色实例
        $color = static::parse($cstr);
        //色值
        $cv = $color->value();
        //luma
        $luma = static::rgb2luma($cv["r"], $cv["g"], $cv["b"]);
        $fc = $luma<128 ? "#ffffff" : "#000000";
        //创建前景色实例
        $fco = static::parse($fc);
        //设置透明度
        if ($opacity<100) $fco->setAlpha($opacity);
        //输出 hex
        $hex = $fco->hex();
        //释放资源
        unset($color);
        unset($fco);
        return $hex;
    }

    /**
     * 用于 SPF-Theme 主题 颜色系统的 auto-shift 功能
     * 自动根据 lmax\lmin 亮度范围 以及 变化级数，对 颜色 进行 自动 加深|减淡 返回 变化后的 颜色 hex 值
     * @param String $cstr 原始颜色值，颜色字符串
     * @param Int $lmax 最大亮度值 0-100 0=纯黑 100=纯白
     * @param Int $lmin 最小亮度值 0-100 0=纯黑 100=纯白
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
        $ol = $color->value("l");
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
                //减淡方向
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
     * !! 与 autoShift 方法的区别是：此方法使用视觉亮度作为 加深减淡的 判断标准，而不是使用 hsl 中的 l 值
     * !!   颜色加深方向(亮度降低)      使用 luma 视觉亮度作为计算依据
     * !!   颜色减淡方向(亮度增加)      使用 hsl 中的 l 通道值作为计算依据
     * @param String $cstr 原始颜色值，颜色字符串
     * @param Int $lmax 最大亮度值 0-100 0=纯黑 100=纯白
     * @param Int $lmin 最小亮度值 0-100 0=纯黑 100=纯白 等于 (luma/255)*100
     * @param Int $steps 变化步数  3 -->  d3,d2,d1,m,l1,l2,l3
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
    public static function autoShiftWithLuma($cstr, $lmax=96, $lmin=24, $steps=3, $isDark=false)
    {
        //创建原始 颜色实例
        $color = static::parse($cstr);
        //原始颜色值
        $ocv = $color->value();
        //原始颜色 视觉亮度值 0-255
        $oluma = $color->luma();
        //luma 值 转为 0-100 
        $olu = ($oluma/255)*100;
        //原始颜色 hsl 中 l 通道值 0-100
        $ol = $ocv["l"];
        //确保颜色在 可变化的亮度范围内
        if ($olu<$lmin || $ol>$lmax) {
            return null;
        }

        //分别计算 加深|减淡 方向的 delta 每步变化值 0-100
        //加深方向，luma 每步变化 0-100
        $dlu = ($olu-$lmin)/$steps;
        //减淡方向，hsl 中 l 值每步变化 0-100
        $dl = ($lmax-$ol)/$steps;

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
                //加深方向的 luma 变化值 0-100
                $d = $i * $dlu;
                //新颜色 键名，如果是 dark 暗黑模式，则 加深|减淡 方向相反
                $k = ($isDark ? "l" : "d").abs($i);
                //根据 luma 变化 % 计算新的 rgb
                $nrgb = static::rgbShiftByLuma($ocv["r"], $ocv["g"], $ocv["b"], $d);
                //设置新 rgb
                $color->setRgb($nrgb["r"], $nrgb["g"], $nrgb["b"]);
            } else {
                //减淡方向的 luma 变化 %
                $d = $i * $dl;
                //新颜色 键名，如果是 dark 暗黑模式，则 加深|减淡 方向相反
                $k = ($isDark ? "d" : "l").abs($i);
                //计算新的 hsl 中的 l 值
                $nl = round($ol + $d);
                $nl = $nl>100 ? 100 : ($nl<0 ? 0 : $nl);
                //设置新的 hsl
                $color->setHsl(null,null,$nl);
            }
            
            //保存 新颜色
            $rtn[$k] = $color->hex();
        }
        //销毁颜色实例
        unset($color);
        //返回
        return $rtn;
    }
    /**
     * !! 使用 photoshop 透明图层叠加算法，创建自动加深|减淡的颜色序列
     * !! 原理：分别在当前颜色上层覆盖 带有一定透明度的 纯白|纯黑 以达到 减淡|加深 颜色的效果
     * @param String $cstr 原始颜色值，颜色字符串
     * @param Int $lmax 0-100 叠加的纯白颜色图层的 最小透明度(数值最大)，此时颜色最淡，亮度最高
     * @param Int $lmin 0-100 叠加的纯黑颜色图层的 最小透明度(数值最小)，此时颜色最深，亮度最低
     * @param Int $steps 变化步数  3 -->  d3,d2,d1,m,l1,l2,l3
     * @param Bool $isDark 是否 dark 暗黑模式，在暗黑模式下，颜色 加深|减淡 方向相反，默认 false
     * @return Array 变化后的 颜色数组
     */
    public static function autoShiftWithPsAlpha($cstr, $lmax=96, $lmin=24, $steps=3, $isDark=false)
    {
        //创建原始 颜色实例
        $color = static::parse($cstr);
        //饱和度
        $s = $color->value("s");

        //纯白|黑 颜色字符串
        $pw = "#ffffff";
        $pb = "#000000";
        //透明度
        $aw = $lmax;
        $ab = 100 - $lmin;
        //每一步的 透明度增加值
        $daw = $aw/$steps;
        $dab = $ab/$steps;

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
                //加深方向 使用纯黑图层覆盖 当前颜色，生成新颜色
                $d = $i * $dab * -1;
                //新颜色 键名，如果是 dark 暗黑模式，则 加深|减淡 方向相反
                $k = ($isDark ? "l" : "d").abs($i);
                //执行 psAlpha 算法，生成新颜色实例
                $nc = static::psAlpha($pb, $cstr, round($d));
            } else {
                //减淡方向 使用纯白图层覆盖 当前颜色，生成新颜色
                $d = $i * $daw;
                //新颜色 键名，如果是 dark 暗黑模式，则 加深|减淡 方向相反
                $k = ($isDark ? "d" : "l").abs($i);
                //执行 psAlpha 算法，生成新颜色实例
                $nc = static::psAlpha($pw, $cstr, round($d));
            }
            //新颜色 饱和度保持原色一致 灰度颜色不需要
            //if ($nc->isGray() !== true) {
            //    $ns = $nc->value("s");
            //    if ($ns<$s) $nc->setHsl(null, $s, null);
            //}
            //保存 新颜色
            $rtn[$k] = $nc->hex();
            //释放
            unset($nc);
        }
        //销毁颜色实例
        unset($color);
        //返回
        return $rtn;
    }



    /**
     * photoshop 颜色算法实现
     */

    /**
     * 透明度图层叠加下层颜色后，得到新颜色
     * 核心算法：R上(0-255) * alpha(0-1) + R下(0-255) * (1-alpha)
     * @param String $front 上层颜色字符串
     * @param String $back 下层颜色字符串
     * @param Int $alpha 上层颜色的透明度 0-100 % 0 表示完全透明
     * @return Color|null 混合后的 颜色实例
     */
    public static function psAlpha($front, $back, $alpha=100) 
    {
        //创建颜色实例
        $cof = static::parse($front);
        $cob = static::parse($back);
        if (!$cof instanceof static || !$cob instanceof static) return null;

        //特殊情况
        if ($alpha <= 0) {
            unset($cof);
            return $cob;
        }
        if ($alpha >= 100) {
            unset($cob);
            return $cof;
        }
        
        //alpha --> 0-1
        $a = $alpha/100;
        //颜色值
        $cvf = $cof->value();
        $cvb = $cob->value();
        //释放
        unset($cof);
        unset($cob);
        //提取 rgb 并乘以 alpha
        $rgbf = [$cvf["r"]*$a,      $cvf["g"]*$a,       $cvf["b"]*$a];
        $rgbb = [$cvb["r"]*(1-$a),  $cvb["g"]*(1-$a),   $cvb["b"]*(1-$a)];
        //rgb 分别相加
        $nrgb = [
            $rgbf[0] + $rgbb[0],
            $rgbf[1] + $rgbb[1],
            $rgbf[2] + $rgbb[2],
        ];
        //只返回 0-255
        $nrgb = array_map(function($i) {
            return $i>255 ? 255 : ($i<0 ? 0 : $i);
        }, $nrgb);
        //创建颜色实例，并返回
        return new static([
            "r" => $nrgb[0],
            "g" => $nrgb[1],
            "b" => $nrgb[2]
        ]);
    }

    /**
     * 正片叠底
     * 核心算法：将 RGB 通道值归一化到 0-1 区间，然后两个颜色相乘，再转回 0-255
     * @param String $c1 第一个颜色字符串
     * @param String $c2 第二个颜色字符串
     * @return Color|null 混合后的 颜色实例
     */
    public static function psMutiply($c1, $c2)
    {
        //创建颜色实例
        $co1 = static::parse($c1);
        $co2 = static::parse($c2);
        if (!$co1 instanceof static || !$co2 instanceof static) return null;
        //颜色值
        $cv1 = $co1->value();
        $cv2 = $co2->value();
        //释放
        unset($co1);
        unset($co2);

        //分别将 rgb 转为 0-1 数值
        $rgbs = [[], []];
        foreach ([$cv1, $cv2] as $i => $cvi) {
            foreach ($cvi as $k => $v) {
                if ($k === "r") $rgbs[$i][] = $v/255;
                if ($k === "g") $rgbs[$i][] = $v/255;
                if ($k === "b") $rgbs[$i][] = $v/255;
            }
        }

        //rgb 通道分别相乘，转为 0-255
        $nrgb = [
            static::float2max($rgbs[0][0] * $rgbs[1][0], 255),
            static::float2max($rgbs[0][1] * $rgbs[1][1], 255),
            static::float2max($rgbs[0][2] * $rgbs[1][2], 255),
        ];

        //创建新颜色实例，并返回
        return new static([
            "r" => $nrgb[0],
            "g" => $nrgb[1],
            "b" => $nrgb[2]
        ]);
    }

    /**
     * 滤色
     * 核心算法：将 RGB 通道值归一化到 0-1 区间，然后 结果RGB = 1 - (1 - 顶层RGB) × (1 - 底层RGB)
     * @param String $front 上层颜色字符串
     * @param String $back 下层颜色字符串
     * @return Color|null 混合后的 颜色实例
     */
    public static function psScreen($front, $back)
    {
        //创建颜色实例
        $cof = static::parse($front);
        $cob = static::parse($back);
        if (!$cof instanceof static || !$cob instanceof static) return null;
        //颜色值
        $cvf = $cof->value();
        $cvb = $cob->value();
        //释放
        unset($cof);
        unset($cob);
        //分别将 rgb 转为 0-1 数值
        $rgbs = [[], []];
        foreach ([$cvf, $cvb] as $i => $cvi) {
            foreach ($cvi as $k => $v) {
                if ($k === "r") $rgbs[$i][] = $v/255;
                if ($k === "g") $rgbs[$i][] = $v/255;
                if ($k === "b") $rgbs[$i][] = $v/255;
            }
        }
        //计算，并转为 0-255
        $nrgb = [
            static::float2max(1 - ((1-$rgbs[0][0]) * (1-$rgbs[1][0])), 255),
            static::float2max(1 - ((1-$rgbs[0][1]) * (1-$rgbs[1][1])), 255),
            static::float2max(1 - ((1-$rgbs[0][2]) * (1-$rgbs[1][2])), 255),
        ];

        //创建新颜色实例，并返回
        return new static([
            "r" => $nrgb[0],
            "g" => $nrgb[1],
            "b" => $nrgb[2]
        ]);
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