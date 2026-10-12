<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Asisten\MateriController;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Materi;
use App\Models\Pengumpulan;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Halaman mata kuliah untuk mahasiswa: materi & tugas per pertemuan, pengumpulan tugas. */
class MatkulController extends Controller
{
    private function pastikanIkut(Request $request, Kelas $kelas): void
    {
        abort_unless($request->user('mahasiswa')->kelas()->whereKey($kelas->id)->exists(), 403, 'Kamu belum mengikuti mata kuliah ini.');
    }

    public function show(Request $request, Kelas $kelas)
    {
        $this->pastikanIkut($request, $kelas);
        $mhs = $request->user('mahasiswa');
        $materi = $kelas->materi()->get()->groupBy('pertemuan');
        $tugas = $kelas->tugas()->get()->groupBy('pertemuan');
        $kiriman = Pengumpulan::where('mahasiswa_id', $mhs->id)->whereIn('tugas_id', $kelas->tugas()->pluck('id'))->get()->keyBy('tugas_id');
        $absen = $mhs->absensi()->where('kelas_id', $kelas->id)->get()->keyBy('pertemuan');

        return view('mahasiswa.matkul', compact('kelas', 'materi', 'tugas', 'kiriman', 'absen'));
    }

    public function unduhMateri(Request $request, Materi $materi)
    {
        $this->pastikanIkut($request, $materi->kelas);

        return MateriController::kirim($materi);
    }

    public function tugas(Request $request, Tugas $tugas)
    {
        $this->pastikanIkut($request, $tugas->kelas);
        $kiriman = $tugas->pengumpulan()->where('mahasiswa_id', $request->user('mahasiswa')->id)->first();

        return view('mahasiswa.tugas', ['tugas' => $tugas, 'kelas' => $tugas->kelas, 'kiriman' => $kiriman]);
    }

    public function kumpul(Request $request, Tugas $tugas)
    {
        $this->pastikanIkut($request, $tugas->kelas);
        $mhs = $request->user('mahasiswa');
        $kiriman = $tugas->pengumpulan()->where('mahasiswa_id', $mhs->id)->first();

        if (! $tugas->bukaPengumpulan()) {
            return back()->withErrors(['file' => 'Tenggat sudah lewat dan tugas ini tidak menerima pengumpulan terlambat.']);
        }
        if ($kiriman?->dinilai()) {
            return back()->withErrors(['file' => 'Tugas sudah dinilai, file tidak bisa diganti. Hubungi asisten bila perlu.']);
        }
        $request->validate([
            'file' => ['required', 'file', 'max:'.config('absen.maks_unggah_kb'), 'extensions:'.implode(',', config('absen.ekstensi_tugas'))],
            'catatan' => 'nullable|string|max:500',
        ], [
            'file.extensions' => 'Jenis file tidak didukung. Gunakan: '.implode(', ', config('absen.ekstensi_tugas')).'.',
            'file.max' => 'Ukuran file maksimal '.round(config('absen.maks_unggah_kb') / 1024).' MB.',
            'file.uploaded' => 'File gagal diunggah, kemungkinan terlalu besar.',
        ]);

        $f = $request->file('file');
        $path = $f->storeAs("tugas/{$tugas->id}", $mhs->npm.'-'.Str::uuid()); // tanpa ekstensi, nama asli di nama_file
        if ($kiriman) {
            Storage::delete($kiriman->path); // ganti file lama
        }
        $data = [
            'path' => $path,
            'nama_file' => $f->getClientOriginalName(),
            'ukuran' => $f->getSize(),
            'catatan' => $request->catatan,
            'dikumpulkan_at' => now(),
            'terlambat' => $tugas->lewatTenggat(),
        ];
        $kiriman ? $kiriman->update($data) : $tugas->pengumpulan()->create($data + ['mahasiswa_id' => $mhs->id]);

        return back()->with('ok', $tugas->lewatTenggat() ? 'Tugas terkirim, tetapi tercatat TERLAMBAT.' : 'Tugas berhasil dikumpulkan.');
    }

    public function unduhKiriman(Request $request, Tugas $tugas)
    {
        $kiriman = $tugas->pengumpulan()->where('mahasiswa_id', $request->user('mahasiswa')->id)->firstOrFail();
        abort_unless(Storage::exists($kiriman->path), 404);

        return Storage::download($kiriman->path, $kiriman->nama_file);
    }
}
