<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Pengumpulan extends Model
{
    protected $table = 'pengumpulan';

    protected $fillable = ['tugas_id', 'mahasiswa_id', 'path', 'nama_file', 'ukuran', 'catatan', 'dikumpulkan_at', 'terlambat', 'nilai', 'komentar', 'dinilai_at'];

    protected function casts(): array
    {
        return ['dikumpulkan_at' => 'datetime', 'dinilai_at' => 'datetime', 'terlambat' => 'boolean', 'nilai' => 'float'];
    }

    protected static function booted(): void
    {
        static::deleting(fn (Pengumpulan $p) => Storage::delete($p->path));
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function dinilai(): bool
    {
        return $this->nilai !== null;
    }
}
