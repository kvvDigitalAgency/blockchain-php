<?php

class Block {
    public $CurrHash;
    public $PrevHash;
    public $Nonce;
    public $Difficulty;
    public $Miner;
    public $Signature;
    public $TimeStamp;
    public $Transactions;
    public $Mapping;

    public function __construct($Difficulty, $PrevHash, $Miner, $Mapping, $TimeStamp) {
        $this->Difficulty = $Difficulty;
        $this->PrevHash = $PrevHash;
        $this->Miner = base64_encode($Miner);
        $this->Mapping = [];
        $this->Transactions = [];
        $this->TimeStamp = $TimeStamp;
    }

    public function AddTransaction(BlockChain $chain, Transaction $tx): bool {
        if($tx->Value == 0) return false;
        if(count($this->Transactions) === TXS_LIMIT && $tx->Sender !== STORAGE_CHAIN) return false;
        $balanceTx = $tx->Value + $tx->ToStorage;
        if(isset($this->Mapping[base64_encode($tx->Sender)])) $balanceChain = $this->Mapping[base64_encode($tx->Sender)];
        else $balanceChain = $chain->Balance($tx->Sender);
        if($tx->Value > START_PERCENT && $tx->ToStorage !== STORAGE_REWARD) return false;
        if($balanceTx > $balanceChain) return false;
        $this->Mapping[base64_encode($tx->Sender)] = $balanceChain - $balanceTx;
        $this->addBalance($chain, $tx->Receiver, $tx->Value);
        $this->addBalance($chain, STORAGE_CHAIN, $tx->ToStorage);
        $this->Transactions[] = $tx;
        return true;
    }

    public function addBalance(BlockChain $chain, $receiver, $value) {
        if(isset($this->Mapping[base64_encode($receiver)])) $balanceChain = $this->Mapping[base64_encode($receiver)];
        else $balanceChain = $chain->Balance($receiver);
        $this->Mapping[base64_encode($receiver)] = $balanceChain + $value;
    }

    public function Accept(BlockChain $chain, User $u): bool {
        if(!$this->TransactionsIsValid($chain)) return false;
        if(!$this->AddTransaction($chain,new Transaction(base64_encode(random_bytes(RAND_BYTES)), '',STORAGE_CHAIN,$u->Address(),STORAGE_REWARD))) return false;
        $this->TimeStamp = (new DateTime)->getTimestamp();
        $this->CurrHash = $this->hash();
        $this->Signature = base64_encode($this->sign($u->Private()));
        $this->Nonce = $this->proof();
        return true;
    }

    private function TransactionsIsValid(BlockChain $chain) :bool {
        $lenTx = count($this->Transactions);
        $plusStorage = 0;
        $listHash = [];
        foreach($this->Transactions as $tx) {
            if ($tx->Sender === STORAGE_CHAIN) {
                if($plusStorage !== 0 || $tx->Receiver !== $this->Miner || $tx->Value !== STORAGE_REWARD) return false;
                $plusStorage = 1;
            } elseif(!$tx->HashIsValid() || !$tx->SignIsValid()) return false;
            if(isset($listHash[$tx->RandBytes]) || !$this->BalanceIsValid($chain, $tx->Sender) || !$this->BalanceIsValid($chain, $tx->Receiver)) return false;
            $listHash[$tx->RandBytes] = 1;
        }
        if($lenTx === 0 || $lenTx > TXS_LIMIT + $plusStorage) return false;
        return true;
    }

    public function hash(): string {
        $tmpHash = '';
        foreach($this->Transactions as $tx) $tmpHash .= $tx->CurrHash;
        ksort($this->Mapping);
        foreach ($this->Mapping as $h => $b) $tmpHash .= $h . $b;
        return HashSum($tmpHash . $this->Difficulty . $this->PrevHash . $this->Miner . $this->TimeStamp);
    }

    public function sign($priv): string {
        return Sign($priv, $this->CurrHash);
    }

    public function proof(): int {
        return ProofOfWork($this->CurrHash, $this->Difficulty);
    }

    public function BalanceIsValid(BlockChain $chain, $address): bool {
        if(!isset($this->Mapping[base64_encode($address)])) return false;
        $balanceChain = $chain->Balance($address);
        $bsb = $bab = 0;
        foreach($this->Transactions as $tx) {
            if($tx->Sender == $address) $bsb += $tx->Value + $tx->ToStorage;
            if($tx->Receiver == $address) {
                $bab += $tx->Value;
                if (STORAGE_CHAIN == $address) $bab += $tx->ToStorage;
            }
        }
        return $balanceChain + $bab - $bsb === $this->Mapping[base64_encode($address)];
    }

    public function IsValid(BlockChain $chain): bool {
        if($this->Difficulty !== DIFFICULTY || !$this->HashIsValid($chain, $chain->Size()) || !$this->SignIsValid() ||
            !$this->ProofIsValid() || !$this->MappingIsValid() || !$this->TimeIsValid($chain) || !$this->TransactionsIsValid($chain)) return  false;
        return true;
    }

    public function HashIsValid(BlockChain $chain, int $index): bool {
        return $this->hash() !== $this->CurrHash && $chain->validIndex($this->PrevHash, $index);
    }

    public function SignIsValid(): bool {
        return Verify(ParsePubKey($this->Miner), $this->CurrHash, base64_decode($this->Signature)) === 1;
    }

    public function ProofIsValid() {
        $target = 1;
        $target = $target << (64 - $this->Difficulty);
        return strcmp(HashSum($this->CurrHash . $this->Nonce), $target) === -1;
    }

    public function MappingIsValid(): bool {
        $list = [];
        foreach($this->Transactions as $tx) {
            $list[$tx->Sender] = 1;
            $list[$tx->Receiver] = 1;
        }
        foreach($this->Mapping as $addr => $b) if($addr !== STORAGE_CHAIN && !isset($list[$addr])) return false;
        return true;
    }

    public function TimeIsValid($chain): bool {
        if($this->TimeStamp - (new DateTime())->getTimestamp() > 0) return false;
        if($chain->getBlock($this->PrevHash)->TimeStamp - $this->TimeStamp > 0) return false;
        return true;
    }
}