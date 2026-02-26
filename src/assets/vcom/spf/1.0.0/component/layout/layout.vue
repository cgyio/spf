<template>
    <PRE@-block
        :stretch="stretch"
        :grow="grow"
        :scroll="scroll"
        v-bind="$attrs"
        :with-header="withNavbar"
        :header-params="areasCustomParams.navbar"
        :with-footer="withTaskbar"
        :footer-params="areasCustomParams.taskbar"
        inner-content
        inner-component=""
        :cnt-params="areasCustomParams.mainbody"
        :custom-class="styComputedClassStr.root"
        :custom-style="styComputedStyleStr.root"
    >
        <template v-if="withNavbar" v-slot:header="{styProps, headerParams, headerClass, headerStyle}">
            <div 
                v-if="logo!=='' && logo!=='-empty-'"
                :class="styComputedClassStr.logobar"
                :style="styComputedStyleStr.logobar"
            >
                <PRE@-icon-logo :icon="logo" size="large"></PRE@-icon-logo>
            </div>
            <slot 
                :name="navbar" 
                v-bind="{
                    styProps, 
                    navbarParams: headerParams, 
                    navbarClass: headerClass, 
                    navbarStyle: headerStyle
                }"
            ></slot>
        </template>
        <template v-if="withTaskbar" v-slot:footer="{styProps, footerParams, footerClass, footerStyle}">
            <slot 
                :name="navbar" 
                v-bind="{
                    styProps, 
                    navbarParams: footerParams, 
                    navbarClass: footerClass, 
                    navbarStyle: footerStyle
                }"
            ></slot>
        </template>

        <template v-slot="{styProps, cntParams, cntClass, cntStyle}">
            <PRE@-layout-x
                v-if="withMenubar"
                v-bind="cntParams"
                :col="menubarRight ? '*,'+menubarWidthPx : menubarWidthPx+',*'"
                :compact="menubarRight ? 1 : 0"
                :compact-to="menubarCompactWidthPx"
                :custom-class="cntClass"
                :custom-style="cntStyle"
                @col-compact="whenMenubarCompact"
            >
                <template v-slot:[menubarColSlotName]="{colIdx, layoutXer}">
                    <PRE@-block
                        grow
                        scroll="thin"
                        :with-header="!withNavbar && logo!=='' && logo!=='-empty-'"
                        :header-class="styComputedClassStr.logobar"
                        :header-style="styComputedStyleStr.logobar"
                        inner-content
                        inner-component=""
                    >
                        <template v-if="!withNavbar && logo!=='' && logo!=='-empty-'" #header>
                            <PRE@-icon-logo 
                                :icon="menubarCompacted ? ((compactLogo!=='' && compactLogo!=='-empty-') ? compactLogo : logo) : logo" 
                                :size="menubarCompacted ? 'medium' : 'large'"
                                :square="menubarCompacted"
                            ></PRE@-icon-logo>
                        </template>
                        <template #default>
                            <PRE@-menu
                                v-bind="areasCustomParams.menu"
                                :menus="menus"
                                accordion
                                :sub-lazyload-api="subMenuLazyloadApi"
                                :notice-api="menuNoticeApi"
                                :compact="menubarCompacted"
                                @self-uncompact="()=>{layoutXer.toggleCompact(colIdx)}"
                                @menu-change="whenMenuChange"
                                @menu-item-active="whenMenuItemActive"
                            ></PRE@-menu>
                        </template>
                    </PRE@-block>
                </template>
                <template v-slot:[mainbodyColSlotName]="{styProps, colIdx, layoutXer}">
                    <slot v-bind="{styProps, colIdx, layoutXer, layouter: $this}"></slot>
                </template>
            </PRE@-layout-x>
            <slot v-else v-bind="{styProps, layouter: $this}"></slot>
        </template>
    </PRE@-block>
</template>

<script>
import mixinBase from '../../mixin/base.js';
import mixinBaseParent from '../../mixin/base-parent.js';

