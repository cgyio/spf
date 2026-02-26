<?php
/**
 * 视图页面 spa_icon.php
 * 所有安装的 icon 图标库 预览页面
 * 使用 spf/vcom/spf.vcom.json 基础组件库作为基础 spa 组件容器
 * 
 * 此视图页面：
 *      0   启用了 SPA 环境，并将基础组件库设为 spf/assets/vcom/spf.vcom.json !! 不要修改
 *      1   自动使用 spf-layout 组件作为 SPA 容器组件
 *      2   基础组件库 spf.vcom.json 已包含 图标库：
 *              md-round|sharp|fill, spinner, spf, btn
 *          其他定义在框架内部的图标库，会自动加载
 *          !! 如果要预览某个
 */

namespace Spf\view;

use Spf\App;
use Spf\exception\AppException;
use Spf\module\src\Resource;
use Spf\module\src\resource\Icon;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Url;
use Spf\util\Path;
use Spf\util\Conv;

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

//在某个路径下查找 *.icon.json 文件，返回文件名 数组
function getIconsetNames($dirkey) {
    $dir = Path::find($dirkey, Path::FIND_DIR);
    if (!is_dir($dir)) return [];

    //是否 app 中安装的图标库
    $inapp = Str::begin($dirkey, "app/");
    $appk = App::$current::clsk();

    //查找路径中的 *.icon.json
    $ics = [];
    $sdh = opendir($dir);
    while (($sfn = readdir($sdh))!==false) {
        if ($sfn==="." || $sfn===".." || is_dir($dir.DS.$sfn)) continue;
        if (strpos($sfn, ".icon.json")===false) continue;
        //收集图表库名称
        $icn = explode(".", $sfn)[0];
        $ics[$icn] = [
            "app" => $inapp,
            "base" => false,
            "js" => ($inapp ? "/$appk" : "")."/src/icon/$icn/default.min.js",
            "json" => ($inapp ? "app/$appk" : "spf")."/assets/icon/$sfn",
        ];
    }
    closedir($sdh);
    return $ics;
}

//获取某个图表库中的所有图标名，返回图标名数组
function getIconsetIcons($iconres) {
    if (!$iconres instanceof Icon) return [];
    $glyphs = $iconres->glyphs ?? [];
    if (!Is::nemarr($glyphs)) return [];
    return array_keys($glyphs);
}

//开始输出 html 生成必要资源
$view->renderStart();

//首先获取 框架内部图标库
$icons = getIconsetNames("spf/assets/icon");
//基础组件库包含的 图标库
$baseIcons = array_map(function($ici) {
    return $ici->desc["iconset"];
}, $view->spaBase->icon);
//去除 baseIcons
foreach ($baseIcons as $bicon) {
    if (isset($icons[$bicon])) {
        //不需要加载 组件库包含的图标库的 js 
        $icons[$bicon]["base"] = true;
    }
}

//当前 App 下安装的 图标库
$appIcons = [];
$appk = App::$current::clsk();
//查找当前 app 下安装的 图标库
if ($appk!=="base_app") {
    $appIcons = getIconsetNames("app/$appk/assets/icon");
}
if (Is::nemarr($appIcons)) $icons = array_merge($icons, $appIcons);

//插入 图标库 js 文件，生成对应的雪碧图，同时缓存图标库实例
foreach ($icons as $icn => $ic) {
    if (!isset($ic["js"]) || $ic["js"]==="" || !isset($ic["base"]) || $ic["base"]!==false) continue;
    echo "<script src=\"".$ic["js"]."\"></script>";
}

