<?php
// Basic configuration loader using environment variables
// Defaults are safe for local development

$CONFIG = [
    'client_id' => 'bzoZFq23SknHOWovbTMxPgZwAkGIxuCU',
    'client_secret' => 'xUyEaHFse52GewTP',
    'base_url' => 'https://sandbox.partner.api.bri.co.id',
    'mock_mode' => (getenv('MOCK_MODE') === '1' || strtolower((string) getenv('MOCK_MODE')) === 'true')
];

