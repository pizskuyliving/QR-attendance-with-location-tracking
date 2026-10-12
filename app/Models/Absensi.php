<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    public const STATUS = [
        'H' => 'Hadir',
        'I' => 'Izin',
        'S' => 'Sakit',
        'A' => 'Alpa',
    ];

    protected $table = 'absensi';

    protected $fillable = ['kelas_id', 'mahasiswa_id', 'pertemuan', 'status', 'metode', 'sesi_absen_id', 'waktu', 'lat', 'lng', 'akurasi', 'jarak', 'perangkat', 'tanda'];

    protected function casts(): array
    {
        return ['waktu' => 'datetime', 'tanda' => 'array'];
    }

    public function punyaLokasi(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    public function linkPeta(): ?string
    {
        return $this->punyaLokasi() ? 'https://www.google.com/maps?q='.$this->lat.','.$this->lng : null;
    }

    public function teksLokasi(): ?string
    {
        if (! $this->punyaLokasi()) {
            return null;
        }

        return round($this->lat, 6).', '.round($this->lng, 6).($this->akurasi ? ' (±'.$this->akurasi.' m)' : '');
    }

    public function dicurigai(): bool
    {
        return ! empty($this->tanda);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** Ubah teks bebas dari Excel (H, hadir, ✓, 1, v, ...) menjadi kode status. */
    public static function normalisasi(mixed $nilai): ?string
    {
        $v = mb_strtolower(trim((string) $nilai));
        if ($v === '' || $v === '-') {
            return null;
        }

        return match (true) {
            in_array($v, ['h', 'hadir', 'v', '✓', '✔', '1', 'x', 'y', 'ya', 'present', 'true'], true) => 'H',
            in_array($v, ['i', 'izin', 'ijin'], true) => 'I',
            in_array($v, ['s', 'sakit'], true) => 'S',
            in_array($v, ['a', 'alpa', 'alpha', 'alfa', '0', 'tidak hadir', 'absent', 'false'], true) => 'A',
            default => null,
        };
    }
}
