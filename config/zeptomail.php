<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZeptoMail API Key
    |--------------------------------------------------------------------------
    |
    | Your ZeptoMail API key (Send Mail token).
    | You can generate this from your ZeptoMail account settings.
    |
    */
    'api_key' => env('ZEPTOMAIL_API_KEY', env('ZEPTOMAIL_TOKEN')),

    /*
    |--------------------------------------------------------------------------
    | ZeptoMail Region/Endpoint
    |--------------------------------------------------------------------------
    |
    | Specify your ZeptoMail region or custom endpoint.
    | Supported regions: us, eu, in, au, jp, ca, sa, cn
    | Or provide a custom endpoint URL.
    |
    | Examples:
    | - 'region' => 'eu'                                    // Uses https://api.zeptomail.eu
    | - 'region' => 'us'                                    // Uses https://api.zeptomail.com
    | - 'endpoint' => 'https://custom.zeptomail.com'        // Custom endpoint
    |
    */
    'region' => env('ZEPTOMAIL_REGION', 'us'),

    /*
    |--------------------------------------------------------------------------
    | Custom Endpoint (Optional)
    |--------------------------------------------------------------------------
    |
    | If you need to use a custom endpoint, specify it here.
    | This will override the region setting.
    |
    */
    'endpoint' => env('ZEPTOMAIL_ENDPOINT'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | ZeptoMail API version to use.
    |
    */
    'api_version' => env('ZEPTOMAIL_API_VERSION', 'v1.1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for API response.
    |
    */
    'timeout' => env('ZEPTOMAIL_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Enable Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for debugging purposes.
    |
    */
    'logging' => env('ZEPTOMAIL_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | Region to Domain Mapping
    |--------------------------------------------------------------------------
    |
    | Internal mapping of regions to their corresponding domains.
    | You shouldn't need to modify this unless ZeptoMail adds new regions.
    |
    */
    'region_domains' => [
        'us' => 'com',
        'eu' => 'eu',
        'in' => 'in',
        'au' => 'com.au',
        'jp' => 'jp',
        'ca' => 'ca',
        'sa' => 'sa',
        'cn' => 'com.cn',
    ],
];
