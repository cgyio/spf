/**
 *	DommyJS通用框架
 *  dj-base基础框架
 *	需要jQuery环境，配合DommyPHP框架使用    /js/dj-base.js
 *	v 2.1.5
 **/



/**** 定义框架核心对象DommyJS ****/
(function(window, undefined){

    var dj = {
        version : '2.1.5',
        //自身代号
        _self : 'dj',
        //默认预设
        _option : {
            debug : true,   //debug开关
            events : [		//定义的事件类型
                'load','ready','error','unload','resize','scroll','focus','blur','focusin','focusout',
                'change','select','submit','keydown','keypress','keyup','toggle','hover',
                'click','dblclick','contextmenu','mouseenter','mouseleave','mouseover','mouseout','mousedown','mouseup',
                'touchstart','touchmove','touchend'
            ],
            vartypes : ['Undefined','Null','Object','Array','Function','Boolean','String','Number'],
            viewport : [640,1366],	//PC/Pad/Mobile分界点
            ajax : {
                url : null,
                option : {
                    cache : false,
                    async : true,
                    processData : true,		//是否自动转换传入的data数据形式
                    dataType : 'json',
                    type : 'GET',	// POST/GET
                    data : {}
                },
                returnData : {  //后台返回数据的默认格式，DommyPHP框架默认输出数据格式
                    _STATU_ : 'SUCCESS',
                    _TITLE_ : 'Undefined Operation',
                    _INFO_ : 'Undefined Operation',
                    _ERRORS_ : [],
                    _ERRORCODE_ : 0,
                    _TPL_ : '',
                    _DATA_ : null,
                    _CALLBACK_ : '',
                    _HTML_ : ''
                },
                errorDataSign : '_ERROR_DATA_'
            },
            until : 100,    //条件回调条件检查时间间隔，毫秒
            url : {
                default : ''
            },
            tpl : {     //预设模板

            },
            notice : {
                dft : {
                    id : '',
                    api : '',   //通知数据后端api url
                    target : null,  //要显示通知数的dom元素
                    exec : null     //此通知dom元素的点击方法
                },
                interval : 5*60*1000  //每次检查通知的时间间隔，毫秒
            }
        },
        option : null,  //运行时设置项
        _cache : {},     //全局缓存
        extend : function(_old, _new){return $.extend(true,{},_old,_new);},
        dump : function(obj){return JSON.stringify(obj);},
        //需要加载blueimp-md5
        md5 : function(s){return dj.is.Function(md5) ? md5(s) : null;},
        //在子模块定义时，将子模块默认预设写入dj._option
        setDefault : function(_opt){
            dj._option = dj.extend(dj._option, _opt);
            return dj._option;
        },
        //生成运行时设置项，dj.init.run()时调用
        set : function(opt){
            dj.option = dj.extend((dj.is(dj.option,'Null') ? dj._option : dj.option), opt);
            return dj.option;
        },
        //读取运行时设置项，dj.option.key
        opt : function(key){
            return dj.fn.array.xpath(dj.option, key);
        },
        //写入、读取全局缓存
        cache : function(key, val){
            if(dj.is.Undefined(val)){
                if(dj.is.String(key)){
                    return key!='' ? dj.fn.array.xpath(dj._cache, key) : null;
                }else if(dj.is.Object(key) && !dj.is.empty(key)){
                    dj._cache = dj.extend(dj._cache, key);
                    return dj._cache;
                }
                return null;
            }else{
                if(dj.is.String(key) && key!=''){
                    dj._cache = dj.fn.array.xpath(dj._cache, key, val);
                    return dj._cache;
                }
                return null;
            }
        },
        //is判断，对象类型判断
        is : function(obj, type){
            return (type === 'Null' && obj === null) || 
			(type === 'Undefined' && obj === void 0 ) || 
			(type === 'Number' && isFinite(obj)) || 
			Object.prototype.toString.call(obj).slice(8,-1) === type;
        },
        //调用jquery构造器
        $ : function(obj){  
            if(dj.is.empty(obj)){return false;}
            if(dj.is.$(obj)){return obj;}
            if(dj.is.dom(obj)){return $(obj);}
            if(dj.is(obj,'String')){
                if(obj.indexOf('#')!=0 && obj.indexOf('.')!=0){
                    if(['html','head','body','window','document'].indexOf(obj)<0){
                        return $('#'+obj);
                    }
                }
                return $(obj);
            }
            if(dj.is.oa(obj)){
                var os = [],
                    o = null;
                for(var i in obj){
                    o = dj.$(obj[i]);
                    if(o!=false){os[i] = o;}
                }
                return dj.is.empty(os) ? false : os;
            }
            return false;
        },
        //条件回调
        until : function(condition, callback){
            var _cidx = -1;
            if(typeof condition == 'number' && callback==undefined){
                _cidx = condition;
                var _l = dj.until._list_[_cidx];
                condition = _l.condition;
                callback = _l.callback;
            }else{
                if(typeof condition == 'function' && typeof callback == 'function'){
                    _cidx = dj.until._list_.length;
                    dj.until._list_.push({
                        condition : condition,
                        callback : callback
                    });
                }
            }
            if(_cidx!=-1){
                if(condition()==true){
                    return callback();
                }else{
                    return setTimeout('window.DommyJS.until('+_cidx+')',dj.option.until);
                }
            }
        },
        //控制台信息输出，仅在 dj.option.debug==true 时有效
        log : function(log){
            if(dj.option.debug==true){
                console.log(log);
            }
        },
        
        //判断前端设备
        device : {
            _uas : {
                Android : "Android",
                iPhone : "iPhone",
                WP : "Windows Phone",
                iPad : "iPad",
                iPod : "iPod"
            },
            _ua : function(){return navigator.userAgent;},
            _check : function(){
                var u = navigator.userAgent;
                var type = null;
                $.each(dj.device._uas, function(i,v){
                    if(u.indexOf(v)>=0){
                        type = i;
                        return false;
                    }
                });
                if(dj.is.Null(type)){
                    var ws = dj._option.viewport;
                    var w = $(window).width();
                    if(w<ws[0]){
                        type = 'Mobile';
                    }else if(w>=ws[0] && w<ws[1]){
                        type = 'Pad';
                    }else{
                        type = 'PC';
                    }
                }
                if(type=='Mobile' && u.indexOf('Linux')>=0){
                    type = 'Android';
                }
                if(u.indexOf('MicroMessenger')>=0){
                    type = dj.is.Null(type) ? 'Wechat' : type+'|Wechat';
                }
                return type;
            },
            is : function(type){
                if(!$is.set(type)){
                    if(dj.device.isMobile()){return 'mobile';}
                    if(dj.device.isPad()){return 'pad';}
                    if(dj.device.isPC()){return 'pc';}
                    return 'unknown';
                }
                type = type.toLowerCase();
                var dtp = dj.device._check();
                var dtpa = dtp.split('|');
                var dtpal = dtp.toLowerCase().split('|');
                switch(type){
                    case 'mobile' :
                        return $.inArray(dtpa[0],['iPad','Pad','PC'])<0;
                        break;
                    case 'pad' :
                        return $.inArray(dtpa[0],['iPad','Pad'])>=0;
                        break;
                    default :
                        return $.inArray(type, dtpal)>=0;
                        break;
                }
            },
            isMobile : function(){return dj.device.is('mobile');},
            isPad : function(){return dj.device.is('pad');},
            isPC : function(){return dj.device.is('pc');},
            isIPhone : function(){return dj.device.is('iphone');},
            isAndroid : function(){return dj.device.is('android');},
            isWechat : function(){return dj.device.is('wechat');},
            //是否全面屏
            isFullScr : function(){return screen.height/screen.width > 16/9;},
            //判断支持功能
            support : {
                hashchange : function(){return typeof window.onhashchange != 'undefined';},
                touchevent : function(){
                    try {
                        document.createEvent("TouchEvent");
                        return true;
                    } catch (e) {
                        return false;
                    }
                }
            }
        },
        //url处理
        url : {
            href : function(u){
                if(dj.is.Undefined(u)){
                    return window.location.href;
                }else{
                    window.location.href = dj.url.local(u);
                }
            },
            host : function(){
                var _href = window.location.href.split("://");
                return _href[0]+'://'+_href[1].split("/")[0];
            },
            local : function(u){
                if(!dj.is(u,'String')){return dj.url.host();}
                if(u.indexOf('://')>=0){return u;}
                var pre = dj.option.url.default;
                pre = dj.is.String(pre) && pre!='' ? dj.url.host()+'/'+pre : dj.url.host();
                if(u.substring(0,1)=='/'){
                    return pre+u;
                }else{
                    return pre+'/'+u;
                }
            },
            uri : function(){
                var _u = window.location.href;
                var _uarr = _u.split('://');
                _uarr = _uarr[1].split('/');
                _uarr.shift();
                var _us = _uarr.join('/');
                if(_us.indexOf('?')>=0){
                    _uarr = _us.split('?');
                    _us = _uarr[0];
                }
                if(_us.indexOf('#')>=0){
                    _uarr = _us.split('#');
                    _us = _uarr[0];
                }
                return '/'+_us;
            },
            hash : function(){
                var hash = window.location.hash;
                if(hash.indexOf('#')<0){
                    return [];
                }else{
                    if(hash.indexOf('#/')<0){
                        hash = hash.replace('#','');
                    }else{
                        hash = hash.replace('#/','');
                    }
                    return hash.split('/');
                }
            },
            hashpath : function(){
                return dj.url.hash().join('/');
            },
            createhash : function(hasharr){
                if(dj.is(hasharr,'Array') && hasharr.length>0){
                    return '#/'+hasharr.join('/');
                }
                if(dj.is(hasharr,'Object') && !jQuery.isEmptyObject(hasharr)){
                    var hs = [];
                    for(var i in hasharr){
                        hs.push(i+'/'+hasharr[i]);
                    }
                    return '#/'+hs.join('/');
                }
                return '';
            }
        },
        //ajax
        ajax : {
            requests : [],			//AJAX加载队列  [{'dtype':'json','data':data},{},...]
            isLoading : false,		//正在加载的标记
            waiting : null,			//等待加载下一条的setTimeout对象
            loadTo : -1,			//加载到队列中的序号，数组序号
            signer : {		//ajax加载标识DOM
                loading : null,
                notice : null,
                isOn : false,
                on : function(callback){
                    if(dj.ajax.signer.isOn==false){
                        dj.ajax.signer.loading = dj.module.isEnabled('weui') ? $weui.toast.on('loading','ajaxloading','加载中') : $ui.loading.on();
                        //dj.ajax.signer.notice = $('<span id="DJ_AJAX_NOTICE" class="-dj-ajax -dj-ajax-notice -bg-gray-3 -gray-5">加载请求 '+a.info()+' ...</span>').appendTo('body');
                        dj.ajax.signer.isOn = true;
                        $callback(callback, dj);
                    }else{
                        //dj.ajax.signer.notice.html('加载请求 '+a.info()+' ...');
                        $callback(callback, dj);
                    }
                },
                off : function(issuccess, callback){
                    var _snr = dj.ajax.signer;
                    if(_snr.isOn==true){
                        if(issuccess===true){
                            //_snr.notice.html('请求 '+a.info()+' 成功');
                            if(dj.ajax.loadTo >= dj.ajax.requests.length-1){
                                //_snr.notice.remove();
                                dj.ajax.signer.isOn = false;
                                _snr.loading.remove();
                                $callback(callback, dj);
                            }else{
                                $callback(callback, dj);
                            }
                        }else if(issuccess===false){	//发生错误
                            /*var _loadnext = '$$.AJAX.loadRequests()';
                            if(dj.ajax.requests.length<=dj.ajax.loadTo+1){
                                _loadnext = '$$.AJAX.signer.off(\'cancel\',$$.AJAX.cancelLoad)';
                            }
                            _snr.loading.removeClass('-bg-cyan').addClass('-bg-red');
                            _snr.notice.removeClass('-bg-gray-3 -gray-5').addClass('-bg-red -white').html('请求 '+dj.ajax.info()+' 出错！<span onclick="$$.AJAX.showErrorRequestData('+dj.ajax.loadTo+')" style="text-decoration:underline; cursor:pointer;">错误信息</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span onclick="$$.AJAX.showErrorRequestData('+dj.ajax.loadTo+',true)" style="text-decoration:underline; cursor:pointer;">数据源</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span onclick="'+_loadnext+'" style="text-decoration:underline; cursor:pointer;">跳过</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span onclick="$$.AJAX.signer.off(\'cancel\',$$.AJAX.cancelLoad)" style="text-decoration:underline; cursor:pointer;">取消</span>');
                            */
                            //输出错误信息到控制台，用于debug
                            dj.log('-=* DommyJS Ajax Error ['+(dj.ajax.loadTo+1)+'/'+dj.ajax.requests.length+'] *=-');
                            dj.log(dj.ajax.getRequestData(dj.ajax.loadTo));
                            //跳过错误，继续下一个请求
                            if(dj.ajax.loadTo >= dj.ajax.requests.length-1){
                                dj.ajax.signer.off('cancel',dj.ajax.cancelLoad);
                            }else{
                                dj.ajax.loadRequests();
                            }
                        }else if(issuccess=='cancel'){
                            dj.ajax.loadTo = dj.ajax.requests.length-1;
                            dj.ajax.signer.off(true,callback);
                        }
                    }else{
                        $callback(callback, dj);
                    }
                }
            },
            //处理url
            url : function(u){
                u = !$is.String(u) ? '' : u;
                if(u.indexOf('://')>=0){
                    return u.indexOf('?')<0 ? u+='?format=json' : (u.indexOf("format=")>0 ? u : u+='&format=json');
                }
                u = u.indexOf('/')==0 ? u.substr(1) : u;
                var opt = $opt('ajax');
                var pre = $is.String(opt.url) && opt.url!='' ? opt.url : '';
                u = pre + ($is.String(u) && u!='' ? '/'+u : '');
                u = dj.url.local(u);
                return u.indexOf('?')<0 ? u+='?format=json' : (u.indexOf("format=")>0 ? u : u+='&format=json');
            },
            parse : function(data){
                var od = $opt('ajax/returnData');	//默认DommyPHP框架返回的json数据格式
                var pd = null;
                if($is.Object(data) && !$is.Undefined(data._STATU_)){
                    pd = dj.extend(od,data);
                }else{
                    pd = od;
                    pd._DATA_ = data;
                }
                if(pd._STATU_=='ERROR'){
                    /*window.DommyJS.ui.dialog.open({
                        type : 'error',
                        title : pd._TITLE_,
                        info : 'ErrorCode&nbsp;&nbsp;&nbsp;<span class="-fw -black">'+parseInt(pd._ERRORCODE_)+'</span><br><br>'+pd._INFO_
                    });*/
                    return $opt('ajax/errorDataSign');
                }else{
                    return pd._DATA_;
                }
            },
            //初始化每次AJAX加载任务的参数
            setPara : function(url, callback, data, options){
                var para = {
                    url : url,
                    callback : callback,
                    isLoaded : false,
                    isSuccess : false,
                    data : /*!dj.is.Object(data) ? {} : */data,
                    options : dj.extend($opt('ajax/option'),(dj.is.Object(options) ? options : {}))
                };
                return para;
            },
		    //加载AJAX请求成功并获得返回数据后，将返回数据保存到request序列中，或者请求失败后，将错误代码存到request序列中
            saveRequest : function(idx, issuccess, data){
                dj.ajax.requests[idx].isLoaded = true;
                dj.ajax.requests[idx].isSuccess = issuccess;
                dj.ajax.requests[idx].responseData = data;
            },
            getRequestData : function(idx){
                var req = dj.ajax.requests[idx];
                if($is.set(req) && !dj.is.Null(req)){
                    return req;
                }
                return null;
            },
            //发生错误时直接加载请求url查看返回错误，用于debug
            showErrorRequestData : function(idx, openurl){
                openurl = openurl==undefined ? false : openurl;
                var _rd = dj.ajax.getRequestData(idx);
                if(_rd!=null){
                    var _u = _rd.url;
                    if(openurl==true){
                        window.open(_u);
                    }else{
                        alert('['+_rd.url+'] '+_rd.responseData);
                    }
                }else{
                    alert('AJAX组件发生未知错误！');
                }
            },
            //按顺序加载队列中的AJAX请求
            loadRequests : function(){
                //var _a = a;
                if(dj.ajax.waiting!=null){
                    try{
                        clearTimeout(dj.ajax.waiting);
                        dj.ajax.waiting = null;
                    }catch(err){};
                }
                
                if(dj.ajax.requests.length <= dj.ajax.loadTo+1){		//队列中无任务或已加载至队列末尾
                    dj.ajax.isLoading = false;
                    dj.ajax.cancelLoad();
                }else{
                    dj.ajax.loadTo++;
                    dj.ajax.loadRequest(dj.ajax.loadTo);
                }
            },
            //加载某条AJAX请求
            loadRequest : function(loadto){
                //var _a = a;
                var reqs = dj.ajax.requests;
                loadto = !dj.is.Number(loadto) ? dj.ajax.loadTo : loadto;
                if(loadto>=0 || loadto<reqs.length){
                    var req = reqs[loadto];
                    dj.ajax.signer.on(function(){
                        var opt = req.options;
                        opt.url = req.url;
                        opt.data = req.data;
                        opt.success = function(data){
                            var d = dj.ajax.parse(data);
                            dj.ajax.saveRequest(loadto,true,d);
                            if(d==$opt('ajax/errorDataSign')){  //返回不正常数据

                            }else{
                                $callback(req.callback, dj, d);
                            }
                            dj.ajax.signer.off(true,function(){
                                dj.ajax.waiting = setTimeout(function(){dj.ajax.loadRequests()},10);
                            });
                        };
                        opt.error = function(XMLHttpRequest, textStatus, errorThrown){
                            dj.ajax.saveRequest(loadto,false,'xhr.statu:'+XMLHttpRequest.statu+'; xhr.readyState:'+XMLHttpRequest.readyState+'; textStatu:'+textStatus);
                            dj.ajax.signer.off(false,function(){
                                
                            });
                        };
                        $.ajax(opt);
                    });
                }
            },
            //取消所有AJAX调用
            cancelLoad : function(){
                if(dj.ajax.waiting!=null){
                    try{
                        clearTimeout(dj.ajax.waiting);
                        dj.ajax.waiting = null;
                    }catch(err){};
                }
                //清空AJAX加载队列
                dj.ajax.requests = null;
                dj.ajax.requests = [];
                dj.ajax.isLoading = false;
                dj.ajax.loadTo = -1;
            },
            //外部调用接口
            load : function(url, callback, data, options){
                url = dj.ajax.url(url);
                //console.log(url);
                //将新的AJAX调用添加到队列中
                var para = dj.ajax.setPara(url, callback, data, options);
                dj.ajax.requests.push(para);
                //如果队列没有运行则运行之
                if(!dj.ajax.isLoading){
                    dj.ajax.loadRequests();
                    dj.ajax.isLoading = true;
                }
            },
            //测试
            debug : function(url, data, options){
                return dj.ajax.load(url, function(d){
                    console.log(dj.ajax.url(url));
                    console.log(data);
                    console.log(options);
                    console.log(d);
                }, data, options);
            }
            
            /*do : function(_url,_success,_error,_opt){
                var _data = _opt==undefined || _opt.data==undefined ? {} : _opt.data;
                dj.ajax.load(_url,_success,_data,_opt);
            }*/

        },
        //子模块
        module : {
            enabled : [],   //当前激活的子模块列表
            isEnabled : function(mod){
                return dj.module.enabled.indexOf(mod)>=0 ? true : $opt(mod+'/enabled')===true;
            }
        },
        //通知中心
        notice : {
            idx : -1,
            list : [	//通知列表
                //{id:'', api:'url', target:'jquery obj', exec:'click method'}
            ],
            checking : null,	//setTimeout对象

            //注册到通知中心
            reg : function(opt){
                opt = dj.extend($opt('notice/dft'), opt);
                if(!$is(opt,'Object') || $is(opt.api,'Undefined') || $is(opt.target,'Undefined')){
                    console.log('无法注册到通知中心，参数错误！[ '+$fn.object.dump(opt)+' ]');
                    return false;
                }else{
                    dj.notice.idx++;
                    opt.id = 'notice_'+dj.notice.idx;
                    opt.target = dj.$(opt.target);
                    if(!$is(opt.exec,'Function')){
                        opt.exec = dj.notice.exec;
                    }
                    //准备dom
                    opt.target.addClass('-dp-notice-off').append('<span class="-dp-notice-sign">0</span>');
                    //注册
                    dj.notice.list.push(opt);
                    //注册后立即检查一下
                    dj.notice.checkByIdx(dj.notice.list.length-1);
                    return dj.notice.list.length-1;
                }
            },
            //注销通知，按idx
            unregByIdx : function(idx){
                if(!$is.empty(dj.notice.list[idx])){
                    var tg = dj.notice.list[idx].target;
                    $('.-dp-notice-sign',tg).remove();
                    tg.removeClass('-dp-notice-on').removeClass('-dp-notice-off');
                    dj.notice.list[idx] = null;
                }
            },
            //注销通知，按target
            unregByTarget : function(target){
                var tg = dj.$(target);
                if(!$is.$(tg)){
                    return false;
                }
                var ns = dj.notice.list;
                var nt = -1;
                for(var i=0;i<ns.length;i++){
                    if(!$is.empty(ns[i]) && ns[i].target.attr('id')==tg.attr('id')){
                        nt = i;
                        break;
                    }
                }
                dj.notice.unregByIdx(nt);
            },
            //启动通知检查
            start : function(){
                //if(dj.notice.list.length>0){
                    dj.notice.check();
                //}
            },
            //停止通知检查
            stop : function(){
                if(dj.notice.checking!=null){
                    try {
                        clearTimeout(dj.notice.checking);
                    }catch(err){}
                }
                dj.notice.checking = null;
            },
            //检查通知
            check : function(){
                if(dj.notice.list.length>0){
                    var ns = dj.notice.list;
                    var nt = null;
                    for(var i=0;i<ns.length;i++){
                        dj.notice.checkByIdx(i);
                    }
                }
                dj.notice.stop();
                dj.notice.checking = setTimeout(window.DommyJS.notice.check, $opt('notice/interval'));
            },
            //检查某一个通知
            checkByIdx : function(idx){
                var nt = dj.notice.list[idx];
                if(!$is.empty(nt)){
                    $.ajax({
                        url : dj.ajax.url(nt.api),  //dj.url.local($opt('ajax/url')+'/'+nt.api+'?format=json'),
                        dataType : 'json',
                        success : function(d){
                            if($is.set(d._DATA_)){
                                dj.notice.show(idx, d._DATA_);
                            }
                        }
                    });
                }
            },
            //显示通知
            show : function(idx, data){
                var nt = dj.notice.list[idx];
                if($is.empty(nt)){return false;}
                var tg = nt.target;
                var n = parseInt(data);
                if(isNaN(n)){return false;}
                if(n<=0){
                    tg.removeClass('-dp-notice').removeClass('-dp-notice-on').addClass('-dp-notice-off');
                }else{
                    tg.removeClass('-dp-notice').removeClass('-dp-notice-off').addClass('-dp-notice-on');
                }
                $('.-dp-notice-sign',tg).html(n);
            }
        },
        //print，需要jq.PrintArea.js
        print : {
            el : function(el){
                el = dj.$(el);
                el.printArea();
            }
        },
        //eventhandler  ???
        evt : {
            handler : {},
            triggers : {},
            defaultTarget : function(evt){
                if(evt=='resize'){
                    return $(window);
                }else if(['load','ready','contextmenu'].indexOf(evt)>=0){
                    return $(document);
                }else{
                    return $('body');
                }
            },
            reg : function(evt, func){
                if($opt('events').indexOf(evt)>=0){
                    if(dj.is(dj.evt.handler[evt],'Undefined')){
                        dj.evt.handler[evt] = [];
                        dj.evt.triggers[evt] = function(event){
                            dj.evt.trigger(evt,event);
                            //阻止冒泡
                            return false;
                        }
                    }
                    if(dj.is(func,'Function')){
                        dj.evt.handler[evt].push(func);
                    }
                }
            },
            attach : function(){
                var evts = $opt('events'), evn = '', evt = null;
                for(var i=0;i<evts.length;i++){
                    evn = evts[i];
                    evt = dj.evt.handler[evn];
                    if(!dj.is(evt,'Undefined') && !dj.is.empty(evt)){
                        dj.evt.defaultTarget(evn).on(evn, dj.evt.triggers[evn]);
                    }
                }
            },
            trigger : function(evt, event){
                var funcs = dj.evt.handler[evt];
                for(var j=0;j<funcs.length;j++){
                    funcs[j](event);
                }
            },
            detach : function(){
                var evts = $opt('events'), evn = '', evt = null;
                for(var i=0;i<evts.length;i++){
                    evn = evts[i];
                    evt = dj.evt.handler[evn];
                    if(!dj.is(evt,'Undefined') && !dj.is.empty(evt)){
                        dj.evt.defaultTarget(evn).off(evn);
                    }
                }
            },
            init : function(){
                //屏蔽右键
                dj.evt.reg('contextmenu',function(event){console.log(event);});
                //touch
                //dj.evt.reg('touchstart',function(event){console.log(event);});
                //resize事件
                ui.resize = {
                    funcs : [],
                    reg : function(func){
                        if(dj.is(func,'Function')){
                            ui.resize.funcs.push(func);
                        }
                    },
                    trigger : function(){
                        for(var i=0;i<ui.resize.funcs.length;i++){
                            ui.resize.funcs[i]();
                        }
                    }
                };
                dj.evt.reg('resize',function(event){ui.resize.trigger();});
                //注册到DommyJS启动函数序列
                d.init.reg(function(){dj.evt.attach();}); 
            }
        },
        //resize全局回调
        resize : {
            funcs : [],
            reg : function(func){
                if(dj.is.Function(func)){
                    dj.resize.funcs.push(func);
                }
            },
            run : function(){
                var funcs = dj.resize.funcs;
                if(funcs.length<=0){return false;}
                for(var i=0;i<funcs.length;i++){
                    funcs[i]();
                }
            }
        },
        //模板替换
        tpl : {
            //支持xpath
            set : function(tplname, str){
                var tplo = {};
                if(dj.is(tplname,'String') && dj.is(str,'String')){
                    //tplo[tplname] = str;
                    tplo = $fn.array.xpath({},tplname,str);
                }else if(dj.is(tplname,'Object') && !dj.is.empty(tplname)){
                    tplo = tplname;
                }
                dj.set({tpl:tplo});
            },
            parsestr : function(str, opt){
                if(dj.is(str,'String') && dj.is(opt,'Object')){
                    str = str.replace(/\$\{([\w\/]+)\}/g, function(match, key, value){
                        var optstr = $fn.array.xpath(opt, key);
                        return $is.String(optstr) || $is.Number(optstr) ? optstr : '';
                    });
                    return str;
                }
                return '';
            },
            parse : function(tplname, opt){   //处理template string
                var tplstr = $opt('tpl/'+tplname);
                return dj.tpl.parsestr(tplstr,opt);
                /*if(dj.is(tplstr,'String') && dj.is(opt,'Object')){
                    tplstr = tplstr.replace(/\$\{(\w+)\}/g, function(match, key, value){
                        if(!dj.is(opt[key],'Undefined')){
                            return opt[key];
                        }else{
                            return '';
                        }
                    });
                    return tplstr;
                }
                return '';*/
            }
        },
        //队列执行，按for循环顺序执行，函数可自带一个参数
        queue : {
            list : {},
            thisobj : {},
            runto : {},
            push : function(qname, func, thisobj){
                if(dj.is(func,'Function')){
                    if(!dj.is(dj.queue.list[qname],'Array')){
                        dj.queue.list[qname] = [];
                        dj.queue.thisobj[qname] = [];
                        dj.queue.runto[qname] = -1;
                    }
                    dj.queue.list[qname].push(function(lastresult){
                        var result = func.call(this,lastresult);
                        return dj.queue.next(qname, result);
                    });
                    dj.queue.thisobj[qname].push(dj.is(thisobj,'Undefined') ? window.DommyJS : thisobj);
                }
                return dj.queue;
            },
            next : function(qname, result){
                if(!$is.Array(dj.queue.list[qname]) || dj.queue.list[qname].length<=0){return false;}
                if(dj.queue.runto[qname]+1>=dj.queue.list[qname].length){
                    dj.queue.reset(qname);
                    return result;
                }else{
                    dj.queue.runto[qname]++;
                    var _thisobj = dj.queue.thisobj[qname][dj.queue.runto[qname]];
                    return dj.queue.list[qname][dj.queue.runto[qname]].call(_thisobj, result);
                }
            },
            run : function(qname, startdata){
                return dj.queue.next(qname, startdata);
            },
            del : function(qname){delete dj.queue.list[qname];delete dj.queue.thisobj[qname];delete dj.queue.runto[qname];},
            reset : function(qname){dj.queue.runto[qname] = -1;}
        },
        //队列执行，以callback回调链式执行，每个函数可自带参数，但是第一个参数必须为回调函数
        //必须在队列函数内部显式调用callback()
        queuecb : {
            list : {},
            thisobj : {},
            runto : {},
            push : function(qname, func, thisobj){
                if($is.Function(func)){
                    if(!$is.Array(dj.queuecb.list[qname])){
                        dj.queuecb.list[qname] = [];
                        dj.queuecb.thisobj[qname] = [];
                        dj.queuecb.runto[qname] = -1;
                    }
                    dj.queuecb.list[qname].push(function(){
                        var args = Array.prototype.slice.call(arguments);
                        args.unshift(function(){
                            var _args = Array.prototype.slice.call(arguments);
                            _args.unshift(qname);
                            //console.log(_args);
                            //dj.queuecb.next(qname);
                            dj.queuecb.next.apply(this, _args);
                        });
                        //console.log(args);
                        func.apply(this, args);
                    });
                    dj.queuecb.thisobj[qname].push($is.Undefined(thisobj) ? window.DommyJS : thisobj);
                }
                return dj.queuecb;
            },
            next : function(){
                var args = Array.prototype.slice.call(arguments);
                var qname = args.shift();
                if(!$is.Array(dj.queuecb.list[qname]) || dj.queuecb.list[qname].length<=0){return false;}
                if(dj.queuecb.runto[qname]+1>=dj.queuecb.list[qname].length){
                    dj.queuecb.reset(qname);
                    return true;
                }else{
                    dj.queuecb.runto[qname]++;
                    var _thisobj = dj.queuecb.thisobj[qname][dj.queuecb.runto[qname]];
                    dj.queuecb.list[qname][dj.queuecb.runto[qname]].apply(_thisobj, args);
                }
            },
            run : function(qname){
                return dj.queuecb.next(qname);
            },
            del : function(qname){delete dj.queuecb.list[qname];delete dj.queuecb.thisobj[qname];delete dj.queuecb.runto[qname];},
            reset : function(qname){dj.queuecb.runto[qname] = -1;}
        },
        //全局回调
        callback : {
            list : {},
            thisobj : {},
            run : function(){
                if(arguments.length<=0){return false;}
                if(arguments.length<=1 && dj.is.Boolean(arguments[0])){return false;}
                var args = Array.prototype.slice.apply(arguments);
                var del = dj.is.Boolean(args[0]) ? args.shift() : false;
                var fname = args.shift();
                if(!dj.is.Function(dj.callback.list[fname])){return false;}
                var func = dj.callback.list[fname];
                var thisobj = dj.callback.thisobj[fname];
                if(del===true){
                    delete dj.callback.list[fname];
                    delete dj.callback.thisobj[fname];
                }
                return func.apply(thisobj, args);
            },
            reg : function(fname, func, thisobj){
                if(dj.is(func,'Function')){
                    var f = dj.callback.list[fname];
                    if(dj.is(f,'Undefined')){
                        dj.callback.list[fname] = func;
                        dj.callback.thisobj[fname] = dj.is.Undefined(thisobj) ? dj : thisobj;
                    }
                }
            },
            single : function(){
                var args = Array.prototype.slice.apply(arguments);
                if(args.length<=0){return false;}
                var func = args.shift();
                if(!dj.is.Function(func)){return false;}
                var thisobj = args.length<=0 ? window : args.shift();
                thisobj = dj.is.Null(thisobj) ? window : thisobj;
                return func.apply(thisobj, args);
            }
        },
        //功能函数
        fn : {},
        //DommyJS框架初始化，函数序列
        init : {    
            reg : function(func){   //注册初始化函数，用于各子模块初始化
                if(dj.is(func,'Function')){
                    dj.queue.push('init',func);
                }
                return dj.init;
            },
            run : function(){    //顺序执行已注册的初始化函数
                return dj.queue.run('init', dj.option);
            },
            set : function(opt){    //读取初始化参数，并根据参数设置，执行各子模块的初始化函数
                var _beforeSubInit = dj.init._beforeSubInit,
                    _afterSubInit = dj.init._afterSubInit;
                if(dj.is(opt,'Object') && !dj.is.empty(opt)){
                    if($is.set(opt._beforeSubInit) && dj.is(opt._beforeSubInit,'Function')){
                        _beforeSubInit = opt._beforeSubInit;
                        delete opt._beforeSubInit;
                    }
                    if($is.set(opt._afterSubInit) && dj.is(opt._afterSubInit,'Function')){
                        _afterSubInit = opt._afterSubInit;
                        delete opt._afterSubInit;
                    }
                }else{
                    opt = {};
                }
                if(!dj.is.empty(opt)){dj.set(opt);}
                //子模块初始化函数执行前执行的全局初始化方法
                if(dj.is(_beforeSubInit,'Function')){
                    dj.init.reg(_beforeSubInit);
                }
                //初始化子模块
                var _initfunc = null, _requiredfunc = [], _normalfunc = [];
                for(var i in dj.option){
                    if($is.set(dj[i]) && dj.is(dj[i].init,'Function')){
                        if($is.set(dj.option[i]) && $is.set(dj.option[i].enabled) && dj.option[i].enabled==true){
                            dj.module.enabled.push(i);
                            _initfunc = dj[i].init;
                            if($is.set(dj.option[i].required) && dj.option[i].required==true){
                                _requiredfunc.push(_initfunc);
                            }else{
                                _normalfunc.push(_initfunc);
                            }
                        }
                    }
                }
                _requiredfunc.push.apply(_requiredfunc, _normalfunc);
                for(var i=0;i<_requiredfunc.length;i++){
                    dj.init.reg(_requiredfunc[i]);
                }
                //子模块初始化函数执行完后，再执行的全局初始化方法
                if(dj.is(_afterSubInit,'Function')){
                    dj.init.reg(_afterSubInit);
                }
                dj.init.reg(dj.init._afterAllInit);
            },
            _beforeSubInit : function(){ },
            _afterSubInit : function(){ },
            _afterAllInit : function(){ }
        },
        //初始化入口
        initialize : function(opt, func){
            if($is.Object(opt) && $is.Boolean(opt.shutdown) && opt.shutdown==true){
                $('body').html('系统维护中，请稍后再试！');
                return false;
            }
            dj.init.set(opt);
            if(dj.is.Function(func)){dj.init.reg(func);}
            dj.log('-=* DommyJS Ver.'+dj.version+' Starting Now ... *=-');
            dj.init.run();
        }

    };

    //is方法扩展
    $.each(dj._option.vartypes,function(i,v){
		dj.is[v] = function(obj){return dj.is(obj, v);}
    });
    dj.is.$ = function(obj){return obj instanceof jQuery;}
	dj.is.dom = (typeof HTMLElement === 'object') ? 
		function(obj){return obj instanceof HTMLElement;} : 
		function(obj){return obj && typeof obj === 'object' && obj.nodeType === 1 && typeof obj.nodeName === 'string';};
	dj.is.oa = function(obj){return dj.is(obj,'Object') || dj.is(obj,'Array');}
	dj.is.empty = function(obj){
		if(dj.is(obj,'Undefined') || dj.is(obj,'Null')){return true;}
		if(dj.is(obj,'Array') && obj.length<=0){return true;}
		if(dj.is(obj,'Object')){return jQuery.isEmptyObject(obj);}
		return false;
	}
	dj.is.set = function(obj){return !dj.is(obj,'Undefined');}
	dj.is._strToCondition = function(s, _com){	//字符串解析为条件语句
		if(s.indexOf('()')>=0 || s.indexOf('=')>0 || s.indexOf('<')>0 || s.indexOf('>')>0){
			return [s];
		}
		s = s.replace(/\(/g,'');
		s = s.replace(/\)/g,'');
		var v = 'true';
		if(s.indexOf('!')>=0){
			s = s.replace(/\!/g,'');
			v = 'false';
		}
		if(!dj.is(_com.runtime[s],'Undefined') && dj.is(_com.runtime[s],'Boolean')){
			return [s,'_com.runtime.'+s+' == '+v];
		}else if(dj.is(_com['_is'+s.ucfirst()],'Function')){
			return [s,'_com._is'+s.ucfirst()+'() == '+v];
		}else{
			return [s,'false'];
		}
    }
    dj.is.djmodule = function(key){
        if(dj.is.String(key) && key!=''){
            if(!dj.is.Undefined(dj[key]) && !dj.is.Undefined(dj.option[key]) && dj.is.Boolean(dj.option[key].enabled)){
                return true;
            }
        }
        return false;
    }

    //until条件序列
    dj.until._list_ = [];

    //默认init初始化方法序列函数
    dj.init.reg(function(){     //在全部初始化函数执行之前执行的全局初始化方法
        
    });
    dj.init._beforeSubInit = function(){    //在全部子模块开始初始化前执行，可在应用时通过 initialize()方法参数修改
        
    }
    dj.init._afterSubInit = function(){    //在全部子模块初始化完成后执行，可在应用时通过 initialize()方法参数修改
        
    }
    dj.init._afterAllInit = function(){     //在初始化函数序列全部执行完后执行
        //debug log
        var subs = [];
        $.each(dj.option,function(i,v){
            if(dj.is.djmodule(i) && dj.option[i].enabled==true){subs.push(v.modulename);}
        });
        dj.log('-=* DommyJS components include : '+subs.join(', ')+'. *=-');
        dj.log('-=* DommyJS Ver.'+dj.version+' Successfully Loaded. Enjoy! *=-');
        //响应resize事件
        $(window).on('resize',dj.resize.run);

        //通知中心启动
        //dj.notice.start();
        
        //hash路由器初始解析
        if($opt('router/enabled')==true){
            dj.router.parse();
        }
    }


    window.Dommy = window.DommyJS = window.DJ = window.dj = window.$$ = dj;
    window.$is = dj.is;
    window.$opt = dj.opt;
    window.$ajax = dj.ajax;
    window.$callback = dj.callback.single;

})(window);



