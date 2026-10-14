<?php

namespace App\Models\Concerns;

/** File unggahan (path + nama_file) atau tautan (url); dipakai materi dan lampiran tugas. */
trait Berkas
{
    public function adalahFile(): bool
    {
        return $this->path !== null;
    }

    /** Label & warna chip jenis file: [teks, kelas CSS]. */
    public function jenis(): array
    {
        if (! $this->adalahFile()) {
            return str_contains((string) $this->url, 'youtu') ? ['VID', 'vid'] : ['URL', 'url'];
        }

        return match (strtolower(pathinfo($this->nama_file, PATHINFO_EXTENSION))) {
            'ppt', 'pptx' => ['PPT', 'ppt'],
            'pdf' => ['PDF', 'pdf'],
            'doc', 'docx' => ['DOC', 'doc'],
            'xls', 'xlsx', 'csv' => ['XLS', 'xls'],
            'zip', 'rar', '7z' => ['ZIP', 'zip'],
            'png', 'jpg', 'jpeg', 'gif', 'webp' => ['IMG', 'img'],
            default => ['FILE', 'zip'],
        };
    }
}
