<?php

namespace VendorDuplicator;

// Don't redefine the functions if included multiple times.
if (!\function_exists('VendorDuplicator\\GuzzleHttp\\Psr7\\str')) {
    require __DIR__ . '/functions.php';
}
