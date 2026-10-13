<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Mata kuliah milik dosen. Dosen membuat kelas-kelasnya dan memilih asisten pengajar tiap kelas. */
class Matkul extends Model
{
    protected $table = 'matkul';

    protected $fillable = ['dosen_id', 'kode', 'nama', 'semester'];

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class)->orderBy('nama_kelas');
    }

    public function label(): string
    {
        return trim(($this->kode ? $this->kode.' · ' : '').$this->nama);
    }
}
