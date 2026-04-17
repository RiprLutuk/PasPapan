<?php

namespace App\Notifications;

use App\Models\CashAdvance;
use App\Support\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CashAdvanceUpdatedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public $advance;

    public function __construct(CashAdvance $advance)
    {
        $this->advance = $advance;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $userName = $this->advance->user->name ?? __('Unknown');
        $amount = number_format($this->advance->amount ?? 0, 0, ',', '.');

        $appName = MailBranding::companyName();
        $statusLabel = __(ucfirst($this->advance->status));

        $paymentMonthName = \Carbon\Carbon::create()->month((int) $this->advance->payment_month)->translatedFormat('F');

        $details = [
            __('Purpose') => $this->advance->purpose ?? '-',
            __('Amount') => 'Rp ' . $amount,
            __('Deduction') => $paymentMonthName . ' ' . $this->advance->payment_year,
            __('Status') => $statusLabel,
        ];

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('Cash Advance Request') . ' - ' . $statusLabel))
            ->view('emails.aligned-request', [
                'greeting' => __('Hello, :name!', ['name' => $userName]),
                'introLines' => [
                    __('Your cash advance request has been updated. Result: **:status**', ['status' => $statusLabel])
                ],
                'details' => $details,
                'actionText' => __('View Details'),
                'actionUrl' => route('my-kasbon'),
                'outroLines' => [
                    __('Thank you for using our application.')
                ]
            ]);
    }
}
