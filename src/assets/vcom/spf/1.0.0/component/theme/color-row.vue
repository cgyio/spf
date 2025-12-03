<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
    >
        <template v-if="colorShiftKeys.length>0">
            <PRE@-theme-color-item
                v-for="ckey of colorShiftKeys"
                :key="'__PRE__-color-item-'+ckey"
                :color="ckey"
                :custom-style="{width: colorItemWidth}"
            ></PRE@-theme-color-item>
        </template>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base.js';

export default {
    mixins: [mixinBase],
    props: {

        /**
         * 颜色 auto shift 层级
         * 默认 6
         */
        shiftLevel: {
            type: Number,
            default: 6
        },

        /**
         * 要展示的 颜色名称 在 $ui.cssvar.color 中定义的键
         * 例如：primary danger red cyan ...
         */
        color: {
            type: String,
            default: '',
            required: true,
        },

    },
    data() {return {
        /**
         * 覆盖 base-style 样式系统参数
         */
        styInit: {
            class: {
                //根元素
                root: ['__PRE__-theme-color-row'],
            },
            style: {
                //根元素
                root: {},
            },
        },
        styEnable: {
            size: false,
            color: false,
            animate: false,
        },
    }},
    computed: {
        //shiftLevel --> shiftQueue  3 --> [d3,d2,d1,m,l1,l2,l3]
        colorShiftQueue() {
            let sl = this.shiftLevel,
                ds = [],
                ls = [],
                rtn = [];
            for (let i=1;i<=sl;i++) {
                ds.unshift(`d${i}`);
                ls.push(`l${i}`);
            }
            rtn.push(...ds);
            rtn.push('m');
            rtn.push(...ls);
            return rtn;
        },
        /**
         * 根据 shiftQueue 和 color 参数，确定最重要输出的 key[]
         * 例如：[ primary-d3, primary-d2, primary-d1, primary-m, ...]
         * !! 会排除 $ui.cssvar.color 中未定义的 颜色 key 替换为 -empty-
         */
        colorShiftKeys() {
            let is = this.$is,
                que = this.colorShiftQueue,
                cn = this.color;
            if (!is.string(cn) || cn === '') return [];
            let cks = que.map(i=>`${cn}.${i}`),
                clrs = this.$ui.cssvar.color,
                lg = this.$cgy.loget,
                rtn = [];
            this.$each(cks, i => {
                let cv = lg(clrs, i);
                if (!is.string(cv)) {
                    rtn.push('-empty-');
                } else {
                    rtn.push(i.replace('.','-'));
                }
            });
            return rtn;
        },
        //根据 shiftLevel 计算 color-item 宽度 %
        colorItemWidth() {
            return (100/(this.shiftLevel*2+1))+'%';
        },
    },
    methods: {}
}
</script>

<style>

</style>