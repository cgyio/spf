/**
 * Vcom 组件库插件 服务组件
 * Vue.service.base
 * 所有 服务组件的通用 参数|方法
 */

export default {
    props: {},
    data() {return {
        //初始化完成标记
        inited: false,
    }},
    computed: {

    },
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
         * 将外部传入的 options 合并到此服务的 data 数据中
         * 通常在 service.init 方法中调用，将经过自定义处理后剩余的 外部 options 合并到服务的 data 中
         * @param {Object} options
         * @return {Boolean} 
         */
        combineOptions(options) {
            let is = this.$is;
            if (is.plainObject(options) && !is.empty(options)) {
                this.$cgy.each(options, (v,k) => {
                    //必须是预定义的 data 项目
                    if (is.undefined(this[k])) return true;
                    if (is.plainObject(v)) {
                        this[k] = Object.assign(this[k], v);
                    } else if (is.array(v)) {
                        //数组使用 整体替换的方式
                        this[k].splice(0);
                        this[k].push(...v);
                    } else if (is(v, 'string,number,boolean')) {
                        //其他标量形式，直接替换
                        this[k] = v;
                    }
                });
            }
            return true;
        },
    }
}