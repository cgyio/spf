import mixinAutoProps from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/auto-props.js';
import mixinBase from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/base.js';
import mixinBaseParent from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/base-parent.js';
import mixinBaseDynamic from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/base-dynamic.js';



/**
 * 合并资源 icon.js
 * !! 不要手动修改 !!
 */




const defineSpfIcon = {
    mixins: [mixinAutoProps],
    props: {
        //图标名称，来自加载的图标包，在 cssvar 中指定
        //指定为 -empty- 则显示一个空图标，占据相应尺寸，不显示任何图标
        icon: {
            type: String,
            default: '-empty-'
        },

        //图表 shape 可选 round|fill|sharp
        shape: {
            type: String,
            default: 'round'
        },

        /**
         * spin 图标旋转 可选：false|true 或 self|wait|wifi...
         * false            不旋转
         * true             使用自旋转图标 spinner-spin
         * self             使用 animateTransform 旋转当前图标
         * wait|wifi...     使用自旋转图标 spinner-wait|wifi...
         */
        spin: {
            type: [Boolean, String],
            default: false
        },

        //spin dura
        spinDura: {
            type: String,
            default: '2s'
        },
    },
    data() {return {
            
        //覆盖 auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: 'spf-icon',
                }
            },
            prefix: 'icon',
            csvk: {
                size: 'icon',
                color: 'fc',
            },
            sub: {
                size: true,
                color: true,
            },
            extra: {
                'disabled #manual': true,
            },
            switch: {
                'autoExtra.disabled @root #style': 'opacity: .3; cursor: not-allowed;', 
            },
        },
        
    }},
    computed: {

        /**
         * icon
         */
        iconKey() {
            if (this.icon=='-empty-') return '-empty-';
            let is = this.$is,
                ui = this.$ui,
                icon = this.icon,
                shape = this.shape,
                spin = this.spin,
                //默认图标
                dft = `md-${shape}`;
            //如果指定了图标旋转
            if (spin !== false) {
                if (spin === true) return 'spinner-spin';
                if (spin !== 'self') {
                    dft = 'spinner';
                }
            }
            
            //获取实际图标数据
            let ico = ui.iconInSet(icon, dft);
            if (is.plainObject(ico)) {
                return ico.full;
            }
            return '-empty-';
        },

        //是否旋转当前图标，而不是使用自旋转图标
        spinSelf() {
            return this.spin === 'self';
        },

        //计算 spin 中心坐标
        spinCenter() {
            let fsz = this.sizePropVal,
                fsarr = this.$ui.sizeValToArr(fsz),
                fszn = fsarr[0],
                r = fszn/2;
            return ` ${r} ${r}`;
        },
    },
    methods: {

    }
}





defineSpfIcon.template = `<svg :class="autoComputedStr.root.class" :style="autoComputedStr.root.style" aria-hidden="true"><use v-if="iconKey!='-empty-'" v-bind:xlink:href="'#'+iconKey"><animateTransform v-if="spinSelf" attributeName="transform" attributeType="XML" type="rotate" :from="'0'+spinCenter" :to="'360'+spinCenter" :dur="spinDura" repeatCount="indefinite" /></use></svg>`;

if (defineSpfIcon.computed === undefined) defineSpfIcon.computed = {};
defineSpfIcon.computed.profile = function() {return {};}










/**
 * 合并资源 icon-logo.js
 * !! 不要手动修改 !!
 */




const defineSpfIconLogo = {
    mixins: [mixinBase],
    props: {
        //图标名称，来自加载的图标包，在 cssvar 中指定
        /**
         * logo 图标名称
         * !! 如果图标有 -light|-dark 两种模式，将会自动根据 主题明暗模式 切换
         */
        icon: {
            type: String,
            default: '-empty-'
        },

        /**
         * logo 默认为 line 长方形
         * 如果是 正方形 logo 需指定此参数
         */
        square: {
            type: Boolean,
            default: false
        },

        /**
         * logo 默认多色图标，要显示为单色 需要设置此参数为 true
         * !! 需要同时指定 color 参数
         */
        singleColor: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: ['spf-icon-logo'],
                }
            },
            prefix: 'icon',
            sub: {
                size: true,
                color: true,
            },
            switch: {
                //启用开关
                square: true,
                singleColor: true,
            },
            csvKey: {
                size: 'icon',
                color: 'fc',
            },
        },
        
    }},
    computed: {

        /**
         * icon
         */
        iconKey() {
            if (this.icon=='-empty-') return '-empty-';
            //如果图标名 以 -dark|-light 结尾，且当前启用了 主题明暗模式 则自动切换
            let ico = this.icon,
                thc = this.$ui.theme,
                the = thc.enable,
                spt = thc.supportDarkMode,
                dark = thc.inDarkMode;
            if (
                the && spt &&
                (ico.endsWith('-light') || ico.endsWith('-dark'))
            ) {
                let icn = ico.split('-').slice(0, -1).join('-');
                return `${icn}-${dark ? 'dark' : 'light'}`;
            }
            return ico;
        },

    },
    methods: {

    }
}





defineSpfIconLogo.template = `<svg :class="styComputedClassStr.root" :style="styComputedStyleStr.root" aria-hidden="true"><use v-if="icon!='-empty-'" v-bind:xlink:href="'#'+iconKey"></use></svg>`;

if (defineSpfIconLogo.computed === undefined) defineSpfIconLogo.computed = {};
defineSpfIconLogo.computed.profile = function() {return {};}










/**
 * 合并资源 button-group.js
 * !! 不要手动修改 !!
 */




const defineSpfButtonGroup = {
    mixins: [mixinBase],
    model: {
        prop: 'active',
        event: 'change'
    },
    props: {

        /**
         * 当前选中的 button name
         */
        active: {
            type: [String, Number],
            default: ''
        },

        //title
        title: {
            type: String,
            default: ''
        },

        /**
         * 样式开关
         */
        /**
         * radius 圆角类型
         * 可选值： normal(默认) | pill | sharp
         */
        radius: {
            type: String,
            default: 'normal'
        },
        /**
         * effect 填充效果
         * 可选值：  normal(默认) | fill | plain
         */
        effect: {
            type: String,
            default: 'plain'
        },
        /**
         * 是否整行显示
         */
        fullLine: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
            
        /**
         * 覆盖 base-style 样式系统参数
         */
        styCalculators: {
            class: {
                //root 是计算根元素的 class 样式类，必须指定
                root: 'styCalcRootClass',
            },
            style: {
                //root 是计算根元素的 css 样式，必须指定
                root: 'styCalcRootStyle',
            }
        },
        styInit: {
            class: {
                //根元素
                root: ['spf-btn-group'],
            },
            style: {
                //根元素
                root: {},
                //icon
                icon: {},
            },
        },
        styCssPre: 'btns',
        styEnable: {
            size: true,
            color: true,
            animate: false,
        },
        stySwitches: {
            //启用 下列样式开关
            radius: true,
            effect: true,
            fullLine: true,
        },
        styCsvKey: {
            size: 'btn',
            color: 'fc',
        },

        //mouse 状态
        mouse: {
            enter: false,
            down: false,
            clicking: false,    //for debounce 点击按钮防抖
        },
    }},
    computed: {

    },
    methods: {

        /**
         * 定义 icon 的样式计算方法，用于计算 icon 组件的 size 参数
         * @return {Object} css{}
         */
        styCalcIconStyle() {
            let is = this.$is,
                //按钮尺寸在 $ui.cssvar.size 中的定义 {}
                szd = this.$ui.cssvar.size.btn,
                //按钮对应文字的 size 定义 {}
                fszd = this.$ui.cssvar.size['btn-fs'],
                //当前输入的 size 参数类型
                sztp = this.sizePropType,
                //按钮文字 size
                fszv = '',
                //计算得到的样式
                rtn = {};

            if ('str,key'.split(',').includes(sztp)) {
                //通过 尺寸字符串 或 尺寸 key 定义的按钮尺寸
                let sz = this.size,
                    szk = '';
                if (sztp === 'key') szk = sz;
                if (sztp === 'str') szk = this.sizeStrToKey;
                //直接取得 按钮文字的 size 100px 形式
                fszv = fszd[szk];
            } else {
                //通过直接输入 尺寸字符串 定义的 按钮尺寸
                let szv = this.sizePropVal,
                    szarr = this.sizeToArr(szv),
                    //得到按钮尺寸数字
                    szn = szarr[0];
                //按钮文字 size 是 按钮尺寸的 0.5
                fszv = (szn*0.5)+'px';
            }
            
            //将得到的 按钮文字 size 转为 icon size
            let fszarr = this.sizeToArr(fszv),
                //数字
                iszn = fszarr[0],
                //是否 props.stretch === square && props.popout === true
                pot = this.stretch === 'square' && this.popout === true;
            if (pot) {
                iszn = iszn * 1.6;
            } else {
                iszn = iszn * 1.4;
            }

            //将得到的 icon size 写入 {}
            return {
                fontSize: iszn+'px',
            };
        },

        //click 事件
        //防抖 debounce
        whenBtnClick(event) {
            if (this.disabled) return false;
            if (this.mouse.clicking!==true) {
                this.mouse.clicking = true;
                event.targetComponent = this;
                //this.$ev('click', this, event);
                this.$wait(500).then(()=>{
                    this.mouse.clicking = false;
                });
            }
        },

        //mouse 事件
        whenMouseEnter(event) {
            if (this.disabled) return false;
            this.mouse.enter = true;
            event.targetComponent = this;
            //this.$ev('mouse-enter', this, event);
        },
        whenMouseLeave(event) {
            if (this.disabled) return false;
            this.mouse.enter = false;
            event.targetComponent = this;
            //this.$ev('mouse-leave', this, event);
        },
        whenMouseDown(event) {
            if (this.disabled) return false;
            this.mouse.down = true;
            event.targetComponent = this;
            //this.$ev('mouse-down', this, event);
        },
        whenMouseUp(event) {
            if (this.disabled) return false;
            this.mouse.down = false;
            event.targetComponent = this;
            //this.$ev('mouse-up', this, event);
        },
    }
}





