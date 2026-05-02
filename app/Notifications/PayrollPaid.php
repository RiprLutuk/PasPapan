<?php

namespace App\Notifications;

use App\Models\Payroll;
use App\Support\MailBranding;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payroll $payroll,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = MailBranding::companyName();
        $period = $this->periodLabel();
        $amount = $this->amountLabel();

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(MailBranding::replyToAddress(), $appName)
            ->subject(MailBranding::subject(__('Payslip Paid').' - '.$period))
            ->view('emails.aligned-request', [
                'greeting' => __('Hello, :name!', ['name' => $notifiable->name]),
                'introLines' => [
                    __('Your payroll for :period has been marked as paid.', ['period' => $period]),
                ],
                'details' => [
                    __('Period') => $period,
                    __('Net Salary') => $amount,
                    __('Paid At') => $this->payroll->paid_at?->format('d M Y H:i') ?? '-',
                ],
                'actionText' => __('Open Payslip'),
                'actionUrl' => route('my-payslips'),
                'outroLines' => [
                    __('Please open My Payslips to review the details.'),
                ],
            ]);
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

    private function amountLabel(): string
    {
        return 'Rp '.number_format((float) $this->payroll->net_salary, 0, ',', '.');
    }
}
