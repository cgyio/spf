<template>
    <div 
        :class="'cv-desk-shortcut '+(customClass==''?'':customClass)"
        :style="scutSty"
        @click="whenShortcutClick"
    >
        <cv-desktop-applogo
            :logo="logo"
            :size="size"
            :img-shadow="true"
            v-bind="$attrs"
        ></cv-desktop-applogo>
        <span 
            v-if="!noLabel"
            :class="'cv-desk-shortcut-label '+(labelClass==''?'':labelClass)"
            :style="labelSty"
        >{{label}}</span>
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

        //size
        size: {
            type: [String, Number],
            default: 'large'
        },

        //app label
        label: {
            type: String,
            default: '快捷方式'
        },

        //不显示 标签
        noLabel: {
            type: Boolean,
            default: false
        },

        //label 样式
        labelClass: {
            type: String,
            default: ''
        },
        labelStyle: {
            type: [String, Object],
            default: ''
        },
        labelSize: {
            type: [Number, String],
            default: ''
        },
    },
    data() {
        return {
            //size setting
            sizes: {
                giant: {
                    body: 96,
                    f: '$',
                    radi: 12,
                },
                large: {
                    body: 80,
                    f: 's',
                    radi: 10,
                },
                medium: {
                    body: 64,
                    f: 's',
                    radi: 8,
                },
                small: {
                    body: 48,
                    f: 's',
                    radi: 6,
                },
                mini: {
                    body: 32,
                    f: 'xs',
                    radi: 4,
                }
            },
        }
    },
    computed: {
        //shortcut style
        scutSty() {
            let is = this.$is,
                ext = this.$extend,
                fix = n => !isNaN(n*1) ? n+'px' : n,
                sz = this.size,
                szs = this.sizes,
                szi = szs[sz] || szs.medium,
                csty = this.customStyle,
                sty = {
                    //width: fix(szi.body),
                    borderRadius: fix(szi.radi)
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
        //label style
        labelSty() {
            let is = this.$is,
                ext = this.$extend,
                fix = n => !isNaN(n*1) ? n+'px' : n,
                cssv = this.cssvar.size.f,
                sz = this.size,
                szs = this.sizes,
                szi = szs[sz] || szs.medium,
                lsty = this.labelStyle,
                lsz = this.labelSize,
                fsz = (lsz=='' || lsz<=0) ? fix(cssv[szi.f]) : fix(lsz),
                sty = {
                    width: fix(szi.body),
                    fontSize: fsz,
                    marginBottom: fix(szi.radi),
                };
            if (is.plainObject(lsty)) {
                sty = ext(sty, lsty);
            }
            sty = this.$cgy.toCssString(sty);
            if (is.string(lsty)) {
                sty = `${sty} ${lsty}`;
            }
            return sty;
        },
        
    },
    methods: {
        //点击时
        whenShortcutClick(evt) {
            console.log(evt);
            console.log('shortcut click');

            this.$invoke('cv-panel', {
                isPopup: true,
                popShow: true,
                popMask: false,
                showBorder: true,
                showShadow: true,

                gap: 'compact',

                width: 640,
                height: 540,
            });
        },
    }
}
</script>

<style>

</style>