defineSpfButtonGroup.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><slot></slot></div>`;

if (defineSpfButtonGroup.computed === undefined) defineSpfButtonGroup.computed = {};
defineSpfButtonGroup.computed.profile = function() {return {};}










/**
 * 合并资源 button.js
 * !! 不要手动修改 !!
 */




const defineSpfButton = {
    mixins: [mixinAutoProps],
    props: {

        //图标名称，来自加载的图标包，在 cssvar 中指定
        icon: {
            type: String,
            default: '-empty-'
        },
        //图标形状
        iconShape: {
            type: String,
            default: 'round',
        },
        //图标旋转，参数要求与 icon 组件相同
        spin: {
            type: [Boolean, String],
            default: false
        },
        //icon 额外的样式
        iconClass: {
            type: String,
            default: ''
        },
        iconStyle: {
            type: [String, Object],
            default: ''
        },

        //按钮文字
        label: {
            type: String,
            default: ''
        },
        //label 额外样式
        labelClass: {
            type: String,
            default: ''
        },
        labelStyle: {
            type: [String, Object],
            default: ''
        },

        /**
         * 是否显示 关闭按钮
         */
        closeable: {
            type: Boolean,
            default: false
        },


        /**
         * 样式开关
         */
        //是否右侧显示图标
        iconRight: {
            type: Boolean,
            default: false
        },
        
        /**
         * shape 形状
         * 可选值： sharp | round(默认) | pill | circle
         * !! 覆盖 base-style 中定义的默认值
         */
        shape: {
            type: String,
            default: 'round'
        },
        /**
         * hoverable 
         * !! 覆盖 base-style 中定义的默认值
         */
        hoverable: {
            type: Boolean,
            default: true
        },

        //link 链接形式按钮
        //link: {
        //    type: Boolean,
        //    default: false
        //},
        //no-gap 按钮之间紧密排列，无间隙
        noGap: {
            type: Boolean,
            default: false
        },

        /**
         * 特殊样式
         */
        //在 单元格中
        incell: {
            type: Boolean,
            default: false
        },
        //作为 标题
        astitle: {
            type: Boolean,
            default: false
        },

        /**
         * 按钮防抖
         */
        //启用防抖 
        debounce: {
            type: Boolean,
            default: false
        },
        //启用组件外部的 fullfilled 标记
        fullfilled: {
            type: Boolean,
            default: false
        },
        //fullfilled == false 时，使用此处定义的等待时间
        debounceDura: {
            type: Number,
            default: 500,   //默认等待 500ms
        },
        //指定一个组件外部的 fullfilled 标记
        fullfilledWhen: {
            type: Boolean,
            default: false
        }
    },
    data() {return {
            
        //覆盖 auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: 'spf-btn btn flex-x flex-x-center',
                },
                icon: {
                    class: '',
                    props: {},
                },
                btn: {
                    class: '',
                    props: {
                        icon: 'close',
                        type: 'danger',
                        effect: 'popout',
                        shape: 'circle',
                    },
                },
                label: {
                    class: '',
                },
            },
            prefix: 'btn',
            csvk: {
                size: 'btn',
                color: 'fc',
            },
            sub: {
                size: true,
                color: true,
                animate: true,
            },
            extra: {
                stretch: 'auto',
                //关闭 tightness
                tightness: false,
                shape: 'round',
                effect: 'normal',
                noGap: true,
                incell: true,
                astitle: true,

                //自定义 样式类
                'active #.active': true,
                'disabled #.disabled': true,

                //手动处理
                'hoverable #manual': true,
            },
            switch: {
                root: {
                    '!autoExtra.disabled': {
                        'autoExtra.hoverable #class': '.hoverable',
                        'stage.pending': '.pending',
                        'stage.press': '.pressed',
                        'fullfilledWhen': '.fullfilled'
                    },
                },

                icon: {
                    '!isEmptyIcon #props': {
                        icon: '{{icon}}',
                        shape: '{{iconShape}}',
                        spin: '{{iconSpin==="" ? false : iconSpin}} [String,Boolean]',
                        size: '{{isSquare ? size : autoSizeShift("s1","icon")}}',
                    },
                    '!isEmptyIcon': {
                        '["str","key"].includes(sizePropType)!==true #style': 'fontSize: {{$ui.sizeValMul(sizePropVal, 0.5)}};'
                    },
                },

                btn: {
                    'closeable #props': {
                        size: '{{autoSizeShift("s2","btn")}}',
                    },
                    'closeable': {
                        'iconRight #style': 'margin-left:-0.8em; margin-right:1.5em;',
                        '!iconRight #style': 'margin-right:-0.8em; margin-left:1.5em;',
                    },
                },
            },
        },

        //sizeTest: 'tiny',

        /**
         * 按钮点击状态
         */
        stage: {
            ready: true,
            enter: false,
            press: false,
            //debounce
            pending: false,
            //fullfilled: false,
        },
    }},
    computed: {
        //判断是否 空 icon
        isEmptyIcon() {
            let icon = this.icon,
                is = this.$is;
            return !(is.string(icon) && icon !== '-empty-' && icon !== '');
        },
        //判断是否 空 label
        isEmptyLabel() {
            let label = this.label,
                is = this.$is;
            return !(is.string(label) && label !== '');
        },
        //判断是否 方形|圆形 按钮
        isSquare() {
            let shape = this.shape;
            return shape==='circle' || shape.endsWith('square');
            //return this.stretch==='square' || this.shape==='circle';
        },

        //stage 状态是否处于 pending 
        stagePending() {
            return this.debounce && this.stage.pending === true;
        },

        //根据 spin 参数以及 debounce 状态，确定要传给 icon 组件的实际 spin 参数
        iconSpin() {
            let is = this.$is,
                spin = this.spin;
            if (!this.debounce) return spin;
            if (this.stagePending) {
                if (is.boolean(spin)) return true;
                return spin;
            }
            return false;
        },
    },
    watch: {
        //外部 fullfilled 标记变化
        fullfilledWhen(nv, ov) {
            //处于 pending 阶段且 fullfilled 标记变为 true
            if (this.stagePending && nv === true) {
                //修改 pending 标记
                this.stage.pending = false;
                //this.stage.fullfilled = true;
            }
        },
    },
    methods: {

        //click 事件
        whenBtnClick(event) {
            if (this.disabled || this.stagePending) return false;
            if (this.debounce) {
                //pending 标记
                this.stage.pending = true;
                //this.stage.fullfilled = false;
                if (this.fullfilled === true) {
                    //启用了外部 fullfilled 标记，则检查此标记
                    if (this.fullfilledWhen === true) {
                        this.stage.pending = false;
                        //this.stage.fullfilled = true;
                        //不触发 click 事件
                        return false;
                    }
                } else {
                    //未启用外部 fullfilled 标记，则 setTimeout
                    this.$wait(this.debounceDura).then(()=>{
                        this.stage.pending = false;
                        //this.stage.fullfilled = true;
                    });
                }
            }

            //触发 click 事件，如果启用了 debounce 以及外部 fullfilled 标记，这将触发外部 async 方法异步修改外部标记
            //event.targetComponent = this;
            return this.$emit('click');
        },

        //mouse 事件
        whenMouseEnter(event) {
            if (this.disabled || this.stagePending) return false;
            //进入标记
            this.stage.enter = true;
            //event.targetComponent = this;
            //this.$ev('mouse-enter', this, event);
            return this.$emit('mouse-enter');
        },
        whenMouseLeave(event) {
            if (this.disabled) return false;
            //进入标记
            this.stage.enter = false;
            //event.targetComponent = this;
            //this.$ev('mouse-leave', this, event);
            return this.$emit('mouse-leave');
        },
        whenMouseDown(event) {
            if (this.disabled || this.stagePending) return false;
            //按下标记
            this.stage.press = true;
            //event.targetComponent = this;
            //this.$ev('mouse-down', this, event);
            return this.$emit('mouse-down');
        },
        whenMouseUp(event) {
            if (this.disabled) return false;
            //按下标记
            this.stage.press = false;
            //event.targetComponent = this;
            //this.$ev('mouse-up', this, event);
            return this.$emit('mouse-up');
        },

        //close按钮
        whenClose(event) {
            return this.$emit('close');
        },
    }
}





defineSpfButton.template = `<div :class="autoComputedStr.root.class" :style="autoComputedStr.root.style" @click="whenBtnClick" @mouseenter="whenMouseEnter" @mouseleave="whenMouseLeave" @mousedown="whenMouseDown" @mouseup="whenMouseUp"><spf-icon v-if="!iconRight && !isEmptyIcon" v-bind="autoComputed.icon.props" :root-class="autoComputedStr.icon.class" :root-style="autoComputedStr.icon.style"></spf-icon><spf-button v-if="iconRight && closeable" v-bind="autoComputed.btn.props" :root-class="autoComputedStr.btn.class" :root-style="autoComputedStr.btn.style" @click="whenClose"></spf-button><label v-if="!isEmptyLabel && !isSquare" :class="autoComputedStr.label.class" :style="autoComputedStr.label.style">{{label}}</label><spf-button v-if="!iconRight && closeable" v-bind="autoComputed.btn.props" :root-class="autoComputedStr.btn.class" :root-style="autoComputedStr.btn.style" @click="whenClose"></spf-button><spf-icon v-if="iconRight && !isEmptyIcon" v-bind="autoComputed.icon.props" :root-class="autoComputedStr.icon.class" :root-style="autoComputedStr.icon.style"></spf-icon></div>`;

if (defineSpfButton.computed === undefined) defineSpfButton.computed = {};
defineSpfButton.computed.profile = function() {return {};}










/**
 * 合并资源 bar.js
 * !! 不要手动修改 !!
 */




const defineSpfBar = {
    mixins: [mixinAutoProps],
    props: {
        /**
         * stretch 按钮延伸类型
         * 可选值： auto | square | grow | row(默认)
         * !! 覆盖 base-style 中定义的默认值
         */
        //stretch: {
        //    type: String,
        //    default: 'row'
        //},
        /**
         * effect 填充效果
         * 可选值：  normal | fill | plain | popout(默认)
         * !! 覆盖 base-style 中定义的默认值
         */
        //effect: {
        //    type: String,
        //    default: 'popout'
        //},

        //图标名称，来自加载的图标包，在 cssvar 中指定
        icon: {
            type: String,
            default: ''
        },
        //是否右侧 icon
        iconRight: {
            type: Boolean,
            default: false
        },

        //行末按钮 指定 按钮 icon 默认空值 表示不显示
        suffixBtn: {
            type: String,
            default: ''
        },

        //是否内置 content 整行容器
        innerContent: {
            type: Boolean,
            default: false
        },
        //是否在内置的 整行容器中启用 flex-y 容器
        innerContentRows: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        //覆盖 auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: 'spf-bar bar',
                },
                iconbar: {
                    props: {
                        stretch: 'auto',
                        tightness: 'none',
                    },
                },
                btnbar: {
                    props: {
                        stretch: 'auto'
                    },
                    accept: {
                        extra: ['tightness'],
                    },
                },
                icon: {
                    class: '',
                },
                btn: {
                    props: {
                        shape: 'circle',
                        effect: 'popout',
                    },
                },
                cnt: {
                    class: 'bar-cnt flex-x flex-no-shrink',
                    //style: 'height: 100%;'
                }
            },
            prefix: 'bar',
            csvk: {
                size: 'bar',
                color: 'bgc',
            },
            sub: {
                size: true,
                color: true,
                border: true,
            },
            extra: {
                stretch: 'row',
                tightness: 'normal',
                shape: 'sharp',
                effect: 'popout',
                innerContent: true,

                'active #.active': true,
                'disabled #.disabled': true,

                'hoverable #manual': true,
                'innerContentRows #manual': true,
            },
            switch: {
                root: {
                    '!disabled': {
                        'autoExtra.hoverable #class': '.hoverable',
                    }
                },

                innerContent: {
                    '!isEmptyIcon @iconbar #props': {
                        icon: '{{icon}}',
                        size: '{{size}}',
                    },
                    '!isEmptyBtn @btnbar #props': {
                        size: '{{size}}',
                        suffixBtn: '{{suffixBtn}}',
                    },
                    'innerContentRows @root @cnt #class': '.flex-y-start',
                    'innerContentRows @root #style': 'min-height: {{sizePropVal}};',
                    'innerContentRows @cnt #style': 'height: 100%;',
                },

                icon: {
                    '!isEmptyIcon #props': {
                        icon: '{{icon}}',
                        size: '{{size}}',
                    }
                },

                btn: {
                    '!isEmptyBtn #props': {
                        size: '{{autoSizeShift("s1","btn")}}',
                        icon: '{{suffixBtn}}',
                    },
                    'iconRight #style': 'margin-left: -0.8em; margin-right: 1.5em;',
                    '!iconRight #style': 'margin-right: -0.8em; margin-left: 1.5em;',
                },

                cnt: {
                    'autoAtom.flexY #class': '.flex-y-{{autoAtom.flexY}}',
                },
            },
        },
    }},
    computed: {
        //判断是否 空 icon
        isEmptyIcon() {
            let icon = this.icon,
                is = this.$is;
            return !(is.string(icon) && icon !== '');
        },
        //判断是否显示 行末 按钮
        isEmptyBtn() {
            let icon = this.suffixBtn,
                is = this.$is;
            return !(is.string(icon) && icon !== '-empty-' && icon !== '');
        },
    },
    methods: {
        //响应 bar 根元素 click
        whenBarClick(ev) {
            if (this.disabled!==true) {
                return this.$emit('click');
            }
        },
    }
}





defineSpfBar.template = `<div :class="autoComputedStr.root.class" :style="autoComputedStr.root.style" @click="whenBarClick"><template v-if="innerContent"><spf-bar v-if="!iconRight && !isEmptyIcon" v-bind="autoComputed.iconbar.props" :icon-props="autoComputed.icon.props" :icon-class="autoComputedStr.icon.class" :icon-style="autoComputedStr.icon.style"></spf-bar><spf-bar v-if="iconRight && !isEmptyBtn" v-bind="autoComputed.btnbar.props" :btn-props="autoComputed.btn.props" :btn-class="autoComputedStr.btn.class" :btn-style="autoComputedStr.btn.style" @suffix-btn-click="$emit('suffix-btn-click')"></spf-bar><div :class="autoComputedStr.cnt.class" :style="autoComputedStr.cnt.style"><slot v-bind="{autoSlotProps}"></slot></div><spf-bar v-if="!iconRight && !isEmptyBtn" v-bind="autoComputed.btnbar.props" :btn-props="autoComputed.btn.props" :btn-class="autoComputedStr.btn.class" :btn-style="autoComputedStr.btn.style" @suffix-btn-click="$emit('suffix-btn-click')"></spf-bar><spf-bar v-if="iconRight && !isEmptyIcon" v-bind="autoComputed.iconbar.props" :icon-props="autoComputed.icon.props" :icon-class="autoComputedStr.icon.class" :icon-style="autoComputedStr.icon.style"></spf-bar></template><template v-else><spf-icon v-if="!iconRight && !isEmptyIcon" v-bind="autoComputed.icon.props" :root-class="autoComputedStr.icon.class" :root-style="autoComputedStr.icon.style"></spf-icon><spf-button v-if="iconRight && !isEmptyBtn" v-bind="autoComputed.btn.props" :root-class="autoComputedStr.btn.class" :root-style="autoComputedStr.btn.style" @click="$emit('suffix-btn-click')"></spf-button><slot v-bind="{autoSlotProps}"></slot><spf-button v-if="!iconRight && !isEmptyBtn" v-bind="autoComputed.btn.props" :root-class="autoComputedStr.btn.class" :root-style="autoComputedStr.btn.style" @click="$emit('suffix-btn-click')"></spf-button><spf-icon v-if="iconRight && !isEmptyIcon" v-bind="autoComputed.icon.props" :root-class="autoComputedStr.icon.class" :root-style="autoComputedStr.icon.style"></spf-icon></template></div>`;

if (defineSpfBar.computed === undefined) defineSpfBar.computed = {};
defineSpfBar.computed.profile = function() {return {};}










/**
 * 合并资源 bar-sty.js
 * !! 不要手动修改 !!
 */



const defineSpfBarSty = {
    mixins: [mixinAutoProps],
    props: {
        
    },
    data() {return {
        auto: {
            element: {
                root: {
                    props: {},
                    accept: {
                        atom: true,
                        extra: true
                    }
                },
            },
            prefix: 'bar',
            csvk: {
                size: 'bar',
                color: 'fc',
            },
            extra: {
                disabled: true
            },
        }
    }},
    computed: {

    },
    methods: {

    }
}





defineSpfBarSty.template = `<spf-bar icon="settings" :icon-props="{ mg: 'xl', mgPo: 'r' }" inner-content mg="xl" mg-po="t" v-slot="{autoSlotProps: slotProps}"><spf-btn icon="settings" :icon-props="{ shape: 'sharp', icon: 'flag' }" label="设置" type="primary" closeable></spf-btn><spf-btn icon="settings" label="设置" type="danger" active no-gap></spf-btn><spf-btn icon="settings" label="设置" type="warn" shape="sharp" no-gap></spf-btn><spf-btn icon="settings" label="设置" type="cyan" effect="fill"></spf-btn><spf-btn icon="settings" label="设置" stretch="grow"></spf-btn></spf-bar>`;

if (defineSpfBarSty.computed === undefined) defineSpfBarSty.computed = {};
defineSpfBarSty.computed.profile = function() {return {};}










/**
 * 合并资源 block-collapse-item.js
 * !! 不要手动修改 !!
 */



const defineSpfBlockCollapseItem = {
    props: {
        //此折叠面板的 激活状态 v-model
        active: {
            type: Boolean,
            default: false
        },
        //此折叠面板的 name 对应的值
        name: {
            type: [String, Number, Boolean],
            default: ''
        },

        //header icon
        icon: {
            type: String,
            default: ''
        },
        //header title
        title: {
            type: String,
            default: ''
        },
        //

    },
    data() {return {
        //active 的 内部状态
        itemActive: false,
    }},
    computed: {},
    watch: {
        //外部传入的 active
        active: {
            handler(nv, ov) {
                this.toggleActive(this.active, false);
            },
            immediate: true,
        },
    },
    created() {
        this.updateToParentCollapseWhenCreated();
    },
    methods: {

        //获取当前折叠面板 item 所在的 collapse 组件 未找到返回 undefined
        async getParentCollapse() {
            let is = this.$is,
                isc = v => is.vue(v) && is.string(v.$options.name) && v.$options.name.endsWith('-block-collapse'),
                pc = this.$parent;
            while (!(is.undefined(pc) || isc(pc))) {
                pc = pc.$parent;
            }
            await this.$wait(10);
            return pc;
        },
        //collapse-item 组件创建时，将相关参数 写回 父组件 collapse，同时从父组件获取一些内部参数
        async updateToParentCollapseWhenCreated() {
            let is = this.$is,
                pc = await this.getParentCollapse();
            //console.log(pc);
            if (is.vue(pc)) {
                return await pc.addCollapseItem(this);
            }
        },
        //切换 active 状态
        toggleActive(active=false, emit=true) {
            console.log('toggle active');
            if (this.itemActive !== active) {
                this.itemActive = active;
                //触发事件
                if (emit) this.$emit('toggle-active', this.name, active);
            }
        },
    }
}


defineSpfBlockCollapseItem.template = `<spf-block with-header v-bind="$attrs"><template v-slot:header="{styProps}"><spf-bar v-if="title!==''" :size="styProps.size" :icon="icon" :color="styProps.color" :disabled="styProps.disabled" hoverable @click.native="toggleActive(!itemActive)"><span>{{title}}</span><span class="flex-1"></span><spf-icon :icon="itemActive ? 'keyboard-arrow-down' : 'keyboard-arrow-right'" color="fc-l2" size="small"></spf-icon></spf-bar></template><template v-if="itemActive" v-slot:default="{styProps}"><spf-block :bd-po="styProps.hasBd ? 't' : ''" :bdc="styProps.bdc"><template v-slot:default="{styProps}"><slot v-bind="{styProps}"></slot></template></spf-block></template></spf-block>`;

if (defineSpfBlockCollapseItem.computed === undefined) defineSpfBlockCollapseItem.computed = {};
defineSpfBlockCollapseItem.computed.profile = function() {return {};}




/**
 * 合并资源 block.js
 * !! 不要手动修改 !!
 */




const defineSpfBlock = {
    mixins: [mixinBase],
    props: {
        /**
         * stretch 容器水平延伸类型
         * 可选值： auto | square | grow | row(默认)
         * !! 覆盖 base-style 中定义的默认值
         */
        stretch: {
            type: String,
            default: 'row'
        },

        /**
         * 是否占满整个纵向空间  默认不占满
         * !! 需要区分当前 block 组件被包裹在 flex-x 还是 flex-y 的容器内部
         *      被包裹在 flex-y 容器内部 则：   grow = true
         *      被包裹在 flex-x 容器内部 则：   growInBar = true
         */
        grow: {
            type: Boolean,
            default: false
        },
        growInBar: {
            type: Boolean,
            default: false
        },

        /**
         * 是否在 grow == true 时启用 scroll-y
         * 可选  ''|thin|bold
         */
        scroll: {
            type: String,
            default: ''
        },

        /**
         * header|footer 区域
         */
        withHeader: {
            type: Boolean,
            default: false
        },
        withFooter: {
            type: Boolean,
            default: false
        },
        //header 组件（通常是 bar）额外参数
        headerParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        //header 额外的 class|style
        headerClass: {
            type: [String,Array],
            default: ''
        },
        headerStyle: {
            type: [String,Object],
            default: ''
        },
        //header 是否可点击，将影响 block-header-click 事件传递
        headerClickable: {
            type: Boolean,
            default: false
        },
        //footer 组件（通常是 bar）额外参数
        footerParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        //footer 额外的 class|style
        footerClass: {
            type: [String,Array],
            default: ''
        },
        footerStyle: {
            type: [String,Object],
            default: ''
        },
        //可额外指定 footer 的 size 默认 = styProps.size
        /*footerSize: {
            type: [String, Number],
            default: ''
        },*/

        /**
         * 是否在 block 组件内容外部自动包裹对应的组件 bar|block
         */
        innerContent: {
            type: Boolean,
            default: false
        },
        //innerContent == true 时，可以指定内部的包裹在 cnt 内容外的组件名称，默认 spf-block
        innerComponent: {
            type: String,
            default: 'spf-block'
        },
        //cnt 元素组件（通常是 block 组件）额外的参数
        cntParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        //cnt 额外的 class|style
        cntClass: {
            type: [String,Array],
            default: ''
        },
        cntStyle: {
            type: [String,Object],
            default: ''
        },

        //额外指定是否隐藏 cnt 内容，通常用于 折叠面板|菜单 等
        hideCnt: {
            type: Boolean,
            default: false
        },
        //在 innerContent == true 且 withHeader||withFooter 时，切换 hideCnt 时的动画效果，基于 animate.css
        toggleCntAnimate: {
            type: Array,
            default: () => [
                //enter 进入
                'animate__animated animate__fadeIn',
                //leave 离开
                'animate__animated animate__fadeOut',
            ]
        },

        //内容组件是否关联到 root 元素的 边框状态
        borderBind: {
            type: Boolean,
            default: false
        },


        /**
         * 是否在默认插槽外自动包裹 spf-block 组件
         * 仅在 withHeader || withFooter 时生效
         * 默认 false 由用户自行向默认插槽插入 需要的组件
         */
        //innerBlock: {
        //    type: Boolean,
        //    default: false
        //},

    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: 'spf-block block flex-y flex-x-start flex-no-shrink',
                    cnt: '',
                    header: '',
                    footer: ''
                },
                style: {
                    cnt: 'min-height: 0px;',
                    header: '',
                    footer: ''
                }
            },
            prefix: 'block',
            group: {
                //新增组开关
                innerContent: true,
                innerBorder: true,
            },
            sub: {
                size: true,
                color: true,
                //animate: 'disabled:false',
            },
            switch: {
                //启用 下列样式开关
                //effect:     '.bar-effect effect-{swv}',
                stretch:    '.stretch-{swv}',
                tightness:  '.block-tightness tightness-{swv}',
                shape:      '.block-shape shape-{swv}',
                //'hoverable:disabled': '.hoverable',
                //active:     '.active',
                grow:       '.flex-1',
                growInBar:  'height: 100%;',
                //根元素上挂载 scroll-y
                rootScroll: '.scroll-y scroll-{swv}',

                //针对 cnt 和 footer 元素
                'bd:inner-border@cnt@footer': '.bd-{swv} bd-po-t',
                'bdPo:inner-border@cnt@footer': '.bd-m bd-po-t',
                'bdc:inner-border@cnt@footer': '.bd-m bd-po-t bdc-{swv}',
                //'grow:inner-content@cnt': 'height: {swv@get,cntHeight};',
                'hideCnt@cnt': 'height: {swv@if,0px,unset};',
            },
            csvKey: {
                size: 'bar',
                color: 'bgc',
            },
        },

        //内部 header|footer|cnt 子组件的默认 params
        headerDefaultParams: {
            size: this.size,
        },
        footerDefaultParams: {
            size: 'xl',
        },
        cntDefaultParams: {

        },
    }},
    computed: {
        //判断 root 元素是否被指定为 占满纵向空间
        rootGrow() {
            return this.grow || this.growInBar;
        },
        //是否在 根元素上挂 scroll-y 类，返回：''|thin|bold
        rootScroll() {
            let grow = this.rootGrow,
                scr = this.scroll,
                innc = this.innerContent;
            if (!grow || innc) return '';
            let wh = this.withHeader,
                wf = this.withFooter;
            if (wh || wf) return '';
            return scr;
        },
        //是否在 内部 block 组件上挂 scroll-y 类，返回：''|thin|bold
        innerBlockScroll() {
            let grow = this.rootGrow,
                scr = this.scroll,
                innc = this.innerContent;
            if (!grow || !innc) return '';
            let wh = this.withHeader,
                wf = this.withFooter;
            if (wh || wf) return scr;
            return '';
        },

        /**
         * 处理 header|footer|cnt-params 合并默认参数 与 外部传入的，通过 v-bind 传入内部子组件
         */
        headerCustomParams() {
            return this.$extend({}, this.headerDefaultParams, this.headerParams);
        },
        footerCustomParams() {
            return this.$extend({}, this.footerDefaultParams, this.footerParams);
        },
        cntCustomParams() {
            return this.$extend({}, this.cntDefaultParams, this.cntParams);
        },

        //占满纵向空间时 自动计算 cnt 元素的 height
        //!! 为 cnt 元素增加 min-height: 0px; height: unset; 即可触发子组件的 scroll  因此此处不需要计算高度
        __cntHeight() {
            let is = this.$is,
                isu = n => is.numeric(n),
                el = this.$el,
                grow = this.grow,
                hc = this.hideCnt;
            if (!grow || hc) return '0px';
            //根元素还未渲染完成时
            if (!is.elm(el) || !is.defined(el.offsetHeight) || el.offsetHeight<=0) return 'unset';
            let h = el.offsetHeight,    //取得当前组件的 总高
                wh = this.withHeader,
                wf = this.withFooter,
                ui = this.$ui,
                csv = ui.cssvar,
                ch = h+'px';
            //总高度 依次减去 header|footer 高度
            this.$each(['header','footer'], (elm,i) => {
                let wk = `with${elm.ucfirst()}`;
                //跳过未启用的 header|footer
                if (!is.defined(this[wk]) || this[wk]!==true) return true;
                let ps = this[`${elm}Params`] || {},
                    sz = is.defined(ps.size) ? ps.size : this.size,
                    szv = ui.sizeVal(sz, 'bar');
                if (!isu(szv)) return true;
                //总高减去 header|footer
                ch = ui.sizeValSub(ch, szv);
            });
            return ch;
        },

        //判断内部元素是否需要自动生成 边框参数
        innerBorder() {
            return this.innerContent && this.borderBind;
        },
    },
    methods: {
        //响应 header click 动作
        whenHeaderClick(evt) {
            if (this.withHeader && this.headerClickable) {
                return this.$emit('header-click');
            }
        },
    }
}





defineSpfBlock.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><template v-if="innerContent"><spf-bar v-if="withHeader" :hoverable="headerClickable" v-bind="headerCustomParams" :custom-class="styComputedClassStr.header" :custom-style="styComputedStyleStr.header" @click="whenHeaderClick" v-slot="{styProps: headerStyProps}"><slot name="header" v-bind="{styProps: headerStyProps}"></slot></spf-bar><template v-if="withHeader || withFooter"><!--<transition name="spf-block-inner-trans" :enter-active-class="toggleCntAnimate[0]" :leave-active-class="toggleCntAnimate[1]" v-if="!hideCnt">--><spf-block v-if="innerComponent==='spf-block'" :grow="rootGrow" :scroll="innerBlockScroll" :size="styProps.size" v-bind="cntCustomParams" :custom-class="styComputedClassStr.cnt" :custom-style="styComputedStyleStr.cnt" v-slot="{styProps: cntStyProps}"><slot v-bind="{styProps: cntStyProps}"></slot></spf-block><template v-else><component v-if="innerComponent!==''" :is="innerComponent" v-bind="cntCustomParams" :custom-class="styComputedClassStr.cnt" :custom-style="styComputedStyleStr.cnt" v-slot="{styProps: cntStyProps}"><slot v-bind="{styProps: cntStyProps}"></slot></component><template v-else><slot v-if="!hideCnt" v-bind="{ styProps, cntParams: cntCustomParams, cntClass: styComputedClass.cnt, cntStyle: styComputedStyle.cnt }"></slot></template></template><!--</transition>--></template><template v-else><slot v-if="!hideCnt" v-bind="{ styProps, cntParams: cntCustomParams, cntClass: styComputedClass.cnt, cntStyle: styComputedStyle.cnt }"></slot></template><spf-bar v-if="withFooter" v-bind="footerCustomParams" :custom-class="styComputedClassStr.footer" :custom-style="styComputedStyleStr.footer" v-slot="{styProps: footerStyProps}"><slot name="footer" v-bind="{styProps: footerStyProps}"></slot></spf-bar></template><template v-else><template v-if="withHeader"><slot name="header" v-bind="{ styProps, headerParams: headerCustomParams, headerClass: styComputedClass.header, headerStyle: styComputedStyle.header }"></slot></template><slot v-if="!hideCnt" v-bind="{ styProps, cntParams: cntCustomParams, cntClass: styComputedClass.cnt, cntStyle: styComputedStyle.cnt }"></slot><template v-if="withFooter"><slot name="footer" v-bind="{ styProps, footerParams: footerCustomParams, footerClass: styComputedClass.footer, footerStyle: styComputedStyle.footer }"></slot></template></template></div>`;

if (defineSpfBlock.computed === undefined) defineSpfBlock.computed = {};
defineSpfBlock.computed.profile = function() {return {};}










