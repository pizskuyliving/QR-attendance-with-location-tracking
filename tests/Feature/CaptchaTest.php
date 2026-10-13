<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['captcha.turnstile.site_key' => 'site-key-asli', 'captcha.turnstile.secret_key' => 'secret-asli']);
    }

    private function mahasiswa(): Mahasiswa
    {
        return Mahasiswa::create(['npm' => '50421001', 'nama' => 'Andi', 'email' => 'andi@contoh.ac.id',
            'email_verified_at' => now(), 'password' => 'rahasia123']);
    }

    private function login(array $tambahan = [])
    {
        return $this->from('/')->post('/login', ['email' => 'andi@contoh.ac.id', 'password' => 'rahasia123'] + $tambahan);
    }

    public function test_halaman_login_hanya_menampilkan_turnstile(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-sitekey="site-key-asli"', false)
            ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js', false)
            ->assertDontSee('imgCaptcha', false)
            ->assertDontSee('Mode uji');
        $this->get('/asisten/login')->assertSee('data-sitekey="site-key-asli"', false);
        $this->get('/daftar')->assertSee('data-sitekey="site-key-asli"', false);
        $this->get('/captcha')->assertNotFound();
    }

    public function test_tanpa_key_di_local_memakai_key_uji(): void
    {
        config(['captcha.turnstile.site_key' => null, 'captcha.turnstile.secret_key' => null]);
        $this->get('/')->assertSee('data-sitekey="1x00000000000000000000AA"', false)->assertSee('Mode uji');
    }

    public function test_tanpa_key_di_production_login_ditolak(): void
    {
        config(['captcha.turnstile.site_key' => null, 'captcha.turnstile.secret_key' => null]);
        $this->app['env'] = 'production';
        $this->mahasiswa();
        Http::fake();

        $this->get('/')->assertSee('Captcha belum dikonfigurasi')->assertDontSee('data-sitekey', false);
        $req = \Illuminate\Http\Request::create('/login', 'POST', ['cf-turnstile-response' => 'x']);
        $this->assertSame('Captcha belum dikonfigurasi oleh laboran. Login sementara tidak bisa dilakukan.', \App\Support\Captcha::periksa($req));
        Http::assertNothingSent();
    }

    public function test_lolos_cloudflare_bisa_login(): void
    {
        $this->mahasiswa();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->login(['cf-turnstile-response' => 'token-ok'])->assertRedirect(route('masuk.cek', ['ke' => '/m']));
        $this->assertAuthenticated('mahasiswa');
        Http::assertSent(fn ($r) => $r['secret'] === 'secret-asli' && $r['response'] === 'token-ok');
    }

    public function test_ditolak_cloudflare_tidak_bisa_login(): void
    {
        $this->mahasiswa();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->login(['cf-turnstile-response' => 'palsu'])->assertSessionHasErrors(['captcha' => 'Verifikasi anti-bot gagal. Muat ulang halaman lalu coba lagi.']);
        $this->assertGuest('mahasiswa');
        $this->followingRedirects()->login(['cf-turnstile-response' => 'palsu'])->assertSee('id="toastCaptcha"', false);
    }

    public function test_token_kedaluwarsa(): void
    {
        $this->mahasiswa();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['timeout-or-duplicate']])]);
        $this->login(['cf-turnstile-response' => 'lama'])->assertSessionHasErrors(['captcha' => 'Verifikasi anti-bot kedaluwarsa. Ulangi centang lalu kirim lagi.']);
    }

    public function test_tanpa_token_ditolak_tanpa_menghubungi_cloudflare(): void
    {
        $this->mahasiswa();
        Http::fake();
        $this->login()->assertSessionHasErrors('captcha');
        $this->assertGuest('mahasiswa');
        Http::assertNothingSent();
    }

    public function test_cloudflare_tidak_bisa_dihubungi(): void
    {
        $this->mahasiswa();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));
        $this->login(['cf-turnstile-response' => 't'])->assertSessionHasErrors(['captcha' => 'Layanan verifikasi anti-bot tidak bisa dihubungi. Coba lagi sebentar.']);
    }

    public function test_login_asisten_juga_diperiksa(): void
    {
        User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

        $data = ['email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'cf-turnstile-response' => 't'];
        $this->from('/asisten/login')->post('/asisten/login', $data)->assertSessionHasErrors('captcha');
        $this->assertGuest();
        $this->from('/asisten/login')->post('/asisten/login', $data)->assertRedirect(route('masuk.cek', ['ke' => '/asisten']));
        $this->assertAuthenticated();
    }

    public function test_daftar_ditolak_bila_captcha_gagal(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->from('/daftar')->post('/daftar', ['npm' => '50421099', 'kelas' => 'A', 'nama' => 'Budi Santoso', 'email' => 'budi@student.uir.ac.id',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'cf-turnstile-response' => 't'])
            ->assertSessionHasErrors('captcha');
        $this->assertDatabaseMissing('mahasiswa', ['npm' => '50421099']);
    }
}
