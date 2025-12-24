<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
    >
        <div class="layout-leftside flex-y flex-x-stretch bd-m bd-po-r">
            <div class="layout-logobar flex-x flex-x-center bg-backdrop-blur">
                <PRE@-icon-logo 
                    :icon="logo" 
                    size="large" 
                ></PRE@-icon-logo>
            </div>
            <div class="layout-menubar flex-1 flex-y flex-x-center flex-no-shrink scroll-y scroll-none">
                <slot name="menubar"></slot>
            </div>
            <div class="layout-usrbar flex-x flex-x-center bg-backdrop-blur bd-m bd-po-t pd-m pd-po-rl">
                <div class="layout-usr-avator mg-s mg-po-r"></div>
                <div class="flex-1"></div>
                <PRE@-button
                    icon="menu"
                    effect="popout"
                    stretch="square"
                ></PRE@-button>
                <PRE@-button
                    icon="power-settings-new"
                    effect="popout"
                    stretch="square"
                    type="danger"
                ></PRE@-button>
            </div>
        </div>
        <div class="layout-mainbody flex-1 flex-y flex-x-stretch flex-y-start">
            <div class="layout-navtab flex-x flex-x-center bg-backdrop-blur">
                <PRE@-tabbar 
                    v-model="$ui.testTabActive"
                    :tab-list="$ui.testTabList"
                    closable
                    position="top"
                    align="left"
                    enable-scroll
                    custom-style="height: 64px;"
                ></PRE@-tabbar>
            </div>
            <div class="layout-body flex-1 flex-y flex-x-start flex-y-start flex-no-shrink scroll-y scroll-none">
                <slot></slot>
            </div>
            <div class="layout-taskbar flex-x flex-x-end bd-m bd-po-t bg-backdrop-blur">
                <div class="taskbar-item">
                    <PRE@-icon
                        icon="chat"
                        shape="fill"
                        size="small"
                        type="success"
                    ></PRE@-icon>
                    <div class="taskitem-label">OMS 订货系统微信群</div>
                    <PRE@-button
                        icon="close"
                        size="tiny"
                        effect="popout"
                        shape="circle"
                        type="danger"
                    ></PRE@-button>
                </div>
                <div class="taskbar-item taskitem-active">
                    <PRE@-icon
                        icon="payment"
                        size="small"
                        type="primary"
                    ></PRE@-icon>
                    <div class="taskitem-label">支付宝付款后台查询</div>
                    <PRE@-button
                        icon="close"
                        size="tiny"
                        effect="popout"
                        shape="circle"
                        type="danger"
                    ></PRE@-button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base.js';

export default {
    mixins: [mixinBase],
    props: {
        //logo
        logo: {
            type: String,
            default: 'spf-pms-logo-line-light'
        }
    },
    data() {return {
        
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: ['__PRE__-layout','flex-x','flex-y-stretch','bd-m','bdc-m','bd-po-t','bgc-m'],
                }
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