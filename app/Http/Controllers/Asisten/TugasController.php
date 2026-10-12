<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Pengumpulan;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TugasController extends Controller
{
    private function validasi(Request $request, Kelas $kelas): array
    {
        $data = $request->validate([
            'pertemuan' => 'required|integer|min:1|max:'.$kelas->jumlah_pertemuan,
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:5000',
            'tenggat' => 'required|date',
        ], [], ['tenggat' => 'tenggat']);
        $data['terima_terlambat'] = $request->boolean('terima_terlambat');

        return $data;
    }

    public function store(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $t = $kelas->tugas()->create($this->validasi($request, $kelas));
        LogAktivitas::catat($kelas, 'tugas_tambah', "Membuat tugas P{$t->pertemuan}: {$t->judul} (tenggat {$t->tenggat->format('d/m/Y H:i')})", ['tugas_id' => $t->id]);

        return redirect()->route('asisten.tugas.show', $t)->with('ok', 'Tugas dibuat. Mahasiswa kelas ini sudah bisa melihat dan mengumpulkan.');
    }

    public function show(Request $request, Tugas $tugas)
    {
        $kelas = $tugas->kelas;
        KelasController::pastikanMilik($request, $kelas);
        $kiriman = $tugas->pengumpulan()->get()->keyBy('mahasiswa_id');
        $mahasiswa = $kelas->mahasiswa()->get();

        return view('asisten.tugas', compact('tugas', 'kelas', 'kiriman', 'mahasiswa'));
    }

    public function update(Request $request, Tugas $tugas)
    {
        KelasController::pastikanMilik($request, $tugas->kelas);
        $tugas->update($this->validasi($request, $tugas->kelas));
        LogAktivitas::catat($tugas->kelas, 'tugas_ubah', "Mengubah tugas P{$tugas->pertemuan}: {$tugas->judul} (tenggat {$tugas->tenggat->format('d/m/Y H:i')})", ['tugas_id' => $tugas->id]);

        return back()->with('ok', 'Tugas diperbarui.');
    }

    public function destroy(Request $request, Tugas $tugas)
    {
        $kelas = $tugas->kelas;
        KelasController::pastikanMilik($request, $kelas);
        LogAktivitas::catat($kelas, 'tugas_hapus', "Menghapus tugas P{$tugas->pertemuan}: {$tugas->judul} beserta {$tugas->pengumpulan()->count()} kiriman");
        $tugas->delete();

        return redirect()->route('asisten.kelas.show', [$kelas, 'tab' => 'tugas'])->with('ok', 'Tugas dihapus.');
    }

    /** Simpan nilai & komentar satu kiriman. */
    public function nilai(Request $request, Tugas $tugas, Pengumpulan $pengumpulan)
    {
        KelasController::pastikanMilik($request, $tugas->kelas);
        abort_unless($pengumpulan->tugas_id === $tugas->id, 404);
        $data = $request->validate(['nilai' => 'nullable|numeric|min:0|max:100', 'komentar' => 'nullable|string|max:2000']);

        $lama = $pengumpulan->nilai;
        $pengumpulan->update([
            'nilai' => $data['nilai'],
            'komentar' => $data['komentar'] ?? null,
            'dinilai_at' => $data['nilai'] !== null ? now() : null,
        ]);
        if ($lama != $pengumpulan->nilai) {
            $m = $pengumpulan->mahasiswa;
            $teks = fn ($n) => $n === null ? 'kosong' : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
            LogAktivitas::catat($tugas->kelas, 'nilai_tugas', "Nilai {$m->npm} {$m->nama} untuk \"{$tugas->judul}\": {$teks($lama)} → {$teks($pengumpulan->nilai)}",
                ['pengumpulan_id' => $pengumpulan->id, 'lama' => $lama, 'baru' => $pengumpulan->nilai]);
        }

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('ok', 'Nilai disimpan.');
    }

    public function unduh(Request $request, Tugas $tugas, Pengumpulan $pengumpulan)
    {
        KelasController::pastikanMilik($request, $tugas->kelas);
        abort_unless($pengumpulan->tugas_id === $tugas->id && Storage::exists($pengumpulan->path), 404);
        $m = $pengumpulan->mahasiswa;

        return Storage::download($pengumpulan->path, $m->npm.'_'.Str::slug($m->nama).'_'.$pengumpulan->nama_file);
    }

    /** Semua kiriman dalam satu file ZIP, nama file diawali NPM. */
    public function unduhSemua(Request $request, Tugas $tugas)
    {
        KelasController::pastikanMilik($request, $tugas->kelas);
        abort_unless(class_exists(\ZipArchive::class), 501, 'Ekstensi PHP zip belum aktif.');
        $kiriman = $tugas->pengumpulan()->with('mahasiswa')->get();
        abort_if($kiriman->isEmpty(), 404, 'Belum ada kiriman.');

        $tmp = tempnam(sys_get_temp_dir(), 'tugas');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($kiriman as $p) {
            if (Storage::exists($p->path)) {
                $zip->addFile(Storage::path($p->path), $p->mahasiswa->npm.'_'.Str::slug($p->mahasiswa->nama).'_'.$p->nama_file);
            }
        }
        $zip->close();

        return response()->download($tmp, Str::slug($tugas->kelas->label().'-'.$tugas->judul).'.zip')->deleteFileAfterSend();
    }
}
