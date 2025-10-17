/**
 * cv-**** 组件数据表记录条目相关功能
 */

export default {
    props: {
        //当前要显示的数据表 xpath 形式：dbname/tbname
        table: {
            type: String,
            default: '',
            required: true
        },

        //当前的查询参数 query
        query: {
            type: Object,
            default: ()=>{
                return {
                    filter: {},
                    sort: {},
                    sk: [],
                    page: {
                        ipage: 1,
                        size: 100
                    }
                };
            }
        },

        //record 记录条目数据
        record: {
            type: Object,
            default: ()=>{
                return {}
            }
        },

        //record 选中状态
        selectStatus: {
            type: [Boolean, Number],
            default: false
        },

        //要显示的 fields
        showFields: {
            type: Array,
            default: ()=>[]
        },

        //显示操作列
        showCtrlCol: {
            type: Boolean,
            default: true
        },
    },
    data() {return {

    }},
    computed: {
        
        //获取 config
        config() {
            return this.$db.getTableConfig(this.table);
        },

        //所有要显示 field 的 width 之和
        widthSum() {
            let fds = this.showFields,
                fdo = this.config.table.field,
                ws = 0;
            for (let fdi of fds) {
                ws += fdo[fdi].width;
            }
            return ws;
        },
        //获取每一列的 width 宽度
        colWidth() {
            let fds = this.showFields,
                fdo = this.config.table.field,
                ws = this.widthSum,
                fdw = {};
            for (let fdi of fds) {
                fdw[fdi] = ((fdo[fdi].width/ws)*100);
            }
            return fdw;
        },
    },
    methods: {

    }
}