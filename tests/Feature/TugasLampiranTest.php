<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\Tugas;
use App\Models\TugasLampiran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TugasLampiranTest extends TestCase
{
    use RefreshDatabase;

    private User $asisten;

    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->asisten = User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->kelas = Kelas::create(['asisten_id' => $this->asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 4]);
    }

    private function isian(array $tambah = []): array
    {
        return $tambah + ['pertemuan' => 1, 'judul' => 'Laporan ERD', 'tenggat' => now()->addWeek()->format('Y-m-d\TH:i'), 'terima_terlambat' => '1'];
    }

    public function test_asisten_buat_tugas_dengan_banyak_file_dan_tautan(): void
    {
        $this->actingAs($this->asisten, 'web')->get(route('asisten.kelas.show', [$this->kelas, 'tab' => 'tugas']))
            ->assertOk()->assertSee('name="lampiran[]" multiple', false)->assertSee('enctype="multipart/form-data"', false);

        $this->post(route('asisten.tugas.store', $this->kelas), $this->isian([
            'lampiran' => [UploadedFile::fake()->create('soal.pdf', 40), UploadedFile::fake()->create('template.docx', 20)],
            'tautan' => "https://drive.google.com/abc\n\n https://youtu.be/xyz \nhttps://drive.google.com/abc",
        ]))->assertRedirect();

        $t = Tugas::firstOrFail();
        $this->assertSame(['soal.pdf', 'template.docx', null, null], $t->lampiran->pluck('nama_file')->all());
        $this->assertSame(['https://drive.google.com/abc', 'https://youtu.be/xyz'], $t->lampiran->whereNull('path')->pluck('url')->values()->all());
        $this->assertSame([['PDF', 'pdf'], ['DOC', 'doc'], ['URL', 'url'], ['VID', 'vid']], $t->lampiran->map->jenis()->all());
        $t->lampiran->whereNotNull('path')->each(fn ($l) => Storage::assertExists($l->path));
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'tugas_tambah', 'keterangan' => 'Membuat tugas P1: Laporan ERD (tenggat '.$t->tenggat->format('d/m/Y H:i').') dengan 4 lampiran']);

        $this->get(route('asisten.tugas.show', $t))->assertOk()->assertSee('Lampiran untuk mahasiswa (4)')->assertSee('https://youtu.be/xyz');
        $this->get(route('asisten.tugas.lampiran', [$t, $t->lampiran[0]]))->assertOk()->assertDownload('soal.pdf');

        // tambah lagi saat mengubah tugas, lalu hapus satu
        $this->put(route('asisten.tugas.update', $t), $this->isian(['lampiran' => [UploadedFile::fake()->create('contoh.zip', 10)]]))
            ->assertSessionHas('ok', 'Tugas diperbarui, 1 lampiran ditambahkan.');
        $this->assertSame(5, $t->lampiran()->count());
        $soal = $t->lampiran[0];
        $this->delete(route('asisten.tugas.lampiran.hapus', [$t, $soal]))->assertSessionHas('ok');
        Storage::assertMissing($soal->path);
        $this->assertSame(4, $t->lampiran()->count());
    }

    public function test_mahasiswa_peserta_bisa_unduh_lampiran_yang_lain_tidak(): void
    {
        $t = Tugas::create(['kelas_id' => $this->kelas->id, 'pertemuan' => 1, 'judul' => 'ERD', 'tenggat' => now()->addDay(), 'terima_terlambat' => false]);
        $file = $t->lampiran()->create(['judul' => 'soal', 'nama_file' => 'soal.pdf', 'ukuran' => 10, 'path' => UploadedFile::fake()->create('x', 10)->store('tugas/1/lampiran')]);
        $t->lampiran()->create(['judul' => 'https://drive.google.com/abc', 'url' => 'https://drive.google.com/abc']);

        $peserta = Mahasiswa::create(['npm' => '1001', 'nama' => 'Peserta', 'email' => '1001@student.uir.ac.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        $this->kelas->mahasiswa()->attach($peserta->id);
        $this->actingAs($peserta, 'mahasiswa')->get(route('mhs.tugas', $t))->assertOk()
            ->assertSee('Lampiran dari asisten')->assertSee('soal.pdf')->assertSee('href="https://drive.google.com/abc" target="_blank" rel="noopener noreferrer"', false);
        $this->get(route('mhs.tugas.lampiran', [$t, $file]))->assertOk()->assertDownload('soal.pdf');

        // lampiran tugas lain lewat URL tugas ini ditolak
        $lain = Tugas::create(['kelas_id' => $this->kelas->id, 'pertemuan' => 2, 'judul' => 'Lain', 'tenggat' => now()->addDay(), 'terima_terlambat' => false]);
        $this->get(route('mhs.tugas.lampiran', [$lain, $file]))->assertNotFound();

        $orangLuar = Mahasiswa::create(['npm' => '2002', 'nama' => 'Luar', 'email' => '2002@student.uir.ac.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        $this->actingAs($orangLuar, 'mahasiswa')->get(route('mhs.tugas.lampiran', [$t, $file]))->assertForbidden();

        // asisten kelas lain tidak bisa mengunduh/menghapus
        $lainAsisten = User::create(['name' => 'B', 'email' => 'b@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->actingAs($lainAsisten, 'web')->get(route('asisten.tugas.lampiran', [$t, $file]))->assertForbidden();
        $this->delete(route('asisten.tugas.lampiran.hapus', [$t, $file]))->assertForbidden();

        // hapus tugas ikut menghapus file lampiran
        $t->delete();
        Storage::assertMissing($file->path);
        $this->assertSame(0, TugasLampiran::count());
    }

    public function test_validasi_lampiran(): void
    {
        $this->actingAs($this->asisten, 'web');
        $this->post(route('asisten.tugas.store', $this->kelas), $this->isian(['lampiran' => [UploadedFile::fake()->create('virus.exe', 5)]]))
            ->assertSessionHasErrors('lampiran.0');
        $this->post(route('asisten.tugas.store', $this->kelas), $this->isian(['tautan' => "https://ok.com\njavascript:alert(1)"]))
            ->assertSessionHasErrors(['daftar_tautan.1' => 'Tautan baris ke-2 bukan alamat web yang valid (harus diawali http:// atau https://).']);
        $this->assertSame(0, Tugas::count());

        // kuota per tugas
        $t = Tugas::create(['kelas_id' => $this->kelas->id, 'pertemuan' => 1, 'judul' => 'ERD', 'tenggat' => now()->addDay(), 'terima_terlambat' => false]);
        for ($i = 0; $i < 19; $i++) {
            $t->lampiran()->create(['judul' => "u{$i}", 'url' => "https://contoh.com/{$i}"]);
        }
        $this->put(route('asisten.tugas.update', $t), $this->isian(['tautan' => "https://a.com\nhttps://b.com"]))->assertSessionHasErrors('lampiran');
        $this->put(route('asisten.tugas.update', $t), $this->isian(['tautan' => 'https://a.com']))->assertSessionHas('ok');
        $this->assertSame(20, $t->lampiran()->count());
    }
}
