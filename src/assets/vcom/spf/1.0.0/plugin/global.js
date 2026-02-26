/**
 * Vue2.* 插件 base
 * 
 * 全局方法
 * 以 Vue.***() 形式调用的方法
 * 
 */

export default {

    cvVersion() {
        console.log('SPF-Vcom version = 1.0');
    },

    /**
     * 修改 Vue 全局属性和方法
     * @param Object $opt
     * @return void
     */
    def(opt = {}) {
        //Object.assign(Vue, $opt);
        Vue.cgy.def(Vue, opt);
    },

    /**
     * 根据传入的字符串查找对应的 组件库名称
     * 在 Vue.vcom.list 中保存了所有组件库名称
     * @param {String} key 包含组件库名称的字符串 如：base-button  pms-table
     * @return {String} 找到的组件库名称
     */
    getVcomName(key) {
        if (!Vue.cgy.is.string(key) || key === '') return null;
        //如果以 . 开头 （在处理样式类名时 会出现）
        if (key.startsWith('.')) key = key.substring(1);
        //以连字符 - 分割
        let glue = '-',
            karr = key.split(glue),
            vcs = Vue.vcom.list || [];
        for (let i=karr.length; i>=1; i--) {
            let arr = karr.slice(0,i),
                vcn = arr.join(glue);
            if (!vcs.includes(vcn)) continue;
            return vcn;
        }
        //未找到
        return null;
    },

    /**
     * 确保某个组件已被定义
     * @param {String} key 组件名
     * @return {String} 如果组件已被定义，则返回 key 否则返回 null
     */
    ensureVcn(key) {
        if (!Vue.cgy.is.string(key) || key === '') return null;
        //if (!Vue.cgy.is.defined(Vue.options.components[key])) return null;
        if (Vue.cgy.is.empty(Vue.component(key))) return null;
        return key;
    },

    /**
     * 全局获取某个组件的名称，将使用对应组件库的 prefix 替换组件库名
     * 例如：存在组件 pms 其 prefix = spf-pms 则查询组件 pms-table 将返回 spf-pms-table
     * @param {String} key 组件名，以 组件库名称开头的
     * @return {String} 实际存在的 组件名称
     */
    vcn(key) {
        let cn = Vue.getVcomName(key);
        //未找到对应的 组件库名称，则原样返回
        if (!Vue.cgy.is.string(cn) || cn === '') return Vue.ensureVcn(key);
        //用组件库 prefix 替换 key 中的组件库名称
        let pre = Vue.vcom[cn].prefix || null;
        if (!Vue.cgy.is.string(pre) || pre === '') return Vue.ensureVcn(key);
        return Vue.ensureVcn(key.replace(`${cn}-`, `${pre}-`)); 
    },

    /**
     * 判断传入的 组件名 是否有效
     * @param {String} key
     * @return {Boolean}
     */
    isVcn(key) {
        let is = Vue.cgy.is,
            vcn = Vue.vcn(key);
        return is.string(vcn) && vcn!=='';
    },



    /**
     * 在 Vue.use(plugin) 方法中使用的 工具方法
     * 任意 vcom 组件库插件，可在 install 方法中使用这些工具
     */

    /**
     * 处理外部传入的 options
     * @param {Object} options 外部传入的 插件启动参数
     * @return {Object} 处理后剩余的 options 参数
     */
    useInstallOptions(options) {
        if (!cgy.is.plainObject(options)) return options;

        //将外部传入的 options 中可能存在的 服务的个性化参数，覆盖到 Vue.service.options
        if (cgy.is.defined(options.service)) {
            let srv = options.service;
            if (cgy.is.plainObject(srv)) {
                Vue.service.options = cgy.extend(Vue.service.options, srv);
            }
            Reflect.deleteProperty(options, 'service');
        }

        //外部传入的 options 中可能包含的 组件列表参数，覆盖到 Vue.vcoms
        if (cgy.is.defined(options.vcoms)) {
            let vcoms = options.vcoms;
            if (cgy.is.plainObject(vcoms)) {
                cgy.each(vcoms, (v,k) => {
                    if (cgy.is.undefined(Vue.vcoms[k]) || !cgy.is.plainObject(v) || cgy.is.empty(v)) return true;
                    Vue.vcoms[k] = cgy.extend(Vue.vcoms[k], v);
                });
            }
            Reflect.deleteProperty(options, 'vcoms');
        }
        
        //将处理后的 options 返回，以供后续使用
        return options;
    },

    /**
     * 批量注册 全局|异步 组件
     * 组件列表收集在 Vue.vcoms 中
     * @return {Boolean} 
     */
    defineVcomComponents () {
        let is = cgy.is;
        if (!is.plainObject(Vue.vcoms) || is.empty(Vue.vcoms)) return true;
        //注册 全局|异步 组件
        cgy.each(Vue.vcoms, (v,k) => {
            if (!is.plainObject(v) || is.empty(v)) return true;
            //只支持 global|async 组件形式
            if (['global', 'async'].includes(k) !== true) return true;

            //依次定义 global|async 形式组件
            cgy.each(v, (vcd,vcn) => {
                /**
                 * 组件定义只能是：
                 *  0   {mixins:[], data: {}, methods:{}, template:``}
                 *  1   () => import('component-url')
                 *  2   'component-url'
                 */
                //传入了 0,1 形式的 组件定义
                if (
                    (is.plainObject(vcd) && !is.empty(vcd)) ||
                    is(vcd, 'function,asyncfunction')
                ) {
                    Vue.component(vcn, vcd);
                }

                //传入了 2 形式的 组件定义
                if (
                    is.string(vcd) && 
                    (vcd.startsWith('http') || vcd.startsWith('/'))
                ) {
                    Vue.component(vcn, ()=>import(vcd));
                }
            });
            
        });

        return true;
    },

    /**
     * 批量创建 Vue.service.support 中定义的 服务的 特殊组件实例，并挂载到 Vue.service
     * @param {Object} options 外部传入的 插件的 install 参数
     * @return {Object} 处理后剩余的 外部参数
     */
    createVcomService(options) {
        //创建 服务单例
        let service = Vue.service || {},
            srvs = service.support || [],
            imps = service.imports || {},
            opts = service.options || {};

        //外部传入的 options.useService[] 覆盖内部的 Vue.service.support
        if (cgy.is.defined(options.useService)) {
            let usrvs = options.useService;
            if (cgy.is.array(usrvs) && usrvs.length>0) {
                srvs = usrvs;
                Vue.service.support = usrvs;
            }
            Reflect.deleteProperty(options, 'useService');
        }

        //依次创建 服务对应的 特殊组件实例
        cgy.each(srvs, srv => {
            let opti = opts[srv] || {},
                impi = imps[srv];
            //外部可指定 启用|关闭 服务
            if (cgy.is.plainObject(opti) && cgy.is.defined(opti.enable) && opti.enable === false) return true;
            //如果没有 import 服务的定义
            if (!cgy.is.plainObject(impi) || cgy.is.empty(impi)) return true;

            //创建服务单例
            let srvo = new Vue({
                mixins: [impi]
            });

            /**
             * 某些服务的 特殊代码
             */
            if (srv === 'bus') {
                //事件总线服务 初始化 事件的保存参数
                srvo.event = {};
            }

            /**
             * 将服务的 async init() 初始化方法，添加到 Vue.service.initSequence 序列
             */
            if (cgy.is.defined(srvo.init) && cgy.is(srvo.init, 'asyncfunction')) {
                //init 方法插入序列，同时绑定 服务的个性化参数
                let srvInit = srvo.init.bind(srvo, opti);
                //将服务名称 挂载到 init.serviceName 以便报错时提供 
                srvInit.serviceName = srv;
                //插入 initSequence 序列
                Vue.service.initSequence.push(srvInit);
            }

            //挂载到 Vue.service
            cgy.def(Vue.service, {
                [srv]: srvo
            });
            //挂载到 Vue.prototype
            cgy.def(Vue.prototype, {
                [`$${srv}`]: srvo
            });

        });

        return options;
    },



    /**
     * 执行 Vue.service.initSequence 中的所有方法
     * 在 启动序列 initSequence 中的所有方法，都必须是 async 方法
     * @return {Boolean|String} 所有服务 init 成功则返回 true，否则返回出错的 服务名称
     */
    async initServicesInSequence() {
        let seq = Vue.service.initSequence,
            rst = true;
        if (!cgy.is.array(seq) || seq.length<=0) return true;
        //依次执行
        for (let i=0;i<seq.length;i++) {
            let init = seq[i];
            //已注册的 init 方法必须是 asyncfunction
            if (!cgy.is(init, 'asyncfunction')) continue;
            //已注册的 init 方法，已经 bind 了 options 参数，不需要额外提供
            let rsti = await init();
            //所有服务的 init 方法必须返回 true 
            rst = rst && rsti;
            if (rst !== true) {
                //任意一个服务未成功 init 则返回服务名称，并退出，在外层方法中报错
                return init.serviceName || 'unknown';
            }
        }
        await cgy.wait(100);
        return rst;
    },

    /**
     * 在 Vue.$root 根组件 created 后执行所有服务组件的 afterRootCreated 方法
     * @param {Vue} Vue.$root 根组件
     * @return {Boolean}
     */
    async initServiceAfterRootCreated(root) {
        let is = Vue.cgy.is,
            srvs = Vue.service.support;

        //依次执行
        await Vue.cgy.each(srvs, async (srvn, i)=>{
            let srv = Vue.service[srvn];
            if (!is.vue(srv)) return true;
            let iti = await srv.afterRootCreated(root);
            if (iti !== true) {
                //失败
                root.$log.error(`服务组件 ${srvn}.afterRootCreated() 方法执行失败`);
                //终止后续
                return false;
            }
        });

        return true;
    },

    /**
     * 扩展 vue 根组件实例创建方法 app = new Vue(...)
     * 将生成的根组件实例挂载到 Vue.$root
     * @param Object $opt 根组件参数
     * @return Vue instance
     */
    rootApp(opt = {}) {
        //必须在 所有服务 init 完成之后 实例化 $root
        Vue.initServicesInSequence().then(inited => {
            //console.log(inited);
            if (inited !== true) {
                throw new Error(`Vcom 组件库服务 ${inited} 未能正确初始化`);
            } else {
                let app = new Vue(opt);
                if (app instanceof Vue) {
                    Vue.$root = app;
                    //在 任意组件实例内部访问 $root
                    Vue.prototype.$root = app;
                    window.app = app;
                    window.vcomRoot = app;
                    
                    app.$elReady().then(()=>{
                        //Vue.nav.start();
                    });
                    return app;
                }
            }
        });
    },


    /**
     * Mustache 表达式解析器
     * Vue 组件实例通过 $mustache 调用此解析器
     */
    mustache: {
        /**
         * !! 入口方法
         *      const getter = Vue.mustache.def('{{exp}}', [返回值可选类型])
         *      const value = getter( this-context 通常为组件实例, false 是否忽略返回值可选类型直接返回原始值 )
         * 
         * 实现 Vue 组件模板中 {{...}} 内部的 Mustache 表达式的解析，以及基于 this(绑定组件实例) 的 变量取值方法
         * 解析语句，并返回 求值函数
         * fn = cgy.mustache('{{code}}', [表达式最终值类型数组...])
         * fn(传入this上下文，通常为vue组件实例) --> 得到当前值 可在计算属性中使用
         * @param {String} code 要解析的表达式
         * @param {Array} _types 这个表达式期望的返回值类型，可以有多个
         *                  !! 如果表达式语句末尾自带类型声明 {{...}}[Boolean] 则优先使用声明的返回值类型
         * @return {Function} 解析得到的求值函数  this.isMustacheFn(rtn) === true
         */
        defineGetter(code='', _types=[String]) {
            let is = cgy.is;

            //传入空字符串直接根据 _types 返回 返回空值的 函数
            if (!is.nemstr(code)) return () => this.empty(_types);
            code = code.trim();

            //!! 可在传入的 code 中包含返回值类型 {{...}}[String,Boolean] 
            let stpsReg = /\[[^\]]+\]$/g,
                stps = code.match(stpsReg);
            if (is.nemarr(stps)) {
                code = code.replace(stps[0], '').trim();
                //code 中包含的返回值类型，将覆盖传入的 _types
                //!! 如果 code 中包含的 类型声明 无效，将使用默认类型 [String]
                _types = (() => {
                    let gfn = new Function('return '+stps[0]+';'),
                        gtps;
                    try {
                        gtps = gfn();
                    } catch (e) {
                        console.error(`Mustache 表达式返回值类型声明错误！${stps[0]}`, e);
                        //return () => {throw new Error(`MustacheError: ${e.message}`)}
                        gtps = [String];
                    }
                    return gtps;
                })();
            }

            //解析 Mustache 语句
            let parsed = this.parse(code);
            if (!is.nemstr(parsed)) return () => this.empty(_types);
            //根据 parsed 代码段，判断是否需要包裹 `` 字符串模板
            let istpl = ['true','false'].includes(parsed)!==true && (parsed.includes('${') || !parsed.includes('this.'));
            //!! 如果 parsed 代码段需要包裹 `` 字符串模板，则修改 _types 为 [String]
            if (istpl) _types = [String];

            //根据 parsed 代码段，生成动态求值函数
            let fnstr = istpl ? '`'+parsed+'`' : parsed,
                //!! 需要指定 1个参数 $args 这是此函数执行时外部传入的 额外参数组成的数组，函数体内部通过 $args[*] 访问
                fn = new Function('$args', 'return '+fnstr+';'),
                //提前计算空值
                empv = this.empty(_types),
                //要返回的包裹函数 _this 表示要绑定的上下文，_origin 表示是否忽略返回值类型，原样返回
                /**
                 * 最终生成的 表达式求值函数
                 * @param {Object} _this 此求值函数运行时的 上下文 通常是 Vue 组件实例
                 * @param {Boolean} _origin 是否忽略返回值类型声明，原样返回
                 * @param {Array} $args 此求值函数可以接收 额外的参数
                 * !! 新增 $args 可以向求值函数额外传入更多参数，函数体内部可通过 $args 关键字访问这些参数
                 */
                getter = (_this, _origin=false, ...$args) => {
                    //求值
                    let val;
                    try {
                        //!! 将外部传入的任意多个额外参数，以 数组形式单参数 $args 传入 求值函数
                        val = fn.call(_this, $args);
                    } catch (e) {
                        console.error(`Mustache 表达式执行失败！ [ return ${fnstr}; ]`, e);
                        //return () => {throw new Error(`MustacheError: ${e.message}`)}
                        //!! 求值错误，直接返回空值
                        return empv;
                    }
                    //如果未定义 返回值可选类型，直接返回求得的值
                    if (!is.nemarr(_types)) return val;
                    //判断类型  类型不符合则返回空值
                    if (_origin!==true && Vue.mustache.inTypes(val, _types)!==true) return empv;
                    //返回最终求得的值
                    return val;
                };
            //!! 标记这是一个求值函数
            getter._isMustacheFn = true;
            //forDev
            getter._getterFunctionBody = 'return '+fnstr+';';
            //返回
            return getter;
        },

        /**
         * !! 核心解析方法
         * 解析 Mustache 表达式，为其中的变量名 添加 this. 前缀
         * !! 传入的 code 必须包裹在 {{}} 中
         * !! 如果传入的 code 包含 {{...}} 则只将 {{}} 内部的字符串作为 Mustache 语句，外部的字符串原样拼接
         * !! 使用字符串模板 `...${}...` 拼接处理后的 内外字符串
         * !! 如果 code 不含任何 {{}} 字符，则原样返回
         * 
         * 变量名可能的写法：
         *      foo                 --> this.foo
         *      foo.bar             --> this.foo.bar
         *      foo[bar]            --> this.foo[this.bar]
         *      foo[0]              --> this.foo[0]
         *      foo.bar['key']      --> this.foo.bar['key']
         *      foo.bar[bar[jaz[...]]]      --> 支持嵌套 this.foo.bar[this.bar[this.jaz[...]]]
         * !! 变量名需要满足 javascript 变量命名规则：
         *      只能包含 [a-zA-z0-9_$] 且 开头不能是 数字
         * 
         */
        parse(code) {
            let is = cgy.is,
                iss = s => is.string(s) && s!=='',
                //变量命名规则
                vreg = /[a-zA-Z_$][a-zA-Z0-9_$]*/g,
                //内部包含 {{...}} 的语法正则
                sreg = /\{\{[^\{\}]+\}\}/g,
                //包裹在 '' 或 "" 中的字符串
                qreg = /('[^']*')|("[^"]*")/g;
            //空字符串
            if (!iss(code)) return '';
            //不含 {{}} 的字符串
            //if (!code.includes('{{') && !code.includes('}}')) return code;
            if (!this.isMustache(code)) return code;
            //trim
            code = code.trim();

            //如果整体包裹在 {{}} 中
            //if (is.nemarr(code.match(areg))) {
            if (this.isPureMustache(code)) {
                //查找 前1个或n个字符 如果是空格 继续向前查找，直到字符串开头 或 找到不是空格的字符，返回找到的字符
                let findPrev = (offset, str) => {
                    if (offset<=0) return '';
                    let i = offset - 1,
                        find = str[i];
                    while (find===' ' && i>0) {
                        i--;
                        find = str[i];
                    }
                    return find===' ' ? '' : find;
                };
                //去除头尾 {{}}
                code = code.slice(2, code.length-2).trim();
                //在这些字符之后的变量名不加 this.
                let except = `. ' "`.split(' '),
                    //这些允许的关键字不加 this.
                    //!! 增加 $args 作为特殊关键字，用于在求值函数内部访问除了 _this 上下文之外的 其他参数 例如：$args[0]
                    kws = 'true,false,null,undefined,$args'.split(',');
                //先拆分出 包裹在 '' 或 "" 中的纯字符
                let idx = -1,
                    strs = [];
                code = code.replace(qreg, (match, offset, str) => {
                    idx++;
                    strs.push(match);
                    return ' @'+idx+' ';
                });
                //给所有符合变量名规则的 前面加 this.
                code = code.replace(vreg, (match, offset, str) => {
                    //检查关键字
                    if (kws.includes(match.toLowerCase())) return match;
                    const prevChar = findPrev(offset, str);
                    //在 except 排除的字符之后的变量名 不加 this.
                    if (except.includes(prevChar)) return match;
                    return `this.${match}`;
                });
                //再回填 '' 或 "" 中的纯字符
                for (let i=0;i<strs.length;i++) {
                    code = code.replace(' @'+i+' ', strs[i]);
                }
                //返回处理后的 代码段
                return code;
            }

            //如果内部包含 {{}} 则只处理 {{}} 内部的语句，最终返回值只能是 String
            let idx = -1,
                //拆分出 {{}} 外部字符串
                out = code.replace(sreg, (match, offset, str) => {
                    idx++;
                    return '${__CODE_'+idx+'__}';
                });
            //开始依次处理 {{}} 内部的 Mustache 语句
            cgy.each(code.match(sreg), (codei, i) => {
                let ck = '__CODE_'+i+'__',
                    parsed = this.parse(codei);
                //替换 out 语句中的 __CODE_n__
                out = out.replace(ck, parsed);
            });
            return out;
        },

        /**
         * 解析 作为 配置参数键名 的 Mustache 表达式，生成完整的表达式，以及其他相关数据
         * 
         * Mustache 表达式作为 配置参数的键名，用于响应式计算组件实例的某种状态值，只有表达式返回值为 true 时，此配置才生效
         * !! 此类型表达式返回值一定是 Boolean 类型
         * 
         * 可以使用下列 语法：
         *      0   标准的 Mustache 表达式，必须返回 Boolean，可以省略末尾的 类型声明 [Boolean]
         *          '{{foo.bar}} [Boolean]'                 --> 求值 this.foo.bar
         *          '{{$is.nemstr(foo.bar)}}'               --> 求值 this.$is.nemstr(this.foo.bar)
         *          '{{foo.bar && $is.nemarr(foo.jaz)}}'    --> 求值 this.foo.bar && this.$is.nemstr(this.foo.jaz)
         * 
         *      1   可以省略 Mustache 表达式头尾的 {{}} 
         *          !! 省略 {{}} 后不能再写 [Boolean] 类型声明
         *          'foo.bar'
         *          '$is.nemstr(foo.bar)'
         *          'foo.bar && $is.nemarr(foo.jaz)'
         * 
         *      2   可以增加修饰符 @... #...
         *          !! 修饰符 必须写在 {{}} 外面，或省略 {{}}
         *          !! 必须写在键名的最后  必须与主语句使用 空格 隔开
         *          !! 不同的修饰符之间也必须以 空格 隔开
         *          !! 不同的修饰符排列顺序为：  {{主语句}}  @...@...  #...
         * 
         *          @ 修饰符指定 此项配置的 作用域
         *          '{{foo.bar}} @root@icon@btn'            --> 此配置作用于 root,icon,btn 3 个元素
         *          'foo.bar && $is.nemstr(atom.fs) @root'  --> 此配置仅作用于 root 元素
         * 
         *          # 修饰符用于 区分相同的状态条件 可以对相同的状态条件，指定不同的配置值
         *          '{{foo.bar}} #1'
         *          '{{foo.bar}} [Boolean] #2'
         *          'foo.bar #3'
         *          'foo.bar || foo.jaz #4'
         * 
         *      !! 所有 配置参数键名表达式的特殊语法 都必须在省略 {{}} 的情况下生效
         * 
         *      3   可以简写 $is.***() 系列类型判断函数
         *          '?nemstr(foo.bar) && ?nemarr(foo.jaz) && !foo.tom'
         *          '?defined(foo.bar.key) || ?vue(subComp)'
         * 
         *      4   直接在键名表达式中输入某个上下文的变量名  将根据此变量值的类型，决定返回的 Boolean 值
         *          'foo.bar'       --> 根据 this.foo.bar 的实际类型，决定生成的 求值函数：
         *                              Boolean     --> return this.foo.bar
         *                              String      --> return this.$is.nemstr(this.foo.bar)
         *                              Array       --> return this.$is.nemarr(this.foo.bar)
         *                              Object      --> return this.$is.nemobj(this.foo.bar)
         *          !! 键名表达式中不能出现多个 上下文变量名，以及其他字符（如：运算符，圆括号，空格）
         *          !! 修饰符不受影响，可以使用
         * 
         *          !! 如果要合并多个上下文变量，使用 $is.***() 函数简写方式
         *          '?(foo.bar)'                    --> 将根据 this.foo.bar 的实际类型，生成求值函数
         *          '?(foo.bar) || ?(foo.jaz)'      --> 可以合并多个监听变量
         *          
         * 此方法实现上述语法的解析，返回解析后得到的 完整的 Mustache 表达式， {{exp}} [Boolean] 以及其它数据
         * @param {String} key 配置参数键名表达式
         * @param {Object} context 解析此表达式时的 上下文 通常是某个组件实例
         * @return {Object} 返回值格式：
         *      {
         *          origin: '原表达式字符串',
         *          modify: {   # 原表达式中 可能存在的 修饰符数据
         *              '@': [],
         *              '#': [],
         *          },
         *          mustache: '{{exp}} [Boolean]',      # 解析生成的 完整 Mustache 表达式，返回值一定是 Boolean 类型
         *          getter: function() {},              # 解析生成的 表达式求值函数
         *      }
         * !! 当传入的 表达式不合法时，返回 null
         */
        //!! 定义允许的 键名表达式 修饰符，定义顺序 与 使用顺序相反
        keyExpressionModify: ['#', '@'],
        //!! 定义 keyExpression 键名表达式的 支持语法正则  不含 修饰符字段
        keyExpressionSyntaxReg: [
            /\?\([^\)]+\)/g,                        //?(var)
            /\?[^\(]+\([^\)]+\)/g,                  //?fn(var)
            /^[a-zA-Z_$][a-zA-Z0-9_$.\[\]'"]*$/g,   //直接使用某个 变量名
        ],
        parseKeyExpression(key='', context={}) {
            let is = cgy.is,
                //解析 Mustache 表达式 生成求值函数
                def = (...args) => this.defineGetter(...args),
                //支持自动判断空值的 类型
                types = [String, Boolean, Number, Array, Object],
                //将传入的某个上下文变量名 作为表达式进行立即求值，用于判断此上下文变量的 类型
                getVal = v => def(`{{${v}}}`, types)(context, true),
                //判断给定的值类型 是否在 types 列表中
                inTypes = v => is.string(v) || is.array(v) || is.boolean(v) || is.realNumber(v) || is.plainObject(v),
                //根据求到的值类型，为表达式包裹 $is 方法
                wrapIsFn = (v, exp) => {
                    //求值类型不在支持范围内，直接返回 'false' 作为表达式
                    if (!inTypes(v)) return 'false';
                    if (is.string(v)) return `$is.nemstr(${exp})`;
                    if (is.boolean(v)) return exp;
                    if (is.realNumber(v)) return `($is.realNumber(${exp}) && (${exp})>0)`;
                    if (is.array(v)) return `$is.nemarr(${exp})`;
                    if (is.palinObject(v)) return `$is.nemobj(${exp})`;
                    //默认
                    return 'false';
                },
                //标记 key 是否是有效的 键名表达式，当下方 0~4 任一条件满足时，此值即为 true  
                //如果此值为 false 则表示 key 不是有效的键名表达式，解析结果将返回 null
                //isKeyExp = false,
                //解析返回值
                rtn = {
                    //原始的 键名表达式字符串
                    origin: key,
                    //如果传入的键名表达式 属于第 3 种情况，此处保存对应的 变量名
                    variable: null,
                    //可能存在的 修饰符数据  例如： ...key... @root@icon@btn #1
                    modify: {
                        //'#': ['1'],
                        //'@': ['root', 'icon', 'btn']
                    },
                    //处理后的 Mustache 表达式
                    mustache: '',
                    //生成的 求值函数
                    getter: null,
                };
            if (!is.nemstr(key)) return null;

            // 0    处理可能存在的 修饰符 #|@
            let mods = this.keyExpressionModify,    //!! 修饰符定义顺序不可变动，因为需要按此顺序依次解析
                hasMods = mods.reduce((has,modi,i) => has || key.includes(` ${modi}`), false);
            if (hasMods) {
                //标记 isKeyExp
                //isKeyExp = true;

                let moda = {},  //{ '#':['1','2',..], '@':['root','elm',..], ... }
                    ka = [];
                cgy.each(mods, (modi,i) => {
                    moda[modi] = [];
                    if (!key.includes(` ${modi}`)) return true;
                    //按修饰符 split
                    ka = key.split(modi);
                    if (ka.length>1) {
                        //key 为截取的 前部字符串
                        key = ka[0].trim();
                        //此 修饰符对应的 修饰数据数组
                        moda[modi].push(...ka.slice(1).map(i=>i.trim()));
                    }
                });
                //保存到解析结果
                rtn.modify = moda;
            }

            // 1    处理标准 Mustache 表达式  只能是 纯表达式，不能是 ...{{...}}... 形式的混合表达式
            //      !! true|false 单个单词也作为纯表达式： 'true @foo @... #foo' 有效
            //if (key.startsWith('{{') && key.includes('}}')) {
            if (this.isPureMustache(key) || ['true','false'].includes(key)) {
                //标记 isKeyExp
                //isKeyExp = true;

                //添加 [Boolean] 类型声明
                if (!is.nemarr(key.match(/\}\}\s*\[[^\]]+\]$/g))) {
                    if (['true','false'].includes(key)) {
                        rtn.mustache = `{{${key}}} [Boolean]`;
                    } else {
                        rtn.mustache = `${key} [Boolean]`;
                    }
                } else {
                    rtn.mustache = key;
                }
                //创建 求值函数
                rtn.getter = def(rtn.mustache);
                //直接返回
                return rtn;
            }

            //!! 预定义的语法规则
            let syntax = this.keyExpressionSyntaxReg;

            // 2    处理表达式 特殊语法 生成 Mustache 表达式
            // 2.1  ?(var) 
            let rega = syntax[0];   // /\?\([^\)]+\)/g;
            if (is.nemarr(key.match(rega))) {
                key = key.replace(rega, (match, offset, str) => {
                    let vk = match.substring(2, match.length-1),
                        //将 () 内部作为 Mustache 语句，进行求值(返回原始值，不做类型处理)
                        vv = getVal(vk);
                    //根据变量值的类型，返回包裹了 $is 方法的表达式，不支持的类型直接返回 false 作为表达式
                    return wrapIsFn(vv, vk);
                });
                
                //标记 isKeyExp
                //isKeyExp = true;
            }
            // 2.2  ?isfn(var)
            let regb = syntax[1];   // /\?[^\(]+\([^\)]+\)/g;
            if (is.nemarr(key.match(regb))) {
                key = key.replace(regb, (match, offset, str) => {
                    let fn = match.split('(')[0].substring(1),  //$is 方法名
                        //(var) 字符串
                        vs = '(' + match.split('(').slice(1).join('(');
                    //拼接 $is.fn(var)
                    return `$is.${fn}${vs}`;
                });
                
                //标记 isKeyExp
                //isKeyExp = true;
            }
            // 2.3  可扩展更多语法 ...

            // 3    针对直接传入 var 变量名的情况
            let regc = syntax[2];   // /^[a-zA-Z_$][a-zA-Z0-9_$.\[\]'"]*$/g;
            if (is.nemarr(key.match(regc))) {
                //保存输入的 var 变量名
                rtn.variable = key;

                //直接作为 Mustache 语句，进行求值(返回原始值，不做类型处理)
                let v = getVal(key);
                //根据变量值的类型，返回包裹了 $is 方法的表达式，不支持的类型直接返回 false 作为表达式
                key = wrapIsFn(v, key);
                
                //标记 isKeyExp
                //isKeyExp = true;
            }

            //  4   可扩展更多 ...


            //解析完成，准备返回
            //如果 isKeyExp !== true 表示这不是有效的 键名表达式，返回 null
            //if (isKeyExp!==true) return null;
            //解析后的 key 必须为 非空字符串
            if (!is.nemstr(key)) return null;
            //生成的 表达式
            rtn.mustache = `{{${key}}} [Boolean]`;
            //创建 求值函数
            rtn.getter = def(rtn.mustache);
            //创建函数失败
            if (!this.isMustacheFn(rtn.getter)) return null;

            /**
             * !! 尝试 立即求值，如果发生错误，表示这不是一个有效的 keyExpression
             * !! 暂停
             */
            /*try {
                let res = rtn.getter(context);
            } catch (e) {
                //使用求值函数求值时，发生错误，表示这不是一个有效的 keyExpression 返回 null
                return null;
            }*/

            //返回
            return rtn;
        },

        /**
         * 递归解析某个组件实例上下文中的 对象|数组 中 所有可能包含的 Mustache 语句  返回求值后的 对象|数组
         * 
         * 针对键名使用表达式的  
         *      解析键名表达式并求值，如果值为 true 则将键名对应的键值 对象|数组 递归解析求值，合并到结果 对象|数组
         * 
         * 针对键值包含 Mustache 表达式的
         *      递归解析并求值，合并到结果 对象|数组
         * 
         * @param {Array|Object} ps 要递归解析的 {}|[]
         * @param {Object} context 解析时的上下文对象，通常是组件实例
         * @return {Array|Object} 合并所有解析求值结果的 {}|[]
         */
        __evaluate(ps={}, context={}) {
            let is = cgy.is,
                ism = m => this.isMustache(m),
                ismfn = fn => this.isMustacheFn(fn);
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
            
            cgy.each(ps, (v,i)=>{

                // 0    键名使用表达式 的情况
                //let kexp = !isa ? this.parseKeyExpression(i, context) : null;
                //if (is.nemobj(kexp) && ismfn(kexp.getter)) {
                if (!isa && ism(i)) {
                    //将此键名表达式求值，检查是否为 true
                    //if (kexp.getter(context)===true) {
                    if (this.defineGetter(i, [Boolean])(context)===true) {
                        //仅当 键名表达式值 == true 时才合并
                        let pv = this.evaluate(v, context);
                        if (is.nemobj(pv)) {
                            rtn = Object.assign(rtn, pv);
                        }
                    }
                    return true;
                }

                // 1    如果值是 {}|[] 递归解析并求值
                if (is.nemarr(v) || is.nemobj(v)) {
                    rtn = setval(rtn, this.evaluate(v, context), i);
                    return true;
                }

                // 2    如果值是 Mustache 语句，解析并调用求值函数
                if (ism(v)) {
                    rtn = setval(rtn, this.defineGetter(v)(context), i);
                    return true;
                }

                // 3    如果值是已解析的 求值函数，则调用
                if (ismfn(v)) {
                    rtn = setval(rtn, v(context), i);
                    return true;
                }

                // 4    值其他类型，直接填入返回数据
                rtn = setval(rtn, v, i);
            });

            //返回处理后的值
            return rtn;
        },


        //工具
        /**
         * 根据传入的 可选类型，返回适当的 空值
         * 在 [可选类型数组] 中，优先返回排在前面的 类型的 空值
         */
        empty(types=[]) {
            let is = cgy.is,
                //默认空值 ''
                dft = '';
            //未指定可选类型数组
            if (!is.nemarr(types)) return dft;
            //空值返回方法
            switch (types[0]) {
                case String:    return '';      break;
                case Boolean:   return false;   break;
                case Number:    return 0;       break;
                case Array:     return [];      break;
                case Object:    return {};      break;

                default:        return dft;     break;
            }
        },
        //判断给定的值，是否在可选类型范围内
        inTypes(val, types=[]) {
            let is = cgy.is,
                rtn = false;
            //未指定可选类型数组
            if (!is.nemarr(types)) return false;
            //依次检查
            cgy.each(types, (type,i) => {
                switch (type) {
                    case String:    rtn = rtn || is.string(val);        if (rtn) {return rtn;}  break;
                    case Boolean:   rtn = rtn || is.boolean(val);       if (rtn) {return rtn;}  break;
                    case Number:    rtn = rtn || is.realNumber(val);    if (rtn) {return rtn;}  break;
                    case Array:     rtn = rtn || is.array(val);         if (rtn) {return rtn;}  break;
                    case Object:    rtn = rtn || is.plainObject(val);   if (rtn) {return rtn;}  break;
                }
            });
            return rtn;
        },
        //判断字符串是否整个包裹在 {{...}} 中的 Mustache 表达式
        isPureMustache(exp='') {
            let is = cgy.is,
                //reg = /^\{\{[^\{\}]+\}\}(\s?\[[^\]]+\])?$/g;
                reg = /^\{\{((.*\{.*\}.*)|([^\{\}]))*\}\}(\s*\[[^\[\]]+\])?$/g;
            return is.nemstr(exp) && is.nemarr(exp.match(reg));
        },
        //判断字符串是否包含 Mustache 表达式
        isMustache(exp='') {
            //return this.isPureMustache(exp) || this.isCompoundMustache(exp);
            let is = cgy.is,
                //reg = /\{\{[^\{\}]+\}\}/g;
                reg = /\{\{((.*\{.*\}.*)|([^\{\}]))*\}\}/g;
            return is.nemstr(exp) && is.nemarr(exp.match(reg));
        },
        //判断字符串是否有效的 键名表达式 keyExpression  需要传入上下文组件实例
        //!! 暂停
        __isKeyExp(exp='', context={}) {
            let is = cgy.is;
            if (!is.nemstr(exp)) return false;

            //排除修饰符
            let mods = this.keyExpressionModify,
                hasMods = mods.reduce((has,modi,i) => has || exp.includes(` ${modi}`), false);
            if (hasMods) {
                cgy.each(mods, (modi,i) => {
                    if (!exp.includes(` ${modi}`)) return true;
                    //按修饰符 split
                    let ka = exp.split(modi);
                    if (ka.length>1) {
                        //exp 为截取的 前部字符串
                        exp = ka[0].trim();
                    }
                });
            }

            //检查剩余的 exp
            // 0    {{...}} 纯 Mustache 表达式
            if (this.isPureMustache(exp)) return true;

            // 1    keyExpression 有效语法 可根据实际扩展
            let regs = this.keyExpressionSyntaxReg,
                //使用语法正则 测试
                isok = regs.reduce((ok, regi, idx) => ok || is.nemarr(exp.match(regi)), false);
            if (isok) {
                //针对 直接传入某个变量的 情况
                if (is.nemarr(exp.match(regs[2]))) {
                    //变量名必须是 foo.bar 形式，而不能是 foo["bar"] 形式
                    if (is.nemarr(exp.match(/^[a-zA-Z_$][a-zA-Z0-9_$.]*$/g))) {
                        //尝试在上下文组件实例中查找此变量，不存在则为 false
                        if (is.undefined(cgy.loget(context, exp))) return false;
                    }
                }
                return true;
            }
            
            // 2    尝试解析
            let kexp = this.parseKeyExpression(exp, context);
            if (!is.nemobj(kexp)) return false;
        },
        //判断一个函数是否 求值函数，检查 fn._isMustacheFn === true
        isMustacheFn(fn=null) {
            let is = cgy.is;
            return is.function(fn) && is.defined(fn._isMustacheFn) && fn._isMustacheFn===true;
        },
    },



    /**
     * request
     */
    request: new Proxy(
        async function(api, data={}, opt={}, useJwt=true) {
            Vue.ui.setRequestStatus('waiting');
            api = Vue.request.api(api);
            console.log(api);
            opt = cgy.extend({
                method: 'post',         //所有 request 默认 post
                responseType: 'json',   //所有返回值默认 json
                //headers: {}
            }, opt);

            //jwt Authorization
            if (useJwt==true) {
                if (Vue.request.uacIsOn()) {
                    //仅当 Vue.usr.uac == true 时，才会在 request 时附加 jwt-token
                    let usr = Vue.usr,
                        token = usr.getTokenFromLocal();
                    //console.log(token);
                    if (cgy.is.empty(token) || !cgy.is.string(token)) {
                        //当本地不存在 jwt-token 时，视为 用户未登录，弹出 cv-login 登录组件
                        let loginok = await usr.loginPanel();
                        //等待登录成功，1min 后超时
                        if (loginok) {
                            //登录成功后，再次获取 token
                            token = usr.getTokenFromLocal();
                        }
                    }
                    opt = cgy.extend(opt, {
                        headers: {
                            Authorization: token
                        }
                    });
                }
            }
            //console.log(api, data, opt);

            return await axios.post(api, data, opt).catch(err=>{
                return Vue.request.handleRequestError(err);
            });
        }, {
            get(target, prop, receiver) {
                let props = {
                    /**
                     * 根据 location.href 取得 api prefix
                     * 所有 api 默认增加一个 appname 前缀，因为通常的功能都是在 app 目录下
                     * 可通过 baseOptions.defaultApiPrefix 来指定特定的 api prefix
                     */
                    apiPrefix: () => {
                        let url = cgy.url().current(),
                            pre = url.uri.length<=0 ? '' : url.uri[0],
                            opre = Vue.baseOptions.defaultApiPrefix;
                        //if (cgy.is.defined(opre) && !cgy.is.empty(opre)) return opre;
                        if (cgy.is.defined(opre)) {
                            if (cgy.is.null(opre)) return '';
                            if (cgy.is.string(opre) && opre!='') return opre;
                        }
                        return pre;
                    },

                    /**
                     * 包裹 api 为正确格式
                     * foo/bar                  [host]/[pre]/api/foo/bar?format=json
                     * foo/bar?jaz=123          [host]/[pre]/api/foo/bar?jaz=123&format=json
                     * /foo/bar                 [host]/[pre]/foo/bar?format=json
                     * 
                     * location.href = [host]/foo/bar/jaz/tom  则：
                     *      ./foo1/bar1         [host]/foo/bar/jaz/foo1/bar1?format=json
                     *      ../../foo1/bar1     [host]/foo/foo1/bar1?format=json
                     */
                    api: (api, useformat=true) => {
                        if (api.includes('://')) {
                            let au = cgy.url(api);
                            if (useformat && !au.hasQuery('format')) au.setQuery({ format: 'json' });
                            return au.url();
                        }
                        let cu = cgy.url(),
                            host = cu.current().host,
                            pre = Vue.request.apiPrefix(),
                            au = host;
                        if (cgy.is.empty(api) || !cgy.is.string(api)) {
                            au += `/${pre}/api`;
                        } else if (api=='/') {
                            au += `/${pre}`;
                        } else if (api.startsWith('/')) {
                            api = api.trimAnyEnd('/');
                            au += `/${pre}${api}`;
                        } else if (api.startsWith('./')) {
                            api = api.trimAnyStart('.').trimAny('/');
                            cu.upPath(0, api);
                            if (useformat && !cu.hasQuery('format')) cu.setQuery({ format: 'json' });
                            return cu.url();
                        } else if (api.startsWith('../')) {
                            let arr = api.split('../'),
                                lvl = 0;
                            for (let i=0;i<arr.length;i++) {
                                if (arr[i]=='') {
                                    lvl += 1;
                                } else {
                                    break;
                                }
                            }
                            cu.upPath(lvl, arr.slice(-1)[0]);
                            if (useformat && !cu.hasQuery('format')) cu.setQuery({ format: 'json' });
                            return cu.url();
                        } else {
                            api = api.trimAny('/');
                            au += `/${pre}/api/${api}`;
                        }
                        au = cgy.url(au);
                        if (useformat && !au.hasQuery('format')) au.setQuery({ format: 'json' });
                        return au.url();
                    },

                    /**
                     * 处理 api 响应数据
                     * @param Object res    response data
                     * @return Object  or  null
                     */
                    response(res = {}) {
                        //console.log(res);
                        let rd = res.data,
                            is = cgy.is;
                        if (is.null(rd)) {
                            Vue.ui.setRequestStatus('error');
                            Vue.ui.error('没有返回任何内容', 'Error');
                            return null;
                        } else if (is.string(rd)) {
                            //console.log(rd);
                            if (rd.includes('error')) {
                                Vue.ui.setRequestStatus('error');
                                Vue.ui.error(rd, 'Error', {
                                    width: '960'
                                });
                                return null;
                            } else {
                                Vue.ui.setRequestStatus('error');
                                Vue.ui.error(rd, 'Error', {
                                    width: '960'
                                });
                                return null;
                            }
                        }
                        if (rd.error==true || is.undefined(rd.data) || is.null(rd.data) || (is.defined(rd.data.error) && rd.data.error==true)) {
                            if (is.undefined(rd.data) && !is.empty(rd)) {
                                Vue.ui.setRequestStatus('success');
                                return rd;
                            }
                            console.log(rd);
                            let code = (!is.empty(rd.data) && is.defined(rd.data.code)) ? rd.data.code : 'empty';
                            return Vue.request.handleResponseError(code, rd.data);
                        } else {
                            Vue.ui.setRequestStatus('success');
                            return rd.data;
                        }
                    },

                    //处理响应结果为错误信息的 request
                    handleResponseError(code, error) {
                        let opt = {};
                        //按 code 来处理
                        switch (code) {
                            case 'php':         //php 系统错误
                                
                                break;
                            case 'empty':       //没有返回值
                                error = {
                                    msg: '接口没有返回任何值',
                                    title: '接口返回空值',
                                    file: 'null',
                                    line: 0
                                };
                                break;
                            case 'jwterror':    //jwt token 验证失败
                                //弹出 cv-login 登录组件
                                Vue.usr.loginPanel().then(rtn=>{
                                    if (rtn==true) {
                                        return window.location.reload();
                                    }
                                });
                                break;

                            default: 
                                
                                error = {
                                    msg: '发生未知错误',
                                    title: '未知错误',
                                    file: 'null',
                                    line: 0
                                };
                                break;
                        }
                        //弹出错误提示
                        Vue.ui.error( error.msg, error.title, opt);
                        //throw error
                        let errmsg = `${error.title}：${error.msg} in File: ${error.file} at Line ${error.line}`;
                        throw new Error(errmsg);
                        return null;
                    },

                    //处理 axios 错误
                    handleRequestError(err) {
                        console.log(err);
                        let {code, name, message, request} = err;
                        //this.$setRequestStatus('error');
                        Vue.ui.error( `[${code}]<br>${message}<br>${request.responseURL}`, name);
                        return null;
                    },

                    //判断当前是否开启了 uac 权限控制
                    uacIsOn() {
                        let is = cgy.is;
                        return is.defined(Vue.usr) && is.defined(Vue.usr.uac) && Vue.usr.uac==true;
                    },

                    /**
                     * 当 判断用户未登录时，弹出 cv-login 登录组件
                     * 
                     */
                    /*loginPage: async function() {
                        if (Vue.request.uacIsOn()) {
                            let logcomp = await Vue.$invokeComp('cv-login', {
                                isPopupPanel: true
                            });
                            return logcomp;
                        }
                    },*/


                }
                if (cgy.is.defined(props[prop])) return props[prop];
                return undefined;
            }
        }
    ),
    //request 方法一次性简写
    async req(...args) {
        //同时调用 $request 和 $response
        let res = await Vue.request(...args);
        //console.log(res);
        if (res!=null) {
            return Vue.request.response(res);
        }
        return null;
    },

    /**
     * debug 状态
     */
    $debug: {
        isOn: ()=>Vue.debug,
        on: ()=>{
            Vue.debug=true;
            if (Vue.prototype.$log) {
                Vue.prototype.$log.on();
            }
        },
        off: ()=>{
            Vue.debug=false;
            if (Vue.prototype.$log) {
                Vue.prototype.$log.off();
            }
        }
    },



    /**
     * 动态加载组件
     */
    /**
     * dynamic component 动态加载组件
     * 异步加载
     * 全局方法：
     *      Vue.$invokeComp('comp-name', { propsData ... }).then(...)
     *      父组件为 Vue.$root 根组件
     * 在任意组件内：
     *      this.$invoke('comp-name', { propsData ... }).then(...)
     *      父组件为 当前组件
     * @param {String} compName 组件的注册名称，必须是全局注册的组件
     * @param {Object} propsData 组件实例化参数
     * @return {Vue|null} 组件实例
     */
    async $invokeComp(compName, propsData = {}) {
        let is = cgy.is,
            pcomp = is.empty(this.$el) ? Vue.$root : this;   //动态加载的组件实例的父组件为当前组件

        /**
         * 处理 compName 自动补齐组件名称前缀
         * !! 基础组件库组件必须以 base- 开头，业务组件库必须以 [组件库名]- 开头
         * !! 例如：base-button  pms-table
         */
        compName = Vue.vcn(compName);

        if (!is.string(compName) || compName==='') return null;
        let comp = Vue.component(compName);
        if (is.undefined(comp)) return null;
        if (comp.toString().includes('import')) {   //异步组件是懒加载的，此时 组件 compName 还未加载
            comp = await comp();
            if (is.undefined(comp.default)) return null;
            comp = comp.default;
        }
        //console.log(comp);
        //实例化组件 compName
        let ins = new comp({propsData}).$mount(),
            pel = null;
        //挂载到父组件（当前组件）的 dom 上
        //console.log(this, pcomp);
        if (is.empty(pcomp) || is.empty(pcomp.$el)) {
            pel = document.querySelector('body');
        } else {
            pel = pcomp.$el;
        }
        pel.appendChild(ins.$el);

        //动态创建的组件实例 push to Vue.dynamicComponentsInstance[] 数组，然后返回
        return Vue.$invokePushToDci(ins);
    },
    /**
     * 向 Vue.dynamicComponentsInstance[] 数组动态增加组件实例
     * @param {Vue} ins 通过 $invokeComp 动态创建的组件实例
     * @return {Vue} 附加了 _destroyDynamicComponentInstance 方法后的组件实例
     * 此实例已被添加到 Vue.dynamicComponentsInstance[] 数组
     */
    $invokePushToDci(ins) {
        let is = Vue.cgy.is;
        if (!is.vue(ins) || is.function(ins._destroyDynamicComponentInstance)) {
            return ins;
        }
        //Vue.dynamiccomponentsInstance[] 数组建立
        if (!is.array(Vue.dynamiccomponentsInstance)) {
            Vue.dynamiccomponentsInstance = [];
        }
        /**
         * 先清理 Vue.dynamicComponentsInstance[] 数组末尾的 undefined 元素
         * [ins, undefined, ins, undefined] --> [ins, undefined, ins]
         */
        let dci = Vue.dynamicComponentsInstance,
            idx = -1;
        if (dci.length>0) {
            for (let i=dci.length-1;i>=0;i--) {
                if (is.undefined(dci[i])) {
                    idx = i;
                    continue;
                } else {
                    break;
                }
            }
            if (idx>=0) dci.splice(idx);
        }
        //将此动态组件实例挂到 Vue.dynamicComponentsInstance 数组，增加相应属性方法
        let dciln = dci.length;
        ins._dcid = dciln;
        ins._destroyDynamicComponentInstance = (function() {
            return Vue.$destroyInvoke(this);
        }).bind(ins);
        dci.push(ins);
        //返回 ins
        return ins;
    },
    /**
     * 销毁 动态加载的 组件实例
     * @param {Vue|Integer} ins 组件实例 或 组件实例在 Vue.dynamicComponentsInstance[] 中的 idx
     * @return {Boolean}
     */
    $destroyInvoke(ins=null) {
        let is = cgy.is,
            dci = Vue.dynamicComponentsInstance || [],
            dcid = -1,
            comp = null;
        //console.log(dci, ins);
        if (is.vue(ins) && is.defined(ins._dcid)) {
            dcid = ins._dcid;
            comp = ins;
        } else if (is.number(ins) && is.defined(dci[ins]) && is.vue(dci[ins])) {
            dcid = ins;
            comp = dci[dcid];
        }
        //console.log(comp, dcid, comp.$destroy);
        if (is.vue(comp) && dcid>=0) {
            //通用 销毁方法
            let dciRemove = () => {
                if (is.elm(comp.$el)) comp.$el.remove();
                Vue.dynamicComponentsInstance[dcid] = undefined;
                return true;
            };
            //尝试执行组件自定义的 $destroy 方法
            if (is(ins.$destroy, 'function,asyncfunction')) {
                if (is.function(ins.$destroy)) {
                    ins.$destroy();
                    return dciRemove();
                } else {
                    return ins.$destroy().then(() => dciRemove());
                }
            } else {
                return dciRemove();
            }
        }

        return false;
    },



    /**
     * plugin 相关
     */
    allPluginLoaded: false,
    async whenAllPluginLoaded() {
        
    },



}