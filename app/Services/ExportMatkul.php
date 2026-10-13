<?php

namespace App\Services;

use App\Models\Matkul;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Rekap gabungan satu mata kuliah untuk dosen: ringkasan per kelas & kinerja asisten,
 * daftar semua mahasiswa lintas kelas, lalu satu lembar rekap absen per kelas.
 */
class ExportMatkul
{
    private const TANPA_NULL = "\0kosong";

    public static function buat(Matkul $matkul): Spreadsheet
    {
        $matkul->loadMissing('dosen', 'kelas.asisten', 'kelas.mahasiswa');
        $pantau = $matkul->kelas->map(fn ($k) => Pantauan::untuk($k));

        $book = new Spreadsheet;
        $book->getProperties()->setTitle('Rekap '.$matkul->label())->setCreator('FLEX — Flexible Learning Experience');
        self::lembarRingkasan($book->getActiveSheet(), $matkul, $pantau->all());
        self::lembarMahasiswa($book->createSheet(), $pantau->all());
        $dipakai = [];
        foreach ($pantau as $p) {
            // nama lembar harus unik; dua asisten bisa sama-sama memegang "Kelas A"
            $judul = 'Absen Kelas '.$p['kelas']->nama_kelas;
            $dipakai[$judul] = ($dipakai[$judul] ?? 0) + 1;
            $ws = $book->createSheet();
            $ws->setTitle(mb_substr($judul.($dipakai[$judul] > 1 ? " ({$dipakai[$judul]})" : ''), 0, 31));
            ExportExcel::lembarRekap($ws, $p['rekap'], 'lengkap');
        }
        $book->setActiveSheetIndex(0);

        return $book;
    }

