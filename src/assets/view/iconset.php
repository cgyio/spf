<?php
/**
 * 视图页面
 * 输出 SPF-Theme 主题系统的 图标库 列表|查找|选择 视图页面
 * 
 * 必须注入的参数：
 *      $icon       图标库资源实例  instanceof  \Spf\module\src\resource\Icon
 */

namespace Spf\view;

use Spf\util\Is;

//图标库元数据
$desc = $icon->desc;
//图标库名称 foo-bar 形式
$iset = $desc["iconset"] ?? "";
//是否多色
$mcolor = $desc["multicolor"] ?? false;

//图表库中所有 图标的 name 
$glyphs = $icon->glyphs;
if (!Is::nemarr($glyphs)) $glyphs = [];
$icons = array_keys($glyphs);

/**
 * 修改此 视图页面的 默认参数
 */
$view->runtimeSetInit([
    //默认 页面标题
    "title" => "SPF-Theme 主题图标库 - ".$iset,
    //默认 页面 CSS 样式文件 url
    "css" => [
        "/src/view/css/iconset.css",
    ],
]);


//开始输出 html
$view->renderStart('<body data-desc="可以指定特别的body标签">');
?>

<?php
for ($i=0;$i<count($icons);$i++) {
    $ico = $icons[$i];
?>
<div class="spf-icon-box">
    <svg class="spf-icon spf-icon-inner" aria-hidden="true">
        <use xlink:href="#<?=$iset."-".$ico?>"></use>
    </svg>
    <span class="spf-icon-tit"><?=$iset."-".$ico?></span>
</div>
<?php
}
?>
<script type="module">
//自动切换 favicon
const $fav = () => document.querySelector("#favicon");
const isDarkMode = () => window.matchMedia('(prefers-color-scheme: dark)').matches;
const changeFavicon = () => {
    let fav = $fav(),
        icp = "/src/icon/spf/logo";
    if (isDarkMode()) {
        fav.href = `${icp}-dark.svg`;
    } else {
        fav.href = `${icp}-color.svg`;
    }
}
//载入时执行一次
changeFavicon();
//监听
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', changeFavicon);
</script>

<?php
$view->renderEnd();
?>