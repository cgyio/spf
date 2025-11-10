<?php
/**
 * 视图页面
 * 输出 BaseException 及其子类的 异常页面
 * 
 * 必须注入的参数：
 *      $exception  异常实例 instanceof BaseException
 */

namespace Spf\view;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;

//异常实例 info
$info = $exception->getInfo();
foreach ($info as $k => $v) {
    $$k = $v;
}

/**
 * 修改此 视图页面的 默认参数
 */
$view->runtimeSetInit([
    //默认 页面标题
    "title" => "Error $code",
    //默认 页面 CSS 样式文件 url
    "static" => [
        "/src/view/css/exception.css",
    ],
]);

//开始输出 html
$view->renderStart();
?>

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

<?php
//结束输出
$view->renderEnd();
?>