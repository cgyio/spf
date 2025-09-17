<template>
    <div 
        :class="'cv-desktop '+(customClass==''?'':customClass)"
        :style="deskSty"
    >
        <template v-if="shortcuts.length>0">
            <cv-desktop-shortcut
                v-for="(sci,scidx) of shortcuts"
                :key="'cv_desktop_short_'+scidx"
                v-bind="sci"
            ></cv-desktop-shortcut>
        </template>
        <slot></slot>
        <template v-if="wins.length>0">
            <cv-desktop-win
                v-for="(win,winidx) of wins"
                :key="'cv_desktop_win_'+winidx"
                v-bind="win.win"
                :winidx="win.idx"
                :active="win.active"
                :minimize="win.minimize"
                :maxmize="win.maxmize"
                :z-index="win.zIndex"
                :pos-x="win.pos.x"
                :pos-y="win.pos.y"
                :size-w="win.size.w"
                :size-h="win.size.h"
                :component="win.comp"
            ></cv-desktop-win>
        </template>
    </div>
</template>

<script>
import mixinBase from 'mixins/base';

export default {
    mixins: [mixinBase],
    props: {

        /**
         * ui
         */
        //wallpaper
        wallpaper: {
            type: String,
            default: ''
        },

        /**
         * shortcuts
         */
        shortcuts: {
            type: Array,
            default: ()=>[]
        },

    },
    data() {
        return {
            /**
             * win 窗口列表
             */
            wins: [
                /*{
                    
                }*/
            ],
            winInitOptions: {
                ins: null,      //win 组件实例
                idx: -1,        //win idx 在列表中的序号
                active: false,      //win 获得焦点标记
                minimize: false,    //win 最小化标记
                maxmize: false,     //win 最大化标记
                //win 样式参数
                zIndex: 10,
                pos: {
                    x: 0,
                    y: 0,
                },
                size: {
                    w: 0,
                    h: 0
                },
                //win 其他参数
                win: {},
                //win 内部功能组件 参数
                comp: {
                    name: '',
                    params: {},
                }
            },
            //默认的 win 其他参数
            winInitParams: {
                icon: 'md-sharp-desktop-windows',
                title: '新建窗口',

            },
        }
    },
    computed: {
        /**
         * desktop style obj {}
         */
        deskSty() {
            let is = this.$is,
                ext = this.$extend,
                csty = this.customStyle,
                sty = ext(
                    {},
                    this.deskBgSty
                );
            if (is.plainObject(csty)) {
                sty = ext(sty, csty);
            }
            sty = this.$cgy.toCssString(sty);
            if (is.string(csty)) {
                sty = `${sty} ${csty}`;
            }
            return sty;
        },
        deskBgSty() {
            let cssv = this.cssvar;
            return {
                backgroundColor: cssv.color.bg,
                backgroundImage: 'url(https://io.cgy.design/src/cgy/desktop-bg.jpg)',
                backgroundSize: '100% auto',
                backgroundRepeat: 'no-repeat',
                backgroundPosition: 'center center'
            };
        },

        //desktop style
        deskWidth() {
            let is = this.$is;
            if (!is.elm(this.$el)) return 0;

        },


        /**
         * win 窗口列表
         */
        //当前获得焦点的 win 窗口
        currentWin() {
            let is = this.$is,
                wins = this.wins,
                idx = -1;
            for (let win of wins) {
                if (win.active==true) {
                    idx = win.idx;
                }
            }
            if (idx<0) return null;
            return this.wins[idx];
        },
        //获取 wins 窗口列表的 z-index 数组
        zIndexs() {
            if (this.wins.length<=0) return [];
            return this.wins.map(i=>i.zIndex);
        },
        //获取 wins 窗口列表中 最大的 z-index
        maxZIndex() {
            let zidxs = this.zIndexs;
            if (zidxs.length<=0) return 10; //最小 10
            let max = 0;
            for (let zidx of zidxs) {
                if (zidx>max) {
                    max = zidx;
                }
            }
            return max;
        },


    },
    created() {
        //dev 自动创建一个窗口
        /*this.createWin({
            size: {w: 320, h:320},
            win: {
                icon: 'Apple',
                title:'窗口 01',
            }
        });
        this.$wait(200).then(()=>{
            this.createWin({
                size: {w:480, h:360},
                win: {
                    icon: 'pocket',
                    title:'窗口 02',
                }
            });
        });
        this.$wait(400).then(()=>{
            this.createWin({
                size: {w:540, h:480},
                win: {
                    icon: 'qq',
                    title:'窗口 03',
                }
            });
        });
        this.$wait(600).then(()=>{
            this.createWin({
                size: {w:640, h:540},
                win: {
                    icon: 'whatsapp',
                    title:'窗口 04',
                }
            });
        });
        this.$wait(800).then(()=>{
            this.createWin({
                size: {w:960, h:640},
                win: {
                    icon: 'youtube',
                    title:'窗口 05',
                }
            });
        });*/
        
    },
    mounted() {
        this.$nextTick(()=>{
            //this.$wait(500).then(()=>{
            //    this.$UI.awaitDanger('ceshi','await danger').then(()=>{
            //        console.log('await fullfill');
            //    });
            //});
        });
    },
    methods: {

        //this.$el ready


        /**
         * 窗口管理
         */
        /**
         * 创建窗口
         */
        async createWin(opt={}) {
            let is = this.$is,
                ext = this.$extend,
                wins = this.wins,
                widx = wins.length,
                win = ext({
                    win: ext({
                        //默认的 win 组件事件处理
                        customEventHandler: this.winCustomEventHandler()
                    }, this.winInitParams),
                }, this.winInitOptions, opt),
                //自动计算初始 win 样式参数
                sty = await this.getInitWinSty(win);
            //配置其他参数
            win = ext(win, {
                //在 wins 列表中的序号
                idx: widx,
            }, sty);
            //添加到 wins 列表
            wins.push(win);
            await this.$wait(10);
            //新建窗口 自动获得焦点
            await this.activeWin(widx);
            //完成
            return true;
        },
        //创建 win 时，计算初始显示参数
        async getInitWinSty(win={}) {
            await this.elReady();
            let is = this.$is,
                ext = this.$extend,
                el = this.$el,
                desk = {
                    w: el.offsetWidth,
                    h: el.offsetHeight
                },
                {pos,size} = win,
                {x,y} = pos,
                {w,h} = size;
            if (w<=0) w = desk.w * 0.5;
            if (h<=0) h = desk.h * 0.6;
            if (x<=0) x = (desk.w-w)/2;
            if (y<=0) y = (desk.h-h)/2;
            return {
                //设置 z-index 当前最大 +1
                zIndex: this.maxZIndex + 1,
                pos: {x,y},
                size: {w,h}
            }
        },
        //某个 win 获得焦点
        async activeWin(winidx=-1) {
            if (winidx<0) return false;
            let is = this.$is,
                wins = this.wins,
                cwin = this.currentWin;
            if (!is.defined(wins[winidx]) || (!is.null(cwin) && cwin.idx==winidx)) return false;
            //取消 已获得焦点的窗口
            for (let win of wins) {
                if (win.idx==winidx) continue;
                if (win.active==true) {
                    this.$set(this.wins[win.idx], 'active', false);
                }
            }
            await this.$wait(10);
            //设置焦点
            this.$set(this.wins[winidx], 'active', true);
            //设置 zIndex 为当前最大 +1
            this.$set(this.wins[winidx], 'zIndex', this.maxZIndex + 1);
            //整体刷新 zIndex 序列
            await this.resetZIndex();
            return true;
        },
        //计算 win 叠放次序 z-index 每当有 win 开启/关闭/获得焦点/失去焦点 时执行一次 防止 zIndex 数值过于庞大
        async resetZIndex() {
            let min = 10,   //z-index 最小值
                //构建一个数组，只包含 每个 win 的 idx 和 zIndex 参数
                zidxs = this.wins.map(i=>{
                    return {
                        idx: i.idx,
                        zIndex: i.zIndex
                    }
                });
            //按 zIndex 排序这个数组，从小到大
            zidxs.sort((a,b)=>{
                return a.zIndex - b.zIndex;
            });
            //从 10 开始 按顺序重设 zIndex 的值
            for (let i=0;i<zidxs.length;i++) {
                let widx = zidxs[i].idx,
                    zidx = min + i + 1;
                this.$set(this.wins[widx], 'zIndex', zidx);
            }
            await this.$wait(10);
            return true;
        },
        //设置某个 win 的 参数
        async setWin(winidx=-1, d={}) {
            let is = this.$is,
                ext = this.$extend;
            if (winidx<0 || !is.defined(this.wins[winidx])) return false;
            if (!is.plainObject(d) || is.empty(d)) return false;
            let owin = ext({}, this.wins[winidx]);
            owin = ext(owin, d);
            this.wins.splice(winidx, 1, owin);
            await this.$wait(10);
            return true;
        },
        //默认的 win 事件处理
        winCustomEventHandler() {
            return {
                //win 窗口初次创建
                'win-ready': win => {
                    //保存 win 组件实例
                    let widx = win.winidx;
                    this.$set(this.wins[widx], 'ins', win);
                },
                //点击 win 窗口使其获得焦点
                'win-active': win => {
                    let widx = win.winidx;
                    this.activeWin(widx);
                },
                //win 移动了
                'win-pos-change': (win, pos) => {
                    let widx = win.winidx;
                    this.$set(this.wins[widx], 'pos', Object.assign({}, pos));
                }
            }
        },
    }
}
</script>

<style>

</style>