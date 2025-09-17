<template>
    <cv-doc
        component="cv-icon"
        component-set="base"
        box-width="540px"
    >
        <template v-slot:demo-comp-box>
            <cv-icon
                v-bind="demoParams"
            ></cv-icon>
        </template>
        <template v-slot:demo-comp-ctrl>
            <cv-doc-ctrl
                prop-key="icon"
                prop-title="图标名称，以 Symbol 方式使用 svg 图标库"
            >
                <span>
                    <cv-el-tag 
                        custom-class="mg-r-s"
                    >vant-*</cv-el-tag>
                    <cv-el-tag 
                        custom-class="mg-r-s"
                    >md-sharp-*</cv-el-tag>
                    <cv-el-tag 
                        icon="btn-shipped"
                        type="light"
                        effect="plain"
                        :hover="true"
                        :separate="true"
                        sep-label="多色图标："
                        sep-value="btn-*"
                        custom-class="mg-r-s"
                    ></cv-el-tag>
                    <cv-el-tag 
                        type="danger"
                        effect="plain"
                        :separate="true"
                        sep-label="动画图标："
                        sep-value="spiner-*"
                        custom-class="mg-r-s"
                    ></cv-el-tag>
                </span>
                <template v-slot:ctrl-diy>
                    <cv-el-select
                        v-model="demoParams.icon"
                        :options="demoIcons"
                        filterable
                        allow-create
                        clearable
                        disabled
                        custom-style="width: 256px;"
                    ></cv-el-select>
                    <cv-el-tag 
                        custom-class="mg-l-s"
                    >md-sharp-*</cv-el-tag>
                </template>
            </cv-doc-ctrl>

            <cv-doc-ctrl
                prop-key="size"
                prop-title="图标尺寸，可使用尺寸字符串，也可以使用纯数字"
            >
                <span class="mg-b-xs">可以使用这些尺寸字符串：</span>
                <span class="ctrl-info">mini, small, medium, large, giant</span>
                <span class="ctrl-info">xxs ~ xxxl</span>
                <span class="ctrl-info">48, 54, 64, 72, 88, 96 px</span>
                <span class="mg-t-s">也可以使用出数字，例如：72</span>
                <template v-slot:ctrl-diy>
                    <cv-el-select
                        v-model="demoParams.size"
                        filterable
                        allow-create
                        clearable
                        custom-style="width: 256px;"
                    >
                        <el-option
                            v-for="szi of demoSizes"
                            :key="'cv_icon_demo_sizes_'+szi"
                            :label="szi"
                            :value="szi"
                        ></el-option>
                    </cv-el-select>
                    <el-date-picker
                        v-model="dt"
                        style="width: 192px;"
                        class="cv-el mg-l-xs"
                        popper-class="cv-el-pop"
                    ></el-date-picker>
                    <el-date-picker
                        v-model="dt"
                        type="datetime"
                        style="width: 256px;"
                        class="cv-el mg-l-xs"
                        popper-class="cv-el-pop"
                    ></el-date-picker>
                    <el-date-picker
                        v-model="dts"
                        type="daterange"
                        range-separator="至"
                        start-placeholder="开始"
                        end-placeholder="结束"
                        style="width: 320px;"
                        class="cv-el mg-l-xs"
                        popper-class="cv-el-pop"
                    ></el-date-picker>
                    <!--<el-input-number
                        v-model="num"
                        style="width: 128px;"
                        class="cv-el mg-l-xs"
                    ></el-input-number>
                    <el-input
                        v-model="num"
                        style="width: 128px;"
                        class="mg-l-xs"
                    ></el-input>-->
                </template>
            </cv-doc-ctrl>

            <cv-doc-ctrl
                prop-key="type"
                prop-title="图标颜色类型，可使用所有主题预定义的颜色名称"
            >
                <span class="mg-b-xs">可以使用这些颜色名称：</span>
                <span class="ctrl-info">primary, danger, info, red, orange, cyan, bg, fc, fc-d3 等</span>
                <span class="ctrl-notice mg-t-s">注意：此参数对多色图标无效！！！</span>
                <template v-slot:ctrl-diy>
                    <cv-el-select
                        v-model="demoParams.type"
                        :options="demoTypes"
                        filterable
                        allow-create
                        clearable
                        custom-style="width: 256px;"
                    ></cv-el-select>
                </template>
            </cv-doc-ctrl>

            <cv-doc-ctrl
                prop-key="color"
                prop-title="更多的颜色定义，可使用 cssvar.color 中定义的所有颜色"
            >
                <span class="mg-b-xs">可以使用这些颜色名称：</span>
                <span class="ctrl-info">primary.d1, red.$, fc.d3 等</span>
                <span class="ctrl-info">
                    <span>可以通过 </span>
                    <el-tag size="small" class="f-m f-primary mg-x-s">cgy.loget()</el-tag>
                    <span> 方法从 cssvar.color 中读取颜色值的 所有可用 key</span>
                </span>
                <span class="mg-t-s">
                    <span>也可以使用 Hex/Rgb 颜色值，例如：</span>
                    <el-tag size="small" class="f-m f-primary mg-r-s">#ff0000</el-tag>
                    <el-tag size="small" class="f-m f-primary mg-r-s">rgba(123,123,123, 0.7)</el-tag>
                </span>
                <span class="ctrl-notice mg-t-s">注意：此参数对多色图标无效！！！</span>
                <template v-slot:ctrl-diy>
                    <cv-el-select
                        v-model="demoParams.color"
                        :options="demoColors"
                        filterable
                        allow-create
                        clearable
                        custom-style="width: 256px;"
                    ></cv-el-select>
                </template>
            </cv-doc-ctrl>

            <cv-doc-ctrl
                prop-key="spin"
                prop-title="图标自旋转，常用于 loading 状态标识"
            >
                <span>
                    <span>忽略 icon 参数值，一律使用 svg 动画图标：</span>
                    <el-tag size="small" class="f-m f-primary">spiner-180-ring</el-tag>
                </span>
                <template v-slot:ctrl-diy>
                    <el-switch v-model="demoParams.spin"></el-switch>
                </template>
            </cv-doc-ctrl>

            <cv-doc-ctrl
                prop-key="customClass"
                prop-title="额外的图标样式类，可使用主题定义的所有可用样式类"
            >
                <span>通常用于指定 margin/padding 等</span>
                <template v-slot:ctrl-diy>
                    <el-input 
                        v-model="demoParams.customClass" 
                        placeholder="输入样式类"
                        clearable
                        class="cv-el"
                        style="width: 256px;"
                    ></el-input>
                </template>
            </cv-doc-ctrl>

            <cv-doc-ctrl
                prop-key="customStyle"
                prop-title="额外的图标样式，使用 CSS Object"
            >
                <span>通常用于指定特殊样式，如：巨大的图标：fontSize: 256px</span>
                <template v-slot:ctrl-diy>
                    <cv-jsoner
                        v-model="demoParams.customStyle"
                        custom-style="width: 480px;"
                    ></cv-jsoner>
                </template>
            </cv-doc-ctrl>
        </template>
    </cv-doc>
