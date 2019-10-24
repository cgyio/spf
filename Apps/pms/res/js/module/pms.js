/*
 *  DommyJS框架子模块
 *  DommyJS.pms   pms系统主界面
 */

(function(window, dj, undefined){
    //基于DommyJS框架子模块定义方式
    dj.module.def('pms',{
        apipre : 'pms/api',
    },{
        foo : 'bar',
        //获取后台数据的api
        api : function(api,querystring){
            api = $opt('pms/apipre')+'/'+api+'?format=json';
            if($is.String(querystring) && querystring!='') api += '&'+querystring;
            return dj.url.local(api);
        },
        sayfoo : function(){
            console.log(dj.pms.foo);
        },



        init : function(){
            dj.log('DommyJS.pms is ready!');
        }
    });
    
})(window, window.DommyJS);