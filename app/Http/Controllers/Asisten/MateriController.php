<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MateriController extends Controller
{
    public function store(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $data = $request->validate([
            'pertemuan' => 'required|integer|min:1|max:'.$kelas->jumlah_pertemuan,
            'judul' => 'nullable|string|max:150',
            'file' => ['nullable', 'required_without:url', 'file', 'max:'.config('absen.maks_unggah_kb'), 'extensions:'.implode(',', config('absen.ekstensi_materi'))],
            'url' => ['nullable', 'required_without:file', 'url:http,https', 'max:500'],
        ], [
            'file.required_without' => 'Pilih file atau isi tautan.',
            'url.required_without' => 'Pilih file atau isi tautan.',
            'file.extensions' => 'Jenis file tidak didukung. Gunakan: '.implode(', ', config('absen.ekstensi_materi')).'.',
        ], ['url' => 'tautan']);

        $isian = ['kelas_id' => $kelas->id, 'pertemuan' => $data['pertemuan'], 'user_id' => $request->user()->id];
        if ($request->hasFile('file')) {
            $f = $request->file('file');
            $isian += [
                // tanpa ekstensi agar file tidak pernah bisa dieksekusi; nama asli disimpan di nama_file
                'path' => $f->storeAs("materi/{$kelas->id}", (string) Str::uuid()),
                'nama_file' => $f->getClientOriginalName(),
                'ukuran' => $f->getSize(),
                'judul' => ($data['judul'] ?? null) ?: pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME),
            ];
        } else {
            $isian += ['url' => $data['url'], 'judul' => ($data['judul'] ?? null) ?: $data['url']];
        }
        $m = Materi::create($isian);
        LogAktivitas::catat($kelas, 'materi_tambah', "Menambah materi P{$m->pertemuan}: {$m->judul}", ['materi_id' => $m->id]);

        return redirect()->route('asisten.kelas.show', [$kelas, 'tab' => 'materi'])->with('ok', 'Materi ditambahkan.');
    }

    public function destroy(Request $request, Kelas $kelas, Materi $materi)
    {
        KelasController::pastikanMilik($request, $kelas);
        abort_unless($materi->kelas_id === $kelas->id, 404);
        LogAktivitas::catat($kelas, 'materi_hapus', "Menghapus materi P{$materi->pertemuan}: {$materi->judul}");
        $materi->delete();

        return back()->with('ok', 'Materi dihapus.');
    }

    /** Dipakai asisten & mahasiswa (hak akses dicek di route masing-masing). */
    public static function kirim(Materi $materi)
    {
        abort_unless($materi->adalahFile() && Storage::exists($materi->path), 404);

        return Storage::download($materi->path, $materi->nama_file);
    }

    public function unduh(Request $request, Kelas $kelas, Materi $materi)
    {
        KelasController::pastikanMilik($request, $kelas);
        abort_unless($materi->kelas_id === $kelas->id, 404);

        return self::kirim($materi);
    }
}
