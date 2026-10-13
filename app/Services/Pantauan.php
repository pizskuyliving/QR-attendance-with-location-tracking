<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Pengumpulan;

/**
 * Ringkasan satu kelas untuk dosen/admin: kemajuan pertemuan, kehadiran, dan kinerja asisten
 * (keteraturan sesi, materi, kecepatan menilai, perubahan absen manual).
 */
class Pantauan
{
    /** Tanpa sesi selama ini (hari) padahal pertemuan belum selesai = kelas dianggap macet. */
    public const HARI_MACET = 8;

    private const HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    public static function untuk(Kelas $kelas): array
    {
        $rekap = new Rekap($kelas);
        $n = (int) $kelas->jumlah_pertemuan;
        $terlaksanaP = array_keys(array_filter($rekap->perPertemuan, fn ($r) => $r['H'] + $r['I'] + $r['S'] + $r['A'] > 0));

        $sesi = $kelas->sesi()->get(['id', 'pertemuan', 'dibuka_at']);
        $sesiTerakhir = $sesi->max('dibuka_at');

        // hari jadwal diambil dari teks "hari" (mis. "Senin 08.00"); bila tak dikenali, cek ini dilewati
        $hariJadwal = collect(self::HARI)->search(fn ($h) => str_contains(strtolower(str_replace("'", '', (string) $kelas->hari)), $h));
        $luarJadwal = $hariJadwal === false ? null
            : $sesi->filter(fn ($s) => $s->dibuka_at->dayOfWeekIso - 1 !== $hariJadwal)->count();

        $pertemuanBermateri = $kelas->materi()->pluck('pertemuan')->unique();
        $tanpaMateri = count(array_diff($terlaksanaP, $pertemuanBermateri->all()));

        $kiriman = Pengumpulan::whereIn('tugas_id', $kelas->tugas()->select('id'))->get(['dikumpulkan_at', 'dinilai_at', 'nilai']);
        $dinilai = $kiriman->whereNotNull('dinilai_at');
        $lamaMenilai = $dinilai->isEmpty() ? null
            : round($dinilai->avg(fn ($p) => max(0, $p->dikumpulkan_at->diffInHours($p->dinilai_at))) / 24, 1);

        $terlaksana = count($terlaksanaP);
        $acuan = $sesiTerakhir ?? $kelas->created_at;
        $macet = $terlaksana < $n && $acuan && $acuan->lt(now()->subDays(self::HARI_MACET));

        $data = [
            'kelas' => $kelas,
            'rekap' => $rekap,
            'mahasiswa' => count($rekap->baris),
            'terlaksana' => $terlaksana,
            'rata' => $rekap->rataRataKehadiran(),
            'terancam' => count($rekap->terancam()),
            'sesiTerakhir' => $sesiTerakhir,
            'macet' => $macet,
            'luarJadwal' => $luarJadwal,
            'tanpaMateri' => $tanpaMateri,
            'belumDinilai' => $kiriman->whereNull('nilai')->count(),
            'lamaMenilai' => $lamaMenilai,
            'janggal' => Absensi::where('kelas_id', $kelas->id)->whereNotNull('tanda')->count(),
            'ubahManual' => LogAktivitas::where('kelas_id', $kelas->id)->whereIn('aksi', ['ubah_absen', 'isi_massal'])->count(),
        ];
        $data['perhatian'] = self::perhatian($data);

        return $data;
    }

    /** Hal yang perlu ditindaklanjuti dosen, dalam kalimat singkat. */
    private static function perhatian(array $d): array
    {
        return array_values(array_filter([
            $d['macet'] ? 'Tidak ada sesi absen lebih dari '.self::HARI_MACET.' hari' : null,
            $d['luarJadwal'] ? "{$d['luarJadwal']} sesi dibuka di luar hari jadwal" : null,
            $d['tanpaMateri'] ? "{$d['tanpaMateri']} pertemuan tanpa materi" : null,
            $d['belumDinilai'] ? "{$d['belumDinilai']} kiriman tugas belum dinilai" : null,
            $d['terancam'] ? "{$d['terancam']} mahasiswa mendekati/melewati batas kehadiran" : null,
        ]));
    }
}
