<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Matkul;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaboranTest extends TestCase
{
    use RefreshDatabase;

    private function laboran(): User
    {
        return User::create(['name' => 'Pak Laboran', 'email' => 'lab@kampus.ac.id', 'password' => 'laboran123', 'role' => 'admin']);
    }

    public function test_laboran_melihat_daftar_dosen_dengan_matkul_kelas_dan_asisten(): void
    {
        $lab = $this->laboran();
        $dosen = User::create(['name' => 'Dr. Sari', 'email' => 'sari@kampus.ac.id', 'password' => 'dosen12345', 'role' => 'dosen']);
        User::create(['name' => 'Dr. Tanpa Matkul', 'email' => 'tm@kampus.ac.id', 'password' => 'dosen12345', 'role' => 'dosen']);
        $asisten = User::create(['name' => 'Budi Asisten', 'email' => 'budi@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $m = Matkul::create(['dosen_id' => $dosen->id, 'nama' => 'Machine Learning', 'kode' => 'IF401']);
        Kelas::create(['matkul_id' => $m->id, 'asisten_id' => $asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Machine Learning', 'hari' => 'Senin 08.00', 'jumlah_pertemuan' => 8]);
        Kelas::create(['matkul_id' => $m->id, 'nama_kelas' => 'B', 'mata_kuliah' => 'Machine Learning', 'jumlah_pertemuan' => 8]);

        $this->actingAs($lab)->get(route('asisten.laboran.dosen'))->assertOk()
            ->assertSee('Dr. Sari')->assertSee('Machine Learning')->assertSee('IF401')->assertSee('Budi Asisten')
            ->assertSee('1 kelas belum punya asisten')->assertSee('Dr. Tanpa Matkul')->assertSee('Belum mengampu mata kuliah');
        $this->actingAs($lab)->get(route('asisten.laboran.dosen', ['cari' => 'sari']))->assertSee('Dr. Sari')->assertDontSee('Dr. Tanpa Matkul');

        $this->actingAs($lab)->get(route('asisten.laboran.akun', ['peran' => 'dosen']))->assertOk()->assertSee('Dr. Sari')->assertSee('1 matkul')->assertDontSee('Budi Asisten</td>', false);
        $this->actingAs($lab)->get(route('asisten.laboran.akun', ['cari' => 'budi']))->assertSee('Budi Asisten')->assertDontSee('Dr. Sari</td>', false);
        $this->actingAs($lab)->get(route('asisten.profil'))->assertSee('Peran: Laboran')->assertDontSee('Kelola akun</h2>', false);
        $this->actingAs($dosen)->get(route('asisten.laboran.akun'))->assertRedirect(route('asisten.matkul.index'))->assertSessionHasErrors('peran');
        $this->actingAs($asisten)->post(route('asisten.akun.store'), [])->assertForbidden();
        $this->actingAs($dosen)->get(route('asisten.laboran.dosen'))->assertRedirect(route('asisten.matkul.index'));
        $this->actingAs($asisten)->get(route('asisten.laboran.sistem'))->assertRedirect(route('asisten.dashboard'));
    }

    public function test_laboran_mengubah_pengaturan_sistem(): void
    {
        $lab = $this->laboran();
        $this->actingAs($lab)->get(route('asisten.laboran.sistem'))->assertOk()->assertSee('Universitas Islam Riau');

        $isi = ['institusi' => 'Universitas Contoh', 'domain_email' => ' @mhs.contoh.ac.id, student.contoh.ac.id ', 'maks_unggah_mb' => 10,
            'huruf' => ['A' => 80, 'B' => 68, 'C' => 56, 'D' => 45], 'daftar_asisten' => '1'];
        $this->actingAs($lab)->put(route('asisten.laboran.sistem.simpan'), ['huruf' => ['A' => 50, 'B' => 60, 'C' => 40, 'D' => 30]] + $isi)
            ->assertSessionHasErrors('huruf.B');
        $this->actingAs($lab)->put(route('asisten.laboran.sistem.simpan'), $isi)->assertSessionHas('ok');

        $this->assertSame('Universitas Contoh', config('absen.institusi'));
        $this->assertSame(['mhs.contoh.ac.id', 'student.contoh.ac.id'], config('absen.domain_email'));
        $this->assertSame(10240, config('absen.maks_unggah_kb'));
        $this->assertSame(['A' => 80, 'B' => 68, 'C' => 56, 'D' => 45, 'E' => 0], config('absen.huruf'));
        $this->assertFalse(config('absen.daftar_mahasiswa'));
        $this->assertTrue(config('absen.daftar_asisten'));

        // pendaftaran mahasiswa ditutup: tautan hilang dan halaman daftar dialihkan
        auth()->logout();
        $this->get('/')->assertSee('Pendaftaran akun mahasiswa sedang ditutup');
        $this->get(route('daftar'))->assertRedirect(route('login'));
        $this->get(route('asisten.daftar'))->assertOk();

        // tersimpan di database dan dipasang lagi saat aplikasi mulai
        config(['absen.institusi' => 'lama']);
        \Illuminate\Support\Facades\Cache::flush();
        \App\Models\Pengaturan::terapkan();
        $this->assertSame('Universitas Contoh', config('absen.institusi'));
    }
}
