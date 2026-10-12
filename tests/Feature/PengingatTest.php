<?php

namespace Tests\Feature;

use App\Mail\Pemberitahuan;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Support\Kehadiran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PengingatTest extends TestCase
{
    use RefreshDatabase;

    private User $asisten;

    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asisten = User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->kelas = Kelas::create(['asisten_id' => $this->asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 8, 'min_hadir' => 75]);
    }

    private function mhs(string $npm, bool $verif = true): Mahasiswa
    {
        $m = Mahasiswa::create(['npm' => $npm, 'nama' => 'Mhs '.$npm, 'email' => $npm.'@contoh.ac.id', 'email_verified_at' => $verif ? now() : null, 'password' => 'rahasia123']);
        $this->kelas->mahasiswa()->attach($m->id);

        return $m;
    }

    private function absen(Mahasiswa $m, array $status): void
    {
        foreach ($status as $p => $s) {
            Absensi::create(['kelas_id' => $this->kelas->id, 'mahasiswa_id' => $m->id, 'pertemuan' => $p + 1, 'status' => $s, 'metode' => 'manual']);
        }
    }

    public function test_jatah_tidak_hadir_dihitung_dari_minimal_kehadiran(): void
    {
        $this->assertSame(2, $this->kelas->maksTidakHadir()); // 8 pertemuan, 75% = wajib hadir 6
        $this->assertSame('aman', Kehadiran::untuk($this->kelas, ['H', 'H'])->level());
        $this->assertSame('waspada', Kehadiran::untuk($this->kelas, ['H', 'A'])->level());
        $this->assertSame('batas', Kehadiran::untuk($this->kelas, ['I', 'S'])->level());
        $this->assertSame('lewat', Kehadiran::untuk($this->kelas, ['A', 'S', 'A'])->level());

        $this->kelas->update(['jumlah_pertemuan' => 3]); // 75% dari 3 = wajib hadir semua
        $this->assertSame('aman', Kehadiran::untuk($this->kelas, [])->level());
        $this->assertSame('lewat', Kehadiran::untuk($this->kelas, ['A'])->level());
    }

    public function test_email_peringatan_kehadiran_terkirim_sekali(): void
    {
        Mail::fake();
        $batas = $this->mhs('50421001');
        $this->absen($batas, ['H', 'A', 'S']);
        $aman = $this->mhs('50421002');
        $this->absen($aman, ['H', 'A']);
        $belumVerif = $this->mhs('50421003', false);
        $this->absen($belumVerif, ['A', 'A', 'A']);

        $this->artisan('flex:kirim-pengingat')->assertSuccessful();
        $this->artisan('flex:kirim-pengingat')->assertSuccessful();

        Mail::assertSent(Pemberitahuan::class, 1);
        Mail::assertSent(Pemberitahuan::class, fn ($m) => $m->hasTo($batas->email) && str_contains($m->subjek, 'Peringatan kehadiran'));

        // naik ke "lewat": email kedua dengan isi berbeda
        Absensi::create(['kelas_id' => $this->kelas->id, 'mahasiswa_id' => $batas->id, 'pertemuan' => 4, 'status' => 'A', 'metode' => 'manual']);
        $this->artisan('flex:kirim-pengingat');
        Mail::assertSent(Pemberitahuan::class, fn ($m) => $m->hasTo($batas->email) && str_contains($m->subjek, 'di bawah batas'));
        Mail::assertSent(Pemberitahuan::class, 2);
    }

    public function test_pengingat_tugas_h1_hanya_untuk_yang_belum_mengumpulkan(): void
    {
        Mail::fake();
        $sudah = $this->mhs('50421001');
        $belum = $this->mhs('50421002');
        $besok = $this->kelas->tugas()->create(['pertemuan' => 1, 'judul' => 'Laporan 1', 'tenggat' => now()->addHours(20), 'terima_terlambat' => true]);
        $this->kelas->tugas()->create(['pertemuan' => 2, 'judul' => 'Laporan 2', 'tenggat' => now()->addDays(3), 'terima_terlambat' => true]);
        $besok->pengumpulan()->create(['mahasiswa_id' => $sudah->id, 'path' => 'x', 'nama_file' => 'a.pdf', 'ukuran' => 1, 'dikumpulkan_at' => now()]);

        $this->artisan('flex:kirim-pengingat');
        $this->artisan('flex:kirim-pengingat');

        Mail::assertSent(Pemberitahuan::class, 1);
        Mail::assertSent(Pemberitahuan::class, fn ($m) => $m->hasTo($belum->email) && str_contains($m->subjek, 'Laporan 1'));
    }

    public function test_jatah_tampil_di_beranda_dan_asisten(): void
    {
        $m = $this->mhs('50421001');
        $this->absen($m, ['A', 'A']);

        $r = $this->actingAs($m, 'mahasiswa')->get(route('mhs.home'));
        $r->assertOk()->assertSee('Jatah tidak hadir habis');
        $r = $this->actingAs($this->asisten, 'web')->get(route('asisten.kelas.show', $this->kelas));
        $r->assertOk()->assertSee('mendekati atau melewati batas kehadiran 75%')->assertSee('tidak hadir 2/2');
    }
}
