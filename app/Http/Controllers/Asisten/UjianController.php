<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\JawabanUjian;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\SoalUjian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Pengumpulan & penilaian jawaban UTS/UAS. Nilainya disimpan di kelas_mahasiswa.nilai_uts/nilai_uas. */
class UjianController extends Controller
{
    public function show(Request $request, Kelas $kelas, string $jenis)
    {
        KelasController::pastikanMilik($request, $kelas);
        $label = SoalUjian::JENIS[$jenis];
        $mahasiswa = $kelas->mahasiswa;
        $jawaban = $kelas->jawabanUjian()->where('jenis', $jenis)->get()->keyBy('mahasiswa_id');
        $soal = $kelas->soalUjian()->where('jenis', $jenis)->get();

        return view('asisten.ujian', compact('kelas', 'jenis', 'label', 'mahasiswa', 'jawaban', 'soal'));
    }

    /** Buka pengumpulan jawaban sampai tenggat tertentu, atau tutup (tenggat kosong). */
    public function tenggat(Request $request, Kelas $kelas, string $jenis)
    {
        KelasController::pastikanMilik($request, $kelas);
        $label = SoalUjian::JENIS[$jenis];
        if ($request->boolean('tutup')) {
            $kelas->update(['tenggat_'.$jenis => null]);
            LogAktivitas::catat($kelas, 'ujian_tenggat', "Menutup pengumpulan jawaban {$label}");

            return back()->with('ok', "Pengumpulan jawaban {$label} ditutup.");
        }
        $data = $request->validate(['tenggat' => 'required|date|after:now'], [], ['tenggat' => 'batas pengumpulan']);
        $kelas->update(['tenggat_'.$jenis => $data['tenggat']]);
        $t = $kelas->tenggatUjian($jenis);
        LogAktivitas::catat($kelas, 'ujian_tenggat', "Membuka pengumpulan jawaban {$label} sampai {$t->format('d/m/Y H:i')}");

        return back()->with('ok', "Pengumpulan jawaban {$label} dibuka sampai {$t->translatedFormat('d M Y H:i')}.");
    }

    /** Nilai (dan komentar) satu mahasiswa; mahasiswa tanpa jawaban online tetap bisa dinilai (ujian tertulis). */
    public function nilai(Request $request, Kelas $kelas, string $jenis, Mahasiswa $mahasiswa)
    {
        KelasController::pastikanMilik($request, $kelas);
        $m = $kelas->mahasiswa()->whereKey($mahasiswa->id)->first();
        abort_unless($m, 404);
        $data = $request->validate(['nilai' => 'nullable|numeric|min:0|max:100', 'komentar' => 'nullable|string|max:2000']);
        $kolom = 'nilai_'.$jenis;
        $lama = $m->pivot->$kolom === null ? null : (float) $m->pivot->$kolom;
        $baru = $data['nilai'] === null ? null : round((float) $data['nilai'], 2);

        if ($lama !== $baru) {
            $kelas->mahasiswa()->updateExistingPivot($m->id, [$kolom => $baru]);
            $teks = fn ($n) => $n === null ? 'kosong' : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
            LogAktivitas::catat($kelas, 'nilai_ujian', "Nilai ".SoalUjian::JENIS[$jenis]." {$m->npm} {$m->nama}: {$teks($lama)} → {$teks($baru)}",
                ['mahasiswa_id' => $m->id, 'jenis' => $jenis, 'lama' => $lama, 'baru' => $baru]);
        }
        $kelas->jawabanUjian()->where('jenis', $jenis)->where('mahasiswa_id', $m->id)->update(['komentar' => $data['komentar'] ?? null]);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('ok', 'Nilai disimpan.');
    }

    public function unduh(Request $request, Kelas $kelas, string $jenis, JawabanUjian $jawaban)
    {
        KelasController::pastikanMilik($request, $kelas);
        abort_unless($jawaban->kelas_id === $kelas->id && $jawaban->jenis === $jenis && Storage::exists($jawaban->path), 404);
        $m = $jawaban->mahasiswa;

        return Storage::download($jawaban->path, $m->npm.'_'.Str::slug($m->nama).'_'.$jawaban->nama_file);
    }

    /** Semua jawaban dalam satu ZIP, nama file diawali NPM. */
    public function zip(Request $request, Kelas $kelas, string $jenis)
    {
        KelasController::pastikanMilik($request, $kelas);
        abort_unless(class_exists(\ZipArchive::class), 501, 'Ekstensi PHP zip belum aktif.');
        $jawaban = $kelas->jawabanUjian()->where('jenis', $jenis)->with('mahasiswa')->get();
        abort_if($jawaban->isEmpty(), 404, 'Belum ada jawaban.');

        $tmp = tempnam(sys_get_temp_dir(), 'ujian');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($jawaban as $j) {
            if (Storage::exists($j->path)) {
                $zip->addFile(Storage::path($j->path), $j->mahasiswa->npm.'_'.Str::slug($j->mahasiswa->nama).'_'.$j->nama_file);
            }
        }
        $zip->close();

        return response()->download($tmp, Str::slug($kelas->label().'-jawaban-'.$jenis).'.zip')->deleteFileAfterSend();
    }
}
