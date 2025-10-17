<template>
    <button
        :class="'cv-btn'+(btnClass!=''?' '+btnClass:'')+(asMenuItem?' btn-menuitem':'')+(fullLine?' btn-full-line':(gapInline?' cv-gap-inline':''))+((active||(popMenu&&popmenu.show)||mouse.down)?' btn-active'+(activeClass!=''?' '+activeClass:''):'')+' '+(customClass==''?'':' '+customClass)"
        :style="btnStyle"
        :title="title"
        @click="whenBtnClick"
        @mouseenter="whenMouseEnter"
        @mouseleave="whenMouseLeave"
        @mousedown="whenMouseDown"
        @mouseup="whenMouseUp"
    >
        <cv-icon
            v-if="icon!='' && !iconRight"
            :icon="spin?'vant-sync':icon"
            :size="iconSize"
            :spin="spin"
        ></cv-icon>
        <label 
            v-if="label!=''"
            :class="(incell?'btn-incell':'')+' '+(asTitle?'btn-astitle':'')"
            :style="(labelZoom!=1?'font-size:'+labelZoom+'em;':'')+(asMenuItem ? (active ? 'font-weight: bold;' : 'color:'+cssvar.color.f.$+';') : '')"
        >{{label}}</label>
        <cv-icon
            v-if="icon!='' && iconRight"
            :icon="spin?'vant-sync':icon"
            :size="iconSize"
            :spin="spin"
        ></cv-icon>
        <slot></slot>
        <cv-icon
            v-if="asMenuItem && popMenuList.length>0 && !active"
            icon="vant-caret-right"
            :size="14"
            :color="popmenu.show ? (type=='default' ? cssvar.color.primary.$ : cssvar.color[type].$) : cssvar.color.f.l3"
        ></cv-icon>
        <cv-icon
            v-if="asMenuItem && (active || menuItemInactiveIcon!='')"
            :icon="popMenuList.length>0 ? 'vant-caret-right' : (active ? menuItemActiveIcon : menuItemInactiveIcon)"
            :size="popMenuList.length>0 ? 14 : iconSize"
            :style="!active ? 'opacity: 0.5;' : ''"
        ></cv-icon>
    </button>
</template>

<script>
import mixinBase from '../../mixin/base';

