<?php

namespace App\Support;

/**
 * Ikon garis 24×24 (gaya Lucide, stroke 2) supaya semua ikon seragam ukuran dan ketebalannya.
 * Pakai {!! \App\Support\Ikon::svg('pin') !!}; ukuran mengikuti font-size (kelas .ic) kecuali diberi kelas lain.
 */
class Ikon
{
    public const PATH = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v12h14V9"/><path d="M10 21v-6h4v6"/>',
        'buku' => '<path d="M4 19.5V5a2 2 0 0 1 2-2h14v16H6.5A2.5 2.5 0 0 0 4 21.5"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>',
        'scan' => '<path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/>',
        'jam' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'user-plus' => '<circle cx="10" cy="8" r="4"/><path d="M2 21a8 8 0 0 1 13.3-6"/><path d="M19 15v6M16 18h6"/>',
        'pin' => '<path d="M20 10c0 5-8 12-8 12s-8-7-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'klip' => '<path d="m21 11-8.6 8.6a5.5 5.5 0 0 1-7.8-7.8l8.6-8.6a3.7 3.7 0 0 1 5.2 5.2l-8.6 8.6a1.8 1.8 0 0 1-2.6-2.6L16 7"/>',
        'hp' => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>',
        'cek-bulat' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'unduh' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
        'tautan' => '<path d="M14 4h6v6"/><path d="M20 4 10 14"/><path d="M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>',
    ];

    public static function svg(string $nama, string $kelas = 'ic'): string
    {
        return '<svg class="'.$kelas.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.(self::PATH[$nama] ?? '').'</svg>';
    }
}
