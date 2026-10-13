<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Asisten\KelasController as KelasAsisten;
use App\Http\Controllers\Controller;
use App\Mail\Pemberitahuan;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Matkul;
use App\Models\User;
use App\Support\Bobot;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Dosen membuat, mengubah, dan menghapus kelas di matkulnya, serta memilih asisten pengajar tiap kelas. */
class KelasController extends Controller
{
    private function pastikanKelas(Request $request, Matkul $matkul, Kelas $kelas): void
    {
        MatkulController::pastikanMilik($request, $matkul);
        abort_unless($kelas->matkul_id === $matkul->id, 404);
    }

    public function store(Request $request, Matkul $matkul)
    {
        MatkulController::pastikanMilik($request, $matkul);
        $data = KelasAsisten::validasi($request, null, denganMatkul: true) + $this->bobot($request);
        $this->pastikanNamaUnik($matkul, $data['nama_kelas']);
        $pilihan = $this->asistenDipilih($request);

        $kelas = Kelas::create($data + $pilihan + ['matkul_id' => $matkul->id, 'mata_kuliah' => $matkul->nama]);
        LogAktivitas::catat($kelas, 'tambah_kelas', "{$request->user()->name} membuat kelas {$kelas->label()}");
        $pesan = "Kelas {$kelas->nama_kelas} ditambahkan ke {$matkul->nama}.";
        if ($kelas->daftarAsisten()->isNotEmpty()) {
            $pesan .= ' '.$this->beriTahuAsisten($request, $kelas, $kelas->daftarAsisten());
        }

        return back()->with('ok', $pesan);
    }

    public function update(Request $request, Matkul $matkul, Kelas $kelas)
    {
        $this->pastikanKelas($request, $matkul, $kelas);
        $data = KelasAsisten::validasi($request, $kelas) + $this->bobot($request);
        $this->pastikanNamaUnik($matkul, $data['nama_kelas'], $kelas);
        $lama = $kelas->only(array_keys($data));
        $kelas->update($data);
        $berubah = array_keys(array_filter($data, fn ($v, $k) => $v != $lama[$k], ARRAY_FILTER_USE_BOTH));
        if ($berubah) {
            LogAktivitas::catat($kelas, 'ubah_kelas', "{$request->user()->name} mengubah pengaturan kelas: ".implode(', ', $berubah));
        }

        return back()->with('ok', "Kelas {$kelas->nama_kelas} diperbarui.");
    }

    /** Pilih, ganti, atau kosongkan asisten pengajar satu kelas (maksimal dua: Asisten 1 & Asisten 2). */
    public function tugaskan(Request $request, Matkul $matkul, Kelas $kelas)
    {
        $this->pastikanKelas($request, $matkul, $kelas);
        $pilihan = $this->asistenDipilih($request);
        $sebelum = array_filter([$kelas->asisten_id, $kelas->asisten2_id]);
        if ($pilihan === ['asisten_id' => $kelas->asisten_id, 'asisten2_id' => $kelas->asisten2_id]) {
            return back()->with('ok', 'Asisten tidak berubah.');
        }
        $lama = $kelas->namaAsisten();
        $kelas->update($pilihan);
        $kelas->load('asisten', 'asisten2');
        LogAktivitas::catat($kelas, 'ganti_asisten', "{$request->user()->name} mengganti asisten pengajar: {$lama} → {$kelas->namaAsisten()}");

        if ($kelas->daftarAsisten()->isEmpty()) {
            return back()->with('ok', "Kelas {$kelas->nama_kelas} kini belum punya asisten.");
        }
        // email hanya untuk asisten yang baru masuk
        $baru = $kelas->daftarAsisten()->reject(fn (User $a) => in_array($a->id, $sebelum, true));
        $pesan = "Kelas {$kelas->nama_kelas} kini diajar {$kelas->namaAsisten()}.";

        return back()->with('ok', $baru->isEmpty() ? $pesan : $pesan.' '.$this->beriTahuAsisten($request, $kelas, $baru));
    }

