<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class used for notification testing. This should be the
    | fully qualified class name of your application's User model.
    |
    */
    'user_model' => env('NIGHTWATCH_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Internal Request Endpoints
    |--------------------------------------------------------------------------
    |
    | Configure the internal API endpoints to test. These endpoints will be
    | called during the internal request tests. You can customize these
    | based on your application's available routes.
    |
    */
    'internal_endpoints' => [
        // 2XX Success endpoints (should return 200-299)
        'success' => [
            ['method' => 'GET', 'path' => '/api/nightwatch-test/public', 'description' => 'Nightwatch public endpoint'],
        ],

        // 3XX Redirect endpoints (should return 300-399)
        'redirect' => [
            // Add your app's redirect endpoints here
        ],

        // 4XX Client error endpoints (should return 400-499)
        'client_error' => [
            ['method' => 'GET', 'path' => '/api/nightwatch-test/authenticated', 'expected_status' => [401, 403], 'description' => 'Unauthorized'],
            ['method' => 'GET', 'path' => '/api/nonexistent-endpoint-xyz', 'expected_status' => [404], 'description' => 'Not found'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authenticated Request Endpoints
    |--------------------------------------------------------------------------
    |
    | Configure the authenticated API endpoints to test. These endpoints will
    | be called with an API bearer token (Sanctum/Passport) during authenticated
    | request tests. The first user in the database will be used to generate the token.
    |
    */
    'authenticated_endpoints' => [
        ['method' => 'GET', 'path' => '/api/nightwatch-test/authenticated', 'label' => 'Authenticated 2XX (GET /api/nightwatch-test/authenticated)'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Recipients
    |--------------------------------------------------------------------------
    |
    | Email addresses used for mail testing. These don't need to be real
    | addresses as the mail driver should be set to 'log' or 'array' during testing.
    |
    */
    'mail' => [
        'to' => 'nightwatch-test@example.com',
        'cc' => 'nightwatch-cc@example.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Outgoing Request Test URL
    |--------------------------------------------------------------------------
    |
    | The base URL for testing outgoing HTTP requests. httpbin.org is used
    | by default as it provides endpoints that return specific status codes.
    |
    */
    'outgoing_request_url' => env('NIGHTWATCH_OUTGOING_URL', 'https://httpbin.org'),

    /*
    |--------------------------------------------------------------------------
    | Bulk Entry Generation
    |--------------------------------------------------------------------------
    |
    | Configuration for bulk entry generation features.
    |
    */
    'bulk' => [
        'max_count' => 1000,
        'slow_query_interval' => 5,
        'slow_query_delay' => 0.3,
    ],
];
