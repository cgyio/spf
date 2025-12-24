<template>
    <svg 
        :class="styComputedClassStr.root" 
        :style="styComputedStyleStr.root"
        aria-hidden="true"
    >
        <use v-if="iconKey!='-empty-'" v-bind:xlink:href="'#'+iconKey">
            <animateTransform
                v-if="spinSelf"
                attributeName="transform"
                attributeType="XML"
                type="rotate"
                :from="'0'+spinCenter"
                :to="'360'+spinCenter"
                :dur="spinDura"
                repeatCount="indefinite" />
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
                    root: ['__PRE__-icon'],
                }
            },
            prefix: 'icon',
            sub: {
                size: true,
                color: true,
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
                fsarr = this.$ui.sizeToArr(fsz),
                fszn = fsarr[0],
                r = fszn/2;
            return ` ${r} ${r}`;
        },
    },
    methods: {

    }
}
</script>

<style>

</style>