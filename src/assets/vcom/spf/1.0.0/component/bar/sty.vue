<template>
    <PRE@-ctn
        size="m"
        shape="sharp"
        tightness="normal"
        v-slot="{cntProps, cntClass, cntStyle}"
    >
        <PRE@-bar
            icon="settings"
            :icon-props="{
                mg: 'xl',
                mgPo: 'r'
            }"
            inner-content
            mg="xl"
            mg-po="t"
            v-bind="cntProps"
            :root-class="cntClass"
            :root-style="cntStyle"
        >
            <PRE@-btn
                icon="settings"
                :icon-props="{
                    shape: 'sharp',
                    icon: 'flag'
                }"
                label="设置"
                type="primary"
                closeable
                :hoverable="false"
            ></PRE@-btn>
            <PRE@-btn
                icon="settings"
                :icon-props="{
                    shape: 'sharp',
                    icon: 'flag'
                }"
                label="设置"
                type="success"
                shape="pill"
                astitle
            ></PRE@-btn>
            <PRE@-btn
                icon="settings"
                label="设置"
                type="danger"
                active
                no-gap
            ></PRE@-btn>
            <PRE@-btn
                icon="settings"
                label="设置"
                type="warn"
                shape="sharp"
                no-gap
            ></PRE@-btn>
            <PRE@-btn
                icon="settings"
                label="设置"
                type="cyan"
                effect="fill"
            ></PRE@-btn>
            <PRE@-btn
                icon="settings"
                label="设置"
                stretch="grow"
            ></PRE@-btn>
        </PRE@-bar>

        <PRE@-bar
            inner-content
            inner-content-rows
            mg="xl"
            mg-po="t"
            border="bd-m bd-po-all bdc-m"
            shape="round"
            v-bind="cntProps"
            :root-class="cntClass"
            :root-style="cntStyle"
            v-slot="{cntProps}"
        >
            <PRE@-bar 
                :size="cntProps.size"
                tightness="none"
                root-class="fc-l1 mg-m mg-po-r flex-x-end"
                root-style="width: 128px;"
            >Label:</PRE@-bar>
            <PRE@-ctn
                grow-in-bar
                inner-content
                v-bind="cntProps"
                root-style="width: 720px;"
            >
                <PRE@-bar :size="cntProps.size" tightness="none">
                    <el-input size="small"></el-input>
                </PRE@-bar>
                <PRE@-ctn
                    :size="cntProps.size"
                    border="bd-m bd-po-all bdc-m"
                    shape="round"
                    mg="xs"
                    mg-po="y"
                    inner-content
                >
                    <span>row 1</span>
                    <span>row 2</span>
                    <span>row 3</span>
                    <span>row 4</span>
                </PRE@-ctn>
            </PRE@-ctn>
        </PRE@-bar>

        <PRE@-bar>
            <PRE@-ctn-vitem
                item-key="foooo"
                v-model="cvitemCollapse.a"
                icon="btn--message"
                label="折叠面板测试"
                info="这是标题更多信息"
                info-class="fs-s"
                border="bd-m"
            >
                <div class="po" style="height: 128px;">content</div>
            </PRE@-ctn-vitem>
        </PRE@-bar>
        <PRE@-bar>
            <PRE@-ctn-vitem
                item-key="barrr"
                v-model="cvitemCollapse.b"
                size="medium"
                icon="btn--message"
                label="折叠面板测试"
                info="这是标题更多信息"
                info-class="fs-s"
                border="bd-m"
            >
                <div class="po" style="height: 128px;">content</div>
            </PRE@-ctn-vitem>
        </PRE@-bar>
        <PRE@-bar>
            <PRE@-ctn-vitem
                item-key="jazzz"
                v-model="cvitemCollapse.c"
                icon="btn--message"
                label="折叠面板测试"
                info="这是标题更多信息"
                info-class="fs-s"
                border="bd-m"
            >
                <div class="po" style="height: 128px;">content</div>
            </PRE@-ctn-vitem>
        </PRE@-bar>

    </PRE@-ctn>
</template>

<script>
import mixinAutoProps from '../../mixin/auto-props';
export default {
    mixins: [mixinAutoProps],
    props: {
        
    },
    data() {return {
        auto: {
            element: {
                root: {
                    props: {},
                    accept: {
                        atom: true,
                        extra: true
                    }
                },
            },
            prefix: 'bar',
            csvk: {
                size: 'bar',
                color: 'fc',
            },
            extra: {
                disabled: true
            },
        },

        cvitemCollapse: {
            a: false,
            b: true,
            c: true
        },
    }},
    computed: {

    },
    methods: {

    }
}
</script>

<style>

</style>