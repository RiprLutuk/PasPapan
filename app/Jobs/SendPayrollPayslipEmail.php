<?php

namespace App\Jobs;

use App\Mail\PayrollPayslipPasswordRequiredMail;
use App\Mail\PayrollPayslipPdfMail;
use App\Models\Payroll;
use App\Support\PayslipPdfFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPayrollPayslipEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $payrollId) {}

    public function handle(PayslipPdfFactory $pdfFactory): void
    {
        $payroll = Payroll::query()
            ->with('user')
            ->whereKey($this->payrollId)
            ->where('status', 'paid')
            ->first();

        if (! $payroll || ! $payroll->user || ! $payroll->user->email || $payroll->pdf_emailed_at) {
            return;
        }

        $password = $pdfFactory->currentPasswordFor($payroll->user);

        if ($password === null) {
            $this->sendPasswordSetupMail($payroll);

            return;
        }

        $pdfContent = $pdfFactory->encryptedPdf($payroll, $password);

        Mail::to($payroll->user->email)->send(new PayrollPayslipPdfMail(
            $payroll,
            $pdfContent,
            $pdfFactory->fileName($payroll),
        ));

        $payroll->forceFill(['pdf_emailed_at' => now()])->save();
    }

    private function sendPasswordSetupMail(Payroll $payroll): void
    {
        $recentPromptExists = Payroll::query()
            ->where('user_id', $payroll->user_id)
            ->where('id', '!=', $payroll->id)
            ->whereNotNull('payslip_password_requested_at')
            ->where('payslip_password_requested_at', '>=', now()->subDay())
            ->exists();

        if (! $payroll->payslip_password_requested_at && ! $recentPromptExists) {
            Mail::to($payroll->user->email)->send(new PayrollPayslipPasswordRequiredMail($payroll));
        }

        $payroll->forceFill(['payslip_password_requested_at' => now()])->save();
    }
}
