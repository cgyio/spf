<template>
    <cv-el-switch
        v-if="$uiReady"
        v-model="cacheDarkMode"
        active-color="cyan"
        inactive-color="orange"
        active-icon="md-sharp-dark-mode"
        inactive-icon="md-sharp-light-mode"
        :async-switch="toggleDarkMode"
        :class="computedClass"
        :style="computedStyle"
        v-tip:left="(cacheDarkMode==true?'关闭':'开启')+'暗黑模式'"
    >
        <div
            v-if="dmToggleMaskOn==true"
            :style="'position:fixed;left:0;top:0;width:100vw;height:100vh;overflow:hidden;background-color:'+(cacheDarkMode==true?'#000':'#fff')+';display:flex;align-items:center;justify-content:center;font-size:18px;color:'+(cacheDarkMode==true?'#fff':'#000')+';font-weight:bold;z-index:100000;opacity:'+dmToggleMaskOpacity+';transition:opacity 0.5s;'"
        >
            <svg 
                style="width:1em;height:1em;fill:currentColor;overflow:hidden;font-size:24px;margin-right:32px;"
                aria-hidden="true"
            >
                <use xlink:href="#spiner-180-ring"></use>
            </svg>
            <span>{{ '正在'+(cacheDarkMode==true ? '关闭' : '开启')+'暗黑模式 ...' }}</span>
        </div>
    </cv-el-switch>
</template>

<script>
import mixinBase from '/vue/@/mixins/base/base';

export default {
    mixins: [mixinBase],
    props: {
        //width
        //width: {
        //    type: [String, Number],
        //    default: 46
        //},

        //指定 css 切换耗时，默认 3000 毫秒
        toggleDura: {
            type: Number,
            default: 3000
        },
    },
    data() {return {
        //darkMode 缓存
        cacheDarkMode: false,

        //切换 darkMode 时的 mask 层，遮挡 界面变化
        dmToggleMaskOn: false,
        dmToggleMaskOpacity: 0,

        //body overflow 属性
        bdov: '',
    }},
    computed: {},
    created() {
        this.$until(()=>this.$uiReady).then(()=>{
            this.cacheDarkMode = this.$UI.darkMode;
        });
    },
    methods: {

        async toggleDarkMode(cacheDarkMode) {
            //this.toggling = true;
            //await this.$wait(150);
            //this.toggling = false;
            //await this.$wait(300);
            this.hideBodyScroll();
            this.dmToggleMaskOn = true;
            await this.$wait(100);
            this.dmToggleMaskOpacity = 1;
            await this.$wait(500);
            this.$UI.toggleDarkMode();
            await this.$wait(this.toggleDura);
            this.dmToggleMaskOpacity = 0;
            await this.$wait(500);
            this.restoreBodyScroll();
            this.dmToggleMaskOn = false;
            return this.$UI.darkMode;
        },

        //隐藏 body 的滚动条，避免在切换 css 时 滚动条闪现
        hideBodyScroll() {
            let bd = document.querySelector('body'),
                sty = window.getComputedStyle(bd),
                ov = sty.overflow;
            this.bdov = ov;
            bd.style.overflow = 'hidden';
        },
        restoreBodyScroll() {
            let bd = document.querySelector('body'),
                ov = this.bdov;
            bd.style.overflow = ov;
        },
    }
}
</script>

<style>

</style>