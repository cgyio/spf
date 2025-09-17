<template>
    <el-time-picker
        v-model="cacheValue"
        :type="type"
        popper-class="cv-el-pop"
        :disabled="disabled"
        v-bind="$attrs"
        :class="computedClass"
        :style="computedStyle"
        @change="whenChange"
        @blur="whenBlur"
        @focus="whenFocus"
    ></el-time-picker>
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
            type: [String, Number, Date, Array],
            default: ''
        },

        /*type: {
            type: String,
            default: 'date'
        },*/
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
        whenChange(value) {
            this.$emit('input', value);
            return this.$emit('change', value);
        },
        whenBlur(pickerComp) {
            return this.$emit('blur', pickerComp);
        },
        whenFocus(pickerComp) {
            return this.$emit('focus', pickerComp);
        }
    }
}
</script>

<style>

</style>