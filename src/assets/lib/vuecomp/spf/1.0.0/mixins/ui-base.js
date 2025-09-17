/**
 * cv-*** 组件 ui 工具集
 * 通过 this.$ui.**** 访问方法/属性
 */

export default {
    props: {
        
    },
    data() {
        return {
            /**
             * theme options
             */
            theme: 'base',
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
            vueuiThemes: {},
            
            //是否开启动画
            animated: false,


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
         * ui 整体初始化
         */
        async init(options) {
            //theme init
            await this.themeInit(options);
            //创建 mask 实例
            if (!this.$is.vue(this.mask)) {
                Vue.$invokeComp('cv-mask').then(comp => {
                    this.mask = comp;
                });
            }

            this.inited = true;
        },



        /**
         * theme methods
         */
        //theme 初始化
        async themeInit(options) {
            //console.log('ui options', Object.assign({}, options));
            //处理 options
            //保存此插件使用的组件库名称 []
            if (cgy.is.array(options.globalComponents) && options.globalComponents.length>0) {
                this.compPackage.splice(0);
                this.compPackage.push(...options.globalComponents);
                Reflect.deleteProperty(options, 'globalComponents');
            } 
            //如果在 options 中定义了要加载的 vue ui 库
            if (cgy.is.defined(options.vueui) && options.vueui.length>0) {
                this.vueui.splice(0);
                this.vueui.push(...options.vueui);
                Reflect.deleteProperty(options, 'vueui');
            }
            //如果在 options 中定义了 vueui 库使用的 样式主题
            if (cgy.is.defined(options.vueuiThemes) && !cgy.is.empty(options.vueuiThemes)) {
                this.vueuiThemes = Object.assign(options.vueuiThemes);
                Reflect.deleteProperty(options, 'vueuiThemes');
            }
            //如果在 options 中指定了 theme，则 调用 Vue.theme.use(theme)
            if (cgy.is.defined(options.theme)) {
                await this.useTheme(options.theme, options.themeOptions);
                Reflect.deleteProperty(options, 'theme');
                Reflect.deleteProperty(options, 'themeOptions');
            }
            await cgy.wait(100);
        },
        /**
         * 应用样式主题，必须在根组件实例化之前执行
         * @param String theme 
         * @return void
         */
        async useTheme(theme = 'base', themeOptions = {}) {
            this.theme = theme;
            for (let i in themeOptions) {
                if ('cssvar,iconPackage'.split(',').includes(i)) continue;
                this.$set(this.themeInfo, i, themeOptions[i]);
            }

            //与 localStorage 同步 darkMode 标记
            let lcsDarkMode = cgy.localStorage.$get('cv_dark_mode');
            if (!lcsDarkMode) {
                cgy.localStorage.$set('cv_dark_mode', JSON.stringify(this.darkMode));
            } else {
                this.darkMode = JSON.parse(lcsDarkMode);
            }

            //修改 cssvar
            let cssvar = themeOptions.cssvar;
            cssvar.color = Object.assign({}, cssvar.colorTheme[this.darkMode?'dark':'light']);
            cssvar.darkMode = this.darkMode;
            this.cssvar = Object.assign({}, cssvar);
            window.cssvar = this.cssvar;

            //加载/切换主题 css 文件
            await this.setThemeStyleLink();
            //加载 icon 图标包
            await this.setThemeIconPackage(themeOptions.iconPackage);

            await cgy.wait(10);
        },
        /**
         * 切换暗黑模式
         * @return void
         */
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            let dm = this.darkMode;
            //写入 localstorage
            cgy.localStorage.$set('cv_dark_mode', JSON.stringify(dm));
            //切换 cssvar
            this.cssvar.color = Object.assign({}, this.cssvar.colorTheme[dm?'dark':'light']);
            this.cssvar.darkMode = dm;
            //加载/切换主题 css 文件
            this.setThemeStyleLink();
            //console.log(this.cssvar);
        },
        /**
         * 添加样式主题 css 文件到 head
         * @return void
         */
        async setThemeStyleLink() {
            //加载css
            let vtc = this.theme,
                vcm = this.darkMode==true? 'dark' : 'light',
                vui = this.vueui,
                vuiths = this.vueuiThemes,
                comps = this.compPackage,
                cssid = `cv_${vtc}_${vcm}_css`,
                cssido = `cv_${vtc}_${vcm=='dark'?'light':'dark'}_css`,
                lib = `${Vue.lib}/vue/@`;
            if (vui.length>0) {
                for (let i=0;i<vui.length;i++) {
                    let vuii = vui[i],
                        vuith = cgy.is.defined(vuiths[vuii]) ? vuiths[vuii] : '',
                        vuin = vuii.replace(/\-/g,'_'),
                        uicssid = `cv_${vuin}_${vuith==''?'':vuith+'_'}${vcm}_css`,
                        uicssido = `cv_${vuin}_${vuith==''?'':vuith+'_'}${vcm=='dark'?'light':'dark'}_css`;
                    cgy.appendStyleLink(uicssid, `${lib}/ui/${vuii}/@/${vuith==''?'':vuith+'-'}${vcm}.css`);
                    cgy.removeStyleLink(uicssido);
                }
            }
            await cgy.wait(50);
            //加载当前主题的 css 
            //自动生成的，同时附加了所有组件库的样式
            //css href like：io.cgy.design/vue/@/themes/base/light.css?comps=base,foo,bar
            let thcss = `${lib}/themes/${vtc}/${vcm}.css`;
            if (comps.length>0) {
                //加载 组件库的 css
                thcss += `?comps=${comps.join(',')}`;
            }
            cgy.appendStyleLink(cssid, thcss);
            cgy.removeStyleLink(cssido);
            await cgy.wait(50);
        },
        /**
         * 加载 iconPackage 图标包
         * @return void
         */
        async setThemeIconPackage(iconPackage=[]) {
            let iconset = iconPackage;
            if (iconset=='') iconset = [];
            if (cgy.is.string(iconset)) iconset = [iconset];
            if (!cgy.is.array(iconset) || cgy.is.empty(iconset)) iconset = [];
            iconset = [ ...this.iconPackage.clone(), ...iconset];
            iconset = iconset.unique();
            let hd = document.querySelector('head');
            for (let ico of iconset) {
                let icoid = `cv_iconset_${ico}`,
                    icojs = document.querySelector(`#${icoid}`);
                if (cgy.is.empty(icojs) || !icojs.nodeName || icojs.nodeName!='SCRIPT') {
                    icojs = document.createElement('script');
                    icojs.setAttribute('id',icoid);
                    icojs.setAttribute('src',`${Vue.lib}/icon/${ico}/js`);
                    hd.appendChild(icojs);
                }
            }
            this.iconPackage.splice(0);
            this.iconPackage.push(...iconset);
            await cgy.wait(50);
        },
        //改变 colorType
        changeColorType(colorType) {
            if (this.cssvar.colorType!==colorType) {
                this.$set(this.cssvar, 'colorType', colorType);
            }
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