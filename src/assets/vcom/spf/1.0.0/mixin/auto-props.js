/**
 * SPF-Vcom 组件库 auto-props 计算系统
 * 根据组件的状态数据，自动计算 组件内元素(子组件)的  样式参数 以及 props 透传参数
 * 
 * 通过:
 *      在 auto.atoms 中定义可用的 原子样式种类
 *      在 auto.element 中定义需要执行自动计算的 元素(子组件)
 *      在 auto.sub 中启用 样式子系统  size|color|border|animate  可自行扩展更多子系统
 *      在 auto.extra 中启用样式开关  可自行扩展更多样式开关
 *      在 auto.switch 中定义开关规则，监听组件的 $attrs|props|data|computed 中相关参数的值，
 *          确认这些监听的数据满足指定条件后，自动为 定义的元素(子组件)，计算对应的 props{}|class[]|style{}
 * 
 * 在模板中，只需要简单的绑定：
 * 
 *      元素：
 *      <div 
 *          :class="autoComputedStr.[elm].class" 
 *          :style="autoComputedStr.[elm].style"
 *      >...</div>
 * 
 *      子组件：
 *      <sub-comp 
 *          v-bind="autoComputed.[elm].props"
 *          :root-class="autoComputedStr.[elm].class"
 *          :root-style="autoComputedStr.[elm].style"
 *      >...</sub-comp>
 * 
 * 即可自动获取 响应式的 props{}|class[]|style{}
 * 
 */

