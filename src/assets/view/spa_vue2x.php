<?php
/**
 * 视图页面 spa_vue2x.php
 * 使用 Vue2.* 基础组件库的 视图页面
 * 
 * 此视图页面：
 *      0   启用了 SPA 环境，并将基础组件库设为 spf/assets/vcom/spf.vcom.json !! 可手动修改
 *      1   自动使用 sv-layout 组件作为 SPA 容器组件
 *      2   自动扫描 使用的所有业务组件库中的 sv-page-* 组件，作为 SPA 页面，注册到 Vue 路由表
 */

namespace Spf\view;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;

/**
 * !! 此 视图页面的 自有默认参数
 * !! 与 ViewConfig 配置类中的默认参数 存在不一致时，在此处定义
 * !! 可通过 View::SpaVue2x([...apps], [...params]) 方法调用中的 params 参数覆盖
 * 通过 runtimeSetInit() 方法，重新执行 $view->initialize()
 */
$view->runtimeSetInit([
    //默认 页面标题
    "title" => "Vue-SPA",

    //视图调用的复合资源
    "compound" => [
        //强制刷新资源缓存，可被 url 参数覆盖
        "create" => false,
        //SPA 环境
        "spa" => [
            //启用
            "enable" => true,
            //SPA 环境基础组件
            "base" => [
                "file" => "spf/assets/vcom/spf.vcom.json",
                "params" => [
                    //基础组件库的 组件加载模式，默认 mini 仅加载 required 组件
                    "mode" => "mini",
                    //可以手动指定 SPA 环境下的 组件名称前缀，对应的 SPF-Theme 样式类名前缀将同时被指定
                    "prefix" => "spf",
                ],
                "options" => [
                    //裁剪 启用的 服务
                    /*"useService" => [
                        "bus", "ui", 
                        //如果有业务组件库有自有的 服务需要启用，需要在此指定
                        "foo"
                    ],*/
                    "foo" => 123,
                    "bar" => 456,
                ],
            ],
            //业务组件库 可有多个
            "app" => [
                "pms" => [
                    "file" => "spf/assets/vcom/pms.vcom.json",
                    "params" => []
                ]
            ],
        ],
    ],

    //需要调用主题参数的 scss
    "merge" => [
        //"spf/assets/view/css/cb.scss",
    ],

    //默认 页面 CSS 样式文件 url
    "static" => [
        //"/src/view/css/exception.css",
    ],
]);

//组件名|主题样式类名 前缀，需要插入 html 中
//$pre = $view->spaBase->desc["prefix"];

//forDev
//var_dump($params);

//开始输出 html
$view->renderStart();
?>

