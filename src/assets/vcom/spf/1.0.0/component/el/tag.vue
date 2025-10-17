<template>
    <el-tag
        v-bind="$attrs"
        :size="size"
        :type="type"
        :disabled="disabled"
        :effect="effect"
        :class="computedClass"
        :style="computedStyle"
        @click="whenClick"
        @close="whenClose"
    >
        <cv-icon
            v-if="icon!=''"
            :icon="icon"
            :size="size"
        ></cv-icon>
        <template v-if="separate">
            <span v-if="sepLabel!=''" class="cv-el-tag-sep-label">{{ sepLabel }}</span>
            <span class="cv-el-tag-sep-value">{{ sepValue }}</span>
        </template>
        <template v-else>
            <slot></slot>
        </template>
    </el-tag>
</template>

<script>
import mixinBase from '../../mixin/base';

export default {
    mixins: [mixinBase],
    props: {
        type: {
            type: String,
            default: 'primary'
        },

        size: {
            type: [String, Number],
            default: ''
        },

        //单独指定 文字颜色
        color: {
            type: String,
            default: ''
        },

        //effect
        effect: {
            type: String,
            default: 'light'
        },

        //disabled
        disabled: {
            type: Boolean,
            default: false
        },

        //hover 是否启用 hover 翻转
        //hover: {
        //    type: Boolean,
        //    default: false
        //},

        //可使用 icon
        icon: {
            type: String,
            default: ''
        },

        //是否 拆分 tag 为 label/value
        separate: {
            type: Boolean,
            default: false
        },
        sepLabel: {
            type: String,
            default: ''
        },
        sepValue: {
            type: String,
            default: ''
        },

    },
    data() {return {
        
    }},
    computed: {

        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            let is = this.$is,
                //szo = this.calcSize(),
                tp = this.typeKey,
                tpk = tp.includes('-') ? tp.split('-')[0] : tp,
                dis = this.disabled,
                cl = this.color,
                ef = this.effect,
                //bd = this.border,
                //ico = this.icon,
                //sep = this.separate,
                cls = [];
            //cv-el
            cls.push('cv-el');
            //type/disabled
            if (!dis) {
                cls.push(`cv-el-tag-${tpk}`);
            } else {
                cls.push('cv-el-tag-disabled');
            }
            //font
            if (cl=='' || cl=='reverse') {
                if (ef=='dark') {
                    cls.push(`f-${tpk}${cl=='' ? '-f' : ''}`);
                } else {
                    cls.push(`f-${tpk}${cl=='' ? '' : '-f'}`);
                }
            }
            //cls.push(`f-${szo.font.key=='$' ? 'm':szo.font.key}`);
            //padding
            //cls.push(`pd-x-${szo.padding.key=='$' ? 'm':szo.padding.key}`);
            //separate
            //if (sep) 

            return this.mixinCustomClass(...cls);
        },

        //tag style
        computedStyle() {
            let is = this.$is,
                /*s2n = s => (is.string(s) && s.endsWith('px')) ? s.replace('px','')*1 : s*1,
                n2s = n => is.realNumber(n) ? n+'px' : n,
                csv = this.cssvar,
                szs = csv.size.btn,
                sz = this.sizeKey=='m' ? '$' : this.sizeKey,
                szo = is.defined(szs[sz]) ? szs[sz] : null,
                szn = s2n(is.realNumber(sz) ? sz : (!is.null(szo) ? szo : sz)),*/
                //szo = this.calcSize(),
                //tp = this.typeKey,
                cl = this.color,
                cr = this.colorHex,
                //ef = this.effect,
                //bd = this.border,
                //hv = this.hover,
                //ms = this.mouse,
                sty = {};
            //height
            //sty.height = szo.height.val;
            //radius
            //sty.borderRadius = szo.radius.val;
            //dark border = none
            //if (ef=='dark' || bd==false) sty.border = 'none';
            //通过 color 自定义文字颜色
            if (cl!='' && cl!='reverse' && cr!='') {
                sty.color = `${cr} !important`;
            }
            //hover
            /*if (hv) {
                sty.cursor = 'pointer';
                sty.transition = 'all 0.3s';
                if (ms.enter==true) {
                    sty.opacity = 0.7;
                }
            }*/

            return this.mixinCustomStyle(sty);
        },
    },
    watch: {
        
    },
    methods: {
        /**
         * 传递事件
         */
        whenClick() {
            return this.$emit('click');
        },
        whenClose() {
            return this.$emit('close');
        },
    }
}
</script>

<style>

</style>