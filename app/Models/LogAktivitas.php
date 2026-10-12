<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jejak perubahan oleh asisten: siapa, kapan, mengubah apa. Hanya ditambah, tidak pernah diedit. */
class LogAktivitas extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'log_aktivitas';

    protected $fillable = ['user_id', 'kelas_id', 'aksi', 'keterangan', 'data', 'ip'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public static function catat(?Kelas $kelas, string $aksi, string $keterangan, ?array $data = null): self
    {
        return self::create([
            'user_id' => auth('web')->id(),
            'kelas_id' => $kelas?->id,
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'data' => $data,
            'ip' => request()->ip(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
