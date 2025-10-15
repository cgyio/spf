<?php
/**
 * Compound 复合资源处理类 子类
 * 定义 Cdn 类型的 远程库资源，处理 库资源的 获取|缓存|输出
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\SrcException;
use Spf\module\src\Mime;
use Spf\Request;
use Spf\Response;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;

class Cdn extends Compound 
{
    /**
     * 定义 复合资源在其 json 主文件中的 资源描述数据的 标准格式
     * !! 在父类基础上扩展
     */
    public static $stdDesc = [
        //此 库资源的变量名 FooBar 形式
        "var" => "",

        //允许输出的 ext 类型数组，必须是 Mime 类中支持的后缀名类型
        "ext" => ["js","css","woff","otf","ttf"],

        /**
         * 复合资源的根路径
         *  0   针对本地的复合资源 Theme | Icon | Vcom | Lib 等类型：
         *      此参数表示 此复合资源的本地保存路径，不指定则使用当前 *.ext.json 文件的 同级同名文件夹
         *  1   针对远程复合资源 Cdn 类型：
         *      此参数表示 cdn 资源的 url 前缀，不带版本号
         * !! 必须指定一个 远程 cdn 地址
         * !! cdn 地址必须是 不含 版本号的 url 前缀
         */
        "root" => "",

    ];

    /**
     * 定义复合资源 内部子资源的 描述参数
     * !! 在父类基础上扩展
     */
    public static $stdSubResource = [
        //子资源类型，可以是 static | dynamic   表示   静态的实际存在的 | 动态生成的   子资源内容
        //!! Cdn 子资源默认为 dynamic 类型
        "type" => "dynamic",
    ];
    
    
    
    /**
     * 工具方法 解析复合资源内部 子资源参数，查找|创建 子资源内容
     * 根据  子资源类型|子资源ext|子资源文件名  分别制定对应的解析方法
     * !! 覆盖父类
     * @return $this
     */
    //dynamic 动态创建 子资源内容
    protected function createDynamicSubResource($opts=[])
    {
        /**
         * 拼接 $desc["root"]/[version]/export-file-name.exportExt 得到远程 cdn 文件地址
         * 读取 cdn 文件，手动创建 子资源实例
         */
        $desc = $this->desc;
        $root = $desc["root"] ?? null;
        $pather = $this->PathProcessor;
        if (!Is::nemstr($root) || $pather->isRemote($root)!==true) {
            //未指定 远程 cdn 地址  或  指定的地址不正确，报错
            throw new SrcException("当前 CDN 库 ".$this->resName().".cdn.json 中未指定有效的 CDN 网址", "resource/getcontent");
        }

        //子资源名称
        $fn = $this->subResourceName;
        //子资源 描述参数
        $opts = $this->subResourceOpts;
        //子资源 file
        $file = $opts["file"] ?? null;
        if (!Is::nemstr($file)) {
            //未指定 目标子资源的 file 文件名
            throw new SrcException("当前 CDN 库 ".$this->resName().".cdn.json 中未指定 ".$this->resExportBaseName()." 对应的文件名", "resource/getcontent");
        }
        //拼接获取 子资源远程地址
        $furl = rtrim($root,"/")."/".$this->resVersion()."/".trim($file, "/");
        if (Resource::exists($furl)!==true) {
            //指定的 目标子资源文件 在 cdn 中不存在
            throw new SrcException("当前 CDN 库 ".$this->resName()." 中未指定的 ".$this->resExportBaseName()." 对应的文件，在 CDN 服务器中不存在", "resource/getcontent");
        }

        //子资源实例化参数
        $ps = $opts["params"] ?? [];
        //此 库资源的 变量名
        $var = $desc["var"] ?? null;
        if (Is::nemstr($var)) $ps = Arr::extend(["var" => $var], $ps);
        $ps = Arr::extend([
            //belongTo
            "belongTo" => $this,
            //不忽略 $_GET
            "ignoreGet" => false,
        ], $ps);

        //根据 子资源远程地址 创建资源实例
        $subres = Resource::create($furl, $ps);

        //缓存子资源实例 到 $this->subResource 属性
        $this->subResource = $subres;
        
        return $this;
    }



    /**
     * 输出之前对 content 执行一些特殊操作，在子资源参数的 fix 项中定义
     * 这些自定义操作，可能会修改最终输出内容 content
     * !! 这些修改 不会被缓存！因为在执行这些操作之前，content 内容已经被写入缓存文件
     */

    /**
     * 针对 Vue UI 库的 css 资源，处理其中可能存在的 icon-font 路径
     * @return $this
     */
    protected function fixIconFontsBeforeExport()
    {
        $cnt = $this->content;
        
        //匹配
        preg_match_all("/url\s*\(['\"]?(font.*)['\"]?\)/U", $cnt, $mts);
        if (!isset($mts[1]) || !Is::nemarr($mts[1])) return $this;

        //依次处理匹配到的 icon-font css 定义语句
        $pather = $this->PathProcessor;
        foreach ($mts[1] as $mti) {
            //获取 font url 路径
            $furl = $pather->innerUrl($mti);
            //替换原始路径
            $cnt = str_replace($mti, $furl, $cnt);
        }

        //修改后的 content 写入资源实例
        $this->content = $cnt;

        return $this;
    }
}