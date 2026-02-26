<template>
    <div
        :class="styComputedClassStr.root"
        :style="styComputedStyleStr.root"
    >
        <template v-if="colLength>0">
            <template v-for="(si, idx) in colLength">
                <div
                    v-if="colOpt[idx] && colOpt[idx].resize && colOpt[idx].resize===true"
                    :key="'layout-x-col-'+idx"
                    :class="colClassStr[idx]"
                    :style="colStyleStr[idx]"
                    v-drag-resize:x="{
                        inComponent: $this,
                        limit: {
                            x: [colOpt[idx].resizeMin, colOpt[idx].resizeMax]
                        },
                        idx: idx,
                    }"
                >
                    <slot :name="'col-'+idx" v-bind="{styProps, colIdx: idx, layoutXer: $this}"></slot>
                </div>
                <template v-else>
                    <div
                        v-if="colOpt[idx] && colOpt[idx].compact && colOpt[idx].compact===true"
                        :key="'layout-x-col-'+idx+'-wrapper'"
                        class="flex-y flex-no-shrink"
                        style="position: relative; height: 100%; overflow: visible;"
                    >
                        <div
                            :key="'layout-x-col-'+idx"
                            :class="colClassStr[idx]"
                            :style="colStyleStr[idx]"
                        >
                            <slot 
                                v-if="colOpt[idx].differentSlot!==true" 
                                :name="'col-'+idx" 
                                v-bind="{styProps, colIdx: idx, layoutXer: $this}"
                            ></slot>
                            <template v-else>
                                <slot 
                                    v-if="compactedCols[idx] && compactedCols[idx]===true" 
                                    :name="'col-'+idx+'-compacted'" 
                                    v-bind="{styProps, colIdx: idx, layoutXer: $this}"
                                ></slot>
                                <slot 
                                    v-else 
                                    :name="'col-'+idx" 
                                    v-bind="{styProps, colIdx: idx, layoutXer: $this}"
                                ></slot>
                            </template>
                        </div>
                        <div
                            :class="compactBtnClassStr[idx]"
                            :style="compactBtnStyleStr[idx]"
                            :title="(compactedCols[idx] && compactedCols[idx]===true) ? '展开':'折叠'"
                            @click="toggleCompact(idx)"
                        >
                            <PRE@-icon
                                :icon="compactBtnIcon[idx]"
                                size="small"
                            ></PRE@-icon>
                        </div>
                    </div>
                    <div
                        v-else
                        :key="'layout-x-col-'+idx"
                        :class="colClassStr[idx]"
                        :style="colStyleStr[idx]"
                    ><slot :name="'col-'+idx" v-bind="{styProps, colIdx: idx, layoutXer: $this}"></slot></div>
                </template>
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
         * 可指定水平方向的子元素各自占用宽度比例
         * 可有下列指定方式：
         *      '*'             默认，1 个子元素，应用 .flex-1 原子类
         *      '20%,*'         2 个子元素，第一个宽度 20%， 第二个应用 .flex-1 原子类
         *      '*,256px'       2 个子元素，第一个应用 .flex-1 原子类，第二个宽度 256px
         *      '20%,70%,10%'   3 个子元素，各自占用对应的 % 宽度
         *      '1,4,2'         3 个子元素，分别应用 .flex-n 原子类
         */
        col: {
            type: [String, Array],
            default: '*'
        },

        /**
         * 定义 col 子元素的通用 class[]|style{} 由 base-style 系统调用
         *      class   可以是 string|array
         *      style   可以是 string|object
         */
        colClass: {
            type: [String, Array],
            default: ''
        },
        colStyle: {
            type: [String, Object],
            default: ''
        },
        /**
         * 可额外定义 col 子元素各自的 class[]|style{}
         * 数组格式，长度与 colLength 一致，并对应
         *      class[]     支持 []|string
         *      style{}     支持 {}|string
         */
        colExtraClass: {
            type: Array,
            default: () => []
        },
        colExtraStyle: {
            type: Array,
            default: () => []
        },

        /**
         * compact 指定可 自动收缩|展开 的 col 
         * !! col 的宽度设置值 不能是 * 或 flex-n 原子类，必须是某个确定的宽度值 256px | 30%
         * 指定 col 序号，从 0 开始，一个(Number) 或 多个(Array)
         * 指定了 compact 的 col 可通过点击 收缩|展开 按钮 自动 收缩|展开
         * 
         * !! compact 优先级高于 resize，即某个 col 如果指定了 compact 则 resize 参数无效
         */
        compact: {
            type: [Number, Array],
            default: -1
        },
        /**
         * 指定收缩后的最小宽度 '32px' 或 ['32px', '5%']
         * 如果传入数组，则顺序必须与 compact 数组顺序一致 
         */
        compactTo: {
            type: [String, Array],
            default: ''
        },
        /**
         * 是否在 某个 col 进入 compact 状态时，使用不同的 slot
         * 如果传入数组，则顺序必须与 compact 数组顺序一致 
         */
        differentCompactSlot: {
            type: [Boolean, Array],
            default: false
        },

        /**
         * resize 指定可通过拖拽调整宽度的 col
         * !! col 的宽度设置值 不能是 * 或 flex-n 原子类，必须是某个确定的宽度值 256px | 30%
         * 指定 col 序号，从 0 开始，一个(Number) 或 多个(Array)
         * 指定了 resize 的 col 将在元素的右侧边缘自动生成可拖拽的 handler 元素
         */
        resize: {
            type: [Number, Array],
            default: -1
        },
        /**
         * 指定 resize 宽度调整的 上下限 可以是 '32px' 或 ['32px', '5%']
         * 如果传入数组，则顺序必须与 resize 数组顺序一致 
         * !! 可以不指定
         */
        //宽度下限
        resizeMin: {
            type: [String, Array],
            default: ''
        },
        //宽度上限
        resizeMax: {
            type: [String, Array],
            default: ''
        },

        //是否显示内部组件的 边框
        innerBorder: {
            type: Boolean,
            default: false
        },
    },
    data() {return {
        //覆盖 base-style 样式系统参数
        sty: {
            init: {
                class: {
                    root: '__PRE__-layout-x flex-x flex-x-start flex-y-stretch flex-no-shrink flex-1 stretch-row',
                    //指定 col 子元素 通用的初始 class[]
                    col: '__PRE__-layout-x-col flex-y flex-no-shrink scroll-y scroll-thin',
                    //指定 compact btn 子元素 通用的 初始 class[]
                    compact: '__PRE__-layout-x-col-compact-btn flex-x flex-x-center flex-y-center flex-no-shrink',
                },
                style: {
                    root: '',
                    //指定 col 子元素 通用的初始 style{}
                    col: '',
                    //指定 compact btn 子元素 通用的初始 style{}
                    compact: '',
                },
            },
            prefix: 'layout-x',
            sub: {
                size: true,
                color: true,
                //animate: 'disabled:false',
            },
            switch: {
                //启用 下列样式开关
                //effect:     '.bar-effect effect-{swv}',
                //stretch:    '.stretch-{swv}',
                //tightness:  '.bar-tightness tightness-{swv}',
                //shape:      '.bar-shape shape-{swv}',
                //'hoverable:disabled': '.hoverable',
                //active:     '.active',
                //grow: '.flex-1',
                //根元素上挂载 scroll-y
                //rootScroll: '.scroll-y scroll-{swv}',
            },
            csvKey: {
                size: 'bar',
                color: 'bgc',
            },
        },

        //某个 col 子元素的 默认参数格式
        defaultCol: {
            //此 col 在 colArr 中的序号 0 开始
            idx: -1,
            //在 colExtraClass|Style 中定义的 各 col 额外的 class[]|style{}
            extra: {
                class: [],  //!! 一定是 []
                style: {},  //!! 一定是 {}
            },

            //在 props.col 中定义的 col 初始宽度设定 可能是 128px|35%|2|*
            set: '',

            //compact 自动缩放参数
            compact: false,
            compactTo: '',
            differentSlot: false,

            //resize 宽度调整参数
            resize: false,
            resizeMin: '',
            resizeMax: '',
        },

        //col 当前的 compact 状态，[true, false, ...] 形式
        compactedCols: [], 

        //col 当前的 resize 状态，未被 resize 设为 false 否则设为 resize 后的 width 尺寸值 带单位
        resizedCols: [],
    }},
    computed: {
        //将 col 参数转为 []
        colArr() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                col = this.col;
            if (isa(col)) return col;
            if (!iss(col)) return [];
            col = col.split(',').map(i=>i.trimAny(' '));
            return col;
        },
        //包含子元素的 长度
        colLength() {
            return this.colArr.length;
        },
        
        //将传入的 colExtraClass|Style 转为 colLength 长度的 [... class[]|style{} ...]
        colExtraClassArr() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ecls = this.colExtraClass || [],
                len = this.colLength,
                rtn = [];
            //未指定额外 class 则返回对应长度的 [... [], ...]
            if (!isa(ecls)) return this.$cgy.arr(len).map(i=>[]);
            //依次解析各 col
            for (let i=0;i<len;i++) {
                if (!is.defined(ecls[i]) || !(iss(ecls[i]) || isa(ecls[i]))) {
                    rtn.push([]);
                    continue;
                }
                if (iss(ecls[i])) {
                    rtn.push(this.$cgy.toClassArr(ecls[i]));
                } else {
                    rtn.push(ecls[i]);
                }
            }
            return rtn;
        },
        colExtraStyleObj() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                esty = this.colExtraStyle || [],
                len = this.colLength,
                rtn = [];
            //未指定额外 style 则返回对应长度的 [... {}, ...]
            if (!iso(esty)) return this.$cgy.arr(len).map(i=>{});
            //依次解析各 col
            for (let i=0;i<len;i++) {
                if (!is.defined(esty[i]) || !(iss(esty[i]) || iso(esty[i]))) {
                    rtn.push({});
                    continue;
                }
                if (iss(esty[i])) {
                    rtn.push(this.$cgy.toCssObj(esty[i]));
                } else {
                    rtn.push(esty[i]);
                }
            }
            return rtn;
        },
        //compact 参数处理   返回 colLength 长度的 []
        colCompactOpt() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isn = n => is.realNumber(n) && n*1>=0,
                isu = n => is.numeric(n),
                cmpt = this.compact,
                cmpto = this.compactTo,
                dslot = this.differentCompactSlot,
                cols = this.colArr,
                cpa = [];
            if (cols.length<=0 || !(isa(cmpt) || isn(cmpt))) return [];
            //将传入的 compact|compactTo|differentCompactSlot 参数转为 []
            if (isn(cmpt)) cmpt = [cmpt];
            if (isu(cmpto)) cmpto = [cmpto];
            if (is.boolean(dslot)) dslot = [dslot];
            if (!isa(cmpt) || !isa(cmpto) || cmpt.length !== cmpto.length) return [];
            if (!isa(cmpt) || !isa(dslot) || cmpt.length !== dslot.length) return [];
            //将 compact 参数格式化为 defaultCol 中的形式
            this.$each(cols, (coli, i) => {
                if (!cmpt.includes(i)) {
                    cpa.push({
                        compact: false,
                        compactTo: '',
                        differentSlot: false,
                    });
                    return true;
                }
                let idx = cmpt.indexOf(i);
                cpa.push({
                    compact: true,
                    compactTo: cmpto[idx],
                    differentSlot: dslot[idx],
                });
            });
            return cpa;
        },
        //resize 参数处理   返回 colLength 长度的 []
        colResizeOpt() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isn = n => is.realNumber(n) && n*1>=0,
                isu = n => is.numeric(n),
                rsz = this.resize,
                rszmin = this.resizeMin,
                rszmax = this.resizeMax,
                cols = this.colArr,
                rszo = [];
            if (cols.length<=0 || !(isa(rsz) || isn(rsz))) return [];
            //将传入的 resize|resizeMin|resizeMax 参数转为 []
            if (isn(rsz)) rsz = [rsz];
            if (isu(rszmin)) rszmin = [rszmin];
            if (isu(rszmax)) rszmax = [rszmax];
            if (!isa(rsz)) return [];
            //将 resize 参数格式化为 defaultCol 中的形式
            this.$each(cols, (coli, i)=>{
                if (!rsz.includes(i)) {
                    rszo.push({
                        resize: false,
                        resizeMin: '',
                        resizeMax: ''
                    });
                    return true;
                }
                let ridx = rsz.indexOf(i),
                    min = rszmin[ridx],
                    max = rszmax[ridx];
                rszo.push({
                    resize: true,
                    resizeMin: (isa(rszmin) && is.defined(min) && isu(min)) ? min : '',
                    resizeMax: (isa(rszmax) && is.defined(max) && isu(max)) ? max : '',
                });
            });
            return rszo;
        },

        /**
         * 生成标准化的 col 参数，与 defaultCol 数据结构一致  返回 colLength 长度的 []
         * 
         */
        colOpt() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isn = n => is.realNumber(n) && n*1>=0,
                iso = o => is.plainObject(o) && !is.empty(o),
                cols = this.colArr,
                ecls = this.colExtraClassArr || [],
                esty = this.colExtraStyleObj || [],
                cmpt = this.colCompactOpt,
                rsz = this.colResizeOpt,
                opts = [];
            if (cols.length<=0) return [];
            this.$each(cols, (coli, i) => {
                //基础 opt
                let opti = {
                        idx: i,
                        set: coli,
                        extra: {
                            class: is.defined(ecls[i]) ? ecls[i] : [],
                            style: is.defined(esty[i]) ? esty[i] : {}
                        }
                    },
                    //只有 100px|50% 形式的静态宽度值，才能支持 compact|resize
                    stcw = !(isn(coli) || coli==='*');
                    
                //compact opt
                if (!stcw || !isa(cmpt) || !is.defined(cmpt[i]) || !iso(cmpt[i])) {
                    //不支持 或 未指定 compact 参数
                    opti = Object.assign(opti, {
                        compact: false,
                        compactTo: ''
                    });
                } else {
                    opti = Object.assign(opti, cmpt[i]);
                }
                //resize opt
                if (!stcw || opti.compact===true || !isa(rsz) || !is.defined(rsz[i]) || !iso(rsz[i])) {
                    //不支持 或 未指定 resize 参数
                    opti = Object.assign(opti, {
                        resize: false,
                        resizeMin: '',
                        resizeMax: ''
                    });
                } else {
                    opti = Object.assign(opti, rsz[i]);
                }
                //合并到 opts
                opts.push(opti);
            });
            return opts;
        },

        /**
         * 根据 colOpt 参数，计算各子元素的 class[]|style{}
         */
        //计算各子元素的 class[]
        colClassArr() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                isn = is.realNumber,
                isu = is.numeric,
                //base-style 样式计算系统得到的 styComputedClass.col 各 col 元素通用的 class[]
                common = this.styComputedClass.col || [],
                //base-style 样式计算系统得到的样式参数
                styProps = this.styProps,
                clen = this.colLength,
                col = this.colOpt,
                cls = [];
            this.$each(col, (coli,i) => {
                let seti = coli.set,
                    extra = coli.extra.class || [],
                    //在 base-style 样式计算系统生成的 通用 col class[] 基础上，叠加处理
                    clsi = [...common];
                if (isn(seti)) {
                    //'1,2,1' 指定纯数字形式 宽度
                    clsi.push(`flex-${seti}`);
                } else if (seti === '*') {
                    //'256px,*,25%' 包含可变宽度 * 标记
                    clsi.push('flex-1');
                } else if (isu(seti)) {
                    //'256px,20%' 包含带单位数字形式的 宽度
                    //不适用 class[] 应在 style{} 中定义
                } else {
                    //其他 指定了不合法的宽度值，不操作
                }

                //根据 styProps 中可能存在的 边框参数，计算子元素的边框参数
                if ((styProps.hasBd === true || this.innerBorder) && i<clen-1) {
                    //仅对 非最后一列 计算边框
                    //边框位置默认在 右侧
                    clsi.push('bd-po-r');
                    //边框宽度
                    if (iss(styProps.bd)) {
                        clsi.push(`bd-${styProps.bd}`);
                    } else {
                        clsi.push('bd-m');
                    }
                    //边框颜色
                    if (iss(styProps.bdc)) {
                        clsi.push(`bdc-${styProps.bdc}`);
                    } else {
                        clsi.push('bdc-m');
                    }
                }

                //合并 extra class 额外定义的 各 col 的 class[]
                if (isa(extra)) clsi.push(...extra);

                //去重
                clsi = clsi.unique();
                //将 clsi 添加到 colClassArr
                cls.push(clsi);
            });
            return cls;
        },
        //计算各子元素的 style{}
        colStyleObj() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isn = is.realNumber,
                isu = is.numeric,
                //base-style 样式计算系统得到的 styComputedStyle.col 各 col 元素通用的 style{}
                common = this.styComputedStyle.col || {},
                col = this.colOpt,
                //当前 col 的 compact 状态
                ccols = this.compactedCols || [],
                //当前 col 的 resize 状态
                rcols = this.resizedCols || [],
                sty = [];
            this.$each(col, (coli,i) => {
                let seti = coli.set,
                    extra = coli.extra.style || {},
                    //在 base-style 样式计算系统生成的 通用 col style{} 基础上，叠加处理
                    styi = this.$cgy.mergeCss({}, common);
                /**
                 * 只有指定了 100px|50% 形式的确定的宽度值，才需要指定 style
                 */
                if (isu(seti)) {
                    if (coli.compact === true && is.defined(ccols[i]) && ccols[i]===true) {
                        //需要检查当前的 compact 状态
                        //如果 当前 col 为 compact 状态，则将 width 设为 compactTo
                        styi.width = coli.compactTo;
                    } else if (coli.resize === true && is.defined(rcols[i]) && rcols[i]!==false) {
                        //需要检查当前的 resize 状态
                        //如果 当前 col 已被 resize 且记录了 resize 后的 width，则作为 当前 width
                        styi.width = rcols[i];
                    } else {
                        //设为初始值
                        styi.width = seti;
                    }
                }

                //合并 extra style 额外的 各 col 的 style{}
                if (iso(extra)) styi = this.$cgy.mergeCss(styi, extra);
                
                //将 styi 添加到 colStyleObj
                sty.push(styi);
            });
            return sty;
        },
        //将计算得到的 各子元素的 class[] 转为 string
        colClassStr() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                cls = this.colClassArr,
                clss = [];
            this.$each(cls, (clsi,i) => {
                if (!isa(clsi)) {
                    clss.push('');
                    return true;
                }
                clss.push(clsi.join(' '));
            });
            return clss;
        },
        //将计算得到的 各子元素的 style{} 转为 string
        colStyleStr() {
            let is = this.$is,
                isc = c => this.$cgy.isCssObj(c),
                sty = this.colStyleObj,
                stys = [];
            this.$each(sty, (styi,i) => {
                if (!isc(styi)) {
                    stys.push('');
                    return true;
                }
                stys.push(this.$cgy.toCssString(styi));
            });
            return stys;
        },

        /**
         * 根据 colCompactOpt 计算各子元素的 compact-btn 元素的 class[]|style{}
         */
        //计算各子元素 compact-btn 的 class[]
        compactBtnClassArr() {
            let is = this.$is,
                clen = this.colLength,
                //base-style 系统自动生成的 compact btn 元素的 通用 class[]
                common = this.styComputedClass.compact || [],
                styProps = this.styProps,
                ccols = this.compactedCols,
                col = this.colOpt,
                cpbcls = [];
            this.$each(col, (coli,i)=>{
                //未开启 compact 的列直接设置按钮 class[] 为 [] 空数组
                if (!is.defined(coli.compact) || coli.compact!==true) {
                    cpbcls.push([]);
                    return true;
                }
                //在 base-style 生成的 通用 class[] 基础上，叠加处理
                let clsi = [...common];
                //compact-btn 位置
                if (i>=clen-1) {
                    //最后一列 compact-btn 位于 左侧
                    clsi.push('compact-btn-left');
                } else {
                    //其他列 compact-btn 位于 右侧
                    clsi.push('compact-btn-right');
                }
                //是否带边框
                if (styProps.hasBd===true || this.innerBorder) {
                    clsi.push('compact-btn-border');
                }
                //根据当前的 展开状态 决定 按钮图标
                if (is.defined(ccols[i]) && ccols[i]===true) {
                    clsi.push('compact-on');
                } else {
                    clsi.push('compact-off');
                }
                //记录 class[]
                cpbcls.push(clsi);
            });
            //返回
            return cpbcls;
        },
        //计算各子元素 compact-btn 的 style{}
        compactBtnStyleObj() {
            let is = this.$is,
                isu = n => is.numeric(n),
                clen = this.colLength,
                //base-style 系统自动生成的 compact btn 元素的 通用 style{}
                common = this.styComputedStyle.compact || {},
                //styProps = this.styProps,
                //ccols = this.compactedCols,
                //已计算好的 子元素 style{}
                colSty = this.colStyleObj,
                //ui
                ui = this.$ui,
                col = this.colOpt,
                cpbsty = [];
            this.$each(col, (coli,i)=>{
                //未开启 compact 的列直接设置按钮 style{} 为 空 {}
                if (!is.defined(coli.compact) || coli.compact!==true) {
                    cpbsty.push({});
                    return true;
                }
                //已计算好的子元素 style{}
                let cstyi = colSty[i] || {};
                //在 base-style 生成的 通用 style{} 基础上，叠加处理
                let styi = this.$cgy.mergeCss({}, common);
                //compact-btn 位置
                if (i>=clen-1) {
                    //最后一列 compact-btn 位于 左侧 left 值由 compact-btn-left 类定义
                    //styi.left = ui.sizeValMul(ui.sizeValSub(ui.cssvar.size.pd.m, 1), -1);
                } else {
                    //其他列 compact-btn 位于 右侧
                    if (isu(cstyi.width)) {
                        styi.left = ui.sizeValSub(cstyi.width, 1);
                    }
                }
                //记录 style{}
                cpbsty.push(styi);
            });
            //返回
            return cpbsty;
        },
        //class[] 转为 class-string
        compactBtnClassStr() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                cls = this.compactBtnClassArr,
                clss = [];
            this.$each(cls, (clsa,i)=>{
                if (!isa(clsa)) {
                    clss.push('');
                    return true;
                }
                //去重
                clsa = clsa.unique();
                //合并
                clss.push(clsa.join(' '));
            });
            return clss;
        },
        //style{} 转为 style-string
        compactBtnStyleStr() {
            let is = this.$is,
                isc = c => this.$cgy.isCssObj(c),
                sty = this.compactBtnStyleObj,
                stys = [];
            this.$each(sty, (styo,i)=>{
                if (!isc(styo)) {
                    stys.push('');
                    return true;
                }
                //style{} 转为 string
                stys.push(this.$cgy.toCssString(styo));
            });
            return stys;
        },
        //compact btn icon 图标
        compactBtnIcon() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                cbcls = this.compactBtnClassArr || [],
                ccols = this.compactedCols || [],
                len = this.colLength,
                rtn = [];
            this.$each(cbcls, (clsi,i) => {
                if (!isa(clsi)) {
                    //未开启 compact
                    rtn.push('');
                    return true;
                }
                let cpd = is.defined(ccols[i]) && ccols[i]===true,  //当前 col 是否已经折叠
                    //按钮在左侧
                    cpl = clsi.includes('compact-btn-left');
                if (!cpd) {
                    rtn.push(cpl ? 'keyboard-arrow-right' : 'keyboard-arrow-left');
                } else {
                    rtn.push(cpl ? 'keyboard-arrow-left' : 'keyboard-arrow-right');
                }
            });
            return rtn;
        },
    },
    methods: {
        //compact-btn 折叠|展开 某个列
        toggleCompact(idx) {
            let is = this.$is,
                clen = this.colLength,
                ccols = this.compactedCols;
            //初始化 compactedCols
            if (is.array(ccols) && ccols.length<=0) {
                for (let i=0;i<clen;i++) {
                    this.compactedCols.push(false);
                }
                ccols = this.compactedCols;
            }
            //读取当前 idx 的子元素列的 compact 状态
            let ccoli = is.defined(ccols[idx]) && ccols[idx]===true;
            //设置 compacted 状态
            this.$set(this.compactedCols, idx, !ccoli);
            //触发事件
            this.$emit(`col-compact`, idx, !ccoli);
        },

        //定义某个 col 被 drag-resize 后执行
        afterDragResize(el, size) {
            let is = this.$is,
                //额外传入 v-drag-resize 指令的 col idx
                idx = el.$drag.extra.idx,
                //resize 后的 col width 尺寸值 带单位
                w = size.x,
                clen = this.colLength,
                rcols = this.resizedCols;
            //console.log(idx, w);
            //初始化 resizedCols
            if (is.array(rcols) && rcols.length<=0) {
                for (let i=0;i<clen;i++) {
                    this.resizedCols.push(false);
                }
                rcols = this.resizedCols;
            }
            //设置 resized 状态
            this.$set(this.resizedCols, idx, w);
            //触发事件
            this.$emit(`col-resize`, idx, w);
        },
    }
}
</script>

<style>

</style>