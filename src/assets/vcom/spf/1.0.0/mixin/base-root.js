/**
 * SPF-Vcom 组件库 默认 根组件 mixin
 * 在使用 SPF-Vcom 组件可的 spa 页面中，必须显式 import 此 mixin
 */

export default {
    props: {

    },
    data() {return {
        
    }},
    computed: {},
    //根组件创建后
    created() {
        //循环执行 Vue.service.* 服务组件的 afterRootCreated 方法
        Vue.initServiceAfterRootCreated(this);
    },
    methods: {

    }
}