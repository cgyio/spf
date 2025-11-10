<?php
/**
 * 视图页面
 * 示例代码，展示一个 默认的 view 视图页面的 写法
 * 
 * 自动注入的参数：
 *      $view   当前 View 视图实例
 * 
 * 其他视图参数注入：
 *      View::page("视图页面路径", [
 * 
 *          # 覆盖默认视图参数
 *          "lang" => "zh-CN",
 *          "title" => "",
 *          "favicon" => [
 *              "href" => "/src/icon/spf/logo-light.svg",
 *              "type" => "image/svg+xml",
 *          ],
 *          "compound" => [
 *              "spa" => [
 *                  "enable" => true,
 *              ],
 *              ...
 *          ],
 *          ...
 * 
 *          # 注入其他参数
 *          "foo" => true,
 *          "bar" => [],
 *          ...
 * 
 *      ]);
 * 将注入参数：
 *      $foo    true
 *      $bar    []
 */

namespace Spf\view;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;

/**
 * !! 此 视图页面的 自有默认参数
 * !! 与 ViewConfig 配置类中的默认参数 存在不一致时，在此处定义
 * 通过 runtimeSetInit() 方法，重新执行 $view->initialize()
 */
$view->runtimeSetInit([
    //默认 页面标题
    "title" => "SPF-View Demo SPF 框架视图示例页面",
    //视图调用的复合资源
    "compound" => [
        //强制刷新资源缓存
        "create" => false,
        //SPA 环境
        "spa" => [
            //启用
            "enable" => true,
            //SPA 环境基础组件
            "base" => [
                "file" => "spf/assets/vcom/spf.vcom.json",
                "params" => [
                    "mode" => "mini",
                ],
            ],
            //业务组件库 可有多个
            "app" => [],
        ],
    ],
    //需要调用主题参数的 scss
    "merge" => [
        "spf/assets/view/css/cb.scss",
    ],
    //默认 页面 CSS 样式文件 url
    "static" => [
        //"/src/view/css/exception.css",
    ],
]);

//开始输出 html
$view->renderStart();
?>

<!-- 此视图页面的 自定义内容 -->
<h1 class="f-red-d3">red</h1>
<h1 class="f-red-d2">red</h1>
<h1 class="f-red-d1">red</h1>
<h1 class="f-red-m">red</h1>
<h1 class="f-red-l1">red</h1>
<h1 class="f-red-l2">red</h1>
<h1 class="f-red-l3">red</h1>


<?php
//结束输出
$view->renderEnd();
?>