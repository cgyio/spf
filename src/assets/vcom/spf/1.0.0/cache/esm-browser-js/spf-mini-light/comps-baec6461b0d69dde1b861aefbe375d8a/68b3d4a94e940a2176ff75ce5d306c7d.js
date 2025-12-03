import cgy from 'https://ms.systech.work/src/lib/cgy/default.min.js';
import globalMethods from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/global.js';
import mixin from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/mixin.js';
import instanceMethods from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/instance.js';
import directive from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/directive.js';
import serviceUi from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/service/ui.js';
import SpfVcomComps from '/src/vcom/spf/1.0.0/default.js?create=true&ver=1.0.0&mode=mini&prefix=spf';



/**
 * 合并资源 plugin.js
 * !! 不要手动修改 !!
 */

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


//SPF-Vcom 组件库插件的 定义文件

//组件库插件依赖的 一些服务(通用功能，是一组特殊组件，全局单例，挂载到 Vue 对象上)
//import serviceEvtBus from 'mixin/evt-bus';
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









/**
 * 合并资源 temp_spf_WF9HE5.js
 * !! 不要手动修改 !!
 */


cgy.each(SpfVcomComps, (v, k) => {
Vue.vcoms.global[k] = v;
});










/**
 * 合并资源 temp_spf_1d2bvP.js
 * !! 不要手动修改 !!
 */




/**
 * 写入组件库 spf 信息
 * !! 不要手动修改 !!
 */

if (Vue.vcom.list.includes('base')!==true) {
    Vue.vcom.list.push('base');
    Vue.vcom.base = {
        name: 'spf',
        isBase: true,
        prefix: 'spf',
    }
}



/**
 * 注入此插件使用的 SPF-Theme 主题参数
 * !! 不要手动修改 !!
 */

