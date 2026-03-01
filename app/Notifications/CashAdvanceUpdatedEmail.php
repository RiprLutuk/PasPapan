<?php

namespace App\Notifications;

use App\Models\CashAdvance;
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
        $userName = $this->advance->user->name ?? 'Unknown';
        $amount = number_format($this->advance->amount ?? 0, 0, ',', '.');

        $appName = \App\Models\Setting::getValue('app.company_name', config('app.name', 'PasPapan'));
        $statusLabel = ucfirst($this->advance->status); // Approved or Rejected

        $paymentMonthName = \Carbon\Carbon::create()->month($this->advance->payment_month)->format('F');

        $details = [
            'Purpose' => $this->advance->purpose ?? '-',
            'Amount' => 'Rp ' . $amount,
            'Deduction' => $paymentMonthName . ' ' . $this->advance->payment_year,
            'Status' => $statusLabel
        ];

        return (new MailMessage)
            ->from(config('mail.from.address'), $appName)
            ->replyTo(
                \App\Models\Setting::getValue('mail.reply_to_address', config('mail.from.address')),
                $appName
            )
            ->subject($appName . " - " . __('Kasbon Request') . " " . __($statusLabel))
            ->view('emails.aligned-request', [
                'greeting' => __('Hello') . " " . $userName . "!",
                'introLines' => [
                    __('Your Kasbon request has been updated. Result:') . " **" . __($statusLabel) . "**"
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
