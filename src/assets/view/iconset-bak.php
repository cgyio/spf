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
$meta = $icon->meta();
//图标库名称 foo-bar 形式
$iset = $meta["iconset"] ?? "";
//是否多色
$mcolor = $meta["multicolor"] ?? false;

//图表库中所有 图标的 name 
$glyphs = $icon->glyphs();
if (!Is::nemarr($glyphs)) $glyphs = [];
$icons = array_keys($glyphs);


?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SPF-Theme 主题图标库 <?=$iset?></title>

<!-- 根据浏览器的 深色模式 自动加载对应的 主题 css -->
<link 
    rel="stylesheet" 
    href="/src/default.theme?mode=light" 
    media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)" 
/>
<link 
    rel="stylesheet" 
    href="/src/default.theme?mode=dark" 
    media="(prefers-color-scheme: dark)" 
/>

<!-- favicon -->
<link rel="icon" id="favicon" href="/src/icon/spf/logo-light.svg" type="image/svg+xml">

<!-- 引用图标库 css 样式 -->
<link rel="stylesheet" href="/src/icon/<?=$iset?>.css" />
<!-- 引用图标库的 svg 雪碧图 -->
<script src="/src/icon/<?=$iset?>.min.js"></script>

<style>
body {
    padding: var(--size-pd-xxxl); margin: 0;
    display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between;
    background-color: var(--color-bgc-m);
}

.spf-icon-box {
    position: relative; width: 96px; height: 128px; padding: var(--size-pd-m); margin: 0 0 var(--size-mg-xxl) 0; overflow: hidden;
    box-sizing: border-box; border-radius: var(--size-rd-m);
    display: flex; flex-direction: column; align-items: center; justify-content: flex-start;
    color: var(--color-fc-l1);
    cursor: pointer;
    transition: all var(--ani-dura);
}
.spf-icon-box > .spf-icon-inner {
    width: 100%; height: auto;
}
.spf-icon-box > .spf-icon-tit {
    width: 100%; min-height: var(--size-bar-s);
    display: flex; align-items: center; justify-content: center;
    font-size: var(--size-fs-m); 
}
.spf-icon-box:hover {
    background-color: var(--color-white-m);
    color: var(--color-fc-m);
}

</style>
</head>
<body>
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

</body>
</html>