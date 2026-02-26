<template>
    <PRE@-block
        v-bind="$attrs"
        @block-created="setBlockStyProps"
    >
        <template v-slot:default="{styProps}">
            <slot v-bind="{styProps}"></slot>
        </template>
    </PRE@-block>
</template>

<script>
export default {
    model: {
        prop: 'value',
        event: 'change'
    },
    props: {
        //v-model 当前折叠面板选中的 item-name 1 个 或 多个
        value: {
            type: Array,
            default: () => []
        },
    },
    data() {return {
        //根 PRE@-block 组件的 styProps
        blockStyProps: {},

        /**
         * 缓存所有 collapse-item {title,name,icon}
         */
        items: {
            /*
            item-name: {
                title: '',
                name: '',
                icon: '',
                active: false,
            },
            ...
            */
        },
    }},
    computed: {

        //获取 this.items 中包含的所有 collapse-item 组件实例的 name 数组，按 items[name].idx 排序
        itemList() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                items = this.items,
                its = [];
            if (!iso(items)) return [];
            this.$each(items, (item, name) => {
                its.push({
                    name,
                    idx: item.idx
                });
            });
            //按 item.idx 排序
            its.sort((a,b) => a.idx - b.idx);
            //输出 name 数组
            return its.map(i => i.name);
        },
        //获取所有 active == true 的 collapse-item name [] 
        activeItemList() {
            return this.getActiveItemList();
        },
    },
    watch: {
        //value 监听外部传入的 值
        value: {
            handler(nv, ov) {
                let is = this.$is,
                    iso = o => is.plainObject(o) && !is.empty(o),
                    isa = a => is.array(a) && a.length>0,
                    val = this.value,
                    items = this.items;
                //if (!iso(items)) return false;
                if (!isa(val)) {
                    if (iso(items)) {
                        //取消所有 item 的 active 状态
                        this.$each(items, (item, name) => {
                            if (item.active) {
                                this.$set(this.items[name], 'active', false);
                                item.ins.toggleActive(false, false);
                            }
                        });
                    }
                } else {
                    //先创建
                    this.$each(val, name => {
                        if (!iso(items[name])) {
                            this.$set(this.items, name, Object.assign({}));
                        }
                    });
                    //根据 val 中包含的 name 设置 active
                    this.$each(this.items, (item, name) => {
                        let active = val.includes(name);
                        if (item.active !== active) {
                            this.$set(this.items[name], 'active', active);
                            if (item.ins && is.vue(item.ins)) item.ins.toggleActive(active, false);
                        }
                    });
                }
                return true;
            },
            immediate: true,
        },
    },
    methods: {
        //响应此 collapse 组件的根组件 PRE@-block 的 block-created 事件
        setBlockStyProps(styProps) {
            console.log(styProps);
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isd = d => is.defined(d),
                ps = {};
            if (iso(styProps)) {
                this.$each(styProps, (v,k)=>{
                    //需要透传的 styProps
                    //边框样式
                    if (k==='hasBd') {
                        ps.bd = styProps.bd;
                        ps.bdPo = 'b';  //styProps.bdPo
                        ps.bdc = styProps.bdc;
                        if (styProps.bdc!=='') {
                            ps.color = styProps.bdc;
                        }
                        return true;
                    }
                    //size
                    if(k==='size') {
                        ps.size = styProps.size;
                        return true;
                    }
                    //color
                    if (k==='color') {
                        ps.color = styProps.color;
                        return true;
                    }
                    //animate
                    if (k.startsWith('animate')) {
                        ps[k] = v;
                        return true;
                    }
                });
                if (iso(ps)) {
                    this.blockStyProps = Object.assign({}, ps);
                }
            }
        },

        //在 collapse-item 创建阶段，将 collapse-item 组件实例添加到 this.items 
        async addCollapseItem(item) {
            let is = this.$is;
            //排除不合法 或 已添加过的 item
            if (!is.vue(item)) return false;
            let oitem = is.defined(this.items[item.name]) ? this.items[item.name] : {};
            //插入 items {}
            this.$set(this.items, item.name, Object.assign(oitem, {
                name: item.name,
                title: item.title,
                icon: item.icon,
                //组件实例 uid 用于排序
                idx: item._uid,
                //组件实例
                ins: item,
            }));
            await this.$wait(10);
            //激活状态
            if (!is.defined(this.items[item.name].active)) {
                this.$set(this.items[item.name], 'active', false);
            }
            if (this.items[item.name].active===true) {
                item.toggleActive(true, false);
            }
            //直接修改 item 组件实例的 props
            //console.log(this.$attrs);
            await this.setCollapseItemStyProps(item);
            //创建事件处理
            item.$on('toggle-active', this.whenCollapseItemToggleActive);

        },
        //将 collapse 父组件样式参数 透传到 collapse-item
        async setCollapseItemStyProps(item) {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                isa = a => is.array(a) && a.length>0,
                ps = this.blockStyProps;
            if (!iso(ps)) return false;
            this.$each(ps, (v,k) => {
                //Vue.set(item.$attrs, k, v);
                item.$attrs[k] = v;
            });
            await this.$wait(10);
            console.log(item);
            return true;
        },

        //获取所有 active == true 的 collapse-item name [] 
        getActiveItemList() {
            let is = this.$is,
                iso = o => is.plainObject(o) && !is.empty(o),
                items = this.items,
                its = [];
            if (!iso(items)) return [];
            this.$each(items, (item, name) => {
                if (item.active === true) {
                    its.push(name);
                }
            });
            return its;
        },

        //针对每个 collapse-item 的事件处理函数
        //item.$emit('toggle-active', item.name, item.itemActive)
        whenCollapseItemToggleActive(name, active) {
            console.log('parent toggle-active');
            let is = this.$is,
                al = this.getActiveItemList();
            if (
                (active && !al.includes(name)) ||
                (!active && al.includes(name))
            ) {
                this.$set(this.items[name], 'active', active);
                //触发 v-model change 事件
                this.$emit('change', this.getActiveItemList());
            }
        },
    }
}
</script>

<style>

</style>