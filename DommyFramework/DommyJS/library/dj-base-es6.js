/*
 *  DommyJS基础框架    ES6版本
 *	需要jQuery环境，配合DommyPHP框架使用    /js/dj-base.js
 *	v 3.0.1
 *
 */

/** 定义DommyJS对象 **/
const $$ = {

    version: '3.0.1',
    //静态参数，运行时不可变更
    _: {
        varTypes: ['undefined','null','object','array','function','boolean','string','number','symbol'],
        eventTypes: [
            'load','ready','error','unload','resize','scroll','focus','blur','focusin','focusout',
            'change','select','submit','keydown','keypress','keyup','toggle','hover',
            'click','dblclick','contextmenu','mouseenter','mouseleave','mouseover','mouseout','mousedown','mouseup',
            'touchstart','touchmove','touchend'
        ],
    },
    //配置参数，可在启动时修改
    option: {
        disabled: false,    //当前页面（单页app）是否停用
        pageid: 'dj',       //当前页面的id
        debug: true,        //debug开关
        
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
    }
}

/** 工具方法 fn **/
const $fn = window.$fn = $$.fn = {
    //log
    log(info){
        if($fn.is(info,'string')){
            info = '$$ >>> '+info;
        }else{
            info = $fn.toString(info);
        }
        if($$.option.debug===true){
            console.log(info);
            //return info;
        }
    },
    //类型判断
    is(anything, type){
        let types = $$._.varTypes;
        let mytype = Object.prototype.toString.call(anything).slice(8,-1).toLowerCase();
        //console.log(anything);
        //console.log(type);
        if(typeof type == 'undefined') return mytype;
        if(typeof type != 'string' || types.indexOf(type.toLowerCase())<0) return false;
        type = type.toLowerCase();
        return (type === 'null' && anything === null) || (type === 'number' && isFinite(anything)) || mytype === type;
    },
    isSet(anything){return !$fn.is(anything,'undefined');},
    isEmpty(anything){
        let $is = $fn.is;
        return $is(anything,'undefined') || $is(anything,'null') || ($is(anything,'array') && anything.length<=0) || ($is(anything,'string') && anything=='') || ($is(anything,'object') && Object.keys(anything).length<=0);
    },
    is$(anything){return anything instanceof jQuery;},
    isDom(anything){return anything instanceof HTMLElement;},
    //toString
    toString(anything){
        let type = $fn.is(anything);
        let str;
        switch(type){
            case 'undefined' :
                str = type;
                break;
            case 'string' :
            case 'number' :
                str = anything+'';
                break;
            case 'function' :
            case 'symbol' :
                str = anything.toString();
                break;
            default :
                str = JSON.stringify(anything);
                break;
        }
        return str;
    },
    //extend  v2.0
    extend(target, source){
        if($fn.is(target,'object')){
            if($fn.is(source,'object')){
                $fn.each(source,function(si,i){
                    if(!$fn.is(target[i],'object') && !$fn.is(target[i],'array')){
                        target[i] = si;
                    }else{
                        target[i] = $fn.extend(target[i],si);
                    }
                });
            }else if($fn.is(source,'array')){
                $fn.each(source,function(si,i){
                    target[i] = si;
                });
            }
        }else if($fn.is(target,'array')){
            if($fn.is(source,'array')){
                if(source[0]=='merge'){
                    source.shift();
                    target.push(...source);
                    target = target.unique();
                }else if(source[0]=='update'){
                    source.shift();
                    $fn.each(source,function(si,i){
                        if(i>=target.length) return false;
                        if($fn.is(si,'null')) return true;
                        if(si===false){
                            target[i] = undefined;
                        }else{
                            if($fn.is(target[i],'object') || $fn.is(target[i],'array')){
                                target[i] = $fn.extend(target[i],si);
                            }else{
                                target[i] = si;
                            }
                        }
                    });
                    target = target.trim();
                }else{
                    target = source;
                }
            }else if($fn.is(source,'object')){
                for(let i in source){
                    if(!$fn.is(i,'number')) continue;
                    i = (i+'').num();
                    if($fn.is(target[i],'undefined')) continue;
                    if($fn.is(target[i],'object') || $fn.is(target[i],'array')){
                        target[i] = $fn.extend(target[i],source[i]);
                    }else{
                        target[i] = source[i];
                    }
                }
            }
        }
        return target;
    },
    //each
    each(obj,func){
        if(!$fn.is(func,'function')) return null;
        let rsts = [], rst = false;
        if($fn.is(obj,'array')){
            for(let i=0;i<obj.length;i++){
                rst = func(obj[i],i,obj);
                if(rst===false) break;
                rsts.push(rst);
            }
            return rsts;
        }else if($fn.is(obj,'object')){
            for(let i in obj){
                rst = func(obj[i],i,obj);
                if(rst===false) break;
                if(rst===true) continue;
                rsts[i] = rst;
            }
            return rsts;
        }
    },
    //按xpath从object中查找
    item(obj = {}, xpath = '', val){
        if(!$fn.is(obj,'object') && !$fn.is(obj,'array')) return null;
        if(!$fn.is(xpath,'string') || xpath=='') return obj;
        if(xpath.includes('/')){
            let xps = xpath.split('/');
            if($fn.is(val,'undefined')){
                let arr = obj;
                $fn.each(xps,function(p,i){
                    p = p.num();
                    if($fn.is(arr[p],'undefined')){
                        arr = null;
                        return false;
                    }else{
                        arr = arr[p];
                    }
                });
                return arr;
            }else{
                let _arr = {}, _tarr = {};
                _arr[xps.pop().num()] = val;
                for(let i=xps.length-1;i>=0;i--){
                //xps.forEach(function(p,i){
                    xps[i] = xps[i].num();
                    _tarr = _arr;
                    _arr = {};
                    _arr[xps[i]] = _tarr;
                };
                console.log(_arr);
                obj = $fn.extend(obj, _arr);
                return obj;
            }
        }else{
            if($fn.is(val,'undefined')){
                return $fn.is(obj[xpath],'undefined') ? null : obj[xpath];
            }else{
                obj[xpath] = val;
                return obj;
            }
        }
    },
}

