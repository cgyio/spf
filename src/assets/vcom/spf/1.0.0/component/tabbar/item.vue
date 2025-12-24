<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
        @click="whenTabItemClick"
    >
        <PRE@-icon 
            v-if="tabIcon!=='' && tabIcon!=='-empty-'"
            :icon="tabIcon"
        ></PRE@-icon>
        <label v-html="tabLabel"></label>
        <div v-if="active && closable" class="flex-1"></div>
        <PRE@-button
            v-if="active && closable"
            :size="closeBtnSize"
            icon="close"
            effect="popout"
            shape="circle"
            type="danger"
        ></PRE@-button>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base.js';

export default {
    mixins: [mixinBase],
    props: {
        //tab-item 数据
        tabKey: {
            type: String,
            default: '',
            required: true,
        },
        tabIcon: {
            type: String,
            default: '-empty-',
        },
        tabLabel: {
            type: String,
            default: '',
        },
        //传入的 tabsize 用于指定 close btn 的 size 参数
        tabSize: {
            type: [String, Number],
            default: 'normal'
        },

        //是否可关闭
        closable: {
            type: Boolean,
            default: false
        },

        //是否激活
        active: {
            type: Boolean,
            default: false
        },

    },
    data() {return {
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: ['tabbar-item', 'flex-x', 'flex-x-center'],
                }
            },
            prefix: 'tabbar-item',
            switch: {
                //启用 下列样式开关
                closable: true,
                active: true,
            },
        },
        
        
    }},
    computed: {
        //close-btn size
        closeBtnSize() {
            //关闭按钮的尺寸，比 tab 尺寸小两级
            return this.$ui.sizeKeyShiftTo(this.tabSize, 's3');
        },
    },
    methods: {
        //tab-item 点击事件
        whenTabItemClick(event) {
            if (this.active === true) return false;
            return this.$emit('tabitem-active', this.tabKey);
        },
    }
}
</script>

<style>

</style>