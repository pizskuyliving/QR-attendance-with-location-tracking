<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Materi extends Model
{
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

    public function adalahFile(): bool
    {
        return $this->path !== null;
    }

    /** Label & warna chip jenis file: [teks, kelas CSS]. */
    public function jenis(): array
    {
        if (! $this->adalahFile()) {
            return str_contains((string) $this->url, 'youtu') ? ['VID', 'vid'] : ['URL', 'url'];
        }

        return match (strtolower(pathinfo($this->nama_file, PATHINFO_EXTENSION))) {
            'ppt', 'pptx' => ['PPT', 'ppt'],
            'pdf' => ['PDF', 'pdf'],
            'doc', 'docx' => ['DOC', 'doc'],
            'xls', 'xlsx', 'csv' => ['XLS', 'xls'],
            'zip', 'rar', '7z' => ['ZIP', 'zip'],
            'png', 'jpg', 'jpeg', 'gif', 'webp' => ['IMG', 'img'],
            default => ['FILE', 'zip'],
        };
    }

    public static function ukuranTeks(?int $byte): string
    {
        if (! $byte) {
            return '';
        }

        return $byte >= 1048576 ? round($byte / 1048576, 1).' MB' : max(1, round($byte / 1024)).' KB';
    }
}
