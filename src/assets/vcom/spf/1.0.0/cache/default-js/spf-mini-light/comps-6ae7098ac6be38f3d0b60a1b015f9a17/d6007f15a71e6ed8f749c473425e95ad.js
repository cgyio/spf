import mixinBase from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/base.js';
import mixinBaseDynamic from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/base-dynamic.js';



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
                root: ['spf-icon'],
            },
            style: {
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
                fsarr = this.$ui.sizeToArr(fsz),
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
                root: ['spf-icon-logo'],
            },
            style: {
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
            
        /**
         * 覆盖 base-style 样式系统参数
         */
        styInit: {
            class: {
                //根元素
                root: ['spf-btn', 'flex-x', 'flex-x-center'],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styCssPre: 'btn',
        styEnable: {
            size: true,
            color: true,
            animate: true,
        },
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
            'stage.pending:disabled': 'pending',
            'stage.press:disabled': 'pressed',
            'fullfilledWhen:disabled': 'fullfilled',
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

        //根据 btn-size 计算 内部 icon 的 size 参数
        iconSize() {
            let is = this.$is,
                ui = this.$ui,
                sztp = this.sizePropType,
                squ = this.stretch==='square' || this.effect==='circle',
                size = this.size;;
            if ('str,key'.split(',').includes(sztp)) {
                //按钮内部 icon 尺寸 小一级
                return squ ? size : ui.sizeKeyShiftTo(size, 's1');
            }
            let sz = this.sizePropVal;
            if (!ui.isSizeVal(sz)) return size;
            let fs = ui.sizeCalcBarFs(sz),
                nsz = ui.sizeValToKey(fs, 'icon');
            if (is.string(nsz)) return nsz;
            return fs;
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
    }
}





defineSpfButton.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root" @click="whenBtnClick" @mouseenter="whenMouseEnter" @mouseleave="whenMouseLeave" @mousedown="whenMouseDown" @mouseup="whenMouseUp"><spf-icon v-if="!iconRight && !isEmptyIcon" :icon="icon" :size="iconSize" :spin="iconSpin" :shape="iconShape" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon><label v-if="!isEmptyLabel && stretch!=='square' && shape !== 'circle'" :class="labelClass" :style="labelStyle">{{label}}</label><spf-icon v-if="iconRight && !isEmptyIcon" :icon="icon" :size="iconSize" :spin="iconSpin" :shape="iconShape" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon></div>`;

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
        /*styCalculators: {
            class: {
                //root 是计算根元素的 class 样式类，必须指定
                root: 'styCalcRootClass',
            },
            style: {
                //root 是计算根元素的 css 样式，必须指定
                root: 'styCalcRootStyle',
            }
        },*/
        styInit: {
            class: {
                root: ['spf-layout','flex-x','flex-y-stretch','bd-m','bdc-m','bd-po-t','bgc-m'],
            },
            style: {
                root: {},
            },
        },
        /*styCssPre: 'layout',
        styEnable: {
            size: false,
            color: false,
            animate: false,
        },
        stySwitches: {
            //启用 下列样式开关
        },
        styCsvKey: {
            
        },*/

    }},
    computed: {
        
    },
    methods: {

    }
}





