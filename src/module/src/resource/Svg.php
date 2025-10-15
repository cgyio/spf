<?php
/**
 * Plain 纯文本资源类 子类
 * 处理 Svg 类型本地文件
 */

namespace Spf\module\src\resource;

use Spf\module\src\Mime;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Conv;
use Spf\util\Path;

class Svg extends Plain 
{
    /**
     * 定义 纯文本文件 资源实例 可用的 params 参数规则
     * 参数项 => 默认值
     * !! 子类应覆盖此属性，定义自己的 params 参数规则
     */
    public static $stdParams = [
        //fill 修改 svg 中某些路径的 颜色
        "fill" => "",   // f00-f00--f00  -->  按路径顺序，分别指定颜色

        //pathes 输出 svg 的路径信息
        "pathes" => false,
        
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
            //拆分 path 数组
            "SplitPathes" => [],
        ],

        //资源实例 输出阶段 执行的中间件
        "export" => [
            //更新 资源的输出参数 params
            "UpdateParams" => [],

            //应用修改操作
            "ApplyModify" => [],

            //如果要输出 pathes
            "CreatePathesJson ?pathes=true" => [
                "break" => true,
            ],

            //正常输出
            "RowProcessor ?pathes=false" => [
                "stage" => "export",
                "break" => true,
            ],
        ],
    ];

    /**
     * 通用的 svg 代码头
     */
    public static $svgh = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
    public static $svgns = "<svg version=\"1.1\" xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" ";

    //此 svg 包含的 path 路径数组
    protected $pathes = [
        //"<path ... ></path>", 路径代码
    ];


    
    /**
     * 资源实例内部定义的 stage 处理方法
     * @param Array $params 方法额外参数
     * @return Bool 返回 false 则终止 当前阶段 后续的其他中间件执行
     */
    //SplitPathes 将 svg 代码按 path 路径拆分为数组，保存到 $this->pathes 属性
    public function stageSplitPathes($params=[])
    {
        $ctx = $this->content;
        $this->pathes = [];
        $pa1 = explode("<path", $ctx);
        $pa1 = array_slice($pa1, 1);
        foreach ($pa1 as $pai) {
            $pa = str_replace("</svg>","",$pai);
            $this->pathes[] = "<path".$pa;
        }

        return true;
    }
    //ApplyModify 根据传入的 params 参数，依次对 svg 代码执行处理，如：修改颜色 
    public function stageApplyModify($params=[])
    {
        foreach ($this->params as $k => $v) {
            //查找对应的 apply 方法
            $m = "apply".Str::camel($k, true);
            if (method_exists($this, $m)) {
                $this->$m();
            }
        }
        //修改后 更新 RowProcessor
        $rower = $this->RowProcessor;
        //清空
        $rower->clearRows();
        //重新执行 stageCreate
        $rower->callStageCreate();

        return true;
    }
    //CreatePathesJson 要输出 path 数组时，生成 json 数据
    public function stageCreatePathesJson($params=[])
    {
        if ($this->params["pathes"] !== true) return true;

        //调整输出文件格式为 json
        $this->setExt("json");
        
        //准备 content
        $this->content = Conv::a2j([
            "pathes" => $this->pathes
        ]);

        return true;
    }



    /**
     * 根据 params 处理 svg 代码
     * @return $this
     */
    //处理 fill 参数
    protected function applyFill()
    {
        $ps = $this->params;
        $fill = $ps["fill"] ?? "";
        if (Is::nemstr($fill)) $fill = explode("-", $fill);
        if (!Is::nemarr($fill) || !Is::indexed($fill)) return $this;
        $fln = count($fill);

        //当前 svg 代码
        $ctx = $this->content;

        //pathes 路径数组
        $pathes = $this->pathes;

        foreach ($pathes as $i => $path) {
            if ($fln>1 && $i>=$fln) continue;
            $fc = $fln===1 ? $fill[0] : $fill[$i];
            //$fc == "" 则跳过
            if ($fc=="" || $fc=="0") continue;
            //查找代码中的 fill="..." 片段，切换为指定的 fc 颜色
            $mts = [];
            $mt = preg_match("/(fill=\"\#?[0-9a-fA-F]*\")/", $path, $mts);
            if ($mt !== 1) {
                //没有 fill="..." 片段
                $npath = str_replace("></path>", "fill=\"#".$fc."\" ></path>", $path);
            } else {
                //找到代码片段，替换颜色
                //匹配的字符串
                $mti = array_slice($mts, 1)[0];
                $npath = str_replace($mti, "fill=\"#".$fc."\"", $path);
            }
            //写入替换后的 pathes
            $this->pathes[$i] = $npath;
            //替换 svg 代码
            $ctx = str_replace($path, $npath, $ctx);
        }

        //写入替换后的 content
        $this->content = $ctx;

        return $this;
    }



    /**
     * 静态方法
     */

    /**
     * 将图标库的 glyph["svg"] 转为标准的 svg 代码
     * @param String $glyph 通标库中 通过解析得到的 svg 代码 <svg id="" viewbox="">...</svg>
     * @return String 标准的 svg 代码格式 <?xml version="1.0" charset="utf-8"?><svg version="" xmlns="" xmlns:xlink="" ...>...</svg>
     */
    public static function glyphToSvg($glyph)
    {
        if (!Is::nemstr($glyph) || strpos($glyph, "<svg")===false) return null;
        $svg = [];
        $svg[] = static::$svgh;
        if (strpos($glyph, "<svg version=\"")===false) {
            $svg[] = preg_replace("/<svg\s+/", static::$svgns, $glyph);
        }
        return implode("", $svg);
    }
}