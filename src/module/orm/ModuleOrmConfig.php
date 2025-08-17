<?php
/**
 * 框架模块配置类
 * ORM 数据库模块
 */

namespace Spf\module\orm;

use Spf\config\ModuleConfig;

class ModuleOrmConfig extends ModuleConfig 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [

        //此模块是否 仅 开发环境下 可用
        //"dev" => true,
        
        /**
         * 指定要使用的数据库配置
         * 可以指定多个 不同位置/不同类型 的数据库
         * !! 数据库配置文件的结构，参考 module/orm/temp/db_foo.json
         */
        "dbs" => [
            /* 
            !! 键名必须与配置文件中的实际数据库名称一致
            "db_foo" => [
                !! 必须指定数据库配置文件的绝对路径，包括文件名，可以不要后缀（配置文件后缀，在 Driver 基类中定义）
                "config" => "root/library/db/db_foo[.json]",

                !! 此处指定的其他参数，将覆盖 数据库配置文件中 同名项目的值
                "type" => "mysql",
                "mysql" => [
                    ...
                ],
                "modelPath" => "root/model/db_foo", 数据模型类定义位置 绝对路径
                "modelRequired" => [
                    ...
                ],
            ],
            ... 可定义多个数据库
            */
        ],

        /**
         * 可定义哪些数据库是必须的
         * 在 Orm 实例创建之后，这些数据库也必须立即初始化
         */
        "required" => [
            //"db_foo",
        ],

        //此模块必须的 中间件
        "middleware" => [
            //入站
            "in" => [
                "middleware/orm_foo",
            ],
            //出站
            "out" => [],
            //中间件配置参数
            //...
            "middleware/orm_foo" => [
                "orm_foo_fooo" => 123,
                "orm_foo_barr" => 456
            ],
        ],
    ];

    

    /**
     * 在 应用用户设置后 执行 自定义的处理方法
     * !! 覆盖父类
     * @return $this
     */
    public function processConf()
    {
        
    }

}