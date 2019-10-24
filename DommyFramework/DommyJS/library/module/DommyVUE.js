/*
 *  DommyJS框架子模块
 *  DommyJS.DommyVUE  vue模块
 */

/**** DommyVUE组织对象，作为一个子模块插入DommyJS框架，必须加载Vue框架 ****/
(function(window, dj, undefined){
    //写入预设参数，可在dj.init.run()参数中定制
    dj.setDefault({
        DommyVUE : {
            modulename : 'DommyVUE',    //子模块name
            required : false,        //是否必需子模块，默认否
            enabled : false,         //模块当前是否激活，默认否
        }
    });

    var dv = {
        //组件集合
        components : [],
        //vue实例集合
        vms : {},
        //状态全局缓存，关联到vue实例的data
        _statu : {},
        $statu : function(key){return dj.fn.array.xpath(dv._statu,key);},
        
        //创建vue实例
        create : function(vmn, opt){
            var el = '#'+vmn;
            if($(el).length>0){
                opt.el = el;
                if(!$is.set(opt.data)) opt.data = {};
                opt.data.vueid = vmn;
                dv.vms[vmn] = new Vue(opt);
                return dv.vms[vmn];
            }
            return null;
        }
    };

    //定义组件
    dv.def = function(compname, opt){
        dv.components.push(compname);
        //预处理
        opt = dj.extend(opt,{
            props: {
                vueid: {
                    type: String,
                    default: ''
                },
                compname: {
                    type: String,
                    default: compname
                }
            },
            computed: {
                idPrefix: function(){
                    return this.vueid+'_'+this.compname.replace
                }
            },
            methods: {

            }
        });
        //other...

        return Vue.component(compname, opt);
    }

    //init
    dv.init = function(){
        dj.log('DommyVUE is ready!');
    }

    window.DommyJS.DommyVUE = window.DommyJS.dommyvue = window.DommyJS.dv = window.DommyVUE = window.dv = window.$dv = dv;
    window.defineVueComponent = dv.def;
    window.createVueObject = dv.create;
})(window, window.DommyJS);
