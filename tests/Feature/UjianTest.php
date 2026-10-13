<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\JawabanUjian;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Matkul;
use App\Models\SoalUjian;
use App\Models\User;
use App\Services\Rekap;
use App\Services\RekapNilai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UjianTest extends TestCase
{
    use RefreshDatabase;

    private User $asisten;

    private User $dosen;

    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->asisten = User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->dosen = User::create(['name' => 'Dosen', 'email' => 'dosen@kampus.ac.id', 'password' => 'dosen12345', 'role' => 'dosen']);
        $matkul = Matkul::create(['dosen_id' => $this->dosen->id, 'nama' => 'Basis Data']);
        $this->kelas = Kelas::create(['matkul_id' => $matkul->id, 'asisten_id' => $this->asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 2]);
    }

    private function mhs(string $npm): Mahasiswa
    {
        $m = Mahasiswa::create(['npm' => $npm, 'nama' => 'Mhs '.$npm, 'email' => $npm.'@student.uir.ac.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        $this->kelas->mahasiswa()->attach($m->id);

        return $m;
    }

    public function test_nilai_uts_uas_masuk_nilai_akhir_sesuai_bobot(): void
    {
        $a = $this->mhs('1001');
        $b = $this->mhs('1002');
        foreach ([1, 2] as $p) {
            Absensi::create(['kelas_id' => $this->kelas->id, 'mahasiswa_id' => $a->id, 'pertemuan' => $p, 'status' => 'H']);
        }
        $this->actingAs($this->asisten, 'web')->put(route('asisten.kelas.bobot', $this->kelas),
            ['bobot_hadir' => 20, 'bobot_tugas' => 0, 'bobot_uts' => 30, 'bobot_uas' => 50])->assertSessionHas('ok');

        $this->put(route('asisten.kelas.nilai-ujian', $this->kelas), [
            'nilai_uts' => [$a->id => 80, $b->id => ''],
            'nilai_uas' => [$a->id => 90, $b->id => 101],
        ])->assertSessionHasErrors('nilai_uas.'.$b->id);
        $this->put(route('asisten.kelas.nilai-ujian', $this->kelas), [
            'nilai_uts' => [$a->id => 80, $b->id => ''],
            'nilai_uas' => [$a->id => 90, $b->id => 60],
        ])->assertSessionHas('ok');
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'nilai_ujian']);

        $baris = collect((new RekapNilai(new Rekap($this->kelas->fresh())))->baris)->keyBy(fn ($x) => $x['mahasiswa']->npm);
        // 20% × 100 + 30% × 80 + 50% × 90 = 89
        $this->assertEquals(89, $baris['1001']['akhir']);
        $this->assertEquals(80, $baris['1001']['uts']);
        // UTS kosong dilewati: (20 × 0 + 50 × 60) / 70
        $this->assertEquals(round(3000 / 70, 1), $baris['1002']['akhir']);

        $this->get(route('asisten.kelas.show', [$this->kelas, 'tab' => 'nilai']))->assertOk()->assertSee('Simpan nilai UTS/UAS')->assertDontSee('Periksa jawaban');
        $this->get(route('asisten.kelas.show', [$this->kelas, 'tab' => 'ujian']))->assertOk()->assertSee('Periksa jawaban (0)')->assertSee('Unggah soal');
        $this->get(route('asisten.absensi.export', [$this->kelas, 'jenis' => 'nilai']))->assertOk();
    }

    public function test_asisten_unggah_soal_dan_hak_aksesnya(): void
    {
        $peserta = $this->mhs('1001');
        $this->actingAs($this->asisten, 'web')->post(route('asisten.soal.store', $this->kelas), [
            'jenis' => 'uts', 'judul' => '', 'file' => UploadedFile::fake()->create('Soal UTS Paket A.pdf', 200),
        ])->assertRedirect()->assertSessionHas('ok');
        $soal = SoalUjian::firstOrFail();
        $this->assertSame('Soal UTS Paket A', $soal->judul);
        $this->assertFalse($soal->tampil_mahasiswa);
        Storage::assertExists($soal->path);
        $this->post(route('asisten.soal.store', $this->kelas), ['jenis' => 'kuis', 'file' => UploadedFile::fake()->create('a.pdf', 5)])->assertSessionHasErrors('jenis');
        $this->post(route('asisten.soal.store', $this->kelas), ['jenis' => 'uas', 'file' => UploadedFile::fake()->create('a.exe', 5)])->assertSessionHasErrors('file');
        $this->get(route('asisten.soal.unduh', [$this->kelas, $soal]))->assertOk()->assertDownload('Soal UTS Paket A.pdf');

        // dosen pengampu bisa mengunduh, asisten lain tidak
        $this->actingAs($this->dosen, 'web')->get(route('asisten.soal.unduh', [$this->kelas, $soal]))->assertOk();
        $this->get(route('asisten.matkul.index'))->assertSee('UTS: Soal UTS Paket A');
        $lain = User::create(['name' => 'Lain', 'email' => 'lain@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->actingAs($lain, 'web')->get(route('asisten.soal.unduh', [$this->kelas, $soal]))->assertForbidden();
        $this->post(route('asisten.soal.store', $this->kelas), ['jenis' => 'uts', 'file' => UploadedFile::fake()->create('a.pdf', 5)])->assertForbidden();

        // mahasiswa: tersembunyi sampai dibagikan
        $this->actingAs($peserta, 'mahasiswa')->get(route('mhs.soal.unduh', $soal))->assertNotFound();
        $this->get(route('mhs.matkul', $this->kelas))->assertDontSee('Ujian Tengah Semester');
        $this->get(route('mhs.ujian', [$this->kelas, 'uts']))->assertDontSee('Soal UTS Paket A');
        $this->actingAs($this->asisten, 'web')->put(route('asisten.soal.tampil', [$this->kelas, $soal]))->assertSessionHas('ok');
        $this->assertTrue($soal->fresh()->tampil_mahasiswa);
        $this->actingAs($peserta, 'mahasiswa')->get(route('mhs.matkul', $this->kelas))->assertSee('Ujian Tengah Semester');
        $this->get(route('mhs.ujian', [$this->kelas, 'uts']))->assertSee('Soal UTS Paket A');
        $this->get(route('mhs.soal.unduh', $soal))->assertOk();
        $bukan = Mahasiswa::create(['npm' => '2002', 'nama' => 'Bukan', 'email' => '2002@student.uir.ac.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        $this->actingAs($bukan, 'mahasiswa')->get(route('mhs.soal.unduh', $soal))->assertForbidden();

        // hapus menghapus file juga
        $this->actingAs($this->asisten, 'web')->delete(route('asisten.soal.destroy', [$this->kelas, $soal]))->assertSessionHas('ok');
        Storage::assertMissing($soal->path);
        $this->assertSame(0, SoalUjian::count());
    }

    public function test_mahasiswa_unggah_jawaban_ujian_lalu_dinilai_asisten(): void
    {
        $a = $this->mhs('1001');
        $b = $this->mhs('1002');
        $berkas = fn ($n = 'jawaban.pdf') => UploadedFile::fake()->create($n, 100);

        // belum dibuka
        $this->actingAs($a, 'mahasiswa')->get(route('mhs.ujian', [$this->kelas, 'uas']))->assertOk()->assertSee('belum dibuka');
        $this->post(route('mhs.ujian.kumpul', [$this->kelas, 'uas']), ['file' => $berkas()])->assertSessionHasErrors('file');
        $this->get('/m/matkul/'.$this->kelas->id.'/ujian/kuis')->assertNotFound();

        // asisten membuka pengumpulan
        $this->actingAs($this->asisten, 'web')->put(route('asisten.ujian.tenggat', [$this->kelas, 'uas']), ['tenggat' => now()->subHour()->format('Y-m-d H:i')])->assertSessionHasErrors('tenggat');
        $this->put(route('asisten.ujian.tenggat', [$this->kelas, 'uas']), ['tenggat' => now()->addDay()->format('Y-m-d H:i')])->assertSessionHas('ok');
        $this->assertTrue($this->kelas->fresh()->bukaJawaban('uas'));
        $this->assertFalse($this->kelas->fresh()->bukaJawaban('uts'));
        // muncul di beranda mahasiswa sebagai yang perlu dikumpulkan
        $this->actingAs($a, 'mahasiswa')->get(route('mhs.home'))->assertSee('Jawaban UAS')->assertDontSee('Jawaban UTS');

        // mahasiswa mengunggah lalu mengganti
        $this->actingAs($a, 'mahasiswa')->post(route('mhs.ujian.kumpul', [$this->kelas, 'uas']), ['file' => $berkas('virus.exe')])->assertSessionHasErrors('file');
        $this->post(route('mhs.ujian.kumpul', [$this->kelas, 'uas']), ['file' => $berkas('uas-v1.pdf'), 'catatan' => 'versi 1'])->assertSessionHas('ok');
        $lama = JawabanUjian::firstOrFail()->path;
        $this->post(route('mhs.ujian.kumpul', [$this->kelas, 'uas']), ['file' => $berkas('uas-v2.pdf')])->assertSessionHas('ok');
        $j = JawabanUjian::firstOrFail();
        $this->assertSame(1, JawabanUjian::count());
        $this->assertSame('uas-v2.pdf', $j->nama_file);
        Storage::assertMissing($lama);
        $this->get(route('mhs.ujian.unduh', [$this->kelas, 'uas']))->assertOk()->assertDownload('uas-v2.pdf');
        $this->get(route('mhs.matkul', $this->kelas))->assertSee('Terkirim');
        $this->get(route('mhs.home'))->assertDontSee('Jawaban UAS');
        $this->actingAs($b, 'mahasiswa')->get(route('mhs.home'))->assertSee('Jawaban UAS');
        $this->actingAs($b, 'mahasiswa')->get(route('mhs.ujian.unduh', [$this->kelas, 'uas']))->assertNotFound();

        // asisten memeriksa dan menilai
        $this->actingAs($this->asisten, 'web')->get(route('asisten.ujian.show', [$this->kelas, 'uas']))->assertOk()->assertSee('uas-v2.pdf')->assertSee('belum mengunggah');
        $this->get(route('asisten.ujian.unduh', [$this->kelas, 'uas', $j]))->assertOk()->assertDownload('1001_mhs-1001_uas-v2.pdf');
        $this->get(route('asisten.ujian.unduh', [$this->kelas, 'uts', $j]))->assertNotFound();
        $this->postJson(route('asisten.ujian.nilai', [$this->kelas, 'uas', $a]), ['nilai' => 88.5, 'komentar' => 'Bagus'])->assertOk();
        $this->postJson(route('asisten.ujian.nilai', [$this->kelas, 'uas', $b]), ['nilai' => 70])->assertOk(); // ujian tertulis, tanpa unggah
        $this->postJson(route('asisten.ujian.nilai', [$this->kelas, 'uas', $a]), ['nilai' => 120])->assertUnprocessable();
        $this->assertEquals(88.5, $this->kelas->mahasiswa()->whereKey($a->id)->first()->pivot->nilai_uas);
        $this->assertEquals(70, $this->kelas->mahasiswa()->whereKey($b->id)->first()->pivot->nilai_uas);
        $this->assertSame('Bagus', $j->fresh()->komentar);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'nilai_ujian']);
        if (class_exists(\ZipArchive::class)) {
            $this->get(route('asisten.ujian.zip', [$this->kelas, 'uas']))->assertOk();
        }
        $lain = User::create(['name' => 'Lain', 'email' => 'lain@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->actingAs($lain, 'web')->get(route('asisten.ujian.show', [$this->kelas, 'uas']))->assertForbidden();
        $this->postJson(route('asisten.ujian.nilai', [$this->kelas, 'uas', $a]), ['nilai' => 10])->assertForbidden();

        // sudah dinilai: tidak bisa diganti, nilai & komentar terlihat
        $this->actingAs($a, 'mahasiswa')->post(route('mhs.ujian.kumpul', [$this->kelas, 'uas']), ['file' => $berkas()])->assertSessionHasErrors('file');
        $this->get(route('mhs.ujian', [$this->kelas, 'uas']))->assertSee('88.5')->assertSee('Bagus');

        // ditutup
        $this->actingAs($this->asisten, 'web')->put(route('asisten.ujian.tenggat', [$this->kelas, 'uas']), ['tutup' => 1])->assertSessionHas('ok');
        $this->assertNull($this->kelas->fresh()->tenggat_uas);
        $this->actingAs($b, 'mahasiswa')->post(route('mhs.ujian.kumpul', [$this->kelas, 'uas']), ['file' => $berkas()])->assertSessionHasErrors('file');
    }
}
