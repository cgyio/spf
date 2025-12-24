<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
        @click="whenBtnClick"
        @mouseenter="whenMouseEnter"
        @mouseleave="whenMouseLeave"
        @mousedown="whenMouseDown"
        @mouseup="whenMouseUp"
    >
        <PRE@-icon
            v-if="!iconRight && !isEmptyIcon"
            :icon="icon"
            :size="iconSize"
            :spin="iconSpin"
            :shape="iconShape"
            :custom-class="iconClass"
            :custom-style="iconStyle"
        ></PRE@-icon>
        <label 
            v-if="!isEmptyLabel && stretch!=='square' && shape !== 'circle'"
            :class="labelClass"
            :style="labelStyle"
        >{{label}}</label>
        <PRE@-icon
            v-if="iconRight && !isEmptyIcon"
            :icon="icon"
            :size="iconSize"
            :spin="iconSpin"
            :shape="iconShape"
            :custom-class="iconClass"
            :custom-style="iconStyle"
        ></PRE@-icon>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base.js';

export default {
    mixins: [mixinBase],
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
         * 样式开关
         */
        //是否右侧显示图标
        iconRight: {
            type: Boolean,
            default: false
        },
        //按钮默认包含 hover 样式
        hoverable: {
            type: Boolean,
            default: true
        },
        //active 选中
        active: {
            type: Boolean,
            default: false
        },
        /**
         * shape 形状
         * 可选值： normal(默认) | pill | circle | sharp
         */
        shape: {
            type: String,
            default: 'normal'
        },
        /**
         * effect 填充效果
         * 可选值：  normal(默认) | fill | plain | popout
         */
        effect: {
            type: String,
            default: 'normal'
        },
        /**
         * stretch 按钮延伸类型
         * 可选值： normal(默认) | full-line | square
         */
        stretch: {
            type: String,
            default: 'normal'
        },
        //link 链接形式按钮
        link: {
            type: Boolean,
            default: false
        },
        //no-gap 按钮之间紧密排列，无间隙
        noGap: {
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
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: ['__PRE__-btn', 'flex-x', 'flex-x-center'],
                }
            },
            prefix: 'btn',
            sub: {
                size: true,
                color: true,
                animate: 'enabled',
            },
            switch: {
                //启用 下列样式开关
                iconRight: true,
                'hoverable:disabled': true,
                shape: true,
                effect: true,
                stretch: true,
                link: true,
                noGap: true,
                active: true,
                'stage.pending:disabled': 'pending',
                'stage.press:disabled': 'pressed',
                'fullfilledWhen:disabled': 'fullfilled',
            },
            csvKey: {
                size: 'btn',
                color: 'fc',
            },
        },

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

        //根据 btn-size 计算 内部 icon 的 size 参数
        iconSize() {
            let is = this.$is,
                ui = this.$ui,
                sztp = this.sizePropType,
                squ = this.stretch==='square' || this.effect==='circle',
                size = this.size;;
            if ('str,key'.split(',').includes(sztp)) {
                //按钮内部 icon 尺寸 小一级
                return squ ? size : ui.sizeKeyShiftTo(size, 's1');
            }
            let sz = this.sizePropVal;
            if (!ui.isSizeVal(sz)) return size;
            let fs = ui.sizeCalcBarFs(sz),
                nsz = ui.sizeValToKey(fs, 'icon');
            if (is.string(nsz)) return nsz;
            return fs;
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
    }
}
</script>

<style>

</style>