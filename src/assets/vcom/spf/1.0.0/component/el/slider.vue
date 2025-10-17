<template>
    <el-slider
        v-model="cacheValue"
        v-bind="$attrs"
        :disabled="disabled"
        :class="computedClass"
        :style="computedStyle"
        @change="whenChange"
        @input="whenInput"
    ></el-slider>
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
        whenChange(value) {
            this.$emit('input', value);
            return this.$emit('change', value);
        },
        whenInput(value) {
            return this.$emit('input', value);
        },
    }
}
</script>

<style>

</style>