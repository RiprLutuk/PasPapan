<?php

namespace App\Notifications;

use App\Jobs\SendPayrollPayslipEmail;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PayrollPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payroll $payroll,
    ) {}

    public function via(object $notifiable): array
    {
        SendPayrollPayslipEmail::dispatch((int) $this->payroll->id);

        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $period = $this->periodLabel();

        return [
            'type' => 'payroll_paid',
            'title' => __('Payslip Paid'),
            'payroll_id' => $this->payroll->id,
            'period' => $period,
            'status' => $this->payroll->status,
            'message' => __('Your payroll for :period has been marked as paid.', ['period' => $period]),
            'url' => route('my-payslips', absolute: false),
        ];
    }

    private function periodLabel(): string
    {
        return Carbon::create()
            ->month((int) $this->payroll->month)
            ->translatedFormat('F').' '.$this->payroll->year;
    }
}
