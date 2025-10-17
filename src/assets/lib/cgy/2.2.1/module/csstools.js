/**
 * cgy.js 库 csstools 扩展
 * css 工具，颜色计算等
 */

const csstools = {};

//cgy 扩展包信息，必须包含
csstools.module = {
    name: 'csstools',
    version: '0.1.0',
    cgyVersion: '2.0.0'
}

//初始化方法，必须包含
csstools.init = cgy => { cgy.def( {

    /**
     * style link tools
     */

    //插入 <link rel=stylesheet href= />
    appendStyleLink(cssid, csshref) {
        let hd = document.querySelector('head'),
            csslink = document.querySelector(`#${cssid}`);
        if (cgy.is.empty(csslink) || !csslink.nodeName || csslink.nodeName!='LINK') {
            csslink = document.createElement('link');
            csslink.setAttribute('id', cssid);
            csslink.setAttribute('rel', 'stylesheet');
            csslink.setAttribute('href', csshref);
            hd.appendChild(csslink);
        }
    },

    //删除 <link rel=stylesheet href= />
    removeStyleLink(cssid) {
        let hd = document.querySelector('head'),
            csslink = document.querySelector(`#${cssid}`);
        if (!cgy.is.empty(csslink) && csslink.nodeName && csslink.nodeName=='LINK') {
            hd.removeChild(csslink);
        }
    },


    /**
     * css style
     */
    // {} --> css 语句
    toCssString(sty={}) {
        if (cgy.is.empty(sty) || !cgy.is.plainObject(sty)) return '';
        let s = [];
        for (let i in sty) {
            let ik = i.toSnakeCase('-');
            s.push(`${ik}:${sty[i]};`);
        }
        return s.join('');
    },
    //css 语句 --> {}
    toCssObj(sty='') {
        if (!cgy.is.string(sty) || sty=='') return {};
        let sarr = sty.split(';'),
            sobj = {};
        for (let sti of sarr) {
            if (!sti.includes(':')) continue;
            let si = sti.trimAny(' '),
                sia = si.split(':'),
                sk = sia[0].trimAny(' ').toCamelCase(false),
                sv = sia[1].trimAny(' ');
            sobj[sk] = sv;
        }
        return sobj;
    },
    //合并 任意 cssString/Obj 返回 {}
    mergeCss(...css) {
        let is = cgy.is,
            ext = cgy.extend,
            isstr = is.string,
            isobj = is.plainObject,
            isemp = is.empty,
            tocstr = cgy.toCssString,
            tocobj = cgy.toCssObj,
            sobj = {};
        for (let csi of css) {
            if (isstr(csi) && csi!='') {
                sobj = ext({}, sobj, tocobj(csi));
            } else if (isobj(csi) && !isemp(csi)) {
                sobj = ext({}, sobj, csi);
            }
        }
        return sobj;
    },

    //querySelector
    elm(selector) {return document.querySelector(selector)},
    elms(selector) {return document.querySelectorAll(selector)},
    //获取元素 style 样式
    elmStyle(el) {return window.getComputedStyle(el);},
    //获取 body/head 元素
    elmBody() {return document.querySelector('body');},
    elmHead() {return document.querySelector('head');},
    //获取某个元素的 box 模型四个顶点的坐标(相对于整个window的坐标，不是相对于父元素)[x,y, window.innerWidth-x, window.innerHeight-y]
    elmRect(el) {
        let cord = {
                lt: [0,0, 0,0],
                rt: [0,0, 0,0],
                lb: [0,0, 0,0],
                rb: [0,0, 0,0],
            },
            ww = window.innerWidth,
            wh = window.innerHeight;
        if (cgy.is.empty(el)) return cord;
        let bcr = el.getBoundingClientRect();
        //if (cgy.elmFixed(el)) {
        //    bcr = cgy.elmOffset(el);
        //}
        //console.log(bcr);
        let ew = bcr.width,
            eh = bcr.height,
            ef = bcr.left,
            et = bcr.top;
        cord.lt = [ef, et, ww-ef, wh-et];
        cord.rt = [ef+ew, et, ww-ef-ew, wh-et];
        cord.lb = [ef, et+eh, ww-ef, wh-et-eh];
        cord.rb = [ef+ew, et+eh, ww-ef-ew, wh-et-eh];
        return cord;
    },
    __elmRect(el) {
        let cord = {
                lt: [0,0, 0,0],
                rt: [0,0, 0,0],
                lb: [0,0, 0,0],
                rb: [0,0, 0,0],
            },
            ww = window.innerWidth,
            wh = window.innerHeight;
        if (cgy.is.empty(el)) return cord;
        let eo = cgy.elmOffset(el),
            ew = eo.width,
            eh = eo.height,
            ef = eo.left,
            et = eo.top;
        cord.lt = [ef, et, ww-ef, wh-et];
        cord.rt = [ef+ew, et, ww-ef-ew, wh-et];
        cord.lb = [ef, et+eh, ww-ef, wh-et-eh];
        cord.rb = [ef+ew, et+eh, ww-ef-ew, wh-et-eh];
        return cord;
    },
    //获取某个元素的实际 offset 位置与尺寸
    elmOffset(el) {
        let ofs = {
                left: el.offsetLeft,
                top: el.offsetTop,
                width: el.offsetWidth,
                height: el.offsetHeight
            },
            scroll = {
                x: window.scrollX,
                y: window.scrollY
            },
            p = el.parentNode,
            isd = cgy.is.defined,
            ism = cgy.is.empty,
            esty = cgy.elmStyle(el),
            hasfixed = false;
        if (el==cgy.elmBody()) {
            ofs.height = window.innerHeight;
            return ofs;
        }
        if (esty.position=='fixed') return ofs;
        while (!ism(p) && isd(p.nodeName) && p.nodeName!='BODY') {
            //console.log(p.getAttribute('class'), 'offsetLeft', p.offsetLeft, p.offsetTop);
            ofs.left += (p.offsetLeft - p.scrollLeft);
            ofs.top += (p.offsetTop - p.scrollTop);
            if (cgy.elmStyle(p).position=='fixed') {
                hasfixed = true;
                break;
            }
            p = p.parentNode;
            //console.log(p.getAttribute('class'));
        }
        if (!hasfixed) {
            ofs.left -= scroll.x;
            ofs.top -= scroll.y;
        }
        return ofs;
    },
    //判断一个元素是否使用 fixed 定位，在另一个 fixed 定位的元素中也属于 fixed 定位
    elmFixed(el) {
        let p = el.parentNode,
            isd = cgy.is.defined,
            ism = cgy.is.empty,
            esty = cgy.elmStyle(el),
            hasfixed = false;
        if (esty.position=='fixed') return true;
        while (!ism(p) && isd(p.nodeName) && p.nodeName!='BODY') {
            if (cgy.elmStyle(p).position=='fixed') {
                hasfixed = true;
                break;
            }
            p = p.parentNode;
        }
        return hasfixed;
    },


    /**
     * animation tools
     */
    //为元素增加一个动画效果，需要 animate css 支持
    async addAnimateTo(el, ani, opt={}) {
        opt = cgy.extend({
            speed: 'fast'
        }, opt);
        let clss = [];
        if (cgy.is.defined(opt.delay)) {    //2,3,4,5
            clss.push('animate__delay-'+opt.delay+'s');
        }
        if (cgy.is.defined(opt.speed) && opt.speed!='none') {    //slow,slower,fast,faster
            clss.push('animate__'+opt.speed);
        }
        if (cgy.is.defined(opt.repeat)) {   //1,2,3,infinite
            if (opt.repeat=='infinite') {
                clss.push('animate__infinite');
            } else {
                clas.push('animate__repeat-'+opt.repeat);
            }
        }
        clss.push(ani.startsWith('animate__') ? ani : 'animate__'+ani);
        //console.log(clss);
        //先删除原有的动画类
        await cgy.removeAnimateFrom(el);
        //添加新动画效果
        await cgy.wait(10);
        let cl = el.classList;
        if (!cl.contains('animate__animated')) {
            cl.add('animate__animated');
        }
        for (let cli of clss) {
            cl.add(cli);
        }
        await cgy.wait(10);
        return el;
    },
    //删除元素已有的动画类
    async removeAnimateFrom(el, ani=null) {
        let cl = el.classList,
            rcl = [];
        if (cgy.is.string(ani)) {
            if (cl.contains('animate__'+ani)) {
                cl.remove('animate__'+ani);
            }
        } else {
            //删除 除 animate__animated 以外的 animate 类
            for (let i=0;i<cl.length;i++) {
                if (cl[i].startsWith('animate__') && cl[i]!='animate__animated') {
                    //cl.remove(cl[i]);
                    rcl.push(cl[i]);
                }
            }
            //console.log(rcl);
            if (rcl.length>0) {
                for (let cli of rcl) {
                    cl.remove(cli);
                }
            }
        }
        await cgy.wait(10);
        return el;
    },


    /**
     * fullscreen F11 全屏
     */
    //网页全屏转换
    toggleFullscreen() {
        let doc = window.document,
            docEl = doc.documentElement,
            requestFullscreen = docEl.requestFullscreen ||
                docEl.mozRequestFullScreen ||
                docEl.webkitRequestFullscreen || 
                docEl.msRequestFullscreen,
            exitFullscreen = doc.exitFullscreen ||
                doc.mozCancelFullScreen ||
                doc.webkitExitFullscreen ||
                doc.msExitFullscreen;
        if (cgy.isFullscreen()===false) {
            requestFullscreen.call(docEl);
        } else {
            exitFullscreen.call(doc);
        }
    },
    //判断当前是否是全屏状态
    isFullscreen() {
        let doc = window.document;
        if (
            !doc.fullscreenElement && 
            !doc.mozFullScreenElement && 
            !doc.webkitFullscreenElement &&
            !doc.msFullscreenElement
        ) {
            return false;
        } else {
            return doc.fullscreenElement || 
            doc.mozFullScreenElement || 
            doc.webkitFullscreenElement ||
            doc.msFullscreenElement;
        }
    },
    //监听 fullscreenchange 事件
    whenFullscreenChange(callback) {
        let doc = window.document;
        //监听 F11
        doc.addEventListener('keydown', function(evt) {
            if (evt.key === 'F11') {
                evt.preventDefault(); //阻止默认行为
                cgy.toggleFullscreen();
            }
        });
        //监听 fullscreenchange
        if (doc.exitFullscreen) doc.addEventListener('fullscreenchange', callback);
        if (doc.webkitExitFullscreen) doc.addEventListener('webkitfullscreenchange', callback);
        if (doc.mozCancelFullScreen) doc.addEventListener('mozfullscreenchange', callback);
        if (doc.msExitFullscreen) doc.addEventListener('MSFullscreenChange', callback);
    },

    /**
     * Mask 遮罩层对象
     *      cgy.mask()
     *              .show()
     *              .hide()     如果当前 仍有元素的 need-mask 属性 == yes 则无法 hide
     *              .get()  --> element
     *              .addClass(classname, classname, ...)
     *              .removeClass(classname, classname, ...)
     */
    mask: cgy.proxyer(
        (selector='.cv-mask') => {
            let body = document.querySelector('body'),
                cmask = document.querySelectorAll(selector),
                mask = cgy.mask.get();
            if (cmask.length>0) {
                cgy.mask.current({
                    mask: cmask[0]
                });
            } else {
                if (cgy.is.undefined(mask)) {
                    cgy.mask.create();
                }
            }
            return cgy.mask;
        },{
            create() {
                let body = document.querySelector('body'),
                    mask = document.createElement('div');
                mask.classList.add('cv-mask');
                body.appendChild(mask);
                cgy.mask.current({
                    mask
                });
                return cgy.mask;
            },
            get: () => cgy.mask.current('mask'),
            addClass(...clss) {
                let mask = cgy.mask.get(),
                    cl = mask.classList;
                for (let cls of clss) {
                    if (!cl.contains(cls)) {
                        cl.add(cls);
                    }
                }
                return cgy.mask;
            },
            removeClass(...clss) {
                let mask = cgy.mask.get(),
                    cl = mask.classList;
                for (let cls of clss) {
                    if (cl.contains(cls)) {
                        cl.remove(cls);
                    }
                }
                return cgy.mask;
            },
            needMask() {
                let body = document.querySelector('body'),
                    cds = body.childNodes,
                    need = false;
                for (let i=0;i<cds.length;i++) {
                    let cdi = cds[i],
                        nm = cdi.getAttribute('need-mask');
                    if (nm=='yes') {
                        need = true;
                        break;
                    }
                }
                return need;
            },
            hide() {
                if (cgy.mask.needMask()) {
                    return cgy.mask.removeClass('mask-show');
                }
                return cgy.mask;
            },
            show: () => cgy.mask.addClass('mask-show'),
        }
    ),



    /**
     * 通用拖拽
     */
    //cache 数据
    dragCache: {
        start: {},
        over: {},
        drop: {},
    },
    //开始
    dragStart: (evt, startData={}, callback=null) => {
        //console.log(startData);
        let target = evt.target,
            tp = target.parentNode,
            dragerClass = cgy.is.defined(startData.dragerClass) ? startData.dragerClass : 'atto-list-drager-drager';
        startData.dragerClass = dragerClass;
        //释放目标元素 是否允许释放 的 判断方法
        if (cgy.is.empty(startData.denied) || !cgy.is(startData.denied,'function,asyncfunction')) {
            startData.denied = ev => {
                let tgt = ev.target,
                    didx = tgt.getAttribute('data-drag-idx'),
                    cidx = cgy.dragCache.start.idx || null;
                if (!cgy.is.empty(cidx) && !cgy.is.empty(didx) && cidx!=didx) {
                    return false;
                }
                return true;
            }
        }
        cgy.dragCache.start = startData;
        //console.log(cgy.dragCache);

        //设置被拖拽元素样式
        if (cgy.is.null(tp.getAttribute('data-drag-idx'))) tp = tp.parentNode;
        //console.log(tp);
        cgy.dragCache.start.drager = tp;
        if (!tp.classList.contains(dragerClass)) {
            tp.classList.add(dragerClass);
        }
        //为被拖拽元素设置 dragEnd 方法
        target.addEventListener('dragend', cgy.dragEnd);
        //执行自定义方法
        if (cgy.is(callback,'function,asyncfunction')) {
            callback(evt, startData);
        }

    },
    //拖拽到目标元素上
    dragEnter: (evt, enterClass='') => {
        let target = evt.target,
            denied = cgy.dragCache.start.denied || function() {};
        //if (cgy.is.defined(cidx) && dragIdx == cidx) return false;
        //设置目标元素 data-drag-droplabel 属性，用来改变 ::before 元素的 content
        target.setAttribute('data-drag-droplabel', cgy.dragCache.start.label);
        //设置 样式
        enterClass = enterClass=='' ? 'atto-list-drager-enter' : enterClass;
        cgy.dragCache.over.enterClass = enterClass;
        if (denied(evt)==true) {
            if (!target.classList.contains(`${enterClass}-denied`)) {
                target.classList.add(`${enterClass}-denied`);
            }
        } else {
            if (!target.classList.contains(enterClass)) {
                target.classList.add(enterClass);
            }
        }
        evt.preventDefault();
    },
    //拖拽离开目标元素
    dragLeave: (evt, enterClass='') => {
        let target = evt.target,
            denied = cgy.dragCache.start.denied || function() {};
        enterClass = enterClass=='' ? (cgy.dragCache.over.enterClass || 'atto-list-drager-enter') : enterClass;
        if (denied(evt)==true) {
            if (target.classList.contains(`${enterClass}-denied`)) {
                target.classList.remove(`${enterClass}-denied`);
            }
        } else {
            if (target.classList.contains(enterClass)) {
                target.classList.remove(enterClass);
            }
        }
        evt.preventDefault();
    },
    //拖拽结束
    dragEnd: evt => {
        let target = cgy.dragCache.start.drager,
            dragerClass = cgy.dragCache.start.dragerClass || 'atto-list-drager-drager';
        //清除样式
        if (target && target.classList) target.classList.remove(dragerClass);
        //callback
        let endfc = cgy.dragCache.start.end || null;
        if (cgy.is(endfc, 'function,asyncfunction')) {
            endfc(evt);
        }
        evt.preventDefault();
    },
    //要让元素允许在此释放，必须设置 dragover 方法
    dragOver: evt => {
        evt.preventDefault();
    },
    //在目标元素释放
    dragDrop: (evt, callback=null) => {
        let target = evt.target,
            denied = cgy.dragCache.start.denied || function() {};
        if (denied(evt)==true) return false;

        evt.preventDefault();
        //执行 dragLeave 方法
        cgy.dragLeave(evt);
        //执行自定义方法
        if (cgy.is(callback,'function,asyncfunction')) {
            if (cgy.is.asyncfunction(callback)) {
                callback(evt, cgy.dragCache.start).then(()=>{
                    //dragEnd
                    cgy.dragEnd(evt);
                    //清除缓存
                    cgy.dragCache.start = {};
                    cgy.dragCache.over = {};
                    cgy.dragCache.end = {};
                });
            } else {
                callback(evt, cgy.dragCache.start);
                cgy.wait(300).then(()=>{
                    //dragEnd
                    cgy.dragEnd(evt);
                    //清除缓存
                    cgy.dragCache.start = {};
                    cgy.dragCache.over = {};
                    cgy.dragCache.end = {};
                });
            }
        }
    },


    /**
     * 尺寸转换
     */
    __sizeConver: {
        /**
         * mm <--> px
         * 1mm = 3.78px
         */
        mm_px_dx: 3.78,
        mm2px: mm => mm * cgy.sizeConver.mm_px_dx,
        px2mm: px => px / cgy.sizeConver.mm_px_dx,

        /**
         * pt <--> px
         * 1pt = 1.34px
         */
        pt_px_dx: 1.34,
        pt2px: pt => pt * cgy.sizeConver.pt_px_dx,
        px2pt: px => px / cgy.sizeConver.pt_px_dx,

        /**
         * word 字号 与 px 转换
         */
        word_font_size_px: {
            '八号':  5,
            '七号':  5.5,
            '小六':  6.5,
            '六号':  7.5,
            '小五':  9,
            '五号':  10.5,
            '小四':  12,
            '四号':  14,
            '小三':  15,
            '三号':  16,
            '小二':  18,
            '二号':  22,
            '小一':  24,
            '一号':  26,
            '小初':  36,
            '初号':  42,
        },
        fsz2px: (fsz='五号') => {
            let is = cgy.is,
                dx = cgy.sizeConver.word_font_size_px;
            if (is.defined(dx[fsz])) return dx[fsz];
            return 0;
        },
    },




} ) }

export default csstools;