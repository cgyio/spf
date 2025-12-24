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

class Size extends Module
{
    /**
     * 定义 此主题模块的 key
     * !! 覆盖父类
     */
    public $key = "size";

    /**
     * 定义此模块 在主题文件中的 标准参数数据格式
     * !! 覆盖父类
     */
    //此模块完整的 标准参数格式
    protected static $stdDef = [
        //尺寸分组
        "groups" => [
            //作为基本尺寸的 尺寸分组
            "base" => [
                //基本尺寸 默认值，所有 mode 都应指定这些尺寸
                "fs", "fw", "mg", "pd", "rd", "icon",
                //按钮
                "btn", "btn-fs", "btn-pd", "btn-rd",
                //单行
                "bar", "bar-pd", "bar-rd",
                //多行盒容器
                "block",
                //阴影
                "shadow",
            ],
            //作为静态尺寸的 尺寸分组，这些尺寸 都不开启 shift
            "static" => [
                //边框尺寸 固定值
                "bd",
                //backdrop-filter: blur(*px)
                "blur",
                //navbar
                "navbar",
                //menubar
                "menubar-max", "menubar-min",
                //taskbar
                "taskbar", "taskitem",
            ],
            //作为当前主题 自定义尺寸的 尺寸分组
            "custom" => [],
            //可 额外定义 别的分组
            //...
        ],

        //统一定义 通用尺寸单位，可以被覆盖
        "unit" => "px",

        //通用 尺寸参数
        "common" => [
            //分别定义 尺寸分组中指定的 必须的 尺寸

            "fs" => [           //字号 8,9,10,12,14,16,18,20,24
                //字体尺寸  标准尺寸参数 数据格式
                "value" => 14,
                "shift" => [
                    //字体尺寸 自动缩放 参数不同于 默认值
                    "step" => 2,
                    //缩放级数
                    "steps" => 4,
                    //手动覆盖
                    "manual" => [
                        "xxxs"  => 8,
                        "xxs"   => 9,
                        "xxxl"  => 24,
                    ],
                ],
            ],

            "fw" => [           //字重 100,200,300,400,500,700,900
                "value" => 400,
                //字重没有单位
                "unit" => "",
                "shift" => [
                    "step" => 100,
                    "manual" => [
                        "xl"    => 700,
                        "xxl"   => 900,
                    ],
                ],
            ],

            "rd" => [           //radius 4,6,8,12,16,20,24
                "value" => 12,
                "shift" => [
                    //"step" => 4,
                    "manual" => [
                        "xxs"   => 4,
                        "xs"    => 6,
                    ]
                ],
            ],

            "mg" => [           //margin 8,12,16,20,24,32,48
                "value" => 20,
                "shift" => [
                    //"step" => 4,
                    "manual" => [
                        "xl"    => 32,
                        "xxl"   => 48,
                    ]
                ],
            ],
            "pd"    => [        //padding 4,8,12,16,20,24,32
                "value" => 16,
                "shift" => [
                    //"step" => 4,
                    "manual" => [
                        "xxl"   => 32,
                    ]
                ],
            ],
            "icon"  => [        //icon 14,16,20,24,28,32,36
                "value" => 24,
                "shift" => [
                    //"step" => 4,
                    "manual" => [
                        "xxs"   => 14,
                    ],
                ],
            ], 

            //按钮
            "btn" => [          //20,24,28,32,36,42,48
                "value" => 32,
                "shift" => [
                    //"step" => 4,
                    "manual" => [
                        "xl"    => 42,
                        "xxl"   => 48,
                    ],
                ],
            ],      
            "btn-fs" => [       //10,12,12,14,14,18,18
                "value" => 14,
                "shift" => [
                    "step" => 2,
                    "manual" => [
                        "xxs"   => 10,
                        "xs"    => 12,
                        "l"     => 14,
                        "xxl"   => 18,
                    ]
                ],
            ],      
            "btn-pd" => [      //4,6,8,12,16,20,24
                "value" => 12,
                "shift" => [
                    //"step" => 4,
                    "manual" => [
                        "xxs"   => 4,
                        "xs"    => 6,
                    ]
                ],
            ],
            "btn-rd" => [       //5,6,7,8,9,10,12
                "value" => 8,
                "shift" => [
                    "step" => 1,
                    "manual" => [
                        "xxl"   => 12,
                    ]
                ],
            ],

            //单行元素
            "bar"   => [        //单行高 28,32,36,40,48,54,64
                "value" => 40,
                "shift" => [
                    //"step" => 4,
                    "manual" => [
                        "l"     => 48,
                        "xl"    => 54,
                        "xxl"   => 64,
                    ],
                ],
            ],  
            "bar-pd" => [      //6,8,12,16,20,24,32
                "value" => 16,
                "shift" => [
                    //"step" => 4,
                    "manual" => [
                        "xxs"   => 6,
                        "xxl"   => 32,
                    ]
                ],
            ],
            "bar-rd" => [       //6,7,8,9,10,12,14
                "value" => 9,
                "shift" => [
                    "step" => 1,
                    "manual" => [
                        "xl"    => 12,
                        "xxl"   => 14,
                    ]
                ],
            ],

            //多行容器元素
            "block"   => [      //多行容器 min-height = bar + 2*bar-pd 36,44,56,68,80,96,118
                "value" => 68,
                "shift" => [
                    "step" => 12,
                    "manual" => [
                        "xxs"   => 36,
                        "xl"    => 96,
                        "xxl"   => 118,
                    ],
                ],
            ],


            "shadow" => [       //4,8,12
                "value" => 8,
                "shift" => [
                    "step" => 4,
                    "steps" => 2,
                    "manual" => [
                        "xs" => 2,
                    ]
                ],
            ],
            
            //静态尺寸
            "bd"    => 1,           //border-width
            "blur"  => 16,          //backdrop-filter: blur()
            "navbar" => 48,         // == bar-xl
            "menubar-max" => 256,
            "menubar-min" => 36,    // == bar-m
            "taskbar" => 36,        // == bar-m
            "taskitem" => 192,      //任务栏中的某个 item 宽度
        ],

        //定义所有 mode 模式下的 参数
        "modes" => [
            //browser 模式 与 common 一致
            "browser" => [],

            //mobile 模式
            "mobile" => [
                //移动端 尺寸系统 需要调整

                //移动端 可单独设置 尺寸单位 例如：小程序端，可设置 rpx
                //"unit" => "px",

                "fs" => [
                    //移动端 字号放大
                    "value" => 16,
                    //字体尺寸 自动缩放 参数
                    "shift" => [
                        //"on" => true,
                        "step" => 2,
                        //缩放级数
                        "steps" => 5,
                        //手动覆盖
                        "manual" => [
                            //步长 4
                            "l"     => 20,
                            "xl"    => 24,
                            "xxl"   => 28,
                            "xxxl"  => 32,
                            "xxxxl" => 36,
                        ],
                    ],
                ],

                "rd"    => 10,
                "mg"    => 18,
                "pd"    => 16,
                "btn"   => 36,
                "bar"   => 48,
                "icon"  => 32,
                "shadow" => [       //2,4,6,8,10,12,14
                    "value" => 8,
                    "shift" => [
                        "step" => 2
                    ],
                ],
                
                //移动端 边框设为 0.5px 如果 单位改为 rpx 则边框为 1
                "bd"    => 0.5,       //border-width
                "blur"  => 8,       //backdrop-filter: blur()
            ],
        ],

        //定义 size 模块的 额外数据定义
        "extra" => [
            /**
             * 尺寸 字符串 与 key 映射
             * !! 如果不是必须的，不要修改
             */
            "sizeStrMap" => [
                "huge" => "xxl",
                "large" => "xl",
                "medium" => "l",
                "normal" => "m",
                "small" => "s",
                "mini" => "xs",
                "tiny" => "xxs",
            ],

            //不同组件的 额外尺寸序列
            "extraSizeQueue" => [
                //fs
                "fs"    => [28,32,48,54,64,72],
                //icon
                "icon"  => [48,54,64,72,88,96,128],
                //bar
                "bar"   => [64,72,88,96],
                //button
                "btn"   => [54,64,72,88,96,128],
            ],

            /**
             * shape 序列
             * 定义主题系统中的 shape 类型
             * 对应着组件中的 shape 参数可选值
             */
            "shapes" => [
                "normal", "pill", "circle", "sharp",
            ],

            /**
             * stretch 序列
             * 定义主题系统中的 stretch 类型
             * 对应着组件中的 stretch 参数可选值
             */
            "stretches" => [
                "square", "normal", "grow", "row",
            ],

            /**
             * tightness 序列
             * 定义主题系统中的 tightness 类型
             * 对应着组件中的 tightness 参数可选值
             */
            "tightnesses" => [
                "loose", "normal", "tight",
            ],

        ],
    ];
    //此模块中，某个 参数 item 的 标准参数格式
    protected static $stdItem = [

        //... 基类中定义的 ...

        //尺寸的 单位 默认 px
        "unit" => "px",
        //尺寸在主题中自动 缩放 的参数
        "shift" => [
            //启用 自动缩放
            "on" => true,
            //自动缩放的 步长 int|float 即 每个尺寸之间的 差值
            "step" => 4,
            //自动缩放的 级数 默认 3 表示 size_[xxs|xs|s|自身|l|xl|xxl]
            "steps" => 3,
            //可 手动 覆盖 缩放后的 尺寸值 int|float
            "manual" => [
                /*
                "xl" => 24,
                */
            ],
        ],
    ];
    //此模块中，所有 参数 item 的分组类型
    protected static $stdGroups = [
        "base", "static", "custom", 
    ];
    //定义此模块支持的 mode 模式列表
    protected static $stdModes = [
        "browser", "mobile",
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
        //尺寸系统经过处理的 conf 参数
        $conf = $this->origin;

        /**
         * 生成 SCSS 序列变量定义语句
         *      $sizeShiftQueue         xxs,xs,s,m,l,xl,xxl         尺寸自动变化级数，最大级数
         *      $sizeListAll            fs,fw,mg,pd,btn,bar,...     所有定义的尺寸 item-key
         */
        // 0    生成 $sizeShiftQueue
        //获取 size 模块最终输出参数的 shift 级数
        $steps = static::autoShiftSteps($ctx);
        if ($steps>0) {
            $que = ["m"];
            for ($i=1;$i<=$steps;$i++) {
                $sk = str_pad("", $i-1, "x")."s";
                $lk = str_pad("", $i-1, "x")."l";
                array_unshift($que, $sk);
                $que[] = $lk;
            }
            $rower->rowDef("sizeShiftQueue", $que);
        }
        //空行
        $rower->rowEmpty(1);

        // 1    生成 $size-item-m|xs|xl... 变量
        $flat = Arr::flat($ctx,"-");
        foreach ($flat as $vk => $vv) {
            $rower->rowDef("size-".$vk, $vv);
        }
        //空行
        $rower->rowEmpty(1);

        // 2    生成 $size-map: ( ... );
        $rower->rowAdd("\$sizeMap: (", "");
        foreach ($flat as $vk => $vv) {
            $rower->rowDef($vk, $vv, [
                "prev" => "",
                "rn" => ",",
            ]);
        }
        $rower->rowAdd(");", "");
        //空行
        $rower->rowEmpty(1);

        // 3    生成 额外的 list | map
        $extra = $conf["extra"] ?? [];
        //生成 $size[Str|Key]Map
        if (isset($extra["sizeStrMap"]) && Is::nemarr($extra["sizeStrMap"])) {
            $vms = $extra["sizeStrMap"];
            $rower->rowDef("sizeStrQueue", array_keys($vms));
            $rower->rowAdd("\$sizeStrMap: (", "");
            foreach ($vms as $vk => $vv) {
                $rower->rowDef($vk, $vv, [
                    "prev" => "",
                    "rn" => ",",
                    "quote" => "'",
                ]);
            }
            $rower->rowAdd(");", "");
            $rower->rowAdd("\$sizeKeyMap: (", "");
            foreach ($vms as $vk => $vv) {
                $rower->rowDef($vv, $vk, [
                    "prev" => "",
                    "rn" => ",",
                    "quote" => "'",
                ]);
            }
            $rower->rowAdd(");", "");
            //空行
            $rower->rowEmpty(1);
        }
        //生成 $sizeKeyMap
        /*if (isset($extra["sizeStrMap"]) && Is::nemarr($extra["sizeStrMap"])) {
            $vms = $extra["sizeStrMap"];
            $rower->rowDef("sizeStrQueue", array_keys($vms));
            $rower->rowAdd("\$sizeStrMap: (", "");
            foreach ($vms as $vk => $vv) {
                $rower->rowDef($vk, $vv, [
                    "prev" => "",
                    "rn" => ",",
                    "quote" => "'",
                ]);
            }
            $rower->rowAdd(");", "");
            //空行
            $rower->rowEmpty(1);
        }*/
        //生成 $extraSizeQueue
        $eszQue = $extra["extraSizeQueue"] ?? [];
        if (Is::nemarr($eszQue)) {
            foreach ($eszQue as $vcom => $que) {
                $rower->rowDef("extra".(Str::camel($vcom, true))."SizeQueue", $que);
            }
            //空行
            $rower->rowEmpty(1);
        }
        //生成 shapeList 
        $sps = $conf["extra"]["shapes"] ?? [];
        $rower->rowDef("shapeList", $sps);
        //生成 stretchList 
        $sts = $conf["extra"]["stretches"] ?? [];
        $rower->rowDef("stretchList", $sts);
        //生成 tightnessList 
        $sts = $conf["extra"]["tightnesses"] ?? [];
        $rower->rowDef("tightnessList", $sts);
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
            $rower->rowDef("--size-".$vk, $vv, ["prev" => ""]);
        }
        $rower->rowAdd("}", "");
        //空行
        $rower->rowEmpty(1);

