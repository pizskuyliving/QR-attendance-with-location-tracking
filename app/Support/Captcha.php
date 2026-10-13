<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Captcha form login & daftar: Cloudflare Turnstile, diverifikasi ke server Cloudflare. */
class Captcha
{
    /** @return array{site_key:?string,secret_key:?string}|null null bila belum dikonfigurasi */
    private static function key(): ?array
    {
        $key = config('captcha.turnstile');
        if (filled($key['site_key'] ?? null) && filled($key['secret_key'] ?? null)) {
            return $key;
        }

        return app()->environment('local', 'testing') ? config('captcha.uji') : null;
    }

    public static function siteKey(): ?string
    {
        return self::key()['site_key'] ?? null;
    }

    /** True bila memakai key uji Cloudflare (key asli belum diisi). */
    public static function modeUji(): bool
    {
        return self::siteKey() === config('captcha.uji.site_key');
    }

    /** Periksa captcha dari request form. Mengembalikan null bila lolos, atau pesan galat. */
    public static function periksa(Request $request): ?string
    {
        $key = self::key();
        if (! $key) {
            Log::error('TURNSTILE_SITE_KEY / TURNSTILE_SECRET_KEY belum diisi di .env');

            return 'Captcha belum dikonfigurasi oleh laboran. Login sementara tidak bisa dilakukan.';
        }

        $token = $request->input('cf-turnstile-response');
        if (! is_string($token) || $token === '') {
            return 'Verifikasi anti-bot belum selesai. Tunggu tanda centang muncul, lalu coba lagi.';
        }
        try {
            $res = Http::asForm()->timeout(8)->post(config('captcha.turnstile.verify_url'), [
                'secret' => $key['secret_key'],
                'response' => $token,
                'remoteip' => $request->ip(),
            ])->json();
        } catch (\Throwable $e) {
            Log::warning('Turnstile tidak bisa dihubungi', ['error' => $e->getMessage()]);

            return 'Layanan verifikasi anti-bot tidak bisa dihubungi. Coba lagi sebentar.';
        }
        if (! ($res['success'] ?? false)) {
            Log::info('Turnstile gagal', ['kode' => $res['error-codes'] ?? null]);

            return in_array('timeout-or-duplicate', $res['error-codes'] ?? [], true)
                ? 'Verifikasi anti-bot kedaluwarsa. Ulangi centang lalu kirim lagi.'
                : 'Verifikasi anti-bot gagal. Muat ulang halaman lalu coba lagi.';
        }

        return null;
    }
}
