<?php
/*
 *  DommyFramework 视图
 *  省级联动选择
 */

namespace dp\view;
use dp\View as View;

$tpl_citypicker_main = <<<EOT
<select id="Citypicker_%{id}%_province" class="%{cssclass/province}%">
    <option value="_none_"%{dftselected/province}%>---请选择---</option>
    %{options/province}%
</select>
<select id="Citypicker_%{id}%_city" class="%{cssclass/city}%">
    <option value="_none_"%{dftselected/city}%>---请选择---</option>
    %{options/city}%
</select>
<select id="Citypicker_%{id}%_district" class="%{cssclass/district}%">
    <option value="_none_"%{dftselected/district}%>---请选择---</option>
    %{options/district}%
</select>
EOT;
$tpl_citypicker_option = <<<EOT
<option value="%{val}%"%{selected}%>%{text}%</option>
EOT;

class Citypicker extends View {

    public $tpl = [
        "main" => "",
        "option" => "",
    ];



    public function router_get(){
        _export_($this->tpl["main"],"html");
    }
}
