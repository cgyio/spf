<?php
/**
 * 工具类
 * Arr 数组工具
 */

namespace Spf\util;

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
        } elseif (is_int($var) || is_float($var)) {
            //return self::mk("[\"".$var."\"]");
            return [ $var ];
        } else if (is_string($var)) {
            if (is_numeric($var)) {
                return [ $var ];
            } elseif (Is::json($var)) {
                return Conv::j2a($var);
            } elseif (Is::query($var)) {
                return Conv::u2a($var);
            } elseif (Is::xml($var)){
                return Conv::x2a($var);
            } elseif (false !== Is::explodable($var)) {
                $split = Is::explodable($var);
                return explode($split, $var);
            } else {
                return [ $var ];
            }
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
     * Array 深拷贝，内部的 Array 项执行递归拷贝，确保后续操作不会影响原数组
     * @param Array|Mixed $arr 要拷贝的 对象，可以是 标量|Array|Object|...
     * @param Bool $copyObject 是否 拷贝 Object 类型，默认 false 则直接返回引用
     * @return Array
     */
    public static function copy($arr = [], $copyObject=false)
    {
        //标量直接返回
        if (is_scalar($arr) || is_null($arr)) return $arr;

        //数组类型
        if (is_array($arr)) {
            //递归
            $narr = [];
            foreach ($arr as $k => $v) {
                $narr[$k] = self::copy($v);
            }
            return $narr;
        }

        //对象类型：克隆并递归拷贝所有属性（包括私有/保护属性）
        if (is_object($arr)) {
            //如果 copyObject === false 则直接返回 引用
            if ($copyObject===false) return $arr;

            //特殊对象处理：Closure 无法深拷贝
            if ($arr instanceof \Closure) {
                //!! 不报错，直接返回原值
                //throw new \InvalidArgumentException("不支持匿名函数（Closure）的深拷贝");
                return $arr;
            }

            //克隆对象（浅拷贝）
            $clone = clone $arr;

            //反射获取所有属性（包括私有/保护）
            $reflection = new \ReflectionObject($clone);
            $properties = $reflection->getProperties();

            foreach ($properties as $property) {
                $property->setAccessible(true); //允许访问私有/保护属性
                $value = $property->getValue($clone);
                //深拷贝属性值并重新赋值
                $property->setValue($clone, self::copy($value));
            }

            return $clone;
        }

        //资源类型：无法深拷贝
        if (is_resource($arr)) {
            //!! 不报错，直接返回原值
            //throw new \InvalidArgumentException("不支持资源类型（resource）的深拷贝");
            return $arr;
        }

        //其他未覆盖类型
        //!! 不报错，直接返回原值
        //throw new \InvalidArgumentException("不支持的类型：" . gettype($data));
        return $arr;
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
     * 从关联数组中 挑选任意项目，生成新的关联数组
     * @param Array $arr 数据源 数组
     * @param Array $args 要选取的 一个|多个 数组项 键名|key-path 如果在 $arr 中未找到，则不出现在新数组中
     *              如果最后一个参数是 关联数组，则将其作为默认值，与生成的关联数组合并
     * @return Array 生成新的 关联数组
     */
    public static function choose($arr=[], ...$args)
    {
        if (!Is::nemarr($arr) || !Is::associate($arr)) return [];
        //未指定要选用的 配置项，则返回全部
        if (!Is::nemarr($args)) return $arr;

        //是否指定了 默认值
        $dft = array_slice($args, -1)[0];
        if (Is::nemarr($dft) && Is::associate($dft)) {
            $args = array_slice($args, 0, -1);
        } else {
            $dft = null;
        }

        //新数组
        $narr = [];
        foreach ($args as $ak) {
            if (!Is::nemstr($ak)) continue;

            //直接选取 $arr 子项
            if (strpos($ak, "/")===false) {
                if (isset($arr[$ak])) {
                    $narr[$ak] = $arr[$ak];
                }
                continue;
            }

            //需要通过 key-path 从 $arr 中查找
            $ov = self::find($arr, $ak);
            if (empty($ov)) continue;
            //找到目标内容后，再以相同的 key-path 写入到新数组中
            $ov = self::wrap($ak, $ov);
            $narr = self::extend($narr, $ov);
        }

        //如果定义了 默认值，则合并
        if (Is::nemarr($dft)) {
            $narr = self::extend($dft, $narr);
        }
        
        //返回
        return $narr;
    }

    /**
     * 针对关联数组 $a $b 返回 $a 中的某些键值，要求这些键在 $b 中也存在
     * 类似于求交集，但是默认的 array_intersect 比较的是 值 而不是 键
     * @param Array $a
     * @param Array $b
     * @return Array
     */
    public static function intersect($a=[], $b=[])
    {
        if (!Is::nemarr($a) || !Is::nemarr($b) || !Is::associate($a) || !Is::associate($b)) return [];
        $n = array_filter($a, function($v,$k) use ($b) {
            return isset($b[$k]);
        }, ARRAY_FILTER_USE_BOTH);
        /*$n = [];
        foreach ($a as $k => $v) {
            if (!isset($b[$k])) continue;
            $n[$k] = $v;
        } */
        return $n;
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
     * @param Bool $replaceIndexedArray 额外指定两个 indexed 数组的合并方式，默认 false 合并去重，true 使用新的 替换 旧的
     * @return Array
     */
    //public static function extend($old = [], $new = [], $replaceIndexedArray=false) 
    public static function extend(...$args) 
    {
        if (count($args)>=1 && is_bool(array_slice($args, -1)[0])) {
            $replaceIndexedArray = array_slice($args, -1)[0];
            $args = array_slice($args, 0, -1);
        } else {
            //两个 indexed 数组的默认合并方式：合并去重
            $replaceIndexedArray = false;
        }

        $arglen = count($args);
        if ($arglen>2) {
            $old = self::copy($args[0]);
            for ($i=1; $i<$arglen; $i++) {
                $old = self::extend($old, $args[$i]);
            }
            return $old;
        } else {
            $old = $arglen>=1 ? $args[0] : [];
            $new = $arglen>=2 ? $args[1] : [];
            if (!Is::nemarr($new)) return self::copy($old);
            if (!Is::nemarr($old)) return self::copy($new);
            $old = self::copy($old);
            foreach ($old as $k => $v) {
                if (!array_key_exists($k, $new)) continue;
                if ($new[$k] === "__delete__") {
                    unset($old[$k]);
                    continue;
                }
                if (is_array($v) && is_array($new[$k])) {
                    if (Is::indexed($v) && Is::indexed($new[$k])) {	
                        //复制新值
                        $nv = self::copy($new[$k]);
                        if ($replaceIndexedArray === false) {
                            /**
                             * 新旧值均为数字下标数组
                             * 合并数组，并去重
                             * !! 当数组的值为{}时，去重报错
                             * !! 添加 SORT_REGULAR 参数，去重时不对数组值进行类型转换
                             */
                            $old[$k] = array_unique(array_merge($v, $nv), SORT_REGULAR);
                        } else {
                            /**
                             * 使用 新的数组 替换 旧的数组
                             */
                            $old[$k] = $nv;
                        }
                    } else {
                        //递归 extend
                        $old[$k] = self::extend($v, $new[$k]);
                    }
                } else {
                    //新旧值不同时为 数组时，新值 覆盖 旧值
                    $old[$k] = self::copy($new[$k]);
                }
            }
            foreach ($new as $k => $v) {
                if (array_key_exists($k, $old) || $v === "__delete__") continue;
                $old[$k] = self::copy($v);
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