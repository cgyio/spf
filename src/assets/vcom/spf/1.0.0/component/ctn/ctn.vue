<template>
    <div
        :class="autoComputedStr.root.class"
        :style="autoComputedStr.root.style"
    >
        <template v-if="innerContent">
            <PRE@-bar
                v-if="withHeader"
                v-bind="autoComputed.header.props"
                :root-class="autoComputedStr.header.class"
                :root-style="autoComputedStr.header.style"
                @click="whenHeaderClick"
                v-slot="{autoParams: headerAutoParams}"
            ><slot name="header" v-bind="{autoParams, headerAutoParams}"></slot></PRE@-bar>
            <template v-if="withHeader || withFooter">
                <PRE@-ctn
                    v-if="innerComponent==='__PRE__-ctn'"
                    v-bind="autoComputed.cnt.props"
                    :root-class="autoComputedStr.cnt.class"
                    :root-style="autoComputedStr.cnt.style"
                    v-slot="{autoParams: cntAutoParams}"
                ><slot v-bind="{autoParams, cntAutoParams}"></slot></PRE@-ctn>
                <template v-else>
                    <component
                        v-if="innerComponent!==''"
                        :is="innerComponent"
                        v-bind="autoComputed.cnt.props"
                        :root-class="autoComputedStr.cnt.class"
                        :root-style="autoComputedStr.cnt.style"
                        v-slot="{autoParams: cntAutoParams}"
                    ><slot v-bind="{autoParams, cntAutoParams}"></slot></component>
                    <template v-else>
                        <slot 
                            v-if="!hideCnt" 
                            v-bind="{
                                autoParams, 
                                cntProps: autoComputed.cnt.props,
                                cntClass: autoComputed.cnt.class,
                                cntStyle: autoComputed.cnt.style
                            }"
                        ></slot>
                    </template>
                </template>
            </template>
            <template v-else>
                <slot 
                    v-if="!hideCnt" 
                    v-bind="{
                        autoParams, 
                        cntProps: autoComputed.cnt.props,
                        cntClass: autoComputed.cnt.class,
                        cntStyle: autoComputed.cnt.style
                    }"
                ></slot>
            </template>
            <PRE@-bar
                v-if="withFooter"
                v-bind="autoComputed.footer.props"
                :root-class="autoComputedStr.footer.class"
                :root-style="autoComputedStr.footer.style"
                v-slot="{autoParams: footerAutoParams}"
            ><slot name="footer" v-bind="{autoParams, footerAutoParams}"></slot></PRE@-bar>
        </template>
        <template v-else>
            <template v-if="withHeader">
                <slot 
                    name="header" 
                    v-bind="{
                        autoParams, 
                        headerProps: autoComputed.header.props,
                        headerClass: autoComputed.header.class,
                        headerStyle: autoComputed.header.style
                    }"
                ></slot>
            </template>
            <slot 
                v-if="!hideCnt" 
                v-bind="{
                    autoParams, 
                    cntProps: autoComputed.cnt.props,
                    cntClass: autoComputed.cnt.class,
                    cntStyle: autoComputed.cnt.style
                }"
            ></slot>
            <template v-if="withFooter">
                <slot 
                    name="footer" 
                    v-bind="{
                        autoParams, 
                        footerProps: autoComputed.footer.props,
                        footerClass: autoComputed.footer.class,
                        footerStyle: autoComputed.footer.style
                    }"
                ></slot>
            </template>
        </template>
    </div>
</template>

<script>
import mixinAutoProps from '../../mixin/auto-props';

