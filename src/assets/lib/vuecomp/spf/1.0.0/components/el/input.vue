<template>
    <el-input
        v-model="cacheValue"
        v-bind="$attrs"
        :type="type"
        :disabled="disabled"
        :class="computedClass"
        :style="computedStyle"
        @change="whenChange"
        @blur="whenBlur"
        @focus="whenFocus"
        @input="whenInput"
        @clear="whenClear"
    >
        <template v-if="icon!='' && iconRight==false" v-slot:prefix>
            <cv-icon
                :icon="icon"
                color="fc.l2"
                :size="17"
                custom-style="margin: 8px 0 0 3px;"
            ></cv-icon>
        </template>
        <template v-if="icon!='' && iconRight==true" v-slot:suffix>
            <cv-icon
                :icon="icon"
                color="fc.l2"
                :size="17"
                custom-style="margin: 8px 3px 0 0;"
            ></cv-icon>
        </template>

        <!--<template v-if="type=='text' && icon==''" v-slot:prefix>
            <slot name="prefix"></slot>
        </template>
        <template v-if="type=='text'" v-slot:suffix>
            <slot name="suffix"></slot>
        </template>-->

        <template v-if="type=='text'" v-slot:prepend>
            <slot name="prepend"></slot>
        </template>

        <template v-if="type=='text'" v-slot:append>
            <slot name="append"></slot>
        </template>

    </el-input>
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
            type: [String,Number],
            default: ''
        },

        type: {
            type: String,
            default: 'text'
        },

        icon: {
            type: String,
            default: ''
        },
        iconRight: {
            type: Boolean,
            default: false
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
        whenBlur(event) {
            return this.$emit('blur', event);
        },
        whenFocus(event) {
            return this.$emit('focus', event);
        },
        whenInput(value) {
            return this.$emit('input', value);
        },
        whenClear() {
            return this.$emit('clear');
        },
    }
}
</script>

<style>

</style>