/**
 * Vue2.* 插件 
 * CGY-VUE 基础插件
 * 
 * 全局指令
 * Vue.directive('fooBar',{...}) 
 * 在组件上 v-foo-bar:x="y"
 * 
 */
//工具库
//import cgy from '/src/lib/cgy/default.min.js';

export default {

    /**
     * 拖拽 resize
     * v-drag-resize:xy="{
     *      inComponent: Vue instance,          # 被 resize 的 elm 所属的 组件实例
     *      
     *      # 指定 x|y 方向的 resize 尺寸范围，[min,max] 必须是 带单位的 尺寸值字符串
     *      limit: {
     *          x: ['10px', '320px'],
     *          y: [null, '35%'],
     *      },
     * 
     *      # 其他自定义参数，会在 resize 完成后，通过关联的 component 组件回调传回，自动触发 after-drag-resize 事件时也会传回这些值
     *      ...
     * }"
     */
    dragResize: {
        bind(el, binding) {
            el.$drag = {
                dir: binding.arg,       //v-drag-resize:xy="foobar"  -->  xy
                value: binding.value,    //v-drag-resize:xy="foobar"  -->  foobar
                target: el,
                comp: null,
                /**
                 * 定义尺寸调整的 范围 min|max
                 * !! 根据传入的 binding.value.min|max 自动生成
                 * 参数格式：
                 *  {
                 *      x: [10, 320],       # 纯数字
                 *      y: [null, 128],     # 仅指定 max
                 *  }
                 */
                limit: {
                    x: [],
                    y: [],
                },
                //定义 x|y 的尺寸单位，可以使用不同的单位  默认 px
                ext: {
                    x: 'px',
                    y: 'px',
                },
                //!! 尺寸单位可选：px|%|...
                exts: ['px', '%', /*...*/],
                //额外传入的 参数
                extra: {},
                //默认 binding.value
                dftOpt: {
                    target: el,
                    inComponent: null,
                    limit: {
                        x: [],  //!! 尺寸值必须是 100px|30% 带单位字符串
                        y: [],  //!! x|y 可以使用不同的 尺寸单位
                    },
                },
                styles: {
                    x: ['drager','drager-resize', 'drager-x'],
                    y: ['drager','drager-resize', 'drager-y'],
                    xy: [],
                    active: ['drag-active']
                },
                start: {
                    x: 0,
                    y: 0,
                    w: 0,
                    h: 0,
                },
                init() {
                    let is = cgy.is,
                        iss = s => is.string(s) && s!=='',
                        iso = o => is.plainObject(o) && !is.empty(o),
                        isa = a => is.array(a) && a.length>0,
                        isn = n => is.realNumber(n),
                        isu = n => is.numeric(n),
                        elg = el.$drag,
                        dir = elg.dir,
                        dft = elg.dftOpt,
                        val = elg.value;
                    if (val==='' || !(iss(val) || iso(val))) {
                        el.$drag.target = el;
                    } else if (iss(val)) {
                        el.$drag.target = document.querySelector(`#${val}`);
                        if (!is.elm(el.$drag.target)) el.$drag.target = el;
                    } else if (iso(val)) {
                        val = Object.assign({},dft,val);

                        //target
                        if (iss(val.target)) {
                            el.$drag.target = document.querySelector(`#${val.target}`);
                        } else if (is.elm(val.target)) {
                            el.$drag.target = val.target;
                        } else {
                            el.$drag.target = el;
                        }
                        Reflect.deleteProperty(val, 'target');

                        //inComponent
                        if (is.vue(val.inComponent)) el.$drag.comp = val.inComponent;
                        Reflect.deleteProperty(val, 'inComponent');
                        
                        //处理 limit
                        if (iso(val.limit)) {
                            cgy.each(['x','y'], (di,i)=>{
                                if (!dir.includes(di) || !isa(val.limit[di])) return true;
                                let sza = el.$drag.parseSizes(...val.limit[di]);
                                //保存 范围数字
                                el.$drag.limit[di] = sza.num;
                                //保存 尺寸单位
                                el.$drag.ext[di] = sza.ext;
                            });
                        }
                        Reflect.deleteProperty(val, 'limit');

                        //其他传入的 自定义参数，保存到 el.$drag.extra
                        if (iso(val)) {
                            el.$drag.extra = Object.assign({}, val);
                        }
                    }
                },
                //处理传入的 limit.x|y  [min, max]  拆分出 [ 纯数字, 纯数字 ] 和 单位
                parseSizes(...sizes) {
                    let is = cgy.is,
                        iss = s => is.string(s) && s!=='',
                        isa = a => is.array(a) && a.length>0,
                        isu = n => is.numeric(n),
                        isn = n => is.realNumber(n),
                        nums = [],
                        exts = [];
                    cgy.each(sizes, (size,i) => {
                        if (!isu(size)) {
                            nums.push(null);
                            return true;
                        }
                        //拆分 数字和单位
                        let sza = el.$drag.sizeToArr(size);
                        //拆分不成功，或者不支持的 尺寸单位
                        if (!isa(sza) || !el.$drag.exts.includes(sza[1])) {
                            nums.push(null);
                            return true;
                        }
                        //保存
                        nums.push(sza[0]);
                        if (!exts.includes(sza[1])) exts.push(sza[1]);
                    });
                    //min|max 必须是相同的尺寸单位，且尺寸单位必须存在  否则 limit 不生效
                    if (exts.length!==1) return {num: [], ext: 'px'};
                    return {num: nums, ext: exts[0]};
                },
                //拆分 数字 和 单位   100px --> [100, 'px']
                sizeToArr(size=null) {
                    let is = cgy.is,
                        iss = s => is.string(s) && s!=='',
                        isn = n => is.realNumber(n),
                        isu = n => is.numeric(n);
                    if (!isu(size)) return null;
                    let unit = size.replace(/^\-?[\d.]{1,}/g, ''),
                        num = unit==='' ? size*1 : (size.substring(0, size.length-unit.length))*1;
                    if (!isn(num) || !iss(unit)) return null;
                    return [num, unit];
                },

                //事件监听
                $dragStart(ev) {
                    let target = ev.target,
                        rel = el.$drag.target,
                        body = document.querySelector('body');
                    target.classList.add(...el.$drag.styles.active);
                    //初始值，计算依据
                    el.$drag.start = {
                        x: ev.clientX,
                        y: ev.clientY,
                        w: rel.offsetWidth,
                        h: rel.offsetHeight
                    }
                    body.addEventListener('dragover', el.$drag.$dragOver);
                },
                $dragEnd(ev) {
                    let is = cgy.is,
                        target = ev.target,
                        body = document.querySelector('body');

                    //计算 resize 后的 尺寸
                    let size = el.$drag.resize(ev);

                    //等待 元素 resize 动画
                    cgy.wait(300).then(()=>{
                        el.$drag.reset();
                        target.classList.remove(...el.$drag.styles.active);
                        body.removeEventListener('dragover', el.$drag.$dragOver);

                        //调用关联组件的相关方法
                        if (is.vue(el.$drag.comp)) {
                            let comp = el.$drag.comp;
                            //尝试调用 comp.afterDragResize 方法
                            if (is(comp.afterDragResize, 'function,asyncfunction')) {
                                comp.afterDragResize(el, size);
                            }
                            //触发事件
                            comp.$emit('after-drag-resize', el, size);
                        }
                    });
                },
                $dragOver: ev => {
                    ev.preventDefault();
                },

                /**
                 * 核心计算 计算 resize 拖拽后的 target 元素的 width|height
                 * 区分不同的 尺寸单位
                 */
                resize(ev) {
                    let is = cgy.is,
                        elg = el.$drag,
                        rel = elg.target,
                        dir = elg.dir,
                        //limit = elg.limit,
                        ext = elg.ext,
                        start = elg.start;
                    //如果不存在 target 退出
                    if (!is.elm(rel)) return false;
                    //按 dir 方向
                    let sizes = {};
                    cgy.each(['x','y'], (di,i) => {
                        if (dir.includes(di)) {
                            let clk = `client${di.toUpperCase()}`,
                                sty = di==='x' ? 'width' : 'height',
                                size = el.$drag.$calc(di, (ev[clk] - start[di]));
                            //设置最终 style.width|height
                            rel.style[sty] = size;
                            //缓存 
                            sizes[di] = size;
                        }
                    });
                    //返回变化后的 尺寸值
                    return sizes;
                },
                reset() {
                    el.$drag.start = {
                        x: 0,
                        y: 0,
                        w: 0,
                        h: 0
                    };
                },

                /**
                 * 核心计算方法  计算 resize 拖拽后的 target 元素的 width|height
                 * @param {String} dir resize 方向 x|y 
                 * @param {Number} resize 变化值  鼠标移动长度 px 
                 * @return {String} 计算后的 width|height 数值字符串，应与 ext 尺寸单位匹配
                 */
                $calc(dir='x', resize=0) {
                    let is = cgy.is,
                        isa = a => is.array(a) && a.length>0,
                        isn = n => is.realNumber(n),
                        elg = el.$drag,
                        rel = elg.target,
                        limit = elg.limit[dir],
                        ext = elg.ext[dir],
                        start = elg.start,
                        //style key
                        styk = dir==='x' ? 'width' : 'height',
                        //offset key
                        offk = dir==='x' ? 'offsetWidth' : 'offsetHeight',
                        //计算后的值
                        size = 0;

                    //根据尺寸单位 执行不同的计算逻辑
                    if (ext==='px') {
                        //使用默认单位 px 
                        //在 offsetWidth|Height 上相加
                        size = rel[offk] + resize;
                    } else {
                        //按使用 ext 尺寸单位的不同  分别计算
                        switch (ext) {
                            //特殊单位处理...

                            //任意非 px 单位  例如 %
                            default: 
                                //获取原始的 style.width|height 应为 以 ext 为单位的尺寸形式
                                let osz = rel.style[styk],
                                    //60% --> [60, '%']
                                    osza = el.$drag.sizeToArr(osz);

                                //!! 如果 尺寸值无效 回退到 px 指定的 limit 将失效
                                if (!isa(osza) || osza[1]!==ext) {
                                    console.log('illegal target.style.'+styk+' for ext:', ext);
                                    //取消 limit
                                    el.$drag.limit[dir] = [];
                                    el.$drag.ext[dir] = 'px';
                                    return el.$drag.$calc(dir, resize);
                                }

                                //变化前的 offset 尺寸值
                                let offsz = rel[offk],
                                    //数值 与 % 的比值
                                    ratio = offsz/osza[0];
                                //变化后的值
                                offsz += resize;
                                //变化后的值 转为 对应的 尺寸单位
                                size = offsz/ratio;
                                
                                break;
                        }
                    }

                    //如果存在 limit 限制
                    if (isa(limit)) {
                        if (isn(limit[0]) && limit[0]>size) size = limit[0];
                        if (isn(limit[1]) && limit[1]<size) size = limit[1]; 
                    }

                    //返回计算后的 尺寸值  带单位
                    return `${size}${ext}`;
                },
            }

            //初始化
            el.$drag.init();

            let drager = document.createElement('div');
            drager.classList.add(...el.$drag.styles[el.$drag.dir]);
            drager.draggable = true;
            drager.addEventListener('dragstart', el.$drag.$dragStart);
            drager.addEventListener('dragend', el.$drag.$dragEnd);
            el.appendChild(drager);
            el.$drager = drager;
        },

        //解绑，清理
        unbind(el) {
            el.removeChild(el.$drager);
            Reflect.deleteProperty(el, '$drag');
            Reflect.deleteProperty(el, '$drager');
        },

    },

    //拖动按钮调整数值
    dragTurn: {
        bind(el, binding) {
            el.$drag = {
                dir: binding.arg,       //v-drag-resize:xy="foobar"  -->  xy
                method: binding.value,    //v-drag-resize:xy="foobar"  -->  foobar
                inTurning: false,
                start: {
                    x: 0,
                    y: 0,
                    l: 0,
                    t: 0
                },
                //防抖执行 turning
                debounceTurning(ev) {
                    if (el.$drag.inTurning) return false;
                    el.$drag.inTurning = true;
                    let method = el.$drag.method;
                    if (typeof method == 'function') {
                        method(ev, el.$drag.start).then(rtn => {
                            if (rtn==true) {
                                el.$drag.inTurning = false;
                                el.$drag.start = {
                                    x: ev.clientX,
                                    y: ev.clientY,
                                    l: el.offsetLeft,
                                    t: el.offsetTop
                                }
                            }
                        });
                    }
                },
                reset() {
                    el.$drag.inTurning = false;
                    el.$drag.start = {
                        x: 0,
                        y: 0,
                        l: 0,
                        t: 0
                    };
                },
                dragover: ev => {
                    ev.preventDefault();
                }
            }

            el.draggable = true;
            el.addEventListener('dragstart', ev => {
                var img = new Image();
                img.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' %3E%3Cpath /%3E%3C/svg%3E";
                ev.dataTransfer.setDragImage(img, 0, 0);
                ev.dataTransfer.setData('text','任意值');   //firefox
                ev.dataTransfer.effectAllowed = 'move';
                let target = ev.target,
                    body = document.querySelector('body');
                el.$drag.start = {
                    x: ev.clientX,
                    y: ev.clientY,
                    l: el.offsetLeft,
                    t: el.offsetTop
                };
                body.addEventListener('dragover', el.$drag.dragover);
            });
            el.addEventListener('drag', el.$drag.debounceTurning);
            el.addEventListener('dragend', ev => {
                let target = ev.target,
                    body = document.querySelector('body');
                el.$drag.reset();
                body.removeEventListener('dragover', el.$drag.dragover);
            });

            //console.log(el);
        }
    },

    //拖动元素移动位置
    dragMove: {
        bind(el, binding) {

            el.$drag = {
                dir: binding.arg,               //v-drag-move:xy="foobar"  -->  xy      移动限制在 x y xy
                moveTarget: binding.value,      //v-drag-move:xy="foobar"  -->  foobar  要移动的目标元素，element 或者 vue 组件，不指定为 el 自身
                vueComp: null,      //如果目标是一个 vue 组件，在此缓存
                start: {
                    x: 0,
                    y: 0,
                    l: 0,
                    t: 0
                },
                //如果
                async setMoveTarget() {
                    let mt = el.$drag.moveTarget;
                    if (mt instanceof Vue) {
                        //指定 vue 组件，要移动的是这个组件
                        //判断组件 dragMoveable 属性是否为 true，应在应用此 directive 的组件内部实现此属性（或计算属性）
                        if (mt.dragMoveable!==true) {
                            el.$drag.moveTarget = null;
                        } else {
                            el.$drag.vueComp = mt;
                            await cgy.until(()=>{
                                if (mt.$el instanceof HTMLElement) {
                                    el.$drag.moveTarget = mt.$el;
                                    return true;
                                }
                                return false;
                            });
                        }
                    } else if (mt instanceof HTMLElement) {
                        //某个 dom 元素，不操作

                    } else {
                        el.$drag.moveTarget = el;
                    }
                },
                //移动
                moving(ev) {
                    let target = el.$drag.moveTarget,
                        vcomp = el.$drag.vueComp,
                        dir = el.$drag.dir,
                        start = el.$drag.start,
                        dx = ev.clientX - start.x,
                        dy = ev.clientY - start.y,
                        tx = start.l + dx,
                        ty = start.t + dy;
                        //console.log(tx,ty);
                    if (dir.includes('x')) {
                        target.style.left = tx+'px';
                    }
                    if (dir.includes('y')) {
                        target.style.top = ty+'px';
                    }
                    el.$drag.start = {
                        x: ev.clientX,
                        y: ev.clientY,
                        l: target.offsetLeft,
                        t: target.offsetTop
                    }
                    if (vcomp!=null && typeof vcomp.whenDragMove == 'function') {
                        vcomp.whenDragMove(target);
                    }
                },
                reset() {
                    el.$drag.start = {
                        x: 0,
                        y: 0,
                        l: 0,
                        t: 0
                    };
                },
                dragover: ev => {
                    ev.preventDefault();
                }
            }

            //首先判断是否允许 drag-move
            el.$drag.setMoveTarget().then(()=>{
                if (el.$drag.moveTarget!=null) {    //有目标，继续执行
                    //console.log(el.$drag.moveTarget);
                    el.draggable = true;
                    el.addEventListener('dragstart', ev => {
                        var img = new Image();
                        img.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' %3E%3Cpath /%3E%3C/svg%3E";
                        ev.dataTransfer.setDragImage(img, 0, 0);
                        ev.dataTransfer.setData('text','任意值');   //firefox
                        ev.dataTransfer.effectAllowed = 'move';
                        let target = el.$drag.moveTarget,
                            body = document.querySelector('body');

                        //屏蔽 transition 属性
                        let css = window.getComputedStyle(target),
                            trans = css.transition;
                        //console.log({trans, prop: css.transitionProperty, dura: css.transitionDuration});
                        if (trans!='') {
                            //target.setAttribute('data-trans-cache', `${css.transitionProperty} ${css.transitionDuration}`);
                            target.setAttribute('data-trans-cache', trans);
                            target.style.transition = 'none';
                            //console.log(target.style.transition);
                        }

                        el.$drag.start = {
                            x: ev.clientX,
                            y: ev.clientY,
                            l: target.offsetLeft,
                            t: target.offsetTop
                        };
                        body.addEventListener('dragover', el.$drag.dragover);
                    });
                    el.addEventListener('drag', el.$drag.moving);
                    el.addEventListener('dragend', ev => {
                        let target = el.$drag.moveTarget,
                            vcomp = el.$drag.vueComp,
                            body = document.querySelector('body');
                        el.$drag.reset();
                        body.removeEventListener('dragover', el.$drag.dragover);

                        //恢复 transition 属性
                        let trans = target.getAttribute('data-trans-cache');
                        if (trans!=undefined && trans!=null) {
                            target.style.transition = trans;
                            target.setAttribute('data-trans-cache', undefined);
                            //console.log(target.style.transition);
                        }

                        if (vcomp!=null && typeof vcomp.afterDragMove == 'function') {
                            vcomp.afterDragMove(target);
                        }
                    });
                }
            });

            //console.log(el);
        },

        //解绑，清理
        unbind(el) {
            Reflect.deleteProperty(el, '$drag');
        },
    },

    //悬停提示 v-tip
    tip: {
        bind(el, binding) {
            el.$tip = {
                //tip 提示元素位置
                pos: binding.arg,           //v-tip:bottom-start="foobar"  -->  bottom-start
                //tip 提示参数
                option: binding.value,      //v-tip:bottom-start="foobar"  -->  foobar
                //tip 样式 modifiers
                mod: binding.modifiers,     //v-tip:bottom-start.dark.danger="foobar"  -->  {dark:true, danger:true}
                //缓存 tipper 元素
                tipper: null,
                //读取 tip pos
                $pos() {
                    let is = cgy.is,
                        tipo = el.$tip,
                        pos = tipo.pos,
                        pas = 'top,right,bottom,left'.split(','),
                        pbs = 'start,end'.split(','),
                        ps = [];
                    if (!is.string(pos) || pos=='') {
                        //默认从 top 弹出
                        ps.push('top');
                        ps.push('');
                        return ps;
                    }
                    if (pos.includes('-')) {
                        ps = pos.split('-');
                    } else {
                        ps.push(pos);
                        ps.push('');
                    }
                    if (ps.length<=1) ps.push('');
                    if (!pas.includes(ps[0])) ps[0] = 'bottom';
                    if (!pbs.includes(ps[1])) ps[1] = '';
                    if (ps.length>2) {
                        ps.splice(2);
                    }
                    return ps;
                },
                //读取 tip 参数 {}
                $option() {
                    let is = cgy.is,
                        mod = el.$tip.mod,
                        opt = el.$tip.option,
                        dft = cgy.extend({},{
                            content: '',

                            type: 'warn',    //颜色类型 cssvar.color 中定义的
                            effect: 'light',     //可选 light/dark
                            shift: 8,           //tipper 与 trigger el 的间距
                            delay: 150,         //延时 300 毫秒后 tip 消失

                            class: '',
                            style: {},
                        });
                    if (is.string(opt)) {
                        dft.content = opt;
                    } else if (is.plainObject(opt)) {
                        dft = cgy.extend({}, dft, opt);
                    }
                    //mod
                    if (is.plainObject(mod) && !is.empty(mod)) {
                        let modc = cgy.extend({}, mod),
                            isd = o => is.defined(o) && o==true,
                            iso = o => is.plainObject(o) && !is.empty(o);
                        //effect = dark
                        if (isd(modc.dark)) {
                            dft.effect = 'dark';
                            Reflect.deleteProperty(modc, 'dark');
                        }
                        //特殊颜色
                        if (is.vue(Vue.ui) && iso(Vue.ui.cssvar) && iso(Vue.ui.cssvar.color)) {
                            let csv = Vue.ui.cssvar.color,
                                sc = '';
                            for (let i in modc) {
                                if (modc[i]!=true) continue;
                                if (is.defined(csv[i])) {
                                    sc = i;
                                    break;
                                }
                            }
                            if (sc!='') {
                                dft.type = sc;
                                Reflect.deleteProperty(modc, sc);
                            }
                        }
                    }
                    //处理特殊的 content
                    if (is.array(dft.content)) {
                        //显示多行内容
                        if (is.string(dft.class) && dft.class!='') {
                            dft.class = [dft.class];
                        } else {
                            dft.class = [];
                        }
                        dft.class.push('cv-tip-multiple-rows');
                        let cnt = [],
                            i = 0;
                        for (let row of dft.content) {
                            if (i==0) {
                                cnt.push(`<span class="f-w700 f-m mg-b-s">${row}</span>`);
                            } else {
                                cnt.push(`<span>${row}</span>`);
                            }
                            i += 1;
                        }
                        dft.content = cnt.join('');
                    }
                    return dft;
                },
                //创建 tipper 元素
                $createTipper(startShow=false) {
                    let is = cgy.is,
                        iss = s => is.string(s) && s!='',
                        opt = el.$tip.$option(),
                        tip = opt.content;
                    if (!iss(tip)) return false;
                    let bd = document.querySelector('body'),
                        tipper = document.createElement('div'),
                        tipcnt = document.createElement('div'),
                        arroww = document.createElement('div'),
                        arrow = document.createElement('span'),
                        pos = el.$tip.$pos(),
                        ocls = opt.class || [],
                        poscls = ['cv-tip'],
                        tipcls = ['cv-tip',`cv-tip-type-${opt.type}`,`cv-tip-effect-${opt.effect}`];
                    if (pos[1]!='') {
                        poscls.push(...pos);
                    } else {
                        poscls.push(pos[0]);
                    }
                    tipcls.push(poscls.join('-'));
                    //if (iss(opt.effect)) tipcls.push(`cv-tip-effect-${opt.effect}`);
                    if (iss(ocls)) tipcls.push(ocls);
                    if (cgy.is.array(ocls) && ocls.length>0) tipcls.push(...ocls);

                    tipper.classList.add(...tipcls);
                    tipcnt.classList.add('cv-tip-content');
                    arroww.classList.add('cv-tip-arrow-wrapper');
                    arrow.classList.add('cv-tip-arrow');
                    tipcnt.innerHTML = opt.content;
                    tipper.appendChild(tipcnt);
                    arroww.appendChild(arrow);
                    tipper.appendChild(arroww);

                    //tipper 增加 msov 动作
                    tipper.addEventListener('mouseenter', ()=>{
                        el.$tip.$stopHiding();
                    });
                    tipper.addEventListener('mouseleave', el.$tip.$msot);

                    //如果 startShow == true 则创建时 opacity = 1
                    if (startShow==true) {
                        tipper.style.opacity = 1;
                    }

                    //添加到 body
                    bd.appendChild(tipper);
                    //缓存 tipper 元素
                    el.$tip.tipper = tipper;

                    return tipper;
                },
                //获取 tipper 元素的 位置与尺寸
                $setTipperPositionAndSize() {
                    if (!cgy.is.elm(el.$tip.tipper)) return false;
                    let elr = cgy.elmRect(el),   //触发元素 el 的 四角坐标 { lt: [x,y, vw-x, vh-y], rt: [], ... }
                        tipper = el.$tip.tipper,
                        tpos = el.$tip.$pos(),
                        tpa = tpos[0],
                        tpb = tpos[1],
                        topt = el.$tip.$option(),
                        tsty = topt.style || {};
                    if (tpb=='start') {
                        if (tpa=='top' || tpa=='bottom') {
                            //top/bottom-start
                            tipper.style.left = elr.lt[0]+'px';
                        } else if (tpa=='left' || tpa=='right') {
                            //left/right-start
                            tipper.style.top = elr.lt[1]+'px';
                        }
                    } else if (tpb=='end') {
                        if (tpa=='top' || tpa=='bottom') {
                            //top/bottom-end
                            tipper.style.right = elr.rt[2]+'px';
                        } else if (tpa=='left' || tpa=='right') {
                            //left/right-end
                            tipper.style.bottom = elr.lb[3]+'px';
                        }
                    } else {
                        let tipw = tipper.offsetWidth,
                            tiph = tipper.offsetHeight,
                            elw = el.offsetWidth,
                            elh = el.offsetHeight;
                        if (tpa=='top' || tpa=='bottom') {
                            //top/bottom
                            tipper.style.left = (elr.lt[0] + elw*0.5 - tipw*0.5)+'px';
                        } else if (tpa=='left' || tpa=='right') {
                            //left/right
                            tipper.style.top = (elr.lt[1] + elh*0.5 - tiph*0.5)+'px';
                        }
                    }
                    if (tpa=='top') {
                        tipper.style.bottom = (elr.lt[3] + topt.shift)+'px';
                    } else if (tpa=='bottom') {
                        tipper.style.top = (elr.lb[1] + topt.shift)+'px';
                    } else if (tpa=='left') {
                        tipper.style.right = (elr.lt[2] + topt.shift)+'px';
                    } else if (tpa=='right') {
                        tipper.style.left = (elr.rt[0] + topt.shift)+'px';
                    }

                    //如果 option 中指定了 style 在此应用
                    if (cgy.is.plainObject(tsty) && !cgy.is.empty(tsty)) {
                        for (let i in tsty) {
                            tipper.style[i] = tsty[i];
                        }
                    }

                    //设置 opacity = 1
                    tipper.style.opacity = 1;
                },
                //msov
                $msov() {
                    if (cgy.is.elm(el.$tip.tipper)) {
                        //如果 tipper 已创建
                        if (el.$tip.isHiding==true) {
                            //如果 tip 正在消失，则中断消失过程
                            el.$tip.isHiding = false;
                        } else if (el.$tip.hiding!=null) {
                            //正在等待消失，则中断等待
                            el.$tip.$stopHiding();
                        }
                    } else {
                        //创建 tipper
                        let tipper = el.$tip.$createTipper();
                        //tipper 未成功创建
                        if (!cgy.is.elm(tipper)) return false;

                        //调整位置
                        el.$tip.$setTipperPositionAndSize();
                        //附加 msot
                        el.addEventListener('mouseleave', el.$tip.$msot);
                    }
                    
                    //解决 未触发 msot 导致 tipper 一直不消失的问题，在 msov 时开始 finalHiding 等待
                    el.$tip.$startFinalHiding();

                    return true;
                },
                //msot
                $msot() {
                    let tipper = el.$tip.tipper,
                        hiding = el.$tip.hiding;
                    if (!cgy.is.elm(tipper)) return false;
                    //tip 消失 正在延时等待中，不做操作
                    if (!cgy.is.null(hiding)) return false;

                    //开始 hiding 等待
                    el.$tip.$startHiding();
                    
                },
                //tip 消失延时
                hiding: null,
                //tip 正在消失
                isHiding: false,
                //解决 未触发 msot 导致 tipper 一直不消失的问题，在 msov 时开始 finalHiding 等待
                finalHiding: null,
                //开始 hiding 等待
                $startHiding() {
                    let opt = el.$tip.$option();
                    if (cgy.is.null(el.$tip.hiding)) {
                        el.$tip.hiding = setTimeout(el.$tip.$hideTip, opt.delay);
                    }
                },
                //开始 finalHiding 等待
                $startFinalHiding() {
                    //未触发 msot 则 5000 毫秒后 hideTip
                    if (cgy.is.null(el.$tip.finalHiding)) {
                        el.$tip.finalHiding = setTimeout(el.$tip.$hideTip, 5000);
                    }
                },
                //中止 hiding 等待
                $stopHiding() {
                    if (!cgy.is.null(el.$tip.hiding)) {
                        clearTimeout(el.$tip.hiding);
                        el.$tip.hiding = null;
                    }
                    if (!cgy.is.null(el.$tip.finalHiding)) {
                        clearTimeout(el.$tip.finalHiding);
                        el.$tip.finalHiding = null;
                    }
                },
                //tip 消失
                $hideTip() {
                    let tipper = el.$tip.tipper,
                        ish = el.$tip.isHiding;
                    if (!cgy.is.elm(tipper) || ish) return false;
                    //标记
                    el.$tip.isHiding = true;
                    //设置 opacity = 0
                    tipper.style.opacity = 0;
                    cgy.wait(300).then(()=>{
                        if (el.$tip.isHiding!=true) {
                            //tip 消失过程被打断，直接恢复 opacity
                            tipper.style.opacity = 1;
                            //移除 hiding
                            el.$tip.$stopHiding();
                            el.$tip.$startFinalHiding();
                            return false;
                        } else {
                            //继续执行 tip 消失过程，移除 tipper 元素
                            //移除 tipper
                            return el.$tip.$removeTipper();
                            /*let bd = document.querySelector('body');
                            bd.removeChild(tipper);
                            el.$tip.tipper = null;
                            //移除 msot 动作
                            el.removeEventListener('mouseleave', el.$tip.$msot);
                            //移除 hiding
                            el.$tip.$stopHiding();
                            //移除 标记
                            el.$tip.isHiding = false;
                            return true;*/
                        }
                    });
                },
                //移除 tipper
                $removeTipper() {
                    let tipper = el.$tip.tipper,
                        bd = document.querySelector('body');
                    if (!cgy.is.elm(tipper)) return false;
                    //移除 tipper
                    bd.removeChild(tipper);
                    el.$tip.tipper = null;
                    //移除 msot 动作
                    el.removeEventListener('mouseleave', el.$tip.$msot);
                    //移除 hiding
                    el.$tip.$stopHiding();
                    //移除 标记
                    el.$tip.isHiding = false;
                    return true;
                },
                //重建 tipper 用于 v-tip 参数发生改变后
                $reCreate() {
                    if (!cgy.is.elm(el.$tip.tipper)) return false;

                    //先移除
                    el.$tip.$removeTipper();

                    //再创建
                    //创建 tipper 创建时即显示
                    let tipper = el.$tip.$createTipper(true);
                    //tipper 未成功创建
                    if (!cgy.is.elm(tipper)) return false;
                    //调整位置
                    el.$tip.$setTipperPositionAndSize();
                    //附加 msot
                    el.addEventListener('mouseleave', el.$tip.$msot);
                    //解决 未触发 msot 导致 tipper 一直不消失的问题，在 msov 时开始 finalHiding 等待
                    el.$tip.$startFinalHiding();

                    return true;
                },
            }

            //moseover 动作
            el.addEventListener('mouseenter', el.$tip.$msov);
        },

        update(el, binding) {
            el.$tip.option = binding.value;
            if (cgy.is.elm(el.$tip.tipper)) {
                //如果 tipper 已生成，则重建
                el.$tip.$reCreate();
            }
        }
    },

    //为目标元素添加 loading-mask v-loading-mask
    loadingMask: {
        bind(el, binding) {

            el.$lmask = {
                //v-loading-mask:primary="true"  -->  primary   loading 图标颜色 red|danger 默认 primary
                iconColor: binding.arg,
                //v-loading-mask:primary="true"  -->  true      显示|隐藏 遮罩层
                maskShow: binding.value,
                //缓存已生成的 遮罩层元素 element
                maskElm: null,
                //切换中
                maskToggling: false,
                //准备参数
                async prepareMask() {
                    let is = cgy.is,
                        iss = s => is.string(s) && s!=='',
                        lm = el.$lmask;
                    //默认 loading 图标颜色
                    if (!iss(lm.iconColor)) el.$lmask.iconColor = 'primary';
                    //默认 loading mask 显示状态
                    if (!is.boolean(lm.maskShow)) el.$lmask.maskShow = true;
                    return true;
                },
                //创建 mask 元素
                async createMask() {
                    //如果在切换中，不执行
                    if (el.$lmask.maskToggling) return false;
                    //进入 切换中状态
                    el.$lmask.maskToggling = true;
                    let lm = el.$lmask,
                        mask = document.createElement('div');
                    //基础样式
                    mask.classList.add('loading-mask', `loading-icon-${lm.iconColor}`);
                    mask.style.zIndex = 10;
                    //animate 进入动画，依赖 animate.css
                    mask.classList.add('animate__animated', 'animate__fadeIn');
                    await cgy.wait(10);
                    //插入 el
                    el.appendChild(mask);
                    //等待动画执行
                    await cgy.wait(300);
                    //缓存
                    el.$lmask.maskElm = mask;
                    //退出切换中状态
                    el.$lmask.maskToggling = false;
                    return true;
                },
                //移除 mask 元素
                async removeMask() {
                    //如果在切换中，不执行
                    if (el.$lmask.maskToggling) return false;
                    //进入 切换中状态
                    el.$lmask.maskToggling = true;
                    let lm = el.$lmask,
                        mask = lm.maskElm;
                    if (cgy.is.elm(mask)) {
                        //animate 隐藏动画，依赖 animate.css
                        mask.classList.remove('animate__fadeIn');
                        mask.classList.add('animate__fadeOut');
                        //等待动画执行
                        await cgy.wait(300);
                        //移除 mask
                        el.removeChild(mask);
                        await cgy.wait(10);
                        //移除缓存
                        el.$lmask.maskElm = null;
                    }
                    //退出切换中状态
                    el.$lmask.maskToggling = false;
                    return true;
                },
                //toggle mask
                async toggleMask(show=null) {
                    let is = cgy.is,
                        lm = el.$lmask;
                    //准备参数
                    await el.$lmask.prepareMask();
                    //更新 maskShow 参数
                    if (is.boolean(show)) el.$lmask.maskShow = show;
                    //toggle mask
                    if (el.$lmask.maskShow === true) {
                        if (!is.elm(lm.maskElm)) return await lm.createMask();
                    } else {
                        if (is.elm(lm.maskElm)) return await lm.removeMask();
                    }
                },
            }

            //初始化时执行 toogle
            el.$lmask.toggleMask();
        },

        //指令值 binding.value 变化时触发
        update(el, binding) {
            if (binding.value === binding.oldValue) return false;
            //指令值更新
            el.$lmask.toggleMask(binding.value);
        },

        //解除绑定时 清理资源
        unbind(el) {
            if (cgy.is.elm(el.$lmask.maskElm)) {
                el.$lmask.removeMask().then(()=>{
                    //删除 $lmask
                    Reflect.deleteProperty(el, '$lmask');
                });
            }
        },
    },

    //nicescroll
    scroll: {
        bind(el, binding) {

            el.$scroll = {
                dir: cgy.is.empty(binding.arg) ? 'y' : binding.arg,       //v-scroll:xy="{...}"  -->  xy      水平/竖直 滚动条
                style: cgy.extend({     //v-scroll:xy="{...}"  -->  {...}  滚动条的样式，object
                    size: 4,
                    color: 'var(--color-scroll)',
                    radius: 2,
                    padding: 2,
                    stopBodyScroll: false,
                    appendToBody: false,
                    useWheelEvent: false    //是否使用 wheel 替代 scroll 事件
                }, !cgy.is.plainObject(binding.value) ? {} : binding.value),    
                //滚动条的元素
                el: {
                    x: null,
                    y: null,
                    thumb: {
                        x: null,
                        y: null
                    },
                    created: {
                        x: false,
                        y: false
                    },
                },
                //body 的 overflow 属性
                bodyOverflow: '',
                //设置 el style
                setElStyle() {
                    //position = relative
                    el.style.position = 'relative';
                    //overflow
                    if (el.$scroll.dir=='xy') {
                        el.style.overflow = 'auto';
                    } else if (el.$scroll.dir=='x') {
                        el.style.overflow = 'auto hidden';
                    } else {
                        el.style.overflow = 'hidden auto';
                    }
                    //scroll-behavior
                    el.style.scrollBehavior = 'smooth';
                    //-webkit-scrollbar 隐藏原生 scroll
                    el.classList.add('cv-scroll-el');
                },
                //临时屏蔽 body 的滚动
                stopBodyScroll() {
                    let body = cgy.elmBody(),
                        ov = cgy.elmStyle(body).overflow;
                    if (el==body) return;
                    //el.$scroll.bodyOverflow = ov;
                    body.style.overflow = 'hidden';
                },
                restoreBodyScroll() {
                    let body = cgy.elmBody();
                    if (el==body) return;
                    cgy.elmBody().style.overflow = 'hidden auto';  //el.$scroll.bodyOverflow;
                },

                //event
                mouseEnter(event, xy='y') {
                    let es = el.$scroll,
                        sel = es.el;
                    if (xy=='xy') {
                        if (!sel.created.x || !sel.created.y) {
                            es.createScrollElm();
                            cgy.wait(100).then(()=>{
                                es.showThumb();
                                es.showThumb('x');
                                //es.setScrollElmEvent();
                            });
                        } else {
                            es.calcScrollPos();
                            es.showThumb();
                            es.showThumb('x');
                        }
                    } else {
                        if (!sel.created[xy]) {
                            es.createScrollElm();
                            cgy.wait(100).then(()=>{
                                es.showThumb(xy);
                                //es.setScrollElmEvent();
                            });
                        } else {
                            es.calcScrollPos();
                            es.showThumb(xy);
                        }
                    }
                    //屏蔽 body 滚动
                    if (es.style.stopBodyScroll) es.stopBodyScroll();
                },
                mouseLeave(event, xy='y') {
                    let es = el.$scroll;
                    if (xy=='xy') {
                        es.hideThumb();
                        es.hideThumb('x');
                    } else {
                        es.hideThumb(xy);
                    }
                    //恢复 body 滚动
                    if (es.style.stopBodyScroll) es.restoreBodyScroll();
                },

                //创建元素
                createScrollElm() {
                    if (el.$scroll.el.created.x && el.$scroll.el.created.y) return;
                    let es = el.$scroll,
                        dir = es.dir,
                        sty = es.style,
                        eof = cgy.elmOffset(el),
                        ew = eof.width,
                        eh = eof.height,
                        er = eof.left + ew,     //el 最右边的 left 值
                        eb = eof.top + eh,      //el 最下边的 top 值
                        esw = el.scrollWidth,
                        esh = el.scrollHeight,
                        esl = el.scrollLeft,
                        est = el.scrollTop,
                        ctn = sty.appendToBody ? cgy.elmBody() : el;

                    //console.log(eof, esh, esw);
                    
                    if (esw>ew && dir.includes('x') && es.el.x==null) {
                        let scel = document.createElement('div'),
                            thum = document.createElement('div'),
                            cl = scel.classList,
                            tcl = thum.classList;
                        cl.add('cv-scroll');
                        cl.add('cv-scroll-x');
                        let sh = sty.size+sty.padding*2;
                        Object.assign(scel.style, {
                            left: eof.left+'px',
                            top: (eb-sh)+'px',
                            width: ew+'px',
                            height: sh+'px'
                        });
                        tcl.add('cv-scroll-thumb');
                        Object.assign(thum.style, {
                            height: sty.size+'px',
                            width: (((ew*ew)/esw)-(2*sty.padding))+'px',
                            left: (((ew*esl)/esw)+sty.padding)+'px',
                            //left: sty.padding+'px',
                            top: sty.padding+'px',
                            borderRadius: sty.radius+'px',
                            backgroundColor: sty.color,
                        });
                        scel.appendChild(thum);
                        ctn.appendChild(scel);
                        el.$scroll.el.x = scel;
                        el.$scroll.el.thumb.x = thum;
                        //console.log(scel.style);
                        es.el.created.x = true;
                    }
                    if (esh>eh && dir.includes('y') && es.el.y==null) {
                        let scel = document.createElement('div'),
                            thum = document.createElement('div'),
                            cl = scel.classList,
                            tcl = thum.classList;
                        cl.add('cv-scroll');
                        cl.add('cv-scroll-y');
                        let sw = sty.size+sty.padding*2;
                        Object.assign(scel.style, {
                            left: (er-sw)+'px',
                            top: eof.top+'px',
                            width: sw+'px',
                            height: eh+'px'
                        });
                        tcl.add('cv-scroll-thumb');
                        Object.assign(thum.style, {
                            width: sty.size+'px',
                            height: (((eh*eh)/esh)-(2*sty.padding))+'px',
                            left: sty.padding+'px',
                            top: (((eh*est)/esh)+sty.padding)+'px',
                            //top: sty.padding+'px',
                            borderRadius: sty.radius+'px',
                            backgroundColor: sty.color,
                        });
                        scel.appendChild(thum);
                        ctn.appendChild(scel);
                        el.$scroll.el.y = scel;
                        el.$scroll.el.thumb.y = thum;
                        es.el.created.y = true;
                    }
                },
                //销毁元素
                removeScrollElm(xy='y') {
                    let sel = el.$scroll.el;
                    if (xy.includes('x') && sel.x!=null) {
                        sel.x.remove();
                        sel.x = null;
                        sel.thumb.x = null;
                        sel.created.x = false;
                    }
                    if (xy.includes('y') && sel.y!=null) {
                        sel.y.remove();
                        sel.y = null;
                        sel.thumb.y = null;
                        sel.created.y = false;
                    }
                },

                //show/hide scroll thumb
                showThumb(xy='y') {if (el.$scroll.el[xy]!=null) el.$scroll.el[xy].classList.add('cv-scroll-hover');},
                hideThumb(xy='y') {if (el.$scroll.el[xy]!=null) el.$scroll.el[xy].classList.remove('cv-scroll-hover');},

                //重新计算滚动条位置
                calcScrollPos() {
                    let es = el.$scroll,
                        dir = es.dir,
                        sty = es.style,
                        sel = es.el,
                        eof = cgy.elmOffset(el),
                        ew = eof.width,
                        eh = eof.height,
                        er = eof.left + ew,     //el 最右边的 left 值
                        eb = eof.top + eh,      //el 最下边的 top 值
                        esw = el.scrollWidth,
                        esh = el.scrollHeight,
                        esl = el.scrollLeft,
                        est = el.scrollTop;

                    //console.log('calcScrollPos', esh, eh);

                    if (sel.x!=null) {
                        if (esw<=ew) {
                            es.removeScrollElm('x');
                        } else {
                            let sh = sty.size+sty.padding*2;
                            Object.assign(sel.x.style, {
                                left: eof.left+'px',
                                top: (eb-sh)+'px',
                                width: ew+'px',
                                height: sh+'px'
                            });
                            Object.assign(sel.thumb.x.style, {
                                height: sty.size+'px',
                                width: (((ew*ew)/esw)-(2*sty.padding))+'px',
                                left: (((ew*esl)/esw)+sty.padding)+'px',
                            });
                        }
                    }
                    if (sel.y!=null) {
                        if (esh<=eh) {
                            es.removeScrollElm();
                        } else {
                            let sw = sty.size+sty.padding*2;
                            Object.assign(sel.y.style, {
                                left: (er-sw)+'px',
                                top: eof.top+'px',
                                width: sw+'px',
                                height: eh+'px'
                            });
                            Object.assign(sel.thumb.y.style, {
                                width: sty.size+'px',
                                height: (((eh*eh)/esh)-(2*sty.padding))+'px',
                                top: (((eh*est)/esh)+sty.padding)+'px'
                            });
                        }
                    }
                },
                //计算 滚动条的 left/top
                calcScrollThumbTop() {
                    let eh = el.offsetHeight,
                        esh = el.scrollHeight,
                        est = el.scrollTop,
                        pd = el.$scroll.style.padding;
                    return (((eh*est)/esh)+pd)+'px';
                    //return (est/(esh-eh))*100;
                },
                calcScrollThumbLeft() {
                    let ew = el.offsetWidth,
                        esw = el.scrollWidth,
                        esl = el.scrollLeft,
                        pd = el.$scroll.style.padding;
                    return (((ew*esl)/esw)+pd)+'px';
                    //return (esl/(esw-ew))*100;
                },

                //scrolling
                scrolling(event) {
                    let es = el.$scroll,
                        xy = es.dir,
                        sel = es.el;
                    if (
                        (xy=='xy' && (!sel.created.x || !sel.created.y)) ||
                        !sel.created[xy]
                    ) {
                        es.mouseEnter(event, xy);
                    }
                    //console.log('scrolling');
                    if (xy.includes('x') && sel.x!=null) {
                        sel.thumb.x.style.left = es.calcScrollThumbLeft();
                        //sel.thumb.x.style.transform = `translateX(${es.calcScrollThumbLeft()}%)`;
                    }
                    if (xy.includes('y') && sel.y!=null) {
                        sel.thumb.y.style.top = es.calcScrollThumbTop();
                        //sel.thumb.y.style.transform = `translateY(${es.calcScrollThumbTop()}%)`;
                    }
                    event.stopPropagation();
                },
                //wheeling
                wheeling(event) {
                    let es = el.$scroll,
                        shift = 2,
                        dy = event.deltaY,
                        scroll = dy*shift,
                        sl = el.scrollLeft,
                        st = el.scrollTop;
                    if (es.dir.includes('x')) {
                        el.scroll({
                            left: sl + scroll
                        });
                    }
                    if (es.dir.includes('y')) {
                        el.scroll({
                            top: st + scroll
                        });
                    }
                    
                    event.stopPropagation();
                },
            }

            let es = el.$scroll;
            //设置样式
            es.setElStyle();
            el.addEventListener('mouseenter', event => {
                es.mouseEnter(event, es.dir);
            });
            el.addEventListener('mouseleave', event => {
                es.mouseLeave(event, es.dir);
            });
            if (es.style.useWheelEvent) {
                el.addEventListener('wheel', es.wheeling);
            }// else {
                el.addEventListener('scroll', es.scrolling);
            //}
        }
    },

    //v-role 用户角色控制组件 属性 / 动作
    role: {
        bind(el, binding, vnote) {
            console.log(binding);
            let val = binding.value,
                usr = Vue.usr,
                rst = usr.infoReady && usr.auths.includes('sys-role-'+val);
            console.log(rst);
            if (!rst) {
                //if (arg=='hide') {
                    console.log(el);
                    //el.remove();
                    el.outerHTML = '<!---->';
                /*} else {
                    let comp = vnode.context;
                    console.log(comp);
                    comp.propsData[arg] = rst;
                }*/
            }
            
            
        }
    },
}