/**
 * SPF-Vcom 组件库 系统级 zIndex 参数处理
 * 所有需要使用到 系统级 zIndex 参数的 组件必须引用此 mixin
 */

export default {
    props: {
        
    },
    data() {return {
        //zIndex 关联到系统级 zIndex
        zIndex: 0,
    }},
    computed: {
        //获取 此组件当前的 zIndex
        compZindex() {
            let is = this.$is,
                el = this.$el;
            if (is.defined(this.zIndex) && this.zIndex>0) return this.zIndex;
            if (is.elm(el)) {
                let csty = getComputedStyle(el);
                if (is.object(csty) && is.defined(csty.zIndex)) {
                    let zidx = csty.zIndex;
                    if (!is.realNumber(zidx)) return 0;
                    return zidx * 1;
                }
            }
            return 0;
        },
    },
    mounted() {
        this.$nextTick(()=>{
            this.$elReady().then(()=>{
                //在 el 元素上增加 mouse-down 事件监听，自动增加 zIndex
                this.$el.addEventListener('mousedown', ()=>{
                    this.whenElMouseDown();
                });
            });
        });
    },
    methods: {
        /**
         * 启用 全局 zIndex 的组件当 $el 元素被点击时
         * 默认方法是自动提升 zIndex 到顶端
         * !! 组件内部可覆盖这个方法，以执行其他自定义操作
         */
        whenElMouseDown() {
            let ozidx = this.$ui.zIndex,
                czidx = this.compZindex;
            if (czidx < ozidx) {
                //将当前 zIndex 提升到顶部
                this.setZindex();
            }
        },

        //设置此组件的 zIndex
        async setZindex(zIndex=null) {
            let is = this.$is,
                zidx = is.realNumber(zIndex) ? zIndex * 1 : this.$ui.getZindex();
            //设置
            this.zIndex = zidx;
            await this.$elStyle({
                zIndex: zidx
            });
            return zidx;
        },
    }
}