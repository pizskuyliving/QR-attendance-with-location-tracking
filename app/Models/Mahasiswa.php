<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Mahasiswa extends Authenticatable
{
    protected $table = 'mahasiswa';

    protected $fillable = ['npm', 'nama', 'kelas', 'email', 'email_verified_at', 'password', 'kode_verifikasi', 'kode_kedaluwarsa', 'percobaan_kode', 'perangkat', 'perangkat_terikat_at'];

    protected $hidden = ['password', 'remember_token', 'kode_verifikasi', 'perangkat'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime', 'kode_kedaluwarsa' => 'datetime', 'perangkat_terikat_at' => 'datetime'];
    }

    public function terverifikasi(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function kelas(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_mahasiswa')->withTimestamps();
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }
}
