<?php
/**
 * Resource 资源处理中间件 Processor 处理器子类
 * PathProcessor 专门处理 任意类型资源 路径相关操作
 * 
 * !! 此处理器 不会修改资源数据，仅为资源实例增加一个 pather 属性指向此处理器实例，用来调用处理器内部的 工具方法
 * !! 默认所有资源在初始化阶段 会自动创建此处理器实例，不需要再在资源类的 middleware 参数中显式指定
 */

namespace Spf\module\src\resource\middleware;

use Spf\module\src\Resource;
use Spf\module\src\resource\Compound;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Url;
use Spf\util\Conv;
use Spf\util\Path;

class PathProcessor extends Processor 
{
    //设置 当前中间件实例 在资源实例中的 属性名，不指定则自动生成 NS\middleware\FooBar  -->  foo_bar
    public static $cacheProperty = "pather";



    /**
     * 工具方法
     */

    /**
     * 判断一个路径是否 remote 远程路径
     * @param String $path
     * @return Bool
     */
    public function isRemote($path)
    {
        if (!Is::nemstr($path)) return false;
        if (Url::isUrl($path)) return true;
        if (strtolower(substr(trim($path), 0, 4)) === "http") return true;
        return false;
    }

    /**
     * 获取目标资源实例的 可用于路径解析的 基础路径 通常是 real 或 uri 
     * !! 路径分隔符 一定是 DS 或者 url 分隔符
     * @return String|null
     */
    public function basePath()
    {
        $res = $this->resource;
        $base = Is::nemstr($res->real) ? $res->real : (Is::nemstr($res->uri) ? $res->uri : null);
        if (!Is::nemstr($base)) return null;
        //remote 路径
        if ($this->isRemote($base)) {
            //确保 路径分隔符 正确
            return str_replace(["\\", DS], "/", $base);
        }
        //本地路径
        $base = str_replace(["/","\\"], DS, $base);
        //去除可能存在的 ../
        return Path::fix($base);
    }

    /**
     * 获取当前资源所在的 文件夹路径 
     * /data/foo/bar/jaz.js         --> /data/foo/bar           普通 local|require 资源
     * /data/foo/bar/jaz            --> /data/foo/bar           build 类型的 资源
     * https://host/foo/bar/jaz.js  --> https://host/foo/bar    remote 类型的 资源
     * create 手动创建的 资源类型，根据传入的 资源上下文决定
     * @return String|null
     */
    public function updir()
    {
        $base = $this->basePath();
        if (!Is::nemstr($base)) return null;
        $dirn = dirname($base);
        $dirn = rtrim($dirn, DS);
        $dirn = rtrim($dirn, "/");
        return $dirn;
    }

    /**
     * 获取在当前资源所在文件夹下的 子路径，与当前资源同级的文件或文件夹，可指定是否检查存在性
     * @param String $subpath 可拼接指定的 子路径
     * @param Bool $exists 是否检查 路径是否存在，如果 true 且 路径不存在，则返回 null
     * @return String|null
     */
    public function sibling($subpath="", $exists=false)
    {
        $updir = $this->updir();
        if (!Is::nemstr($updir)) return null;

        $isRemote = $this->isRemote($updir);

        if ($isRemote) {
            //针对 remote 路径
            $subpath = str_replace(["\\",DS], "/", $subpath);
            $path = $updir.(Is::nemstr($subpath) ? "/".ltrim($subpath, "/") : "");
        } else {
            //本地路径
            $subpath = str_replace(["\\","/"], DS, $subpath);
            $path = $updir.(Is::nemstr($subpath) ? DS.ltrim($subpath, DS) : "");
        }

        //去除可能存在的 ../
        $path = Path::fix($path);

        //如果不检查是否存在
        if ($exists !== true) return $path;

        //检查是否存在
        if ($isRemote) return Resource::exists($path) ? $path : null;
        return file_exists($path) ? $path : null;
    }

    /**
     * 获取在当前资源所在文件夹下的 子路径，与当前资源同级的文件或文件夹，将其转为 url 
     * @param String $subpath 可拼接指定的 子路径
     * @param Bool $exists 是否检查 路径是否存在，如果 true 且 路径不存在，则返回 null
     * @return String|null
     */
    public function url($subpath="", $exists=false)
    {
        $sibling = $this->sibling($subpath, $exists);
        if (!Is::nemstr($sibling)) return null;
        //转为完整 url
        return Url::src($sibling, true);
    }

