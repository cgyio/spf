<template>
    <div
        :class="autoComputedStr.root.class"
        :style="autoComputedStr.root.style"
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
import mixinAutoProps from '../../mixin/auto-props';
import mixinBaseDynamic from '../../mixin/base-dynamic';

export default {
    mixins: [mixinAutoProps, mixinBaseDynamic],
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
            
        //覆盖 auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: '__PRE__-mask flex-x flex-y-center flex-x-center'
                }
            },
            prefix: 'mask',
            csvk: {
                size: '',
                color: 'bgc',
            },
            sub: {
                size: false,
                color: true,
                animate: true,
            },
            extra: {
                blur: true,
                alpha: 'normal',
                disabled: true
            },
            switch: {
                root: {
                    '!disabled && ?(animatePropClsk) #class': ['{{animatePropClsk}}'],
                }
            },
        },

        //覆盖 base-dynamic 动态组件参数
        dc: {
            //此动态组件只允许 单例
            multiple: false,
        },
    }},
    computed: {},
    methods: {

        /**
         * 动态组件的 动画效果
         */
        //显示
        async dcShow() {
            if (this.isDcShow===false) {
                //先设置 zIndex
                await this.setZindex();
                //执行显示动画
                await this.dcToggle('show', true);
                //触发事件
                this.$emit('mask-on');
                return true;
            }
            return false;
        },
        //隐藏
        async dcHide() {
            if (this.isDcShow===true) {
                //执行显示动画
                await this.dcToggle('show', false);
                //触发事件
                this.$emit('mask-off');
                return true;
            }
            return false;
        },

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