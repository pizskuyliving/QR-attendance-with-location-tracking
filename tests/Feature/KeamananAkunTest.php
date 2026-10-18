<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KeamananAkunTest extends TestCase
{
    use RefreshDatabase;

    private function asisten(array $isi = []): User
    {
        return User::create($isi + ['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
    }

    public function test_sesi_staf_lain_berakhir_saat_sandi_diganti(): void
    {
        $u = $this->asisten();
        $this->actingAs($u)->get(route('asisten.profil'))->assertOk();

        // kata sandi diganti dari perangkat lain / direset laboran
        $u->password = 'gantibaru1';
        $u->save();

        $this->get(route('asisten.profil'))->assertRedirect(route('asisten.login'));
        $this->assertGuest('web');
    }

    public function test_sesi_mahasiswa_lain_berakhir_saat_sandi_diganti(): void
    {
        $m = Mahasiswa::create(['npm' => '50421001', 'nama' => 'Andi', 'email' => 'andi@contoh.ac.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        $this->actingAs($m, 'mahasiswa')->get(route('mhs.akun'))->assertOk();

        $m->password = 'gantibaru1';
        $m->save();

        $this->get(route('mhs.akun'))->assertRedirect(route('login'));
        $this->assertGuest('mahasiswa');
    }

    public function test_ganti_sandi_sendiri_tetap_masuk(): void
    {
        $u = $this->asisten();
        $this->actingAs($u)->get(route('asisten.profil'))->assertOk();

        $this->post(route('asisten.sandi'), ['password_lama' => 'asisten123', 'password' => 'gantibaru1', 'password_confirmation' => 'gantibaru1'])
            ->assertSessionHasNoErrors();
        $this->get(route('asisten.profil'))->assertOk();
        $this->assertAuthenticatedAs($u, 'web');
    }

    public function test_ganti_email_wajib_kata_sandi(): void
    {
        $u = $this->asisten();

        $this->actingAs($u)->put(route('asisten.profil.update'), ['name' => 'Asisten', 'email' => 'baru@kampus.ac.id'])
            ->assertSessionHasErrors('password_lama');
        $this->put(route('asisten.profil.update'), ['name' => 'Asisten', 'email' => 'baru@kampus.ac.id', 'password_lama' => 'salah'])
            ->assertSessionHasErrors('password_lama');
        $this->assertSame('asisten@kampus.ac.id', $u->fresh()->email);

        $this->put(route('asisten.profil.update'), ['name' => 'Asisten', 'email' => ' Baru@Kampus.ac.id ', 'password_lama' => 'asisten123'])
            ->assertSessionHasNoErrors();
        $this->assertSame('baru@kampus.ac.id', $u->fresh()->email);
        $this->assertTrue(LogAktivitas::where('aksi', 'ganti_email')->exists());
    }

    public function test_ganti_nama_saja_tanpa_kata_sandi(): void
    {
        $u = $this->asisten();

        $this->actingAs($u)->put(route('asisten.profil.update'), ['name' => 'Nama Baru', 'email' => 'asisten@kampus.ac.id'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Nama Baru', $u->fresh()->name);
    }

    public function test_login_staf_tidak_peka_huruf_besar_email(): void
    {
        config(['captcha.turnstile.site_key' => 'k', 'captcha.turnstile.secret_key' => 's']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        $this->asisten();

        $this->post(route('masuk'), ['peran' => 'asisten', 'email' => ' Asisten@Kampus.ac.id', 'password' => 'asisten123', 'cf-turnstile-response' => 't'])
            ->assertRedirect(route('masuk.cek', ['ke' => '/asisten']));
        $this->assertAuthenticated('web');
    }

    public function test_tambah_dan_hapus_akun_tercatat_di_log(): void
    {
        $admin = $this->asisten(['email' => 'laboran@kampus.ac.id', 'role' => 'admin']);

        $this->actingAs($admin)->post(route('asisten.akun.store'), ['name' => 'Dosen X', 'email' => 'DosenX@kampus.ac.id', 'password' => 'dosen12345', 'role' => 'dosen'])
            ->assertSessionHasNoErrors();
        $dosen = User::where('email', 'dosenx@kampus.ac.id')->firstOrFail();
        $this->assertTrue(LogAktivitas::where('aksi', 'akun_tambah')->where('keterangan', 'like', '%dosenx@kampus.ac.id%')->exists());

        $this->delete(route('asisten.akun.destroy', $dosen))->assertSessionHasNoErrors();
        $this->assertTrue(LogAktivitas::where('aksi', 'akun_hapus')->where('keterangan', 'like', '%dosenx@kampus.ac.id%')->exists());
    }

    public function test_ekspor_dibatasi_per_menit(): void
    {
        $u = $this->asisten();
        $kelas = Kelas::create(['asisten_id' => $u->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 8]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($u)->get(route('asisten.absensi.export', $kelas))->assertOk();
        }
        $this->get(route('asisten.absensi.export', $kelas))->assertStatus(429);
    }

    public function test_csp_membatasi_plugin_base_dan_form(): void
    {
        $csp = $this->get(route('login'))->headers->get('Content-Security-Policy');

        foreach (["frame-ancestors 'none'", "object-src 'none'", "base-uri 'self'", "form-action 'self'"] as $aturan) {
            $this->assertStringContainsString($aturan, $csp);
        }
    }
}
