<?php
/**
 * 框架 Src 资源处理模块 
 * Resource 资源类，要管理任意类型的文件资源，需要建立此类的实例
 */

namespace Spf\module\src;

abstract class Resource 
{
    /**
     * 资源参数
     */
    //extension 文件后缀名，支持的文件类型 在 Mime 类中定义
    public $ext = "";
}