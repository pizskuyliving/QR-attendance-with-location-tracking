<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\SesiAbsen;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SesiController extends Controller
{
    public function buka(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $data = $request->validate(['pertemuan' => 'required|integer|min:1|max:'.$kelas->jumlah_pertemuan]);

        // hanya satu sesi aktif per kelas
        $kelas->sesi()->whereNull('ditutup_at')->update(['ditutup_at' => now()]);

        $sesi = $kelas->sesi()->create([
            'pertemuan' => $data['pertemuan'],
            'secret' => Str::random(40),
            'dibuka_at' => now(),
        ]);

        LogAktivitas::catat($kelas, 'buka_sesi', "Menyalakan QR absen pertemuan {$sesi->pertemuan}", ['sesi_id' => $sesi->id]);

        return redirect()->route('asisten.sesi.show', $sesi);
    }

    public function show(Request $request, SesiAbsen $sesi)
    {
        KelasController::pastikanMilik($request, $sesi->kelas);

        return view('asisten.sesi', ['sesi' => $sesi, 'kelas' => $sesi->kelas]);
    }

    /** Dipanggil berkala oleh layar QR: isi QR terbaru + daftar yang sudah hadir. */
    public function status(Request $request, SesiAbsen $sesi)
    {
        KelasController::pastikanMilik($request, $sesi->kelas);
        $kelas = $sesi->kelas;
        $hadir = Absensi::with('mahasiswa')
            ->where(['kelas_id' => $kelas->id, 'pertemuan' => $sesi->pertemuan, 'status' => 'H'])
            ->orderByDesc('waktu')->get();

        return response()->json([
            'aktif' => $sesi->aktif(),
            'payload' => $sesi->aktif() ? $sesi->payload() : null,
            'sisa_detik' => SesiAbsen::PERIODE - (time() % SesiAbsen::PERIODE),
            'jumlah_hadir' => $hadir->count(),
            'jumlah_mahasiswa' => $kelas->mahasiswa()->count(),
            'hadir' => $hadir->take(50)->map(fn ($a) => [
                'nama' => $a->mahasiswa->nama,
                'npm' => $a->mahasiswa->npm,
                'waktu' => $a->waktu?->format('H:i:s'),
                'lokasi' => $a->teksLokasi(),
                'peta' => $a->linkPeta(),
                'jarak' => $a->jarak,
                'tanda' => $a->tanda ?? [],
            ])->values(),
        ]);
    }

    public function akhiri(Request $request, SesiAbsen $sesi)
    {
        $kelas = $sesi->kelas;
        KelasController::pastikanMilik($request, $kelas);
        $sesi->update(['ditutup_at' => now()]);

        $pesan = "Sesi absen pertemuan {$sesi->pertemuan} diakhiri.";
        if ($request->boolean('tandai_alpa')) {
            $sudah = Absensi::where(['kelas_id' => $kelas->id, 'pertemuan' => $sesi->pertemuan])->pluck('mahasiswa_id');
            $belum = $kelas->mahasiswa()->whereNotIn('mahasiswa.id', $sudah)->get();
            foreach ($belum as $m) {
                Absensi::create(['kelas_id' => $kelas->id, 'mahasiswa_id' => $m->id, 'pertemuan' => $sesi->pertemuan,
                    'status' => 'A', 'metode' => 'manual', 'sesi_absen_id' => $sesi->id, 'waktu' => now()]);
            }
            $pesan .= " {$belum->count()} mahasiswa yang tidak scan ditandai Alpa.";
        }
        LogAktivitas::catat($kelas, 'akhiri_sesi', $pesan, ['sesi_id' => $sesi->id, 'tandai_alpa' => $request->boolean('tandai_alpa')]);

        return redirect()->route('asisten.kelas.show', $kelas)->with('ok', $pesan);
    }
}
