<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DaftarAsistenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@kampus.ac.id', 'password' => 'admin123', 'role' => 'admin']);
    }

    private function daftar(array $ubah = [])
    {
        return $this->post('/asisten/daftar', $ubah + ['name' => 'Rina Asisten', 'email' => 'rina@gmail.com', 'keterangan' => 'Basis Data kelas A',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'cf-turnstile-response' => 't']);
    }

    private function login()
    {
        return $this->from('/asisten/login')->post('/asisten/login', ['email' => 'rina@gmail.com', 'password' => 'rahasia123', 'cf-turnstile-response' => 't']);
    }

    public function test_daftar_lalu_menunggu_persetujuan(): void
    {
        $this->get('/asisten/login')->assertSee('Daftar akun asisten');
        $this->get('/asisten/daftar')->assertOk()->assertSee('disetujui admin', false);

        $this->daftar()->assertRedirect(route('asisten.login'))->assertSessionHas('ok');
        $u = User::where('email', 'rina@gmail.com')->first();
        $this->assertSame(['asisten', 'menunggu'], [$u->role, $u->status]);

        $this->login()->assertSessionHasErrors(['email' => 'Akunmu masih menunggu persetujuan admin.']);
        $this->assertGuest('web');
    }

    public function test_admin_menyetujui_lalu_bisa_masuk(): void
    {
        $this->daftar();
        $u = User::where('email', 'rina@gmail.com')->first();

        $this->actingAs($this->admin, 'web')->get(route('asisten.dashboard'))->assertSee('1</b> pendaftaran asisten menunggu', false);
        $this->get(route('asisten.profil'))->assertSee('Basis Data kelas A')->assertSee('Setujui');
        $this->post(route('asisten.akun.setujui', $u))->assertSessionHas('ok');
        $this->assertSame('aktif', $u->fresh()->status);
        $this->assertTrue(LogAktivitas::where('aksi', 'asisten_setujui')->exists());

        auth('web')->logout();
        $this->login()->assertRedirect(route('asisten.dashboard'));
        $this->assertAuthenticatedAs($u->fresh(), 'web');
    }

    public function test_ditolak_tidak_bisa_masuk_dan_email_tidak_bisa_dipakai_ulang(): void
    {
        $this->daftar();
        $u = User::where('email', 'rina@gmail.com')->first();
        $this->actingAs($this->admin, 'web')->post(route('asisten.akun.tolak', $u))->assertSessionHas('ok');
        auth('web')->logout();

        $this->login()->assertSessionHasErrors('email');
        $this->assertGuest('web');
        $this->daftar()->assertSessionHasErrors('email');

        // admin menghapus data pendaftaran -> email bisa dipakai lagi
        $this->actingAs($this->admin, 'web')->delete(route('asisten.akun.destroy', $u));
        auth('web')->logout();
        $this->daftar()->assertSessionHas('ok');
    }

    public function test_hanya_admin_yang_bisa_menyetujui(): void
    {
        $this->daftar();
        $u = User::where('email', 'rina@gmail.com')->first();
        $asisten = User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->actingAs($asisten, 'web')->post(route('asisten.akun.setujui', $u))->assertForbidden();
        $this->assertSame('menunggu', $u->fresh()->status);
    }

    public function test_validasi_dan_captcha(): void
    {
        $this->daftar(['password' => 'pendek', 'password_confirmation' => 'pendek', 'keterangan' => ''])->assertSessionHasErrors(['password', 'keterangan']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);
        $this->daftar()->assertSessionHasErrors('captcha');
        $this->assertDatabaseMissing('users', ['email' => 'rina@gmail.com']);
    }

    public function test_akun_buatan_admin_langsung_aktif(): void
    {
        $this->actingAs($this->admin, 'web')->post(route('asisten.akun.store'), ['name' => 'Budi', 'email' => 'budi@kampus.ac.id', 'password' => 'budi12345', 'role' => 'asisten']);
        $this->assertSame('aktif', User::where('email', 'budi@kampus.ac.id')->value('status'));
    }
}
