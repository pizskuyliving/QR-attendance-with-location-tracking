<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class KodeVerifikasi extends Mailable
{
    use Queueable;

    public function __construct(public string $nama, public string $kode, public int $menit) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode verifikasi FLEX: '.$this->kode);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.kode-verifikasi', text: 'mail.kode-verifikasi-teks');
    }
}
