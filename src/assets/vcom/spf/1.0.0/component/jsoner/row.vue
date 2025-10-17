<template>
    <div :class="'row '+valueType+'-value'+subopenedCls">

        <!-- valueType = string,number,boolean,null,undefined -->
        <template v-if="valueTypeIs('string','number','boolean','null','undefined')">
            <span class="tree-btn"></span>
            <template v-if="!isArrayItem">
                <span v-if="!asHtml" class="key">
                    <input 
                        type="text" 
                        :id="'key_'+keyChainFixed" 
                        :title="'key_'+keyChainFixed"
                        name="jsoner_row_key" 
                        :value="ctxkey" 
                        :readonly="!allowDelKey"
                        :style="!allowDelKey ? 'cursor:not-allowed;' : ''"
                        @change="editRowKey"  
                        @focus="inputFocus"
                    >
                </span>
                <span
                    v-else
                    class="key-ashtml"
                    v-html="ctxkey"
                ></span>
            </template>
            <span v-else class="key array-key">
                <el-tag type="info" size="small" style="font-family:var(--font-fml-code);">{{ ctxkey }}</el-tag>
            </span>
            <span v-if="!asHtml" class="value" :style="valueSty">
                <el-switch
                    v-if="valueTypeIs('boolean')"
                    v-model="context"
                    :disabled="readonly"
                    :style="readonly ? 'cursor:not-allowed;' : ''"
                    @change="emitChange"
                ></el-switch>
                <input 
                    v-else
                    type="text" 
                    :id="'val_'+keyChainFixed" 
                    :title="'val_'+keyChainFixed" 
                    name="jsoner_row_value" 
                    :value="context" 
                    :readonly="readonly" 
                    :style="readonly ? 'cursor:not-allowed;' : ''"
                    @change="editRow" 
                    @focus="inputFocus"
                >
                <el-tag 
                    v-if="valueTypeIs('null','undefined')"
                    type="danger" 
                    size="small"
                    class="btn"
                >空值</el-tag>
                <!--<el-tag 
                    v-if="valueTypeIs('number')"
                    type="info" 
                    size="small"
                >数值</el-tag>-->
            </span>
            <span 
                v-else 
                class="value-ashtml"
                :style="valueSty"
            >
                <span class="val-row val-tit" v-html="context"></span>
                <slot :name="'ashtml-'+ctxkey.toLowerCase()+'-extra'"></slot>
            </span>
            <span v-if="!readonly" class="ctrl">
                <PRE@-button
                    icon="vant-delete"
                    title="删除此项"
                    type="danger"
                    size="small"
                    popout
                    :disabled="emptyKey || !allowDelKey"
                    custom-class="btn"
                    @click="$emit('delete-row', ctxkey)"
                ></PRE@-button>
            </span>
        </template>

        <!-- valueType = array,object -->
        <div v-if="valueTypeIs('array','object')" :class="'rhead'+subopenedCls">
            <span class="tree-btn" :style="subopened ? '' : 'border-bottom: none;'">
                <!--<PRE@-button
                    :disabled="emptyKey"
                    :icon="subopened ? 'vant-caret-down' : 'vant-caret-right'"
                    :title="subopened ? '折叠此项' : '展开此项'"
                    type="primary"
                    size="small"
                    popout
                    @click="subopened = !subopened"
                ></PRE@-button>-->
                <PRE@-icon
                    icon="md-sharp-keyboard-arrow-right"
                    :size="20"
                    :custom-class="'tree-btn-icon'+subopenedCls"
                ></PRE@-icon>
            </span>
            <span v-if="!isArrayItem" class="key">
                <input 
                    v-if="allowDelKey"
                    type="text" 
                    :value="ctxkey" 
                    :readonly="!allowDelKey" 
                    :style="!allowDelKey ? 'cursor:not-allowed;' : ''"
                    @focus="inputFocus"
                    @change="editRowKey" 
                >
                <span
                    v-else
                    class="readonly-key"
                    v-html="ctxkey"
                ></span>
            </span>
            <span v-else class="key array-key">
                <el-tag type="info" size="small" style="font-family:var(--font-fml-code);">{{ ctxkey }}</el-tag>
            </span>
            <span 
                class="value" 
                :style="valueSty" 
                :title="($is.empty(context) ? '新增' : (subopened ? '收起' : '展开'))+' '+keyChain+' 子'+keyColumnLabel"
                @click="toggleSubopened"
            >
                <!--<el-tag v-if="!subopened && !$is.empty(context)" type="info" size="small">...</el-tag>-->
                <template v-if="!$is.empty(context)">
                    <PRE@-button
                        v-if="!subopened"
                        icon="md-sharp-more-horiz"
                        type="primary"
                        size="small"
                        popout
                        @click=""
                    ></PRE@-button>
                    <PRE@-button
                        v-if="subopened"
                        icon="md-sharp-expand-less"
                        type="primary"
                        size="small"
                        popout
                    ></PRE@-button>
                </template>
                <template v-if="$is.empty(context)/* && msov*/ && !readonly">
                    <PRE@-button
                        icon="vant-plus"
                        type="primary"
                        size="small"
                        popout
                        :disabled="emptyKey"
                        custom-class="btn"
                    ></PRE@-button>
                </template>
                <!--<PRE@-button
                    v-if="!readonly && $is.empty(context) && !subopened"
                    icon="vant-plus"
                    title="添加子项目"
                    type="primary"
                    size="small"
                    popout
                    :disabled="emptyKey"
                    custom-class="btn"
                    @click="newRow"
                ></PRE@-button>-->
                <span style="flex:1;"></span>
                <el-tag 
                    type="info" 
                    size="small"
                >{{ valueType=='object' ? '键值' : '数组' }}</el-tag>
                <!--<PRE@-icon
                    v-if="valueIcon!=''"
                    :icon="valueIcon"
                    :size="18"
                    :title="'类型：'+valueType.ucfirst()"
                    :color="cssvar.color.fc.l1"
                ></PRE@-icon>-->
            </span>
            <span v-if="!readonly" class="ctrl">
                <PRE@-button
                    icon="vant-delete"
                    title="删除此项"
                    type="danger"
                    size="small"
                    popout
                    :disabled="!allowDelKey || emptyKey"
                    custom-class=""
                    @click="$emit('delete-row', ctxkey)"
                ></PRE@-button>
            </span>
        </div>
        <!-- empty -->
        <div v-if="$is.empty(context) && subopened" :class="'tbody lvl-'+(lvl+1)+subopenedCls">
            <div class="row empty">无内容</div>
        </div>
        <template v-if="!$is.empty(context) && subopened">
            <!-- valueType = array -->
            <div v-if="valueTypeIs('array')" :class="'tbody lvl-'+(lvl+1)">
                <PRE@-jsoner-row
                    v-for="(vi,vidx) of context"
                    :key="'cv_jsoner_array_'+lvlPath.join('_')+'_'+(lvl+1)+'_'+vidx"
                    v-model="context[vidx]"
                    :treekey="vidx"
                    :key-chain="keyChain+'.'+vidx"
                    :is-array-item="true"
                    :lvl="lvl+1"
                    :readonly="readonly"
                    :as-html="asHtml"
                    :root-editable="allowAddNewSubKey"
                    :sub-editable="true"
                    :key-column-label="keyColumnLabel"
                    :value-column-label="valueColumnLabel"
                    :ctrl-column-label="ctrlColumnLabel"
                    :value-width="valueWidth"
                    @edit-row-key="doEditRowKey"
                    @delete-row="delRow"
                    @change="emitChange"
                ></PRE@-jsoner-row>
                <div 
                    v-if="allowAddNewSubKey" 
                    class="row nohover" 
                    style="min-height:var(--size-row-l); align-items:center;"
                >
                    <!--<span class="tree-btn"></span>-->
                    <PRE@-button
                        icon="vant-plus"
                        :label="'新增 '+keyChain+' 子'+keyColumnLabel"
                        type="primary"
                        size="small"
                        popout
                        :disabled="emptyKey"
                        custom-class="mg-l-xs"
                        custom-style="font-family:var(--font-fml-code);"
                        @click="newRow"
                    ></PRE@-button>
                </div>
            </div>
            <!-- valueType = object -->
            <div v-if="valueTypeIs('object')" :class="'tbody lvl-'+(lvl+1)">
                <PRE@-jsoner-row
                    v-for="(vi,vikey) of context"
                    :key="'cv_jsoner_object_'+lvlPath.join('_')+'_'+(lvl+1)+'_'+vikey"
                    v-model="context[vikey]"
                    :treekey="vikey"
                    :key-chain="keyChain+'.'+vikey"
                    :lvl="lvl+1"
                    :readonly="readonly"
                    :as-html="asHtml"
                    :root-editable="allowAddNewSubKey"
                    :sub-editable="true"
                    :key-column-label="keyColumnLabel"
                    :value-column-label="valueColumnLabel"
                    :ctrl-column-label="ctrlColumnLabel"
                    :value-width="valueWidth"
                    @edit-row-key="doEditRowKey"
                    @delete-row="delRow"
                    @change="emitChange"
                ></PRE@-jsoner-row>
                <div 
                    v-if="allowAddNewSubKey" 
                    class="row nohover" 
                    style="min-height:var(--size-row-l); align-items:center;"
                >
                    <!--<span class="tree-btn"></span>-->
                    <PRE@-button
                        icon="vant-plus"
                        :label="'新增 '+keyChain+' 子'+keyColumnLabel"
                        type="primary"
                        size="small"
                        popout
                        :disabled="emptyKey"
                        custom-class="mg-l-xs"
                        custom-style="font-family:var(--font-fml-code);"
                        @click="newRow"
                    ></PRE@-button>
                </div>
            </div>
        </template>
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
        //v-model tree-row 值 value 任意类型
        value: {
            //type: [String, Number, Object, Array],
            default: '',
            required: true
        },

        //tree-row 键 key
        treekey: {
            type: [String, Number],
            default: '',
            required: true
        },
        //key 链
        keyChain: {
            type: [String, Number],
            default: ''
        },
        //当前 row 是否 array 子项
        //是 则不显示 key input
        isArrayItem: {
            type: Boolean,
            default: false
        },

        //当前 tree-row 的递归深度 lvl
        lvl: {
            type: Number,
            default: 0,
            required: true
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
        
        //指定 value 列宽度 由父组件计算得到的 px
        valueWidth: {
            type: String,
            default: ''
        },
    },
    data() {return {
        //内部缓存 值
        context: this.value,
        //内部缓存 键
        ctxkey: this.treekey,

        //如果是可展开类型 array/object 是否展开子项
        subopened: false,

    }},
    computed: {
        //获取 value 类型
        valueType() {
            let is = this.$is,
                val = this.context;
            if (is.boolean(val)) return 'boolean';
            if (is.null(val)) return 'null';
            if (is.plainObject(val)) return 'object';
            if (is.array(val)) return 'array';
            if (is.realNumber(val)) return 'number';
            if (is.string(val)) return 'string';
            return 'undefined';
        },
        //根据 valueType 显示 icon
        valueIcon() {
            let vt = this.valueType,
                vts = {
                    'array': 'md-sharp-format-list-numbered',    //'vant-orderedlist',
                    'object': 'md-sharp-data-object',
                    'null': 'md-sharp-warning',
                };
            if (!this.$is.defined(vts[vt])) return '';
            return vts[vt];
        },

        //value style
        valueSty() {
            let is = this.$is,
                vw = this.valueWidth,
                sty = {};
            if (vw=='' || is.empty(vw) || !is.string(vw)) return sty;
            sty.width = vw;
            return sty;
        },

        //fix keyChain foo.bar.jaz --> foo_bar_jaz
        keyChainFixed() {
            let kc = this.keyChain;
            if (!this.$is.string(kc) || kc=='') return '';
            return kc.replace(/\./g, '_', kc);
        },


        //lvl path
        lvlPath() {
            let lvl = this.lvl;
            if (lvl<=0) return '0';
            return Array.from(Array(lvl)).map((i,idx)=>idx);
        },

        //判断 treekey 是否为空
        emptyKey() {
            let k = this.ctxkey;
            return k === '';
        },

        //给 row/rhead 增加 subopened class
        subopenedCls() {
            return this.subopened ? ' subopened' : '';
        },

        /**
         * 可编辑性 判断
         */
        //新增子项（删除子项）
        allowAddNewSubKey() {
            return !this.readonly && this.subEditable;
        },
        //删除当前项
        allowDelKey() {
            return !this.readonly && this.rootEditable;
        },
    },
    created() {
        if (this.valueTypeIs('null','undefined')) {
            this.context = this.valueType;
        }
    },
    methods: {
        //判断 valueType 
        valueTypeIs(...types) {
            return types.includes(this.valueType);
        },

        /**
         * 显示/隐藏 子项
         */
        toggleSubopened() {
            let empty = this.$is.empty(this.context),
                subo = this.subopened;
            if (empty) {
                //子项内容为空时，调用新增 row 
                this.newRow();
            } else {
                //子项不为空时，toggle subopened
                this.subopened = !subo;
            }
        },

        /**
         * 编辑
         */
        //新增 context[newidx] || context.newkey
        newRow() {
            if (this.readonly) return;
            let vis = this.valueTypeIs,
                kl = this.keyColumnLabel,
                vl = this.valueColumnLabel,
                kv = `新${kl}`,
                vv = `新${vl}`;
            if (vis('array')) {
                this.context.push(vv);
            } else if (vis('object')) {
                this.context = Object.assign(this.context, {
                    [kv]: vv
                });
            }
            this.emitChange();
            this.$wait(100).then(()=>{
                if (this.subopened) this.subopened = false;
                this.subopened = true;
            });
            
        },
        //删除 context[i] || context.i
        delRow(i) {
            if (this.readonly) return;
            let vis = this.valueTypeIs;
            if (vis('array')) {
                this.subopened = false;
                this.context.splice(i,1);
                this.$wait(10).then(() => {
                    this.subopened = true;
                });
            } else if (vis('object')) {
                let ctx = this.context;
                Reflect.deleteProperty(ctx, i);
                this.context = Object.assign({}, ctx);
            }
            this.emitChange();
        },
        //编辑 context
        editRow(event) {
            if (this.readonly) return;
            let val = event.target.value;
            if (this.$is.json(val)) val = JSON.parse(val);
            this.context = val;
            this.emitChange();
            event.target.blur();
        },
        //编辑 treekey
        editRowKey(event) {
            if (this.readonly) return;
            let is = this.$is,
                okey = this.ctxkey,
                nkey = event.target.value,
                rk = event.target.id,
                rka = rk.split('_');

            rka.shift();
            rka.splice(-1,1,nkey);
            rk = rka.join('_');
            event.target.blur();
            //console.log(rk);

            //提交修改
            this.$emit('edit-row-key', okey, nkey);
            
            //auto-focus value input
            this.$wait(100).then(()=>{
                this.focusRowValue(rk);
            });
        },
        //value-input auto-focus
        focusRowValue(relkey) {
            let is = this.$is,
                rk = 'val_'+relkey,
                inps = document.querySelectorAll('input[name=jsoner_row_value]');
            if (inps.length<=0) return;
            let vinps = [];
            for (let inp of inps) {
                if (inp.id && is.string(inp.id) && inp.id == rk) {
                    vinps.push(inp);
                }
            }
            //console.log(rk);
            //console.log(inps);
            //console.log(vinps);
            //return;
            if (vinps.length>0) {
                let vinp = vinps[0];
                //console.log(vinp);
                vinp.focus();
                this.$wait(10).then(()=>{
                    //console.log(document.activeElement);
                    vinp.select();
                });
            }
        },
        //响应子项目的 edit-row-key 事件
        doEditRowKey(okey, nkey) {
            if (this.readonly) return;
            if (this.valueType!='object') return;  //仅 object key 可编辑
            let tgt = this.context[okey],
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
            this.context = Object.assign({}, ctx);
            this.emitChange();
        },

        //input focus
        inputFocus(event) {
            //console.log(event);
            let inp = event.target;
            if (this.$is.empty(inp)) return;
            if (this.readonly) {
                inp.blur();
            } else {
                inp.select();
            }
        },

        //emitChange
        emitChange() {
            this.$emit('input', this.context);
            this.$emit('change', this.context);
        },
    }
}
</script>

<style>

</style>