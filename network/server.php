<?php

set_time_limit(0);

include_once 'Package.php';
include_once 'values.php';

function Listen(string $address, $handle) :void {
    if(!$address = createAddr($address)) return;

    $socket = stream_socket_server($address);

    while ($conn = stream_socket_accept($socket)) {
        if(($pack = readPackage(fgets($conn, MAX_SIZE))) !== null)
            fwrite($conn, server . phpserialize($handle($pack)) . END_BYTES);
        fclose($conn);
    }

    fclose($socket);
}

function handleServer(Package $pack): ?Package {
    if(isset(HANDLERS[$pack->Option])) return new Package($pack->Option, HANDLERS[$pack->Option]($pack));
    return null;
}

function HandleToUpper(Package $pack) {
    return mb_strtolower($pack->Data);
}

function HandleToLower(Package $pack) {
    return mb_strtoupper($pack->Data);
}

Listen(ADDRESS, 'handleServer');