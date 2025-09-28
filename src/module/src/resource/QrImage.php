<?php
/**
 * Image 图片资源处理类 子类
 * QrImage 专门处理 QRcode 二维码图片
 */

namespace Spf\module\src\resource;

use Spf\module\src\ResourceMiddleware;
use Spf\module\src\SrcException;
use Spf\module\src\resource\util\QRcode as QR;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Url;
use Spf\util\Path;

class QrImage extends Image 
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    public static $stdParams = [
        //等比缩放，默认 100  
        "zoom" => false,
        //缩放并裁剪，例如：原图 1920*1080 输入裁剪参数 1920x1200 最终输出尺寸 1728*1080 放大到 1920*1200
        "clip" => false,
        //缩略图，默认 256 方形，可任意形状 256|128x128|256x128
        //"thumb" => false,
        //水印，可指定字符，或水印图片路径
        //"mark" => "",
        //灰度
        //"gray" => false,

        //针对 二维码 的生成参数
        //编码内容
        "s" => "SPF-QRcode",
        //编码内容的 类型 string|url
        "type" => "string",
        //二维码尺寸 1-10
        "size" => 8,
        //二维码边距
        "padding" => 1,
        //二维码容错级别 0-3
        "failover" => 3,
        //是否保存此二维码
        "save" => false,
        //此二维码的 保存文件名 不带后缀
        "file" => false,
    ];

    /**
     * 针对此资源实例的 处理中间件
     * 需要严格按照先后顺序 定义处理中间件
     * !! 覆盖父类
     */
    public $middleware = [
        //资源实例 构建阶段 执行的中间件
        "create" => [
            //二维码图片资源的主文件是 json 读取其内容
            "GetJsonContent" => [],
            //调用资源内部定义的 stage 方法 生成 二维码图片 二进制内容，并创建 GD 句柄
            "GetQrContent" => [],
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],
            //根据输出参数 params 处理图片，生成 imageData
            "ImageProcessor" => [
                "stage" => "process_image"
            ],
        ],
    ];

    //支持的 二维码图片格式
    public $qrext = "png";



    /**
     * 资源实力内部定义的 stage 处理方法
     * @param Array $params 方法额外参数
     * @return Bool 返回 false 则终止 当前阶段 后续的其他中间件执行
     */
    //GetQrContent 创建二维码图片 二进制内容
    public function stageGetQrContent($params=[])
    {
        //手动合并 params
        
        //二维码图片资源的 主文件是 json 其内容已被读取并转换到 jsonData 属性中
        $jd = $this->jsonData;
        //json 中定义的 params 参数
        $jps = $jd["params"] ?? [];
        //当前资源的 params
        $cps = $this->params;
        //标准的 params
        $std = static::$stdParams;
        //合并这些 params
        $this->params = Arr::extend($std, $jps, $cps);

        //!! QRcode 二维码图片格式 在 $res->qrext 中定义，默认是 png
        $this->setExt($this->qrext);

        //生成 二维码图片二进制内容
        $qrimg = $this->createQrImageContent();
        //保存到 content
        $this->content = $qrimg;

        //手动调用中间件
        ResourceMiddleware::manual("ImageProcessor", $this, [
            "stage" => "get_meta"
        ]);

        //通过图片内容，创建 GD 库资源句柄
        $source = imagecreatefromstring($this->content);
        if ($source === false) {
            //无法创建图片资源 句柄
            throw new SrcException("无法读取图片内容", "resource/getcontent");
        }
        $this->source = $source;

        return true;
    }



    /**
     * 工具方法
     */

    /**
     * 生成二维码图片的 二进制内容
     * @return String 图片的二进制内容
     */
    protected function createQrImageContent()
    {
        //二维码参数
        $ps = $this->params;

        //编码内容
        $s = $ps["s"] ?? "";
        //编码内容的 类型 string|url
        $type = $ps["type"] ?? "string";
        //二维码尺寸 1-10
        $size = $ps["size"] ?? 8;
        //二维码边距
        $padding = $ps["padding"] ?? 1;
        //二维码容错级别 0-3
        $failover = $ps["failover"] ?? 3;
        //是否保存此二维码
        $save = $ps["save"] ?? false;
        //此二维码的 保存文件名 不带后缀
        $file = $ps["file"] ?? false;

        //处理编码内容
        $s = urldecode($s);
        //处理 url 类型的 编码内容
        if ($type === "url") {
            $s = str_replace("|","/",$s);
        }

        //是否保存本次生成的 二维码图片
        if ($save!==false) {
            $dir = Path::find("spf/assets/qrcode", Path::FIND_DIR).DS;
            if (is_bool($file)) {
                $file = $dir."QR_".date("YmdHis",time()).".".$this->qrext;
            } else {
                $file = $dir.$file.".".$this->qrext;
            }
        }

        //生成 图片 二进制 内容
        $qrimg = QR::png($s, $file, $failover, $size, $padding, $save);
        if (empty($qrimg)) {
            //创建 二维码图片失败
            throw new SrcException("创建二维码图片失败", "resource/getcontent");
        }

        //返回 content
        return $qrimg;
    }
}