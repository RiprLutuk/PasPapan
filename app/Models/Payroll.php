<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'user_id',
        'type', // regular, special
        'month',
        'year',
        'basic_salary',
        'allowances',
        'deductions',
        'overtime_pay',
        'total_allowance',
        'total_deduction',
        'net_salary',
        'details', // snapshot of components
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
        'paid_at' => 'datetime',
        'payslip_password_requested_at' => 'datetime',
        'pdf_emailed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
