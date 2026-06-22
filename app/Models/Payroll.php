<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'user_id',
        'type', // regular, special
        'period_type',
        'month',
        'year',
        'period_start',
        'period_end',
        'period_sequence',
        'basic_salary',
        'allowances',
        'deductions',
        'overtime_pay',
        'total_allowance',
        'total_deduction',
        'net_salary',
        'taxable_income',
        'non_taxable_income',
        'pph21_tax',
        'bpjs_employee_total',
        'bpjs_employer_total',
        'employer_contribution_total',
        'details', // snapshot of components
        'coretax_payload',
        'status',
        'generated_by',
        'paid_at',
        'payslip_password_requested_at',
        'pdf_emailed_at',
    ];

    protected $casts = [
        'allowances' => 'array',
        'deductions' => 'array',
        'details' => 'array',
        'coretax_payload' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'paid_at' => 'datetime',
        'payslip_password_requested_at' => 'datetime',
        'pdf_emailed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
