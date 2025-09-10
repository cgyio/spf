<?php



?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Error <?=$code?></title>
<style>
body {
    margin: 0; padding: 0; overflow: hidden;
    width: 100vw; height: 100vh;
    display: flex; align-items: center; justify-content: center;
    background-color: #f2f2f2;
}
#error_main {
    min-width: 640px; min-height: 128px; padding: 48px 0; margin: 0 0 25vh 0;
    display: flex; flex-direction: column;
    box-sizing: border-box;
    border: #ededed solid 3px; border-radius: 32px;
    font-size: 14px; 
    font-family: monospace, 'PingFang SC', 'Microsoft Yahei', '微软雅黑', sans-serif;
    color: #888;
    background-color: #fff;
}
.error-row {
    width: 100%; height: 28px; padding: 0 48px; margin: 0;
    display: flex; align-items: center;
}
.error-row-sub {
    height: 24px;
    font-size: 12px;
}
.error-row-title {
    min-height: 28px;
    font-size: 22px; font-weight: bold; color: #ff0000;
}
.error-row-msg {
    min-height: 28px;
    font-size: 16px; font-weight: bold; color: #333;
}
.error-row-gap-bottom {margin-bottom: 16px;}
.error-row-gap-top {margin-top: 16px;}
.strong {
    min-width: 64px; margin-right: 16px;
    font-weight: bold; color: #666; 
}

@media (prefers-color-scheme: dark) {
    body {
        background-color: #101010;
    }
    #error_main {
        background-color: #000;
        border-color: #1b1b1b;
    }
    .error-row-msg {color: #ccc;}
    .strong {color: #999;}
}
</style>
</head>
<body>
    <div id="error_main">
        <div class="error-row error-row-title error-row-gap-bottom">
            Error <?=$code?> : <?=$title?>
        </div>
        <div class="error-row error-row-msg <?php if (isset($file)) echo "error-row-gap-bottom"; ?>"><?=$message?></div>
        <?php
            if (isset($file)) {
        ?>
        <div class="error-row">
            <span class="strong">文件</span>
            <span><?=$file?></span>
        </div>
        <?php
            }
            if (isset($file)) {
        ?>
        <div class="error-row">
            <span class="strong">行号</span>
            <span><?=$line?></span>
        </div>
        <?php
            }
            if (isset($trace) && is_array($trace) && !empty($trace)) {
        ?>
        <div class="error-row error-row-gap-top"><span class="strong">调用</span></div>
        <?php
                $i = 0;
                foreach ($trace as $tinfo) {
                    if (is_array($tinfo) && isset($tinfo["function"])) {
                        $tinfo = "#".$i." ".$tinfo["class"].$tinfo["type"].$tinfo["function"]."()";
                    } else if (!is_string($tinfo)) {
                        $tinfo = "#".$i;
                    }
                    $i++;
        ?>
        <div class="error-row error-row-sub">
            <span class="strong"></span>
            <span><?=$tinfo?></span>
        </div>
        <?php
                }
            }
        ?>
    </div>
</body>
</html>