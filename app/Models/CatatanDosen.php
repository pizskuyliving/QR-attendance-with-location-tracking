<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Catatan dosen untuk asisten pengampu satu kelas. */
class CatatanDosen extends Model
{
    protected $table = 'catatan_dosen';

    protected $fillable = ['kelas_id', 'dosen_id', 'isi'];

    protected function casts(): array
    {
        return ['dibaca_at' => 'datetime'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }
}
