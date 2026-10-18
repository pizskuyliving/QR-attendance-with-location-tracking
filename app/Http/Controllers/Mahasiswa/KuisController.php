<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Kuis;
use App\Models\KuisJawaban;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

/** Mahasiswa mengerjakan kuis refleksi: hanya yang hadir, satu kali, dalam batas waktu. Jawaban tersimpan tiap memilih. */
class KuisController extends Controller
{
    private function siapkan(Request $request, Kuis $kuis): array
    {
        $mhs = $request->user('mahasiswa');
        abort_unless($mhs->kelas()->whereKey($kuis->kelas_id)->exists(), 403, 'Kamu belum mengikuti mata kuliah ini.');
        abort_if($kuis->draf(), 404);

        return [$mhs, $kuis->jawaban()->where('mahasiswa_id', $mhs->id)->first()];
    }

    public function show(Request $request, Kuis $kuis)
    {
        [$mhs, $jawaban] = $this->siapkan($request, $kuis);
        $hadir = $kuis->bolehDijawab($mhs);
        $soal = $kuis->soal;
        if ($kuis->berjalan() && $hadir && ! $jawaban) {
            $jawaban = KuisJawaban::firstOrCreate(['kuis_id' => $kuis->id, 'mahasiswa_id' => $mhs->id], ['mulai_at' => now(), 'jawaban' => []]);
        }

        return view('mahasiswa.kuis', [
            'kuis' => $kuis, 'kelas' => $kuis->kelas, 'hadir' => $hadir, 'jawaban' => $jawaban,
            'urutan' => $kuis->acakUntuk($mhs, $soal), 'soal' => $soal,
        ]);
    }

    /** Simpan otomatis satu pilihan (dipanggil tiap mahasiswa memilih jawaban). */
    public function simpan(Request $request, Kuis $kuis)
    {
        [$mhs, $jawaban] = $this->siapkan($request, $kuis);
        $data = $request->validate(['soal' => 'required|integer', 'pilihan' => 'required|in:a,b,c']);
        if ($galat = $this->tolak($kuis, $mhs, $jawaban)) {
            return response()->json(['pesan' => $galat], 422);
        }
        abort_unless($kuis->soal->contains('id', $data['soal']), 422);
        $this->catat($kuis, $jawaban, [$data['soal'] => $data['pilihan']]);

        return response()->json(['ok' => true]);
    }

    /** Kirim akhir: tombol Kirim, otomatis saat waktu habis, atau otomatis saat mahasiswa meninggalkan halaman (alasan=pindah_tab). */
    public function kirim(Request $request, Kuis $kuis)
    {
        [$mhs, $jawaban] = $this->siapkan($request, $kuis);
        $pilihan = collect($request->input('jawaban', []))->filter(fn ($v, $k) => in_array($v, ['a', 'b', 'c'], true) && $kuis->soal->contains('id', (int) $k))->all();
        if ($galat = $this->tolak($kuis, $mhs, $jawaban)) {
            return redirect()->route('mhs.kuis', $kuis)->withErrors(['kuis' => $galat]);
        }
        $otomatis = $request->input('alasan') === 'pindah_tab' ? 'pindah_tab' : null;
        $this->catat($kuis, $jawaban, $pilihan, selesai: true, otomatis: $otomatis);
        if ($otomatis) {
            LogAktivitas::catat($kuis->kelas, 'mhs_kuis_pindah_tab', "{$mhs->npm} {$mhs->nama} meninggalkan halaman kuis P{$kuis->pertemuan}; jawaban dikirim otomatis",
                ['kuis_id' => $kuis->id, 'mahasiswa_id' => $mhs->id]);
        }

        return redirect()->route('mhs.kuis', $kuis)->with('ok', $otomatis
            ? 'Jawaban dikirim otomatis karena kamu meninggalkan halaman kuis.'
            : 'Jawaban terkirim. Nilai dan kunci jawaban tampil setelah kuis ditutup.');
    }

    private function tolak(Kuis $kuis, $mhs, ?KuisJawaban $jawaban): ?string
    {
        return match (true) {
            ! $kuis->bolehDijawab($mhs) => "Kuis ini hanya untuk mahasiswa yang sudah absen hadir di pertemuan {$kuis->pertemuan}.",
            ! $kuis->terimaJawaban() => 'Waktu kuis sudah habis. Jawaban yang sempat tersimpan tetap dihitung.',
            ! $jawaban => 'Buka ulang halaman kuis.',
            $jawaban->selesai_at !== null => 'Jawaban kamu sudah terkirim.',
            default => null,
        };
    }

    private function catat(Kuis $kuis, KuisJawaban $jawaban, array $baru, bool $selesai = false, ?string $otomatis = null): void
    {
        $isi = array_replace($jawaban->jawaban ?? [], array_map('strval', $baru));
        [$benar, $nilai] = $kuis->nilaiDari($isi);
        $jawaban->update(['jawaban' => $isi, 'benar' => $benar, 'nilai' => $nilai] + ($selesai ? ['selesai_at' => now(), 'kirim_otomatis' => $otomatis] : []));
    }
}
