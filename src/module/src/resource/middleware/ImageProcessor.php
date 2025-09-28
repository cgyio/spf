<?php
/**
 * Resource 资源处理中间件 Processor 处理器子类
 * ImageProcessor 专门处理 Image 类型图片资源 相关操作
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\SrcException;
use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

class ImageProcessor extends Processor 
{
    /**
     * 定义此中间件的 标准参数 控制具体的处理方法
     * 中间件运行时，传入的参数 将与此合并
     * !! 覆盖父类
     */
    protected static $stdParams = [
        "stage" => "get_content",
        "content" => "",
    ];

    //设置 当前中间件实例 在资源实例中的 属性名，不指定则自动生成 NS\middleware\FooBar  -->  foo_bar
    public static $cacheProperty = "imager";



    /**
     * 处理器初始化动作
     * !! Processor 子类 必须实现
     * @return $this
     */
    protected function initialize()
    {
        return $this;
    }

    /**
     * 自定义 get_content 阶段 执行的操作
     * 获取 image 图片资源 二进制内容
     * !! Processor 子类 必须实现
     * @return Bool
     */
    protected function stageGetContent()
    {
        //手动调用 GetContent 中间件 获取 resource->content
        static::manual("GetContent", $this->resource, [
            "content" => $this->params["content"]
        ]);

        if (!Is::nemstr($this->resource->content)) {
            //图片资源的 二进制内容 读取失败
            throw new SrcException("无法读取图片内容", "resource/getcontent");
        }
        
        //通过图片内容，创建 GD 库资源句柄
        $source = imagecreatefromstring($this->resource->content);
        if ($source === false) {
            //无法创建图片资源 句柄
            throw new SrcException("无法读取图片内容", "resource/getcontent");
        }
        $this->resource->source = $source;

        return true;
    }

    /**
     * 自定义 get_meta 阶段 执行的操作
     * 获取 image 图片资源 meta 元数据
     * !! Processor 子类 必须实现
     * @return Bool
     */
    protected function stageGetMeta()
    {
        $cnt = $this->resource->content;

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

        //写入 meta 数据
        $this->resource->meta = [
            "width" => (int)$w,
            "height" => (int)$h,
            "bit" => (int)$bit,
            "ratio" => (int)$w/(int)$h,
            "size" => $size,
            "sstr" => $sstr
        ];

        //如果 ext|mime 不一致 修改 mime
        if ($this->resource->mime !== $mime) {
            $this->resource->mime = $mime;
            $this->resource->ext = Mime::getExt($mime);
        }

        return true;
    }

    /**
     * 自定义 process_image 阶段 执行的操作
     * 输出之前 依次处理 image 图片 生成最终图片 二进制数据
     * !! Processor 子类 必须实现
     * @return Bool
     */
    protected function stageProcessImage()
    {
        $res = $this->resource;
        //当前资源的 params
        $cps = $res->params;
        //图片资源的 标准 params
        $std = $res::$stdParams;

        //先缓存 图片资源句柄
        $this->resource->im = $res->source;

        //严格按 stdParams 顺序，依次执行图片处理
        foreach ($std as $opr => $op) {
            //params 中 此操作的 参数
            $cop = $cps[$opr] ?? null;
            if (is_null($cop) || $cop === $op || $cop === false) {
                //未指定此操作的参数 或 指定了跳过此操作，跳过操作
                continue;
            }
            
            //调用处理方法
            $m = "processImage".Str::camel($opr, true);
            if (method_exists($this, $m)) {
                if ($cop === true) {
                    //指定此操作参数为 true 时，不传入参数，使用默认参数
                    $this->$m();
                } else {
                    $this->$m($cop);
                }
            }
        }

        return true;
    }



    /**
     * 工具方法
     */

    /**
     * 图片处理的 具体方法，处理后的 source 缓存到 $this->resource->im
     * @param String $p 处理参数
     * @return void
     */
    //等比例缩放
    protected function processImageZoom($p="100")
    {
        $p = empty($p) || !is_numeric($p) ? 100 : (int)$p;
        if ($p===100) return;
        $this->resource->im = self::zoomImage($this->resource->im, $p);
    }
    //缩放到指定宽高，当指定的宽高比不等于原图片时，保证缩放后的图片不超出指定的宽高
    protected function processImageClip($p="0x0")
    {
        if ($p==="0x0") return;
        if (Is::nemstr($p) && strpos($p, "x")!==false) {
            $opt = explode("x", $p);
        } else {
            $opt = Arr::mk($p);
        }
        if (!Is::nemarr($opt) || !Is::indexed($opt)) return;
        if (count($opt)<2) $opt[] = $opt[0];
        $this->resource->im = self::clipImage($this->resource->im, (int)$opt[0], (int)$opt[1]);
    }
    //自动生成缩略图，裁切原图，满足缩略图宽高比
    protected function processImageThumb($p="256")
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
        $this->resource->im = self::clipImage($this->resource->im, (int)$opt[0], (int)$opt[1]);
    }
    //添加水印
    protected function processImageMark($p="cphp,right,bottom,25")
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
        if (is_null($this->resource->im)) $this->resource->im = imagecreatefromstring(file_get_contents($this->realPath));
        //当前图像尺寸
        $w = imagesx($this->resource->im);
        $h = imagesy($this->resource->im);
        //水印应缩放到的尺寸
        $ww = $w * (int)$opt[3] / 100;
        $wh = $ww / $mp->ratio;
        //缩放水印
        $wim = imagecreatetruecolor($ww, $wh);
        imagecopyresampled($wim, $mp->source, 0, 0, 0, 0, $ww, $wh, $mp->width, $mp->height);
        //水印位置
        list($x, $y) = $this->watermarkPosition($w, $h, $ww, $wh, $opt[1], $opt[2]);
        //水印copy到当前图像
        imagecopy($this->resource->im, $wim, $x, $y, 0, 0, $ww, $wh);
        
        return $this;*/
    }
    //图片 灰度化
    protected function processImageGray($p=true)
    {
        if ($p!==true) return;
        //静态方法
        $this->resource->im = self::grayImage($this->resource->im);
    }



    /**
     * 图片处理方法，可外部调用的 静态工具方法
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