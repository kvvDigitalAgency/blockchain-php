<?php

class Package {
    public $Option;
    public $Data;
    public function __construct($Option, $Data) {
        $this->Option = $Option;
        $this->Data = $Data;
    }
}
function readPackage(string $data): ?Package {
    if(strpos($data, END_BYTES) && ($pack = unserialize(str_replace(END_BYTES, '', $data))) instanceof Package) return $pack;
    return null;
}