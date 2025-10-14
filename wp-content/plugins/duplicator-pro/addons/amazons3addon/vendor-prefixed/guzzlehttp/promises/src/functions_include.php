<?php

namespace VendorDuplicator;

// Don't redefine the functions if included multiple times.
if (!\function_exists('VendorDuplicator\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
