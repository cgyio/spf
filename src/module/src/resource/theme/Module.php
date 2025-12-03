<?php
/**
 * SPF-Theme 主题子模块 基类
 * 主题中包含各种模块，例如：颜色系统模块，尺寸系统模块 等
 * 应基于此类
 */

namespace Spf\module\src\resource\theme;

use Spf\module\src\Resource;
use Spf\module\src\Codex;
use Spf\module\src\SrcException;
use Spf\module\src\resource\Theme;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Path;
use Spf\util\Conv;
use Spf\util\Color;

class Module
{
    /**
     * 定义 此主题模块的 key
     * !! 子类必须覆盖
     */
    public $key = "";

    //此主题模块所在主题实例
    public $theme = null;

    /**
     * 定义此模块 在主题文件中的 标准参数数据格式
     * !! 子类必须覆盖
     */
    //此模块完整的 标准参数格式
    protected static $stdDef = [

        //必须定义 参数 item 的 分组形式，其中必须包含所有 item key
        "groups" => [
            //必须定义所有 $stdGroups 中包含的 分组名称
            /*
            "group_name" => [
                item_key, item_key, ...
            ],
            ...
            */
        ],

        /*
        # 为每个 item 定义的通用参数，与 $stdItem 中的结构一致，下方定义的所有 item 都应覆盖此通用参数
        "conf_key" => [ conf_value ],
        ...
        */

        //通用参数，各 mode 下的参数，应在此基础上覆盖
        "common" => [
            /*
            # 定义每个 item
            "item_key" => [
                # 与 $stdItem 结构一致
                "conf_key" => [ conf_value ],
                ...
            ],
            ...
            */
        ],

        //定义所有 mode 模式下的 参数
        "modes" => [
            /*
            # 定义 各 mode 模式下的 参数
            "mode_name" => [
                "item_key" => [
                    # 与 $stdItem 结构一致
                    "conf_key" => [ conf_value ],
                    ...
                ],
                ...
            ],
            ...
            */
        ],

        //定义主题模块的 额外数据定义
        "extra" => [
            //各子模块可以有不同的数据定义
            //...
        ],
    ];
    //此模块中，某个 参数 item 的 标准参数格式
    protected static $stdItem = [
        //必须包含 item 名称 key
        "key" => "",
        //必须包含 item 的初始值
        "value" => "",
        //必须包含 item 所在的参数分组
        "group" => "base",
        //别名，可以指向另一个 item key
        "alias" => null,
        //必须包含 编辑器参数
        "editor" => [
            //此 参数 item 的说明
            "desc" => "",
        ],
        //必须包含 dark 暗黑模式标记
        "dark" => false,
        /*
        # 其他 项目
        "conf_key" => [ conf_value ],
        ...
        */
    ];
    //此模块中，所有 参数 item 的分组类型
    protected static $stdGroups = [
        "base", "static", "custom", 
    ];
    //定义此模块支持的 mode 模式列表
    protected static $stdModes = [
        "light", "dark", //...
    ];
    //是否已与 Module 基类合并了 $stdXxxx 默认参数
    protected static $stdMerged = false;
    //定义此模块的 默认 mode 模式，不指定则使用 stdModes[0]
    protected static $dftMode = "";

    /**
     * 缓存 传入的 主题文件中的 此主题模块的 设置数据，已格式化为 stdDef 格式
     */
    protected $origin = [];

    /**
     * 此主题模块 在解析参数后 得到的 最终参数数据
     */
    protected $context = [
        /*
        # 最终得到的参数形式，按 mode 分别设定
        "mode_name" => [
            # 各 item 参数
            "item_name" => [
                # 与 $stdItem 一致的 参数结构
                "conf_key" => [ conf_value ],
                ...
            ],
            ...
        ],
        ...
        */
    ];

    //已解析 标记
    public $parsed = false;