defineSpfLayout.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><div class="layout-leftside flex-y flex-x-stretch bd-m bd-po-r"><div class="layout-logobar flex-x flex-x-center bg-backdrop-blur"><spf-icon-logo :icon="logo" size="large"></spf-icon-logo></div><div class="layout-menubar flex-1 flex-y flex-x-center flex-no-shrink scroll-y scroll-none"><slot name="menubar"></slot></div><div class="layout-usrbar flex-x flex-x-center bg-backdrop-blur bd-m bd-po-t pd-m pd-po-rl"><div class="layout-usr-avator mg-s mg-po-r"></div><div class="flex-1"></div><spf-button icon="menu" effect="popout" stretch="square"></spf-button><spf-button icon="power-settings-new" effect="popout" stretch="square" type="danger"></spf-button></div></div><div class="layout-mainbody flex-1 flex-y flex-x-stretch flex-y-start"><div class="layout-navtab flex-x flex-x-center bg-backdrop-blur"><spf-tabbar v-model="$ui.testTabActive" :tab-list="$ui.testTabList" closable position="top" align="left" enable-scroll custom-style="height: 64px;"></spf-tabbar></div><div class="layout-body flex-1 flex-y flex-x-start flex-y-start flex-no-shrink scroll-y scroll-none"><slot></slot></div><div class="layout-taskbar flex-x flex-x-end bd-m bd-po-t bg-backdrop-blur"><div class="taskbar-item"><spf-icon icon="chat" shape="fill" size="small" type="success"></spf-icon><div class="taskitem-label">OMS 订货系统微信群</div><spf-button icon="close" size="tiny" effect="popout" shape="circle" type="danger"></spf-button></div><div class="taskbar-item taskitem-active"><spf-icon icon="payment" size="small" type="primary"></spf-icon><div class="taskitem-label">支付宝付款后台查询</div><spf-button icon="close" size="tiny" effect="popout" shape="circle" type="danger"></spf-button></div></div></div></div>`;

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
 * 合并资源 layout-mask.js
 * !! 不要手动修改 !!
 */




const defineSpfLayoutMask = {
    mixins: [mixinBase, mixinBaseDynamic],
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
        /**
         * 覆盖 base-style 样式系统参数
         */
        styInit: {
            class: {
                //根元素
                root: ['spf-mask', 'flex-x', 'flex-y-center', 'flex-x-center'],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styCssPre: 'mask',
        styEnable: {
            size: false,
            color: true,
            animate: true,
        },
        stySwitches: {
            //启用 下列样式开关
            blur: true,
            alpha: true,
            'dcDisplay.show': 'show',
        },
        styCsvKey: {
            size: '',
            color: 'bgc',
        },

        //作为动态组件
        multiple: false,
    }},
    computed: {},
    methods: {
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





defineSpfLayoutMask.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root" @click="whenMaskClick"><spf-icon v-if="loading" icon="spinner-spin" size="huge" spin></spf-icon></div>`;

