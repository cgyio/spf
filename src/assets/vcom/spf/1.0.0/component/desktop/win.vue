<template>
    <div 
        :class="'cv-desk-win '+(customClass==''?'':customClass)"
        :style="'z-index:'+zIndex+';'"
        @mousedown="$ev('win-active', $this)"
    >
        <div class="cv-win-titbar" v-drag-move:xy="$this">
            <cv-desktop-applogo
                :logo="icon"
                size="mini"
                custom-class="cv-win-icon"
            ></cv-desktop-applogo>
            <span class="f-d3 f-m f-bold mg-r-m">{{ title }}</span>
            <span class="f-s mg-r-m">extra</span>
            <slot name="titbar-left-ctrl"></slot>
            <span class="flex-1"></span>
            <slot name="titbar-right-ctrl"></slot>
            <cv-button
                icon="md-sharp-remove"
                popout="dark"
            ></cv-button>
            <cv-button
                icon="md-sharp-crop-square"
                popout="dark"
            ></cv-button>
            <cv-button
                icon="md-sharp-close"
                type="danger"
                popout
            ></cv-button>
        </div>
    </div>
</template>

<script>
import mixinBase from 'mixins/base';

export default {
    mixins: [mixinBase],
    props: {
        //win 序号
        winidx: {
            type: Number,
            default: 0
        },

        //icon
        icon: {
            type: String,
            default: 'icon/md-sharp-desktop-windows'
        },
        //title
        title: {
            type: String,
            default: '新建窗口'
        },

        /**
         * 窗口状态
         */
        //此窗口是否获得焦点
        active: {
            type: Boolean,
            default: false,
        },
        //最小化
        minimize: {
            type: Boolean,
            default: false,
        },
        //最大化
        maxmize: {
            type: Boolean,
            default: false,
        },

        /**
         * ui
         */
        zIndex: {
            type: Number,
            default: 10
        },
        posX: {
            type: [Number, String],
            default: 0
        },
        posY: {
            type: [Number, String],
            default: 0
        },
        sizeW: {
            type: [Number, String],
            default: 0
        },
        sizeH: {
            type: [Number, String],
            default: 0
        },
        //是否允许拖拽移动
        dragMoveable: {
            type: Boolean,
            default: true
        },
    },
    data() {
        return {
            /**
             * ui
             */
        }
    },
    computed: {
        /**
         * desktop-win style
         */
        
    },
    watch: {
        posX(nv, ov) {this.setWinPos({x: this.posX});},
        posY(nv, ov) {this.setWinPos({y: this.posY});},
        sizeW(nv, ov) {this.setWinSize({w: this.sizeW});},
        sizeH(nv, ov) {this.setWinSize({h: this.sizeH});},
    },
    created() {},
    mounted() {
        this.$nextTick(()=>{
            this.$ev('win-ready', this);
            this.$wait(10).then(()=>{
                this.setWinPos({
                    x: this.posX,
                    y: this.posY
                });
                this.setWinSize({
                    w: this.sizeW,
                    h: this.sizeH
                });
            });
        });
    },
    methods: {
        /**
         * ui
         */
        //设置 pos
        async setWinPos(pos={}) {
            await this.elReady();
            let is = this.$is,
                fix = n => !isNaN(n*1) ? n+'px' : n;
            if (is.defined(pos.x)) this.$el.style.left = fix(pos.x);
            if (is.defined(pos.y)) this.$el.style.top = fix(pos.y);
        },
        //设置 size
        async setWinSize(size={}) {
            await this.elReady();
            let is = this.$is,
                fix = n => !isNaN(n*1) ? n+'px' : n;
            if (is.defined(size.w)) this.$el.style.width = fix(size.w);
            if (is.defined(size.h)) this.$el.style.height = fix(size.h);
        },
        //拖拽移动
        whenDragMove(target) {
            //console.log(pos);
        },
        //拖拽移动完成
        afterDragMove(target) {
            let x = target.offsetLeft,
                y = target.offsetTop;
            //通知 desktop 父组件 win 的新 pos
            this.$ev('win-pos-change', this, {x,y});
        },
        
    }
}
</script>

<style>

</style>