<?php

namespace App\Support;

/**
 * Kode 6 angka berbasis waktu (TOTP, RFC 6238) yang dipakai Google Authenticator / Microsoft Authenticator.
 * Ditulis sendiri agar tidak perlu paket tambahan.
 */
class Totp
{
    public const PERIODE = 30;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function rahasiaBaru(): string
    {
        return self::base32(random_bytes(20));
    }

    public static function kode(string $rahasia, int $langkah): string
    {
        $hash = hash_hmac('sha1', pack('J', $langkah), self::dariBase32($rahasia), true);
        $o = ord($hash[19]) & 0x0F;
        $angka = ((ord($hash[$o]) & 0x7F) << 24) | (ord($hash[$o + 1]) << 16) | (ord($hash[$o + 2]) << 8) | ord($hash[$o + 3]);

        return str_pad((string) ($angka % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    public static function langkahSekarang(): int
    {
        return intdiv(time(), self::PERIODE);
    }

    /**
     * Cocokkan kode dengan toleransi ±1 periode (jam HP sedikit meleset).
     * Mengembalikan langkah yang cocok, atau null. $setelah mencegah kode yang sama dipakai dua kali.
     */
    public static function cocok(string $rahasia, string $kode, ?int $setelah = null): ?int
    {
        $kode = preg_replace('/\D/', '', $kode);
        if (strlen($kode) !== 6) {
            return null;
        }
        $kini = self::langkahSekarang();
        foreach ([$kini, $kini - 1, $kini + 1] as $langkah) {
            if (($setelah === null || $langkah > $setelah) && hash_equals(self::kode($rahasia, $langkah), $kode)) {
                return $langkah;
            }
        }

        return null;
    }

    /** Isi QR untuk dipindai aplikasi authenticator. */
    public static function uri(string $rahasia, string $akun, string $penerbit): string
    {
        $label = rawurlencode($penerbit).':'.rawurlencode($akun);

        return "otpauth://totp/{$label}?secret={$rahasia}&issuer=".rawurlencode($penerbit).'&algorithm=SHA1&digits=6&period='.self::PERIODE;
    }

    private static function base32(string $biner): string
    {
        $bit = '';
        foreach (str_split($biner) as $c) {
            $bit .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $hasil = '';
        foreach (str_split($bit, 5) as $potong) {
            $hasil .= self::BASE32[bindec(str_pad($potong, 5, '0'))];
        }

        return $hasil;
    }

    private static function dariBase32(string $teks): string
    {
        $bit = '';
        foreach (str_split(strtoupper(rtrim($teks, '='))) as $c) {
            $bit .= str_pad(decbin(strpos(self::BASE32, $c)), 5, '0', STR_PAD_LEFT);
        }
        $hasil = '';
        foreach (str_split($bit, 8) as $potong) {
            if (strlen($potong) === 8) {
                $hasil .= chr(bindec($potong));
            }
        }

        return $hasil;
    }
}
