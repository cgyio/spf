<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
    >
        <template v-if="innerContent">
            <PRE@-bar
                v-if="withHeader"
                :hoverable="headerClickable"
                v-bind="headerCustomParams"
                :custom-class="styComputedClassStr.header"
                :custom-style="styComputedStyleStr.header"
                @click="whenHeaderClick"
                v-slot="{styProps: headerStyProps}"
            ><slot name="header" v-bind="{styProps: headerStyProps}"></slot></PRE@-bar>
            <template v-if="withHeader || withFooter">
                <!--<transition
                    name="__PRE__-block-inner-trans"
                    :enter-active-class="toggleCntAnimate[0]"
                    :leave-active-class="toggleCntAnimate[1]"
                        v-if="!hideCnt"
                >-->
                    <PRE@-block
                        v-if="innerComponent==='__PRE__-block'"
                        :grow="rootGrow"
                        :scroll="innerBlockScroll"
                        :size="styProps.size"
                        v-bind="cntCustomParams"
                        :custom-class="styComputedClassStr.cnt"
                        :custom-style="styComputedStyleStr.cnt"
                        v-slot="{styProps: cntStyProps}"
                    ><slot v-bind="{styProps: cntStyProps}"></slot></PRE@-block>
                    <template v-else>
                        <component
                            v-if="innerComponent!==''"
                            :is="innerComponent"
                            v-bind="cntCustomParams"
                            :custom-class="styComputedClassStr.cnt"
                            :custom-style="styComputedStyleStr.cnt"
                            v-slot="{styProps: cntStyProps}"
                        ><slot v-bind="{styProps: cntStyProps}"></slot></component>
                        <template v-else>
                            <slot 
                                v-if="!hideCnt" 
                                v-bind="{
                                    styProps, 
                                    cntParams: cntCustomParams,
                                    cntClass: styComputedClass.cnt,
                                    cntStyle: styComputedStyle.cnt
                                }"
                            ></slot>
                        </template>
                    </template>
                <!--</transition>-->
            </template>
            <template v-else>
                <slot 
                    v-if="!hideCnt" 
                    v-bind="{
                        styProps, 
                        cntParams: cntCustomParams,
                        cntClass: styComputedClass.cnt,
                        cntStyle: styComputedStyle.cnt
                    }"
                ></slot>
            </template>
            <PRE@-bar
                v-if="withFooter"
                v-bind="footerCustomParams"
                :custom-class="styComputedClassStr.footer"
                :custom-style="styComputedStyleStr.footer"
                v-slot="{styProps: footerStyProps}"
            ><slot name="footer" v-bind="{styProps: footerStyProps}"></slot></PRE@-bar>
        </template>
        <template v-else>
            <template v-if="withHeader">
                <slot 
                    name="header" 
                    v-bind="{
                        styProps, 
                        headerParams: headerCustomParams, 
                        headerClass: styComputedClass.header, 
                        headerStyle: styComputedStyle.header
                    }"
                ></slot>
            </template>
            <slot 
                v-if="!hideCnt" 
                v-bind="{
                    styProps, 
                    cntParams: cntCustomParams,
                    cntClass: styComputedClass.cnt,
                    cntStyle: styComputedStyle.cnt
                }"
            ></slot>
            <template v-if="withFooter">
                <slot 
                    name="footer" 
                    v-bind="{
                        styProps, 
                        footerParams: footerCustomParams, 
                        footerClass: styComputedClass.footer, 
                        footerStyle: styComputedStyle.footer
                    }"
                ></slot>
            </template>
        </template>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base.js';

