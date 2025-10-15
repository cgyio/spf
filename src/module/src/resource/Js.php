<?php
/**
 * Codex 类型资源类
 * 处理 JS 文件资源
 */

namespace Spf\module\src\resource;

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

class Js extends Codex 
{
    /**
     * 定义 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 覆盖父类
     */
    public static $stdParams = [

        /**
         * !! 无法通过 url 传递 的参数，只能在此资源实例化时 手动传入
         */
        //可在资源实例化时，指定当前的 Codex 资源是否属于某个 Compound 复合资源的 内部资源
        /*"belongTo" => null,
        //是否忽略 $_GET 参数
        "ignoreGet" => false,
        //可额外定义此资源的 中间件，这些额外的中间件 将在资源实例化时，附加到预先定义的中间件数组后面
        "middleware" => [
            //create 阶段
            "create" => [],
            //export 阶段
            "export" => [],
        ],
        //import 资源时 被导入资源的实例化参数，将与 dftImportParams 合并
        "importParams" => [],
        //合并资源时的 被合并资源的实例化参数，将与 dftMergeParams 合并
        "mergeParams" => [],*/

        /**
         * 其他参数
         */
        //可指定实际输出的 资源后缀名 可与实际资源的后缀名不一致，例如：scss 文件可指定输出为 css
        "export" => "js",
        //代码导入参数，true:处理导入(合并本地资源|补齐远程资源地址)，false:删除所有导入语句，'keep':导入语句保持原始形式
        //"import" => true,
        //代码合并参数，可指定要合并输出的 其他本地资源文件，文件名|文件路径，如果不带 ext 则使用 $this->ext
        //"merge" => [],

        //是否以 ESM 形式输出 js 资源，可选 true|false|'keep' 默认 keep 保持原始形式
        "esm" => "keep",
        /**
         * 当 esm===true 时，如果原始 js 代码中不包含 export 语句，将无法 自动生成导出语句，因为不知道要导出哪些内容
         * 因此，必须指定一个 默认导出变量名，此变量名必须在原始 js 代码中定义了
         * 如果不指定，则无法生成 esm 导出语句
         */
        "var" => "",
        
        //其他可选参数
        //...
    ];
    
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
            //import 处理
            "ImportProcessor" => [
                "stage" => "create"
            ],
            //条件执行 资源合并
            "MergeProcessor ?!empty(merge)" => [
                "stage" => "create"
            ],
            //esm 处理
            "EsmProcessor" => [
                "stage" => "create"
            ],
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],
            
            //使用 esm 处理器生成最终 content
            "EsmProcessor" => [
                "stage" => "export",
                //终止后续中间件
                "break" => true,
            ],
        ],
    ];

}