<?php
/**
 * 框架 可复用类特征
 * 通用的 基础类功能：
 *      获取类名 FooBar | foo_bar
 *      获取类type  NS\foo_bar\jaz\Tom::is(false|true)  ==  foo_bar | FooBar
 *      判断类type  NS\foo_bar\jaz\Tom::isFooBarCls()   ==  true
 */

namespace Spf\traits;

use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

trait Base 
{
    /**
     * !! 引用类 不要覆盖这些方法
     */

    /**
     * 返回当前类的 类名 FooBar 格式
     * @return String
     */
    final public static function clsn()
    {
        return Cls::name(static::class);
    }

    /**
     * 返回当前类的 类名 foo_bar 格式
     * @return String
     */
    final public static function clsk()
    {
        $clsn = static::clsn();
        return Str::snake($clsn, "_");
    }

    /**
     * 魔术方法 __callStatic
     */
    public static function __callStatic($key, $args)
    {
        $cls = static::class;

        /**
         * NS\foo_bar\Jaz::is()         -->  foo_bar
         * NS\foo_bar\Jaz::is(true)     -->  FooBar
         */
        if ($key === "is") {
            //去除类全称的 NS 头
            $clsp = Cls::rela($cls);
            if (!Is::nemstr($clsp)) return null;
            $clsp = explode("/", trim($clsp,"/"));
            if (count($clsp)<=0) return null;
            $isk = Str::snake($clsp[0],"_");
            if (!empty($args) && $args[0]===true) return Str::camel($isk, true);
            return $isk;
        }

        /**
         * Class::isFooBarCls()     -->  is_subclass_of( Class::class, Cls::find("foo/bar") )
         *                          -->  判断 此类全称 是否存在 NS\[foo_bar]\...
         */
        if ( false !== ($cp = Str::between($key, "is", "Cls"))) {
            $pclk = Str::snake($cp, "/");
            $pcls = Cls::find($pclk);
            if (empty($pcls)) $pcls = Cls::find($pclk, "Spf\\");
            if (class_exists($pcls)) return is_subclass_of($cls, $pcls);

            //解析类全称，判断在 NS 之后是否存在 $key
            return static::is() === Str::snake($cp,"_");
        }

        return null;
    }
}