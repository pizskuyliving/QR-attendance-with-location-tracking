<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'keterangan'])]
#[Hidden(['password', 'remember_token', 'dua_langkah_rahasia', 'dua_langkah_cadangan'])]
class User extends Authenticatable
{
    use Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dua_langkah_rahasia' => 'encrypted',
            'dua_langkah_cadangan' => 'encrypted:array',
            'dua_langkah_aktif_at' => 'datetime',
        ];
    }

    public function pakaiDuaLangkah(): bool
    {
        return $this->dua_langkah_aktif_at !== null && $this->dua_langkah_rahasia !== null;
    }

    /** Cek kode authenticator atau kode cadangan; kode yang sudah dipakai tidak bisa dipakai lagi. */
    public function cekKodeDuaLangkah(string $kode): bool
    {
        if (! $this->pakaiDuaLangkah()) {
            return false;
        }
        $langkah = \App\Support\Totp::cocok($this->dua_langkah_rahasia, $kode, $this->dua_langkah_terakhir);
        if ($langkah !== null) {
            $this->forceFill(['dua_langkah_terakhir' => $langkah])->save();

            return true;
        }
        // kode cadangan: XXXX-XXXX, sekali pakai
        $bersih = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $kode));
        foreach ($this->dua_langkah_cadangan ?? [] as $i => $hash) {
            if (strlen($bersih) === 8 && \Illuminate\Support\Facades\Hash::check($bersih, $hash)) {
                $sisa = $this->dua_langkah_cadangan;
                unset($sisa[$i]);
                $this->forceFill(['dua_langkah_cadangan' => array_values($sisa)])->save();

                return true;
            }
        }

        return false;
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'asisten_id');
    }

    /** Kelas tempat user menjadi Asisten 2. */
    public function kelasKedua(): HasMany
    {
        return $this->hasMany(Kelas::class, 'asisten2_id');
    }

    public function matkul(): HasMany
    {
        return $this->hasMany(Matkul::class, 'dosen_id');
    }

    public function peranLabel(): string
    {
        return ['admin' => 'Laboran', 'dosen' => 'Dosen', 'asisten' => 'Asisten dosen'][$this->role] ?? $this->role;
    }
}
