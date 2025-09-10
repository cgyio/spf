<?php

if (!isset($code)) $code = 404;
if (!isset($info)) $info = "Not Found";


?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=$code?> Page</title>
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
.code-title {
    padding: 0; margin: 0 0 32px 0;
    font-size: 64px; color: #f00;
}
.code-info {
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
    <div class="code-title"><?=$code?></div>
    <div class="code-info"><?=$info?></div>
</body>
</html>