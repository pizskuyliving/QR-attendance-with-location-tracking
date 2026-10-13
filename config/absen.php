<?php

return [
    // Nilai di file ini adalah bawaan. Institusi, domain email, pendaftaran, ukuran unggahan, dan nilai huruf
    // bisa diubah laboran dari menu Sistem (tabel pengaturan) dan nilai dari sana yang dipakai.

    // identitas sistem (dipakai di judul halaman, header, footer, email, dan aplikasi authenticator)
    'nama' => 'FLEX',
    'nama_panjang' => 'Flexible Learning Experience',
    'institusi' => env('NAMA_INSTITUSI', 'Universitas Islam Riau'),
    'versi' => '1.0',

    // pilihan kelas saat mahasiswa daftar dan saat asisten membuat kelas
    'kelas' => ['A', 'B', 'C', 'D', 'E', 'F', 'G'],

    // domain email kampus yang boleh dipakai daftar; subdomain ikut diterima (mis. student.uir.ac.id juga menerima x.student.uir.ac.id).
    // Pisahkan dengan koma bila lebih dari satu. Kosongkan untuk menerima semua email.
    'domain_email' => array_values(array_filter(array_map(
        fn ($d) => strtolower(trim($d, " .@")),
        explode(',', (string) env('DOMAIN_EMAIL_MAHASISWA', 'student.uir.ac.id'))
    ))),

    // pendaftaran mandiri dari halaman masuk; laboran bisa menutupnya dari menu Sistem
    'daftar_mahasiswa' => true,
    'daftar_asisten' => true,

    // unggahan: ukuran maksimal (KB) dan jenis file yang diterima
    'maks_unggah_kb' => (int) env('MAKS_UNGGAH_KB', 20480),
    'ekstensi_materi' => ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip', 'rar', '7z', 'png', 'jpg', 'jpeg'],
    'ekstensi_tugas' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'csv', 'txt', 'zip', 'rar', '7z', 'png', 'jpg', 'jpeg', 'sql', 'py', 'java', 'php', 'ipynb'],

    // konversi nilai angka ke huruf (batas bawah), urut dari terbesar
    'huruf' => ['A' => 85, 'B' => 70, 'C' => 55, 'D' => 40, 'E' => 0],
];