/**
 * 合并资源 block-collapse.js
 * !! 不要手动修改 !!
 */



const defineSpfBlockCollapse = {
    model: {
        prop: 'value',
        event: 'change'
    },
    props: {
        //v-model 当前折叠面板选中的 item-name 1 个 或 多个
        value: {
            type: Array,
            default: () => []
        },
    },
    data() {return {
        //根 spf-block 组件的 styProps
        blockStyProps: {},

        /**
         * 缓存所有 collapse-item {title,name,icon}
         */
        items: {
            /*
            item-name: {
                title: '',
                name: '',
                icon: '',
                active: false,
            },
            ...
            */
        },
    }},
    computed: {

        //获取 this.items 中包含的所有 collapse-item 组件实例的 name 数组，按 items[name].idx 排序
        itemList() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                items = this.items,
                its = [];
            if (!iso(items)) return [];
            this.$each(items, (item, name) => {
                its.push({
                    name,
                    idx: item.idx
                });
            });
            //按 item.idx 排序
            its.sort((a,b) => a.idx - b.idx);
            //输出 name 数组
            return its.map(i => i.name);
        },
        //获取所有 active == true 的 collapse-item name [] 
        activeItemList() {
            return this.getActiveItemList();
        },
    },
    watch: {
        //value 监听外部传入的 值
        value: {
            handler(nv, ov) {
                let is = this.$is,
                    iso = o => is.plainObject(o) && !is.empty(o),
                    isa = a => is.array(a) && a.length>0,
                    val = this.value,
                    items = this.items;
                //if (!iso(items)) return false;
                if (!isa(val)) {
                    if (iso(items)) {
                        //取消所有 item 的 active 状态
                        this.$each(items, (item, name) => {
                            if (item.active) {
                                this.$set(this.items[name], 'active', false);
                                item.ins.toggleActive(false, false);
                            }
                        });
                    }
                } else {
                    //先创建
                    this.$each(val, name => {
                        if (!iso(items[name])) {
                            this.$set(this.items, name, Object.assign({}));
                        }
                    });
                    //根据 val 中包含的 name 设置 active
                    this.$each(this.items, (item, name) => {
                        let active = val.includes(name);
                        if (item.active !== active) {
                            this.$set(this.items[name], 'active', active);
                            if (item.ins && is.vue(item.ins)) item.ins.toggleActive(active, false);
                        }
                    });
                }
                return true;
            },
            immediate: true,
        },
    },
    methods: {
        //响应此 collapse 组件的根组件 spf-block 的 block-created 事件
        setBlockStyProps(styProps) {
            console.log(styProps);
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isd = d => is.defined(d),
                ps = {};
            if (iso(styProps)) {
                this.$each(styProps, (v,k)=>{
                    //需要透传的 styProps
                    //边框样式
                    if (k==='hasBd') {
                        ps.bd = styProps.bd;
                        ps.bdPo = 'b';  //styProps.bdPo
                        ps.bdc = styProps.bdc;
                        if (styProps.bdc!=='') {
                            ps.color = styProps.bdc;
                        }
                        return true;
                    }
                    //size
                    if(k==='size') {
                        ps.size = styProps.size;
                        return true;
                    }
                    //color
                    if (k==='color') {
                        ps.color = styProps.color;
                        return true;
                    }
                    //animate
                    if (k.startsWith('animate')) {
                        ps[k] = v;
                        return true;
                    }
                });
                if (iso(ps)) {
                    this.blockStyProps = Object.assign({}, ps);
                }
            }
        },

        //在 collapse-item 创建阶段，将 collapse-item 组件实例添加到 this.items 
        async addCollapseItem(item) {
            let is = this.$is;
            //排除不合法 或 已添加过的 item
            if (!is.vue(item)) return false;
            let oitem = is.defined(this.items[item.name]) ? this.items[item.name] : {};
            //插入 items {}
            this.$set(this.items, item.name, Object.assign(oitem, {
                name: item.name,
                title: item.title,
                icon: item.icon,
                //组件实例 uid 用于排序
                idx: item._uid,
                //组件实例
                ins: item,
            }));
            await this.$wait(10);
            //激活状态
            if (!is.defined(this.items[item.name].active)) {
                this.$set(this.items[item.name], 'active', false);
            }
            if (this.items[item.name].active===true) {
                item.toggleActive(true, false);
            }
            //直接修改 item 组件实例的 props
            //console.log(this.$attrs);
            await this.setCollapseItemStyProps(item);
            //创建事件处理
            item.$on('toggle-active', this.whenCollapseItemToggleActive);

        },
        //将 collapse 父组件样式参数 透传到 collapse-item
        async setCollapseItemStyProps(item) {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isa = a => is.array(a) && a.length>0,
                ps = this.blockStyProps;
            if (!iso(ps)) return false;
            this.$each(ps, (v,k) => {
                //Vue.set(item.$attrs, k, v);
                item.$attrs[k] = v;
            });
            await this.$wait(10);
            console.log(item);
            return true;
        },

        //获取所有 active == true 的 collapse-item name [] 
        getActiveItemList() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                items = this.items,
                its = [];
            if (!iso(items)) return [];
            this.$each(items, (item, name) => {
                if (item.active === true) {
                    its.push(name);
                }
            });
            return its;
        },

        //针对每个 collapse-item 的事件处理函数
        //item.$emit('toggle-active', item.name, item.itemActive)
        whenCollapseItemToggleActive(name, active) {
            console.log('parent toggle-active');
            let is = this.$is,
                al = this.getActiveItemList();
            if (
                (active && !al.includes(name)) ||
                (!active && al.includes(name))
            ) {
                this.$set(this.items[name], 'active', active);
                //触发 v-model change 事件
                this.$emit('change', this.getActiveItemList());
            }
        },
    }
}


defineSpfBlockCollapse.template = `<spf-block v-bind="$attrs" @block-created="setBlockStyProps"><template v-slot:default="{styProps}"><slot v-bind="{styProps}"></slot></template></spf-block>`;

if (defineSpfBlockCollapse.computed === undefined) defineSpfBlockCollapse.computed = {};
defineSpfBlockCollapse.computed.profile = function() {return {};}




/**
 * 合并资源 menu.js
 * !! 不要手动修改 !!
 */



