<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAsset extends Model
{
    protected $fillable = [
        'name',
        'serial_number',
        'type',
        'user_id',
        'date_assigned',
        'return_date',
        'status',
        'notes',
    ];

    /**
     * Get the user that was assigned this asset.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
