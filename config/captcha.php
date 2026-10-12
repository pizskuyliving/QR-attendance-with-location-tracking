<?php

return [
    /*
     * Captcha login & daftar memakai Cloudflare Turnstile.
     * Buat widget di dash.cloudflare.com > Turnstile, lalu isi kedua key di .env.
     * Bila key kosong dan APP_ENV=local, dipakai key uji Cloudflare (selalu lolos) agar bisa dicoba.
     * Di production key wajib diisi; tanpa key login ditolak.
     */
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ],

    // key uji resmi Cloudflare: widget selalu lolos, JANGAN dipakai di production
    'uji' => [
        'site_key' => '1x00000000000000000000AA',
        'secret_key' => '1x0000000000000000000000000000000AA',
    ],
];
