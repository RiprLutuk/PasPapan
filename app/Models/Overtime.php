<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'duration',
        'reason',
        'status',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get duration as "Xh Ym" string
     */
    public function getDurationTextAttribute()
    {
        $minutes = $this->duration;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        $text = '';
        if ($hours > 0) {
            $text .= "{$hours}h ";
        }
        if ($mins > 0 || $text == '') {
            $text .= "{$mins}m";
        }

        return trim($text);
    }
}
