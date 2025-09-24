<?php
require_once __DIR__ . '/briapi.php';

// Ensure mock is off
putenv('MOCK_MODE=0');

global $CONFIG;

function out($label, $value) {
    echo $label, ': ', $value, PHP_EOL;
}

try {
    $token = briapi_get_access_token($CONFIG);
    out('Token OK, length', strlen($token));
} catch (Throwable $e) {
    fwrite(STDERR, 'Token error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

try {
    $banks = briapi_list_banks($CONFIG);
    out('Banks fetched', is_array($banks) ? count($banks) : 0);
    if (!empty($banks)) {
        $first = $banks[0];
        out('First bank', json_encode($first));
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'List banks error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "Live check completed." . PHP_EOL;

