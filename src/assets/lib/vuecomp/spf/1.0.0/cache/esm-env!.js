import mixinBase from 'https://ms.systech.work/vue/@/mixins/base/base.js';
import mixinBase__05ImKhAm from 'https://ms.systech.work/src/lib/vuecomp/spf/1.0.0/mixins/base.js';



/**
 * 定义组件 SvNavbar
 * !! 不要手动修改 !!
 */



let SvNavbar = {
    mixins: [mixinBase],
    props: {
        //bar 高度
        //可选：l,xl,xxl, 18-64px
        size: {
            type: [Number, String],
            default: ''
        },

        //是否 fixed
        fixed: {
            type: Boolean,
            default: true
        },

        //位置
        position: {
            type: String,
            default: 'top'
        },

        //是否显示 gap-inner
        showGapInner: {
            type: Boolean,
            default: true
        },
        //gap-inner, xs,s,l,xl
        gapInner: {
            type: String,
            default: ''
        },

        //是否显示 bg
        showBg: {
            type: Boolean,
            default: true
        },
        //是否使用 backdrop-filter
        blurBg: {
            type: Boolean,
            default: true
        },

        //是否显示 border
        showBorder: {
            type: Boolean,
            default: false
        },

        //是否显示 shadow
        showShadow: {
            type: Boolean,
            default: true
        },

        //logo
        showLogo: {
            type: Boolean,
            default: true
        },
        logo: {
            type: String,
            default: 'cgy-cgydesign_light'
        },
        logoHref: {
            type: String,
            default: ''
        },

        //nav title
        barTitle: {
            type: String,
            default: ''
        },

        //自定义 bar 内容
        customBar: {
            type: Boolean,
            default: false
        },

    },
    data() {return {

    }},
    computed: {
        //输出 bar css
        barClass() {
            let is = this.$is,
                sz = this.size,
                szs = 'l,xl,xxl'.split(','),
                fxd = this.fixed,
                pos = this.position,
                gapi = this.showGapInner,
                gap = this.gapInner,
                gaps = 'xs,s,l,xl'.split(','),
                bgc = this.showBg,
                bgb = this.blurBg,
                bd = this.showBorder,
                shd = this.showShadow,
                ccls = this.customClass,
                cls = ['cv-bar'];
            //高度
            if (sz!='' && sz!=0) {
                if (is.realNumber(sz)) {
                    if (sz<18) {
                        sz = 18;
                    } else if (sz>64) {
                        sz = 64;
                    }
                    cls.push(`bar-${sz}px`);
                } else if (is.string(sz) && szs.includes(sz)) {
                    cls.push(`bar-${sz}`);
                }
            }
            //背景色
            if (bgc) {
                cls.push('bar-bg');
                if (bgb) cls.push('bar-bg-blur');
            }
            //fixed 位置
            if (fxd) {
                cls.push('bar-fixed');
                cls.push(`fixed-${pos}`);
            }
            //gap-inner
            if (gapi) {
                cls.push('bar-gap-inner');
                if (gaps.includes(gap)) cls.push(`gap-${gap}`);
            }
            //边线
            if (bd) cls.push('bar-bd');
            //阴影
            if (shd) cls.push('bar-shadow');
            //输出
            cls = cls.join(' ');
            //custom-class
            if (ccls!='') {
                cls += ' '+ccls;
            }
            return cls;
        },
        //输出 logo src
        logoSrc() {
            let sl = this.showLogo,
                lg = this.logo,
                prefix = `${Vue.lib}/icon/`;
            if (!sl || lg=='') return '';
            if (lg.includes('//')) return lg;
            return prefix+lg;
        },
    },
    methods: {
        //点击 logo 
        clickLogo() {
            let lh = this.logoHref;
            if (lh=='') return false;
            lh = `${Vue.host}/${lh.trimAnyStart('/')}`;
            window.location.href = lh;
        },
    }
}

SvNavbar.template = `<div :class="barClass"><template v-if="customBar"><slot name="custom-bar"></slot></template><template v-else><img v-if="showLogo && logo!=''" class="bar-logo" :style="logoHref!='' ? 'cursor:pointer;' : ''" :src="logoSrc" @click="clickLogo"><span v-if="barTitle!=''" class="f-xl f-w900 f-black">{{ barTitle }}</span><slot name="left-bar"></slot><span class="cv-flex flex-1"></span><slot name="right-bar"></slot></template></div>`;



/**
 * 定义组件 SvElSlider
 * !! 不要手动修改 !!
 */



let SvElSlider = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: Number,
            default: 0
        },
    },
    data() {return {
        cacheValue: this.value,
    }},
    computed: {

        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            let dft = ['cv-el'];
            return this.mixinCustomClass(...dft);
        },
    },
    watch: {
        value(nv,ov) {
            this.cacheValue = this.value;
        }
    },
    methods: {
        /**
         * 传递事件
         */
        whenChange(value) {
            this.$emit('input', value);
            return this.$emit('change', value);
        },
        whenInput(value) {
            return this.$emit('input', value);
        },
    }
}

SvElSlider.template = `<el-slider v-model="cacheValue" v-bind="$attrs" :disabled="disabled" :class="computedClass" :style="computedStyle" @change="whenChange" @input="whenInput"></el-slider>`;



/**
 * 定义组件 SvElSwitch
 * !! 不要手动修改 !!
 */



let SvElSwitch = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: [String, Number, Boolean],
            default: ''
        },

        title: {
            type: String,
            default: ''
        },
        
        //width 默认 46px
        width: {
            type: [String, Number],
            default: 46
        },

        /**
         * 是/否 时的 颜色/icon/text
         * 颜色 在 cssvar.color 中定义
         * icon 在 iconPackage 中
         * text 和 icon 同时指定时 以 icon 为主
         * 默认都不指定 使用默认颜色，不使用 icon
         */
        activeColor: {
            type: String,
            default: ''
        },
        inactiveColor: {
            type: String,
            default: ''
        },
        activeIcon: {
            type: String,
            default: ''
        },
        inactiveIcon: {
            type: String,
            default: ''
        },
        activeText: {
            type: String,
            default: ''
        },
        inactiveText: {
            type: String,
            default: ''
        },

        //定义 switch 组件的 class/style
        switchClass: {
            type: String,
            default: ''
        },
        switchStyle: {
            type: [String, Object],
            default: ''
        },

        /**
         * switch 切换是否为 async 异步操作
         * 如果是 则指定 异步方法
         * 此方法参数为 当前的 cacheValue 状态值，返回切换后的 状态值 true/false
         */
        asyncSwitch: {
            type: [Boolean, Function],
            default: false
        },

        
    },
    data() {return {
        cacheValue: this.value,
        //switch disabled
        swDisabled: this.disabled,

        //toggle 切换中 标记
        toggling: false,    
        //icon 显示标记
        iconSt: {
            dftsz: 14,  //默认 icon size
            active: {
                size: 0,
                spin: false
            },
            inactive: {
                size: 0,
                spin: false
            },
        },

    }},
    computed: {

        //计算后的 class
        computedClass() {
            let is = this.$is,
                dis = this.swDisabled,
                cls = ['cv-el-switch-wrapper'];
            if (dis) cls.push('cv-el-switch-disabled');
            return this.mixinCustomClass(...cls);
        },
        swClass() {
            let is = this.$is,
                scls = this.switchClass,
                clss = ['cv-el'];
            if (is.string(scls) && scls!='') clss.push(scls);
            return clss.join(' ');
        },
        //计算后的 style
        computedStyle() {
            let is = this.$is,
                iss = s => is.string(s) && s!='',
                isn = n => is.realNumber(n) && n>0,
                w = this.width,
                tgl = this.toggling,
                sty = {};
            if (this.useText==true) {
                sty.width = 'unset !important';
            } else {
                sty.width = this.switchWidth+'px';
            }
            if (tgl==true) {
                sty.cursor = 'wait';
            }
            return this.mixinCustomStyle(sty);
        },
        swStyle() {
            let is = this.$is,
                ssty = this.switchStyle,
                sty = {
                    position: 'absolute',
                    left: 0,
                    top: 0,
                    //width: this.switchWidth+'px',
                    zIndex: 1,
                };
            if ((is.string(ssty) && ssty!='') || (is.plainObject(ssty) && !is.empty(ssty))) sty = this.$cgy.mergeCss(sty, ssty);
            return this.$cgy.toCssString(sty);
        },
        //根据 width 以及是否指定了 text 获取 switch width 数字
        switchWidth() {
            let is = this.$is,
                ut = this.useText,
                w = this.width;
            //如果指定了 active/inactive Text 则不指定 wrapper 宽度
            if (ut==true) return 0;
            //指定了 width
            if (is.realNumber(w)) return w;
            if (is.string(w) && w.endsWith('px')) {
                return w.replace('px','')*1;
            }
            //默认宽度
            return 46;
        },

        //根据 active/inactive Color 计算 Hex
        actHex() {
            return this.calcHex(this.activeColor);
        },
        inactHex() {
            return this.calcHex(this.inactiveColor);
        },
        //根据 active/inactive Color 计算 icon color
        actFHex() {
            return this.calcFHex(this.activeColor);
        },
        inactFHex() {
            return this.calcFHex(this.inactiveColor);
        },

        //是否使用 icon
        useIcon() {
            let is = this.$is,
                isd = s => is.string(s) && s!='';
            return isd(this.activeIcon) || isd(this.inactiveIcon);
        },
        //输出 icon 序列，未指定则使用 -empty- 代替
        icons() {
            let is = this.$is,
                isd = s => is.string(s) && s!='',
                act = this.activeIcon,
                inact = this.inactiveIcon,
                iqs = {
                    active: '-empty-',
                    inactive: '-empty-'
                };
            if (isd(act)) iqs.active = act;
            if (isd(inact)) iqs.inactive = inact;
            return iqs;
        },

        //是否使用 text 
        useText() {
            if (this.useIcon==true) return false;
            let is = this.$is,
                isd = s => is.string(s) && s!='';
            return isd(this.activeText) || isd(this.inactiveText);
        },
        activeTextWidth() {
            if (this.useText==false) return 0;
            let cv = this.cacheValue,
                act = this.activeText,
                acw = (cv==false || act=='') ? 16 : 0;
            return acw;
        },
        inactiveTextWidth() {
            if (this.useText==false) return 0;
            let cv = this.cacheValue,
                act = this.inactiveText,
                acw = (cv==true || act=='') ? 16 : 0;
            return acw;
        },
        activeTextStyle() {
            if (this.useText==false) return '';
            let acw = this.activeTextWidth;
            return this.$cgy.toCssString({
                width: acw>0 ? acw+'px' : 'unset',
                color: this.actFHex
            });
        },
        inactiveTextStyle() {
            if (this.useText==false) return '';
            let acw = this.inactiveTextWidth;
            return this.$cgy.toCssString({
                width: acw>0 ? acw+'px' : 'unset',
                color: this.actFHex
            });
        },

        //是否使用 async switch 异步切换
        useAsync() {
            let is = this.$is,
                asw = this.asyncSwitch;
            return is.asyncfunction(asw);
        },

    },
    watch: {
        value(nv,ov) {
            this.cacheValue = this.value;
            console.log('prop change', this.cacheValue);
        },

        cacheValue: {
            handler(nv, ov) {
                this.iconShowActive();
            },
            immediate: true
        },

        disabled(nv,ov) {
            this.swDisabled = this.disabled;
        },
    },
    methods: {
        /**
         * 执行 switch 切换
         */
        async doSwitch() {
            if (this.swDisabled==true || this.toggling==true) return false;
            let is = this.$is,
                ov = this.cacheValue,
                ua = this.useAsync;
            if (ua==true) {
                //this.swDisabled = true;
                this.toggling = true;
                //异步切换
                await this.iconHideAll();
                await this.iconShowSpin();
                //执行异步切换方法
                let ov = this.cacheValue,
                    nv = await this.asyncSwitch(ov);
                if (nv==ov) {
                    //未切换成功，不做改变
                    await this.iconShowActive();
                } else {
                    //状态发生改变，将新状态写入 cacheValue
                    this.cacheValue = nv;
                    //触发 change 事件
                    this.whenChange(nv);
                }
                //this.swDisabled = false;
                this.toggling = false;
                await this.$wait(10);
                return true;
            }

            //正常 switch 切换
            this.cacheValue = !ov;
            //触发 change 事件
            this.whenChange(this.cacheValue);
            return true;
        },
        /**
         * 屏蔽 switch click
         */
        whenSwitchClick(evt) {
            evt.preventDefault();
        },
        /**
         * 传递事件
         */
        async whenChange(value) {
            this.$emit('input', value);
            return this.$emit('change', value);
        },

        /**
         * icon 切换
         */
        //隐藏全部
        async iconHideAll() {
            this.$set(this.iconSt.active, 'size', 0);
            this.$set(this.iconSt.inactive, 'size', 0);
            this.$set(this.iconSt.active, 'spin', false);
            this.$set(this.iconSt.inactive, 'spin', false);
            await this.$wait(150);
            return true;
        },
        //如果 异步切换 显示 spiner
        async iconShowSpin() {
            let cv = this.cacheValue,
                ua = this.useAsync,
                isz = this.iconSt.dftsz;
            if (!ua) return false;
            let ick = cv==true ? 'active' : 'inactive';
            this.$set(this.iconSt[ick], 'size', isz);
            this.$set(this.iconSt[ick], 'spin', true);
            await this.$wait(150);
            return true;
        },
        //显示切换后 icon
        async iconShowActive() {
            //先隐藏
            await this.iconHideAll();
            //再显示
            let cv = this.cacheValue,
                isz = this.iconSt.dftsz,
                ick = cv==true ? 'active' : 'inactive';
            this.$set(this.iconSt[ick], 'size', isz);
            await this.$wait(150);
            return true;
        },


        /**
         * calc 
         */
        //计算 colorHex
        calcHex(colorString) {
            let is = this.$is,
                isd = is.defined,
                lgt = this.$cgy.loget,
                cvs = this.cssvar.color,
                clr = colorString;
            if (!is.string(clr) || clr=='') return '';
            if (isd(cvs[clr]) && isd(cvs[clr].$)) return cvs[clr].$;
            if (clr.includes('.')) return lgt(cvs, clr, '');
            if (clr.startsWith('#') || clr.startsWith('rgb')) return clr;
            return '';
        },

        //根据 cssvar.color.* 获取 icon color
        calcFHex(colorString) {
            return this.$cgy.loget(this.cssvar.color, 'white.$', '#fff');

            let is = this.$is,
                csv = this.cssvar.color,
                ck = colorString,
                lgt = k => this.$cgy.loget(csv, k, null),
                dft = 'white';
            if (!is.string(ck)) return dft;
            if (!ck.includes('.')) ck = `${ck}.$`;
            if (is.null(lgt(ck))) return dft;
            return ck.split('.')[0]+'.f';
        },
    }
}

