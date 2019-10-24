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
            pageid : 'DJ',    //当前页面的id
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
                    errcode : 0,
                    errmsg : '',
                    data : {}
                }
            },
            vue : {
                components : {}
            },
            until : 100,    //条件回调条件检查时间间隔，毫秒
            url : {
                default : ''
            },
            tpl : {     //预设模板
                prefix : '-dj-template-'
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
        //md5 : function(s){return dj.is.Function(md5) ? md5(s) : null;},
        //在子模块定义时，将子模块默认预设写入dj._option
        setDefault : function(_opt){
            dj._option = dj.extend(dj._option, _opt);
            if(!dj.is.Null(dj.option)) dj.set(_opt);
            return dj._option;
        },
        //生成运行时设置项，dj.init.run()时调用
        set : function(opt){
            dj.option = dj.extend((dj.is(dj.option,'Null') ? dj._option : dj.option), opt);
            return dj.option;
        },
        //读取运行时设置项，dj.option.key
        opt : function(key){
            return dj.is(dj.option,'Null') ? dj.fn.array.xpath(dj._option, key) : dj.fn.array.xpath(dj.option, key);
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
        //import 加载js，css
        import : function(type,url,success,error){
            type = type.toLowerCase();
            if(['js','css'].indexOf(type)<0) type = 'js';
            var u = type+'/'+url+'.'+type;
            if(type=='js'){
                $.ajax({
                    url : dj.url.local(u),
                    dataType : 'script',
                    success : function(){
                        if($is.Function(success)){
                            success.call(dj);
                        }else{
                            dj.log('import '+url+' complete!');
                        }
                    },
                    error : function(jqXHR, textStatus, errorThrown){
                        if($is.Function(error)){
                            error(jqXHR, textStatus, errorThrown);
                        }else{
                            dj.log('import '+url+' error : '+errorThrown);
                        }
                    }
                });
            }else if(type=='css'){
                $('<link rel="stylesheet" href="/'+u+'">').appendTo('head');
                $(function(){
                    if($is.Function(success)){
                        success.call(dj);
                    }
                });
            }
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
                    return setTimeout('window.DommyJS.until('+_cidx+')',dj.opt('until'));
                }
            }
        },
        //控制台信息输出，仅在 dj.option.debug==true 时有效
        log : function(log){
            if(dj.opt('debug')==true){
                console.log('DJ >>> '+log);
            }
        },
        err : function(errmsg){
            dj.log("DJ >>> Error : "+errmsg);
            alert(errmsg);
            return false;
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
                //var pre = dj.option.url.default;
                //pre = dj.is.String(pre) && pre!='' ? dj.url.host()+'/'+pre : dj.url.host();
                var pre = dj.url.host();
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
        //ajax  need jquery
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
            },
            //动态加载子模块
            import : function(modname,callback){
                var modf = '';
                if(modname.indexOf('/')<0){
                    modf = 'module/'+modname;
                }else{
                    var marr = modname.split('/');
                    var app = marr.shift();
                    modf = app+'/js/module/'+marr.join('/');
                }
                dj.import('js',modf,function(){
                    //加载可能存在的css
                    var modcss = modf.replace(/js/g,'css');
                    dj.import('css',modcss);
                    dj.module.afterimport(modname,callback);
                },function(){
                    dj.log('import module '+modname+' error : '+errorThrown);
                });
            },
            afterimport : function(modname,callback){
                var mod = modname.split('/').pop();
                var opt = {};
                opt[mod] = {imported : true};
                dj.set(opt);
                if($is.Function(callback)) callback();
            },
            //定义子模块
            setDefault : function(modname,opt){
                var _opt = {};
                _opt[modname] = dj.extend({
                    modulename : modname,
                    modulepath : '',
                    required : false,
                    enabled : false,
                    imported : false
                },opt);
                dj.setDefault(_opt);
            },
            def : function(modname,_opt,opt){
                if($is.set(dj[modname])){
                    return dj.err('modulename '+modname+' has been used!');
                }
                dj.module.setDefault(modname,_opt);
                dj[modname] = opt;
                dj[modname.toLowerCase()] = window['$'+modname.toLowerCase()] = dj[modname] = opt;
            },
            //初始化动作，异步加载子模块文件，文件全部加载完成后，调用callback
            init : function(opt,callback){
                var modi = null, imp = [], f = '';
                for(var i in opt){
                    modi = opt[i];
                    if(!$is.set(modi.enabled) || modi.enabled==false) continue;
                    if($is.set(dj[i])) continue;
                    if($is.set(modi.path)){
                        f = modi.path;
                    }else if($is.set(opt.pageid)){
                        f = opt.pageid+'/'+i;
                    }else{
                        f = i;
                    }
                    imp.push(f);
                }
                if(imp.length<=0){
                    if($is.Function(callback)) callback();
                }
                var imported = 0;
                for(var i=0;i<imp.length;i++){
                    //console.log(imp[i]);
                    dj.module.import(imp[i],function(){
                        imported+=1;
                    });
                }
                dj.until(function(){
                    return imported >= imp.length;
                },function(){
                    if($is.Function(callback)) callback();
                });
            }
        },
        //vue支持
        vue : {
            //引入的组件集合
            components : {},
            //vue实例集合
            vms : {},
            //获取vue实例
            $ : function(vueid){return dj.vue.vms[vueid] || null;},
            //状态全局缓存，关联到vue实例的data
            _statu : {},
            $statu : function(key){return dj.fn.array.xpath(dj.vue._statu,key);},
            //创建vue实例
            create : function(vmn, opt){
                var el = '#'+vmn;
                if($(el).length>0){
                    opt.el = el;
                    if(!$is.set(opt.data)) opt.data = {};
                    opt.data.vueid = vmn;
                    dj.vue.vms[vmn] = new Vue(opt);
                    return dj.vue.vms[vmn];
                }
                return null;
            },
            //动态加载vue单文件组件（*.vue），需要 /js/vue/http-vue-loader.js
            import : function(comps){
                var compo = {};
                for(var i in comps){
                    if($is.set(dj.vue.components[i])){
                        compo[i] = dj.vue.components[i];
                    }else{
                        var compf = comps[i];
                        if(compf.indexOf('/')<0){
                            compf = '/res/dj/vue/component/'+compf+'.vue';
                        }else{
                            var arr = compf.split('/');
                            var app = arr.shift();
                            compf = '/res/app/'+app+'/vue/'+arr.join('/')+'.vue';
                        }
                        compo[i] = dj.vue.components[i] = httpVueLoader(compf);
                    }
                }
                return compo;
            },
            //初始化，加载启动参数中指定的vue组件
            init : function(){
                var comps = $opt('vue/components');
                if(!$is.Null(comps)){
                    dj.vue.import(comps);
                }
            }
            //自定义vue组件
            /*def : function(compname, opt){
                dj.vue.components.push(compname);
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
            },*/
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
            parse : function(tplname, opt){   //处理template string
                var tplstr = $opt('tpl/'+tplname);
                return dj.tpl.parsestr(tplstr,opt);
            },
            parsestr : function(str, opt){
                if(!$is(str,'String') || str=='' || !$is(opt,'Object')) return '';
                /*
                *  模板语法
                *  ${foo/bar/...}              在opt中查找xpath
                *  ${tpl::tplname,_opt}        调用其他模板，_opt可为  url格式数据,xpath,不指定则_opt=opt
                *  ${funcname::p1,p2,...}      调用注册到dj.tpl.fn数组中的处理函数(或在opt中包含的函数)，opt默认为函数内部this，p1,p2,...可为xpath,字符串,数字
                */
                str = str.replace(/\$\{([\w\/\(\)\:\+\-\,\=\~\u4e00-\u9fa5]+)\}/g, function(match, key, value){
                    var rst = '';
                    if(key.indexOf('::')>=0){
                        var karr = key.split('::');
                        var m = karr[0];
                        switch(m){
                            case 'tpl' :

                                break;
                            
                            default :
                                rst = dj.tpl.fn.run(m,karr[1],opt);
                                break;
                        }
                    }else{
                        rst = dj.fn.array.xpath(opt, key);
                    }
                    return $is(rst,'String') || $is(rst,'Number') ? rst : '';
                });
                return str;
            },
            //预设的模板标签处理函数
            fn : {
                reg : function(fn,func){
                    if($is.Function(func)){
                        var fo = dj.fn.array.xpath({},fn,func);
                        dj.tpl.fn = dj.extend(dj.tpl.fn, fo);
                    }else if($is.Object(fn)){
                        for(var i in fn){
                            dj.tpl.fn.reg(i,fn[i]);
                        }
                    }
                },
                $ : function(fn){return dj.fn.array.xpath(dj.tpl.fn, fn);},
                run : function(fn,key,opt){    //执行模板标签函数
                    var f = dj.tpl.fn.$(fn);
                    if(!$is.Function(f)) f = dj.fn.array.xpath(opt,fn);
                    if(!$is.Function(f)) return '';
                    var arr = key.split(',');
                    for(var i=0;i<arr.length;i++){
                        if(!$is.Null(dj.fn.array.xpath(opt,arr[i]))){
                            arr[i] = dj.fn.array.xpath(opt,arr[i]);
                        }
                    }
                    return f.apply(opt,arr);
                }
            },
            //针对页面内置的text/x-template进行预处理
            innertpl : {
                _getxpath : function(str){
                    var prefix = $opt('prefix');
                    var xpath = str.split(prefix).pop();
                    xpath = xpath.replace(/\-/g,'/');
                    xpath = xpath.replace(/\_/g,'/');
                    xpath = xpath.toLowerCase();
                    return xpath;
                },
                xtemp : function(){
                    var prefix = $opt('prefix');
                    $('script[type="text/x-template"]').each(function(i,v){
                        v = $(v);
                        if(v.hasAttr('for') && v.attr('for').indexOf(prefix)>=0){
                            var xpath = dj.tpl.innertpl._getxpath(v.attr('for'));
                            var tpls = v.html();
                            var tplo = dj.fn.array.xpath({}, xpath, tpls);
                            dj.set({tpl:tplo});
                            v.remove();
                        }
                    });
                }
            },
            //模板初始化
            init : function(){
                dj.tpl.innertpl.xtemp();
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
            set : function(opt,callback){    //读取初始化参数，并根据参数设置，执行各子模块的初始化函数
                //初始化子模块，异步加载子模块文件
                dj.module.init(opt,function(){
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
                    var _initfunc = null, _requiredfunc = [], _normalfunc = [];
                    for(var i in dj.option){
                        if($is.set(dj[i]) && dj.is(dj[i].init,'Function')){
                            if($is.set(dj.option[i].enabled) && dj.option[i].enabled==true){
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
                    if($is.Function(callback)){
                        callback();
                    }
                });
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
            dj.init.set(opt,function(){
                if(dj.is.Function(func)){dj.init.reg(func);}
                dj.log('DommyJS Ver.'+dj.version+' Starting Now ... ');
                dj.init.run();
            });
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
        dj.tpl.init();
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
        dj.log('DommyJS components include : '+subs.join(', ')+'.');
        dj.log('DommyJS Ver.'+dj.version+' Successfully Loaded. Enjoy!');

        //vue组件加载
        dj.vue.init();

        //响应resize事件
        $(window).on('resize',dj.resize.run);

        //通知中心启动
        //dj.notice.start();
        
        //hash路由器初始解析
        /*if($opt('router/enabled')==true){
            dj.router.parse();
        }*/
    }


    window.Dommy = window.DommyJS = window.DJ = window.dj = window.$$ = dj;
    window.$is = dj.is;
    window.$opt = dj.opt;
    window.$ajax = dj.ajax;
    window.$module = dj.module;
    window.$vue = dj.vue;
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
                                if(dj.is(_arr[dj.fn.string.num(xps[i])],'Undefined')){
                                    _arr = null;
                                    break;
                                }else{
                                    _arr = _arr[xps[i]];
                                }
                            }
                            return _arr;
                        }else{
                            var _arr = {}, _tarr = {};
                            _arr[dj.fn.string.num(xps[xps.length-1])] = val;
                            for(var i=xps.length-2;i>=0;i--){
                                _tarr = _arr;
                                _arr = {};
                                _arr[dj.fn.string.num(xps[i])] = _tarr;
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
            //数字类型字符串转为数字
            num : function(str){
                return $is.Number(str) ? str*1 : str;
            },
            //检查字符串str中是否包含key(or [key1,key2,...])，hasall==true则必须全部包含，false至少包含1个
            has : function(str,key,hasall){
                if(!$is.String(str) || str=='') return false;
                if($is.String(key)){
                    if(key=='') return false;
                    return str.indexOf(key)>=0;
                }else if($is.Array(key)){
                    if(!key.length || key.length<=0) return false;
                    hasall = $is.Boolean(hasall) ? hasall : true;
                    var flag = hasall;
                    for(var i=0;i<key.length;i++){
                        if(hasall==true && str.indexOf(key[i])<0){
                            flag = false;
                            break;
                        }
                        if(hasall==false && str.indexOf(key[i])>=0){
                            flag = true;
                            break;
                        }
                    }
                    return flag;
                }else{
                    return false;
                }
            },
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