export default {
    //!! 禁用根元素自动继承 $attrs
    inheritAttrs: false,

    props: {

        /**
         * 组件内部任意子元素可定义额外的 class[]|style{}|props{}
         * 命名方式 elmClass|elmStyle|elmProps 例如： rootClass|rootStyle 为 root 元素额外定义 class[]|style{}
         * 这些额外定义的 class[]|style{}|props{} 拥有最高优先级，将覆盖 默认的 和 动态计算的 class[]|style{}|props{}
         * !! 可以不定义，因为计算属性会尝试从 $attrs 中读取
         */
        //!! 默认定义 root 元素的 额外 class[]|style{} 因为任何组件都会包含 root 根元素
        rootClass: {
            type: [String, Array],
            default: ''
        },
        rootStyle: {
            type: [String, Object],
            default: ''
        },
        //!! 在根元素是 子组件的情况下，可以通过 rootProps 向其传递 props 参数
        rootProps: {
            type: Object,
            default: () => {
                return {}
            }
        },
        //可定义其他元素的 额外 class[]|style{}|props{}
        //elmClass: {},
        //elmStyle: {},
        //elmProps: {type: Object, default: ()=>{}}
        //...

        /**
         * auto-props 样式子系统
         * !! 子系统参数通常只影响 root 元素的 最终 class[]|style{}
         * !! 对于其他元素，可通过 自定义样式计算方法 根据子系统参数计算元素样式，
         * !! 或 通过 switch 系统 监听子系统参数值，动态修改元素的 class[]|style{}
         */

        /**
         * size 尺寸样式子系统
         * 可选:    mini | small | normal(默认) | medium | large
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
         * color 颜色子系统
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
         * !!不推荐  还可以输入 任意 css 颜色字符串：
         *      rgb() rgba() #fff ...
         */
        color: {
            type: String,
            default: ''
        },

        /**
         * border 边框样式子系统
         * 通过指定 bd-* bdc-* bd-po-* 原子类，调整边框样式
         */
        border: {
            type: [String, Array],
            default: ''
        },

        /**
         * animate 动画子系统
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
         * 其他样式开关系统
         * 处理 stretch|shape|effect|tightness 等样式参数
         * !! 必须在 auto.extra{} 中定义要启用的 其他样式开关，以及其初始(默认)值
         * !! 在组件内定义这些 props 时，其类型和默认值必须与 auto.extra{} 中一致
         * !! 也可以不定义这些 props，因为 计算属性 autoExtra 会尝试从 $attrs 中获取
         * 
         */
        /**
         * 示例开关： stretch 横向拉伸
         * 可选 auto|grow|row    在 $ui.cssvar.extra.size.stretchList 中定义
         * !! 监听方式 auto.switch.root['{{stretch!==""}}'] = '.{{auto.prefix}}-stretch stretch-{{stretch}}'
         */
        /*stretch: {
            type: String,
            default: 'auto'
        },*/
        /**
         * 示例开关：tightness 内容排布松紧
         * 可选 normal|loose|tight    在 $ui.cssvar.extra.size.tightnessList 中定义
         * !! 监听方式 auto.switch.root['{{tightness!==""}}'] = '.{{auto.prefix}}-tightness tightness-{{tightness}}'
         */
        /*tightness: {
            type: String,
            default: 'normal'
        },*/
        /**
         * 示例开关：shape 形状
         * 可选 sharp|round|pill|square|round-square|circle    在 $ui.cssvar.extra.size.shapeList 中定义
         * !! 监听方式 auto.switch.root['{{shape!==""}}'] = '.{{auto.prefix}}-shape shape-{{shape}}'
         */
        /*shape: {
            type: String,
            default: 'sharp'
        },*/
        /**
         * 示例开关：effect 背景|前景|边框 复合样式
         * 可选 normal|fill|plain|popout   在 $ui.cssvar.extra.size.effectList 中定义
         * !! 监听方式 auto.switch.root['{{effect!==""}}'] = '.{{auto.prefix}}-effect effect-{{effect}}'
         */
        /*effect: {
            type: String,
            default: 'normal'
        },*/
        /**
         * effect 样式相关的 status 状态
         * !! auto.switch.root['{{hoverable}}'] = '.{{auto.prefix}}-hoverable'
         * !! auto.switch.root['{{active}}'] = '.{{auto.prefix}}-active'
         * !! auto.switch.root['{{disabled}}'] = '.{{auto.prefix}}-disabled'
         */
        /*hoverable: {
            type: Boolean,
            default: false
        },
        active: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        },*/


    },
    data() {return {
        //auto-props 通用参数计算系统的配置参数
        auto: {
            //定义可以自动计算的数据类型 class|style|props 以及其对应的空值
            calcs: {
                class: [],
                style: {},
                props: {},
            },
            //支持直接传入 参数的 原子样式类 如： $attrs.bdPo = 't'  --> .bd-po-t
            atoms: 'fc,fs,fw,fml,pd,pdPo,mg,mgPo,bgc,bgcA,bd,bdc,bdPo,rd,rdPo,opa,shadow,flexX,flexY'.split(','),
            //自定需要自动计算 class[]|style{}|props{} 的组件内部元素
            element: {
                //root 根元素 是必须的
                root: {
                    class: '',  //初始 class[] 可以是 String|Array
                    style: '',  //初始 style{} 可以是 String|Object
                    props: {},  //初始 props{} 必须是 Object
                    //可以自定义此元素的 class[]|style{} 样式计算方法，不指定使用默认的 autoCalc[Elm]Class
                    calculator: {
                        class: 'autoCalcRootClass',      //默认方法名，也可以直接指定 function

                        /**
                         * 不指定计算方法则依次回退到默认方法：
                         *      autoCalc[Elm]Style()  >  autoCalcStyle(elm)  >  autoCalc(elm, 'style')
                         */
                        style: '',
                        props: '',
                    },
                    /**
                     * 定义此元素允许接收的  atom原子样式|sub子系统|extra样式开关  透传参数 props
                     * 定义形式：
                     *      accept.[atom|sub|extra] = true|false                    # 表示 全部接收|全部不接收
                     *      accept.[atom|sub|extra] = ['disabled', 'active']        # 表示 只接收指定的 参数
                     *      accept.[atom|sub|extra] = 'autoElmRoot[Atom|Sub|Extra]' # 表示 通过这个计算属性，生成透传参数 {}
                     * !! 如果不定义 accept 参数，表示全部不接收，相当于：
                     *      accept: {
                     *          atom: false,
                     *          sub: false,
                     *          extra: false
                     *      }
                     * !! root 根元素默认全部接收
                     * !! 其他元素 可在组件内部自行定义参数透传方式
                     */
                    accept: {
                        atom:   true,
                        sub:    true,
                        extra:  true
                    },

                },
                //其他需要自动计算的 元素 
                //forDev
                /*icon: {
                    class: '',
                    style: '',
                    props: {
                        //子组件的 初始 props 不变的内容
                        foo: 'bar',
                        bar: 123
                    },
                    accept: {
                        atom: true,
                        sub: false,
                        extra: true
                    },
                },*/
                //...
            },

            /**
             * 当前组件的 css 功能样式类 统一前缀
             * 例如：icon 组件的所有功能样式类统一前缀为：icon
             * 则有下列功能样式类：icon-primary icon-medium icon-red-hover ...
             * !! 引用组件内部必须覆盖
             */
            prefix: '',
    
            /**
             * 此组件的 size|color 参数 对应的在 $ui.cssvar.size|color 中的 键名
             * 例如：button 组件中，auto.csvk.size = btn
             * 表示从 $ui.cssvar.size.btn[...] 获取尺寸值
             * !! 引用组件内部必须覆盖
             */
            csvk: {
                size: '',
                color: '',
            },

            /**
             * 定义可用的 样式子系统
             * !! 引用组件内可覆盖或扩展
             * !! 如果扩展，必须定义对应的 props 以及至少定义计算属性 [subname]PropClsk 生成对应的样式类 class 片段字符串
             * 组件内可根据实际需要，选择开启子系统
             * 
             * !! 如果此处开启了某些子系统，则这些子系统参数会根据各元素的 accept.sub 参数 自动透传到子组件(元素)
             * !! 通过 auto.switch['?nemobj(autoSub) @* #props'] 配置项 处理透传
             * !! 组件内部可以关闭这个默认透传的 开关，然后自定义透传方法
             * !! 仅仅是传递 props 到子组件，具体如何接收 仍然由子组件内部自行处理
             */
            sub: {
                size: false,    //!! false 表示组件内全局关闭此子系统，所有元素将不会计算此子系统相关的 class[]|style{}
                color: false,
                border: false,
                animate: false,
            },

            /**
             * 定义支持的 额外的样式系统开关
             * !! 默认全部关闭，组件内部可选择性开启
             * 开启方式：
             *      0   hoverable: true             # 表示开启 hoverable 开关，Boolean 类型，初始值 false
             *      1   active: 'true'              # 表示开启 active 开关，Boolean 类型，初始值 true
             *      2   stretch: 'auto'             # 表示开启 stretch 开关，String 类型，初始值 'auto'
             *      3   shape: ''                   # 表示开启 shape 开关，String 类型，初始值 ''
             *      4   foo: []                     # 也可以使用其他类型的初始值
             * 
             * 开启后，系统将自动监听这些样式开关的值，在 非空值(或!==false) 时，向 root 元素增加 class[]
             *      Boolean 类型的开关值        --> 增加 prefix-[foo] 样式类
             *      String 类型的开关值         --> 增加 prefix-[foo] 和 [foo]-[foo-val] 样式类
             *      Array 类型的开关值 ['a', 'b', 1, false]     --> 增加：
             *          prefix-[foo] [foo]-a [foo]-b [foo]-1 样式类
             *      其他类型的开关值            --> 增加 prefix-[foo] 样式类
             * 
             * !! 支持通过 #.class-name 指定特殊的样式类名，
             * !! 也可以使用表达式 #.{{autoExtra.foo}}-custom 自定义样式类生成方式
             *      'foo #.custom-{{autoExtra.foo}} another-foo-class': 'bar'
             *      在 autoExtra.foo!=='' 时，将会生成 样式类：
             *          custom-bar another-foo-class 
             * 
             * !! 支持通过指定 #manual 标记，通知系统不自动生成此样式开关的 class[]，
             * !! 由用户自行在 auto.switch 中监听 autoExtra.[foo] 并执行相应操作
             * !! 用于更复杂的 样式类生成逻辑，或直接使用内联样式
             * 例如，在 auto.extra 中设置：
             *      'shape #manual': 'sharp'        # 不会自动生成 class[]
             * 还需要 在 auto.switch 中增加规则：
             *      'autoExtra.shape @root': '.{{auto.prefix}}-shape shape-{{autoExtra.shape}}'
             * 
             * !! 同时这些额外参数将 会根据各元素的 accept.extra 参数 自动透传到子组件(元素)
             * !! 通过 auto.switch['?nemobj(autoExtra) @* #props'] 配置项 处理透传
             * !! 组件内部可以关闭这个默认透传的 开关，然后自定义透传方法
             * !! 仅仅是传递 props 到子组件，具体怎样接收，仍然由子组件内部的 subComp.auto.extra 决定
             */
            extra: {
                //!! auto-props 系统默认的 extra 样式开关，组件内可自行扩展
                stretch:    false,      //可选 auto|grow|row    在 $ui.cssvar.extra.size.stretchList 中定义
                tightness:  false,      //可选 normal|loose|tight|none    在 $ui.cssvar.extra.size.tightnessList 中定义
                shape:      false,      //可选 sharp|round|pill|square|round-square|circle    在 $ui.cssvar.extra.size.shapeList 中定义
                effect:     false,      //可选 normal|fill|plain|popout   在 $ui.cssvar.extra.size.effectList 中定义
                hoverable:  false,      //Boolean
                active:     false,      //Boolean
                disabled:   false,      //Boolean

                //可扩展更多样式开关，在 auto.switch 中监听这些开关值即可，可以不在 props 中定义
                //...
            },

            /**
             * switch 样式开关系统
             * 基础用法：设置项键名是一个返回 Boolean 的 Mustache 表达式，键值是可以生成 class[]|style{}|props{} 的求值函数
             * 当 键名表达式 == true 时，向目标元素的 class[]|style{}|props{} 中插入 键值中生成的 class[]|style{}|props{}
             * 键值在定义时，可以使用 Mustache 表达式，最终将被解析为 value 求值函数
             * 
             * 例如：
             *      # 针对 root 元素
             *      root: {
             *          # 指定 class-string 以 . 开头的字符串
             *          '{{autoAtom.fs!==""}}': '.fs-{{autoAtom.fs}}',
             *          # 指定 class[]
             *          '{{autoAtom.fw!==""}}': ['fw-{{autoAtom.fw}}', '...'],
             * 
             *          # 直接指定 style-string 包含 : 和 ; 的字符串
             *          '{{autoAtom.bdc!=="" || autoAtom.bdPo!==""}}': 'border-width: {{$cssvar.size.bd[autoAtom.bd]}};',
             *          # 指定 style{}
             *          '{{autoAtom.bgc!==""}}': {
             *              backgroundColor: '{{$cssvar.color[autoAtom.bgc].m}}',
             *          },
             * 
             *          # 同一条 键名表达式，可以指定多个键值，在 键名开头 通过 #1 #2 ... 区分
             *          '#1 {{...}}': [... class ...],
             *          '#2 {{...}}': {... style ...},
             *      },
             *      # 其他元素
             *      others: {... switches ...},
             * 
             *      # 可以为多个元素添加同一个 Boolean 状态
             *      '{{!disabled}}': {
             *          root: {... switches ...},
             *          others: {... switches ...},
             *      }
             * 
             * 针对某个具体的 switch 开关参数，解析后的完整格式：
             *  {
             *      # 定义时的完整键名，可以是 {{Mustache}} 或 switch 专有的语法糖表达式
             *      key: '',
             * 
             *      # 定义时的完整键值 可以是 字符串|数组|对象
             *      val: '',
             * 
             *      # 当此开关仅监听某一个组件变量时，此处保存变量名
             *      variable: null,
             * 
             *      # 这个开关要处理的数据类型 class|style|props
             *      calc: 'class',
             * 
             *      # 这个开关要满足的条件，解析 key 表达式 得到的 求值函数，返回值一定是 Boolean
             *      !! 可以直接定义函数，参数为运行时的上下文环境组件实例 _this
             *      until: _this => {},
             * 
             *      # 当这个开关满足条件时，计算 class[]|style{} 的求值函数
             *      !! 通过 解析 开关设置的键值表达式 得到的求值函数
             *      !! 返回值根据 calc 决定是 Array 或 Object
             *      !! 需要 3 个参数：
             *      !!      _this   当前组件实例
             *      !!      _opt    这个开关解析后的完整参数数据
             *      !!      _elm    此函数执行时的所属元素名
             *      !! 可以直接定义函数
             *      value: _this => {},
             *  }
             * 
             * !! 可以直接通过定义 合法的 switch 参数对象 来 定义开关
             * 推荐使用 快捷语法：
             * 
             * 针对开关参数的 定义键名，有以下语法：
             *      0   标准的 Mustache 表达式，必须返回 Boolean，可以省略末尾的 类型声明 [Boolean]
             *          '{{foo.bar}} [Boolean]'                 --> 求值 this.foo.bar
             *          '{{$is.nemstr(foo.bar)}}'               --> 求值 this.$is.nemstr(this.foo.bar)
             *          '{{foo.bar && $is.nemarr(foo.jaz)}}'    --> 求值 this.foo.bar && this.$is.nemstr(this.foo.jaz)
             * 
             *      1   开关参数的键名，可以省略 Mustache 表达式头尾的 {{}} 
             *          !! 省略 {{}} 后不能再写 [Boolean] 类型声明
             *          'foo.bar'
             *          '$is.nemstr(foo.bar)'
             *          'foo.bar && $is.nemarr(foo.jaz)'
             * 
             *      2   可以为键名表达式增加修饰符 @... #...
             *          !! 修饰符 必须写在 {{}} 外面，或省略 {{}}
             *          !! 必须写在键名的最后  必须与主语句使用 空格 隔开
             *          !! 不同的修饰符之间也必须以 空格 隔开
             *          !! 不同的修饰符排列顺序为：  {{主语句}}  @...@...  #...
             * 
             *          @ 修饰符指定此开关将影响哪些元素的 样式计算
             *          '{{foo.bar}} @root@icon @btn'           --> 此开关作用于 root,icon,btn 3 个元素
             *          'foo.bar && $is.nemstr(atom.fs) @root'  --> 此开关仅作用于 root 元素
             *          !! 不指定作用元素，则根据此开关规则所在的 元素:{} 配置对象 来决定
             *          !! 如果此开关规则位于 auto.switch{} 下，则默认应用于 root 元素 
             *          !! 如果指定了 @* 则表示此条规则将作用于所有启用的元素 autoEnabledElm[]
             * 
             *          # 修饰符用于 区分相同的开关条件  或  指定此开关条件满足后，要修改的数据类型 class|style|props...
             *          '{{foo.bar}} #1'
             *          '{{foo.bar}} #2'
             *          'foo.bar #class'
             *          'foo.bar || foo.jaz #style'
             * 
             *          !! 使用修饰符时，可以不需要开关条件 只需要 true 单词即可，表示此开关一直生效
             *          'true #props'
             *          'true @root #style'
             *          'false #props'          # false 开头的开关没有意义，因为它永远不会生效
             *          !! 或者以某个元素开头
             *          'root #class'
             * 
             *      !! 所有 开关参数键名的特殊语法 都必须在省略 {{}} 的情况下生效
             * 
             *      3   可以简写 $is.***() 系列类型判断函数
             *          '?nemstr(foo.bar) && ?nemarr(foo.jaz) && !foo.tom'
             *          '?defined(foo.bar.key) || ?vue(subComp)'
             * 
             *      4   直接在键名中输入某个监听 props|data|computed 将根据此监听值的类型，决定返回的 Boolean 值
             *          'foo.bar'       --> 根据 this.foo.bar 的实际类型，决定生成的 求值函数：
             *                              Boolean     --> return this.foo.bar
             *                              String      --> return this.$is.nemstr(this.foo.bar)
             *                              Array       --> return this.$is.nemarr(this.foo.bar)
             *                              Object      --> return this.$is.nemobj(this.foo.bar)
             *          !! 键名表达式中不能出现多个 监听变量名，以及其他字符（如：运算符，圆括号，空格）
             *          !! 修饰符不受影响，可以使用
             * 
             *          !! 如果要合并多个监听变量，使用 $is.***() 函数简写方式
             *          '?(foo.bar)'                    --> 将根据 this.foo.bar 的实际类型，生成求值函数
             *          '?(foo.bar) || ?(foo.jaz)'      --> 可以合并多个监听变量
             *          
             * 针对开关参数的 定义键值，有下列语法：
             *      0   false 表示不启用这个开关，不会参与元素的 class[]|style{}|props{} 计算
             * 
             *      1   true 
             *          !! 仅针对 直接在键名中输入某个监听变量名 的情况
             *          'foo.bar': true  或  'foo.bar @root #1': true  
             *          表示：当监听变量的值符合要求时，自动为元素增加 prefix-foo-bar 系列类名
             *              如果 foo.bar 值类型为 Boolean 且 foo.bar === true 时：
             *                  为元素增加 prefix-foo-bar 样式类  foo.bar --> foo-bar  (foo.barJaz --> foo-bar-jaz)
             *              如果 foo.bar 值类型为 String 且 不为 '' 时：
             *                  为元素增加 prefix-foo-bar 和 foo-bar-{{foo.bar}} 样式类
             *              如果 foo.bar 值为其他类型 且 满足不为空的 要求时：
             *                  为元素增加 prefix-foo-bar 样式类
             * 
             *      2   数组形式 [ 'class-a', 'class-b', 'class-{{Mustache}}' ]
             *          或者  类型声明为 [Array] 的 pureMustache 表达式
             *          或者  以 '.' 开头的 String 字符串 '.class-a class-b class-{{Mustache}}'
             *          或者 在键名中明确标记 #class
             *          表示 在满足条件时，为元素增加这些定义的 样式类
             * 
             *      3   标准 CSS-Object { width: '24px', backgroundColor: '{{Mustache}}', ... }
             *          或者  类型声明为 [Object] 的 pureMustache 表达式
             *          或者  标准 CSS-String  'width: 24px; background-color: {{Mustache}}; '  
             *          !! CSS-String 形式 不能省略 ; 分号
             *          或者 在键名中明确标记 #style
             *          表示 在满足条件时，为元素增加这些定义的 具体样式值
             * 
             *      4   完整的开关参数对象 { calc: 'class', value: function(){}, ... }
             *          !! calc,value 参数必须指定
             *          表示 直接定义开关参数
             * 
             *      5   键名中明确标记 #props ，且键值是 {} 或 类型声明为 [Object] 表达式时，作为 props 处理
             *          表示 在满足条件时，向元素(子组件)传递这些 props 
             *          !! 在 props 配置块{} 内部也可以使用 Mustache 语句，但必须写完整的 {{...}}
             *          通常的写法：
             *              # 表示作用于 icon 元素(子组件)的 动态 props
             *              'icon #props': {
             *                  # 静态值，通常写在 auto.element.elm.props{} 中
             *                  foo: 'bar',
             * 
             *                  # 键值为表达式  返回任意支持类型
             *                  !! 键值表达式如果要返回 非 String 类型的值，必须声明类型
             *                  bar: 'foo-{{bar}}'  或  '{{bar}} [Array任意支持类型]',
             *                  # 带表达式的 []|{} 值
             *                  jaz: ['foo', '{{bar}} [可以是任意类型]', ...],
             *                  tom: {
             *                      foo: 'with-{{foo}}',
             *                      bar: '{{bar}} [任意类型]',
             *                      ...
             *                  },
             * 
             *                  # 键名可带表达式，实际键名写在 # 后
             *                  '{{exp-a}} [Boolean可省略] #jry': '静态值'  或  '返回任意支持类型的表达式'  或  带表达式的 []{},
             * 
             *                  # 键名带表达式，但是未指定实际键名，根据键名表达式，决定是否合并到最终结果 {} 中
             *                  !! 这种情况，键值只能是 {}
             *                  '{{exp-b}}': {
             *                      foo: 'bar-new',
             *                      bar: '{{bar}} [Array]',
             *                      tom: {
             *                          foo: 'with-{{foo}}-new',
             *                      },
             *                      '{{exp-c}} #jry': {
             *                          ... 继续递归 ...
             *                      },
             *                  },
             *              }  
             *      
             * 
             * 
             */
            switch: {

                /**
                 * 原子样式相关
                 */
                /**
                 * 原子样式参数透传
                 * !! 根据 auto.element.[elm].accept.atom 中定义的 各元素的 原子样式参数透传方式
                 * !! 生成要透传给 各元素(子组件)的 props{} 参数
                 */
                '?nemobj(autoAtom) @* #props': '{{ $is.nemobj(autoElmAtomProps[$args[1]]) ? autoElmAtomProps[$args[1]] : {} }} [Object]',
                /**
                 * 原子样式 转为 root 元素的 class[]
                 * !! 默认情况下，原子样式参数仅对 root 元素生效，生成 class[]
                 * !! 可在组件内部将此条规则设为 '?nemobj(autoAtom) @root': false 以关闭
                 */
                '?nemobj(autoAtom) @root #class': '{{autoAtomClass}} [Array]',


                /**
                 * 子系统相关
                 */
                /**
                 * 子系统参数透传
                 * !! 根据 auto.element.[elm].accept.sub 中定义的 各元素的 子系统参数透传方式
                 * !! 生成要透传给 各元素(子组件)的 props{} 参数
                 */
                '?nemobj(autoSub) @* #props': '{{ $is.nemobj(autoElmSubProps[$args[1]]) ? autoElmSubProps[$args[1]] : {} }} [Object]',
                /**
                 * 子系统参数转为 root 元素的 class[]
                 * !! 默认情况下，所有子系统参数转为 class[] 仅对 root 元素生效
                 * !! 可在组件内部将此条规则设为 '?nemarr(autoEnabledSub) @root': false 以关闭
                 */
                '?nemarr(autoEnabledSub) @root #class': '{{autoSubClass}} [Array]',


                /**
                 * 其他样式开关系统
                 */
                /**
                 * 其他样式开关系统 参数透传
                 * !! 根据 auto.element.[elm].accept.extra 中定义的 各元素的 样式系统参数透传方式
                 * !! 生成要透传给 各元素(子组件)的 props{} 参数
                 */
                '?nemobj(autoExtra) @* #props': '{{ $is.nemobj(autoElmExtraProps[$args[1]]) ? autoElmExtraProps[$args[1]] : {} }} [Object]',
                /**
                 * 其他样式开关参数转为 root 元素的 class[]
                 * !! 默认情况下，所有额外样式开关参数转为 class[] 仅对 root 元素生效
                 * !! 可在组件内部将此条规则设为 '?nemarr(autoEnabledExtra) @root': false 以关闭
                 */
                '?nemobj(autoEnabledExtra) @root #class': '{{autoExtraClass}} [Array]',


                

                
                //forDev:  Number 类型测试  不指定元素 则默认作用于 root 元素
                //'?(num)': 'min-width: {{num}}px;',

                //针对 root 元素
                /*root: {

                    //forDev:  嵌套规则 测试
                    'disabled': {
                        'autoAtom.fml': '.fml-{{autoAtom.fml}}',
                        'autoAtom.pd':   '.pd-{{autoAtom.pd}}',
                        'autoAtom.pdPo': '.pd-po-{{autoAtom.pdPo}}',

                        'active': {
                            'autoAtom.bgc':  '.bgc-{{autoAtom.bgc}}',
                            'autoAtom.bgcA': '.bgc-a-{{autoAtom.bgcA}}',
                        },
                    },
                    
                },*/

                //针对其他元素...

                //forDev:  不设置条件的情况下 传递 props 给子组件
                /*'icon #props': {
                    foo: true,
                    '{{auto.sub.size}} #size': '{{size}} [String]',
                    //条件合并
                    '{{auto.sub.size}}': {
                        bar: 'icon-{{size}}',
                        jaz: ['foo-bar', 'bar-{{size}}'],
                    },
                },*/
            },
        },

        //number test
        num: 0,
    }},
    computed: {
        //快捷访问 this.$ui.cssvar
        $cssvar() {
            return this.$ui.cssvar || {};
        },

        /**
         * 从 $attrs 中筛选可能存在的 atom 原子样式参数  返回 {}
         * 可用的原子样式包括：
         *    .fc-*|.fs-*|.fw-*|.fml-*
         *    .pd-*|.mg-*|.pd-po-*|.mg-po-*
         *    .bgc-*|.bgc-a*
         *    .bd-*|.bd-po-*|.bdc-*|.rd-*|.rd-po-*
         *    .opa-*|.shadow-*
         *    .flex-x-*|.flex-y-*
         * 返回 原子样式参数 如果未定义则默认 '' 空值：
         *  {
         *      fs: '',
         *      fw: '',
         *      bd: 'm',
         *      bdPo: 't',
         *      ...
         *  }
         */
        autoAllAtom() {
            let is = this.$is,
                iss = s => is.nemstr(s),
                //支持的 原子样式 参数列表
                atoms = this.auto.atoms,
                rtn = {};
            this.$each(atoms, (atom,i) => {
                let d = this.autoGetInputProps(atom);
                if (is.nemstr(d)) {
                    rtn[atom] = d;
                } else {
                    //默认的原子样式为 ''
                    rtn[atom] = '';
                }
            });
            //如果 border 子系统有参数
            if (iss(this.border)) {
                rtn = Object.assign(rtn, this.borderPropVal);
            }
            return rtn;
        },
        //从 autoAllAtom 中提取 不为空值的 原子样式参数
        autoAtom() {
            let is = this.$is,
                atom = this.autoAllAtom || {},
                rtn = {};
            this.$each(atom, (v,k) => {
                if (is.nemstr(v)) rtn[k] = v;
            });
            return rtn;
        },
        //根据 不为空值的 原子样式参数 生成对应的 class[]  通常应用到 root 根元素
        autoAtomClass() {
            let is = this.$is,
                atoms = this.autoAtom || {},
                rtn = [];
            if (!is.nemobj(atoms)) return rtn;
            this.$each(atoms, (atv,atom)=>{
                rtn.push(`${atom.toSnakeCase('-')}-${atv.toSnakeCase('-')}`);
            });
            return rtn;
        },
        /**
         * 生成要透传给各元素的 原子样式 props 参数
         * 各元素应在 auto.element.elm.accept.atom 中指定 接收原子样式参数透传的 方式
         * !! 如果定义了通过计算属性生成透传参数，还应定义对应的计算属性
         */
        autoElmAtomProps() {
            let atoms = this.autoAtom || {};
            return this.autoAcceptProps('atom', atoms);
        },


        /**
         * 获取当前需要自动计算样式的元素 []
         * !! 将收集 auto.element 中定义的元素
         */
        autoEnabledElm() {
            let is = this.$is,
                iso = o => is.nemobj(o),
                elmd = this.auto.element || {},
                elms = [];
            this.$each(elmd, (elmc,elm) => {
                //!! 定义元素必须是 {} 且必须至少定义 class|style|props 中的一个
                if (!iso(elmc) || !(is.defined(elmc.class) || is.defined(elmc.style) || is.defined(elmc.props))) return true;
                elms.push(elm);
            });
            return elms;
        },

        /**
         * 解析 组件内部定义的 和 外部传入的 元素的 class[]|style{}|props{}
         * 返回值: 
         *  {
         *      root: {class: []. style: {}, props: {}},
         *      elm: {...},
         *      ...
         *  }
         * 
         * !! 如果 auto.element 中指定了其他要计算的元素，例如 foo
         * !!       则外部传入的 class|style|props 的 prop 名称必须是  fooClass|fooStyle|fooProps
         * !!       可以不定义这些 props，会尝试从 $attrs 中查找
         */
        //初始定义的 
        autoInit() {
            let is = this.$is,
                ext = this.$extend,
                //class|style|props 结构以及空值
                calcs = this.auto.calcs,
                elms = this.autoEnabledElm || [],
                init = this.auto.element,
                rtn = {};
            //如果没有定义任何需要计算样式的元素，直接返回 {}
            if (!is.nemarr(elms)) return rtn;
            this.$each(elms, (el,i) => {
                //定义结构
                rtn[el] = ext({}, calcs);
                this.$each(rtn[el], (i,calc)=>{
                    let d = init[el][calc] || null;
                    if (is.null(d) || is.empty(d)) return true;
                    //合并
                    rtn[el][calc] = this.autoExtendRtn(rtn[el][calc], d, calc);
                });
            });
            return rtn;
        },
        //外部传入的
        autoInput() {
            let is = this.$is,
                ext = this.$extend,
                //class|style|props 结构以及空值
                calcs = this.auto.calcs,
                inits = this.autoInit,
                //在 this[] 以及 this.$attrs[] 中查找目标 未找到返回 undefined
                find = (e,t='class') => {
                    let k = `${e}${t.ucfirst()}`;
                    //return is.defined(this[k]) ? this[k] : (is.defined(this.$attrs[k]) ? this.$attrs[k] : null);
                    return this.autoGetInputProps(k);
                },
                rtn = {};
            //未定义任何元素
            if (!is.nemobj(inits)) return rtn;
            this.$each(inits, (init, elm)=>{
                //定义结构
                rtn[elm] = ext({}, calcs);
                this.$each(rtn[elm], (v,calc)=>{
                    let d = find(elm,calc);
                    //if (calc==='props' && elm==='icon') console.log(calc, d, this.$attrs['icon-props']);
                    if (is.undefined(d)) return true;
                    //合并
                    rtn[elm][calc] = this.autoExtendRtn(rtn[elm][calc], d, calc);
                });
            });
            return rtn;
        },

        /**
         * 样式|props 计算核心方法
         * 返回计算后的 class[] style{} props{}
         *  {
         *      root: {class:[], style:{}, props:{}},
         *      elm: {...},
         *      ...
         *  }
         */
        autoComputed() {
            let is = this.$is,
                isfn = fn => (is.nemstr(fn) && is.function(this[fn])) || is.function(fn),
                ext = this.$extend,
                //class|style|props 结构以及空值
                calcs = this.auto.calcs,
                //init 初始数据
                inits = this.autoInit,
                //外部传入的数据
                input = this.autoInput,
                rtn = {};
            //为定义任何元素
            if (!is.nemobj(inits)) return rtn;
            this.$each(inits, (init, elm) => {
                //定义结构
                rtn[elm] = ext({}, calcs);
                this.$each(rtn[elm], (i, calc) => {
                    //依次查询存在的 自定义计算方法
                    let fn = this.$cgy.loget(this.auto.element, `${elm}.calculator.${calc}`, null);
                    if (is.function(fn)) {
                        //直接定义了 function
                        fn = fn.bind(this);
                    } else if (isfn(fn)) {
                        //定义了 this[fn] 计算方法
                        fn = this[fn].bind(this);
                    } else {
                        //未定义 或 空字符串 表示使用默认方法名  依次回退
                        fn = this.autoCalculatorFallback(elm, calc);
                    }
                    //执行计算  失败则使用空值
                    let res = is.function(fn) ? fn() : null;    //ext({}, calcs[calc]);
                    //合并
                    rtn[elm][calc] = this.autoExtendRtn(res, input[elm][calc], calc);
                });
            });
            return rtn;
        },
        //class[]|style{} 转为字符串，供模板调用
        autoComputedStr() {
            let is = this.$is,
                calced = this.autoComputed,
                rtn = {};
            this.$each(calced, (ci, elm) => {
                //定义结构
                rtn[elm] = {
                    class: '',
                    style: ''
                };
                this.$each(rtn[elm], (i,calc) => {
                    if (calc==='props') return true;
                    let elci = ci[calc];
                    switch (calc) {
                        case 'class':
                            if (is.nemarr(elci)) {
                                rtn[elm].class = elci.unique().join(' ');
                            }
                            break;
                        case 'style':
                            if (is.nemobj(elci)) {
                                rtn[elm].style = this.$cgy.toCssString(elci);
                            }
                            break;
                    }
                });
            });
            return rtn;
        },


        //全局开启的子系统，元素要启用某个子系统，必须在全局启用此子系统
        autoEnabledSub() {
            let is = this.$is,
                sub = this.auto.sub || {},
                rtn = [];
            if (!is.nemobj(sub)) return [];
            this.$each(sub, (sube, subn) => {
                if (sube!==true) return true;
                rtn.push(subn);
            });
            return rtn;
        },
        //根据启用的子系统，生成当前的左右子系统的 参数{}
        autoSub() {
            let is = this.$is,
                subs = this.autoEnabledSub || [],
                rtn = {};
            if (!is.nemarr(subs)) return rtn;
            this.$each(subs,(sub,i)=>{
                switch (sub) {
                    case 'size':
                    case 'border':
                        rtn[sub] = this[sub];
                        break;
                    case 'color':
                        rtn.type = this.type;
                        rtn.color = this.color;
                        break;
                    case 'animate':
                        rtn.animateType = this.animateType;
                        rtn.animateClass = this.animateClass;
                        rtn.animateInfinite = this.animateInfinite;
                        break;
                }
            });
            return rtn;
        },
        //根据启用的子系统 生成 class[]  通常应用到 root 根元素
        autoSubClass() {
            let is = this.$is,
                //所有启用的子系统
                subs = this.autoEnabledSub || [],
                //待输出的 class[]
                rtn = [];
            if (!is.nemarr(subs)) return rtn;
            this.$each(subs, (sub, i)=>{
                //子系统对应的 计算属性名称
                let ck = `${sub}PropClsk`;
                //跳过未定义对应 计算属性 或 未能返回有效值的 子系统
                if (!is.defined(this[ck]) || !is.nemstr(this[ck])) return true;
                rtn.push(this[ck]);
            });
            return rtn;
        },
        /**
         * 生成要透传给各元素的 子系统 props 参数
         * 各元素应在 auto.element.elm.accept.sub 中指定 接收子系统参数透传的 方式
         * !! 如果定义了通过计算属性生成透传参数，还应定义对应的计算属性
         */
        autoElmSubProps() {
            let subs = this.autoSub || {};
            return this.autoAcceptProps('sub', subs);
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
            let csvk = this.auto.csvk.size;
            if (csvk==='') csvk = null;
            return this.$ui.sizeVal(this.size, csvk);
        },
        /**
         * 将传入的尺寸参数转为可拼接 class 样式类的字符
         * 根据 sizePropType 来决定：
         *      str     --> sizeStrToKey    --> xl|m|...
         *      key     --> size            --> xl|m|...
         *      csv     --> sizePropVal     --> 仅返回 px 为单位的尺寸值
         *      css     --> sizePropVal     --> 仅返回 px 为单位的尺寸值
         *      num     --> sizePropVal     --> 自动添加 px 单位
         * 返回的字符串，拼接 auto.prefix 即可得到 尺寸样式类
         */
        sizePropClsk() {
            let is = this.$is,
                ptp = this.sizePropType,
                sz = this.size;
            if ('str,key,num'.split(',').includes(ptp)) {
                //传入 str(medium) | key(xl) | num(100) 形式的 size 时 直接转为 size-class
                //key(xl) 转为 str(large) 
                if (ptp === 'str') sz = this.sizeStrToKey;
                //num(100) 转为 100px
                if (ptp === 'num') sz += 'px';
            } else if (ptp === 'csv') {
                //传入 btn.s 形式的 size 返回实际的 px 尺寸
                sz = this.sizePropVal;
            } else if (ptp === 'css' && sz.slice(-2) === 'px') {
                //传入 100px 形式时 也会转为 size-class 例如 icon-100px
                //sz = sz;
            } else {
                //其他形式 返回 null
                sz = null;
            }
            //自动拼接 prefix
            return is.nemstr(sz) ? this.autoClsnAutoPrefix(sz) : null;
        },


        /**
         * color 子系统
         */
        /**
         * 根据 type|color 参数，获取最终的 颜色参数值
         * color 覆盖 type
         */
        colorProp() {
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
         *      m|l2|d3                 key     指向 $ui.cssvar.color[auto.csvk.color][***]
         *      red|blue-l2|black       key     $ui.cssvar.color 中的键形式
         *      red.m|fc.l2             key     同上
         *      rgba()|#fff             css     有效的 css 字符串形式
         * !! 组件内部不要修改
         */
        colorPropType() {
            let is = this.$is,
                //传入的实际 颜色参数值
                cr = this.colorProp,
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
            } else if (cr==='m' || (cr.length===2 && (cr.startsWith('l') || cr.startsWith('d')))) {
                //传入 m|l2|d3 形式
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
        colorPropKey() {
            let is = this.$is,
                cr = this.colorProp,
                tp = this.colorPropType,
                csvk = this.auto.csvk.color ?? '';
            if ('str,key'.split(',').includes(tp) !== true) return '';
            if (cr.includes('.'))  return cr.split('.').join('-');
            if (cr.includes('-')) return cr;
            if (cr==='m' || (cr.length===2 && (cr.startsWith('l') || cr.startsWith('d')))) {
                if (csvk==='') return '';
                return `${csvk}-${cr}`;
            }
            return `${cr}-m`;
        },
        //获取实际的 颜色值 hex|rgb|hsl 形式的 颜色值字符串
        colorPropVal() {
            let tp = this.colorPropType,
                ck = this.colorPropKey;
            //直接传入了颜色值
            if (tp === 'css') return this.colorProp;
            //传入了无效 color
            if (ck==='') return '';
            //传入了 颜色名|键
            ck = ck.replace('-','.');
            return this.$cgy.loget(this.$ui.cssvar.color, ck);
        },
        //返回可用于拼接 class 样式类的字符串
        colorPropClsk() {
            let is= this.$is,
                ctp = this.colorPropType,
                cr = this.colorPropVal;
            if ('str,key'.split(',').includes(ctp)) {
                //str(primary) 或 key(red|blue-l2|yellow.d1|m|d2) 形式的 颜色参数 直接转为 color-class
                cr = this.colorPropKey;
            }
            //自动拼接
            return is.nemstr(cr) ? this.autoClsnAutoPrefix(cr) : null;
        },


        /**
         * border 子系统
         */
        /**
         * 根据传入的 border 参数，生成 边框参数 {bd:'..', bdPo:'..', bdc: '..'}
         * border 参数形式： 'bd-m bd-po-tb bdc-d3'
         */
        borderPropVal() {
            let is = this.$is,
                bd = this.border,
                rtn = {
                    //默认边框样式
                    bd: 'm',
                    bdPo: 'all',
                    bdc: 'm',
                };
            if (!is.nemstr(bd)) return {bd:'', bdPo:'', bdc:''};
            //传入的 class 列表转为 []
            let bdcls = this.$cgy.toClassArr(bd);
            this.$each(bdcls, (clsi,i) => {
                if (!is.nemstr(clsi) || !clsi.startsWith('bd')) return true;
                if (clsi.startsWith('bdc-')) {
                    rtn.bdc = clsi.substring(4);
                    return true;
                }
                if (clsi.startsWith('bd-po-')) {
                    rtn.bdPo = clsi.substring(6);
                    return true;
                }
                if (clsi.startsWith('bd-')) {
                    rtn.bd = clsi.substring(3);
                    return true;
                }
            });
            return rtn;
        },
        //返回可直接拼接 class 类名的 字符串
        borderPropClsk() {
            let is = this.$is,
                bdo = this.borderPropVal || {},
                cls = [];
            if (!is.nemobj(bdo)) return '';
            this.$each(bdo, (v,k) => {
                if (!is.nemstr(v)) return true;
                cls.push(`${k.toSnakeCase()}-${v}`);
            });
            return is.nemarr(cls) ? cls.join(' ') : '';
        },
        

        /**
         * animate 子系统
         */
        //计算当前的 animate class[]
        animatePropVal() {
            let is = this.$is,
                ani = this.animateType,
                inf = this.animateInfinite,
                ics = this.animateClass,
                rtn = ['animate__animated'];
            if (is.nemstr(ics) || is.nemarr(ics)) {
                //传入了完整的 animate 类名序列
                if (is.string(ics)) ics = this.$cgy.toClassArr(ics);    //ics.replace(new RegExp('\\s+','g'), ' ').split(' ');
                //合并
                rtn.push(...ics);
            } else if (is.nemstr(ani)) {
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
        //返回可直接拼接 class 类名的 字符串
        animatePropClsk() {
            let is = this.$is,
                clss = this.animatePropVal || [];
            if (!is.nemarr(clss)) return '';
            return clss.unique().join(' ');
        },


        /**
         * extra 额外样式系统参数
         */
        /**
         * 根据 auto.extra 定义启用的 额外样式系统开关参数，返回标准格式：
         *  {
         *      foo: {
         *          # 监听值
         *          watch: 'foo',
         *          # 类型
         *          type: 'boolean|string|array|plainObject|realNumber',
         *          # 默认值
         *          default: false,
         *          # 满足条件时，生成 class[] 的函数 一定返回 []
         *          !! 仅当使用 #.*** 自定义样式类生成表达式时，生成
         *          class: (_this, _opt={}, _ev=null) => {},
         *          # 是否手动处理，不自动生成 class[]
         *          manual: false,
         *      }
         *  }
         */
        autoEnabledExtra() {
            let is = this.$is,
                extra = this.auto.extra || {},
                rtn = {};
            if (!is.nemobj(extra)) return rtn;
            this.$each(extra, (eon, exn) => {
                //跳过未启用的
                if (eon===false) return true;
                //创建标准格式
                let rtni = {
                    watch: '',
                    type: '',
                    default: null,
                    class: null,
                    manual: false
                };

                //处理键名
                if (!exn.includes('#')) {
                    //正常定义
                    rtni.watch = exn.trim();
                    //自动生成 class[] 不需要定义 求值函数
                    rtni.class = null;
                } else {
                    //存在 # 标记
                    if (exn.includes('#manual')) {
                        //存在 #manual 手动标记
                        rtni.watch = exn.split('#')[0].trim();
                        rtni.manual = true;
                    } else if (exn.includes('#.')) {
                        //存在 自定义类名标记
                        let cla = exn.split('#.');
                        rtni.watch = cla[0].trim();
                        //自定义的 class[]
                        cla = this.$cgy.toClassArr(cla[1].trim());
                        //将其中可能存在的 Mustache 表达式转为求值函数
                        cla = cla.map(i=>{
                            if (this.$mustache.isMustache(i)) {
                                return this.$mustache.defineGetter(i, [String]);
                            }
                            return i;
                        })
                        //生成 样式开关 class[] 生成函数
                        rtni.class = (_this, _opt={}, _ev=null) => {
                            return cla.map(i=>{
                                if (_this.$mustache.isMustacheFn(i)) {
                                    return i(_this, _opt, _ev);
                                }
                                return i;
                            });
                        }
                    } else {
                        //其他情况 跳过 
                        return true;
                    }
                }

                //处理键值
                //支持的默认值类型
                let tps = 'realNumber,string,boolean,array,plainObject'.split(','),
                    tp = null;
                for (let tpi of tps) {
                    if (!is.function(is[tpi])) continue;
                    if (!is[tpi](eon)) continue;
                    if (tpi==='string' && ['true','false'].includes(eon)) {
                        rtni.default = eon==='true';
                        tp = 'boolean';
                        break;
                    } else if (tpi==='boolean') {
                        rtni.default = eon!==true;
                        tp = 'boolean';
                        break;
                    } else {
                        rtni.default = eon;
                        tp = tpi;
                        break;
                    }
                }
                //未匹配到合法类型
                if (!is.nemstr(tp)) return true;
                rtni.type = tp;

                //保存
                rtn[rtni.watch] = rtni;
            });
            return rtn;
        },
        //从 props|$attrs 中尝试获取 额外样式开关参数当前的实际值
        autoExtra() {
            let is = this.$is,
                extras = this.autoEnabledExtra || {},
                rtn = {};
            if (!is.nemobj(extras)) return {};
            this.$each(extras, (exc,exn) => {
                let ek = exc.watch,
                    etp = exc.type,
                    dft = exc.default,
                    istp = v => is[etp](v),
                    //ev = is.defined(this[ek]) ? this[ek] : (is.defined(this.$attrs[ek]) ? this.$attrs[ek] : undefined);
                    ev = this.autoGetInputProps(ek);

                //正常获取到传入值
                if (istp(ev)) {
                    rtn[exn] = ev;
                    return true;
                }

                //未定义 则使用默认值
                if (is.undefined(ev)) {
                    rtn[exn] = dft;
                } else {
                    //处理 etp==boolean 且 传入 '' 的情况 <comp-name foo></comp-name> 的形式传入 true
                    if (etp==='boolean' && ev==='') {
                        rtn[exn] = true;
                    } else {
                        rtn[exn] = dft;
                    }
                }
            });
            return rtn;
        },
        //根据启用的样式开关参数 生成 class[]  通常应用到 root 根元素
        autoExtraClass() {
            let is = this.$is,
                excs = this.autoEnabledExtra || {},
                extras = this.autoExtra || {},
                rtn = [];
            if (!is.nemobj(extras)) return [];
            this.$each(extras, (ev,exn)=>{
                let exc = excs[exn];
                if (!is.nemobj(exc)) return true;

                //空值检查
                let etp = exc.type;
                if (!is[etp](ev)) return true;
                //判断非空值 false|''|0|[]|{}
                let em = false;
                switch (etp) {
                    case 'realNumber':  em = ev===0;        break;
                    case 'boolean':     em = ev!==true;     break;
                    default:            em = is.empty(ev);  break;
                }
                //空值不生成 class[]
                if (em===true) return true;

                //#manual 标记的 样式开关，不自动生成
                if (exc.manual===true) return true;

                //通过 #.*** 定义了特殊 class[] 生成方式的
                if (is.function(exc.class)) {
                    let rtni = exc.class(this, exc, ev);
                    if (is.nemarr(rtni)) rtn = this.$cgy.mergeClassArr(rtn, rtni);
                    return true;
                }
                
                //默认自动生成 class[]
                //生成 prefix-exn
                rtn.push(this.autoClsnAutoPrefix(exn));
                //针对 string|realNumber
                if (etp==='string') rtn.push(`${exn.toSnakeCase('-')}-${ev.toSnakeCase('-')}`);
                if (etp==='realNumber') rtn.push(`${exn.toSnakeCase('-')}-${ev}`);
                //针对 array 类型值中的 string|realNumber 值
                if (etp==='array') {
                    for (let evi of ev) {
                        if (!is.nemstr(evi) || !is.realNumber(evi)) continue;
                        if (is.nemstr(evi)) {
                            rtn.push(`${exn.toSnakeCase('-')}-${evi.toSnakeCase('-')}`);
                        } else if (is.realNumber(evi)) {
                            rtn.push(`${exn.toSnakeCase('-')}-${evi}`);
                        }
                    }
                }
            });
            return rtn;
        },
        /**
         * 生成要透传给各元素的 样式开关系统 props 参数
         * 各元素应在 auto.element.elm.accept.extra 中指定 接收样式开关系统参数透传的 方式
         * !! 如果定义了通过计算属性生成透传参数，还应定义对应的计算属性
         */
        autoElmExtraProps() {
            let extras = this.autoExtra || {};
            return this.autoAcceptProps('extra', extras);
        },


        /**
         * switch 样式开关系统
         */
        /**
         * 收集所有元素的 定义的 样式开关，返回处理后的 完整的 开关参数
         * 开关参数形式 见 auto.switch 注释
         * !! 计算属性将缓存解析结果，运行时 auto.switch 配置参数只要不变，就不会重复解析
         * 解析结果数据结构：
         *  {
         *      root: {
         *          'foo.bar @root #1': {
         *              key: 'foo.bar @root #1',
         *              val: true,
         *              variable: 'foo.bar',
         *              calc: 'class',
         *              until: _this => {状态条件求值，返回 Boolean},
         *              value: _this => {计算最终要合并的 root 元素的 class[]，返回 Array }
         *          },
         *          '另一条 switch 开关': { ... 开关参数 ... },
         *          ...
         *      },
         * 
         *      其他元素 ...
         *  }
         */
        autoElmSwitch() {
            let is = this.$is,
                sws = this.auto.switch,
                elms = this.autoEnabledElm || [];
            if (!is.nemarr(elms)) return {};
            return this.autoParseSwitchRecursive(sws);
        },


        /**
         * 输出当前组件 auto-props 系统的所有有效参数
         * 通常用于向 slot 输出当前组件的参数数据
         */
        autoParams() {
            let is = this.$is;
            return {
                atom: is.nemobj(this.autoAtom) ? this.autoAtom : {},
                sub: is.nemobj(this.autoSub) ? this.autoSub : {},
                extra: is.nemobj(this.autoExtra) ? this.autoExtra : {},
                computed: is.nemobj(this.autoComputed) ? this.autoComputed : {}
            };
        },
    },
    methods: {

        /**
         * !! 核心方法 计算元素的样式 返回此元素的 根据 switch 开关状态自动生成的 class[]|style{} 
         * @param {String} elm 要计算的元素
         * @param {String} calc 要计算样式类型 class|style
         * @return {Array|Object} 根据 calc 决定返回 class[]|style{}
         */
        autoCalc(elm='root', calc='class') {
            let is = this.$is,
                //计算类型 以及其空值
                calcs = this.auto.calcs,
                elms = this.autoEnabledElm || [],
                //预编译的 元素开关规则对象
                elsw = this.autoElmSwitch || {},
                //合并结果
                ext = this.autoExtendRtn,
                rtn = is.array(calcs[calc]) ? [] : {};
            //计算类型无效，返回 null
            if (!is.defined(calcs[calc])) return null;
            //元素不存在，或此元素没有定义开关规则，返回空数据
            if (!is.nemstr(elm) || !elms.includes(elm) || !is.nemobj(elsw[elm])) return rtn;

            //!! 合并初始值
            let inits = this.autoInit || {},
                init = inits[elm][calc] || calcs[calc];
            rtn = ext(rtn, init, calc);

            //遍历元素的开关规则，依次执行
            this.$each(elsw[elm], (sw, swk) => {
                //跳过不是 有效的已解析的 元素开关规则
                if (!this.autoIsSwObj(sw)) return true;
                //只执行 calc 类型的计算
                if (sw.calc!==calc) return true;
                //执行 until 条件判断
                if (sw.until(this)===true) {
                    /**
                     * 条件满足时，执行 value 函数，计算最终的 []|{}
                     * !! 需要传入 3 个参数
                     * !!   _this   当前组件实例
                     * !!   _opt    这个开关解析后的完整参数数据
                     * !!   _elm    此函数执行时的所属元素名
                     */
                    let value = sw.value(this, sw, elm);
                    rtn = ext(rtn, value, calc);
                }
            });
            return rtn;
        },


        /**
         * switch 解析
         */
        /**
         * 解析某一条 switch 开关规则
         * !! 如果未传入默认指向的 元素，则键名表达式中必须包含 @元素 修饰符，否则此条规则无效
         * 
         * @param {String} key 开关规则键名表达式
         * @param {Boolean|String|Array|Object} val 开关规则的键值定义
         * @param {String|Array} elm 此开关规则默认指向的 元素名，可以有多个
         * @param {Function} preUntil 此条规则只有在此函数返回 true 时才起效，需要合并到 此开关规则的 value 求值函数中
         * @param {String} preKey 如果存在 preUntil 则为此条开关规则的 key 增加一个前缀，用于区分同一元素下的不同 开关
         * @return {Object} 返回数据：
         *  {
         *      elm: { ... 开关参数对象 ... },
         * 
         *      # 可能有多个 elm
         *      elm_a: { ... 相同的开关参数对象 ... },
         *      elm_b: { ... },
         *      ...
         *  }
         */
        autoParseSwitch(key='', val=false, elm=null, preUntil=null, preKey=null) {
            let is = this.$is,
                elms = this.autoEnabledElm || [],
                //标准 switch 参数结构
                rtn = {
                    key: '',
                    val: '',
                    variable: null,
                    calc: '',
                    until: null,
                    value: null,
                };
            //传入 空键名 或 false 键值，直接返回 null
            if (!is.nemstr(key) || val===false || val==='false') return null;
            //如果指定的 默认指向元素不存在，直接返回 null
            if (is.nemstr(elm) && !elms.includes(elm)) return null;
            //过滤不存在的 指向元素
            if (is.nemarr(elm)) elm = elm.filter(i=>is.nemstr(i) && elms.includes(i));

            //此条开关规则指向的元素 []
            let toelm = is.nemstr(elm) ? [elm] : (is.nemarr(elm) ? elm : []);

            // 0    解析键名表达式
            let kexp = this.autoParseSwitchKey(key, preUntil, preKey);
            //解析失败则跳过此开关
            if (!is.nemobj(kexp) || !is.function(kexp.until)) return null;
            //如果未指定默认指向的元素，且键名表达式中未指定 @.. 作用元素 直接返回 null
            if (!is.nemarr(toelm) && !is.nemarr(kexp.element)) return null;
            //合并到开关参数
            rtn = Object.assign(rtn, kexp);
            //此开关作用于多个元素
            if (is.nemarr(kexp.element)) {
                toelm.push(...kexp.element);
                toelm = toelm.unique();
            }
            Reflect.deleteProperty(rtn, 'element');
            
            // 1    解析键值  需要传入键名解析结果，用于处理 键值 == true 的情况
            let vexp = this.autoParseSwitchVal(val, kexp);
            //解析失败则跳过此开关
            if (!is.nemobj(vexp) || !is.function(vexp.value)) return null;
            //合并到开关参数
            rtn = Object.assign(rtn, vexp);

            // 2    确保解析结果有效
            if (
                !is.nemstr(rtn.calc) || !is.defined(this.auto.calcs[rtn.calc]) || 
                !is.function(rtn.until) || !is.function(rtn.value)
            ) {
                //解析结果无效 则跳过
                return null;
            }

            // 3    返回解析得到的开关参数，可能对应了一个或多个元素
            let rtns = {};
            if (is.nemarr(toelm)) {
                this.$each(toelm, (toel,i) => {
                    //跳过不存在的元素
                    if (!elms.includes(toel)) return true;
                    //开关参数 写入对应元素
                    rtns[toel] = {};
                    //!! 使用 rtn.key 作为 解析后的开关键名，因为可能存在 preKey 前缀
                    rtns[toel][rtn.key] = rtn;
                });
            }
            if (!is.nemobj(rtns)) return null;
            return rtns;
        },
        /**
         * switch 样式开关配置 键名解析，支持 Mustache 以及特殊语法
         * !! 如果指定了 preUntil 函数，则需要将此函数合并到生成的 rtn.until 条件求值函数中
         * !! 仅外部 preUntil(ctx)===true 时，条件求值函数才有可能返回 true
         */
        autoParseSwitchKey(key='', preUntil=null, preKey=null) {
            let is = this.$is,
                elms = this.autoEnabledElm || [],
                //Vue.mustache.parseKeyExpression 解析结果
                kexp = null,
                //准备解析结果
                rtn = {
                    //原始的 key
                    origin: key,
                    //开关定义键名字符串  与 preKey 合并
                    key: is.nemstr(preKey) ? `${preKey} > ${key}` : key,
                    //如果是 直接输入某个监听变量名 作为键名，则在此保存此变量名
                    variable: null,     // 'foo.bar': true  -->  variable = 'foo.bar'
                    //可能存在的 修饰符数据  例如： ...key... @root@icon@btn #1
                    modify: {
                        //'#': ['1'],
                        //'@': ['root', 'icon', 'btn']
                    },
                    //此开关可能指向某些元素  由 @root@icon 修饰符指定
                    element: [],
                    //处理后的 Mustache 表达式
                    mustache: '',
                    //开关键名的 求值函数
                    until: null,
                };
            
            key = key.trim();
            
            //!! 如果传入了 'elm #class|style|props|...' 作为键名  则使用快速解析
            let startsWithElm = false,
                kelm = null,
                kclc = null;
            for (let i of Object.keys(this.auto.calcs)) {
                let ki = `#${i.toLowerCase()}`,
                    ko = key.includes(ki) && elms.includes(key.replace(ki,'').trim());
                startsWithElm = startsWithElm || ko;
                if (ko) {
                    kelm = key.replace(ki,'').trim();
                    kclc = i.toLowerCase();
                    break;
                }
            };
            if (startsWithElm && is.nemstr(kelm) && is.nemstr(kclc)) {
                //生成 快速解析结果
                kexp = {
                    origin: key,
                    variable: null,
                    modify: {
                        '#': [kclc],
                        '@': [kelm]
                    },
                    mustache: '{{true}} [Boolean]',
                    getter: _this => true,
                };
                kexp.getter._isMustacheFn = true;
                kexp.getter._getterFunctionBody = 'return true;';
            } else {
                //调用 Vue.mustache 解析器
                kexp = this.$mustache.parseKeyExpression(key, this);
            }
            
            //解析失败 返回 null
            if (!is.nemobj(kexp) || !is.function(kexp.getter)) return null;
            //合并结果
            rtn = Object.assign(rtn, kexp);
            //@ 修饰符指向的 元素列表
            if (is.nemarr(rtn.modify['@'])) {
                //!! 如果指定了 @* 则将作用于 所有启用的元素
                if (rtn.modify['@'].includes('*')) {
                    rtn.element = this.autoEnabledElm || [];
                } else {
                    //作用于指定的元素
                    rtn.element.push(...rtn.modify['@']);
                }
            }

            //!! 如果外部指定了额外的 preUntil 函数
            let oGetter = rtn.getter;
            if (this.$mustache.isMustacheFn(preUntil)) {
                //创建包裹函数，包裹外部传入的 和 此处生成的 until 函数，使用 && 运算
                let getter = _this => preUntil(_this) && oGetter(_this);
                //标记
                getter._isMustacheFn = true;
                //forDebug
                getter._getterFunctionBody = `${preUntil._getterFunctionBody} > ${oGetter._getterFunctionBody}`;
                //使用
                rtn.until = getter;
            } else {
                //直接使用生成的
                rtn.until = oGetter;
            }

            //forDebug
            rtn._untilFunctionBody = rtn.until._getterFunctionBody;

            //删除多余项目
            Reflect.deleteProperty(rtn, 'getter');
            //Reflect.deleteProperty(rtn, 'origin');
            //返回
            return rtn;

        },
        /**
         * switch 样式开关配置 键值解析，支持 Mustache 
         * 最终生成此开关规则的参数：
         *      calc    要处理的 样式类型 class|style
         *      value   求值函数，用于实际计算 class[]|style{}
         *              !! 此求值函数需要 3 个参数：
         *              !!  _this   当前的组件实例
         *              !!  _opt    这个开关解析后的完整参数数据
         *              !!  _elm    此函数执行时的所属元素名 
         */
        autoParseSwitchVal(val=false, kexp={}) {
            let is = this.$is,
                must = this.$mustache,
                pre = this.auto.prefix;
            //如果未传入有效的 键名解析结果，返回 null
            if (!is.nemobj(kexp) || !is.function(kexp.until)) return null;
            //switch 配置参数键值可选类型
            if (!is.boolean(val) && !is.nemstr(val) && !is.nemarr(val) && !is.nemobj(val)) {
                //不合法类型  返回 null
                return null;
            }
            //准备解析结果数据结构
            let rtn = {
                //原始语句
                val,
                //此条 switch 配置要计算的属性 class 或 style
                calc: '',
                //生成属性计算函数，根据 calc 类型返回 对应类型的 class[]|style{}
                value: null,
            };

            //处理明确标记的 #class|#style|#props 处理数据类型
            let isClass = false,
                isStyle = false,
                isProps = false;
            if (is.nemarr(kexp.modify['#'])) {
                isClass = kexp.modify['#'].includes('class');
                isStyle = kexp.modify['#'].includes('style');
                isProps = kexp.modify['#'].includes('props');
            }

            // 0    false 不启用此 switch
            if (val===false || val==='false') return null;

            // 1    true 根据键名解析结果，智能生成 class[]
            if (isClass===true && (val===true || val==='true')) {
                //!! 需要调用键名解析结果中的 variable 参数
                let vn = kexp.variable;
                //仅 foo.bar.jazTom 形式的监听变量名有效，foo.bar["key"] 形式的变量名无效
                if (!is.nemstr(vn) || !is.nemarr(vn.match(/^[a-zA-Z_$][a-zA-Z0-9_$.]*$/g))) return null;
                //将变量名 按 . 以及 驼峰形式 转换为 snake-case
                //foo.bar.jazTom  -->  foo-bar-jaz-tom
                let clsn = vn.split('.').map(i=>i.toSnakeCase('-')).join('-'),
                    //auto-prefix
                    clsnp = this.autoClsnAutoPrefix(clsn),
                    //loget
                    loget = this.$cgy.loget,
                    //获取此变量的值 检查其类型
                    vv = loget(this, vn);
                //变量名不存在 返回 null
                if (is.undefined(vv)) return null;
                let isStr = is.string(vv);

                //calc 设为 class
                rtn.calc = 'class';
                
                //创建 value 求值函数
                let getter = (_this, _opt={}, _elm=null) => {
                    let rtnClsa = [clsnp];
                    if (isStr) {
                        let rtnv = loget(_this, vn);
                        if (is.nemstr(rtnv)) rtnClsa.push(`${clsn}-${rtnv.toSnakeCase('-')}`);
                    }
                    return rtnClsa;
                }
                //标记 求值函数
                getter._isMustacheFn = true;
                rtn.value = getter;
                return rtn;
            }

            // 2    数组类型  或  声明类型为 [Array] 的表达式  或  以 . 开头的字符串 计算并返回 class[]
            if (
                isProps!==true && (
                    isClass===true ||
                    is.nemarr(val) || 
                    (must.isPureMustache(val) && val.trim().endsWith('[Array]')) ||
                    (is.nemstr(val) && val.startsWith('.'))
                )
            ) {
                //calc 设为 class
                rtn.calc = 'class';

                //首先处理 声明类型为 [Array] 的 表达式
                if (must.isPureMustache(val) && val.trim().endsWith('[Array]')) {
                    //解析这个表达式，生成求值函数
                    let getter = must.defineGetter(val);
                    if (!must.isMustacheFn(getter)) return null;
                    //包裹求值函数，以传入额外参数
                    rtn.value = (_this, _opt={}, _elm=null) => getter(_this, false, _opt, _elm);
                    //标记
                    rtn.value._isMustacheFn = true;
                    //forDev
                    rtn._valueFunctionBody = getter._getterFunctionBody;
                    return rtn;
                }

                //val 归一化为 []
                if (is.string(val)) val = this.$cgy.toClassArr(val.substring(1).trim());
                //筛选
                val = val.filter(i=>is.nemstr(i));
                //将 val 指定的 class[] 中的 Mustache 表达式转为求值函数
                let getters = val.map(i => {
                    if (!must.isMustache(i)) return i;
                    //Mustache 表达式转换为求值函数，返回值一定是 String
                    return must.defineGetter(i, [String]);
                });
                
                //创建 value 求值函数
                let getter = (_this, _opt={}, _elm=null) => {
                    let rtnClsa = [];
                    this.$each(getters, (gi, i) => {
                        if (is.nemstr(gi)) {
                            rtnClsa.push(gi);
                            return true;
                        }
                        if (must.isMustacheFn(gi)) {
                            //!! 向求值函数传入额外参数 _opt,_elm
                            let clsi = gi(_this, false, _opt, _elm);
                            if (is.nemstr(clsi)) rtnClsa.push(clsi);
                            return true;
                        }
                    });
                    return rtnClsa;
                }
                //标记 求值函数
                getter._isMustacheFn = true;
                rtn.value = getter;
                return rtn;
            }

            // 3    标准的 CSS-Object  或者  声明类型为 [Object] 的表达式  或  同时包含 : ; 的字符串(因为可能存在表达式中包含:，因此不检查 :; 数量是否相等)  计算并返回 style{}
            if (
                isProps!==true && (
                    isStyle===true ||
                    (is.nemobj(val) && this.$cgy.isCssObj(val)) || 
                    (must.isPureMustache(val) && val.trim().endsWith('[Object]')) ||
                    (is.nemstr(val) && this.$cgy.isCssStr(val, false))
                )
            ) {
                //calc 设为 style
                rtn.calc = 'style';

                //首先处理声明类型为 [Object] 的表达式
                if (must.isPureMustache(val) && val.trim().endsWith('[Object]')) {
                    //解析表达式，生成求值函数
                    let getter = must.defineGetter(val);
                    if (!must.isMustacheFn(getter)) return null;
                    //生成包裹函数，以传入额外参数
                    rtn.value = (_this, _opt={}, _elm=null) => getter(_this, false, _opt, _elm);
                    //标记
                    rtn.value._isMustacheFn = true;
                    //forDev
                    rtn._valueFunctionBody = getter._getterFunctionBody;
                    return rtn;
                }

                //val 归一化为 {}
                if (is.string(val)) val = this.$cgy.toCssObj(val);
                
                //将 val 指定的 style{} 中的 Mustache 表达式转为求值函数
                let getters = {};
                this.$each(val, (v,k) => {
                    if (!is.nemstr(v)) return true;
                    if (!must.isMustache(v)) {
                        getters[k] = v;
                        return true;
                    }
                    //Mustache 表达式转换为求值函数，返回值一定是 String
                    getters[k] = must.defineGetter(v, [String]);
                });

                //创建 value 求值函数
                let getter = (_this, _opt={}, _elm=null) => {
                    let rtnSty = {};
                    this.$each(getters, (gi, i) => {
                        if (is.nemstr(gi)) {
                            rtnSty[i] = gi;
                            return true;
                        }
                        if (must.isMustacheFn(gi)) {
                            //!! 向求值函数传入额外参数 _opt,_elm
                            let clsi = gi(_this, false, _opt, _elm);
                            if (is.nemstr(clsi)) rtnSty[i] = clsi;
                            return true;
                        }
                    });
                    return rtnSty;
                }
                //标记 求值函数
                getter._isMustacheFn = true;
                rtn.value = getter;
                return rtn;
            }

            // 4    传入了完整的 switch 定义参数
            if (
                isProps!==true && is.nemobj(val) && 
                is.nemstr(val.calc) && is.defined(this.auto.calcs[val.calc]) && 
                is.function(val.value)
            ) {
                rtn = Object.assign(rtn, val);
                //将手动指定的 value 求值函数标记为 MustacheFn
                rtn.value._isMustacheFn = true;
                return rtn;
            }

            // 5    键名包含 #props 修饰符  作为 props 参数块
            if (
                isProps===true && (
                    is.nemobj(val) ||
                    (must.isPureMustache(val) && val.trim().endsWith('[Object]'))
                )
            ) {
                //作为 props 参数处理
                rtn.calc = 'props';

                // 5.1  键值是 {}
                if (is.nemobj(val)) {
                    //!! 预编译 props 参数块，编译为求值函数，缓存到 rtn.val 中
                    rtn.val = this.autoParseSwitchPropsRecursive(val, kexp.until);
                    
                    //创建 value 求值函数
                    let getter = (_this, _opt={}, _elm=null) => {
                        //console.log(_opt.val);
                        //递归解析，得到结果 {}  传入额外参数
                        let rv = this.autoEvaluatePropsRecursive(_opt.val, _this, _opt, _elm);
                        //console.log(rv);
                        return rv;
                    }
                    //标记 求值函数
                    getter._isMustacheFn = true;
                    rtn.value = getter;
                    return rtn;
                }

                // 5.2  键值是表达式
                if (must.isPureMustache(val) && val.trim().endsWith('[Object]')) {
                    //解析表达式生成求值函数
                    let getter = must.defineGetter(val);
                    if (!must.isMustacheFn(getter)) return null;
                    //!! 包裹生成的求值函数，并向生成的求值函数传入额外参数
                    rtn.value = (_this, _opt={}, _elm=null) => getter(_this, false, _opt, _elm);
                    //标记
                    rtn.value._isMustacheFn = true;
                    //forDev
                    rtn._valueFunctionBody = getter._getterFunctionBody;
                    return rtn;
                }

                // 5.3  其他形式都不是有效的 props 配置形式，返回 null
                return null;
            }

            return null;
        },
        /**
         * 递归解析某一段 switch 开关配置参数块 一定是 {} 根据键名和键值的形式，分别处理
         * @param {Object} sws 要解析的 switch 开关配置参数块 {}
         * @param {String|Array} elm 这些开关指向的 元素，可以有多个
         * @param {Function} preUntil 这些开关的 前置 条件求值函数，只有 preUntil==true 这些开关才可能生效
         * @param {String} preKey 如果存在 preUntil 则为这些开关的键名增加一个前缀，用于区分同一个元素下的不同开关
         * @return {Object} 返回数据形式一定是：
         *  {
         *      elm: {
         *          'switch-key-a': { ... 单条开关参数 ... },
         *          'pre-key > switch-key-b': { ... 单条开关参数 ... },
         *          ...
         *      },
         * 
         *      elm_a: { ... 一组开关参数 ... },
         *      elm_b: { ... 一组开关参数 ... },
         *      ...
         *  }
         */
        autoParseSwitchRecursive(sws={}, elm=null, preUntil=null, preKey=null) {
            let is = this.$is,
                //ext = this.$cgy.extend,
                elms = this.autoEnabledElm || [],
                //判断 {} 是否是 某个开关规则对象 包含必须的 calc,value 键
                isSwObj = o => is.nemobj(o) && 'calc,value'.split(',').minus(Object.keys(o)).length<=0,
                //判断字符串是否 元素名
                isElm = s => is.nemstr(s) && elms.includes(s),
                //isMustacheFn
                isMfn = fn => this.$mustache.isMustacheFn(fn),
                //合并到返回值
                ext = (res, sw) => {
                    if (!is.nemobj(sw)) return res;
                    res = this.$cgy.extend(res, sw);
                    return res;
                },
                //返回值
                rtn = {};
            if (!is.nemobj(sws)) return rtn;

            //处理可能传入的 指向元素 归一化为 []
            if (!is.nemstr(elm) && !is.nemarr(elm)) {
                elm = [];
            } else {
                if (is.nemstr(elm)) {
                    elm = isElm(elm) ? [elm] : [];
                } else {
                    elm = elm.filter(i=>isElm(i));
                }
            }
            //合并 elm 和 后传入的元素  新数组如果没有元素 返回 null ，否则返回数组
            let appendElm = (ela, ...nela) => {
                if (!is.nemarr(nela)) return is.nemarr(ela) ? [...ela] : null;
                if (is.nemarr(ela)) return [...ela, ...nela].unique();
                return nela;
            }

            //依次解析
            this.$each(sws, (swc, swk) => {

                // 0    首先处理 #props 的情况 用于传递 props 到指定元素的 配置项
                if (swk.includes('#props')) {
                    let swkl = swk.replace('#props', '').trim(),
                        //!! 如果是 'elm #props' 键名，则直接将 elm 元素名 传递给解析方法
                        dftelm = isElm(swkl) ? appendElm(elm, swkl) : (
                            is.nemarr(elm) ? elm : (
                                swk.includes('@') ? null : 'root'
                            )
                        );
                    //解析此条规则  结果合并到 rtn
                    rtn = ext(rtn, this.autoParseSwitch(swk, swc, dftelm, preUntil, preKey));
                    return true;
                }

                // 1    如果键值是一个对象，且不是 开关规则对象，也不是 CSS-Object，则递归调用
                if (is.nemobj(swc) && !isSwObj(swc) && !this.$cgy.isCssObj(swc)) {
                    // 1.1  直接定义在某个元素下的 开关规则块
                    if (isElm(swk)) {
                        //递归解析 此元素下的 所有开关规则
                        rtn = ext(rtn, this.autoParseSwitchRecursive(swc, appendElm(elm, swk), preUntil, preKey));
                        return true;
                    }

                    // 1.2  定义在键名表达式下的 开关规则块
                    //解析键名表达式
                    let kexp = this.autoParseSwitchKey(swk, preUntil, preKey);
                    if (!is.nemobj(kexp) || !is.function(kexp.until)) return true;
                    /**
                     * 将这个键名表达式的解析结果 作为前置条件，继续传入后续递归处理方法中
                     * !! 后续递归解析时，使用 kexp.until 和 kexp.key 替换当前的 preUntil 和 preKey
                     * !! 后续递归解析时，根据 kexp.element 决定是否传入指向的 元素
                     */
                    //递归解析 此表达式条件下的 开关规则块
                    rtn = ext(rtn, this.autoParseSwitchRecursive(swc, appendElm(elm, ...kexp.element), kexp.until, kexp.key));
                    return true;
                }

                // 2    定义在键名表达式下的 单条规则，通过 @ 指向元素  或 通过前置传入指向元素
                let dftelm = is.nemarr(elm) ? elm : (swk.includes('@') ? null : 'root');
                //解析此条规则  结果合并到 rtn
                rtn = ext(rtn, this.autoParseSwitch(swk, swc, dftelm, preUntil, preKey));

            });

            return rtn;
        },
        /**
         * 递归解析作为 props 传递的 带 Mustache 表达式的 {} 
         * 生成处理后的 props{} 其中表达式被替换为求值函数
         * !! 在 props{} 内部使用的 键名|键值 表达式，不能省略 {{...}} 必须完整定义
         * 例如：
         *  props = {
         *      # 静态值，通常写在 auto.element.elm.props{} 中
         *      foo: 'bar',
         * 
         *      # 键值为表达式  返回任意支持类型
         *      !! 键值表达式如果要返回 非 String 类型的值，必须声明类型
         *      bar: 'foo-{{bar}}'  或  '{{bar}} [Array任意支持类型]',
         *      # 带表达式的 []|{} 值
         *      jaz: ['foo', '{{bar}} [可以是任意类型]', ...],
         *      tom: {
         *          foo: 'with-{{foo}}',
         *          bar: '{{bar}} [任意类型]',
         *          ...
         *      },
         * 
         *      # 键名可带表达式，实际键名写在 # 后
         *      '{{exp-a}} [Boolean可省略] #jry': '静态值'  或  '返回任意支持类型的表达式'  或  带表达式的 []{},
         * 
         *      # 键名带表达式，但是未指定实际键名，根据键名表达式，决定是否合并到最终结果 {} 中
         *      !! 这种情况，键值只能是 {}
         *      '{{exp-b}}': {
         *          foo: 'bar-new',
         *          bar: '{{bar}} [Array]',
         *          tom: {
         *              foo: 'with-{{foo}}-new',
         *          },
         *          '{{exp-c}} #jry': {
         *              ... 继续递归 ...
         *          },
         *      },
         *  }  
         * 
         * 解析为：
         *  {
         *      # 静态值不处理，原样返回
         *      foo: 'bar',
         *      
         *      # 键值表达式，转为 求值函数
         *      bar: _this => 'foo-barval',
         *      jaz: [
         *          'foo',
         *          _this => barval(任意支持的类型)
         *      ],
         *      tom: {
         *          foo: _this => 'with-fooval',
         *          bar: _this => barval(任意支持的类型)
         *      },
         * 
         *      # 键名带表达式，且包含实际键名的
         *      jry: {
         *          # 键名表达式转为 until 条件求值函数
         *          __until__: _this => exp-a-val(Boolean),
         * 
         *          # 键值如果不是 []|{} 的，直接转为 求值函数
         *          __value__: _this => someval(任意支持的类型),
         *          # 键值如果是 []|{} 则继续递归生成求值函数
         *          __value__: [
         *              '静态值',
         *              _this => someval(任意支持的类型),
         *          ] 或 {
         *              k: 静态值,
         *              kk: _this => kkval(任意支持类型)
         *          },
         *      },
         * 
         *      # 键名带表达式，且未指定实际键名的  此段代码块需要条件合并
         *      !! 键名前部增加 __until__ 前缀
         *      '__until__{{exp-b}}': {
         *          # 键名表达式转为 until 条件求值函数
         *          __until__: _this => exp-b-val(Boolean),
         * 
         *          # 键值继续递归生成求值函数
         *          __value__: {
         *              foo: 'bar-new',
         *              bar: _this => barval(Array),
         *              tom: {
         *                  foo: _this => 'with-fooval-new',
         *              },
         * 
         *              # 嵌套键名表达式
         *              jry: {
         *                  __until__: _this => exp-c-val(Boolean),
         *                  # 继续递归
         *                  __value__: getter-function  或  []  或  {}
         *              },
         *          }
         * 
         *      }
         *  }
         * 
         */
        autoParseSwitchPropsRecursive(props={}, preUntil=null) {
            let is = this.$is,
                must = this.$mustache,
                ism = m => must.isMustache(m),
                ismfn = fn => must.isMustacheFn(fn),
                //解析结果
                rtn = {};
            if (!is.nemobj(props)) return {};

            this.$each(props, (prop, pkey) => {
                // 0    处理带表达式的键名
                if (ism(pkey)) {
                    //键名带表达式，最终一定解析为此格式
                    let rkey = pkey,
                        rtni = {
                            __until__: null,
                            __value__: null,
                        },
                        //解析这个键名表达式
                        kexp = this.autoParseSwitchKey(pkey, preUntil);
                    //无法解析键名表达式，则跳过
                    if (!ismfn(kexp.until)) return true;
                    //使用解析生成的 getter
                    rtni.__until__ = kexp.until;
                    if (pkey.includes('#')) {
                        // 0.1  通过 #key 指定了实际键名，使用实际键名作为 rtn 结果的键名
                        //!! 如果有多个 #key1 #key2 只有最后一个生效 key2
                        rkey = pkey.trim().split('#').slice(-1)[0];
                    } else {
                        // 0.2  未指定实际键名的，则在当前键名前增加 __until__ 前缀
                        rkey = `__until__${pkey}`;
                    }

                    // 0.3  根据键值类型，分别解析
                    if (ism(prop)) {
                        // 0.3.1    键值为表达式   解析生成 求值函数
                        rtni.__value__ = must.defineGetter(prop);
                    } else if (is.nemarr(prop)) {
                        // 0.3.2    键值为 [] 依次处理其中可能存在的 表达式
                        rtni.__value__ = prop.map(i=>{
                            if (ism(i)) return must.defineGetter(i);
                            return i;
                        });
                    } else if (is.nemobj(prop)) {
                        // 0.3.3    键值为 {} 则递归
                        rtni.__value__ = this.autoParseSwitchPropsRecursive(prop, rtni.__until__);
                    } else {
                        // 0.3.4    其他类型的值，原样返回
                        rtni.__value__ = prop;
                    }

                    //合并到结果
                    rtn[rkey] = rtni;
                    return true;
                }

                // 1    处理普通键名，根据键值类型分别处理
                if (ism(prop)) {
                    // 1.1  键值为表达式   解析生成 求值函数
                    rtn[pkey] = must.defineGetter(prop);
                } else if (is.nemarr(prop)) {
                    // 1.2  键值为 [] 依次处理其中可能存在的 表达式
                    rtn[pkey] = prop.map(i=>{
                        if (ism(i)) return must.defineGetter(i);
                        return i;
                    });
                } else if (is.nemobj(prop)) {
                    // 1.3  键值为 {} 则递归
                    rtn[pkey] = this.autoParseSwitchPropsRecursive(prop, preUntil);
                } else {
                    // 1.4  其他类型的值，原样返回
                    rtn[pkey] = prop;
                }

            });

            return rtn;
        },
        /**
         * 求值阶段，解析并求值 autoParseSwitchPropsRecursive 方法生成的 {}
         * 将其中所有满足条件的 求值函数替换为实际求得的值
         */
        autoEvaluatePropsRecursive(val={}, context={}, ...$extraArgs) {
            let is = this.$is,
                ext = this.$extend,
                must = this.$mustache,
                ism = m => must.isMustache(m),
                ismfn = fn => must.isMustacheFn(fn),
                //解析结果
                rtn = {};
            if (!is.nemobj(val)) return {};

            this.$each(val, (v,k) => {
                // 0    处理 v 是 {__until__:..., __value__:... } 形式的情况
                if (is.nemobj(v) && is.defined(v.__until__) && is.defined(v.__value__)) {
                    //条件求值函数不合法，则跳过
                    if (!ismfn(v.__until__)) return true;

                    //先计算条件，满足的情况下才计算值
                    if (v.__until__(context)!==true) return true;

                    //求值
                    let vv = v.__value__,
                        rv = undefined;
                    // 根据键值类型  分别求值
                    if (ismfn(vv)) {
                        // 0.1  键值是一个求值函数
                        rv = vv(context, false, ...$extraArgs);
                    } else if (is.nemarr(vv)) {
                        // 0.2  键值是 []
                        rv = vv.map(i=>{
                            if (ismfn(i)) return i(context, false, ...$extraArgs);
                            return i;
                        });
                    } else if (is.nemobj(vv)) {
                        // 0.3  键值是 {}  递归求值
                        rv = this.autoEvaluatePropsRecursive(vv, context, ...$extraArgs);
                    } else {
                        // 0.4  其他类型键值，直接返回
                        rv = vv;
                    }
                    //如果未求得正确的值，则跳过
                    if (is.undefined(rv)) return true;

                    //根据键名形式，合并结果
                    if (k.startsWith('__until__')) {
                        //为指定实际键名，则合并到 rtn
                        rtn = ext(rtn, rv);
                    } else {
                        //指定了实际键名，则合并到键名下
                        rtn[k] = rv;
                    }
                    return true;
                }

                // 1    处理普通的 v 根据其形式分别处理
                let rv = undefined;
                if (ismfn(v)) {
                    // 1.1  键值是一个求值函数
                    rv = v(context, false, ...$extraArgs);
                } else if (is.nemarr(v)) {
                    // 1.2  键值是 []
                    rv = v.map(i=>{
                        if (ismfn(i)) return i(context, false, ...$extraArgs);
                        return i;
                    });
                } else if (is.nemobj(v)) {
                    // 1.3  键值是 {}  递归求值
                    rv = this.autoEvaluatePropsRecursive(v, context, ...$extraArgs);
                } else {
                    // 1.4  其他类型键值，直接返回
                    rv = v;
                }
                //如果未求得正确的值，则跳过
                if (is.undefined(rv)) return true;

                //合并
                //!! 此种情况下，键名 k 一定是普通键名
                rtn[k] = rv;

            });

            return rtn;
        },



        /**
         * 工具
         */
        //判断一个对象 是否是 完整的已编译的 switch 开关参数对象
        autoIsSwObj(o={}) {
            let is = this.$is;
            if (!is.nemobj(o)) return false;
            let ks = Object.keys(o);
            //必须包含这些键名 key,val,calc,until,value
            if ('key,val,calc,until,value'.split(',').minus(ks).length>0) return false;
            //调用Vue.mustache 方法
            let must = this.$mustache;
            return (
                is.defined(o.until) && must.isMustacheFn(o.until) &&
                is.defined(o.value) && must.isMustacheFn(o.value) &&
                is.nemstr(o.calc) && is.defined(this.auto.calcs[o.calc])
            );
        },
        //根据 calc 类型 合并数据
        autoExtendRtn(rtn, val, calc='class') {
            let is = this.$is,
                calcs = this.auto.calcs;
            if (!is.nemstr(calc) || !is.defined(calcs[calc])) return rtn;
            switch (calc) {
                case 'class':
                    //将传入的 rtn|val 归一化为 []
                    rtn = is.nemstr(rtn) ? this.$cgy.toClassArr(rtn) : (!is.array(rtn) ? [] : rtn);
                    val = is.nemstr(val) ? this.$cgy.toClassArr(val) : (!is.array(val) ? [] : val);
                    rtn = this.$cgy.mergeClassArr(rtn, val);
                    break;
                case 'style':
                    //将 rtn|val 归一化为 标准的 CSS-Obj 
                    rtn = is.nemstr(rtn) ? this.$cgy.toCssObj(rtn) : (!this.$cgy.isCssObj(rtn) ? {} : rtn);
                    val = is.nemstr(val) ? this.$cgy.toCssObj(val) : (!this.$cgy.isCssObj(val) ? {} : val);
                    rtn = Object.assign(rtn, val);
                    break;
                case 'props':
                    //将 rtn|val 归一化为 {}
                    rtn = !is.plainObject(rtn) ? {} : rtn;
                    val = !is.plainObject(val) ? {} : val;
                    rtn = this.$extend(rtn, val);
                    break;
            }
            return rtn;
        },
        //根据元素的 accept 形式，生成对应的 atom|sub|extra 透传参数 props{}
        autoAcceptProps(type='atom', full={}) {
            let is = this.$is,
                elms = this.autoEnabledElm || [],
                defs = this.auto.element || {},
                rtn = {};
            //元素不存在
            if (!is.nemarr(elms)) return rtn;
            this.$each(elms, (elm,i)=>{
                //建立空值
                rtn[elm] = {};

                //当前未传入任何 atom|sub|extra 参数
                if (!is.nemobj(full)) return true;

                //当前元素的定义参数  未定义 accept 方式则 默认为 false 不接收任何参数
                let def = defs[elm] || {},
                    acp = this.$cgy.loget(def, `accept.${type}`, false);

                //accept[type] === true 全部接收
                if (acp===true) {
                    rtn[elm] = Object.assign({}, full);
                    return true;
                }
    
                //accept[type] === false 全部不接收
                if (acp===false) return true;
                
                //accept[type] = [...] 只接收指定的部分
                if (is.nemarr(acp)) {
                    this.$each(full, (v,k)=>{
                        if (acp.includes(k)) {
                            rtn[elm][k] = v;
                        }
                    });
                    return true;
                }
    
                //accept[type] = '计算属性'  通过自定义的计算属性生成 要透传的 atom|sub|extra 参数
                if (is.nemstr(acp) && is.defined(this[acp]) && is.plainObject(this[acp])) {
                    rtn[elm] = this[acp];
                    return true;
                }
            });
            
            return rtn;
        },
        //将传入的 样式类名 补齐头部的 prefix
        autoClsnAutoPrefix(...clsns) {
            let is = this.$is,
                pre = this.auto.prefix,
                empty = !is.nemstr(pre);
            if (!is.nemarr(clsns)) return '';
            //筛选传入的 clsns
            clsns = clsns.filter(i=>is.nemstr(i));
            if (!is.nemarr(clsns)) return '';

            //只传入一个 类名，直接返回 补齐 prefix 后的类名字符串
            if (clsns.length===1) return empty ? clsns[0].toSnakeCase('-') : `${pre.toSnakeCase('-')}-${clsns[0].toSnakeCase('-')}`;
            //传入多个 类名，返回 补齐 prefix 后的类名字符串数组
            return clsns.map(i => empty ? i : `${pre.toSnakeCase('-')}-${i.toSnakeCase('-')}`);
        },
        //根据传入的 elm 和 calc 计算参数，依次回退到默认方法，返回可执行的 function
        autoCalculatorFallback(el, calc='class') {
            let is = this.$is,
                isfn = fn => (is.nemstr(fn) && is.function(this[fn])) || is.function(fn),
                chks = [
                    `autoCalc${el.ucfirst()}${calc.ucfirst()}`,
                    `autoCalc${calc.ucfirst()}`,
                    'autoCalc'
                ],
                fn = null;
            this.$each(chks, (dfn,i) => {
                if (isfn(dfn)) {
                    let tfn = this[dfn].bind(this);
                    if (i===0) {
                        fn = () => tfn();
                    } else if (i===1) {
                        fn = () => tfn(el);
                    } else {
                        fn = () => tfn(el, calc);
                    }
                    return false;
                }
            });
            return is.function(fn) ? fn : null;
        },

        /**
         * 从 props 或 $attrs 中获取指定的值
         * 键名分别尝试 fooBar 和 foo-bar 
         * !! 模板中通过 :foo-bar="..." 传入的值，
         * !!   在 props 中为 props.fooBar
         * !!   在 $attrs 中为 $attrs['foo-bar']
         * !! 因此需要分别尝试
         * 未找到  返回 undefined
         */
        autoGetInputProps(pkey='') {
            let is = this.$is,
                snk = s => is.nemstr(s) ? s.toSnakeCase('-') : '',
                cml = s => is.nemstr(s) ? s.toCamelCase(false) : '',    //首字母不大写
                isd = s => is.defined(s),
                //分别从 props 和 $attrs 中获取
                fnd = k => isd(this[k]) ? this[k] : (isd(this.$attrs[k]) ? this.$attrs[k] : undefined),
                rtn = '';
            if (!is.nemstr(pkey)) return undefined;
            //先尝试 camelCase
            rtn = fnd(cml(pkey));
            if (isd(rtn)) return rtn;
            //再尝试 kabab-case
            return fnd(snk(pkey));
        },

        /**
         * size 尺寸系统，自动缩放传入的 size 尺寸
         * 如果计算未能得到合法尺寸结果，则原样返回
         */
        autoSizeShift(shift='s1', csvKey='icon') {
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
         * 根据当前组件的 border 边框参数
         * 修改其中部分，生成新的参数  
         * 最终合并为 class 字符串
         * @param {String} bd 边框宽度，xxs~m~xxl  * 表示使用当前组件参数
         * @param {String} bdPo 边框未知，trbl  * 表示使用当前组件参数
         * @param {String} bdc 边框颜色，l3~m~d3|primary|blue|...  * 表示使用当前组件参数
         * @return {String} 生成完整的 class 字符串，可用于传递给子组件的 border 参数
         */
        autoModBorder(bd='*',bdPo='*',bdc='*') {
            let is = this.$is,
                isb = b => is.nemstr(b) && b!=='' && b!=='*',
                obd = this.borderPropVal || {},
                nbd = {};
            if (isb(bd)) nbd.bd = bd;
            if (isb(bdPo)) nbd.bdPo = bdPo;
            if (isb(bdc)) nbd.bdc = bdc;
            nbd = this.$extend({},obd,nbd);
            if (!is.nemobj(nbd)) return '';
            //合并为 class 字符串
            let cls = [];
            this.$each(nbd, (v,k) => {
                if (!is.nemstr(v)) return true;
                cls.push(`${k.toSnakeCase()}-${v}`);
            });
            return is.nemarr(cls) ? cls.join(' ') : '';
        },
    }
}