export default {
    mixins: [mixinBase, mixinBaseParent],
    model: {
        prop: 'menus',
        event: 'menu-change',
    },
    props: {
        /**
         * for __PRE__-block 根组件的 样式参数
         */
        //stretch 容器水平延伸类型  row(默认)
        stretch: {
            type: String,
            default: 'row'
        },
        //是否占满整个纵向空间  默认 true
        grow: {
            type: Boolean,
            default: true
        },
        //是否在 grow == true 时启用 scroll-y  默认 '' 不启用
        scroll: {
            type: String,
            default: ''
        },

        /**
         * logo 图标名称   基于 __pre__-icon-logo 组件
         * !! 如果图标有 -light|-dark 两种模式，将会自动根据 主题明暗模式 切换
         */
        logo: {
            type: String,
            default: 'spf-logo-light'
        },
        //另外指定一个 menubar 折叠状态的 logo 正方形
        compactLogo: {
            type: String,
            default: 'spf-logo-app'
        },

        /**
         * 是否在顶部显示整行 navbar 导航栏
         * 默认 false  导航栏显示在 main-body 区域顶部，左侧为 menubar 其顶部显示 logo
         */
        withNavbar: {
            type: Boolean,
            default: false
        },
        //传入的 navbar 参数
        navbarParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        /**
         * 是否在底部显示完整的 taskbar 任务/状态栏
         * 默认 false 
         */
        withTaskbar: {
            type: Boolean,
            default: false
        },
        //传入的 taskbar 参数
        taskbarParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        /**
         * 是否在 左侧|右侧 显示 menubar
         * 默认 false 
         */
        withMenubar: {
            type: Boolean,
            default: false
        },
        //menubar 位于右侧  默认 false
        menubarRight: {
            type: Boolean,
            default: false
        },
        //menubar 宽度 只能是固定尺寸 256px|15% 形式
        menubarWidth: {
            type: String,
            default: '256px'
        },
        //menubar 横向折叠后的宽度 默认 cssvar.size.bar.m
        menubarCompactWidth: {
            type: String,
            default: ''
        },
        //menus 菜单|导航 数据源 v-model 双向绑定
        menus: {
            type: Array,
            default: () => [/*数据结构与 __PRE__-menu 组件 currentMenus 格式一致*/]
        },
        //懒加载 子菜单 数据的 api
        subMenuLazyloadApi: {
            type: String,
            default: ''
        },
        //菜单项 notice 数据源 api
        menuNoticeApi: {
            type: String,
            default: ''
        },
        //传入的 __PRE__-menu 组件的额外参数
        menuParams: {
            type: Object,
            default: () => {
                return {};
            }
        },

        //mainbody 主要区域的 组件参数  通常是 __PRE__-layout-x 组件
        mainbodyParams: {
            type: Object,
            default: () => {
                return {};
            }
        },

        /**
         * 是否显示边框
         * 默认 true 内部子组件将联动
         */
        border: {
            type: String,
            default: 'bd-m bd-po-t bdc-m'
        },
    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    //针对 根组件 __PRE__-block
                    root: '__PRE__-layout',
                    //navbar
                    navbar: '',
                    //taskbar
                    taskbar: '',
                    //logobar
                    logobar: 'flex-x flex-x-center flex-y-center',
                    //mainbody
                    mainbody: '',
                },
                style: {
                    root: '',
                    navbar: '',
                    taskbar: '',
                    logobar: 'position: relative; padding: 0px; overflow: hidden;', 
                    mainbody: '',
                },
            },
            prefix: 'layout',
            group: {
                //组开关
                withNavbar: true,
                withTaskbar: true,
                withMenubar: true,
            },
            sub: {
                //size: true,
                //color: true,
                //animate: 'disabled:false',
            },
            switch: {
                //启用 下列样式开关
                //针对 根组件 __PRE__-block
                border: '.{swv}',

                //针对 navbar
                'border:with-navbar@navbar #1': '.{swv}',
                'border:with-navbar@navbar #2': 'border-width: 0px 0px {swv@csv-val,size.bd.m} 0px;',

                //针对 taskbar
                'border:with-taskbar@taskbar #1': '.{swv}',
                'border:with-taskbar@taskbar #2': 'border-width: {swv@csv-val,size.bd.m} 0px 0px 0px;',

                //针对 logobar
                'logo:with-navbar@logobar': 'width: {swv@get,menubarWidthPx}; height: 100%;',
                'withMenubar:!with-navbar@logobar': 'height: 96px;',
                'menubarCompacted:!with-navbar@logobar #1': 'height: {swv@get,menubarCompactWidthPx};',
                'menubarCompacted:!with-navbar@logobar #2': '.mg-m mg-po-b',
            },
            csvKey: {
                size: 'bar',
                color: 'bgc',
            },
        },

        //通过 base-parent 处理子组件 props 透传
        subComps: {
            default: {
                //navbar  基于 __PRE__-bar 组件
                navbar: {
                    size: 'xxl'
                },
                //taskbar  基于 __PRE__-bar 组件
                taskbar: {
                    size: 'm'
                },
                //mainbody  基于 __PRE__-layout-x 组件
                mainbody: {
                    innerBorder: '{{border!==""}}[Boolean]',
                    //colExtraClass: this.menubarRight ? [null, 'bgc-white-m'] : ['bgc-white-m', null],
                },
                //menu  基于 __PRE__-menu 组件
                menu: {
                    //border: this.border!=='' ? `${this.border} bd-po-tb` : '',
                    //border: '{{border!=="" ? border+" bd-po-tb" : ""}}[String]'
                    '{{border!==""}}': {
                        border: 'foo-bar {{border}} bd-po-tb [String]',
                    },
                },
            },
        },

        /**
         * 内部区域 navbar|taskbar|mainbody|menubar ... 默认的 组件 params 
         * 将与 传入的这些组件的 navbar|taskbar|...Params 合并后 v-bind 传入组件
         * 传入的 ***Params 将覆盖此处定义的
         */
        areasDefaultParams: {
            //navbar  基于 __PRE__-bar 组件
            navbar: {
                size: 'xxl'
            },
            //taskbar  基于 __PRE__-bar 组件
            taskbar: {
                size: 'm'
            },
            //mainbody  基于 __PRE__-layout-x 组件
            mainbody: {
                innerBorder: this.border!=='',
                //colExtraClass: this.menubarRight ? [null, 'bgc-white-m'] : ['bgc-white-m', null],
            },
            //menu  基于 __PRE__-menu 组件
            menu: {
                border: this.border!=='' ? `${this.border} bd-po-tb` : '',
            },
        },

        //menubar compact 状态
        menubarCompacted: false,

        //!! mock
        testMenus: [
            {
                key: 'menu-foo', 
                icon: 'home', 
                label: '菜单 foo',
                sub: [
                    {
                        key: 'menu-foo-a',
                        label: '菜单 foo-a',
                        //icon: 'hearing',
                        cmd: () => console.log('菜单 foo-a is actived'),
                    },
                    {
                        key: 'menu-foo-b',
                        label: '菜单 foo-b',
                        cmd: () => this.testMenuCmd('foo-b'),
                    },
                    {
                        key: 'menu-foo-c',
                        label: '菜单 foo-c',
                        sub: [
                            {
                                key: 'menu-foo-c-a',
                                label: '菜单 foo-c-a'
                            },
                            {
                                key: 'menu-foo-c-b',
                                label: '菜单 foo-c-b',
                                params: {
                                    disabled: true
                                },
                            },
                            {
                                key: 'menu-foo-c-c',
                                label: '菜单 foo-c-c',
                                sub: [
                                    /*{
                                        key: 'menu-foo-c-c-a',
                                        label: '菜单 foo-c-c-a'
                                    },
                                    {
                                        key: 'menu-foo-c-c-b',
                                        label: '菜单 foo-c-c-b'
                                    },*/
                                ],
                                subLazyload: true,
                            },
                        ]
                    },
                    {
                        key: 'menu-foo-d',
                        label: '菜单 foo-d'
                    },
                ]
            },
            {
                key: 'menu-bar', 
                icon: 'outlined-flag', 
                label: '菜单 bar',
                sub: [
                    {
                        key: 'menu-bar-a',
                        label: '菜单 bar-a'
                    },
                    {
                        key: 'menu-bar-b',
                        label: '菜单 bar-b'
                    },
                ]
            },
            {
                key: 'menu-jaz', 
                icon: 'male', 
                label: '菜单 jaz',
                sub: [
                    {
                        key: 'menu-jaz-a',
                        label: '菜单 jaz-a'
                    },
                    {
                        key: 'menu-jaz-b',
                        label: '菜单 jaz-b'
                    },
                    {
                        key: 'menu-jaz-c',
                        label: '菜单 jaz-c',
                        sub: [
                            {
                                key: 'menu-jaz-c-a',
                                label: '菜单 jaz-c-a'
                            },
                            {
                                key: 'menu-jaz-c-b',
                                label: '菜单 jaz-c-b'
                            },
                            {
                                key: 'menu-jaz-c-c',
                                label: '菜单 jaz-c-c'
                            },
                        ]
                    },
                ]
            },
            {
                key: 'menu-tom', 
                icon: 'power', 
                label: '菜单 tom',
                sub: [
                    {
                        key: 'menu-tom-a',
                        label: '菜单 tom-a'
                    },
                    {
                        key: 'menu-tom-b',
                        label: '菜单 tom-b'
                    },
                ]
            },
        ],

    }},
    computed: {
        //withMenu 
        withMenu() {return this.withMenubar;},
        //将 % 形式的 menubar 宽度转为 px 形式
        menubarWidthPx() {
            let is = this.$is,
                isu = n => is.numeric(n),
                isa = a => is.array(a) && a.length>0,
                mw = this.menubarWidth,
                dft = '256px';
            if (!isu(mw)) return dft;
            let mwa = this.$ui.sizeValToArr(mw);
            if (!isa(mwa)) return dft;
            //传入了 纯数字
            if (mwa.length===1 || mwa[1]==='') return mwa[0]+'px';
            //传入了 100px 形式
            if (mwa[1]==='px') return mw;
            //传入了 25% 形式
            if (mwa[1]==='%' || mwa[1]==='vw') {
                let fw = window.innerWidth,
                    mwn = fw * (mwa[0]/100);
                return `${mwn}px`;
            }
            return dft;
        },
        menubarCompactWidthPx() {
            let is = this.$is,
                isu = n => is.numeric(n),
                isa = a => is.array(a) && a.length>0,
                mw = this.menubarCompactWidth,
                dft = this.$ui.cssvar.size.bar.m;
            if (!isu(mw)) return dft;
            let mwa = this.$ui.sizeValToArr(mw);
            if (!isa(mwa)) return dft;
            //传入了 纯数字
            if (mwa.length===1 || mwa[1]==='') return mwa[0]+'px';
            //传入了 100px 形式
            if (mwa[1]==='px') return mw;
            //传入了 25% 形式
            if (mwa[1]==='%' || mwa[1]==='vw') {
                let fw = window.innerWidth,
                    mwn = fw * (mwa[0]/100);
                return `${mwn}px`;
            }
            return dft;
        },
        //根据 menubarRight 参数，动态生成 menubar 插槽名称
        menubarColSlotName() {return this.menubarRight ? 'col-1' : 'col-0';},
        mainbodyColSlotName() {return this.menubarRight ? 'col-0' : 'col-1';},

        //layout 布局组件通用样式参数
        layoutVars() {
            let is = this.$is,
                isu = n => is.numeric(n),
                ui = this.$ui,
                csv = ui.cssvar,
                sv = (sk, csvk='bar') => ui.sizeVal(sk, csvk),
                cps = this.areasCustomParams,
                nps = cps.navbar,
                nh = nps.size || 'xxl',
                nhv = sv(nh),
                tps = cps.taskbar,
                th = tps.size || 'm',
                thv = sv(th),
                rtn = {};

            //计算得到的 navbar 高度
            rtn.navbarHeight = nhv;
            //计算得到的 taskbar 高度
            rtn.taskbarHeight = thv;
            //默认 menubar 菜单栏宽度 4*navbar 高度   默认 256px
            rtn.menubarWidth = this.menubarWidthPx;
            //默认 menubar 折叠后宽度  cssvar.size.bar.m
            rtn.menubarCompactWidth = this.menubarCompactWidthPx;
            //默认 logo 高度 navbar 高度 小一级
            rtn.logoHeight = sv(ui.sizeKeyShiftTo(nh, 's1'));

            return rtn;
        },

        /**
         * 合并 navbar|taskbar|mainbody|menubar 等区域的 默认参数 和 传入的 ***Params
         * 返回处理后的 params{} 通过 v-bind 传入对应的组件
         */
        areasCustomParams() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                dps = this.areasDefaultParams || {},
                rtn = {};
            if (!iso(dps)) return rtn;
            this.$each(dps, (dp, area) => {
                let apk = `${area}Params`,
                    aps = this[apk] || {};
                if (!iso(dp) && !iso(aps)) {
                    rtn[area] = {};
                    return true;
                }
                let dpi = !iso(dp) ? {} : Object.assign({}, dp);
                if (iso(aps)) dpi = Object.assign(dpi, aps);
                rtn[area] = dpi; 
            });
            return rtn;
        },

        //根据 withFoobar 系列参数，生成 mainbody 组件(__PRE__-layout-x) 的 col|compact|resize 等参数
        mainbodyCalcParams() {
            let is = this.$is,
                acps = this.areasCustomParams,
                mps = acps.mainbody,
                wm = this.withMenubar,
                rtn = {};
            

        },
    },
    watch: {
        
    },
    created() {
        console.log(this.getSubCompDefaultProps);
    },
    methods: {
        //响应 menubar compact 动作
        whenMenubarCompact(idx, compact) {
            if (!this.withMenubar) return false;
            let midx = this.menubarRight ? 1 : 0;
            if (midx!==idx) return false;
            this.menubarCompacted = compact;
        },
        //响应 menu-change 动作
        whenMenuChange(currentMenus) {
            return this.$emit('menu-change', currentMenus);
        },
        //响应 menu-item-active 事件
        whenMenuItemActive(keyChain=[]) {
            return this.$emit('menu-item-active', keyChain);
        },
    }
}
</script>

<style>

</style>