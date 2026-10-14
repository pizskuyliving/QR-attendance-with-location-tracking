<?php

namespace App\Models;

use App\Models\Concerns\Berkas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** File atau tautan yang dilampirkan asisten pada tugas (soal, template laporan, contoh, dll.). */
class TugasLampiran extends Model
{
    use Berkas;

    protected $table = 'tugas_lampiran';

    protected $fillable = ['tugas_id', 'judul', 'path', 'nama_file', 'ukuran', 'url'];

    protected static function booted(): void
    {
        static::deleting(fn (TugasLampiran $l) => $l->path && Storage::delete($l->path));
    }

    public function tugas(): BelongsTo
    {
        return $this->belongsTo(Tugas::class);
    }
}
