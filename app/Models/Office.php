<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude', 'radius', 'is_active'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Jarak (meter) dari titik kantor ke koordinat tertentu, rumus Haversine. */
    public function distanceTo(float $lat, float $lng): int
    {
        $earthRadius = 6371000; // meter

        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return (int) round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
