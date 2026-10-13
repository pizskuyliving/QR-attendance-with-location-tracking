<?php

use App\Mail\KodeVerifikasi;
use App\Services\Pengingat;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

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

// Pindah dari SQLite ke database baru (mis. PostgreSQL): atur DB_* di .env, `php artisan migrate`, lalu jalankan ini.
Artisan::command('flex:salin-sqlite {--file= : lokasi file SQLite lama}', function () {
    $file = $this->option('file') ?: database_path('database.sqlite');
    $tujuan = DB::connection();
    if (! is_file($file)) {
        $this->error("File SQLite tidak ditemukan: {$file}");

        return 1;
    }
    if ($tujuan->getDriverName() === 'sqlite') {
        $this->error('DB_CONNECTION di .env masih sqlite. Ubah ke pgsql/mysql dan jalankan `php artisan migrate` dulu.');

        return 1;
    }
    config(['database.connections.sumber_sqlite' => ['driver' => 'sqlite', 'database' => $file, 'prefix' => '', 'foreign_key_constraints' => false]]);
    $sumber = DB::connection('sumber_sqlite');
    $lewati = ['migrations', 'sqlite_sequence', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs'];
    $tabel = collect($sumber->select("select name from sqlite_master where type = 'table'"))->pluck('name')->diff($lewati)->values();

    if ($tabel->contains(fn ($t) => Schema::hasTable($t) && DB::table($t)->exists())) {
        $this->error('Database tujuan sudah berisi data. Jalankan `php artisan migrate:fresh` dulu agar kosong.');

        return 1;
    }

    $pg = $tujuan->getDriverName() === 'pgsql';
    $tujuan->transaction(function () use ($tabel, $sumber, $tujuan, $pg) {
        // abaikan urutan foreign key selama menyalin (PostgreSQL: butuh user superuser, mis. postgres)
        $tujuan->statement($pg ? "SET session_replication_role = 'replica'" : 'SET FOREIGN_KEY_CHECKS=0');
        foreach ($tabel as $t) {
            if (! Schema::hasTable($t)) {
                $this->warn("Lewati {$t}: tidak ada di database tujuan.");

                continue;
            }
            // kolom boolean disimpan 0/1 di SQLite; PostgreSQL butuh true/false
            $boolean = collect(Schema::getColumns($t))->filter(fn ($c) => in_array($c['type_name'], ['bool', 'boolean', 'tinyint(1)']))->pluck('name')->all();
            $jumlah = 0;
            foreach ($sumber->table($t)->cursor()->chunk(500) as $baris) {
                $data = $baris->map(function ($r) use ($boolean) {
                    $r = (array) $r;
                    foreach ($boolean as $k) {
                        if (array_key_exists($k, $r) && $r[$k] !== null) {
                            $r[$k] = (bool) $r[$k];
                        }
                    }

                    return $r;
                })->all();
                $tujuan->table($t)->insert($data);
                $jumlah += count($data);
            }
            if ($pg && Schema::hasColumn($t, 'id')) {
                $tujuan->statement("select setval(pg_get_serial_sequence('\"{$t}\"', 'id'), coalesce((select max(id) from \"{$t}\"), 1), (select max(id) from \"{$t}\") is not null)");
            }
            $this->line(str_pad($t, 24).$jumlah.' baris');
        }
        $pg ? $tujuan->statement("SET session_replication_role = 'origin'") : $tujuan->statement('SET FOREIGN_KEY_CHECKS=1');
    });
    $this->info('Selesai. Semua pengguna perlu login ulang.');
})->purpose('Salin semua data dari database SQLite lama ke database di .env');