    /**
     * 构造
     * @param Array $conf 传入主题文件中 关于此主题模块的 设置参数内容
     * @param Theme $theme 主题实例
     * @return void
     */
    final public function __construct($conf=[], $theme=null)
    {
        if (!$theme instanceof Theme) return null;
        if (!Is::nemarr($conf)) $conf = [];

        //先合并 当前模块 和 模块基类的 $stdFoobar 标准数据结构
        static::mergeStd();
        //使用 stdDef 格式化 $conf
        $conf = Arr::extend(static::$stdDef, $conf);
        //缓存
        $this->origin = $conf;
        $this->theme = $theme;
    }

    /**
     * 解析传入的 主题文件设置数据 写入 context
     * @return $this
     */
    final public function parse()
    {
        //只会解析一次
        if ($this->parsed === true) return $this;

        $conf = $this->origin;
        //解析 主题设置内容
        $ctx = static::parseStdDef($conf);

        //!! forDev
        //if ($this->key === "color") {
        //    var_dump($ctx["light"]["black"]);
        //    var_dump($ctx["dark"]["black"]);
        //}

        //开始执行 自动 shift 参数 item 值的 操作，如 颜色 加深|减淡 尺寸 增加|缩小
        $ctx = static::autoShift($ctx);

        //解析得到的 主题模块数据 写入 context
        $this->context = $ctx;
        //标记
        $this->parsed = true;
        return $this;
    }

    /**
     * 获取当前主题子模块的 dftMode 默认 mode 模式
     * @return String 
     */
    final public function getDftMode()
    {
        //预设支持的 modes 模式列表
        $modes = static::$stdModes;
        //预设的 dftMode
        $dftm = static::$dftMode;
        
        //未指定则使用 modes[0]
        if (!Is::nemstr($dftm)) return $modes[0];

        //指定了不合法的 mode
        if (!in_array($dftm, $modes)) return $modes[0];

        return $dftm;
    }

    /**
     * 此模块是否支持 给定的输出模式
     * @param String $mode
     * @return Bool
     */
    final public function supportMode($mode)
    {
        if (!Is::nemstr($mode)) return false;
        return in_array($mode, static::$stdModes);
    }

    /**
     * 按传入的 mode 获取 并 合并 context 中的 参数
     * !! 子类可以覆盖此方法
     * @param Array $modes 要获取的 mode 模式
     * @return Array 合并后的 包含所有 item 的 参数数组
     */
    public function getItemByMode(...$modes)
    {
        //需要解析
        $this->parse();

        //根据 请求的 mode 模式，提取对应的 主题参数，排在后面的 覆盖 前面的
        $ctx = $this->context;
        if (!Is::nemarr($ctx)) return [];
        //默认 mode 模式
        $dftmode = $this->getDftMode(); //static::$dftMode;
        $dftc = $ctx[$dftmode] ?? [];
        //获取 默认 mode 的 参数 value
        $rtn = $this->getItemsValue($dftc);
        foreach ($modes as $mode) {
            //如果传入的 mode 不存在，则使用 默认模式
            $modc = $ctx[$mode] ?? null;
            if (!Is::nemarr($modc)) continue;
            //调用获取 items 参数 value 的方法
            $itemsv = $this->getItemsValue($modc);
            //后指定的 mode 覆盖 先指定的
            $rtn = Arr::extend($rtn, $itemsv);
        }

        //处理 alias 别名
        $rtn = $this->getItemsAliasValue($rtn);

        //返回
        return $rtn;
    }

    /**
     * 按传入的 mode 获取此主题模块的 额外数据
     * !! 子类可以覆盖此方法
     * @param Array $modes 要获取的 mode 模式
     * @return Array 获取到的 此主题模块的 额外数据，各模块可能各不相同
     */
    public function getExtraByMode(...$modes)
    {
        return $this->origin["extra"] ?? [];
    }

