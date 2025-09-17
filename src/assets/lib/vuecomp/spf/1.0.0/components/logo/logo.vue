<template>
    <img 
        :src="logoSrc"
        :alt="alt"
        :class="computedClass"
        :style="computedStyle"
    >
</template>

<script>
import mixinBase from '/vue/@/mixins/base/base';

export default {
    mixins: [mixinBase],
    props: {
        /**
         * logo 使用 io.cgy.design/icon/*
         * 必须 包含 *-light / *-dark 两个图标
         */
        logo: {
            type: String,
            default: 'cgy'
        },

        /**
         * 指定 width 或 height 
         * 则 height 或 width 为 auto
         * 同时指定 height 生效 width 为 auto
         */
        width: {
            type: [String, Number],
            default: ''
        },
        height: {
            type: [String, Number],
            default: ''
        },

        //img alt
        alt: {
            type: String,
            default: ''
        },

        //logo icon url prefix
        urlPrefix: {
            type: String,
            default: 'https://io.cgy.design/icon/'
        },
    },
    data() {return {}},
    computed: {
        //计算得到的 custom-style
        computedStyle() {
            let is = this.$is,
                iss = s => is.string(s),
                isn = n => is.realNumber(n),
                ise = s => (!iss(s) && !isn(s)) || (iss(s) && s=='') || (isn(s) && s<=0),
                w = this.width,
                h = this.height,
                sty = {};
            if (!ise(h)) {
                sty.height = isn(h) ? `${h}px` : h;
                sty.width = 'auto';
            } else if (!ise(w)) {
                sty.width = isn(w) ? `${w}px` : w;
                sty.height = 'auto';
            }

            return this.mixinCustomStyle(sty);
        },

        //处理 logo img src
        logoSrc() {
            if (!this.$is.string(this.logo) || this.logo=='') return '';
            let dark = this.$UI.darkMode,
                logo = this.logo,
                url = this.urlPrefix,
                src = `${url}${logo}${dark ? '-dark' : '-light'}`;
            return src;
        },
    },
    methods: {

    }
}
</script>

<style>

</style>