//准备菜单栏数据
$menus = [];
$iconsetIcons = [];
$iconsetJs = [];
$activeIcon = "";
foreach ($icons as $icn => $ic) {
    if (!isset($ic["json"]) || $ic["json"]==="") continue;
    $icoi = Resource::create($ic["json"]);
    if ($icoi instanceof Icon) {
        //记录 图标库图标名称数组
        $iconsetIcons[$icn] = getIconsetIcons($icoi);
        //记录 图标库 js 文件地址  用于重建 js 缓存
        $iconsetJs[$icn] = $ic["js"] ?? "";
        $menus[] = [
            "icon" => $icn."-".($iconsetIcons[$icn][0] ?? "-empty-"),
            "label" => ($ic["app"]===true ? "$appk > " : "Spf > ").$icn,
            "key" => $icn,
            "params" => [
                "active" => !Is::nemstr($activeIcon)
            ]
        ];
        if (!Is::nemstr($activeIcon)) $activeIcon = $icn;
        //释放图标库实例
        unset($icoi);
    }
}

?>

<!-- 此时图页面的 模板内容 -->
<div id="PRE@_app" class="PRE@-layout-wrapper" v-cloak>
    <PRE@-layout
        v-model="iconMenus"
        with-menubar
        border="bd-m bd-po-t bdc-m"
        :mainbody-params="{
            innerBorder: true
        }"
        :menu-params="{
            shape: 'round'
        }"
        @menu-item-active="whenMenuItemActive"
    >
        <PRE@-block
            grow
            scroll="bold"
            with-header
            :header-params="{
                size: 'xl',
                icon: 'apps',
                iconParams: {
                    color: 'primary',
                },
                bdPo: 'b',
            }"
            inner-content
        >
            <template v-slot:header>
                <span class="fw-bold fc-black-m fs-medium">
                    {{'图标库：'+activeIconset}}
                </span>
                <PRE@-bar
                    stretch="auto"
                    shape="pill"
                    effect="normal"
                    tightness="loose"
                    size="mini"
                    color="yellow"
                    fs="small"
                    mg="xxl"
                    mg-po="l"
                    bdc="yellow-l2"
                >
                    <span class="">图标数：</span>
                    <span class="fc-black-m fw-bold">{{iconset[activeIconset].length}}</span>
                </PRE@-bar>
                <span class="flex-1"></span>
                <PRE@-bar
                    v-if="selectIcon!==''"
                    stretch="auto"
                    shape="pill"
                    effect="normal"
                    tightness="loose"
                    size="mini"
                    color="primary"
                    fs="small"
                    bdc="primary-l2"
                >
                    <span class="">选中：</span>
                    <span class="fs-m fc-black-m fw-bold fml-code">{{activeIconset+'-'+selectIcon}}</span>
                </PRE@-bar>
                <span class="flex-1"></span>
                <PRE@-icon icon="search" type="primary"></PRE@-icon>
                <el-input
                    v-model="sk"
                    size="small"
                    placeholder="筛选"
                    clearable
                    style="width: 256px;"
                ></el-input>
                <PRE@-button
                    v-if="iconsetJs[activeIconset] && iconsetJs[activeIconset]!==''"
                    icon="refresh"
                    label="重建缓存"
                    type="danger"
                    mg="xxl"
                    mg-po="l"
                    debounce
                    fullfilled
                    :fullfilled-when="reCreated"
                    @click="reCreateIconsetJs"
                ></PRE@-button>
            </template>
            <template v-slot="">
                <div 
                    v-if="currentIconset.length>0"
                    class="flex-x flex-y-start flex-x-start flex-no-shrink"
                    style="flex-wrap: wrap;"
                >
                    <div
                        v-for="(icon,idx) of currentIconset"
                        :key="'spf-iconset-'+activeIconset+'-'+icon"
                        :class="'po-ov flex-y flex-x-center flex-y-center flex-no-shrink'+(selectIcon===icon?' bgc-white shadow-m-a2 rd-m rd-po-all':' bd-m bdc-m bd-po-rb')"
                        :style="'min-width:128px; width:10%; height:160px; transition:all var(--ani-dura); cursor:pointer;'+(selectIcon===icon ? 'z-index:10; transform:scale(1.2);' : '')"
                        :title="'复制图标名 '+activeIconset+'-'+icon+' 到剪贴板'"
                        @mouseenter="selectIcon = icon"
                        @mouseleave="selectIcon = ''"
                        @click="copyIconName(activeIconset+'-'+icon)"
                    >
                        <svg 
                            :class="'__PRE__-icon'+(selectIcon===icon?' icon-primary-m':'')" 
                            style="font-size: 48px; margin: 32px 0; cursor: pointer;"
                            aria-hidden="true"
                        >
                            <use v-bind:xlink:href="'#'+activeIconset+'-'+icon"></use>
                        </svg>
                        <div
                            class="flex-x flex-x-center flex-no-shrink pd-m pd-po-x fs-xs fml-code"
                            style="min-height: 32px; cursor: pointer; text-align: center;"
                        >{{activeIconset+'-'+icon}}</div>
                    </div>
                </div>
            </template>
        </PRE@-block>
    </PRE@-layout>
