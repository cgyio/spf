<?php
/**
 *  SPF 框架 可复用类特征
 *  arrayIterator
 * 
 *  1   使类具有 $var[$i]形式访问 和 迭代 的功能，类必须 implements \ArrayAccess, \IteratorAggregate \Countable
 *  2   增加 each 方法
 *  3   实现 count 方法， count($thisInstance) == count($this->context)
 * 
 */

namespace Spf\traits;

trait arrayIterator
{
    //操作的数组
    //protected $context = [];

    //通过ArrayAccess接口实现直接访问 context 数组
    //实现接口方法
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->context[] = $value;
        } else {
            $this->context[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->context[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->context[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->context[$offset]) ? $this->context[$offset] : null;
    }

    //通过IteratorAggregate接口实现直接迭代 context 数组
    //实现接口方法
    public function getIterator()
    {
        return new \ArrayIterator($this->context);
    }

    //each
    public function each(\Closure $closure)
    {
        $closure = $closure->bindTo($this);
        $rtn = [];
        foreach ($this->context as $key => $value) {
            $rst = $closure($value, $key);
            if ($rst===true) continue;
            if ($rst===false) break;
            $rtn[$key] = $rst;
        }
        return $rtn;
    }

    //map
    public function map($callback)
    {
        return array_map($callback, $this->context);
    }

    //count
    public function count()
    {
        return count($this->context);
    }
}