<?php

namespace VendorDuplicator;

// Don't redefine the functions if included multiple times.
if (!\function_exists('VendorDuplicator\\GuzzleHttp\\uri_template')) {
    require __DIR__ . '/functions.php';
}
