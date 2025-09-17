<template>
    <div :class="barClass">
        <template v-if="customBar">
            <slot name="custom-bar"></slot>
        </template>
        <template v-else>
            <img 
                v-if="showLogo && logo!=''"
                class="bar-logo" 
                :style="logoHref!='' ? 'cursor:pointer;' : ''"
                :src="logoSrc"
                @click="clickLogo"
            >
            <span 
                v-if="barTitle!=''"
                class="f-xl f-w900 f-black"
            >{{ barTitle }}</span>
            <slot name="left-bar"></slot>
            <span class="cv-flex flex-1"></span>
            <slot name="right-bar"></slot>
        </template>
    </div>
</template>

<script>
import mixinBase from '/vue/@/mixins/base/base';

export default {
    mixins: [mixinBase],
    props: {
        //bar 高度
        //可选：l,xl,xxl, 18-64px
        size: {
            type: [Number, String],
            default: ''
        },

        //是否 fixed
        fixed: {
            type: Boolean,
            default: true
        },

        //位置
        position: {
            type: String,
            default: 'top'
        },

        //是否显示 gap-inner
        showGapInner: {
            type: Boolean,
            default: true
        },
        //gap-inner, xs,s,l,xl
        gapInner: {
            type: String,
            default: ''
        },

        //是否显示 bg
        showBg: {
            type: Boolean,
            default: true
        },
        //是否使用 backdrop-filter
        blurBg: {
            type: Boolean,
            default: true
        },

        //是否显示 border
        showBorder: {
            type: Boolean,
            default: false
        },

        //是否显示 shadow
        showShadow: {
            type: Boolean,
            default: true
        },

        //logo
        showLogo: {
            type: Boolean,
            default: true
        },
        logo: {
            type: String,
            default: 'cgy-cgydesign_light'
        },
        logoHref: {
            type: String,
            default: ''
        },

        //nav title
        barTitle: {
            type: String,
            default: ''
        },

        //自定义 bar 内容
        customBar: {
            type: Boolean,
            default: false
        },

    },
    data() {return {

    }},
    computed: {
        //输出 bar css
        barClass() {
            let is = this.$is,
                sz = this.size,
                szs = 'l,xl,xxl'.split(','),
                fxd = this.fixed,
                pos = this.position,
                gapi = this.showGapInner,
                gap = this.gapInner,
                gaps = 'xs,s,l,xl'.split(','),
                bgc = this.showBg,
                bgb = this.blurBg,
                bd = this.showBorder,
                shd = this.showShadow,
                ccls = this.customClass,
                cls = ['cv-bar'];
            //高度
            if (sz!='' && sz!=0) {
                if (is.realNumber(sz)) {
                    if (sz<18) {
                        sz = 18;
                    } else if (sz>64) {
                        sz = 64;
                    }
                    cls.push(`bar-${sz}px`);
                } else if (is.string(sz) && szs.includes(sz)) {
                    cls.push(`bar-${sz}`);
                }
            }
            //背景色
            if (bgc) {
                cls.push('bar-bg');
                if (bgb) cls.push('bar-bg-blur');
            }
            //fixed 位置
            if (fxd) {
                cls.push('bar-fixed');
                cls.push(`fixed-${pos}`);
            }
            //gap-inner
            if (gapi) {
                cls.push('bar-gap-inner');
                if (gaps.includes(gap)) cls.push(`gap-${gap}`);
            }
            //边线
            if (bd) cls.push('bar-bd');
            //阴影
            if (shd) cls.push('bar-shadow');
            //输出
            cls = cls.join(' ');
            //custom-class
            if (ccls!='') {
                cls += ' '+ccls;
            }
            return cls;
        },
        //输出 logo src
        logoSrc() {
            let sl = this.showLogo,
                lg = this.logo,
                prefix = `${Vue.lib}/icon/`;
            if (!sl || lg=='') return '';
            if (lg.includes('//')) return lg;
            return prefix+lg;
        },
    },
    methods: {
        //点击 logo 
        clickLogo() {
            let lh = this.logoHref;
            if (lh=='') return false;
            lh = `${Vue.host}/${lh.trimAnyStart('/')}`;
            window.location.href = lh;
        },
    }
}
</script>

<style>

</style>