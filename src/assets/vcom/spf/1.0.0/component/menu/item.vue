<template>
    <PRE@-block
        v-bind="$attrs"
        inner-content
        with-header
        :header-params="menuHeaderParams"
        :header-style="styComputedStyleStr.header"
        header-clickable
        :hide-cnt="noSub || collapse"
        @header-click="whenMenuHeaderClick"
        :cnt-style="styComputedStyleStr.cnt"
        :custom-class="styComputedClassStr.item"
        :custom-style="styComputedStyleStr.item"
    >
        <template #header>
            <template v-if="!compact">
                <label :style="styComputedStyleStr.label">{{label}}</label>
                <span class="flex-1"></span>
                <!--菜单项 notice-->
                <template v-if="menuNoticeShow">
                    <div
                        v-if="menuNoticeType==='dot'"
                        :class="styComputedClassStr.notice"
                        :style="styComputedStyleStr.notice"
                    ></div>
                    <div
                        v-if="menuNoticeType==='number'"
                        :class="styComputedClassStr.notice"
                        :style="styComputedStyleStr.notice"
                    >{{notice.showValue}}</div>
                    <PRE@-icon
                        v-if="menuNoticeType==='icon'"
                        :icon="notice.showValue"
                        color="danger"
                        :class="styComputedClassStr.notice"
                        :style="styComputedStyleStr.notice"
                    ></PRE@-icon>
                </template>
                <!--折叠箭头|lazyLoading 标记-->
                <PRE@-icon
                    v-if="!noSub"
                    :icon="collapse ? 'keyboard-arrow-down' : 'keyboard-arrow-up'"
                    size="small"
                    :spin="lazyload && lazyLoading"
                    :custom-class="styComputedClassStr.arrow"
                    :custom-style="styComputedStyleStr.arrow"
                ></PRE@-icon>
            </template>
        </template>
        <template v-if="!noSub && sub.length>0 && !collapse" #default>
            <template v-for="(menui, midx) of sub">
                <PRE@-menu-item
                    v-if="!menui.params.hide"
                    :key="'__PRE__-menu-item-'+idx+'-sub-'+midx"
                    :size="size"
                    :color="color"
                    :background="background"
                    :border="border"
                    :shape="shape"
                    :label="menui.label"
                    :icon="menui.icon"
                    :sub="menui.sub"
                    v-bind="menui.params"
                    :menu-comp="menuComp"
                    @menu-item-toggle-collapse="whenMenuSubItemToggleCollapse"
                    @menu-item-click="whenMenuSubItemClick"
                ></PRE@-menu-item>
            </template>
        </template>
    </PRE@-block>
</template>

<script>
import mixinBase from '../../mixin/base.js';

