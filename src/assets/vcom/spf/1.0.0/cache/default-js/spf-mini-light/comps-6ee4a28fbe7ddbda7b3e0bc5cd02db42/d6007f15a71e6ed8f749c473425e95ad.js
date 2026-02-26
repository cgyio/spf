import mixinBase from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/base.js';
import mixinBaseDynamic from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/base-dynamic.js';



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
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: ['spf-icon'],
                }
            },
            prefix: 'icon',
            sub: {
                size: true,
                color: true,
            },
            switch: {
                //stretch: false,
                //shape: false,
                //tightness: false,
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
                fsarr = this.$ui.sizeValToArr(fsz),
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
 * 合并资源 bar.js
 * !! 不要手动修改 !!
 */




const defineSpfBar = {
    mixins: [mixinBase],
    props: {
        /**
         * stretch 按钮延伸类型
         * 可选值： auto | square | grow | row(默认)
         * !! 覆盖 base-style 中定义的默认值
         */
        stretch: {
            type: String,
            default: 'row'
        },
        /**
         * effect 填充效果
         * 可选值：  normal | fill | plain | popout(默认)
         * !! 覆盖 base-style 中定义的默认值
         */
        effect: {
            type: String,
            default: 'popout'
        },

        //图标名称，来自加载的图标包，在 cssvar 中指定
        icon: {
            type: String,
            default: ''
        },
        //icon 额外的参数
        iconParams: {
            type: Object,
            default: () => {
                return {};
            }
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
        //行末按钮的额外参数
        btnParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        //行末按钮 额外的样式
        btnClass: {
            type: String,
            default: ''
        },
        btnStyle: {
            type: [String, Object],
            default: ''
        },

        //是否内置 content 整行容器
        innerContent: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            //!! 需要计算样式的元素 至少需要定义 calculator 和 init 参数中的一个，这样 styElmList 才能收集到此元素
            init: {
                class: {
                    root: 'spf-bar bar',
                    icon: '',
                    btn: '',
                    cnt: 'bar-cnt flex-x flex-no-shrink',
                },
                style: {
                    icon: '',
                    btn: '',
                    cnt: 'height: 100%;',
                }
            },
            prefix: 'bar',
            group: {
                //新增组开关
                innerContent: true,
            },
            sub: {
                size: true,
                color: true,
            },
            switch: {
                //启用 下列样式开关
                effect:     '.bar-effect effect-{swv}',
                stretch:    '.stretch-{swv}',
                tightness:  '.bar-tightness tightness-{swv}',
                shape:      '.bar-shape shape-{swv}',
                'hoverable:!disabled': '.hoverable',
                active:     '.active',
                //innerContent: 'padding: 0px;',

                //只针对 suffixBtn 元素
                'size@btn': '.btn-{swv@call,stySizeShift,s1,btn}',
                'iconRight@btn': {
                    marginLeft: '{swv@if,-0.8em,1.5em}', 
                    marginRight: '{swv@if,1.5em,-0.8em}',
                },

                //只针对 cnt 元素
                'flexY@cnt': '.flex-y-{swv}',
            },
            csvKey: {
                size: 'bar',
                color: 'bgc',
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





defineSpfBar.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root" @click="whenBarClick"><template v-if="innerContent"><spf-bar v-if="!iconRight && !isEmptyIcon" stretch="auto" :size="styProps.size" :icon="icon" :icon-params="iconParams" :icon-class="iconClass" :icon-style="iconStyle"></spf-bar><spf-bar v-if="iconRight && !isEmptyBtn" stretch="auto" :size="styProps.size" :suffix-btn="suffixBtn" :btn-params="btnParams" :btn-class="btnClass" :btn-style="btnStyle" @suffix-btn-click="$emit('suffix-btn-click')"></spf-bar><div :class="styComputedClassStr.cnt" :style="styComputedStyleStr.cnt"><slot v-bind="{styProps}"></slot></div><spf-bar v-if="!iconRight && !isEmptyBtn" stretch="auto" :size="styProps.size" :suffix-btn="suffixBtn" :btn-params="btnParams" :btn-class="btnClass" :btn-style="btnStyle" @suffix-btn-click="$emit('suffix-btn-click')"></spf-bar><spf-bar v-if="iconRight && !isEmptyIcon" stretch="auto" :size="styProps.size" :icon="icon" :icon-params="iconParams" :icon-class="iconClass" :icon-style="iconStyle"></spf-bar></template><template v-else><spf-icon v-if="!iconRight && !isEmptyIcon" :icon="icon" v-bind="iconParams" :custom-class="styComputedClassStr.icon" :custom-style="styComputedStyleStr.icon"></spf-icon><spf-button v-if="iconRight && !isEmptyBtn" effect="popout" v-bind="btnParams" size="" :icon="suffixBtn" :custom-class="styComputedClassStr.btn" :custom-style="styComputedStyleStr.btn" @click="$emit('suffix-btn-click')"></spf-button><slot v-bind="{styProps}"></slot><spf-button v-if="!iconRight && !isEmptyBtn" effect="popout" v-bind="btnParams" size="" :icon="suffixBtn" :custom-class="styComputedClassStr.btn" :custom-style="styComputedStyleStr.btn" @click="$emit('suffix-btn-click')"></spf-button><spf-icon v-if="iconRight && !isEmptyIcon" :icon="icon" v-bind="iconParams" :custom-class="styComputedClassStr.icon" :custom-style="styComputedStyleStr.icon"></spf-icon></template></div>`;

if (defineSpfBar.computed === undefined) defineSpfBar.computed = {};
defineSpfBar.computed.profile = function() {return {};}










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
        footerSize: {
            type: [String, Number],
            default: ''
        },

        /**
         * 是否在 block 组件内容外部自动包裹对应的组件 bar|block
         */
        innerContent: {
            type: Boolean,
            default: false
        },
        //cnt 元素（通常是 block 组件）额外的参数
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
                    cnt: '',
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
            },
            csvKey: {
                size: 'bar',
                color: 'bgc',
            },
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





defineSpfBlock.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><template v-if="innerContent"><spf-bar v-if="withHeader" :size="styProps.size" :hoverable="headerClickable" v-bind="headerParams" :custom-class="styComputedClassStr.header" :custom-style="styComputedStyleStr.header" @click="whenHeaderClick" v-slot="{styProps: headerStyProps}"><slot name="header" v-bind="{styProps: headerStyProps}"></slot></spf-bar><template v-if="withHeader || withFooter"><transition name="spf-block-inner-trans" :enter-active-class="toggleCntAnimate[0]" :leave-active-class="toggleCntAnimate[1]"><spf-block v-if="!hideCnt" :grow="rootGrow" :scroll="innerBlockScroll" :size="styProps.size" v-bind="cntParams" :custom-class="styComputedClassStr.cnt" :custom-style="styComputedStyleStr.cnt" v-slot="{styProps: cntStyProps}"><slot v-bind="{styProps: cntStyProps}"></slot></spf-block></transition></template><template v-else><slot v-if="!hideCnt" v-bind="{styProps, cntParams}"></slot></template><spf-bar v-if="withFooter" :size="footerSize==='' ? styProps.size : footerSize" v-bind="footerParams" :custom-class="styComputedClassStr.footer" :custom-style="styComputedStyleStr.footer" v-slot="{styProps: footerStyProps}"><slot name="footer" v-bind="{styProps: footerStyProps}"></slot></spf-bar></template><template v-else><template v-if="withHeader"><slot name="header" v-bind="{ styProps, headerParams, headerClass: styComputedClass.header, headerStyle: styComputedStyle.header }"></slot></template><slot v-if="!hideCnt" v-bind="{styProps, cntParams}"></slot><template v-if="withFooter"><slot name="footer" v-bind="{ styProps, footerParams, footerClass: styComputedClass.footer, footerStyle: styComputedStyle.footer }"></slot></template></template></div>`;

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
 * 合并资源 block-horizontal.js
 * !! 不要手动修改 !!
 */




const defineSpfBlockHorizontal = {
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
         * 可额外定义 子元素的 class[]|style{} 
         * 数组格式，长度与 colLength 一致，并对应
         *      class[]     支持 []|string
         *      style{}     支持 {}|string
         */
        colClass: {
            type: Array,
            default: () => []
        },
        colStyle: {
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
    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: 'spf-block-hor flex-x flex-x-start flex-y-stretch flex-no-shrink flex-1 stretch-row',
                    //指定 子元素的初始 class[]
                    col: 'spf-block-hor-col flex-y flex-no-shrink',
                },
                style: {
                    root: '',
                    //指定 子元素的初始 style{}
                    col: '',
                },
            },
            //prefix: '',
            sub: {
                //size: true,
                //color: true,
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
            //定义的 class[]|style{} 合并了传入的 colClass[i]|colStyle[i] 后的 class[]|style{}
            init: {
                class: [],  //!! 一定是 []
                style: {},  //!! 一定是 {}
            },

            //在 props.col 中定义的 col 初始宽度设定 可能是 128px|35%|2|*
            set: '',

            //compact 自动缩放参数
            compact: false,
            compactTo: '',

            //resize 宽度调整参数
            resize: false,
            resizeMin: '',
            resizeMax: '',
        },

        //col 当前的 compact 状态，[true, false, ...] 形式
        compactedCols: [], 
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
        //合并 sty.init.class|style.col 与 props.colClass|Style 作为 class[]|style{} 计算的初始值
        colInitClassArr() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                tca = c => this.$cgy.toClassArr,
                //col 子元素长度
                clen = this.colLength,
                //在 sty.init.class.col 中定义的 所有子元素共用的 初始 class[]
                init = this.styInitClass.col || [],
                //在 props.colClass[] 中定义的所有子元素的 各自对应的 用户初始 class
                cstc = this.colClass,
                //合并后的 class[]
                icls = [];
            for (let i=0;i<clen;i++) {
                let icli = [...init];
                if (is.defined(cstc[i])) {
                    if (iss(cstc[i])) {
                        icli.push(...tca(cstc[i]));
                    } else if (isa(cstc[i])) {
                        icli.push(...cstc[i]);
                    }
                }
                //去重后，合并到 colInitClassArr
                icls.push(icli.unique());
            }
            return icls;
        },
        colInitStyleObj() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                isc = c => this.$cgy.isCssObj(c),
                tco = this.$cgy.toCssObj,
                meg = this.$cgy.mergeCss,
                //col 子元素长度
                clen = this.colLength,
                //在 sty.init.style.col 中定义的 所有子元素共用的 初始 style{}
                init = this.styInitStyle.col || {},
                //在 props.colStyle{} 中定义的所有子元素的 各自对应的 用户初始 style
                csty = this.colStyle,
                //合并后的 style{}
                isty = [];
            for (let i=0;i<clen;i++) {
                let istyi = meg({}, init);
                if (is.defined(csty[i])) {
                    if (iss(csty[i]) || isc(csty[i])) {
                        istyi = meg(istyi, csty[i]);
                    }
                }
                //合并到 colInitStyleObj
                isty.push(istyi);
            }
            return isty;
        },
        //compact 参数处理
        colCompactOpt() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isn = n => is.realNumber(n) && n*1>=0,
                isu = n => is.numeric(n),
                cmpt = this.compact,
                cmpto = this.compactTo,
                cols = this.colArr,
                cpa = [];
            if (cols.length<=0 || !(isa(cmpt) || isn(cmpt))) return [];
            //将传入的 compact|compactTo 参数转为 []
            if (isn(cmpt)) cmpt = [cmpt];
            if (isu(cmpto)) cmpto = [cmpto];
            if (!isa(cmpt) || !isa(cmpto) || cmpt.length !== cmpto.length) return [];
            //将 compact 参数格式化为 defaultCol 中的形式
            this.$each(cols, (coli, i) => {
                if (!cmpt.includes(i)) {
                    cpa.push({
                        compact: false,
                        compactTo: ''
                    });
                    return true;
                }
                cpa.push({
                    compact: true,
                    compactTo: cmpto[cmpt.indexOf(i)]
                });
            });
            return cpa;
        },
        //resize 参数处理
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
         * 生成标准化的 col 参数，与 defaultCol 数据结构一致  返回 []
         */
        colOpt() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isn = n => is.realNumber(n) && n*1>=0,
                iso = o => is.plainObject(o) && !is.empty(o),
                cols = this.colArr,
                clsa = this.colInitClassArr,
                stya = this.colInitStyleObj,
                cmpt = this.colCompactOpt,
                rsz = this.colResizeOpt,
                opts = [];
            if (cols.length<=0) return [];
            this.$each(cols, (coli, i) => {
                //基础 opt
                let opti = {
                        idx: i,
                        init: {
                            class: clsa[i] || [],
                            style: stya[i] || {},
                        },
                        set: coli,
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
                isn = is.realNumber,
                isu = is.numeric,
                //base-style 样式计算系统得到的样式参数
                styProps = this.styProps,
                clen = this.colLength,
                col = this.colOpt,
                cls = [];
            this.$each(col, (coli,i) => {
                let seti = coli.set,
                    init = coli.init.class || [],
                    clsi = [...init];
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
                    //指定了不合法的宽度值，不操作

                }

                //如果此子元素列定义了 compact 需要额外增加 with-compact-btn 类
                if (is.defined(coli.compact) && coli.compact===true) {
                    clsi.push('with-compact-btn');
                }

                //根据 styProps 中可能存在的 边框参数，计算子元素的边框参数
                if (styProps.hasBd === true && i<clen-1) {
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
                isn = is.realNumber,
                isu = is.numeric,
                col = this.colOpt,
                //当前 col 的 compact 状态
                ccols = this.compactedCols || [],
                sty = [];
            this.$each(col, (coli,i) => {
                let seti = coli.set,
                    init = coli.init.style || {},
                    styi = this.$cgy.mergeCss({}, init);
                /**
                 * 只有指定了 100px|50% 形式的确定的宽度值，才需要指定 style
                 */
                if (isu(seti)) {
                    if (coli.compact === true && is.defined(ccols[i]) && ccols[i]===true) {
                        //需要检查当前的 compact 状态
                        //如果 当前 col 为 compact 状态，则将 width 设为 compactTo
                        styi.width = coli.compactTo;
                    } else {
                        //设为初始值
                        styi.width = seti;
                    }
                }
                
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
                //写入默认 compact-btn class
                let clsi = ['spf-block-hor-col-compact-btn'];
                //compact-btn 位置
                if (i>=clen-1) {
                    //最后一列 compact-btn 位于 左侧
                    clsi.push('compact-btn-left');
                } else {
                    //其他列 compact-btn 位于 右侧
                    clsi.push('compact-btn-right');
                }
                //是否带边框
                if (styProps.hasBd===true) {
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
                //写入默认 compact-btn style
                let styi = {
                    //默认样式在 pre-block-hor-col-compact-btn 类中定义
                    //此处不再定义
                    //top: '50%',
                    //marginTop: '-50%',
                    //zIndex: 10,
                };
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
            this.$emit(`col-compact-${ccoli?'off':'on'}`, idx);
        },
    }
}





defineSpfBlockHorizontal.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root"><template v-if="colLength>0"><template v-for="(si, idx) in colLength"><div v-if="colOpt[idx] && colOpt[idx].resize && colOpt[idx].resize===true" :key="'block-hor-col-'+idx" :class="colClassStr[idx]" :style="colStyleStr[idx]" v-drag-resize:x=""><slot :name="'col-'+si" :sty-props="styProps"></slot></div><div v-else :key="'block-hor-col-'+idx" :class="colClassStr[idx]" :style="colStyleStr[idx]"><template v-if="colOpt[idx] && colOpt[idx].compact && colOpt[idx].compact===true"><slot v-if="compactedCols[idx] && compactedCols[idx]===true" :name="'col-'+si+'-compacted'" :sty-props="styProps"></slot><slot v-else :name="'col-'+si" :sty-props="styProps"></slot><div :class="compactBtnClassStr[idx]" :style="compactBtnStyleStr[idx]" :title="(compactedCols[idx] && compactedCols[idx]===true) ? '展开':'折叠'" @click="toggleCompact(idx)"></div></template><slot v-else :name="'col-'+si" :sty-props="styProps"></slot></div></template></template></div>`;

if (defineSpfBlockHorizontal.computed === undefined) defineSpfBlockHorizontal.computed = {};
defineSpfBlockHorizontal.computed.profile = function() {return {};}










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
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: 'spf-btn btn flex-x flex-x-center',
                }
            },
            prefix: 'btn',
            sub: {
                size: true,
                color: true,
                animate: 'disabled:false',
            },
            switch: {
                //启用 下列样式开关
                effect:     '.{swv@pre}-effect effect-{swv}',
                stretch:    '.{swv@pre}-stretch stretch-{swv}',
                shape:      '.{swv@pre}-shape shape-{swv}',
                //按钮应关闭 tightness
                tightness:  false,

                //iconRight: true,
                //link: true,
                noGap: true,
                incell: true,
                astitle: true,

                //按钮状态
                'hoverable:!disabled': '.hoverable',
                active: '.active',
                'stage.pending:!disabled': '.pending',
                'stage.press:!disabled': '.pressed',
                'fullfilledWhen:!disabled': '.fullfilled',

                //switch 定义字符串模板测试
                //sizeTest: '.btn-test btn-test-{swv}',
            },
            csvKey: {
                size: 'btn',
                color: 'fc',
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

        //根据 btn-size 计算 内部 icon 的 size 参数
        iconSize() {
            if (this.isSquare) return this.size;
            return this.stySizeShift('s1', 'icon');
        },
        //根据 btn-size 计算 内部 close 按钮的 size 参数
        btnSize() {
            return this.stySizeShift('s2', 'btn');
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





defineSpfButton.template = `<div :class="styComputedClassStr.root" :style="styComputedStyleStr.root" @click="whenBtnClick" @mouseenter="whenMouseEnter" @mouseleave="whenMouseLeave" @mousedown="whenMouseDown" @mouseup="whenMouseUp"><spf-icon v-if="!iconRight && !isEmptyIcon" :icon="icon" :size="iconSize" :spin="iconSpin" :shape="iconShape" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon><spf-button v-if="iconRight && closeable" icon="close" :size="btnSize" type="danger" effect="popout" shape="circle" custom-style="margin-left:-0.8em; margin-right:1.5em;" @click="whenClose"></spf-button><label v-if="!isEmptyLabel && !isSquare" :class="labelClass" :style="labelStyle">{{label}}</label><spf-button v-if="!iconRight && closeable" icon="close" :size="btnSize" type="danger" effect="popout" shape="circle" custom-style="margin-right:-0.8em; margin-left:1.5em;" @click="whenClose"></spf-button><spf-icon v-if="iconRight && !isEmptyIcon" :icon="icon" :size="iconSize" :spin="iconSpin" :shape="iconShape" :custom-class="iconClass" :custom-style="iconStyle"></spf-icon></div>`;

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
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: 'spf-mask flex-x flex-y-center flex-x-center',
                }
            },
            prefix: 'mask',
            sub: {
                size: false,
                color: true,
                animate: 'disabled:false',
            },
            switch: {
                //启用 下列样式开关
                blur: true,
                alpha: true,
            },
            csvKey: {
                size: '',
                color: 'bgc',
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
'spf-bar': defineSpfBar,
'spf-block-collapse-item': defineSpfBlockCollapseItem,
'spf-block': defineSpfBlock,
'spf-block-collapse': defineSpfBlockCollapse,
'spf-block-horizontal': defineSpfBlockHorizontal,
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
