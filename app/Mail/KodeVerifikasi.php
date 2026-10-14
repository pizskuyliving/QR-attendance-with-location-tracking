<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class KodeVerifikasi extends Mailable
{
    use Queueable;

    /** @param 'daftar'|'sandi' $tujuan pendaftaran akun baru, atau atur ulang kata sandi */
    public function __construct(public string $nama, public string $kode, public int $menit, public string $tujuan = 'daftar') {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->tujuan === 'sandi' ? 'Kode atur ulang kata sandi FLEX: ' : 'Kode verifikasi FLEX: ').$this->kode);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.kode-verifikasi', text: 'mail.kode-verifikasi-teks');
    }
}
