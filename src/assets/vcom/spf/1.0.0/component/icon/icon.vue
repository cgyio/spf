<template>
    <svg 
        :class="autoComputedStr.root.class" 
        :style="autoComputedStr.root.style"
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
import mixinAutoProps from '../../mixin/auto-props';

export default {
    mixins: [mixinAutoProps],
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
            
        //覆盖 auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: '__PRE__-icon',
                }
            },
            prefix: 'icon',
            csvk: {
                size: 'icon',
                color: 'fc',
            },
            sub: {
                size: true,
                color: true,
            },
            extra: {
                'disabled #manual': true,
            },
            switch: {
                'autoExtra.disabled @root #style': 'opacity: .3; cursor: not-allowed;', 
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
            if (is.plainObject(ico)) {
                return ico.full;
            }
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
</script>

<style>

</style>