SvElSwitch.template = `<div :class="computedClass" :style="computedStyle" :title="title" @click="doSwitch"><el-switch v-model="cacheValue" v-bind="$attrs" :activeColor="actHex" :inactiveColor="inactHex" :disabled="swDisabled" :class="swClass" :style="swStyle"></el-switch><div class="cv-el-switch-cover"><cv-icon v-if="useIcon" :icon="icons.active" :size="iconSt.active.size" :color="actFHex" :spin="iconSt.active.spin" custom-class="cv-el-switch-icon-left"></cv-icon><span v-if="useText" class="cv-el-switch-text-left" :style="activeTextStyle">{{ cacheValue==true ? activeText : '' }}</span><span class="flex-1"></span><cv-icon v-if="useIcon" :icon="icons.inactive" :size="iconSt.inactive.size" :color="inactFHex" :spin="iconSt.inactive.spin" custom-class="cv-el-switch-icon-right"></cv-icon><span v-if="useText" class="cv-el-switch-text-right" :style="inactiveTextStyle">{{ cacheValue==false ? inactiveText : '' }}</span></div><slot></slot></div>`;



/**
 * 定义组件 SvElInputNumber
 * !! 不要手动修改 !!
 */



let SvElInputNumber = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: Number,
            default: 0
        },
    },
    data() {return {
        cacheValue: this.value,
    }},
    computed: {

        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            let dft = ['cv-el'];
            return this.mixinCustomClass(...dft);
        },
    },
    watch: {
        value(nv,ov) {
            this.cacheValue = this.value;
        }
    },
    methods: {
        /**
         * 传递事件
         */
        whenChange(value, oldValue) {
            this.$emit('input', value);
            return this.$emit('change', value, oldValue);
        },
        whenBlur(event) {
            return this.$emit('blur', event);
        },
        whenFocus(event) {
            return this.$emit('focus', event);
        }
    }
}

SvElInputNumber.template = `<el-input-number v-model="cacheValue" v-bind="$attrs" popper-class="cv-el-pop" :disabled="disabled" :class="computedClass" :style="computedStyle" @change="whenChange" @blur="whenBlur" @focus="whenFocus"></el-input-number>`;



/**
 * 定义组件 SvElDatePicker
 * !! 不要手动修改 !!
 */



let SvElDatePicker = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: [String, Number, Date, Array],
            default: ''
        },

        type: {
            type: String,
            default: 'date'
        },
    },
    data() {return {
        cacheValue: this.value,
    }},
    computed: {

        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            let dft = ['cv-el'];
            return this.mixinCustomClass(...dft);
        },
    },
    watch: {
        value(nv,ov) {
            this.cacheValue = this.value;
        }
    },
    methods: {
        /**
         * 传递事件
         */
        whenChange(value) {
            this.$emit('input', value);
            return this.$emit('change', value);
        },
        whenBlur(pickerComp) {
            return this.$emit('blur', pickerComp);
        },
        whenFocus(pickerComp) {
            return this.$emit('focus', pickerComp);
        }
    }
}

SvElDatePicker.template = `<sv-el-date-picker v-model="cacheValue" :type="type" popper-class="cv-el-pop" :disabled="disabled" v-bind="$attrs" :class="computedClass" :style="computedStyle" @change="whenChange" @blur="whenBlur" @focus="whenFocus"></sv-el-date-picker>`;



/**
 * 定义组件 SvElSelect
 * !! 不要手动修改 !!
 */



let SvElSelect = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: [String, Array],
            default: ''
        },

        //options
        options: {
            type: Array,
            default: ()=>[]
        },

        //prefix icon
        icon: {
            type: String,
            default: ''
        },
    },
    data() {return {
        cacheValue: this.value,
    }},
    computed: {

        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            let dft = ['cv-el'];
            return this.mixinCustomClass(...dft);
        },
    },
    watch: {
        value(nv,ov) {
            this.cacheValue = this.value;
        }
    },
    methods: {
        /**
         * 传递事件
         */
        whenSelectChange(selectedValue) {
            this.$emit('input', selectedValue);
            return this.$emit('change', selectedValue);
        },
        whenVisibleChange(visible) {
            return this.$emit('visible-change', visible);
        },
        whenSelectClear() {
            return this.$emit('clear');
        },
        whenSelectBlur(event) {
            return this.$emit('blur', event);
        },
        whenSelectFocus(event) {
            return this.$emit('focus', event);
        }
    }
}

SvElSelect.template = `<el-select v-model="cacheValue" v-bind="$attrs" popper-class="cv-el-pop" :disabled="disabled" :class="computedClass" :style="computedStyle" @change="whenSelectChange" @visible-change="whenVisibleChange" @clear="whenSelectClear" @blur="whenSelectBlur" @focus="whenSelectFocus"><template v-if="options.length>0"><template v-for="(opi,opidx) of options"><el-option v-if="$is.plainObject(opi)" :key="'cv_el_select_option_'+opidx" :label="opi.label" :value="opi.value"></el-option><el-option v-else :key="'cv_el_select_option_'+opidx" :label="opi" :value="opi"></el-option></template></template><template v-else><slot></slot></template><template v-slot:prefix><slot name="prefix"></slot><cv-icon v-if="icon!=''" :icon="icon" color="fc.l2" :size="17" custom-style="margin: 8px 0 0 3px;"></cv-icon></template><template v-slot:empty><slot name="empty"></slot></template></el-select>`;



/**
 * 定义组件 SvElInput
 * !! 不要手动修改 !!
 */



let SvElInput = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: [String,Number],
            default: ''
        },

        type: {
            type: String,
            default: 'text'
        },

        icon: {
            type: String,
            default: ''
        },
        iconRight: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        cacheValue: this.value,
    }},
    computed: {

        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            let dft = ['cv-el'];
            return this.mixinCustomClass(...dft);
        },
    },
    watch: {
        value(nv,ov) {
            this.cacheValue = this.value;
        }
    },
    methods: {
        /**
         * 传递事件
         */
        whenChange(value) {
            this.$emit('input', value);
            return this.$emit('change', value);
        },
        whenBlur(event) {
            return this.$emit('blur', event);
        },
        whenFocus(event) {
            return this.$emit('focus', event);
        },
        whenInput(value) {
            return this.$emit('input', value);
        },
        whenClear() {
            return this.$emit('clear');
        },
    }
}

SvElInput.template = `<el-input v-model="cacheValue" v-bind="$attrs" :type="type" :disabled="disabled" :class="computedClass" :style="computedStyle" @change="whenChange" @blur="whenBlur" @focus="whenFocus" @input="whenInput" @clear="whenClear"><template v-if="icon!='' && iconRight==false" v-slot:prefix><cv-icon :icon="icon" color="fc.l2" :size="17" custom-style="margin: 8px 0 0 3px;"></cv-icon></template><template v-if="icon!='' && iconRight==true" v-slot:suffix><cv-icon :icon="icon" color="fc.l2" :size="17" custom-style="margin: 8px 3px 0 0;"></cv-icon></template><!--<template v-if="type=='text' && icon==''" v-slot:prefix><slot name="prefix"></slot></template><template v-if="type=='text'" v-slot:suffix><slot name="suffix"></slot></template>--><template v-if="type=='text'" v-slot:prepend><slot name="prepend"></slot></template><template v-if="type=='text'" v-slot:append><slot name="append"></slot></template></el-input>`;



/**
 * 定义组件 SvElTag
 * !! 不要手动修改 !!
 */



let SvElTag = {
    mixins: [mixinBase],
    props: {
        type: {
            type: String,
            default: 'primary'
        },

        size: {
            type: [String, Number],
            default: ''
        },

        //单独指定 文字颜色
        color: {
            type: String,
            default: ''
        },

        //effect
        effect: {
            type: String,
            default: 'light'
        },

        //disabled
        disabled: {
            type: Boolean,
            default: false
        },

        //hover 是否启用 hover 翻转
        //hover: {
        //    type: Boolean,
        //    default: false
        //},

        //可使用 icon
        icon: {
            type: String,
            default: ''
        },

        //是否 拆分 tag 为 label/value
        separate: {
            type: Boolean,
            default: false
        },
        sepLabel: {
            type: String,
            default: ''
        },
        sepValue: {
            type: String,
            default: ''
        },

    },
    data() {return {
        
    }},
    computed: {

        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            let is = this.$is,
                //szo = this.calcSize(),
                tp = this.typeKey,
                tpk = tp.includes('-') ? tp.split('-')[0] : tp,
                dis = this.disabled,
                cl = this.color,
                ef = this.effect,
                //bd = this.border,
                //ico = this.icon,
                //sep = this.separate,
                cls = [];
            //cv-el
            cls.push('cv-el');
            //type/disabled
            if (!dis) {
                cls.push(`cv-el-tag-${tpk}`);
            } else {
                cls.push('cv-el-tag-disabled');
            }
            //font
            if (cl=='' || cl=='reverse') {
                if (ef=='dark') {
                    cls.push(`f-${tpk}${cl=='' ? '-f' : ''}`);
                } else {
                    cls.push(`f-${tpk}${cl=='' ? '' : '-f'}`);
                }
            }
            //cls.push(`f-${szo.font.key=='$' ? 'm':szo.font.key}`);
            //padding
            //cls.push(`pd-x-${szo.padding.key=='$' ? 'm':szo.padding.key}`);
            //separate
            //if (sep) 

            return this.mixinCustomClass(...cls);
        },

        //tag style
        computedStyle() {
            let is = this.$is,
                /*s2n = s => (is.string(s) && s.endsWith('px')) ? s.replace('px','')*1 : s*1,
                n2s = n => is.realNumber(n) ? n+'px' : n,
                csv = this.cssvar,
                szs = csv.size.btn,
                sz = this.sizeKey=='m' ? '$' : this.sizeKey,
                szo = is.defined(szs[sz]) ? szs[sz] : null,
                szn = s2n(is.realNumber(sz) ? sz : (!is.null(szo) ? szo : sz)),*/
                //szo = this.calcSize(),
                //tp = this.typeKey,
                cl = this.color,
                cr = this.colorHex,
                //ef = this.effect,
                //bd = this.border,
                //hv = this.hover,
                //ms = this.mouse,
                sty = {};
            //height
            //sty.height = szo.height.val;
            //radius
            //sty.borderRadius = szo.radius.val;
            //dark border = none
            //if (ef=='dark' || bd==false) sty.border = 'none';
            //通过 color 自定义文字颜色
            if (cl!='' && cl!='reverse' && cr!='') {
                sty.color = `${cr} !important`;
            }
            //hover
            /*if (hv) {
                sty.cursor = 'pointer';
                sty.transition = 'all 0.3s';
                if (ms.enter==true) {
                    sty.opacity = 0.7;
                }
            }*/

            return this.mixinCustomStyle(sty);
        },
    },
    watch: {
        
    },
    methods: {
        /**
         * 传递事件
         */
        whenClick() {
            return this.$emit('click');
        },
        whenClose() {
            return this.$emit('close');
        },
    }
}

SvElTag.template = `<el-tag v-bind="$attrs" :size="size" :type="type" :disabled="disabled" :effect="effect" :class="computedClass" :style="computedStyle" @click="whenClick" @close="whenClose"><cv-icon v-if="icon!=''" :icon="icon" :size="size"></cv-icon><template v-if="separate"><span v-if="sepLabel!=''" class="cv-el-tag-sep-label">{{ sepLabel }}</span><span class="cv-el-tag-sep-value">{{ sepValue }}</span></template><template v-else><slot></slot></template></el-tag>`;



/**
 * 定义组件 SvElSwitchDarkmode
 * !! 不要手动修改 !!
 */



let SvElSwitchDarkmode = {
    mixins: [mixinBase],
    props: {
        //width
        //width: {
        //    type: [String, Number],
        //    default: 46
        //},

        //指定 css 切换耗时，默认 3000 毫秒
        toggleDura: {
            type: Number,
            default: 3000
        },
    },
    data() {return {
        //darkMode 缓存
        cacheDarkMode: false,

        //切换 darkMode 时的 mask 层，遮挡 界面变化
        dmToggleMaskOn: false,
        dmToggleMaskOpacity: 0,

        //body overflow 属性
        bdov: '',
    }},
    computed: {},
    created() {
        this.$until(()=>this.$uiReady).then(()=>{
            this.cacheDarkMode = this.$UI.darkMode;
        });
    },
    methods: {

        async toggleDarkMode(cacheDarkMode) {
            //this.toggling = true;
            //await this.$wait(150);
            //this.toggling = false;
            //await this.$wait(300);
            this.hideBodyScroll();
            this.dmToggleMaskOn = true;
            await this.$wait(100);
            this.dmToggleMaskOpacity = 1;
            await this.$wait(500);
            this.$UI.toggleDarkMode();
            await this.$wait(this.toggleDura);
            this.dmToggleMaskOpacity = 0;
            await this.$wait(500);
            this.restoreBodyScroll();
            this.dmToggleMaskOn = false;
            return this.$UI.darkMode;
        },

        //隐藏 body 的滚动条，避免在切换 css 时 滚动条闪现
        hideBodyScroll() {
            let bd = document.querySelector('body'),
                sty = window.getComputedStyle(bd),
                ov = sty.overflow;
            this.bdov = ov;
            bd.style.overflow = 'hidden';
        },
        restoreBodyScroll() {
            let bd = document.querySelector('body'),
                ov = this.bdov;
            bd.style.overflow = ov;
        },
    }
}

SvElSwitchDarkmode.template = `<cv-el-switch v-if="$uiReady" v-model="cacheDarkMode" active-color="cyan" inactive-color="orange" active-icon="md-sharp-dark-mode" inactive-icon="md-sharp-light-mode" :async-switch="toggleDarkMode" :class="computedClass" :style="computedStyle" v-tip:left="(cacheDarkMode==true?'关闭':'开启')+'暗黑模式'"><div v-if="dmToggleMaskOn==true" :style="'position:fixed;left:0;top:0;width:100vw;height:100vh;overflow:hidden;background-color:'+(cacheDarkMode==true?'#000':'#fff')+';display:flex;align-items:center;justify-content:center;font-size:18px;color:'+(cacheDarkMode==true?'#fff':'#000')+';font-weight:bold;z-index:100000;opacity:'+dmToggleMaskOpacity+';transition:opacity 0.5s;'"><svg style="width:1em;height:1em;fill:currentColor;overflow:hidden;font-size:24px;margin-right:32px;" aria-hidden="true"><use xlink:href="#spiner-180-ring"></use></svg><span>{{ '正在'+(cacheDarkMode==true ? '关闭' : '开启')+'暗黑模式 ...' }}</span></div></cv-el-switch>`;



/**
 * 定义组件 SvElTimePicker
 * !! 不要手动修改 !!
 */



let SvElTimePicker = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'input'
    },
    props: {
        value: {
            type: [String, Number, Date, Array],
            default: ''
        },

        /*type: {
            type: String,
            default: 'date'
        },*/
    },
    data() {return {
        cacheValue: this.value,
    }},
    computed: {

        //计算后的 组件根元素 class !!! 组件内覆盖
        computedClass() {
            let dft = ['cv-el'];
            return this.mixinCustomClass(...dft);
        },
    },
    watch: {
        value(nv,ov) {
            this.cacheValue = this.value;
        }
    },
    methods: {
        /**
         * 传递事件
         */
        whenChange(value) {
            this.$emit('input', value);
            return this.$emit('change', value);
        },
        whenBlur(pickerComp) {
            return this.$emit('blur', pickerComp);
        },
        whenFocus(pickerComp) {
            return this.$emit('focus', pickerComp);
        }
    }
}

SvElTimePicker.template = `<el-time-picker v-model="cacheValue" :type="type" popper-class="cv-el-pop" :disabled="disabled" v-bind="$attrs" :class="computedClass" :style="computedStyle" @change="whenChange" @blur="whenBlur" @focus="whenFocus"></el-time-picker>`;



