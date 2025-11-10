<?php
/**
 * SPF-Theme 主题模块 
 * 主题 尺寸系统 模块
 */

namespace Spf\module\src\resource\theme\module;

use Spf\module\src\SrcException;
use Spf\module\src\resource\theme\Module;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Conv;

class Vars extends Module
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
    protected static $stdDef = [
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
            //default 模式
            "default" => [],
        ],
    ];
    //此模块中，某个 参数 item 的 标准参数格式
    protected static $stdItem = [
        //... 基类中定义的 ...
    ];
    //此模块中，所有 参数 item 的分组类型
    protected static $stdGroups = [
        "base", "static", "custom", 
    ];
    //定义此模块支持的 mode 模式列表
    protected static $stdModes = [
        "default",
    ];



    /**
     * createExtContentRows
     * !! 子类必须实现
     * @param Array $ctx 要输出的 主题参数数组，通常来自于 $this->getItemByMode() 方法
     * @param RowProcessor $rower 临时资源的 内容行处理器
     * @return RowProcessor
     */
    //createScssContentRows
    protected function createScssContentRows($ctx=[], &$rower)
    {
        // 0    生成 $foo-bar-jaz... 变量
        $flat = Arr::flat($ctx,"-");
        foreach ($flat as $vk => $vv) {
            $rower->rowDef($vk, $vv, [
                "quote" => "\"",
            ]);
        }
        //空行
        $rower->rowEmpty(1);

        //SCSS 语句 需要包含 css 变量定义语句
        return $this->createCssContentRows($ctx, $rower);
    }
    //createCssContentRows
    protected function createCssContentRows($ctx=[], &$rower)
    {
        /**
         * 定义 css 尺寸变量语句
         */
        $rower->rowAdd(":root {", "");
        $flat = Arr::flat($ctx, "-");
        foreach ($flat as $vk => $vv) {
            $rower->rowDef("--".$vk, $vv, ["prev" => ""]);
        }
        $rower->rowAdd("}", "");
        //空行
        $rower->rowEmpty(1);

        return $rower;
    }



    /**
     * 静态方法
     */

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