export default {
    mixins: [mixinBase],
    props: {
        type: {
            type: String,
            default: 'default'  //default/primary/danger/warn/success/info
        },

        size: {
            type: [String, Number],
            default: 'default'   //mini/small/default/medium/large
        },

        //强制使用固定宽度
        forceWidth: {
            type: [String, Number],
            default: ''
        },

        icon: {
            type: String,
            default: ''
        },

        plain: {
            type: Boolean,
            default: false
        },

        text: {
            type: Boolean,
            default: false
        },
        //是否显示浅色 text
        lightText: {
            type: Boolean,
            default: false
        },

        round: {
            type: Boolean,
            default: false
        },
        square: {
            type: Boolean,
            default: false
        },

        // true|false  or  'gray'|'red'|'success' 起始颜色类别
        // false    普通按钮
        // true     隐形按钮，根据 type 变换起始颜色
        // 'gray'   隐形按钮，起始按钮颜色为 灰色
        // 'blue'   隐形按钮，起始按钮颜色为 自定义的 red ~ purple
        popout: {
            type: [Boolean, String],
            default: false
        },
        
        disable: {
            type: Boolean,
            default: false
        },

        label: {
            type: [String, Number],
            default: ''
        },

        title: {
            type: String,
            default: ''
        },

        active: {
            type: Boolean,
            default: false
        },

        //active 时的 class
        activeClass: {
            type: String,
            default: ''
        },
        //active 时的 style
        activeStyle: {
            type: [String, Object],
            default: ''
        },

        //disabled: {
        //    type: Boolean,
        //    default: false
        //},

        spin: {
            type: Boolean,
            default: false
        },

        //icon 在右侧
        iconRight: {
            type: Boolean,
            default: false
        },

        //button 在 table cell 中
        incell: {
            type: Boolean,
            default: false
        },

        //作为 menu item 显示
        asMenuItem: {
            type: Boolean,
            default: false
        },
        //作为 menuitem 并被选中时的 icon sign
        menuItemActiveIcon: {
            type: String,
            default: 'vant-check'
        },
        //作为 menuitem 并未被选中时的 icon sign
        menuItemInactiveIcon: {
            type: String,
            default: ''
        },

        //显示为 title ，文字加粗，padding 放大
        asTitle: {
            type: Boolean,
            default: false
        },

        //是否在 flex line 中显示 gap
        gapInline: {
            type: Boolean,
            default: true
        },
        //在 flex line 中占据整行
        fullLine: {
            type: Boolean,
            default: false
        },

        //单独缩放 icon 尺寸
        iconZoom: {
            type: Number,
            default: 1
        },
        //单独缩放 label 文字尺寸
        labelZoom: {
            type: Number,
            default: 1
        },


        /**
         * pop menu
         */
        //是否可以下拉选择
        popMenu: {
            type: Boolean,
            default: false
        },
        //下拉选项
        /**
         * [
         *      {label:'', value:'', disabled:true},
         *      ...
         * ]
         */
        popMenuList: {
            type: Array,
            default: ()=>[]
        },
        //当此 popmenu 为子菜单时，在父菜单 list 中的 idx
        //popMenuIdx: {
        //    type: Number,
        //    default: -1
        //},
        //下拉菜单 tips 提示
        /*popMenuTips: {
            type: String,
            default: '更多操作'
        },*/
        //选中项
        popMenuActive: {
            type: [String, Number, Array],
            default: ''
        },
        //是否只选择 leaf 叶子节点
        popMenuSelectLeafOnly: {
            type: Boolean,
            default: true
        },
        //弹出菜单的触发方式，click/hover/focus/custom
        popMenuTrigger: {
            type: String,
            default: 'click'
        },
        //是否多选
        popMenuMultiple: {
            type: Boolean,
            default: false
        },
        //是否输出完整的 active menu path
        popMenuFullPath: {
            type: Boolean,
            default: false
        },
        //其他 popMenu params
        popMenuParams: {
            type: Object,
            default: () => {
                return {}
            }
        },
        //弹出菜单的事件处理
        popMenuEventHandler: {
            type: Object,
            default: () => {
                return {}
            }
        },





        

        color: {
            type: String,
            default: ''
        },

        //icon 不设置 color，用于 彩色 icon
        unsetColor: {
            type: Boolean,
            default: false
        },

        //icon 与 text 使用不同 color
        differentColor: {
            type: Boolean,
            default: false
        },
        textColor: {
            type: String,
            default: ''
        },

        /*customClass: {
            type: String,
            default: ''
        },*/

        
    },

    data() {
        /*let cssvar = this.cssvar,
            df = this.$is.defined,
            size = {
                mini:   22,
                small:  28,
                default: 32,
                medium: 36,
                large: 48
            },
            color = {
                default: {
                    bg: ''
                }
            };
        if (df(cssvar) && df(cssvar.size) && df(cssvar.size.btn)) {
            size = this.$extend(size, cssvar.size.btn);
        }*/
        return {
            //预设
            //default: {
            //    size,
            //},

            //mouseover 状态
            mouse: {
                enter: false,
                down: false,
                clicking: false,    //for debounce 点击按钮防抖
            },
            //slot 是否有内容
            slotUsed: false,

            //pop menu
            popmenu: {
                comp: null,
                show: false,
                closing: false,
                //list: this.popMenuList,
                //active: this.popMenuActive,
                //activePath: []
            },
        }
    },
    computed: {
        //计算 btn class
        btnClass() {
            let tp = this.type,
                sz = this.size,
                pl = this.plain,
                tx = this.text,
                ltx = this.lightText,
                rd = this.round,
                sq = this.square,
                po = this.popout,
                ds = this.disable,
                ms = this.mouse,
                cls = [];
            if (ds) {
                cls.push('btn-disable');
                if (pl) cls.push('btn-plain');
                if (tx) cls.push('btn-text');
                if (po!==false) cls.push('btn-popout');
            } else if (tx) {
                cls.push('btn-text');
                if (ltx) cls.push('btn-text-light');
                if (tp!='default') {
                    cls.push('btn-'+tp);
                } else {
                    cls.push(`btn-${this.cssvar.colorType}`);
                }
            } else if (po!==false) {
                cls.push('btn-popout');
                if (this.$is.string(po)) cls.push('btn-popout-from-'+po);
                if (tp!='default') {
                    cls.push('btn-'+tp);
                } else {
                    cls.push(`btn-${this.cssvar.colorType}`);
                }
            } else if (pl) {
                cls.push('btn-plain');
                if (tp!='default') {
                    cls.push('btn-'+tp);
                } else {
                    cls.push('btn-'+this.cssvar.colorType)
                }
            } else if (tp!='default') {
                cls.push('btn-'+tp);
            }
            /* else if (tp!='default') {
                cls.push('btn-'+tp);
                if (pl) cls.push('btn-plain');
            }*/
            if (sz!='default') cls.push('btn-'+sz);
            //round square 二选一 round 优先
            if (rd) {
                cls.push('btn-round');
            } else if (sq) {
                cls.push('btn-square');
            }
            if (ms.down && !this.asMenuItem) cls.push('btn-shrink');
            return cls.join(' ');
        },
        //计算 btn style
        btnStyle() {
            let lb = this.label,
                su = this.slotUsed,
                fw = this.forceWidth,   //强制使用固定宽度
                pl = this.plain,
                tp = this.type,
                sz = this.btnSize,
                csty = this.customStyle,
                asty = this.activeStyle,
                sty = this.$is.plainObject(csty) ? {} : '';
            if (fw!='') {
                fw = this.$is.string(fw) ? fw : fw+'px';
                if (this.$is.string(sty)) {
                    sty += `width:${fw};`;
                    sty += `height:${sz}px;`;
                    sty += `padding:0;`;
                } else {
                    sty.width = fw;
                    sty.height = sz+'px';
                    sty.padding = 0;
                }
            } else if (lb=='' && !su) {
                if (this.$is.string(sty)) {
                    sty += `width:${sz}px;`;
                    sty += `height:${sz}px;`;
                    sty += `padding:0;`;
                } else {
                    sty.width = sz+'px';
                    sty.height = sz+'px';
                    sty.padding = 0;
                }
            }
            if (pl && this.type=='def') {

            }
            if (this.asMenuItem) {
                if (this.popmenu.show) {
                    if (this.$is.string(sty)) {
                        sty += `background-color:${cssvar.color[this.type].bg};`;
                    } else {
                        sty.backgroundColor = cssvar.color[this.type].bg;
                    }
                }
            }
            if (this.$is.plainObject(csty)) {
                sty = this.$extend(sty, csty);
            } else {
                sty += csty;
            }
            if ((this.active||(this.popMenu&&this.popmenu.show)||this.mouse.down) && !this.$is.empty(asty)) {
                sty = this.mixinAnyStyle(sty, asty);
            } 
            return sty;
        },
        //计算 btn size
        btnSize() {
            let sz = this.size,
                cv = this.cssvar.size.btn,
                isz = 0;
            if (sz=='default') {
                isz = cv.$.replace('px','');
                isz = isz*1;
            } else {
                if (this.$is.number(sz)) {
                    isz = sz;
                } else {
                    let szns = 'large,medium,small,mini'.split(','),
                        szs = 'xl,l,s,xs'.split(',');
                    if (szns.includes(sz)) {
                        let szn = szs[szns.indexOf(sz)];
                        isz = cv[szn].replace('px','');
                        isz = isz*1;
                    } else {
                        isz = cv.$.replace('px','');
                        isz = isz*1;
                    }
                }
            }
            return isz;
        },
        //计算 icon size
        iconSize() {
            let sz = this.size,
                cv = this.cssvar.size.btn.icon,
                isz = 0;
            if (sz=='default') {
                isz = cv.$.replace('px','');
                isz = isz*1;
            } else {
                if (this.$is.number(sz)) {
                    isz = sz-12;
                } else {
                    let szns = 'large,medium,small,mini'.split(','),
                        szs = 'xl,l,s,xs'.split(',');
                    if (szns.includes(sz)) {
                        let szn = szs[szns.indexOf(sz)];
                        isz = cv[szn].replace('px','');
                        isz = isz*1;
                    } else {
                        isz = null;
                    }
                }
            }
            if (this.$is.number(isz)) {
                isz = isz*this.iconZoom;
                return isz<10 ? 10 : isz;
            }
            return 'default';
        },
        //计算前景色
        /*frontColor() {
            let cv = this.cssvar.color,
                tp = this.type,
                bgc = tp=='default' ? cv.bg.light : cv[tp].$,
                brt = cgy.colorBrightness(bgc),
                darkbc = brt<128;
            return darkbc ? cv.white : cv.black;
        },*/
    },
    watch: {
        /*popMenuList(nv, ov) {
            this.popmenu.list.splice(0);
            this.popmenu.list.push(...this.popMenuList);
        },
        popMenuActive(nv, ov) {
            if (this.$is.array(this.popmenu.active)) {
                this.popmenu.active.splice(0);
                this.popmenu.active.push(...this.popMenuActive);
            } else {
                this.popmenu.active = this.popMenuActive;
            }
        },*/
    },
    mounted() {
        this.slotUsed = this.slotHasContent();
    },
    methods: {
        //click 事件
        //防抖 debounce
        whenBtnClick(event) {
            if (this.disable) return false;
            if (this.mouse.clicking!==true) {
                this.mouse.clicking = true;
                if (this.popMenu && this.popMenuTrigger=='click') {
                    if (this.asMenuItem) {
                        this.togglePopMenu(true);
                    } else {
                        this.togglePopMenu();
                    }
                }
                event.targetComponent = this;
                this.$ev('click', this, event);
                this.$wait(500).then(()=>{
                    this.mouse.clicking = false;
                });
            }
        },

        //mouse 事件
        whenMouseEnter(event) {
            if (this.disable) return false;
            this.mouse.enter = true;
            if (this.popMenu && (this.popMenuTrigger=='hover' || this.asMenuItem)) {
                if (this.popmenu.closing!=false) {
                    clearTimeout(this.popmenu.closing);
                    this.popmenu.closing = false;
                } else {
                    this.togglePopMenu(true);
                }
                if (!this.$is.null(this.popmenu.comp)) {
                    this.popmenu.comp.stopClosing();
                }
            }
            event.targetComponent = this;
            this.$ev('mouse-enter', this, event);
        },
        whenMouseLeave(event) {
            if (this.disable) return false;
            this.mouse.enter = false;
            if (this.popMenu && (this.popMenuTrigger=='hover' || this.popMenuTrigger=='click' || this.asMenuItem)) {
                if (this.popmenu.closing==false) {
                    this.popmenu.closing = setTimeout(()=>{
                        this.togglePopMenu(false);
                        clearTimeout(this.popmenu.closing);
                        this.popmenu.closing = false;
                    }, this.asMenuItem ? 50 : 100);
                }
            }
            event.targetComponent = this;
            this.$ev('mouse-leave', this, event);
        },
        whenMouseDown(event) {
            if (this.disable) return false;
            this.mouse.down = true;
            if (this.popMenu && this.popMenuTrigger=='focus') {
                this.togglePopMenu(true);
            }
            event.targetComponent = this;
            this.$ev('mouse-down', this, event);
        },
        whenMouseUp(event) {
            if (this.disable) return false;
            this.mouse.down = false;
            if (this.popMenu && this.popMenuTrigger=='focus') {
                this.togglePopMenu(false);
            }
            event.targetComponent = this;
            this.$ev('mouse-up', this, event);
        },

        //弹出/关闭 pop menu
        togglePopMenu(show=null) {
            show = !this.$is.boolean(show) ? !this.popmenu.show : show;
            if (this.popmenu.show==show) return;
            if (show) {
                let opt = this.$extend({}, this.popMenuParams, {
                    type: (this.type=='' || this.type=='default') ? this.cssvar.colorType : this.type,
                    menuList: this.popMenuList,
                    menuActive: this.popMenuActive,
                    menuMultiple: this.popMenuMultiple,
                    selectLeafOnly: this.popMenuSelectLeafOnly,
                    isSubMenu: this.asMenuItem,
                    customEventHandler: this.$extend({
                        'panel-pop': (popMenuComp, popShow) => {
                            this.popmenu.show = popShow;
                            if (popShow==false) {
                                this.popmenu.comp = null;
                            }
                        },
                    }, this.popMenuEventHandler)
                });
                //console.log(opt);
                this.$ui.popmenu(this, opt).then((comp)=>{
                    this.popmenu.comp = comp;
                });
            } else {
                this.popmenu.comp.panel.show = false;
            }
        },

        //接受 popmenu 组件调用，输出 active
        popMenuSelected(active, activePath) {
            let full = this.popMenuFullPath;
            //触发 pop-menu-selected 事件，由外部组件捕获并处理
            if (full) {
                this.$ev('pop-menu-selected', this, activePath);
            } else {
                this.$ev('pop-menu-selected', this, active);
            }
        },
        //根据 active string 计算 active path
        /*getPopMenuActivePath(active, list=null) {
            list = this.$is.array(list) ? list : this.popmenu.list;
            let parr = [];
            for (let i=0;i<list.length;i++) {
                let li = list[i];
                if (!this.$is.array(li.children)) {
                    if (li.value == active) {
                        parr.push(li.value);
                        break;
                    }
                } else {
                    let ap = this.getPopMenuActivePath(active, li.children);
                    if (ap.length>0) {
                        parr.push(li.value, ...ap);
                        break;
                    }
                }
            }
            return parr;
        },
        //合并 popmenu active
        setPopMenuActive(active) {
            console.log(active);
            this.popmenu.activePath.splice(0);
            this.popmenu.activePath.push(...active);
            let is = this.$is;
            if (this.popMenuSelectLeafOnly) {
                if (this.popMenuMultiple) {
                    this.popmenu.active.splice(0);
                    for (let i of active) {
                        this.popmenu.active.push(i.slice(-1)[0]);
                    }
                } else {
                    this.popmenu.active = active.slice(-1)[0];
                }
            } else {
                this.popmenu.active.splice(0);
                this.popmenu.active.push(...active);
            }
            
            if (this.asMenuItem) {
                this.$ev('pop-menu-active-change', this, this.popmenu.activePath, this.popMenuIdx);
            } else {
                this.$ev('pop-menu-selected', this, this.popmenu.active, this.popmenu.activePath);
            }
        },*/

        //判断插槽 slot 是否有内容
        slotHasContent() {
            let is = this.$is,
                el = this.$el,
                cds = el.childNodes;
            //console.log(cds);
            if (cds.length>9) return true;
            let cdi = cds[5];
            if (is.defined(cdi.nodeType)) {
                if (cdi.nodeType==1) return true;
                if (cdi.nodeName=='#text' && cdi.data.trim()!="") return true;
            }
            return false;
        },
    }
}
</script>

<style>

</style>