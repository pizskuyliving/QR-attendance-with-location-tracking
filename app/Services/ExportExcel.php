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

/** Membuat file rekap absen .xlsx yang siap cetak (A4 landscape, muat satu halaman lebar). */
class ExportExcel
{
    // fromArray() melewati nilai yang == nullValue; pakai penanda agar angka 0 tetap ditulis
    private const TANPA_NULL = "\0kosong";

    public static function buat(Rekap $rekap, string $jenis = 'lengkap'): Spreadsheet
    {
        $kelas = $rekap->kelas;
        $book = new Spreadsheet;
        $book->getProperties()->setTitle('Rekap Absen '.$kelas->label())->setCreator('FLEX — Flexible Learning Experience');

        $ws = $book->getActiveSheet();
        if ($jenis === 'nilai') {
            self::lembarNilai($ws, new RekapNilai($rekap));
            $book->getProperties()->setTitle('Rekap Nilai '.$kelas->label());

            return $book;
        }
        $ws->setTitle('Rekap Absen');
        self::lembarRekap($ws, $rekap, $jenis);

        if ($jenis === 'lengkap') {
            self::lembarPertemuan($book->createSheet(), $rekap);
            self::lembarRiwayatScan($book->createSheet(), $rekap);
            if ($kelas->tugas()->exists() || $kelas->bobot_uts || $kelas->bobot_uas) {
                self::lembarNilai($book->createSheet(), new RekapNilai($rekap));
            }
        }
        $book->setActiveSheetIndex(0);

        return $book;
    }

