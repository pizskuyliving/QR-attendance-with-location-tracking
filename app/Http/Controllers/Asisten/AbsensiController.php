<?php

namespace App\Http\Controllers\Asisten;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Services\ExportExcel;
use App\Services\Rekap;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AbsensiController extends Controller
{
    /** Ubah / hapus satu sel absen (dipanggil dari tabel rekap). */
    public function simpanSel(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $data = $request->validate([
            'mahasiswa_id' => ['required', 'integer', Rule::exists('kelas_mahasiswa', 'mahasiswa_id')->where('kelas_id', $kelas->id)],
            'pertemuan' => 'required|integer|min:1|max:'.$kelas->jumlah_pertemuan,
            'status' => ['nullable', Rule::in(array_keys(Absensi::STATUS))],
        ]);
        $kunci = ['kelas_id' => $kelas->id, 'mahasiswa_id' => $data['mahasiswa_id'], 'pertemuan' => $data['pertemuan']];
        $lama = Absensi::where($kunci)->first();
        $baru = $data['status'] ?: null;
        if ($lama?->status !== $baru) {
            $m = Mahasiswa::find($data['mahasiswa_id']);
            $teks = fn ($s) => $s ? Absensi::STATUS[$s] : 'kosong';
            LogAktivitas::catat($kelas, 'ubah_absen',
                "Absen {$m->npm} {$m->nama} P{$data['pertemuan']}: {$teks($lama?->status)} → {$teks($baru)}",
                ['mahasiswa_id' => $m->id, 'pertemuan' => (int) $data['pertemuan'], 'lama' => $lama?->status, 'baru' => $baru,
                    'metode_lama' => $lama?->metode]);
        }

        if (empty($data['status'])) {
            Absensi::where($kunci)->delete();
        } else {
            $a = Absensi::firstOrNew($kunci);
            $a->status = $data['status'];
            $a->metode = $a->exists && $a->metode === 'qr' && $data['status'] === 'H' ? 'qr' : 'manual';
            $a->waktu ??= now();
            $a->save();
        }

        return response()->json(['ok' => true]);
    }

    /** Isi satu pertemuan sekaligus (mis. semua yang kosong jadi Alpa). */
    public function isiMassal(Request $request, Kelas $kelas)
    {
        KelasController::pastikanMilik($request, $kelas);
        $data = $request->validate([
            'pertemuan' => 'required|integer|min:1|max:'.$kelas->jumlah_pertemuan,
            'status' => ['required', Rule::in(array_merge(array_keys(Absensi::STATUS), ['hapus']))],
        ]);

        if ($data['status'] === 'hapus') {
            $jumlah = Absensi::where(['kelas_id' => $kelas->id, 'pertemuan' => $data['pertemuan']])->delete();
            LogAktivitas::catat($kelas, 'hapus_massal', "Menghapus semua absen pertemuan {$data['pertemuan']} ({$jumlah} data)",
                ['pertemuan' => (int) $data['pertemuan'], 'jumlah' => $jumlah]);

            return back()->with('ok', "Semua absen pertemuan {$data['pertemuan']} dihapus.");
        }

        $sudah = Absensi::where(['kelas_id' => $kelas->id, 'pertemuan' => $data['pertemuan']])->pluck('mahasiswa_id');
        $jumlah = 0;
        foreach ($kelas->mahasiswa()->whereNotIn('mahasiswa.id', $sudah)->get() as $m) {
            Absensi::create(['kelas_id' => $kelas->id, 'mahasiswa_id' => $m->id, 'pertemuan' => $data['pertemuan'],
                'status' => $data['status'], 'metode' => 'manual', 'waktu' => now()]);
            $jumlah++;
        }
        LogAktivitas::catat($kelas, 'isi_massal', "Mengisi {$jumlah} absen kosong pertemuan {$data['pertemuan']} menjadi ".Absensi::STATUS[$data['status']],
            ['pertemuan' => (int) $data['pertemuan'], 'status' => $data['status'], 'jumlah' => $jumlah]);

        return back()->with('ok', "$jumlah mahasiswa yang belum diisi pada pertemuan {$data['pertemuan']} ditandai ".Absensi::STATUS[$data['status']].'.');
    }

    public function export(Request $request, Kelas $kelas)
    {
        KelasController::pastikanBisaLihat($request, $kelas);
        $jenis = in_array($request->query('jenis'), ['lengkap', 'ringkas', 'nilai'], true) ? $request->query('jenis') : 'lengkap';
        $book = ExportExcel::buat(new Rekap($kelas->load('mahasiswa', 'asisten', 'asisten2')), $jenis);
        $nama = ($jenis === 'nilai' ? 'rekap-nilai-' : 'rekap-absen-').Str::slug($kelas->mata_kuliah.'-'.$kelas->nama_kelas).'.xlsx';

        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), $nama,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
