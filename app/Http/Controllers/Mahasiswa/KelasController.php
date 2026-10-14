<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

/** Mahasiswa memilih sendiri mata kuliah yang diikuti dari kelas yang sudah dibuat asisten. */
class KelasController extends Controller
{
    public function index(Request $request)
    {
        $mhs = $request->user('mahasiswa');
        $diikuti = $mhs->kelas()->pluck('kelas.id')->all();
        $semua = Kelas::with('asisten', 'asisten2')->withCount('mahasiswa')->orderBy('mata_kuliah')->get();
        $adaAbsen = $mhs->absensi()->distinct()->pluck('kelas_id')->all();

        return view('mahasiswa.kelas', [
            'mhs' => $mhs,
            'diikuti' => $diikuti,
            'adaAbsen' => $adaAbsen,
            'kelasku' => $semua->where('nama_kelas', $mhs->kelas)->values(),
            'kelasLain' => $semua->where('nama_kelas', '!=', $mhs->kelas)->groupBy('nama_kelas')->sortKeys(),
        ]);
    }

    public function gabung(Request $request, Kelas $kelas)
    {
        $mhs = $request->user('mahasiswa');
        if (! $mhs->kelas()->whereKey($kelas->id)->exists()) {
            $mhs->kelas()->attach($kelas->id);
            LogAktivitas::catat($kelas, 'mhs_gabung', "{$mhs->npm} {$mhs->nama} (kelas {$mhs->kelas}) bergabung ke {$kelas->label()}", ['mahasiswa_id' => $mhs->id]);
        }

        return back()->with('ok', 'Kamu terdaftar di '.$kelas->label().'.');
    }

    public function keluar(Request $request, Kelas $kelas)
    {
        $mhs = $request->user('mahasiswa');
        // keluar hanya boleh sebelum ada catatan absen, agar data kehadiran tidak hilang
        if ($mhs->absensi()->where('kelas_id', $kelas->id)->exists()) {
            return back()->withErrors(['kelas' => 'Kamu sudah punya catatan absen di '.$kelas->label().'. Hubungi asisten bila salah pilih.']);
        }
        if (\App\Models\Pengumpulan::where('mahasiswa_id', $mhs->id)->whereIn('tugas_id', $kelas->tugas()->pluck('id'))->exists()) {
            return back()->withErrors(['kelas' => 'Kamu sudah mengumpulkan tugas di '.$kelas->label().'. Hubungi asisten bila salah pilih.']);
        }
        if ($kelas->jawabanUjian()->where('mahasiswa_id', $mhs->id)->exists()) {
            return back()->withErrors(['kelas' => 'Kamu sudah mengunggah jawaban ujian di '.$kelas->label().'. Hubungi asisten bila salah pilih.']);
        }
        $pivot = $mhs->kelas()->whereKey($kelas->id)->first()?->pivot;
        if ($pivot && ($pivot->nilai_uts !== null || $pivot->nilai_uas !== null)) {
            return back()->withErrors(['kelas' => 'Kamu sudah punya nilai UTS/UAS di '.$kelas->label().'. Hubungi asisten bila salah pilih.']);
        }
        if ($pivot) {
            $mhs->kelas()->detach($kelas->id);
            LogAktivitas::catat($kelas, 'mhs_keluar', "{$mhs->npm} {$mhs->nama} keluar dari {$kelas->label()}", ['mahasiswa_id' => $mhs->id]);
        }

        return back()->with('ok', 'Kamu keluar dari '.$kelas->label().'.');
    }
}
