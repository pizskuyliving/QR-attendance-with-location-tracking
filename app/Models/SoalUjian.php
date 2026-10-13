<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SoalUjian extends Model
{
    protected $table = 'soal_ujian';

    public const JENIS = ['uts' => 'UTS', 'uas' => 'UAS'];

    protected $fillable = ['kelas_id', 'jenis', 'judul', 'path', 'nama_file', 'ukuran', 'tampil_mahasiswa', 'user_id'];

    protected function casts(): array
    {
        return ['tampil_mahasiswa' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleting(fn (SoalUjian $s) => Storage::delete($s->path));
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function label(): string
    {
        return self::JENIS[$this->jenis] ?? strtoupper($this->jenis);
    }

    /** Chip jenis file, sama seperti materi. */
    public function jenisFile(): array
    {
        return (new Materi(['nama_file' => $this->nama_file, 'path' => $this->path]))->jenis();
    }
}
