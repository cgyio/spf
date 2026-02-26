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
     * 动态组件相关
     */
    //获取此组件的父组件，指定父组件名则查找第一个，否则直接返回 $parent，未找到则返回 undefined
    async $parentComp(compName='') {
        let is = this.$is,
            iss = s => is.string(s) && s!=='',
            pc = this.$parent;
        if (!iss(compName)) return pc;
        let ispc = v => {
            if (!is.vue(v) || !is.string(v.$options.name)) return false;
            let vn = v.$options.name,
                cn = compName;
            if (cn.startsWith('*-')) {
                cn = cn.substring(2);
                return vn.endsWith(cn);
            } else if (cn.endsWith('-*')) {
                cn = cn.substring(0, cn.length-2);
                return vn.startsWith(cn);
            } else {
                return vn === cn;
            }
        };
        while (!is.undefined(pc) || !ispc(pc)) {
            pc = pc.$parent;
        }
        await this.$wait(10);
        return pc;
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

    /**
     * 为组件实例增加 Mustache 语句解析能力，生成求值函数
     * 获取语句的值 应在 计算属性中 调用求值函数，并传入 $this 组件实例
     */
    mustache(code, ...types) {
        if (!this.$is.nemarr(types)) types = [String, Boolean];
        return this.$cgy.mustache(code, types);
    },
    /**
     * 解析 {}|[] 中可能存在的 Mustache 语句 或 求值函数，
     * 如果存在 则解析并求值，将返回值填入 返回的 {}|[] 中
     */
    parseMustacheIn(ps={}) {
        let is = this.$is,
            must = this.$mustache,
            ism = m => must.isMustache(m),
            ismfn = fn => must.isMustacheFn(fn);
        //不是有效的 {}|[] 原样返回
        if (!is.nemarr(ps) && !is.nemobj(ps)) return ps;
        let isa = is.array(ps),
            //写入结果
            setval = (res, val, key) => {
                if (isa) {
                    res.push(val);
                } else {
                    res[key] = val;
                }
                return res;
            },
            rtn = isa ? [] : {};
        this.$each(ps, (v,i)=>{
            //先处理 键为 Mustache 表达式的情况
            if (!isa && ism(i)) {
                if (i.trim().endsWith(']')!==true) i = `${i} [Boolean]`;
                let iv = this.mustache(i)(this);
                if (iv===true) {
                    //仅当 键表达式值 == true 时才合并
                    let pv = this.parseMustacheIn(v);
                    if (is.nemobj(pv)) {
                        rtn = Object.assign(rtn, pv);
                    }
                }
                return true;
            }

            //递归调用
            if (is.nemarr(v) || is.nemobj(v)) {
                rtn = setval(rtn, this.parseMustacheIn(v), i);
                return true;
            }
            //如果是 Mustache 语句，解析并调用求值函数
            if (ism(v)) {
                rtn = setval(rtn, this.mustache(v)(this), i);
                return true;
            }
            //如果是已解析的 求值函数，则调用
            if (ismfn(v)) {
                rtn = setval(rtn, v(this), i);
                return true;
            }
            //其他类型，直接填入返回数据
            rtn = setval(rtn, v, i);
        });
        //返回处理后的值
        return rtn;
    },
    

}