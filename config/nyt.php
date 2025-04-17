<?php

return [
    'api_key' => env('NYT_API_KEY'),
    'base_url' => env('NYT_BASE_URL', 'https://api.nytimes.com/svc/books/v3'),
    'endpoints' => [
        'best_sellers_history' => '/lists/best-sellers/history.json',
    ],
    'cache_ttl' => env('NYT_CACHE_TTL', 3600), // Cache time in seconds (default 1 hour)
];