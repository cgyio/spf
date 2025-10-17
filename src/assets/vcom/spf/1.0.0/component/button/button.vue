<template>
    <div
        :class="computedClass"
        :style="computedStyle"
        :title-bak="title"
        @click="whenBtnClick"
        @mouseenter="whenMouseEnter"
        @mouseleave="whenMouseLeave"
        @mousedown="whenMouseDown"
        @mouseup="whenMouseUp"
    >
        <PRE@-icon
            v-if="icon!='' && !iconRight"
            :icon="icon"
            :size="iconSize"
            :color="popoutColor"
            :spin="spin"
            :custom-class="(label!=''?'btn-icon-left':'')+' '+(iconClass==''?'':iconClass)"
            :custom-style="iconStyle"
        ></PRE@-icon>
        <label 
            v-if="label!=''"
            :style="popoutColor!=''?'color:'+popoutColor+';':''"
        >{{label}}</label>
        <PRE@-icon
            v-if="icon!='' && iconRight"
            :icon="icon"
            :size="iconSize"
            :color="popoutColor"
            :spin="spin"
            :custom-class="(label!=''?'btn-icon-right':'')+' '+(iconClass==''?'':iconClass)"
            :custom-style="iconStyle"
        ></PRE@-icon>
    </div>
</template>

<script>
import mixinBase from '__URL_MIXIN__/base.js';

export default {
    mixins: [mixinBase],
    props: {

        //样式前缀
        cssPre: {
            type: String,
            default: 'btn-'
        },

        //图标名称，来自加载的图标包，在 cssvar 中指定
        icon: {
            type: String,
            default: ''
        },

        //按钮文字
        label: {
            type: String,
            default: ''
        },

        //title
        title: {
            type: String,
            default: ''
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

        /**
         * 开关
         */
        //disabled
        //disabled: {
        //    type: Boolean,
        //    default: false
        //},
        //active
        active: {
            type: Boolean,
            default: false
        },
        /**
         * popout 可以指定弹出之前的 按钮图标/文本 颜色
         * popout = true 则使用 type 指定的 颜色
         * popout = fc.d2 则使用 cssvar.color.fc.d2 作为 按钮图标/文本 颜色
         * popout = #ff0000 直接指定 按钮图标/文本 颜色
         */
        popout: {
            type: [Boolean, String],
            default: false
        },
        //text 链接样式 按钮
        text: {
            type: Boolean,
            default: false
        },
        //是否右侧显示图标
        iconRight: {
            type: Boolean,
            default: false
        },
        //round
        round: {
            type: Boolean,
            default: false
        },
        //square
        square: {
            type: Boolean,
            default: false
        },
        //plain
        plain: {
            type: Boolean,
            default: false
        },
        //spin
        spin: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        //mouse 状态
        mouse: {
            enter: false,
            down: false,
            clicking: false,    //for debounce 点击按钮防抖
        },
    }},
    computed: {
        /**
         * customClass/Style 配套的 计算属性
         * !! 引用的组件内部 应根据需要覆盖
         */
        //计算后的 class override
        computedClass() {
            let is = this.$is,
                sz = this.sizeClass,
                tp = this.typeClass,
                clss = ['cv-btn'];
            if (!is.empty(sz)) clss.push(...sz);
            if (!is.empty(tp)) clss.push(...tp);
            if (this.label=='') clss.push('btn-no-label');
            if (this.square) clss.push('btn-square');
            if (this.round) clss.push('btn-round');
            if (this.plain) clss.push('btn-plain');
            if (this.text) clss.push('btn-text');
            if (this.popout) clss.push('btn-popout');
            if (this.active) clss.push('btn-active');
            if (this.disabled) clss.push('btn-disabled');
            //if (this.mouse.enter==true) clss.push('btn-msov');
            if (this.mouse.down==true) clss.push('btn-shrink');
            return this.mixinCustomClass(...clss);
        },

        //根据 size 属性 获取 icon size 数字 或 px字符串
        iconSize() {
            let is = this.$is,
                sz = this.sizeKey,
                csv = this.cssvar.size.btn;
            if (is.realNumber(sz)) return sz-12;
            if (is.string(sz)) {
                if (is.defined(csv.icon[sz])) return csv.icon[sz];
                if (sz.endsWith('px')) {
                    sz = sz.replace('px', '');
                    return sz*1 - 12;
                }
            }
            return sz;
        },

        //popout 指定的 图标/文本 颜色
        popoutColor() {
            let is = this.$is,
                csv = this.cssvar.color,
                po = this.popout;
            if (!is.string(po)) return '';
            let c = this.$cgy.loget(csv, po, '');
            if (is.plainObject(c) && is.defined(c.$)) return c.$;
            if (is.string(c) && c!='') return c;
            if (po.startsWith('#') || po.startsWith('rgb')) return po;
            return '';
        },
    },
    methods: {
        //click 事件
        //防抖 debounce
        whenBtnClick(event) {
            if (this.disabled) return false;
            if (this.mouse.clicking!==true) {
                this.mouse.clicking = true;
                event.targetComponent = this;
                this.$ev('click', this, event);
                this.$wait(500).then(()=>{
                    this.mouse.clicking = false;
                });
            }
        },

        //mouse 事件
        whenMouseEnter(event) {
            if (this.disabled) return false;
            this.mouse.enter = true;
            event.targetComponent = this;
            this.$ev('mouse-enter', this, event);
        },
        whenMouseLeave(event) {
            if (this.disabled) return false;
            this.mouse.enter = false;
            event.targetComponent = this;
            this.$ev('mouse-leave', this, event);
        },
        whenMouseDown(event) {
            if (this.disabled) return false;
            this.mouse.down = true;
            event.targetComponent = this;
            this.$ev('mouse-down', this, event);
        },
        whenMouseUp(event) {
            if (this.disabled) return false;
            this.mouse.down = false;
            event.targetComponent = this;
            this.$ev('mouse-up', this, event);
        },
    }
}
</script>

<style>

</style>