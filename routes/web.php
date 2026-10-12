<?php

use App\Http\Controllers\Asisten\AbsensiController;
use App\Http\Controllers\Asisten\DuaLangkahController;
use App\Http\Controllers\Asisten\KelasController;
use App\Http\Controllers\Asisten\MahasiswaController;
use App\Http\Controllers\Asisten\ProfilController;
use App\Http\Controllers\Asisten\SesiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DaftarAsistenController;
use App\Http\Controllers\Mahasiswa\AbsenController;
use App\Http\Controllers\Mahasiswa\KelasController as KelasMahasiswaController;
use App\Http\Controllers\Mahasiswa\MatkulController;
use App\Http\Controllers\Asisten\MateriController;
use App\Http\Controllers\Asisten\TugasController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

// ---------- Login ----------
Route::get('/', [AuthController::class, 'formMahasiswa'])->name('login');
Route::post('/login', [AuthController::class, 'loginMahasiswa'])->middleware('throttle:masuk')->name('login.mahasiswa');
Route::get('/asisten/login', [AuthController::class, 'formAsisten'])->name('asisten.login');
Route::post('/asisten/login', [AuthController::class, 'loginAsisten'])->middleware('throttle:masuk');
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

Route::get('/lupa-sandi', [AuthController::class, 'lupa'])->name('lupa');
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
    Route::get('/tugas/{tugas}', [MatkulController::class, 'tugas'])->name('tugas');
    Route::post('/tugas/{tugas}', [MatkulController::class, 'kumpul'])->middleware('throttle:20,1')->name('tugas.kumpul');
    Route::get('/tugas/{tugas}/unduh', [MatkulController::class, 'unduhKiriman'])->name('tugas.unduh');
    Route::post('/kelas/{kelas}', [KelasMahasiswaController::class, 'gabung'])->middleware('throttle:30,1')->name('kelas.gabung');
    Route::delete('/kelas/{kelas}', [KelasMahasiswaController::class, 'keluar'])->middleware('throttle:30,1')->name('kelas.keluar');
    Route::post('/akun/sandi', [AuthController::class, 'gantiPassword'])->middleware('throttle:5,1')->name('sandi');
});

// ---------- Asisten ----------
Route::middleware(['auth:web', 'asisten.aktif'])->prefix('asisten')->name('asisten.')->group(function () {
    Route::get('/', [KelasController::class, 'dashboard'])->name('dashboard');

    Route::post('/kelas', [KelasController::class, 'store'])->name('kelas.store');
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
    Route::get('/kelas/{kelas}/export', [AbsensiController::class, 'export'])->name('absensi.export');
    Route::put('/kelas/{kelas}/bobot', [KelasController::class, 'bobot'])->name('kelas.bobot');

    Route::post('/kelas/{kelas}/materi', [MateriController::class, 'store'])->name('materi.store');
    Route::get('/kelas/{kelas}/materi/{materi}', [MateriController::class, 'unduh'])->name('materi.unduh');
    Route::delete('/kelas/{kelas}/materi/{materi}', [MateriController::class, 'destroy'])->name('materi.destroy');

    Route::post('/kelas/{kelas}/tugas', [TugasController::class, 'store'])->name('tugas.store');
    Route::get('/tugas/{tugas}', [TugasController::class, 'show'])->name('tugas.show');
    Route::put('/tugas/{tugas}', [TugasController::class, 'update'])->name('tugas.update');
    Route::delete('/tugas/{tugas}', [TugasController::class, 'destroy'])->name('tugas.destroy');
    Route::post('/tugas/{tugas}/kiriman/{pengumpulan}/nilai', [TugasController::class, 'nilai'])->name('tugas.nilai');
    Route::get('/tugas/{tugas}/kiriman/{pengumpulan}', [TugasController::class, 'unduh'])->name('tugas.unduh');
    Route::get('/tugas/{tugas}/zip', [TugasController::class, 'unduhSemua'])->name('tugas.zip');

    Route::post('/kelas/{kelas}/sesi', [SesiController::class, 'buka'])->name('sesi.buka');
    Route::get('/sesi/{sesi}', [SesiController::class, 'show'])->name('sesi.show');
    Route::get('/sesi/{sesi}/status', [SesiController::class, 'status'])->name('sesi.status');
    Route::post('/sesi/{sesi}/akhiri', [SesiController::class, 'akhiri'])->name('sesi.akhiri');

    Route::get('/profil', [ProfilController::class, 'show'])->name('profil');
    Route::post('/2fa', [DuaLangkahController::class, 'siapkan'])->name('2fa.siapkan');
    Route::post('/2fa/konfirmasi', [DuaLangkahController::class, 'konfirmasi'])->middleware('throttle:10,1')->name('2fa.konfirmasi');
    Route::post('/2fa/batal', [DuaLangkahController::class, 'batalSiapkan'])->name('2fa.batal');
    Route::post('/2fa/cadangan', [DuaLangkahController::class, 'cadanganBaru'])->middleware('throttle:10,1')->name('2fa.cadangan');
    Route::delete('/2fa', [DuaLangkahController::class, 'matikan'])->middleware('throttle:10,1')->name('2fa.matikan');
    Route::delete('/akun/{user}/2fa', [DuaLangkahController::class, 'resetOlehAdmin'])->name('akun.2fa');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::post('/profil/sandi', [AuthController::class, 'gantiPassword'])->middleware('throttle:5,1')->name('sandi');
    Route::post('/akun', [ProfilController::class, 'tambahAsisten'])->name('akun.store');
    Route::put('/akun/{user}', [ProfilController::class, 'resetAsisten'])->name('akun.reset');
    Route::delete('/akun/{user}', [ProfilController::class, 'hapusAsisten'])->name('akun.destroy');
    Route::post('/akun/{user}/setujui', [ProfilController::class, 'setujui'])->name('akun.setujui');
    Route::post('/akun/{user}/tolak', [ProfilController::class, 'tolak'])->name('akun.tolak');
});
