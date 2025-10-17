/**
 * cv-**** 组件增加数据表 table 操作功能
 */

export default {
    props: {
        //当前要显示的数据表 xpath 形式：dbname/tbname
        table: {
            type: String,
            default: '',
            required: true
        },

        //是否查询关联表数据
        useRelatedTable: {
            type: Boolean,
            default: false
        },

        //数据表自定义参数，用于当前组件显示的特别参数
        customConfig: {
            type: Object,
            default: function() {
                return {}
            }
        },

        //数据表自定义查询参数，用于在当前组件中显示部分数据记录
        customQuery: {
            type: Object,
            default: function() {
                return {}
            }
        },

        //form 中的 initData
        //表单初始数据
        initData: {
            type: Object,
            default: ()=>{
                return {};
            }
        },
    },
    data() {return {
        //config ready
        ready: false,

        //query 查询设置
        query: {
            filter: {},
            sort: {},
            sk: [],
            page: {
                ipage: 1,
                size: 100
            }
        },
        querying: false,

        //某次查询的结果 recordset，此 rs 应与 query 保持对应
        //即 query 一旦变化，则 recordset 也对应变化
        rs: [],
        //查询返回的原始结果数据，来自接口 db/[xpath]/api/retrieve/...
        raw: {
            export: {},
            page: {},
            query: {},
            rs: []
        },
    }},
    computed: {
        //获取 config
        config() {
            return this.$db.getTableConfig(this.table);
        },
    },
    watch: {
        //当 query 参数改变时
        /*query: {
            handler(nv, ov) {
                //重新远程加载 rs
                this.reRequestRecord();
            },
            deep: true
        }*/
    },
    methods: {

        /**
         * table 准备
         */
        async prepareTable(reload=false) {
            let xpath = this.table;
            await this.$db.conf(xpath, reload);
            //table config 加载完成后处理
            if (this.$db.ready(xpath)) {
                this.ready = true;
                let conf = this.config;
                //处理 虚拟表/普通表 的 query 参数
                if (conf.table.isVirtual==false) {
                    //普通表
                    //处理 query 初始数据
                    await this.eachField(field=>{
                        let fd = conf.table.field[field];
                        if (fd.sortable) {
                            this.$set(this.query.sort, field, (field==conf.table.idf ? 'DESC' : fd.sort))
                        }
                        if (fd.filterable) {
                            this.$set(this.query.filter, field, {
                                logic: '',
                                value: null
                            });
                        }
                    });
                } else {
                    //虚拟表
                    //this.$set(conf, 'query', {});
                    //处理 query 初始数据
                    await this.eachField(field=>{
                        let fd = conf.table.field[field];
                        if (field==conf.table.idf) {
                            let k = this.$db.key(xpath);
                            this.$db.config[k].field[field].sortable = false;
                            this.$db.config[k].field[field].filterable = false;
                            return true;
                        }
                        if (fd.sortable) {
                            this.$set(this.query.sort, field, fd.sort);
                        }
                        if (fd.filterable) {
                            this.$set(this.query.filter, field, {
                                logic: '',
                                value: null
                            });
                        }
                    });
                    this.query.page = Object.assign({}, {
                        ipage: 1,
                        size: 9999
                    });
                }
                //应用 customQuery
                if (!this.$is.empty(this.customQuery)) {
                    this.query = Object.assign(this.query, this.customQuery)
                }
                //应用 customConfig/customQuery
                /*let cc = this.customConfig,
                    cq = this.customQuery,
                    oc = this.config,
                    is = this.$is,
                    extend = this.$extend;
                if (!is.empty(cq)) {
                    cc = extend(cc, {
                        query: cq
                    });
                }
                if (!is.empty(cc)) {
                    cc = extend({}, oc, cc);
                    this.config = Object.assign({}, this.config, cc);
                }

                //disabled fields
                if (is.defined(this.disabledFields) && is.array(this.disabledFields) && this.disabledFields.length>0) {
                    let dis = {};
                    for (let i=0;i<this.disabledFields.length; i++) {
                        this.config.table.field[this.disabledFields[i]].inputer.params.disabled = true;
                    }
                }
                await this.$cgy.wait(100);
                return true;*/
            }
        },



        /**
         * request
         */
        api(api='') {
            let xpath = this.$db.xpath(this.table),
                arr = [];
            arr.push(xpath, 'api');
            if (!this.$is.empty(api)) arr.push(api);
            return this.$db.api(arr.join('/'));
        },

        /**
         * 远程加载 rs
         * 根据 query 条件
         * 返回获取到的 rs 数据
         * 可由引用此 mixin 的组件内部覆盖
         * @param {String} rtype 提供 retrieve 方法参数 即 api = retrieve/[rtype] 默认为 show
         * @return {Array}
         */
        async requestRecord(rtype='table') {
            let is = this.$is,
                api = this.api(`retrieve/${rtype}`),
                pd = this.query;

            this.querying = true;

            //是否同时查询关联表
            if (this.useRelatedTable || this.config.table.userelatedtable) {
                pd.export = {
                    related: true
                };
            }
            //开始加载 rs
            //console.log(pd);
            if (this.config.table.isVirtual==false) {
                //普通表
                let rtn = await this.$req(api, pd);
                console.log(rtn);
                this.querying = false;
                //处理返回的 record set 数据
                if (is.defined(rtn.rs) && is.array(rtn.rs)) {
                    this.rs.splice(0);
                    this.rs.push(...rtn.rs);
                    //对 rs 执行 dbToTable 数据格式转换
                    if (is.defined(rtn.page) && !is.empty(rtn.page)) {
                        this.$set(this.query, 'page', rtn.page);
                    }
                    this.raw = Object.assign(this.raw, rtn);
                    return this.rs;
                }
                return {};
            } else {
                //虚拟表
                let rs = {};
                try {
                    rs = await this.$req(api, pd);
                    this.querying = false;
                    return rs;
                } catch(e) {
                    //this.record.rs.splice(0);
                    return {};
                }
            }
            //return true;
        },

        /**
         * 循环 fields 执行 callback
         * @param Function callback
         * @return {}
         */
        async eachField(callback = null, fields) {
            let is = this.$is;
            if (is(callback,'function,asyncfunction')) {
                let fds = (is.undefined(fields) || !is.array(fields) || is.empty(fields)) ? this.config.table.fields : fields,
                    rst = {};
                for (let i=0;i<fds.length;i++) {
                    let fdn = fds[i],
                        rsti = null;
                    if (is.function(callback)) {
                        rsti = callback.call(this, fdn);
                    } else {
                        rsti = await callback.call(this, fdn);
                    }
                    if (rsti===true) continue;
                    if (rsti===false) break;
                    rst[fdn] = rsti;
                }
                await this.$wait(100);
                return rst;
            }
            return {};
        },

        /**
         * 同步方式执行 eachField
         * @param {Function} callback 
         * @param {Array} fields 
         * @return {}
         */
        eachFieldSync(callback = null, fields) {
            let is = this.$is;
            if (is(callback,'function')) {
                let fds = (is.undefined(fields) || !is.array(fields) || is.empty(fields)) ? this.config.table.fields : fields,
                    rst = {};
                for (let i=0;i<fds.length;i++) {
                    let fdn = fds[i],
                        rsti = callback.call(this, fdn);
                    if (rsti===true) continue;
                    if (rsti===false) break;
                    rst[fdn] = rsti;
                }
                return rst;
            }
            return {};
        },

        /**
         * 循环 rs row 执行 callback
         * @param {Function} callback
         * @return void
         */
        async eachRow(callback = null, rs) {
            rs = this.$is.array(rs) && rs.length>0 ? rs : this.rs;
            if (!this.$is(callback, 'function,asyncfunction') || rs.length<=0) return;
            for (let i=0;i<rs.length;i++) {
                let rsi = rs[i];
                if (this.$is.function(callback)) {
                    callback.call(this, rsi);
                } else {
                    await callback.call(this, rsi);
                }
            }
        },



        /**
         * 显示
         */
    }
}