export default {
    mixins: [mixinBase],
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
         * !! 需要区分当前 block 组件被包裹在 flex-x 还是 flex-y 的容器内部
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
        //header 组件（通常是 bar）额外参数
        headerParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        //header 额外的 class|style
        headerClass: {
            type: [String,Array],
            default: ''
        },
        headerStyle: {
            type: [String,Object],
            default: ''
        },
        //header 是否可点击，将影响 block-header-click 事件传递
        headerClickable: {
            type: Boolean,
            default: false
        },
        //footer 组件（通常是 bar）额外参数
        footerParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        //footer 额外的 class|style
        footerClass: {
            type: [String,Array],
            default: ''
        },
        footerStyle: {
            type: [String,Object],
            default: ''
        },
        //可额外指定 footer 的 size 默认 = styProps.size
        /*footerSize: {
            type: [String, Number],
            default: ''
        },*/

        /**
         * 是否在 block 组件内容外部自动包裹对应的组件 bar|block
         */
        innerContent: {
            type: Boolean,
            default: false
        },
        //innerContent == true 时，可以指定内部的包裹在 cnt 内容外的组件名称，默认 __PRE__-block
        innerComponent: {
            type: String,
            default: '__PRE__-block'
        },
        //cnt 元素组件（通常是 block 组件）额外的参数
        cntParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        //cnt 额外的 class|style
        cntClass: {
            type: [String,Array],
            default: ''
        },
        cntStyle: {
            type: [String,Object],
            default: ''
        },

        //额外指定是否隐藏 cnt 内容，通常用于 折叠面板|菜单 等
        hideCnt: {
            type: Boolean,
            default: false
        },
        //在 innerContent == true 且 withHeader||withFooter 时，切换 hideCnt 时的动画效果，基于 animate.css
        toggleCntAnimate: {
            type: Array,
            default: () => [
                //enter 进入
                'animate__animated animate__fadeIn',
                //leave 离开
                'animate__animated animate__fadeOut',
            ]
        },

        //内容组件是否关联到 root 元素的 边框状态
        borderBind: {
            type: Boolean,
            default: false
        },


        /**
         * 是否在默认插槽外自动包裹 PRE@-block 组件
         * 仅在 withHeader || withFooter 时生效
         * 默认 false 由用户自行向默认插槽插入 需要的组件
         */
        //innerBlock: {
        //    type: Boolean,
        //    default: false
        //},

    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: '__PRE__-block block flex-y flex-x-start flex-no-shrink',
                    cnt: '',
                    header: '',
                    footer: ''
                },
                style: {
                    cnt: 'min-height: 0px;',
                    header: '',
                    footer: ''
                }
            },
            prefix: 'block',
            group: {
                //新增组开关
                innerContent: true,
                innerBorder: true,
            },
            sub: {
                size: true,
                color: true,
                //animate: 'disabled:false',
            },
            switch: {
                //启用 下列样式开关
                //effect:     '.bar-effect effect-{swv}',
                stretch:    '.stretch-{swv}',
                tightness:  '.block-tightness tightness-{swv}',
                shape:      '.block-shape shape-{swv}',
                //'hoverable:disabled': '.hoverable',
                //active:     '.active',
                grow:       '.flex-1',
                growInBar:  'height: 100%;',
                //根元素上挂载 scroll-y
                rootScroll: '.scroll-y scroll-{swv}',

                //针对 cnt 和 footer 元素
                'bd:inner-border@cnt@footer': '.bd-{swv} bd-po-t',
                'bdPo:inner-border@cnt@footer': '.bd-m bd-po-t',
                'bdc:inner-border@cnt@footer': '.bd-m bd-po-t bdc-{swv}',
                //'grow:inner-content@cnt': 'height: {swv@get,cntHeight};',
                'hideCnt@cnt': 'height: {swv@if,0px,unset};',
            },
            csvKey: {
                size: 'bar',
                color: 'bgc',
            },
        },

        //内部 header|footer|cnt 子组件的默认 params
        headerDefaultParams: {
            size: this.size,
        },
        footerDefaultParams: {
            size: 'xl',
        },
        cntDefaultParams: {

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
        //是否在 内部 block 组件上挂 scroll-y 类，返回：''|thin|bold
        innerBlockScroll() {
            let grow = this.rootGrow,
                scr = this.scroll,
                innc = this.innerContent;
            if (!grow || !innc) return '';
            let wh = this.withHeader,
                wf = this.withFooter;
            if (wh || wf) return scr;
            return '';
        },

        /**
         * 处理 header|footer|cnt-params 合并默认参数 与 外部传入的，通过 v-bind 传入内部子组件
         */
        headerCustomParams() {
            return this.$extend({}, this.headerDefaultParams, this.headerParams);
        },
        footerCustomParams() {
            return this.$extend({}, this.footerDefaultParams, this.footerParams);
        },
        cntCustomParams() {
            return this.$extend({}, this.cntDefaultParams, this.cntParams);
        },

        //占满纵向空间时 自动计算 cnt 元素的 height
        //!! 为 cnt 元素增加 min-height: 0px; height: unset; 即可触发子组件的 scroll  因此此处不需要计算高度
        __cntHeight() {
            let is = this.$is,
                isu = n => is.numeric(n),
                el = this.$el,
                grow = this.grow,
                hc = this.hideCnt;
            if (!grow || hc) return '0px';
            //根元素还未渲染完成时
            if (!is.elm(el) || !is.defined(el.offsetHeight) || el.offsetHeight<=0) return 'unset';
            let h = el.offsetHeight,    //取得当前组件的 总高
                wh = this.withHeader,
                wf = this.withFooter,
                ui = this.$ui,
                csv = ui.cssvar,
                ch = h+'px';
            //总高度 依次减去 header|footer 高度
            this.$each(['header','footer'], (elm,i) => {
                let wk = `with${elm.ucfirst()}`;
                //跳过未启用的 header|footer
                if (!is.defined(this[wk]) || this[wk]!==true) return true;
                let ps = this[`${elm}Params`] || {},
                    sz = is.defined(ps.size) ? ps.size : this.size,
                    szv = ui.sizeVal(sz, 'bar');
                if (!isu(szv)) return true;
                //总高减去 header|footer
                ch = ui.sizeValSub(ch, szv);
            });
            return ch;
        },

        //判断内部元素是否需要自动生成 边框参数
        innerBorder() {
            return this.innerContent && this.borderBind;
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