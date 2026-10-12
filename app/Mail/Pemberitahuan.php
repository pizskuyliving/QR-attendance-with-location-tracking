<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email pemberitahuan umum (peringatan kehadiran, pengingat tugas) dengan satu tombol tautan. */
class Pemberitahuan extends Mailable
{
    use Queueable;

    /** @param list<string> $paragraf */
    public function __construct(
        public string $subjek,
        public string $nama,
        public array $paragraf,
        public string $tautan,
        public string $labelTautan,
        public bool $penting = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjek);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.pemberitahuan', text: 'mail.pemberitahuan-teks');
    }
}
