<?php
// Basic configuration loader using environment variables
// Defaults are safe for local development

$CONFIG = [
    'client_id' => getenv('BRIAPI_CLIENT_ID') ?: '',
    'client_secret' => getenv('BRIAPI_CLIENT_SECRET') ?: '',
    'base_url' => getenv('BRIAPI_BASE_URL') ?: 'https://sandbox.partner.api.bri.co.id',
    'mock_mode' => (getenv('MOCK_MODE') === '1' || strtolower((string) getenv('MOCK_MODE')) === 'true')
];

