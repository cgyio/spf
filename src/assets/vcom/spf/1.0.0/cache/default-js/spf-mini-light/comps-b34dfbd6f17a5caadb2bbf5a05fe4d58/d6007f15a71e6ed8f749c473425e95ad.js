import mixinBase from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/base.js';



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
            let ico = this.icon;
            if (this.spin === true) {
                //图标 spin 将调用 spinner 图标库
                if (ico=='-empty-') return 'spinner-spin';
                return 'spinner-' + ico;
            }
            if (this.icon=='-empty-') return '-empty-';
            //if (this.spin) return 'spiner-180-ring';
            return this.icon;
        },

        //计算 spin 中心坐标
        /*spinCenter() {
            let fsz = this.sizePropVal,
                fsarr = this.sizeToArr(fsz),
                fszn = fsarr[0],
                r = fszn/2;
            return ` ${r} ${r}`;
        },*/
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
 * 导出组件定义参数
 * 外部需要使用 Vue.component(key, val) 注册语句来依次注册组件
 * 不要手动修改
 */

export default {
'spf-icon': defineSpfIcon,
'spf-icon-logo': defineSpfIconLogo,
}
