<?php

return [

    // Berapa detik sekali QR di layar kantor berganti.
    'qr_period' => (int) env('ABSEN_QR_PERIOD', 15),

    // Berapa periode ke belakang yang masih diterima.
    // 1 = QR berlaku maksimal ±30 detik (periode berjalan + 1 periode sebelumnya).
    'qr_grace_windows' => (int) env('ABSEN_QR_GRACE', 1),

    // Jam masuk kerja dan toleransi terlambat (menit).
    'work_start' => env('ABSEN_JAM_MASUK', '08:00'),
    'late_tolerance' => (int) env('ABSEN_TOLERANSI_MENIT', 15),

    // Jam pulang. Absen pulang sebelum jam ini ditandai "pulang cepat".
    // Catatan: setelah admin menyimpan halaman Pengaturan, nilai di sana yang dipakai.
    'work_end' => env('ABSEN_JAM_PULANG', '17:00'),

    // Jeda minimal (menit) antara absen masuk dan absen pulang,
    // supaya scan dobel tidak tercatat sebagai pulang.
    'min_minutes_before_checkout' => (int) env('ABSEN_MIN_MENIT_PULANG', 60),

    // Hari kerja (1 = Senin ... 7 = Minggu). Dipakai untuk menghitung "tidak hadir".
    // Contoh Senin–Sabtu: ABSEN_HARI_KERJA=1,2,3,4,5,6
    'work_days' => array_map('intval', explode(',', env('ABSEN_HARI_KERJA', '1,2,3,4,5'))),

    // Akurasi GPS terburuk yang masih diterima (meter).
    'max_gps_accuracy' => (int) env('ABSEN_MAX_AKURASI_GPS', 100),

];
