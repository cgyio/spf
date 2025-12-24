/**
 * Vcom 组件库插件 服务组件
 * Vue.service.dc
 * 动态组件 创建|管理|销毁 服务组件
 */

import baseService from 'base.js';

export default {
    props: {},
    data() {return {
        //动态组件实例数组 []
        list: [
            //按顺序储存 自动生成的动态组件全局唯一 dynamic-component-instance-key（dcKey）
            /*
            'dc-key-foo', 'dc-key-bar', ...
            */
        ],

        //缓存动态组件实例
        ins: {
            /*
            'dc-key-foo': vue-instance,
            'dc-key-bar': vue-instance,
            ...
            */
        },

    }},
    computed: {},
    methods: {
        
        /**
         * !! Vue.service.* 必须实现的 初始化方法，必须 async
         * !! 各 service 服务组件必须覆盖此方法
         * @param {Object} options 外部传入的插件预定义的 Vue.service.options.[service-name] 中的参数
         * @return {Boolean} 必须返回 true|false 表示初始化 成功|失败
         */
        async init(options) {

            return true;
        },

        /**
         * !! Vue.service.* 可实现的 afterRootCreated 方法，必须 async
         * !! 将在 Vue.$root 根组件实例创建后自动执行
         * @param {Vue} root 当前应用的 根组件实例，== Vue.$root
         * @return {Boolean} 必须返回 true|false 表示初始化 成功|失败
         */
        async afterRootCreated(root) {
            return true;
        },



        /**
         * 动态组件的 创建|管理|销毁
         */
        /**
         * 动态组件创建实例  异步方法
         * 全局方法：
         *      Vue.service.dc.invoker('comp-name', { compProps ... }).then(...)
         *      父组件为 Vue.$root 根组件
         * 在任意组件内：
         *      this.$invoke('comp-name', { compProps ... }).then(...)
         *      父组件为 当前组件
         * @param {String} compName 组件的注册名称，必须是全局注册的组件
         * @param {Object} compProps 组件实例化参数
         * @param {Vue|null} parentComp 父组件，默认不指定时，父组件为 Vue.$root 当前环境下的根组件
         * @return {Vue|null} 组件实例
         */
        async invoke(compName, compProps = {}, parentComp = null) {
            let is = this.$is,
                //动态加载的组件实例的父组件为指定的 parentComp 或 Vue.$root
                pcomp = is.vue(parentComp) ? parentComp : Vue.$root;   

            /**
             * 处理 compName 自动补齐组件名称前缀
             * !! 基础组件库组件必须以 base- 开头，业务组件库必须以 [组件库名]- 开头
             * !! 例如：base-button  pms-table
             */
            let vcn = Vue.vcn(compName);
            if (!is.string(vcn) || vcn==='') return null;
            //查找可能存在的 已经实例化的动态组件实例
            let ocomp = this.get(vcn);
            if (is.vue(ocomp)) {
                //存在已创建的动态组件实例，检查其是否允许同时实例化多次
                if (is.defined(ocomp.dc.multiple) && ocomp.dc.multiple!==true) {
                    //不允许同时实例化多次，直接返回这个已经存在的组件实例
                    return ocomp;
                }
            }
            //不存在已经创建的动态组件实例，或者允许同时实例化多个组件实例，则创建全局唯一 key
            let dcKey = this.getKey(compName);

            //获取 组件定义，异步组件将自动加载
            let compDef = await this.getDefine(vcn),
                comp = compDef.constructor;
            //console.log(compDef);
            //检查组件定义的 data 确认此组件可以被动态加载 comp.data.dynamic === true
            if (compDef.data.dc.dynamic !== true) {
                return null;
            }

            //处理 compProps 提取其中的 on 部分 用于绑定事件处理
            let props = this.getEventProps(compProps);
            
            //实例化组件 comp
            let ins = new comp({propsData: props.props}).$mount();
            //全局唯一 key 写入组件实例
            ins.dcKey = dcKey;

            //事件处理
            if (!is.empty(props.on)) {
                ins = await this.on(ins, props.on);
            }

            //挂载到父组件（parentComp | Vue.$root）的 dom 上
            let pel = null;
            if (!is.vue(pcomp) || is.empty(pcomp.$el)) {
                pel = document.querySelector('body');
            } else {
                pel = pcomp.$el;
            }
            pel.appendChild(ins.$el);

            //组件实例缓存到 service.invoker.list|ins
            await this.cache(dcKey, ins);

            //返回创建好的组件实例
            return ins;
        },
        //覆盖 组件库 instance.js 中定义的实例方法 $invoke
        //async $invoke(compName, compProps = {}) {
            // Vue.service.dc.$invoke(...) === Vue.service.dc.invoke(..., Vue.$root)
        //    return await this.invoke(compName, compProps, Vue.$root);
        //},

        /**
         * 处理传入的 事件监听参数
         * @param {Vue} comp 组件实例
         * @param {Object} on 事件监听参数 结构为：
         *  {
         *      'evt-name': function() { ... },
         *      'evt-name': {
         *          handler() { ... },
         *          once: true,
         *          ... 其他额外参数
         *      },
         *      ...
         *  }
         * @param {Boolean} force 强制注册为 once 事件，默认 false 不强制
         * @return {Vue} 附加了事件监听后的 组件实例
         */
        async on(comp, on={}, force=false) {
            let is = this.$is;
            if (!is.vue(comp) || !is.plainObject(on) || is.empty(on)) return comp;
            this.$each(on, (eh, ehn) => {
                let ehp = null;
                if (!is(eh, 'function,asyncfunction')) {
                    if (is.plainObject(eh) && is.defined(eh.handler) && is(eh.handler, 'function,asyncfunction')) {
                        //传入了 on: { 'evt-name': { handler() {...}, once: true, ... }, ... } 形式
                        ehp = Object.assign({}, eh);
                        eh = ehp.handler;
                        Reflect.deleteProperty(ehp, 'handler');
                    }
                    //其他形式直接跳过
                    return true;
                }

                //额外参数
                let once = false;
                if (is.plainObject(ehp) && !is.empty(ehp)) {
                    //处理额外事件监听参数
                    //once
                    if (ehp.once && ehp.once===true) once = true;
                }
                
                //如果 force == true 则强制注册为 once 事件
                if (once || force) {
                    //once
                    comp.$once(ehn, eh);
                } else {
                    //监听事件
                    comp.$on(ehn, eh);
                }
                //添加到 监听事件数组，用于后期解除监听
                if (!is.array(comp.dcEvents)) comp.$set(comp._data, 'dcEvents', []);
                if (!comp.dcEvents.includes(ehn)) comp.dcEvents.push(ehn);
            });
            await this.$wait(10);
            return comp;
        },

        /**
         * 根据传入的组件实例的 dcEvents 中保存的监听事件名，批量移除事件监听
         * @param {Vue} comp
         * @return {Vue} 移除了所有监听事件的组件实例
         */
        off(comp) {
            let is = this.$is,
                ehs = comp.dcEvents || [];
            if (is.array(ehs) && ehs.length>0) {
                this.$each(ehs, (ehn, i) => {
                    //解除事件监听
                    comp.$off(ehn);
                });
            }
            return comp;
        },

        /**
         * 将新创建是组件实例，缓存到 Vue.service.invoker.list|ins
         * @param {String} dcKey 组件实例全局唯一 key
         * @param {Vue} comp 组件实例
         * @return {Boolean}
         */
        async cache(dcKey, comp) {
            let is = this.$is;
            if (!is.vue(comp) || !is.string(dcKey) || dcKey==='') return false;
            //缓存到 list
            this.list.push(dcKey);
            //缓存到 ins
            this.$set(this.ins, dcKey, comp);
            await this.$wait(10);
            return true;
        },

        /**
         * 判断传入的 dcKey 是否存在
         * @param {String} dcKey
         * @return {Boolean}
         */
        hasKey(dcKey) {
            let is = this.$is,
                cl = this.list,
                ins = this.ins;
            return cl.includes(dcKey) && is.defined(ins[dcKey]) && is.vue(ins[dcKey]);
        },

        /**
         * 销毁动态创建的组件实例
         * @param {String} dcKey 组件实例全局唯一 key
         * @return {Boolean}
         */
        async destroy(dcKey) {
            let is = this.$is,
                cl = this.list,
                idx = cl.indexOf(dcKey);
            if (idx<0) return false;
            //获取组件实例
            let comp = this.ins[dcKey];
            if (is.vue(comp)) {
                //执行可能存在的 $destroy 方法
                if (is.function(comp.$destroy)) {
                    comp.$destroy();
                } else if (is.asyncfunction(comp.$destroy)) {
                    await comp.$destroy();
                }
                //解除事件监听
                this.off(comp);
                //销毁 dom
                if (is.elm(comp.$el)) comp.$el.remove();
                await this.$wait(100);
            }
            
            //从 list 中删除
            this.list.splice(idx, 1);
            //从 ins 中删除
            this.$set(this.ins, dcKey, undefined);
            await this.$wait(10);
            return true;
        },

        /**
         * 根据组件名称，获取可能存在的动态组件实例 未找到 返回 null，否则返回第一个组件实例
         * @param {String} compName 组件名 base-comp 或 [业务组件库名]-comp  也可以直接传入 dcKey
         * @return {Vue|null} 组件实例 或 null
         */
        get(compName) {
            if (this.hasKey(compName)) return this.ins[compName];
            let is = this.$is,
                cl = this.list,
                cd = this.ins,
                vcn = Vue.vcn(compName),
                comp = null;
            //确保输入有效的 组件名
            if (!is.string(vcn) || vcn==='') return null;
            this.$each(cl, (dk, i) => {
                if (!is.vue(cd[dk])) return true;
                if (cd[dk].$options.name === vcn) {
                    comp = cd[dk];
                    return false;
                }
            });
            return comp;
        },

        /**
         * 创建全局唯一的 动态组件实例 key 结构为：comp-name-[8位随机字符串]
         * @param {String} compName 组件名 base-comp 或 [业务组件库名]-comp
         * @return {String} 在 this.list 和 this.ins 中不存在的 key
         */
        getKey(compName) {
            let is = this.$is,
                cl = this.list,
                cd = Object.keys(this.ins),
                //生成 key
                ck = () => `${compName}-${this.$cgy.nonce(8, false)}`,
                dk = ck();
            while (cl.includes(dk) || cd.includes(dk)) {
                dk = ck();
            }
            return dk;
        },

        /**
         * 从传入的 props 参数中拆分出 on 事件处理参数
         * @param {Object} props 
         * @return {Object} {props: { ... }, on: { ... }}
         */
        getEventProps(props={}) {
            let is = this.$is;
            if (!is.plainObject(props) || is.empty(props) || !is.defined(props.on)) {
                return {props, on: {}};
            }

            let on = {};
            if (is.plainObject(props.on) && !is.empty(props.on)) {
                on = Object.assign({}, props.on);
            }
            Reflect.deleteProperty(props, 'on');
            return {props, on};
        },

        /**
         * 根据传入的 compName 组件名 获取组件的定义参数
         * @param {String} compName 组件名 短名|完整名
         * @return {Object} {constructor, options, ...}
         */
        async getDefine(compName) {
            let is = this.$is,
                vcn = this.$vcn(compName);
            if (!is.string(vcn) || vcn==='') return null;
            let comp = Vue.component(vcn);
            if (is.undefined(comp)) return null;
            //异步组件形式
            if (comp.toString().includes('import')) {
                //异步组件是懒加载的，此时 组件 compName 还未加载
                comp = await comp();
                if (is.undefined(comp.default)) return null;
                comp = comp.default;
            }
            //comp 是 function，所有组件定义都挂在 comp.options 上
            if (!is.function(comp) || !is.defined(comp.options)) return null;
            //准备一个临时 Vue 实例
            let vm = new Vue({}),
                //返回数据
                rtn = {
                    //Vue 构造函数本身
                    constructor: comp,
                    //所有定义项
                    options: comp.options,
                    //组件 props 定义
                    props: comp.options.props,
                    //组件 data 定义，在 options 中保存的是 function 需要执行后得到结果
                    data: comp.options.data.call(vm),
                };
            //销毁临时实例
            vm.$destroy();
            return rtn;
        },

    }
}