const defineSpfMenu = {
    model: {
        prop: 'menus',
        event: 'menu-change',
    },
    props: {
        /**
         * 菜单列表样式
         */
        //菜单项单行高度 cssvar.size.bar.* 定义的尺寸  xl|m|large|normal...
        size: {
            type: String,
            default: 'normal'
        },
        //菜单列表的颜色主题 cssvar.color{} 中定义的颜色
        color: {
            type: String,
            default: 'primary'
        },
        //菜单列表的背景色 cssvar.color{} 中定义的颜色
        background: {
            type: String,
            default: 'bgc'
        },
        //菜单项的边框样式原子类  默认 '' 表示不显示边框  可选 bd-m bd-po-tb bdc-m ...
        border: {
            type: String,
            default: ''
        },
        //菜单项是否使用特殊形状 sharp(默认)|round|pill
        shape: {
            type: String,
            default: 'sharp'
        },
        //是否占满纵向空间 默认占满
        grow: {
            type: Boolean,
            default: true
        },
        //是否显示滚动条  默认显示  ''|thin|bold
        scroll: {
            type: String,
            default: 'thin'
        },

        /**
         * 手风琴模式，同时只允许一个 一级菜单项处于展开状态
         * !! 如果某个菜单项中的某个子菜单处于 active 状态，则不折叠
         */
        accordion: {
            type: Boolean,
            default: false
        },

        //菜单列表整体是否处于 compact 横向折叠状态  通常配合 spf-layout-x 组件使用
        compact: {
            type: Boolean,
            default: false
        },

        /**
         * 菜单列表参数集合 []
         * 当 manual != true 时，将根据此参数，自动生成所有菜单项组件实例
         */
        menus: {
            type: Array,
            default: () => [
                //每个菜单项参数格式 与 dftMenuOpts{} 一致
                //{}, ...
            ]
        },
        //指定 子菜单懒加载模式 的数据源 api 为空表示不开启 懒加载模式
        subLazyloadApi: {
            type: String,
            default: ''
        },
        //指定菜单项的 notice 数据源 api
        noticeApi: {
            type: String,
            default: ''
        },
        //菜单项 notice 检查频率  默认 10 分钟
        noticeCheckFrequency: {
            type: Number,
            default: 600000
        },

        /**
         * 菜单项参数
         */
        //指定菜单项 展开后 是否显示边框
        /*itemWithBd: {
            type: Boolean,
            default: false
        },
        //指定边框样式
        itemBd: {
            type: String,
            default: ''
        },
        //itemBdPo: {
        //    type: String,
        //    default: ''
        //},
        itemBdc: {
            type: String,
            default: ''
        },*/
        //可额外指定 菜单项 header 栏的样式参数
        itemParams: {
            type: Object,
            default: () => {
                return {/*
                    headerParams: {
                        iconParams: {...},
                        color: 'random',
                        ...
                    },
                    ...
                */};
            }
        },

        /**
         * 是否通过 slot 默认插槽 手动输入菜单项组件
         */
        manual: {
            type: Boolean,
            default: false
        },

    },
    data() {return {
        //定义默认的菜单项参数
        dftMenuOpts: {
            //!! 此菜单项正在 inited 在多个 spf-menu 组件共用一个 menus 数据源时，防止数据处理错误
            initing: false,
            inited: false,

            key: '',            //menu-key 全局唯一
            label: '',          //菜单项标签
            icon: '',           //菜单项图标
            //子菜单项 []
            sub: [
                //相同数据格式
                //{}, ...
            ],
            //标记此菜单项的子菜单启用 懒加载模式
            subLazyload: false,
            //标记此菜单项的 notice 通知
            notice: {
                type: 'number',     //通知类型：number|dot|icon
                value: 0,           //通知数据：数值|true,false|图标名称
            },

            //菜单项组件参数 在 spf-menu-item 组件中定义的 props
            //!! 可通过 props.itemParams 传入自定义参数，覆盖此处的默认值
            //!! 将通过 v-bind 传入 spf-menu-item 组件中
            params: {
                //固定参数 自动生成，无需指定
                //menuKey: '',
                //keyChain: [],     # menu-key-chain 菜单键名链
                //idx: -1,          # 此菜单项 在父菜单项 sub 列表中的 idx 序号
                //idxChain: [],     # menu-idx-chain 菜单项序号链
                //lazyload: false,  # 此菜单是否懒加载
                //菜单项状态
                collapse: true,
                disabled: false,
                active: false,
                lazyLoading: false,
                //过滤设置
                hide: false,

                //默认的 菜单项 header 参数，定义通用的 菜单项样式
                headerParams: {
                    iconParams: {
                        color: 'random',    //默认 random 表示随机颜色，可选：primary|danger|success|...|blue|red|bz...
                        //size: 'm',
                    },
                    //菜单项 悬停|选中 时的颜色  与 菜单颜色主题一致
                    //color: 'primary',        //默认 primary 
                    //type: '',
                },

                //自动处理后的 菜单 notice  默认值
                notice: {
                    //自动判断是否显示 notice 标记
                    show: false,
                    type: 'number',
                    value: 0,
                    //显示的值，例如: number 类型 notice 超过 9|99|999 显示为 9+|99+|999+
                    showValue: '',
                },

                //其他 spf-menu-item 定义的 props ...
                //headerLabelActiveStyle: 'font-weight: bold;',
                //headerLabelExpandStyle: 'font-weight: bold; color: var(--color-primary-m);',
                //...
            },


            //菜单项动作 function
            cmd: null,
            //如果是导航菜单，此处设置 跳转 route 如果 cmd 和 route 都未指定，则使用 keyChain
            route: '',


            
            //不含子菜单 标记
            //noSub: true,
        },

        //根据外部传入的 menus 参数列表，缓存当前的 菜单项列表的 完整参数以及状态数据
        currentMenus: [],
        //当前 active 菜单项的 idxChain
        activeMenuIdxChain: [],

        //初始化 menus 标记
        //进行中
        initing: false,
        //已初始化完成
        inited: false,

        //更新中 标记
        updating: false,

        //定时检查 notice
        noticeChecking: null,
    }},
    computed: {
        
    },
    watch: {
        /**
         * 当外部传入的 menus 菜单列表参数发生改变时
         */
        menus: {
            handler(nv, ov) {
                if (this.inited!==true) {
                    //初始化 menus 缓存到 currentMenus
                    this.initMenus();
                } else {
                    //已初始化过 通过 v-model 同步外部 menus 与 currentMenus
                    this.updateMenus();
                }
            },
            //创建时立即执行
            immediate: true,
        },

        //监听外部传入的 compact
        compact(nv, ov) {
            //只有在 初始化完成后才会处理
            if (this.initing || !this.inited) return false;
            this.toggleCompact().then(()=>{
                //触发 menu-change 事件 将处理后的 currentMenus 回传至外层组件
                this.$emit('menu-change', this.currentMenus);
            });
        },

        //监听 inited 初始化完成后，执行 notice check
        inited(nv, ov) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='';
            if (!iss(this.noticeApi) || this.noticeCheckFrequency<=0) return;
            //必须在 inited 之后
            if (this.inited && this.$is.null(this.noticeChecking)) {
                //立即执行一次
                this.loadMenuNotice().then(()=>{
                    //创建 interval
                    this.noticeChecking = setInterval(
                        this.loadMenuNotice,
                        this.noticeCheckFrequency
                    );
                });
            }
        },
    },
    methods: {
        /**
         * 将传入的 menus 参数填充为完整的 menuOpts{} 格式，
         * 缓存到 currentMenus
         */
        async initMenus(menus=[]) {
            //如果 manual == true 不执行
            if (this.manual===true) {
                //清空缓存
                this.currentMenus.splice(0);
                this.$wait(10);
                return false;
            }
            //如果 正在初始化过程中 或 已经初始化过  跳过
            if (this.initing===true || this.inited===true) return false;
            
            //标记 初始化进行中
            this.initing = true;

            let is = this.$is,
                //iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m),
                isim = m => this.isInitedMenuOpt(m),
                fail = () => {
                    //清空缓存
                    this.currentMenus.splice(0);
                    this.initing = false;
                    return false;
                },
                curs = [];

            //未传入有效的 menus 列表 
            if (!isa(menus)) menus = this.menus;
            if (!isa(menus)) return fail();
            //开始依次使用 默认值填充传入的 menus 参数
            await this.$each(menus, async (mi, i) => {
                //必须是合法的 menu 参数
                if (!ism(mi)) return true;
                //如果此参数已经初始化
                if (isim(mi)) {
                    curs.push(mi);
                    return true;
                }
                let opt = await this.initMenuOpt(mi, {}, i);
                if (!isim(opt)) return true;
                curs.push(opt);
            });
            //写入 currentMenus 缓存
            this.currentMenus.splice(0);
            if (isa(curs)) this.currentMenus.push(...curs);
            await this.$wait(10);

            //处理外部传入的 compact 横向折叠
            if (this.compact===true) {
                await this.toggleCompact();
            }

            //触发 menu-change 事件 将处理后的 currentMenus 回传至外层组件
            this.$emit('menu-change', this.currentMenus);
            //给外部处理添加一个缓冲时间，
            await this.$wait(100);

            //完成初始化
            this.initing = false;
            //以及初始化过 标记
            this.inited = true;
            return true;
        },
        //init 初始化某个菜单项 返回处理后的 menuOpt{} 菜单项参数
        async initMenuOpt(opts={}, parentOpts={}, idx=-1) {
            let is = this.$is,
                ext = this.$extend,
                //iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                ism = m => this.isMenuOpt(m),
                isim = m => this.isInitedMenuOpt(m);
            //首先确保传入的参数有效
            if (!ism(opts) || idx<0) return null;

            //准备生成标准的 menuOpt{} 参数
            let dft = this.$extend({}, this.dftMenuOpts),
                //此菜单项的父级菜单 menuOpt{}
                pm = !ism(parentOpts) ? {} : parentOpts,
                //外部传入的 itemParams
                ips = iso(this.itemParams) ? this.itemParams : {},
                //此菜单项的子菜单 []
                sub = isa(opts.sub) ? opts.sub : [],
                rtn = {};

            //去除 dft 中的 sub 项 防止 extend 出错
            if (isa(dft.sub)) Reflect.deleteProperty(dft, 'sub');
            if (iso(dft.params) && isa(dft.params.sub)) Reflect.deleteProperty(dft.params, 'sub');
            //去除 opts 中的 sub 项 防止 extend 出错
            if (isa(opts.sub)) Reflect.deleteProperty(opts, 'sub');
            
            //menu-key 必须是 foo-bar 形式，因此需要 fooBar --> foo-bar
            let menuKey = opts.key.toSnakeCase('-');

            //合并 dft.params, menus[*], props.itemParams 
            rtn = ext(
                rtn,    //空值
                dft,    //默认值
                {       //使用 props.color 覆盖 dft.params.headerParams.iconParams.color
                    params: {
                        headerParams: {
                            iconParams: {
                                color: this.color || 'random',
                            }
                        }
                    }
                },
                {       //外部传入的 通用 itemParams{}
                    params: ips
                },
                opts,   //外部传入的 menus[] 中的当前 menu 的设置值 {}
                {       //菜单参数的 固定值
                    key: menuKey,
                    params: {
                        menuKey,
                        idx,
                    }
                }
            );
            //console.log(dft,opts,ips,rtn);
            await this.$wait(5);
            
            //生成 keyChain|idxChain
            let kc = [],    
                idxc = [];
            if (ism(pm)) {
                //存在父菜单，则合并父菜单的 key|idxChain
                if (iso(pm.params) && isa(pm.params.keyChain)) kc = [...pm.params.keyChain];
                if (iso(pm.params) && isa(pm.params.idxChain)) idxc = [...pm.params.idxChain];
            }
            //合并当前菜单的 key|idx
            kc.push(menuKey);
            idxc.push(idx);
            //合并到 rtn.params
            rtn.params = ext(rtn.params, {
                keyChain: kc,
                idxChain: idxc
            });

            //处理 sub 子菜单项
            if (isa(sub)) {
                let nsub = [];
                //递归处理子菜单
                await this.$each(sub, async (mi, i) => {
                    if (!ism(mi)) return true;
                    let opt = await this.initMenuOpt(mi, rtn, i);
                    if (!isim(opt)) return true;
                    //子菜单项写入 sub
                    nsub.push(opt);
                });
                //写入子菜单参数
                rtn.sub = nsub;
                //递归处理子菜单的 icon 参数
                rtn = this.setSubMenuIcon(rtn);
            } else {
                //不存在子菜单 则填入空 []
                rtn.sub = [];
            }
            //子菜单是否懒加载 标记
            if (this.isSubLazyload(rtn)) {
                rtn.params.lazyload = true;
            }
            
            //无子菜单，则 collapse 设为 true
            //if (!isa(rtn.sub)) {
            if (this.hasNoSub(rtn)) {
                rtn.params.collapse = true;
            }

            //处理 notice 通知
            if (iso(rtn.notice)) {
                rtn.params.notice = this.parseMenuNotice(rtn.notice);
            }

            //如果当前菜单项已被激活
            if (rtn.params.active && rtn.params.active===true) {
                //初始化时 只有 第一个被设为 active 的菜单项才会生效
                if (!isa(this.activeMenuIdxChain)) {
                    this.activeMenuIdxChain.push(...rtn.params.idxChain);
                }
            }

            await this.$wait(5);
            //返回处理结果
            return rtn;
        },

        /**
         * 菜单参数 初始化后发生改变时 update 更新 currentMenus 数据
         * 通过 watch menus 值的改变
         */
        async updateMenus(menus=[]) {
            //如果 manual == true 不执行
            if (this.manual===true) {
                //清空缓存
                this.currentMenus.splice(0);
                this.$wait(10);
                return false;
            }
            //如果 正在更新过程中 或 还未初始化过  跳过
            if (this.updating===true || this.inited!==true) return false;
            
            //标记 更新进行中
            this.updating = true;

            let is = this.$is,
                //iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m),
                isim = m => this.isInitedMenuOpt(m);

            //未传入有效的 menus 列表 
            if (!isa(menus)) menus = this.menus;
            if (!isa(menus)) return false;
            //使用 传入的 menus[] 回写到 currentMenus
            await this.$each(menus, async (mi,i) => {
                //确保 menus 中的所有值都是有效的 menuOpt
                if (ism(mi)) {
                    let opt = Object.assign({}, mi);
                    if (!isim(mi)) {
                        //传入的 menuOpt 还未经过初始化
                        opt = await this.initMenuOpt(mi, {}, i);
                        if (!isim(opt)) return true;
                    }
                    if (is.defined(this.currentMenus[i])) {
                        //currentMenus 中已存在
                        this.currentMenus.splice(i, 1, opt);
                    } else {
                        //currentMenus 中不存在
                        this.currentMenus.push(opt);
                    }
                }
            });

            //完成更新
            this.updating = false;
            return true;
        },

        /**
         * 根据 compact 横向折叠状态，修改所有 一级菜单的 collapse 状态
         * 恢复时，只有 active 的一级菜单才会恢复至 展开状态
         */
        async toggleCompact() {
            let is = this.$is,
                isim = m => this.isInitedMenuOpt(m),
                isac = m => this.isMenuActived(m),
                compact = this.compact,
                menus = this.currentMenus;
            this.$each(menus, (mi,i) => {
                if (!isim(mi)) return true;
                
                if (compact!==true) {
                    //恢复至未横向折叠状态
                    if (isac(mi)) {
                        //只有处于 active 状态的 一级菜单 才会恢复至 未纵向折叠状态
                        this.$set(mi.params, 'collapse', false);
                    } else {
                        //其他菜单 不操作
                        return true;
                    }
                } else {
                    //切换至横向折叠状态
                    this.$set(mi.params, 'collapse', true);
                }
            });
            await this.$wait(10);
            return true;
        },

        //工具方法
        //判断一个 {} 是否有效的 menuOpt 菜单参数
        isMenuOpt(opt={}) {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                iss = s => is.string(s) && s!=='';
            return iso(opt) && iss(opt.key) && iss(opt.label);
        },
        //判断一个 {} 是否有效的 已经过初始化的 menuOpt 菜单参数
        isInitedMenuOpt(opt={}) {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m);
            if (!ism(opt)) return false;
            return iso(opt.params) && isa(opt.params.keyChain) && isa(opt.params.idxChain);
        },
        //判断此菜单是否处于 active 状态，此菜单自身或某个子菜单 active 都返回 true
        isMenuActived(opt={}) {
            if (!this.isInitedMenuOpt(opt)) return false;
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                aidxc = this.activeMenuIdxChain,
                idxc = opt.params.idxChain;
            //无 active 菜单
            if (!isa(aidxc)) return false;
            let acstr = aidxc.join('-'),
                cstr = idxc.join('-');
            //根据 idxChain 序号链 判断
            return acstr===cstr || acstr.startsWith(cstr);
        },
        //判断此菜单的子菜单是否采用 懒加载模式
        isSubLazyload(opt={}) {
            if (!this.isMenuOpt(opt)) return false;
            return this.subLazyloadApi!=='' && opt.subLazyload===true;
        },
        //判断此菜单的子菜单是否采用懒加载，且还未加载
        isUnloadedSubLazyload(opt={}) {
            if (!this.isSubLazyload(opt)) return false;
            let is = this.$is,
                isa = a => is.array(a) && a.length>0;
            return !isa(opt.sub);
        },
        //判断此菜单是否存在子菜单
        hasNoSub(opt={}) {
            if (!this.isMenuOpt(opt)) return true;
            let is = this.$is,
                isa = a => is.array(a) && a.length>0;
            return !(isa(opt.sub) || this.isSubLazyload(opt));
        },
        //远程加载 lazyload 子菜单
        async loadSubMenu(opt={}) {
            if (!this.isSubLazyload(opt)) return false;
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m),
                isim = m => this.isInitedMenuOpt(m),
                api = this.subLazyloadApi;
            //加载标记
            if (opt.params.lazyLoading!==false) return false;
            this.$set(opt.params, 'lazyLoading', true);
            //同步
            this.$emit('menu-change', this.currentMenus);

            //远程加载
            /*let sub = await this.$req(api, {
                keyChain: opt.params.keyChain
            });*/

            //!! mock for Dev
            await this.$wait(1000);
            let sub = [
                {
                    key: 'menu-foo-c-c-a',
                    label: '菜单 foo-c-c-a'
                },
                {
                    key: 'menu-foo-c-c-b',
                    label: '菜单 foo-c-c-b'
                }
            ];

            if (!isa(sub)) {
                //加载失败，报错
                //...
                
                this.$set(opt.params, 'lazyLoading', false);
                this.$emit('menu-change', this.currentMenus);
                return false;
            }

            //初始化子菜单
            let nsub = [];
            //递归处理子菜单
            await this.$each(sub, async (mi, i) => {
                if (!ism(mi)) return true;
                let subi = await this.initMenuOpt(mi, opt, i);
                if (!isim(subi)) return true;
                //子菜单项写入 sub
                nsub.push(subi);
            });
            //写入子菜单参数
            if (!is.array(opt.sub)) {
                opt.sub = [];
            } else {
                opt.sub.splice(0);
            }
            opt.sub.push(...nsub);
            //递归处理子菜单的 icon 参数
            //let nopt = this.setSubMenuIcon(opt);

            //结束
            this.$set(opt.params, 'lazyLoading', false);
            this.$emit('menu-change', this.currentMenus);
            return true;
        },
        /**
         * 批量设置某个菜单项中所有子菜单的 icon
         * 如果所有子菜单都没有指定 icon 则将所有子菜单的 icon 设为为 ''
         * 如果有子菜单设置了 icon 则将其余的子菜单 icon 设置为 '-empty-'
         */
        setSubMenuIcon(opts={}) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m),
                isc = c => iss(c) && c!=='' && c!=='-empty-';
            if (!ism(opts) || !isa(opts.sub)) return opts;
            let hasicon = false;
            this.$each(opts.sub, (subi,i) => {
                //检查当前子菜单
                if (isc(subi.icon)) {
                    hasicon = true;
                }
                //递归执行 子菜单的子菜单  将处理后的 子菜单的子菜单回填到当前 子菜单的 sub[] 中
                opts.sub[i] = this.setSubMenuIcon(subi);
            });
            //根据 hasicon 状态批量设置 子菜单的 icon
            this.$each(opts.sub, (subi,i) => {
                //只修改未设置 icon 的子菜单
                if (!isc(subi.icon)) {
                    opts.sub[i].icon = hasicon ? '-empty-' : '';
                }
            });
            //返回处理后的 菜单参数
            return opts;
        },
        /**
         * 自动从指定的 noticeApi 获取各菜单项 notice 数据
         * 依据 noticeCheckFrequency 定时检查
         */
        async loadMenuNotice() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                isim = m => this.isInitedMenuOpt(m),
                api = this.noticeApi,
                menus = this.currentMenus;
            if (!iss(api) || !isa(menus)) return false;
            //远程获取 notice 数据
            //let notices = await this.$req(api);
            //!! mock for Dev
            await this.$wait(1000);
            let notices = [
                {
                    idxChain: [0],
                    notice: {
                        type: 'number',
                        value: 120
                    }
                },
                {
                    idxChain: [0,0],
                    notice: {
                        type: 'dot',
                        value: true
                    }
                },
                {
                    idxChain: [1],
                    notice: {
                        type: 'icon',
                        value: 'sentiment-dissatisfied'
                    }
                },
                {
                    idxChain: [2],
                    notice: {
                        type: 'dot',
                        value: false
                    }
                },
            ];

            //解析结果
            if (!isa(notices)) return false;
            this.$each(notices, (nt,i)=>{
                let idxc = nt.idxChain || [],
                    ntc = nt.notice || {};
                if (!isa(idxc) || !iso(ntc)) return true;
                let mi = this.getCurrentMenuByIdxChain(idxc);
                if (!isim(mi)) return true;
                //处理
                let nto = this.parseMenuNotice(ntc);
                //写入
                this.$set(mi.params, 'notice', Object.assign({}, nto));
                this.$set(mi, 'notice', Object.assign({}, ntc));
            });
            await this.$wait(10);
            //触发事件
            this.$emit('menu-change', this.currentMenus);
            return true;
        },
        //根据 菜单项 notice 数据，生成传入 menu-item 组件的 notice 参数
        parseMenuNotice(notice={}) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                isn = n => is.realNumber(n),
                dft = Object.assign({}, this.dftMenuOpts.params.notice);
            if (!iso(notice)) return dft;
            let ntp = notice.type || 'number',
                ntv = notice.value || 0,
                nto = dft;
            nto.type = ntp;
            //分别处理各种类型的 notice
            switch (ntp) {
                //数字类型通知
                case 'number':
                    nto.show = isn(ntv) && ntv>0;
                    if (isn(ntv) && ntv>0) {
                        nto.show = true;
                        let steps = [9,99,999];
                        this.$each(steps, (step,i)=>{
                            if (ntv>step) nto.showValue = `${step}+`;
                        });
                    }
                    break;
                //红点通知
                case 'dot':
                    if (is.boolean(ntv)) nto.show = ntv===true;
                    break;
                //图标通知
                case 'icon':
                    if (iss(ntv) && ntv!=='-empty-') {
                        nto.show = true;
                        nto.showValue = ntv;
                    }
                    break;
                //可扩展更多 notice 类型
                //...
            }
            //返回
            return nto;
        },
        //根据 idxChain 从 currentMenus 获取某个 menuItem {}
        getCurrentMenuByIdxChain(idxChain=[]) {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                isn = n => is.realNumber(n);
            if (!isa(idxChain)) return null;
            let mi = this.currentMenus;
            for (let i=0;i<idxChain.length;i++) {
                let idx = idxChain[i];
                if (!isn(idx) || idx<0) {
                    mi = null;
                    break;
                }
                if (isa(mi)) {
                    if (!is.defined(mi[idx]) || !iso(mi[idx])) {
                        mi = null;
                        break;
                    }
                    mi = mi[idx];
                } else if (iso(mi) && isa(mi.sub)) {
                    if (!is.defined(mi.sub[idx]) || !iso(mi.sub[idx])) {
                        mi = null;
                        break;
                    }
                    mi = mi.sub[idx];
                } else {
                    mi = null;
                    break;
                }
            }
            return mi;
        }, 
        //根据 keyChain 从 currentMenus 获取某个 menuItem {}
        getCurrentMenuByKeyChain(keyChain=[]) {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                iss = s => is.string(s) && s!=='',
                //从 opts[] 中查找指定的 opt.key
                gk = (a, k) => {
                    let idx = -1;
                    this.$each(a, (c,i)=>{
                        if (!iss(c.key) || c.key!==k) return true;
                        idx = i;
                        return false;
                    });
                    if (idx<0) return null;
                    return a[idx];
                };
            if (!isa(keyChain)) return null;
            let mi = this.currentMenus;
            for (let i=0;i<keyChain.length;i++) {
                let key = keyChain[i];
                if (!iss(key)) {
                    mi = null;
                    break;
                }
                if (isa(mi)) {
                    mi = gk(mi, key);
                } else if (iso(mi) && isa(mi.sub)) {
                    mi = gk(mi.sub, key);
                }
                if (!iso(mi)) {
                    mi = null;
                    break;
                }
            }
            return mi;
        },
        //折叠除了 exceptIdx 之外的其他 一级菜单，用于手风琴模式
        collapseMenus(except=-1) {
            let is = this.$is,
                ms = this.currentMenus;
            this.$each(ms, (mi,i) => {
                if (i===except) return true;
                //active 除外
                if (this.isMenuActived(mi)===true) return true;
                //设置 mi.params.collapse = true
                if (mi.params.collapse!==true) {
                    this.$set(mi.params, 'collapse', true);
                }
            });
        },
        //激活某个菜单 
        async activeMenu(opt={}) {
            if (!this.isInitedMenuOpt(opt) || this.isMenuActived(opt)) return false;
            //先取消 active
            await this.disactiveMenu();
            //激活此菜单 idxChain 序号链上的所有 子菜单项的 active 状态
            let midxc = opt.params.idxChain;
            for (let i=midxc.length;i>0;i--) {
                let idxc = midxc.slice(0,i),
                    mi = i===midxc.length ? opt : this.getCurrentMenuByIdxChain(idxc);
                if (!this.isInitedMenuOpt(mi)) continue;
                //激活 active
                this.$set(mi.params, 'active', true);
            }
            //缓存当前菜单的序号链到 activeMenuIdxChain
            this.activeMenuIdxChain.splice(0);
            this.activeMenuIdxChain.push(...midxc);
            return await this.$wait(10);
        },
        //取消激活菜单
        async disactiveMenu() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                gcm = this.getCurrentMenuByIdxChain,
                isim = m => this.isInitedMenuOpt(m),
                aidxc = this.activeMenuIdxChain;
            if (!isa(aidxc)) return false;
            //取消 active idxChain 序号链上的所有 子菜单项的 active 状态
            for (let i=aidxc.length;i>0;i--) {
                let idxc = aidxc.slice(0,i),
                    mi = gcm(idxc);
                if (!isim(mi)) continue;
                //取消 active
                this.$set(mi.params, 'active', false);
            }
            //清除 activeMenuIdxChain
            this.activeMenuIdxChain.splice(0);
            return await this.$wait(10);
        },
        //计算某个菜单项的 所有子菜单当前应显示的 高度
        calcMenuHeight(opt={}, size=null) {
            if (!this.isInitedMenuOpt(opt)) return '0px';
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                sub = opt.sub,
                ui = this.$ui,
                sz = ui.sizeVal(size, 'bar');
            //默认菜单栏行高为 bar.m
            if (!ui.isSizeVal(sz)) sz = ui.cssvar.size.bar.m;
            //如果不存在子菜单 或 处于折叠状态 或 处于过滤隐藏状态  返回单行高度
            if (!isa(sub) || opt.params.collapse===true || opt.params.hide===true) return '0px';
            let hs = [];
            this.$each(sub, (subi,i) => {
                //如果出于 过滤隐藏状态，不计算高度
                if (subi.params && subi.params.hide && subi.params.hide===true) return true;
                //加入子菜单单行高度
                hs.push(sz);
                //计算子菜单的高度
                hs.push(this.calcMenuHeight(subi, size));
            });
            //所有高度相加
            let h = ui.sizeValAdd(...hs);
            if (!ui.isSizeVal(h)) return '0px';
            return h;
        },
        //根据 idxChain 计算菜单项高度
        calcMenuHeightByIdxChain(idxChain=[], size=null) {
            let mi = this.getCurrentMenuByIdxChain(idxChain);
            return this.calcMenuHeight(mi, size);
        },


        //事件
        async whenMenuItemToggleCollapse(idxChain=[]) {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isim = m => this.isInitedMenuOpt(m),
                mi = this.getCurrentMenuByIdxChain(idxChain);
            if (!isim(mi)) return false;
            //不存在子菜单
            if (this.hasNoSub(mi)) return false;

            //针对 lazyload 子菜单 还未加载的情况
            if (this.isUnloadedSubLazyload(mi)) {
                //加载子菜单
                await this.loadSubMenu(mi);
            }

            //如果是 手风琴模式，则 折叠其他一级菜单  active 除外
            if (this.accordion===true) {
                this.collapseMenus(idxChain[0]);
            }
            //设置当前菜单项的 collapse 状态
            this.$set(mi.params, 'collapse', !mi.params.collapse);
            //触发事件 同步外层的 menus 参数值
            await this.$wait(10);
            return this.$emit('menu-change', this.currentMenus);
        },
        async whenMenuItemClick(keyChain=[], idxChain=[]) {
            //console.log(keyChain);
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                isim = m => this.isInitedMenuOpt(m),
                isf = f => is(f, 'function,asyncfunction'),
                mi = this.getCurrentMenuByIdxChain(idxChain);
            if (!isim(mi)) return false;
            //如果此菜单项 包含子菜单，则无法执行 active 操作
            if (!this.hasNoSub(mi)) return false;
            //如果当前菜单项 active == true 表示此菜单项已处于 active 状态，不操作
            if (mi.params.active && mi.params.active===true) return false;

            //激活此菜单
            await this.activeMenu(mi);

            //手风琴模式下，折叠其他一级菜单
            if (this.accordion===true) {
                this.collapseMenus(idxChain[0]);
            }

            //触发事件 同步外层的 menus 参数值
            await this.$wait(10);
            this.$emit('menu-change', this.currentMenus);

            //执行当前菜单项定义的 cmd 自定义函数
            let cmd = mi.cmd || null;
            if (isf(cmd)) return cmd();
            
            //执行当前菜单项定义的 route 目标路由
            let route = mi.route || '';
            if (iss(route)) {
                //TODO: 调用 Vue.service.nav 服务，跳转...
                //...
                return;
            }

            //如果当前菜单项未指定 cmd 和 route 则向外抛出 menu-item-active 事件
            return this.$emit('menu-item-active', keyChain);
        },
        async whenCompactMenuItemClick(idxChain=[]) {
            //仅在菜单列表 横向折叠状态下有效
            if (!this.compact) return false;
            //通知父组件，接触 compact 横向折叠状态
            this.$emit('self-uncompact');
            //监听 props.compact 参数直到变为 false
            await this.$until(()=>!this.compact);
            //等待界面刷新
            await this.$wait(300);
            let mi = this.getCurrentMenuByIdxChain(idxChain);
            if (this.isMenuActived(mi)!==true) {
                //仅对未 active 的菜单项，执行 toggleCollapse 操作
                return await this.whenMenuItemToggleCollapse(idxChain);
            }
            return true;
        },
    }
}


defineSpfMenu.template = `<spf-block v-bind="$attrs" :size="size" :grow="grow" :scroll="scroll" v-slot="{styProps, cntParams}"><template v-if="manual"><slot v-bind="{styProps, cntParams}"></slot></template><template v-if="!manual && inited && currentMenus.length>0"><template v-for="(menui, midx) of currentMenus"><spf-menu-item v-if="!menui.params.hide" :key="'spf-menu-item-'+midx" :size="styProps.size" :color="color" :background="background" :border="border" :shape="shape" :label="menui.label" :icon="menui.icon" :sub="menui.sub" v-bind="menui.params" :menu-comp="$this" :compact="compact" @menu-item-toggle-collapse="whenMenuItemToggleCollapse" @menu-item-click="whenMenuItemClick" @compact-menu-item-click="whenCompactMenuItemClick"></spf-menu-item></template></template></spf-block>`;

if (defineSpfMenu.computed === undefined) defineSpfMenu.computed = {};
defineSpfMenu.computed.profile = function() {return {};}




/**
 * 合并资源 menu-item.js
 * !! 不要手动修改 !!
 */




