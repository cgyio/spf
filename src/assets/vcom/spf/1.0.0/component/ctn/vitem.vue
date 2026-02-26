<template>
    <PRE@-ctn
        v-bind="autoComputed.root.props"
        inner-content
        with-header
        :header-props="autoComputed.header.props"
        :header-class="autoComputed.header.class"
        :header-style="autoComputed.header.style"
        :cnt-props="autoComputed.cnt.props"
        :cnt-class="autoComputed.cnt.class"
        :cnt-style="autoComputed.cnt.style"
        @header-click="$emit('change', !collapsed)"
    >
        <template #header>
            <PRE@-icon
                v-if="collapseSignLeft"
                v-bind="autoComputed.sign.props"
                :root-class="autoComputedStr.sign.class"
                :root-style="autoComputedStr.sign.style"
            ></PRE@-icon>
            <PRE@-icon
                v-if="$is.nemstr(icon)"
                v-bind="autoComputed.icon.props"
                :root-class="autoComputedStr.icon.class"
                :root-style="autoComputedStr.icon.style"
            ></PRE@-icon>
            <span
                v-if="$is.nemstr(label)"
                :class="autoComputedStr.label.class"
                :style="autoComputedStr.label.style"
            >{{label}}</span>
            <slot 
                v-else
                name="label" 
                v-bind="{
                    autoParams, 
                    labelProps: autoComputed.label.props
                }"
            ></slot>
            <span class="flex-1"></span>
            <span
                v-if="$is.nemstr(info)"
                :class="autoComputedStr.info.class"
                :style="autoComputedStr.info.style"
            >{{info}}</span>
            <slot 
                v-else
                name="info" 
                v-bind="{
                    autoParams, 
                    infoProps: autoComputed.info.props
                }"
            ></slot>
            <PRE@-icon
                v-if="!collapseSignLeft"
                v-bind="autoComputed.sign.props"
                :root-class="autoComputedStr.sign.class"
                :root-style="autoComputedStr.sign.style"
            ></PRE@-icon>
        </template>
        <template #default>
            <slot 
                v-bind="{
                    autoParams,
                    cntProps: autoComputed.cnt.props,
                    cntClass: autoComputed.cnt.class,
                    cntStyle: autoComputed.cnt.style
                }"
            ></slot>
        </template>
    </PRE@-ctn>
</template>

<script>
import mixinAutoProps from '../../mixin/auto-props';

export default {
    mixins: [mixinAutoProps],
    model: {
        prop: 'collapsed',
        event: 'change'
    },
    props: {
        //当前折叠面板 item 在 __PRE__-ctn-v 组件的面板列表中的唯一标记 key
        itemKey: {
            type: String,
            default: '',
            required: true
        },

        //header props
        icon: {
            type: String,
            default: ''
        },
        label: {
            type: String,
            default: ''
        },
        info: {
            type: String,
            default: ''
        },

        //collapse 启用面板折叠
        collapse: {
            type: Boolean,
            default: true
        },
        //当前已折叠标记
        collapsed: {
            type: Boolean,
            default: true
        },
        //面板折叠标记在 左侧
        collapseSignLeft: {
            type: Boolean,
            default: false
        },

    },
    data() {return {
        //auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: '',
                    props: {
                        stretch: 'row',
                        headerClickable: true,
                    },
                    accept: {
                        sub: ['size','color'],
                    },
                },
                header: {
                    class: '',
                    props: {},
                    accept: {
                        sub: ['size','color'],
                        extra: ['tightness','shape'],
                    },
                },
                icon: {
                    class: '',
                },
                label: {
                    class: 'fc-d3',
                },
                info: {
                    class: 'fc-l2',
                },
                sign: {
                    props: {},
                },
                cnt: {
                    props: {},
                },
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
                //'grow #.flex-1': true,
                //growInBar: true,
                //stretch: 'row',
                tightness: 'normal',
                shape: 'sharp',
                //innerContent: true,
                //borderBind: 'true',
            },
            switch: {
                'root #props': {
                    '{{$is.nemstr(border)}} #border': '{{autoModBorder("*","b","*")}}',
                    hideCnt: '{{collapsed}} [Boolean]',
                },

                'icon #props': {
                    icon: '{{icon}}',
                },

                '!collapsed @label #class': '.fw-bold',

                'sign #props': {
                    icon: 'keyboard-arrow-{{collapsed ? "down" : "up"}}',
                    size: '{{autoSizeShift("s1","icon")}}',
                    mg: '{{autoSizeShift("s1","mg")}}',
                    mgPo: '{{collapseSignLeft ? "r" : "l"}}',
                },
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