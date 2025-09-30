<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Request ID propagation
    |--------------------------------------------------------------------------
    |
    | Toggle the middleware globally. When disabled the package skips all
    | processing and leaves responses untouched.
    |
    */

    'enabled' => env('TELESCOPE_REQUEST_ID_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Show in JSON responses
    |--------------------------------------------------------------------------
    |
    | When enabled, the request ID will be included in JSON responses.
    |
    */

    'show_in_json' => env('TELESCOPE_REQUEST_ID_IN_JSON', true),

    /*
    |--------------------------------------------------------------------------
    | JSON key
    |--------------------------------------------------------------------------
    |
    | The key that will be appended to JSON responses whenever possible.
    |
    */

    'key' => env('TELESCOPE_REQUEST_ID_KEY', 'request_id'),

    /*
    |--------------------------------------------------------------------------
    | Header name
    |--------------------------------------------------------------------------
    |
    | Header used to propagate the identifier between services.
    |
    */

    'header' => env('TELESCOPE_REQUEST_ID_HEADER', 'X-Request-Id'),


    /*
    |--------------------------------------------------------------------------
    | Apply Middleware To Routes
    |--------------------------------------------------------------------------
    |
    | Specify the routes that should have the request ID middleware applied.
    |
    */

    'routes' => ['api', 'web'],

    /*
    |--------------------------------------------------------------------------
    | Except URIs
    |--------------------------------------------------------------------------
    |
    | URIs that should not receive a request identifier. Patterns follow the
    | same semantics as \Illuminate\Http\Request::is().
    |
    */

    'except' => [
        // 'telescope*',
    ],
];