    /** Masukkan kelas lama (dibuat asisten sebelum ada matkul) ke matkul ini; asistennya tetap. */
    public function ambil(Request $request, Matkul $matkul)
    {
        MatkulController::pastikanMilik($request, $matkul);
        $id = $request->validate(['kelas_id' => ['required', Rule::exists('kelas', 'id')->whereNull('matkul_id')]])['kelas_id'];
        $kelas = Kelas::findOrFail($id);
        $this->pastikanNamaUnik($matkul, $kelas->nama_kelas);
        $asal = $kelas->mata_kuliah;
        $kelas->update(['matkul_id' => $matkul->id, 'mata_kuliah' => $matkul->nama]);
        LogAktivitas::catat($kelas, 'matkul_tautkan', "{$request->user()->name} memasukkan kelas {$asal} — Kelas {$kelas->nama_kelas} ke mata kuliah {$matkul->label()}");

        return back()->with('ok', "Kelas {$kelas->nama_kelas} ({$kelas->namaAsisten()}) kini bagian dari {$matkul->nama}. Data absen, materi, dan tugasnya tetap.");
    }

    public function destroy(Request $request, Matkul $matkul, Kelas $kelas)
    {
        $this->pastikanKelas($request, $matkul, $kelas);
        LogAktivitas::catat(null, 'hapus_kelas', "{$request->user()->name} menghapus kelas {$kelas->label()} beserta {$kelas->absensi()->count()} data absen",
            ['kelas_id' => $kelas->id, 'label' => $kelas->label()]);
        $kelas->delete();

        return back()->with('ok', "Kelas {$kelas->nama_kelas} dihapus.");
    }

    /** Bobot nilai (kehadiran, tugas, UTS, UAS) bila dikirim dari form. */
    private function bobot(Request $request): array
    {
        return $request->has('bobot_hadir') ? Bobot::validasi($request) : [];
    }

    /** Asisten 1 & 2 dari form; kalau hanya Asisten 2 yang diisi, ia naik menjadi Asisten 1. */
    private function asistenDipilih(Request $request): array
    {
        $aturan = ['nullable', Rule::exists('users', 'id')->where('role', 'asisten')->where('status', 'aktif')];
        $v = $request->validate(
            ['asisten_id' => $aturan, 'asisten2_id' => [...$aturan, 'different:asisten_id']],
            ['asisten2_id.different' => 'Asisten 1 dan Asisten 2 harus orang yang berbeda.'],
        );
        $id = array_values(array_filter([$v['asisten_id'] ?? null, $v['asisten2_id'] ?? null]));

        return ['asisten_id' => isset($id[0]) ? (int) $id[0] : null, 'asisten2_id' => isset($id[1]) ? (int) $id[1] : null];
    }

    private function pastikanNamaUnik(Matkul $matkul, string $nama, ?Kelas $kecuali = null): void
    {
        $ada = $matkul->kelas()->where('nama_kelas', $nama)->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali->id))->exists();
        if ($ada) {
            abort(back()->withErrors(['nama_kelas' => "Kelas {$nama} sudah ada di {$matkul->nama}."])->withInput());
        }
    }

    /** Email ke asisten yang baru ditugaskan; gagal kirim tidak membatalkan penugasan. */
    private function beriTahuAsisten(Request $request, Kelas $kelas, Collection $penerima): string
    {
        try {
            foreach ($penerima as $a) {
                $rekan = $kelas->daftarAsisten()->reject(fn (User $x) => $x->id === $a->id)->first();
                Mail::to($a->email)->send(new Pemberitahuan(
                    "Kamu ditugaskan mengajar {$kelas->label()}",
                    $a->name,
                    ["{$request->user()->name} memilihmu sebagai asisten pengajar {$kelas->label()}"
                        .($kelas->hari ? " ({$kelas->hari}".($kelas->ruang ? ", {$kelas->ruang}" : '').')' : '').'.'
                        .($rekan ? " Kamu mengajar bersama {$rekan->name}." : ''),
                        'Kelas ini sudah muncul di dashboard asistenmu. Dari sana kamu bisa menyalakan QR absen, mengunggah materi, dan menilai tugas.'],
                    route('asisten.kelas.show', $kelas),
                    'Buka kelas',
                ));
            }

            return 'Pemberitahuan sudah dikirim ke email '.($penerima->count() > 1 ? 'keduanya.' : $penerima->first()->name.'.');
        } catch (\Throwable $e) {
            Log::error('Gagal kirim email penugasan asisten', ['kelas' => $kelas->id, 'error' => $e->getMessage()]);

            return 'Email pemberitahuan gagal terkirim, kabari asisten secara langsung.';
        }
    }
}