/**** fn方法 / 原型扩展 ****/
(function(window, dj, undefined){

    //通用fn方法
    var fn = {
        //dom，页面元素相关功能方法
        dom : {
            //改变网页标题，缓存现有标题
            title : function(tit){
                if(!dj.is.String(tit) || tit==''){return false;}
                var titstuck = dj.cache('pagetitles');
                if(dj.is.Null(titstuck)){
                    titstuck = [];
                }
                titstuck.push($('title').html());
                //写入缓存
                dj.cache('pagetitles',titstuck);
                //改写标题
                $('title').html(tit);
                //$(document).attr('title',tit);
            },
            //将a元素的子元素转移到b元素，a、b均为jquery对象，clone==true时复制a元素的子元素到b
            childto : function(a, b, clone){
                clone = $is.Boolean(clone) ? clone : false;
                var _a = clone==true ? dj.$(a).clone() : dj.$(a);
                b = dj.$(b);
                _a.children().each(function(i,v){
                    b.append($(v));
                });
                if(clone==true){
                    _a.remove();
                }
                return b;
            } 
        },
        //数组
        array : {
            xpath : function(arr, xpath, val){
                if(!dj.is(xpath,'String') || xpath==''){return null;}
                if(dj.is(arr,'Array') || dj.is(arr,'Object')){
                    if(xpath.indexOf('/')<0){
                        if(dj.is(val,'Undefined')){
                            return dj.is(arr[xpath],'Undefined') ? null : arr[xpath];
                        }else{
                            arr[xpath] = val;
                            return arr;
                        }
                    }else{
                        var xps = xpath.split('/');
                        if(dj.is(val,'Undefined')){
                            var _arr = arr;
                            for(var i=0;i<xps.length;i++){
                                if(dj.is(_arr[xps[i]],'Undefined')){
                                    _arr = null;
                                    break;
                                }else{
                                    _arr = _arr[xps[i]];
                                }
                            }
                            return _arr;
                        }else{
                            var _arr = {}, _tarr = {};
                            _arr[xps[xps.length-1]] = val;
                            for(var i=xps.length-2;i>=0;i--){
                                _tarr = _arr;
                                _arr = {};
                                _arr[xps[i]] = _tarr;
                            }
                            arr = dj.extend(arr, _arr);
                            return arr;
                        }
                    }
                }else{
                    return null;
                }
            }
        },
        //字符串处理
        string : {
            ucfirst : function(str){
                str = str.toLowerCase();
                str = str.replace(/\b\w+\b/g, function(word){
                    return word.substring(0,1).toUpperCase()+word.substring(1);
                });
                return str;
            },
            repeat : function(str,count){return new Array(count + 1).join(str);},
            //url参数转为{}
            url2obj : function(u){
                if($is.String(u) && u!=''){
                    var uarr = u.split('&'), uuarr = null;
                    var o = {};
                    for(var i=0;i<uarr.length;i++){
                        uuarr = uarr[i].split('=');
                        o[uuarr[0]] = uuarr[1];
                    }
                    return o;
                }else{
                    return {};
                }
            }
            
        },
        //object
        object : {
            dump : function(obj){return JSON.stringify(obj);},
            obj2url : function(o){
                if($is.Object(o) && !$is.empty(o)){
                    var s = [];
                    for(var i in o){
                        if(!$is.Null(o[i])){
                            s.push(i+'='+o[i].toString());
                        }else{
                            s.push(i+'=');
                        }
                    }
                    return s.join('&');
                }
                return '';
            }
        },
        //数字处理
        number : {
            moneyformat : function(n){
                if($is.Number(n)){
                    if(n==0){return '0.00';}
                    var nx = Math.round(n*100);
                    var ns = ''+nx;
                    var narr = ns.split('');
                    if(narr.length<3){
                        var ln = 3-narr.length;
                        for(var i=0;i<ln;i++){
                            narr.unshift('0');
                        }
                    }
                    narr.splice(narr.length-2,0,'.');
                    return narr.join('');
                }
                return n;
            },
            // 35 -> 0035
            prefix : function(n, dig){
                dig = !$is.Number(dig) || isNaN(parseInt(dig)) ? 2 : parseInt(dig);
                return (Array(dig).join('0') + n).slice(-dig);
            },
        },
        //日期时间处理
        datetime : {
            timestamp : function(ts){
                if($is(ts,'String')){
                    ts = Date.parse(new Date(ts));
                }else{
                    ts = new Date();
                    ts = ts.getTime();
                }
                return ts;
            },
            unixTimestamp : function(ts){
                ts = fn.datetime.timestamp(ts);
                return Math.round(ts/1000);
            },
            todate : function(str){     //数字、字符串转换成日期对象
                if($is(str,'Number')){
                    var dt = new Date();
                    dt.setTime(str);
                    return dt;
                }else if($is(str,'String')){   //2018-05-01 23:15:19
                    return new Date(Date.parse(str.replace(/-/g,"/")));
                }else if($is(str.getFullYear,'Function')){
                    return str;
                }else{
                    return new Date();
                }
            },
            today : function(){     //返回今天的日期 2018-05-10
    
            },
            weekofyear : function(dt){   //获取某个日期在一年中的周数
                dt = fn.datetime.todate(dt);
                var dayms = 24*60*60*1000;
                var dt_f = new Date(dt.getFullYear(),0,1);
                var wd = dt.getDay();
                var wd_f = dt_f.getDay();
                var t_diff = dt.getTime()-dt_f.getTime();
                var wd_f_remain = (7-wd_f)*dayms;
                t_diff = t_diff-wd_f_remain;
                var week_diff = t_diff/(7*dayms);
                return Math.ceil(week_diff)+1;
            },
            timeparse : function(unixtimestamp){
                var ts = unixtimestamp*1000;
                var dt = new Date();
                var pf = fn.number.prefix;
                dt.setTime(ts);
                var rtn = {
                    yy : dt.getFullYear(),
                    y : dt.getFullYear() - parseInt(dt.getFullYear()/100)*100,
                    mm : pf(dt.getMonth()+1,2),
                    m : dt.getMonth()+1,
                    dd : pf(dt.getDate(),2),
                    d : dt.getDate(),
                    hh : pf(dt.getHours(),2),
                    h : dt.getHours(),
                    h_s : '',
                    ii : pf(dt.getMinutes(),2),
                    i : dt.getMinutes(),
                    ss : pf(dt.getSeconds(),2),
                    s : dt.getSeconds(),
                    wd : dt.getDay(),     //星期几
                    wd_s : '周'+(['日','一','二','三','四','五','六'][dt.getDay()]),
                    se : parseInt(dt.getMonth()/3)+1,   //季度
                    wk : fn.datetime.weekofyear(dt),    //周数
                    wk_s : ''
                };
                var tt = new Date();
                var tw = fn.datetime.weekofyear(tt);
                var wdf = tw-rtn.wk;
                switch(wdf){
                    case 0 : rtn.wk_s = '本周';break;
                    case 1 : rtn.wk_s = '上周';break;
                    case 2 : rtn.wk_s = '上上周';break;
                    case -1 : rtn.wk_s = '下周';break;
                    case -2 : rtn.wk_s = '下下周';break;
                    default : rtn.wk_s = '第'+rtn.wk+'周';break;
                }
                if(rtn.h<5){
                    rtn.h_s = '凌晨';
                }else if(rtn.h<8){
                    rtn.h_s = '早上';
                }else if(rtn.h<12){
                    rtn.h_s = '上午';
                }else if(rtn.h<13){
                    rtn.h_s = '中午';
                }else if(rtn.h<19){
                    rtn.h_s = '下午';
                }else if(rtn.h<22){
                    rtn.h_s = '晚上';
                }else if(rtn.h<24){
                    rtn.h_s = '深夜';
                }
                return rtn;
            },
            dateformat : function(unixtimestamp, type){
                var dt = fn.datetime.timeparse(unixtimestamp)
                var dtarr = [
                    dt.yy,
                    dt.mm,
                    dt.dd
                ];
                var tarr = [
                    dt.hh,
                    dt.ii,
                    dt.ss
                ];
                type = !$is.String(type) ? 'default' : type;
                if(type=='dateonly'){
                    return dtarr.join('-');
                }else if(type=='timeonly'){
                    return tarr.join('-');
                }else{
                    return dtarr.join('-')+' '+tarr.join(':');
                }
                
            }
        },

        //其他
        //延时执行
        wait : function(func, time){
            if(dj.is.Function(func)){
                time = dj.is.Number(time) ? time : 0;
                setTimeout(function(){
                    func();
                },time);
            }
        },
    };


    //jq原型扩展
    jQuery.fn.extend({
		hasAttr : function(attr){return typeof($(this).attr(attr)) != 'undefined';},
		attrEx : function(attr, dft){return typeof($(this).attr(attr)) == 'undefined' ? dft : $(this).attr(attr);},
        attrDt : function(attr, dft, pre){	//获取元素的 [pre]-data-xxx 属性
            pre = $is.String(pre) ? pre.toLowerCase() : 'dj';
			attr = pre+'-data-'+attr;
			return $(this).attrEx(attr, dft);
        },
        setAttrDt : function(attr, val, pre){
            pre = $is.String(pre) ? pre.toLowerCase() : 'dj';
            attr = pre+'-data-'+attr;
            $(this).attr(attr, val);
            return val;
        },
		isChildOf : function(jqo){
			jqo = dj.$(jqo);
			if(jqo.is('body')){return true;}
			var flag = false;
			var po = $(this).parent();
			while(po.length>0){
				if(po.get(0) == jqo.get(0)){
					flag = true;
					break;
				}
				po = po.parent();
			}
			return flag;
		},
		toViewportCenter : function(){
			var vp = {w:$(window).width(), h:$(window).height()},
				ss = {w:$(this).width(), h:$(this).height()};
			return $(this).css({
				left : (vp.w-ss.w)/2,
				top : (vp.h-ss.h)/2
			});
        },
        childto : function(jqo, clone){
            $fn.dom.childto($(this), jqo, clone);
            return $(this);
        },
		
		/** 调用css3动画，需要加载animate.css **/
		/*参数  $(selector).ani('effect1 effect2', callback)
		可选动画样式  
		bounce, flash, pulse, rubberBand, shake, swing, tada, wobble, bounceIn, bounceInDown, bounceInLeft, bounceInRight,
		bounceInUp, bounceOut, bounceOutDown, bounceOutLeft, bounceOutRight, bounceOutUp, fadeIn, fadeInDown, fadeInDownBig,
		fadeInLeft, fadeInLeftBig, fadeInRight, fadeInRightBig, fadeInUp, fadeInUpBig, fadeOut, fadeOutDown, fadeOutDownBig,
		fadeOutLeft, fadeOutLeftBig, fadeOutRight, fadeOutRightBig, fadeOutUp, fadeOutUpBig, flip, flipInX, flipInY, flipOutX,
		flipOutY, lightSpeedIn, lightSpeedOut, rotateIn, rotateInDownLeft, rotateInDownRight, rotateInUpLeft, rotateInUpRight,
		rotateOut, rotateOutDownLeft, rotateOutDownRight, rotateOutUpLeft, rotateOutUpRight, hinge, rollIn, rollOut, zoomIn,
		zoomInDown, zoomInLeft, zoomInRight, zoomInUp, zoomOut, zoomOutDown, zoomOutLeft, zoomOutRight, zoomOutUp */
		ani : function(effects, callback){
			var _self = $(this);
			return $(this).addClass('animated '+effects).one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
				setTimeout(function(){
					_self.removeClass('animated '+effects);
				},1000);
				if(typeof callback == 'function'){
					callback.call(_self);
				}
			});
		}
    });

    window.DommyJS.fn = window.$fn = fn;
    window.$xpath = fn.array.xpath;

})(window, window.DommyJS);