<!-- 此时图页面的 模板内容 -->
<div id="PRE@_app" class="PRE@-layout-wrapper" v-cloak>

    <PRE@-layout>
        <br><br>
        <PRE@-button
            icon="desktop-mac"
            label="Mask On"
            @click="testUiMaskOn"
        ></PRE@-button>
        <PRE@-button
            icon="desktop-mac"
            label="Open Win"
            @click="openDefaultWin"
        ></PRE@-button>
        <!--<PRE@-win
            win-key="test-win"
            icon-shape="fill"
            minimizable
            maximizable
            closeable
            hoverable
            tabbar
            :tab-list="$ui.testTabList"
            :tab-active="$ui.testTabActive"
            custom-style="z-index: 10;"
            @tab-active="tabKey => {$ui.testTabActive = tabKey;}"
        >
            <template v-slot:tabwin-index="{tab, win}">
                <div class="win-row">
                    <span class="fw-bold fc-d3">{{tab.label}}</span>
                    <span class="flex-1"></span>
                    <span class="fs-s fc-l2">行尾信息</span>
                </div>
            </template>
            <template v-slot:tabwin-foo="{tab, win}">
                <div class="win-row">
                    <span>{{tab.label}}</span>
                </div>
            </template>
            <template v-slot:tabwin-bar="{tab, win}">
                <div class="win-row">
                    <span>{{tab.label}}</span>
                </div>
            </template>
            <template v-slot:tabwin-jaz="{tab, win}">
                <div class="win-row">
                    <span>{{tab.label}}</span>
                </div>
            </template>
        </PRE@-win>
        <br><br>

        <PRE@-win
            tightness="loose"
            closeable
            shadow="bold"
            custom-style="z-index: 20;"
        >
            <template v-slot:titctrl>
                <PRE@-button
                    icon="search"
                    label="搜索"
                    type="primary"
                ></PRE@-button>
                <PRE@-button
                    icon="storm"
                    icon-shape="sharp"
                    type="danger"
                    stretch="square"
                ></PRE@-button>
            </template>
        </PRE@-win>-->
        <br><br>

        <div class="flex-y mg-l mg-po-l" style="width: 640px; height: 480px;">
            <PRE@-win
                win-type="inside"
                icon=""
                sharp
                hoverable
                :tab-list="winTabList"
                :tab-active="winTabActive"
                :win-confirmed="winConfirmed"
                @tab-active="whenTabActive"
                @confirm="whenWinConfirm"
            >
                <template v-slot:tab-foo="{win, tab}">
                    <div class="win-row">{{tab.key + ': ' + tab.label}}</div>
                </template>
            </PRE@-win>
        </div>

        <br><br><br><br><br><br><br><br><br><br>

        <div class="flex-x">
            <PRE@-button icon="shutter-speed" label="拍摄" size="mini" type="primary"></PRE@-button>
            <PRE@-button icon="shutter-speed" label="拍摄" size="small" type="danger"></PRE@-button>
            <PRE@-button icon="shutter-speed" label="拍摄"></PRE@-button>
            <PRE@-button icon="shutter-speed" icon-shape="fill" label="拍摄" size="medium" color="cyan"></PRE@-button>
            <PRE@-button icon="shutter-speed" icon-shape="sharp" label="拍摄" size="large" color="orange"></PRE@-button>
            <PRE@-button icon="wallet" label="我的钱包" size="mini" type="primary" effect="fill"></PRE@-button>
            <PRE@-button icon="wallet" label="我的钱包" size="small" type="danger" effect="fill"></PRE@-button>
            <PRE@-button icon="wallet" icon-shape="fill" label="我的钱包" effect="fill"></PRE@-button>
            <PRE@-button icon="wallet" icon-shape="sharp" label="我的钱包" size="medium" color="cyan" effect="fill"></PRE@-button>
            <PRE@-button icon="drafts" label="邮件" size="large" color="orange" effect="fill"></PRE@-button>
            <PRE@-button icon="drafts" icon-shape="sharp" label="邮件" size="mini" type="primary" effect="popout"></PRE@-button>
            <PRE@-button icon="bluetooth-audio" label="蓝牙连接" size="small" type="danger" effect="popout"></PRE@-button>
            <PRE@-button icon="bluetooth-audio" icon-shape="sharp" label="蓝牙连接" effect="popout"></PRE@-button>
            <PRE@-button icon="wifi" label="无线网络" size="medium" color="cyan" effect="popout"></PRE@-button>
            <PRE@-button icon="wifi" icon-shape="sharp" label="无线网络" size="large" color="orange" effect="popout"></PRE@-button>
        </div><br><br>

        <div class="flex-x">
            <PRE@-button icon="drafts" label="邮件" size="large" color="orange" effect="fill"></PRE@-button>
            <PRE@-button icon="drafts" label="邮件" size="large" color="orange" effect="fill" stretch="square"></PRE@-button>
        </div><br><br>

        <div class="flex-x">
            <PRE@-button icon="sync" label="刷新数据" type="primary" spin></PRE@-button>
            <PRE@-button icon="sync" label="刷新数据" type="primary" spin="self"></PRE@-button>
            <PRE@-button icon="wifi" label="连接网络" type="primary" spin="wifi" disabled></PRE@-button>
            <PRE@-button icon="delivery-dining" label="配送" type="danger"></PRE@-button>
            <PRE@-button icon="delivery-dining" icon-shape="fill" label="配送" type="warn"></PRE@-button>
            <PRE@-button icon="delivery-dining" icon-shape="sharp" label="配送" type="success"></PRE@-button>
            <PRE@-button icon="snowboarding" label="滑雪中" color="cyan"></PRE@-button>
            <PRE@-button icon="snowboarding" icon-shape="sharp" label="滑雪中" color="orange"></PRE@-button>
            <PRE@-button icon="snowboarding" icon-shape="fill" label="滑雪中" color="purple"></PRE@-button>
        </div><br>

        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        <br><br><br><br><br><br><br><br><br><br>
        
    </PRE@-layout>
    <!--
    <div class="flex-x">
        <PRE@-button-group size="normal" effect="plain">
            <PRE@-button icon="vant-home" label="主页"></PRE@-button>
            <PRE@-button icon="vant-close" label="退出"></PRE@-button>
            <PRE@-button icon="vant-check" label="确定" active></PRE@-button>
        </PRE@-button-group>
        <PRE@-button-group size="normal" radius="pill" effect="plain" type="warn">
            <PRE@-button icon="vant-home" label="主页"></PRE@-button>
            <PRE@-button icon="vant-close" label="退出" active></PRE@-button>
            <PRE@-button icon="vant-check" label="确定"></PRE@-button>
        </PRE@-button-group>
    </div><br><br>
    <div class="flex-x">
        <PRE@-button-group size="normal" radius="pill" effect="plain" full-line>
            <PRE@-button icon="vant-home" label="主页" type="primary"></PRE@-button>
            <PRE@-button icon="vant-close" label="退出" type="danger"></PRE@-button>
            <PRE@-button icon="vant-check" label="确定" type="success"></PRE@-button>
        </PRE@-button-group>
    </div><br><br>

    <br><br><br><br>

    <div class="__PRE__-scrollbar bd-m bd-xy" style="width: 320px; height: 240px;">
        foobar<br><br><br><br><br>
        foobar<br><br><br><br><br>
        foobar<br><br><br><br><br>
        <div class="bg-red-l3" style="width:100%;height:100px;"></div>
        foobar<br><br><br><br><br>
        foobar<br><br><br><br><br>
        foobar<br><br><br><br><br>
    </div>
    
    <br><br><br><br>

    <PRE@-theme-color-row color="red"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="orange"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="yellow"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="green"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="cyan"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="blue"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="purple"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="gray"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="fc"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="bgc"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="bdc"></PRE@-theme-color-row>
    <PRE@-theme-color-row color="bz"></PRE@-theme-color-row>
