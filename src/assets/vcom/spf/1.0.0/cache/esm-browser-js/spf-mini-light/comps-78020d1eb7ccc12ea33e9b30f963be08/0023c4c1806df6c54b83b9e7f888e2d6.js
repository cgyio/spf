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
 * 合并资源 temp_spf_vXkugb.js
 * !! 不要手动修改 !!
 */


cgy.each(SpfVcomComps, (v, k) => {
Vue.vcoms.global[k] = v;
});










/**
 * 合并资源 temp_spf_THTivK.js
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
        light: JSON.parse('{"color":{"red":{"m":"#fa5151","d3":"#782626","d2":"#a23434","d1":"#cf4343","l1":"#fb7a7a","l2":"#fca4a4","l3":"#fdcece","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"orange":{"m":"#ff8600","d3":"#7a4000","d2":"#a55700","d1":"#d36f00","l1":"#ffa33d","l2":"#ffc07a","l3":"#ffddb7","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#000000e6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"yellow":{"m":"#fa9d3b","d3":"#784b1c","d2":"#a26626","d1":"#cf8230","l1":"#fbb46a","l2":"#fccc99","l3":"#fde3c8","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#000000e6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"green":{"m":"#07c160","d3":"#035c2e","d2":"#047d3e","d1":"#05a04f","l1":"#42cf86","l2":"#7edeac","l3":"#b9edd2","m-fc":"#ffffffe6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"cyan":{"m":"#01c4b3","d3":"#005e55","d2":"#007f74","d1":"#00a294","l1":"#3dd2c5","l2":"#7ae0d7","l3":"#b7eee9","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"blue":{"m":"#1485ee","d3":"#093f72","d2":"#0d569a","d1":"#106ec5","l1":"#4ca2f2","l2":"#84bff6","l3":"#bddcfa","m-fc":"#ffffffe6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"purple":{"m":"#ea6e9c","d3":"#70344a","d2":"#984765","d1":"#c25b81","l1":"#ef90b3","l2":"#f4b3cb","l3":"#f9d6e3","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"gray":{"m":"#888888","d3":"#414141","d2":"#585858","d1":"#707070","l1":"#a4a4a4","l2":"#c1c1c1","l3":"#dddddd","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"danger":{"m":"#fa5151","d3":"#782626","d2":"#a23434","d1":"#cf4343","l1":"#fb7a7a","l2":"#fca4a4","l3":"#fdcece","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"warn":{"m":"#fa9d3b","d3":"#784b1c","d2":"#a26626","d1":"#cf8230","l1":"#fbb46a","l2":"#fccc99","l3":"#fde3c8","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#000000e6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"success":{"m":"#07c160","d3":"#035c2e","d2":"#047d3e","d1":"#05a04f","l1":"#42cf86","l2":"#7edeac","l3":"#b9edd2","m-fc":"#ffffffe6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"primary":{"m":"#1485ee","d3":"#093f72","d2":"#0d569a","d1":"#106ec5","l1":"#4ca2f2","l2":"#84bff6","l3":"#bddcfa","m-fc":"#ffffffe6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"info":{"m":"#888888","d3":"#414141","d2":"#585858","d1":"#707070","l1":"#a4a4a4","l2":"#c1c1c1","l3":"#dddddd","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"bz":{"m":"#ff8600","d3":"#7a4000","d2":"#a55700","d1":"#d36f00","l1":"#ffa33d","l2":"#ffc07a","l3":"#ffddb7","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#000000e6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"fc":{"m":"#555555","d3":"#282828","d2":"#373737","d1":"#464646","l1":"#7d7d7d","l2":"#a6a6a6","l3":"#cfcfcf","m-fc":"#ffffffe6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#ffffffe6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"bgc":{"m":"#f3f3f3","d3":"#919191","d2":"#b1b1b1","d1":"#d3d3d3","l1":"#f6f6f6","l2":"#fbfbfb","l3":"#ffffff","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"bdc":{"m":"#dfdfdf","d3":"#787878","d2":"#999999","d1":"#bdbdbd","l1":"#e5e5e5","l2":"#ececec","l3":"#f3f3f3","m-fc":"#000000e6","d3-fc":"#ffffffe6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#000000e6","l2-fc":"#000000e6","l3-fc":"#000000e6"},"white":{"m":"#ffffff"},"black":{"m":"#000000"},"shadow":{"m":"#000000"}},"size":{"fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"22px","xxs":"10px","xxxl":"26px","xxxs":"9px","xxxxl":"32px","xxxxs":"8px"},"fw":{"m":"400","l":"500","s":"300","xl":"700","xs":"200","xxl":"900","xxs":"100"},"mg":{"m":"14px","l":"16px","s":"12px","xl":"20px","xs":"10px","xxl":"24px","xxs":"8px"},"pd":{"m":"14px","l":"16px","s":"12px","xl":"20px","xs":"10px","xxl":"24px","xxs":"8px"},"rd":{"m":"8px","l":"10px","s":"6px","xl":"12px","xs":"4px","xxl":"14px","xxs":"2px"},"bar":{"m":"36px","l":"40px","s":"32px","xl":"44px","xs":"28px","xxl":"48px","xxs":"24px"},"icon":{"m":"24px","l":"28px","s":"20px","xl":"32px","xs":"16px","xxl":"36px","xxs":"12px"},"btn":{"m":"32px","l":"36px","s":"28px","xl":"42px","xs":"24px","xxl":"48px","xxs":"20px"},"btn-fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"20px","xxs":"10px"},"btn-pd":{"m":"14px","l":"16px","s":"12px","xl":"20px","xs":"10px","xxl":"24px","xxs":"8px"},"btn-rd":{"m":"8px","l":"9px","s":"7px","xl":"10px","xs":"6px","xxl":"12px","xxs":"5px"},"shadow":{"m":"8px","l":"12px","s":"4px"},"bd":{"m":"1px"},"blur":{"m":"8px"}},"vars":{"font":{"fml":{"default":"-apple-system,BlinkMacSystemFont,\'Segoe UI\',\'PingFang SC\',\'Microsoft Yahei\',Helvetica,Arial,sans-serif,\'Apple Color Emoji\',\'Segoe UI Emoji\'","code":"\'Cascadia Code\', \'Consolas\', \'Courier New\', \'Pingfang SC\', \'Microsoft Yahei\', monospace","fangsong":"\'Times New Roman\', \'仿宋\'","yahei":"\'微软雅黑\', \'Microsoft Yahei\'","songti":"\'Times New Roman\', \'宋体\'"}},"ani":{"dura":"0.3s"}},"extra":{"color":{"alias":{"danger":"red","warn":"yellow","success":"green","primary":"blue","info":"gray"},"types":["primary","danger","warn","success","info"],"effects":["normal","fill","plain","popout"]},"size":{"sizeStrMap":{"huge":"xxl","large":"xl","medium":"l","normal":"m","small":"s","mini":"xs","tiny":"xxs"},"extraSizeQueue":{"icon":[48,54,64,72,88,96,128],"btn":[54,64,72,88,96,128]},"shapes":["normal","pill","circle","sharp"],"stretches":["square","normal","row"],"tightnesses":["loose","normal","tight"]},"vars":[]}}'),
        dark: JSON.parse('{"color":{"red":{"m":"#fa5151","d3":"#fdc0c0","d2":"#fc9b9b","d1":"#fb7575","l1":"#bb3c3c","l2":"#7a2727","l3":"#3c1313","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"orange":{"m":"#ff8600","d3":"#ffd3a3","d2":"#ffba6d","d1":"#ff9f35","l1":"#bf6400","l2":"#7c4100","l3":"#3d2000","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"yellow":{"m":"#fa9d3b","d3":"#fddbb8","d2":"#fcc78f","d1":"#fbb164","l1":"#bb752c","l2":"#7a4c1c","l3":"#3c250e","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#000000e6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"green":{"m":"#07c160","d3":"#a5e8c5","d2":"#71dba4","d1":"#3bce81","l1":"#059048","l2":"#035e2f","l3":"#012e17","m-fc":"#ffffffe6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"cyan":{"m":"#01c4b3","d3":"#a3e9e3","d2":"#6eddd3","d1":"#36d0c2","l1":"#009386","l2":"#006057","l3":"#002f2a","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"blue":{"m":"#1485ee","d3":"#aad3f8","d2":"#79b9f5","d1":"#459ef1","l1":"#0f63b2","l2":"#094174","l3":"#041f39","m-fc":"#ffffffe6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"purple":{"m":"#ea6e9c","d3":"#f7cadb","d2":"#f3acc6","d1":"#ee8cb0","l1":"#af5275","l2":"#72354c","l3":"#381a25","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"gray":{"m":"#888888","d3":"#d4d4d4","d2":"#bbbbbb","d1":"#a0a0a0","l1":"#666666","l2":"#424242","l3":"#202020","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"danger":{"m":"#fa5151","d3":"#fdc0c0","d2":"#fc9b9b","d1":"#fb7575","l1":"#bb3c3c","l2":"#7a2727","l3":"#3c1313","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"warn":{"m":"#fa9d3b","d3":"#fddbb8","d2":"#fcc78f","d1":"#fbb164","l1":"#bb752c","l2":"#7a4c1c","l3":"#3c250e","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#000000e6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"success":{"m":"#07c160","d3":"#a5e8c5","d2":"#71dba4","d1":"#3bce81","l1":"#059048","l2":"#035e2f","l3":"#012e17","m-fc":"#ffffffe6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"primary":{"m":"#1485ee","d3":"#aad3f8","d2":"#79b9f5","d1":"#459ef1","l1":"#0f63b2","l2":"#094174","l3":"#041f39","m-fc":"#ffffffe6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"info":{"m":"#888888","d3":"#d4d4d4","d2":"#bbbbbb","d1":"#a0a0a0","l1":"#666666","l2":"#424242","l3":"#202020","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"bz":{"m":"#ff8600","d3":"#ffd3a3","d2":"#ffba6d","d1":"#ff9f35","l1":"#bf6400","l2":"#7c4100","l3":"#3d2000","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"fc":{"m":"#bbbbbb","d3":"#e6e6e6","d2":"#d8d8d8","d1":"#c9c9c9","l1":"#8c8c8c","l2":"#5b5b5b","l3":"#2c2c2c","m-fc":"#000000e6","d3-fc":"#000000e6","d2-fc":"#000000e6","d1-fc":"#000000e6","l1-fc":"#000000e6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"bgc":{"m":"#202020","d3":"#707070","d2":"#555555","d1":"#3a3a3a","l1":"#151515","l2":"#0a0a0a","l3":"#000000","m-fc":"#ffffffe6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"bdc":{"m":"#333333","d3":"#7c7c7c","d2":"#636363","d1":"#4b4b4b","l1":"#262626","l2":"#181818","l3":"#0c0c0c","m-fc":"#ffffffe6","d3-fc":"#ffffffe6","d2-fc":"#ffffffe6","d1-fc":"#ffffffe6","l1-fc":"#ffffffe6","l2-fc":"#ffffffe6","l3-fc":"#ffffffe6"},"white":{"m":"#000000"},"black":{"m":"#ffffff"},"shadow":{"m":"#ffffff"}},"size":{"fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"22px","xxs":"10px","xxxl":"26px","xxxs":"9px","xxxxl":"32px","xxxxs":"8px"},"fw":{"m":"400","l":"500","s":"300","xl":"700","xs":"200","xxl":"900","xxs":"100"},"mg":{"m":"14px","l":"16px","s":"12px","xl":"20px","xs":"10px","xxl":"24px","xxs":"8px"},"pd":{"m":"14px","l":"16px","s":"12px","xl":"20px","xs":"10px","xxl":"24px","xxs":"8px"},"rd":{"m":"8px","l":"10px","s":"6px","xl":"12px","xs":"4px","xxl":"14px","xxs":"2px"},"bar":{"m":"36px","l":"40px","s":"32px","xl":"44px","xs":"28px","xxl":"48px","xxs":"24px"},"icon":{"m":"24px","l":"28px","s":"20px","xl":"32px","xs":"16px","xxl":"36px","xxs":"12px"},"btn":{"m":"32px","l":"36px","s":"28px","xl":"42px","xs":"24px","xxl":"48px","xxs":"20px"},"btn-fs":{"m":"14px","l":"16px","s":"12px","xl":"18px","xs":"10px","xxl":"20px","xxs":"10px"},"btn-pd":{"m":"14px","l":"16px","s":"12px","xl":"20px","xs":"10px","xxl":"24px","xxs":"8px"},"btn-rd":{"m":"8px","l":"9px","s":"7px","xl":"10px","xs":"6px","xxl":"12px","xxs":"5px"},"shadow":{"m":"8px","l":"12px","s":"4px"},"bd":{"m":"1px"},"blur":{"m":"8px"}},"vars":{"font":{"fml":{"default":"-apple-system,BlinkMacSystemFont,\'Segoe UI\',\'PingFang SC\',\'Microsoft Yahei\',Helvetica,Arial,sans-serif,\'Apple Color Emoji\',\'Segoe UI Emoji\'","code":"\'Cascadia Code\', \'Consolas\', \'Courier New\', \'Pingfang SC\', \'Microsoft Yahei\', monospace","fangsong":"\'Times New Roman\', \'仿宋\'","yahei":"\'微软雅黑\', \'Microsoft Yahei\'","songti":"\'Times New Roman\', \'宋体\'"}},"ani":{"dura":"0.3s"}},"extra":{"color":{"alias":{"danger":"red","warn":"yellow","success":"green","primary":"blue","info":"gray"},"types":["primary","danger","warn","success","info"],"effects":["normal","fill","plain","popout"]},"size":{"sizeStrMap":{"huge":"xxl","large":"xl","medium":"l","normal":"m","small":"s","mini":"xs","tiny":"xxs"},"extraSizeQueue":{"icon":[48,54,64,72,88,96,128],"btn":[54,64,72,88,96,128]},"shapes":["normal","pill","circle","sharp"],"stretches":["square","normal","row"],"tightnesses":["loose","normal","tight"]},"vars":[]}}'),
    },
}
let autoDarkMode = cgy.localStorage.$getJson('service.ui', 'theme.autoDarkMode', true);
let inDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
if (!autoDarkMode) inDarkMode = cgy.localStorage.$getJson('service.ui', 'theme.inDarkMode', false);
Vue.service.options.ui.theme.autoDarkMode = autoDarkMode;
Vue.service.options.ui.theme.inDarkMode = inDarkMode;
Vue.service.options = cgy.extend(Vue.service.options, {nav:{pages:[], page:{}}});


