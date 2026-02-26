/**
 * SPF-Vcom 组件库 父组件(内部包含子组件的组件) 通用 mixin
 * 处理 子组件 props 的 参数透传 问题
 * 
 * 例如：此父组件包含子组件 foo,bar (可自定义名称，不一定是组件名，只要将最终的 subCompProps.foo|bar 绑定到正确的子组件即可)
 *      0   在父组件 data.subComps 中定义对应子组件的初始 props   形式为：
 *          this.data.subComps = {
 *              default: {
 *                  foo: {
 *                      # 对应组件的 props 初始值 ...
 * 
 *                  },
 *                  bar: { ... },
 *              }
 *          }
 *      1   在父组件的 props 中定义各子组件的 外部传入的额外 props 参数： 名称统一为 fooProps|barProps 形式
 *          !! 不定义也可以，此 mixin 会在计算属性 subCompProps 中尝试访问 this.$attrs[`${subCompName}Props`]
 *          this.props.fooProps = {type: Object, default: ()=>{return {};}}
 *          this.props.barProps = {type: Object, default: ()=>{return {};}}
 *      2   此 mixin 会定义计算属性 subCompProps 包含所有定义的子组件的处理后的 props，外部传入的覆盖 default 默认的
 *          在此父组件模板中，只需要将处理后的子组件 props 绑定(v-bind) 到对应的子组件即可，例如：
 *          <template> ... <PRE@-foo v-bind="subCompProps.foo">...</PRE@-foo> ... </template>
 *      3   如果某个子组件的 props 需要经过特殊处理（不仅仅是外部传入的覆盖 default 默认的）
 *          !! 需要额外定义计算属性：subComp[Foo|Bar]Props 有父组件自行处理 子组件的 props 传入覆盖默认以及其他操作
 *          !! 如果存在计算属性 subComp[Foo|Bar]Props 则在计算属性 subCompProps 中将直接调用，而不是执行通用覆盖操作
 *      4   可通过在此父组件中定义 withFoo|withBar 来 开启|关闭 对应的子组件 props 计算操作
 *          !! 不定义也可以，此 mixin 会在计算属性 subCompProps 中尝试访问 this.$attrs[`with${subCompName.ucfirst()}`]
 *          如果 withFoo === false 则 计算属性 subCompProps 中将 不包含 foo 子组件的 props
 *              
 */

