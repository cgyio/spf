<?php
/**
 * SPF-Theme 主题模块 
 * 主题 样式参数系统 模块
 */

namespace Spf\module\src\resource\theme;

use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Conv;

class ThemeVarsModule extends ThemeModule 
{
    /**
     * 定义 此主题模块的 key
     * !! 覆盖父类
     */
    public $key = "vars";

    /**
     * 定义此模块 在主题文件中的 标准参数数据格式
     * !! 覆盖父类
     */
    //此模块完整的 标准参数格式
    protected static $stdCtx = [
        //样式参数分组
        "groups" => [
            //作为基本参数的 分组
            "base" => [
                "font", "ani",
                //...
            ],
            //作为静态参数
            "static" => [],
            //作为当前主题 自定义参数
            "custom" => [],
            //可 额外定义 别的分组
            //...
        ],

        //通用 颜色参数
        "common" => [
            //字体规则
            "font" => [
                "value" => [
                    //font-family，css 变量  --font-fml[-code|-fangsong]
                    "fml" => [
                        //默认
                        "default"   => "-apple-system,BlinkMacSystemFont,'Segoe UI','PingFang SC','Microsoft Yahei',Helvetica,Arial,sans-serif,'Apple Color Emoji','Segoe UI Emoji'",
                        //code
                        "code"      => "'Cascadia Code', 'Consolas', 'Courier New', 'Pingfang SC', 'Microsoft Yahei', monospace",
                        //打印字体样式
                        "fangsong"  => "'Times New Roman', '仿宋'",
                        "yahei"     => "'微软雅黑', 'Microsoft Yahei'",
                        "songti"    => "'Times New Roman', '宋体'",
                    ],
    
                    //可定义 其他规则，自动生成 css 变量
                    //...
                ],
            ],

            //动画规则
            "ani" => [
                "value" => [
                    //transition dura，css 变量 ---ani-dura
                    "dura" => "0.3s",
                ],
            ],
        ],

        //定义所有 mode 模式下的 参数
        "modes" => [
            //light 模式
            "light" => [],

            //dark 模式
            "dark" => [],

            //mobile 模式
            "mobile" => [],
        ],
    ];
    //此模块中，某个 参数 item 的 标准参数格式
    protected static $stdItem = [
        //... 基类中定义的 ...
    ];
    //此模块中，所有 参数 item 的分组类型
    protected static $stdGroups = [
        //"base", "static", "custom", 
    ];
    //是否已与 ThemeModule 基类合并了 $stdFoobar
    protected static $stdMerged = false;
    //定义此模块的 默认 mode 模式，通常为 light
    protected static $dftMode = "light";

    

    /**
     * 创建 SCSS 变量定义语句 rows
     * !! 覆盖父类
     * @param ThemeExporter $exper 资源输出类实例
     * @param Array $ctx 资源输出类的 context["module_name"]
     * @return ThemeExporter 返回生成 content 缓存后的 资源输出类实例
     */
    public function createScssVarsDefineRows(&$exper, $ctx)
    {
        //生成 其他变量 的 SCSS 变量定义语句，保存到 $exper->content 缓存
        $conf = $this->origin;

        // 0    生成 $foo-bar-jaz... 变量
        $flat = Arr::flat($ctx,"-");
        foreach ($flat as $vk => $vv) {
            $exper->rowDef($vk, $vv, [
                "quote" => "\"",
            ]);
        }
        //空行
        $exper->rowAddEmpty(1);

        return $exper;
    }
    
    /**
     * 创建 CSS 变量定义语句 rows
     * !! 覆盖父类
     * @param ThemeExporter $exper 资源输出类实例
     * @param Array $ctx 资源输出类的 context["module_name"]
     * @return ThemeExporter 返回生成 content 缓存后的 资源输出类实例
     */
    public function createCssVarsDefineRows(&$exper, $ctx)
    {
        // 生成 --item-m 变量
        $flat = Arr::flat($ctx,"-");
        foreach ($flat as $vk => $vv) {
            $exper->rowDef("--".$vk, $vv, [
                "prev" => "",
            ]);
        }
        return $exper;
    }

    /**
     * 判断给定的 值 是否可以作为 当前模块的 参数 item 的 值
     * !! 覆盖父类
     * @param Mixed $val
     * @return Mixed|false 不是合法的 参数值 返回 false， 否则返回处理后的 val
     */
    public static function isItemValue($val)
    {
        //合法的 样式参数值，必须是 associate 关联数组
        if (!Is::nemarr($val) || !Is::associate($val)) return false;
        return $val;
    }
}