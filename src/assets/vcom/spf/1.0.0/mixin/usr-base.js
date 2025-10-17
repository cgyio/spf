/**
 * cv-**** 组件 usr 用户工具
 */

export default {
    props: {
        
    },
    data() {
        return {

            /**
             * 可外部定义的 usr 参数
             */

            //是否开启 uac 控制
            uac: false,
            //用户操作后端接口
            apiPrefix: 'usr',
            //本地 token 缓存的 key
            lokey: 'cv_token',
            //关联的微信公众号，在 wx.cgy.design 中定义
            wxaccount: 'qyspkj',


            //用户登录信息
            info: {},
            raw: {},
            roles: [],
            auths: [],
            //全局 uid
            uid: '',
            uidx: -1,
            //与微信账号关联
            openid: '',
            //login 标记
            isLogin: false,

            //用户有权限的 navs 导航菜单
            navs: [
                /*{
                    id: '100',
                    icon: 'vant-flag',
                    title: '起步',
                    children: [
                        {id: 101, title: '关于 cVue 框架', url: 'doc/doc-about', icon: 'vant-shortcut-fill', lock: true},
                        {id: 102, title: '安装与部署', url: 'doc/doc-install'},
                        {id: 103, title: '快速上手', url: 'doc/doc-start'},
                    ]
                },
                ...
                */
            ],

            //初始化完成标记
            inited: false,
        }
    },
    computed: {
        //判断用户信息是否准备完成
        infoReady() {
            let is = cgy.is,
                isd = is.defined,
                ism = is.empty;
            return this.isLogin && !ism(this.info) && !ism(this.raw) && !ism(this.roles) && !ism(this.auths);
        },

        //用户权限相关计算属性
        isSuperRole() {return this.isSuper();},
        isNormalRole() {return this.isRole('normal');},
        isDbRole() {return this.isRole('db');},
        isQueryRole() {return this.isRole('query');},
        isFinancialRole() {return this.isRole('financial');},
        isStockerRole() {return this.isRole('stocker');},
        isPurchaseRole() {return this.isRole('purchase');},
        isQcRole() {return this.isRole('qc');},
        isRndRole() {return this.isRole('rnd');},
        isProductRole() {return this.isRole('product');},
    },
    methods: {

        /**
         * usr 初始化
         */
        async init(options) {
            if (cgy.is.defined(options.usr)) {
                let uopt = cgy.extend({}, options.usr);
                Reflect.deleteProperty(options, 'usr');
                //加载外部定义的 usr 参数
                let ks = 'uac,apiPrefix,lokey,wxaccount'.split(','),
                    isd = cgy.is.defined;
                for (let i of ks) {
                    if (isd(uopt[i]) && isd(this[i])) {
                        if (!(cgy.is.string(uopt[i]) && uopt[i]=='')) {
                            //string 类型的预设值，不能为空
                            this[i] = uopt[i];
                        }
                    }
                }
                //apiPrefix 不能为空
                //if (this.apiPrefix=='') this.apiPrefix = 'uac';
            }
            
            if (this.uac) {
                //如果开启了 uac 权限控制，则自动检查登录状态，自动获取 nav 导航列表
                await this.getUsrInfo();
                if (this.isLogin) {
                    await this.getUsrNavs();
                }
            }

            this.inited = true;
        },



        /**
         * request usr api
         */

        /**
         * 处理 usr api
         */
        api(api='') {
            api = api.trimAny('/');
            let pre = this.apiPrefix,
                u = [];
            if (pre!='') u.push(pre);
            if (api!='') u.push(api);
            return Vue.request.api(`/${u.join('/')}`);
        },
    
        /**
         * usr request
         */
        async request(api='', ...args) {
            api = this.api(api);
            //console.log(api);
            let rtn = await Vue.req(api, ...args);
            return rtn;
        },

        /**
         * request usr info
         */
        async getUsrInfo() {
            let usrinfo = await this.request();
            console.log(usrinfo);
            if (cgy.is.empty(usrinfo) || (cgy.is.defined(usrinfo.isLogin) && usrinfo.isLogin==false)) {
                //用户还未登录，弹出登录窗口
                //await this.popupLoginPanel();
                //this.$request.loginPage();
                Vue.ui.error('还未登录 foooobarrrr');
            } else {
                if (cgy.is.empty(usrinfo)) {
                    //用户信息为空
                    //window.location.href = this.api('loginPage').replace('?format=json','');
                    Vue.ui.error('还未登录');
                } else {
                    let uinfo = cgy.is.defined(usrinfo.info) ? usrinfo.info : usrinfo.data;
                    this.info = Object.assign({}, uinfo);
                    this.raw = Object.assign({}, usrinfo.raw);
                    if (!cgy.is.empty(usrinfo.roles)) {
                        this.roles.splice(0);
                        this.roles.push(...usrinfo.roles);
                    }
                    if (!cgy.is.empty(usrinfo.auths)) {
                        this.auths.splice(0);
                        this.auths.push(...usrinfo.auths);
                    }
                    this.uid = uinfo.uid;
                    this.uidx = uinfo.id;
                    this.openid = uinfo.openid;
                    this.isLogin = true;
                }
            }
        },
        //get usr navs
        async getUsrNavs() {
            let navs = await this.request('nav');
            console.log(navs);
            if (cgy.is.empty(navs)) {
                return Vue.ui.error(
                    '此用户未找到拥有权限的导航页面列表',
                    '未找到导航列表'
                );
            }
            let tree = cgy.is.defined(navs.navtree) ? navs.navtree : [];
            if (tree.length>0) {
                this.navs.splice(0);
                this.navs.push(...tree);
            }
            /*

            for (let i=0;i<navs.operations.length;i++) {
                let navi = cgy.extend({},navs.operations[i]);
                if (!cgy.is.defined(navi.sort)) continue;
                let narr = navi.name.split('-').slice(1),
                    name = narr.join('-'),
                    url = `/nav/${narr.join('/')}`,
                    nsi = cgy.extend({}, navi, {
                        id: navi.sort,
                        comp: {
                            name,
                            url: Vue.request.api(url, true)
                        },
                        lock: cgy.is.boolean(navi.lock) && navi.lock===true,
                        narr
                    });
                if (ns.length<=0 || ns[ns.length-1].narr.length>=narr.length) {
                    ns.push(nsi);
                } else {
                    let nsci = ns[ns.length-1];
                    if (!cgy.is.defined(nsci.children)) nsci.children = [];
                    nsci.children.push(nsi);
                }
            }
            console.log(ns);
            if (ns.length>0) {
                this.navs.splice(0);
                this.navs.push(...ns);
            }*/
        },
        //清空用户登录信息，用于在操作过程中，token过期，需要再次登录的情况
        async clearUsrInfo() {
            this.info = Object.assign({});
            this.raw = Object.assign({});
            this.roles.splice(0);
            this.auths.splice(0);
            this.uid = '';
            this.uidx = -1;
            this.openid = '';
            this.isLogin = false;
        },
    
        /**
         * 用户登录
         * @param Object data 要 post 到 api 的登录数据
         * @param String api 如果不使用默认 api
         * @return void 刷新页面
         */
        async login(data, {type='scan', redirectTo='', reload=true}) {
            let is = cgy.is,
                api = this.api('login/'+type),
                isScan = is.defined(data.openid),   //微信扫码登录
                isPwd = is.defined(data.pwd);       //账号密码登录
            if (isScan) {  
                let openid = data.openid;
                Reflect.deleteProperty(data, 'openid');
                data = cgy.extend(data, {
                    where: {
                        openid
                    }
                });
            }
            //登录
            console.log(api, data);
            let rtn = await Vue.req(api, data, {}, false);  //不提交 本地 token
            //检查登录结果
            console.log(rtn);
            if (is.defined(rtn.isLogin) && rtn.isLogin==true) {
                //登录成功
                //保存后端下发的 jwt token 写入 localStorage
                let setlo = await this.setTokenToLocal(rtn);
                if (setlo===true) {
                    //token 保存成功
                    if (reload) {
                        //跳转到 redirectTo 页面 或 刷新页面
                        if (redirectTo!='') {
                            window.location.href = redirectTo;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        //不刷新或跳转，则需要再次加载 usr 用户登录信息
                        await this.getUsrInfo();
                    }
                    return true;
                } else {
                    //token 保存到本地失败

                    return false;
                }
            } else {
                //登录失败
                let msg = is.defined(rtn.msg) ? rtn.msg : null;
                if (is.null(msg)) {
                    if (isScan) {
                        msg = '无法登录系统，请检查输入的账号密码，或者确认扫码微信号是否拥有登录系统的权限';
                    } else if (isPwd) {
                        msg = '未知错误，无法登录';
                    } else {
                        msg = '登录失败，请刷新页面并重新尝试';
                    }
                }
                //显示错误提示
                Vue.ui.error(
                    msg,
                    '登录失败',
                    {
                        confirm: () => {
                            if (isScan) {
                                //扫码登录失败时，直接刷新页面
                                window.location.reload();
                            }
                            //密码登录失败时，不做操作，等待调用此方法的组件，根据返回值，自行处理
                        }
                    }
                );
                //返回值
                return rtn;
            }
        },
        /**
         * 用户登出
         * @param Boolean silence 是否不弹出提示框，默认 false
         * @param Boolean reload 是否在登出成功后刷新页面
         * @return void
         */
        logout(silence=false, reload=true) {
            if (silence) {
                return this.doLogout(silence, reload);
            } else {
                Vue.ui.confirm(
                    '点击「确定」按钮将退出你的账号，请先保存数据！<br>点击「取消」按钮或直接关闭对话框可以取消此操作<br>确定要退出吗？',
                    '确定要退出账号吗',
                    {
                        confirm: () => {
                            return this.doLogout(silence, reload);
                        }
                    }
                );
            }
        },
        //调用登出 api
        async doLogout(silence=false, reload=true) {
            let is = cgy.is,
                api = this.api('logout'),
                rtn = await Vue.req(api,{},{},false);
            if (is.defined(rtn.isLogout) && rtn.isLogout==true) {
                //登出成功
                //清空 usr info
                this.clearUsrInfo();
                //清空 本地缓存的 token
                this.clearTokenFromLocal();
                
                if (silence) {
                    //不弹出提示
                    if (reload) {
                        //要求刷新
                        window.location.reload();
                    }
                } else {
                    let msg = '已退出登录';
                    if (reload) {
                        msg += '，点击「确定」按钮将刷新页面';
                    } else {
                        msg += '，如果继续操作，可能需要你再次登录';
                    }
                    Vue.ui.success(
                        msg,
                        '你已退出登录',
                        {
                            confirm: () => {
                                if (reload) {
                                    //要求刷新
                                    window.location.reload();
                                }
                            }
                        }
                    );
                }
            }
        },
        /**
         * 登录成功后，保存从登录接口下发的 jwt token 到 localStorage
         */
        async setTokenToLocal(rtn) {
            let is = cgy.is,
                lokey = this.lokey;
            if (is.defined(rtn.isLogin) && rtn.isLogin==true) {
                //登录成功
                //保存后端下发的 jwt token 写入 localStorage
                if (is.defined(rtn.token)) {
                    window.localStorage.setItem(lokey, rtn.token);
                }
                await cgy.wait(50);
                return true;
            } else {
                return false;
            }
        },
        //读取 localStroage 中保存的 jwt-token
        getTokenFromLocal() {
            let is = cgy.is,
                lokey = this.lokey,
                token = window.localStorage.getItem(lokey);
            if (is.string(token) && token!='') return token;
            return null;
        },
        //清除 localStorage 中保存的 jet-token
        clearTokenFromLocal() {
            window.localStorage.removeItem(this.lokey);
        },
        /**
        * 当判断出用户未登录时，弹出用户登录界面
        * 通常用于 在操作过程中，后端返回用户未登录状态时，作出响应
        */
       async loginPanel() {
            //因为已经判断用户未登录，因此 先清空用户信息
            //清空 usr info
            this.clearUsrInfo();
            //清空 本地缓存的 token
            this.clearTokenFromLocal();
            //弹出 cv-login
            let logcomp = await Vue.$invokeComp('cv-login', {
                isPopupPanel: true,
                api: this.apiPrefix,
                wxaccount: this.wxaccount,
            });
            //轮询 usr.isLogin 直到登录成功 或 超时 1min
            await cgy.until(()=>{
                return this.isLogin==true;
            }, 60000);
            //登录成功后，关闭登录窗口
            logcomp.popShow = false;
            await cgy.wait(50);
            Vue.$destroyInvoke(logcomp);
            //返回 usr.isLogin
            await cgy.wait(50);
            return this.isLogin;
       },



        /**
         * 用户权限控制
         */

        /**
         * 用户权鉴
         * @param String ...oprs 需要鉴别权限的操作名称，第一个参数为 boolean 时，作为 or 参数
         * @return Promise
         */
        async auth(...oprs) {
            let api = this.api('grant'),
                ud = {},
                is = cgy.is;
            if (oprs.length<=0) return Promise.resolve(false);
            if (oprs.length==1) {
                if (is.plainObject(oprs[0])) {
                    ud = oprs[0];
                } else if (is.string(oprs[0])) {
                    ud.opr = oprs[0];
                }
            } else {
                if (is.boolean(oprs[0])) {
                    ud.or = oprs.shift();
                }
                ud.oprs = oprs;
            }
            
            let res = await Vue.req(api, ud),
                grant = false,
                opr = 'undefined';
            //let res = await this.$req(`uac/grant/${operation}`);
            //let is = this.$is,
            //    grant = false;
            if (is.defined(res.grant) || is.boolean(res.grant)) {
                grant = res.grant;
            }
            if (is.defined(res.operation)) {
                opr = res.operation;
            }
            if (grant!=true) {
                Vue.ui.error(
                    `当前用户没有此项操作权限 [ OPR = ${opr} ]<br>如有疑问请与管理员联系`,
                    '无操作权限'
                );
                return false;
            }
            return true;
        },
        /**
         * 判断用户角色
         * @param String ...roles 需要判断的用户角色，第一个参数为 boolean 时，作为 or 参数
         * @return Promise
         */
        async role(...roles) {
            let api = this.api('role'),
                ud = {},
                is = cgy.is;
            if (roles.length<=0) return Promise.resolve(false);
            if (roles.length==1) {
                if (is.plainObject(roles[0])) {
                    ud = roles[0];
                } else if (is.string(roles[0])) {
                    ud.roles = roles;
                }
            } else {
                if (is.boolean(roles[0])) {
                    ud.or = roles.shift();
                }
                ud.roles = roles;
            }
            
            let res = await Vue.req(api, ud),
                has = false,
                ros = 'undefined';
            if (is.defined(res.has) || is.boolean(res.has)) {
                has = res.has;
            }
            if (is.defined(res.roles)) {
                ros = res.roles;
            }
            if (has!=true) {
                Vue.ui.error(
                    `当前用户不属于这些用户角色 [ ROLEs = ${ros} ]<br>如有疑问请与管理员联系`,
                    '无操作权限'
                );
                return false;
            }
            return true;
        },
        /**
         * usr 权限详细计算
         */
        //检查用户是否属于某个角色
        isRole(role) {
            return this.hasAuth(`sys-role-${role}`);
        },
        isRoleAll(...roles) {
            let auths = roles.map(i=>`sys-role-${i}`);
            return this.hasAuthAll(...auths);
        },
        isRoleAny(...roles) {
            let auths = roles.map(i=>`sys-role-${i}`);
            return this.hasAuthAny(...auths);
        },
        //检查是否拥有操作权限
        hasAuth(opr) {
            if (!this.infoReady) return false;
            return this.isSuper() || this.auths.includes(opr);
        },
        hasAuthAll(...oprs) {
            if (!this.infoReady) return false;
            if (this.isSuper()) return true;
            let auths = this.auths,
                diff = oprs.minus(auths);
            return diff.length<=0;
        },
        hasAuthAny(...oprs) {
            if (!this.infoReady) return false;
            if (this.isSuper()) return true;
            let auths = this.auths,
                diff = oprs.inter(auths);
            return diff.length>0;
        },
        //是否Super用户
        isSuper() {
            if (!this.infoReady) return false;
            return this.auths.includes('sys-super');
        },

        //用户针对某个 table 的权限检查
        hasTableAuth(table, opr) {
            opr = `db-${table.split('/').slice(-2).join('-')}-${opr}`;
            return this.hasAuth(opr);
        },

    }
}