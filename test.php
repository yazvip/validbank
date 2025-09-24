<?php
// Basic smoke tests in mock mode
putenv('MOCK_MODE=1');

require_once __DIR__ . '/briapi.php';

global $CONFIG;

function assert_true($cond, $msg) {
    if (!$cond) {
        fwrite(STDERR, "FAIL: $msg\n");
        exit(1);
    }
}

// Test token in mock mode
$token = briapi_get_access_token($CONFIG);
assert_true($token === 'MOCK_ACCESS_TOKEN', 'Mock token should be returned');

// Test list banks mock
$banks = briapi_list_banks($CONFIG);
assert_true(is_array($banks) && count($banks) >= 1, 'Banks should be a non-empty array');
assert_true(isset($banks[0]['bankCode']), 'Bank item should have bankCode');

// Test validate account mock
$res = briapi_validate_account($CONFIG, '014', '1234567890');
assert_true(($res['http_code'] ?? 0) === 200, 'Validate account mock should return 200');
$body = json_decode($res['body'] ?? '', true);
assert_true(($body['data']['beneficiaryAccount'] ?? '') === '1234567890', 'Beneficiary account should echo input');

echo "All tests passed.\n";

