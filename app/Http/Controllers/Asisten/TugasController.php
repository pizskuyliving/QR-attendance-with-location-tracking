<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Pengumpulan;
use App\Models\Tugas;
use App\Models\TugasLampiran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TugasController extends Controller
{
    /** Batas lampiran per tugas (file + tautan), dan per sekali simpan. */
    public const MAKS_LAMPIRAN = 20;

    private const MAKS_SEKALI = 10;

    /** Isi tugas + lampiran baru: [data tugas, tautan[]]. File lampiran diambil dari $request->file('lampiran'). */
    private function validasi(Request $request, Kelas $kelas, ?Tugas $tugas = null): array
    {
        $ekstensi = config('absen.ekstensi_materi');
        // tautan diketik satu per baris; baris kosong & dobel diabaikan
        $tautan = collect(preg_split('/\R/', (string) $request->input('tautan')))->map(fn ($b) => trim($b))->filter()->unique()->values()->all();
        $data = Validator::make($request->all() + ['daftar_tautan' => $tautan], [
            'pertemuan' => 'required|integer|min:1|max:'.$kelas->jumlah_pertemuan,
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:5000',
            'tenggat' => 'required|date',
            'lampiran' => 'nullable|array|max:'.self::MAKS_SEKALI,
            'lampiran.*' => ['file', 'max:'.config('absen.maks_unggah_kb'), 'extensions:'.implode(',', $ekstensi)],
            'daftar_tautan' => 'array|max:'.self::MAKS_SEKALI,
            'daftar_tautan.*' => 'url:http,https|max:500',
        ], [
            'lampiran.max' => 'Maksimal '.self::MAKS_SEKALI.' file sekali unggah.',
            'lampiran.*.extensions' => 'Jenis file :attribute tidak didukung. Gunakan: '.implode(', ', $ekstensi).'.',
            'lampiran.*.max' => 'File :attribute melebihi batas ukuran.',
            'lampiran.*.uploaded' => 'File :attribute gagal diunggah (mungkin terlalu besar).',
            'daftar_tautan.max' => 'Maksimal '.self::MAKS_SEKALI.' tautan sekali simpan.',
            'daftar_tautan.*.url' => 'Tautan baris ke-:position bukan alamat web yang valid (harus diawali http:// atau https://).',
            'daftar_tautan.*.max' => 'Tautan baris ke-:position terlalu panjang.',
        ], ['tenggat' => 'tenggat', 'lampiran.*' => 'ke-:position'])->validate();

        $baru = count($request->file('lampiran', [])) + count($tautan);
        $ada = $tugas ? $tugas->lampiran()->count() : 0;
        if ($ada + $baru > self::MAKS_LAMPIRAN) {
            throw ValidationException::withMessages(['lampiran' => 'Satu tugas maksimal '.self::MAKS_LAMPIRAN.' lampiran. Sekarang sudah ada '.$ada.'; hapus yang tidak perlu dulu.']);
        }

        $isi = collect($data)->only(['pertemuan', 'judul', 'deskripsi', 'tenggat'])->all();
        $isi['deskripsi'] ??= null;
        $isi['terima_terlambat'] = $request->boolean('terima_terlambat');

        return [$isi, $tautan];
    }

    /** Simpan file & tautan lampiran; kembalikan jumlahnya. */
    private function simpanLampiran(Request $request, Tugas $tugas, array $tautan): int
    {
        foreach ($request->file('lampiran', []) as $f) {
            $tugas->lampiran()->create([
                // tanpa ekstensi agar file tidak pernah bisa dieksekusi; nama asli disimpan di nama_file
                'path' => $f->storeAs("tugas/{$tugas->kelas_id}/lampiran", (string) Str::uuid()),
                'nama_file' => $f->getClientOriginalName(),
                'ukuran' => $f->getSize(),
                'judul' => Str::limit(pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME), 150),
            ]);
        }
        foreach ($tautan as $url) {
            $tugas->lampiran()->create(['url' => $url, 'judul' => $url]);
        }

        return count($request->file('lampiran', [])) + count($tautan);
    }

    public function store(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        [$isi, $tautan] = $this->validasi($request, $kelas);
        $t = $kelas->tugas()->create($isi);
        $n = $this->simpanLampiran($request, $t, $tautan);
        LogAktivitas::catat($kelas, 'tugas_tambah', "Membuat tugas P{$t->pertemuan}: {$t->judul} (tenggat {$t->tenggat->format('d/m/Y H:i')})".($n ? " dengan {$n} lampiran" : ''), ['tugas_id' => $t->id]);

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
        [$isi, $tautan] = $this->validasi($request, $tugas->kelas, $tugas);
        $tugas->update($isi);
        $n = $this->simpanLampiran($request, $tugas, $tautan);
        LogAktivitas::catat($tugas->kelas, 'tugas_ubah', "Mengubah tugas P{$tugas->pertemuan}: {$tugas->judul} (tenggat {$tugas->tenggat->format('d/m/Y H:i')})".($n ? ", menambah {$n} lampiran" : ''), ['tugas_id' => $tugas->id]);

        return back()->with('ok', $n ? "Tugas diperbarui, {$n} lampiran ditambahkan." : 'Tugas diperbarui.');
    }

    public function hapusLampiran(Request $request, Tugas $tugas, TugasLampiran $lampiran)
    {
        KelasController::pastikanMilik($request, $tugas->kelas);
        abort_unless($lampiran->tugas_id === $tugas->id, 404);
        LogAktivitas::catat($tugas->kelas, 'tugas_lampiran_hapus', "Menghapus lampiran \"{$lampiran->judul}\" dari tugas {$tugas->judul}", ['tugas_id' => $tugas->id]);
        $lampiran->delete();

        return back()->with('ok', 'Lampiran dihapus.');
    }

    public function unduhLampiran(Request $request, Tugas $tugas, TugasLampiran $lampiran)
    {
        KelasController::pastikanMilik($request, $tugas->kelas);
        abort_unless($lampiran->tugas_id === $tugas->id, 404);

        return self::kirimLampiran($lampiran);
    }

    /** Dipakai asisten & mahasiswa (hak akses dicek di pemanggil). */
    public static function kirimLampiran(TugasLampiran $lampiran)
    {
        abort_unless($lampiran->adalahFile() && Storage::exists($lampiran->path), 404);

        return Storage::download($lampiran->path, $lampiran->nama_file);
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
