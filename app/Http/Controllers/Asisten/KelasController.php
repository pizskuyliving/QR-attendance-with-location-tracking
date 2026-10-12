<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Services\Rekap;
use App\Services\RekapNilai;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    public static function pastikanMilik(Request $request, Kelas $kelas): void
    {
        $u = $request->user();
        abort_unless($u->role === 'admin' || $kelas->asisten_id === $u->id, 403, 'Kelas ini bukan milik Anda.');
    }

    public function dashboard(Request $request)
    {
        $u = $request->user();
        $kelas = Kelas::withCount('mahasiswa')
            ->when($u->role !== 'admin', fn ($q) => $q->where('asisten_id', $u->id))
            ->with('asisten')
            ->orderBy('mata_kuliah')->orderBy('nama_kelas')
            ->get()
            ->map(function ($k) {
                $rekap = new Rekap($k);

                return [
                    'kelas' => $k,
                    'sesiAktif' => $k->sesiAktif(),
                    'terlaksana' => $rekap->pertemuanTerlaksana(),
                    'rata' => $rekap->rataRataKehadiran(),
                ];
            });

        return view('asisten.dashboard', compact('kelas'));
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request, null);
        $data['asisten_id'] = $request->user()->id;
        $kelas = Kelas::create($data);
        LogAktivitas::catat($kelas, 'tambah_kelas', 'Membuat kelas '.$kelas->label());

        return redirect()->route('asisten.kelas.show', $kelas)->with('ok', 'Kelas berhasil ditambahkan.');
    }

    public function show(Request $request, Kelas $kelas)
    {
        self::pastikanMilik($request, $kelas);
        $tab = $request->query('tab', 'absen');
        $rekap = new Rekap($kelas->load('mahasiswa', 'asisten'));
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
        $nilai = $tab === 'nilai' ? new RekapNilai($rekap) : null;

        return view('asisten.kelas', compact('kelas', 'tab', 'rekap', 'sesiAktif', 'riwayatSesi', 'riwayatScan', 'log', 'materi', 'daftarTugas', 'nilai'));
    }

    public function update(Request $request, Kelas $kelas)
    {
        self::pastikanMilik($request, $kelas);
        $data = $this->validasi($request, $kelas);
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
        self::pastikanMilik($request, $kelas);
        $data = $request->validate(['bobot_hadir' => 'required|integer|min:0|max:100']);
        $lama = "{$kelas->bobot_hadir}/{$kelas->bobot_tugas}";
        $kelas->update(['bobot_hadir' => $data['bobot_hadir'], 'bobot_tugas' => 100 - $data['bobot_hadir']]);
        LogAktivitas::catat($kelas, 'ubah_bobot', "Mengubah bobot nilai (hadir/tugas) {$lama} → {$kelas->bobot_hadir}/{$kelas->bobot_tugas}");

        return back()->with('ok', 'Bobot nilai disimpan.');
    }

    public function destroy(Request $request, Kelas $kelas)
    {
        self::pastikanMilik($request, $kelas);
        LogAktivitas::catat(null, 'hapus_kelas', 'Menghapus kelas '.$kelas->label().' beserta '.$kelas->absensi()->count().' data absen',
            ['kelas_id' => $kelas->id, 'label' => $kelas->label()]);
        $kelas->delete();

        return redirect()->route('asisten.dashboard')->with('ok', 'Kelas dihapus.');
    }

    private function validasi(Request $request, ?Kelas $kelas): array
    {
        // kelas A–G (config/absen.php); nama lama di luar daftar tetap boleh dipertahankan saat edit
        $pilihan = array_filter([...config('absen.kelas'), $kelas?->nama_kelas]);

        return $request->validate([
            'mata_kuliah' => 'required|string|max:100',
            'nama_kelas' => ['required', Rule::in($pilihan)],
            'hari' => 'nullable|string|max:30',
            'ruang' => 'nullable|string|max:30',
            'jumlah_pertemuan' => 'required|integer|min:1|max:16',
            'min_hadir' => 'sometimes|integer|min:0|max:100',
            // radius kampus (opsional): isi ketiganya atau kosongkan ketiganya
            'lokasi_lat' => 'nullable|required_with:lokasi_lng,radius|numeric|between:-90,90',
            'lokasi_lng' => 'nullable|required_with:lokasi_lat,radius|numeric|between:-180,180',
            'radius' => 'nullable|required_with:lokasi_lat,lokasi_lng|integer|min:20|max:5000',
        ]);
    }
}