const defineSpfMenuItem = {
    mixins: [mixinBase],
    props: {
        /**
         * 标记通过 pre-menu 组件默认 slot 手动插入的 pre-menu-item 组件
         */
        manual: {
            type: Boolean,
            default: false
        },

        /**
         * 菜单项 参数
         */
        //icon
        icon: {
            type: String,
            default: ''
        },
        //label
        label: {
            type: String,
            default: ''
        },
        //menu-key 菜单项键名，在 menusOpts{} 中的键名 foo-bar 格式
        menuKey: {
            type: String,
            default: ''
        },
        //menu-key-chain 菜单项 键名链  [foo, bar, jaz-tom] 形式
        keyChain: {
            type: Array,
            default: () => []
        },
        //idx 当前菜单项在父菜单项的 sub 列表中的 idx 序号
        idx: {
            type: Number,
            default: -1
        },
        //menu-idx-chain 菜单项序号链
        idxChain: {
            type: Array,
            default: () => []
        },

        //菜单项 样式
        size: {
            type: String,
            default: 'normal'
        },
        color: {
            type: String,
            default: 'primary'
        },
        background: {
            type: String,
            default: 'bgc'
        },
        border: {
            type: String,
            default: ''
        },
        //菜单项 shape 可选 sharp(默认)|round|pill
        shape: {
            type: String,
            default: 'sharp'
        },

        //header 参数
        //header params
        headerParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        //header label styles
        /*headerLabelActiveStyle: {
            type: String,
            default: 'font-weight: bold;'
        },
        headerLabelExpandStyle: {
            type: String,
            default: 'font-weight: bold;'
        },*/

        //菜单项的当前状态
        //collapse 折叠
        collapse: {
            type: Boolean,
            default: false
        },
        //disabled
        disabled: {
            type: Boolean,
            default: false
        },
        //active
        active: {
            type: Boolean,
            default: false
        },
        //子菜单加载中
        lazyLoading: {
            type: Boolean,
            default: false
        },

        //子菜单
        sub: {
            type: Array,
            default: () => []
        },
        //子菜单是否懒加载
        lazyload: {
            type: Boolean,
            default: false
        },

        //菜单项的 notice 通知
        notice: {
            type: Object,
            default: () => {
                return {};
            }
        },

        //传入 spf-menu 组件实例
        menuComp: {
            type: Object,
            default: () => {
                return {};
            }
        },

        //菜单项是否处于 横向折叠状态
        compact: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    //不使用 root 根元素
                    root: '',
                    //菜单项整体
                    item: 'spf-menu',
                    //菜单项 header 
                    header: '',
                    //菜单项 header label 
                    label: '',
                    //菜单项 通知 
                    notice: 'spf-menu-notice',
                    //菜单项 箭头标记
                    arrow: '',
                    //子菜单容器 
                    cnt: '',
                },
                style: {
                    //菜单项整体
                    item: '',
                    //菜单项 header 
                    header: '',
                    //菜单项 header label 
                    label: '',
                    //菜单项 通知 
                    notice: '',
                    //菜单项 箭头标记
                    arrow: 'margin-right: -0.5em;',
                    //子菜单容器 
                    cnt: 'min-height: 0px;',
                }
            },
            prefix: 'menu',
            group: {
                //新增组开关
                noSub: true,
                collapse: true,
                lazyLoading: true,
                menuNoticeShow: true,
                //active: true,
                //横向折叠状态，仅一级菜单可能为 true 
                compact: true,
            },
            sub: {
                //size: true,
                //color: true,
                //animate: 'disabled:false',
                //仅启用 switch 子系统
            },
            switch: {
                //启用 下列样式开关

                //针对 item 菜单项整体
                'border:collapse@item':     '.menu-with-bd menu-collapse',
                'border:!collapse@item':    '.{swv} menu-with-bd menu-expand',
                //展开子菜单 或 有子菜单被 active 时整体增加  背景色
                '!collapse:!no-sub@item': 'background-color: {swv@get,menuColors.itemExpandBgc};',
                'active:!no-sub@item': 'background-color: {swv@get,menuColors.itemExpandBgc};',

                //针对 菜单项 header
                'shape@header': '.bar-shape shape-{swv}',
                'menuExtraPadding@header': 'padding-left: {swv};',
                '!collapse:!no-sub@header': 'background-color: {swv@get,menuColors.headerExpandBgc};',
                //横向折叠状态，仅针对一级菜单
                'compact@header': 'padding: 0; justify-content: center;',

                //针对 菜单项的 label
                'active:no-sub@label': 'font-weight: bold;',
                'active:!no-sub@label': 'font-weight: bold; color: {swv@get,menuColors.labelExpandColor};',
                //菜单失效时 label 文字样式
                'disabled@label': 'color: {swv@get,menuColors.labelDisabledColor};',
                '!collapse:!no-sub@label': 'font-weight: bold; color: {swv@get,menuColors.labelExpandColor};',

                //菜单项 notice 通知
                'menuNoticeType:menu-notice-show@notice': '.notice-{swv}',
                'menuNoticeShow:no-sub@notice': 'margin-right: -4px;',

                //针对 菜单项的 箭头
                'collapse:!lazy-loading@arrow': '.icon-l2',
                'lazyLoading:!no-sub@arrow': 'color: {swv@get,menuColors.labelExpandColor};',
                'active:!no-sub@arrow': 'color: {swv@get,menuColors.labelExpandColor};',
                '!collapse:!no-sub@arrow': 'color: {swv@get,menuColors.labelExpandColor};',

                //针对 子菜单容器
                'collapse:!no-sub@cnt': {
                    //展开子菜单时，自动计算 子菜单高度
                    height: '{swv@if,0px,(get,menuSubHeight)}',
                },
            },
            csvKey: {
                //size: 'bar',
                //color: 'bgc',
            },
        },
    }},
    computed: {
        //当前菜单项是否不含子菜单
        noSub() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0;
            return !isa(this.sub) && !this.lazyload;
        },
        //当前菜单项在整个菜单中的深度层级  从 0 开始
        menuDeepLevel() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                idxc = this.idxChain || [];
            if (!isa(idxc)) return -1;
            return idxc.length - 1;
        },
        //根据当前菜单项的深度层级，计算额外的 padding-left 返回 '48px' 或 ''
        menuExtraPadding() {
            let is = this.$is,
                isu = n => is.numeric(n),
                lvl = this.menuDeepLevel;
            if (lvl<=0) return '';
            //一级菜单 且 compact == true 时，不计算
            if (lvl===0 && this.compact===true) return '';
            //从 styProps 中获取相应的尺寸值 并计算
            let ui = this.$ui,
                sv = ui.sizeVal,
                sz = this.size,
                isz = sv(sz, 'icon'),
                pd = sv(sz, 'pd');
            if (!isu(isz) || !isu(pd)) return '';
            let add = ui.sizeValAdd,
                mul = ui.sizeValMul,
                div = ui.sizeValDiv,
                rtn = add(pd, mul(add(isz, div(pd, 2)), lvl));
            if (!isu(rtn)) return '';
            return rtn;
        },
        //当前菜单项的 子菜单高度
        menuSubHeight() {
            if (this.noSub || this.collapse) return '0px';
            return this.menuComp.calcMenuHeightByIdxChain(this.idxChain, this.size);
        },

        //菜单项 notice 通知
        menuNoticeShow() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isd = d => is.defined(d),
                nt = this.notice;
            if (iso(nt) && isd(nt.show) && isd(nt.type) && isd(nt.showValue)) return nt.show;
            return false;
        },
        menuNoticeType() {
            return this.menuNoticeShow ? this.notice.type : '';
        },

        //菜单项 header params
        menuHeaderParams() {
            let is = this.$is,
                color = this.color,
                size = this.size,
                icon = this.icon,
                hps = this.headerParams,
                rtn = this.$extend({
                    icon,
                    //默认 菜单项图表样式
                    iconParams: {
                        color,
                        size
                    },
                    //默认菜单项 悬停|选中 背景颜色 primary
                    color,
                }, hps);
            //处理 iconParams.color == random 随机图标颜色 的情况
            if (rtn.iconParams.color === 'random') {
                let clrs = this.$ui.cssvar.extra.color.types,
                    rnd = Math.floor(Math.random() * clrs.length);
                rtn.iconParams.color = clrs[rnd];
            }
            //处理 展开子菜单 或 有子菜单处于 active 时 icon 的额外样式
            if (!this.noSub && (!this.collapse || this.active)) {
                rtn = this.$extend(rtn, {
                    iconStyle: {
                        color: this.menuColors.labelExpandColor
                    }
                });
            }
            //处理 noSub == true 且 active == true 时 增加 active 标记
            if (this.noSub && this.active) rtn.active = true;
            //处理 disabled == true
            if (this.disabled) rtn.disabled = true;

            //处理 一级菜单 且 compact == true 横向折叠时的额外样式
            if (this.menuDeepLevel===0 && this.compact) {
                if (this.active) {
                    //如果 active == true 则增加 active 标记
                    rtn.active = true;
                } else{
                    //未选中状态，使用默认 icon 颜色
                    rtn = this.$extend(rtn, {
                        iconStyle: {
                            color: this.$ui.cssvar.color.fc.m,
                        }
                    });
                }
            }
            return rtn;
        },

        //根据菜单主题色，生成需要的目标颜色
        menuColors() {
            let is = this.$is,
                //菜单主题色
                color = this.color,
                //菜单背景色
                bgc = this.background,
                csv = this.$ui.cssvar.color;

            return {
                //子菜单展开时，为整个菜单项增加背景色 10% 透明度
                itemExpandBgc: bgc==='bgc' ? `${csv.bgc.d1}19` : `${csv[bgc].l2}19`,
                //子菜单展开时，header 背景色  transparent
                headerExpandBgc: 'transparent',
                //子菜单展开时，label 文字颜色
                labelExpandColor: csv[color].d2,
                //子菜单失效时，label 文字颜色  透明度由 spf-bar.disabled 样式类提供
                labelDisabledColor: csv.fc.m,
            };
        },
    },
    methods: {
        /**
         * 响应菜单项 header 栏点击动作
         */
        whenMenuHeaderClick() {
            //if (this.manual) return false;
            if (this.compact===true) {
                //横向折叠状态，点击自动展开
                return this.$emit('compact-menu-item-click', this.idxChain);
            } else if (this.noSub) {
                //没有子菜单，表示这是菜单点击事件
                return this.$emit('menu-item-click', this.keyChain, this.idxChain);
            } else {
                //有子菜单，表示这是 折叠|展开 动作
                return this.$emit('menu-item-toggle-collapse', this.idxChain);
            }
        },
        whenMenuSubItemToggleCollapse(idxChain=[]) {
            if (this.manual) return false;
            return this.$emit('menu-item-toggle-collapse', idxChain);
        },
        whenMenuSubItemClick(keyChain=[], idxChain=[]) {
            if (this.manual) return false;
            return this.$emit('menu-item-click', keyChain, idxChain);
        },
    }
}





defineSpfMenuItem.template = `<spf-block v-bind="$attrs" inner-content with-header :header-params="menuHeaderParams" :header-style="styComputedStyleStr.header" header-clickable :hide-cnt="noSub || collapse" @header-click="whenMenuHeaderClick" :cnt-style="styComputedStyleStr.cnt" :custom-class="styComputedClassStr.item" :custom-style="styComputedStyleStr.item"><template #header><template v-if="!compact"><label :style="styComputedStyleStr.label">{{label}}</label><span class="flex-1"></span><!--菜单项 notice--><template v-if="menuNoticeShow"><div v-if="menuNoticeType==='dot'" :class="styComputedClassStr.notice" :style="styComputedStyleStr.notice"></div><div v-if="menuNoticeType==='number'" :class="styComputedClassStr.notice" :style="styComputedStyleStr.notice">{{notice.showValue}}</div><spf-icon v-if="menuNoticeType==='icon'" :icon="notice.showValue" color="danger" :class="styComputedClassStr.notice" :style="styComputedStyleStr.notice"></spf-icon></template><!--折叠箭头|lazyLoading 标记--><spf-icon v-if="!noSub" :icon="collapse ? 'keyboard-arrow-down' : 'keyboard-arrow-up'" size="small" :spin="lazyload && lazyLoading" :custom-class="styComputedClassStr.arrow" :custom-style="styComputedStyleStr.arrow"></spf-icon></template></template><template v-if="!noSub && sub.length>0 && !collapse" #default><template v-for="(menui, midx) of sub"><spf-menu-item v-if="!menui.params.hide" :key="'spf-menu-item-'+idx+'-sub-'+midx" :size="size" :color="color" :background="background" :border="border" :shape="shape" :label="menui.label" :icon="menui.icon" :sub="menui.sub" v-bind="menui.params" :menu-comp="menuComp" @menu-item-toggle-collapse="whenMenuSubItemToggleCollapse" @menu-item-click="whenMenuSubItemClick"></spf-menu-item></template></template></spf-block>`;

if (defineSpfMenuItem.computed === undefined) defineSpfMenuItem.computed = {};
defineSpfMenuItem.computed.profile = function() {return {};}










/**
 * 合并资源 layout.js
 * !! 不要手动修改 !!
 */




const defineSpfLayout = {
    mixins: [mixinBase, mixinBaseParent],
    model: {
        prop: 'menus',
        event: 'menu-change',
    },
    props: {
        /**
         * for spf-block 根组件的 样式参数
         */
        //stretch 容器水平延伸类型  row(默认)
        stretch: {
            type: String,
            default: 'row'
        },
        //是否占满整个纵向空间  默认 true
        grow: {
            type: Boolean,
            default: true
        },
        //是否在 grow == true 时启用 scroll-y  默认 '' 不启用
        scroll: {
            type: String,
            default: ''
        },

        /**
         * logo 图标名称   基于 __pre__-icon-logo 组件
         * !! 如果图标有 -light|-dark 两种模式，将会自动根据 主题明暗模式 切换
         */
        logo: {
            type: String,
            default: 'spf-logo-light'
        },
        //另外指定一个 menubar 折叠状态的 logo 正方形
        compactLogo: {
            type: String,
            default: 'spf-logo-app'
        },

        /**
         * 是否在顶部显示整行 navbar 导航栏
         * 默认 false  导航栏显示在 main-body 区域顶部，左侧为 menubar 其顶部显示 logo
         */
        withNavbar: {
            type: Boolean,
            default: false
        },
        //传入的 navbar 参数
        navbarParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        /**
         * 是否在底部显示完整的 taskbar 任务/状态栏
         * 默认 false 
         */
        withTaskbar: {
            type: Boolean,
            default: false
        },
        //传入的 taskbar 参数
        taskbarParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        /**
         * 是否在 左侧|右侧 显示 menubar
         * 默认 false 
         */
        withMenubar: {
            type: Boolean,
            default: false
        },
        //menubar 位于右侧  默认 false
        menubarRight: {
            type: Boolean,
            default: false
        },
        //menubar 宽度 只能是固定尺寸 256px|15% 形式
        menubarWidth: {
            type: String,
            default: '256px'
        },
        //menubar 横向折叠后的宽度 默认 cssvar.size.bar.m
        menubarCompactWidth: {
            type: String,
            default: ''
        },
        //menus 菜单|导航 数据源 v-model 双向绑定
        menus: {
            type: Array,
            default: () => [/*数据结构与 spf-menu 组件 currentMenus 格式一致*/]
        },
        //懒加载 子菜单 数据的 api
        subMenuLazyloadApi: {
            type: String,
            default: ''
        },
        //菜单项 notice 数据源 api
        menuNoticeApi: {
            type: String,
            default: ''
        },
        //传入的 spf-menu 组件的额外参数
        menuParams: {
            type: Object,
            default: () => {
                return {};
            }
        },

        //mainbody 主要区域的 组件参数  通常是 spf-layout-x 组件
        mainbodyParams: {
            type: Object,
            default: () => {
                return {};
            }
        },

        /**
         * 是否显示边框
         * 默认 true 内部子组件将联动
         */
        border: {
            type: String,
            default: 'bd-m bd-po-t bdc-m'
        },
    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    //针对 根组件 spf-block
                    root: 'spf-layout',
                    //navbar
                    navbar: '',
                    //taskbar
                    taskbar: '',
                    //logobar
                    logobar: 'flex-x flex-x-center flex-y-center',
                    //mainbody
                    mainbody: '',
                },
                style: {
                    root: '',
                    navbar: '',
                    taskbar: '',
                    logobar: 'position: relative; padding: 0px; overflow: hidden;', 
                    mainbody: '',
                },
            },
            prefix: 'layout',
            group: {
                //组开关
                withNavbar: true,
                withTaskbar: true,
                withMenubar: true,
            },
            sub: {
                //size: true,
                //color: true,
                //animate: 'disabled:false',
            },
            switch: {
                //启用 下列样式开关
                //针对 根组件 spf-block
                border: '.{swv}',

                //针对 navbar
                'border:with-navbar@navbar #1': '.{swv}',
                'border:with-navbar@navbar #2': 'border-width: 0px 0px {swv@csv-val,size.bd.m} 0px;',

                //针对 taskbar
                'border:with-taskbar@taskbar #1': '.{swv}',
                'border:with-taskbar@taskbar #2': 'border-width: {swv@csv-val,size.bd.m} 0px 0px 0px;',

                //针对 logobar
                'logo:with-navbar@logobar': 'width: {swv@get,menubarWidthPx}; height: 100%;',
                'withMenubar:!with-navbar@logobar': 'height: 96px;',
                'menubarCompacted:!with-navbar@logobar #1': 'height: {swv@get,menubarCompactWidthPx};',
                'menubarCompacted:!with-navbar@logobar #2': '.mg-m mg-po-b',
            },
            csvKey: {
                size: 'bar',
                color: 'bgc',
            },
        },

        //通过 base-parent 处理子组件 props 透传
        subComps: {
            default: {
                //navbar  基于 spf-bar 组件
                navbar: {
                    size: 'xxl'
                },
                //taskbar  基于 spf-bar 组件
                taskbar: {
                    size: 'm'
                },
                //mainbody  基于 spf-layout-x 组件
                mainbody: {
                    innerBorder: '{{border!==""}}[Boolean]',
                    //colExtraClass: this.menubarRight ? [null, 'bgc-white-m'] : ['bgc-white-m', null],
                },
                //menu  基于 spf-menu 组件
                menu: {
                    //border: this.border!=='' ? `${this.border} bd-po-tb` : '',
                    //border: '{{border!=="" ? border+" bd-po-tb" : ""}}[String]'
                    '{{border!==""}}': {
                        border: 'foo-bar {{border}} bd-po-tb [String]',
                    },
                },
            },
        },

        /**
         * 内部区域 navbar|taskbar|mainbody|menubar ... 默认的 组件 params 
         * 将与 传入的这些组件的 navbar|taskbar|...Params 合并后 v-bind 传入组件
         * 传入的 ***Params 将覆盖此处定义的
         */
        areasDefaultParams: {
            //navbar  基于 spf-bar 组件
            navbar: {
                size: 'xxl'
            },
            //taskbar  基于 spf-bar 组件
            taskbar: {
                size: 'm'
            },
            //mainbody  基于 spf-layout-x 组件
            mainbody: {
                innerBorder: this.border!=='',
                //colExtraClass: this.menubarRight ? [null, 'bgc-white-m'] : ['bgc-white-m', null],
            },
            //menu  基于 spf-menu 组件
            menu: {
                border: this.border!=='' ? `${this.border} bd-po-tb` : '',
            },
        },

        //menubar compact 状态
        menubarCompacted: false,

        //!! mock
        testMenus: [
            {
                key: 'menu-foo', 
                icon: 'home', 
                label: '菜单 foo',
                sub: [
                    {
                        key: 'menu-foo-a',
                        label: '菜单 foo-a',
                        //icon: 'hearing',
                        cmd: () => console.log('菜单 foo-a is actived'),
                    },
                    {
                        key: 'menu-foo-b',
                        label: '菜单 foo-b',
                        cmd: () => this.testMenuCmd('foo-b'),
                    },
                    {
                        key: 'menu-foo-c',
                        label: '菜单 foo-c',
                        sub: [
                            {
                                key: 'menu-foo-c-a',
                                label: '菜单 foo-c-a'
                            },
                            {
                                key: 'menu-foo-c-b',
                                label: '菜单 foo-c-b',
                                params: {
                                    disabled: true
                                },
                            },
                            {
                                key: 'menu-foo-c-c',
                                label: '菜单 foo-c-c',
                                sub: [
                                    /*{
                                        key: 'menu-foo-c-c-a',
                                        label: '菜单 foo-c-c-a'
                                    },
                                    {
                                        key: 'menu-foo-c-c-b',
                                        label: '菜单 foo-c-c-b'
                                    },*/
                                ],
                                subLazyload: true,
                            },
                        ]
                    },
                    {
                        key: 'menu-foo-d',
                        label: '菜单 foo-d'
                    },
                ]
            },
            {
                key: 'menu-bar', 
                icon: 'outlined-flag', 
                label: '菜单 bar',
                sub: [
                    {
                        key: 'menu-bar-a',
                        label: '菜单 bar-a'
                    },
                    {
                        key: 'menu-bar-b',
                        label: '菜单 bar-b'
                    },
                ]
            },
            {
                key: 'menu-jaz', 
                icon: 'male', 
                label: '菜单 jaz',
                sub: [
                    {
                        key: 'menu-jaz-a',
                        label: '菜单 jaz-a'
                    },
                    {
                        key: 'menu-jaz-b',
                        label: '菜单 jaz-b'
                    },
                    {
                        key: 'menu-jaz-c',
                        label: '菜单 jaz-c',
                        sub: [
                            {
                                key: 'menu-jaz-c-a',
                                label: '菜单 jaz-c-a'
                            },
                            {
                                key: 'menu-jaz-c-b',
                                label: '菜单 jaz-c-b'
                            },
                            {
                                key: 'menu-jaz-c-c',
                                label: '菜单 jaz-c-c'
                            },
                        ]
                    },
                ]
            },
            {
                key: 'menu-tom', 
                icon: 'power', 
                label: '菜单 tom',
                sub: [
                    {
                        key: 'menu-tom-a',
                        label: '菜单 tom-a'
                    },
                    {
                        key: 'menu-tom-b',
                        label: '菜单 tom-b'
                    },
                ]
            },
        ],

    }},
    computed: {
        //withMenu 
        withMenu() {return this.withMenubar;},
        //将 % 形式的 menubar 宽度转为 px 形式
        menubarWidthPx() {
            let is = this.$is,
                isu = n => is.numeric(n),
                isa = a => is.array(a) && a.length>0,
                mw = this.menubarWidth,
                dft = '256px';
            if (!isu(mw)) return dft;
            let mwa = this.$ui.sizeValToArr(mw);
            if (!isa(mwa)) return dft;
            //传入了 纯数字
            if (mwa.length===1 || mwa[1]==='') return mwa[0]+'px';
            //传入了 100px 形式
            if (mwa[1]==='px') return mw;
            //传入了 25% 形式
            if (mwa[1]==='%' || mwa[1]==='vw') {
                let fw = window.innerWidth,
                    mwn = fw * (mwa[0]/100);
                return `${mwn}px`;
            }
            return dft;
        },
        menubarCompactWidthPx() {
            let is = this.$is,
                isu = n => is.numeric(n),
                isa = a => is.array(a) && a.length>0,
                mw = this.menubarCompactWidth,
                dft = this.$ui.cssvar.size.bar.m;
            if (!isu(mw)) return dft;
            let mwa = this.$ui.sizeValToArr(mw);
            if (!isa(mwa)) return dft;
            //传入了 纯数字
            if (mwa.length===1 || mwa[1]==='') return mwa[0]+'px';
            //传入了 100px 形式
            if (mwa[1]==='px') return mw;
            //传入了 25% 形式
            if (mwa[1]==='%' || mwa[1]==='vw') {
                let fw = window.innerWidth,
                    mwn = fw * (mwa[0]/100);
                return `${mwn}px`;
            }
            return dft;
        },
        //根据 menubarRight 参数，动态生成 menubar 插槽名称
        menubarColSlotName() {return this.menubarRight ? 'col-1' : 'col-0';},
        mainbodyColSlotName() {return this.menubarRight ? 'col-0' : 'col-1';},

        //layout 布局组件通用样式参数
        layoutVars() {
            let is = this.$is,
                isu = n => is.numeric(n),
                ui = this.$ui,
                csv = ui.cssvar,
                sv = (sk, csvk='bar') => ui.sizeVal(sk, csvk),
                cps = this.areasCustomParams,
                nps = cps.navbar,
                nh = nps.size || 'xxl',
                nhv = sv(nh),
                tps = cps.taskbar,
                th = tps.size || 'm',
                thv = sv(th),
                rtn = {};

            //计算得到的 navbar 高度
            rtn.navbarHeight = nhv;
            //计算得到的 taskbar 高度
            rtn.taskbarHeight = thv;
            //默认 menubar 菜单栏宽度 4*navbar 高度   默认 256px
            rtn.menubarWidth = this.menubarWidthPx;
            //默认 menubar 折叠后宽度  cssvar.size.bar.m
            rtn.menubarCompactWidth = this.menubarCompactWidthPx;
            //默认 logo 高度 navbar 高度 小一级
            rtn.logoHeight = sv(ui.sizeKeyShiftTo(nh, 's1'));

            return rtn;
        },

        /**
         * 合并 navbar|taskbar|mainbody|menubar 等区域的 默认参数 和 传入的 ***Params
         * 返回处理后的 params{} 通过 v-bind 传入对应的组件
         */
        areasCustomParams() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                dps = this.areasDefaultParams || {},
                rtn = {};
            if (!iso(dps)) return rtn;
            this.$each(dps, (dp, area) => {
                let apk = `${area}Params`,
                    aps = this[apk] || {};
                if (!iso(dp) && !iso(aps)) {
                    rtn[area] = {};
                    return true;
                }
                let dpi = !iso(dp) ? {} : Object.assign({}, dp);
                if (iso(aps)) dpi = Object.assign(dpi, aps);
                rtn[area] = dpi; 
            });
            return rtn;
        },

        //根据 withFoobar 系列参数，生成 mainbody 组件(spf-layout-x) 的 col|compact|resize 等参数
        mainbodyCalcParams() {
            let is = this.$is,
                acps = this.areasCustomParams,
                mps = acps.mainbody,
                wm = this.withMenubar,
                rtn = {};
            

        },
    },
    watch: {
        
    },
    created() {
        console.log(this.getSubCompDefaultProps);
    },
    methods: {
        //响应 menubar compact 动作
        whenMenubarCompact(idx, compact) {
            if (!this.withMenubar) return false;
            let midx = this.menubarRight ? 1 : 0;
            if (midx!==idx) return false;
            this.menubarCompacted = compact;
        },
        //响应 menu-change 动作
        whenMenuChange(currentMenus) {
            return this.$emit('menu-change', currentMenus);
        },
        //响应 menu-item-active 事件
        whenMenuItemActive(keyChain=[]) {
            return this.$emit('menu-item-active', keyChain);
        },
    }
}





