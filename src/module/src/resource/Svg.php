<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Svg 子类
 * 继承自 Plain 纯文本类型基类，处理 *.svg 类型本地文件
 */

namespace Spf\module\src\resource;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Conv;
use Spf\util\Path;

class Svg extends Plain 
{
    /**
     * 通用的 svg 代码头
     */
    public static $svgh = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
    public static $svgns = "<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" ";



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