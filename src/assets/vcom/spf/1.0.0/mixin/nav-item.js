/**
 * 作为 cVue 框架 nav 页面的组件，必须引用此 mixin
 */

export default {
    props: {
        //关联到 父组件 layout
        layout: {
            type: Object,
            default: ()=>{
                return {}
            }
        }
    },
    data() {return {}},
    computed: {

    },
    mounted() {
        //组件 $el 生成后执行
        this.$elReady().then(()=>{
            //触发 nav-item-ready 事件
            this.$ev('nav-item-ready', this);
        });
    },
    methods: {

    }
}