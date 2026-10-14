<?php

namespace App\Services;

use App\Mail\Pemberitahuan;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Tugas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email otomatis: peringatan batas kehadiran dan pengingat tugas H-1.
 * Dijalankan tiap jam oleh scheduler (routes/console.php). Setiap email dicatat di tabel
 * notifikasi agar tidak terkirim dua kali; yang gagal dikirim dicoba lagi di jam berikutnya.
 */
class Pengingat
{
    public int $terkirim = 0;

    public int $gagal = 0;

    public function kehadiran(): void
    {
        Kelas::with('mahasiswa')->get()->each(function (Kelas $kelas) {
            foreach ((new Rekap($kelas, ringan: true))->terancam() as $b) {
                $level = $b['kehadiran']->level();
                if (! in_array($level, ['batas', 'lewat'], true)) {
                    continue;
                }
                $k = $b['kehadiran'];
                $paragraf = $level === 'lewat'
                    ? ["Kamu sudah tidak hadir {$k->tidakHadir} kali di {$kelas->label()}, melebihi batas {$k->maks} kali. Kehadiranmu kini di bawah minimal {$k->minPersen}%.",
                        'Segera hubungi asisten atau dosen pengampu untuk menanyakan konsekuensinya, misalnya syarat mengikuti ujian.']
                    : ["Kamu sudah tidak hadir {$k->tidakHadir} kali di {$kelas->label()}. Itu batas maksimal agar kehadiran tetap minimal {$k->minPersen}%.",
                        'Satu kali lagi tidak hadir (termasuk izin atau sakit) akan membuat kehadiranmu di bawah batas. Pastikan hadir di sisa pertemuan.'];
                $this->kirim($b['mahasiswa'], "hadir:{$kelas->id}:{$level}", new Pemberitahuan(
                    $level === 'lewat' ? 'Kehadiran di bawah batas: '.$kelas->mata_kuliah : 'Peringatan kehadiran: '.$kelas->mata_kuliah,
                    $b['mahasiswa']->nama, $paragraf, route('mhs.matkul', $kelas), 'Lihat kehadiran', penting: true,
                ));
            }
        });
    }

    public function tugas(): void
    {
        Tugas::with('kelas.mahasiswa', 'pengumpulan:id,tugas_id,mahasiswa_id')
            ->whereBetween('tenggat', [now(), now()->addDay()])
            ->get()
            ->each(function (Tugas $t) {
                $sudah = $t->pengumpulan->pluck('mahasiswa_id')->flip();
                foreach ($t->kelas->mahasiswa as $m) {
                    if ($sudah->has($m->id)) {
                        continue;
                    }
                    $this->kirim($m, "tugas:{$t->id}", new Pemberitahuan(
                        'Pengingat tugas: '.$t->judul,
                        $m->nama,
                        ["Tugas \"{$t->judul}\" ({$t->kelas->label()}, pertemuan {$t->pertemuan}) belum kamu kumpulkan.",
                            'Tenggatnya '.$t->tenggat->translatedFormat('l, d F Y \p\u\k\u\l H:i').' ('.$t->tenggat->diffForHumans().').'
                            .($t->terima_terlambat ? ' Kiriman setelah tenggat masih diterima tetapi ditandai terlambat.' : ' Setelah tenggat, tugas tidak bisa dikirim lagi.')],
                        route('mhs.tugas', $t), 'Kumpulkan tugas',
                    ));
                }
            });
    }

    private function kirim(Mahasiswa $m, string $kunci, Pemberitahuan $email): void
    {
        if (! $m->email || ! $m->terverifikasi()
            || DB::table('notifikasi')->where(['mahasiswa_id' => $m->id, 'kunci' => $kunci])->exists()) {
            return;
        }
        try {
            Mail::to($m->email)->send($email);
            DB::table('notifikasi')->insert(['mahasiswa_id' => $m->id, 'kunci' => $kunci, 'dikirim_at' => now()]);
            $this->terkirim++;
        } catch (\Throwable $e) {
            $this->gagal++;
            Log::error('Gagal kirim pengingat', ['npm' => $m->npm, 'kunci' => $kunci, 'error' => $e->getMessage()]);
        }
    }
}
