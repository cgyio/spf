<template>
    <cv-doc
        component="cv-el-*"
        component-set="base"
        box-width="640px"
    >
        <template v-slot:demo-comp-box>
            <div class="with-mg">
                <cv-el-select
                    v-model="demoParams.icon"
                    :options="demoIcons"
                    filterable
                    allow-create
                    clearable
                    custom-style="width: 256px;"
                ></cv-el-select>
                <cv-el-select
                    v-model="demoParams.icon"
                    :options="demoIcons"
                    filterable
                    allow-create
                    clearable
                    disabled
                    custom-class="mg-l-xs"
                    custom-style="width: 256px;"
                ></cv-el-select>
            </div>
            <div class="with-mg">
                <cv-el-select
                    v-model="demoParams.sizes"
                    :options="demoSizes"
                    filterable
                    allow-create
                    clearable
                    multiple
                    custom-style="width: 360px;"
                ></cv-el-select>
            </div>
            <div class="with-mg">
                <cv-el-date-picker
                    v-model="dt"
                    custom-class="cv-el-fml-normal"
                    custom-style="width: 192px;"
                ></cv-el-date-picker>
                <cv-el-date-picker
                    v-model="dts"
                    type="daterange"
                    disabled
                    range-separator="至"
                    start-placeholder="开始"
                    end-placeholder="结束"
                    custom-class="mg-l-xs"
                    custom-style="width: 280px;"
                ></cv-el-date-picker>
            </div>
            <div class="with-mg">
                <cv-el-date-picker
                    v-model="dt"
                    type="datetime"
                    custom-style="width: 200px;"
                ></cv-el-date-picker>
                <cv-el-date-picker
                    v-model="dts"
                    type="datetimerange"
                    range-separator="至"
                    start-placeholder="开始"
                    end-placeholder="结束"
                    custom-class="cv-el-fml-normal mg-l-xs"
                    custom-style="width: 380px;"
                ></cv-el-date-picker>
            </div>
            <div class="with-mg">
                <cv-el-time-picker
                    v-model="tm"
                    size="large"
                    :picker-options="{selectableRange:'09:00:00 - 18:00:00'}"
                    custom-style="width: 192px;"
                ></cv-el-time-picker>
                <cv-el-time-picker
                    v-model="tms"
                    :picker-options="{selectableRange:'09:00:00 - 18:00:00'}"
                    is-range
                    custom-class="mg-l-xs"
                    custom-style="width: 320px;"
                ></cv-el-time-picker>
            </div>
            <div class="with-mg">
                <cv-el-input-number
                    v-model="num"
                    :max="10"
                    :min="1"
                    :step="1"
                    custom-class="cv-el-fml-normal mg-r-xs"
                    custom-style="width: 128px;"
                ></cv-el-input-number>
                <cv-el-input-number
                    v-model="num"
                    :max="10"
                    controls-position="right"
                    custom-style="width: 128px;"
                ></cv-el-input-number>
            </div>
            <div class="with-mg">
                <cv-el-input
                    v-model="demoParams.customClass"
                    custom-class="mg-r-xs"
                    custom-style="width: 128px;"
                ></cv-el-input>
                <cv-el-input
                    v-model="demoParams.customClass"
                    type="password"
                    icon="btn-lock"
                    icon-right
                    placeholder="密码"
                    v-tip.dark.danger="'通过 v-tip 指定的 Tip'"
                    custom-class="mg-r-xs"
                    custom-style="width: 128px;"
                ></cv-el-input>
                <cv-el-input
                    v-model="demoParams.customClass"
                    v-tip="'Tip：'+demoParams.customClass"
                    custom-style="width: 192px;"
                >
                    <template v-slot:prepend>
                        <span>密码</span>
                    </template>
                </cv-el-input>
            </div>
            <div class="with-mg">
                <cv-el-input
                    v-model="demoParams.customClass"
                    type="textarea"
                    :rows="4"
                    custom-style="flex:1;"
                ></cv-el-input>
            </div>
            <div class="with-mg">
                <cv-el-tag 
                    custom-class="mg-r-xs"
                >md-sharp-*</cv-el-tag>
                <cv-el-input
                    v-model="demoParams.customClass"
                    custom-class="mg-r-xs"
                    custom-style="width: 96px;"
                ></cv-el-input>
                <cv-el-switch
                    v-model="demoParams.spin"
                    v-tip="demoParams.spin ? 'Spinning' : 'Wait'"
                    active-color="cyan"
                    inactive-color="danger"
                ></cv-el-switch>
                <cv-el-switch
                    v-model="demoParams.spin"
                    v-tip="demoParams.spin ? 'Spinning' : 'Wait'"
                    disabled
                    custom-class="mg-l-xs"
                ></cv-el-switch>
                <cv-el-switch
                    v-model="demoParams.spin"
                    v-tip="demoParams.spin ? 'Spinning' : 'Wait'"
                    active-text="加载中..."
                    inactive-text="就绪"
                    custom-class="mg-l-xs"
                ></cv-el-switch>
                <cv-el-switch
                    v-model="demoParams.spin"
                    v-tip="demoParams.spin ? 'Spinning' : 'Wait'"
                    active-color="yellow"
                    inactive-color="light"
                    active-icon="vant-check"
                    inactive-icon="vant-close"
                    :async-switch="asyncToggleSpin"
                    custom-class="mg-l-xs"
                ></cv-el-switch>
            </div>
            <div class="with-mg">
                <el-slider
                    v-model="num"
                    class="mg-r-xl"
                    style="width: 128px;"
                ></el-slider>
                <cv-el-slider
                    v-model="num"
                    custom-class="mg-r-xl"
                    custom-style="width: 128px;"
                ></cv-el-slider>
                <cv-el-slider
                    v-model="num"
                    disabled
                    custom-style="width: 128px;"
                ></cv-el-slider>
            </div>
            <div class="with-mg">
                <cv-el-tag
                    size="small"
                >图标Tag</cv-el-tag>
                <cv-el-tag
                    icon="md-sharp-tag-faces"
                >图标Tag</cv-el-tag>
                <cv-el-tag
                    icon="md-sharp-tag-faces"
                    effect="plain"
                    v-tip="{content:['多行 Tip','第一行内容比较长比较长，有标点符号！','第二行内容']}"
                >图标Tag</cv-el-tag>
                <cv-el-tag
                    icon="md-sharp-tag-faces"
                    effect="dark"
                    v-tip="{content:'tooltip 内容',type:'danger',effect:'dark'}"
                >图标Tag</cv-el-tag>
            </div>
            <div class="with-mg">
                <cv-el-tag
                    type="cyan"
                    icon="md-sharp-male"
                    closable
                    v-tip="{content:'tooltip 内容'}"
                >图标Tag</cv-el-tag>
                <cv-el-tag
                    type="cyan"
                    icon="md-sharp-male"
                    effect="plain"
                    closable
                    v-tip="{content:'tooltip 内容'}"
                >图标Tag</cv-el-tag>
                <cv-el-tag
                    type="cyan"
                    icon="md-sharp-male"
                    effect="dark"
                    closable
                    v-tip="{content:'tooltip 内容'}"
                >图标Tag</cv-el-tag>
            </div>
            <div class="with-mg">
                <cv-el-tag
                    size="small"
                    type="orange"
                    icon="md-sharp-male"
                    separate
                    sep-label="分割"
                    sep-value="Tag"
                    closable
                    v-tip="{content:'tooltip 内容'}"
                ></cv-el-tag>
                <cv-el-tag
                    type="orange"
                    icon="md-sharp-tag-faces"
                    effect="plain"
                    separate
                    sep-label="分割"
                    sep-value="Tag"
                    v-tip="{content:'tooltip 内容'}"
                ></cv-el-tag>
                <cv-el-tag
                    type="orange"
                    color="white"
                    icon="md-sharp-tag-faces"
                    effect="dark"
                    separate
                    sep-label="分割"
                    sep-value="Tag"
                    v-tip="{content:'tooltip 内容'}"
                ></cv-el-tag>
            </div>
        </template>
        <template v-slot:demo-comp-ctrl>
            <cv-doc-ctrl
                prop-key="icon"
                prop-title="图标名称，以 Symbol 方式使用 svg 图标库"
                v-loading="demoParams.spin"
            >
                <span>
                    <cv-el-tag 
                        v-tip="'vant-*'"
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
            sizes: ['mini'],
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
        tm: '',
        tms: [],
        num: 0,

    }},
    computed: {},
    methods: {
        handleCmd() {

        },

        async asyncToggleSpin(spin) {
            await this.$wait(1000);
            return !spin;
        },
    }
}
</script>

<style>

</style>