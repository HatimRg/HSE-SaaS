<?php

return [
    'name' => env('APP_NAME', 'HSE SaaS'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'fr',
    'fallback_locale' => 'en',
    'faker_locale' => 'fr_FR',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'providers' => [],
    'aliases' => [],
];
