/**
 * Vue 2.* 组件库插件
 * SPF-Vcom 组件库插件
 * 
 * !! 任何 vcom 基础组件库插件，都必须依赖：
 *      Vue2.* 库，必须在 import 此插件前引入，Vue 必须全局访问
 *      cgy 库（/src/lib/cgy/default.min.js）必须在插件开头，显示挂载到 Vue.cgy
 * 
 * !! vcom 业务组件库插件 无必须依赖的库
 * 
 * !! 此 vcom 组件库插件，外部使用时的参数格式：
 * Vue.use(plugin, options) 的 options 参数形式：
 *      {
 *          # 插件依赖的 服务
 *          service: {
 *              evt: {
 *                  # 是否启用此服务
 *                  enable: true,
 *                  # 其他参数
 *                  ...
 *              },
 *              ui: { ... },
 *              usr: { ... },
 *              ...
 *          },
 * 
 *          # 插件关联的 组件列表
 *          vcoms: {
 *              global: {
 *                  'sv-button': defineSvButton {...},
 *                  ...
 *              },
 *              async: {
 *                  'ms-foo': ()=>import(...),
 *                  ...
 *              },
 *          },
 *      }
 */

import cgy from '/src/lib/cgy/default.min.js';

//SPF-Vcom 组件库插件的 定义文件
import globalMethods from 'plugin/global';
import mixin from 'plugin/mixin';
import instanceMethods from 'plugin/instance';
import directive from 'plugin/directive';

//组件库插件依赖的 一些服务(通用功能，是一组特殊组件，全局单例，挂载到 Vue 对象上)
//import serviceEvtBus from 'mixin/evt-bus';
import serviceUi from 'plugin/service/ui';
//import serviceUsr from 'plugin/service/usr';
//import serviceNav from 'plugin/service/nav';
//import serviceDb from 'plugin/service/db';

/**
 * vcom 基础组件的插件必须的 Vue 对象准备
 * !! 只有 vcom 基础组件库插件需要此段代码，业务组件库插件不需要
 */
//cgy 挂到 window 上
window.cgy = cgy;
//其他需要提前挂载到 Vue 的参数
cgy.def(Vue, {
    //cgy 库挂载到 Vue
    cgy,

    /**
     * 组件库插件依赖的一些服务
     * 服务是一组特殊组件，全局单例，挂载到 Vue 对象上，并通过 mixin 挂载到组件库中每个组件的 computed 中
     * 
     * 需要提前定义 Vue.serviceOptions 参数，用于接收外部传入的 服务的个性化参数
     * 可在 Vue.use(plugin, {...}) 方法中外部传入 这些服务的 个性化参数
     * 
     * 在 创建根组件，依次执行各服务的 async init() 初始化这些服务时，参数会传递到对应的 init 方法
     */
    service: {
        //此插件支持的 服务列表
        support: [
            //服务 必须严格按顺序加载
            //'bus', 'ui', //'usr', 'nav', 'db',
            'ui'
        ],
        //所有启用的服务，必须对应着 import mixin/service-base.js
        imports: {
            //bus:    serviceEvtBus,
            ui:     serviceUi,
            //usr:    serviceUsr,
            //nav:    serviceNav,
            //db:     serviceDb,
        },

        //接受外部传入的 各服务的个性化参数
        options: {},

        //服务的 init 序列，保存着需要依次执行的 各服务的 async init() 方法
        initSequence: [],

        //服务的 特殊组件单例，全局挂载到此
        /*
        bus: VueComponent instance,
        ui: null,
        ...
        */

    },

    //当前已经启用的 Vcom 组件库列表，以及各组件库信息
    vcom: {
        //已启用的 vcom 组件库名称 数组
        list: [],

        //各 vcom 组件库的 desc 元数据
        /*
        'vcn': { ... desc ... },
        ...
        */
    },
    
    //插件使用的 组件库 定义
    vcoms: {
        /**
         * 需要注册为 全局组件的 组件列表
         * 通常是 基础组件库中的 组件
         * 需要包含完整的 Vue2.* 组件定义代码
         */
        global: {
            /*
            'sv-button': defineSvButton {...},
            ...
            */
        },

        /**
         * 需要定义为 异步组件的 组件列表
         * 通常是 业务组件库中的 组件
         * 定义为 () => import(url) 形式
         */
        async: {
            /*
            'pms-table': ()=>import(com-url),
            'pms-foo': 'com-url',
            ...
            */
        },
    },

});

const bs = Object.create(null);
bs.install = function(Vue, options = {}) {

    //扩展 Vue
    cgy.def(Vue, {
        
        host: window.location.href.split('://')[0]+'://'+window.location.href.split('://')[1].split('/')[0],
        
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
        }
    });

    // 1. 添加全局方法或 property
    //Vue.myGlobalMethod = function () {
        // 逻辑...
    //}
    cgy.def(Vue, globalMethods);

    //处理传入的 options 参数，应用其中的 options.service | options.vcoms 等参数
    options = Vue.useInstallOptions(options);

    // 2. 添加全局资源
    //定义 全局|异步 组件
    Vue.defineVcomComponents();
    //Vue.directive('my-directive', { } )
    if (!cgy.is.empty(directive)) {
        for (let dir in directive) {
            Vue.directive(dir, directive[dir]);
        }
    }

    // 4. 添加实例方法
    //Vue.prototype.$myMethod = function (methodOptions) {
        // 逻辑...
    //}
    cgy.def(Vue.prototype, {
        $cgy:       cgy,
        $is:        cgy.is,
        $extend:    cgy.extend,
        $each:      cgy.each,
        $wait:      cgy.wait,
        $until:     cgy.until,
        $log: cgy.log.ready({
            label: 'Vcom',
            sign: '>>>'
        }),
        $vcn:       Vue.vcn,
        $request:   Vue.request,
        $req:       Vue.req,
        $lib:       Vue.lib,
    });

    //引入 instanceMethods 文件
    cgy.def(Vue.prototype, instanceMethods);

    //创建 服务单例
    options = Vue.createVcomService(options);
    

    // 3. 注入组件选项
    //混入 mixin
    Vue.mixin(mixin);
}

export default bs;