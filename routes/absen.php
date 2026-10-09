<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\QrDisplayController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

/*
| Tambahkan satu baris ini di paling bawah routes/web.php:
|     require __DIR__.'/absen.php';
*/

Route::middleware('auth')->group(function () {

    // Pegawai (admin juga boleh tes)
    Route::get('/absen', [AttendanceController::class, 'scan'])->name('absen.scan');
    Route::post('/absen', [AttendanceController::class, 'store'])
        ->middleware('throttle:10,1') // maks 10 percobaan per menit
        ->name('absen.store');
    Route::get('/absen/riwayat', [AttendanceController::class, 'history'])->name('absen.riwayat');

    // Khusus admin
    Route::middleware(EnsureRole::class.':admin')->group(function () {
        // Layar QR kantor
        Route::get('/layar-qr/{office}', [QrDisplayController::class, 'show'])->name('qr.display');
        Route::get('/layar-qr/{office}/token', [QrDisplayController::class, 'token'])->name('qr.token');

        // Rekap & laporan
        Route::get('/admin/absensi', [AdminAttendanceController::class, 'daily'])->name('admin.harian');
        Route::get('/admin/rekap', [AdminAttendanceController::class, 'monthly'])->name('admin.rekap');
        Route::get('/admin/rekap/export', [AdminAttendanceController::class, 'export'])->name('admin.rekap.export');

        // Pengaturan jam kerja
        Route::get('/admin/pengaturan', [AdminSettingsController::class, 'edit'])->name('admin.pengaturan');
        Route::put('/admin/pengaturan', [AdminSettingsController::class, 'update'])->name('admin.pengaturan.update');
    });
});
