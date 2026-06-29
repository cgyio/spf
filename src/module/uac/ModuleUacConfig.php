<?php
/**
 * 框架模块配置类
 * Uac 权限控制模块
 */

namespace Spf\module\uac;

use Spf\config\ModuleConfig;

class ModuleUacConfig extends ModuleConfig 
{
    /**
     * 预设的设置参数
     * !! 子类自定义
     */
    protected $init = [
        
        //此模块是否受 WEB_PAUSE 影响，默认 true，此模块下的操作方法可自行在 注释中覆盖此参数
        //"pause" => true,

        //此模块是否 仅 开发环境下 可用
        //"dev" => true,

        //依赖的 其他模块
        "dependency" => [
            //此模块依赖 Orm 模块
            "orm" => [
                //必须启用
                "enable" => true,
                //其他 orm 模块参数
                /**
                 * 默认不提供参数，当前应用的 参数中 必须开启 Orm 模块，并传入参数
                 * 如果此处 传入了 参数，则可以不在 应用参数中启用 Orm 模块
                 */
            ],
        ],

        //此模块必须依赖的 通用可扩展资源类
        "expandableResource" => [
            "dependency" => [
                //用户登录器 资源
                "module/uac/Loginner",
            ],
        ],

        /**
         * !! Uac 模块依赖 Orm 模块，且 必须明确指定 使用到的 权限数据库和对应的数据表
         * !! 如果不指定则使用默认定义的
         */
        "orm" => [
            //!! 数据库名称 必须是当前应用的 Orm 模块参数 dbs 中定义的 数据库名称 可以通过 $orm->db(dbn) 获取数据库实例的 dbn
            "dbn" => "account",     //默认使用 account 库名
            //!! 必须指定 必须的数据模型名 foo_bar 形式
            "model" => [
                //!! 这些数据模型结构，必须与框架指定的 account|role 模型结构一致
                //账号表
                "account"           => "account",
                //角色表
                "role"              => "role",
                //组织表
                "organize"          => "organize",
                //账号 iso 表
                "account_iso"       => "account_iso",
                //账号角色表
                "account_role"      => "account_role",
                //账号额外权限表
                "account_auth"      => "account_auth",
                //账号组织表
                "account_organize"  => "account_organize",
                //角色权限表
                "role_auth"         => "role_auth",
            ],
        ],

        /**
         * 权限 隔离参数
         * !! 如果 Uac 模块指向的 权限数据库 被多个 项目 或 应用 同时使用，
         * !! 必须在每个 App 应用内部的 Uac 参数中 明确指定 当前应用的 项目隔离ID
         * !! 此 iso 标记，将被自动添加到 权限数据库相关表的 prefilter 前置查询条件中
         * 
         * iso = isolate 通常的结构为：项目名称.应用名称，支持多个层级  foo.bar.jaz...
         */
        "iso" => "",

        /**
         * !! 可以额外指定，当前应用下的 获取 全部操作列表的 operations 接口的 url 前缀，默认 "operations"
         * 例如：当前应用 app_foo
         *      默认 operations 接口：      https://host/app_foo/api/operations/...
         *      设为 perms:                 https://host/app_foo/api/perms/...
         */
        "api" => "operations",

        /**
         * TODO: 待实现
         * 使用 外部的 统一权限中心 ServiceApp
         * !! 如果此项开启，则 前面的 orm 参数失效
         * !! 此处指定的 统一权限中心 ServiceApp 必须是标准的 Spf 框架 AuthServiceApp(微服务)
         * !! 实现了所有 外部权鉴接口
         * !! iso 参数依然是必须的
         */
        "service" => false,
        /*
        !! 标准结构
        "service" => [
            # ServiceApp 在当前应用中的 应用名
            "app" => "uni_auth",

            # ServiceApp 外部访问的 url 前缀
            "url" => "https:://auth.domain.com/uni_auth",

            # 要使用的 接口，默认使用全部可用接口
            "api" => [
                !! 应用上报  当前应用必须在初始化时，通过此接口，将自身应用id 以及 iso 上报到 ServiceApp
                "regist"    => "regist",

                # 登录登出
                "login"     => "login",
                "logout"    => "logout",

                # 权鉴
                "ac"        => "ac",

                # 查询 可用角色|可用权限
                "role"      => "get/role",
                "auth"      => "get/auth",

                # 更多 ...
            ],

            # iso 不指定则使用 上面定义的 iso
            "iso" => "",

            # 更多 ServiceApp 参数
            ...
        ],
        */

        //jwt 相关参数
        "jwt" => [
            //jwt-secret 文件保存路径，默认 app/%{APPK}%/library/jwt/secret/[base64(Request Audience)].json
            "secret" => "app/%{APPK}%/library/jwt/secret",

            //请求头中 jwt-token 保存在 字段名  默认 Authorization
            "header" => "Authorization",

            //其他参数
            //加密算法
            "alg" => "HS256",
            //token 过期时间 8h
            "expire" => 8*60*60,
            //默认 audience
            "dftAudience" => "public",
        ],

        //此模块必须的 中间件
        "middleware" => [
            //入站
            "in" => [
                "module/uac/middleware/in/authority_control",
            ],
            //出站
            "out" => [],

            //中间件配置参数
            //"middleware/orm_foo" => [
            //    "orm_foo_fooo" => 123,
            //    "orm_foo_barr" => 456
            //],
            //...
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