/**
 * SPF-Vcom 组件库，组件样式自动计算系统 mixin
 * 在任何 需要设置 class|style|size|type|color... 的组件中，需要引用此 mixin
 */

export default {
    props: {

        /**
         * 为组件提供自定义 class|style
         * !! 在 props 中定义 class|style 会报错
         * !! [Vue warn]: "class" is a reserved attribute and cannot be used as component prop
         * !! 因此定义为 customClass|customStyle
         */
        //组件根元素上附加 custom class 样式
        customClass: {
            type: [String, Array],
            default: ''
        },
        //组件根元素上附件的 cuatom style 
        customStyle: {
            type: [String, Object],
            default: ''
        },
        

        /**
         * size 尺寸样式
         * 可选:     mini | small | normal(默认) | medium | large
         *          xs | s | m | l | xl
         *          btn.s | bar.xl ... 在 cssvar 中有定义的 参数键
         *          25px | 50% | 100vw | 75vh ...
         *          72 ...
         */
        size: {
            type: [Number, String],
            default: 'normal'
        },


        /**
         * color 颜色系统
         */

        /**
         * 通过指定 type 来指定组件的 颜色
         * 可选：   primary|danger|warn|success|info    在 $ui.cssvar.extra.color.types 中定义了
         * !! 如果传入了 color 参数，type 参数将被覆盖
         */
        type: {
            type: String,
            default: ''
        },
        /**
         * 可传入 color 参数，来设置更为具体的 颜色，将覆盖 type 参数
         * 可选：   $ui.cssvar.color 中定义的所有 键
         *      red | red-l3 | fc[-m] | white ...
         *      也可以这样写：red.m | fc.d3 ...
         * 还可以输入 任意 css 颜色字符串：!!不推荐
         *      rgb() rgba() #fff ...
         */
        color: {
            type: String,
            default: ''
        },


        /**
         * 其他样式 开关
         * 可在组件内自行增加 其他样式开关，将自动在根元素 class[] 中增加相应的 功能样式类
         * !! 自行增加的 其他样式开关，需要在 sty.switch 中增加相应的启用标记
         * 例如：增加 foo 样式开关，则需要增加 sty.switch.foo = true
         * 根据 props.foo 的类型，有下列效果：
         *      Boolean 类型：  props.foo === true      在根元素 class[] 中增加 pre-foo 类
         *      String 类型：   props.foo === 'bar'     在根元素 class[] 中增加 pre-foo 和 pre-foo-bar 类
         * !! 在 sty.switch 中可使用字符串模板形式 定义监听方式：（只能监听 string 类型的 prop）
         * !! 例如 有定义 sty.switch.effect = '.{swv@pre}-effect effect-{swv}' 则有下列映射：
         *      props.effect === 'popout'               在根元素 class[] 中增加 pre-effect 和 effect-popout 类
         */
        /**
         * 示例开关：effect 背景|前景|边框 复合样式
         * 可选 normal|fill|plain|popout
         * 在 $ui.cssvar.extra.size.effectList 中定义
         * !! 默认关闭监听，组件内部可通过 sty.switch.effect = '.{swv@pre}-effect effect-{swv}' 启动监听此 prop
         */
        effect: {
            type: String,
            default: 'normal'
        },
        /**
         * effect 样式相关的 status 状态
         * !! 默认关闭监听，组件内部可通过 sty.switch.[hoverable|active] = true 启动监听此 prop
         */
        hoverable: {
            type: Boolean,
            default: false
        },
        active: {
            type: Boolean,
            default: false
        },
        /**
         * 示例开关：stretch 横向拉伸
         * 可选 auto|grow|row 
         * 在 $ui.cssvar.extra.size.stretchList 中定义
         * !! 默认关闭监听，组件内部可通过 sty.switch.stretch = '.{swv@pre}-stretch stretch-{swv}' 启动监听此 prop
         */
        stretch: {
            type: String,
            default: 'auto'
        },
        /**
         * 示例开关：tightness 内容排布松紧
         * 可选 normal|loose|tight 
         * 在 $ui.cssvar.extra.size.tightnessList 中定义
         * !! 默认关闭监听，组件内部可通过 sty.switch.tightness = '.{swv@pre}-tightness tightness-{swv}' 启动监听此 prop
         */
        tightness: {
            type: String,
            default: 'normal'
        },
        /**
         * 示例开关：shape 形状
         * 可选 sharp|round|pill|square|round-square|circle 
         * 在 $ui.cssvar.extra.size.shapeList 中定义
         * !! 默认关闭监听，组件内部可通过 sty.switch.shape = '.{swv@pre}-shape shape-{swv}' 启动监听此 prop
         */
        shape: {
            type: String,
            default: 'sharp'
        },

        /*
        其他 样式开关 自行在组件中增加
        ...
        */


        /**
         * animate 动画效果
         * 基于 animate.css
         */
        //动画类型 animate__*** 类名
        animateType: {
            type: String,
            default: ''
        },
        //完整指定 animate 类名序列，需要写 animate__ 前缀，会覆盖 animateType 参数
        animateClass: {
            type: [String, Array],
            default: ''
        },
        //是否循环播放
        animateInfinite: {
            type: Boolean,
            default: false
        },


        /**
         * 组件的 影响到 size|color|animate 等样式的 特殊状态
         */
        //失效
        disabled: {
            type: Boolean,
            default: false
        },


        /**
         * 快速应用原子样式  等同于在 custom-class 中指定原子样式
         * 可用的原子样式包括：
         *    .fc-*|.fs-*|.fw-*|.fml-*
         *    .pd-*|.mg-*|.pd-po-*|.mg-po-*
         *    .bgc-*|.bgc-a*
         *    .bd-*|.bd-po-*|.bdc-*|.rd-*|.rd-po-*
         *    .opa-*|.shadow-*
         *    .flex-x-*|.flex-y-*
         * !! 需配合内部 sty.switch 中指定监听这些 props
         * !! 可在组件内部 sty.switch 中指定 false 来关闭监听
         * 
         * 默认空值，表示不额外指定，使用当前组件样式自定义的
         * !! 如果当前组件的自有样式中 覆盖了这些原子样式的属性值，则原子样式不生效
         * 例如：btn 组件会根据 btn-size 参数自动计算并应用 font-size 则 指定 fs-xl 原子样式也不会生效
         */
        //.fc-* 可选：red|red-m|danger|danger-l2...
        fc: {
            type: String,
            default: ''
        },
        //.fs-* 可选：large|xl ...
        fs: {
            type: String,
            default: ''
        },
        //.fw-* 可选：100~900|bold|thin|normal
        fw: {
            type: String,
            default: ''
        },
        //.fml-* 可选：default|code...
        fml: {
            type: String,
            default: ''
        },
        //.pd-* 可选：large|xl...
        pd: {
            type: String,
            default: ''
        },
        //.pd-po-* 可选：trblQueue
        pdPo: {
            type: String,
            default: ''
        },
        //.mg-* 可选：large|xl...
        mg: {
            type: String,
            default: ''
        },
        //.mg-po-* 可选：trblQueue
        mgPo: {
            type: String,
            default: ''
        },
        //.bgc-* 可选：red|danger|red-m|danger-l2...
        bgc: {
            type: String,
            default: ''
        },
        //.bgc-a* 可选 1~9
        bgcA: {
            type: String,
            default: ''
        },
        //.bd-* 可选：m
        bd: {
            type: String,
            default: ''
        },
        //.bd-po-* 可选：trblQueue
        bdPo: {
            type: String,
            default: ''
        },
        //.bdc-* 可选：red|danger|red-m|danger-l2...
        bdc: {
            type: String,
            default: ''
        },
        //.rd-* 可选：large|xl...
        rd: {
            type: String,
            default: ''
        },
        //.rd-po-* 可选：cornerQueue
        rdPo: {
            type: String,
            default: ''
        },
        //.opa-* 可选：10|20|30~100 
        opa: {
            type: String,
            default: ''
        },
        //.shadow-* 可选：[xxl~m~xxs]-[a1~a4]
        shadow: {
            type: String,
            default: ''
        },
        //.flex-x-* 可选：start|end|center|stretch
        flexX: {
            type: String,
            default: ''
        },
        //.flex-y-* 可选：start|end|center|stretch
        flexY: {
            type: String,
            default: ''
        },
        

    },

    data() {return {

        /**
         * base-style 样式系统参数
         */
        sty: {
            /**
             * 定义此组件的 根元素以及内部任意元素 条件样式的计算方法
             * !! 此处应关联到 methods 中的方法名，或直接定义 function 
             * !! 所有需要自动计算 class[]|css{} 的 组件根元素|后代元素 至少需要定义 calculator 或 init 参数中的一个
             */
            calculator: {
                /**
                 * 样式类 [] 计算方法
                 * 这些方法，应：
                 *      绑定 this 到 当前组件实例
                 *      返回 class [] 包含所有计算后需要添加到对应元素的 classList 中的样式类
                 */
                class: {
                    //root 是计算根元素的 class 样式类，必须指定
                    root: 'styCalcRootClass',   //!! 也可以定义为 '' 空字符串，与 'styCalcRootClass' 相同
                    //组件内部可定义其他元素的 class []
                    //...
                },
                
                /**
                 * 样式 {} 计算方法
                 * 这些方法，应：
                 *      绑定 this 到 当前组件实例
                 *      返回 css 样式 {} 包含任意计算好值的 css 属性
                 */
                style: {
                    //root 是计算根元素的 css 样式，必须指定
                    root: 'styCalcRootStyle',   //!! 也可以定义为 '' 空字符串，与 'styCalcRootStyle' 相同
                    //组件内部可定义其他元素的 css {}
                    //...
                }
            },

            /**
             * 组件 根元素|后代元素 的初始 class[]|style{}
             * !! 所有需要自动计算 class[]|css{} 的 组件根元素|后代元素 都需要在此定义对应的 []|{}
             * !! 不建议把初始 class[]|style{} 写到 template 中
             * !! class|style 也支持 字符串写法 '__PRE__-foo flex-x flex-y-center'|'width:100px; height: auto;'
             * !! 所有需要自动计算 class[]|css{} 的 组件根元素|后代元素 至少需要定义 calculator 或 init 参数中的一个
             */
            init: {
                class: {
                    //根元素
                    root: [],
                },
                style: {
                    //根元素
                    root: {},
                },
            },

            /**
             * 当前组件的 css 功能样式类 统一前缀
             * 例如：icon 组件的所有功能样式类统一前缀为：icon
             * 则有下列功能样式类：icon-primary icon-medium icon-red-hover ...
             * !! 组件内部必须覆盖
             */
            prefix: '',

            /**
             * 定义 base-style 样式系统的 样式组开关
             * 这些开关的 true|false 状态将影响 sub 子系统 以及 switch 其他样式开关的 生效与否和计算方法
             * 这些开关必须定义在 props|data|data.*.* 中  数据类型必须是 Boolean 
             * 常用的 样式组开关 例如：
             *      disabled        生效|失效   常用于 button 组件
             *      show            显示|隐藏   常用于 win 组件  dialog 组件
             * 组开关定义参数
             *      默认格式：  {
             *                      # 组开关键名
             *                      key: '默认为外部键名',
             *                      # 是否启用
             *                      enable: true|false,
             *                      # 监听的数据源
             *                      watch: 'foo' | 'foo.bar.jaz',
             *                      # 此组开关开启时，在 root 元素挂载的样式类名（自动拼接 prefix 类名前缀）
             *                      class: '默认为开关键名，可自定义样式类名，连接符 -',
             *                  }
             *      简易参数格式：
             *          foo: true                   --> { key:'foo', enable:true, watch:'foo', class:'foo' }
             *          'foo.bar.jaz': true         --> { key:'jaz', enable:true, watch:'foo.bar.jaz', class:'foo-bar-jaz' }
             *          'foo.bar.jaz:bar': true     --> { key:'bar', enable:true, watch:'foo.bar.jaz', class:'foo-bar-jaz' }
             *          'foo.bar.jaz': 'foo'        --> { key:'foo', enable:true, watch:'foo.bar.jaz', class:'foo' }
             *          'foo.bar.jaz:bar': '.foo'    --> { key:'bar', enable:true, watch:'foo.bar.jaz', class:'.foo' }
             * !! key|class 都是 kabab-case 因此：
             *          fooBar: true                --> { key:'foo-bar', enable:true, watch:'fooBar', class:'foo-bar' }
             */
            group: {
                //生效|失效 组开关
                disabled: {
                    key: 'disabled',
                    //!! 默认启用 disabled 组开关
                    enable: true,
                    //监听数据源 默认定义在 props
                    watch: 'disabled',
                    //==true 时，在 root 元素挂载的样式类名（自动拼接 prefix 类名前缀）
                    /**
                     * ==true 时，在 root 元素挂载的样式类名
                     *  foo     --> .pre-foo （自动拼接 prefix 类名前缀）
                     *  .foo    --> .foo  以 . 开头则不拼接 prefix 类名前缀，原样添加到 class[] 类名数组
                     */
                    class: '.disabled',
                },
                //上述组开关参数，可简写为 disabled: true
            },

            /**
             * 定义的 base-style 样式系统的子系统
             * !! size|color|animate|switch 必须存在
             * !! 引用的组件可以自行扩展
             * 所有定义的 子系统 必须同时定义对应的 class|style 计算方法，例如：
             *      styCalcRootSizeClass  styCalcFooColorStyle
             * 如果此子系统受 group 样式组开关状态影响方式为 'both' 则需要定义两个计算方法：
             *      styCalcRootSwitchClass[Groupkey]False 和 styCalcRootSwitchClass[Groupkey]True
             * 
             * !! 所有方法名都可以自定义 { ... calculator: { class: 'fooBarClass', style: 'fooBarStyle' }, ... }
             * !! size|color|animate 子系统默认不启用，switch 子系统默认启用，有需要的组件应在内部覆盖
             */
            sub: {
                //定义一个子系统，指定其 启用状态、受 disabled 的影响方式，以及 自定义计算方法
                size: {
                    //启用|停用 
                    enable: false,
                    /**
                     * 影响此子系统的 group 样式组开关
                     *      false   --> 不受任何组开关影响，== 所有组开影响方式为 'none'
                     *      true    --> 接受所有组开关影响，组开关影响方式都为 'both'
                     *      'on'    --> 接受所有组开关影响，组开关影响方式都为 true
                     *      'off'   --> 接受所有组开关影响，组开关影响方式都为 false
                     *      可单独指定某个组开关影响方式
                     *      !! 其他未指定的组开关为 'none'
                     *      {
                     *          [group key]: 'none',    --> 无论此组开关 true|false 都不执行此子系统方法
                     *          [group key]: 'both',    --> 此组开关 true|false 时有不同的计算方法
                     *          [group key]: true       --> 仅在此组开关 ==true 时，执行此子系统计算
                     *          [group key]: false      --> 仅在此组开关 ==false 时，执行此子系统计算
                     *      }
                     */
                    group: false,
                    /**
                     * 此子系统是否不受任何组开关影响
                     * !! 在格式化子系统参数时自动生成，不要手动指定
                     * !! 只有此参数 == true 时，才会在每次执行 styCalc 时，执行此子系统方法
                     * !! 否则，只有在此子系统 group 参数定义的某个组开关 开启|关闭 时才会执行此子系统的计算方法
                     */
                    noGroup: true,
                    /**
                     * 可自定义此子系统对应的计算方法，可定义 方法名  或者  直接定义 function 函数
                     * 方法名中可使用 {elm}|{Elm} 作为 this.sty.calculator 中定义元素的通配符，例如：
                     *      calcSwitch{Elm}Class
                     * 
                     * 根据 group 组开关影响方式不同，可定义对应的计算方法：
                     * 如果定义了 group.foo = 'both' 则需要在此处定义：
                     *      calculator: {
                     *          foo: {
                     *              class: {
                     *                  on: 'styCalc{Elm}[Sub]ClassFooTrue',
                     *                  off: 'styCalc{Elm}[Sub]ClassFooFalse',
                     *              },
                     *              style: {
                     *                  on: 'styCalc{Elm}[Sub]StyleFooTrue',
                     *                  off: 'styCalc{Elm}[Sub]StyleFooFalse',
                     *              }
                     *          }
                     *      }
                     * !! 只有影响方式定义为 both 的 group 组开关，才需要单独定义计算方法，其他不需要定义
                     * !! 如果使用默认方法名，不需要在此处定义方法名
                     */
                    calculator: {
                        class: 'styCalc{Elm}SizeClass',
                        style: 'styCalc{Elm}SizeStyle',
                    }
                },
                /**
                 * 可以通过指定 Boolean|String 快速指定子系统参数
                 * !! 此方式指定子系统参数，无法自定义计算方法，必须使用默认计算方法名
                 * !! 用于在引用组件内部快速覆盖子系统参数
                 * 指定含义：
                 *      false       --> { enabled: false }
                 *      true        --> { enable: true, group: false }
                 *      'both'      --> { enable: true, group: true }
                 *      'on'        --> { enable: true, group: 'on' }
                 *      'off'       --> { enable: true, group: 'off' }
                 *      'disabled'  --> { enable: true, group: {disabled: true} }
                 *      'disabled:false|both'       --> { enable: true, group: {disabled: false|both} }
                 *      'foo,bar:both,jaz:false'    --> {
                 *          enable: true,
                 *          group: {
                 *              foo: true,
                 *              bar: 'both',
                 *              jaz: false
                 *          }
                 *      }
                 */
                color: false,
                //指定一个 {} 必须至少包含 enable 键
                switch: {
                    //!! 默认 启用 switch 子系统
                    enable: true,
                    //组开关影响此子系统
                    //!! 默认接受所有 sty.group 中定义的组开关的影响，影响方式为 both
                    group: true,
                    //此键是可选的，不指定则使用默认方法名
                    calculator: {
                        //!! 因为 默认接受所有组开关的影响，影响方式为 both 此处需要为所有组开关定义 on|off 方法
                        //!! 仅定义一个 group.disabled 作为示例，其他未定义的组开关，将会使用默认方法名
                        disabled: {
                            class: {
                                //sty.group.disabled 监听的数据 == true 时的计算方法
                                on: 'styCalc{Elm}SwitchClassDisabledTrue',
                                //sty.group.disabled 监听的数据 == false 时的计算方法
                                off: 'styCalc{Elm}SwitchClassDisabledFalse',
                            },
                            style: {
                                on: 'styCalc{Elm}SwitchStyleDisabledTrue',
                                off: 'styCalc{Elm}SwitchStyleDisabledFalse',
                            },
                        },
                        //其他 sty.group 中定义的组开关，可以在此处定义方法名，不定义则使用默认的方法名
                    },
                },
                animate: {
                    //启用|停用 此子系统
                    enable: false,
                    //组开关影响此子系统
                    group: {
                        //!! 默认接受 group.disabled 组开关影响，影响方式为 false
                        disabled: false,
                    },
                    //不定义 calculator 使用默认方法名
                },
            },

            /**
             * switch 子系统
             * 启用 其他样式 开关
             * !! 要在组件内部 props|data 中自行增加 样式开关定义，可以是 Boolean|String 类型
             * 启用后 根据 props.foo 传入的值 或 data.foo 保存的值，自动在 根元素 class[] 中增加对应的 样式类
             *      根据开关定义的类型，有不同的 样式类添加逻辑：
             *      Boolean 类型：  props.foo|data.foo === true     则在 根元素 class[] 中增加 pre-foo 类
             *      String 类型：   props.foo|data.foo === 'bar'    则在 根元素 class[] 中增加 pre-foo 和 pre-foo-bar 类
             * 
             * !! 也可以通过 监听 data 中的某个键的值(必须是 Boolean|String) 来增加对应的 样式类
             * 例如：组件 data 中定义了 data.foo.barJaz 数据类型 Boolean，在此处添加：sty.switch['foo.barJaz:disabled'] = true 则：
             *      当 props.disabled === false && data.foo.barJaz === true 时 在根元素 class[] 中增加 pre-foo-bar-jaz 类
             *      如果 data.foo.barJaz 是 String 类型，则：
             *          当 data.foo.barJaz === 'tomJak' 时 在根元素 class[] 中增加 pre-foo-bar-jaz 和 pre-foo-bar-jaz-tom-jak 类
             * 
             * !! switch 默认可以接收所有 group 组开关的影响，以 sty.group 中定义的 disabled 组开关为例：
             * !!   如果定义的 switch 名称以 :!disabled 结尾，则此样式类只会在 sty.group.disabled 监听的数据 !== true 时添加
             * !!   如果 switch 名称不含 :[!][group-key] 结尾时，则不论何种情况，都会添加对应的样式类
             * 例如：button 组件中增加 'hoverShrink:!disabled' = true 则：
             *      disabled === false && props.hoverShrink === true        --> class[] 增加 pre-hover-shrink
             *      disabled === true && props.hoverShrink === true         --> class[] 中不含 pre-hover-shrink
             * 
             * !! switch 可设置为只针对某一个或多个 sty.init.class|style 中定义的元素，只在这些元素的样式计算中起效
             * !!   快捷定义方法：foo.bar[:!disabled]@root@elmA@... 
             * !!   如果不定义 @foo 则表示此 switch 仅对 root 元素生效
             * !!   如果定义了 @foo 则表示此 switch 仅在计算 foo 元素的样式时，起效
             * !!   如果需要此 switch 在对 root 和 foo 元素计算样式时都生效，必须定义为 @root@foo
             * 例如： 定义 switch 为 foo.bar = true 则：
             *      当 [props|data].foo.bar === true 时 在 root 元素的 class[] 中增加样式类 pre-foo-bar
             *      !! 相当于 foo.bar@root = true
             * 如果定义： foo.bar@elmA = true 则：
             *      当 [props|data].foo.bar === true 时 在 elmA 元素的 class[] 中增加样式类 pre-foo-bar
             * 如果定义： foo.bar@root@elmA = true 则：
             *      当 [props|data].foo.bar === true 时 在 root 和 elmA 元素的 class[] 中都会增加样式类 pre-foo-bar
             * 
             * !! 如果定义的 switch 值不是 true 而是 string 类型，则在增加样式类时，使用此出定义的 string 作为类名
             * 例如：定义 sty.switch.fooBar = 'jaz-tom' 当 props.fooBar === true 时，自动在 根元素 class[] 中增加样式类：
             *          pre-foo-bar 和 pre-jaz-tom
             * 
             * !! 定义的 switch 值还可以是：class[]|class-string|style{}|style-string
             *      foo:                '.flex-x fs-large bgc-red-m rd-small'   # class 字符串以 . 开头 可直接使用原子样式类
             *      'foo:!disabled':    ['foo-bar', 'jaz-tom']                  # class[] 数组
             *      'foo.bar:show':     'width:100px; height:auto;'             # style 字符串
             *      'foo.bar':          {zIndex: 1, width: '100px'}             # style{}
             *      class 类名会自动解析为 []，style 样式自动解析为 {}
             * !! 在定义 class-string|style-string|style{} 时还可以使用 模板字符： 
             *      foo:        '.flex-x pre-{swv}',                                # {swv} 代替 string 类型的 switch 监听数据值
             *      bar:        'font-size: {swv@size-key@csv-val,size.btn.*}',     # {swv@size-key@csv-val,size.btn.*} 执行模板替换方法，swv==large 则会返回 this.$cssvar.size.btn.xl
             *      stretch:    '.{swv@pre}-stretch stretch-{swv}',                 # {swv@pre} 代替 sty.prefix 中指定的 类名前缀
             * 
             * !! 如果 定义的 switch 项目以 ! 开头，表示只有此 switch 监听值必须是 boolean 且 == false 时，才会执行后续的 添加 class[]|style{}
             *      '!foo:!disabled': '.atom-class'         # 表示 foo == false && disabled == true 时，才会向 class[] 中添加原子类 .atom-class
             * 
             * !! 如果 定义了 switch == 某个值，表示只有 switch 监听值 == 指定的值时，才会执行后续的 添加 class[]|style{}
             *      'foo=bar:!disabled': '.atom-class'      # 表示 只有 foo == 'bar' 时，才会向 class[] 中添加原子类 .atom-class
             *      'foo=false:!disabled': '.atom-class'    # 相当于 !foo:!disabled  监听值必须是 boolean
             *      'foo=*:!disabled': '.atom-class'        # 相当于 foo:!disabled  监听值必须是 string|boolean
             * 
             * !! 可以在 switch 定义键名后加上 #1|#2... 表示在相同的 监听条件下，执行多个 修改 class[]|style{} 操作
             *      '!foo.bar:!disabled@elm #1': '.atom-class'              # foo.bar==false && disabled==false 时 添加原子类 .atom-class
             *      '!foo.bar:!disabled@elm #2': 'padding-left: 12px;'      # 相同条件下，增加 行内 style 
             * 
             * !! 可以直接定义标准 switch 其他样式开关参数格式：
             *  {
             *      enable: true|false,
             *      //key: 'foo-bar',             # 外部访问的键名  foo-bar 形式
             *      group: 'disabled',          # :之后的部分，表示此 switch 受哪个组开关影响
             * !!   groupOn: false,             # foo:!disabled 表示此 switch 在 disabled 组开关 == false 时插入样式类
             * !!   for: ['root','elmA',...],   # 此 switch 可只针对某个元素，默认只针对 root，可有多个针对的元素
             *      watch: 'foo.bar.jazTom',    # 监听数据源，定义在 props|data|data.*.*...
             *      until: '*'|true,            # 监听值 == 此值时，才会执行后续  默认 监听 string 类型时 == '*'(任意非空 string)，监听 boolean 时 == true
             *      class: 'foo-bar-jaz-tom',   # 要添加的样式类名，不带前缀，可通过 定义 switch 值来覆盖
             *              [...],              # 数组形式 class 直接合并到元素的 class[] 中
             *      style: {...},               # 样式{} 直接合并到元素的 style{} 中
             *  }
             */
            switch: {
                //!! 默认关闭，可在组件内部开启
                stretch:    false,  //'.{swv@pre}-stretch stretch-{swv}',
                tightness:  false,  //'.{swv@pre}-tightness tightness-{swv}',
                shape:      false,  //'.{swv@pre}-shape shape-{swv}',
                effect:     false,  //'.{swv@pre}-effect effect-{swv}',
                hoverable:  false,  //'hoverable:disabled': '.disabled',
                active:     false,  //'.active',

                /**
                 * 快捷应用 原子样式
                 * !! 默认开启，可在组件内部设为 false 来关闭监听
                 */
                fc:     '.fc-{swv}',
                fs:     '.fs-{swv}',
                fw:     '.fw-{swv}',
                fml:    '.fml-{swv}',
                pd:     '.pd-{swv}',
                pdPo:   '.pd-po-{swv}',
                mg:     '.mg-{swv}',
                mgPo:   '.mg-po-{swv}',
                bgc:    '.bgc-{swv}',
                bgcA:   '.bgc-a{swv}',
                bdPo:   '.bd-m bd-po-{swv}',
                bd:     '.bd-{swv}',
                bdc:    '.bdc-{swv}',
                rd:     '.rd-{swv}',
                rdPo:   '.rd-po-{swv}',
                opa:    '.opa-{swv}',
                shadow: '.shadow-{swv}',
                flexX:  '.flex-x-{swv}',
                flexY:  '.flex-y-{swv}',
            },
    
            /**
             * 此组件的 size|color 参数 对应的在 $ui.cssvar.size|color 中的 键名
             * 例如：button 组件中，sty.csvKey.size = btn
             * 表示从 $ui.cssvar.size.btn[...] 获取尺寸值
             * !! 引用组件内部必须覆盖
             */
            csvKey: {
                size: '',
                color: '',
            },
        },

    }},

    computed: {
        //快捷访问 this.$ui.cssvar
        $cssvar() {
            return this.$ui.cssvar || {};
        },

        /**
         * 组件样式计算必须的 计算属性
         */
        //判断 custom class 是否为空
        /*isEmptyClass() {
            let is = this.$is,
                ccls = this.customClass;
            if (is.string(ccls)) return ccls === '';
            if (is.array(ccls)) {
                let ncls = ccls.filter(cli => {
                    return is.string(cli) && cli !== '';
                });
                return ncls.length<=0;
            }
            return true;
        },
        //判断 custom style 是否是 object 或 为空
        isEmptyStyle() {
            let is = this.$is,
                csty = this.customStyle;
            if (is.string(csty)) return csty === '';
            if (is.plainObject(csty)) return is.empty(csty);
            return true;
        },*/

        /**
         * 获取当前需要自动计算样式的元素 []
         * !! 将收集 sty.[calculator|init].[class|style] 中定义的元素
         * !! 任意需要自动计算样式的元素，只定义 [calculator|init].[class|style] 其中一个项目即可被收集到
         */
        styElmList() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                calc = this.sty.calculator || {},
                init = this.sty.init || {},
                //从这些 参数 {} 中提取 元素名
                ec = [calc.class || {}, calc.style || {}, init.class || {}, init.style || {}],
                elms = [];
            for (let i=0;i<ec.length;i++) {
                if (!iso(ec[i])) continue;
                //收集定义的 元素
                this.$each(ec[i], (ecp, el) => {
                    if (!elms.includes(el)) elms.push(el);
                });
            }
            return elms;
        },

        /**
         * 解析 init|[custom|elm] class|style
         * 返回 class[]|css{}
         * !! 传入的 customClass 只针对 root 元素
         * !! 如果 sty.[calculator|init].[class|style] 中指定了其他要计算的元素，例如 foo
         * !!       则外部传入的 custom class|style 的 prop 名称必须是  fooClass|Style
         */
        //初始 class[]
        styInitClass() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                elms = this.styElmList,
                init = this.sty.init.class,
                rtn = {};
            //如果没有定义任何需要计算样式的元素，直接返回 {}
            if (!isa(elms)) return rtn;
            this.$each(elms, (el,i) => {
                let cls = init[el];
                if (!is.defined(cls) || !(iss(cls) || isa(cls))) {
                    rtn[el] = [];
                    return true;
                }
                if (isa(cls)) {
                    rtn[el] = cls;
                    return true;
                }
                if (iss(cls)) {
                    rtn[el] = this.$cgy.toClassArr(cls);
                    return true;
                }
            });
            return rtn;
        },
        //初始 style{}
        styInitStyle() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                elms = this.styElmList,
                init = this.sty.init.style,
                rtn = {};
            //如果没有定义任何需要计算样式的元素，直接返回 {}
            if (!isa(elms)) return rtn;
            this.$each(elms, (el,i) => {
                let sty = init[el];
                if (!is.defined(sty) || !(iss(sty) || iso(sty))) {
                    rtn[el] = {};
                    return true;
                }
                if (iso(sty)) {
                    rtn[el] = sty;
                    return true;
                }
                if (iss(sty)) {
                    rtn[el] = this.$cgy.toCssObj(sty);
                    return true;
                }
            });
            return rtn;
        },
        //传入的 class[]
        styCustomClass() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                icls = this.styInitClass,
                rtn = {};
            this.$each(icls, (clsi, elm) => {
                let cp = elm==='root' ? 'customClass' : `${elm}Class`,
                    cd = is.defined(this[cp]) ? this[cp] : null;
                if (is.null(cd) || !(iss(cd) || isa(cd))) {
                    //未定义 合法的 外部 customClass
                    rtn[elm] = [];
                    return true;
                }
                if (is.string(cd)) {
                    //传入 string 形式的 customClass 转为 []
                    rtn[elm] = this.$cgy.toClassArr(cd);
                } else {
                    //传入 array 形式的 customClass
                    rtn[elm] = [...cd];
                }
            });
            //返回
            return rtn;
        },
        //传入的 css{}
        styCustomStyle() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                icsty = this.styInitStyle,
                rtn = {};
            this.$each(icsty, (styi, elm) => {
                let cp = elm==='root' ? 'customStyle' : `${elm}Style`,
                    cd = is.defined(this[cp]) ? this[cp] : null;
                if (is.null(cd) || !(iss(cd) || iso(cd))) {
                    //未定义 合法的 外部 customStyle
                    rtn[elm] = {};
                    return true;
                }
                if (is.string(cd)) {
                    //传入 string 形式的 customStyle 转为 {}
                    rtn[elm] = this.$cgy.toCssObj(cd);
                } else {
                    //传入 {} 形式的 customStyle
                    rtn[elm] = this.$cgy.extend({}, cd);
                }
            });
            //返回
            return rtn;
        },

        /**
         * 样式计算核心方法
         * 返回计算后的 class[] css{}
         */
        //自动计算并返回 class[] 合并了外部传入的 custom class
        styComputedClass() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                isfn = fn => (iss(fn) && is.function(this[fn])) || is.function(fn),
                //预定义的 样式计算方法
                calc = this.sty.calculator.class || {},
                //外部传入的 [custom|elm]Class
                ccls = this.styCustomClass,
                rtn = {};
            //如果没有定义任何需要计算样式的元素，直接返回 {}
            if (!iso(ccls)) return rtn;
            this.$cgy.each(ccls, (cclsi, el) => {
                let fn = calc[el];
                //空字符串表示使用默认方法名
                if (!is.defined(fn) || fn==='') fn = `styCalc${el.ucfirst()}Class`;
                //定义的 计算方法必须是 methods 方法名，或直接定义的 function，如果未定义，则调用默认方法
                if (!isfn(fn)) {
                    //默认使用 styCalc() 核心样式计算方法
                    fn = () => this.styCalc(el, 'class');
                } else{
                    //定义了 methods 方法名，或直接定义的 function
                    if (iss(fn)) fn = this[fn];
                }
                //准备样式计算的结果 class[]
                let clss = [];
                //确保获取到正确的计算方法
                if (is.function(fn)) {
                    //绑定
                    fn = fn.bind(this);
                    //执行 计算方法，返回 class[] 
                    clss = fn();
                }
                //计算结果必须是 []
                if (!is.array(clss)) clss = [];
                //!! 自动合并外部传入的 [custom|elm] class[]
                if (isa(cclsi)) {
                    clss = this.$cgy.mergeClassArr(clss, cclsi);
                }

                //保存到返回值 {} 的 el 键中
                rtn[el] = clss.unique();
            });
            //返回计算结果
            return rtn;
        },
        //自动计算并返回 css{} 合并了外部传入的 custom style
        styComputedStyle() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                isfn = fn => (iss(fn) && is.function(this[fn])) || is.function(fn),
                //calc = this.sty.calculator || {},
                //cssCalc = calc.style || {},
                //预定义的 样式计算方法
                calc = this.sty.calculator.style || {},
                //外部传入的 [custom|elm]Style
                csty = this.styCustomStyle,
                rtn = {};
            //如果没有定义任何需要计算样式的元素，直接返回 {}
            if (!iso(csty)) return rtn;
            this.$cgy.each(csty, (cstyi, el) => {
                let fn = calc[el];
                //空字符串表示使用默认方法名
                if (!is.defined(fn) || fn==='') fn = `styCalc${el.ucfirst()}Style`;
                //定义的 计算方法必须是 methods 方法名，或直接定义的 function，如果未定义，则调用默认方法
                if (!isfn(fn)) {
                    //默认使用 styCalc() 核心样式计算方法
                    fn = () => this.styCalc(el, 'style');
                } else{
                    //定义了 methods 方法名，或直接定义的 function
                    if (iss(fn)) fn = this[fn];
                }
                //准备样式计算的结果 style{}
                let css = {};
                //确保获取到正确的计算方法
                if (is.function(fn)) {
                    //绑定
                    fn = fn.bind(this);
                    //执行 计算方法，返回 style{} 
                    css = fn();
                }
                //计算结果必须是 {}
                if (!is.plainObject(css)) css = {};
                //!! 自动合并外部传入的 [custom|elm] style{}
                if (iso(cstyi)) {
                    css = this.$extend(css, cstyi);
                }
                
                //保存到返回值 {} 的 el 键中
                rtn[el] = css;
            });
            //返回计算结果
            return rtn;
        },
        //styComputedClass 得到的 所有 class[] 转为 字符串
        styComputedClassStr() {
            let is = this.$is,
                clss = this.styComputedClass,
                rtn = {};
            if (!is.plainObject(clss) || is.empty(clss)) return {};
            this.$each(clss, (cls, el) => {
                if (!is.array(cls) || cls.length<=0) {
                    rtn[el] = '';
                } else {
                    rtn[el] = cls.join(' ');
                }
            });
            return rtn;
        },
        //styComputedStyle 得到的所有 css{} 转为 字符串
        styComputedStyleStr() {
            let is = this.$is,
                stys = this.styComputedStyle,
                rtn = {};
            if (!is.plainObject(stys) || is.empty(stys)) return {};
            this.$each(stys, (sty, el) => {
                if (!is.plainObject(sty) || is.empty(sty)) {
                    rtn[el] = '';
                } else {
                    rtn[el] = this.$cgy.toCssString(sty);
                }
            });
            return rtn;
        },



        /**
         * 格式化 group|sub|switch 参数，返回标准参数格式
         */
        //格式化 sty.group 参数，返回标准参数格式 {}
        styGroup() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                group = this.sty.group,
                rtn = {};
            if (!iso(group)) return rtn;
            this.$each(group, (v,k) => {
                let p = this.styParseGroupParams(k,v);
                if (!iso(p)) return true;
                rtn[p.key] = p;
            });
            return rtn;
        },
        //返回启用的 group 组开关参数 {}
        styEnabledGroup() {
            let group = this.styGroup,
                rtn = {};
            this.$each(group, (v,k) => {
                if (v.enable!==true) return true;
                rtn[k] = v;
            });
            return rtn;
        },
        //格式化 sty.sub 参数，返回标准参数格式 {}
        stySub() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                sub = this.sty.sub,
                rtn = {};
            if (!iso(sub)) return rtn;
            this.$each(sub, (v,k) => {
                let p = this.styParseSubParams(k,v);
                if (!iso(p)) return true;
                rtn[k] = p;
            });
            return rtn;
        },
        //返回启用的 sub 子系统参数 {}
        styEnabledSub() {
            let sub = this.stySub,
                rtn = {};
            this.$each(sub, (v,k) => {
                if (v.enable!==true) return true;
                rtn[k] = v;
            });
            return rtn;
        },
        //格式化 sty.switch 参数，返回标准参数格式 {}
        stySwitch() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                sws = this.sty.switch,
                rtn = {};
            if (!iso(sws)) return rtn;
            this.$each(sws, (v,k) => {
                let p = this.styParseSwitchParams(k,v);
                if (!iso(p)) return true;
                rtn[k] = p;
            });
            return rtn;
        },
        //返回启用的 switch 其他样式开关参数 {}
        styEnabledSwitch() {
            let sws = this.stySwitch,
                rtn = {};
            this.$each(sws, (v,k) => {
                if (v.enable!==true) return true;
                rtn[k] = v;
            });
            return rtn;
        },



        /**
         * size 子系统
         */

        /**
         * 判断传入的 size 参数的形式，可以有 5 中参数形式
         *      medium|normal       str     尺寸字符形式，在 sizeMap 中定义了键名的
         *      m|s|xl              key     尺寸键形式，在 sizeMap 中定义了键值的
         *      fs.m|btn.xl         csv     在 $ui.cssvar.size 中定义了项目的
         *      32px|100vw|75%      css     可直接在 css 中使用的 尺寸值字符串
         *      72|128              num     纯数字，自动增加 px 单位
         * !! 组件内部不要修改
         */
        sizePropType() {
            return this.$ui.sizeType(this.size);
        },
        //当 sizePropType == key 时，返回对应的 size 字符串 xl --> large
        sizeKeyToStr() {
            if (this.sizePropType !== 'key') return '';
            return this.$ui.sizeKeyToStr(this.size);
        },
        //当 sizePropType == str 时，返回对应的 size key large --> xl
        sizeStrToKey() {
            if (this.sizePropType !== 'str') return '';
            return this.$ui.sizeStrToKey(this.size);
        },
        /**
         * 根据传入的 size 参数，获取实际输出的 基础 size 的值
         * 所有尺寸计算，将基于此
         * !! 组件内部不要修改
         * !! 返回的是 带有单位的 css 尺寸字符串 如：100px|50%|100vw ...
         */
        sizePropVal() {
            let csvKey = this.sty.csvKey.size;
            if (csvKey==='') csvKey = null;
            return this.$ui.sizeVal(this.size, csvKey);
        },
        


        /**
         * color 子系统
         */
        /**
         * 根据 type|color 参数，获取最终的 颜色参数值
         * color 覆盖 type
         */
        colorPropVal() {
            let is = this.$is,
                cr = this.color,
                tp = this.type,
                c = '';
            if (
                !(is.string(cr) && cr !== '') &&
                !(is.string(tp) && tp !== '')
            ) {
                //都未指定，返回 ''
                return '';
            }
            if (is.string(cr) && cr !== '') {
                c = cr;
            } else {
                c = tp;
            }
            return c;
        },
        /**
         * 根据 type|color 参数，判断传入的颜色参数形式，可以有：
         *      primary|danger          str     颜色字符串形式
         *      red|blue-l2|black       key     $ui.cssvar.color 中的键形式
         *      red.m|fc.l2             key     同上
         *      rgba()|#fff             css     有效的 css 字符串形式
         * !! 组件内部要修改
         */
        colorPropType() {
            let is = this.$is,
                //传入的实际 颜色参数值
                cr = this.colorPropVal,
                csv = this.$ui.cssvar.color,
                tps = this.$ui.cssvar.extra.color.types;
            //未传入任何 颜色参数
            if (!is.string(cr) || cr === '') return null;
            //在 $ui.cssvar.extra.color.types 中定义了  --> str
            if (tps.includes(cr)) return 'str';
            //$ui.cssvar.color 中的键形式  --> key
            if (cr.includes('.') || cr.includes('-')) {
                let k = cr.includes('.') ? cr.split('.')[0] : cr.split('-')[0];
                if (is.defined(csv[k])) return 'key';
            } else if (is.defined(csv[cr])) {
                return 'key';
            }
            //必须是有效的 css 颜色字符串
            let reg = this.$cgy.reg;
            if (
                reg('hex').test(cr) === true ||
                reg('rgb').test(cr) === true ||
                reg('hsl').test(cr) === true
            ) {
                return 'css';
            }
            return null;
        },
        /**
         * 将 str|key 形式的 颜色字符串，转为可以拼接 color-class 的 字符串
         *      primary         --> primary-m
         *      red-l3          --> red-l3
         *      blue.d2         --> blue-d2
         */
        colorValToKey() {
            let is = this.$is,
                cr = this.colorPropVal,
                tp = this.colorPropType;
            if ('str,key'.split(',').includes(tp) !== true) return '';
            if (cr.includes('.'))  return cr.split('.').join('-');
            if (cr.includes('-')) return cr;
            return `${cr}-m`;
        },
        /**
         * 获取实际的 颜色值 hex|rgb|hsl 形式的 颜色值字符串
         */
        colorValStr() {
            let tp = this.colorPropType,
                ck = this.colorValToKey;
            //直接传入了颜色值
            if (tp === 'css') return this.colorPropVal;
            //传入了 颜色名|键
            ck = ck.replace('-','.');
            return this.$cgy.loget(this.$ui.cssvar.color, ck);
        },



        /**
         * animate 子系统
         */
        //计算当前的 animate class[]
        animateClasses() {
            return this.styCalcRootAnimateClass();
        },



        /**
         * base-style 样式系统 props 收集，用于向子组件传递样式参数
         */
        styProps() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                isd = o => is.defined(o),
                lgt = this.$cgy.loget,
                props = {
                    prefix: this.sty.prefix,
                    csvKey: this.sty.csvKey,
                },
                sbs = this.styEnabledSub,
                gps = this.styEnabledGroup,
                sws = this.styEnabledSwitch;
            //收集启用的子系统样式参数
            if (iso(sbs)) {
                this.$each(sbs, (sbc,sb) => {
                    //跳过 switch 子系统
                    if (sb==='switch') return true;
                    //尺寸子系统
                    if (sb==='size') {
                        props.size = this.size;
                        props.sizeVal = this.sizePropVal;
                        return true;
                    }
                    //颜色子系统
                    if (sb==='color') {
                        props.color = this.colorPropVal;
                        return true;
                    }
                    //动画子系统
                    if (sb==='animate') {
                        props.animateType = this.animateType;
                        props.animateClass = this.animateClass;
                        props.animateInfinite = this.animateInfinite;
                        return true;
                    }
                    //其他自定义子系统，需要定义 fooPropVal 计算属性 用于获取子系统的样式参数
                    if (is.defined(this[`${sb}PropVal`])) {
                        props[sb] = this[`${sb}PropVal`];
                    }
                });
            }
            //收集启用的组开关监听值
            if (iso(gps)) {
                this.$each(gps, (gpc,gp) => {
                    props[gp] = lgt(this, gpc.watch);
                });
            }
            //收集启用的 switch 开关监听值
            if (iso(sws)) {
                this.$each(sws, (swc,sw) => {
                    props[sw] = lgt(this, swc.watch);
                });
            }
            //额外处理
            if (isd(props.bd) || isd(props.bdPo) || isd(props.bdc)) {
                //是否有 border 边框
                props.hasBd = iss(props.bd) || iss(props.bdPo) || iss(props.bdc);
            }
            //返回收集到的 样式系统 props 可挂载到 slot 插槽中 或 传递给子组件
            return props;
        },
    },

    methods: {

        /**
         * 自动补全 sty.prefix 类名前缀
         *  foo     --> pre-foo
         *  .foo    --> foo
         */
        styClsn(clsn) {
            let is = this.$is,
                pre = this.sty.prefix;
            if (!is.string(clsn) && !is.string(pre)) return '';
            if (!is.string(clsn)) return is.string(pre) ? pre : '';
            let nopre = clsn.startsWith('.');
            if (nopre) clsn = clsn.substring(1);
            if (!is.string(pre) || pre==='' || nopre) return clsn;
            return `${pre}-${clsn}`;
        },

        /**
         * size 尺寸系统，自动缩放传入的 size 尺寸
         * 如果计算未能得到合法尺寸结果，则原样返回
         */
        stySizeShift(shift='s1', csvKey='icon') {
            let is = this.$is,
                ui = this.$ui,
                sztp = this.sizePropType,
                size = this.size;
            if (!is.string(shift) || shift==='' || shift.length!==2) return size;

            //传入了 str|key 形式的尺寸参数  large|xl 形式
            if ('str,key'.split(',').includes(sztp)) {
                return ui.sizeKeyShiftTo(size, shift);
            }

            //直接传入了尺寸值 100px 形式
            let sz = this.sizePropVal;
            if (!ui.isSizeVal(sz)) return size;
            //缩放 1 级，相差 4 单位
            let sftl = shift.split(''),     //s1 --> [s,1]
                sftp = sftl[0],
                slvl = sftl[1] * 1,
                step = 4;
            if (!'sl'.includes(sftp)) sftp = 's';
            if (isNaN(slvl)) slvl = 1;
            //计算缩放后尺寸值
            let nsz = null,
                nk = null;
            if (sftp==='s') {
                nsz = ui.sizeValSub(sz, slvl*step);
            } else {
                nsz = ui.sizeValAdd(sz, slvl*step);
            }
            //缩放后尺寸不合法
            if (!ui.isSizeVal(nsz)) return size;
            //缩放后尺寸值 转为 cssvar.size[csvKey] 中的键名
            nk = ui.sizeValToKey(nsz, csvKey);
            if (is.string(nk)) return nk;
            return nsz;
        },

        /**
         * 默认的 root 根元素样式类 计算方法
         * !! 组件可以覆盖并实现各自的 计算方法
         * @return {Array} class[]
         */
        styCalcRootClass() {
            return this.styCalc('root', 'class');
        },

        /**
         * 默认的 root 根元素样式 计算方法（模板方法）
         * !! 通常情况下，仅根据输入的 prop 计算元素 class[] 即可，然后通过 样式类来控制组件外观
         * !! 一般不需要单独计算样式
         * !! 特殊组件可以覆盖并实现各自的 计算方法
         * @return {Object} css{}
         */
        styCalcRootStyle() {
            return this.styCalc('root', 'style');
        },

        /**
         * 默认的组件元素样式计算方法
         * !! 这是底层方法，组件内部不要覆盖
         * !! 如果需要自定义某个元素的 class|style 计算方法，覆盖 styCalc[Elm][Class|Style] 方法
         * @param {String} elm 要计算样式的元素名，在 this.sty.calculator 中定义的元素
         * @param {String} calc 要计算 class 还是 style，决定以返回的是  [] 还是 {}
         * @return {Array|Object} 返回 [] | {}
         */
        styCalc(elm='root', calc='class') {
            let is = this.$is,
                //非空数组
                isa = a => is.array(a) && a.length>0,
                //非空 {}
                iso = o => is.plainObject(o) && !is.empty(o),
                //class[] 合并方法
                mergeCls = this.$cgy.mergeClassArr,
                //css 功能样式类 前缀
                pre = this.sty.prefix,
                //class|style 初始值，必须指定为 []|{}
                inits = calc==='class' ? this.styInitClass : this.styInitStyle,
                init = inits[elm] || (calc==='class' ? [] : {}),
                //启用的样式组开关
                gps = this.styEnabledGroup,
                //要输出的 class|style  []|{}
                rtn = calc==='class' ? (isa(init) ? [...init] : []) : (iso(init) ? this.$extend({}, init) : {}),
                //合并结果
                merge = (rst, rsti) => {
                    if (calc==='class' && isa(rsti)) {
                        //rst.push(...rsti);
                        rst = mergeCls(rst, rsti);
                    } else if (calc==='style' && iso(rsti)) {
                        rst = this.$extend({}, rst, rsti);
                    }
                    return rst;
                },
                //执行计算
                exec = (rst, group=null, groupOn=false) => {
                    let rsti = this.styEachSubCalc(elm, calc, group, groupOn);
                    return merge(rst, rsti);
                };

            //开始依次执行各子系统的计算方法
            // 0    执行 不受任何样式组开关 影响的子系统计算方法
            rtn = exec(rtn);

            //!! 1  单独执行一次 switch 子系统的计算方法，处理那些不受任何组开关影响的 switch 开关
            rtn = merge(rtn, this.styCalcSwitch(elm, calc));

            // 2    循环所有 启用的样式组开关 分别在组开关 true|false 状态下执行各子系统对应的计算方法
            this.$each(gps, (v,k) => {
                //跳过未启用的，已在 styEnabledGroup 中筛选过，此处一般不可能
                if (v.enable!==true) return true;
                //准备组开关的 监听数据源 和 样式类名
                let clsn = this.styClsn(v.class),
                    //读取 监听的数据
                    gv = this.$cgy.loget(this, v.watch);
                //监听的数据不是 boolean 类型，跳过
                if (!is.boolean(gv)) return true;
                //分别执行 组开关的 true|false 状态下的 子系统计算方法
                if (gv) {
                    // 3    执行组开关 == true 时的子系统计算方法
                    rtn = exec(rtn, k, true);
                    // 4    为 root 元素添加 组开关的样式类
                    if (calc==='class' && elm==='root') {
                        rtn = mergeCls(rtn, clsn);
                    }
                } else {
                    // 5    执行组开关 == false 时的子系统计算方法
                    rtn = exec(rtn, k, false);
                }
            });

            return rtn;
        },

        /**
         * 循环执行所有已启用的样式子系统的计算方法
         * !! 这是底层方法，组件内部不要覆盖
         * @param {String} elm 在 this.sty.calculator 中定义的需要计算 class|style 的元素名 例如：root
         * @param {String} calc 要计算 class 还是 style，决定了返回值是 [] 还是 {}
         * @param {String} group 指定影响本次计算的 样式组开关  默认 null 表示执行不受任何组开关影响的子系统计算方法
         * @param {Boolean} groupOn 如果指定了 group 则此处指定此开关的 on|off 状态
         * @return {Array|Object} 根据 calc 决定返回 [] | {}
         */
        styEachSubCalc(elm='root', calc='class', group=null, groupOn=false) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                //启用的样式组开关参数
                gps = this.styEnabledGroup,
                //本次计算是否需要处理 group 组开关影响
                usegp = iss(group) && is.defined(gps[group]),
                //启用的子系统
                sub = this.styEnabledSub,
                //判断是否 {on:.., off:..} 复合计算方法
                //ismul = fs => is.plainObject(fs) && !is.empty(fs) && is.defined(fs.on) && is.defined(fs.off),
                //判断计算结果是否格式正确
                isrst = rst => (calc==='class' && is.array(rst)) || (calc==='style' && is.plainObject(rst)),
                //将子系统计算结果合并到输出结果中
                ext = (rst, rsti) => {
                    if (calc==='class') {
                        //rst.push(...rsti);
                        rst = this.$cgy.mergeClassArr(rst, rsti);
                    } else {
                        rst = this.$extend({}, rst, rsti);
                    }
                    return rst;
                },
                rst = calc==='class' ? [] : {};

            //如果传入了无效的 group 组开关名直接返回
            if (iss(group) && !is.defined(gps[group])) return rst;

            this.$each(sub, (subp, subn) => {
                //如果是否指定了 group 与 子系统的 noGroup 参数必须匹配
                if (!usegp !== subp.noGroup) return true;
                
                //获取 默认的 或 自定义的  方法 null|function
                let fn = this.styParseSubCalculator(subn, group, groupOn, elm, calc);

                //找到了计算方法
                if (is.function(fn)) {
                    //执行 传入 组开关参数 group|groupOn
                    let rsti = fn(group, groupOn);
                    if (isrst(rsti)) rst = ext(rst, rsti);
                    return true;
                }

                /**
                 * !! 新增 在未定义指定元素的指定子系统计算方法时，调用通用的 子系统计算方法
                 * 例如：
                 *      调用 针对 root 元素的 size 子系统的 class[] 计算方法，但是没有定义 styCalcRootSizeClass 方法
                 *      将回退至 styCalcSize 通用方法
                 * !! 通用子系统方法需要传入 完整的 参数序列 elm,calc,group,groupOn
                 */
                //通用方法名
                let fnn = `styCalc${subn.ucfirst()}`;
                fn = this[fnn] || null;
                if (is.function(fn)) {
                    //执行通用方法，传入完整参数
                    let rsti = fn(elm, calc, group, groupOn);
                    //合并结果
                    if (isrst(rsti)) rst = ext(rst, rsti);
                }
            });
            return rst;
        },



        /**
         * 格式化 group|sub|switch 参数，生成标准格式的 参数 {}
         */
        //解析组开关参数，得到标准格式参数 {}
        styParseGroupParams(key, p=null) {
            let is = this.$is,
                ext = this.$extend,
                lgt = this.$cgy.loget,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                //fooBar --> foo-bar
                snc = s => iss(s) ? s.toSnakeCase('-') : '';
            if (!iss(key) || (!iso(p) && !iss(p) && !is.boolean(p))) return null;
            //标准组开关参数格式
            let dft = {
                    key: null,
                    enable: false,
                    watch: null,
                    class: null
                };
            //解析键名
            if (!key.includesAny('.',':')) {
                /**
                 * 正常字符串形式  需要执行 fooBar --> foo-bar
                 * 例如：fooBar: **  --> {key: foo-bar, watch: fooBar, class: foo-bar}
                 */
                let k = snc(key);
                dft = ext(dft, {
                    key: k,
                    watch: key,
                    class: k
                });
            } else {
                //首先解析 ***:key-str
                if (key.includes(':')) {
                    let ka = key.split(':'),
                        k = snc(ka[1]);
                    key = ka[0];
                    if (iss(k)) dft.key = k;
                }
                if (key.includes('.')) {
                    //解析 fooBar.jaz  --> foo-bar-jaz
                    let ka = key.split('.').map(i=>snc(i)),
                        k = ka.slice(-1)[0],
                        cls = ka.join('-');
                    if (!iss(dft.key)) dft.key = k;
                    dft = ext(dft, {
                        watch: key,
                        class: cls
                    });
                } else {
                    //解析普通字符
                    let k = snc(key);
                    if (!iss(dft.key)) dft.key = k;
                    dft = ext(dft, {
                        watch: key,
                        class: k
                    });
                }
            }
            /*if (!key.includes(':')) {
                //foo.bar.jaz 形式
                let ka = key.split('.');
                dft = ext(dft, {
                    key: ka.slice(-1)[0],
                    watch: key,
                    class: ka.join('-')
                });
            } else if (!key.includes('.')) {
                //foo:bar 形式
                let ka = key.split(':');
                dft = ext(dft, {
                    key: ka[1],
                    watch: ka[0],
                    class: key
                });
            } else {
                //foo.bar.jaz:bar 形式
                let ka = key.split(':'),
                    kb = ka[0].split('.');
                dft = ext(dft, {
                    key: ka[1],
                    watch: ka[0],
                    class: kb.join('-')
                });
            }*/
            //解析参数
            if (is.boolean(p)) {
                //true|false
                dft.enable = p;
            } else if (iss(p)) {
                //字符串形式，作为 class 样式类名
                dft.class = p;
                dft.enable = true;
            } else if (iso(p)) {
                //{} 形式 合并
                dft = ext(dft, p);
            }
            //判断参数是否有效
            if (!iss(dft.key) || !iss(dft.watch) || !iss(dft.class)) return null;
            let wt = dft.watch;
            //判断监听的数据源是否存在，以及是否 Boolean 类型
            if (!wt.includes('.')) {
                if (!is.defined(this[wt]) || !is.boolean(this[wt])) return null;
            } else {
                if (is.undefined(lgt(this, wt)) || !is.boolean(lgt(this, wt))) return null;
            }
            //返回
            return dft;
        },
        //解析子系统参数，得到统一的标准数据{}
        styParseSubParams(key, p=false) {
            let is = this.$is,
                group = this.styEnabledGroup,
                ism = s => is.empty(s),
                iss = s => is.string(s) && s!=='',
                isb = s => is.boolean(s),
                iso = s => is.plainObject(s) && !ism(s);
            if (!iss(key) || (!iss(p) && !iso(p) && !isb(p))) return null;
            //定义标准参数格式
            let dft = {
                    enable: false,
                    group: false,
                    noGroup: true,
                };

            //解析参数 p
            if (isb(p)) {
                /**
                 * 传入 true|false 形式
                 *      true        --> { enable: true, group: false }
                 *      false       --> { enabled: false }
                 */
                dft.enable = p;
            } else if (iss(p)) {
                /**
                 * 传入字符串形式 both|on|off|group-key,...
                 *      'both'      --> { enable: true, group: true }
                 *      'on'        --> { enable: true, group: 'on' }
                 *      'off'       --> { enable: true, group: 'off' }
                 *      'disabled'  --> { enable: true, group: {disabled: true} }
                 *      'disabled:false|both'       --> { enable: true, group: {disabled: false|both} }
                 *      'foo,bar:both,jaz:false'    --> {
                 *          enable: true,
                 *          group: {
                 *              foo: true,
                 *              bar: 'both',
                 *              jaz: false
                 *          }
                 *      }
                 */
                dft.enable = true;
                if ('both,on,off'.split(',').includes(p)) {
                    dft.group = p==='both' ? true : p;
                } else {
                    dft.group = {};
                    if (!p.includesAny(',',':')) {
                        dft.group[p] = true;
                    } else {
                        let pa = p.split(',');
                        this.$each(pa, (pv,i) => {
                            if (!iss(pv)) return true;
                            if (!pv.includes(':')) {
                                dft.group[pv] = true;
                            } else {
                                let pva = pv.split(':');
                                if (!iss(pva[0]) || !iss(pva[1]) || 'true,false,both,none'.split(',').includes(pva[1])!==true) {
                                    return true;
                                }
                                dft.group[pva[0]] = 'both,none'.split(',').includes(pva[1]) ? pva[1] : pva[1]==='true';
                            }
                        });
                    }
                }
            } else {
                //直接传入了 {}
                dft = this.$extend(dft, p);
            }

            //验证 group 参数是否有效，自动生成 noGroup 参数值
            let gp = dft.group,
                //临时 group 
                tgp = {};
            if (iss(gp) || isb(gp)) {
                //group 参数是 on|off|true|false 形式 
                let rgp = isb(gp) ? (gp ? 'both' : 'none') : gp==='on';
                this.$each(group, (gpc, gpk) => {
                    if (gpc.enable!==true) return true;
                    tgp[gpk] = rgp;
                });
                //修改 group 参数
                dft.group = tgp;
                //如果所有 group 影响方式都是 none
                dft.noGroup = rgp==='none';
            } else {
                //group 参数是 {} 形式
                this.$each(gp, (v,k) => {
                    if (!is.defined(group[k])) return true;
                    if (!iss(v) && !isb(v)) return true;
                    if (iss(v) && ['none','both'].includes(v)!==true) v = 'none';
                    tgp[k] = v;
                });
                //未指定的 组开关设为 none
                this.$each(group, (v,k) => {
                    if (is.defined(tgp[k])) return true;
                    tgp[k] = 'none';
                });
                //修改 group 参数
                dft.group = tgp;
                //如果所有 group 影响方式都是 none
                let isnone = true;
                this.$each(tgp, (v,k)=>{
                    isnone = isnone && v==='none';
                });
                dft.noGroup = isnone;
            }

            //处理 calculator 方法名
            let calc = is.defined(dft.calculator) ? dft.calculator : {};
            if (!iso(calc)) calc = {};
            //如果 noGroup == true 则只生成单个方法
            if (dft.noGroup) {
                if (ism(calc.class)) calc.class = `styCalc{Elm}${key.ucfirst()}Class`;
                if (ism(calc.style)) calc.style = `styCalc{Elm}${key.ucfirst()}Style`;
            } else {
                this.$each(dft.group, (v,k)=>{
                    //group == none 不生成方法
                    if (v==='none') {
                        //如果有方法则删除
                        if (!ism(calc[k])) Reflect.deleteProperty(calc, k);
                        return true;
                    }
                    if (ism(calc[k])) calc[k] = {};
                    if (isb(v)) {
                        //group == true|false 只生成单个方法
                        if (ism(calc[k].class)) calc[k].class = `styCalc{Elm}${key.ucfirst()}Class${k.ucfirst()}`;
                        if (ism(calc[k].style)) calc[k].style = `styCalc{Elm}${key.ucfirst()}Style${k.ucfirst()}`;
                    } else {
                        //group == both 则需要生成两个方法
                        this.$each(['class','style'], ctp => {
                            if (ism(calc[k][ctp])) calc[k][ctp] = {on:null, off:null};
                            if (ism(calc[k][ctp].on)) calc[k][ctp].on = `styCalc{Elm}${key.ucfirst()}${ctp.ucfirst()}${k.ucfirst()}True`;
                            if (ism(calc[k][ctp].off)) calc[k][ctp].off = `styCalc{Elm}${key.ucfirst()}${ctp.ucfirst()}${k.ucfirst()}False`;
                        });
                    }
                });
            }
            //修改 calculator 参数
            dft.calculator = calc;

            //返回
            return dft;
        },
        //解析 switch 子系统 switch 参数，得到统一的标准数据 {}
        styParseSwitchParams(key, p=false) {
            let is = this.$is,
                lgt = this.$cgy.loget,
                group = this.styEnabledGroup,
                ism = s => is.empty(s),
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                isb = s => is.boolean(s),
                iso = s => is.plainObject(s) && !ism(s),
                //将数组中的所有 string 转为 snake-case 形式
                snake = (a, glue='-') => {
                    if (is.string(a) && a==='') return '';
                    if (iss(a)) {
                        return a.toSnakeCase(glue);
                    }
                    if (is.array(a)) {
                        if (a.length<=0) return [];
                        let na = [];
                        this.$each(a, i=>{
                            if (!iss(i)) return true;
                            na.push(snake(i, glue));
                        });
                        return na;
                    }
                    return a;
                };
            if (!iss(key) || (!iss(p) && !iso(p) && !isb(p) && !isa(p))) return null;
            //定义标准参数格式
            let dft = {
                    //是否启用
                    enable: false,
                    //受影响的 样式组开关键名
                    group: null,
                    //是否在 组开关 == true 时增加这个样式类，默认是 false，在 组开关 == false 时增加样式类
                    groupOn: false,
                    //针对某个元素，未指定 @foo 则针对 root 元素，如果指定了 @elm 则针对 foo 元素，可以有多个
                    for: [],
                    //监听数据源
                    watch: null,
                    //!! 监听值只有 == 此处定义的值时，才会执行后续 修改 class[]|style{}
                    //默认 '*' 表示 true 或 任意非空字符串，可选 'foo'|true|false
                    until: '*',
                    //样式类名（自动补齐样式前缀）
                    //!! 如果是 [] 形式，则直接合并到元素 class[] 中，不做其他处理
                    class: null,
                    //可合并到元素 style{} 中的样式
                    style: {},
                };
            //key 字符串末尾包含 #1|#2... 标记，表示相同开关监听条件下，执行多个 样式计算操作
            if (key.includes(' #')) {
                //!! # 前面必须包含 空格
                key = key.replace(/\s+#/g, ' #');
                //直接截掉 # 后字符
                key = key.split(' #')[0];
            }
            //解析 key  [!]foo.bar[=any]:!groupA@root@elmA@elmB
            if (!key.includesAny('.',':',':!','@','=') && !key.startsWith('!')) {
                //dft.key = snake(key);
                dft.for = ['root'];
                dft.watch = key;
                dft.class = snake(key);
            } else {
                //处理指定了监听值 必须等于某个指定值 的情况
                if (key.startsWith('!')) {
                    // !foo.... 形式
                    key = key.substring(1);
                    dft.until = false;
                } else if (key.includes('=')) {
                    // foo=bar... 形式
                    let ka = key.split('=');
                    if (!ka[1].includesAny(':','@')) {
                        dft.until = ka[1];
                        key = ka[0];
                    } else {
                        let kaa = [], ksuf = '';
                        if (ka[1].includes(':')) {
                            kaa = ka[1].split(':');
                            ksuf = `:${kaa[1]}`;
                        } else {
                            kaa = ka[1].split('@');
                            ksuf = `@${kaa[1]}`;
                        }
                        dft.until = kaa[0];
                        key = `${ka[0]}${ksuf}`;
                    }
                }
                //处理 switch 只针对某个元素的 情况
                if (key.includes('@')) {
                    let ka = key.split('@');
                    key = ka[0];
                    dft.for = ka.slice(1);
                } else {
                    dft.for = ['root'];
                }
                //处理 group|groupOn
                if (key.includesAny(':',':!')) {
                    let ka = key.split(':'),
                        kg = ka[1];
                    if (kg.startsWith('!')) {
                        //:!disabled 则 groupOn = false;
                        dft.groupOn = false;
                        dft.group = kg.substring(1);
                    } else {
                        //:disabled 则 groupOn = true
                        dft.groupOn = true;
                        dft.group = kg;
                    }
                    //key 更新
                    key = ka[0];
                }
                dft.watch = key;
                if (key.includes('.')) {
                    let ka = key.split('.');
                    //dft.key = snake(ka.slice(-1)[0]);
                    dft.class = snake(ka, '-').join('-');
                } else {
                    //dft.key = snake(key);
                    dft.class = snake(key);
                }
            }
            //解析 p
            if (iss(p)) {
                //字符串形式 p
                dft.enable = true;
                if (p.includes(':')) {
                    //p == 'width: 100px;' 形式
                    p = this.$cgy.toCssObj(p);
                    if (iso(p)) {
                        dft.style = p;
                        dft.class = null;
                    }
                } else if (p.startsWith('.')/* || p.includes(' ')*/) {
                    //p == '.foo-bar' 形式 直接使用原子类 必须 . 开头
                    p = p.startsWith('.') ? p.substring(1) : p;
                    p = this.$cgy.toClassArr(p);
                    if (isa(p)) {
                        dft.class = p;
                    }
                } else {
                    //p == 'foo-bar' 或 'fooBar' 形式
                    dft.class = snake(p);
                }
            } else if (isa(p)) {
                //设置了 [] 数组形式
                dft.enable = true;
                dft.class = p;
            } else if (isb(p)) {
                //设置了 true|false
                dft.enable = p;
            } else {
                //设置了 {}
                if (this.$cgy.isCssObj(p)) {
                    //直接设置了 style{} 
                    dft.enable = true;
                    dft.style = p;
                    dft.class = null;
                } else {
                    //直接设置了 {} 格式的 switch 参数值
                    dft = this.$extend(dft, p);
                }
            }
            //判断参数是否有效
            if (!iss(dft.watch)) return null;
            if (!iso(dft.style) && !(iss(dft.class) || isa(dft.class))) return null;
            let wt = dft.watch,
                wv = !wt.includes('.') ? this[wt] : lgt(this, wt);
            //判断监听的数据源是否存在，以及是否 Boolean|String 类型
            if (is.undefined(wv) || !(is.boolean(wv) || is.string(wv))) return null;
            //!! 根据监听值类型，处理 until 的值
            if (is.string(wv)) {
                //监听 string 类型，则 until 只能是 ''|'*'|'fooBar-...任意'
                if (!is.string(dft.until)) {
                    //until 值不是 string
                    if (dft.until===true) {
                        dft.until = '*';
                    } else if (dft.until===false) {
                        dft.until = '';
                    } else {
                        dft.until = '*';
                    }
                } else {
                    //''|"" 字符串转为空值
                    if (dft.until==="''" || dft.until==='""') dft.until = '';
                }
            } else if (is.boolean(wv)) {
                //监听 boolean 类型，则 until 只能是 true|false
                if (!is.boolean(dft.until)) {
                    //'true'|'false' 转为真正的 boolean
                    if ('true,false'.split(',').includes(dft.until)) dft.until = dft.until!=='false';
                    //任意非空字符串 转为 true
                    if (is.string(dft.until)) {
                        dft.until = dft.until!=='';
                    } else {
                        dft.until = true;
                    }
                }
            }
            
            //返回
            return dft;
        },
        /**
         * 从子系统完整参数中，获取对应的方法函数 返回 null|function
         * 如果指定的方法名不存在，则会依次回退到默认方法名，回退方式举例：
         * 
         *  子系统 = foosub，组开关 = bargroup，影响方式 == 'both'，查找 bargroup == true 状态下 root 元素的 class 计算方法名：
         *      首先查找任意自定义方法名，未定义或未找到方法，则依次回退到下列方法名：
         *          --> styCalcRootFoosubClassBargroupTrue
         *          --> styCalcRootFoosubClassGroupTrue     //此方法在 所有组开关的 true 状态下都有效
         * 
         *  子系统 = foosub，组开关 = bargroup，影响方式 == false，查找 bargroup == false 状态下 root 元素的 style 计算方法名：
         *      首先查找任意自定义方法名，未定义或未找到方法，则依次回退到下列方法名：
         *          --> styCalcRootFoosubStyleBargroup
         *          --> styCalcRootFoosubStyle              //此方法在 所有此子系统可生效的 组开关状态下 都有效
         * 
         *  子系统 = foosub，不受任何组开关影响，查找 任意组开关状态下 root 元素的 class 计算方法名：
         *      首先查找任意自定义方法名，未定义或未找到方法，则依次回退到下列方法名：
         *          --> styCalcRootFoosubClass
         */
        styParseSubCalculator(sub='size', group=null, groupOn=false, elm='root', calc='class') {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isf = f => is.function(f),
                isb = b => is.boolean(b),
                isd = d => is.defined(d),
                isdf = fn => iss(fn) && is.defined(this[fn]) && isf(this[fn]),
                iso = o => is.plainObject(o) && !is.empty(o),
                rep = s => s.replace('{Elm}', elm.ucfirst()).replace('{elm}', elm),
                //检查传入的 fn 方法名 或 function，返回有效的 function 无效则返回 null
                rtnfn = fn => {
                    if (iss(fn)) {
                        //定义了方法名
                        fn = rep(fn);
                        if (!isdf(fn)) return null;
                        fn = this[fn];
                    } else if (isf(fn)) {
                        //定义了 function
                        fn = fn.bind(this);
                    } else {
                        return null;
                    }
                    //返回找到的 function
                    return fn;
                },
                gps = this.styEnabledGroup,
                subs = this.styEnabledSub;
            if (iss(group) && !is.defined(gps[group])) return null;
            if (!iss(sub) || !is.defined(subs[sub])) return null;
            let p = subs[sub],
                calcs = p.calculator,
                fnPre = `styCalc{Elm}${sub.ucfirst()}${calc.ucfirst()}`,
                fn = null;
            
            //单一方法，且 此子系统不受任何组开关影响
            if (!iss(group)) {
                //子系统的 noGroup 参数必须为 true 才可以在此情况下执行计算方法
                if (p.noGroup!==true) return null;
                fn = calcs[calc] || null;
                if (!iss(fn) && !isf(fn)) {
                    //定义错误，则使用默认方法名
                    fn = fnPre;
                }
                return rtnfn(fn);
            }

            //获取组开关 group 对此子系统的影响形式
            let gp = p.group[group] || 'none';
            if (!isb(gp) && !iss(gp)) return null;
            if (iss(gp) && ['none','both'].includes(gp)!==true) return null;

            //读取 calcs[group][calc]
            let getfn = () => (iso(calcs[group]) && isd(calcs[group][calc])) ? calcs[group][calc] : undefined;

            //单一方法，且 此子系统受传入 group 组开关的影响方式 != 'both'
            if (gp!=='both') {
                //console.log(p, calcs, group, calc);
                //fn = calcs[group][calc] || null;
                fn = getfn() || null;
                if (!iss(fn) && !isf(fn)) {
                    //定义错误，则使用默认方法名
                    fn = `${fnPre}${group.ucfirst()}`;
                } else {
                    //自定义的 方法 或 function
                    let tfn = rtnfn(fn);
                    if (isf(tfn)) return tfn;
                    //自定义的 方法名 不存在，则使用默认的
                    fn = `${fnPre}${group.ucfirst()}`;
                }
                //再检查一次
                let tfn = rtnfn(fn);
                if (isf(tfn)) return tfn;
                //如果还不存在，则使用默认的 无 group 键名的 方法名
                return rtnfn(fnPre);
            }

            //当 此子系统受传入 group 组开关的影响方式 == 'both' 时，将读取复合方法 {on:..., off:...}
            let fns = getfn() || {},    //fns = calcs[group][calc] || {},
                fnk = groupOn ? 'on' : 'off';
            fn = fns[fnk] || null;
            if (!iss(fn) && !isf(fn)) {
                //定义错误，则使用默认方法名
                fn = `${fnPre}${group.ucfirst()}${groupOn ? 'True' : 'False'}`;
            } else {
                //自定义的 方法 或 function
                let tfn = rtnfn(fn);
                if (isf(tfn)) return tfn;
                //自定义的 方法名 不存在，则使用默认的
                fn = `${fnPre}${group.ucfirst()}${groupOn ? 'True' : 'False'}`;
            }
            //再检查一次
            let tfn = rtnfn(fn);
            if (isf(tfn)) return tfn;
            //如果还不存在，则使用默认的 无 group 键名的 方法名
            return rtnfn(`${fnPre}Group${groupOn ? 'True' : 'False'}`);
        },



        /**
         * base-style 样式系统 子系统计算方法定义
         * size|color|switch|animate ... 子系统的必须方法，包括：
         *      计算 sty.calculator 中定义的所有元素的 class|style 的方法，例如：
         *          styCalcRootColorStyle   --> color 子系统，计算 root 元素的 style{}
         *          styCalcFooSizeClass     --> size 子系统，计算 foo 元素的 class[]
         * 当启用这些子系统时，会自动调用这些方法
         *      styCalc[Elm][Size|Color...]Class    方法返回 [...]
         *      styCalc[Elm][Size|Color...]Style    方法返回 {...}
         * !! 如果有需要，引用组件可以覆盖这些方法
         * !! 如果引用组件内部有其他元素需要计算样式，或有不同的计算逻辑，可自行 覆盖|增加 对应的计算方法
         * 例如：   组件内部 root 元素的 size 子系统有不同的计算逻辑，可覆盖 styCalcRootSizeClass|Style 方法
         *          或  组件内部针对 block 元素需要执行 size 子系统计算，则需要增加 styCalcBlockSizeClass|Style 方法
         */
        //size 子系统  默认只针对 root 元素执行计算，因此系统只定义 root 元素的计算方法
        styCalcRootSizeClass(group=null, groupOn=false) {
            let pre = this.sty.prefix,
                ptp = this.sizePropType,
                sz = this.size,
                clss = [];
            if ('str,key,num'.split(',').includes(ptp)) {
                //传入 str(medium) | key(xl) | num(100) 形式的 size 时 直接转为 size-class
                //key(xl) 转为 str(large) 
                if (ptp === 'key') sz = this.sizeKeyToStr;
                //num(100) 转为 100px
                if (ptp === 'num') sz += 'px';
                //转为 size-class 添加到 class[]
                clss.push(`${pre}-${sz}`)
            } else if (ptp === 'css' && sz.slice(-2) === 'px') {
                //传入 100px 形式时 也会转为 size-class 例如 icon-100px
                clss.push(`${pre}-${sz}`);
            }
            return clss;
        },
        styCalcRootSizeStyle(group=null, groupOn=false) {return {};},
        //color 子系统  默认只针对 root 元素执行计算，因此系统只定义 root 元素的计算方法
        styCalcRootColorClass(group=null, groupOn=false) {
            let pre = this.sty.prefix,
                ctp = this.colorPropType,
                cr = this.colorPropVal,
                clss = [];
            if ('str,key'.split(',').includes(ctp)) {
                //str(primary) 或 key(red|blue-l2|yellow.d1) 形式的 颜色参数 直接转为 color-class
                clss.push(`${pre}-${this.colorValToKey}`);
            }
            return clss;
        },
        styCalcRootColorStyle(group=null, groupOn=false) {return {};},
        //animate 子系统  默认只针对 root 元素执行计算，因此系统只定义 root 元素的计算方法
        styCalcRootAnimateClass(group=null, groupOn=false) {
            let is = this.$is,
                ani = this.animateType,
                inf = this.animateInfinite,
                ics = this.animateClass,
                rtn = ['animate__animated'];
            if (
                (is.string(ics) && ics !== '') ||
                (is.array(ics) && ics.length>0)
            ) {
                //传入了完整的 animate 类名序列
                if (is.string(ics)) ics = this.$cgy.toClassArr(ics);    //ics.replace(new RegExp('\\s+','g'), ' ').split(' ');
                //合并
                rtn.push(...ics);
            } else if (is.string(ani) && ani !== '') {
                //补齐 animate__ 前缀，再合并
                rtn.push(`animate__${ani}`);
            } else {
                //全部为指定，返回 空[]
                return [];
            }
            
            //循环
            if (inf === true) rtn.push('animate__infinite')
            //返回
            return rtn;
        },
        styCalcRootAnimateStyle(group=null, groupOn=false) {return {};},
        //switch 子系统  默认针对所有元素，因此系统定义 通用计算方法
        styCalcSwitch(elm='root', calc='class', group=null, groupOn=false) {
            //switch 子系统 内部自行区分不同的 元素|组开关 自行计算 class[]|style{}
            return this.styEachSwitch(elm, calc, group, groupOn);
        },

        

        /**
         * switch 子系统
         */
        //循环并根据 group 以及 groupOn 判断并生成 switch 样式类 class[]
        styEachSwitch(elm='root', calc='class', group=null, groupOn=false) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                isa = a => is.array(a) && a.length>0,
                //所有启用的 group 组开关
                gps = this.styEnabledGroup,
                //所有启用的 switch
                sws = this.styEnabledSwitch,
                ext = (o,n) => {
                    if (is.empty(o)) o = calc==='class' ? [] : {};
                    if (calc==='class') {
                        if (!isa(n)) return o;
                        o = this.$cgy.mergeClassArr(o, n);
                    } else {
                        if (!iso(n)) return o;
                        o = this.$extend({}, o, n);
                    }
                    return o;
                },
                rtn = calc==='class' ? [] : {};

            //如果未传入有效的 elm
            if (!iss(elm)) return rtn;

            //如果传入了 group 组开关名
            if (iss(group)) {
                //传入的 group 有误
                if (!is.defined(gps[group])) return rtn;
                //当前 group 参数
                let gp = gps[group],
                    //组开关监听的数据值
                    gv = this.$cgy.loget(this, gp.watch);
                //未获取到组开关监听的数据值 或 数据值不是 Boolean (通常不可能)
                if (is.undefined(gv) || !is.boolean(gv)) return rtn;
                //如果组开关监听值 !== 传入的 groupOn 表示内部方法调用出错 (通常不可能)
                if (gv !== groupOn) return rtn;
            }

            //开始循环 switch
            if (iso(sws)) {
                this.$each(sws, (swp, swk) => {
                    //跳过未启用的
                    if (swp.enable!==true) return true;
                    //跳过针对元素 [] 不含传入的 elm 的 switch
                    if (!swp.for.includes(elm)) return true;
                    
                    if (!iss(group)) {
                        //如果未传入 group 则仅执行 swp.group===null 的 switch
                        if (!is.null(swp.group)) return true;
                    } else {
                        //如果传入了 group
                        //仅执行 swp.group===group 的 switch
                        if (swp.group!==group) return true;
                        //仅执行 swp.groupOn===groupOn 的 switch
                        if (swp.groupOn!==groupOn) return true;
                    }

                    //根据 switch 监听数据值 生成 class[]|style{} 并合并
                    if (calc==='class') {
                        rtn = ext(rtn, this.stySwitchClassArr(swk));
                    } else {
                        rtn = ext(rtn, this.stySwitchStyleObj(swk));
                    }
                });
            }
            return rtn;
        },
        /**
         * 根据 switch 其他样式开关的 监听数据值，生成要插入 class[] 中的 样式类 []
         * @param {String} key switch-key 键名
         * @return {Array} 要插入 class[] 中的 样式类 []
         */
        stySwitchClassArr(key) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                //组件样式类前缀
                pre = this.sty.prefix,
                //启用的 switch
                sws = this.styEnabledSwitch;
            if (!iss(key) || !is.defined(sws[key])) return [];

            let swp = sws[key],
                //switch 定义的 class
                cls = swp.class,
                //开关监听数据值
                swv = this.$cgy.loget(this, swp.watch),
                //until 指定开关值满足的条件值
                until = swp.until;
            //switch 没有定义有效的 class 参数
            if (!iss(cls) && !isa(cls)) return [];
            //开关值 只能是 Boolean|String
            if (!is.boolean(swv) && !is.string(swv)) return [];
            //until 类型必须与 swv 类型一致
            if (!is.equal(until,swv)) return [];
            
            //针对 Boolean 类型开关值
            if (is.boolean(swv)) {
                //switch 定义了字符串形式的 class 则拼接类名
                if (iss(cls)) return swv===until ? [`${pre}-${cls}`] : [];
                
                //switch 定义了 [] 形式的 class 
                //检查 class[] 中是否包含 {swv@if,..} 字符串模板
                let tpl = [];
                this.$each(cls, (v,i) => {
                    if (iss(v) && v.includes('{swv@if,')) {
                        tpl.push(v);
                    }
                });
                //如果 class[] 中不包含 @if 字符串模板 则只在 swv == until 时 返回 class[]
                if (!isa(tpl)) {
                    if (swv===until) {
                        return cls.map(i=>this.stySwvParse(swv?'true':'false', i));
                    }
                    //return swv===until ? cls : [];
                    return [];
                }
                //如果 class[] 中包含 @if 字符串模板
                //swv == until 时，返回所有 class[] 同时处理其中的字符串模板
                if (swv===until) return cls.map(i=>this.stySwvParse(until?'true':'false', i));
                //swv !== until 时，只返回有字符串模板的 class[] 同时处理其中的字符串模板
                return tpl.map(i=>this.stySwvParse(swv?'true':'false', i));
            }

            //针对 String 类型开关值
            if (swv===until || (until==='*' && iss(swv))) {
                //switch 定义了字符串形式的 class 则拼接类名
                if (iss(cls)) return [`${pre}-${cls}`, `${pre}-${cls}-${swv.toSnakeCase('-')}`];
                //!! switch 定义了 [] 形式的 class 则尝试处理 class[] 中可能存在的 模板字符
                return cls.map(i=>this.stySwvParse(swv, i));
            }
            
            return [];
        },
        /**
         * 根据 switch 其他样式开关的 监听数据值，生成要插入 style{} 中的 样式数据 {}
         * @param {String} key switch-key 键名
         * @return {Array} 要插入 style{} 中的 样式数据 {}
         */
        stySwitchStyleObj(key) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                iscss = o => iso(o) && this.$cgy.isCssObj(o),
                //启用的 switch
                sws = this.styEnabledSwitch;
            if (!iss(key) || !is.defined(sws[key])) return {};

            let swp = sws[key],
                //switch 定义的 style
                sty = swp.style,
                //开关监听数据值
                swv = this.$cgy.loget(this, swp.watch),
                //until 指定开关值满足的条件值
                until = swp.until;
            //switch 没有定义有效的 style 参数
            if (!iscss(sty)) return {};
            //开关值 只能是 Boolean|String
            if (!is.boolean(swv) && !is.string(swv)) return {};
            //until 类型必须与 swv 类型一致
            if (!is.equal(until,swv)) return {};

            //如果开关值是 Boolean 类型尝试处理 style{} 中可能存在的 模板字符
            if (is.boolean(swv)) {
                //判断 switch.style 是否包含 {swv@if,...} 字符串模版
                let tpl = {};
                this.$each(sty, (v,k) => {
                    if (iss(v) && v.includes('{swv@if,')) {
                        tpl[k] = v;
                    }
                });
                //如果不包含 @if 字符串模板
                if (!iso(tpl)) {
                    //仅在 swv==until 时，返回 style
                    if (swv===until) {
                        return this.$each(sty, (v,k) => {
                            return this.stySwvParse(swv?'true':'false', v);
                        });
                    }
                    //return swv===until ? sty : {};
                    return {};
                }
                //如果包含 @if 字符串模板，则：
                //swv == until 时 返回所有 style 中定义的值，同时处理字符串模板
                if (swv===until) {
                    return this.$each(sty, (v,k) => {
                        return this.stySwvParse(until?'true':'false', v);
                    });
                }
                //swv !== until 时 只返回 style 中带有字符串模板的 样式参数值，同时处理字符串模板
                return this.$each(tpl, (v,k) => {
                    return this.stySwvParse(swv?'true':'false', v);
                });
            }

            //!! 如果开关值 符合 until 要求，则尝试处理 style{} 中可能存在的 模板字符
            if (swv===until || (until==='*' && iss(swv))) {
                return this.$each(sty, (v,k) => {
                    return this.stySwvParse(swv, v);
                });
            }
            
            //否则返回 空{}
            return {};
        },



        /**
         * string 类型的 switch 监听数据值 处理系列方法
         * 用于替换 定义在 switch 开关中的 模板字符
         */
        /**
         * 检查定义在 switch 中的字符串，可以是 class[ 'string', ... ] 或 style{color: 'string', ...}
         * 如果其中包含 {swv} 或 {swv@...} 模板字符，则执行并替换
         * @param {String} swv switch 监听数据值，必须是 string 类型
         * @param {String} def 定义的字符串
         * @return {String} 替换后的字符串
         */
        stySwvParse(swv, def) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='';
            //不合法 原样返回
            if (!iss(swv) || !iss(def)) return def;
            //定义正则
            let regx = new RegExp('\\{swv[^\\}]*\\}', 'g');
            return def.replace(regx, (si) => {
                //console.log(si);
                si = si.replace('{', '');
                si = si.replace('}', '');
                //执行语句
                let rtn = this.stySwvCall(swv, si);
                if (!iss(rtn)) return '';
                return rtn;
            });
        },
        /**
         * 调用入口
         * 根据 swv@function-name,arg,arg,...[@next-function,arg,arg,...] 语法 调用对应处理方法，传入参数，返回结果
         * @param {String} swv switch 监听数据值，必须是 string 类型
         * @param {String} cmd switch 定义值中的处理语句
         * @return {String} 执行处理后的字符串
         */
        stySwvCall(swv, cmd) {
            let is = this.$is;
            //语法不正确 原样返回
            if (!is.string(cmd) || cmd==='' || !cmd.startsWith('swv')) return cmd;
            //传入 'swv' 则返回原值
            if (cmd==='swv') return swv;
            //解析 cmd 语句，从左到右 依次执行，前一次执行结果，作为后一次执行的第一个参数
            if (cmd.startsWith('swv@')) {
                let ca = cmd.substring(4).split('@'),
                    rtn = swv;
                for (let i=0;i<ca.length;i++) {
                    let cmdi = ca[i];
                    //如果语句中包含 (...) 则将 (...) 作为子句，先执行
                    if (cmdi.includes('(') && cmdi.includes(')')) {
                        cmdi = this.stySwvParse(swv, cmdi.replace(/\(/g,'{swv@').replace(/\)/g,'}'));
                    }
                    if (!is.string(cmdi) || cmdi==='') continue;
                    //执行cmd语句
                    let cb = cmdi.split(',');
                    if (cb.length<1 || cb[0]==='') continue;
                    //获取方法名 参数列表
                    let fn = cb.shift();
                    fn = `stySwvTo${fn.toCamelCase(true)}`;
                    if (!is.defined(this[fn]) || !is.function(this[fn])) continue;
                    //上一次执行结果插入 参数列表首位
                    cb.unshift(rtn);
                    //执行方法 结果保存到 rtn
                    rtn = this[fn].call(this, ...cb);
                }
                return rtn;
            }
        },
        //模板写法：swv@replace,color.*.m  执行替换：primary --> color.primary.m
        //嵌套写法：swv@size-key@replace,size.btn.*  执行替换：large --> xl --> size.btn.xl
        stySwvToReplace(swv, rpl) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='';
            if (!iss(rpl)) return swv;
            if (!rpl.includes('*')) return rpl;
            return rpl.replace(/\*/g, swv);
        },
        //模板写法：swv@get,prop|foo.bar|... 读取组件当前的 props|data|computed 数据，必须是 string 类型
        stySwvToGet(swv, prop) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='';
            if (!iss(prop)) return swv;
            let v = prop.includes('.') ? this.$cgy.loget(this, prop) : this[prop];
            if (is.defined(v) && is.string(v)) return v;
            return swv;
        },
        //模板写法：swv@pre  读取 this.sty.prefix 
        stySwvToPre(swv) {
            return this.stySwvToGet(swv, 'sty.prefix');
        },
        //模板写法：swv@call,fn,arg,..swv..,arg,...  调用 this[fn] 方法 需要使用 'swv' 代替当前开关值传入目标方法的参数列表中
        stySwvToCall(swv, fn, ...args) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isf = f => (iss(f) && is(this[f], 'function,asyncfunction')) || is(f, 'function,asyncfunction');
            if (!isf(fn)) return swv;
            if (iss(fn)) fn = this[fn];
            //处理 args 参数列表中的 'swv' 字符，替换为当前开关值 swv
            args = args.map(i => i==='swv' ? swv : i);
            //执行方法
            let rst = fn(...args);
            if (!is.string(rst)) return swv;
            return rst;
        },
        //模板写法：swv@if,foo=bar,jaz=(function,arg,arg,...),... 根据 swv 读取映射的值，值可以是另一个 swv@指令
        //模板写法：swv@if,bar,(function,arg,arg,...)... 针对 swv 是 'true|false' 形式的值，true 返回第一个值，false 返回第二个值
        stySwvToIf(swv, ...maps) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0;
            if (!iss(swv) || !isa(maps)) return swv;
            
            //准备查找 swv 对应的 映射值，可能是另一个 swv@ 指令，形式为：'(function,arg,arg,...)'
            let mto = null;

            //开始从 maps 中查找映射值
            if (['true','false'].includes(swv)) {
                //如果 swv 是 'true|false' 字符串形式的 boolean 值
                //至少需要 2 个待选值
                if (maps.length<2) return swv;
                //true 选择第一个，false 选择第二个
                mto = swv==='true' ? maps[0] : maps[1];
            } else {
                //swv 是普通字符串
                this.$each(maps, (v,i) => {
                    if (!iss(v)) return true;
                    if (!v.includes('=')) v = `=${v}`;  //表示 swv==='' 时选用此值
                    let va = v.split('=');
                    if (va[0] === swv) {
                        mto = va[1];
                        return false;
                    }
                });
            }
            //未找到对应的 映射值 
            if (!iss(mto)) return swv;
            //如果找到的映射值是一个指令
            if (mto.startsWith('(') && mto.endsWith(')')) {
                mto = mto.substring(1, mto.length-1);
                mto = `swv@${mto}`;
                //执行指令
                return this.stySwvCall(swv, mto);
            }
            //直接返回
            return mto;
        },
        //模板写法：swv@size-key  执行转换：large --> xl
        stySwvToSizeKey(swv) {
            let sk = this.$ui.sizeStrToKey(swv);
            if (this.$is.empty(sk)) return swv;
            return sk;
        },
        //模板写法：swv@csv-val,color.*.m  执行查询：primary --> return this.$cssvar.color.primary.m
        //可以嵌套：swv@size-key@csv-val,size.btn.*  执行查询：large --> xl --> return this.$cssvar.size.btn.xl
        stySwvToCsvVal(swv, xpath) {
            xpath = this.stySwvToReplace(swv, xpath);
            let rtn = this.$cgy.loget(this.$cssvar, xpath);
            if (this.$is.undefined(rtn)) return '';
            return rtn;
        },

    }
}