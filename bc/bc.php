<?php

include_once 'Block.php';
include_once 'BlockChain.php';
include_once 'Transaction.php';
include_once 'User.php';
include_once 'values.php';

function NewChain($fileName, $receiver) {
    $file = fopen($fileName, 'w+');
    if(!$file) return null;
    fwrite($file, '{}');
    fflush($file);
    fclose($file);
    $chain = new BlockChain($fileName);
    $genesis = new Block(DIFFICULTY, GENESIS_BLOCK, $receiver, [], (new DateTime)->getTimestamp());
    $genesis->Mapping[base64_encode(STORAGE_CHAIN)] = STORAGE_VALUE;
    $genesis->Mapping[base64_encode($receiver)] = GENESIS_REWARD;
    $genesis->CurrHash = $genesis->hash();
    $chain->AddBlock($genesis);
    return null;
}

function LoadChain($fileName): ?BlockChain {
    if(!file_exists($fileName)) return null;
    $data = json_decode(file_get_contents($fileName),1);
    if(!is_array($data)) return null;
    return new BlockChain($fileName, count($data));
}

function NewBlock($miner, $prevHash): ?Block {
    return new Block(DIFFICULTY, $prevHash, $miner, [], (new DateTime)->getTimestamp());
}

function NewTransaction(User $u, $lastHash, string $to, int $value): ?Transaction {
    $tx = new Transaction(base64_encode(random_bytes(RAND_BYTES)), $lastHash, $u->address(), $to, $value);
    $tx->CurrHash = $tx->Hash();
    if($value > START_PERCENT) $tx->ToStorage = STORAGE_REWARD;
    $tx->Signature = base64_encode($tx->Sign($u->Private()));
    return $tx;
}

function HashSum($data): string {
    return hash('sha256',$data);
}

function Sign($priv, $data): string {
    openssl_sign($data,$s,$priv,OPENSSL_ALGO_SHA256);
    return $s;
}

function ProofOfWork($blockHash, $diff): int {
    $target = 1;
    $nonce = rand(0, PHP_INT_MAX);
    $target = $target << (64 - $diff);
    while($nonce < PHP_INT_MAX) {
        $hash = HashSum($blockHash . $nonce);
        printf("Mining: %s\r", base64_encode($hash));
        flush();
        if (strcmp($hash, $target) === -1) {
            echo "\n";
            return $nonce;
        }
        $nonce++;
    }
    return $nonce;
}

function Verify($pub,$data,$sign) {
    return openssl_verify($data,$sign,$pub,OPENSSL_ALGO_SHA256);
}

function ParsePubKey($data): string {
    return base64_decode($data);
}

function StringPrivate($priv): string {
    return base64_encode($priv);
}

function parsePrivate($str) {
    return openssl_pkey_get_private(base64_decode($str));
}

function NewUser() :User {
    return new User();
}

function LoadUser($purse) :User {
    return new User(openssl_pkey_get_private(base64_decode($purse)));
}