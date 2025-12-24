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
         */
        /*

        示例开关：shape 形状，可选 round|pill|sharp 圆角|胶囊|矩形
        shape: {
            type: String,
            default: 'round'
        },

        示例开关：effect 效果，可选 light|dark|plain 明|暗|线框
        effect: {
            type: String,
            default: 'light
        },

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
    },

    data() {return {

        /**
         * base-style 样式系统参数
         */
        sty: {
            /**
             * 定义此组件的 根元素以及内部任意元素 条件样式的计算方法
             * !! 此处应关联到 methods 中的方法名，或直接定义 function 
             * !! 所有需要自动计算 class[]|css{} 的 组件根元素|后代元素 都需要在此定义对应计算函数
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
                    root: 'styCalcRootClass',
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
                    root: 'styCalcRootStyle',
                    //组件内部可定义其他元素的 css {}
                    //...
                }
            },

            /**
             * 组件 根元素|后代元素 的初始 class[]|style{}
             * !! 所有需要自动计算 class[]|css{} 的 组件根元素|后代元素 都需要在此定义对应的 []|{}
             * !! 不建议把初始 class[]|style{} 写到 template 中
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
             * 定义的 base-style 样式系统的子系统
             * !! size|color|animate|switch 必须存在
             * !! 引用的组件可以自行扩展
             * 所有定义的 子系统 必须同时定义对应的 class|style 计算方法，例如：
             *      styCalcRootSizeClass  styCalcFooColorStyle
             * 如果此子系统受 disabled 状态影响方式为 'both' 则需要定义两个计算方法：
             *      styCalcRootSwitchClass 和 styCalcRootSwitchClassDisabled
             * !! 所有方法名都可以自定义 { ... calculator: { class: 'fooBarClass', style: 'fooBarStyle' }, ... }
             * 
             * !! size|color|animate 子系统默认不启用，有需要的组件应在内部覆盖
             */
            sub: {
                //定义一个子系统，指定其 启用状态、受 disabled 的影响方式，以及 自定义计算方法
                size: {
                    //启用|停用 
                    enable: false,
                    /**
                     * disabled 键表示此子系统受 disabled 控制的方式
                     *      true     --> 仅在 disabled == true 时启用
                     *      false    --> 仅在 disabled == false 时启用
                     *      'both'   --> 都启用，但是计算方法有区别
                     *      'none'   --> 都启用，不受 disabled 影响
                     */
                    disabled: 'none',
                    /**
                     * 可自定义此子系统对应的计算方法
                     * 方法名中可使用 {elm}|{Elm} 作为 this.sty.calculator 中定义元素的通配符，例如：
                     *      calcSwitch{Elm}Class
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
                 *      true        --> { enable: true, disabled: 'none' }
                 *      false       --> { enabled: false }
                 *      'disabled'  --> { enable: true, disabled: true }
                 *      'enabled'   --> { enable: true, disabled: false }
                 *      'both'      --> { enable: true, disabled: 'both' }
                 */
                color: false,
                //指定一个 {} 必须至少包含 enable 键
                switch: {
                    //!! 默认 启用 switch 子系统
                    enable: true,
                    disabled: 'both',
                    //此键是可选的，不指定则使用默认方法名
                    calculator: {
                        //因 此子系统的 disabled 参数为 both，因此需要同时定义两个计算方法
                        class: {
                            //disabled == false 时的计算方法
                            enabled: 'styCalc{Elm}SwitchClass',
                            //disabled == true 时的计算方法
                            disabled: 'styCalc{Elm}SwitchClassDisabled',
                        },
                        style: {
                            enabled: 'styCalc{Elm}SwitchStyle',
                            disabled: 'styCalc{Elm}SwitchStyleDisabled',
                        },
                    },
                },
                animate: {
                    //启用|停用 此子系统
                    enable: false,
                    //仅在 disabled == false 时启用
                    disabled: false,
                    /*calculator: {
                        class: '',
                        style: ''
                    },*/
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
             * !! 如果定义的 switch 名称以 :disabled 结尾，则此样式类会在 disabled === true 时不添加
             * 例如：button 组件中增加 'hoverShrink:disabled' = true 则：
             *      disabled === false && props.hoverShrink === true        --> class[] 增加 pre-hover-shrink
             *      disabled === true && props.hoverShrink === true         --> class[] 中不含 pre-hover-shrink
             * 
             * !! 如果定义的 switch 值不是 true 而是 string 类型，则在增加样式类时，使用此出定义的 string 作为类名
             * 例如：定义 sty.switch.fooBar = 'jaz-tom' 当 props.fooBar === true 时，自动在 根元素 class[] 中增加样式类：
             *          pre-foo-bar 和 pre-jaz-tom
             */
            switch: {
                //示例开关
                //shape: true,
                //effect: true,
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
        /**
         * 组件样式计算必须的 计算属性
         */
        //判断 custom class 是否为空
        isEmptyClass() {
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
        },

        /**
         * 解析传入的 custom class|style
         * 返回 class[]|css{}
         */
        //传入的 class[]
        styCustomClass() {
            if (this.isEmptyClass) return [];
            let is = this.$is,
                cls = this.customClass,
                rtn = [];
            if (is.string(cls)) {
                if (cls === '') return [];
                return cls.replace(new RegExp('\\s+','g'), ' ').split(' ');
            }
            if (is.array(cls)) {
                if (cls.length<=0) return [];
                return cls;
            }
            return [];
        },
        //传入的 css{}
        styCustomStyle() {
            if (this.isEmptyStyle) return {};
            let is = this.$is,
                sty = this.customStyle;
            if (is.string(sty)) {
                if (sty === '') return {};
                return this.$cgy.toCssObj(sty);
            }
            if (is.plainObject(sty)) {
                if (is.empty(sty)) return {};
                return sty;
            }
            return {};
        },

        /**
         * 样式计算核心方法
         * 返回计算后的 class[] css{}
         */
        //自动计算并返回 class[] 合并了外部传入的 custom class
        styComputedClass() {
            let is = this.$is,
                calc = this.sty.calculator || {},
                clsCalc = calc.class || {},
                rtn = {};
            //依次执行预定义的 class[] 计算方法
            if (!is.plainObject(clsCalc) || is.empty(clsCalc)) return {};
            this.$cgy.each(clsCalc, (fn, el) => {
                //定义的 计算方法必须是 methods 方法名，或直接定义的 function
                if (!is(fn, 'string,function')) return true;
                if (is.string(fn)) {
                    if (!is.defined(this[fn]) || !is.function(this[fn])) return true;
                    fn = this[fn];
                }
                //绑定
                fn = fn.bind(this);
                //执行 计算方法，返回 class[] 
                let clss = fn();

                //!! 组件根元素，自动合并外部传入的 custom class[]
                if (el === 'root') {
                    let extn = this.styCustomClass;
                    clss.push(...extn);
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
                calc = this.sty.calculator || {},
                cssCalc = calc.style || {},
                rtn = {};
            //依次执行预定义的 css{} 计算方法
            if (!is.plainObject(cssCalc) || is.empty(cssCalc)) return {};
            this.$cgy.each(cssCalc, (fn, el) => {
                //定义的 计算方法必须是 methods 方法名，或直接定义的 function
                if (!is(fn, 'string,function')) return true;
                if (is.string(fn)) {
                    if (!is.defined(this[fn]) || !is.function(this[fn])) return true;
                    fn = this[fn];
                }
                //绑定
                fn = fn.bind(this);
                //执行 计算方法，返回 css{} 
                let css = fn();

                //!! 组件根元素，自动合并外部传入的 custom style{}
                if (el === 'root') {
                    let extn = this.styCustomStyle;
                    css = this.$extend(css, extn);
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
    },

    methods: {

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
                //css 功能样式类 前缀
                pre = this.sty.prefix,
                //class|style 初始值，必须指定为 []|{}
                init = this.sty.init[calc][elm] || (calc==='class' ? [] : {}),
                //要输出的 class|style  []|{}
                rtn = calc==='class' ? (isa(init) ? [...init] : []) : (iso(init) ? this.$extend({}, init) : {}),
                //执行计算 合并结果
                exec = (rst, dis) => {
                    let rsti = this.styEachSubCalc(elm, calc, dis);
                    if (calc==='class' && isa(rsti)) {
                        rst.push(...rsti);
                    } else if (calc==='style' && iso(rsti)) {
                        rst = this.$extend({}, rst, rsti);
                    }
                    return rst;
                };

            //开始依次执行各子系统的计算方法
            // 0    执行不受 disabled 影响的子系统计算方法
            rtn = exec(rtn, 'none');

            if (this.disabled===false) {
                // 1    执行仅当 disabled == false 时起效的子系统计算方法
                rtn = exec(rtn, false);
                rtn = exec(rtn, 'enabled');
            } else {
                // 2    执行仅当 disabled == true 时起效的子系统计算方法
                rtn = exec(rtn, true);
                rtn = exec(rtn, 'disabled');

                // 3    如果需要，增加 disabled 样式类
                if (calc==='class' && elm==='root') {
                    rtn.push(`${pre}-disabled`);
                }
            } 

            return rtn;
        },



        /**
         * 循环执行所有已启用的样式子系统的计算方法
         * @param {String} elm 在 this.sty.calculator 中定义的需要计算 class|style 的元素名 例如：root
         * @param {String} calc 要计算 class 还是 style，决定了返回值是 [] 还是 {}
         * @param {Boolean|String} disabled 受 disabled 控制形式 可选 true|false 或 none|disabled|enabled
         * @return {Array|Object} 根据 calc 决定返回 [] | {}
         */
        styEachSubCalc(elm='root', calc='class', disabled='none') {
            let is = this.$is,
                sub = this.sty.sub,
                //判断是否 {enabled:.., disabled:..} 复合计算方法
                ismul = fs => is.plainObject(fs) && !is.empty(fs) && is.defined(fs.enabled) && is.defined(fs.disabled),
                //判断计算结果是否格式正确
                isrst = rst => (calc==='class' && is.array(rst)) || (calc==='style' && is.plainObject(rst)),
                //将子系统计算结果合并到输出结果中
                ext = (rst, rsti) => {
                    if (calc==='class') {
                        rst.push(...rsti);
                    } else {
                        rst = this.$extend({}, rst, rsti);
                    }
                    return rst;
                },
                rst = calc==='class' ? [] : {};

            //根据传入的 disabled 获取实际子系统参数中的 disabled 值
            let dis = 'none';
            if (is.boolean(disabled)) {
                dis = disabled===true;
            } else if (is.string(disabled) && ['disabled','enabled'].includes(disabled)) {
                dis = 'both';
            }

            this.$each(sub, (subi, subn) => {
                let subp = this.styParseSubParams(subi);
                //跳过 未启用的 以及 不符合传入的 disabled 条件的 子系统
                if (subp.enable!==true || subp.disabled!==dis) return true;
                //获取 默认的 或 自定义的  方法名
                let fn = this.styParseSubCalculator(subn, subp, elm, calc);
                //没能找到正确的 计算方法
                if (!is(fn, 'string,function') && !ismul(fn)) return true;

                //针对 复合计算方法
                if (ismul(fn)) {
                    //只有 dis==both 且 disabled==disabled|enabled 时可计算
                    if (dis!=='both' || ['disabled','enabled'].includes(disabled)!==true) return true;
                    fn = fn[disabled];
                }

                //按计算方法的定义方式，获取最终要执行的 function
                if (is.string(fn)) {
                    if (!is.defined(this[fn]) || !is.function(this[fn])) return true;
                    fn = this[fn];
                } else if (is.function(fn)) {
                    fn = fn.bind(this);
                } else {
                    fn = null;
                }

                //执行
                if (is.function(fn)) {
                    let rsti = fn();
                    if (isrst(rsti)) rst = ext(rst, rsti);
                }
            });
            return rst;
        },
        //解析子系统参数，得到统一的标准数据{}
        styParseSubParams(p=false) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isb = s => is.boolean(s),
                iso = s => is.plainObject(s) && !is.empty(s),
                dft = {
                    enable: false,
                    disabled: 'none',
                    calculator: {
                        class: null,
                        style: null
                    }
                };
            /**
             * true|false
             *      true        --> { enable: true, disabled: 'none' }
             *      false       --> { enabled: false }
             */
            if (isb(p)) {
                dft.enable = p;
                return dft;
            }
            /**
             * disabled|enabled|none|both
             *      'disabled'  --> { enable: true, disabled: true }
             *      'enabled'   --> { enable: true, disabled: false }
             *      'both'      --> { enable: true, disabled: 'both' }
             */
            if (iss(p) && 'disabled,enabled,both,none'.split(',').includes(p)) {
                dft.enable = true;
                dft.disabled = 'both,none'.split(',').includes(p) ? p : p==='disabled';
                return dft;
            }
            //传入完整的 {}
            if (iso(p)) {
                p = this.$extend({}, dft, p);
                return p;
            }

            return dft;
        },
        //从子系统完整参数中，获取对应的方法名 返回 null|string|function|{enabled:..., disabled:...}
        styParseSubCalculator(sub='size', p={}, elm='root', calc='class') {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o);
            if (!iso(p) || !is.defined(p.enable)) return null;
            let dis = p.disabled || 'none',
                calcs = p.calculator || {},
                cf = (iso(calcs) && is.defined(calcs[calc])) ? calcs[calc] : null,
                mulcf = is.plainObject(cf) && is.defined(cf.enabled) && is.defined(cf.disabled);
            //使用默认 方法
            if (!is(cf, 'string,function') && !mulcf) {
                let dfn = `styCalc${elm.ucfirst()}${sub.ucfirst()}${calc.ucfirst()}`;
                if (dis!=='both') {
                    return dfn;
                } else {
                    //需要执行两个方法
                    return {
                        enabled: dfn,
                        disabled: `${dfn}Disabled`
                    };
                }
            }

            //指定了自定义方法
            //替换 {elm} {Elm} 的方法
            let rep = s => s.replace('{Elm}', elm.ucfirst()).replace('{elm}', elm);
            if (is.function(cf)) return cf;
            if (is.string(cf)) return rep(cf);
            let rtn = {};
            this.$each(cf, (f,n) => {
                if (is.string(f)) {
                    rtn[n] = rep(f);
                } else if (is.function(f)) {
                    rtn[n] = f;
                } else {
                    rtn = null;
                    return false;
                }
            });
            return rtn;
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
         */
        //size 子系统
        styCalcRootSizeClass() {
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
        styCalcRootSizeStyle() {return {};},
        //color 子系统
        styCalcRootColorClass() {
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
        styCalcRootColorStyle() {return {};},
        //switch 子系统 在 disabled 状态不同时有不同的计算方法
        styCalcRootSwitchClass() {
            let is = this.$is,
                enableSws = this.sty.switch,
                clss = [];
            if (is.plainObject(enableSws) && !is.empty(enableSws)) {
                this.$each(enableSws, (swen, sw) => {
                    //if (swen !== true) return true;
                    if (swen === false) return true;
                    //如果 switch 名称以 :disabled 结尾，则截取 sw
                    if (sw.endsWith(':disabled')) {
                        //截掉 :disabled 部分
                        sw = sw.slice(0,-9);
                    }
                    //根据此开关值，生成 样式类[]
                    let swcls = this.stySwitchClasses(sw, swen);
                    if (!is.array(swcls) || swcls.length<=0) return true;
                    //合并
                    clss.push(...swcls);
                });
            }
            return clss;
        },
        styCalcRootSwitchClassDisabled() {
            let is = this.$is,
                enableSws = this.sty.switch,
                clss = [];
            if (is.plainObject(enableSws) && !is.empty(enableSws)) {
                this.$each(enableSws, (swen, sw) => {
                    //if (swen !== true) return true;
                    if (swen === false) return true;
                    //如果 switch 名称是 :disabled 结尾，则跳过
                    if (sw.endsWith(':disabled')) return true;
                    //根据此开关值，生成 样式类[]
                    let swcls = this.stySwitchClasses(sw, swen);
                    if (!is.array(swcls) || swcls.length<=0) return true;
                    //合并
                    clss.push(...swcls);
                });
            }
            return clss;
        },
        styCalcRootSwitchStyle() {return {};},
        styCalcRootSwitchStyleDisabled() {return {};},
        //animate 子系统
        styCalcRootAnimateClass() {
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
        styCalcRootAnimateStyle() {return {};},

        

        /**
         * 根据自定义 样式开关的 值，生成要插入 class[] 中的 样式类 []
         * @param {String} swn 样式开关名
         * @param {Boolean|String} def 在 sty.switch 中定义的开关键的值
         * @return {Array} 要插入 class[] 中的 样式类 []
         */
        stySwitchClasses(swn, def) {
            if (!this.$is.string(swn) || swn === '') return [];
            let is = this.$is,
                //组件样式类前缀
                pre = this.sty.prefix,
                //switch 开关变量名 转为 样式类名  fooBar --> foo-bar
                swk = '',
                //开关值
                swv = null;

            if (!swn.includes('.')) {
                //普通类型 switch
                //传入的 props.swn 值 或 data.swn 中保存的值
                swv = this[swn] || null;
                //switch 开关变量名 转为 样式类名  fooBar --> foo-bar
                swk = (!is.string(def) && def!=='') ? swn.toSnakeCase('-') : def.toSnakeCase('-');
            } else {
                //开关名是 监听的 data 中的键名
                swv = this.$cgy.loget(this.$data, swn);
                //switch 开关变量名 转为 样式类名  fooBar.jazTom --> foo-bar-jaz-tom
                swk = (!is.string(def) && def!=='') ? swn.split('.').map(i=>i.toSnakeCase('-')).join('-') : def.toSnakeCase('-');
            }

            //开关值 只能是 Boolean|String
            if (!is.boolean(swv) && !is.string(swv)) return [];

            //针对 Boolean 类型
            if (is.boolean(swv)) {
                if (swv !== true) return [];
                return [`${pre}-${swk}`];
            }

            //针对 String 类型
            if (is.string(swv)) {
                if (swv === '') return [];
                //开关值转为 foo-bar 形式
                swv = swv.toSnakeCase('-');
                return [`${pre}-${swk}`, `${pre}-${swk}-${swv}`];
            }

            //默认返回空值
            return [];
        },

    }
}