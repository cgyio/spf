/**
 * Vue2.* 插件 
 * CGY-VUE 基础插件
 * 
 * 组件通用 mixin
 */

export default {

    props: {

        /**
         * 自定义的组件事件处理
         * 当动态生成一个组件实例时，通过 props 传入此参数
         * 当组件使用事件总线 $ev 一个事件时，会先检查此参数是否已经定义了事件处理方法
         * 如果定义了事件处理方法，则会拦截 $ev 方法，转而使用此处定义的事件处理方法
         * * 需要事件总线 $bus 支持 *
         */
        customEventHandler: {
            type: Object,
            default: ()=>{
                return {};
            }
        },

        /**
         * 这两个 props 在 mixinBase 里提供
         * 在这里提供会影响 element-ui 这类 ui 组件库，会报错
         * * 应在所有需要这两个 props 的组件中引入 mixinBase *
         */
        //组件根元素上附加 custom class 样式
        /*customClass: {
            type: String,
            default: ''
        },
        //组件根元素上附件的 cuatom style 
        customStyle: {
            type: String,
            default: ''
        },*/
    },

    data: function() {
        
        return {
            /**
             * api requesting flag
             */
            cssvar: Vue.ui.cssvar,
        }
    },

    computed: {
        //Vue
        //Vue() {return Vue;},
        //在组件模板中引用自身
        $this() {return this;},
        //访问 动态组件
        $invokes() {return Vue.dynamicComponentsInstance;},

        /**
         * 定义这些计算变量为了可以在 vue 开发工具中看到这些功能组件的单例实例
         */
        //访问 ui 实例
        $UI() {return Vue.ui;},
        //访问 usr 实例
        $USR() {return Vue.usr;},
        //访问 nav 实例
        $NAV() {return Vue.nav;},
        //访问 db 实例
        $DB() {return Vue.db;},

    },

    methods: {

        /**
         * dom 相关
         */
/*        //获取
        async $all(selector) {
            let el = null,
                els = [],
                rst = [];
            try {
                await this.$until(()=>{
                    el = this.$el;
                    els = document.querySelectorAll(selector);
                    return !this.$is.null(el) && els.length>0;
                });
                for (let i=0;i<els.length;i++) {
                    if (this.$hasSubNode(els[i])) {
                        rst.push(els[i]);
                    }
                }
                return rst;
            } catch (err) {
                throw err;
                return [];
            }
        },
        async $(selector) {
            let els = await this.$all(selector);
            if (els.length<=0) return null;
            return els[0];
        },
        //判断 node 在当前元素内
        $hasSubNode(node) {
            let is = this.$is,
                el = this.$el,
                has = false,
                ps = node.parentNode;
            while(is.elm(ps) && is.defined(ps.nodeName) && ps.nodeName.toLowerCase()!='body') {
                if (ps==el) {
                    has = true;
                    break;
                }
                ps = ps.parentNode;
                if (!is.elm(ps)) break;
            }
            return has;
        },
        //求出某个元素相对于 body 的 left 与 top
        $offset(node) {
            let is = this.$is,
                offset = {left: node.offsetLeft, top: node.offsetTop},
                ps = node.parentNode;
            while(is.elm(ps) && is.defined(ps.nodeName) && ps.nodeName.toLowerCase()!='body') {
                offset.left += ps.offsetLeft;
                offset.top += ps.offsetTop;
                ps = ps.parentNode;
                if (!is.elm(ps)) break;
            }
            return offset;
        },
        //确认 $el 已加载
        async $elReady(elm=null) {
            let is = this.$is;
            await this.$until(() => {
                let el = elm==null ? this.$el : elm;
                return is.elm(el) && is.defined(el.nodeType) && el.nodeType==1;
            });
            return elm==null ? this.$el : elm;
        },
*/
    }
}