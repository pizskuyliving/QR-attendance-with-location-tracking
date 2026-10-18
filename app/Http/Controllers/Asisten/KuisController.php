<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Kuis;
use App\Models\LogAktivitas;
use App\Services\ExportExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Kuis refleksi akhir pertemuan: buat (draf) → buka dengan batas waktu → hasil & analisis per soal. */
class KuisController extends Controller
{
    private function validasi(Request $request, Kelas $kelas, ?Kuis $kuis = null): array
    {
        return $request->validate([
            'pertemuan' => ['required', 'integer', 'min:1', 'max:'.$kelas->jumlah_pertemuan,
                Rule::unique('kuis')->where('kelas_id', $kelas->id)->ignore($kuis?->id)],
            'durasi_menit' => 'required|integer|min:1|max:120',
            'soal' => 'required|array|size:'.Kuis::JUMLAH_SOAL,
            'soal.*.pertanyaan' => 'required|string|max:1000',
            'soal.*.a' => 'required|string|max:500',
            'soal.*.b' => 'required|string|max:500',
            'soal.*.c' => 'required|string|max:500',
            'soal.*.kunci' => 'required|in:a,b,c',
        ], [
            'pertemuan.unique' => 'Pertemuan ini sudah punya kuis. Satu pertemuan hanya satu kuis.',
            'soal.size' => 'Kuis harus berisi '.Kuis::JUMLAH_SOAL.' soal.',
            'soal.*.pertanyaan.required' => 'Pertanyaan soal :position belum diisi.',
            'soal.*.a.required' => 'Pilihan A soal :position belum diisi.',
            'soal.*.b.required' => 'Pilihan B soal :position belum diisi.',
            'soal.*.c.required' => 'Pilihan C soal :position belum diisi.',
            'soal.*.kunci.required' => 'Pilih jawaban benar untuk soal :position.',
        ], ['durasi_menit' => 'durasi']);
    }

    private function simpanSoal(Kuis $kuis, array $soal): void
    {
        $kuis->soal()->delete();
        foreach (array_values($soal) as $i => $s) {
            $kuis->soal()->create(['urutan' => $i + 1, 'pertanyaan' => $s['pertanyaan'], 'pilihan_a' => $s['a'], 'pilihan_b' => $s['b'], 'pilihan_c' => $s['c'], 'kunci' => $s['kunci']]);
        }
    }

    private static function pastikan(Request $request, Kuis $kuis): void
    {
        KelasController::pastikanMilik($request, $kuis->kelas);
    }

    public function store(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $data = $this->validasi($request, $kelas);
        $kuis = DB::transaction(function () use ($kelas, $data) {
            $kuis = $kelas->kuis()->create(['pertemuan' => $data['pertemuan'], 'durasi_menit' => $data['durasi_menit']]);
            $this->simpanSoal($kuis, $data['soal']);

            return $kuis;
        });
        LogAktivitas::catat($kelas, 'kuis_buat', "Membuat kuis P{$kuis->pertemuan}", ['kuis_id' => $kuis->id]);

        return redirect()->route('asisten.kuis.show', $kuis)->with('ok', 'Kuis disimpan sebagai draf. Buka kuis di akhir pertemuan.');
    }

