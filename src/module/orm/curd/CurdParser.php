<?php
/**
 * SPF-Orm 数据库操作模块
 * curd 操作 查询条件解析器 基类
 * 
 * 处理用于 medoo 查询方法的 参数：
 *      table, join, columns, where
 * 
 * 参数形式满足 medoo 方法参数要求
 * 
 */

namespace Spf\module\orm\curd;

use Spf\module\Orm;
use Spf\module\orm\OrmException;
use Spf\module\orm\Db;
use Spf\module\orm\Model;
use Spf\module\orm\Curd;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;
use Spf\util\Conv;
use Medoo\Medoo;

abstract class CurdParser 
{
    /**
     * 关联的 
     */
    //Curd 操作实例
    public $curd = null;
    //数据库实例
    public $db = null;
    //数据模型类全称
    public $model = "";


    /**
     * 构造
     * @param Curd $curd 操作实例
     * @return void
     */
    public function __construct($curd)
    {
        if (!$curd instanceof Curd) return null;
        $this->curd = $curd;
        $this->db = $curd->db;
        $this->model = $curd->model;

        //使用初始化方法
        $this->initParam();
    }

    /**
     * 获取 模型参数
     * @param String $key
     * @return Mixed
     */
    public function conf($key=null)
    {
        return $this->curd->conf($key);
    }

    /**
     * 初始化 curd 参数
     * !! 子类必须实现 !!
     * @return Parser $this
     */
    abstract public function initParam();

    /**
     * 设置 curd 参数
     * !! 子类必须实现 !!
     * @param Mixed $param 要设置的 curd 参数
     * @return Parser $this
     */
    abstract public function setParam($param=null);

    /**
     * 重置 curd 参数 到初始状态
     * !! 子类必须实现 !!
     * @return Parser $this
     */
    abstract public function resetParam();

    /**
     * 执行 curd 操作前 返回处理后的 curd 参数
     * !! 子类必须实现 !!
     * @return Mixed curd 操作 medoo 参数，应符合 medoo 参数要求
     */
    abstract public function getParam();
}