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

    <PRE@-bar-sty
        bgc="cyan-l3"
        disabled
    ></PRE@-bar-sty>
    
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
        //v-loading-mask
        blockLoading: true,

        //collapse
        collapseValue: ['foo'],

        //menu
        showMenuA: true,
        showMenuA4: false,
        showMenuB: false,
        showMenuC: false,
        showMenuD: false,

        testMenus: [
            {
                key: 'menu-foo', 
                icon: 'btn-tuangou', 
                label: '菜单 foo',
                sub: [
                    {
                        key: 'menu-foo-a',
                        label: '菜单 foo-a',
                        //icon: 'hearing',
                        cmd: () => console.log('菜单 foo-a is actived'),
                    },
                    {
                        key: 'menu-foo-b',
                        label: '菜单 foo-b',
                        cmd: () => this.testMenuCmd('foo-b'),
                    },
                    {
                        key: 'menu-foo-c',
                        label: '菜单 foo-c',
                        sub: [
                            {
                                key: 'menu-foo-c-a',
                                label: '菜单 foo-c-a'
                            },
                            {
                                key: 'menu-foo-c-b',
                                label: '菜单 foo-c-b',
                                params: {
                                    disabled: true
                                },
                            },
                            {
                                key: 'menu-foo-c-c',
                                label: '菜单 foo-c-c',
                                sub: [
                                    /*{
                                        key: 'menu-foo-c-c-a',
                                        label: '菜单 foo-c-c-a'
                                    },
                                    {
                                        key: 'menu-foo-c-c-b',
                                        label: '菜单 foo-c-c-b'
                                    },*/
                                ],
                                subLazyload: true,
                            },
                        ]
                    },
                    {
                        key: 'menu-foo-d',
                        label: '菜单 foo-d'
                    },
                ]
            },
            {
                key: 'menu-bar', 
                icon: 'outlined-flag', 
                label: '菜单 bar',
                sub: [
                    {
                        key: 'menu-bar-a',
                        label: '菜单 bar-a'
                    },
                    {
                        key: 'menu-bar-b',
                        label: '菜单 bar-b'
                    },
                ]
            },
            {
                key: 'menu-jaz', 
                icon: 'male', 
                label: '菜单 jaz',
                sub: [
                    {
                        key: 'menu-jaz-a',
                        label: '菜单 jaz-a'
                    },
                    {
                        key: 'menu-jaz-b',
                        label: '菜单 jaz-b'
                    },
                    {
                        key: 'menu-jaz-c',
                        label: '菜单 jaz-c',
                        sub: [
                            {
                                key: 'menu-jaz-c-a',
                                label: '菜单 jaz-c-a'
                            },
                            {
                                key: 'menu-jaz-c-b',
                                label: '菜单 jaz-c-b'
                            },
                            {
                                key: 'menu-jaz-c-c',
                                label: '菜单 jaz-c-c'
                            },
                        ]
                    },
                ]
            },
            {
                key: 'menu-tom', 
                icon: 'power', 
                label: '菜单 tom',
                sub: [
                    {
                        key: 'menu-tom-a',
                        label: '菜单 tom-a'
                    },
                    {
                        key: 'menu-tom-b',
                        label: '菜单 tom-b'
                    },
                ]
            },
        ],
        testMenuCompact: false,

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

        whenLayoutXColResized(...args) {
            console.log('layout-x col resized', ...args);
        },


        testMenuCmd(menuKey) {
            console.log(menuKey, 'active with cmd this.testMenuCmd');
        },
    }
});
</script>

<?php
//结束输出
$view->renderEnd();
?>