export default {
    mixins: [mixinBase],
    props: {
        /**
         * 标记通过 pre-menu 组件默认 slot 手动插入的 pre-menu-item 组件
         */
        manual: {
            type: Boolean,
            default: false
        },

        /**
         * 菜单项 参数
         */
        //icon
        icon: {
            type: String,
            default: ''
        },
        //label
        label: {
            type: String,
            default: ''
        },
        //menu-key 菜单项键名，在 menusOpts{} 中的键名 foo-bar 格式
        menuKey: {
            type: String,
            default: ''
        },
        //menu-key-chain 菜单项 键名链  [foo, bar, jaz-tom] 形式
        keyChain: {
            type: Array,
            default: () => []
        },
        //idx 当前菜单项在父菜单项的 sub 列表中的 idx 序号
        idx: {
            type: Number,
            default: -1
        },
        //menu-idx-chain 菜单项序号链
        idxChain: {
            type: Array,
            default: () => []
        },

        //菜单项 样式
        size: {
            type: String,
            default: 'normal'
        },
        color: {
            type: String,
            default: 'primary'
        },
        background: {
            type: String,
            default: 'bgc'
        },
        border: {
            type: String,
            default: ''
        },
        //菜单项 shape 可选 sharp(默认)|round|pill
        shape: {
            type: String,
            default: 'sharp'
        },

        //header 参数
        //header params
        headerParams: {
            type: Object,
            default: () => {
                return {};
            }
        },
        //header label styles
        /*headerLabelActiveStyle: {
            type: String,
            default: 'font-weight: bold;'
        },
        headerLabelExpandStyle: {
            type: String,
            default: 'font-weight: bold;'
        },*/

        //菜单项的当前状态
        //collapse 折叠
        collapse: {
            type: Boolean,
            default: false
        },
        //disabled
        disabled: {
            type: Boolean,
            default: false
        },
        //active
        active: {
            type: Boolean,
            default: false
        },
        //子菜单加载中
        lazyLoading: {
            type: Boolean,
            default: false
        },

        //子菜单
        sub: {
            type: Array,
            default: () => []
        },
        //子菜单是否懒加载
        lazyload: {
            type: Boolean,
            default: false
        },

        //菜单项的 notice 通知
        notice: {
            type: Object,
            default: () => {
                return {};
            }
        },

        //传入 __PRE__-menu 组件实例
        menuComp: {
            type: Object,
            default: () => {
                return {};
            }
        },

        //菜单项是否处于 横向折叠状态
        compact: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    //不使用 root 根元素
                    root: '',
                    //菜单项整体
                    item: '__PRE__-menu',
                    //菜单项 header 
                    header: '',
                    //菜单项 header label 
                    label: '',
                    //菜单项 通知 
                    notice: '__PRE__-menu-notice',
                    //菜单项 箭头标记
                    arrow: '',
                    //子菜单容器 
                    cnt: '',
                },
                style: {
                    //菜单项整体
                    item: '',
                    //菜单项 header 
                    header: '',
                    //菜单项 header label 
                    label: '',
                    //菜单项 通知 
                    notice: '',
                    //菜单项 箭头标记
                    arrow: 'margin-right: -0.5em;',
                    //子菜单容器 
                    cnt: 'min-height: 0px;',
                }
            },
            prefix: 'menu',
            group: {
                //新增组开关
                noSub: true,
                collapse: true,
                lazyLoading: true,
                menuNoticeShow: true,
                //active: true,
                //横向折叠状态，仅一级菜单可能为 true 
                compact: true,
            },
            sub: {
                //size: true,
                //color: true,
                //animate: 'disabled:false',
                //仅启用 switch 子系统
            },
            switch: {
                //启用 下列样式开关

                //针对 item 菜单项整体
                'border:collapse@item':     '.menu-with-bd menu-collapse',
                'border:!collapse@item':    '.{swv} menu-with-bd menu-expand',
                //展开子菜单 或 有子菜单被 active 时整体增加  背景色
                '!collapse:!no-sub@item': 'background-color: {swv@get,menuColors.itemExpandBgc};',
                'active:!no-sub@item': 'background-color: {swv@get,menuColors.itemExpandBgc};',

                //针对 菜单项 header
                'shape@header': '.bar-shape shape-{swv}',
                'menuExtraPadding@header': 'padding-left: {swv};',
                '!collapse:!no-sub@header': 'background-color: {swv@get,menuColors.headerExpandBgc};',
                //横向折叠状态，仅针对一级菜单
                'compact@header': 'padding: 0; justify-content: center;',

                //针对 菜单项的 label
                'active:no-sub@label': 'font-weight: bold;',
                'active:!no-sub@label': 'font-weight: bold; color: {swv@get,menuColors.labelExpandColor};',
                //菜单失效时 label 文字样式
                'disabled@label': 'color: {swv@get,menuColors.labelDisabledColor};',
                '!collapse:!no-sub@label': 'font-weight: bold; color: {swv@get,menuColors.labelExpandColor};',

                //菜单项 notice 通知
                'menuNoticeType:menu-notice-show@notice': '.notice-{swv}',
                'menuNoticeShow:no-sub@notice': 'margin-right: -4px;',

                //针对 菜单项的 箭头
                'collapse:!lazy-loading@arrow': '.icon-l2',
                'lazyLoading:!no-sub@arrow': 'color: {swv@get,menuColors.labelExpandColor};',
                'active:!no-sub@arrow': 'color: {swv@get,menuColors.labelExpandColor};',
                '!collapse:!no-sub@arrow': 'color: {swv@get,menuColors.labelExpandColor};',

                //针对 子菜单容器
                'collapse:!no-sub@cnt': {
                    //展开子菜单时，自动计算 子菜单高度
                    height: '{swv@if,0px,(get,menuSubHeight)}',
                },
            },
            csvKey: {
                //size: 'bar',
                //color: 'bgc',
            },
        },
    }},
    computed: {
        //当前菜单项是否不含子菜单
        noSub() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0;
            return !isa(this.sub) && !this.lazyload;
        },
        //当前菜单项在整个菜单中的深度层级  从 0 开始
        menuDeepLevel() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                idxc = this.idxChain || [];
            if (!isa(idxc)) return -1;
            return idxc.length - 1;
        },
        //根据当前菜单项的深度层级，计算额外的 padding-left 返回 '48px' 或 ''
        menuExtraPadding() {
            let is = this.$is,
                isu = n => is.numeric(n),
                lvl = this.menuDeepLevel;
            if (lvl<=0) return '';
            //一级菜单 且 compact == true 时，不计算
            if (lvl===0 && this.compact===true) return '';
            //从 styProps 中获取相应的尺寸值 并计算
            let ui = this.$ui,
                sv = ui.sizeVal,
                sz = this.size,
                isz = sv(sz, 'icon'),
                pd = sv(sz, 'pd');
            if (!isu(isz) || !isu(pd)) return '';
            let add = ui.sizeValAdd,
                mul = ui.sizeValMul,
                div = ui.sizeValDiv,
                rtn = add(pd, mul(add(isz, div(pd, 2)), lvl));
            if (!isu(rtn)) return '';
            return rtn;
        },
        //当前菜单项的 子菜单高度
        menuSubHeight() {
            if (this.noSub || this.collapse) return '0px';
            return this.menuComp.calcMenuHeightByIdxChain(this.idxChain, this.size);
        },

        //菜单项 notice 通知
        menuNoticeShow() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isd = d => is.defined(d),
                nt = this.notice;
            if (iso(nt) && isd(nt.show) && isd(nt.type) && isd(nt.showValue)) return nt.show;
            return false;
        },
        menuNoticeType() {
            return this.menuNoticeShow ? this.notice.type : '';
        },

        //菜单项 header params
        menuHeaderParams() {
            let is = this.$is,
                color = this.color,
                size = this.size,
                icon = this.icon,
                hps = this.headerParams,
                rtn = this.$extend({
                    icon,
                    //默认 菜单项图表样式
                    iconParams: {
                        color,
                        size
                    },
                    //默认菜单项 悬停|选中 背景颜色 primary
                    color,
                }, hps);
            //处理 iconParams.color == random 随机图标颜色 的情况
            if (rtn.iconParams.color === 'random') {
                let clrs = this.$ui.cssvar.extra.color.types,
                    rnd = Math.floor(Math.random() * clrs.length);
                rtn.iconParams.color = clrs[rnd];
            }
            //处理 展开子菜单 或 有子菜单处于 active 时 icon 的额外样式
            if (!this.noSub && (!this.collapse || this.active)) {
                rtn = this.$extend(rtn, {
                    iconStyle: {
                        color: this.menuColors.labelExpandColor
                    }
                });
            }
            //处理 noSub == true 且 active == true 时 增加 active 标记
            if (this.noSub && this.active) rtn.active = true;
            //处理 disabled == true
            if (this.disabled) rtn.disabled = true;

            //处理 一级菜单 且 compact == true 横向折叠时的额外样式
            if (this.menuDeepLevel===0 && this.compact) {
                if (this.active) {
                    //如果 active == true 则增加 active 标记
                    rtn.active = true;
                } else{
                    //未选中状态，使用默认 icon 颜色
                    rtn = this.$extend(rtn, {
                        iconStyle: {
                            color: this.$ui.cssvar.color.fc.m,
                        }
                    });
                }
            }
            return rtn;
        },

        //根据菜单主题色，生成需要的目标颜色
        menuColors() {
            let is = this.$is,
                //菜单主题色
                color = this.color,
                //菜单背景色
                bgc = this.background,
                csv = this.$ui.cssvar.color;

            return {
                //子菜单展开时，为整个菜单项增加背景色 10% 透明度
                itemExpandBgc: bgc==='bgc' ? `${csv.bgc.d1}19` : `${csv[bgc].l2}19`,
                //子菜单展开时，header 背景色  transparent
                headerExpandBgc: 'transparent',
                //子菜单展开时，label 文字颜色
                labelExpandColor: csv[color].d2,
                //子菜单失效时，label 文字颜色  透明度由 __PRE__-bar.disabled 样式类提供
                labelDisabledColor: csv.fc.m,
            };
        },
    },
    methods: {
        /**
         * 响应菜单项 header 栏点击动作
         */
        whenMenuHeaderClick() {
            //if (this.manual) return false;
            if (this.compact===true) {
                //横向折叠状态，点击自动展开
                return this.$emit('compact-menu-item-click', this.idxChain);
            } else if (this.noSub) {
                //没有子菜单，表示这是菜单点击事件
                return this.$emit('menu-item-click', this.keyChain, this.idxChain);
            } else {
                //有子菜单，表示这是 折叠|展开 动作
                return this.$emit('menu-item-toggle-collapse', this.idxChain);
            }
        },
        whenMenuSubItemToggleCollapse(idxChain=[]) {
            if (this.manual) return false;
            return this.$emit('menu-item-toggle-collapse', idxChain);
        },
        whenMenuSubItemClick(keyChain=[], idxChain=[]) {
            if (this.manual) return false;
            return this.$emit('menu-item-click', keyChain, idxChain);
        },
    }
}
</script>

<style>

</style>