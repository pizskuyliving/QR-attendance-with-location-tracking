<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MasukPeranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['captcha.turnstile.site_key' => 'k', 'captcha.turnstile.secret_key' => 's']);
        Http::fake(['*' => Http::response(['success' => true])]);
        Mahasiswa::create(['npm' => '50421001', 'nama' => 'Andi', 'email' => 'andi@contoh.ac.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        User::create(['name' => 'Dosen', 'email' => 'dosen@kampus.ac.id', 'password' => 'dosen12345', 'role' => 'dosen']);
    }

    private function masuk(string $peran, string $email, string $sandi)
    {
        return $this->from('/')->post(route('masuk'), ['peran' => $peran, 'email' => $email, 'password' => $sandi, 'cf-turnstile-response' => 't']);
    }

    public function test_halaman_masuk_punya_pilihan_peran(): void
    {
        $this->get('/')->assertOk()->assertSee('Masuk sebagai')->assertSee('<option value="mahasiswa" selected>', false);
        $this->get(route('asisten.login'))->assertOk()->assertSee('<option value="mahasiswa" selected>', false); // selalu mulai dari Mahasiswa
    }

    public function test_masuk_sesuai_peran(): void
    {
        $this->masuk('mahasiswa', 'andi@contoh.ac.id', 'rahasia123')->assertRedirect(route('masuk.cek', ['ke' => '/m']));
        $this->assertAuthenticated('mahasiswa');
        $this->masuk('dosen', 'dosen@kampus.ac.id', 'dosen12345')->assertRedirect(route('masuk.cek', ['ke' => '/asisten']));
        $this->assertAuthenticated('web');
    }

    public function test_peran_salah_diberi_petunjuk_dan_pilihan_tetap(): void
    {
        $this->masuk('dosen', 'andi@contoh.ac.id', 'rahasia123')
            ->assertSessionHasErrors(['email' => 'Ini akun mahasiswa. Pilih "Masuk sebagai: Mahasiswa".'])
            ->assertSessionHasInput('peran', 'dosen');
        $this->masuk('mahasiswa', 'dosen@kampus.ac.id', 'dosen12345')
            ->assertSessionHasErrors(['email' => 'Akun ini bukan akun mahasiswa. Pilih peran Asisten, Dosen, atau Laboran.']);
        $this->masuk('mahasiswa', 'andi@contoh.ac.id', 'salah12345')->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        $this->assertGuest('web');
        $this->assertGuest('mahasiswa');
    }

    public function test_cookie_sesi_hilang_setelah_login_diberi_pesan_jelas(): void
    {
        // browser tidak mengirim cookie sesi kembali: jangan terpental diam-diam
        $this->get(route('masuk.cek', ['ke' => '/m']))->assertRedirect(route('login', ['sesi' => 'hilang']));
        $this->get(route('masuk.cek', ['ke' => '/asisten']))->assertRedirect(route('asisten.login', ['sesi' => 'hilang']));
        $this->get(route('login', ['sesi' => 'hilang']))->assertSee('tidak menyimpan cookie login');

        // sesi tersimpan: diteruskan ke tujuan, hanya path di situs ini
        $mhs = \App\Models\Mahasiswa::firstOrFail();
        $this->actingAs($mhs, 'mahasiswa')->get(route('masuk.cek', ['ke' => '/m/kelas']))->assertRedirect('/m/kelas');
        $this->get(route('masuk.cek', ['ke' => '//jahat.example/x']))->assertRedirect('/');
        $this->get(route('masuk.cek', ['ke' => 'https://jahat.example']))->assertRedirect('/');
    }

    public function test_halaman_tujuan_sebelum_login_disaring_sesuai_akun(): void
    {
        // tautan asisten dibuka dulu, lalu masuk sebagai mahasiswa: jangan diarahkan ke area asisten
        $this->get('/asisten/matkul')->assertRedirect(route('asisten.login'));
        $this->masuk('mahasiswa', 'andi@contoh.ac.id', 'rahasia123')->assertRedirect(route('masuk.cek', ['ke' => '/m']));
        $this->post('/logout');

        // halaman dosen dibuka dulu, lalu masuk sebagai asisten: ke beranda asisten, bukan 403
        User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->get('/asisten/matkul');
        $this->masuk('asisten', 'asisten@kampus.ac.id', 'asisten123')->assertRedirect(route('masuk.cek', ['ke' => '/asisten']));
        $this->post('/logout');

        // tujuan yang memang boleh tetap dipakai
        $this->get('/m/kelas');
        $this->masuk('mahasiswa', 'andi@contoh.ac.id', 'rahasia123')->assertRedirect(route('masuk.cek', ['ke' => '/m/kelas']));
    }
}