if (defineSpfLayoutMask.computed === undefined) defineSpfLayoutMask.computed = {};
defineSpfLayoutMask.computed.profile = function() {return {};}










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
        /**
         * 覆盖 base-style 样式系统参数
         */
        styInit: {
            class: {
                root: ['tabbar-item', 'flex-x', 'flex-x-center'],
            },
            style: {
                root: {},
            },
        },
        styCssPre: 'tabbar-item',
        stySwitches: {
            //启用 下列样式开关
            closable: true,
            active: true,
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
            
        /**
         * 覆盖 base-style 样式系统参数
         */
        styInit: {
            class: {
                root: ['spf-tabbar', 'flex-1', 'flex-x', 'flex-y-stretch'],
            },
            style: {
                root: {},
            },
        },
        styCssPre: 'tabbar',
        styEnable: {
            size: true,
            color: false,
            animate: false,
        },
        stySwitches: {
            //启用 下列样式开关
            position: true,
            border: true,
            closable: true,
        },
        styCsvKey: {
            size: 'btn'
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
        /**
         * 覆盖 base-style 样式系统参数
         */
        styCalculators: {
            foo: 123
        },
        styInit: {
            class: {
                //根元素
                root: ['spf-win', 'flex-y', 'flex-x-stretch'],
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
            animate: true,
        },
        stySwitches: {
            //启用 下列样式开关
            winType: 'type',
            border: true,
            sharp: true,
            shadow: true,
            hoverable: true,
            tightness: true,
            'dcDisplay.show': 'show',
            'dcDisplay.minimized': 'minimized',
            'dcDisplay.maximized': 'maximized',

        },
        styCsvKey: {
            size: 'block',
            color: 'fc',
        },

        //窗口的 display 显示状态 覆盖 base-dynamic.mixin 中参数
        dcDisplay: {
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
        },

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
            this.dcDisplay.loading = this.loading;
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
    methods: {
        /**-
         * win-minimize|maximize|close
         */
        winMinimize(event) {
            if (this.canMinimize || this.dcDisplay.minimized || this.winKey==='') return false;
            this.dcDisplay.minimized = true;
            return this.$ui.minimizeWin(this.winKey).then(()=>{
                return this.$emit('minimize', this.winKey);
            });
        },
        winMaximize(event) {
            if (this.canMaximize || this.winKey==='') return false;
            this.dcDisplay.maximized = !this.dcDisplay.maximized;
            this.dcDisplay.minimized = false;
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
        winCancel(event) {

        },
        winConfirm(event) {

        },

        //win-loading 状态切换
        winLoading(loading=null) {
            let is = this.$is;
            if (!is.boolean(loading)) {
                //不指定 loading 状态，则 toggle
                this.dcDisplay.loading = !this.dcDisplay.loading;
            } else {
                //直接指定 loading 状态
                this.dcDisplay.loading = loading;
            }
            //事件
            return this.$emit('loading', this.dcDisplay.loading);
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





defineSpfWin.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><div class="win-titbar flex-x" v-drag-move:xy="$this"><spf-icon v-if="icon !== '' && icon !== '-empty-'" :icon="icon" :shape="iconShape" :spin="loading ? true : spin" :color="iconColor" v-bind="iconParams"></spf-icon><div class="win-title flex-1 flex-x">新窗口</div><div class="win-titctrl flex-x flex-x-end"><slot name="titctrl"></slot></div><template v-if="winType !== 'inside'"><spf-button v-if="minimizable" icon="arrow-downward" effect="popout" stretch="square" no-gap @click="winMinimize"></spf-button><spf-button v-if="maximizable" :icon="dcDisplay.maximized ? 'fullscreen-exit' : 'fullscreen'" effect="popout" stretch="square" no-gap @click="winMaximize"></spf-button><spf-button v-if="closeable" icon="close" type="danger" effect="popout" stretch="square" no-gap @click="winClose"></spf-button></template></div><div v-if="tabList.length>1 && tabItemList.length>1" class="win-tabbar flex-x flex-x-center"><spf-tabbar :value="tab.active" :tab-list="tabItemList" :size="tabbarSize" v-bind="tabbarParams" @tab-active="whenTabActive"></spf-tabbar></div><div class="win-body flex-1 flex-y flex-x-stretch"><template v-if="$is.plainObject(tabActiveItem) && !$is.empty(tabActiveItem)"><slot v-if="tabActiveCompName === ''" :name="'tab-'+tabActiveItem.key" :tab="tabActiveItem" :win="$this"></slot><component v-else :is="tabActiveCompName" v-bind="tabActiveItem.compProps"></component></template><slot></slot></div><div class="win-ctrlbar flex-x flex-x-end"><slot name="winctrl-extra"></slot><div class="flex-1"></div><slot name="winctrl"></slot><spf-button v-if="cancelButton" :icon="cancelIcon" :label="cancelLabel" effect="popout" type="danger" @click="winCancel"></spf-button><spf-button v-if="confirmButton" :icon="confirmIcon" :label="confirmLabel" effect="fill" type="primary" @click="winConfirm"></spf-button></div><div v-if="dcDisplay.loading" class="win-loading flex-x flex-x-center flex-y-center"><spf-icon icon="spinner-spin" size="huge" type="primary" spin></spf-icon></div></div>`;

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
'spf-layout-mask': defineSpfLayoutMask,
'spf-tabbar-item': defineSpfTabbarItem,
'spf-tabbar': defineSpfTabbar,
'spf-win-content': defineSpfWinContent,
'spf-win': defineSpfWin,
}
