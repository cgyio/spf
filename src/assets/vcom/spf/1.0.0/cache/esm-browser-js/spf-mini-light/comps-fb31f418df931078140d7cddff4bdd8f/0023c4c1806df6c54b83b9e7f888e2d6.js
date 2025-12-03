import cgy from 'https://ms.systech.work/src/lib/cgy/default.min.js';
import globalMethods from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/global.js';
import mixin from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/mixin.js';
import instanceMethods from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/instance.js';
import directive from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/directive.js';
import serviceUi from 'https://ms.systech.work/src/vcom/spf/1.0.0/plugin/service/ui.js';
import SpfVcomComps from '/src/vcom/spf/1.0.0/default.js?create=true&ver=1.0.0&mode=mini&prefix=spf&min=true';



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
 * 合并资源 temp_spf_4hJyrO.js
 * !! 不要手动修改 !!
 */


cgy.each(SpfVcomComps, (v, k) => {
Vue.vcoms.global[k] = v;
});










/**
 * 合并资源 temp_spf_O6c1rl.js
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
        light: JSON.parse('{"color":{"red":{"m":"#fa5151","d3":"#770404","d2":"#bc0606","d1":"#f80d0d","l1":"#fb8383","l2":"#fdbaba","l3":"#feebeb","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"orange":{"m":"#ff8600","d3":"#7a4100","d2":"#a85a00","d1":"#d17000","l1":"#ffac4d","l2":"#ffd29e","l3":"#fff5eb","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#000000b3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"yellow":{"m":"#fa9d3b","d3":"#773f03","d2":"#b35f05","d1":"#f48106","l1":"#fcbc79","l2":"#fdd7af","l3":"#fef5eb","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#000000b3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"green":{"m":"#07c160","d3":"#04763b","d2":"#058f48","d1":"#06a754","l1":"#30f891","l2":"#8efbc3","l3":"#ebfef4","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"cyan":{"m":"#01c4b3","d3":"#017a70","d2":"#019387","d1":"#01ad9e","l1":"#2afeec","l2":"#8afef5","l3":"#ebfffd","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"blue":{"m":"#1485ee","d3":"#083f72","d2":"#0b579d","d1":"#0e6ec8","l1":"#5dabf4","l2":"#a4d0f9","l3":"#ecf5fe","m-fc":"#ffffffb3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"purple":{"m":"#ea6e9c","d3":"#6b0f31","d2":"#aa184e","d1":"#e12d6f","l1":"#f098b9","l2":"#f6c1d4","l3":"#fcedf3","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"gray":{"m":"#888888","d3":"#3d3d3d","d2":"#575757","d1":"#6e6e6e","l1":"#ababab","l2":"#d1d1d1","l3":"#f5f5f5","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"danger":{"m":"#fa5151","d3":"#770404","d2":"#bc0606","d1":"#f80d0d","l1":"#fb8383","l2":"#fdbaba","l3":"#feebeb","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"warn":{"m":"#fa9d3b","d3":"#773f03","d2":"#b35f05","d1":"#f48106","l1":"#fcbc79","l2":"#fdd7af","l3":"#fef5eb","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#000000b3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"success":{"m":"#07c160","d3":"#04763b","d2":"#058f48","d1":"#06a754","l1":"#30f891","l2":"#8efbc3","l3":"#ebfef4","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"primary":{"m":"#1485ee","d3":"#083f72","d2":"#0b579d","d1":"#0e6ec8","l1":"#5dabf4","l2":"#a4d0f9","l3":"#ecf5fe","m-fc":"#ffffffb3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"bz":{"m":"#ff8600","d3":"#7a4100","d2":"#a85a00","d1":"#d17000","l1":"#ffac4d","l2":"#ffd29e","l3":"#fff5eb","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#000000b3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"fc":{"m":"#737373","d3":"#3d3d3d","d2":"#4f4f4f","d1":"#616161","l1":"#9e9e9e","l2":"#c9c9c9","l3":"#f5f5f5","m-fc":"#ffffffb3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#ffffffb3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"bgc":{"m":"#f2f2f2","d3":"#3d3d3d","d2":"#7a7a7a","d1":"#b5b5b5","l1":"#f2f2f2","l2":"#f5f5f5","l3":"#f5f5f5","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#000000b3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"bdc":{"m":"#efefef","d3":"#3d3d3d","d2":"#787878","d1":"#b5b5b5","l1":"#f2f2f2","l2":"#f2f2f2","l3":"#f5f5f5","m-fc":"#000000b3","d3-fc":"#ffffffb3","d2-fc":"#ffffffb3","d1-fc":"#000000b3","l1-fc":"#000000b3","l2-fc":"#000000b3","l3-fc":"#000000b3"},"white":{"m":"#ffffff"},"black":{"m":"#000000"},"shadow":{"m":"#0000004d"}},"size":{"fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"22px","xxs":"10px","xxxl":"26px","xxxs":"9px","xxxxl":"32px","xxxxs":"8px"},"fw":{"m":"400px","l":"500px","s":"300px","xl":"700px","xs":"200px","xxl":"900px","xxs":"100px"},"mg":{"m":"16px","l":"20px","s":"12px","xl":"24px","xs":"8px","xxl":"28px","xxs":"4px"},"pd":{"m":"12px","l":"16px","s":"8px","xl":"20px","xs":"4px","xxl":"24px","xxs":"0px"},"rd":{"m":"8px","l":"10px","s":"6px","xl":"12px","xs":"4px","xxl":"14px","xxs":"2px"},"bar":{"m":"36px","l":"40px","s":"32px","xl":"44px","xs":"28px","xxl":"48px","xxs":"24px"},"icon":{"m":"24px","l":"28px","s":"20px","xl":"32px","xs":"16px","xxl":"36px","xxs":"12px"},"btn":{"m":"32px","l":"36px","s":"28px","xl":"42px","xs":"24px","xxl":"48px","xxs":"20px"},"btn-fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"20px","xxs":"10px"},"btn-gap":{"m":"14px","l":"16px","s":"12px","xl":"20px","xs":"10px","xxl":"24px","xxs":"8px"},"btn-rd":{"m":"8px","l":"9px","s":"7px","xl":"10px","xs":"6px","xxl":"12px","xxs":"5px"},"bd":{"m":"1px"}},"vars":{"font":{"fml":{"default":"-apple-system,BlinkMacSystemFont,\'Segoe UI\',\'PingFang SC\',\'Microsoft Yahei\',Helvetica,Arial,sans-serif,\'Apple Color Emoji\',\'Segoe UI Emoji\'","code":"\'Cascadia Code\', \'Consolas\', \'Courier New\', \'Pingfang SC\', \'Microsoft Yahei\', monospace","fangsong":"\'Times New Roman\', \'仿宋\'","yahei":"\'微软雅黑\', \'Microsoft Yahei\'","songti":"\'Times New Roman\', \'宋体\'"}},"ani":{"dura":"0.3s"}},"extra":{"color":[],"size":{"sizeStrMap":{"huge":"xxl","large":"xl","medium":"l","normal":"m","small":"s","mini":"xs","tiny":"xxs"},"extraSizeQueue":{"icon":[48,54,64,72,88,96,128],"btn":[54,64,72,88,96,128]}},"vars":[]}}'),
        dark: JSON.parse('{"color":{"red":{"m":"#fa5151","d3":"#fb8888","d2":"#fb7474","d1":"#fa6666","l1":"#df0707","l2":"#770404","l3":"#140101","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"orange":{"m":"#ff8600","d3":"#ffc685","d2":"#ffb057","d1":"#ff9d2e","l1":"#b35f00","l2":"#613400","l3":"#140b00","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"yellow":{"m":"#fa9d3b","d3":"#fcc488","d2":"#fbb76f","d1":"#fbab56","l1":"#d16e05","l2":"#723d03","l3":"#140b01","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#000000b3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"green":{"m":"#07c160","d3":"#89fbc0","d2":"#4ef9a0","d1":"#0ef67e","l1":"#058543","l2":"#034f28","l3":"#01140a","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"cyan":{"m":"#01c4b3","d3":"#85fef4","d2":"#48feef","d1":"#06fee9","l1":"#01897e","l2":"#00514a","l3":"#001413","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"blue":{"m":"#1485ee","d3":"#8dc4f7","d2":"#66b0f4","d1":"#3b99f1","l1":"#0c5ca7","l2":"#07345f","l3":"#010a13","m-fc":"#ffffffb3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"purple":{"m":"#ea6e9c","d3":"#f094b6","d2":"#ee87ac","d1":"#ec79a3","l1":"#cd1d5e","l2":"#701033","l3":"#120308","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"gray":{"m":"#888888","d3":"#c2c2c2","d2":"#adadad","d1":"#9c9c9c","l1":"#5e5e5e","l2":"#333333","l3":"#0a0a0a","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"danger":{"m":"#fa5151","d3":"#fb8888","d2":"#fb7474","d1":"#fa6666","l1":"#df0707","l2":"#770404","l3":"#140101","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"warn":{"m":"#fa9d3b","d3":"#fcc488","d2":"#fbb76f","d1":"#fbab56","l1":"#d16e05","l2":"#723d03","l3":"#140b01","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#000000b3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"success":{"m":"#07c160","d3":"#89fbc0","d2":"#4ef9a0","d1":"#0ef67e","l1":"#058543","l2":"#034f28","l3":"#01140a","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"primary":{"m":"#1485ee","d3":"#8dc4f7","d2":"#66b0f4","d1":"#3b99f1","l1":"#0c5ca7","l2":"#07345f","l3":"#010a13","m-fc":"#ffffffb3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"bz":{"m":"#ff8600","d3":"#ffc685","d2":"#ffb057","d1":"#ff9d2e","l1":"#b35f00","l2":"#613400","l3":"#140b00","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"fc":{"m":"#8c8c8c","d3":"#c2c2c2","d2":"#b0b0b0","d1":"#9e9e9e","l1":"#616161","l2":"#363636","l3":"#0a0a0a","m-fc":"#000000b3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#000000b3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"bgc":{"m":"#121212","d3":"#c2c2c2","d2":"#878787","d1":"#4d4d4d","l1":"#0f0f0f","l2":"#0d0d0d","l3":"#0a0a0a","m-fc":"#ffffffb3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#ffffffb3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"bdc":{"m":"#1a1a1a","d3":"#c2c2c2","d2":"#8a8a8a","d1":"#525252","l1":"#141414","l2":"#0f0f0f","l3":"#0a0a0a","m-fc":"#ffffffb3","d3-fc":"#000000b3","d2-fc":"#000000b3","d1-fc":"#ffffffb3","l1-fc":"#ffffffb3","l2-fc":"#ffffffb3","l3-fc":"#ffffffb3"},"white":{"m":"#000000"},"black":{"m":"#ffffff"},"shadow":{"m":"#ffffff4d"}},"size":{"fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"22px","xxs":"10px","xxxl":"26px","xxxs":"9px","xxxxl":"32px","xxxxs":"8px"},"fw":{"m":"400px","l":"500px","s":"300px","xl":"700px","xs":"200px","xxl":"900px","xxs":"100px"},"mg":{"m":"16px","l":"20px","s":"12px","xl":"24px","xs":"8px","xxl":"28px","xxs":"4px"},"pd":{"m":"12px","l":"16px","s":"8px","xl":"20px","xs":"4px","xxl":"24px","xxs":"0px"},"rd":{"m":"8px","l":"10px","s":"6px","xl":"12px","xs":"4px","xxl":"14px","xxs":"2px"},"bar":{"m":"36px","l":"40px","s":"32px","xl":"44px","xs":"28px","xxl":"48px","xxs":"24px"},"icon":{"m":"24px","l":"28px","s":"20px","xl":"32px","xs":"16px","xxl":"36px","xxs":"12px"},"btn":{"m":"32px","l":"36px","s":"28px","xl":"42px","xs":"24px","xxl":"48px","xxs":"20px"},"btn-fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"20px","xxs":"10px"},"btn-gap":{"m":"14px","l":"16px","s":"12px","xl":"20px","xs":"10px","xxl":"24px","xxs":"8px"},"btn-rd":{"m":"8px","l":"9px","s":"7px","xl":"10px","xs":"6px","xxl":"12px","xxs":"5px"},"bd":{"m":"1px"}},"vars":{"font":{"fml":{"default":"-apple-system,BlinkMacSystemFont,\'Segoe UI\',\'PingFang SC\',\'Microsoft Yahei\',Helvetica,Arial,sans-serif,\'Apple Color Emoji\',\'Segoe UI Emoji\'","code":"\'Cascadia Code\', \'Consolas\', \'Courier New\', \'Pingfang SC\', \'Microsoft Yahei\', monospace","fangsong":"\'Times New Roman\', \'仿宋\'","yahei":"\'微软雅黑\', \'Microsoft Yahei\'","songti":"\'Times New Roman\', \'宋体\'"}},"ani":{"dura":"0.3s"}},"extra":{"color":[],"size":{"sizeStrMap":{"huge":"xxl","large":"xl","medium":"l","normal":"m","small":"s","mini":"xs","tiny":"xxs"},"extraSizeQueue":{"icon":[48,54,64,72,88,96,128],"btn":[54,64,72,88,96,128]}},"vars":[]}}'),
    },
}
let autoDarkMode = cgy.localStorage.$getJson('service.ui', 'theme.autoDarkMode', true);
let inDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
if (!autoDarkMode) inDarkMode = cgy.localStorage.$getJson('service.ui', 'theme.inDarkMode', false);
Vue.service.options.ui.theme.autoDarkMode = autoDarkMode;
Vue.service.options.ui.theme.inDarkMode = inDarkMode;
Vue.service.options = cgy.extend(Vue.service.options, {nav:{pages:[], page:{}}});