    /**
     * 获取某个 mode 模式下 所有 items 的 value
     * !! 子类可以覆盖此方法
     * @param Array $items 要获取的 mode 模式下的 所有 items 参数
     * @return Array 返回获取到的 此 参数 item 的 value，通常是 item["value"] 的 值 
     */
    protected function getItemsValue($items)
    {
        /**
         * 通常情况下，直接返回此 mode 下所有 items 的 value 值
         * !! 如果有不同获取方法，应在子类中覆盖此方法
         */
        
        $rtn = [];
        foreach ($items as $item => $itemc) {
            //处理 别名 item
            $alias = $itemc["alias"] ?? null;
            if (Is::nemstr($alias)) {
                $rtn[$item] = $alias;
                continue;
            }

            //正常的 value
            $rtn[$item] = $itemc["value"] ?? null;
        }

        return $rtn;        
    }

    /**
     * 将处理后的 items 中的 别名 item 转换为指向的 item 的 值
     * !! 子类不要覆盖
     * @param Array $itemsv 已获取到的 items 的所有 value
     * @return Array 处理后的 items 的所有 value
     */
    final protected function getItemsAliasValue($items)
    {
        $rtn = [];
        foreach ($items as $item => $itemv) {
            //普通的 item value
            if (!Is::nemstr($itemv)) {
                $rtn[$item] = $itemv;
                continue;
            }

            //别名指向 不正确
            if (!isset($items[$itemv])) continue;
            //开始查找指向的 item
            $to = $items[$itemv];
            if (Is::nemstr($to) && isset($items[$to])) {
                //指向的 item 也是 别名，重复查找，直到 指向的 item 不是别名
                $to = $items[$to];
                while (Is::nemstr($to) && isset($items[$to])) {
                    $to = $items[$to];
                }
            }
            //读取 指向的 item value
            $rtn[$item] = $to;
        }

        return $rtn;
    }

    /**
     * 获取 当前主题模块中 所有 可用的 item
     * @return Array 包含所有 item key 的 一维数组
     */
    public function getAllItems()
    {
        $items = [];
        //所有分组类型
        $grks = static::$stdGroups;
        //分组数据
        $groups = $this->origin["groups"] ?? [];
        foreach ($grks as $grk) {
            $gitems = $groups[$grk] ?? [];
            if (!Is::nemarr($gitems) || !Is::indexed($gitems)) continue;
            //合并到 items
            $gs = array_merge([], array_diff($gitems, $items));
            $items = array_merge($items, $gs);
        }
        return $items;
    }

    /**
     * 获取 context 数据
     * @param Array $nctx 指定此数组，则使用此数组替换 context
     * @return Array context 数据
     */
    public function ctx($nctx=null) {
        if (Is::nemarr($nctx)) $this->context = $nctx;
        return $this->context;
    }

    /**
     * 获取 conf 模块参数内容
     * @return Array $this->origin
     */
    public function conf()
    {
        return $this->origin;
    }

    /**
     * 创建内部 临时 Codex 资源实例，通常用于生成内容行数组
     * @param String $ext 资源后缀名
     * @param Array $params 需要额外指定的 临时资源实例化参数
     * @return Codex 资源实例
     */
    public function tempCodex($ext, $params=[])
    {
        if (!Is::nemarr($params)) $params = [];
        $params = Arr::extend([
            "ext" => $ext,
            "belongTo" => null,
            "ignoreGet" => true,
            "import" => false,
            "export" => $ext,
        ], $params);

        return Resource::manual(
            "",
            "SPF_Theme_temp.".$ext,
            $params
        );
    }



    /**
     * 资源内容(代码行数组) 创建方法
     * !! 如果需要，模块子类可覆盖此方法
     * @param Array $ctx 要输出的 主题参数数组，通常来自于 $this->getItemByMode() 方法
     * @param String $ext 内容代码类型 scss|css|js 默认 scss
     * @param Array $params 需要额外指定的 临时资源实例化参数
     * @return Array 内容行数组
     */
    public function createContentRows($ctx=[], $ext="scss", $params=[])
    {
        //创建一个临时 Codex 资源
        $codex = $this->tempCodex($ext, $params);
        //准备行数组
        $rower = $codex->RowProcessor;
        $rower->clearRows();

        /**
         * 调用对应的 createExtContentRows 方法
         * 例如：createScssContentRows, createCssContentRows, ...
         * !! 子类必须实现这些方法
         */
        $m = "create".Str::camel($ext, true)."ContentRows";
        if (method_exists($this,$m)) {
            $this->$m($ctx, $rower);
            //输出 rows 行数组
            $rows = array_merge([], $codex->rows);
        } else {
            $rows = [];
        }

        //释放临时资源
        unset($codex);
        //返回生成的 rows 行数组
        return $rows;
    }

