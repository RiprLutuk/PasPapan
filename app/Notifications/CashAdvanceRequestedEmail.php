<?php

namespace App\Notifications;

use App\Models\CashAdvance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CashAdvanceRequestedEmail extends Notification implements ShouldQueue
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

        // Month name
        $paymentMonthName = \Carbon\Carbon::create()->month((int) $this->advance->payment_month)->format('F');

        $details = [
            'Staff' => $userName,
            'Purpose' => $this->advance->purpose ?? '-',
            'Amount' => 'Rp ' . $amount,
            'Deduction' => $paymentMonthName . ' ' . $this->advance->payment_year,
        ];

        // Check Role
        $url = route('team-kasbon');
        if ($notifiable instanceof \App\Models\User && $notifiable->isAdmin) {
            $url = route('admin.manage-kasbon');
        } elseif ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            $url = route('admin.manage-kasbon');
        }

        return (new MailMessage)
            ->from(config('mail.from.address'), $appName)
            ->replyTo(
                \App\Models\Setting::getValue('mail.reply_to_address', config('mail.from.address')),
                $appName
            )
            ->subject($appName . " - " . __('New Kasbon Request') . ": $userName")
            ->view('emails.aligned-request', [
                'greeting' => __('Hello, Approver!'),
                'introLines' => [
                    __('User') . " **{$userName}** " . __('has submitted a new Kasbon request.')
                ],
                'details' => $details,
                'actionText' => __('Review Request'),
                'actionUrl' => $url,
                'outroLines' => [
                    __('Please review this request at your earliest convenience.')
                ]
            ]);
    }
}