    /**
     * 针对 Compound 复合类型资源，获取资源内部 子路径下的 文件|文件夹，可指定是否检查存在性
     * @param String $subpath 可拼接指定的 子路径
     * @param Bool $exists 是否检查 路径是否存在，如果 true 且 路径不存在，则返回 null
     * @return String|null
     */
    public function inner($subpath="", $exists=false)
    {
        $res = $this->resource;

        //生成要检查的路径字符串
        $path = [];
        //需要在 subpath 前增加 当前资源名称 
        $path[] = $res->resName();

        //如果是 复合资源
        if ($res instanceof Compound) {
            if ($res->desc["enableVersion"] === true) {
                //启用了版本控制，需要在 subpath 前增加 版本号
                $ver = $res->resVersion();
                if (Is::nemstr($ver)) $path[] = $ver;
            }
        }

        //subpath 添加到 path
        if (Is::nemstr($subpath)) {
            //subpath 路径分隔符 调整
            $subpath = str_replace(["\\", DS], "/", $subpath);
            $subpath = ltrim($subpath, "/");
            $path[] = $subpath;
        }

        //构建路径
        $path = implode("/", $path);

        //调用 sibling 方法
        return $this->sibling($path, $exists);
    }

    /**
     * 针对 Compound 复合类型资源，获取资源内部 子路径下的 文件|文件夹，将其转为 url 
     * @param String $subpath 可拼接指定的 子路径
     * @param Bool $exists 是否检查 路径是否存在，如果 true 且 路径不存在，则返回 null
     * @return String|null
     */
    public function innerUrl($subpath="", $exists=false)
    {
        $inner = $this->inner($subpath, $exists);
        if (!Is::nemstr($inner)) return null;
        //转为完整 url
        return Url::src($inner, true);
    }

    /**
     * 专门处理 Codex 类型资源中的 import 语句指向的 文件路径|url
     * !! 针对 js 文件，如果 import 指向本地路径，则将其转为 url，因为 js 文件不能直接合并 任意本地文件，可能存在 代码冲突
     * @param String $url import 语句中的 文件路径|url
     * @param String $ext 可额外指定此文件的 ext 不指定默认使用 $this->resource->ext 
     * @return String 根据传入的 url 决定返回 实际存在的 本地路径  或  完整的 url
     */
    public function fixImportUrl($url, $ext=null)
    {
        if (!Is::nemstr($url)) return $url;
        $url = trim($url);
        //补齐 后缀名
        $url = $this->autoSuffix($url, $ext);

        //res
        $res = $this->resource;
        //是否 import js 文件
        $isjs = Is::nemstr($ext) ? $ext==="js" : $res->ext==="js";
        
        //当前 url
        $uo = Url::current();

        //先检查一次 url 是否真是文件路径
        if (file_exists($url)) {
            if ($isjs) {
                //如果 import 的是 js 文件，将本地路径 转为 url
                return Url::src($url, true);
            }
            return $url;
        }

        //如果 url 符合相对路径的形式，则 先检查 是否 import 了本地文件
        if (
            substr($url, 0,4) !== "http" &&
            !in_array(substr($url, 0,2), ["//", "./"]) &&
            substr($url, 0,1) !== "/"
        ) {
            //首先尝试使用 Path::find() 方法
            $fp = Path::find($url, Path::FIND_FILE);
            if (!file_exists($fp)) {
                if ($res->ParentProcessor->hasParent()) {
                    //如果此资源是 某个 Compound 复合资源内部的资源，则使用 复合资源实例的 pather->inner 方法，获取可能存在的 内部文件路径
                    $pres = $res->parentResource;
                    $fp =  $pres->PathProcessor->inner($url, true);
                } else {
                    //此资源是普通资源，直接通过 sibling 查询同级其他文件路径
                    $fp = $this->sibling($url, true);
                    if (!file_exists($fp)) {
                        //如果此资源同级未找到文件，则尝试在此资源同级同名文件夹下 查找
                        $fp = $this->inner($url, true);
                    }
                }
            }

            //找到本地文件
            if (file_exists($fp)) {
                if ($isjs) {
                    //如果 import 的是 js 文件，将本地路径 转为 url
                    return Url::src($fp, true);
                }
                return $fp;
            }

            //未找到任何 本地文件，则作为 url 拼接
            if (isset($pres)) {
                //如果此资源是 某个 Compound 复合资源内部的资源，则使用 复合资源实例的 pather->innerUrl 方法，获取对应的 url 
                $fp = $pres->PathProcessor->innerUrl($url, false);
            } else {
                //普通资源，直接使用 url 方法
                $fp = $this->url($url, false);
            }
            //不检查最终的 url 是否存在，直接返回
            return $fp;
        }

        //如果 url 符合网址形式
        if (substr($url, 0, 4) === "http" && strpos($url, "://") !== false) {
            //传入完整的 url 直接返回
            return $url;
        } else {
            //传入 以 // 或 / 或 ./ 开头的 url
            if (substr($url, 0, 2) === "//") {
                $url = $uo->protocol.":".$url;
            } else if (substr($url, 0, 2) === "./") {
                $url = $uo->domain.substr($url, 1);
            } else {
                $url = $uo->domain.$url;
            }
            return $url;
        }
    }

