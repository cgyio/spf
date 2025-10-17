/**
 * cv-**** 数据库单例组件
 */

export default {
    props: {},
    data() {return {
        apiPrefix: 'db',

        //db config structure
        structure: {

        },
        //db configs
        config: {
            /*
            dbn: {...},
            dbn_tbn: {...},
            */
        },
        //table records
        //record: {},
    }},
    computed: {

    },
    methods: {

        /**
         * 处理 db/tb xpath
         * dbn/tbn  -->  [appname]/dbn/tbn
         * @param {String} xpath    dbn/tbn
         * @return {String} 完整的 db/tb xpath
         */
        xpath(xpath) {
            let appname = Vue.request.apiPrefix(),
                parr = xpath.split('/');
            if (appname!='' && parr[0]!=appname && parr[0]!='app') parr.unshift(appname);
            return parr.join('/');
        },

        /**
         * 从完整的 db/tb xpath 中获取 dbn tbn
         * @param {String} xpath    appname/dbn/tbn
         * @return {Array} [dbn, tbn]
         */
        names(xpath) {
            xpath = this.xpath(xpath);
            let parr = xpath.split('/'),
                appname = Vue.request.apiPrefix(),
                ns = {
                    db: null,
                    table: null
                };
            if (parr[0]=='app') parr.shift();
            if (parr[0]==appname) parr.shift();
            if (parr.length>=1) ns.db = parr[0];
            if (parr.length>1) ns.table = parr[1];
            if (parr.length>2) {
                let sli = parr.slice(-2);
                ns.db = sli[0];
                ns.table = sli[1];
            }
            return ns;
        },

        /**
         * 从完整的 xpath 获取 config key
         * appname/dbn/tbn  -->  dbn_tbn
         * @param {String} xpath    appname/dbn/tbn
         * @return {string}
         */
        key(xpath) {
            xpath = this.xpath(xpath);
            let ns = this.names(xpath),
                dbn = ns.db,
                tbn = ns.table,
                key = cgy.is.empty(tbn) ? dbn : `${dbn}_${tbn}`;
            return key;
        },


        /**
         * request db api
         */

        /**
         * 处理 db api
         */
        api(api='') {
            api = api.trimAny('/');
            let pre = this.apiPrefix,
                u = [];
            if (pre!='') u.push(pre);
            if (api!='') u.push(api);
            return Vue.request.api(`/${u.join('/')}`);
        },
    
        /**
         * db request
         */
        async request(api='', ...args) {
            api = this.api(api);
            let rtn = await Vue.req(api, ...args);
            return rtn;
        },

        /**
         * get db/tb config
         */
        async conf(xpath, ...args) {
            xpath = this.xpath(xpath);
            let is = cgy.is,
                cf = this.config,
                ns = this.names(xpath),
                dbn = ns.db,
                tbn = ns.table,
                key = is.empty(tbn) ? dbn : `${dbn}_${tbn}`,
                reload = (args.length>0 && is.boolean(args[args.length-1])) ? args.pop() : false;
            if (reload || is.undefined(cf[key])) {
                let conf = await this.request(`${xpath}/config`);
                console.log(conf);
                if (is.undefined(cf[key])) this.$set(cf, key, {});
                if (is.defined(conf.db) && is.defined(conf.table)) {
                    //获取了 table config 其中包含 db 信息
                    if (is.undefined(cf[dbn])) this.$set(cf, dbn, {});
                    cf[dbn] = Object.assign({}, cf[dbn], conf.db);
                    cf[key] = Object.assign({}, cf[key], conf.table);
                } else {
                    cf[key] = Object.assign({}, cf[key], conf);
                }
            }
            if (args.length<=0) return cf[key];
            let argstr = args.join('.').replace(/\//g, '.');
            return cgy.loget(cf[key], argstr);
        },
        //判断 config ready
        ready(xpath) {
            if (!this.$is.string(xpath) || xpath=='') return false;
            let key = this.key(xpath);
            return cgy.is.defined(this.config[key]) && !cgy.is.empty(this.config[key]);
        },
        //子组件获取相关 table config {}
        getTableConfig(xpath) {
            if (this.ready(xpath)!==true) return {};
            let key = this.key(xpath),
                dbn = this.names(xpath).db,
                conf = this.config;
            return {
                db: conf[dbn],
                table: conf[key]
            };
        },


        /**
         * 数据格式转换
         * 用于处理从 api 获取的数据  或 即将写入数据库的数据
         */
        /**
         * 数据从 db 保存格式 转换为 table 显示格式
         * @param {any} val
         * @param {String} xpath like: dbn/tbn/fdn 数据表 xpath 用于获取 field config
         * @return {any} 转换后用于输出的数据
         */
        convDbToTable(val, xpath) {
            
        },



        /**
         * 数据库相关的 tools
         */
        //根据 field type 给出筛选 logic 列表
        getLogicsByFieldType(fieldConfig={}) {
            let lgs = [
                {value: '=', label: '等于'},
                {value: '!', label: '不等于'},
                {value: '<>', label: '在范围内'},
                {value: '><', label: '不在范围内'},
                {value: '>', label: '大于'},
                {value: '>=', label: '大于等于'},
                {value: '<', label: '小于'},
                {value: '<=', label: '小于等于'},
                {value: '~', label: '包含'}
            ];
            if (this.$is.empty(fieldConfig)) return lgs;
            let fc = fieldConfig,
                lgi = [
                    {value: '', label: '不筛选此字段'}
                ];
                
            if (fc.isSwitch || fc.isSelector) {
                lgi.push({value: '=', label: '等于'});
                if (fc.isSelector) {
                    lgi.push({value: '~', label: '包含'});
                }
            } else if (fc.isTime) {
                lgi.push(...[
                    {value: '>', label: '晚于'},
                    {value: '<', label: '早于'},
                    {value: '<>', label: '在范围内'},
                    {value: '><', label: '不在范围内'},
                    {value: '~', label: '包含'},
                    {value: '!~', label: '不包含'}
                ]);
            } else if (fc.isId) {
                lgi.push(...[
                    {value: '=', label: '等于'},
                    {value: '!', label: '不等于'},
                    {value: '>', label: '大于'},
                    {value: '<', label: '小于'}
                ]);
            } else if (fc.type=='varchar') {
                lgi.push(...[
                    {value: '=', label: '等于'},
                    {value: '!', label: '不等于'},
                    {value: '~', label: '包含'},
                    {value: '!~', label: '不包含'}
                ]);
            } else if (fc.type=='integer' || fc.type=='float') {
                lgi.push(...[
                    {value: '=', label: '等于'},
                    {value: '!', label: '不等于'},
                    {value: '>', label: '大于'},
                    {value: '>=', label: '大于等于'},
                    {value: '<', label: '小于'},
                    {value: '<=', label: '小于等于'},
                    {value: '<>', label: '在范围内'},
                    {value: '><', label: '不在范围内'},
                ]);
            } else {
                lgi.push(...lgs);
            }
            return lgi;
        },

    }
}