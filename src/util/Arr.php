<?php
/**
 * 工具类
 * Arr 数组工具
 */

namespace Spf\util;

use Spf\Util;
use Spf\util\Is;
use Spf\util\Conv;

class Arr extends Util 
{

    /**
     * conv 方法
     * 任意类型 转为 array
     * @param Mixed $var 
     * @return Array
     */
    public static function mk($var = null)
    {
        if (empty($var)) {
            return [];
        } else if (is_array($var)) {
            return $var;
        } else if (is_string($var)) {
            if (Is::json($var)) {
                return Conv::j2a($var);
            } elseif (Is::query($var)) {
                return Conv::u2a($var);
            } elseif (Is::xml($var)){
                return Conv::x2a($var);
            } elseif (false !== Is::explodable($var)) {
                $split = Is::explodable($var);
                return explode($split, $var);
            //} elseif (is_numeric($var)) {
            //    return self::mk("[\"".$var."\"]");
            } else {
                return [ $var ];
            }
        } elseif (is_int($var) || is_float($var)) {
            //return self::mk("[\"".$var."\"]");
            return [ $var ];
        } elseif (is_object($var)) {
            $rst = [];
            foreach ($var as $k => $v) {
                if (property_exists($var, $k)) {
                    $rst[$k] = $v;
                }
            }
            return $rst;
        } else {
            return [ $var ];
        }
    }

    /**
     * 复制一个array
     * @param Array $arr
     * @return Array
     */
    public static function copy($arr = [])
    {
        return array_merge($arr, []);
    }

    /**
     * 返回arr中最后一个value，针对一维数组，多维数组返回null
     * @param Array $arr
     * @return Mixed
     */
    public static function last($arr = [])
    {
        //return $arr[array_key_last($arr)];    //php>=7.3
        return array_slice($arr, -1)[0];
    }

    /**
     * 按 a/b/c or a.b.c 形式搜索多维数组，返回找到的值，未找到则返回null
     * 如果指定了 $data 则使用 extend 方法修改原数组 $arr，返回修改后的数组
     * @param Array $arr
     * @param String $key 要查询的多维数组的 键名路径
     * @param Mixed $data 如果指定了此值，则使用此值，覆盖原数组中的值，返回修改后的 $arr，默认 __empty__ 标识未指定
     * @return Mixed
     */
    public static function find($arr = [], $key = "", $data="__empty__")
    {
        //确认是否指定了 $data 要覆盖的新键值
        $hasdata = $data!=="__empty__";
        //处理边界情况
        //传入的原数组不是 非空数组，直接返回 null
        if (!Is::nemarr($arr)) return null;
        if (is_int($key)) {
            //传入的键名路径是 数字
            if (isset($arr[$key])) {
                //此数字是存在的 键名
                if ($hasdata) {
                    //覆盖原值
                    $arr[$key] = Arr::extend($arr[$key], $data);
                    return $arr;
                }
                return $arr[$key];
            }
            return null;
        } else if (!Is::nemstr($key)) {
            //输入的键名路径 不是非空字符串
            if ($key=="") {
                //输入空键名路径
                if ($hasdata) {
                    //覆盖原值
                    $arr = Arr::extend($arr, $data);
                    //return $arr;
                }
                return $arr;
            }
            return null;
        }

        if ($hasdata) {
            //指定了新值，使用新值，覆盖原数组
            //按 键名路径 包裹新值，形成多维数组
            $data = Arr::wrap($key, $data);
            //调用 extend 方法，合并 新旧数组
            $arr = Arr::extend($arr, $data);
            //返回合并后数组
            return $arr;
        }

        //未指定新值，按键名路径，查找多维数组
        $ctx = $arr;
        //当 key 中既包含 / 也包含 . 则以 / 作为分隔符
        if (strpos($key, ".")!==false && strpos($key, "/")!==false) {
            $karr = explode("/", $key);
        } else {
            if (strpos($key, ".")!==false) {
                $karr = explode('.', $key);
            } else if (strpos($key, "/")!==false) {
                $karr = explode("/", $key);
            } else {
                $karr = [$key];
            }
        }
        
        $rst = null;
        for ($i=0; $i<count($karr); $i++) {
            $ki = $karr[$i];
            if (!isset($ctx[$ki])) {
                break;
            }
            if ($i >= count($karr)-1) {
                $rst = $ctx[$ki];
                break;
            } else {
                if (is_array($ctx[$ki])) {
                    $ctx = $ctx[$ki];
                } else {
                    break;
                }
            }
        }
        return $rst;
    }