/**
 * 定义组件 SvElDoc
 * !! 不要手动修改 !!
 */



let SvElDoc = {
    mixins: [mixinBase],
    props: {},
    data() {return {

        //cv-icon 可操作的 参数
        demoParams: {
            icon: 'spiner-wind-toy',
            size: 'medium',
            sizes: ['mini'],
            type: '',
            color: '',
            spin: false,
            customClass: '',
            customStyle: {}
        },
        //针对 demoParams 中 每个参数的 说明
        demoParamsInfo: {
            icon: '图标名称，以 Symbol 方式使用 svg 图标库',
            size: '图标尺寸，可使用尺寸字符串，也可以使用纯数字',
            type: '图标颜色类型，可使用所有主题预定义的颜色名称',
            color: '更多的颜色定义，可使用 cssvar.color 中定义的所有颜色',
            spin: '图标自旋转，常用于 loading 标识',
            customClass: '额外的图标样式类，可使用主题定义的所有可用样式类',
            customStyle: '额外的图表样式，使用 CSS Object'
        },

        demoIcons: [
            'md-sharp-portable-wifi-off', 'md-sharp-keyboard', 
            'spiner-3-dots-scale', 'spiner-wind-toy',
            'vant-setting-fill', 'vant-apple'
        ],
        demoSizes: [
            'xxs','mini','small','medium','large','giant','xxl','xxxl',88,'96px',
            16,17,18,19,20,
        ],
        demoTypes: [
            'primary','danger','warn','success','info','disable',
            'orange','cyan','purple','brand',
            'fc-d3', 'fc', 'bg'
        ],
        demoColors: [
            'primary','primary.l1','primary.l2','primary.l3',
            'red','red.l1','red.l2','red.l3',
            'cyan','cyan.l1','cyan.l2','cyan.l3',
            //this.cssvar.color.fc.d3,
            '#ff0000'
        ],
        demoSpin: false,


        dt: new Date(),
        dts: [],
        tm: '',
        tms: [],
        num: 0,

    }},
    computed: {},
    methods: {
        handleCmd() {

        },

        async asyncToggleSpin(spin) {
            await this.$wait(1000);
            return !spin;
        },
    }
}

SvElDoc.template = `<cv-doc component="cv-el-*" component-set="base" box-width="640px"><template v-slot:demo-comp-box><div class="with-mg"><cv-el-select v-model="demoParams.icon" :options="demoIcons" filterable allow-create clearable custom-style="width: 256px;"></cv-el-select><cv-el-select v-model="demoParams.icon" :options="demoIcons" filterable allow-create clearable disabled custom-class="mg-l-xs" custom-style="width: 256px;"></cv-el-select></div><div class="with-mg"><cv-el-select v-model="demoParams.sizes" :options="demoSizes" filterable allow-create clearable multiple custom-style="width: 360px;"></cv-el-select></div><div class="with-mg"><cv-el-date-picker v-model="dt" custom-class="cv-el-fml-normal" custom-style="width: 192px;"></cv-el-date-picker><cv-el-date-picker v-model="dts" type="daterange" disabled range-separator="至" start-placeholder="开始" end-placeholder="结束" custom-class="mg-l-xs" custom-style="width: 280px;"></cv-el-date-picker></div><div class="with-mg"><cv-el-date-picker v-model="dt" type="datetime" custom-style="width: 200px;"></cv-el-date-picker><cv-el-date-picker v-model="dts" type="datetimerange" range-separator="至" start-placeholder="开始" end-placeholder="结束" custom-class="cv-el-fml-normal mg-l-xs" custom-style="width: 380px;"></cv-el-date-picker></div><div class="with-mg"><cv-el-time-picker v-model="tm" size="large" :picker-options="{selectableRange:'09:00:00 - 18:00:00'}" custom-style="width: 192px;"></cv-el-time-picker><cv-el-time-picker v-model="tms" :picker-options="{selectableRange:'09:00:00 - 18:00:00'}" is-range custom-class="mg-l-xs" custom-style="width: 320px;"></cv-el-time-picker></div><div class="with-mg"><cv-el-input-number v-model="num" :max="10" :min="1" :step="1" custom-class="cv-el-fml-normal mg-r-xs" custom-style="width: 128px;"></cv-el-input-number><cv-el-input-number v-model="num" :max="10" controls-position="right" custom-style="width: 128px;"></cv-el-input-number></div><div class="with-mg"><cv-el-input v-model="demoParams.customClass" custom-class="mg-r-xs" custom-style="width: 128px;"></cv-el-input><cv-el-input v-model="demoParams.customClass" type="password" icon="btn-lock" icon-right placeholder="密码" v-tip.dark.danger="'通过 v-tip 指定的 Tip'" custom-class="mg-r-xs" custom-style="width: 128px;"></cv-el-input><cv-el-input v-model="demoParams.customClass" v-tip="'Tip：'+demoParams.customClass" custom-style="width: 192px;"><template v-slot:prepend><span>密码</span></template></cv-el-input></div><div class="with-mg"><cv-el-input v-model="demoParams.customClass" type="textarea" :rows="4" custom-style="flex:1;"></cv-el-input></div><div class="with-mg"><cv-el-tag custom-class="mg-r-xs">md-sharp-*</cv-el-tag><cv-el-input v-model="demoParams.customClass" custom-class="mg-r-xs" custom-style="width: 96px;"></cv-el-input><cv-el-switch v-model="demoParams.spin" v-tip="demoParams.spin ? 'Spinning' : 'Wait'" active-color="cyan" inactive-color="danger"></cv-el-switch><cv-el-switch v-model="demoParams.spin" v-tip="demoParams.spin ? 'Spinning' : 'Wait'" disabled custom-class="mg-l-xs"></cv-el-switch><cv-el-switch v-model="demoParams.spin" v-tip="demoParams.spin ? 'Spinning' : 'Wait'" active-text="加载中..." inactive-text="就绪" custom-class="mg-l-xs"></cv-el-switch><cv-el-switch v-model="demoParams.spin" v-tip="demoParams.spin ? 'Spinning' : 'Wait'" active-color="yellow" inactive-color="light" active-icon="vant-check" inactive-icon="vant-close" :async-switch="asyncToggleSpin" custom-class="mg-l-xs"></cv-el-switch></div><div class="with-mg"><el-slider v-model="num" class="mg-r-xl" style="width: 128px;"></el-slider><cv-el-slider v-model="num" custom-class="mg-r-xl" custom-style="width: 128px;"></cv-el-slider><cv-el-slider v-model="num" disabled custom-style="width: 128px;"></cv-el-slider></div><div class="with-mg"><cv-el-tag size="small">图标Tag</cv-el-tag><cv-el-tag icon="md-sharp-tag-faces">图标Tag</cv-el-tag><cv-el-tag icon="md-sharp-tag-faces" effect="plain" v-tip="{content:['多行 Tip','第一行内容比较长比较长，有标点符号！','第二行内容']}">图标Tag</cv-el-tag><cv-el-tag icon="md-sharp-tag-faces" effect="dark" v-tip="{content:'tooltip 内容',type:'danger',effect:'dark'}">图标Tag</cv-el-tag></div><div class="with-mg"><cv-el-tag type="cyan" icon="md-sharp-male" closable v-tip="{content:'tooltip 内容'}">图标Tag</cv-el-tag><cv-el-tag type="cyan" icon="md-sharp-male" effect="plain" closable v-tip="{content:'tooltip 内容'}">图标Tag</cv-el-tag><cv-el-tag type="cyan" icon="md-sharp-male" effect="dark" closable v-tip="{content:'tooltip 内容'}">图标Tag</cv-el-tag></div><div class="with-mg"><cv-el-tag size="small" type="orange" icon="md-sharp-male" separate sep-label="分割" sep-value="Tag" closable v-tip="{content:'tooltip 内容'}"></cv-el-tag><cv-el-tag type="orange" icon="md-sharp-tag-faces" effect="plain" separate sep-label="分割" sep-value="Tag" v-tip="{content:'tooltip 内容'}"></cv-el-tag><cv-el-tag type="orange" color="white" icon="md-sharp-tag-faces" effect="dark" separate sep-label="分割" sep-value="Tag" v-tip="{content:'tooltip 内容'}"></cv-el-tag></div></template><template v-slot:demo-comp-ctrl><cv-doc-ctrl prop-key="icon" prop-title="图标名称，以 Symbol 方式使用 svg 图标库" v-loading="demoParams.spin"><span><cv-el-tag v-tip="'vant-*'" custom-class="mg-r-s">vant-*</cv-el-tag><cv-el-tag custom-class="mg-r-s">md-sharp-*</cv-el-tag><cv-el-tag icon="btn-shipped" type="light" effect="plain" :hover="true" :separate="true" sep-label="多色图标：" sep-value="btn-*" custom-class="mg-r-s"></cv-el-tag><cv-el-tag type="danger" effect="plain" :separate="true" sep-label="动画图标：" sep-value="spiner-*" custom-class="mg-r-s"></cv-el-tag></span><template v-slot:ctrl-diy><cv-el-select v-model="demoParams.icon" :options="demoIcons" filterable allow-create clearable disabled custom-style="width: 256px;"></cv-el-select><cv-el-tag custom-class="mg-l-s">md-sharp-*</cv-el-tag></template></cv-doc-ctrl></template></cv-doc>`;



/**
 * 定义组件 SvButton
 * !! 不要手动修改 !!
 */



let SvButton = {
    mixins: [mixinBase__05ImKhAm],
    props: {

        //样式前缀
        cssPre: {
            type: String,
            default: 'btn-'
        },

        //图标名称，来自加载的图标包，在 cssvar 中指定
        icon: {
            type: String,
            default: ''
        },

        //按钮文字
        label: {
            type: String,
            default: ''
        },

        //title
        title: {
            type: String,
            default: ''
        },

        //icon 额外的样式
        iconClass: {
            type: String,
            default: ''
        },
        iconStyle: {
            type: [String, Object],
            default: ''
        },

        /**
         * 开关
         */
        //disabled
        //disabled: {
        //    type: Boolean,
        //    default: false
        //},
        //active
        active: {
            type: Boolean,
            default: false
        },
        /**
         * popout 可以指定弹出之前的 按钮图标/文本 颜色
         * popout = true 则使用 type 指定的 颜色
         * popout = fc.d2 则使用 cssvar.color.fc.d2 作为 按钮图标/文本 颜色
         * popout = #ff0000 直接指定 按钮图标/文本 颜色
         */
        popout: {
            type: [Boolean, String],
            default: false
        },
        //text 链接样式 按钮
        text: {
            type: Boolean,
            default: false
        },
        //是否右侧显示图标
        iconRight: {
            type: Boolean,
            default: false
        },
        //round
        round: {
            type: Boolean,
            default: false
        },
        //square
        square: {
            type: Boolean,
            default: false
        },
        //plain
        plain: {
            type: Boolean,
            default: false
        },
        //spin
        spin: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        //mouse 状态
        mouse: {
            enter: false,
            down: false,
            clicking: false,    //for debounce 点击按钮防抖
        },
    }},
    computed: {
        /**
         * customClass/Style 配套的 计算属性
         * !! 引用的组件内部 应根据需要覆盖
         */
        //计算后的 class override
        computedClass() {
            let is = this.$is,
                sz = this.sizeClass,
                tp = this.typeClass,
                clss = ['cv-btn'];
            if (!is.empty(sz)) clss.push(...sz);
            if (!is.empty(tp)) clss.push(...tp);
            if (this.label=='') clss.push('btn-no-label');
            if (this.square) clss.push('btn-square');
            if (this.round) clss.push('btn-round');
            if (this.plain) clss.push('btn-plain');
            if (this.text) clss.push('btn-text');
            if (this.popout) clss.push('btn-popout');
            if (this.active) clss.push('btn-active');
            if (this.disabled) clss.push('btn-disabled');
            //if (this.mouse.enter==true) clss.push('btn-msov');
            if (this.mouse.down==true) clss.push('btn-shrink');
            return this.mixinCustomClass(...clss);
        },

        //根据 size 属性 获取 icon size 数字 或 px字符串
        iconSize() {
            let is = this.$is,
                sz = this.sizeKey,
                csv = this.cssvar.size.btn;
            if (is.realNumber(sz)) return sz-12;
            if (is.string(sz)) {
                if (is.defined(csv.icon[sz])) return csv.icon[sz];
                if (sz.endsWith('px')) {
                    sz = sz.replace('px', '');
                    return sz*1 - 12;
                }
            }
            return sz;
        },

        //popout 指定的 图标/文本 颜色
        popoutColor() {
            let is = this.$is,
                csv = this.cssvar.color,
                po = this.popout;
            if (!is.string(po)) return '';
            let c = this.$cgy.loget(csv, po, '');
            if (is.plainObject(c) && is.defined(c.$)) return c.$;
            if (is.string(c) && c!='') return c;
            if (po.startsWith('#') || po.startsWith('rgb')) return po;
            return '';
        },
    },
    methods: {
        //click 事件
        //防抖 debounce
        whenBtnClick(event) {
            if (this.disabled) return false;
            if (this.mouse.clicking!==true) {
                this.mouse.clicking = true;
                event.targetComponent = this;
                this.$ev('click', this, event);
                this.$wait(500).then(()=>{
                    this.mouse.clicking = false;
                });
            }
        },

        //mouse 事件
        whenMouseEnter(event) {
            if (this.disabled) return false;
            this.mouse.enter = true;
            event.targetComponent = this;
            this.$ev('mouse-enter', this, event);
        },
        whenMouseLeave(event) {
            if (this.disabled) return false;
            this.mouse.enter = false;
            event.targetComponent = this;
            this.$ev('mouse-leave', this, event);
        },
        whenMouseDown(event) {
            if (this.disabled) return false;
            this.mouse.down = true;
            event.targetComponent = this;
            this.$ev('mouse-down', this, event);
        },
        whenMouseUp(event) {
            if (this.disabled) return false;
            this.mouse.down = false;
            event.targetComponent = this;
            this.$ev('mouse-up', this, event);
        },
    }
}

SvButton.template = `<div :class="computedClass" :style="computedStyle" :title-bak="title" @click="whenBtnClick" @mouseenter="whenMouseEnter" @mouseleave="whenMouseLeave" @mousedown="whenMouseDown" @mouseup="whenMouseUp"><cv-icon v-if="icon!='' && !iconRight" :icon="icon" :size="iconSize" :color="popoutColor" :spin="spin" :custom-class="(label!=''?'btn-icon-left':'')+' '+(iconClass==''?'':iconClass)" :custom-style="iconStyle"></cv-icon><label v-if="label!=''" :style="popoutColor!=''?'color:'+popoutColor+';':''">{{label}}</label><cv-icon v-if="icon!='' && iconRight" :icon="icon" :size="iconSize" :color="popoutColor" :spin="spin" :custom-class="(label!=''?'btn-icon-right':'')+' '+(iconClass==''?'':iconClass)" :custom-style="iconStyle"></cv-icon></div>`;



/**
 * 定义组件 SvButtonDemo
 * !! 不要手动修改 !!
 */



