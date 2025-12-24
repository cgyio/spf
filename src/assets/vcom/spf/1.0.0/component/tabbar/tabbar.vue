<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
    >
        <div class="tabbar-ctrl-start flex-x">
            <PRE@-button
                v-if="enableScroll"
                icon="keyboard-arrow-left"
                :size="ctrlSize"
                effect="popout"
                stretch="square"
                disabled
            ></PRE@-button>
        </div>
        <div class="tabbar-list flex-1 flex-x flex-x-center flex-no-shrink">
            <div v-if="align !== 'left'" class="tabbar-list-holder"></div>
            <template v-if="tabList.length>0">
                <PRE@-tabbar-item
                    v-for="tab of tabList"
                    :key="'tabbar_items_'+tab.key"
                    :tab-key="tab.key"
                    :tab-icon="tab.icon ? tab.icon : '-empty-'"
                    :tab-label="tab.label"
                    :tab-size="size"
                    :closable="closable"
                    :active="value === tab.key"
                    @tabitem-active="whenTabItemActive"
                ></PRE@-tabbar-item>
            </template>
            <slot></slot>
            <div v-if="align !== 'right'" class="tabbar-list-holder"></div>
        </div>
        <div class="tabbar-ctrl-end flex-x">
            <PRE@-button
                v-if="enableScroll"
                :size="ctrlSize"
                icon="keyboard-arrow-right"
                effect="popout"
                stretch="square"
                disabled
            ></PRE@-button>
        </div>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base.js';

export default {
    mixins: [mixinBase],
    model: {
        prop: 'value',
    },
    props: {
        /**
         * 覆盖 base-style.mixin 中的 size 参数
         * 可选：mini|small|normal|medium
         */
        size: {
            type: String,
            default: 'normal'
        },

        //tabbar 位置 可选：top|bottom
        position: {
            type: String,
            default: 'top',
        },

        //align 对齐形式  可选：left|center|right
        align: {
            type: String,
            default: 'center'
        },

        //是否使用 border 样式
        border: {
            type: Boolean,
            default: false
        },

        //启用横向滚动
        enableScroll: {
            type: Boolean,
            default: false
        },

        //是否启用 tab-item 的 close 功能
        closable: {
            type: Boolean,
            default: false,
        },

        /**
         * 指定 tab-item 列表 []
         */
        tabList: {
            type: Array,
            default: () => []
        },

        //v-model 关联的 prop 表示当前激活的 tab-item.key
        value: {
            type: [String, Number],
            default: ''
        },
    },
    data() {return {
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: ['__PRE__-tabbar', 'flex-1', 'flex-x', 'flex-y-stretch'],
                }
            },
            prefix: 'tabbar',
            sub: {
                size: true,
            },
            switch: {
                //启用 下列样式开关
                position: true,
                border: true,
                closable: true,
            },
            csvKey: {
                size: 'btn',
                color: '',
            },
        },

    }},
    computed: {
        //根据 size 自动计算 ctrl-start|end 按钮的 size 参数
        ctrlSize() {
            //ctrl 按钮尺寸小一级
            return this.$ui.sizeKeyShiftTo(this.size, 's1');
        },
    },
    methods: {
        //响应 tabitem-active 事件
        whenTabItemActive(tabKey) {
            console.log(tabKey);
            //触发 tab-active 事件
            this.$emit('tab-active', tabKey);
            //触发 input 事件，使 v-model 生效
            return this.$emit('input', tabKey);
        },
    }
}
</script>

<style>

</style>