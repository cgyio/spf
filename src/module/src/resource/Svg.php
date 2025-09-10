<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Svg 子类
 * 继承自 Plain 纯文本类型基类，处理 *.svg 类型本地文件
 */

namespace Spf\module\src\resource;

use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Conv;
use Spf\util\Path;

class Svg extends Plain 
{
    /**
     * 定义 纯文本文件 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 子类应覆盖此属性，定义自己的 params 参数规则
     */
    protected static $stdParams = [
        //fill 修改 svg 中某些路径的 颜色
        "fill" => "",   // f00-f00--f00  -->  按路径顺序，分别指定颜色

        //pathes 输出 svg 的路径信息
        "pathes" => false,
        
        //其他可选参数
        //...
    ];
    
    /**
     * 此类型纯文本资源的 注释符 [ 开始符号, 每行注释的开头符号, 结尾符号 ]
     * !! 子类必须覆盖此属性
     */
    public $cm = ["<!--", "  ", "-->"];

    /**
     * 通用的 svg 代码头
     */
    public static $svgh = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
    public static $svgns = "<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" ";

    //此 svg 包含的 path 路径数组
    protected $pathes = [
        //"<path ... ></path>", 路径代码
    ];


    /**
     * 当前资源创建完成后 执行
     * !! 覆盖父类
     * @return Resource $this
     */
    protected function afterCreated()
    {
        //格式化 params 
        $this->formatParams();

        //拆分 svg 路径到 pathes 数组
        $this->splitPathes();

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
        $this->extendParams($params);
        $this->formatParams();

        //分别处理 params 中的参数
        foreach ($this->params as $k => $v) {
            //查找对应的 apply 方法
            $m = "apply".Str::camel($k, true);
            if (method_exists($this, $m)) {
                $this->$m();
            }
        }

        //pathes
        if ($this->params["pathes"] === true) {
            //调整输出文件格式为 json
            $this->ext = "json";
            $this->mime = Mime::getMime("json");
            //准备 content
            $this->content = Conv::a2j([
                "pathes" => $this->pathes
            ]);
        }

        return $this;
    }

    /**
     * 将 svg 中的 <path ...></path> 拆分为数组 缓存到 pathes
     * @return $this
     */
    protected function splitPathes()
    {
        $ctx = $this->content;
        $this->pathes = [];
        $pa1 = explode("<path", $ctx);
        $pa1 = array_slice($pa1, 1);
        foreach ($pa1 as $pai) {
            $pa = str_replace("</svg>","",$pai);
            $this->pathes[] = "<path".$pa;
        }
        return $this;
    }



    /**
     * 根据 params 处理 svg 代码
     * @return $this
     */
    //处理 fill 参数
    protected function applyFill()
    {
        $ps = $this->params;
        $fill = $ps["fill"] ?? "";
        if (Is::nemstr($fill)) $fill = explode("-", $fill);
        if (!Is::nemarr($fill) || !Is::indexed($fill)) return $this;
        $fln = count($fill);

        //当前 svg 代码
        $ctx = $this->content;

        //pathes 路径数组
        $pathes = $this->pathes;

        foreach ($pathes as $i => $path) {
            if ($fln>1 && $i>=$fln) continue;
            $fc = $fln===1 ? $fill[0] : $fill[$i];
            //$fc == "" 则跳过
            if ($fc=="") continue;
            //查找代码中的 fill="..." 片段，切换为指定的 fc 颜色
            $mts = [];
            $mt = preg_match("/(fill=\"\#?[0-9a-fA-F]*\")/", $path, $mts);
            if ($mt !== 1) {
                //没有 fill="..." 片段
                $npath = str_replace("></path>", "fill=\"#".$fc."\" ></path>", $path);
            } else {
                //找到代码片段，替换颜色
                //匹配的字符串
                $mti = array_slice($mts, 1)[0];
                $npath = str_replace($mti, "fill=\"#".$fc."\"", $path);
            }
            //写入替换后的 pathes
            $this->pathes[$i] = $npath;
            //替换 svg 代码
            $ctx = str_replace($path, $npath, $ctx);
        }

        //写入替换后的 content
        $this->content = $ctx;

        return $this;
    }



    /**
     * 静态方法
     */

    /**
     * 将图标库的 glyph["svg"] 转为标准的 svg 代码
     * @param String $glyph 通标库中 通过解析得到的 svg 代码 <svg id="" viewbox="">...</svg>
     * @return String 标准的 svg 代码格式 <?xml version="1.0" charset="utf-8"?><svg version="" xmlns="" xmlns:xlink="" ...>...</svg>
     */
    public static function glyphToSvg($glyph)
    {
        if (!Is::nemstr($glyph) || strpos($glyph, "<svg")===false) return null;
        $svg = [];
        $svg[] = static::$svgh;
        if (strpos($glyph, "<svg version=\"")===false) {
            $svg[] = preg_replace("/<svg\s+/", static::$svgns, $glyph);
        }
        return implode("", $svg);
    }
}