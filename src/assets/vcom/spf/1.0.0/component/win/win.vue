<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
    >
        <div class="win-titbar flex-x" v-drag-move:xy="$this">
            <PRE@-icon 
                v-if="icon !== '' && icon !== '-empty-'"
                :icon="icon" 
                :shape="iconShape"
                :spin="loading ? true : spin"
                :color="iconColor" 
                v-bind="iconParams"
            ></PRE@-icon>
            <div class="win-title flex-1 flex-x">新窗口</div>
            <div class="win-titctrl flex-x flex-x-end">
                <slot name="titctrl"></slot>
            </div>
            <template v-if="winType !== 'inside'">
                <PRE@-button
                    v-if="minimizable"
                    icon="arrow-downward"
                    effect="popout"
                    stretch="square"
                    no-gap
                    @click="winMinimize"
                ></PRE@-button>
                <PRE@-button
                    v-if="maximizable"
                    :icon="dcDisplay.maximized ? 'fullscreen-exit' : 'fullscreen'"
                    effect="popout"
                    stretch="square"
                    no-gap
                    @click="winMaximize"
                ></PRE@-button>
                <PRE@-button
                    v-if="closeable"
                    icon="close"
                    type="danger"
                    effect="popout"
                    stretch="square"
                    no-gap
                    @click="winClose"
                ></PRE@-button>
            </template>
        </div>
        <div v-if="tabList.length>1 && tabItemList.length>1" class="win-tabbar flex-x flex-x-center">
            <PRE@-tabbar
                :value="tab.active"
                :tab-list="tabItemList"
                :size="tabbarSize"
                v-bind="tabbarParams"
                @tab-active="whenTabActive"
            ></PRE@-tabbar>
        </div>
        <div class="win-body flex-1 flex-y flex-x-stretch">
            <template v-if="$is.plainObject(tabActiveItem) && !$is.empty(tabActiveItem)">
                <slot
                    v-if="tabActiveCompName === ''"
                    :name="'tab-'+tabActiveItem.key"
                    :tab="tabActiveItem"
                    :win="$this"
                ></slot>
                <component
                    v-else
                    :is="tabActiveCompName"
                    v-bind="tabActiveItem.compProps"
                ></component>
            </template>
            <slot></slot>
        </div>
        <div class="win-ctrlbar flex-x flex-x-end">
            <slot name="winctrl-extra"></slot>
            <div class="flex-1"></div>
            <slot name="winctrl"></slot>
            <PRE@-button
                v-if="cancelButton"
                :icon="cancelIcon"
                :label="cancelLabel"
                effect="popout"
                type="danger"
                @click="winCancel"
            ></PRE@-button>
            <PRE@-button
                v-if="confirmButton"
                :icon="confirmIcon"
                :label="confirmLabel"
                effect="fill"
                type="primary"
                @click="winConfirm"
            ></PRE@-button>
        </div>
        <div v-if="dcDisplay.loading" class="win-loading flex-x flex-x-center flex-y-center">
            <PRE@-icon icon="spinner-spin" size="huge" type="primary" spin></PRE@-icon>
        </div>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base.js';
import mixinBaseDynamic from '../../mixin/base-dynamic.js';