    public static function lembarRekap(Worksheet $ws, Rekap $rekap, string $jenis): void
    {
        $kelas = $rekap->kelas;
        $n = $kelas->jumlah_pertemuan;
        $denganPertemuan = $jenis !== 'ringkas';

        $judul = ['No', 'NPM', 'Nama'];
        if ($denganPertemuan) {
            for ($p = 1; $p <= $n; $p++) {
                $judul[] = 'P'.$p;
            }
        }
        array_push($judul, 'H', 'I', 'S', 'A', '% Hadir');
        $kolomAkhir = Coordinate::stringFromColumnIndex(count($judul));

        $ws->setCellValue('A1', 'REKAP ABSENSI MAHASISWA');
        $ws->mergeCells("A1:{$kolomAkhir}1");
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $info = [
            ['Mata Kuliah', $kelas->mata_kuliah],
            ['Kelas', $kelas->nama_kelas],
            ['Asisten', $kelas->namaAsisten()],
            ['Dicetak', now()->translatedFormat('d F Y H:i')],
        ];
        foreach ($info as $i => [$k, $v]) {
            $ws->setCellValue('A'.($i + 3), $k);
            $ws->setCellValue('C'.($i + 3), ': '.$v);
            $ws->getStyle('A'.($i + 3))->getFont()->setBold(true);
        }

        $r0 = 8;
        $ws->fromArray($judul, null, "A{$r0}");
        $r = $r0 + 1;
        foreach ($rekap->baris as $i => $b) {
            $data = [$i + 1, $b['mahasiswa']->npm, self::teks($b['mahasiswa']->nama)];
            if ($denganPertemuan) {
                for ($p = 1; $p <= $n; $p++) {
                    $data[] = $b['sel'][$p]?->status ?? '';
                }
            }
            array_push($data, $b['total']['H'], $b['total']['I'], $b['total']['S'], $b['total']['A'], $b['persen'] / 100);
            $ws->fromArray($data, self::TANPA_NULL, "A{$r}");
            $ws->setCellValueExplicit("B{$r}", $b['mahasiswa']->npm, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }
        $rAkhir = max($r - 1, $r0);

        $head = $ws->getStyle("A{$r0}:{$kolomAkhir}{$r0}");
        $head->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $head->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $head->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $tabel = $ws->getStyle("A{$r0}:{$kolomAkhir}{$rAkhir}");
        $tabel->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('999999');
        $ws->getStyle('A'.($r0 + 1).":A{$rAkhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle('D'.($r0 + 1).":{$kolomAkhir}{$rAkhir}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle("{$kolomAkhir}".($r0 + 1).":{$kolomAkhir}{$rAkhir}")->getNumberFormat()->setFormatCode('0.0%');

        // warnai sel status agar mudah dibaca saat dicetak
        if ($denganPertemuan) {
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
        }

        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(14);
        $ws->getColumnDimension('C')->setWidth(32);
        for ($c = 4; $c <= count($judul); $c++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(6);
        }
        $ws->getColumnDimension($kolomAkhir)->setWidth(10);

        $keterangan = $rAkhir + 2;
        $ws->setCellValue("A{$keterangan}", 'Keterangan: '.collect(Absensi::STATUS)->map(fn ($v, $k) => "$k = $v")->implode(', '));
        $ws->getStyle("A{$keterangan}")->getFont()->setItalic(true)->setSize(9);

        self::tandaTangan($ws, $kelas, $keterangan + 2, count($judul));

        $ws->freezePane('D'.($r0 + 1));
        $setup = $ws->getPageSetup();
        $setup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4);
        $setup->setFitToWidth(1)->setFitToHeight(0);
        $setup->setRowsToRepeatAtTopByStartAndEnd($r0, $r0);
        $setup->setPrintArea('A1:'.$kolomAkhir.($keterangan + 6));
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

    private static function lembarNilai(Worksheet $ws, RekapNilai $nilai): void
    {
        $kelas = $nilai->rekap->kelas;
        $ws->setTitle('Rekap Nilai');
        $ws->setCellValue('A1', 'REKAP NILAI '.mb_strtoupper($kelas->label()));
        $ws->setCellValue('A2', "Bobot: kehadiran {$kelas->bobot_hadir}% · tugas {$kelas->bobot_tugas}% · UTS {$kelas->bobot_uts}% · UAS {$kelas->bobot_uas}%. Tugas yang lewat tenggat tanpa kiriman = 0; kiriman belum dinilai belum dihitung.");
        $judul = ['No', 'NPM', 'Nama', 'Hadir (%)'];
        foreach ($nilai->tugas as $t) {
            $judul[] = "P{$t->pertemuan} ".$t->judul;
        }
        array_push($judul, 'Rata-rata tugas', 'UTS', 'UAS', 'Nilai akhir', 'Huruf');
        $ws->fromArray($judul, null, 'A4');
        $r = 5;
        foreach ($nilai->baris as $i => $b) {
            $isi = [$i + 1, $b['mahasiswa']->npm, self::teks($b['mahasiswa']->nama), $b['hadir']];
            foreach ($nilai->tugas as $t) {
                $isi[] = $b['tugas'][$t->id] ?? (isset($b['kiriman'][$t->id]) ? 'belum dinilai' : '-');
            }
            array_push($isi, $b['rata_tugas'] ?? '-', $b['uts'] ?? '-', $b['uas'] ?? '-', $b['akhir'] ?? '-', $b['huruf'] ?? '-');
            $ws->fromArray($isi, self::TANPA_NULL, "A{$r}");
            $ws->setCellValueExplicit("B{$r}", $b['mahasiswa']->npm, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }
        $kolomAkhir = Coordinate::stringFromColumnIndex(count($judul));
        $ws->mergeCells("A1:{$kolomAkhir}1");
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ws->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
        $ws->getStyle("A4:{$kolomAkhir}4")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $ws->getStyle("A4:{$kolomAkhir}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $ws->getStyle("A4:{$kolomAkhir}4")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        if ($r > 5) {
            $ws->getStyle("A4:{$kolomAkhir}".($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $ws->getStyle("D5:{$kolomAkhir}".($r - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $ws->getColumnDimension('A')->setWidth(5);
        $ws->getColumnDimension('B')->setWidth(14);
        $ws->getColumnDimension('C')->setWidth(28);
        for ($c = 4; $c <= count($judul); $c++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(13);
        }
        $ws->getRowDimension(4)->setRowHeight(32);
        $ws->freezePane('D5');
        $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
    }

    /** Cegah teks diawali =, +, - atau @ dibaca Excel sebagai rumus (formula injection). */
    public static function teks(?string $v): string
    {
        $v = (string) $v;

        return preg_match('/^[=+\-@]/', $v) ? ' '.$v : $v;
    }
}
