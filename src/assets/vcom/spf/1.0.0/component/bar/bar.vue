<template>
    <div
        :class="autoComputedStr.root.class"
        :style="autoComputedStr.root.style"
        @click="whenBarClick"
    >
        <template v-if="innerContent">
            <PRE@-bar
                v-if="!iconRight && !isEmptyIcon"
                v-bind="autoComputed.iconbar.props"
                :icon-props="autoComputed.icon.props"
                :icon-class="autoComputedStr.icon.class"
                :icon-style="autoComputedStr.icon.style"
            ></PRE@-bar>
            <PRE@-bar
                v-if="iconRight && !isEmptyBtn"
                v-bind="autoComputed.btnbar.props"
                :btn-props="autoComputed.btn.props"
                :btn-class="autoComputedStr.btn.class"
                :btn-style="autoComputedStr.btn.style"
                @suffix-btn-click="$emit('suffix-btn-click')"
            ></PRE@-bar>
            <div
                :class="autoComputedStr.cnt.class"
                :style="autoComputedStr.cnt.style"
            >
                <slot 
                    v-bind="{
                        autoParams,
                        cntProps: autoComputed.cnt.props,
                        cntClass: autoComputed.cnt.class,
                        cntStyle: autoComputed.cnt.style,
                    }"
                ></slot>
            </div>
            <PRE@-bar
                v-if="!iconRight && !isEmptyBtn"
                v-bind="autoComputed.btnbar.props"
                :btn-props="autoComputed.btn.props"
                :btn-class="autoComputedStr.btn.class"
                :btn-style="autoComputedStr.btn.style"
                @suffix-btn-click="$emit('suffix-btn-click')"
            ></PRE@-bar>
            <PRE@-bar
                v-if="iconRight && !isEmptyIcon"
                v-bind="autoComputed.iconbar.props"
                :icon-props="autoComputed.icon.props"
                :icon-class="autoComputedStr.icon.class"
                :icon-style="autoComputedStr.icon.style"
            ></PRE@-bar>
        </template>
        <template v-else>
            <PRE@-icon
                v-if="!iconRight && !isEmptyIcon"
                v-bind="autoComputed.icon.props"
                :root-class="autoComputedStr.icon.class"
                :root-style="autoComputedStr.icon.style"
            ></PRE@-icon>
            <PRE@-btn
                v-if="iconRight && !isEmptyBtn"
                v-bind="autoComputed.btn.props"
                :root-class="autoComputedStr.btn.class"
                :root-style="autoComputedStr.btn.style"
                @click="$emit('suffix-btn-click')"
            ></PRE@-btn>
            <slot 
                v-bind="{
                    autoParams,
                    cntProps: autoComputed.cnt.props,
                    cntClass: autoComputed.cnt.class,
                    cntStyle: autoComputed.cnt.style,
                }"
            ></slot>
            <PRE@-btn
                v-if="!iconRight && !isEmptyBtn"
                v-bind="autoComputed.btn.props"
                :root-class="autoComputedStr.btn.class"
                :root-style="autoComputedStr.btn.style"
                @click="$emit('suffix-btn-click')"
            ></PRE@-btn>
            <PRE@-icon
                v-if="iconRight && !isEmptyIcon"
                v-bind="autoComputed.icon.props"
                :root-class="autoComputedStr.icon.class"
                :root-style="autoComputedStr.icon.style"
            ></PRE@-icon>
        </template>
    </div>
</template>

<script>
import mixinAutoProps from '../../mixin/auto-props';

export default {
    mixins: [mixinAutoProps],
    props: {
        /**
         * stretch 按钮延伸类型
         * 可选值： auto | square | grow | row(默认)
         * !! 覆盖 base-style 中定义的默认值
         */
        //stretch: {
        //    type: String,
        //    default: 'row'
        //},
        /**
         * effect 填充效果
         * 可选值：  normal | fill | plain | popout(默认)
         * !! 覆盖 base-style 中定义的默认值
         */
        //effect: {
        //    type: String,
        //    default: 'popout'
        //},

        //图标名称，来自加载的图标包，在 cssvar 中指定
        icon: {
            type: String,
            default: ''
        },
        //是否右侧 icon
        iconRight: {
            type: Boolean,
            default: false
        },

        //行末按钮 指定 按钮 icon 默认空值 表示不显示
        suffixBtn: {
            type: String,
            default: ''
        },

        //是否内置 content 整行容器
        innerContent: {
            type: Boolean,
            default: false
        },
        //是否在内置的 整行容器中启用 flex-y 容器
        innerContentRows: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        //覆盖 auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: '__PRE__-bar bar',
                },
                iconbar: {
                    props: {
                        stretch: 'auto',
                        tightness: 'none',
                    },
                },
                btnbar: {
                    props: {
                        stretch: 'auto'
                    },
                    accept: {
                        extra: ['tightness'],
                    },
                },
                icon: {
                    class: '',
                },
                btn: {
                    props: {
                        shape: 'circle',
                        effect: 'popout',
                    },
                },
                cnt: {
                    class: 'bar-cnt flex-x flex-no-shrink',
                    //style: 'height: 100%;'
                }
            },
            prefix: 'bar',
            csvk: {
                size: 'bar',
                color: 'bgc',
            },
            sub: {
                size: true,
                color: true,
                border: true,
            },
            extra: {
                stretch: 'row',
                tightness: 'normal',
                shape: 'sharp',
                effect: 'popout',
                innerContent: true,

                'active #.active': true,
                'disabled #.disabled': true,

                'hoverable #manual': true,
                'innerContentRows #manual': true,
            },
            switch: {
                root: {
                    '!disabled': {
                        'autoExtra.hoverable #class': '.hoverable',
                    }
                },

                innerContent: {
                    '!isEmptyIcon @iconbar #props': {
                        icon: '{{icon}}',
                        size: '{{size}}',
                    },
                    '!isEmptyBtn @btnbar #props': {
                        size: '{{size}}',
                        suffixBtn: '{{suffixBtn}}',
                    },

                    'cnt #props': {
                        size: '{{size}}',
                    },

                    innerContentRows: {
                        'true @root @cnt #class': '.flex-y-start',
                        'true @root #style': 'min-height: {{sizePropVal}};',
                        'true @cnt #style': 'height: 100%;',
                        'true @cnt #props': {
                            stretch: 'auto',
                        },
                    },
                },

                icon: {
                    '!isEmptyIcon #props': {
                        icon: '{{icon}}',
                        size: '{{size}}',
                    }
                },

                btn: {
                    '!isEmptyBtn #props': {
                        size: '{{autoSizeShift("s1","btn")}}',
                        icon: '{{suffixBtn}}',
                    },
                    'iconRight #style': 'margin-left: -0.8em; margin-right: 1.5em;',
                    '!iconRight #style': 'margin-right: -0.8em; margin-left: 1.5em;',
                },

                'autoAtom.flexY @cnt #class': '.flex-y-{{autoAtom.flexY}}',
            },
        },
    }},
    computed: {
        //判断是否 空 icon
        isEmptyIcon() {
            let icon = this.icon,
                is = this.$is;
            return !(is.string(icon) && icon !== '');
        },
        //判断是否显示 行末 按钮
        isEmptyBtn() {
            let icon = this.suffixBtn,
                is = this.$is;
            return !(is.string(icon) && icon !== '-empty-' && icon !== '');
        },
    },
    methods: {
        //响应 bar 根元素 click
        whenBarClick(ev) {
            if (this.disabled!==true) {
                return this.$emit('click');
            }
        },
    }
}
</script>

<style>

</style>