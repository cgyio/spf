/**
 * Vue2.* 插件 base
 * 
 * 全局方法
 * 以 Vue.***() 形式调用的方法
 * 
 */

export default {

    cvVersion() {
        console.log('CGY-VUE version = 1.0');
    },

    /**
     * 执行 base plugin initSequence 中的所有方法
     * 在 启动序列 initSequence 中的所有方法，都必须是 async 方法
     */
    async initBasePlugin() {
        let options = Vue.baseOptions,
            seq = Vue.baseInitSequence;
        for (let i=0;i<seq.length;i++) {
            await seq[i](options);
        }
        await cgy.wait(100);
        return true;
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
     * 扩展 vue 根组件实例创建方法 app = new Vue(...)
     * 将生成的根组件实例挂载到 Vue.$root
     * @param Object $opt 根组件参数
     * @return Vue instance
     */
    rootApp(opt = {}) {
        //必须在 base plugin init 之后实例化 $root
        //因为 nav 页面组件必须在 $root 实例化之前注册
        Vue.initBasePlugin().then(inited => {
            //console.log(inited);
            if (!inited) {
                throw new Error('插件 Base 未能正确初始化');
            } else {
                let app = new Vue(opt);
                if (app instanceof Vue) {
                    Vue.$root = app;
                    window.app = app;
                    window.cvRoot = app;
                    //Vue.nav.start()
                    app.$elReady().then(()=>{
                        Vue.nav.start();
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
     * dynamic component 动态加载组件
     * 异步加载
     * 全局方法：
     *      Vue.$invokeComp('comp-name', { propsData ... }).then(...)
     *      父组件为 Vue.$root 根组件
     * 在任意组件内：
     *      this.$invoke('comp-name', { propsData ... }).then(...)
     *      父组件为 当前组件
     * @param String compName 组件的注册名称，必须是全局注册的组件
     * @param Object propsData 组件实例化参数
     * @return Vue Component instance 组件实例
     */
    async $invokeComp(compName, propsData = {}) {
        let is = cgy.is,
            pcomp = is.empty(this.$el) ? Vue.$root : this;   //动态加载的组件实例的父组件为当前组件
            //pcomp = this==Vue ? Vue.$root : this;   //动态加载的组件实例的父组件为当前组件
        let comp = Vue.component(compName);
        if (is.undefined(comp)) return false;
        if (comp.toString().includes('import')) {   //异步组件是懒加载的，此时 组件 compName 还未加载
            comp = await comp();
            if (is.undefined(comp.default)) return false;
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
        //先清理 Vue.dynamicComponentsInstance
        let dci = Vue.dynamicComponentsInstance,
            idx = -1;
        for (let i=dci.length-1;i>=0;i--) {
            if (cgy.is.undefined(dci[i])) {
                idx = i;
                continue;
            } else {
                break;
            }
        }
        if (idx>=0) {
            dci.splice(idx);
        }
        //将此动态组件实例挂到 Vue.dynamicComponentsInstance 数组，增加相应属性方法
        let dciln = dci.length;
        ins._dcid = dciln;
        ins._destroyDynamicComponentInstance = function() {
            ins.$destroy();
            dci[dciln] = undefined;
        }
        dci.push(ins);
        //返回 ins
        return ins;
    },
    /**
     * 销毁 动态加载的 组件实例
     */
    $destroyInvoke(compIns=null) {
        let is = cgy.is,
            dci = Vue.dynamicComponentsInstance,
            dcid = -1,
            comp = null;
        //console.log(dci, compIns);
        if (compIns instanceof Vue && is.defined(compIns._dcid)) {
            dcid = compIns._dcid;
            comp = compIns;
        } else if (is.number(compIns) && is.defined(dci[compIns]) && dci[compIns] instanceof Vue) {
            dcid = compIns;
            comp = dci[dcid];
        }
        //console.log(comp, dcid, comp.$destroy);
        if (comp instanceof Vue && is.function(comp.$destroy)) {
            if (is.elm(comp.$el)) comp.$el.remove();
            comp.$destroy();
            Vue.dynamicComponentsInstance[dcid] = undefined;
        }
    },



    /**
     * plugin 相关
     */
    allPluginLoaded: false,
    async whenAllPluginLoaded() {
        
    },



}