</template>

<script>
import mixinBase from '/vue/@/mixins/base/base';

export default {
    mixins: [mixinBase],
    props: {},
    data() {return {

        //cv-icon 可操作的 参数
        demoParams: {
            icon: 'spiner-wind-toy',
            size: 'medium',
            type: '',
            color: '',
            spin: false,
            customClass: '',
            customStyle: {}
        },
        //针对 demoParams 中 每个参数的 说明
        demoParamsInfo: {
            icon: '图标名称，以 Symbol 方式使用 svg 图标库',
            size: '图标尺寸，可使用尺寸字符串，也可以使用纯数字',
            type: '图标颜色类型，可使用所有主题预定义的颜色名称',
            color: '更多的颜色定义，可使用 cssvar.color 中定义的所有颜色',
            spin: '图标自旋转，常用于 loading 标识',
            customClass: '额外的图标样式类，可使用主题定义的所有可用样式类',
            customStyle: '额外的图表样式，使用 CSS Object'
        },

        demoIcons: [
            'md-sharp-portable-wifi-off', 'md-sharp-keyboard', 
            'spiner-3-dots-scale', 'spiner-wind-toy',
            'vant-setting-fill', 'vant-apple'
        ],
        demoSizes: [
            'xxs','mini','small','medium','large','giant','xxl','xxxl',88,'96px',
            16,17,18,19,20,
        ],
        demoTypes: [
            'primary','danger','warn','success','info','disable',
            'orange','cyan','purple','brand',
            'fc-d3', 'fc', 'bg'
        ],
        demoColors: [
            'primary','primary.l1','primary.l2','primary.l3',
            'red','red.l1','red.l2','red.l3',
            'cyan','cyan.l1','cyan.l2','cyan.l3',
            //this.cssvar.color.fc.d3,
            '#ff0000'
        ],
        demoSpin: false,


        dt: new Date(),
        dts: [],
        num: 0,

    }},
    computed: {},
    methods: {

    }
}
</script>

<style>

</style>