<?php

include_once 'Package.php';
include_once 'values.php';

var_dump(($a = Send(ADDRESS, new Package(TO_UPPER, 'Hello, World!'))) !== null?$a->Data:null);
var_dump(($a = Send(ADDRESS, new Package(TO_LOWER, 'Hello, World!'))) !== null?$a->Data:null);

function Send(string $address, Package $pack): ?Package {
    if(!$address = createAddr($address)) return null;

    $data = '';
    $fp = stream_socket_client($address, $ec, $em,0.1);
    if(!$fp) return null;

    fwrite($fp, client . phpserialize($pack) . END_BYTES);

    while (!feof($fp)) $data = fgets($fp, MAX_SIZE);
    fclose($fp);

    return readPackage($data);
}