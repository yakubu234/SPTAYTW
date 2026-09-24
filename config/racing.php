<?php
return [
    'provider' => env('RACING_PROVIDER', 'the-racing-api'),
    'api' => [
        'base_url' => env('RACING_API_BASE_URL', 'https://api.theracingapi.com/v1'),
        'username' => env('RACING_API_USERNAME'),
        'password' => env('RACING_API_PASSWORD'),
        'timeout' => 20,
    ],
    'enrichment' => [
        'history_limit' => 10,
        'history_days' => 730,
        'cache_hours' => 12,
    ],
    'thresholds' => [
        'minimum_data_quality' => 55,
        'qualified' => 72,
        'strong' => 82,
        'maximum_field_size' => 18,
    ],
];
