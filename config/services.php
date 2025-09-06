<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'elasticsearch' => [
        'enabled'  => env('ELASTICSEARCH_ENABLED', false),
        'host'     => env('ELASTICSEARCH_HOST', null),       // e.g. http://localhost:9200
        'username' => env('ELASTICSEARCH_USERNAME', null),   // optional
        'password' => env('ELASTICSEARCH_PASSWORD', null),   // optional
        'index'    => env('ELASTICSEARCH_INDEX', 'survey-submissions'),
        'timeout'  => env('ELASTICSEARCH_TIMEOUT', 3),       // seconds
    ],

];
