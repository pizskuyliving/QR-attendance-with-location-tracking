<?php

namespace Tests\Feature;

use App\Mail\Pemberitahuan;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Matkul;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PantauanDosenTest extends TestCase
{
    use RefreshDatabase;

    private function siapkan(): array
    {
        $dosen = User::create(['name' => 'Dr. Dosen', 'email' => 'dosen@kampus.ac.id', 'password' => 'dosen12345', 'role' => 'dosen']);
        $asisten = User::create(['name' => 'Asisten A', 'email' => 'a@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $matkul = Matkul::create(['dosen_id' => $dosen->id, 'nama' => 'Basis Data', 'kode' => 'IF231']);
        $kelas = Kelas::create(['asisten_id' => $asisten->id, 'matkul_id' => $matkul->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data', 'hari' => 'Senin 08.00', 'jumlah_pertemuan' => 8]);

        return [$dosen, $asisten, $kelas, $matkul];
    }

    public function test_dosen_membuat_kelas_dan_memilih_asisten_yang_diberi_tahu_lewat_email(): void
    {
        Mail::fake();
        [$dosen, $asisten, , $matkul] = $this->siapkan();
        $belum = User::create(['name' => 'Menunggu', 'email' => 'm@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten', 'status' => 'menunggu']);

        $this->actingAs($dosen)->post(route('asisten.matkul.kelas.store', $matkul), ['nama_kelas' => 'A', 'jumlah_pertemuan' => 8])->assertSessionHasErrors('nama_kelas');
        $this->actingAs($dosen)->post(route('asisten.matkul.kelas.store', $matkul), ['nama_kelas' => 'B', 'jumlah_pertemuan' => 10, 'asisten_id' => $belum->id])->assertSessionHasErrors('asisten_id');
        $this->actingAs($dosen)->post(route('asisten.matkul.kelas.store', $matkul), ['nama_kelas' => 'B', 'jumlah_pertemuan' => 10, 'bobot_hadir' => 30, 'bobot_tugas' => 70, 'bobot_uts' => 0, 'bobot_uas' => 0])->assertSessionHasNoErrors();
        $b = Kelas::where('nama_kelas', 'B')->firstOrFail();
        $this->assertNull($b->asisten_id);
        $this->assertSame([10, 30, 70, 'Basis Data'], [$b->jumlah_pertemuan, $b->bobot_hadir, $b->bobot_tugas, $b->mata_kuliah]);
        Mail::assertNothingSent();

        $this->actingAs($dosen)->put(route('asisten.matkul.kelas.asisten', [$matkul, $b]), ['asisten_id' => $asisten->id])->assertSessionHas('ok');
        $this->assertSame($asisten->id, $b->fresh()->asisten_id);
        Mail::assertSent(Pemberitahuan::class, fn ($m) => $m->hasTo('a@kampus.ac.id') && str_contains($m->subjek, 'Kelas B'));
        $this->actingAs($asisten)->get(route('asisten.dashboard'))->assertSee('Dosen Dr. Dosen');

        $this->actingAs($dosen)->put(route('asisten.matkul.kelas.update', [$matkul, $b]), ['nama_kelas' => 'C', 'jumlah_pertemuan' => 8, 'hari' => 'Rabu'])->assertSessionHasNoErrors();
        $this->assertSame(['C', 'Rabu'], [$b->fresh()->nama_kelas, $b->fresh()->hari]);

        $this->actingAs($dosen)->put(route('asisten.matkul.update', $matkul), ['nama' => 'Basis Data I']);
        $this->assertSame('Basis Data I', $b->fresh()->mata_kuliah);

        $this->actingAs($dosen)->delete(route('asisten.matkul.destroy', $matkul))->assertSessionHasErrors('matkul');
        $this->actingAs($dosen)->delete(route('asisten.matkul.kelas.destroy', [$matkul, $b]))->assertSessionHas('ok');
        $this->assertNull(Kelas::find($b->id));
    }

    public function test_asisten_tidak_bisa_membuat_atau_menghapus_kelas_matkul_tapi_bisa_atur_radius_dan_bobot(): void
    {
        [, $asisten, $kelas, $matkul] = $this->siapkan();
        $this->actingAs($asisten);

        $this->post(route('asisten.matkul.kelas.store', $matkul), ['nama_kelas' => 'B', 'jumlah_pertemuan' => 8])->assertForbidden();
        $this->put(route('asisten.kelas.bobot', $kelas), ['bobot_hadir' => 50, 'bobot_tugas' => 50, 'bobot_uts' => 0, 'bobot_uas' => 0])->assertSessionHas('ok'); // bobot nilai boleh diatur asisten
        $this->assertSame([50, 50], [$kelas->fresh()->bobot_hadir, $kelas->fresh()->bobot_tugas]);
        $this->get(route('asisten.kelas.show', [$kelas, 'tab' => 'nilai']))->assertSee('Simpan bobot');
        $this->delete(route('asisten.kelas.destroy', $kelas))->assertForbidden();
        $this->put(route('asisten.kelas.update', $kelas), ['nama_kelas' => 'G', 'jumlah_pertemuan' => 2, 'lokasi_lat' => -6.35, 'lokasi_lng' => 106.84, 'radius' => 100])->assertSessionHas('ok');
        $kelas->refresh();
        $this->assertSame(['A', 8, 100], [$kelas->nama_kelas, $kelas->jumlah_pertemuan, $kelas->radius]);
        $this->get(route('asisten.kelas.show', [$kelas, 'tab' => 'pengaturan']))->assertSee('Diatur dosen')->assertDontSee('Hapus kelas');
    }

    public function test_dosen_melihat_pantauan_dan_hanya_matkulnya(): void
    {
        [$dosen, $asisten, $kelas, $matkul] = $this->siapkan();
        $m = Mahasiswa::create(['npm' => '50421001', 'nama' => 'Andi', 'email' => 'andi@contoh.ac.id', 'password' => 'rahasia123']);
        $kelas->mahasiswa()->attach($m->id);
        Absensi::create(['kelas_id' => $kelas->id, 'mahasiswa_id' => $m->id, 'pertemuan' => 1, 'status' => 'H', 'metode' => 'manual', 'waktu' => now()]);

        $this->actingAs($dosen)->get(route('asisten.dashboard'))->assertRedirect(route('asisten.matkul.index'));
        $this->actingAs($dosen)->get(route('asisten.matkul.index'))->assertOk()
            ->assertSee('Basis Data')->assertSee('Asisten A')->assertSee('1/8')->assertSee('1 pertemuan tanpa materi');
        $this->actingAs($dosen)->get(route('asisten.absensi.export', $kelas))->assertOk();
        $this->actingAs($dosen)->get(route('asisten.kelas.show', $kelas))->assertRedirect(route('asisten.matkul.index'));
        $this->actingAs($dosen)->post(route('asisten.sesi.buka', $kelas), ['pertemuan' => 1])->assertForbidden();

        $lain = User::create(['name' => 'Dosen Lain', 'email' => 'lain@kampus.ac.id', 'password' => 'dosen12345', 'role' => 'dosen']);
        $this->actingAs($lain)->get(route('asisten.matkul.index'))->assertOk()->assertSee('Belum ada mata kuliah');
        $this->actingAs($lain)->put(route('asisten.matkul.kelas.asisten', [$matkul, $kelas]), ['asisten_id' => null])->assertForbidden();
        $this->actingAs($lain)->get(route('asisten.absensi.export', $kelas))->assertForbidden();
        $this->actingAs($asisten)->get(route('asisten.matkul.index'))->assertRedirect(route('asisten.dashboard'));
    }

    public function test_kelas_lama_dimasukkan_ke_matkul_dan_hapus_asisten_tidak_menghapus_kelas_matkul(): void
    {
        [$dosen, $asisten, $kelas, $matkul] = $this->siapkan();
        $lama = Kelas::create(['asisten_id' => $asisten->id, 'nama_kelas' => 'B', 'mata_kuliah' => 'BD lama', 'jumlah_pertemuan' => 8]);

        $this->actingAs($asisten)->get(route('asisten.kelas.show', [$lama, 'tab' => 'pengaturan']))->assertSee('Kelas lama')->assertSee('Hapus kelas');
        $this->actingAs($dosen)->post(route('asisten.matkul.kelas.ambil', $matkul), ['kelas_id' => $lama->id])->assertSessionHas('ok');
        $this->assertSame([$matkul->id, 'Basis Data', $asisten->id], [$lama->fresh()->matkul_id, $lama->fresh()->mata_kuliah, $lama->fresh()->asisten_id]);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@kampus.ac.id', 'password' => 'admin12345', 'role' => 'admin']);
        $this->actingAs($admin)->delete(route('asisten.akun.destroy', $asisten))->assertSessionHas('ok');
        $this->assertNull($kelas->fresh()->asisten_id);
        $this->actingAs($dosen)->get(route('asisten.matkul.index'))->assertSee('Belum ada asisten');
    }

    public function test_rekap_gabungan_matkul_berisi_ringkasan_mahasiswa_dan_absen_per_kelas(): void
    {
        [$dosen, $asisten, , $matkul] = $this->siapkan();
        $kelasB = Kelas::create(['asisten_id' => null, 'matkul_id' => $matkul->id, 'nama_kelas' => 'B', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 8]);
        $m = Mahasiswa::create(['npm' => '50421001', 'nama' => '=HYPERLINK("x")', 'email' => 'andi@contoh.ac.id', 'password' => 'rahasia123']);
        $kelasB->mahasiswa()->attach($m->id);

        $res = $this->actingAs($dosen)->get(route('asisten.matkul.export', $matkul))->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'xl');
        file_put_contents($file, $res->streamedContent());
        $book = IOFactory::load($file);
        unlink($file);

        $this->assertSame(['Ringkasan', 'Semua Mahasiswa', 'Absen Kelas A', 'Absen Kelas B'], $book->getSheetNames());
        $this->assertSame('Asisten A', $book->getSheet(0)->getCell('B8')->getValue());
        $this->assertSame('Belum ada asisten', $book->getSheet(0)->getCell('B9')->getValue());
        $this->assertSame('50421001', $book->getSheet(1)->getCell('C2')->getValue());
        $this->assertSame(' =HYPERLINK("x")', $book->getSheet(1)->getCell('D2')->getValue());

        // tiap lembar absen: tanda tangan dosen pengampu dan asisten dalam satu baris
        $sel = fn ($ws) => collect($ws->toArray(null, false, false, true))->flatMap(fn ($r, $i) => collect($r)->filter()->mapWithKeys(fn ($v, $c) => [$c.$i => $v]));
        $a = $sel($book->getSheetByName('Absen Kelas A'));
        $baris = (int) substr($a->search('Dosen Pengampu,'), 1);
        $this->assertSame('C', substr($a->search('Dosen Pengampu,'), 0, 1));
        $this->assertSame($baris, (int) substr($a->search('Asisten,'), 1));
        $this->assertSame($baris + 4, (int) substr($a->search('Dr. Dosen'), 1));
        $this->assertSame($baris + 4, (int) substr($a->search('Asisten A'), 1));
        $this->assertContains('(..............................)', $sel($book->getSheetByName('Absen Kelas B'))->all());
    }

    public function test_satu_kelas_maksimal_dua_asisten_dengan_akses_sama(): void
    {
        Mail::fake();
        [$dosen, $asisten, $kelas, $matkul] = $this->siapkan();
        $b = User::create(['name' => 'Asisten B', 'email' => 'b@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $c = User::create(['name' => 'Asisten C', 'email' => 'c@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $rute = route('asisten.matkul.kelas.asisten', [$matkul, $kelas]);

        // orang yang sama di dua slot ditolak
        $this->actingAs($dosen)->put($rute, ['asisten_id' => $asisten->id, 'asisten2_id' => $asisten->id])->assertSessionHasErrors('asisten2_id');

        // tambah Asisten 2: hanya asisten baru yang dapat email
        $this->actingAs($dosen)->put($rute, ['asisten_id' => $asisten->id, 'asisten2_id' => $b->id])->assertSessionHas('ok');
        $this->assertSame([$asisten->id, $b->id], [$kelas->fresh()->asisten_id, $kelas->fresh()->asisten2_id]);
        Mail::assertSent(Pemberitahuan::class, 1);
        Mail::assertSent(Pemberitahuan::class, fn ($m) => $m->hasTo('b@kampus.ac.id') && str_contains(implode(' ', $m->paragraf), 'bersama Asisten A'));
        $this->assertSame('Asisten A & Asisten B', $kelas->fresh()->namaAsisten());

        // Asisten 2 punya akses penuh ke kelas; asisten lain tidak
        $this->actingAs($b)->get(route('asisten.dashboard'))->assertSee('Basis Data');
        $this->actingAs($b)->get(route('asisten.kelas.show', [$kelas, 'tab' => 'pengaturan']))->assertOk()->assertSee('Asisten A &amp; Asisten B', false);
        $this->actingAs($b)->post(route('asisten.sesi.buka', $kelas), ['pertemuan' => 1])->assertSessionHasNoErrors();
        $this->actingAs($c)->get(route('asisten.kelas.show', $kelas))->assertForbidden();

        // catatan dosen terkirim ke keduanya
        $this->actingAs($dosen)->post(route('asisten.matkul.catatan', [$matkul, $kelas]), ['isi' => 'Halo berdua'])->assertSessionHasNoErrors();
        Mail::assertSent(Pemberitahuan::class, fn ($m) => $m->hasTo('a@kampus.ac.id') && str_contains($m->subjek, 'Catatan'));
        Mail::assertSent(Pemberitahuan::class, fn ($m) => $m->hasTo('b@kampus.ac.id') && str_contains($m->subjek, 'Catatan'));

        // kalau hanya Asisten 2 diisi, ia naik menjadi Asisten 1
        $this->actingAs($dosen)->put($rute, ['asisten_id' => '', 'asisten2_id' => $c->id])->assertSessionHas('ok');
        $this->assertSame([$c->id, null], [$kelas->fresh()->asisten_id, $kelas->fresh()->asisten2_id]);
        $this->actingAs($b)->get(route('asisten.kelas.show', $kelas))->assertForbidden();

        // menghapus akun Asisten 2 hanya mengosongkan slotnya
        $this->actingAs($dosen)->put($rute, ['asisten_id' => $c->id, 'asisten2_id' => $b->id]);
        $b->delete();
        $this->assertSame([$c->id, null], [$kelas->fresh()->asisten_id, $kelas->fresh()->asisten2_id]);
    }

    public function test_catatan_dosen_terkirim_ke_asisten_dan_ditandai_dibaca(): void
    {
        Mail::fake();
        [$dosen, $asisten, $kelas, $matkul] = $this->siapkan();
        $lain = Kelas::create(['asisten_id' => $asisten->id, 'nama_kelas' => 'C', 'mata_kuliah' => 'Lain', 'jumlah_pertemuan' => 8]);

        $this->actingAs($dosen)->post(route('asisten.matkul.catatan', [$matkul, $lain]), ['isi' => 'x'])->assertNotFound();
        $this->actingAs($dosen)->post(route('asisten.matkul.catatan', [$matkul, $kelas]), ['isi' => 'Tolong unggah materi P3.'])->assertSessionHasNoErrors();
        Mail::assertSent(Pemberitahuan::class, fn ($m) => $m->hasTo('a@kampus.ac.id') && in_array('Tolong unggah materi P3.', $m->paragraf, true));

        $this->actingAs($asisten)->get(route('asisten.dashboard'))->assertSee('1</b> catatan baru', false)->assertSee('Catatan baru');
        $this->actingAs($asisten)->get(route('asisten.kelas.show', $kelas))->assertSee('Tolong unggah materi P3.')->assertSee('1 baru');
        $this->assertNotNull($kelas->catatanDosen()->first()->dibaca_at);
        $this->actingAs($dosen)->get(route('asisten.matkul.index'))->assertSee('sudah dibaca');
    }
}
