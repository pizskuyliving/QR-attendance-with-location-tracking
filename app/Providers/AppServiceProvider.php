<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // semua link & form ikut https bila APP_URL https (atau FORCE_HTTPS=true)
        if (str_starts_with(config('app.url'), 'https://') || config('app.force_https')) {
            URL::forceScheme('https');
        }

        // aturan kata sandi yang sama untuk semua form (daftar, ganti sandi, akun asisten)
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // login & daftar: dibatasi per email+IP, plus batas longgar per IP agar satu kelas di Wi-Fi kampus tidak ikut terblokir
        RateLimiter::for('masuk', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(120)->by($request->ip()),
        ]);
    }
}
