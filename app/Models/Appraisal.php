<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'evaluator_id',
        'calibrator_id',
        'period_month',
        'period_year',
        'status',
        'calibration_status',
        'calibration_notes',
        'attendance_score',
        'subjective_score',
        'final_score',
        'notes',
        'meeting_date',
        'meeting_link',
        'employee_acknowledgement',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'employee_acknowledgement' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function calibrator()
    {
        return $this->belongsTo(User::class, 'calibrator_id');
    }

    public function evaluations()
    {
        return $this->hasMany(AppraisalEvaluation::class);
    }
}
