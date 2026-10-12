<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Materi;
use App\Models\Pengumpulan;
use App\Models\Tugas;
use App\Models\User;
use App\Services\Rekap;
use App\Services\RekapNilai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MateriTugasTest extends TestCase
{
    use RefreshDatabase;

    private User $asisten;

    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->asisten = User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->kelas = Kelas::create(['asisten_id' => $this->asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 4]);
    }

    private function mhs(string $npm, ?Kelas $kelas = null): Mahasiswa
    {
        $m = Mahasiswa::create(['npm' => $npm, 'nama' => 'Mhs '.$npm, 'email' => $npm.'@student.uir.ac.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        if ($kelas !== false) {
            ($kelas ?? $this->kelas)->mahasiswa()->attach($m->id);
        }

        return $m;
    }

    private function tugas(array $ubah = []): Tugas
    {
        return Tugas::create($ubah + ['kelas_id' => $this->kelas->id, 'pertemuan' => 1, 'judul' => 'Laporan ERD', 'tenggat' => now()->addDay(), 'terima_terlambat' => false]);
    }

    public function test_asisten_unggah_materi_dan_hanya_peserta_yang_bisa_unduh(): void
    {
        $this->actingAs($this->asisten, 'web')->post(route('asisten.materi.store', $this->kelas), [
            'pertemuan' => 2, 'judul' => '', 'file' => UploadedFile::fake()->create('Pertemuan 2 - Normalisasi.pptx', 300),
        ])->assertRedirect()->assertSessionHas('ok');
        $m = Materi::firstOrFail();
        $this->assertSame('Pertemuan 2 - Normalisasi', $m->judul);
        Storage::assertExists($m->path);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'materi_tambah']);

        // tautan
        $this->post(route('asisten.materi.store', $this->kelas), ['pertemuan' => 2, 'judul' => 'Video', 'url' => 'https://youtu.be/abc'])->assertSessionHas('ok');
        // validasi: ekstensi, kosong, pertemuan di luar batas, ukuran
        $this->post(route('asisten.materi.store', $this->kelas), ['pertemuan' => 1, 'file' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('file');
        $this->post(route('asisten.materi.store', $this->kelas), ['pertemuan' => 1])->assertSessionHasErrors(['file', 'url']);
        $this->post(route('asisten.materi.store', $this->kelas), ['pertemuan' => 9, 'url' => 'https://a.id'])->assertSessionHasErrors('pertemuan');
        $this->post(route('asisten.materi.store', $this->kelas), ['pertemuan' => 1, 'file' => UploadedFile::fake()->create('besar.pdf', config('absen.maks_unggah_kb') + 1)])->assertSessionHasErrors('file');
        $this->assertSame(2, Materi::count());

        // peserta bisa membuka & mengunduh tanpa perlu absen
        $peserta = $this->mhs('1001');
        $this->actingAs($peserta, 'mahasiswa')->get(route('mhs.matkul', $this->kelas))->assertOk()
            ->assertSee('Pertemuan 2 - Normalisasi')->assertSee('https://youtu.be/abc');
        $this->get(route('mhs.materi.unduh', $m))->assertOk()->assertDownload('Pertemuan 2 - Normalisasi.pptx');

        // bukan peserta ditolak
        $lain = $this->mhs('1002', Kelas::create(['asisten_id' => $this->asisten->id, 'nama_kelas' => 'B', 'mata_kuliah' => 'Jaringan', 'jumlah_pertemuan' => 4]));
        $this->actingAs($lain, 'mahasiswa')->get(route('mhs.matkul', $this->kelas))->assertForbidden();
        $this->get(route('mhs.materi.unduh', $m))->assertForbidden();

        // asisten lain tidak boleh menambah
        $asisten2 = User::create(['name' => 'B', 'email' => 'b@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->actingAs($asisten2, 'web')->post(route('asisten.materi.store', $this->kelas), ['pertemuan' => 1, 'url' => 'https://a.id'])->assertForbidden();

        // hapus materi menghapus filenya
        $this->actingAs($this->asisten, 'web')->delete(route('asisten.materi.destroy', [$this->kelas, $m]))->assertSessionHas('ok');
        Storage::assertMissing($m->path);
    }

    public function test_mahasiswa_kumpul_ganti_dan_terkunci_setelah_dinilai(): void
    {
        $t = $this->tugas();
        $mhs = $this->mhs('1001');
        $this->actingAs($mhs, 'mahasiswa')->get(route('mhs.home'))->assertSee('Laporan ERD');
        $this->get(route('mhs.tugas', $t))->assertOk()->assertSee('Kumpulkan tugas');

        $this->post(route('mhs.tugas.kumpul', $t), ['file' => UploadedFile::fake()->create('erd.exe', 5)])->assertSessionHasErrors('file');
        $this->post(route('mhs.tugas.kumpul', $t), ['file' => UploadedFile::fake()->create('erd-v1.pdf', 50), 'catatan' => 'versi 1'])->assertSessionHas('ok');
        $p1 = Pengumpulan::firstOrFail();
        $this->assertFalse($p1->terlambat);
        Storage::assertExists($p1->path);

        // kirim ulang mengganti file lama, tetap satu baris
        $this->post(route('mhs.tugas.kumpul', $t), ['file' => UploadedFile::fake()->create('erd-v2.pdf', 50)])->assertSessionHas('ok');
        $p2 = Pengumpulan::firstOrFail();
        $this->assertSame(1, Pengumpulan::count());
        $this->assertSame('erd-v2.pdf', $p2->nama_file);
        Storage::assertMissing($p1->path);
        Storage::assertExists($p2->path);
        $this->get(route('mhs.home'))->assertSee('Tidak ada tugas yang menunggu');

        // mahasiswa yang sudah kumpul tidak bisa keluar dari matkul
        $this->delete(route('mhs.kelas.keluar', $this->kelas))->assertSessionHasErrors('kelas');

        // asisten menilai lewat JSON
        $this->actingAs($this->asisten, 'web')->postJson(route('asisten.tugas.nilai', [$t, $p2]), ['nilai' => 88.5, 'komentar' => 'Rapi'])->assertJson(['ok' => true]);
        $this->assertSame(88.5, $p2->fresh()->nilai);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'nilai_tugas']);
        $this->postJson(route('asisten.tugas.nilai', [$t, $p2]), ['nilai' => 120])->assertUnprocessable();
        $this->get(route('asisten.tugas.unduh', [$t, $p2]))->assertOk()->assertDownload('1001_mhs-1001_erd-v2.pdf');
        $this->get(route('asisten.tugas.zip', $t))->assertOk();
        $this->get(route('asisten.tugas.show', $t))->assertOk()->assertSee('erd-v2.pdf');

        // setelah dinilai tidak bisa ganti file
        $this->actingAs($mhs, 'mahasiswa')->get(route('mhs.tugas', $t))->assertSee('88.5')->assertSee('Rapi')->assertDontSee('Kirim ulang');
        $this->post(route('mhs.tugas.kumpul', $t), ['file' => UploadedFile::fake()->create('erd-v3.pdf', 50)])->assertSessionHasErrors('file');
        $this->assertSame('erd-v2.pdf', $p2->fresh()->nama_file);
        $this->get(route('mhs.tugas.unduh', $t))->assertOk();

    }

    public function test_tenggat_terlambat_dan_tutup(): void
    {
        $mhs = $this->mhs('1001');
        $tutup = $this->tugas(['judul' => 'Tutup', 'tenggat' => now()->subHour()]);
        $telat = $this->tugas(['judul' => 'Boleh telat', 'tenggat' => now()->subHour(), 'terima_terlambat' => true]);

        $this->actingAs($mhs, 'mahasiswa')->get(route('mhs.tugas', $tutup))->assertSee('tidak menerima pengumpulan terlambat');
        $this->post(route('mhs.tugas.kumpul', $tutup), ['file' => UploadedFile::fake()->create('a.pdf', 5)])->assertSessionHasErrors('file');
        $this->assertSame(0, Pengumpulan::count());

        $this->post(route('mhs.tugas.kumpul', $telat), ['file' => UploadedFile::fake()->create('a.pdf', 5)])->assertSessionHas('ok');
        $this->assertTrue(Pengumpulan::firstOrFail()->terlambat);
        $this->get(route('mhs.tugas', $telat))->assertSee('Terlambat');
    }

    public function test_rekap_nilai_dan_bobot(): void
    {
        $a = $this->mhs('1001');
        $b = $this->mhs('1002');
        $c = $this->mhs('1003');
        // kehadiran: A hadir 4/4, B 2/4, C 0
        foreach ([1, 2, 3, 4] as $p) {
            Absensi::create(['kelas_id' => $this->kelas->id, 'mahasiswa_id' => $a->id, 'pertemuan' => $p, 'status' => 'H', 'metode' => 'manual']);
        }
        foreach ([1, 2] as $p) {
            Absensi::create(['kelas_id' => $this->kelas->id, 'mahasiswa_id' => $b->id, 'pertemuan' => $p, 'status' => 'H', 'metode' => 'manual']);
        }
        $lewat = $this->tugas(['judul' => 'T1', 'tenggat' => now()->subDay()]);
        $lewat2 = $this->tugas(['judul' => 'T2', 'tenggat' => now()->subDay()]);
        $nanti = $this->tugas(['judul' => 'T3', 'tenggat' => now()->addWeek()]);
        $kirim = fn ($m, $t, $nilai) => Pengumpulan::create(['tugas_id' => $t->id, 'mahasiswa_id' => $m->id, 'path' => 'x', 'nama_file' => 'x.pdf', 'ukuran' => 1,
            'dikumpulkan_at' => now(), 'terlambat' => false, 'nilai' => $nilai, 'dinilai_at' => $nilai === null ? null : now()]);
        $kirim($a, $lewat, 90);
        $kirim($a, $lewat2, 80);
        $kirim($b, $lewat, 70);
        $kirim($b, $lewat2, null); // belum dinilai: tidak dihitung

        $baris = collect((new RekapNilai(new Rekap($this->kelas->fresh())))->baris)->keyBy(fn ($r) => $r['mahasiswa']->npm);
        // bobot default 20/80
        $this->assertSame(85.0, $baris['1001']['rata_tugas']);
        $this->assertSame(88.0, $baris['1001']['akhir']); // (100*20 + 85*80)/100
        $this->assertSame('A', $baris['1001']['huruf']);
        $this->assertSame(70.0, $baris['1002']['rata_tugas']);
        $this->assertSame(1, $baris['1002']['belum_dinilai']);
        $this->assertSame(66.0, $baris['1002']['akhir']); // (50*20 + 70*80)/100
        $this->assertSame('C', $baris['1002']['huruf']);
        $this->assertSame(0.0, $baris['1003']['rata_tugas']); // dua tugas lewat tenggat tanpa kiriman = 0
        $this->assertSame('E', $baris['1003']['huruf']);
        $this->assertNull($baris['1001']['tugas'][$nanti->id]);

        $this->assertSame(100.0, RekapNilai::akhir(100, null, 20, 80)); // belum ada tugas: kehadiran saja

        $this->actingAs($this->asisten, 'web')->put(route('asisten.kelas.bobot', $this->kelas), ['bobot_hadir' => 40])->assertSessionHas('ok');
        $this->assertSame(60, $this->kelas->fresh()->bobot_tugas);
        $this->put(route('asisten.kelas.bobot', $this->kelas), ['bobot_hadir' => 140])->assertSessionHasErrors('bobot_hadir');
        $this->get(route('asisten.kelas.show', [$this->kelas, 'tab' => 'nilai']))->assertOk()->assertSee('1001')->assertSee('91');
        $res = $this->get(route('asisten.absensi.export', [$this->kelas, 'jenis' => 'nilai']))->assertOk();
        $this->assertStringContainsString('spreadsheet', $res->headers->get('content-type'));
    }

    public function test_hapus_mahasiswa_tugas_dan_kelas_ikut_menghapus_file(): void
    {
        $mhs = $this->mhs('1001');
        $t = $this->tugas();
        $this->actingAs($mhs, 'mahasiswa')->post(route('mhs.tugas.kumpul', $t), ['file' => UploadedFile::fake()->create('a.pdf', 5)]);
        $p = Pengumpulan::firstOrFail();
        $this->actingAs($this->asisten, 'web')->delete(route('asisten.mhs.destroy', [$this->kelas, $mhs]));
        $this->assertSame(0, Pengumpulan::count());
        Storage::assertMissing($p->path);

        $mhs2 = $this->mhs('1002');
        $this->actingAs($mhs2, 'mahasiswa')->post(route('mhs.tugas.kumpul', $t), ['file' => UploadedFile::fake()->create('b.pdf', 5)]);
        $p2 = Pengumpulan::firstOrFail();
        $this->actingAs($this->asisten, 'web')->post(route('asisten.materi.store', $this->kelas), ['pertemuan' => 1, 'file' => UploadedFile::fake()->create('m.pdf', 5)])->assertSessionHas('ok');
        $m = Materi::firstOrFail();
        $this->delete(route('asisten.kelas.destroy', $this->kelas));
        $this->assertSame(0, Tugas::count() + Materi::count() + Pengumpulan::count());
        Storage::assertMissing($p2->path);
        Storage::assertMissing($m->path);
    }
}