let SvButtonDemo = {
    mixins: [mixinBase__05ImKhAm],
    props: {},
    data() {return {

        demoIcons: [
            'md-sharp-portable-wifi-off', 'md-sharp-keyboard', 
            'spiner-3-dots-scale', 'spiner-wind-toy',
            'vant-setting-fill', 'vant-apple'
        ],
        demoSizes: [
            'mini','small','medium','large','giant'
        ],
        demoTypes: [
            'danger','orange','warn','success','cyan','primary','purple',
            'info','disable',
        ],
        demoPopouts: [
            'fc.d3','fc.$','warn.d3','success.d1','cyan.d2','fc','purple.l1',
            'info.d3','#74c0fc',
        ],

        demoActive: true,
        demoPopout: true,
        demoText: true,
        demoIconRight: true,
        demoRound: true,
        demoSquare: true,
        demoPlain: true,
        demoSpin: true,
        demoDisabled: true,
    }},
    computed: {

    },
    methods: {

    }
}

SvButtonDemo.template = `<div class="cv-demo"><div class="cv-demo-row cv-demo-tit">cv-button 组件</div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">icon 参数</span><template v-for="ici of demoIcons"><span class="f-d1 mg-r-xs" :key="'cv_button_demo_icons_'+ici">{{ici}}</span><cv-button :key="'cv_button_demo_icons_comp_'+ici" :icon="ici" custom-class="mg-r-xl mg-l-xs"></cv-button></template></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">label 参数</span><span class="f-d1 mg-r-xs">按钮文字</span><cv-button icon="md-sharp-portable-wifi-off" label="WIFI Off" custom-class="mg-r-xl mg-l-xs"></cv-button><cv-button icon="md-sharp-portable-wifi-off" label="中文字" custom-class="mg-r-xl mg-l-xs"></cv-button></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">size 参数</span><template v-for="(szi,szidx) of demoSizes"><span class="f-d1 mg-r-xs" :key="'cv_button_demo_sizes_'+szi">{{szi}}</span><cv-button :key="'cv_button_demo_sizes_comp_'+szi" icon="vant-setting-fill" :size="szi" label="按钮文字" :type="demoTypes[szidx]" custom-class="mg-r-xl mg-l-xs"></cv-button></template></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">type 参数</span><span class="f-d1 mg-r-xs" style="margin-left:200px;">默认空值</span><cv-button :key="'cv_button_demo_types_comp_'+tpi" icon="md-sharp-local-police" label="按钮" custom-class="mg-r-xl mg-l-xs"></cv-button><template v-for="tpi of demoTypes"><span class="f-d1 mg-r-xs" :key="'cv_button_demo_types_'+tpi">{{tpi}}</span><cv-button :key="'cv_button_demo_types_comp_'+tpi" icon="md-sharp-local-police" :type="tpi" label="按钮" custom-class="mg-r-xl mg-l-xs"></cv-button></template></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">active 参数</span><span class="f-d1 mg-r-xs">开启/关闭 active</span><el-switch v-model="demoActive" class="mg-r-xl" title="改变 active 参数"></el-switch><span class="f-d1 mg-r-xs">默认空值</span><cv-button :key="'cv_button_demo_types_comp_'+tpi" icon="md-sharp-local-police" label="按钮" :active="demoActive" custom-class="mg-r-xl mg-l-xs"></cv-button><template v-for="tpi of demoTypes"><span class="f-d1 mg-r-xs" :key="'cv_button_demo_types_'+tpi">{{tpi}}</span><cv-button :key="'cv_button_demo_types_comp_'+tpi" icon="md-sharp-local-police" :type="tpi" label="按钮" :active="demoActive" custom-class="mg-r-xl mg-l-xs"></cv-button></template></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">popout 参数</span><span class="f-d1 mg-r-xs">开启/关闭 popout</span><el-switch v-model="demoPopout" class="mg-r-xl" title="改变 popout 参数"></el-switch><cv-button icon="md-sharp-portable-wifi-off" :popout="demoPopout" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-portable-wifi-off" label="按钮" :popout="demoPopout" custom-class="mg-r-xl"></cv-button><cv-button v-for="ci of demoTypes" :key="'cv_button_demo_popout_'+ci" icon="md-sharp-portable-wifi-off" label="按钮" :type="ci" :popout="demoPopout" custom-class="mg-r-xl"></cv-button></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3"></span><span class="f-d1 mg-r-xl">指定 popout 颜色</span><cv-button v-for="(ci,cidx) of demoTypes" :key="'cv_button_demo_popout_'+ci" icon="md-sharp-portable-wifi-off" label="按钮" :type="ci" :popout="demoPopouts[cidx]" custom-class="mg-r-xl"></cv-button></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">text 参数</span><span class="f-d1 mg-r-xs">开启/关闭 text</span><el-switch v-model="demoText" class="mg-r-xl" title="改变 text 参数"></el-switch><cv-button icon="md-sharp-keyboard" label="按钮 dommy529" :text="demoText" :disabled="demoDisabled" custom-class="mg-r-xl" custom-style="color:var(--color-fc-d3);"></cv-button><cv-button v-for="ci of demoTypes" :key="'cv_button_demo_disabled_'+ci" icon="md-sharp-keyboard" label="按钮 dommy529" :type="ci" :text="demoText" :disabled="demoDisabled" custom-class="mg-r-xl"></cv-button></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">disabled 参数</span><span class="f-d1 mg-r-xs">开启/关闭 disabled</span><el-switch v-model="demoDisabled" class="mg-r-xl" title="改变 disabled 参数"></el-switch><cv-button v-for="ci of demoTypes" :key="'cv_button_demo_disabled_'+ci" icon="md-sharp-keyboard" label="按钮" :type="ci" :disabled="demoDisabled" custom-class="mg-r-xl"></cv-button></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">iconRight 参数</span><span class="f-d1 mg-r-xs">开启/关闭 iconRight</span><el-switch v-model="demoIconRight" class="mg-r-xl" title="改变 iconRight 参数"></el-switch><cv-button icon="md-sharp-palette" :icon-right="demoIconRight" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" :icon-right="demoIconRight" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" type="primary" :icon-right="demoIconRight" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" type="success" :plain="true" :icon-right="demoIconRight" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" type="success" :plain="true" :active="true" :icon-right="demoIconRight" custom-class="mg-r-xl"></cv-button></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">round 参数</span><span class="f-d1 mg-r-xs">开启/关闭 round</span><el-switch v-model="demoRound" class="mg-r-xl" title="改变 round 参数"></el-switch><cv-button icon="md-sharp-local-police" :round="demoRound" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-local-police" label="按钮" :round="demoRound" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-local-police" label="按钮" type="primary" :round="demoRound" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-local-police" label="按钮" type="cyan" :plain="true" :round="demoRound" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-local-police" label="按钮" type="cyan" :plain="true" :active="true" :round="demoRound" custom-class="mg-r-xl"></cv-button></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">square 参数</span><span class="f-d1 mg-r-xs">开启/关闭 square</span><el-switch v-model="demoSquare" class="mg-r-xl" title="改变 square 参数"></el-switch><cv-button icon="md-sharp-local-police" :square="demoSquare" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-local-police" label="按钮" :square="demoSquare" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-local-police" label="按钮" type="primary" :square="demoSquare" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-local-police" label="按钮" type="warn" :plain="true" :square="demoSquare" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-local-police" label="按钮" type="warn" :plain="true" :active="true" :square="demoSquare" custom-class="mg-r-xl"></cv-button></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">plain 参数</span><span class="f-d1 mg-r-xs">开启/关闭 plain</span><el-switch v-model="demoPlain" class="mg-r-xl" title="改变 plain 参数"></el-switch><cv-button icon="vant-setting-fill" :plain="demoPlain" :disabled="demoDisabled" custom-class="mg-r-xl"></cv-button><cv-button icon="vant-setting-fill" label="按钮" :plain="demoPlain" :disabled="demoDisabled" custom-class="mg-r-xl"></cv-button><cv-button icon="vant-setting-fill" label="按钮" :active="true" :plain="demoPlain" :disabled="demoDisabled" custom-class="mg-r-xl"></cv-button><cv-button icon="vant-setting-fill" label="按钮" type="primary" :plain="demoPlain" :disabled="demoDisabled" custom-class="mg-r-xl"></cv-button><cv-button icon="vant-setting-fill" label="按钮" type="primary" :active="true" :plain="demoPlain" :disabled="demoDisabled" custom-class="mg-r-xl"></cv-button></div><div class="cv-demo-row"><span class="cv-demo-label f-l f-d3">spin 参数</span><span class="f-d1 mg-r-xs">开启/关闭 spin</span><el-switch v-model="demoSpin" class="mg-r-xl" title="改变 spin 参数"></el-switch><cv-button icon="md-sharp-palette" :spin="demoSpin" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" :spin="demoSpin" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" type="primary" :spin="demoSpin" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" type="primary" :active="true" :spin="demoSpin" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" type="danger" :plain="true" :spin="demoSpin" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" type="danger" :plain="true" :active="true" :spin="demoSpin" custom-class="mg-r-xl"></cv-button><cv-button icon="md-sharp-palette" label="按钮" type="danger" :active="true" :spin="demoSpin" :disabled="true" custom-class="mg-r-xl"></cv-button></div></div>`;



/**
 * 定义组件 SvButtonBak
 * !! 不要手动修改 !!
 */



