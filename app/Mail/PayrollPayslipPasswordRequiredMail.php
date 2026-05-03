<?php

namespace App\Mail;

use App\Models\Payroll;
use App\Support\MailBranding;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayrollPayslipPasswordRequiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payroll $payroll) {}

    public function envelope(): Envelope
    {
        $period = $this->periodLabel();
        $appName = MailBranding::companyName();

        return new Envelope(
            from: new Address(MailBranding::fromAddress(), $appName),
            replyTo: [new Address(MailBranding::replyToAddress(), $appName)],
            subject: MailBranding::subject(__('Set Payslip Password').' - '.$period),
        );
    }

    public function content(): Content
    {
        $period = $this->periodLabel();

        return new Content(
            view: 'emails.aligned-request',
            with: [
                'greeting' => __('Hello, :name!', ['name' => $this->payroll->user?->name ?? __('there')]),
                'introLines' => [
                    __('Your payroll for :period has been marked as paid.', ['period' => $period]),
                    __('Please set a payslip protection password before we send the encrypted PDF attachment.'),
                ],
                'details' => [
                    __('Period') => $period,
                ],
                'actionText' => __('Set Protection Password'),
                'actionUrl' => route('my-payslips'),
                'outroLines' => [
                    __('After the password is set, the queued worker will send the latest encrypted payslip PDF to this email.'),
                ],
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function periodLabel(): string
    {
        return Carbon::create()
            ->month((int) $this->payroll->month)
            ->translatedFormat('F').' '.$this->payroll->year;
    }
}
