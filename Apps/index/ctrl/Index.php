<?php


namespace dp\app\index\ctrl;
use dp\Ctrl as Ctrl;

class Index extends Ctrl {

    public function exec(){
        _export_($this->id);
    }

}