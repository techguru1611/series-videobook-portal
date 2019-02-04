<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'version' => env('STRIPE_VERSION'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),         // Your Google Client ID
        'iso_client_id' => env('GOOGLE_CLIENT_ID_IOS', ''),         // Your Google Client ID
        'android_client_id' => env('GOOGLE_CLIENT_ID_ANDROID', ''),         // Your Google Client ID
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''), // Your Google Client Secret
        'redirect' => env('GOOGLE_REDIRECT_URL', ''),       // Your Google Redirect URL
    ],

    'facebook_sdk' => [
        'app_id' => env('FACEBOOK_APP_ID', ''),                         // Your Facebook Client ID
        'app_secret' => env('FACEBOOK_APP_SECRET', ''),                 // Your Facebook Client Secret
        'default_graph_version' => env('FACEBOOK_GRAPH_VERSION', ''),   // Your Facebook Graph Version
    ],    

    'facebook' => [
        'client_id' => env('FACEBOOK_APP_ID', ''),          // Your Facebook Client ID
        'client_secret' => env('FACEBOOK_APP_SECRET', ''),  // Your Facebook Client Secret
        'redirect' => env('GOOGLE_REDIRECT_URL', ''),     // Your Facebook Redirect URL
    ],    

];
