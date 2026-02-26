/**
 * SPF-Vcom 组件库 可作为动态组件的 通用 mixin
 * 所有可作为动态组件，被 Vue.service.dc.invoke() 动态创建的组件，必须显式 import 此 mixin
 */

//可被动态加载的组件，通常需要处理 zIndex
import mixinZindex from 'zindex.js';

export default {
    mixins: [mixinZindex],
    props: {
        //动态组件的显示和隐藏
        show: {
            type: Boolean,
            default: false
        },
    },
    data() {return {

        /**
         * 动态组件通用参数
         */
        dc: {
            //!! 不要覆盖 !! 可作为动态组件的 标记
            dynamic: true,
            //是否可以同时创建多个此动态组件的实例，默认 true
            multiple: true,

            //此动态组件当前实例的 dynamic-componet-instance-key 由 service.dc 服务组件动态创建，全局唯一
            key: null,
            //此动态组件实例化时增加的监听事件，用于在 $destroy 方法中解除监听
            events: [],

            //显示状态
            display: {
                //toggle show dura 基于 animate.css 其中 css 变量 --animate-duration == $ui.cssvar.vars.ani.dura == 0.3s
                dura: 300,  //应与 $ui.cssvar.vars.ani.dura 保持一致
                //指定动态组件 的 animate 动画类型，基于 animate.css  引用组件内部可自行覆盖
                ani: {
                    //显示|隐藏 动画 animateType
                    show: {
                        //false->true 的动画类型
                        on: 'fadeIn',
                        //true->false 的动画类型
                        off: 'fadeOut'
                    },
                    //可定义其他动画，例如 win 组件的 minimize 动画
                    //!! 定义其他动画时 必须同时定义 计算属性来判断此动画的完成状态，例如：isDcMinimize
                    //!! 还必须定义一个 set 方法，用来改变状态，例如：dcSetMinimize(true|false)
                },
            },
        },

        /**
         * !! 需要启用 auto-props 并定义 switch
         */
        auto: {
            sub: {
                //启用 animate 子系统
                animate: true,  //'disabled:false'
            },
            //增加开关
            switch: {
                //显示隐藏标记
                'isDcShow @root #class': '.{{auto.prefix}}-show',
            },
        },

    }},
    computed: {
        //此动态组件是否已显示
        isDcShow() {
            let is = this.$is;
            if (is.defined(this.show)) return this.show;
            if (is.defined(this.dc.display.show)) return this.dc.display.show;
            return false;
        },
    },
    mounted() {

    },
    methods: {
        /**
         * 在此组件通过 Vue.service.dc 动态实例化后，修改 propsData 以重新渲染
         * @param {Object} newProps 要修改的 propsData
         * @param {Boolean} force 是否强制将事件注册为 once 默认 false
         * @return {Boolean}
         */
        async dcSet(newProps={}, force=false) {
            let is = this.$is,
                ops = this.$props;
            if (!is.plainObject(newProps) || is.empty(newProps)) return false;

            //拆分 newProps 中可能存在的 on 时间处理参数
            let props = this.$dc.getEventProps(newProps);

            //修改组件实例的 props
            if (!is.empty(props.props)) {
                this.$each(props.props, (v,k) => {
                    if (is.undefined(ops[k])) return true;
                    let ov = ops[k];
                    if (ov !== v) {
                        Vue.set(this._props, k, v);
                    }
                });
            }
            await this.$wait(10);

            //注册组件实例的事件处理
            if (!is.empty(props.on)) {
                await this.$dc.on(this, props.on, force);
            }
            
            return true;
        },

        /**
         * 在动态创建的组件实例内部销毁自身
         * @return {Boolean}
         */
        async dcDestroy() {
            return await this.$dc.destroy(this.dcKey)
        },



        /**
         * 动态组件 动画相关
         */

        /**
         * 动态组件实例创建后 执行 dc.display.ani 中定义的动画
         * !! 需要组件启用 base-style 样式系统的 animate 动画子系统
         * !! 引用组件内部不要覆盖  可在 dc.display.ani 中创建其他类型的动画 如：dcToggleMinimize
         * @param {String} type 要执行的动画名称
         * @param {Boolean} to 此动画的目标状态  true|false 表示 切换后的状态
         * @param {Object} style 可额外指定动画前后的样式 将在动画执行之前和完成后 添加到组件的根元素
         *                 { before: style{}|style-string, after: ... }
         * @return {Boolean} 动画完成标记
         */
        async dcToggle(type='show', to=true, style={}) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                iscss = o => iso(o) && this.$cgy.isCssObj(o);
            //要执行的动画不存在
            if (!is.defined(this.dc.display.ani[type])) return false;
            //读取此动画对应的计算属性值，获取组件当前的状态
            let sk = `isDc${type.ucfirst()}`,
                fk = `dcSet${type.ucfirst()}`;
            //未定义对应的计算属性
            if (!is.defined(this[sk]) || !is.boolean(this[sk])) return false;
            //未定义对应的 set 方法
            if (!is.defined(this[fk]) || !is(this[fk], 'function,asyncfunction')) return false;
            let st = this[sk],
                fn = this[fk],
                asyncfn = is.asyncfunction(fn);
            //当前状态已经是目标状态，不执行动画
            if (st === to) return false;

            //处理额外 style
            if (!is.plainObject(style) || is.empty(style)) style = {};
            style = this.$extend({
                before: null,
                after: null
            }, style);
            style = this.$each(style, (v,k)=>{
                let nv = null;
                if (iss(v)) {
                    nv = this.$cgy.toCssObj(v);
                } else if (iscss(v)) {
                    nv = v;
                } else {
                    return null;
                }
                if (!iscss(nv)) return null;
                return nv;
            });

            // 0    设置动画执行前的 style
            if (iscss(style.before)) {
                await this.$elStyle(style.before);
            }

            //当前动画的 animateType 定义在 dc.display.ani.***.on|off
            let ani = this.dc.display.ani[type][to?'on':'off'],
                dura = this.dc.display.dura;

            // 1    设置动画效果，如果 to == false 则动画将开始执行
            await this.dcSet({
                animateType: ani
            });
            //隐藏动画先运行 为动画留出 dura
            if (!to) await this.$wait(dura);
            
            // 2    设置 to 状态
            if (asyncfn) {
                await fn.call(this, to);
            } else {
                fn.call(this, to);
            }
            //显示动画后运行 为动画留出 dura
            if (show) await this.$wait(300);
            
            // 3    设置动画完成后的 style
            if (iscss(style.after)) {
                await this.$elStyle(style.after);
            }

            //完成
            return true;
        },

        /**
         * 快捷调用 dcToggle
         * !! 可在引用组件内部定义快捷调用 dcToggle 的方法
         */
        //dcShow() {...},

        //动画状态 set 方法
        //dcSetShow
        async dcSetShow(show=true) {
            return await this.dcSet({show});
        },

    }
}