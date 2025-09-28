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

        //如果不检查是否存在
        if ($exists !== true) return $path;

        //检查是否存在
        if ($isRemote) return Resource::exists($path) ? $path : null;
        return file_exists($path) ? $path : null;
    }

    /**
     * 针对 Compound 复合类型资源，获取资源内部 子路径下的 文件|文件夹，可指定是否检查存在性
     * 复合类型资源一定是 本地资源
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
                $ver = $res->getVersion();
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