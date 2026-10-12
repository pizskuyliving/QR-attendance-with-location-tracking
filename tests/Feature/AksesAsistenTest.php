<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AksesAsistenTest extends TestCase
{
    use RefreshDatabase;

    public function test_asisten_tidak_bisa_mengubah_mahasiswa_kelas_lain_lewat_kelasnya_sendiri(): void
    {
        $a = User::create(['name' => 'A', 'email' => 'a@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $b = User::create(['name' => 'B', 'email' => 'b@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $kelasA = Kelas::create(['asisten_id' => $a->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 8]);
        $kelasB = Kelas::create(['asisten_id' => $b->id, 'nama_kelas' => 'B', 'mata_kuliah' => 'Jaringan', 'jumlah_pertemuan' => 8]);
        $m = Mahasiswa::create(['npm' => '50421009', 'nama' => 'Milik B', 'email' => 'm@contoh.ac.id', 'password' => 'rahasia123']);
        $kelasB->mahasiswa()->attach($m->id);

        $this->actingAs($a)->post(route('asisten.mhs.reset', [$kelasA, $m]))->assertNotFound();
        $this->actingAs($a)->put(route('asisten.mhs.update', [$kelasA, $m]), ['npm' => '50421009', 'nama' => 'Diubah'])->assertNotFound();
        $this->actingAs($a)->delete(route('asisten.mhs.destroy', [$kelasA, $m]))->assertNotFound();

        $m->refresh();
        $this->assertSame('Milik B', $m->nama);
        $this->assertTrue(Hash::check('rahasia123', $m->password));
    }

    public function test_asisten_yang_ditolak_dikeluarkan_dari_sesi(): void
    {
        $u = User::create(['name' => 'C', 'email' => 'c@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten', 'status' => 'ditolak']);

        $this->actingAs($u)->get(route('asisten.dashboard'))->assertRedirect(route('asisten.login'));
        $this->assertGuest('web');
    }

    public function test_header_keamanan_terpasang(): void
    {
        $this->get(route('asisten.login'))
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
