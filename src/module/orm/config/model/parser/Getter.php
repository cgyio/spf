<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型配置解析类  继承自 Parser 基类
 * 
 * 处理 数据模型(表) 类中 定义的 fooBarGetter 方法，生成计算字段
 * 
 * !! 还会额外检查 字段类型 Types 类中 可能存在的 某些特殊字段类型自有的 Getter 方法，创建对应的 计算字段
 * 例如：
 *      Datetime 类型字段       自动创建 col_name_datetime_str 计算字段 自动输出 Y-m-d H:i:s 字符串
 *      Money 类型字段          自动创建 col_name_money_str 计算字段，自动输出 分转元后的 ￥12.35 金额字符
 * 
 * 
 * 在 model 类中定义了 protected fooBarGetter() 方法，
 * 必须有注释：
 *      /**
 *       * getter
 *       * @name foo_bar    # 字段名必须是 foo_bar 形式
 *       * @title 字段名
 *       * @desc 字段说明
 *       * @width 3
 *       * @type String     # php 类型
 *       * @jstype String   # js 类型
 *       * ...
 * 
 * 
 */

namespace Spf\module\orm\config\model\parser;

use Spf\module\orm\OrmException;
use Spf\module\orm\config\DbConfig;
use Spf\module\orm\config\model\Parser;
use Spf\module\orm\Types;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class Getter extends Parser 
{
    /**
     * 解析过程中的 数据，这些数据最终将被 写入 $this->context 
     * 通常指定了 此解析器将要修改 $this->context 中的 哪些数据
     * !! 与 DbConfig::$exportModelConf[] 结构一致
     * !! 覆盖父类
     */
    protected $temp = [
        //特殊字段 列表
        "special" => [],
        //每个字段的 参数数组
        "column" => [],
        //字段名集合
        "columns" => [],
    ];

    /**
     * 默认的 getter 计算字段的 字段参数结构
     */
    protected $dftGetter = [
        //基础数据
        "name" => "",
        "title" => "",
        "desc" => "",

        //字段类型
        "type" => [
            "js" => "String",   //用于前端显示的 字段数据类型  String|Boolean|Int|Float|Array|Object
            "php" => "String",  //用于后端处理时的 字段数据类型，String|Boolean|Number|Array...
        ],

        //!! 是否 计算字段 
        "isGetter" => true,

        //计算字段执行时的 依赖参数
        "getter" => [
            "class" => "",          //getter 方法所在类全称
            "method" => "",         //getter 方法名全称
            "args" => [],           //默认传递的 参数
            "isStatic" => false,    //是否静态调用
        ],

        //默认 width 3
        "width" => 3,
    ];
    


    /**
     * 解析入口
     * 解析 $this->origin 参数，将生成的最终参数 写入 $this->context 并返回
     * !! 必须实现，覆盖父类
     * @return Array 解析得到的 此数据模型(表)参数 []
     */
    public function parse()
    {
        //收集 getters
        $getters = [];

        // 0    查询 数据模型类中定义的 Getter 方法
        $inmod = Cls::specific(
            $this->model,           //当前模型类 全称
            "protected,&!static",   //非静态，protected 方法
            "getter",               //必须包含 * getter 注释
            null,                   //没有额外筛选方法
            //$collector              //自定义的 方法信息处理方法
            function($mi, $mc) use (&$getters) {
                $conf = $this->parseGetter($mi, $mc);
                $mn = $conf["name"];
                $getters[$mn] = $conf;
                return $conf;
            }
        );

        // 1    检查 Types 特殊字段类型中的 Getter 计算字段
        $this->eachColumn(function($colk, $colv) use (&$getters) {
            //已解析的 字段参数
            $ctx = $this->context["column"][$colk] ?? [];
            $ctp = $ctx["type"]["def"] ?? null;
            $tpcls = Types::get($ctp);
            if (!class_exists($tpcls)) return true;

            //收集 Types 字段类型类中可能定义的 getter 计算字段方法
            $intp = Cls::specific(
                $tpcls,                 //当前 Types 类型类
                "public,&!static",      //非静态，public 方法
                "getter",               //必须包含 * getter 注释
                null,                   //没有额外筛选方法
                //$collector              //自定义的 方法信息处理方法
                function($mi, $mc) use (&$getters, $colk, $ctp) {
                    //特殊类型字段的 计算字段名
                    $nname = $colk."_".$ctp."_".$mc["name"];
                    $conf = $this->parseGetter($mi, $mc, [
                        "name" => $nname,
                        "getter" => [
                            //!! 传入当前的 特殊类型字段，作为计算字段的 依赖
                            "args" => [$colk],
                        ]
                    ]);
                    $getters[$nname] = $conf;
                    return $conf;
                }
            );

            return true;
        });

        //写入 $temp
        $gks = array_merge([], array_keys($getters));
        $this->setTemp([
            "column" => $getters,
            "columns" => $gks,
            "special" => [
                "getter" => $gks
            ],
        ]);

        //解析完成，将 $this->temp 写入 $this->context 
        $this->setCtx($this->temp);

        //!! forDev
        //var_dump($this->context["column"]["role"]);
        //exit;

        return $this->context;
    }



    /**
     * 内部工具
     */

    /**
     * 解析收集到的 getter 方法的参数，输出为 dftGetter 参数结构
     * @param String $mi 收集到的 ReflectionMethod 实例
     * @param Array $mc 收集到的 方法参数 Cls::specific() 返回的结构  方法注释中的 @xxxx 项目的 键值对
     * @param Array $override 额外手动覆盖参数
     * @return Array 返回处理后的 与 dftGetter 结构一致的 方法参数
     */
    protected function parseGetter($mi, $mc=[], $override=[])
    {
        //处理后
        $rtn = Arr::copy($this->dftGetter);

        //name|title|desc
        $rtn["name"] = Is::nemstr($mc["name"]) ? $mc["name"] : $mi->getName();
        $rtn["title"] = Is::nemstr($mc["title"]) ? $mc["title"] : $rtn["name"];
        $rtn["desc"] = Is::nemstr($mc["desc"]) ? $mc["desc"] : $rtn["title"];
        unset($mc["name"]);
        unset($mc["title"]);
        unset($mc["desc"]);

        //!! 计算字段 默认 php 类型 String
        $tp = Is::nemstr($mc["type"]) ? $mc["type"] : "String";
        $jstp = Is::nemstr($mc["jstype"]) ? $mc["jstype"] : "String";
        $rtn["type"] = [
            "js" => $jstp,
            "php" => $tp,
        ];
        unset($mc["type"]);
        unset($mc["jstype"]);

        //getter 方法
        $rtn["getter"] = Arr::extend($rtn["getter"], [
            "class" => $mc["class"],
            "method" => $mc["method"]
        ]);

        //override
        if (Is::nemaso($override)) {
            $rtn = Arr::extend($rtn, $override);
        }

        return $rtn;
    }
}