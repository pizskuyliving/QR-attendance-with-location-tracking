<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Kelas;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Membuat file rekap absen & nilai .xlsx yang siap cetak (A4 landscape, muat satu halaman lebar). */
class ExportExcel
{
    // fromArray() melewati nilai yang == nullValue; pakai penanda agar angka 0 tetap ditulis
    private const TANPA_NULL = "\0kosong";

    public static function buat(Rekap $rekap): Spreadsheet
    {
        $kelas = $rekap->kelas;
        $nilai = new RekapNilai($rekap);
        $book = new Spreadsheet;
        $book->getProperties()->setTitle('Rekap '.$kelas->label())->setCreator('FLEX — Flexible Learning Experience');

        self::lembarRekap($book->getActiveSheet(), $rekap, $nilai);
        if ($nilai->tugas->isNotEmpty()) {
            self::lembarTugas($book->createSheet(), $nilai);
        }
        self::lembarPertemuan($book->createSheet(), $rekap);
        self::lembarRiwayatScan($book->createSheet(), $rekap);
        $book->setActiveSheetIndex(0);

        return $book;
    }

    /** Lembar utama: absen per pertemuan dan nilai akhir tiap mahasiswa dalam satu tabel siap cetak. */
    public static function lembarRekap(Worksheet $ws, Rekap $rekap, ?RekapNilai $nilai = null): void
    {
        $kelas = $rekap->kelas;
        $nilai ??= new RekapNilai($rekap);
        $n = $kelas->jumlah_pertemuan;
        $ws->setTitle('Rekap');

        $judul = ['No', 'NPM', 'Nama'];
        for ($p = 1; $p <= $n; $p++) {
            $judul[] = 'P'.$p;
        }
        array_push($judul, 'H', 'I', 'S', 'A', '% Hadir');
        $kolomHadir = count($judul);
        array_push($judul, 'Tugas', 'Kuis', 'UTS', 'UAS', 'Nilai akhir', 'Huruf');
        $kolomAkhir = Coordinate::stringFromColumnIndex(count($judul));
        $hadir = Coordinate::stringFromColumnIndex($kolomHadir);
        $nilaiAwal = Coordinate::stringFromColumnIndex($kolomHadir + 1);
        $nilaiAkhir = Coordinate::stringFromColumnIndex($kolomHadir + 5);

        $ws->setCellValue('A1', 'REKAP ABSENSI & NILAI MAHASISWA');
        $ws->mergeCells("A1:{$kolomAkhir}1");
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $info = [
            ['Mata Kuliah', $kelas->mata_kuliah],
            ['Kelas', $kelas->nama_kelas],
            ['Asisten', $kelas->namaAsisten()],
            ['Bobot nilai', "Kehadiran {$kelas->bobot_hadir}% · Tugas {$kelas->bobot_tugas}% · Kuis {$kelas->bobot_kuis}% · UTS {$kelas->bobot_uts}% · UAS {$kelas->bobot_uas}%"],
            ['Dicetak', now()->translatedFormat('d F Y H:i')],
        ];
        foreach ($info as $i => [$k, $v]) {
            $ws->setCellValue('A'.($i + 3), $k);
            $ws->setCellValue('C'.($i + 3), ': '.$v);
            $ws->getStyle('A'.($i + 3))->getFont()->setBold(true);
        }

        $r0 = 9;
        $ws->fromArray($judul, null, "A{$r0}");
        $r = $r0 + 1;
        foreach ($rekap->baris as $i => $b) {
            $data = [$i + 1, $b['mahasiswa']->npm, self::teks($b['mahasiswa']->nama)];
            for ($p = 1; $p <= $n; $p++) {
                $data[] = $b['sel'][$p]?->status ?? '';
            }
            $v = $nilai->baris[$i];
            array_push($data, $b['total']['H'], $b['total']['I'], $b['total']['S'], $b['total']['A'], $b['persen'] / 100,
                $v['rata_tugas'] ?? '-', $v['rata_kuis'] ?? '-', $v['uts'] ?? '-', $v['uas'] ?? '-', $v['akhir'] ?? '-', $v['huruf'] ?? '-');
            $ws->fromArray($data, self::TANPA_NULL, "A{$r}");
            $ws->setCellValueExplicit("B{$r}", $b['mahasiswa']->npm, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }
        $rAkhir = max($r - 1, $r0);

        $head = $ws->getStyle("A{$r0}:{$kolomAkhir}{$r0}");
        $head->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $head->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $head->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $ws->getRowDimension($r0)->setRowHeight(30);

        $tabel = $ws->getStyle("A{$r0}:{$kolomAkhir}{$rAkhir}");
        $tabel->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('999999');
        $ws->getStyle('A'.($r0 + 1).":A{$rAkhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle('D'.($r0 + 1).":{$kolomAkhir}{$rAkhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle("{$hadir}".($r0 + 1).":{$hadir}{$rAkhir}")->getNumberFormat()->setFormatCode('0.0%');
        $ws->getStyle("{$nilaiAwal}".($r0 + 1).":{$nilaiAkhir}{$rAkhir}")->getNumberFormat()->setFormatCode('0.0');
        // garis tebal pemisah bagian absen dan bagian nilai
        $ws->getStyle("{$nilaiAwal}{$r0}:{$nilaiAwal}{$rAkhir}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $ws->getStyle("{$nilaiAkhir}".($r0 + 1).":{$kolomAkhir}{$rAkhir}")->getFont()->setBold(true);

        // warnai sel status agar mudah dibaca saat dicetak
        $warna = ['H' => 'D9F2E1', 'I' => 'FFF4CC', 'S' => 'DCEBFA', 'A' => 'F9D6D5'];
        foreach ($rekap->baris as $i => $b) {
            for ($p = 1; $p <= $n; $p++) {
                $s = $b['sel'][$p]?->status;
                if ($s) {
                    $c = Coordinate::stringFromColumnIndex(3 + $p).($r0 + 1 + $i);
                    $ws->getStyle($c)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warna[$s]);
                }
            }
        }

        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(14);
        $ws->getColumnDimension('C')->setWidth(32);
        for ($c = 4; $c <= count($judul); $c++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth($c < $kolomHadir ? 5 : 8);
        }
        $ws->getColumnDimension($hadir)->setWidth(9);
        $ws->getColumnDimension($nilaiAkhir)->setWidth(9);

        $keterangan = $rAkhir + 2;
        $ws->setCellValue("A{$keterangan}", 'Keterangan: '.collect(Absensi::STATUS)->map(fn ($v, $k) => "$k = $v")->implode(', ').'. % Hadir = jumlah Hadir ÷ '.$n.' pertemuan.');
        $ws->setCellValue('A'.($keterangan + 1), 'Nilai akhir = rata-rata berbobot kehadiran, tugas, kuis, UTS, dan UAS; komponen yang belum ada nilainya dilewati. Tugas lewat tenggat tanpa kiriman = 0; kiriman belum dinilai belum dihitung. Kuis yang tidak dijawab = 0.');
        $ws->getStyle("A{$keterangan}:A".($keterangan + 1))->getFont()->setItalic(true)->setSize(9);

        self::tandaTangan($ws, $kelas, $keterangan + 3, count($judul));

        $ws->freezePane('D'.($r0 + 1));
        $setup = $ws->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4);
        $setup->setFitToWidth(1)->setFitToHeight(0);
        $setup->setRowsToRepeatAtTopByStartAndEnd($r0, $r0);
        $setup->setPrintArea('A1:'.$kolomAkhir.($keterangan + 7));
        $ws->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
    }

    /**
     * Blok tanda tangan sejajar: dosen pengampu di kolom C, lalu tiap asisten pengajar
     * di sisa kolom (dibagi rata, digabung, rata tengah). Nama kosong diberi garis titik.
     */
    private static function tandaTangan(Worksheet $ws, Kelas $kelas, int $baris, int $jumlahKolom): void
    {
        $blok = [];
        if ($kelas->matkul_id && $kelas->matkul?->dosen) {
            $blok[] = ['Dosen Pengampu,', $kelas->matkul->dosen->name];
        }
        $asisten = $kelas->daftarAsisten();
        foreach ($asisten as $i => $a) {
            $blok[] = [$asisten->count() > 1 ? 'Asisten '.($i + 1).',' : 'Asisten,', $a->name];
        }
        if ($asisten->isEmpty()) {
            $blok[] = ['Asisten,', null];
        }

        // kolom C untuk blok pertama; blok lain berbagi kolom D s.d. kolom terakhir
        $rentang = [['C', 'C']];
        $sisa = count($blok) - 1;
        if ($sisa > 0) {
            $lebar = intdiv($jumlahKolom - 3, $sisa);
            for ($i = 0; $i < $sisa; $i++) {
                $dari = 4 + $i * $lebar;
                $sampai = $i === $sisa - 1 ? $jumlahKolom : $dari + $lebar - 1;
                $rentang[] = [Coordinate::stringFromColumnIndex($dari), Coordinate::stringFromColumnIndex($sampai)];
            }
        }

        $nama = $baris + 4;
        foreach ($blok as $i => [$label, $orang]) {
            [$a, $b] = $rentang[$i];
            foreach ([$baris, $nama] as $r) {
                if ($a !== $b) {
                    $ws->mergeCells("{$a}{$r}:{$b}{$r}");
                }
                $ws->getStyle("{$a}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setShrinkToFit(true);
            }
            $ws->setCellValue("{$a}{$baris}", $label);
            $ws->setCellValue("{$a}{$nama}", $orang ? self::teks($orang) : '(..............................)');
            if ($orang) {
                $ws->getStyle("{$a}{$nama}")->getFont()->setBold(true)->setUnderline(true);
            }
        }
    }

    private static function lembarPertemuan(Worksheet $ws, Rekap $rekap): void
    {
        $ws->setTitle('Per Pertemuan');
        $ws->fromArray(['Pertemuan', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Belum diisi', '% Hadir'], null, 'A1');
        $total = count($rekap->baris);
        $r = 2;
        foreach ($rekap->perPertemuan as $p => $c) {
            $ws->fromArray(['Pertemuan '.$p, $c['H'], $c['I'], $c['S'], $c['A'], $c['kosong'], $total ? $c['H'] / $total : 0], self::TANPA_NULL, "A{$r}");
            $r++;
        }
        $ws->getStyle('A1:G1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $ws->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $ws->getStyle('A1:G'.($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $ws->getStyle('G2:G'.($r - 1))->getNumberFormat()->setFormatCode('0.0%');
        foreach (range('A', 'G') as $c) {
            $ws->getColumnDimension($c)->setWidth($c === 'A' ? 14 : 12);
        }
        $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setPaperSize(PageSetup::PAPERSIZE_A4);
    }

    private static function lembarRiwayatScan(Worksheet $ws, Rekap $rekap): void
    {
        $ws->setTitle('Riwayat Scan');
        $ws->fromArray(['Waktu', 'Pertemuan', 'NPM', 'Nama', 'Latitude', 'Longitude', 'Akurasi (m)', 'Peta', 'Jarak (m)', 'Tanda mencurigakan'], null, 'A1');
        $scan = $rekap->kelas->absensi()->with('mahasiswa')->where('metode', 'qr')->orderBy('waktu')->get();
        $r = 2;
        foreach ($scan as $a) {
            $ws->fromArray([$a->waktu?->format('Y-m-d H:i:s'), $a->pertemuan, $a->mahasiswa->npm, self::teks($a->mahasiswa->nama),
                $a->lat, $a->lng, $a->akurasi, $a->linkPeta(), $a->jarak, implode('; ', $a->tanda ?? [])], self::TANPA_NULL, "A{$r}");
            $ws->setCellValueExplicit("C{$r}", $a->mahasiswa->npm, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            if ($a->linkPeta()) {
                $ws->getCell("H{$r}")->setValue('Buka peta')->getHyperlink()->setUrl($a->linkPeta());
            }
            $r++;
        }
        $ws->getStyle('A1:J1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $ws->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        foreach (['A' => 20, 'B' => 10, 'C' => 14, 'D' => 28, 'E' => 13, 'F' => 13, 'G' => 12, 'H' => 12, 'I' => 10, 'J' => 40] as $c => $w) {
            $ws->getColumnDimension($c)->setWidth($w);
        }
        $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
    }

    /** Rincian nilai tiap tugas; rata-ratanya sudah ada di lembar Rekap. */
    private static function lembarTugas(Worksheet $ws, RekapNilai $nilai): void
    {
        $ws->setTitle('Nilai Tugas');
        $judul = ['No', 'NPM', 'Nama'];
        foreach ($nilai->tugas as $t) {
            $judul[] = "P{$t->pertemuan} ".self::teks($t->judul);
        }
        $judul[] = 'Rata-rata tugas';
        $ws->fromArray($judul, null, 'A1');
        $r = 2;
        foreach ($nilai->baris as $i => $b) {
            $isi = [$i + 1, $b['mahasiswa']->npm, self::teks($b['mahasiswa']->nama)];
            foreach ($nilai->tugas as $t) {
                $isi[] = $b['tugas'][$t->id] ?? (isset($b['kiriman'][$t->id]) ? 'belum dinilai' : '-');
            }
            $isi[] = $b['rata_tugas'] ?? '-';
            $ws->fromArray($isi, self::TANPA_NULL, "A{$r}");
            $ws->setCellValueExplicit("B{$r}", $b['mahasiswa']->npm, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }
        $kolomAkhir = Coordinate::stringFromColumnIndex(count($judul));
        $rAkhir = max($r - 1, 1);
        $ws->getStyle("A1:{$kolomAkhir}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $ws->getStyle("A1:{$kolomAkhir}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $ws->getStyle("A1:{$kolomAkhir}1")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $ws->getStyle("A1:{$kolomAkhir}{$rAkhir}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $ws->getStyle("D2:{$kolomAkhir}{$rAkhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(14);
        $ws->getColumnDimension('C')->setWidth(28);
        for ($c = 4; $c <= count($judul); $c++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(13);
        }
        $ws->getRowDimension(1)->setRowHeight(32);
        $ws->freezePane('D2');
        $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
    }

    /** Cegah teks diawali =, +, - atau @ dibaca Excel sebagai rumus (formula injection). */
    public static function teks(?string $v): string
    {
        $v = (string) $v;

        return preg_match('/^[=+\-@]/', $v) ? ' '.$v : $v;
    }
}
