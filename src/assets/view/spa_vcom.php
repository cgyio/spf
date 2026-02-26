<?php
/**
 * 视图页面 spa_vcom.php
 * 任意 vcom 组件库的 预览页面
 * 使用 spf/vcom/spf.vcom.json 基础组件库作为基础 spa 组件容器
 * 
 * 此视图页面：
 *      0   启用了 SPA 环境，并将基础组件库设为 spf/assets/vcom/spf.vcom.json !! 不要修改
 *      1   自动使用 spf-layout 组件作为 SPA 容器组件
 *      2   如果未指定 compound["spa"]["app"] 内容，则预览 spf 基础组件库
 *          如果指定了 compound["spa"]["app"] 内容，则预览指定的  组件库的内容组件（基础组件库|业务组件库  都可以预览）
 */

namespace Spf\view;

use Spf\exception\AppException;
use Spf\module\src\resource\Vcom;
use Spf\module\src\resource\Theme;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Url;

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
                //!! 不要修改
                "file" => "spf/assets/vcom/spf.vcom.json",
                //!! 按需修改
                "params" => [
                    //基础组件库的 组件加载模式，默认 mini 仅加载 required 组件
                    "mode" => "mini",
                    //可以手动指定 SPA 环境下的 组件名称前缀，对应的 SPF-Theme 样式类名前缀将同时被指定
                    "prefix" => "spf",
                ],
                //!! 按需修改
                "options" => [
                    //裁剪 启用的 服务
                    /*"useService" => [
                        "bus", "ui", 
                        //如果有业务组件库有自有的 服务需要启用，需要在此指定
                        "foo"
                    ],*/
                    //"foo" => 123,
                    //"bar" => 456,
                ],
            ],
            
            /**
             * 要预览的  基础组件库|业务组件库
             * !! 只能定义一个
             * !! 不定义则预览当前的 基础组件库  即：spf/vcom/spf.vcom.json 组件库
             */
            "app" => [
                /**
                 * !! 通过 外部指定 要预览的组件库信息
                 * 例如：在 控制器方法 viewFoo 中 return 
                 *  [ 
                 *      # 指定  视图方法@视图页面路径
                 *      "view" => "SpaVue2x@spf/assets/view/spa_vcom.php",
                 *      "params" => [
                 *          # 指定要预览的 组件库
                 *          "app" => "path/to/vcom[.vcom.json]",
                 * 
                 *          # 也可以完整定义 要预览的组件库的 参数
                 *          "compound" => [
                 *              "spa" => [
                 *                  "app" => [
                 *                      "foo" => [
                 *                          "file" => "path/to/vcom[.vcom.json]",
                 *                          # 额外参数
                 *                          "params" => [
                 *                              "mode" => "full",
                 *                              "prefix" => "foo",
                 *                              ...
                 *                          ]
                 *                      ]
                 *                  ]
                 *              ]
                 *          ]
                 *      ]
                 *  ]
                 */
                /*"pms" => [
                    "file" => "spf/assets/vcom/pms.vcom.json",
                    "params" => []
                ]*/
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

//开始输出 html 生成必要资源
$view->renderStart();



/**
 * 准备要预览的组件库实例
 * 保存在 $view->spaApp[] 中  !! 只预览第一个
 * 如果未指定 app 则预览 $view->spaBase
 */
$vcomToView = Is::nemarr($view->spaApp) ? $view->spaApp[0] : $view->spaBase;
//!! 必须是有效的 SPF-Vcom 实例
if (!$vcomToView instanceof Vcom) {
    //抛出响应异常
    throw new AppException("没有找到有效的待预览组件库实例", "response/fail");
}
//获取基础组件库所使用的 SPF-Theme 主题资源实例
$vcomTheme = $view->spaBase->theme;
//!! 必须是有效的 SPF-Theme 实例
if (!$vcomTheme instanceof Theme) {
    //抛出响应异常
    throw new AppException("没有找到有效的主题资源实例", "response/fail");
}

//准备组件库关联的 SPF-Theme 主题样式文件  用于开发时重建缓存
$vcomThemeCss = [];
foreach ($view->links as $link) {
    if (!isset($link["rel"]) || !Is::nemstr($link["rel"]) || $link["rel"]!=="stylesheet") continue;
    if (!isset($link["href"]) || !Is::nemstr($link["href"])) continue;
    //!! 仅处理 browser.min.css 主题相关的 样式文件
    if (strpos($link["href"], "browser.min.css")!==false) {
        //处理 样式文件的 url
        $href = Url::fixShortUrl($link["href"]);
        $hrefo = new Url($href);
        //增加 create=true
        $hrefo->query["create"] = "true";
        //重新生成 完整的 组件库 css 文件 url 带 create=true 用于重建样式缓存
        $chref = Url::mk("../browser.min.css", $hrefo);

        //处理 query
        if (!isset($hrefo->query["theme"])) {
            $hrefo->query["mode"] = "light";
        } else {
            $hrefo->query["mode"] = $hrefo->query["theme"];
            unset($hrefo->query["theme"]);
        }
        //重新生成 主题样式默认 css 文件 url 用于重建主题样式文件缓存
        $nhref = Url::mk("../../../../theme/".$vcomTheme->desc["name"]."/".$vcomTheme->resVersion()."/default.css", $hrefo);
        
        $vcomThemeCss["重建".$hrefo->query["mode"]."模式 - 主题样式"] = $nhref->full;
        $vcomThemeCss["重建".$hrefo->query["mode"]."模式 - 组件库样式"] = $chref->full;
    }
}
?>

<!-- 此时图页面的 模板内容 -->
<div id="PRE@_app" class="PRE@-layout-wrapper" v-cloak>
    <div class="flex-x">
<?php
    foreach ($vcomThemeCss as $ckey => $css) {
?>
        <a class="btn btn-no-a flex-x flex-x-center btn-normal btn-primary effect-normal stretch-normal tightness-normal hoverable" href="<?=$css?>" target="_blank">
            <label><?=$ckey?></label>
        </a>
<?php
    }
?>
    </div>
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