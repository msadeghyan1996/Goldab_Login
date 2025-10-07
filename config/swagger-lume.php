<?php

return [
    'api' => [
        'title' => 'OTP Authentication API',
    ],

    'routes' => [
        'api' => 'docs',
        'docs' => 'api/documentation',
        'oauth2_callback' => 'api/oauth2-callback',
        'assets' => 'swagger-ui-assets',
        'middleware' => [
            'api' => [],
            'asset' => [],
            'docs' => [],
            'oauth2_callback' => [],
        ],
    ],

    'paths' => [
        'docs' => storage_path('api-docs'),
        'docs_json' => 'api-docs.json',
        'docs_yaml' => 'api-docs.yaml',
        'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
        'annotations' => base_path('app'),
        'excludes' => [],
        'base' => env('L5_SWAGGER_BASE_PATH', null),
    ],
    'security' => [
        'api_key_security' => [
            'type' => 'apiKey',
            'description' => 'A short description for security scheme',
            'name' => 'api_key',
            'in' => 'header',
        ],
    ],
    'generate_always' => env('SWAGGER_GENERATE_ALWAYS', false),
    'swagger_version' => env('SWAGGER_VERSION', '3.0'),
];
