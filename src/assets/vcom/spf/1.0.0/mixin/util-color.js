/**
 * Util Mixins 工具类 mixin
 * util-color 色彩工具
 */

export default {
    props: {
        //样式类别
        type: {
            type: String,
            default: 'default'
        },

    },
    data() {return {
        //可选的样式类别
        types: [
            "default",
            "primary",
            "success",
            "warn",
            "danger",
            "info"
        ],
    }},
    computed: {
        //根据 type 返回 typeColor
        typeColor() {
            let cvc = this.cssvar.color,
                tp = this.type,
                back = '',
                front = '';
            if (tp=='default') {
                back = 'transparent';
                front = cvc.f.$;
            } else {
                back = cvc[tp].$;
                front = cvc[tp].$;
            }
            return {}
        },
    },
    methods: {

    }
}