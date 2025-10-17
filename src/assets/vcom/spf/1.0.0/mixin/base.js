/**
 * cv-**** 组件通用 mixin
 */

const mixin = {
    props: {

        /**
         * 这两个 props 在 mixinBase 里提供
         * 在这里提供会影响 element-ui 这类 ui 组件库，会报错
         * * 应在所有需要这两个 props 的组件中引入 mixinBase *
         */
        //组件根元素上附加 custom class 样式
        customClass: {
            type: String,
            default: ''
        },
        //组件根元素上附件的 cuatom style 
        customStyle: {
            type: [String, Object],
            default: ''
        },


        /**
         * 某些组件需要 size/type/color 属性
         */
        /**
         * 指定组件的 css 样式类前缀
         * 通过组合 cssPre 和 size/type 生成 class 样式类字符串 附加到组件根元素
         * 如： 
         *      cssPre = icon-
         *      size = medium   medium 在 sizes 中定义为 m
         *      type = fc-d2
         * 则 组件 css 中应定义样式类：
         *      .icon-m         控制此组件的 尺寸
         *      .icon-fc-d2     控制此组件根元素的 颜色
         */
        /**
         * 组件样式前缀
         * !! 组件内部 必须覆盖
         */
        cssPre: {
            type: String,
            default: ''
        },
        size: {
            type: [Number, String],
            default: 'medium'
        },
        /**
         * !! 通过 type 指定的颜色参数，应在组建的 css 中指定 class
         * 如：type = fc-d2 则在组件 css 中应指定 *-fc-d2 的样式类
         */
        type: {
            type: String,
            default: ''
        },
        color: {
            type: String,
            default: ''
        },


        /**
         * disabled
         */
        disabled: {
            type: Boolean,
            default: false
        },

        /**
         * 指定 v-tip 悬停提示
         * 默认显示在下方 bottom effect = light
         */
        /*tip: {
            type: String,
            default: ''
        },*/
        /**
         * v-tip:[tipPosition]="{}"
         * 可选 top/bottom/left/right - start/end
         */
        /*tipPosition: {
            type: String,
            default: 'top'
        },*/


        //组件根元素上附加的 animate css 效果
        animateType: {
            type: String,
            default: ''
        },
        animateInfinite: {
            type: Boolean,
            default: false
        },
        animateClasses: {
            type: String,
            default: ''
        },
        
    },

    //通用的基础组件

    data: function() {return {
        /**
         * 所有组件公用的 size 可选属性值
         * !! 特殊需要的组件 应在组件内部 覆盖
         */
        sizes: {
            giant: 'xl',
            large: 'l',
            medium: 'm',
            small: 's',
            mini: 'xs'
        },
    }},

    computed: {

        /**
         * customClass/Style 配套的 计算属性
         * !! 引用的组件内部 应根据需要覆盖
         */
        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            return this.mixinCustomClass();
        },
        //计算后的 组件根元素 style !!! 组件内覆盖
        computedStyle() {
            return this.mixinCustomStyle();
        },
        //判断 customClass 是否为空
        emptyCustomClass() {
            let is = this.$is,
                ccls = this.customClass;
            if (!is.string(ccls) || ccls=='') return true;
            return false;
        },
        //判断 customStyle 是否是 object 或 为空
        emptyCustomStyle() {
            let is = this.$is,
                csty = this.customStyle;
            if (is.string(csty) && csty=='') return true;
            if (is.plainObject(csty) && is.empty(csty)) return true;
            if (!is.string(csty) && !is.plainObject(csty)) return true;
            return false;
        },

        /**
         * size/type/color 配套的 计算属性
         */
        //根据 size 从 sizes 中获取 参数 xl~xs 返回字符串
        sizeKey() {
            let is = this.$is,
                isd = is.defined,
                szs = this.sizes,
                sz = this.size;
            if (is.realNumber(sz)) return sz;   //如果指定数字形式的 size 直接返回
            if (isd(szs[sz])) return szs[sz];
            if (Object.values(szs).includes(sz)) return sz;
            if (is.string(sz) && sz.startsWith('xx')) return sz;
            if (is.string(sz) && sz.endsWith('px')) return sz;
            return 'm'; //默认返回 m ==medium
        },
        //根据 cssPre 和 size 创建 控制组件 size 的 css 列表 []
        sizeClass() {
            let is = this.$is,
                isn = is.realNumber,
                cp = this.cssPre,
                sz = this.sizeKey,
                clss = [];
            if (!is.string(cp) || cp=='') return [];
            if (isn(sz)) {
                clss.push(`${cp}${sz}px`);
            } else {
                clss.push(`${cp}${sz}`);
            }
            return clss;
        },
        //根据 type 从 cssvar.color 中获取 key
        typeKey() {
            let is = this.$is,
                isd = is.defined,
                cvs = this.cssvar.color,
                tp = this.type;
            if (!is.string(tp) || tp=='') return '';
            if (isd(cvs[tp]) && isd(cvs[tp].$)) return tp;
            if (tp.includes('-')) return tp;
            return '';
        },
        //根据 type 从 cssvar.color 中获取对应的 颜色 hex
        typeHex() {
            if (this.typeKey=='') return '';
            let is = this.$is,
                tp = this.typeKey,
                cvs = this.cssvar.color;
            if (is.defined(cvs[tp])) return cvs[tp].$;
            if (tp.includes('-')) {
                return this.$cgy.loget(cvs, tp.replace(/\-/g, '.'), '');
            }
            return '';
        },
        //根据 cssPre 和 type 创建 控制组件 type 的 css 列表 []
        typeClass() {
            let is = this.$is,
                isn = is.realNumber,
                cp = this.cssPre,
                tp = this.typeKey,
                clss = [];
            if (!is.string(cp) || cp=='') return [];
            if (tp!='') clss.push(`${cp}${tp}`);
            return clss;
        },
        //根据 color 从 cssvar.color 中获取对应的 颜色 hex
        colorHex() {
            let is = this.$is,
                isd = is.defined,
                lgt = this.$cgy.loget,
                cvs = this.cssvar.color,
                clr = this.color;
            if (!is.string(clr) || clr=='') return '';
            if (isd(cvs[clr]) && isd(cvs[clr].$)) return cvs[clr].$;
            if (clr.includes('.')) return lgt(cvs, clr, '');
            if (clr.startsWith('#') || clr.startsWith('rgb')) return clr;
            return '';
        },

        //计算当前的 animate class 
        animateClass() {
            let ani = this.animateType,
                inf = this.animateInfinite ? 'animate__infinite' : '',
                ics = this.animateClasses;
            if (ics!='') return `animate__animated ${ics}`;
            if (ani=='') return '';
            return `animate__animated animate__${ani} ${inf}`;
        },

        //$UI 是否加载完成
        $uiReady() {
            let ui = this.$UI;
            return this.$is.vue(ui);
        },
    },

    methods: {
        //el 元素加载完成
        async elReady() {
            await this.$until(()=>{
                let el = this.$el,
                    is = this.$is;
                return is.elm(el);
            },5000);
            return true;
        },

        /**
         * customClass/Style 配套方法
         */
        //计算 组件根元素 class
        mixinCustomClass(...cls) {
            if (this.emptyCustomClass) return cls.join(' ');
            let is = this.$is,
                acls = this.animateClass,
                ccls = this.customClass,
                clss = [];
            for (let cli of cls) {
                if (is.string(cli) && cli!='') {
                    clss.push(cli);
                }
            }
            if (acls!='') clss.push(acls);
            if (ccls!='') clss.push(ccls);
            return clss.join(' ');
        },
        //与 customStyle 混合
        mixinCustomStyle(...sty) {
            if (!this.emptyCustomStyle) {
                sty.push(this.customStyle);
            }
            let sobj = this.$cgy.mergeCss(...sty);
            return this.$cgy.toCssString(sobj);
        },
        mixinAnyStyle(sty1,sty2) {
            let isstr = this.$is.string,
                isobj = this.$is.plainObject;
            if (isobj(sty1) && isobj(sty2)) return this.$extend(sty1, sty2);
            if (isobj(sty1)) sty1 = cgy.toCssString(sty1);
            if (isobj(sty2)) sty = cgy.toCssString(sty2);
            return sty1+' '+sty2;
        }
    }
}

export default mixin;