let SvButtonBak = {
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

SvButtonBak.template = `<button :class="'cv-btn'+(btnClass!=''?' '+btnClass:'')+(asMenuItem?' btn-menuitem':'')+(fullLine?' btn-full-line':(gapInline?' cv-gap-inline':''))+((active||(popMenu&&popmenu.show)||mouse.down)?' btn-active'+(activeClass!=''?' '+activeClass:''):'')+' '+(customClass==''?'':' '+customClass)" :style="btnStyle" :title="title" @click="whenBtnClick" @mouseenter="whenMouseEnter" @mouseleave="whenMouseLeave" @mousedown="whenMouseDown" @mouseup="whenMouseUp"><cv-icon v-if="icon!='' && !iconRight" :icon="spin?'vant-sync':icon" :size="iconSize" :spin="spin"></cv-icon><label v-if="label!=''" :class="(incell?'btn-incell':'')+' '+(asTitle?'btn-astitle':'')" :style="(labelZoom!=1?'font-size:'+labelZoom+'em;':'')+(asMenuItem ? (active ? 'font-weight: bold;' : 'color:'+cssvar.color.f.$+';') : '')">{{label}}</label><cv-icon v-if="icon!='' && iconRight" :icon="spin?'vant-sync':icon" :size="iconSize" :spin="spin"></cv-icon><slot></slot><cv-icon v-if="asMenuItem && popMenuList.length>0 && !active" icon="vant-caret-right" :size="14" :color="popmenu.show ? (type=='default' ? cssvar.color.primary.$ : cssvar.color[type].$) : cssvar.color.f.l3"></cv-icon><cv-icon v-if="asMenuItem && (active || menuItemInactiveIcon!='')" :icon="popMenuList.length>0 ? 'vant-caret-right' : (active ? menuItemActiveIcon : menuItemInactiveIcon)" :size="popMenuList.length>0 ? 14 : iconSize" :style="!active ? 'opacity: 0.5;' : ''"></cv-icon></button>`;



/**
 * 定义组件 SvLogo
 * !! 不要手动修改 !!
 */



let SvLogo = {
    mixins: [mixinBase],
    props: {
        /**
         * logo 使用 io.cgy.design/icon/*
         * 必须 包含 *-light / *-dark 两个图标
         */
        logo: {
            type: String,
            default: 'cgy'
        },

        /**
         * 指定 width 或 height 
         * 则 height 或 width 为 auto
         * 同时指定 height 生效 width 为 auto
         */
        width: {
            type: [String, Number],
            default: ''
        },
        height: {
            type: [String, Number],
            default: ''
        },

        //img alt
        alt: {
            type: String,
            default: ''
        },

        //logo icon url prefix
        urlPrefix: {
            type: String,
            default: 'https://io.cgy.design/icon/'
        },
    },
    data() {return {}},
    computed: {
        //计算得到的 custom-style
        computedStyle() {
            let is = this.$is,
                iss = s => is.string(s),
                isn = n => is.realNumber(n),
                ise = s => (!iss(s) && !isn(s)) || (iss(s) && s=='') || (isn(s) && s<=0),
                w = this.width,
                h = this.height,
                sty = {};
            if (!ise(h)) {
                sty.height = isn(h) ? `${h}px` : h;
                sty.width = 'auto';
            } else if (!ise(w)) {
                sty.width = isn(w) ? `${w}px` : w;
                sty.height = 'auto';
            }

            return this.mixinCustomStyle(sty);
        },

        //处理 logo img src
        logoSrc() {
            if (!this.$is.string(this.logo) || this.logo=='') return '';
            let dark = this.$UI.darkMode,
                logo = this.logo,
                url = this.urlPrefix,
                src = `${url}${logo}${dark ? '-dark' : '-light'}`;
            return src;
        },
    },
    methods: {

    }
}

SvLogo.template = `<img :src="logoSrc" :alt="alt" :class="computedClass" :style="computedStyle">`;



/**
 * 定义组件 SvJsoner
 * !! 不要手动修改 !!
 */



let SvJsoner = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'change'
    },
    props: {
        //v-model 绑定的外部值，需要按 tree 结构进行编辑
        //可以是 json 字符串，plainObject，array
        value: {
            type: [String, Object, Array],
            default: '{}'
        },

        /**
         * 是否只读
         * 只读 即 不允许任何修改，key/value 都不可以修改
         */
        readonly: {
            type: Boolean,
            default: false
        },
        /**
         * 是否作为普通 html 展示数据
         * 数据将不显示在 input 中，可以自动换行，支持 html 样式
         */
        asHtml: {
            type: Boolean,
            default: false
        },

        /**
         * 是否允许修改 json 数据结构
         * 即 是否允许 增删 key
         * 即使不允许修改结构，value 仍可以修改
         */
        //修改 根结构
        rootEditable: {
            type: Boolean,
            default: true
        },
        //修改 子项结构
        subEditable: {
            type: Boolean,
            default: true
        },

        /**
         * 是否与数据库记录关联
         * !!! 如果与 数据库记录 关联 必须提供 table/recordId/idField/update-api 参数
         * 与数据库关联后：
         *      不允许修改根结构，因为 根结构 即 数据表结构
         *      删除操作将使用 __delete__ 标记
         *      字段值 改变后 将出现 确认按钮 点击则立即提交 update-api
         *      
         */
        isRecord: {
            type: Boolean,
            default: false
        },
        recordTable: {
            type: String,
            default: ''
        },
        recordId: {
            type: [String, Number],
            default: ''
        },
        recordIdColumn: {
            type: String,
            default: ''
        },
        //数据库操作 api 前缀，完整的 api 应为 [recordApi]/retrieve|update|delete|info
        recordApi: {
            type: String,
            default: ''
        },

        //是否显示 ctrl 控制栏
        showCtrl: {
            type: Boolean,
            default: true
        },

        //自定义 ctrl 按钮及动作
        customCtrl: {
            type: Array,
            default: ()=>{
                return [
                    /*
                    {
                        btn: {
                            icon: '',
                            label: '',
                            atto-button 参数
                        },
                        click: ()=>{
                            动作
                        }
                    }
                    */
                ];
            }
        },

        //指定 value 列宽度
        valueWidth: {
            type: [String, Number],
            default: '50%'
        },

        //指定 高度，则 tbody 显示滚动条
        height: {
            type: [String, Number],
            default: ''
        },

        //项目列 label 自定义
        keyColumnLabel: {
            type: String,
            default: '项目'
        },
        //内容列 label 自定义
        valueColumnLabel: {
            type: String,
            default: '内容'
        },
        //操作列 label 自定义
        ctrlColumnLabel: {
            type: String,
            default: '操作'
        },

    },

    data: function() {return {
        //value 的内部处理后的缓存，所有编辑都针对此对象
        context: [],
        //context type: object or array
        ctxType: 'object',

        //build 标记
        buildInReady: false,

        //标记是否 emit 导致 value 改变
        emitToValueChange: false,
        
        beenModified: false,
        showJson: false,

        //el width
        elw: 0,

        //全屏显示 标记
        fullscreen: false,
    }},

    computed: {
        jsonHtml() {
            let o = JSON.parse(this.buildJson());
            return JSON.stringify(o, null, 4);
        },

        //经过处理后的 实际显示的 value width px
        valw() {
            let is = this.$is,
                vw = this.valueWidth,
                elw = this.elw,
                rvw = '256px';
            if (vw=='' || is.empty(vw)) return rvw;
            if (is.string(vw) && vw.includes('%')) {
                let vpec = vw.replace('%','');
                vpec = (vpec*1)/100;
                if (elw<=0) return rvw;
                rvw = `${elw*vpec}px`;
            } else if (is.realNumber(vw)) {
                rvw = vw+'px';
            } else if (is.string(vw)) {
                if (vw!='') {
                    rvw = vw;
                }
            }
            return rvw;
        },

        /**
         * 可编辑性 判断
         */
        //可新增 key
        allowAddNewKey() {
            return !this.readonly && this.rootEditable && !this.isRecord;
        },

        //当 asHtml = true 时，输出 slot name {}
        slotName() {
            let is = this.$is,
                ctx = this.context;
            if (is.empty(ctx)) return {};
            let sn = {};
            if (is.array(ctx)) {
                for (let i=0;i<ctx.length;i++) {
                    sn[i] = `ashtml-${i}-extra`;
                }
            } else if (is.plainObject(ctx)) {
                for (let i in ctx) {
                    sn[i] = `ashtml-${i.toLowerCase()}-extra`;
                }
            }
            return sn;
        },
    },

    watch: {
        value(nv, ov) {
            if (this.emitToValueChange) {
                this.emitToValueChange = false;
            } else {
                this.buildContext();
            }
        },
    },

    created() {
        //this.buildContext();
        this.init();
    },

    mounted() {
        this.$nextTick(()=>{
            let el = this.$el;
            if (this.$is.elm(el)) {
                this.$wait(100).then(()=>{
                    this.elw = el.offsetWidth;
                });
            }
        });
    },

    methods: {

        //init/reload
        init() {
            this.buildContext();
            this.$until(()=>{
                return this.$is.elm(this.$el);
            },3000).then(()=>{
                this.elw = this.$el.offsetWidth;
            });
        },

        //json --> context
        buildContext() {
            this.buildInReady = false;
            let json = this.value,
                is = this.$is,
                ctx = is.json(json) ? JSON.parse(json) : (is(json,'array,object') ? json : {});

            //console.log(ctx);
            if (is.array(ctx)) {
                this.ctxType = 'array';
                this.context.splice(0);
                this.context.push(...ctx);
            } else if (is.plainObject(ctx)) {
                this.ctxType = 'object';
                this.context = Object.assign({}, ctx);
            }
            this.$wait(100).then(()=>{
                this.buildInReady = true;
            });
        },

        //context array --> json string
        buildJson() {
            return JSON.stringify(this.context);
        },

        /**
         * 编辑
         */
        //新增 行
        newRow() {
            if (this.readonly) return;
            let kl = this.keyColumnLabel,
                vl = this.valueColumnLabel,
                kv = `新${kl}`,
                vv = `新${vl}`;
            switch (this.ctxType) {
                case 'array':
                    this.context.push(vv);
                    break;
                case 'object':
                    this.$set(this.context, kv,vv);
                    break;
            }
            this.emitChange();
        },
        //删除 行
        delRow(i) {
            if (this.readonly) return;
            switch (this.ctxType) {
                case 'array':
                    this.context.splice(i,1);
                    break;
                case 'object':
                    let ctx = Object.assign({}, this.context);
                    Reflect.deleteProperty(ctx, i);
                    this.context = Object.assign({}, ctx);
                    break;
            }
            this.emitChange();
        },
        //编辑 treekey
        doEditRowKey(okey, nkey) {
            if (this.readonly) return;
            switch (this.ctxType) {
                case 'array':
                    //array key 不可编辑
                    return;
                    break;
                case 'object':
                    /*let tgt = this.context[okey],
                        is = this.$is;
                    if (is.plainObject(tgt)) {
                        let ctxi = Object.assign({}, tgt);
                        this.$set(this.context, nkey, ctxi);
                    } else if (is.array(tgt)) {
                        this.$set(this.context, nkey, []);
                        this.context[nkey].push(...tgt);
                    } else {
                        this.$set(this.context, nkey, tgt);
                    }
                    let ctx = Object.assign({}, this.context);
                    Reflect.deleteProperty(ctx, okey);
                    this.context = Object.assign({}, ctx);*/

                    let tgt = this.context[okey],
                        ctx = null,
                        is = this.$is;
                    if (is.plainObject(tgt)) {
                        ctx = Object.assign({}, tgt);
                    } else if (is.array(tgt)) {
                        ctx = [];
                        ctx.push(...tgt);
                    } else {
                        ctx = tgt;
                    }
                    let octx = Object.assign({},this.context);
                    Reflect.deleteProperty(octx, okey);
                    octx[nkey] = ctx;
                    this.context = Object.assign({}, octx);

                    break;
            }
            this.emitChange();
        },



        //add context key
        /*addProperty() {
            this.context.push({
                key: '',
                value: ''
            });
        },

        //delete context key
        deleteProperty(idx) {
            this.context.splice(idx, 1);
            this.emitChange();
        },

        //on input
        inputKey(idx, event) {
            //console.log(event);
            this.context[idx].key = event.target.value;
            this.beenModified = true;
        },
        inputValue(idx, event) {
            //console.log(event);
            this.context[idx].value = event.target.value;
            this.beenModified = true;
        },*/

        emitChange() {
            let val = this.value,
                is = this.$is,
                emitval = this.context;
            if (is.string(val)) {
                emitval = this.buildJson();
            }
            this.emitToValueChange = true;
            this.$emit('change', emitval);
            this.$emit('input', emitval);
            this.beenModified = false;
        },

        //输出中文序号，①②③④⑤⑥⑦⑧⑨⑩，超过10项，则输出 01-99
        /*showSerialNumber(idx=0) {
            let ss = '①,②,③,④,⑤,⑥,⑦,⑧,⑨,⑩'.split(','),
                ln = this.context.length;
            if (ln<=10) {
                return idx<ln ? ss[idx] : (idx+1)+'';
            }
            let sidx = (idx+1)+'',
                rpt = 2-sidx.length;
            return '0'.repeat(rpt)+sidx;
        },*/

        //点击 customCtrl 指定的 按钮
        customCtrlClick(cci) {
            let is = this.$is,
                clk = cci.click;
            if (!is(clk,'function,asyncfunction')) return false;
            return clk(this);
        },

        //全屏显示 切换
        toggleFullscreen() {
            this.fullscreen = !this.fullscreen;
            //重新计算 valueWidth
            this.$wait(100).then(()=>{
                this.elw = this.$el.offsetWidth;
            });
        },
    }
}

SvJsoner.template = `<div :class="'cv-jsoner'+(fullscreen ? ' fullscreen' : '')+(customClass==''?'':' '+customClass)" :style="(((height!='' && height!='auto') || height>0) ? 'height:'+height+';' : '')+(customStyle==''?'':customStyle)"><div class="thead"><span class="tree-btn"></span><span class="key"><span>{{ keyColumnLabel }}</span><span style="flex:1;"></span><cv-icon icon="md-sharp-drag-indicator" :size="18" custom-class="resize"></cv-icon></span><span class="value" :style="'width:'+valw+';'">{{ valueColumnLabel }}</span><span v-if="!readonly" class="ctrl">{{ ctrlColumnLabel }}</span></div><div v-if="buildInReady" :class="'tbody lvl-0'+((height!='' || height>0) ? ' with-scroll' : '')"><div v-if="$is.empty(context)" class="row empty">无内容</div><template v-if="!$is.empty(context) && ctxType=='array'"><cv-jsoner-row v-for="(cti,ctidx) of context" :key="'cv_jsoner_array_0_1_'+ctidx" v-model="context[ctidx]" :treekey="ctidx" :key-chain="ctidx" :is-array-item="true" :lvl="1" :readonly="readonly" :as-html="asHtml" :root-editable="rootEditable && !isRecord" :sub-editable="subEditable" :key-column-label="keyColumnLabel" :value-column-label="valueColumnLabel" :ctrl-column-label="ctrlColumnLabel" :value-width="valw" @edit-row-key="doEditRowKey" @delete-row="delRow" @change="emitChange"><template v-if="asHtml" v-slot:[slotName[ctidx]]><slot :name="slotName[ctidx]"></slot></template></cv-jsoner-row></template><template v-if="!$is.empty(context) && ctxType=='object'"><cv-jsoner-row v-for="(cti,ctikey) of context" :key="'cv_jsoner_object_0_1_'+ctikey" v-model="context[ctikey]" :treekey="ctikey" :key-chain="ctikey" :lvl="1" :readonly="readonly" :as-html="asHtml" :root-editable="rootEditable && !isRecord" :sub-editable="subEditable" :key-column-label="keyColumnLabel" :value-column-label="valueColumnLabel" :ctrl-column-label="ctrlColumnLabel" :value-width="valw" @edit-row-key="doEditRowKey" @delete-row="delRow" @change="emitChange"><template v-if="asHtml" v-slot:[slotName[ctikey]]><slot :name="slotName[ctikey]"></slot></template></cv-jsoner-row></template></div><div v-if="showCtrl" class="tctrl"><cv-button v-if="allowAddNewKey" icon="vant-plus" :label="'新增'+keyColumnLabel" size="small" type="primary" popout @click="newRow"></cv-button><span class="gap"></span><!--<cv-button v-if="!readonly && beenModified" icon="vant-check" type="danger" label="确认修改" custom-class="btn" @click="emitChange"></cv-button>--><template v-if="!readonly && customCtrl.length>0"><cv-button v-for="(cci,ccidx) of customCtrl" :key="'cv_jsoner_custom_ctrl_'+ccidx" v-bind="cci.btn" size="small" type="primary" popout custom-class="btn" @click="customCtrlClick(cci)"></cv-button></template><cv-button icon="vant-sync" title="刷新" size="small" type="primary" popout custom-class="btn" :spin="!buildInReady" :disable="!buildInReady" @click="init"></cv-button><cv-button :icon="'vant-'+(fullscreen ? 'compress' : 'expend')" :title="fullscreen ? '恢复正常尺寸' : '最大化编辑器'" size="small" type="primary" popout custom-class="btn" @click="toggleFullscreen"></cv-button><cv-button :icon="showJson ? 'vant-eye-close' : 'vant-eye'" :title="(showJson ? '关闭' : '查看')+'JSON'" size="small" type="primary" popout :active="showJson" custom-class="btn" @click="showJson = !showJson"></cv-button><slot name="jsoner-ctrl" :jsoner="$this"></slot></div><div v-if="showJson" class="jsonpre"><pre v-html="jsonHtml" style="white-space: pre; margin: 0;"></pre></div></div>`;



/**
 * 定义组件 SvJsonerRow
 * !! 不要手动修改 !!
 */



