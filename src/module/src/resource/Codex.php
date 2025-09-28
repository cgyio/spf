<?php
/**
 * 框架 Src 资源处理模块
 * Resource 资源类 Codex 子类
 * 处理 框架支持直接 处理|合并|编译 的纯文本格式的 代码文件类型的 资源
 * 例如：js|css|scss|vue 等，在 Mime::$processable["codex"] 中定义了所有支持的 后缀名
 */

namespace Spf\module\src\resource;

use Spf\module\src\Resource;
use Spf\module\src\SrcException;
use Spf\Request;
use Spf\Response;
use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Arr;
use Spf\util\Str;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Url;

class Codex extends Resource
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    public static $stdParams = [
        //可指定实际输出的 资源后缀名 可与实际资源的后缀名不一致，例如：scss 文件可指定输出为 css
        "export" => "",
        
        //是否忽略 import 默认 false
        "noimport" => false,

        //代码合并参数
        //可指定要合并输出的 其他 文件
        "merge" => [],
        //合并资源时的 资源实例化参数，将与 dftMergeParams 合并
        "mergeps" => [
            //无法通过 url 传递，只能在资源实例化时 传入
        ],
    ];

    /**
     * 定义 import 此类型本地资源时，本地资源的默认实例化参数
     * !! 子类应覆盖此属性
     */
    public static $dftImportParams = [
        //默认不再执行 被 import 的本地资源内部的 import 语句
        "noimport" => true,
    ];

    /**
     * 定义 合并资源时 被合并资源的默认实例化参数
     * !! 子类应覆盖此属性
     */
    public static $dftMergeParams = [];
    
    /**
     * 针对此资源实例的 处理中间件
     * 需要严格按照先后顺序 定义处理中间件
     * !! 覆盖父类
     */
    public $middleware = [
        //资源实例 构建阶段 执行的中间件
        "create" => [
            //使用资源类的 stdParams 标准参数数组 填充 params
            "UpdateParams" => [],
            //获取 资源实例的 meta 数据
            "GetMeta" => [],
            //获取 资源的 content
            "GetContent" => [],
            //内容行处理器
            "RowProcessor" => [
                "stage" => "create"
            ],
            //import 处理 有条件执行
            "ImportProcessor ?noimport=false" => [
                "stage" => "create",
                "exit" => true
            ],
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],

            
            //内容行 合并生成 最终输出的 content
            "RowProcessor" => [
                "stage" => "export"
            ],
        ],
    ];
}