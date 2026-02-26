/**
 * SPF-Vcom 组件库  当某个组件被当作另一个组件的根元素时 的额外功能
 * 任意 可作为其他组件的根元素的组件 需引用此 mixin
 * 例如：PRE@-bar, PRE@-block, PRE@-layout, ...
 */

export default {
    props: {
        //可手动指定此组件当前的父组件名称
        insideComponent: {
            type: String,
            default: ''
        },
    },
    data() {return {}},
    computed: {},
    created() {
        this.whenSelfCreated();
    },
    mounted() {
        this.whenSelfMounted();
    },
    updated() {
        this.whenSelfUpdated();
    },
    methods: {
        /**
         * 当此组件作为其他组件根元素时，在此组件的各生命周期方法内执行的系列方法
         */
        //created
        whenSelfCreated() {

        },
        //mounted
        whenSelfMounted() {},
        //updated
        whenSelfUpdated() {},
    }
}