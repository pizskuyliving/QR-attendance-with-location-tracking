<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Services\ImportExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MahasiswaController extends Controller
{
    public function store(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $data = $request->validate([
            'npm' => 'required|string|max:20',
            'nama' => 'required|string|max:100',
        ]);
        $npm = preg_replace('/\s+/', '', $data['npm']);

        $mhs = Mahasiswa::firstOrCreate(['npm' => $npm], ['nama' => $data['nama'], 'password' => $npm]);
        if ($kelas->mahasiswa()->whereKey($mhs->id)->exists()) {
            return back()->withErrors(['npm' => "NPM $npm sudah ada di kelas ini."])->withInput();
        }
        $kelas->mahasiswa()->attach($mhs->id);
        LogAktivitas::catat($kelas, 'tambah_mahasiswa', "Menambah {$mhs->npm} {$mhs->nama} ke kelas", ['mahasiswa_id' => $mhs->id]);

        return back()->with('ok', "{$mhs->nama} ditambahkan ke kelas.".($mhs->terverifikasi() ? '' : ' Mahasiswa perlu daftar akun sendiri sebelum bisa login.'));
    }

    public function update(Request $request, Kelas $kelas, Mahasiswa $mahasiswa)
    {
        $this->pastikanAnggota($request, $kelas, $mahasiswa);
        $data = $request->validate([
            'npm' => ['required', 'string', 'max:20', Rule::unique('mahasiswa', 'npm')->ignore($mahasiswa->id)],
            'nama' => 'required|string|max:100',
        ]);
        $lama = $mahasiswa->only('npm', 'nama');
        $mahasiswa->update($data);
        if ($lama !== $mahasiswa->only('npm', 'nama')) {
            LogAktivitas::catat($kelas, 'ubah_mahasiswa', "Mengubah data {$lama['npm']} {$lama['nama']} menjadi {$mahasiswa->npm} {$mahasiswa->nama}",
                ['mahasiswa_id' => $mahasiswa->id, 'lama' => $lama, 'baru' => $mahasiswa->only('npm', 'nama')]);
        }

        return back()->with('ok', 'Data mahasiswa diperbarui.');
    }

    public function destroy(Request $request, Kelas $kelas, Mahasiswa $mahasiswa)
    {
        $this->pastikanAnggota($request, $kelas, $mahasiswa);
        $absen = $mahasiswa->absensi()->where('kelas_id', $kelas->id)->orderBy('pertemuan')->pluck('status', 'pertemuan');
        LogAktivitas::catat($kelas, 'hapus_mahasiswa', "Menghapus {$mahasiswa->npm} {$mahasiswa->nama} dari kelas beserta {$absen->count()} data absen",
            ['mahasiswa_id' => $mahasiswa->id, 'npm' => $mahasiswa->npm, 'nama' => $mahasiswa->nama, 'absen' => $absen]);
        $mahasiswa->absensi()->where('kelas_id', $kelas->id)->delete();
        // kiriman tugas di kelas ini ikut dihapus (hook model menghapus filenya)
        \App\Models\Pengumpulan::where('mahasiswa_id', $mahasiswa->id)->whereIn('tugas_id', $kelas->tugas()->pluck('id'))->get()->each->delete();
        $kelas->mahasiswa()->detach($mahasiswa->id);
        // hapus akun bila sudah tidak terdaftar di kelas mana pun
        if (! $mahasiswa->kelas()->exists()) {
            $mahasiswa->delete();
        }

        return back()->with('ok', "{$mahasiswa->nama} dihapus dari kelas.");
    }

    public function resetPassword(Request $request, Kelas $kelas, Mahasiswa $mahasiswa)
    {
        $this->pastikanAnggota($request, $kelas, $mahasiswa);
        // sandi sementara acak (bukan NPM yang mudah ditebak teman sekelas), hanya ditampilkan sekali ke asisten
        $sementara = Str::password(10, symbols: false);
        $mahasiswa->update(['password' => $sementara]);
        LogAktivitas::catat($kelas, 'reset_sandi', "Mereset kata sandi {$mahasiswa->npm} {$mahasiswa->nama}", ['mahasiswa_id' => $mahasiswa->id]);

        return back()->with('ok', "Kata sandi sementara {$mahasiswa->nama}: {$sementara} — berikan langsung ke mahasiswa dan minta segera menggantinya di menu Akun.");
    }

    /** Lepas ikatan HP, misalnya mahasiswa ganti HP atau menghapus data browser. */
    public function resetPerangkat(Request $request, Kelas $kelas, Mahasiswa $mahasiswa)
    {
        $this->pastikanAnggota($request, $kelas, $mahasiswa);
        $mahasiswa->update(['perangkat' => null, 'perangkat_terikat_at' => null]);
        LogAktivitas::catat($kelas, 'reset_perangkat', "Mereset HP terdaftar {$mahasiswa->npm} {$mahasiswa->nama}", ['mahasiswa_id' => $mahasiswa->id]);

        return back()->with('ok', "HP {$mahasiswa->nama} direset. Scan berikutnya akan mengikat HP yang dipakai saat itu.");
    }

    public function import(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $request->validate(['file' => 'required|file|max:5120|mimes:xlsx,xls,csv,ods']);

        $import = new ImportExcel($kelas);
        try {
            $import->jalankan($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Gagal membaca file: '.$e->getMessage()]);
        }

        LogAktivitas::catat($kelas, 'import', "Import Excel {$request->file('file')->getClientOriginalName()}: {$import->baru} baru, {$import->diperbarui} diperbarui, {$import->absenDiimpor} absen",
            ['baru' => $import->baru, 'diperbarui' => $import->diperbarui, 'absen' => $import->absenDiimpor]);
        $pesan = "Import selesai: {$import->baru} mahasiswa baru, {$import->diperbarui} sudah ada/diperbarui";
        if ($import->absenDiimpor) {
            $pesan .= ", {$import->absenDiimpor} data absen ikut diimpor";
        }

        return redirect()->route('asisten.kelas.show', [$kelas, 'tab' => 'mahasiswa'])
            ->with('ok', $pesan.'.')
            ->with('dilewati', $import->dilewati);
    }

    public function template(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $book = new Spreadsheet;
        $ws = $book->getActiveSheet();
        $judul = ['No', 'NPM', 'Nama'];
        for ($p = 1; $p <= $kelas->jumlah_pertemuan; $p++) {
            $judul[] = 'P'.$p;
        }
        $ws->fromArray($judul, null, 'A1');
        $ws->fromArray([1, '', ''], null, 'A2');
        $ws->getStyle('A1:'.$ws->getHighestColumn().'1')->getFont()->setBold(true);
        $ws->getColumnDimension('B')->setWidth(14);
        $ws->getColumnDimension('C')->setWidth(30);
        $ws->getStyle('B:B')->getNumberFormat()->setFormatCode('@');
        $ws->setCellValue('E4', 'Kolom P1..P'.$kelas->jumlah_pertemuan.' opsional. Isi H / I / S / A.');

        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'),
            'template-mahasiswa.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** Kelas milik asisten ini dan mahasiswanya memang anggota kelas itu. */
    private function pastikanAnggota(Request $request, Kelas $kelas, Mahasiswa $mahasiswa): void
    {
        KelasController::pastikanMilik($request, $kelas);
        abort_unless($kelas->mahasiswa()->whereKey($mahasiswa->id)->exists(), 404);
    }
}