    /**
     * 按 a/b/c or a.b.c 形式将指定的 $val 包裹为多维数组
     * a/b/c  -->  [ "a"=>[ "b"=>[ "c"=>$val ] ] ]
     * @param String $xpath 数组键名 路径
     * @param Mixed $val 要包裹的 键值
     * @return Array 返回包裹后的数组 a/b/c  -->  [ "a"=>[ "b"=>[ "c"=>$val ] ] ]
     */
    public static function wrap($xpath, $val=null)
    {
        if (!Is::nemstr($xpath)) return [];
        //当 xpath 中既包含 / 也包含 . 则以 / 作为分隔符
        if (strpos($xpath, ".")!==false && strpos($xpath, "/")!==false) {
            $karr = explode("/", $xpath);
        } else {
            if (strpos($xpath, ".")!==false) {
                $karr = explode('.', $xpath);
            } else if (strpos($xpath, "/")!==false) {
                $karr = explode("/", $xpath);
            } else {
                $karr = [$xpath];
            }
        }
        $rst = $val;
        for ($i=count($karr)-1; $i>=0; $i--) {
            $ki = $karr[$i];
            $rst = [
                $ki => $rst
            ];
        }
        return $rst;
    }

    /**
     * 查找arg（可有多个）在arr中出现的次数，不指定arg则返回数组长度
     * @param Array $arr
     * @param Array $args 要查找的 值
     * @return Int
     */
    public static function len($arr = [], ...$args)
    {
        if (empty($args)) return count($arr);
        $count = [];
        foreach ($args as $i => $v) {
            $count[$i] = count(array_keys($arr, $v));
        }
        return count($args) <= 1 ? $count[0] : $count;
    }

    /**
     * 查找arg（可有多个）在arr中的key，多个arg的默认返回第一个，
     * 最后一参数为 false 时，返回所有key
     * 未找到返回 false
     * @param Array $arr
     * @param Array $args 要查找的 值
     * @return Mixed String or Array or false
     */
    public static function key($arr = [], ...$args)
    {
        if (empty($args)) return false;
        if (count($args)<=1 && is_bool(self::last($args))) return false;
        $getFirst = is_bool(self::last($args)) ? array_pop($args) : true;
        $idxs = [];
        foreach ($args as $i => $v) {
            $idxs[$i] = $getFirst ? array_search($v, $arr) : array_keys($arr, $v);
        }
        return count($args) <= 1 ? $idxs[0] : $idxs;
    }

    /**
     * 返回当前arr的维度
     * @param Array $arr
     * @return Int
     */
    public static function dimension($arr = [])
    {
        $di = [];
        foreach ($arr as $k => $v) {
            if (!is_array($v)) {
                $di[$k] = 1;
            } else {
                $di[$k] = 1 + self::dimension($v);
            }
        }
        return max($di);
    }

    /**
     * 判断两个arr是否相等，如果是多维数组，则递归判断
     * @param Array $arr_a
     * @param Array $arr_b
     * @return Bool
     */
    public static function equal($arr_a = [], $arr_b = [])
    {
        //异或运算 ^ （true ^ false == true），确保两个数组维度相同
        $di_a = Is::onedimension($arr_a);
        $di_b = Is::onedimension($arr_b);
        if ($di_a ^ $di_b) return false;
        //长度必须相同
        if (count($arr_b) != count($arr_a)) return false;
        
        if ($di_a) {   //一维数组比较
            return empty(array_diff_assoc($arr_a, $arr_b));   //值 和 顺序都必须一样
        } else {    //多维数组比较，递归
            foreach ($arr_a as $k => $v) {
                if (isset($arr_b[$k])) {
                    if (is_array($v) ^ is_array($arr_b[$k])) {
                        return false;
                    } else {
                        if (self::equal($v, $arr_b[$k]) === true) {
                            continue;
                        } else {
                            return false;
                        }
                    }
                } else {
                    return false;
                }
            }
            return true;
        }
    }

    /**
     * 多维数组递归合并，新值替换旧值，like jQuery extend
     * @param Array $old
     * @param Array $new
     * @return Array
     */
    public static function extend($old = [], $new = []) 
    {
        if (func_num_args()>2) {
            $args = func_get_args();
            $old = $args[0];
            for ($i=1; $i<count($args); $i++) {
                $old = self::extend($old, $args[$i]);
            }
            return $old;
        } else {
            if (!Is::nemarr($new)) return $old;
            if (!Is::nemarr($old)) return $new;
            foreach ($old as $k => $v) {
                if (!array_key_exists($k, $new)) continue;
                if ($new[$k] === "__delete__") {
                    unset($old[$k]);
                    continue;
                }
                if (is_array($v) && is_array($new[$k])) {
                    if (Is::indexed($v) && Is::indexed($new[$k])) {	
                        /**
                         * 新旧值均为数字下标数组
                         * 合并数组，并去重
                         * !! 当数组的值为{}时，去重报错
                         * !! 添加 SORT_REGULAR 参数，去重时不对数组值进行类型转换
                         */
                        $old[$k] = array_unique(array_merge($v, $new[$k]), SORT_REGULAR);
                    } else {
                        //递归extend
                        $old[$k] = self::extend($v, $new[$k]);
                    }
                } else {
                    //新旧值不同时为 数组时，新值 覆盖 旧值
                    $old[$k] = $new[$k];
                }
            }
            foreach ($new as $k => $v) {
                if (array_key_exists($k, $old) || $v === "__delete__") continue;
                $old[$k] = $v;
            }
            return $old;
        }
    }

