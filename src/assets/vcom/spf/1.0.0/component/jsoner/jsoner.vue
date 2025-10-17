<template>
    <div 
        :class="'PRE@-jsoner'+(fullscreen ? ' fullscreen' : '')+(customClass==''?'':' '+customClass)"
        :style="(((height!='' && height!='auto') || height>0) ? 'height:'+height+';' : '')+(customStyle==''?'':customStyle)"
    >
        <div class="thead">
            <span class="tree-btn"></span>
            <span class="key">
                <span>{{ keyColumnLabel }}</span>
                <span style="flex:1;"></span>
                <PRE@-icon
                    icon="md-sharp-drag-indicator"
                    :size="18"
                    custom-class="resize"
                ></PRE@-icon>
            </span>
            <span class="value" :style="'width:'+valw+';'">{{ valueColumnLabel }}</span>
            <span v-if="!readonly" class="ctrl">{{ ctrlColumnLabel }}</span>
        </div>
        <div v-if="buildInReady" :class="'tbody lvl-0'+((height!='' || height>0) ? ' with-scroll' : '')">
            <div v-if="$is.empty(context)" class="row empty">无内容</div>
            <template v-if="!$is.empty(context) && ctxType=='array'">
                <PRE@-jsoner-row
                    v-for="(cti,ctidx) of context"
                    :key="'cv_jsoner_array_0_1_'+ctidx"
                    v-model="context[ctidx]"
                    :treekey="ctidx"
                    :key-chain="ctidx"
                    :is-array-item="true"
                    :lvl="1"
                    :readonly="readonly"
                    :as-html="asHtml"
                    :root-editable="rootEditable && !isRecord"
                    :sub-editable="subEditable"
                    :key-column-label="keyColumnLabel"
                    :value-column-label="valueColumnLabel"
                    :ctrl-column-label="ctrlColumnLabel"
                    :value-width="valw"
                    @edit-row-key="doEditRowKey"
                    @delete-row="delRow"
                    @change="emitChange"
                >
                    <template v-if="asHtml" v-slot:[slotName[ctidx]]>
                        <slot :name="slotName[ctidx]"></slot>
                    </template>
                </PRE@-jsoner-row>
            </template>
            <template v-if="!$is.empty(context) && ctxType=='object'">
                <PRE@-jsoner-row
                    v-for="(cti,ctikey) of context"
                    :key="'cv_jsoner_object_0_1_'+ctikey"
                    v-model="context[ctikey]"
                    :treekey="ctikey"
                    :key-chain="ctikey"
                    :lvl="1"
                    :readonly="readonly"
                    :as-html="asHtml"
                    :root-editable="rootEditable && !isRecord"
                    :sub-editable="subEditable"
                    :key-column-label="keyColumnLabel"
                    :value-column-label="valueColumnLabel"
                    :ctrl-column-label="ctrlColumnLabel"
                    :value-width="valw"
                    @edit-row-key="doEditRowKey"
                    @delete-row="delRow"
                    @change="emitChange"
                >
                    <template v-if="asHtml" v-slot:[slotName[ctikey]]>
                        <slot :name="slotName[ctikey]"></slot>
                    </template>
                </PRE@-jsoner-row>
            </template>
        </div>
        <div v-if="showCtrl" class="tctrl">
            <PRE@-button
                v-if="allowAddNewKey"
                icon="vant-plus"
                :label="'新增'+keyColumnLabel"
                size="small"
                type="primary"
                popout
                @click="newRow"
            ></PRE@-button>
            <span class="gap"></span>
            <!--<PRE@-button
                v-if="!readonly && beenModified"
                icon="vant-check"
                type="danger"
                label="确认修改"
                custom-class="btn"
                @click="emitChange"
            ></PRE@-button>-->
            <template v-if="!readonly && customCtrl.length>0">
                <PRE@-button
                    v-for="(cci,ccidx) of customCtrl"
                    :key="'cv_jsoner_custom_ctrl_'+ccidx"
                    v-bind="cci.btn"
                    size="small"
                    type="primary"
                    popout
                    custom-class="btn"
                    @click="customCtrlClick(cci)"
                ></PRE@-button>
            </template>
            <PRE@-button
                icon="vant-sync"
                title="刷新"
                size="small"
                type="primary"
                popout
                custom-class="btn"
                :spin="!buildInReady"
                :disable="!buildInReady"
                @click="init"
            ></PRE@-button>
            <PRE@-button
                :icon="'vant-'+(fullscreen ? 'compress' : 'expend')"
                :title="fullscreen ? '恢复正常尺寸' : '最大化编辑器'"
                size="small"
                type="primary"
                popout
                custom-class="btn"
                @click="toggleFullscreen"
            ></PRE@-button>
            <PRE@-button
                :icon="showJson ? 'vant-eye-close' : 'vant-eye'"
                :title="(showJson ? '关闭' : '查看')+'JSON'"
                size="small"
                type="primary"
                popout
                :active="showJson"
                custom-class="btn"
                @click="showJson = !showJson"
            ></PRE@-button>
            <slot name="jsoner-ctrl" :jsoner="$this"></slot>
        </div>
        <div v-if="showJson" class="jsonpre">
            <pre
                v-html="jsonHtml"
                style="white-space: pre; margin: 0;"
            ></pre>
        </div>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base';

