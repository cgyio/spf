<template>
    <PRE@-block
        with-header
        v-bind="$attrs"
    >
        <template v-slot:header="{styProps}">
            <PRE@-bar
                v-if="title!==''"
                :size="styProps.size"
                :icon="icon"
                :color="styProps.color"
                :disabled="styProps.disabled"
                hoverable
                @click.native="toggleActive(!itemActive)"
            >
                <span>{{title}}</span>
                <span class="flex-1"></span>
                <PRE@-icon
                    :icon="itemActive ? 'keyboard-arrow-down' : 'keyboard-arrow-right'"
                    color="fc-l2"
                    size="small"
                ></PRE@-icon>
            </PRE@-bar>
        </template>
        <template v-if="itemActive" v-slot:default="{styProps}">
            <PRE@-block
                :bd-po="styProps.hasBd ? 't' : ''"
                :bdc="styProps.bdc"
            >
                <template v-slot:default="{styProps}">
                    <slot v-bind="{styProps}"></slot>
                </template>
            </PRE@-block>
        </template>
    </PRE@-block>
</template>

<script>
export default {
    props: {
        //此折叠面板的 激活状态 v-model
        active: {
            type: Boolean,
            default: false
        },
        //此折叠面板的 name 对应的值
        name: {
            type: [String, Number, Boolean],
            default: ''
        },

        //header icon
        icon: {
            type: String,
            default: ''
        },
        //header title
        title: {
            type: String,
            default: ''
        },
        //

    },
    data() {return {
        //active 的 内部状态
        itemActive: false,
    }},
    computed: {},
    watch: {
        //外部传入的 active
        active: {
            handler(nv, ov) {
                this.toggleActive(this.active, false);
            },
            immediate: true,
        },
    },
    created() {
        this.updateToParentCollapseWhenCreated();
    },
    methods: {

        //获取当前折叠面板 item 所在的 collapse 组件 未找到返回 undefined
        async getParentCollapse() {
            let is = this.$is,
                isc = v => is.vue(v) && is.string(v.$options.name) && v.$options.name.endsWith('-block-collapse'),
                pc = this.$parent;
            while (!(is.undefined(pc) || isc(pc))) {
                pc = pc.$parent;
            }
            await this.$wait(10);
            return pc;
        },
        //collapse-item 组件创建时，将相关参数 写回 父组件 collapse，同时从父组件获取一些内部参数
        async updateToParentCollapseWhenCreated() {
            let is = this.$is,
                pc = await this.getParentCollapse();
            //console.log(pc);
            if (is.vue(pc)) {
                return await pc.addCollapseItem(this);
            }
        },
        //切换 active 状态
        toggleActive(active=false, emit=true) {
            console.log('toggle active');
            if (this.itemActive !== active) {
                this.itemActive = active;
                //触发事件
                if (emit) this.$emit('toggle-active', this.name, active);
            }
        },
    }
}
</script>

<style>

</style>