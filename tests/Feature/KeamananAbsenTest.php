<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\SesiAbsen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KeamananAbsenTest extends TestCase
{
    use RefreshDatabase;

    private User $asisten;

    private Kelas $kelas;

    private SesiAbsen $sesi;

    // titik kelas contoh
    private const LAT = -6.3536;

    private const LNG = 106.8418;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asisten = User::create(['name' => 'Asisten', 'email' => 'asisten@kampus.ac.id', 'password' => 'asisten123', 'role' => 'asisten']);
        $this->kelas = Kelas::create(['asisten_id' => $this->asisten->id, 'nama_kelas' => 'A', 'mata_kuliah' => 'Basis Data', 'jumlah_pertemuan' => 8]);
        $this->sesi = $this->kelas->sesi()->create(['pertemuan' => 1, 'secret' => Str::random(40), 'dibuka_at' => now()]);
    }

    private function mhs(string $npm): Mahasiswa
    {
        $m = Mahasiswa::create(['npm' => $npm, 'nama' => 'Mhs '.$npm, 'email' => $npm.'@contoh.ac.id', 'email_verified_at' => now(), 'password' => 'rahasia123']);
        $this->kelas->mahasiswa()->attach($m->id);

        return $m;
    }

    private function scan(Mahasiswa $m, string $hp, float $lat = self::LAT, float $lng = self::LNG, ?int $akurasi = 15)
    {
        return $this->actingAs($m, 'mahasiswa')->withCredentials()->withCookie('absen_perangkat', str_pad($hp, 40, 'x'))
            ->postJson(route('mhs.absen'), ['kode' => $this->sesi->payload(), 'lat' => $lat, 'lng' => $lng, 'akurasi' => $akurasi]);
    }

    private function aturRadius(int $radius = 100): void
    {
        $this->kelas->update(['lokasi_lat' => self::LAT, 'lokasi_lng' => self::LNG, 'radius' => $radius]);
    }

    public function test_scan_di_luar_radius_ditolak_dan_dicatat(): void
    {
        $this->aturRadius(100);
        $m = $this->mhs('50421001');

        // ±1,1 km ke utara
        $this->scan($m, 'hp-a', self::LAT + 0.01)->assertStatus(403)->assertJson(['ok' => false])
            ->assertJsonPath('pesan', fn ($p) => str_contains($p, 'batas 100 m'));
        $this->assertDatabaseMissing('absensi', ['mahasiswa_id' => $m->id]);
        $this->assertTrue(LogAktivitas::where('aksi', 'scan_ditolak')->where('keterangan', 'like', '%di luar radius%')->exists());

        // ±80 m dari titik: diterima, jarak tersimpan
        $this->scan($m, 'hp-a', self::LAT + 0.00072)->assertOk()->assertJson(['ok' => true]);
        $a = Absensi::where('mahasiswa_id', $m->id)->first();
        $this->assertEqualsWithDelta(80, $a->jarak, 3);
    }

    public function test_kelonggaran_akurasi_maksimal_50_meter(): void
    {
        $this->aturRadius(100);
        $m = $this->mhs('50421001');
        // ±133 m dengan akurasi 40 -> batas 140, diterima
        $this->scan($m, 'hp-a', self::LAT + 0.0012, akurasi: 40)->assertOk();
        $m2 = $this->mhs('50421002');
        // ±178 m dengan akurasi 500 -> kelonggaran tetap 50, batas 150, ditolak
        $this->scan($m2, 'hp-b', self::LAT + 0.0016, akurasi: 500)->assertStatus(403);
    }

    public function test_tanpa_radius_scan_dari_mana_saja_diterima(): void
    {
        $m = $this->mhs('50421001');
        $this->scan($m, 'hp-a', -7.0, 110.0)->assertOk();
        $this->assertNull(Absensi::first()->jarak);
    }

    public function test_akun_terkunci_di_hp_pertama(): void
    {
        $m = $this->mhs('50421001');
        $this->scan($m, 'hp-a')->assertOk();
        $this->assertNotNull($m->fresh()->perangkat);

        $this->sesi = $this->kelas->sesi()->create(['pertemuan' => 2, 'secret' => Str::random(40), 'dibuka_at' => now()]);
        $this->scan($m, 'hp-lain')->assertStatus(403)->assertJsonPath('pesan', fn ($p) => str_contains($p, 'terkunci di HP lain'));
        $this->assertDatabaseMissing('absensi', ['mahasiswa_id' => $m->id, 'pertemuan' => 2]);
        $this->scan($m, 'hp-a')->assertOk();
    }

    public function test_satu_hp_tidak_bisa_untuk_dua_akun(): void
    {
        $andi = $this->mhs('50421001');
        $budi = $this->mhs('50421002');
        $this->scan($andi, 'hp-andi')->assertOk();

        $this->scan($budi, 'hp-andi', self::LAT + 0.00001)->assertStatus(403)
            ->assertJsonPath('pesan', 'HP ini sudah dipakai absen oleh akun lain. Satu HP hanya untuk satu akun mahasiswa.');
        $this->assertNull($budi->fresh()->perangkat);
        $this->assertTrue(LogAktivitas::where('keterangan', 'like', '%50421002%terikat ke 50421001%')->exists());
    }

    public function test_asisten_reset_hp_dan_tercatat_di_log(): void
    {
        $m = $this->mhs('50421001');
        $this->scan($m, 'hp-a')->assertOk();

        $this->actingAs($this->asisten, 'web')->post(route('asisten.mhs.perangkat', [$this->kelas, $m]))->assertSessionHas('ok');
        $this->assertNull($m->fresh()->perangkat);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'reset_perangkat', 'user_id' => $this->asisten->id, 'kelas_id' => $this->kelas->id]);

        $this->sesi = $this->kelas->sesi()->create(['pertemuan' => 2, 'secret' => Str::random(40), 'dibuka_at' => now()]);
        $this->scan($m->fresh(), 'hp-baru')->assertOk();
    }

    public function test_hp_baru_tanpa_cookie_mendapat_penanda_dari_halaman_scan(): void
    {
        $m = $this->mhs('50421001');
        $this->actingAs($m, 'mahasiswa')->get(route('mhs.scan'))->assertOk()->assertCookie('absen_perangkat');
    }

    public function test_tanda_koordinat_kembar_dan_akurasi_rendah(): void
    {
        $andi = $this->mhs('50421001');
        $budi = $this->mhs('50421002');
        $cici = $this->mhs('50421003');
        $this->scan($andi, 'hp-andi')->assertOk();
        $this->scan($budi, 'hp-budi')->assertOk();                       // koordinat sama persis
        $this->scan($cici, 'hp-cici', self::LAT + 0.0003, akurasi: 300)->assertOk();

        $tanda = fn (Mahasiswa $m) => Absensi::where('mahasiswa_id', $m->id)->first()->tanda;
        $this->assertSame(['Koordinat sama persis dengan 50421001'], $tanda($budi));
        $this->assertSame(['Koordinat sama persis dengan 50421002'], $tanda($andi));
        $this->assertSame(['Akurasi GPS rendah (±300 m)'], $tanda($cici));

        $this->actingAs($this->asisten, 'web')->get(route('asisten.kelas.show', [$this->kelas, 'tab' => 'riwayat', 'curiga' => 1]))
            ->assertSee('Koordinat sama persis dengan 50421001')->assertSee('Akurasi GPS rendah');
        $hadir = collect($this->actingAs($this->asisten, 'web')->getJson(route('asisten.sesi.status', $this->sesi))->json('hadir'))->keyBy('npm');
        $this->assertSame(['Akurasi GPS rendah (±300 m)'], $hadir['50421003']['tanda']);
        $this->assertSame(['Koordinat sama persis dengan 50421002'], $hadir['50421001']['tanda']);
    }

    public function test_perubahan_absen_oleh_asisten_tercatat(): void
    {
        $m = $this->mhs('50421001');
        $this->scan($m, 'hp-a')->assertOk();
        $this->actingAs($this->asisten, 'web');

        $this->postJson(route('asisten.absensi.sel', $this->kelas), ['mahasiswa_id' => $m->id, 'pertemuan' => 1, 'status' => 'A'])->assertOk();
        $this->postJson(route('asisten.absensi.sel', $this->kelas), ['mahasiswa_id' => $m->id, 'pertemuan' => 1, 'status' => null])->assertOk();
        $this->post(route('asisten.absensi.massal', $this->kelas), ['pertemuan' => 2, 'status' => 'I']);
        $this->put(route('asisten.mhs.update', [$this->kelas, $m]), ['npm' => '50421001', 'nama' => 'Nama Baru']);
        $this->post(route('asisten.mhs.reset', [$this->kelas, $m]));

        $log = LogAktivitas::where('user_id', $this->asisten->id)->orderBy('id')->pluck('keterangan')->all();
        $this->assertSame([
            'Absen 50421001 Mhs 50421001 P1: Hadir → Alpa',
            'Absen 50421001 Mhs 50421001 P1: Alpa → kosong',
            'Mengisi 1 absen kosong pertemuan 2 menjadi Izin',
            'Mengubah data 50421001 Mhs 50421001 menjadi 50421001 Nama Baru',
            'Mereset kata sandi 50421001 Nama Baru',
        ], $log);
        $this->assertSame(['mahasiswa_id' => $m->id, 'pertemuan' => 1, 'lama' => 'H', 'baru' => 'A', 'metode_lama' => 'qr'],
            LogAktivitas::where('aksi', 'ubah_absen')->first()->data);

        $this->get(route('asisten.kelas.show', [$this->kelas, 'tab' => 'log']))->assertOk()
            ->assertSee('P1: Hadir → Alpa')->assertSee('Asisten');
    }

    public function test_ubah_kelas_dan_radius_tercatat_dan_hanya_berisi_yang_berubah(): void
    {
        $this->actingAs($this->asisten, 'web')->put(route('asisten.kelas.update', $this->kelas), [
            'mata_kuliah' => 'Basis Data', 'nama_kelas' => 'A', 'jumlah_pertemuan' => 8,
            'lokasi_lat' => self::LAT, 'lokasi_lng' => self::LNG, 'radius' => 100,
        ])->assertSessionHas('ok');

        $this->assertTrue($this->kelas->fresh()->punyaRadius());
        $this->assertSame('Mengubah pengaturan kelas: lokasi_lat, lokasi_lng, radius', LogAktivitas::where('aksi', 'ubah_kelas')->value('keterangan'));

        // radius tanpa titik ditolak
        $this->put(route('asisten.kelas.update', $this->kelas), ['mata_kuliah' => 'Basis Data', 'nama_kelas' => 'A', 'jumlah_pertemuan' => 8, 'radius' => 100])
            ->assertSessionHasErrors(['lokasi_lat', 'lokasi_lng']);
    }

    public function test_asisten_lain_tidak_bisa_melihat_log(): void
    {
        $lain = User::create(['name' => 'Lain', 'email' => 'lain@kampus.ac.id', 'password' => 'x12345678', 'role' => 'asisten']);
        $this->actingAs($lain, 'web')->get(route('asisten.kelas.show', [$this->kelas, 'tab' => 'log']))->assertForbidden();
        $m = $this->mhs('50421001');
        $this->actingAs($lain, 'web')->post(route('asisten.mhs.perangkat', [$this->kelas, $m]))->assertForbidden();
    }
}
