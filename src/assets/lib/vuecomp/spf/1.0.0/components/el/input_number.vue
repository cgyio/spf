<template>
    <el-input-number
        v-model="cacheValue"
        v-bind="$attrs"
        popper-class="cv-el-pop"
        :disabled="disabled"
        :class="computedClass"
        :style="computedStyle"
        @change="whenChange"
        @blur="whenBlur"
        @focus="whenFocus"
    ></el-input-number>
</template>

<script>
import mixinBase from '/vue/@/mixins/base/base';

export default {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: Number,
            default: 0
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
        whenChange(value, oldValue) {
            this.$emit('input', value);
            return this.$emit('change', value, oldValue);
        },
        whenBlur(event) {
            return this.$emit('blur', event);
        },
        whenFocus(event) {
            return this.$emit('focus', event);
        }
    }
}
</script>

<style>

</style>