export default {
    mixins: [mixinAutoProps],
    props: {
        /**
         * stretch 容器水平延伸类型
         * 可选值： auto | square | grow | row(默认)
         * !! 覆盖 base-style 中定义的默认值
         */
        stretch: {
            type: String,
            default: 'row'
        },

        /**
         * 是否占满整个纵向空间  默认不占满
         * !! 需要区分当前 ctn 组件被包裹在 flex-x 还是 flex-y 的容器内部
         *      被包裹在 flex-y 容器内部 则：   grow = true
         *      被包裹在 flex-x 容器内部 则：   growInBar = true
         */
        grow: {
            type: Boolean,
            default: false
        },
        growInBar: {
            type: Boolean,
            default: false
        },

        /**
         * 是否在 grow == true 时启用 scroll-y
         * 可选  ''|thin|bold
         */
        scroll: {
            type: String,
            default: ''
        },

        /**
         * header|footer 区域
         */
        withHeader: {
            type: Boolean,
            default: false
        },
        withFooter: {
            type: Boolean,
            default: false
        },
        
        //header 是否可点击，将影响 ctn-header-click 事件传递
        headerClickable: {
            type: Boolean,
            default: false
        },

        /**
         * 是否在 ctn 组件内容外部自动包裹对应的组件 bar|ctn
         */
        innerContent: {
            type: Boolean,
            default: false
        },
        //innerContent == true 时，可以指定内部的包裹在 cnt 内容外的组件名称，默认 __PRE__-ctn
        innerComponent: {
            type: String,
            default: '__PRE__-ctn'
        },

        //额外指定是否隐藏 cnt 内容，通常用于 折叠面板|菜单 等
        hideCnt: {
            type: Boolean,
            default: false
        },
        //在 innerContent == true 且 withHeader||withFooter 时，切换 hideCnt 时的动画效果，基于 animate.css
        /*toggleCntAnimate: {
            type: Array,
            default: () => [
                //enter 进入
                'animate__animated animate__fadeIn',
                //leave 离开
                'animate__animated animate__fadeOut',
            ]
        },*/

        //内容组件是否关联到 root 元素的 边框状态 默认关联
        borderBind: {
            type: Boolean,
            default: true
        },

    },
    data() {return {
        //覆盖 auto-props 系统参数
        auto: {
            element: {
                root: {
                    class: '__PRE__-ctn block flex-y flex-x-start flex-no-shrink',
                },
                header: {
                    class: '',
                },
                footer: {
                    class: '',
                    props: {
                        size: 'xl',
                    },
                },
                cnt: {
                    style: 'min-height: 0px;',
                },
            },
            prefix: 'block',
            csvk: {
                size: 'bar',
                color: 'bgc',
            },
            sub: {
                size: true,
                color: true,
                border: true,
                //animate: 'disabled:false',
            },
            extra: {
                //其他样式开关
                'grow #.flex-1': true,
                growInBar: true,
                stretch: 'row',
                tightness: 'normal',
                shape: 'sharp',
                innerContent: true,
                borderBind: 'true',
            },
            switch: {
                root: {
                    'growInBar #style': 'height: 100%;',
                    '?nemstr(rootScroll) #class': '.scroll-y scroll-{{rootScroll}}',
                    '?nemstr(rootScroll) #style': 'overflow: hidden auto;',
                },

                'withHeader @header #props': {
                    hoverable: '{{headerClickable}} [Boolean]',
                    size: '{{size}}',
                },

                'withFooter @footer': {
                    'borderBind && ?nemstr(border) #class': ['{{autoModBorder("*","t","*")}}'],
                    'true #props': {
                        size: '{{autoSizeShift("l2","bar")}}',
                    },
                },

                'cnt #style': {
                    height: '{{hideCnt ? "0px" : "unset"}}'
                },
                'cnt #props': {
                    size: '{{size}}',
                    '{{innerContent}}': {
                        grow: '{{rootGrow}} [Boolean]',
                        scroll: '{{innerCtnScroll}}',
                    },
                },
            },
        },
    }},
    computed: {
        //判断 root 元素是否被指定为 占满纵向空间
        rootGrow() {
            return this.grow || this.growInBar;
        },
        //是否在 根元素上挂 scroll-y 类，返回：''|thin|bold
        rootScroll() {
            let grow = this.rootGrow,
                scr = this.scroll,
                innc = this.innerContent;
            if (!grow || innc) return '';
            let wh = this.withHeader,
                wf = this.withFooter;
            if (wh || wf) return '';
            return scr;
        },
        //是否在 内部 ctn 组件上挂 scroll-y 类，返回：''|thin|bold
        innerCtnScroll() {
            let grow = this.rootGrow,
                scr = this.scroll,
                innc = this.innerContent;
            if (!grow || !innc) return '';
            let wh = this.withHeader,
                wf = this.withFooter;
            if (wh || wf) return scr;
            return '';
        },
    },
    methods: {
        //响应 header click 动作
        whenHeaderClick(evt) {
            if (this.withHeader && this.headerClickable) {
                return this.$emit('header-click');
            }
        },
    }
}
</script>

<style>

</style>