    public function show(Request $request, Kuis $kuis)
    {
        self::pastikan($request, $kuis);
        $kelas = $kuis->kelas;
        $soal = $kuis->soal;
        $mahasiswa = $kelas->mahasiswa()->get();
        $hadir = Absensi::where('kelas_id', $kelas->id)->where('pertemuan', $kuis->pertemuan)->where('status', 'H')->pluck('mahasiswa_id')->flip();
        $jawaban = $kuis->jawaban()->get()->keyBy('mahasiswa_id');

        // persentase benar per soal, dari mahasiswa yang menjawab
        $perSoal = $soal->mapWithKeys(function ($s) use ($jawaban) {
            $dijawab = $jawaban->filter(fn ($j) => isset($j->jawaban[$s->id]));
            $benar = $dijawab->filter(fn ($j) => $j->jawaban[$s->id] === $s->kunci)->count();
            $sebaran = collect(Kuis::PILIHAN)->map(fn ($l, $h) => $dijawab->filter(fn ($j) => $j->jawaban[$s->id] === $h)->count());

            return [$s->id => ['persen' => $jawaban->count() ? round($benar / $jawaban->count() * 100) : null, 'sebaran' => $sebaran]];
        });

        $kelasLain = Kelas::query()->when($request->user()->role !== 'admin', fn ($q) => $q->diajar($request->user()->id))
            ->whereKeyNot($kelas->id)
            ->where(fn ($q) => $kelas->matkul_id ? $q->where('matkul_id', $kelas->matkul_id) : $q->where('mata_kuliah', $kelas->mata_kuliah))
            ->orderBy('nama_kelas')->get();

        return view('asisten.kuis', compact('kuis', 'kelas', 'soal', 'mahasiswa', 'hadir', 'jawaban', 'perSoal', 'kelasLain'));
    }

    public function update(Request $request, Kuis $kuis)
    {
        self::pastikan($request, $kuis);
        if (! $kuis->draf()) {
            return back()->withErrors(['soal' => 'Kuis yang sudah dibuka tidak bisa diubah, agar nilai mahasiswa tetap adil.']);
        }
        $data = $this->validasi($request, $kuis->kelas, $kuis);
        DB::transaction(function () use ($kuis, $data) {
            $kuis->update(['pertemuan' => $data['pertemuan'], 'durasi_menit' => $data['durasi_menit']]);
            $this->simpanSoal($kuis, $data['soal']);
        });
        LogAktivitas::catat($kuis->kelas, 'kuis_ubah', "Mengubah kuis P{$kuis->pertemuan}", ['kuis_id' => $kuis->id]);

        return back()->with('ok', 'Kuis diperbarui.');
    }

    public function buka(Request $request, Kuis $kuis)
    {
        self::pastikan($request, $kuis);
        abort_unless($kuis->draf(), 422, 'Kuis ini sudah pernah dibuka.');
        $durasi = (int) $request->validate(['durasi_menit' => 'required|integer|min:1|max:120'], [], ['durasi_menit' => 'durasi'])['durasi_menit'];
        if ($kuis->soal()->count() !== Kuis::JUMLAH_SOAL) {
            return back()->withErrors(['soal' => 'Lengkapi '.Kuis::JUMLAH_SOAL.' soal dulu.']);
        }
        $kuis->update(['durasi_menit' => $durasi, 'dibuka_at' => now(), 'ditutup_at' => now()->addMinutes($durasi)]);
        LogAktivitas::catat($kuis->kelas, 'kuis_buka', "Membuka kuis P{$kuis->pertemuan} selama {$durasi} menit", ['kuis_id' => $kuis->id]);

        return back()->with('ok', "Kuis dibuka selama {$durasi} menit. Mahasiswa yang sudah absen hadir di pertemuan {$kuis->pertemuan} bisa mengerjakan dari HP.");
    }

    public function tutup(Request $request, Kuis $kuis)
    {
        self::pastikan($request, $kuis);
        if ($kuis->berjalan()) {
            $kuis->update(['ditutup_at' => now()]);
            LogAktivitas::catat($kuis->kelas, 'kuis_tutup', "Menutup kuis P{$kuis->pertemuan} lebih awal", ['kuis_id' => $kuis->id]);
        }

        return back()->with('ok', 'Kuis ditutup. Mahasiswa sekarang bisa melihat nilai dan kunci jawaban.');
    }

