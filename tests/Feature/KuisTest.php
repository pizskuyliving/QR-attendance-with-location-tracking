<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Kuis;
use App\Models\KuisJawaban;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Services\Rekap;
use App\Services\RekapNilai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KuisTest extends TestCase
{
    use RefreshDatabase;

    private User $asisten;

    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asisten = User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->kelas = Kelas::create(['asisten_id' => $this->asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 4]);
    }

    private function mhs(string $npm, bool $hadir = true): Mahasiswa
    {
        $m = Mahasiswa::create(['npm' => $npm, 'nama' => 'Mhs '.$npm, 'email' => $npm.'@student.uir.ac.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        $this->kelas->mahasiswa()->attach($m->id);
        Absensi::create(['kelas_id' => $this->kelas->id, 'mahasiswa_id' => $m->id, 'pertemuan' => 2, 'status' => $hadir ? 'H' : 'A', 'metode' => 'manual']);

        return $m;
    }

    /** 5 soal; kunci soal ke-i: a, b, c, a, b */
    private function isian(array $ubah = []): array
    {
        $soal = [];
        foreach (['a', 'b', 'c', 'a', 'b'] as $i => $k) {
            $soal[] = ['pertanyaan' => 'Pertanyaan '.($i + 1), 'a' => "A{$i}", 'b' => "B{$i}", 'c' => "C{$i}", 'kunci' => $k];
        }

        return $ubah + ['pertemuan' => 2, 'durasi_menit' => 10, 'soal' => $soal];
    }

    private function buatKuis(): Kuis
    {
        $this->actingAs($this->asisten, 'web')->post(route('asisten.kuis.store', $this->kelas), $this->isian())->assertRedirect();

        return Kuis::firstOrFail();
    }

    public function test_asisten_buat_ubah_buka_dan_tutup_kuis(): void
    {
        $this->actingAs($this->asisten, 'web')->get(route('asisten.kelas.show', [$this->kelas, 'tab' => 'kuis']))->assertOk()->assertSee('Buat kuis refleksi');

        $kurang = $this->isian();
        array_pop($kurang['soal']);
        $this->post(route('asisten.kuis.store', $this->kelas), $kurang)->assertSessionHasErrors(['soal' => 'Kuis harus berisi 5 soal.']);
        $tanpaKunci = $this->isian();
        unset($tanpaKunci['soal'][2]['kunci']);
        $this->post(route('asisten.kuis.store', $this->kelas), $tanpaKunci)->assertSessionHasErrors(['soal.2.kunci' => 'Pilih jawaban benar untuk soal 3.']);

        $kuis = $this->buatKuis();
        $this->assertTrue($kuis->draf());
        $this->assertSame(['a', 'b', 'c', 'a', 'b'], $kuis->soal->pluck('kunci')->all());
        $this->post(route('asisten.kuis.store', $this->kelas), $this->isian())->assertSessionHasErrors(['pertemuan' => 'Pertemuan ini sudah punya kuis. Satu pertemuan hanya satu kuis.']);

        $ubah = $this->isian();
        $ubah['soal'][0]['pertanyaan'] = 'Apa itu normalisasi?';
        $this->put(route('asisten.kuis.update', $kuis), $ubah)->assertSessionHas('ok');
        $this->get(route('asisten.kuis.show', $kuis))->assertOk()->assertSee('Apa itu normalisasi?')->assertSee('Buka kuis');

        $this->post(route('asisten.kuis.buka', $kuis), ['durasi_menit' => 5])->assertSessionHas('ok');
        $kuis->refresh();
        $this->assertTrue($kuis->berjalan());
        $this->assertEquals(5, (int) round($kuis->dibuka_at->diffInMinutes($kuis->ditutup_at)));
        $this->put(route('asisten.kuis.update', $kuis), $ubah)->assertSessionHasErrors('soal'); // terkunci setelah dibuka
        $this->get(route('asisten.kuis.show', $kuis))->assertSee('Tutup sekarang');

        $this->post(route('asisten.kuis.tutup', $kuis))->assertSessionHas('ok');
        $this->assertTrue($kuis->fresh()->selesai());
        $this->assertSame(['kuis_buat', 'kuis_buka', 'kuis_tutup', 'kuis_ubah'], LogAktivitas::where('aksi', 'like', 'kuis_%')->orderBy('aksi')->pluck('aksi')->all());

        // asisten kelas lain tidak bisa
        $lain = User::create(['name' => 'B', 'email' => 'b@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->actingAs($lain, 'web')->get(route('asisten.kuis.show', $kuis))->assertForbidden();
    }

    public function test_hanya_mahasiswa_hadir_yang_bisa_menjawab_dalam_batas_waktu(): void
    {
        $kuis = $this->buatKuis();
        $hadir = $this->mhs('1001');
        $absen = $this->mhs('1002', hadir: false);
        $soal = $kuis->soal->keyBy('urutan');

        // draf belum terlihat
        $this->actingAs($hadir, 'mahasiswa')->get(route('mhs.kuis', $kuis))->assertNotFound();
        $this->actingAs($this->asisten, 'web')->post(route('asisten.kuis.buka', $kuis), ['durasi_menit' => 10]);

        // beranda menampilkan kuis untuk yang hadir saja
        $this->actingAs($hadir, 'mahasiswa')->get(route('mhs.home'))->assertSee('Kuis sedang dibuka');
        $this->actingAs($absen, 'mahasiswa')->get(route('mhs.home'))->assertDontSee('Kuis sedang dibuka');
        $this->get(route('mhs.kuis', $kuis))->assertOk()->assertSee('hanya untuk mahasiswa yang sudah absen');
        $this->postJson(route('mhs.kuis.simpan', $kuis), ['soal' => $soal[1]->id, 'pilihan' => 'a'])->assertStatus(422);

        // yang hadir: buka, pilih (tersimpan otomatis), kunci belum terlihat
        $this->actingAs($hadir, 'mahasiswa')->get(route('mhs.kuis', $kuis))->assertOk()->assertSee('Sisa waktu')->assertSee('Pertanyaan 3')->assertDontSee('pilihanmu');
        $this->postJson(route('mhs.kuis.simpan', $kuis), ['soal' => $soal[1]->id, 'pilihan' => 'a'])->assertOk();
        $this->postJson(route('mhs.kuis.simpan', $kuis), ['soal' => $soal[2]->id, 'pilihan' => 'c'])->assertOk();
        $j = KuisJawaban::where('mahasiswa_id', $hadir->id)->first();
        $this->assertSame([1, 20.0], [$j->benar, $j->nilai]);

        // kirim: 4 benar dari 5 (soal 2 diganti jadi benar, soal 5 salah)
        $this->post(route('mhs.kuis.kirim', $kuis), ['jawaban' => [$soal[2]->id => 'b', $soal[3]->id => 'c', $soal[4]->id => 'a', $soal[5]->id => 'a']])
            ->assertSessionHas('ok');
        $j->refresh();
        $this->assertSame([4, 80.0], [$j->benar, $j->nilai]);
        $this->assertNotNull($j->selesai_at);
        $this->post(route('mhs.kuis.kirim', $kuis), ['jawaban' => [$soal[5]->id => 'b']])->assertSessionHasErrors('kuis'); // sekali saja
        $this->get(route('mhs.kuis', $kuis))->assertSee('Jawaban terkirim');

        // setelah waktu habis: nilai & kunci terlihat
        $this->travel(11)->minutes();
        $this->get(route('mhs.kuis', $kuis))->assertSee('4 dari 5 benar')->assertSee('pilihanmu');
        $this->actingAs($absen, 'mahasiswa')->get(route('mhs.kuis', $kuis))->assertSee('tidak tercatat hadir');
    }

    public function test_pindah_tab_mengirim_otomatis_dan_tercatat(): void
    {
        $kuis = $this->buatKuis();
        $m = $this->mhs('1001');
        $soal = $kuis->soal->keyBy('urutan');
        $this->actingAs($this->asisten, 'web')->post(route('asisten.kuis.buka', $kuis), ['durasi_menit' => 10]);

        $this->actingAs($m, 'mahasiswa')->get(route('mhs.kuis', $kuis))->assertOk()->assertSee('Jangan tinggalkan halaman ini');
        $this->postJson(route('mhs.kuis.simpan', $kuis), ['soal' => $soal[1]->id, 'pilihan' => 'a'])->assertOk();

        // dikirim lewat sendBeacon saat tab disembunyikan
        $this->post(route('mhs.kuis.kirim', $kuis), ['jawaban' => [$soal[2]->id => 'b'], 'alasan' => 'pindah_tab'])
            ->assertSessionHas('ok', 'Jawaban dikirim otomatis karena kamu meninggalkan halaman kuis.');
        $j = KuisJawaban::where('mahasiswa_id', $m->id)->first();
        $this->assertNotNull($j->selesai_at);
        $this->assertSame('pindah_tab', $j->kirim_otomatis);
        $this->assertSame([2, 40.0], [$j->benar, $j->nilai]);
        $this->assertTrue(LogAktivitas::where('aksi', 'mhs_kuis_pindah_tab')->exists());

        // tidak bisa menjawab lagi setelah kembali ke tab
        $this->postJson(route('mhs.kuis.simpan', $kuis), ['soal' => $soal[3]->id, 'pilihan' => 'c'])->assertStatus(422);
        $this->get(route('mhs.kuis', $kuis))->assertSee('dikirim otomatis karena kamu meninggalkan halaman kuis');
        $this->actingAs($this->asisten, 'web')->get(route('asisten.kuis.show', $kuis))->assertSee('pindah tab');
    }

    public function test_kirim_biasa_tidak_ditandai_otomatis(): void
    {
        $kuis = $this->buatKuis();
        $m = $this->mhs('1001');
        $this->actingAs($this->asisten, 'web')->post(route('asisten.kuis.buka', $kuis), ['durasi_menit' => 10]);

        $this->actingAs($m, 'mahasiswa')->get(route('mhs.kuis', $kuis))->assertOk();
        $this->post(route('mhs.kuis.kirim', $kuis), ['jawaban' => [], 'alasan' => 'lain'])->assertSessionHas('ok');
        $this->assertNull(KuisJawaban::where('mahasiswa_id', $m->id)->value('kirim_otomatis'));
    }

    public function test_waktu_habis_jawaban_tersimpan_tetap_dihitung(): void
    {
        $kuis = $this->buatKuis();
        $m = $this->mhs('1001');
        $this->actingAs($this->asisten, 'web')->post(route('asisten.kuis.buka', $kuis), ['durasi_menit' => 1]);
        $s1 = $kuis->soal->first();
        $this->actingAs($m, 'mahasiswa')->get(route('mhs.kuis', $kuis));
        $this->postJson(route('mhs.kuis.simpan', $kuis), ['soal' => $s1->id, 'pilihan' => 'a'])->assertOk();

        $this->travel(2)->minutes();
        $this->postJson(route('mhs.kuis.simpan', $kuis), ['soal' => $s1->id, 'pilihan' => 'b'])->assertStatus(422);
        $this->post(route('mhs.kuis.kirim', $kuis), ['jawaban' => []])->assertSessionHasErrors('kuis');
        $this->assertSame(20.0, KuisJawaban::first()->nilai);
    }

    public function test_nilai_kuis_masuk_rekap_dengan_bobot_sendiri(): void
    {
        $kuis = $this->buatKuis();
        $a = $this->mhs('1001');
        $b = $this->mhs('1002', hadir: false);
        $this->actingAs($this->asisten, 'web')->post(route('asisten.kuis.buka', $kuis), ['durasi_menit' => 10]);
        KuisJawaban::create(['kuis_id' => $kuis->id, 'mahasiswa_id' => $a->id, 'jawaban' => [], 'benar' => 4, 'nilai' => 80, 'selesai_at' => now()]);

        // sebelum selesai belum dihitung
        $nilai = new RekapNilai(new Rekap($this->kelas->fresh(), ringan: true));
        $this->assertNull($nilai->baris[0]['rata_kuis']);

        $this->travel(11)->minutes();
        $this->put(route('asisten.kelas.bobot', $this->kelas), ['bobot_hadir' => 50, 'bobot_tugas' => 0, 'bobot_kuis' => 50, 'bobot_uts' => 0, 'bobot_uas' => 0])->assertSessionHas('ok');
        $nilai = collect((new RekapNilai(new Rekap($this->kelas->fresh(), ringan: true)))->baris)->keyBy(fn ($r) => $r['mahasiswa']->npm);
        $this->assertSame(80.0, $nilai['1001']['rata_kuis']);
        $this->assertSame(0.0, $nilai['1002']['rata_kuis']); // tidak hadir = 0
        // hadir 1 dari 4 pertemuan = 25%; akhir = 50%×25 + 50%×80
        $this->assertSame(52.5, $nilai['1001']['akhir']);

        $this->get(route('asisten.kelas.show', [$this->kelas, 'tab' => 'rekap']))->assertOk()->assertSee('50% × rata-rata kuis');
        $this->get(route('asisten.kuis.show', $kuis))->assertOk()->assertSee('Analisis per soal')->assertSee('tidak hadir');
        $this->get(route('asisten.kuis.excel', $kuis))->assertOk()->assertDownload('basis-data-kelas-a-kuis-p2.xlsx');
        $this->get(route('asisten.absensi.export', $this->kelas))->assertOk();

        // form bobot lama tanpa kuis tetap bisa disimpan (kuis = 0)
        $this->put(route('asisten.kelas.bobot', $this->kelas), ['bobot_hadir' => 50, 'bobot_tugas' => 50, 'bobot_uts' => 0, 'bobot_uas' => 0])->assertSessionHas('ok');
        $this->assertSame(0, (int) $this->kelas->fresh()->bobot_kuis);
    }

    public function test_salin_ke_kelas_lain_dan_hapus(): void
    {
        $kuis = $this->buatKuis();
        $b = Kelas::create(['asisten_id' => $this->asisten->id, 'nama_kelas' => 'B', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 4]);
        $orang = Kelas::create(['asisten_id' => User::create(['name' => 'Z', 'email' => 'z@kampus.ac.id', 'password' => 'x12345678', 'role' => 'asisten'])->id, 'nama_kelas' => 'C', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 4]);

        $this->get(route('asisten.kuis.show', $kuis))->assertSee('Basis Data — Kelas B', false)->assertDontSee('Kelas C');
        $this->post(route('asisten.kuis.salin', $kuis), ['kelas_id' => $orang->id])->assertForbidden();
        $this->post(route('asisten.kuis.salin', $kuis), ['kelas_id' => $b->id])->assertSessionHas('ok');
        $salinan = $b->kuis()->first();
        $this->assertTrue($salinan->draf());
        $this->assertSame($kuis->soal->pluck('pertanyaan')->all(), $salinan->soal->pluck('pertanyaan')->all());
        $this->post(route('asisten.kuis.salin', $kuis), ['kelas_id' => $b->id])->assertSessionHasErrors('kelas_id');

        $this->delete(route('asisten.kuis.destroy', $kuis))->assertRedirect();
        $this->assertSame(1, Kuis::count());
        $this->assertSame(5, \App\Models\KuisSoal::count());
    }

    public function test_urutan_acak_tetap_per_mahasiswa(): void
    {
        $kuis = $this->buatKuis();
        $a = $this->mhs('1001');
        $urut = fn ($m) => collect($kuis->acakUntuk($m, $kuis->soal))->map(fn ($u) => $u['soal']->id.implode('', $u['pilihan']))->all();
        $this->assertSame($urut($a), $urut($a));
        $this->assertCount(5, $urut($a));
    }
}
