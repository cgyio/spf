<?php
/**
 * 框架模块类
 * Src 资源处理模块
 */

namespace Spf\module;

use Spf\App;
use Spf\Response;
use Spf\Module;
use Spf\module\src\Resource;
use Spf\module\src\Fs;
use Spf\module\src\resource\Compound;
use Spf\module\src\SrcException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Url;
use Spf\util\Cls;
use Spf\util\Conv;
use Spf\util\Path;
use Spf\util\Color;
use Spf\util\Dsl;

class Src extends Module 
{
    /**
     * 单例模式
     * !! 覆盖父类，具体模块子类必须覆盖
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;

    /**
     * 模块的元数据
     * !! 实际模块类必须覆盖
     */
    //模块的说明信息
    public $intr = "资源处理模块";
    //模块的名称 类名 FooBar 形式
    public $name = "Src";



    /**
     * 资源处理模块启用后，将在实例化后，立即执行此 初始化操作
     * !! 覆盖父类
     * @return Bool
     */
    protected function initModule()
    {

        return true;
    }



    /**
     * default
     * @desc 资源输出
     * @export src
     * @auth false
     * @pause false 资源输出不受WEB_PAUSE影响
     * @param Array $args url 参数
     * @return Src 输出
     */
    public function default(...$args)
    {
        if (!Is::nemarr($args)) {
            Response::insSetCode(404);
            return null;
        }

        //首先尝试 使用 Compound 复合资源代理响应方法
        if (count($args)>1) {
            //指向的 复合资源类 ext
            $ext = $args[0];
            if (static::hasExt($ext) !== false) {
                //存在的 复合资源 ext
                return Compound::responseProxyer(...$args);
            }
        }

        //根据 URI 参数，解析并获取 资源
        $resource = Resource::create($args);
        
        //返回得到的资源，将在 Response::export() 方法中自动调用资源输出
        return $resource;
    }

    /**
     * api
     * @desc 文件系统操作
     * @auth true
     * @role all
     * 
     * @param Array $args url 参数
     * @return Mixed
     */
    public function fsApi(...$args)
    {
        //调用 Fs::response 方法 代理此请求
        return Fs::response(...$args);
    }



    /**
     * 针对 特定类型资源的 响应方法
     */



    /**
     * 工具方法
     */

    /**
     * 检查某个路径 是否真实存在的文件路径，
     * 如果是，则直接实例化为文件资源实例，并输出
     * 否则，返回 false
     * @param String $path 资源路径
     * @return Resource|false
     */
    protected function localFileExistsed($path)
    {
        $lf = Resource::findLocal($path);
        if (!file_exists($lf)) return false;

        //如果此文件真实存在，直接创建资源实例 并返回
        $res = Resource::create($lf, [
            //min
            "min" => strpos($path, ".min.") !== false,
        ]);
        if (!$res instanceof Resource) return false;
        //将 Response 输出类型 改为 src
        Response::insSetType("src");
        //输出资源
        return $res;
    }









    

