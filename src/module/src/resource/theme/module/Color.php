<?php
/**
 * SPF-Theme 主题模块 
 * 主题 颜色系统 模块
 */

namespace Spf\module\src\resource\theme\module;

use Spf\module\src\Resource;
use Spf\module\src\SrcException;
use Spf\module\src\resource\theme\Module;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Color as ColorUtil;

class Color extends Module
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
    protected static $stdDef = [
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
                "info",     //gray
                //业务主题色
                "bz",
                //还可以定义 其他基本色
                //...
            ],
            //有特定用途的颜色
            "specific" => [
                "fc", "bgc", "bdc", 
                //还可以定义 其他特定色
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
                "max" => 72, 
                "min" => 48 
            ],
            //加深|减淡 的级数
            "steps" => 3,
        ],

        //alpha 透明度级数 9 表示透明度 a1~a9 = 10%~90% 所有颜色增加 9 级透明度
        

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
            "fc"        => "#666666",       //字体颜色
            //背景色
            "bgc" => [
                "value" => "#f3f3f3",
                "shift" => [
                    //加深|减淡 l 极限值 0~1 之间 [ 最大(亮|淡), 最小(暗|深) ]
                    "lvl" => [ 
                        "max" => 100, 
                        "min" => 60 
                    ],
                ],
            ],
            //边框色
            "bdc" => [
                "value" => "#dfdfdf",
                "shift" => [
                    //加深|减淡 l 极限值 0~1 之间 [ 最大(亮|淡), 最小(暗|深) ]
                    "lvl" => [ 
                        "max" => 64, 
                        "min" => 54
                    ],
                ],
            ],
            //静态颜色
            "white"     => "#ffffff",
            "black"     => "#000000",
            "shadow"    => "#000000",   //阴影色 透明度由 scss 自动生成

            //别名颜色，可直接指定 要指向的 颜色 key
            "danger"    => "red",
            "warn"      => "yellow",
            "success"   => "green",
            "primary"   => "blue",
            "info"      => "gray",
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
                        "max" => 64,    //80, 
                        "min" => 24,    //4 
                    ],
                ],

                //如果有不同于 common 设置的，在此指定
                //dark 模式下 前景色|背景色|边框色 反转
                "fc"        => "#aaaaaa",
                //背景色
                "bgc" => [
                    "value" => "#202020",
                    "shift" => [
                        //加深|减淡 l 极限值 0~1 之间 [ 最大(亮|淡), 最小(暗|深) ]
                        "lvl" => [ 
                            "max" => 36, 
                            "min" => 0,
                        ],
                    ],
                ],
                //边框色
                "bdc" => [
                    "value" => "#333333",
                    "shift" => [
                        //加深|减淡 l 极限值 0~1 之间 [ 最大(亮|淡), 最小(暗|深) ]
                        "lvl" => [ 
                            "max" => 36, 
                            "min" => 24, 
                        ],
                    ],
                ],
                //dark 模式下 阴影为 白色
                //"shadow"    => "#000000",
                //dark 模式下，黑白色 互换
                "white"     => "#000000",
                "black"     => "#ffffff",
            ],
        ],

        //定义 color 模块的 额外数据定义
        "extra" => [
            /**
             * !! 由 scss 自动生成，此处不再需要
             * alpha 透明度级数
             * 默认 9（最大） 表示透明度 a1~a9 = 10%~90% 所有颜色增加 9 级透明度
             * 0 表示 不启用颜色透明度
             * !! 不要使用其他 透明度级数，目前仅支持 0|9
             */
            //"alpha" => 9,

            /**
             * type 序列
             * 定义主题系统中的 type 类型
             * 应定义对应的 别名颜色
             * 对应着组件中的 type 参数可选值
             */
            "types" => [
                "primary", "danger", "warn", "success", "info",
            ],

            /**
             * effect 序列
             * 定义主题系统中的 effect 类型
             * 对应着组件中的 effect 参数可选值
             */
            "effects" => [
                "normal", "fill", "plain", "popout",
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
        "base", "specific", "static", "custom", 
    ];
    //定义此模块支持的 mode 模式列表
    protected static $stdModes = [
        "light", "dark",
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
        //颜色系统经过处理的 conf 参数
        $conf = $this->origin;

        /**
         * 生成 SCSS 序列变量定义语句
         *      $colorShiftQueue        d3,d2,d1,m,l1,l2,l3         颜色自动变化级数，最大级数，有些颜色可能不会 shift 这些级数
         *      $colorListFooBar        red,blue,danger,...         某个颜色组 group 中包含的所有颜色 item-key
         *      $colorListAll           red,blue,yellow,danger,...  所有颜色
         */
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
            //调用 $rower->rowDef() 方法 生成 变量 定义语句
            $rower->rowDef($vk, $vv);
        }

        // 1    生成 $colorListAll 变量
        if (Is::nemarr($allitems)) $rower->rowDef("colorListAll", $allitems);

        // 2    生成 $colorShiftQueue
        //获取 color 模块最终输出参数的 shift 级数
        $steps = static::autoShiftSteps($ctx);
        if ($steps>0) {
            $que = ["m"];
            for ($i=1;$i<=$steps;$i++) {
                array_unshift($que, "l".$i);
                $que[] = "d".$i;
            }
            $rower->rowDef("colorShiftQueue", $que);
        }
        //空行
        $rower->rowEmpty(1);

        // 3    生成 $colorListAlias  $colorAliasMap
        $alias = $conf["extra"]["alias"] ?? [];
        $alks = array_keys($alias);
        //定义 $colorListAlias
        $rower->rowDef("colorListAlias", $alks);
        //定义 $colorAliasMap
        $rower->rowAdd("\$colorAliasMap: (", "");
        foreach ($alias as $ak => $av) {
            $rower->rowDef($ak, $av, [
                "prev" => "",
                "rn" => ",",
                "quote" => "'",
            ]);
        }
        $rower->rowAdd(");", "");
        //空行
        $rower->rowEmpty(1);


        // 3    生成透明度级数 $colorAlphaMap
        //!! scss 自动处理，此处不需要
        /*$alvls = $conf["extra"]["alpha"] ?? 9;   //默认启用
        if (is_int($alvls) && $alvls===9) {
            //!! 目前仅支持：透明度级数 只能是 0 或 9
            $als = [];
            $rower->rowAdd("\$colorAlphaMap: (", "");
            for ($i=1;$i<=$alvls;$i++) {
                $alk = "a".$i;
                $alv = round($i*0.1*255);
                $alv = dechex((int)$alv);
                if (strlen($alv)<2) $alv = "0".$alv;
                //定义
                $rower->rowAdd("$alk: $alv,","");
                //添加到 colorListAlpha
                $als[] = $alk;
            }
            $rower->rowAdd(");", "");
            //定义 $colorListAlpha
            $rower->rowDef("colorListAlpha", $als);
            //空行
            $rower->rowEmpty(1);
        }*/

        // 4    生成 extra 数据中的 typeList|effectList
        $tps = $conf["extra"]["types"] ?? [];
        $rower->rowDef("typeList", $tps);
        $efs = $conf["extra"]["effects"] ?? [];
        $rower->rowDef("effectList", $efs);
        $rower->rowEmpty(1);

        // 5    生成 $color-item-m 变量
        $flat = Arr::flat($ctx, "-");
        foreach ($flat as $vk => $vv) {
            $rower->rowDef("color-".$vk, $vv);
        }
        //空行
        $rower->rowEmpty(1);

        // 6    生成 $color-map: ( ... );
        $rower->rowAdd("\$colorMap: (", "");
        foreach ($flat as $vk => $vv) {
            $rower->rowDef($vk, $vv, [
                "prev" => "",
                "rn" => ",",
            ]);
        }
        $rower->rowAdd(");", "");
        //空行
        $rower->rowEmpty(1);

        //SCSS 语句 需要包含 css 变量定义语句
        return $this->createCssContentRows($ctx, $rower);
    }
    //createCssContentRows
    protected function createCssContentRows($ctx=[], &$rower)
    {
        /**
         * 定义 css 颜色变量语句
         */
        $rower->rowAdd(":root {", "");
        $flat = Arr::flat($ctx, "-");
        foreach ($flat as $vk => $vv) {
            $rower->rowDef("--color-".$vk, $vv, ["prev" => ""]);
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
        //合法的 颜色参数值，只能是 合法的 颜色字符串  #ffffff | rgba() | hsl() ...
        if (false === ColorUtil::isColorString($val)) return false;
        return $val;
    }



    /**
     * auto-shift 静态方法
     */

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
        //$nval = ColorUtil::autoShift($oval, $lvl["max"], $lvl["min"], $sts, $isDark);
        //!! 使用 luma 亮度作为颜色加深减淡的判断方式
        //$nval = ColorUtil::autoShiftWithLuma($oval, $lvl["max"], $lvl["min"], $sts, $isDark);
        //!! 使用 psAlpha 算法 通过叠加纯黑|白透明图层的方式，加深|减淡 颜色
        $nval = ColorUtil::autoShiftWithPsAlpha($oval, $lvl["max"], $lvl["min"], $sts, $isDark);
        //var_dump($item);var_dump($oval);
        //var_dump($nval);
        if (!Is::nemarr($nval)) {
            //处理发生错误
            return null;
        }

        //合并手动定义的部分
        $val = Arr::extend($val, $nval);
        $manual = $shift["manual"] ?? [];
        $val = Arr::extend($val, $manual);

        //为每个 颜色 创建前景色
        //!! 由 scss 自动处理，此处不再需要
        /*$fval = [];
        foreach ($val as $k => $c) {
            $fc = ColorUtil::autoFrontColor($c);
            $fval["$k-fc"] = $fc;
        }
        $val = Arr::extend($val, $fval);*/

        //得到的 新 value 替换原来的 value
        $conf["value"] = $val;
        return $conf;
    }
}