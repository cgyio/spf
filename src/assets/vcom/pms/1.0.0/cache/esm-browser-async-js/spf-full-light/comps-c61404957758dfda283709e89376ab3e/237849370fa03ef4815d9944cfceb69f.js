import PmsVcomComps from '/src/vcom/pms/1.0.0/async-default.js?create=true&ver=1.0.0&prefix=spf&min=true';



/**
 * 合并资源 plugin.js
 * !! 不要手动修改 !!
 */

/**
 * Vue 2.* 组件库插件
 * SPF-Vcom 组件库插件
 * pms.vcom PMS 系统业务组件库
 */

//!! 业务组件库不需要 引入 cgy 库
//import cgy from '/src/lib/cgy/default.min.js';

//SPF-Vcom 组件库插件的 定义文件
//import globalMethods from 'plugin/global';
//import mixin from 'plugin/mixin';
//import instanceMethods from 'plugin/instance';
//import directive from 'plugin/directive';

const pms = Object.create(null);
pms.install = function(Vue, options = {}) {

    //处理传入的 options 参数，应用其中的 options.service | options.vcoms 等参数
    options = Vue.useInstallOptions(options);
    
}

export default pms;



/**
 * 合并资源 pms.vcom.js
 * !! 不要手动修改 !!
 */


cgy.each(PmsVcomComps, (v, k) => {
Vue.vcoms.async[k] = v;
});










/**
 * 合并资源 temp_theme_info_inject.js
 * !! 不要手动修改 !!
 */




/**
 * 注入此插件使用的 SPF-Theme 主题参数
 * !! 不要手动修改 !!
 */

if (Vue.vcom.list.includes('pms')!==true) {
    Vue.vcom.list.push('pms');
    Vue.vcom.list.pms = {
        name: 'pms',
        isBase: false,
    }
}