defineSpfLayout.template = `<spf-block :stretch="stretch" :grow="grow" :scroll="scroll" v-bind="$attrs" :with-header="withNavbar" :header-params="areasCustomParams.navbar" :with-footer="withTaskbar" :footer-params="areasCustomParams.taskbar" inner-content inner-component="" :cnt-params="areasCustomParams.mainbody" :custom-class="styComputedClassStr.root" :custom-style="styComputedStyleStr.root"><template v-if="withNavbar" v-slot:header="{styProps, headerParams, headerClass, headerStyle}"><div v-if="logo!=='' && logo!=='-empty-'" :class="styComputedClassStr.logobar" :style="styComputedStyleStr.logobar"><spf-icon-logo :icon="logo" size="large"></spf-icon-logo></div><slot :name="navbar" v-bind="{ styProps, navbarParams: headerParams, navbarClass: headerClass, navbarStyle: headerStyle }"></slot></template><template v-if="withTaskbar" v-slot:footer="{styProps, footerParams, footerClass, footerStyle}"><slot :name="navbar" v-bind="{ styProps, navbarParams: footerParams, navbarClass: footerClass, navbarStyle: footerStyle }"></slot></template><template v-slot="{styProps, cntParams, cntClass, cntStyle}"><spf-layout-x v-if="withMenubar" v-bind="cntParams" :col="menubarRight ? '*,'+menubarWidthPx : menubarWidthPx+',*'" :compact="menubarRight ? 1 : 0" :compact-to="menubarCompactWidthPx" :custom-class="cntClass" :custom-style="cntStyle" @col-compact="whenMenubarCompact"><template v-slot:[menubarColSlotName]="{colIdx, layoutXer}"><spf-block grow scroll="thin" :with-header="!withNavbar && logo!=='' && logo!=='-empty-'" :header-class="styComputedClassStr.logobar" :header-style="styComputedStyleStr.logobar" inner-content inner-component=""><template v-if="!withNavbar && logo!=='' && logo!=='-empty-'" #header><spf-icon-logo :icon="menubarCompacted ? ((compactLogo!=='' && compactLogo!=='-empty-') ? compactLogo : logo) : logo" :size="menubarCompacted ? 'medium' : 'large'" :square="menubarCompacted"></spf-icon-logo></template><template #default><spf-menu v-bind="areasCustomParams.menu" :menus="menus" accordion :sub-lazyload-api="subMenuLazyloadApi" :notice-api="menuNoticeApi" :compact="menubarCompacted" @self-uncompact="()=>{layoutXer.toggleCompact(colIdx)}" @menu-change="whenMenuChange" @menu-item-active="whenMenuItemActive"></spf-menu></template></spf-block></template><template v-slot:[mainbodyColSlotName]="{styProps, colIdx, layoutXer}"><slot v-bind="{styProps, colIdx, layoutXer, layouter: $this}"></slot></template></spf-layout-x><slot v-else v-bind="{styProps, layouter: $this}"></slot></template></spf-block>`;

if (defineSpfLayout.computed === undefined) defineSpfLayout.computed = {};
defineSpfLayout.computed.profile = function() {return {};}










/**
 * 合并资源 layout-usrbar.js
 * !! 不要手动修改 !!
 */



const defineSpfLayoutUsrbar = {
    props: {

    },
    data() {return {

    }},
    computed: {},
    methods: {

    }
}


defineSpfLayoutUsrbar.template = `<div class="spf-layout-usrbar flex-x flex-x-end pd-xxl pd-po-r"><spf-button icon="vant-sound" effect="popout" stretch="square" type="primary" custom-class="mg-xs mg-po-r"></spf-button><spf-button icon="vant-setting" effect="popout" stretch="square" type="primary" custom-class="mg-xs mg-po-r"></spf-button><spf-button icon="vant-logout" effect="popout" stretch="square" type="danger" custom-class="mg-xs mg-po-r"></spf-button></div>`;

if (defineSpfLayoutUsrbar.computed === undefined) defineSpfLayoutUsrbar.computed = {};
defineSpfLayoutUsrbar.computed.profile = function() {return {};}




/**
 * 合并资源 layout-navbar.js
 * !! 不要手动修改 !!
 */




const defineSpfLayoutNavbar = {
    mixins: [mixinBase],
    props: {

        //logo
        logo: {
            type: String,
            default: 'spf-pms-logo-line-light'
        }

    },
    data() {return {
            
        /**
         * 覆盖 base-style 样式系统参数
         */
        styCalculators: {
            class: {
                //root 是计算根元素的 class 样式类，必须指定
                root: 'styCalcRootClass',
            },
            style: {
                //root 是计算根元素的 css 样式，必须指定
                root: 'styCalcRootStyle',
            }
        },
        styInit: {
            class: {
                //根元素
                root: ['spf-layout-navbar',],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styCssPre: 'navbar',
        styEnable: {
            size: false,
            color: false,
            animate: false,
        },
        stySwitches: {
            //启用 下列样式开关

        },

    }},
    computed: {
        
    },
    methods: {

    }
}





defineSpfLayoutNavbar.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><spf-icon-logo :icon="logo" size="huge"></spf-icon-logo><div class="navbar-content"></div><spf-layout-usrbar></spf-layout-usrbar></div>`;

if (defineSpfLayoutNavbar.computed === undefined) defineSpfLayoutNavbar.computed = {};
defineSpfLayoutNavbar.computed.profile = function() {return {};}










/**
 * 合并资源 layout---layout.js
 * !! 不要手动修改 !!
 */




const defineSpfLayoutLayout = {
    mixins: [mixinBase],
    props: {
        //logo
        logo: {
            type: String,
            default: 'spf-pms-logo-line-light'
        }
    },
    data() {return {
        
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: 'spf-layout flex-x flex-y-stretch bd-m bdc-m bd-po-t bgc-m',
                }
            },
        },

    }},
    computed: {
        
    },
    methods: {

    }
}





defineSpfLayoutLayout.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><div class="layout-leftside flex-y flex-x-stretch bd-m bd-po-r"><div class="layout-logobar flex-x flex-x-center bg-backdrop-blur"><spf-icon-logo :icon="logo" size="large"></spf-icon-logo></div><div class="layout-menubar flex-1 flex-y flex-x-center flex-no-shrink scroll-y scroll-none"><slot name="menubar"></slot></div><div class="layout-usrbar flex-x flex-x-center bg-backdrop-blur bd-m bd-po-t pd-m pd-po-rl"><div class="layout-usr-avator mg-s mg-po-r"></div><div class="flex-1"></div><spf-button icon="menu" effect="popout" stretch="square"></spf-button><spf-button icon="power-settings-new" effect="popout" stretch="square" type="danger"></spf-button></div></div><div class="layout-mainbody flex-1 flex-y flex-x-stretch flex-y-start"><div class="layout-navtab flex-x flex-x-center bg-backdrop-blur"><spf-tabbar v-model="$ui.testTabActive" :tab-list="$ui.testTabList" closable position="top" align="left" enable-scroll custom-style="height: 64px;"></spf-tabbar></div><div class="layout-body flex-1 flex-y flex-x-start flex-y-start flex-no-shrink scroll-y scroll-none"><slot></slot></div><div class="layout-taskbar flex-x flex-x-end bd-m bd-po-t bg-backdrop-blur"><div class="taskbar-item"><spf-icon icon="chat" shape="fill" size="small" type="success"></spf-icon><div class="taskitem-label">OMS 订货系统微信群</div><spf-button icon="close" size="tiny" effect="popout" shape="circle" type="danger"></spf-button></div><div class="taskbar-item taskitem-active"><spf-icon icon="payment" size="small" type="primary"></spf-icon><div class="taskitem-label">支付宝付款后台查询</div><spf-button icon="close" size="tiny" effect="popout" shape="circle" type="danger"></spf-button></div></div></div></div>`;

if (defineSpfLayoutLayout.computed === undefined) defineSpfLayoutLayout.computed = {};
defineSpfLayoutLayout.computed.profile = function() {return {};}










/**
 * 合并资源 layout-mask.js
 * !! 不要手动修改 !!
 */




const defineSpfLayoutMask = {
    mixins: [mixinAutoProps, mixinBaseDynamic],
    props: {
        
        //mask 颜色，可选：white|black|primary|danger...
        type: {
            type: String,
            default: 'black',
        },
        //mask 颜色浓度 可选 light|normal|dark
        alpha: {
            type: String,
            default: 'normal',
        },
        //mask 是否启用 backdrop-blur 效果
        blur: {
            type: Boolean,
            default: false,
        },
        //是否启用 loading 图标
        loading: {
            type: Boolean,
            default: false,
        },

        //是否启用点击关闭
        clickOff: {
            type: Boolean,
            default: true
        },

        //mask 使用 渐显|渐隐 效果
        //动画类型 animate__*** 类名
        animateType: {
            type: String,
            default: 'fadeIn'
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
    },
    data() {return {
            
        //覆盖 auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: 'spf-mask flex-x flex-y-center flex-x-center'
                }
            },
            prefix: 'mask',
            csvk: {
                size: '',
                color: 'bgc',
            },
            sub: {
                size: false,
                color: true,
                animate: true,
            },
            extra: {
                blur: true,
                alpha: 'normal',
                disabled: true
            },
            switch: {
                root: {
                    '!disabled && ?(animatePropClsk) #class': ['{{animatePropClsk}}'],
                }
            },
        },

        //覆盖 base-dynamic 动态组件参数
        dc: {
            //此动态组件只允许 单例
            multiple: false,
        },
    }},
    computed: {},
    methods: {

        /**
         * 动态组件的 动画效果
         */
        //显示
        async dcShow() {
            if (this.isDcShow===false) {
                //先设置 zIndex
                await this.setZindex();
                //执行显示动画
                await this.dcToggle('show', true);
                //触发事件
                this.$emit('mask-on');
                return true;
            }
            return false;
        },
        //隐藏
        async dcHide() {
            if (this.isDcShow===true) {
                //执行显示动画
                await this.dcToggle('show', false);
                //触发事件
                this.$emit('mask-off');
                return true;
            }
            return false;
        },

        //处理点击事件
        whenMaskClick(event) {
            if (this.clickOff === true) {
                //maskOff
                this.$ui.maskOff().then(()=>{
                    return this.$emit('mask-click');
                });
            }
            return this.$emit('mask-click');
        },
    }
}





defineSpfLayoutMask.template = `<div :class="autoComputedStr.root.class" :style="autoComputedStr.root.style" @click="whenMaskClick"><spf-icon v-if="loading" icon="spinner-spin" size="huge" spin></spf-icon></div>`;

if (defineSpfLayoutMask.computed === undefined) defineSpfLayoutMask.computed = {};
defineSpfLayoutMask.computed.profile = function() {return {};}










/**
 * 合并资源 layout-x.js
 * !! 不要手动修改 !!
 */