let SvJsonerRow = {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'change'
    },
    props: {
        //v-model tree-row 值 value 任意类型
        value: {
            //type: [String, Number, Object, Array],
            default: '',
            required: true
        },

        //tree-row 键 key
        treekey: {
            type: [String, Number],
            default: '',
            required: true
        },
        //key 链
        keyChain: {
            type: [String, Number],
            default: ''
        },
        //当前 row 是否 array 子项
        //是 则不显示 key input
        isArrayItem: {
            type: Boolean,
            default: false
        },

        //当前 tree-row 的递归深度 lvl
        lvl: {
            type: Number,
            default: 0,
            required: true
        },

        /**
         * 是否只读
         * 只读 即 不允许任何修改，key/value 都不可以修改
         */
        readonly: {
            type: Boolean,
            default: false
        },
        /**
         * 是否作为普通 html 展示数据
         * 数据将不显示在 input 中，可以自动换行，支持 html 样式
         */
        asHtml: {
            type: Boolean,
            default: false
        },

        /**
         * 是否允许修改 json 数据结构
         * 即 是否允许 增删 key
         * 即使不允许修改结构，value 仍可以修改
         */
        //修改 根结构
        rootEditable: {
            type: Boolean,
            default: true
        },
        //修改 子项结构
        subEditable: {
            type: Boolean,
            default: true
        },

        //项目列 label 自定义
        keyColumnLabel: {
            type: String,
            default: '项目'
        },
        //内容列 label 自定义
        valueColumnLabel: {
            type: String,
            default: '内容'
        },
        //操作列 label 自定义
        ctrlColumnLabel: {
            type: String,
            default: '操作'
        },
        
        //指定 value 列宽度 由父组件计算得到的 px
        valueWidth: {
            type: String,
            default: ''
        },
    },
    data() {return {
        //内部缓存 值
        context: this.value,
        //内部缓存 键
        ctxkey: this.treekey,

        //如果是可展开类型 array/object 是否展开子项
        subopened: false,

    }},
    computed: {
        //获取 value 类型
        valueType() {
            let is = this.$is,
                val = this.context;
            if (is.boolean(val)) return 'boolean';
            if (is.null(val)) return 'null';
            if (is.plainObject(val)) return 'object';
            if (is.array(val)) return 'array';
            if (is.realNumber(val)) return 'number';
            if (is.string(val)) return 'string';
            return 'undefined';
        },
        //根据 valueType 显示 icon
        valueIcon() {
            let vt = this.valueType,
                vts = {
                    'array': 'md-sharp-format-list-numbered',    //'vant-orderedlist',
                    'object': 'md-sharp-data-object',
                    'null': 'md-sharp-warning',
                };
            if (!this.$is.defined(vts[vt])) return '';
            return vts[vt];
        },

        //value style
        valueSty() {
            let is = this.$is,
                vw = this.valueWidth,
                sty = {};
            if (vw=='' || is.empty(vw) || !is.string(vw)) return sty;
            sty.width = vw;
            return sty;
        },

        //fix keyChain foo.bar.jaz --> foo_bar_jaz
        keyChainFixed() {
            let kc = this.keyChain;
            if (!this.$is.string(kc) || kc=='') return '';
            return kc.replace(/\./g, '_', kc);
        },


        //lvl path
        lvlPath() {
            let lvl = this.lvl;
            if (lvl<=0) return '0';
            return Array.from(Array(lvl)).map((i,idx)=>idx);
        },

        //判断 treekey 是否为空
        emptyKey() {
            let k = this.ctxkey;
            return k === '';
        },

        //给 row/rhead 增加 subopened class
        subopenedCls() {
            return this.subopened ? ' subopened' : '';
        },

        /**
         * 可编辑性 判断
         */
        //新增子项（删除子项）
        allowAddNewSubKey() {
            return !this.readonly && this.subEditable;
        },
        //删除当前项
        allowDelKey() {
            return !this.readonly && this.rootEditable;
        },
    },
    created() {
        if (this.valueTypeIs('null','undefined')) {
            this.context = this.valueType;
        }
    },
    methods: {
        //判断 valueType 
        valueTypeIs(...types) {
            return types.includes(this.valueType);
        },

        /**
         * 显示/隐藏 子项
         */
        toggleSubopened() {
            let empty = this.$is.empty(this.context),
                subo = this.subopened;
            if (empty) {
                //子项内容为空时，调用新增 row 
                this.newRow();
            } else {
                //子项不为空时，toggle subopened
                this.subopened = !subo;
            }
        },

        /**
         * 编辑
         */
        //新增 context[newidx] || context.newkey
        newRow() {
            if (this.readonly) return;
            let vis = this.valueTypeIs,
                kl = this.keyColumnLabel,
                vl = this.valueColumnLabel,
                kv = `新${kl}`,
                vv = `新${vl}`;
            if (vis('array')) {
                this.context.push(vv);
            } else if (vis('object')) {
                this.context = Object.assign(this.context, {
                    [kv]: vv
                });
            }
            this.emitChange();
            this.$wait(100).then(()=>{
                if (this.subopened) this.subopened = false;
                this.subopened = true;
            });
            
        },
        //删除 context[i] || context.i
        delRow(i) {
            if (this.readonly) return;
            let vis = this.valueTypeIs;
            if (vis('array')) {
                this.subopened = false;
                this.context.splice(i,1);
                this.$wait(10).then(() => {
                    this.subopened = true;
                });
            } else if (vis('object')) {
                let ctx = this.context;
                Reflect.deleteProperty(ctx, i);
                this.context = Object.assign({}, ctx);
            }
            this.emitChange();
        },
        //编辑 context
        editRow(event) {
            if (this.readonly) return;
            let val = event.target.value;
            if (this.$is.json(val)) val = JSON.parse(val);
            this.context = val;
            this.emitChange();
            event.target.blur();
        },
        //编辑 treekey
        editRowKey(event) {
            if (this.readonly) return;
            let is = this.$is,
                okey = this.ctxkey,
                nkey = event.target.value,
                rk = event.target.id,
                rka = rk.split('_');

            rka.shift();
            rka.splice(-1,1,nkey);
            rk = rka.join('_');
            event.target.blur();
            //console.log(rk);

            //提交修改
            this.$emit('edit-row-key', okey, nkey);
            
            //auto-focus value input
            this.$wait(100).then(()=>{
                this.focusRowValue(rk);
            });
        },
        //value-input auto-focus
        focusRowValue(relkey) {
            let is = this.$is,
                rk = 'val_'+relkey,
                inps = document.querySelectorAll('input[name=jsoner_row_value]');
            if (inps.length<=0) return;
            let vinps = [];
            for (let inp of inps) {
                if (inp.id && is.string(inp.id) && inp.id == rk) {
                    vinps.push(inp);
                }
            }
            //console.log(rk);
            //console.log(inps);
            //console.log(vinps);
            //return;
            if (vinps.length>0) {
                let vinp = vinps[0];
                //console.log(vinp);
                vinp.focus();
                this.$wait(10).then(()=>{
                    //console.log(document.activeElement);
                    vinp.select();
                });
            }
        },
        //响应子项目的 edit-row-key 事件
        doEditRowKey(okey, nkey) {
            if (this.readonly) return;
            if (this.valueType!='object') return;  //仅 object key 可编辑
            let tgt = this.context[okey],
                is = this.$is;
            if (is.plainObject(tgt)) {
                let ctxi = Object.assign({}, tgt);
                this.$set(this.context, nkey, ctxi);
            } else if (is.array(tgt)) {
                this.$set(this.context, nkey, []);
                this.context[nkey].push(...tgt);
            } else {
                this.$set(this.context, nkey, tgt);
            }
            let ctx = Object.assign({}, this.context);
            Reflect.deleteProperty(ctx, okey);
            this.context = Object.assign({}, ctx);
            this.emitChange();
        },

        //input focus
        inputFocus(event) {
            //console.log(event);
            let inp = event.target;
            if (this.$is.empty(inp)) return;
            if (this.readonly) {
                inp.blur();
            } else {
                inp.select();
            }
        },

        //emitChange
        emitChange() {
            this.$emit('input', this.context);
            this.$emit('change', this.context);
        },
    }
}

SvJsonerRow.template = `<div :class="'row '+valueType+'-value'+subopenedCls"><!-- valueType = string,number,boolean,null,undefined --><template v-if="valueTypeIs('string','number','boolean','null','undefined')"><span class="tree-btn"></span><template v-if="!isArrayItem"><span v-if="!asHtml" class="key"><input type="text" :id="'key_'+keyChainFixed" :title="'key_'+keyChainFixed" name="jsoner_row_key" :value="ctxkey" :readonly="!allowDelKey" :style="!allowDelKey ? 'cursor:not-allowed;' : ''" @change="editRowKey" @focus="inputFocus"></span><span v-else class="key-ashtml" v-html="ctxkey"></span></template><span v-else class="key array-key"><el-tag type="info" size="small" style="font-family:var(--font-fml-code);">{{ ctxkey }}</el-tag></span><span v-if="!asHtml" class="value" :style="valueSty"><el-switch v-if="valueTypeIs('boolean')" v-model="context" :disabled="readonly" :style="readonly ? 'cursor:not-allowed;' : ''" @change="emitChange"></el-switch><input v-else type="text" :id="'val_'+keyChainFixed" :title="'val_'+keyChainFixed" name="jsoner_row_value" :value="context" :readonly="readonly" :style="readonly ? 'cursor:not-allowed;' : ''" @change="editRow" @focus="inputFocus"><el-tag v-if="valueTypeIs('null','undefined')" type="danger" size="small" class="btn">空值</el-tag><!--<el-tag v-if="valueTypeIs('number')" type="info" size="small">数值</el-tag>--></span><span v-else class="value-ashtml" :style="valueSty"><span class="val-row val-tit" v-html="context"></span><slot :name="'ashtml-'+ctxkey.toLowerCase()+'-extra'"></slot></span><span v-if="!readonly" class="ctrl"><cv-button icon="vant-delete" title="删除此项" type="danger" size="small" popout :disabled="emptyKey || !allowDelKey" custom-class="btn" @click="$emit('delete-row', ctxkey)"></cv-button></span></template><!-- valueType = array,object --><div v-if="valueTypeIs('array','object')" :class="'rhead'+subopenedCls"><span class="tree-btn" :style="subopened ? '' : 'border-bottom: none;'"><!--<cv-button :disabled="emptyKey" :icon="subopened ? 'vant-caret-down' : 'vant-caret-right'" :title="subopened ? '折叠此项' : '展开此项'" type="primary" size="small" popout @click="subopened = !subopened"></cv-button>--><cv-icon icon="md-sharp-keyboard-arrow-right" :size="20" :custom-class="'tree-btn-icon'+subopenedCls"></cv-icon></span><span v-if="!isArrayItem" class="key"><input v-if="allowDelKey" type="text" :value="ctxkey" :readonly="!allowDelKey" :style="!allowDelKey ? 'cursor:not-allowed;' : ''" @focus="inputFocus" @change="editRowKey"><span v-else class="readonly-key" v-html="ctxkey"></span></span><span v-else class="key array-key"><el-tag type="info" size="small" style="font-family:var(--font-fml-code);">{{ ctxkey }}</el-tag></span><span class="value" :style="valueSty" :title="($is.empty(context) ? '新增' : (subopened ? '收起' : '展开'))+' '+keyChain+' 子'+keyColumnLabel" @click="toggleSubopened"><!--<el-tag v-if="!subopened && !$is.empty(context)" type="info" size="small">...</el-tag>--><template v-if="!$is.empty(context)"><cv-button v-if="!subopened" icon="md-sharp-more-horiz" type="primary" size="small" popout @click=""></cv-button><cv-button v-if="subopened" icon="md-sharp-expand-less" type="primary" size="small" popout></cv-button></template><template v-if="$is.empty(context)/* && msov*/ && !readonly"><cv-button icon="vant-plus" type="primary" size="small" popout :disabled="emptyKey" custom-class="btn"></cv-button></template><!--<cv-button v-if="!readonly && $is.empty(context) && !subopened" icon="vant-plus" title="添加子项目" type="primary" size="small" popout :disabled="emptyKey" custom-class="btn" @click="newRow"></cv-button>--><span style="flex:1;"></span><el-tag type="info" size="small">{{ valueType=='object' ? '键值' : '数组' }}</el-tag><!--<cv-icon v-if="valueIcon!=''" :icon="valueIcon" :size="18" :title="'类型：'+valueType.ucfirst()" :color="cssvar.color.fc.l1"></cv-icon>--></span><span v-if="!readonly" class="ctrl"><cv-button icon="vant-delete" title="删除此项" type="danger" size="small" popout :disabled="!allowDelKey || emptyKey" custom-class="" @click="$emit('delete-row', ctxkey)"></cv-button></span></div><!-- empty --><div v-if="$is.empty(context) && subopened" :class="'tbody lvl-'+(lvl+1)+subopenedCls"><div class="row empty">无内容</div></div><template v-if="!$is.empty(context) && subopened"><!-- valueType = array --><div v-if="valueTypeIs('array')" :class="'tbody lvl-'+(lvl+1)"><cv-jsoner-row v-for="(vi,vidx) of context" :key="'cv_jsoner_array_'+lvlPath.join('_')+'_'+(lvl+1)+'_'+vidx" v-model="context[vidx]" :treekey="vidx" :key-chain="keyChain+'.'+vidx" :is-array-item="true" :lvl="lvl+1" :readonly="readonly" :as-html="asHtml" :root-editable="allowAddNewSubKey" :sub-editable="true" :key-column-label="keyColumnLabel" :value-column-label="valueColumnLabel" :ctrl-column-label="ctrlColumnLabel" :value-width="valueWidth" @edit-row-key="doEditRowKey" @delete-row="delRow" @change="emitChange"></cv-jsoner-row><div v-if="allowAddNewSubKey" class="row nohover" style="min-height:var(--size-row-l); align-items:center;"><!--<span class="tree-btn"></span>--><cv-button icon="vant-plus" :label="'新增 '+keyChain+' 子'+keyColumnLabel" type="primary" size="small" popout :disabled="emptyKey" custom-class="mg-l-xs" custom-style="font-family:var(--font-fml-code);" @click="newRow"></cv-button></div></div><!-- valueType = object --><div v-if="valueTypeIs('object')" :class="'tbody lvl-'+(lvl+1)"><cv-jsoner-row v-for="(vi,vikey) of context" :key="'cv_jsoner_object_'+lvlPath.join('_')+'_'+(lvl+1)+'_'+vikey" v-model="context[vikey]" :treekey="vikey" :key-chain="keyChain+'.'+vikey" :lvl="lvl+1" :readonly="readonly" :as-html="asHtml" :root-editable="allowAddNewSubKey" :sub-editable="true" :key-column-label="keyColumnLabel" :value-column-label="valueColumnLabel" :ctrl-column-label="ctrlColumnLabel" :value-width="valueWidth" @edit-row-key="doEditRowKey" @delete-row="delRow" @change="emitChange"></cv-jsoner-row><div v-if="allowAddNewSubKey" class="row nohover" style="min-height:var(--size-row-l); align-items:center;"><!--<span class="tree-btn"></span>--><cv-button icon="vant-plus" :label="'新增 '+keyChain+' 子'+keyColumnLabel" type="primary" size="small" popout :disabled="emptyKey" custom-class="mg-l-xs" custom-style="font-family:var(--font-fml-code);" @click="newRow"></cv-button></div></div></template></div>`;



/**
 * 定义组件 SvIcon
 * !! 不要手动修改 !!
 */



let SvIcon = {
    mixins: [mixinBase],
    props: {
        //图标名称，来自加载的图标包，在 cssvar 中指定
        //指定为 -empty- 则显示一个空图标，占据相应尺寸，不显示任何图标
        icon: {
            type: String,
            default: '-empty-'
        },

        /**
         * 样式前缀 cssPre
         */
        cssPre: {
            type: String,
            default: 'icon-'
        },

        //尺寸
        //size: {
        //    type: [String, Number],
        //    default: 'default'
        //},

        //颜色
        //type: {
        //    type: String,
        //    default: ''
        //},
        //强制指定颜色，unset 则不指定颜色，用于显示彩色 icon
        //color: {
        //    type: String,
        //    default: ''
        //},

        //spin
        spin: {
            type: Boolean,
            default: false
        },

        //使用外部 icon 图标，不通过自带 iconPackage 图标包
        //图标来源 https://io.cgy.design/icon/***
        /*useExtraIcon: {
            type: Boolean,
            default: false
        },
        extraIconApi: {
            type: String,
            default: 'https://io.cgy.design/icon/'
        },*/
    },
    data() {
        return {
            
            /**
             * 所有组件公用的 size 可选属性值
             * !! 特殊需要的组件 应在组件内部 覆盖
             */
            /*sizes: {
                huge:       'xxxl',
                large:      'xxl',
                big:        'xl',
                medium:     'm',
                small:      's',
                mini:       'xs'
            }*/
        }
    },
    computed: {

        /**
         * customClass/Style 配套的 计算属性
         * !! 引用的组件内部 应根据需要覆盖
         */
        //计算后的 class override
        computedClass() {
            let is = this.$is,
                sz = this.sizeClass,
                tp = this.typeClass,
                clss = ['cv-icon'];
            if (!is.empty(sz)) clss.push(...sz);
            if (!is.empty(tp)) clss.push(...tp);
            return this.mixinCustomClass(...clss);
        },
        //计算后得到的当前样式 override
        computedStyle() {
            let is = this.$is,
                isd = is.defined,
                isn = is.realNumber,
                icon = this.icon,
                csz = this.cssvar.size.icon,
                sz = this.sizeKey,
                clr = this.colorHex,
                sty = {};
            if (clr!='') sty.color = clr;
            if (isn(sz)) {
                sz += 'px';
            } else if (isd(csz[sz])) {
                sz = csz[sz];
            } else if (is.string(sz) && sz.endsWith('px')){
                sz = sz;
            }else {
                sz = csz.$;
            }
            if (icon=='-empty-') {
                sty.width = sz;
                sty.height = sz;
            } else {
                sty.fontSize = sz;
            }
            return this.mixinCustomStyle(sty);
        },

        /**
         * icon
         */
        iconKey() {
            if (this.icon=='-empty-') return '-empty-';
            if (this.spin) return 'spiner-180-ring';
            return this.icon;
        },

        /**
         * spin
         */
        //计算 spin 中心坐标
        spinCenter() {
            let sty = this.computedStyle,
                sobj = this.$cgy.toCssObj(sty),
                fsz = sobj.fontSize || this.cssvar.size.icon.$,
                r = parseInt(fsz.replace('px',''))/2;
            return ` ${r} ${r}`;
        },
    },
    methods: {

    }
}

SvIcon.template = `<svg :class="computedClass" :style="computedStyle" aria-hidden="true"><use v-if="icon!='-empty-'" v-bind:xlink:href="'#'+iconKey"><!--<animateTransform v-if="spin" attributeName="transform" attributeType="XML" type="rotate" :from="'0'+spinCenter" :to="'360'+spinCenter" dur="1.6s" repeatCount="indefinite" />--></use></svg>`;



