/**
 * Vue2.* 插件 
 * CGY-VUE 基础插件
 * 
 * 组件实例方法
 * 以 $cv 开头
 * 
 */

export default {

    /**
     * dom 相关方法
     */
    //获取
    async $all(selector) {
        let el = null,
            els = [],
            rst = [];
        try {
            await cgy.until(()=>{
                el = this.$el;
                els = document.querySelectorAll(selector);
                return !cgy.is.null(el) && els.length>0;
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
        let is = cgy.is,
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
        let is = cgy.is,
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
        let is = cgy.is;
        await cgy.until(() => {
            let el = elm==null ? this.$el : elm;
            return is.elm(el) && is.defined(el.nodeType) && el.nodeType==1;
        });
        return elm==null ? this.$el : elm;
    },
    //为某个元素添加 ResizeObserver 触发 resize 事件
    $onResize(elm, handler=null) {
        if (!this.$is.elm(elm) || !this.$is(handler, 'function,asyncfunction')) return false;
        //不能重复添加 resize 事件处理函数
        if (!this.$is.empty(elm.resizer)) return false;
        elm.resizer = new ResizeObserver(entries => {
            //console.log(entries);
            handler(entries);
        });
        elm.resizer.observe(elm);
        return elm;
    },
    $offResize(elm) {
        if (!this.$is.elm(elm) || this.$is.empty(elm.resizer)) return false;
        elm.resizer.unobserve(elm);
        elm.resizer = undefined;
        return elm;
    },
    //为 $el 元素添加 style
    async $elStyle(style={}) {
        let is = this.$is,
            el = this.$el;
        if (!is.elm(el)) return false;
        //设置 el 样式
        if (is.plainObject(style) && !is.empty(style)) {
            this.$each(style, (v,k) => {
                el.style[k] = v;
            });
            await this.$wait(10);
        }
        return true;
    },
    



    /**
     * dynamic component 动态加载组件
     * 异步加载
     * 在任意组件内：
     *      this.$invokeComp('comp-name', { propsData ... }).then(...)
     * @param String compName 组件的注册名称，必须是全局注册的组件
     * @param Object propsData 组件实例化参数
     * @return Vue Component instance 组件实例
     */
    async $invoke(compName, propsData = {}) {
        //return await Vue.$invokeComp.call(this, compName, propsData);
        return await this.$dc.invoke(compName, propsData, this);
    },
    /**
     * 销毁 dynamic component 实例
     */
    //$destroyInvoke(compIns) {
    //    return Vue.$destroyInvoke(compIns);
    //},

    

}