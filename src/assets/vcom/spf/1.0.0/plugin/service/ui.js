/**
 * Vcom 组件库插件 服务组件
 * Vue.service.ui
 */

import baseService from 'base.js';
export default {
    mixins: [baseService],
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
            
            //是否开启动画
            animated: true,

            //iconset 此组件库使用的 图标库
            iconset: {
                //是否启用
                enable: true,
                /*
                'md-round': ['close', 'check', ...],
                ...
                */
            },

            //mask 全局遮罩层系统
            mask: {
                //layout-mask 组件 props 可通过 Vue.service.options.ui.mask.props 外部指定
                props: {
                    type: 'black',
                    alpha: 'normal',
                    blur: false,
                    loading: false,
                    clickOff: true,
                    animateType: 'fadeIn',

                    //事件处理
                    on: {
                        'mask-on': () => this.whenMaskOn(),
                        'mask-off': () => this.whenMaskOff(),
                    },
                },
                //layout-mask 组件实例
                ins: null,
            },

            //全局弹出层统一从 $ui 取得 z-index
            zIndex: 1000,       //当前系统级 zIndex
            zIndexOrigin: 1000, //从 1000 开始增加，可通过 Vue.service.options.ui.zIndexOrigin 外部修改

            //request status
            request: {
                waiting: false,
                success: false,
                error: false
            },



            /**
             * !! for Dev
             */
            testTabList: [
                {
                    key: 'index',
                    label: '首页',
                },
                {
                    key: 'foo',
                    icon: 'content-paste-off',
                    label: '页面测试'
                },
                {
                    key: 'bar',
                    label: '更多页面'
                },
                {
                    key: 'jaz',
                    label: '<span class="fc-red">红字</span>标题'
                }
            ],
            testTabActive: 'index',
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
            this.combineOptions(options);

            
            
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
         * !! Vue.service.* 可实现的 afterRootCreated 方法，必须 async
         * !! 将在 Vue.$root 根组件实例创建后自动执行
         * @param {Vue} root 当前应用的 根组件实例，== Vue.$root
         * @return {Boolean} 必须返回 true|false 表示初始化 成功|失败
         */
        async afterRootCreated(root) {
            //创建 mask 组件实例
            await this.initMask();

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



        /**
         * iconset 相关
         */
        //判断给定的 icon 图标名属于哪一个图标库 找到则返回相应的图表数据 未找到则返回 null
        iconInSet(icon, defaultIconset = null) {
            let is = this.$is,
                isets = this.iconset,
                icns = Object.keys(isets).filter(i=>i!=='enable'),
                //返回值
                rtn = {
                    //所在图标库名称
                    iconset: null,
                    //完整图标名
                    full: null,
                    //去除图标库前缀的短图标名
                    short: null
                };
                
            this.$each(icns, (icos, icn) => {
                if (icon.startsWith(icn)) {
                    rtn.iconset = icn;
                    rtn.full = icon;
                    rtn.short = icon.replace(`${icn}-`, '');
                    return false;
                }
            });
            //找到则返回
            if (!is.null(rtn.iconset)) return rtn;

            //在默认的 iconset 中查找
            if (is.string(defaultIconset) && is.defined(isets[defaultIconset])) {
                let dft = isets[defaultIconset];
                if (dft.includes(icon)) {
                    rtn.iconset = defaultIconset;
                    rtn.full = `${defaultIconset}-${icon}`;
                    rtn.short = icon;
                    return rtn;
                }
            }

            //未找到
            return null;
        },



        /**
         * mask 全局遮罩层
         */
        //创建全局遮罩层实例，应在 Vue.$root 创建后执行，其父组件为 Vue.$root
        async initMask() {
            let mask = await this.$dc.invoke('base-layout-mask', this.mask.props);
            this.mask.ins = mask;
        },
        //
        /**
         * 显示遮罩层，同时指定 zIndex
         * 可以通过 maskProps 修改 mask 组件样式，例如：
         *  {
         *      blur: true,
         *      type: 'primary',
         *      # 额外的事件处理 都会被作为 once 一次性事件
         *      on: {
         *          'mask-on': function() { ... },
         *          'mask-off': function() { ... },
         *      }
         *  }
         */
        async maskOn(maskProps={}, zIndex=null) {
            let is = this.$is,
                mask = this.mask.ins;
            if (is.vue(mask)) {
                //设置 zIndex
                if (is.elm(mask.$el)) mask.setZindex(zIndex);
                //将 maskProps 写入 mask 组件实例
                if (is.plainObject(maskProps) && !is.empty(maskProps)) {
                    //!! 自动处理 maskProps 中的 on 事件参数，强制注册为 once
                    await mask.dcSet(maskProps, true);  
                }

                //如果 mask 已隐藏
                if (mask.isDcShow !== true) {
                    //调用 dcShow 显示动画
                    await mask.dcShow();
                    //触发 mask-on 事件
                    mask.$emit('mask-on');
                }
                return true;
            }
            return false;
        },
        /**
         * 隐藏遮罩层
         * 可以自定义一个 mask-off once 事件处理方法
         * !! 不推荐在此处定义事件，建议在 maskOn 方法中统一传入自定义事件
         * @param {Function} callback 
         * @return {Boolean}
         */
        async maskOff(callback=null) {
            let is = this.$is,
                mask = this.mask.ins;
            //如果指定了 callback 则注册到 once 事件
            if (is(callback, 'function,asyncfunction')) {
                await this.$dc.on(mask, {
                    'mask-off': callback
                }, true);
            }
            if (is.vue(mask)) {
                if (mask.isDcShow !== false) {
                    //调用 dcHide 隐藏动画
                    await mask.dcHide();
                    //触发 mask-off 事件
                    mask.$emit('mask-off');
                }
                return true;
            }
            return false;
        },
        /**
         * 显示遮罩层，指向某个 win 窗口实例，表示当前 mask 作为此窗口的遮罩层
         * 可以通过 maskProps 修改 mask 组件样式，与 maskOn 方法参数一致
         */
        async maskFor(win=null, maskProps={}) {
            let is = this.$is;
            //如果指定了 win 组件
            if (is.vue(win) && is.elm(win.$el)) {
                //先显示并设置 mask 的 zIndex
                await this.maskOn(maskProps);
                //设置当前窗口的 zIndex
                win.setZindex();
                //win.$el.style.zIndex = this.getZindex();
                return true;
            }
            //未指定 win 组件，直接返回
            return false;
        },
        //mask-on|off 默认事件 每次 on|off 都会执行
        whenMaskOn() {
            this.$log.success('mask-on ok');
        },
        whenMaskOff() {
            let is = this.$is,
                mask = this.mask.ins,
                oprops = Object.assign({}, this.mask.props);
            if (!is.vue(mask)) return;

            //将 mask 组件实例的 props 恢复为默认值
            if (is.defined(oprops.on)) Reflect.deleteProperty(oprops, 'on');
            mask.dcSet(oprops);

            //log
            this.$log.success('mask-off ok');
        },



        /**
         * 全局 zIndex
         */
        //获取 zindex 并自增
        getZindex() {
            //首先尝试 restore
            this.restoreZindex();
            this.zIndex += 1;
            return this.zIndex;
        },
        //每次获取 系统级 zindex 时，检查是否存在浮动层，如果不存在任何浮动层，则恢复 ui.zindex 到初始值 1000
        restoreZindex() {
            let is = this.$is,
                wl = this.$win.list || [],
                mask = this.mask.ins;
            if (wl.length<=0 && (!is.vue(mask) || mask.isDcShow!==true)) {
                //窗口系统中没有已开启的窗口实例，且 mask 未显示，表示当前没有任何浮动层
                this.zIndex = this.zIndexOrigin;
            }
        },
        //获取某个 组件实例的 zIndex



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



        /**
         * UI 工具方法
         */
        
        /**
         * size 尺寸系统工具
         */

        /**
         * 判断传入的 size 参数的形式，可以有 5 中参数形式
         *      medium|normal       str     尺寸字符形式，在 sizeMap 中定义了键名的
         *      m|s|xl              key     尺寸键形式，在 sizeMap 中定义了键值的
         *      fs.m|btn.xl         csv     在 $ui.cssvar.size 中定义了项目的
         *      32px|100vw|75%      css     可直接在 css 中使用的 尺寸值字符串
         *      72|128              num     纯数字，自动增加 px 单位
         * @param {String|Number} size
         * @return {String|null} 返回尺寸值类型 str|key|csv|css|num 
         */
        sizeType(size=null) {
            let is = this.$is,
                isd = is.defined,
                csv = this.cssvar.size,
                szs = this.cssvar.extra.size.sizeStrMap,
                sz = (is.null(size) || (!is.string(size) && !is.realNumber(size))) ? null : size;
            if (is.null(sz)) return null;
            //在 sizeMap 中定义了键的  --> str
            if (isd(szs[sz])) return 'str';
            //在 sizeMap 中定义了值的  --> key
            if (Object.values(szs).includes(sz)) return 'key';
            //在 $ui.cssvar.size 中定义了项目值的
            if (!is.undefined(this.$cgy.loget(csv, sz))) return 'csv';
            //纯数字
            if (is.realNumber(sz)) return 'num';
            //32px|100vw|75% 形式
            if (is.numeric(sz)) return 'css';
            //其他的默认作为 css 尺寸值字符串，calc(var(--size-btn-s) * 2) ...
            return 'css';
        },

        //判断是否合法的 size key  m|s|l|xxs|xl 形式
        isSizeKey(skey) {
            let is = this.$is,
                sks = 's,m,l'.split(',');
            if (!is.string(skey) || skey==='') return false;
            let len = skey.length,
                fs = skey.substring(0,1),
                ls = skey.substring(len-1);
            if (len===1 && sks.includes(skey)!==true) return false;
            if (len>1 && (fs!=='x' || sks.includes(ls)!==true)) return false;
            return true;
        },
        /**
         * 尺寸 key 解析为数组
         * xxl  --> ['xxl', 'l', 3]
         * m    --> ['m', 'm', 0]
         * s    --> ['s', 's', 1]
         */
        sizeKeyParse(skey) {
            if (!this.isSizeKey(skey)) return null;
            let len = skey.length;
            return [skey, skey.substring(len-1), len];
        },
        /**
         * 将 str|key 形式的 size 值，缩放指定的级数
         * l1-->放大1级  s2-->缩小2级
         *      sizeKeyShiftTo('xxl', 's1')     --> xl
         *      sizeKeyShiftTo('xxl', 's3')     --> m
         *      sizeKeyShiftTo('xxl', 's5')     --> xs
         *      sizeKeyShiftTo('m', 's3')       --> xxs
         *      sizeKeyShiftTo('m', 's9')       --> xxs
         * @param {String|Number} size 只能传入 str|key 形式的尺寸值
         * @param {String} shift 缩放级数 l1|s2...
         * @return {String} 缩放后的尺寸 key  m|s|xxl 形式 可作为 size 参数传递到组件内部的其他组件上
         */
        sizeKeyShiftTo(size, shift) {
            let is = this.$is,
                sztp = this.sizeType(size);
            //必须传入 str|key 形式的 size 值
            if (['str','key'].includes(sztp)!==true) return null;
            let skey = sztp==='str' ? this.sizeStrToKey(size) : size;
            if (!this.isSizeKey(skey)) return null;
            if (!is.string(shift) || shift.length!==2) return null;
            let skl = this.sizeKeyParse(skey);
            if (is.empty(skl)) return null;
            let otp = skl[1],           // xxl --> l
                olvl = skl[2] * 1,      // xxl --> 3
                sfl = shift.split(''),  // l2 --> [l, 2]
                sftp = sfl[0],          // l
                slvl = sfl[1] * 1,      // 2
                ntp = '',
                nlvl = 0;

            if (otp === sftp) {
                ntp = otp;
                nlvl = olvl + slvl;
            } else {
                if (otp === 'm') {
                    ntp = sftp;
                    nlvl = slvl;
                } else {
                    nlvl = olvl - slvl;
                    if (nlvl === 0) {
                        ntp = 'm';
                    } else {
                        ntp = otp==='s' ? 'l' : 's';
                        nlvl = Math.abs(nlvl);
                    }
                }
            }
            //级数范围 0-3
            nlvl = nlvl>3 ? 3 : (nlvl<0 ? 0 : nlvl);
            //输出
            if (nlvl<=1) return ntp;
            let kl = [ntp];
            for (let i=0;i<nlvl-1;i++) {
                kl.unshift('x');
            }
            return kl.join('');
        },

        //判断传入的 size 是否合法的 css 尺寸值 100px 数字+单位 形式
        isSizeVal(size) {
            let is = this.$is;
            if (!is.numeric(size)) return false;
            return true;
        },
        /**
         * 根据传入的 size 参数，以及可能存在的 size 用途(btn|bar...) 获取对应的 size 值
         * @param {String|Number} size
         * @param {String} csvKey size 用途，在 cssvar.size 中定义的键名
         * @return {String|null} 返回的是 带有单位的 css 尺寸字符串 如：100px|50%|100vw ...
         */
        sizeVal(size, csvKey=null) {
            let is = this.$is,
                lgt = this.$cgy.loget,
                sztp = this.sizeType(size);
            if (is.null(sztp)) return null;
            let csv = this.cssvar.size;
            if (sztp==='css') return size;
            if (sztp==='num') return size+'px';
            if (sztp==='csv') return lgt(csv, size);
            if (!is.string(csvKey) || !is.defined(csv[csvKey])) return null;
            let skey = sztp==='str' ? this.sizeStrToKey(size) : size;
            if (!is.defined(csv[csvKey][skey])) return null;
            return lgt(csv, `${csvKey}.${skey}`);
        },

        //size str 转为 key   huge|large|medium|normal  -->  xxl|xl|l|m
        sizeStrToKey(str) {
            let is = this.$is,
                szmap = this.cssvar.extra.size.sizeStrMap;
            if (!is.defined(szmap[str])) return null;
            return szmap[str];
        },
        //size key 转为 str   xxl|xl|l|m  -->  huge|large|medium|normal
        sizeKeyToStr(key) {
            let is = this.$is,
                szmap = this.cssvar.extra.size.sizeStrMap,
                szs = Object.values(szmap);
            if (!szs.includes(key)) return null;
            let str = null;
            this.$each(szmap, (v,k) => {
                if (v === key) {
                    str = k;
                    return false;
                }
            });
            return str;
        },
        //实际尺寸值 转为 key 如果在 cssvar.size.csvKey{} 中未找到，则返回 null
        sizeValToKey(size, csvKey=null) {
            let is = this.$is,
                csv = this.cssvar.size;
            //传入的 size 必须是 numeric 形式 100px|100
            if (!this.isSizeVal(size)) return null;
            if (!is.string(csvKey) || csvKey==='' || is.undefined(csv[csvKey]) || !is.plainObject(csv[csvKey])) return null;
            if (is.realNumber(size)) size = size+'px';
            let key = null;
            this.$each(csv[csvKey], (v,k) => {
                if (v === size) {
                    key = k;
                    return false;
                }
            });
            return key;
        },

        //将尺寸值 转为 [ 数字, 单位 ] 数组
        sizeToArr(sz) {
            let is = this.$is;
            //只有 100px 形式的尺寸值才可以拆分为 [数字, 单位]
            if (!this.isSizeVal(sz)) return null;
            //默认单位 px
            if (!is.realNumber(sz)) sz = sz+'px';

            //字符串拆分数组
            let szarr = sz.split(''),
                //保存数字和小数点
                sznarr = [];
            for (let i=0;i<szarr.length;i++) {
                let szi = szarr[i];
                if (is.realNumber(szi) || szi === '.') {
                    sznarr.push(szi);
                } else {
                    break;
                }
            }
            if (sznarr.length<=0) return [];
            let szn = sznarr.join(''),
                szu = sz.replace(szn, '');
            return [szn, szu];
        },

        //根据 bar 单行元素的 高度，计算合适的内部字体尺寸
        sizeCalcBarFs(size) {
            let is = this.$is,
                // 72px --> [72, 'px']
                szl = this.sizeToArr(size);
            //无法解析尺寸值，则原样返回
            if (!is.array(szl) || szl.length<=0) return size;
            let szn = szl[0],
                szu = szl[1] || '',
                //!! 字号 = 行高/2.3
                fs = Math.round(szn/2.3);
            return `${fs}${szu}`;
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
        async __win() {},

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