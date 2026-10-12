<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu sesi absen = QR yang dinyalakan asisten untuk satu pertemuan.
 * Isi QR berganti setiap PERIODE detik agar foto QR yang dibagikan cepat kedaluwarsa.
 */
class SesiAbsen extends Model
{
    public const PERIODE = 15;

    protected $table = 'sesi_absen';

    protected $fillable = ['kelas_id', 'pertemuan', 'secret', 'dibuka_at', 'ditutup_at'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['dibuka_at' => 'datetime', 'ditutup_at' => 'datetime'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function aktif(): bool
    {
        return $this->ditutup_at === null;
    }

    public static function jendelaSekarang(): int
    {
        return intdiv(time(), self::PERIODE);
    }

    public function tandaTangan(int $jendela): string
    {
        return strtoupper(substr(hash_hmac('sha256', $this->id.':'.$jendela, $this->secret.config('app.key')), 0, 16));
    }

    /** Teks yang dikodekan ke QR. */
    public function payload(?int $jendela = null): string
    {
        $jendela ??= self::jendelaSekarang();

        return 'ABSENQR-'.$this->id.'-'.$jendela.'-'.$this->tandaTangan($jendela);
    }

    /**
     * Cocokkan isi QR hasil scan. Mengembalikan sesi bila valid, atau string pesan galat.
     */
    public static function verifikasi(string $teks): self|string
    {
        $bagian = explode('-', strtoupper(trim($teks)));
        if (count($bagian) !== 4 || $bagian[0] !== 'ABSENQR') {
            return 'QR tidak dikenali. Pastikan kamu memindai QR absen dari layar asisten.';
        }
        [, $id, $jendela, $ttd] = $bagian;

        $sesi = self::find((int) $id);
        if (! $sesi) {
            return 'Sesi absen tidak ditemukan.';
        }
        if (! $sesi->aktif()) {
            return 'Sesi absen ini sudah diakhiri oleh asisten.';
        }
        $jendela = (int) $jendela;
        $sekarang = self::jendelaSekarang();
        // terima jendela saat ini dan satu sebelumnya (toleransi jeda scan)
        if ($jendela > $sekarang || $jendela < $sekarang - 1) {
            return 'QR sudah kedaluwarsa. Pindai ulang QR yang tampil di layar.';
        }
        if (! hash_equals($sesi->tandaTangan($jendela), $ttd)) {
            return 'QR tidak valid.';
        }

        return $sesi;
    }
}
