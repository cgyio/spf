<?php

if (!isset($pause_msg)) {
    $pause_msg = "网站暂停响应";
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Web Paused</title>
<style>
body {
    margin: 0; padding: 0; overflow: hidden;
    width: 100vw; height: 100vh;
    display: flex; align-items: center; justify-content: center;
    background-color: #f2f2f2;
}
#pause_main {
    min-width: 128px; height: 64px; padding: 0 16px; margin: 0;
    display: flex; align-items: center;
    box-sizing: border-box;
    border: #ededed solid 1px; border-radius: 8px;
    font-size: 14px; 
    font-family: monospace, 'PingFang SC', 'Microsoft Yahei', '微软雅黑', sans-serif;
    color: #bbb;
    background-color: #fff;
}
</style>
</head>
<body>
    <div id="pause_main"><?=$pause_msg?></div>
</body>
</html>