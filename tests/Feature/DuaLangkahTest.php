<?php

namespace Tests\Feature;

use App\Models\LogAktivitas;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DuaLangkahTest extends TestCase
{
    use RefreshDatabase;

    private User $asisten;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        $this->asisten = User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
    }

    private function kodeSekarang(string $rahasia, int $geser = 0): string
    {
        return Totp::kode($rahasia, Totp::langkahSekarang() + $geser);
    }

    /** Pasang 2FA lewat halaman profil; mengembalikan [rahasia, kode cadangan]. */
    private function aktifkan(): array
    {
        $this->actingAs($this->asisten, 'web')->post(route('asisten.2fa.siapkan'))->assertRedirect();
        $rahasia = session('dua_langkah_calon');
        $this->get(route('asisten.profil'))->assertSee(trim(chunk_split($rahasia, 4, ' ')))->assertSee('otpauth://totp/', false);

        $this->post(route('asisten.2fa.konfirmasi'), ['kode' => '000000'])->assertSessionHasErrors('kode');
        $res = $this->post(route('asisten.2fa.konfirmasi'), ['kode' => $this->kodeSekarang($rahasia)])->assertSessionHas('kode_cadangan');
        $cadangan = session('kode_cadangan');
        auth('web')->logout();
        $this->flushSession();

        return [$rahasia, $cadangan];
    }

    private function loginSandi()
    {
        return $this->post('/asisten/login', ['email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'cf-turnstile-response' => 't']);
    }

    public function test_kode_sesuai_vektor_rfc6238(): void
    {
        $rahasia = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // "12345678901234567890"
        $this->assertSame('287082', Totp::kode($rahasia, 1));
        $this->assertSame('081804', Totp::kode($rahasia, 37037036));
        $this->assertSame('279037', Totp::kode($rahasia, 66666666));
    }

    public function test_aktifkan_lalu_login_wajib_kode(): void
    {
        [$rahasia, $cadangan] = $this->aktifkan();
        $u = $this->asisten->fresh();
        $this->assertTrue($u->pakaiDuaLangkah());
        $this->assertCount(8, $cadangan);
        $this->assertNotSame($rahasia, $u->getRawOriginal('dua_langkah_rahasia')); // tersimpan terenkripsi

        $this->loginSandi()->assertRedirect(route('asisten.login.2fa'));
        $this->assertGuest('web');
        $this->get(route('asisten.dashboard'))->assertRedirect(route('asisten.login'));

        $this->post('/asisten/login/2fa', ['kode' => '123456'])->assertSessionHasErrors('kode');
        $this->assertGuest('web');
        $this->post('/asisten/login/2fa', ['kode' => $this->kodeSekarang($rahasia, 1)])->assertRedirect(route('masuk.cek', ['ke' => '/asisten']));
        $this->assertAuthenticatedAs($u, 'web');
    }

    public function test_kode_yang_sama_tidak_bisa_dipakai_ulang(): void
    {
        [$rahasia] = $this->aktifkan();
        $kode = $this->kodeSekarang($rahasia, 1);
        $this->loginSandi();
        $this->post('/asisten/login/2fa', ['kode' => $kode])->assertRedirect(route('masuk.cek', ['ke' => '/asisten']));
        auth('web')->logout();
        $this->flushSession();

        $this->loginSandi();
        $this->post('/asisten/login/2fa', ['kode' => $kode])->assertSessionHasErrors('kode');
        $this->assertGuest('web');
    }

    public function test_kode_cadangan_sekali_pakai(): void
    {
        [, $cadangan] = $this->aktifkan();
        $this->loginSandi();
        $this->post('/asisten/login/2fa', ['kode' => strtolower($cadangan[0])])->assertRedirect(route('masuk.cek', ['ke' => '/asisten']));
        $this->assertCount(7, $this->asisten->fresh()->dua_langkah_cadangan);
        auth('web')->logout();
        $this->flushSession();

        $this->loginSandi();
        $this->post('/asisten/login/2fa', ['kode' => $cadangan[0]])->assertSessionHasErrors('kode');
    }

    public function test_lima_kali_salah_harus_login_ulang(): void
    {
        $this->aktifkan();
        $this->loginSandi();
        for ($i = 0; $i < 4; $i++) {
            $this->post('/asisten/login/2fa', ['kode' => '111111'])->assertSessionHasErrors('kode');
        }
        $this->post('/asisten/login/2fa', ['kode' => '111111'])->assertRedirect(route('asisten.login'));
        $this->get(route('asisten.login.2fa'))->assertRedirect(route('asisten.login'));
    }

    public function test_tanpa_lewat_kata_sandi_tidak_bisa_ke_halaman_kode(): void
    {
        $this->aktifkan();
        $this->get(route('asisten.login.2fa'))->assertRedirect(route('asisten.login'));
        $this->post('/asisten/login/2fa', ['kode' => '123456'])->assertRedirect(route('asisten.login'));
    }

    public function test_akun_tanpa_2fa_tetap_login_biasa_dan_diingatkan(): void
    {
        $this->loginSandi()->assertRedirect(route('masuk.cek', ['ke' => '/asisten']));
        $this->get(route('asisten.dashboard'))->assertSee('Aktifkan 2FA');
    }

    public function test_matikan_butuh_sandi_dan_kode(): void
    {
        [$rahasia] = $this->aktifkan();
        $this->actingAs($this->asisten->fresh(), 'web');
        $this->delete(route('asisten.2fa.matikan'), ['password' => 'salah', 'kode' => $this->kodeSekarang($rahasia, 1)])->assertSessionHasErrors('password_2fa');
        $this->assertTrue($this->asisten->fresh()->pakaiDuaLangkah());
        $this->delete(route('asisten.2fa.matikan'), ['password' => 'asisten123', 'kode' => $this->kodeSekarang($rahasia, 1)])->assertSessionHas('ok');
        $this->assertFalse($this->asisten->fresh()->pakaiDuaLangkah());
        $this->assertTrue(LogAktivitas::where('aksi', '2fa_mati')->exists());
    }

    public function test_admin_bisa_reset_2fa_asisten_lain(): void
    {
        $this->aktifkan();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@kampus.ac.id', 'password' => 'admin123', 'role' => 'admin']);
        $this->actingAs($this->asisten->fresh(), 'web')->delete(route('asisten.akun.2fa', $this->asisten))->assertForbidden();

        $this->actingAs($admin, 'web')->get(route('asisten.laboran.akun'))->assertSee('Reset 2FA');
        $this->delete(route('asisten.akun.2fa', $this->asisten))->assertSessionHas('ok');
        $this->assertFalse($this->asisten->fresh()->pakaiDuaLangkah());
        $this->assertTrue(LogAktivitas::where('aksi', '2fa_reset')->exists());
    }
}
