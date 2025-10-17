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
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    public static $stdParams = [
        /**
         * 可在资源实例化时，指定 一个 Compound 复合资源 作为此资源的 parentResource 
         * !! 此参数无法通过 url 传递，只能在资源实例化时，手动传入
         */
        //"belongTo" => null,
        //是否忽略 $_GET 参数
        //"ignoreGet" => false,
        
        //等比缩放，默认 100  
        "zoom" => false,
        //缩放并裁剪，例如：原图 1920*1080 输入裁剪参数 1920x1200 最终输出尺寸 1728*1080 放大到 1920*1200
        "clip" => false,
        //缩略图，默认 256 方形，可任意形状 256|128x128|256x128
        "thumb" => false,
        //水印，可指定字符，或水印图片路径
        "mark" => "",   //TODO:
        //灰度
        "gray" => false,

        //可额外定义此资源的 中间件，这些额外的中间件 将在资源实例化时，附加到预先定义的中间件数组后面
        /*"middleware" => [
            //create 阶段
            "create" => [],
            //export 阶段
            "export" => [],
        ],*/
    ];

    /**
     * 针对此资源实例的 处理中间件
     * 需要严格按照先后顺序 定义处理中间件
     * !! 覆盖父类
     */
    public $middleware = [
        //资源实例 构建阶段 执行的中间件
        "create" => [
            //使用资源类的 stdParams 标准参数数组 填充 params
            "UpdateParams" => [],
            //获取 image 图片资源的 content 二进制数据，同时创建 GD 库的资源句柄
            "ImageProcessor #1" => [
                "stage" => "get_content"
            ],
            //获取 image 图片资源的 meta 数据
            "ImageProcessor #2" => [
                "stage" => "get_meta"
            ],
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],
            //根据输出参数 params 处理图片，生成 imageData
            "ImageProcessor #3" => [
                "stage" => "process_image"
            ],
        ],
    ];

    //image处理句柄
    public $source = null;
    //经过处理步骤后 缓存的 source
    public $im = null;

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
     * 工具方法
     */

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



    
}