/**
 * 定义组件 SvIconDoc
 * !! 不要手动修改 !!
 */



let SvIconDoc = {
    mixins: [mixinBase],
    props: {},
    data() {return {

        //cv-icon 可操作的 参数
        demoParams: {
            icon: 'spiner-wind-toy',
            size: 'medium',
            type: '',
            color: '',
            spin: false,
            customClass: '',
            customStyle: {}
        },
        //针对 demoParams 中 每个参数的 说明
        demoParamsInfo: {
            icon: '图标名称，以 Symbol 方式使用 svg 图标库',
            size: '图标尺寸，可使用尺寸字符串，也可以使用纯数字',
            type: '图标颜色类型，可使用所有主题预定义的颜色名称',
            color: '更多的颜色定义，可使用 cssvar.color 中定义的所有颜色',
            spin: '图标自旋转，常用于 loading 标识',
            customClass: '额外的图标样式类，可使用主题定义的所有可用样式类',
            customStyle: '额外的图表样式，使用 CSS Object'
        },

        demoIcons: [
            'md-sharp-portable-wifi-off', 'md-sharp-keyboard', 
            'spiner-3-dots-scale', 'spiner-wind-toy',
            'vant-setting-fill', 'vant-apple'
        ],
        demoSizes: [
            'xxs','mini','small','medium','large','giant','xxl','xxxl',88,'96px',
            16,17,18,19,20,
        ],
        demoTypes: [
            'primary','danger','warn','success','info','disable',
            'orange','cyan','purple','brand',
            'fc-d3', 'fc', 'bg'
        ],
        demoColors: [
            'primary','primary.l1','primary.l2','primary.l3',
            'red','red.l1','red.l2','red.l3',
            'cyan','cyan.l1','cyan.l2','cyan.l3',
            //this.cssvar.color.fc.d3,
            '#ff0000'
        ],
        demoSpin: false,


        dt: new Date(),
        dts: [],
        num: 0,

    }},
    computed: {},
    methods: {

    }
}

SvIconDoc.template = `<cv-doc component="cv-icon" component-set="base" box-width="540px"><template v-slot:demo-comp-box><cv-icon v-bind="demoParams"></cv-icon></template><template v-slot:demo-comp-ctrl><cv-doc-ctrl prop-key="icon" prop-title="图标名称，以 Symbol 方式使用 svg 图标库"><span><cv-el-tag custom-class="mg-r-s">vant-*</cv-el-tag><cv-el-tag custom-class="mg-r-s">md-sharp-*</cv-el-tag><cv-el-tag icon="btn-shipped" type="light" effect="plain" :hover="true" :separate="true" sep-label="多色图标：" sep-value="btn-*" custom-class="mg-r-s"></cv-el-tag><cv-el-tag type="danger" effect="plain" :separate="true" sep-label="动画图标：" sep-value="spiner-*" custom-class="mg-r-s"></cv-el-tag></span><template v-slot:ctrl-diy><cv-el-select v-model="demoParams.icon" :options="demoIcons" filterable allow-create clearable disabled custom-style="width: 256px;"></cv-el-select><cv-el-tag custom-class="mg-l-s">md-sharp-*</cv-el-tag></template></cv-doc-ctrl><cv-doc-ctrl prop-key="size" prop-title="图标尺寸，可使用尺寸字符串，也可以使用纯数字"><span class="mg-b-xs">可以使用这些尺寸字符串：</span><span class="ctrl-info">mini, small, medium, large, giant</span><span class="ctrl-info">xxs ~ xxxl</span><span class="ctrl-info">48, 54, 64, 72, 88, 96 px</span><span class="mg-t-s">也可以使用出数字，例如：72</span><template v-slot:ctrl-diy><cv-el-select v-model="demoParams.size" filterable allow-create clearable custom-style="width: 256px;"><el-option v-for="szi of demoSizes" :key="'cv_icon_demo_sizes_'+szi" :label="szi" :value="szi"></el-option></cv-el-select><el-date-picker v-model="dt" style="width: 192px;" class="cv-el mg-l-xs" popper-class="cv-el-pop"></el-date-picker><el-date-picker v-model="dt" type="datetime" style="width: 256px;" class="cv-el mg-l-xs" popper-class="cv-el-pop"></el-date-picker><el-date-picker v-model="dts" type="daterange" range-separator="至" start-placeholder="开始" end-placeholder="结束" style="width: 320px;" class="cv-el mg-l-xs" popper-class="cv-el-pop"></el-date-picker><!--<el-input-number v-model="num" style="width: 128px;" class="cv-el mg-l-xs"></el-input-number><el-input v-model="num" style="width: 128px;" class="mg-l-xs"></el-input>--></template></cv-doc-ctrl><cv-doc-ctrl prop-key="type" prop-title="图标颜色类型，可使用所有主题预定义的颜色名称"><span class="mg-b-xs">可以使用这些颜色名称：</span><span class="ctrl-info">primary, danger, info, red, orange, cyan, bg, fc, fc-d3 等</span><span class="ctrl-notice mg-t-s">注意：此参数对多色图标无效！！！</span><template v-slot:ctrl-diy><cv-el-select v-model="demoParams.type" :options="demoTypes" filterable allow-create clearable custom-style="width: 256px;"></cv-el-select></template></cv-doc-ctrl><cv-doc-ctrl prop-key="color" prop-title="更多的颜色定义，可使用 cssvar.color 中定义的所有颜色"><span class="mg-b-xs">可以使用这些颜色名称：</span><span class="ctrl-info">primary.d1, red.$, fc.d3 等</span><span class="ctrl-info"><span>可以通过</span><el-tag size="small" class="f-m f-primary mg-x-s">cgy.loget()</el-tag><span>方法从 cssvar.color 中读取颜色值的 所有可用 key</span></span><span class="mg-t-s"><span>也可以使用 Hex/Rgb 颜色值，例如：</span><el-tag size="small" class="f-m f-primary mg-r-s">#ff0000</el-tag><el-tag size="small" class="f-m f-primary mg-r-s">rgba(123,123,123, 0.7)</el-tag></span><span class="ctrl-notice mg-t-s">注意：此参数对多色图标无效！！！</span><template v-slot:ctrl-diy><cv-el-select v-model="demoParams.color" :options="demoColors" filterable allow-create clearable custom-style="width: 256px;"></cv-el-select></template></cv-doc-ctrl><cv-doc-ctrl prop-key="spin" prop-title="图标自旋转，常用于 loading 状态标识"><span><span>忽略 icon 参数值，一律使用 svg 动画图标：</span><el-tag size="small" class="f-m f-primary">spiner-180-ring</el-tag></span><template v-slot:ctrl-diy><el-switch v-model="demoParams.spin"></el-switch></template></cv-doc-ctrl><cv-doc-ctrl prop-key="customClass" prop-title="额外的图标样式类，可使用主题定义的所有可用样式类"><span>通常用于指定 margin/padding 等</span><template v-slot:ctrl-diy><el-input v-model="demoParams.customClass" placeholder="输入样式类" clearable class="cv-el" style="width: 256px;"></el-input></template></cv-doc-ctrl><cv-doc-ctrl prop-key="customStyle" prop-title="额外的图标样式，使用 CSS Object"><span>通常用于指定特殊样式，如：巨大的图标：fontSize: 256px</span><template v-slot:ctrl-diy><cv-jsoner v-model="demoParams.customStyle" custom-style="width: 480px;"></cv-jsoner></template></cv-doc-ctrl></template></cv-doc>`;



/**
 * 定义组件 SvDesktopShortcut
 * !! 不要手动修改 !!
 */



let SvDesktopShortcut = {
    mixins: [mixinBase__05ImKhAm],
    props: {
        //io.cgy.design/icon/logo-*
        logo: {
            type: String,
            default: 'qq',
            required: true
        },

        //size
        size: {
            type: [String, Number],
            default: 'large'
        },

        //app label
        label: {
            type: String,
            default: '快捷方式'
        },

        //不显示 标签
        noLabel: {
            type: Boolean,
            default: false
        },

        //label 样式
        labelClass: {
            type: String,
            default: ''
        },
        labelStyle: {
            type: [String, Object],
            default: ''
        },
        labelSize: {
            type: [Number, String],
            default: ''
        },
    },
    data() {
        return {
            //size setting
            sizes: {
                giant: {
                    body: 96,
                    f: '$',
                    radi: 12,
                },
                large: {
                    body: 80,
                    f: 's',
                    radi: 10,
                },
                medium: {
                    body: 64,
                    f: 's',
                    radi: 8,
                },
                small: {
                    body: 48,
                    f: 's',
                    radi: 6,
                },
                mini: {
                    body: 32,
                    f: 'xs',
                    radi: 4,
                }
            },
        }
    },
    computed: {
        //shortcut style
        scutSty() {
            let is = this.$is,
                ext = this.$extend,
                fix = n => !isNaN(n*1) ? n+'px' : n,
                sz = this.size,
                szs = this.sizes,
                szi = szs[sz] || szs.medium,
                csty = this.customStyle,
                sty = {
                    //width: fix(szi.body),
                    borderRadius: fix(szi.radi)
                };
            if (is.plainObject(csty)) {
                sty = ext(sty, csty);
            }
            sty = this.$cgy.toCssString(sty);
            if (is.string(csty)) {
                sty = `${sty} ${csty}`;
            }
            return sty;
        },
        //label style
        labelSty() {
            let is = this.$is,
                ext = this.$extend,
                fix = n => !isNaN(n*1) ? n+'px' : n,
                cssv = this.cssvar.size.f,
                sz = this.size,
                szs = this.sizes,
                szi = szs[sz] || szs.medium,
                lsty = this.labelStyle,
                lsz = this.labelSize,
                fsz = (lsz=='' || lsz<=0) ? fix(cssv[szi.f]) : fix(lsz),
                sty = {
                    width: fix(szi.body),
                    fontSize: fsz,
                    marginBottom: fix(szi.radi),
                };
            if (is.plainObject(lsty)) {
                sty = ext(sty, lsty);
            }
            sty = this.$cgy.toCssString(sty);
            if (is.string(lsty)) {
                sty = `${sty} ${lsty}`;
            }
            return sty;
        },
        
    },
    methods: {
        //点击时
        whenShortcutClick(evt) {
            console.log(evt);
            console.log('shortcut click');

            this.$invoke('cv-panel', {
                isPopup: true,
                popShow: true,
                popMask: false,
                showBorder: true,
                showShadow: true,

                gap: 'compact',

                width: 640,
                height: 540,
            });
        },
    }
}

SvDesktopShortcut.template = `<div :class="'cv-desk-shortcut '+(customClass==''?'':customClass)" :style="scutSty" @click="whenShortcutClick"><cv-desktop-applogo :logo="logo" :size="size" :img-shadow="true" v-bind="$attrs"></cv-desktop-applogo><span v-if="!noLabel" :class="'cv-desk-shortcut-label '+(labelClass==''?'':labelClass)" :style="labelSty">{{label}}</span></div>`;



/**
 * 定义组件 SvDesktopApplogo
 * !! 不要手动修改 !!
 */



let SvDesktopApplogo = {
    mixins: [mixinBase__05ImKhAm],
    props: {
        //io.cgy.design/icon/logo-*
        logo: {
            type: String,
            default: 'qq',
            required: true
        },

        //background color
        background: {
            type: String,
            default: 'transparent'
        },

        //size
        size: {
            type: [String, Number],
            default: 'medium'
        },

        //img
        imgStyle: {
            type: [String, Object],
            default: ''
        },
        imgSize: {
            type: [String, Number],
            default: ''
        },
        //drop-shadow
        imgShadow: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            //size setting
            sizes: {
                giant: {
                    body: 72,
                    icon: 48,
                    radi: 12,
                },
                large: {
                    body: 60,
                    icon: 40,
                    radi: 10,
                },
                medium: {
                    body: 48,
                    icon: 32,
                    radi: 8,
                },
                small: {
                    body: 36,
                    icon: 24,
                    radi: 6,
                },
                mini: {
                    body: 28,
                    icon: 20,
                    radi: 4,
                }
            },
        }
    },
    computed: {
        //logo style
        logoSty() {
            let is = this.$is,
                ext = this.$extend,
                fix = n => !isNaN(n*1) ? n+'px' : n,
                sz = this.size,
                szs = this.sizes,
                szi = szs[sz] || szs.medium,
                bgc = this.background,
                csty = this.customStyle,
                sty = {
                    width: fix(szi.body),
                    height: fix(szi.body),
                    borderRadius: fix(szi.radi),
                    background: bgc,
                };
            if (is.plainObject(csty)) {
                sty = ext(sty, csty);
            }
            sty = this.$cgy.toCssString(sty);
            if (is.string(csty)) {
                sty = `${sty} ${csty}`;
            }
            return sty;
        },
        //img style
        imgSty() {
            let is = this.$is,
                ext = this.$extend,
                fix = n => !isNaN(n*1) ? n+'px' : n,
                sz = this.size,
                szs = this.sizes,
                szi = szs[sz] || szs.medium,
                isty = this.imgStyle,
                isz = this.imgSize,
                isn = (isz=='' || isz<=0) ? fix(szi.icon) : fix(isz),
                sty = {
                    width: isn,
                    height: 'auto',
                };
            if (is.plainObject(isty)) {
                sty = ext(sty, isty);
            }
            sty = this.$cgy.toCssString(sty);
            if (is.string(isty)) {
                sty = `${sty} ${isty}`;
            }
            return sty;
        },

        //src
        imgSrc() {
            let logo = this.logo;
            if (logo.startsWith('http') || logo.includes('//')) return logo;
            let pre = 'https://io.cgy.design';
            if (logo.startsWith('logo-')) return `${pre}/icon/${logo}`;
            if (logo.startsWith('icon/')) return `${pre}/${logo}`;
            return `${pre}/icon/logo-${logo}`;
        },
    },
    methods: {

    }
}

SvDesktopApplogo.template = `<div :class="'cv-desk-app-logo '+(customClass==''?'':customClass)" :style="logoSty"><img :src="imgSrc" :class="imgShadow?'cv-desk-img-shadow':''" :style="imgSty"></div>`;



/**
 * 定义组件 SvDesktopWintest
 * !! 不要手动修改 !!
 */


let SvDesktopWintest = {

}

SvDesktopWintest.template = ` `;



/**
 * 定义组件 SvDesktop
 * !! 不要手动修改 !!
 */



