import cgy from '/src/lib/cgy/default.min.js?module=tagprint';
import globalMethods from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/global.js';
import mixin from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/mixin.js';
import instanceMethods from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/instance.js';
import directive from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/directive.js';
import mixinEvtBus from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/evt-bus.js';
import mixinUiBase from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/ui-base.js';
import mixinUsrBase from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/usr-base.js';
import mixinNavBase from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/nav-base.js';
import mixinDbBase from 'https://ms.systech.work/src/vcom/spf/1.0.0/mixin/db-base.js';
import SpfVcomComps from '/src/vcom/spf/1.0.0/default.js?ignoreGet=true&create=true&ver=1.0.0&mode=mini&prefix=spf&min=true';



/**
 * 合并资源 plugin.js
 * !! 不要手动修改 !!
 */

/**
 * Vue 2.* 插件 base
 * spf.vcom 基础组件库 插件
 * 
 * 入口，提供 install 方法
 * 
 * 依赖的库：
 *      Vue         2.*
 *      cgy         /src/lib/cgy/default.min.js?module=tagprint
 */

//需要 cgy.core.js 支持
//const cgy = ...
//console.log(cgy.version);

//需要 vue.2.7.9 支持
//console.log(Vue);




//cgy 挂到 window 上
window.cgy = cgy;

const bs = Object.create(null);
bs.install = function(Vue, options = {}) {

    //扩展 Vue
    cgy.def(Vue, {
        cgy,
        host: window.location.href.split('://')[0]+'://'+window.location.href.split('://')[1].split('/')[0],
        //lib: 'https://io.cgy.design',
        
        //根组件实例
        $root: {
            value: null,
            writable: true
        },

        //动态组件缓存，通过 invokeComp() 方法动态加载的组件实例挂在此属性下
        dynamicComponentsInstance: [],
        //debug
        debug: {
            value: false,
            writable: true
        },

        //usr 用户管理器，全局管理 usr 用户
        //usr,
        //主页 page 管理器，用于 spa 单页应用
        //pager,

        //ui 相关
        //样式主题/暗黑模式管理
        //theme,

        //base 插件自定义 options
        baseOptions: {},
        //base 插件 init 序列
        baseInitSequence: [],
    });

    // 1. 添加全局方法或 property
    //Vue.myGlobalMethod = function () {
        // 逻辑...
    //}
    //先将 options 缓存，在 initBasePlugin() 执行时将对 Vue.baseOptions 进行处理
    Object.assign(Vue.baseOptions, options);

    //应用全局方法
    //Object.assign(Vue, globalMethods);
    cgy.def(Vue, globalMethods);

    //Vue.usr.setOptions(options);
    //Vue.pager.setOptions(options);
    //Vue.theme.setOptions(options);

    // 2. 添加全局资源
    //注册全局组件
    //先缓存 此插件使用的 组件库名称 到 baseOptions
    /*Vue.baseOptions.globalComponents = ['base'];
    for (let [compn, compu] of Object.entries(comps)) {
        Vue.component(
            compn,
            ()=>import(compu)
        );
    }*/
    //Vue.directive('my-directive', { } )
    if (!cgy.is.empty(directive)) {
        for (let dir in directive) {
            Vue.directive(dir, directive[dir]);
        }
    }

    // 3. 注入组件选项
    //Vue.mixin(mixin);

    // 4. 添加实例方法
    //Vue.prototype.$myMethod = function (methodOptions) {
        // 逻辑...
    //}
    cgy.def(Vue.prototype, {
        $cgy: cgy,
        $is: cgy.is,
        $extend: cgy.extend,
        $wait: cgy.wait,
        $until: cgy.until,
        $log: cgy.log.ready({
            label: 'CVue',
            sign: '>>>'
        }),
        $request: Vue.request,
        $req: Vue.req,
        $lib: Vue.lib,
    });

    //引入 instanceMethods 文件
    cgy.def(Vue.prototype, instanceMethods);

    //特殊组件实例，单例
    cgy.def(Vue, {
        
        //事件总线
        evtBus: new Vue({
            mixins: [mixinEvtBus]
        }),

        //ui
        ui: new Vue({
            mixins: [mixinUiBase]
        }),

        //uac 权限控制
        usr: new Vue({
            mixins: [mixinUsrBase]
        }),

        //nav 导航管理
        nav: new Vue({
            mixins: [mixinNavBase]
        }),

        //db 数据库管理
        db: new Vue({
            mixins: [mixinDbBase]
        }),
    });
    cgy.def(Vue.prototype, {
        $bus: Vue.evtBus,
        $ev: Vue.evtBus.$trigger,
        $ui: Vue.ui,
        $usr: Vue.usr,
        $nav: Vue.nav,
        $db: Vue.db,
    });
    Vue.evtBus.event = {};

    //将各功能组件单例的 init 方法添加到 baseInitSequence 启动序列
    //只有在启动序列中所有 async 函数执行完成后，才能执行根组件创建
    let initComps = ['ui','usr','nav'];
    for (let i=0;i<initComps.length;i++) {
        let compi = Vue[initComps[i]],
            init = compi.init;
        if (cgy.is(init,'asyncfunction')) {
            Vue.baseInitSequence.push(init.bind(compi));
        }
    }
    
    //混入 mixin
    Vue.mixin(mixin);
    
    //各功能模块准备
    //Vue.prepareBasePluginModules(options, 'usr','pager','theme');
    //cgy.conf(Vue, options)
}

export default bs;









/**
 * 合并资源 spf.vcom.js
 * !! 不要手动修改 !!
 */


for (const [key, value] of Object.entries(SpfVcomComps)) {
Vue.component(key, value);
}









