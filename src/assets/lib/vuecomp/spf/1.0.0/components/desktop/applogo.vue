<template>
    <div 
        :class="'cv-desk-app-logo '+(customClass==''?'':customClass)"
        :style="logoSty"
    >
        <img 
            :src="imgSrc" 
            :class="imgShadow?'cv-desk-img-shadow':''"
            :style="imgSty"
        >
    </div>
</template>

<script>
import mixinBase from 'mixins/base';

export default {
    mixins: [mixinBase],
    props: {
        //io.cgy.design/icon/logo-*
        logo: {
            type: String,
            default: 'qq',
            required: true
        },

        //background color
        background: {
            type: String,
            default: 'transparent'
        },

        //size
        size: {
            type: [String, Number],
            default: 'medium'
        },

        //img
        imgStyle: {
            type: [String, Object],
            default: ''
        },
        imgSize: {
            type: [String, Number],
            default: ''
        },
        //drop-shadow
        imgShadow: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            //size setting
            sizes: {
                giant: {
                    body: 72,
                    icon: 48,
                    radi: 12,
                },
                large: {
                    body: 60,
                    icon: 40,
                    radi: 10,
                },
                medium: {
                    body: 48,
                    icon: 32,
                    radi: 8,
                },
                small: {
                    body: 36,
                    icon: 24,
                    radi: 6,
                },
                mini: {
                    body: 28,
                    icon: 20,
                    radi: 4,
                }
            },
        }
    },
    computed: {
        //logo style
        logoSty() {
            let is = this.$is,
                ext = this.$extend,
                fix = n => !isNaN(n*1) ? n+'px' : n,
                sz = this.size,
                szs = this.sizes,
                szi = szs[sz] || szs.medium,
                bgc = this.background,
                csty = this.customStyle,
                sty = {
                    width: fix(szi.body),
                    height: fix(szi.body),
                    borderRadius: fix(szi.radi),
                    background: bgc,
                };
            if (is.plainObject(csty)) {
                sty = ext(sty, csty);
            }
            sty = this.$cgy.toCssString(sty);
            if (is.string(csty)) {
                sty = `${sty} ${csty}`;
            }
            return sty;
        },
        //img style
        imgSty() {
            let is = this.$is,
                ext = this.$extend,
                fix = n => !isNaN(n*1) ? n+'px' : n,
                sz = this.size,
                szs = this.sizes,
                szi = szs[sz] || szs.medium,
                isty = this.imgStyle,
                isz = this.imgSize,
                isn = (isz=='' || isz<=0) ? fix(szi.icon) : fix(isz),
                sty = {
                    width: isn,
                    height: 'auto',
                };
            if (is.plainObject(isty)) {
                sty = ext(sty, isty);
            }
            sty = this.$cgy.toCssString(sty);
            if (is.string(isty)) {
                sty = `${sty} ${isty}`;
            }
            return sty;
        },

        //src
        imgSrc() {
            let logo = this.logo;
            if (logo.startsWith('http') || logo.includes('//')) return logo;
            let pre = 'https://io.cgy.design';
            if (logo.startsWith('logo-')) return `${pre}/icon/${logo}`;
            if (logo.startsWith('icon/')) return `${pre}/${logo}`;
            return `${pre}/icon/logo-${logo}`;
        },
    },
    methods: {

    }
}
</script>

<style>

</style>