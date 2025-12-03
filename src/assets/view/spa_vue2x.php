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
<div id="PRE@_app" class="PRE@-layout-wrapper">
    <PRE@-icon icon="vant-sync" size="mini" type="info"></PRE@-icon>
    <PRE@-icon icon="vant-sync" size="small" type="warn"></PRE@-icon>
    <PRE@-icon icon="vant-sync" type="primary" spin></PRE@-icon>
    <PRE@-icon icon="vant-sync" size="medium" type="success"></PRE@-icon>
    <PRE@-icon icon="vant-sync" size="large" type="danger"></PRE@-icon>
    <br><br>
    <PRE@-icon-logo 
        icon="spf-pms-app-light"
        size="128px"
        square
    ></PRE@-icon-logo>
    <PRE@-icon-logo 
        icon="spf-qy-logo-light"
        size="128px"
        color="gray"
        custom-style="width:200px;"
    ></PRE@-icon-logo>
    <br><br>
    <br><br>
    <br><br>
    <div class="flex-x">
        <PRE@-button icon="vant-sync" label="刷新数据" size="mini" type="primary"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" size="small" type="danger"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" size="medium" color="cyan"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" size="large" color="black"></PRE@-button>
    </div><br><br>
    <div class="flex-x">
        <PRE@-button icon="vant-sync" label="刷新数据" type="primary" spin active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="danger" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="warn" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="success" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="cyan" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="orange" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="purple" active></PRE@-button>
    </div><br>
    <div class="flex-x">
        <PRE@-button icon="vant-sync" label="刷新数据" type="primary" spin></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="danger"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="warn"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="success"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="cyan"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="orange"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="purple"></PRE@-button>
    </div><br><br>
    <div class="flex-x">
        <PRE@-button icon="vant-sync" label="刷新数据" type="primary" effect="plain" spin></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="danger" effect="plain"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="warn" effect="plain"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="success" effect="plain"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="cyan" effect="plain"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="orange" effect="plain"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="purple" effect="plain"></PRE@-button>
    </div><br><br>
    <div class="flex-x">
        <PRE@-button icon="vant-sync" label="刷新数据" type="primary" effect="fill" spin active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="danger" effect="fill" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="warn" effect="fill" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="success" effect="fill" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="cyan" effect="fill" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="orange" effect="fill" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="purple" effect="fill" active></PRE@-button>
    </div><br>
    <div class="flex-x">
        <PRE@-button icon="vant-sync" label="刷新数据" type="primary" effect="fill" spin></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="danger" effect="fill"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="warn" effect="fill"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="success" effect="fill"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="cyan" effect="fill"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="orange" effect="fill"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="purple" effect="fill"></PRE@-button>
    </div><br>
    <div class="flex-x">
        <PRE@-button icon="vant-sync" label="刷新数据" effect="popout" spin></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="primary" effect="popout" spin></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="danger" effect="popout"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="warn" effect="popout"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="success" effect="popout"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="cyan" effect="popout"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="orange" effect="popout"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="purple" effect="popout"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="fc" effect="popout" spin></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="gray" effect="popout"></PRE@-button>
    </div><br><br>
    <div class="flex-x">
        <PRE@-button icon="vant-sync" label="刷新数据" type="primary" radius="normal"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="primary" radius="pill" stretch="full-line"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="primary" radius="sharp"></PRE@-button>
        ---
        <PRE@-button icon="vant-sync" label="刷新数据" type="danger" radius="pill" effect="normal" spin icon-class="f-white-m"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="danger" radius="normal" effect="fill"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" type="danger" radius="sharp" effect="plain" disabled></PRE@-button>
        ---
        <PRE@-button icon="vant-sync" label="刷新数据" color="orange" radius="normal" effect="fill" icon-right></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="orange" radius="pill" effect="fill" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="orange" radius="sharp" effect="fill" active disabled></PRE@-button>
        ---
        <PRE@-button icon="vant-sync" label="刷新数据" color="green" radius="pill" effect="popout"></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="green" radius="sharp" effect="popout" disabled></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="green" radius="normal" effect="popout" stretch="square" ></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="green" radius="sharp" effect="popout" stretch="square" active></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" color="green" radius="normal" effect="popout" stretch="square" disabled></PRE@-button>
        <PRE@-button icon="vant-sync" label="刷新数据" size="128px" color="green" radius="normal" effect="popout" stretch="square" active disabled spin></PRE@-button>
    </div><br><br>
    <div class="flex-x">
        <PRE@-button label="链接按钮" link></PRE@-button>
        <PRE@-button label="链接按钮" link active></PRE@-button>
        <PRE@-button label="链接按钮" link disabled></PRE@-button>
        <PRE@-button label="链接按钮" size="large" type="danger" link></PRE@-button>
        <PRE@-button label="链接按钮" type="danger" link active></PRE@-button>
        <PRE@-button label="链接按钮" type="danger" link active disabled></PRE@-button>
    </div>
    <div class="flex-x">
        <PRE@-button icon="vant-home" label="链接按钮" link></PRE@-button>
        <PRE@-button icon="vant-home" label="链接按钮" link active custom-class="mg-rl-xl"></PRE@-button>
        <PRE@-button icon="vant-home" label="链接按钮" link disabled></PRE@-button>
    </div><br><br>
    <div class="flex-x">
        <PRE@-button icon="vant-home" size="128px" label="链接按钮" link></PRE@-button>
    </div><br><br>
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
    //foo: '__URL_COMPONENT__',
});
</script>

<?php
//结束输出
$view->renderEnd();
?>