<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * "Smart import": membaca file Excel/CSV apa pun yang punya kolom Nama dan NPM,
 * mencari baris judul secara otomatis (tidak harus di baris 1), dan jika ada
 * kolom pertemuan (P1, Pertemuan 1, 1, ...) ikut mengimpor status absennya.
 */
class ImportExcel
{
    public int $baru = 0;

    public int $diperbarui = 0;

    public int $absenDiimpor = 0;

    public array $dilewati = [];

    public function __construct(private Kelas $kelas) {}

    public function jalankan(string $path): void
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        [$barisJudul, $kolom] = $this->cariJudul($rows);
        if ($barisJudul === null) {
            throw new \RuntimeException('Kolom "NPM" dan "Nama" tidak ditemukan. Pastikan ada baris judul berisi NPM dan Nama.');
        }

        DB::transaction(function () use ($rows, $barisJudul, $kolom) {
            for ($i = $barisJudul + 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $npm = $this->bersihkanNpm($r[$kolom['npm']] ?? '');
                $nama = trim(preg_replace('/\s+/', ' ', (string) ($r[$kolom['nama']] ?? '')));

                if ($npm === '' && $nama === '') {
                    continue; // baris kosong
                }
                if ($npm === '' || $nama === '') {
                    $this->dilewati[] = 'Baris '.($i + 1).': NPM atau Nama kosong';
                    continue;
                }

                $mhs = Mahasiswa::where('npm', $npm)->first();
                if ($mhs) {
                    // nama akun yang sudah didaftarkan sendiri oleh mahasiswa tidak ditimpa
                    if ($mhs->nama !== $nama && ! $mhs->terverifikasi()) {
                        $mhs->update(['nama' => $nama]);
                    }
                    $this->diperbarui++;
                } else {
                    // kata sandi awal = NPM
                    $mhs = Mahasiswa::create(['npm' => $npm, 'nama' => $nama, 'password' => $npm]);
                    $this->baru++;
                }
                $this->kelas->mahasiswa()->syncWithoutDetaching([$mhs->id]);

                foreach ($kolom['pertemuan'] as $p => $idx) {
                    $status = Absensi::normalisasi($r[$idx] ?? '');
                    if ($status === null) {
                        continue;
                    }
                    Absensi::updateOrCreate(
                        ['kelas_id' => $this->kelas->id, 'mahasiswa_id' => $mhs->id, 'pertemuan' => $p],
                        ['status' => $status, 'metode' => 'import']
                    );
                    $this->absenDiimpor++;
                }
            }
        });
    }

    /** @return array{0:?int,1:array} */
    private function cariJudul(array $rows): array
    {
        foreach (array_slice($rows, 0, 15, true) as $i => $r) {
            $kolom = ['npm' => null, 'nama' => null, 'pertemuan' => []];
            foreach ($r as $idx => $sel) {
                $t = mb_strtolower(trim((string) $sel));
                if ($t === '') {
                    continue;
                }
                $rapat = preg_replace('/[\s.\-_:]/u', '', $t); // "N P M", "N.P.M", "No. Pokok" -> rapat
                if ($kolom['npm'] === null && preg_match('/^(npm|nim|nopokok|nomorpokok|nomorinduk)/', $rapat)) {
                    $kolom['npm'] = $idx;
                } elseif ($kolom['nama'] === null && preg_match('/^nama/', $t)) {
                    $kolom['nama'] = $idx;
                } elseif (preg_match('/^(?:p|pert|pertemuan|minggu|m)?\s*[-.]?\s*(\d{1,2})$/', $t, $m)) {
                    $p = (int) $m[1];
                    if ($p >= 1 && $p <= $this->kelas->jumlah_pertemuan && ! isset($kolom['pertemuan'][$p])) {
                        $kolom['pertemuan'][$p] = $idx;
                    }
                }
            }
            if ($kolom['npm'] !== null && $kolom['nama'] !== null) {
                return [$i, $kolom];
            }
        }

        return [null, []];
    }

    private function bersihkanNpm(mixed $v): string
    {
        $s = trim((string) $v);
        // Excel sering menyimpan NPM sebagai angka: 1.2345678E7 atau 12345678.0
        if (is_numeric($s) && str_contains(strtolower($s), 'e')) {
            $s = number_format((float) $s, 0, '', '');
        }
        $s = preg_replace('/\.0+$/', '', $s);

        return preg_replace('/\s+/', '', $s);
    }
}
