<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiTemplate extends Model
{
    protected $fillable = [
        'name',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'integer',
        'is_active' => 'boolean',
    ];

    public function evaluations()
    {
        return $this->hasMany(AppraisalEvaluation::class);
    }
}