export default {
    props: {
        //子组件外部传入的 props 用于覆盖当前父组件内部定义的子组件默认的 props
        /* !! 可不定义，计算属性会尝试从 $attrs 中查找
        fooProps: {
            type: Object,
            default: () => {
                return {};
            }
        },
        */

        //是否启用某个子组件
        /* !! 可不定义，计算属性会尝试从 $attrs 中查找
        withFoo: {
            type: Boolean,
            default: false
        },
        */
    },
    data() {return {
        //定义此父组件内部的子组件的 默认 props
        subComps: {
            default: {
                /*
                foo: {
                    # 如果只有简单的求值逻辑，可使用 Mustache 表达式，复杂逻辑 使用自定义的计算属性 subCompFooProps 
                    fooPropA: '{{foo.bar == "" ? "dft" : foo.bar}}[String]',

                    # 也可以直接解析 Mustache 表达式得到 求值函数
                    fooPropsB: this.mustache('{{...}}', String, Boolean, ...),

                    # 还可以 以 Mustache 表达式为键名，此表达式的返回值必须是 Boolean
                    # 表示只有当 此表达式的值为 true 时，才会将此配置键对应的值{} 合并到子组件 props 中
                    # 值{} 中也可以嵌套 Mustache 表达式
                    '{{foo.bar===true}} [Boolean 可省略]': {
                        fooPropsC: '{{...}} [Array]',
                        ...
                    }
                },
                */
            },
            //
        },
    }},
    computed: {
        /**
         * 根据 withFoo 获取各子组件的 enable 状态  返回 {foo: true, bar: false, ...}
         * !! 在模板中可以直接 v-if="subCompEnabled.foo" 开关某个区域  而 不需要定义并使用 props.withFoo
         */
        subCompEnabled() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                scdft = this.subComps.default || {};
            if (!iso(scdft)) return {};
            let rtn = {};
            this.$each(scdft, (scc,scn) => {
                //withFooBar 形式
                let wk = `with${scn.toCamelCase(true)}`,
                    //尝试读取 this.withFooBar 不存在则读取 this.$attrs.withFooBar
                    wv = is.defined(this[wk]) ? this[wk] : (
                        is.defined(this.$attrs[wk]) ? this.$attrs[wk] : false   //默认不启用
                    );
                //写入
                rtn[scn] = is.boolean(wv) && wv===true;
            });
            return rtn;
        },
        //根据 withFoo 形式的 props 筛选启用 props 计算的 子组件  返回 名称数组
        enabledSubComps() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                sce = this.subCompEnabled || {};
            if (!iso(sce)) return [];
            let rtn = [];
            this.$each(sce, (scc,scn) => {
                //仅保留启用的 子组件名
                if (scc===true) rtn.push(scn);
            });
            return rtn;
        },
        //处理 subComps.default 中可能定义的 Mustache 语句求值函数，求值并返回当前值
        getSubCompDefaultProps() {
            let is = this.$is,
                esc = this.enabledSubComps || [],
                dft = this.subComps.default || {},
                rtn = {};
            if (!is.nemarr(esc)) return {};
            this.$each(esc, (scn,i) => {
                if (!is.nemobj(dft[scn])) {
                    rtn[scn] = {};
                    return true;
                }
                //处理可能存在的 Mustache 语句 或 求值函数
                rtn[scn] = this.parseMustacheIn(dft[scn]);
            });
            return rtn;
        },

        /**
         * !! 核心计算属性 处理 子组件 外部传入的 props 覆盖 内部定义的 default props 
         * 将会根据 withFoo 自动筛选启用的 子组件
         * 返回所有启用的子组件的处理后的 props{}
         */
        subCompProps() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                scs = this.enabledSubComps,
                //通用合并操作后的 子组件 props
                csp = this.combinedSubCompProps,
                rtn = {};
            if (!isa(scs)) return {};
            this.$each(scs, (scn,i) => {
                //首先查找 是否存在 自定义的 子组件 props 计算属性 subCompFooBarProps
                let ck = `subComp${scn.toCamelCase(true)}Props`;
                if (is.defined(this[ck])) {
                    rtn[scn] = iso(this[ck]) ? this[ck] : {};
                    return true;
                }
                //如果不存在自定义计算属性  直接返回通用合并后的 props
                let rtni = csp[scn] || {};
                if (!iso(rtni)) rtni = {};
                //写入
                rtn[scn] = rtni;
            });
            return rtn;
        },

        /**
         * 返回所有启用子组件的 外部传入 和 内部定义 的 props 合并后的 props{}
         * !! 在自定义 subCompFooBarProps 计算属性内部可直接引用
         * !! 避免在每个自定义计算属性内部都要手动合并一次 props
         */
        combinedSubCompProps() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                scs = this.enabledSubComps || [],
                dft = this.getSubCompDefaultProps || {},
                rtn = {};
            if (!isa(scs)) return {};
            this.$each(scs, (scn,i) => {
                //执行通用的 props 合并操作
                //fooBarProps 形式
                let pk = `${scn.toCamelCase()}Props`,
                    //尝试读取 this.fooBarProps 不存在则读取 this.$attrs.fooBarProps
                    pv = is.defined(this[pk]) ? this[pk] : (
                        is.defined(this.$attrs[pk]) ? this.$attrs[pk] : {}
                    ),
                    //读取组件内定义的子组件默认 props
                    dv = dft[scn] || {},
                    //合并到
                    rtni = {};
                //合并外部传入的 和 内部定义的
                if (iso(dv)) rtni = Object.assign(rtni, dv);
                if (iso(pv)) rtni = Object.assign(rtni, pv);
                //写入
                rtn[scn] = rtni;
            });
            return rtn;
        },

        /**
         * !! 如果某个子组件 props 需要经过其他处理，应在父组件内部定义对应 subCompFooProps 计算属性
         * 在其内部自行处理 props 覆盖操作，以及其他自定义的操作
         * 返回此子组件的 处理后的 props{}
         */
        //subCompFooProps() {},
    },
    methods: {

    }
}