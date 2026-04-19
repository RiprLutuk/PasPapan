<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    use HasFactory;
    use HasTimestamps;

    protected $fillable = [
        'name',
        'value',
        'latitude',
        'longitude',
        'radius',
        'secret_key',
        'dynamic_enabled',
        'dynamic_ttl_seconds',
    ];

    protected $casts = [
        'dynamic_enabled' => 'boolean',
        'dynamic_ttl_seconds' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'float',
    ];

    function getLatLngAttribute(): array|null
    {
        if (is_null($this->latitude) || is_null($this->longitude)) {
            return null;
        }
        return  [
            'lat' => $this->latitude,
            'lng' => $this->longitude
        ];
    }
}
