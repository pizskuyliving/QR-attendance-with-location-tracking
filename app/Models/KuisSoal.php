<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KuisSoal extends Model
{
    protected $table = 'kuis_soal';

    protected $fillable = ['kuis_id', 'urutan', 'pertanyaan', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'kunci'];

    public function kuis(): BelongsTo
    {
        return $this->belongsTo(Kuis::class);
    }

    public function pilihan(string $huruf): string
    {
        return $this->{'pilihan_'.$huruf};
    }
}
