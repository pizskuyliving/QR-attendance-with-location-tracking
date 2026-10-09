<?php

use Laravel\Fortify\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Fortify Password Broker
    |--------------------------------------------------------------------------
    */

    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    */

    'username' => 'email',

    'email' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Lowercase Usernames
    |--------------------------------------------------------------------------
    */

    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    |
    | Setelah login, pegawai langsung diarahkan ke halaman absen.
    |
    */

    'home' => '/absen',

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    */

    'prefix' => '',

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        'passkeys' => 'passkeys',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    */

    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Passkeys
    |--------------------------------------------------------------------------
    */

    'passkeys' => [
        'relying_party_id' => parse_url(config('app.url'), PHP_URL_HOST),
        'allowed_origins' => [config('app.url')],
        'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', config('app.key')),
        'timeout' => 60000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | registration      -> MATI: akun pegawai dibuat oleh admin.
    | emailVerification -> MATI: tidak perlu verifikasi email.
    | Two-factor & passkeys tetap aktif (opsional bagi pengguna, dan
    | halaman bawaan starter kit membutuhkan route-nya).
    |
    */

    'features' => [
        // Features::registration(),
        Features::resetPasswords(),
        // Features::emailVerification(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
            'window' => 0,
        ]),
        Features::passkeys([
            'confirmPassword' => true,
        ]),
    ],

];
