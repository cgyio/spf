/**
 * cv-**** 组件 form 表单项组件通用 mixin
 */

import mixinBase from 'mixins/base';

export default {
    mixins: [mixinBase],
    
    //v-model 参数
    model: {
        prop: 'value',
        event: 'input'
    },

    props: {
        //可由各表单项 inputer 组件覆盖，指定各自的默认 value 属性
        value: {
            //type: any,
            default: ''
        },
        
        //size
        size: {
            type: String,
            default: 'default'
        },

        //是否在相邻 inp 组件之间显示 gap
        gapInline: {
            type: Boolean,
            default: true
        },
    },
    data() {return {
        //size keys
        szs: {
            mini: 'xs',
            small: 's',
            default: '$',
            medium: 'l',
            large: 'xl',
        },
        
        //mouse
        mouseDown: false,
    }},
    computed: {
        
    },
    methods: {
        //获取 cssvar 中 size 的值 数字
        cssvarSize(key='') {
            let sz = this.szs[this.size],
                csz = this.cssvar.size.inp,
                szc = key=='' ? csz[sz] : csz[key][sz];
            return szc.replace('px','')*1;
        },
    }
}