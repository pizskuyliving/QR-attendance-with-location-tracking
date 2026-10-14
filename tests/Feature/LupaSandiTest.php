<?php

namespace Tests\Feature;

use App\Mail\KodeVerifikasi;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LupaSandiTest extends TestCase
{
    use RefreshDatabase;

    private Mahasiswa $mhs;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        Mail::fake();
        $this->mhs = Mahasiswa::create(['npm' => '50421001', 'nama' => 'Andi', 'email' => 'andi@student.uir.ac.id', 'email_verified_at' => now(), 'password' => 'lama12345']);
    }

    private function minta(string $peran, string $email)
    {
        return $this->post(route('lupa'), ['peran' => $peran, 'email' => $email, 'cf-turnstile-response' => 't']);
    }

    private function kodeTerkirim(string $email): string
    {
        $kode = null;
        Mail::assertSent(KodeVerifikasi::class, function (KodeVerifikasi $m) use ($email, &$kode) {
            $kode = $m->kode;

            return $m->hasTo($email) && $m->tujuan === 'sandi';
        });

        return $kode;
    }

    public function test_mahasiswa_atur_ulang_sandi_lewat_kode_email(): void
    {
        $this->get(route('lupa'))->assertOk()->assertSee('Kirim kode');
        $this->minta('mahasiswa', 'ANDI@student.uir.ac.id ')->assertRedirect(route('lupa.kode'));
        $kode = $this->kodeTerkirim('andi@student.uir.ac.id');
        $this->get(route('lupa.kode'))->assertOk()->assertSee('an••@student.uir.ac.id');

        $salah = $kode === '111111' ? '222222' : '111111';
        $this->post(route('lupa.kode'), ['kode' => $salah, 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertSessionHasErrors(['kode' => 'Kode salah. Sisa percobaan: 4.']);

        $this->post(route('lupa.kode'), ['kode' => $kode, 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertRedirect(route('login'))->assertSessionHas('ok');
        $this->assertTrue(Hash::check('baru12345', $this->mhs->fresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertTrue(LogAktivitas::where('aksi', 'lupa_sandi')->exists());

        // kode sekali pakai
        $this->post(route('lupa.kode'), ['kode' => $kode, 'password' => 'lagi12345', 'password_confirmation' => 'lagi12345'])->assertRedirect(route('lupa'));
    }

    public function test_staf_memakai_tabel_akun_staf(): void
    {
        $dosen = User::create(['name' => 'Dosen', 'email' => 'dosen@kampus.ac.id', 'password' => 'lama12345', 'role' => 'dosen']);
        $this->minta('dosen', 'dosen@kampus.ac.id')->assertRedirect(route('lupa.kode'));
        $kode = $this->kodeTerkirim('dosen@kampus.ac.id');
        $this->post(route('lupa.kode'), ['kode' => $kode, 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertRedirect(route('asisten.login'));
        $this->assertTrue(Hash::check('baru12345', $dosen->fresh()->password));
        $this->assertTrue(Hash::check('lama12345', $this->mhs->fresh()->password)); // akun mahasiswa tidak tersentuh
    }

    public function test_email_tak_terdaftar_tidak_dibocorkan(): void
    {
        $this->minta('mahasiswa', 'tidakada@student.uir.ac.id')->assertRedirect(route('lupa.kode'))
            ->assertSessionHas('ok', 'Jika email tersebut terdaftar, kode 6 angka sudah dikirim. Cek juga folder Spam.');
        // salah memilih peran juga tidak mengirim apa pun
        $this->minta('asisten', 'andi@student.uir.ac.id')->assertSessionHas('ok');
        // asisten yang belum disetujui laboran tidak bisa memakai jalur ini
        User::create(['name' => 'Baru', 'email' => 'baru@kampus.ac.id', 'password' => 'lama12345', 'role' => 'asisten', 'status' => 'menunggu']);
        $this->minta('asisten', 'baru@kampus.ac.id');
        Mail::assertNothingSent();
    }

    public function test_kode_dibatasi_percobaan_dan_waktu(): void
    {
        $this->minta('mahasiswa', 'andi@student.uir.ac.id');
        $kode = $this->kodeTerkirim('andi@student.uir.ac.id');
        $salah = $kode === '111111' ? '222222' : '111111';
        $data = ['password' => 'baru12345', 'password_confirmation' => 'baru12345'];
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('lupa.kode'), ['kode' => $salah] + $data);
        }
        $this->post(route('lupa.kode'), ['kode' => $kode] + $data)->assertSessionHasErrors(['kode' => 'Terlalu banyak kode salah. Tekan "Kirim ulang kode" untuk kode baru.']);
        $this->assertTrue(Hash::check('lama12345', $this->mhs->fresh()->password));

        // kirim ulang dalam 60 detik tidak membuat kode baru; setelah jeda, kode baru menggantikan yang lama
        $this->post(route('lupa.ulang'));
        Mail::assertSentCount(1);
        $this->travel(2)->minutes();
        $this->post(route('lupa.ulang'))->assertSessionHas('ok');
        Mail::assertSentCount(2);

        // kode kedaluwarsa setelah 15 menit
        $this->travel(16)->minutes();
        $this->post(route('lupa.kode'), ['kode' => '123456'] + $data)
            ->assertSessionHasErrors(['kode' => 'Kode sudah kedaluwarsa atau tidak berlaku. Tekan "Kirim ulang kode".']);
    }
}
