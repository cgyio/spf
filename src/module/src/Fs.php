<?php
/**
 * File System 文件系统
 * 管理|操作 可访问路径下的 文件|文件夹
 * 
 * 可访问路径定义在：Src::$current->config->fs["access"]
 */

namespace Spf\module\src;

use Spf\Response;
use Spf\App;
use Spf\module\Src;
use Spf\exception\BaseException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Num;
use Spf\util\Curl;

class Fs 
{




    /**
     * 代理 发送到 Src 模块 的文件系统相关的 请求
     * 
     * 请求示例：
     * https://host/src/api/fs/mkdir/foo/bar/jaz...     
     * 
     * @param Array $args URI 数组
     * @return Mixed 
     */
    public static function response(...$args)
    {
        if (!Is::nemarr($args)) return Response::returnCode(404);

        //调用可能存在的 responseToFooBar 方法
        $action = Str::snake($args[0]);
        $actm = "responseTo".Str::camel($action);
        if (method_exists(static::class, $actm)) {
            array_shift($args);
            return static::$actm(...$args);
        }


        return [
            "args" => $args
        ];
    }

    /**
     * 代理响应
     * mkdir 创建文件夹
     * @param Array $args URI
     * @return Array ["success"=>true, "dir"=>"实际路径"]
     */
    protected static function responseToMkdir(...$args)
    {
        $path = implode("/", $args);
        $mk = Path::mkdir($path);
        Response::insSetType("api");
        return [
            "success" => $mk,
            "dir" => $mk===true ? Path::find($path, Path::FIND_DIR) : null
        ];
    }
}