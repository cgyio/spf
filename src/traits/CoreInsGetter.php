<?php
/**
 * SPF 框架 可复用类特征
 * 为任意类添加 核心类实例 的快速获取能力
 * 快速获取：
 *      Runtime::$env|app|request|response
 *      
 * 在任意类内部：
 *      $this->App()        --> 获取 Runtime::$app|App::$current
 *          $this->App()->ModuleOrm     --> 获取当前会话的 Orm 模块实例
 *      $this->Mod()        --> Module::$modules[]
 *          $this->Mod('mod_name')      --> 获取模块实例
 *          $this->Mod('ModName')
 *      $this->Env()        --> Runtime::$env
 *      $this->Req()        --> Runtime::$request
 *      $this->Rep()        --> Runtime::$response
 * 
 *      $this->AppOk()      --> true
 *      $this->ModOk('mod_name')
 *      $this->ReqOk()      --> true
 *      ...
 * 
 *      $this->Appk()       --> app_name
 *      $this->Appn()       --> AppName
 *      $this->AppIsBase()  --> true or app_name
 *  
 */

namespace Spf\traits;

use Spf\Runtime;
//use Spf\Env;
//use Spf\App;
use Spf\Module;
//use Spf\Request;
//use Spf\Response;
use Spf\util\Is;
use Spf\util\Str;
use Spf\util\Arr;
use Spf\util\Cls;
use Spf\util\Path;

trait CoreInsGetter
{
    /**
     * 快速获取 核心类实例
     * !! 不存在则返回 null
     * @param String $clsn 核心类名 foo_bar 或 fooBar 形式
     * @return Core 核心类实例 
     */
    private function _getCoreIns($clsn)
    {
        $clsn = Str::snake($clsn, "_");
        if (!empty(Runtime::$$clsn)) return Runtime::$$clsn;
        $cls = Cls::find($clsn, "Spf");
        if (!class_exists($cls)) return null;
        if (!isset($cls::$isInsed) || $cls::$isInsed!==true) return null;
        return $cls::$current;
    }
    public function Env() { return $this->_getCoreIns("env"); }
    public function App() { return $this->_getCoreIns("app"); }
    public function Request() { return $this->_getCoreIns("request"); }
    public function Req() { return $this->Request(); }
    public function Response() { return $this->_getCoreIns("response"); }
    public function Rep() { return $this->Response(); }

    //获取 Module::$modules
    public function Module($modk=null)
    {
        //所有已实例化的 模块
        $mods = Module::all();
        if (!Is::nemstr($modk)) return $mods;
        //foo_bar
        $modk = Str::snake($modk, "_");
        if (!isset($mods[$modk])) return null;
        return $mods[$modk];
    }
    public function Mod($modk=null) { return $this->Module($modk); }

    /**
     * 快速判断核心类是否已经实例化
     * @return Bool
     */
    public function EnvOk() { return !is_null($this->Env()); }
    public function AppOk() { return !is_null($this->App()); }
    public function RequestOk() { return !is_null($this->Request()); }
    public function ReqOk() { return !is_null($this->Req()); }
    public function ResponseOk() { return !is_null($this->Response()); }
    public function RepOk() { return !is_null($this->Rep()); }
    public function ModuleOk($modk=null)
    {
        if (!Is::nemstr($modk)) return false;
        return !is_null($this->Module($modk));
    }
    public function ModOk($modk=null) { return $this->ModuleOk($modk); }

    //快速获取当前 App 应用名 foo_bar
    public function Appk() { return $this->AppOk() ? $this->App()::clsk() : null; }
    //快速获取当前 App 应用名 FooBar
    public function Appn() { return $this->AppOk() ? $this->App()::clsn() : null; }
    //快速判断是否 base_app 返回 true 或 appk 
    public function AppIsBase()
    {
        $appk = $this->Appk();
        if (is_null($appk) || $appk==="base_app") return true;
        return $appk;
    }

}