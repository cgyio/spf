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

        //使用外部 icon 图标，不通过自带 iconPackage 图标包
        //图标来源 https://io.cgy.design/icon/***
        /*useExtraIcon: {
            type: Boolean,
            default: false
        },
        extraIconApi: {
            type: String,
            default: 'https://io.cgy.design/icon/'
        },*/
    },
    data() {return {
            
        /**
         * 覆盖 base-style 样式系统参数
         */
        styTemp: {
            class: {
                //根元素
                root: ['spf-icon-wrapper'],
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
        styEnableSwitches: {
            
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
            //if (this.spin) return 'spiner-180-ring';
            return this.icon;
        },

        /**
         * spin
         */
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





defineSpfIcon.template = `<svg :class="styComputedClassStr.root" :style="styComputedStyleStr.root" aria-hidden="true"><use v-if="icon!='-empty-'" v-bind:xlink:href="'#'+iconKey"><animateTransform v-if="spin" attributeName="transform" attributeType="XML" type="rotate" :from="'0'+spinCenter" :to="'360'+spinCenter" dur="1.6s" repeatCount="indefinite" /></use></svg>`;

if (defineSpfIcon.computed === undefined) defineSpfIcon.computed = {};
defineSpfIcon.computed.profile = function() {return {};}













/**
 * 导出组件定义参数
 * 外部需要使用 Vue.component(key, val) 注册语句来依次注册组件
 * 不要手动修改
 */

export default {
'spf-icon': defineSpfIcon,
}
