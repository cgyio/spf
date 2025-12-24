import mixinBase from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/base.js';



/**
 * 合并资源 icon-spin.js
 * !! 不要手动修改 !!
 */




const defineSpfIconSpin = {
    mixins: [mixinBase],
    props: {
        //图标名称，来自加载的图标包，在 cssvar 中指定
        //指定为 -empty- 则显示一个空图标，占据相应尺寸，不显示任何图标
        icon: {
            type: String,
            default: '-empty-'
        },
    },
    data() {return {
            
        /**
         * 覆盖 base-style 样式系统参数
         */
        styInit: {
            class: {
                //根元素
                root: ['spf-icon'],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styCssPre: 'icon',
        styEnable: {
            size: true,
            color: true,
            animate: false,
        },
        stySwitches: {
            
        },
        styCsvKey: {
            size: 'icon',
            color: 'fc',
        },
        
    }},
    computed: {

        /**
         * icon
         */
        iconKey() {
            let ico = this.icon;
            //图标 spin 将调用 spinner 图标库
            if (ico=='-empty-' || ico=='') return 'spinner-spin';
            return 'spinner-' + ico;
        }
    },
    methods: {

    }
}





defineSpfIconSpin.template = `<svg :class="styComputedClassStr.root" :style="styComputedStyleStr.root" aria-hidden="true"><use v-if="iconKey!='-empty-'" v-bind:xlink:href="'#'+iconKey"></use></svg>`;

if (defineSpfIconSpin.computed === undefined) defineSpfIconSpin.computed = {};
defineSpfIconSpin.computed.profile = function() {return {};}










/**
 * 合并资源 icon.js
 * !! 不要手动修改 !!
 */




const defineSpfIcon = {
    mixins: [mixinBase],
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
            
        /**
         * 覆盖 base-style 样式系统参数
         */
        styInit: {
            class: {
                //根元素
                root: ['spf-icon'],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styCssPre: 'icon',
        styEnable: {
            size: true,
            color: true,
            animate: false,
        },
        stySwitches: {
            
        },
        styCsvKey: {
            size: 'icon',
            color: 'fc',
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
            if (is.plainObject(ico)) return ico.full;
            return '-empty-';
        },

        //是否旋转当前图标，而不是使用自旋转图标
        spinSelf() {
            return this.spin === 'self';
        },

        //计算 spin 中心坐标
        spinCenter() {
            let fsz = this.sizePropVal,
                fsarr = this.sizeToArr(fsz),
                fszn = fsarr[0],
                r = fszn/2;
            return ` ${r} ${r}`;
        },
    },
    methods: {

    }
}





defineSpfIcon.template = `<svg :class="styComputedClassStr.root" :style="styComputedStyleStr.root" aria-hidden="true"><use v-if="iconKey!='-empty-'" v-bind:xlink:href="'#'+iconKey"><animateTransform v-if="spinSelf" attributeName="transform" attributeType="XML" type="rotate" :from="'0'+spinCenter" :to="'360'+spinCenter" :dur="spinDura" repeatCount="indefinite" /></use></svg>`;

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
            
        /**
         * 覆盖 base-style 样式系统参数
         */
        styInit: {
            class: {
                //根元素
                root: ['spf-icon-logo'],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styCssPre: 'icon',
        styEnable: {
            size: true,
            color: true,
            animate: false,
        },
        stySwitches: {
            //启用开关
            square: true,
            singleColor: true,
        },
        styCsvKey: {
            size: 'icon',
            color: 'fc',
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
    mixins: [mixinBase],
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

        //title
        title: {
            type: String,
            default: ''
        },


        /**
         * 样式开关
         */
        //是否右侧显示图标
        iconRight: {
            type: Boolean,
            default: false
        },
        //按钮默认包含 hover 样式
        hoverable: {
            type: Boolean,
            default: true
        },
        //active 选中
        active: {
            type: Boolean,
            default: false
        },
        /**
         * shape 形状
         * 可选值： normal(默认) | pill | circle | sharp
         */
        shape: {
            type: String,
            default: 'normal'
        },
        /**
         * effect 填充效果
         * 可选值：  normal(默认) | fill | plain | popout
         */
        effect: {
            type: String,
            default: 'normal'
        },
        /**
         * stretch 按钮延伸类型
         * 可选值： normal(默认) | full-line | square
         */
        stretch: {
            type: String,
            default: 'normal'
        },
        //link 链接形式按钮
        link: {
            type: Boolean,
            default: false
        },
        //no-gap 按钮之间紧密排列，无间隙
        noGap: {
            type: Boolean,
            default: false
        },

        /**
         * 按钮防抖
         */
        //启用防抖 
        debounce: {
            type: Boolean,
            default: true
        },
        //当未指定 fullfilled 指向的标志时，使用此处定义的等待时间
        debounceDura: {
            type: Number,
            default: 500,   //默认等待 500ms
        },
        //可以指定一个组件外部的 fullfilled 标记
        fullfilled: {
            type: Boolean,
            default: false
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
                /**
                 * !! 定义 icon 样式计算方法
                 */
                icon: 'styCalcIconStyle'
            }
        },
        styInit: {
            class: {
                //根元素
                root: ['spf-btn'],
            },
            style: {
                //根元素
                root: {},
                //icon
                icon: {},
            },
        },
        styCssPre: 'btn',
        stySwitches: {
            //启用 下列样式开关
            iconRight: true,
            'hoverable:disabled': true,
            shape: true,
            effect: true,
            stretch: true,
            link: true,
            noGap: true,
            active: true,
            'mouse.down:disabled': true,
        },
        styCsvKey: {
            size: 'btn',
            color: 'fc',
        },

        /**
         * 按钮点击状态
         */
        stage: {
            ready: true,
            enter: false,
            press: false,
            //debounce
            pending: false,
            fullfilled: false,
        },

        //mouse 状态
        mouse: {
            enter: false,
            down: false,
            clicking: false,    //for debounce 点击按钮防抖
        },

        //
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
                //是否 props.stretch === square && props.effect === popout
                pot = this.stretch === 'square' && this.effect === 'popout';
            if (pot) {
                iszn = Math.round(iszn * 1.8);
            } else {
                iszn = Math.round(iszn * 1.6);
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
            if (this.debounce === false || (this.debounce === true && this.stage.pending !== true)) {
                this.stage.press = true;
                
            }
            if (this.mouse.clicking!==true) {
                this.mouse.clicking = true;
                //event.targetComponent = this;
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





defineSpfButton.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root" :title-bak="title" @click="whenBtnClick" @mouseenter="whenMouseEnter" @mouseleave="whenMouseLeave" @mousedown="whenMouseDown" @mouseup="whenMouseUp"><spf-icon v-if="!iconRight && !isEmptyIcon" :icon="icon" :size="styComputedStyle.icon.fontSize" :spin="spin" :shape="iconShape" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon><label v-if="!isEmptyLabel && stretch!=='square' && shape !== 'circle'" :class="labelClass" :style="labelStyle">{{label}}</label><spf-icon v-if="iconRight && !isEmptyIcon" :icon="icon" :size="styComputedStyle.icon.fontSize" :spin="spin" :shape="iconShape" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon></div>`;

if (defineSpfButton.computed === undefined) defineSpfButton.computed = {};
defineSpfButton.computed.profile = function() {return {};}










/**
 * 合并资源 layout.js
 * !! 不要手动修改 !!
 */




const defineSpfLayout = {
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
                root: ['spf-layout','flex-x','flex-y-stretch','bd-m','bdc-m','bd-po-t','bgc-m'],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styCssPre: 'layout',
        styEnable: {
            size: false,
            color: false,
            animate: false,
        },
        stySwitches: {
            //启用 下列样式开关
        },
        styCsvKey: {
            
        },

    }},
    computed: {
        
    },
    methods: {

    }
}





defineSpfLayout.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><div class="layout-leftside flex-y flex-x-stretch bd-m bd-po-r"><div class="layout-logobar flex-x flex-x-center bg-backdrop-blur"><spf-icon-logo :icon="logo" size="large"></spf-icon-logo></div><div class="layout-menubar flex-1 flex-y flex-x-center flex-no-shrink scroll-y scroll-none"><slot name="menubar"></slot></div><div class="layout-usrbar flex-x flex-x-center bg-backdrop-blur bd-m bd-po-t pd-m pd-po-rl"><div class="layout-usr-avator mg-s mg-po-r"></div><div class="flex-1"></div><spf-button icon="menu" effect="popout" stretch="square"></spf-button><spf-button icon="power-settings-new" effect="popout" stretch="square" type="danger"></spf-button></div></div><div class="layout-mainbody flex-1 flex-y flex-x-stretch flex-y-start"><div class="layout-navtab flex-x flex-x-center bg-backdrop-blur"></div><div class="layout-body flex-1 flex-y flex-x-start flex-y-start flex-no-shrink scroll-y scroll-none"><slot></slot></div><div class="layout-taskbar flex-x flex-x-end bd-m bd-po-t bg-backdrop-blur"><div class="taskbar-item"><spf-icon icon="chat" shape="fill" size="small" type="success"></spf-icon><div class="taskitem-label">OMS 订货系统微信群</div><spf-button icon="close" size="tiny" effect="popout" shape="circle" type="danger"></spf-button></div><div class="taskbar-item taskitem-active"><spf-icon icon="payment" size="small" type="primary"></spf-icon><div class="taskitem-label">支付宝付款后台查询</div><spf-button icon="close" size="tiny" effect="popout" shape="circle" type="danger"></spf-button></div></div></div></div>`;

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
 * 合并资源 win.js
 * !! 不要手动修改 !!
 */




const defineSpfWin = {
    mixins: [mixinBase],
    props: {
        /**
         * 此窗口的元素据
         */
        /**
         * 窗口唯一键名
         * 所有通过 $ui.openWin() 方法打开的窗口，必须指定唯一键名
         * 此键名将用于在 $ui.winList 对象中标记此窗口的实例
         * 手动在页面中使用窗口组件，包含自定义内容的 不需要传入此参数
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

        /**
         * 是否启用窗口元素
         */
        //tabbar
        tabbar: {
            type: Boolean,
            default: false
        },
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
        
        /**
         * 指定此窗口的类型
         * 可选：inside(在页面内部) | popout(普通弹出) | modal(模态弹出)
         * 默认 popout
         */
        winType: {
            type: String,
            default: 'popout',
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
                root: ['spf-win'],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styCssPre: 'win',
        styEnable: {
            size: false,
            color: false,
            animate: false,
        },
        stySwitches: {
            //启用 下列样式开关
            winType: 'type',
            border: true,
            sharp: true,
            shadow: true,
            hoverable: true,
            tightness: true,
        },
        styCsvKey: {
            size: 'block',
            color: 'fc',
        },
    }},
    computed: {},
    methods: {

    }
}





defineSpfWin.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><div class="win-titbar"><spf-icon v-if="icon !== '' && icon !== '-empty-'" :icon="icon" :shape="iconShape" :spin="spin" :color="iconColor" v-bind="iconParams"></spf-icon><div class="win-title">新窗口</div><div class="win-titctrl"><slot name="titctrl"></slot></div><template v-if="winType !== 'inside'"><spf-button v-if="minimizable" icon="arrow-downward" effect="popout" stretch="square" no-gap></spf-button><spf-button v-if="maximizable" icon="crop-square" effect="popout" stretch="square" no-gap></spf-button><spf-button v-if="closeable" icon="close" type="danger" effect="popout" stretch="square" no-gap></spf-button></template></div><div v-if="tabbar" class="win-tabbar"><div class="tabbar-start"></div><div class="tabbar-item tabitem-active"><label>标签页 1</label><spf-button icon="close" size="tiny" effect="popout" shape="circle" type="danger"></spf-button></div><div class="tabbar-item"><label>标签页 2</label><spf-button icon="close" size="tiny" effect="popout" shape="circle" type="danger"></spf-button></div><div class="tabbar-item"><label>比较长的标签页 3</label><spf-button icon="close" size="tiny" effect="popout" shape="circle" type="danger"></spf-button></div><div class="tabbar-end"></div></div><div class="win-body"><slot></slot></div><div class="win-ctrlbar"><slot name="winctrl-extra"></slot><div class="flex-1"></div><slot name="winctrl"></slot><spf-button v-if="cancelButton" :icon="cancelIcon" :label="cancelLabel" effect="popout" type="danger"></spf-button><spf-button v-if="confirmButton" :icon="confirmIcon" :label="confirmLabel" effect="fill" type="primary"></spf-button></div></div>`;

if (defineSpfWin.computed === undefined) defineSpfWin.computed = {};
defineSpfWin.computed.profile = function() {return {};}













/**
 * 导出组件定义参数
 * 外部需要使用 Vue.component(key, val) 注册语句来依次注册组件
 * 不要手动修改
 */

export default {
'spf-icon-spin': defineSpfIconSpin,
'spf-icon': defineSpfIcon,
'spf-icon-logo': defineSpfIconLogo,
'spf-button-group': defineSpfButtonGroup,
'spf-button': defineSpfButton,
'spf-layout': defineSpfLayout,
'spf-layout-usrbar': defineSpfLayoutUsrbar,
'spf-layout-navbar': defineSpfLayoutNavbar,
'spf-win': defineSpfWin,
}
