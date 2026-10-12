<?php

use App\Mail\KodeVerifikasi;
use App\Services\Pengingat;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

// Uji pengaturan SMTP di .env: php artisan flex:tes-email [alamat]; tanpa alamat dikirim ke MAIL_FROM_ADDRESS
Artisan::command('flex:tes-email {alamat?}', function (?string $alamat = null) {
    $alamat ??= config('mail.from.address');
    $this->line('Mailer: '.config('mail.default').' · host: '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').' · pengirim: '.config('mail.from.name').' <'.config('mail.from.address').'>');
    if (config('mail.default') === 'log') {
        $this->warn('MAIL_MAILER masih "log": email hanya ditulis ke storage/logs, tidak benar-benar dikirim.');
    }
    try {
        Mail::to($alamat)->send(new KodeVerifikasi('Tes', '123456', 10));
        $this->info(config('mail.default') === 'log'
            ? 'Email uji ditulis ke storage/logs/laravel.log.'
            : "Terkirim ke {$alamat}. Cek kotak masuk dan folder Spam.");
    } catch (\Throwable $e) {
        $this->error('Gagal: '.$e->getMessage());

        return 1;
    }
})->purpose('Kirim email uji untuk memastikan SMTP benar');

// Peringatan batas kehadiran & pengingat tugas H-1, dikirim lewat email.
// Agar berjalan otomatis, biarkan `php artisan schedule:work` menyala (atau cron/Task Scheduler: `php artisan schedule:run` tiap menit).
Artisan::command('flex:kirim-pengingat', function () {
    $p = new Pengingat;
    $p->kehadiran();
    $p->tugas();
    $this->info("Pengingat terkirim: {$p->terkirim}, gagal: {$p->gagal}.");
})->purpose('Kirim email peringatan kehadiran dan pengingat tugas H-1');

Schedule::command('flex:kirim-pengingat')->hourly()->withoutOverlapping();
