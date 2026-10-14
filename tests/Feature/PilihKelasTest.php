<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PilihKelasTest extends TestCase
{
    use RefreshDatabase;

    private User $asisten;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        $this->asisten = User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
    }

    private function kelas(string $nama, string $matkul): Kelas
    {
        return Kelas::create(['asisten_id' => $this->asisten->id, 'nama_kelas' => $nama, 'mata_kuliah' => $matkul, 'jumlah_pertemuan' => 8]);
    }

    private function daftar(array $ubah = [])
    {
        return $this->post('/daftar', $ubah + ['npm' => '50421099', 'kelas' => 'B', 'nama' => 'Budi Santoso', 'email' => 'budi@student.uir.ac.id',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'cf-turnstile-response' => 't']);
    }

    public function test_form_daftar_menampilkan_kelas_a_sampai_g_walau_belum_ada_matkul(): void
    {
        $res = $this->get('/daftar')->assertOk();
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $k) {
            $res->assertSee('<option value="'.$k.'"', false);
        }
        $this->daftar(['kelas' => 'Z'])->assertSessionHasErrors('kelas');
    }

    public function test_email_wajib_domain_kampus(): void
    {
        $this->kelas('B', 'Basis Data');
        $this->get('/daftar')->assertSee('Wajib email kampus (@student.uir.ac.id)')->assertSee('placeholder="nama@student.uir.ac.id"', false);
        foreach (['budi@gmail.com', 'budi@uir.ac.id', 'budi@student.uir.ac.id.palsu.com', 'budi@bukanstudent.uir.ac.id', 'budi@student.uir.ac.idx'] as $email) {
            $this->daftar(['email' => $email])->assertSessionHasErrors(['email' => 'Gunakan email kampus (@student.uir.ac.id). Email pribadi seperti Gmail tidak bisa dipakai.']);
        }
        $this->assertDatabaseCount('mahasiswa', 0);

        $this->daftar(['email' => 'Budi.Santoso@Student.UIR.ac.id'])->assertRedirect(route('daftar.verifikasi'));
        $this->assertDatabaseHas('mahasiswa', ['email' => 'budi.santoso@student.uir.ac.id']);
        $this->assertDatabaseCount('mahasiswa', 1);
    }

    public function test_domain_bisa_diatur_atau_dimatikan(): void
    {
        config(['absen.domain_email' => ['kampus.ac.id', 'uir.ac.id']]);
        $this->daftar(['email' => 'budi@kampus.ac.id'])->assertRedirect(route('daftar.verifikasi'));
        config(['absen.domain_email' => []]);
        $this->daftar(['npm' => '50421097', 'email' => 'budi@gmail.com'])->assertRedirect(route('daftar.verifikasi'));
    }

    public function test_setelah_verifikasi_tidak_otomatis_ikut_matkul_lalu_diarahkan_memilih(): void
    {
        $this->kelas('B', 'Basis Data');
        $this->daftar()->assertRedirect(route('daftar.verifikasi'));
        $mhs = Mahasiswa::where('npm', '50421099')->first();
        $this->assertSame('B', $mhs->kelas);
        $mhs->update(['kode_verifikasi' => Hash::make('123456')]);

        $this->post('/daftar/verifikasi', ['kode' => '123456'])->assertRedirect(route('mhs.kelas'));
        $this->assertSame(0, $mhs->kelas()->count());
    }

    public function test_mahasiswa_memilih_beberapa_matkul_termasuk_kelas_lain(): void
    {
        $bd = $this->kelas('B', 'Basis Data');
        $web = $this->kelas('B', 'Pemrograman Web');
        $ulang = $this->kelas('A', 'Algoritma');
        $mhs = Mahasiswa::create(['npm' => '50421099', 'nama' => 'Budi', 'kelas' => 'B', 'email' => 'b@x.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        $this->actingAs($mhs, 'mahasiswa');

        $this->get(route('mhs.kelas'))->assertOk()
            ->assertSeeInOrder(['Kelas B', 'Basis Data', 'Pemrograman Web', 'Kelas lain', 'Kelas A', 'Algoritma']);

        foreach ([$bd, $web, $ulang] as $k) {
            $this->post(route('mhs.kelas.gabung', $k))->assertSessionHas('ok');
        }
        $this->post(route('mhs.kelas.gabung', $bd)); // dobel tidak masalah
        $this->assertEqualsCanonicalizing([$bd->id, $web->id, $ulang->id], $mhs->kelas()->pluck('kelas.id')->all());
        $this->assertSame(3, LogAktivitas::where('aksi', 'mhs_gabung')->count());
        $this->get(route('mhs.home'))->assertSee('Basis Data')->assertSee('Pemrograman Web')->assertSee('Algoritma');

        // keluar boleh selama belum ada absen
        $this->delete(route('mhs.kelas.keluar', $web))->assertSessionHas('ok');
        $this->assertFalse($mhs->kelas()->whereKey($web->id)->exists());

        // setelah ada absen tidak bisa keluar sendiri
        Absensi::create(['kelas_id' => $bd->id, 'mahasiswa_id' => $mhs->id, 'pertemuan' => 1, 'status' => 'H', 'metode' => 'qr', 'waktu' => now()]);
        $this->delete(route('mhs.kelas.keluar', $bd))->assertSessionHasErrors('kelas');
        $this->assertTrue($mhs->kelas()->whereKey($bd->id)->exists());

        // nilai UTS/UAS yang sudah diisi asisten juga mengunci (keluar akan menghapus nilainya)
        $mhs->kelas()->updateExistingPivot($ulang->id, ['nilai_uts' => 80]);
        $this->delete(route('mhs.kelas.keluar', $ulang))->assertSessionHasErrors('kelas');
        $this->assertEquals(80, $mhs->kelas()->whereKey($ulang->id)->first()->pivot->nilai_uts);

        $this->actingAs($this->asisten, 'web')->get(route('asisten.kelas.show', [$bd, 'tab' => 'log', 'aksi' => 'mahasiswa']))
            ->assertSee('50421099 Budi (kelas B) bergabung ke Basis Data — Kelas B');
    }

    public function test_kelas_hanya_a_sampai_g_dan_dibuat_dosen(): void
    {
        $dosen = User::create(['name' => 'Dosen', 'email' => 'dosen@kampus.ac.id', 'password' => 'dosen12345', 'role' => 'dosen']);
        $mk = \App\Models\Matkul::create(['dosen_id' => $dosen->id, 'nama' => 'Jaringan']);
        $this->actingAs($dosen, 'web');
        $this->post(route('asisten.matkul.kelas.store', $mk), ['nama_kelas' => '3KA01', 'jumlah_pertemuan' => 8])->assertSessionHasErrors('nama_kelas');
        $this->post(route('asisten.matkul.kelas.store', $mk), ['nama_kelas' => 'C', 'jumlah_pertemuan' => 8])->assertSessionHasNoErrors();
        $this->assertSame('Jaringan', Kelas::where('matkul_id', $mk->id)->value('mata_kuliah'));

        // kelas lama dengan nama di luar daftar tetap bisa disimpan asistennya
        $lama = $this->kelas('3KA01', 'Basis Data');
        $this->actingAs($this->asisten, 'web')
            ->put(route('asisten.kelas.update', $lama), ['mata_kuliah' => 'Basis Data Lanjut', 'nama_kelas' => '3KA01', 'jumlah_pertemuan' => 8])->assertSessionHasNoErrors();
    }
}
