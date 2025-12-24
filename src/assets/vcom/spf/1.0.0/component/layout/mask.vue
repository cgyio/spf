<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
        @click="whenMaskClick"
    >
        <PRE@-icon 
            v-if="loading"
            icon="spinner-spin" 
            size="huge" 
            spin
        ></PRE@-icon>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base.js';
import mixinBaseDynamic from '../../mixin/base-dynamic.js';

export default {
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
                    root: ['__PRE__-mask', 'flex-x', 'flex-y-center', 'flex-x-center'],
                }
            },
            prefix: 'mask',
            sub: {
                size: false,
                color: true,
                animate: 'enabled',
            },
            switch: {
                //启用 下列样式开关
                blur: true,
                alpha: true,
                'dcDisplay.show': 'show',
            },
            csvKey: {
                size: '',
                color: 'bgc',
            },
        },

        //作为动态组件
        multiple: false,
    }},
    computed: {},
    methods: {
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
</script>

<style>

</style>