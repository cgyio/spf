/*
 *  DommyJS框架子模块
 *  DommyJS.Router  路由模块
 */

/**** router前端路由 ****/
(function(window, dj, undefined){
    //写入预设参数，可在dj.init.run()参数中定制
    dj.setDefault({
        router : {
            modulename : 'Router',    //子模块name
            required : false,    //是否必需子模块，默认否
            enabled : false,     //模块当前是否激活，默认否

            ruleobj : {
                hashpath : null,
                template : null,
                title : null,
                exec : null,    //路由方法，每次hash加载时都执行
                onload : null,    //路由方法，仅首次hash加载时执行
                loaded : false
            },

            idpre : 'DP_PAGE_',
            pageshift : false,
            shiftani : {
                dura : 200,
                easing : 'easeOutQuad'
            },
            dfthash : '',   //当hash为空时，默认跳转的目标hash
        }
    });

    var router = {
        rule : {},
        container : {
            current : null,
            temp : null
        },
        hash : null,
        hashstuck : [],     //已加载的页面hash队列
        hashindex : -1,     //当前页面在hash队列中的序号
        hashistory : [],    //hash历史记录
        cache : {
            container : null
        },
        callbacks : [],     //响应hashchange时的函数队列
    };

    /**
     * 路由规则说明
     *  dj.router.rule = {
     *      //默认根路由
     *      _default_ : {
     *          hashpath : '',            //hash路径
     *          template : '_default_',   //内容模板容器id，不指定则默认按照rule数组xpath
     *          exec : function(){ },     //路由方法
     *          loaded : false            //此路由规则是否已经加载
     *      },
     *      index : {
     *          _default_ : {template, exec},   //hash=#/index[/...]
     *          action1 : {
     *              _default_ : {template, exec},   //hash=#/index/action1[/...]
     *              method1 : {
     *                  _default_ : {template, exec},   //hash=#/index/action1/method1[/...]
     *              },
     *              method2 : {
     *                  _default_ : {template, exec}   //hash=#/index/action1/method2[/...]
     *                  param : {
     *                      _default_ : {template, exec}   //hash=#/index/action1/method2/param[/...]
     *                  }
     *              },
     *              ...
     *          },
     *          ...
     *      },
     *      ...
     *  }
     */
    //设置默认hash
    router.setdefault = function(hashpath){
        dj.option.router.dfthash = hashpath;
    };
    //新建路由规则
    router.addrule = function(hashpath, ruleobj){
        if(dj.is.String(hashpath)){
            var ro = $opt('router/ruleobj');
            ro.hashpath = hashpath;
            if(dj.is.Function(ruleobj)){
                ro.exec = ruleobj
            }else if(dj.is.Object(ruleobj)){
                ro = dj.extend(ro,ruleobj);
            }
            hashpath = hashpath=='' ? '_default_' : hashpath+'/_default_';
            var _r = dj.fn.array.xpath(router.rule, hashpath, ro);
            router.rule = dj.extend({},_r);
        }else if(dj.is.Object(hashpath) && !dj.is.empty(hashpath)){
            router.rule = dj.extend(router.rule, hashpath);
        }
    };
    //编辑路由规则
    router.editrule = function(hashpath, ruleobj){
        var ro = router.$rule(hashpath);
        if(dj.is.Null(ro)){return false;}
        var hashpath = ro.hashpath=='' ? '_default_' : ro.hashpath+'/_default_';
        ro = dj.extend(ro, ruleobj);
        var r = dj.fn.array.xpath(router.rule, hashpath, ro);
        router.rule = dj.extend({},r);
    };
    //查找路由规则
    router.$rule = function(hashpath){
        if(!dj.is.String(hashpath)){
            hashpath = dj.url.hash().join('/');
        }
        var _r = router.rule;
        if(hashpath=='' || hashpath=='_default_'){
            if($is.set(_r._default_)){
                return _r._default_;
            }
            return null;
        }
        if(hashpath.indexOf('/')<0){
            var r = dj.fn.array.xpath(_r,hashpath+'/_default_');
            if(dj.is.Null(r)){return router.$rule('');}
            return r;
        }else{
            var harr = hashpath.split('/');
            var r = null;
            for(var i=harr.length-1;i>=0;i--){
                r = dj.fn.array.xpath(_r,harr.join('/')+'/_default_');
                if(dj.is.Null(r)){
                    harr.pop();
                }else{
                    break;
                }
            }
            return r;
        }
    };
    //执行路由规则
    router.exec = function(rule){
        var hasharr = dj.url.hash();
        var hashpath = hasharr.join('/');
        var _hasharr = rule.hashpath.split('/');
        //获取可能存在的hash参数
        if(hasharr.length>_hasharr.length){
            params = hasharr.slice(_hasharr.length);
        }else{
            params = [];
        }
        if(router.hash == rule.hashpath){   //要跳转的路由规则与当前路由规则一致
            if(rule.hashpath==hashpath){    //非下级路由（带参数的），表示两次路由hash完全一样，不做操作
                return false;
            }else{      //要跳转的路由规则是当前路由规则的下级
                //检查是否存在模版文件
                var tempbox = router.$tempbox(hashpath, false);
                if(tempbox.length>0){
                    //存在模板文件，则将模板内容写入当前页面指定容器中(#DP_PAGE_INNER_hash_hash)
                    $('#'+$opt('router/idpre')+'INNER_'+params.join('_')).html(tempbox.html());
                }
                //不存在模板则，调用路由规则方法
                router.doexec(rule, params);
            }
        }else{  //应用新路由规则
            var hashidx = router.hashstuck.indexOf(rule.hashpath);
            if(hashidx<0){    //此hash未加载过
                if(router.hashindex<router.hashstuck.length-1){
                    router.hashstuck.splice(router.hashindex);
                }
                router.hashstuck.push(rule.hashpath);
                router.hashindex = router.hashstuck.length-1;
                router.showtemp(rule,'go',function(){
                    router.doexec(rule, params);
                });
            }else{
                var shifttype = router.hashindex<=hashidx ? 'go' : 'back';
                router.hashindex = hashidx;
                router.showtemp(rule,shifttype,function(){
                    router.doexec(rule, params);
                });
            }
        }
    };
    //处理并显示模板内容
    router.showtemp = function(rule, shifttype, callback){
        var temp = rule.template==null ? rule.hashpath : rule.template;
        var tb = router.$tempbox(temp);
        //更换页面标题
        var ptit = $is.String(rule.title) && rule.title!='' ? rule.title : tb.attrDt('title','','router');
        $fn.dom.title(ptit);
        //显示页面
        $(window).scrollTop(0);
        if($opt('router/pageshift')==false){      //不采用页面转换效果
            tb.childto(router.container.current);
            if(dj.is.Function(callback)){return callback();}
        }else{  //采用页面转换效果
            shifttype = dj.is(shifttype,'String') && ['go','back'].indexOf(shifttype.toLowerCase())>=0 ? shifttype.toLowerCase() : 'go';
            var sopt = $opt('router/shiftani'),
                rcto = router.container;
            if(shifttype=='go'){
                tb.childto(rcto.temp);
                rcto.temp.css({display:''}).animate({left:0},sopt.dura,sopt.easing,function(){
                    //缓存当前页面
                    if(router.hash!=null){router.cacheit(router.hash,'current');}
                    //保存当前hash
                    router.hash = rule.hashpath;
                    rcto.temp.childto(rcto.current);
                    rcto.temp.css({left:screen.width,display:'none'});
                    if(dj.is.Function(callback)){return callback();}
                });
            }else if(shifttype=='back'){
                rcto.current.childto(rcto.temp);
                rcto.temp.css({left:0,display:''});
                tb.childto(rcto.current);
                rcto.temp.animate({left:screen.width},sopt.dura,sopt.easing,function(){
                    //缓存当前页面
                    router.cacheit(router.hash,'temp');
                    //保存当前hash
                    router.hash = rule.hashpath;
                    rcto.temp.css({display:'none'});
                    if(dj.is.Function(callback)){return callback();}
                })
            }
        }
        
    };
    //执行rule.exec/rule.onload方法，然后执行router.callbacks函数序列
    router.doexec = function(rule, params){
        if(rule.loaded===false){
            if(dj.is.Function(rule.onload)){rule.onload.apply(dj,params);}
        }
        if(dj.is.Function(rule.exec)){
            rule.exec.apply(dj,params);
        }
        if(rule.loaded===false){router.editrule(rule.hashpath,{loaded:true});}
        router.runcallback();
    };
    //查找页面模板容器
    router.$tempbox = function(hashpath, create){
        hashpath = hashpath=='' ? '_default_' : hashpath;
        create = $is.Boolean(create) ? create : true;
        var harr = hashpath.split('/');
        var id = $opt('router/idpre')+harr.join('_');
        var box = dj.$(id);
        if(box.length<=0){
            return create==true ? $('<div class="-dp-page-cache" id="'+id+'"></div>').appendTo('body') : box;
        }else{
            return box;
        }
    };
    //将页面内容缓存到模板容器
    router.cacheit = function(hashpath, from){
        hashpath = !$is.set(hashpath) || dj.is.Null(hashpath) ? (router.hash=='' ? '_default_' : router.hash) : hashpath;
        hashpath = hashpath=='' ? '_default_' : hashpath;
        from = !dj.is.String(from) ? 'current' : (Object.keys(router.container).indexOf(from)<0 ? 'current' : from);
        if(dj.is(hashpath,'Null')){return;}
        if(dj.is(hashpath,'String')){
            var cbox = router.$tempbox(hashpath);
            //写入
            router.container[from].childto(cbox);
        }
    };
    //添加hashchange响应方法
    router.addcallback = function(func){
        if(dj.is.Function(func)){
            router.callbacks.push(func);
        }
    };
    router.runcallback = function(){
        var cbs = router.callbacks;
        for(var i=0;i<cbs.length;i++){
            if(dj.is.Function(cbs[i])){
                cbs[i]();
            }
        }
    };
    //解析hash，查找规则，并应用规则
    router.parse = function(){
        var hasharr = dj.url.hash();
        var hashpath = hasharr.join('/');
        
        if(hashpath==''){
            hashpath = $opt('router/dfthash');
            if(hashpath!=''){
                //跳转
                location.hash = '#/'+hashpath;
                return;
            }
        }
        var rule = router.$rule(hashpath);
        if(dj.is.Null(rule)){   //没有相应的路由规则
            return false;
        }else{
            //写入router.hashistory
            if(router.hashistory[router.hashistory.length-1]!=hashpath){
                router.hashistory.push(hashpath);
            }
            //如果nav模块激活，则调用nav.hashchange()
            if(dj.module.isEnabled('nav')){
                $nav.hashchange();
            }
            router.exec(rule);
        }
    };
    
    //初始化
    router.init = function(){
        if(dj.device.support.hashchange()){
            window.onhashchange = router.parse;
            //页面结构初始化
            var opt = $opt('router');
            if($('#'+opt.idpre+'CUR').length<=0){
                $('<div id="DP_PAGE_CUR" class="-dp-page"></div>').appendTo('body');
            }
            if($('#'+opt.idpre+'TMP').length<=0){
                $('<div id="DP_PAGE_TMP" class="-dp-page-temp"></div>').appendTo('body');
            }
            router.container.current = $('#'+opt.idpre+'CUR');
            router.container.temp = $('#'+opt.idpre+'TMP').css({display:'none'});
            
            //解析初始hash
            //router.parse();
        }
    }

    window.DommyJS.Router = windowDommyJS.router = window.$router = router;
})(window, window.DommyJS);