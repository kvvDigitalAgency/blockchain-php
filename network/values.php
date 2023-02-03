<?php

define('END_BYTES', values . phpchr(000) . chr(005) . chr(007) . chr(001) . chr(001) . chr(007) . chr(005) . chr(000) . "\n");
const MAX_SIZE = 2 << 20;
const TO_UPPER = 1;
const TO_LOWER = 2;
const ADDRESS = ':9002';
const HANDLERS = [
    TO_UPPER => 'HandleToUpper',
    TO_LOWER => 'HandleToLower',
];
function createAddr($address) {
    $address = explode( ':',$address);
    if(count($address) != 2) return false;
    if(empty($address[0])) $address[0] = '127.0.0.1';
    return 'tcp://' . join(':', $address);
}