    /** Salin soal ke kelas lain (mata kuliah sama) sebagai draf di pertemuan yang sama. */
    public function salin(Request $request, Kuis $kuis)
    {
        self::pastikan($request, $kuis);
        $tujuan = Kelas::findOrFail($request->validate(['kelas_id' => 'required|integer'])['kelas_id']);
        KelasController::pastikanMilik($request, $tujuan);
        if ($tujuan->kuis()->where('pertemuan', $kuis->pertemuan)->exists()) {
            return back()->withErrors(['kelas_id' => "Kelas {$tujuan->nama_kelas} sudah punya kuis di pertemuan {$kuis->pertemuan}."]);
        }
        if ($kuis->pertemuan > $tujuan->jumlah_pertemuan) {
            return back()->withErrors(['kelas_id' => "Kelas {$tujuan->nama_kelas} hanya punya {$tujuan->jumlah_pertemuan} pertemuan."]);
        }
        $baru = DB::transaction(function () use ($kuis, $tujuan) {
            $baru = $tujuan->kuis()->create(['pertemuan' => $kuis->pertemuan, 'durasi_menit' => $kuis->durasi_menit]);
            foreach ($kuis->soal as $s) {
                $baru->soal()->create($s->only(['urutan', 'pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'kunci']));
            }

            return $baru;
        });
        LogAktivitas::catat($tujuan, 'kuis_buat', "Menyalin kuis P{$kuis->pertemuan} dari kelas {$kuis->kelas->nama_kelas}", ['kuis_id' => $baru->id]);

        return back()->with('ok', "Kuis disalin ke kelas {$tujuan->nama_kelas} sebagai draf.");
    }

    public function destroy(Request $request, Kuis $kuis)
    {
        self::pastikan($request, $kuis);
        $kelas = $kuis->kelas;
        LogAktivitas::catat($kelas, 'kuis_hapus', "Menghapus kuis P{$kuis->pertemuan} beserta {$kuis->jawaban()->count()} jawaban");
        $kuis->delete();

        return redirect()->route('asisten.kelas.show', [$kelas, 'tab' => 'kuis'])->with('ok', 'Kuis dihapus.');
    }

    /** Hasil kuis ke Excel: nilai tiap mahasiswa dan pilihan per soal. */
    public function excel(Request $request, Kuis $kuis)
    {
        self::pastikan($request, $kuis);
        $kelas = $kuis->kelas;
        $soal = $kuis->soal;
        $jawaban = $kuis->jawaban()->get()->keyBy('mahasiswa_id');
        $hadir = Absensi::where('kelas_id', $kelas->id)->where('pertemuan', $kuis->pertemuan)->where('status', 'H')->pluck('mahasiswa_id')->flip();

        $book = new Spreadsheet;
        $ws = $book->getActiveSheet()->setTitle('Kuis P'.$kuis->pertemuan);
        $judul = ['No', 'NPM', 'Nama', 'Hadir'];
        foreach ($soal as $s) {
            $judul[] = 'Soal '.$s->urutan.' (kunci '.strtoupper($s->kunci).')';
        }
        array_push($judul, 'Benar', 'Nilai');
        $ws->fromArray($judul, null, 'A1');
        $r = 2;
        foreach ($kelas->mahasiswa()->get() as $i => $m) {
            $j = $jawaban[$m->id] ?? null;
            $isi = [$i + 1, $m->npm, ExportExcel::teks($m->nama), isset($hadir[$m->id]) ? 'Ya' : 'Tidak'];
            foreach ($soal as $s) {
                $isi[] = strtoupper($j?->jawaban[$s->id] ?? '-');
            }
            array_push($isi, $j?->benar ?? 0, $j?->nilai ?? 0);
            $ws->fromArray($isi, "\0kosong", "A{$r}");
            $ws->setCellValueExplicit("B{$r}", $m->npm, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }
        $ws->getStyle('A1:'.$ws->getHighestColumn().'1')->getFont()->setBold(true);
        foreach (['A' => 5, 'B' => 14, 'C' => 28] as $c => $w) {
            $ws->getColumnDimension($c)->setWidth($w);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'kuis');
        (new Xlsx($book))->save($tmp);

        return response()->download($tmp, \Illuminate\Support\Str::slug($kelas->label().'-kuis-p'.$kuis->pertemuan).'.xlsx')->deleteFileAfterSend();
    }
}
