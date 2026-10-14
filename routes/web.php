<?php

use App\Http\Controllers\Asisten\AbsensiController;
use App\Http\Controllers\Asisten\DuaLangkahController;
use App\Http\Controllers\Asisten\KelasController;
use App\Http\Controllers\Asisten\MahasiswaController;
use App\Http\Controllers\Asisten\ProfilController;
use App\Http\Controllers\Asisten\SesiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DaftarAsistenController;
use App\Http\Controllers\Laboran\LaboranController;
use App\Http\Controllers\LupaSandiController;
use App\Http\Controllers\Dosen\KelasController as DosenKelasController;
use App\Http\Controllers\Dosen\MatkulController as DosenMatkulController;
use App\Http\Controllers\Mahasiswa\AbsenController;
use App\Http\Controllers\Mahasiswa\KelasController as KelasMahasiswaController;
use App\Http\Controllers\Mahasiswa\MatkulController;
use App\Http\Controllers\Asisten\MateriController;
use App\Http\Controllers\Asisten\PesanController;
use App\Http\Controllers\Asisten\SoalUjianController;
use App\Http\Controllers\Asisten\TugasController;
use App\Http\Controllers\Asisten\UjianController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

// ---------- Login ----------
Route::get('/', [AuthController::class, 'formMahasiswa'])->name('login');
Route::get('/masuk/cek', [AuthController::class, 'cekSesi'])->name('masuk.cek');
Route::post('/masuk', [AuthController::class, 'masuk'])->middleware('throttle:masuk')->name('masuk');
Route::get('/asisten/login', [AuthController::class, 'formAsisten'])->name('asisten.login');
Route::get('/asisten/daftar', [DaftarAsistenController::class, 'form'])->name('asisten.daftar');
Route::post('/asisten/daftar', [DaftarAsistenController::class, 'store'])->middleware('throttle:5,10');
Route::get('/asisten/login/2fa', [AuthController::class, 'formDuaLangkah'])->name('asisten.login.2fa');
Route::post('/asisten/login/2fa', [AuthController::class, 'duaLangkah'])->middleware('throttle:10,1');

// ---------- Daftar akun mahasiswa ----------
Route::get('/daftar', [RegisterController::class, 'form'])->name('daftar');
Route::post('/daftar', [RegisterController::class, 'store'])->middleware('throttle:masuk');
Route::get('/daftar/verifikasi', [RegisterController::class, 'formVerifikasi'])->name('daftar.verifikasi');
Route::post('/daftar/verifikasi', [RegisterController::class, 'verifikasi'])->middleware('throttle:10,1');
Route::post('/daftar/kirim-ulang', [RegisterController::class, 'kirimUlang'])->middleware('throttle:3,10')->name('daftar.ulang');

Route::get('/lupa-sandi', [LupaSandiController::class, 'form'])->name('lupa');
Route::post('/lupa-sandi', [LupaSandiController::class, 'kirim'])->middleware('throttle:masuk');
Route::get('/lupa-sandi/kode', [LupaSandiController::class, 'formKode'])->name('lupa.kode');
Route::post('/lupa-sandi/kode', [LupaSandiController::class, 'simpan'])->middleware('throttle:10,1');
Route::post('/lupa-sandi/kirim-ulang', [LupaSandiController::class, 'kirimUlang'])->middleware('throttle:3,10')->name('lupa.ulang');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------- Mahasiswa ----------
Route::middleware('auth:mahasiswa')->prefix('m')->name('mhs.')->group(function () {
    Route::get('/', [AbsenController::class, 'home'])->name('home');
    Route::get('/scan', [AbsenController::class, 'scan'])->name('scan');
    Route::post('/absen', [AbsenController::class, 'absen'])->middleware('throttle:30,1')->name('absen');
    Route::get('/riwayat', [AbsenController::class, 'riwayat'])->name('riwayat');
    Route::get('/akun', [AbsenController::class, 'akun'])->name('akun');
    Route::get('/kelas', [KelasMahasiswaController::class, 'index'])->name('kelas');
    Route::get('/matkul/{kelas}', [MatkulController::class, 'show'])->name('matkul');
    Route::get('/materi/{materi}/unduh', [MatkulController::class, 'unduhMateri'])->name('materi.unduh');
    Route::get('/soal/{soal}/unduh', [MatkulController::class, 'unduhSoal'])->name('soal.unduh');
    Route::prefix('/matkul/{kelas}/ujian/{jenis}')->whereIn('jenis', ['uts', 'uas'])->name('ujian')->group(function () {
        Route::get('/', [MatkulController::class, 'ujian']);
        Route::post('/', [MatkulController::class, 'kumpulUjian'])->middleware('throttle:20,1')->name('.kumpul');
        Route::get('/unduh', [MatkulController::class, 'unduhJawaban'])->name('.unduh');
    });
    Route::get('/tugas/{tugas}', [MatkulController::class, 'tugas'])->name('tugas');
    Route::post('/tugas/{tugas}', [MatkulController::class, 'kumpul'])->middleware('throttle:20,1')->name('tugas.kumpul');
    Route::get('/tugas/{tugas}/unduh', [MatkulController::class, 'unduhKiriman'])->name('tugas.unduh');
    Route::get('/tugas/{tugas}/lampiran/{lampiran}', [MatkulController::class, 'unduhLampiran'])->name('tugas.lampiran');
    Route::post('/kelas/{kelas}', [KelasMahasiswaController::class, 'gabung'])->middleware('throttle:30,1')->name('kelas.gabung');
    Route::delete('/kelas/{kelas}', [KelasMahasiswaController::class, 'keluar'])->middleware('throttle:30,1')->name('kelas.keluar');
    Route::post('/akun/sandi', [AuthController::class, 'gantiPassword'])->middleware('throttle:5,1')->name('sandi');
});

