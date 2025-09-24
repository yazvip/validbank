<?php

require_once __DIR__ . '/config.php';

/**
 * Create BRIAPI signature
 */
function briapi_create_signature(string $resourcePath, string $httpVerb, string $accessToken, string $timestamp, string $body, string $clientSecret): string {
    $stringToSign = "path={$resourcePath}&verb={$httpVerb}&token={$accessToken}&timestamp={$timestamp}&body={$body}";
    return base64_encode(hash_hmac('sha256', $stringToSign, $clientSecret, true));
}

/**
 * Simple HTTP request wrapper
 * @return array{http_code:int, body:string}
 */
function briapi_http_request(string $url, string $method, array $headers = [], string $body = ''): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if (!empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http_code' => $httpCode, 'body' => (string) $response];
}

/**
 * Get access token (mock or real)
 */
function briapi_get_access_token(array $config): string {
    if (!empty($config['mock_mode'])) {
        return 'MOCK_ACCESS_TOKEN';
    }

    $clientId = (string) ($config['client_id'] ?? '');
    $clientSecret = (string) ($config['client_secret'] ?? '');
    $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');

    if ($clientId === '' || $clientSecret === '') {
        throw new RuntimeException('BRIAPI client credentials are not set. Please set BRIAPI_CLIENT_ID and BRIAPI_CLIENT_SECRET.');
    }

    $tokenUrl = $baseUrl . '/oauth/client_credential/accesstoken';
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
    ];
    $res = briapi_http_request($tokenUrl, 'POST', $headers, 'grant_type=client_credentials');
    if ($res['http_code'] !== 200) {
        throw new RuntimeException('Failed to obtain access token. HTTP ' . $res['http_code'] . ': ' . $res['body']);
    }
    $data = json_decode($res['body'], true);
    if (!is_array($data) || !isset($data['access_token'])) {
        throw new RuntimeException('No access_token in token response: ' . $res['body']);
    }
    return (string) $data['access_token'];
}

/**
 * Fetch list of external banks (mock or real)
 * @return array<int,array<string,string>>
 */
function briapi_list_banks(array $config): array {
    if (!empty($config['mock_mode'])) {
        return [
            ['bankCode' => '014', 'bankName' => 'BCA'],
            ['bankCode' => '008', 'bankName' => 'Mandiri'],
            ['bankCode' => '002', 'bankName' => 'BRI'],
        ];
    }

    $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
    $accessToken = briapi_get_access_token($config);

    $resourcePath = '/v1/transfer/external/accounts';
    $timestamp = gmdate("Y-m-d\TH:i:s.000\Z");
    $body = '';
    $signature = briapi_create_signature($resourcePath, 'GET', $accessToken, $timestamp, $body, (string) $config['client_secret']);

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'BRI-Timestamp: ' . $timestamp,
        'BRI-Signature: ' . $signature,
    ];

    $res = briapi_http_request($baseUrl . $resourcePath, 'GET', $headers);
    if ($res['http_code'] !== 200) {
        error_log('Failed to fetch bank list. HTTP ' . $res['http_code'] . ': ' . $res['body']);
        return [];
    }
    $data = json_decode($res['body'], true);
    if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
        return [];
    }
    return $data['data'];
}

/**
 * Validate account number with bank (mock or real)
 * @return array{http_code:int, body:string}
 */
function briapi_validate_account(array $config, string $bankCode, string $accountNumber): array {
    if (!empty($config['mock_mode'])) {
        $mock = [
            'responseCode' => '00',
            'responseDescription' => 'Success',
            'data' => [
                'bankCode' => $bankCode,
                'beneficiaryAccount' => $accountNumber,
                'beneficiaryName' => 'JANE DOE',
            ],
        ];
        return ['http_code' => 200, 'body' => json_encode($mock)];
    }

    $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
    $accessToken = briapi_get_access_token($config);

    $resourcePath = '/v2/transfer/external/accounts?bankcode=' . rawurlencode($bankCode) . '&beneficiaryaccount=' . rawurlencode($accountNumber);
    $timestamp = gmdate("Y-m-d\TH:i:s.000\Z");
    $body = '';
    $signature = briapi_create_signature($resourcePath, 'GET', $accessToken, $timestamp, $body, (string) $config['client_secret']);

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'BRI-Timestamp: ' . $timestamp,
        'BRI-Signature: ' . $signature,
    ];

    $res = briapi_http_request($baseUrl . $resourcePath, 'GET', $headers);
    return $res;
}