/** 处理工具函数，原型扩展，附加到window、$$ **/
{
    //string扩展
    Object.assign(String.prototype, {
        ucfirst(){
            str = this.toLowerCase();
            str = str.replace(/\b\w+\b/g, function(word){
                return word.substring(0,1).toUpperCase()+word.substring(1);
            });
            return str;
        },
        //如果是数字形式字符串，则转为数字，否则返回字符串本身
        num(){
            if(isFinite(this)) return this*1;
            return this;
        }
    });
    //array扩展
    Object.assign(Array.prototype, {
        unique(){
            let me = this, arr = [];
            for (let i=0;i<me.length;i++) {
                if (arr.indexOf(me[i]) === -1) {
                    arr.push(me[i]);
                }
            }
            return arr;
        },
        trim(type){
            type = $fn.is(type,'undefined') ? 'undefined' : type.toLowerCase();
            let me = this, arr = [];
            for(let i=0;i<me.length;i++){
                switch(type){
                    case "undefined" :
                        if(!$fn.is(me[i],'undefined')) arr.push(me[i]);
                        break;
                    default :
                        arr.push(me[i]);
                        break;
                }
            }
            return arr;
        },
        item(xpath = '', val){
            let me = this;
            return $fn.item(me,xpath,val);
        }
    });
    //
    //jq原型扩展
    jQuery.fn.extend({
		hasAttr(attr){return typeof($(this).attr(attr)) != 'undefined';},
		attrEx(attr, dft){return typeof($(this).attr(attr)) == 'undefined' ? dft : $(this).attr(attr);},
		isChildOf(jqo){
			jqo = $(jqo);
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
		toViewportCenter(){
			var vp = {w:$(window).width(), h:$(window).height()},
				ss = {w:$(this).width(), h:$(this).height()};
			return $(this).css({
				left : (vp.w-ss.w)/2,
				top : (vp.h-ss.h)/2
			});
        },
        childto(jqo, clone){
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
		ani(effects, callback){
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
    //工具方法附加到window，$$
    $fn.each($fn,function(fi,i){
        window['$'+i] = $$[i] = fi;
    });
    //扩展is方法
    $$._.varTypes.forEach(function(vti,i){
        let ucf = vti.ucfirst();
        window['$is'+ucf] = $$['is'+ucf] = (anything) => $fn.is(anything,vti);
    });

}



/** 在 任何其他定义与动作执行之前的 $dj初始化 **/





/** 别名 **/
const DommyJS = $$;
const $dj = $$;
window.DommyJS = window.$dj = window.$$ = $$;