    /**
     * createExtContentRows
     * !! 子类必须实现
     * @param Array $ctx 要输出的 主题参数数组，通常来自于 $this->getItemByMode() 方法
     * @param RowProcessor $rower 临时资源的 内容行处理器
     * @return RowProcessor
     */
    //createScssContentRows
    protected function createScssContentRows($ctx=[], &$rower)
    {
        //子类实现...
        return $rower;
    }
    //createCssContentRows
    protected function createCssContentRows($ctx=[], &$rower)
    {
        //子类实现...
        return $rower;
    }
    //createJsContentRows
    protected function createJsContentRows($ctx=[], &$rower)
    {
        /**
         * 将 ctx 转为 json 并使用 parse 语句
         */
        $json = Conv::a2j($ctx);
        //js 变量名
        $jsv = $this->key;
        $rower->rowAdd("const $jsv = JSON.parse('".$json."');","");
        $rower->rowEmpty(1);

        return $rower;
    }



    /**
     * 工具方法
     */

    /**
     * 返回此主题模块 支持的 mode 输出模式列表 在 static::$stdModes 中定义
     * @return Array
     */
    public function supportedModes()
    {
        return static::$stdModes;
    }



    /**
     * 静态工具
     */

    /**
     * 合并当前主题模块的 $stdFoobar 标准数据结构 到 Module 基类的 数据结构中，更新此模块的 $stdFoobar
     * @return Bool
     */
    protected static function mergeStd()
    {
        //只需要 合并一次
        if (static::$stdMerged === true) return true;

        //合并当前模块类 和 Module 基类中的 stdXxxx 默认参数，参数中的 indexed 数组使用覆盖模式
        $stdDef = Arr::extend(Module::$stdDef, static::$stdDef, true);
        $stdItem = Arr::extend(Module::$stdItem, static::$stdItem, true);
        $stdGroups = Arr::extend(Module::$stdGroups, static::$stdGroups, true);
        $stdModes = Arr::extend(Module::$stdModes, static::$stdModes, true);
        //更新当前 主题模块的 $stdFoobar
        static::$stdDef = $stdDef;
        static::$stdItem = $stdItem;
        static::$stdGroups = $stdGroups;
        static::$stdModes = $stdModes;
        //标记
        static::$stdMerged = true;

        return true;
    }

    /**
     * 判断给定的 值 是否可以作为 当前模块的 参数 item 的 值
     * !! 子类必须覆盖
     * @param Mixed $val
     * @return Mixed|false 不是合法的 参数值 返回 false， 否则返回处理后的 val
     */
    public static function isItemValue($val)
    {
        //子类实现，不同的 主题模块系统，对参数值的 要求不同

        return $val;
    }

    /**
     * 判断一个字符串，是 合法的 参数 item 名称
     * !! 子类不要覆盖
     * @param String $key
     * @return Bool
     */
    final public static function isItemKey($key) 
    {
        //合法的 item key 与 php 变量名规则一致，同时还不能在 $stdItem 数组的键名中
        if (!Is::nemstr($key)) return false;
        if (in_array($key, array_keys(static::$stdItem))) return false;
        //合法的 php 变量名
        if (preg_match("/^[a-zA-Z0-9_]+$/", $key)) return true;
        return false;
    }



    /**
     * 主题子模块 stdXxxx 参数静态解析方法
     */

