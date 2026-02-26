<template>
    <PRE@-block
        v-bind="$attrs"
        :size="size"
        :grow="grow"
        :scroll="scroll"
        v-slot="{styProps, cntParams}"
    >
        <template v-if="manual">
            <slot v-bind="{styProps, cntParams}"></slot>
        </template>
        <template v-if="!manual && inited && currentMenus.length>0">
            <template v-for="(menui, midx) of currentMenus">
                <PRE@-menu-item
                    v-if="!menui.params.hide"
                    :key="'__PRE__-menu-item-'+midx"
                    :size="styProps.size"
                    :color="color"
                    :background="background"
                    :border="border"
                    :shape="shape"
                    :label="menui.label"
                    :icon="menui.icon"
                    :sub="menui.sub"
                    v-bind="menui.params"
                    :menu-comp="$this"
                    :compact="compact"
                    @menu-item-toggle-collapse="whenMenuItemToggleCollapse"
                    @menu-item-click="whenMenuItemClick"
                    @compact-menu-item-click="whenCompactMenuItemClick"
                ></PRE@-menu-item>
            </template>
        </template>
    </PRE@-block>
</template>

<script>
export default {
    model: {
        prop: 'menus',
        event: 'menu-change',
    },
    props: {
        /**
         * 菜单列表样式
         */
        //菜单项单行高度 cssvar.size.bar.* 定义的尺寸  xl|m|large|normal...
        size: {
            type: String,
            default: 'normal'
        },
        //菜单列表的颜色主题 cssvar.color{} 中定义的颜色
        color: {
            type: String,
            default: 'primary'
        },
        //菜单列表的背景色 cssvar.color{} 中定义的颜色
        background: {
            type: String,
            default: 'bgc'
        },
        //菜单项的边框样式原子类  默认 '' 表示不显示边框  可选 bd-m bd-po-tb bdc-m ...
        border: {
            type: String,
            default: ''
        },
        //菜单项是否使用特殊形状 sharp(默认)|round|pill
        shape: {
            type: String,
            default: 'sharp'
        },
        //是否占满纵向空间 默认占满
        grow: {
            type: Boolean,
            default: true
        },
        //是否显示滚动条  默认显示  ''|thin|bold
        scroll: {
            type: String,
            default: 'thin'
        },

        /**
         * 手风琴模式，同时只允许一个 一级菜单项处于展开状态
         * !! 如果某个菜单项中的某个子菜单处于 active 状态，则不折叠
         */
        accordion: {
            type: Boolean,
            default: false
        },

        //菜单列表整体是否处于 compact 横向折叠状态  通常配合 __PRE__-layout-x 组件使用
        compact: {
            type: Boolean,
            default: false
        },

        /**
         * 菜单列表参数集合 []
         * 当 manual != true 时，将根据此参数，自动生成所有菜单项组件实例
         */
        menus: {
            type: Array,
            default: () => [
                //每个菜单项参数格式 与 dftMenuOpts{} 一致
                //{}, ...
            ]
        },
        //指定 子菜单懒加载模式 的数据源 api 为空表示不开启 懒加载模式
        subLazyloadApi: {
            type: String,
            default: ''
        },
        //指定菜单项的 notice 数据源 api
        noticeApi: {
            type: String,
            default: ''
        },
        //菜单项 notice 检查频率  默认 10 分钟
        noticeCheckFrequency: {
            type: Number,
            default: 600000
        },

        /**
         * 菜单项参数
         */
        //指定菜单项 展开后 是否显示边框
        /*itemWithBd: {
            type: Boolean,
            default: false
        },
        //指定边框样式
        itemBd: {
            type: String,
            default: ''
        },
        //itemBdPo: {
        //    type: String,
        //    default: ''
        //},
        itemBdc: {
            type: String,
            default: ''
        },*/
        //可额外指定 菜单项 header 栏的样式参数
        itemParams: {
            type: Object,
            default: () => {
                return {/*
                    headerParams: {
                        iconParams: {...},
                        color: 'random',
                        ...
                    },
                    ...
                */};
            }
        },

        /**
         * 是否通过 slot 默认插槽 手动输入菜单项组件
         */
        manual: {
            type: Boolean,
            default: false
        },

    },
    data() {return {
        //定义默认的菜单项参数
        dftMenuOpts: {
            //!! 此菜单项正在 inited 在多个 __PRE__-menu 组件共用一个 menus 数据源时，防止数据处理错误
            initing: false,
            inited: false,

            key: '',            //menu-key 全局唯一
            label: '',          //菜单项标签
            icon: '',           //菜单项图标
            //子菜单项 []
            sub: [
                //相同数据格式
                //{}, ...
            ],
            //标记此菜单项的子菜单启用 懒加载模式
            subLazyload: false,
            //标记此菜单项的 notice 通知
            notice: {
                type: 'number',     //通知类型：number|dot|icon
                value: 0,           //通知数据：数值|true,false|图标名称
            },

            //菜单项组件参数 在 __PRE__-menu-item 组件中定义的 props
            //!! 可通过 props.itemParams 传入自定义参数，覆盖此处的默认值
            //!! 将通过 v-bind 传入 __PRE__-menu-item 组件中
            params: {
                //固定参数 自动生成，无需指定
                //menuKey: '',
                //keyChain: [],     # menu-key-chain 菜单键名链
                //idx: -1,          # 此菜单项 在父菜单项 sub 列表中的 idx 序号
                //idxChain: [],     # menu-idx-chain 菜单项序号链
                //lazyload: false,  # 此菜单是否懒加载
                //菜单项状态
                collapse: true,
                disabled: false,
                active: false,
                lazyLoading: false,
                //过滤设置
                hide: false,

                //默认的 菜单项 header 参数，定义通用的 菜单项样式
                headerParams: {
                    iconParams: {
                        color: 'random',    //默认 random 表示随机颜色，可选：primary|danger|success|...|blue|red|bz...
                        //size: 'm',
                    },
                    //菜单项 悬停|选中 时的颜色  与 菜单颜色主题一致
                    //color: 'primary',        //默认 primary 
                    //type: '',
                },

                //自动处理后的 菜单 notice  默认值
                notice: {
                    //自动判断是否显示 notice 标记
                    show: false,
                    type: 'number',
                    value: 0,
                    //显示的值，例如: number 类型 notice 超过 9|99|999 显示为 9+|99+|999+
                    showValue: '',
                },

                //其他 __PRE__-menu-item 定义的 props ...
                //headerLabelActiveStyle: 'font-weight: bold;',
                //headerLabelExpandStyle: 'font-weight: bold; color: var(--color-primary-m);',
                //...
            },


            //菜单项动作 function
            cmd: null,
            //如果是导航菜单，此处设置 跳转 route 如果 cmd 和 route 都未指定，则使用 keyChain
            route: '',


            
            //不含子菜单 标记
            //noSub: true,
        },

        //根据外部传入的 menus 参数列表，缓存当前的 菜单项列表的 完整参数以及状态数据
        currentMenus: [],
        //当前 active 菜单项的 idxChain
        activeMenuIdxChain: [],

        //初始化 menus 标记
        //进行中
        initing: false,
        //已初始化完成
        inited: false,

        //更新中 标记
        updating: false,

        //定时检查 notice
        noticeChecking: null,
    }},
    computed: {
        
    },
    watch: {
        /**
         * 当外部传入的 menus 菜单列表参数发生改变时
         */
        menus: {
            handler(nv, ov) {
                if (this.inited!==true) {
                    //初始化 menus 缓存到 currentMenus
                    this.initMenus();
                } else {
                    //已初始化过 通过 v-model 同步外部 menus 与 currentMenus
                    this.updateMenus();
                }
            },
            //创建时立即执行
            immediate: true,
        },

        //监听外部传入的 compact
        compact(nv, ov) {
            //只有在 初始化完成后才会处理
            if (this.initing || !this.inited) return false;
            this.toggleCompact().then(()=>{
                //触发 menu-change 事件 将处理后的 currentMenus 回传至外层组件
                this.$emit('menu-change', this.currentMenus);
            });
        },

        //监听 inited 初始化完成后，执行 notice check
        inited(nv, ov) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='';
            if (!iss(this.noticeApi) || this.noticeCheckFrequency<=0) return;
            //必须在 inited 之后
            if (this.inited && this.$is.null(this.noticeChecking)) {
                //立即执行一次
                this.loadMenuNotice().then(()=>{
                    //创建 interval
                    this.noticeChecking = setInterval(
                        this.loadMenuNotice,
                        this.noticeCheckFrequency
                    );
                });
            }
        },
    },
    methods: {
        /**
         * 将传入的 menus 参数填充为完整的 menuOpts{} 格式，
         * 缓存到 currentMenus
         */
        async initMenus(menus=[]) {
            //如果 manual == true 不执行
            if (this.manual===true) {
                //清空缓存
                this.currentMenus.splice(0);
                this.$wait(10);
                return false;
            }
            //如果 正在初始化过程中 或 已经初始化过  跳过
            if (this.initing===true || this.inited===true) return false;
            
            //标记 初始化进行中
            this.initing = true;

            let is = this.$is,
                //iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m),
                isim = m => this.isInitedMenuOpt(m),
                fail = () => {
                    //清空缓存
                    this.currentMenus.splice(0);
                    this.initing = false;
                    return false;
                },
                curs = [];

            //未传入有效的 menus 列表 
            if (!isa(menus)) menus = this.menus;
            if (!isa(menus)) return fail();
            //开始依次使用 默认值填充传入的 menus 参数
            await this.$each(menus, async (mi, i) => {
                //必须是合法的 menu 参数
                if (!ism(mi)) return true;
                //如果此参数已经初始化
                if (isim(mi)) {
                    curs.push(mi);
                    return true;
                }
                let opt = await this.initMenuOpt(mi, {}, i);
                if (!isim(opt)) return true;
                curs.push(opt);
            });
            //写入 currentMenus 缓存
            this.currentMenus.splice(0);
            if (isa(curs)) this.currentMenus.push(...curs);
            await this.$wait(10);

            //处理外部传入的 compact 横向折叠
            if (this.compact===true) {
                await this.toggleCompact();
            }

            //触发 menu-change 事件 将处理后的 currentMenus 回传至外层组件
            this.$emit('menu-change', this.currentMenus);
            //给外部处理添加一个缓冲时间，
            await this.$wait(100);

            //完成初始化
            this.initing = false;
            //以及初始化过 标记
            this.inited = true;
            return true;
        },
        //init 初始化某个菜单项 返回处理后的 menuOpt{} 菜单项参数
        async initMenuOpt(opts={}, parentOpts={}, idx=-1) {
            let is = this.$is,
                ext = this.$extend,
                //iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                ism = m => this.isMenuOpt(m),
                isim = m => this.isInitedMenuOpt(m);
            //首先确保传入的参数有效
            if (!ism(opts) || idx<0) return null;

            //准备生成标准的 menuOpt{} 参数
            let dft = this.$extend({}, this.dftMenuOpts),
                //此菜单项的父级菜单 menuOpt{}
                pm = !ism(parentOpts) ? {} : parentOpts,
                //外部传入的 itemParams
                ips = iso(this.itemParams) ? this.itemParams : {},
                //此菜单项的子菜单 []
                sub = isa(opts.sub) ? opts.sub : [],
                rtn = {};

            //去除 dft 中的 sub 项 防止 extend 出错
            if (isa(dft.sub)) Reflect.deleteProperty(dft, 'sub');
            if (iso(dft.params) && isa(dft.params.sub)) Reflect.deleteProperty(dft.params, 'sub');
            //去除 opts 中的 sub 项 防止 extend 出错
            if (isa(opts.sub)) Reflect.deleteProperty(opts, 'sub');
            
            //menu-key 必须是 foo-bar 形式，因此需要 fooBar --> foo-bar
            let menuKey = opts.key.toSnakeCase('-');

            //合并 dft.params, menus[*], props.itemParams 
            rtn = ext(
                rtn,    //空值
                dft,    //默认值
                {       //使用 props.color 覆盖 dft.params.headerParams.iconParams.color
                    params: {
                        headerParams: {
                            iconParams: {
                                color: this.color || 'random',
                            }
                        }
                    }
                },
                {       //外部传入的 通用 itemParams{}
                    params: ips
                },
                opts,   //外部传入的 menus[] 中的当前 menu 的设置值 {}
                {       //菜单参数的 固定值
                    key: menuKey,
                    params: {
                        menuKey,
                        idx,
                    }
                }
            );
            //console.log(dft,opts,ips,rtn);
            await this.$wait(5);
            
            //生成 keyChain|idxChain
            let kc = [],    
                idxc = [];
            if (ism(pm)) {
                //存在父菜单，则合并父菜单的 key|idxChain
                if (iso(pm.params) && isa(pm.params.keyChain)) kc = [...pm.params.keyChain];
                if (iso(pm.params) && isa(pm.params.idxChain)) idxc = [...pm.params.idxChain];
            }
            //合并当前菜单的 key|idx
            kc.push(menuKey);
            idxc.push(idx);
            //合并到 rtn.params
            rtn.params = ext(rtn.params, {
                keyChain: kc,
                idxChain: idxc
            });

            //处理 sub 子菜单项
            if (isa(sub)) {
                let nsub = [];
                //递归处理子菜单
                await this.$each(sub, async (mi, i) => {
                    if (!ism(mi)) return true;
                    let opt = await this.initMenuOpt(mi, rtn, i);
                    if (!isim(opt)) return true;
                    //子菜单项写入 sub
                    nsub.push(opt);
                });
                //写入子菜单参数
                rtn.sub = nsub;
                //递归处理子菜单的 icon 参数
                rtn = this.setSubMenuIcon(rtn);
            } else {
                //不存在子菜单 则填入空 []
                rtn.sub = [];
            }
            //子菜单是否懒加载 标记
            if (this.isSubLazyload(rtn)) {
                rtn.params.lazyload = true;
            }
            
            //无子菜单，则 collapse 设为 true
            //if (!isa(rtn.sub)) {
            if (this.hasNoSub(rtn)) {
                rtn.params.collapse = true;
            }

            //处理 notice 通知
            if (iso(rtn.notice)) {
                rtn.params.notice = this.parseMenuNotice(rtn.notice);
            }

            //如果当前菜单项已被激活
            if (rtn.params.active && rtn.params.active===true) {
                //初始化时 只有 第一个被设为 active 的菜单项才会生效
                if (!isa(this.activeMenuIdxChain)) {
                    this.activeMenuIdxChain.push(...rtn.params.idxChain);
                }
            }

            await this.$wait(5);
            //返回处理结果
            return rtn;
        },

        /**
         * 菜单参数 初始化后发生改变时 update 更新 currentMenus 数据
         * 通过 watch menus 值的改变
         */
        async updateMenus(menus=[]) {
            //如果 manual == true 不执行
            if (this.manual===true) {
                //清空缓存
                this.currentMenus.splice(0);
                this.$wait(10);
                return false;
            }
            //如果 正在更新过程中 或 还未初始化过  跳过
            if (this.updating===true || this.inited!==true) return false;
            
            //标记 更新进行中
            this.updating = true;

            let is = this.$is,
                //iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m),
                isim = m => this.isInitedMenuOpt(m);

            //未传入有效的 menus 列表 
            if (!isa(menus)) menus = this.menus;
            if (!isa(menus)) return false;
            //使用 传入的 menus[] 回写到 currentMenus
            await this.$each(menus, async (mi,i) => {
                //确保 menus 中的所有值都是有效的 menuOpt
                if (ism(mi)) {
                    let opt = Object.assign({}, mi);
                    if (!isim(mi)) {
                        //传入的 menuOpt 还未经过初始化
                        opt = await this.initMenuOpt(mi, {}, i);
                        if (!isim(opt)) return true;
                    }
                    if (is.defined(this.currentMenus[i])) {
                        //currentMenus 中已存在
                        this.currentMenus.splice(i, 1, opt);
                    } else {
                        //currentMenus 中不存在
                        this.currentMenus.push(opt);
                    }
                }
            });

            //完成更新
            this.updating = false;
            return true;
        },

        /**
         * 根据 compact 横向折叠状态，修改所有 一级菜单的 collapse 状态
         * 恢复时，只有 active 的一级菜单才会恢复至 展开状态
         */
        async toggleCompact() {
            let is = this.$is,
                isim = m => this.isInitedMenuOpt(m),
                isac = m => this.isMenuActived(m),
                compact = this.compact,
                menus = this.currentMenus;
            this.$each(menus, (mi,i) => {
                if (!isim(mi)) return true;
                
                if (compact!==true) {
                    //恢复至未横向折叠状态
                    if (isac(mi)) {
                        //只有处于 active 状态的 一级菜单 才会恢复至 未纵向折叠状态
                        this.$set(mi.params, 'collapse', false);
                    } else {
                        //其他菜单 不操作
                        return true;
                    }
                } else {
                    //切换至横向折叠状态
                    this.$set(mi.params, 'collapse', true);
                }
            });
            await this.$wait(10);
            return true;
        },

        //工具方法
        //判断一个 {} 是否有效的 menuOpt 菜单参数
        isMenuOpt(opt={}) {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                iss = s => is.string(s) && s!=='';
            return iso(opt) && iss(opt.key) && iss(opt.label);
        },
        //判断一个 {} 是否有效的 已经过初始化的 menuOpt 菜单参数
        isInitedMenuOpt(opt={}) {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m);
            if (!ism(opt)) return false;
            return iso(opt.params) && isa(opt.params.keyChain) && isa(opt.params.idxChain);
        },
        //判断此菜单是否处于 active 状态，此菜单自身或某个子菜单 active 都返回 true
        isMenuActived(opt={}) {
            if (!this.isInitedMenuOpt(opt)) return false;
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                aidxc = this.activeMenuIdxChain,
                idxc = opt.params.idxChain;
            //无 active 菜单
            if (!isa(aidxc)) return false;
            let acstr = aidxc.join('-'),
                cstr = idxc.join('-');
            //根据 idxChain 序号链 判断
            return acstr===cstr || acstr.startsWith(cstr);
        },
        //判断此菜单的子菜单是否采用 懒加载模式
        isSubLazyload(opt={}) {
            if (!this.isMenuOpt(opt)) return false;
            return this.subLazyloadApi!=='' && opt.subLazyload===true;
        },
        //判断此菜单的子菜单是否采用懒加载，且还未加载
        isUnloadedSubLazyload(opt={}) {
            if (!this.isSubLazyload(opt)) return false;
            let is = this.$is,
                isa = a => is.array(a) && a.length>0;
            return !isa(opt.sub);
        },
        //判断此菜单是否存在子菜单
        hasNoSub(opt={}) {
            if (!this.isMenuOpt(opt)) return true;
            let is = this.$is,
                isa = a => is.array(a) && a.length>0;
            return !(isa(opt.sub) || this.isSubLazyload(opt));
        },
        //远程加载 lazyload 子菜单
        async loadSubMenu(opt={}) {
            if (!this.isSubLazyload(opt)) return false;
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m),
                isim = m => this.isInitedMenuOpt(m),
                api = this.subLazyloadApi;
            //加载标记
            if (opt.params.lazyLoading!==false) return false;
            this.$set(opt.params, 'lazyLoading', true);
            //同步
            this.$emit('menu-change', this.currentMenus);

            //远程加载
            /*let sub = await this.$req(api, {
                keyChain: opt.params.keyChain
            });*/

            //!! mock for Dev
            await this.$wait(1000);
            let sub = [
                {
                    key: 'menu-foo-c-c-a',
                    label: '菜单 foo-c-c-a'
                },
                {
                    key: 'menu-foo-c-c-b',
                    label: '菜单 foo-c-c-b'
                }
            ];

            if (!isa(sub)) {
                //加载失败，报错
                //...
                
                this.$set(opt.params, 'lazyLoading', false);
                this.$emit('menu-change', this.currentMenus);
                return false;
            }

            //初始化子菜单
            let nsub = [];
            //递归处理子菜单
            await this.$each(sub, async (mi, i) => {
                if (!ism(mi)) return true;
                let subi = await this.initMenuOpt(mi, opt, i);
                if (!isim(subi)) return true;
                //子菜单项写入 sub
                nsub.push(subi);
            });
            //写入子菜单参数
            if (!is.array(opt.sub)) {
                opt.sub = [];
            } else {
                opt.sub.splice(0);
            }
            opt.sub.push(...nsub);
            //递归处理子菜单的 icon 参数
            //let nopt = this.setSubMenuIcon(opt);

            //结束
            this.$set(opt.params, 'lazyLoading', false);
            this.$emit('menu-change', this.currentMenus);
            return true;
        },
        /**
         * 批量设置某个菜单项中所有子菜单的 icon
         * 如果所有子菜单都没有指定 icon 则将所有子菜单的 icon 设为为 ''
         * 如果有子菜单设置了 icon 则将其余的子菜单 icon 设置为 '-empty-'
         */
        setSubMenuIcon(opts={}) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ism = m => this.isMenuOpt(m),
                isc = c => iss(c) && c!=='' && c!=='-empty-';
            if (!ism(opts) || !isa(opts.sub)) return opts;
            let hasicon = false;
            this.$each(opts.sub, (subi,i) => {
                //检查当前子菜单
                if (isc(subi.icon)) {
                    hasicon = true;
                }
                //递归执行 子菜单的子菜单  将处理后的 子菜单的子菜单回填到当前 子菜单的 sub[] 中
                opts.sub[i] = this.setSubMenuIcon(subi);
            });
            //根据 hasicon 状态批量设置 子菜单的 icon
            this.$each(opts.sub, (subi,i) => {
                //只修改未设置 icon 的子菜单
                if (!isc(subi.icon)) {
                    opts.sub[i].icon = hasicon ? '-empty-' : '';
                }
            });
            //返回处理后的 菜单参数
            return opts;
        },
        /**
         * 自动从指定的 noticeApi 获取各菜单项 notice 数据
         * 依据 noticeCheckFrequency 定时检查
         */
        async loadMenuNotice() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                isim = m => this.isInitedMenuOpt(m),
                api = this.noticeApi,
                menus = this.currentMenus;
            if (!iss(api) || !isa(menus)) return false;
            //远程获取 notice 数据
            //let notices = await this.$req(api);
            //!! mock for Dev
            await this.$wait(1000);
            let notices = [
                {
                    idxChain: [0],
                    notice: {
                        type: 'number',
                        value: 120
                    }
                },
                {
                    idxChain: [0,0],
                    notice: {
                        type: 'dot',
                        value: true
                    }
                },
                {
                    idxChain: [1],
                    notice: {
                        type: 'icon',
                        value: 'sentiment-dissatisfied'
                    }
                },
                {
                    idxChain: [2],
                    notice: {
                        type: 'dot',
                        value: false
                    }
                },
            ];

            //解析结果
            if (!isa(notices)) return false;
            this.$each(notices, (nt,i)=>{
                let idxc = nt.idxChain || [],
                    ntc = nt.notice || {};
                if (!isa(idxc) || !iso(ntc)) return true;
                let mi = this.getCurrentMenuByIdxChain(idxc);
                if (!isim(mi)) return true;
                //处理
                let nto = this.parseMenuNotice(ntc);
                //写入
                this.$set(mi.params, 'notice', Object.assign({}, nto));
                this.$set(mi, 'notice', Object.assign({}, ntc));
            });
            await this.$wait(10);
            //触发事件
            this.$emit('menu-change', this.currentMenus);
            return true;
        },
        //根据 菜单项 notice 数据，生成传入 menu-item 组件的 notice 参数
        parseMenuNotice(notice={}) {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                iso = o => is.plainObject(o) && !is.empty(o),
                isn = n => is.realNumber(n),
                dft = Object.assign({}, this.dftMenuOpts.params.notice);
            if (!iso(notice)) return dft;
            let ntp = notice.type || 'number',
                ntv = notice.value || 0,
                nto = dft;
            nto.type = ntp;
            //分别处理各种类型的 notice
            switch (ntp) {
                //数字类型通知
                case 'number':
                    nto.show = isn(ntv) && ntv>0;
                    if (isn(ntv) && ntv>0) {
                        nto.show = true;
                        let steps = [9,99,999];
                        this.$each(steps, (step,i)=>{
                            if (ntv>step) nto.showValue = `${step}+`;
                        });
                    }
                    break;
                //红点通知
                case 'dot':
                    if (is.boolean(ntv)) nto.show = ntv===true;
                    break;
                //图标通知
                case 'icon':
                    if (iss(ntv) && ntv!=='-empty-') {
                        nto.show = true;
                        nto.showValue = ntv;
                    }
                    break;
                //可扩展更多 notice 类型
                //...
            }
            //返回
            return nto;
        },
        //根据 idxChain 从 currentMenus 获取某个 menuItem {}
        getCurrentMenuByIdxChain(idxChain=[]) {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                isn = n => is.realNumber(n);
            if (!isa(idxChain)) return null;
            let mi = this.currentMenus;
            for (let i=0;i<idxChain.length;i++) {
                let idx = idxChain[i];
                if (!isn(idx) || idx<0) {
                    mi = null;
                    break;
                }
                if (isa(mi)) {
                    if (!is.defined(mi[idx]) || !iso(mi[idx])) {
                        mi = null;
                        break;
                    }
                    mi = mi[idx];
                } else if (iso(mi) && isa(mi.sub)) {
                    if (!is.defined(mi.sub[idx]) || !iso(mi.sub[idx])) {
                        mi = null;
                        break;
                    }
                    mi = mi.sub[idx];
                } else {
                    mi = null;
                    break;
                }
            }
            return mi;
        }, 
        //根据 keyChain 从 currentMenus 获取某个 menuItem {}
        getCurrentMenuByKeyChain(keyChain=[]) {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                iss = s => is.string(s) && s!=='',
                //从 opts[] 中查找指定的 opt.key
                gk = (a, k) => {
                    let idx = -1;
                    this.$each(a, (c,i)=>{
                        if (!iss(c.key) || c.key!==k) return true;
                        idx = i;
                        return false;
                    });
                    if (idx<0) return null;
                    return a[idx];
                };
            if (!isa(keyChain)) return null;
            let mi = this.currentMenus;
            for (let i=0;i<keyChain.length;i++) {
                let key = keyChain[i];
                if (!iss(key)) {
                    mi = null;
                    break;
                }
                if (isa(mi)) {
                    mi = gk(mi, key);
                } else if (iso(mi) && isa(mi.sub)) {
                    mi = gk(mi.sub, key);
                }
                if (!iso(mi)) {
                    mi = null;
                    break;
                }
            }
            return mi;
        },
        //折叠除了 exceptIdx 之外的其他 一级菜单，用于手风琴模式
        collapseMenus(except=-1) {
            let is = this.$is,
                ms = this.currentMenus;
            this.$each(ms, (mi,i) => {
                if (i===except) return true;
                //active 除外
                if (this.isMenuActived(mi)===true) return true;
                //设置 mi.params.collapse = true
                if (mi.params.collapse!==true) {
                    this.$set(mi.params, 'collapse', true);
                }
            });
        },
        //激活某个菜单 
        async activeMenu(opt={}) {
            if (!this.isInitedMenuOpt(opt) || this.isMenuActived(opt)) return false;
            //先取消 active
            await this.disactiveMenu();
            //激活此菜单 idxChain 序号链上的所有 子菜单项的 active 状态
            let midxc = opt.params.idxChain;
            for (let i=midxc.length;i>0;i--) {
                let idxc = midxc.slice(0,i),
                    mi = i===midxc.length ? opt : this.getCurrentMenuByIdxChain(idxc);
                if (!this.isInitedMenuOpt(mi)) continue;
                //激活 active
                this.$set(mi.params, 'active', true);
            }
            //缓存当前菜单的序号链到 activeMenuIdxChain
            this.activeMenuIdxChain.splice(0);
            this.activeMenuIdxChain.push(...midxc);
            return await this.$wait(10);
        },
        //取消激活菜单
        async disactiveMenu() {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                gcm = this.getCurrentMenuByIdxChain,
                isim = m => this.isInitedMenuOpt(m),
                aidxc = this.activeMenuIdxChain;
            if (!isa(aidxc)) return false;
            //取消 active idxChain 序号链上的所有 子菜单项的 active 状态
            for (let i=aidxc.length;i>0;i--) {
                let idxc = aidxc.slice(0,i),
                    mi = gcm(idxc);
                if (!isim(mi)) continue;
                //取消 active
                this.$set(mi.params, 'active', false);
            }
            //清除 activeMenuIdxChain
            this.activeMenuIdxChain.splice(0);
            return await this.$wait(10);
        },
        //计算某个菜单项的 所有子菜单当前应显示的 高度
        calcMenuHeight(opt={}, size=null) {
            if (!this.isInitedMenuOpt(opt)) return '0px';
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                sub = opt.sub,
                ui = this.$ui,
                sz = ui.sizeVal(size, 'bar');
            //默认菜单栏行高为 bar.m
            if (!ui.isSizeVal(sz)) sz = ui.cssvar.size.bar.m;
            //如果不存在子菜单 或 处于折叠状态 或 处于过滤隐藏状态  返回单行高度
            if (!isa(sub) || opt.params.collapse===true || opt.params.hide===true) return '0px';
            let hs = [];
            this.$each(sub, (subi,i) => {
                //如果出于 过滤隐藏状态，不计算高度
                if (subi.params && subi.params.hide && subi.params.hide===true) return true;
                //加入子菜单单行高度
                hs.push(sz);
                //计算子菜单的高度
                hs.push(this.calcMenuHeight(subi, size));
            });
            //所有高度相加
            let h = ui.sizeValAdd(...hs);
            if (!ui.isSizeVal(h)) return '0px';
            return h;
        },
        //根据 idxChain 计算菜单项高度
        calcMenuHeightByIdxChain(idxChain=[], size=null) {
            let mi = this.getCurrentMenuByIdxChain(idxChain);
            return this.calcMenuHeight(mi, size);
        },


        //事件
        async whenMenuItemToggleCollapse(idxChain=[]) {
            let is = this.$is,
                isa = a => is.array(a) && a.length>0,
                isim = m => this.isInitedMenuOpt(m),
                mi = this.getCurrentMenuByIdxChain(idxChain);
            if (!isim(mi)) return false;
            //不存在子菜单
            if (this.hasNoSub(mi)) return false;

            //针对 lazyload 子菜单 还未加载的情况
            if (this.isUnloadedSubLazyload(mi)) {
                //加载子菜单
                await this.loadSubMenu(mi);
            }

            //如果是 手风琴模式，则 折叠其他一级菜单  active 除外
            if (this.accordion===true) {
                this.collapseMenus(idxChain[0]);
            }
            //设置当前菜单项的 collapse 状态
            this.$set(mi.params, 'collapse', !mi.params.collapse);
            //触发事件 同步外层的 menus 参数值
            await this.$wait(10);
            return this.$emit('menu-change', this.currentMenus);
        },
        async whenMenuItemClick(keyChain=[], idxChain=[]) {
            //console.log(keyChain);
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                iso = o => is.plainObject(o) && !is.empty(o),
                isim = m => this.isInitedMenuOpt(m),
                isf = f => is(f, 'function,asyncfunction'),
                mi = this.getCurrentMenuByIdxChain(idxChain);
            if (!isim(mi)) return false;
            //如果此菜单项 包含子菜单，则无法执行 active 操作
            if (!this.hasNoSub(mi)) return false;
            //如果当前菜单项 active == true 表示此菜单项已处于 active 状态，不操作
            if (mi.params.active && mi.params.active===true) return false;

            //激活此菜单
            await this.activeMenu(mi);

            //手风琴模式下，折叠其他一级菜单
            if (this.accordion===true) {
                this.collapseMenus(idxChain[0]);
            }

            //触发事件 同步外层的 menus 参数值
            await this.$wait(10);
            this.$emit('menu-change', this.currentMenus);

            //执行当前菜单项定义的 cmd 自定义函数
            let cmd = mi.cmd || null;
            if (isf(cmd)) return cmd();
            
            //执行当前菜单项定义的 route 目标路由
            let route = mi.route || '';
            if (iss(route)) {
                //TODO: 调用 Vue.service.nav 服务，跳转...
                //...
                return;
            }

            //如果当前菜单项未指定 cmd 和 route 则向外抛出 menu-item-active 事件
            return this.$emit('menu-item-active', keyChain);
        },
        async whenCompactMenuItemClick(idxChain=[]) {
            //仅在菜单列表 横向折叠状态下有效
            if (!this.compact) return false;
            //通知父组件，接触 compact 横向折叠状态
            this.$emit('self-uncompact');
            //监听 props.compact 参数直到变为 false
            await this.$until(()=>!this.compact);
            //等待界面刷新
            await this.$wait(300);
            let mi = this.getCurrentMenuByIdxChain(idxChain);
            if (this.isMenuActived(mi)!==true) {
                //仅对未 active 的菜单项，执行 toggleCollapse 操作
                return await this.whenMenuItemToggleCollapse(idxChain);
            }
            return true;
        },
    }
}
</script>

<style>

</style>