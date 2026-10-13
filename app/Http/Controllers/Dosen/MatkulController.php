<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Mail\Pemberitahuan;
use App\Models\CatatanDosen;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Matkul;
use App\Models\User;
use App\Services\ExportMatkul;
use App\Services\Pantauan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Mata kuliah dosen dan pantauan kelas-kelasnya. Admin melihat semua matkul. */
class MatkulController extends Controller
{
    public static function pastikanMilik(Request $request, Matkul $matkul): void
    {
        $u = $request->user();
        abort_unless($u->role === 'admin' || $matkul->dosen_id === $u->id, 403, 'Mata kuliah ini bukan milik Anda.');
    }

    public function index(Request $request)
    {
        $u = $request->user();
        $matkul = Matkul::with(['dosen', 'kelas.asisten', 'kelas.asisten2', 'kelas.mahasiswa', 'kelas.catatanDosen', 'kelas.soalUjian'])
            ->when($u->role !== 'admin', fn ($q) => $q->where('dosen_id', $u->id))
            ->orderBy('nama')->get()
            ->map(fn (Matkul $m) => ['matkul' => $m, 'kelas' => $m->kelas->map(fn (Kelas $k) => Pantauan::untuk($k))]);

        $dosen = $u->role === 'admin' ? User::where('role', 'dosen')->where('status', 'aktif')->orderBy('name')->get() : collect();
        // pilihan asisten pengajar: semua akun asisten aktif
        $asisten = User::where('role', 'asisten')->where('status', 'aktif')->orderBy('name')->get(['id', 'name', 'email']);
        // kelas lama (dibuat asisten sebelum ada matkul) yang bisa dimasukkan ke matkul dosen
        $kelasLama = Kelas::with('asisten', 'asisten2')->whereNull('matkul_id')->orderBy('mata_kuliah')->orderBy('nama_kelas')->get();

        return view('dosen.matkul', compact('matkul', 'dosen', 'asisten', 'kelasLama'));
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['dosen_id'] = $request->user()->role === 'admin' ? $request->validate([
            'dosen_id' => ['required', Rule::exists('users', 'id')->where('role', 'dosen')],
        ])['dosen_id'] : $request->user()->id;
        $m = Matkul::create($data);

        return back()->with('ok', "Mata kuliah {$m->nama} dibuat. Tambahkan kelasnya dan pilih asisten pengajar.");
    }

    public function update(Request $request, Matkul $matkul)
    {
        self::pastikanMilik($request, $matkul);
        $matkul->update($this->validasi($request));
        $matkul->kelas()->update(['mata_kuliah' => $matkul->nama]); // nama yang dilihat mahasiswa ikut berubah

        return back()->with('ok', 'Mata kuliah diperbarui.');
    }

    public function export(Request $request, Matkul $matkul)
    {
        self::pastikanMilik($request, $matkul);
        $book = ExportMatkul::buat($matkul);
        $nama = 'rekap-matkul-'.Str::slug($matkul->kode ? $matkul->kode.'-'.$matkul->nama : $matkul->nama).'.xlsx';

        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), $nama,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** Catatan dosen untuk asisten kelas: tampil di dashboard & halaman kelas asisten, dan dikirim lewat email. */
    public function catatan(Request $request, Matkul $matkul, Kelas $kelas)
    {
        self::pastikanMilik($request, $matkul);
        abort_unless($kelas->matkul_id === $matkul->id, 404);
        $isi = trim($request->validate(['isi' => 'required|string|max:1000'])['isi']);
        $asisten = $kelas->daftarAsisten();
        if ($asisten->isEmpty()) {
            return back()->withErrors(['isi' => "Kelas {$kelas->nama_kelas} belum punya asisten. Pilih asistennya dulu."]);
        }
        CatatanDosen::create(['kelas_id' => $kelas->id, 'dosen_id' => $request->user()->id, 'isi' => $isi]);
        LogAktivitas::catat($kelas, 'catatan_dosen', "{$request->user()->name} mengirim catatan ke asisten");

        $nama = $asisten->pluck('name')->implode(' & ');
        try {
            foreach ($asisten as $a) {
                Mail::to($a->email)->send(new Pemberitahuan(
                    "Catatan dosen: {$kelas->label()}",
                    $a->name,
                    ["{$request->user()->name} mengirim catatan untuk kelas {$kelas->label()}:", $isi],
                    route('asisten.kelas.show', $kelas),
                    'Buka kelas',
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Gagal kirim email catatan dosen', ['kelas' => $kelas->id, 'error' => $e->getMessage()]);

            return back()->with('ok', "Catatan tersimpan dan tampil di dashboard {$nama}, tetapi emailnya gagal terkirim.");
        }

        return back()->with('ok', "Catatan terkirim ke {$nama} (dashboard dan email).");
    }

    public function destroy(Request $request, Matkul $matkul)
    {
        self::pastikanMilik($request, $matkul);
        if ($matkul->kelas()->exists()) {
            return back()->withErrors(['matkul' => "Hapus dulu kelas-kelas {$matkul->nama} sebelum menghapus mata kuliahnya."]);
        }
        $matkul->delete();

        return back()->with('ok', "Mata kuliah {$matkul->nama} dihapus.");
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => 'nullable|string|max:20',
            'semester' => 'nullable|string|max:30',
        ]);
    }
}