/**** plugin插件，需配合DommyPHP框架plugin ****/
(function(window, dj, undefined){
    //写入预设参数，可在dj.init.run()参数中定制
    dj.setDefault({
        plugin : {
            modulename : 'plugin',    //子模块name
            required : true,    //是否必需子模块，默认是
            enabled : true,     //模块当前是否激活，默认是

            idpre : 'DJ_Plugin_',
            api : 'plugin',
            dura : {
                ani : 200,
                show : 2000
            },
            easing : 'easeOutQuad',
        },

        tpl : {
            plugin : {
                loading : '<div id="${id}" class="-dp-ui-loading"><div class="-dp-ui-loading_a"></div><div class="-dp-ui-loading_b"></div><div class="-dp-ui-loading_c"></div><div class="-dp-ui-loading_d"></div></div>',
                ajaxerr : '<div id="${id}" class="-dp-ui-ajaxerr"></div>'
            }
        }
    });

    var plugin = {
        //获取后端api
        $api : function(u){
            var api = dj.url.local('plugin');
            if($is.String(u) && u!=''){
                api += '/'+u;
            }
            if(api.indexOf('?')<0){
                api += '?format=json';
            }else{
                if(api.indexOf('format=')<0){
                    api += '&format=json';
                }
            }
            return api;
        },
        //获取元素plugin-data-xxx属性
        $attr : function(dom, key, dft){
            dom = dj.$(dom);
            return dom.attrDt(key,dft,'plugin');
        }
    };

    /** cityselector，省市县联动下拉列表 **/
    plugin.cityselector = function(sel, startval, final){
        if(!$is.Array(sel) || sel.length!=3){return false;}
        if(!$is.Array(startval) || startval.length!=3){
            startval = ['','',''];
        }
        var fds = ['province','city','district'];
        var err = false, selos = {}, vals = {};
        $.each(fds, function(i,v){
            var selo = dj.$(sel[i]);
            if(selo.length<=0){
                err = true;
                return false;
            }else{
                selos[v] = selo;
                vals[v] = startval[i];
            }
        });
        if(err==true){return false;}
        this.select = selos;
        this.values = vals;
        this.final = $is.Function(final) ? final : function(){ };
        this._setevent();
    };
    plugin.cityselector.selectors = {};     //实例集合
    plugin.cityselector.create = function(selid, sel, startval){
        var csel = new plugin.cityselector(sel, startval);
        if(csel==false){
            return false;
        }
        plugin.cityselector.selectors[selid] = csel;
        plugin.cityselector.selectors[selid].id = selid;
        //初始加载
        plugin.cityselector.selectors[selid].init();
        return plugin.cityselector.selectors[selid];
    }
    plugin.cityselector.$ = function(selid){
        if($is.set(plugin.cityselector.selectors[selid])){
            return plugin.cityselector.selectors[selid];
        }else{
            return false;
        }
    }
    plugin.cityselector.prototype = {
        //附加event处理
        _setevent : function(){
            var _self = this;
            this.select.province.on('change', function(){
                _self.onProvince();
            });
            this.select.city.on('change', function(){
                _self.onCity();
            });
            this.select.district.on('change', function(){
                _self.onDistrict();
            });
        },
        //后台获取数据，写入select
        _setoption : function(type){
            var sel = this.select;
            var val = this.values;
            sel[type].html('');
            var api = 'cityselector/'+type;
            if(type!='province'){
                if(type=='city' && val.province==''){return false;}
                if(type=='district' && val.city==''){return false;}
                api += '?'+(type=='city' ? 'province='+val.province : 'city='+val.city);
            }
            api = plugin.$api(api);
            //dj.log(api);
            $ajax.load(api, function(d){
                //dj.log(d);
                if($is.Array(d) && d.length>0){
                    $('<option value=""'+(d.indexOf(val[type])<0 ? ' selected' : '')+'>--请选择--</option>').appendTo(sel[type]);
                    for(var i=0;i<d.length;i++){
                        $('<option value="'+d[i]+'"'+(d[i]==val[type] ? ' selected' : '')+'>'+d[i]+'</option>').appendTo(sel[type]);
                    }
                }
            })
        },
        //onchange事件
        onProvince : function(){
            var prov = this.select.province.val();
            this.values.province = prov;
            this.values.city = '';
            this.values.district = '';
            this._setoption('city');
            this._setoption('district');
        },
        onCity : function(){
            var city = this.select.city.val();
            this.values.city = city;
            this.values.district = '';
            this._setoption('district');
        },
        onDistrict : function(){
            var dist = this.select.district.val();
            this.values.district = dist;
            if($is.Function(this.final)){
                var val = this.values;
                this.final(val);
            }
        },
        //初始加载
        init : function(){
            var selid = this.id;
            this._setoption('province');
            this._setoption('city');
            this._setoption('district');
        }
    };

    window.DommyJS.plugin = window.$plugin = plugin;
})(window, window.DommyJS);



