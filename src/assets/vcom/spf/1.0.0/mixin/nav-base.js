/**
 * cv-**** nav 导航组件
 */

export default {
    props: {},
    data() {
        return {
            //导航菜单列表，关联到 usr.navs
            nav: [],

            //已开启 spa 页面
            tabs: [

            ],

            //当前 active
            active: {
                nav: '',
                tab: -1,
                comp: ''
            },

            //navmenu 组件实例，一个 spa 只能有一个 navmenu
            menuComp: null,
            //navtab 组件实例，一个 spa 只能有一个 navtab
            tabComp: null,

            //初始化完成标记
            inited: false,
        }
    },
    computed: {

    },
    methods: {
        /**
         * 导航菜单初始化，应在根实例创建之前实例化完成
         */
        async init(options) {
            if (cgy.is.defined(options.nav)) {
                let nopt = cgy.extend({},options.nav);
                Reflect.deleteProperty(options, 'nav');
                let ks = Object.keys(nopt),
                    isd = cgy.is.defined;
                for (let i of ks) {
                    if (isd(this[i])) {
                        this[i] = nopt[i];
                    }
                }
            }

            //从 usr 关联 navs
            this.nav = Object.assign(this.nav, Vue.usr.navs);
            await cgy.wait(10);
            //将 nav 列表中的所有组件进行全局注册
            await this.registCompFromNav();
            await cgy.wait(10);

            //监听 nav-item-change 事件
            Vue.evtBus.$whenAll(this, {
                'nav-item-ready': navcomp => {
                    return this.whenNavItemReady(navcomp);
                }
            });

            this.inited = true;
        },

        /**
         * 入口方法，等待根组件加载完成后，执行
         * 此方法只执行一次
         * 
         * 开始加载 nav 中的页面
         * 
         * 首先 标记为 lock 的 nav 必须加载
         * 其次 加载 active.nav 
         * 如果 active.nav=='' 且 没有页面标记为 lock 则默认加载第一个 nav
         */
        start() {
            //如果没有导航列表，则不做操作
            if (cgy.is.empty(this.nav)) return;
            //如果存在导航列表，则开始加载第一个页面
            cgy.until(()=>{
                //只有所有必须的组件都已 mounted 完成
                return this.menuComp!=null && this.tabComp!=null;
            }).then(()=>{
                let act = this.active.nav,
                    lcs = this.getLockNavs();
                if (act!='') lcs.push(act);
                if (lcs.length<=0) {
                    let fn = this.getFirstNav();
                    if (fn!='') {
                        lcs.push(fn);
                    }
                }
                if (lcs.length>0) {
                    this.navTo(lcs[0]);
                    if (lcs.length>1) {
                        this.tabs.splice(0);
                        for (let i=1;i<lcs.length;i++) {
                            this.pushNavItemToTabs(lcs[i]);
                        }
                    }
                }
            });
        },

        /**
         * 加载某个 nav 页面
         */
        async loadNav() {
            //this.loadingOn();
            Vue.ui.setRequestStatus('waiting');
            let navitem = this.getNavItemById(this.active.nav),
                navid = navitem.id,
                url = navitem.url,
                compinfo = {};
            if (cgy.is.undefined(navitem.comp)) {
                compinfo = await import(this.api.compinfo+url);
                if (cgy.is.undefined(compinfo.default)) {
                    return Vue.ui.error(`无法加载目标页面 [ navid= ${navid} ]`, '加载页面失败');
                } else{
                    compinfo = compinfo.default;
                    //保存到 nav 数组
                    navitem.comp = compinfo;
                    //console.log(Vue.options.components);
                    //注册页面组件
                    /*Vue.component(
                        navitem.comp.name,
                        ()=>import(navitem.comp.url)
                    );*/
                    Vue.options.components[navitem.comp.name] = ()=>import(navitem.comp.url);
                    await cgy.wait(100);
                    console.log(Vue.options.components);
                }
            } else {
                compinfo = navitem.comp;
            }

            //设置 layout.menu.currentComp
            this.setActiveComp(compinfo.name);
            //this.loadingOff();
            //Vue.ui.setRequestStatus('success');
            //等待被加载的 nav 页面组件触发 nav-item-ready 事件
        },

        /**
         * 重新进入 某个 tab
         */
        async loadTab(tabidx) {
            let tabitem = this.tabs[tabidx];
            if (cgy.is.empty(tabitem)) return Vue.ui.error(`无法加载标签页面 [ tabidx= ${tabidx} ]`, '加载页面失败');
            if (cgy.is.undefined(tabitem.comp)) {
                return await this.loadNav();
            } else {
                //设置 layout.menu.currentComp
                this.setActiveComp(tabitem.comp.name);
            }
        },

        /**
         * 操作 nav
         */
        //导航跳转页面
        navTo(navid) {
            if (navid==this.active.nav) return;
            //设置 active.nav
            this.setActiveNav(navid);
            //检查此 nav 是否已加载，在 tabs 列表中
            let tabitem = this.getTabItemById(navid),
                tabidx = -1;
            if (tabitem!=null) {
                tabidx = tabitem._tabidx;
                this.tabTo(tabidx);
            } else {
                tabidx = this.pushNavItemToTabs(navid);
                this.setActiveTab(tabidx);
                //首次加载 nav
                this.loadNav();
            }
        },
        //按 id 获取 nav 信息
        getNavItemById(navid, nav=null) {
            let item = null;
            nav = nav==null ? this.nav : nav;
            for (let i=0;i<nav.length;i++) {
                let navi = nav[i];
                if (navi.id==navid) {
                    item = navi;
                    break;
                }
                if (!cgy.is.empty(navi.children)) {
                    let itemi = this.getNavItemById(navid, navi.children);
                    if (itemi!=null) {
                        item = itemi
                        break;
                    }
                }
            }
            return item;
        },
        //设置 active.nav 选择某个导航菜单
        setActiveNav(navid) {
            if (navid==this.active.nav) return;
            Vue.set(this.active, 'nav', navid);
            //触发 nav-active-comp-change
            //Vue.evtBus.$trigger('nav-active-nav-change', this, navid);
        },
        getActiveNavItem() {
            let navid = this.active.nav;
            return this.getNavItemById(navid);
        },
        //检查 navid 是否在 tabs 中
        navInTabs(navid) {
            return this.getTabItemById(navid) !== null;
        },
        //获取某个 nav 的上级 nav 路径
        getNavXPath(navid, nav=null) {
            nav = nav==null ? this.nav : nav;
            let np = [];
            for (let i=0;i<nav.length;i++) {
                let navi = nav[i];
                if (cgy.is.empty(navi.children) && navi.id!=navid) continue;
                if (navi.id==navid) {
                    np.push(navi);
                    break;
                } else {
                    let snp = this.getNavXPath(navid, navi.children);
                    if (snp.length>0) {
                        np.push(navi);
                        np.push(...snp);
                        break;
                    }
                }
            }
            return np;
        },
        //设置 active.comp
        setActiveComp(compn='') {
            //nav / tab 变化最终转换为 active.comp 变化
            Vue.set(this.active,'comp', compn);
            //触发 nav-active-comp-change
            //console.log('触发 nav-active-change 事件');
            Vue.evtBus.$trigger('nav-active-change', this, this.getActiveNavItem());
        },
        //将 nav 列表中的所有组件进行全局注册
        async registCompFromNav(nav=null) {
            nav = nav==null ? this.nav : nav;
            for (let i=0;i<nav.length;i++) {
                let navi = nav[i];
                if (cgy.is.empty(navi.comp) && cgy.is.empty(navi.url) && cgy.is.empty(navi.children)) continue;
                if (cgy.is.defined(navi.comp)) {
                    //console.log('regist comp', navi.comp);
                    navi.comp.url = Vue.request.api(navi.comp.url, false);
                    //全局注册
                    Vue.component(
                        navi.comp.name,
                        ()=>import(navi.comp.url)
                    );
                }
                if (cgy.is.array(navi.children) && navi.children.length>0) {
                    await this.registCompFromNav(navi.children);
                }
            }
        },
        //获取 nav 列表中的所有组件，输出为局部注册的形式
        async registCompFromNavLocalized(nav=null) {
            nav = nav==null ? this.nav : nav;
            let comps = {};
            for (let i=0;i<nav.length;i++) {
                let navi = nav[i];
                if (cgy.is.empty(navi.comp) && cgy.is.empty(navi.url) && cgy.is.empty(navi.children)) continue;
                if (cgy.is.defined(navi.comp)) {
                    comps[navi.comp.name] = ()=>import(navi.comp.url);
                } else {
                    if (cgy.is.array(navi.children) && navi.children.length>0) {
                        let compi = await this.registCompFromNavLocalized(navi.children);
                        if (!cgy.is.empty(compi)) {
                            comps = cgy.extend(comps, compi);
                        }
                    }
                    /*if (!cgy.is.empty(navi.url)) {
                        let compinfo = await import(this.api.compinfo+navi.url);
                        if (cgy.is.defined(compinfo.default)) {
                            compinfo = compinfo.default;
                            navi.comp = compinfo;
                            //局部注册
                            comps[compinfo.name] = ()=>import(compinfo.url);
                        }
                    }*/
                }
            }
            return comps;
        },
        //响应 nav-item-ready 事件
        whenNavItemReady(navcomp) {
            let navitem = this.getActiveTabItem();
            //console.log(navitem);
            //取消 request waiting status
            Vue.ui.setRequestStatus('success');
        },


        /**
         * 操作 tabs
         */
        tabTo(tabidx) {
            if (tabidx==this.active.tab) return;
            //设置 active.tab
            this.setActiveTab(tabidx);
            //设置 active.nav
            let tabitem = this.tabs[tabidx],
                navid = tabitem.id;
            if (navid!=this.active.nav) {
                this.setActiveNav(navid);
            }
            //重新进入 tab
            this.loadTab(tabidx);
        },
        //在 tabs 中按 navid 查找 tab
        getTabItemById(navid) {
            let tabs = this.tabs;
            if (tabs.length<=0) return null;
            for (let tabi of tabs) {
                if (tabi.id==navid) {
                    return tabi;
                    break;
                }
            }
            return null;
        },
        //设置 active.tab 激活某个 tab 页面
        setActiveTab(tabidx) {
            if (tabidx==this.active.tab) return;
            Vue.set(this.active, 'tab', tabidx);
            //触发 nav-active-comp-change
            //Vue.evtBus.$trigger('nav-active-tab-change', this, tabidx);
            //回调
            //if (this.navtab!=null && cgy.is(this.navtab.whenTabActive,'function,asyncfunction')) {
            //    this.navtab.whenTabActive.call(this.navtab, tabidx);
            //}
        },
        getActiveTabItem() {
            let tabidx = this.active.tab;
            if (tabidx>=0) return this.tabs[tabidx];
            return null;
        },
        //将某个 nav 添加到 tabs 列表
        pushNavItemToTabs(navid) {
            let navitem = this.getNavItemById(navid),
                tabitem = this.getTabItemById(navid);
            if (tabitem!=null) return;
            navitem._tabidx = this.tabs.length;
            if (!cgy.is.defined(navitem.lock)) navitem.lock = flase;
            this.tabs.push(navitem);
            return navitem._tabidx;
        }, 
        //将某个 navid 从 tabs 中移除
        removeNavItemFromTabs(navid) {
            let tabitem = this.getTabItemById(navid);
            if (tabitem==null) return;
            return this.removeItemFromTabs(tabitem);
        },
        //将某个 tabidx 从 tabs 中移除
        removeTabItemFromTabs(tabidx) {
            let tabitem = this.tabs[tabidx];
            if (cgy.is.empty(tabitem)) return;
            return this.removeItemFromTabs(tabitem);
        },
        //将除当前激活的 tab 之外其他所有 tab 从 tabs 中移除
        removeAllTabItemFromTabs() {
            if (this.tabs.length<=0) return;
            let items = [],
                actidx = -1;
            for (let i=0;i<this.tabs.length;i++) {
                let tbi = this.tabs[i];
                if (tbi.lock && tbi.lock==true) {
                    tbi._tabidx = items.length;
                    items.push(tbi);
                } else if (i==this.active.tab) {
                    tbi._tabidx = items.length;
                    actidx = items.length;
                    items.push(tbi);
                }
            }
            this.tabs.splice(0);
            this.tabs.push(...items);
            if (actidx>=0 && actidx!=this.active.tab) {
                this.setActiveTab(actidx);
            }
        },
        //将某个 tabitem 从 tabs 中移除
        removeItemFromTabs(tabitem) {
            let tabidx = tabitem._tabidx;
            if (!cgy.is.number(tabidx) || tabidx<0) return;
            //先将要删除的 tabitem 的 _tabidx 属性值设为 -1
            tabitem._tabidx = -1;
            //从 tabs 数组删除 tabitem
            this.tabs.splice(tabidx, 1);
            //重置 tabs 中所有 tabitem 的 _tabidx 属性
            //使用 响应式方法 修改 value
            for (let i=0;i<this.tabs.length;i++) {
                Vue.set(this.tabs[i], '_tabidx', i);
            }
            //修改 active.tab
            if (tabidx==this.active.tab) {
                tabidx -= 1;
                tabidx = tabidx<0 ? 0 : tabidx;
                this.tabTo(tabidx);
            } else {
                if (this.active.tab > tabidx) {
                    this.setActiveTab(this.active.tab - 1);
                }
            }
        },
        

        /**
         * tool
         */
        //获取所有标记为 lock 的 nav 页面
        getLockNavs(nav=null) {
            let lcs = [];
            nav = nav==null ? this.nav : nav;
            for (let i=0;i<nav.length;i++) {
                let navi = nav[i];
                if (cgy.is.defined(navi.lock) && navi.lock==true) lcs.push(navi.id);
                if (!cgy.is.empty(navi.children)) {
                    let lcsi = this.getLockNavs(navi.children);
                    if (lcsi.length>0) {
                        lcs.push(...lcsi);
                    }
                }
            }
            return lcs;
        },
        //获取排序为第一的 nav 必须包含 comp 属性
        getFirstNav(nav=null) {
            let f = '';
            nav = nav==null ? this.nav : nav;
            for (let i=0;i<nav.length;i++) {
                let navi = nav[i];
                if (!cgy.is.empty(navi.children)) {
                    let fi = this.getFirstNav(navi.children);
                    if (fi!='') {
                        f =fi;
                        break;
                    }
                }
                if (cgy.is.defined(navi.comp) && navi.comp.url!='') {
                    f = navi.id;
                    break;
                }
            }
            return f;
        },

        //根据 nav 获取要加载的 page 组件名
        getCompNameByNav(navid) {
            console.log('get comp name by nav', navid);
        },
        //根据 xpath 截取 nav 子菜单项
        getSubNavByXPath(xpath=[]) {
            //console.log(xpath);
            let nav = this.nav,
                navi = nav;
            if (xpath.length<=0) return nav;
            for (let i=0;i<xpath.length;i++) {
                let xpi = xpath[i];
                navi = navi[xpi];
                if (cgy.is.empty(navi.children)) return [];
                navi = navi.children;
                //console.log(xpi, navi);
            }
            //return cgy.is.array(navi.children) ? navi.children : [];
            return navi;
        },



        /**
         * page loading
         */
        //page loading
        loadingOn() {
            if (this.layout==null) return false;
            if (this.loading) return false;
            this.loading = true;
            this.layout.page.loading = true;
        },
        loadingOff() {
            if (this.layout==null) return false;
            if (!this.loading) return false;
            this.loading = false;
            this.layout.page.loading = false;
        },
        
    }
}