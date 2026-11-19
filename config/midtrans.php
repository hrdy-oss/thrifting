<?php

$isProduction = filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Credentials
    |--------------------------------------------------------------------------
    |
    | Server Key and Client Key can be found in the Midtrans dashboard under
    | Settings > Access Keys. Keep the Server Key secret; it is only used
    | server-side to create transactions and verify notifications.
    |
    */

    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Keep this false while testing with the Midtrans Sandbox. Set it to true
    | only after switching to production access keys.
    |
    */

    'is_production' => $isProduction,

    'snap_url' => env(
        'MIDTRANS_SNAP_URL',
        $isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
    ),

    'api_url' => rtrim(env(
        'MIDTRANS_API_URL',
        $isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2',
    ), '/'),

    /*
    |--------------------------------------------------------------------------
    | Notification URL
    |--------------------------------------------------------------------------
    |
    | This is shown in the admin order screen so the URL can be pasted into
    | Midtrans Dashboard > Settings > Payment Notification URL.
    |
    */

    'notification_path' => '/midtrans/notification',

];
