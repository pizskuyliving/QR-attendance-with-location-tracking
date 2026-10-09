<?php

namespace App\Services;

/**
 * Token QR dinamis tanpa database.
 *
 * Isi QR: "ABSEN.<officeId>.<window>.<signature>"
 *   - window    = floor(waktu_unix / periode)  -> berganti tiap 15 detik
 *   - signature = HMAC-SHA256(officeId|window, APP_KEY), dipotong 20 karakter
 *
 * Tanpa APP_KEY, token tidak bisa dipalsukan. Token lama otomatis
 * tidak berlaku setelah lewat masa tenggang.
 */
class QrTokenService
{
    private const PREFIX = 'ABSEN';

    public function period(): int
    {
        return max(5, (int) config('absen.qr_period', 15));
    }

    public function currentWindow(): int
    {
        return intdiv(time(), $this->period());
    }

    /** Detik tersisa sebelum QR berganti. */
    public function secondsLeft(): int
    {
        return $this->period() - (time() % $this->period());
    }

    public function make(int $officeId, ?int $window = null): string
    {
        $window ??= $this->currentWindow();

        return implode('.', [self::PREFIX, $officeId, $window, $this->sign($officeId, $window)]);
    }

    /**
     * Mengembalikan office_id kalau token sah dan masih berlaku, null kalau tidak.
     */
    public function verify(string $token): ?int
    {
        $parts = explode('.', trim($token));

        if (count($parts) !== 4 || $parts[0] !== self::PREFIX) {
            return null;
        }

        [, $officeId, $window, $signature] = $parts;

        if (! ctype_digit($officeId) || ! ctype_digit($window)) {
            return null;
        }

        $officeId = (int) $officeId;
        $window = (int) $window;
        $now = $this->currentWindow();
        $grace = max(0, (int) config('absen.qr_grace_windows', 1));

        // Hanya periode sekarang dan beberapa periode sebelumnya yang diterima.
        if ($window > $now || $window < $now - $grace) {
            return null;
        }

        if (! hash_equals($this->sign($officeId, $window), $signature)) {
            return null;
        }

        return $officeId;
    }

    private function sign(int $officeId, int $window): string
    {
        $key = config('app.key');

        return substr(hash_hmac('sha256', $officeId.'|'.$window, $key), 0, 20);
    }
}
