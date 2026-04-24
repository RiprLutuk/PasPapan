<?php

namespace App\Notifications;

use App\Models\CashAdvance;
use App\Support\MailBranding;
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
        $userName = $this->advance->user->name ?? __('Unknown');
        $amount = number_format($this->advance->amount ?? 0, 0, ',', '.');

        $appName = MailBranding::companyName();
        $paymentMonthName = \Carbon\Carbon::create()->month((int) $this->advance->payment_month)->translatedFormat('F');

        $details = [
            __('Staff') => $userName,
            __('Purpose') => $this->advance->purpose ?? '-',
            __('Amount') => 'Rp '.$amount,
            __('Deduction') => $paymentMonthName.' '.$this->advance->payment_year,
        ];

        $url = route('home');

        if ($notifiable instanceof \App\Models\User) {
            if ($notifiable->can('manageCashAdvances')) {
                $url = route('admin.manage-kasbon');
            } elseif ($notifiable->can('reviewSubordinateRequests')) {
                $url = route('team-kasbon');
            }
        } elseif ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            $url = route('admin.manage-kasbon');
        }

        return (new MailMessage)
            ->from(MailBranding::fromAddress(), $appName)
            ->replyTo(
                MailBranding::replyToAddress(),
                $appName
            )
            ->subject(MailBranding::subject(__('New Cash Advance Request').' - '.$userName))
            ->view('emails.aligned-request', [
                'greeting' => __('Hello, Approver!'),
                'introLines' => [
                    __('A new cash advance request has been submitted by :name.', ['name' => $userName]),
                ],
                'details' => $details,
                'actionText' => __('Review Request'),
                'actionUrl' => $url,
                'outroLines' => [
                    __('Please review this request at your earliest convenience.'),
                ],
            ]);
    }
}
