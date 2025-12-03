/**
 * Vcom 组件库插件 服务组件
 * Vue.service.ui
 */

export default {
    props: {
        
    },
    data() {
        return {
            /**
             * 全局 ui 服务的 参数
             */

            //SPF-Theme 主题信息
            theme: {
                //启用主题
                enable: false,
                //是否支持暗黑模式
                supportDarkMode: false,
                //当前是否在 暗黑模式下
                inDarkMode: false,
                //是否根据浏览器明暗模式，自动切换主题的 明暗模式
                autoDarkMode: true,  
                //明暗模式下的 cssvar 将根据暗黑模式启用与否，填充到 ui.cssvar 中
                cssvar: {
                    //无论是否启用暗黑模式，cssvar.light 一定存在
                    light: {},
                    //dark: {},
                },

                //明暗模式的 links
                links: {
                    light: [],
                    dark: [],
                },
            },

            //自动明暗切换 事件监听对象
            autoDarkModeShifter: null,

            /**
             * theme options
             */
            /*theme: 'base',
            themeInfo: {
                name: 'base',
                version: '',
                desc: '',
                hasDarkMode: true
            },
            //使用的组件库 
            compPackage: [],
            //使用的 图标包
            iconPackage: ['vant','md-sharp','spiner'],
            //暗黑模式开关，如需修改初始值，应在 Vue.theme.use() 方法前设置
            darkMode: false,
            //cssvar
            cssvar: {
                color: {},
                size: {}
            },
            //使用的 vue ui 库
            vueui: [],
            //vue ui 库使用的 样式主题，用于加载对应的 vueui css
            vueuiThemes: {},*/
            
            //是否开启动画
            animated: true,


            //mask
            mask: null,
            //全局弹出层统一从 $ui 取得 z-index
            zIndex: 1000, //从 1000 开始增加 

            //request status
            request: {
                waiting: false,
                success: false,
                error: false
            },

            //初始化完成标记
            inited: false,
        }
    },
    computed: {
        //当前 theme 的明暗模式 默认 light
        themeMode() {
            return (this.theme.enable && this.theme.supportDarkMode) ?
                    (this.theme.inDarkMode ? 'dark' : 'light') :
                    'light';
        },
        //当前 theme 明暗主题下的 cssvar
        cssvar() {
            let is = this.$is,
                thmode = this.themeMode,
                csvs = this.theme.cssvar || {};
            if (is.defined(csvs[thmode])) return csvs[thmode];
            //!! 未找到返回空 {} 未发生错误的情况下，不可能
            return {};
        },

        //request sign
        requestSign() {
            let req = this.request,
                sign = {
                    type: 'primary',
                    icon: 'vant-time-circle',
                    label: '就绪'
                };
            if (req.waiting) {
                sign.icon = 'vant-sync';
                sign.label = '数据加载中...';
            }
            if (req.success) {
                sign.icon = 'vant-check';
                sign.type = 'success';
                sign.label = '数据响应成功';
            }
            if (req.error) {
                sign.icon = 'vant-close';
                sign.type = 'danger';
                sign.label = '数据相应失败';
            }
            return sign;
        },
    },
    created() {
        
    },
    methods: {

        /**
         * !! Vue.service.* 必须实现的 初始化方法，必须 async
         * ui 整体初始化
         * @param {Object} options 外部传入与插件预定义的 Vue.service.options.ui 中的参数
         * @return {Boolean} 必须返回 true|false 表示初始化 成功|失败
         */
        async init(options) {
            this.$log("Vue.service.ui init start!");
            let is = this.$is;

            //需要提前处理的 ui 参数
            if (is.defined(options.log)) {
                //是否开启全局 自定义 console.log 样式
                if (is.boolean(options.log)) {
                    if (options.log) {
                        this.$cgy.log.on();
                    } else {
                        this.$cgy.log.off();
                    }
                }
                Reflect.deleteProperty(options, 'log');
            }

            //写入 theme 外部参数
            if (is.defined(options.theme)) {
                let thopts = options.theme;
                if (is.plainObject(thopts) && !is.empty(thopts)) {
                    this.theme = Object.assign(this.theme, thopts);
                    await this.$wait(10);
                }
                Reflect.deleteProperty(options, 'theme');
            }
            //写入当前模式的 cssvar
            /*if (is.defined(options.cssvar)) {
                let csvs = options.cssvar;
                if (is.plainObject(csvs) && !is.empty(csvs)) {
                    this.cssvar = Object.assign(this.cssvar, csvs);
                    await this.$wait(10);
                }
                Reflect.deleteProperty(options, 'cssvar');
            }*/

            //如果主题支持 明暗模式 则收集所有 name=color-scheme-* 的 css link 元素
            if (this.theme.enable && this.theme.supportDarkMode) {
                let links = document.querySelectorAll('link');
                links.forEach(lnk => {
                    let rel = lnk.rel,
                        lnm = lnk.getAttribute('name'),
                        lhf = lnk.getAttribute('light-href'),
                        dhf = lnk.getAttribute('dark-href');
                    if (
                        rel === 'stylesheet' && 
                        is.defined(lnm) && is.string(lnm) && lnm.startsWith('color-scheme-') &&
                        //明暗主题相关的 link 必须定义 light-href 和 dark-href
                        is.defined(lhf) && is.defined(dhf)
                    ) {
                        let lmode = lnm.substring(13);
                        this.theme.links[lmode].push(lnk);
                    }
                });
                //写入 this.theme.links
                //this.$set(this.theme, 'links', Object.assign({},thlnks));
                await this.$wait(50);

                //根据 theme.autoDarkMode 设置
                if (this.theme.autoDarkMode === true) {
                    this.$log('setAutoDarkMode');
                    await this.setAutoDarkMode();
                } else {
                    this.$log('setManualDarkMode');
                    await this.setManualDarkMode();
                }
            }

            //其他参数
            if (is.plainObject(options) && !is.empty(options)) {
                this.$cgy.each(options, (v,k) => {
                    //必须是预定义的 data 项目
                    if (is.undefined(this[k])) return true;
                    if (is.plainObject(v)) {
                        this[k] = Object.assign(this[k], v);
                    } else if (is.array(v)) {
                        //数组使用 整体替换的方式
                        this[k].splice(0);
                        this[k].push(...v);
                    } else if (is(v, 'string,number,boolean')) {
                        //其他标量形式，直接替换
                        this[k] = v;
                    }
                });
            }

            
            
            //创建 mask 实例
            /*if (!this.$is.vue(this.mask)) {
                Vue.$invokeComp('cv-mask').then(comp => {
                    this.mask = comp;
                });
            }*/

            this.inited = true;
            return true;
        },

        /**
         * 明暗模式 设为 自动切换
         * @return {Boolean}
         */
        async setAutoDarkMode() {
            if (this.theme.supportDarkMode !== true) return false;
            //首次设置 不管 autoDarkMode 的状态
            if (this.theme.autoDarkMode === true && this.inited === true) return false;

            //首先将 theme.links 中所有元素的 href 复位
            await this.setDarkModeLinkHref(null);

            //将 autoDarkmode 设为 true
            this.$set(this.theme, 'autoDarkMode', true);

            //创建一个 MediaQueryList 对象
            if (this.$is.empty(this.autoDarkModeShifter)) {
                const media = window.matchMedia('(prefers-color-scheme: dark)');
                //附加一个 变化后的回调方法
                media.afterChange = shifter => {
                    let mode = shifter.matches ? 'dark' : 'light';
                    this.$log('autoDarkMode to', mode);
                    //执行 shiftDarkModeTo 方法
                    return this.shiftDarkModeTo(mode);
                }
                //开始监听事件
                media.addEventListener('change', media.afterChange);
                //保存到 autoDarkModeShifter
                this.autoDarkModeShifter = media;
            }

            //同步数据到 storage
            return await this.syncToLocalStorage();
        },
        /**
         * 明暗模式 设为 手动切换
         * @return {Boolean}
         */
        async setManualDarkMode() {
            if (this.theme.supportDarkMode !== true) return false;
            //首次设置 不管 autoDarkMode 的状态
            if (this.theme.autoDarkMode !== true && this.inited === true) return false;

            //删除事件监听，并删除 MediaQueryList 对象
            if (!this.$is.empty(this.autoDarkModeShifter)) {
                let media = this.autoDarkModeShifter;
                media.removeEventListener('change', media.afterChange);
                this.autoDarkModeShifter = null;
            }

            //将 autoDarkMode 设为 false
            this.$set(this.theme, 'autoDarkMode', false);

            //将所有 link 的 href 设为当前明暗模式
            let cmode = this.themeMode;
            await this.setDarkModeLinkHref(cmode);

            //同步数据到 storage
            return await this.syncToLocalStorage();
        },
        /**
         * 明暗模式切换时，设置|同步 数据
         * @param {String} mode 要切换到的 模式
         * @return {Boolean}
         */
        async shiftDarkModeTo(mode='dark') {
            if (this.theme.supportDarkMode !== true) return false;
            let cmode = this.themeMode;
            if (cmode === mode) return true;
            //设置数据
            this.$set(this.theme, 'inDarkMode', mode === 'dark');
            await this.$wait(10);
            //同步到 localStorage
            return await this.syncToLocalStorage();
        },
        /**
         * 手动切换 明暗模式
         * @return {Boolean}
         */
        async toggleDarkMode() {
            if (this.theme.supportDarkMode !== true) return false;
            if (this.theme.autoDarkMode === true) return false;
            let cmode = this.themeMode,
                tmode = cmode === 'dark' ? 'light' : 'dark',
                links = this.theme.links,
                //当前浏览器的 明暗模式
                bmode = this.prefersColorSchemeIs();
            this.$log('manualDarkMode to', tmode);

            //手动将 当前浏览器明暗模式下的 link.href 设为 tmode-href
            await this.setDarkModeLinkHref(tmode);

            //执行数据的 设置|同步
            return await this.shiftDarkModeTo(tmode);
        },
        /**
         * 将所有 link 的 href 设为 指定模式的 mode-href 用于手动切换明暗模式，同时阻止自动切换生效
         * @param {String} mode 要设置的 明暗模式，指定 null 表示复位所有 href
         * @return {Boolean}
         */
        async setDarkModeLinkHref(mode=null) {
            let is = this.$is,
                each = this.$cgy.each,
                links = this.theme.links,
                modes = 'light,dark'.split(',');
            
            if (is.string(mode) && modes.includes(mode)) {
                //将所有 link 的 href 设为指定 明暗模式，这会阻止 浏览器自动明暗切换
                each(modes, modei => {
                    each(links[modei], lnk => {
                        let href = lnk.getAttribute(`${mode}-href`);
                        if (lnk.href !== href) {
                            lnk.setAttribute('href', href);
                        }
                    });
                });
            } else{
                //将所有 link 的 href 复位
                each(modes, modei => {
                    each(links[modei], lnk => {
                        let href = lnk.getAttribute(`${modei}-href`);
                        if (lnk.href !== href) {
                            lnk.setAttribute('href', href);
                        }
                    });
                });
            }
            await this.$wait(50);
            return true;
        },

        /**
         * 获取当前页面的 prefers-color-scheme 或 判断是否 dark|light
         * @param {String} mode 要判断的 明暗模式，默认不指定，直接返回当前的 dark|light 模式
         * @return {Boolean}
         */
        prefersColorSchemeIs(mode=null) {
            if (!this.$is.string(mode) || mode === '' || 'dark,light'.split(',').includes(mode) !== true) {
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            //判断
            return window.matchMedia(`(prefers-color-scheme: ${mode})`).matches;
        },

        /**
         * 将 ui 相关数据写入 localStorage
         * @return {Boolean}
         */
        async syncToLocalStorage() {
            this.$cgy.localStorage.$setJson('service.ui', {
                theme: {
                    autoDarkMode: this.theme.autoDarkMode,
                    inDarkMode: this.theme.inDarkMode,
                }
            });
            await this.$wait(50);
            this.$log('syncToLocalStorage service.ui');
            return true;
        },



        /**
         * 主题样式相关
         */

        /**
         * 返回完整的 样式类名称，自动补全前缀
         * 例如：    base-btn-primary    --> spf-btn-primary
         *          .pms-table-wrapper  --> .spf-pms-table-wrapper
         * !! 与直接写 __PRE__-btn-primary 效果一致
         * @param {String} cls 要自动补全的 样式类名，必须以组件库名开始
         * @return {String} 补全后的 样式类名
         */
        clsn(cls) {
            let is = this.$is,
                vcn = Vue.getVcomName(cls);
            //未找到对应的 组件库
            if (!is.string(vcn) || vcn === '') return cls;
            //组件库对应的 类名前缀 
            let pre = Vue.vcom[vcn].prefix || null;
            if (!is.string(pre) || pre === '') return cls;
            //用对应组件库的名称前缀，替换组件库名
            return cls.replace(`${vcn}-`, `${pre}-`);
        },



        //获取 zindex 并自增
        getZIndex() {
            this.zIndex += 1;
            return this.zIndex;
        },

        //修改 request 状态指示
        setRequestStatus(status = 'success') {
            let sts = 'waiting,success,error'.split(',');
            if (!sts.includes(status)) return;
            for (let i=0;i<sts.length;i++) {
                if (sts[i]==status) {
                    this.request[sts[i]] = true;
                } else {
                    this.request[sts[i]] = false;
                }
            }
        },



        //alert
        /**
         * cvAlert 警告框
         * @param Object propsData 组件实例化参数
         * @return Vue Component instance 组件实例
         */
        async alert(content, type='info', label='', propsData = {}) {
            propsData.type = type;
            propsData.label = label;
            propsData.content = content;
            let is = this.$is,
                isd = is.defined,
                evs = 'cancel,confirm'.split(','),
                evhs = {};
            for (let evi of evs) {
                if (isd(propsData[evi]) && is(propsData[evi], 'function,asyncfunction')) {
                    evhs[evi] = propsData[evi];
                    Reflect.deleteProperty(propsData, evi);
                }
            }
            if (!is.empty(evhs)) {
                if (!isd(propsData.customEventHandler)) propsData.customEventHandler = {};
                propsData.customEventHandler = this.$extend(propsData.customEventHandler, evhs);
            }
            //console.log(propsData);
            return await this.$invoke('cv-alert', propsData);
        },
        error(content, label='', opt={}) {return this.alert(content, 'error', label, opt)},
        success(content, label='', opt={}) {return this.alert(content, 'success', label, opt)},
        warn(content, label='', opt={}) {return this.alert(content, 'warn', label, opt)},
        confirm(content, label='', opt={}) {return this.alert(content, 'confirm', label, opt)},
        danger(content, label='', opt={}) {
            label = label=='' ? '这是危险操作，请确认' : label;
            opt = this.$extend({
                icon: 'vant-error-fill',
                iconColor: 'red',
                confirmBtnParams: {
                    label: '知道了，确认操作'
                }
            }, opt);
            return this.alert(content, 'confirm', label, opt);
        },
        async awaitDanger(content, label='', opt={}) {
            return new Promise((resolve, reject) => {
                label = label=='' ? '这是危险操作，请确认' : label;
                opt = this.$extend({
                    icon: 'vant-error-fill',
                    iconColor: 'red',
                    confirmBtnParams: {
                        label: '知道了，确认操作'
                    },
                    customEventHandler: {
                        confirm: () => {
                            return resolve();
                        }
                    }
                }, opt);
                return this.alert(content, 'confirm', label, opt);
            });
        },

        /**
         * 窗口
         * @return 组件实例
         */
        async win() {},

        //pop panel
        //async poppanel(triggerBy, opt={}) {

        //},
        //popmenu
        async popmenu(triggerBy, opt={}) {
            opt = this.$extend({
                triggerBy
            }, opt);
            let comp = await this.$invoke('cv-popmenu', opt),
                mslv = event => {
                    comp.startClosing();
                    triggerBy.removeEventListener('mouseleave', mslv);
                };
            if (this.$is.elm(triggerBy)) {
                triggerBy.addEventListener('mouseleave', mslv);
            }
            return comp;
        },
    }
}