export default {
    mixins: [mixinBase, mixinBaseDynamic],
    props: {
        /**
         * 此窗口的元素据
         */
        /**
         * 窗口唯一键名
         * 所有通过 $ui.openWin() 方法打开的窗口，必须指定唯一键名
         * 此键名将用于在 $ui.winList 对象中标记此窗口的实例
         * 手动在页面中使用窗口组件，作为容器包裹自定义内容的 不需要传入此参数
         */
        winKey: {
            type: String,
            default: ''
        },
        //窗口图标
        icon: {
            type: String,
            default: 'desktop-mac'
        },
        iconColor: {
            type: String,
            default: 'primary'
        },
        iconShape: {
            type: String,
            default: 'round'
        },
        spin: {
            type: [Boolean, String],
            default: false
        },
        iconParams: {
            type: Object,
            default() {return {};}
        },

        //窗口标题
        title: {
            type: String,
            default: '窗口'
        },
        //窗口是否可以 最大化|最小化
        maximizable: {
            type: Boolean,
            default: false,
        },
        minimizable: {
            type: Boolean,
            default: false,
        },
        closeable: {
            type: Boolean,
            default: false,
        },

        //窗口的 loading 状态
        loading: {
            type: Boolean,
            default: false,
        },

        /**
         * 是否启用窗口元素
         */
        //tabbar
        /*tabbar: {
            type: Boolean,
            default: false
        },*/
        //ctrlbar
        ctrlbar: {
            type: Boolean,
            default: true
        },
        //cancel|confirmButton
        cancelButton: {
            type: Boolean,
            default: true
        },
        confirmButton: {
            type: Boolean,
            default: true
        },
        cancelIcon: {
            type: String,
            default: 'close'
        },
        confirmIcon: {
            type: String,
            default: 'check'
        },
        cancelLabel: {
            type: String,
            default: '取消'
        },
        confirmLabel: {
            type: String,
            default: '确定'
        },
        //cancel|confirm 回调方法
        /*onCancel: {
            type: Function,
            default: ()=>{
                return () => {}
            }
        },*/
        
        /**
         * 指定此窗口的类型
         * 可选：inside(在页面内部) | popout(普通弹出) | modal(模态弹出)
         * 默认 popout
         */
        winType: {
            type: String,
            default: 'popout',
        },

        //此窗口是否允许拖拽移动，只有 winType != inside 时起效
        moveable: {
            type: Boolean,
            default: false
        },

        //窗口是否启用 border 边框
        border: {
            type: Boolean,
            default: true
        },
        //窗口是否启用 sharp 直角边框
        sharp: {
            type: Boolean,
            default: false
        },

        //窗口是否启用 shadow 可选 false|true 或 normal|thin|bold
        shadow: {
            type: [Boolean, String],
            default: false
        },

        //tightness 窗口内容紧凑程度 可选 loose|normal|tight
        tightness: {
            type: String,
            default: 'normal'
        },

        //hoverable 悬停阴影
        hoverable: {
            type: Boolean,
            default: false,
        },

        //窗口 开启|关闭|minimize|maximize 动画类型 animate__*** 类名
        animateType: {
            type: String,
            default: 'zoomIn'
        },

        /**
         * tabbar 参数
         */
        //tabbar-size 可选：mini|small|normal|medium
        tabbarSize: {
            type: String,
            default: 'small'
        },
        //其他额外的 tabbar 组件参数
        tabbarParams: {
            type: Object,
            default() {return {}},
        },
        //tabitem-list [{key:, label:, icon:, ...}, ...]
        tabList: {
            type: Array,
            default: () => []
        },
        //tabitem-active 当前激活的 tab.key
        tabActive: {
            type: String,
            default: ''
        },

        /**
         * 可直接传入窗口内容 html
         */
        contentHtml: {
            type: String,
            default: '',
        },

        //win 窗口的 confirmed|canceled 外部标记
        winConfirmed: {
            type: Boolean,
            default: false,
        },
        winCanceled: {
            type: Boolean,
            default: false,
        },
        


    },
    data() {return {
            
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: ['__PRE__-win', 'flex-y', 'flex-x-stretch'],
                }
            },
            prefix: 'win',
            sub: {
                animate: 'enabled'
            },
            switch: {
                //启用 下列样式开关
                winType: 'type',
                border: true,
                sharp: true,
                shadow: true,
                hoverable: true,
                tightness: true,
                'dcDisplay.show': 'show',
                'dcDisplay.minimized': 'minimized',
                'dcDisplay.maximized': 'maximized',
            },
            csvKey: {
                size: 'block',
                color: 'fc',
            },
        },

        //窗口的 display 显示状态 覆盖 base-dynamic.mixin 中参数
        dcDisplay: {
            //显示|隐藏 默认隐藏
            show: false,
            //maximize|minimize
            maximized: false,
            minimized: false,
            //loading 状态
            loading: false,

            //动画效果
            ani: {
                show: 'zoomIn',
                hide: 'zoomOut'
            }
        },

        /**
         * win-tab 标签页相关
         */
        tab: {
            //标准 tab-item 参数格式
            dftItem: {
                //必须的参数
                key: '',
                label: '',
                //可选的参数
                icon: '',
                //如果此 tab 指向某个组件
                component: '',
                compProps: {},
            },

            //当前激活的 tab-item key 未初始化时为 null
            active: null,
        },


    }},
    computed: {
        //判断此窗口是否可以 minimize|maximize|close
        canMinimize() {
            return this.winType !== 'inside' && this.minimizable;
        },
        canMaximize() {
            return this.winType !== 'inside' && this.maximizable;
        },
        canClose() {
            return this.winType !== 'inside' && this.closeable;
        },

        //判断此窗口是否可以 drag-move
        dragMoveable() {
            return this.winType !== 'inside' && this.moveable;
        },

        //此窗口当前的 position {w:, h:, l:, t:,}
        winPosition() {
            
        },

        //使用标准的 tab-item 数据格式化传入是 tab-list
        tabItemList() {
            let is = this.$is,
                tbl = this.tabList,
                dti = this.tab.dftItem;
            if (!is.array(tbl) || tbl.length<=0) return [];
            let tbil = [];
            this.$each(tbl, (v,k) => {
                if (!is.plainObject(v) || is.empty(v)) return true;
                if (!is.defined(v.key) || !is.defined(v.label)) return true;
                tbil.push(this.$extend({}, dti, v));
            });
            return tbil;
        },
        //获取当前激活的 tab-item 参数
        tabActiveItem() {
            let is = this.$is,
                ta = this.tab.active,
                tbl = this.tabItemList;
            if (!is.string(ta) || ta==='') return {};
            let ti = {};
            this.$each(tbl, (v,k)=>{
                if (is.plainObject(v) && is.defined(v.key) && v.key===ta) {
                    ti = this.$extend({}, v);
                    return false;
                }
            });
            return ti;
        },
        //如果当前激活的 tab-item 指向某个组件，则返回此组件的真实组件名
        tabActiveCompName() {
            let is = this.$is,
                tai = this.tabActiveItem,
                tcn = is.defined(tai.component) ? tai.component : null;
            if (!is.string(tcn) || tcn==='') return '';
            let vcn = this.$vcn(tcn);
            if (is.null(vcn)) return '';
            return vcn;
        },

        //判断是否直接传入了有效的 contentHtml
        hasContentHtml() {
            let is = this.$is,
                html = this.contentHtml;
            if (!is.string(html) || html==='') return false;

        },
    },
    watch: {
        //外部指定的 loading 状态
        loading(nv, ov) {
            this.dcDisplay.loading = this.loading;
        },
        //指定了外部 tab-active
        tabActive: {
            handler(nv, ov) {
                let is= this.$is,
                    ta = this.tabActive,
                    tbl = this.tabItemList,
                    tbk = (is.array(tbl) && tbl.length>0) ? tbl[0].key : '';

                if (!is.string(ta) || ta==='' || !this.isLegalTabKey(ta)) {
                    this.tab.active = tbk;
                } else {
                    this.tab.active = ta;
                }

            },
            immediate: true,
        },
    },
    created() {
        
    },
    methods: {
        /**-
         * win-minimize|maximize|close
         */
        winMinimize(event) {
            if (this.canMinimize || this.dcDisplay.minimized || this.winKey==='') return false;
            this.dcDisplay.minimized = true;
            return this.$ui.minimizeWin(this.winKey).then(()=>{
                return this.$emit('minimize', this.winKey);
            });
        },
        winMaximize(event) {
            if (this.canMaximize || this.winKey==='') return false;
            this.dcDisplay.maximized = !this.dcDisplay.maximized;
            this.dcDisplay.minimized = false;
            return this.$ui.maximizeWin(this.winKey).then(()=>{
                return this.$emit('maximize', this.winKey);
            });
        },
        winClose(event) {
            if (this.canClose || this.winKey==='') return false;
            return this.$ui.closeWin(this.winKey).then(()=>{
                //return this.$emit('close', this.winKey);
            });
        },
        winCancel(event) {

        },
        winConfirm(event) {

        },

        //win-loading 状态切换
        winLoading(loading=null) {
            let is = this.$is;
            if (!is.boolean(loading)) {
                //不指定 loading 状态，则 toggle
                this.dcDisplay.loading = !this.dcDisplay.loading;
            } else {
                //直接指定 loading 状态
                this.dcDisplay.loading = loading;
            }
            //事件
            return this.$emit('loading', this.dcDisplay.loading);
        },



        /**
         * win-tab 标签页相关
         */
        //判断给定的 tab-key 存在且合法
        isLegalTabKey(key) {
            let is = this.$is,
                tbil = this.tabItemList;
            if (!is.array(tbil) || tbil.length<=0) return false;
            let tks = tbil.map(i=>i.key);
            return is.string(key) && key!=='' && is.array(tks) && tks.includes(key);
        },
        //tab-active 动作
        whenTabActive(key) {
            if (this.isLegalTabKey(key)) {
                this.tab.active = key;
                /*this.$wait(100).then(()=>{
                    let tcn = this.tabActiveCompName;
                    if (tcn!=='') {
                        //当前激活的 tab 指向有效的 component
                        if (!Vue.compLoaded(tcn)) {

                        }
                    }
                });*/
                return this.$emit('tab-active', key);
            }
            return false;
        },
        
    }
}
</script>

<style>

</style>