const defineSpfLayoutX = {
    mixins: [mixinBase],
    props: {
        /**
         * 可指定水平方向的子元素各自占用宽度比例
         * 可有下列指定方式：
         *      '*'             默认，1 个子元素，应用 .flex-1 原子类
         *      '20%,*'         2 个子元素，第一个宽度 20%， 第二个应用 .flex-1 原子类
         *      '*,256px'       2 个子元素，第一个应用 .flex-1 原子类，第二个宽度 256px
         *      '20%,70%,10%'   3 个子元素，各自占用对应的 % 宽度
         *      '1,4,2'         3 个子元素，分别应用 .flex-n 原子类
         */
        col: {
            type: [String, Array],
            default: '*'
        },

        /**
         * 定义 col 子元素的通用 class[]|style{} 由 base-style 系统调用
         *      class   可以是 string|array
         *      style   可以是 string|object
         */
        colClass: {
            type: [String, Array],
            default: ''
        },
        colStyle: {
            type: [String, Object],
            default: ''
        },
        /**
         * 可额外定义 col 子元素各自的 class[]|style{}
         * 数组格式，长度与 colLength 一致，并对应
         *      class[]     支持 []|string
         *      style{}     支持 {}|string
         */
        colExtraClass: {
            type: Array,
            default: () => []
        },
        colExtraStyle: {
            type: Array,
            default: () => []
        },

        /**
         * compact 指定可 自动收缩|展开 的 col 
         * !! col 的宽度设置值 不能是 * 或 flex-n 原子类，必须是某个确定的宽度值 256px | 30%
         * 指定 col 序号，从 0 开始，一个(Number) 或 多个(Array)
         * 指定了 compact 的 col 可通过点击 收缩|展开 按钮 自动 收缩|展开
         * 
         * !! compact 优先级高于 resize，即某个 col 如果指定了 compact 则 resize 参数无效
         */
        compact: {
            type: [Number, Array],
            default: -1
        },
        /**
         * 指定收缩后的最小宽度 '32px' 或 ['32px', '5%']
         * 如果传入数组，则顺序必须与 compact 数组顺序一致 
         */
        compactTo: {
            type: [String, Array],
            default: ''
        },
        /**
         * 是否在 某个 col 进入 compact 状态时，使用不同的 slot
         * 如果传入数组，则顺序必须与 compact 数组顺序一致 
         */
        differentCompactSlot: {
            type: [Boolean, Array],
            default: false
        },

        /**
         * resize 指定可通过拖拽调整宽度的 col
         * !! col 的宽度设置值 不能是 * 或 flex-n 原子类，必须是某个确定的宽度值 256px | 30%
         * 指定 col 序号，从 0 开始，一个(Number) 或 多个(Array)
         * 指定了 resize 的 col 将在元素的右侧边缘自动生成可拖拽的 handler 元素
         */
        resize: {
            type: [Number, Array],
            default: -1
        },
        /**
         * 指定 resize 宽度调整的 上下限 可以是 '32px' 或 ['32px', '5%']
         * 如果传入数组，则顺序必须与 resize 数组顺序一致 
         * !! 可以不指定
         */
        //宽度下限
        resizeMin: {
            type: [String, Array],
            default: ''
        },
        //宽度上限
        resizeMax: {
            type: [String, Array],
            default: ''
        },

        //是否显示内部组件的 边框
        innerBorder: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: 'spf-layout-x flex-x flex-x-start flex-y-stretch flex-no-shrink flex-1 stretch-row',
                    //指定 col 子元素 通用的初始 class[]
                    col: 'spf-layout-x-col flex-y flex-no-shrink scroll-y scroll-thin',
                    //指定 compact btn 子元素 通用的 初始 class[]
                    compact: 'spf-layout-x-col-compact-btn flex-x flex-x-center flex-y-center flex-no-shrink',
                },
                style: {
                    root: '',
                    //指定 col 子元素 通用的初始 style{}
                    col: '',
                    //指定 compact btn 子元素 通用的初始 style{}
                    compact: '',
                },
            },
            prefix: 'layout-x',
            sub: {
                size: true,
                color: true,
                //animate: 'disabled:false',
            },
            switch: {
                //启用 下列样式开关
                //effect:     '.bar-effect effect-{swv}',
                //stretch:    '.stretch-{swv}',
                //tightness:  '.bar-tightness tightness-{swv}',
                //shape:      '.bar-shape shape-{swv}',
                //'hoverable:disabled': '.hoverable',
                //active:     '.active',
                //grow: '.flex-1',
                //根元素上挂载 scroll-y
                //rootScroll: '.scroll-y scroll-{swv}',
            },
            csvKey: {
                size: 'bar',
                color: 'bgc',
            },
        },

        //某个 col 子元素的 默认参数格式
        defaultCol: {
            //此 col 在 colArr 中的序号 0 开始
            idx: -1,
            //在 colExtraClass|Style 中定义的 各 col 额外的 class[]|style{}
            extra: {
                class: [],  //!! 一定是 []
                style: {},  //!! 一定是 {}
            },

            //在 props.col 中定义的 col 初始宽度设定 可能是 128px|35%|2|*
            set: '',

            //compact 自动缩放参数
            compact: false,
            compactTo: '',
            differentSlot: false,

            //resize 宽度调整参数
            resize: false,
            resizeMin: '',
            resizeMax: '',
        },

        //col 当前的 compact 状态，[true, false, ...] 形式
        compactedCols: [], 

        //col 当前的 resize 状态，未被 resize 设为 false 否则设为 resize 后的 width 尺寸值 带单位
        resizedCols: [],
    }},
    computed: {
        //将 col 参数转为 []
        colArr() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                col = this.col;
            if (isa(col)) return col;
            if (!iss(col)) return [];
            col = col.split(',').map(i=>i.trimAny(' '));
            return col;
        },
        //包含子元素的 长度
        colLength() {
            return this.colArr.length;
        },
        
        //将传入的 colExtraClass|Style 转为 colLength 长度的 [... class[]|style{} ...]
        colExtraClassArr() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ecls = this.colExtraClass || [],
                len = this.colLength,
                rtn = [];
            //未指定额外 class 则返回对应长度的 [... [], ...]
            if (!isa(ecls)) return this.$cgy.arr(len).map(i=>[]);
            //依次解析各 col
            for (let i=0;i<len;i++) {
                if (!is.defined(ecls[i]) || !(iss(ecls[i]) || isa(ecls[i]))) {
                    rtn.push([]);
                    continue;
                }
                if (iss(ecls[i])) {
                    rtn.push(this.$cgy.toClassArr(ecls[i]));
                } else {
                    rtn.push(ecls[i]);
                }
            }
            return rtn;
        },
        colExtraStyleObj() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                esty = this.colExtraStyle || [],
                len = this.colLength,
                rtn = [];
            //未指定额外 style 则返回对应长度的 [... {}, ...]
            if (!iso(esty)) return this.$cgy.arr(len).map(i=>{});
            //依次解析各 col
            for (let i=0;i<len;i++) {
                if (!is.defined(esty[i]) || !(iss(esty[i]) || iso(esty[i]))) {
                    rtn.push({});
                    continue;
                }
                if (iss(esty[i])) {
                    rtn.push(this.$cgy.toCssObj(esty[i]));
                } else {
                    rtn.push(esty[i]);
                }
            }
            return rtn;
        },
        //compact 参数处理   返回 colLength 长度的 []
        colCompactOpt() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isn = n => is.realNumber(n) && n*1>=0,
                isu = n => is.numeric(n),
                cmpt = this.compact,
                cmpto = this.compactTo,
                dslot = this.differentCompactSlot,
                cols = this.colArr,
                cpa = [];
            if (cols.length<=0 || !(isa(cmpt) || isn(cmpt))) return [];
            //将传入的 compact|compactTo|differentCompactSlot 参数转为 []
            if (isn(cmpt)) cmpt = [cmpt];
            if (isu(cmpto)) cmpto = [cmpto];
            if (is.boolean(dslot)) dslot = [dslot];
            if (!isa(cmpt) || !isa(cmpto) || cmpt.length !== cmpto.length) return [];
            if (!isa(cmpt) || !isa(dslot) || cmpt.length !== dslot.length) return [];
            //将 compact 参数格式化为 defaultCol 中的形式
            this.$each(cols, (coli, i) => {
                if (!cmpt.includes(i)) {
                    cpa.push({
                        compact: false,
                        compactTo: '',
                        differentSlot: false,
                    });
                    return true;
                }
                let idx = cmpt.indexOf(i);
                cpa.push({
                    compact: true,
                    compactTo: cmpto[idx],
                    differentSlot: dslot[idx],
                });
            });
            return cpa;
        },
        //resize 参数处理   返回 colLength 长度的 []
        colResizeOpt() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isn = n => is.realNumber(n) && n*1>=0,
                isu = n => is.numeric(n),
                rsz = this.resize,
                rszmin = this.resizeMin,
                rszmax = this.resizeMax,
                cols = this.colArr,
                rszo = [];
            if (cols.length<=0 || !(isa(rsz) || isn(rsz))) return [];
            //将传入的 resize|resizeMin|resizeMax 参数转为 []
            if (isn(rsz)) rsz = [rsz];
            if (isu(rszmin)) rszmin = [rszmin];
            if (isu(rszmax)) rszmax = [rszmax];
            if (!isa(rsz)) return [];
            //将 resize 参数格式化为 defaultCol 中的形式
            this.$each(cols, (coli, i)=>{
                if (!rsz.includes(i)) {
                    rszo.push({
                        resize: false,
                        resizeMin: '',
                        resizeMax: ''
                    });
                    return true;
                }
                let ridx = rsz.indexOf(i),
                    min = rszmin[ridx],
                    max = rszmax[ridx];
                rszo.push({
                    resize: true,
                    resizeMin: (isa(rszmin) && is.defined(min) && isu(min)) ? min : '',
                    resizeMax: (isa(rszmax) && is.defined(max) && isu(max)) ? max : '',
                });
            });
            return rszo;
        },

        /**
         * 生成标准化的 col 参数，与 defaultCol 数据结构一致  返回 colLength 长度的 []
         * 
         */
        colOpt() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isn = n => is.realNumber(n) && n*1>=0,
                iso = o => is.plainObject(o) && !is.empty(o),
                cols = this.colArr,
                ecls = this.colExtraClassArr || [],
                esty = this.colExtraStyleObj || [],
                cmpt = this.colCompactOpt,
                rsz = this.colResizeOpt,
                opts = [];
            if (cols.length<=0) return [];
            this.$each(cols, (coli, i) => {
                //基础 opt
                let opti = {
                        idx: i,
                        set: coli,
                        extra: {
                            class: is.defined(ecls[i]) ? ecls[i] : [],
                            style: is.defined(esty[i]) ? esty[i] : {}
                        }
                    },
                    //只有 100px|50% 形式的静态宽度值，才能支持 compact|resize
                    stcw = !(isn(coli) || coli==='*');
                    
                //compact opt
                if (!stcw || !isa(cmpt) || !is.defined(cmpt[i]) || !iso(cmpt[i])) {
                    //不支持 或 未指定 compact 参数
                    opti = Object.assign(opti, {
                        compact: false,
                        compactTo: ''
                    });
                } else {
                    opti = Object.assign(opti, cmpt[i]);
                }
                //resize opt
                if (!stcw || opti.compact===true || !isa(rsz) || !is.defined(rsz[i]) || !iso(rsz[i])) {
                    //不支持 或 未指定 resize 参数
                    opti = Object.assign(opti, {
                        resize: false,
                        resizeMin: '',
                        resizeMax: ''
                    });
                } else {
                    opti = Object.assign(opti, rsz[i]);
                }
                //合并到 opts
                opts.push(opti);
            });
            return opts;
        },

        /**
         * 根据 colOpt 参数，计算各子元素的 class[]|style{}
         */
        //计算各子元素的 class[]
        colClassArr() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                isn = is.realNumber,
                isu = is.numeric,
                //base-style 样式计算系统得到的 styComputedClass.col 各 col 元素通用的 class[]
                common = this.styComputedClass.col || [],
                //base-style 样式计算系统得到的样式参数
                styProps = this.styProps,
                clen = this.colLength,
                col = this.colOpt,
                cls = [];
            this.$each(col, (coli,i) => {
                let seti = coli.set,
                    extra = coli.extra.class || [],
                    //在 base-style 样式计算系统生成的 通用 col class[] 基础上，叠加处理
                    clsi = [...common];
                if (isn(seti)) {
                    //'1,2,1' 指定纯数字形式 宽度
                    clsi.push(`flex-${seti}`);
                } else if (seti === '*') {
                    //'256px,*,25%' 包含可变宽度 * 标记
                    clsi.push('flex-1');
                } else if (isu(seti)) {
                    //'256px,20%' 包含带单位数字形式的 宽度
                    //不适用 class[] 应在 style{} 中定义
                } else {
                    //其他 指定了不合法的宽度值，不操作
                }

                //根据 styProps 中可能存在的 边框参数，计算子元素的边框参数
                if ((styProps.hasBd === true || this.innerBorder) && i<clen-1) {
                    //仅对 非最后一列 计算边框
                    //边框位置默认在 右侧
                    clsi.push('bd-po-r');
                    //边框宽度
                    if (iss(styProps.bd)) {
                        clsi.push(`bd-${styProps.bd}`);
                    } else {
                        clsi.push('bd-m');
                    }
                    //边框颜色
                    if (iss(styProps.bdc)) {
                        clsi.push(`bdc-${styProps.bdc}`);
                    } else {
                        clsi.push('bdc-m');
                    }
                }

                //合并 extra class 额外定义的 各 col 的 class[]
                if (isa(extra)) clsi.push(...extra);

                //去重
                clsi = clsi.unique();
                //将 clsi 添加到 colClassArr
                cls.push(clsi);
            });
            return cls;
        },
        //计算各子元素的 style{}
        colStyleObj() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isn = is.realNumber,
                isu = is.numeric,
                //base-style 样式计算系统得到的 styComputedStyle.col 各 col 元素通用的 style{}
                common = this.styComputedStyle.col || {},
                col = this.colOpt,
                //当前 col 的 compact 状态
                ccols = this.compactedCols || [],
                //当前 col 的 resize 状态
                rcols = this.resizedCols || [],
                sty = [];
            this.$each(col, (coli,i) => {
                let seti = coli.set,
                    extra = coli.extra.style || {},
                    //在 base-style 样式计算系统生成的 通用 col style{} 基础上，叠加处理
                    styi = this.$cgy.mergeCss({}, common);
                /**
                 * 只有指定了 100px|50% 形式的确定的宽度值，才需要指定 style
                 */
                if (isu(seti)) {
                    if (coli.compact === true && is.defined(ccols[i]) && ccols[i]===true) {
                        //需要检查当前的 compact 状态
                        //如果 当前 col 为 compact 状态，则将 width 设为 compactTo
                        styi.width = coli.compactTo;
                    } else if (coli.resize === true && is.defined(rcols[i]) && rcols[i]!==false) {
                        //需要检查当前的 resize 状态
                        //如果 当前 col 已被 resize 且记录了 resize 后的 width，则作为 当前 width
                        styi.width = rcols[i];
                    } else {
                        //设为初始值
                        styi.width = seti;
                    }
                }

                //合并 extra style 额外的 各 col 的 style{}
                if (iso(extra)) styi = this.$cgy.mergeCss(styi, extra);
                
                //将 styi 添加到 colStyleObj
                sty.push(styi);
            });
            return sty;
        },
        //将计算得到的 各子元素的 class[] 转为 string
        colClassStr() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                cls = this.colClassArr,
                clss = [];
            this.$each(cls, (clsi,i) => {
                if (!isa(clsi)) {
                    clss.push('');
                    return true;
                }
                clss.push(clsi.join(' '));
            });
            return clss;
        },
        //将计算得到的 各子元素的 style{} 转为 string
        colStyleStr() {
            let is = this.$is,
                isc = c => this.$cgy.isCssObj(c),
                sty = this.colStyleObj,
                stys = [];
            this.$each(sty, (styi,i) => {
                if (!isc(styi)) {
                    stys.push('');
                    return true;
                }
                stys.push(this.$cgy.toCssString(styi));
            });
            return stys;
        },

        /**
         * 根据 colCompactOpt 计算各子元素的 compact-btn 元素的 class[]|style{}
         */
        //计算各子元素 compact-btn 的 class[]
        compactBtnClassArr() {
            let is = this.$is,
                clen = this.colLength,
                //base-style 系统自动生成的 compact btn 元素的 通用 class[]
                common = this.styComputedClass.compact || [],
                styProps = this.styProps,
                ccols = this.compactedCols,
                col = this.colOpt,
                cpbcls = [];
            this.$each(col, (coli,i)=>{
                //未开启 compact 的列直接设置按钮 class[] 为 [] 空数组
                if (!is.defined(coli.compact) || coli.compact!==true) {
                    cpbcls.push([]);
                    return true;
                }
                //在 base-style 生成的 通用 class[] 基础上，叠加处理
                let clsi = [...common];
                //compact-btn 位置
                if (i>=clen-1) {
                    //最后一列 compact-btn 位于 左侧
                    clsi.push('compact-btn-left');
                } else {
                    //其他列 compact-btn 位于 右侧
                    clsi.push('compact-btn-right');
                }
                //是否带边框
                if (styProps.hasBd===true || this.innerBorder) {
                    clsi.push('compact-btn-border');
                }
                //根据当前的 展开状态 决定 按钮图标
                if (is.defined(ccols[i]) && ccols[i]===true) {
                    clsi.push('compact-on');
                } else {
                    clsi.push('compact-off');
                }
                //记录 class[]
                cpbcls.push(clsi);
            });
            //返回
            return cpbcls;
        },
        //计算各子元素 compact-btn 的 style{}
        compactBtnStyleObj() {
            let is = this.$is,
                isu = n => is.numeric(n),
                clen = this.colLength,
                //base-style 系统自动生成的 compact btn 元素的 通用 style{}
                common = this.styComputedStyle.compact || {},
                //styProps = this.styProps,
                //ccols = this.compactedCols,
                //已计算好的 子元素 style{}
                colSty = this.colStyleObj,
                //ui
                ui = this.$ui,
                col = this.colOpt,
                cpbsty = [];
            this.$each(col, (coli,i)=>{
                //未开启 compact 的列直接设置按钮 style{} 为 空 {}
                if (!is.defined(coli.compact) || coli.compact!==true) {
                    cpbsty.push({});
                    return true;
                }
                //已计算好的子元素 style{}
                let cstyi = colSty[i] || {};
                //在 base-style 生成的 通用 style{} 基础上，叠加处理
                let styi = this.$cgy.mergeCss({}, common);
                //compact-btn 位置
                if (i>=clen-1) {
                    //最后一列 compact-btn 位于 左侧 left 值由 compact-btn-left 类定义
                    //styi.left = ui.sizeValMul(ui.sizeValSub(ui.cssvar.size.pd.m, 1), -1);
                } else {
                    //其他列 compact-btn 位于 右侧
                    if (isu(cstyi.width)) {
                        styi.left = ui.sizeValSub(cstyi.width, 1);
                    }
                }
                //记录 style{}
                cpbsty.push(styi);
            });
            //返回
            return cpbsty;
        },
        //class[] 转为 class-string
        compactBtnClassStr() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                cls = this.compactBtnClassArr,
                clss = [];
            this.$each(cls, (clsa,i)=>{
                if (!isa(clsa)) {
                    clss.push('');
                    return true;
                }
                //去重
                clsa = clsa.unique();
                //合并
                clss.push(clsa.join(' '));
            });
            return clss;
        },
        //style{} 转为 style-string
        compactBtnStyleStr() {
            let is = this.$is,
                isc = c => this.$cgy.isCssObj(c),
                sty = this.compactBtnStyleObj,
                stys = [];
            this.$each(sty, (styo,i)=>{
                if (!isc(styo)) {
                    stys.push('');
                    return true;
                }
                //style{} 转为 string
                stys.push(this.$cgy.toCssString(styo));
            });
            return stys;
        },
        //compact btn icon 图标
        compactBtnIcon() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                cbcls = this.compactBtnClassArr || [],
                ccols = this.compactedCols || [],
                len = this.colLength,
                rtn = [];
            this.$each(cbcls, (clsi,i) => {
                if (!isa(clsi)) {
                    //未开启 compact
                    rtn.push('');
                    return true;
                }
                let cpd = is.defined(ccols[i]) && ccols[i]===true,  //当前 col 是否已经折叠
                    //按钮在左侧
                    cpl = clsi.includes('compact-btn-left');
                if (!cpd) {
                    rtn.push(cpl ? 'keyboard-arrow-right' : 'keyboard-arrow-left');
                } else {
                    rtn.push(cpl ? 'keyboard-arrow-left' : 'keyboard-arrow-right');
                }
            });
            return rtn;
        },
    },
    methods: {
        //compact-btn 折叠|展开 某个列
        toggleCompact(idx) {
            let is = this.$is,
                clen = this.colLength,
                ccols = this.compactedCols;
            //初始化 compactedCols
            if (is.array(ccols) && ccols.length<=0) {
                for (let i=0;i<clen;i++) {
                    this.compactedCols.push(false);
                }
                ccols = this.compactedCols;
            }
            //读取当前 idx 的子元素列的 compact 状态
            let ccoli = is.defined(ccols[idx]) && ccols[idx]===true;
            //设置 compacted 状态
            this.$set(this.compactedCols, idx, !ccoli);
            //触发事件
            this.$emit(`col-compact`, idx, !ccoli);
        },

        //定义某个 col 被 drag-resize 后执行
        afterDragResize(el, size) {
            let is = this.$is,
                //额外传入 v-drag-resize 指令的 col idx
                idx = el.$drag.extra.idx,
                //resize 后的 col width 尺寸值 带单位
                w = size.x,
                clen = this.colLength,
                rcols = this.resizedCols;
            //console.log(idx, w);
            //初始化 resizedCols
            if (is.array(rcols) && rcols.length<=0) {
                for (let i=0;i<clen;i++) {
                    this.resizedCols.push(false);
                }
                rcols = this.resizedCols;
            }
            //设置 resized 状态
            this.$set(this.resizedCols, idx, w);
            //触发事件
            this.$emit(`col-resize`, idx, w);
        },
    }
}





defineSpfLayoutX.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><template v-if="colLength>0"><template v-for="(si, idx) in colLength"><div v-if="colOpt[idx] && colOpt[idx].resize && colOpt[idx].resize===true" :key="'layout-x-col-'+idx" :class="colClassStr[idx]" :style="colStyleStr[idx]" v-drag-resize:x="{ inComponent: $this, limit: { x: [colOpt[idx].resizeMin, colOpt[idx].resizeMax] }, idx: idx, }"><slot :name="'col-'+idx" v-bind="{styProps, colIdx: idx, layoutXer: $this}"></slot></div><template v-else><div v-if="colOpt[idx] && colOpt[idx].compact && colOpt[idx].compact===true" :key="'layout-x-col-'+idx+'-wrapper'" class="flex-y flex-no-shrink" style="position: relative; height: 100%; overflow: visible;"><div :key="'layout-x-col-'+idx" :class="colClassStr[idx]" :style="colStyleStr[idx]"><slot v-if="colOpt[idx].differentSlot!==true" :name="'col-'+idx" v-bind="{styProps, colIdx: idx, layoutXer: $this}"></slot><template v-else><slot v-if="compactedCols[idx] && compactedCols[idx]===true" :name="'col-'+idx+'-compacted'" v-bind="{styProps, colIdx: idx, layoutXer: $this}"></slot><slot v-else :name="'col-'+idx" v-bind="{styProps, colIdx: idx, layoutXer: $this}"></slot></template></div><div :class="compactBtnClassStr[idx]" :style="compactBtnStyleStr[idx]" :title="(compactedCols[idx] && compactedCols[idx]===true) ? '展开':'折叠'" @click="toggleCompact(idx)"><spf-icon :icon="compactBtnIcon[idx]" size="small"></spf-icon></div></div><div v-else :key="'layout-x-col-'+idx" :class="colClassStr[idx]" :style="colStyleStr[idx]"><slot :name="'col-'+idx" v-bind="{styProps, colIdx: idx, layoutXer: $this}"></slot></div></template></template></template></div>`;

if (defineSpfLayoutX.computed === undefined) defineSpfLayoutX.computed = {};
defineSpfLayoutX.computed.profile = function() {return {};}










/**
 * 合并资源 tabbar-item.js
 * !! 不要手动修改 !!
 */




const defineSpfTabbarItem = {
    mixins: [mixinBase],
    props: {
        //tab-item 数据
        tabKey: {
            type: String,
            default: '',
            required: true,
        },
        tabIcon: {
            type: String,
            default: '-empty-',
        },
        tabLabel: {
            type: String,
            default: '',
        },
        //传入的 tabsize 用于指定 close btn 的 size 参数
        tabSize: {
            type: [String, Number],
            default: 'normal'
        },

        //是否可关闭
        closable: {
            type: Boolean,
            default: false
        },

        //是否激活
        active: {
            type: Boolean,
            default: false
        },

    },
    data() {return {
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: ['tabbar-item', 'flex-x', 'flex-x-center'],
                }
            },
            prefix: 'tabbar-item',
            switch: {
                //启用 下列样式开关
                closable: true,
                active: true,
            },
        },
        
        
    }},
    computed: {
        //close-btn size
        closeBtnSize() {
            //关闭按钮的尺寸，比 tab 尺寸小两级
            return this.$ui.sizeKeyShiftTo(this.tabSize, 's3');
        },
    },
    methods: {
        //tab-item 点击事件
        whenTabItemClick(event) {
            if (this.active === true) return false;
            return this.$emit('tabitem-active', this.tabKey);
        },
    }
}





defineSpfTabbarItem.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root" @click="whenTabItemClick"><spf-icon v-if="tabIcon!=='' && tabIcon!=='-empty-'" :icon="tabIcon"></spf-icon><label v-html="tabLabel"></label><div v-if="active && closable" class="flex-1"></div><spf-button v-if="active && closable" :size="closeBtnSize" icon="close" effect="popout" shape="circle" type="danger"></spf-button></div>`;

