/**
 * 需要使用 evt-bus 的组件应该使用的 通用 evt mixin
 */

export default {
    props: {
        
    },
    data() {return {

    }},
    computed: {},

    /**
     * 所有使用 this.$bus.$when() 订阅事件的组件
     * 应在组件销毁时，调用 this.$bus.$whenOffAll() 取消订阅
     */
    beforeDestroy() {
        this.$bus.$whenOffAll(this);
    },

    methods: {

    }
}