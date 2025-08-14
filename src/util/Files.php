<?php
/**
 * 框架特殊工具类
 * 处理 $_FILES
 */

namespace Spf\util;

class Files extends SpecialUtil
{
    /**
     * 此工具 在启动参数中的 参数定义
     *  [
     *      "util" => [
     *          "util_name" => [
     *              # 如需开启某个 特殊工具，设为 true
     *              "enable" => true|false, 是否启用
     *              ... 其他参数
     *          ],
     *      ]
     *  ]
     * !! 子类必须覆盖这些静态参数，否则不同的工具类会相互干扰
     */
    //此工具 在当前会话中的 启用标记
    public Static $enable = true;   //默认启用
    //缓存 框架启动参数中 针对此工具的参数
    protected static $initConf = [];



    //原始数据
    protected $origin = [];

    //处理后数据
    protected $context = [];

    /**
     * 构造
     * @return void
     */
    public function __construct()
    {
        $this->origin = $_FILES;
    }

    /**
     * 根据 上传表单 文件控件 name 获取上传文件
     * @param String $name
     * @return Array 可能上传多文件，统一返回 数组
     */
    public function name($name)
    {
        if (!Is::nemstr($name) || !isset($_FILES[$name])) return [];
        $fall = $_FILES[$name];
        $fs = [];
        if (Is::indexed($fall["name"])) {
            $ks = array_keys($fall);
            $ci = count($fall["name"]);
            for ($i=0;$i<$ci;$i++) {
                $fs[$i] = [];
                foreach ($ks as $ki => $k) {
                    $fs[$i][$k] = $fall[$k][$i];
                }
            }
        } else {
            $fs[] = $fall;
        }
        return $fs;
    }

    /**
     * __get
     * @param String $key 上传表单 文件控件 name
     * @return Array
     */
    public function __get($key)
    {
        $fs = $this->name($key);
        return $fs;
    }
}