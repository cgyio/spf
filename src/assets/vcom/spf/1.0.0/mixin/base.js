/**
 * 组件通用 mixin
 */

import mixinBaseStyle from 'base-style.min.js';

export default {
    mixins: [mixinBaseStyle],
    props: {
        
    },
    data() {return {
        
    }},

    computed: {

        //$UI 是否加载完成
        $uiReady() {
            let ui = this.$UI;
            return this.$is.vue(ui);
        },
    },

    methods: {
        //el 元素加载完成
        async elReady() {
            await this.$until(()=>{
                let el = this.$el,
                    is = this.$is;
                return is.elm(el);
            },5000);
            return true;
        },
    }
}