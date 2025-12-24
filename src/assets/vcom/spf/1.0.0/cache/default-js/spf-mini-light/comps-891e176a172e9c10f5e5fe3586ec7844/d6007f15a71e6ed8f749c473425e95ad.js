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

        //spin
        spin: {
            type: Boolean,
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
            return this.icon;
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





defineSpfIcon.template = `<svg :class="styComputedClassStr.root" :style="styComputedStyleStr.root" aria-hidden="true"><use v-if="iconKey!='-empty-'" v-bind:xlink:href="'#'+iconKey"><animateTransform v-if="spin" attributeName="transform" attributeType="XML" type="rotate" :from="'0'+spinCenter" :to="'360'+spinCenter" :dur="spinDura" repeatCount="indefinite" /></use></svg>`;

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

        //按钮文字
        label: {
            type: String,
            default: ''
        },

        //title
        title: {
            type: String,
            default: ''
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

        /**
         * spin 
         * false                表示使用 spf-icon 组件并关闭 spin
         * true                 表示使用 spf-icon-spin 组件并使用 spinner-spin 图标
         * 'self'               表示使用 spf-icon 组件并开启 spin
         * wait|wifi...         表示使用 spf-icon-spin 组件并应用 icon = spinner-wait|wifi...
         */
        spin: {
            type: [Boolean, String],
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
                iszn = Math.round(iszn * 1.6);
            } else {
                iszn = Math.round(iszn * 1.4);
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





defineSpfButton.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root" :title-bak="title" @click="whenBtnClick" @mouseenter="whenMouseEnter" @mouseleave="whenMouseLeave" @mousedown="whenMouseDown" @mouseup="whenMouseUp"><template v-if="!iconRight"><template v-if="spin === false || spin === 'self'"><spf-icon v-if="!isEmptyIcon" :icon="icon" :size="styComputedStyle.icon.fontSize" :spin="spin === 'self'" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon></template><spf-icon-spin v-else :icon="(spin === true || spin === '') ? '-empty-' : spin" :size="styComputedStyle.icon.fontSize" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon-spin></template><label v-if="!isEmptyLabel && stretch!=='square' && shape !== 'circle'" :class="labelClass" :style="labelStyle">{{label}}</label><template v-if="iconRight"><template v-if="spin === false || spin === 'self'"><spf-icon v-if="!isEmptyIcon" :icon="icon" :size="styComputedStyle.icon.fontSize" :spin="spin === 'self'" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon></template><spf-icon-spin v-else :icon="(spin === true || spin === '') ? '-empty-' : spin" :size="styComputedStyle.icon.fontSize" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon-spin></template></div>`;

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
                root: ['spf-layout',],
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





defineSpfLayout.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><div class="layout-navbar"><spf-layout-navbar :logo="logo"></spf-layout-navbar></div><div class="layout-mainbody"><div class="layout-menubar"></div><div class="layout-content"><slot></slot></div></div><div class="layout-taskbar"></div></div>`;

if (defineSpfLayout.computed === undefined) defineSpfLayout.computed = {};
defineSpfLayout.computed.profile = function() {return {};}










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





defineSpfLayoutNavbar.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><spf-icon-logo :icon="logo" size="huge"></spf-icon-logo></div>`;

if (defineSpfLayoutNavbar.computed === undefined) defineSpfLayoutNavbar.computed = {};
defineSpfLayoutNavbar.computed.profile = function() {return {};}













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
'spf-layout-navbar': defineSpfLayoutNavbar,
}
