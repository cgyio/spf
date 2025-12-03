import serviceFoo from 'https://ms.systech.work/src/vcom/pms/1.0.0/mixin/service/foo.js';
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

//业务组件库插件 可引入自己的 服务

//此处通过 修改 Vue.service.* 属性，来启用 业务组件库自有的 服务
Vue.service.support.push('foo');
Vue.service.imports.foo = serviceFoo;

const pms = Object.create(null);
pms.install = function(Vue, options = {}) {

    //处理传入的 options 参数，应用其中的 options.service | options.vcoms 等参数
    options = Vue.useInstallOptions(options);

    let vcomPrefix = 'PRE@';
    
}

export default pms;









/**
 * 合并资源 temp_pms_AeAYO3.js
 * !! 不要手动修改 !!
 */


cgy.each(PmsVcomComps, (v, k) => {
Vue.vcoms.async[k] = v;
});










/**
 * 合并资源 temp_pms_mNtGwW.js
 * !! 不要手动修改 !!
 */




/**
 * 写入组件库 pms 信息
 * !! 不要手动修改 !!
 */

if (Vue.vcom.list.includes('pms')!==true) {
    Vue.vcom.list.push('pms');
    Vue.vcom.pms = {
        name: 'pms',
        isBase: false,
        prefix: 'spf-pms',
    }
}



/**
 * 注入当前 SPA 使用的所有业务组件库中的 页面组件参数
 * !! 不要手动修改 !!
 */

let spaPages = {
    'spf-pms-page-index': JSON.parse('{"isSpaPage":true,"spa":{"sort":1,"pageset":""}}'),
}
cgy.each(spaPages, (v,k) => {
    if (!Vue.service.options.nav.pages.includes(k)) {
        Vue.service.options.nav.pages.push(k);
        Vue.service.options.nav.page[k] = v;
        Vue.service.options.nav.page[k].spaPageName = k;
    }
});


