<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = ['asisten_id', 'asisten2_id', 'matkul_id', 'nama_kelas', 'mata_kuliah', 'hari', 'ruang', 'jumlah_pertemuan', 'lokasi_lat', 'lokasi_lng', 'radius', 'bobot_hadir', 'bobot_tugas', 'bobot_uts', 'bobot_uas', 'min_hadir', 'tenggat_uts', 'tenggat_uas'];

    protected function casts(): array
    {
        return ['tenggat_uts' => 'datetime', 'tenggat_uas' => 'datetime'];
    }

    /** Batas unggah jawaban UTS/UAS (null = pengumpulan ditutup). */
    public function tenggatUjian(string $jenis): ?\Illuminate\Support\Carbon
    {
        return $this->{'tenggat_'.$jenis};
    }

    /** Mahasiswa masih boleh mengunggah jawaban ujian ini? */
    public function bukaJawaban(string $jenis): bool
    {
        $t = $this->tenggatUjian($jenis);

        return $t !== null && now()->lte($t);
    }

    public function asisten(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asisten_id');
    }

    /** Asisten kedua (opsional); aksesnya sama dengan asisten pertama. */
    public function asisten2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asisten2_id');
    }

    /** Semua asisten pengajar kelas ini (0–2 orang). */
    public function daftarAsisten(): Collection
    {
        return collect([$this->asisten, $this->asisten2])->filter()->values();
    }

    /** Apakah user ini salah satu asisten pengajar kelas. */
    public function diajarOleh(User $u): bool
    {
        return in_array($u->id, array_filter([$this->asisten_id, $this->asisten2_id]), true);
    }

    /** Kelas yang diajar user (sebagai Asisten 1 atau Asisten 2). */
    public function scopeDiajar(Builder $q, int $userId): Builder
    {
        return $q->where(fn ($w) => $w->where('asisten_id', $userId)->orWhere('asisten2_id', $userId));
    }

    public function matkul(): BelongsTo
    {
        return $this->belongsTo(Matkul::class);
    }

    public function mahasiswa(): BelongsToMany
    {
        return $this->belongsToMany(Mahasiswa::class, 'kelas_mahasiswa')->withTimestamps()->withPivot('nilai_uts', 'nilai_uas')->orderBy('npm');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function sesi(): HasMany
    {
        return $this->hasMany(SesiAbsen::class);
    }

    public function sesiAktif(): ?SesiAbsen
    {
        return $this->sesi()->whereNull('ditutup_at')->latest('id')->first();
    }

    public function punyaRadius(): bool
    {
        return $this->lokasi_lat !== null && $this->lokasi_lng !== null && $this->radius;
    }

    /** Jarak (meter) dari titik kelas, rumus haversine. */
    public function jarakDari(float $lat, float $lng): ?int
    {
        if (! $this->punyaRadius()) {
            return null;
        }
        $r = 6371000;
        $dLat = deg2rad($lat - $this->lokasi_lat);
        $dLng = deg2rad($lng - $this->lokasi_lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($this->lokasi_lat)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public function materi(): HasMany
    {
        return $this->hasMany(Materi::class)->orderBy('pertemuan')->orderBy('id');
    }

    public function jawabanUjian(): HasMany
    {
        return $this->hasMany(JawabanUjian::class);
    }

    public function soalUjian(): HasMany
    {
        return $this->hasMany(SoalUjian::class)->orderBy('jenis', 'desc')->orderBy('id');
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class)->orderBy('pertemuan')->orderBy('tenggat');
    }

    protected static function booted(): void
    {
        // file materi, tugas, soal & jawaban ujian ikut dibersihkan saat kelas dihapus
        static::deleting(function (Kelas $k) {
            $k->materi()->get()->each->delete();
            $k->tugas()->get()->each->delete();
            $k->soalUjian()->get()->each->delete();
            $k->jawabanUjian()->get()->each->delete();
        });
    }

    public function catatanDosen(): HasMany
    {
        return $this->hasMany(CatatanDosen::class)->latest('id');
    }

    public function log(): HasMany
    {
        return $this->hasMany(LogAktivitas::class);
    }

    /** Berapa kali boleh tidak hadir agar kehadiran tetap >= min_hadir persen. */
    public function maksTidakHadir(): int
    {
        $n = (int) $this->jumlah_pertemuan;

        return max(0, $n - (int) ceil($n * ($this->min_hadir ?? 75) / 100));
    }

    /**
     * Siapa yang boleh mengatur jadwal, bobot, minimal hadir, dan menghapus kelas:
     * dosen pemilik matkul (atau admin). Kelas lama yang belum masuk matkul tetap diatur asistennya.
     */
    public function diaturOleh(User $u): bool
    {
        if ($u->role === 'admin') {
            return true;
        }

        return $this->matkul_id ? $this->matkul?->dosen_id === $u->id : $this->asisten_id === $u->id;
    }

    public function namaAsisten(): string
    {
        $nama = $this->daftarAsisten()->pluck('name');

        return $nama->isEmpty() ? 'Belum ada asisten' : $nama->implode(' & ');
    }

    public function label(): string
    {
        return $this->mata_kuliah.' — Kelas '.$this->nama_kelas;
    }
}
