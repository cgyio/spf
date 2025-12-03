<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
    >
        <template v-if="color!=='' && color!=='-empty-'">
            <div class="item-title">{{colorShiftKey.toUpperCase()}}</div>
            <div class="item-body"></div>
            <div class="item-desc">
                <span>{{colorName}}</span>
                <span class="flex-1"></span>
                <span>{{colorValStr.toUpperCase()}}</span>
            </div>
        </template>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base.js';

export default {
    mixins: [mixinBase],
    props: {
        /**
         * 通过 样式计算系统 color 参数 指定要显示的 颜色
         * 例如：
         *      primary[-m] danger-l2 cyan-d1 ...
         * 也可以写为：
         *      primary.m  danger.l2  cyan.d1
         */
        color: {
            type: String,
            default: '',
            required: true,
        }
    },
    data() {return {
        /**
         * 覆盖 base-style 样式系统参数
         */
        styInit: {
            class: {
                //根元素
                root: ['__PRE__-theme-color-item'],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styCssPre: 'item',
        styEnable: {
            size: false,
            color: true,
            animate: false,
        },
        stySwitches: {
            //启用 下列样式开关
        },
        styCsvKey: {
            size: 'btn',
            color: 'bgc',
        },
    }},
    computed: {
        /**
         * 根据输入的 color 参数，得到 颜色元数据
         */
        //颜色名称
        colorName() {
            return this.colorValToKey.split('-')[0];
        },
        //颜色 shift queue key m|l1|d2...
        colorShiftKey() {
            return this.colorValToKey.split('-')[1];
        },
    },
    methods: {}
}
</script>

<style>

</style>