    /**
     * 解析 主题文件中 关于此主题模块的 设置参数数据
     * !! 如有需要，子类可以覆盖此方法
     * @param Array $conf 主题文件中 关于此主题模块的 设置参数数据
     * @return Array 返回解析得到的 context 数据
     */
    public static function parseStdDef($conf=[])
    {
        if (!Is::nemarr($conf)) return [];
        
        // 0 获取全部可用的 参数 item key
        $items = [];
        //所有分组类型
        $grks = static::$stdGroups;
        //分组数据
        $groups = $conf["groups"] ?? [];
        foreach ($grks as $grk) {
            $gitems = $groups[$grk] ?? [];
            if (!Is::nemarr($gitems) || !Is::indexed($gitems)) continue;
            //合并到 items
            $gs = array_merge([], array_diff($gitems, $items));
            $items = array_merge($items, $gs);
        }
        if (!Is::nemarr($items)) return [];
        //!!
        //var_dump($items);

        // 1 提取通用参数
        $pc = static::parseCommon($conf);
        $conf = $pc["items"] ?? [];
        //通用参数格式化
        $common = Arr::extend(static::$stdItem, $pc["common"] ?? []);
        //!!
        //var_dump($common);

        // 2 依次解析 $conf["modes"] 中 不同 mode 模式下的 参数 item 设置值，解析结果保存到 $res
        $cmitems = $conf["common"] ?? [];
        //!!
        //var_dump($cmitems);
        //定义的 modes 模式列表
        $modes = static::$stdModes;
        //传入参数中定义的 modes 
        $modecs = $conf["modes"] ?? [];
        $res = [];
        foreach ($modes as $mode) {
            //!!
            //var_dump($mode);

            //读取传入的 conf["modes"] 中定义的 mode 模式参数
            $modec = $modecs[$mode] ?? [];

            //!!
            //var_dump($modec);

            if (!Is::nemarr($modec)) {
                //mode 模式下未定义 item 参数，直接使用 conf["common"]
                $modc = Arr::copy($cmitems);
                //mode 模式下的 通用参数
                $modcm = Arr::copy($common);
            } else {
                //定义了 mode 模式下的 items 参数
                //需要先提取可能存在的 通用参数
                $pcm = static::parseCommon($modec);
                //mode 模式下的 items 设置参数 合并 通用 items 参数
                $modc = Arr::extend($cmitems, $pcm["items"] ?? []);
                //合并 原有的 通用参数
                $modcm = Arr::extend($common, $pcm["common"] ?? []);
            }

            //!!
            //var_dump($modc);
            //var_dump($modcm);

            //处理 mode 模式下的 额外 通用参数
            $modcm = Arr::extend($modcm, [
                //暗黑模式标记
                "dark" => $mode === "dark",
            ]);

            //依次解析 item
            $modres = [];
            foreach ($items as $item) {

                //仅在 groups 中定义了 item，在 common 和 当前 mode 下都未定义 此 item，跳过
                if (!isset($modc[$item])) continue;

                
                //!!
                //if (/*$this->key==="color" && */$item==="black") {
                //    var_dump($item);
                //    var_dump($modc[$item]);
                //}

                //处理 mode 模式下 item 的 通用参数
                $itemcm = [];
                //group
                $group = static::parseItemGroup($item, $groups);
                $itemcm["group"] = $group;
                //如果 所在 group 是 static 则 将 shift 设为不启用
                if ($group==="static" && isset(static::$stdItem["shift"])) {
                    $itemcm["shift"] = [
                        "on" => false
                    ];
                }
                //合并 modcm
                $itemcm = Arr::extend($modcm, $itemcm);
                
                //解析 item 参数
                $itemcv = static::parseStdItem($item, $modc[$item], $itemcm);
                //写入 modres
                if (Is::nemarr($itemcv)) {
                    if (!isset($modres[$item])) $modres[$item] = [];
                    $modres[$item] = Arr::extend($modres[$item], $itemcv);
                }
            }

            //写入 res
            $res[$mode] = $modres;
        }

        // 3 完成解析 返回 context 内容
        return $res;
    }

