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
                //toggle show dura
                dura: 300,  // == $ui.cssvar.vars.ani.dura
            },

        },
        //!! 不要覆盖 !! 可作为动态组件的 标记
        dynamic: true,
        //是否可以同时创建多个此动态组件的实例，默认 true
        multiple: true,
        //此动态组件当前实例的 dynamic-componet-instance-key 由 service.dc 服务组件动态创建，全局唯一
        dcKey: null,
        //此动态组件实例化时增加的监听事件，用于在 $destroy 方法中解除监听
        dcEvents: [],

        //显示状态
        dcDisplay: {
            show: false,
            //显隐动画类型
            ani: {
                show: 'fadeIn',
                hide: 'fadeOut'
            }

        },
        //show: false,

    }},
    computed: {
        //此动态组件是否已显示
        isDcShow() {
            return this.dcDisplay.show;
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
         * 动态组件实例创建后的 显隐动画
         */
        //toggle
        async dcToggleShow(style={}, show=true) {
            if (this.dcDisplay.show===show) return false;
            let is = this.$is,
                ani = this.dcDisplay.ani[show ? 'show' : 'hide'];
            //设置 el 额外的样式
            await this.$elStyle(style);

            //设置动画效果
            await this.dcSet({
                animateType: ani
            });
            //隐藏动画先运行
            if (!show) {
                //为动画留出 dura
                await this.$wait(300);
            }
            //设置显示|隐藏
            this.$set(this.dcDisplay, 'show', show);
            //显示动画后运行
            if (show) {
                //为动画留出 dura
                await this.$wait(300);
            }
            return true;
        },
        //显示
        async dcShow(style={}) {
            return await this.dcToggleShow(style, true);
        },
        //隐藏
        async dcHide(style={}) {
            return await this.dcToggleShow(style, false);
        },

    }
}