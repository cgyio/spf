<template>
    <PRE@-ctn
        v-bind="autoComputed.root.props"
        inner-content
        v-slot="{autoParams: cntAutoParams, cntProps, cntClass, cntStyle}"
    >
        <slot 
            v-bind="{
                autoParams: cntAutoParams,
                vProps: cntProps,
                vClass: cntClass,
                vStyle: cntStyle
            }"
        ></slot>
    </PRE@-ctn>
</template>

<script>
import mixinAutoProps from '../../mixin/auto-props';

export default {
    mixins: [mixinAutoProps],
    props: {

    },
    data() {return {
        //auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: ''
                }
            },
            prefix: 'block',
            csvk: {
                size: 'bar',
                color: 'bgcc'
            },
            sub: {
                size: true,
                color: true,
                border: true,
            },
            extra: {
                //其他样式开关
                'grow #.flex-1': true,
                growInBar: true,
                stretch: 'row',
                tightness: 'normal',
                shape: 'sharp',
                //innerContent: true,
                //borderBind: 'true',
            },
        },
    }},
    computed: {

    },
    methods: {

    }
}
</script>

<style>

</style>