</div>

<!-- 应用 SPA 环境插件，创建 根组件实例 js 代码块 -->
<script type="module">
<?php
$view->useSpaPlugin([
    //baseOptions
    //...
], true);
?>


//插入 Vue.rootApp() 代码，创建 根组件实例
Vue.rootApp({
    el: '#PRE@_app',
    //使用 baseRootMixin
    mixins: [baseRootMixin],
    props: {},
    data() {return {
        //图标库图标列表
        iconset: JSON.parse('<?=Conv::a2j($iconsetIcons)?>'),
        //图标库 js 地址，用于重建图标库缓存文件
        iconsetJs: JSON.parse('<?=Conv::a2j($iconsetJs)?>'),
        //图标库名称作为 menus
        iconMenus: JSON.parse('<?=Conv::a2j($menus)?>'),
        //当前显示的 图标库
        activeIconset: '<?=$activeIcon?>',
        //选中的 icon
        selectIcon: '',

        //检索关键字
        sk: '',

        //重建 图标库缓存 标记
        reCreating: false,
        //完成重建 标记
        reCreated: false,
    }},
    computed: {
        //当前显示的图标库
        currentIconset() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                isa = a => is.array(a) && a.length>0,
                ics = this.iconset,
                aic = this.activeIconset;
            if (!iss(aic) || !is.defined(ics[aic]) || !isa(ics[aic])) return [];
            let cis = ics[aic],
                sk = this.sk;
            if (!iss(sk)) return cis;
            return cis.filter(i=>iss(i) && i.includes(sk));
        },
    },
    methods: {
        //点击选中不同的 图标库
        whenMenuItemActive(keyChain=[]) {
            if (keyChain.length<=0) return;
            let ac = keyChain[0];
            if (ac===this.activeIconset) return;
            //重新指定当前显示的 图标库
            this.activeIconset = ac;
        },
        //点击某个图标库的 重建按钮
        async reCreateIconsetJs() {
            let is = this.$is,
                iss = s => is.string(s) && s!=='',
                ac = this.activeIconset,
                js = this.iconsetJs[ac];
            if (!iss(js)) return;

            if (this.reCreating) return;
            this.recreating = true;
            this.reCreated = false;

            //访问 带 create=true 参数的 图标库 js
            let res = await window.fetch(`${js}?create=true`);
            console.log(res, res.text());
            if (!res.ok) {
                //请求错误
                this.recreating = false;
                throw new Error(`图标库 ${ac} 重建 js 缓存失败！response.status = ${res.status}`);
                return;
            }
            //重建成功
            this.recreating = false;
            this.reCreated = true;
            //等待 0.5 秒后 刷新当前页
            await this.$wait(500);
            this.reCreated = false;
            //window.history.go(0);
        },
        //赋值图标名称到剪贴板
        async copyIconName(icn) {
            await navigator.clipboard.writeText(icn);
            alert('图标名复制成功！');
        },
    }
});
</script>

<?php
//结束输出
$view->renderEnd();
?>