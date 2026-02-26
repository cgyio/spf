<template>
    <div
        :class="autoComputedStr.root.class"
        :style="autoComputedStr.root.style"
        @click="whenBtnClick"
        @mouseenter="whenMouseEnter"
        @mouseleave="whenMouseLeave"
        @mousedown="whenMouseDown"
        @mouseup="whenMouseUp"
    >
        <PRE@-icon
            v-if="!iconRight && !isEmptyIcon"
            v-bind="autoComputed.icon.props"
            :root-class="autoComputedStr.icon.class"
            :root-style="autoComputedStr.icon.style"
        ></PRE@-icon>
        <PRE@-button
            v-if="iconRight && closeable"
            v-bind="autoComputed.btn.props"
            :root-class="autoComputedStr.btn.class"
            :root-style="autoComputedStr.btn.style"
            @click="whenClose"
        ></PRE@-button>
        <label 
            v-if="!isEmptyLabel && !isSquare"
            :class="autoComputedStr.label.class"
            :style="autoComputedStr.label.style"
        >{{label}}</label>
        <PRE@-button
            v-if="!iconRight && closeable"
            v-bind="autoComputed.btn.props"
            :root-class="autoComputedStr.btn.class"
            :root-style="autoComputedStr.btn.style"
            @click="whenClose"
        ></PRE@-button>
        <PRE@-icon
            v-if="iconRight && !isEmptyIcon"
            v-bind="autoComputed.icon.props"
            :root-class="autoComputedStr.icon.class"
            :root-style="autoComputedStr.icon.style"
        ></PRE@-icon>
    </div>
</template>

<script>
import mixinAutoProps from '../../mixin/auto-props';

