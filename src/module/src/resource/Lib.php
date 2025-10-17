<?php
/**
 * Compound 复合资源处理类 子类
 * 定义 Lib 类型的 本地库资源，处理 库资源的 获取|输出
 * 通常为 本地 JS 库
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\SrcException;
use Spf\module\src\Mime;
use Spf\Request;
use Spf\Response;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;

class Lib extends Compound 
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    public static $stdParams = [
        
        //本地库资源，支持合并库中某些子模块，输出为单一文件资源，指定要合并的 子模块名称
        "module" => [],
        
    ];
    
    /**
     * 定义 复合资源在其 json 主文件中的 资源描述数据的 标准格式
     * !! 在父类基础上扩展
     */
    public static $stdDesc = [
        //此 库资源的变量名 FooBar 形式
        "var" => "",

        //!! 本地库默认不启用缓存，因为这是本地库，都是本地文件
        "enableCache" => false,

        //允许输出的 ext 类型数组，必须是 Mime 类中支持的后缀名类型
        "ext" => ["js","css","woff","otf","ttf"],

        /**
         * 复合资源的根路径
         *  0   针对本地的复合资源 Theme | Icon | Vcom | Lib 等类型：
         *      此参数表示 此复合资源的本地保存路径，不指定则使用当前 *.ext.json 文件的 同级同名文件夹
         *  1   针对远程复合资源 Cdn 类型：
         *      此参数表示 cdn 资源的 url 前缀，不带版本号
         * !! 指定一个 本地库文件 *.lib.json 路径（完整路径），如果不指定，则使用当前 json 文件的路径
         */
        "root" => "",

        //指定此本地库的主文件，例如：core.js 保存在 [root-path]/[lib-name]/[version] 下
        "main" => "",

        //指定本地库中，可以合并的子模块文件夹名称，默认 module，所有要合并的子模块，都应保存在此文件夹下
        "modulePath" => "module",

        //指定默认加载的 字模块，输出时，将与 params["module"] 中指定的子模块 合并
        "moduleDefault" => [],

        /**
         * 指定 子模块的 合并语法
         * 例如：针对 Spf 框架的 js 工具库，使用扩展包模式，则合并子模块的语法为：
         *      __VAR__.use(__MODULE__)   其中 __**__ 为字符串模板，分别指代 库主变量名 和 子模块名
         * 默认使用 use() 语法
         */
        "useSyntax" => "__VAR__.use(__MODULE__)",

    ];

    /**
     * 定义复合资源 内部子资源的 描述参数
     * !! 在父类基础上扩展
     */
    public static $stdSubResource = [
        //子资源类型，可以是 static | dynamic   表示   静态的实际存在的 | 动态生成的   子资源内容
        //!! Lib 子资源默认为 dynamic 类型
        "type" => "dynamic",
    ];

    //本地库 主文件的 资源实例
    public $mainResource = null;

    //要输出的 本地库内部 真实资源实例
    public $innerResource = null;
    
    
    
    /**
     * 工具方法 解析复合资源内部 子资源参数，查找|创建 子资源内容
     * 根据  子资源类型|子资源ext|子资源文件名  分别制定对应的解析方法
     * !! 覆盖父类
     * @return $this
     */
    //dynamic 动态创建 JS 子资源内容
    protected function createDynamicJsSubResource()
    {
        //此本地库的主文件资源实例
        $mres = $this->mainResource;
        if (empty($mres) || $mres->ext !== "js") {
            throw new SrcException("当前本地库 ".$this->resBaseName()." 无法获取有效的主文件资源实例", "resource/getcontent");
        }

        //要合并的 子模块
        $mods = $this->resSubModules();
        //主变量名
        $var = $this->desc["var"];
        //合并语法
        $syntax = $this->desc["useSyntax"];

        /**
         * JS 子模块合并，通过 import 子模块 url，然后在底部 添加合并语法 的方式执行
         */
        $imports = $mres->imports;
        if (!Is::nemarr($imports)) $imports = [];
        //合并语句
        $uses = [];
        foreach ($mods as $modn) {
            //import 变量名
            $modk = str_replace(".","_", $modn);
            $ivar = Str::camel($modk, true);
            $iurl = $this->resModulePath($modn, true);
            if (!Is::nemstr($iurl)) continue;
            //创建 import 语句
            $imports[$ivar] = $iurl;
            //创建 合并语句
            $syn = $syntax;
            $syn = str_replace("__VAR__", $var, $syn);
            $syn = str_replace("__MODULE__", $ivar, $syn);
            $uses[] = $syn.";";
        }
        //var_dump($imports);
        //var_dump($uses);
        //exit;

        //调用主文件 ImportProcessor 生成包含 import 语句的 主文件内容行
        $mres->imports = $imports;
        $importer = $mres->ImportProcessor;
        $importer->callStageExport();
        //调用主文件 RowProcessor 在末尾增加 合并语句
        $rower = $mres->RowProcessor;
        $rower->rowEmpty(3);
        $rower->rowComment("合并子模块", "不要手动修改");
        $rower->rowEmpty(1);
        $rower->rowAdd($uses);

        //将处理后的 主文件资源实例 作为 subResource
        $this->subResource = $mres;
        
        return $this;
    }



    /**
     * 工具方法 初始化复合资源 desc 工具
     * !! 覆盖父类
     */

    /**
     * 将 desc 中指定的 root 参数，更新到资源实例中
     * @return $this
     */
    protected function initRoot()
    {
        /**
         * 本地库 root 必须是本地 *.lib.json 文件路径（完整的 包含 json 文件名的 路径）
         * 如果不指定，只是用当前 json 的文件路径
         */
        $desc = $this->desc;
        $root = $desc["root"] ?? null;

        if (!Is::nemstr($root)) {
            //未指定，则使用当前的 json 文件路径
            $root = $this->real;
            $this->desc["root"] = $root;
        } else {
            //指定了 root，确保其有效
            if ($this->PathProcessor->isRemote($root) === true) {
                //指定了一个 url
                throw new SrcException("当前本地库 ".$this->resBaseName()." 未设置有效的描述文件路径", "resource/getcontent");
            }
            //Path::find 处理
            $rootp = Path::find($root, Path::FIND_FILE);
            if (!Is::nemstr($rootp)) {
                //指定了一个不存在 文件路径
                throw new SrcException("当前本地库 ".$this->resBaseName()." 未设置有效的描述文件路径", "resource/getcontent");
            }
            
            //使用 root 替换当前的 real
            $this->real = $rootp;
            $this->desc["root"] = $rootp;
        }

        return $this;
    }

    /**
     * 根据请求参数 获取目标子资源的 名称|参数 保存到 subResourceName | subResourceOpts 属性
     * @return $this
     */
    protected function initSubResource()
    {
        //创建 主文件实例
        $this->resMainResource();

        //调用父类 initSubResource 方法
        return parent::initSubResource();
    }



    /**
     * 工具方法 getters 资源信息获取
     */

    /**
     * 获取此本地库的 主文件 desc["main"] 中定义的，如果存在，则创建资源实例并返回
     * @param Array $params 可传入资源实例化参数
     * @return Resource|null
     */
    public function resMainResource($params=[])
    {
        //如果已创建
        if (!empty($this->mainResource)) {
            //未改变实例化参数，直接返回
            if (!Is::nemarr($params)) return $this->mainResource;
            //如果传入了新的 params 则 clone
            $mres = $this->mainResource->clone($params);
            //重新保存
            $this->mainResource = $mres;
            return $mres;
        }

        //如果还未创建，则创建
        $desc = $this->desc;
        $main = $desc["main"] ?? null;
        if (!Is::nemstr($main)) {
            throw new SrcException("当前本地库 ".$this->resBaseName()." 未设置有效的主文件路径", "resource/getcontent");
        }
        $mainp = $this->PathProcessor->inner($main, true);
        if (!Is::nemstr($mainp)) {
            throw new SrcException("当前本地库 ".$this->resBaseName()." 未设置有效的主文件路径", "resource/getcontent");
        }

        //创建资源实例
        if (!Is::nemarr($params)) $params = [];
        $params = Arr::extend($params, [
            "belongTo" => $this,
            "ignoreGet" => false,
        ]);
        $mres = Resource::create($mainp, $params);
        if (!$mres instanceof Codex) {
            //主文件必须是 codex 类型资源
            throw new SrcException("当前本地库 ".$this->resBaseName()." 无法主文件 $main 的资源实例", "resource/getcontent");
        }

        //保存
        $this->mainResource = $mres;

        return $mres;
    }

    /**
     * 获取当前请求的 本地库 要合并的 子模块数组
     * @return Array
     */
    public function resSubModules()
    {
        $dft = $this->desc["moduleDefault"] ?? [];
        if (!Is::nemarr($dft)) $dft = [];
        $psm = $this->params["module"] ?? [];
        if (Is::nemstr($psm)) $psm = Arr::mk($psm);
        $mods = array_merge([],$dft,$psm);
        return $mods;
    }

    /**
     * 获取此本地库内部 子模块文件路径
     * @param String $modn 子模块名称
     * @param Bool $url 是否返回 url，默认 false，=true 则将子模块路径 转为可外部访问的 url
     * @return String|null 子模块文件真实路径 或 url，未找到返回 null
     */
    public function resModulePath($modn, $url=false)
    {
        if (!Is::nemstr($modn)) return null;
        //模块后缀名
        if (strpos($modn, ".")===false) $modn .= ".".$this->ext;
        //子模块存放文件夹
        $modp = $this->desc["modulePath"];
        //拼接路径
        $modfp = rtrim($modp,"/")."/".ltrim($modn, "/");
        //查找文件
        $modf = $this->PathProcessor->inner($modfp, true);
        if (!Is::nemstr($modf)) {
            //未找到文件
            return null;
        }

        if ($url !== true) return $modf;
        //转为完整 url
        return Url::src($modf, true);
    }

}