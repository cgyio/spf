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
        ],*/
        //import 资源时 被导入资源的实例化参数，将与 dftImportParams 合并
        "importParams" => [],
        //合并资源时的 被合并资源的实例化参数，将与 dftMergeParams 合并
        "mergeParams" => [],

        /**
         * 其他参数
         */
        //可指定实际输出的 资源后缀名 可与实际资源的后缀名不一致，例如：scss 文件可指定输出为 css
        "export" => "",
        //代码导入参数，true:处理导入(合并本地资源|补齐远程资源地址)，false:删除所有导入语句，'keep':导入语句保持原始形式
        "import" => true,
        //代码合并参数，可指定要合并输出的 其他本地资源文件，文件名|文件路径，如果不带 ext 则使用 $this->ext
        "merge" => [],
        
    ];

    /**
     * 定义 import 此类型本地资源时，本地资源的默认实例化参数
     * !! 子类应覆盖此属性
     */
    public static $dftImportParams = [
        //忽略 $_GET
        "ignoreGet" => true,
        //默认不再执行 被 import 的本地资源内部的 import 语句
        "import" => false,
        //被 import 的本地资源 不继续合并其他资源
        "merge" => "",
    ];

    /**
     * 定义 合并资源时 被合并资源的默认实例化参数
     * !! 子类应覆盖此属性
     */
    public static $dftMergeParams = [
        //忽略 $_GET
        "ignoreGet" => true,
        //待合并的本地资源 必须处理其内部的 import 语句
        "import" => true,
        //待合并的本地资源 不继续合并其他资源
        "merge" => "",
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
            //import 处理 有条件执行
            "ImportProcessor" => [
                "stage" => "create"
            ],
            //条件执行 资源合并
            "MergeProcessor ?!empty(merge)" => [
                "stage" => "create"
            ],
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],
            
            //条件执行 合并生成 content
            "ImportProcessor ?import!=keep&empty(merge)" => [
                "stage" => "export",
                //终止后续中间件
                "break" => true,
            ],
            "RowProcessor ?import=keep&empty(merge)" => [
                "stage" => "export",
                "break" => true,
            ],
            "MergeProcessor ?!empty(merge)" => [
                "stage" => "export",
                "break" => true,
            ],
        ],
    ];

    

    /**
     * 资源输出的最后一步，echo
     * !! 覆盖父类
     * @param String $content 可单独指定最终输出的内容，不指定则使用 $this->content
     * @return Resource $this
     */
    protected function echoContent($content=null)
    {
        //!! 增加 min 判断，对 JS|CSS 执行压缩
        $content = $this->minify($content);

        //调用父类 echoContent 方法
        return parent::echoContent($content);
    }



    /**
     * 工具方法
     */

    /**
     * 判断是否 压缩输出
     * @return Bool
     */
    public function isMin()
    {
        $min = $this->params["min"] ?? false;
        return is_bool($min) ? $min : false;
    }

    /**
     * 压缩 JS/CSS
     * @param String $content 可以指定要压缩的代码内容
     * @return String 压缩后的 内容
     */
    public function minify($content=null)
    {
        //min 判断，对 JS|CSS 执行压缩
        if (!Is::nemstr($content)) {
            if ($this->isMin() === true) {
                //压缩 JS/CSS 文本
                $this->content = static::minifyCnt($this->content, $this->ext);
            }
            $content = $this->content;
        } else {
            if ($this->isMin() === true) {
                //压缩 JS/CSS 文本
                $content = static::minifyCnt($content, $this->ext);
            }
        }
        return $content;
    }



    /**
     * 静态工具
     */

    /**
     * 压缩指定的 字符串
     * @param String $cnt 要执行压缩的代码
     * @param String $ext 这段代码的 类型 js|css
     * @return String 压缩后的代码
     */
    public static function minifyCnt($cnt, $ext="js")
    {
        if (!Is::nemstr($cnt) || !Is::nemstr($ext)) return $cnt;
        $mcls = "MatthiasMullie\\Minify\\".strtoupper($ext);
        if (!class_exists($mcls)) return $cnt;

        //压缩
        $minifier = new $mcls();
        $minifier->add($cnt);
        $mcnt = $minifier->minify();
        return $mcnt;
    }
}