/**** router前端路由 ****/
(function(window, dj, undefined){
    //写入预设参数，可在dj.init.run()参数中定制
    dj.setDefault({
        router : {
            modulename : 'router',    //子模块name
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

    window.DommyJS.router = window.$router = router;
})(window, window.DommyJS);



/**** db数据库数据表模块 ****/
(function(window, dj, undefined){
    //写入预设参数，可在dj.init.run()参数中定制
    dj.setDefault({
        db : {
            modulename : 'db',    //子模块name
            required : false,    //是否必需子模块，默认否
            enabled : false,     //模块当前是否激活，默认否

            api : {   //数据库操作后端统一接口
                backup : 'db/_fpath_/backup',   //数据库备份
                restore : 'db/_fpath_/restore',   //数据库恢复
                export : 'db/_xpath_/export',           //统一接口
                table : 'db/_xpath_/export/table',      //获取完整的数据表dom
                detail : 'db/_xpath_/export/detail',           //数据记录条目详情获取
                form : 'db/_xpath_/export/form',        //表单获取接口
                filter : 'db/_xpath_/export/filter',    //数据筛选界面接口
                order : 'db/_xpath_/export/order',      //数据排序界面接口
                submit : 'db/_xpath_/export/sendto',    //数据编辑提交接口
                del : 'db/_xpath_/export/del',          //数据删除接口
                prev : 'db/_xpath_',     //接口前缀
            },
            
            query : {      //从数据库接口获取数据的条件对象
                filter : {},
                search : {},
                sort : {},
                pagesize : 20,
                pagecount : 0,
                ipage : 1,
                recordcount : 0,
                currentrscount : 0,
                currentpgcount : 0,
                showmode : 'default',
                rs : {
                    data : null,
                    original : null
                }
            },

            boxidpre : 'DP_DB_',    //数据表容器dom的id前缀

            
        },

        tpl : {
            db : {

            }
        }
    });

    var db = {
        option : {},
        items : {},     //数据表实例集合
    };
    //创建数据库实例，根据db.option
    db.create = function(){
        var opt = db.option;
        if(!$is.empty(opt)){
            for(var i in opt){
                db.items[i] = new db.dbitem(opt[i]);
            }
        }
    };

    //调整xpath参数，在适当的位置插入'table' or 'field'
    db._fixpath = function(xpath){
        if(!$is.String(xpath) || xpath==''){return '';}
        var xarr = xpath.split('/');
        var ln = xarr.length;
        if(ln==2){
            xarr.splice(1,0,'table');
        }else if(ln>2){
            xarr.splice(1,0,'table');
            xarr.splice(3,0,'field');
        }
        return xarr.join('/');
    }
    //获取db.option.xxx
    db.$opt = function(xpath){
        return $fn.array.xpath(db.option, xpath);
    };
    //获取数据库、数据表、字段实例
    db.$ = function(xpath){
        xpath = db._fixpath(xpath);
        if(xpath=='' || xpath.split('/').length>5){return null;}
        return $fn.array.xpath(db.items, xpath);
    };
    //读取、写入缓存
    db.cache = function(xpath, val){
        if(!$is.String(xpath) || xpath==''){return null;}
        xpath = 'db/'+xpath;
        if(!$is.set(val)){
            return dj.cache(xpath);
        }else{
            return dj.cache(xpath, val);
        }
    };
    //执行某个数据表的operation方法
    db.trigger = function(){
        var args = Array.prototype.slice.call(arguments);
        if(args.length<=0){return null;}
        var xpath = args.shift();
        if(!$is.String(xpath) || xpath==''){return null;}
        var xarr = xpath.split('/');
        if(xarr.length<=2){return null;}
        xpath = xarr.splice(0,2).join('/');
        var dbi = db.$(xpath);
        if($is.Null(dbi)){return null;}
        args.unshift(xarr.join('/'));
        return dbi.trigger.apply(dbi,args);
    };

    //调用微信扫码$wx.scanQRCode()
    db.scanQRCode = function(){
        var args = Array.prototype.slice.call(arguments);
        /*if(args.length<=0){return false;}
        var xarr = args[0].split('/');
        if(xarr.length<=2){return false;}
        var dbi = db.$(xarr[0]+'/'+xarr[1]);
        if($is.Null(dbi)){return false;}*/
        return $wx.scanQRCode(function(result){
            /*if($is.Function(dbi.operation.parseQRCode)){
                result = dbi.trigger('parseQRCode',result);
            }*/
            args.splice(1,0,result);
            return db.trigger.apply(db,args);
        });
    }



    /** db/tb/fd类公用的prototype **/
    db.itemproto = {
        //返回父级对象实例
        $fo : function(){
            var xp = this.xpath;
            if(xp.indexOf('/')<0){return db;}
            var xarr = xp.split('/');
            xarr.pop();
            return db.$(xarr.join('/'));
        },
        //返回子对象
        $co : function(xpath){
            if(!$is.String(xpath) || xpath==''){return null;}
            var xp = this.xpath;
            xp += '/'+xpath;
            return db.$(xp);
        },
        //修改属性
        mod : function(key, val){
            if($is.String(key)){
                if(key==''){return this;}
                var co = this[key];
                if(co instanceof (db.tbitem || db.fditem)){return this;}
                this[key] = val;
                var xpath = db._fixpath(this.xpath)+'/'+key;
                db.option = $fn.array.xpath(db.option, xpath, val);
            }else if($is.Object(key)){
                if($is.empty(key)){return this;}
                for(var i in key){
                    this.mod(i, key[i]);
                }
            }
            return this;
        },
        //读取、写入缓存
        cache : function(xpath, val){
            if(!$is.String(xpath) || xpath==''){return null;}
            return db.cache(this.xpath+'/'+xpath, val);
        },
        //将xpath转为下划线形式
        $hashid : function(){
            return this.xpath.replace(/\//g,'_');
        },
        //获取api接口地址
        $api : function(type){
            type = $is.String(type) && type!='' ? type : 'export';
            var api = $opt('db/api/'+type);
            api = api.replace(/_xpath_/g,this.xpath);
            if(this.xpath.indexOf('/')<0){
                api = api.replace(/_fpath_/g,this.xpath);
            }else{
                api = api.replace(/_fpath_/g,this.$fo().xpath);
            }
            return dj.url.local(api);
        },

        /** ui类操作 **/
        //获取容器id
        boxid : function(type){
            type = !$is.String(type) || type=='' ? 'table' : type;
            return $opt('db/boxidpre')+this.xpath.replace(/\//g,'_')+'_'+type;
        },
    };



    /** db item class 数据库类 **/
    db.dbitem = function(opt){
        for(var i in opt){
            if(i=='table'){continue;}
            this[i] = opt[i];
        }
        this.table = {};
        this.xpath = opt.code;
        if(!$is.empty(opt.table)){
            for(var i in opt.table){
                if(!$is.empty(opt.table[i])){
                    opt.table[i].xpath = opt.code+'/'+i;
                    this.table[i] = new db.tbitem(opt.table[i]);
                }
            }
        }
    };
    db.dbitem.prototype = dj.extend(db.itemproto, {
        //备份数据库
        backup : function(callback){
            var api = this.$api('backup');
            console.log(api);
            $ajax.load(api, callback);
        },
        //恢复数据库
        restore : function(callback){
            var api = this.$api('restore');
            console.log(api);
            $ajax.load(api, callback);
        },
        tst : function(){
            console.log(this.code);
        }
    });



    /** tb item class 数据表类 **/
    db.tbitem = function(opt){
        for(var i in opt){
            if(i=='field'){continue;}
            this[i] = opt[i];
        }
        this.field = {};
        if(!$is.empty(opt.field)){
            for(var i in opt.field){
                if(!$is.empty(opt.field[i])){
                    opt.field[i].xpath = opt.xpath+'/'+i;
                    this.field[i] = new db.fditem(opt.field[i]);
                }
            }
        }
        //数据表自定义操作方法
        this.operation = {};
        //将数据表operations预设与method关联
        for(var i in this.operations){
            this.operation[i] = function(){
                //空函数
            }
        }
        //写入默认表操作方法
        this.operation = dj.extend(this.operation, {
            //form
            form : function(){
                this.form.apply(this, arguments);
            },
            //reload
            reload : function(){
                
            },
            //filter
            filter : function(){

            }
        });
    };
    db.tbitem.prototype = dj.extend(db.itemproto, {
        //获取本表所属数据库对象
        $db : function(){return this.$fo();},
        //添加自定义方法
        addoperation : function(m, func){
            if($is.String(m) && m!='' && $is.Function(func)){
                this.operation[m] = func;
            }else if($is.Object(m) && !$is.empty(m)){
                this.operation = dj.extend(this.operation, m);
            }
            return this;
        },
        //执行自定义方法
        trigger : function(){
            var args = Array.prototype.slice.call(arguments);
            if(args.length<=0){return this;}
            var m = args.shift();
            if(!$is.String(m) || m==''){return this;}
            if(m.indexOf('/')<0){
                if($is.Function(this.operation[m])){
                    m = this.operation[m];
                    return m.apply(this, args);
                }
                return this;
            }else{
                var mf = $fn.array.xpath(this.operation,m);
                if($is.Function(mf)){
                    return mf.apply(this, args);
                }else{
                    var marr = m.split('/');
                    //marr.splice(1,0,dj.device.is());
                    var api = this.$api('export')+'/'+marr.join('/');
                    var callback = args.length>0 ? args.shift() : null;
                    var _selfobj = this;
                    if($is.String(callback) && callback!=''){
                        var cbarr = callback.split('/');
                        if(cbarr.length<=1){
                            callback = this.operation[cbarr[0]];
                            if(!$is.Function(callback)){
                                callback = null;
                            }
                        }else{
                            var cbn = cbarr.pop();
                            _selfobj = db.$(cbarr.join('/'));
                            if($is.Null(_selfobj)){
                                callback = null;
                            }else{
                                callback = _selfobj.operation[cbn];
                                if(!$is.Function(callback)){
                                    callback = null;
                                }
                            }
                        }
                    }
                    $ajax.load(api, function(d){
                        args.unshift(d);
                        if($is.Function(callback)){
                            return callback.apply(_selfobj, args);
                        }
                    });
                }
            }
        },
        //执行按钮方法
        triggerbtn : function(){

        },

        /** 获取数据 **/
        //解析数据表数据获取条件，并缓存到dj.cache.db.{tbitem.xpath}.query
        //然后根据条件，从数据库api接口获取数据
        query : function(opt, callback){
            //读取query缓存，不存在则建立
            var query = this.$query();
            //按传入的opt参数修改query条件对象
            if($is.Object(opt) && !$is.empty(opt)){
                //query = dj.extend(query, opt);
                query = this._modquery(query, opt);
            }
            //将修改后的filteropt写入缓存
            this.cache('query',query);
            //加载数据，post方法，将query条件以json格式post到数据库接口
            //var u = dj.url.local($opt('db/api/rs').replace(/_xpath_/g,this.xpath));
            var u = this.$api('table');
            var _self = this;
            var querystring = this._strquery();
            //console.log(JSON.parse(querystring));
            $ajax.load(u, function(d){
                //console.log(d);
                if($is.Object(d) && !$is.empty(d) && $is.set(d.rs) && $is.set(d.rs.data)){
                    //缓存returndata
                    _self._cachequery(d);
                    //显示数据
                    _self.display();
                    if($is.Function(callback)){
                        return callback.call(_self, d);
                    }
                }else{
                    //获取数据出错
                    return _self._queryerror(d);
                }
            },querystring,{
                type : 'POST',
                asnyc : false,
                contentType: "application/json; charset=utf-8" 
            });
        },
        //加载更多，移动端专用
        loadmore : function(){
            var query = this.cache('query');
            var ps = query.pagesize;
            this.query({
                pagesize : parseInt(ps)+parseInt($opt('db/query/pagesize'))
            });
        },
        //筛选数据
        filter : function(opt){
            var _self = this;
            var querystring = '';
            if(!$is.set(opt)){  //不传入筛选条件opt，则显示筛选界面
                querystring = this._strquery();
                $ajax.load(this.$api('filter'), function(d){
                    //console.log(d);
                    if($is.Object(d) && $is.set(d.html)){
                        //缓存
                        _self._cachequery(d);
                        //调用数据表operation方法，显示界面
                        _self.trigger('filter',d);
                    }
                },querystring,{
                    type : 'POST',
                    asnyc : false,
                    contentType: "application/json; charset=utf-8" 
                });
            }else{  //按条件执行筛选
                this.query(opt);
            }
        },
        //修改排序方式
        order : function(opt){
            var _self = this;
            var querystring = '';
            if(!$is.set(opt)){  //不传入条件opt，则显示排序方式选择界面
                querystring = this._strquery();
                $ajax.load(this.$api('order'), function(d){
                    //console.log(d);
                    if($is.Object(d) && $is.set(d.html)){
                        //缓存
                        _self._cachequery(d);
                        //调用数据表operation方法，显示界面
                        _self.trigger('order',d);
                    }
                },querystring,{
                    type : 'POST',
                    asnyc : false,
                    contentType: "application/json; charset=utf-8" 
                });
            }else{  //按条件执行筛选
                this.query(opt);
            }
        },
        //获取query条件，如果不存在则初始化
        $query : function(){
            //读取query缓存，不存在则建立
            var query = this.cache('query');
            if($is.Null(query)){
                //初始化query条件对象
                query = this._dftquery();
                //缓存
                this.cache('query',query);
            }
            return query;
        },
        //将query条件转换为JSON，排除结果集query.rs
        _strquery : function(query){
            query = $is.Object(query) && !$is.empty(query) ? query : this.$query();
            if($is.empty(query)){return '';}
            var _q = dj.extend({}, query);
            delete _q.rs;
            delete _q.html;
            return JSON.stringify(_q);
        },
        //初始化query条件对象
        _dftquery : function(){
            var query = dj.extend({}, $opt('db/query'));
            var sf = this.specialfield;
            query.sort = sf.sort;
            query.search = '';
            if(!$is.empty(sf.filter)){
                for(var i in sf.filter){
                    query.filter[i] = [];
                }
            }
            if(query.showmode=='default'){
                query.showmode = dj.device.is();
            }
            return query;
        },
        //根据opt修改query条件对象
        _modquery : function(query, opt){
            query = $is.Null(query) ? this.$query() : query;
            if($is.Object(opt) && !$is.empty(opt)){
                //处理数组类型的参数，新的覆盖老的，不合并
                if($is.set(opt.filter)){
                    for(var i in opt.filter){
                        if($is.Array(opt.filter[i])){
                            query.filter[i] = opt.filter[i];
                            delete opt.filter[i];
                        }
                    }
                }
                for(var i in opt){
                    if($is.Array(opt[i])){
                        query[i] = opt[i];
                        delete opt[i];
                    }
                }
                query = dj.extend(query, opt);
            }
            return query;
        },
        //将query对象重置为默认
        _resetquery : function(){
            var query = this._dftquery();
            dj._cache.db[this.$fo().code][this.name].query = query;
        },
        //缓存数据接口返回的数据
        _cachequery : function(query){
            var qc = this.cache('query');
            if($is.Null(qc)){
                this.cache('query', query);
            }else{
                delete qc.rs;
                //qc = dj.extend(qc, query);
                qc = this._modquery(qc, query);
                dj._cache.db[this.$fo().code][this.name].query = qc;
            }
            return this;
        },
        //获取数据出错
        _queryerror : function(d){
            console.log(d);
        },

        /** 显示数据表 **/
        display : function(){
            var query = this.$query();
            if($is.String(query.html)){
                //显示数据表
                $router.container.current.html(query.html);
                //页面title
                $fn.dom.title('管理'+this.title);
                //执行display函数序列
                dj.queue.run(this.hashid+'_display');
            }
        },
        //添加到显示数据时的函数序列，for顺序执行
        ondisplay : function(func){
            if($is.Function(func)){
                dj.queue.push(this.$hashid()+'_display', func, this);
            }
            return this;
        },

        /** 显示记录详情 **/
        //显示记录详情
        detail : function(){
            var args = Array.prototype.slice.call(arguments);
            var argstr = args.length<=0 ? '' : '/'+args.join('/');
            var hashid = this.$hashid();
            //var _titfield = this.specialfield.title[0];
            //console.log(_titfield);
            $ajax.load(this.$api('detail')+'/'+dj.device.is()+argstr, function(d){
                console.log(d);
                //显示
                $router.container.current.html(d.html);
                //title
                //$fn.dom.title(d.rs.data[_titfield]);
                //执行detail函数序列
                dj.queue.run(hashid+'_detail', d);
            });
        },
        //添加方法到detail页面回调序列，用于显示记录detail页面后顺序执行
        ondetail : function(func){
            if($is.Function(func)){
                dj.queue.push(this.$hashid()+'_detail', func, this);
            }
            return this;
        },

        /** 显示表单相关 **/
        //显示表单
        form : function(type, id, popup){
            popup = !$is.Boolean(popup) ? popup=='popup' : popup;
            var hashid = this.$hashid();
            type = $is.String(type) && type!='' ? type : 'new';
            id = $is.set(id) ? id : 0;
            var ptit = (type=='new' ? '新建' : '编辑')+this.title+'记录'+(id==0 ? '' : ' ID='+id);
            this._formget(type, id, function(d){
                if(popup){
                    this.trigger('form_popup',d);
                }else{
                    $fn.dom.title(ptit);
                    $router.container.current.html(d.html);
                    //执行form函数序列
                    dj.queue.run(hashid+'_form', d);
                }
            });
        },
        _formget : function(type, id, callback){
            type = $is.String(type) && type!='' ? type : 'new';
            id = $is.set(id) ? id : 0;
            var u = this.$api('form')+'/'+dj.device.is()+'/'+type+(type=='new' ? '' : '/'+id);
            var _self = this;
            $ajax.load(u, function(d){
                //console.log(d);
                if($is.Function(callback)){
                    callback.call(_self, d);
                }
            });
        },
        //添加方法到form页面回调序列，用于显示记录form页面后顺序执行
        onform : function(func){
            if($is.Function(func)){
                dj.queue.push(this.$hashid()+'_form', func, this);
            }
            return this;
        },
        //提交表单前，将表单数据转换为可提交的数据
        formdata : function(form){
            form = $is.String(form) && form!='' ? dj.$(this.boxid(form)) : dj.$(this.boxid('form'));
            var fds = this.fields;
            var d = {}, inp = null;
            for(var i=0;i<fds.length;i++){
                if(fds[i]=='id'){continue;}
                inp = $('*[name="'+fds[i]+'"]',form);
                if(inp.length>0){
                    d[fds[i]] = inp.val();
                }
            }
            //选取 name=formattach_*** 字段
            $('*[name^="formattach"]',form).each(function(i,v){
                v = $(v);
                var n = v.attr('name');
                n = n.replace(/formattach_/g,'');
                d[n] = v.val();
            });
            //console.log(d);
            return $fn.object.obj2url(d);
        },
        //提交表单的方法
        submit : function(type, id, callback){
            type = $is.String(type) && type!='' ? type : 'new';
            id = $is.Number(id) ? id : 0;
            var _self = this;
            var form = dj.$(this.boxid('form'));
            var d = this.formdata();
            var u = this.$api('submit')+'/'+type+(type=='new' ? '' : '/'+id);
            //console.log(u);
            //console.log(d);
            $ajax.load(u, function(d){
                //console.log(d);
                if(d==true){
                    if($is.Function(callback)){
                        callback.call(_self);
                    }
                }
            }, d,{
                type : 'POST'
            });
        },
        //删除记录
        del : function(id, callback){
            if($is.Number(id) && parseInt(id)>0){
                var xpath = this.xpath;
                $ajax.load(this.$api('del')+'/'+id, function(d){
                    if(d==true){
                        if($is.Function(callback)){
                            callback.call($dbi(xpath));
                        }
                    }
                });
            }
        },

        /** 弹出，选取记录界面 **/
        //弹出选取界面
        select : function(opt, callback){
            if($is.String(opt) && opt!=''){
                opt = $fn.string.url2obj(opt);
            }
            opt = dj.extend({
                title : '选取_TBN_',
                toinp : null,
                sels : null,
                multi : 'no',
                type : 'default',
                sk : null,
                linkinp : null,
                skprev : null,
                infotype : 'default',
                attach : null
            }, opt);
            if(!$is.Null(opt.toinp)){
                opt.sels = $('input[name="'+opt.toinp+'"]').val();
            }
            opt.multi = ['yes','no'].indexOf(opt.multi)<0 ? 'no' : opt.multi;
            if(!$is.Null(opt.linkinp)){
                opt.skprev = $('*[name="'+opt.linkinp+'"]').val();
            }
            var optstr = JSON.stringify(opt);
            var api = this.$api('export')+'/select/'+dj.device.is();
            var _self = this;
            //console.log(api);
            //console.log(optstr);
            $ajax.load(api, function(d){
                //console.log(d);
                //显示记录选择界面
                _self.trigger('select_show', d, callback);
            }, optstr, {
                type : 'POST',
                asnyc : false,
                contentType: "application/json; charset=utf-8"
            });
        },
        //根据条件显示记录列表
        selectitems : function(){
            var sp = dj.$(this.boxid('selectpanel'));
            var el = {}, opt = {};
            $.each(['toinp','sels','selstr','multi','type','sk','skprev','infotype'],function(i,v){
                el[v] = $('input[name="select_'+v+'"]',sp);
                var vv = el[v].val();
                opt[v] = !$is.String(vv) || vv=='' ? '' : vv;
            });
            var ib = dj.$(this.boxid('selectpanel_items'));
            var optstr = JSON.stringify(opt);
            var api = this.$api('export')+'/selectitems/'+dj.device.is();
            //console.log(api);
            //console.log(optstr);
            $ajax.load(api, function(d){
                //console.log(d);
                //显示记录列表
                if($is.set(d.html)){
                    ib.html(d.html);
                    el.selstr.val(d.selstr);
                }
            }, optstr, {
                type : 'POST',
                asnyc : false,
                contentType: "application/json; charset=utf-8"
            });
        },
        //选中某个记录条目
        selectdo : function(chkbox, selid, selstr){
            if(!$is.String(selid) || selid=='' || selid==0){return false;}
            var sp = dj.$(this.boxid('selectpanel'));
            var el = {};
            $.each(['multi','sels','selstr','callback','toinp'],function(i,v){
                el[v] = $('input[name="select_'+v+'"]',sp);
            });
            if(el.multi.val()=='yes'){
                var selsa = el.sels.val()=='' ? [] : el.sels.val().split(',');
                var selstra = el.selstr.val()=='' ? [] : el.selstr.val().split(',');
                var idx = selsa.indexOf(selid),
                    stridx = selstra.indexOf(selstr);
                if(chkbox.prop('checked')==false){  //从选取列表中删除
                    if(idx>=0){selsa.splice(idx,1);}
                    if(stridx>=0){selstra.splice(stridx,1);}
                }else{
                    if(idx<0){selsa.push(selid);}
                    if(stridx<0){selstra.push(selstr);}
                }
                el.sels.val(selsa.join(','));
                el.selstr.val(selstra.join(','));
            }else{
                if(chkbox.prop('checked')==false){return false;}
                this.trigger('select_callback',selid,selstr);
            }
        },
        //多选时提交选中记录
        selectmultido : function(){
            var sp = dj.$(this.boxid('selectpanel'));
            var val = {};
            $.each(['sels','selstr'],function(i,v){
                val[v] = $('input[name="select_'+v+'"]',sp).val();
            });
            this.trigger('select_callback',val.sels,val.selstr);
        },

        /** 弹出，快捷编辑界面 **/
        //弹出界面
        fastmod : function(type, opt){
            //console.log(type);
            if(!$is.String(type) || type==''){return false;}
            if($is.String(opt) && opt!=''){
                opt = $fn.string.url2obj(opt);
            }
            opt = dj.extend({
                type : type
            }, opt);
            var optstr = JSON.stringify(opt);
            var api = this.$api('export')+'/fastmod/'+dj.device.is();
            var _self = this;
            //console.log(api);
            //console.log(optstr);
            $ajax.load(api, function(d){
                //console.log(d);
                //显示
                _self.trigger('fastmod_show', d);
            }, optstr, {
                type : 'POST',
                asnyc : false,
                contentType: "application/json; charset=utf-8"
            });
        },
        //提交编辑
        fastmodo : function(type, inps){
            if(!$is.String(type) || type==''){return false;}
            if(!$is.String(inps) || inps==''){return false;}
            var fmp = dj.$(this.boxid('fastmod_'+type));
            var inparr = inps.split(',');
            var dt = {}, notready = false;
            $.each(inparr,function(i,v){
                var inpv = $('*[name="fastmod_'+v+'"]',fmp).val();
                if(inpv==''){
                    notready = true;
                    return false;
                }else{
                    dt[v] = inpv;
                }
            });
            if(notready==true){return false;}
            var api = this.$api('export')+'/fastmodo/'+type;
            var _self = this;
            //console.log(api);
            $ajax.load(api, function(d){
                //console.log(d);
                _self.trigger('fastmod_submit',d);
            },dt,{type:'POST'});
        }

    });



    /** fd item class 数据字段类 **/
    db.fditem = function(opt){
        for(var i in opt){
            this[i] = opt[i];
        }
    };
    db.fditem.prototype = dj.extend(db.itemproto, {
        //获取本字段所属数据表对象
        $tb : function(){return this.$fo();}
    });



    //init
    db.init = function(){
        //根据/res/export/db/settings.js给出的参数赋值db.option，参数已缓存到dj.cache.db
        db.option = dj.extend({},dj.cache('db'));
        //建立缓存，dj.cache.db
        dj._cache.db = {};
        //创建数据库实例
        db.create();
    }



    window.DommyJS.db = window.$db = db;
    window.$dbi = db.$;

})(window, window.DommyJS);



/**** nav导航栏 ****/
(function(window, dj, undefined){
    //写入预设参数，可在dj.init.run()参数中定制
    dj.setDefault({
        nav : {
            modulename : 'nav',    //子模块name
            required : false,    //是否必需子模块，默认否
            enabled : false,     //模块当前是否激活，默认否

            tree : {},      //导航树
            //btns : {},      //导航栏按钮集合
            pos : 'left',   //导航栏方位 left/right/stay，非移动端使用stay
            bgc : 'light',  //导航栏背景 dark/light
            height : 48,    //导航菜单项默认高度，36/48
            width : 255,    //PC端/Pad端导航栏默认宽度
            navobj : {      //默认的navitem对象
                appname : null,
                hashpath : null,
                hashid : null,
                text : null,
                hash : null,
                //exec : null,
                //onload : null,
                //reload : null,
                execobj : null,
                icon : null,
                notice : null,
                subs : null,
                table : [],
                notice : null,
                btns : {},
                tabs : {}
            },
            btnobj : {
                title : null,
                icon : null,
                exec : null
            },
            dftbtn : {
                notice : {
                    icon : 'comment_light',
                    exec : 'notice'
                },
                more : {
                    icon : 'apps',
                    exec : 'morebtns'
                }
            },
            barbtnnum : {
                mobile : 6,
                pad : 10,
                pc : 20
            },

            api : {     //后端数据接口
                page : 'operation/_hashpath_/page',
                notice : 'operation/_hashpath_/notice'    //通知获取
            },

            datatable : {
                pagesize : {
                    mobile : [20],
                    pad : [50,100,500,1000],
                    pc : [50,100,500,1000],
                }
            },

            idpre : 'DP_NAV_',
            dura : {
                ani : 200,
                show : 2000
            },
            easing : 'easeOutQuad',
        },
        tpl : {
            nav : {
                box : '<div id="DP_NAV_BOX" class="-dp-nav-box_${pos}_${bgc}_${height}" style="width:${width};font-size:${fsize};" nav-data-isopen="no"></div>',
                item : '<div id="DP_NAV_ITEM_${nav}" nav-data-hash="${hash}" nav-data-isopen="no" nav-data-issel="no" class="-dp-nav-item"><h3><i><svg class="-dp-icon-symbol" aria-hidden="true"><use xlink:href="#icon-${icon}"></use></svg></i><span>${text}</span><i class="iconfont icon-unfold" style="margin-right:0;"></i></h3><div class="-dp-nav-subox" nav-data-h="${height}"></div></div>',
                link : '<a id="DP_NAV_ITEM_${fnav}_${nav}" href="#/${hash}" class="-dp-nav-link"><i><svg class="-dp-icon-symbol" aria-hidden="true"><use xlink:href="#icon-${icon}"></use></svg></i><span>${text}</span></a>',
                bar : '<div id="DP_NAV_BAR" class="-dp-nav-bar_${bgc}"><i class="iconfont icon-sortlight -dp-nav-openbtn"></i><i class="iconfont icon-back_light -dp-nav-backbtn"></i><i class="-dp-nav-pgico_${device}"><svg class="-dp-icon-symbol" aria-hidden="true"><use xlink:href="#icon-answers"></use></svg></i><span class="-dp-nav-path_${device}"></span><span class="-dp-nav-tabar"></span></div>',
                barbtn : '<a href="${href}" class="-dp-nav-bbtn" nav-data-btncmd="${cmd}" style="font-size:${fsize}px;"><i class="iconfont icon-${icon}"></i></a>'
            }
        }
    });

    var nav = {
        items : {},
        el : {
            bar : null,
            box : null
        },
        acthash : null,     //当前navitem的hashpath
        current : null      //当前的navitem
    };


    //修改tree重建nav
    nav.reset = function(treeo){
        if(arguments.length<=0){return false;}
        if(arguments.length==1){
            var treeo = arguments[0];
            if($is.Object(treeo) && !$is.empty(treeo)){
                treeo = {nav:{
                    tree : treeo
                }};
                dj.set(treeo);
                nav.reload();
            }
        }else if(arguments.length>=2){
            var hashpath = arguments[0];
            var treeo = arguments[1];
            if($is.String(hashpath) && hashpath!=''){
                dj.option.nav.tree = $fn.array.xpath(dj.option.nav.tree,hashpath,treeo);
                nav.reload();
            }
        }
    };

    //创建navitem对象
    nav.createitem = function(callback){
        var tree = $opt('nav/tree');
        for(var i in tree){
            nav.items[i] = new nav._itemobj(tree[i]);
            //nav.items[i].oncreate();
        }
        return $callback(callback, nav);
    };
    //与数据库对象关联
    nav.linkdb = function(callback){

    };
    //创建dom
    nav.createdom = function(callback){
        var opt = $opt('nav');
        var bid = $opt('nav/idpre');
        var keys = Object.keys($opt('nav/navobj'));
        var ismobile = dj.device.isMobile(),
            ispad = dj.device.isPad(),
            ispc = dj.device.isPC();
        var device = ismobile==true ? 'mobile' : (ispad==true ? 'pad' : 'pc');
        //根据device调整pos,height
        if(ispc!=true){
            dj.set({
                nav : {
                    height : 48,
                    pos : opt.pos=='stay' ? 'left' : opt.pos
                }
            });
        }else{
            dj.set({
                nav : {
                    height : 36,
                    pos : 'stay'
                }
            });
        }
        opt = $opt('nav');
        //开始创建navbar
        nav.el.bar = dj.$(bid+'BAR');
        if(nav.el.bar.length<=0){
            nav.el.bar = $(dj.tpl.parse('nav/bar',{
                bgc : opt.bgc,
                device : device
            })).appendTo('body');
        }
        nav.el.openbtn = $('i.-dp-nav-openbtn',nav.el.bar).on('click',function(){nav.toggle();});
        nav.el.backbtn = $('i.-dp-nav-backbtn',nav.el.bar).css({display:'none'}).on('click',function(){$navc().goback();});
        nav.el.path = $('span[class*="-dp-nav-path"]',nav.el.bar);
        nav.el.pgico = $('i[class*="-dp-nav-pgico"]',nav.el.bar);

        //开始创建navbox
        nav.el.box = dj.$(bid+'BOX');
        if(nav.el.box.length<=0){
            nav.el.box = $(dj.tpl.parse('nav/box',{
                pos : opt.pos,
                bgc : opt.bgc,
                height : opt.height,
                width : ismobile==true ? '100vw' : opt.width+'px',
                fsize : ispc==true ? '12px' : (ispad==true ? '14px' : '16px')
            })).appendTo('body');
        }
        
        var items = nav.items;
        var tmpo = {}, tmpsubs = [], tmpso = {};
        for(var i in items){
            tmpsubs = nav.$sub(i);
            nav.el[i] = {};
            tmpo = {
                nav : i,
                text : items[i].text,
                hash : items[i].hash,
                icon : items[i].icon,
                height : tmpsubs.length*opt.height
            };
            nav.el[i].box = $(dj.tpl.parse('nav/item',tmpo)).appendTo(nav.el.box);
            nav.el[i].subox = $('div.-dp-nav-subox',nav.el[i].box);
            for(var j in items[i]){
                if(!nav.isitem(items[i][j])){continue;}
                tmpso = {
                    fnav : i,
                    nav : j,
                    hash : items[i].hash+'/'+items[i][j].hash,
                    text : items[i][j].text,
                    icon : items[i][j].icon
                }
                nav.el[i][j] = $(dj.tpl.parse('nav/link',tmpso)).appendTo(nav.el[i].subox);
            }
            nav.el[i].subox.css({height:0});
        }
        //修改body的padding-top
        $('body').css('padding-top',nav.el.bar.height());
        //router.container.temp
        if(dj.module.isEnabled('router')){
            $router.container.temp.css({
                top : nav.el.bar.height(),
                height : screen.height - nav.el.bar.height()
            });
        }
        return $callback(callback, nav);
    };
    //创建barbtn
    nav.createbtn = function(hash){
        var navo = nav.$item(hash);
        var btns = navo.btns;
        var hashpath = navo.hashpath;
        //先清空
        for(var i in nav.el.barbtn){
            if(dj.is.$(nav.el.barbtn[i])){
                if(i=='notice'){
                    nav.noticebtnunreg();
                }
                nav.el.barbtn[i].remove();
            }
        }
        nav.el.barbtn = {};
        //创建
        //if(dj.is.empty(btns)){return false;}
        var opt = $opt('nav');
        var device = dj.device.is();
        var fs = device=='mobile' ? 24 : 22;
        /**/
        var bi = 0, cmd = null;
        for(var i in btns){
            bi++;
            if(i=='other'){continue;}
            if(bi<=opt.barbtnnum[device]-2){
                cmd = btns[i].exec==null ? i : btns[i].exec;
                nav.el.barbtn[i] = $(dj.tpl.parse('nav/barbtn',{
                    href : 'javascript:'+cmd,
                    cmd : cmd,
                    icon : btns[i].icon,
                    fsize : fs
                })).appendTo(nav.el.bar);
            }else{
                navo.btns.other[i] = btns[i];
            }
        }
        //notice
        if($is.String(navo.notice) && navo.notice!=''){
            nav.el.barbtn.notice = $(dj.tpl.parse('nav/barbtn',{
                href : '#/'+hashpath+'/notice',
                cmd : opt.dftbtn.notice.exec,
                icon : opt.dftbtn.notice.icon,
                fsize : fs
            })).appendTo(nav.el.bar);
            nav.el.barbtn.notice.attr({
                id : $opt('nav/idpre')+navo.hashid+'_notice',
                'nav-data-hashpath' : hashpath
            });
            nav.noticebtnreg();
        }
        //other
        if(!$is.empty(navo.btns.other)){
            nav.el.barbtn.more = $(dj.tpl.parse('nav/barbtn',{
                href : 'javascript:$nav.openbtnsheet(\''+hash+'\')',
                cmd : opt.dftbtn.more.exec,
                icon : opt.dftbtn.more.icon,
                fsize : fs
            })).appendTo(nav.el.bar);
        }

    };
    //根据nav.tree准备router路由规则
    nav.setrouter = function(callback){
        if(dj.module.isEnabled('router')){
            var tree = nav.tree;
            var items = nav.items;
            var dfthash = null;
            var keys = Object.keys($opt('nav/navobj'));
            var subs = null;
            for(var i in items){
                subs = nav.$sub(i);
                for(var j in items[i]){
                    if(!nav.isitem(items[i][j])){continue;}
                    //添加router路由规则
                    dj.router.addrule(items[i].hash+'/'+items[i][j].hash,{
                        title : /*items[i].text+' > '+*/items[i][j].text,
                        exec : function(){
                            nav.dftshow();
                        },
                        onload : function(){
                            nav.dftload();
                        }
                    });
                    //将item.tab添加为router路由规则
                    if(!$is.empty(items[i][j].tabs)){
                        for(var t in items[i][j].tabs){
                            
                            dj.router.addrule(items[i].hash+'/'+items[i][j].hash+'/'+t, {
                                title : dj.tpl.parsestr(items[i][j].tabs[t],items[i][j]),
                                exec : function(){
                                    var hasharr = dj.url.hash();
                                    if(hasharr.length<3){return false;}
                                    var args = Array.prototype.slice.call(arguments);
                                    var item = nav.$item();
                                    var exec = item.tabexec[hasharr[2]];    //navitem.tab路由show方法关联到navitem.tabexec.{method}
                                    //重建navbar中的btns
                                    nav.createbtn(item.hashpath);
                                    //显示backbtn
                                    nav.showbackbtn();
                                    if($is.Function(exec)){
                                        exec.apply(item.$execobj(), args);
                                    }
                                }
                            });
                        }
                    }
                    //写入router默认hash
                    if(dj.is.Null(dfthash)){
                        dfthash = items[i].hash+'/'+items[i][j].hash;
                        dj.router.setdefault(dfthash);
                    }
                }
            }
        }
        return $callback(callback,nav);
    };
    //创建动作响应
    nav.setevent = function(callback){
        //nav item标题栏点击动作
        $('div#'+$opt('nav/idpre')+'BOX > div > h3').on('click', function(){
            var _me = $(this);
            var _nav = _me.parent();
            var _hash = _nav.attrDt('hash',null,'nav');
            var _isopen = _nav.attrDt('isopen','no','nav'),
                _issel = _nav.attrDt('issel','no','nav');
            if(_issel!='yes'){
                if(_isopen=='yes'){
                    nav.disactive(_hash);
                }else{
                    nav.active(_hash);
                }
            }
            
        });

        return $callback(callback, nav);
    };
    //重建nav
    nav.reload = function(callback){
        nav.el.bar.remove();
        nav.el.box.remove();
        nav.el = {
            bar : null,
            box : null
        },
        nav.acthash = null;
        nav.current = null;
        nav.createdom(function(){
            nav.setevent(function(){
                nav.hashchange();
                return $callback(callback, nav);
            });
        });
    };
    //打开actionsheet，显示navitem.btns.other，用于执行自定义的数据库operations操作
    nav.openbtnsheet = function(hash){
        var item = nav.$item(hash);
        if(dj.module.isEnabled('weui')){
            var obtns = item.btns.other;
            var obs = [];
            var opt = {};
            for(var i=0;i<obtns.length;i++){
                opt = {
                    text : obtns[i].title,
                    action : ''
                };
                if(!$is.String(obtns[i].page)){
                    opt.action = 'javascript:'+obtns[i].exec;
                }else{
                    opt.action = '#/'+hash+'/'+obtns[i].page;
                }
                obs.push(opt);
            }
            $weui.menu(obs,'更多数据操作');
        }else{
            console.log('weui not enable');
        }
    };

    //根据xpath获取navitem
    nav.$item = function(hash, prop){
        if(!dj.is.String(hash) || hash==''){
            var hasharr = dj.url.hash();
            if(hasharr.length<2){return null;}
            hash = hasharr[0]+'/'+hasharr[1];
        }
        prop = $is.String(prop) && prop!='' ? prop : null;
        var navo = dj.fn.array.xpath(nav.items,hash);
        return $is.Null(prop) ? navo : ($is.set(navo[prop]) ? navo[prop] : null);
    };
    //判断某个object是navitem
    nav.isitem = function(obj){
        return $is.Object(obj) && $is.set(obj.hash) && $is.set(obj.hashpath);
    };
    //查找某个nav菜单的全部子菜单，返回数组
    nav.$sub = function(hash){
        var item = nav.$item(hash);
        if(dj.is.Null(item)){
            return [];
        }else{
            var keys = Object.keys($opt('nav/navobj'));
            var sub = [];
            for(var i in item){
                if(keys.indexOf(i)>=0){continue;}
                if(!$is.Object(item[i]) || !$is.set(item[i].hash)){continue;}
                sub.push(i);
            }
            return sub;
        }
    };
    //根据hash在nav.el中查找某个item
    nav.$itemel = function(hash){
        if(!dj.is.String(hash) || hash==''){return null;}
        return dj.fn.array.xpath(nav.el,hash);
    };
    //获取后端数据接口
    nav.$api = function(hash, type){
        type = !$is.String(type) || type=='' ? 'page' : type;
        var api = $opt('nav/api');
        if(!$is.set(api[type])){return null;}
        api = api[type];
        api = api.replace(/_hashpath_/g,hash);
        return api;
    };
    //根据hash获取exec方法指向的this
    nav.$execobj = function(hash){
        var itemo = nav.$item(hash);
        if(!$is.Null(itemo)){
            return itemo.$execobj();
        }
        return nav;
    };
    //执行某个item.method，nav.exec(methodhash, p1,p2,...)
    nav.trigger = function(){
        var args = Array.prototype.slice.call(arguments);
        if(args.length<=0){return false;}
        var hash = args.shift();
        var hasharr = hash.split('/');
        if(hasharr.length<=1){return false;}
        var func = hasharr.pop();
        args.unshift(func);
        var item = nav.$item(hasharr.join('/'));
        return item.trigger.apply(item, args);
    }
    //执行navitem的btn方法
    nav.btnexec = function(){
        var args = Array.prototype.slice.call(arguments);
        if(args.length<=0){return false;}
        var hash = args.shift();
        var hasharr = hash.split('/');
        if(hasharr.length<=1){return false;}
        var btncmd = hasharr.pop();
        var item = nav.$item(hasharr.join('/'));
        if($is.Null(item)){return false;}
        var func = item[btncmd];
        if(!$is.Function(func)){return false;}
        return func.apply(item.$execobj(),args);
    }

    //hashchange响应
    nav.hashchange = function(callback){
        var hasharr = dj.url.hash();
        var hashpath = '';
        if(hasharr.length<=0){return false;}
        if(hasharr.length>=2){
            hashpath = hasharr[0]+'/'+hasharr[1];
        }else{
            hashpath = hasharr[0];
        }
        if(hashpath==nav.acthash){return false;}
        if(nav.acthash!=null){
            var actarr = nav.acthash.split('/');
            if(hasharr[0]==actarr[0]){
                nav.disactive(nav.acthash);
                nav.active(hashpath);
            }else{
                nav.disactive(nav.acthash);
                nav.disactive(actarr[0]);
                nav.active(hashpath);
            }
        }else{
            nav.active(hashpath);
        }
        nav.acthash = hashpath;
        nav.current = nav.$item(hashpath);
        nav.close(null, dj.device.isMobile());
    };

    //navbar元素动作
    nav.showbackbtn = function(){
        var opt = $opt('nav');
        nav.el.openbtn.css({display:'none'});
        nav.el.backbtn.css({display:''}).stop(true, false).animate({opacity:1, 'margin-left':0},opt.dura.ani,opt.easing);
    };
    nav.hidebackbtn = function(){
        var opt = $opt('nav');
        nav.el.backbtn.stop(true, false).animate({opacity:0, 'margin-left':-24},opt.dura.ani,opt.easing,function(){
            nav.el.backbtn.css({display:'none'});
            nav.el.openbtn.css({display:''});
        });
    };
    //通知按钮
    nav.noticebtnreg = function(){  //注册到通知中心
        if($is.set(nav.el.barbtn.notice)){
            var hashpath = nav.el.barbtn.notice.attrDt('hashpath','','nav');
            var item = nav.$item(hashpath);
            dj.notice.reg({
                target : nav.el.barbtn.notice,
                api : nav.$api(hashpath, 'notice')+'/'+item.notice
            })
        }
    }
    nav.noticebtnunreg = function(){  //取消注册到通知中心
        if($is.set(nav.el.barbtn.notice)){
            dj.notice.unregByTarget(nav.el.barbtn.notice);
        }
    }
    //开启nav栏
    nav.open = function(callback, noani){
        var opt = $opt('nav');
        noani = $is.Boolean(noani) ? noani : false;
        if(opt.pos=='stay' || nav.el.box.attrDt('isopen','no','nav')=='yes'){
            return $callback(callback, nav);
        }
        if($is.set(nav.el.openbtn)){
            nav.el.openbtn.stop(true,false).animate({left:'-4px'},opt.dura.ani,opt.easing);
        }
        var tocss = {left:0};
        if(noani){
            nav.el.box.css(tocss).setAttrDt('isopen','yes','nav');
            return $callback(callback, nav);
        }else{
            nav.el.box.stop(true,false).animate(tocss,opt.dura.ani,opt.easing,function(){
                nav.el.box.setAttrDt('isopen','yes','nav');
                return $callback(callback, nav);
            });
        }
    };
    //关闭nav栏
    nav.close = function(callback, noani){
        var opt = $opt('nav');
        noani = $is.Boolean(noani) ? noani : false;
        if(opt.pos=='stay' || nav.el.box.attrDt('isopen','no','nav')=='no'){
            return $callback(callback, nav);
        }
        if($is.set(nav.el.openbtn)){
            nav.el.openbtn.stop(true,false).animate({left:0},opt.dura.ani,opt.easing);
        }
        var tocss = opt.pos=='left' ? {left:nav.el.box.width()*-1} : {left:screen.width};
        if(noani){
            nav.el.box.css(tocss).setAttrDt('isopen','no','nav');
            return $callback(callback, nav);
        }else{
            nav.el.box.stop(true,false).animate(tocss,opt.dura.ani,opt.easing,function(){
                nav.el.box.setAttrDt('isopen','no','nav');
                return $callback(callback, nav);
            });
        }
    };
    //开启关闭nav
    nav.toggle = function(callback){
        var isopen = nav.el.box.attrDt('isopen','no','nav');
        if(isopen=='yes'){
            nav.close(callback);
        }else if(isopen=='no'){
            nav.open(callback);
        }
    }
    //激活某个navitem
    nav.active = function(hash, callback){
        if(!dj.is.String(hash) || hash==''){return false;}
        var opt = $opt('nav');
        var el = nav.$itemel(hash);
        if(hash.indexOf('/')<0){
            //关闭其他item
            $('.-dp-nav-item_open').each(function(i,v){
                v = $(v);
                var _hash = v.attrDt('hash',null,'nav');
                if(_hash!=hash && v.attrDt('issel','no','nav')!='yes'){
                    nav.disactive(_hash);
                }
            });
            el.box.removeClass('-dp-nav-item').addClass('-dp-nav-item_open').setAttrDt('isopen','yes','nav');
            $('h3 > i:last',el.box).removeClass('icon-unfold').addClass('icon-fold');
            el.subox.animate({
                height : el.subox.attrDt('h',0,'nav')
            },opt.dura.ani,opt.easing,function(){
                return $callback(callback, nav);
            });
        }else{
            var hasharr = hash.split('/');
            var p = [];
            p.push(nav.$item(hasharr[0]).text);
            p.push(nav.$item(hash).text);
            nav.el.path.html('<strong>'+p[0]+'</strong><i class="iconfont icon-playfill"></i>'+p[1]);
            $('svg',nav.el.pgico).html('<use xlink:href="#icon-'+nav.$item(hash).icon+'"></use>');
            //nav.createbtn(hash);
            if(el.parent().parent().attrDt('isopen','no','nav')=='yes'){
                el.removeClass('-dp-nav-link').addClass('-dp-nav-link_sel');
                el.parent().parent().setAttrDt('issel','yes','nav');
                return $callback(callback, nav);
            }else{
                nav.active(hasharr[0], function(){
                    el.removeClass('-dp-nav-link').addClass('-dp-nav-link_sel');
                    el.parent().parent().setAttrDt('issel','yes','nav');
                    return $callback(callback, nav);
                });
            }
        }
    }
    //取消激活某个navitem
    nav.disactive = function(hash, callback){
        if(!dj.is.String(hash) || hash==''){return false;}
        var opt = $opt('nav');
        var el = nav.$itemel(hash);
        if(hash.indexOf('/')>0){
            el.removeClass('-dp-nav-link_sel').addClass('-dp-nav-link');
            el.parent().parent().setAttrDt('issel','no','nav');
            return $callback(callback, nav);
        }else{
            el.box.removeClass('-dp-nav-item_open').addClass('-dp-nav-item').setAttrDt('isopen','no','nav');
            $('h3 > i:last',el.box).removeClass('icon-fold').addClass('icon-unfold');
            el.subox.animate({
                height : 0
            },opt.dura.ani,opt.easing,function(){
                return $callback(callback, nav);
            });
        }
    }

    //默认onload响应方法
    nav.dftload = function(){
        var hasharr = dj.url.hash();
        var hashpath = hasharr.splice(0,2).join('/');
        var item = nav.$item(hashpath);
        //运行item.onload方法序列，以callback方式
        if($is.Array(dj.queuecb.list[item.hashid+'_load']) && dj.queuecb.list[item.hashid+'_load'].length>0){
            //dj.log('#/'+hashpath+' onload queue is executed');
            dj.queuecb.run(item.hashid+'_load');
        }
    };
    //默认hash响应方法
    nav.dftshow = function(){
        var hasharr = dj.url.hash();
        var hashpath = hasharr.splice(0,2).join('/');
        var item = nav.$item(hashpath);
        //运行item.onshow方法序列，以for循环方式
        if($is.Array(dj.queue.list[item.hashid+'_show']) && dj.queue.list[item.hashid+'_show'].length>0){
            //dj.log('#/'+hashpath+' onshow queue is executed');
            dj.queue.run(item.hashid+'_show');
        }
    };

    //初始化
    nav.init = function(){
        //创建item对象树
        nav.createitem(function(){
            //创建dom
            nav.createdom(function(){
                nav.setrouter(function(){
                    nav.setevent();
                });
            });
        });
        
        //注册到resize事件
        dj.resize.reg(function(){
            //nav.reload();
        });
    }

    /**** nav导航item类 ****/
    nav._itemobj = function(opt){
        var navo = $opt('nav/navobj');
        var btno = $opt('nav/btnobj');
        var keys = Object.keys(navo);
        if($is.Object(opt) && !$is.empty(opt)){
            //写入默认预设
            opt = dj.extend(navo,opt);
            //写入默认属性
            opt.text = $is.Null(opt.text) ? opt.hash : opt.text;
            opt.icon = $is.Null(opt.icon) ? 'answers' : opt.icon;
            opt.hashid = opt.hashpath.replace(/\//g,'_');
            //添加默认tabs
            if($is.empty(opt.tabs)){opt.tabs = {};}
            opt.tabs.page = "查看页面";
            if($is.String(opt.notice) && opt.notice!=''){
                opt.tabs.notice = '查看通知';   //当前navitem的通知页面
            }
            //写入默认btn预设
            opt.btns = dj.extend(opt.btns, {
                other : [],
                reload : dj.extend($opt('nav/btnobj'), {
                    title : '刷新页面',
                    icon : 'refresh',
                    exec : '$navi().reload()'
                })
            });
            //如此navitem包含关联数据表，则将数据表operations写入navitem.tabs以及navitem.btns
            if(dj.module.isEnabled('db') && opt.table.length>0){
                opt.tabs.table = "数据表管理";
                var tb = null, opr = null, pgn = [], oprn = {};
                for(var i=0;i<opt.table.length;i++){
                    tb = dj.db.$(opt.table[i]);
                    for(var j in tb.operations){
                        opr = tb.operations[j];
                        if(!$is.String(opr.page) || opr.page==''){
                            opt.btns.other.push(dj.extend({}, opr));
                        }else{
                            pgn = opr.page.split('/');
                            opt.tabs[pgn[0]] = '';
                            pgn.splice(1,0,''+i);
                            oprn = dj.extend({}, opr);
                            oprn.page = pgn.join('/');
                            opt.btns.other.push(oprn);
                        }
                        
                    }
                }
                opt.tabs = dj.extend(opt.tabs, {
                    detail : '显示记录详情',
                });
            }
            //将经过处理的属性值写入navitem对象
            for(var i=0;i<keys.length;i++){
                this[keys[i]] = opt[keys[i]];
            }
            //其他属性
            this.tabexec = {};
            if(!$is.empty(this.tabs)){
                for(var i in this.tabs){
                    this.tabexec[i] = function(){ }
                }
            }
            //下级navitem
            for(var i in opt){
                if(!nav.isitem(opt[i])){continue;}
                this[i] = new nav._itemobj(opt[i]);
                this[i].oncreate();
            }
        }
    };
    nav._itemobj.prototype = {
        //navitem对象实例建立后执行
        oncreate : function(){
            var _self = this;

            /** 添加load方法到onload方法序列 **/
            this.onload(function(callback){
                //加载navitem关联的html页面，后端api = http//host/{ajaxpre}/{hashpath}/page
                //html页面加载到router.container.current
                $ajax.load(this.$api('page')+'/'+dj.device.is(), function(d){
                    //console.log(d);
                    $router.container.current.html(d.html);
                    callback();
                });
            });

            /** 添加show方法到onshow方法序列 **/
            this.onshow(function(){
                //隐藏navbar.backbtn
                nav.hidebackbtn();
                //重建navbar中的btns
                nav.createbtn(this.hashpath);
            });

            /** 数据库相关 **/
            if(this.table.length>0 && dj.module.isEnabled('db')){
                //加载并显示数据表
                this.ontab('table',function(tbidx){
                    var tb = this.$tb(tbidx);
                    if(!$is.Null(tb)){
                        tb.query.call(tb);
                    }
                });
                //显示记录详情，关联到router.rule.navtabshow
                this.ontab('detail',function(){
                    var args = Array.prototype.slice.call(arguments);
                    if(args.length<1){return false;}
                    var tbidx = args.shift();
                    var tb = this.$tb(tbidx);
                    if(!$is.Null(tb)){
                        tb.detail.apply(tb, args);
                    }
                });
                //显示表单
                //this.ontab('form',function(tbidx, type, id){
                this.ontab('form',function(){
                    var args = Array.prototype.slice.call(arguments);
                    if(args.length<2){return false;}
                    var tbidx = args.shift();
                    var tb = this.$tb(tbidx);
                    //console.log(args);
                    if(!$is.Null(tb)){
                        tb.form.apply(tb, args);
                    }
                });
            }

            /** notice相关 **/
            this.ontab('notice', function(){
                $ajax.load(_self.$api('notice'), function(d){
                    $router.container.current.html(d)
                });
            });
        },
        //添加show方法到onshow方法序列，在hashpath显示时队列执行，以for循环方式
        onshow : function(func){
            if($is.Function(func)){
                dj.queue.push(this.hashid+'_show',func,this);
            }
            return this;
        },
        //添加load方法到onload方法序列，在hashpath显示时队列执行，以callback回调链方式
        //此方法必须自带一个callback回调函数参数，并置于参数第一个
        onload : function(func){
            if($is.Function(func)){
                dj.queuecb.push(this.hashid+'_load',func,this);
            }
            return this;
        },
        reload : function(){
            var hasharr = dj.url.hash();
            var hashpath = this.hashpath;
            if(hashpath.split('/').length<hasharr.length){  //当前页面为此navitem的下级tab页面，调用tab页面的ontab方法
                var tab = hasharr.splice(hashpath.split('/').length);
                var tabm = this.tabexec[tab.shift()];
                if($is.Function(tabm)){
                    tabm.apply(this.$execobj(), tab);
                }
            }else{
                return nav.dftload();
            }
        },
        //添加tabexec方法，关联到navitem.tab的onshow方法
        ontab : function(tabn, func){
            if($is.Object(tabn) && !$is.empty(tabn)){
                for(var i in tabn){
                    this.ontab(i, tabn[i]);
                }
            }else if($is.String(tabn) && tabn!='' && $is.Function(func)){
                this.addtab(tabn);
                this.tabexec[tabn] = func;
            }
            return this;
        },
        //设定方法
        method : function(method, func){
            var keys = Object.keys($opt('nav/navobj'));
            if(!$is.set(func) && $is.Object(method) && !$is.empty(method)){
                for(var i in method){
                    this.method(i,method[i]);
                }
            }else if($is.String(method) && method!='' && $is.Function(func)){
                this[method] = func;
            }
            return this;
        },
        $api : function(type){
            return nav.$api(this.hashpath, type);
        },
        trigger : function(){
            var args = Array.prototype.slice.call(arguments);
            if(args.length<=0){return false;}
            var method = args.shift();
            var func = this[method];
            if(!$is.Function(func)){return false;}
            var thisobj = nav.$execobj(this.hashpath);
            return func.apply(thisobj, args);
        },
        $execobj : function(){
            var eo = this.execobj;
            if(!dj.is.String(eo)){return this;}
            eo = eo.replace(/_hash_/g,this.hash);
            eo = eo.replace(/_hashpath_/g,this.hashpath);
            var hasharr = this.hashpath.split('/');
            var earr = hasharr.splice(-2);
            hasharr.push(earr[0]);
            eo = eo.replace(/_fhash_/g,earr[0]);
            eo = eo.replace(/_fhashpath_/g,hasharr.join('/'));
            eo = dj.fn.array.xpath(dj, eo);
            return dj.is.Null(eo) ? this : eo;
        },

        //获取当前navitem包含的数据表对象
        $tb : function(idx){
            if(dj.module.isEnabled('db')===false){return null;}
            if(this.table.length<0){return null;}
            //idx = !$is.Number(idx) ? 0 : idx;
            if($is.Number(idx)){
                if(!$is.set(this.table[idx])){return null;}
                return dj.db.$(this.table[idx]);
            }else if($is.String(idx)){
                var _idx = -1;
                for(var i=0;i<this.table.length;i++){
                    if(this.table[i].split('/').indexOf(idx)>=0){
                        _idx = i;
                        break;
                    }
                }
                if(_idx<0){return null;}
                return dj.db.$(this.table[_idx]);
            }
            return null;
        },

        //功能函数
        //动态添加nav.tabs[xxx]
        addtab : function(tabn, opt){
            if(!$is.set(this.tabs[tabn])){
                this.tabs[tabn] = '';
                dj.router.addrule(this.hashpath+'/'+tabn, {
                    title : '',
                    exec : function(){
                        var hasharr = dj.url.hash();
                        if(hasharr.length<3){return false;}
                        var args = Array.prototype.slice.call(arguments);
                        var item = nav.$item();
                        var exec = item.tabexec[hasharr[2]];    //navitem.tab路由show方法关联到navitem.tabexec.{method}
                        //重建navbar中的btns
                        nav.createbtn(item.hashpath);
                        //显示backbtn
                        nav.showbackbtn();
                        if($is.Function(exec)){
                            exec.apply(item.$execobj(), args);
                        }
                    }
                });
            }
        },
        //hash跳转
        goto : function(hash, reload){
            var hashpath = this.hashpath+'/'+hash;
            var rule = $router.$rule(hashpath);
            if($is.Object(rule) && $is.set(rule.loaded)){
                if($is.Boolean(reload) && reload==true){
                    $router.editrule(hashpath,{loaded:false});
                }
                location.hash = '#/'+hashpath;
            }
        },
        //返回按钮功能
        goback : function(){
            var hh = $router.hashistory;
            //console.log(hh);
            if(hh.length<2){
                this.toroot();
                return;
            }
            var ch = hh[hh.length-1],
                th = hh[hh.length-2];
            var carr = ch.split('/'),
                tarr = th.split('/');
            if(tarr[0]!=carr[0] || tarr[1]!=carr[1]){
                history.go(-1);
            }else{
                if(carr.length<=2){return false;}
                if(tarr.length<=2){
                    history.go(-1);
                }else{
                    switch(carr[2]){
                        case 'table' :
                            if(['form','detail','instock','outstock'].indexOf(tarr[2])>=0){
                                this.toroot();
                            }else{
                                history.go(-1);
                            }
                            break;
                        default :
                            history.go(-1);
                            break;
                    }
                }
            }
        },
        //从navitem的tab页面返回navitem的根路径，同时调用reload方法
        toroot : function(reload){
            if($is.Boolean(reload) && reload==true){
                $router.editrule(this.hashpath,{loaded:false});
            }
            location.hash = '#/'+this.hashpath;
            //this.reload();
        },
        
        //未完成功能提示
        comingsoon : function(){
            $weui.msg.err('此项功能正在开发中，敬请期待！','Coming Soon');
        }
        
    };

    window.DommyJS.nav = window.$nav = nav;
    window.$navi = nav.$item;
    window.$navs = nav.items
    window.$navc = function(){return nav.current;}

})(window, window.DommyJS);



/**** DommyVUE组织对象，作为一个子模块插入DommyJS框架，必须加载Vue框架 ****/
(function(window, dj, undefined){
    //写入预设参数，可在dj.init.run()参数中定制
    dj.setDefault({
        dv : {
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
        
        //创建vue实例
        create : function(vmn, opt){
            var el = '#'+vmn;
            if($(el).length>0){
                opt.el = el;
                dv.vms[vmn] = new Vue(opt);
                return dv.vms[vmn];
            }
            return null;
        }
    };

    //定义组件
    dv.def = function(compname, opt){
        dv.components.push(compname);
        //other...

        return Vue.component(compname, opt);
    }

    //init
    dv.init = function(){
        dj.log('-=* DommyVUE is ready! *=-');
    }

    window.DommyJS.DommyVUE = window.DommyJS.dv = window.DommyVUE = window.dv = window.$dv = dv;
})(window, window.DommyJS);



/**** ui ****/
(function(window, dj, undefined){
    //写入预设参数，可在dj.init.run()参数中定制
    dj.setDefault({
        ui : {
            modulename : 'ui',    //子模块name
            required : false,    //是否必需子模块，默认否
            enabled : false,     //模块当前是否激活，默认否

            idpre : 'DJ_UI_',
            dura : {
                ani : 200,
                show : 2000
            },
            easing : 'easeOutQuad',
        },

        tpl : {
            ui : {
                loading : '<div id="${id}" class="-dp-ui-loading"><div class="-dp-ui-loading_a"></div><div class="-dp-ui-loading_b"></div><div class="-dp-ui-loading_c"></div><div class="-dp-ui-loading_d"></div></div>',
                ajaxerr : '<div id="${id}" class="-dp-ui-ajaxerr"></div>'
            }
        }
    });

    var ui = {

    };

    //loading
    ui.loading = {
        create : function(){
            var id = $opt('ui/idpre')+'loading';
            if(dj.$(id).length<=0){
                $(dj.tpl.parse('ui/loading',{
                    id : id
                })).appendTo('body').toViewportCenter();
            }
            return dj.$(id);
        },
        on : function(){return ui.loading.create();},
        off : function(){
            var id = $opt('ui/idpre')+'loading';
            if(dj.$(id).length>0){dj.$(id).remove();}
        }
    };

    //ajax err
    ui.ajaxerr = {
        create : function(){
            var id = $opt('ui/idpre')+'ajaxerr';
            if(dj.$(id).length<=0){
                $(dj.tpl.parse('ui/ajaxerr',{
                    id : id
                })).appendTo('body').toViewportCenter();
            }
            return dj.$(id);
        }
    };

    



    window.DommyJS.ui = window.$ui = ui;
})(window, window.DommyJS);