    private static function lembarRingkasan(Worksheet $ws, Matkul $matkul, array $pantau): void
    {
        $ws->setTitle('Ringkasan');
        $judul = ['Kelas', 'Asisten', 'Mahasiswa', 'Pertemuan', 'Rata-rata hadir', 'Mendekati/lewat batas', 'Sesi terakhir',
            'Sesi di luar jadwal', 'Pertemuan tanpa materi', 'Kiriman belum dinilai', 'Rata-rata lama menilai (hari)',
            'Ubah absen manual', 'Scan janggal', 'Perlu perhatian'];
        $akhir = Coordinate::stringFromColumnIndex(count($judul));

        $ws->setCellValue('A1', 'REKAP MATA KULIAH '.mb_strtoupper($matkul->label()));
        $ws->mergeCells("A1:{$akhir}1");
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $info = [['Dosen', $matkul->dosen->name], ['Semester', $matkul->semester ?: '-'], ['Dicetak', now()->translatedFormat('d F Y H:i')]];
        foreach ($info as $i => [$k, $v]) {
            $ws->setCellValue('A'.($i + 3), $k);
            $ws->setCellValue('B'.($i + 3), ': '.ExportExcel::teks($v));
            $ws->getStyle('A'.($i + 3))->getFont()->setBold(true);
        }

        $r0 = 7;
        $ws->fromArray($judul, null, "A{$r0}");
        $r = $r0 + 1;
        foreach ($pantau as $p) {
            $k = $p['kelas'];
            $ws->fromArray([
                'Kelas '.$k->nama_kelas, ExportExcel::teks($k->namaAsisten()), $p['mahasiswa'], "{$p['terlaksana']}/{$k->jumlah_pertemuan}",
                $p['rata'] / 100, $p['terancam'], $p['sesiTerakhir']?->format('Y-m-d H:i') ?? 'Belum ada',
                $p['luarJadwal'] ?? '-', $p['tanpaMateri'], $p['belumDinilai'], $p['lamaMenilai'] ?? '-',
                $p['ubahManual'], $p['janggal'], implode('; ', $p['perhatian']) ?: 'Aman',
            ], self::TANPA_NULL, "A{$r}");
            $r++;
        }
        $rAkhir = max($r - 1, $r0);
        self::gayaTabel($ws, "A{$r0}:{$akhir}{$r0}", "A{$r0}:{$akhir}{$rAkhir}");
        $ws->getStyle("A{$r0}:{$akhir}{$r0}")->getAlignment()->setWrapText(true);
        $ws->getRowDimension($r0)->setRowHeight(32);
        $ws->getStyle('E'.($r0 + 1).":E{$rAkhir}")->getNumberFormat()->setFormatCode('0.0%');
        $ws->getStyle('C'.($r0 + 1).":M{$rAkhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle('N'.($r0 + 1).":N{$rAkhir}")->getAlignment()->setWrapText(true);
        foreach (['A' => 10, 'B' => 24, 'C' => 11, 'D' => 11, 'E' => 11, 'F' => 13, 'G' => 17, 'H' => 11, 'I' => 12, 'J' => 12, 'K' => 14, 'L' => 11, 'M' => 10, 'N' => 48] as $c => $w) {
            $ws->getColumnDimension($c)->setWidth($w);
        }
        $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
    }

    private static function lembarMahasiswa(Worksheet $ws, array $pantau): void
    {
        $ws->setTitle('Semua Mahasiswa');
        $judul = ['No', 'Kelas', 'NPM', 'Nama', 'H', 'I', 'S', 'A', '% Hadir', 'Status kehadiran', 'Rata-rata tugas', 'UTS', 'UAS', 'Nilai akhir', 'Huruf'];
        $akhir = Coordinate::stringFromColumnIndex(count($judul));
        $ws->fromArray($judul, null, 'A1');
        $r = 2;
        foreach ($pantau as $p) {
            $nilai = collect((new RekapNilai($p['rekap']))->baris)->keyBy(fn ($b) => $b['mahasiswa']->id);
            foreach ($p['rekap']->baris as $b) {
                $m = $b['mahasiswa'];
                $n = $nilai[$m->id] ?? null;
                $ws->fromArray([
                    $r - 1, $p['kelas']->nama_kelas, $m->npm, ExportExcel::teks($m->nama),
                    $b['total']['H'], $b['total']['I'], $b['total']['S'], $b['total']['A'], $b['persen'] / 100,
                    $b['kehadiran']->teks(), $n['rata_tugas'] ?? '-', $n['uts'] ?? '-', $n['uas'] ?? '-', $n['akhir'] ?? '-', $n['huruf'] ?? '-',
                ], self::TANPA_NULL, "A{$r}");
                $ws->setCellValueExplicit("C{$r}", $m->npm, DataType::TYPE_STRING);
                if (in_array($b['kehadiran']->level(), ['batas', 'lewat'], true)) {
                    $ws->getStyle("J{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9D6D5');
                }
                $r++;
            }
        }
        $rAkhir = max($r - 1, 1);
        self::gayaTabel($ws, "A1:{$akhir}1", "A1:{$akhir}{$rAkhir}");
        $ws->getStyle("I2:I{$rAkhir}")->getNumberFormat()->setFormatCode('0.0%');
        $ws->getStyle("K2:N{$rAkhir}")->getNumberFormat()->setFormatCode('0.0');
        $ws->getStyle("A2:B{$rAkhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle("E2:I{$rAkhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle("K2:O{$rAkhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (['A' => 5, 'B' => 7, 'C' => 14, 'D' => 28, 'E' => 5, 'F' => 5, 'G' => 5, 'H' => 5, 'I' => 9, 'J' => 44, 'K' => 12, 'L' => 8, 'M' => 8, 'N' => 11, 'O' => 7] as $c => $w) {
            $ws->getColumnDimension($c)->setWidth($w);
        }
        $ws->setAutoFilter("A1:{$akhir}{$rAkhir}");
        $ws->freezePane('E2');
        $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
    }

    private static function gayaTabel(Worksheet $ws, string $kepala, string $tabel): void
    {
        $h = $ws->getStyle($kepala);
        $h->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $h->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $h->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $ws->getStyle($tabel)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('999999');
    }
}