    /**
     * 专门处理 Codex 类型资源 merge 合并操作中的 要合并的本地资源的 路径
     * @param String $path import 语句中的 文件路径|url
     * @param String $ext 可额外指定此文件的 ext 不指定默认使用 $this->resource->ext 
     * @return String|null 真实存在的 本地文件路径
     */
    public function fixMergeFilePath($path, $ext=null)
    {
        if (!Is::nemstr($path)) return $path;
        $path = trim($path);
        if (!Is::nemstr($ext)) {
            //先检查 $path 中是否包含 ext 
            $ext = pathinfo($path)["extension"];
            //最后使用 当前资源的 ext 作为默认值
            if (!Is::nemstr($ext)) $ext = $this->resource->ext;
        }
        //补齐后缀名
        $path = $this->autoSuffix($path, $ext);

        //先检查一次 是否传入了 真是文件路径
        if (file_exists($path)) return $path;

        //再尝试 Path::find
        $fp = Path::find($path, Path::FIND_FILE);
        if (file_exists($fp)) return $fp;

        //最后尝试 在当前资源的 同级目录  或  内部目录  下查找
        //res
        $res = $this->resource;
        if ($res->ParentProcessor->hasParent()) {
            //如果此资源是 某个 Compound 复合资源内部的资源，则使用 复合资源实例的 pather->inner 方法，获取可能存在的 内部文件路径
            $pres = $res->parentResource;
            $fp =  $pres->PathProcessor->inner($path, true);
        } else {
            //此资源是普通资源，直接通过 sibling 查询同级其他文件路径
            $fp = $this->sibling($path, true);
            if (!file_exists($fp)) {
                //如果此资源同级未找到文件，则尝试在此资源同级同名文件夹下 查找
                $fp = $this->inner($path, true);
            }
        }
        if (file_exists($fp)) return $fp;

        //未找到任何文件
        return null;
    }

    /**
     * 自动补齐 资源路径中的 ext 后缀名
     * @param String $path 可以是 本地路径 或 url
     * @param String $ext 后缀名，不指定则默认使用 $this->resource->ext
     * @return String 补齐后的 路径
     */
    public function autoSuffix($path, $ext=null)
    {
        if (!Is::nemstr($path)) return $path;
        if (!Is::nemstr($ext)) $ext = $this->resource->ext;
        //补齐 后缀名
        if (Is::nemstr($ext)) {
            //后缀名长度，包含前面的 .
            $extlen = strlen($ext)+1;
            //去除可能存在的 ?...
            if (strpos($path, "?")!==false) {
                $pa = explode("?", $path);
            } else {
                $pa = [$path];
            }

            //补齐后缀名
            if (substr($pa[0], $extlen*-1) !== ".$ext") $pa[0] = $pa[0].".$ext";
            $path = implode("?", $pa);
        }
        return $path;
    }



    /**
     * !! 已废弃
     * 针对本地资源，或此资源外部访问 url 的 前缀 urlpre
     * 即 通过 urlpre/[$this->name] 可以直接访问到此资源
     * 例如：
     * 有本地资源：             /data/ms/app/foo_app/assets/bar/jaz.js
     * 相对地址为：             src/foo_app/bar/jaz.js 
     * 最终生成的 url 前缀为：   https://domain/foo_app/src/bar
     * !! 子类可覆盖此方法，例如 ParsablePlain 类型资源 有不同的 url 访问规则
     * @return String|null
     */
    public function __getLocalResUrlPrefix()
    {
        if ($this->sourceType !== "local") return null;
        if (!Is::nemstr($this->real)) return null;
        //文件 basename
        $basename = basename($this->real);
        //本地资源路径 转为 相对路径
        $rela = Path::rela($this->real);
        if (!Is::nemstr($rela)) return null;
        //相对路径 数组
        $relarr = explode("/", trim($rela,"/"));

        /**
         * !! 只有 Src::$current->config->resource["access"] 中定义的 路径 可以被 url 访问到
         */
        $access = [];
        if (Src::$isInsed === true) {
            $access = Src::$current->config->resource["access"] ?? [];
        }
        if (!Is::nemarr($access)) return null;
        $accessable = false;
        foreach ($access as $aci) {
            $acilen = strlen($aci);
            if (substr($rela, 0, $acilen) === $aci) {
                //去除 路径 前缀
                $rela = substr($rela, $acilen);
                $accessable = true;
                break;
            }
        }
        if ($accessable !== true) return null;

        //当前 url
        $uo = Url::current();

        //生成 urlpre
        $ua = [];
        $ua[] = $uo->domain;
        //app
        if (App::$isInsed === true) {
            $appk = App::$current::clsk();
            if ($appk !== "base_app") {
                $ua[] = $appk;
                //去除 rela 相对路径中的 appk
                if (strpos($rela, $appk."/") !== false) {
                    $rela = str_replace($appk."/", "", $rela);
                }
            }
        }
        //src
        $ua[] = "src";
        //去除 rela 相对路径中的 文件名 basename
        $rela = str_replace($basename, "", $rela);
        $ua[] = trim($rela, "/");

        return implode("/", $ua);
    }



}