-->
</div>

<!-- 应用 SPA 环境插件，创建 根组件实例 js 代码块 -->
<script type="module">
<?php
$view->useSpaPlugin([
    //baseOptions
    //...
], true);
?>


//forDev
//Vue.initServicesInSequence().then(rst => Vue.service.ui.$log('service init returns', rst));


//插入 Vue.rootApp() 代码，创建 根组件实例
Vue.rootApp({
    el: '#PRE@_app',
    //使用 baseRootMixin
    mixins: [baseRootMixin],
    props: {},
    data() {return {
        winTabList: [
            {key: 'foo', label: 'foo',},
            {key: 'layout', label: 'layout', component: 'pms-layout', compParams: {}}
        ],
        winTabActive: 'layout',
        winConfirmed: false,
    }},
    methods: {
        testUiMaskOn() {
            this.$ui.maskOn(null, {
                //blur: true,
                alpha: 'light',
                type: 'danger',
                loading: true,
                on: {
                    'mask-click': () => {
                        console.log('mask-click callback');
                    }
                },
            });
        },
        openDefaultWin() {
            this.$win.openSingleCompWin('pms-layout', {

            });
        },

        whenTabActive(key) {
            console.log('root tab-active:', key);
            this.winTabActive = key;
        },

        async whenWinConfirm(win) {
            console.log(win);
            win.winLoading(true);
            await this.$wait(3000);
            win.winLoading(false);
            this.winConfirmed = true;
        },
    }
});
</script>

<?php
//结束输出
$view->renderEnd();
?>