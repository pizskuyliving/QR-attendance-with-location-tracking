<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'office_id', 'date',
        'check_in_at', 'check_in_lat', 'check_in_lng', 'check_in_distance',
        'check_out_at', 'check_out_lat', 'check_out_lng', 'check_out_distance',
        'early_leave', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'early_leave' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