export default {
    mixins: [mixinAutoProps],
    props: {

        //图标名称，来自加载的图标包，在 cssvar 中指定
        icon: {
            type: String,
            default: '-empty-'
        },
        //图标形状
        iconShape: {
            type: String,
            default: 'round',
        },
        //图标旋转，参数要求与 icon 组件相同
        spin: {
            type: [Boolean, String],
            default: false
        },
        //icon 额外的样式
        iconClass: {
            type: String,
            default: ''
        },
        iconStyle: {
            type: [String, Object],
            default: ''
        },

        //按钮文字
        label: {
            type: String,
            default: ''
        },
        //label 额外样式
        labelClass: {
            type: String,
            default: ''
        },
        labelStyle: {
            type: [String, Object],
            default: ''
        },

        /**
         * 是否显示 关闭按钮
         */
        closeable: {
            type: Boolean,
            default: false
        },


        /**
         * 样式开关
         */
        //是否右侧显示图标
        iconRight: {
            type: Boolean,
            default: false
        },
        
        /**
         * shape 形状
         * 可选值： sharp | round(默认) | pill | circle
         * !! 覆盖 base-style 中定义的默认值
         */
        shape: {
            type: String,
            default: 'round'
        },
        /**
         * hoverable 
         * !! 覆盖 base-style 中定义的默认值
         */
        hoverable: {
            type: Boolean,
            default: true
        },

        //link 链接形式按钮
        //link: {
        //    type: Boolean,
        //    default: false
        //},
        //no-gap 按钮之间紧密排列，无间隙
        noGap: {
            type: Boolean,
            default: false
        },

        /**
         * 特殊样式
         */
        //在 单元格中
        incell: {
            type: Boolean,
            default: false
        },
        //作为 标题
        astitle: {
            type: Boolean,
            default: false
        },

        /**
         * 按钮防抖
         */
        //启用防抖 
        debounce: {
            type: Boolean,
            default: false
        },
        //启用组件外部的 fullfilled 标记
        fullfilled: {
            type: Boolean,
            default: false
        },
        //fullfilled == false 时，使用此处定义的等待时间
        debounceDura: {
            type: Number,
            default: 500,   //默认等待 500ms
        },
        //指定一个组件外部的 fullfilled 标记
        fullfilledWhen: {
            type: Boolean,
            default: false
        }
    },
    data() {return {
            
        //覆盖 auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: '__PRE__-btn btn flex-x flex-x-center',
                },
                icon: {
                    class: '',
                    props: {},
                },
                btn: {
                    class: '',
                    props: {
                        icon: 'close',
                        type: 'danger',
                        effect: 'popout',
                        shape: 'circle',
                    },
                },
                label: {
                    class: '',
                },
            },
            prefix: 'btn',
            csvk: {
                size: 'btn',
                color: 'fc',
            },
            sub: {
                size: true,
                color: true,
                animate: true,
            },
            extra: {
                stretch: 'auto',
                //关闭 tightness
                tightness: false,
                shape: 'round',
                effect: 'normal',
                noGap: true,
                incell: true,
                astitle: true,

                //自定义 样式类
                'active #.active': true,
                'disabled #.disabled': true,

                //手动处理
                'hoverable #manual': true,
            },
            switch: {
                root: {
                    '!autoExtra.disabled': {
                        'autoExtra.hoverable #class': '.hoverable',
                        'stage.pending': '.pending',
                        'stage.press': '.pressed',
                        'fullfilledWhen': '.fullfilled'
                    },
                },

                icon: {
                    '!isEmptyIcon #props': {
                        icon: '{{icon}}',
                        shape: '{{iconShape}}',
                        spin: '{{iconSpin==="" ? false : iconSpin}} [String,Boolean]',
                        size: '{{isSquare ? size : autoSizeShift("s1","icon")}}',
                    },
                    '!isEmptyIcon': {
                        '["str","key"].includes(sizePropType)!==true #style': 'fontSize: {{$ui.sizeValMul(sizePropVal, 0.5)}};'
                    },
                },

                btn: {
                    'closeable #props': {
                        size: '{{autoSizeShift("s2","btn")}}',
                    },
                    'closeable': {
                        'iconRight #style': 'margin-left:-0.8em; margin-right:1.5em;',
                        '!iconRight #style': 'margin-right:-0.8em; margin-left:1.5em;',
                    },
                },
            },
        },

        //sizeTest: 'tiny',

        /**
         * 按钮点击状态
         */
        stage: {
            ready: true,
            enter: false,
            press: false,
            //debounce
            pending: false,
            //fullfilled: false,
        },
    }},
    computed: {
        //判断是否 空 icon
        isEmptyIcon() {
            let icon = this.icon,
                is = this.$is;
            return !(is.string(icon) && icon !== '-empty-' && icon !== '');
        },
        //判断是否 空 label
        isEmptyLabel() {
            let label = this.label,
                is = this.$is;
            return !(is.string(label) && label !== '');
        },
        //判断是否 方形|圆形 按钮
        isSquare() {
            let shape = this.shape;
            return shape==='circle' || shape.endsWith('square');
            //return this.stretch==='square' || this.shape==='circle';
        },

        //stage 状态是否处于 pending 
        stagePending() {
            return this.debounce && this.stage.pending === true;
        },

        //根据 spin 参数以及 debounce 状态，确定要传给 icon 组件的实际 spin 参数
        iconSpin() {
            let is = this.$is,
                spin = this.spin;
            if (!this.debounce) return spin;
            if (this.stagePending) {
                if (is.boolean(spin)) return true;
                return spin;
            }
            return false;
        },
    },
    watch: {
        //外部 fullfilled 标记变化
        fullfilledWhen(nv, ov) {
            //处于 pending 阶段且 fullfilled 标记变为 true
            if (this.stagePending && nv === true) {
                //修改 pending 标记
                this.stage.pending = false;
                //this.stage.fullfilled = true;
            }
        },
    },
    methods: {

        //click 事件
        whenBtnClick(event) {
            if (this.disabled || this.stagePending) return false;
            if (this.debounce) {
                //pending 标记
                this.stage.pending = true;
                //this.stage.fullfilled = false;
                if (this.fullfilled === true) {
                    //启用了外部 fullfilled 标记，则检查此标记
                    if (this.fullfilledWhen === true) {
                        this.stage.pending = false;
                        //this.stage.fullfilled = true;
                        //不触发 click 事件
                        return false;
                    }
                } else {
                    //未启用外部 fullfilled 标记，则 setTimeout
                    this.$wait(this.debounceDura).then(()=>{
                        this.stage.pending = false;
                        //this.stage.fullfilled = true;
                    });
                }
            }

            //触发 click 事件，如果启用了 debounce 以及外部 fullfilled 标记，这将触发外部 async 方法异步修改外部标记
            //event.targetComponent = this;
            return this.$emit('click');
        },

        //mouse 事件
        whenMouseEnter(event) {
            if (this.disabled || this.stagePending) return false;
            //进入标记
            this.stage.enter = true;
            //event.targetComponent = this;
            //this.$ev('mouse-enter', this, event);
            return this.$emit('mouse-enter');
        },
        whenMouseLeave(event) {
            if (this.disabled) return false;
            //进入标记
            this.stage.enter = false;
            //event.targetComponent = this;
            //this.$ev('mouse-leave', this, event);
            return this.$emit('mouse-leave');
        },
        whenMouseDown(event) {
            if (this.disabled || this.stagePending) return false;
            //按下标记
            this.stage.press = true;
            //event.targetComponent = this;
            //this.$ev('mouse-down', this, event);
            return this.$emit('mouse-down');
        },
        whenMouseUp(event) {
            if (this.disabled) return false;
            //按下标记
            this.stage.press = false;
            //event.targetComponent = this;
            //this.$ev('mouse-up', this, event);
            return this.$emit('mouse-up');
        },

        //close按钮
        whenClose(event) {
            return this.$emit('close');
        },
    }
}
</script>

<style>

</style>