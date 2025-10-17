/**
 * CGY-VUE ui 基础组件
 * windows 窗口系统
 */

export default {
    props: {

    },
    data() {
        return {
            //已创建的窗口 列表
            wins: [],

            //默认的窗口创建参数
            winInitOptions: {
                idx: -1,        //在 wins 数组中的 idx
                win: null,      //win 窗口组件实例
                focused: false, //win 窗口获得焦点 标记

                //显示参数
                style: {
                    //窗口 z-index
                    zIndex: 0,
                    //pos 位置
                    left: 0,
                    top: 0,
                    //size 尺寸
                    width: 0,
                    height: 0
                },

                //其他创建参数
                extra: {
                    icon: 'md-sharp-desktop-windows',
                    title: '新建窗口',
                },
            },

            //窗口系统 所有 window 窗口组件的 z-index 从 10 开始计数
            minWinZIndex: 10,
        }
    },
    computed: {
        //获取当前获得焦点的 win
        focusedWin() {
            if (this.wins.length<=0) return {};
            for (let win of this.wins) {
                if (win.focused==true) return this.wins[win.idx];
            }
            return {};
        },
    },
    methods: {

    }
}