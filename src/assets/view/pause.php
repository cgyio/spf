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
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    font-size: 14px; 
    font-family: monospace, 'PingFang SC', 'Microsoft Yahei', '微软雅黑', sans-serif;
    color: #bbb;
    background-color: #f2f2f2;
}
.pause-title {
    padding: 0; margin: 0 0 24px 0;
    font-size: 48px; color: #f00;
}
.pause-info {
    padding: 0; margin: 0 0 35vh 0;
    font-size: 24px; color: #888;
}

@media (prefers-color-scheme: dark) {
    body {
        background-color: #101010;
    }
}
</style>
</head>
<body>
    <div class="pause-title">Web Paused!</div>
    <div class="pause-info"><?=$pause_msg?></div>
</body>
</html>