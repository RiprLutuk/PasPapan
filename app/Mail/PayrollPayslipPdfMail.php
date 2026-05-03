<?php

namespace App\Mail;

use App\Models\Payroll;
use App\Support\MailBranding;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayrollPayslipPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payroll $payroll,
        public string $pdfContent,
        public string $fileName,
    ) {}

    public function envelope(): Envelope
    {
        $period = $this->periodLabel();
        $appName = MailBranding::companyName();

        return new Envelope(
            from: new Address(MailBranding::fromAddress(), $appName),
            replyTo: [new Address(MailBranding::replyToAddress(), $appName)],
            subject: MailBranding::subject(__('Payslip PDF').' - '.$period),
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
                    __('Your payslip PDF for :period is attached to this email.', ['period' => $period]),
                    __('Use your latest payslip protection password to open the PDF file.'),
                ],
                'details' => [
                    __('Period') => $period,
                    __('Paid At') => $this->payroll->paid_at?->format('d M Y H:i') ?? '-',
                ],
                'outroLines' => [
                    __('For security, this attachment is encrypted and can only be opened with your payslip password.'),
                ],
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->fileName)
                ->withMime('application/pdf'),
        ];
    }

    private function periodLabel(): string
    {
        return Carbon::create()
            ->month((int) $this->payroll->month)
            ->translatedFormat('F').' '.$this->payroll->year;
    }
}
