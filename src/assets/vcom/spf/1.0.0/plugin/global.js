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