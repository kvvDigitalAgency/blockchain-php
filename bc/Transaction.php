<?php

class Transaction {
    public $RandBytes;
    public $PrevBlock;
    public $Sender;
    public $Receiver;
    public $Value;
    public $ToStorage;
    public $CurrHash;
    public $Signature;

    public function __construct($RandBytes, $PrevBlock, $Sender, $Receiver, $Value) {
        $this->RandBytes = $RandBytes;
        $this->PrevBlock = $PrevBlock;
        $this->Sender = $Sender;
        $this->Receiver = $Receiver;
        $this->Value = $Value;
        $this->ToStorage = 0;
    }

    public function Hash() {
        return HashSum(join('',[$this->RandBytes,$this->PrevBlock,
            $this->Sender,$this->Receiver,$this->Value,$this->ToStorage]));
    }

    public function sign($priv) {
         return Sign($priv, $this->CurrHash);
    }

    public function HashIsValid() {
        return $this->Hash() === $this->CurrHash;
    }

    public function SignIsValid() {
        return Verify($this->Sender, $this->CurrHash, base64_decode($this->Signature)) === 1;
    }
}