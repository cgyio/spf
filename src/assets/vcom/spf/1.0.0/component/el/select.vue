<template>
    <el-select
        v-model="cacheValue"
        v-bind="$attrs"
        popper-class="cv-el-pop"
        :disabled="disabled"
        :class="computedClass"
        :style="computedStyle"
        @change="whenSelectChange"
        @visible-change="whenVisibleChange"
        @clear="whenSelectClear"
        @blur="whenSelectBlur"
        @focus="whenSelectFocus"
    >
        <template v-if="options.length>0">
            <template
                v-for="(opi,opidx) of options"
            >
                <el-option
                    v-if="$is.plainObject(opi)"
                    :key="'cv_el_select_option_'+opidx"
                    :label="opi.label"
                    :value="opi.value"
                ></el-option>
                <el-option
                    v-else
                    :key="'cv_el_select_option_'+opidx"
                    :label="opi"
                    :value="opi"
                ></el-option>
            </template>
        </template>
        <template v-else>
            <slot></slot>
        </template>

        <template v-slot:prefix>
            <slot name="prefix"></slot>
            <cv-icon
                v-if="icon!=''"
                :icon="icon"
                color="fc.l2"
                :size="17"
                custom-style="margin: 8px 0 0 3px;"
            ></cv-icon>
        </template>

        <template v-slot:empty>
            <slot name="empty"></slot>
        </template>
    </el-select>
</template>

<script>
import mixinBase from '../../mixin/base';

export default {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: [String, Array],
            default: ''
        },

        //options
        options: {
            type: Array,
            default: ()=>[]
        },

        //prefix icon
        icon: {
            type: String,
            default: ''
        },
    },
    data() {return {
        cacheValue: this.value,
    }},
    computed: {

        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            let dft = ['cv-el'];
            return this.mixinCustomClass(...dft);
        },
    },
    watch: {
        value(nv,ov) {
            this.cacheValue = this.value;
        }
    },
    methods: {
        /**
         * 传递事件
         */
        whenSelectChange(selectedValue) {
            this.$emit('input', selectedValue);
            return this.$emit('change', selectedValue);
        },
        whenVisibleChange(visible) {
            return this.$emit('visible-change', visible);
        },
        whenSelectClear() {
            return this.$emit('clear');
        },
        whenSelectBlur(event) {
            return this.$emit('blur', event);
        },
        whenSelectFocus(event) {
            return this.$emit('focus', event);
        }
    }
}
</script>

<style>

</style>