export default {
    mixins: [mixinBase],
    model: {
        prop: 'value',
        event: 'change'
    },
    props: {
        //v-model 绑定的外部值，需要按 tree 结构进行编辑
        //可以是 json 字符串，plainObject，array
        value: {
            type: [String, Object, Array],
            default: '{}'
        },

        /**
         * 是否只读
         * 只读 即 不允许任何修改，key/value 都不可以修改
         */
        readonly: {
            type: Boolean,
            default: false
        },
        /**
         * 是否作为普通 html 展示数据
         * 数据将不显示在 input 中，可以自动换行，支持 html 样式
         */
        asHtml: {
            type: Boolean,
            default: false
        },

        /**
         * 是否允许修改 json 数据结构
         * 即 是否允许 增删 key
         * 即使不允许修改结构，value 仍可以修改
         */
        //修改 根结构
        rootEditable: {
            type: Boolean,
            default: true
        },
        //修改 子项结构
        subEditable: {
            type: Boolean,
            default: true
        },

        /**
         * 是否与数据库记录关联
         * !!! 如果与 数据库记录 关联 必须提供 table/recordId/idField/update-api 参数
         * 与数据库关联后：
         *      不允许修改根结构，因为 根结构 即 数据表结构
         *      删除操作将使用 __delete__ 标记
         *      字段值 改变后 将出现 确认按钮 点击则立即提交 update-api
         *      
         */
        isRecord: {
            type: Boolean,
            default: false
        },
        recordTable: {
            type: String,
            default: ''
        },
        recordId: {
            type: [String, Number],
            default: ''
        },
        recordIdColumn: {
            type: String,
            default: ''
        },
        //数据库操作 api 前缀，完整的 api 应为 [recordApi]/retrieve|update|delete|info
        recordApi: {
            type: String,
            default: ''
        },

        //是否显示 ctrl 控制栏
        showCtrl: {
            type: Boolean,
            default: true
        },

        //自定义 ctrl 按钮及动作
        customCtrl: {
            type: Array,
            default: ()=>{
                return [
                    /*
                    {
                        btn: {
                            icon: '',
                            label: '',
                            atto-button 参数
                        },
                        click: ()=>{
                            动作
                        }
                    }
                    */
                ];
            }
        },

        //指定 value 列宽度
        valueWidth: {
            type: [String, Number],
            default: '50%'
        },

        //指定 高度，则 tbody 显示滚动条
        height: {
            type: [String, Number],
            default: ''
        },

        //项目列 label 自定义
        keyColumnLabel: {
            type: String,
            default: '项目'
        },
        //内容列 label 自定义
        valueColumnLabel: {
            type: String,
            default: '内容'
        },
        //操作列 label 自定义
        ctrlColumnLabel: {
            type: String,
            default: '操作'
        },

    },

    data: function() {return {
        //value 的内部处理后的缓存，所有编辑都针对此对象
        context: [],
        //context type: object or array
        ctxType: 'object',

        //build 标记
        buildInReady: false,

        //标记是否 emit 导致 value 改变
        emitToValueChange: false,
        
        beenModified: false,
        showJson: false,

        //el width
        elw: 0,

        //全屏显示 标记
        fullscreen: false,
    }},

    computed: {
        jsonHtml() {
            let o = JSON.parse(this.buildJson());
            return JSON.stringify(o, null, 4);
        },

        //经过处理后的 实际显示的 value width px
        valw() {
            let is = this.$is,
                vw = this.valueWidth,
                elw = this.elw,
                rvw = '256px';
            if (vw=='' || is.empty(vw)) return rvw;
            if (is.string(vw) && vw.includes('%')) {
                let vpec = vw.replace('%','');
                vpec = (vpec*1)/100;
                if (elw<=0) return rvw;
                rvw = `${elw*vpec}px`;
            } else if (is.realNumber(vw)) {
                rvw = vw+'px';
            } else if (is.string(vw)) {
                if (vw!='') {
                    rvw = vw;
                }
            }
            return rvw;
        },

        /**
         * 可编辑性 判断
         */
        //可新增 key
        allowAddNewKey() {
            return !this.readonly && this.rootEditable && !this.isRecord;
        },

        //当 asHtml = true 时，输出 slot name {}
        slotName() {
            let is = this.$is,
                ctx = this.context;
            if (is.empty(ctx)) return {};
            let sn = {};
            if (is.array(ctx)) {
                for (let i=0;i<ctx.length;i++) {
                    sn[i] = `ashtml-${i}-extra`;
                }
            } else if (is.plainObject(ctx)) {
                for (let i in ctx) {
                    sn[i] = `ashtml-${i.toLowerCase()}-extra`;
                }
            }
            return sn;
        },
    },

    watch: {
        value(nv, ov) {
            if (this.emitToValueChange) {
                this.emitToValueChange = false;
            } else {
                this.buildContext();
            }
        },
    },

    created() {
        //this.buildContext();
        this.init();
    },

    mounted() {
        this.$nextTick(()=>{
            let el = this.$el;
            if (this.$is.elm(el)) {
                this.$wait(100).then(()=>{
                    this.elw = el.offsetWidth;
                });
            }
        });
    },

    methods: {

        //init/reload
        init() {
            this.buildContext();
            this.$until(()=>{
                return this.$is.elm(this.$el);
            },3000).then(()=>{
                this.elw = this.$el.offsetWidth;
            });
        },

        //json --> context
        buildContext() {
            this.buildInReady = false;
            let json = this.value,
                is = this.$is,
                ctx = is.json(json) ? JSON.parse(json) : (is(json,'array,object') ? json : {});

            //console.log(ctx);
            if (is.array(ctx)) {
                this.ctxType = 'array';
                this.context.splice(0);
                this.context.push(...ctx);
            } else if (is.plainObject(ctx)) {
                this.ctxType = 'object';
                this.context = Object.assign({}, ctx);
            }
            this.$wait(100).then(()=>{
                this.buildInReady = true;
            });
        },

        //context array --> json string
        buildJson() {
            return JSON.stringify(this.context);
        },

        /**
         * 编辑
         */
        //新增 行
        newRow() {
            if (this.readonly) return;
            let kl = this.keyColumnLabel,
                vl = this.valueColumnLabel,
                kv = `新${kl}`,
                vv = `新${vl}`;
            switch (this.ctxType) {
                case 'array':
                    this.context.push(vv);
                    break;
                case 'object':
                    this.$set(this.context, kv,vv);
                    break;
            }
            this.emitChange();
        },
        //删除 行
        delRow(i) {
            if (this.readonly) return;
            switch (this.ctxType) {
                case 'array':
                    this.context.splice(i,1);
                    break;
                case 'object':
                    let ctx = Object.assign({}, this.context);
                    Reflect.deleteProperty(ctx, i);
                    this.context = Object.assign({}, ctx);
                    break;
            }
            this.emitChange();
        },
        //编辑 treekey
        doEditRowKey(okey, nkey) {
            if (this.readonly) return;
            switch (this.ctxType) {
                case 'array':
                    //array key 不可编辑
                    return;
                    break;
                case 'object':
                    /*let tgt = this.context[okey],
                        is = this.$is;
                    if (is.plainObject(tgt)) {
                        let ctxi = Object.assign({}, tgt);
                        this.$set(this.context, nkey, ctxi);
                    } else if (is.array(tgt)) {
                        this.$set(this.context, nkey, []);
                        this.context[nkey].push(...tgt);
                    } else {
                        this.$set(this.context, nkey, tgt);
                    }
                    let ctx = Object.assign({}, this.context);
                    Reflect.deleteProperty(ctx, okey);
                    this.context = Object.assign({}, ctx);*/

                    let tgt = this.context[okey],
                        ctx = null,
                        is = this.$is;
                    if (is.plainObject(tgt)) {
                        ctx = Object.assign({}, tgt);
                    } else if (is.array(tgt)) {
                        ctx = [];
                        ctx.push(...tgt);
                    } else {
                        ctx = tgt;
                    }
                    let octx = Object.assign({},this.context);
                    Reflect.deleteProperty(octx, okey);
                    octx[nkey] = ctx;
                    this.context = Object.assign({}, octx);

                    break;
            }
            this.emitChange();
        },



        //add context key
        /*addProperty() {
            this.context.push({
                key: '',
                value: ''
            });
        },

        //delete context key
        deleteProperty(idx) {
            this.context.splice(idx, 1);
            this.emitChange();
        },

        //on input
        inputKey(idx, event) {
            //console.log(event);
            this.context[idx].key = event.target.value;
            this.beenModified = true;
        },
        inputValue(idx, event) {
            //console.log(event);
            this.context[idx].value = event.target.value;
            this.beenModified = true;
        },*/

        emitChange() {
            let val = this.value,
                is = this.$is,
                emitval = this.context;
            if (is.string(val)) {
                emitval = this.buildJson();
            }
            this.emitToValueChange = true;
            this.$emit('change', emitval);
            this.$emit('input', emitval);
            this.beenModified = false;
        },

        //输出中文序号，①②③④⑤⑥⑦⑧⑨⑩，超过10项，则输出 01-99
        /*showSerialNumber(idx=0) {
            let ss = '①,②,③,④,⑤,⑥,⑦,⑧,⑨,⑩'.split(','),
                ln = this.context.length;
            if (ln<=10) {
                return idx<ln ? ss[idx] : (idx+1)+'';
            }
            let sidx = (idx+1)+'',
                rpt = 2-sidx.length;
            return '0'.repeat(rpt)+sidx;
        },*/

        //点击 customCtrl 指定的 按钮
        customCtrlClick(cci) {
            let is = this.$is,
                clk = cci.click;
            if (!is(clk,'function,asyncfunction')) return false;
            return clk(this);
        },

        //全屏显示 切换
        toggleFullscreen() {
            this.fullscreen = !this.fullscreen;
            //重新计算 valueWidth
            this.$wait(100).then(()=>{
                this.elw = this.$el.offsetWidth;
            });
        },
    }
}
</script>

<style>

</style>