<?php

namespace App\Support;

use App\Models\Payroll;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PayslipPdfFactory
{
    public function encryptedPdf(Payroll $payroll, string $password): string
    {
        $payroll->loadMissing(['user.division', 'user.jobTitle']);

        $pdf = Pdf::loadView('pdf.payslip', [
            'payroll' => $payroll,
        ])->setPaper('a4');

        $pdf->setEncryption($password, Str::random(32));

        return $pdf->output();
    }

    public function currentPasswordFor(User $user): ?string
    {
        if (! $user->hasValidPayslipPassword() || blank($user->payslip_password)) {
            return null;
        }

        try {
            $password = Crypt::decryptString((string) $user->payslip_password);
        } catch (\Throwable) {
            return null;
        }

        return filled($password) ? $password : null;
    }

    public function fileName(Payroll $payroll): string
    {
        $payroll->loadMissing('user');

        return sprintf(
            'payslip-%s-%s-%s.pdf',
            $payroll->year,
            str_pad((string) $payroll->month, 2, '0', STR_PAD_LEFT),
            Str::slug($payroll->user?->name ?? 'employee'),
        );
    }
}