    /**
     * 解析 模块下某个 item 的参数，$stdItem 格式的参数
     * !! 如有需要，子类可以覆盖此方法
     * @param String $key item 名称 item_key
     * @param Array|String $conf 待解析的 item 参数，应与 $stdItem 格式一致
     * @param Array $common 之前已定义的 通用参数数组 应与 $stdItem 格式一致
     * @return Array|null 解析后的 符合 $stdItem 标准格式的 item 参数
     */
    public static function parseStdItem($key, $conf, $common=[])
    {
        //$stdItem 中的键名，不能作为 item key
        $ks = array_keys(static::$stdItem);
        if (in_array($key, $ks)) return null;

        //通用参数
        if (!Is::nemarr($common)) $common = [];

        //使用 简易设置，直接输入 value|alias 值
        if (!Is::nemarr($conf) || !Is::associate($conf)) {
            if (false !== ($val = static::isItemValue($conf))) {
                //输入的是 合法的 参数 item 的值
                $conf = [
                    "value" => $val
                ];
            } else if (static::isItemKey($conf) !== false && $conf !== $key) {
                //输入的是 另一个 item key
                $conf = [
                    "alias" => $conf
                ];
            } else {
                //输入不正确
                return null;
            }
        }

        //定义了 完整的参数形式 与 stdItem 格式一致
        if (Is::nemarr($conf)) {
            //格式化为 标准数据格式
            $conf = Arr::extend(static::$stdItem, $common, $conf, [
                //自动写入 item key
                "key" => $key,
            ]);
        }

        //返回 合并后的 颜色参数
        return Is::nemarr($conf) ? $conf : null;
    }

    /**
     * 从一组 items 参数中，提取可能存在的 通用参数
     * !! 子类不要覆盖
     * @param Array $items 一组 items 参数，通常定义在 某个 mode 下
     * @return Array 提取得到的 通用参数，以及剩余的 items 参数数组
     *  [
     *      "common" => [ 通用参数 ... ],
     *      "items" => [
     *          # 剩余的 items 参数
     *          "item_name" => [ ... ],
     *          ...
     *      ]
     *  ]
     */
    final public static function parseCommon($items=[])
    {
        if (!Is::nemarr($items)) return $items;
        $common = [];
        $nitems = [];
        //定义在 stdItem 中的参数项
        $cks = array_keys(static::$stdItem);
        foreach ($cks as $ck) {
            if (!isset($items[$ck])) continue;
            $common[$ck] = $items[$ck];
            //unset($items[$ck]);
        }
        foreach ($items as $ik => $ic) {
            if (in_array($ik, $cks)) continue;
            $nitems[$ik] = $ic;
        }
        return [
            "common" => $common,
            "items" => $nitems
        ];
    }

    /**
     * 解析 某个 参数 item 在哪个 group 中
     * !! 子类不要覆盖
     * @param String $key item key
     * @param Array $groups 预定义的 groups 数组
     * @return String group 名称，默认 base
     */
    final public static function parseItemGroup($key, $groups) 
    {
        //所有 group 分组名
        $grks = static::$stdGroups;
        foreach ($grks as $grk) {
            $gks = $groups[$grk] ?? [];
            if (!Is::nemarr($gks) || !Is::indexed($gks)) continue;
            if (in_array($key, $gks)) return $grk;
        }
        return $grks[0];
    }

    /**
     * 处理 参数 item 中的 别名，用别名指向的 item["value"] 替换 当前 item["value"]
     * @param Array $items 参数 item 数组 [ "item_name" => [ "value" => ..., ... ], ... ]
     * @return Array 替换别名指向后的 items 数组
     */
    final public static function parseAlias($items)
    {
        if (!Is::nemarr($items)) return $items;
        foreach ($items as $item => $iconf) {
            $alias = $iconf["alias"] ?? null;
            if (!Is::nemstr($alias) || !isset($items[$alias])) continue;
            $to = $items[$alias];
            $toval = $to["value"] ?? [];
            $items[$item]["value"] = $toval;
        }
        return $items;
    }



    /**
     * auto-shift 静态方法
     */

