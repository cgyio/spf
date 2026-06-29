<?php
/**
 * SPF-Orm 数据库操作模块
 * 数据模型类
 * 
 * 框架默认的 AccountRole 账号角色模型
 * 用于 通用账号系统
 * 各应用可以继承并扩展 此数据模型
 */

namespace Spf\module\uac\orm\model;

use Spf\module\orm\Model;

class AccountRole extends Model 
{
    /**
     * 数据模型(表) 的额外配置参数
     * 这些参数将在 数据库配置解析阶段，在 调用 Preparer 预处理之前 合并到 此数据模型的待解析配置数据中
     * !! 具体的 数据模型(表) 子类必须覆盖
     * !! 否则这些静态参数 会在多个 数据模型(表) 类中相互影响
     */
    public static $customConf = [
        //!! 参数结构 与 module/orm/config/DbConfig::$stdModel[] 一致
        //!! 可以使用 %{...}% 模板字符，可用的模板字符 在 module\orm\ModuleOrmConfig::processConf() 方法中查看
    ];

    /**
     * 此数据模型(表) 的 配置参数
     * !! 具体的 数据模型(表) 子类必须覆盖这些 静态参数
     * !! 否则这些静态参数 会在多个 数据模型(表) 类中相互影响
     */
    //数据模型(表) 所属 数据库实例  访问：NS\model\Foo::$db
    public static $db = null;
    //数据模型(表) 名称 foo_bar  访问：NS\model\Foo::$modk
    public static $modk = "account_role";
    //数据模型(表) 类名 FooBar  访问：NS\model\Foo::$modn
    public static $modn = "AccountRole";
    //数据模型(表) 配置参数 (object)[]  访问：NS\model\Foo::$config
    public static $config = null;
    //标记 此数据模型(表) 配置参数已经被解析并初始化
    public static $isConfed = false;

    
}