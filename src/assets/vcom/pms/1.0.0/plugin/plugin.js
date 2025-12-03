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
import serviceFoo from 'mixin/service/foo';

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