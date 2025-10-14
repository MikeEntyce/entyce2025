<?php

namespace VendorDuplicator\Dropbox;

// Don't redefine the functions if included multiple times.
if (!\function_exists('VendorDuplicator\\Dropbox\\GuzzleHttp\\uri_template')) {
    require __DIR__ . '/functions.php';
}
