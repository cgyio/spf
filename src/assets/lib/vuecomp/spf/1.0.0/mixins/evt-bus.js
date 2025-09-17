/**
 * 事件总线相关操作
 */

export default {
    data() {return {
        //已经通过 $when 方法订阅的事件集合
        evt: {
            /*
            'event-name': [
                {
                    comp: 订阅的组件对象,
                    handler: 事件处理方法，可以是方法名 或 方法函数,
                    once: false,    是否一次性
                }
            ]
             */
        }
    }},
    created() {
        //Vue.prototype.$ev = this.$trigger;
    },
    methods: {
        
        /**
         * 通过事件总线订阅某个事件，替代 $on 方法
         * @param {String} evtName 事件名称
         * @param {Object} comp 事件订阅者，组件对象
         * @param {String | Function} handler 事件处理方法，方法名或方法函数
         * @param {Object} opt 其他关于此事件订阅的参数
         * @return {Boolean}
         */
        $when(evtName, comp, handler, opt={}) {
            this.$log(
                `组件 ${comp.$options.name} 订阅 ${evtName} 事件`,
                ...arguments
            );
            let evt = this.event,   //this.evt,
                is = this.$is;
            if (is.undefined(comp._uid)) return false;  //事件订阅者必须是组件对象
            if (is.undefined(evt[evtName])) {
                //this.$set(this.evt, evtName, []);
                this.event[evtName] = [];
            }
            if (this.watching(evtName, comp)==true) return true;    //只能订阅一次
            let evo = Object.assign({}, {
                comp,
                handler,
                once: false     //默认不是一次性事件
            }, opt);
            //this.evt[evtName].push(evo);
            this.event[evtName].push(evo);
            return true;
        },

        /**
         * 取消事件订阅
         * @param {String} evtName 事件名称
         * @param {Object} comp 事件订阅者，组件对象
         * @return {Boolean}
         */
        $whenOff(evtName, comp) {
            this.$log(
                `组件 ${comp.$options.name} 取消订阅 ${evtName} 事件`,
                ...arguments
            );
            let evt = this.event,   //this.evt,
                is = this.$is;
            if (is.undefined(comp._uid)) return false;
            if (is.undefined(evt[evtName]) || !is.array(evt[evtName])) return false;
            let evts = evt[evtName],
                evtn = [];
            for (let i=0;i<evts.length;i++) {
                let evi = evts[i],
                    compi = evi.comp;
                if (compi._uid==comp._uid) continue;
                evtn.push(evi);
            }
            this.event[evtName] = evtn;
            return true;
        },

        /**
         * 触发事件，替代 $emit 方法
         * * 使用事件总线触发事件，必须在第2个参数传递触发事件的组件对象 *
         * @param {String} evtName 事件名称
         * //@param {Object} comp 事件触发者，组件对象
         * @param {...any} args 事件处理函数的参数
         * @return {any}
         */
        $trigger(evtName, /*comp, */...args) {
            let is = this.$is,
                comp = args[0];
            if (!is.empty(comp) && is.vue(comp)) {  //如果第2个参数是一个组件，则此组件为此事件的触发者
                //检查触发者的 props.customEventHandler 对象，看是否有自定义事件处理方法
                let ceh = comp.customEventHandler;
                if (!is.empty(ceh) && is.defined(ceh[evtName])) {
                    let evh = ceh[evtName],
                        evf = is(evh,'function,asyncfunction') ? evh : comp[evh];
                    if (is(evf,'function,asyncfunction')) {
                        //有自定义的事件处理方法，拦截由事件总线注册的事件处理方法，
                        this.$log(
                            `组件 ${comp.$options.name} 响应 ${evtName} 事件`,
                            '通过 customEventHandler 中定义的方法',
                            evtName,
                            comp,
                            args
                        );
                        return evf.call(comp, ...args);
                    }
                }
            }
            //if (!is.empty(comp) && !is.vue(comp)) args.unshift(comp);
            //检查此事件是否有组件通过事件总线订阅
            if (this.isTriggerable(evtName)) {
                let evt = this.event[evtName],  //this.evt[evtName],
                    rst = [],
                    del = [];   //需要在执行后删除的事件订阅，idx 序号
                for (let i=0;i<evt.length;i++) {
                    let evi = evt[i],
                        com = evi.comp,
                        hdl = evi.handler;
                    //事件订阅者必须是组件
                    if (!is.vue(com)) continue;
                    //如果有其他组件订阅此事件，则 $ui 组件即使订阅了此事件，也不会响应
                    if (com.$options.name=='atto-ms-layout' && evt.length>1) continue;
                    //如果事件触发者组件是
                    let hdf = is(hdl,'function,asyncfunction') ? hdl : com[hdl];
                    if (!is(hdf,'function,asyncfunction')) continue;
                    let rsi = hdf.call(com, ...args);
                    if (evi.once==true) {   //如果是一次性事件
                        del.push(i)
                    }
                    this.$log(
                        `组件 ${com.$options.name} 响应 ${evtName} 事件`,
                        evtName,
                        com,
                        args
                    );
                    rst.push(rsi);
                }
                if (del.length>0) {     //有需要删除的事件订阅
                    for (let i=0;i<del.length;i++) {
                        let idx = del[i];
                        //this.evt[evtName].splice(idx,1);
                        this.event[evtName].splice(idx,1);
                    }
                }
                return rst;
            }
            //如果没有组件通过事件总线订阅此事件，则调用默认的 $emit 方法
            if (!is.empty(comp) && is.vue(comp)) {
                /*this.$log(
                    `无订阅，因此 ${comp.$options.name}.$emit('${evtName}')`,
                    evtName, 
                    args
                );*/
                return comp.$emit(evtName, /*comp, */...args.slice(1));
            }
            /*this.$log(
                `未知组件触发了 ${evtName} 事件，无订阅， 因此 $bus.$emit('${evtName}')`,
                evtName, 
                args
            );*/
            return this.$emit(evtName, /*comp, */...args);
        },

        /**
         * 同一个组件对象，一次订阅多个事件
         * @param {Object} comp 事件订阅者，组件对象
         * @param {Object} evts 事件描述，{ evtName:{handler:,once:false, ... }, ... }
         * @return {Boolean}
         */
        $whenAll(comp, evts={}) {
            let is = this.$is;
            if (!is.vue(comp) || is.empty(evts)) return false;
            let flag = true;
            for (let [evtName, opt] of Object.entries(evts)) {
                if (
                    (!is.plainObject(opt) && !is(opt,'string,function,asyncfunction')) || 
                    (is.plainObject(opt) && (is.empty(opt.handler) || !is(opt.handler, 'string,function,asyncfunction')))
                ) {
                    continue;
                }
                let handler, _opt, bd;
                if (!is.plainObject(opt)) {
                    handler = opt;
                    _opt = {};
                } else {
                    handler = opt.handler;
                    _opt = this.$cgy.clone(opt);
                    Reflect.deleteProperty(_opt, 'handler');
                }
                bd = this.$when(evtName, comp, handler, _opt);
                flag = flag && bd;
                if (flag===false) break;
            }
            return flag;
        },

        /**
         * 取消某个组件的所有订阅事件
         * 同一个组件对象，一次订阅多个事件
         * @param {Object} comp 事件订阅者，组件对象
         * @return {Boolean}
         */
        $whenOffAll(comp) {
            this.$log(
                `组件 ${comp.$options.name} 被销毁，取消所有订阅事件`,
                ...arguments
            );
            let evt = this.event,   //this.evt,
                is = this.$is;
            if (is.undefined(comp._uid)) return false;
            if (is.empty(evt)) return true;
            for (let evn in evt) {
                let evts = evt[evn],
                    evtn = [];
                if (is.undefined(evts) || !is.array(evts)) continue;
                for (let i=0;i<evts.length;i++) {
                    let evi = evts[i],
                        compi = evi.comp;
                    if (compi._uid==comp._uid) continue;
                    evtn.push(evi);
                }
                this.event[evn] = evtn;
            }
            return true;
        },



        /**
         * tools
         */
        
        //判断某个事件是否可以被触发，已定义，有订阅者
        isTriggerable(evtName) {
            let is = this.$is,
                evt = this.event;   //this.evt;
            if (is.undefined(evt[evtName])) return false;
            if (is.empty(this.getWatchers(evtName))) return false;
            return true;
        },

        //获取某个事件的所有订阅者，组件对象组成的数组
        getWatchers(evtName) {
            let evt = this.event,   //this.evt,
                is = this.$is;
            if (is.defined(evt[evtName]) && is.array(evt[evtName]) && evt[evtName].length>0) {
                let wts = evt[evtName],
                    wt = [];
                for (let i=0;i<wts.length;i++) {
                    if (is.empty(wts[i].comp) || !is.vue(wts[i].comp)) continue;
                    wt.push(wts[i].comp);
                }
                return wt;
            }
            return [];
        },

        //获取某个组件订阅了哪些事件，返回 { evtName:handler, ... }
        getWatchingEvents(comp) {
            let evt = this.event,
                is = this.$is,
                wes = {};
            if (!is.vue(comp)) return {};
            for (let [evtName, evo] of Object.entries(evt)) {
                if (evo.comp._uid!==comp._uid) continue;
                wes[evtName] = evo.handler;
            }
            return wes;
        },
        
        /**
         * 判断组件是否订阅某事件
         * @param {String} evtName 事件名称
         * @param {Object} comp 事件订阅者，组件对象
         * @return {Boolean}
         */
       watching(evtName, comp) {
           let is = this.$is;
           if (!is.vue(comp)) return false;
           let wts = this.getWatchers(evtName);
           if (is.empty(wts)) return false;
           for (let i=0;i<wts.length;i++) {
               if (is.vue(wts[i]) && wts[i]._uid===comp._uid) {
                   return true;
                   break;
               }
           }
           return false;
       },
    }
}