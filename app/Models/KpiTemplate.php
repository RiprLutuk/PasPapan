<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiTemplate extends Model
{
    protected $fillable = [
        'kpi_group_id',
        'name',
        'indicator_description',
        'weight',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'integer',
        'is_active' => 'boolean',
    ];

    public function kpiGroup()
    {
        return $this->belongsTo(KpiGroup::class);
    }

    public function evaluations()
    {
        return $this->hasMany(AppraisalEvaluation::class);
    }
}
