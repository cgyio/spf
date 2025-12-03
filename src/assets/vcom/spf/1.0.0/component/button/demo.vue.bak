<template>
    <div class="cv-demo">
        <div class="cv-demo-row cv-demo-tit">cv-button 组件</div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">icon 参数</span>
            <template v-for="ici of demoIcons">
                <span class="f-d1 mg-r-xs" :key="'cv_button_demo_icons_'+ici">{{ici}}</span>
                <cv-button
                    :key="'cv_button_demo_icons_comp_'+ici"
                    :icon="ici"
                    custom-class="mg-r-xl mg-l-xs"
                ></cv-button>
            </template>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">label 参数</span>
            <span class="f-d1 mg-r-xs">按钮文字</span>
            <cv-button
                icon="md-sharp-portable-wifi-off"
                label="WIFI Off"
                custom-class="mg-r-xl mg-l-xs"
            ></cv-button>
            <cv-button
                icon="md-sharp-portable-wifi-off"
                label="中文字"
                custom-class="mg-r-xl mg-l-xs"
            ></cv-button>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">size 参数</span>
            <template v-for="(szi,szidx) of demoSizes">
                <span class="f-d1 mg-r-xs" :key="'cv_button_demo_sizes_'+szi">{{szi}}</span>
                <cv-button
                    :key="'cv_button_demo_sizes_comp_'+szi"
                    icon="vant-setting-fill"
                    :size="szi"
                    label="按钮文字"
                    :type="demoTypes[szidx]"
                    custom-class="mg-r-xl mg-l-xs"
                ></cv-button>
            </template>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">type 参数</span>
            <span class="f-d1 mg-r-xs" style="margin-left:200px;">默认空值</span>
            <cv-button
                :key="'cv_button_demo_types_comp_'+tpi"
                icon="md-sharp-local-police"
                label="按钮"
                custom-class="mg-r-xl mg-l-xs"
            ></cv-button>
            <template v-for="tpi of demoTypes">
                <span class="f-d1 mg-r-xs" :key="'cv_button_demo_types_'+tpi">{{tpi}}</span>
                <cv-button
                    :key="'cv_button_demo_types_comp_'+tpi"
                    icon="md-sharp-local-police"
                    :type="tpi"
                    label="按钮"
                    custom-class="mg-r-xl mg-l-xs"
                ></cv-button>
            </template>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">active 参数</span>
            <span class="f-d1 mg-r-xs">开启/关闭 active</span>
            <el-switch v-model="demoActive" class="mg-r-xl" title="改变 active 参数"></el-switch>
            <span class="f-d1 mg-r-xs">默认空值</span>
            <cv-button
                :key="'cv_button_demo_types_comp_'+tpi"
                icon="md-sharp-local-police"
                label="按钮"
                :active="demoActive"
                custom-class="mg-r-xl mg-l-xs"
            ></cv-button>
            <template v-for="tpi of demoTypes">
                <span class="f-d1 mg-r-xs" :key="'cv_button_demo_types_'+tpi">{{tpi}}</span>
                <cv-button
                    :key="'cv_button_demo_types_comp_'+tpi"
                    icon="md-sharp-local-police"
                    :type="tpi"
                    label="按钮"
                    :active="demoActive"
                    custom-class="mg-r-xl mg-l-xs"
                ></cv-button>
            </template>
        </div>
        
        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">popout 参数</span>
            <span class="f-d1 mg-r-xs">开启/关闭 popout</span>
            <el-switch v-model="demoPopout" class="mg-r-xl" title="改变 popout 参数"></el-switch>
            <cv-button
                icon="md-sharp-portable-wifi-off"
                :popout="demoPopout"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-portable-wifi-off"
                label="按钮"
                :popout="demoPopout"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                v-for="ci of demoTypes"
                :key="'cv_button_demo_popout_'+ci"
                icon="md-sharp-portable-wifi-off"
                label="按钮"
                :type="ci"
                :popout="demoPopout"
                custom-class="mg-r-xl"
            ></cv-button>
        </div>
        
        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3"></span>
            <span class="f-d1 mg-r-xl">指定 popout 颜色</span>
            <cv-button
                v-for="(ci,cidx) of demoTypes"
                :key="'cv_button_demo_popout_'+ci"
                icon="md-sharp-portable-wifi-off"
                label="按钮"
                :type="ci"
                :popout="demoPopouts[cidx]"
                custom-class="mg-r-xl"
            ></cv-button>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">text 参数</span>
            <span class="f-d1 mg-r-xs">开启/关闭 text</span>
            <el-switch v-model="demoText" class="mg-r-xl" title="改变 text 参数"></el-switch>
            <cv-button
                icon="md-sharp-keyboard"
                label="按钮 dommy529"
                :text="demoText"
                :disabled="demoDisabled"
                custom-class="mg-r-xl"
                custom-style="color:var(--color-fc-d3);"
            ></cv-button>
            <cv-button
                v-for="ci of demoTypes"
                :key="'cv_button_demo_disabled_'+ci"
                icon="md-sharp-keyboard"
                label="按钮 dommy529"
                :type="ci"
                :text="demoText"
                :disabled="demoDisabled"
                custom-class="mg-r-xl"
            ></cv-button>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">disabled 参数</span>
            <span class="f-d1 mg-r-xs">开启/关闭 disabled</span>
            <el-switch v-model="demoDisabled" class="mg-r-xl" title="改变 disabled 参数"></el-switch>
            <cv-button
                v-for="ci of demoTypes"
                :key="'cv_button_demo_disabled_'+ci"
                icon="md-sharp-keyboard"
                label="按钮"
                :type="ci"
                :disabled="demoDisabled"
                custom-class="mg-r-xl"
            ></cv-button>
        </div>
        
        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">iconRight 参数</span>
            <span class="f-d1 mg-r-xs">开启/关闭 iconRight</span>
            <el-switch v-model="demoIconRight" class="mg-r-xl" title="改变 iconRight 参数"></el-switch>
            <cv-button
                icon="md-sharp-palette"
                :icon-right="demoIconRight"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                :icon-right="demoIconRight"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                type="primary"
                :icon-right="demoIconRight"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                type="success"
                :plain="true"
                :icon-right="demoIconRight"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                type="success"
                :plain="true"
                :active="true"
                :icon-right="demoIconRight"
                custom-class="mg-r-xl"
            ></cv-button>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">round 参数</span>
            <span class="f-d1 mg-r-xs">开启/关闭 round</span>
            <el-switch v-model="demoRound" class="mg-r-xl" title="改变 round 参数"></el-switch>
            <cv-button
                icon="md-sharp-local-police"
                :round="demoRound"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-local-police"
                label="按钮"
                :round="demoRound"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-local-police"
                label="按钮"
                type="primary"
                :round="demoRound"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-local-police"
                label="按钮"
                type="cyan"
                :plain="true"
                :round="demoRound"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-local-police"
                label="按钮"
                type="cyan"
                :plain="true"
                :active="true"
                :round="demoRound"
                custom-class="mg-r-xl"
            ></cv-button>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">square 参数</span>
            <span class="f-d1 mg-r-xs">开启/关闭 square</span>
            <el-switch v-model="demoSquare" class="mg-r-xl" title="改变 square 参数"></el-switch>
            <cv-button
                icon="md-sharp-local-police"
                :square="demoSquare"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-local-police"
                label="按钮"
                :square="demoSquare"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-local-police"
                label="按钮"
                type="primary"
                :square="demoSquare"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-local-police"
                label="按钮"
                type="warn"
                :plain="true"
                :square="demoSquare"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-local-police"
                label="按钮"
                type="warn"
                :plain="true"
                :active="true"
                :square="demoSquare"
                custom-class="mg-r-xl"
            ></cv-button>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">plain 参数</span>
            <span class="f-d1 mg-r-xs">开启/关闭 plain</span>
            <el-switch v-model="demoPlain" class="mg-r-xl" title="改变 plain 参数"></el-switch>
            <cv-button
                icon="vant-setting-fill"
                :plain="demoPlain"
                :disabled="demoDisabled"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="vant-setting-fill"
                label="按钮"
                :plain="demoPlain"
                :disabled="demoDisabled"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="vant-setting-fill"
                label="按钮"
                :active="true"
                :plain="demoPlain"
                :disabled="demoDisabled"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="vant-setting-fill"
                label="按钮"
                type="primary"
                :plain="demoPlain"
                :disabled="demoDisabled"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="vant-setting-fill"
                label="按钮"
                type="primary"
                :active="true"
                :plain="demoPlain"
                :disabled="demoDisabled"
                custom-class="mg-r-xl"
            ></cv-button>
        </div>

        <div class="cv-demo-row">
            <span class="cv-demo-label f-l f-d3">spin 参数</span>
            <span class="f-d1 mg-r-xs">开启/关闭 spin</span>
            <el-switch v-model="demoSpin" class="mg-r-xl" title="改变 spin 参数"></el-switch>
            <cv-button
                icon="md-sharp-palette"
                :spin="demoSpin"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                :spin="demoSpin"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                type="primary"
                :spin="demoSpin"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                type="primary"
                :active="true"
                :spin="demoSpin"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                type="danger"
                :plain="true"
                :spin="demoSpin"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                type="danger"
                :plain="true"
                :active="true"
                :spin="demoSpin"
                custom-class="mg-r-xl"
            ></cv-button>
            <cv-button
                icon="md-sharp-palette"
                label="按钮"
                type="danger"
                :active="true"
                :spin="demoSpin"
                :disabled="true"
                custom-class="mg-r-xl"
            ></cv-button>
        </div>
    </div>
</template>

<script>
import mixinBase from '../../mixin/base';

export default {
    mixins: [mixinBase],
    props: {},
    data() {return {

        demoIcons: [
            'md-sharp-portable-wifi-off', 'md-sharp-keyboard', 
            'spiner-3-dots-scale', 'spiner-wind-toy',
            'vant-setting-fill', 'vant-apple'
        ],
        demoSizes: [
            'mini','small','medium','large','giant'
        ],
        demoTypes: [
            'danger','orange','warn','success','cyan','primary','purple',
            'info','disable',
        ],
        demoPopouts: [
            'fc.d3','fc.$','warn.d3','success.d1','cyan.d2','fc','purple.l1',
            'info.d3','#74c0fc',
        ],

        demoActive: true,
        demoPopout: true,
        demoText: true,
        demoIconRight: true,
        demoRound: true,
        demoSquare: true,
        demoPlain: true,
        demoSpin: true,
        demoDisabled: true,
    }},
    computed: {

    },
    methods: {

    }
}
</script>

<style>

</style>