<?php

return [
    'api' => [
        'token-expires-in' => env('API_TOKEN_EXPIRES', 1440),
        'refresh-token-expires-in' => env('API_REFRESH_TOKEN_EXPIRES', 43200),
    ],
    'clients' => [
        'web' => [
            'id' => env('WEB_CLIENT_ID', 'Foo'),
            'secret' => env('WEB_CLIENT_SECRET', 'Bar'),
        ],
    ],
];
