<?php

class BlockChain {
    public $DB;
    public $index;
    public function __construct($file, int $index = 1) {
        $this->DB = $file;
        $this->index = $index;
    }
    public function AddBlock(Block $block): void {
        $this->index++;
        $data = $this->readDb();
        $hash = base64_encode($block->CurrHash);
        if(isset($data[$hash])) return;
        $data[$hash] = serialize($block);
        $file = fopen($this->DB, 'wb');
        fwrite($file, json_encode($data));
        fflush($file);
        fclose($file);
    }

    public function Balance($address) {
        $balance = 0;
        if(($data = $this->readDb()) === null) return null;
        $address = base64_encode($address);

        if(count($data)) {
            $block = end($data);
            do {
                if (isset(($blocku = unserialize($block))->Mapping[$address])) {
                    $balance = $blocku->Mapping[$address];
                    break;
                }
            } while($block = prev($data));
        }
        return $balance;
    }

    public function LastHash() {
        if(($data = $this->readDb()) === null) return null;
        end($data);
        if(end($data)) return base64_decode(key($data));
        return null;
    }

    public function Size(): int {
        return $this->index;
    }

    public function validIndex($hash, $index): bool {
        $data = $this->readDb();
        return $data[$hash] === end($data);
    }

    public function getBlock($hash) {
        $data = $this->readDb();
        return unserialize($data[$hash]);
    }
    public function readDb() {
        return json_decode(file_get_contents($this->DB),1);
    }
}