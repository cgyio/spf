<template>
    <svg 
        :class="computedClass" 
        :style="computedStyle"
        aria-hidden="true"
    >
        <use v-if="icon!='-empty-'" v-bind:xlink:href="'#'+iconKey">
            <!--<animateTransform
                v-if="spin"
                attributeName="transform"
                attributeType="XML"
                type="rotate"
                :from="'0'+spinCenter"
                :to="'360'+spinCenter"
                dur="1.6s"
                repeatCount="indefinite" />-->
        </use>
    </svg>
</template>

<script>
import mixinBase from '../../mixin/base';

export default {
    mixins: [mixinBase],
    props: {
        //图标名称，来自加载的图标包，在 cssvar 中指定
        //指定为 -empty- 则显示一个空图标，占据相应尺寸，不显示任何图标
        icon: {
            type: String,
            default: '-empty-'
        },

        /**
         * 样式前缀 cssPre
         */
        cssPre: {
            type: String,
            default: 'icon-'
        },

        //尺寸
        //size: {
        //    type: [String, Number],
        //    default: 'default'
        //},

        //颜色
        //type: {
        //    type: String,
        //    default: ''
        //},
        //强制指定颜色，unset 则不指定颜色，用于显示彩色 icon
        //color: {
        //    type: String,
        //    default: ''
        //},

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
    data() {
        return {
            
            /**
             * 所有组件公用的 size 可选属性值
             * !! 特殊需要的组件 应在组件内部 覆盖
             */
            /*sizes: {
                huge:       'xxxl',
                large:      'xxl',
                big:        'xl',
                medium:     'm',
                small:      's',
                mini:       'xs'
            }*/
        }
    },
    computed: {

        /**
         * customClass/Style 配套的 计算属性
         * !! 引用的组件内部 应根据需要覆盖
         */
        //计算后的 class override
        computedClass() {
            let is = this.$is,
                sz = this.sizeClass,
                tp = this.typeClass,
                clss = ['cv-icon'];
            if (!is.empty(sz)) clss.push(...sz);
            if (!is.empty(tp)) clss.push(...tp);
            return this.mixinCustomClass(...clss);
        },
        //计算后得到的当前样式 override
        computedStyle() {
            let is = this.$is,
                isd = is.defined,
                isn = is.realNumber,
                icon = this.icon,
                csz = this.cssvar.size.icon,
                sz = this.sizeKey,
                clr = this.colorHex,
                sty = {};
            if (clr!='') sty.color = clr;
            if (isn(sz)) {
                sz += 'px';
            } else if (isd(csz[sz])) {
                sz = csz[sz];
            } else if (is.string(sz) && sz.endsWith('px')){
                sz = sz;
            }else {
                sz = csz.$;
            }
            if (icon=='-empty-') {
                sty.width = sz;
                sty.height = sz;
            } else {
                sty.fontSize = sz;
            }
            return this.mixinCustomStyle(sty);
        },

        /**
         * icon
         */
        iconKey() {
            if (this.icon=='-empty-') return '-empty-';
            if (this.spin) return 'spiner-180-ring';
            return this.icon;
        },

        /**
         * spin
         */
        //计算 spin 中心坐标
        spinCenter() {
            let sty = this.computedStyle,
                sobj = this.$cgy.toCssObj(sty),
                fsz = sobj.fontSize || this.cssvar.size.icon.$,
                r = parseInt(fsz.replace('px',''))/2;
            return ` ${r} ${r}`;
        },
    },
    methods: {

    }
}
</script>

<style>

</style>