// ---------- Asisten ----------
Route::middleware(['auth:web', 'asisten.aktif'])->prefix('asisten')->name('asisten.')->group(function () {
    Route::get('/', [KelasController::class, 'dashboard'])->name('dashboard');

    // kelola kelas: asisten & admin
    Route::middleware('peran:admin,asisten')->group(function () {
        Route::get('/kelas/{kelas}', [KelasController::class, 'show'])->name('kelas.show');
        Route::put('/kelas/{kelas}', [KelasController::class, 'update'])->name('kelas.update');
        Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');

        Route::post('/kelas/{kelas}/mahasiswa', [MahasiswaController::class, 'store'])->name('mhs.store');
        Route::put('/kelas/{kelas}/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update'])->name('mhs.update');
        Route::delete('/kelas/{kelas}/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'destroy'])->name('mhs.destroy');
        Route::post('/kelas/{kelas}/mahasiswa/{mahasiswa}/reset', [MahasiswaController::class, 'resetPassword'])->name('mhs.reset');
        Route::post('/kelas/{kelas}/mahasiswa/{mahasiswa}/reset-perangkat', [MahasiswaController::class, 'resetPerangkat'])->name('mhs.perangkat');
        Route::post('/kelas/{kelas}/import', [MahasiswaController::class, 'import'])->name('mhs.import');
        Route::get('/kelas/{kelas}/template', [MahasiswaController::class, 'template'])->name('mhs.template');

        Route::post('/kelas/{kelas}/absensi', [AbsensiController::class, 'simpanSel'])->name('absensi.sel');
        Route::post('/kelas/{kelas}/absensi/massal', [AbsensiController::class, 'isiMassal'])->name('absensi.massal');
        Route::put('/kelas/{kelas}/bobot', [KelasController::class, 'bobot'])->name('kelas.bobot');
        Route::put('/kelas/{kelas}/nilai-ujian', [KelasController::class, 'nilaiUjian'])->name('kelas.nilai-ujian');

        Route::post('/kelas/{kelas}/materi', [MateriController::class, 'store'])->name('materi.store');
        Route::get('/kelas/{kelas}/materi/{materi}', [MateriController::class, 'unduh'])->name('materi.unduh');
        Route::delete('/kelas/{kelas}/materi/{materi}', [MateriController::class, 'destroy'])->name('materi.destroy');
        Route::post('/kelas/{kelas}/soal', [SoalUjianController::class, 'store'])->name('soal.store');
        Route::put('/kelas/{kelas}/soal/{soal}/tampil', [SoalUjianController::class, 'tampil'])->name('soal.tampil');
        Route::delete('/kelas/{kelas}/soal/{soal}', [SoalUjianController::class, 'destroy'])->name('soal.destroy');
        Route::prefix('/kelas/{kelas}/ujian/{jenis}')->whereIn('jenis', ['uts', 'uas'])->name('ujian.')->group(function () {
            Route::get('/', [UjianController::class, 'show'])->name('show');
            Route::put('/tenggat', [UjianController::class, 'tenggat'])->name('tenggat');
            Route::post('/nilai/{mahasiswa}', [UjianController::class, 'nilai'])->name('nilai');
            Route::get('/jawaban/{jawaban}', [UjianController::class, 'unduh'])->name('unduh');
            Route::get('/zip', [UjianController::class, 'zip'])->name('zip');
        });

        Route::post('/kelas/{kelas}/tugas', [TugasController::class, 'store'])->name('tugas.store');
        Route::get('/tugas/{tugas}', [TugasController::class, 'show'])->name('tugas.show');
        Route::put('/tugas/{tugas}', [TugasController::class, 'update'])->name('tugas.update');
        Route::delete('/tugas/{tugas}', [TugasController::class, 'destroy'])->name('tugas.destroy');
        Route::post('/tugas/{tugas}/kiriman/{pengumpulan}/nilai', [TugasController::class, 'nilai'])->name('tugas.nilai');
        Route::get('/tugas/{tugas}/kiriman/{pengumpulan}', [TugasController::class, 'unduh'])->name('tugas.unduh');
        Route::get('/tugas/{tugas}/zip', [TugasController::class, 'unduhSemua'])->name('tugas.zip');
        Route::get('/tugas/{tugas}/lampiran/{lampiran}', [TugasController::class, 'unduhLampiran'])->name('tugas.lampiran');
        Route::delete('/tugas/{tugas}/lampiran/{lampiran}', [TugasController::class, 'hapusLampiran'])->name('tugas.lampiran.hapus');

        Route::post('/kelas/{kelas}/sesi', [SesiController::class, 'buka'])->name('sesi.buka');
        Route::get('/sesi/{sesi}', [SesiController::class, 'show'])->name('sesi.show');
        Route::get('/sesi/{sesi}/status', [SesiController::class, 'status'])->name('sesi.status');
        Route::post('/sesi/{sesi}/akhiri', [SesiController::class, 'akhiri'])->name('sesi.akhiri');
    });

    // unduh rekap Excel: juga untuk dosen matkul yang ditautkan (dicek di controller)
    Route::get('/kelas/{kelas}/export', [AbsensiController::class, 'export'])->name('absensi.export');
    Route::get('/kelas/{kelas}/soal/{soal}', [SoalUjianController::class, 'unduh'])->name('soal.unduh');

    // mata kuliah & pantauan asisten: dosen & admin
    // kotak pesan: catatan dosen untuk asisten
    Route::middleware('peran:asisten')->group(function () {
        Route::get('/pesan', [PesanController::class, 'index'])->name('pesan');
        Route::post('/pesan/baca-semua', [PesanController::class, 'bacaSemua'])->name('pesan.baca-semua');
        Route::post('/pesan/{catatan}/baca', [PesanController::class, 'baca'])->name('pesan.baca');
    });

    Route::middleware('peran:admin,dosen')->group(function () {
        Route::get('/matkul', [DosenMatkulController::class, 'index'])->name('matkul.index');
        Route::post('/matkul', [DosenMatkulController::class, 'store'])->name('matkul.store');
        Route::put('/matkul/{matkul}', [DosenMatkulController::class, 'update'])->name('matkul.update');
        Route::post('/matkul/{matkul}/kelas', [DosenKelasController::class, 'store'])->name('matkul.kelas.store');
        Route::post('/matkul/{matkul}/kelas-lama', [DosenKelasController::class, 'ambil'])->name('matkul.kelas.ambil');
        Route::put('/matkul/{matkul}/kelas/{kelas}', [DosenKelasController::class, 'update'])->name('matkul.kelas.update');
        Route::put('/matkul/{matkul}/kelas/{kelas}/asisten', [DosenKelasController::class, 'tugaskan'])->name('matkul.kelas.asisten');
        Route::delete('/matkul/{matkul}/kelas/{kelas}', [DosenKelasController::class, 'destroy'])->name('matkul.kelas.destroy');
        Route::post('/matkul/{matkul}/kelas/{kelas}/catatan', [DosenMatkulController::class, 'catatan'])->middleware('throttle:10,1')->name('matkul.catatan');
        Route::get('/matkul/{matkul}/export', [DosenMatkulController::class, 'export'])->name('matkul.export');
        Route::delete('/matkul/{matkul}', [DosenMatkulController::class, 'destroy'])->name('matkul.destroy');
    });

    // menu khusus laboran
    Route::middleware('peran:admin')->group(function () {
        Route::get('/dosen', [LaboranController::class, 'dosen'])->name('laboran.dosen');
        Route::get('/sistem', [LaboranController::class, 'sistem'])->name('laboran.sistem');
        Route::put('/sistem', [LaboranController::class, 'simpan'])->name('laboran.sistem.simpan');
        Route::get('/akun', [LaboranController::class, 'akun'])->name('laboran.akun');
        Route::delete('/akun/{user}/2fa', [DuaLangkahController::class, 'resetOlehAdmin'])->name('akun.2fa');
        Route::post('/akun', [ProfilController::class, 'tambahAsisten'])->name('akun.store');
        Route::put('/akun/{user}', [ProfilController::class, 'resetAsisten'])->name('akun.reset');
        Route::delete('/akun/{user}', [ProfilController::class, 'hapusAsisten'])->name('akun.destroy');
        Route::post('/akun/{user}/setujui', [ProfilController::class, 'setujui'])->name('akun.setujui');
        Route::post('/akun/{user}/tolak', [ProfilController::class, 'tolak'])->name('akun.tolak');
    });

    Route::get('/profil', [ProfilController::class, 'show'])->name('profil');
    Route::post('/2fa', [DuaLangkahController::class, 'siapkan'])->name('2fa.siapkan');
    Route::post('/2fa/konfirmasi', [DuaLangkahController::class, 'konfirmasi'])->middleware('throttle:10,1')->name('2fa.konfirmasi');
    Route::post('/2fa/batal', [DuaLangkahController::class, 'batalSiapkan'])->name('2fa.batal');
    Route::post('/2fa/cadangan', [DuaLangkahController::class, 'cadanganBaru'])->middleware('throttle:10,1')->name('2fa.cadangan');
    Route::delete('/2fa', [DuaLangkahController::class, 'matikan'])->middleware('throttle:10,1')->name('2fa.matikan');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::post('/profil/sandi', [AuthController::class, 'gantiPassword'])->middleware('throttle:5,1')->name('sandi');
});
