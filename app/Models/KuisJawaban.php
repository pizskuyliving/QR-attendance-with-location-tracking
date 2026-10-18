<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KuisJawaban extends Model
{
    protected $table = 'kuis_jawaban';

    protected $fillable = ['kuis_id', 'mahasiswa_id', 'jawaban', 'benar', 'nilai', 'mulai_at', 'selesai_at', 'kirim_otomatis'];

    protected function casts(): array
    {
        return ['jawaban' => 'array', 'nilai' => 'float', 'mulai_at' => 'datetime', 'selesai_at' => 'datetime'];
    }

    public function kuis(): BelongsTo
    {
        return $this->belongsTo(Kuis::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
