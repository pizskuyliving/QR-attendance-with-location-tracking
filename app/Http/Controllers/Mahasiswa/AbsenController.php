<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\SesiAbsen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class AbsenController extends Controller
{
    public function home(Request $request)
    {
        $mhs = $request->user('mahasiswa');
        $kelas = $mhs->kelas()->with('asisten', 'asisten2')->get();
        $absen = $mhs->absensi()->get()->groupBy('kelas_id');

        $ringkasan = $kelas->map(function ($k) use ($absen) {
            $catatan = $absen[$k->id] ?? collect();
            $hadir = $catatan->where('status', 'H')->count();

            return [
                'kelas' => $k,
                'hadir' => $hadir,
                'persen' => $k->jumlah_pertemuan ? round($hadir / $k->jumlah_pertemuan * 100) : 0,
                'sesiAktif' => $k->sesiAktif(),
                'jatah' => \App\Support\Kehadiran::untuk($k, $catatan->pluck('status')),
            ];
        });

        // tugas yang belum dikumpulkan dan masih bisa dikirim, tenggat terdekat dulu
        $tugasAktif = \App\Models\Tugas::with('kelas')->whereIn('kelas_id', $kelas->pluck('id'))
            ->whereDoesntHave('pengumpulan', fn ($q) => $q->where('mahasiswa_id', $mhs->id))
            ->where(fn ($q) => $q->where('tenggat', '>', now()->subDays(7))->where('terima_terlambat', true)->orWhere('tenggat', '>', now()))
            ->orderBy('tenggat')->limit(5)->get();

        // UTS/UAS yang pengumpulan jawabannya sedang dibuka dan belum dikirim/dinilai
        $terkirim = \App\Models\JawabanUjian::where('mahasiswa_id', $mhs->id)->get(['kelas_id', 'jenis'])
            ->map(fn ($j) => $j->kelas_id.'-'.$j->jenis)->all();
        $ujianAktif = $kelas->flatMap(fn ($k) => collect(\App\Models\SoalUjian::JENIS)
            ->filter(fn ($label, $j) => $k->bukaJawaban($j) && $k->pivot->{'nilai_'.$j} === null && ! in_array($k->id.'-'.$j, $terkirim, true))
            ->map(fn ($label, $j) => ['kelas' => $k, 'jenis' => $j, 'label' => $label, 'tenggat' => $k->tenggatUjian($j)])
            ->values())
            ->sortBy(fn ($u) => $u['tenggat']->getTimestamp())->values();

        // kuis yang sedang dibuka di pertemuan yang ia hadiri dan belum dikirim
        $kuisAktif = \App\Models\Kuis::with('kelas')->whereIn('kelas_id', $kelas->pluck('id'))
            ->whereNotNull('dibuka_at')->where('ditutup_at', '>', now())
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('absensi')->whereColumn('absensi.kelas_id', 'kuis.kelas_id')
                ->whereColumn('absensi.pertemuan', 'kuis.pertemuan')->where('absensi.mahasiswa_id', $mhs->id)->where('absensi.status', 'H'))
            ->whereDoesntHave('jawaban', fn ($q) => $q->where('mahasiswa_id', $mhs->id)->whereNotNull('selesai_at'))
            ->orderBy('ditutup_at')->get();

        return view('mahasiswa.home', compact('mhs', 'ringkasan', 'tugasAktif', 'ujianAktif', 'kuisAktif'));
    }

    /** Nama cookie penanda HP. Isinya acak, terenkripsi oleh Laravel, berlaku 5 tahun. */
    public const COOKIE_PERANGKAT = 'absen_perangkat';

    /** Batas akurasi GPS (meter) yang masih dianggap wajar; di atas ini scan ditandai. */
    public const AKURASI_WAJAR = 100;

    public function scan(Request $request)
    {
        $this->tokenPerangkat($request); // pastikan HP sudah punya penanda sebelum scan

        return view('mahasiswa.scan');
    }

    public function absen(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:200',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'akurasi' => 'nullable|numeric|min:0',
        ]);
        if ($request->lat === null || $request->lng === null) {
            return response()->json(['ok' => false, 'pesan' => 'Lokasi tidak terbaca. Izinkan akses lokasi (GPS) untuk situs ini, lalu scan lagi.'], 422);
        }
        $mhs = $request->user('mahasiswa');

        $sesi = SesiAbsen::verifikasi($request->kode);
        if (is_string($sesi)) {
            return response()->json(['ok' => false, 'pesan' => $sesi], 422);
        }

        $kelas = $sesi->kelas;
        if (! $mhs->kelas()->whereKey($kelas->id)->exists()) {
            return response()->json(['ok' => false, 'pesan' => 'Kamu belum mengikuti '.$kelas->label().'. Buka Beranda → Pilih mata kuliah, ikuti mata kuliah ini, lalu scan lagi.'], 403);
        }

        // 1) satu akun = satu HP
        $perangkat = hash('sha256', $this->tokenPerangkat($request));
        if ($galat = $this->periksaPerangkat($mhs, $perangkat)) {
            $this->catatDitolak($kelas, $sesi, $mhs, $galat[0], $request);

            return response()->json(['ok' => false, 'pesan' => $galat[1]], 403);
        }

        $ada = Absensi::where(['kelas_id' => $kelas->id, 'mahasiswa_id' => $mhs->id, 'pertemuan' => $sesi->pertemuan])->first();
        if ($ada && $ada->status === 'H') {
            return response()->json([
                'ok' => true,
                'sudah' => true,
                'pesan' => 'Kamu sudah tercatat hadir pada pertemuan ini.',
                'kelas' => $kelas->label(),
                'pertemuan' => $sesi->pertemuan,
                'waktu' => $ada->waktu?->format('H:i') ?? '-',
            ]);
        }

        // izin/sakit yang sudah dicatat asisten tidak ditimpa oleh scan
        if ($ada && $ada->metode !== 'qr' && in_array($ada->status, ['I', 'S'], true)) {
            return response()->json(['ok' => false, 'pesan' => 'Asisten sudah mencatatmu '.Absensi::STATUS[$ada->status].' di pertemuan ini. Hubungi asisten bila ingin diubah.'], 409);
        }

        // 2) radius kampus
        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $akurasi = $request->akurasi !== null ? (int) round($request->akurasi) : null;
        $jarak = $kelas->jarakDari($lat, $lng);
        // GPS di dalam gedung sering meleset: beri kelonggaran sebesar akurasinya, maksimal 50 m
        if ($jarak !== null && $jarak > $kelas->radius + min($akurasi ?? 0, 50)) {
            $this->catatDitolak($kelas, $sesi, $mhs, "di luar radius ({$jarak} m dari titik kelas, batas {$kelas->radius} m)", $request);

            return response()->json(['ok' => false, 'pesan' => "Kamu berada sekitar {$jarak} m dari lokasi kelas (batas {$kelas->radius} m). Absen hanya bisa dilakukan di area kelas."], 403);
        }

        // 3) tanda scan mencurigakan (tetap dicatat hadir, asisten yang memutuskan)
        $tanda = [];
        if ($akurasi === null) {
            $tanda[] = 'Akurasi GPS tidak terbaca';
        } elseif ($akurasi > self::AKURASI_WAJAR) {
            $tanda[] = "Akurasi GPS rendah (±{$akurasi} m)";
        }
        // HP asli di satu ruangan hampir tidak mungkin melaporkan koordinat yang persis sama (≈10 cm)
        $kembar = Absensi::with('mahasiswa')
            ->where(['kelas_id' => $kelas->id, 'pertemuan' => $sesi->pertemuan, 'metode' => 'qr'])
            ->where('mahasiswa_id', '!=', $mhs->id)
            ->whereBetween('lat', [$lat - 0.0000005, $lat + 0.0000005])
            ->whereBetween('lng', [$lng - 0.0000005, $lng + 0.0000005])
            ->get();
        foreach ($kembar as $k) {
            $tanda[] = 'Koordinat sama persis dengan '.$k->mahasiswa->npm;
            $k->update(['tanda' => array_values(array_unique([...($k->tanda ?? []), 'Koordinat sama persis dengan '.$mhs->npm]))]);
        }

        $a = Absensi::updateOrCreate(
            ['kelas_id' => $kelas->id, 'mahasiswa_id' => $mhs->id, 'pertemuan' => $sesi->pertemuan],
            ['status' => 'H', 'metode' => 'qr', 'sesi_absen_id' => $sesi->id, 'waktu' => now(),
                'lat' => $lat, 'lng' => $lng, 'akurasi' => $akurasi, 'jarak' => $jarak,
                'perangkat' => $perangkat, 'tanda' => $tanda ?: null]
        );

        return response()->json([
            'ok' => true,
            'pesan' => 'Kehadiran berhasil dicatat.',
            'kelas' => $kelas->label(),
            'pertemuan' => $sesi->pertemuan,
            'waktu' => $a->waktu->format('H:i'),
            'lokasi' => $a->teksLokasi(),
        ]);
    }

    /** Token acak yang disimpan di cookie HP; dibuat sekali lalu dipakai terus. */
    private function tokenPerangkat(Request $request): string
    {
        $token = $request->cookie(self::COOKIE_PERANGKAT);
        if (! is_string($token) || strlen($token) !== 40) {
            $token = Str::random(40);
            Cookie::queue(Cookie::make(self::COOKIE_PERANGKAT, $token, 60 * 24 * 365 * 5, httpOnly: true, sameSite: 'lax'));
            $request->cookies->set(self::COOKIE_PERANGKAT, $token);
        }

        return $token;
    }

    /** @return array{0:string,1:string}|null [alasan untuk log, pesan untuk mahasiswa] */
    private function periksaPerangkat(Mahasiswa $mhs, string $perangkat): ?array
    {
        if ($mhs->perangkat === $perangkat) {
            return null;
        }
        if ($mhs->perangkat !== null) {
            return ['memakai HP lain (akun terkunci di HP sebelumnya)',
                'Akun kamu terkunci di HP lain. Absen hanya bisa dari HP yang pertama kali dipakai scan. Kalau ganti HP atau menghapus data browser, minta asisten mereset perangkat.'];
        }
        $pemilik = Mahasiswa::where('perangkat', $perangkat)->first();
        if ($pemilik) {
            return ['memakai HP yang sudah terikat ke '.$pemilik->npm,
                'HP ini sudah dipakai absen oleh akun lain. Satu HP hanya untuk satu akun mahasiswa.'];
        }
        $mhs->update(['perangkat' => $perangkat, 'perangkat_terikat_at' => now()]);

        return null;
    }

    private function catatDitolak($kelas, SesiAbsen $sesi, Mahasiswa $mhs, string $alasan, Request $request): void
    {
        LogAktivitas::catat($kelas, 'scan_ditolak', "Scan {$mhs->npm} {$mhs->nama} ditolak: {$alasan}", [
            'mahasiswa_id' => $mhs->id, 'pertemuan' => $sesi->pertemuan,
            'lat' => $request->lat, 'lng' => $request->lng, 'akurasi' => $request->akurasi,
        ]);
    }

    public function riwayat(Request $request)
    {
        $mhs = $request->user('mahasiswa');
        $kelas = $mhs->kelas()->get();
        $absen = $mhs->absensi()->get()->groupBy('kelas_id');

        return view('mahasiswa.riwayat', compact('mhs', 'kelas', 'absen'));
    }

    public function akun(Request $request)
    {
        return view('mahasiswa.akun', ['mhs' => $request->user('mahasiswa')]);
    }
}