    /**
     * 多维数组转为 indexed 数组
     * 例如：
     *  [
     *      [ "name" => "a", "children" => [ indexed_a ... ] ],
     *      [ "name" => "b", "children" => [ indexed_b ... ] ],
     *      ...
     *  ] 转换后：
     *  [
     *      indexed_a ... ,
     *      indexed_b ... ,
     *      ...
     *  ]
     * @param Array $arr
     * @param String $key 多维数组的每个层级中 需要被添加到 indexed 数组中的 键名，默认 children
     * @return Array
     */
    public static function indexed($arr=[], $key="children")
    {
        $narr = [];
        for ($i=0;$i<count($arr);$i++) {
            $ai = $arr[$i];
            $ki = [];
            if (isset($ai[$key])) {
                $ki = $ai[$key];
                unset($ai[$key]);
            }
            $narr[] = $ai;
            if (!empty($ki)) {
                $idxki = self::indexed($ki, $key);
                if (!empty($idxki)) {
                    $narr = array_merge($narr, $idxki);
                }
            }
        }
        return $narr;
    }

    /**
     * 将多维关联数组 转为 一维数组，各层级键名 使用 glup 连接符连接后 作为 一维数组 键名
     * 例如：
     *  [
     *      "foo" => [
     *          "bar" => [
     *              "jaz" => 1,
     *          ],
     *          "tom" => 2,
     *      ],
     *      "bar" => 3,
     *  ] 转换后：
     *  [
     *      "foo_bar_jaz" => 1,
     *      "foo_tom" => 2,
     *      "bar" => 3
     *  ]
     * @param Array $arr
     * @param String $glup 键名的 连接符 默认 _
     * @return Array
     */
    public static function flat($arr=[], $glup="_")
    {
        if (!Is::nemstr($glup)) $glup = "_";
        if (!Is::associate($arr)) return [];
        /**
         * 因为是 递归，glup 可能是 foo_bar_ 形式
         * 需要解析 pre 和 glup
         * !! 用作 glup 连接字符的 strlen() 必须为 1
         */
        if (strlen($glup)>1) {
            $pre = $glup;
            $glup = substr($pre, -1);
        } else {
            $pre = "";
        }
        $rtn = [];
        foreach ($arr as $k => $sub) {
            if (Is::associate($sub)) {
                $rtn = array_merge($rtn, self::flat($sub, $pre.$k.$glup));
                continue;
            }
            $rtn[$pre.$k] = $sub;
        }
        return $rtn;
    }

    /**
     * 一维 indexed 数组去重
     * @param Array $arr
     * @return Array
     */
    public static function uni($arr = [])
    {
        if (Is::indexed($arr)) {
            $arr = array_merge(array_flip(array_flip($arr)));
        }
        return $arr;
    }

    /**
     * 版本号组成的数组排序
     * @param Array $vers 要排序的版本号数组，like：[ 2.0, 1.3.9, 1.13.8, 4.0 ]
     * @param String $order 排序方式，默认 asc，可选 desc
     * @param Int $dig 版本号每一节最大位数，默认 5 位
     * @return Array 排序后的数组
     */
    public static function sortVersion($vers = [], $order = "asc", $dig = 5)
    {
        if (count($vers)<=1) return $vers;
        $lvls = 0;
        $varrs = array_map(function ($ver) use (&$lvls) {
            $varr = explode(".", $ver);
            if (count($varr)>$lvls) $lvls = count($varr);
            return $varr;
        }, $vers);
        $nvers = array_map(function ($varr) use ($lvls, $dig) {
            if (count($varr)<$lvls) {
                $nvarr = array_merge($varr, array_fill(0, $lvls-count($varr), "0"));
            } else {
                $nvarr = $varr;
            }
            $vstrs = array_map(function ($nvarri) use ($dig) {
                return str_pad($nvarri, $dig, "0", STR_PAD_LEFT);
            }, $nvarr);
            $vstr = implode("",$vstrs);
            return $vstr*1;
        }, $varrs);
        
        $verasso = [];
        for ($i=0;$i<count($vers);$i++) {
            $verasso[$nvers[$i]] = $vers[$i];
        }

        $order = strtolower($order);
        if ($order=="asc") {
            ksort($verasso);
        } else if ($order=="desc") {
            krsort($verasso);
        }
        /*$vs = [];
        foreach ($verasso as $k => $v) {
            $vs[] = $v;
        }*/
        return array_values($verasso);
    }
}