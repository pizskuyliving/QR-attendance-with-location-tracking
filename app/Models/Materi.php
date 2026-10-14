<?php

namespace App\Models;

use App\Models\Concerns\Berkas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Materi extends Model
{
    use Berkas;

    protected $table = 'materi';

    protected $fillable = ['kelas_id', 'pertemuan', 'judul', 'path', 'nama_file', 'ukuran', 'url', 'user_id'];

    protected static function booted(): void
    {
        static::deleting(fn (Materi $m) => $m->path && Storage::delete($m->path));
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public static function ukuranTeks(?int $byte): string
    {
        if (! $byte) {
            return '';
        }

        return $byte >= 1048576 ? round($byte / 1048576, 1).' MB' : max(1, round($byte / 1024)).' KB';
    }
}
