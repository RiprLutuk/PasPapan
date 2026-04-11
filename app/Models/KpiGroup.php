<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiGroup extends Model
{
    protected $fillable = [
        'name',
        'weight',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'weight' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function kpiTemplates()
    {
        return $this->hasMany(KpiTemplate::class);
    }

    public function activeKpiTemplates()
    {
        return $this->hasMany(KpiTemplate::class)->where('is_active', true);
    }
}
