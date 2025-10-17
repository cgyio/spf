/**
 * cv-**** 组件 popup 功能 mixin
 */

import mixinBase from '/vue/@/mixins/base/base';
import mixinEvtBase from '/vue/@/mixins/base/evt-base';

export default {
    mixins: [mixinBase, mixinEvtBase],
    //v-model 参数
    model: {
        prop: 'popShow',
        event: 'panel-pop'
    },

    props: {
        //显示/隐藏
        popShow: {
            type: Boolean,
            default: true
        },

        //触发 pop 的 组件实例/element
        triggerBy: {
            //type: Object,
            default: () => null
        },

        //style
        //pop panel type 颜色类型
        type: {
            type: String,
            default: ''
        },
        //pop panel 尺寸，height 为空表示自适应高度
        width: {
            type: [Number, String],
            default: 256
        },
        //如果指定高度，则 body 出现滚动条
        height: {
            type: [Number, String],
            default: ''
        },
        //指定 pop panel 的最小宽度/高度
        minWidthHeight: {
            type: Array,
            default: () => [128,480]    //15行
        },
    },
    data() {
        return {
            panel: {
                show: this.popShow,
                recalc: false,
            },
        }
    },
    computed: {
        //计算 弹出 panel 的 位置/尺寸/动画形式/style
        //popMenuPosSzAni() {
        popPanelStyle() {
            let pns = {
                    w: 0,
                    h: 0,
                    l: 0,
                    t: 0,
                    ani: ['fadeIn','fadeOut']
                },
                dh = 32;    //最小单位高度，1 行菜单项的高度
            if (this.$is.null(this.triggerBy)) return pns;
            let tb = this.triggerBy,    //=='__self__' ? this : this.triggerBy,
                el = this.$is.vue(tb) ? tb.$el : tb,
                cord = cgy.elmRect(el),
                sw = this.width,
                sh = this.height,
                mw = this.minWidthHeight[0],
                mh = this.minWidthHeight[1],
                w = 0,  //计算后 pop panel 的实际 宽
                h = 0,  //计算后 pop panel 的实际 高
                l = 0,  //计算后 pop panel 的实际 左
                t = 0,  //计算后 pop panel 的实际 顶
                maxh = 0,   //如果未指定 height/minHeight 则计算 maxHeight
                ani = ['fadeIn','fadeOut'],
                dir = '',   // pop panel 的弹出方向，默认为向 rb 右下 弹出
                is = this.$is,
                isn = is.number,
                ispopmenu = is.defined(this.list);
            w = isn(sw) ? sw*1 : (sw=='' ? mw*1 : sw.replace('px','')*1);
            w = isNaN(w) ? mw*1 : w;
            w = w<mw ? mw : w;
            //针对 popmenu
            if (ispopmenu) {
                let mln = this.list.length;
                mh = mh > mln*dh ? mln*dh : mh;
                //高度包含上下 padding 边距，pop panel 的 gap 参数为 compact 表示边距为 cssvar.size.gap.s = 12
                mh += 12*2;
            }
            h = isn(sh) ? sh*1 : (sh=='' ? mh : sh.replace('px','')*1);
            h = isNaN(h) ? mh : h;
            h = h<mh ? mh : h;
            if (this.showBorder) {
                w += 2;
                h += 2;
            }
            if (h<dh) {
                //如果高度小于一行菜单项
                maxh = dh*15; //最大高度设置为 15 行菜单项
            }

            //console.log(tb, el, cord);
            
            //根据实际空间尺寸，决定弹出方向，然后计算 l/t
            if (ispopmenu && (this.isSubMenu || this.popAsSubMenu)) {
            //if (this.isSubMenu || this.popAsSubMenu) {   //子菜单，计算方式不同
                dir = cord.rt[2]<w ? 'l' : 'r';
                if (h<dh) {
                    dir += cord.rb[1] > cord.rt[3] ? 't' : 'b';
                } else {
                    dir += cord.rt[3]<h ? 't' : 'b';
                }
                if (dir.includes('l')) {    
                    //向左弹出
                    l = cord.lt[0] - w;
                } else {
                    //向右弹出
                    l = cord.rt[0];
                }
                if (dir.includes('t')) {
                    //向上弹出
                    t = cord.rb[1] - h;
                } else {
                    //向下弹出
                    t = cord.rt[1];
                }
            } else {
                dir = cord.lt[2]<w ? 'l' : 'r';
                if (h<dh) {
                    dir += cord.lt[1]>cord.lb[3] ? 't' : 'b';
                } else {
                    dir += cord.lb[3]<h ? 't' : 'b';
                }
                if (dir.includes('l')) {    
                    //向左弹出
                    l = cord.rt[0] - w;
                } else {
                    //向右弹出
                    l = cord.lt[0];
                }
                if (dir.includes('t')) {
                    //向上弹出
                    t = cord.lt[1] - h;
                    //ani = ['fadeInUp','fadeOutDown'];
                } else {
                    //向下弹出
                    t = cord.lb[1];
                }
            }
            //如果 h 小于 1 行菜单项高度，表示未指定 height/minHeight 则计算 maxHeight
            if (h<dh) {
                if (dir.includes('t')) {
                    //向上弹出
                    maxh = cord.rt[1] - dh;
                    maxh = maxh<dh ? dh : maxh;
                    //t = cord.rt[1] - maxh;
                } else {
                    //向下弹出
                    maxh = cord.rb[3] - dh;
                    maxh = maxh<dh ? dh : maxh;
                }
            }

            //console.log(w,h,l,t,maxh,ani);
            
            //输出
            return {
                w,h,l,t,maxh,ani
            };
        },

        //如果需要，输出 max-height 到 custom-style
        popPanelCustomStyle() {
            let ps = this.popPanelStyle;
            if (ps.maxh>0) {
                return this.mixinCustomStyle({
                    maxHeight: ps.maxh+'px'
                });
            }
            return this.customStyle;
        },
    },
    watch: {
        popShow: {
            handler(nv, ov) {
                this.panel.show = this.popShow;
            },
            immediate: true
        }
    },
    methods: {
        //触发 v-model
        whenPanelPop(popShow) {
            this.panel.show = popShow;
            this.$ev('panel-pop', this, popShow);
        },

        /**
         * 计算弹出 panel 的 style
         */
        calcPopPanelStyle() {
            let pns = {
                w: 0,
                h: 0,
                l: 0,
                t: 0,
                ani: ['fadeIn','fadeOut']
            };
            if (this.$is.null(this.triggerBy)) return pns;
            let tb = this.triggerBy=='__self__' ? this : this.triggerBy,
                el = this.$is.vue(tb) ? tb.$el : tb,
                cord = cgy.elmRect(el),
                sw = this.width,
                sh = this.height,
                mw = this.minWidthHeight[0],
                mh = this.minWidthHeight[1],
                w = 0,  //计算后 pop panel 的实际 宽
                h = 0,  //计算后 pop panel 的实际 高
                l = 0,  //计算后 pop panel 的实际 左
                t = 0,  //计算后 pop panel 的实际 顶
                ani = ['fadeIn','fadeOut'],
                dir = '',   // pop panel 的弹出方向，默认为向 rb 右下 弹出
                is = this.$is,
                isn = is.number,
                ispopmenu = is.defined(this.list);
            w = isn(sw) ? sw*1 : (sw=='' ? mw*1 : sw.replace('px','')*1);
            w = isNaN(w) ? mw*1 : w;
            w = w<mw ? mw : w;
            //针对 popmenu
            if (ispopmenu) {
                let mln = this.list.length,
                    mih = this.menuItemHeight;
                mh = mh > mln*mih ? mln*mih : mh;
            }
            h = isn(sh) ? sh*1 : (sh=='' ? mh : sh.replace('px','')*1);
            h = isNaN(h) ? mh : h;
            h = h<mh ? mh : h;
            if (this.showBorder) {
                w += 2;
                h += 2;
            }

            console.log(tb, el, cord);
            
            //根据实际空间尺寸，决定弹出方向，然后计算 l/t
            if (ispopmenu && (this.isSubMenu || this.popAsSubMenu)) {
            //if (this.isSubMenu || this.popAsSubMenu) {   //子菜单，计算方式不同
                dir = cord.rt[2]<w ? 'l' : 'r';
                dir += cord.rt[3]<h ? 't' : 'b';
                if (dir.includes('l')) {    
                    //向左弹出
                    l = cord.lt[0] - w;
                } else {
                    //向右弹出
                    l = cord.rt[0];
                }
                if (dir.includes('t')) {
                    //向上弹出
                    t = cord.rb[1] - h;
                    //ani = ['fadeInUp','fadeOutDown'];
                } else {
                    //向下弹出
                    t = cord.rt[1];
                }
            } else {
                dir = cord.lt[2]<w ? 'l' : 'r';
                dir += cord.lb[3]<h ? 't' : 'b';
                if (dir.includes('l')) {    
                    //向左弹出
                    l = cord.rt[0] - w;
                } else {
                    //向右弹出
                    l = cord.lt[0];
                }
                if (dir.includes('t')) {
                    //向上弹出
                    t = cord.lt[1] - h;
                    //ani = ['fadeInUp','fadeOutDown'];
                } else {
                    //向下弹出
                    t = cord.lb[1];
                }
            }
            //输出
            return {
                w,h,l,t,ani
            };
        },

    }
}