Vue.service.options = cgy.extend(Vue.service.options, {ui: {}});
Vue.service.options.ui.theme = {
    enable: true,
    supportDarkMode: true,
    cssvar: {
        light: JSON.parse('{"color":{"red":{"m":"#fa5151","d3":"#770404","d2":"#bc0606","d1":"#f80d0d","l1":"#fb8383","l2":"#fdbaba","l3":"#feebeb"},"orange":{"m":"#ff8600","d3":"#7a4100","d2":"#a85a00","d1":"#d17000","l1":"#ffac4d","l2":"#ffd29e","l3":"#fff5eb"},"yellow":{"m":"#fa9d3b","d3":"#773f03","d2":"#b35f05","d1":"#f48106","l1":"#fcbc79","l2":"#fdd7af","l3":"#fef5eb"},"green":{"m":"#07c160","d3":"#04763b","d2":"#058f48","d1":"#06a754","l1":"#30f891","l2":"#8efbc3","l3":"#ebfef4"},"cyan":{"m":"#01c4b3","d3":"#017a70","d2":"#019387","d1":"#01ad9e","l1":"#2afeec","l2":"#8afef5","l3":"#ebfffd"},"blue":{"m":"#1485ee","d3":"#083f72","d2":"#0b579d","d1":"#0e6ec8","l1":"#5dabf4","l2":"#a4d0f9","l3":"#ecf5fe"},"purple":{"m":"#ea6e9c","d3":"#6b0f31","d2":"#aa184e","d1":"#e12d6f","l1":"#f098b9","l2":"#f6c1d4","l3":"#fcedf3"},"gray":{"m":"#888888","d3":"#3d3d3d","d2":"#575757","d1":"#6e6e6e","l1":"#ababab","l2":"#d1d1d1","l3":"#f5f5f5"},"danger":{"m":"#fa5151","d3":"#770404","d2":"#bc0606","d1":"#f80d0d","l1":"#fb8383","l2":"#fdbaba","l3":"#feebeb"},"warn":{"m":"#fa9d3b","d3":"#773f03","d2":"#b35f05","d1":"#f48106","l1":"#fcbc79","l2":"#fdd7af","l3":"#fef5eb"},"success":{"m":"#07c160","d3":"#04763b","d2":"#058f48","d1":"#06a754","l1":"#30f891","l2":"#8efbc3","l3":"#ebfef4"},"primary":{"m":"#1485ee","d3":"#083f72","d2":"#0b579d","d1":"#0e6ec8","l1":"#5dabf4","l2":"#a4d0f9","l3":"#ecf5fe"},"bz":{"m":"#ff8600","d3":"#7a4100","d2":"#a85a00","d1":"#d17000","l1":"#ffac4d","l2":"#ffd29e","l3":"#fff5eb"},"fc":{"m":"#737373","d3":"#3d3d3d","d2":"#4f4f4f","d1":"#616161","l1":"#9e9e9e","l2":"#c9c9c9","l3":"#f5f5f5"},"bgc":{"m":"#f2f2f2","d3":"#3d3d3d","d2":"#7a7a7a","d1":"#b5b5b5","l1":"#f2f2f2","l2":"#f5f5f5","l3":"#f5f5f5"},"bdc":{"m":"#efefef","d3":"#3d3d3d","d2":"#787878","d1":"#b5b5b5","l1":"#f2f2f2","l2":"#f2f2f2","l3":"#f5f5f5"},"white":{"m":"#ffffff"},"black":{"m":"#000000"},"shadow":{"m":"#0000004d"}},"size":{"fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"22px","xxs":"10px","xxxl":"26px","xxxs":"9px","xxxxl":"32px","xxxxs":"8px"},"fw":{"m":"400px","l":"500px","s":"300px","xl":"700px","xs":"200px","xxl":"900px","xxs":"100px"},"mg":{"m":"16px","l":"20px","s":"12px","xl":"24px","xs":"8px","xxl":"28px","xxs":"4px"},"pd":{"m":"12px","l":"16px","s":"8px","xl":"20px","xs":"4px","xxl":"24px","xxs":"0px"},"rd":{"m":"8px","l":"10px","s":"6px","xl":"12px","xs":"4px","xxl":"14px","xxs":"2px"},"btn":{"m":"32px","l":"36px","s":"28px","xl":"40px","xs":"24px","xxl":"44px","xxs":"20px"},"bar":{"m":"36px","l":"40px","s":"32px","xl":"44px","xs":"28px","xxl":"48px","xxs":"24px"},"icon":{"m":"20px","l":"24px","s":"16px","xl":"28px","xs":"12px","xxl":"32px","xxs":"8px"},"bd":{"m":"1px"}},"vars":{"font":{"fml":{"default":"-apple-system,BlinkMacSystemFont,\'Segoe UI\',\'PingFang SC\',\'Microsoft Yahei\',Helvetica,Arial,sans-serif,\'Apple Color Emoji\',\'Segoe UI Emoji\'","code":"\'Cascadia Code\', \'Consolas\', \'Courier New\', \'Pingfang SC\', \'Microsoft Yahei\', monospace","fangsong":"\'Times New Roman\', \'仿宋\'","yahei":"\'微软雅黑\', \'Microsoft Yahei\'","songti":"\'Times New Roman\', \'宋体\'"}},"ani":{"dura":"0.3s"}}}'),
        dark: JSON.parse('{"color":{"red":{"m":"#fa5151","d3":"#feebeb","d2":"#fdbaba","d1":"#fb8383","l1":"#f80d0d","l2":"#bc0606","l3":"#770404"},"orange":{"m":"#ff8600","d3":"#fff5eb","d2":"#ffd29e","d1":"#ffac4d","l1":"#d17000","l2":"#a85a00","l3":"#7a4100"},"yellow":{"m":"#fa9d3b","d3":"#fef5eb","d2":"#fdd7af","d1":"#fcbc79","l1":"#f48106","l2":"#b35f05","l3":"#773f03"},"green":{"m":"#07c160","d3":"#ebfef4","d2":"#8efbc3","d1":"#30f891","l1":"#06a754","l2":"#058f48","l3":"#04763b"},"cyan":{"m":"#01c4b3","d3":"#ebfffd","d2":"#8afef5","d1":"#2afeec","l1":"#01ad9e","l2":"#019387","l3":"#017a70"},"blue":{"m":"#1485ee","d3":"#ecf5fe","d2":"#a4d0f9","d1":"#5dabf4","l1":"#0e6ec8","l2":"#0b579d","l3":"#083f72"},"purple":{"m":"#ea6e9c","d3":"#fcedf3","d2":"#f6c1d4","d1":"#f098b9","l1":"#e12d6f","l2":"#aa184e","l3":"#6b0f31"},"gray":{"m":"#888888","d3":"#f5f5f5","d2":"#d1d1d1","d1":"#ababab","l1":"#6e6e6e","l2":"#575757","l3":"#3d3d3d"},"danger":{"m":"#fa5151","d3":"#feebeb","d2":"#fdbaba","d1":"#fb8383","l1":"#f80d0d","l2":"#bc0606","l3":"#770404"},"warn":{"m":"#fa9d3b","d3":"#fef5eb","d2":"#fdd7af","d1":"#fcbc79","l1":"#f48106","l2":"#b35f05","l3":"#773f03"},"success":{"m":"#07c160","d3":"#ebfef4","d2":"#8efbc3","d1":"#30f891","l1":"#06a754","l2":"#058f48","l3":"#04763b"},"primary":{"m":"#1485ee","d3":"#ecf5fe","d2":"#a4d0f9","d1":"#5dabf4","l1":"#0e6ec8","l2":"#0b579d","l3":"#083f72"},"bz":{"m":"#ff8600","d3":"#fff5eb","d2":"#ffd29e","d1":"#ffac4d","l1":"#d17000","l2":"#a85a00","l3":"#7a4100"},"fc":{"m":"#737373","d3":"#f5f5f5","d2":"#c9c9c9","d1":"#9e9e9e","l1":"#616161","l2":"#4f4f4f","l3":"#3d3d3d"},"bgc":{"m":"#f2f2f2","d3":"#f5f5f5","d2":"#f5f5f5","d1":"#f2f2f2","l1":"#b5b5b5","l2":"#7a7a7a","l3":"#3d3d3d"},"bdc":{"m":"#efefef","d3":"#f5f5f5","d2":"#f2f2f2","d1":"#f2f2f2","l1":"#b5b5b5","l2":"#787878","l3":"#3d3d3d"},"white":{"m":"#ffffff"},"black":{"m":"#000000"},"shadow":{"m":"#0000004d"}},"size":{"fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"22px","xxs":"10px","xxxl":"26px","xxxs":"9px","xxxxl":"32px","xxxxs":"8px"},"fw":{"m":"400px","l":"500px","s":"300px","xl":"700px","xs":"200px","xxl":"900px","xxs":"100px"},"mg":{"m":"16px","l":"20px","s":"12px","xl":"24px","xs":"8px","xxl":"28px","xxs":"4px"},"pd":{"m":"12px","l":"16px","s":"8px","xl":"20px","xs":"4px","xxl":"24px","xxs":"0px"},"rd":{"m":"8px","l":"10px","s":"6px","xl":"12px","xs":"4px","xxl":"14px","xxs":"2px"},"btn":{"m":"32px","l":"36px","s":"28px","xl":"40px","xs":"24px","xxl":"44px","xxs":"20px"},"bar":{"m":"36px","l":"40px","s":"32px","xl":"44px","xs":"28px","xxl":"48px","xxs":"24px"},"icon":{"m":"20px","l":"24px","s":"16px","xl":"28px","xs":"12px","xxl":"32px","xxs":"8px"},"bd":{"m":"1px"}},"vars":{"font":{"fml":{"default":"-apple-system,BlinkMacSystemFont,\'Segoe UI\',\'PingFang SC\',\'Microsoft Yahei\',Helvetica,Arial,sans-serif,\'Apple Color Emoji\',\'Segoe UI Emoji\'","code":"\'Cascadia Code\', \'Consolas\', \'Courier New\', \'Pingfang SC\', \'Microsoft Yahei\', monospace","fangsong":"\'Times New Roman\', \'仿宋\'","yahei":"\'微软雅黑\', \'Microsoft Yahei\'","songti":"\'Times New Roman\', \'宋体\'"}},"ani":{"dura":"0.3s"}}}'),
    },
}
let autoDarkMode = cgy.localStorage.$getJson('service.ui', 'theme.autoDarkMode', true);
let inDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
if (!autoDarkMode) inDarkMode = cgy.localStorage.$getJson('service.ui', 'theme.inDarkMode', false);
Vue.service.options.ui.theme.autoDarkMode = autoDarkMode;
Vue.service.options.ui.theme.inDarkMode = inDarkMode;
Vue.service.options = cgy.extend(Vue.service.options, {nav:{pages:[], page:{}}});


