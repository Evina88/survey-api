<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | Set the default guard to 'api' so auth() uses JWT with our Responder
    | unless otherwise specified. Password broker can stay 'users'.
    |
    */

    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | - 'web' uses sessions with the default User provider (if you need it).
    | - 'api' uses JWT with the Responder provider (tymon/jwt-auth).
    |
    */

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver'   => 'jwt',
            'provider' => 'responders',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Eloquent providers for both application user types:
    | - 'users'      => App\Models\User (optional, for web/console)
    | - 'responders' => App\Models\Responder (our JWT subjects)
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        'responders' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Responder::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Leave default password broker for 'users'. Responders don’t need
    | password reset flows for this assessment unless specified.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => 10800,

];