let SvDesktop = {
    mixins: [mixinBase__05ImKhAm],
    props: {

        /**
         * ui
         */
        //wallpaper
        wallpaper: {
            type: String,
            default: ''
        },

        /**
         * shortcuts
         */
        shortcuts: {
            type: Array,
            default: ()=>[]
        },

    },
    data() {
        return {
            /**
             * win 窗口列表
             */
            wins: [
                /*{
                    
                }*/
            ],
            winInitOptions: {
                ins: null,      //win 组件实例
                idx: -1,        //win idx 在列表中的序号
                active: false,      //win 获得焦点标记
                minimize: false,    //win 最小化标记
                maxmize: false,     //win 最大化标记
                //win 样式参数
                zIndex: 10,
                pos: {
                    x: 0,
                    y: 0,
                },
                size: {
                    w: 0,
                    h: 0
                },
                //win 其他参数
                win: {},
                //win 内部功能组件 参数
                comp: {
                    name: '',
                    params: {},
                }
            },
            //默认的 win 其他参数
            winInitParams: {
                icon: 'md-sharp-desktop-windows',
                title: '新建窗口',

            },
        }
    },
    computed: {
        /**
         * desktop style obj {}
         */
        deskSty() {
            let is = this.$is,
                ext = this.$extend,
                csty = this.customStyle,
                sty = ext(
                    {},
                    this.deskBgSty
                );
            if (is.plainObject(csty)) {
                sty = ext(sty, csty);
            }
            sty = this.$cgy.toCssString(sty);
            if (is.string(csty)) {
                sty = `${sty} ${csty}`;
            }
            return sty;
        },
        deskBgSty() {
            let cssv = this.cssvar;
            return {
                backgroundColor: cssv.color.bg,
                backgroundImage: 'url(https://io.cgy.design/src/cgy/desktop-bg.jpg)',
                backgroundSize: '100% auto',
                backgroundRepeat: 'no-repeat',
                backgroundPosition: 'center center'
            };
        },

        //desktop style
        deskWidth() {
            let is = this.$is;
            if (!is.elm(this.$el)) return 0;

        },


        /**
         * win 窗口列表
         */
        //当前获得焦点的 win 窗口
        currentWin() {
            let is = this.$is,
                wins = this.wins,
                idx = -1;
            for (let win of wins) {
                if (win.active==true) {
                    idx = win.idx;
                }
            }
            if (idx<0) return null;
            return this.wins[idx];
        },
        //获取 wins 窗口列表的 z-index 数组
        zIndexs() {
            if (this.wins.length<=0) return [];
            return this.wins.map(i=>i.zIndex);
        },
        //获取 wins 窗口列表中 最大的 z-index
        maxZIndex() {
            let zidxs = this.zIndexs;
            if (zidxs.length<=0) return 10; //最小 10
            let max = 0;
            for (let zidx of zidxs) {
                if (zidx>max) {
                    max = zidx;
                }
            }
            return max;
        },


    },
    created() {
        //dev 自动创建一个窗口
        /*this.createWin({
            size: {w: 320, h:320},
            win: {
                icon: 'Apple',
                title:'窗口 01',
            }
        });
        this.$wait(200).then(()=>{
            this.createWin({
                size: {w:480, h:360},
                win: {
                    icon: 'pocket',
                    title:'窗口 02',
                }
            });
        });
        this.$wait(400).then(()=>{
            this.createWin({
                size: {w:540, h:480},
                win: {
                    icon: 'qq',
                    title:'窗口 03',
                }
            });
        });
        this.$wait(600).then(()=>{
            this.createWin({
                size: {w:640, h:540},
                win: {
                    icon: 'whatsapp',
                    title:'窗口 04',
                }
            });
        });
        this.$wait(800).then(()=>{
            this.createWin({
                size: {w:960, h:640},
                win: {
                    icon: 'youtube',
                    title:'窗口 05',
                }
            });
        });*/
        
    },
    mounted() {
        this.$nextTick(()=>{
            //this.$wait(500).then(()=>{
            //    this.$UI.awaitDanger('ceshi','await danger').then(()=>{
            //        console.log('await fullfill');
            //    });
            //});
        });
    },
    methods: {

        //this.$el ready


        /**
         * 窗口管理
         */
        /**
         * 创建窗口
         */
        async createWin(opt={}) {
            let is = this.$is,
                ext = this.$extend,
                wins = this.wins,
                widx = wins.length,
                win = ext({
                    win: ext({
                        //默认的 win 组件事件处理
                        customEventHandler: this.winCustomEventHandler()
                    }, this.winInitParams),
                }, this.winInitOptions, opt),
                //自动计算初始 win 样式参数
                sty = await this.getInitWinSty(win);
            //配置其他参数
            win = ext(win, {
                //在 wins 列表中的序号
                idx: widx,
            }, sty);
            //添加到 wins 列表
            wins.push(win);
            await this.$wait(10);
            //新建窗口 自动获得焦点
            await this.activeWin(widx);
            //完成
            return true;
        },
        //创建 win 时，计算初始显示参数
        async getInitWinSty(win={}) {
            await this.elReady();
            let is = this.$is,
                ext = this.$extend,
                el = this.$el,
                desk = {
                    w: el.offsetWidth,
                    h: el.offsetHeight
                },
                {pos,size} = win,
                {x,y} = pos,
                {w,h} = size;
            if (w<=0) w = desk.w * 0.5;
            if (h<=0) h = desk.h * 0.6;
            if (x<=0) x = (desk.w-w)/2;
            if (y<=0) y = (desk.h-h)/2;
            return {
                //设置 z-index 当前最大 +1
                zIndex: this.maxZIndex + 1,
                pos: {x,y},
                size: {w,h}
            }
        },
        //某个 win 获得焦点
        async activeWin(winidx=-1) {
            if (winidx<0) return false;
            let is = this.$is,
                wins = this.wins,
                cwin = this.currentWin;
            if (!is.defined(wins[winidx]) || (!is.null(cwin) && cwin.idx==winidx)) return false;
            //取消 已获得焦点的窗口
            for (let win of wins) {
                if (win.idx==winidx) continue;
                if (win.active==true) {
                    this.$set(this.wins[win.idx], 'active', false);
                }
            }
            await this.$wait(10);
            //设置焦点
            this.$set(this.wins[winidx], 'active', true);
            //设置 zIndex 为当前最大 +1
            this.$set(this.wins[winidx], 'zIndex', this.maxZIndex + 1);
            //整体刷新 zIndex 序列
            await this.resetZIndex();
            return true;
        },
        //计算 win 叠放次序 z-index 每当有 win 开启/关闭/获得焦点/失去焦点 时执行一次 防止 zIndex 数值过于庞大
        async resetZIndex() {
            let min = 10,   //z-index 最小值
                //构建一个数组，只包含 每个 win 的 idx 和 zIndex 参数
                zidxs = this.wins.map(i=>{
                    return {
                        idx: i.idx,
                        zIndex: i.zIndex
                    }
                });
            //按 zIndex 排序这个数组，从小到大
            zidxs.sort((a,b)=>{
                return a.zIndex - b.zIndex;
            });
            //从 10 开始 按顺序重设 zIndex 的值
            for (let i=0;i<zidxs.length;i++) {
                let widx = zidxs[i].idx,
                    zidx = min + i + 1;
                this.$set(this.wins[widx], 'zIndex', zidx);
            }
            await this.$wait(10);
            return true;
        },
        //设置某个 win 的 参数
        async setWin(winidx=-1, d={}) {
            let is = this.$is,
                ext = this.$extend;
            if (winidx<0 || !is.defined(this.wins[winidx])) return false;
            if (!is.plainObject(d) || is.empty(d)) return false;
            let owin = ext({}, this.wins[winidx]);
            owin = ext(owin, d);
            this.wins.splice(winidx, 1, owin);
            await this.$wait(10);
            return true;
        },
        //默认的 win 事件处理
        winCustomEventHandler() {
            return {
                //win 窗口初次创建
                'win-ready': win => {
                    //保存 win 组件实例
                    let widx = win.winidx;
                    this.$set(this.wins[widx], 'ins', win);
                },
                //点击 win 窗口使其获得焦点
                'win-active': win => {
                    let widx = win.winidx;
                    this.activeWin(widx);
                },
                //win 移动了
                'win-pos-change': (win, pos) => {
                    let widx = win.winidx;
                    this.$set(this.wins[widx], 'pos', Object.assign({}, pos));
                }
            }
        },
    }
}

SvDesktop.template = `<div :class="'cv-desktop '+(customClass==''?'':customClass)" :style="deskSty"><template v-if="shortcuts.length>0"><cv-desktop-shortcut v-for="(sci,scidx) of shortcuts" :key="'cv_desktop_short_'+scidx" v-bind="sci"></cv-desktop-shortcut></template><slot></slot><template v-if="wins.length>0"><cv-desktop-win v-for="(win,winidx) of wins" :key="'cv_desktop_win_'+winidx" v-bind="win.win" :winidx="win.idx" :active="win.active" :minimize="win.minimize" :maxmize="win.maxmize" :z-index="win.zIndex" :pos-x="win.pos.x" :pos-y="win.pos.y" :size-w="win.size.w" :size-h="win.size.h" :component="win.comp"></cv-desktop-win></template></div>`;



/**
 * 定义组件 SvDesktopWin
 * !! 不要手动修改 !!
 */



let SvDesktopWin = {
    mixins: [mixinBase__05ImKhAm],
    props: {
        //win 序号
        winidx: {
            type: Number,
            default: 0
        },

        //icon
        icon: {
            type: String,
            default: 'icon/md-sharp-desktop-windows'
        },
        //title
        title: {
            type: String,
            default: '新建窗口'
        },

        /**
         * 窗口状态
         */
        //此窗口是否获得焦点
        active: {
            type: Boolean,
            default: false,
        },
        //最小化
        minimize: {
            type: Boolean,
            default: false,
        },
        //最大化
        maxmize: {
            type: Boolean,
            default: false,
        },

        /**
         * ui
         */
        zIndex: {
            type: Number,
            default: 10
        },
        posX: {
            type: [Number, String],
            default: 0
        },
        posY: {
            type: [Number, String],
            default: 0
        },
        sizeW: {
            type: [Number, String],
            default: 0
        },
        sizeH: {
            type: [Number, String],
            default: 0
        },
        //是否允许拖拽移动
        dragMoveable: {
            type: Boolean,
            default: true
        },
    },
    data() {
        return {
            /**
             * ui
             */
        }
    },
    computed: {
        /**
         * desktop-win style
         */
        
    },
    watch: {
        posX(nv, ov) {this.setWinPos({x: this.posX});},
        posY(nv, ov) {this.setWinPos({y: this.posY});},
        sizeW(nv, ov) {this.setWinSize({w: this.sizeW});},
        sizeH(nv, ov) {this.setWinSize({h: this.sizeH});},
    },
    created() {},
    mounted() {
        this.$nextTick(()=>{
            this.$ev('win-ready', this);
            this.$wait(10).then(()=>{
                this.setWinPos({
                    x: this.posX,
                    y: this.posY
                });
                this.setWinSize({
                    w: this.sizeW,
                    h: this.sizeH
                });
            });
        });
    },
    methods: {
        /**
         * ui
         */
        //设置 pos
        async setWinPos(pos={}) {
            await this.elReady();
            let is = this.$is,
                fix = n => !isNaN(n*1) ? n+'px' : n;
            if (is.defined(pos.x)) this.$el.style.left = fix(pos.x);
            if (is.defined(pos.y)) this.$el.style.top = fix(pos.y);
        },
        //设置 size
        async setWinSize(size={}) {
            await this.elReady();
            let is = this.$is,
                fix = n => !isNaN(n*1) ? n+'px' : n;
            if (is.defined(size.w)) this.$el.style.width = fix(size.w);
            if (is.defined(size.h)) this.$el.style.height = fix(size.h);
        },
        //拖拽移动
        whenDragMove(target) {
            //console.log(pos);
        },
        //拖拽移动完成
        afterDragMove(target) {
            let x = target.offsetLeft,
                y = target.offsetTop;
            //通知 desktop 父组件 win 的新 pos
            this.$ev('win-pos-change', this, {x,y});
        },
        
    }
}

SvDesktopWin.template = `<div :class="'cv-desk-win '+(customClass==''?'':customClass)" :style="'z-index:'+zIndex+';'" @mousedown="$ev('win-active', $this)"><div class="cv-win-titbar" v-drag-move:xy="$this"><cv-desktop-applogo :logo="icon" size="mini" custom-class="cv-win-icon"></cv-desktop-applogo><span class="f-d3 f-m f-bold mg-r-m">{{ title }}</span><span class="f-s mg-r-m">extra</span><slot name="titbar-left-ctrl"></slot><span class="flex-1"></span><slot name="titbar-right-ctrl"></slot><cv-button icon="md-sharp-remove" popout="dark"></cv-button><cv-button icon="md-sharp-crop-square" popout="dark"></cv-button><cv-button icon="md-sharp-close" type="danger" popout></cv-button></div></div>`;



/**
 * 定义为全局组件
 * !! 不要手动修改 !!
 */

let SvNavbarComp = Vue.component('sv-navbar', SvNavbar);
let SvElSliderComp = Vue.component('sv-el-slider', SvElSlider);
let SvElSwitchComp = Vue.component('sv-el-switch', SvElSwitch);
let SvElInputNumberComp = Vue.component('sv-el-input-number', SvElInputNumber);
let SvElDatePickerComp = Vue.component('sv-el-date-picker', SvElDatePicker);
let SvElSelectComp = Vue.component('sv-el-select', SvElSelect);
let SvElInputComp = Vue.component('sv-el-input', SvElInput);
let SvElTagComp = Vue.component('sv-el-tag', SvElTag);
let SvElSwitchDarkmodeComp = Vue.component('sv-el-switch-darkmode', SvElSwitchDarkmode);
let SvElTimePickerComp = Vue.component('sv-el-time-picker', SvElTimePicker);
let SvElDocComp = Vue.component('sv-el-doc', SvElDoc);
let SvButtonComp = Vue.component('sv-button', SvButton);
let SvButtonDemoComp = Vue.component('sv-button-demo', SvButtonDemo);
let SvButtonBakComp = Vue.component('sv-button-bak', SvButtonBak);
let SvLogoComp = Vue.component('sv-logo', SvLogo);
let SvJsonerComp = Vue.component('sv-jsoner', SvJsoner);
let SvJsonerRowComp = Vue.component('sv-jsoner-row', SvJsonerRow);
let SvIconComp = Vue.component('sv-icon', SvIcon);
let SvIconDocComp = Vue.component('sv-icon-doc', SvIconDoc);
let SvDesktopShortcutComp = Vue.component('sv-desktop-shortcut', SvDesktopShortcut);
let SvDesktopApplogoComp = Vue.component('sv-desktop-applogo', SvDesktopApplogo);
let SvDesktopWintestComp = Vue.component('sv-desktop-wintest', SvDesktopWintest);
let SvDesktopComp = Vue.component('sv-desktop', SvDesktop);
let SvDesktopWinComp = Vue.component('sv-desktop-win', SvDesktopWin);



/**
 * ESM 输出
 * !! 不要手动修改 !!
 */

export default {
SvNavbar: SvNavbarComp,
SvElSlider: SvElSliderComp,
SvElSwitch: SvElSwitchComp,
SvElInputNumber: SvElInputNumberComp,
SvElDatePicker: SvElDatePickerComp,
SvElSelect: SvElSelectComp,
SvElInput: SvElInputComp,
SvElTag: SvElTagComp,
SvElSwitchDarkmode: SvElSwitchDarkmodeComp,
SvElTimePicker: SvElTimePickerComp,
SvElDoc: SvElDocComp,
SvButton: SvButtonComp,
SvButtonDemo: SvButtonDemoComp,
SvButtonBak: SvButtonBakComp,
SvLogo: SvLogoComp,
SvJsoner: SvJsonerComp,
SvJsonerRow: SvJsonerRowComp,
SvIcon: SvIconComp,
SvIconDoc: SvIconDocComp,
SvDesktopShortcut: SvDesktopShortcutComp,
SvDesktopApplogo: SvDesktopApplogoComp,
SvDesktopWintest: SvDesktopWintestComp,
SvDesktop: SvDesktopComp,
SvDesktopWin: SvDesktopWinComp,
}


