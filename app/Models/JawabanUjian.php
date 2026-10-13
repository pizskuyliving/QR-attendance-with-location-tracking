<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class JawabanUjian extends Model
{
    protected $table = 'jawaban_ujian';

    protected $fillable = ['kelas_id', 'mahasiswa_id', 'jenis', 'path', 'nama_file', 'ukuran', 'catatan', 'komentar', 'dikumpulkan_at'];

    protected function casts(): array
    {
        return ['dikumpulkan_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::deleting(fn (JawabanUjian $j) => Storage::delete($j->path));
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
