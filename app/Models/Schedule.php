<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'date',
        'is_off',
    ];

    protected $casts = [
        'date' => 'date',
        'is_off' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function shiftSwapRequests()
    {
        return $this->hasMany(ShiftSwapRequest::class);
    }
}
