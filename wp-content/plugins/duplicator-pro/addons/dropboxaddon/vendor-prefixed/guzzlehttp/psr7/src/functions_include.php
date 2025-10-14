<?php

namespace VendorDuplicator\Dropbox;

// Don't redefine the functions if included multiple times.
if (!\function_exists('VendorDuplicator\\Dropbox\\GuzzleHttp\\Psr7\\str')) {
    require __DIR__ . '/functions.php';
}
