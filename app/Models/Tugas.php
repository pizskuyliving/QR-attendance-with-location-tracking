<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = ['kelas_id', 'pertemuan', 'judul', 'deskripsi', 'tenggat', 'terima_terlambat'];

    protected function casts(): array
    {
        return ['tenggat' => 'datetime', 'terima_terlambat' => 'boolean'];
    }

    protected static function booted(): void
    {
        // hapus file kiriman mahasiswa ikut terhapus
        static::deleting(fn (Tugas $t) => $t->pengumpulan()->get()->each->delete());
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pengumpulan(): HasMany
    {
        return $this->hasMany(Pengumpulan::class);
    }

    public function lewatTenggat(): bool
    {
        return now()->greaterThan($this->tenggat);
    }

    /** Mahasiswa masih boleh mengirim / mengganti file? */
    public function bukaPengumpulan(): bool
    {
        return ! $this->lewatTenggat() || $this->terima_terlambat;
    }
}
