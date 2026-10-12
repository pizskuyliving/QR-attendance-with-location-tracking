<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = ['asisten_id', 'nama_kelas', 'mata_kuliah', 'hari', 'ruang', 'jumlah_pertemuan', 'lokasi_lat', 'lokasi_lng', 'radius', 'bobot_hadir', 'bobot_tugas', 'min_hadir'];

    public function asisten(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asisten_id');
    }

    public function mahasiswa(): BelongsToMany
    {
        return $this->belongsToMany(Mahasiswa::class, 'kelas_mahasiswa')->withTimestamps()->orderBy('npm');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function sesi(): HasMany
    {
        return $this->hasMany(SesiAbsen::class);
    }

    public function sesiAktif(): ?SesiAbsen
    {
        return $this->sesi()->whereNull('ditutup_at')->latest('id')->first();
    }

    public function punyaRadius(): bool
    {
        return $this->lokasi_lat !== null && $this->lokasi_lng !== null && $this->radius;
    }

    /** Jarak (meter) dari titik kelas, rumus haversine. */
    public function jarakDari(float $lat, float $lng): ?int
    {
        if (! $this->punyaRadius()) {
            return null;
        }
        $r = 6371000;
        $dLat = deg2rad($lat - $this->lokasi_lat);
        $dLng = deg2rad($lng - $this->lokasi_lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->lokasi_lat)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class)->orderBy('pertemuan')->orderBy('id');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class)->orderBy('pertemuan')->orderBy('tenggat');
    }

    protected static function booted(): void
    {
        // file materi & tugas ikut dibersihkan saat kelas dihapus
        static::deleting(function (Kelas $k) {
            $k->materi()->get()->each->delete();
            $k->tugas()->get()->each->delete();
        });
    }

    public function log(): HasMany
    {
        return $this->hasMany(LogAktivitas::class);
    }

    /** Berapa kali boleh tidak hadir agar kehadiran tetap >= min_hadir persen. */
    public function maksTidakHadir(): int
    {
        $n = (int) $this->jumlah_pertemuan;

        return max(0, $n - (int) ceil($n * ($this->min_hadir ?? 75) / 100));
    }

    public function label(): string
    {
        return $this->mata_kuliah.' — Kelas '.$this->nama_kelas;
    }
}
