<?php
/**
 * 框架核心类
 * View 视图类，基类
 */

namespace Spf;

use Spf\exception\BaseException;
use Spf\exception\AppException;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

class View extends Core 
{
    /**
     * 单例模式
     * !! 覆盖父类
     */
    public static $current = null;
    //此核心类已经实例化 标记
    public static $isInsed = false;
    //标记 是否可以同时实例化多个 此核心类的子类
    public static $multiSubInsed = true;

    //视图页面 文件路径
    public $page = "";



    /**
     * 此 View 视图类自有的 init 方法，执行以下操作：
     *  0   ...
     * !! Core 子类必须实现的，View 子类不要覆盖
     * @return $this
     */
    final public function initialize()
    {
        // 0 
        
        return $this;
    }



    /**
     * 静态方法
     */

    /**
     * 输出 page 页面
     * @param String $page 输出页面路径
     * @param Array $params 要传入页面内的 数据
     * @return String html
     */
    public static function page($page, $params=[])
    {
        if (!Is::nemstr($page)) return;
        $page = Path::find($page, Path::FIND_FILE);
        if (!file_exists($page)) return;
        //将 params 中 项目转为 定义的 变量
        if (!Is::nemarr($params)) $params = [];
        foreach ($params as $k => $v) {
            $$k = $v;
        }
        //require page
        require($page);
        //从 输出缓冲区 中获取内容
        $html = ob_get_contents();
        //清空缓冲区
        ob_clean();

        //返回 html
        return $html;
    }


}