<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Image 子类
 * 处理 image/* 类型的 资源
 * !! 依赖 GD 库
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\SrcException;
use Spf\Request;
use Spf\Response;
use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;

class Image extends Resource
{
    /**
     * image 图片参数
     */
    public $width = 0;
    public $height = 0;
    //宽高比，width/height
    public $ratio = 1;
    public $bit = 8;
    //完整的 图片信息
    public $info = [
        "width" => 0,
        "height" => 0,
        "bit" => 8,
        "size" => 0,
        "sstr" => ""
    ];

    //image处理句柄
    public $source = null;
    //经过处理步骤后 缓存的 source
    public $im = null;

    /**
     * 对图片内容进行编辑，通过 params 传入操作参数
     * !! 必须严格按定义的 操作顺序，依次处理
     */
    public $queue = [
        //等比缩放，默认 100%
        "zoom" => "100",
        //缩放并裁剪，例如：原图 1920*1080 输入裁剪参数 1920x1200 最终输出尺寸 1728*1080 放大到 1920*1200
        "clip" => "0x0",
        //缩略图，默认 256 方形，可任意形状 256|128x128|256x128
        "thumb" => "256",
        //水印，可指定字符，或水印图片路径
        "mark" => "",   //TODO:
        //灰度
        "gray" => "yes",
    ];

    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //getContent 方法得到的 图片 二进制内容
        $cnt = $this->content;

        //通过图片内容，获取图片信息
        $info = getimagesizefromstring($cnt);
        if ($info === false) {
            //图片信息读取失败
            throw new SrcException("无法读取图片信息", "resource/getcontent");
        }
        $w = $info[0];
        $h = $info[1];
        $mime = $info["mime"];
        $bit = $info["bits"];
        $size = strlen($cnt);
        $sstr = Conv::sizeToStr($size);
        $this->width = (int)$w;
        $this->height = (int)$h;
        $this->ratio = $this->width/$this->height;
        $this->bit = (int)$bit;
        $this->info = [
            "width" => $this->width,
            "height" => $this->height,
            "bit" => $this->bit,
            "size" => $size,
            "sstr" => $sstr
        ];
        //修改 mime
        if ($this->mime !== $mime) {
            $this->mime = $mime;
            $this->ext = Mime::getExt($mime);
        }

        //通过图片内容，创建 GD 库资源句柄
        $source = imagecreatefromstring($cnt);
        if ($source === false) {
            //无法创建图片资源 句柄
            throw new SrcException("无法读取图片内容", "resource/getcontent");
        }
        $this->source = $source;

        return $this;
    }

    /**
     * 在输出资源内容之前，对资源内容执行处理
     * !! 覆盖父类
     * @param Array $params 可传入额外的 资源处理参数
     * @return Resource $this
     */
    protected function beforeExport($params=[])
    {
        //合并额外参数
        if (!Is::nemarr($params)) $params = [];
        if (!Is::nemarr($params)) $this->params = Arr::extend($this->params, $params);

        //对图片进行依次处理
        $this->process();

        return $this;
    }

    /**
     * 资源输出的最后一步，echo
     * !! 覆盖父类
     * @param String $content 可单独指定最终输出的内容，不指定则使用 $this->content
     * @return Resource $this
     */
    protected function echoContent($content=null)
    {
        //读取处理后 图片数据
        $imgData = $this->getImageData($this->im);
        if (!is_string($imgData) || empty($imgData)) {
            //未读取到数据
            throw new SrcException("未能正确获取到图片数据", "resource/export");
        }
        
        //调用 父类方法
        return parent::echoContent($imgData);
    }

    /**
     * 按 编辑操作序列 $queue 依次处理图片，处理后的 source 缓存到 $this->im;
     * @param Array $params 操作参数，不指定 默认使用 $this->params
     * @return void
     */
    protected function process($params = [])
    {
        //先缓存
        $this->im = $this->source;
        //获取参数
        if (!Is::nemarr($params)) $params = $this->params;
        //未指定任何操作
        if (!Is::nemarr($params)) return;

        //严格按顺序，依次执行
        foreach ($this->queue as $opr => $op) {
            if (!isset($params[$opr])) continue;
            //传入参数 
            $pp = $params[$opr];
            if ($pp === false) continue;
            if ($pp === true || $pp === "yes") $pp = $op;
            //调用处理方法
            $m = "process".Str::camel($opr, true);
            if (method_exists($this, $m)) {
                $this->$m($pp);
            }
        }
    }

    /**
     * 图片处理的 具体方法，处理后的 source 缓存到 $this->im
     * @param String $p 处理参数
     * @return void
     */
    //等比例缩放
    protected function processZoom($p="100")
    {
        $p = empty($p) || !is_numeric($p) ? 100 : (int)$p;
        if ($p===100) return;
        $this->im = self::zoomImage($this->im, $p);
    }
    //缩放到指定宽高，当指定的宽高比不等于原图片时，保证缩放后的图片不超出指定的宽高
    protected function processClip($p="0x0")
    {
        if ($p==="0x0") return;
        $opt = explode("x", $p);
        $this->im = self::clipImage($this->im, (int)$opt[0], (int)$opt[1]);
    }
    //自动生成缩略图，裁切原图，满足缩略图宽高比
    protected function processThumb($p="256")
    {
        if (!Is::nemstr($p) && !is_numeric($p)) return;
        if (is_numeric($p)) {
            if ($p<=0) return;
            $p = (int)$p;
            $opt = [ $p, $p ];
        } else {
            $opt = explode("x", $p);
            if (
                count($opt)<=0 || 
                !is_numeric($opt[0]) || 
                (isset($opt[1]) && !is_numeric($opt[1]))
            ) {
                return;
            }
            if (count($opt)<2) $opt[] = $opt[0];
        }
        if ($opt[0]<=0 && $opt[1]<=0) return;
        $this->im = self::clipImage($this->im, (int)$opt[0], (int)$opt[1]);
    }
    //添加水印
    protected function processMark($p="cphp,right,bottom,25")
    {
        //TODO...
        /*
        $opt = explode(",", $opt);
        $mn = $opt[0];
        $mp = Path::exists([
            "icon/watermark/$mn.jpg",
            "icon/watermark/$mn.png",
            "entry/icon/watermark/$mn.jpg",
            "entry/icon/watermark/$mn.png"
        ]);
        if (is_null($mp)) return $this;
        $mp = Resource::create($mp);
        //var_dump($mp); exit;
        //$mpo = $mp->exporter;
        //当前图像
        if (is_null($this->im)) $this->im = imagecreatefromstring(file_get_contents($this->realPath));
        //当前图像尺寸
        $w = imagesx($this->im);
        $h = imagesy($this->im);
        //水印应缩放到的尺寸
        $ww = $w * (int)$opt[3] / 100;
        $wh = $ww / $mp->ratio;
        //缩放水印
        $wim = imagecreatetruecolor($ww, $wh);
        imagecopyresampled($wim, $mp->source, 0, 0, 0, 0, $ww, $wh, $mp->width, $mp->height);
        //水印位置
        list($x, $y) = $this->watermarkPosition($w, $h, $ww, $wh, $opt[1], $opt[2]);
        //水印copy到当前图像
        imagecopy($this->im, $wim, $x, $y, 0, 0, $ww, $wh);
        
        return $this;*/
    }
    //图片 灰度化
    protected function processGray($p="yes")
    {
        if ($p!=="yes") return;
        //静态方法
        $this->im = self::grayImage($this->im);
    }



    /**
     * 格局传入的 资源句柄，调用对应的 imagejpeg | imagepng | imagegif | imagebmp | imagewebp 方法
     * 返回 生成的 图片二进制数据
     * @param Object $source 图片资源句柄
     * @return String 图片二进制数据
     */
    protected function getImageData($source)
    {
        //获取资源 mime
        $mime = $this->mime;
        //image*** 方法
        $m = str_replace("/", "", $mime);   // imagejpeg, imagepng, imagegif, imagewebp, imagebmp
        //调用 image*** 方法，向输出缓冲区写入 图片数据
        if ($this->ext === "png") {
            $m($source);
        } else {
            $m($source, null, 100);
        }
        //从缓冲区 获取图片数据
        $imgData = ob_get_contents();
        ob_clean();

        //返回数据
        return $imgData;
    }



    /**
     * 图片处理方法，可外部调用的工具方法
     */

    /**
     * 图片转为灰度
     * @param Object $source 图片资源句柄
     * @return Object|false 处理后的 资源句柄 处理失败时 返回 false
     */
    public static function grayImage($source)
    {
        $width = imagesx($source);
        $height = imagesy($source);

        //创建目标画布（与原图尺寸相同）
        $grayImg = imagecreatetruecolor($width, $height);
        if (!$grayImg) return false;

        //遍历每个像素，计算灰度值并赋值
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // 获取当前像素的RGB值
                $rgb = imagecolorat($source, $x, $y);
                $r = ($rgb >> 16) & 0xFF; // 红色分量
                $g = ($rgb >> 8) & 0xFF;  // 绿色分量
                $b = $rgb & 0xFF;         // 蓝色分量

                // 计算灰度值（常用公式：0.299*R + 0.587*G + 0.114*B）
                $gray = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);

                // 创建灰度颜色（R=G=B=灰度值）
                $grayColor = imagecolorallocate($grayImg, $gray, $gray, $gray);

                // 将灰度颜色赋值给目标画布
                imagesetpixel($grayImg, $x, $y, $grayColor);
            }
        }

        return $grayImg;
    }
    
    /**
     * 等比缩放，百分比
     * @param Object $source 图片资源句柄
     * @param Int $pec 缩放百分比 默认 100
     * @return Object|false 处理后的 资源句柄 处理失败时 返回 false
     */
    public static function zoomImage($source, $pec=100)
    {
        if ($pec===100) return $source;

        //图片尺寸
        $ow = imagesx($source);
        $oh = imagesy($source);
        $w = round($ow * $pec/100);
        $h = round($oh * $pec/100);

        //重绘
        $im = imagecreatetruecolor($w, $h);
        imagecopyresampled($im, $source, 0, 0, 0, 0, $w, $h, $ow, $oh);
        return $im;
    }

    /**
     * 缩放并裁剪，直到适合输入的 宽高
     * @param Object $source 图片资源句柄
     * @param Int $w 输出的 宽度
     * @param Int $h 输出的 高度
     * @return Object|false 处理后的 资源句柄 处理失败时 返回 false
     */
    public static function clipImage($source, $w, $h)
    {
        if ($w<=0 && $h<=0) return $source;

        //图片尺寸
        $ow = imagesx($source);
        $oh = imagesy($source);
        $ro = $ow/$oh;

        //指定的 输出宽高 有一个为 0，转为 zoom
        if ($w<=0 || $h<=0) {
            $pec = $w<=0 ? round(100 * $h/$oh) : round(100 * $w/$ow);
            //调用 zoom 方法
            return self::zoomImage($source, $pec);
        }

        //指定的 输出宽高比
        $r = $w/$h;

        //如果 输出宽高比 等于 原宽高比，转为 zoom
        if ($ro === $r) {
            $pec = round(100 * $w/$ow);
            //调用 zoom 方法
            return self::zoomImage($source, $pec);
        }

        //计算裁切尺寸
        if ($ro < $r) {
            //需要裁切 高度
            $cw = $ow;
            $ch = $cw/$r;
        } else {
            //需要裁切 宽度
            $ch = $oh;
            $cw = $ch * $r;
        }
        
        //先裁剪到目标宽高比 从图片中心裁切
        $im = imagecreatetruecolor($cw, $ch);
        imagecopy($im, $source, 0, 0, ($ow-$cw)/2, ($oh-$ch)/2, $cw, $ch);
        //再缩放到目标尺寸
        $newim = imagecreatetruecolor($w, $h);
        imagecopyresampled($newim, $im, 0, 0, 0, 0, $w, $h, $cw, $ch);
        //销毁中间图片源
        imagedestroy($im);

        return $newim;
    }

    //根据图片尺寸，水印尺寸，水印位置，计算水印坐标
    /*protected function watermarkPosition($w, $h, $ww, $wh, $xpos = "left", $ypos = "top")
    {
        $sep = 5;   //水印边距 5%
        $x = 0;
        $y = 0;
        switch ($xpos) {
            case "left" :       $x = $w * $sep / 100; break;
            case "right" :      $x = ($w * (100-$sep) / 100) - $ww; break;
            case "center" :     $x = ($w - $ww) / 2; break;
        }
        switch ($ypos) {
            case "top" :        $y = $h * $sep / 100; break;
            case "bottom" :     $y = ($h * (100-$sep) / 100) - $wh; break;
            case "center" :     $y = ($h - $wh) / 2; break;
        }
        return [$x, $y];
    }*/
}