    /**
     * forDev
     * api
     * @desc 资源模块测试方法
     */
    public function srcTestApi()
    {

        /*$res = [
            "params" => [
                "foo" => false,
                "bar" => 456,
                "jaz" => "slot",
                "merge" => ["str", "str2"],
                "testUrl" => "",
            ],
            "jaz" => true,
        ];
        $res = (object)$res;

        $opts = "?foo=false&bar<=123&SCOPE.jaz!=yes&(nemarr(merge)|nemarr(env.dir))&in_array('str', merge)&Url::isUrl(testUrl)|jaz=slot";

        $dsl = new Dsl($res, "params");
        $rtn = $dsl->exec($opts);

        exit;*/

        $jsf = Path::find("spf/assets/test.js");
        $jso = Resource::create($jsf, [
            "import" => "keep"
        ]);

        //var_dump($jso);
        Response::insSetType("src");
        return $jso;

        exit;


        $jso->merge(
            //[
            //    "esm" => true,
            //],
            
            "spf/assets/lib/cgy/2.2.1/modules/csstools.js",
            "spf/assets/lib/cgy/2.2.1/modules/request.js",
        );
        Response::insSetType("src");
        return $jso;



        $js = 'export const name = "Alice";export let name1 = "Alice";export name2 = "Alice";export var name3 = "Alice";export function greet() {return "Hello";}export class Person {constructor(name) {this.name = name;}}const gender = "female";function sayHi() {}export {gender,sayHi};export {gender as userGender,sayHi as greetUser};export foo;export default function() {return "This is a default export";}function myFunction() {}export default myFunction;export default class MyClass {}export default {gender,sayHi}export default {name: "Bob",age: 25};export default {mixin:[], props:{}, data(){return {}, methods:{},};export default {gender as userGender,sayHi as greetUser}';

        $mts = Esmer::parseExportVars($js);
        var_dump($mts);
        exit;


        //合并 js 文件
        $jsfs = [
            //"spf/assets/lib/vuecomp/spf/1.0.0/mixins/base.js",
            "spf/assets/lib/vuecomp/spf/1.0.0/mixins/db-base.js",
            "spf/assets/lib/vuecomp/spf/1.0.0/mixins/db-record.js",
            "spf/assets/lib/vuecomp/spf/1.0.0/mixins/el-base.js",
        ];

        $res = Resource::create("spf/assets/lib/vuecomp/spf/1.0.0/mixins/base.js");
        $cnt = $res->useFile(...$jsfs);
        var_dump($cnt);
        $jsfs = array_map(function($jsf) {
            return Path::find($jsf);
        }, $jsfs);
        $merger = new Merger("js");
        $merger->add(...$jsfs);
        var_dump($merger->imports);
        var_dump($merger->importRows);
        var_dump($merger->rows);
        var_dump($merger->mergedRows());

        
        
        exit;


        $path = [
            "/data/vendor/cgyio/spf/src/assets/lib/vue.lib",
            "/data/vendor/cgyio/resper/src/../../spf/src/assets/.foo.css",
            "/data/ms/assets/theme/spf_ms.theme",
            "/data/ms/library/foo.php",
            "/data/ms/library/db/config/dbn.json",
            "/data/ms/app/goods/assets/foo/bar.js",
            "/data/ms/app/goods/assets/uploads/foo/bar.jpg",
            "/data/ms/app/goods/library/db/config/dbn.json",
            "/data/ms/app/goods/library/db/dbn.db",
            "/data/ms/app/goods/library/foo.php",
        ];
        /*$rela = [];
        foreach ($path as $pi) {
            $rela[] = Path::rela($pi);
        }*/
        $urls = [];
        foreach ($path as $pi) {
            $urls[] = Url::src($pi);
        }

        var_dump($path);
        //var_dump($rela);exit;
        var_dump($urls);exit;


        $clrs = [
            "#fa0",
            "#fa07",
            "#ffaa00",
            "#ffaa0077",

            "rgb(255,128,0)",
            "rgb(100%,50%,0)",
            "rgba(255,128,0,.5)",
            "rgb(255 128 0 / .5)",
            "rgb(100% 50% 0 / 50%)",

            "hsl(120,75,65)",
            "hsl(120deg,75%,65%)",
            "hsla(120,75,65,.5)",
            "hsl(120deg 75% 65% / 50%)"
        ];

        $rtn = [];
        foreach ($clrs as $clr) {
            //var_dump($clr." ======> ");
            $color = new Color($clr);
            //var_dump($color);
            $rtn[$clr] = $color->hex()."; ".$color->rgb()."; ".$color->hsl();
        }

        var_dump($rtn);
        exit;
    }



    /**
     * api forDev
     * @desc 颜色参数工具
     * @param String $hex 颜色值 hex 不带 #
     * @return void
     */
    public function colorApi($hex)
    {
        $hex = "#".$hex;
        $color = Color::parse($hex);
        if (!$color instanceof Color) return ["color" => "输入的颜色值不存在"];
        return $color->value();
    }

    
}