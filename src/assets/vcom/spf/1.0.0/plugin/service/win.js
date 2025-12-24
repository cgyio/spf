/**
 * Vcom 组件库插件 服务组件
 * Vue.service.win
 * 窗口系统 
 * 与 Vue.service.dc 动态组件服务配合的 窗口组件 创建|管理|销毁 服务组件实例
 */

import baseService from 'base.js';
export default {
    mixins: [baseService],
    props: {},
    data() {return {
        //已开启的 win 窗口组件实例数组  [winKey1, winKey2, ...]
        list: [],

        //已开启的 win 窗口组件的当前状态数据，以 winKey 为键名
        win: {
            //默认数据
            default: {
                //win 窗口 props 参数
                winProps: {
                    winKey: '',
                    icon: '',
                    title: '',

                    //窗口默认 尺寸
                    width: '640px',
                    height: '480px',

                    winType: 'popout',
                    minimizable: true,
                    maximizable: true,
                    closeable: true,
                    moveable: true,
                    border: true,
                    shadow: 'normal',
                    tightness: 'normal',
                    //其他 props
                    //...
                },

                /**
                 * 窗口内容
                 * !! 通过窗口系统动态创建的 win 实例的内容，必须是 1 个或多个 component 组件
                 */
                //内容 component 组件的 props
                compProps: {
                    //基础组件：pre-icon        可写为：base-icon   !! 基础组件必须以 base- 开头
                    //业务组件：pre-pms-foo     可写为：pms-foo     !! 业务组件库名称不可以省略
                    /*
                    'base-icon': { 
                        # 如果同时包裹多个 component 需要指定每个内容组件的 tabLabel
                        tabLabel: 'tab 标签名',
                        ... 
                    },
                    'pms-foo': { ... },
                    ...
                    */
                },
                //自动解析额 compProps 得到的 窗口内容 component 组件名称数组
                compNames: [
                    /*
                    'base-icon', 'pms-foo', ...
                    */
                ],
                //是否单组件窗口
                isSingleCompWin: false,


                //组件实例缓存
                ins: {
                    //当前 win 组件的实例
                    win: null,
                    /* 当前窗口内容组件实例，一个或多个
                    'base-icon': vue instance,
                    'pms-foo': vue instance,
                    */
                   //当前窗口组件实例对应的 taskbar 中的 taskitem 组件实例
                   taskitem: null,
                },

                //窗口当前的 position|size 状态
                pos: {
                    //在 minimize 之前保存 pos
                    origin: {
                        l: 0, t: 0,
                        w: 0, h: 0,
                    },
                    //当前的窗口 pos 状态
                    current: {
                        l: 0, t: 0,
                        w: 0, h: 0,
                    },
                },
                //此窗口当前是否被聚焦
                focused: false,

            },

            /* 已开启的 win 窗口实例数据
            'win-key': { ... extend from default ... },
            */
        },

        /**
         * 窗口系统可以关联 pre-win-taskbar 组件实例，用于在某个窗口实例最小化时，提供恢复按钮
         * taskbar 组件实例通常挂载到 pre-layout 组件实例中
         */
        taskbar: null,

        
    }},
    computed: {
        /**
         * 获取当前 focused 窗口组件以及相关数据
         */
        //当前激活的 窗口组件实例 在 this.win 中的数据
        focusedItem() {
            if (this.list.length<=0) return null;
            let is = this.$is,
                win = null;
            this.$each(this.list, (winKey, winItem) => {
                if (is.defined(winItem.focused) && is.boolean(winItem.focused) && winItem.focused === true) {
                    win = winItem;
                    return false;
                }
            });
            return win;
        },
        //当前激活的 winKey
        focusedKey() {
            let is = this.$is,
                wi = this.focusedItem;
            if (is.null(wi)) return '';
            return wi.winKey;
        },
        //当前激活的 win 组件实例
        focusedWin() {
            let wi = this.focusedItem;
            if (this.$is.null(wi)) return null;
            return wi.ins.win || null;
        },
    },
    methods: {

        /**
         * !! Vue.service.* 必须实现的 初始化方法，必须 async
         * ui 整体初始化
         * @param {Object} options 外部传入的 Vue.service.options.win 中的参数
         * @return {Boolean} 必须返回 true|false 表示初始化 成功|失败
         */
        async init(options) {
            this.$log("Vue.service.win init start!");
            let is = this.$is;

            //可以外部传入 默认的 win 组件的启动参数
            if (is.defined(options.defaultWin)) {
                let dftw = options.defaultWin;
                if (is.plainObject(dftw) && !is.empty(dftw)) {
                    dftw = this.$extend({}, this.win.default, dftw);
                    this.$set(this.win, 'default', Object.assign({}, dftw));
                }
                Reflect.deleteProperty(options, 'defaultWin');
            }

            //合并剩余的 options
            this.combineOptions(options);

            //inited 标记
            this.inited = true;
            return true;
        },

        /**
         * !! Vue.service.* 可实现的 afterRootCreated 方法，必须 async
         * !! 将在 Vue.$root 根组件实例创建后自动执行
         * @param {Vue} root 当前应用的 根组件实例，== Vue.$root
         * @return {Boolean} 必须返回 true|false 表示初始化 成功|失败
         */
        async afterRootCreated(root) {
            return true;
        },
        
        

        /**
         * 开启一个弹出窗口，可选的 opts 形式：
         *  0   直接传入 html 字符串
         *      $win.open(`<span class="fc-red fs-large fw-bold">Warning</span>`)
         * 
         *  1   直接传入 compName 和 compProps 以及 winProps
         *      $win.open(
         *          # 组件名 短名或全名
         *          'pms-table', 
         *          # 组件 props 可选的
         *          {
         *              table: '', 
         *              title: '', 
         *              query: {}, 
         *              ... 
         *          }, 
         *          # 窗口 props 可选的
         *          {
         *              winKey: '', 
         *              winType: 'modal', 
         *              ... 
         *          }
         *      )
         *      可以同时传入多个 compName 和 compProps
         *      $win.open(
         *          # 组件名数组
         *          ['pms-table', 'pms-form'],
         *          # 组件 props 必须的
         *          {
         *              'pms-table': { tabLabel: '必须的', ... },
         *              'pms-form': { tabLabel: '必须的', ... }
         *          },
         *          # 窗口 props 可选的
         *          {
         *              winKey: '', 
         *              winType: 'modal', 
         *              ... 
         *          }
         *      )
         * 
         *  2   传入完整的 $win.win.default 相同结构的 参数
         *      $win.open({
         *          winProps: { ... },
         *          compProps: { ... },
         *          ...
         *      })
         */
        async open(compName, compParams={}, winParams={}) {
            let is = this.$is,
                vcn = Vue.vcn(compName)
        },

        /**
         * 开启窗口，包裹单个 component 
         * @param {String} compName 组件名 可以是 短名或全名  base-foo|pms-foo  pre-foo|pre-pms-foo
         * @param {Object} compProps 组件的启动 props
         * @param {Object} winProps 外部包裹的 win 组件的 props
         * @return {Vue|null} 窗口 win 组件实例
         */
        async openSingleCompWin(compName, compProps={}, winProps={}) {
            let is = this.$is,
                ext = this.$extend,
                dftw = this.win.default,
                wprops = ext({}, dftw.winProps),
                cprops = {},
                vcn = this.$vcn(compName);
            if (!is.string(vcn) || vcn==='') return null;

            //查找可能存在的已开启的同组件窗口
            let witem = this.getItem(compName),
                comp = !is.null(witem) ? witem.ins[compName] : null;
            if (is.vue(comp) && is.defined(comp.multiple) && comp.multiple===false) {
                //只允许单例的组件 不再重复创建窗口
                let win = witem.ins.win;
                //显示窗口
                await win.show();
                //返回此窗口实例
                return win;
            }

            //创建窗口
            if (!is.plainObject(compProps)) compProps = {};
            if (!is.plainObject(winProps)) winProps = {};
            //准备 winProps
            if (!is.empty(winProps)) wprops = ext(wprops, winProps);
            //winKey
            if (!is.defined(wprops.winKey) || wprops.winKey==='') wprops.winKey = this.getKey(compName);
            console.log(wprops);
            //tabList
            wprops.tabList = [{
                key: 'default-tab',
                label: 'default-tab',
                component: compName,
                compProps
            }];
            //窗口尺寸，未指定则使用默认
            let sty = {
                width: is.numeric(wprops.width) ? wprops.width : dftw.width,
                height: is.numeric(wprops.height) ? wprops.height : dftw.height,
            };
            if (is.defined(wprops.width)) Reflect.deleteProperty(wprops, 'width');
            if (is.defined(wprops.height)) Reflect.deleteProperty(wprops, 'height');
            //窗口位置
            let wl = this.$ui.sizeToArr(sty.width),
                hl = this.$ui.sizeToArr(sty.height);
            sty.left = ((window.innerWidth - wl[0])/2) + wl[1];
            sty.top = ((window.innerHeight - hl[0])/2) + hl[1];
            //zIndex
            //sty.zIndex = this.$ui.getZindex();
            console.log(sty);
            //创建动态组件实例
            let win = await this.$dc.invoke('base-win', wprops);
            if (is.vue(win)) {
                //将创建的 窗口 item 缓存到 this.win
                let witem = /*ext({}, dftw, */{
                    winProps: wprops,
                    compProps,
                    compNames: [compName],
                    isSingleCompWin: true,
                    ins: {
                        win,

                    },
                }//);
                this.list.push(wprops.winKey);
                this.$set(this.win, wprops.winKey, Object.assign({}, witem));
                await this.$wait(100);

                //设置 zIndex
                win.setZindex();

                //窗口创建完成后 执行显示动画
                await win.dcShow(sty);
                return win;
            }
            return null;
        },



        /**
         * 按传入的 winKey 查找已开启的窗口 item
         * !! 如果传入了 compName 则查找 isSingleCompWin==true 的窗口中 包裹了此组件的窗口 item
         * 找到则返回 窗口 item 数据 包含在 this.win 对象中的数据，未找到则返回 null
         * @param {String} winKey | compName
         * @return {Object|null}
         */
        getItem(winKey) {
            if (Vue.isVcn(winKey)) {
                let is = this.$is,
                    wl = this.win,
                    item = null;
                this.$each(wl, (wi, wk) => {
                    if (wi.isSingleCompWin !== true) return true;
                    let wcl = wi.compNames || [];
                    if (wcl.includes(winKey)) {
                        item = this.win[wk];
                        return false;
                    }
                });
                return item;
            }
            if (this.list.includes(winKey)) {
                return this.win[winKey] || null;
            }
            return null;
        },

        /**
         * 创建全局唯一的 winKey
         * @param {String} compName
         * @return {String} 
         */
        getKey(compName) {
            if (Vue.isVcn(compName) !== true) return null;
            let vcn = this.$vcn(compName),
                ck = () => `${vcn}-${this.$cgy.nonce(8,false)}`,
                wk = ck();
            while (this.list.includes(wk)) {
                wk = ck();
            }
            return wk;
        },

        /**
         * 计算窗口的 尺寸|位置|
         */




        
        //关闭弹出窗口
        async closeWin(winKey) {

        },
        //最大化|最小化 弹出窗口
        async minimizeWin(winKey) {
            
        },
        async maximizeWin(winKey) {},
        //在 win.list 中查找是否已有相同内部组件名称的窗口实例 已被建立，找到返回 win 实例数据，否则返回 null
        getComponentWin(component) {
            let is = this.$is,
                win = this.win,
                wl = win.list;
            if (!is.array(wl) || is.empty(wl)) return null;
            for (let wi of wl) {
                let wio = win[wi];
                if (!is.vue(wio)) continue;
                if (wio.component === component) return wio;
            }
            return null;
        },

    }
}