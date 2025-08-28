<?php
/**
 * SPF-Theme 主题模块 
 * 主题 颜色系统 模块
 */

namespace Spf\module\src\resource\theme;

use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Color;

class ThemeColorModule extends ThemeModule 
{
    /**
     * 定义 此主题模块的 key
     * !! 覆盖父类
     */
    public $key = "color";

    /**
     * 定义此模块 在主题文件中的 标准参数数据格式
     * !! 覆盖父类
     */
    //此模块完整的 标准参数格式
    protected static $stdCtx = [
        //颜色分组
        "groups" => [
            //作为基本颜色的 颜色分组
            "base" => [
                //基本颜色 默认值，所有 mode 都应指定这些颜色
                //主要基本色
                "red", "orange", "yellow", "green", "cyan", "blue", "purple", "gray",
                //主要基本色的 别名，这些颜色都指向 上述的 主要基本色
                "danger",   //red
                "warn",     //yellow
                "success",  //green
                "primary",  //blue
                //业务主题色
                "bz",
                //其他用途 基本色
                "fc", "bgc", "bdc", 
                //还可以定义 其他基本色
                //...
            ],
            //作为静态颜色的 颜色分组，这些颜色 都不开启 shift
            "static" => [
                //这些 静态色 必须定义
                "white", "black",
                "shadow",
            ],
            //作为当前主题 自定义颜色的 颜色分组
            "custom" => [],
            //可 额外定义 别的分组
            //...
        ],
            
        //可设置通用的 shift 参数
        "shift" => [
            //自动 加深|减淡 极限值
            "lvl" => [ 
                "max" => 96, 
                "min" => 24 
            ],
            //加深|减淡 的级数
            "steps" => 3,
        ],

        //通用 颜色参数
        "common" => [
            //分别定义 颜色分组中指定的 必须的 颜色
            "red" => [
                //标准颜色参数 数据格式
                "value" => "#fa5151",

                //这些参数 可以使用通用参数，或自动生成，可以不指定
                //"key" => "red",
                //"shift" => [ ... ],
                //"alias" => [ ... ],
                //"editor" => [ ... ],
            ],

            //还可以使用 简易的定义方法 直接指定 原始色值，其他参数都是用 通用|默认 参数
            "orange"    => "#ff8600",
            "yellow"    => "#fa9d3b",
            "green"     => "#07c160",
            "cyan"      => "#01c4b3",
            "blue"      => "#1485ee",
            "purple"    => "#ea6e9c",
            "gray"      => "#888888",
            "bz"        => "#ff8600",
            "fc"        => "#737373",       //字体颜色
            "bgc"       => "#f2f2f2",       //背景色
            "bdc"       => "#efefef",       //边框色
            "shadow"    => "#0000004d",     //阴影色，色值可添加透明度
            //静态颜色
            "white"     => "#ffffff",
            "black"     => "#000000",

            //别名颜色，可直接指定 要指向的 颜色 key
            "danger"    => "red",
            "warn"      => "yellow",
            "success"   => "green",
            "primary"   => "blue",
        ],

        //定义所有 mode 模式下的 参数
        "modes" => [
            //light 模式
            "light" => [
                //可定义 此模式下的 shift 参数，覆盖通用的 shift 参数
                //"shift" => [ ... ],

                //如果有不同于 common 设置的，在此指定
                //...
            ],

            //dark 模式
            "dark" => [
                //可定义 此模式下的 shift 参数，覆盖通用的 shift 参数
                "shift" => [
                    //dark 模式下，可适当调低 颜色亮度
                    "lvl" => [ 
                        "max" => 76, 
                        "min" => 4 
                    ],
                ],

                //如果有不同于 common 设置的，在此指定
                //dark 模式下 前景色|背景色|边框色 反转
                "fc"        => "#8c8c8c",
                "bgc"       => "#121212",
                "bdc"       => "#1a1a1a",
                //dark 模式下 阴影为 白色
                "shadow"    => "#ffffff4d",
                //dark 模式下，黑白色 互换
                "white"     => "#000000",
                "black"     => "#ffffff",
            ],

            //mobile 模式
            "mobile" => [
                //颜色完全与 common 一致...
            ],
        ],
    ];
    //此模块中，某个 参数 item 的 标准参数格式
    protected static $stdItem = [

        //... 基类中定义的 ...

        //颜色在主题中自动 加深|减淡 的参数
        "shift" => [
            //启用 自动 加深|减淡 通过 hsl 明度计算 l 值
            "on" => true,
            //加深|减淡 l 极限值 0~1 之间 [ 最大(亮|淡), 最小(暗|深) ]
            "lvl" => [ 
                "max" => 96, 
                "min" => 24 
            ],
            //加深|减淡 级数 默认 3 表示 color_[d3|d2|d1|自身|l1|l2|l3]
            "steps" => 3,
            //可 手动 覆盖 加深|减淡 后的 色值
            "manual" => [
                /*
                "d2" => "...",
                */
            ],
        ],
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
        //生成 颜色系统模块的 SCSS 变量定义语句，保存到 $exper->content 缓存
        $conf = $this->origin;

        // 0    生成 $colorListGroupName 列表变量，例如：$colorListBase
        $groups = $conf["groups"] ?? [];
        $allitems = [];
        foreach ($groups as $grk => $gritems) {
            if (!Is::nemarr($gritems)) continue;
            //合并 items
            $gs = array_merge([], array_diff($gritems, $allitems));
            $allitems = array_merge($allitems, $gs);
            //变量名
            $vk = "colorList".Str::camel($grk, true);
            //变量值 item 名称数组
            $vv = $gritems;
            //调用 $exper->rowDef() 方法 生成 变量 定义语句
            $exper->rowDef($vk, $vv);
        }

        // 1    生成 $colorListAll 变量
        if (Is::nemarr($allitems)) $exper->rowDef("colorListAll", $allitems);

        // 2    生成 $colorShiftQueue
        //获取 color 模块最终输出参数的 shift 级数
        $steps = static::autoShiftSteps($ctx);
        if ($steps>0) {
            $que = ["m"];
            for ($i=1;$i<=$steps;$i++) {
                array_unshift($que, "l".$i);
                $que[] = "d".$i;
            }
            $exper->rowDef("colorShiftQueue", $que);
        }
        //空行
        $exper->rowAddEmpty(1);

        // 3    生成 $color-item-m 变量
        $flat = Arr::flat($ctx, "-");
        foreach ($flat as $vk => $vv) {
            $exper->rowDef("color-".$vk, $vv);
        }
        //空行
        $exper->rowAddEmpty(1);

        // 4    生成 $color-map: ( ... );
        $exper->rowAdd("\$color-map: (", "");
        foreach ($flat as $vk => $vv) {
            $exper->rowDef($vk, $vv, [
                "prev" => "",
                "rn" => ",",
            ]);
        }
        $exper->rowAdd(");", "");
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
        // 生成 --color-item-m 变量
        $flat = Arr::flat($ctx,"-");
        foreach ($flat as $vk => $vv) {
            $exper->rowDef("--color-".$vk, $vv, [
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
        //合法的 颜色参数值，只能是 合法的 颜色字符串  #ffffff | rgba() | hsl() ...
        if (false === Color::isColorString($val)) return false;
        return $val;
    }

    /**
     * 处理 某个 参数 item 的 auto shift
     * !! 需要 auto shift 的 主题模块，必须实现此方法
     * 自动 加深|减淡 颜色
     * @param String $item 参数 item 名称 key
     * @param Array $conf item 设置参数
     * @return Array|null 处理后的 conf 所有 参数 item 的值（原始值|变体值）都保存在 value 项下
     */
    protected static function autoShiftItem($item, $conf)
    {
        if (!Is::nemarr($conf)) return $conf;

        //此 item 的原始值 
        $oval = $conf["value"] ?? null;
        if (false === static::isItemValue($oval)) {
            //原始值不合法 !! 通常不会发生此情况
            return null;
        }

        //auto shift 后的 item 值 数组，原始值 key 为 m
        $val = [
            "m" => $oval,
        ];

        //shift 必须开启
        $shift = $conf["shift"] ?? (static::$stdItem["shift"] ?? []);
        $on = $shift["on"] ?? true;
        if ($on !== true) {
            //未开启 shift 则 调整 value 值的 形式
            $conf["value"] = $val;
            return $conf;
        }

        /**
         * 开始 自动 加深|减淡 颜色
         */
        $lvl = $shift["lvl"];
        $sts = $shift["steps"];
        $isDark = $conf["dark"] ?? false;
        //调用 Color 工具方法
        $nval = Color::autoShift($oval, $lvl["max"], $lvl["min"], $sts, $isDark);
        if (!Is::nemarr($nval)) {
            //处理发生错误
            return null;
        }

        //合并手动定义的部分
        $val = Arr::extend($val, $nval);
        $manual = $shift["manual"] ?? [];
        $val = Arr::extend($val, $manual);

        //得到的 新 value 替换原来的 value
        $conf["value"] = $val;
        return $conf;
    }
}