        return $rower;
    }



    /**
     * 获取某个 mode 模式下 所有 items 的 value
     * !! 覆盖父类
     * @param Array $items 要获取的 mode 模式下的 所有 items 参数
     * @return Array 返回获取到的 此 参数 item 的 value，通常是 item["value"] 的 值 
     */
    protected function getItemsValue($items)
    {
        /**
         * 通常情况下，直接返回此 mode 下所有 items 的 value 值
         * !! 如果有不同获取方法，应在子类中覆盖此方法
         * !! Size 尺寸系统，获取 value 需要附加 unit 单位
         */
        
        $rtn = [];
        foreach ($items as $item => $itemc) {
            //处理 别名 item
            $alias = $itemc["alias"] ?? null;
            if (Is::nemstr($alias)) {
                $rtn[$item] = $alias;
                continue;
            }

            //!! 尺寸单位
            $unit = $itemc["unit"] ?? static::$stdItem["unit"];
            //value 
            $val = $itemc["value"] ?? null;
            //为 尺寸数字 附加 单位
            if (Is::nemarr($val)) {
                foreach ($val as $k => $v) {
                    if (is_numeric($v)) {
                        $val[$k] = $v.$unit;
                    }
                }
            }
            //获取
            $rtn[$item] = $val;
        }

        return $rtn;        
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
        //合法的 尺寸参数值，只能是 数值
        if (!is_numeric($val)) return false;
        return $val * 1;
    }



    /**
     * auto-shift 静态方法
     */

    /**
     * 处理 某个 参数 item 的 auto shift
     * !! 需要 auto shift 的 主题模块，必须实现此方法
     * 自动 增大|缩小 尺寸
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

        //开始自动 增大|缩小 尺寸
        $step = $shift["step"];     //变化步长，即 每级 增减的 尺寸数字
        $steps = $shift["steps"];   //变化级数
        for ($i=1; $i<=$steps; $i++) {
            $up = $oval + $i*$step;
            $dn = $oval - $i*$step;
            //键名前缀 xx..
            $k = str_pad("", $i-1, "x");
            //存入 val 数组
            $val[$k."l"] = $up;
            $val[$k."s"] = $dn<=0 ? 0 : $dn;
        }

        //合并手动定义的部分
        $manual = $shift["manual"] ?? [];
        $val = Arr::extend($val, $manual);

        //得到的 新 value 替换原来的 value
        $conf["value"] = $val;
        return $conf;
    }
}