<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\SoalUjian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SoalUjianController extends Controller
{
    public function store(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $ekstensi = config('absen.ekstensi_materi');
        $data = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(SoalUjian::JENIS))],
            'judul' => 'nullable|string|max:150',
            'file' => ['required', 'file', 'max:'.config('absen.maks_unggah_kb'), 'extensions:'.implode(',', $ekstensi)],
            'tampil_mahasiswa' => 'nullable|boolean',
        ], [
            'file.extensions' => 'Jenis file tidak didukung. Gunakan: '.implode(', ', $ekstensi).'.',
            'file.max' => 'Ukuran file maksimal '.round(config('absen.maks_unggah_kb') / 1024).' MB.',
            'file.uploaded' => 'File gagal diunggah, kemungkinan terlalu besar.',
        ], ['file' => 'file soal']);

        $f = $request->file('file');
        $soal = SoalUjian::create([
            'kelas_id' => $kelas->id,
            'jenis' => $data['jenis'],
            // tanpa ekstensi agar file tidak pernah bisa dieksekusi; nama asli disimpan di nama_file
            'path' => $f->storeAs("soal/{$kelas->id}", (string) Str::uuid()),
            'nama_file' => $f->getClientOriginalName(),
            'ukuran' => $f->getSize(),
            'judul' => ($data['judul'] ?? null) ?: pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME),
            'tampil_mahasiswa' => $request->boolean('tampil_mahasiswa'),
            'user_id' => $request->user()->id,
        ]);
        LogAktivitas::catat($kelas, 'soal_tambah', "Mengunggah soal {$soal->label()}: {$soal->judul}", ['soal_id' => $soal->id]);

        return redirect()->route('asisten.kelas.show', [$kelas, 'tab' => 'ujian'])->with('ok', "Soal {$soal->label()} diunggah.");
    }

    /** Bagikan ke / sembunyikan dari mahasiswa kelas ini. */
    public function tampil(Request $request, Kelas $kelas, SoalUjian $soal)
    {
        KelasController::pastikanMilik($request, $kelas);
        abort_unless($soal->kelas_id === $kelas->id, 404);
        $soal->update(['tampil_mahasiswa' => ! $soal->tampil_mahasiswa]);
        $teks = $soal->tampil_mahasiswa ? 'dibagikan ke mahasiswa' : 'disembunyikan dari mahasiswa';
        LogAktivitas::catat($kelas, 'soal_tampil', "Soal {$soal->label()} \"{$soal->judul}\" {$teks}");

        return back()->with('ok', "Soal {$soal->label()} {$teks}.");
    }

    public function destroy(Request $request, Kelas $kelas, SoalUjian $soal)
    {
        KelasController::pastikanMilik($request, $kelas);
        abort_unless($soal->kelas_id === $kelas->id, 404);
        LogAktivitas::catat($kelas, 'soal_hapus', "Menghapus soal {$soal->label()}: {$soal->judul}");
        $soal->delete();

        return back()->with('ok', 'Soal dihapus.');
    }

    /** Asisten kelas, dosen matkul, dan laboran boleh mengunduh. */
    public function unduh(Request $request, Kelas $kelas, SoalUjian $soal)
    {
        KelasController::pastikanBisaLihat($request, $kelas);
        abort_unless($soal->kelas_id === $kelas->id, 404);

        return self::kirim($soal);
    }

    public static function kirim(SoalUjian $soal)
    {
        abort_unless(Storage::exists($soal->path), 404);

        return Storage::download($soal->path, $soal->nama_file);
    }
}