if (defineSpfTabbarItem.computed === undefined) defineSpfTabbarItem.computed = {};
defineSpfTabbarItem.computed.profile = function() {return {};}










/**
 * 合并资源 tabbar.js
 * !! 不要手动修改 !!
 */




const defineSpfTabbar = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
    },
    props: {
        /**
         * 覆盖 base-style.mixin 中的 size 参数
         * 可选：mini|small|normal|medium
         */
        size: {
            type: String,
            default: 'normal'
        },

        //tabbar 位置 可选：top|bottom
        position: {
            type: String,
            default: 'top',
        },

        //align 对齐形式  可选：left|center|right
        align: {
            type: String,
            default: 'center'
        },

        //是否使用 border 样式
        border: {
            type: Boolean,
            default: false
        },

        //启用横向滚动
        enableScroll: {
            type: Boolean,
            default: false
        },

        //是否启用 tab-item 的 close 功能
        closable: {
            type: Boolean,
            default: false,
        },

        /**
         * 指定 tab-item 列表 []
         */
        tabList: {
            type: Array,
            default: () => []
        },

        //v-model 关联的 prop 表示当前激活的 tab-item.key
        value: {
            type: [String, Number],
            default: ''
        },
    },
    data() {return {
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: ['spf-tabbar', 'flex-1', 'flex-x', 'flex-y-stretch'],
                }
            },
            prefix: 'tabbar',
            sub: {
                size: true,
            },
            switch: {
                //启用 下列样式开关
                position: true,
                border: true,
                closable: true,
            },
            csvKey: {
                size: 'btn',
                color: '',
            },
        },

    }},
    computed: {
        //根据 size 自动计算 ctrl-start|end 按钮的 size 参数
        ctrlSize() {
            //ctrl 按钮尺寸小一级
            return this.$ui.sizeKeyShiftTo(this.size, 's1');
        },
    },
    methods: {
        //响应 tabitem-active 事件
        whenTabItemActive(tabKey) {
            console.log(tabKey);
            //触发 tab-active 事件
            this.$emit('tab-active', tabKey);
            //触发 input 事件，使 v-model 生效
            return this.$emit('input', tabKey);
        },
    }
}





defineSpfTabbar.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><div class="tabbar-ctrl-start flex-x"><spf-button v-if="enableScroll" icon="keyboard-arrow-left" :size="ctrlSize" effect="popout" stretch="square" disabled></spf-button></div><div class="tabbar-list flex-1 flex-x flex-x-center flex-no-shrink"><div v-if="align !== 'left'" class="tabbar-list-holder"></div><template v-if="tabList.length>0"><spf-tabbar-item v-for="tab of tabList" :key="'tabbar_items_'+tab.key" :tab-key="tab.key" :tab-icon="tab.icon ? tab.icon : '-empty-'" :tab-label="tab.label" :tab-size="size" :closable="closable" :active="value === tab.key" @tabitem-active="whenTabItemActive"></spf-tabbar-item></template><slot></slot><div v-if="align !== 'right'" class="tabbar-list-holder"></div></div><div class="tabbar-ctrl-end flex-x"><spf-button v-if="enableScroll" :size="ctrlSize" icon="keyboard-arrow-right" effect="popout" stretch="square" disabled></spf-button></div></div>`;

if (defineSpfTabbar.computed === undefined) defineSpfTabbar.computed = {};
defineSpfTabbar.computed.profile = function() {return {};}










/**
 * 合并资源 win-content.js
 * !! 不要手动修改 !!
 */



const defineSpfWinContent = {
    props: {},
    data() {return {}},
    computed: {},
    methods: {
        
    }
}


defineSpfWinContent.template = `<div class="flex-1 flex-y flex-x-start">窗口内容</div>`;

if (defineSpfWinContent.computed === undefined) defineSpfWinContent.computed = {};
defineSpfWinContent.computed.profile = function() {return {};}




/**
 * 合并资源 win.js
 * !! 不要手动修改 !!
 */




const defineSpfWin = {
    mixins: [mixinBase, mixinBaseDynamic],
    props: {
        /**
         * 此窗口的元素据
         */
        /**
         * 窗口唯一键名
         * 所有通过 $ui.openWin() 方法打开的窗口，必须指定唯一键名
         * 此键名将用于在 $ui.winList 对象中标记此窗口的实例
         * 手动在页面中使用窗口组件，作为容器包裹自定义内容的 不需要传入此参数
         */
        winKey: {
            type: String,
            default: ''
        },
        //窗口图标
        icon: {
            type: String,
            default: 'desktop-mac'
        },
        iconColor: {
            type: String,
            default: 'primary'
        },
        iconShape: {
            type: String,
            default: 'round'
        },
        spin: {
            type: [Boolean, String],
            default: false
        },
        iconParams: {
            type: Object,
            default() {return {};}
        },

        //窗口标题
        title: {
            type: String,
            default: '窗口'
        },
        //窗口是否可以 最大化|最小化
        maximizable: {
            type: Boolean,
            default: false,
        },
        minimizable: {
            type: Boolean,
            default: false,
        },
        closeable: {
            type: Boolean,
            default: false,
        },

        //窗口的 loading 状态
        loading: {
            type: Boolean,
            default: false,
        },

        /**
         * 是否启用窗口元素
         */
        //tabbar
        /*tabbar: {
            type: Boolean,
            default: false
        },*/
        //ctrlbar
        ctrlbar: {
            type: Boolean,
            default: true
        },
        //cancel|confirmButton
        cancelButton: {
            type: Boolean,
            default: true
        },
        confirmButton: {
            type: Boolean,
            default: true
        },
        cancelIcon: {
            type: String,
            default: 'close'
        },
        confirmIcon: {
            type: String,
            default: 'check'
        },
        cancelLabel: {
            type: String,
            default: '取消'
        },
        confirmLabel: {
            type: String,
            default: '确定'
        },
        //cancel|confirm 回调方法
        /*onCancel: {
            type: Function,
            default: ()=>{
                return () => {}
            }
        },*/
        
        /**
         * 指定此窗口的类型
         * 可选：inside(在页面内部) | popout(普通弹出) | modal(模态弹出)
         * 默认 popout
         */
        winType: {
            type: String,
            default: 'popout',
        },

        //此窗口是否允许拖拽移动，只有 winType != inside 时起效
        moveable: {
            type: Boolean,
            default: false
        },

        //窗口是否启用 border 边框
        border: {
            type: Boolean,
            default: true
        },
        //窗口是否启用 sharp 直角边框
        sharp: {
            type: Boolean,
            default: false
        },

        //窗口是否启用 shadow 可选 false|true 或 normal|thin|bold
        shadow: {
            type: [Boolean, String],
            default: false
        },

        //tightness 窗口内容紧凑程度 可选 loose|normal|tight
        tightness: {
            type: String,
            default: 'normal'
        },

        //hoverable 悬停阴影
        hoverable: {
            type: Boolean,
            default: false,
        },

        //窗口 开启|关闭|minimize|maximize 动画类型 animate__*** 类名
        animateType: {
            type: String,
            default: 'zoomIn'
        },

        /**
         * tabbar 参数
         */
        //tabbar-size 可选：mini|small|normal|medium
        tabbarSize: {
            type: String,
            default: 'small'
        },
        //其他额外的 tabbar 组件参数
        tabbarParams: {
            type: Object,
            default() {return {}},
        },
        //tabitem-list [{key:, label:, icon:, ...}, ...]
        tabList: {
            type: Array,
            default: () => []
        },
        //tabitem-active 当前激活的 tab.key
        tabActive: {
            type: String,
            default: ''
        },

        /**
         * 可直接传入窗口内容 html
         */
        contentHtml: {
            type: String,
            default: '',
        },

        //win 窗口的 confirmed|canceled 外部标记
        winConfirmed: {
            type: Boolean,
            default: false,
        },
        winCanceled: {
            type: Boolean,
            default: false,
        },
        


    },
    data() {return {
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: 'spf-win flex-y flex-x-stretch',
                }
            },
            prefix: 'win',
            sub: {
                animate: 'disabled:false'
            },
            switch: {
                //启用 下列样式开关
                winType: 'type',
                border: true,
                sharp: true,
                shadow: true,
                hoverable: true,
                tightness: true,
                //'dcDisplay.show': 'show',
                'dc.display.minimized': 'minimized',
                'dc.display.maximized': 'maximized',
            },
            csvKey: {
                size: 'block',
                color: 'fc',
            },
        },

        //覆盖 base-dynamic 动态组件参数
        dc: {
            //显示
            display: {
                //maximize|minimize
                maximized: false,
                minimized: false,
                //loading 状态
                loading: false,

                //动画
                ani: {
                    show: {
                        on: 'zoomIn',
                        off: 'zoomOut'
                    },
                    //win 组件自有动画
                    minimize: {
                        on: 'zoomOut',
                        off: 'zoomIn'
                    }
                }
            },
        },

        //窗口的 display 显示状态 覆盖 base-dynamic.mixin 中参数
        /*dcDisplay: {
            //显示|隐藏 默认隐藏
            show: false,
            //maximize|minimize
            maximized: false,
            minimized: false,
            //loading 状态
            loading: false,

            //动画效果
            ani: {
                show: 'zoomIn',
                hide: 'zoomOut'
            }
        },*/

        /**
         * win-tab 标签页相关
         */
        tab: {
            //标准 tab-item 参数格式
            dftItem: {
                //必须的参数
                key: '',
                label: '',
                //可选的参数
                icon: '',
                //如果此 tab 指向某个组件
                component: '',
                compProps: {},
            },

            //当前激活的 tab-item key 未初始化时为 null
            active: null,
        },


    }},
    computed: {
        //判断此窗口是否可以 minimize|maximize|close
        canMinimize() {
            return this.winType !== 'inside' && this.minimizable;
        },
        canMaximize() {
            return this.winType !== 'inside' && this.maximizable;
        },
        canClose() {
            return this.winType !== 'inside' && this.closeable;
        },

        //display 状态
        isDcMinimize() {return this.dc.display.minimized;},
        isDcMaximize() {return this.dc.display.maximized;},

        //判断此窗口是否可以 drag-move
        dragMoveable() {
            return this.winType !== 'inside' && this.moveable;
        },

        //此窗口当前的 position {w:, h:, l:, t:,}
        winPosition() {
            
        },

        //使用标准的 tab-item 数据格式化传入是 tab-list
        tabItemList() {
            let is = this.$is,
                tbl = this.tabList,
                dti = this.tab.dftItem;
            if (!is.array(tbl) || tbl.length<=0) return [];
            let tbil = [];
            this.$each(tbl, (v,k) => {
                if (!is.plainObject(v) || is.empty(v)) return true;
                if (!is.defined(v.key) || !is.defined(v.label)) return true;
                tbil.push(this.$extend({}, dti, v));
            });
            return tbil;
        },
        //获取当前激活的 tab-item 参数
        tabActiveItem() {
            let is = this.$is,
                ta = this.tab.active,
                tbl = this.tabItemList;
            if (!is.string(ta) || ta==='') return {};
            let ti = {};
            this.$each(tbl, (v,k)=>{
                if (is.plainObject(v) && is.defined(v.key) && v.key===ta) {
                    ti = this.$extend({}, v);
                    return false;
                }
            });
            return ti;
        },
        //如果当前激活的 tab-item 指向某个组件，则返回此组件的真实组件名
        tabActiveCompName() {
            let is = this.$is,
                tai = this.tabActiveItem,
                tcn = is.defined(tai.component) ? tai.component : null;
            if (!is.string(tcn) || tcn==='') return '';
            let vcn = this.$vcn(tcn);
            if (is.null(vcn)) return '';
            return vcn;
        },

        //判断是否直接传入了有效的 contentHtml
        hasContentHtml() {
            let is = this.$is,
                html = this.contentHtml;
            if (!is.string(html) || html==='') return false;

        },
    },
    watch: {
        //外部指定的 loading 状态
        loading(nv, ov) {
            this.dc.display.loading = this.loading;
        },
        //指定了外部 tab-active
        tabActive: {
            handler(nv, ov) {
                let is= this.$is,
                    ta = this.tabActive,
                    tbl = this.tabItemList,
                    tbk = (is.array(tbl) && tbl.length>0) ? tbl[0].key : '';

                if (!is.string(ta) || ta==='' || !this.isLegalTabKey(ta)) {
                    this.tab.active = tbk;
                } else {
                    this.tab.active = ta;
                }

            },
            immediate: true,
        },
    },
    created() {
        
    },
    watch: {
        winType: {
            handler(nv, ov) {
                let wtp = this.winType;
                if (wtp==='inside') {
                    //非弹出窗口，不启用 animate 子系统
                    this.$set(this.sty.sub, 'animate', false);
                } else {
                    //弹出窗口，则在 disabled == false 情况下启用 animate
                    this.$set(this.sty.sub, 'animate', 'disabled:false');
                }
            },
            immediate: true,
        },
    },
    methods: {
        /**
         * 动态组件动画相关
         */
        async dcShow(style={}) {
            if (this.winType==='inside') {
                //非弹出窗口不执行显示动画
                return true;
            }
            return await this.dcToggle('show', true, {before: style});
        },
        //set方法
        async dcSetMinimize(min=true) {
            if (this.winType==='inside') {
                //非弹出窗口不执行 minimize
                return false;
            }
            this.$set(this.dc.display, 'minimized', min);
            return await this.$wait(10);
        },
        async dcSetMaximize(max=true) {
            if (this.winType==='inside') {
                //非弹出窗口不执行 maximize
                return false;
            }
            this.$set(this.dc.display, 'maximized', max);
            return await this.$wait(10);
        },

        /**
         * 全局 zIndex 操作
         * !! 覆盖 zindex mixin 中的方法
         */
        whenElMouseDown() {
            if (this.winType==='inside') {
                //非弹出窗口不执行 zIndex 提升
                return false;
            }
            //调用 zindex mixin 中的 whenElMouseDown 原始方法
            return mixinZindex.methods.whenElMouseDown.call(this);
        },

        /**
         * win-minimize|maximize|close
         */
        async winMinimize(event) {
            if (this.canMinimize || this.isDcMinimize || this.winKey==='') return false;
            //执行最小化动画
            await this.dcToggle('minimize', true, {

            });

            //this.dc.display.minimized = true;
            //return this.$ui.minimizeWin(this.winKey).then(()=>{
            //    return this.$emit('minimize', this.winKey);
            //});
        },
        winMaximize(event) {
            if (this.canMaximize || this.winKey==='') return false;
            this.dc.display.maximized = !this.dc.display.maximized;
            this.dc.display.minimized = false;
            return this.$ui.maximizeWin(this.winKey).then(()=>{
                return this.$emit('maximize', this.winKey);
            });
        },
        winClose(event) {
            if (this.canClose || this.winKey==='') return false;
            return this.$ui.closeWin(this.winKey).then(()=>{
                //return this.$emit('close', this.winKey);
            });
        },
        //focus 聚焦
        winFocus() {

        },
        winCancel(event) {

        },
        winConfirm(event) {

        },

        //win-loading 状态切换
        winLoading(loading=null) {
            let is = this.$is;
            if (!is.boolean(loading)) {
                //不指定 loading 状态，则 toggle
                this.dc.display.loading = !this.dc.display.loading;
            } else {
                //直接指定 loading 状态
                this.dc.display.loading = loading;
            }
            //事件
            return this.$emit('loading', this.dc.display.loading);
        },



        /**
         * win-tab 标签页相关
         */
        //判断给定的 tab-key 存在且合法
        isLegalTabKey(key) {
            let is = this.$is,
                tbil = this.tabItemList;
            if (!is.array(tbil) || tbil.length<=0) return false;
            let tks = tbil.map(i=>i.key);
            return is.string(key) && key!=='' && is.array(tks) && tks.includes(key);
        },
        //tab-active 动作
        whenTabActive(key) {
            if (this.isLegalTabKey(key)) {
                this.tab.active = key;
                /*this.$wait(100).then(()=>{
                    let tcn = this.tabActiveCompName;
                    if (tcn!=='') {
                        //当前激活的 tab 指向有效的 component
                        if (!Vue.compLoaded(tcn)) {

                        }
                    }
                });*/
                return this.$emit('tab-active', key);
            }
            return false;
        },
        
    }
}





defineSpfWin.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><div class="win-titbar flex-x" v-drag-move:xy="$this"><spf-icon v-if="icon !== '' && icon !== '-empty-'" :icon="icon" :shape="iconShape" :spin="loading ? true : spin" :color="iconColor" v-bind="iconParams"></spf-icon><div class="win-title flex-1 flex-x">新窗口</div><div class="win-titctrl flex-x flex-x-end"><slot name="titctrl"></slot></div><template v-if="winType !== 'inside'"><spf-button v-if="minimizable" icon="arrow-downward" effect="popout" stretch="square" no-gap @click="winMinimize"></spf-button><spf-button v-if="maximizable" :icon="dc.display.maximized ? 'fullscreen-exit' : 'fullscreen'" effect="popout" stretch="square" no-gap @click="winMaximize"></spf-button><spf-button v-if="closeable" icon="close" type="danger" effect="popout" stretch="square" no-gap @click="winClose"></spf-button></template></div><div v-if="tabList.length>1 && tabItemList.length>1" class="win-tabbar flex-x flex-x-center"><spf-tabbar :value="tab.active" :tab-list="tabItemList" :size="tabbarSize" v-bind="tabbarParams" @tab-active="whenTabActive"></spf-tabbar></div><div class="win-body flex-1 flex-y flex-x-stretch"><template v-if="$is.plainObject(tabActiveItem) && !$is.empty(tabActiveItem)"><slot v-if="tabActiveCompName === ''" :name="'tab-'+tabActiveItem.key" :tab="tabActiveItem" :win="$this"></slot><component v-else :is="tabActiveCompName" v-bind="tabActiveItem.compProps"></component></template><slot></slot></div><div class="win-ctrlbar flex-x flex-x-end"><slot name="winctrl-extra"></slot><div class="flex-1"></div><slot name="winctrl"></slot><spf-button v-if="cancelButton" :icon="cancelIcon" :label="cancelLabel" effect="popout" type="danger" @click="winCancel"></spf-button><spf-button v-if="confirmButton" :icon="confirmIcon" :label="confirmLabel" effect="fill" type="primary" @click="winConfirm"></spf-button></div><div v-if="dc.display.loading" class="win-loading flex-x flex-x-center flex-y-center"><spf-icon icon="spinner-spin" size="huge" type="primary" spin></spf-icon></div></div>`;

if (defineSpfWin.computed === undefined) defineSpfWin.computed = {};
defineSpfWin.computed.profile = function() {return {};}













/**
 * 导出组件定义参数
 * 外部需要使用 Vue.component(key, val) 注册语句来依次注册组件
 * 不要手动修改
 */

export default {
'spf-icon': defineSpfIcon,
'spf-icon-logo': defineSpfIconLogo,
'spf-button-group': defineSpfButtonGroup,
'spf-button': defineSpfButton,
'spf-bar': defineSpfBar,
'spf-bar-sty': defineSpfBarSty,
'spf-block-collapse-item': defineSpfBlockCollapseItem,
'spf-block': defineSpfBlock,
'spf-block-collapse': defineSpfBlockCollapse,
'spf-menu': defineSpfMenu,
'spf-menu-item': defineSpfMenuItem,
'spf-layout': defineSpfLayout,
'spf-layout-usrbar': defineSpfLayoutUsrbar,
'spf-layout-navbar': defineSpfLayoutNavbar,
'spf-layout---layout': defineSpfLayoutLayout,
'spf-layout-mask': defineSpfLayoutMask,
'spf-layout-x': defineSpfLayoutX,
'spf-tabbar-item': defineSpfTabbarItem,
'spf-tabbar': defineSpfTabbar,
'spf-win-content': defineSpfWinContent,
'spf-win': defineSpfWin,
}
