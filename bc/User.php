<?php

class User {
    public $PrivateKey;

    public function __construct($PrivateKey = null) {
        if(!$PrivateKey) $PrivateKey = openssl_pkey_new();
        $this->PrivateKey = $PrivateKey;
    }

    public function Address() :string {
        return openssl_pkey_get_details($this->PrivateKey)['key'];
    }

    public function Private() {
        openssl_pkey_export($this->PrivateKey, $key);
        return $key;
    }

    public function Public() {
        return $this->Address();
    }

    public function Purse() {
        return StringPrivate($this->Private());
    }
}