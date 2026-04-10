<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppraisalEvaluation extends Model
{
    protected $fillable = [
        'appraisal_id',
        'kpi_template_id',
        'self_score',
        'manager_score',
        'comments',
    ];

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function kpiTemplate()
    {
        return $this->belongsTo(KpiTemplate::class);
    }
}
