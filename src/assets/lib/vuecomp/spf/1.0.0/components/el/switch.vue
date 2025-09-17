<template>
    <div
        :class="computedClass"
        :style="computedStyle"
        :title="title"
        @click="doSwitch"
    >
        <el-switch
            v-model="cacheValue"
            v-bind="$attrs"
            :activeColor="actHex"
            :inactiveColor="inactHex"
            :disabled="swDisabled"
            :class="swClass"
            :style="swStyle"
        ></el-switch>
        <div class="cv-el-switch-cover">
            <cv-icon
                v-if="useIcon"
                :icon="icons.active"
                :size="iconSt.active.size"
                :color="actFHex"
                :spin="iconSt.active.spin"
                custom-class="cv-el-switch-icon-left"
            ></cv-icon>
            <span 
                v-if="useText"
                class="cv-el-switch-text-left"
                :style="activeTextStyle"
            >{{ cacheValue==true ? activeText : '' }}</span>
            <span class="flex-1"></span>
            <cv-icon
                v-if="useIcon"
                :icon="icons.inactive"
                :size="iconSt.inactive.size"
                :color="inactFHex"
                :spin="iconSt.inactive.spin"
                custom-class="cv-el-switch-icon-right"
            ></cv-icon>
            <span 
                v-if="useText"
                class="cv-el-switch-text-right"
                :style="inactiveTextStyle"
            >{{ cacheValue==false ? inactiveText : '' }}</span>
        </div>
        <slot></slot>
    </div>
</template>

<script>
import mixinBase from '/vue/@/mixins/base/base';

export default {
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
</script>

<style>

</style>