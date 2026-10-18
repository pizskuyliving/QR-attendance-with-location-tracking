<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Services\Pantauan;
use App\Services\Rekap;
use App\Services\RekapNilai;
use App\Support\Bobot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    public static function pastikanMilik(Request $request, Kelas $kelas): void
    {
        $u = $request->user();
        abort_unless($u->role === 'admin' || $kelas->diajarOleh($u), 403, 'Kelas ini bukan milik Anda.');
    }

    /** Untuk akses baca saja (mis. unduh rekap): pemilik, admin, atau dosen matkul yang ditautkan. */
    public static function pastikanBisaLihat(Request $request, Kelas $kelas): void
    {
        $u = $request->user();
        $dosen = $u->role === 'dosen' && $kelas->matkul_id && $kelas->matkul?->dosen_id === $u->id;
        abort_unless($dosen || $u->role === 'admin' || $kelas->diajarOleh($u), 403, 'Kelas ini bukan milik Anda.');
    }

    public function dashboard(Request $request)
    {
        $u = $request->user();
        if ($u->role === 'dosen') {
            return redirect()->route('asisten.matkul.index');
        }
        $kelas = Kelas::withCount(['mahasiswa', 'catatanDosen as catatan_baru_count' => fn ($q) => $q->whereNull('dibaca_at')])
            ->when($u->role !== 'admin', fn ($q) => $q->diajar($u->id))
            ->with('asisten', 'asisten2', 'matkul.dosen')
            ->orderBy('mata_kuliah')->orderBy('nama_kelas')
            ->get()
            ->map(function ($k) {
                $hadir = Pantauan::hadir($k);

                return [
                    'kelas' => $k,
                    'sesiAktif' => $k->sesiAktif(),
                    'terlaksana' => count($hadir['pertemuan']),
                    'rata' => $hadir['rata'],
                ];
            });

        return view('asisten.dashboard', compact('kelas'));
    }

    public function show(Request $request, Kelas $kelas)
    {
        self::pastikanMilik($request, $kelas);
        $tab = $request->query('tab', 'absen');
        if ($tab === 'nilai') {
            $tab = 'rekap'; // tab Nilai sudah digabung ke Rekap & Nilai
        }
        // grid absen (tab Absen) butuh model lengkap (lokasi, tanda curiga); tab lain cukup status saja
        $rekap = new Rekap($kelas->load('mahasiswa', 'asisten', 'asisten2', 'matkul.dosen'), ringan: $tab !== 'absen');
        $sesiAktif = $kelas->sesiAktif();
        $riwayatSesi = $kelas->sesi()->latest('id')->withCount(['absensi' => fn ($q) => $q->where('metode', 'qr')])->limit(20)->get();
        $riwayatScan = $tab === 'riwayat'
            ? $kelas->absensi()->with('mahasiswa')->where('metode', 'qr')
                ->when($request->filled('p'), fn ($q) => $q->where('pertemuan', (int) $request->query('p')))
                ->when($request->boolean('curiga'), fn ($q) => $q->whereNotNull('tanda'))
                ->latest('waktu')->paginate(50)->withQueryString()
            : null;

        $log = $tab === 'log'
            ? $kelas->log()->with('user')
                ->when($request->query('aksi') === 'ditolak', fn ($q) => $q->where('aksi', 'scan_ditolak'))
                ->when($request->query('aksi') === 'asisten', fn ($q) => $q->whereNotIn('aksi', ['scan_ditolak', 'mhs_gabung', 'mhs_keluar']))
                ->when($request->query('aksi') === 'mahasiswa', fn ($q) => $q->whereIn('aksi', ['mhs_gabung', 'mhs_keluar']))
                ->latest('id')->paginate(50)->withQueryString()
            : null;

        $materi = $tab === 'materi' ? $kelas->materi()->get()->groupBy('pertemuan') : null;
        $daftarTugas = $tab === 'tugas' ? $kelas->tugas()->withCount(['pengumpulan', 'pengumpulan as dinilai_count' => fn ($q) => $q->whereNotNull('nilai')])->get() : null;
        $nilai = $tab === 'rekap' ? new RekapNilai($rekap) : null;
        $daftarKuis = $tab === 'kuis' ? $kelas->kuis()->withCount('jawaban')->withAvg('jawaban', 'nilai')->get() : null;
        $kuisTerpakai = $daftarKuis?->pluck('pertemuan')->map(fn ($p) => (int) $p)->all() ?? [];
        $soal = $tab === 'ujian' ? $kelas->soalUjian()->with('pengunggah')->get() : null;
        $jumlahJawaban = $tab === 'ujian' ? $kelas->jawabanUjian()->selectRaw('jenis, count(*) as n')->groupBy('jenis')->pluck('n', 'jenis') : null;

        // catatan dosen dibaca di menu Pesan; di sini cukup pengingat jumlah yang belum dibaca
        $pesanBaru = $kelas->diajarOleh($request->user()) ? $kelas->catatanDosen()->whereNull('dibaca_at')->count() : 0;

        return view('asisten.kelas', compact('kelas', 'tab', 'rekap', 'sesiAktif', 'riwayatSesi', 'riwayatScan', 'log', 'materi', 'daftarTugas', 'daftarKuis', 'kuisTerpakai', 'nilai', 'soal', 'jumlahJawaban', 'pesanBaru'));
    }

    public function update(Request $request, Kelas $kelas)
    {
        self::pastikanMilik($request, $kelas);
        // kelas di matkul dosen: asisten hanya mengatur radius lokasi; sisanya wewenang dosen
        self::radiusBawaan($request);
        $data = $kelas->diaturOleh($request->user()) ? self::validasi($request, $kelas) : $request->validate(self::ATURAN_LOKASI);
        $lama = $kelas->only(array_keys($data));
        $kelas->update($data);
        $berubah = array_filter($data, fn ($v, $k) => is_null($v) !== is_null($lama[$k]) || $v != $lama[$k], ARRAY_FILTER_USE_BOTH);
        if ($berubah) {
            LogAktivitas::catat($kelas, 'ubah_kelas', 'Mengubah pengaturan kelas: '.implode(', ', array_keys($berubah)),
                ['lama' => array_intersect_key($lama, $berubah), 'baru' => $berubah]);
        }

        return back()->with('ok', 'Data kelas diperbarui.');
    }

    public function bobot(Request $request, Kelas $kelas)
    {
        // bobot nilai boleh diatur asisten pengajar kelas maupun dosen pengampu
        self::pastikanMilik($request, $kelas);
        $data = Bobot::validasi($request);
        $lama = self::teksBobot($kelas);
        $kelas->update($data);
        LogAktivitas::catat($kelas, 'ubah_bobot', "{$request->user()->name} mengubah bobot nilai (hadir/tugas/kuis/UTS/UAS) {$lama} → ".self::teksBobot($kelas));

        return back()->with('ok', 'Bobot nilai disimpan.');
    }

    public static function teksBobot(Kelas $k): string
    {
        return "{$k->bobot_hadir}/{$k->bobot_tugas}/{$k->bobot_kuis}/{$k->bobot_uts}/{$k->bobot_uas}";
    }

    /** Simpan nilai UTS & UAS semua mahasiswa kelas sekaligus (kosong = belum ada nilai). */
    public function nilaiUjian(Request $request, Kelas $kelas)
    {
        self::pastikanMilik($request, $kelas);
        $data = $request->validate([
            'nilai_uts' => 'array', 'nilai_uts.*' => 'nullable|numeric|min:0|max:100',
            'nilai_uas' => 'array', 'nilai_uas.*' => 'nullable|numeric|min:0|max:100',
        ], [], ['nilai_uts.*' => 'nilai UTS', 'nilai_uas.*' => 'nilai UAS']);
        $berubah = 0;
        foreach ($kelas->mahasiswa as $m) {
            $baru = [];
            foreach (['nilai_uts', 'nilai_uas'] as $kolom) {
                if (array_key_exists($m->id, $data[$kolom] ?? [])) {
                    $v = $data[$kolom][$m->id];
                    $baru[$kolom] = $v === null || $v === '' ? null : round((float) $v, 2);
                }
            }
            $lama = ['nilai_uts' => $m->pivot->nilai_uts === null ? null : (float) $m->pivot->nilai_uts,
                'nilai_uas' => $m->pivot->nilai_uas === null ? null : (float) $m->pivot->nilai_uas];
            if ($baru && array_intersect_key($lama, $baru) != $baru) {
                $kelas->mahasiswa()->updateExistingPivot($m->id, $baru);
                $berubah++;
            }
        }
        if ($berubah) {
            LogAktivitas::catat($kelas, 'nilai_ujian', "{$request->user()->name} mengisi nilai UTS/UAS {$berubah} mahasiswa");
        }

        return back()->with('ok', $berubah ? "Nilai UTS/UAS {$berubah} mahasiswa disimpan." : 'Tidak ada nilai yang berubah.');
    }

    public function destroy(Request $request, Kelas $kelas)
    {
        self::pastikanMilik($request, $kelas);
        abort_unless($kelas->diaturOleh($request->user()), 403, 'Kelas ini hanya bisa dihapus dosen pengampu.');
        LogAktivitas::catat(null, 'hapus_kelas', 'Menghapus kelas '.$kelas->label().' beserta '.$kelas->absensi()->count().' data absen',
            ['kelas_id' => $kelas->id, 'label' => $kelas->label()]);
        $kelas->delete();

        return redirect()->route('asisten.dashboard')->with('ok', 'Kelas dihapus.');
    }

    public const ATURAN_LOKASI = [
        // radius kampus (opsional): isi ketiganya atau kosongkan ketiganya
        'lokasi_lat' => 'nullable|required_with:lokasi_lng,radius|numeric|between:-90,90',
        'lokasi_lng' => 'nullable|required_with:lokasi_lat,radius|numeric|between:-180,180',
        'radius' => 'nullable|required_with:lokasi_lat,lokasi_lng|integer|min:20|max:5000',
    ];

    public const RADIUS_BAWAAN = 100;

    /** Titik lokasi diisi tapi radius dikosongkan: pakai radius bawaan, jangan ditolak. */
    public static function radiusBawaan(Request $request): void
    {
        if ($request->filled(['lokasi_lat', 'lokasi_lng']) && ! $request->filled('radius')) {
            $request->merge(['radius' => self::RADIUS_BAWAAN]);
        }
    }

    /** Aturan isian kelas. Kelas di matkul dosen memakai nama matkul, jadi mata_kuliah tidak diisi manual. */
    public static function validasi(Request $request, ?Kelas $kelas, bool $denganMatkul = false): array
    {
        // kelas A–G (config/absen.php); nama lama di luar daftar tetap boleh dipertahankan saat edit
        $pilihan = array_filter([...config('absen.kelas'), $kelas?->nama_kelas]);
        $denganMatkul = $denganMatkul || $kelas?->matkul_id;
        self::radiusBawaan($request);

        return $request->validate([
            ...($denganMatkul ? [] : ['mata_kuliah' => 'required|string|max:100']),
            'nama_kelas' => ['required', Rule::in($pilihan)],
            'hari' => 'nullable|string|max:30',
            'ruang' => 'nullable|string|max:30',
            'jumlah_pertemuan' => 'required|integer|min:1|max:16',
            'min_hadir' => 'sometimes|integer|min:0|max:100',
            ...self::ATURAN_LOKASI,
        ]);
    }
}
