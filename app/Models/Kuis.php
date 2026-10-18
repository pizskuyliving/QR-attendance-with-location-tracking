<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kuis refleksi di akhir pertemuan. Draf (dibuka_at null) → dibuka asisten dengan batas waktu → selesai (lewat ditutup_at).
 * Hanya mahasiswa yang berstatus Hadir di pertemuan itu yang boleh menjawab.
 */
class Kuis extends Model
{
    public const JUMLAH_SOAL = 5;

    public const PILIHAN = ['a' => 'A', 'b' => 'B', 'c' => 'C'];

    /** Jawaban yang masuk sedikit setelah waktu habis (sinyal lambat) masih diterima. */
    public const TOLERANSI_DETIK = 30;

    protected $table = 'kuis';

    protected $fillable = ['kelas_id', 'pertemuan', 'durasi_menit', 'dibuka_at', 'ditutup_at'];

    protected function casts(): array
    {
        return ['dibuka_at' => 'datetime', 'ditutup_at' => 'datetime'];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function soal(): HasMany
    {
        return $this->hasMany(KuisSoal::class)->orderBy('urutan');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(KuisJawaban::class);
    }

    public function draf(): bool
    {
        return $this->dibuka_at === null;
    }

    public function berjalan(): bool
    {
        return ! $this->draf() && now()->lt($this->ditutup_at);
    }

    public function selesai(): bool
    {
        return ! $this->draf() && now()->gte($this->ditutup_at);
    }

    /** Masih menerima jawaban (termasuk toleransi beberapa detik setelah waktu habis). */
    public function terimaJawaban(): bool
    {
        return ! $this->draf() && now()->lte($this->ditutup_at->copy()->addSeconds(self::TOLERANSI_DETIK));
    }

    /** [label, kelas badge] */
    public function status(): array
    {
        return match (true) {
            $this->draf() => ['Draf', 'grey'],
            $this->berjalan() => ['Sedang dibuka', 'I'],
            default => ['Selesai', 'H'],
        };
    }

    /** Boleh menjawab: tercatat Hadir di pertemuan kuis ini. */
    public function bolehDijawab(Mahasiswa $m): bool
    {
        return Absensi::where('kelas_id', $this->kelas_id)->where('pertemuan', $this->pertemuan)
            ->where('mahasiswa_id', $m->id)->where('status', 'H')->exists();
    }

    /** Urutan soal dan pilihan diacak per mahasiswa, tetap sama setiap kali halaman dibuka. */
    public function acakUntuk(Mahasiswa $m, $soal): array
    {
        $acak = new \Random\Randomizer(new \Random\Engine\Mt19937(crc32("kuis{$this->id}-{$m->id}")));

        return array_map(fn (KuisSoal $s) => ['soal' => $s, 'pilihan' => $acak->shuffleArray(array_keys(self::PILIHAN))],
            $acak->shuffleArray($soal->values()->all()));
    }

    /** Hitung jumlah benar & nilai 0–100 dari jawaban {soal_id: a|b|c}. */
    public function nilaiDari(?array $jawaban, $soal = null): array
    {
        $soal ??= $this->soal;
        $benar = $soal->filter(fn ($s) => ($jawaban[$s->id] ?? null) === $s->kunci)->count();

        return [$benar, $soal->count() ? round($benar / $soal->count() * 100, 2) : 0];
    }
}