    /**
     * 对现有的主题参数 按 shift 参数要求，进行 自动 增减，如果 不存在 shift 参数则不做处理
     * !! 子类不要覆盖
     * @param Array $conf 当前的 主题模块参数，[ "mode_name" => [ "item_name" => [ item_conf ], ... ], ... ]
     * @return Array 处理后的 主题模块参数数组，自动生成的 参数值及其变体，都保存在 value 项下
     *  [
     *      "mode_name" => [
     *          "item_name" => [
     *              "value" => [
     *                  "m" => 原始值,
     *                  "d2 或 xxl" => 变体值,
     *                  ...
     *              ],
     *              其他 item 参数 ...
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     */
    final public static function autoShift($conf=[])
    {
        if (!Is::nemarr($conf)) return [];

        //当前 主题模块的 $stdItem 中 是否定义了 shift 参数
        $hasShift = isset(static::$stdItem["shift"]);

        //自动生成后的 
        $rtn = [];
        //依次处理 各 mode 模式下的 主题模块参数
        foreach ($conf as $mode => $items) {
            if (!Is::nemarr($items)) continue;
            $modeRtn = [];
            //依次处理此 mode 下的 各 参数 item
            foreach ($items as $item => $iconf) {
                if (!Is::nemarr($iconf)) continue;

                //存在 alias 别名的 或 不存在 shift 参数的 不处理
                $alias = $iconf["alias"] ?? null;
                if (Is::nemstr($alias) || !$hasShift) {
                    $modeRtn[$item] = $iconf;
                    continue;
                }
                
                //未启用 shift 参数的 仅将 value 改为 [ "m" => ... ]
                $shift = $iconf["shift"] ?? [];
                $on = $shift["on"] ?? true;
                if ($on !== true) {
                    $iconf["value"] = [
                        "m" => $iconf["value"]
                    ];
                    $modeRtn[$item] = $iconf;
                    continue;
                }

                //调用 子类的 自动 shift 方法
                $asv = static::autoShiftItem($item, $iconf);
                if (Is::nemarr($asv)) {
                    $modeRtn[$item] = $asv;
                } else {
                    //auto shift 方法没有返回正确结果，报错，因为可能主题参数有误
                    throw new SrcException("$mode 模式下的 $item 参数执行 auto-shift 方法没有得到正确结果", "resource/getcontent");
                }
            }
            //存入 rtn
            $rtn[$mode] = $modeRtn;
        }

        //返回
        return $rtn;
    }

    /**
     * 处理 某个 参数 item 的 auto shift
     * !! 需要 auto shift 的 主题模块子类，必须实现此方法
     * @param String $item 参数 item 名称 key
     * @param Array $conf item 设置参数
     * @return Array|null 处理后的 conf 所有 参数 item 的值（原始值|变体值）都保存在 value 项下
     */
    protected static function autoShiftItem($item, $conf)
    {
        /**
         * !! 此处不做处理，适用于 没有 shift 参数的 主题模块
         * 需要 auto shift 的主题模块，自行实现 处理逻辑
         */

        return $conf;
    }

    /**
     * 获取此模块的 auto-shift 级数，用于生成 ModnShiftQueue SCSS 参数
     * @param Array $modc 此主题模块的 参数数组
     * @return Int auto-shift 级数，如果此主题模块未开启 shift 参数，返回 0
     */
    protected static function autoShiftSteps($modc)
    {
        $stdItem = static::$stdItem;
        //是否开启 auto shift
        $hasShift = isset($stdItem["shift"]);
        if (!$hasShift) return 0;
        //检查 modc 中每个 item 的 值
        $steps = 0;
        foreach ($modc as $item => $itemv) {
            if (!Is::nemarr($itemv)) continue;
            $isteps = count($itemv);
            $isteps = ($isteps-1)/2;
            if ($isteps<0) {
                $isteps = 0;
            } else {
                $isteps = (int)$isteps;
            }
            if ($isteps>$steps) {
                $steps = $isteps;
            }
        }
        return $steps;
    }

}